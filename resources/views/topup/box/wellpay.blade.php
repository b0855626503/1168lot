@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','THAI QR PAYMENT')

@push('styles')
    <style>


        .qr_payment {
            max-width: 420px;
            width: 90%;
            margin: 20px auto;
            /*background-color: #fff;*/
            padding: 25px 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }


        /* จัดกึ่งกลางทั้งแนวตั้งและแนวนอน */
        .qr_payment .qr-box{
            display: grid;
            place-items: center;
            border: 2px dashed #ccc;
            padding: 6px;
            border-radius: 8px;
            background: #fff;
        }

        /* ขนาด QR แบบยืดหยุ่น แต่ไม่เกิน 300px และอยู่กลางเสมอ */
        .qr_payment .qr-box img,
        .qr_payment .qr-box svg{
            display: block;
            width: clamp(200px, 60vw, 300px); /* 200–300px ตามจอ */
            height: auto;
            margin: 0 auto;
            background: transparent;
            padding: 0;
            border-radius: 4px;
        }

        .qr_payment .qr-box{
            display:grid; place-items:center;
            border:2px dashed #ccc; padding:16px 36px; /* เผื่อที่สำหรับป้ายข้าง */
            border-radius:8px; background:#fff;
        }

        .qr_payment .qr-wrap{ position:relative; display:inline-block; }

        .qr_payment .qr-box img, .qr-box svg{
            display:block; width:clamp(200px,60vw,300px); height:auto; margin:0 auto;
            background:transparent; border-radius:4px;
        }

        /* ป้ายด้านบน */
        .qr_payment .qr-badge{
            position:absolute; left:50%; top:-10px; transform:translate(-50%,-50%);
            background:#e53935; color:#fff;  font-size:14px; width:100%;
            padding:6px 12px; border-radius:999px; box-shadow:0 2px 6px rgba(0,0,0,.12);
        }

        /* ป้ายแนวตั้งซ้าย-ขวา */
        .qr_payment .qr-side{
            position:absolute; top:50%; transform:translateY(-50%);
            writing-mode:vertical-rl; text-orientation:mixed;
            color:#b71c1c; font-weight:700; font-size:12px;
            background:rgba(229,57,53,.08); border:1px solid rgba(229,57,53,.25);
            padding:6px 4px; border-radius:6px;
        }
        .qr_payment .qr-side.left{  left:-28px;  }
        .qr_payment .qr-side.right{ right:-28px; }

        .qr_payment  .qr-bottom{
            position:absolute; left:50%; bottom:-12px; transform:translateX(-50%);
            background:#fff5f5; color:#b71c1c; font-weight:700; font-size:12px;
            border:1px solid rgba(229,57,53,.35);
            padding:6px 12px; border-radius:999px; white-space:nowrap;
        }

        @media (max-width:480px){
            .qr_payment     .qr-box{ padding:14px 28px; }
            .qr_payment    .qr-side.left{ left:-24px; }
            .qr_payment .qr-side.right{ right:-24px; }
            .qr_payment .qr-bottom{ bottom:-12px; font-size:11px; }
        }

        .qr_payment .header {
            font-size: 18px;
            font-weight: bold;
            color: #c90138;
            margin-bottom: 10px;
        }

        .qr_payment .warning {
            /*background-color: #ffe5e5;*/
            color: #cc0000;
            padding: 10px;
            font-size: 14px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .qr_payment .info {
            text-align: left;
            font-size: 14px;
            margin-bottom: 10px;
            /*background-color: #e8f8f5;*/
        }

        .qr_payment .qr-box {
            border: 2px dashed #ccc;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
        }


        .qr_payment .expiration {
            font-size: 13px;
            color: #8cb802;
            margin-bottom: 10px;
        }

        .qr_payment .qr-box img {
            width: 100%;
            max-width: 250px;
            height: auto;
        }

        .qr_payment .btn-download {
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

        .qr_payment .tips {
            margin-top: 15px;
            /*background-color: #fef9e7;*/
            padding: 10px;
            font-size: 13px;
            border-left: 5px solid #f1c40f;
            text-align: left;
        }

        @media (max-width: 480px) {
            .qr_payment {
                padding: 15px 10px;
            }

            .qr_payment .info p, .warning, .expiration, .tips {
                font-size: 14px;
            }

            .qr_payment .btn-download {
                width: 100%;
                font-size: 16px;
                padding: 12px;
            }
        }

        .qr_payment .warning-box {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            margin-bottom: 16px;
            border-radius: 8px;
            font-size: 20px;
            line-height: 1.5;
            border-left: 5px solid;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
        }

        .qr_payment .warning-box i {
            font-size: 18px;
            margin-top: 2px;
        }

        .qr_payment .warning-box.danger {
            background: #fff5f5;
            border-color: #e53935;
            color: #b71c1c;
        }

        .qr_payment .warning-box.danger i {
            color: #e53935;
        }

        .qr_payment .warning-box.info {
            background: #e8f4fd;
            border-color: #2196f3;
            color: #0d47a1;
        }

        .qr_payment .warning-box.info i {
            color: #2196f3;
        }



    </style>
@endpush


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

@section('content')
	
	<div class="container qr_payment">
		<div class="header">WELLPAY QR PAYMENT</div>
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
        <br>

        @php $type = detectQrType($data['qrcode']); @endphp
        <div class="qr-box">
            <div class="qr-wrap">
                <div class="qr-badge">{{ $data['date_create'] }}</div>
                <div class="qr-side left">{{ $data['payamount'] }} - {{ $member->user_name }}</div>
                <div class="qr-side right">{{ $data['txid'] }}</div>
                <div class="qr-bottom">ห้ามใช้ซ้ำเด็ดขาด • หมดอายุ {{ $data['expired_date'] }}</div>
                @php $type = detectQrType($data['qrcode']); @endphp

                @if ($type === 'base64_img')
                    <img id="qrcode" src="{{ $data['qrcode'] }}" alt="QR Image">
                @elseif ($type === 'raw_base64')
                    <img id="qrcode" src="data:image/png;base64,{{ $data['qrcode'] }}" alt="QR Image">
                @elseif ($type === 'promptpay_emv')
                    <div id="qr-svg" class="qr-svg-wrap">
                        {!! QrCode::format('svg')->errorCorrection('H')->size(420)->margin(4)->generate($data['qrcode']) !!}
                    </div>
                @elseif ($type === 'url')
                    <a href="{{ $data['qrcode'] }}" target="_blank">{{ $data['qrcode'] }}</a>
                @else
                    <pre>{{ $data['qrcode'] }}</pre>
                @endif
            </div>
        </div>
		
		
		<div class="expiration">{{ __('app.qrscan.expire') }} : <span id="countdown">--:--</span></div>
		
		{{--		<div class="warning-box danger">--}}
		{{--			<i class="fas fa-exclamation-triangle"></i>--}}
		{{--			<div>--}}
		{{--				<strong>{{ __('app.qrscan.note') }} !</strong> {{ __('app.qrscan.note_detail') }}--}}
		{{--			</div>--}}
		{{--		</div>--}}
		
		
		<div class="info" style="padding: 10px; border-radius: 8px; margin-bottom: 10px;">
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

@endsection


@push('scripts')
	
	<script>

        const createdAt = new Date("{{ Carbon::parse($data['date_create'])->toIso8601String() }}");
        const expireAt = new Date("{{ Carbon::parse($data['expired_date'])->toIso8601String() }}");


        const countdown = document.getElementById('countdown');

        let timer;

        function expireAction() {
            fetch("{{ route('api.wellpay.deposit.expire', ['txid' => $data['detail']]) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                body: JSON.stringify({})
            }).then(() => {

//                 document.querySelector('.qr-box').style.display = 'none';
//                 document.getElementById('downloadBtn').style.display = 'none';
//
//                 const expirationDiv = document.querySelector('.expiration');
//                 expirationDiv.innerHTML = `
//     <div id="payment-success" style="
//         opacity: 0;
//         transform: translateY(-10px);
//         background-color: #e6f9ec;
//         border: 2px solid #2ecc71;
//         color: #2e7d32;
//         font-weight: bold;
//         font-size: 16px;
//         padding: 12px 20px;
//         border-radius: 10px;
//         display: inline-block;
//         box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
//         transition: all 0.5s ease;
//     ">
//         ✅ รายการนี้หมดอายุ การใช้งานแล้ว โปรดอย่า่ สแกน QR ของรายการนี้้
//     </div>
// `;
//
// // trigger fade-in
//                 setTimeout(() => {
//                     const el = document.getElementById('payment-success');
//                     el.style.opacity = '1';
//                     el.style.transform = 'translateY(0)';
//                 }, 50);
				
				
				{{--if (window.opener) {--}}
				{{--    window.close();--}}
				{{--} else {--}}
				{{--    window.location.href = "{{ route('customer.home.index') }}";--}}
				{{--}--}}
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
        function trans(key, replace = {}) {
            var translation = key.split('.').reduce((t, i) => t[i] || null, window.i18n);

            for (var placeholder in replace) {
                translation = translation.replace(`:${placeholder}`, replace[placeholder]);
            }
            return translation;
        }

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
                const qrTarget = 300; // px ของ QR ในไฟล์ที่โหลด
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
                const res = await fetch("{{ route('api.wellpay.deposit.status', ['txid' => $data['detail']]) }}");
                const data = await res.json();
                
                
                if (data.success && data.status !== 'pending') {
                    clearInterval(interval);
                    document.querySelector('.qr-box').style.display = 'none';
                    document.getElementById('downloadBtn').style.display = 'none';
                    
                    const expirationDiv = document.querySelector('.expiration');
                    
                    let message = '';
                    switch (data.status) {
                        case 'completed':
                            message = '✅ รายการได้รับการชำระเรียบร้อยแล้ว โปรดตรวจสอบยอดเครดิตของท่าน';
                            break;
                        case 'expired':
                            message = '✅ รายการนี้หมดอายุการใช้งานแล้ว โปรดอย่า่สแกน QR ของรายการนี้';
                            break;
                        case 'CANCEL':
                            message = '✅ รายการนี้ถูกยกเลิกแล้ว';
                            break;
                        default:
                            message = '⚠️ สถานะไม่ทราบแน่ชัด โปรดติดต่อเจ้าหน้าที่';
                    }
                    
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
                    ${message}
                </div>
            `;
                    
                    setTimeout(() => {
                        const el = document.getElementById('payment-success');
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    }, 50);
                    
                   
                }
                

            } catch (e) {
                console.warn('เช็คสถานะไม่สำเร็จ:', e);
            }
        }, 3000);
	</script>
@endpush
