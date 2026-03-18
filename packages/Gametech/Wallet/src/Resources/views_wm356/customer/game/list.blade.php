@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@push('styles')
    <style>
        .menu-item {
            min-width: 110px;
            min-height: 80px;
            background: #232323;
            border-radius: 16px;
            box-shadow: 0 2px 12px #0005;
            color: #ffb52a;
            display: flex;
            align-items: center;
            justify-content: center;
            /* ถ้าอยากให้ card มีระยะห่างระหว่างกัน ให้ใช้ gap ที่ .menu-scroll */
            transition: border 0.18s, background 0.15s, color 0.15s;
            text-align: center;
            border: 1px solid #ffb52a;
        / / เพิ่มถ้าอยากให้ hover ชัด
        }

        /* ป้องกัน a ทำลาย flex ของ block */
        .menu-item a {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: inherit;
            text-decoration: none;
            padding: 12px 0 6px 0;
        }

        .menu-item img {
            width: 36px;
            height: 36px;
            margin-bottom: 5px;
            object-fit: contain;
        }

        .menu-item small {
            margin-top: 2px;
            font-size: 1.08rem;
            letter-spacing: 0.3px;
            color: #ffb52a;
            font-weight: 600;
        }

        .menu-item:hover,
        .menu-item.active,
        .menu-item:focus-within {
            background: #111;
            box-shadow: 0 4px 20px #0009;
        }

        .menu-scroll-wrapper {
            width: 100%;
            overflow-x: auto;
            /* optional: hide scrollbar */
            scrollbar-width: none;
        }


        .menu-scroll {
            display: flex;
            gap: 22px;
            /*background: #191919;*/
            padding: 18px 18px 10px 18px;
            width: fit-content;
            margin: 0 auto;

        }

        .menu-scroll-wrapper::-webkit-scrollbar {
            display: none;
        }

        .menu-scroll-wrapper {
            scrollbar-width: none;
        }

        @media (max-width: 600px) {
            .cat {

                padding-right: 0px !important;
                padding-left: 0px !important;

            }

            .menu-scroll {
                width: 100%;
                margin: 0;
                gap: 5px;
                padding: 5px 4px 4px 0px;
            }

            .menu-item {
                min-width: 66px;
                min-height: 48px;
                border-radius: 11px;
                font-size: 0.95rem;
            }

            .menu-item a {
                padding: 7px 0 3px 0;
            }

            .menu-item img {
                width: 26px;
                height: 26px;
                margin-bottom: 2px;
            }

            .menu-item small {
                font-size: smaller;
            }
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 18px;
            background: transparent;
            border-radius: 22px;
            padding: 22px 10px;
            width: 120px;
            align-items: center;

            /* box-shadow: 0 4px 40px #000b; // ใส่ได้ถ้าอยากเด่น */
        }

        .sidebar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: linear-gradient(135deg, #232323 70%, #23231c 100%);
            border-radius: 16px;
            padding: 15px 8px 10px 8px;
            text-decoration: none;
            color: #ffd700;
            font-weight: 700;
            font-size: 1.04rem;
            letter-spacing: 0.5px;
            box-shadow: 0 1.5px 10px #0007;
            transition: background 0.16s,
            color 0.16s,
            box-shadow 0.14s,
            transform 0.14s;
            margin: 0 auto;
            outline: none;
            margin-bottom: 10px;
            border: 1px solid #ffb52a;
        / / เพิ่มถ้าอยากให้ hover ชัด
        }

        .sidebar-item img {
            width: 38px;
            height: 38px;
            margin-bottom: 8px;
            object-fit: contain;
            transition: filter 0.2s, transform 0.13s;
        }

        .sidebar-item span {
            margin-top: 0;
            font-size: 1.01rem;
            letter-spacing: 1px;
            text-shadow: 0 1px 8px #0007;
            white-space: nowrap;
        }

        /* effect hover/active เน้นพื้นหลังทองนวล+ขยายเล็กน้อย */
        .sidebar-item:hover, .sidebar-item.active, .sidebar-item:focus {
            background: linear-gradient(125deg, #ffe066 35%, #fffbe6 100%);
            color: #181818;
            box-shadow: 0 2px 16px #ffe06644, 0 4px 24px #0005;
            transform: translateY(-2px) scale(1.04);
            text-decoration: none;
        }

        .sidebar-item:hover img, .sidebar-item.active img {
            filter: brightness(1.13) drop-shadow(0 0 8px #ffe06644);
            transform: scale(1.06) rotate(-2deg);
        }

        @media (max-width: 600px) {
            .sidebar-menu {
                width: 80px;
                padding: 10px 2px;
                gap: 10px;
            }

            .sidebar-item {
                font-size: 0.93rem;
                padding: 10px 3px 7px 3px;
            }

            .sidebar-item img {
                width: 26px;
                height: 26px;
                margin-bottom: 6px;
            }
        }


        @media (max-width: 991.98px) {
            .x-category-index .-games-list-outer-container.-has-sidebar .-container-fluid {
                padding-left: 15px;
                padding-right: 15px;
            }
        }

        .example {
            width: 100%;
            height: auto;
            min-height: 100%;
            overflow: hidden;
        }

        .example img {
            max-width: 100%;
            max-height: auto;
            position: relative;
            vertical-align: middle;
            left: 50%;
            transform: translate(-50%);
            height: 150px;
            width: 150px;
            object-fit: cover;
        }

        .example-cover img {
            object-fit: cover;
        }



    </style>
@endpush

@section('content')
    <div id="main__content" class="x-ez-games-by-category">
        <section class="x-category-index -v2">
            <div class="-nav-menu-container js-category-menus">
                <div class="container-fluid pr-lg-0">
                    <div class="-nav-menu-container js-category-menus -v2">
                        <div class="x-quick-transaction-buttons js-quick-transaction-buttons">
                            <a class="btn -btn -promotion -vertical" href="{{ route('customer.promotion.index') }}"
                               target="_blank"
                               rel="noopener nofollow">
                            <span class="-ic-wrapper"> <img alt="โปรโมชั่นสุดคุ้ม เพื่อลูกค้าคนสำคัญ"
                                                            class="img-fluid -ic" width="40" height="40"
                                                            src="/assets/wm356/images/ic-quick-transaction-button-promotion.png?v=2"/></span>

                                <span class="-btn-inner-content">
            <span class="-btn-inner-content-title">{{ __('app.home.promotion') }}</span>
        </span>
                            </a>

                            <button
                                    class="btn -btn -deposit x-bg-position-center lazyloaded"
                                    data-toggle="modal"
                                    data-target="#depositModal"
                                    data-bgset="/assets/wm356/images/btn-deposit-bg.png?v=2"
                                    style="background-image: url('/assets/wm356/images/btn-deposit-bg.png?v=2');"
                            >
                            <span class="-ic-wrapper"> <img alt="ฝากเงินง่ายๆ ด้วยระบบออโต้ การันตี 1 นาที"
                                                            class="img-fluid -ic" width="40" height="40"
                                                            src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.png"/></span>

                                <span class="-btn-inner-content">
            <span class="-btn-inner-content-title">{{ __('app.home.refill') }}</span>
        </span>
                            </button>

                            <button
                                    class="btn -btn -withdraw x-bg-position-center lazyloaded"
                                    data-toggle="modal"
                                    data-target="#withdrawModal"
                                    data-bgset="/assets/wm356/images/btn-withdraw-bg.png?v=2"
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
                        <nav class="nav-menu" id="navbarCategory">
                            <div class="menu-scroll-wrapper d-lg-none d-block">
                                <div class="menu-scroll">
                                    @foreach($gameTypes as $gameType)
                                        <div class="menu-item">
                                            <a href="{{ route('customer.cats.list', ['id' => strtolower($gameType->id)]) }}">
                                                <img src="{{ $gameType->icon }}">
                                                <small>{{ __('app.game.'.strtolower($gameType->id)) }}</small>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="sidebar-menu d-lg-block d-none">
                                @foreach($gameTypes as $gameType)
                                    <a href="{{ route('customer.cats.list', ['id' => strtolower($gameType->id)]) }}"
                                       class="sidebar-item">
                                        <img src="{{ $gameType->icon }}" alt="คาสิโน">
                                        <span>{{ __('app.game.'.strtolower($gameType->id)) }}</span>
                                    </a>
                                @endforeach

                            </div>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="-games-list-outer-container -has-sidebar">
                <div class="container-fluid -container-fluid">

                    {{--                    <div class="x-menu-mobile-sidebar-wrapper -v2">--}}
                    {{--                        <div data-menu-sticky="js-sticky-widget">--}}
                    {{--                            <ul class="nav -menu-list">--}}
                    {{--                                --}}{{--                                {{ dd($lists)  }}--}}
                    {{--                                @foreach($lists as $i => $item)--}}
                    {{--                                    <li class="nav-item">--}}
                    {{--                                        <a--}}
                    {{--                                                href="javascript:void(0);"--}}
                    {{--                                                class="nav-link js-side-{{ Str::lower($item->id) }}-btn"--}}
                    {{--                                                aria-label="{{ Str::lower($item->id) }} image provider non{{ Str::lower($item->id) }}"--}}
                    {{--                                                onclick="location.href='{{ route('customer.game.list', ['id' => Str::lower($item->id)]) }}'"--}}
                    {{--                                                data-menu-container=".js-menu-container"--}}
                    {{--                                                data-target-collapse="#collapse-brand"--}}
                    {{--                                                data-target-collapse-mobile="#collapse-mobile-brand"--}}
                    {{--                                                data-button-menu="{{ Str::lower($item->id) }}"--}}
                    {{--                                        >--}}
                    {{--                                            <div class="-menu-wrapper">--}}
                    {{--                                                <picture>--}}
                    {{--                                                    <source type="image/webp"--}}
                    {{--                                                            data-srcset="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"/>--}}
                    {{--                                                    <source type="image/png"--}}
                    {{--                                                            data-srcset="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"/>--}}
                    {{--                                                    <img--}}
                    {{--                                                            alt="{{ Str::lower($item->id) }}"--}}
                    {{--                                                            class="img-fluid -img-btn lazyload"--}}
                    {{--                                                            width="40"--}}
                    {{--                                                            height="40"--}}
                    {{--                                                            data-src="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"--}}
                    {{--                                                            src="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"--}}
                    {{--                                                    />--}}
                    {{--                                                </picture>--}}

                    {{--                                                <picture>--}}
                    {{--                                                    <source type="image/webp"--}}
                    {{--                                                            data-srcset="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"/>--}}
                    {{--                                                    <source type="image/png"--}}
                    {{--                                                            data-srcset="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"/>--}}
                    {{--                                                    <img--}}
                    {{--                                                            alt="{{ Str::lower($item->id) }}"--}}
                    {{--                                                            class="img-fluid -img-btn -hover lazyload"--}}
                    {{--                                                            width="40"--}}
                    {{--                                                            height="40"--}}
                    {{--                                                            data-src="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"--}}
                    {{--                                                            src="{{ Storage::url('icon_img/' . $item->icon).'?v=1' }}"--}}
                    {{--                                                    />--}}
                    {{--                                                </picture>--}}

                    {{--                                                <span class="-menu-text-child">{{ $item->name }}</span>--}}
                    {{--                                            </div>--}}
                    {{--                                        </a>--}}
                    {{--                                    </li>--}}
                    {{--                                @endforeach--}}
                    {{--                            </ul>--}}
                    {{--                        </div>--}}
                    {{--                    </div>--}}

                    <div class="-games-list-container js-game-scroll-container js-game-container">

                        <div class="-games-list-wrapper">
                            <div class="-game-title-wrapper">

                                <div class="-game-title-inner">
                                    <h2 class="-game-title h3 -shimmer">
                                        {{ $game_name }}
                                    </h2>
                                </div>

                                <div class="-game-search-inner">
                                    <form id="frmseach">
                                        <div class="input-group x-search-component -v2">
                                            <input type="text" id="searchKeyword" name="search" value=""
                                                   class="x-form-control form-control -form-search-input"
                                                   placeholder="ค้นหาชื่อเกม..." data-search/>
                                        </div>
                                    </form>
                                </div>

                            </div>

                            <ul class="navbar-nav -slot-provider-page">
                                @foreach($games as $i => $item)
                                    <li class="nav-item" data-filter-item
                                        data-filter-name="{{ strtolower($item['gameName']) }}">
                                        <div
                                                class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert"
                                                data-status="-cannot-entry -untestable">
                                            <div class="-inner-wrapper">


                                                <picture>
                                                    <source type="image/webp"
                                                            data-srcset="{{ $item['image']['vertical'] }}"/>
                                                    <source type="image/png"
                                                            data-srcset="{{ $item['image']['vertical'] }}"/>
                                                    <img
                                                            loading="lazy"
                                                            alt="smm-{{ $id }} cover image png"
                                                            class="img-fluid lazyload -cover-img"
                                                            width="400"
                                                            height="580"
                                                            data-src="{{ $item['image']['vertical'] }}"
                                                            src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                                                    />
                                                </picture>

                                                <div class="-overlay">
                                                    <div class="-overlay-inner">
                                                        <div class="-wrapper-container">
                                                            <a   href="{{ route('customer.game.redirect_single', ['type' => $item['gameType'] , 'provider' => $item['provider'] , 'id' => $item['id']]  ) }}"
                                                               class="js-account-approve-aware -btn -btn-play"
                                                               data-toggle="modal" data-target="#gametechPopup"
                                                               target="gametechPopup">
                                                                <i class="fas fa-play"></i>
                                                                <span class="-text-btn">{{ __('app.home.join') }}</span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="-title">{{ $item['gameName'] }}</div>
                                        </div>
                                    </li>
                                @endforeach


                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="js-replace-seo-section-container">

        </section>
    </div>
@endsection

@push('scripts')
{{--    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"--}}
{{--            integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct"--}}
{{--            crossorigin="anonymous"></script>--}}
    {{--    <script src="{{ asset('js/mdetect.js?v=1') }}"></script>--}}
    <script type="text/javascript">

        function isMobile() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        document.querySelectorAll("a[target='gametechPopup']").forEach(link => {
            link.addEventListener("click", function(e) {
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



        $(document).ready(function () {

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

