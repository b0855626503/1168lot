<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>ไม่พบหน้าที่ร้องขอ (404)</title>
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
        /* search animation */
        .search{
            transform-origin:center;
            animation: float 3s ease-in-out infinite;
            filter: drop-shadow(0 8px 20px rgba(0,0,0,.3));
        }
        @keyframes float{
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
            <!-- Illustration: แว่นขยายค้นหา/เส้นทาง -->
            <div class="visual" aria-hidden="true">
                <svg class="search" width="360" height="260" viewBox="0 0 360 260" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="g1" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="#7aa2ff"/>
                            <stop offset="1" stop-color="#00e5ff"/>
                        </linearGradient>
                        <linearGradient id="g2" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="#c7d2fe"/>
                            <stop offset="1" stop-color="#e0e7ff"/>
                        </linearGradient>
                        <filter id="soft" x="-30%" y="-30%" width="160%" height="160%">
                            <feGaussianBlur stdDeviation="6"/>
                        </filter>
                    </defs>
                    <!-- “404” เบลอด้านหลัง -->
                    <g opacity=".12" filter="url(#soft)" transform="translate(40,40)">
                        <text x="0" y="80" font-size="96" font-weight="900" fill="url(#g2)">404</text>
                    </g>
                    <!-- หน้ากระดาษ -->
                    <g transform="translate(60,30)">
                        <rect x="0" y="0" width="180" height="220" rx="14" fill="url(#g2)" opacity=".65"/>
                        <rect x="16" y="28" width="148" height="10" rx="5" fill="url(#g1)" opacity=".8"/>
                        <rect x="16" y="52" width="120" height="10" rx="5" fill="url(#g1)" opacity=".5"/>
                        <rect x="16" y="76" width="140" height="10" rx="5" fill="url(#g1)" opacity=".5"/>
                        <rect x="16" y="100" width="132" height="10" rx="5" fill="url(#g1)" opacity=".35"/>
                    </g>
                    <!-- แว่นขยาย -->
                    <g transform="translate(200,110)">
                        <circle cx="40" cy="40" r="40" fill="none" stroke="url(#g1)" stroke-width="10"/>
                        <rect x="68" y="68" width="72" height="12" rx="6" fill="url(#g1)" transform="rotate(45 68 68)"/>
                    </g>
                    <!-- breadcrumb จุดนำทาง -->
                    <g opacity=".35">
                        <circle cx="42" cy="220" r="5" fill="url(#g1)"/>
                        <circle cx="92" cy="220" r="5" fill="url(#g1)"/>
                        <circle cx="142" cy="220" r="5" fill="url(#g1)"/>
                        <rect x="42" y="217" width="100" height="6" rx="3" fill="url(#g1)"/>
                        <rect x="92" y="217" width="100" height="6" rx="3" fill="url(#g1)"/>
                    </g>
                </svg>
            </div>

            <!-- Content -->
            <div class="content">
                <div class="chip" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    ไม่พบหน้าที่ร้องขอ
                </div>

                <h1 id="page-title">ขออภัย! เราไม่พบหน้าที่คุณเข้ามา</h1>
                <p>ลิงก์อาจไม่ถูกต้อง หน้านี้อาจถูกย้ายหรือลบไปแล้ว กรุณาเลือกดำเนินการต่อด้านล่าง</p>

                <div class="actions" role="navigation" aria-label="ตัวเลือกการนำทาง">
                    <button class="btn btn-primary" onclick="handleBack()" aria-label="กลับไปหน้าก่อนหน้า">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        กลับไปหน้าที่แล้ว
                    </button>

                    <a class="btn btn-outline" href="/" aria-label="กลับไปหน้าแรก">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-10.5z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                        กลับหน้าแรก
                    </a>
                </div>

                <div class="tips">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    ถ้าเชื่อว่าควรมีหน้านี้จริง&nbsp;ลองตรวจ URL หรือกลับหน้าแรกเพื่อเริ่มใหม่
                </div>
            </div>
        </div>

        <div class="footer">
            <span>รหัสสถานะ: 404 Not Found</span>
            <span class="kbd">ทางลัด: <b>Alt</b>/<b>⌥</b> + <b>←</b> เพื่อย้อนกลับ</span>
        </div>
    </section>
</main>

<script>
    function handleBack(){
        // ถ้ามีประวัติ ให้ย้อนกลับ; ถ้าไม่มีกลับหน้าแรก
        if (window.history && window.history.length > 1) {
            history.back();
            setTimeout(() => { location.href = "/"; }, 800);
        } else {
            location.href = "/";
        }
    }
</script>
</body>
</html>
