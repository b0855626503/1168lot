@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@push('styles')
    <style>
        .example {
            width: 100%;
            height: auto;
            min-height: 100%;
            overflow: hidden;
        }

        .example img {
            max-width: 100%;
            max-height: auto;
            position: relative;
            vertical-align: middle;
            left: 50%;
            transform: translate(-50%);
            height: 150px;
            width: 150px;
            object-fit: cover;
        }

        .example-cover img {
            object-fit: cover;
        }
    </style>
@endpush

@section('content')
    <div id="main__content" class="x-ez-games-by-category">
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
            <span class="-btn-inner-content-title">{{ __('app.home.promotion') }}</span>
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
                                           data-menu-container=".js-menu-container"
                                           class="x-category-button -category-casio -index-page -category-button-v2  -hoverable">
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
                                           data-menu-container=".js-menu-container"
                                           class="x-category-button -category-slot -index-page -category-button-v2  -hoverable">
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
                                                <i class="fas fa-caret-down d-none d-lg-flex"></i>
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

                                <li class="-list-parent nav-item">


                                    <div class="d-lg-block d-none">
                                        <div id="collapse-brand"
                                             class="x-menu-collapse-container -v2 -category-brand collapse show"
                                             data-parent="#accordion-games">
                                            @foreach($lists as $i => $item)
                                                <button
                                                    type="button"
                                                    class="btn-block -child-collapse non{{ Str::lower($item->id) }}"
                                                    onclick="location.href='{{ route('customer.credit.game.list', ['id' => Str::lower($item->id)]) }}'"
                                                    data-target=".js-game-container"
                                                    data-target-collapse="#collapse-brand"
                                                    data-target-collapse-mobile="#collapse-mobile-brand"
                                                    data-menu-container=".js-menu-container"
                                                    data-button-menu="{{ Str::lower($item->id) }}"
                                                >
                                                    <div class="-child-collapse-wrapper">
                                                        <picture>
                                                            <img
                                                                alt="{{ Str::lower($item->id) }}"
                                                                class="img-fluid -img-btn lazyload"
                                                                width="40"
                                                                height="40"
                                                                data-src="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"
                                                                src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                            />
                                                        </picture>

                                                        <span class="-menu-text-child">{{ $item->name }}</span>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </li>


                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="-games-list-outer-container -has-sidebar">
                <div class="container-fluid -container-fluid">

                    <div class="x-menu-mobile-sidebar-wrapper -v2">
                        <div data-menu-sticky="js-sticky-widget">
                            <ul class="nav -menu-list">
                                {{--                                {{ dd($lists)  }}--}}
                                @foreach($lists as $i => $item)
                                    <li class="nav-item">
                                        <a
                                            href="javascript:void(0);"
                                            class="nav-link js-side-{{ Str::lower($item->id) }}-btn"
                                            aria-label="{{ Str::lower($item->id) }} image provider non{{ Str::lower($item->id) }}"
                                            onclick="location.href='{{ route('customer.credit.game.list', ['id' => Str::lower($item->id)]) }}'"
                                            data-menu-container=".js-menu-container"
                                            data-target-collapse="#collapse-brand"
                                            data-target-collapse-mobile="#collapse-mobile-brand"
                                            data-button-menu="{{ Str::lower($item->id) }}"
                                        >
                                            <div class="-menu-wrapper">
                                                <picture>
                                                    <source type="image/webp"
                                                            data-srcset="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"/>
                                                    <source type="image/png"
                                                            data-srcset="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"/>
                                                    <img
                                                        alt="{{ Str::lower($item->id) }}"
                                                        class="img-fluid -img-btn lazyload"
                                                        width="40"
                                                        height="40"
                                                        data-src="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"
                                                        src="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"
                                                    />
                                                </picture>

                                                <picture>
                                                    <source type="image/webp"
                                                            data-srcset="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"/>
                                                    <source type="image/png"
                                                            data-srcset="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"/>
                                                    <img
                                                        alt="{{ Str::lower($item->id) }}"
                                                        class="img-fluid -img-btn -hover lazyload"
                                                        width="40"
                                                        height="40"
                                                        data-src="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"
                                                        src="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"
                                                    />
                                                </picture>

                                                <span class="-menu-text-child">{{ $item->name }}</span>
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="-games-list-container js-game-scroll-container js-game-container">
                        <div class="-games-list-wrapper">
                            <div class="-game-title-wrapper">
                                <div class="-game-title-inner">
                                    <h2 class="-game-title h3 -shimmer">
                                        {{ $game_name->name }} ({{ __('app.home.freecredit') }})
                                    </h2>
                                </div>

                            </div>

                            <ul class="navbar-nav -slot-provider-page">
                                @foreach($games as $i => $item)
                                    <li class="nav-item">
                                        <div
                                            class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                            data-status="-cannot-entry -untestable">
                                            <div class="-inner-wrapper">


                                                <picture>
                                                    <source type="image/webp"
                                                            data-srcset="{{ $item->image }}"/>
                                                    <source type="image/png"
                                                            data-srcset="{{ $item->image }}"/>
                                                    <img
                                                        alt="smm-{{ $id }} cover image png"
                                                        class="img-fluid lazyload -cover-img"
                                                        width="400"
                                                        height="580"
                                                        data-src="{{ $item->image }}"
                                                        src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                    />
                                                </picture>

                                                <div class="-overlay">
                                                    <div class="-overlay-inner">
                                                        <div class="-wrapper-container">
                                                            <a href="{{ route('customer.credit.game.redirect', [ 'id' => $id , 'name' => $item->code ,'method' => $item->method ]) }}"
                                                               class="js-account-approve-aware -btn -btn-play"
                                                               data-toggle="modal" data-target="#gametechPopup"
                                                               target="gametechPopup">
                                                                <i class="fas fa-play"></i>
                                                                <span class="-text-btn">{{ __('app.home.join') }}</span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="-title">{{ $item->name }}</div>
                                        </div>
                                    </li>
                                @endforeach


                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="js-replace-seo-section-container">

        </section>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct"
            crossorigin="anonymous"></script>
    <script src="{{ asset('js/mdetect.js?v=1') }}"></script>
    <script type="text/javascript">


        let windowObjectReference = null; // global variable
        let previousURL; /* global variable that will store the
                    url currently in the secondary window */
        function openRequestedSingleTab(url, windowName) {

            // window.toSend = $(this);
            const w = 900;
            const h = 500;
            const y = window.top.outerHeight / 2 + window.top.screenY - (h / 2);
            const x = window.top.outerWidth / 2 + window.top.screenX - (w / 2);
            // console.log(windowObjectReference);
            if (windowObjectReference === null || windowObjectReference.closed) {
                windowObjectReference = window.open(url, windowName, `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=${w}, height=${h}, top=${y}, left=${x}`);
                // setTimeout(function () { window.toSend = windowObjectReference }, 1000)

            } else if (previousURL !== url) {
                if (!windowObjectReference.opener) {
                    windowObjectReference = window.open(url, windowName, `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=${w}, height=${h}, top=${y}, left=${x}`);

                } else {
                    windowObjectReference.location.href = url;
                }

                // windowObjectReference = open(url, windowName, `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=${w}, height=${h}, top=${y}, left=${x}`);
                windowObjectReference.focus();
            } else {
                windowObjectReference.close();
            }

            // window.toSend = window.opener;
            previousURL = url;


            return $(this);
            // console.log(windowObjectReference);
            /* explanation: we store the current url in order to compare url
               in the event of another call of this function. */
        }


        $(document).ready(function () {

            const links = document.querySelectorAll(
                "a[target='gametechPopup']"
            );
            for (const link of links) {
                link.addEventListener(
                    "click",
                    (event) => {

                        Toast.fire({
                            icon: 'info',
                            title: '{{ __('app.game.login') }}'
                        })

                        if (MobileEsp.DetectIos()) {
                            // windowObjectReference = open(link.href, 'gametechPopup');
                            window.location.href = link.href;
                            event.preventDefault();
                        } else if (MobileEsp.DetectAndroid()) {
                            // windowObjectReference = window.open(link.href, 'gametechPopup');
                            window.location.href = link.href;
                            event.preventDefault();
                        } else {
                            openRequestedSingleTab(link.href, 'gametechPopup');
                            event.preventDefault();
                        }


                    },
                    false
                );
            }

            // console.log(previousURL);
        });

    </script>
@endpush

