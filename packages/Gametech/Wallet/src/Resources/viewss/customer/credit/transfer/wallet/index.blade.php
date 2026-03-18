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

                <div class="row">
                    <div class="col-6">
                        <a class="btn btn-trans btn-block text-white"
                           href="{{ route('customer.credit.transfer.game.index') }}">โยกเข้าเกมส์ <i
                                class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="col-6">
                        <a class="btn btn-trans-light btn-block text-white"><i class="fas fa-arrow-left"></i>
                            โยกเข้ากระเป๋า</a></div>
                </div>
                <br>
                <div class="card text-light card-trans">
                    <div class="card-body py-3 px-2">

                        <cashback></cashback>

                    </div>
                </div>


                <section class="content mt-3">


                    <div class="card card-trans">
                        <div class="card-body">

                            <div class="col">

                                <carousel-free :games="{{ json_encode($games)}}" :items="5" :loop="true" :center="true"
                                               :nav="false" :margin="10"
                                               :responsive="{0:{items:2,nav:false},600:{items:5,nav:false}}"
                                               responsive-base-element=".owl-stage"></carousel-free>
                            </div>
                        </div>
                    </div>

                </section>


                <section class="content mt-3">

                    <div class="card card-trans">
                        <div class="card-body">

                            <div class="row">
                                <div class="col-12">
                                    <form method="POST" action="{{ route('customer.credit.transfer.wallet.check') }}"
                                          @submit.prevent="onSubmit">
                                        @csrf
                                        <div class="col-12">
                                            <input type="hidden" name="game" id="game">
                                            <p class="text-center text-warning">ระบุจำนวนเงินที่โยกเข้ากระเป๋า
                                                (Cashback)</p>

                                            <div class="form-group">
                                                <div class="input-group mb-3">
                                                    <input
                                                        required
                                                        type="number"
                                                        min="1"
                                                        class="form-control"
                                                        :class="[errors.has('amount') ? 'is-invalid' : '']"
                                                        id="amount" name="amount"
                                                        data-vv-as="&quot;Amount&quot;"
                                                        placeholder="จำนวน Cashback" autocomplete="off">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">฿</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-center text-warning">
                                                โยก Cashback
                                                ออกเกมส์ขั้นต่ำ {{ core()->currency($config->free_mintransferback) }}
                                                บาท</p>

                                            <button class="btn btn-danger btn-block shadow-box">ดำเนินการต่อ</button>
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
