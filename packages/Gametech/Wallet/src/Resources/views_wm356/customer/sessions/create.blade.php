{{-- extend layout --}}
@extends('wallet::layouts.app')

{{-- page title --}}
@section('title','')

@php
    function imageMimeType($file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return match($ext) {
            'webp' => 'image/webp',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/*',
        };
    }
@endphp

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
                padding: 5px 4px 4px 5px;
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

        .category-bar {
            display: flex;
            align-items: center;
            /*background: linear-gradient(92deg, #2e2e34 0%, #19191e 80%);*/
            border-radius: 14px;
            padding: 0 20px;
            min-height: 46px;
            margin-bottom: 20px;
            /*box-shadow: 0 2px 12px #0006, 0 0 0px #2229 inset;*/
            position: relative;
        }

        .category-title {
            background: linear-gradient(92deg, #ffb52a 60%, #ffd700 100%);
            color: #2a1a06;
            font-size: 1.18rem;
            font-weight: 900;
            padding: 7px 30px 7px 22px;
            border-radius: 11px 0 12px 11px;
            box-shadow: 0 1px 8px #ffb52a44;
            margin-left: -10px;
            margin-right: 18px;
            letter-spacing: 1.5px;
            min-width: 74px;
            text-align: center;
            border: 2.5px solid #b4001a;
            outline: 3.5px solid #232323;
            outline-offset: -6px;
        }

        @media (max-width: 600px) {
            .category-bar {
                padding: 0 6px;
                min-height: 40px;
                border-radius: 8px;
            }
            .category-title {
                font-size: 1rem;
                padding: 5px 16px 5px 10px;
                border-radius: 6px 0 9px 6px;
                min-width: 46px;
                margin-left: -5px;
                margin-right: 10px;
            }
        }

        .category-block {
            width: 100%;
            max-width: 1280px;      /* หรือ 100% ถ้าอยากเต็มจอ */
            min-height: 200px;      /* กำหนดความสูงเท่ากัน */
            margin-left: auto;
            margin-right: auto;
            /*background: linear-gradient(100deg, #23232c 80%, #18181c 100%);*/
            border-radius: 20px;
            /*box-shadow: 0 4px 40px #000a, 0 0 0 #ffd70015 inset;*/
            padding: 0 0 32px 0;
            /*margin-bottom: 34px;*/
            /*border: 1.8px solid #292942;*/
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;  /* หรือ center ถ้าอยากให้อยู่กลาง block */
        }



    </style>

    @foreach($slides as $i => $item)
        <link
                rel="preload"
                href="{{ url(Storage::url('slide_img/'.$item->filepic)) }}"
                as="image"
                type="{{ imageMimeType(Storage::url('slide_img/'.$item->filepic)) }}"

        >
    @endforeach

    @foreach($gameTypes as $gameType)
        <link
                rel="preload"
                href="{{ url($gameType->icon) }}"
                as="image"
                type="{{ imageMimeType($gameType->icon) }}"

        >
    @endforeach
    
    <link
            rel="preload"
            href="{{ url(core()->imgurl($webconfig->logo,'img')) }}"
            as="image"
            type="{{ imageMimeType(Storage::url('img/'.$webconfig->logo)) }}"

    >

@endpush


@push('script')
    <script type="application/ld+json">
        {
            "url": "login"
        }

    </script>
@endpush

@push('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Lazyload
            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        const el = e.target;
                        el.src = el.dataset.src;
                        observer.unobserve(el);
                    }
                });
            });

            document.querySelectorAll('.lazyload').forEach(img => {
                observer.observe(img);
            });

        });
    </script>


@endpush

@section('content')

    <div id="main__content"
         class="x-bg-position-center x-bg-index">
        <div class="js-replace-cover-seo-container">
            <div class="x-homepage-banner-container">
                <div
                        data-slickable='{"arrows":false,"dots":true,"slidesToShow":1,"centerMode":true,"infinite":true,"autoplay":true,"autoplaySpeed":4000,"pauseOnHover":false,"focusOnSelect":true,"variableWidth":true,"responsive":{"sm":{"fade":true,"variableWidth":false}}}'
                        class="x-banner-slide-wrapper -single"
                        data-animatable="fadeInUp"
                        data-delay="200"
                >
                    @if(count($slides) == 1)
                        @foreach($slides as $i => $item)
                            <div class="-slide-inner-wrapper -slick-item">
                                <div class="-link-wrapper">
                                    <picture>
                                        <source type="image/webp"
                                                srcset="{{ Storage::url('slide_img/'.$item->filepic) }}"/>
                                        <source type="image/jpg"
                                                srcset="{{ Storage::url('slide_img/'.$item->filepic) }}"/>
                                        <img loading="lazy" class="img-fluid -slick-item -item-{{ $i+1 }}" alt="banner-{{ $i+1 }}"
                                             width="1200"
                                             height="590"
                                             src="{{ Storage::url('slide_img/'.$item->filepic) }}"/>
                                    </picture>
                                </div>
                            </div>
                        @endforeach
                        @foreach($slides as $i => $item)
                            <div class="-slide-inner-wrapper -slick-item">
                                <div class="-link-wrapper">
                                    <picture>
                                        <source type="image/webp"
                                                srcset="{{ Storage::url('slide_img/'.$item->filepic) }}"/>
                                        <source type="image/jpg"
                                                srcset="{{ Storage::url('slide_img/'.$item->filepic) }}"/>
                                        <img loading="lazy" class="img-fluid -slick-item -item-{{ $i+1 }}" alt="banner-{{ $i+1 }}"
                                             width="1200"
                                             height="590"
                                             src="{{ Storage::url('slide_img/'.$item->filepic) }}"/>
                                    </picture>
                                </div>
                            </div>
                        @endforeach
                    @else
                        @foreach($slides as $i => $item)
                            <div class="-slide-inner-wrapper -slick-item">
                                <div class="-link-wrapper">
                                    <picture>
                                        <source type="image/webp"
                                                srcset="{{ Storage::url('slide_img/'.$item->filepic) }}"/>
                                        <source type="image/jpg"
                                                srcset="{{ Storage::url('slide_img/'.$item->filepic) }}"/>
                                        <img loading="lazy" class="img-fluid -slick-item -item-{{ $i+1 }}" alt="banner-{{ $i+1 }}"
                                             width="1200"
                                             height="590"
                                             src="{{ Storage::url('slide_img/'.$item->filepic) }}"/>
                                    </picture>
                                </div>
                            </div>
                        @endforeach
                    @endif

                </div>
            </div>
        </div>

        <div class="x-index-content-main-container -anon">
            <div class="x-title-with-tag-header" data-animatable="fadeInUp" data-delay="150">
                <div class="container">
                    <h1 class="-title">{{ $webconfig->content_header }}</h1>
                </div>
            </div>

            <div class="x-category-total-game -v2">
                <div class="container-fluid cat">
                    <div class="menu-scroll-wrapper">
                        <div class="menu-scroll">
                            @foreach($gameTypes as $gameType)
                                <div class="menu-item">
                                    <a href="{{ route('customer.session.index') }}">
                                        <img src="{{ $gameType->icon }}" alt="{{ $gameType->id }} icon หมวดเกมน่าสนใจ แตกทุกวัน" width="36" height="36" loading="lazy">
                                        <small>{{ __('app.game.'.strtolower($gameType->id)) }}</small>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>




            @foreach($games as $i => $game)

                @if(count($game) > 0)

                    <div class="category-block">
                        <div class="x-lotto-category x-provider-category -provider_{{ strtolower($i) }}">
                            <div class="container-fluid">
{{--                                                    <div class="category-title" data-animatable="fadeInUp" data-delay="150">--}}
{{--                                                        {{ $i }}--}}
{{--                                                    </div>--}}

                                <div class="category-bar">
                                    <div class="category-title">{{ __('app.game.'.strtolower($i)) }}</div>

                                </div>


                                <div class="-lotto-category-wrapper">
                                    <ul class="navbar-nav">

                                        @foreach($game as $k => $item)


                                            <li class="nav-item -lotto-card-item">
                                                <div
                                                        class="x-game-list-item-macro-in-share js-game-list-toggle -big-with-countdown-dark -cannot-entry -untestable -use-promotion-alert"
                                                        data-status="-cannot-entry -untestable">
                                                    <div class="-inner-wrapper">


                                                        <picture>
                                                            <source type="image/webp"
                                                                    data-srcset="{{ $item['logoURL'] }}"/>
                                                            <source type="image/png"
                                                                    data-srcset="{{ $item['logoURL'] }}"/>
                                                            <img loading="lazy"
                                                                    alt="{{ ucfirst($item['providerName']) }} เกมค่ายดัง แตกง่าย ที่ thegrand789.com"
                                                                    class="img-fluid lazyload -cover-img"
                                                                    width="400"
                                                                    height="580"
                                                                    data-src="{{ $item['logoURL'] }}"
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
                                                    <div class="-title">{{$item['providerName']}}</div>
                                                </div>


                                            </li>
                                        @endforeach

                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach

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

        <div class="x-modal modal -v2 x-theme-switcher-v2" id="themeSwitcherModal" tabindex="-1" role="dialog"
             aria-hidden="true" data-loading-container=".js-modal-content" data-ajax-modal-always-reload="true">
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable modal-dialog-centered"
                 role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-body -modal-body">
                        <div class="-theme-switcher-container">
                            <div class="-inner-header-section">

                                <a class="-link-wrapper" href="{{ route('customer.home.index') }}">
                                    <picture>
                                        <source type="image/webp"
                                                data-srcset="{{ url(core()->imgurl($webconfig->logo,'img')) }}"/>
                                        <source type="image/png"
                                                data-srcset="{{ url(core()->imgurl($webconfig->logo,'img')) }}"/>
                                        <img
                                                alt="logo image" loading="lazy"
                                                class="img-fluid lazyload -logo"
                                                width="180"
                                                height="42"
                                                data-src="{{ url(core()->imgurl($webconfig->logo,'img')) }}"
                                                src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                        />
                                    </picture>
                                </a>


                            </div>


                            <div class="-inner-top-body-section">
                                <div class="row -wrapper-box">
                                    <div class="col"><a style="color:black"
                                                        href="{{ route('customer.home.lang', ['lang' => 'th']) }}"><img
                                                    src="/images/flag/th.png" class="img img-fluid" loading="lazy"></a></div>
                                    <div class="col"><a style="color:black"
                                                        href="{{ route('customer.home.lang', ['lang' => 'en']) }}"><img
                                                    src="/images/flag/en.png" class="img img-fluid" loading="lazy"></a></div>
                                    <div class="col"><a style="color:black"
                                                        href="{{ route('customer.home.lang', ['lang' => 'kh']) }}"><img
                                                    src="/images/flag/kh.png" class="img img-fluid" loading="lazy"></a></div>
                                    <div class="col"><a style="color:black"
                                                        href="{{ route('customer.home.lang', ['lang' => 'la']) }}"><img
                                                    src="/images/flag/la.png" class="img img-fluid" loading="lazy"></a></div>
                                </div>

                                <div class="col-6 -wrapper-box">
                                    <a

                                            class="btn -btn-item -top-btn -register-button lazyload x-bg-position-center"
                                            href="{{ route('customer.session.store') }}"
                                            data-bgset="/assets/wm356/images/btn-register-login-bg.png?v=2"
                                    >
                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="/assets/wm356/images/ic-modal-menu-register.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="/assets/wm356/images/ic-modal-menu-register.png?v=2"/>
                                            <img
                                                    alt="รูปไอคอนสมัครสมาชิก" loading="lazy"
                                                    class="img-fluid -icon-image lazyload"
                                                    width="50"
                                                    height="50"
                                                    data-src="/assets/wm356/images/ic-modal-menu-register.png?v=2"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                            />
                                        </picture>

                                        <div class="-typo-wrapper">
                                            <div class="-typo">{{ __('app.login.register') }}</div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-6 -wrapper-box">
                                    <button
                                            type="button"
                                            class="btn -btn-item -top-btn -login-btn lazyload x-bg-position-center"
                                            data-toggle="modal"
                                            data-dismiss="modal"
                                            data-target="#loginModal"
                                            data-bgset="assets/wm356/images/btn-register-login-bg.png?v=2"
                                    >
                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="/assets/wm356/images/ic-modal-menu-login.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="/assets/wm356/images/ic-modal-menu-login.png?v=2"/>
                                            <img
                                                    alt="รูปไอคอนเข้าสู่ระบบ" loading="lazy"
                                                    class="img-fluid -icon-image lazyload"
                                                    width="50"
                                                    height="50"
                                                    data-src="/assets/wm356/images/ic-modal-menu-login.png?v=2"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                            />
                                        </picture>

                                        <div class="-typo-wrapper">
                                            <div class="-typo">{{ __('app.login.login') }}</div>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <div class="-inner-center-body-section">
                                <div class="col-6 -wrapper-box">
                                    <a
                                            href="{{ route('customer.promotion.show') }}"
                                            class="btn -btn-item -promotion-button -menu-center -horizontal lazyload x-bg-position-center"
                                            data-bgset="/assets/wm356/images/btn-register-login-bg.png"
                                    >
                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="/assets/wm356/images/ic-modal-menu-promotion.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="/assets/wm356/images/ic-modal-menu-promotion.png?v=2"/>
                                            <img
                                                    alt="รูปไอคอนโปรโมชั่น" loading="lazy"
                                                    class="img-fluid -icon-image lazyload"
                                                    width="65"
                                                    height="53"
                                                    data-src="/assets/wm356/images/ic-modal-menu-promotion.png?v=2"
                                                    src="/assets/wm356/images/ic-modal-menu-promotion.png?v=2"
                                            />
                                        </picture>

                                        <div class="-typo-wrapper">
                                            <div class="-typo">{{ __('app.login.promotion') }}</div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-6 -wrapper-box">
                                    <a
                                            href="{{ $webconfig->linelink }}"
                                            class="btn -btn-item -line-button -menu-center -horizontal lazyload x-bg-position-center"
                                            target="_blank"
                                            rel="noopener nofollow"
                                            data-bgset="/assets/wm356/images/btn-register-login-bg.png"
                                    >
                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.png"/>
                                            <img
                                                    alt="รูปไอคอนดูหนัง" loading="lazy"
                                                    class="img-fluid -icon-image lazyload"
                                                    width="65"
                                                    height="53"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.png"
                                            />
                                        </picture>

                                        <div class="-typo-wrapper">
                                            <div class="-typo">{{ __('app.register.line_id') }}</div>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <div class="-inner-bottom-body-section"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
