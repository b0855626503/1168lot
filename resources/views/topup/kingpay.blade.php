@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;
    use SimpleSoftwareIO\QrCode\Facades\QrCode;

    function detectQrType($input)
    {
        if (Str::startsWith($input, 'data:image')) {
            return 'base64_img';
        }
    
        if (Str::startsWith($input, ['http://', 'https://', 'line://'])) {
            return 'url';
        }
    
        if (Str::startsWith($input, '000201')) {
            return 'promptpay_emv';
        }
    
        if (isBase64($input)) {
            return 'raw_base64';
        }
    
        return 'text';
    }
    
    function isBase64($str)
    {
        $decoded = base64_decode($str, true);
        return $decoded && base64_encode($decoded) === $str;
    }
@endphp
        <!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
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
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
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

        /* เอาพื้นหลังขาวก้อนใหญ่ใน svg ออก แล้วไปใส่ที่กล่องแทน */
        .qr-box svg {
            width: 100%;
            max-width: 360px;   /* ขยาย/หดบนหน้าจอได้ตามต้องการ */
            height: auto;
            display: block;
            background: transparent;  /* เดิมเป็น #fff */
            padding: 0;               /* เดิมมี padding ทำให้ขาวเยอะ */
            border-radius: 4px;
        }

        /* ให้กล่อง dashed มีพื้นหลังขาวบาง ๆ พอช่วยคอนทราสต์ */
        .qr-box {
            border: 2px dashed #ccc;
            padding: 6px;         /* เดิม 10px ลดลง */
            border-radius: 8px;
            background: #fff;     /* ย้ายพื้นหลังขาวมาไว้ที่นี่แทน */
        }

    </style>
</head>
<body>
<div class="container">
    <div class="header">KINGPAY QR PAYMENT</div>
    <!-- คำเตือนหลัก -->

    <div class="warning-box danger">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>{{ __('app.qrscan.warning') }}!</strong> {{ __('app.qrscan.warning_detail') }}
        </div>
    </div>


    <div class="info">
        <p><strong>{{ __('app.qrscan.date') }}:</strong> {{ $data['date_create'] }}</p>
        <p><strong>{{ __('app.qrscan.amount') }}:</strong> ฿{{ number_format($data['payamount'], 2) }}</p>
        <p><strong>{{ __('app.qrscan.orderid') }} :</strong> {{ $data['detail'] }}</p>
        <p><strong>{{ __('app.qrscan.txtid') }} :</strong> {{ $data['txid'] }}</p>
    </div>


    <div class="qr-box">
        @php $type = detectQrType($data['qrcode']); @endphp

        @if ($type === 'base64_img')
            <img src="{{ $data['qrcode'] }}" alt="QR Image">
        @elseif ($type === 'raw_base64')
            <img id="qrcode" src="data:image/png;base64,{{ $data['qrcode'] }}" alt="QR Image">
        @elseif ($type === 'promptpay_emv')

            <div id="qr-svg" class="qr-svg-wrap">
                {!! QrCode::format('svg')
 ->errorCorrection('H')
 ->size(420)
 ->margin(4)
 ->generate($data['qrcode']) !!}

            </div>

        @elseif ($type === 'url')
            <a href="{{ $data['qrcode'] }}" target="_blank">{{ $data['qrcode'] }}</a>
        @else
            <pre>{{ $data['qrcode'] }}</pre>
        @endif
    </div>


    <div class="expiration">{{ __('app.qrscan.expire') }} : <span id="countdown">--:--</span></div>

    <div class="warning-box danger">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>{{ __('app.qrscan.note') }} !</strong> {{ __('app.qrscan.note_detail') }}
        </div>
    </div>


    <div class="info" style="background-color: #e8f8f5; padding: 10px; border-radius: 8px; margin-bottom: 10px;">
        <strong>📌 {{ __('app.qrscan.use_account') }}:</strong>
        <p>{{ __('app.qrscan.acc_name') }}: <strong>{{ $member->name }}</strong></p>
        <p>{{ __('app.qrscan.acc_bank') }}: <strong>{{ $member->bank->name_th }}</strong></p>
        <p>{{ __('app.qrscan.acc_no') }}: <strong>{{ $member->acc_no }}</strong></p>
    </div>

    <a id="downloadBtn" class="btn-download" style="cursor: pointer;">{{ __('app.qrscan.download') }}</a>
    <canvas id="qrCanvas" style="display:none;"></canvas>


    <div class="tips">
        <strong>Tips:</strong>
        <ol>
            <li>{{ __('app.qrscan.tip_1') }}</li>
            <li>{{ __('app.qrscan.tip_2') }}</li>
            <li>{{ __('app.qrscan.tip_3') }} </li>
        </ol>
    </div>
</div>
<script src="{{ asset('lang-').app()->getLocale() }}.js?v={{ date('Ymdh') }}"></script>
<script>

    const createdAt = new Date("{{ Carbon::parse($data['date_create'])->toIso8601String() }}");
    {{--const expireAt = new Date("{{ Carbon::parse($data['expired_date'])->toIso8601String() }}");--}}
    const expireAt = new Date(createdAt.getTime() + 15 * 60 * 1000); // 15 นาที

    const countdown = document.getElementById('countdown');

    let timer;

    function expireAction() {
        fetch("{{ route('api.wildpay.deposit.expire', ['txid' => $data['detail']]) }}", {
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
        // expireAction();
    } else {
        updateCountdown();
        timer = setInterval(updateCountdown, 1000);
    }
</script>

<script>
    function trans(key, replace = {}) {
        var translation = key.split('.').reduce((t, i) => t[i] || null, window.i18n);

        for (var placeholder in replace) {
            translation = translation.replace(`:${placeholder}`, replace[placeholder]);
        }
        return translation;
    }


    {{--document.getElementById('downloadBtn').addEventListener('click', async function () {--}}
    {{--    const base64 = "{{ $data['qrcode'] }}";--}}
    {{--    const amount = "฿{{ number_format($data['payamount'], 2) }}";--}}
    {{--    const created = "{{ $data['date_create'] }}";--}}
    {{--    const orderId = "{{ $data['detail'] }}";--}}
    {{--    const txid = "{{ $data['txid'] }}";--}}
    {{--    const siteName = "{{ request()->getHost() }}";--}}

    {{--    const accountName = "{{ $member->name }}";--}}
    {{--    const bankName = "{{ $member->bank->name_th }}";--}}
    {{--    const accountNo = "{{ $member->acc_no }}";--}}

    {{--    const expireTime = expireAt.toLocaleTimeString('th-TH', {--}}
    {{--        hour: '2-digit',--}}
    {{--        minute: '2-digit',--}}
    {{--        second: '2-digit'--}}
    {{--    });--}}

    {{--    const expireText = trans('app.qrscan.expire') + ' ' + expireTime;--}}
    {{--    const img = document.getElementById('qrcode');--}}
    {{--    const img = new Image();--}}
    {{--    img.src = 'data:image/png;base64,' + base64;--}}
    {{--    await img.decode();--}}

    {{--    const canvas = document.getElementById('qrCanvas');--}}
    {{--    const ctx = canvas.getContext('2d');--}}

    {{--    const originalWidth = img.width;--}}
    {{--    const extraWidth = 80;--}}
    {{--    const width = originalWidth + extraWidth;--}}
    {{--    const lineHeight = 22;--}}
    {{--    const padding = 15;--}}

    {{--    const infoLines = 9; // ปรับรวมบรรทัดข้อความทั้งหมด--}}
    {{--    const height = img.height + (infoLines * lineHeight) + padding * 3;--}}

    {{--    canvas.width = width;--}}
    {{--    canvas.height = height;--}}

    {{--    // พื้นหลังขาว--}}
    {{--    ctx.fillStyle = "#fff";--}}
    {{--    ctx.fillRect(0, 0, width, height);--}}

    {{--    // ชื่อเว็บด้านบน--}}
    {{--    ctx.fillStyle = "#111";--}}
    {{--    ctx.font = "bold 12px Tahoma, sans-serif";--}}
    {{--    ctx.textAlign = "center";--}}
    {{--    ctx.fillText(siteName.toUpperCase(), width / 2, lineHeight + 4);--}}

    {{--    // วาด QR--}}
    {{--    const qrY = lineHeight * 2;--}}
    {{--    const qrX = (width - originalWidth) / 2;--}}
    {{--    ctx.drawImage(img, qrX, qrY);--}}

    {{--    // ข้อความรายละเอียด--}}
    {{--    ctx.fillStyle = "#333";--}}
    {{--    ctx.font = "10px Tahoma, sans-serif";--}}
    {{--    const baseY = qrY + img.height + padding;--}}
    {{--    const cx = width / 2;--}}

    {{--    ctx.fillText(`${amount} - ${created}`, cx, baseY);--}}
    {{--    ctx.fillText(`${expireText}`, cx, baseY + lineHeight);--}}
    {{--    ctx.fillText(`${trans('app.qrscan.orderid')}: ${orderId}`, cx, baseY + lineHeight * 2);--}}
    {{--    ctx.fillText(`${trans('app.qrscan.txtid')}: ${txid}`, cx, baseY + lineHeight * 3);--}}
    {{--    ctx.fillText(`⚠ ${trans('app.qrscan.warning_detail')}`, cx, baseY + lineHeight * 4);--}}

    {{--    ctx.fillText(`${trans('app.qrscan.use_account')}`, cx, baseY + lineHeight * 5);--}}
    {{--    ctx.fillText(`${trans('app.qrscan.acc_name')}: ${accountName}`, cx, baseY + lineHeight * 6);--}}
    {{--    ctx.fillText(`${trans('app.qrscan.acc_bank')}: ${bankName}`, cx, baseY + lineHeight * 7);--}}
    {{--    ctx.fillText(`${trans('app.qrscan.acc_no')}: ${accountNo}`, cx, baseY + lineHeight * 8);--}}

    {{--    // ดาวน์โหลด--}}
    {{--    const url = canvas.toDataURL("image/png");--}}
    {{--    const a = document.createElement('a');--}}
    {{--    a.href = url;--}}
    {{--    a.download = `qrcode_{{ number_format($data['payamount'], 2) }}_{{ $data['date_create'] }}.png`;--}}
    {{--    a.click();--}}
    {{--});--}}

</script>
<script>
    document.getElementById('downloadBtn').addEventListener('click', async function () {
        const type = "{{ $type }}";
        const canvas = document.getElementById('qrCanvas');
        const ctx = canvas.getContext('2d', { willReadFrequently: true });

        // เตรียมข้อความประกอบไฟล์
        const amount = "฿{{ number_format($data['payamount'], 2) }}";
        const created = "{{ $data['date_create'] }}";
        const orderId = "{{ $data['detail'] }}";
        const txid = "{{ $data['txid'] }}";
        const siteName = "{{ request()->getHost() }}";
        const accountName = "{{ $member->name }}";
        const bankName = "{{ $member->bank->name_th }}";
        const accountNo = "{{ $member->acc_no }}";
        const expireTime = new Date("{{ \Carbon\Carbon::parse($data['expired_date'])->toIso8601String() }}")
            .toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const expireText = '{{ __("app.qrscan.expire") }} ' + expireTime;

        // สร้างรูป QR สำหรับวาดบน canvas
        function loadImageForCanvas() {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => resolve(img);
                img.onerror = reject;

                if (type === 'base64_img') {
                    img.src = "{{ $data['qrcode'] }}";
                } else if (type === 'raw_base64') {
                    img.src = 'data:image/png;base64,{{ $data["qrcode"] }}';
                } else if (type === 'promptpay_emv') {
                    // ดึง SVG ที่แสดงอยู่ แล้วทำเป็น blob/data URL
                    const svgEl = document.querySelector('#qr-svg svg');
                    const svgData = new XMLSerializer().serializeToString(svgEl);
                    const svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
                    const url = URL.createObjectURL(svgBlob);
                    img.onload = () => { URL.revokeObjectURL(url); resolve(img); };
                    img.src = url;
                } else {
                    reject(new Error('Unsupported QR type for download.'));
                }
            });
        }

        try {
            const qrImg = await loadImageForCanvas();

            // ขนาดเอาท์พุตสูงขึ้นเพื่อความคม (ไม่เบลอ)
            const qrTarget = 512; // px ของ QR ในไฟล์ที่โหลด
            const sidePadding = 100;
            const lineHeight = 28;
            const lines = [
                siteName.toUpperCase(),
                `${amount} - {{ $data['date_create'] }}`,
                expireText,
                '{{ __("app.qrscan.orderid") }}: ' + orderId,
                '{{ __("app.qrscan.txtid") }}: ' + txid,
                '⚠ {{ __("app.qrscan.warning_detail") }}',
                '{{ __("app.qrscan.use_account") }}',
                '{{ __("app.qrscan.acc_name") }}: ' + accountName,
                '{{ __("app.qrscan.acc_bank") }}: ' + bankName,
                '{{ __("app.qrscan.acc_no") }}: ' + accountNo
            ];

            const width  = qrTarget + sidePadding * 2;
            const height = qrTarget + (lines.length * lineHeight) + 70;

            canvas.width = width;
            canvas.height = height;

            // วาดพื้นหลังขาว
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, width, height);

            // ปิดการเบลอพิกเซล
            ctx.imageSmoothingEnabled = false;

            // หัวข้อเว็บ
            ctx.fillStyle = '#222';
            ctx.font = 'bold 18px Tahoma, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(siteName.toUpperCase(), width / 2, 32);

            // วาด QR ตรงกลาง
            const qrX = (width - qrTarget) / 2;
            const qrY = 50;
            ctx.drawImage(qrImg, qrX, qrY, qrTarget, qrTarget);

            // ข้อความประกอบ
            ctx.font = '14px Tahoma, sans-serif';
            ctx.fillStyle = '#333';
            let y = qrY + qrTarget + 24;
            for (let i = 1; i < lines.length; i++) {
                ctx.fillText(lines[i], width / 2, y);
                y += lineHeight;
            }

            // ดาวน์โหลด
            const url = canvas.toDataURL('image/png');
            const a = document.createElement('a');
            a.href = url;
            a.download = `qrcode_{{ number_format($data['payamount'], 2) }}_{{ \Illuminate\Support\Str::slug($data['date_create']) }}.png`;
            a.click();
        } catch (e) {
            alert('ไม่สามารถสร้างไฟล์ดาวน์โหลดได้: ' + e.message);
        }
    });
</script>

<script>
    const txid = "{{ $data['detail'] }}";
    const interval = setInterval(async () => {
        try {
            const res = await fetch("{{ route('api.wildpay.deposit.status', ['txid' => $data['detail']]) }}");
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

                {{--setTimeout(() => {--}}
                {{--    if (window.opener) {--}}
                {{--        window.close();--}}
                {{--    } else {--}}
                {{--        window.location.href = "{{ route('customer.home.index') }}";--}}
                {{--    }--}}
                {{--}, 3000);--}}
            } else if (data.success && data.status === 'EXPIRED') {
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
        ✅ รายการนี้หมดอายุ การใช้งานแล้ว โปรดอย่า่ สแกน QR ของรายการนี้้
    </div>
`;

// trigger fade-in
                setTimeout(() => {
                    const el = document.getElementById('payment-success');
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, 50);

                {{--setTimeout(() => {--}}
                {{--    if (window.opener) {--}}
                {{--        window.close();--}}
                {{--    } else {--}}
                {{--        window.location.href = "{{ route('customer.home.index') }}";--}}
                {{--    }--}}
                {{--}, 3000);--}}
            } else if (data.success && data.status === 'CANCEL') {
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
        ✅ รายการนี้ถูกยกเลิก แล้ว
    </div>
`;

// trigger fade-in
                setTimeout(() => {
                    const el = document.getElementById('payment-success');
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, 50);

                {{--setTimeout(() => {--}}
                {{--    if (window.opener) {--}}
                {{--        window.close();--}}
                {{--    } else {--}}
                {{--        window.location.href = "{{ route('customer.home.index') }}";--}}
                {{--    }--}}
                {{--}, 3000);--}}

            }

        } catch (e) {
            console.warn('เช็คสถานะไม่สำเร็จ:', e);
        }
    }, 5000);
</script>


</body>
</html>
