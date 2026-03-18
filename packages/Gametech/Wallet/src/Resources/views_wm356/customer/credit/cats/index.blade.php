@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



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
                        <div class="x-quick-transaction-buttons js-quick-transaction-buttons">
                            <a class="btn -btn -promotion -vertical" href="{{ route('customer.promotion.index') }}"
                               target="_blank"
                               rel="noopener nofollow">
                            <span class="-ic-wrapper"> <img alt="โปรโมชั่นสุดคุ้ม เพื่อลูกค้าคนสำคัญ"
                                                            class="img-fluid -ic" width="40" height="40"
                                                            src="/assets/wm356/images/ic-quick-transaction-button-promotion.png?v=2"/></span>

                                <span class="-btn-inner-content">
            <span class="-btn-inner-content-title">โปรโมชั่น</span>
        </span>
                            </a>

                            <button
                                class="btn -btn -deposit x-bg-position-center lazyloaded"
                                data-toggle="modal"
                                data-target="#depositModal"
                                data-bgset="/assets/wm356/images/btn-deposit-bg.png?v=2"
                                style="background-image: url('/assets/wm356/images/btn-deposit-bg.png?v=2');"
                            >
                            <span class="-ic-wrapper"> <img alt="ฝากเงินง่ายๆ ด้วยระบบออโต้ การันตี 1 นาที"
                                                            class="img-fluid -ic" width="40" height="40"
                                                            src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.png"/></span>

                                <span class="-btn-inner-content">
            <span class="-btn-inner-content-title">{{ __('app.home.refill') }}</span>
        </span>
                            </button>

                            <button
                                class="btn -btn -withdraw x-bg-position-center lazyloaded"
                                data-toggle="modal"
                                data-target="#withdrawModal"
                                data-bgset="/assets/wm356/images/btn-withdraw-bg.png?v=2"
                                style="background-image: url('/assets/wm356/images/btn-withdraw-bg.png?v=2');"
                            >
                            <span class="-ic-wrapper"> <img alt="ถอนเงินง่ายๆ ด้วยระบบออโต้ การันตี เท่าไหร่ก็จ่าย"
                                                            class="img-fluid -ic" width="40" height="40"
                                                            src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.png"/></span>

                                <span class="-btn-inner-content">
            <span class="-btn-inner-content-title">{{ __('app.home.withdraw') }}</span>
        </span>
                            </button>
                        </div>
                        <nav class="nav-menu" id="navbarCategory">
                            <ul class="-menu-parent navbar-nav js-menu-container" id="accordion-games">


                                <li class="-list-parent nav-item">
                                    <div class="d-lg-block d-none">
                                        <a href="{{ route('customer.credit.cats.list', ['id' => 'casino']) }}"
                                           onclick="location.href='{{ route('customer.credit.cats.list', ['id' => 'casino']) }}"
                                           data-menu-container=".js-menu-container"
                                           class="x-category-button -category-casino -category-button-v2 -hoverable">
                                            <img alt="category casino image png" class="-img -default" width="300"
                                                 height="82"
                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-casino.png?v=2"/>

                                            <img alt="category casino image png" class="-img -hover" width="300"
                                                 height="82"
                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-casino-hover.png?v=2"/>

                                            <span class="-menu-text-main -text-btn-image">
                                                    <div class="-menu-text-wrapper">
                                                        <span class="-text-desktop">{{ __('app.home.casino') }}</span>
                                                        <span class="-text-mobile">{{ __('app.home.casino') }}</span>
                                                    </div>
                                                </span>
                                        </a>
                                    </div>

                                    <div class="d-lg-none d-block w-100">
                                        <a href="{{ route('customer.credit.cats.list', ['id' => 'casino']) }}"
                                           class="x-category-button -category-casino -index-page -category-button-v2 -hoverable">
                                            <img alt="category casino image png" class="-img -default" width="300"
                                                 height="82"
                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-casino.png?v=2"/>

                                            <img alt="category casino image png" class="-img -hover" width="300"
                                                 height="82"
                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-casino-hover.png?v=2"/>

                                            <span class="-menu-text-main -text-btn-image">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">{{ __('app.home.casino') }}</span>
                                                <span class="-text-mobile">{{ __('app.home.casino') }}</span>
                                            </div>
                                        </span>
                                        </a>
                                    </div>
                                </li>
                                <li class="-list-parent nav-item">
                                    <div class="d-lg-block d-none">
                                        <a href="{{ route('customer.credit.cats.list', ['id' => 'slot']) }}"
                                           onclick="location.href='{{ route('customer.credit.cats.list', ['id' => 'slot']) }}"
                                           data-menu-container=".js-menu-container"
                                           class="x-category-button -category-slot -category-button-v2 -hoverable">
                                            <img alt="category slot image png" class="-img -default" width="300"
                                                 height="82"
                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot.png?v=2"/>

                                            <img alt="category slot image png" class="-img -hover" width="300"
                                                 height="82"
                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot-hover.png?v=2"/>

                                            <span class="-menu-text-main -text-btn-image">
                                                    <div class="-menu-text-wrapper">
                                                        <span class="-text-desktop">{{ __('app.home.slot') }}</span>
                                                        <span class="-text-mobile">{{ __('app.home.slot') }}</span>
                                                    </div>
                                                </span>
                                        </a>
                                    </div>

                                    <div class="d-lg-none d-block w-100">
                                        <a href="{{ route('customer.credit.cats.list', ['id' => 'slot']) }}"
                                           class="x-category-button -category-slot -index-page -category-button-v2 -hoverable">
                                            <img alt="category slot image png" class="-img -default" width="300"
                                                 height="82"
                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot.png?v=2"/>

                                            <img alt="category slot image png" class="-img -hover" width="300"
                                                 height="82"
                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot-hover.png?v=2"/>

                                            <span class="-menu-text-main -text-btn-image">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">{{ __('app.home.slot') }}</span>
                                                <span class="-text-mobile">{{ __('app.home.slot') }}</span>
                                            </div>
                                        </span>
                                        </a>
                                    </div>
                                </li>
                                <li class="-list-parent nav-item">
                                    <div class="d-lg-block d-none">
                                        <a href="{{ route('customer.credit.cats.list', ['id' => 'sport']) }}"
                                           onclick="location.href='{{ route('customer.credit.cats.list', ['id' => 'sport']) }}"
                                           data-menu-container=".js-menu-container"
                                           class="x-category-button -category-sport -category-button-v2 -hoverable">
                                            <img alt="category sport image png" class="-img -default" width="300"
                                                 height="82"
                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-sport.png?v=2"/>

                                            <img alt="category sport image png" class="-img -hover" width="300"
                                                 height="82"
                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-sport-hover.png?v=2"/>

                                            <span class="-menu-text-main -text-btn-image">
                                                    <div class="-menu-text-wrapper">
                                                        <span class="-text-desktop">{{ __('app.home.sport') }}</span>
                                                        <span class="-text-mobile">{{ __('app.home.sport') }}</span>
                                                    </div>
                                                </span>
                                        </a>
                                    </div>

                                    <div class="d-lg-none d-block w-100">
                                        <a href="{{ route('customer.credit.cats.list', ['id' => 'sport']) }}"
                                           class="x-category-button -category-sport -index-page -category-button-v2 -hoverable">
                                            <img alt="category sport image png" class="-img -default" width="300"
                                                 height="82"
                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-sport.png?v=2"/>

                                            <img alt="category sport image png" class="-img -hover" width="300"
                                                 height="82"
                                                 src="\assets\wm356\web\ezl-wm-356\img\menu-category-sport-hover.png?v=2"/>

                                            <span class="-menu-text-main -text-btn-image">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">{{ __('app.home.sport') }}</span>
                                                <span class="-text-mobile">{{ __('app.home.sport') }}</span>
                                            </div>
                                        </span>
                                        </a>
                                    </div>
                                </li>


                            </ul>
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
                                                                <a
                                                                    href="{{ route('customer.credit.game.list', ['id' => Str::lower($item->id)]) }}"
                                                                    class="-btn -btn-play">
                                                                    <i class="fas fa-play"></i>
                                                                    <span class="-text-btn">{{ __('app.home.join') }}</span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

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
@endpush
