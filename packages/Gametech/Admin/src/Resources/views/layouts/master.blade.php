<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" class="scroll-smooth">
<head>
    <base href="/">
    <meta charset="UTF-8">
    <title>{{ ucwords($config->sitename) }} - @yield('title')</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ core()->imgurl($config->favicon,'img') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Admin Zone">
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
          integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    @stack('styles')

    <link rel="stylesheet" href="{{ asset('assets/admin/css/web.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/ui/css/ui.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/toasty/dist/toasty.min.css') }}">
    <style>
        .toast-container {
            z-index: 9999;
        }
        /* base */
        /*.toastify.rt-toast { position: relative; }*/

        /* ธีมระดับ (ถ้าใช้ t.level) */
        .toastify.rt-danger  { background: #ef4444 !important; color: #fff !important; }
        .toastify.rt-warning { background: #f59e0b !important; color: #111 !important; }
        .toastify.rt-success { background: #10b981 !important; color: #fff !important; }
        .toastify.rt-info    { background: #3b82f6 !important; color: #fff !important; }

        /* แถบสีซ้ายเล็ก ๆ (สวยขึ้นนิด) */
        .toastify.rt-toast::before {
            content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; opacity: .6;
        }
        .toastify.rt-danger::before  { background: #7f1d1d; }
        .toastify.rt-warning::before { background: #92400e; }
        .toastify.rt-success::before { background: #065f46; }
        .toastify.rt-info::before    { background: #1e3a8a; }

        /* ถ้าอยากใช้ Bootstrap class โดยตรง (bg-*) */
        .toastify.bg-warning { background-color: #ffc107 !important; background-image: none !important; color: #212529 !important; border: #000 2px solid !important; }
        .toastify.bg-danger  { background-color: #dc3545 !important; background-image: none !important; color: #fff !important; border: #000 2px solid !important; }
        .toastify.bg-success { background-color: #198754 !important; background-image: none !important; color: #fff !important; border: #000 2px solid !important;  }
        .toastify.bg-info    { background-color: #0dcaf0 !important; background-image: none !important; color: #000 !important; border: #000 2px solid !important;  }

    </style>
    <style>
        /* ใช้กับทุก toast ของเรา */
        .gt-toast {
            font-family: "Prompt", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            border-radius: 999px;
            padding: 8px 16px;
            font-size: 16px;
            display: flex;
            align-items: center;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(6px);
        }

        /* สีพื้นแต่ละประเภท */
        .gt-toast-success {
            background: linear-gradient(135deg, #00c851, #00e676);
            color: #fff;
        }

        .gt-toast-error {
            background: linear-gradient(135deg, #ff4444, #ff6b6b);
            color: #fff;
        }

        .gt-toast-deposit {
            background: linear-gradient(135deg, #149110, #1cac17);
            color: #f5f5f5;
        }

        .gt-toast-withdraw {
            background: linear-gradient(135deg, #401313, rgba(94, 35, 35, 0.93));
            color: #fff;
        }

        .gt-toast-info {
            background: linear-gradient(135deg, #4285f4, #5c9dff);
            color: #fff;
        }

        .gt-toast-topup {
            background: linear-gradient(135deg, #dca721, #e6c56c);
            color: #1e1b1b;
        }

        .gt-toast-admin {
            background: linear-gradient(135deg, #6f42c1, #7a5cab);
            color: #e3e6f5;
        }

        /* ปรับรูป avatar ที่ Toastify ใส่มาให้ */
        .gt-toast .toastify-avatar {
            width: 24px;
            height: 24px;
            margin-right: 10px;
            border-radius: 999px;
            /*background: #fff;*/
            padding: 3px;
        }

        /* ปุ่มปิด (x) ให้กลม ๆ ดูโตขึ้นหน่อย */
        .toastify .toast-close {
            margin-left: 8px;
            font-size: 16px;
            opacity: 0.9;
        }

        .toastify .toastify-avatar {
            width: 64px !important;
            height: 64px !important;
        }

        .app-preloader {
            position: fixed;
            inset: 0; /* top:0; right:0; bottom:0; left:0 */
            z-index: 3000; /* สูงกว่า modal/backdrop ของ AdminLTE */
            background: rgba(0, 0, 0, .25);
            backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body.is-blocking {
            overflow: hidden;
        }

        .badge-bounce {
            animation: badgeBounce 0.6s ease;
            animation-iteration-count: 4; /* เด้ง 4 รอบ */
        }

        @keyframes badgeBounce {
            0% {
                transform: scale(0.6);
                opacity: 0.6;
            }
            50% {
                transform: scale(1.25);
                opacity: 1;
            }
            100% {
                transform: scale(1);
            }
        }
    </style>
    <style>
        [v-cloak] {
            display: none !important
        }
    </style>
    @yield('css')

    <script>
        (function () {
            try {
                if (localStorage.getItem('adminlte-theme') === 'dark') {
                    // ใส่ที่ <html> ก่อน กันกระพริบ แล้วค่อย sync ไป <body> ภายหลัง
                    document.documentElement.classList.add('dark-mode');
                }
            } catch (e) {
            }
        })();
    </script>
    @laravelPWA
</head>

<body class="hold-transition sidebar-mini text-sm">

<div id="app" v-cloak>

    <div class="wrapper">

        @include('admin::layouts.header')

        @include('admin::layouts.sidebar')

        @include('admin::layouts.content')

        @include('admin::layouts.footer')

    </div>


</div>

<script>
    window.flashMessages = [];
    window.serverErrors = [];

    @foreach (['success', 'warning', 'error', 'info'] as $key)
    @if ($value = session($key))
    window.flashMessages.push({'type': '{{ $key }}', 'message': "{{ $value }}"});
    @endif
            @endforeach

            @if (isset($errors))
            @if (count($errors))
        window.serverErrors = @json($errors->getMessages());
    @endif
    @endif
</script>
<audio hidden preload="auto" muted="false" src="{{ asset('storage/sound/alert.mp3') }}" id="alertsound"></audio>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script src="{{ mix('assets/admin/js/manifest.js') }}"></script>
<script src="{{ mix('assets/admin/js/vendor.js') }}"></script>
<script baseUrl="{{ url()->to('/') }}" id="mainscript" src="{{ mix('assets/admin/js/app.js') }}"></script>
{{--<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.8.0/dist/alpine.min.js" defer></script>--}}
<script src="{{ asset('assets/ui/js/ui.js') }}"></script>
{{--<script src="{{ asset('vendor/toasty/dist/toasty.min.js') }}"></script>--}}

@stack('scripts')
@yield('script')
<script type="text/javascript">
    function updateBadge(key, value) {
        const el = document.getElementById('badge_' + key);
        if (!el) return;

        // อัปเดตตัวเลข
        el.textContent = value;

        // เล่นแอนิเมชันเด้ง
        el.classList.remove('badge-bounce');
        void el.offsetWidth; // force reflow
        el.classList.add('badge-bounce');
    }

    function update(key, value) {
        const el = document.getElementById('badge_' + key);
        if (!el) return;

        // อัปเดตตัวเลข
        el.textContent = value;

    }

    let reloadTimer = null;


    function handleRT(e) {
        if (e.ui === 'swal') {
            if (typeof Swal !== 'undefined') Swal.fire(e.swal);
            return;
        }

        const t = e.toast || {};
        const s = e.sound || '';
        const level = (e.level || t.level || '').toLowerCase(); // 'danger' | 'warning' | 'success' | 'info'
        const classes = ['rt-toast'];
        if (level) classes.push(`rt-${level}`);
        if (t.className) classes.push(t.className);

        if (s === 'withdraw') {
            try {
                const a = document.getElementById('alertsound');
                if (a) {

                    a.muted = false;
                    a.currentTime = 0;

                    let repeat = 2; // เล่นรอบแรก + อีก 2 = รวม 3 รอบ

                    const playSound = () => {
                        a.currentTime = 0;
                        a.play().catch(() => {});
                    };

                    playSound();

                    // พอเล่นจบรอบหนึ่ง ให้เล่นต่อจนกว่าจะครบ repeat
                    const handler = () => {
                        if (repeat > 0) {
                            repeat--;
                            playSound();
                        } else {
                            a.removeEventListener('ended', handler);
                        }
                    };

                    a.addEventListener('ended', handler);
                }
            } catch (err) {
                console.warn('sound play error', err);
            }
        }

        Toastify({
            text: e.message,
            duration: t.duration ?? 20000,
            newWindow: t.newWindow ?? true,
            close: t.close ?? true,
            gravity: t.gravity ?? 'top',
            position: t.position ?? 'right',
            stopOnFocus: t.stopOnFocus ?? true,
            className: classes.join(' '),
            style: t.style || undefined,
            avatar: t.avatar || undefined,
        }).showToast();
    }

    const currentUserId = {{ auth()->guard('admin')->id() ?? 'null' }};
    Echo.channel('{{ config('app.name')  }}_events')
        .listen('RealTimeMessage', (e) => {
            Toastify({
                text: e.message,
                duration: 20000,
                newWindow: true,
                close: true,
                gravity: "top", // `top` or `bottom`
                position: "right", // `left`, `center` or `right`
                stopOnFocus: true, // Prevents dismissing of toast on hover
            }).showToast();

        })
        .listen('.RealTime.Message.All', handleRT)
        .listen('SumNewPayment', (e) => {
            if(e.sum === 0){
                update('bank_in', e.sum);
            }else{
                updateBadge('bank_in', e.sum);
            }

            if (currentUserId === e.code) return;

            if ($('#deposittable').length && $.fn.DataTable.isDataTable('#deposittable')) {
                window.LaravelDataTables["deposittable"].draw(false);
            }


        })
        .listen('SumNewWithdraw', (e) => {
            if(e.sum === 0){
                update('withdraw', e.sum);
            }else{
                updateBadge('withdraw', e.sum);
            }


            if (e.type === 'up') {
                let count = 0;
                const intervalId = setInterval(() => {
                    // แสดงแจ้งเตือน
                    window.Toasty.error('<span class="text-danger">มีการ แจ้งถอนรายการใหม่</span>');

                    // เพิ่มตัวนับ
                    count++;

                    // ตรวจสอบว่าแจ้งเตือนครบ 5 รอบหรือยัง
                    if (count === 2) {
                        // หยุดตัวจับเวลา
                        clearInterval(intervalId);
                    }
                }, 3000);  // 1000 มิลลิวินาที คือ 1 วินาที

            }
            if (currentUserId === e.code) return;

            if ($('#withdrawtable').length && $.fn.DataTable.isDataTable('#withdrawtable')) {
                window.LaravelDataTables["withdrawtable"].draw(false);
            }
        })
        .listen('SumNewWithdrawFree', (e) => {
            if (document.getElementById('badge_withdraw_free')) {
                document.getElementById('badge_withdraw_free').textContent = e.sum;
            }
            if (document.getElementById('badge_withdraw_seamless_free')) {
                document.getElementById('badge_withdraw_seamless_free').textContent = e.sum;
            }

            if (e.type === 'up') {
                let count = 0;
                const intervalId = setInterval(() => {
                    // แสดงแจ้งเตือน
                    window.Toasty.error('<span class="text-danger">มีการ แจ้งถอนรายการฟรี ใหม่</span>');

                    // เพิ่มตัวนับ
                    count++;

                    // ตรวจสอบว่าแจ้งเตือนครบ 5 รอบหรือยัง
                    if (count === 2) {
                        // หยุดตัวจับเวลา
                        clearInterval(intervalId);
                    }
                }, 3000);  // 1000 มิลลิวินาที คือ 1 วินาที

            }

            if ($('#withdrawfreetable').length && $.fn.DataTable.isDataTable('#withdrawfreetable')) {
                window.LaravelDataTables["withdrawfreetable"].draw(true);
            }
        });

    Echo.channel('global')
        .listen('RealTimeMessageAll', (e) => {
            Swal.fire(e.message);

        });

    $(document).ready(function () {
        if ($('#dataTableBuilder').length && $.fn.DataTable.isDataTable('#dataTableBuilder')) {
            var table = window.LaravelDataTables["dataTableBuilder"];

            // ปิด event keyup ของ DataTables เดิม
            $('#dataTableBuilder_filter input').off('keyup.DT input.DT');

            // ตั้ง debounce (สมมติ 3 วินาที)
            var debounceSearch = _.debounce(function (val) {
                table.search(val).draw();
            }, 500);

            // bind ใหม่
            $('#dataTableBuilder_filter input').on('keyup', function () {
                debounceSearch(this.value);
            });

        }


    });


    {{--const private_channel = 'admins.{{ auth()->guard('admin')->user()->code }}';--}}
    {{--Echo.private(private_channel)--}}
    {{--    .notification((notification) => {--}}
    //         Toast.fire({
    //             icon: 'success',
    //             title: notification.message
    //         });
    {{--    });--}}
</script>

<script defer>
    document.addEventListener('DOMContentLoaded', function () {
        const KEY = 'adminlte-theme';

        // ให้ body สะท้อนค่าที่บันทึกไว้
        const apply = (dark) => {
            document.body.classList.toggle('dark-mode', dark);
            document.documentElement.classList.toggle('dark-mode', dark); // sync กับ A)
            localStorage.setItem(KEY, dark ? 'dark' : 'light');
        };
        apply(localStorage.getItem(KEY) === 'dark');

        // ใช้ event delegation เผื่อปุ่มถูก render ทีหลัง
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-theme-toggle], #theme-toggle');
            if (!btn) return;
            e.preventDefault();
            apply(!document.body.classList.contains('dark-mode'));
        });
    });
</script>


</body>
</html>
