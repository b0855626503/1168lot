{{-- extend layout --}}
@extends('wallet::layouts.app')

{{-- page title --}}
@section('title','')

@section('content')
    <div class="container text-light mt-5">
        <h3 class="text-center text-light"><i class="fas fa-info-circle"></i>
            กรุณากรอกเฉพาะข้อมูลจริงเท่านั้น</h3>
        <p class="text-center text-color-fixed">เพื่อประโยชน์ของตัวท่านเอง</p>
        <div class="row">

            <div class="col-md-6 offset-md-3 col-sm-12">
                <div class="card card-trans profile">


                    <div class="card-body">
                        <form method="POST" action="{{ route('customer.session.register') }}"
                              @submit.prevent="onSubmit">
                            @csrf
                            @if($id)
                                <input type="hidden" id="upline" name="upline" value="{!! $id !!}">

                            @endif
                            {{--                            <input type="hidden" id="firstname" name="firstname">--}}
                            {{--                            <input type="hidden" id="lastname" name="lastname">--}}
                            <div class="card-body" id="zone-acc">

                                <div class="form-group">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fal fa-university"></i>
                            </span>
                                        </div>
                                        <select class="custom-select" id="bank" name="bank"
                                                v-validate="'required'"
                                                :class="[errors.has('bank') ? 'is-invalid' : '']">
                                            <option value="">กรุณาเลือกธนาคาร</option>
                                            @foreach($banks as $i => $bank)
                                                <option
                                                    value="{{ $bank->code }}">{{ $bank->name_th }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fal fa-money-check-alt"></i>
                            </span>
                                        </div>
                                        <input autocomplete="off" class="form-control" id="acc_no"
                                               minlength="5"
                                               data-vv-as="&quot;เลขที่บัญชี&quot;"
                                               value="{{ old('acc_no') }}"
                                               v-validate="'required|min:5|numeric'"
                                               :class="[errors.has('acc_no') ? 'is-invalid' : '']"
                                               name="acc_no"
                                               placeholder="เลขบัญชี ธนาคาร ถ้าเลือกธนาคาร TW กรอกเป็นเบอร์ TW"
                                               type="text">
                                    </div>
                                    <span class="control-error" v-if="errors.has('acc_no')">@{{ errors.first('acc_no') }}</span>

                                </div>

                                <div class="form-group wallet_id">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fal fa-wallet"></i>
                            </span>
                                        </div>
                                        <input autocomplete="off" class="form-control" id="wallet_id"
                                               v-validate="'max:20'"
                                               data-vv-as="&quot;Wallet ID&quot;"
                                               value="{{ old('wallet_id') }}"
                                               :class="[errors.has('wallet_id') ? 'is-invalid' : '']"
                                               name="wallet_id"
                                               placeholder="ระบุ Wallet ID สำหรับการเติมผ่าน True Money" type="text">
                                    </div>
                                    <span class="control-error" v-if="errors.has('wallet_id')">@{{ errors.first('wallet_id') }}</span>

                                </div>
                                {{--                                <button type="button" role="button" id="btnverify" class="btn btn-info btn-block" style="border: none"><i--}}
                                {{--                                        class="fa fa-check"></i> ตรวจสอบบัญชี--}}
                                {{--                                </button>--}}

                                <p class="wallet_id text-center">Wallet id ต้องตรงใน TrueMoney Wallet
                                    เท่านั้นใส่ไม่ตรงกันเครดิตจะไม่เข้า</p>
                                <p class="wallet_id text-center">* ไม่จำเป็นต้องกรอก ถ้าไม่ได้ตั้ง Wallet ID ไว้
                                    หรือกรอกเป็นเบอร์โทร</p>
                                <p class="tw" style="display:none">ลูกค้าที่เลือกธนาคารเป็น True Wallet
                                    ต้องกรอกเลขที่บัญชีเป็นเบอร์โทร และต้องตรงกับเบอร์โทรศัพท์</p>
                            </div>

                            <div class="card-body" id="zone-user">
                                <div class="form-group">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fal fa-user"></i>
                            </span>
                                        </div>
                                        <input autocomplete="off" class="form-control" id="firstname"
                                               name="firstname"
                                               v-validate="'required'"
                                               :class="[errors.has('firstname') ? 'is-invalid' : '']"
                                               data-vv-as="&quot;firstname&quot;"
                                               value="{{ old('firstname') }}"
                                               placeholder="ชื่อ" type="text">
                                        <input autocomplete="off"
                                               name="lastname"
                                               class="form-control"
                                               v-validate="'required'"
                                               value="{{ old('lastname') }}"
                                               :class="[errors.has('lastname') ? 'is-invalid' : '']"
                                               id="lastname" placeholder="นามสกุล" type="text"
                                        >
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fal fa-mobile-alt"></i>
                            </span>
                                        </div>
                                        <input autocomplete="off"
                                               class="form-control" id="tel"
                                               name="tel"
                                               data-vv-as="&quot;เบอร์โทร&quot;"
                                               placeholder="เบอร์โทรศัพท์"
                                               value="{{ old('tel') }}"
                                               v-validate="'required'"
                                               :class="[errors.has('tel') ? 'is-invalid' : '']"
                                               type="text"
                                               data-inputmask="'mask': '(999)-999-9999'"
                                        >
                                    </div>

                                    <span class="control-error"
                                          v-if="errors.has('tel')">@{{ errors.first('tel') }}</span>
                                </div>
                                <div class="form-group">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fab fa-line"></i>
                            </span>
                                        </div>
                                        <input autocomplete="off" class="form-control" id="lineid"
                                               name="lineid"
                                               data-vv-as="&quot;ไอดีไลน์&quot;"
                                               v-validate="'required'"
                                               value="{{ old('lineid') }}"
                                               :class="[errors.has('lineid') ? 'is-invalid' : '']"
                                               placeholder="ไอดีไลน์" type="text">
                                    </div>
                                    <span class="control-error" v-if="errors.has('lineid')">@{{ errors.first('lineid') }}</span>
                                </div>
                                <div class="form-group">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fal fa-user-alt"></i>
                            </span>
                                        </div>
                                        <input autocomplete="off"
                                               data-vv-as="&quot;User ID&quot;"
                                               class="form-control text-lowercase" id="user_name"
                                               name="user_name" maxlength="10"
                                               placeholder="User Name ไม่เกิน 5-10 ตัวอักษร"
                                               value="{{ old('user_name') }}"
                                               v-validate="'required|min:5|max:10'"
                                               :class="[errors.has('user_name') ? 'is-invalid' : '']"
                                               type="text">
                                    </div>

                                    <span class="control-error" v-if="errors.has('user_name')">@{{ errors.first('user_name') }}</span>
                                    <p>ต้องไม่ใช้ข้อมูลเดียวกับเบอร์โทร เป็นตัวเลขและตัวอักษรอังกฤษเล็ก a-z</p>
                                </div>
                                <div class="form-group">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fal fa-lock"></i>
                            </span>
                                        </div>
                                        <input autocomplete="off"
                                               data-vv-as="&quot;Password&quot;"
                                               class="form-control" id="password"
                                               v-validate="'required|min:6'"
                                               value="{{ old('password') }}"
                                               :class="[errors.has('password') ? 'is-invalid' : '']"
                                               name="password" placeholder="รหัสผ่าน" type="password" ref="password">
                                    </div>
                                    <span class="control-error" v-if="errors.has('password')">@{{ errors.first('password') }}</span>

                                </div>
                                <div class="form-group">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fal fa-lock"></i>
                            </span>
                                        </div>
                                        <input autocomplete="off"
                                               class="form-control" id="password_confirm"
                                               v-validate="'required|min:6|confirmed:password'"
                                               :class="[errors.has('password_confirm') ? 'is-invalid' : '']"
                                               name="password_confirm"
                                               placeholder="ยืนยันรหัสผ่าน"
                                               value="{{ old('password_confirm') }}"
                                               type="password">
                                    </div>
                                </div>
                                @if($config->seamless == 'Y')
                                    <div class="form-group">
                                        <div class="input-group mb-3">
                                            <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fal fa-gift"></i>
                            </span>
                                            </div>
                                            <select class="custom-select" id="promotion" name="promotion"
                                                    v-validate="'required'"
                                                    :class="[errors.has('promotion') ? 'is-invalid' : '']">
                                                <option value="N">ไม่รับโปรโมชั่น</option>
                                                <option value="Y">รับโปรโมชั่น</option>

                                            </select>
                                        </div>
                                    </div>
                                @else

                                    @if($config->multigame_open == 'N')
                                        <div class="form-group">
                                            <div class="input-group mb-3">
                                                <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fal fa-gift"></i>
                            </span>
                                                </div>
                                                <select class="custom-select" id="promotion" name="promotion"
                                                        v-validate="'required'"
                                                        :class="[errors.has('promotion') ? 'is-invalid' : '']">
                                                    <option value="N">ไม่รับโปรโมชั่น</option>
                                                    <option value="Y">รับโปรโมชั่น</option>

                                                </select>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                                <div class="form-group">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-asterisk"></i>
                            </span>
                                        </div>
                                        <select class="custom-select" id="refer" name="refer"
                                                v-validate="'required'"
                                                data-vv-as="&quot;รู้จักเราจาก&quot;"
                                                :class="[errors.has('refer') ? 'is-invalid' : '']">
                                            <option value="">* รู้จักเราจากที่ไหน</option>
                                            @foreach($refers as $i => $refer)
                                                <option value="{{ $refer->code }}">{{ $refer->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <recapcha sitekey="{{ config('capcha.website') }}"></recapcha>
                                </div>
                                <div class="row mt-2">
                                    <button id="btnsubmit" class="btn btn-primary btn-block" style="border: none"
                                            disabled><i
                                            class="fas fa-user-plus"></i> สมัครสมาชิก
                                    </button>
                                </div>
                            </div>

                            {{--                            <div class="card-body">--}}
                            {{--                                <p class="control-error" v-if="errors.has('acc_no')">@{{ errors.first('acc_no') }}</p>--}}
                            {{--                                <p class="control-error"--}}
                            {{--                                   v-if="errors.has('tel')">@{{ errors.first('tel') }}</p>--}}
                            {{--                                <p class="control-error" v-if="errors.has('user_name')">@{{ errors.first('user_name') }}</p>--}}
                            {{--                            </div>--}}


                        </form>
                    </div>
                </div>


            </div>
        </div>
    </div>
@endsection
@once
    @push('scripts')
        <script src="https://www.google.com/recaptcha/api.js?onload=vueRecaptchaApiLoaded&render=explicit" async
                defer></script>
        <script type="text/javascript" src="{{ asset('vendor/inputmask/jquery.inputmask.js') }}"></script>
        <script type="module">
            function markmobile() {
                $('#tel').inputmask({
                    alias: 'tel',
                    mask: "(999)-999-9999",
                    removeMaskOnSubmit: true,
                    autoUnmask: true,
                    clearIncomplete: true,
                    clearMaskOnLostFocus: true
                });
            }

            $(document).ready(function () {
                markmobile()

                $('#bank').on('change', function () {
                    if ($('#bank option:selected').val() == '18') {
                        // $('#acc_no_tw').prop('required',true);
                        $('.acc_no_tw').css('display', 'block');
                        $('.tw').css('display', 'block');
                    } else {
                        // $('#acc_no_tw').prop('required',false);
                        $('.acc_no_tw').css('display', 'none');
                        $('.tw').css('display', 'none');
                    }
                });
            });
        </script>
    @endpush
@endonce
