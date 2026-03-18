<nav class="x-header js-header-selector navbar navbar-expand-lg -anon">
    <div class="container-fluid -inner-container">
        <div class="">
            <button type="button" class="btn bg-transparent p-0 x-hamburger" data-toggle="modal"
                    data-target="#themeSwitcherModal">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <div id="headerBrand">
            <a class="navbar-brand" href="{{ route('customer.home.index') }}">
                <img alt="{{ $config->description }}" class="-logo -default img-fluid" width="440"
                     height="104" src="{{ url(core()->imgurl($config->logo,'img')) }}"/>
                <img alt="{{ $config->description }}" class="-logo -invert img-fluid" width="440"
                     height="104" src="{{ url(core()->imgurl($config->logo,'img')) }}"/>
            </a>
        </div>

        <div class="x-menu">
            <div class="-menu-container">

{{--                <a class="-menu-link" href="{{ route('customer.cats.list', ['id' => 'slot']) }}">--}}
{{--                    <img alt="{{ $config->description }}" class="-icon img-fluid" width="40" height="40"--}}
{{--                         src="{{ url('assets/wm356/web/ezl-wm-356/img/ic-menu-slot.png?v=2') }}">--}}
{{--                    <img alt="{{ $config->description }}" class="-icon -active img-fluid" width="40" height="40"--}}
{{--                         src="{{ url('assets/wm356/web/ezl-wm-356/img/ic-menu-slot-active.png?v=2') }}">--}}
{{--                    <span>{{ __('app.home.slot') }}</span>--}}
{{--                </a>--}}

{{--                <a class="-menu-link" href="{{ route('customer.cats.list', ['id' => 'casino']) }}">--}}
{{--                    <img alt="{{ $config->description }}" class="-icon img-fluid" width="40" height="40"--}}
{{--                         src="{{ url('assets/wm356/web/ezl-wm-356/img/ic-menu-casino.png?v=2') }}">--}}
{{--                    <img alt="{{ $config->description }}" class="-icon -active img-fluid" width="40" height="40"--}}
{{--                         src="{{ url('assets/wm356/web/ezl-wm-356/img/ic-menu-casino-active.png?v=2') }}">--}}
{{--                    <span>{{ __('app.home.casino') }}</span>--}}
{{--                </a>--}}

{{--                <a class="-menu-link" href="{{ route('customer.cats.list', ['id' => 'sport']) }}">--}}
{{--                    <img alt="{{ $config->description }}" class="-icon img-fluid" width="40" height="40"--}}
{{--                         src="{{ url('assets/wm356/web/ezl-wm-356/img/ic-menu-sport.png?v=2') }}">--}}
{{--                    <img alt="{{ $config->description }}" class="-icon -active img-fluid" width="40" height="40"--}}
{{--                         src="{{ url('assets/wm356/web/ezl-wm-356/img/ic-menu-sport-active.png?v=2') }}">--}}
{{--                    <span>{{ __('app.home.sport') }}</span>--}}
{{--                </a>--}}
                <a class="-menu-link" href="{{ route('customer.promotion.index') }}">
                    <img alt="{{ $config->description }}" class="-icon img-fluid" width="40"
                         height="40" src="{{ url('assets/wm356/web/ezl-wm-356/img/ic-menu-promotion.png?v=2') }}"/>
                    <img alt="{{ $config->description }}" class="-icon -active img-fluid"
                         width="40" height="40"
                         src="{{ url('assets/wm356/web/ezl-wm-356/img/ic-menu-promotion-active.png?v=2') }}"/>
                    <span>{{ __('app.login.promotion') }}</span>
                </a>
            </div>

        </div>



        <div id="headerContent">
            <div class="x-logged">
                <a href="{{ $config->linelink }}" class="x-header-btn-support -in-anon" target="_blank"
                   rel="noreferrer nofollow">
                    <picture>
                        <source type="image/webp" srcset="/assets/wm356/web/ezl-wm-356/img/ic-line-support.webp?v=1"/>
                        <source type="image/png?v=2" srcset="/assets/wm356/web/ezl-wm-356/img/ic-line-support.png?v=1"/>
                        <img alt="{{ $config->description }}" class="img-fluid -ic"
                             width="120" height="39" src="/assets/wm356/web/ezl-wm-356/img/ic-line-support.png?v=1"/>
                    </picture>
                    <picture>
                        <source type="image/webp"
                                srcset="/assets/wm356/web/ezl-wm-356/img/ic-line-support-mobile.webp?v=2"/>
                        <source type="image/png?v=2"
                                srcset="/assets/wm356/web/ezl-wm-356/img/ic-line-support-mobile.png?v=2"/>
                        <img alt="{{ $config->description }}" class="img-fluid -ic -mobile"
                             width="28" height="28"
                             src="/assets/wm356/web/ezl-wm-356/img/ic-line-support-mobile.png?v=2"/>
                    </picture>
                </a>

                <div class="-transaction-outer-container">
                    @if(!request()->routeIs('customer.credit.*'))
                        <div class="-deposit-container d-none d-xl-block">
                            <a href="#deposit" class="text-white js-account-approve-aware btn -btn-deposit"
                               data-toggle="modal" data-target="#depositModal">
                                <div class="f-7">{{ __('app.home.refill') }}</div>
                            </a>
                        </div>
                    @endif
                    <div class="-withdraw-container d-none d-xl-block">
                        <a href="#withdraw" class="text-white js-account-approve-aware btn -btn-withdraw"
                           data-toggle="modal" data-target="#withdrawModal">
                            <div class="f-7">{{ __('app.home.withdraw') }}</div>
                        </a>
                    </div>
                </div>

                <div class="-profile-outer-container">
                    <div class="-balance-container">
                        <div class="-text-username">
                            {{ $userdata->user_name }}
                        </div>

                        <div class="-user-balance js-user-balance f-sm-6 f-7">
                            <div class="-inner-box-wrapper">
                                @if(request()->routeIs('customer.credit.*'))
                                    <span id="customer-balance" class="js-customer-balance">
                                            <span
                                                class="text-green-lighter wallet_amount">{{ $userdata->balance_free }}</span>
                                        </span>
                                    <button
                                        type="button"
                                        id="btn-customer-balance-reload"
                                        class="-btn-balance -btn-balance-free -in-box btnbalance"

                                    >
                                        <i class="fas fa-sync-alt f-9"></i>
                                    </button>
                                @else
                                    <span id="customer-balance" class="js-customer-balance">
                                            <span
                                                class="text-green-lighter wallet_amount">{{ $userdata->balance }}</span>
                                        </span>
                                    <button
                                        type="button"
                                        id="btn-customer-balance-reload"
                                        class="-btn-balance -btn-balance-normal -in-box btnbalance"

                                    >
                                        <i class="fas fa-sync-alt f-9"></i>
                                    </button>
                                @endif

                            </div>
                        </div>
                    </div>

                    <div class="-balance-container">

                        <div class="-user-balance js-user-balance f-sm-6 f-7">
                            <div class="-inner-box-wrapper">
                                <span id="customer-diamond" class="js-customer-balance">
                                    <i class="fas fa-gem"></i>
                                            <span class="text-green-lighter"
                                                  id="diamond_amount">{{ number_format($userdata->diamond,0) }}</span>
                                        </span>

                            </div>
                        </div>
                    </div>


                    <div class="-profile-container">
                        <a href="#account" data-toggle="modal" data-target="#accountModal" class="-btn-wrapper">
                            <div class="x-profile-image">
                                <img class="img-fluid -profile-image"
                                     src="/images/icon/4f037d726e06fc63eb4c615fd98558f6.png?v=1"
                                     alt="customer image"/>
                            </div>
                        </a>
                        <div class="d-xl-none d-block">
                            <div class="js-ez-logged-sidebar">
                                <div class="x-profile-image" id="mobilebtn">
                                    <img class="img-fluid -profile-image"
                                         src="/images/icon/4f037d726e06fc63eb4c615fd98558f6.png?v=1"
                                         alt="customer image"/>
                                </div>
                            </div>

                            <div class="x-menu-account-list-sidebar">
                                <div class="x-modal-account-menu-mobile">
                                    <div class="-modal-profile-mobile -default d-block d-xl-none">
                                        <div class="text-right">
                                            <i class="fas fa-times f-5 js-close-account-sidebar"></i>
                                        </div>

                                        <div class="x-profile-image">
                                            <img class="img-fluid -profile-image"
                                                 src="/images/icon/4f037d726e06fc63eb4c615fd98558f6.png?v=1"
                                                 alt="customer image"/>
                                        </div>

                                        <div class="-balance-container">
                                            <div class="-text-username">
                                                {{ $userdata->user_name }}
                                            </div>

                                            <div class="-user-balance js-user-balance f-sm-6 f-7">
                                                <div class="-inner-box-wrapper">
                                                    <img alt="หวยออนไลน์ แทงหวยออนไลน์" class="-ic img-fluid"
                                                         width="26"
                                                         height="26"
                                                         src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-coin.png"/>
                                                    @if(request()->routeIs('customer.credit.*'))
                                                        <span id="customer-balance" class="js-customer-balance">
                                                                <span
                                                                    class="text-green-lighter">{{ $userdata->balance_free }}</span>
                                                            </span>
                                                    @else
                                                        <span id="customer-balance" class="js-customer-balance">
                                                                <span
                                                                    class="text-green-lighter wallet_amount">{{ $userdata->balance }}</span>
                                                            </span>
                                                    @endif
                                                    <button
                                                        type="button"
                                                        id="btn-customer-balance-reload"
                                                        class="-btn-balance -btn-balance-normal -in-box"
                                                    >
                                                        <i class="fas fa-sync-alt f-9"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="-transaction-button-wrapper">

                                        <a
                                            class="x-transaction-button-v2 -in-sidebar -deposit js-close-account-sidebar x-bg-position-center lazyloaded"
                                            href="javascript:void(0)"
                                            data-toggle="modal"
                                            data-target="#depositModal"
                                            data-bgset="/assets/wm356/images/btn-deposit-bg.png?v=2"
                                            style="background-image: url('/assets/wm356/images/btn-deposit-bg.png?v=2');"
                                        >
                                            <img alt="ฝากเงิน เงินฝาก หวยออนไลน์ แทงหวยออนไลน์"
                                                 class="img-fluid -icon"
                                                 width="35" height="35"
                                                 src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.png"/>

                                            <div class="-text-wrapper">
                                                <span class="-title">{{ __('app.home.refill') }}</span>
                                                <span class="-sub-title">Deposit</span>
                                            </div>
                                        </a>

                                        <a
                                            class="x-transaction-button-v2 -in-sidebar -withdraw js-close-account-sidebar x-bg-position-center lazyloaded"
                                            href="javascript:void(0)"
                                            data-toggle="modal"
                                            data-target="#withdrawModal"
                                            data-bgset="/assets/wm356/images/btn-withdraw-bg.png?v=2"
                                            style="background-image: url('/assets/wm356/images/btn-withdraw-bg.png?v=2');"
                                        >
                                            <img alt="ถอนเงิน ยอดถอน หวยออนไลน์ แทงหวยออนไลน์"
                                                 class="img-fluid -icon"
                                                 width="35" height="35"
                                                 src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.png"/>

                                            <div class="-text-wrapper">
                                                <span class="-title">{{ __('app.home.withdraw') }}</span>
                                                <span class="-sub-title">Withdraw</span>
                                            </div>
                                        </a>
                                    </div>

                                    <ul class="navbar-nav">
                                        <li class="nav-item -account-profile">
                                            <button type="button"
                                                    class="nav-link js-close-account-sidebar -account-profile"
                                                    data-toggle="modal" data-target="#accountModalMobile">
                                                <img
                                                    alt="ข้อมูลบัญชี หวยออนไลน์ แทงหวยออนไลน์"
                                                    class="img-fluid -icon-image"
                                                    width="35"
                                                    height="35"
                                                    src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-user.png"
                                                />
                                                <span class="-text-menu">{{ __('app.home.profile') }}</span>
                                            </button>
                                        </li>

                                        <li class="nav-item -account-bill-history">
                                            <button type="button"
                                                    class="nav-link js-close-account-sidebar -account-bill-history"
                                                    data-toggle="modal" data-target="#billHistoryModalMobile">
                                                <img
                                                    alt="ประวัติ หวยออนไลน์ แทงหวยออนไลน์"
                                                    class="img-fluid -icon-image"
                                                    width="35"
                                                    height="35"
                                                    src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-bill-history.png"
                                                />
                                                <span class="-text-menu">{{ __('app.home.history') }}</span>
                                            </button>
                                        </li>
                                        <li class="nav-item -promotion-return-by-user">
                                            <a href="{{ route('customer.contributor.index') }}" class="nav-link">

                                                <img alt="โบนัสเพิ่ม ทุกสัปดาห์ หวยออนไลน์ แทงหวยออนไลน์"
                                                     class="img-fluid -icon-image" width="35" height="35"
                                                     src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-provider.png"/>
                                                <span class="-text-menu">{{ __('app.home.suggest') }}</span>
                                            </a>
                                        </li>
                                        @if($config->wheel_open === 'Y')
                                            <li class="nav-item -promotion-return-by-user">
                                                <a href="{{ route('customer.spin.index') }}" class="nav-link">

                                                    <img alt="โบนัสเพิ่ม ทุกสัปดาห์ หวยออนไลน์ แทงหวยออนไลน์"
                                                         class="img-fluid -icon-image" width="35" height="35"
                                                         src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-bonus.png"/>
                                                    <span class="-text-menu">{{ __('app.home.wheel') }}</span>
                                                </a>
                                            </li>
                                        @endif
                                        @if(request()->routeIs('customer.credit.*'))
                                            <li class="nav-item -coupon js-account-approve-aware">
                                                <button type="button"
                                                        class="nav-link js-close-account-sidebar -coupon js-account-approve-aware"
                                                        data-toggle="modal" data-target="#bonusModalMobile">
                                                    <img
                                                        alt="ใช้คูปอง หวยออนไลน์ แทงหวยออนไลน์"
                                                        class="img-fluid -icon-image"
                                                        width="35"
                                                        height="35"
                                                        src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-coupon.png"
                                                    />
                                                    <span class="-text-menu">{{ __('app.home.get_bonus') }}</span>
                                                </button>
                                            </li>
                                        @else
                                            @if($config->freecredit_open === 'N')
                                                <li class="nav-item -coupon js-account-approve-aware">
                                                    <button type="button"
                                                            class="nav-link js-close-account-sidebar -coupon js-account-approve-aware"
                                                            data-toggle="modal" data-target="#bonusModalMobile">
                                                        <img
                                                            alt="ใช้คูปอง หวยออนไลน์ แทงหวยออนไลน์"
                                                            class="img-fluid -icon-image"
                                                            width="35"
                                                            height="35"
                                                            src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-coupon.png"
                                                        />
                                                        <span class="-text-menu">{{ __('app.home.get_bonus') }}</span>
                                                    </button>
                                                </li>
                                            @endif
                                        @endif
                                        <li class="nav-item -coupon js-account-approve-aware">
                                            <button type="button"
                                                    class="nav-link js-close-account-sidebar -coupon js-account-approve-aware"
                                                    data-toggle="modal" data-target="#couponModalMobile">
                                                <img
                                                    alt="ใช้คูปอง หวยออนไลน์ แทงหวยออนไลน์"
                                                    class="img-fluid -icon-image"
                                                    width="35"
                                                    height="35"
                                                    src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-coupon.png"
                                                />
                                                <span class="-text-menu">{{ __('app.home.coupon') }}</span>
                                            </button>
                                        </li>

                                        @if(request()->routeIs('customer.credit.*'))
                                            <li class="nav-item -promotion-return-by-user">
                                                <a class="nav-link" href="{{ route('customer.home.index') }}">
                                                    <img
                                                        alt="โบนัสเพิ่ม ทุกสัปดาห์ หวยออนไลน์ แทงหวยออนไลน์"
                                                        class="img-fluid -icon-image"
                                                        width="35"
                                                        height="35"
                                                        src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-bonus.png"
                                                    />
                                                    <span class="-text-menu">{{ __('app.home.credit') }}</span>
                                                </a>
                                            </li>
                                        @else
                                            @if($config->freecredit_open === 'Y')
                                                <li class="nav-item -promotion-return-by-user">
                                                    <a class="nav-link" href="{{ route('customer.credit.index') }}">
                                                        <img
                                                            alt="โบนัสเพิ่ม ทุกสัปดาห์ หวยออนไลน์ แทงหวยออนไลน์"
                                                            class="img-fluid -icon-image"
                                                            width="35"
                                                            height="35"
                                                            src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-bonus.png"
                                                        />
                                                        <span class="-text-menu">{{ __('app.home.freecredit') }}</span>
                                                    </a>
                                                </li>
                                            @endif
                                        @endif

                                        <li class="nav-item -logout">
                                            <a class="nav-link js-require-confirm -logout"
                                               href="{{ route('customer.session.destroy') }}"
                                               data-title="ต้องการออกจากระบบ ?">
                                                <img
                                                    alt="ออกจากระบบ หวยออนไลน์ แทงหวยออนไลน์"
                                                    class="img-fluid -icon-image"
                                                    width="35"
                                                    height="35"
                                                    src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-menu-logout.png"
                                                />
                                                <span class="-text-menu">{{ __('app.home.logout') }}</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <div class="-overlay"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
@if(isset($notice[Route::currentRouteName()]['route']) === true)
<nav class="js-header-selector navbar navbar-expand-lg -anon newsboxhead" data-delat="200">

    &nbsp;&nbsp; <span>{{ $notice[Route::currentRouteName()]['msg'] }}</span>
</nav>
@endif
