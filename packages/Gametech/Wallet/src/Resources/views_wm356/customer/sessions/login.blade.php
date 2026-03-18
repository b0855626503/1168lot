{{-- extend layout --}}
@extends('wallet::layouts.app')

{{-- page title --}}
@section('title','')


@push('script')
    <script type="application/ld+json">
        {
            "url": "login"
        }

    </script>
@endpush

@push('scripts')
    <script>
        $("form").submit(function (event) {
            let token = $('meta[name="csrf-token"]').attr("content");
            $('<input />').attr('type', 'hidden')
                .attr('name', '_token')
                .attr('value', token)
                .appendTo('form');
            return true;
        });
    </script>
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
@endpush

@section('content')

    <div id="main__content" data-bgset="/assets/wm356/images/index-bg.jpg?v=2"
         class="lazyload x-bg-position-center x-bg-index lazyload">
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
                                        <img loading="lazy" class="img-fluid -slick-item -item-{{ $i+1 }}"
                                             alt="banner-{{ $i+1 }}"
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
                                        <img loading="lazy" class="img-fluid -slick-item -item-{{ $i+1 }}"
                                             alt="banner-{{ $i+1 }}"
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
                                        <img loading="lazy" class="img-fluid -slick-item -item-{{ $i+1 }}"
                                             alt="banner-{{ $i+1 }}"
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
                    <h1 class="-title">{{ $config->content_header }}</h1>
                </div>
            </div>

            <div class="x-category-total-game -v2">
                <div class="container-fluid">
                    <nav class="nav-menu" id="navbarCategory">
                        <ul class="-menu-parent navbar-nav flex-row">

                            <li class="-list-parent nav-item px-lg-2 -category-casino" data-animatable="fadeInUp"
                                data-delay="100">
                                <a href="{{ route('customer.session.index') }}"
                                   class="x-category-button -category-casino -index-page -category-button-v2 -hoverable">
                                    <img loading="lazy" alt="category casino image png" class="-img -default"
                                         width="300" height="82"
                                         src="\assets\wm356\web\ezl-wm-356\img\menu-category-casino.png?v=2"/>

                                    <img loading="lazy" alt="category casino image png" class="-img -hover" width="300"
                                         height="82"
                                         src="\assets\wm356\web\ezl-wm-356\img\menu-category-casino-hover.png?v=2"/>

                                    <span class="-menu-text-main -text-btn-image">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">{{ __('app.home.livecasino') }}</span>
                                                <span class="-text-mobile">{{ __('app.home.casino') }}</span>
                                            </div>
                                        </span>
                                </a>
                            </li>
                            <li class="-list-parent nav-item px-lg-2 -category-slot" data-animatable="fadeInUp"
                                data-delay="150">
                                <a href="{{ route('customer.session.index') }}"
                                   class="x-category-button -category-slot -index-page -category-button-v2 -hoverable">
                                    <img loading="lazy" alt="category slot image png" class="-img -default" width="300"
                                         height="82"
                                         src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot.png?v=2"/>

                                    <img loading="lazy" alt="category slot image png" class="-img -hover" width="300"
                                         height="82"
                                         src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot-hover.png?v=2"/>

                                    <span class="-menu-text-main -text-btn-image">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">{{ __('app.home.slot') }}</span>
                                                <span class="-text-mobile">{{ __('app.home.slot') }}</span>
                                            </div>
                                        </span>
                                </a>
                            </li>
                            <li class="-list-parent nav-item px-lg-2 -category-sport" data-animatable="fadeInUp"
                                data-delay="200">
                                <a href="{{ route('customer.session.index') }}"
                                   class="x-category-button -category-sport -index-page -category-button-v2 -hoverable">
                                    <img loading="lazy" alt="category sport image png" class="-img -default" width="300"
                                         height="82"
                                         src="\assets\wm356\web\ezl-wm-356\img\menu-category-sport.png?v=2"/>

                                    <img loading="lazy" alt="category sport image png" class="-img -hover" width="300"
                                         height="82"
                                         src="\assets\wm356\web\ezl-wm-356\img\menu-category-sport-hover.png?v=2"/>

                                    <span class="-menu-text-main -text-btn-image">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">{{ __('app.home.sport') }}</span>
                                                <span class="-text-mobile">{{ __('app.home.sport') }}</span>
                                            </div>
                                        </span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <div class="x-provider-category -provider_casinos">
                <div class="container-fluid">
                    <div class="-provider-category-wrapper" data-animatable="fadeInUp" data-delay="150">
                        <div class="d-lg-block d-none">
                            <ul class="navbar-nav">
                                <li class="nav-item -provider-card-item -first">
                                    <a href="javascript:void(0);">
                                        <div class="-provider-label-inner">
                                            <div class="-title">Live Casino</div>
                                            <div class="-sub-title">ชั้นนำ</div>
                                        </div>
                                        <img
                                                alt="Category casino first-banner"
                                                class="-img img-fluid lazyload"
                                                width="400"
                                                height="580"
                                                data-src="/assets/wm356/images/index-casino-category-first-banner.png?v=2"
                                                src="https://asset.cloudigame.co/build/admin/img/ezl-default-loading-big.png"
                                        />
                                    </a>
                                </li>
                                @if(isset($games['CASINO']))
                                    @foreach($games['CASINO'] as $k => $item)
                                        <li class="nav-item -provider-card-item -sm-sa-gaming">
                                            <div
                                                    class="x-game-list-item-macro-in-share js-game-list-toggle -big -use-promotion-alert"
                                                    data-status="">
                                                <div class="-inner-wrapper">
                                                    <div class="x-game-badge-component - -big"
                                                         data-animatable="fadeInUp"
                                                         data-delay="400">
                                                        <span></span>
                                                    </div>

                                                    <picture>
                                                        <source type="image/webp"
                                                                data-srcset="{{ $item->filepic }}"/>
                                                        <source type="image/png"
                                                                data-srcset="{{ $item->filepic }}"/>
                                                        <img loading="lazy"
                                                             alt="sm-sa-gaming cover image png"
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

                                                <div class="-title">{{$item['name']}}</div>
                                            </div>
                                        </li>
                                    @endforeach
                                @endif

                            </ul>
                        </div>
                        <div class="d-lg-none d-block">
                            @if(isset($games['CASINO']))
                                <ul class="navbar-nav">
                                    @foreach($games['CASINO'] as $k => $item)
                                        @if($loop->even)
                                            <li class="nav-item -provider-card-item -sm-sa-gaming">
                                                <div
                                                        class="x-game-list-item-macro-in-share js-game-list-toggle -big -use-promotion-alert"
                                                        data-status="">
                                                    <div class="-inner-wrapper">
                                                        <div class="x-game-badge-component - -big"
                                                             data-animatable="fadeInUp"
                                                             data-delay="400">
                                                            <span></span>
                                                        </div>

                                                        <picture>
                                                            <source type="image/webp"
                                                                    data-srcset="{{ $item->filepic }}"/>
                                                            <source type="image/png"
                                                                    data-srcset="{{ $item->filepic }}"/>
                                                            <img
                                                                    alt="sm-sa-gaming cover image png"
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

                                                    <div class="-title">{{$item['name']}}</div>
                                                </div>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                                <ul class="navbar-nav">
                                    @foreach($games['CASINO'] as $k => $item)
                                        @if($loop->odd)
                                            <li class="nav-item -provider-card-item -sm-sa-gaming">
                                                <div
                                                        class="x-game-list-item-macro-in-share js-game-list-toggle -big -use-promotion-alert"
                                                        data-status="">
                                                    <div class="-inner-wrapper">
                                                        <div class="x-game-badge-component - -big"
                                                             data-animatable="fadeInUp"
                                                             data-delay="400">
                                                            <span></span>
                                                        </div>

                                                        <picture>
                                                            <source type="image/webp"
                                                                    data-srcset="{{ $item->filepic }}"/>
                                                            <source type="image/png"
                                                                    data-srcset="{{ $item->filepic }}"/>
                                                            <img
                                                                    alt="sm-sa-gaming cover image png"
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

                                                    <div class="-title">{{$item['name']}}</div>
                                                </div>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="x-provider-category -provider_slots">
                <div class="container-fluid">
                    <div class="-provider-category-wrapper" data-animatable="fadeInUp" data-delay="150">
                        <div class="d-lg-block d-none">
                            <ul class="navbar-nav">
                                <li class="nav-item -provider-card-item -first">
                                    <a href="{{ route('customer.session.index') }}">
                                        <div class="-provider-label-inner">
                                            <div class="-title">สล็อตเกม</div>
                                            <div class="-sub-title">ใหม่ล่าสุด</div>
                                        </div>
                                        <img
                                                alt="Category slot first-banner"
                                                class="-img img-fluid lazyload"
                                                width="400"
                                                height="580"
                                                data-src="/assets/wm356/images/index-slot-category-first-banner.png?v=2"
                                                src="https://asset.cloudigame.co/build/admin/img/ezl-default-loading-big.png"
                                        />
                                    </a>
                                </li>
                                @if(isset($games['SLOT']))
                                    @foreach($games['SLOT'] as $k => $item)

                                        <li class="nav-item -provider-card-item -smm-pg-soft">
                                            <div
                                                    class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                                    data-status="-cannot-entry -untestable">
                                                <div class="-inner-wrapper">
                                                    <div class="x-game-badge-component - -big"
                                                         data-animatable="fadeInUp"
                                                         data-delay="400">
                                                        <span></span>
                                                    </div>

                                                    <picture>
                                                        <source type="image/webp"
                                                                data-srcset="{{ $item->filepic }}"/>
                                                        <source type="image/png"
                                                                data-srcset="{{ $item->filepic }}"/>
                                                        <img
                                                                alt="smm-pg-soft cover image png"
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

                                                <div class="-title">{{$item['name']}}</div>
                                            </div>
                                        </li>

                                    @endforeach
                                @endif


                            </ul>
                        </div>
                        <div class="d-lg-none d-block">
                            @if(isset($games['SLOT']))
                                <ul class="navbar-nav">
                                    @foreach($games['SLOT'] as $k => $item)
                                        @if($loop->even)
                                            <li class="nav-item -provider-card-item -smm-pg-soft">
                                                <div
                                                        class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                                        data-status="-cannot-entry -untestable">
                                                    <div class="-inner-wrapper">
                                                        <div class="x-game-badge-component - -big"
                                                             data-animatable="fadeInUp"
                                                             data-delay="400">
                                                            <span></span>
                                                        </div>

                                                        <picture>
                                                            <source type="image/webp"
                                                                    data-srcset="{{ $item->filepic }}"/>
                                                            <source type="image/png"
                                                                    data-srcset="{{ $item->filepic }}"/>
                                                            <img
                                                                    alt="smm-pg-soft cover image png"
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

                                                    <div class="-title">{{$item['name']}}</div>
                                                </div>
                                            </li>

                                        @endif
                                    @endforeach
                                </ul>
                                <ul class="navbar-nav">
                                    @foreach($games['SLOT'] as $k => $item)
                                        @if($loop->odd)
                                            <li class="nav-item -provider-card-item -smm-pg-soft">
                                                <div
                                                        class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                                        data-status="-cannot-entry -untestable">
                                                    <div class="-inner-wrapper">
                                                        <div class="x-game-badge-component - -big"
                                                             data-animatable="fadeInUp"
                                                             data-delay="400">
                                                            <span></span>
                                                        </div>

                                                        <picture>
                                                            <source type="image/webp"
                                                                    data-srcset="{{ $item->filepic }}"/>
                                                            <source type="image/png"
                                                                    data-srcset="{{ $item->filepic }}"/>
                                                            <img
                                                                    alt="smm-pg-soft cover image png"
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

                                                    <div class="-title">{{$item['name']}}</div>
                                                </div>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="x-lotto-category">
                <div class="container-fluid">
                    <div class="-lotto-category-wrapper" data-animatable="fadeInUp" data-delay="150">
                        <ul class="navbar-nav">
                            @if(isset($games['SPORT']))
                                @foreach($games['SPORT'] as $k => $item)
                                    <li class="nav-item -lotto-card-item">
                                        <div
                                                class="x-game-list-item-macro-in-share js-game-list-toggle -big-with-countdown-dark -cannot-entry -untestable -use-promotion-alert"
                                                data-status="-cannot-entry -untestable">
                                            <div class="-inner-wrapper">


                                                <picture>
                                                    <source type="image/webp"
                                                            data-srcset="{{ $item->filepic }}"/>
                                                    <source type="image/png"
                                                            data-srcset="{{ $item->filepic }}"/>
                                                    <img
                                                            alt="smm-pg-soft cover image png"
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
                                                                <span class="-text-btn">{{ __('app.home.join') }}</span>
                                                            </a>


                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="-title">{{$item['name']}}</div>
                                        </div>


                                    </li>
                                @endforeach
                            @endif
                            @if(isset($games['TRADING']))
                                @foreach($games['TRADING'] as $k => $item)
                                    <li class="nav-item -lotto-card-item">
                                        <div
                                                class="x-game-list-item-macro-in-share js-game-list-toggle -big-with-countdown-dark -cannot-entry -untestable -use-promotion-alert"
                                                data-status="-cannot-entry -untestable">
                                            <div class="-inner-wrapper">


                                                <picture>
                                                    <source type="image/webp"
                                                            data-srcset="{{ $item->filepic }}"/>
                                                    <source type="image/png"
                                                            data-srcset="{{ $item->filepic }}"/>
                                                    <img
                                                            alt="smm-pg-soft cover image png"
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
                                                                <span class="-text-btn">{{ __('app.home.join') }}</span>
                                                            </a>


                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="-title">{{$item['name']}}</div>
                                        </div>


                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </div>
            </div>


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
                                                    <form method="POST" action="{{ route('customer.session.create') }}"
                                                          onsubmit="return;">

                                                        <div
                                                                class="-animatable-container -password-body">
                                                            <input
                                                                    type="text"
                                                                    required
                                                                    autocomplete="off"
                                                                    id="user_name"
                                                                    name="user_name"
                                                                    inputmode="text"
                                                                    placeholder=""
                                                                    class="form-control x-form-control"
                                                                    style="text-transform: lowercase;"
                                                            />
                                                        </div>
                                                        <div class="-x-input-icon flex-column">
                                                            <input type="password" id="password" name="password"
                                                                   required
                                                                   class="form-control x-form-control"
                                                                   placeholder="XXXXXXXX"/>
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


    </div>

@endsection
