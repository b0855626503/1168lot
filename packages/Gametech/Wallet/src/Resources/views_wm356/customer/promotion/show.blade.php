@extends('wallet::layouts.app')

{{-- page title --}}
@section('title','')


@section('content')

    <div id="main__content" data-bgset="/assets/wm356/images/index-bg.jpg?v=2"
         class="lazyload x-bg-position-center x-bg-index lazyload">

        <div class="js-replace-cover-seo-container">
            <div class="x-cover -small x-cover-promotion lazyload x-bg-position-center"
                 data-bgset="{{ Storage::url('gametype_img/' . $type->filepic).'?'.microtime() }}"
                 style="background-image: url(&quot;{{ Storage::url('gametype_img/' . $type->filepic).'?'.microtime() }}&quot;);">
                <div class="x-cover-template-full">
                    <div class="container -container-wrapper">
                        <div class="-row-wrapper">
                            <div class="-col-wrapper -first" data-animatable="fadeInModal">
                                <div class="x-cover-typography">
                                    <h1 class="-title">{{ $type->title }}</h1>
                                    <p class="-sub-title">{{ $type->content }}</p>
                                </div>

{{--                                <button class="btn x-cover-btn"  onclick="window.location.href='{{ route('customer.session.store') }}'">--}}
{{--                                    {{ __('app.login.register') }}--}}
{{--                                </button>--}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="x-promotion-index">
            <div class="container">
                <div class="row px-2">
                    @foreach($promotions as $i => $item)
                        <div class="col-lg-4 col-6 -promotion-card-link" data-animatable="fadeInUp"
                             data-delay="{{ 100 * $i }}">
                            <a
                                class="d-block h-100"
                                data-toggle="modal" data-target="#promotionmodal{{ $i }}"
                            >
                                <div class="x-card card -multi-card lazyload x-bg-position-center"
                                     data-bgset="assets/wm356/images/card-promotion-bg.jpg?v=2">
                                    <div class="-img-container">
                                        <img src="{{  Storage::url('promotion_img/'.$item->filepic)  }}"
                                             alt="{{ $item->name_th }}" class="-img"/>
                                    </div>
                                    <div class="card-body">
                                        <div class="-title-container m-3">
                                            <h3 class="-title">{{ $item->name_th }}</h3>
                                        </div>
                                    </div>

                                </div>
                            </a>
                        </div>
                    @endforeach

                        @foreach($pro_contents as $i => $item)
                            <div class="col-lg-4 col-6 -promotion-card-link" data-animatable="fadeInUp"
                                 data-delay="{{ 100 * $i }}">
                                <a
                                    class="d-block h-100"
                                    data-toggle="modal" data-target="#promotionmodals{{ $i }}"
                                >
                                    <div class="x-card card -multi-card lazyload x-bg-position-center"
                                         data-bgset="/assets/wm356/images/card-promotion-bg.jpg?v=2">
                                        <div class="-img-container">
                                            <img src="{{  Storage::url('procontent_img/'.$item->filepic)  }}"
                                                 alt="{{ $item->name_th }}" class="-img"/>
                                        </div>
                                        <div class="card-body">
                                            <div class="-title-container m-3">
                                                <h3 class="-title">{{ $item->name_th }}</h3>
                                            </div>
                                        </div>
                                        <div class="card-footer">

                                        </div>

                                    </div>
                                </a>
                            </div>
                        @endforeach

                </div>
            </div>

        </div>

        @foreach($promotions as $i => $item)
            <div class="x-modal modal -v2 -with-backdrop -with-separator -with-more-than-half-size"
                 id="promotionmodal{{ $i }}"
                 tabindex="-1"
                 role="dialog"
                 data-loading-container=".modal-body"
                 data-ajax-modal-always-reload="true"
                 data="deposit"
                 data-container="#promotionmodal{{ $i }}"
                 style="display: none;"
                 aria-hidden="true">

                <div
                    class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-single-card"
                    role="document">
                    <div class="modal-content -modal-content">
                        <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="modal-header -modal-header"></div>
                        <div class="modal-body -modal-body">
                            <div class="d-flex flex-column">
                                <div class="-real-content">
                                    <div
                                        class="x-card card -single-card x-bg-position-center lazyloaded"
                                        data-bgset="assets/wm356/images/card-promotion-bg.jpg?v=2"
                                        style="background-image: url('assets/wm356/images/card-promotion-bg.jpg?v=2');"
                                    >
                                        <div class="-img-container">
                                            <img src="{{  Storage::url('promotion_img/'.$item->filepic)  }}"
                                                 alt="{{ $item->name_th }}" class="-img"/>
                                        </div>
                                        <div class="card-body">
                                            <div class="-title-container m-3">
                                                <h3 class="-title">{{ $item->name_th }}</h3>
                                            </div>
                                            <div class="-promotion-content p-3">
                                                {!! $item->content !!}
                                            </div>
                                        </div>


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        @foreach($pro_contents as $i => $item)
            <div class="x-modal modal -v2 -with-backdrop -with-separator -with-more-than-half-size"
                 id="promotionmodals{{ $i }}"
                 tabindex="-1"
                 role="dialog"
                 data-loading-container=".modal-body"
                 data-ajax-modal-always-reload="true"
                 data="deposit"
                 data-container="#promotionmodal{{ $i }}"
                 style="display: none;"
                 aria-hidden="true">

                <div
                    class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-single-card"
                    role="document">
                    <div class="modal-content -modal-content">
                        <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="modal-header -modal-header"></div>
                        <div class="modal-body -modal-body">
                            <div class="d-flex flex-column">
                                <div class="-real-content">
                                    <div
                                        class="x-card card -single-card x-bg-position-center lazyloaded"
                                        data-bgset="/assets/wm356/images/card-promotion-bg.jpg?v=2"
                                        style="background-image: url('/assets/wm356/images/card-promotion-bg.jpg?v=2');"
                                    >
                                        <div class="-img-container">
                                            <img src="{{  Storage::url('procontent_img/'.$item->filepic)  }}"
                                                 alt="{{ $item->name_th }}" class="-img"/>
                                        </div>
                                        <div class="card-body">
                                            <div class="-title-container m-3">
                                                <h3 class="-title">{{ $item->name_th }}</h3>
                                            </div>
                                            <div class="-promotion-content p-3">
                                                {!! $item->content !!}
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach


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
                                                data-srcset="{{ url(core()->imgurl($config->logo,'img')) }}"/>
                                        <source type="image/png"
                                                data-srcset="{{ url(core()->imgurl($config->logo,'img')) }}"/>
                                        <img
                                                alt="logo image" loading="lazy"
                                                class="img-fluid lazyload -logo"
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

@push('script')
    <script type="application/ld+json">
            {
                "url": "promotion"
            }
    </script>
@endpush

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            $('body').addClass('x-ez-igame-promotion-index');
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




