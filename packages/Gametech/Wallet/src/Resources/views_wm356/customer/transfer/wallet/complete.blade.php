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
                            <div class="col text-success">
                                <h5 class="text-center">
                                    <i class="fas fa-check-circle text-success"></i>ทำรายการสำเร็จ
                                </h5>
                                <p class=" text-center mb-0">{{ $item['date_create'] }}</p>
                                <p class=" text-center mb-0">รหัสอ้างอิง {{ $item['invoice'] }}</p></div>
                        </div>
                        <hr>

                        <div class="row">
                            <div class="col-5">
                                <p class="text-warning text-center text-top">เกมที่เลือก</p>
                                <img class="d-block mx-auto rounded-circle img-fluid img-fix-size"
                                     src="{{ $item['game_pic'] }}" alt="">
                                <p class="text-color-fixed text-center mb-0  text-top">{{ $item['game_name'] }}</p>
                            </div>
                            <div class="col-2 d-flex align-items-center text-white">
                                <div class="mx-auto"><i class="fas fa-arrow-right arrow"></i>
                                </div>
                            </div>
                            <div class="col-5">
                                <p class="text-warning  text-center  text-top">กระเป๋าหลัก</p>
                                <img class="d-block mx-auto rounded-circle img-fluid img-fix-size"
                                     src="{{ $item['wallet'] }}" alt="">
                                <p class="text-color-fixed text-center mb-0 text-top">Wallet</p>

                            </div>
                        </div>
                        <hr>

                        <div class="row">
                            <div class="col-5">
                                <p class="text-center text-top m-0 text-white">เงินในเกม</p>
                                <p class="text-center text-color-fixed text-sub m-0">{{ $item['game_before'] }} </p>
                            </div>
                            <div class="col-2 d-flex align-items-center">
                                <div class="mx-auto">

                                    <p class="text-center text-top m-0 text-white">เงินที่โยก</p>
                                    <p class="text-center text-color-fixed text-sub m-0">{{ $item['amount'] }} ฿</p>

                                </div>
                            </div>
                            <div class="col-5">
                                <p class="text-center m-0 text-top text-white">Wallet ก่อนโยก</p>
                                <p class="text-center text-color-fixed text-sub m-0">{{ $item['wallet_before'] }} ฿</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-5">
                                <p class="text-center text-top m-0 text-white">คงเหลือ</p>
                                <p class="text-center text-color-fixed text-sub m-0">{{ $item['game_after'] }} </p>
                            </div>
                            <div class="col-2 d-flex align-items-center">
                                <div class="mx-auto">


                                </div>
                            </div>
                            <div class="col-5">
                                <p class="text-center m-0 text-top text-white">Wallet หลังโยก</p>
                                <p class="text-center text-color-fixed text-sub m-0">{{ $item['wallet_after'] }} ฿</p>
                            </div>
                        </div>

                        <hr>
                        <div class="row ng-star-inserted">
                            <div class="col">
                                <a href="{{ route('customer.transfer.wallet.index') }}"
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
