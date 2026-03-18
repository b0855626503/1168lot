<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตรวจสอบสถานะระบบ</title>
    <style>
        body {
            font-family: Tahoma;
            padding: 30px;
            background-color: #f9f9f9;
            text-align: center;
        }
        .box {
            display: inline-block;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
            margin-top: 40px;
        }
        .status {
            font-size: 22px;
            margin-top: 10px;
        }
        .ping-time {
            font-size: 16px;
            color: #555;
        }
    </style>
</head>
<body>

<h1>ตรวจสอบสถานะระบบ</h1>

<div class="box">
    <div>⏱️ Ping ไปเซิร์ฟเวอร์</div>
    <div class="status" id="status">--</div>
    <div class="ping-time" id="pingTime">รอกำลังเชื่อมต่อ...</div>
</div>

<script>
    async function pingLoop() {
        const statusEl = document.getElementById('status');
        const timeEl = document.getElementById('pingTime');

        while (true) {
            const start = performance.now();
            try {
                let url = '{{ route('api.ping') }}?_=' + Date.now();
                // let url = 'https://api.168csn.com/api/ping?_=' + Date.now();
                const res = await fetch(url);
                const end = performance.now();
                const time = Math.round(end - start);

                if (res.ok) {
                    statusEl.textContent = '✅ ระบบตอบกลับปกติ';
                    timeEl.textContent = `เวลาในการตอบกลับ: ${time} ms`;
                    timeEl.style.color = time > 500 ? 'orange' : '#555';
                } else {
                    statusEl.textContent = '❌ ระบบไม่ตอบกลับ';
                    timeEl.textContent = '';
                    timeEl.style.color = 'red';
                }
            } catch {
                statusEl.textContent = '❌ ไม่สามารถเชื่อมต่อ';
                timeEl.textContent = '';
                timeEl.style.color = 'red';
            }

            await new Promise(r => setTimeout(r, 5000));
        }
    }

    pingLoop();
</script>
</body>
</html>
