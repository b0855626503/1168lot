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
                                <h5 class="text-success text-center">
                                    <i class="fas fa-check-circle text-success"></i>ทำรายการสำเร็จ
                                </h5>
                                <p class=" text-center mb-0">{{ $item['date_create'] }}</p>
                                <p class=" text-center mb-0">รหัสอ้างอิง {{ $item['invoice'] }}</p></div>
                        </div>
                        <hr>

                        <div class="row">
                            <div class="col">
                                <p class="text-warning  text-center  text-top">กระเป๋าหลัก</p>
                                <img class="d-block mx-auto rounded-circle img-fix-size"
                                     src="assets/images/wallet.png">
                                <p class="text-color-fixed text-center mb-0 text-top">Wallet</p>

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
                            </div>
                        </div>
                        <hr>

                        <div class="row">
                            <div class="col">
                                <p class="text-center text-top m-0 text-white">เงินที่โยก</p>
                                <p class="text-center text-color-fixed text-sub m-0">{{ $item['amount'] }} ฿</p>
                            </div>
                            <div class="col-2 d-flex align-items-center">
                                <div class="mx-auto">
                                    @if($item['pro_code'] > 0)
                                        <p class="text-center text-top m-0 text-white">โบนัสที่ได้</p>
                                        <p class="text-center text-color-fixed text-sub m-0">{{ $item['bonus'] }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="col">
                                <p class="text-center m-0 text-top text-white">เครดิตที่ได้รับ</p>
                                <p class="text-center text-color-fixed text-sub m-0">{{ $item['total'] }}</p>
                            </div>
                        </div>
                        @if($item['pro_code'] > 0)
                            <div class="row">
                                <div class="col align-items-center mt-1">
                                    <p class="text-center text-top m-0 text-primary">จากโปรโมชั่น
                                        : {{ $item['pro_name'] }}</p>
                                </div>
                            </div>
                        @endif
                        <hr>
                        <div class="row ng-star-inserted">
                            <div class="col">
                                <a href="{{ route('customer.home.index' }}"
                                   class="btn btn-theme btn-lg btn-block shadow-box"
                                   type="button">
                                    <i class="fas fa-home"></i> กลับไปสู่หน้าหลัก
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
