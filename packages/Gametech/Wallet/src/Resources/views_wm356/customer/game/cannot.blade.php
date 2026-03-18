@extends('wallet::layouts.blank')


@section('title','')




@section('content')
    <div class="game-error-wrapper">
        <div class="game-error-box">
            <div class="error-icon">⚠️</div>
            <h1 class="error-title">ขออภัย</h1>
            <p class="error-message">
                เกมนี้ไม่สามารถเล่นได้<br>
                หรือยังไม่พร้อมให้บริการในขณะนี้
            </p>

        </div>
    </div>
@endsection

@push('styles')
    <style>
        .game-error-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: radial-gradient(circle, #1a1a1a, #0d0d0d);
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
            position: relative;
        }

        .game-error-box {
            text-align: center;
            background: rgba(30, 30, 30, 0.85);
            padding: 40px;
            border-radius: 20px;
            border: 2px solid #ff3c3c;
            box-shadow: 0 0 20px #ff3c3c66, 0 0 60px #ff3c3c33;
            max-width: 400px;
            animation: fadeInScale 0.5s ease-out;
        }

        .error-icon {
            font-size: 48px;
            color: #ff3c3c;
            margin-bottom: 20px;
            text-shadow: 0 0 10px #ff3c3c;
        }

        .error-title {
            font-size: 28px;
            margin-bottom: 10px;
            color: #ff3c3c;
            text-shadow: 0 0 5px #ff3c3c;
        }

        .error-message {
            font-size: 18px;
            color: #ccc;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .error-button {
            padding: 12px 24px;
            background: #ff3c3c;
            border: none;
            color: #fff;
            font-size: 16px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s ease;
            box-shadow: 0 0 10px #ff3c3c99;
        }

        .error-button:hover {
            background: #e00000;
        }

        @keyframes fadeInScale {
            from {
                transform: scale(0.8);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
@endpush

@push('scripts')
    <script type="text/javascript">
        // $(document).ready(function () {
            setTimeout(function () {
                window.close();
            }, 2000);
        // });
    </script>
@endpush
