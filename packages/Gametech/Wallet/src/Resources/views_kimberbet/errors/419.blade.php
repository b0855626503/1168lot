<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>รหัสความปลอดภัยหมดอายุ</title>
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
            --ok:#10b981;
            --shadow: 0 10px 30px rgba(0,0,0,.35);
            --radius: 20px;
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
            background: conic-gradient(from 140deg at 60% -10%, #7aa2ff55, transparent 30%, #00e5ff33, transparent 70%);
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
        .tips{
            margin-top:4px; font-size:14px; color:var(--muted);
            display:flex; align-items:center; gap:8px;
        }
        .chip{
            display:inline-flex; align-items:center; gap:6px;
            border:1px dashed var(--outline);
            border-radius:999px; padding:8px 12px; font-size:13px; color:var(--muted);
        }
        /* hourglass animation */
        .sand{
            transform-origin:center;
            animation: pulse 2.2s ease-in-out infinite;
            filter: drop-shadow(0 8px 20px rgba(0,0,0,.3));
        }
        @keyframes pulse{
            0%,100%{ transform: translateY(0) }
            50%{ transform: translateY(6px) }
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
    </style>
</head>
<body>
<main class="wrap" role="main" aria-labelledby="page-title">
    <section class="card" aria-live="polite">
        <div class="grid">
            <!-- Illustration (SVG inline; เปลี่ยนสีตามธีมอัตโนมัติ) -->
            <div class="visual" aria-hidden="true">
                <svg class="sand" width="360" height="260" viewBox="0 0 360 260" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="g1" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="#7aa2ff"/>
                            <stop offset="1" stop-color="#00e5ff"/>
                        </linearGradient>
                        <linearGradient id="g2" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="#ffd26e"/>
                            <stop offset="1" stop-color="#ff9f6e"/>
                        </linearGradient>
                        <filter id="blur" x="-30%" y="-30%" width="160%" height="160%">
                            <feGaussianBlur stdDeviation="6"/>
                        </filter>
                    </defs>
                    <!-- lock circle -->
                    <circle cx="260" cy="78" r="34" fill="url(#g1)" opacity=".25" />
                    <rect x="246" y="76" rx="2" ry="2" width="28" height="18" fill="url(#g1)" />
                    <path d="M250 76 v-8 a10 10 0 1 1 20 0 v8" fill="none" stroke="url(#g1)" stroke-width="4" />
                    <!-- hourglass -->
                    <g transform="translate(40,20)">
                        <rect x="20" y="12" width="200" height="16" rx="8" fill="url(#g1)"/>
                        <rect x="20" y="212" width="200" height="16" rx="8" fill="url(#g1)"/>
                        <path d="M30 28 C 120 84, 120 84, 120 120 C 120 156, 120 156, 30 212" stroke="url(#g1)" stroke-width="14" fill="none" stroke-linecap="round"/>
                        <path d="M210 28 C 120 84, 120 84, 120 120 C 120 156, 120 156, 210 212" stroke="url(#g1)" stroke-width="14" fill="none" stroke-linecap="round"/>
                        <!-- sand top -->
                        <path d="M60 92 q60 -28 120 0 q-60 18 -120 0z" fill="url(#g2)" opacity=".9"/>
                        <!-- stream -->
                        <rect x="116" y="106" width="8" height="36" rx="4" fill="url(#g2)"/>
                        <!-- sand bottom -->
                        <ellipse cx="120" cy="178" rx="56" ry="16" fill="url(#g2)"/>
                        <ellipse cx="120" cy="178" rx="56" ry="16" fill="#000" opacity=".12" filter="url(#blur)"/>
                    </g>
                </svg>
            </div>

            <!-- Content -->
            <div class="content">
                <div class="chip" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7v2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-1V9a7 7 0 0 0-7-7Zm0 2a5 5 0 0 1 5 5v2H7V9a5 5 0 0 1 5-5Z" fill="currentColor" opacity=".6"/></svg>
                    รหัสความปลอดภัยหมดอายุ
                </div>

                <h1 id="page-title">ขออภัย! รหัสความปลอดภัยนี้หมดอายุแล้ว</h1>
                <p>เพื่อความปลอดภัย ลิงก์นี้ใช้ได้ชั่วคราวเท่านั้น กรุณาเลือกดำเนินการต่อด้านล่าง</p>

                <div class="actions" role="navigation" aria-label="ตัวเลือกการนำทาง">
                    <button class="btn btn-primary" onclick="handleBack()" aria-label="กลับไปหน้าก่อนหน้า">
                        <!-- back icon -->
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        กลับไปหน้าที่แล้ว
                    </button>

                    <a class="btn btn-outline" href="/" aria-label="กลับไปหน้าแรก">
                        <!-- home icon -->
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-10.5z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                        กลับหน้าแรก
                    </a>
                </div>

                <div class="tips">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    ถ้าคุณถูกพามาหน้านี้บ่อย&nbsp;ทดลองรีเฟรชหรือขอรับลิงก์ใหม่จากหน้าก่อนหน้า
                </div>
            </div>
        </div>

        <div class="footer">
            <span>รหัสลิงก์มีอายุจำกัดเพื่อความปลอดภัยของบัญชีคุณ</span>
            <span class="kbd">สั้น ๆ: ลิงก์หมดเวลา → เลือกกลับหรือไปหน้าแรก</span>
        </div>
    </section>
</main>

<script>
    function handleBack(){
        // ถ้ากลับได้ก็กลับหน้าก่อนหน้า; ถ้ากลับไม่ได้ ให้ไปหน้าแรก
        if (window.history && window.history.length > 1) {
            history.back();
            // กันบางกรณีที่ back แล้วไม่ไป ให้ fallback ภายใน 800ms
            setTimeout(() => { location.href = "/"; }, 800);
        } else {
            location.href = "/";
        }
    }
</script>
</body>
</html>
