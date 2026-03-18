<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">

<head>
    <meta charset="utf-8">
    <title>{{ ucwords($config->sitename) }} - {{ $config->title }}</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <link rel="icon" type="image/png" sizes="32x32" href="{!! core()->imgurl($config->favicon,'img') !!}">
    <meta name="description" content="{{ $config->description }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <link rel="stylesheet" type="text/css" href="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>

    <!-- Scrollbar Custom CSS -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">

    <!-- Font Awesome JS -->
    <link href="https://kit-pro.fontawesome.com/releases/v5.15.3/css/pro.min.css" rel="stylesheet">

    <!-- AOS JS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Swiper -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css"/>

    <!-- AOS JS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    @stack('styles')
    <link rel="stylesheet" href="{{ mix('css/web.css') }}">

    <!-- Facebook shared -->
    <meta property="og:url" content=""/>
    <meta property="og:type" content="article"/>
    <meta property="og:title" content="{{ $config->title }}"/>
    <meta property="og:description" content="{{ $config->description }}"/>
    <meta property="og:image" content="img"/>
    <meta name='robots' content='max-image-preview:large'/>

</head>

<body>
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
                        <i class="fas fa-dice-d6"></i>
                        {{ __('app.home.playgame') }}
                    </a>
                </li>
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
                @if($config->freecredit_open === 'Y')
                    <li>
                        <a href="{{ route('customer.credit.index') }}">
                            <img src="images/icon/return.png">
                            {{ __('app.home.cashback') }}
                        </a>
                    </li>
                @endif
                <li>
                    <a href="{{ route('customer.history.index') }}">
                        <i class="far fa-history"></i>
                        {{ __('app.home.history') }}
                    </a>
                </li>
                @if($config->pro_onoff === 'Y')
                    <li>
                        <a href="{{ route('customer.promotion.index') }}">
                            <i class="far fa-gift"></i>
                            {{ __('app.home.promotion') }}
                        </a>
                    </li>
                @endif
                <li>
                    <a href="../">
                        <i class="far fa-sign-out"></i>
                        {{ __('app.home.logout') }}
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


        <!-- DIV TOP LOGIN -->
        <div class="toplogin">
            <div class="containtoplogin">
                <div class="topdetaillogin">
                    GAMETECH
                </div>
                <div class="toploginbox">

                    <div class="flexcenter mr-3">
                        <span class="telheader"><img src="/images/icon/goldprofile.png">0881122334</span>
                    </div>

                    <div class="flexcenter mr-2">
                        <div class="-balance-container">
                            <div class="-user-balance js-user-balance f-sm-6 f-7 ">
                                <div class="-inner-box-wrapper">
                                    <img class="img-fluid -ic-coin" src="/images/icon/coin.png" alt="customer image"
                                         width="26" height="21">
                                    <span id="customer-balance"><span class="text-green-lighter">500,000.00</span>
                                    </span>
                                </div>
                                <button type="button" class="-btn-balance" id="btn-customer-balance-reload">
                                    <i class="fas fa-sync-alt f-9 reloadcredit"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flexcenter mr-1">
                        <a href="{{ route('customer.topup.index') }}">
                            <button class="btn blue">
                                {{ __('app.home.refill') }}
                            </button>
                        </a>
                    </div>
                    <div class="flexcenter">
                        <a href="{{ route('customer.withdraw.index') }}">
                            <button class="btn gold">
                                {{ __('app.home.withdraw') }}
                            </button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- DIV TOP LOGIN -->


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
                                <i class="fas fa-dice-d6"></i>
                                {{ __('app.home.playgame') }}
                            </a>
                        </li>
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
                        @if($config->freecredit_open === 'Y')
                            <li>
                                <a href="{{ route('customer.credit.index') }}">
                                    <img src="/images/icon/return.png">
                                    {{ __('app.home.cashback') }}
                                </a>
                            </li>
                        @endif
                        <li>
                            <a href="{{ route('customer.history.index') }}">
                                <i class="far fa-history"></i>
                                {{ __('app.home.history') }}
                            </a>
                        </li>
                        @if($config->pro_onoff === 'Y')
                            <li>
                                <a href="{{ route('customer.promotion.index') }}">
                                    <i class="far fa-gift"></i>
                                    {{ __('app.home.promotion') }}
                                </a>
                            </li>
                        @endif
                        <li>
                            <a href="../">
                                <i class="far fa-sign-out"></i>
                                {{ __('app.home.logout') }}
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

                    <div class="mr-1">
                           <span class="telheader ml-auto">
                              <img src="/images/icon/goldprofile.png"> 0881122334</span>
                        <div class="-balance-container">
                            <div class="-user-balance js-user-balance f-sm-6 f-7 ">
                                <div class="-inner-box-wrapper">
                                    <img class="img-fluid -ic-coin" src="/images/icon/coin.png" alt="customer image"
                                         width="26" height="21">
                                    <span id="customer-balance"><span class="text-green-lighter">500,000.00</span>
                                    </span>
                                </div>
                                <button type="button" class="-btn-balance" id="btn-customer-balance-reload">
                                    <i class="fas fa-sync-alt f-9 reloadcredit"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flexcenter d-none d-md-flex">
                        <div>
                            <a href="{{ route('customer.topup.index') }}">
                                <button class="btn blue">
                                    {{ __('app.home.refill') }}
                                </button>
                            </a>
                            <a href="{{ route('customer.withdraw.index') }}">
                                <button class="btn gold">
                                    {{ __('app.home.withdraw') }}
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DIV SECCOND BAR -->
