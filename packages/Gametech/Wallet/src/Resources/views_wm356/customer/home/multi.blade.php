<div id="main__content" data-bgset="assets/wm356/images/index-bg.jpg?v=2"
     class="lazyload x-bg-position-center x-bg-index lazyload">
    <div class="js-replace-cover-seo-container">
        <div class="x-homepage-banner-container">
            <div
                data-slickable='{"arrows":false,"dots":true,"slidesToShow":1,"centerMode":true,"infinite":true,"autoplay":true,"autoplaySpeed":4000,"pauseOnHover":false,"focusOnSelect":true,"variableWidth":true,"responsive":{"sm":{"fade":true,"variableWidth":false}}}'
                class="x-banner-slide-wrapper -single"
                data-animatable="fadeInUp"
                data-delay="200"
            >
                @foreach($slides as $i => $item)
                    <div class="-slide-inner-wrapper -slick-item">
                        <div class="-link-wrapper">
                            <picture>
                                <source type="image/webp"
                                        srcset="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                <source type="image/jpg"
                                        srcset="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                                <img class="img-fluid -slick-item -item-{{ $i+1 }}" alt="banner-{{ $i+1 }}" width="1200"
                                     height="590"
                                     src="{{  Storage::url('slide_img/'.$item->filepic)  }}"/>
                            </picture>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </div>

    <div class="x-title-with-tag-header" data-animatable="fadeInUp" data-delay="150">
        <div class="container">
            <h1 class="-title">{{ $config->content_header }}</h1>
        </div>
    </div>


    <div id="app">
        @foreach($games as $i => $game)
            <div class="x-provider-category -provider_{{ strtolower($i) }} align-items-center align-content-center">
                <div class="container-fluid">
                    <div class="-provider-category-wrapper" data-animatable="fadeInUp" data-delay="150">
                        <ul class="navbar-nav">
                            {{--                            {{ dd($games[$i]) }}--}}
                            @foreach($games[$i] as $k => $item)

                                <li class="nav-item -provider-card-item -smm-{{ $item['id'] }}">
                                    <div
                                        class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert text-center"
                                        data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">
                                            <div class="x-game-badge-component - -big" data-animatable="fadeInUp"
                                                 data-delay="400">
                                                <span></span>
                                            </div>

                                            <picture>
                                                <source type="image/webp"
                                                        data-srcset="{{ $item['image'] }}"/>
                                                <source type="image/png"
                                                        data-srcset="{{ $item['image'] }}"/>
                                                <img
                                                    alt="smm-pg-soft cover image png"
                                                    class="img-fluid lazyload -cover-img"
                                                    width="400"
                                                    height="580"
                                                    data-src="{{ $item['image'] }}"
                                                    src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        @if($item['new'] === true)
                                                            <button
                                                                onclick="openQuickRegis({ details : {{ Illuminate\Support\Js::from($item) }}})"
                                                                class="-btn -btn-play">
                                                                <i class="fas fa-plus"></i>
                                                                <span class="-text-btn">สมัคร</span>
                                                            </button>

                                                        @else

                                                            @if($item['connect'] === true)
                                                                <button
                                                                    onclick="openQuickView({ details : {{ Illuminate\Support\Js::from($item) }}})"
                                                                    class="-btn -btn-play">
                                                                    <i class="fas fa-play"></i>
                                                                    <span class="-text-btn">เล่นเกม</span>
                                                                </button>
                                                                <button
                                                                    onclick="openTransfer({ details : {{ Illuminate\Support\Js::from($item) }}})"
                                                                    class="-btn -btn-demo"
                                                                    rel="nofollow noopener">
                                                                    <span class="-text-btn">โยกเงิน</span>
                                                                </button>
                                                            @else
                                                                <button class="-btn -btn-play">
                                                                    <i class="fas fa-times"></i>
                                                                    <span class="-text-btn">เชื่อมต่อไม่ได้</span>
                                                                </button>

                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="-title">{{$item['name']}}</div>
                                    </div>
                                </li>

                            @endforeach

                        </ul>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
