@php use Carbon\Carbon; @endphp
        <!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>ชำระเงินด้วย QR Code</title>
    <style>
        body {
            font-family: Tahoma, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 420px;
            width: 90%;
            margin: 20px auto;
            background-color: #fff;
            padding: 25px 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }


        .header {
            font-size: 18px;
            font-weight: bold;
            color: #003366;
            margin-bottom: 10px;
        }

        .warning {
            background-color: #ffe5e5;
            color: #cc0000;
            padding: 10px;
            font-size: 14px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .info {
            text-align: left;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .qr-box {
            border: 2px dashed #ccc;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
        }


        .expiration {
            font-size: 13px;
            color: #555;
            margin-bottom: 10px;
        }

        .qr-box img {
            width: 100%;
            max-width: 250px;
            height: auto;
        }

        .btn-download {
            display: block;
            background-color: #28a745;
            color: #fff;
            padding: 12px 0;
            border-radius: 30px;
            text-decoration: none;
            font-size: 16px;
            margin: 10px auto;
            width: 100%;
            max-width: 300px;
        }

        .tips {
            margin-top: 15px;
            background-color: #fef9e7;
            padding: 10px;
            font-size: 13px;
            border-left: 5px solid #f1c40f;
            text-align: left;
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px 10px;
            }

            .info p, .warning, .expiration, .tips {
                font-size: 14px;
            }

            .btn-download {
                width: 100%;
                font-size: 16px;
                padding: 12px;
            }
        }

        .warning-box {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            margin-bottom: 16px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.5;
            border-left: 5px solid;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }

        .warning-box i {
            font-size: 18px;
            margin-top: 2px;
        }

        .warning-box.danger {
            background: #fff5f5;
            border-color: #e53935;
            color: #b71c1c;
        }

        .warning-box.danger i {
            color: #e53935;
        }

        .warning-box.info {
            background: #e8f4fd;
            border-color: #2196f3;
            color: #0d47a1;
        }

        .warning-box.info i {
            color: #2196f3;
        }


    </style>
</head>
<body>
<div class="container">
    <div class="header">THAI QR PAYMENT</div>
    <!-- คำเตือนหลัก -->
    <div class="warning-box danger">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>คำเตือน!</strong> QR Code นี้สามารถสแกนจ่ายได้เพียงครั้งเดียวเท่านั้น โปรดอย่าสแกนซ้ำ
        </div>
    </div>


    <div class="info">
        <p><strong>วันที่:</strong> {{ $data['date_create'] }}</p>
        <p><strong>ยอด:</strong> ฿{{ number_format($data['payamount'], 2) }}</p>
        <p><strong>ใบคำสั่งซื้อ :</strong> {{ $data['detail'] }}</p>
        <p><strong>เลขที่อ้างอิง :</strong> {{ $data['txid'] }}</p>
    </div>



    <div class="qr-box">
        <img src="data:image/png;base64,{{ $data['qrcode'] }}" alt="QR Code">

    </div>


    <div class="expiration">เวลาหมดอายุ: <span id="countdown">--:--</span></div>

    <a id="downloadBtn" class="btn-download">ดาวน์โหลด QR</a>

    <div class="warning-box info">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>โปรดทราบ!</strong> จะสามารถสร้างรายการใหม่ได้ หากรายการนี้หมดอายุหรือได้รับการชำระแล้ว
        </div>
    </div>

    <div class="info" style="background-color: #e8f8f5; padding: 10px; border-radius: 8px; margin-bottom: 10px;">
        <strong>📌 ใช้บัญชีที่ปรากฏด้านล่าง เท่านั้นในการสแกน:</strong>
        <p>ชื่อบัญชี: <strong>{{ $member->name }}</strong></p>
        <p>ธนาคาร: <strong>{{ $member->bank->name_th }}</strong></p>
        <p>เลขที่บัญชี: <strong>{{ $member->acc_no }}</strong></p>
    </div>

    <div class="tips">
        <strong>Tips:</strong>
        <ol>
            <li>QR มีอายุ 15 นาที ถ้าหมดเวลา โปรดอย่าใช้งาน</li>
            <li>เปิดแอปธนาคาร แล้วใช้สแกนจากรูปเพื่อชำระเงิน</li>
        </ol>
    </div>
</div>

<script>

    const createdAt = new Date("{{ Carbon::parse($data['date_create'])->toIso8601String() }}");
    const expireAt = new Date(createdAt.getTime() + 15 * 60 * 1000); // 15 นาที

    const countdown = document.getElementById('countdown');

    let timer;

    function expireAction() {
        fetch("{{ route('api.payment.deposit.expire', ['txid' => $data['detail']]) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({})
        }).then(() => {
            if (window.opener) {
                window.close();
            } else {
                window.location.href = "{{ route('customer.home.index') }}";
            }
        });
    }

    function updateCountdown() {
        const now = new Date();
        const diff = expireAt - now;

        if (diff <= 0) {
            countdown.textContent = '00:00';
            clearInterval(timer);
            expireAction();
            return;
        }

        const minutes = String(Math.floor(diff / 1000 / 60)).padStart(2, '0');
        const seconds = String(Math.floor((diff / 1000) % 60)).padStart(2, '0');
        countdown.textContent = `${minutes}:${seconds}`;
    }

    const now = new Date();
    if (now >= expireAt) {
        // หากเข้ามาหน้านี้ตอนหมดเวลาแล้ว ให้ทำทันที
        countdown.textContent = '00:00';
        expireAction();
    } else {
        updateCountdown();
        timer = setInterval(updateCountdown, 1000);
    }
</script>

<script>
    // สร้าง blob และดาวน์โหลดไฟล์จาก Base64
    const base64 = "{{ $data['qrcode'] }}";
    const byteCharacters = atob(base64);
    const byteNumbers = Array.from(byteCharacters).map(c => c.charCodeAt(0));
    const byteArray = new Uint8Array(byteNumbers);
    const blob = new Blob([byteArray], {type: 'image/png'});
    const url = URL.createObjectURL(blob);

    const downloadBtn = document.getElementById('downloadBtn');
    downloadBtn.href = url;
    downloadBtn.download = "qrcode.png";
</script>
<script>
    const txid = "{{ $data['detail'] }}";
    const interval = setInterval(async () => {
        try {
            const res = await fetch("{{ route('api.payment.deposit.status', ['txid' => $data['detail']]) }}");
            const data = await res.json();

            if (data.success && data.status === 'PAID') {
                clearInterval(interval);

                // ซ่อน QR กับปุ่ม
                document.querySelector('.qr-box').style.display = 'none';
                document.getElementById('downloadBtn').style.display = 'none';

                const expirationDiv = document.querySelector('.expiration');
                expirationDiv.innerHTML = `
    <div id="payment-success" style="
        opacity: 0;
        transform: translateY(-10px);
        background-color: #e6f9ec;
        border: 2px solid #2ecc71;
        color: #2e7d32;
        font-weight: bold;
        font-size: 16px;
        padding: 12px 20px;
        border-radius: 10px;
        display: inline-block;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.5s ease;
    ">
        ✅ รายการได้รับการ ชำระเรียบร้อยแล้ว โปรดตรวจสอบ ยอดเครติดของท่าน
    </div>
`;

// trigger fade-in
                setTimeout(() => {
                    const el = document.getElementById('payment-success');
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, 50);

                setTimeout(() => {
                    if (window.opener) {
                        window.close();
                    } else {
                        window.location.href = "{{ route('customer.home.index') }}";
                    }
                }, 3000);
            }else if (data.success && data.status === 'EXPIRED') {
                clearInterval(interval);
                document.querySelector('.qr-box').style.display = 'none';
                document.getElementById('downloadBtn').style.display = 'none';

                setTimeout(() => {
                    if (window.opener) {
                        window.close();
                    } else {
                        window.location.href = "{{ route('customer.home.index') }}";
                    }
                }, 3000);
            }

        } catch (e) {
            console.warn('เช็คสถานะไม่สำเร็จ:', e);
        }
    }, 5000);
</script>


</body>
</html>
