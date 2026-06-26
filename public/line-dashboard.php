<?php /* =========================
     LINE Account Dashboard (Vue) — real-time auto-polling
     ใช้: /line-dashboard-vue.php?web=autokuu
     ========================= */ ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LINE Account Dashboard</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = {}</script>
    <style>
        .fade-enter-active, .fade-leave-active { transition: all 0.4s ease; }
        .fade-enter-from { opacity: 0; transform: translateY(-8px); }
        .fade-leave-to { opacity: 0; transform: translateY(8px); }
        .slide-enter-active { transition: all 0.3s ease-out; }
        .slide-enter-from { opacity: 0; max-height: 0; }
        .slide-enter-to { opacity: 1; max-height: 500px; }
        .pulse-dot { animation: pulse-dot 1.5s infinite; }
        @keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:.3} }
        .number-pop { transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1); }
        .log-stream { scroll-behavior: smooth; }
        .status-badge { transition: all 0.3s ease; }
        @keyframes stream-in { from { opacity:0; transform:translateX(-10px) } to { opacity:1; transform:translateX(0) } }
        .stream-item { animation: stream-in 0.3s ease-out; }
        @keyframes deposit-flash {
            0% { background-color: #d1fae5; box-shadow: 0 0 12px rgba(16,185,129,0.5); }
            100% { background-color: transparent; box-shadow: none; }
        }
        .deposit-flash { animation: deposit-flash 2s ease-out; }
        @keyframes deposit-badge-pop {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); opacity: 1; }
        }
        .deposit-badge { animation: deposit-badge-pop 0.4s ease-out; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased">

<div id="app">
    <div class="max-w-7xl mx-auto px-3 md:px-4 py-4 md:py-6">

        <!-- ========== HEADER ========== -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 gap-3">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">📊 LINE Account Dashboard</h1>
                <p class="text-sm text-gray-500 mt-1">
                    🖥 {{ serversLabel }}
                    <span v-if="webFilter" class="text-indigo-500">· web={{ webFilter }}</span>
                </p>
            </div>
            <div class="flex items-center gap-2 md:gap-3 flex-wrap">
                <span v-if="newDepositCount > 0" class="deposit-badge text-xs font-bold text-emerald-700 bg-emerald-100 px-2 py-1 rounded-full">💰 เงินเข้า! ({{ newDepositCount }})</span>
                <span class="text-xs text-gray-400 whitespace-nowrap">🕐 {{ lastUpdate }}</span>
                <span v-if="polling" class="flex items-center gap-1 text-xs text-emerald-600">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 pulse-dot"></span>
                    LIVE
                </span>
                <button @click="togglePolling"
                        :class="polling ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-500 hover:bg-slate-600'"
                        class="px-3 py-2 text-white text-xs md:text-sm rounded-lg transition-colors flex items-center gap-2">
                    {{ polling ? '⏸ หยุด' : '▶ เริ่ม' }} auto
                </button>
                <button @click="manualRefresh"
                        class="px-3 py-2 bg-indigo-600 text-white text-xs md:text-sm rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2">
                    🔄 รีเฟรช
                </button>
            </div>
        </div>

        <!-- ========== CONFIG ========== -->
        <div class="bg-white rounded-xl border p-3 md:p-4 mb-4">
            <div class="flex flex-wrap items-center gap-2 md:gap-3 text-sm">
                <span class="text-gray-500 font-medium text-xs md:text-sm">🌐 Servers:</span>
                <input v-model="server1" @change="saveConfig"
                       class="flex-1 min-w-[140px] px-2 py-1.5 md:px-3 md:py-2 border rounded-lg text-xs md:text-sm font-mono focus:ring-2 focus:ring-indigo-300 outline-none">
                <span class="text-gray-300 text-xs">+</span>
                <input v-model="server2" @change="saveConfig"
                       class="flex-1 min-w-[140px] px-2 py-1.5 md:px-3 md:py-2 border rounded-lg text-xs md:text-sm font-mono focus:ring-2 focus:ring-indigo-300 outline-none">
                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded font-mono">web={{ webFilter || '—' }}</span>
            </div>
            <div class="flex items-center justify-between mt-2">
                <p class="text-xs text-gray-400">poll ทุก {{ pollInterval }} วินาที</p>
                <div class="flex items-center gap-2 text-xs">
                    <span class="text-gray-400">ถี่:</span>
                    <select v-model.number="pollInterval" @change="saveConfig" class="border rounded px-1 py-0.5 text-xs">
                        <option :value="10">10s</option>
                        <option :value="30">30s</option>
                        <option :value="60">60s</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- ========== LOADING ========== -->
        <div v-if="loading && accounts.length === 0" class="text-center py-16">
            <div class="inline-block w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
            <p class="text-gray-500 mt-3">กำลังดึงข้อมูลจาก servers...</p>
        </div>

        <!-- ========== ERROR (all servers down) ========== -->
        <div v-if="fetchErrors.length > 0 && accounts.length === 0" class="bg-red-50 border border-red-200 rounded-xl p-4 md:p-6 mb-4">
            <h2 class="text-red-800 font-semibold text-lg">⚠️ เกิดข้อผิดพลาด</h2>
            <p v-for="e in fetchErrors" class="text-sm text-red-600">• {{ e }}</p>
        </div>

        <!-- ========== WARNING (some servers down, data preserved) ========== -->
        <div v-if="fetchErrors.length > 0 && accounts.length > 0" class="bg-amber-50 border border-amber-300 rounded-lg px-3 py-2 mb-3 flex items-center gap-2 text-xs md:text-sm">
            <span class="text-amber-600">⚠️</span>
            <span class="text-amber-800">เซิร์ฟเวอร์บางตัวไม่ตอบสนอง — ข้อมูลที่แสดงอาจเป็นข้อมูลเก่า</span>
            <button @click="fetchErrors = []" class="ml-auto text-amber-500 hover:text-amber-700">✕</button>
        </div>

        <!-- ========== SUMMARY CARDS ========== -->
        <div v-if="accounts.length > 0" class="mb-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2 md:gap-4 mb-3 md:mb-4">
                <div class="bg-white rounded-xl border p-3 md:p-4">
                    <p class="text-xs md:text-sm text-gray-500">รวมทั้งหมด</p>
                    <p class="text-2xl md:text-3xl font-bold text-gray-800 number-pop">{{ summary.total }}</p>
                </div>
                <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-3 md:p-4">
                    <p class="text-xs md:text-sm text-emerald-700">🟢 พร้อมใช้งาน</p>
                    <p class="text-2xl md:text-3xl font-bold text-emerald-800 number-pop">{{ summary.ready }}</p>
                </div>
                <div class="bg-red-50 rounded-xl border border-red-200 p-3 md:p-4">
                    <p class="text-xs md:text-sm text-red-700">🔴 มีปัญหา</p>
                    <p class="text-2xl md:text-3xl font-bold text-red-800 number-pop">{{ summary.error }}</p>
                </div>
                <div class="bg-slate-50 rounded-xl border p-3 md:p-4">
                    <p class="text-xs md:text-sm text-slate-600">⏳ กำลังดำเนินการ</p>
                    <p class="text-2xl md:text-3xl font-bold text-slate-700 number-pop">{{ summary.other }}</p>
                </div>
                <div class="bg-indigo-50 rounded-xl border border-indigo-200 p-3 md:p-4">
                    <p class="text-xs md:text-sm text-indigo-700">🎧 Listeners</p>
                    <p class="text-2xl md:text-3xl font-bold text-indigo-800 number-pop">{{ summary.listeners }}</p>
                </div>
            </div>

            <!-- Per-server cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                <div v-for="s in perServer" class="bg-white rounded-xl border p-3 md:p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-semibold text-xs md:text-sm text-gray-700">🖥 {{ s.server }}</span>
                        <span class="text-[10px] md:text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded">{{ s.filter }}</span>
                    </div>
                    <div class="grid grid-cols-4 gap-2 text-center text-xs">
                        <div><p class="text-gray-400">ทั้งหมด</p><p class="font-bold text-gray-700">{{ s.total }}</p></div>
                        <div><p class="text-emerald-600">พร้อม</p><p class="font-bold text-emerald-700">{{ s.ready }}</p></div>
                        <div><p class="text-red-500">ผิดพลาด</p><p class="font-bold text-red-600">{{ s.error }}</p></div>
                        <div><p class="text-slate-500">อื่นๆ</p><p class="font-bold text-slate-600">{{ s.other }}</p></div>
                    </div>
                    <p class="text-[10px] md:text-xs text-gray-400 mt-2">🎧 Listeners: {{ s.listeners }}</p>
                </div>
            </div>
        </div>

        <!-- ========== PROBLEM BANNER ========== -->
        <div v-if="problemAccounts.length > 0" class="mb-4 bg-red-50 border border-red-300 rounded-xl p-3 md:p-4">
            <h3 class="font-semibold text-red-800 mb-2 text-sm md:text-base">
                🚨 พบบัญชีที่มีปัญหา <span class="font-bold">{{ problemAccounts.length }}</span> บัญชี
            </h3>
            <div class="text-sm space-y-1">
                <div v-for="a in problemAccounts" class="flex items-center gap-2 text-red-700 text-xs md:text-sm">
                    <span class="text-[10px] md:text-xs font-mono bg-red-100 px-1.5 py-0.5 rounded">{{ a._server }}</span>
                    <span class="font-medium">{{ a.status === 'error' ? '🔴' : '⏳' }} {{ a.bank }}/{{ a.acc }}</span>
                    <span class="text-red-500">{{ a.status }}<span v-if="a.lastStage"> [{{ stepThai(a.lastStage) }}]</span><span v-if="a.error"> — {{ truncate(a.error, 80) }}</span></span>
                </div>
            </div>
            <p class="text-[10px] md:text-xs text-red-500 mt-2">กด 🔄 Restart เพื่อลองเริ่มใหม่</p>
        </div>

        <!-- ========== ACCOUNT TABLE ========== -->
        <div v-if="accounts.length > 0" class="bg-white rounded-xl border overflow-hidden">
            <div class="px-3 md:px-4 py-2 md:py-3 bg-gray-50 border-b flex items-center justify-between">
                <h2 class="font-semibold text-sm md:text-base text-gray-700">
                    บัญชี <span class="text-indigo-600">{{ accounts.length }}</span> บัญชี
                </h2>
                <span class="text-[10px] md:text-xs text-gray-400 hidden md:inline">คลิกแถวเพื่อดู logs / webhooks</span>
                <span class="text-[10px] md:text-xs text-gray-400 md:hidden">← เลื่อน · แตะดู →</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs md:text-sm">
                    <thead>
                        <tr class="text-left text-[10px] md:text-xs uppercase text-gray-500 bg-gray-50 border-b">
                            <th class="px-2 md:px-4 py-1.5 md:py-2 w-12 md:w-14">Server</th>
                            <th class="px-2 md:px-4 py-1.5 md:py-2">Bank</th>
                            <th class="px-2 md:px-4 py-1.5 md:py-2">Acc</th>
                            <th class="px-2 md:px-4 py-1.5 md:py-2">Status</th>
                            <th class="px-2 md:px-4 py-1.5 md:py-2">ปัญหา</th>
                            <th class="px-2 md:px-4 py-1.5 md:py-2 hidden md:table-cell">Last Stage</th>
                            <th class="px-2 md:px-4 py-1.5 md:py-2 hidden sm:table-cell">Update</th>
                            <th class="px-2 md:px-4 py-1.5 md:py-2">Webhook ล่าสุด</th>
                            <th class="px-2 md:px-4 py-1.5 md:py-2 text-center w-16 md:w-20">Restart</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(a, idx) in sortedAccounts" :key="a._key">
                            <tr @click="toggleDetail(a._key)"
                                :class="['border-b hover:bg-gray-50 transition-colors cursor-pointer', { 'deposit-flash': a._depositFlash }]">
                                <td class="px-2 md:px-4 py-1.5 md:py-2">
                                    <span class="text-[10px] md:text-xs font-mono bg-indigo-50 text-indigo-700 px-1 md:px-1.5 py-0.5 rounded">{{ a._server }}</span>
                                </td>
                                <td class="px-2 md:px-4 py-1.5 md:py-2 font-medium text-gray-800">{{ a.bank }}</td>
                                <td class="px-2 md:px-4 py-1.5 md:py-2 font-mono text-gray-600 text-[11px] md:text-sm">{{ a.acc }}</td>
                                <td class="px-2 md:px-4 py-1.5 md:py-2">
                                    <span :class="statusClass(a.status)" class="status-badge inline-flex items-center gap-1 text-[10px] md:text-xs font-medium px-1.5 md:px-2 py-0.5 rounded-full">
                                        {{ statusIcon(a.status) }} {{ a.status }}
                                    </span>
                                </td>
                                <td class="px-2 md:px-4 py-1.5 md:py-2 max-w-[120px] md:max-w-[180px]" v-html="problemHtml(a)"></td>
                                <td class="px-2 md:px-4 py-1.5 md:py-2 text-[10px] md:text-xs text-gray-500 font-mono max-w-[100px] truncate hidden md:table-cell" :title="(a.recentLogs && a.recentLogs[0] ? a.recentLogs[0].step : a.lastStage) || '—'">{{ stepThai(a.recentLogs && a.recentLogs[0] ? a.recentLogs[0].step : a.lastStage) }}</td>
                                <td class="px-2 md:px-4 py-1.5 md:py-2 text-[10px] md:text-xs text-gray-400 whitespace-nowrap hidden sm:table-cell">{{ (a.recentLogs && a.recentLogs[0] ? a.recentLogs[0].datetime : a.update) || '—' }}</td>
                                <td class="px-2 md:px-4 py-1.5 md:py-2 max-w-[160px] md:max-w-[220px]">
                                    <template v-if="a.recentWebhooks && a.recentWebhooks[0]">
                                        <span class="text-[10px] md:text-xs font-medium" :class="a.recentWebhooks[0].data && a.recentWebhooks[0].data.event === 'deposit' ? 'text-emerald-700' : 'text-blue-700'">
                                            {{ a.recentWebhooks[0].data && a.recentWebhooks[0].data.event === 'deposit' ? '💰' : '💵' }}
                                            {{ a.recentWebhooks[0].data && a.recentWebhooks[0].data.event === 'balance' ? formatAmount(a.recentWebhooks[0].data.lastBalance || a.recentWebhooks[0].data.balance) : formatAmount(a.recentWebhooks[0].data.amount) }}
                                        </span>
                                        <span v-if="a._depositFlash" class="deposit-badge text-[9px] font-bold text-emerald-600 bg-emerald-100 px-1 rounded-full">ใหม่!</span>
                                        <span class="text-[11px] md:text-xs text-gray-600 font-mono ml-1">{{ a.recentWebhooks[0].data && (a.recentWebhooks[0].data.fullDate || a.recentWebhooks[0].data.date || a.recentWebhooks[0].datetime) ? (a.recentWebhooks[0].data.fullDate || a.recentWebhooks[0].data.date || a.recentWebhooks[0].datetime).substring(11, 16) : '' }}</span>
                                    </template>
                                    <span v-else class="text-[10px] md:text-xs text-gray-400">—</span>
                                </td>
                                <td class="px-2 md:px-4 py-1.5 md:py-2 text-center" @click.stop>
                                    <button @click="restartAccount(a)"
                                            :disabled="!canRestart(a) || a._restarting"
                                            :class="canRestart(a) ? 'bg-amber-50 text-amber-700 hover:bg-amber-100 border-amber-300 cursor-pointer' : 'bg-gray-100 text-gray-400 cursor-not-allowed border-gray-200'"
                                            class="text-[10px] md:text-xs px-1.5 md:px-2 py-0.5 md:py-1 rounded border"
                                            :title="canRestart(a) ? 'Restart บัญชีนี้' : 'ต้องทำ QR/PIN ก่อน restart'">
                                        {{ a._restarting ? '⏳' : '🔄' }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="expandedKey === a._key" class="bg-gray-50 border-b">
                                <td :colspan="9" class="px-3 md:px-4 py-2 md:py-3">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <!-- Logs -->
                                        <div>
                                            <h4 class="text-[10px] md:text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                                                📋 Recent Logs ({{ (a.recentLogs || []).length }})
                                            </h4>
                                            <div v-if="!a.recentLogs || a.recentLogs.length === 0" class="text-[10px] md:text-xs text-gray-400 italic">ไม่มีข้อมูล</div>
                                            <div v-else class="max-h-72 overflow-y-auto log-stream space-y-1">
                                                <div v-for="(l, li) in a.recentLogs" :key="li"
                                                     class="stream-item text-[10px] md:text-xs bg-white rounded border px-2 py-1"
                                                     :style="{ animationDelay: (li * 50) + 'ms' }">
                                                    <span class="text-gray-400 font-mono">{{ l.datetime || '—' }}</span>
                                                    <span class="text-gray-700 font-medium ml-2">{{ stepThai(l.step) }}</span>
                                                    <div v-if="l.data" class="mt-0.5 text-[10px] md:text-xs flex flex-wrap gap-x-2">
                                                        <span v-if="l.data.error" class="text-red-600 font-medium">⚠ {{ truncate(l.data.error, 60) }}</span>
                                                        <span v-if="l.data.status" class="text-gray-500">status={{ l.data.status }}</span>
                                                        <span v-if="l.data.stage" class="text-gray-400">stage={{ l.data.stage }}</span>
                                                        <span v-if="l.data.attempt" class="text-gray-400">attempt={{ l.data.attempt }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Webhooks -->
                                        <div>
                                            <h4 class="text-[10px] md:text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                                                🔗 Recent Webhooks ({{ (a.recentWebhooks || []).length }})
                                            </h4>
                                            <div v-if="!a.recentWebhooks || a.recentWebhooks.length === 0" class="text-[10px] md:text-xs text-gray-400 italic">ไม่มีข้อมูล</div>
                                            <div v-else class="max-h-72 overflow-y-auto log-stream space-y-1.5">
                                                <div v-for="(w, wi) in a.recentWebhooks" :key="wi"
                                                     class="stream-item rounded-lg border px-3 py-2"
                                                     :class="w.step === 'webhook:error' ? 'bg-red-50 border-red-200' : (w.data && w.data.event === 'deposit') ? 'bg-emerald-50 border-emerald-200' : 'bg-blue-50 border-blue-200'"
                                                     :style="{ animationDelay: (wi * 60) + 'ms' }">
                                                    <!-- Error -->
                                                    <div v-if="w.step === 'webhook:error'" class="text-[10px] md:text-xs">
                                                        <span class="text-red-700 font-medium">⚠️ ส่งไม่สำเร็จ</span>
                                                        <span class="text-gray-400 font-mono ml-2">{{ w.datetime || '—' }}</span>
                                                        <div class="flex flex-wrap gap-x-2 gap-y-0.5 mt-0.5">
                                                            <span v-if="w.data && w.data.status" class="text-gray-500">HTTP {{ w.data.status }}</span>
                                                            <span v-if="w.data && w.data.attempt" class="text-gray-400">retry #{{ w.data.attempt }}</span>
                                                            <span v-if="w.data && w.data.error" class="text-red-600">⚠ {{ truncate(w.data.error, 60) }}</span>
                                                        </div>
                                                    </div>
                                                    <!-- Deposit -->
                                                    <div v-else-if="w.data && w.data.event === 'deposit'">
                                                        <div class="flex items-center justify-between mb-1.5">
                                                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">💰 เงินเข้า</span>
                                                            <span class="text-[9px] md:text-[10px] text-gray-400 font-mono">{{ w.data.fullDate || w.data.date || w.datetime || '—' }}</span>
                                                        </div>
                                                        <div class="text-base md:text-lg font-bold text-emerald-700 mb-1.5">{{ formatAmount(w.data.amount) }}</div>
                                                        <div class="grid grid-cols-2 gap-x-3 gap-y-0.5 text-[10px] md:text-xs">
                                                            <div v-if="w.data.from_name"><span class="text-gray-400">ชื่อ</span> <span class="text-gray-800 font-medium">{{ w.data.from_name }}</span></div>
                                                            <div v-if="w.data.from_bank"><span class="text-gray-400">ธนาคาร</span> <span class="text-gray-700">{{ w.data.from_bank }}</span></div>
                                                            <div v-if="w.data.from_acc"><span class="text-gray-400">บัญชี</span> <span class="font-mono text-gray-600">{{ w.data.from_acc }}</span></div>
                                                            <div v-if="w.data.balance"><span class="text-gray-400">ยอดคงเหลือ</span> <span class="font-medium text-gray-700">{{ formatAmount(w.data.balance) }}</span></div>
                                                        </div>
                                                    </div>
                                                    <!-- Balance -->
                                                    <div v-else-if="w.data && w.data.event === 'balance'">
                                                        <div class="flex items-center justify-between mb-1.5">
                                                            <span class="text-[10px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full">💵 อัปเดทยอด</span>
                                                            <span class="text-[9px] md:text-[10px] text-gray-400 font-mono">{{ w.data.fullDate || w.data.date || w.datetime || '—' }}</span>
                                                        </div>
                                                        <div v-if="w.data.amount" class="text-base md:text-lg font-bold text-blue-700 mb-1.5">{{ formatAmount(w.data.amount) }}</div>
                                                        <div v-else class="text-sm text-blue-600 mb-1.5">— ไม่ระบุจำนวนเงิน —</div>
                                                        <div v-if="w.data.lastBalance" class="text-base md:text-lg font-bold text-blue-700 mb-1.5">💵 {{ formatAmount(w.data.lastBalance) }}</div>
                                                        <div class="grid grid-cols-2 gap-x-3 gap-y-0.5 text-[10px] md:text-xs">
                                                            <div v-if="w.data.from_name"><span class="text-gray-400">ชื่อ</span> <span class="text-gray-800 font-medium">{{ w.data.from_name }}</span></div>
                                                            <div v-if="w.data.from_bank"><span class="text-gray-400">ธนาคาร</span> <span class="text-gray-700">{{ w.data.from_bank }}</span></div>
                                                            <div v-if="w.data.from_acc"><span class="text-gray-400">บัญชี</span> <span class="font-mono text-gray-600">{{ w.data.from_acc }}</span></div>
                                                            <div v-if="w.data.bank"><span class="text-gray-400">ไปยัง</span> <span class="text-gray-600">{{ w.data.bank.toUpperCase() }}/{{ w.data.acc }}</span></div>
                                                            <div v-if="w.data.balance"><span class="text-gray-400">ยอดคงเหลือ</span> <span class="font-medium text-gray-700">{{ formatAmount(w.data.balance) }}</span></div>
                                                        </div>
                                                    </div>
                                                    <!-- Fallback (old format / unknown) -->
                                                    <div v-else>
                                                        <div class="flex items-baseline justify-between mb-1">
                                                            <span class="text-sm text-gray-600 font-medium">📨 {{ w.step || '—' }}</span>
                                                            <span class="text-[9px] md:text-[10px] text-gray-400 font-mono">{{ w.datetime || '—' }}</span>
                                                        </div>
                                                        <div v-if="w.data && w.data.bank" class="text-[10px] md:text-xs text-gray-400">{{ w.data.bank }}/{{ w.data.acc }}</div>
                                                    </div>
                                                    <!-- Footer -->
                                                    <div class="flex items-center justify-between mt-1 pt-1 border-t border-gray-200/50">
                                                        <span class="text-[9px] md:text-[10px]" :class="w.step === 'webhook:error' ? 'text-red-400' : 'text-gray-400'">
                                                            {{ w.step === 'webhook:error' ? '❌' : '✅' }}
                                                            <span v-if="w.data && w.data.status">HTTP {{ w.data.status }}</span>
                                                            <span v-if="w.data && w.data.attempt > 1"> · retry #{{ w.data.attempt }}</span>
                                                        </span>
                                                        <span v-if="w.data && w.data.transactionID" class="text-[8px] md:text-[9px] text-indigo-400 font-mono" :title="w.data.transactionID">tx: {{ w.data.transactionID.substring(0, 18) }}…</span>
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

    </div>
</div>

<script>
const { createApp, ref, reactive, computed, onMounted, onUnmounted, watch } = Vue;

createApp({
    setup() {
        // ========== CONFIG ==========
        const LS_KEY1 = 'dashvue_server1';
        const LS_KEY2 = 'dashvue_server2';
        const LS_INTERVAL = 'dashvue_interval';

        const server1 = ref(localStorage.getItem(LS_KEY1) || 'https://linejs.168csn.com');
        const server2 = ref(localStorage.getItem(LS_KEY2) || 'https://line.168csn.com');
        const pollInterval = ref(parseInt(localStorage.getItem(LS_INTERVAL) || '10'));

        // ========== STATE ==========
        const loading = ref(false);
        const polling = ref(true);
        const fetchErrors = ref([]);
        const accounts = ref([]);
        const expandedKey = ref(null);
        let pollTimer = null;

        // Read web filter
        const pageParams = new URLSearchParams(window.location.search);
        let wf = pageParams.get('web')?.trim().toLowerCase() || null;
        if (!wf) {
            const host = window.location.hostname;
            const parts = host.split('.');
            if (parts.length >= 3 && parts[0] !== 'www') wf = parts[0].toLowerCase();
        }
        const webFilter = ref(wf);

        // Track new deposits for visual notification
        const lastTxMap = new Map(); // key → last transactionID
        const newDepositKeys = ref(new Set()); // keys with new deposits
        const newDepositCount = ref(0);

        // ========== COMPUTED ==========
        const lastUpdate = ref('—');

        const summary = computed(() => {
            let total = 0, ready = 0, error = 0, other = 0, listeners = 0;
            accounts.value.forEach(a => {
                total++;
                if (a.status === 'ready') ready++;
                else if (a.status === 'error') error++;
                else other++;
            });
            // listeners from raw results
            rawResults.value.forEach(r => {
                listeners += (r.summary?.activeListeners || 0);
            });
            return { total, ready, error, other, listeners };
        });

        const rawResults = ref([]);

        const perServer = computed(() => {
            return rawResults.value.map(r => ({
                server: r.server || '—',
                filter: r.filter || webFilter.value || 'ทั้งหมด',
                total: r.summary?.total || 0,
                ready: r.summary?.ready || 0,
                error: r.summary?.error || 0,
                other: r.summary?.other || 0,
                listeners: r.summary?.activeListeners || 0,
            }));
        });

        const serversLabel = computed(() => {
            return rawResults.value.map(r => r.server || '—').join(' + ');
        });

        const problemAccounts = computed(() => {
            return accounts.value.filter(a => a.status !== 'ready');
        });

        const sortedAccounts = computed(() => {
            const arr = [...accounts.value];
            const order = { 'error': 0, 'other': 1, 'ready': 2 };
            arr.sort((a, z) => {
                const sa = order[a.status] ?? 3;
                const sz = order[z.status] ?? 3;
                if (sa !== sz) return sa - sz;
                return (a.bank + a.acc).localeCompare(z.bank + z.acc);
            });
            return arr;
        });

        // ========== METHODS ==========
        function saveConfig() {
            localStorage.setItem(LS_KEY1, server1.value);
            localStorage.setItem(LS_KEY2, server2.value);
            localStorage.setItem(LS_INTERVAL, String(pollInterval.value));
        }

        function formatAmount(val) {
            if (val == null) return '—';
            const n = Number(val);
            if (isNaN(n)) return String(val);
            return n.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ฿';
        }

        function truncate(str, len) {
            if (!str) return '';
            const s = String(str);
            return s.length > len ? s.substring(0, len) + '…' : s;
        }

        function stepThai(step) {
            if (!step) return '—';
            const map = {
                'listener:running': 'กำลังฟัง',
                'listener:start': 'เริ่ม listener',
                'listener:ready': 'พร้อม',
                'listener:connect_attempt': 'เชื่อมต่อ',
                'listener:connect_stage': 'เชื่อมต่อ',
                'listener:connect_stage_ok': 'เชื่อมต่อแล้ว',
                'listener:error': 'listener พัง',
                'listener:restart_start': 'กำลัง restart',
                'listener:restart_scheduled': 'นัด restart',
                'listener:restart_exhausted': 'restart เกิน',
                'listener:manual_restart': 'restart เอง',
                'listener:network_circuit_opened': 'ตัดวงจรเน็ต',
                'listener:fetch_trace': 'ดึง trace',
                'push:state': 'push',
                'push:error': 'push พัง',
                'sync:update': 'sync',
                'sync:restored': 'กู้ sync',
                'catchup:start': 'ตามข้อความ',
                'catchup:done': 'ตามแล้ว',
                'recover:start': 'กู้คืน',
                'e2ee:ready': 'เข้ารหัส',
                'login:update_authtoken': 'อัปเดต token',
                'login:pincall': 'ขอ PIN',
                'message:accepted': 'รับข้อความ',
                'message:parsed': 'แยกข้อความ',
                'message:error': 'ข้อความพัง',
                'message:ignored': 'ข้าม',
                'webhook:sent': 'ส่งแล้ว',
                'webhook:error': 'ส่งพัง',
                'messages:dedup_restored': 'dedup',
            };
            return map[step] || step;
        }

        function statusClass(status) {
            if (status === 'ready') return 'bg-emerald-100 text-emerald-800';
            if (status === 'error') return 'bg-red-100 text-red-800';
            return 'bg-slate-100 text-slate-600';
        }

        function statusIcon(status) {
            if (status === 'ready') return '🟢';
            if (status === 'error') return '🔴';
            return '⏳';
        }

        function problemHtml(a) {
            if (a.status === 'error' && a.error) {
                const es = truncate(a.error, 50);
                return '<span class="text-red-700 font-medium text-[10px] md:text-xs" title="' + (a.error || '') + '">⚠️ ' + es + '</span>';
            }
            if (a.lastStage && a.lastStage.includes('error')) {
                return '<span class="text-orange-600 text-[10px] md:text-xs">⚠️ ' + (a.lastStage || '') + '</span>';
            }
            if (a.status === 'ready') return '<span class="text-emerald-600 text-[10px] md:text-xs">✅ ปกติ</span>';
            if (a.status === 'pincode_required' || a.status === 'qr_required') return '<span class="text-amber-600 text-[10px] md:text-xs">⏳ รอ QR/PIN</span>';
            return '—';
        }

        function canRestart(a) {
            return a.status !== 'pincode_required' && a.status !== 'qr_required';
        }

        function toggleDetail(key) {
            expandedKey.value = expandedKey.value === key ? null : key;
        }

        // ========== FETCH (with retry) ==========
        async function fetchOne(url, label, baseUrl) {
            try {
                const resp = await fetch(url, {
                    signal: AbortSignal.timeout(15000),
                    cache: 'no-cache',
                });
                if (!resp.ok) return { ok: false, error: label + ' → HTTP ' + resp.status };
                const json = await resp.json();
                if (json.accounts) {
                    json.accounts = json.accounts.map(a => ({
                        ...a,
                        _server: json.server || label,
                        _serverUrl: baseUrl,
                        _key: (json.server || label) + '|' + a.bank + '|' + a.acc,
                        _restarting: false,
                    }));
                }
                return { ok: true, json };
            } catch (e) {
                return { ok: false, error: label + ' → ' + e.message };
            }
        }

        async function fetchWithRetry(url, label, baseUrl, retries = 2) {
            for (let attempt = 0; attempt <= retries; attempt++) {
                if (attempt > 0) await sleep(1500); // wait 1.5s before retry
                const result = await fetchOne(url, label, baseUrl);
                if (result.ok) return result;
                if (attempt === retries) return result; // last attempt, return error
            }
        }

        function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

        async function fetchData() {
            if (loading.value && accounts.value.length > 0) return;
            loading.value = true;

            const s1 = server1.value.replace(/\/$/, '');
            const s2 = server2.value.replace(/\/$/, '');
            const cacheBuster = '&_t=' + Date.now();
            const baseQuery = '/status' + (webFilter.value ? '?web=' + encodeURIComponent(webFilter.value) : '?');
            const query = baseQuery + cacheBuster;

            const fetched = await Promise.all([
                fetchWithRetry(s1 + query, 'SV1', s1),
                fetchWithRetry(s2 + query, 'SV2', s2),
            ]);

            // Collect errors from failed fetches
            const allErrors = [];
            const valid = [];
            for (const r of fetched) {
                if (r.ok) {
                    valid.push(r.json);
                } else {
                    allErrors.push(r.error);
                }
            }

            if (valid.length === 0) {
                fetchErrors.value = allErrors;
                loading.value = false;
                return;
            }

            // Build map of old accounts keyed by _key (server|bank|acc)
            const oldMap = new Map();
            accounts.value.forEach(a => oldMap.set(a._key, a));

            // Merge: take accounts from successful servers, preserve old data for failed servers
            const serverAccounts = new Map();
            valid.forEach(r => {
                const label = r.server || '—';
                serverAccounts.set(label, (r.accounts || []).map(a => ({
                    ...a,
                    _server: label,
                    _key: label + '|' + a.bank + '|' + a.acc,
                    _restarting: false,
                })));
            });

            // Keep accounts from servers that failed this round
            const aliveServers = new Set(serverAccounts.keys());
            for (const [key, old] of oldMap) {
                if (!aliveServers.has(old._server)) {
                    // Server is down — keep its old accounts
                    const label = old._server;
                    let kept = serverAccounts.get(label) || [];
                    kept.push({ ...old });
                    serverAccounts.set(label, kept);
                }
            }

            // Flatten + restore _restarting state
            const newAccounts = [];
            for (const accs of serverAccounts.values()) {
                for (const a of accs) {
                    const old = oldMap.get(a._key);
                    if (old) a._restarting = old._restarting;
                    newAccounts.push(a);
                }
            }

            accounts.value = newAccounts;
            rawResults.value = valid;
            fetchErrors.value = allErrors;

            // Detect new deposits
            const fresh = new Set();
            let count = 0;
            for (const a of newAccounts) {
                const whs = a.recentWebhooks || [];
                if (whs.length > 0 && whs[0].data && whs[0].data.event === 'deposit') {
                    const txId = whs[0].data.transactionID;
                    if (txId && lastTxMap.get(a._key) !== txId && lastTxMap.has(a._key)) {
                        fresh.add(a._key);
                        count++;
                        a._depositFlash = true;
                        setTimeout(() => { a._depositFlash = false; }, 3000);
                    }
                    if (txId) lastTxMap.set(a._key, txId);
                }
            }
            newDepositKeys.value = fresh;
            newDepositCount.value = count;

            const now = new Date();
            lastUpdate.value = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            loading.value = false;
        }

        async function manualRefresh() {
            await fetchData();
        }

        function togglePolling() {
            polling.value = !polling.value;
            if (polling.value) startPolling();
            else stopPolling();
        }

        function startPolling() {
            stopPolling();
            pollTimer = setInterval(fetchData, pollInterval.value * 1000);
        }

        function stopPolling() {
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        }

        // Watch interval change
        watch(pollInterval, () => {
            saveConfig();
            if (polling.value) startPolling();
        });

        // ========== RESTART ==========
        async function restartAccount(a) {
            if (!a._serverUrl) { alert('ไม่พบ server URL'); return; }
            if (!confirm('ต้องการ restart ' + a.bank + '/' + a.acc + ' บน ' + a._serverUrl + ' หรือไม่?\n\nการ restart จะหยุด listener เดิมและเริ่มใหม่')) return;

            a._restarting = true;
            try {
                const resp = await fetch(a._serverUrl + '/restart', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bank: a.bank, acc: a.acc }),
                    signal: AbortSignal.timeout(10000),
                });
                const result = await resp.json();
                if (result.ok) {
                    // success — will show checkmark briefly
                    setTimeout(() => { a._restarting = false; }, 2000);
                } else {
                    alert('Restart ล้มเหลว: ' + (result.error || 'unknown error'));
                    a._restarting = false;
                }
            } catch (e) {
                alert('Restart error: ' + e.message);
                a._restarting = false;
            }
        }

        // ========== LIFECYCLE ==========
        onMounted(() => {
            fetchData();
            if (polling.value) startPolling();
        });

        onUnmounted(() => stopPolling());

        return {
            // Config
            server1, server2, pollInterval, webFilter,
            // State
            loading, polling, fetchErrors, accounts, expandedKey, lastUpdate, newDepositCount,
            // Computed
            summary, perServer, serversLabel, problemAccounts, sortedAccounts,
            // Methods
            saveConfig, formatAmount, truncate, stepThai, statusClass, statusIcon, problemHtml, canRestart,
            toggleDetail, fetchData, manualRefresh, togglePolling, restartAccount,
        };
    }
}).mount('#app');
</script>

</body>
</html>
