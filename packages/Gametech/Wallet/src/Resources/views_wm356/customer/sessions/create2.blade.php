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
                                                srcset="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                        <source type="image/jpg"
                                                srcset="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                        <img class="img-fluid -slick-item -item-{{ $i+1 }}" alt="banner-{{ $i+1 }}"
                                             width="1200"
                                             height="590"
                                             src="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                    </picture>
                                </div>
                            </div>
                        @endforeach
                        @foreach($slides as $i => $item)
                            <div class="-slide-inner-wrapper -slick-item">
                                <div class="-link-wrapper">
                                    <picture>
                                        <source type="image/webp"
                                                srcset="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                        <source type="image/jpg"
                                                srcset="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                        <img class="img-fluid -slick-item -item-{{ $i+1 }}" alt="banner-{{ $i+1 }}"
                                             width="1200"
                                             height="590"
                                             src="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
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
                                                srcset="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                        <source type="image/jpg"
                                                srcset="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                        <img class="img-fluid -slick-item -item-{{ $i+1 }}" alt="banner-{{ $i+1 }}"
                                             width="1200"
                                             height="590"
                                             src="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
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
                <div class="container-fluid">
                    <nav class="nav-menu" id="navbarCategory">
                        <ul class="-menu-parent navbar-nav flex-row">

                            <li class="-list-parent nav-item px-lg-2 -category-casino" data-animatable="fadeInUp"
                                data-delay="100">
                                <a href="javascript:void(0);"
                                   class="x-category-button -category-casino -index-page -category-button-v2 -hoverable">
                                    <img alt="category casino image png" class="-img -default" width="300" height="82"
                                         src="\assets\wm356\web\ezl-wm-356\img\menu-category-casino.png?v=2"/>

                                    <img alt="category casino image png" class="-img -hover" width="300" height="82"
                                         src="\assets\wm356\web\ezl-wm-356\img\menu-category-casino-hover.png?v=2"/>

                                    <span class="-menu-text-main -text-btn-image">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">คาสิโนสด</span>
                                                <span class="-text-mobile">คาสิโน</span>
                                            </div>
                                        </span>
                                </a>
                            </li>
                            <li class="-list-parent nav-item px-lg-2 -category-slot" data-animatable="fadeInUp"
                                data-delay="150">
                                <a href="javascript:void(0);"
                                   class="x-category-button -category-slot -index-page -category-button-v2 -hoverable">
                                    <img alt="category slot image png" class="-img -default" width="300" height="82"
                                         src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot.png?v=2"/>

                                    <img alt="category slot image png" class="-img -hover" width="300" height="82"
                                         src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot-hover.png?v=2"/>

                                    <span class="-menu-text-main -text-btn-image">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">สล็อต</span>
                                                <span class="-text-mobile">สล็อต</span>
                                            </div>
                                        </span>
                                </a>
                            </li>
                            <li class="-list-parent nav-item px-lg-2 -category-sport" data-animatable="fadeInUp"
                                data-delay="200">
                                <a href="javascript:void(0);"
                                   class="x-category-button -category-sport -index-page -category-button-v2 -hoverable">
                                    <img alt="category sport image png" class="-img -default" width="300" height="82"
                                         src="\assets\wm356\web\ezl-wm-356\img\menu-category-sport.png?v=2"/>

                                    <img alt="category sport image png" class="-img -hover" width="300" height="82"
                                         src="\assets\wm356\web\ezl-wm-356\img\menu-category-sport-hover.png?v=2"/>

                                    <span class="-menu-text-main -text-btn-image">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">กีฬา</span>
                                                <span class="-text-mobile">กีฬา</span>
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
                            <li class="nav-item -provider-card-item -sm-sa-gaming">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -use-promotion-alert"
                                     data-status="">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-sa-gaming/ezs-sm-sa-gaming-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-sa-gaming/ezs-sm-sa-gaming-vertical.png"/>
                                            <img
                                                    alt="sm-sa-gaming cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/sm-sa-gaming/ezs-sm-sa-gaming-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>


                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">SA Gaming</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -sm-wm">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -use-promotion-alert"
                                     data-status="">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-wm/ezs-sm-wm-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-wm/ezs-sm-wm-vertical.png"/>
                                            <img
                                                    alt="sm-wm cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/sm-wm/ezs-sm-wm-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>


                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">WM Casino</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -sm-aesexy">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                     data-status="-cannot-entry -untestable">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <img
                                                alt="sm-aesexy cover image png"
                                                class="img-fluid lazyload -cover-img"
                                                width="400"
                                                height="580"
                                                data-src="https://asset.cloudigame.co/build/admin/img/sm-aesexy/ezs-sm-aesexy-vertical.png"
                                                src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                        />

                                        <div class="-overlay">
                                            <div class="-overlay-inner">
                                                <div class="-wrapper-container">
                                                    <a href="#loginModal"
                                                       class="js-account-approve-aware -btn -btn-play"
                                                       data-toggle="modal" data-target="#loginModal">
                                                        <i class="fas fa-play"></i>
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">AE Sexy</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -sm-dream-gaming">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -use-promotion-alert"
                                     data-status="">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-dream-gaming/ezs-sm-dream-gaming-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-dream-gaming/ezs-sm-dream-gaming-vertical.png"/>
                                            <img
                                                    alt="sm-dream-gaming cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/sm-dream-gaming/ezs-sm-dream-gaming-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>


                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">Dream Gaming</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -sm-eg">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                     data-status="-cannot-entry -untestable">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-eg/ezs-sm-eg-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-eg/ezs-sm-eg-vertical.png"/>
                                            <img
                                                    alt="sm-eg cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/sm-eg/ezs-sm-eg-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">Evolution Gaming</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -sm-we">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                     data-status="-cannot-entry -untestable">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-we/ezs-sm-we-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-we/ezs-sm-we-vertical.png"/>
                                            <img
                                                    alt="sm-we cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/sm-we/ezs-sm-we-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">WE</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -wt-bbin-live">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                     data-status="-cannot-entry -untestable">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-bbin-live/ezs-wt-bbin-live-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-bbin-live/ezs-wt-bbin-live-vertical.png"/>
                                            <img
                                                    alt="wt-bbin-live cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt-bbin-live/ezs-wt-bbin-live-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">BBIN Casino</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -wt-pretty-gaming">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -use-promotion-alert"
                                     data-status="">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-pretty-gaming/ezs-wt-pretty-gaming-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-pretty-gaming/ezs-wt-pretty-gaming-vertical.png"/>
                                            <img
                                                    alt="wt-pretty-gaming cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt-pretty-gaming/ezs-wt-pretty-gaming-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>


                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">Pretty Gaming</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -wt-xtreme-gaming">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                     data-status="-cannot-entry -untestable">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <img
                                                alt="wt-xtreme-gaming cover image png"
                                                class="img-fluid lazyload -cover-img"
                                                width="400"
                                                height="580"
                                                data-src="https://asset.cloudigame.co/build/admin/img/wt-xtreme-gaming/ezs-wt-xtreme-gaming-vertical.png"
                                                src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                        />

                                        <div class="-overlay">
                                            <div class="-overlay-inner">
                                                <div class="-wrapper-container">
                                                    <a href="#loginModal"
                                                       class="js-account-approve-aware -btn -btn-play"
                                                       data-toggle="modal" data-target="#loginModal">
                                                        <i class="fas fa-play"></i>
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">Xtreme Gaming</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -wtm-bg">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -use-promotion-alert"
                                     data-status="">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <img
                                                alt="wtm-bg cover image png"
                                                class="img-fluid lazyload -cover-img"
                                                width="400"
                                                height="580"
                                                data-src="https://asset.cloudigame.co/build/admin/img/wtm-bg/ezs-wtm-bg-vertical-animation.gif"
                                                src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                        />

                                        <div class="-overlay">
                                            <div class="-overlay-inner">
                                                <div class="-wrapper-container">
                                                    <a href="#loginModal"
                                                       class="js-account-approve-aware -btn -btn-play"
                                                       data-toggle="modal" data-target="#loginModal">
                                                        <i class="fas fa-play"></i>
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>


                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">Big Gaming</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -wtm-asia-gaming">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                     data-status="-cannot-entry -untestable">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <img
                                                alt="wtm-asia-gaming cover image png"
                                                class="img-fluid lazyload -cover-img"
                                                width="400"
                                                height="580"
                                                data-src="https://asset.cloudigame.co/build/admin/img/wtm-asia-gaming/ezs-wtm-asia-gaming-vertical-animation.gif"
                                                src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                        />

                                        <div class="-overlay">
                                            <div class="-overlay-inner">
                                                <div class="-wrapper-container">
                                                    <a href="#loginModal"
                                                       class="js-account-approve-aware -btn -btn-play"
                                                       data-toggle="modal" data-target="#loginModal">
                                                        <i class="fas fa-play"></i>
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">Asia Gaming</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -wt-n2live">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                     data-status="-cannot-entry -untestable">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-n2live/ezs-wt-n2live-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-n2live/ezs-wt-n2live-vertical.png"/>
                                            <img
                                                    alt="wt-n2live cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt-n2live/ezs-wt-n2live-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">N2 Live</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="x-provider-category -provider_slots">
                <div class="container-fluid">
                    <div class="-provider-category-wrapper" data-animatable="fadeInUp" data-delay="150">
                        <ul class="navbar-nav">
                            <li class="nav-item -provider-card-item -first">
                                <a href="javascript:void(0);">
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
                            <li class="nav-item -provider-card-item -smm-pg-soft">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                     data-status="-cannot-entry -untestable">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/smm-pg-soft/ezs-smm-pg-soft-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/smm-pg-soft/ezs-smm-pg-soft-vertical.png"/>
                                            <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/smm-pg-soft/ezs-smm-pg-soft-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">PGSoft</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -sm-joker">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                     data-status="-cannot-entry -untestable">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-joker/ezs-sm-joker-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-joker/ezs-sm-joker-vertical.png"/>
                                            <img
                                                    alt="sm-joker cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/sm-joker/ezs-sm-joker-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">Joker Gaming</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -sm-jili">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                     data-status="-cannot-entry -untestable">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-jili/ezs-sm-jili-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-jili/ezs-sm-jili-vertical.png"/>
                                            <img
                                                    alt="sm-jili cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/sm-jili/ezs-sm-jili-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">Jili</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -sm-kingmaker">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -use-promotion-alert"
                                     data-status="">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-kingmaker/ezs-sm-kingmaker-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/sm-kingmaker/ezs-sm-kingmaker-vertical.png"/>
                                            <img
                                                    alt="sm-kingmaker cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/sm-kingmaker/ezs-sm-kingmaker-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">Kingmaker</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -wt-bbin-slot">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                     data-status="-cannot-entry -untestable">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-bbin-slot/ezs-wt-bbin-slot-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-bbin-slot/ezs-wt-bbin-slot-vertical.png"/>
                                            <img
                                                    alt="wt-bbin-slot cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt-bbin-slot/ezs-wt-bbin-slot-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">BBIN Slot</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -wt-play-tech">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -use-promotion-alert"
                                     data-status="">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-play-tech/ezs-wt-play-tech-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-play-tech/ezs-wt-play-tech-vertical.png"/>
                                            <img
                                                    alt="wt-play-tech cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt-play-tech/ezs-wt-play-tech-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">PlayTech</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -wt-evo-play">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -use-promotion-alert"
                                     data-status="">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-evo-play/ezs-wt-evo-play-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-evo-play/ezs-wt-evo-play-vertical.png"/>
                                            <img
                                                    alt="wt-evo-play cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt-evo-play/ezs-wt-evo-play-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">Evoplay</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -wtm-endorphina">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -use-promotion-alert"
                                     data-status="">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wtm-endorphina/ezs-wtm-endorphina-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wtm-endorphina/ezs-wtm-endorphina-vertical.png"/>
                                            <img
                                                    alt="wtm-endorphina cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wtm-endorphina/ezs-wtm-endorphina-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">Endorphina</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -wt-cq9">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                     data-status="-cannot-entry -untestable">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-cq9/ezs-wt-cq9-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-cq9/ezs-wt-cq9-vertical.png"/>
                                            <img
                                                    alt="wt-cq9 cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt-cq9/ezs-wt-cq9-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">CQ9</div>
                                </div>
                            </li>
                            <li class="nav-item -provider-card-item -wt-habanero">
                                <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -use-promotion-alert"
                                     data-status="">
                                    <div class="-inner-wrapper">
                                        <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                             data-delay="400">
                                            <span></span>
                                        </div>

                                        <picture>
                                            <source type="image/webp"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-habanero/ezs-wt-habanero-vertical.webp?v=2"/>
                                            <source type="image/png"
                                                    data-srcset="https://asset.cloudigame.co/build/admin/img/wt-habanero/ezs-wt-habanero-vertical.png"/>
                                            <img
                                                    alt="wt-habanero cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt-habanero/ezs-wt-habanero-vertical.png"
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
                                                        <span class="-text-btn">เข้าเล่น</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-title">Habanero</div>
                                </div>
                            </li>
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
                            เข้าสู่ระบบ
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
                                                    <span class="-title">กรอก Username</span>
                                                    <span
                                                            class="-sub-title">Username เพื่อใช้ในการเข้าระบบ</span>
                                                </div>
                                            </div>

                                            <div data-animatable="fadeInRegister" data-offset="0" class="col">
                                                <div class="-fake-inner-body">
                                                    {{--                                                    <form method="post" data-register-v3-form="v3/check-for-login"--}}
                                                    {{--                                                          data-register-step="loginPhoneNumber">--}}
                                                    <form method="POST" action="{{ route('customer.session.create') }}">
                                                        @csrf
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
                                                                ยืนยัน
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <div class="tab-pane" id="tab-content-loginPasswordV3" data-completed-dismiss-modal="">
                                    <div class="x-modal-body-base -v3 -password x-form-register-v3">
                                        <div class="row -register-container-wrapper">
                                            <div class="col">
                                                <div class="x-title-register-modal-v3">
                                                    <span class="-title">รหัสผ่าน</span>
                                                    <span class="-sub-title">กรอกตัวเลขรหัส 6 หลัก เข้าสู่ระบบ <span
                                                                class="js-phone-number -highlight"></span></span>
                                                </div>
                                            </div>

                                            <div data-animatable="fadeInRegister" data-offset="0" class="col">
                                                <div class="x-modal-separator-container x-form-register">
                                                    <div class="-top">
                                                        <div data-animatable="fadeInRegister" data-offset="0"
                                                             class="-animatable-container -password-body">
                                                            <form action="/login-json-check" class="js-login-form">
                                                                <div
                                                                        class="d-flex -password-input-container js-register-v3-input-group">
                                                                    <div
                                                                            class="js-separator-container js-password-container">
                                                                        <input
                                                                                type="password"
                                                                                id="loginPassword_1"
                                                                                name="loginPassword_1"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-password js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="set_password"
                                                                        />
                                                                    </div>

                                                                    <div
                                                                            class="js-separator-container js-password-container">
                                                                        <input
                                                                                type="password"
                                                                                id="loginPassword_2"
                                                                                name="loginPassword_2"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-password js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="set_password"
                                                                        />
                                                                    </div>

                                                                    <div
                                                                            class="js-separator-container js-password-container">
                                                                        <input
                                                                                type="password"
                                                                                id="loginPassword_3"
                                                                                name="loginPassword_3"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-password js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="set_password"
                                                                        />
                                                                    </div>

                                                                    <div
                                                                            class="js-separator-container js-password-container">
                                                                        <input
                                                                                type="password"
                                                                                id="loginPassword_4"
                                                                                name="loginPassword_4"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-password js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="set_password"
                                                                        />
                                                                    </div>

                                                                    <div
                                                                            class="js-separator-container js-password-container">
                                                                        <input
                                                                                type="password"
                                                                                id="loginPassword_5"
                                                                                name="loginPassword_5"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-password js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="set_password"
                                                                        />
                                                                    </div>

                                                                    <div
                                                                            class="js-separator-container js-password-container">
                                                                        <input
                                                                                type="password"
                                                                                id="loginPassword_6"
                                                                                name="loginPassword_6"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-password js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="set_password"
                                                                        />
                                                                    </div>
                                                                </div>

                                                                <div class="d-none js-keypad-number-wrapper">
                                                                    <div class="x-keypad-number-container">
                                                                        <div class="-btn-group-wrapper">
                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="1"
                                                                                    data-target="#loginPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-password-container"}'
                                                                            >
                                                                                1
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="2"
                                                                                    data-target="#loginPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-password-container"}'
                                                                            >
                                                                                2
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="3"
                                                                                    data-target="#loginPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-password-container"}'
                                                                            >
                                                                                3
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="4"
                                                                                    data-target="#loginPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-password-container"}'
                                                                            >
                                                                                4
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="5"
                                                                                    data-target="#loginPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-password-container"}'
                                                                            >
                                                                                5
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="6"
                                                                                    data-target="#loginPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-password-container"}'
                                                                            >
                                                                                6
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="7"
                                                                                    data-target="#loginPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-password-container"}'
                                                                            >
                                                                                7
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="8"
                                                                                    data-target="#loginPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-password-container"}'
                                                                            >
                                                                                8
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="9"
                                                                                    data-target="#loginPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-password-container"}'
                                                                            >
                                                                                9
                                                                            </button>

                                                                            <div
                                                                                    class="btn -btn js-keypad-btn -btn-none"
                                                                                    type="button"
                                                                                    data-key="none"
                                                                                    data-target="#loginPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-password-container"}'
                                                                            ></div>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="0"
                                                                                    data-target="#loginPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-password-container"}'
                                                                            >
                                                                                0
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-backspace"
                                                                                    type="button"
                                                                                    data-key="backspace"
                                                                                    data-target="#loginPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-password-container"}'
                                                                            >
                                                                                <i class="fas fa-backspace"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="-x-input-icon">
                                                                    <input type="hidden" id="usernameV3"
                                                                           inputmode="text"
                                                                           name="username" pattern="[0-9]*" autofocus=""
                                                                           class="form-control x-form-control"
                                                                           placeholder="เบอร์โทรศัพท์"/>
                                                                </div>

                                                                <input type="hidden" id="phoneNumberV3"
                                                                       name="phoneNumber"
                                                                       autofocus="" class="form-control x-form-control"
                                                                       placeholder="เบอร์โทรศัพท์"/>

                                                                <div class="text-center">
                                                                    <button type="submit"
                                                                            class="btn -submit btn-primary my-lg-3 mt-0">
                                                                        ยืนยัน
                                                                    </button>
                                                                </div>
                                                            </form>

                                                            <div class="x-reset-pw-text-container">
                                                                <form
                                                                        method="post"
                                                                        data-register-v3-form="v3/reset-password/request-otp"
                                                                        data-register-step="resetPasswordPhoneNumber"
                                                                        data-tab-next-step="#tab-content-resetPasswordVerifyOtp"
                                                                >
                                                                    <input type="hidden" required=""
                                                                           id="phone_numberV3[phoneNumber]"
                                                                           name="phone_number[phoneNumber]"
                                                                           pattern=".{10,11}" value=""
                                                                           placeholder="095-123-4567"/>
                                                                    <button type="submit" class="-btn-reset-password">
                                                                        <u>ลืมรหัสผ่าน</u>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="-bottom"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane" id="tab-content-resetPasswordVerifyOtp"
                                     data-completed-dismiss-modal="">
                                    <div class="x-modal-body-base -v3 x-form-register-v3">
                                        <div class="row -register-container-wrapper">
                                            <div class="col">
                                                <div class="x-title-register-modal-v3">
                                                    <span class="-title">กรอกรหัส OTP</span>
                                                    <span class="-sub-title">รหัส 4 หลัก ส่งไปยัง <span
                                                                class="js-phone-number -highlight"></span></span>
                                                </div>
                                            </div>

                                            <div data-animatable="fadeInRegister" data-offset="0" class="col">
                                                <div class="x-modal-separator-container x-form-register">
                                                    <div class="-top">
                                                        <div data-animatable="fadeInRegister" data-offset="0"
                                                             class="-animatable-container -otp-body">
                                                            <form
                                                                    method="post"
                                                                    data-register-v3-form="v3/reset-password/verify-otp/_resetPasswordToken"
                                                                    data-register-step="resetPasswordVerifyOtp"
                                                                    data-tab-next-step="#tab-content-resetPasswordSetPassword"
                                                                    name="js-reset-password-v3-otp-form"
                                                            >
                                                                <div
                                                                        class="d-flex -otp-input-container js-register-v3-input-group">
                                                                    <div
                                                                            class="js-separator-container js-login-reset-password-otp-container">
                                                                        <input
                                                                                type="text"
                                                                                id="resetPasswordOtp0"
                                                                                name="otp0"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-otp js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="otp"
                                                                        />
                                                                    </div>

                                                                    <div
                                                                            class="js-separator-container js-login-reset-password-otp-container">
                                                                        <input
                                                                                type="text"
                                                                                id="resetPasswordOtp1"
                                                                                name="otp1"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-otp js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="otp"
                                                                        />
                                                                    </div>

                                                                    <div
                                                                            class="js-separator-container js-login-reset-password-otp-container">
                                                                        <input
                                                                                type="text"
                                                                                id="resetPasswordOtp2"
                                                                                name="otp2"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-otp js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="otp"
                                                                        />
                                                                    </div>

                                                                    <div
                                                                            class="js-separator-container js-login-reset-password-otp-container">
                                                                        <input
                                                                                type="text"
                                                                                id="resetPasswordOtp3"
                                                                                name="otp3"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-otp js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="otp"
                                                                        />
                                                                    </div>
                                                                </div>
                                                                <input type="hidden" id="resetPasswordOtp" name="otp"
                                                                       pattern="[0-9]*" class="form-control mb-3"/>
                                                                <input type="hidden" id="resetPasswordToken"
                                                                       name="resetPasswordToken"
                                                                       class="form-control mb-3"/>

                                                                <div class="d-none js-keypad-number-wrapper">
                                                                    <div class="x-keypad-number-container">
                                                                        <div class="-btn-group-wrapper">
                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="1"
                                                                                    data-target="#resetPasswordOtp0"
                                                                                    data-options='{"maxLength":4,"inputContainer":".js-separator-container.js-login-reset-password-otp-container","targetSubmitForm":"js-reset-password-v3-otp-form"}'
                                                                            >
                                                                                1
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="2"
                                                                                    data-target="#resetPasswordOtp0"
                                                                                    data-options='{"maxLength":4,"inputContainer":".js-separator-container.js-login-reset-password-otp-container","targetSubmitForm":"js-reset-password-v3-otp-form"}'
                                                                            >
                                                                                2
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="3"
                                                                                    data-target="#resetPasswordOtp0"
                                                                                    data-options='{"maxLength":4,"inputContainer":".js-separator-container.js-login-reset-password-otp-container","targetSubmitForm":"js-reset-password-v3-otp-form"}'
                                                                            >
                                                                                3
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="4"
                                                                                    data-target="#resetPasswordOtp0"
                                                                                    data-options='{"maxLength":4,"inputContainer":".js-separator-container.js-login-reset-password-otp-container","targetSubmitForm":"js-reset-password-v3-otp-form"}'
                                                                            >
                                                                                4
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="5"
                                                                                    data-target="#resetPasswordOtp0"
                                                                                    data-options='{"maxLength":4,"inputContainer":".js-separator-container.js-login-reset-password-otp-container","targetSubmitForm":"js-reset-password-v3-otp-form"}'
                                                                            >
                                                                                5
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="6"
                                                                                    data-target="#resetPasswordOtp0"
                                                                                    data-options='{"maxLength":4,"inputContainer":".js-separator-container.js-login-reset-password-otp-container","targetSubmitForm":"js-reset-password-v3-otp-form"}'
                                                                            >
                                                                                6
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="7"
                                                                                    data-target="#resetPasswordOtp0"
                                                                                    data-options='{"maxLength":4,"inputContainer":".js-separator-container.js-login-reset-password-otp-container","targetSubmitForm":"js-reset-password-v3-otp-form"}'
                                                                            >
                                                                                7
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="8"
                                                                                    data-target="#resetPasswordOtp0"
                                                                                    data-options='{"maxLength":4,"inputContainer":".js-separator-container.js-login-reset-password-otp-container","targetSubmitForm":"js-reset-password-v3-otp-form"}'
                                                                            >
                                                                                8
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="9"
                                                                                    data-target="#resetPasswordOtp0"
                                                                                    data-options='{"maxLength":4,"inputContainer":".js-separator-container.js-login-reset-password-otp-container","targetSubmitForm":"js-reset-password-v3-otp-form"}'
                                                                            >
                                                                                9
                                                                            </button>

                                                                            <div
                                                                                    class="btn -btn js-keypad-btn -btn-none"
                                                                                    type="button"
                                                                                    data-key="none"
                                                                                    data-target="#resetPasswordOtp0"
                                                                                    data-options='{"maxLength":4,"inputContainer":".js-separator-container.js-login-reset-password-otp-container","targetSubmitForm":"js-reset-password-v3-otp-form"}'
                                                                            ></div>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="0"
                                                                                    data-target="#resetPasswordOtp0"
                                                                                    data-options='{"maxLength":4,"inputContainer":".js-separator-container.js-login-reset-password-otp-container","targetSubmitForm":"js-reset-password-v3-otp-form"}'
                                                                            >
                                                                                0
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-backspace"
                                                                                    type="button"
                                                                                    data-key="backspace"
                                                                                    data-target="#resetPasswordOtp0"
                                                                                    data-options='{"maxLength":4,"inputContainer":".js-separator-container.js-login-reset-password-otp-container","targetSubmitForm":"js-reset-password-v3-otp-form"}'
                                                                            >
                                                                                <i class="fas fa-backspace"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="text-center">
                                                                    <button type="submit"
                                                                            class="btn -submit btn-primary my-lg-3 mt-0">
                                                                        ยืนยัน
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    <div class="-bottom"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane" id="tab-content-resetPasswordSetPassword"
                                     data-completed-dismiss-modal="">
                                    <div class="x-modal-body-base -v3 -password x-form-register-v3">
                                        <div class="row -register-container-wrapper">
                                            <div class="col">
                                                <div class="x-title-register-modal-v3">
                                                    <span class="-title">ตั้งรหัสผ่านใหม่</span>
                                                    <span
                                                            class="-sub-title">กรอกตัวเลขรหัส 6 หลัก เพื่อใช้เข้าสู่ระบบ</span>
                                                </div>
                                            </div>

                                            <div data-animatable="fadeInRegister" data-offset="0" class="col">
                                                <div class="x-modal-separator-container x-form-register">
                                                    <div class="-top">
                                                        <div data-animatable="fadeInRegister" data-offset="0"
                                                             class="-animatable-container -password-body">
                                                            <form
                                                                    method="post"
                                                                    data-register-v3-form="v3/reset-password/set-password/_resetPasswordSetPassword"
                                                                    data-register-step="resetPasswordSetPassword"
                                                                    data-tab-next-step="#tab-content-resetPasswordResult"
                                                            >
                                                                <div
                                                                        class="d-flex -password-input-container js-register-v3-input-group">
                                                                    <div
                                                                            class="js-separator-container js-reset-password-container">
                                                                        <input
                                                                                type="password"
                                                                                id="resetPasswordSetPassword_1"
                                                                                name="resetPasswordSetPassword_1"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-password js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="set_password"
                                                                        />
                                                                    </div>

                                                                    <div
                                                                            class="js-separator-container js-reset-password-container">
                                                                        <input
                                                                                type="password"
                                                                                id="resetPasswordSetPassword_2"
                                                                                name="resetPasswordSetPassword_2"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-password js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="set_password"
                                                                        />
                                                                    </div>

                                                                    <div
                                                                            class="js-separator-container js-reset-password-container">
                                                                        <input
                                                                                type="password"
                                                                                id="resetPasswordSetPassword_3"
                                                                                name="resetPasswordSetPassword_3"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-password js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="set_password"
                                                                        />
                                                                    </div>

                                                                    <div
                                                                            class="js-separator-container js-reset-password-container">
                                                                        <input
                                                                                type="password"
                                                                                id="resetPasswordSetPassword_4"
                                                                                name="resetPasswordSetPassword_4"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-password js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="set_password"
                                                                        />
                                                                    </div>

                                                                    <div
                                                                            class="js-separator-container js-reset-password-container">
                                                                        <input
                                                                                type="password"
                                                                                id="resetPasswordSetPassword_5"
                                                                                name="resetPasswordSetPassword_5"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-password js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="set_password"
                                                                        />
                                                                    </div>

                                                                    <div
                                                                            class="js-separator-container js-reset-password-container">
                                                                        <input
                                                                                type="password"
                                                                                id="resetPasswordSetPassword_6"
                                                                                name="resetPasswordSetPassword_6"
                                                                                inputmode="text"
                                                                                readonly=""
                                                                                pattern="[0-9]*"
                                                                                autofocus="1"
                                                                                class="-digit-password js-otp-input"
                                                                                data-separator-input="true"
                                                                                data-type="set_password"
                                                                        />
                                                                    </div>
                                                                </div>

                                                                <input type="hidden" id="resetPasswordSetPasswordToken"
                                                                       name="resetPasswordSetPasswordToken"/>

                                                                <div class="d-none js-keypad-number-wrapper">
                                                                    <div class="x-keypad-number-container">
                                                                        <div class="-btn-group-wrapper">
                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="1"
                                                                                    data-target="#resetPasswordSetPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-reset-password-container"}'
                                                                            >
                                                                                1
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="2"
                                                                                    data-target="#resetPasswordSetPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-reset-password-container"}'
                                                                            >
                                                                                2
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="3"
                                                                                    data-target="#resetPasswordSetPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-reset-password-container"}'
                                                                            >
                                                                                3
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="4"
                                                                                    data-target="#resetPasswordSetPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-reset-password-container"}'
                                                                            >
                                                                                4
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="5"
                                                                                    data-target="#resetPasswordSetPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-reset-password-container"}'
                                                                            >
                                                                                5
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="6"
                                                                                    data-target="#resetPasswordSetPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-reset-password-container"}'
                                                                            >
                                                                                6
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="7"
                                                                                    data-target="#resetPasswordSetPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-reset-password-container"}'
                                                                            >
                                                                                7
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="8"
                                                                                    data-target="#resetPasswordSetPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-reset-password-container"}'
                                                                            >
                                                                                8
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="9"
                                                                                    data-target="#resetPasswordSetPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-reset-password-container"}'
                                                                            >
                                                                                9
                                                                            </button>

                                                                            <div
                                                                                    class="btn -btn js-keypad-btn -btn-none"
                                                                                    type="button"
                                                                                    data-key="none"
                                                                                    data-target="#resetPasswordSetPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-reset-password-container"}'
                                                                            ></div>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-keypad"
                                                                                    type="button"
                                                                                    data-key="0"
                                                                                    data-target="#resetPasswordSetPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-reset-password-container"}'
                                                                            >
                                                                                0
                                                                            </button>

                                                                            <button
                                                                                    class="btn -btn js-keypad-btn -btn-backspace"
                                                                                    type="button"
                                                                                    data-key="backspace"
                                                                                    data-target="#resetPasswordSetPassword_1"
                                                                                    data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-reset-password-container"}'
                                                                            >
                                                                                <i class="fas fa-backspace"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="text-center">
                                                                    <button type="submit"
                                                                            class="btn -submit btn-primary my-lg-3 mt-0">
                                                                        ยืนยัน
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    <div class="-bottom"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane" id="tab-content-resetPasswordResult"
                                     data-completed-dismiss-modal="#loginModal">
                                    <div class="x-modal-body-base -v3 x-form-register-v3">
                                        <div class="row -register-container-wrapper">
                                            <div data-animatable="fadeInRegister" data-offset="0" class="col">
                                                <div
                                                        class="text-center d-flex flex-column justify-content-center h-100">
                                                    <div class="text-center">
                                                        <img
                                                                alt="สมัครสมาชิก SUCCESS"
                                                                class="js-ic-success -success-ic img-fluid"
                                                                width="150"
                                                                height="150"
                                                                src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/animated-register-success.png"
                                                        />
                                                    </div>

                                                    <div class="-title">อัปเดตรหัสผ่านของคุณเรียบร้อย!</div>
                                                    <div class="-sub-title">ไปหน้าเข้าสู่ระบบอัตโนมัติใน</div>
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
