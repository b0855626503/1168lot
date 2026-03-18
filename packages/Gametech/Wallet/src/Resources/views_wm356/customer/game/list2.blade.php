@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



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
            <span class="-btn-inner-content-title">ฝากเงิน</span>
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
            <span class="-btn-inner-content-title">ถอนเงิน</span>
        </span>
                            </button>
                        </div>
                        <nav class="nav-menu" id="navbarCategory">
                            <ul class="-menu-parent navbar-nav js-menu-container" id="accordion-games">


                                <li class="-list-parent nav-item">
                                    <a
                                        href="{{ route('customer.cats.list', ['id' => 'slot']) }}"
                                        onclick="location.href='pgsoft2"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-collapse-menu="js-brand"
                                        data-button-menu="pgsoft2"
                                        class="x-category-button active d-lg-none js-brand -category-brand -category-button-icon -hoverable"
                                    >
                                        <div class="-menu-text-wrapper">
                                            <span class="-text-desktop">สล็อต</span>
                                            <span class="-text-mobile">สล็อต</span>
                                        </div>
                                    </a>

                                    <a
                                        href="#0"
                                        data-toggle="collapse"
                                        data-target="#collapse-brand"
                                        aria-expanded="false"
                                        aria-controls="collapse-brand"
                                        class="x-category-button -is-collapse -category-brand collapse show active js-brand d-lg-flex d-none -category-button-v2 -hoverable"
                                    >
                                        <img alt="category slot image png" class="-img -default" width="300" height="82"
                                             src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot.png?v=2"/>

                                        <img alt="category slot image png" class="-img -hover" width="300" height="82"
                                             src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot-hover.png?v=2"/>

                                        <span class="-menu-text-main -text-btn-image">
                                                <div class="-menu-text-wrapper">
                                                    <span class="-text-desktop">สล็อต</span>
                                                    <span class="-text-mobile">สล็อต</span>
                                                </div>
                                                <i class="fas fa-caret-down d-none d-lg-flex"></i>
                                            </span>
                                    </a>

                                    <div class="d-lg-block d-none">
                                        <div id="collapse-brand"
                                             class="x-menu-collapse-container -v2 -category-brand collapse show"
                                             data-parent="#accordion-games">

                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonjoker"
                                                data-target=".js-game-container"
                                                data-href-push-state="joker.html.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="joker"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp"
                                                                data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.webp?v=2"/>
                                                        <source type="image/png"
                                                                data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.png"/>
                                                        <img
                                                            alt="joker"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">Joker</span>
                                                </div>
                                            </button>

                                        </div>
                                    </div>
                                </li>
                                <li class="-list-parent nav-item">
                                    <a
                                        href="{{ route('customer.cats.list', ['id' => 'casino']) }}"
                                        onclick="location.href='allbet"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand-casino"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-collapse-menu="js-brand"
                                        data-button-menu="allbet"
                                        class="x-category-button active d-lg-none js-brand -category-brand -category-button-icon -hoverable"
                                    >
                                        <div class="-menu-text-wrapper">
                                            <span class="-text-desktop">คาสิโน</span>
                                            <span class="-text-mobile">คาสิโน</span>
                                        </div>
                                    </a>

                                    <a
                                        href="#0"
                                        data-toggle="collapse"
                                        data-target="#collapse-brand-casino"
                                        aria-expanded="false"
                                        aria-controls="collapse-brand-casino"
                                        class="x-category-button -is-collapse -category-brand collapse show active js-brand d-lg-flex d-none -category-button-v2 -hoverable"
                                    >
                                        <img alt="category slot image png" class="-img -default" width="300" height="82"
                                             src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot.png?v=2"/>

                                        <img alt="category slot image png" class="-img -hover" width="300" height="82"
                                             src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot-hover.png?v=2"/>

                                        <span class="-menu-text-main -text-btn-image">
                                                <div class="-menu-text-wrapper">
                                                    <span class="-text-desktop">คาสิโน</span>
                                                    <span class="-text-mobile">คาสิโน</span>
                                                </div>
                                                <i class="fas fa-caret-down d-none d-lg-flex"></i>
                                            </span>
                                    </a>

                                    <div class="d-lg-block d-none">
                                        <div id="collapse-brand-casino"
                                             class="x-menu-collapse-container -v2 -category-brand collapse show"
                                             data-parent="#accordion-games">

                                                                                        <button
                                                                                            type="button"
                                                                                            class="btn-block -child-collapse nonallbet"
                                                                                            data-target=".js-game-container"
                                                                                            data-href-push-state="allbet.html'"
                                                                                            data-target-collapse="#collapse-brand"
                                                                                            data-target-collapse-mobile="#collapse-mobile-brand"
                                                                                            data-menu-container=".js-menu-container"
                                                                                            data-button-menu="allbet"
                                                                                        >
                                                                                            <div class="-child-collapse-wrapper">
                                                                                                <picture>
                                                                                                    <source type="image/webp"
                                                                                                            data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.webp?v=2"/>
                                                                                                    <source type="image/png"
                                                                                                            data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.png"/>
                                                                                                    <img
                                                                                                        alt="joker"
                                                                                                        class="img-fluid -img-btn lazyload"
                                                                                                        width="40"
                                                                                                        height="40"
                                                                                                        data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.png"
                                                                                                        src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                                                                    />
                                                                                                </picture>

                                                                                                <span class="-menu-text-child">Allbet</span>
                                                                                            </div>
                                                                                        </button>

                                        </div>
                                    </div>
                                </li>
                                <li class="-list-parent nav-item">
                                    <a
                                        href="{{ route('customer.cats.list', ['id' => 'sport']) }}"
                                        onclick="location.href='cockfight"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand-sport"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-collapse-menu="js-brand"
                                        data-button-menu="cockfight"
                                        class="x-category-button active d-lg-none js-brand -category-brand -category-button-icon -hoverable"
                                    >
                                        <div class="-menu-text-wrapper">
                                            <span class="-text-desktop">กีฬา</span>
                                            <span class="-text-mobile">กีฬา</span>
                                        </div>
                                    </a>

                                    <a
                                        href="#0"
                                        data-toggle="collapse"
                                        data-target="#collapse-brand-sport"
                                        aria-expanded="false"
                                        aria-controls="collapse-brand-sport"
                                        class="x-category-button -is-collapse -category-brand collapse show active js-brand d-lg-flex d-none -category-button-v2 -hoverable"
                                    >
                                        <img alt="category slot image png" class="-img -default" width="300" height="82"
                                             src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot.png?v=2"/>

                                        <img alt="category slot image png" class="-img -hover" width="300" height="82"
                                             src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot-hover.png?v=2"/>

                                        <span class="-menu-text-main -text-btn-image">
                                                <div class="-menu-text-wrapper">
                                                    <span class="-text-desktop">กีฬา</span>
                                                    <span class="-text-mobile">กีฬา</span>
                                                </div>
                                                <i class="fas fa-caret-down d-none d-lg-flex"></i>
                                            </span>
                                    </a>

                                    <div class="d-lg-block d-none">
                                        <div id="collapse-brand-sport"
                                             class="x-menu-collapse-container -v2 -category-brand collapse show"
                                             data-parent="#accordion-games">

                                            {{--                                            <button--}}
                                            {{--                                                type="button"--}}
                                            {{--                                                class="btn-block -child-collapse nonjoker"--}}
                                            {{--                                                data-target=".js-game-container"--}}
                                            {{--                                                data-href-push-state="joker.html.html'"--}}
                                            {{--                                                data-target-collapse="#collapse-brand"--}}
                                            {{--                                                data-target-collapse-mobile="#collapse-mobile-brand"--}}
                                            {{--                                                data-menu-container=".js-menu-container"--}}
                                            {{--                                                data-button-menu="joker"--}}
                                            {{--                                            >--}}
                                            {{--                                                <div class="-child-collapse-wrapper">--}}
                                            {{--                                                    <picture>--}}
                                            {{--                                                        <source type="image/webp"--}}
                                            {{--                                                                data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.webp?v=2"/>--}}
                                            {{--                                                        <source type="image/png"--}}
                                            {{--                                                                data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.png"/>--}}
                                            {{--                                                        <img--}}
                                            {{--                                                            alt="joker"--}}
                                            {{--                                                            class="img-fluid -img-btn lazyload"--}}
                                            {{--                                                            width="40"--}}
                                            {{--                                                            height="40"--}}
                                            {{--                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.png"--}}
                                            {{--                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                                            {{--                                                        />--}}
                                            {{--                                                    </picture>--}}

                                            {{--                                                    <span class="-menu-text-child">Joker</span>--}}
                                            {{--                                                </div>--}}
                                            {{--                                            </button>--}}

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
                    {{--                    <div class="x-menu-mobile-sidebar-wrapper -v2">--}}
                    {{--                        <div data-menu-sticky="js-sticky-widget">--}}
                    {{--                            <ul class="nav -menu-list">--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-joker-btn"--}}
                    {{--                                        aria-label="joker image provider nonjoker"--}}
                    {{--                                        onclick="location.href='joker.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="joker"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="joker"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="joker"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Joker</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-jili-btn"--}}
                    {{--                                        aria-label="jili image provider nonjili"--}}
                    {{--                                        onclick="location.href='jili.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="jili"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-jili.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-jili.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="jili"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-jili.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-jili.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-jili.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="jili"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-jili.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Jili</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-bbinslot-btn"--}}
                    {{--                                        aria-label="bbinslot image provider nonbbinslot"--}}
                    {{--                                        onclick="location.href='bbinslot.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="bbinslot"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-bbin-slot.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-bbin-slot.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="bbinslot"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-bbin-slot.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-bbin-slot.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-bbin-slot.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="bbinslot"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-bbin-slot.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">BBIN</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-wmslot-btn"--}}
                    {{--                                        aria-label="wmslot image provider nonwmslot"--}}
                    {{--                                        onclick="location.href='wmslot.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="wmslot"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-wm-slot.webp?v=2"/>--}}
                    {{--                                                <source type="image/png?v=2"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-wm-slot.png?v=2"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="wmslot"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="build/images/logo-symbol-dark-wt-wm-slot.png?v=2"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-wm-slot.webp?v=2"/>--}}
                    {{--                                                <source type="image/png?v=2"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-wm-slot.png?v=2"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="wmslot"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="build/images/logo-symbol-dark-wt-wm-slot.png?v=2"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">World Match</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-evoplay-btn"--}}
                    {{--                                        aria-label="evoplay image provider nonevoplay"--}}
                    {{--                                        onclick="location.href='evoplay.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="evoplay"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-evo-play.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-evo-play.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="evoplay"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-evo-play.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-evo-play.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-evo-play.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="evoplay"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-evo-play.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Evoplay</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-kingmaker-btn"--}}
                    {{--                                        aria-label="kingmaker image provider nonkingmaker"--}}
                    {{--                                        onclick="location.href='kingmaker.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="kingmaker"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-kingmaker.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-kingmaker.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="kingmaker"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-kingmaker.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-kingmaker.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-kingmaker.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="kingmaker"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-kingmaker.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Kingmaker</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-goldy-btn"--}}
                    {{--                                        aria-label="goldy image provider nongoldy"--}}
                    {{--                                        onclick="location.href='goldy.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="goldy"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-goldy.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-goldy.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="goldy"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-goldy.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-goldy.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-goldy.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="goldy"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-goldy.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Goldy</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-endorphina-btn"--}}
                    {{--                                        aria-label="endorphina image provider nonendorphina"--}}
                    {{--                                        onclick="location.href='endorphina.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="endorphina"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-endorphina.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-endorphina.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="endorphina"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-endorphina.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-endorphina.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-endorphina.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="endorphina"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-endorphina.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Endor phina</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-spinix-btn"--}}
                    {{--                                        aria-label="spinix image provider nonspinix"--}}
                    {{--                                        onclick="location.href='spinix.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="spinix"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-spinix.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-spinix.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="spinix"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-spinix.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-spinix.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-spinix.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="spinix"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-spinix.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">SpiniX</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-mpoker-btn"--}}
                    {{--                                        aria-label="mpoker image provider nonmpoker"--}}
                    {{--                                        onclick="location.href='mpoker.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="mpoker"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-mpoker.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-mpoker.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="mpoker"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-mpoker.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-mpoker.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-mpoker.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="mpoker"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-mpoker.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">MPoker</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-dragoonsoft-btn"--}}
                    {{--                                        aria-label="dragoonsoft image provider nondragoonsoft"--}}
                    {{--                                        onclick="location.href='dragoonsoft.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="dragoonsoft"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-dragoon-soft.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-dragoon-soft.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="dragoonsoft"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-dragoon-soft.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-dragoon-soft.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-dragoon-soft.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="dragoonsoft"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-dragoon-soft.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Dragoon Soft</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-nextspin-btn"--}}
                    {{--                                        aria-label="nextspin image provider nonnextspin"--}}
                    {{--                                        onclick="location.href='nextspin.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="nextspin"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-next-spin.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-next-spin.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="nextspin"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-next-spin.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-next-spin.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-next-spin.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="nextspin"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-next-spin.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Next Spin</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-rich88-btn"--}}
                    {{--                                        aria-label="rich88 image provider nonrich88"--}}
                    {{--                                        onclick="location.href='rich88.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="rich88"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-rich88.webp?v=2"/>--}}
                    {{--                                                <source type="image/png?v=2"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-rich88.png?v=2"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="rich88"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="build/images/logo-symbol-dark-wt-rich88.png?v=2"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-rich88.webp?v=2"/>--}}
                    {{--                                                <source type="image/png?v=2"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-rich88.png?v=2"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="rich88"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="build/images/logo-symbol-dark-wt-rich88.png?v=2"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Rich88</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-ameba-btn"--}}
                    {{--                                        aria-label="ameba image provider nonameba"--}}
                    {{--                                        onclick="location.href='ameba.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="ameba"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-ameba.webp?v=2"/>--}}
                    {{--                                                <source type="image/png?v=2"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-ameba.png?v=2"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="ameba"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="build/images/logo-symbol-dark-wt-ameba.png?v=2"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-ameba.webp?v=2"/>--}}
                    {{--                                                <source type="image/png?v=2"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-ameba.png?v=2"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="ameba"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="build/images/logo-symbol-dark-wt-ameba.png?v=2"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Ameba</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-kalamba-btn"--}}
                    {{--                                        aria-label="kalamba image provider nonkalamba"--}}
                    {{--                                        onclick="location.href='kalamba.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="kalamba"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-kalamba.webp?v=2"/>--}}
                    {{--                                                <source type="image/png?v=2"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-kalamba.png?v=2"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="kalamba"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="build/images/logo-symbol-dark-wt-kalamba.png?v=2"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-kalamba.webp?v=2"/>--}}
                    {{--                                                <source type="image/png?v=2"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-kalamba.png?v=2"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="kalamba"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="build/images/logo-symbol-dark-wt-kalamba.png?v=2"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Kalamba</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-yggdrasil-btn"--}}
                    {{--                                        aria-label="yggdrasil image provider nonyggdrasil"--}}
                    {{--                                        onclick="location.href='yggdrasil.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="yggdrasil"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-ygg-gaming.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-ygg-gaming.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="yggdrasil"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-ygg-gaming.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-ygg-gaming.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-ygg-gaming.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="yggdrasil"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-ygg-gaming.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Yggdrasil</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-playtech-btn"--}}
                    {{--                                        aria-label="playtech image provider nonplaytech"--}}
                    {{--                                        onclick="location.href='playtech.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="playtech"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-play-tech.webp?v=2"/>--}}
                    {{--                                                <source type="image/png?v=2"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-play-tech.png?v=2"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="playtech"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="build/images/logo-symbol-dark-wt-play-tech.png?v=2"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-play-tech.webp?v=2"/>--}}
                    {{--                                                <source type="image/png?v=2"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-play-tech.png?v=2"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="playtech"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="build/images/logo-symbol-dark-wt-play-tech.png?v=2"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Play Tech</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-netentslot-btn"--}}
                    {{--                                        aria-label="netentslot image provider nonnetentslot"--}}
                    {{--                                        onclick="location.href='netentslot.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="netentslot"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-netent-slot.webp?v=2"/>--}}
                    {{--                                                <source type="image/png?v=2"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-netent-slot.png?v=2"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="netentslot"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="build/images/logo-symbol-dark-wt-netent-slot.png?v=2"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-netent-slot.webp?v=2"/>--}}
                    {{--                                                <source type="image/png?v=2"--}}
                    {{--                                                        data-srcset="build/images/logo-symbol-dark-wt-netent-slot.png?v=2"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="netentslot"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="build/images/logo-symbol-dark-wt-netent-slot.png?v=2"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">NetEnt</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-redtiger-btn"--}}
                    {{--                                        aria-label="redtiger image provider nonredtiger"--}}
                    {{--                                        onclick="location.href='redtiger.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="redtiger"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-red-tiger.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-red-tiger.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="redtiger"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-red-tiger.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-red-tiger.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-red-tiger.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="redtiger"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-red-tiger.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Red Tiger</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-playstar-btn"--}}
                    {{--                                        aria-label="playstar image provider nonplaystar"--}}
                    {{--                                        onclick="location.href='playstar.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="playstar"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-ps.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-ps.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="playstar"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-ps.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-ps.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-ps.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="playstar"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-ps.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Playstar</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-habanero-btn"--}}
                    {{--                                        aria-label="habanero image provider nonhabanero"--}}
                    {{--                                        onclick="location.href='habanero.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="habanero"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-habanero.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-habanero.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="habanero"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-habanero.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-habanero.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-habanero.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="habanero"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-habanero.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Habanero</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-cq9-btn"--}}
                    {{--                                        aria-label="cq9 image provider noncq9"--}}
                    {{--                                        onclick="location.href='cq9.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="cq9"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-cq9.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-cq9.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="cq9"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-cq9.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-cq9.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-cq9.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="cq9"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-cq9.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">CQ9</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-onlyplay-btn"--}}
                    {{--                                        aria-label="onlyplay image provider nononlyplay"--}}
                    {{--                                        onclick="location.href='onlyplay.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="onlyplay"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-only-play.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-only-play.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="onlyplay"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-only-play.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-only-play.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-only-play.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="onlyplay"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-only-play.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">Only Play</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                                <li class="nav-item">--}}
                    {{--                                    <a--}}
                    {{--                                        href="#0"--}}
                    {{--                                        class="nav-link js-side-pgsoft-btn -active"--}}
                    {{--                                        aria-label="pgsoft image provider nonpgsoft"--}}
                    {{--                                        onclick="location.href='pgsoft.html'"--}}
                    {{--                                        data-menu-container=".js-menu-container"--}}
                    {{--                                        data-target-collapse="#collapse-brand"--}}
                    {{--                                        data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                        data-button-menu="pgsoft"--}}
                    {{--                                    >--}}
                    {{--                                        <div class="-menu-wrapper">--}}
                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-smm-pg-soft.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-smm-pg-soft.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="pgsoft"--}}
                    {{--                                                    class="img-fluid -img-btn lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-smm-pg-soft.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <picture>--}}
                    {{--                                                <source type="image/webp"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-smm-pg-soft.webp?v=2"/>--}}
                    {{--                                                <source type="image/png"--}}
                    {{--                                                        data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-smm-pg-soft.png"/>--}}
                    {{--                                                <img--}}
                    {{--                                                    alt="pgsoft"--}}
                    {{--                                                    class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                    width="40"--}}
                    {{--                                                    height="40"--}}
                    {{--                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-smm-pg-soft.png"--}}
                    {{--                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"--}}
                    {{--                                                />--}}
                    {{--                                            </picture>--}}

                    {{--                                            <span class="-menu-text-child">PG Soft</span>--}}
                    {{--                                        </div>--}}
                    {{--                                    </a>--}}
                    {{--                                </li>--}}
                    {{--                            </ul>--}}
                    {{--                        </div>--}}
                    {{--                    </div>--}}

                    <div class="-games-list-container js-game-scroll-container js-game-container">
                        <div class="-games-list-wrapper">
                            <div class="-game-title-wrapper">
                                <div class="-game-title-inner">
                                    <h2 class="-game-title h3 -shimmer">
                                        {{ $game_name->name }}
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
                                                        alt="smm-pg-soft cover image png"
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
                                                            <a href="{{ route('customer.game.redirect', [ 'id' => $id , 'name' => $item->code ,'method' => $item->method ]) }}"
                                                               class="js-account-approve-aware -btn -btn-play"
                                                               data-toggle="modal" data-target="#gametechPopup"
                                                               target="gametechPopup">
                                                                <i class="fas fa-play"></i>
                                                                <span class="-text-btn">เข้าเล่น</span>
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


    </div>
@endsection

@push('scripts')
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

        {{--var isMobile = false;--}}
        {{--var windowObjectReference = null; // global variable--}}
        {{--var PreviousUrl = ''; /* global variable that will store the--}}
        {{--            url currently in the secondary window */--}}


        {{--function openPopup(url) {--}}

        {{--Toast.fire({--}}
        {{--    icon: 'info',--}}
        {{--    title: '{{ __('app.game.login') }}'--}}
        {{--})--}}

        {{--    const w = 900;--}}
        {{--    const h = 500;--}}
        {{--    const y = window.top.outerHeight / 2 + window.top.screenY - (h / 2);--}}
        {{--    const x = window.top.outerWidth / 2 + window.top.screenX - (w / 2);--}}
        {{--    PreviousUrl = url;--}}
        {{--    console.log(windowObjectReference);--}}


        {{--    if (windowObjectReference == null || windowObjectReference.closed) {--}}

        {{--        if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent)--}}
        {{--            || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0, 4))) {--}}
        {{--            // alert('mobile');--}}
        {{--            windowObjectReference = window.open(PreviousUrl, '_blank');--}}
        {{--            windowObjectReference.focus();--}}

        {{--        } else {--}}
        {{--            // alert('pc');--}}
        {{--            windowObjectReference = window.open(PreviousUrl, "gametech", `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=${w}, height=${h}, top=${y}, left=${x}`);--}}
        {{--            windowObjectReference.focus();--}}
        {{--        }--}}
        {{--    } else {--}}
        {{--        windowObjectReference.location.href = PreviousUrl;--}}
        {{--        windowObjectReference.focus();--}}

        {{--    }--}}

        {{--}--}}

        {{--const link = document.querySelector("a[target='gametechPopup']");--}}


        {{--link.addEventListener(--}}
        {{--    "click",--}}
        {{--    (event) => {--}}
        {{--        openPopup(link.href);--}}
        {{--        event.preventDefault();--}}
        {{--    },--}}
        {{--    false--}}
        {{--);--}}

        {{--        @if($refill)--}}
        {{--        $(document).ready(function () {--}}

        {{--            Swal.fire({--}}
        {{--                title: 'คุณได้รับสิทธิ์ในการเลือกรับโปร',--}}
        {{--                html: "จากยอดเติมล่าสุด {{ $refill->value }} บาท<br>คุณจึงได้รับสิทธิ์ในการเลือกรับโปรสุดพิเศษ<br>กดที่รับโปร เพื่อไปเลือกโปรโมชั่น<br>กดไม่รับโปร เพื่อสละสิทธิ์ใน ยอดเติมล่าสุดนี้<br>** โปรดตัดสินใจการกดเข้าเล่นเกม **",--}}
        {{--                icon: 'warning',--}}
        {{--                showCancelButton: true,--}}
        {{--                confirmButtonColor: '#3085d6',--}}
        {{--                cancelButtonColor: '#d33',--}}
        {{--                confirmButtonText: 'รับโปร',--}}
        {{--                cancelButtonText: 'ไม่รับโปร',--}}
        {{--                allowOutsideClick: false,--}}
        {{--                allowEscapeKey: false,--}}
        {{--                allowEnterKey: false--}}
        {{--            }).then((result) => {--}}
        {{--                if (result.isConfirmed) {--}}
        {{--                    window.location.href = '{{ route('customer.promotion.index') }}';--}}
        {{--                } else {--}}
        {{--                    axios.post(`{{ route('customer.promotion.cancel') }}`).then(response => {--}}
        {{--                        if (response.data.success) {--}}
        {{--                            Toast.fire({--}}
        {{--                                icon: 'warning',--}}
        {{--                                title: 'คุณเลือกไม่รับโปร คุณจะได้รับ สิทธิ์ในการรับโปร เมื่อมีการฝากเงินเข้ามาในระบบ'--}}
        {{--                            })--}}
        {{--                        }--}}
        {{--                    }).catch(err => [err]);--}}

        {{--                }--}}
        {{--            })--}}

        {{--        });--}}
        {{--        @endif--}}
    </script>
@endpush

