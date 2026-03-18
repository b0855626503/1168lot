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

                <div class="card card-trans transfer">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <p class="text-warning  text-center  text-top">กระเป๋าหลัก</p>
                                <img class="d-block mx-auto rounded-circle img-fix-size"
                                     src="assets/images/wallet.png">
                                <p class="text-color-fixed text-center mb-0  text-top">Wallet</p>
                                <h4 class="transfer-slide-balance text-center text-sub">{{ $item['member_balance'] }}</h4>
                            </div>
                            <div class="col-1 d-flex align-items-center">
                                <div class="mx-auto"><i class="fas fa-arrow-right arrow"></i>
                                </div>
                            </div>
                            <div class="col">
                                <p class="text-warning text-center text-top">เกมที่เลือก</p>
                                <img class="d-block mx-auto rounded-circle img-fix-size"
                                     src="{{ $item['game_pic'] }}">
                                <p class="text-color-fixed text-center mb-0  text-top">{{ $item['game_name'] }}</p>
                                <h4 class="transfer-slide-balance text-center text-sub">{{ $item['game_balance'] }}</h4>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col">
                                <p class="text-center text-top m-0">เงินที่โยก</p>
                                <p class="text-center text-color-fixed text-sub m-0">{{ $item['amount'] }} ฿</p>
                            </div>
                            <div class="col-1 d-flex align-items-center">
                                <div class="mx-auto"><i class="fas fa-arrow-right arrow"></i>
                                </div>
                            </div>
                            <div class="col">
                                <p class="text-center m-0 text-top">เครดิตที่ได้รับ</p>
                                <p class="text-center text-color-fixed text-sub m-0">{{ $item['total'] }}</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <p class="text-center text-top m-0">เงินที่โยก</p>
                                <p class="text-center text-color-fixed text-sub m-0">{{ $item['amount'] }} ฿</p>
                            </div>
                            <div class="col-1 d-flex align-items-center">
                                <div class="mx-auto">
                                    @if($item['pro_code'] > 0)
                                        <p class="text-center text-top m-0">โบนัสที่ได้</p>
                                        <p class="text-center text-color-fixed text-sub m-0">{{ $item['bonus'] }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="col">
                                <p class="text-center m-0 text-top">เครดิตที่ได้รับ</p>
                                <p class="text-center text-color-fixed text-sub m-0">{{ $item['total'] }}</p>
                            </div>
                        </div>
                        @if($item['pro_code'] > 0)
                            <div class="row">
                                <div class="col align-items-center">
                                    <p class="text-center text-top m-0">จากโปรโมชั่น : {{ $item['pro_name'] }}</p>
                                </div>
                            </div>
                        @endif
                        <hr>
                        <div class="row ng-star-inserted">
                            <div class="col-6">
                                <button class="btn btn-theme btn-lg btn-block shadow-box" type="button">
                                    <i class="fas fa-times"></i> ยกเลิก
                                </button>
                            </div>
                            <div class="col-6">
                                <form method="POST" action="{{ route('customer.transfer.success') }}"
                                      @submit.prevent="onSubmit">
                                    @csrf
                                    <input type="hidden" name="gametoken" value="{{ session('gametoken') }}">
                                    <button class="btn btn-primary btn-success btn-lg btn-block shadow-box"
                                            type="submit">
                                        <i class="fas fa-check"></i> ยืนยัน
                                    </button>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
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
    <script>
        jQuery(document).ready(function ($) {

            var $owl = $('.owl-carousel');
            $owl.children().each(function (index) {
                $(this).attr('data-position', index); // NB: .attr() instead of .data()
            });
            $owl.owlCarousel({
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

            $(document).on('click', '.owl-item>div', function () {
                var $speed = 300;  // in ms
                $owl.trigger('to.owl.carousel', [$(this).data('position'), $speed]);
            });
        });
    </script>
@endpush
