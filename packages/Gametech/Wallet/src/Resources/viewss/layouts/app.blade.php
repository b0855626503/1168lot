<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="UTF-8">
    <title>{{ ucwords($config->sitename) }} - {{ $config->title }}</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <link rel="icon" type="image/png" sizes="32x32" href="{!! core()->imgurl($config->favicon,'img') !!}">
    <meta name="description" content="{{ $config->description }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Prompt&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.7.2/css/all.css"
          integrity="sha384-6jHF7Z3XI3fF4XZixAuSu0gGKrXwoX/w3uFPxC56OtjChio7wtTGJWRW53Nhx6Ev" crossorigin="anonymous">

    <link rel="stylesheet" href="{{ asset('assets/ui/css/ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/default.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ mix('css/web.css') }}">
    <style>
        .nav-top {
            background: {{ ($config->wallet_navbar_color ?? '#6f0000') }}     !important;
        }

        .nav-footer {
            background: {{ ($config->wallet_footer_color ?? '#6f0000') }}     !important;
        }

        .custom-theme {
            background: linear-gradient(45deg, {{ ($config->wallet_body_start_color ?? '#200122') }} 10%, {{ ($config->wallet_body_stop_color ?? '#6f0000') }} 90%) !important;
        }

        .exchange {
            background: {{ ($config->wallet_footer_exchange ?? '#6f0000') }}     !important;
        }

        a.active, a.active i, a.active p {
            color: {{ ($config->wallet_footer_active ?? '#6f0000') }}     !important;
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

        <nav class=" navbar navbar-expand border-bottom nav-header nav-top">
            <div class="container">
                <div class="row w-100">
                    <div class="col-3 h-40">&nbsp;</div>
                    {!! core()->showImg($config->logo,'img','','','img-top') !!}
                    <div class="col-1 offset-8">&nbsp;</div>
                </div>
            </div>
        </nav>

        <div style="margin-top: 6rem;margin-bottom: 6rem;">
            @yield('content')
        </div>


        <div class="navigation nav-footer">
            <div class="container">
                <div class="row">
                    <div class="col-md-8 offset-md-2">
                        <div class="navigation-nav mt-2">
                            <div class="list-inline-item d-flex align-items-end text-center">
                                <a href="{{ route('customer.home.index') }}" class="active"><i
                                        class="fa fa-home mb-0"></i><br>หน้าแรก</a>
                            </div>


                            <div class="list-inline-item d-flex align-items-end text-center">
                                <a href="{{ route('customer.session.store') }}"><i class="fa fa-user mb-0"></i><br>
                                    สมัคร</a>
                            </div>

                            @if($config->seamless == 'N')
                                <div class="list-inline-item d-flex align-items-end text-center">
                                    <a href="{{ route('customer.home.download') }}">
                                        <i class="fa fa-download m-0"></i><br>ดาวน์โหลด</a>
                                </div>
                            @endif


                                @if($config->pro_onoff == 'Y')
                                    <div class="list-inline-item d-flex align-items-end text-center">
                                        <a href="{{ route('customer.promotion.show') }}">
                                            <i class="fa fa-download m-0"></i><br>โปรโมชั่น</a>
                                    </div>
                                @endif



                            <div class="list-inline-item d-flex align-items-end text-center">
                                <a target="_blank" href="{{ $config->linelink }}">
                                    <i class="fa fa-comments mb-0"></i>
                                    <br>ติดต่อ </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


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

{{--<script src="{{ asset('assets/main/js/manifest.js') }}" data-pagespeed-no-defer></script>--}}
{{--<script src="{{ asset('assets/main/js/vendor.js') }}" data-pagespeed-no-defer></script>--}}
{{--<script baseUrl="{{ url()->to('/') }}" src="{{ asset('assets/main/js/app.js') }}" data-pagespeed-no-defer></script>--}}
<script type="text/javascript" src="{!! mix('js/manifest.js') !!}"></script>
<script type="text/javascript" src="{{ mix('js/vendor.js') }}"></script>
<script type="text/javascript" src="{{ mix('js/app.js') }}"></script>
<script type="module" id="mainscript" baseUrl="{{ url()->to('/') }}" src="{{ mix('js/web.js') }}"></script>
@stack('scripts')
</body>
</html>
