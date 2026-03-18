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


                <a class="-menu-link" href="{{ route('customer.promotion.show') }}">
                    <img alt="{{ $config->description }}" class="-icon img-fluid" width="40" loading="lazy" fetchpriority="low"
                         height="40" src="/assets\wm356\web\ezl-wm-356\img\ic-menu-promotion.png?v=2"/>
                    <img alt="{{ $config->description }}" class="-icon -active img-fluid" loading="lazy" fetchpriority="low"
                         width="40" height="40" src="/assets\wm356\web\ezl-wm-356\img\ic-menu-promotion-active.png?v=2"/>
                    <span>{{ __('app.login.promotion') }}</span>
                </a>
            </div>
        </div>

        <div id="headerContent">
            <div class="d-flex">
{{--                <a href="{{ $config->linelink }}" class="x-header-btn-support -in-anon" target="_blank"--}}
{{--                   rel="noreferrer nofollow">--}}
{{--                    <picture>--}}
{{--                        <source type="image/webp" srcset="/assets\wm356\web\ezl-wm-356\img\ic-line-support.webp?v=1"/>--}}
{{--                        <source type="image/png?v=2" srcset="/assets\wm356\web\ezl-wm-356\img\ic-line-support.png?v=1"/>--}}
{{--                        <img alt="{{ $config->description }}" class="img-fluid -ic" loading="lazy" fetchpriority="low"--}}
{{--                             width="120" height="39" src="/assets\wm356\web\ezl-wm-356\img\ic-line-support.png?v=1"/>--}}
{{--                    </picture>--}}
{{--                    <picture>--}}
{{--                        <source type="image/webp"--}}
{{--                                srcset="/assets\wm356\web\ezl-wm-356\img\ic-line-support-mobile.webp?v={{ time() }}"/>--}}
{{--                        <source type="image/png"--}}
{{--                                srcset="/assets\wm356\web\ezl-wm-356\img\ic-line-support-mobile.png?v={{ time() }}"/>--}}
{{--                        <img alt="{{ $config->description }}" class="img-fluid -ic -mobile" loading="lazy" fetchpriority="low"--}}
{{--                             width="28" height="28"--}}
{{--                             src="/assets\wm356\web\ezl-wm-356\img\ic-line-support-mobile.png?v={{ time() }}"/>--}}
{{--                    </picture>--}}
{{--                </a>--}}

                <a href="{{ route('customer.session.store',['id' => session('marketing_code')]) }}" class="-btn-header-login btn mr-1 mr-sm-2" style="height: 39px">
                    {{ __('app.login.register') }}
                </a>

                <a href="javascript:void(0)" style="height: 39px" class="-btn-header-login btn" data-toggle="modal"
                   data-target="#loginModal">
                    {{ __('app.login.login') }}
                </a>
            </div>
        </div>
    </div>
</nav>
