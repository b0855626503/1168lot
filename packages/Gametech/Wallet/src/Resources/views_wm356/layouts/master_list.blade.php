<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin=""/>
    <link rel="dns-prefetch" href="//fonts.gstatic.com/"/>
    <link preload href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap"
          as="font" onload="this.onload=null;this.rel='stylesheet'" crossorigin=""/>
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" crossorigin=""
              rel="stylesheet"/>
    </noscript>


    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"/>
    <link rel="icon" type="image/png" sizes="32x32" href="{!! core()->imgurl($config->favicon,'img') !!}">
    <link rel="icon" type="image/x-icon" href="{!! core()->imgurl($config->favicon,'img') !!}">
    <link rel="apple-touch-icon" sizes="60x60" href="{!! core()->imgurl($config->favicon,'img') !!}">
    <meta name="apple-mobile-web-app-title" content="{{ ucwords($config->sitename) }}"/>
    <title>{{ ucwords($config->sitename) }} - {{ $config->title }}</title>
    <meta name="description" content="{{ $config->description }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="keywords" content="web.meta.keywords"/>

    <meta property="og:title" content="{{ ucwords($config->sitename) }}"/>
    <meta property="og:description"
          content="{{ $config->description }}"/>
    <meta property="og:locale" content="{{ config('app.locale') }}"/>
    <meta property="og:site_name" content="{{ ucwords($config->sitename) }}"/>
    <meta property="og:url" content="{{ url('') }}"/>
    <meta property="og:image" content="{{ url(core()->imgurl($config->logo,'img')) }}"/>

    <link rel="canonical" href=""/>

    <meta name="twitter:site" content="@twitter"/>
    <meta name="twitter:card" content="summary"/>
    <meta name="twitter:title" content="{{ ucwords($config->sitename) }}"/>
    <meta name="twitter:description"
          content="{{ $config->description }}"/>
    <meta name="twitter:image" content="{{ url(core()->imgurl($config->logo,'img')) }}"/>


    <link href="{!! core()->imgurl($config->favicon,'img') !!}"
          onload="this.onload=null;this.rel='icon'" crossorigin=""/>
    <noscript>
        <link rel="icon" href="{!! core()->imgurl($config->favicon,'img') !!}"/>
    </noscript>
    <meta name="msapplication-TileColor" content="#ffffff"/>
    <meta name="msapplication-TileImage" content="/assets/wm356/images/ms-icon-144x144.png"/>
    <meta name="theme-color" content="#ffffff"/>

    <meta name="format-detection" content="telephone=no"/>
    <link rel="stylesheet" href="/assets/wm356/css/style_wallet.css?v={{ date('YmdHis') }}"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link
            rel="stylesheet"
            href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@6.6.6/css/flag-icons.min.css"
    />
    <script type="text/javascript">
        window["gif64"] = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";
        window["Bonn"] = {
            boots: [],
            inits: [],
        };
    </script>
    @stack('styles')

    <style>
        .x-header {
            background: {{ ($config->wallet_navbar_color? $config->wallet_navbar_color :'#1d1d1d') }}     !important;
        }

        .x-footer.-ezl .-copy-right-container {
            background-color: {{ ($config->wallet_footer_color?$config->wallet_footer_color:'#255b48') }}     !important;
        }

        .swal2-html-container img {
            max-width: 100%;
            height: auto;
        }

        #account-actions-mobile .-left-wrapper, #account-actions-mobile .-right-wrapper {
            background: linear-gradient(180deg, {{ ($config->wallet_footer_exchange?$config->wallet_footer_exchange:'#0d0d0d') }}, {{ ($config->wallet_footer_exchange?$config->wallet_footer_exchange:'#000000') }});
        }

        #account-actions-mobile .-center-wrapper {
            background: linear-gradient(180deg, {{ ($config->wallet_footer_exchange?$config->wallet_footer_exchange:'#0d0d0d') }}, {{ ($config->wallet_footer_exchange?$config->wallet_footer_exchange:'#000000') }});
        }

        #account-actions-mobile .-fake-center-bg-wrapper svg path {
            fill: {{ ($config->wallet_footer_exchange?$config->wallet_footer_exchange:'url(#rectangleGradient)') }}



        }




    </style>

    @if($config->header_code)
        {!! $config->header_code !!}
    @endif
</head>

<body class="">

@if($config->seamless == 'Y')
    @include('wallet::layouts.header_wallet')
@else
    @include('wallet::layouts.header_wallet_single')
@endif
@yield('content')

@include('wallet::layouts.modal')

@include('wallet::layouts.footer')

<script src="https://js.pusher.com/7.2.0/pusher.min.js"></script>

<script></script>

{{--<script>--}}
{{--    Bonn.boots.push(function () {--}}
{{--        setTimeout(function () {--}}
{{--            $("#bankInfoModal").modal("show");--}}
{{--        }, 500);--}}
{{--    });--}}
{{--</script>--}}

<script>
    var IS_ANDROID = false;
    var IS_MOBILE = false;
</script>

<div class="myAlert-top alertcopy">
    <i class="fa-regular fa-circle-check"></i>
    <br>
    <strong>
        {{ __('app.home.copy') }} </strong>
</div>

{{--<script src="assets/wm356/web/ezl-wm-356/app.629ea432.js"></script>--}}


<script type="text/javascript">
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

<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js"></script>
<script src="{{ mix('assets/wm356/js/manifest.js') }}"></script>
<script src="{{ mix('assets/wm356/js/vendor.js') }}"></script>


<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
        crossorigin="anonymous"></script>
<script src="/assets/wm356/js/runtime.1ba6bf05.js?v=111"></script>
<script src="/assets/wm356/js/0.e84cf97a.js?v=1"></script>
<script src="/assets/wm356/js/1.9a969cca.js?v=1"></script>
<script src="/assets/wm356/web/ezl-wm-356/app.629ea432.js?v=1"></script>

{{--<script src="https://kit.fontawesome.com/1b817ed1d5.js?v=1" crossorigin="anonymous"></script>--}}
<script src="{{ mix('assets/wm356/js/app.js') }}" id="mainscript" baseUrl="{{ url()->to('/') }}"></script>
<script src="{{ asset('lang-').app()->getLocale() }}.js?v={{ date('Ymd') }}"></script>

<script src="{{ asset('assets/wm356/js/js_wallet.js?v='.time()) }}"></script>
{{--<script src="{{ asset('assets/wm356/js/js_wallet_fix.js?v='.time()) }}"></script>--}}

<script src="{{ asset('vendor/vex/js/vex.combined.js') }}"></script>
<script src="//cdn.jsdelivr.net/npm/jquery.marquee@1.6.0/jquery.marquee.min.js" type="text
/javascript"></script>

@stack('scripts')
<script type="text/javascript">


    @if(isset($notice_new[Route::currentRouteName()]['route']) === true)

    $(document).ready(function () {

        Swal.fire({
            html: '{!! $notice_new[Route::currentRouteName()]['msg'] !!}',
            focusConfirm: false,
            showCloseButton: true,
            showConfirmButton: false
        });

    });

    @endif
    const private_channel = '{{ env('APP_NAME') }}_members.{{ auth()->guard('customer')->user()->code }}';

    Echo.private(private_channel)
        .notification((notification) => {
            Toast.fire({
                icon: 'success',
                title: notification.message
            });
            $('.-btn-balance-normal').trigger('click');
        });


</script>
@stack('script')
<link rel="dns-prefetch" href="//cdnjs.cloudflare.com"/>
<link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/fontawesome.min.css"
        integrity="sha512-shT5e46zNSD6lt4dlJHb+7LoUko9QZXTGlmWWx0qjI9UhQrElRb+Q5DM7SVte9G9ZNmovz2qIaV7IWv0xQkBkw=="
        crossorigin="anonymous"
        onload="this.onload=null;this.rel='stylesheet'"
/>
<link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/solid.min.css"
        integrity="sha512-xIEmv/u9DeZZRfvRS06QVP2C97Hs5i0ePXDooLa5ZPla3jOgPT/w6CzoSMPuRiumP7A/xhnUBxRmgWWwU26ZeQ=="
        crossorigin="anonymous"
        onload="this.onload=null;this.rel='stylesheet'"
/>
<link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/regular.min.css"
        integrity="sha512-1yhsV5mlXC9Ve9GDpVWlM/tpG2JdCTMQGNJHvV5TEzAJycWtHfH0/HHSDzHFhFgqtFsm1yWyyHqssFERrYlenA=="
        crossorigin="anonymous"
        onload="this.onload=null;this.rel='stylesheet'"
/>

<noscript>
    <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/regular.min.css"
            integrity="sha512-1yhsV5mlXC9Ve9GDpVWlM/tpG2JdCTMQGNJHvV5TEzAJycWtHfH0/HHSDzHFhFgqtFsm1yWyyHqssFERrYlenA=="
            crossorigin="anonymous"
    />
    <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/solid.min.css"
            integrity="sha512-xIEmv/u9DeZZRfvRS06QVP2C97Hs5i0ePXDooLa5ZPla3jOgPT/w6CzoSMPuRiumP7A/xhnUBxRmgWWwU26ZeQ=="
            crossorigin="anonymous"
    />
    <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/fontawesome.min.css"
            integrity="sha512-shT5e46zNSD6lt4dlJHb+7LoUko9QZXTGlmWWx0qjI9UhQrElRb+Q5DM7SVte9G9ZNmovz2qIaV7IWv0xQkBkw=="
            crossorigin="anonymous"
    />
</noscript>
</body>
</html>


