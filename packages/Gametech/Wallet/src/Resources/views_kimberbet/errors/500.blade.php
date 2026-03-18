<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>เกิดข้อผิดพลาดภายในระบบ (500)</title>
    <meta name="robots" content="noindex">
    <style>
        :root{
            --bg: #0b1020;
            --bg-soft: #0f1530;
            --card: #121936;
            --text: #e8ecff;
            --muted: #b9c1ffcc;
            --primary: #6ea8ff;
            --primary-strong:#4d90ff;
            --outline:#2a3566;
            --shadow: 0 10px 30px rgba(0,0,0,.35);
            --radius: 20px;
            --warn:#f59e0b;
            --danger:#ef4444;
        }
        @media (prefers-color-scheme: light){
            :root{
                --bg: #f4f7ff;
                --bg-soft:#eef3ff;
                --card:#ffffff;
                --text:#0e1733;
                --muted:#384567b0;
                --primary:#2563eb;
                --primary-strong:#1d4ed8;
                --outline:#d7def5;
                --shadow: 0 10px 30px rgba(16,24,40,.12);
            }
        }
        *{box-sizing:border-box}
        html,body{height:100%}
        body{
            margin:0; background:
                radial-gradient(1200px 600px at 10% -10%, #2a4fff22 0%, transparent 60%),
                radial-gradient(900px 500px at 120% 120%, #00d4ff22 0%, transparent 60%),
                var(--bg);
            color:var(--text);
            font:16px/1.6 ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans","Liberation Sans","Apple Color Emoji","Segoe UI Emoji";
            display:grid; place-items:center;
            padding:24px;
        }
        .wrap{max-width:840px; width:100%}
        .card{
            background:linear-gradient(180deg, var(--card), var(--bg-soft));
            border:1px solid var(--outline);
            border-radius:var(--radius);
            box-shadow:var(--shadow);
            overflow:hidden;
            position:relative;
            isolation:isolate;
        }
        .card::before{
            content:"";
            position:absolute; inset:-2px;
            background: conic-gradient(from 140deg at 60% -10%, #ff8a8a55, transparent 30%, #ffd16644, transparent 70%);
            filter: blur(24px);
            z-index:-1;
        }
        .grid{
            display:grid;
            grid-template-columns:1.1fr 1fr;
            gap:12px;
        }
        @media (max-width: 760px){ .grid{ grid-template-columns:1fr; } }
        .visual{
            padding:28px 28px 0 28px;
            display:flex; align-items:center; justify-content:center;
        }
        .content{
            padding:28px; padding-top:0;
            display:flex; flex-direction:column; gap:12px;
        }
        h1{
            margin:0; font-weight:800; letter-spacing:.2px;
            line-height:1.2;
            font-size: clamp(22px, 3.4vw, 32px);
        }
        p{margin:0; color:var(--muted)}
        .actions{display:flex; gap:12px; flex-wrap:wrap; margin-top:6px}
        .btn{
            appearance:none; border:none; cursor:pointer; user-select:none;
            padding:12px 16px; border-radius:12px; font-weight:700;
            line-height:1; display:inline-flex; align-items:center; gap:8px;
            transition: transform .05s ease, box-shadow .2s ease, background .2s ease, color .2s ease, border-color .2s ease;
            text-decoration:none; white-space:nowrap;
        }
        .btn:active{ transform: translateY(1px) }
        .btn-primary{ background:var(--primary); color:white; box-shadow:0 4px 16px rgba(77,144,255,.35) }
        .btn-primary:hover{ background:var(--primary-strong) }
        .btn-outline{
            background:transparent; color:var(--text);
            border:1px solid var(--outline)
        }
        .btn-outline:hover{ border-color: color-mix(in oklab, var(--outline) 60%, var(--text)); }
        .btn-warn{
            background: linear-gradient(180deg, #fbbf24, #f59e0b);
            color:#1f2937;
            box-shadow:0 4px 16px rgba(245, 158, 11, .35);
        }
        .tips{
            margin-top:4px; font-size:14px; color:var(--muted);
            display:flex; align-items:center; gap:8px; flex-wrap:wrap;
        }
        .chip{
            display:inline-flex; align-items:center; gap:6px;
            border:1px dashed var(--outline);
            border-radius:999px; padding:8px 12px; font-size:13px; color:var(--muted);
        }
        /* cog wobble */
        .cog{
            transform-origin:center;
            animation: wobble 3.2s ease-in-out infinite;
            filter: drop-shadow(0 8px 20px rgba(0,0,0,.3));
        }
        @keyframes wobble{
            0%,100%{ transform: rotate(0deg) translateY(0) }
            20%{ transform: rotate(-6deg) translateY(1px) }
            50%{ transform: rotate(4deg) translateY(3px) }
            80%{ transform: rotate(-3deg) translateY(1px) }
        }
        .footer{
            padding:16px 28px 22px;
            border-top:1px dashed var(--outline);
            display:flex; justify-content:space-between; align-items:center;
            gap:12px; flex-wrap:wrap; color:var(--muted);
            background:linear-gradient(0deg, transparent, #ffffff05);
        }
        .kbd{
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,"Liberation Mono","Courier New", monospace;
            font-size:12px; border:1px solid var(--outline);
            padding:2px 6px; border-radius:6px; color:var(--text);
            background: #00000012;
        }
        .mono{font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,"Liberation Mono","Courier New", monospace;}
    </style>
</head>
<body>
<main class="wrap" role="main" aria-labelledby="page-title">
    <section class="card" aria-live="polite">
        <div class="grid">
            <!-- Illustration: เฟือง/ประแจ สื่อระบบขัดข้อง -->
            <div class="visual" aria-hidden="true">
                <svg class="cog" width="360" height="260" viewBox="0 0 360 260" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="g1" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="#ff9f6e"/>
                            <stop offset="1" stop-color="#ffd26e"/>
                        </linearGradient>
                        <linearGradient id="g2" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="#7aa2ff"/>
                            <stop offset="1" stop-color="#00e5ff"/>
                        </linearGradient>
                        <filter id="soft" x="-30%" y="-30%" width="160%" height="160%">
                            <feGaussianBlur stdDeviation="6"/>
                        </filter>
                    </defs>
                    <!-- “500” เบลอ -->
                    <g opacity=".12" filter="url(#soft)" transform="translate(36,36)">
                        <text x="0" y="80" font-size="96" font-weight="900" fill="url(#g1)">500</text>
                    </g>
                    <!-- เฟือง -->
                    <g transform="translate(90,40)">
                        <circle cx="80" cy="80" r="60" fill="none" stroke="url(#g2)" stroke-width="12"/>
                        <circle cx="80" cy="80" r="20" fill="url(#g2)"/>
                        <!-- แฉก -->
                        <g stroke="url(#g2)" stroke-width="14" stroke-linecap="round">
                            <line x1="80" y1="6" x2="80" y2="28"/>
                            <line x1="80" y1="132" x2="80" y2="154"/>
                            <line x1="6" y1="80" x2="28" y2="80"/>
                            <line x1="132" y1="80" x2="154" y2="80"/>
                            <line x1="28" y1="28" x2="44" y2="44"/>
                            <line x1="116" y1="116" x2="132" y2="132"/>
                            <line x1="28" y1="132" x2="44" y2="116"/>
                            <line x1="116" y1="44" x2="132" y2="28"/>
                        </g>
                    </g>
                    <!-- ประแจ -->
                    <g transform="translate(210,140) rotate(-30)">
                        <rect x="0" y="18" width="120" height="14" rx="7" fill="url(#g1)"/>
                        <path d="M0,25 a18,18 0 1 1 26,16 l-14,-8 l-12,8 a18,18 0 0 1 0,-16z" fill="url(#g1)"/>
                    </g>
                </svg>
            </div>

            <!-- Content -->
            <div class="content">
                <div class="chip" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    เกิดข้อผิดพลาดภายในระบบ
                </div>

                <h1 id="page-title">ขออภัย! ระบบขัดข้องชั่วคราว</h1>
                <p>อาจเกิดจากการเชื่อมต่อไม่เสถียร หรือเซิร์ฟเวอร์กำลังปรับปรุง กรุณาลองใหม่อีกครั้ง หรือกลับไปยังหน้าก่อนหน้า/หน้าแรก</p>

                <div class="actions" role="navigation" aria-label="ตัวเลือกการนำทาง">
                    <button class="btn btn-warn" onclick="location.reload()" aria-label="ลองโหลดหน้านี้ใหม่">
                        <!-- refresh -->
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 12a9 9 0 1 1-2.64-6.36" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M21 3v6h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        ลองโหลดใหม่
                    </button>

                    <button class="btn btn-primary" onclick="handleBack()" aria-label="กลับไปหน้าก่อนหน้า">
                        <!-- back -->
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        กลับไปหน้าที่แล้ว
                    </button>

                    <a class="btn btn-outline" href="/" aria-label="กลับไปหน้าแรก">
                        <!-- home -->
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-10.5z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                        กลับหน้าแรก
                    </a>
                </div>

                <!-- แสดงรหัสติดตามเหตุขัดข้อง (ถ้ามี) -->
                <!-- สำหรับ Laravel คุณอาจส่ง $errorId (เช่นจาก Sentry/UUID) มายัง view -->
                <!-- ใช้ Blade เงื่อนไข: -->
                <!--
          @isset($errorId)
                    <div class="tips mono">รหัสติดตามเหตุขัดข้อง: {{ $errorId }}</div>
          @endisset
                -->
                <div class="tips">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    ถ้าเกิดซ้ำบ่อย ๆ โปรดแจ้งทีมงานพร้อมเวลาที่พบเหตุขัดข้อง (<span class="mono" id="now"></span>)
                </div>
            </div>
        </div>

        <div class="footer">
            <span>รหัสสถานะ: 500 Internal Server Error</span>
            <span class="kbd">ทิป: กด <b>F5</b> หรือ <b>Ctrl/⌘ + R</b> เพื่อรีเฟรช</span>
        </div>
    </section>
</main>

<script>
    function handleBack(){
        if (window.history && window.history.length > 1) {
            history.back();
            setTimeout(() => { location.href = "/"; }, 800);
        } else {
            location.href = "/";
        }
    }
    // แสดงเวลาปัจจุบันให้ผู้ใช้แนบไปกับการแจ้งปัญหา
    (function showNow(){
        try{
            const el = document.getElementById('now');
            if(!el) return;
            const dt = new Date();
            // แสดงเวลาในเขตเอเชีย/กรุงเทพแบบง่าย ๆ (offset +07:00)
            const pad = n => String(n).padStart(2,'0');
            const y = dt.getFullYear();
            const m = pad(dt.getMonth()+1);
            const d = pad(dt.getDate());
            const hh = pad(dt.getHours());
            const mm = pad(dt.getMinutes());
            const ss = pad(dt.getSeconds());
            el.textContent = `${y}-${m}-${d} ${hh}:${mm}:${ss} (+07:00)`;
        }catch(e){}
    })();
</script>
</body>
</html>
