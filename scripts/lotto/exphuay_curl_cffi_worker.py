#!/usr/bin/env python3
import json
import sys
import time
from typing import Any, Dict


def _safe_error(code: str, message: str, started_at: float) -> Dict[str, Any]:
    return {
        "ok": False,
        "http_status": None,
        "response_body": None,
        "response_content_type": None,
        "duration_ms": int((time.time() - started_at) * 1000),
        "error_code": code,
        "error_message": message,
    }


def main() -> int:
    started_at = time.time()

    try:
        raw = sys.stdin.read()
        payload = json.loads(raw if raw else "{}")
    except Exception as exc:  # noqa: BLE001
        print(json.dumps(_safe_error("PYTHON_WORKER_IO_ERROR", f"invalid stdin payload: {exc}", started_at)))
        return 0

    try:
        from curl_cffi import requests  # type: ignore
    except Exception as exc:  # noqa: BLE001
        print(
            json.dumps(
                _safe_error(
                    "PYTHON_WORKER_DEPENDENCY_MISSING",
                    f"curl_cffi import failed: {exc}",
                    started_at,
                )
            )
        )
        return 0

    url = str(payload.get("url", "")).strip()
    if not url:
        print(json.dumps(_safe_error("PYTHON_WORKER_BAD_REQUEST", "url is required", started_at)))
        return 0

    method = str(payload.get("method", "GET")).upper()
    query = payload.get("query") if isinstance(payload.get("query"), dict) else {}
    headers = payload.get("headers") if isinstance(payload.get("headers"), dict) else {}
    timeout_seconds = float(payload.get("timeout_seconds", 20))
    impersonate = str(payload.get("impersonate", "chrome124")).strip() or "chrome124"
    warmup = payload.get("warmup") if isinstance(payload.get("warmup"), dict) else {}
    warmup_enabled = bool(warmup.get("enabled", True))
    warmup_url = str(warmup.get("url", "https://exphuay.com/")).strip()

    try:
        session = requests.Session(impersonate=impersonate)

        if warmup_enabled and warmup_url:
            try:
                session.get(warmup_url, headers=headers, timeout=max(3.0, min(timeout_seconds, 10.0)))
            except Exception:
                pass

        response = session.request(
            method=method,
            url=url,
            params=query,
            headers=headers,
            timeout=max(1.0, timeout_seconds),
            allow_redirects=True,
        )

        body = response.text
        status_code = int(response.status_code)
        content_type = str(response.headers.get("content-type", ""))
        ok = 200 <= status_code < 300

        result = {
            "ok": ok,
            "http_status": status_code,
            "response_body": body,
            "response_content_type": content_type,
            "duration_ms": int((time.time() - started_at) * 1000),
            "error_code": None if ok else f"HTTP_STATUS_{status_code}",
            "error_message": None if ok else "HTTP status not successful",
        }
        print(json.dumps(result, ensure_ascii=False))
        return 0
    except Exception as exc:  # noqa: BLE001
        print(json.dumps(_safe_error("NETWORK_ERROR", str(exc), started_at), ensure_ascii=False))
        return 0


if __name__ == "__main__":
    raise SystemExit(main())
