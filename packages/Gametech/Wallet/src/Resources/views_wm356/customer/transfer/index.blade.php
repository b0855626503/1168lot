@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')


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

                            <div class="col">

                                <carousel :games="{{$games}}" :items="5" :loop="true" :center="true"></carousel>

                            </div>
                        </div>
                    </div>

                </section>


                <section class="content mt-3">

                    <div class="card card-trans">
                        <div class="card-body">

                            <div class="row">
                                <div class="col-12">
                                    <form method="POST" action="{{ route('customer.transfer.check') }}"
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
                                                        placeholder="จำนวนเงิน" autocomplete="off">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">฿</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-center text-warning">
                                                โยกเงินเข้าเกมส์ขั้นต่ำ {{ intval($config->mintransfer) }} บาท</p>
                                            @if($promotions)
                                                <p class="text-center text-warning">
                                                    ต้องกดรับโปรโมชั่นก่อนโยกเงินเข้าเกมนะคะ</p>
                                                <div class="form-group">
                                                    <div class="input-group mb-3">
                                                        <select class="form-control" v-validate="'required'"
                                                                :class="[errors.has('promotion') ? 'is-invalid' : '']"
                                                                id="promotion" name="promotion">
                                                            <option value="">== เลือกโปรโมชั่น ==</option>
                                                            @foreach($promotions as $promotion)
                                                                <option
                                                                    value="{{ $promotion->code }}">{{ $promotion->name_th }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @endif
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
