@extends('wallet::layouts.blank')


@section('title','')




@section('content')
    <div class="game-loading-wrapper">
        <div class="game-loading-box">
            <div class="glow-border">
                <div class="loading-circle"></div>
                <div class="loading-text">เข้าสู่เกม</div>
                <div class="subtext">กำลังเตรียมการผจญภัยของคุณ กรุณารอสักครู่...</div>
            </div>
        </div>

        <!-- Optional sparkles or particles -->
        <div class="particles"></div>
    </div>
@endsection

@push('styles')

    <style>
        .game-loading-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: radial-gradient(circle at center, #1c1c1c, #0d0d0d);
            overflow: hidden;
            position: relative;
        }

        .game-loading-box {
            text-align: center;
            z-index: 10;
        }

        .glow-border {
            padding: 40px;
            border: 3px solid #ffeb3b;
            border-radius: 16px;
            background-color: rgba(0,0,0,0.7);
            box-shadow: 0 0 20px #fff00088, 0 0 60px #fff00044;
            animation: pulseGlow 2s infinite ease-in-out;
        }

        .loading-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 20px;
            border: 6px solid transparent;
            border-top: 6px solid #fff000;
            animation: spin 1.2s linear infinite;
        }

        .loading-text {
            font-size: 24px;
            color: #fff000;
            text-shadow: 0 0 10px #fff000, 0 0 20px #fff000;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .subtext {
            margin-top: 10px;
            font-size: 14px;
            color: #cccccc;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes pulseGlow {
            0%, 100% {
                box-shadow: 0 0 20px #fff00088, 0 0 60px #fff00044;
            }
            50% {
                box-shadow: 0 0 40px #fff000aa, 0 0 90px #fff00066;
            }
        }

        /* Optional: fake particle effect */
        .particles::before {
            content: '';
            position: absolute;
            width: 150%;
            height: 150%;
            background: url('https://www.transparenttextures.com/patterns/dark-matter.png') repeat;
            opacity: 0.03;
            animation: moveBackground 60s linear infinite;
        }

        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-50%, -50%); }
        }
    </style>

@endpush

@push('scripts')

    <script type="text/javascript">
        setTimeout(function () {
            location.href = '{!! $url !!}';
        }, 2000);

    </script>

@endpush
