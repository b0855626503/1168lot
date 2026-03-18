<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="UTF-8">
    <title>{{ ucwords($config->sitename) }} - {{ $config->title }}</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ core()->imgurl($config->favicon,'img') }}">
    <meta name="description" content="{{ $config->description }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Prompt&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.7.2/css/all.css"
          integrity="sha384-6jHF7Z3XI3fF4XZixAuSu0gGKrXwoX/w3uFPxC56OtjChio7wtTGJWRW53Nhx6Ev" crossorigin="anonymous">
    @stack('styles')
    <link rel="stylesheet" href="{{ mix('css/web.css') }}">

    <style>
        .nav-top {
            background: {{ ($config->wallet_navbar_color?$config->wallet_navbar_color:'#6f0000') }}       !important;
        }

        .nav-footer {
            background: {{ ($config->wallet_footer_color?$config->wallet_footer_color:'#6f0000') }}       !important;
        }

        .custom-theme {
            background: linear-gradient(45deg, {{ ($config->wallet_body_start_color?$config->wallet_body_start_color:'#200122') }} 10%, {{ ($config->wallet_body_stop_color?$config->wallet_body_stop_color:'#6f0000') }} 90%) !important;
        }

        .exchange {
            background: {{ ($config->wallet_footer_exchange?$config->wallet_footer_exchange:'#6f0000') }}       !important;
        }

        .exchange-single {
            background: {{ ($config->wallet_footer_exchange?$config->wallet_footer_exchange:'#6f0000') }}       !important;
        }

        a.active, a.active i, a.active p {
            color: {{ ($config->wallet_footer_active?$config->wallet_footer_active:'#6f0000') }}       !important;
        }

    </style>
    @yield('css')

    @if($config->header_code)
        {!! $config->header_code !!}
    @endif
</head>

<body class="layout-navbar-fixed custom-theme">


<div id="app" class="bg-login">

    <div class="wrapper">

        <nav class="navbar navbar-expand border-bottom nav-header nav-top">
            <div class="container">
                <div class="row w-100">
                    <div class="col-3 w-40">@yield('back')</div>
                    {!! core()->showImg($config->logo,'img','','','img-top') !!}
                    <div class="col-1 offset-8">
                        <a href="{{ route('customer.session.destroy') }}"
                           class="nav-link text-light p-2 signout-btn mx-auto hand-point">
                            <i class="fal fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div style="margin-top: 6rem;margin-bottom: 6rem;">
            @yield('content')
        </div>

        @include('wallet::layouts.footer')

    </div>
</div>


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

<script type="text/javascript" src="{{ mix('js/manifest.js') }}"></script>
<script type="text/javascript" src="{{ mix('js/vendor.js') }}"></script>
<script type="text/javascript" src="{{ mix('js/app.js') }}"></script>
<script type="module" id="mainscript" baseUrl="{{ url()->to('/') }}" src="{{ mix('js/web.js') }}"></script>
<script type="module" src="{{ asset('assets/ui/js/ui.js') }}"></script>
<script type="text/javascript">
    @if(isset($notice[Route::currentRouteName()]['route']) == true)
    $(document).ready(function () {
        Swal.fire(
            'โปรดทราบ',
            '{{ $notice[Route::currentRouteName()]['msg'] }}',
            'info'
        );

    });
    @endif
</script>

@stack('scripts')
</body>
</html>
