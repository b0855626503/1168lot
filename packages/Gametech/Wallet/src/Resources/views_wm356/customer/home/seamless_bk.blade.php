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
            <div class="container-fluid">
                <nav class="nav-menu" id="navbarCategory">
                    <ul class="-menu-parent navbar-nav flex-row">

                        <li class="-list-parent nav-item px-lg-2 -category-casino" data-animatable="fadeInUp"
                            data-delay="100">
                            <a href="{{ route('customer.cats.list', ['id' => 'casino']) }}"
                               class="x-category-button -category-casino -index-page -category-button-v2 -hoverable">
                                <img alt="category casino image png" class="-img -default" width="300" height="82"
                                     src="\assets\wm356\web\ezl-wm-356\img\menu-category-casino.png?v=2"/>

                                <img alt="category casino image png" class="-img -hover" width="300" height="82"
                                     src="\assets\wm356\web\ezl-wm-356\img\menu-category-casino-hover.png?v=2"/>

                                <span class="-menu-text-main -text-btn-image">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">{{ __('app.home.casino') }}</span>
                                                <span class="-text-mobile">{{ __('app.home.casino') }}</span>
                                            </div>
                                        </span>
                            </a>
                        </li>
                        <li class="-list-parent nav-item px-lg-2 -category-slot" data-animatable="fadeInUp"
                            data-delay="150">
                            <a href="{{ route('customer.cats.list', ['id' => 'slot']) }}"
                               class="x-category-button -category-slot -index-page -category-button-v2 -hoverable">
                                <img alt="category slot image png" class="-img -default" width="300" height="82"
                                     src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot.png?v=2"/>

                                <img alt="category slot image png" class="-img -hover" width="300" height="82"
                                     src="\assets\wm356\web\ezl-wm-356\img\menu-category-slot-hover.png?v=2"/>

                                <span class="-menu-text-main -text-btn-image">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">{{ __('app.home.slot') }}</span>
                                                <span class="-text-mobile">{{ __('app.home.slot') }}</span>
                                            </div>
                                        </span>
                            </a>
                        </li>
                        <li class="-list-parent nav-item px-lg-2 -category-sport" data-animatable="fadeInUp"
                            data-delay="200">
                            <a href="{{ route('customer.cats.list', ['id' => 'sport']) }}"
                               class="x-category-button -category-sport -index-page -category-button-v2 -hoverable">
                                <img alt="category sport image png" class="-img -default" width="300" height="82"
                                     src="\assets\wm356\web\ezl-wm-356\img\menu-category-sport.png?v=2"/>

                                <img alt="category sport image png" class="-img -hover" width="300" height="82"
                                     src="\assets\wm356\web\ezl-wm-356\img\menu-category-sport-hover.png?v=2"/>

                                <span class="-menu-text-main -text-btn-image">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">{{ __('app.home.sport') }}</span>
                                                <span class="-text-mobile">{{ __('app.home.sport') }}</span>
                                            </div>
                                        </span>
                            </a>
                        </li>
                        @if(isset($games['HUAY']))
                        <li class="-list-parent nav-item px-lg-2 -category-lotto" data-animatable="fadeInUp"
                            data-delay="200">
                            <a href="{{ route('customer.cats.list', ['id' => 'huay']) }}"
                               class="x-category-button -category-lotto -index-page -category-button-v2 -hoverable">
                                <img alt="category lotto image png" class="-img -default" width="300" height="82"
                                     src="\assets\wm356\web\ezl-wm-356\img\menu-category-lotto.png?v=2"/>

                                <img alt="category lotto image png" class="-img -hover" width="300" height="82"
                                     src="\assets\wm356\web\ezl-wm-356\img\menu-category-lotto-hover.png?v=2"/>

                                <span class="-menu-text-main -text-btn-image">
                                            <div class="-menu-text-wrapper">
                                                <span class="-text-desktop">แทงหวย</span>
                                                <span class="-text-mobile">แทงหวย</span>
                                            </div>
                                        </span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </nav>
            </div>
        </div>

        <div class="x-lotto-category x-provider-category -provider_casinos">
            <div class="container-fluid">
                <div class="-lotto-category-wrapper" data-animatable="fadeInUp" data-delay="150">
                    <ul class="navbar-nav">

                        @if(isset($games['CASINO']))
                            @foreach($games['CASINO'] as $k => $item)

                                <li class="nav-item -lotto-card-item">
                                    <div
                                            class="x-game-list-item-macro-in-share js-game-list-toggle -big-with-countdown-dark -cannot-entry -untestable -use-promotion-alert"
                                            data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">


                                            <picture>
                                                <source type="image/webp"
                                                        data-srcset="{{ $item->filepic }}"/>
                                                <source type="image/png"
                                                        data-srcset="{{ $item->filepic }}"/>
                                                <img
                                                        alt="smm-pg-soft cover image png"
                                                        class="img-fluid lazyload -cover-img"
                                                        width="400"
                                                        height="580"
                                                        data-src="{{ $item->filepic }}"
                                                        src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a
                                                                href="{{ route('customer.game.redirect_single', [ 'id' => $item->id , 'name' => $item->code ,'method' => $item->method ]) }}"
                                                                class="-btn -btn-play"
                                                                data-toggle="modal" data-target="#gametechPopup"
                                                                target="gametechPopup">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">{{ __('app.home.join') }}</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="-title">{{$item['name']}}</div>
                                    </div>


                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <div class="x-lotto-category x-provider-category -provider_slots">
            <div class="container-fluid">
                <div class="-lotto-category-wrapper" data-animatable="fadeInUp" data-delay="150">
                    <ul class="navbar-nav">

                        @if(isset($games['SLOT']))
                            @foreach($games['SLOT'] as $k => $item)

                                <li class="nav-item -lotto-card-item">
                                    <div
                                            class="x-game-list-item-macro-in-share js-game-list-toggle -big-with-countdown-dark -cannot-entry -untestable -use-promotion-alert"
                                            data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">


                                            <picture>
                                                <source type="image/webp"
                                                        data-srcset="{{ $item->filepic }}"/>
                                                <source type="image/png"
                                                        data-srcset="{{ $item->filepic }}"/>
                                                <img
                                                        alt="smm-pg-soft cover image png"
                                                        class="img-fluid lazyload -cover-img"
                                                        width="400"
                                                        height="580"
                                                        data-src="{{ $item->filepic }}"
                                                        src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a
                                                                href="{{ route('customer.game.redirect_single', [ 'id' => $item->id , 'name' => $item->code ,'method' => $item->method ]) }}"
                                                                class="-btn -btn-play"
                                                                data-toggle="modal" data-target="#gametechPopup"
                                                                target="gametechPopup">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">{{ __('app.home.join') }}</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="-title">{{$item['name']}}</div>
                                    </div>


                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <div class="x-lotto-category">
            <div class="container-fluid">
                <div class="-lotto-category-wrapper" data-animatable="fadeInUp" data-delay="150">
                    <ul class="navbar-nav">
                        @if(isset($games['SPORT']))
                            @foreach($games['SPORT'] as $k => $item)
                                <li class="nav-item -lotto-card-item">
                                    <div
                                            class="x-game-list-item-macro-in-share js-game-list-toggle -big-with-countdown-dark -cannot-entry -untestable -use-promotion-alert"
                                            data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">


                                            <picture>
                                                <source type="image/webp"
                                                        data-srcset="{{ $item->filepic }}"/>
                                                <source type="image/png"
                                                        data-srcset="{{ $item->filepic }}"/>
                                                <img
                                                        alt="smm-pg-soft cover image png"
                                                        class="img-fluid lazyload -cover-img"
                                                        width="400"
                                                        height="580"
                                                        data-src="{{ $item->filepic }}"
                                                        src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a
                                                                href="{{ route('customer.game.redirect_single', [ 'id' => $item->id , 'name' => $item->code ,'method' => $item->method ]) }}"
                                                                class="-btn -btn-play"
                                                                data-toggle="modal" data-target="#gametechPopup"
                                                                target="gametechPopup">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">{{ __('app.home.join') }}</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="-title">{{$item['name']}}</div>
                                    </div>


                                </li>
                            @endforeach
                        @endif
                        @if(isset($games['TRADING']))
                            @foreach($games['TRADING'] as $k => $item)
                                <li class="nav-item -lotto-card-item">
                                    <div
                                            class="x-game-list-item-macro-in-share js-game-list-toggle -big-with-countdown-dark -cannot-entry -untestable -use-promotion-alert"
                                            data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">


                                            <picture>
                                                <source type="image/webp"
                                                        data-srcset="{{ $item->filepic }}"/>
                                                <source type="image/png"
                                                        data-srcset="{{ $item->filepic }}"/>
                                                <img
                                                        alt="smm-pg-soft cover image png"
                                                        class="img-fluid lazyload -cover-img"
                                                        width="400"
                                                        height="580"
                                                        data-src="{{ $item->filepic }}"
                                                        src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a
                                                                href="{{ route('customer.game.list', ['id' => Str::lower($item->id)]) }}"
                                                                class="-btn -btn-play">
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">{{ __('app.home.join') }}</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="-title">{{$item['name']}}</div>
                                    </div>


                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            </div>
        </div>


        <div class="x-lotto-category">
            <div class="container-fluid">
                <div class="-lotto-category-wrapper" data-animatable="fadeInUp" data-delay="150">
                    <ul class="navbar-nav">
                        @if(isset($games['HUAY']))
                            @foreach($games['HUAY'] as $k => $item)
                                <li class="nav-item -lotto-card-item">
                                    <div
                                            class="x-game-list-item-macro-in-share js-game-list-toggle -big-with-countdown-dark -cannot-entry -untestable -use-promotion-alert"
                                            data-status="-cannot-entry -untestable">
                                        <div class="-inner-wrapper">

                                            <picture>
                                                <source type="image/webp"
                                                        data-srcset="{{ $item->filepic }}"/>
                                                <source type="image/png"
                                                        data-srcset="{{ $item->filepic }}"/>
                                                <img
                                                        alt="smm-pg-soft cover image png"
                                                        class="img-fluid lazyload -cover-img"
                                                        width="400"
                                                        height="580"
                                                        data-src="{{ $item->filepic }}"
                                                        src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                />
                                            </picture>

                                            <div class="-overlay">
                                                <div class="-overlay-inner">
                                                    <div class="-wrapper-container">
                                                        <a
                                                                href="{{ route('customer.game.list', ['id' => Str::lower($item->id)]) }}"
                                                                class="-btn -btn-play"
                                                        >
                                                            <i class="fas fa-play"></i>
                                                            <span class="-text-btn">{{ __('app.home.join') }}</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="-title">{{$item['name']}}</div>
                                    </div>


                                </li>
                            @endforeach
                        @endif

                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct"
            crossorigin="anonymous"></script>
    <script src="{{ asset('js/mdetect.js?v=1') }}"></script>
    <script type="text/javascript">


        let windowObjectReference = null; // global variable
        let previousURL; /* global variable that will store the
                    url currently in the secondary window */
        function openRequestedSingleTab(url, windowName) {

            const winHtml = `<!DOCTYPE html><html><head><title>Window with Blob</title></head><body><h1>Hello from the new window!</h1></body></html>`;
            const winUrl = URL.createObjectURL(new Blob([winHtml], {type: "text/html"}));
            const win = window.open(url, windowName, `width=800,height=400,screenX=200,screenY=200`);

            // const htmlResponse = "<html><head><title>API Response</title></head><body><h1>Hello, API!</h1></body></html>";

// Create a data URI for the HTML content
//             const dataUri = "data:text/html;charset=utf-8," + encodeURIComponent(htmlResponse);

// Open a new tab or window with the data URI
//             const newTab = window.open(url, '_blank');

// Check if the newTab variable is not null
//             if (newTab) {
//                 // Focus on the new tab
//                 newTab.focus();
//             } else {
//                 // Handling if the popup is blocked or window.open fails
//                 console.error('Failed to open new tab. Ensure popups are not blocked.');
//             }
            // window.toSend = $(this);
            // const w = 900;
            // const h = 500;
            // const y = window.top.outerHeight / 2 + window.top.screenY - (h / 2);
            // const x = window.top.outerWidth / 2 + window.top.screenX - (w / 2);
            // // console.log(windowObjectReference);
            // if (windowObjectReference === null || windowObjectReference.closed) {
            //     windowObjectReference = window.open(url, windowName, `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=${w}, height=${h}, top=${y}, left=${x}`);
            //     // setTimeout(function () { window.toSend = windowObjectReference }, 1000)
            //
            // } else if (previousURL !== url) {
            //     if (!windowObjectReference.opener) {
            //         windowObjectReference = window.open(url, windowName, `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=${w}, height=${h}, top=${y}, left=${x}`);
            //
            //     } else {
            //         windowObjectReference.location.href = url;
            //     }
            //
            //     // windowObjectReference = open(url, windowName, `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=${w}, height=${h}, top=${y}, left=${x}`);
            //     windowObjectReference.focus();
            // } else {
            //     windowObjectReference.close();
            // }
            //
            // // window.toSend = window.opener;
            // previousURL = url;
            //
            //
            // return $(this);
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

                        openRequestedSingleTab(link.href, 'gametechPopup');
                        event.preventDefault();
                        // if (MobileEsp.DetectIos()) {
                        //     // windowObjectReference = open(link.href, 'gametechPopup');
                        //     window.location.href = link.href;
                        //     event.preventDefault();
                        // } else if (MobileEsp.DetectAndroid()) {
                        //     // windowObjectReference = window.open(link.href, 'gametechPopup');
                        //     window.location.href = link.href;
                        //     event.preventDefault();
                        // } else {
                        //     openRequestedSingleTab(link.href, 'gametechPopup');
                        //     event.preventDefault();
                        // }


                    },
                    false
                );
            }

            // console.log(previousURL);

            $('[data-search]').on('keyup', function () {
                var searchVal = $(this).val();
                var filterItems = $('[data-filter-item]');

                if (searchVal != '') {
                    filterItems.addClass('hidden');
                    $('[data-filter-item][data-filter-name*="' + searchVal.toLowerCase() + '"]').removeClass('hidden');
                } else {
                    filterItems.removeClass('hidden');
                }
            });
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
        {{--        {{ dd($refill) }}--}}
        @if($refill)
        $(document).ready(function () {

            Swal.fire({
                title: "{{ __('app.promotion.can') }}",
                html: "{{ __('app.promotion.word') }} {{ $refill->value }} {!!  __('app.promotion.word2') !!}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __('app.promotion.yes') }}',
                cancelButtonText: '{{ __('app.promotion.no') }}',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '{{ route('customer.promotion.index') }}';
                } else {
                    axios.post(`{{ route('customer.promotion.cancel') }}`).then(response => {
                        if (response.data.success) {
                            Toast.fire({
                                icon: 'warning',
                                title: '{{ __('app.promotion.no2') }}'
                            })
                        }
                    }).catch(err => [err]);

                }
            })

        });
        @endif
    </script>
@endpush