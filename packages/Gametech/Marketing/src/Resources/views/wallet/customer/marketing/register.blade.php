{{-- extend layout --}}
@extends('wallet::layouts.marketing')

{{-- page title --}}
@section('title','')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        .x-register-tab-container2{
            /* ลบ duplicate: มี max-width / border-radius ซ้ำ */
            position: relative;
            width: 100%;
            max-width: 1100px;
            padding: 30px;
            margin: 20px auto 20px;
            border: 2px solid #ffe083;
            background: rgba(10,14,37,.5882352941);
            border-radius: 10px;
        }

        .input-wrapper{ position: relative; width: 100%; }
        .input-password{
            width: 100%;
            padding: 10px 40px 10px 10px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .toggle-icon{
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            width: 20px; height: 20px;
        }

        /* จัดการ autofill บน Chrome */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active{
            transition: background-color 9999s ease-in-out 0s;
            -webkit-text-fill-color: #fff !important;
            box-shadow: 0 0 0 1000px #000 inset !important;
        }
    </style>

    <style>
        :root{
            --in-height: 56px;           /* ความสูงอินพุต/Select เดียวกันทั้งระบบ */
            --in-radius: 28px;

            --in-bg: #0a0d14;
            --in-border: #2a2f3a;
            --in-text: #e6e9ee;
            --in-muted: #9aa3ad;

            --in-elev: #10131a;
            --in-hover: #182030;
            --in-selected: #0f172a;
            --in-accent: #3b82f6;
        }

        /* กรณีต้องการบังคับใหญ่ขึ้นผ่านคลาส s2-lg */
        .s2-lg .select2-selection--single{ height: var(--in-height); }

        /* โทนหลักของ select2 (ธีมกำหนดเอง) */
        .select2-container--gt-dark .select2-selection--single{
            background: var(--in-bg);
            border: 1px solid var(--in-border);
            color: var(--in-text);
            border-radius: var(--in-radius);
            display: flex;
            align-items: center;
            height: var(--in-height);             /* ใช้ตัวแปร ไม่ทับด้วย 50px */
        }
        .select2-container--gt-dark .select2-selection__rendered{
            color: var(--in-text);
            line-height: calc(var(--in-height) - 2px);
            padding-inline: 16px 40px;
            font-size: 14px;

        }
        .select2-container--gt-dark .select2-selection__placeholder{ color: var(--in-muted); }
        .select2-container--gt-dark .select2-selection__arrow{ right: 12px; height: 100%; }
        .select2-container--gt-dark .select2-selection__arrow b{
            border-width: 6px 5px 0 5px;
            border-color: var(--in-muted) transparent transparent transparent;
        }

        /* โฟกัส */
        .select2-container--gt-dark.select2-container--focus .select2-selection--single{
            border-color: var(--in-accent);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--in-accent) 24%, transparent);
            outline: 0;
        }

        /* ดรอปดาวน์ */
        .select2-container--gt-dark .select2-dropdown{
            background: var(--in-elev);
            border: 1px solid var(--in-border);
            color: var(--in-text);
            border-radius: 14px;
            overflow: hidden;
            max-height: 60vh;
            display: flex;
            flex-direction: column;
            z-index: 10000; /* กันโดนทับ */
        }
        .select2-container--gt-dark .select2-search--dropdown{
            background: var(--in-elev);
            border-bottom: 1px solid var(--in-border);
            padding: 8px;
        }
        .select2-container--gt-dark .select2-search__field{
            background: var(--in-bg);
            border: 1px solid var(--in-border);
            color: var(--in-text);
            border-radius: 10px;
            padding: 8px 10px;
        }
        .select2-container--gt-dark .select2-search__field::placeholder{ color: var(--in-muted); }

        /* ให้ “รายการ” เป็นตัวเลื่อน ไม่ปล่อยให้ล้นทั้ง dropdown */
        .select2-container--gt-dark .select2-results{ flex: 1 1 auto; min-height: 0; }
        .select2-container--gt-dark .select2-results__options{
            max-height: min(65vh, 420px) !important; /* จำกัดสูงสุดตามจอ */
            /*overflow-y: auto !important;*/
            overscroll-behavior: contain;            /* กันทะลุไปเลื่อนพื้นหลัง */
            -webkit-overflow-scrolling: touch;       /* โมบายลื่นมือ */
        }
        /* กันบางธีมตั้ง overflow แปลก ๆ */
        .select2-container--gt-dark .select2-results{ max-height: none !important; overflow-y: auto; }

        /* กันโดนทับเวลาอยู่ในโมดัล/คอนเทนเนอร์ซ้อน */
        .select2-container--open{ z-index: 10000 !important; }
        .select2-container--gt-dark .select2-dropdown{ z-index: 10000 !important; }
    </style>
@endpush

@section('content')
    <div id="main__content" data-bgset="/assets/wm356/images/index-bg.jpg?v=2"
         class="lazyload x-bg-position-center x-bg-index lazyload">

        <div class="x-index-content-main-container -anon">


            @include('wallet::customer.marketing.normal')


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

                                        <div class="col">
                                            <div class="-fake-inner-body">
                                                {{--                                                    <form method="post" data-register-v3-form="v3/check-for-login"--}}
                                                {{--                                                          data-register-step="loginPhoneNumber">--}}
                                                <form method="POST"
                                                      action="{{ route('customer.session.create') }}"
                                                      @submit.prevent="onSubmit">
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
                                                        <button
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
    {{--	<div class="x-modal modal -v2 x-theme-switcher-v2" id="themeSwitcherModal" tabindex="-1" role="dialog"--}}
    {{--	     aria-hidden="true" data-loading-container=".js-modal-content" data-ajax-modal-always-reload="true">--}}
    {{--		<div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable modal-dialog-centered"--}}
    {{--		     role="document">--}}
    {{--			<div class="modal-content -modal-content">--}}
    {{--				<button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">--}}
    {{--					<i class="fas fa-times"></i>--}}
    {{--				</button>--}}
    {{--				<div class="modal-body -modal-body">--}}
    {{--					<div class="-theme-switcher-container">--}}
    {{--						<div class="-inner-header-section">--}}
    {{--							<a class="-link-wrapper" href="{{ route('customer.home.index') }}">--}}
    {{--								<picture>--}}
    {{--									<source type="image/webp"--}}
    {{--									        data-srcset="{{ url(core()->imgurl($webconfig->logo,'img')) }}"/>--}}
    {{--									<source type="image/png?v=2"--}}
    {{--									        data-srcset="{{ url(core()->imgurl($webconfig->logo,'img')) }}"/>--}}
    {{--									<img--}}
    {{--											alt="logo image"--}}
    {{--											class="img-fluid lazyload -logo lazyload"--}}
    {{--											width="180"--}}
    {{--											height="42"--}}
    {{--											data-src="{{ url(core()->imgurl($webconfig->logo,'img')) }}"--}}
    {{--											src="{{ url(core()->imgurl($webconfig->logo,'img')) }}"--}}
    {{--									/>--}}
    {{--								</picture>--}}
    {{--							</a>--}}
    {{--						</div>--}}
    {{--						--}}
    {{--						<div class="-inner-top-body-section">--}}
    {{--							<div class="col-6 -wrapper-box">--}}
    {{--								<a--}}
    {{--										class="btn -btn-item -top-btn -register-button lazyload x-bg-position-center"--}}
    {{--										href="{{ route('customer.session.store') }}"--}}
    {{--										data-bgset="/assets/wm356/images/btn-register-login-bg.png?v=2"--}}
    {{--										style="background-image: url('/assets/wm356/images/btn-register-login-bg.png?v=2');"--}}
    {{--								>--}}
    {{--									<picture>--}}
    {{--										<source type="image/webp"--}}
    {{--										        data-srcset="/assets/wm356/images/ic-modal-menu-register.webp?v=2"/>--}}
    {{--										<source type="image/png?v=2"--}}
    {{--										        data-srcset="/assets/wm356/images/ic-modal-menu-register.png?v=2"/>--}}
    {{--										<img--}}
    {{--												alt="รูปไอคอนสมัครสมาชิก"--}}
    {{--												class="img-fluid -icon-image lazyload"--}}
    {{--												width="50"--}}
    {{--												height="50"--}}
    {{--												data-src="/assets/wm356/images/ic-modal-menu-register.png?v=2"--}}
    {{--												src="/assets/wm356/images/ic-modal-menu-register.png?v=2"--}}
    {{--										/>--}}
    {{--									</picture>--}}
    {{--									--}}
    {{--									<div class="-typo-wrapper">--}}
    {{--										<div class="-typo">สมัครเลย</div>--}}
    {{--									</div>--}}
    {{--								</a>--}}
    {{--							</div>--}}
    {{--							<div class="col-6 -wrapper-box">--}}
    {{--								<button--}}
    {{--										type="button"--}}
    {{--										class="btn -btn-item -top-btn -login-btn lazyload x-bg-position-center"--}}
    {{--										data-toggle="modal"--}}
    {{--										data-dismiss="modal"--}}
    {{--										data-target="#loginModal"--}}
    {{--										data-bgset="/assets/wm356/images/btn-register-login-bg.png?v=2"--}}
    {{--										style="background-image: url('/assets/wm356/images/btn-register-login-bg.png?v=2');"--}}
    {{--								>--}}
    {{--									<picture>--}}
    {{--										<source type="image/webp"--}}
    {{--										        data-srcset="/assets/wm356/images/ic-modal-menu-login.webp?v=2"--}}
    {{--										        srcset="/assets/wm356/images/ic-modal-menu-login.webp?v=2">--}}
    {{--										<source type="image/png?v=2"--}}
    {{--										        data-srcset="/assets/wm356/images/ic-modal-menu-login.png?v=2"--}}
    {{--										        srcset="/assets/wm356/images/ic-modal-menu-login.png?v=2">--}}
    {{--										<img alt="รูปไอคอนเข้าสู่ระบบ" class="img-fluid -icon-image lazyloaded"--}}
    {{--										     width="50" height="50"--}}
    {{--										     data-src="/assets/wm356/images/ic-modal-menu-login.png?v=2"--}}
    {{--										     src="/assets/wm356/images/ic-modal-menu-login.png?v=2">--}}
    {{--									</picture>--}}
    {{--									--}}
    {{--									<div class="-typo-wrapper">--}}
    {{--										<div class="-typo">เข้าสู่ระบบ</div>--}}
    {{--									</div>--}}
    {{--								</button>--}}
    {{--							</div>--}}
    {{--						</div>--}}
    {{--						--}}
    {{--						<div class="-inner-center-body-section">--}}
    {{--							<div class="col-6 -wrapper-box">--}}
    {{--								<a--}}
    {{--										href="{{ route('customer.promotion.show') }}"--}}
    {{--										class="btn -btn-item -promotion-button -menu-center -horizontal lazyload x-bg-position-center"--}}
    {{--										data-bgset="/assets/wm356/images/btn-register-login-bg.png"--}}
    {{--								>--}}
    {{--									<picture>--}}
    {{--										<source type="image/webp"--}}
    {{--										        data-srcset="/assets/wm356/images/ic-modal-menu-promotion.webp?v=2"/>--}}
    {{--										<source type="image/png?v=2"--}}
    {{--										        data-srcset="/assets/wm356/images/ic-modal-menu-promotion.png?v=2"/>--}}
    {{--										<img--}}
    {{--												alt="รูปไอคอนโปรโมชั่น"--}}
    {{--												class="img-fluid -icon-image lazyload"--}}
    {{--												width="65"--}}
    {{--												height="53"--}}
    {{--												data-src="/assets/wm356/images/ic-modal-menu-promotion.png?v=2"--}}
    {{--												src="/assets/wm356/images/ic-modal-menu-promotion.png?v=2"--}}
    {{--										/>--}}
    {{--									</picture>--}}
    {{--									--}}
    {{--									<div class="-typo-wrapper">--}}
    {{--										<div class="-typo">โปรโมชั่น</div>--}}
    {{--									</div>--}}
    {{--								</a>--}}
    {{--							</div>--}}
    {{--							<div class="col-6 -wrapper-box">--}}
    {{--								<a--}}
    {{--										href="https://lin.ee/BpAUj1s"--}}
    {{--										class="btn -btn-item -line-button -menu-center -horizontal lazyload x-bg-position-center"--}}
    {{--										target="_blank"--}}
    {{--										rel="noopener nofollow"--}}
    {{--										data-bgset="/assets/wm356/images/btn-register-login-bg.png"--}}
    {{--								>--}}
    {{--									<picture>--}}
    {{--										<source type="image/webp"--}}
    {{--										        data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.webp?v=2"/>--}}
    {{--										<source type="image/png"--}}
    {{--										        data-srcset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.png"/>--}}
    {{--										<img--}}
    {{--												alt="รูปไอคอนดูหนัง"--}}
    {{--												class="img-fluid -icon-image lazyload"--}}
    {{--												width="65"--}}
    {{--												height="53"--}}
    {{--												data-src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.png"--}}
    {{--												src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-modal-menu-line.png"--}}
    {{--										/>--}}
    {{--									</picture>--}}
    {{--									--}}
    {{--									<div class="-typo-wrapper">--}}
    {{--										<div class="-typo">ไลน์</div>--}}
    {{--									</div>--}}
    {{--								</a>--}}
    {{--							</div>--}}
    {{--						</div>--}}
    {{--						--}}
    {{--						<div class="-inner-bottom-body-section"></div>--}}
    {{--					</div>--}}
    {{--				</div>--}}
    {{--			</div>--}}
    {{--		</div>--}}
    {{--	</div>--}}
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
                                                src="/images/flag/th.png" class="img img-fluid" loading="lazy"></a>
                                </div>
                                <div class="col"><a style="color:black"
                                                    href="{{ route('customer.home.lang', ['lang' => 'en']) }}"><img
                                                src="/images/flag/en.png" class="img img-fluid" loading="lazy"></a>
                                </div>
                                <div class="col"><a style="color:black"
                                                    href="{{ route('customer.home.lang', ['lang' => 'kh']) }}"><img
                                                src="/images/flag/kh.png" class="img img-fluid" loading="lazy"></a>
                                </div>
                                <div class="col"><a style="color:black"
                                                    href="{{ route('customer.home.lang', ['lang' => 'la']) }}"><img
                                                src="/images/flag/la.png" class="img img-fluid" loading="lazy"></a>
                                </div>
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

@endsection

@push('scripts')

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="preload" href="{{ asset('vendor/inputmask/jquery.inputmask.js') }}" as="script">
    <script src="{{ asset('vendor/inputmask/jquery.inputmask.js') }}" defer></script>
    <script type="text/javascript" defer>
        function markmobile() {
            $('#tel').inputmask({
                alias: 'tel',
                mask: "(999)-999-9999",
                removeMaskOnSubmit: true,
                autoUnmask: true,
                clearIncomplete: true,
                clearMaskOnLostFocus: true
            });
        }

        function togglePassword() {
            const passwordInput = document.getElementById("password1");
            const toggleIcon = document.getElementById("toggle-icon");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleIcon.classList.remove("fa-eye");
                toggleIcon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                toggleIcon.classList.remove("fa-eye-slash");
                toggleIcon.classList.add("fa-eye");
            }
        }

        function isNumeric(str) {
            return /^\d+$/.test(str); // true ถ้าเป็นตัวเลขล้วน
        }

        function trans(key, replace = {}) {
            var translation = key.split('.').reduce((t, i) => t[i] || null, window.i18n);

            for (var placeholder in replace) {
                translation = translation.replace(`:${placeholder}`, replace[placeholder]);
            }
            return translation;
        }

        // $('.bank-select').select2({
        //     theme: 'gt-dark',          // ใช้ธีมกำหนดเองด้านล่าง
        //     width: '100%',
        //     placeholder: 'กรุณาเลือกธนาคาร',
        //     allowClear: false,
        //     minimumResultsForSearch: 8,
        //     containerCssClass: 's2-lg',            // ให้ขนาดเท่ากับ input เดิม
        //     dropdownCssClass:  's2-lg s2-dropdown',
        //     // ใส่ parent ถ้าอยู่ในโมดัล/ฟอร์มเพื่อกัน z-index/overflow
        //     dropdownParent: $('#registerForm').length ? $('#registerForm') : $(document.body),
        // });
        //
        // // โฟกัสช่องค้นหา + เลื่อนรายการไฮไลต์ให้อยู่ในจอ
        // $(document).on('select2:open', () => {
        //     const f = document.querySelector('.select2-container--open .select2-search__field');
        //     if (f) f.focus({preventScroll:true});
        //     setTimeout(() => {
        //         const hi = document.querySelector('.select2-results__option--highlighted');
        //         if (hi) hi.scrollIntoView({block:'nearest'});
        //     }, 0);
        // });




        (function waitForJQuery() {
            if (typeof window.jQuery !== 'undefined') {


                $(function () {
                    markmobile();

                    $('#bank').on('change', function () {
                        if ($('#bank option:selected').val() === '18') {
                            // $('#acc_no_tw').prop('required',true);
                            // $('#acc_no').prop('readonly',true);
                            $('.acc_no_tw').css('display', 'block');
                            $('.tw').css('display', 'block');
                        } else {
                            // $('#acc_no').prop('readonly',false);
                            // $('#acc_no_tw').prop('required',false);
                            $('.acc_no_tw').css('display', 'none');
                            $('.tw').css('display', 'none');
                        }
                    });

                    $(".bank-select").select2({
                        theme: 'gt-dark',
                        width: '100%',
                        placeholder: 'กรุณาเลือกธนาคาร',
                        allowClear: false,
                        minimumResultsForSearch: 8,
                        containerCssClass: 's2-lg',
                        dropdownCssClass:  's2-lg s2-dropdown',
                        dropdownParent: $(document.body),
                        templateResult: function(option) {
                            if (!option.id) return option.text;
                            return $('<span><img src="' + $(option.element).data('img') + '" width="20"> ' + option.text + '</span>');
                        },
                        templateSelection: function(option) {
                            if (!option.id) return option.text;
                            return $('<span><img src="' + $(option.element).data('img') + '" width="20"> ' + option.text + '</span>');
                        }
                    });


                    let phoneTimer;
                    $('#user_name1').on('input', function () {
                        clearTimeout(phoneTimer);
                        const phone = document.getElementById('user_name1').value.trim();
                        const status = document.getElementById('phone-status');
                        const status1 = document.getElementById('account-status');

                        if (!isNumeric(phone)) {
                            status.innerText = trans('app.register.numberonly');
                            status.style.color = 'red';
                            return;
                        }

                        phoneTimer = setTimeout(function () {
                            if (phone.length === 10) {

                                // window.app.checkPhone(phone);

                                axios.post("{{ route('customer.check.phone') }}", {username: phone})
                                    .then(res => {
                                        const status = document.getElementById('phone-status');
                                        if (res.data.exists) {
                                            if ($('#bank option:selected').val() === '18') {
                                                $('#acc_no').val(phone)
                                                status1.innerText = res.data.message;
                                                status1.style.color = 'red';
                                            }
                                            status.innerText = res.data.message;
                                            status.style.color = 'red';
                                        } else {
                                            if ($('#bank option:selected').val() === '18') {
                                                $('#acc_no').val(phone)
                                                status1.innerText = res.data.message;
                                                status1.style.color = 'green';
                                            }
                                            status.innerText = res.data.message;
                                            status.style.color = 'green';
                                        }
                                    })
                                    .catch(() => {
                                        status.innerText = 'เกิดข้อผิดพลาด';
                                        status.style.color = 'gray';
                                    });

                            } else {
                                if ($('#bank option:selected').val() === '18') {
                                    $('#acc_no').val('')
                                    status1.innerText = '';

                                }
                                status.innerText = '';

                            }
                        }, 500);
                    });

                    // เช็คธนาคาร + บัญชีเมื่อเปลี่ยน
                    let bankTimer;
                    $('#bank, #acc_no').on('input change', function () {
                        clearTimeout(bankTimer);

                        const bank = document.getElementById('bank').value;
                        const account = document.getElementById('acc_no').value.trim();
                        const status = document.getElementById('account-status');

                        if (!isNumeric(account)) {
                            status.innerText = trans('app.register.numberonly');
                            status.style.color = 'red';
                            return;
                        }

                        if (bank == 18) return;

                        if (bank && account.length >= 10) {
                            bankTimer = setTimeout(function () {

                                axios.post("{{ route('customer.check.bank') }}", {bank: bank, acc_no: account})
                                    .then(res => {

                                        if (res.data.valid) {
                                            status.innerText = res.data.message;
                                            status.style.color = 'red';
                                        } else {
                                            $('#name').val(res.data.firstname+' '+res.data.lastname);
                                            // $('#lastname').val(res.data.lastname);
                                            status.innerText = res.data.message;
                                            status.style.color = 'green';
                                        }
                                    })
                                    .catch(() => {
                                        status.innerText = 'เกิดข้อผิดพลาด';
                                        status.style.color = 'gray';
                                    });

                            }, 500);
                        } else {
                            status.innerText = '';
                        }
                    });
                });
            } else {
                setTimeout(waitForJQuery, 50);
            }
        })();


    </script>
    <script>
        // $(".bank-select").select2({
        //     theme: 'gt-dark',
        //     width: '100%',
        //     placeholder: 'กรุณาเลือกธนาคาร',
        //     allowClear: true,
        //     minimumResultsForSearch: 6,            // มี 6 รายการขึ้นไปค่อยโชว์ช่องค้นหา
        //     containerCssClass: 's2-lg',
        //     dropdownCssClass:  's2-lg s2-dropdown',
        //     templateResult: function(option) {
        //         if (!option.id) return option.text;
        //         return $('<span><img src="' + $(option.element).data('img') + '" width="20"> ' + option.text + '</span>');
        //     },
        //     templateSelection: function(option) {
        //         if (!option.id) return option.text;
        //         return $('<span><img src="' + $(option.element).data('img') + '" width="20"> ' + option.text + '</span>');
        //     }
        // });

    </script>

@endpush

