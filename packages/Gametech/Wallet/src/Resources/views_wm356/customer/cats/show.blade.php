@extends('wallet::layouts.show')

{{-- page title --}}
@section('title','')

@push('styles')
    <style>
        .menu-item {
            min-width: 110px;
            min-height: 80px;
            background: #232323;
            border-radius: 16px;
            box-shadow: 0 2px 12px #0005;
            color: #ffb52a;
            display: flex;
            align-items: center;
            justify-content: center;
            /* ถ้าอยากให้ card มีระยะห่างระหว่างกัน ให้ใช้ gap ที่ .menu-scroll */
            transition: border 0.18s, background 0.15s, color 0.15s;
            text-align: center;
            border: 1px solid #ffb52a; // เพิ่มถ้าอยากให้ hover ชัด
        }

        /* ป้องกัน a ทำลาย flex ของ block */
        .menu-item a {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: inherit;
            text-decoration: none;
            padding: 12px 0 6px 0;
        }

        .menu-item img {
            width: 36px;
            height: 36px;
            margin-bottom: 5px;
            object-fit: contain;
        }

        .menu-item small {
            margin-top: 2px;
            font-size: 1.08rem;
            letter-spacing: 0.3px;
            color: #ffb52a;
            font-weight: 600;
        }

        .menu-item:hover,
        .menu-item.active,
        .menu-item:focus-within {
            background: #111;
            box-shadow: 0 4px 20px #0009;
        }

        .menu-scroll-wrapper {
            width: 100%;
            overflow-x: auto;
            /* optional: hide scrollbar */
            scrollbar-width: none;
        }


        .menu-scroll {
            display: flex;
            gap: 22px;
            /*background: #191919;*/
            padding: 18px 18px 10px 18px;
            width: fit-content;
            margin: 0 auto;

        }

        .menu-scroll-wrapper::-webkit-scrollbar { display: none; }
        .menu-scroll-wrapper { scrollbar-width: none; }
        @media (max-width: 600px) {
            .cat {

                padding-right: 0px !important;
                padding-left: 0px !important;

            }
            .menu-scroll {
                width: 100%;
                margin: 0;
                gap: 5px;
                padding: 5px 4px 4px 0px;
            }
            .menu-item {
                min-width: 66px;
                min-height: 48px;
                border-radius: 11px;
                font-size: 0.95rem;
            }
            .menu-item a {
                padding: 7px 0 3px 0;
            }
            .menu-item img {
                width: 26px;
                height: 26px;
                margin-bottom: 2px;
            }
            .menu-item small {
                font-size: smaller;
            }
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 18px;
            background: transparent;
            border-radius: 22px;
            padding: 22px 10px;
            width: 120px;
            align-items: center;

            /* box-shadow: 0 4px 40px #000b; // ใส่ได้ถ้าอยากเด่น */
        }

        .sidebar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: linear-gradient(135deg, #232323 70%, #23231c 100%);
            border-radius: 16px;
            padding: 15px 8px 10px 8px;
            text-decoration: none;
            color: #ffd700;
            font-weight: 700;
            font-size: 1.04rem;
            letter-spacing: 0.5px;
            box-shadow: 0 1.5px 10px #0007;
            transition:
                    background 0.16s,
                    color 0.16s,
                    box-shadow 0.14s,
                    transform 0.14s;
            margin: 0 auto;
            outline: none;
            margin-bottom: 10px;
            border: 1px solid #ffb52a; // เพิ่มถ้าอยากให้ hover ชัด
        }

        .sidebar-item img {
            width: 38px;
            height: 38px;
            margin-bottom: 8px;
            object-fit: contain;
            transition: filter 0.2s, transform 0.13s;
        }

        .sidebar-item span {
            margin-top: 0;
            font-size: 1.01rem;
            letter-spacing: 1px;
            text-shadow: 0 1px 8px #0007;
            white-space: nowrap;
        }

        /* effect hover/active เน้นพื้นหลังทองนวล+ขยายเล็กน้อย */
        .sidebar-item:hover, .sidebar-item.active, .sidebar-item:focus {
            background: linear-gradient(125deg, #ffe066 35%, #fffbe6 100%);
            color: #181818;
            box-shadow: 0 2px 16px #ffe06644, 0 4px 24px #0005;
            transform: translateY(-2px) scale(1.04);
            text-decoration: none;
        }

        .sidebar-item:hover img, .sidebar-item.active img {
            filter: brightness(1.13) drop-shadow(0 0 8px #ffe06644);
            transform: scale(1.06) rotate(-2deg);
        }

        @media (max-width: 600px) {
            .sidebar-menu { width: 80px; padding: 10px 2px; gap: 10px; }
            .sidebar-item { font-size: 0.93rem; padding: 10px 3px 7px 3px; }
            .sidebar-item img { width: 26px; height: 26px; margin-bottom: 6px;}
        }


        @media (max-width: 991.98px) {
            .x-category-index .-games-list-outer-container.-has-sidebar .-container-fluid {
                padding-left: 15px;
                padding-right: 15px;
            }
        }


    </style>
@endpush

@section('content')
    <div id="main__content" class="x-ez-games-by-category">

        <div class="js-replace-cover-seo-container">
            <div class="x-cover -small x-cover-category x-bg-position-center lazyloaded"
                 data-bgset="{{ Storage::url('gametype_img/' . $type->filepic).'?v='.date('Ymd') }}"
                 style="background-image: url(&quot;{{ Storage::url('gametype_img/' . $type->filepic).'?v='.date('Ymd') }}&quot;);">
                <div class="x-cover-template-full">
                    <div class="container -container-wrapper">
                        <div class="-row-wrapper">
                            <div class="-col-wrapper -first animated fadeInModal" data-animatable="fadeInModal">
                                <div class="x-cover-typography -v2">
                                    <h1 class="-title">{{ $type->title }}</h1>
                                    <p class="-sub-title">{{ $type->content }}</p>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="x-category-index -v2">
            <div class="-nav-menu-container js-category-menus">
                <div class="container-fluid pr-lg-0">
                    <div class="-nav-menu-container js-category-menus -v2">
{{--                        <div class="x-quick-transaction-buttons js-quick-transaction-buttons">--}}
{{--                            <a class="btn -btn -promotion -vertical" href="{{ route('customer.promotion.index') }}"--}}
{{--                               target="_blank"--}}
{{--                               rel="noopener nofollow">--}}
{{--                            <span class="-ic-wrapper"> <img alt="โปรโมชั่นสุดคุ้ม เพื่อลูกค้าคนสำคัญ"--}}
{{--                                                            class="img-fluid -ic" width="40" height="40"--}}
{{--                                                            src="/assets/wm356/images/ic-quick-transaction-button-promotion.png?v=2"/></span>--}}

{{--                                <span class="-btn-inner-content">--}}
{{--            <span class="-btn-inner-content-title">โปรโมชั่น</span>--}}
{{--        </span>--}}
{{--                            </a>--}}

{{--                            <button--}}
{{--                                    class="btn -btn -deposit x-bg-position-center lazyloaded"--}}
{{--                                    data-toggle="modal"--}}
{{--                                    data-target="#depositModal"--}}
{{--                                    data-bgset="/assets/wm356/images/btn-deposit-bg.png?v=2"--}}
{{--                                    style="background-image: url('/assets/wm356/images/btn-deposit-bg.png?v=2');"--}}
{{--                            >--}}
{{--                            <span class="-ic-wrapper"> <img alt="ฝากเงินง่ายๆ ด้วยระบบออโต้ การันตี 1 นาที"--}}
{{--                                                            class="img-fluid -ic" width="40" height="40"--}}
{{--                                                            src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.png"/></span>--}}

{{--                                <span class="-btn-inner-content">--}}
{{--            <span class="-btn-inner-content-title">{{ __('app.home.refill') }}</span>--}}
{{--        </span>--}}
{{--                            </button>--}}

{{--                            <button--}}
{{--                                    class="btn -btn -withdraw x-bg-position-center lazyloaded"--}}
{{--                                    data-toggle="modal"--}}
{{--                                    data-target="#withdrawModal"--}}
{{--                                    data-bgset="/assets/wm356/images/btn-withdraw-bg.png?v=2"--}}
{{--                                    style="background-image: url('/assets/wm356/images/btn-withdraw-bg.png?v=2');"--}}
{{--                            >--}}
{{--                            <span class="-ic-wrapper"> <img alt="ถอนเงินง่ายๆ ด้วยระบบออโต้ การันตี เท่าไหร่ก็จ่าย"--}}
{{--                                                            class="img-fluid -ic" width="40" height="40"--}}
{{--                                                            src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.png"/></span>--}}

{{--                                <span class="-btn-inner-content">--}}
{{--            <span class="-btn-inner-content-title">{{ __('app.home.withdraw') }}</span>--}}
{{--        </span>--}}
{{--                            </button>--}}
{{--                        </div>--}}

                        <nav class="nav-menu" id="navbarCategory">

                            <div class="menu-scroll-wrapper d-lg-none d-block">
                                <div class="menu-scroll">
                                    @foreach($gameTypes as $gameType)
                                        <div class="menu-item">
                                            <a href="{{ route('customer.cats.show_list', ['id' => strtolower($gameType->id)]) }}">
                                                <img src="{{ $gameType->icon }}">
                                                <small>{{ __('app.game.'.strtolower($gameType->id)) }}</small>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="sidebar-menu d-lg-block d-none">
                                @foreach($gameTypes as $gameType)
                                <a href="{{ route('customer.cats.show_list', ['id' => strtolower($gameType->id)]) }}" class="sidebar-item">
                                    <img src="{{ $gameType->icon }}" alt="คาสิโน">
                                    <span>{{ __('app.game.'.strtolower($gameType->id)) }}</span>
                                </a>
                                @endforeach

                            </div>


                            {{--                            <ul class="-menu-parent navbar-nav js-menu-container" id="accordion-games">--}}


{{--                                <li class="-list-parent nav-item">--}}
{{--                                    <div class="d-lg-block d-none">--}}
{{--                                        <a href="{{ route('customer.cats.list', ['id' => 'casino']) }}"--}}
{{--                                           onclick="location.href='{{ route('customer.cats.list', ['id' => 'casino']) }}"--}}
{{--                                           data-menu-container=".js-menu-container"--}}
{{--                                           class="x-category-button -category-casino -category-button-v2 -hoverable">--}}
{{--                                            <img alt="category casino image png" class="-img -default" width="300"--}}
{{--                                                 height="82"--}}
{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-casino-hover.jpg?v=2"/>--}}

{{--                                            <img alt="category casino image png" class="-img -hover" width="300"--}}
{{--                                                 height="82"--}}
{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-casino-hover.jpg?v=2"/>--}}

{{--                                            <span class="-menu-text-main -text-btn-image">--}}
{{--                                                    <div class="-menu-text-wrapper">--}}
{{--                                                        <span class="-text-desktop">{{ __('app.home.casino') }}</span>--}}
{{--                                                        <span class="-text-mobile">{{ __('app.home.casino') }}</span>--}}
{{--                                                    </div>--}}
{{--                                                </span>--}}
{{--                                        </a>--}}
{{--                                    </div>--}}

{{--                                    --}}{{--                                    <div class="d-lg-none d-block w-100">--}}
{{--                                    --}}{{--                                        <a href="{{ route('customer.cats.list', ['id' => 'casino']) }}"--}}
{{--                                    --}}{{--                                           class="x-category-button -category-casino -index-page -category-button-v2 -hoverable">--}}
{{--                                    --}}{{--                                            <img alt="category casino image png" class="-img -default" width="300"--}}
{{--                                    --}}{{--                                                 height="82"--}}
{{--                                    --}}{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-casino-hover.jpg?v=2"/>--}}

{{--                                    --}}{{--                                            <img alt="category casino image png" class="-img -hover" width="300"--}}
{{--                                    --}}{{--                                                 height="82"--}}
{{--                                    --}}{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-casino-hover.jpg?v=2"/>--}}

{{--                                    --}}{{--                                            <span class="-menu-text-main -text-btn-image">--}}
{{--                                    --}}{{--                                            <div class="-menu-text-wrapper">--}}
{{--                                    --}}{{--                                                <span class="-text-desktop">{{ __('app.home.casino') }}</span>--}}
{{--                                    --}}{{--                                                <span class="-text-mobile">{{ __('app.home.casino') }}</span>--}}
{{--                                    --}}{{--                                            </div>--}}
{{--                                    --}}{{--                                        </span>--}}
{{--                                    --}}{{--                                        </a>--}}
{{--                                    --}}{{--                                    </div>--}}
{{--                                </li>--}}
{{--                                <li class="-list-parent nav-item">--}}
{{--                                    <div class="d-lg-block d-none">--}}
{{--                                        <a href="{{ route('customer.cats.list', ['id' => 'slot']) }}"--}}
{{--                                           onclick="location.href='{{ route('customer.cats.list', ['id' => 'slot']) }}"--}}
{{--                                           data-menu-container=".js-menu-container"--}}
{{--                                           class="x-category-button -category-slot -category-button-v2 -hoverable">--}}
{{--                                            <img alt="category slot image png" class="-img -default" width="300"--}}
{{--                                                 height="82"--}}
{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot-hover.jpg?v=2"/>--}}

{{--                                            <img alt="category slot image png" class="-img -hover" width="300"--}}
{{--                                                 height="82"--}}
{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot-hover.jpg?v=2"/>--}}

{{--                                            <span class="-menu-text-main -text-btn-image">--}}
{{--                                                    <div class="-menu-text-wrapper">--}}
{{--                                                        <span class="-text-desktop">{{ __('app.home.slot') }}</span>--}}
{{--                                                        <span class="-text-mobile">{{ __('app.home.slot') }}</span>--}}
{{--                                                    </div>--}}
{{--                                                </span>--}}
{{--                                        </a>--}}
{{--                                    </div>--}}

{{--                                    --}}{{--                                    <div class="d-lg-none d-block w-100">--}}
{{--                                    --}}{{--                                        <a href="{{ route('customer.cats.list', ['id' => 'slot']) }}"--}}
{{--                                    --}}{{--                                           class="x-category-button -category-slot -index-page -category-button-v2 -hoverable">--}}
{{--                                    --}}{{--                                            <img alt="category slot image png" class="-img -default" width="300"--}}
{{--                                    --}}{{--                                                 height="82"--}}
{{--                                    --}}{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot-hover.jpg?v=2"/>--}}

{{--                                    --}}{{--                                            <img alt="category slot image png" class="-img -hover" width="300"--}}
{{--                                    --}}{{--                                                 height="82"--}}
{{--                                    --}}{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot-hover.jpg?v=2"/>--}}

{{--                                    --}}{{--                                            <span class="-menu-text-main -text-btn-image">--}}
{{--                                    --}}{{--                                            <div class="-menu-text-wrapper">--}}
{{--                                    --}}{{--                                                <span class="-text-desktop">{{ __('app.home.slot') }}</span>--}}
{{--                                    --}}{{--                                                <span class="-text-mobile">{{ __('app.home.slot') }}</span>--}}
{{--                                    --}}{{--                                            </div>--}}
{{--                                    --}}{{--                                        </span>--}}
{{--                                    --}}{{--                                        </a>--}}
{{--                                    --}}{{--                                    </div>--}}
{{--                                </li>--}}
{{--                                <li class="-list-parent nav-item">--}}
{{--                                    <div class="d-lg-block d-none">--}}
{{--                                        <a href="{{ route('customer.cats.list', ['id' => 'sport']) }}"--}}
{{--                                           onclick="location.href='{{ route('customer.cats.list', ['id' => 'sport']) }}"--}}
{{--                                           data-menu-container=".js-menu-container"--}}
{{--                                           class="x-category-button -category-sport -category-button-v2 -hoverable">--}}
{{--                                            <img alt="category sport image png" class="-img -default" width="300"--}}
{{--                                                 height="82"--}}
{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-sport-hover.jpg?v=2"/>--}}

{{--                                            <img alt="category sport image png" class="-img -hover" width="300"--}}
{{--                                                 height="82"--}}
{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-sport-hover.jpg?v=2"/>--}}

{{--                                            <span class="-menu-text-main -text-btn-image">--}}
{{--                                                    <div class="-menu-text-wrapper">--}}
{{--                                                        <span class="-text-desktop">{{ __('app.home.sport') }}</span>--}}
{{--                                                        <span class="-text-mobile">{{ __('app.home.sport') }}</span>--}}
{{--                                                    </div>--}}
{{--                                                </span>--}}
{{--                                        </a>--}}
{{--                                    </div>--}}

{{--                                    --}}{{--                                    <div class="d-lg-none d-block w-100">--}}
{{--                                    --}}{{--                                        <a href="{{ route('customer.cats.list', ['id' => 'sport']) }}"--}}
{{--                                    --}}{{--                                           class="x-category-button -category-sport -index-page -category-button-v2 -hoverable">--}}
{{--                                    --}}{{--                                            <img alt="category sport image png" class="-img -default" width="300"--}}
{{--                                    --}}{{--                                                 height="82"--}}
{{--                                    --}}{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-sport-hover.jpg?v=2"/>--}}

{{--                                    --}}{{--                                            <img alt="category sport image png" class="-img -hover" width="300"--}}
{{--                                    --}}{{--                                                 height="82"--}}
{{--                                    --}}{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-sport-hover.jpg?v=2"/>--}}

{{--                                    --}}{{--                                            <span class="-menu-text-main -text-btn-image">--}}
{{--                                    --}}{{--                                            <div class="-menu-text-wrapper">--}}
{{--                                    --}}{{--                                                <span class="-text-desktop">{{ __('app.home.sport') }}</span>--}}
{{--                                    --}}{{--                                                <span class="-text-mobile">{{ __('app.home.sport') }}</span>--}}
{{--                                    --}}{{--                                            </div>--}}
{{--                                    --}}{{--                                        </span>--}}
{{--                                    --}}{{--                                        </a>--}}
{{--                                    --}}{{--                                    </div>--}}
{{--                                </li>--}}
{{--                                <li class="-list-parent nav-item">--}}
{{--                                    <div class="d-lg-block d-none">--}}
{{--                                        <a href="{{ route('customer.cats.list', ['id' => 'lotto']) }}"--}}
{{--                                           onclick="location.href='{{ route('customer.cats.list', ['id' => 'lotto']) }}"--}}
{{--                                           data-menu-container=".js-menu-container"--}}
{{--                                           class="x-category-button -category-lotto -category-button-v2 -hoverable">--}}
{{--                                            <img alt="category lotto image png" class="-img -default" width="300"--}}
{{--                                                 height="82"--}}
{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-lotto-hover.jpg?v=2"/>--}}

{{--                                            <img alt="category lotto image png" class="-img -hover" width="300"--}}
{{--                                                 height="82"--}}
{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-lotto-hover.jpg?v=2"/>--}}

{{--                                            <span class="-menu-text-main -text-btn-image">--}}
{{--                                                    <div class="-menu-text-wrapper">--}}
{{--                                                        <span class="-text-desktop">{{ __('app.home.sport') }}</span>--}}
{{--                                                                                                                <span class="-text-mobile">{{ __('app.home.sport') }}</span>--}}
{{--                                                    </div>--}}
{{--                                                </span>--}}
{{--                                        </a>--}}
{{--                                    </div>--}}

{{--                                    --}}{{--                                    <div class="d-lg-none d-block w-100">--}}
{{--                                    --}}{{--                                        <a href="{{ route('customer.cats.list', ['id' => 'lotto']) }}"--}}
{{--                                    --}}{{--                                           class="x-category-button -category-lotto -index-page -category-button-v2 -hoverable">--}}
{{--                                    --}}{{--                                            <img alt="category lotto image png" class="-img -default" width="300"--}}
{{--                                    --}}{{--                                                 height="82"--}}
{{--                                    --}}{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-lotto-hover.jpg?v=2"/>--}}

{{--                                    --}}{{--                                            <img alt="category lotto image png" class="-img -hover" width="300"--}}
{{--                                    --}}{{--                                                 height="82"--}}
{{--                                    --}}{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-lotto-hover.jpg?v=2"/>--}}

{{--                                    --}}{{--                                            <span class="-menu-text-main -text-btn-image">--}}
{{--                                    --}}{{--                                            <div class="-menu-text-wrapper">--}}
{{--                                    --}}{{--                                                <span class="-text-desktop">{{ __('app.home.sport') }}</span>--}}
{{--                                    --}}{{--                                                                                                <span class="-text-mobile">{{ __('app.home.sport') }}</span>--}}
{{--                                    --}}{{--                                            </div>--}}
{{--                                    --}}{{--                                        </span>--}}
{{--                                    --}}{{--                                        </a>--}}
{{--                                    --}}{{--                                    </div>--}}
{{--                                </li>--}}
{{--                                <li class="-list-parent nav-item">--}}
{{--                                    <div class="d-lg-block d-none">--}}
{{--                                        <a href="{{ route('customer.cats.list', ['id' => 'keno']) }}"--}}
{{--                                           onclick="location.href='{{ route('customer.cats.list', ['id' => 'keno']) }}"--}}
{{--                                           data-menu-container=".js-menu-container"--}}
{{--                                           class="x-category-button -category-keno -category-button-v2 -hoverable">--}}
{{--                                            <img alt="category keno image png" class="-img -default" width="300"--}}
{{--                                                 height="82"--}}
{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-keno-hover.jpg?v=2"/>--}}

{{--                                            <img alt="category keno image png" class="-img -hover" width="300"--}}
{{--                                                 height="82"--}}
{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-keno-hover.jpg?v=2"/>--}}

{{--                                            <span class="-menu-text-main -text-btn-image">--}}
{{--                                                    <div class="-menu-text-wrapper">--}}
{{--                                                        <span class="-text-desktop">{{ __('app.home.sport') }}</span>--}}
{{--                                                                                                                <span class="-text-mobile">{{ __('app.home.sport') }}</span>--}}
{{--                                                    </div>--}}
{{--                                                </span>--}}
{{--                                        </a>--}}
{{--                                    </div>--}}

{{--                                    --}}{{--                                    <div class="d-lg-none d-block w-100">--}}
{{--                                    --}}{{--                                        <a href="{{ route('customer.cats.list', ['id' => 'keno']) }}"--}}
{{--                                    --}}{{--                                           class="x-category-button -category-keno -index-page -category-button-v2 -hoverable">--}}
{{--                                    --}}{{--                                            <img alt="category keno image png" class="-img -default" width="300"--}}
{{--                                    --}}{{--                                                 height="82"--}}
{{--                                    --}}{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-keno-hover.jpg?v=2"/>--}}

{{--                                    --}}{{--                                            <img alt="category keno image png" class="-img -hover" width="300"--}}
{{--                                    --}}{{--                                                 height="82"--}}
{{--                                    --}}{{--                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-keno-hover.jpg?v=2"/>--}}

{{--                                    --}}{{--                                            <span class="-menu-text-main -text-btn-image">--}}
{{--                                    --}}{{--                                            <div class="-menu-text-wrapper">--}}
{{--                                    --}}{{--                                                <span class="-text-desktop">{{ __('app.home.sport') }}</span>--}}
{{--                                    --}}{{--                                                                                                <span class="-text-mobile">{{ __('app.home.sport') }}</span>--}}
{{--                                    --}}{{--                                            </div>--}}
{{--                                    --}}{{--                                        </span>--}}
{{--                                    --}}{{--                                        </a>--}}
{{--                                    --}}{{--                                    </div>--}}
{{--                                </li>--}}


{{--                            </ul>--}}
                        </nav>
                    </div>
                </div>
            </div>

            <div class="-games-list-outer-container -has-sidebar">
                <div class="container-fluid -container-fluid">


                    <div class="-games-list-container js-game-scroll-container js-game-container">
                        <div class="-games-list-wrapper">
                            <div class="-game-title-wrapper">
                                <div class="-game-title-inner">
                                    <h2 class="-game-title h3 -shimmer">
                                        {{ $name }}
                                    </h2>
                                </div>

                            </div>

                            <ul class="navbar-nav -slot-provider-page">
                                @if(isset($games))
                                    @foreach($games as $k => $item)
                                        <li class="nav-item">
                                            <div
                                                    class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                                    data-status="-cannot-entry -untestable">
                                                <div class="-inner-wrapper">


                                                    <picture>
                                                        <source type="image/webp"
                                                                data-srcset="{{ $item->filepic }}"/>
                                                        <source type="image/png"
                                                                data-srcset="{{ $item->filepic }}"/>
                                                        <img
                                                                alt="smm-{{ Str::lower($item->id) }} cover image png"
                                                                class="img-fluid lazyload -cover-img"
                                                                width="400"
                                                                height="580"
                                                                data-src="{{ $item->filepic }}"
                                                                src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                        />
                                                    </picture>

                                                    <div class="-overlay">
                                                        <div class="-overlay-inner">
                                                            <div class="-wrapper-container">
                                                                <a href="#loginModal"
                                                                   class="js-account-approve-aware -btn -btn-play"
                                                                   data-toggle="modal" data-target="#loginModal">
                                                                    <i class="fas fa-play"></i>
                                                                    <span
                                                                            class="-text-btn">{{ __('app.home.join') }}</span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="-title">{{ $item->name }}</div>
                                            </div>
                                        </li>
                                    @endforeach
                                @endif


                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>


    </div>

    <div class="x-modal modal -v2 -with-half-size" id="loginModal" tabindex="-1" role="dialog" aria-hidden="true"
         data-loading-container=".js-modal-content" data-ajax-modal-always-reload="true">
        <div
                class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -dialog-in-tab -register-index-dialog"
                role="document">
            <div class="modal-content -modal-content">
                <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>

                <div class="x-modal-account-security-tabs js-modal-account-security-tabs -v3">


                    <button type="button" class="-btn -login js-modal-account-security-tab-button -active"
                            data-modal-target="#loginModal">
                        {{ __('app.login.login') }}
                    </button>
                </div>

                <div class="modal-body -modal-body">
                    <div class="x-register-tab-container -login js-tab-pane-checker-v3">


                        <div class="tab-content">
                            <div class="tab-pane active" id="tab-content-loginPhoneNumber"
                                 data-completed-dismiss-modal="">
                                <div class="x-modal-body-base -v3 -phone-number x-form-register-v3">
                                    <div class="row -register-container-wrapper">
                                        <div class="col">
                                            <div class="x-title-register-modal-v3">
                                                <span class="-title">{{ __('app.login.username') }}</span>
                                                <span
                                                        class="-sub-title">{{ __('app.login.username_login') }}</span>
                                            </div>
                                        </div>

                                        <div data-animatable="fadeInRegister" data-offset="0" class="col">
                                            <div class="-fake-inner-body">
                                                {{--                                                    <form method="post" data-register-v3-form="v3/check-for-login"--}}
                                                {{--                                                          data-register-step="loginPhoneNumber">--}}
                                                <form method="POST" action="{{ route('customer.session.create') }}" onsubmit="return;">

                                                    <div
                                                            class="-animatable-container -password-body">
                                                        <input
                                                                type="text"
                                                                required
                                                                autocomplete="off"
                                                                id="user_name"
                                                                name="user_name"
                                                                inputmode="text"
                                                                placeholder="{{ __('app.login.username') }}"
                                                                class="form-control x-form-control"
                                                                style="text-transform: lowercase;"
                                                        />
                                                    </div>
                                                    <div class="-x-input-icon flex-column">
                                                        <input type="password" id="password" name="password"
                                                               required
                                                               class="form-control x-form-control"
                                                               placeholder="{{ __('app.login.password') }}"/>
                                                    </div>


                                                    <div class="text-center">
                                                        <button type="submit"
                                                                class="btn -submit btn-primary mt-lg-3 mt-0">
                                                            {{ __('app.login.submit') }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection

@push('script')
    <script type="application/ld+json">
        {
            "url": "member"
        }


    </script>
@endpush

@push('scripts')
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

    <script>
        function reload() {
            window.location.reload(true);
        }

        function openQuickRegis({details}) {
            console.log(details);
            // if (event) {
            //     event.preventDefault();
            //     event.stopPropagation();
            // }
            Swal.fire({
                title: 'ยืนยันการทำรายการนี้ ?',
                text: "คุณต้องการเปิดบัญชี เกม " + details.name + " หรือไม่",
                imageUrl: details.image,
                imageWidth: 90,
                imageHeight: 90,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ตกลง',
                cancelButtonText: 'ยกเลิก',
                customClass: {
                    container: 'text-sm',
                    popup: 'text-sm'
                },
            }).then((result) => {
                if (result.isConfirmed) {

                    $('.modal').modal('hide');
                    axios.post("{{ route('customer.home.create') }}", {id: details.code})
                        .then(response => {
                            if (response.data.success) {
                                reload();
                            } else {
                                Swal.fire(
                                    'พบข้อผิดพลาด',
                                    response.data.message,
                                    'error'
                                );
                            }
                        })
                        .catch(response => {
                            $('.modal').modal('hide');
                            Swal.fire(
                                'การเชื่อมต่อระบบ มีปัญหา',
                                response.data.message,
                                'error'
                            );
                        });

                }
            })
        }

        function openQuickView({details, event}) {
            console.log(event);
            // console.log(event);
            // if (event) {
            //     event.preventDefault();
            //     event.stopPropagation();
            // }
            console.log(details);
            axios.post("{{ route('customer.profile.view') }}", {id: details.code})
                .then(response => {

                    console.log(response.data.success);
                    if (response.data.success) {

                        let btn = '';
                        if (response.data.data.game.link_ios) {
                            btn += '<a class="btn btn-sm btn-success mx-1" target="_blank" href="' + response.data.data.game.link_ios + '"><i class="fa-brands fa-apple"></i> iOS</a>';
                        }
                        if (response.data.data.game.link_android) {
                            btn += '<a class="btn btn-sm btn-primary mx-1" target="_blank" href="' + response.data.data.game.link_android + '"><i class="fa-brands fa-android"></i> Android</a>';
                        }
                        if (response.data.data.game.link_web) {
                            btn += '<a class="btn btn-sm btn-secondary mx-1" target="_blank" href="' + response.data.data.game.link_web + '"><i class="fas fa-link"></i> Web</a>';
                        }
                        if (response.data.data.game.autologin === 'Y') {
                            btn = '<a class="btn btn-sm btn-secondary mx-1" target="_blank" href="' + `{{ route('customer.game.login') }}` + '/' + response.data.data.game.id + '"><i class="fas fa-link"></i> Login</a>';
                        }

                        Swal.fire({
                            title: '<h5>ข้อมูลของเกม ' + details.name + '</h5>',
                            imageUrl: details.image,
                            imageWidth: 90,
                            imageHeight: 90,
                            html:
                                '<table class="table table-borderless text-sm" style="color:black;">, ' +
                                '<tbody> ' +
                                '<tr class="copybtn"> ' +
                                '<td>Username</td>' +
                                '<td><span>' + response.data.data.user_name + '</span></td>' +
                                '<td style="text-align: center"><a class="user text-primary" href="javascript:void(0)" onclick="copylink()">[คัดลอก]</a></td>' +
                                '</tr> ' +
                                '<tr class="copybtn"> ' +
                                '<td>Password</td>' +
                                '<td><span>' + response.data.data.user_pass + '</span></td>' +
                                '<td style="text-align: center"><a class="pass text-primary" href="javascript:void(0)" onclick="copylink()">[คัดลอก]</a></td>' +
                                '</tr> ' +
                                '<tr> ' +
                                '<td colspan="3">' + btn + '</td>' +
                                '</tr> ' +
                                '</tbody> ',
                            showConfirmButton: false,
                            showCloseButton: true,
                            showCancelButton: false,
                            focusConfirm: false,
                            scrollbarPadding: true,
                            customClass: {
                                container: 'text-sm',
                                popup: 'text-sm'
                            },
                            willOpen: () => {

                                $(".copybtn").click(function (event) {
                                    var $tempElement = $("<input>");
                                    $("body").append($tempElement);
                                    $tempElement.val($(this).closest(".copybtn").find("span").text()).select();
                                    document.execCommand("Copy");
                                    $tempElement.remove();
                                });


                            }
                        });

                    }
                })
                .catch(response => {

                    Swal.fire(
                        'เกิดปัญหาบางประการ',
                        'ไม่สามารถดำเนินการได้ โปรดลองใหม่อีกครั้ง',
                        'error'
                    );

                });

        }

        {{--function openTransfer({details, event}) {--}}
        {{--    (async () => {--}}
        {{--        const ipAPI = "{{ route('customer.transfer.load.promotion') }}";--}}
        {{--        const response = await fetch(ipAPI);--}}
        {{--        const data = await response.json();--}}
        {{--        const inputOptions = data.promotions;--}}
        {{--        const configeweb = {{ Illuminate\Support\Js::from($webconfig) }};--}}

        {{--        let foottext = '<small class="text-center text-danger">โยกเข้าเกมส์ ขั้นต่ำ {{ core()->currency($webconfig->mintransfer) }}  บาท</small>';--}}

        {{--        if (configeweb.mintransfer_pro !== 0) {--}}
        {{--            foottext += '<p><small class="text-center text-danger">สามารถโยกเข้าเกม ได้เมื่อเงินในเกมเหลือน้อยกว่า {{ core()->currency($webconfig->mintransfer_pro) }} บาท (กรณีมีการรับโปรไปแล้ว)</small></p>';--}}
        {{--        }--}}

        {{--        var options = {};--}}
        {{--        $.map(inputOptions,--}}
        {{--            function (o) {--}}
        {{--                options[o.value] = o.text;--}}
        {{--            });--}}
        {{--        const {value: formValues} = await Swal.fire({--}}
        {{--            title: "โยกเข้าเกม " + details.name,--}}
        {{--            input: "select",--}}
        {{--            inputOptions: options,--}}
        {{--            inputPlaceholder: "เลือกโปรโมชั่น",--}}
        {{--            html: 'จำนวนเงินที่โยก  <input id="amount" name="amount" class="swal2-input" placeholder="กรุณากรอกจำนวนเงิน" step="0.01" min="1" type="number" value="0">',--}}
        {{--            footer: foottext,--}}
        {{--            preConfirm: async (selectedOption) => {--}}

        {{--                const amount = document.getElementById('amount').value;--}}
        {{--                if (!amount) {--}}
        {{--                    Swal.showValidationMessage(`โปรดระบุจำนวนเงินที่ต้องการโยก`)--}}
        {{--                }--}}
        {{--                if (!selectedOption) {--}}
        {{--                    return new Promise(function (resolve) {--}}
        {{--                        resolve({amount: amount, game: details.code})--}}
        {{--                    });--}}
        {{--                } else {--}}
        {{--                    return new Promise(function (resolve) {--}}
        {{--                        resolve({promotion: selectedOption, amount: amount, game: details.code})--}}
        {{--                    });--}}

        {{--                }--}}


        {{--            },--}}

        {{--            didOpen: function () {--}}

        {{--                Swal.getPopup()?.querySelector('input')?.focus()--}}
        {{--            },--}}
        {{--            showCancelButton: true--}}
        {{--        });--}}


        {{--        if (formValues) {--}}

        {{--            axios.post("{{ route('customer.transfer.game.checkpro') }}", formValues)--}}
        {{--                .then(response => {--}}
        {{--                    if (response.data.success) {--}}

        {{--                        Swal.fire(--}}
        {{--                            'สำเร็จ',--}}
        {{--                            'โยกเงินเข้าเกมสำเร็จแล้ว',--}}
        {{--                            'success'--}}
        {{--                        );--}}

        {{--                    } else {--}}

        {{--                        Swal.fire(--}}
        {{--                            'พบข้อผิดพลาด',--}}
        {{--                            response.data.message,--}}
        {{--                            'error'--}}
        {{--                        );--}}
        {{--                    }--}}
        {{--                })--}}
        {{--                .catch(response => {--}}

        {{--                    Swal.fire(--}}
        {{--                        'การเชื่อมต่อระบบ มีปัญหา',--}}
        {{--                        response.data.message,--}}
        {{--                        'error'--}}
        {{--                    );--}}
        {{--                });--}}


        {{--        }--}}


        {{--    })()--}}
        {{--}--}}

        @if($refill)
        $(document).ready(function () {

            Swal.fire({
                title: "{{ __('app.promotion.can') }}",
                html: "{{ __('app.promotion.word') }} {{ $refill->value }} {!!  __('app.promotion.word2') !!}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __('app.promotion.yes') }}',
                cancelButtonText: '{{ __('app.promotion.no') }}',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '{{ route('customer.promotion.index') }}';
                } else {
                    axios.post(`{{ route('customer.promotion.cancel') }}`).then(response => {
                        if (response.data.success) {
                            Toast.fire({
                                icon: 'warning',
                                title: '{{ __('app.promotion.no2') }}'
                            })
                        }
                    }).catch(err => [err]);

                }
            })

        });
        @endif
    </script>
@endpush
