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

    <link href="https://kit-pro.fontawesome.com/releases/v5.15.3/css/pro.min.css" rel="stylesheet">
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
    @if($config->header_code)
        {!! $config->header_code !!}
    @endif
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
{{--                @if(request()->routeIs('customer.credit.*'))--}}
{{--                    <li>--}}
{{--                        <a href="{{ route('customer.credit.game.index') }}">--}}
{{--                            <i class="fas fa-dice-d6"></i>--}}
{{--                            {{ __('app.home.playgame') }}--}}
{{--                        </a>--}}
{{--                    </li>--}}
{{--                @else--}}
{{--                    <li>--}}
{{--                        <a href="{{ route('customer.home.index') }}">--}}
{{--                            <i class="fas fa-dice-d6"></i>--}}
{{--                            {{ __('app.home.playgame') }}--}}
{{--                        </a>--}}
{{--                    </li>--}}
{{--                @endif--}}
                <li>
                    <a href="{{ route('customer.profile.index') }}">
                        <i class="fas fa-user-alt"></i>
                        {{ __('app.home.profile') }}
                    </a>
                </li>
                <li>
                    <a href="{{ route('customer.profile.changemain') }}">
                        <i class="fas fa-user-lock"></i>
                        {{ __('app.home.changepass') }}
                    </a>
                </li>
                @if($config->money_tran_open === 'Y')
                    <li>
                        <a href="{{ route('customer.money.index') }}">
                            <i class="fas fa-hands-helping"></i>
                            {{ __('app.home.transfer') }}
                        </a>
                    </li>
                @endif
                @if(request()->routeIs('customer.credit.*'))
                <li>
                    <a href="{{ route('customer.contributor.index') }}">
                        <i class="fas fa-hands-helping"></i>
                        {{ __('app.home.suggest') }}
                    </a>
                </li>
                @endif
                @if(!request()->routeIs('customer.credit.*'))
                    @if($config->wheel_open === 'Y')
                        <li>
                            <a href="{{ route('customer.spin.index') }}">
                                <i class="fas fa-bullseye"></i>
                                {{ __('app.home.wheel') }}
                            </a>
                        </li>
                    @endif
                @endif
                @if(!request()->routeIs('customer.credit.*'))
                    @if($config->freecredit_open === 'Y')
                        <li>
                            <a href="{{ route('customer.credit.index') }}">
                                <img src="/images/icon/return.png">
                                {{ __('app.home.freecredit') }}
                            </a>
                        </li>
                    @endif
                @else
                        <li>
                            <a href="{{ route('customer.home.index') }}">
                                <img src="/images/icon/coin.png">
                                {{ __('app.home.credit') }}
                            </a>
                        </li>
                @endif
                @if(request()->routeIs('customer.credit.*'))
                    <li>
                        <a href="{{ route('customer.credit.history.index') }}">
                            <i class="far fa-history"></i>
                            {{ __('app.home.history') }}
                        </a>
                    </li>
                @else
                    <li>
                        <a href="{{ route('customer.history.index') }}">
                            <i class="far fa-history"></i>
                            {{ __('app.home.history') }}
                        </a>
                    </li>
                @endif
{{--                @if($config->pro_onoff === 'Y')--}}
{{--                    <li>--}}
{{--                        <a href="{{ route('customer.promotion.index') }}">--}}
{{--                            <i class="far fa-gift"></i>--}}
{{--                            {{ __('app.home.promotion') }}--}}
{{--                        </a>--}}
{{--                    </li>--}}
{{--                @endif--}}
                <li>
                    <a href="{{ route('customer.session.destroy') }}">
                        <i class="far fa-sign-out"></i>
                        {{ __('app.home.logout') }}
                    </a>
                </li>
{{--                    <li>--}}
{{--                        <a href="#" data-toggle="dropdown" aria-expanded="false">--}}
{{--                            &nbsp;<span class="fi fi-{{ $lang }} fis"></span>&nbsp;&nbsp;&nbsp;&nbsp;--}}
{{--                            {{ __('app.login.language') }}--}}
{{--                            <div class="dropdown-menu">--}}
{{--                                <a class="dropdown-item" style="color:black" href="{{ route('customer.home.lang', ['lang' => 'th']) }}">TH</a>--}}
{{--                                <a class="dropdown-item" style="color:black" href="{{ route('customer.home.lang', ['lang' => 'kh']) }}">KH</a>--}}
{{--                            </div>--}}
{{--                        </a>--}}
{{--                    </li>--}}
            </ul>
        </div>
        <div class="overlaysidebar"></div>
        <div class="x-hamburger js-hamburger-toggle sidebarCollapse">
            <span></span>
            <span></span>
            <span></span>
        </div>


        <!-- DIV TOP LOGIN -->
        @if(request()->routeIs('customer.credit.*'))
            <profilefree></profilefree>
        @else
            <profile></profile>
        @endif

        <!-- DIV TOP LOGIN -->


        <!-- DIV SECCOND BAR -->
        <div class="second-bar">
            <div class="ctscb">
                <div class="leftscb">
                    @if(request()->routeIs('customer.credit.*'))
                        <a href="{{ route('customer.credit.index') }}">
                            {!! core()->showImg($config->logo,'img','','','') !!}
                        </a>
                    @else
                    <a href="{{ route('customer.home.index') }}">
                        {!! core()->showImg($config->logo,'img','','','') !!}
                    </a>
                    @endif
                </div>
                <div class="rightscb">
                    <ul>
                        @if(request()->routeIs('customer.credit.*'))
                            <li>
                                <a href="{{ route('customer.credit.game.index') }}">
                                    <i class="fas fa-dice-d6"></i>
                                    {{ __('app.home.playgame') }}
                                </a>
                            </li>
                        @else
                            <li>
                                <a href="{{ route('customer.home.index') }}">
                                    <i class="fas fa-dice-d6"></i>
                                    {{ __('app.home.playgame') }}
                                </a>
                            </li>
                        @endif
                        <li>
                            <a href="{{ route('customer.profile.index') }}">
                                <i class="fas fa-user-alt"></i>
                                {{ __('app.home.profile') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('customer.profile.changemain') }}">
                                <i class="fas fa-user-lock"></i>
                                {{ __('app.home.changepass') }}
                            </a>
                        </li>
                        @if($config->money_tran_open === 'Y')
                            <li>
                                <a href="{{ route('customer.money.index') }}">
                                    <i class="fas fa-hands-helping"></i>
                                    {{ __('app.home.transfer') }}
                                </a>
                            </li>
                        @endif
                        <li>
                            <a href="{{ route('customer.contributor.index') }}">
                                <i class="fas fa-hands-helping"></i>
                                {{ __('app.home.suggest') }}
                            </a>
                        </li>
                        {{--                        @if(!request()->routeIs('customer.credit.*'))--}}
                        @if($config->wheel_open === 'Y')
                            <li>
                                <a href="{{ route('customer.spin.index') }}">
                                    <i class="fas fa-bullseye"></i>
                                    {{ __('app.home.wheel') }}
                                </a>
                            </li>
                        @endif
                        {{--                        @endif--}}
                        @if(!request()->routeIs('customer.credit.*'))
                        @if($config->freecredit_open === 'Y')
                            <li>
                                <a href="{{ route('customer.credit.index') }}">
                                    <img src="/images/icon/return.png">
                                    {{ __('app.home.freecredit') }}
                                </a>
                            </li>
                        @endif
                      @else
                                <li>
                                    <a href="{{ route('customer.home.index') }}">
                                        <img src="/images/icon/coin.png">
                                        {{ __('app.home.credit') }}
                                    </a>
                                </li>
                       @endif
                        @if(request()->routeIs('customer.credit.*'))
                            <li>
                                <a href="{{ route('customer.credit.history.index') }}">
                                    <i class="far fa-history"></i>
                                    {{ __('app.home.history') }}
                                </a>
                            </li>
                        @else
                            <li>
                                <a href="{{ route('customer.history.index') }}">
                                    <i class="far fa-history"></i>
                                    {{ __('app.home.history') }}
                                </a>
                            </li>
                        @endif
                        @if($config->pro_onoff === 'Y')
                            <li>
                                <a href="{{ route('customer.promotion.index') }}">
                                    <i class="far fa-gift"></i>
                                    {{ __('app.home.promotion') }}
                                </a>
                            </li>
                        @endif
                            <li>
                                <a href="{{ $config->linelink }}" target="_blank">
                                    <i class="fab fa-line"></i>
                                    {{ __('app.home.contact') }}
                                </a>
                            </li>
                        <li>
                            <a href="{{ route('customer.session.destroy') }}">
                                <i class="far fa-sign-out"></i>
                                {{ __('app.home.logout') }}
                            </a>
                        </li>
{{--                            <li>--}}
{{--                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">--}}
{{--                                    <span class="fi fi-{{ $lang }} fis" style="width:1.9em;line-height: 2.8em"></span><br>--}}
{{--                                    {{ __('app.login.language') }}--}}
{{--                                    <div class="dropdown-menu">--}}
{{--                                        <a class="dropdown-item" style="color:black" href="{{ route('customer.home.lang', ['lang' => 'th']) }}">TH</a>--}}
{{--                                        <a class="dropdown-item" style="color:black" href="{{ route('customer.home.lang', ['lang' => 'kh']) }}">KH</a>--}}
{{--                                    </div>--}}
{{--                                </a>--}}
{{--                            </li>--}}
                    </ul>

                </div>
                <div class="toploginbox">
                    @if(request()->routeIs('customer.credit.*'))
                        <profilefree-min></profilefree-min>
                    @else
                        <profile-min></profile-min>
                    @endif

                    <div class="flexcenter d-none d-md-flex">
                        <div>
                            <a href="{{ route('customer.topup.index') }}">
                                <button class="btn blue">
                                    {{ __('app.home.refill') }}
                                </button>
                            </a>
                            @if(request()->routeIs('customer.credit.*'))
                                <a href="{{ route('customer.credit.withdraw.index') }}">
                                    <button class="btn gold">
                                        {{ __('app.home.withdraw') }}
                                    </button>
                                </a>
                            @else
                                <a href="{{ route('customer.withdraw.index') }}">
                                    <button class="btn gold">
                                        {{ __('app.home.withdraw') }}
                                    </button>
                                </a>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DIV SECCOND BAR -->

        @if(isset($notice[Route::currentRouteName()]['route']) === true)
            <!-- SECTION01 -->
            <section class="section01">
                <div class="containalert">
                    <div class="newsboxhead" data-animatable="fadeInUp" data-delat="200">
                        <div class="-icon-container">
                            <i class="fas fa-volume-up"></i>
                        </div>
                        <span> {{ $notice[Route::currentRouteName()]['msg'] }} </span>
                    </div>
                </div>
            </section>
            <!-- SECTION01 -->
            <hr class="x-hr-border-glow my-0">
        @endif
        @yield('content')


    </div>
</div>

<footer class="x-footer -anon mt-auto bg-black">

    <div class="copyright mt-0">
        COPYRIGHT©2022, GAMETECH
    </div>
</footer>
<div class="myAlert-top alertcopy">
    <i class="fal fa-check-circle"></i>
    <br>
    <strong>
        {{ __('app.home.copy') }} </strong>
</div>
<div class="x-button-actions" id="account-actions-mobile">
    <div class="-outer-wrapper">
        <div class="-left-wrapper">
      <span class="-item-wrapper">
        <span class="-ic-img">
          <span class="-text d-block">{{ __('app.home.refill') }}</span>
          <a href="{{ route('customer.topup.index') }}">
            <img src="/images/icon/deposit.png">
          </a>
        </span>
      </span>
            @if(request()->routeIs('customer.credit.*'))
                <span class="-item-wrapper">
        <span class="-ic-img">
          <span class="-text d-block">{{ __('app.home.withdraw') }}</span>
          <a href="{{ route('customer.credit.withdraw.index') }}">
            <img src="/images/icon/withdraw.png">
          </a>
        </span>
      </span>
            @else
                <span class="-item-wrapper">
        <span class="-ic-img">
          <span class="-text d-block">{{ __('app.home.withdraw') }}</span>
          <a href="{{ route('customer.withdraw.index') }}">
            <img src="/images/icon/withdraw.png">
          </a>
        </span>
      </span>
            @endif

        </div>
        @if(request()->routeIs('customer.credit.*'))
            <a href="{{ route('customer.credit.game.index') }}">
      <span class="-center-wrapper js-footer-lobby-selector js-menu-mobile-container">
        <div class="-selected">
          <img src="/images/icon/menu.png">
          <h5>{{ __('app.home.playgame') }}</h5>
        </div>
      </span>
            </a>
        @else
            <a href="{{ route('customer.home.index') }}">
      <span class="-center-wrapper js-footer-lobby-selector js-menu-mobile-container">
        <div class="-selected">
          <img src="/images/icon/menu.png">
          <h5>{{ __('app.home.playgame') }}</h5>
        </div>
      </span>
            </a>
        @endif
        <div class="-fake-center-bg-wrapper">
            <svg viewBox="-10 -1 30 12">
                <defs>
                    <linearGradient id="rectangleGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#225db9"></stop>
                        <stop offset="100%" stop-color="#041d4a"></stop>
                    </linearGradient>
                </defs>
                <path d="M-10 -1 H30 V12 H-10z M 5 5 m -5, 0 a 5,5 0 1,0 10,0 a 5,5 0 1,0 -10,0z"></path>
            </svg>
        </div>
        <div class="-right-wrapper">
      <span class="-item-wrapper">
        <span class="-ic-img">
          <span class="-text d-block">{{ __('app.home.promotion') }}</span>
          <a href="{{ route('customer.promotion.index') }}">
            <img src="/images/icon/tab_promotion.png">
          </a>
        </span>
      </span>
            <span class="-item-wrapper">
        <span class="-ic-img">
          <span class="-text d-block">{{ __('app.home.contact') }}</span>
          <a target="_blank" href="
          {{ $config->linelink }}">
            <img src="/images/icon/support-mobile.webp">
          </a>
        </span>
      </span>
        </div>
        <div class="-fully-overlay js-footer-lobby-overlay"></div>
    </div>
</div>
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
<script src="{{ asset('lang-').app()->getLocale() }}.js?time={{ time() }}"></script>
<script type="text/javascript">



    @if(isset($notice_new[Route::currentRouteName()]['route']) === true)

    $(document).ready(function () {

        Swal.fire({
            html : '{!! $notice_new[Route::currentRouteName()]['msg'] !!}',
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
        });


</script>
</body>
</html>


