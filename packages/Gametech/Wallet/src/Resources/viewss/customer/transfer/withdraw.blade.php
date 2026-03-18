@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('back')
    <a class="nav-link p-2 text-light mx-auto hand-point" href="{{ route('customer.home.index') }}">
        <i class="fas fa-chevron-left"></i> กลับ</a>
@endsection


@section('content')
    <div class="container mt-5">
        <h3 class="text-center text-light">ถอนแคชแบ็ก</h3>
        <p class="text-center text-color-fixed"> กรุณาโยกแคชแบ็กเข้ากระเป๋าหลักก่อนทำการถอนแคชแบ็ก</p>
        <div class="row text-light">

            <div class="col-md-10 offset-md-1 col-sm-12">
                <div class="row">
                    <div class="col-6">
                        <div class="card text-light card-trans">
                            <div class="card-body p-2">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <h5 class="content-heading text-center"><i class="fal fa-wallet"></i>
                                            กระเป๋าแคชแบ๊ก</h5>
                                        <h5 class="text-color-fixed text-right">{{ $profile->balance_free }}</h5>
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
                                            ถอนแล้ว</h5>
                                        <h5 class="text-color-fixed text-right">{{ is_null($profile->withdraws_free_amount_sum) ? '0.00' : $profile->withdraws_free_amount_sum }}</h5>
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
                                            <img alt=""
                                                 class="d-block mx-auto rounded-circle img-fix-size ng-star-inserted"
                                                 style="width:80px"
                                                 src="https://wallet.dumbovip.com/images/bank/{{ $profile->bank->filepic }}">
                                            <p class="text-center text-color-fixed mb-0">{{ $profile->bank->name_th }}</p>
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
                                    <form method="POST" action="{{ route('customer.credit.store') }}"
                                          @submit.prevent="onSubmit">
                                        @csrf
                                        <div class="col-sm-12">
                                            <p class="float-left">กรอกจำนวนเงินที่ต้องการถอน</p>
                                            <div class="form-group">
                                                <div class="input-group mb-3">
                                                    <input v-validate="'required|numeric:min:1'"
                                                           class="form-control"
                                                           :class="[errors.has('amount') ? 'is-invalid' : '']"
                                                           id="amount" name="amount"
                                                           data-vv-as="&quot;Amount&quot;"
                                                           placeholder="จำนวนเงิน">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">฿</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <button class="btn btn-primary btn-block shadow-box">แจ้งถอนแคชแบ๊ก</button>
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





