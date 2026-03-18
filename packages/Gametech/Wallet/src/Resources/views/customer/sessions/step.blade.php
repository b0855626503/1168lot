@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bs-stepper/dist/css/bs-stepper.min.css"/>
    <style>
        .mt-3px {
            margin-top: 3px;
        }
        @media (max-width: 520px) {
            .bs-stepper-header {
                margin: 0 -10px;
                text-align: center;
                display: none;
            }
        }
    </style>
@endpush

<div id="stepperForm" class="bs-stepper linear">
    <div class="bs-stepper-header" role="tablist">
        <div class="step" data-target="#test-form-1">
            <button type="button" class="step-trigger" role="tab" id="stepperFormTrigger1" aria-controls="test-form-1"
                    aria-selected="true">
                <span class="bs-stepper-circle">1</span>
                <span class="bs-stepper-label text-danger">ตรวจสอบเบอร์</span>
            </button>
        </div>
        <div class="line"></div>
        <div class="step" data-target="#test-form-2">
            <button type="button" class="step-trigger" role="tab" id="stepperFormTrigger2" aria-controls="test-form-2"
                    aria-selected="false" disabled="disabled">
                <span class="bs-stepper-circle">2</span>
                <span class="bs-stepper-label text-danger">ข้อมูลธนาคาร</span>
            </button>
        </div>
        <div class="line"></div>
        <div class="step" data-target="#test-form-3">
            <button type="button" class="step-trigger" role="tab" id="stepperFormTrigger3" aria-controls="test-form-3"
                    aria-selected="false" disabled="disabled">
                <span class="bs-stepper-circle">3</span>
                <span class="bs-stepper-label text-danger">ข้อมูลส่วนตัว</span>
            </button>
        </div>
    </div>
    <div class="bs-stepper-content mb-5">
        <form method="POST" action="{{ route('customer.session.register') }}" class="needs-validation"
              @submit.prevent="onSubmit">
            <div id="test-form-1" role="tabpanel" class="content fade bs-stepper-pane"
                 aria-labelledby="stepperFormTrigger1">
                @csrf
                @if($id)
                    <div id="zone-contributor">
                        <input type="hidden" id="upline" name="upline" value="{!! $id !!}">
                        <div class="form-group my-2">
                            <div>
                                <label> ผู้แนะนำ </label>
                                <div class="el-input my-1">
                                    <i class="fas fa-hands-helping"></i>
                                    <input autocomplete="off" class="inputstyle" readonly value="{!! $contributor !!}" type="text">
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="x-hr-border-glow my-0">
                @endif

                <div class="form-row">
                    <div class="form-group my-2 col-md-10">
                        <div>
                            <div class="el-input my-1">
                                <i class="fal fa-mobile-alt"></i>
                                <input autocomplete="off" class="inputstyle"
                                       id="tel"
                                       name="tel"
                                       data-vv-as="&quot;เบอร์โทร&quot;"
                                       placeholder="เบอร์โทรศัพท์"
                                       value="{{ old('tel') }}"
                                       v-validate="'required|min:10'"
                                       :class="[errors.has('tel') ? 'is-invalid' : '']"
                                       data-inputmask="'mask': '(999)-999-9999'">
                            </div>
                            <span class="control-error text-warning"
                                  v-if="errors.has('tel')">@{{ errors.first('tel') }}</span>
                        </div>
                    </div>
                    <div class="form-group my-2 col-md-2">
                        <button class="loginbtn otp-request mt-3px"><span id="timer">OTP</span></button>
                    </div>
                </div>
                <div class="form-group my-2">
                    <div>
                        <div class="el-input my-1">
                            <i class="fal fa-mobile-alt"></i>
                            <input autocomplete="off" class="inputstyle"
                                   id="otp"
                                   name="otp" maxlength="5"
                                   data-vv-as="&quot;OTP&quot;"
                                   placeholder="รหัส OTP"
                                   value="{{ old('otp') }}"
                                   v-validate="'required|min:5|numeric'"
                                   :class="[errors.has('otp') ? 'is-invalid' : '']">
                        </div>
                        <span class="control-error text-warning"
                              v-if="errors.has('otp')">@{{ errors.first('otp') }}</span>

                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <button class="btn btn-lg btn-outline-primary btn-prev-form invisible">ย้อนกลับ</button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-lg btn-outline-primary btn-check-mobile float-right">ถัดไป</button>
                    </div>
                </div>

            </div>
            <div id="test-form-2" role="tabpanel" class="content fade bs-stepper-pane"
                 aria-labelledby="stepperFormTrigger2">

                <div id="zone-acc">

                    <div class="form-group my-2">
                        <div>
                            <label> ธนาคาร *</label>
                            <div class="el-input my-1">
                                <i class="fal fa-university"></i>
                                <select class="inputstyle" id="bank" name="bank"
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
                    </div>

                    <div class="form-group my-2">
                        <div>
                            <label> เลขบัญชี *</label>
                            <div class="el-input my-1">
                                <i class="fal fa-money-check-alt"></i>
                                <input autocomplete="off" class="inputstyle" id="acc_no"
                                       data-vv-as="&quot;เลขที่บัญชี&quot;"
                                       value="{{ old('acc_no') }}"
                                       v-validate="'required|min:5|numeric'"
                                       :class="[errors.has('acc_no') ? 'is-invalid' : '']"
                                       name="acc_no"
                                       placeholder="เลขบัญชี ธนาคาร ถ้าเลือกธนาคาร TW กรอกเป็นเบอร์ TW"
                                       type="text">
                            </div>
                            <span class="control-error text-warning"
                                  v-if="errors.has('acc_no')">@{{ errors.first('acc_no') }}</span>

                        </div>
                    </div>

                    <div class="form-group my-2 wallet_id">
                        <div>
                            <label> Wallet ID</label>
                            <div class="el-input my-1">
                                <i class="fal fa-wallet"></i>
                                <input autocomplete="off" class="inputstyle" id="wallet_id"
                                       v-validate="'max:20'"
                                       data-vv-as="&quot;Wallet ID&quot;"
                                       value="{{ old('wallet_id') }}"
                                       :class="[errors.has('wallet_id') ? 'is-invalid' : '']"
                                       name="wallet_id"
                                       placeholder="ระบุ Wallet ID สำหรับการเติมผ่าน True Money" type="text">
                            </div>
                            <span class="control-error text-warning" v-if="errors.has('wallet_id')">@{{ errors.first('wallet_id') }}</span>

                        </div>
                    </div>

                    <p class="wallet_id text-center">Wallet id ต้องตรงใน TrueMoney Wallet
                        เท่านั้นใส่ไม่ตรงกันเครดิตจะไม่เข้า</p>
                    <p class="wallet_id text-center">* ไม่จำเป็นต้องกรอก ถ้าไม่ได้ตั้ง Wallet ID ไว้
                        หรือกรอกเป็นเบอร์โทร</p>
                    <p class="tw" style="display:none">ลูกค้าที่เลือกธนาคารเป็น True Wallet
                        ต้องกรอกเลขที่บัญชีเป็นเบอร์โทร และต้องตรงกับเบอร์โทรศัพท์</p>
                </div>

                <hr class="x-hr-border-glow my-0">

                <div id="zone-user">

                    <div class="form-group my-2">
                        <div>
                            <label> ชื่อ *</label>
                            <div class="el-input my-1">
                                <i class="fal fa-user"></i>
                                <input autocomplete="off" class="inputstyle" id="firstname"
                                       name="firstname"
                                       v-validate="'required'"
                                       :class="[errors.has('firstname') ? 'is-invalid' : '']"
                                       data-vv-as="&quot;firstname&quot;"
                                       value="{{ old('firstname') }}"
                                       placeholder="ชื่อ" type="text">

                            </div>
                        </div>
                    </div>

                    <div class="form-group my-2">
                        <div>
                            <label> นามสกุล *</label>
                            <div class="el-input my-1">
                                <i class="fal fa-user"></i>
                                <input autocomplete="off" class="inputstyle"
                                       name="lastname"
                                       v-validate="'required'"
                                       value="{{ old('lastname') }}"
                                       :class="[errors.has('lastname') ? 'is-invalid' : '']"
                                       id="lastname" placeholder="นามสกุล" type="text">
                            </div>
                        </div>
                    </div>

                    <div class="form-group my-2">
                        <div>
                            <label> Line Id</label>
                            <div class="el-input my-1">
                                <i class="fab fa-line"></i>
                                <input autocomplete="off" class="inputstyle" id="lineid"
                                       name="lineid"
                                       data-vv-as="&quot;ไอดีไลน์&quot;"
                                       value="{{ old('lineid') }}"
                                       :class="[errors.has('lineid') ? 'is-invalid' : '']"
                                       placeholder="ไอดีไลน์" type="text">
                            </div>
                        </div>
                    </div>


                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <button class="btn btn-lg btn-outline-primary btn-prev-form">ย้อนกลับ</button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-lg btn-outline-primary btn-next-form float-right">ถัดไป</button>
                    </div>
                </div>


            </div>
            <div id="test-form-3" role="tabpanel" class="content fade bs-stepper-pane"
                 aria-labelledby="stepperFormTrigger3">

                <div id="zone-info">
                    <div class="form-group my-2">
                        <div>
                            <label> รหัสผู้ใช้งาน *</label>
                            <div class="el-input my-1">
                                <i class="fal fa-user-alt"></i>
                                <input autocomplete="off"
                                       data-vv-as="&quot;User ID&quot;"
                                       class="inputstyle text-lowercase" id="user_name"
                                       name="user_name" maxlength="10"
                                       placeholder="User Name ไม่เกิน 5-10 ตัวอักษร"
                                       value="{{ old('user_name') }}"
                                       v-validate="'required|min:5|max:10'"
                                       :class="[errors.has('user_name') ? 'is-invalid' : '']"
                                       type="text">
                            </div>
                            <span class="control-error text-warning" v-if="errors.has('user_name')">@{{ errors.first('user_name') }}</span>
                            <p class="text-center">ต้องไม่ใช้ข้อมูลเดียวกับเบอร์โทร เป็นตัวเลขและตัวอักษรอังกฤษเล็ก
                                a-z</p>
                        </div>
                    </div>

                    <div class="form-group my-2">
                        <div>
                            <label> รหัสผ่าน *</label>
                            <div class="el-input my-1">
                                <i class="fal fa-lock"></i>
                                <input autocomplete="off"
                                       data-vv-as="&quot;Password&quot;"
                                       class="inputstyle" id="password"
                                       v-validate="'required|min:6'"
                                       value="{{ old('password') }}"
                                       :class="[errors.has('password') ? 'is-invalid' : '']"
                                       name="password" placeholder="รหัสผ่าน" type="password" ref="password">

                            </div>
                            <span class="control-error text-warning" v-if="errors.has('password')">@{{ errors.first('password') }}</span>
                        </div>
                    </div>

                    <div class="form-group my-2">
                        <div>
                            <label> ยืนยันรหัสผ่าน *</label>
                            <div class="el-input my-1">
                                <i class="fal fa-lock"></i>
                                <input autocomplete="off"
                                       class="inputstyle" id="password_confirm"
                                       v-validate="'required|min:6|confirmed:password'"
                                       :class="[errors.has('password_confirm') ? 'is-invalid' : '']"
                                       name="password_confirm"
                                       placeholder="ยืนยันรหัสผ่าน"
                                       value="{{ old('password_confirm') }}"
                                       type="password">
                            </div>
                            <span class="control-error text-warning" v-if="errors.has('password_confirm')">@{{ errors.first('password_confirm') }}</span>
                        </div>
                    </div>
                </div>

                <hr class="x-hr-border-glow my-0">

                @if($config->seamless == 'Y')

                    <div class="form-group my-2">
                        <div>
                            <label> โปรโมชั่น</label>
                            <div class="el-input my-1">
                                <i class="fal fa-gift"></i>
                                <select class="inputstyle" id="promotion" name="promotion"
                                        v-validate="'required'"
                                        :class="[errors.has('promotion') ? 'is-invalid' : '']">
                                    <option value="N">ไม่รับโปรโมชั่น</option>
                                    <option value="Y">รับโปรโมชั่น</option>
                                </select>
                            </div>
                        </div>
                    </div>

                @else

                    @if($config->multigame_open == 'N')

                        <div class="form-group my-2">
                            <div>
                                <label> โปรโมชั่น</label>
                                <div class="el-input my-1">
                                    <i class="fal fa-gift"></i>
                                    <select class="inputstyle" id="promotion" name="promotion"
                                            v-validate="'required'"
                                            :class="[errors.has('promotion') ? 'is-invalid' : '']">
                                        <option value="N">ไม่รับโปรโมชั่น</option>
                                        <option value="Y">รับโปรโมชั่น</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    @endif

                @endif

                <div class="form-group my-2">
                    <div>
                        <label> รู้จักเราจาก</label>
                        <div class="el-input my-1">
                            <i class="fas fa-asterisk"></i>
                            <select class="inputstyle" id="refer" name="refer"
                                    v-validate="'required'"
                                    data-vv-as="&quot;รู้จักเราจาก&quot;"
                                    :class="[errors.has('refer') ? 'is-invalid' : '']">
                                @foreach($refers as $i => $refer)
                                    <option value="{{ $refer->code }}">{{ $refer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <button id="btnsubmit" class="btn btn-primary btn-block" style="border: none" type="submit"><i
                            class="fas fa-user-plus"></i> สมัครสมาชิก
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>


@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bs-stepper/dist/js/bs-stepper.js"></script>
    <script src="{{ asset('vendor/inputmask/jquery.inputmask.js') }}"></script>
    <script>
        let timerOn = true;

        function timer(remaining) {
            $('.otp-request').prop('disabled', true);
            // $('#timer').css('display', 'block');
            var m = Math.floor(remaining / 60);
            var s = remaining % 60;

            m = m < 10 ? '0' + m : m;
            s = s < 10 ? '0' + s : s;
            document.getElementById('timer').innerHTML = m + ':' + s;
            remaining -= 1;

            if (remaining >= 0 && timerOn) {
                setTimeout(function () {
                    timer(remaining);
                }, 1000);
                return;
            }

            if (!timerOn) {
                // Do validate stuff here
                // alert('test');
                return;
            }

            // Do timeout stuff here
            $('.otp-request').prop('disabled', false);
            document.getElementById('timer').innerHTML = 'OTP';
            // $('#dvtimer').css('display', 'none');
            // alert('OTP หมดอายุโปรด ทำรายการใหม่');
        }

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

        function isNumeric(value) {
            return /^-?\d+$/.test(value);
        }

        $(document).ready(function () {
            markmobile()
            // window.stepperForm = new Stepper(document.querySelector('.bs-stepper'));

            var stepperFormEl = document.getElementById('stepperForm');
            window.stepperForm = new Stepper(stepperFormEl, {
                animation: true
            })

            // window.stepperForm = new Stepper(document.querySelector('.bs-stepper'));
            var btnNextList = [].slice.call(document.querySelectorAll('.btn-next-form'));
            var btnPrevList = [].slice.call(document.querySelectorAll('.btn-prev-form'));
            var stepperPanList = [].slice.call(stepperFormEl.querySelectorAll('.bs-stepper-pane'));
            var inputTelForm = document.getElementById('tel');
            var inputOtpForm = document.getElementById('otp');

            var inputBankForm = document.getElementById('bank');
            var inputAccForm = document.getElementById('acc_no');
            var inputNameForm = document.getElementById('firstname');
            var inputSurForm = document.getElementById('lastname');

            var inputIDForm = document.getElementById('user_name');
            var inputPassForm = document.getElementById('password');
            var inputPassConForm = document.getElementById('password_confirm');

            var form = stepperFormEl.querySelector('.bs-stepper-content form');

            // var gonextstep = false;

            btnNextList.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    window.stepperForm.next();
                })
            })
            btnPrevList.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    window.stepperForm.previous();
                })
            })

            {{--function axiosTest() {--}}
            {{--    return new Promise((resolve, reject) => {--}}
            {{--        axios.post(`{{ route('customer.verify.mobile') }}`, {--}}
            {{--            mobile: inputTelForm.value,--}}
            {{--            otp: inputOtpForm.value,--}}
            {{--        }).then(res => {--}}
            {{--            resolve(res.data.success)--}}
            {{--        }).catch(err => reject(err))--}}
            {{--    })--}}
            {{--}--}}

            // const fetchResult = async () => {
            //     const result = await axiosTest()
            //
            //     return result
            // }
            // async function fetchResult(gonextstep) {
            //     let success = await axiosTest()
            //     if (success) {
            //         gonextstep = true;
            //     }
            // }

            stepperFormEl.addEventListener('show.bs-stepper', function (event) {
                form.classList.remove('was-validated');
                // form2.classList.remove('was-validated');
                // form3.classList.remove('was-validated');
                // console.log('event');
                // console.log(event);
                var nextStep = event.detail.indexStep;
                var currentStep = nextStep;

                if (currentStep > 0) {
                    currentStep--
                }

                var stepperPan = stepperPanList[currentStep];

                if (stepperPan.getAttribute('id') === 'test-form-1') {
                    if (!inputTelForm.value.length || !inputOtpForm.value.length) {
                        event.preventDefault();
                        form.classList.add('was-validated');
                    }

                }

                if (stepperPan.getAttribute('id') === 'test-form-2') {
                    if (!inputBankForm.value.length || !inputAccForm.value.length || !inputNameForm.value.length || !inputSurForm.value.length) {
                        event.preventDefault();
                        form.classList.add('was-validated');
                    }
                }

                if (stepperPan.getAttribute('id') === 'test-form-3') {
                    if (!inputIDForm.value.length || !inputPassForm.value.length || !inputPassConForm.value.length) {
                        event.preventDefault();
                        form.classList.add('was-validated');
                    }
                }

                // if ((stepperPan.getAttribute('id') === 'test-form-1' && !inputTelForm.value.length) ||
                //     (stepperPan.getAttribute('id') === 'test-form-2' && !inputOtpForm.value.length)) {
                //     event.preventDefault();
                //     form.classList.add('was-validated');
                // }

                console.warn(currentStep);
                console.warn(event.detail.indexStep);
            })

            // var stepperEl = document.getElementById('stepper1');
            // stepperFormEl.addEventListener('show.bs-stepper', function (event) {
            //     // You can call prevent to stop the rendering of your step
            //     // event.preventDefault()
            //
            //     console.warn(event.detail.indexStep)
            // })

            stepperFormEl.addEventListener('shown.bs-stepper', function (event) {
                // console.warn('shown');
                // console.warn(event.detail.indexStep);

                {{--if (event.detail.indexStep === 1) {--}}
                {{--    event.preventDefault();--}}
                {{--    axios.post(`{{ route('customer.verify.request_otp') }}`, {--}}
                {{--        mobile: $('#tel').val()--}}
                {{--    }).then(response => {--}}
                {{--        $('#refer').text('รหัสอ้างอิง : ' + response.data.data.refer);--}}
                {{--        timer(response.data.data.minute);--}}
                {{--    }).catch(err => [err]);--}}
                {{--}--}}
            });

            $('.otp-request').on('click', function (event) {
                event.preventDefault();
                if (inputTelForm.value.length !== 10) return;
                axios.post(`{{ route('customer.verify.request_otp') }}`, {
                    mobile: $('#tel').val()
                }).then(response => {

                    if (response.data.success === true) {
                        Swal.fire({
                            icon: 'success',
                            title: response.data.message
                        });
                        timer(response.data.data.minute)
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: response.data.message
                        });
                    }


                }).catch(err => [err]);
            });

            $('.btn-check-mobile').on('click', function (event) {
                if (!inputTelForm.value.length || !inputOtpForm.value.length || !isNumeric(inputOtpForm.value)) {
                    event.preventDefault();
                    form.classList.add('was-validated');
                } else {
                    axios.post(`{{ route('customer.verify.mobile') }}`, {
                        mobile: inputTelForm.value,
                        otp: inputOtpForm.value,
                    }).then(response => {

                        if (response.data.success === true) {
                            window.stepperForm.next();
                        }else{

                            event.preventDefault();
                            Swal.fire({
                                icon: 'warning',
                                title: response.data.message
                            });
                        }

                    }).catch(err => [err]);
                }

            });

        });


    </script>

@endpush
{{--@endonce--}}
