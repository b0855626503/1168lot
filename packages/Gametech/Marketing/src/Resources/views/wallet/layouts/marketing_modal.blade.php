
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
                            <source type="image/webp"
                                    srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-alert-success.webp"/>
                            <source type="image/png"
                                    srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-alert-success.png"/>
                            <img class="-img-alert js-ic-success img-fluid" alt="ทำรายการเว็บพนันออนไลน์สำเร็จ"
                                 width="40" height="40"
                                 src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-alert-success.png"/>
                        </picture>

                        <picture>
                            <source type="image/webp"
                                    srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-alert-failed.webp"/>
                            <source type="image/png"
                                    srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-alert-failed.png"/>
                            <img class="-img-alert js-ic-fail img-fluid" alt="ทำรายการเว็บพนันออนไลน์ไม่สำเร็จ"
                                 width="40" height="40"
                                 src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-alert-failed.png"/>
                        </picture>
                    </div>
                    <div class="my-auto js-modal-content"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(request()->routeIs('customer.session.*') || request()->routeIs('customer.promotion.show') || request()->routeIs('customer.cats.show_list'))
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
                                            data-srcset="{{ url(core()->imgurl($config->logo,'img')) }}"/>
                                    <source type="image/png?v=2"
                                            data-srcset="{{ url(core()->imgurl($config->logo,'img')) }}"/>
                                    <img
                                            alt="logo image" loading="lazy" fetchpriority="low"
                                            class="img-fluid lazyload -logo lazyload"
                                            width="180"
                                            height="42"
                                            data-src="{{ url(core()->imgurl($config->logo,'img')) }}"
                                            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                                    />
                                </picture>
                            </a>


                        </div>


                        <div class="-inner-top-body-section">
                            <div class="row -wrapper-box">
                                <div class="col"><a style="color:black"
                                                    href="{{ route('customer.home.lang', ['lang' => 'th']) }}"><img
                                                src="/images/flag/th.png" class="img img-fluid" loading="lazy"
                                                fetchpriority="low"></a></div>
                                <div class="col"><a style="color:black"
                                                    href="{{ route('customer.home.lang', ['lang' => 'en']) }}"><img
                                                src="/images/flag/en.png" class="img img-fluid" loading="lazy"
                                                fetchpriority="low"></a></div>
                                <div class="col"><a style="color:black"
                                                    href="{{ route('customer.home.lang', ['lang' => 'kh']) }}"><img
                                                src="/images/flag/kh.png" class="img img-fluid" loading="lazy"
                                                fetchpriority="low"></a></div>
                                <div class="col"><a style="color:black"
                                                    href="{{ route('customer.home.lang', ['lang' => 'my']) }}"><img
                                                src="/images/flag/my.png" class="img img-fluid" loading="lazy"
                                                fetchpriority="low"></a></div>
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
                                        <source type="image/png?v=2"
                                                data-srcset="/assets/wm356/images/ic-modal-menu-register.png?v=2"/>
                                        <img
                                                alt="รูปไอคอนสมัครสมาชิก" loading="lazy" fetchpriority="low"
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
                                        <source type="image/png?v=2"
                                                data-srcset="/assets/wm356/images/ic-modal-menu-login.png?v=2"/>
                                        <img
                                                alt="รูปไอคอนเข้าสู่ระบบ" loading="lazy" fetchpriority="low"
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
                                        <source type="image/png?v=2"
                                                data-srcset="/assets/wm356/images/ic-modal-menu-promotion.png?v=2"/>
                                        <img
                                                alt="รูปไอคอนโปรโมชั่น" loading="lazy" fetchpriority="low"
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
                                        href="{{ $config->linelink }}"
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
                                                alt="รูปไอคอนดูหนัง" loading="lazy" fetchpriority="low"
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
@else
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
                                            data-srcset="{{ url(core()->imgurl($config->logo,'img')) }}"/>
                                    <source type="image/png?v=2"
                                            data-srcset="{{ url(core()->imgurl($config->logo,'img')) }}"/>
                                    <img
                                            alt="logo image" loading="lazy" fetchpriority="low"
                                            class="img-fluid lazyload -logo lazyload"
                                            width="180"
                                            height="42"
                                            data-src="{{ url(core()->imgurl($config->logo,'img')) }}"
                                            src="{{ url(core()->imgurl($config->logo,'img')) }}"
                                    />
                                </picture>
                            </a>
                        </div>

                        <div class="-inner-top-body-section">
                            <div class="row -wrapper-box">
                                <div class="col"><a style="color:black"
                                                    href="{{ route('customer.home.lang', ['lang' => 'th']) }}"><img
                                                src="/images/flag/th.png" class="img img-fluid" loading="lazy"
                                                fetchpriority="low"></a></div>
                                <div class="col"><a style="color:black"
                                                    href="{{ route('customer.home.lang', ['lang' => 'en']) }}"><img
                                                src="/images/flag/en.png" class="img img-fluid" loading="lazy"
                                                fetchpriority="low"></a></div>
                                <div class="col"><a style="color:black"
                                                    href="{{ route('customer.home.lang', ['lang' => 'kh']) }}"><img
                                                src="/images/flag/kh.png" class="img img-fluid" loading="lazy"
                                                fetchpriority="low"></a></div>
                                <div class="col"><a style="color:black"
                                                    href="{{ route('customer.home.lang', ['lang' => 'my']) }}"><img
                                                src="/images/flag/my.png" class="img img-fluid" loading="lazy"
                                                fetchpriority="low"></a></div>
                            </div>
                            <div class="col-6 -wrapper-box">
                                <button
                                        type="button"
                                        class="btn -btn-item x-transaction-button-v2 -deposit -top-btn -horizontal x-bg-position-center lazyloaded"
                                        data-toggle="modal"
                                        data-dismiss="modal"
                                        data-target="#depositModal"
                                        data-bgset="/assets/wm356/images/btn-deposit-bg.png?v=2"
                                        style="background-image: url('/assets/wm356/images/btn-deposit-bg.png?v=2');"
                                >
                                    <picture>
                                        <source type="image/webp"
                                                data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.webp?v=2"
                                                srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.webp?v=2"/>
                                        <source type="image/png"
                                                data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.png"
                                                srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.png"/>
                                        <img loading="lazy" fetchpriority="low"
                                             alt="รูปไอคอนฝากเงิน"
                                             class="img-fluid -icon-image ls-is-cached lazyloaded"
                                             width="50"
                                             height="50"
                                             data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.png"
                                             src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.png"
                                        />
                                    </picture>

                                    <div class="-typo-wrapper">
                                        <div class="-title">{{ __('app.home.refill') }}</div>
                                        <div class="-sub-title">{{ __('app.home.refill') }}</div>
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
                                        data-bgset="/assets/wm356/images/btn-withdraw-bg.png?v=2"
                                        style="background-image: url('/assets/wm356/images/btn-withdraw-bg.png?v=2');"
                                >
                                    <picture>
                                        <source type="image/webp"
                                                data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.webp?v=2"
                                                srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.webp?v=2"/>
                                        <source type="image/png"
                                                data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.png"
                                                srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.png"/>
                                        <img loading="lazy" fetchpriority="low"
                                             alt="รูปไอคอนถอนเงิน"
                                             class="img-fluid -icon-image ls-is-cached lazyloaded"
                                             width="50"
                                             height="50"
                                             data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.png"
                                             src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.png"
                                        />
                                    </picture>

                                    <div class="-typo-wrapper">
                                        <div class="-title">{{ __('app.home.withdraw') }}</div>
                                        <div class="-sub-title">{{ __('app.home.withdraw') }}</div>
                                    </div>
                                </button>
                            </div>
                        </div>


                        <div class="-inner-center-body-section">
                            <div class="col-6 -wrapper-box">
                                <a
                                        href="{{ route('customer.promotion.index') }}"
                                        class="btn -btn-item -promotion-button -menu-center -horizontal lazyload x-bg-position-center"
                                        data-bgset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/btn-menu-middle-bg.png"
                                >
                                    <picture>
                                        <source type="image/webp"
                                                data-srcset="/assets/wm356/images/ic-modal-menu-promotion.webp?v=2"/>
                                        <source type="image/png?v=2"
                                                data-srcset="/assets/wm356/images/ic-modal-menu-promotion.png?v=2"/>
                                        <img loading="lazy" fetchpriority="low"
                                             alt="รูปไอคอนโปรโมชั่น"
                                             class="img-fluid -icon-image lazyload"
                                             width="65"
                                             height="53"
                                             data-src="/assets/wm356/images/ic-modal-menu-promotion.png?v=2"
                                             src="/assets/wm356/images/ic-modal-menu-promotion.png?v=2"
                                        />
                                    </picture>

                                    <div class="-typo-wrapper">
                                        <div class="-typo">{{ __('app.home.promotion') }}</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6 -wrapper-box">
                                <a
                                        href="{{ $config->linelink }}"
                                        class="btn -btn-item -line-button -menu-center -horizontal lazyload x-bg-position-center"
                                        target="_blank"
                                        rel="noopener nofollow"
                                        data-bgset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/btn-menu-middle-bg.png"
                                >
                                    <picture>
                                        <source type="image/webp"
                                                data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.webp?v=2"/>
                                        <source type="image/png"
                                                data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.png"/>
                                        <img loading="lazy" fetchpriority="low"
                                             alt="รูปไอคอนดูหนัง"
                                             class="img-fluid -icon-image lazyload"
                                             width="65"
                                             height="53"
                                             data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.png"
                                             src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.png"
                                        />
                                    </picture>

                                    <div class="-typo-wrapper">
                                        <div class="-typo">{{ __('app.home.line') }}</div>
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
@endif


<div class="x-modal modal -v2 -modal-full-page" id="websiteMenuModal" tabindex="-1" role="dialog" aria-hidden="true"
     data-loading-container=".js-modal-content" data-ajax-modal-always-reload="true">
    <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content -modal-content">
            <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>

            <div class="modal-body -modal-body">
                <div class="x-website-menu-modal-body">
                    <a href="{{ route('customer.home.index') }}" class="nav-link -btn-logo">
                        <img
                                alt="บาคาร่าออนไลน์ สล็อตออนไลน์ อันดับหนึ่งในประเทศไทย"
                                class="img-fluid lazyload -img" loading="lazy" fetchpriority="low"
                                width="400"
                                height="150"
                                data-src="{{ url(core()->imgurl($config->logo,'img')) }}"
                                src="{{ url(core()->imgurl($config->logo,'img')) }}"
                        />
                    </a>


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
    <div
            class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-big -modal-main-account"
            role="document">
        <div class="modal-content -modal-content">
            <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-body -modal-body">
                <div class="x-modal-account-menu">
                    <ul class="navbar-nav">
                        <li class="nav-item -account-profile">
                            <button
                                    type="button"
                                    class="nav-link js-close-account-sidebar active"
                                    data="customer-info"
                                    data-container="#accountModal"
                                    data-active-menu="-account-profile"
                                    onclick="opentabaccount(event, 'accountprofile')"
                            >
                                <img alt="ข้อมูลบัญชี หวยออนไลน์ แทงหวยออนไลน์" class="img-fluid -icon-image"
                                     width="35" height="35" loading="lazy" fetchpriority="low"
                                     src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-user.png"/>
                                <span class="-text-menu">{{ __('app.home.profile') }}</span>
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
                                <img alt="ประวัติ หวยออนไลน์ แทงหวยออนไลน์" class="img-fluid -icon-image" width="35"
                                     height="35" loading="lazy" fetchpriority="low"
                                     src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-bill-history.png"/>
                                <span class="-text-menu">{{ __('app.home.history') }}</span>
                            </button>
                        </li>
                        <li class="nav-item -promotion-return-by-user">
                            <a href="{{ route('customer.contributor.index') }}" class="nav-link">

                                <img alt="โบนัสเพิ่ม ทุกสัปดาห์ หวยออนไลน์ แทงหวยออนไลน์" loading="lazy"
                                     fetchpriority="low"
                                     class="img-fluid -icon-image" width="35" height="35"
                                     src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-provider.png"/>
                                <span class="-text-menu">{{ __('app.home.suggest') }}</span>
                            </a>
                        </li>
                        @if($config->wheel_open === 'Y')
                            <li class="nav-item -promotion-return-by-user">
                                <a href="{{ route('customer.spin.index') }}" class="nav-link">

                                    <img alt="โบนัสเพิ่ม ทุกสัปดาห์ หวยออนไลน์ แทงหวยออนไลน์" loading="lazy"
                                         fetchpriority="low"
                                         class="img-fluid -icon-image" width="35" height="35"
                                         src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-bonus.png"/>
                                    <span class="-text-menu">{{ __('app.home.wheel') }}</span>
                                </a>
                            </li>
                        @endif


                        @if(request()->routeIs('customer.credit.*'))
                            <li class="nav-item -coupon">
                                <button
                                        type="button"
                                        class="nav-link js-close-account-sidebar js-account-approve-aware"
                                        data="coupon-apply"
                                        data-container="#accountModal"
                                        data-active-menu="-coupon"
                                        onclick="opentabaccount(event, 'getbonus')"

                                >
                                    <img alt="ใช้คูปอง หวยออนไลน์ แทงหวยออนไลน์" class="img-fluid -icon-image"
                                         width="35" height="35" loading="lazy" fetchpriority="low"
                                         src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-coupon.png"/>
                                    <span class="-text-menu">{{ __('app.home.get_bonus') }}</span>
                                </button>
                            </li>
                        @else
                            @if($config->freecredit_open === 'N')
                                <li class="nav-item -coupon">
                                    <button
                                            type="button"
                                            class="nav-link js-close-account-sidebar js-account-approve-aware"
                                            data="coupon-apply"
                                            data-container="#accountModal"
                                            data-active-menu="-coupon"
                                            onclick="opentabaccount(event, 'getbonus')"

                                    >
                                        <img alt="ใช้คูปอง หวยออนไลน์ แทงหวยออนไลน์" class="img-fluid -icon-image"
                                             width="35" height="35" loading="lazy" fetchpriority="low"
                                             src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-coupon.png"/>
                                        <span class="-text-menu">{{ __('app.home.get_bonus') }}</span>
                                    </button>
                                </li>
                            @endif
                        @endif
                        <li class="nav-item -coupon">
                            <button
                                    type="button"
                                    class="nav-link js-close-account-sidebar js-account-approve-aware"
                                    data="coupon-apply"
                                    data-container="#accountModal"
                                    data-active-menu="-coupon"
                                    onclick="opentabaccount(event, 'accountcoupon')"

                            >
                                <img alt="ใช้คูปอง หวยออนไลน์ แทงหวยออนไลน์" class="img-fluid -icon-image"
                                     width="35" height="35" loading="lazy" fetchpriority="low"
                                     src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-coupon.png"/>
                                <span class="-text-menu">{{ __('app.home.coupon') }}</span>
                            </button>
                        </li>

                        @if(request()->routeIs('customer.credit.*'))
                            <li class="nav-item -promotion-return-by-user">
                                <a href="{{ route('customer.home.index') }}" class="nav-link">

                                    <img alt="โบนัสเพิ่ม ทุกสัปดาห์ หวยออนไลน์ แทงหวยออนไลน์"
                                         class="img-fluid -icon-image" width="35" height="35" loading="lazy"
                                         fetchpriority="low"
                                         src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-bonus.png"/>
                                    <span class="-text-menu">{{ __('app.home.credit') }}</span>
                                </a>
                            </li>
                        @else
                            @if($config->freecredit_open === 'Y')
                                <li class="nav-item -promotion-return-by-user">
                                    <a href="{{ route('customer.credit.index') }}" class="nav-link">

                                        <img alt="โบนัสเพิ่ม ทุกสัปดาห์ หวยออนไลน์ แทงหวยออนไลน์"
                                             class="img-fluid -icon-image" width="35" height="35" loading="lazy"
                                             fetchpriority="low"
                                             src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-bonus.png"/>
                                        <span class="-text-menu">{{ __('app.home.freecredit') }}</span>
                                    </a>
                                </li>
                            @endif
                        @endif


                        <li class="nav-item -logout">
                            <a href="{{ route('customer.session.destroy') }}" class="nav-link js-require-confirm"
                               data-title="{{ __('app.home.want_logout') }}">
                                <img alt="ออกจากระบบ หวยออนไลน์ แทงหวยออนไลน์" class="img-fluid -icon-image"
                                     width="35" height="35" loading="lazy" fetchpriority="low"
                                     src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-logout.png"/>
                                <span class="-text-menu">{{ __('app.home.logout') }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="js-profile-account-modal -layout-account">

                    @auth
                        <div class="x-account-profile -v2 tabcontent" id="accountprofile" style="display: block;">
                            <div data-animatable="fadeInModal" class="-profile-container animated fadeInModal">
                                <h3 class="x-title-modal text-center mx-auto">
                                    {{ __('app.home.profile') }}
                                </h3>

                                <div class="text-center">
                                    <div class="my-3">
                                        <div class="x-profile-image">
                                            <img class="img-fluid -profile-image" loading="lazy" fetchpriority="low"
                                                 src="/images/icon/iconprofile.png"
                                                 alt="customer image"/>
                                        </div>
                                    </div>

                                    <div class="my-3">
                                        <div class="-text-username">Username: {{ $userdata->user_name }}</div>
                                        <p>{{ __('app.profile.tel') }} : {{ $userdata->tel }}</p>
                                        <p>Points : {{ $userdata->point_deposit }} / Diamonds
                                            : {{ $userdata->diamond }}</p>
                                        <a href="javascript:void(0)" class="-link-change-password" data-toggle="modal"
                                           data-target="#changePasswordModal">
                                            <u>{{ __('app.home.changepass') }}</u>
                                        </a>
                                    </div>
                                </div>

                                <div class="-bank-info-container">
                                    <div class="x-customer-bank-info-container -v2">
                                        <div class="media m-auto">
                                            <img loading="lazy" fetchpriority="low"
                                                 src="{{ Storage::url('bank_img/' . $userdata->bank->filepic) }}"
                                                 class="-img rounded-circle" width="50" height="50" alt="bank-ktb"/>
                                            <div class="-content-wrapper">
                                                <span class="-name">{{ $userdata->name }}</span>
                                                <span class="-number">{{ $userdata->acc_no }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="x-admin-contact text-center">
                            <span class="x-text-with-link-component">
                                <label class="-text-message">{{ __('app.home.problem') }}</label>
                                <a href="{{ $config->linelink }}" class="-link-message" target="_blank" rel="noopener">
                                    <u>{{ __('app.home.customer_service') }}</u>
                                </a>
                            </span>
                                </div>

                                <div class="js-has-info"></div>
                            </div>
                        </div>
                    @endauth

                    <div class="-outer-history-wrapper tabcontent" id="accounthistory">
                        <div class="x-bill-history-container">
                            <h3 class="x-title-modal text-center mb-3">
                                {{ __('app.home.history') }}
                            </h3>

                            <div class="x-admin-contact text-center">
                                @if(request()->routeIs('customer.credit.*'))
                                    <div class="btn-group" role="group" aria-label="Basic example">

                                        <button type="button" class="btn btn-secondary"
                                                onclick="LoadHistory2('withdraw')">
                                            {{ __('app.home.withdraw') }}
                                        </button>

                                        @if($config->freecredit_open == 'Y')
                                            @if($config->wheel_open == 'Y')
                                                <button type="button" class="btn btn-secondary"
                                                        onclick="LoadHistory2('spin')">
                                                    {{ __('app.home.wheel') }}
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-secondary"
                                                    onclick="LoadHistory2('cashback')">
                                                {{ __('app.home.cashback') }}
                                            </button>
                                            <button type="button" class="btn btn-secondary"
                                                    onclick="LoadHistory2('memberic')">
                                                {{ __('app.home.ic') }}
                                            </button>
                                        @endif

                                    </div>
                                @else
                                    <div class="btn-group" role="group" aria-label="Basic example">
                                        <button type="button" class="btn btn-secondary"
                                                onclick="LoadHistory('deposit')">
                                            {{ __('app.home.refill') }}
                                        </button>
                                        <button type="button" class="btn btn-secondary"
                                                onclick="LoadHistory('withdraw')">
                                            {{ __('app.home.withdraw') }}
                                        </button>
                                        {{--                                        <button type="button" class="btn btn-secondary"--}}
                                        {{--                                                onclick="LoadHistory('transfer')">--}}
                                        {{--                                            โยก--}}
                                        {{--                                        </button>--}}
                                        @if($config->freecredit_open == 'N')
                                            @if($config->wheel_open == 'Y')
                                                <button type="button" class="btn btn-secondary"
                                                        onclick="LoadHistory('spin')">
                                                    {{ __('app.home.wheel') }}
                                                </button>
                                            @endif
                                            <button type="button" class="btn btn-secondary"
                                                    onclick="LoadHistory('cashback')">
                                                {{ __('app.home.cashback') }}
                                            </button>
                                            <button type="button" class="btn btn-secondary"
                                                    onclick="LoadHistory('memberic')">
                                                {{ __('app.home.ic') }}
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>


                            <div
                                    class="wg-container wg-container__wg_bill_history wg--loaded"
                                    data-widget-name="wg_bill_history"
                                    data-widget-options='{"script_path":null,"style_path":null,"image_path":null,"visibility":"away","visibility_offset":"100%","render_url":"\/_widget","render_method":"GET","attr_style":null,"attr_class":null,"scroll_position":"current","options":{},"callback":{},"mode":"clear","mask_mode":"over","mask_style":"wg-loading","limit":20,"page":1,"template":"@Base\/Widget\/billHistory.html.twig","name":"wg_bill_history"}'
                                    data-widget-user-options='{"page":1}'
                            >
                                <div class="wg-content">
                                    <table class="table table-borderless table-striped table-fixed">
                                        <tbody class="historydata">


                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    @auth
                        <div class="x-account-coupon tabcontent" id="getbonus">

                            <div data-animatable="fadeInModal" class="-coupon-container animated fadeInModal">
                                <h3 class="x-title-modal text-center mx-auto">
                                    {{ __('app.home.get_bonus') }}
                                </h3>

                                <div class="x-deposit-promotion-outer-container js-scroll-ltr -fade -on-left -on-right">
                                    <div
                                            class="x-deposit-promotion -v2 -slide pt-0 -has-promotion"
                                            data-scroll-booster-container=".x-deposit-promotion-outer-container"
                                            data-scroll-booster-content=".x-deposit-promotion"
                                            style="transform: translate(0px, 0px);"
                                    >

                                        <div class="-promotion-box-wrapper">
                                            <button type="button"
                                                    onclick="openPopup('BONUS', '{{ __('app.bonus.wheel') }}')"
                                                    class="btn -promotion-box-apply-btn js-promotion-apply"
                                                    data-url="/promotion/2/apply" data-type="deposit"
                                                    data-display-slide-mode="true">
                                                <picture>
                                                    <source type="image/webp"
                                                            srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.webp?v=2"/>
                                                    <source type="image/png"
                                                            srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                                    <img class="-img" alt="BONUS" width="26" height="26" loading="lazy"
                                                         fetchpriority="low"
                                                         src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                                </picture>

                                                <span class="-title">{{ __('app.home.wheel') }}</span>
                                                <span class="-sub-title">{{ $userdata->bonus }}</span>
                                            </button>
                                            <a href="javascript:void(0)"
                                               class="-promotion-box-cancel-btn js-cancel-promotion"
                                               data-display-slide-mode="true">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                        <div class="-promotion-box-wrapper">
                                            <button type="button"
                                                    onclick="openPopup('FASTSTART','{{ __('app.bonus.faststart') }}')"
                                                    class="btn -promotion-box-apply-btn js-promotion-apply"
                                                    data-url="/promotion/2/apply" data-type="deposit"
                                                    data-display-slide-mode="true">
                                                <picture>
                                                    <source type="image/webp"
                                                            srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.webp?v=2"/>
                                                    <source type="image/png"
                                                            srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                                    <img class="-img" alt="BONUS" width="26" height="26" loading="lazy"
                                                         fetchpriority="low"
                                                         src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                                </picture>

                                                <span class="-title">{{ __('app.home.suggest') }}</span>
                                                <span class="-sub-title">{{ $userdata->faststart }}</span>
                                            </button>
                                            <a href="javascript:void(0)"
                                               class="-promotion-box-cancel-btn js-cancel-promotion"
                                               data-display-slide-mode="true">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                        <div class="-promotion-box-wrapper">
                                            <button type="button"
                                                    onclick="openPopup('CASHBACK','{{ __('app.bonus.cashback') }}')"
                                                    class="btn -promotion-box-apply-btn js-promotion-apply"
                                                    data-url="/promotion/2/apply" data-type="deposit"
                                                    data-display-slide-mode="true">
                                                <picture>
                                                    <source type="image/webp"
                                                            srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.webp?v=2"/>
                                                    <source type="image/png"
                                                            srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                                    <img class="-img" alt="BONUS" width="26" height="26" loading="lazy"
                                                         fetchpriority="low"
                                                         src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                                </picture>

                                                <span class="-title">{{ __('app.home.cashback') }}</span>
                                                <span class="-sub-title">{{ $userdata->cashback }}</span>
                                            </button>
                                            <a href="javascript:void(0)"
                                               class="-promotion-box-cancel-btn js-cancel-promotion"
                                               data-display-slide-mode="true">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>

                                    </div>


                                </div>

                                <div class="x-deposit-promotion-outer-container js-scroll-ltr -fade -on-left -on-right">
                                    <div
                                            class="x-deposit-promotion -v2 -slide pt-0 -has-promotion"
                                            data-scroll-booster-container=".x-deposit-promotion-outer-container"
                                            data-scroll-booster-content=".x-deposit-promotion"
                                            style="transform: translate(0px, 0px);"
                                    >

                                        <div class="-promotion-box-wrapper">
                                            <button type="button" onclick="openPopup('IC','{{ __('app.bonus.ic') }}')"
                                                    class="btn -promotion-box-apply-btn js-promotion-apply"
                                                    data-url="/promotion/2/apply" data-type="deposit"
                                                    data-display-slide-mode="true">
                                                <picture>
                                                    <source type="image/webp"
                                                            srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.webp?v=2"/>
                                                    <source type="image/png"
                                                            srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                                    <img class="-img" alt="BONUS" width="26" height="26" loading="lazy"
                                                         fetchpriority="low"
                                                         src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                                </picture>

                                                <span class="-title">IC</span>
                                                <span class="-sub-title">{{ $userdata->ic }}</span>
                                            </button>
                                            <a href="javascript:void(0)"
                                               class="-promotion-box-cancel-btn js-cancel-promotion"
                                               data-display-slide-mode="true">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                        <div class="-promotion-box-wrapper">
                                            <button type="button" onclick="bonusModal()"
                                                    class="btn -promotion-box-apply-btn js-promotion-apply"
                                                    data-type="deposit"
                                                    data-display-slide-mode="true">
                                                <picture>
                                                    <source type="image/webp"
                                                            srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.webp?v=2"/>
                                                    <source type="image/png"
                                                            srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                                    <img class="-img" alt="BONUS" width="26" height="26" loading="lazy"
                                                         fetchpriority="low"
                                                         src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                                </picture>

                                                <span class="-title">{{ __('app.home.coupon') }}</span>

                                            </button>
                                            <a href="javascript:void(0)"
                                               class="-promotion-box-cancel-btn js-cancel-promotion"
                                               data-display-slide-mode="true">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    </div>


                                </div>
                            </div>
                        </div>


                        <div class="x-account-coupon tabcontent" id="accountcoupon">
                            <div data-animatable="fadeInModal" class="-coupon-container animated fadeInModal">
                                <h3 class="x-title-modal text-center mx-auto">
                                    {{ __('app.home.use_coupon') }}
                                </h3>

                                <div class="-coupon-member-detail mb-3 mt-5">
                                    <div class="-coupon-box d-flex">
                                        <img alt="คูปอง เว็บไซต์พนันออนไลน์ คาสิโนออนไลน์" loading="lazy"
                                             fetchpriority="low"
                                             class="img-fluid -ic-coupon m-auto"
                                             src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-coupon.png"/>
                                    </div>
                                    <div class="-text-box-container">
                                        <div class="-title-member">{{ __('app.home.special_member') }}</div>
                                        <div class="-text-member">{{ $userdata->user_name }}</div>
                                    </div>
                                </div>

                                <div class="-form-coupon-container">
                                    <form id="frmcoupon" name="frmcoupon" method="post"
                                          action="{{ route('customer.coupon.redeem') }}" onsubmit="return false"
                                          data-ajax-form="/account/_ajax_/coupon-apply" data-callback="_onCouponApply_"
                                          data-dismiss-modal="#accountModal" data-container="#accountModal">
                                        @csrf
                                        <div class="my-4 -x-input-icon">
                                            <img alt="คูปอง เว็บไซต์พนันออนไลน์ คาสิโนออนไลน์" class="-icon"
                                                 loading="lazy" fetchpriority="low"
                                                 src="/assets/wm356/images/ic-coupon-input.png?v=2"/>

                                            <input type="text" id="coupon_coupon" name="coupon"
                                                   required="required" class="x-coupon-input text-center form-control"
                                                   placeholder="{{ __('app.home.coupon_code') }}"/>
                                        </div>

                                        <div class="-btn-submit-container">
                                            <button type="submit" class="btn -submit btn-primary">
                                                {{ __('app.login.submit') }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endauth

                    {{--                    <div class="x-account-promotion text-center tabcontent" id="accountpromotion">--}}
                    {{--                        <div class="-account-promotion-container animated fadeInModal"--}}
                    {{--                             data-animatable="fadeInModal">--}}
                    {{--                            <h3 class="x-title-modal text-center mx-auto">--}}
                    {{--                                โปรโมชั่นที่เข้าร่วม--}}
                    {{--                            </h3>--}}

                    {{--                            <div class="-no-result-container">--}}
                    {{--                                <img alt="เว็บไซต์พนันออนไลน์ คาสิโนออนไลน์" class="img-fluid -no-result-img"--}}
                    {{--                                     width="150" height="150"--}}
                    {{--                                     src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-promotion-no-result.png"/>--}}
                    {{--                            </div>--}}
                    {{--                            <div class="text-center -text-container">--}}
                    {{--                                คุณยังไม่มีโปรโมชั่นที่เข้าร่วม--}}
                    {{--                            </div>--}}
                    {{--                        </div>--}}
                    {{--                    </div>--}}


                    {{--                    <div class="x-promotion-return-by-user-container tabcontent" id="accountreturn"--}}
                    {{--                         data-animatable="fadeInUp">--}}
                    {{--                        <h3 class="x-title-modal text-center mx-auto">--}}
                    {{--                            รับคืนยอดเสีย--}}
                    {{--                        </h3>--}}

                    {{--                        <div class="-group-round-container -no-data">--}}
                    {{--                            <div class="-date-range-container text-center">--}}
                    {{--                                ยอดโบนัสระหว่างวันที่ 18 - 24 ก.ย. 2023--}}
                    {{--                            </div>--}}
                    {{--                        </div>--}}

                    {{--                        <div class="text-center">--}}
                    {{--                            <button type="button" disabled="" class="btn btn-primary -promotion-return-btn">--}}
                    {{--                                <span class="-text-btn">ไม่เข้าเงื่อนไข</span>--}}
                    {{--                            </button>--}}
                    {{--                        </div>--}}

                    {{--                        <div class="-description-container">--}}
                    {{--                            <div>--}}
                    {{--                                คุณไม่เข้าเงื่อนไขการรับโบนัส--}}
                    {{--                            </div>--}}
                    {{--                            <div><span class="-text-description">โปรดอ่านเงื่อนไขการเข้าร่วม</span>ด้านล่างค่ะ</div>--}}
                    {{--                        </div>--}}

                    {{--                        <div class="-condition-container">--}}
                    {{--                            <div class="-condition-title"><u>โปรดอ่านเงื่อนไข</u></div>--}}
                    {{--                            <div class="x-promotion-content">--}}
                    {{--                                <p>--}}
                    {{--                                    <big><strong>เล่นเสียให้คืน 5% ทุกสัปดาห์</strong></big><br/>--}}
                    {{--                                    ► รับโบนัสทุกวันจันทร์ 1 ครั้ง / สัปดาห์ (ตัดรอบ อังคาร 00:00 ถึง 23:59--}}
                    {{--                                    วันจันทร์)<br/>--}}
                    {{--                                    ► ต้องมียอดเทิร์นโอเวอร์ 5 เท่าของเงินฝากภายในสัปดาห์ (NET Tureover)<br/>--}}
                    {{--                                    ► โบนัสจะได้รับทุกวันจันทร์สามารถกดรับได้ที่หน้าเว็บ<br/>--}}
                    {{--                                    ► เพียงมียอดเล่น 50% ของโบนัสที่ได้รับสามารถถอนได้เลย<br/>--}}
                    {{--                                    ► ต้องมียอดเสียมากกว่า 2000 บาทต่อสัปดาห์จึงจะได้รับยอด 5%<br/>--}}
                    {{--                                    ► หลังจากรับโปรโมชั่นเครดิตมีอายุการใช้งาน 3--}}
                    {{--                                    วันหลังจากนั้นเครดิตคืนยอดเสียจะถูกปรับเป็น 0<br/>--}}
                    {{--                                    <a href="/term-and-condition">เงื่อนไขและกติกาพื้นฐานจะถูกนำมาใช้กับโปรโมชั่นนี้</a>--}}
                    {{--                                </p>--}}
                    {{--                            </div>--}}
                    {{--                        </div>--}}

                    {{--                        <div class="my-3">--}}
                    {{--                            <div class="x-admin-contact -no-fixed">--}}
                    {{--            <span class="x-text-with-link-component">--}}
                    {{--                <label class="-text-message">ติดปัญหา</label>--}}
                    {{--                <a href="{{ $config->linelink }}" class="-link-message" target="_blank" rel="noopener">--}}
                    {{--                    <u>ติดต่อฝ่ายบริการลูกค้า</u>--}}
                    {{--                </a>--}}
                    {{--            </span>--}}
                    {{--                            </div>--}}
                    {{--                        </div>--}}
                    {{--                    </div>--}}


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
    @auth
        <div
                class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-mobile -account-modal -no-fixed-button"
                role="document">
            <div class="modal-content -modal-content">
                <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>

                <div class="modal-header -modal-header">
                    <div class="x-modal-mobile-header">
                        <div class="-header-mobile-container">
                            <h3 class="x-title-modal text-center mx-auto">
                                {{ __('app.home.profile') }}
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
                                        <img class="img-fluid -profile-image" loading="lazy" fetchpriority="low"
                                             src="/images/icon/iconprofile.png"
                                             alt="customer image"/>
                                    </div>
                                </div>

                                <div class="my-3">
                                    <div class="-text-username">Username: {{ $userdata->user_name }}</div>
                                    <p>{{ __('app.profile.tel') }} : {{ $userdata->tel }}</p>
                                    <p>Points : {{ $userdata->point_deposit }} / Diamonds : {{ $userdata->diamond }}</p>
                                    <a href="javascript:void(0)" class="-link-change-password" data-toggle="modal"
                                       data-target="#changePasswordModal">
                                        <u>{{ __('app.home.changepass') }}</u>
                                    </a>
                                </div>
                            </div>

                            <div class="-bank-info-container">
                                <div class="x-customer-bank-info-container -v2">
                                    <div class="media m-auto">
                                        <img loading="lazy" fetchpriority="low"
                                             src="{{ Storage::url('bank_img/' . $userdata->bank->filepic) }}"
                                             class="-img rounded-circle" width="50" height="50" alt="bank-ktb"/>
                                        <div class="-content-wrapper">
                                            <span class="-name">{{ $userdata->name }}</span>
                                            <span class="-number">{{ $userdata->acc_no }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="x-admin-contact text-center">
                        <span class="x-text-with-link-component">
                            <label class="-text-message">{{ __('app.home.problem') }}</label>
                            <a href="{{ $config->linelink }}" class="-link-message" target="_blank" rel="noopener">
                                <u>{{ __('app.home.customer_service') }}</u>
                            </a>
                        </span>
                            </div>

                            <div class="js-has-info"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endauth

</div>

{{--<div--}}
{{--    class="x-modal modal -v2 -with-more-than-half-size"--}}
{{--    id="providerUserModalMobile"--}}
{{--    data="provider-user-info?isMobileView=1"--}}
{{--    data-container="#providerUserModalMobile"--}}
{{-->--}}
{{--    <div--}}
{{--        class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-mobile -no-fixed-button"--}}
{{--        role="document">--}}
{{--        <div class="modal-content -modal-content">--}}
{{--            <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">--}}
{{--                <i class="fas fa-times"></i>--}}
{{--            </button>--}}

{{--            <div class="modal-header -modal-header">--}}
{{--                <div class="x-modal-mobile-header">--}}
{{--                    <div class="-header-mobile-container">--}}
{{--                        <h3 class="x-title-modal text-center mx-auto">--}}
{{--                            เข้าเล่นผ่านแอพ--}}
{{--                        </h3>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="modal-body -modal-body">--}}
{{--                <div id="accountProviderUser" class="x-account-provider -has-provider">--}}
{{--                    <div data-animatable="fadeInModal" class="-account-provider-container animated fadeInModal">--}}
{{--                        <div class="-account-provider-inner x-account-provider-v2">--}}
{{--                            <div class="-provider-row-wrapper">--}}
{{--                                <div class="-img-wrapper">--}}
{{--                                    <img class="img-fluid -img-provider" alt="Logo Dream Gaming square" width="45"--}}
{{--                                         height="45"--}}
{{--                                         src="\assets\wm356\web\ezl-wm-356\img\ic-menu-promotion.png"/>--}}
{{--                                </div>--}}
{{--                                <div class="-account-wrapper">--}}
{{--                                    <div class="d-flex mb-2">--}}
{{--                                        <div class="-text-provider-user -first-column">--}}
{{--                                            <div>Username</div>--}}
{{--                                            <div>:</div>--}}
{{--                                        </div>--}}
{{--                                        <div class="-text-provider-user">smtwm356dg000347@B5E</div>--}}
{{--                                    </div>--}}
{{--                                    <div class="d-flex">--}}
{{--                                        <div class="-text-provider-user -first-column">--}}
{{--                                            <div>Password</div>--}}
{{--                                            <div>:</div>--}}
{{--                                        </div>--}}
{{--                                        <div class="-text-provider-user">Aa309361</div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                                <div class="-btn-action-wrapper">--}}
{{--                                    <a--}}
{{--                                        href="javascript:void(0);"--}}
{{--                                        class="-text-copy-me js-copy-to-clipboard f-9 mb-2"--}}
{{--                                        data-container="providerUserModalMobile"--}}
{{--                                        data-html="true"--}}
{{--                                        data-message="✓ คัดลอกแล้ว"--}}
{{--                                        data-copy-me="smtwm356dg000347@B5E"--}}
{{--                                        data-theme="copy-me"--}}
{{--                                        data-arrow="true"--}}
{{--                                    >--}}
{{--                                        <i class="fas fa-copy"></i>--}}
{{--                                        <div id="-copy-message"></div>--}}
{{--                                    </a>--}}
{{--                                    <a--}}
{{--                                        href="javascript:void(0);"--}}
{{--                                        class="-text-copy-me js-copy-to-clipboard f-9"--}}
{{--                                        data-container="providerUserModalMobile"--}}
{{--                                        data-html="true"--}}
{{--                                        data-message="✓ คัดลอกแล้ว"--}}
{{--                                        data-copy-me="Aa309361"--}}
{{--                                        data-theme="copy-me"--}}
{{--                                        data-arrow="true"--}}
{{--                                    >--}}
{{--                                        <i class="fas fa-copy"></i>--}}
{{--                                        <div id="-copy-message"></div>--}}
{{--                                    </a>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}

{{--</div>--}}
@auth
    <div
            class="x-modal modal -v2 -with-more-than-half-size"
            id="bonusModalMobile"
            data="coupon-apply?isMobileView=1"
            data-container="#bonusModalMobile"
    >
        <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-mobile"
             role="document">
            <div class="modal-content -modal-content">
                <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>

                <div class="modal-header -modal-header">
                    <div class="x-modal-mobile-header">
                        <div class="-header-mobile-container">
                            <h3 class="x-title-modal text-center mx-auto">
                                {{ __('app.home.get_bonus') }}
                            </h3>
                        </div>
                    </div>
                </div>

                <div class="modal-body -modal-body">
                    <div class="x-account-coupon">
                        <div data-animatable="fadeInModal" class="-coupon-container animated fadeInModal mb-2">

                            <div class="x-deposit-promotion-outer-container js-scroll-ltr -fade -on-left -on-right">
                                <div
                                        class="x-deposit-promotion -v2 -slide pt-0 -has-promotion"
                                        data-scroll-booster-container=".x-deposit-promotion-outer-container"
                                        data-scroll-booster-content=".x-deposit-promotion"
                                        style="transform: translate(0px, 0px);"
                                >

                                    <div class="-promotion-box-wrapper">
                                        <button type="button" class="btn -promotion-box-apply-btn js-promotion-apply"
                                                onclick="openPopup('BONUS','{{ __('app.bonus.wheel') }}')"
                                                data-url="/promotion/2/apply" data-type="deposit"
                                                data-display-slide-mode="true">
                                            <picture>
                                                <source type="image/webp"
                                                        srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.webp?v=2"/>
                                                <source type="image/png"
                                                        srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                                <img class="-img" alt="BONUS" width="26" height="26" loading="lazy"
                                                     fetchpriority="low"
                                                     src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                            </picture>

                                            <span class="-title">{{ __('app.home.wheel') }}</span>
                                            <span class="-sub-title">{{ $userdata->bonus }}</span>
                                        </button>
                                        <a href="javascript:void(0)"
                                           class="-promotion-box-cancel-btn js-cancel-promotion"
                                           data-display-slide-mode="true">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                    <div class="-promotion-box-wrapper">
                                        <button type="button" class="btn -promotion-box-apply-btn js-promotion-apply"
                                                onclick="openPopup('FASTSTART','{{ __('app.bonus.faststart') }}')"
                                                data-url="/promotion/2/apply" data-type="deposit"
                                                data-display-slide-mode="true">
                                            <picture>
                                                <source type="image/webp"
                                                        srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.webp?v=2"/>
                                                <source type="image/png"
                                                        srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                                <img class="-img" alt="BONUS" width="26" height="26" loading="lazy"
                                                     fetchpriority="low"
                                                     src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                            </picture>

                                            <span class="-title">{{ __('app.home.suggest') }}</span>
                                            <span class="-sub-title">{{ $userdata->faststart }}</span>
                                        </button>
                                        <a href="javascript:void(0)"
                                           class="-promotion-box-cancel-btn js-cancel-promotion"
                                           data-display-slide-mode="true">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                    <div class="-promotion-box-wrapper">
                                        <button type="button" class="btn -promotion-box-apply-btn js-promotion-apply"
                                                onclick="openPopup('CASHBACK','{{ __('app.bonus.cashback') }}')"
                                                data-url="/promotion/2/apply" data-type="deposit"
                                                data-display-slide-mode="true">
                                            <picture>
                                                <source type="image/webp"
                                                        srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.webp?v=2"/>
                                                <source type="image/png"
                                                        srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                                <img class="-img" alt="BONUS" width="26" height="26" loading="lazy"
                                                     fetchpriority="low"
                                                     src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                            </picture>

                                            <span class="-title">{{ __('app.home.cashback') }}</span>
                                            <span class="-sub-title">{{ $userdata->cashback }}</span>
                                        </button>
                                        <a href="javascript:void(0)"
                                           class="-promotion-box-cancel-btn js-cancel-promotion"
                                           data-display-slide-mode="true">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>

                                </div>


                            </div>

                            <div class="x-deposit-promotion-outer-container js-scroll-ltr -fade -on-left -on-right">
                                <div
                                        class="x-deposit-promotion -v2 -slide pt-0 -has-promotion"
                                        data-scroll-booster-container=".x-deposit-promotion-outer-container"
                                        data-scroll-booster-content=".x-deposit-promotion"
                                        style="transform: translate(0px, 0px);"
                                >

                                    <div class="-promotion-box-wrapper">
                                        <button type="button" class="btn -promotion-box-apply-btn js-promotion-apply"
                                                onclick="openPopup('IC','{{ __('app.bonus.ic') }}')"
                                                data-url="/promotion/2/apply" data-type="deposit"
                                                data-display-slide-mode="true">
                                            <picture>
                                                <source type="image/webp"
                                                        srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.webp?v=2"/>
                                                <source type="image/png"
                                                        srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                                <img class="-img" alt="BONUS" width="26" height="26" loading="lazy"
                                                     fetchpriority="low"
                                                     src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                            </picture>

                                            <span class="-title">IC</span>
                                            <span class="-sub-title">{{ $userdata->ic }}</span>
                                        </button>
                                        <a href="javascript:void(0)"
                                           class="-promotion-box-cancel-btn js-cancel-promotion"
                                           data-display-slide-mode="true">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                    <div class="-promotion-box-wrapper">
                                        <button type="button" class="btn -promotion-box-apply-btn js-promotion-apply"
                                                onclick="bonusModal()"
                                                data-url="/promotion/2/apply" data-type="deposit"
                                                data-display-slide-mode="true">
                                            <picture>
                                                <source type="image/webp"
                                                        srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.webp?v=2"/>
                                                <source type="image/png"
                                                        srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                                <img class="-img" alt="BONUS" width="26" height="26" loading="lazy"
                                                     fetchpriority="low"
                                                     src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-gift.png"/>
                                            </picture>

                                            <span class="-title">{{ __('app.home.coupon') }}</span>

                                        </button>
                                        <a href="javascript:void(0)"
                                           class="-promotion-box-cancel-btn js-cancel-promotion"
                                           data-display-slide-mode="true">
                                            <i class="fas fa-times"></i>
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
@endauth
@auth
    <div
            class="x-modal modal -v2 -with-more-than-half-size"
            id="couponModalMobile"
            data="coupon-apply?isMobileView=1"
            data-container="#couponModalMobile"
    >
        <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-mobile"
             role="document">
            <div class="modal-content -modal-content">
                <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>

                <div class="modal-header -modal-header">
                    <div class="x-modal-mobile-header">
                        <div class="-header-mobile-container">
                            <h3 class="x-title-modal text-center mx-auto">
                                {{ __('app.home.use_coupon') }}
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="modal-body -modal-body">
                    <div class="x-account-coupon">
                        <div data-animatable="fadeInModal" class="-coupon-container animated fadeInModal">
                            <div class="-coupon-member-detail mb-3 mt-5">
                                <div class="-coupon-box d-flex">
                                    <img alt="คูปอง เว็บไซต์พนันออนไลน์ คาสิโนออนไลน์"
                                         class="img-fluid -ic-coupon m-auto"
                                         src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-coupon.png"/>
                                </div>
                                <div class="-text-box-container">
                                    <div class="-title-member">{{ __('app.home.special_member') }}</div>
                                    <div class="-text-member">{{ $userdata->user_name }}</div>
                                </div>
                            </div>

                            <div class="-form-coupon-container">
                                <form
                                        id="frmcoupon2"
                                        name="frmcoupon2"
                                        method="post"
                                        data-dismiss-modal="#couponModalMobile"
                                        data-container="#couponModalMobile"
                                        action="{{ route('customer.coupon.redeem') }}" onsubmit="return false"
                                >
                                    @csrf
                                    <div class="my-4 -x-input-icon">
                                        <img alt="คูปอง เว็บไซต์พนันออนไลน์ คาสิโนออนไลน์" class="-icon" loading="lazy"
                                             fetchpriority="low"
                                             src="/assets/wm356/images/ic-coupon-input.png?v=2"/>

                                        <input type="text" id="coupon_coupon2" name="coupon" required="required"
                                               class="x-coupon-input text-center form-control"
                                               placeholder="{{ __('app.home.coupon_code') }}"/>
                                    </div>

                                    <div class="-btn-submit-container">
                                        <button type="button" class="btn -submit btn-primary">
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
@endauth
{{--<div--}}
{{--    class="x-modal modal -v2 -with-more-than-half-size"--}}
{{--    id="joinPromotionModalMobile"--}}
{{--    data="promotion?isMobileView=1"--}}
{{--    data-container="#joinPromotionModalMobile"--}}
{{-->--}}
{{--    <div--}}
{{--        class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-mobile -no-fixed-button -no-padding-x"--}}
{{--        role="document">--}}
{{--        <div class="modal-content -modal-content">--}}
{{--            <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">--}}
{{--                <i class="fas fa-times"></i>--}}
{{--            </button>--}}

{{--            <div class="modal-header -modal-header">--}}
{{--                <div class="x-modal-mobile-header">--}}
{{--                    <div class="-header-mobile-container">--}}
{{--                        <h3 class="x-title-modal text-center mx-auto">--}}
{{--                            โปรโมชั่นที่เข้าร่วม--}}
{{--                        </h3>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="modal-body -modal-body">--}}
{{--                <div class="x-account-promotion text-center">--}}
{{--                    <div class="-account-promotion-container animated fadeInModal" data-animatable="fadeInModal">--}}
{{--                        <div class="-no-result-container">--}}
{{--                            <img alt="เว็บไซต์พนันออนไลน์ คาสิโนออนไลน์" class="img-fluid -no-result-img"--}}
{{--                                 width="150" height="150"--}}
{{--                                 src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-promotion-no-result.png"/>--}}
{{--                        </div>--}}
{{--                        <div class="text-center -text-container">--}}
{{--                            คุณยังไม่มีโปรโมชั่นที่เข้าร่วม--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}

{{--</div>--}}

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
    <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-deposit -modal-mobile"
         role="document">
        <div class="modal-content -modal-content">
            <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-header -modal-header">
                <h3 class="x-title-modal m-auto">
                    {{ __('app.home.topup_channel') }}
                </h3>
            </div>

            <div class="modal-body -modal-body">
                <div class="x-deposit-form -v2">
                    <div class="-deposit-container">
                        <div data-animatable="fadeInModal" class="order-lg-2 -form order-0 animated fadeInModal">
                            <div class="container">
                                <div class="el-input my-1">

                                    <div
                                            class="x-deposit-promotion-outer-container js-scroll-ltr -fade -on-left -on-right">
                                        <div
                                                class="x-deposit-promotion -v2 -slide pt-0 -has-promotion"
                                                data-scroll-booster-container=".x-deposit-promotion-outer-container"
                                                data-scroll-booster-content=".x-deposit-promotion"
                                                style="transform: translate(0px, 0px);"
                                        >
                                            @if(count($topupbanks) > 0)
                                                <div class="-promotion-box-wrapper width50">
                                                    <button type="button"
                                                            onclick="topupSelect('topup_bank')"
                                                            class="btn -promotion-box-apply-btn js-promotion-apply btn-for-deposit"
                                                            data-url="/promotion/2/apply" data-type="deposit"
                                                            data-display-slide-mode="true">
                                                        <picture>
                                                            <source type="image/webp"
                                                                    srcset="https://img2.pic.in.th/pic/bank19da438c9e295f0b.png"/>
                                                            <source type="image/png"
                                                                    srcset="https://img2.pic.in.th/pic/bank19da438c9e295f0b.png"/>
                                                            <img class="-img img50" alt="BONUS" width="26" height="26"
                                                                 loading="lazy" fetchpriority="low"
                                                                 src="https://img2.pic.in.th/pic/bank19da438c9e295f0b.png"/>
                                                        </picture>

                                                        <span class="-title">{{ __('app.home.topup_bank') }}</span>

                                                    </button>
                                                    <a href="javascript:void(0)"
                                                       class="-promotion-box-cancel-btn js-cancel-promotion"

                                                       data-display-slide-mode="true">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                </div>
                                            @endif
                                            @if($config->qrscan == 'Y')
                                                <div class="-promotion-box-wrapper width50">
                                                    <button type="button"
                                                            onclick="topupSelect('topup_qrscan')"
                                                            class="btn -promotion-box-apply-btn js-promotion-apply btn-for-deposit"
                                                            data-url="/promotion/2/apply" data-type="deposit"
                                                            data-display-slide-mode="true">
                                                        <picture>
                                                            <source type="image/webp"
                                                                    srcset="https://img5.pic.in.th/file/secure-sv1/qr0068bdbf0cc6226d.png"/>
                                                            <source type="image/png"
                                                                    srcset="https://img5.pic.in.th/file/secure-sv1/qr0068bdbf0cc6226d.png"/>
                                                            <img class="-img img50" alt="BONUS" width="26" height="26"
                                                                 loading="lazy" fetchpriority="low"
                                                                 src="https://img5.pic.in.th/file/secure-sv1/qr0068bdbf0cc6226d.png"/>
                                                        </picture>

                                                        <span class="-title">{{ __('app.home.topup_scan') }}</span>

                                                    </button>
                                                    <a href="javascript:void(0)"
                                                       class="-promotion-box-cancel-btn js-cancel-promotion"

                                                       data-display-slide-mode="true">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                </div>
                                            @endif

                                        </div>
                                    </div>

                                </div>

                                <div id="topup_bank" class="-deposit-form-inner-wrapper table-responsive-new" style="display:none">
                                    @foreach($topupbanks as $bank)
                                        @foreach($bank['banks_account'] as $item)


                                            <div class="-bank-info-container mt-3 ml-3 mr-3">
                                                <div class="x-customer-bank-info-container -v2">
                                                    <div class="media m-auto">
                                                        <img loading="lazy" fetchpriority="low"
                                                             src="{{ $bank['filepic'] }}"
                                                             class="-img rounded-circle" width="50" height="50"
                                                             alt="bank-ktb"/>
                                                        <div class="-content-wrapper">
                                                            <span class="-name">{{ $bank['name_th'] }}</span>
                                                            <span class="-name">{{ $item['acc_name'] }}</span>
                                                            <span class="-number">{{$item['acc_no'] }}</span>
                                                            <button onclick="copylink()" class="btncopy btn btn-flat"
                                                                    data-clipboard-text="{{ $item['acc_no'] }}"><i
                                                                        class="fa fa-copy"></i> {{ __('app.con.copy') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                    @if($item['qrcode'] === 'Y')

                                                        <div class="media m-auto">
                                                            <img loading="lazy" fetchpriority="low"
                                                                 src="{{ $bank['filepic2'] }}"
                                                                 class="img-fluid"
                                                                 alt="bank-ktb"/>
                                                        </div>

                                                    @endif
                                                </div>

                                            </div>


                                        @endforeach
                                    @endforeach
                                    <div class="-bank-info-container mt-3 ml-3 mr-3 text-center">
                                        <small>{{ __('app.topup.remark') }}</small>
                                    </div>
                                </div>
                                @if($config->qrscan == 'Y')
                                    <div id="topup_qrscan" class="-deposit-form-inner-wrapper" style="display:none">

                                        <br>
{{--                                        <p class="text-center">{{ __('app.topup.papayapay_detail_1') }}</p>--}}
{{--                                        @auth--}}
{{--                                            <div class="-bank-info-container">--}}
{{--                                                <div class="x-customer-bank-info-container -v2">--}}
{{--                                                    <div class="media m-auto">--}}
{{--                                                        <img loading="lazy" fetchpriority="low"--}}
{{--                                                             src="{{ Storage::url('bank_img/' . $userdata->bank->filepic) }}"--}}
{{--                                                             class="-img rounded-circle" width="50" height="50"--}}
{{--                                                             alt="bank-ktb"/>--}}
{{--                                                        <div class="-content-wrapper">--}}
{{--                                                            <span class="-name">{{ $userdata->name }}</span>--}}
{{--                                                            <span class="-number">{{ $userdata->acc_no }}</span>--}}
{{--                                                        </div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                        @endauth--}}
                                        <form novalidate="" id="frmqrscan" name="deposit"
                                              method="post"
                                              class="qrscan"
                                              action="" onsubmit="return false;">
                                            @csrf
                                            <div class="-fake-bg-bottom-wrapper">
                                                <div class="x-modal-separator-container">
                                                    <div class="-top">
                                                        <div class="-promotion-intro-deposit -spacer">
                                                            <div class="js-promotion-active-html"></div>
                                                        </div>

{{--                                                        <div class="-spacer">--}}
{{--                                                            <div class="js-turnover text-center">--}}
{{--                                                                <div class="-turnover-wrapper">Rate : <span>{{ $config->rate }}</span>--}}
{{--                                                                <div class="-turnover-wrapper">Last Update : <span>{{ $config->rate_update }}</span>--}}
{{--                                                                </div>--}}
{{--                                                            </div>--}}
{{--                                                        </div>--}}

                                                        <div class="-spacer pt-2">
                                                            <div
                                                                    class="-x-input-icon x-input-operator mb-3 flex-column">
                                                                <button type="button"
                                                                        class="-icon-left -btn-icon js-adjust-amount-by-operator"
                                                                        data-operator="-" data-value="1">
                                                                    <i class="fas fa-minus-circle"></i>
                                                                </button>

                                                                <input
                                                                        type="text"
                                                                        id="deposit_amount"
                                                                        name="amount"
                                                                        required="required"
                                                                        pattern="[0-9]*"
                                                                        value="200"
                                                                        class="x-form-control -no text-center js-deposit-input-amount form-control"
                                                                        placeholder="เงินฝากขั้นต่ำ 200"
                                                                        inputmode="text"
                                                                />
                                                                <button type="button"
                                                                        class="-icon-right -btn-icon js-adjust-amount-by-operator"
                                                                        data-operator="+" data-value="1">
                                                                    <i class="fas fa-plus-circle"></i>
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <div class="-spacer">
                                                            <div class="x-select-amount js-quick-amount -v2"
                                                                 data-target-input="#deposit_amount">

                                                                {{--                                                                <div class="-amount-container">--}}
                                                                {{--                                                                    <button type="button"--}}
                                                                {{--                                                                            class="btn btn-block -btn-select-amount"--}}
                                                                {{--                                                                            data-amount="100">--}}
                                                                {{--                                                                        <span class="-no">100</span>--}}
                                                                {{--                                                                    </button>--}}
                                                                {{--                                                                </div>--}}
                                                                {{--                                                                <div class="-amount-container">--}}
                                                                {{--                                                                    <button type="button"--}}
                                                                {{--                                                                            class="btn btn-block -btn-select-amount"--}}
                                                                {{--                                                                            data-amount="200">--}}
                                                                {{--                                                                        <span class="-no">200</span>--}}
                                                                {{--                                                                    </button>--}}
                                                                {{--                                                                </div>--}}
                                                                <div class="-amount-container">
                                                                    <button type="button"
                                                                            class="btn btn-block -btn-select-amount"
                                                                            data-amount="200">
                                                                        <span class="-no">200</span>
                                                                    </button>
                                                                </div>
                                                                <div class="-amount-container">
                                                                    <button type="button"
                                                                            class="btn btn-block -btn-select-amount"
                                                                            data-amount="300">
                                                                        <span class="-no">300</span>
                                                                    </button>
                                                                </div>
                                                                <div class="-amount-container">
                                                                    <button type="button"
                                                                            class="btn btn-block -btn-select-amount"
                                                                            data-amount="400">
                                                                        <span class="-no">400</span>
                                                                    </button>
                                                                </div>
                                                                <div class="-amount-container">
                                                                    <button type="button"
                                                                            class="btn btn-block -btn-select-amount"
                                                                            data-amount="500">
                                                                        <span class="-no">500</span>
                                                                    </button>
                                                                </div>
                                                                <div class="-amount-container">
                                                                    <button type="button"
                                                                            class="btn btn-block -btn-select-amount"
                                                                            data-amount="600">
                                                                        <span class="-no">600</span>
                                                                    </button>
                                                                </div>
                                                                <div class="-amount-container">
                                                                    <button type="button"
                                                                            class="btn btn-block -btn-select-amount"
                                                                            data-amount="1000">
                                                                        <span class="-no">1000</span>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="-spacer">
                                                            <hr class="-liner"/>
                                                        </div>

                                                        <div class="-bank-info-container mt-3 ml-3 mr-3" style="color:yellow">
                                                            {{--                                            <small>{{ __('app.topup.hengpay_detail_1') }} </small><br>--}}
                                                            <small>{{ __('app.topup.maintenance') }} </small><br>
{{--                                                            <small>{{ __('app.topup.papayapay_detail_2') }} </small><br>--}}
{{--                                                            <small>{{ __('app.topup.papayapay_detail_3') }} </small><br>--}}
                                                        </div>
                                                        <div class="text-center -spacer">
                                                            <button type="button" id="btnqrsubmit"
                                                                    class="btn btn-primary my-0 my-lg-3">
                                                                {{ __('app.login.submit') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="-bottom"></div>
                                                </div>
                                            </div>

                                        </form>


                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@auth
    @if(request()->routeIs('customer.credit.*'))
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
                            {{ __('app.home.withdraw_freecredit') }}
                        </h3>
                    </div>
                    <div class="modal-body -modal-body">
                        <div class="x-withdraw-form -v2">
                            <form novalidate="" name="withdraw" method="post" id="frmwithdraw"
                                  data-container="#withdrawModal" action="{{ route('customer.credit.withdraw.store') }}"
                                  onsubmit="return false;">
                                @csrf
                                <div data-animatable="fadeInModal" class="-animatable-container animated fadeInModal">
                                    <div class="text-center d-flex flex-column">
                                        <input required readonly
                                               step="1.00"
                                               min="1"
                                               :class="[errors.has('amount') ? 'is-invalid' : '']"
                                               class="form-control x-from-control" type="number"
                                               placeholder="กรุณากรอกจำนวนเงิน"
                                               id="amount" name="amount"
                                               data-vv-as="&quot;Amount&quot;"
                                               autocomplete="off"
                                               value="{{ floor($userdata->balance_free) }}">

                                        <br>
                                        <p class="text-center text-warning">
                                            {{ __('app.home.withdraw_turn') }} {{number_format($userdata->turnprofree,2) }}
                                            /
                                            {{ __('app.home.withdraw_max') }} {{ number_format($userdata->limitfree,2) }}</p>
                                        <p class="text-center text-warning">
                                            {{ __('app.home.withdraw_min') }} {{ $config->free_minwithdraw }}
                                            {{ __('app.home.withdraw_baht') }}</p>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn -submit btn-primary my-0 my-lg-3">
                                            {{ __('app.login.submit') }}
                                        </button>
                                    </div>
                                </div>


                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @else
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
                            {{ __('app.home.withdraw') }}
                        </h3>
                    </div>
                    <div class="modal-body -modal-body">
                        <div class="x-withdraw-form -v2">
                            <form novalidate="" name="withdraw" method="post" id="frmwithdraw"
                                  data-container="#withdrawModal" action="{{ route('customer.withdraw.store') }}"
                                  onsubmit="return false;">
                                @csrf
                                <div data-animatable="fadeInModal" class="-animatable-container animated fadeInModal">
                                    <div class="text-center d-flex flex-column">
                                        <input required
                                               {{ ($userdata->pro === true ? 'readonly' : '') }}
                                               step="1.00"
                                               min="1"
                                               :class="[errors.has('amount') ? 'is-invalid' : '']"
                                               class="form-control x-from-control" type="number"
                                               placeholder="กรุณากรอกจำนวนเงิน"
                                               id="amount" name="amount"
                                               data-vv-as="&quot;Amount&quot;"
                                               autocomplete="off"
                                               value="{{ floor($userdata->balance) }}">

                                        <br>
                                        @if($userdata->pro === true)
                                        <p class="text-center text-warning">
                                            {{ __('app.home.withdraw_turn') }} {{number_format($userdata->turnpro,2) }}
                                            /
                                            {{ __('app.home.withdraw_max') }} {{ number_format($userdata->limit,2) }}</p>
                                        @endif
                                            <p class="text-center text-warning">
                                            {{ __('app.home.withdraw_min') }} {{ $config->minwithdraw }}
                                            {{ __('app.home.withdraw_baht') }}</p>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn -submit btn-primary my-0 my-lg-3">
                                            {{ __('app.login.submit') }}
                                        </button>
                                    </div>
                                </div>


                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @endif
@endauth
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
{{--<div--}}
{{--        class="x-modal modal -v2 -with-half-size"--}}
{{--        id="changePasswordModal"--}}
{{--        data-container="#changePassordModal"--}}
{{--        data-dismiss-modal-target="#accountModalMobile, #accountModal"--}}
{{-->--}}
{{--    <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -dialog-in-tab -change-password-index-dialog" role="document">--}}
{{--        <div class="modal-content -modal-content">--}}
{{--            <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">--}}
{{--                <i class="fas fa-times"></i>--}}
{{--            </button>--}}
{{--            <div class="modal-header -modal-header">--}}
{{--                <h3 class="x-title-modal d-inline-block m-auto">--}}
{{--                    <span></span>--}}
{{--                </h3>--}}
{{--            </div>--}}
{{--            <div class="modal-body -modal-body">--}}
{{--                <div class="x-register-tab-container -register js-tab-pane-checker-v3">--}}
{{--                    <div class="container">--}}
{{--                        <ul class="nav nav-tabs x-register-tab js-change-password-tab">--}}
{{--                            <li class="nav-item active -currentPassword" id="tab-currentPassword">--}}
{{--                                <a data-toggle="tab" href="https://wm356.co/#tab-content-currentPassword" class="nav-link">--}}
{{--                                    currentPassword--}}
{{--                                </a>--}}
{{--                            </li>--}}
{{--                            <li class="nav-item -newPassword" id="tab-newPassword">--}}
{{--                                <a data-toggle="tab" href="https://wm356.co/#tab-content-newPassword" class="nav-link">--}}
{{--                                    newPassword--}}
{{--                                </a>--}}
{{--                            </li>--}}
{{--                            <li class="nav-item -resultChangePasswordSuccess" id="tab-resultChangePasswordSuccess">--}}
{{--                                <a data-toggle="tab" href="https://wm356.co/#tab-content-resultChangePasswordSuccess" class="nav-link">--}}
{{--                                    resultChangePasswordSuccess--}}
{{--                                </a>--}}
{{--                            </li>--}}
{{--                        </ul>--}}
{{--                    </div>--}}
{{--                    <form method="post" name="js-change-password-current" data-register-v3-form="/_ajax_/v3/change-password" data-register-step="changePassword" data-is-passcode="1">--}}
{{--                        <div class="tab-content">--}}
{{--                            <div class="tab-pane active" id="tab-content-currentPassword" data-completed-dismiss-modal="">--}}
{{--                                <div class="x-modal-body-base -v3 x-form-register-v3">--}}
{{--                                    <div class="row -register-container-wrapper">--}}
{{--                                        <div class="col">--}}
{{--                                            <div class="x-title-register-modal-v3">--}}
{{--                                                <span class="-title">รหัสผ่านเดิม 6 หลัก</span>--}}
{{--                                                <span class="-sub-title">กรุณากรอกเลขรหัสผ่านเดิม 6 หลัก</span>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}

{{--                                        <div data-animatable="fadeInRegister" data-offset="0" class="col animated fadeInRegister">--}}
{{--                                            <div class="x-modal-separator-container x-form-change-password">--}}
{{--                                                <div class="-top">--}}
{{--                                                    <div data-animatable="fadeInModal" data-offset="0" class="-animatable-container -password-body animated fadeInModal">--}}
{{--                                                        <div class="d-flex -password-input-container js-register-v3-input-group">--}}
{{--                                                            <div class="js-separator-container js-change-password-container">--}}
{{--                                                                <input--}}
{{--                                                                        type="text"--}}
{{--                                                                        id="currentPassword_1"--}}
{{--                                                                        name="currentPassword_1"--}}
{{--                                                                        readonly=""--}}
{{--                                                                        inputmode="text"--}}
{{--                                                                        pattern="[0-9]*"--}}
{{--                                                                        class="-digit-password js-otp-input"--}}
{{--                                                                        data-separator-input="true"--}}
{{--                                                                        data-type="current_set_password"--}}
{{--                                                                />--}}
{{--                                                            </div>--}}

{{--                                                            <div class="js-separator-container js-change-password-container">--}}
{{--                                                                <input--}}
{{--                                                                        type="text"--}}
{{--                                                                        id="currentPassword_2"--}}
{{--                                                                        name="currentPassword_2"--}}
{{--                                                                        readonly=""--}}
{{--                                                                        inputmode="text"--}}
{{--                                                                        pattern="[0-9]*"--}}
{{--                                                                        class="-digit-password js-otp-input"--}}
{{--                                                                        data-separator-input="true"--}}
{{--                                                                        data-type="current_set_password"--}}
{{--                                                                />--}}
{{--                                                            </div>--}}

{{--                                                            <div class="js-separator-container js-change-password-container">--}}
{{--                                                                <input--}}
{{--                                                                        type="text"--}}
{{--                                                                        id="currentPassword_3"--}}
{{--                                                                        name="currentPassword_3"--}}
{{--                                                                        readonly=""--}}
{{--                                                                        inputmode="text"--}}
{{--                                                                        pattern="[0-9]*"--}}
{{--                                                                        class="-digit-password js-otp-input"--}}
{{--                                                                        data-separator-input="true"--}}
{{--                                                                        data-type="current_set_password"--}}
{{--                                                                />--}}
{{--                                                            </div>--}}

{{--                                                            <div class="js-separator-container js-change-password-container">--}}
{{--                                                                <input--}}
{{--                                                                        type="text"--}}
{{--                                                                        id="currentPassword_4"--}}
{{--                                                                        name="currentPassword_4"--}}
{{--                                                                        readonly=""--}}
{{--                                                                        inputmode="text"--}}
{{--                                                                        pattern="[0-9]*"--}}
{{--                                                                        class="-digit-password js-otp-input"--}}
{{--                                                                        data-separator-input="true"--}}
{{--                                                                        data-type="current_set_password"--}}
{{--                                                                />--}}
{{--                                                            </div>--}}

{{--                                                            <div class="js-separator-container js-change-password-container">--}}
{{--                                                                <input--}}
{{--                                                                        type="text"--}}
{{--                                                                        id="currentPassword_5"--}}
{{--                                                                        name="currentPassword_5"--}}
{{--                                                                        readonly=""--}}
{{--                                                                        inputmode="text"--}}
{{--                                                                        pattern="[0-9]*"--}}
{{--                                                                        class="-digit-password js-otp-input"--}}
{{--                                                                        data-separator-input="true"--}}
{{--                                                                        data-type="current_set_password"--}}
{{--                                                                />--}}
{{--                                                            </div>--}}

{{--                                                            <div class="js-separator-container js-change-password-container">--}}
{{--                                                                <input--}}
{{--                                                                        type="text"--}}
{{--                                                                        id="currentPassword_6"--}}
{{--                                                                        name="currentPassword_6"--}}
{{--                                                                        readonly=""--}}
{{--                                                                        inputmode="text"--}}
{{--                                                                        pattern="[0-9]*"--}}
{{--                                                                        class="-digit-password js-otp-input"--}}
{{--                                                                        data-separator-input="true"--}}
{{--                                                                        data-type="current_set_password"--}}
{{--                                                                />--}}
{{--                                                            </div>--}}
{{--                                                        </div>--}}

{{--                                                        <input type="hidden" id="currentPassword" name="currentPassword" pattern="[0-9]*" class="form-control mb-3" />--}}

{{--                                                        <div class="d-none js-keypad-number-wrapper">--}}
{{--                                                            <div class="x-keypad-number-container">--}}
{{--                                                                <div class="-btn-group-wrapper">--}}
{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="1"--}}
{{--                                                                            data-target="#currentPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'--}}
{{--                                                                    >--}}
{{--                                                                        1--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="2"--}}
{{--                                                                            data-target="#currentPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'--}}
{{--                                                                    >--}}
{{--                                                                        2--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="3"--}}
{{--                                                                            data-target="#currentPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'--}}
{{--                                                                    >--}}
{{--                                                                        3--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="4"--}}
{{--                                                                            data-target="#currentPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'--}}
{{--                                                                    >--}}
{{--                                                                        4--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="5"--}}
{{--                                                                            data-target="#currentPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'--}}
{{--                                                                    >--}}
{{--                                                                        5--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="6"--}}
{{--                                                                            data-target="#currentPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'--}}
{{--                                                                    >--}}
{{--                                                                        6--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="7"--}}
{{--                                                                            data-target="#currentPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'--}}
{{--                                                                    >--}}
{{--                                                                        7--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="8"--}}
{{--                                                                            data-target="#currentPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'--}}
{{--                                                                    >--}}
{{--                                                                        8--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="9"--}}
{{--                                                                            data-target="#currentPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'--}}
{{--                                                                    >--}}
{{--                                                                        9--}}
{{--                                                                    </button>--}}

{{--                                                                    <div--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-none"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="none"--}}
{{--                                                                            data-target="#currentPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'--}}
{{--                                                                    ></div>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="0"--}}
{{--                                                                            data-target="#currentPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'--}}
{{--                                                                    >--}}
{{--                                                                        0--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-backspace"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="backspace"--}}
{{--                                                                            data-target="#currentPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-change-password-container","enabledButtonTarget":".js-current-password-button","targetSubmitForm":"js-change-password-current"}'--}}
{{--                                                                    >--}}
{{--                                                                        <i class="fas fa-backspace"></i>--}}
{{--                                                                    </button>--}}
{{--                                                                </div>--}}
{{--                                                            </div>--}}
{{--                                                        </div>--}}

{{--                                                        <div class="text-center">--}}
{{--                                                            <button--}}
{{--                                                                    type="button"--}}
{{--                                                                    class="btn -submit btn-primary my-lg-3 mt-0 js-current-password-button"--}}
{{--                                                                    onclick="$(&#39;a[href=\&#39;#tab-content-newPassword\&#39;]&#39;).click();"--}}
{{--                                                            >--}}
{{--                                                                ยืนยัน--}}
{{--                                                            </button>--}}
{{--                                                        </div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                                <div class="-bottom"></div>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}

{{--                            <div class="tab-pane" id="tab-content-newPassword" data-completed-dismiss-modal="">--}}
{{--                                <div class="x-modal-body-base -v3 x-form-register-v3">--}}
{{--                                    <div class="row -register-container-wrapper">--}}
{{--                                        <div class="col">--}}
{{--                                            <div class="x-title-register-modal-v3">--}}
{{--                                                <span class="-title">ตั้งรหัส 6 หลักใหม่</span>--}}
{{--                                                <span class="-sub-title">กรอกเลขรหัส 6 หลัก เพื่อใช้เข้าสู่ระบบ</span>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}

{{--                                        <div data-animatable="fadeInRegister" data-offset="0" class="col animated fadeInRegister">--}}
{{--                                            <div class="x-modal-separator-container x-form-change-password">--}}
{{--                                                <div class="-top">--}}
{{--                                                    <div data-animatable="fadeInModal" data-offset="0" class="-animatable-container -password-body animated fadeInModal">--}}
{{--                                                        <div class="d-flex -password-input-container js-register-v3-input-group">--}}
{{--                                                            <div class="js-separator-container js-new-password-container">--}}
{{--                                                                <input--}}
{{--                                                                        type="text"--}}
{{--                                                                        id="newPassword_1"--}}
{{--                                                                        name="newPassword_1"--}}
{{--                                                                        inputmode="text"--}}
{{--                                                                        pattern="[0-9]*"--}}
{{--                                                                        class="-digit-password js-otp-input"--}}
{{--                                                                        data-separator-input="true"--}}
{{--                                                                        data-type="set_new_password"--}}
{{--                                                                        required=""--}}
{{--                                                                />--}}
{{--                                                            </div>--}}

{{--                                                            <div class="js-separator-container js-new-password-container">--}}
{{--                                                                <input--}}
{{--                                                                        type="text"--}}
{{--                                                                        id="newPassword_2"--}}
{{--                                                                        name="newPassword_2"--}}
{{--                                                                        inputmode="text"--}}
{{--                                                                        pattern="[0-9]*"--}}
{{--                                                                        class="-digit-password js-otp-input"--}}
{{--                                                                        data-separator-input="true"--}}
{{--                                                                        data-type="set_new_password"--}}
{{--                                                                        required=""--}}
{{--                                                                />--}}
{{--                                                            </div>--}}

{{--                                                            <div class="js-separator-container js-new-password-container">--}}
{{--                                                                <input--}}
{{--                                                                        type="text"--}}
{{--                                                                        id="newPassword_3"--}}
{{--                                                                        name="newPassword_3"--}}
{{--                                                                        inputmode="text"--}}
{{--                                                                        pattern="[0-9]*"--}}
{{--                                                                        class="-digit-password js-otp-input"--}}
{{--                                                                        data-separator-input="true"--}}
{{--                                                                        data-type="set_new_password"--}}
{{--                                                                        required=""--}}
{{--                                                                />--}}
{{--                                                            </div>--}}

{{--                                                            <div class="js-separator-container js-new-password-container">--}}
{{--                                                                <input--}}
{{--                                                                        type="text"--}}
{{--                                                                        id="newPassword_4"--}}
{{--                                                                        name="newPassword_4"--}}
{{--                                                                        inputmode="text"--}}
{{--                                                                        pattern="[0-9]*"--}}
{{--                                                                        class="-digit-password js-otp-input"--}}
{{--                                                                        data-separator-input="true"--}}
{{--                                                                        data-type="set_new_password"--}}
{{--                                                                        required=""--}}
{{--                                                                />--}}
{{--                                                            </div>--}}

{{--                                                            <div class="js-separator-container js-new-password-container">--}}
{{--                                                                <input--}}
{{--                                                                        type="text"--}}
{{--                                                                        id="newPassword_5"--}}
{{--                                                                        name="newPassword_5"--}}
{{--                                                                        inputmode="text"--}}
{{--                                                                        pattern="[0-9]*"--}}
{{--                                                                        class="-digit-password js-otp-input"--}}
{{--                                                                        data-separator-input="true"--}}
{{--                                                                        data-type="set_new_password"--}}
{{--                                                                        required=""--}}
{{--                                                                />--}}
{{--                                                            </div>--}}

{{--                                                            <div class="js-separator-container js-new-password-container">--}}
{{--                                                                <input--}}
{{--                                                                        type="text"--}}
{{--                                                                        id="newPassword_6"--}}
{{--                                                                        name="newPassword_6"--}}
{{--                                                                        inputmode="text"--}}
{{--                                                                        pattern="[0-9]*"--}}
{{--                                                                        class="-digit-password js-otp-input"--}}
{{--                                                                        data-separator-input="true"--}}
{{--                                                                        data-type="set_new_password"--}}
{{--                                                                        required=""--}}
{{--                                                                />--}}
{{--                                                            </div>--}}
{{--                                                        </div>--}}

{{--                                                        <input type="hidden" id="newPassword[first]" name="newPassword[first]" pattern="[0-9]*" class="form-control mb-3" />--}}

{{--                                                        <div class="d-none js-keypad-number-wrapper">--}}
{{--                                                            <div class="x-keypad-number-container">--}}
{{--                                                                <div class="-btn-group-wrapper">--}}
{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="1"--}}
{{--                                                                            data-target="#newPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
{{--                                                                    >--}}
{{--                                                                        1--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="2"--}}
{{--                                                                            data-target="#newPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
{{--                                                                    >--}}
{{--                                                                        2--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="3"--}}
{{--                                                                            data-target="#newPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
{{--                                                                    >--}}
{{--                                                                        3--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="4"--}}
{{--                                                                            data-target="#newPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
{{--                                                                    >--}}
{{--                                                                        4--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="5"--}}
{{--                                                                            data-target="#newPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
{{--                                                                    >--}}
{{--                                                                        5--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="6"--}}
{{--                                                                            data-target="#newPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
{{--                                                                    >--}}
{{--                                                                        6--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="7"--}}
{{--                                                                            data-target="#newPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
{{--                                                                    >--}}
{{--                                                                        7--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="8"--}}
{{--                                                                            data-target="#newPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
{{--                                                                    >--}}
{{--                                                                        8--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="9"--}}
{{--                                                                            data-target="#newPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
{{--                                                                    >--}}
{{--                                                                        9--}}
{{--                                                                    </button>--}}

{{--                                                                    <div--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-none"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="none"--}}
{{--                                                                            data-target="#newPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
{{--                                                                    ></div>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-keypad"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="0"--}}
{{--                                                                            data-target="#newPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
{{--                                                                    >--}}
{{--                                                                        0--}}
{{--                                                                    </button>--}}

{{--                                                                    <button--}}
{{--                                                                            class="btn -btn js-keypad-btn -btn-backspace"--}}
{{--                                                                            type="button"--}}
{{--                                                                            data-key="backspace"--}}
{{--                                                                            data-target="#newPassword_1"--}}
{{--                                                                            data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
{{--                                                                    >--}}
{{--                                                                        <i class="fas fa-backspace"></i>--}}
{{--                                                                    </button>--}}
{{--                                                                </div>--}}
{{--                                                            </div>--}}
{{--                                                        </div>--}}

{{--                                                        <div class="text-center">--}}
{{--                                                            <button type="submit" class="btn -submit btn-primary my-lg-3 mt-0 js-new-password-button">--}}
{{--                                                                ยืนยัน--}}
{{--                                                            </button>--}}
{{--                                                        </div>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                                <div class="-bottom"></div>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}

{{--                            <div class="tab-pane" id="tab-content-resultChangePasswordSuccess" data-completed-dismiss-modal="#changePasswordModal">--}}
{{--                                <div class="js-change-password-success-container">--}}
{{--                                    <div class="x-modal-body-base -v3 x-form-register-v3">--}}
{{--                                        <div class="row -register-container-wrapper">--}}
{{--                                            <div data-animatable="fadeInRegister" data-offset="0" class="col animated fadeInRegister">--}}
{{--                                                <div class="text-center d-flex flex-column justify-content-center h-100">--}}
{{--                                                    <div class="text-center">--}}
{{--                                                        <img alt="สมัครสมาชิก SUCCESS" class="js-ic-success -success-ic img-fluid" width="150" height="150" src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/animated-register-success.png" />--}}
{{--                                                    </div>--}}

{{--                                                    <div class="-title">เปลี่ยนรหัสผ่านสำเร็จ!</div>--}}
{{--                                                    <div class="-sub-title">ระบบกำลังพาคุณไปหน้าหลัก</div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </form>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</div>--}}
<div
        class="x-modal modal -v2"
        id="changePasswordModal"
        data="change-password"
        data-container="#changePassordModal"

>

    <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-deposit -modal-mobile"
         role="document">
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
                                <a data-toggle="tab" href="https://wm356.co/#tab-content-currentPassword"
                                   class="nav-link">
                                    currentPassword
                                </a>
                            </li>
                            <li class="nav-item -newPassword" id="tab-newPassword">
                                <a data-toggle="tab" href="https://wm356.co/#tab-content-newPassword" class="nav-link">
                                    newPassword
                                </a>
                            </li>
                            <li class="nav-item -resultChangePasswordSuccess" id="tab-resultChangePasswordSuccess">
                                <a data-toggle="tab" href="https://wm356.co/#tab-content-resultChangePasswordSuccess"
                                   class="nav-link">
                                    resultChangePasswordSuccess
                                </a>
                            </li>
                        </ul>
                    </div>
                    <form method="post" name="frmchangepass"
                          action="{{ route('customer.profile.changepass') }}">
                        @csrf
                        <div class="tab-content">
                            <div class="tab-pane active" id="tab-content-currentPassword"
                                 data-completed-dismiss-modal="">
                                <div class="x-modal-body-base -v3 x-form-register-v3">
                                    <div class="row -register-container-wrapper">
                                        <div class="col">
                                            <div class="x-title-register-modal-v3">
                                                <span class="-title">รหัสผ่านเดิม</span>
                                                <span class="-sub-title">กรุณากรอกเลขรหัสผ่านเดิม</span>
                                            </div>
                                        </div>

                                        <div data-animatable="fadeInRegister" data-offset="0"
                                             class="col animated fadeInRegister">
                                            <div class="x-modal-separator-container x-form-change-password">
                                                <div class="-top">
                                                    <div data-animatable="fadeInModal" data-offset="0"
                                                         class="-animatable-container -password-body animated fadeInModal">
                                                        <div class="d-flex -password-input-container js-register-v3-input-group">
                                                            <input
                                                                    type="text"
                                                                    id="currentPassword"
                                                                    name="currentPassword"
                                                                    required
                                                                    inputmode="text"
                                                                    class="-digit-password js-otp-input"

                                                            />
                                                        </div>
                                                        <span class="-sub-title">กรุณากรอกเลขรหัสผ่านใหม่</span>
                                                        <div class="d-flex -password-input-container js-register-v3-input-group">
                                                            <input
                                                                    type="text"
                                                                    id="newPassword"
                                                                    name="newPassword"
                                                                    inputmode="text"
                                                                    required

                                                                    class="-digit-password js-otp-input"

                                                            />
                                                        </div>


                                                        <div class="text-center">
                                                            <button
                                                                    type="submit"
                                                                    class="btn -submit btn-primary my-lg-3 mt-0 js-current-password-button">
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

                            {{--                                <div class="tab-pane" id="tab-content-newPassword" data-completed-dismiss-modal="">--}}
                            {{--                                    <div class="x-modal-body-base -v3 x-form-register-v3">--}}
                            {{--                                        <div class="row -register-container-wrapper">--}}
                            {{--                                            <div class="col">--}}
                            {{--                                                <div class="x-title-register-modal-v3">--}}
                            {{--                                                    <span class="-title">ตั้งรหัส 6 หลักใหม่</span>--}}
                            {{--                                                    <span class="-sub-title">กรอกเลขรหัส 6 หลัก เพื่อใช้เข้าสู่ระบบ</span>--}}
                            {{--                                                </div>--}}
                            {{--                                            </div>--}}

                            {{--                                            <div data-animatable="fadeInRegister" data-offset="0"--}}
                            {{--                                                 class="col animated fadeInRegister">--}}
                            {{--                                                <div class="x-modal-separator-container x-form-change-password">--}}
                            {{--                                                    <div class="-top">--}}
                            {{--                                                        <div data-animatable="fadeInModal" data-offset="0"--}}
                            {{--                                                             class="-animatable-container -password-body animated fadeInModal">--}}
                            {{--                                                            <div class="d-flex -password-input-container js-register-v3-input-group">--}}
                            {{--                                                                <div class="js-separator-container js-new-password-container">--}}
                            {{--                                                                    <input--}}
                            {{--                                                                            type="text"--}}
                            {{--                                                                            id="newPassword_1"--}}
                            {{--                                                                            name="newPassword_1"--}}
                            {{--                                                                            inputmode="text"--}}
                            {{--                                                                            pattern="[0-9]*"--}}
                            {{--                                                                            class="-digit-password js-otp-input"--}}
                            {{--                                                                            data-separator-input="true"--}}
                            {{--                                                                            data-type="set_new_password"--}}
                            {{--                                                                            required=""--}}
                            {{--                                                                    />--}}
                            {{--                                                                </div>--}}

                            {{--                                                                <div class="js-separator-container js-new-password-container">--}}
                            {{--                                                                    <input--}}
                            {{--                                                                            type="text"--}}
                            {{--                                                                            id="newPassword_2"--}}
                            {{--                                                                            name="newPassword_2"--}}
                            {{--                                                                            inputmode="text"--}}
                            {{--                                                                            pattern="[0-9]*"--}}
                            {{--                                                                            class="-digit-password js-otp-input"--}}
                            {{--                                                                            data-separator-input="true"--}}
                            {{--                                                                            data-type="set_new_password"--}}
                            {{--                                                                            required=""--}}
                            {{--                                                                    />--}}
                            {{--                                                                </div>--}}

                            {{--                                                                <div class="js-separator-container js-new-password-container">--}}
                            {{--                                                                    <input--}}
                            {{--                                                                            type="text"--}}
                            {{--                                                                            id="newPassword_3"--}}
                            {{--                                                                            name="newPassword_3"--}}
                            {{--                                                                            inputmode="text"--}}
                            {{--                                                                            pattern="[0-9]*"--}}
                            {{--                                                                            class="-digit-password js-otp-input"--}}
                            {{--                                                                            data-separator-input="true"--}}
                            {{--                                                                            data-type="set_new_password"--}}
                            {{--                                                                            required=""--}}
                            {{--                                                                    />--}}
                            {{--                                                                </div>--}}

                            {{--                                                                <div class="js-separator-container js-new-password-container">--}}
                            {{--                                                                    <input--}}
                            {{--                                                                            type="text"--}}
                            {{--                                                                            id="newPassword_4"--}}
                            {{--                                                                            name="newPassword_4"--}}
                            {{--                                                                            inputmode="text"--}}
                            {{--                                                                            pattern="[0-9]*"--}}
                            {{--                                                                            class="-digit-password js-otp-input"--}}
                            {{--                                                                            data-separator-input="true"--}}
                            {{--                                                                            data-type="set_new_password"--}}
                            {{--                                                                            required=""--}}
                            {{--                                                                    />--}}
                            {{--                                                                </div>--}}

                            {{--                                                                <div class="js-separator-container js-new-password-container">--}}
                            {{--                                                                    <input--}}
                            {{--                                                                            type="text"--}}
                            {{--                                                                            id="newPassword_5"--}}
                            {{--                                                                            name="newPassword_5"--}}
                            {{--                                                                            inputmode="text"--}}
                            {{--                                                                            pattern="[0-9]*"--}}
                            {{--                                                                            class="-digit-password js-otp-input"--}}
                            {{--                                                                            data-separator-input="true"--}}
                            {{--                                                                            data-type="set_new_password"--}}
                            {{--                                                                            required=""--}}
                            {{--                                                                    />--}}
                            {{--                                                                </div>--}}

                            {{--                                                                <div class="js-separator-container js-new-password-container">--}}
                            {{--                                                                    <input--}}
                            {{--                                                                            type="text"--}}
                            {{--                                                                            id="newPassword_6"--}}
                            {{--                                                                            name="newPassword_6"--}}
                            {{--                                                                            inputmode="text"--}}
                            {{--                                                                            pattern="[0-9]*"--}}
                            {{--                                                                            class="-digit-password js-otp-input"--}}
                            {{--                                                                            data-separator-input="true"--}}
                            {{--                                                                            data-type="set_new_password"--}}
                            {{--                                                                            required=""--}}
                            {{--                                                                    />--}}
                            {{--                                                                </div>--}}
                            {{--                                                            </div>--}}

                            {{--                                                            <input type="hidden" id="newPassword[first]"--}}
                            {{--                                                                   name="newPassword[first]" pattern="[0-9]*"--}}
                            {{--                                                                   class="form-control mb-3"/>--}}

                            {{--                                                            <div class="d-none js-keypad-number-wrapper">--}}
                            {{--                                                                <div class="x-keypad-number-container">--}}
                            {{--                                                                    <div class="-btn-group-wrapper">--}}
                            {{--                                                                        <button--}}
                            {{--                                                                                class="btn -btn js-keypad-btn -btn-keypad"--}}
                            {{--                                                                                type="button"--}}
                            {{--                                                                                data-key="1"--}}
                            {{--                                                                                data-target="#newPassword_1"--}}
                            {{--                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
                            {{--                                                                        >--}}
                            {{--                                                                            1--}}
                            {{--                                                                        </button>--}}

                            {{--                                                                        <button--}}
                            {{--                                                                                class="btn -btn js-keypad-btn -btn-keypad"--}}
                            {{--                                                                                type="button"--}}
                            {{--                                                                                data-key="2"--}}
                            {{--                                                                                data-target="#newPassword_1"--}}
                            {{--                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
                            {{--                                                                        >--}}
                            {{--                                                                            2--}}
                            {{--                                                                        </button>--}}

                            {{--                                                                        <button--}}
                            {{--                                                                                class="btn -btn js-keypad-btn -btn-keypad"--}}
                            {{--                                                                                type="button"--}}
                            {{--                                                                                data-key="3"--}}
                            {{--                                                                                data-target="#newPassword_1"--}}
                            {{--                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
                            {{--                                                                        >--}}
                            {{--                                                                            3--}}
                            {{--                                                                        </button>--}}

                            {{--                                                                        <button--}}
                            {{--                                                                                class="btn -btn js-keypad-btn -btn-keypad"--}}
                            {{--                                                                                type="button"--}}
                            {{--                                                                                data-key="4"--}}
                            {{--                                                                                data-target="#newPassword_1"--}}
                            {{--                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
                            {{--                                                                        >--}}
                            {{--                                                                            4--}}
                            {{--                                                                        </button>--}}

                            {{--                                                                        <button--}}
                            {{--                                                                                class="btn -btn js-keypad-btn -btn-keypad"--}}
                            {{--                                                                                type="button"--}}
                            {{--                                                                                data-key="5"--}}
                            {{--                                                                                data-target="#newPassword_1"--}}
                            {{--                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
                            {{--                                                                        >--}}
                            {{--                                                                            5--}}
                            {{--                                                                        </button>--}}

                            {{--                                                                        <button--}}
                            {{--                                                                                class="btn -btn js-keypad-btn -btn-keypad"--}}
                            {{--                                                                                type="button"--}}
                            {{--                                                                                data-key="6"--}}
                            {{--                                                                                data-target="#newPassword_1"--}}
                            {{--                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
                            {{--                                                                        >--}}
                            {{--                                                                            6--}}
                            {{--                                                                        </button>--}}

                            {{--                                                                        <button--}}
                            {{--                                                                                class="btn -btn js-keypad-btn -btn-keypad"--}}
                            {{--                                                                                type="button"--}}
                            {{--                                                                                data-key="7"--}}
                            {{--                                                                                data-target="#newPassword_1"--}}
                            {{--                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
                            {{--                                                                        >--}}
                            {{--                                                                            7--}}
                            {{--                                                                        </button>--}}

                            {{--                                                                        <button--}}
                            {{--                                                                                class="btn -btn js-keypad-btn -btn-keypad"--}}
                            {{--                                                                                type="button"--}}
                            {{--                                                                                data-key="8"--}}
                            {{--                                                                                data-target="#newPassword_1"--}}
                            {{--                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
                            {{--                                                                        >--}}
                            {{--                                                                            8--}}
                            {{--                                                                        </button>--}}

                            {{--                                                                        <button--}}
                            {{--                                                                                class="btn -btn js-keypad-btn -btn-keypad"--}}
                            {{--                                                                                type="button"--}}
                            {{--                                                                                data-key="9"--}}
                            {{--                                                                                data-target="#newPassword_1"--}}
                            {{--                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
                            {{--                                                                        >--}}
                            {{--                                                                            9--}}
                            {{--                                                                        </button>--}}

                            {{--                                                                        <div--}}
                            {{--                                                                                class="btn -btn js-keypad-btn -btn-none"--}}
                            {{--                                                                                type="button"--}}
                            {{--                                                                                data-key="none"--}}
                            {{--                                                                                data-target="#newPassword_1"--}}
                            {{--                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
                            {{--                                                                        ></div>--}}

                            {{--                                                                        <button--}}
                            {{--                                                                                class="btn -btn js-keypad-btn -btn-keypad"--}}
                            {{--                                                                                type="button"--}}
                            {{--                                                                                data-key="0"--}}
                            {{--                                                                                data-target="#newPassword_1"--}}
                            {{--                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
                            {{--                                                                        >--}}
                            {{--                                                                            0--}}
                            {{--                                                                        </button>--}}

                            {{--                                                                        <button--}}
                            {{--                                                                                class="btn -btn js-keypad-btn -btn-backspace"--}}
                            {{--                                                                                type="button"--}}
                            {{--                                                                                data-key="backspace"--}}
                            {{--                                                                                data-target="#newPassword_1"--}}
                            {{--                                                                                data-options='{"maxLength":6,"inputContainer":".js-separator-container.js-new-password-container","enabledButtonTarget":".js-new-password-button","targetSubmitForm":"js-change-password-new"}'--}}
                            {{--                                                                        >--}}
                            {{--                                                                            <i class="fas fa-backspace"></i>--}}
                            {{--                                                                        </button>--}}
                            {{--                                                                    </div>--}}
                            {{--                                                                </div>--}}
                            {{--                                                            </div>--}}

                            {{--                                                            <div class="text-center">--}}
                            {{--                                                                <button type="submit"--}}
                            {{--                                                                        class="btn -submit btn-primary my-lg-3 mt-0 js-new-password-button">--}}
                            {{--                                                                    ยืนยัน--}}
                            {{--                                                                </button>--}}
                            {{--                                                            </div>--}}
                            {{--                                                        </div>--}}
                            {{--                                                    </div>--}}
                            {{--                                                    <div class="-bottom"></div>--}}
                            {{--                                                </div>--}}
                            {{--                                            </div>--}}
                            {{--                                        </div>--}}
                            {{--                                    </div>--}}
                            {{--                                </div>--}}

                            {{--                                <div class="tab-pane" id="tab-content-resultChangePasswordSuccess"--}}
                            {{--                                     data-completed-dismiss-modal="#changePasswordModal">--}}
                            {{--                                    <div class="js-change-password-success-container">--}}
                            {{--                                        <div class="x-modal-body-base -v3 x-form-register-v3">--}}
                            {{--                                            <div class="row -register-container-wrapper">--}}
                            {{--                                                <div data-animatable="fadeInRegister" data-offset="0"--}}
                            {{--                                                     class="col animated fadeInRegister">--}}
                            {{--                                                    <div class="text-center d-flex flex-column justify-content-center h-100">--}}
                            {{--                                                        <div class="text-center">--}}
                            {{--                                                            <img alt="สมัครสมาชิก SUCCESS"--}}
                            {{--                                                                 class="js-ic-success -success-ic img-fluid" width="150"--}}
                            {{--                                                                 height="150"--}}
                            {{--                                                                 src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/animated-register-success.png"/>--}}
                            {{--                                                        </div>--}}

                            {{--                                                        <div class="-title">เปลี่ยนรหัสผ่านสำเร็จ!</div>--}}
                            {{--                                                        <div class="-sub-title">ระบบกำลังพาคุณไปหน้าหลัก</div>--}}
                            {{--                                                    </div>--}}
                            {{--                                                </div>--}}
                            {{--                                            </div>--}}
                            {{--                                        </div>--}}
                            {{--                                    </div>--}}
                            {{--                                </div>--}}
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{--<div--}}
{{--    class="x-modal modal -v2 -with-more-than-half-size"--}}
{{--    id="promotionReturnByUserModalMobile"--}}
{{--    data="promotion-return?isMobileView=1"--}}
{{--    data-container="#promotionReturnByUserModalMobile"--}}
{{-->--}}
{{--    <div--}}
{{--        class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-mobile -no-fixed-button"--}}
{{--        role="document">--}}
{{--        <div class="modal-content -modal-content">--}}
{{--            <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">--}}
{{--                <i class="fas fa-times"></i>--}}
{{--            </button>--}}

{{--            <div class="modal-header -modal-header">--}}
{{--                <div class="x-modal-mobile-header">--}}
{{--                    <div class="-header-mobile-container">--}}
{{--                        <h3 class="x-title-modal text-center mx-auto">--}}
{{--                            รับคืนยอดเสีย--}}
{{--                        </h3>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="modal-body -modal-body">--}}
{{--                <div class="x-promotion-return-by-user-container animated fadeInUp" data-animatable="fadeInUp">--}}
{{--                    <div class="-group-round-container -no-data">--}}
{{--                        <div class="-date-range-container text-center">--}}
{{--                            ยอดโบนัสระหว่างวันที่ 18 - 24 ก.ย. 2023--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="text-center">--}}
{{--                        <button type="button" disabled="" class="btn btn-primary -promotion-return-btn">--}}
{{--                            <span class="-text-btn">ไม่เข้าเงื่อนไข</span>--}}
{{--                        </button>--}}
{{--                    </div>--}}

{{--                    <div class="-description-container">--}}
{{--                        <div>--}}
{{--                            คุณไม่เข้าเงื่อนไขการรับโบนัส--}}
{{--                        </div>--}}
{{--                        <div><span class="-text-description">โปรดอ่านเงื่อนไขการเข้าร่วม</span>ด้านล่างค่ะ</div>--}}
{{--                    </div>--}}

{{--                    <div class="-condition-container">--}}
{{--                        <div class="-condition-title"><u>โปรดอ่านเงื่อนไข</u></div>--}}
{{--                        <div class="x-promotion-content">--}}
{{--                            <p>--}}
{{--                                <big><strong>เล่นเสียให้คืน 5% ทุกสัปดาห์</strong></big><br/>--}}
{{--                                ► รับโบนัสทุกวันจันทร์ 1 ครั้ง / สัปดาห์ (ตัดรอบ อังคาร 00:00 ถึง 23:59--}}
{{--                                วันจันทร์)<br/>--}}
{{--                                ► ต้องมียอดเทิร์นโอเวอร์ 5 เท่าของเงินฝากภายในสัปดาห์ (NET Tureover)<br/>--}}
{{--                                ► โบนัสจะได้รับทุกวันจันทร์สามารถกดรับได้ที่หน้าเว็บ<br/>--}}
{{--                                ► เพียงมียอดเล่น 50% ของโบนัสที่ได้รับสามารถถอนได้เลย<br/>--}}
{{--                                ► ต้องมียอดเสียมากกว่า 2000 บาทต่อสัปดาห์จึงจะได้รับยอด 5%<br/>--}}
{{--                                ► หลังจากรับโปรโมชั่นเครดิตมีอายุการใช้งาน 3--}}
{{--                                วันหลังจากนั้นเครดิตคืนยอดเสียจะถูกปรับเป็น 0<br/>--}}
{{--                                <a href="/term-and-condition">เงื่อนไขและกติกาพื้นฐานจะถูกนำมาใช้กับโปรโมชั่นนี้</a>--}}
{{--                            </p>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="my-3">--}}
{{--                        <div class="x-admin-contact -no-fixed">--}}
{{--                        <span class="x-text-with-link-component">--}}
{{--                            <label class="-text-message">ติดปัญหา</label>--}}
{{--                            <a href="{{ $config->linelink }}" class="-link-message" target="_blank" rel="noopener">--}}
{{--                                <u>ติดต่อฝ่ายบริการลูกค้า</u>--}}
{{--                            </a>--}}
{{--                        </span>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}

{{--</div>--}}


<div class="x-modal modal -v2 x-modal-promotion-alert -with-half-size" id="promotionAlertModal" tabindex="-1"
     role="dialog" aria-hidden="true" data-loading-container=".js-modal-content"
     data-ajax-modal-always-reload="true">
    <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable modal-dialog-centered"
         role="document">
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
    <div
            class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-mobile -no-fixed-button"
            role="document">
        <div class="modal-content -modal-content">
            <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>

            <div class="modal-header -modal-header">
                <div class="x-modal-mobile-header">
                    <div class="-header-mobile-container">
                        <h3 class="x-title-modal text-center mx-auto">
                            {{ __('app.home.history') }}
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
                                <div class="x-admin-contact text-center">
                                    @if(request()->routeIs('customer.credit.*'))
                                        <div class="btn-group" role="group" aria-label="Basic example">

                                            <button type="button" class="btn btn-secondary"
                                                    onclick="LoadHistory2('withdraw')">
                                                {{ __('app.home.withdraw') }}
                                            </button>

                                        </div>
                                    @else
                                        <div class="btn-group" role="group" aria-label="Basic example">
                                            <button type="button" class="btn btn-secondary"
                                                    onclick="LoadHistory('deposit')">
                                                {{ __('app.home.refill') }}
                                            </button>
                                            <button type="button" class="btn btn-secondary"
                                                    onclick="LoadHistory('withdraw')">
                                                {{ __('app.home.withdraw') }}
                                            </button>
                                            {{--                                            <button type="button" class="btn btn-secondary"--}}
                                            {{--                                                    onclick="LoadHistory('transfer')">--}}
                                            {{--                                                โยก--}}
                                            {{--                                            </button>--}}
                                         
                                            @if($config->freecredit_open == 'N')
                                                @if($config->wheel_open == 'Y')
                                                    <button type="button" class="btn btn-secondary"
                                                            onclick="LoadHistory('spin')">
                                                        {{ __('app.home.wheel') }}
                                                    </button>
                                                @endif
                                                <button type="button" class="btn btn-secondary"
                                                        onclick="LoadHistory('cashback')">
                                                    {{ __('app.home.cashback') }}
                                                </button>
                                                <button type="button" class="btn btn-secondary"
                                                        onclick="LoadHistory('memberic')">
                                                    {{ __('app.home.ic') }}
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>


                                <div
                                        class="wg-container wg-container__wg_bill_history wg--loaded"
                                        data-widget-name="wg_bill_history"
                                        data-widget-options='{"script_path":null,"style_path":null,"image_path":null,"visibility":"away","visibility_offset":"100%","render_url":"\/_widget","render_method":"GET","attr_style":null,"attr_class":null,"scroll_position":"current","options":{},"callback":{},"mode":"clear","mask_mode":"over","mask_style":"wg-loading","limit":20,"page":1,"template":"@Base\/Widget\/billHistory.html.twig","name":"wg_bill_history"}'
                                        data-widget-user-options='{"page":1}'
                                >
                                    <div class="wg-content">
                                        <div class="table-responsive">
                                        <table class="table table-borderless table-striped=">
                                            <tbody class="historydata">


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
        </div>
    </div>
</div>

<template id="history-theme" style="display:none">
    <tr>
        <td class="-description-body-wrapper">
            <div class="-title-wrapper">
                <span class="-title">{method} {billid}</span>
            </div>
            <div class="-state-wrapper">
                <span class="-state-text">{{ __('app.status.index') }} : </span>

                <img loading="lazy" fetchpriority="low"
                     src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/{image}.png"
                     class="-ic" alt="State icon image"/>

                <span class="-state-title">{status}</span>
                <span class="-state-title -short">{status}</span>
            </div>
        </td>
        <td class="-transaction-body-wrapper">
            <div class="-amount -deposit">{amount}</div>
            <div class="-datetime">{datetime}</div>
        </td>
    </tr>
</template>

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

<script>


</script>
