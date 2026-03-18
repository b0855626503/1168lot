@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','THAI QR PAYMENT')

@push('styles')
    <style>

        :root {
            --navy: #0f3563; /* เฮดเดอร์ */
            --border: #d7d7d7;
            --label: #888;
            --copy: #173a6b;
            --copy-hover: #0a2b55;
            --danger: #cc1f1a;
            --muted: #6c757d;
            --card-w: 420px;
        }

        * {
            box-sizing: border-box
        }

        body {
            height: auto !important;
            margin: 0;
            background: #f4f6f9;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans Thai", sans-serif;
            color: #222;
        }

        .wrap {
            min-height: 100svh;
            display: grid;
            place-items: center;
            padding: 24px
        }

        .card {
            width: 100%;
            max-width: var(--card-w);
            background: #fff;
            border: 1px solid #cfd4da;
            border-radius: 6px;
            box-shadow: 0 1px 0 rgba(0, 0, 0, .03);
            overflow: hidden
        }

        .card header {
            background: var(--navy);
            padding: 14px 16px;
            text-align: center
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            font-weight: 700;
            letter-spacing: .3px;
            width: max(160px, 20%);
        }

        .brand svg {
            width: 28px;
            height: 28px
        }

        .body {
            padding: 18px 22px
        }

        /*.promptpay-logo{display:grid; background:#0f3563; color:#fff; width: 30%; border-radius:4px; margin:0 auto; font-weight:700}*/

        .rowline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin: 8px 0
        }

        .rowline .left {
            flex: 1;
            background: #f1f1f1;
            border: 1px solid #e6e6e6;
            border-radius: 4px;
            padding: 10px 12px
        }

        .rowline .title {
            color: #555;
            font-size: .9rem
        }

        .rowline .value {
            font-size: 1.25rem;
            font-weight: 700
        }

        .btn-copy {
            white-space: nowrap;
            padding: 7px 10px;
            border: 1px solid #204a83;
            background: #204a83;
            color: #fff;
            border-radius: 4px;
            font-weight: 700;
            cursor: pointer
        }

        .btn-copy:hover {
            background: var(--copy-hover);
            border-color: var(--copy-hover)
        }

        hr {
            border: none;
            border-top: 1px dashed var(--border);
            margin: 14px 0
        }

        .meta {
            font-size: .92rem;
            color: #333
        }

        .meta .small {
            font-size: .86rem;
            color: #666
        }

        .muted {
            color: #6c757d
        }

        .warn {
            color: var(--danger);
            font-weight: 600;
            margin: 8px 0;
            font-size: 16px;
        }

        .qrbox {
            display: grid;
            place-items: center;
            margin: 14px 0 8px
        }

        .qrbox img {
            width: 240px;
            height: 240px;
            object-fit: contain;
            image-rendering: pixelated;
            border: 6px solid #e9ecef;
            border-radius: 6px
        }

        .expiry {
            text-align: center;
            color: #666;
            font-size: .95rem;
            margin: 8px 0 4px
        }

        /* footer note */
        .footnote {
            font-size: .84rem;
            color: #555;
            text-align: center;
            padding: 0 10px 6px
        }

        /* จัดกึ่งกลางทั้งแนวตั้งและแนวนอน */
        .qr_payment .qr-box {
            display: grid;
            place-items: center;
            /*border: 2px dashed #ccc;*/
            padding: 6px;
            border-radius: 8px;
            background: #fff;
        }

        /* ขนาด QR แบบยืดหยุ่น แต่ไม่เกิน 300px และอยู่กลางเสมอ */
        .qr_payment .qr-box img,
        .qr_payment .qr-box svg {
            display: block;
            width: clamp(200px, 60vw, 250px); /* 200–300px ตามจอ */
            height: auto;
            margin: 0 auto;
            background: transparent;
            padding: 0;
            border-radius: 4px;
        }

        .qr_payment .qr-box {
            display: grid;
            place-items: center;
            /*border:2px dashed #ccc; */
            padding: 16px 36px; /* เผื่อที่สำหรับป้ายข้าง */
            border-radius: 8px;
            background: #fff;
        }

        .qr_payment .qr-wrap {
            position: relative;
            display: inline-block;
        }

        .qr_payment .qr-box img, .qr-box svg {
            display: block;
            width: clamp(200px, 60vw, 250px);
            height: auto;
            margin: 0 auto;
            background: transparent;
            border-radius: 4px;
        }

        .qr_payment .qr-box {
            /*border: 2px dashed #ccc;*/
            padding: 10px;
            border-radius: 8px;
            /*margin-bottom: 10px;*/
        }

        .qr_payment .qr-box img {
            width: 100%;
            max-width: 200px;
            height: auto;
        }

        /* ปรับ wrap ให้เป็นคอนเทนเนอร์กว้างกลางหน้า */
        .wrap.qr_payment {
            min-height: unset; /* ไม่ต้องเต็มจอเพื่อให้สูงตามเนื้อหา */
            display: block; /* จากเดิม grid + place-items:center */
            padding: 16px;
        }

        .qr-grid {
            max-width: 1120px; /* กรอบรวมบนเดสก์ท็อป */
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr; /* มือถือ: 1 คอลัมน์ */
            gap: 16px;
        }

        /* การ์ดไม่จำกัดความกว้างคงที่อีกต่อไป */
        .card {
            width: 100%;
            max-width: none;
        }

        /* เดสก์ท็อปขึ้นไป: 2 คอลัมน์ */
        @media (min-width: 992px) {
            .qr-grid {
                grid-template-columns: 1fr 1fr; /* ซ้าย/ขวา */
                align-items: start;
                gap: 20px;
            }

            /* การ์ดล่าง (customer) ให้พาด 2 คอลัมน์ */
            .card-span-2 {
                grid-column: 1 / -1;
            }
        }

        /* ปรับโลโก้ PromptPay ให้กว้างอัตโนมัติ ไม่บีบ */
        .promptpay-logo {
            display: grid;
            place-items: center;
            /*background:#0f3563;*/
            color: #fff;
            border-radius: 4px;
            margin: 0 auto;
            /*padding: 6px 10px;*/
            width: max(160px, 40%); /* อย่างน้อย 160px หรือ 40% ของการ์ด */
        }

        .promptpay-logo img {
            max-height: 28px;
        }

        /* กล่อง QR ให้ปรับขนาดตามการ์ด */
        .qrbox img {
            width: min(260px, 80%);
            height: auto;
        }

        /* เส้นแบ่งและตัวหนังสือให้กระชับพื้นที่ */
        hr {
            margin: 12px 0;
        }

        .meta {
            font-size: .95rem;
        }

        /* เดิมมี .qr-grid แล้ว — เพิ่มต่อท้ายได้เลย */
        .qr-grid {
            max-width: 1120px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        /* คอลัมน์ขวา (ห่อการ์ด 2 ใบทางขวา) */
        .right-col {
            display: grid;
            gap: 20px;
        }

        @media (min-width: 992px) {
            .qr-grid {
                grid-template-columns: 1fr 1fr; /* ซ้าย QR, ขวา รายละเอียด+ลูกค้า */
                align-items: start;
                gap: 20px;
            }

            .right-col {
                grid-column: 2; /* ให้คอลัมน์ขวาไปอยู่ช่องขวา */
            }
        }

        .qr_payment .expiration {
            font-size: 13px;
            color: #0236b8;
            margin-bottom: 10px;
        }

        /* ===== Payment Info Card ===== */
        .payment-info {
            display: grid;
            gap: 12px;
        }

        .info-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #f9fbff;
        }

        .info-item:hover {
            box-shadow: 0 2px 10px rgba(0, 0, 0, .05)
        }

        .info-label {
            color: #445;
            font-size: .92rem;
            white-space: nowrap;
        }

        .info-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .info-value {
            font-weight: 700;
            color: var(--copy);
            letter-spacing: .2px;
        }

        .info-value.mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Noto Sans Mono", monospace;
        }

        /* ปุ่มคัดลอก: ใช้ฐาน .btn-copy เดิม แล้วทำเวอร์ชันเล็ก */
        .btn-copy.-sm {
            padding: 6px 10px;
            font-size: .85rem;
            line-height: 1;
            border-radius: 6px;
        }

        .copied {
            font-size: .85rem;
            color: #16a34a;
            opacity: 0;
            transition: opacity .18s ease;
        }

        .copied.show {
            opacity: 1
        }

        /* เส้นหัวข้อเล็ก ๆ */
        .section-title {
            font-weight: 700;
            color: #222;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title .dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--navy);
        }

        /* ===== Payment Info (refined) ===== */
        .payment-card {
            display: grid;
            gap: 12px;
        }

        .info-row {
            display: grid;
            grid-template-columns: minmax(120px, 180px) 1fr; /* ซ้าย=ฉลาก ขวา=ค่า+ปุ่ม */
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            background: #fff;
            border: 1px solid #e6ebf1;
            border-radius: 12px;
        }

        .info-row .label {
            color: #556070;
            font-size: .92rem;
        }

        .info-row .content {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .value {
            font-weight: 700;
            color: var(--copy);
            letter-spacing: .2px;
        }

        .value.mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Noto Sans Mono", monospace;
            font-size: 1.02rem;
        }

        /* ปุ่มคัดลอกแบบ pill */
        .btn-copy {
            white-space: nowrap;
            padding: 6px 12px;
            border: 1px solid #173a6b;
            background: #173a6b;
            color: #fff;
            border-radius: 999px;
            font-weight: 700;
            cursor: pointer;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-copy:hover {
            background: #0f2e56;
            border-color: #0f2e56
        }

        .btn-copy svg {
            width: 14px;
            height: 14px;
        }

        .copied {
            font-size: .85rem;
            color: #16a34a;
            opacity: 0;
            transition: opacity .18s ease;
        }

        .copied.show {
            opacity: 1;
        }

        /* mobile: ให้ฉลากอยู่บน, ค่า/ปุ่มอยู่ล่าง ชิดซ้าย-ขวาพอดี */
        @media (max-width: 575.98px) {
            .info-row {
                grid-template-columns: 1fr;
                gap: 6px;
                padding: 12px;
            }

            .info-row .content {
                justify-content: space-between;
            }
        }

        /* === FIX: mobile overflow for payment info === */

        /* มือถือ: label อยู่บน, ด้านล่างเป็น "ค่า | ปุ่ม" แบบ 1fr auto */
        @media (max-width: 575.98px) {
            .payment-card .info-item {
                display: grid !important;
                grid-template-columns: 1fr; /* แถวเดียว */
                gap: 8px;
                padding: 12px; /* ลดระยะให้พอดีจอ */
            }

            .payment-card .info-item .info-label {
                margin: 0; /* เลเบลขึ้นบรรทัดบน */
                font-size: .9rem;
            }

            .payment-card .info-item .info-right {
                display: grid;
                grid-template-columns: 1fr auto; /* ค่า ชิดซ้าย | ปุ่ม ชิดขวา */
                align-items: center;
                gap: 8px;
                min-width: 0; /* อนุญาตให้หด ไม่ดันล้นจอ */
            }

            .payment-card .info-item .info-value {
                min-width: 0;
                word-break: break-all; /* ตัวเลขยาวยอมตัดบรรทัด */
                overflow-wrap: anywhere;
                line-height: 1.2;
            }

            .payment-card .info-item .btn-copy {
                padding: 8px 12px; /* ปุ่มขนาดพอดี */
                flex: 0 0 auto;
            }

            /* ประหยัดพื้นที่: ซ่อนข้อความ "คัดลอกแล้ว" บนมือถือ */
            .payment-card .info-item .copied {
                display: none !important;
            }
        }

        /* เดสก์ท็อป: ให้บล็อกค่าหด/ตัดได้ ไม่ดันล้น container */
        .payment-card .info-item .info-right {
            min-width: 0;
        }

        .payment-card .info-item .info-value {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis; /* ถ้าไม่อยากตัดบรรทัดบนเดสก์ท็อปจะขึ้น ... */
            white-space: nowrap;
        }

        /* ถ้าอยากให้เดสก์ท็อปก็หักบรรทัดได้เหมือนมือถือ ให้แทน 3 บรรทัดบนด้วยสองบรรทัดนี้:
           white-space: normal;
           overflow: visible;
        */
        /* --- เดสก์ท็อป: ให้ค่ากับปุ่มแพ็กเป็นก้อนเดียวชิดขวา --- */
        .payment-card .info-item {
            display: flex;
            align-items: center;
        }

        .payment-card .info-item .info-right {
            margin-left: auto; /* ดันทั้งก้อนชิดขวา */
            display: inline-flex;
            align-items: center;
            gap: 8px; /* เว้นระยะระหว่างเลขกับปุ่ม */
            flex-wrap: nowrap; /* ไม่ให้ตัดบรรทัดคั่นกลาง */
            text-align: right;
        }

        /* --- มือถือ: ย้าย label ขึ้นบรรทัดบน แล้ว "ค่า+ปุ่ม" ชิดขวาไปด้วยกัน --- */
        @media (max-width: 575.98px) {
            .payment-card .info-item {
                display: grid !important;
                grid-template-columns: 1fr; /* label บรรทัดบน, content บรรทัดล่าง */
                gap: 8px;
                padding: 12px;
            }

            .payment-card .info-item .info-right {
                justify-self: end; /* ชิดขวาของการ์ด */
                display: inline-flex; /* แพ็กเลขกับปุ่มไว้ด้วยกัน */
                align-items: center;
                gap: 8px;
                width: auto; /* หดเท่าที่จำเป็น ไม่กินพื้นที่ */
            }

            .payment-card .info-item .info-value {
                white-space: nowrap; /* เลขไม่หักบรรทัดคั่นปุ่ม */
            }

            /* ถ้าต้องการลดที่ว่างขอบขวาสุดจริง ๆ ก็ลด padding ขวาของการ์ดลงนิดได้ */
            /* .payment-card .info-item { padding-right: 10px; } */
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

        @media (max-width: 480px) {


            .qr_payment .btn-download {
                width: 100%;
                font-size: 16px;
                padding: 12px;
            }
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
    <div class="sub-page sub-footer bg-member">
        <div class="container p-0">
            <div class="member-container">
                <div class="wrap qr_payment">
                    <div class="qr-grid">

                        {{-- คอลัมน์ซ้าย: QR --}}
                        <article class="card" role="region" aria-label="Thai QR Payment">
                            <header>
                                <div class="brand" aria-label="THAI QR PAYMENT">
                                    <img class="img-fluid" src="{{ url('images/logo/logo_promptpay.png') }}">
                                </div>
                            </header>

                            <div class="body">
                                <div class="promptpay-logo justify-content-center">
                                    <img class="img-fluid" src="{{ url('images/logo/promptpay.png') }}">
                                </div>

                                @php $type = detectQrType($data['qrcode']); @endphp
                                <div class="qr-box">
                                    <div class="qr-wrap">
                                        @php $type = detectQrType($data['qrcode']); @endphp

                                        @if ($type === 'base64_img')
                                            <img id="qrcode" src="{{ $data['qrcode'] }}" alt="QR Image">
                                        @elseif ($type === 'raw_base64')
                                            <img id="qrcode" src="data:image/png;base64,{{ $data['qrcode'] }}"
                                                 alt="QR Image">
                                        @elseif ($type === 'promptpay_emv')
                                            <div id="qr-svg" class="qr-svg-wrap">
                                                {!! QrCode::format('svg')->errorCorrection('H')->size(240)->margin(4)->generate($data['qrcode']) !!}
                                            </div>
                                        @elseif ($type === 'url')
                                            <a href="{{ $data['qrcode'] }}" target="_blank">{{ $data['qrcode'] }}</a>
                                        @else
                                            <pre>{{ $data['qrcode'] }}</pre>
                                        @endif
                                    </div>
                                </div>

                                <div class="expiration text-center">{{ __('app.qrscan.expire') }} : <span
                                            id="countdown">--:--</span></div>


                                {{--                    <div class="payment-card promptpay">--}}

                                {{--                        <div class="info-row">--}}
                                {{--                            <div class="label">ชื่อบัญชี</div>--}}
                                {{--                            <div class="content">--}}
                                {{--                                <div class="value">{{ $data['bankAccountName'] }}</div>--}}
                                {{--                            </div>--}}
                                {{--                        </div>--}}

                                {{--                        <div class="info-row">--}}
                                {{--                            <div class="label">เลขบัญชี</div>--}}
                                {{--                            <div class="content">--}}
                                {{--                                <div class="value mono">{{ $data['bankAccountNumber'] }}</div>--}}
                                {{--                                <button type="button" class="btn-copy" data-copy="{{ $data['bankAccountNumber'] }}">--}}
                                {{--                                    --}}{{-- ไอคอน copy --}}
                                {{--                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">--}}
                                {{--                                        <path d="M9 9h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Z"--}}
                                {{--                                              stroke="currentColor" stroke-width="1.6"/>--}}
                                {{--                                        <path d="M7 15H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1"--}}
                                {{--                                              stroke="currentColor" stroke-width="1.6"/>--}}
                                {{--                                    </svg>--}}
                                {{--                                    คัดลอก--}}
                                {{--                                </button>--}}
                                {{--                                <span class="copied" aria-live="polite">คัดลอกแล้ว</span>--}}
                                {{--                            </div>--}}
                                {{--                        </div>--}}

                                {{--                        <div class="info-row">--}}
                                {{--                            <div class="label">ธนาคาร</div>--}}
                                {{--                            <div class="content">--}}
                                {{--                                <div class="value">{{ $data['bankName'] }}</div>--}}
                                {{--                            </div>--}}
                                {{--                        </div>--}}


                                {{--                        <div class="info-row">--}}
                                {{--                            <div class="label">เลขพร้อมเพย์</div>--}}
                                {{--                            <div class="content">--}}
                                {{--                                <div class="value mono">{{ $data['promptpayNumber'] }}</div>--}}
                                {{--                                <button type="button" class="btn-copy" data-copy="{{ $data['promptpayNumber'] }}">--}}
                                {{--                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">--}}
                                {{--                                        <path d="M9 9h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Z"--}}
                                {{--                                              stroke="currentColor" stroke-width="1.6"/>--}}
                                {{--                                        <path d="M7 15H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1"--}}
                                {{--                                              stroke="currentColor" stroke-width="1.6"/>--}}
                                {{--                                    </svg>--}}
                                {{--                                    คัดลอก--}}
                                {{--                                </button>--}}
                                {{--                                <span class="copied" aria-live="polite">คัดลอกแล้ว</span>--}}
                                {{--                            </div>--}}
                                {{--                        </div>--}}
                                {{--                        --}}
                                {{--                    </div>--}}

                                {{--                    <div class="payment-card promptpay">--}}

                                {{--                        <div class="info-item">--}}
                                {{--                            <div class="info-label">ชื่อบัญชี</div>--}}
                                {{--                            <div class="info-right">--}}
                                {{--                                <div class="info-value">{{ $data['bankAccountName'] }}</div>--}}
                                {{--                            </div>--}}
                                {{--                        </div>--}}

                                {{--                        <div class="info-item">--}}
                                {{--                            <div class="info-label">เลขบัญชี</div>--}}
                                {{--                            <div class="info-right">--}}
                                {{--                                <div class="info-value mono">{{ $data['bankAccountNumber'] }}</div>--}}
                                {{--                                <button type="button"--}}
                                {{--                                        class="btn-copy -sm"--}}
                                {{--                                        data-copy="{{ $data['bankAccountNumber'] }}"--}}
                                {{--                                        aria-label="คัดลอกเลขบัญชี">--}}
                                {{--                                    คัดลอก--}}
                                {{--                                </button>--}}
                                {{--                                <span class="copied" aria-live="polite">คัดลอกแล้ว</span>--}}
                                {{--                            </div>--}}
                                {{--                        </div>--}}

                                {{--                        <div class="info-item">--}}
                                {{--                            <div class="info-label">ธนาคาร</div>--}}
                                {{--                            <div class="info-right">--}}
                                {{--                                <div class="info-value">{{ $banks[$data['bankName']] }} ({{ $data['bankName'] }})</div>--}}
                                {{--                            </div>--}}
                                {{--                        </div>--}}


                                {{--                        <div class="info-item">--}}
                                {{--                            <div class="info-label">เลขพร้อมเพย์</div>--}}
                                {{--                            <div class="info-right">--}}
                                {{--                                <div class="info-value mono">{{ $data['promptpayNumber'] }}</div>--}}
                                {{--                                <button type="button"--}}
                                {{--                                        class="btn-copy -sm"--}}
                                {{--                                        data-copy="{{ $data['promptpayNumber'] }}"--}}
                                {{--                                        aria-label="คัดลอกเลขพร้อมเพย์">--}}
                                {{--                                    คัดลอก--}}
                                {{--                                </button>--}}
                                {{--                                <span class="copied" aria-live="polite">คัดลอกแล้ว</span>--}}
                                {{--                            </div>--}}
                                {{--                        </div>--}}

                                {{--                    </div>--}}

                                <a id="downloadBtn" class="btn-download text-center"
                                   style="cursor: pointer;">{{ __('app.qrscan.download') }}</a>
                                <canvas id="qrCanvas" style="display:none;"></canvas>

                        </article>

                        {{-- คอลัมน์ขวา: รวม "รายละเอียด" + "ลูกค้า" ซ้อนกัน --}}
                        <div class="right-col">

                            {{-- รายละเอียด / คำเตือน --}}
                            <article class="card" role="region" aria-label="Detail">
                                @php
                                    $date1 = Carbon::parse($data['date_create']);
                                    $date2 = Carbon::parse($data['expired_date']);
                                    $minutes = $date1->diffInMinutes($date2);

                                    $discount = false;
                                    if ($data['payamount'] < $data['amount']){
                                        $diff = $data['amount'] - $data['payamount'];
                                        $discount = true;
                                    }

                                @endphp

                                <div class="body">
                                    <div class="meta">
                                        <div>{{ __('app.qrscan.date') }} <span id="orderTime"
                                                                               class="small">{{ $data['date_create'] }}</span>
                                        </div>
                                        <div>{{ __('app.qrscan.txtid') }} <span id="orderTime"
                                                                                class="small">{{ $data['txid'] }}</span>
                                        </div>
                                        <div class="warn"
                                             style="margin-top:6px; color:#dc2626; font-weight:600">
                                            {!! __('app.qrscan.will_expire', [
                 'field' => '<span id="limitMin">'.$minutes.'</span>'
             ]) !!}

                                        </div>

                                        @if($discount)
                                            <div class="discount">
                                                <div class="small"
                                                     style="margin-top:10px">{{ __('app.qrscan.discount_1') }}</div>
                                                <div class="small">

                                                    {!! __('app.qrscan.discount_2', [
                            'field' => '<strong>'.e($data['payamount']).'</strong>'
                        ]) !!}
                                                </div>
                                                <div class="small">

                                                    {!! __('app.qrscan.discount_3', [
                                                      'field' => '<strong>'.e($diff).'</strong>'
                                                  ]) !!}

                                                </div>
                                            </div>
                                        @else
                                            <div class="normal">
                                                {{-- แก้ tag strong ที่หาย > --}}
                                                <div class="small">
                                                    {!! __('app.qrscan.payamount', [
                   'field' => '<strong>'.e($data['payamount']).'</strong>'
               ]) !!}</div>
                                            </div>
                                        @endif
                                    </div>

                                    <p class="warn">{{ __('app.qrscan.warning') }}
                                        !! {{ __('app.qrscan.warning_detail') }}</p>
                                    <p class="warn">{{ __('app.qrscan.warning') }}
                                        !!! {{ __('app.qrscan.warning_detail_2') }}</p>
                                </div>
                            </article>

                            {{-- ข้อมูลบัญชีลูกค้า --}}
                            <article class="card" role="region" aria-label="customer">
                                <div class="body">
                                    <div class="meta">
                                        <div class="info"
                                             style="padding: 10px; border-radius: 8px; margin-bottom: 10px;">
                                            @php
                                                $lang = app()->getLocale();
                                                    if ($lang !== 'th') {
                                                        $lang = 'en';
                                                    }

                                                    $nameField = "name_{$lang}";

                                            @endphp
                                            <strong>📌 {{ __('app.qrscan.use_account') }}:</strong>
                                            <br>{{ __('app.qrscan.acc_name') }}: <strong>{{ $member->name }}</strong>
                                            <br>{{ __('app.qrscan.acc_bank') }}:
                                            <strong>{{ $member->bank->{$nameField} }}</strong>
                                            <br>{{ __('app.qrscan.acc_no') }}: <strong>{{ $member->acc_no }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </article>

                        </div> {{-- /.right-col --}}

                    </div> {{-- /.qr-grid --}}

                </div>
            </div>
        </div>
    </div>
@endsection


@push('scripts')

    <script>
        document.addEventListener("DOMContentLoaded", function () {
        const createdAt = new Date("{{ Carbon::parse($data['date_create'])->toIso8601String() }}");
        const expireAt = new Date("{{ Carbon::parse($data['expired_date'])->toIso8601String() }}");


        const countdown = document.getElementById('countdown');

        let timer;

        function expireAction() {
            fetch("{{ route('api.kingpay.deposit.expire', ['txid' => $data['detail']]) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                body: JSON.stringify({})
            }).then(() => {

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
        });
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
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById('downloadBtn').addEventListener('click', async function () {
                // ====== ข้อมูลจาก Blade ======
                const type = "{{ $type }}";
                const amount = "฿{{ number_format($data['payamount'], 2) }}";
                const created = "{{ $data['date_create'] }}";
                const orderId = "{{ $data['detail'] }}";
                const txid = "{{ $data['txid'] }}";
                const siteName = "{{ request()->getHost() }}";
                const accountName = "{{ $member->name }}";
                const bankName = "{{ $member->bank->name_th }}";
                const accountNo = "{{ $member->acc_no }}";
                const expireTime = new Date("{{ \Carbon\Carbon::parse($data['expired_date'])->toIso8601String() }}")
                    .toLocaleTimeString('th-TH', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
                const expireText = '{{ __("app.qrscan.expire") }} ' + expireTime;

                // ====== อ้างอิง canvas ======
                const canvas = document.getElementById('qrCanvas');
                const ctx = canvas.getContext('2d', {willReadFrequently: true});

                // ====== utils ======
                const isIOS = () => /iP(hone|ad|od)/.test(navigator.userAgent) || /Macintosh/.test(navigator.userAgent) && 'ontouchend' in document;
                const supportsDownloadAttr = () => 'download' in document.createElement('a');

                function dataURLToBlob(dataURL) {
                    const parts = dataURL.split(',');
                    const meta = parts[0];
                    const base64 = parts[1];
                    const isBase64 = meta.indexOf('base64') >= 0;
                    const mime = (meta.match(/data:([^;]+)/) || [, 'application/octet-stream'])[1];
                    let byteString;
                    if (isBase64) {
                        byteString = atob(base64);
                    } else {
                        byteString = decodeURIComponent(parts[1]);
                    }
                    const len = byteString.length;
                    const u8 = new Uint8Array(len);
                    for (let i = 0; i < len; i++) u8[i] = byteString.charCodeAt(i);
                    return new Blob([u8], {type: mime});
                }

                function canvasToBlobSafe(canvas) {
                    return new Promise((resolve, reject) => {
                        if (canvas.toBlob) {
                            canvas.toBlob(b => b ? resolve(b) : reject(new Error('toBlob failed')), 'image/png');
                        } else {
                            try {
                                const dataURL = canvas.toDataURL('image/png');
                                resolve(dataURLToBlob(dataURL));
                            } catch (e) {
                                reject(e);
                            }
                        }
                    });
                }

                async function tryWebShare(file, title = siteName) {
                    if (navigator.canShare && navigator.canShare({files: [file]})) {
                        try {
                            await navigator.share({files: [file], title});
                            return true;
                        } catch {
                            return false; // ผู้ใช้ยกเลิกก็ให้ตกไปแผน B
                        }
                    }
                    return false;
                }

                function downloadBlob(blob, filename) {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = filename;

                    // ต้องแนบลง DOM เพื่อให้ browser บางตัวยอมคลิก
                    document.body.appendChild(a);
                    a.click();

                    // cleanup
                    setTimeout(() => {
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    }, 100);
                }

                function openInNewTabForSave(url) {
                    const w = window.open(url, '_blank');
                    if (!w) {
                        alert('เบราว์เซอร์บล็อกหน้าต่างใหม่ไว้ โปรดอนุญาตป๊อปอัป แล้วลองอีกครั้ง');
                    }
                }

                function buildFilename() {
                    // ใช้ slug ของวันที่เพื่อลดปัญหาตัวอักษรพิเศษในไฟล์เนม
                    const slugDate = "{{ \Illuminate\Support\Str::slug($data['date_create']) }}";
                    return `qrcode_{{ number_format($data['payamount'], 2) }}_${slugDate}.png`;
                }

                // โหลดรูป QR สำหรับวาดบน canvas (ป้องกัน taint/cors ให้เรียบร้อย)
                function loadImageForCanvas() {
                    return new Promise((resolve, reject) => {
                        const img = new Image();
                        // base64/data url ไม่ต้องใส่ crossOrigin ก็ได้ แต่ใส่ไว้ไม่เสียหาย
                        img.crossOrigin = 'anonymous';
                        img.onload = () => resolve(img);
                        img.onerror = () => reject(new Error('โหลดภาพ QR ไม่สำเร็จ'));

                        if (type === 'base64_img') {
                            img.src = "{{ $data['qrcode'] }}";
                        } else if (type === 'raw_base64') {
                            img.src = 'data:image/png;base64,{{ $data["qrcode"] }}';
                        } else if (type === 'promptpay_emv') {
                            const svgEl = document.querySelector('#qr-svg svg');
                            if (!svgEl) return reject(new Error('ไม่พบ SVG ของ QR'));
                            // ensure มี width/height ให้ renderer ไม่เดาเอง
                            if (!svgEl.getAttribute('width')) svgEl.setAttribute('width', '256');
                            if (!svgEl.getAttribute('height')) svgEl.setAttribute('height', '256');

                            const svgData = new XMLSerializer().serializeToString(svgEl);
                            const svgBlob = new Blob([svgData], {type: 'image/svg+xml;charset=utf-8'});
                            const url = URL.createObjectURL(svgBlob);
                            img.onload = () => {
                                URL.revokeObjectURL(url);
                                resolve(img);
                            };
                            img.src = url;
                        } else {
                            reject(new Error('Unsupported QR type for download.'));
                        }
                    });
                }

                try {
                    const qrImg = await loadImageForCanvas();

                    // ====== วาดลง canvas (เพิ่มความคม/กันเบลอ) ======
                    const qrTarget = 300; // ขนาด QR ในไฟล์
                    const sidePadding = 100;
                    const lineHeight = 28;

                    const lines = [
                        siteName.toUpperCase(),
                        `${amount} - ${created}`,
                        expireText,
                        '{{ __("app.qrscan.orderid") }}: ' + orderId,
                        '{{ __("app.qrscan.txtid") }}: ' + txid,
                        '⚠ {{ __("app.qrscan.warning_detail") }}',
                        '{{ __("app.qrscan.use_account") }}',
                        '{{ __("app.qrscan.acc_name") }}: ' + accountName,
                        '{{ __("app.qrscan.acc_bank") }}: ' + bankName,
                        '{{ __("app.qrscan.acc_no") }}: ' + accountNo
                    ];

                    const width = qrTarget + sidePadding * 2;
                    const height = qrTarget + (lines.length * lineHeight) + 70;

                    canvas.width = width;
                    canvas.height = height;

                    // พื้นหลัง
                    ctx.fillStyle = '#fff';
                    ctx.fillRect(0, 0, width, height);

                    ctx.imageSmoothingEnabled = false;

                    // หัวข้อเว็บ
                    ctx.fillStyle = '#222';
                    ctx.font = 'bold 18px Tahoma, sans-serif';
                    ctx.textAlign = 'center';
                    ctx.fillText(lines[0], width / 2, 32);

                    // วาด QR
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

                    // ====== สร้างไฟล์ แล้วพยายามดาวน์โหลด/แชร์อย่างเหมาะสม ======
                    const filename = buildFilename();
                    const blob = await canvasToBlobSafe(canvas);

                    // 1) ลอง Web Share API (เหมาะกับมือถือ โดยเฉพาะ iOS รุ่นใหม่)
                    if (await tryWebShare(new File([blob], filename, {type: 'image/png'}), siteName)) {
                        return;
                    }

                    // 2) ถ้าเบราว์เซอร์รองรับ download attribute + ไม่ใช่ iOS (มักไม่รองรับกับ data/object URL)
                    if (supportsDownloadAttr() && !isIOS()) {
                        downloadBlob(blob, filename);
                        return;
                    }

                    // 3) Fallback: เปิดแท็บใหม่ให้ผู้ใช้กดบันทึกเอง
                    const url = URL.createObjectURL(blob);
                    openInNewTabForSave(url);
                    // revoke ทีหลังอีกนิดหน่อย
                    setTimeout(() => URL.revokeObjectURL(url), 30_000);

                } catch (e) {
                    alert('ไม่สามารถสร้างไฟล์ดาวน์โหลดได้: ' + (e?.message || e));
                }
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const txid = "{{ $data['detail'] }}";
            const interval = setInterval(async () => {
                try {
                    const res = await fetch("{{ route('api.kingpay.deposit.status', ['txid' => $data['detail']]) }}");
                    const data = await res.json();


                    if (data.success && data.status !== 'pending') {
                        clearInterval(interval);
                        document.querySelector('.qr-box').style.display = 'none';
                        // document.querySelector('.promptpay').style.display = 'none';
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
                        margin-top: 20px;
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
        });
    </script>
@endpush
