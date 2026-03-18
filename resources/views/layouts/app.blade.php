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
    <link rel="icon" type="image/png" sizes="32x32" href="{!! core()->imgurl($webconfig->favicon,'img') !!}">
    <link rel="icon" type="image/x-icon" href="{!! core()->imgurl($webconfig->favicon,'img') !!}">
    <link rel="apple-touch-icon" sizes="60x60" href="{!! core()->imgurl($webconfig->favicon,'img') !!}">
    <meta name="apple-mobile-web-app-title" content="{{ ucwords($webconfig->sitename) }} - {{ $webconfig->title }}"/>
    <title>{{ ucwords($webconfig->sitename) }} - {{ $webconfig->title }}</title>
    <meta name="description" content="{{ $webconfig->description }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="keywords"
          content="slot, casino, pgslot, joker, บาคาร่าออนไลน์, พนันออนไลน์, เว็บพนันออนไลน์, คาสิโนออนไลน์, บาคาร่า, บอลออนไลน์, สล็อต, ค่าน้ำดีที่สุด, เว็บพนัน, เกมสล็อต, นักพนัน"/>

    <meta property="og:title" content="{{ ucwords($webconfig->sitename) }} - {{ $webconfig->title }}"/>
    <meta property="og:description"
          content="{{ $webconfig->description }}"/>
    <meta property="og:locale" content="{{ config('app.locale') }}"/>
    <meta property="og:site_name" content="{{ ucwords($webconfig->sitename) }}"/>
    <meta property="og:url" content="{{ url('') }}"/>
    <meta property="og:image" content="{{ url(core()->imgurl($webconfig->logo,'img')) }}"/>

    <link rel="canonical" href=""/>

    <meta name="twitter:site" content="@twitter"/>
    <meta name="twitter:card" content="summary"/>
    <meta name="twitter:title" content="{{ ucwords($webconfig->sitename) }} - {{ $webconfig->title }}"/>
    <meta name="twitter:description"
          content="{{ $webconfig->description }}"/>
    <meta name="twitter:image" content="{{ url(core()->imgurl($webconfig->logo,'img')) }}"/>


    <link preload href="{!! core()->imgurl($webconfig->favicon,'img') !!}" as="style"
          onload="this.onload=null;this.rel='icon'" crossorigin=""/>
    <noscript>
        <link rel="icon" href="{!! core()->imgurl($webconfig->favicon,'img') !!}"/>
    </noscript>
    <meta name="msapplication-TileColor" content="#ffffff"/>
    <meta name="msapplication-TileImage" content="assets/wm356/images/ms-icon-144x144.png"/>
    <meta name="theme-color" content="#ffffff"/>

    <meta name="format-detection" content="telephone=no"/>
    <link rel="stylesheet" href="assets/wm356/css/style.css?v=3"/>
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
    @stack('script')

    <style>
        .x-header {
            background: {{ ($webconfig->wallet_navbar_color? $webconfig->wallet_navbar_color :'#1d1d1d') }}    !important;
        }

        .x-footer.-ezl .-copy-right-container {
            background-color: {{ ($webconfig->wallet_footer_color?$webconfig->wallet_footer_color:'#255b48') }}    !important;
        }
        {{--body, html {--}}
        {{--    height: 100%;--}}
        {{--    font-family: FC Iconic Text, Helvetica Neue, Helvetica, Arial, sans-serif;--}}
        {{--    background-color: {{ ($webconfig->wallet_body_start_color? $webconfig->wallet_body_start_color :'#0f0f0f') }} !important;--}}
        {{--}--}}
        {{--.x-provider-category.-provider_casinos {--}}
        {{--    background: {{ ($webconfig->wallet_body_start_color? $webconfig->wallet_body_start_color :'#0f0f0f') }} !important;--}}
        {{--}--}}

        {{--#main__content {--}}
        {{--    background: {{ ($webconfig->wallet_body_start_color? $webconfig->wallet_body_start_color :'#0f0f0f') }} !important;--}}
        {{--}--}}
    </style>

    @if($webconfig->header_code)
        {!! $webconfig->header_code !!}
    @endif

{{--    @laravelPWA--}}
</head>

<body class="">

<nav class="x-header js-header-selector navbar navbar-expand-lg -anon">
    <div class="container-fluid -inner-container">
        <div class="">
            <button type="button" class="btn bg-transparent p-0 x-hamburger" data-toggle="modal"
                    data-target="#themeSwitcherModal">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <div id="headerBrand">
            <a class="navbar-brand" href="{{ route('customer.session.index') }}">
                <img alt="{{ $webconfig->description }}" class="-logo -default img-fluid" width="440"
                     height="104" src="{{ url(core()->imgurl($webconfig->logo,'img')) }}"/>
                <img alt="{{ $webconfig->description }}" class="-logo -invert img-fluid" width="440"
                     height="104" src="{{ url(core()->imgurl($webconfig->logo,'img')) }}"/>
            </a>
        </div>

        <div class="x-menu">
            <div class="-menu-container">

            </div>
        </div>

        <div id="headerContent">
            <div class="d-flex">
                <a href="{{ $webconfig->linelink }}" class="x-header-btn-support -in-anon" target="_blank"
                   rel="noreferrer nofollow">
                    <picture>
                        <source type="image/webp" srcset="/assets\wm356\web\ezl-wm-356\img\ic-line-support.webp?v=1"/>
                        <source type="image/png?v=2" srcset="/assets\wm356\web\ezl-wm-356\img\ic-line-support.png?v=1"/>
                        <img alt="{{ $webconfig->description }}" class="img-fluid -ic" loading="lazy" fetchpriority="low"
                             width="120" height="39" src="/assets\wm356\web\ezl-wm-356\img\ic-line-support.png?v=1"/>
                    </picture>
                    <picture>
                        <source type="image/webp"
                                srcset="/assets\wm356\web\ezl-wm-356\img\ic-line-support-mobile.webp?v={{ time() }}"/>
                        <source type="image/png"
                                srcset="/assets\wm356\web\ezl-wm-356\img\ic-line-support-mobile.png?v={{ time() }}"/>
                        <img alt="{{ $webconfig->description }}" class="img-fluid -ic -mobile" loading="lazy"
                             fetchpriority="low"
                             width="28" height="28"
                             src="/assets\wm356\web\ezl-wm-356\img\ic-line-support-mobile.png?v={{ time() }}"/>
                    </picture>
                </a>

                <a href="{{ route('customer.session.store') }}" class="-btn-header-login btn mr-1 mr-sm-2">
                    {{ __('app.login.register') }}
                </a>

                <a href="{{ route('customer.session.index') }}" class="-btn-header-login btn">
                    {{ __('app.login.login') }}
                </a>
            </div>
        </div>
    </div>
</nav>


@yield('content')

<footer class="x-footer -ezl -anon">
    <div class="-inner-wrapper lazyload x-bg-position-center"
         data-bgset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/footer-inner-bg.png">
        <div class="container -inner-title-wrapper">
            {!! $webconfig->content_detail !!}
        </div>


    </div>

    <div class="text-center -copy-right-container">
        <p class="mb-0 -copy-right-text">
            Copyright © 2023 {{ $webconfig->sitename }}. All Rights Reserved.
        </p>
    </div>
</footer>




{{--<script src="https://js.pusher.com/7.2.0/pusher.min.js"></script>--}}

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


{{--<script src="{{ mix('assets/wm356/js/manifest.js') }}"></script>--}}
<script src="{{ mix('assets/wm356/js/vendor.js') }}"></script>

<script src="assets/wm356/js/runtime.1ba6bf05.js?v=5"></script>
<script src="assets/wm356/js/0.e84cf97a.js?v=1"></script>
<script src="assets/wm356/js/1.9a969cca.js?v=1"></script>
<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
        crossorigin="anonymous"></script>
<script src="assets/wm356/web/ezl-wm-356/app.629ea432.js?v=1"></script>
<script src="{{ mix('assets/wm356/js/app.js') }}" id="mainscript" baseUrl="{{ url()->to('/') }}"></script>
{{--<script src="{{ mix('assets/wm356/js/vue.js') }}" id="mainscript" baseUrl="{{ url()->to('/') }}"></script>--}}
@stack('scripts')
<script src="{{ asset('lang-').app()->getLocale() }}.js?time={{ time() }}"></script>

{{--@stack('scripts')--}}
{{--<script src="{{ asset('js/js.js?'.time()) }}"></script>--}}
</body>
</html>


