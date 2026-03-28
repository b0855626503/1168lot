#!/usr/bin/env node
'use strict';

function readStdin() {
  return new Promise((resolve, reject) => {
    let data = '';
    process.stdin.setEncoding('utf8');
    process.stdin.on('data', (chunk) => {
      data += chunk;
    });
    process.stdin.on('end', () => resolve(data));
    process.stdin.on('error', reject);
  });
}

function emit(payload) {
  process.stdout.write(JSON.stringify(payload));
}

function maskError(error) {
  if (!error) return '';
  return String(error.message || error).slice(0, 2000);
}

function patternMatches(url, pattern) {
  if (!pattern) return false;
  if (pattern.startsWith('/') && !pattern.endsWith('/')) {
    return url.includes(pattern);
  }
  try {
    const regex = new RegExp(pattern);
    return regex.test(url);
  } catch (_err) {
    return url.includes(pattern);
  }
}

function scoreCapture(capture, index) {
  return {
    index,
    exactUrl: capture.match_type === 'exact_url' ? 1 : 0,
    exactMethod: capture.method_match === 'exact' ? 1 : 0,
    exactContentType: capture.content_type_match === 'exact' ? 1 : 0,
    priority: Number(capture.rule_priority || 0),
    ts: Number(capture.timestamp_ms || 0),
  };
}

function compareScore(a, b) {
  if (a.exactUrl !== b.exactUrl) return b.exactUrl - a.exactUrl;
  if (a.exactMethod !== b.exactMethod) return b.exactMethod - a.exactMethod;
  if (a.exactContentType !== b.exactContentType) return b.exactContentType - a.exactContentType;
  if (a.priority !== b.priority) return b.priority - a.priority;
  if (a.ts !== b.ts) return b.ts - a.ts;
  return 0;
}

async function main() {
  const raw = await readStdin();
  const input = JSON.parse(raw || '{}');
  let playwright;
  try {
    playwright = require('playwright');
  } catch (_err) {
    emit({
      ok: false,
      error_code: 'BROWSER_RUNTIME_UNAVAILABLE',
      error_message: 'playwright package is not available',
    });
    return;
  }

  const request = input.request || {};
  const browserCfg = input.browser || {};
  const captureCfg = browserCfg.capture || {};
  const selectionMode = String(input.selection_mode || captureCfg.selection_mode || 'best').toLowerCase();
  const allowDomFallback = !!input.allow_dom_fallback;
  const waitUntil = String(browserCfg.wait_until || 'networkidle');
  const timeoutMs = Number(browserCfg.timeout_ms || 15000);
  const patterns = Array.isArray(captureCfg.url_patterns) ? captureCfg.url_patterns.map((v) => String(v || '').trim()).filter(Boolean) : [];
  const maxCaptured = Math.max(1, Number(captureCfg.max_captured_responses || 3));
  const blockTypes = Array.isArray(browserCfg.block_resource_types) ? browserCfg.block_resource_types.map((v) => String(v || '').trim()) : [];

  const start = Date.now();
  let browser;
  const captures = [];
  const phaseTiming = {};
  try {
    browser = await playwright.chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    if (blockTypes.length > 0) {
      await page.route('**/*', (route) => {
        const resourceType = route.request().resourceType();
        if (blockTypes.includes(resourceType)) {
          return route.abort();
        }
        return route.continue();
      });
    }

    page.on('response', async (response) => {
      if (captures.length >= maxCaptured) return;
      const url = response.url();
      let matched = patterns.length === 0;
      let rulePriority = 0;
      let matchType = 'wildcard';
      if (!matched) {
        for (const pattern of patterns) {
          if (patternMatches(url, pattern)) {
            matched = true;
            rulePriority += 1;
            if (url === pattern) {
              matchType = 'exact_url';
            }
            break;
          }
        }
      }
      if (!matched) return;

      const headers = response.headers() || {};
      const contentType = String(headers['content-type'] || '');
      let body = '';
      try {
        body = await response.text();
      } catch (_err) {
        body = '';
      }
      captures.push({
        url,
        status: response.status(),
        method: response.request().method(),
        response_body: body,
        response_content_type: contentType,
        timestamp_ms: Date.now(),
        rule_priority: rulePriority,
        match_type: matchType,
        method_match: 'exact',
        content_type_match: (contentType.startsWith('application/json') || contentType.startsWith('text/html')) ? 'exact' : 'generic',
      });
    });

    const navStart = Date.now();
    await page.goto(String(request.url || ''), { waitUntil, timeout: timeoutMs });
    phaseTiming.navigation_ms = Date.now() - navStart;

    const selector = String(browserCfg.wait_for_selector || '').trim();
    if (selector) {
      const readyStart = Date.now();
      try {
        await page.waitForSelector(selector, { timeout: timeoutMs });
      } catch (_err) {
        emit({
          ok: false,
          error_code: 'DOM_SELECTOR_NOT_FOUND',
          error_message: 'wait_for_selector not found',
          phase_timing: { ...phaseTiming, readiness_ms: Date.now() - readyStart },
        });
        return;
      }
      phaseTiming.readiness_ms = Date.now() - readyStart;
    }

    await page.waitForTimeout(250);
    phaseTiming.capture_ms = 250;

    let selected = null;
    if (captures.length > 0) {
      if (selectionMode === 'first') {
        selected = captures[0];
      } else if (selectionMode === 'all') {
        selected = captures[captures.length - 1];
      } else {
        const scored = captures.map((c, i) => scoreCapture(c, i)).sort(compareScore);
        if (scored.length > 1 && compareScore(scored[0], scored[1]) === 0) {
          emit({
            ok: false,
            error_code: 'CAPTURE_AMBIGUOUS_MATCH',
            error_message: 'cannot break tie in selection_mode=best',
            network_summary: { captured_count: captures.length },
            phase_timing: phaseTiming,
          });
          return;
        }
        selected = captures[scored[0].index];
      }
    }

    if (!selected && allowDomFallback) {
      const html = await page.content();
      emit({
        ok: true,
        response_body: html,
        response_content_type: 'text/html',
        http_status: 200,
        selected_endpoint: null,
        selected_capture: null,
        selected_driver: 'BROWSER_RUNTIME',
        selection_priority: 'dom_fallback',
        payload_origin: 'dom_fallback',
        duration_ms: Date.now() - start,
        captured_count: captures.length,
        phase_timing: phaseTiming,
        network_summary: { captured_count: captures.length },
      });
      return;
    }

    if (!selected) {
      emit({
        ok: false,
        error_code: 'NO_NETWORK_MATCH',
        error_message: 'no network payload matched capture rules',
        duration_ms: Date.now() - start,
        phase_timing: phaseTiming,
        network_summary: { captured_count: captures.length },
      });
      return;
    }

    emit({
      ok: true,
      response_body: selected.response_body,
      response_content_type: selected.response_content_type || 'application/json',
      http_status: selected.status || 200,
      selected_endpoint: selected.url,
      selected_capture: {
        url: selected.url,
        method: selected.method,
        content_type: selected.response_content_type,
        status: selected.status,
      },
      selected_driver: 'BROWSER_RUNTIME',
      selection_priority: selectionMode,
      payload_origin: 'network_capture',
      duration_ms: Date.now() - start,
      captured_count: captures.length,
      phase_timing: phaseTiming,
      network_summary: {
        captured_count: captures.length,
        urls: captures.map((c) => c.url),
      },
    });
  } catch (error) {
    const message = maskError(error);
    const code = /timeout/i.test(message) ? 'BROWSER_EXECUTOR_TIMEOUT' : 'BROWSER_LAUNCH_FAILED';
    emit({
      ok: false,
      error_code: code,
      error_message: message || 'browser runtime failed',
    });
  } finally {
    if (browser) {
      try {
        await browser.close();
      } catch (_err) {
        // ignore
      }
    }
  }
}

main().catch((error) => {
  emit({
    ok: false,
    error_code: 'BROWSER_EXECUTOR_IO_ERROR',
    error_message: maskError(error),
  });
});

