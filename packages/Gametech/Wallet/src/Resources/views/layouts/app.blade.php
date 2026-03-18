<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">

<head>
    <meta charset="utf-8">
    <title>{{ ucwords($config->sitename) }} - {{ $config->title }}</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <link rel="icon" type="image/png" sizes="32x32" href="{!! core()->imgurl($config->favicon,'img') !!}">
    <meta name="description" content="{{ $config->description }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Scrollbar Custom CSS -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">

    <!-- Font Awesome JS -->
    <link href="https://kit-pro.fontawesome.com/releases/v5.15.3/css/pro.min.css" rel="stylesheet">
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@6.6.6/css/flag-icons.min.css"
    />
    <!-- AOS JS -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css"/>

    <link rel="stylesheet" href="{{ mix('css/web.css') }}">
    @stack('styles')


    <!-- Facebook shared -->
    <meta property="og:url" content=""/>
    <meta property="og:type" content="article"/>
    <meta property="og:title" content="{{ $config->title }}"/>
    <meta property="og:description" content="{{ $config->description }}"/>
    <meta property="og:image" content="{{ url(core()->imgurl($config->logo,'img')) }}"/>
    <meta name='robots' content='max-image-preview:large'/>

</head>

<body class="custom-theme">
<div id="app">
    <div class="wrapper">
        <!-- Sidebar  -->
        <div class="insidebarleft">
            <a href="{{ route('customer.home.index') }}">
                {!! core()->showImg($config->logo,'img','','','') !!}

            </a>
            <ul>
                <li>
                    <a href="{{ route('customer.home.index') }}">
                        <img src="images/icon/icon-home.png">
                        {{ __('app.login.home') }}
                    </a>
                </li>
                @if($config->pro_onoff == 'Y')
                    <li>
                        <a href="{{ route('customer.promotion.show') }}">
                            <img src="images/icon/icon-promotion.png">
                            {{ __('app.login.promotion') }}
                        </a>
                    </li>
                @endif
                <li>
                    <a target="_blank" href="{{ $config->linelink }}">
                        <img src="images/icon/icon-contact.png">
                        {{ __('app.login.contact') }}
                    </a>
                </li>
                <li>
                    <a href="#" data-toggle="dropdown" aria-expanded="false">
                        &nbsp;<span class="fi fi-{{ $lang }} fis"></span>&nbsp;&nbsp;&nbsp;&nbsp;
                        {{ __('app.login.language') }}
                        <div class="dropdown-menu">
                            <a class="dropdown-item" style="color:black" href="{{ route('customer.home.lang', ['lang' => 'th']) }}">TH</a>
                            <a class="dropdown-item" style="color:black" href="{{ route('customer.home.lang', ['lang' => 'kh']) }}">KH</a>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
        <div class="overlaysidebar"></div>
        <div class="x-hamburger js-hamburger-toggle sidebarCollapse">
            <span></span>
            <span></span>
            <span></span>
        </div>


        <!-- DIV SECCOND BAR -->
        <div class="second-bar">
            <div class="ctscb">
                <div class="leftscb">
                    <a href="{{ route('customer.home.index') }}">
                        {!! core()->showImg($config->logo,'img','','','') !!}
                    </a>
                </div>
                <div class="rightscb">
                    <ul>
                        <li>
                            <a href="{{ route('customer.home.index') }}">
                                <img src="images/icon/icon-home.png">
                                {{ __('app.login.home') }}
                            </a>
                        </li>
                        @if($config->pro_onoff == 'Y')
                            <li>
                                <a href="{{ route('customer.promotion.show') }}">
                                    <img src="images/icon/icon-promotion.png">
                                    {{ __('app.login.promotion') }}
                                </a>
                            </li>
                        @endif
                        <li>
                            <a target="_blank" href="{{ $config->linelink }}">
                                <img src="images/icon/icon-contact.png">
                                {{ __('app.login.contact') }}
                            </a>
                        </li>
                        <li>
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <span class="fi fi-{{ $lang }} fis" style="width:1.9em;line-height: 2.8em"></span><br>
                                {{ __('app.login.language') }}
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" style="color:black" href="{{ route('customer.home.lang', ['lang' => 'th']) }}">TH</a>
                                    <a class="dropdown-item" style="color:black" href="{{ route('customer.home.lang', ['lang' => 'kh']) }}">KH</a>
                                </div>
                            </a>
                        </li>
                    </ul>

                </div>
                <div class="toploginbox">
                    <a href="{{ route('customer.home.index') }}">
                        <button class="btn">
                            {{ __('app.login.login') }}
                        </button>
                    </a>
                    <a href="{{ route('customer.session.store') }}">
                        <button class="btn">
                            {{ __('app.login.register') }}
                        </button>
                    </a>

                </div>
            </div>
        </div>
        <!-- DIV SECCOND BAR -->


        @yield('content')


    </div>
</div>
<footer class="x-footer -anon mt-auto bg-black">
    <div class="copyright">
        COPYRIGHT©2022, GAMETECH
    </div>
</footer>


<div class="overlay"></div>
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
<script src="{{ mix('js/manifest.js') }}"></script>
<script src="{{ mix('js/vendor.js') }}"></script>
<script src="{{ mix('js/app.js') }}" id="mainscript" baseUrl="{{ url()->to('/') }}"></script>
@stack('scripts')
<script src="{{ asset('js/js.js?'.time()) }}"></script>
</body>
</html>


