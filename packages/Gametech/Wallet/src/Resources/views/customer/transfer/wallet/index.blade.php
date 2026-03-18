@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('content')
    <div class="p-1">
        <div class="headsecion">
            <img src="/images/icon/coin.png"> โยกเข้ากระเป๋า
        </div>
        <div class="ctpersonal mt-4">

            <div class="row">
                <div class="col-6">
                    <a class="btn btn-trans btn-block text-white" href="{{ route('customer.transfer.game.index') }}">
                        โยกเข้าเกมส์ <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="col-6">
{{--                    <a class="btn btn-trans btn-block text-white"--}}
{{--                       href="{{ route('customer.transfer.wallet.index') }}">--}}
{{--                        <i class="fas fa-arrow-left"></i> โยกเข้ากระเป๋า</a>--}}
                </div>
            </div>
            <hr class="x-hr-border-glow my-0">

            <div class="row text-light">
                <div class="col-md-12">
                    <div class="card card-trans">
                        <div class="card-body">

                            <carousel :games="{{ json_encode($games)}}" :items="5" :loop="true" :center="true"
                                      :nav="false" :margin="10"
                                      :responsive="{0:{items:2,nav:false},600:{items:5,nav:false}}"
                                      responsive-base-element=".owl-stage"></carousel>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="x-hr-border-glow my-0">

            <form method="POST" action="{{ route('customer.transfer.wallet.check') }}"
                  @submit.prevent="onSubmit">
                @csrf
                <input type="hidden" name="game" id="game">
                <div class="inboxmain">

                    <table>
                        <tbody>
                        <tr style="border:none">
                            <td class="pt-3 pb-1" style="width:50%">
                                จำนวนเงินที่โยก
                            </td>
                            <td class="pt-3 pb-1 text-right">
                                (บาท)
                            </td>
                        </tr>
                        </tbody></table>
                    <table>
                        <tbody><tr>
                            <td class="pb-2">
                                ฿
                            </td>
                            <td class="pb-2">
                                <input required  step="0.01"
                                       min="1"
                                       :class="[errors.has('amount') ? 'is-invalid' : '']"
                                       class="inputmain" type="number" placeholder="กรุณากรอกจำนวนเงิน"
                                       id="amount" name="amount"
                                       data-vv-as="&quot;Amount&quot;"
                                       autocomplete="off">
                            </td>
                        </tr>
                        </tbody></table>
                    <br>
                    <p class="text-center text-warning">
                        โยกเข้ากระเป๋า ขั้นต่ำ {{ core()->currency($config->mintransferback) }}
                        บาท</p>
                    <button class="moneyBtn"> ดำเนินการต่อ </button>
                </div>
            </form>

        </div>

    </div>

{{--    <div class="container">--}}
{{--        <div class="row">--}}
{{--            <div class="col-md-8 offset-md-2 col-sm-12">--}}

{{--                <div class="row">--}}
{{--                    <div class="col-6">--}}
{{--                        <a class="btn btn-trans btn-block text-white"--}}
{{--                           href="{{ route('customer.transfer.game.index') }}">โยกเข้าเกมส์ <i--}}
{{--                                class="fas fa-arrow-right"></i></a>--}}
{{--                    </div>--}}
{{--                    <div class="col-6">--}}
{{--                        <a class="btn btn-trans-light btn-block text-white">--}}
{{--                            <i class="fas fa-arrow-left"></i> โยกเข้ากระเป๋า</a></div>--}}
{{--                </div>--}}
{{--                <br>--}}
{{--                <div class="card text-light card-trans">--}}
{{--                    <div class="card-body py-3 px-2">--}}

{{--                        <wallet></wallet>--}}

{{--                    </div>--}}
{{--                </div>--}}


{{--                <section class="content mt-3">--}}


{{--                    <div class="card card-trans">--}}
{{--                        <div class="card-body">--}}

{{--                            <div class="col">--}}

{{--                                <carousel :games="{{ json_encode($games)}}" :items="5" :loop="true" :center="true"--}}
{{--                                          :nav="false" :margin="10"--}}
{{--                                          :responsive="{0:{items:2,nav:false},600:{items:5,nav:false}}"--}}
{{--                                          responsive-base-element=".owl-stage"></carousel>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                </section>--}}


{{--                <section class="content mt-3">--}}

{{--                    <div class="card card-trans">--}}
{{--                        <div class="card-body">--}}

{{--                            <div class="row">--}}
{{--                                <div class="col-12">--}}
{{--                                    <form method="POST" action="{{ route('customer.transfer.wallet.check') }}"--}}
{{--                                          @submit.prevent="onSubmit">--}}
{{--                                        @csrf--}}
{{--                                        <div class="col-12">--}}
{{--                                            <input type="hidden" name="game" id="game">--}}
{{--                                            <p class="text-center text-warning">ระบุจำนวนเงินที่โยกเข้ากระเป๋า</p>--}}

{{--                                            <div class="form-group">--}}
{{--                                                <div class="input-group mb-3">--}}
{{--                                                    <input--}}
{{--                                                        required--}}
{{--                                                        type="number"--}}
{{--                                                        min="1"--}}
{{--                                                        class="form-control"--}}
{{--                                                        :class="[errors.has('amount') ? 'is-invalid' : '']"--}}
{{--                                                        id="amount" name="amount"--}}
{{--                                                        data-vv-as="&quot;Amount&quot;"--}}
{{--                                                        placeholder="จำนวนเงิน" autocomplete="off">--}}
{{--                                                    <div class="input-group-prepend">--}}
{{--                                                        <span class="input-group-text">฿</span>--}}
{{--                                                    </div>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                            <p class="text-center text-warning">--}}
{{--                                                โยกเงินออกเกมส์ขั้นต่ำ {{ core()->currency($config->mintransferback) }}--}}
{{--                                                บาท</p>--}}

{{--                                            <button class="btn btn-danger btn-block shadow-box">ดำเนินการต่อ</button>--}}
{{--                                        </div>--}}
{{--                                    </form>--}}

{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                </section>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}

@endsection
