@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('back')
    <a class="nav-link p-2 text-light mx-auto hand-point" href="{{ route('customer.home.index') }}">
        <i class="fas fa-chevron-left"></i> กลับ</a>

@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">

                <div class="row">
                    <div class="col-6">
                        <a class="btn btn-trans-light  btn-block">โยกเข้าเกมส์ <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="col-6">
                        <a class="btn btn-trans btn-block" href="/member/transfer/wallet">
                            <i class="fas fa-arrow-left"></i>โยกเข้ากระเป๋า</a></div>
                </div>
                <br>
                <div class="card text-light card-trans">
                    <div class="card-body py-3 px-2">

                        <div class="row">
                            <div class="col-sm-12 wallet">
                                <h4 class="wallet-heading">MY WALLET</h4>
                                <div style="opacity: 1;">
                                    <span class="wallet-money">฿ </span>
                                    <span class="wallet-balance text-color-fixed">{{ $profile->balance }}</span>
                                    <div class="text-right">
                                            <span class="point"><i class="fas fa-coins"></i> แต้มสะสม
                                                <span class="text-color-fixed">{{ $profile->point_deposit }}</span> แต้ม </span>
                                        <span class="diamond"><i class="fas fa-gem"></i> เพชรสะสม
                                                <span
                                                    class="text-color-fixed">{{ $profile->diamond }}</span> เพชร </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>


                <section class="content mt-3">


                    <div class="card card-trans">
                        <div class="card-body">

                            <div class="row">
                                <games-slider>
                                    @foreach($games as $game)
                                        <game :item="{{ json_encode($game) }}"></game>
                                    @endforeach
                                </games-slider>
                            </div>
                        </div>
                    </div>

                </section>

                <section class="content mt-3">


                    <div class="card card-trans">
                        <div class="card-body">

                            <div class="row">
                                <div class="col-12">
                                    <form method="POST" action="{{ route('customer.transfer.confirm') }}"
                                          @submit.prevent="onSubmit">
                                        @csrf
                                        <div class="col-12">
                                            <input type="hidden" name="game" id="game">
                                            <p class="text-center text-warning">ระบุจำนวนเงินที่โยก</p>

                                            <div class="form-group">
                                                <div class="input-group mb-3">
                                                    <input
                                                        v-validate="'required|numeric'"
                                                        class="form-control"
                                                        :class="[errors.has('amount') ? 'is-invalid' : '']"
                                                        id="amount" name="amount"
                                                        data-vv-as="&quot;Amount&quot;"
                                                        placeholder="จำนวนเงิน">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">฿</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-center text-warning">
                                                โยกเงินเข้าเกมส์ขั้นต่ำ {{ intval($config->mintransfer) }} บาท</p>
                                            <p class="text-center text-warning">
                                                ต้องกดรับโปรโมชั่นก่อนโยกเงินเข้าเกมนะคะ</p>
                                            <button class="btn btn-primary btn-block shadow-box">ดำเนินการต่อ</button>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </div>

                </section>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/owl/dist/assets/owl.carousel.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/owl/dist/assets/owl.theme.default.css') }}">

@endpush
@push('scripts')
    <script type="text/javascript" src="{{ asset('vendor/owl/dist/owl.carousel.js') }}"></script>

    <script type="text/x-template" id="games-slider-template">
        <div class="owl-carousel owl-theme owl-loaded owl-responsive owl-drag">

            <div class="slider">
                <slot v-for="(game, index) in games" @click="selectTab(game.code)" :key="index"
                      :name="`slide-${parseInt(index) - 1}`">
                </slot>
            </div>


        </div>
    </script>

    <script type="text/x-template" id="game-template">

        <div :data="item.code">
            <img class="d-block mx-auto rounded-circle transfer-slide-img fix-img h-90 w-90"
                 :src="`https://wallet.dumbovip.com/assets/images/icn-game/${item.filepic}`">
            <p class="transfer-slide-name text-center text-color-fixed mb-0" v-text="item.name"></p>
            <p class="mb-0">
            <h4 class="transfer-slide-balance text-center" v-text="item.balance"></h4>
        </div>

    </script>

    <script type="text/javascript">
        (() => {
            Vue.component('games-slider', {
                'template': '#games-slider-template',

                data: function () {
                    return {
                        games: []

                    }
                },
                created: function () {
                    this.games = this.$children;
                },
                mounted: function () {
                    console.log('this');

                    // this.slider.children().each(function (index) {
                    //     $(this).attr('data-position', index);
                    // });
                    // this.$nextTick(function () {
                    //
                    //     this.slider.children().children().children().on('click', function (e) {
                    //         console.log($(this).children('div'));
                    //         console.log($(this).children('div')[0].key);
                    //         // console.log(game.code);
                    //         this_this.trigger('to.owl.carousel', [$(this).children('div')[0].dataset.position, 300]);
                    //         // document.getElementById("game").value = selectedTab.code;
                    //         // this_this.$emit('selectTab', $(this).children('div')[0].dataset.position)
                    //
                    //     });
                    // })


                    // this.slider.children().on('click',function (e){
                    //     console.log(e);
                    //     // this.slider.trigger('to.owl.carousel', [e.index, 300]);
                    // });


                },
                methods: {
                    selectTab(code) {
                        // console.log(this.games);
                        // console.log(this.game.code);
                        console.log('ev1 ' + code);
                        console.log('ev2 ' + this.$refs.game.code);
                        console.log('ev3 ' + this.games);
                        // document.getElementById("game").value = gamecode;
                    },

                }
            })

            Vue.component('game', {
                'template': '#game-template',
                props: {
                    item: {}
                },
                data: function () {
                    return {}
                },
                mounted: function () {
                    var this_this = this;
                    this.slider = $('.owl-carousel');
                    console.log(this.item.code);
                    this.gamecode = this.item.code

                    this.slider.owlCarousel({
                        items: 5,
                        loop: true,
                        center: true,
                        rewind: false,
                        mouseDrag: true,
                        touchDrag: true,
                        pullDrag: true,
                        freeDrag: false,
                        margin: 10,
                        stagePadding: 0,
                        merge: false,
                        mergeFit: true,
                        autoWidth: false,
                        startPosition: 0,
                        rtl: false,
                        smartSpeed: 250,
                        fluidSpeed: false,
                        dragEndSpeed: false,
                        itemElement: 'div',
                        stageElement: 'div',
                        refreshClass: 'owl-refresh',
                        loadedClass: 'owl-loaded',
                        loadingClass: 'owl-loading',
                        rtlClass: 'owl-rtl',
                        responsiveClass: 'owl-responsive',
                        dragClass: 'owl-drag',
                        itemClass: 'owl-item',
                        stageClass: 'owl-stage',
                        stageOuterClass: 'owl-stage-outer',
                        grabClass: 'owl-grab',

                    });


                },

            })
        })()
    </script>
@endpush
{{--@push('scripts')--}}
{{--    <script type="text/javascript" src="{{ asset('vendor/owl/dist/owl.carousel.js') }}"></script>--}}
{{--    <script>--}}
{{--        jQuery(document).ready(function ($) {--}}

{{--            var $owl = $('.owl-carousel');--}}
{{--            $owl.children().each(function (index) {--}}
{{--                $(this).attr('data-position', index); // NB: .attr() instead of .data()--}}
{{--            });--}}
{{--            $owl.owlCarousel({--}}
{{--                items: 5,--}}
{{--                loop: true,--}}
{{--                center: true,--}}
{{--                rewind: false,--}}

{{--                mouseDrag: true,--}}
{{--                touchDrag: true,--}}
{{--                pullDrag: true,--}}
{{--                freeDrag: false,--}}

{{--                margin: 10,--}}
{{--                stagePadding: 0,--}}

{{--                merge: false,--}}
{{--                mergeFit: true,--}}
{{--                autoWidth: false,--}}

{{--                startPosition: 0,--}}
{{--                rtl: false,--}}

{{--                smartSpeed: 250,--}}
{{--                fluidSpeed: false,--}}
{{--                dragEndSpeed: false,--}}


{{--                itemElement: 'div',--}}
{{--                stageElement: 'div',--}}

{{--                refreshClass: 'owl-refresh',--}}
{{--                loadedClass: 'owl-loaded',--}}
{{--                loadingClass: 'owl-loading',--}}
{{--                rtlClass: 'owl-rtl',--}}
{{--                responsiveClass: 'owl-responsive',--}}
{{--                dragClass: 'owl-drag',--}}
{{--                itemClass: 'owl-item',--}}
{{--                stageClass: 'owl-stage',--}}
{{--                stageOuterClass: 'owl-stage-outer',--}}
{{--                grabClass: 'owl-grab',--}}

{{--            });--}}

{{--            $(document).on('click', '.owl-item>div', function () {--}}
{{--                var $speed = 300;  // in ms--}}
{{--                $owl.trigger('to.owl.carousel', [$(this).data('position'), $speed]);--}}
{{--            });--}}
{{--        });--}}
{{--    </script>--}}
{{--@endpush--}}
