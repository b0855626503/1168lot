@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')


@section('content')

    <div class="p-1">
        <div class="headsecion">
            <i class="fas fa-user-lock"></i> โอนเงิน
        </div>
        <div class="ctpersonal">
            <div class="row text-light">
                <div class="col-md-12">
                    <div class="card card-trans">
                        <div class="card-body">
                            <h5 class="content-heading"><i class="fas fa-sack-dollar"></i> ยอดเงินคงเหลือ</h5>
                            <h5 class="text-color-fixed text-right">{{ $profile->balance }} ฿ </h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row text-light">
                <div class="col-md-12">
                    <div class="card card-trans">
                        <div class="card-body">
                            <h5 class="content-heading"><i class="fas fa-sack-dollar"></i> ยอดเทิร์นปัจจุบัน</h5>
                            <h5 class="text-color-fixed text-right">{{ $turnpro }}  </h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="inboxmain">
                <form method="POST" action="{{ route('customer.money.store') }}"
                      @submit.prevent="onSubmit">
                    @csrf
                <div class="card card-trans">
                    <div class="card-body">
                        <table>
                            <tbody>
                            <tr style="border:none">
                                <td class="pt-3 pb-1" style="width:50%">
                                    เบอร์ผู้รับโอน
                                </td>
                                <td class="pt-3 pb-1 text-right">

                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <table>
                            <tbody>
                            <tr>
                                <td class="pb-2">
                                    <i class="fal fa-mobile-alt"></i>
                                </td>
                                <td class="pb-2">
                                    <input required
                                           :class="[errors.has('to_member_code') ? 'is-invalid' : '']"
                                           class="inputmain" type="text" placeholder="กรอกเบอร์ผู้รับโอน"
                                           id="to_member_code" name="to_member_code"
                                           data-vv-as="&quot;เบอร์ผู้รับโอน&quot;"
                                           autocomplete="off"
                                           value="">
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <table>
                            <tbody>
                            <tr style="border:none">
                                <td class="pt-3 pb-1" style="width:50%">
                                    ยอดเงินที่ต้องการโอน
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
                                           autocomplete="off"
                                           value="0">
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <br>
                        <div class="text-center">
                            <small class="text-center">เงื่อนไขการโอนเงิน ผู้โอน และผู้รับโอน จะต้องไม่ติด ยอดเทิร์น ถึงจะสามารถโอนเงินได้</small>
                        </div>

                        <button class="moneyBtn"> โอนเงิน</button>
                    </div>
                </div>
                </form>
            </div>

        </div>
    </div>
@endsection





