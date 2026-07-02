@extends('admin::layouts.master')

@section('title', 'สถานะไลน์แจ้งเตือน')

@section('content')
<linejs-dashboard></linejs-dashboard>
@endsection

@push('styles')
<style>
/* ===== LINE JS Dashboard ===== */
#linejs-dashboard {
    color: #1e293b;
    font-size: 14px;
    line-height: 1.5;
}

/* ── Cards ── */
.ldb-card {
    background: #fff;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    padding: 18px 20px;
    margin-bottom: 16px;
}
.ldb-card-sm { padding: 16px 18px; }
.ldb-card--emerald { background: #ecfdf5; border: 2px solid #22c55e; }
.ldb-card--red     { background: #fef2f2; border: 2px solid #ef4444; }
.ldb-card--slate   { background: #f8fafc; border: 2px solid #94a3b8; }
.ldb-card--indigo  { background: #eef2ff; border: 2px solid #6366f1; }

/* ── Summary values ── */
.ldb-sum-label { font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
.ldb-sum-val   { font-size: 32px; font-weight: 800; color: #0f172a; line-height: 1; }
.ldb-sum-val--green  { color: #15803d; }
.ldb-sum-val--red    { color: #b91c1c; }
.ldb-sum-val--slate  { color: #1e293b; }

/* ── Grid ── */
.ldb-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.ldb-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
.ldb-grid-inner { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 768px) { .ldb-grid-4 { grid-template-columns: repeat(2, 1fr); } .ldb-grid-2, .ldb-grid-inner { grid-template-columns: 1fr; } }

/* ── Table ── */
.ldb-table-wrap {
    overflow-x: auto;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    background: #fff;
}
.ldb-table { width: 100%; font-size: 14px; border-collapse: collapse; }
.ldb-table thead th {
    text-align: left; font-size: 12px; font-weight: 700;
    color: #334155; padding: 12px 16px;
    background: #f1f5f9;
    border-bottom: 2px solid #cbd5e1; white-space: nowrap;
}
.ldb-table tbody td {
    padding: 12px 16px; border-bottom: 1px solid #f1f5f9;
    vertical-align: middle; color: #1e293b; font-size: 14px;
}
.ldb-table tbody tr { transition: background 0.12s; cursor: pointer; }
.ldb-table tbody tr:hover { background: #eff6ff; }
.ldb-table tbody tr:nth-child(even) { background: #fafbfc; }
.ldb-table tbody tr:nth-child(even):hover { background: #eff6ff; }

/* ── Badges ── */
.ldb-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 999px; }
.ldb-badge--green { background: #bbf7d0; color: #14532d; }
.ldb-badge--red   { background: #fecaca; color: #7f1d1d; }
.ldb-badge--slate { background: #e2e8f0; color: #334155; }

/* ── Server Tag ── */
.ldb-server-tag { font-size: 12px; font-weight: 700; background: #e0e7ff; color: #4338ca; padding: 3px 10px; border-radius: 4px; }

/* ── Buttons ── */
.ldb-btn { padding: 7px 16px; font-size: 13px; font-weight: 600; border-radius: 6px; border: 1px solid; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; transition: all 0.15s; }
.ldb-btn--emerald { background: #16a34a; color: #fff; border-color: #16a34a; }
.ldb-btn--emerald:hover { background: #15803d; }
.ldb-btn--slate { background: #64748b; color: #fff; border-color: #64748b; }
.ldb-btn--slate:hover { background: #475569; }
.ldb-btn--indigo { background: #4f46e5; color: #fff; border-color: #4f46e5; }
.ldb-btn--indigo:hover { background: #4338ca; }
.ldb-btn--amber { background: #fffbeb; color: #92400e; border-color: #fcd34d; }
.ldb-btn--amber:hover { background: #fef3c7; }
.ldb-btn--disabled { background: #f3f4f6; color: #9ca3af; border-color: #dee2e6; cursor: not-allowed; }
.ldb-btn--xs { padding: 5px 12px; font-size: 12px; border-radius: 4px; }

/* ── Header ── */
.ldb-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 14px; }
.ldb-header h1 { font-size: 22px; font-weight: 800; color: #0f172a; margin: 0; }

/* ── Detail row ── */
.ldb-detail { background: #f8fafc; padding: 20px 24px; border-top: 1px solid #e2e8f0; }
.ldb-log-scroll { max-height: 340px; overflow-y: auto; }
.ldb-log-item { background: #fff; border-radius: 6px; border: 1px solid #e2e8f0; padding: 10px 14px; margin-bottom: 8px; font-size: 13px; }
.ldb-wh-item { border-radius: 8px; border: 1px solid; padding: 14px 18px; margin-bottom: 10px; font-size: 13px; }
.ldb-wh-item--deposit { background: #f0fdf4; border-color: #86efac; }
.ldb-wh-item--balance { background: #eff6ff; border-color: #93c5fd; }
.ldb-wh-item--error   { background: #fef2f2; border-color: #fca5a5; }
.ldb-wh-item--other   { background: #f8fafc; border-color: #e2e8f0; }

/* ── Status / Misc ── */
.ldb-section-title { font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 12px; }
.ldb-loading { text-align: center; padding: 80px 0; }
.ldb-loading p { color: #64748b; margin-top: 14px; font-size: 15px; }
.ldb-error { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 6px; padding: 20px; margin-bottom: 16px; font-size: 14px; }
.ldb-error h2 { font-weight: 700; font-size: 16px; color: #b91c1c; margin: 0 0 8px; }
.ldb-warning { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 6px; padding: 12px 18px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; font-size: 14px; color: #92400e; font-weight: 600; }
.ldb-problem { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 6px; padding: 18px; margin-bottom: 16px; }
.ldb-problem h3 { font-weight: 700; color: #b91c1c; margin: 0 0 10px; font-size: 15px; }
.ldb-problem-item { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #dc2626; padding: 4px 0; }

/* ── Typography helpers ── */
.ldb-mono { font-size: 13px; }
.ldb-text-xs { font-size: 12px; color: #94a3b8; }
.ldb-text-sm { font-size: 14px; color: #64748b; }
.ldb-truncate { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ldb-mt-sm { margin-top: 8px; }
.ldb-mr-sm { margin-right: 8px; }
.ldb-flex-between { display: flex; justify-content: space-between; align-items: center; }
.ldb-flex-wrap { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.ldb-gap-sm { gap: 8px; }
.ldb-gap-md { gap: 14px; }

/* ── Input & Select ── */
.ldb-input { padding: 8px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; min-width: 180px; }
.ldb-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
.ldb-select { padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; background: #fff; }

/* ── Animations ── */
@keyframes ldb-spin  { to { transform: rotate(360deg); } }
@keyframes ldb-pulse { 0%,100%{opacity:1} 50%{opacity:.3} }
@keyframes ldb-flash { 0%{background:#dcfce7;box-shadow:0 0 16px rgba(34,197,94,0.3)} 100%{background:transparent;box-shadow:none} }
@keyframes ldb-pop   { 0%{transform:scale(0);opacity:0} 50%{transform:scale(1.2)} 100%{transform:scale(1);opacity:1} }
.ldb-spinner { width: 36px; height: 36px; border: 3px solid #e2e8f0; border-top-color: #6366f1; border-radius: 50%; animation: ldb-spin 0.7s linear infinite; margin: 0 auto 14px; }
.ldb-pulse { animation: ldb-pulse 1.5s infinite; }
.ldb-flash { animation: ldb-flash 2s ease-out; }
.ldb-pop   { animation: ldb-pop 0.4s ease-out; }
</style>
@endpush

@push('scripts')
<script type="text/x-template" id="linejs-dashboard-template">
<div id="linejs-dashboard" style="padding: 16px 20px;">
    <!-- ========== HEADER ========== -->
    <div class="ldb-header">
        <div>
            <h1>📊 LINE Account Dashboard</h1>
            <p class="ldb-text-sm ldb-mt-sm">
                🖥 @{{ serversLabel }}
            </p>
        </div>
        <div class="ldb-flex-wrap">
            <span v-if="newDepositCount > 0" class="ldb-pop" style="font-size:12px;font-weight:800;color:#fff;background:#dc2626;padding:3px 12px;border-radius:999px">💰 เงินเข้า! (@{{ newDepositCount }})</span>
            <span class="ldb-text-xs" style="white-space:nowrap">🕐 @{{ lastUpdate }}</span>
            <span v-if="polling" style="display:flex;align-items:center;gap:4px;font-size:11px;color:#059669">
                <span style="width:8px;height:8px;border-radius:50%;background:#10b981;animation:ldb-pulse 1.5s infinite"></span>
                LIVE
            </span>
            <button @click="togglePolling" :class="polling ? 'ldb-btn ldb-btn--emerald' : 'ldb-btn ldb-btn--slate'">
                @{{ polling ? '⏸ หยุด' : '▶ เริ่ม' }} auto
            </button>
            <button @click="manualRefresh" class="ldb-btn ldb-btn--indigo">🔄 รีเฟรช</button>
        </div>
    </div>

    <!-- ========== CONFIG ========== -->
    <div class="ldb-card ldb-card-sm">
        <div class="ldb-flex-wrap ldb-gap-md" style="font-size:13px">
            <span style="color:#6b7280;font-weight:500">🌐 Servers:</span>
            <input v-model="server1" @change="saveConfig" class="ldb-input" placeholder="https://linejs.168csn.com" style="flex:1">
            <span style="color:#94a3b8;font-weight:700">+</span>
            <input v-model="server2" @change="saveConfig" class="ldb-input" placeholder="https://line.168csn.com" style="flex:1">
            <span class="ldb-text-xs" style="background:#f3f4f6;padding:4px 8px;border-radius:4px;font-family:monospace">acc=@{{ accParam || '—' }}</span>
        </div>
        <div class="ldb-flex-between ldb-mt-sm">
            <span class="ldb-text-xs">poll ทุก @{{ pollInterval }} วินาที</span>
            <div class="ldb-flex-wrap ldb-gap-sm">
                <span class="ldb-text-xs">ถี่:</span>
                <select v-model.number="pollInterval" @change="saveConfig" class="ldb-select">
                    <option :value="15">15s</option>
                    <option :value="30">30s</option>
                </select>
            </div>
        </div>
    </div>

    <!-- ========== NO ACCOUNTS ========== -->
    <div v-if="!accParam && !loading" class="ldb-card" style="text-align:center;padding:48px 20px">
        <p style="font-size:40px;margin:0 0 12px">🔍</p>
        <p style="font-size:16px;font-weight:700;color:#1e293b;margin:0 0 6px">ไม่พบบัญชี SCB / GSB</p>
        <p style="font-size:13px;color:#64748b;margin:0">กรุณาเพิ่มบัญชี SCB หรือ GSB ในระบบก่อนใช้งาน</p>
    </div>

    <!-- ========== LOADING ========== -->
    <div v-if="loading && accounts.length === 0" class="ldb-loading">
        <div class="ldb-spinner"></div>
        <p>กำลังดึงข้อมูลจาก servers...</p>
    </div>

    <!-- ========== ERROR ========== -->
    <div v-if="fetchErrors.length > 0 && accounts.length === 0" class="ldb-error">
        <h2>⚠️ เกิดข้อผิดพลาด</h2>
        <p v-for="e in fetchErrors">• @{{ e }}</p>
    </div>

    <!-- ========== WARNING ========== -->
    <div v-if="fetchErrors.length > 0 && accounts.length > 0" class="ldb-warning">
        <span>⚠️</span>
        <span>เซิร์ฟเวอร์บางตัวไม่ตอบสนอง — ข้อมูลที่แสดงอาจเป็นข้อมูลเก่า</span>
        <button @click="fetchErrors = []" style="margin-left:auto;cursor:pointer;color:#d97706;border:none;background:none">✕</button>
    </div>

    <!-- ========== SUMMARY CARDS ========== -->
    <div v-if="accounts.length > 0" style="margin-bottom:16px">
        <div class="ldb-grid-4" style="margin-bottom:12px">
            <div class="ldb-card ldb-card-sm"><p class="ldb-sum-label">รวมทั้งหมด</p><p class="ldb-sum-val">@{{ summary.total }}</p></div>
            <div class="ldb-card ldb-card-sm ldb-card--emerald"><p class="ldb-sum-label">🟢 พร้อมใช้งาน</p><p class="ldb-sum-val ldb-sum-val--green">@{{ summary.ready }}</p></div>
            <div class="ldb-card ldb-card-sm ldb-card--red"><p class="ldb-sum-label">🔴 มีปัญหา</p><p class="ldb-sum-val ldb-sum-val--red">@{{ summary.error }}</p></div>
            <div class="ldb-card ldb-card-sm ldb-card--slate"><p class="ldb-sum-label">⏳ กำลังดำเนินการ</p><p class="ldb-sum-val ldb-sum-val--slate">@{{ summary.other }}</p></div>
        </div>

        <div class="ldb-grid-2">
            <div v-for="s in perServer" class="ldb-card ldb-card-sm">
                <div class="ldb-flex-between" style="margin-bottom:12px">
                    <span style="font-weight:700;font-size:15px;color:#1e293b">🖥 @{{ s.server }}</span>
                    <span class="ldb-text-xs" style="background:#f1f5f9;padding:3px 10px;border-radius:4px;font-weight:600">@{{ s.filter }}</span>
                </div>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;text-align:center;font-size:12px">
                    <div><p style="color:#64748b;margin:0;font-size:13px;font-weight:600">ทั้งหมด</p><p style="font-weight:800;color:#1e293b;margin:4px 0 0;font-size:20px">@{{ s.total }}</p></div>
                    <div><p style="color:#16a34a;margin:0;font-size:13px;font-weight:600">พร้อม</p><p style="font-weight:800;color:#16a34a;margin:4px 0 0;font-size:20px">@{{ s.ready }}</p></div>
                    <div><p style="color:#dc2626;margin:0;font-size:13px;font-weight:600">ผิดพลาด</p><p style="font-weight:800;color:#dc2626;margin:4px 0 0;font-size:20px">@{{ s.error }}</p></div>
                    <div><p style="color:#475569;margin:0;font-size:13px;font-weight:600">อื่นๆ</p><p style="font-weight:800;color:#475569;margin:4px 0 0;font-size:20px">@{{ s.other }}</p></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== PROBLEM BANNER ========== -->
    <div v-if="problemAccounts.length > 0" class="ldb-problem">
        <h3>🚨 พบบัญชีที่มีปัญหา <span style="font-weight:700">@{{ problemAccounts.length }}</span> บัญชี</h3>
        <div style="font-size:13px">
            <div v-for="a in problemAccounts" class="ldb-problem-item">
                <span class="ldb-server-tag">@{{ a._server }}</span>
                <span style="font-weight:500">@{{ a.status === 'error' ? '🔴' : '⏳' }} @{{ a.bank }}/@{{ a.acc }}</span>
                <span style="color:#ef4444">@{{ a.status }}<span v-if="a.lastStage"> [@{{ stepThai(a.lastStage) }}]</span><span v-if="a.error"> — @{{ truncate(a.error, 80) }}</span></span>
            </div>
        </div>
        <p class="ldb-text-xs" style="color:#ef4444;margin-top:8px">กด 🔄 Restart เพื่อลองเริ่มใหม่</p>
    </div>

    <!-- ========== ACCOUNT TABLE ========== -->
    <div v-if="accounts.length > 0" class="ldb-table-wrap">
        <div style="padding:10px 16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between">
            <h2 style="font-weight:600;font-size:14px;color:#374151;margin:0">
                บัญชี <span style="color:#4f46e5">@{{ accounts.length }}</span> บัญชี
            </h2>
            <span class="ldb-text-xs">คลิกแถวเพื่อดู logs / webhooks</span>
        </div>

        <table class="ldb-table">
            <thead>
                <tr>
                    <th>Server</th>
                    <th>Bank</th>
                    <th>Acc</th>
                    <th>Status</th>
                    <th>ปัญหา</th>
                    <th>Last Stage</th>
                    <th>Update</th>
                    <th>Webhook ล่าสุด</th>
                    <th style="text-align:center;width:60px">Restart</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="(a, idx) in sortedAccounts" :key="a._key">
                    <tr @click="toggleDetail(a._key)" :class="{ 'ldb-flash': a._depositFlash }">
                        <td><span class="ldb-server-tag">@{{ a._server }}</span></td>
                        <td style="font-weight:500;color:#1f2937">@{{ a.bank }}</td>
                        <td><span class="ldb-mono" style="color:#4b5563;font-size:12px">@{{ a.acc }}</span></td>
                        <td><span :class="statusClass(a.status)">@{{ statusIcon(a.status) }} @{{ a.status }}</span></td>
                        <td><div class="ldb-truncate" v-html="problemHtml(a)"></div></td>
                        <td class="ldb-truncate" style="color:#6b7280;font-size:11px;font-family:monospace">@{{ stepThai(a.recentLogs && a.recentLogs[0] ? a.recentLogs[0].step : a.lastStage) }}</td>
                        <td style="color:#475569;font-size:13px;white-space:nowrap">@{{ (a.recentLogs && a.recentLogs[0] ? a.recentLogs[0].datetime : a.update) || '—' }}</td>
                        <td style="max-width:180px">
                            <template v-if="a.recentWebhooks && a.recentWebhooks[0]">
                                <span style="font-size:14px;font-weight:700" :style="a.recentWebhooks[0].data && a.recentWebhooks[0].data.event === 'deposit' ? 'color:#16a34a' : 'color:#2563eb'">
                                    @{{ a.recentWebhooks[0].data && a.recentWebhooks[0].data.event === 'deposit' ? '💰' : '💵' }}
                                    @{{ a.recentWebhooks[0].data && a.recentWebhooks[0].data.event === 'balance' ? formatAmount(a.recentWebhooks[0].data.lastBalance || a.recentWebhooks[0].data.balance) : formatAmount(a.recentWebhooks[0].data.amount) }}
                                </span>
                                <span class="ldb-mono" style="font-size:13px;color:#475569;margin-left:4px">@{{ a.recentWebhooks[0].data && (a.recentWebhooks[0].data.fullDate || a.recentWebhooks[0].data.date || a.recentWebhooks[0].datetime) ? (a.recentWebhooks[0].data.fullDate || a.recentWebhooks[0].data.date || a.recentWebhooks[0].datetime).substring(11, 16) : '' }}</span>
                                <span v-if="a._depositFlash" class="ldb-pop" style="font-size:11px;font-weight:800;color:#fff;background:#dc2626;padding:2px 8px;border-radius:999px;margin-left:6px">ใหม่ !!!</span>
                            </template>
                            <span v-else class="ldb-text-xs">—</span>
                        </td>
                        <td style="text-align:center" @click.stop>
                            <button @click="restartAccount(a)"
                                    :disabled="!canRestart(a) || a._restarting"
                                    :class="canRestart(a) ? 'ldb-btn ldb-btn--amber ldb-btn--xs' : 'ldb-btn ldb-btn--disabled ldb-btn--xs'"
                                    :title="canRestart(a) ? 'Restart บัญชีนี้' : 'ต้องทำ QR/PIN ก่อน restart'">
                                @{{ a._restarting ? '⏳' : '🔄' }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="expandedKey === a._key">
                        <td colspan="9" class="ldb-detail">
                            <div class="ldb-grid-inner">
                                <div>
                                    <div class="ldb-section-title">📋 Recent Logs (@{{ (a.recentLogs || []).length }})</div>
                                    <div v-if="!a.recentLogs || a.recentLogs.length === 0" class="ldb-text-xs" style="font-style:italic">ไม่มีข้อมูล</div>
                                    <div v-else class="ldb-log-scroll">
                                        <div v-for="(l, li) in a.recentLogs" :key="li" class="ldb-log-item">
                                            <span class="ldb-mono" style="color:#9ca3af">@{{ l.datetime || '—' }}</span>
                                            <span style="font-weight:500;color:#374151;margin-left:8px">@{{ stepThai(l.step) }}</span>
                                            <div v-if="l.data" style="margin-top:2px;font-size:10px;display:flex;flex-wrap:wrap;gap:0 8px">
                                                <span v-if="l.data.error" style="color:#dc2626;font-weight:500">⚠ @{{ truncate(l.data.error, 60) }}</span>
                                                <span v-if="l.data.status" style="color:#6b7280">status=@{{ l.data.status }}</span>
                                                <span v-if="l.data.stage" style="color:#9ca3af">stage=@{{ l.data.stage }}</span>
                                                <span v-if="l.data.attempt" style="color:#9ca3af">attempt=@{{ l.data.attempt }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="ldb-section-title">🔗 Recent Webhooks (@{{ (a.recentWebhooks || []).length }})</div>
                                    <div v-if="!a.recentWebhooks || a.recentWebhooks.length === 0" class="ldb-text-xs" style="font-style:italic">ไม่มีข้อมูล</div>
                                    <div v-else class="ldb-log-scroll">
                                        <div v-for="(w, wi) in a.recentWebhooks" :key="wi"
                                             :class="['ldb-wh-item', w.step === 'webhook:error' ? 'ldb-wh-item--error' : (w.data && w.data.event === 'deposit') ? 'ldb-wh-item--deposit' : (w.data && w.data.event === 'balance') ? 'ldb-wh-item--balance' : 'ldb-wh-item--other']">
                                            <div v-if="w.step === 'webhook:error'" style="font-size:11px">
                                                <span style="color:#991b1b;font-weight:500">⚠️ ส่งไม่สำเร็จ</span>
                                                <span class="ldb-mono" style="color:#9ca3af;margin-left:8px">@{{ w.datetime || '—' }}</span>
                                                <div class="ldb-flex-wrap ldb-gap-sm ldb-mt-sm">
                                                    <span v-if="w.data && w.data.status" style="color:#6b7280">HTTP @{{ w.data.status }}</span>
                                                    <span v-if="w.data && w.data.attempt" style="color:#9ca3af">retry #@{{ w.data.attempt }}</span>
                                                    <span v-if="w.data && w.data.error" style="color:#dc2626">⚠ @{{ truncate(w.data.error, 60) }}</span>
                                                </div>
                                            </div>
                                            <div v-else-if="w.data && w.data.event === 'deposit'">
                                                <div class="ldb-flex-between" style="margin-bottom:6px">
                                                    <span style="font-size:11px;font-weight:700;color:#059669;background:#d1fae5;padding:2px 10px;border-radius:999px">💰 เงินเข้า</span>
                                                    <span style="font-size:10px;color:#9ca3af;font-family:monospace">@{{ w.data.fullDate || w.data.date || w.datetime || '—' }}</span>
                                                </div>
                                                <div style="font-size:18px;font-weight:700;color:#059669;margin-bottom:6px">@{{ formatAmount(w.data.amount) }}</div>
                                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:2px 12px;font-size:11px">
                                                    <div v-if="w.data.from_name"><span style="color:#9ca3af">ชื่อ</span> <span style="color:#1f2937;font-weight:500">@{{ w.data.from_name }}</span></div>
                                                    <div v-if="w.data.from_bank"><span style="color:#9ca3af">ธนาคาร</span> <span style="color:#374151">@{{ w.data.from_bank }}</span></div>
                                                    <div v-if="w.data.from_acc"><span style="color:#9ca3af">บัญชี</span> <span class="ldb-mono" style="color:#4b5563">@{{ w.data.from_acc }}</span></div>
                                                    <div v-if="w.data.balance"><span style="color:#9ca3af">ยอดคงเหลือ</span> <span style="font-weight:500;color:#374151">@{{ formatAmount(w.data.balance) }}</span></div>
                                                </div>
                                            </div>
                                            <div v-else-if="w.data && w.data.event === 'balance'">
                                                <div class="ldb-flex-between" style="margin-bottom:6px">
                                                    <span style="font-size:11px;font-weight:700;color:#1d4ed8;background:#dbeafe;padding:2px 10px;border-radius:999px">💵 อัปเดทยอด</span>
                                                    <span style="font-size:10px;color:#9ca3af;font-family:monospace">@{{ w.data.fullDate || w.data.date || w.datetime || '—' }}</span>
                                                </div>
                                                <div v-if="w.data.amount" style="font-size:18px;font-weight:700;color:#1d4ed8;margin-bottom:6px">@{{ formatAmount(w.data.amount) }}</div>
                                                <div v-else style="font-size:14px;color:#3b82f6;margin-bottom:6px">— ไม่ระบุจำนวนเงิน —</div>
                                                <div v-if="w.data.lastBalance" style="font-size:18px;font-weight:700;color:#1d4ed8;margin-bottom:6px">💵 @{{ formatAmount(w.data.lastBalance) }}</div>
                                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:2px 12px;font-size:11px">
                                                    <div v-if="w.data.from_name"><span style="color:#9ca3af">ชื่อ</span> <span style="color:#1f2937;font-weight:500">@{{ w.data.from_name }}</span></div>
                                                    <div v-if="w.data.from_bank"><span style="color:#9ca3af">ธนาคาร</span> <span style="color:#374151">@{{ w.data.from_bank }}</span></div>
                                                    <div v-if="w.data.from_acc"><span style="color:#9ca3af">บัญชี</span> <span class="ldb-mono" style="color:#4b5563">@{{ w.data.from_acc }}</span></div>
                                                    <div v-if="w.data.bank"><span style="color:#9ca3af">ไปยัง</span> <span style="color:#4b5563">@{{ w.data.bank.toUpperCase() }}/@{{ w.data.acc }}</span></div>
                                                    <div v-if="w.data.balance"><span style="color:#9ca3af">ยอดคงเหลือ</span> <span style="font-weight:500;color:#374151">@{{ formatAmount(w.data.balance) }}</span></div>
                                                </div>
                                            </div>
                                            <div v-else>
                                                <div class="ldb-flex-between" style="margin-bottom:4px">
                                                    <span style="font-size:13px;color:#4b5563;font-weight:500">📨 @{{ w.step || '—' }}</span>
                                                    <span style="font-size:10px;color:#9ca3af;font-family:monospace">@{{ w.datetime || '—' }}</span>
                                                </div>
                                                <div v-if="w.data && w.data.bank" style="font-size:11px;color:#9ca3af">@{{ w.data.bank }}/@{{ w.data.acc }}</div>
                                            </div>
                                            <div style="display:flex;justify-content:space-between;margin-top:4px;padding-top:4px;border-top:1px solid rgba(0,0,0,0.05)">
                                                <span style="font-size:10px" :style="w.step === 'webhook:error' ? 'color:#fca5a5' : 'color:#9ca3af'">
                                                    @{{ w.step === 'webhook:error' ? '❌' : '✅' }}
                                                    <span v-if="w.data && w.data.status">HTTP @{{ w.data.status }}</span>
                                                    <span v-if="w.data && w.data.attempt > 1"> · retry #@{{ w.data.attempt }}</span>
                                                </span>
                                                <span v-if="w.data && w.data.transactionID" style="font-size:9px;color:#6366f1;font-family:monospace" :title="w.data.transactionID">tx: @{{ w.data.transactionID.substring(0, 18) }}…</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
</script>

<script>
Vue.component('linejs-dashboard', {
    template: '#linejs-dashboard-template',

    data: function () {
        return {
            server1: localStorage.getItem('linejs_admin_s1') || 'https://linejs.168csn.com',
            server2: localStorage.getItem('linejs_admin_s2') || 'https://line.168csn.com',
            pollInterval: parseInt(localStorage.getItem('linejs_admin_interval') || '15'),
            loading: false,
            polling: true,
            fetchErrors: [],
            accounts: [],
            expandedKey: null,
            rawResults: [],
            lastUpdate: '—',
            newDepositCount: 0,
            accParam: @json($accParam),
            scbAccounts: @json($scbAccounts),
            gsbAccounts: @json($gsbAccounts),
            pollTimer: null,
            lastTxMap: {}
        };
    },

    computed: {
        summary: function () {
            var total = 0, ready = 0, error = 0, other = 0, listeners = 0;
            this.accounts.forEach(function (a) {
                total++;
                if (a.status === 'ready') ready++;
                else if (a.status === 'error') error++;
                else other++;
            });
            this.rawResults.forEach(function (r) {
                listeners += (r.summary && r.summary.activeListeners) || 0;
            });
            return { total: total, ready: ready, error: error, other: other, listeners: listeners };
        },
        perServer: function () {
            var self = this;
            return this.rawResults.map(function (r) {
                var acc = r.filter && r.filter.acc ? r.filter.acc.join(',') : 'ทั้งหมด';
                return {
                    server: r.server || '—',
                    filter: acc,
                    total: (r.summary && r.summary.total) || 0,
                    ready: (r.summary && r.summary.ready) || 0,
                    error: (r.summary && r.summary.error) || 0,
                    other: (r.summary && r.summary.other) || 0,
                    listeners: (r.summary && r.summary.activeListeners) || 0,
                };
            });
        },
        serversLabel: function () {
            return this.rawResults.map(function (r) { return r.server || '—'; }).join(' + ');
        },
        problemAccounts: function () {
            return this.accounts.filter(function (a) { return a.status !== 'ready'; });
        },
        sortedAccounts: function () {
            var arr = this.accounts.slice();
            var order = { 'error': 0, 'other': 1, 'ready': 2 };
            arr.sort(function (a, z) {
                var sa = order[a.status] != null ? order[a.status] : 3;
                var sz = order[z.status] != null ? order[z.status] : 3;
                if (sa !== sz) return sa - sz;
                return (a.bank + a.acc).localeCompare(z.bank + z.acc);
            });
            return arr;
        }
    },

    watch: {
        pollInterval: function () {
            this.saveConfig();
            if (this.polling) this.startPolling();
        }
    },

    mounted: function () {
        this.fetchData();
        if (this.polling) this.startPolling();
    },

    beforeDestroy: function () {
        this.stopPolling();
    },

    methods: {
        formatAmount: function (val) {
            if (val == null) return '—';
            var n = Number(val);
            if (isNaN(n)) return String(val);
            return n.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ฿';
        },
        truncate: function (str, len) {
            if (!str) return '';
            var s = String(str);
            return s.length > len ? s.substring(0, len) + '…' : s;
        },
        stepThai: function (step) {
            if (!step) return '—';
            var map = {
                'listener:running': 'กำลังรับข้อความ', 'listener:start': 'เริ่มเปิดรับ',
                'listener:ready': 'พร้อมทำงาน', 'listener:connect_attempt': 'กำลังเชื่อมต่อ',
                'listener:connect_stage_ok': 'เชื่อมต่อสำเร็จ',
                'listener:error': 'รับข้อความล้มเหลว', 'listener:restart_start': 'เริ่มต้นใหม่',
                'listener:fetch_trace': 'เช็คข้อความใหม่', 'push:state': 'ส่งสถานะ',
                'push:error': 'ส่งสถานะล้มเหลว', 'sync:update': 'อัปเดตข้อมูล',
                'webhook:sent': 'แจ้งเว็บสำเร็จ', 'webhook:error': 'แจ้งเว็บล้มเหลว',
                'message:accepted': 'ได้รับข้อความ', 'message:parsed': 'อ่านข้อความแล้ว',
            };
            return map[step] || step;
        },
        statusClass: function (status) {
            if (status === 'ready') return 'ldb-badge ldb-badge--green';
            if (status === 'error') return 'ldb-badge ldb-badge--red';
            return 'ldb-badge ldb-badge--slate';
        },
        statusIcon: function (status) {
            if (status === 'ready') return '🟢';
            if (status === 'error') return '🔴';
            return '⏳';
        },
        problemHtml: function (a) {
            if (a.status === 'error' && a.error) return '<span style="color:#991b1b;font-size:11px">⚠️ ' + this.truncate(a.error, 50) + '</span>';
            if (a.lastStage && a.lastStage.indexOf('error') !== -1) return '<span style="color:#c2410c;font-size:11px">⚠️ ' + (a.lastStage || '') + '</span>';
            if (a.status === 'ready') return '<span style="color:#059669;font-size:11px">✅ ปกติ</span>';
            if (a.status === 'pincode_required' || a.status === 'qr_required') return '<span style="color:#d97706;font-size:11px">⏳ รอ QR/PIN</span>';
            return '—';
        },
        canRestart: function (a) {
            return a.status !== 'pincode_required' && a.status !== 'qr_required';
        },
        toggleDetail: function (key) {
            this.expandedKey = this.expandedKey === key ? null : key;
        },
        saveConfig: function () {
            localStorage.setItem('linejs_admin_s1', this.server1);
            localStorage.setItem('linejs_admin_s2', this.server2);
            localStorage.setItem('linejs_admin_interval', String(this.pollInterval));
        },
        fetchOne: async function (url, label, baseUrl) {
            try {
                var resp = await fetch(url, { signal: AbortSignal.timeout(15000), cache: 'no-cache' });
                if (!resp.ok) return { ok: false, error: label + ' → HTTP ' + resp.status };
                var json = await resp.json();
                if (json.accounts) {
                    json.accounts = json.accounts.map(function (a) {
                        return Object.assign({}, a, {
                            _server: json.server || label,
                            _serverUrl: baseUrl,
                            _key: (json.server || label) + '|' + a.bank + '|' + a.acc,
                            _restarting: false,
                        });
                    });
                }
                return { ok: true, json: json };
            } catch (e) {
                return { ok: false, error: label + ' → ' + e.message };
            }
        },
        fetchWithRetry: async function (url, label, baseUrl, retries) {
            retries = retries || 2;
            for (var i = 0; i <= retries; i++) {
                if (i > 0) await new Promise(function (r) { setTimeout(r, 1500); });
                var result = await this.fetchOne(url, label, baseUrl);
                if (result.ok) return result;
                if (i === retries) return result;
            }
        },
        fetchData: async function () {
            if (this.loading && this.accounts.length > 0) return;
            var self = this;
            this.loading = true;

            if (!this.accParam) {
                this.loading = false;
                return;
            }

            var s1 = this.server1.replace(/\/$/, '');
            var s2 = this.server2.replace(/\/$/, '');
            var query = '/status?acc=' + encodeURIComponent(this.accParam) + '&_t=' + Date.now();

            var fetched = await Promise.all([
                this.fetchWithRetry(s1 + query, 'SV1', s1),
                this.fetchWithRetry(s2 + query, 'SV2', s2),
            ]);

            var allErrors = [];
            var valid = [];
            fetched.forEach(function (r) {
                if (r.ok) valid.push(r.json);
                else allErrors.push(r.error);
            });

            if (valid.length === 0) {
                this.fetchErrors = allErrors;
                this.loading = false;
                return;
            }

            var oldMap = {};
            this.accounts.forEach(function (a) { oldMap[a._key] = a; });

            var serverAccounts = {};
            valid.forEach(function (r) {
                var label = r.server || '—';
                serverAccounts[label] = (r.accounts || []).map(function (a) {
                    return Object.assign({}, a, {
                        _server: label,
                        _key: label + '|' + a.bank + '|' + a.acc,
                        _restarting: false,
                    });
                });
            });

            var aliveServers = Object.keys(serverAccounts);
            Object.keys(oldMap).forEach(function (key) {
                var old = oldMap[key];
                if (aliveServers.indexOf(old._server) === -1) {
                    serverAccounts[old._server] = serverAccounts[old._server] || [];
                    serverAccounts[old._server].push(Object.assign({}, old));
                }
            });

            var newAccounts = [];
            Object.keys(serverAccounts).forEach(function (sv) {
                serverAccounts[sv].forEach(function (a) {
                    var old = oldMap[a._key];
                    if (old) a._restarting = old._restarting;
                    newAccounts.push(a);
                });
            });

            this.accounts = newAccounts;
            this.rawResults = valid;
            this.fetchErrors = allErrors;

            var count = 0;
            var self2 = this;
            newAccounts.forEach(function (a) {
                var whs = a.recentWebhooks || [];
                if (whs.length > 0 && whs[0].data && whs[0].data.event === 'deposit') {
                    var txId = whs[0].data.transactionID;
                    if (txId && self2.lastTxMap[a._key] !== txId && self2.lastTxMap.hasOwnProperty(a._key)) {
                        count++;
                        a._depositFlash = true;
                        setTimeout(function () { a._depositFlash = false; }, 3000);
                    }
                    if (txId) self2.lastTxMap[a._key] = txId;
                }
            });
            this.newDepositCount = count;

            this.lastUpdate = new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            this.loading = false;
        },
        manualRefresh: function () { this.fetchData(); },
        togglePolling: function () {
            this.polling = !this.polling;
            if (this.polling) this.startPolling();
            else this.stopPolling();
        },
        startPolling: function () {
            this.stopPolling();
            var self = this;
            this.pollTimer = setInterval(function () { self.fetchData(); }, this.pollInterval * 1000);
        },
        stopPolling: function () {
            if (this.pollTimer) { clearInterval(this.pollTimer); this.pollTimer = null; }
        },
        restartAccount: async function (a) {
            if (!a._serverUrl) { alert('ไม่พบ server URL'); return; }
            if (!confirm('ต้องการ restart ' + a.bank + '/' + a.acc + ' บน ' + a._serverUrl + ' หรือไม่?\n\nการ restart จะหยุด listener เดิมและเริ่มใหม่')) return;
            a._restarting = true;
            try {
                var resp = await fetch(a._serverUrl + '/restart', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bank: a.bank, acc: a.acc }),
                    signal: AbortSignal.timeout(10000),
                });
                var result = await resp.json();
                if (result.ok) {
                    var self = this;
                    setTimeout(function () { a._restarting = false; }, 2000);
                } else {
                    alert('Restart ล้มเหลว: ' + (result.error || 'unknown error'));
                    a._restarting = false;
                }
            } catch (e) {
                alert('Restart error: ' + e.message);
                a._restarting = false;
            }
        }
    }
});
</script>
@endpush
