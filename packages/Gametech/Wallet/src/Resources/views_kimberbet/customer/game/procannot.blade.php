@extends('wallet::layouts.blank')


@section('title','')

@push('styles')
    <style>
        .popup-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .popup-dialog {
            background: #fff;
            border-radius: 16px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.25);
            text-align: center;
            animation: fadeIn 0.3s ease;
        }

        .popup-header {
            background: #f44336;
            padding: 1rem;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }

        .popup-title {
            color: #fff;
            margin: 0;
            font-size: 1.5rem;
        }

        .popup-body {
            padding: 1.5rem;
            font-size: 1.1rem;
            color: #333;
        }

        .popup-footer {
            padding: 1rem;
            border-top: 1px solid #eee;
        }

        .popup-button {
            padding: 0.6rem 1.5rem;
            background: #f44336;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .popup-button:hover {
            background: #d32f2f;
        }

        @keyframes fadeIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
@endpush


@section('content')
    <div id="promo-warning-popup" class="popup-backdrop">
        <div class="popup-dialog">
            <div class="popup-header">
                <h2 class="popup-title">ไม่สามารถเข้าเล่นได้</h2>
            </div>
            <div class="popup-body">
                <p class="popup-message">
                    คุณรับโปรอยู่<br>
                    ไม่สามารถเล่นเกมในหมวดนี้ได้
                </p>
            </div>
            <div class="popup-footer">
                <button class="popup-button" onclick="closePromoPopup()">ตกลง</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script type="text/javascript">
        function closePromoPopup() {
            window.close();
        }
        // $(document).ready(function () {
            setTimeout(function () {
                window.close();
            }, 5000);
        // });
    </script>
@endpush
