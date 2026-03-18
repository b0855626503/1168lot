@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('back')

    <a class="nav-link p-2 text-light mx-auto hand-point" href="{{ route('customer.credit.index') }}">
        <i class="fas fa-chevron-left"></i> กลับ</a>
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">

                <div class="card card-trans transfer">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <p class="text-warning text-center text-top">เกมที่เลือก</p>
                                <img class="d-block mx-auto rounded-circle img-fluid img-fix-size"
                                     src="{{ $item['game_pic'] }}">
                                <p class="text-color-fixed text-center mb-0  text-top">{{ $item['game_name'] }}</p>
                                <h4 class="transfer-slide-balance text-center text-sub">{{ $item['game_balance'] }}</h4>
                            </div>
                            <div class="col-2 d-flex align-items-center text-white">
                                <div class="mx-auto"><i class="fas fa-arrow-right arrow"></i>
                                </div>
                            </div>
                            <div class="col-5">
                                <p class="text-warning  text-center  text-top">กระเป๋าหลัก</p>
                                <img class="d-block mx-auto rounded-circle img-fluid img-fix-size"
                                     src="{{ $item['wallet'] }}">
                                <p class="text-color-fixed text-center mb-0 text-top">Cashback</p>
                                <h4 class="transfer-slide-balance text-center text-sub">{{ $item['member_balance'] }}</h4>
                            </div>

                        </div>
                        <hr>

                        <div class="row">
                            <div class="col-5">

                            </div>
                            <div class="col-2 d-flex align-items-center">
                                <div class="mx-auto">

                                    <p class="text-center text-top m-0 text-white">เงินที่โยก</p>
                                    <p class="text-center text-color-fixed text-sub m-0">{{ $item['amount'] }} ฿</p>

                                </div>
                            </div>
                            <div class="col-5">

                            </div>
                        </div>

                        <hr>
                        <div class="row ng-star-inserted">
                            <div class="col-6">
                                <a href="{{ url()->previous() }}" class="btn btn-theme btn-lg btn-block shadow-box"
                                   type="button">
                                    <i class="fas fa-times"></i> ยกเลิก
                                </a>
                            </div>
                            <div class="col-6">
                                <form method="POST" action="{{ route('customer.credit.transfer.wallet.confirm') }}"
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
