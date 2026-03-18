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
    <meta name="description" content="{{ $config->description }}">
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
    </style>
    @yield('css')


</head>

<body class="hold-transition sidebar-mini text-sm">

<div id="app">

    <div class="wrapper">

        @include('admin::layouts.header')

        @include('admin::layouts.sidebar')

        @include('admin::layouts.contents')

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
<script src="{{ asset('assets/ui/js/ui.js?v='.time()) }}"></script>
{{--<script src="{{ asset('vendor/toasty/dist/toasty.min.js') }}"></script>--}}

@stack('scripts')
@yield('script')
<script type="text/javascript">

    // window.Echo = new Echo({
    //     broadcaster: 'pusher',
    //     key: process.env.PUSHER_APP_KEY,
    //     wsHost: window.location.hostname,
    //     disableStats: true,
    //     authEndpoint: '/broadcasting/auth'
    // });

    Echo.private('{{ env('APP_NAME')  }}_events')
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
            // window.Toasty.info(e.message);
            // window.app.loadCnt();

        })
        .listen('SumNewPayment', (e) => {
            if (document.getElementById('badge_bank_in')) {
                document.getElementById('badge_bank_in').textContent = e.sum;
            }

            if ($('#deposittable').length && $.fn.DataTable.isDataTable('#deposittable')) {
                window.LaravelDataTables["deposittable"].draw(true);
            }

        })
        .listen('SumNewWithdraw', (e) => {
            if (document.getElementById('badge_withdraw')) {
                document.getElementById('badge_withdraw').textContent = e.sum;
            }
            if (document.getElementById('badge_withdraw_seamless')) {
                document.getElementById('badge_withdraw_seamless').textContent = e.sum;
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

            if ($('#withdrawtable').length && $.fn.DataTable.isDataTable('#withdrawtable')) {
                window.LaravelDataTables["withdrawtable"].draw(true);
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

    Echo.channel('{{ env('APP_NAME')  }}_events')
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
            // window.Toasty.info(e.message);
            // window.app.loadCnt();

        })
        .listen('SumNewPayment', (e) => {
            if (document.getElementById('badge_bank_in')) {
                document.getElementById('badge_bank_in').textContent = e.sum;
            }

            if ($('#deposittable').length && $.fn.DataTable.isDataTable('#deposittable')) {
                window.LaravelDataTables["deposittable"].draw(true);
            }

        })
        .listen('SumNewWithdraw', (e) => {
            if (document.getElementById('badge_withdraw')) {
                document.getElementById('badge_withdraw').textContent = e.sum;
            }
            if (document.getElementById('badge_withdraw_seamless')) {
                document.getElementById('badge_withdraw_seamless').textContent = e.sum;
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

            if ($('#withdrawtable').length && $.fn.DataTable.isDataTable('#withdrawtable')) {
                window.LaravelDataTables["withdrawtable"].draw(true);
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
</body>
</html>
