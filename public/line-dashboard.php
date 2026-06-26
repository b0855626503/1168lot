<!-- =========================
     LINE Account Dashboard — รวมสถานะจาก 2 servers
     เปิดใช้โดยตรง: วางไฟล์นี้ใน public/ แล้วเข้า /line-dashboard.php?web=autokuu
     ========================= -->
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LINE Account Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .status-ready { background-color: #d1fae5; color: #065f46; }
        .status-error { background-color: #fee2e2; color: #991b1b; }
        .status-other  { background-color: #f1f5f9; color: #475569; }
        .pulse { animation: pulse 2s infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .expand-row { cursor: pointer; }
        .expand-row:hover { background-color: #f8fafc; }
        .log-detail { max-height: 20rem; overflow-y: auto; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased">

<div class="max-w-7xl mx-auto px-4 py-6" id="app">

    <!-- ========== HEADER ========== -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                📊 LINE Account Dashboard
            </h1>
            <p class="text-sm text-gray-500 mt-1" id="pageSubtitle">กำลังโหลดข้อมูล...</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-gray-400" id="lastUpdate">—</span>
            <button onclick="confirmedRefresh()"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2"
                    id="refreshBtn">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                รีเฟรช
            </button>
        </div>
    </div>

    <!-- ========== CONFIG — ตั้งค่า base URLs ========== -->
    <div class="bg-white rounded-xl border p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <span class="text-gray-500 font-medium">🌐 Servers:</span>
            <input type="text" id="server1Url"
                   value="https://linejs.168csn.com"
                   placeholder="Server 1 base URL"
                   class="flex-1 min-w-[200px] px-3 py-2 border rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-300 focus:border-indigo-500 outline-none">
            <span class="text-gray-300">+</span>
            <input type="text" id="server2Url"
                   value="https://line.168csn.com"
                   placeholder="Server 2 base URL"
                   class="flex-1 min-w-[200px] px-3 py-2 border rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-300 focus:border-indigo-500 outline-none">
            <span class="text-gray-300">→</span>
            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded font-mono" id="webFilterBadge">web=—</span>
        </div>
        <p class="text-xs text-gray-400 mt-2">ค่า <code>web=</code> อ่านจาก query string ของหน้านี้โดยอัตโนมัติ</p>
    </div>

    <!-- ========== LOADING ========== -->
    <div id="loadingState" class="text-center py-16">
        <div class="inline-block w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
        <p class="text-gray-500 mt-3">กำลังดึงข้อมูลจาก servers...</p>
    </div>

    <!-- ========== ERROR ========== -->
    <div id="errorState" class="hidden bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
        <h2 class="text-red-800 font-semibold text-lg">⚠️ เกิดข้อผิดพลาด</h2>
        <div id="errorMessages" class="mt-2 text-sm text-red-600"></div>
    </div>

    <!-- ========== SUMMARY CARDS ========== -->
    <div id="summarySection" class="hidden mb-6">
        <!-- Combined -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
            <div class="bg-white rounded-xl border p-4">
                <p class="text-sm text-gray-500">รวมทั้งหมด</p>
                <p class="text-3xl font-bold text-gray-800" id="sumTotal">0</p>
            </div>
            <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-4">
                <p class="text-sm text-emerald-700">🟢 พร้อมใช้งาน</p>
                <p class="text-3xl font-bold text-emerald-800" id="sumReady">0</p>
            </div>
            <div class="bg-red-50 rounded-xl border border-red-200 p-4">
                <p class="text-sm text-red-700">🔴 มีปัญหา</p>
                <p class="text-3xl font-bold text-red-800" id="sumError">0</p>
            </div>
            <div class="bg-slate-50 rounded-xl border p-4">
                <p class="text-sm text-slate-600">⏳ กำลังดำเนินการ</p>
                <p class="text-3xl font-bold text-slate-700" id="sumOther">0</p>
            </div>
            <div class="bg-indigo-50 rounded-xl border border-indigo-200 p-4">
                <p class="text-sm text-indigo-700">🎧 Active Listeners</p>
                <p class="text-3xl font-bold text-indigo-800" id="sumListeners">0</p>
            </div>
        </div>

        <!-- Per-server breakdown -->
        <div id="perServerBreakdown" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
    </div>

    <!-- ========== PROBLEM SUMMARY ========== -->
    <div id="problemBanner" class="hidden mb-6">
        <div class="bg-red-50 border border-red-300 rounded-xl p-4">
            <h3 class="font-semibold text-red-800 mb-2">
                🚨 พบบัญชีที่มีปัญหา <span id="problemCount" class="font-bold">0</span> บัญชี
            </h3>
            <div id="problemList" class="text-sm space-y-1"></div>
            <p class="text-xs text-red-500 mt-2">กด 🔄 Restart เพื่อลองเริ่มใหม่ — ถ้าไม่หายต้องตรวจสอบเพิ่มเติม</p>
        </div>
    </div>

    <!-- ========== ACCOUNT TABLE ========== -->
    <div id="accountsSection" class="hidden bg-white rounded-xl border overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between">
            <h2 class="font-semibold text-gray-700">
                บัญชี <span id="accountCount" class="text-indigo-600">0</span> บัญชี
            </h2>
            <span class="text-xs text-gray-400 hidden md:inline">คลิกแถวเพื่อดู logs / webhooks</span>
            <span class="text-xs text-gray-400 md:hidden">← เลื่อนซ้ายขวา · แตะเพื่อดู logs →</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-gray-500 bg-gray-50 border-b">
                        <th class="px-4 py-2 w-14">Server</th>
                        <th class="px-4 py-2">Bank</th>
                        <th class="px-4 py-2">Acc</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">ปัญหา</th>
                        <th class="px-4 py-2">Last Stage</th>
                        <th class="px-4 py-2">Update</th>
                        <th class="px-4 py-2 text-center">Logs</th>
                        <th class="px-4 py-2 text-center">Webhooks</th>
                        <th class="px-4 py-2 text-center w-20">Restart</th>
                    </tr>
                </thead>
                <tbody id="accountTableBody"></tbody>
            </table>
        </div>
    </div>

</div>

<script>
// ===================== CONFIG =====================
const DEFAULT_SERVERS = [
    'https://linejs.168csn.com',
    'https://line.168csn.com',
];

// ===================== STATE =====================
let allResults = [];
let expandedRow = null;

// ===================== INIT =====================
document.addEventListener('DOMContentLoaded', () => {
    // Restore saved URLs
    const saved1 = localStorage.getItem('dash_server1');
    const saved2 = localStorage.getItem('dash_server2');
    if (saved1) document.getElementById('server1Url').value = saved1;
    if (saved2) document.getElementById('server2Url').value = saved2;

    // Read ?web= from this page's URL, or extract from domain
    const pageParams = new URLSearchParams(window.location.search);
    let webFilter = pageParams.get('web')?.trim().toLowerCase() || null;

    // Fallback: try to extract from hostname (e.g. "autokuu" from "autokuu.example.com")
    if (!webFilter) {
        const host = window.location.hostname;
        const parts = host.split('.');
        if (parts.length >= 3 && parts[0] !== 'www') {
            webFilter = parts[0].toLowerCase();
        }
    }

    document.getElementById('webFilterBadge').textContent = webFilter ? `web=${webFilter}` : 'web=— (ทั้งหมด)';

    // Save URLs on change
    document.getElementById('server1Url').addEventListener('change', function () {
        localStorage.setItem('dash_server1', this.value);
    });
    document.getElementById('server2Url').addEventListener('change', function () {
        localStorage.setItem('dash_server2', this.value);
    });

    refresh();
});

// ===================== CONFIRMED REFRESH =====================
function confirmedRefresh() {
    if (confirm('ต้องการโหลดข้อมูลใหม่จากทั้ง 2 servers หรือไม่?')) {
        refresh();
    }
}

// ===================== FETCH =====================
async function refresh() {
    const btn = document.getElementById('refreshBtn');
    btn.disabled = true;
    btn.classList.add('opacity-50');

    document.getElementById('loadingState').classList.remove('hidden');
    document.getElementById('errorState').classList.add('hidden');
    document.getElementById('summarySection').classList.add('hidden');
    document.getElementById('problemBanner').classList.add('hidden');
    document.getElementById('accountsSection').classList.add('hidden');

    const server1 = document.getElementById('server1Url').value.replace(/\/$/, '');
    const server2 = document.getElementById('server2Url').value.replace(/\/$/, '');

    const pageParams = new URLSearchParams(window.location.search);
    let webFilter = pageParams.get('web')?.trim().toLowerCase() || null;
    if (!webFilter) {
        const host = window.location.hostname;
        const parts = host.split('.');
        if (parts.length >= 3 && parts[0] !== 'www') {
            webFilter = parts[0].toLowerCase();
        }
    }

    let query = '/status';
    if (webFilter) query += '?web=' + encodeURIComponent(webFilter);

    const urls = [server1 + query, server2 + query];

    allResults = [];
    const errors = [];

    const fetches = urls.map(async (url, i) => {
        try {
            const resp = await fetch(url, {
                signal: AbortSignal.timeout(15000),
            });
            if (!resp.ok) {
                errors.push(`${urls[i]} → HTTP ${resp.status}`);
                return null;
            }
            const json = await resp.json();
            if (json.accounts) {
                json.accounts = json.accounts.map(a => ({
                    ...a,
                    _server: json.server || 'SV' + (i + 1),
                    _serverUrl: i === 0 ? server1 : server2,
                }));
            }
            return json;
        } catch (e) {
            errors.push(`${urls[i]} → ${e.message}`);
            return null;
        }
    });

    const results = await Promise.all(fetches);
    document.getElementById('loadingState').classList.add('hidden');

    const valid = results.filter(Boolean);
    if (valid.length === 0) {
        document.getElementById('errorState').classList.remove('hidden');
        document.getElementById('errorMessages').innerHTML = errors.map(e => `<p>• ${e}</p>`).join('');
        btn.disabled = false;
        btn.classList.remove('opacity-50');
        return;
    }

    allResults = valid;

    const now = new Date();
    document.getElementById('lastUpdate').textContent =
        'อัปเดตล่าสุด: ' + now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    document.getElementById('pageSubtitle').textContent =
        valid.map(r => r.server || '—').join(' + ') + (webFilter ? ' · web=' + webFilter : '');

    renderSummary(valid, webFilter);
    renderProblemBanner(valid);
    renderAccounts(valid);

    btn.disabled = false;
    btn.classList.remove('opacity-50');
}

// ===================== RENDER SUMMARY =====================
function renderSummary(results, webFilter) {
    document.getElementById('summarySection').classList.remove('hidden');

    let totalAll = 0, readyAll = 0, errorAll = 0, otherAll = 0, listenersAll = 0;
    const perServer = [];

    results.forEach(r => {
        const s = r.summary || {};
        totalAll += s.total || 0;
        readyAll += s.ready || 0;
        errorAll += s.error || 0;
        otherAll += s.other || 0;
        listenersAll += s.activeListeners || 0;

        perServer.push({
            server: r.server || '—',
            filter: r.filter || webFilter || 'ทั้งหมด',
            total: s.total || 0,
            ready: s.ready || 0,
            error: s.error || 0,
            other: s.other || 0,
            listeners: s.activeListeners || 0,
        });
    });

    document.getElementById('sumTotal').textContent = totalAll;
    document.getElementById('sumReady').textContent = readyAll;
    document.getElementById('sumError').textContent = errorAll;
    document.getElementById('sumOther').textContent = otherAll;
    document.getElementById('sumListeners').textContent = listenersAll;

    document.getElementById('perServerBreakdown').innerHTML = perServer.map(s => `
        <div class="bg-white rounded-xl border p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="font-semibold text-sm text-gray-700">🖥 ${s.server}</span>
                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded">${s.filter}</span>
            </div>
            <div class="grid grid-cols-4 gap-2 text-center text-xs">
                <div>
                    <p class="text-gray-400">ทั้งหมด</p>
                    <p class="font-bold text-gray-700">${s.total}</p>
                </div>
                <div>
                    <p class="text-emerald-600">พร้อม</p>
                    <p class="font-bold text-emerald-700">${s.ready}</p>
                </div>
                <div>
                    <p class="text-red-500">ผิดพลาด</p>
                    <p class="font-bold text-red-600">${s.error}</p>
                </div>
                <div>
                    <p class="text-slate-500">อื่นๆ</p>
                    <p class="font-bold text-slate-600">${s.other}</p>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">🎧 Listeners: ${s.listeners}</p>
        </div>
    `).join('');
}

// ===================== PROBLEM BANNER =====================
function renderProblemBanner(results) {
    const problemAccounts = [];
    results.forEach(r => {
        (r.accounts || []).forEach(a => {
            if (a.status !== 'ready') {
                problemAccounts.push(a);
            }
        });
    });

    if (problemAccounts.length === 0) {
        document.getElementById('problemBanner').classList.add('hidden');
        return;
    }

    document.getElementById('problemBanner').classList.remove('hidden');
    document.getElementById('problemCount').textContent = problemAccounts.length;

    document.getElementById('problemList').innerHTML = problemAccounts.map(a => {
        const icon = a.status === 'error' ? '🔴' : '⏳';
        const errText = a.error ? ` — ${String(a.error).substring(0, 80)}` : '';
        const stageText = a.lastStage ? ` [${a.lastStage}]` : '';
        return `
            <div class="flex items-center gap-2 text-red-700">
                <span class="text-xs font-mono bg-red-100 px-1.5 py-0.5 rounded">${a._server || '—'}</span>
                <span class="font-medium">${icon} ${a.bank}/${a.acc}</span>
                <span class="text-red-500">${a.status}${stageText}${errText}</span>
            </div>
        `;
    }).join('');
}

// ===================== RENDER ACCOUNTS TABLE =====================
function renderAccounts(results) {
    document.getElementById('accountsSection').classList.remove('hidden');

    const allAccounts = [];
    results.forEach(r => {
        (r.accounts || []).forEach(a => allAccounts.push(a));
    });

    // Sort: error first → other → ready
    const statusOrder = { 'error': 0, 'other': 1, 'ready': 2 };
    allAccounts.sort((a, b) => {
        const sa = statusOrder[a.status] ?? 3;
        const sb = statusOrder[b.status] ?? 3;
        if (sa !== sb) return sa - sb;
        return (a.bank + a.acc).localeCompare(b.bank + b.acc);
    });

    document.getElementById('accountCount').textContent = allAccounts.length;

    const tbody = document.getElementById('accountTableBody');
    tbody.innerHTML = allAccounts.map((a, idx) => {
        const statusClass = a.status === 'ready' ? 'status-ready'
            : a.status === 'error' ? 'status-error'
            : 'status-other';
        const statusIcon = a.status === 'ready' ? '🟢'
            : a.status === 'error' ? '🔴'
            : '⏳';

        // Build problem summary
        let problemHtml = '—';
        if (a.status === 'error' && a.error) {
            const errShort = String(a.error).substring(0, 50);
            problemHtml = '<span class="text-red-700 font-medium text-xs" title="' + escHtml(a.error || '') + '">⚠️ ' + escHtml(errShort) + ((a.error || '').length > 50 ? '…' : '') + '</span>';
        } else if (a.lastStage && a.lastStage.includes('error')) {
            problemHtml = '<span class="text-orange-600 text-xs">⚠️ ' + escHtml(a.lastStage) + '</span>';
        } else if (a.status === 'ready') {
            problemHtml = '<span class="text-emerald-600 text-xs">✅ ปกติ</span>';
        } else if (a.status === 'pincode_required' || a.status === 'qr_required') {
            problemHtml = '<span class="text-amber-600 text-xs">⏳ รอ QR/PIN</span>';
        }

        const logCount = (a.recentLogs || []).length;
        const webhookCount = (a.recentWebhooks || []).length;

        const canRestart = a.status !== 'pincode_required' && a.status !== 'qr_required';
        const restartTitle = canRestart ? 'Restart บัญชีนี้' : 'ต้องทำ QR/PIN ก่อน restart';

        return `
            <tr class="expand-row border-b hover:bg-gray-50 transition-colors"
                onclick="toggleRow(${idx})" id="row-${idx}">
                <td class="px-4 py-2">
                    <span class="text-xs font-mono bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded">${a._server || '—'}</span>
                </td>
                <td class="px-4 py-2 font-medium text-gray-800">${a.bank}</td>
                <td class="px-4 py-2 font-mono text-gray-600">${a.acc}</td>
                <td class="px-4 py-2">
                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full ${statusClass}">
                        ${statusIcon} ${a.status}
                    </span>
                </td>
                <td class="px-4 py-2 max-w-[180px]">${problemHtml}</td>
                <td class="px-4 py-2 text-xs text-gray-500 font-mono max-w-[120px] truncate" title="${a.lastStage || '—'}">
                    ${a.lastStage || '—'}
                </td>
                <td class="px-4 py-2 text-xs text-gray-400 whitespace-nowrap">${a.update || '—'}</td>
                <td class="px-4 py-2 text-center">
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">${logCount}</span>
                </td>
                <td class="px-4 py-2 text-center">
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">${webhookCount}</span>
                </td>
                <td class="px-4 py-2 text-center" onclick="event.stopPropagation()">
                    <button onclick="restartAccount('${escHtml(a._serverUrl || '')}', '${escHtml(a.bank)}', '${escHtml(a.acc)}', this)"
                            class="text-xs px-2 py-1 rounded ${canRestart ? 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-300 cursor-pointer' : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200'}"
                            title="${restartTitle}"
                            ${canRestart ? '' : 'disabled'}>
                        🔄
                    </button>
                </td>
            </tr>
            <tr id="detail-${idx}" class="hidden">
                <td colspan="10" class="px-4 py-3 bg-gray-50 border-b">
                    ${renderDetailRow(a)}
                </td>
            </tr>
        `;
    }).join('');
}

// ===================== DETAIL ROW =====================
function renderDetailRow(account) {
    const logs = account.recentLogs || [];
    const webhooks = account.recentWebhooks || [];

    return `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                    📋 Recent Logs (${logs.length})
                </h4>
                ${logs.length === 0 ? '<p class="text-xs text-gray-400 italic">ไม่มีข้อมูล</p>' : `
                    <div class="log-detail space-y-1">
                        ${logs.map(l => {
                            const d = l.data || {};
                            const tags = [];
                            if (d.error) tags.push('<span class="text-red-600 font-medium">⚠ ' + escHtml(String(d.error).substring(0, 60)) + '</span>');
                            if (d.status) tags.push('<span class="text-gray-500">status=' + d.status + '</span>');
                            if (d.stage) tags.push('<span class="text-gray-400">stage=' + d.stage + '</span>');
                            if (d.attempt) tags.push('<span class="text-gray-400">attempt=' + d.attempt + '</span>');
                            return `
                                <div class="text-xs bg-white rounded border px-2 py-1">
                                    <span class="text-gray-400 font-mono">${escHtml(l.datetime || '—')}</span>
                                    <span class="text-gray-700 font-medium ml-2">${escHtml(l.step || '—')}</span>
                                    ${tags.length ? '<div class="mt-0.5 text-xs">' + tags.join(' · ') + '</div>' : ''}
                                </div>
                            `;
                        }).join('')}
                    </div>
                `}
            </div>

            <div>
                <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                    🔗 Recent Webhooks (${webhooks.length})
                </h4>
                ${webhooks.length === 0 ? '<p class="text-xs text-gray-400 italic">ไม่มีข้อมูล</p>' : `
                    <div class="log-detail space-y-1">
                        ${webhooks.map(w => {
                            const d = w.data || {};
                            const isError = w.step === 'webhook:error';
                            return `
                                <div class="text-xs rounded border px-2 py-1 ${isError ? 'bg-red-50 border-red-200' : 'bg-white'}">
                                    <span class="text-gray-400 font-mono">${escHtml(w.datetime || '—')}</span>
                                    <span class="font-medium ml-2 ${isError ? 'text-red-700' : 'text-emerald-700'}">${escHtml(w.step || '—')}</span>
                                    <div class="mt-0.5 flex flex-wrap gap-x-2 gap-y-0.5 text-xs">
                                        ${d.status ? '<span class="text-gray-500">HTTP ' + d.status + '</span>' : ''}
                                        ${d.attempt ? '<span class="text-gray-400">attempt #' + d.attempt + '</span>' : ''}
                                        ${d.transactionID ? '<span class="text-indigo-500 font-mono text-[11px]" title="' + escHtml(d.transactionID) + '">tx: ' + escHtml(String(d.transactionID).substring(0, 16)) + '…</span>' : ''}
                                        ${d.error ? '<span class="text-red-600">⚠ ' + escHtml(String(d.error).substring(0, 50)) + '</span>' : ''}
                                        ${d.url ? '<span class="text-gray-400 text-[10px] truncate block max-w-[300px]">' + escHtml(String(d.url).substring(0, 60)) + '</span>' : ''}
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `}
            </div>
        </div>
    `;
}

// ===================== RESTART ACCOUNT =====================
async function restartAccount(serverUrl, bank, acc, btnElement) {
    if (!serverUrl) {
        alert('ไม่พบ server URL สำหรับบัญชีนี้');
        return;
    }
    if (!confirm('ต้องการ restart ' + bank + '/' + acc + ' บน ' + serverUrl + ' หรือไม่?\n\nการ restart จะหยุด listener เดิมและเริ่มใหม่')) {
        return;
    }

    // Show loading
    const originalHtml = btnElement.innerHTML;
    btnElement.innerHTML = '⏳';
    btnElement.disabled = true;
    btnElement.classList.add('animate-spin');

    try {
        const restartUrl = serverUrl + '/restart';
        const resp = await fetch(restartUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ bank: bank, acc: acc }),
            signal: AbortSignal.timeout(10000),
        });
        const result = await resp.json();

        if (result.ok) {
            btnElement.innerHTML = '✅';
            btnElement.classList.remove('animate-spin');
            btnElement.classList.add('bg-green-100', 'text-green-700', 'border-green-300');
            setTimeout(() => {
                btnElement.innerHTML = originalHtml;
                btnElement.classList.remove('bg-green-100', 'text-green-700', 'border-green-300');
                btnElement.disabled = false;
            }, 3000);
        } else {
            btnElement.innerHTML = '❌';
            btnElement.classList.remove('animate-spin');
            btnElement.classList.add('bg-red-100', 'text-red-700', 'border-red-300');
            alert('Restart ล้มเหลว: ' + (result.error || 'unknown error'));
            setTimeout(() => {
                btnElement.innerHTML = originalHtml;
                btnElement.classList.remove('bg-red-100', 'text-red-700', 'border-red-300');
                btnElement.disabled = false;
            }, 3000);
        }
    } catch (e) {
        btnElement.innerHTML = '❌';
        btnElement.classList.remove('animate-spin');
        btnElement.classList.add('bg-red-100', 'text-red-700', 'border-red-300');
        alert('Restart error: ' + e.message);
        setTimeout(() => {
            btnElement.innerHTML = originalHtml;
            btnElement.classList.remove('bg-red-100', 'text-red-700', 'border-red-300');
            btnElement.disabled = false;
        }, 3000);
    }
}

// ===================== TOGGLE ROW =====================
function toggleRow(idx) {
    const detail = document.getElementById('detail-' + idx);
    if (!detail) return;

    if (expandedRow !== null && expandedRow !== idx) {
        const prev = document.getElementById('detail-' + expandedRow);
        if (prev) prev.classList.add('hidden');
    }

    detail.classList.toggle('hidden');
    expandedRow = detail.classList.contains('hidden') ? null : idx;
}

// ===================== HELPERS =====================
function escHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>

</body>
</html>
