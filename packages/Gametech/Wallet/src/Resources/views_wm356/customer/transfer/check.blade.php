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
                                <p class="text-color-fixed text-center mb-0 text-top">Wallet</p>
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
                            <div class="col-6">
                                <a href="{{ url()->previous() }}" class="btn btn-theme btn-lg btn-block shadow-box"
                                   type="button">
                                    <i class="fas fa-times"></i> ยกเลิก
                                </a>
                            </div>
                            <div class="col-6">
                                <form method="POST" action="{{ route('customer.transfer.confirm') }}"
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
