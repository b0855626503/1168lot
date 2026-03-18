@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('back')
    <a class="nav-link p-2 text-light mx-auto hand-point" href="{{ route('customer.home.index') }}">
        <i class="fas fa-chevron-left"></i> กลับ</a>
@endsection


@section('content')
    <div class="container mt-5">
        <h3 class="text-center text-light">ถอนเงิน</h3>
        @if($config->multigame_open == 'Y')
            <p class="text-center text-color-fixed"> กรุณาโยกเงินเข้ากระเป๋าหลักก่อนทำการถอนเงิน</p>
        @else
            <p class="text-center text-color-fixed"> เมื่อแจ้งถอน ยอดเงินเครดิตจะถูกหักออกทันที</p>
        @endif

        <div class="row text-light">

            <div class="col-md-8 offset-md-2 col-sm-12">
                <div class="row">
                    <div class="col-6">
                        <div class="card text-light card-trans">
                            <div class="card-body p-2">
                                <div class="row">
                                    <div class="col-sm-12">
                                        @if($config->multigame_open == 'Y')
                                            <h5 class="content-heading text-center"><i class="fal fa-wallet"></i> กระเป๋าเงิน
                                            </h5>
                                        @else
                                            <h5 class="content-heading text-center"><i class="fal fa-wallet"></i> เครดิตของฉัน
                                            </h5>
                                        @endif
                                        <h5 class="text-color-fixed text-right">{{ $profile->balance }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card text-light card-trans">
                            <div class="card-body p-2">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <h5 class="content-heading text-center"><i class="fal fa-minus-octagon"></i>
                                            ถอนวันนี้</h5>
                                        @if($config->seamless == 'Y')
                                            <h5 class="text-color-fixed text-right">{{ is_null($profile->withdrawSeamless_amount_sum) ? '0.00' : $profile->withdrawSeamless_amount_sum }}</h5>
                                        @else
                                            <h5 class="text-color-fixed text-right">{{ is_null($profile->withdraw_amount_sum) ? '0.00' : $profile->withdraw_amount_sum }}</h5>

                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row text-light">
                    <div class="col-md-12">
                        <div class="card card-trans">
                            <div class="card-body">
                                <div class="justify-content-center" id="withdraw">
                                    <div class="row">
                                        <div class="col-6 offset-3">
                                            {!! (!is_null($profile->bank) ? core()->showImg($profile->bank->filepic,'bank_img','80px','80px','d-block mx-auto rounded-circle img-fix-size ng-star-inserted') : '') !!}
                                            <p class="text-center text-color-fixed mb-0">{{ (!is_null($profile->bank) ? $profile->bank->name_th : '') }}</p>
                                        </div>
                                    </div>
                                    <div class="row my-4">
                                        <div class="col-4 col-sm-4 text-center">
                                            <p class="m-0"><i class="fal fa-money-check-alt fa-2x"></i></p>
                                            <p class="m-0">เลขบัญชี</p>
                                            <p class="text-color-fixed">{{ $profile->acc_no }}</p>
                                        </div>
                                        <div class="col-4 col-sm-4 text-center">
                                            <p class="m-0"><i class="fal fa-user fa-2x"></i></p>
                                            <p class="m-0">ชื่อบัญชี</p>
                                            <p class="text-color-fixed">{{ $profile->name }}</p>
                                        </div>
                                        <div class="col-4 col-sm-4 text-center">
                                            <p class="m-0"><i class="fal fa-mobile-alt fa-2x"></i></p>
                                            <p class="m-0">เบอร์โทรศัพท์</p>
                                            <p class="text-color-fixed">{{ $profile->tel }}</p>
                                        </div>
                                    </div>
                                    <hr>

                                    <form method="POST" action="{{ route('customer.withdraw.store') }}" @submit.prevent="onSubmit">
                                        @csrf
                                        <div class="col-sm-12">
                                            <p class="float-left">กรอกจำนวนเงินที่ต้องการถอน</p>

                                            <div class="form-group">
                                                <div class="input-group mb-3">
                                                    <input required
                                                           {{ ($pro == true ? 'readonly' : '') }}
                                                           type="number"
                                                           step="1"
                                                           min="1"
                                                           class="form-control"
                                                           :class="[errors.has('amount') ? 'is-invalid' : '']"
                                                           id="amount" name="amount"
                                                           data-vv-as="&quot;Amount&quot;"
                                                           placeholder="จำนวนเงิน" autocomplete="off" value="{{ floor($profile->balance) }}">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">฿</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <p class="text-center text-warning">
                                                ยอดถอนขั้นต่ำ {{ $config->minwithdraw }}
                                                บาท</p>
                                            <button class="btn btn-primary btn-block shadow-box">แจ้งถอน
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

@endsection





