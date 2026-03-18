@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')
    <div id="main__content" class="x-ez-games-by-category">
        <div class="js-replace-cover-seo-container">
            <div class="x-cover -small x-cover-category lazyload x-bg-position-center" data-bgset="build/images/cover-bg-slot.png?v=2">
                <div class="x-cover-template-full">
                    <div class="container -container-wrapper">
                        <div class="-row-wrapper">
                            <div class="-col-wrapper -first" data-animatable="fadeInModal">
                                <div class="x-cover-typography -v2">
                                    <h1 class="-title">PG SLOT แบรนด์สล็อตอันดับ 1 ที่ได้รับความนิยมตลอดการ</h1>
                                    <p class="-sub-title">สล็อตออนไลน์ ยอดนิยมที่ทำเงินได้ง่าย ปลอดภัยไร้ความกังวล</p>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <section class="js-replace-seo-section-top-container"></section>

        <section class="x-category-index -v2">
            <div class="-nav-menu-container js-category-menus">
                <div class="container-fluid pr-lg-0">
                    <div class="-nav-menu-container js-category-menus -v2">
                        <div class="x-quick-transaction-buttons js-quick-transaction-buttons">
                            <a class="btn -btn -promotion -vertical" href="/promotions" target="_blank" rel="noopener nofollow">
                                <span class="-ic-wrapper"> <img alt="โปรโมชั่นสุดคุ้ม เพื่อลูกค้าคนสำคัญ" class="img-fluid -ic" width="40" height="40" src="build/images/ic-quick-transaction-button-promotion.png?v=2" /></span>

                                <span class="-btn-inner-content">
            <span class="-btn-inner-content-title">โปรโมชั่น</span>
        </span>
                            </a>

                            <button
                                class="btn -btn -deposit x-bg-position-center lazyloaded"
                                data-toggle="modal"
                                data-target="#depositModal"
                                data-bgset="build/images/btn-deposit-bg.png?v=2"
                                style="background-image: url('build/images/btn-deposit-bg.png?v=2');"
                            >
                                <span class="-ic-wrapper"> <img alt="ฝากเงินง่ายๆ ด้วยระบบออโต้ การันตี 1 นาที" class="img-fluid -ic" width="40" height="40" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.png" /></span>

                                <span class="-btn-inner-content">
            <span class="-btn-inner-content-title">ฝากเงิน</span>
        </span>
                            </button>

                            <button
                                class="btn -btn -withdraw x-bg-position-center lazyloaded"
                                data-toggle="modal"
                                data-target="#withdrawModal"
                                data-bgset="build/images/btn-withdraw-bg.png?v=2"
                                style="background-image: url('build/images/btn-withdraw-bg.png?v=2');"
                            >
                                <span class="-ic-wrapper"> <img alt="ถอนเงินง่ายๆ ด้วยระบบออโต้ การันตี เท่าไหร่ก็จ่าย" class="img-fluid -ic" width="40" height="40" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.png" /></span>

                                <span class="-btn-inner-content">
            <span class="-btn-inner-content-title">ถอนเงิน</span>
        </span>
                            </button>
                        </div>
                        <nav class="nav-menu" id="navbarCategory">
                            <ul class="-menu-parent navbar-nav js-menu-container" id="accordion-games">
                                <li class="-list-parent nav-item">
                                    <div class="d-lg-block d-none">
                                        <a href="หวย.html" onclick="location.href='%E0%B8%AB%E0%B8%A7%E0%B8%A2" data-menu-container=".js-menu-container" class="x-category-button -category-lotto -category-button-v2 -hoverable">
                                            <img alt="category lotto image png" class="-img -default" width="300" height="82" src="build\web\ezl-wm-356\img\menu-category-lotto.png?v=2" />

                                            <img alt="category lotto image png" class="-img -hover" width="300" height="82" src="build\web\ezl-wm-356\img\menu-category-lotto-hover.png?v=2" />

                                            <span class="-menu-text-main -text-btn-image">
                                                    <div class="-menu-text-wrapper">
                                                        <span class="-text-desktop">ประเภทหวย</span>
                                                        <span class="-text-mobile">หวย</span>
                                                    </div>
                                                </span>
                                        </a>
                                    </div>

                                    <div class="d-lg-none d-block w-100">
                                        <a href="หวย.html" onclick="location.href='%E0%B8%AB%E0%B8%A7%E0%B8%A2" data-menu-container=".js-menu-container" class="x-category-button -category-lotto -category-button-icon -hoverable">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">ประเภทหวย</span>
                                                <span class="-text-mobile">หวย</span>
                                            </div>
                                        </a>
                                    </div>
                                </li>

                                <li class="-list-parent nav-item">
                                    <a
                                        href="pgsoft.html"
                                        onclick="location.href='pgsoft"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-collapse-menu="js-brand"
                                        data-button-menu="pgsoft"
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
                                        <img alt="category slot image png" class="-img -default" width="300" height="82" src="build\web\ezl-wm-356\img\menu-category-slot.png?v=2" />

                                        <img alt="category slot image png" class="-img -hover" width="300" height="82" src="build\web\ezl-wm-356\img\menu-category-slot-hover.png?v=2" />

                                        <span class="-menu-text-main -text-btn-image">
                                                <div class="-menu-text-wrapper">
                                                    <span class="-text-desktop">สล็อต</span>
                                                    <span class="-text-mobile">24 ค่าย</span>
                                                </div>
                                                <i class="fas fa-caret-down d-none d-lg-flex"></i>
                                            </span>
                                    </a>

                                    <div class="d-lg-block d-none">
                                        <div id="collapse-brand" class="x-menu-collapse-container -v2 -category-brand collapse show" data-parent="#accordion-games">
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
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.png" />
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
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonjili"
                                                onclick="location.href='jili.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="jili"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-jili.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-jili.png" />
                                                        <img
                                                            alt="jili"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-jili.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">Jili</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonbbinslot"
                                                onclick="location.href='bbinslot.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="bbinslot"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-bbin-slot.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-bbin-slot.png" />
                                                        <img
                                                            alt="bbinslot"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-bbin-slot.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">BBINSlot</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonwmslot"
                                                onclick="location.href='wmslot.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="wmslot"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-wm-slot.webp?v=2" />
                                                        <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-wm-slot.png?v=2" />
                                                        <img
                                                            alt="wmslot"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="build/images/logo-symbol-dark-wt-wm-slot.png?v=2"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">WM Slot</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonevoplay"
                                                onclick="location.href='evoplay.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="evoplay"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-evo-play.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-evo-play.png" />
                                                        <img
                                                            alt="evoplay"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-evo-play.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">Evoplay</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonkingmaker"
                                                onclick="location.href='kingmaker.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="kingmaker"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-kingmaker.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-kingmaker.png" />
                                                        <img
                                                            alt="kingmaker"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-kingmaker.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">Kingmaker</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nongoldy"
                                                onclick="location.href='goldy.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="goldy"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-goldy.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-goldy.png" />
                                                        <img
                                                            alt="goldy"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-goldy.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">Goldy</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonendorphina"
                                                onclick="location.href='endorphina.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="endorphina"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-endorphina.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-endorphina.png" />
                                                        <img
                                                            alt="endorphina"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-endorphina.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">Endorphina</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonspinix"
                                                onclick="location.href='spinix.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="spinix"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-spinix.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-spinix.png" />
                                                        <img
                                                            alt="spinix"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-spinix.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">SpinIX</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonmpoker"
                                                onclick="location.href='mpoker.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="mpoker"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-mpoker.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-mpoker.png" />
                                                        <img
                                                            alt="mpoker"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-mpoker.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">MPoker</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nondragoonsoft"
                                                onclick="location.href='dragoonsoft.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="dragoonsoft"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-dragoon-soft.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-dragoon-soft.png" />
                                                        <img
                                                            alt="dragoonsoft"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-dragoon-soft.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">DragoonSoft</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonnextspin"
                                                onclick="location.href='nextspin.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="nextspin"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-next-spin.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-next-spin.png" />
                                                        <img
                                                            alt="nextspin"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-next-spin.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">NextSpin</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonrich88"
                                                onclick="location.href='rich88.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="rich88"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-rich88.webp?v=2" />
                                                        <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-rich88.png?v=2" />
                                                        <img
                                                            alt="rich88"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="build/images/logo-symbol-dark-wt-rich88.png?v=2"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">Rich88</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonameba"
                                                onclick="location.href='ameba.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="ameba"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-ameba.webp?v=2" />
                                                        <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-ameba.png?v=2" />
                                                        <img
                                                            alt="ameba"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="build/images/logo-symbol-dark-wt-ameba.png?v=2"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">Ameba</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonkalamba"
                                                onclick="location.href='kalamba.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="kalamba"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-kalamba.webp?v=2" />
                                                        <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-kalamba.png?v=2" />
                                                        <img
                                                            alt="kalamba"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="build/images/logo-symbol-dark-wt-kalamba.png?v=2"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">Kalamba</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonyggdrasil"
                                                onclick="location.href='yggdrasil.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="yggdrasil"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-ygg-gaming.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-ygg-gaming.png" />
                                                        <img
                                                            alt="yggdrasil"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-ygg-gaming.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">Yggdrasil Gaming</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonplaytech"
                                                onclick="location.href='playtech.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="playtech"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-play-tech.webp?v=2" />
                                                        <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-play-tech.png?v=2" />
                                                        <img
                                                            alt="playtech"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="build/images/logo-symbol-dark-wt-play-tech.png?v=2"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">Playtech</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonnetentslot"
                                                onclick="location.href='netentslot.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="netentslot"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-netent-slot.webp?v=2" />
                                                        <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-netent-slot.png?v=2" />
                                                        <img
                                                            alt="netentslot"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="build/images/logo-symbol-dark-wt-netent-slot.png?v=2"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">Netent Slot</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonredtiger"
                                                onclick="location.href='redtiger.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="redtiger"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-red-tiger.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-red-tiger.png" />
                                                        <img
                                                            alt="redtiger"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-red-tiger.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">RedTiger</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonplaystar"
                                                onclick="location.href='playstar.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="playstar"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-ps.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-ps.png" />
                                                        <img
                                                            alt="playstar"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-ps.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">Playstar</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nonhabanero"
                                                onclick="location.href='habanero.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="habanero"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-habanero.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-habanero.png" />
                                                        <img
                                                            alt="habanero"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-habanero.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">Habanero</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse noncq9"
                                                onclick="location.href='cq9.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="cq9"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-cq9.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-cq9.png" />
                                                        <img
                                                            alt="cq9"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-cq9.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">CQ9</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse nononlyplay"
                                                onclick="location.href='onlyplay.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="onlyplay"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-only-play.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-only-play.png" />
                                                        <img
                                                            alt="onlyplay"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-only-play.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">OnlyPlay</span>
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn-block -child-collapse active nonpgsoft"
                                                onclick="location.href='pgsoft.html'"
                                                data-target-collapse="#collapse-brand"
                                                data-target-collapse-mobile="#collapse-mobile-brand"
                                                data-menu-container=".js-menu-container"
                                                data-button-menu="pgsoft"
                                            >
                                                <div class="-child-collapse-wrapper">
                                                    <picture>
                                                        <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-smm-pg-soft.webp?v=2" />
                                                        <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-smm-pg-soft.png" />
                                                        <img
                                                            alt="pgsoft"
                                                            class="img-fluid -img-btn lazyload"
                                                            width="40"
                                                            height="40"
                                                            data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-smm-pg-soft.png"
                                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                        />
                                                    </picture>

                                                    <span class="-menu-text-child">PG-Soft</span>
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                </li>

                                <li class="-list-parent nav-item">
                                    <div class="d-lg-block d-none">
                                        <a href="casino.html" onclick="location.href='casino" data-menu-container=".js-menu-container" class="x-category-button -category-casino -category-button-v2 -hoverable">
                                            <img alt="category casino image png" class="-img -default" width="300" height="82" src="build\web\ezl-wm-356\img\menu-category-casino.png?v=2" />

                                            <img alt="category casino image png" class="-img -hover" width="300" height="82" src="build\web\ezl-wm-356\img\menu-category-casino-hover.png?v=2" />

                                            <span class="-menu-text-main -text-btn-image">
                                                    <div class="-menu-text-wrapper">
                                                        <span class="-text-desktop">คาสิโนสด</span>
                                                        <span class="-text-mobile">คาสิโน</span>
                                                    </div>
                                                </span>
                                        </a>
                                    </div>

                                    <div class="d-lg-none d-block w-100">
                                        <a href="casino.html" onclick="location.href='casino" data-menu-container=".js-menu-container" class="x-category-button -category-casino -category-button-icon -hoverable">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">คาสิโนสด</span>
                                                <span class="-text-mobile">คาสิโน</span>
                                            </div>
                                        </a>
                                    </div>
                                </li>

                                <li class="-list-parent nav-item">
                                    <div class="d-lg-block d-none">
                                        <a href="sport.html" onclick="location.href='sport" data-menu-container=".js-menu-container" class="x-category-button -category-sport -category-button-v2 -hoverable">
                                            <img alt="category sport image png" class="-img -default" width="300" height="82" src="build\web\ezl-wm-356\img\menu-category-sport.png?v=2" />

                                            <img alt="category sport image png" class="-img -hover" width="300" height="82" src="build\web\ezl-wm-356\img\menu-category-sport-hover.png?v=2" />

                                            <span class="-menu-text-main -text-btn-image">
                                                    <div class="-menu-text-wrapper">
                                                        <span class="-text-desktop">กีฬา</span>
                                                        <span class="-text-mobile">กีฬา</span>
                                                    </div>
                                                </span>
                                        </a>
                                    </div>

                                    <div class="d-lg-none d-block w-100">
                                        <a href="sport.html" onclick="location.href='sport" data-menu-container=".js-menu-container" class="x-category-button -category-sport -category-button-icon -hoverable">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">กีฬา</span>
                                                <span class="-text-mobile">กีฬา</span>
                                            </div>
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
                    <div class="x-menu-mobile-sidebar-wrapper -v2">
                        <div data-menu-sticky="js-sticky-widget">
                            <ul class="nav -menu-list">
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-joker-btn"
                                        aria-label="joker image provider nonjoker"
                                        onclick="location.href='joker.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="joker"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.png" />
                                                <img
                                                    alt="joker"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.png" />
                                                <img
                                                    alt="joker"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-joker.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Joker</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-jili-btn"
                                        aria-label="jili image provider nonjili"
                                        onclick="location.href='jili.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="jili"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-jili.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-jili.png" />
                                                <img
                                                    alt="jili"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-jili.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-jili.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-jili.png" />
                                                <img
                                                    alt="jili"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-jili.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Jili</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-bbinslot-btn"
                                        aria-label="bbinslot image provider nonbbinslot"
                                        onclick="location.href='bbinslot.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="bbinslot"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-bbin-slot.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-bbin-slot.png" />
                                                <img
                                                    alt="bbinslot"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-bbin-slot.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-bbin-slot.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-bbin-slot.png" />
                                                <img
                                                    alt="bbinslot"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-bbin-slot.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">BBIN</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-wmslot-btn"
                                        aria-label="wmslot image provider nonwmslot"
                                        onclick="location.href='wmslot.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="wmslot"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-wm-slot.webp?v=2" />
                                                <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-wm-slot.png?v=2" />
                                                <img
                                                    alt="wmslot"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="build/images/logo-symbol-dark-wt-wm-slot.png?v=2"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-wm-slot.webp?v=2" />
                                                <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-wm-slot.png?v=2" />
                                                <img
                                                    alt="wmslot"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="build/images/logo-symbol-dark-wt-wm-slot.png?v=2"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">World Match</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-evoplay-btn"
                                        aria-label="evoplay image provider nonevoplay"
                                        onclick="location.href='evoplay.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="evoplay"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-evo-play.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-evo-play.png" />
                                                <img
                                                    alt="evoplay"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-evo-play.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-evo-play.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-evo-play.png" />
                                                <img
                                                    alt="evoplay"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-evo-play.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Evoplay</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-kingmaker-btn"
                                        aria-label="kingmaker image provider nonkingmaker"
                                        onclick="location.href='kingmaker.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="kingmaker"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-kingmaker.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-kingmaker.png" />
                                                <img
                                                    alt="kingmaker"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-kingmaker.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-kingmaker.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-kingmaker.png" />
                                                <img
                                                    alt="kingmaker"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-sm-kingmaker.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Kingmaker</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-goldy-btn"
                                        aria-label="goldy image provider nongoldy"
                                        onclick="location.href='goldy.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="goldy"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-goldy.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-goldy.png" />
                                                <img
                                                    alt="goldy"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-goldy.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-goldy.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-goldy.png" />
                                                <img
                                                    alt="goldy"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-goldy.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Goldy</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-endorphina-btn"
                                        aria-label="endorphina image provider nonendorphina"
                                        onclick="location.href='endorphina.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="endorphina"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-endorphina.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-endorphina.png" />
                                                <img
                                                    alt="endorphina"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-endorphina.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-endorphina.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-endorphina.png" />
                                                <img
                                                    alt="endorphina"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-endorphina.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Endor phina</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-spinix-btn"
                                        aria-label="spinix image provider nonspinix"
                                        onclick="location.href='spinix.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="spinix"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-spinix.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-spinix.png" />
                                                <img
                                                    alt="spinix"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-spinix.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-spinix.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-spinix.png" />
                                                <img
                                                    alt="spinix"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-spinix.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">SpiniX</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-mpoker-btn"
                                        aria-label="mpoker image provider nonmpoker"
                                        onclick="location.href='mpoker.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="mpoker"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-mpoker.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-mpoker.png" />
                                                <img
                                                    alt="mpoker"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-mpoker.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-mpoker.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-mpoker.png" />
                                                <img
                                                    alt="mpoker"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-mpoker.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">MPoker</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-dragoonsoft-btn"
                                        aria-label="dragoonsoft image provider nondragoonsoft"
                                        onclick="location.href='dragoonsoft.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="dragoonsoft"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-dragoon-soft.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-dragoon-soft.png" />
                                                <img
                                                    alt="dragoonsoft"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-dragoon-soft.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-dragoon-soft.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-dragoon-soft.png" />
                                                <img
                                                    alt="dragoonsoft"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-dragoon-soft.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Dragoon Soft</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-nextspin-btn"
                                        aria-label="nextspin image provider nonnextspin"
                                        onclick="location.href='nextspin.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="nextspin"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-next-spin.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-next-spin.png" />
                                                <img
                                                    alt="nextspin"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-next-spin.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-next-spin.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-next-spin.png" />
                                                <img
                                                    alt="nextspin"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-next-spin.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Next Spin</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-rich88-btn"
                                        aria-label="rich88 image provider nonrich88"
                                        onclick="location.href='rich88.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="rich88"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-rich88.webp?v=2" />
                                                <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-rich88.png?v=2" />
                                                <img
                                                    alt="rich88"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="build/images/logo-symbol-dark-wt-rich88.png?v=2"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-rich88.webp?v=2" />
                                                <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-rich88.png?v=2" />
                                                <img
                                                    alt="rich88"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="build/images/logo-symbol-dark-wt-rich88.png?v=2"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Rich88</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-ameba-btn"
                                        aria-label="ameba image provider nonameba"
                                        onclick="location.href='ameba.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="ameba"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-ameba.webp?v=2" />
                                                <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-ameba.png?v=2" />
                                                <img
                                                    alt="ameba"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="build/images/logo-symbol-dark-wt-ameba.png?v=2"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-ameba.webp?v=2" />
                                                <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-ameba.png?v=2" />
                                                <img
                                                    alt="ameba"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="build/images/logo-symbol-dark-wt-ameba.png?v=2"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Ameba</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-kalamba-btn"
                                        aria-label="kalamba image provider nonkalamba"
                                        onclick="location.href='kalamba.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="kalamba"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-kalamba.webp?v=2" />
                                                <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-kalamba.png?v=2" />
                                                <img
                                                    alt="kalamba"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="build/images/logo-symbol-dark-wt-kalamba.png?v=2"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-kalamba.webp?v=2" />
                                                <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-kalamba.png?v=2" />
                                                <img
                                                    alt="kalamba"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="build/images/logo-symbol-dark-wt-kalamba.png?v=2"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Kalamba</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-yggdrasil-btn"
                                        aria-label="yggdrasil image provider nonyggdrasil"
                                        onclick="location.href='yggdrasil.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="yggdrasil"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-ygg-gaming.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-ygg-gaming.png" />
                                                <img
                                                    alt="yggdrasil"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-ygg-gaming.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-ygg-gaming.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-ygg-gaming.png" />
                                                <img
                                                    alt="yggdrasil"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wtm-ygg-gaming.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Yggdrasil</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-playtech-btn"
                                        aria-label="playtech image provider nonplaytech"
                                        onclick="location.href='playtech.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="playtech"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-play-tech.webp?v=2" />
                                                <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-play-tech.png?v=2" />
                                                <img
                                                    alt="playtech"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="build/images/logo-symbol-dark-wt-play-tech.png?v=2"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-play-tech.webp?v=2" />
                                                <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-play-tech.png?v=2" />
                                                <img
                                                    alt="playtech"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="build/images/logo-symbol-dark-wt-play-tech.png?v=2"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Play Tech</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-netentslot-btn"
                                        aria-label="netentslot image provider nonnetentslot"
                                        onclick="location.href='netentslot.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="netentslot"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-netent-slot.webp?v=2" />
                                                <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-netent-slot.png?v=2" />
                                                <img
                                                    alt="netentslot"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="build/images/logo-symbol-dark-wt-netent-slot.png?v=2"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="build/images/logo-symbol-dark-wt-netent-slot.webp?v=2" />
                                                <source type="image/png?v=2" data-srcset="build/images/logo-symbol-dark-wt-netent-slot.png?v=2" />
                                                <img
                                                    alt="netentslot"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="build/images/logo-symbol-dark-wt-netent-slot.png?v=2"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">NetEnt</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-redtiger-btn"
                                        aria-label="redtiger image provider nonredtiger"
                                        onclick="location.href='redtiger.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="redtiger"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-red-tiger.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-red-tiger.png" />
                                                <img
                                                    alt="redtiger"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-red-tiger.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-red-tiger.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-red-tiger.png" />
                                                <img
                                                    alt="redtiger"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-red-tiger.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Red Tiger</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-playstar-btn"
                                        aria-label="playstar image provider nonplaystar"
                                        onclick="location.href='playstar.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="playstar"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-ps.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-ps.png" />
                                                <img
                                                    alt="playstar"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-ps.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-ps.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-ps.png" />
                                                <img
                                                    alt="playstar"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-ps.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Playstar</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-habanero-btn"
                                        aria-label="habanero image provider nonhabanero"
                                        onclick="location.href='habanero.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="habanero"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-habanero.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-habanero.png" />
                                                <img
                                                    alt="habanero"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-habanero.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-habanero.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-habanero.png" />
                                                <img
                                                    alt="habanero"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-habanero.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Habanero</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-cq9-btn"
                                        aria-label="cq9 image provider noncq9"
                                        onclick="location.href='cq9.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="cq9"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-cq9.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-cq9.png" />
                                                <img
                                                    alt="cq9"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-cq9.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-cq9.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-cq9.png" />
                                                <img
                                                    alt="cq9"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-cq9.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">CQ9</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-onlyplay-btn"
                                        aria-label="onlyplay image provider nononlyplay"
                                        onclick="location.href='onlyplay.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="onlyplay"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-only-play.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-only-play.png" />
                                                <img
                                                    alt="onlyplay"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-only-play.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-only-play.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-only-play.png" />
                                                <img
                                                    alt="onlyplay"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-wt-only-play.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">Only Play</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a
                                        href="#0"
                                        class="nav-link js-side-pgsoft-btn -active"
                                        aria-label="pgsoft image provider nonpgsoft"
                                        onclick="location.href='pgsoft.html'"
                                        data-menu-container=".js-menu-container"
                                        data-target-collapse="#collapse-brand"
                                        data-target-collapse-mobile="#collapse-mobile-brand"
                                        data-button-menu="pgsoft"
                                    >
                                        <div class="-menu-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-smm-pg-soft.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-smm-pg-soft.png" />
                                                <img
                                                    alt="pgsoft"
                                                    class="img-fluid -img-btn lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-smm-pg-soft.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-smm-pg-soft.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-smm-pg-soft.png" />
                                                <img
                                                    alt="pgsoft"
                                                    class="img-fluid -img-btn -hover lazyload"
                                                    width="40"
                                                    height="40"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/common/logo-symbol-dark-smm-pg-soft.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>

                                            <span class="-menu-text-child">PG Soft</span>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="-games-list-container js-game-scroll-container js-game-container">
                        <div class="-games-list-wrapper">
                            <div class="-game-title-wrapper">
                                <div class="-game-title-inner">
                                    <h2 class="-game-title h3 -shimmer">
                                        PG Soft
                                        <span class="-sub-title">
                                                (111 เกม)
                                            </span>
                                    </h2>
                                </div>

                                <div class="-game-search-inner">
                                    <form action="/search-result">
                                        <div class="input-group x-search-component -v2">
                                            <input type="text" id="searchKeyword" name="search" value="" class="x-form-control form-control -form-search-input" placeholder="ค้นหาเกมส์หรือค่ายเกมส์..." />

                                            <div class="input-group-prepend">
                                                <div class="input-group-text -text-group-wrapper" id="btnGroupAddon">
                                                    <button class="-btn-search">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <ul class="navbar-nav -slot-provider-page">
                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component -new -big" data-animatable="fadeInUp" data-delay="400">
                                                <span>New</span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1572362-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1572362-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1572362-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component -new -big" data-animatable="fadeInUp" data-delay="400">
                                                <span>New</span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1594259-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1594259-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1594259-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component -new -big" data-animatable="fadeInUp" data-delay="400">
                                                <span>New</span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1473388-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1473388-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1473388-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component -new -big" data-animatable="fadeInUp" data-delay="400">
                                                <span>New</span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1397455-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1397455-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1397455-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component -new -big" data-animatable="fadeInUp" data-delay="400">
                                                <span>New</span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1601012-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1601012-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1601012-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1513328-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1513328-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1513328-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1432733-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1432733-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1432733-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1448762-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1448762-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1448762-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1418544-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1418544-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1418544-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1381200-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1381200-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1381200-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1420892-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1420892-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1420892-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1543462-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1543462-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1543462-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1402846-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1402846-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1402846-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1340277-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1340277-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1340277-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1372643-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1372643-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1372643-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1368367-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1368367-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1368367-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1338274-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1338274-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1338274-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1312883-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1312883-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/1312883-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/135-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/135-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/135-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/132-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/132-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/132-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/128-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/128-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/128-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/127-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/127-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/127-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/130-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/130-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/130-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/129-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/129-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/129-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/battleground-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/battleground-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/battleground-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/queen-banquet-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/queen-banquet-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/queen-banquet-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/rooster-rbl-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/rooster-rbl-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/rooster-rbl-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/btrfly-blossom-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/btrfly-blossom-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/btrfly-blossom-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/dest-sun-moon-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/dest-sun-moon-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/dest-sun-moon-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/garuda-gems-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/garuda-gems-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/garuda-gems-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/fortune-tiger-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/fortune-tiger-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/fortune-tiger-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/oriental-pros-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/oriental-pros-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/oriental-pros-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/mask-carnival-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/mask-carnival-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/mask-carnival-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/cocktail-nite-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/cocktail-nite-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/cocktail-nite-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/emoji-riches-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/emoji-riches-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/emoji-riches-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/jurassic-kdm-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/jurassic-kdm-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/jurassic-kdm-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/spirit-wonder-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/spirit-wonder-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/spirit-wonder-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/lgd-monkey-kg-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/lgd-monkey-kg-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/lgd-monkey-kg-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/buffalo-win-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/buffalo-win-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/buffalo-win-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/sprmkt-spree-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/sprmkt-spree-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/sprmkt-spree-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/crypt-fortune-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/crypt-fortune-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/crypt-fortune-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/ways-of-qilin-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/ways-of-qilin-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/ways-of-qilin-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/heist-stakes-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/heist-stakes-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/heist-stakes-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/wild-bandito-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/wild-bandito-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/wild-bandito-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/crypto-gold-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/crypto-gold-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/crypto-gold-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/mermaid-riches-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/mermaid-riches-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/mermaid-riches-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/rise-of-apollo-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/rise-of-apollo-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/rise-of-apollo-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/candy-bonanza-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/candy-bonanza-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/candy-bonanza-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/fortune-ox-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/fortune-ox-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/fortune-ox-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/jack-frosts-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/jack-frosts-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/jack-frosts-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/majestic-ts-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/majestic-ts-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/majestic-ts-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/bali-vacation-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/bali-vacation-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/bali-vacation-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/opera-dynasty-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/opera-dynasty-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/opera-dynasty-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/thai-river-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/thai-river-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/thai-river-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/gdn-ice-fire-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/gdn-ice-fire-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/gdn-ice-fire-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/sct-cleopatra-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/sct-cleopatra-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/sct-cleopatra-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/lucky-neko-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/lucky-neko-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/lucky-neko-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/jewels-prosper-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/jewels-prosper-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/jewels-prosper-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/treasures-aztec-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/treasures-aztec-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/treasures-aztec-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/galactic-gems-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/galactic-gems-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/galactic-gems-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/genies-wishes-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/genies-wishes-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/genies-wishes-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/queen-bounty-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/queen-bounty-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/queen-bounty-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/wild-fireworks-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/wild-fireworks-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/wild-fireworks-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/phoenix-rises-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/phoenix-rises-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/phoenix-rises-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/circus-delight-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/circus-delight-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/circus-delight-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/dreams-of-macau-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/dreams-of-macau-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/dreams-of-macau-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/ganesha-fortune-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/ganesha-fortune-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/ganesha-fortune-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/mahjong-ways2-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/mahjong-ways2-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/mahjong-ways2-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/egypts-book-mystery-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/egypts-book-mystery-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/egypts-book-mystery-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/cai-shen-wins-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/cai-shen-wins-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/cai-shen-wins-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/candy-burst-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/candy-burst-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/candy-burst-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/bikini-paradise-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/bikini-paradise-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/bikini-paradise-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/fortune-mouse-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/fortune-mouse-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/fortune-mouse-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/shaolin-soccer-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/shaolin-soccer-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/shaolin-soccer-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/mahjong-ways-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/mahjong-ways-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/mahjong-ways-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/muay-thai-champion-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/muay-thai-champion-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/muay-thai-champion-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/dragon-tiger-luck-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/dragon-tiger-luck-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/dragon-tiger-luck-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/gem-saviour-conquest-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/gem-saviour-conquest-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/gem-saviour-conquest-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/flirting-scholar-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/flirting-scholar-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/flirting-scholar-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/leprechaun-riches-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/leprechaun-riches-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/leprechaun-riches-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/ninja-vs-samurai-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/ninja-vs-samurai-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/ninja-vs-samurai-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/vampires-charm-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/vampires-charm-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/vampires-charm-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/dragon-hatch-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/dragon-hatch-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/dragon-hatch-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/captains-bounty-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/captains-bounty-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/captains-bounty-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/the-great-icescape-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/the-great-icescape-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/the-great-icescape-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/journey-to-the-wealth-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/journey-to-the-wealth-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/journey-to-the-wealth-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/double-fortune-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/double-fortune-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/double-fortune-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/emperors-favour-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/emperors-favour-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/emperors-favour-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/ganesha-gold-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/ganesha-gold-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/ganesha-gold-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/symbols-of-egypt-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/symbols-of-egypt-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/symbols-of-egypt-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/jungle-delight-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/jungle-delight-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/jungle-delight-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/piggy-gold-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/piggy-gold-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/piggy-gold-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/gem-saviour-sword-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/gem-saviour-sword-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/gem-saviour-sword-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/santas-gift-rush-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/santas-gift-rush-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/santas-gift-rush-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/prosperity-lion-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/prosperity-lion-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/prosperity-lion-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/mr-hallow-win-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/mr-hallow-win-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/mr-hallow-win-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/legend-of-hou-yi-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/legend-of-hou-yi-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/legend-of-hou-yi-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/hip-hop-panda-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/hip-hop-panda-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/hip-hop-panda-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/baccarat-deluxe-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/baccarat-deluxe-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/baccarat-deluxe-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/dragon-legend-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/dragon-legend-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/dragon-legend-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/hotpot-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/hotpot-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/hotpot-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/fortune-tree-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/fortune-tree-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/fortune-tree-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/plushie-frenzy-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/plushie-frenzy-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/plushie-frenzy-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/win-win-won-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/win-win-won-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/win-win-won-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/reel-love-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/reel-love-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/reel-love-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/hood-wolf-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/hood-wolf-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/hood-wolf-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/medusa-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/medusa-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/medusa-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/medusa2-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/medusa2-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/medusa2-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/fortune-gods-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/fortune-gods-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/fortune-gods-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/gem-saviour-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/gem-saviour-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/gem-saviour-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <li class="nav-item">
                                    <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert" data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp" data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/diaochan-vertical.webp?v=2" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/diaochan-vertical.png" />
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/ez-smm-pg-soft/diaochan-vertical.png"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a href="#loginModal" class="js-account-approve-aware -btn -btn-play" data-toggle="modal" data-target="#loginModal">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">เข้าเล่น</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="js-replace-seo-section-container">
            <div class="x-vertical-content">
                <div class="container-fluid">
                    <div class="-vertical-content-wrapper -content-center">
                        <div class="-block-content">
                            <p class="-text-description" data-delay="200">
                                แนะนำ PG SLOT แบรนด์เกมสล็อตออนไลน์อันดับ 1 ของโลก สุดยอดแบรนด์ที่มีผู้เข้ามาใช้บริการสูงมากที่สุดในประเทศไทยนับว่าเป็นอีกหนึ่งค่ายที่ได้รับการพัฒนามาอย่างต่อเนื่อง มีเกมสล็อตที่มี Signature เป็นของตัวเอง
                                และ สล็อต pg โดดเด่นด้วยระบบที่ทันสมัย พร้อมฟีเจอร์เกมที่หลากหลาย ทำให้ผู้ที่เข้ามาใช้บริการได้พบกับสุดยอดความมันส์ และลุ้นไปกับโบนัสก้อนโตที่มีมูลค่าสูงสุดถึง 100,000 เท่า
                                ผ่านการนำเข้าจากคาสิโนสล็อตเว็บตรงของเรา ซึ่งบริการเกมแท้ 100% เพิ่มความสนุกด้วยเกมที่มีภาพ เสียง เนื้อหาที่น่าสนใจ รวมไปถึงระบบทันสมัยและภาพระดับ Full HD
                                ที่จะพาผู้เล่นเข้าไปผจญภัยและสร้างกำไรได้อย่างต่อเนื่องด้วย ทางเข้า PG ที่เปิดให้บริการตลอด 24 ชั่วโมง
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="x-vertical-content">
                <div class="container-fluid">
                    <div class="-vertical-content-wrapper -content-center">
                        <div class="-block-content">
                            <h3 class="-text-title">
                                PG SLOT ค่ายเกมยอดนิยมพบกับสล็อตภาพสวยระดับ Full HD
                            </h3>

                            <p class="-text-description" data-delay="200">
                                แหล่งรวมสล็อตยอดนิยมที่พร้อมให้สมาชิกได้เข้ามาร่วมสนุกและร่วมชิงเงินรางวัลก้อนใหญ่จาก PG SLOT โดยเราเปิดให้บริการเกมสล็อตมากกว่า 106 เกม และการันตีว่าทุกเกมที่คาสิโนของเราให้บริการมีคุณภาพสูง
                                พร้อมจ่ายเงินรางวัลแบบต่อเนื่อง ที่สำคัญคือเกม สล็อต pg มาในรูปแบบของวิดีโอเกมภาพสวยในระบบ 3 มิติ และพร้อมจะให้ทุกท่านได้เข้ามาร่วมสนุกอย่างเต็มรูปแบบ กับสล็อตวิดีโอเกมที่อยู่ในระดับ Full HD
                                ไหลลื่นไม่มีสะดุดพร้อมร่วมสนุกผ่าน ทางเข้า PG ได้ตลอด 24 ชั่วโมง
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="x-vertical-content">
                <div class="container-fluid">
                    <div class="-vertical-content-wrapper -content-center">
                        <div class="-block-content">
                            <h3 class="-text-title">
                                PG SLOT ที่มาพร้อมกับระบบภายในเกมที่ทันสมัยที่สุด
                            </h3>

                            <p class="-text-description" data-delay="200"></p>
                            <p class="text-left -text-description">
                                วิดีโอเกมสล็อตมาแรงของ PG SLOT โดยทางค่ายมีความน่าสนใจในหลายๆ เรื่องซึ่งในวันนี้เราจะมาแนะนำให้ทุกท่านได้รู้จัก สล็อต pg ให้มากขึ้น ผ่านการนำเสนอระบบใหม่ล่าสุดที่มาพร้อมความทันสมัยของตัวเกม
                                และฟีเจอร์พิเศษที่จะทำให้ผู้ใช้งานทุกท่านถูกใจอย่างแน่นอน
                            </p>
                            <ul class="text-left -text-description">
                                <li>ระบบใหม่ล่าสุด PG SLOT อัปเดตระบบคาสิโนให้รองรับทุกความต้องการ ผ่านระบบอัตโนมัติ ที่ทำให้ผู้ใช้บริการสามารถเข้าถึงเกมสล็อตออนไลน์ได้แบบต่อเนื่องตลอด 24 ชั่วโมง</li>
                                <li>เกมทันสมัยตอบโจทย์ได้อย่างเหนือระดับ ตัวเกมทั้งหมดจากทางค่าย PG ผ่านการพัฒนาให้ตรงต่อยุคสมัย ดีไซน์ของตัวเกมมีความน่าสนใจ ภาพสวย เล่นง่าย หลากหลายรูปแบบ</li>
                                <li>ฟีเจอร์พิเศษโดดเด่นเหนือใคร ทางค่ายมีการนำเสนอฟีเจอร์เกมสล็อตที่จะทำให้เงินรางวัลออกง่ายขึ้นมาหลายรูปแบบ เช่น คอมโบเกม , รีสปิน , ตัวคูณเงินรางวัลและโบนัสเกม เป็นต้น</li>
                            </ul>
                            <p class="text-left -text-description">
                                ค่ายเกม PG SLOT ยังมีอะไรที่น่าสนใจอยู่ภายในอีกมากมาย และเราอยากให้ทุกท่านได้เข้ามาพิสูจน์ผ่าน ทางเข้า PG ด้วยตัวเอง ท่านจะได้พบกับสุดยอดเกมสล็อตที่จะมาสร้างความประทับใจตลอดการเข้าใช้งานอย่างแน่นอน
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div
            class="x-modal modal -v2 -alert-modal"
            id="alertModal"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
            data-loading-container=".js-modal-content"
            data-ajax-modal-always-reload="true"
            data-animatable="fadeInRight"
            data-delay="700"
            data-dismiss-alert="true"
        >
            <div class="modal-dialog -modal-size -v2" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-body -modal-body">
                        <div class="d-flex -alert-body">
                            <div class="text-center mr-3 -alert-body-wrapper">
                                <picture>
                                    <source type="image/webp" srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-alert-success.webp" />
                                    <source type="image/png" srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-alert-success.png" />
                                    <img class="-img-alert js-ic-success img-fluid" alt="ทำรายการเว็บพนันออนไลน์สำเร็จ" width="40" height="40" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-alert-success.png" />
                                </picture>

                                <picture>
                                    <source type="image/webp" srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-alert-failed.webp" />
                                    <source type="image/png" srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-alert-failed.png" />
                                    <img class="-img-alert js-ic-fail img-fluid" alt="ทำรายการเว็บพนันออนไลน์ไม่สำเร็จ" width="40" height="40" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-alert-failed.png" />
                                </picture>
                            </div>
                            <div class="my-auto js-modal-content"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="x-modal modal -v2 x-theme-switcher-v2" id="themeSwitcherModal" tabindex="-1" role="dialog" aria-hidden="true" data-loading-container=".js-modal-content" data-ajax-modal-always-reload="true">
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable modal-dialog-centered" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-body -modal-body">
                        <div class="-theme-switcher-container">
                            <div class="-inner-header-section">
                                <a class="-link-wrapper" href="index.html">
                                    <picture>
                                        <source type="image/webp" data-srcset="build/images/logo.webp?v=2" />
                                        <source type="image/png?v=2" data-srcset="build/images/logo.png?v=2" />
                                        <img
                                            alt="logo image"
                                            class="img-fluid lazyload -logo lazyload"
                                            width="180"
                                            height="42"
                                            data-src="build/images/logo.png?v=2"
                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                        />
                                    </picture>
                                </a>
                            </div>

                            <div class="-inner-top-body-section">
                                <div class="col-6 -wrapper-box">
                                    <button
                                        type="button"
                                        class="btn -btn-item x-transaction-button-v2 -deposit -top-btn -horizontal x-bg-position-center lazyloaded"
                                        data-toggle="modal"
                                        data-dismiss="modal"
                                        data-target="#depositModal"
                                        data-bgset="build/images/btn-deposit-bg.png?v=2"
                                        style="background-image: url('build/images/btn-deposit-bg.png?v=2');"
                                    >
                                        <picture>
                                            <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.webp?v=2" srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.webp?v=2" />
                                            <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.png" srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.png" />
                                            <img
                                                alt="รูปไอคอนฝากเงิน"
                                                class="img-fluid -icon-image ls-is-cached lazyloaded"
                                                width="50"
                                                height="50"
                                                data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.png"
                                                src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.png"
                                            />
                                        </picture>

                                        <div class="-typo-wrapper">
                                            <div class="-title">ฝากเงิน</div>
                                            <div class="-sub-title">Deposit</div>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-6 -wrapper-box">
                                    <button
                                        type="button"
                                        class="btn -btn-item x-transaction-button-v2 -withdraw -top-btn -horizontal x-bg-position-center lazyloaded"
                                        data-toggle="modal"
                                        data-dismiss="modal"
                                        data-target="#withdrawModal"
                                        data-bgset="build/images/btn-withdraw-bg.png?v=2"
                                        style="background-image: url('build/images/btn-withdraw-bg.png?v=2');"
                                    >
                                        <picture>
                                            <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.webp?v=2" srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.webp?v=2" />
                                            <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.png" srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.png" />
                                            <img
                                                alt="รูปไอคอนถอนเงิน"
                                                class="img-fluid -icon-image ls-is-cached lazyloaded"
                                                width="50"
                                                height="50"
                                                data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.png"
                                                src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.png"
                                            />
                                        </picture>

                                        <div class="-typo-wrapper">
                                            <div class="-title">ถอนเงิน</div>
                                            <div class="-sub-title">Withdraw</div>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <div class="-inner-center-body-section">
                                <div class="col-6 -wrapper-box">
                                    <a
                                        href="promotions.html"
                                        class="btn -btn-item -promotion-button -menu-center -horizontal lazyload x-bg-position-center"
                                        data-bgset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/btn-menu-middle-bg.png"
                                    >
                                        <picture>
                                            <source type="image/webp" data-srcset="build/images/ic-modal-menu-promotion.webp?v=2" />
                                            <source type="image/png?v=2" data-srcset="build/images/ic-modal-menu-promotion.png?v=2" />
                                            <img
                                                alt="รูปไอคอนโปรโมชั่น"
                                                class="img-fluid -icon-image lazyload"
                                                width="65"
                                                height="53"
                                                data-src="build/images/ic-modal-menu-promotion.png?v=2"
                                                src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                            />
                                        </picture>

                                        <div class="-typo-wrapper">
                                            <div class="-typo">โปรโมชั่น</div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-6 -wrapper-box">
                                    <a
                                        href="https://lin.ee/BpAUj1s"
                                        class="btn -btn-item -line-button -menu-center -horizontal lazyload x-bg-position-center"
                                        target="_blank"
                                        rel="noopener nofollow"
                                        data-bgset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/btn-menu-middle-bg.png"
                                    >
                                        <picture>
                                            <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.webp?v=2" />
                                            <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.png" />
                                            <img
                                                alt="รูปไอคอนดูหนัง"
                                                class="img-fluid -icon-image lazyload"
                                                width="65"
                                                height="53"
                                                data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.png"
                                                src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                            />
                                        </picture>

                                        <div class="-typo-wrapper">
                                            <div class="-typo">ไลน์</div>
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

        <div class="x-modal modal -v2" id="bookmarkModal" tabindex="-1" role="dialog" aria-hidden="true" data-loading-container=".js-modal-content" data-ajax-modal-always-reload="true">
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -no-fixed-button" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-header -modal-header">
                        <h3 class="x-title-modal d-inline-block m-auto">
                            <span>Bookmark</span>
                        </h3>
                    </div>
                    <div class="modal-body -modal-body">
                        <div class="x-bookmark-modal-container">
                            <nav>
                                <div class="nav nav-tabs x-bookmark-tabs-header-wrapper" id="nav-tab" role="tablist">
                                    <a class="nav-link" id="nav-android-tab" data-toggle="tab" href="#nav-android" role="tab" aria-controls="nav-android" aria-selected="true">Android</a>
                                    <a class="nav-link active" id="nav-ios-tab" data-toggle="tab" href="#nav-ios" role="tab" aria-controls="nav-ios" aria-selected="true">iOS</a>
                                </div>
                            </nav>

                            <div class="tab-content x-bookmark-tabs-content-wrapper" id="nav-tabContent">
                                <div class="tab-pane fade" id="nav-android" role="tabpanel" aria-labelledby="nav-android-tab">
                                    <div
                                        class="-slide-wrapper -bookmark-slider-for-android"
                                        data-slickable='{"arrows":false,"dots":true,"slidesToShow":1,"fade":true,"infinite":true,"autoplay":false,"asNavFor":".-bookmark-slider-nav-android"}'
                                    >
                                        <div class="-slide-inner-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-android-1.webp" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-android-1.png" />
                                                <img
                                                    class="-img lazyload"
                                                    alt="บาคาร่าออนไลน์ คาสิโนออนไลน์ อันดับ 1 ของไทย"
                                                    width="253"
                                                    height="513"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-android-1.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>
                                        </div>
                                        <div class="-slide-inner-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-android-2.webp" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-android-2.png" />
                                                <img
                                                    class="-img lazyload"
                                                    alt="บาคาร่าออนไลน์ คาสิโนออนไลน์ อันดับ 1 ของไทย"
                                                    width="253"
                                                    height="513"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-android-2.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>
                                        </div>
                                        <div class="-slide-inner-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-android-3.webp" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-android-3.png" />
                                                <img
                                                    class="-img lazyload"
                                                    alt="บาคาร่าออนไลน์ คาสิโนออนไลน์ อันดับ 1 ของไทย"
                                                    width="253"
                                                    height="513"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-android-3.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>
                                        </div>
                                    </div>

                                    <div
                                        class="-slide-wrapper -bookmark-slider-nav-android"
                                        data-slickable='{"arrows":false,"dots":false,"slidesToShow":1,"fade":true,"infinite":true,"autoplay":false,"asNavFor":".-bookmark-slider-for-android"}'
                                    >
                                        <div class="-slide-inner-wrapper">
                                            <div class="-content-wrapper -center">
                                                <picture>
                                                    <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-1.webp" />
                                                    <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-1.png" />
                                                    <img
                                                        class="-icon lazyload"
                                                        alt="บาคาร่าออนไลน์ คาสิโนออนไลน์ อันดับ 1 ของไทย"
                                                        width="60"
                                                        height="60"
                                                        data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-1.png"
                                                        src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                    />
                                                </picture>
                                                <div class="-description">
                                                    เข้า Google Chrome แล้ว Search <br />
                                                    “WM356” เข้าสู่หน้าเว็บ
                                                </div>
                                            </div>
                                        </div>
                                        <div class="-slide-inner-wrapper">
                                            <div class="-content-wrapper -center">
                                                <picture>
                                                    <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-2.webp" />
                                                    <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-2.png" />
                                                    <img
                                                        class="-icon lazyload"
                                                        alt="บาคาร่าออนไลน์ คาสิโนออนไลน์ อันดับ 1 ของไทย"
                                                        width="60"
                                                        height="60"
                                                        data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-2.png"
                                                        src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                    />
                                                </picture>
                                                <div class="-description">เลือก “ติดตั้ง”</div>
                                            </div>
                                        </div>
                                        <div class="-slide-inner-wrapper">
                                            <div class="-content-wrapper -center">
                                                <picture>
                                                    <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-3.webp" />
                                                    <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-3.png" />
                                                    <img
                                                        class="-icon lazyload"
                                                        alt="บาคาร่าออนไลน์ คาสิโนออนไลน์ อันดับ 1 ของไทย"
                                                        width="60"
                                                        height="60"
                                                        data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-3.png"
                                                        src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                    />
                                                </picture>
                                                <div class="-description">
                                                    ตรวจสอบหน้า <br />
                                                    โฮมสกรีนว่ามีไอคอนขึ้นแล้ว
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade show active" id="nav-ios" role="tabpanel" aria-labelledby="nav-ios-tab">
                                    <div class="-slide-wrapper -bookmark-slider-for-ios" data-slickable='{"arrows":false,"dots":true,"slidesToShow":1,"fade":true,"infinite":true,"autoplay":false,"asNavFor":".-bookmark-slider-nav-ios"}'>
                                        <div class="-slide-inner-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-ios-1.webp" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-ios-1.png" />
                                                <img
                                                    class="-img lazyload"
                                                    alt="บาคาร่าออนไลน์ คาสิโนออนไลน์ อันดับ 1 ของไทย"
                                                    width="253"
                                                    height="513"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-ios-1.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>
                                        </div>
                                        <div class="-slide-inner-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-ios-2.webp" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-ios-2.png" />
                                                <img
                                                    class="-img lazyload"
                                                    alt="บาคาร่าออนไลน์ คาสิโนออนไลน์ อันดับ 1 ของไทย"
                                                    width="253"
                                                    height="513"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-ios-2.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>
                                        </div>
                                        <div class="-slide-inner-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-ios-3.webp" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-ios-3.png" />
                                                <img
                                                    class="-img lazyload"
                                                    alt="บาคาร่าออนไลน์ คาสิโนออนไลน์ อันดับ 1 ของไทย"
                                                    width="253"
                                                    height="513"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-ios-3.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>
                                        </div>
                                        <div class="-slide-inner-wrapper">
                                            <picture>
                                                <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-ios-4.webp" />
                                                <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-ios-4.png" />
                                                <img
                                                    class="-img lazyload"
                                                    alt="บาคาร่าออนไลน์ คาสิโนออนไลน์ อันดับ 1 ของไทย"
                                                    width="253"
                                                    height="513"
                                                    data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-ios-4.png"
                                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                />
                                            </picture>
                                        </div>
                                    </div>

                                    <div
                                        class="-slide-wrapper -bookmark-slider-nav-ios"
                                        data-slickable='{"arrows":false,"dots":false,"slidesToShow":1,"fade":true,"infinite":true,"autoplay":false,"asNavFor":".-bookmark-slider-for-ios"}'
                                    >
                                        <div class="-slide-inner-wrapper">
                                            <div class="-content-wrapper -center">
                                                <picture>
                                                    <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-1.webp" />
                                                    <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-1.png" />
                                                    <img
                                                        class="-icon lazyload"
                                                        alt="บาคาร่าออนไลน์ คาสิโนออนไลน์ อันดับ 1 ของไทย"
                                                        width="60"
                                                        height="60"
                                                        data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-1.png"
                                                        src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                    />
                                                </picture>
                                                <div class="-description">
                                                    เข้า Safari แล้ว Search “WM356” <br />
                                                    เข้าสู่หน้าเว็บ กดที่
                                                </div>
                                            </div>
                                        </div>
                                        <div class="-slide-inner-wrapper">
                                            <div class="-content-wrapper -center">
                                                <picture>
                                                    <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-2.webp" />
                                                    <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-2.png" />
                                                    <img
                                                        class="-icon lazyload"
                                                        alt="บาคาร่าออนไลน์ คาสิโนออนไลน์ อันดับ 1 ของไทย"
                                                        width="60"
                                                        height="60"
                                                        data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-2.png"
                                                        src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                    />
                                                </picture>
                                                <div class="-description">เลือก “เพิ่มลงใปยังหน้าจอโฮม”</div>
                                            </div>
                                        </div>
                                        <div class="-slide-inner-wrapper">
                                            <div class="-content-wrapper -center">
                                                <picture>
                                                    <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-3.webp" />
                                                    <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-3.png" />
                                                    <img
                                                        class="-icon lazyload"
                                                        alt="บาคาร่าออนไลน์ คาสิโนออนไลน์ อันดับ 1 ของไทย"
                                                        width="60"
                                                        height="60"
                                                        data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-3.png"
                                                        src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                    />
                                                </picture>
                                                <div class="-description">
                                                    กด “เพิ่ม”ทางลัดเข้าสู่เกมส์ <br />
                                                    ลงในหน้าจอโฮม
                                                </div>
                                            </div>
                                        </div>
                                        <div class="-slide-inner-wrapper">
                                            <div class="-content-wrapper -center">
                                                <picture>
                                                    <source type="image/webp" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-4.webp" />
                                                    <source type="image/png" data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-4.png" />
                                                    <img
                                                        class="-icon lazyload"
                                                        alt="บาคาร่าออนไลน์ คาสิโนออนไลน์ อันดับ 1 ของไทย"
                                                        width="60"
                                                        height="60"
                                                        data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/bookmark-slide-number-4.png"
                                                        src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                                    />
                                                </picture>
                                                <div class="-description">
                                                    ตรวจสอบหน้า <br />
                                                    โฮมสกรีนว่ามีไอคอนขึ้นแล้ว
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            Bonn.boots.push(function () {
                                const $bookmarkModal = $("#bookmarkModal");
                                if (!$bookmarkModal.length) {
                                    return;
                                }

                                const $slideWrapper = $bookmarkModal.find(".-slide-wrapper");
                                const slickSetPosition = () => $slideWrapper.slick("setPosition");

                                // WATCHING ON MODAL WAS OPENED
                                $bookmarkModal.on("shown.bs.modal", function (e) {
                                    slickSetPosition();
                                });

                                // WATCHING ON TAB WAS TOGGLED
                                $bookmarkModal.find('a[data-toggle="tab"]').on("shown.bs.tab", function (e) {
                                    slickSetPosition();
                                });
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>

        <div class="x-modal modal -v2 -modal-full-page" id="websiteMenuModal" tabindex="-1" role="dialog" aria-hidden="true" data-loading-container=".js-modal-content" data-ajax-modal-always-reload="true">
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>

                    <div class="modal-body -modal-body">
                        <div class="x-website-menu-modal-body">
                            <a href="index.html" class="nav-link -btn-logo">
                                <img
                                    alt="บาคาร่าออนไลน์ สล็อตออนไลน์ อันดับหนึ่งในประเทศไทย"
                                    class="img-fluid lazyload -img"
                                    width="400"
                                    height="150"
                                    data-src="build/images/logo.png?v=2"
                                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                />
                            </a>

                            <ul class="nav x-website-menu-entry-list">
                                <li class="nav-item -nav-item">
                                    <a class="nav-link -nav-link" href="https://ezmovie.co" target="_blank" rel="noopener nofollow">
                                        <img
                                            alt="บาคาร่าออนไลน์ สล็อตออนไลน์ อันดับหนึ่งในประเทศไทย"
                                            class="img-fluid lazyload -img"
                                            width="50"
                                            height="50"
                                            data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-website-menu-movie.png"
                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                        />

                                        <span class="-text">ดูหนัง</span>
                                    </a>
                                </li>

                                <li class="nav-item -nav-item">
                                    <a class="nav-link -nav-link" href="promotions.html">
                                        <img
                                            alt="บาคาร่าออนไลน์ สล็อตออนไลน์ อันดับหนึ่งในประเทศไทย"
                                            class="img-fluid lazyload -img"
                                            width="50"
                                            height="50"
                                            data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-website-menu-promotion.png"
                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                        />

                                        <span class="-text">โปรโมชั่น</span>
                                    </a>
                                </li>

                                <li class="nav-item -nav-item">
                                    <a class="nav-link -nav-link" href="event.html">
                                        <img
                                            alt="บาคาร่าออนไลน์ สล็อตออนไลน์ อันดับหนึ่งในประเทศไทย"
                                            class="img-fluid lazyload -img"
                                            width="50"
                                            height="50"
                                            data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-website-menu-event.png"
                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                        />

                                        <span class="-text">สิทธิพิเศษ</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="x-wrapper-right-container"></div>

        <div
            class="x-modal modal -v2"
            id="accountModal"
            data="customer-info"
            data-container="#accountModal"
        >
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-big -modal-main-account" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-body -modal-body">
                        <div class="x-modal-account-menu">
                            <ul class="navbar-nav">
                                <li class="nav-item -account-profile" >
                                    <button
                                        type="button"
                                        class="nav-link js-close-account-sidebar active"
                                        data="customer-info"
                                        data-container="#accountModal"
                                        data-active-menu="-account-profile"
                                        onclick="opentabaccount(event, 'accountprofile')"
                                    >
                                        <img alt="ข้อมูลบัญชี หวยออนไลน์ แทงหวยออนไลน์" class="img-fluid -icon-image" width="35" height="35" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-user.png" />
                                        <span class="-text-menu">ข้อมูลบัญชี</span>
                                    </button>
                                </li>

                                <li class="nav-item -account-bill-history">
                                    <button
                                        type="button"
                                        class="nav-link js-close-account-sidebar"
                                        data="customer-bill-history"
                                        data-container="#accountModal"
                                        data-active-menu="-account-bill-history"
                                        onclick="opentabaccount(event, 'accounthistory')"
                                    >
                                        <img alt="ประวัติ หวยออนไลน์ แทงหวยออนไลน์" class="img-fluid -icon-image" width="35" height="35" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-bill-history.png" />
                                        <span class="-text-menu">ประวัติ</span>
                                    </button>
                                </li>

                                <li class="nav-item -account-provider">
                                    <button
                                        type="button"
                                        class="nav-link js-close-account-sidebar"
                                        data="provider-user-info"
                                        data-container="#accountModal"
                                        data-active-menu="-account-provider"
                                        onclick="opentabaccount(event, 'accountProviderUser')"

                                    >
                                        <img alt="เข้าเล่นผ่านแอพ หวยออนไลน์ แทงหวยออนไลน์" class="img-fluid -icon-image" width="35" height="35" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-provider.png" />
                                        <span class="-text-menu">เข้าเล่นผ่านแอพ</span>
                                    </button>
                                </li>

                                <li class="nav-item -coupon">
                                    <button
                                        type="button"
                                        class="nav-link js-close-account-sidebar js-account-approve-aware"
                                        data="coupon-apply"
                                        data-container="#accountModal"
                                        data-active-menu="-coupon"
                                        onclick="opentabaccount(event, 'accountcoupon')"

                                    >
                                        <img alt="ใช้คูปอง หวยออนไลน์ แทงหวยออนไลน์" class="img-fluid -icon-image" width="35" height="35" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-coupon.png" />
                                        <span class="-text-menu">ใช้คูปอง</span>
                                    </button>
                                </li>

                                <li class="nav-item -join-promotion">
                                    <button
                                        type="button"
                                        class="nav-link js-close-account-sidebar"
                                        data="promotion"
                                        data-container="#accountModal"
                                        data-active-menu="-join-promotion"
                                        onclick="opentabaccount(event, 'accountpromotion')"

                                    >
                                        <img alt="โปรโมชั่นที่เข้าร่วม หวยออนไลน์ แทงหวยออนไลน์" class="img-fluid -icon-image" width="35" height="35" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-promotion.png" />
                                        <span class="-text-menu">โปรโมชั่นที่เข้าร่วม</span>
                                    </button>
                                </li>

                                <li class="nav-item -promotion-return-by-user">
                                    <button
                                        type="button"
                                        class="nav-link js-close-account-sidebar"
                                        data="promotion-return"
                                        data-container="#accountModal"
                                        data-active-menu="-promotion-return-by-user"
                                        onclick="opentabaccount(event, 'accountreturn')"

                                    >
                                        <img alt="โบนัสเพิ่ม ทุกสัปดาห์ หวยออนไลน์ แทงหวยออนไลน์" class="img-fluid -icon-image" width="35" height="35" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-bonus.png" />
                                        <span class="-text-menu">รับคืนยอดเสีย</span>
                                    </button>
                                </li>

                                <li class="nav-item -logout">
                                    <a href="../" class="nav-link js-require-confirm" data-title="ต้องการออกจากระบบ ?">
                                        <img alt="ออกจากระบบ หวยออนไลน์ แทงหวยออนไลน์" class="img-fluid -icon-image" width="35" height="35" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-logout.png" />
                                        <span class="-text-menu">ออกจากระบบ</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="js-profile-account-modal -layout-account">






                            <div class="x-account-profile -v2 tabcontent" id="accountprofile" style="display: block;">
                                <div data-animatable="fadeInModal" class="-profile-container animated fadeInModal">
                                    <h3 class="x-title-modal text-center mx-auto">
                                        ข้อมูลบัญชี
                                    </h3>

                                    <div class="text-center">
                                        <div class="my-3">
                                            <div class="x-profile-image">
                                                <img class="img-fluid -profile-image" src="/images/icon/4f037d726e06fc63eb4c615fd98558f6.png?v=1" alt="customer image" />
                                            </div>
                                        </div>

                                        <div class="my-3">
                                            <div class="-text-username">Username: 0963056322</div>
                                            <a href="javascript:void(0)" class="-link-change-password" data-toggle="modal" data-target="#changePasswordModal">
                                                <u>เปลี่ยนรหัสผ่าน</u>
                                            </a>
                                        </div>
                                    </div>

                                    <div class="-bank-info-container">
                                        <div class="x-customer-bank-info-container -v2">
                                            <div class="media m-auto">
                                                <img src="https://wm356.co/media/cache/admin_preview/202305/taxon/fde8f3e3bd69087c438ff67961050942.png" class="-img rounded-circle" width="50" height="50" alt="bank-ktb" />
                                                <div class="-content-wrapper">
                                                    <span class="-name">นายกฤษดา ศรีสระ</span>
                                                    <span class="-number">484-042-3512</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="x-admin-contact text-center">
                            <span class="x-text-with-link-component">
                                <label class="-text-message">พบปัญหา</label>
                                <a href="https://lin.ee/BpAUj1s" class="-link-message" target="_blank" rel="noopener">
                                    <u>ติดต่อฝ่ายบริการลูกค้า</u>
                                </a>
                            </span>
                                    </div>

                                    <div class="js-has-info"></div>
                                </div>
                            </div>





                            <div class="-outer-history-wrapper tabcontent" id="accounthistory">
                                <div class="x-bill-history-container">
                                    <h3 class="x-title-modal text-center mb-3">
                                        ประวัติการทำรายการ
                                    </h3>

                                    <div
                                        class="wg-container wg-container__wg_bill_history wg--loaded"
                                        data-widget-name="wg_bill_history"
                                        data-widget-options='{"script_path":null,"style_path":null,"image_path":null,"visibility":"away","visibility_offset":"100%","render_url":"\/_widget","render_method":"GET","attr_style":null,"attr_class":null,"scroll_position":"current","options":{},"callback":{},"mode":"clear","mask_mode":"over","mask_style":"wg-loading","limit":20,"page":1,"template":"@Base\/Widget\/billHistory.html.twig","name":"wg_bill_history"}'
                                        data-widget-user-options='{"page":1}'
                                    >
                                        <div class="wg-content">
                                            <table class="table table-borderless table-striped">
                                                <tbody>
                                                <tr>
                                                    <td class="-description-body-wrapper">
                                                        <div class="-title-wrapper">
                                                            <span class="-title">ฝากเงิน</span>
                                                        </div>
                                                        <div class="-state-wrapper">
                                                            <span class="-state-text">สถานะ : </span>

                                                            <img src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic_fail.png" class="-ic" alt="State icon image" />

                                                            <span class="-state-title">ไม่สำเร็จ (ยกเลิก)</span>
                                                            <span class="-state-title -short">ไม่สำเร็จ</span>
                                                        </div>
                                                    </td>
                                                    <td class="-transaction-body-wrapper">
                                                        <div class="-amount -deposit">1,000</div>
                                                        <div class="-datetime">27/09/66 - 19:00 น.</div>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="-description-body-wrapper">
                                                        <div class="-title-wrapper">
                                                            <span class="-title">ฝากเงิน</span>
                                                        </div>
                                                        <div class="-state-wrapper">
                                                            <span class="-state-text">สถานะ : </span>

                                                            <img src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic_success.png" class="-ic" alt="State icon image" />

                                                            <span class="-state-title">สำเร็จ</span>
                                                            <span class="-state-title -short">สำเร็จ</span>
                                                        </div>
                                                    </td>
                                                    <td class="-transaction-body-wrapper">
                                                        <div class="-amount -deposit">1,000</div>
                                                        <div class="-datetime">17/09/66 - 14:08 น.</div>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="-description-body-wrapper">
                                                        <div class="-title-wrapper">
                                                            <span class="-title">ฝากเงิน</span>
                                                        </div>
                                                        <div class="-state-wrapper">
                                                            <span class="-state-text">สถานะ : </span>

                                                            <img src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic_success.png" class="-ic" alt="State icon image" />

                                                            <span class="-state-title">สำเร็จ</span>
                                                            <span class="-state-title -short">สำเร็จ</span>
                                                        </div>
                                                    </td>
                                                    <td class="-transaction-body-wrapper">
                                                        <div class="-amount -deposit">500</div>
                                                        <div class="-datetime">17/09/66 - 12:35 น.</div>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="-description-body-wrapper">
                                                        <div class="-title-wrapper">
                                                            <span class="-title">ฝากเงิน</span>
                                                        </div>
                                                        <div class="-state-wrapper">
                                                            <span class="-state-text">สถานะ : </span>

                                                            <img src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic_success.png" class="-ic" alt="State icon image" />

                                                            <span class="-state-title">สำเร็จ</span>
                                                            <span class="-state-title -short">สำเร็จ</span>
                                                        </div>
                                                    </td>
                                                    <td class="-transaction-body-wrapper">
                                                        <div class="-amount -deposit">500</div>
                                                        <div class="-datetime">16/09/66 - 20:06 น.</div>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>






                            <div id="accountProviderUser" class="x-account-provider -has-provider tabcontent">
                                <div data-animatable="fadeInModal" class="-account-provider-container animated fadeInModal">
                                    <h3 class="x-title-modal text-center mx-auto">
                                        เข้าเล่นผ่านแอพ
                                    </h3>

                                    <div class="-account-provider-inner x-account-provider-v2">
                                        <div class="-provider-row-wrapper">
                                            <div class="-img-wrapper">
                                                <img class="img-fluid -img-provider" alt="Logo Dream Gaming square" width="45" height="45" src="https://asset.cloudigame.co/build/admin/img/lobby_main/sm-dream-gaming-logo-square.png" />
                                            </div>
                                            <div class="-account-wrapper">
                                                <div class="d-flex mb-2">
                                                    <div class="-text-provider-user -first-column">
                                                        <div>Username</div>
                                                        <div>:</div>
                                                    </div>
                                                    <div class="-text-provider-user">smtwm356dg000347@B5E</div>
                                                </div>
                                                <div class="d-flex">
                                                    <div class="-text-provider-user -first-column">
                                                        <div>Password</div>
                                                        <div>:</div>
                                                    </div>
                                                    <div class="-text-provider-user">Aa309361</div>
                                                </div>
                                            </div>
                                            <div class="-btn-action-wrapper">
                                                <a
                                                    href="javascript:void(0);"
                                                    class="-text-copy-me js-copy-to-clipboard f-9 mb-2"
                                                    data-container="accountModal"
                                                    data-html="true"
                                                    data-message="✓ คัดลอกแล้ว"
                                                    data-copy-me="smtwm356dg000347@B5E"
                                                    data-theme="copy-me"
                                                    data-arrow="true"
                                                >
                                                    <i class="fas fa-copy"></i>
                                                    <div id="-copy-message"></div>
                                                </a>
                                                <a href="javascript:void(0);" class="-text-copy-me js-copy-to-clipboard f-9" data-container="accountModal" data-html="true" data-message="✓ คัดลอกแล้ว" data-copy-me="Aa309361" data-theme="copy-me" data-arrow="true">
                                                    <i class="fas fa-copy"></i>
                                                    <div id="-copy-message"></div>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>



                            <div class="x-account-coupon tabcontent" id="accountcoupon">
                                <div data-animatable="fadeInModal" class="-coupon-container animated fadeInModal">
                                    <h3 class="x-title-modal text-center mx-auto">
                                        ใช้คูปอง
                                    </h3>

                                    <div class="-coupon-member-detail mb-3 mt-5">
                                        <div class="-coupon-box d-flex">
                                            <img alt="คูปอง เว็บไซต์พนันออนไลน์ คาสิโนออนไลน์" class="img-fluid -ic-coupon m-auto" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-coupon.png" />
                                        </div>
                                        <div class="-text-box-container">
                                            <div class="-title-member">สำหรับลูกค้าคนพิเศษ</div>
                                            <div class="-text-member">0963056322</div>
                                        </div>
                                    </div>

                                    <div class="-form-coupon-container">
                                        <form name="coupon" method="post" action="/account/_ajax_/coupon-apply" data-ajax-form="/account/_ajax_/coupon-apply" data-callback="_onCouponApply_" data-dismiss-modal="#accountModal" data-container="#accountModal">
                                            <div class="my-4 -x-input-icon">
                                                <img alt="คูปอง เว็บไซต์พนันออนไลน์ คาสิโนออนไลน์" class="-icon" src="build/images/ic-coupon-input.png?v=2" />

                                                <input type="text" id="coupon_coupon" name="coupon[coupon]" required="required" class="x-coupon-input text-center form-control" placeholder="รหัสคูปอง" />
                                            </div>

                                            <div class="-btn-submit-container">
                                                <button type="submit" class="btn -submit btn-primary">
                                                    ยืนยัน
                                                </button>
                                            </div>

                                            <input type="hidden" id="coupon__token" name="coupon[_token]" value="F0RoAj-5ycV0SZkTwJoHTYeEKQaGPB_NKJ1gBe-OqzE" />
                                        </form>
                                    </div>
                                </div>
                            </div>







                            <div class="x-account-promotion text-center tabcontent" id="accountpromotion">
                                <div class="-account-promotion-container animated fadeInModal" data-animatable="fadeInModal">
                                    <h3 class="x-title-modal text-center mx-auto">
                                        โปรโมชั่นที่เข้าร่วม
                                    </h3>

                                    <div class="-no-result-container">
                                        <img alt="เว็บไซต์พนันออนไลน์ คาสิโนออนไลน์" class="img-fluid -no-result-img" width="150" height="150" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-promotion-no-result.png" />
                                    </div>
                                    <div class="text-center -text-container">
                                        คุณยังไม่มีโปรโมชั่นที่เข้าร่วม
                                    </div>
                                </div>
                            </div>


                            <div class="x-promotion-return-by-user-container tabcontent" id="accountreturn" data-animatable="fadeInUp">
                                <h3 class="x-title-modal text-center mx-auto">
                                    รับคืนยอดเสีย
                                </h3>

                                <div class="-group-round-container -no-data">
                                    <div class="-date-range-container text-center">
                                        ยอดโบนัสระหว่างวันที่ 18 - 24 ก.ย. 2023
                                    </div>
                                </div>

                                <div class="text-center">
                                    <button type="button" disabled="" class="btn btn-primary -promotion-return-btn">
                                        <span class="-text-btn">ไม่เข้าเงื่อนไข</span>
                                    </button>
                                </div>

                                <div class="-description-container">
                                    <div>
                                        คุณไม่เข้าเงื่อนไขการรับโบนัส
                                    </div>
                                    <div><span class="-text-description">โปรดอ่านเงื่อนไขการเข้าร่วม</span>ด้านล่างค่ะ</div>
                                </div>

                                <div class="-condition-container">
                                    <div class="-condition-title"><u>โปรดอ่านเงื่อนไข</u></div>
                                    <div class="x-promotion-content">
                                        <p>
                                            <big><strong>เล่นเสียให้คืน 5% ทุกสัปดาห์</strong></big><br />
                                            ► รับโบนัสทุกวันจันทร์ 1 ครั้ง / สัปดาห์ (ตัดรอบ อังคาร 00:00 ถึง 23:59 วันจันทร์)<br />
                                            ► ต้องมียอดเทิร์นโอเวอร์ 5 เท่าของเงินฝากภายในสัปดาห์ (NET Tureover)<br />
                                            ► โบนัสจะได้รับทุกวันจันทร์สามารถกดรับได้ที่หน้าเว็บ<br />
                                            ► เพียงมียอดเล่น 50% ของโบนัสที่ได้รับสามารถถอนได้เลย<br />
                                            ► ต้องมียอดเสียมากกว่า 2000 บาทต่อสัปดาห์จึงจะได้รับยอด 5%<br />
                                            ► หลังจากรับโปรโมชั่นเครดิตมีอายุการใช้งาน 3 วันหลังจากนั้นเครดิตคืนยอดเสียจะถูกปรับเป็น 0<br />
                                            <a href="/term-and-condition">เงื่อนไขและกติกาพื้นฐานจะถูกนำมาใช้กับโปรโมชั่นนี้</a>
                                        </p>
                                    </div>
                                </div>

                                <div class="my-3">
                                    <div class="x-admin-contact -no-fixed">
            <span class="x-text-with-link-component">
                <label class="-text-message">ติดปัญหา</label>
                <a href="https://lin.ee/BpAUj1s" class="-link-message" target="_blank" rel="noopener">
                    <u>ติดต่อฝ่ายบริการลูกค้า</u>
                </a>
            </span>
                                    </div>
                                </div>
                            </div>





                        </div>

                    </div>
                </div>
            </div>

        </div>

        <div
            class="x-modal modal -v2 -with-more-than-half-size"
            id="accountModalMobile"
            data="customer-info?isMobileView=1"
            data-container="#accountModalMobile"
        >
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-mobile -account-modal -no-fixed-button" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>

                    <div class="modal-header -modal-header">
                        <div class="x-modal-mobile-header">
                            <div class="-header-mobile-container">
                                <h3 class="x-title-modal text-center mx-auto">
                                    ข้อมูลบัญชี
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body -modal-body">
                        <div class="x-account-profile -v2">
                            <div data-animatable="fadeInModal" class="-profile-container animated fadeInModal">
                                <div class="text-center">
                                    <div class="my-3">
                                        <div class="x-profile-image">
                                            <img class="img-fluid -profile-image" src="/images/icon/4f037d726e06fc63eb4c615fd98558f6.png?v=1" alt="customer image" />
                                        </div>
                                    </div>

                                    <div class="my-3">
                                        <div class="-text-username">Username: 0963056322</div>
                                        <a href="javascript:void(0)" class="-link-change-password" data-toggle="modal" data-target="#changePasswordModal">
                                            <u>เปลี่ยนรหัสผ่าน</u>
                                        </a>
                                    </div>
                                </div>

                                <div class="-bank-info-container">
                                    <div class="x-customer-bank-info-container -v2">
                                        <div class="media m-auto">
                                            <img src="https://wm356.co/media/cache/admin_preview/202305/taxon/fde8f3e3bd69087c438ff67961050942.png" class="-img rounded-circle" width="50" height="50" alt="bank-ktb" />
                                            <div class="-content-wrapper">
                                                <span class="-name">นายกฤษดา ศรีสระ</span>
                                                <span class="-number">484-042-3512</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="x-admin-contact text-center">
                        <span class="x-text-with-link-component">
                            <label class="-text-message">พบปัญหา</label>
                            <a href="https://lin.ee/BpAUj1s" class="-link-message" target="_blank" rel="noopener">
                                <u>ติดต่อฝ่ายบริการลูกค้า</u>
                            </a>
                        </span>
                                </div>

                                <div class="js-has-info"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div
            class="x-modal modal -v2 -with-more-than-half-size"
            id="providerUserModalMobile"
            data="provider-user-info?isMobileView=1"
            data-container="#providerUserModalMobile"
        >
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-mobile -no-fixed-button" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>

                    <div class="modal-header -modal-header">
                        <div class="x-modal-mobile-header">
                            <div class="-header-mobile-container">
                                <h3 class="x-title-modal text-center mx-auto">
                                    เข้าเล่นผ่านแอพ
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body -modal-body">
                        <div id="accountProviderUser" class="x-account-provider -has-provider">
                            <div data-animatable="fadeInModal" class="-account-provider-container animated fadeInModal">
                                <div class="-account-provider-inner x-account-provider-v2">
                                    <div class="-provider-row-wrapper">
                                        <div class="-img-wrapper">
                                            <img class="img-fluid -img-provider" alt="Logo Dream Gaming square" width="45" height="45" src="https://asset.cloudigame.co/build/admin/img/lobby_main/sm-dream-gaming-logo-square.png" />
                                        </div>
                                        <div class="-account-wrapper">
                                            <div class="d-flex mb-2">
                                                <div class="-text-provider-user -first-column">
                                                    <div>Username</div>
                                                    <div>:</div>
                                                </div>
                                                <div class="-text-provider-user">smtwm356dg000347@B5E</div>
                                            </div>
                                            <div class="d-flex">
                                                <div class="-text-provider-user -first-column">
                                                    <div>Password</div>
                                                    <div>:</div>
                                                </div>
                                                <div class="-text-provider-user">Aa309361</div>
                                            </div>
                                        </div>
                                        <div class="-btn-action-wrapper">
                                            <a
                                                href="javascript:void(0);"
                                                class="-text-copy-me js-copy-to-clipboard f-9 mb-2"
                                                data-container="providerUserModalMobile"
                                                data-html="true"
                                                data-message="✓ คัดลอกแล้ว"
                                                data-copy-me="smtwm356dg000347@B5E"
                                                data-theme="copy-me"
                                                data-arrow="true"
                                            >
                                                <i class="fas fa-copy"></i>
                                                <div id="-copy-message"></div>
                                            </a>
                                            <a
                                                href="javascript:void(0);"
                                                class="-text-copy-me js-copy-to-clipboard f-9"
                                                data-container="providerUserModalMobile"
                                                data-html="true"
                                                data-message="✓ คัดลอกแล้ว"
                                                data-copy-me="Aa309361"
                                                data-theme="copy-me"
                                                data-arrow="true"
                                            >
                                                <i class="fas fa-copy"></i>
                                                <div id="-copy-message"></div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div
            class="x-modal modal -v2 -with-more-than-half-size"
            id="couponModalMobile"
            data="coupon-apply?isMobileView=1"
            data-container="#couponModalMobile"
        >
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-mobile" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>

                    <div class="modal-header -modal-header">
                        <div class="x-modal-mobile-header">
                            <div class="-header-mobile-container">
                                <h3 class="x-title-modal text-center mx-auto">
                                    ใช้คูปอง
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body -modal-body">
                        <div class="x-account-coupon">
                            <div data-animatable="fadeInModal" class="-coupon-container animated fadeInModal">
                                <div class="-coupon-member-detail mb-3 mt-5">
                                    <div class="-coupon-box d-flex">
                                        <img alt="คูปอง เว็บไซต์พนันออนไลน์ คาสิโนออนไลน์" class="img-fluid -ic-coupon m-auto" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-coupon.png" />
                                    </div>
                                    <div class="-text-box-container">
                                        <div class="-title-member">สำหรับลูกค้าคนพิเศษ</div>
                                        <div class="-text-member">0963056322</div>
                                    </div>
                                </div>

                                <div class="-form-coupon-container">
                                    <form
                                        name="coupon"
                                        method="post"
                                        data-dismiss-modal="#couponModalMobile"
                                        data-container="#couponModalMobile"
                                    >
                                        <div class="my-4 -x-input-icon">
                                            <img alt="คูปอง เว็บไซต์พนันออนไลน์ คาสิโนออนไลน์" class="-icon" src="build/images/ic-coupon-input.png?v=2" />

                                            <input type="text" id="coupon_coupon" name="coupon[coupon]" required="required" class="x-coupon-input text-center form-control" placeholder="รหัสคูปอง" />
                                        </div>

                                        <div class="-btn-submit-container">
                                            <button type="submit" class="btn -submit btn-primary">
                                                ยืนยัน
                                            </button>
                                        </div>

                                        <input type="hidden" id="coupon__token" name="coupon[_token]" value="F0RoAj-5ycV0SZkTwJoHTYeEKQaGPB_NKJ1gBe-OqzE" />
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div
            class="x-modal modal -v2 -with-more-than-half-size"
            id="joinPromotionModalMobile"
            data="promotion?isMobileView=1"
            data-container="#joinPromotionModalMobile"
        >
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-mobile -no-fixed-button -no-padding-x" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>

                    <div class="modal-header -modal-header">
                        <div class="x-modal-mobile-header">
                            <div class="-header-mobile-container">
                                <h3 class="x-title-modal text-center mx-auto">
                                    โปรโมชั่นที่เข้าร่วม
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body -modal-body">
                        <div class="x-account-promotion text-center">
                            <div class="-account-promotion-container animated fadeInModal" data-animatable="fadeInModal">
                                <div class="-no-result-container">
                                    <img alt="เว็บไซต์พนันออนไลน์ คาสิโนออนไลน์" class="img-fluid -no-result-img" width="150" height="150" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-promotion-no-result.png" />
                                </div>
                                <div class="text-center -text-container">
                                    คุณยังไม่มีโปรโมชั่นที่เข้าร่วม
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div
            class="x-modal modal -v2 -with-backdrop -with-separator -with-more-than-half-size"
            id="depositModal"
            tabindex="-1"
            role="dialog"
            data-loading-container=".modal-body"
            data-ajax-modal-always-reload="true"
            data="deposit"
            data-container="#depositModal"
            style="display: none;"
            aria-hidden="true"
        >
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-deposit -modal-mobile" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-header -modal-header">
                        <h3 class="x-title-modal m-auto">
                            ฝากเงิน
                        </h3>
                    </div>

                    <div class="modal-body -modal-body">
                        <div class="x-deposit-form -v2">
                            <div class="-deposit-container">
                                <div data-animatable="fadeInModal" class="order-lg-2 -form order-0 animated fadeInModal">
                                    <div class="-deposit-form-inner-wrapper">
                                        <form novalidate="" name="deposit" method="post" data-ajax-form="/account/_ajax_/deposit" data-container="#depositModal">
                                            <div class="-fake-bg-bottom-wrapper">
                                                <div class="x-modal-separator-container">
                                                    <div class="-top">
                                                        <div class="-promotion-intro-deposit -spacer">
                                                            <div class="js-promotion-active-html"></div>
                                                        </div>

                                                        <div class="-spacer">
                                                            <div class="js-turnover d-none text-center">
                                                                <div class="-turnover-wrapper">Turnover : <span>0</span></div>
                                                            </div>
                                                        </div>

                                                        <div class="-spacer pt-2">
                                                            <div class="-x-input-icon x-input-operator mb-3 flex-column">
                                                                <button type="button" class="-icon-left -btn-icon js-adjust-amount-by-operator" data-operator="-" data-value="10">
                                                                    <i class="fas fa-minus-circle"></i>
                                                                </button>

                                                                <input
                                                                    type="text"
                                                                    id="deposit_amount"
                                                                    name="deposit[amount]"
                                                                    required="required"
                                                                    pattern="[0-9]*"
                                                                    class="x-form-control -no text-center js-deposit-input-amount form-control"
                                                                    placeholder="เงินฝากขั้นต่ำ 10"
                                                                    inputmode="text"
                                                                />
                                                                <button type="button" class="-icon-right -btn-icon js-adjust-amount-by-operator" data-operator="+" data-value="10">
                                                                    <i class="fas fa-plus-circle"></i>
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <div class="-spacer">
                                                            <div class="x-select-amount js-quick-amount -v2" data-target-input="#deposit_amount">
                                                                <div class="-amount-container">
                                                                    <button type="button" class="btn btn-block -btn-select-amount" data-amount="50">
                                                                        <span class="-no">50</span>
                                                                    </button>
                                                                </div>
                                                                <div class="-amount-container">
                                                                    <button type="button" class="btn btn-block -btn-select-amount" data-amount="100">
                                                                        <span class="-no">100</span>
                                                                    </button>
                                                                </div>
                                                                <div class="-amount-container">
                                                                    <button type="button" class="btn btn-block -btn-select-amount" data-amount="300">
                                                                        <span class="-no">300</span>
                                                                    </button>
                                                                </div>
                                                                <div class="-amount-container">
                                                                    <button type="button" class="btn btn-block -btn-select-amount" data-amount="500">
                                                                        <span class="-no">500</span>
                                                                    </button>
                                                                </div>
                                                                <div class="-amount-container">
                                                                    <button type="button" class="btn btn-block -btn-select-amount" data-amount="1000">
                                                                        <span class="-no">1,000</span>
                                                                    </button>
                                                                </div>
                                                                <div class="-amount-container">
                                                                    <button type="button" class="btn btn-block -btn-select-amount" data-amount="5000">
                                                                        <span class="-no">5,000</span>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="-spacer">
                                                            <hr class="-liner" />
                                                        </div>

                                                        <div class="x-deposit-promotion-outer-container js-scroll-ltr -fade -on-left -on-right">
                                                            <div
                                                                class="x-deposit-promotion -v2 -slide pt-0 -has-promotion"
                                                                data-scroll-booster-container=".x-deposit-promotion-outer-container"
                                                                data-scroll-booster-content=".x-deposit-promotion"
                                                                style="transform: translate(0px, 0px);"
                                                            >
                                                                <div class="-promotion-box-wrapper">
                                                                    <button type="button" class="btn -promotion-box-apply-btn js-promotion-apply" data-url="/promotion/2/apply" data-type="deposit" data-display-slide-mode="true">
                                                                        <picture>
                                                                            <source type="image/webp" srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.webp?v=2" />
                                                                            <source type="image/png" srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png" />
                                                                            <img class="-img" alt="ทุกยอดฝากรับโบนัส 2% สูงสุด 300 บาท" width="26" height="26" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png" />
                                                                        </picture>

                                                                        <span class="-title">ทุกยอดฝากรับโบนัส 2% สูงสุด 300 บาท</span>
                                                                    </button>
                                                                    <a href="javascript:void(0)" class="-promotion-box-cancel-btn js-cancel-promotion" data-url="/promotion-active/cancel" data-display-slide-mode="true">
                                                                        <i class="fas fa-times"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="text-center -spacer">
                                                            <button type="submit" class="btn -submit btn-primary my-0 my-lg-3">
                                                                ยืนยัน
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="-bottom"></div>
                                                </div>
                                            </div>

                                            <input type="hidden" id="deposit__token" name="deposit[_token]" value="2GDdByVzCdOZZX6U5AQWcfaLUVsVOFnn25BLNSacVoA" />
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="x-modal modal -v2 -with-more-than-half-size"
            id="withdrawModal"
            data="withdraw"
            data-container="#withdrawModal"
        >
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-header -modal-header">
                        <h3 class="x-title-modal m-auto">
                            ฝากเงิน
                        </h3>
                    </div>
                    <div class="modal-body -modal-body">
                        <div class="x-withdraw-form -v2">
                            <form novalidate="" name="withdraw" method="post" data-ajax-form="/account/_ajax_/withdraw" data-container="#withdrawModal">
                                <div data-animatable="fadeInModal" class="-animatable-container animated fadeInModal">
                                    <div class="text-center d-flex flex-column">
                                        <div class="-x-input-icon x-input-operator mb-3 flex-column">
                                            <button type="button" class="-icon-left -btn-icon js-adjust-amount-by-operator" data-operator="-" data-value="10">
                                                <i class="fas fa-minus-circle"></i>
                                            </button>
                                            <input
                                                type="text"
                                                id="withdraw_amount"
                                                name="withdraw[amount]"
                                                required="required"
                                                pattern="[0-9]*"
                                                class="x-form-control text-center js-withdraw-input-amount -no form-control"
                                                placeholder="เงินถอนขั้นต่ำ 100"
                                                inputmode="text"
                                            />
                                            <button type="button" class="-icon-right -btn-icon js-adjust-amount-by-operator" data-operator="+" data-value="10">
                                                <i class="fas fa-plus-circle"></i>
                                            </button>
                                        </div>

                                        <div class="x-select-amount js-quick-amount -v2" data-target-input="#withdraw_amount">
                                            <div class="-amount-container">
                                                <button type="button" class="btn btn-block -btn-select-amount" data-amount="100">
                                                    <span class="-no">100</span>
                                                </button>
                                            </div>
                                            <div class="-amount-container">
                                                <button type="button" class="btn btn-block -btn-select-amount" data-amount="300">
                                                    <span class="-no">300</span>
                                                </button>
                                            </div>
                                            <div class="-amount-container">
                                                <button type="button" class="btn btn-block -btn-select-amount" data-amount="500">
                                                    <span class="-no">500</span>
                                                </button>
                                            </div>
                                            <div class="-amount-container">
                                                <button type="button" class="btn btn-block -btn-select-amount" data-amount="1000">
                                                    <span class="-no">1,000</span>
                                                </button>
                                            </div>
                                            <div class="-amount-container">
                                                <button type="button" class="btn btn-block -btn-select-amount" data-amount="2000">
                                                    <span class="-no">2,000</span>
                                                </button>
                                            </div>
                                            <div class="-amount-container">
                                                <button type="button" class="btn btn-block -btn-select-amount" data-amount="5000">
                                                    <span class="-no">5,000</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn -submit btn-primary my-0 my-lg-3">
                                            ยืนยัน
                                        </button>
                                    </div>
                                </div>

                                <input type="hidden" id="withdraw__token" name="withdraw[_token]" value="Q3VDBE8vDGxQb-MAn0yHIDeh-SSvz1Xe7kSzooyneZI" />
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div
            class="x-modal modal -v2"
            id="depositChoosePromotionModal"
            data="promotions/in-deposit"
            data-container="#depositChoosePromotionModal"
        >
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-header -modal-header">
                        <h3 class="x-title-modal d-inline-block m-auto">
                            <span></span>
                        </h3>
                    </div>
                    <div class="modal-body -modal-body">
                        <div class="js-modal-content"></div>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="x-modal modal -v2 -with-more-than-half-size"
            id="promotionReturnByUserModalMobile"
            data="promotion-return?isMobileView=1"
            data-container="#promotionReturnByUserModalMobile"
        >
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-mobile -no-fixed-button" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>

                    <div class="modal-header -modal-header">
                        <div class="x-modal-mobile-header">
                            <div class="-header-mobile-container">
                                <h3 class="x-title-modal text-center mx-auto">
                                    รับคืนยอดเสีย
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body -modal-body">
                        <div class="x-promotion-return-by-user-container animated fadeInUp" data-animatable="fadeInUp">
                            <div class="-group-round-container -no-data">
                                <div class="-date-range-container text-center">
                                    ยอดโบนัสระหว่างวันที่ 18 - 24 ก.ย. 2023
                                </div>
                            </div>

                            <div class="text-center">
                                <button type="button" disabled="" class="btn btn-primary -promotion-return-btn">
                                    <span class="-text-btn">ไม่เข้าเงื่อนไข</span>
                                </button>
                            </div>

                            <div class="-description-container">
                                <div>
                                    คุณไม่เข้าเงื่อนไขการรับโบนัส
                                </div>
                                <div><span class="-text-description">โปรดอ่านเงื่อนไขการเข้าร่วม</span>ด้านล่างค่ะ</div>
                            </div>

                            <div class="-condition-container">
                                <div class="-condition-title"><u>โปรดอ่านเงื่อนไข</u></div>
                                <div class="x-promotion-content">
                                    <p>
                                        <big><strong>เล่นเสียให้คืน 5% ทุกสัปดาห์</strong></big><br />
                                        ► รับโบนัสทุกวันจันทร์ 1 ครั้ง / สัปดาห์ (ตัดรอบ อังคาร 00:00 ถึง 23:59 วันจันทร์)<br />
                                        ► ต้องมียอดเทิร์นโอเวอร์ 5 เท่าของเงินฝากภายในสัปดาห์ (NET Tureover)<br />
                                        ► โบนัสจะได้รับทุกวันจันทร์สามารถกดรับได้ที่หน้าเว็บ<br />
                                        ► เพียงมียอดเล่น 50% ของโบนัสที่ได้รับสามารถถอนได้เลย<br />
                                        ► ต้องมียอดเสียมากกว่า 2000 บาทต่อสัปดาห์จึงจะได้รับยอด 5%<br />
                                        ► หลังจากรับโปรโมชั่นเครดิตมีอายุการใช้งาน 3 วันหลังจากนั้นเครดิตคืนยอดเสียจะถูกปรับเป็น 0<br />
                                        <a href="/term-and-condition">เงื่อนไขและกติกาพื้นฐานจะถูกนำมาใช้กับโปรโมชั่นนี้</a>
                                    </p>
                                </div>
                            </div>

                            <div class="my-3">
                                <div class="x-admin-contact -no-fixed">
                        <span class="x-text-with-link-component">
                            <label class="-text-message">ติดปัญหา</label>
                            <a href="https://lin.ee/BpAUj1s" class="-link-message" target="_blank" rel="noopener">
                                <u>ติดต่อฝ่ายบริการลูกค้า</u>
                            </a>
                        </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div
            class="x-modal modal -v2 -alert-reset-passcode-modal -with-half-size"
            id="alertResetPasscodeModal"
            data-container="#alertResetPasscodeModal"
        >
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content -modal-content">
                    <div class="modal-header -modal-header"></div>
                    <div class="modal-body -modal-body">
                        <div class="x-register-tab-container js-tab-pane-checker-v3">
                            <div class="container">
                                <ul class="nav nav-tabs x-register-tab">
                                    <li class="nav-item -confirmRequestTab" id="tab-confirmRequestTab">
                                        <a data-toggle="tab" href="https://wm356.co/#tab-content-logged-confirmRequestTab" class="nav-link">
                                            confirmRequestTab
                                        </a>
                                    </li>
                                    <li class="nav-item -resetPasswordVerifyOtp" id="tab-resetPasswordVerifyOtp">
                                        <a data-toggle="tab" href="https://wm356.co/#tab-content-logged-resetPasswordVerifyOtp" class="nav-link">
                                            resetPasswordVerifyOtp
                                        </a>
                                    </li>
                                    <li class="nav-item -resetPasswordSetPassword" id="tab-resetPasswordSetPassword">
                                        <a data-toggle="tab" href="https://wm356.co/#tab-content-logged-resetPasswordSetPassword" class="nav-link">
                                            resetPasswordSetPassword
                                        </a>
                                    </li>
                                    <li class="nav-item -resetPasswordResult" id="tab-resetPasswordResult">
                                        <a data-toggle="tab" href="https://wm356.co/#tab-content-logged-resetPasswordResult" class="nav-link">
                                            resetPasswordResult
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <div class="x-alert-reset-passcode-tabs x-modal-body-base -v3">
                                <div class="tab-content">
                                    <div class="tab-pane active" id="tab-content-logged-confirmRequestTab" data-completed-dismiss-modal="">
                                        <div class="x-tab-confirm-request">
                                            <div class="x-title-register-modal-v3">
                                                <span class="-title">ประกาศ</span>
                                            </div>

                                            <p class="mt-3 -description">
                                                เนื่องจากมีการเปลี่ยนรูปแบบการใช้งาน <br />
                                                ลูกค้ากรุณาตั้งรหัสผ่านใหม่เป็นเลข 6 หลัก <br />
                                                เพื่อเข้าสู่ระบบ
                                            </p>

                                            <img alt="Reset passcode init" class="-ic img-fluid mb-3" width="130" height="136" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-reset-passcode.png" />

                                            <div class="x-reset-pw-text-container">
                                                <form
                                                    method="post"
                                                    data-register-v3-form="/_ajax_/v3/reset-password/request-otp"
                                                    data-register-step="resetPasswordPhoneNumber"
                                                    data-tab-next-step="#tab-content-logged-resetPasswordVerifyOtp"
                                                >
                                                    <input type="hidden" required="" id="phone_number[phoneNumber]" name="phone_number[phoneNumber]" pattern=".{10,11}" value="0963056322" placeholder="095-123-4567" />
                                                    <button type="submit" class="btn -submit -btn-confirm btn-primary my-lg-3 mt-0 js-btn-confirm">
                                                        <i class="-ic fa fa-spinner fa-spin"></i>
                                                        <span class="-message">เปลี่ยนรหัสผ่าน</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane" id="tab-content-logged-resetPasswordVerifyOtp" data-completed-dismiss-modal="">
                                        <div class="x-modal-body-base -v3 x-form-register-v3">
                                            <div class="row -register-container-wrapper">
                                                <div class="col">
                                                    <div class="x-title-register-modal-v3">
                                                        <span class="-title">กรอกรหัส OTP</span>
                                                        <span class="-sub-title">รหัส 4 หลัก ส่งไปยัง <span class="js-phone-number -highlight"></span></span>
                                                    </div>
                                                </div>

                                                <div data-animatable="fadeInRegister" data-offset="0" class="col animated fadeInRegister">
                                                    <div class="x-modal-separator-container x-form-register">
                                                        <div class="-top">
                                                            <div data-animatable="fadeInRegister" data-offset="0" class="-animatable-container -otp-body animated fadeInRegister">
                                                                <form
                                                                    method="post"
                                                                    data-register-v3-form="/_ajax_/v3/reset-password/verify-otp/_resetPasswordToken"
                                                                    data-register-step="resetPasswordVerifyOtp"
                                                                    data-tab-next-step="#tab-content-logged-resetPasswordSetPassword"
                                                                    name="js-reset-password-v3-otp-form"
                                                                >
                                                                    <div class="d-flex -otp-input-container js-register-v3-input-group">
                                                                        <div class="js-separator-container js-login-reset-password-otp-container">
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

                                                                        <div class="js-separator-container js-login-reset-password-otp-container">
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

                                                                        <div class="js-separator-container js-login-reset-password-otp-container">
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

                                                                        <div class="js-separator-container js-login-reset-password-otp-container">
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
                                                                    <input type="hidden" id="resetPasswordOtp" name="otp" pattern="[0-9]*" class="form-control mb-3" />
                                                                    <input type="hidden" id="resetPasswordToken" name="resetPasswordToken" class="form-control mb-3" />

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
                                                                        <button type="submit" class="btn -submit btn-primary my-lg-3 mt-0">
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

                                    <div class="tab-pane" id="tab-content-logged-resetPasswordSetPassword" data-completed-dismiss-modal="">
                                        <div class="x-modal-body-base -v3 -password x-form-register-v3">
                                            <div class="row -register-container-wrapper">
                                                <div class="col">
                                                    <div class="x-title-register-modal-v3">
                                                        <span class="-title">ตั้งรหัสผ่านใหม่</span>
                                                        <span class="-sub-title">กรอกตัวเลขรหัส 6 หลัก เพื่อใช้เข้าสู่ระบบ</span>
                                                    </div>
                                                </div>

                                                <div data-animatable="fadeInRegister" data-offset="0" class="col animated fadeInRegister">
                                                    <div class="x-modal-separator-container x-form-register">
                                                        <div class="-top">
                                                            <div data-animatable="fadeInRegister" data-offset="0" class="-animatable-container -password-body animated fadeInRegister">
                                                                <form
                                                                    method="post"
                                                                    data-register-v3-form="/_ajax_/v3/reset-password/set-password/_resetPasswordSetPassword"
                                                                    data-register-step="resetPasswordSetPassword"
                                                                    data-tab-next-step="#tab-content-logged-resetPasswordResult"
                                                                >
                                                                    <div class="d-flex -password-input-container js-register-v3-input-group">
                                                                        <div class="js-separator-container js-reset-password-container">
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

                                                                        <div class="js-separator-container js-reset-password-container">
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

                                                                        <div class="js-separator-container js-reset-password-container">
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

                                                                        <div class="js-separator-container js-reset-password-container">
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

                                                                        <div class="js-separator-container js-reset-password-container">
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

                                                                        <div class="js-separator-container js-reset-password-container">
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

                                                                    <input type="hidden" id="resetPasswordSetPasswordToken" name="resetPasswordSetPasswordToken" />

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
                                                                        <button type="submit" class="btn -submit btn-primary my-lg-3 mt-0">
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

                                    <div class="tab-pane" id="tab-content-logged-resetPasswordResult" data-completed-dismiss-modal="#alertResetPasscodeModal">
                                        <div class="x-modal-body-base -v3 x-form-register-v3">
                                            <div class="row -register-container-wrapper">
                                                <div data-animatable="fadeInRegister" data-offset="0" class="col animated fadeInRegister">
                                                    <div class="text-center d-flex flex-column justify-content-center h-100">
                                                        <div class="text-center">
                                                            <img alt="สมัครสมาชิก SUCCESS" class="js-ic-success -success-ic img-fluid" width="150" height="150" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/animated-register-success.png" />
                                                        </div>

                                                        <div class="-title">อัปเดตรหัสผ่านของคุณเรียบร้อย!</div>
                                                        <div class="-sub-title">ระบบกำลังพาคุณไปหน้าหลัก</div>
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

        <div class="x-modal modal -v2 x-modal-promotion-alert -with-half-size" id="promotionAlertModal" tabindex="-1" role="dialog" aria-hidden="true" data-loading-container=".js-modal-content" data-ajax-modal-always-reload="true">
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable modal-dialog-centered" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-header -modal-header"></div>
                    <div class="modal-body -modal-body"></div>
                </div>
            </div>
        </div>

        <div
            class="x-modal modal -v2 -with-half-size"
            id="changePasswordModal"
            data-container="#changePassordModal"
            data-dismiss-modal-target="#accountModalMobile, #accountModal"
        >
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -dialog-in-tab -change-password-index-dialog" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-header -modal-header">
                        <h3 class="x-title-modal d-inline-block m-auto">
                            <span></span>
                        </h3>
                    </div>
                    <div class="modal-body -modal-body">
                        <div class="x-register-tab-container -register js-tab-pane-checker-v3">
                            <div class="container">
                                <ul class="nav nav-tabs x-register-tab js-change-password-tab">
                                    <li class="nav-item active -currentPassword" id="tab-currentPassword">
                                        <a data-toggle="tab" href="https://wm356.co/#tab-content-currentPassword" class="nav-link">
                                            currentPassword
                                        </a>
                                    </li>
                                    <li class="nav-item -newPassword" id="tab-newPassword">
                                        <a data-toggle="tab" href="https://wm356.co/#tab-content-newPassword" class="nav-link">
                                            newPassword
                                        </a>
                                    </li>
                                    <li class="nav-item -resultChangePasswordSuccess" id="tab-resultChangePasswordSuccess">
                                        <a data-toggle="tab" href="https://wm356.co/#tab-content-resultChangePasswordSuccess" class="nav-link">
                                            resultChangePasswordSuccess
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <form method="post" name="js-change-password-current" data-register-v3-form="/_ajax_/v3/change-password" data-register-step="changePassword" data-is-passcode="1">
                                <div class="tab-content">
                                    <div class="tab-pane active" id="tab-content-currentPassword" data-completed-dismiss-modal="">
                                        <div class="x-modal-body-base -v3 x-form-register-v3">
                                            <div class="row -register-container-wrapper">
                                                <div class="col">
                                                    <div class="x-title-register-modal-v3">
                                                        <span class="-title">รหัสผ่านเดิม 6 หลัก</span>
                                                        <span class="-sub-title">กรุณากรอกเลขรหัสผ่านเดิม 6 หลัก</span>
                                                    </div>
                                                </div>

                                                <div data-animatable="fadeInRegister" data-offset="0" class="col animated fadeInRegister">
                                                    <div class="x-modal-separator-container x-form-change-password">
                                                        <div class="-top">
                                                            <div data-animatable="fadeInModal" data-offset="0" class="-animatable-container -password-body animated fadeInModal">
                                                                <div class="d-flex -password-input-container js-register-v3-input-group">
                                                                    <div class="js-separator-container js-change-password-container">
                                                                        <input
                                                                            type="text"
                                                                            id="currentPassword_1"
                                                                            name="currentPassword_1"
                                                                            readonly=""
                                                                            inputmode="text"
                                                                            pattern="[0-9]*"
                                                                            class="-digit-password js-otp-input"
                                                                            data-separator-input="true"
                                                                            data-type="current_set_password"
                                                                        />
                                                                    </div>

                                                                    <div class="js-separator-container js-change-password-container">
                                                                        <input
                                                                            type="text"
                                                                            id="currentPassword_2"
                                                                            name="currentPassword_2"
                                                                            readonly=""
                                                                            inputmode="text"
                                                                            pattern="[0-9]*"
                                                                            class="-digit-password js-otp-input"
                                                                            data-separator-input="true"
                                                                            data-type="current_set_password"
                                                                        />
                                                                    </div>

                                                                    <div class="js-separator-container js-change-password-container">
                                                                        <input
                                                                            type="text"
                                                                            id="currentPassword_3"
                                                                            name="currentPassword_3"
                                                                            readonly=""
                                                                            inputmode="text"
                                                                            pattern="[0-9]*"
                                                                            class="-digit-password js-otp-input"
                                                                            data-separator-input="true"
                                                                            data-type="current_set_password"
                                                                        />
                                                                    </div>

                                                                    <div class="js-separator-container js-change-password-container">
                                                                        <input
                                                                            type="text"
                                                                            id="currentPassword_4"
                                                                            name="currentPassword_4"
                                                                            readonly=""
                                                                            inputmode="text"
                                                                            pattern="[0-9]*"
                                                                            class="-digit-password js-otp-input"
                                                                            data-separator-input="true"
                                                                            data-type="current_set_password"
                                                                        />
                                                                    </div>

                                                                    <div class="js-separator-container js-change-password-container">
                                                                        <input
                                                                            type="text"
                                                                            id="currentPassword_5"
                                                                            name="currentPassword_5"
                                                                            readonly=""
                                                                            inputmode="text"
                                                                            pattern="[0-9]*"
                                                                            class="-digit-password js-otp-input"
                                                                            data-separator-input="true"
                                                                            data-type="current_set_password"
                                                                        />
                                                                    </div>

                                                                    <div class="js-separator-container js-change-password-container">
                                                                        <input
                                                                            type="text"
                                                                            id="currentPassword_6"
                                                                            name="currentPassword_6"
                                                                            readonly=""
                                                                            inputmode="text"
                                                                            pattern="[0-9]*"
                                                                            class="-digit-password js-otp-input"
                                                                            data-separator-input="true"
                                                                            data-type="current_set_password"
                                                                        />
                                                                    </div>
                                                                </div>

                                                                <input type="hidden" id="currentPassword" name="currentPassword" pattern="[0-9]*" class="form-control mb-3" />

                                                                <div class="d-none js-keypad-number-wrapper">
                                                                    <div class="x-keypad-number-container">
                                                                        <div class="-btn-group-wrapper">
                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="1"
                                                                                data-target="#currentPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'
                                                                            >
                                                                                1
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="2"
                                                                                data-target="#currentPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'
                                                                            >
                                                                                2
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="3"
                                                                                data-target="#currentPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'
                                                                            >
                                                                                3
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="4"
                                                                                data-target="#currentPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'
                                                                            >
                                                                                4
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="5"
                                                                                data-target="#currentPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'
                                                                            >
                                                                                5
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="6"
                                                                                data-target="#currentPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'
                                                                            >
                                                                                6
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="7"
                                                                                data-target="#currentPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'
                                                                            >
                                                                                7
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="8"
                                                                                data-target="#currentPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'
                                                                            >
                                                                                8
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="9"
                                                                                data-target="#currentPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'
                                                                            >
                                                                                9
                                                                            </button>

                                                                            <div
                                                                                class="btn -btn js-keypad-btn -btn-none"
                                                                                type="button"
                                                                                data-key="none"
                                                                                data-target="#currentPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'
                                                                            ></div>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="0"
                                                                                data-target="#currentPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'
                                                                            >
                                                                                0
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-backspace"
                                                                                type="button"
                                                                                data-key="backspace"
                                                                                data-target="#currentPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'
                                                                            >
                                                                                <i class="fas fa-backspace"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="text-center">
                                                                    <button
                                                                        type="button"
                                                                        class="btn -submit btn-primary my-lg-3 mt-0 js-current-password-button"
                                                                        onclick="$(&#39;a[href=\&#39;#tab-content-newPassword\&#39;]&#39;).click();"
                                                                    >
                                                                        ยืนยัน
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="-bottom"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane" id="tab-content-newPassword" data-completed-dismiss-modal="">
                                        <div class="x-modal-body-base -v3 x-form-register-v3">
                                            <div class="row -register-container-wrapper">
                                                <div class="col">
                                                    <div class="x-title-register-modal-v3">
                                                        <span class="-title">ตั้งรหัส 6 หลักใหม่</span>
                                                        <span class="-sub-title">กรอกเลขรหัส 6 หลัก เพื่อใช้เข้าสู่ระบบ</span>
                                                    </div>
                                                </div>

                                                <div data-animatable="fadeInRegister" data-offset="0" class="col animated fadeInRegister">
                                                    <div class="x-modal-separator-container x-form-change-password">
                                                        <div class="-top">
                                                            <div data-animatable="fadeInModal" data-offset="0" class="-animatable-container -password-body animated fadeInModal">
                                                                <div class="d-flex -password-input-container js-register-v3-input-group">
                                                                    <div class="js-separator-container js-new-password-container">
                                                                        <input
                                                                            type="text"
                                                                            id="newPassword_1"
                                                                            name="newPassword_1"
                                                                            inputmode="text"
                                                                            pattern="[0-9]*"
                                                                            class="-digit-password js-otp-input"
                                                                            data-separator-input="true"
                                                                            data-type="set_new_password"
                                                                            required=""
                                                                        />
                                                                    </div>

                                                                    <div class="js-separator-container js-new-password-container">
                                                                        <input
                                                                            type="text"
                                                                            id="newPassword_2"
                                                                            name="newPassword_2"
                                                                            inputmode="text"
                                                                            pattern="[0-9]*"
                                                                            class="-digit-password js-otp-input"
                                                                            data-separator-input="true"
                                                                            data-type="set_new_password"
                                                                            required=""
                                                                        />
                                                                    </div>

                                                                    <div class="js-separator-container js-new-password-container">
                                                                        <input
                                                                            type="text"
                                                                            id="newPassword_3"
                                                                            name="newPassword_3"
                                                                            inputmode="text"
                                                                            pattern="[0-9]*"
                                                                            class="-digit-password js-otp-input"
                                                                            data-separator-input="true"
                                                                            data-type="set_new_password"
                                                                            required=""
                                                                        />
                                                                    </div>

                                                                    <div class="js-separator-container js-new-password-container">
                                                                        <input
                                                                            type="text"
                                                                            id="newPassword_4"
                                                                            name="newPassword_4"
                                                                            inputmode="text"
                                                                            pattern="[0-9]*"
                                                                            class="-digit-password js-otp-input"
                                                                            data-separator-input="true"
                                                                            data-type="set_new_password"
                                                                            required=""
                                                                        />
                                                                    </div>

                                                                    <div class="js-separator-container js-new-password-container">
                                                                        <input
                                                                            type="text"
                                                                            id="newPassword_5"
                                                                            name="newPassword_5"
                                                                            inputmode="text"
                                                                            pattern="[0-9]*"
                                                                            class="-digit-password js-otp-input"
                                                                            data-separator-input="true"
                                                                            data-type="set_new_password"
                                                                            required=""
                                                                        />
                                                                    </div>

                                                                    <div class="js-separator-container js-new-password-container">
                                                                        <input
                                                                            type="text"
                                                                            id="newPassword_6"
                                                                            name="newPassword_6"
                                                                            inputmode="text"
                                                                            pattern="[0-9]*"
                                                                            class="-digit-password js-otp-input"
                                                                            data-separator-input="true"
                                                                            data-type="set_new_password"
                                                                            required=""
                                                                        />
                                                                    </div>
                                                                </div>

                                                                <input type="hidden" id="newPassword[first]" name="newPassword[first]" pattern="[0-9]*" class="form-control mb-3" />

                                                                <div class="d-none js-keypad-number-wrapper">
                                                                    <div class="x-keypad-number-container">
                                                                        <div class="-btn-group-wrapper">
                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="1"
                                                                                data-target="#newPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'
                                                                            >
                                                                                1
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="2"
                                                                                data-target="#newPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'
                                                                            >
                                                                                2
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="3"
                                                                                data-target="#newPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'
                                                                            >
                                                                                3
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="4"
                                                                                data-target="#newPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'
                                                                            >
                                                                                4
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="5"
                                                                                data-target="#newPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'
                                                                            >
                                                                                5
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="6"
                                                                                data-target="#newPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'
                                                                            >
                                                                                6
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="7"
                                                                                data-target="#newPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'
                                                                            >
                                                                                7
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="8"
                                                                                data-target="#newPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'
                                                                            >
                                                                                8
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="9"
                                                                                data-target="#newPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'
                                                                            >
                                                                                9
                                                                            </button>

                                                                            <div
                                                                                class="btn -btn js-keypad-btn -btn-none"
                                                                                type="button"
                                                                                data-key="none"
                                                                                data-target="#newPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'
                                                                            ></div>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-keypad"
                                                                                type="button"
                                                                                data-key="0"
                                                                                data-target="#newPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'
                                                                            >
                                                                                0
                                                                            </button>

                                                                            <button
                                                                                class="btn -btn js-keypad-btn -btn-backspace"
                                                                                type="button"
                                                                                data-key="backspace"
                                                                                data-target="#newPassword_1"
                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'
                                                                            >
                                                                                <i class="fas fa-backspace"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="text-center">
                                                                    <button type="submit" class="btn -submit btn-primary my-lg-3 mt-0 js-new-password-button">
                                                                        ยืนยัน
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="-bottom"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane" id="tab-content-resultChangePasswordSuccess" data-completed-dismiss-modal="#changePasswordModal">
                                        <div class="js-change-password-success-container">
                                            <div class="x-modal-body-base -v3 x-form-register-v3">
                                                <div class="row -register-container-wrapper">
                                                    <div data-animatable="fadeInRegister" data-offset="0" class="col animated fadeInRegister">
                                                        <div class="text-center d-flex flex-column justify-content-center h-100">
                                                            <div class="text-center">
                                                                <img alt="สมัครสมาชิก SUCCESS" class="js-ic-success -success-ic img-fluid" width="150" height="150" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/animated-register-success.png" />
                                                            </div>

                                                            <div class="-title">เปลี่ยนรหัสผ่านสำเร็จ!</div>
                                                            <div class="-sub-title">ระบบกำลังพาคุณไปหน้าหลัก</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div
            class="x-modal modal -v2 -with-more-than-half-size show"
            id="billHistoryModalMobile"
            tabindex="-1"
            role="dialog"
            data-loading-container=".modal-body"
            data-ajax-modal-always-reload="true"
            data="customer-bill-history?isMobileView=1"
            data-container="#billHistoryModalMobile"
            aria-modal="true"
        >
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-mobile -no-fixed-button" role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>

                    <div class="modal-header -modal-header">
                        <div class="x-modal-mobile-header">
                            <div class="-header-mobile-container">
                                <h3 class="x-title-modal text-center mx-auto">
                                    ประวัติการทำรายการ
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body -modal-body">
                        <div class="-outer-history-wrapper">
                            <div class="x-bill-history-container">


                                <div
                                    class="wg-container wg-container__wg_bill_history wg--loaded"
                                    data-widget-name="wg_bill_history"
                                    data-widget-options='{"script_path":null,"style_path":null,"image_path":null,"visibility":"away","visibility_offset":"100%","render_url":"\/_widget","render_method":"GET","attr_style":null,"attr_class":null,"scroll_position":"current","options":{},"callback":{},"mode":"clear","mask_mode":"over","mask_style":"wg-loading","limit":20,"page":1,"template":"@Base\/Widget\/billHistory.html.twig","name":"wg_bill_history"}'
                                    data-widget-user-options='{"page":1}'
                                >
                                    <div class="wg-content">
                                        <table class="table table-borderless table-striped">
                                            <tbody>
                                            <tr>
                                                <td class="-description-body-wrapper">
                                                    <div class="-title-wrapper">
                                                        <span class="-title">ฝากเงิน</span>
                                                    </div>
                                                    <div class="-state-wrapper">
                                                        <span class="-state-text">สถานะ : </span>

                                                        <img src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic_fail.png" class="-ic" alt="State icon image" />

                                                        <span class="-state-title">ไม่สำเร็จ (ยกเลิก)</span>
                                                        <span class="-state-title -short">ไม่สำเร็จ</span>
                                                    </div>
                                                </td>
                                                <td class="-transaction-body-wrapper">
                                                    <div class="-amount -deposit">1,000</div>
                                                    <div class="-datetime">27/09/66 - 19:00 น.</div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="-description-body-wrapper">
                                                    <div class="-title-wrapper">
                                                        <span class="-title">ฝากเงิน</span>
                                                    </div>
                                                    <div class="-state-wrapper">
                                                        <span class="-state-text">สถานะ : </span>

                                                        <img src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic_success.png" class="-ic" alt="State icon image" />

                                                        <span class="-state-title">สำเร็จ</span>
                                                        <span class="-state-title -short">สำเร็จ</span>
                                                    </div>
                                                </td>
                                                <td class="-transaction-body-wrapper">
                                                    <div class="-amount -deposit">1,000</div>
                                                    <div class="-datetime">17/09/66 - 14:08 น.</div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="-description-body-wrapper">
                                                    <div class="-title-wrapper">
                                                        <span class="-title">ฝากเงิน</span>
                                                    </div>
                                                    <div class="-state-wrapper">
                                                        <span class="-state-text">สถานะ : </span>

                                                        <img src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic_success.png" class="-ic" alt="State icon image" />

                                                        <span class="-state-title">สำเร็จ</span>
                                                        <span class="-state-title -short">สำเร็จ</span>
                                                    </div>
                                                </td>
                                                <td class="-transaction-body-wrapper">
                                                    <div class="-amount -deposit">500</div>
                                                    <div class="-datetime">17/09/66 - 12:35 น.</div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class="-description-body-wrapper">
                                                    <div class="-title-wrapper">
                                                        <span class="-title">ฝากเงิน</span>
                                                    </div>
                                                    <div class="-state-wrapper">
                                                        <span class="-state-text">สถานะ : </span>

                                                        <img src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic_success.png" class="-ic" alt="State icon image" />

                                                        <span class="-state-title">สำเร็จ</span>
                                                        <span class="-state-title -short">สำเร็จ</span>
                                                    </div>
                                                </td>
                                                <td class="-transaction-body-wrapper">
                                                    <div class="-amount -deposit">500</div>
                                                    <div class="-datetime">16/09/66 - 20:06 น.</div>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script id="b-loading" type="text/template">
            <div class="x-dice-container py-5 m-auto d-flex">
                <div id="dice" class="mx-auto">
                    <div class="side front">
                        <div class="dot center bg-danger"></div>
                    </div>
                    <div class="side front inner"></div>
                    <div class="side top">
                        <div class="dot dtop dleft"></div>
                        <div class="dot dbottom dright"></div>
                    </div>
                    <div class="side top inner"></div>
                    <div class="side right">
                        <div class="dot dtop dleft"></div>
                        <div class="dot center"></div>
                        <div class="dot dbottom dright"></div>
                    </div>
                    <div class="side right inner"></div>
                    <div class="side left">
                        <div class="dot dtop dleft"></div>
                        <div class="dot dtop dright"></div>
                        <div class="dot dbottom dleft"></div>
                        <div class="dot dbottom dright"></div>
                    </div>
                    <div class="side left inner"></div>
                    <div class="side bottom">
                        <div class="dot center"></div>
                        <div class="dot dtop dleft"></div>
                        <div class="dot dtop dright"></div>
                        <div class="dot dbottom dleft"></div>
                        <div class="dot dbottom dright"></div>
                    </div>
                    <div class="side bottom inner"></div>
                    <div class="side back">
                        <div class="dot dtop dleft"></div>
                        <div class="dot dtop dright"></div>
                        <div class="dot dbottom dleft"></div>
                        <div class="dot dbottom dright"></div>
                        <div class="dot center dleft"></div>
                        <div class="dot center dright"></div>
                    </div>
                    <div class="side back inner"></div>
                    <div class="side cover x"></div>
                    <div class="side cover y"></div>
                    <div class="side cover z"></div>
                </div>
            </div>
        </script>

        <script id="loading" type="text/template">
            <div class="x-dice-container py-5 m-auto d-flex">
                <div id="dice" class="mx-auto">
                    <div class="side front">
                        <div class="dot center bg-danger"></div>
                    </div>
                    <div class="side front inner"></div>
                    <div class="side top">
                        <div class="dot dtop dleft"></div>
                        <div class="dot dbottom dright"></div>
                    </div>
                    <div class="side top inner"></div>
                    <div class="side right">
                        <div class="dot dtop dleft"></div>
                        <div class="dot center"></div>
                        <div class="dot dbottom dright"></div>
                    </div>
                    <div class="side right inner"></div>
                    <div class="side left">
                        <div class="dot dtop dleft"></div>
                        <div class="dot dtop dright"></div>
                        <div class="dot dbottom dleft"></div>
                        <div class="dot dbottom dright"></div>
                    </div>
                    <div class="side left inner"></div>
                    <div class="side bottom">
                        <div class="dot center"></div>
                        <div class="dot dtop dleft"></div>
                        <div class="dot dtop dright"></div>
                        <div class="dot dbottom dleft"></div>
                        <div class="dot dbottom dright"></div>
                    </div>
                    <div class="side bottom inner"></div>
                    <div class="side back">
                        <div class="dot dtop dleft"></div>
                        <div class="dot dtop dright"></div>
                        <div class="dot dbottom dleft"></div>
                        <div class="dot dbottom dright"></div>
                        <div class="dot center dleft"></div>
                        <div class="dot center dright"></div>
                    </div>
                    <div class="side back inner"></div>
                    <div class="side cover x"></div>
                    <div class="side cover y"></div>
                    <div class="side cover z"></div>
                </div>
            </div>
        </script>

        <footer class="x-footer -ezl -anon">
            <div class="-inner-wrapper lazyload x-bg-position-center" data-bgset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/footer-inner-bg.png">
                <div class="container -inner-title-wrapper">
                    <h3 class="-text-title">
                        WM356 ระบบใหม่ เจ้าแรกในไทย
                    </h3>

                    <p class="-text-sub-title">
                        Wm356 สร้างประสบการณ์ใหม่ให้กับคุณได้ดีที่สุด โดยเฉพาะการเล่นกับเว็บไซต์ Wm356 ที่จะพาคุณไปพบหวยหลากหลายประเภทรวมไว้ในเว็บเดียว สมัครสมาชิกแล้วพร้อมเข้าเลือกเลขได้อย่างเป็นอิสระ ไม่ว่าคุณจะแทงหวยในรูปแบบใด
                        ทำเงินได้ตลอด 24 ชั่วโมงอย่างมั่นใจ เพราะที่นี่มีความมั่นคงสูง และเตรียมพร้อมในทุก ๆ ด้านเพื่อให้คอหวยทุกคนได้รับความคุ้มค่ามากที่สุด ทั้งยังกล้าการันตีการเป็นเว็บหวยที่ดีที่สุดแห่งยุคอีกด้วย
                    </p>
                </div>

                <div class="container">
                    <div class="-footer-menu">
                        <div class="-block-provider">
                            <h3 class="-text-title -provider">PAYMENTS ACCEPTED</h3>

                            <img alt="หวยออนไลน์ แทงหวยออนไลน์ คาสิโนออนไลน์ เว็บพนันออนไลน์" class="img-fluid -image" width="580" height="40" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/icon-payment.png" />
                        </div>

                        <div class="-block-tag">
                            <h3 class="-text-title -tag">TAG</h3>
                            <div class="row x-footer-seo -ezl">
                                <div class="col-12 mb-3 -footer-seo-title"></div>

                                <div class="col-12 -tags"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="-terms-wrapper">
                    <a href="term-and-condition.html" target="_blank" class="-link" rel="nofollow noopener">
                        Term and condition
                    </a>
                </div>
            </div>

            <div class="text-center -copy-right-container">
                <p class="mb-0 -copy-right-text">
                    Copyright © 2023 WM356. All Rights Reserved.
                </p>
            </div>
        </footer>

        <script></script>
    </div>

@endsection

@push('scripts')
    <script>
        function copy(id) {
            var copyText = document.getElementById(id);
            var input = document.createElement("textarea");
            input.value = copyText.textContent;
            this.copycontent = copyText.textContent;
            document.body.appendChild(input);
            input.select();
            input.setSelectionRange(0, 99999);
            document.execCommand("copy");
            input.remove();
        }
    </script>
@endpush

