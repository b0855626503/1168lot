<nav class="x-header js-header-selector navbar navbar-expand-lg -anon">
    <div class="container-fluid -inner-container">
        <div class="">
            <button type="button" class="btn bg-transparent p-0 x-hamburger" data-toggle="modal"
                    data-target="#themeSwitcherModal">
                <span></span>
                <span></span>
                <span></span>
                <span class="sr-only">เปิดเมนูธีม</span>
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
                    <img alt="{{ $config->description }}" class="-icon img-fluid" width="40"
                         height="40" src="/assets\wm356\web\ezl-wm-356\img\ic-menu-promotion.png?v=2"/>
                    <img alt="{{ $config->description }}" class="-icon -active img-fluid"
                         width="40" height="40" src="/assets\wm356\web\ezl-wm-356\img\ic-menu-promotion-active.png?v=2"/>
                    <span>{{ __('app.login.promotion') }}</span>
                </a>
            </div>
        </div>

        <div id="headerContent">
            <div class="d-flex">
                <a href="{{ route('customer.session.store') }}" class="-btn-header-login btn mr-1 mr-sm-2" style="height: 39px">
                    {{ __('app.login.register') }}
                </a>

                <a href="{{ url('/') }}" class="-btn-header-login btn" style="height: 39px" data-toggle="modal"
                   data-target="#loginModal">
                    {{ __('app.login.login') }}
                </a>
            </div>
        </div>
    </div>
</nav>
