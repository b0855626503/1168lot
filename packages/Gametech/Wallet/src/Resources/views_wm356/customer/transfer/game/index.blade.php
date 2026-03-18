@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('content')
    <div class="p-1">
        <div class="headsecion">
            <img src="/images/icon/coin.png"> โยกเข้าเกมส์
        </div>
        <div class="ctpersonal mt-4">

            <div class="row">
                <div class="col-6">
{{--                    <a class="btn btn-trans-light btn-block text-white"--}}
{{--                       href="{{ route('customer.transfer.game.index') }}">--}}
{{--                        โยกเข้าเกมส์ <i class="fas fa-arrow-right"></i></a>--}}
                </div>
                <div class="col-6">
                    <a class="btn btn-trans btn-block text-white"
                       href="{{ route('customer.transfer.wallet.index') }}">
                        <i class="fas fa-arrow-left"></i> โยกเข้ากระเป๋า</a>
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

            <form method="POST" action="{{ route('customer.transfer.game.check') }}"
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
                        </tbody>
                    </table>
                    <table>
                        <tbody>
                        <tr>
                            <td class="pb-2">
                                ฿
                            </td>
                            <td class="pb-2">
                                <input required step="0.01"
                                       min="1"
                                       :class="[errors.has('amount') ? 'is-invalid' : '']"
                                       class="inputmain" type="number" placeholder="กรุณากรอกจำนวนเงิน"
                                       id="amount" name="amount"
                                       data-vv-as="&quot;Amount&quot;"
                                       autocomplete="off">
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <br>
                    <p class="text-center text-warning">
                        โยกเข้าเกมส์ ขั้นต่ำ {{ core()->currency($config->mintransfer) }}
                        บาท</p>
                    @if($config->mintransfer_pro != 0)
                        <p class="text-center text-warning">สามารถโยกเข้าเกม
                            ได้เมื่อเงินในเกมเหลือน้อยกว่า {{ core()->currency($config->mintransfer_pro) }}
                            บาท (กรณีมีการรับโปรไปแล้ว)</p>
                    @endif
                    @if($promotions)
                        <p class="text-center text-warning">
                            ต้องกดรับโปรโมชั่นก่อนโยกเงินเข้าเกมนะคะ</p>
                        <table>
                            <tbody>
                            <tr>
                                <td class="pb-2">
                                    ฿
                                </td>
                                <td class="pb-2">
                                    <select class="inputmain"
                                            id="promotion" name="promotion">
                                        <option value="">== เลือกโปรโมชั่น ==</option>
                                        @foreach($promotions as $promotion)
                                            <option
                                                value="{{ $promotion->code }}">{{ $promotion->name_th }}</option>
                                        @endforeach
                                    </select>

                                </td>
                            </tr>
                            </tbody>
                        </table>
                    @endif
                    <button class="moneyBtn"> ดำเนินการต่อ</button>
                </div>
            </form>

        </div>

    </div>

@endsection
