@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')


@section('content')
    <div class="p-1">
        <div class="headsecion">
            <img src="/images/icon/withdraw.png"> {{ __('app.home.withdraw_freecredit') }}
        </div>
        <div class="ctpersonal">
            <form method="POST" action="{{ route('customer.credit.withdraw.store') }}"
                  @submit.prevent="onSubmit">
                @csrf
                <div class="inboxmain">
                    <table>
                        <tbody><tr>
                            <td class="py-3" style="width:50px">
                           <span class="circlered">
                            <img src="{{ Storage::url('bank_img/'.$profile->bank->filepic) }}">
                           </span>
                            </td>
                            <td class="py-3">
                                {{ (!is_null($profile->bank) ? $profile->bank->name_th : '') }}<br>
                                <span class="commentbox">{{ $profile->acc_no }}</span>
                            </td>
                        </tr>
                        </tbody></table>
                    <table>
                        <tbody>
                        <tr>
                            <td class="py-3" style="width:50%">
                                {{ __('app.home.withdraw_credit') }}
                            </td>
                            <td class="py-3 text-right" style="font-weight: 300; font-size: 20px;">
                                {{ $profile->balance_free }}
                            </td>
                        </tr>
                        <tr>
                            <td class="py-3" style="width:50%">
                                {{ __('app.home.withdraw_turn') }}
                            </td>
                            <td class="py-3 text-right" style="font-weight: 300; font-size: 20px;">
                                {{ $turnpro }}
                            </td>
                        </tr>
                        <tr style="border:none">
                            <td class="pt-3 pb-1" style="width:50%">
                                {{ __('app.home.withdraw_amount') }}
                            </td>
                            <td class="pt-3 pb-1 text-right">
                                ({{ __('app.home.withdraw_baht') }})
                            </td>
                        </tr>
                        </tbody></table>
                    <table>
                        <tbody><tr>
                            <td class="pb-2">
                                ฿
                            </td>
                            <td class="pb-2">
                                <input required readonly  step="0.01"
                                       min="1"
                                       :class="[errors.has('amount') ? 'is-invalid' : '']"
                                       class="inputmain" type="number" placeholder="กรุณากรอกจำนวนเงิน"
                                       id="amount" name="amount"
                                       data-vv-as="&quot;Amount&quot;"
                                       autocomplete="off"
                                       value="{{$profile->balance_free}}">
                            </td>
                        </tr>
                        </tbody></table>
                    <br>
                    <p class="text-center text-warning">
                        {{ __('app.home.withdraw_min') }} {{ $config->free_minwithdraw }}
                        {{ __('app.home.withdraw_baht') }}</p>
                    <button class="moneyBtn"> {{ __('app.home.withdraw') }} </button>
                </div>
            </form>
        </div>

    </div>
@endsection





