<div id="main__content" data-bgset="/assets/wm356/images/index-bg.jpg?v=2"
     class="lazyload x-bg-position-center x-bg-index lazyload">
    <div class="js-replace-cover-seo-container">
        <div class="x-homepage-banner-container">
            <div
                    data-slickable='{"arrows":false,"dots":true,"slidesToShow":1,"centerMode":true,"infinite":true,"autoplay":true,"autoplaySpeed":4000,"pauseOnHover":false,"focusOnSelect":true,"variableWidth":true,"responsive":{"sm":{"fade":true,"variableWidth":false}}}'
                    class="x-banner-slide-wrapper -single"
                    data-animatable="fadeInUp"
                    data-delay="200"
            >
                @if(count($slides) == 1)
                    @foreach($slides as $i => $item)
                        <div class="-slide-inner-wrapper -slick-item">
                            <div class="-link-wrapper">
                                <picture>
                                    <source type="image/webp"
                                            srcset="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                    <source type="image/jpg"
                                            srcset="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                    <img class="img-fluid -slick-item -item-{{ $i+1 }}" alt="banner-{{ $i+1 }}"
                                         width="1200"
                                         height="590"
                                         src="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                </picture>
                            </div>
                        </div>
                    @endforeach
                    @foreach($slides as $i => $item)
                        <div class="-slide-inner-wrapper -slick-item">
                            <div class="-link-wrapper">
                                <picture>
                                    <source type="image/webp"
                                            srcset="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                    <source type="image/jpg"
                                            srcset="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                    <img class="img-fluid -slick-item -item-{{ $i+1 }}" alt="banner-{{ $i+1 }}"
                                         width="1200"
                                         height="590"
                                         src="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                </picture>
                            </div>
                        </div>
                    @endforeach
                @else
                    @foreach($slides as $i => $item)
                        <div class="-slide-inner-wrapper -slick-item">
                            <div class="-link-wrapper">
                                <picture>
                                    <source type="image/webp"
                                            srcset="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                    <source type="image/jpg"
                                            srcset="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                    <img class="img-fluid -slick-item -item-{{ $i+1 }}" alt="banner-{{ $i+1 }}"
                                         width="1200"
                                         height="590"
                                         src="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                </picture>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <div class="x-index-content-main-container -logged">
        <div class="x-quick-transaction-buttons js-quick-transaction-buttons">
            <a class="btn -btn -promotion -vertical" href="{{ route('customer.promotion.index') }}" target="_blank"
               rel="noopener nofollow">
                <span class="-ic-wrapper"> <img alt="โปรโมชั่นสุดคุ้ม เพื่อลูกค้าคนสำคัญ" class="img-fluid -ic"
                                                width="40" height="40"
                                                src="/assets/wm356/images/ic-quick-transaction-button-promotion.png?v=2"/></span>

                <span class="-btn-inner-content">
            <span class="-btn-inner-content-title">{{ __('app.home.promotion') }}</span>
        </span>
            </a>

            <button
                    class="btn -btn -deposit x-bg-position-center lazyloaded"
                    data-toggle="modal"
                    data-target="#depositModal"
                    data-bgset="build/images/btn-deposit-bg.png?v=2"
                    style="background-image: url('/assets/wm356/images/btn-deposit-bg.png?v=2');"
            >
                <span class="-ic-wrapper"> <img alt="ฝากเงินง่ายๆ ด้วยระบบออโต้ การันตี 1 นาที" class="img-fluid -ic"
                                                width="40" height="40"
                                                src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.png"/></span>

                <span class="-btn-inner-content">
            <span class="-btn-inner-content-title">{{ __('app.home.refill') }}</span>
        </span>
            </button>

            <button
                    class="btn -btn -withdraw x-bg-position-center lazyloaded"
                    data-toggle="modal"
                    data-target="#withdrawModal"
                    data-bgset="build/images/btn-withdraw-bg.png?v=2"
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


        <div class="x-title-with-tag-header" data-animatable="fadeInUp" data-delay="150">
            <div class="container">
                <h1 class="-title">{{ $config->content_header }}</h1>
            </div>
        </div>

        <div class="x-category-total-game -v2">
            <div class="container-fluid cat">
                <div class="menu-scroll-wrapper">
                    <div class="menu-scroll">
                        {{--                        {{ dd($gameTypes) }}--}}
                        @foreach($gameTypes as $gameType)
                            {{--                            {{ dd($gameType) }}--}}
                            <div class="menu-item">
                                <a href="{{ route('customer.cats.list', ['id' => strtolower($gameType->id)]) }}">
                                    <img src="{{ $gameType->icon }}">
                                    <small>{{ __('app.game.'.strtolower($gameType->id)) }}</small>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>


        @if(count($hots) > 0)
            <div class="category-block">
                <div class="x-lotto-category x-provider-category -provider_hots">
                    <div class="container-fluid">
                        {{--                    <div class="category-title" data-animatable="fadeInUp" data-delay="150">--}}
                        {{--                        {{ $i }}--}}
                        {{--                    </div>--}}

                        <div class="category-bar">
                            <div class="category-title">{{ __('app.game.hots') }}</div>

                        </div>


                        <div class="-lotto-category-wrapper">
                            <ul class="navbar-nav">

                                @foreach($hots as $k => $item)

                                    <li class="nav-item -lotto-card-item">
                                        <div
                                                class="x-game-list-item-macro-in-share js-game-list-toggle -big-with-countdown-dark -cannot-entry -untestable -use-promotion-alert {{ $item['maintainance'] ? 'maintenance' : '' }}"
                                                data-status="-cannot-entry -untestable" >
                                            <div class="-inner-wrapper">
                                                @if($item['maintainance'])
                                                    <!-- ▼ ป้ายมุมขวาบน -->
                                                    <div class="-maintenance-badge" aria-label="maintenance time">
                                                        <i class="far fa-clock" aria-hidden="true"></i>
                                                        <span class="-label">{{ __('app.home.maintenance') }}</span>
                                                        <time class="-time" datetime="2025-09-27T00:46:00+07:00">{{ core()->formatDate($item['endMaintenance']) }}</time>
                                                    </div>
                                                    <!-- ▲ ป้ายมุมขวาบน -->
                                                @endif

                                                <picture>
                                                    <source type="image/webp"
                                                            data-srcset="{{ $item['logoURL'] }}"/>
                                                    <source type="image/png"
                                                            data-srcset="{{ $item['logoURL'] }}"/>
                                                    <img
                                                            alt="smm-pg-soft cover image png"
                                                            class="img-fluid lazyload -cover-img"
                                                            width="400"
                                                            height="580"
                                                            data-src="{{ $item['logoURL'] }}"
                                                            src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                    />
                                                </picture>

                                                <div class="-overlay">
                                                    <div class="-overlay-inner">
                                                        <div class="-wrapper-container">
                                                            @if($item['gameList'])
                                                                <a
                                                                        href="{{ route('customer.game.list', ['id' => Str::lower($item['provider']) , 'name' => Str::lower($item['prefix']), 'type' => Str::lower($item['providerType']) ]) }}"
                                                                        class="-btn -btn-play">
                                                                    <i class="{{ $item['maintainance'] ? 'fas fa-screwdriver-wrench' : 'fas fa-play'}}"></i>
                                                                    <span class="-text-btn">{{  $item['maintainance'] ? __('app.home.maintenance') :  __('app.home.join') }}</span>
                                                                </a>

                                                                {{--                                                                    <a--}}
                                                                {{--                                                                            href="{{ route('api.games.get', ['type' => $item['providerType'] , 'provider' => $item['provider']]) }}"--}}
                                                                {{--                                                                            class="-btn -btn-play">--}}
                                                                {{--                                                                        <i class="{{ $item['maintainance'] ? 'fas fa-screwdriver-wrench' : 'fas fa-play'}}"></i>--}}
                                                                {{--                                                                        <span class="-text-btn">{{  $item['maintainance'] ? __('app.home.maintenance') :  __('app.home.join') }}</span>--}}
                                                                {{--                                                                    </a>--}}
                                                            @else
                                                                <a data-toggle="modal" data-target="#gametechPopup"
                                                                   target="gametechPopup"
                                                                   href="{{ route('customer.game.redirect_single', ['type' => $item['providerType'] , 'provider' => $item['provider'] , 'id' => 'lobby']) }}"
                                                                   class="-btn -btn-play">
                                                                    <i class="{{ $item['maintainance'] ? 'fas fa-screwdriver-wrench' : 'fas fa-play'}}"></i>
                                                                    <span class="-text-btn">{{  $item['maintainance'] ? __('app.home.maintenance') :  __('app.home.join') }}</span>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="-title">{{$item['rec'] .'. '.$item['providerName']}}</div>
                                        </div>


                                    </li>
                                @endforeach

                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{--        {{ dd($games) }}--}}
        @foreach($games as $i => $game)
            @if(count($game) > 0)
                <div class="category-block">
                    <div class="x-lotto-category x-provider-category -provider_{{ strtolower($i) }}">
                        <div class="container-fluid">
                            {{--                    <div class="category-title" data-animatable="fadeInUp" data-delay="150">--}}
                            {{--                        {{ $i }}--}}
                            {{--                    </div>--}}

                            <div class="category-bar">
                                <div class="category-title">{{ __('app.game.'.strtolower($i)) }}</div>

                            </div>


                            <div class="-lotto-category-wrapper">
                                <ul class="navbar-nav">

                                    @foreach($game as $k => $item)

                                        <li class="nav-item -lotto-card-item">
                                            <div
                                                    class="x-game-list-item-macro-in-share js-game-list-toggle -big-with-countdown-dark -cannot-entry -untestable -use-promotion-alert {{ $item['maintainance'] ? 'maintenance' : '' }}"
                                                    data-status="-cannot-entry -untestable" >
                                                <div class="-inner-wrapper">

                                                    @if($item['maintainance'])
                                                    <!-- ▼ ป้ายมุมขวาบน -->
                                                    <div class="-maintenance-badge" aria-label="maintenance time">
                                                        <i class="far fa-clock" aria-hidden="true"></i>
                                                        <span class="-label">{{ __('app.home.maintenance') }}</span>
                                                        <time class="-time" datetime="2025-09-27T00:46:00+07:00">{{ core()->formatDate($item['endMaintenance']) }}</time>
                                                    </div>
                                                    <!-- ▲ ป้ายมุมขวาบน -->
                                                    @endif
                                                    <picture>
                                                        <source type="image/webp"
                                                                data-srcset="{{ $item['logoURL'] }}"/>
                                                        <source type="image/png"
                                                                data-srcset="{{ $item['logoURL'] }}"/>
                                                        <img
                                                                alt="smm-pg-soft cover image png"
                                                                class="img-fluid lazyload -cover-img"
                                                                width="400"
                                                                height="580"
                                                                data-src="{{ $item['logoURL'] }}"
                                                                src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                        />
                                                    </picture>

                                                    <div class="-overlay">
                                                        <div class="-overlay-inner">
                                                            <div class="-wrapper-container">
                                                                @if($item['gameList'])
                                                                    <a
                                                                            href="{{ route('customer.game.list', ['id' => Str::lower($item['provider']) , 'name' => Str::lower($item['prefix']), 'type' => Str::lower($item['providerType']) ]) }}"
                                                                            class="-btn -btn-play">
                                                                        <i class="{{ $item['maintainance'] ? 'fas fa-screwdriver-wrench' : 'fas fa-play'}}"></i>
                                                                        <span class="-text-btn">{{  $item['maintainance'] ? __('app.home.maintenance') :  __('app.home.join') }}</span>
                                                                    </a>

{{--                                                                    <a--}}
{{--                                                                            href="{{ route('api.games.get', ['type' => $item['providerType'] , 'provider' => $item['provider']]) }}"--}}
{{--                                                                            class="-btn -btn-play">--}}
{{--                                                                        <i class="{{ $item['maintainance'] ? 'fas fa-screwdriver-wrench' : 'fas fa-play'}}"></i>--}}
{{--                                                                        <span class="-text-btn">{{  $item['maintainance'] ? __('app.home.maintenance') :  __('app.home.join') }}</span>--}}
{{--                                                                    </a>--}}
                                                                @else
                                                                    <a data-toggle="modal" data-target="#gametechPopup"
                                                                       target="gametechPopup"
                                                                       href="{{ route('customer.game.redirect_single', ['type' => $item['providerType'] , 'provider' => $item['provider'] , 'id' => 'lobby']) }}"
                                                                       class="-btn -btn-play">
                                                                        <i class="{{ $item['maintainance'] ? 'fas fa-screwdriver-wrench' : 'fas fa-play'}}"></i>
                                                                        <span class="-text-btn">{{  $item['maintainance'] ? __('app.home.maintenance') :  __('app.home.join') }}</span>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="-title">{{$item['providerName']}}</div>
                                            </div>


                                        </li>
                                    @endforeach

                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach


    </div>
</div>

@push('scripts')

    <script type="text/javascript">

        function isMobile() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        document.querySelectorAll("a[target='gametechPopup']").forEach(link => {
            link.addEventListener("click", function (e) {
                e.preventDefault();
                const url = link.href;

                if (isMobile()) {
                    // ✅ แสดง Toast ก่อน
                    if (window.Toast && window.Toast.fire) {
                        window.Toast.fire({
                            icon: 'success',
                            title: '{{ __("app.game.login_complete") }}'
                        });
                    }

                    // ✅ รอ 500ms แล้ว redirect ไปหน้าเกม
                    setTimeout(() => {
                        window.location.href = url;
                    }, 500);

                } else {
                    // ✅ เดสก์ท็อปเปิด popup
                    const newWindow = window.open(url, 'gametechPopup', 'width=800,height=400,screenX=200,screenY=200');

                    if (!newWindow) {
                        if (window.Toast && window.Toast.fire) {
                            window.Toast.fire({
                                icon: 'error',
                                title: 'Popup ถูกบล็อก กรุณาอนุญาต popup ในเบราว์เซอร์ของคุณ'
                            });
                        }
                    } else {
                        window.Toast.fire({
                            icon: 'success',
                            title: '{{ __("app.game.login_complete") }}'
                        });
                    }
                }
            });
        });


    </script>
@endpush
