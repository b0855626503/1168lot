{{-- extend layout --}}
@extends('wallet::layouts.app')

{{-- page title --}}
@section('title','')

@push('styles')
    <style>
        .homeregis {
            display: none !important;
        }



    </style>
@endpush

@push('template')
{{--    <script type="text/x-template" id="register-template">--}}
{{--        <div class="register-container sub-page sub-footer vhm-100">--}}
{{--            <div id="block-register" class="register-inner-content card shadow">--}}
{{--                <h4 class="card-title text-center pt-3">{{ __('app.login.register') }}</h4>--}}
{{--                <div class="card-body pt-0 px-0">--}}
{{--                    <div class="theme-form">--}}
{{--                        <form method="POST" ref="form"--}}
{{--                              action="{{ route('customer.session.register') }}"--}}
{{--                              @submit.prevent="onSubmit">--}}
{{--                            @csrf--}}
{{--                            <div id="regForm" class="swiper-container">--}}
{{--                                <div class="swiper-wrapper">--}}
{{--                                    <!-- Slide 1 -->--}}
{{--                                    <div class="swiper-slide regis01 p-2">--}}
{{--                                        <label class="text-content">{{ __('app.register.username') }}</label>--}}
{{--                                        <div class="input-group input-group-lg">--}}
{{--                                            <span class="input-group-text"><i--}}
{{--                                                        class="bi bi-phone-fill bi-1-5x"></i></span>--}}
{{--                                            <input autocomplete="off"--}}
{{--                                                   data-vv-as="{{ __('app.register.username') }}"--}}
{{--                                                   class="form-control x-form-control" id="user_name1"--}}
{{--                                                   name="user_name" maxlength="10" minlength="10"--}}
{{--                                                   placeholder="{{ __('app.register.username_placeholder') }}"--}}
{{--                                                   v-model="form.user_name"--}}
{{--                                                   v-validate="'required'"--}}
{{--                                                   :class="{ 'is-invalid': errors.has('user_name') }"--}}
{{--                                                   type="text">--}}
{{--                                        </div>--}}
{{--                                        <span class="control-error text-warning" v-if="errors.has('user_name')">@{{ errors.first('user_name') }}</span><br>--}}
{{--                                        <small id="phone-status" class="form-text"></small>--}}
{{--                                    </div>--}}

{{--                                    <!-- Slide 2 -->--}}
{{--                                    <div class="swiper-slide regis02 p-2">--}}
{{--                                        <div class="text-danger small">{{ __('app.register.warning') }}</div>--}}
{{--                                        <label>{{ __('app.register.bank') }}</label>--}}
{{--                                        <div class="mb-3" style="font-size: 1.2em;">--}}
{{--                                            <select class="form-control x-form-control" id="bank" name="bank"--}}
{{--                                                    v-model="form.bank"--}}
{{--                                                    v-validate="'required'"--}}
{{--                                                    :class="{ 'is-invalid': errors.has('bank') }">--}}
{{--                                                <option value="">{{ __('app.register.select_bank') }}</option>--}}
{{--                                                @foreach($banks as $i => $bank)--}}
{{--                                                    <option value="{{ $bank->code }}">{{ $bank->name_th }}</option>--}}
{{--                                                @endforeach--}}
{{--                                            </select>--}}
{{--                                        </div>--}}

{{--                                        <div class="mb-3">--}}
{{--                                            <label>{{ __('app.register.bank_account') }}</label>--}}
{{--                                            <div class="input-group input-group-lg">--}}
{{--                                                <span class="input-group-text"><i--}}
{{--                                                            class="bi bi-credit-card-2-front-fill"></i></span>--}}
{{--                                                <input autocomplete="off" class="form-control" id="acc_no"--}}
{{--                                                       minlength="5" maxlength="12"--}}
{{--                                                       name="acc_no"--}}
{{--                                                       placeholder="{{ __('app.register.bank_placeholder') }}"--}}
{{--                                                       v-model="form.acc_no"--}}
{{--                                                       v-validate="'required|min:5|numeric'"--}}
{{--                                                       :class="{ 'is-invalid': errors.has('acc_no') }"--}}
{{--                                                       type="text">--}}
{{--                                            </div>--}}
{{--                                            <em class="fw-light text-content">เลขล้วนไม่มีช่องว่าง</em><br>--}}
{{--                                            <span class="control-error text-warning" v-if="errors.has('acc_no')">@{{ errors.first('acc_no') }}</span>--}}
{{--                                            <small id="account-status" class="form-text"></small>--}}
{{--                                        </div>--}}

{{--                                        <div class="mb-3">--}}
{{--                                            <label>{{ __('app.register.name') }}</label>--}}
{{--                                            <div class="input-group input-group-lg">--}}
{{--                                                <span class="input-group-text"><i--}}
{{--                                                            class="bi bi-person-lines-fill"></i></span>--}}
{{--                                                <input autocomplete="off" class="form-control x-form-control"--}}
{{--                                                       name="firstname" id="firstname"--}}
{{--                                                       v-model="form.firstname"--}}
{{--                                                       v-validate="'required'"--}}
{{--                                                       :class="{ 'is-invalid': errors.has('firstname') }"--}}
{{--                                                       placeholder="{{ __('app.register.name') }}" type="text">--}}
{{--                                            </div>--}}
{{--                                            <span class="control-error text-warning" v-if="errors.has('firstname')">@{{ errors.first('firstname') }}</span>--}}
{{--                                        </div>--}}

{{--                                        <div class="mb-3">--}}
{{--                                            <label>{{ __('app.register.surname') }}</label>--}}
{{--                                            <input autocomplete="off" class="form-control x-form-control"--}}
{{--                                                   name="lastname" id="lastname"--}}
{{--                                                   v-model="form.lastname"--}}
{{--                                                   v-validate="'required'"--}}
{{--                                                   :class="{ 'is-invalid': errors.has('lastname') }"--}}
{{--                                                   placeholder="{{ __('app.register.surname') }}" type="text">--}}
{{--                                            <span class="control-error text-warning" v-if="errors.has('lastname')">@{{ errors.first('lastname') }}</span>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}

{{--                                    <!-- Slide 3 -->--}}
{{--                                    <div class="swiper-slide regis03 p-2">--}}
{{--                                        <div class="mb-3">--}}
{{--                                            <label>{{ __('app.register.password') }}</label>--}}
{{--                                            <div class="input-group input-group-lg">--}}
{{--                                                <span class="input-group-text"><i class="bi bi-key-fill"></i></span>--}}
{{--                                                <input autocomplete="off"--}}
{{--                                                       class="form-control x-form-control input-password"--}}
{{--                                                       id="password1" name="password" ref="password"--}}
{{--                                                       v-model="form.password"--}}
{{--                                                       v-validate="'required|min:6|max:10'"--}}
{{--                                                       :class="{ 'is-invalid': errors.has('password') }"--}}
{{--                                                       placeholder="{{ __('app.register.password') }}"--}}
{{--                                                       minlength="6" maxlength="10" type="password">--}}
{{--                                            </div>--}}
{{--                                            <em class="fw-light">ใช้สำหรับ Login</em><br>--}}
{{--                                            <span class="control-error text-warning" v-if="errors.has('password')">@{{ errors.first('password') }}</span>--}}
{{--                                        </div>--}}

{{--                                        <hr>--}}
{{--                                        <div class="mb-3">--}}
{{--                                            <label>{{ __('app.register.refer') }}</label>--}}
{{--                                            <div class="input-group input-group-lg">--}}
{{--                                                <select class="form-select form-select-lg" id="refer" name="refer"--}}
{{--                                                        v-model="form.refer"--}}
{{--                                                        v-validate="'required'"--}}
{{--                                                        :class="{ 'is-invalid': errors.has('refer') }">--}}
{{--                                                    @foreach($refers as $i => $refer)--}}
{{--                                                        <option value="{{ $refer->code }}">{{ $refer->name }}</option>--}}
{{--                                                    @endforeach--}}
{{--                                                </select>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}
{{--                                </div>--}}

{{--                                <div class="swiper-pagination"></div>--}}
{{--                            </div>--}}

{{--                            <hr>--}}
{{--                            <div class="d-flex w-100 justify-content-between mt-auto pt-3">--}}
{{--                                <button v-if="step > 0" type="button" class="btn btn-secondary w-100 rounded-pill mx-2 preregis" @click="goPrev">--}}
{{--                                    <i class="bi bi-arrow-left"></i> ก่อนหน้า--}}
{{--                                </button>--}}

{{--                                <button--}}
{{--                                        v-if="step < 2"--}}
{{--                                        type="button"--}}
{{--                                        class="btn btn-custom-primary w-100 rounded-pill fw-bolder mx-2 nextregis"--}}
{{--                                        :disabled="disableNextBtn"--}}
{{--                                        @click="goNext">--}}
{{--                                    ถัดไป <i class="bi bi-arrow-right"></i>--}}
{{--                                </button>--}}

{{--                                <button v-if="step === 2" type="submit" class="btn btn-success rounded-pill w-100 mx-2 regisbtn"--}}
{{--                                        :disabled="!canSubmit">--}}
{{--                                    <i class="bi bi-person-plus-fill"></i> สมัครสมาชิก--}}
{{--                                </button>--}}
{{--                            </div>--}}
{{--                        </form>--}}

{{--                        <div class="d-inline-flex w-100 mt-3 justify-content-between">--}}
{{--                            <div></div>--}}
{{--                            <div>--}}
{{--                                <a href="{{ $webconfig->linelink }}" target="_blank"--}}
{{--                                   class="btn btn-link btn-sm text-white">ต้องการความช่วยเหลือ</a>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </script>--}}

<script type="text/x-template" id="register-template">
    <div class="register-container sub-page sub-footer vhm-100">
        <div id="block-register" class="register-inner-content card shadow">
            <h4 class="card-title text-center pt-3">{{ __('app.login.register') }}</h4>
            <div class="card-body pt-0 px-0">
                <div class="theme-form">
                    <form method="POST" ref="form"
                          action="{{ route('customer.session.register') }}"
                          @submit.prevent="onSubmit">
                        @csrf
                        <div id="regForm" class="swiper-container">
                            <div class="swiper-wrapper">
                                <!-- Slide 1 -->
                                <div class="swiper-slide regis01 p-2">
                                    <label class="text-content">{{ __('app.register.username') }}</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="bi bi-phone-fill"></i></span>
                                        <input v-model.lazy="form.user_name"
                                               @input="validatePhone"
                                               type="text"
                                               class="form-control x-form-control"
                                               id="user_name1"
                                               name="user_name"
                                               maxlength="10"
                                               autocomplete="off"
                                               data-vv-as="{{ __('app.register.username') }}"
                                               placeholder="{{ __('app.register.username_placeholder') }}"
                                               v-validate="'required|numeric|length:10'"
                                               :class="{ 'is-invalid': errors.has('user_name') }">
                                    </div>
                                    <span class="control-error text-warning" v-if="errors.has('user_name')">@{{ errors.first('user_name') }}</span><br>
                                    <small id="phone-status" class="form-text"></small>
                                </div>

                                <!-- Slide 2 -->
                                <div class="swiper-slide regis02 p-2">
                                    <div class="text-danger small">{{ __('app.register.warning') }}</div>
                                    <label>{{ __('app.register.bank') }}</label>
                                    <div class="mb-3">
                                        <select v-model="form.bank"
                                                @change="validateBank"
                                                class="form-control x-form-control"
                                                id="bank" name="bank"
                                                v-validate="'required'"
                                                :class="{ 'is-invalid': errors.has('bank') }">
                                            <option value="">{{ __('app.register.select_bank') }}</option>
                                            @foreach($banks as $bank)
                                                <option value="{{ $bank->code }}">{{ $bank->name_th }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <label>{{ __('app.register.bank_account') }}</label>
                                    <div class="input-group input-group-lg mb-3">
                                        <span class="input-group-text"><i class="bi bi-credit-card-2-front-fill"></i></span>
                                        <input v-model.lazy="form.acc_no"
                                               @input="validateBank"
                                               type="text"
                                               class="form-control"
                                               id="acc_no"
                                               name="acc_no"
                                               minlength="5"
                                               maxlength="12"
                                               autocomplete="off"
                                               placeholder="{{ __('app.register.bank_placeholder') }}"
                                               v-validate="'required|numeric|min:5'"
                                               :class="{ 'is-invalid': errors.has('acc_no') }">
                                    </div>
                                    <em class="fw-light">เลขล้วนไม่มีช่องว่าง</em><br>
                                    <span class="control-error text-warning" v-if="errors.has('acc_no')">@{{ errors.first('acc_no') }}</span>
                                    <small id="account-status" class="form-text"></small>

                                    <label>{{ __('app.register.name') }}</label>
                                    <input v-model="form.firstname"
                                           type="text"
                                           class="form-control x-form-control mb-3"
                                           name="firstname"
                                           id="firstname"
                                           autocomplete="off"
                                           placeholder="{{ __('app.register.name') }}"
                                           v-validate="'required'"
                                           :class="{ 'is-invalid': errors.has('firstname') }">
                                    <span class="control-error text-warning" v-if="errors.has('firstname')">@{{ errors.first('firstname') }}</span>

                                    <label>{{ __('app.register.surname') }}</label>
                                    <input v-model="form.lastname"
                                           type="text"
                                           class="form-control x-form-control mb-3"
                                           name="lastname"
                                           id="lastname"
                                           autocomplete="off"
                                           placeholder="{{ __('app.register.surname') }}"
                                           v-validate="'required'"
                                           :class="{ 'is-invalid': errors.has('lastname') }">
                                    <span class="control-error text-warning" v-if="errors.has('lastname')">@{{ errors.first('lastname') }}</span>
                                </div>

                                <!-- Slide 3 -->
                                <div class="swiper-slide regis03 p-2">
                                    <label>{{ __('app.register.password') }}</label>
                                    <div class="input-group input-group-lg mb-3">
                                        <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                        <input v-model="form.password"
                                               type="password"
                                               class="form-control x-form-control"
                                               name="password"
                                               id="password1"
                                               minlength="6"
                                               maxlength="10"
                                               autocomplete="off"
                                               placeholder="{{ __('app.register.password') }}"
                                               v-validate="'required|min:6|max:10'"
                                               :class="{ 'is-invalid': errors.has('password') }">
                                    </div>
                                    <em class="fw-light">ใช้สำหรับ Login</em><br>
                                    <span class="control-error text-warning" v-if="errors.has('password')">@{{ errors.first('password') }}</span>

                                    <label>{{ __('app.register.refer') }}</label>
                                    <select v-model="form.refer"
                                            class="form-control form-select form-select-lg mt-2"
                                            name="refer"
                                            v-validate="'required'"
                                            :class="{ 'is-invalid': errors.has('refer') }">
                                        @foreach($refers as $refer)
                                            <option value="{{ $refer->code }}">{{ $refer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="swiper-pagination"></div>
                        </div>

                        <hr>
                        <div class="d-flex w-100 justify-content-between mt-auto pt-3">
                            <button v-if="step > 0"
                                    type="button"
                                    class="btn btn-secondary w-100 rounded-pill mx-2 preregis"
                                    @click="goPrev">
                                <i class="bi bi-arrow-left"></i> ก่อนหน้า
                            </button>

                            <button v-if="step < 2"
                                    type="button"
                                    class="btn btn-custom-primary w-100 rounded-pill fw-bolder mx-2 nextregis"
                                    :disabled="disableNextBtn"
                                    @click="goNext">
                                ถัดไป <i class="bi bi-arrow-right"></i>
                            </button>

                            <button v-if="step === 2"
                                    type="submit"
                                    class="btn btn-success rounded-pill w-100 mx-2 regisbtn"
                                    :disabled="!canSubmit">
                                <i class="bi bi-person-plus-fill"></i> สมัครสมาชิก
                            </button>
                        </div>
                    </form>

                    <div class="d-inline-flex w-100 mt-3 justify-content-between">
                        <div></div>
                        <div>
                            <a href="{{ $webconfig->linelink }}" target="_blank"
                               class="btn btn-link btn-sm text-white">ต้องการความช่วยเหลือ</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

@endpush

@section('content')

    <register-form></register-form>

@endsection

@push('components')
    <script type="module">
        {{--Vue.component('register-form', {--}}
        {{--    template: '#register-template',--}}
        {{--    data() {--}}
        {{--        return {--}}
        {{--            step: 0,--}}
        {{--            isPhoneValid: false,--}}
        {{--            isBankValid: false,--}}
        {{--            isSubmitting: false,--}}
        {{--            pendingSubmit: false,--}}
        {{--            swiper: null,--}}
        {{--            form: {--}}
        {{--                user_name: '',--}}
        {{--                bank: '',--}}
        {{--                acc_no: '',--}}
        {{--                firstname: '',--}}
        {{--                lastname: '',--}}
        {{--                password: '',--}}
        {{--                refer: ''--}}
        {{--            }--}}
        {{--        }--}}
        {{--    },--}}
        {{--    computed: {--}}
        {{--        canSubmit() {--}}
        {{--            return this.isPhoneValid && this.isBankValid &&--}}
        {{--                this.form.firstname &&--}}
        {{--                this.form.lastname &&--}}
        {{--                this.form.acc_no &&--}}
        {{--                this.form.bank &&--}}
        {{--                this.form.user_name &&--}}
        {{--                this.form.password &&--}}
        {{--                this.form.refer;--}}
        {{--        },--}}
        {{--        disableNextBtn() {--}}
        {{--            return (this.step === 0 && !this.isPhoneValid) || (this.step === 1 && !this.isBankValid);--}}
        {{--        }--}}
        {{--    },--}}
        {{--    watch: {--}}
        {{--        isPhoneValid() {--}}
        {{--            this.triggerPendingSubmit();--}}
        {{--        },--}}
        {{--        isBankValid() {--}}
        {{--            this.triggerPendingSubmit();--}}
        {{--        }--}}
        {{--    },--}}
        {{--    methods: {--}}
        {{--        isNumeric(str) {--}}
        {{--            return /^\d+$/.test(str);--}}
        {{--        },--}}
        {{--        setStatus(elId, message, color) {--}}
        {{--            const el = document.getElementById(elId);--}}
        {{--            if (el) {--}}
        {{--                el.innerText = message;--}}
        {{--                el.style.color = color;--}}
        {{--            }--}}
        {{--        },--}}
        {{--        validatePhone() {--}}
        {{--            const phone = this.form.user_name.trim();--}}
        {{--            if (!this.isNumeric(phone)) {--}}
        {{--                this.setStatus('phone-status', 'กรุณากรอกเฉพาะตัวเลข', 'red');--}}
        {{--                this.isPhoneValid = false;--}}
        {{--                return;--}}
        {{--            }--}}

        {{--            if (phone.length !== 10) {--}}
        {{--                this.setStatus('phone-status', 'กรุณากรอกเบอร์ 10 หลัก', 'red');--}}
        {{--                this.isPhoneValid = false;--}}
        {{--                return;--}}
        {{--            }--}}

        {{--            // เริ่ม validate--}}
        {{--            this.setStatus('phone-status', 'กำลังตรวจสอบ...', 'gray');--}}
        {{--            axios.post("{{ route('customer.check.phone') }}", { username: phone })--}}
        {{--                .then(res => {--}}
        {{--                    if (res.data.exists) {--}}
        {{--                        this.setStatus('phone-status', 'เบอร์นี้มีในระบบแล้ว', 'red');--}}
        {{--                        this.isPhoneValid = false;--}}
        {{--                    } else {--}}
        {{--                        this.setStatus('phone-status', 'สามารถใช้เบอร์นี้ได้', 'green');--}}
        {{--                        this.isPhoneValid = true;--}}
        {{--                    }--}}
        {{--                })--}}
        {{--                .catch(() => {--}}
        {{--                    this.setStatus('phone-status', 'เกิดข้อผิดพลาด', 'gray');--}}
        {{--                    this.isPhoneValid = false;--}}
        {{--                });--}}
        {{--        },--}}
        {{--        validateBank() {--}}
        {{--            const account = this.form.acc_no.trim();--}}
        {{--            const bank = this.form.bank;--}}

        {{--            if (!this.isNumeric(account)) {--}}
        {{--                this.setStatus('account-status', 'กรุณากรอกเลขบัญชีเป็นตัวเลขเท่านั้น', 'red');--}}
        {{--                this.isBankValid = false;--}}
        {{--                return;--}}
        {{--            }--}}

        {{--            if (!bank || account.length < 10) {--}}
        {{--                this.setStatus('account-status', '', '');--}}
        {{--                this.isBankValid = false;--}}
        {{--                return;--}}
        {{--            }--}}

        {{--            this.setStatus('account-status', 'กำลังตรวจสอบ...', 'gray');--}}
        {{--            axios.post("{{ route('customer.check.bank') }}", { bank, acc_no: account })--}}
        {{--                .then(res => {--}}
        {{--                    if (res.data.valid) {--}}
        {{--                        this.setStatus('account-status', 'มีข้อมูลเลขบัญชีนี้ในระบบแล้ว', 'red');--}}
        {{--                        this.isBankValid = false;--}}
        {{--                    } else {--}}
        {{--                        this.setStatus('account-status', 'สามารถใช้ได้', 'green');--}}
        {{--                        this.isBankValid = true;--}}
        {{--                    }--}}
        {{--                })--}}
        {{--                .catch(() => {--}}
        {{--                    this.setStatus('account-status', 'เกิดข้อผิดพลาด', 'gray');--}}
        {{--                    this.isBankValid = false;--}}
        {{--                });--}}
        {{--        },--}}
        {{--        triggerPendingSubmit() {--}}
        {{--            if (this.pendingSubmit && this.canSubmit) {--}}
        {{--                this.finalSubmit();--}}
        {{--            }--}}
        {{--        },--}}
        {{--        finalSubmit() {--}}
        {{--            this.isSubmitting = true;--}}
        {{--            this.$refs.form.submit();--}}
        {{--        },--}}
        {{--        onSubmit() {--}}
        {{--            if (this.isSubmitting) return;--}}
        {{--            this.pendingSubmit = true;--}}
        {{--            this.$validator.validateAll().then(success => {--}}
        {{--                if (success && this.step === 2 && this.canSubmit) {--}}
        {{--                    this.finalSubmit();--}}
        {{--                } else if (!this.canSubmit) {--}}
        {{--                    console.warn('รอ validate เสร็จก่อน');--}}
        {{--                } else {--}}
        {{--                    this.isSubmitting = false;--}}
        {{--                    this.pendingSubmit = false;--}}
        {{--                }--}}
        {{--            });--}}
        {{--        },--}}
        {{--        goNext() {--}}
        {{--            if (!this.disableNextBtn && this.swiper && this.step < 2) {--}}
        {{--                this.swiper.slideNext();--}}
        {{--            }--}}
        {{--        },--}}
        {{--        goPrev() {--}}
        {{--            if (this.swiper && this.step > 0) {--}}
        {{--                this.swiper.slidePrev();--}}
        {{--            }--}}
        {{--        }--}}
        {{--    },--}}
        {{--    mounted() {--}}
        {{--        this.swiper = new Swiper('#regForm', {--}}
        {{--            pagination: {--}}
        {{--                el: '.swiper-pagination',--}}
        {{--                clickable: true,--}}
        {{--                type: 'progressbar'--}}
        {{--            },--}}
        {{--            navigation: {--}}
        {{--                nextEl: ".nextregis",--}}
        {{--                prevEl: ".preregis",--}}
        {{--            },--}}
        {{--            autoHeight: true,--}}
        {{--            allowTouchMove: false--}}
        {{--        });--}}

        {{--        this.step = this.swiper.activeIndex;--}}

        {{--        this.swiper.on('slideChange', () => {--}}
        {{--            this.step = this.swiper.activeIndex;--}}
        {{--        });--}}

        {{--        // เรียก validate แรกสุด เพื่อให้ปุ่มถูกต้อง--}}
        {{--        this.validatePhone();--}}
        {{--        this.validateBank();--}}
        {{--    }--}}
        {{--});--}}

        {{--Vue.component('register-form', {--}}
        {{--    template: '#register-template',--}}
        {{--    data() {--}}
        {{--        return {--}}
        {{--            step: 0,--}}
        {{--            isPhoneValid: false, // ตั้งค่าเริ่มต้นเป็น false--}}
        {{--            isBankValid: false, // ตั้งค่าเริ่มต้นเป็น false--}}
        {{--            isSubmitting: false,--}}
        {{--            pendingSubmit: false,--}}
        {{--            swiper: null,--}}
        {{--            form: {--}}
        {{--                user_name: '',--}}
        {{--                bank: '',--}}
        {{--                acc_no: '',--}}
        {{--                firstname: '',--}}
        {{--                lastname: '',--}}
        {{--                password: '',--}}
        {{--                refer: ''--}}
        {{--            },--}}
        {{--            debounceTimer: null, // Timer for debounce to prevent rapid API calls--}}
        {{--        }--}}
        {{--    },--}}
        {{--    computed: {--}}
        {{--        canSubmit() {--}}
        {{--            return this.isPhoneValid && this.isBankValid &&--}}
        {{--                this.form.firstname && this.form.lastname &&--}}
        {{--                this.form.acc_no && this.form.bank &&--}}
        {{--                this.form.user_name && this.form.password &&--}}
        {{--                this.form.refer;--}}
        {{--        },--}}
        {{--        disableNextBtn() {--}}
        {{--            // ปรับการตรวจสอบให้แน่ใจว่าปุ่มปิดใช้งานตามสถานะ--}}
        {{--            return (this.step === 0 && !this.isPhoneValid) ||--}}
        {{--                (this.step === 1 && (!this.isBankValid || !this.form.firstname || !this.form.lastname));--}}
        {{--        }--}}
        {{--    },--}}
        {{--    watch: {--}}
        {{--        'form.user_name': function(newVal) {--}}
        {{--            if (newVal.length === 10) {--}}
        {{--                this.debounceValidatePhone();--}}
        {{--            } else {--}}
        {{--                this.isPhoneValid = false;--}}
        {{--                this.setStatus('phone-status', '', ''); // Clear status message--}}
        {{--            }--}}
        {{--        },--}}
        {{--        'form.acc_no': function(newVal) {--}}
        {{--            if (newVal.length >= 10 && this.form.bank) {--}}
        {{--                this.debounceValidateBank();--}}
        {{--            } else {--}}
        {{--                this.isBankValid = false;--}}
        {{--                this.setStatus('account-status', '', ''); // Clear status message--}}
        {{--            }--}}
        {{--        },--}}
        {{--        'form.bank': function(newVal) {--}}
        {{--            if (this.form.acc_no.length >= 10 && newVal) {--}}
        {{--                this.debounceValidateBank();--}}
        {{--            } else {--}}
        {{--                this.isBankValid = false;--}}
        {{--                this.setStatus('account-status', '', ''); // Clear status message--}}
        {{--            }--}}
        {{--        }--}}
        {{--    },--}}
        {{--    methods: {--}}
        {{--        debounceValidatePhone() {--}}
        {{--            clearTimeout(this.debounceTimer);--}}
        {{--            this.debounceTimer = setTimeout(() => {--}}
        {{--                this.validatePhone();--}}
        {{--            }, 500); // Debounce time in milliseconds--}}
        {{--        },--}}
        {{--        debounceValidateBank() {--}}
        {{--            clearTimeout(this.debounceTimer);--}}
        {{--            this.debounceTimer = setTimeout(() => {--}}
        {{--                this.validateBank();--}}
        {{--            }, 500); // Debounce time in milliseconds--}}
        {{--        },--}}
        {{--        isNumeric(str) {--}}
        {{--            return /^\d+$/.test(str);--}}
        {{--        },--}}
        {{--        setStatus(id, msg, color) {--}}
        {{--            const el = document.getElementById(id);--}}
        {{--            if (el) {--}}
        {{--                el.innerText = msg;--}}
        {{--                el.style.color = color;--}}
        {{--            }--}}
        {{--        },--}}
        {{--        async validatePhone() {--}}
        {{--            console.log('validatePhone Start');--}}
        {{--            const phone = this.form.user_name.trim();--}}
        {{--            if (!this.isNumeric(phone)) {--}}
        {{--                this.setStatus('phone-status', 'กรุณากรอกเฉพาะตัวเลข', 'red');--}}
        {{--                this.isPhoneValid = false;--}}
        {{--                return;--}}
        {{--            }--}}
        {{--            if (phone.length !== 10) {--}}
        {{--                this.setStatus('phone-status', 'กรุณากรอกเบอร์ 10 หลัก', 'red');--}}
        {{--                this.isPhoneValid = false;--}}
        {{--                return;--}}
        {{--            }--}}
        {{--            this.setStatus('phone-status', 'กำลังตรวจสอบ...', 'gray');--}}

        {{--            try {--}}
        {{--                const response = await axios.post("{{ route('customer.check.phone') }}", { username: phone });--}}
        {{--                const r = response.data;--}}

        {{--                if (r.data.exists) {--}}
        {{--                    this.setStatus('phone-status', 'เบอร์นี้มีในระบบแล้ว', 'red');--}}
        {{--                    this.isPhoneValid = false;--}}
        {{--                } else {--}}
        {{--                    this.setStatus('phone-status', 'สามารถใช้เบอร์นี้ได้', 'green');--}}
        {{--                    this.isPhoneValid = true;--}}
        {{--                }--}}
        {{--            } catch (err) {--}}
        {{--                this.setStatus('phone-status', 'เกิดข้อผิดพลาด', 'gray');--}}
        {{--                this.isPhoneValid = false;--}}
        {{--            }--}}
        {{--        },--}}
        {{--        validateBank() {--}}

        {{--            const acc = this.form.acc_no.trim();--}}
        {{--            if (!this.isNumeric(acc)) {--}}
        {{--                this.setStatus('account-status', 'กรุณากรอกเลขบัญชีเป็นตัวเลขเท่านั้น', 'red');--}}
        {{--                this.isBankValid = false;--}}
        {{--                return;--}}
        {{--            }--}}
        {{--            if (!this.form.bank || acc.length < 10) {--}}
        {{--                this.setStatus('account-status', '', '');--}}
        {{--                this.isBankValid = false;--}}
        {{--                return;--}}
        {{--            }--}}
        {{--            this.setStatus('account-status', 'กำลังตรวจสอบ...', 'gray');--}}

        {{--            axios.post("{{ route('customer.check.bank') }}", {--}}
        {{--                bank: this.form.bank,--}}
        {{--                acc_no: acc--}}
        {{--            }).then(res => {--}}
        {{--                if (res.data.valid) {--}}
        {{--                    this.setStatus('account-status', 'มีข้อมูลเลขบัญชีนี้ในระบบแล้ว', 'red');--}}
        {{--                    this.isBankValid = false;--}}
        {{--                } else {--}}
        {{--                    this.setStatus('account-status', 'สามารถใช้ได้', 'green');--}}
        {{--                    this.isBankValid = true;--}}
        {{--                }--}}
        {{--            }).catch(() => {--}}
        {{--                this.setStatus('account-status', 'เกิดข้อผิดพลาด', 'gray');--}}
        {{--                this.isBankValid = false;--}}
        {{--            });--}}
        {{--        },--}}
        {{--        onSubmit() {--}}
        {{--            if (this.isSubmitting) return;--}}
        {{--            this.pendingSubmit = true;--}}
        {{--            this.$validator.validateAll().then(valid => {--}}
        {{--                if (valid && this.step === 2 && this.canSubmit) {--}}
        {{--                    this.finalSubmit();--}}
        {{--                } else {--}}
        {{--                    this.pendingSubmit = false;--}}
        {{--                    this.isSubmitting = false;--}}
        {{--                }--}}
        {{--            });--}}
        {{--        },--}}
        {{--        finalSubmit() {--}}
        {{--            this.isSubmitting = true;--}}
        {{--            this.$refs.form.submit();--}}
        {{--        },--}}
        {{--        goNext() {--}}
        {{--            if (!this.disableNextBtn && this.swiper && this.step < 2) {--}}
        {{--                this.swiper.slideNext();--}}
        {{--            }--}}
        {{--        },--}}
        {{--        goPrev() {--}}
        {{--            if (this.swiper && this.step > 0) {--}}
        {{--                this.swiper.slidePrev();--}}
        {{--            }--}}
        {{--        }--}}
        {{--    },--}}
        {{--    mounted() {--}}
        {{--        this.swiper = new Swiper('#regForm', {--}}
        {{--            pagination: {--}}
        {{--                el: '.swiper-pagination',--}}
        {{--                clickable: true,--}}
        {{--                type: 'progressbar'--}}
        {{--            },--}}
        {{--            navigation: {--}}
        {{--                nextEl: ".nextregis",--}}
        {{--                prevEl: ".preregis",--}}
        {{--            },--}}
        {{--            autoHeight: true,--}}
        {{--            allowTouchMove: false--}}
        {{--        });--}}

        {{--        this.step = this.swiper.activeIndex;--}}

        {{--        this.swiper.on('slideChange', () => {--}}
        {{--            this.step = this.swiper.activeIndex;--}}
        {{--        });--}}

        {{--        // ตรวจสอบสถานะเริ่มต้น--}}
        {{--        this.validatePhone();--}}
        {{--        this.validateBank();--}}
        {{--    }--}}
        {{--});--}}

        Vue.component('register-form', {
            template: '#register-template',
            data() {
                return {
                    step: 0,
                    isPhoneValid: false, // ค่าเริ่มต้นให้ปิดการทำงาน
                    isBankValid: false, // ค่าเริ่มต้นให้ปิดการทำงาน
                    isSubmitting: false,
                    form: {
                        user_name: '',
                        bank: '',
                        acc_no: '',
                        firstname: '',
                        lastname: '',
                        password: '',
                        refer: ''
                    },
                };
            },
            computed: {
                canSubmit() {
                    // ตรวจสอบว่าทุก input ผ่านการ validate
                    return this.isPhoneValid && this.isBankValid &&
                        this.form.firstname && this.form.lastname &&
                        this.form.acc_no && this.form.bank &&
                        this.form.user_name && this.form.password &&
                        this.form.refer;
                },
                disableNextBtn() {
                    // ปิดปุ่มถัดไปจนกว่าจะผ่านเงื่อนไข
                    return (this.step === 0 && !this.isPhoneValid) ||
                        (this.step === 1 && (!this.isBankValid || !this.form.firstname || !this.form.lastname));
                }
            },
            methods: {
                validatePhone() {
                    const phone = this.form.user_name.trim();

                    // ตั้งค่าเบื้องต้น
                    this.isPhoneValid = false;

                    // ตรวจสอบเงื่อนไขเบื้องต้น
                    if (!/^\d{10}$/.test(phone)) {
                        this.setStatus('phone-status', 'กรุณากรอกเบอร์โทร 10 หลัก', 'red');
                        return;
                    }

                    // ตรวจสอบข้อมูลผ่าน API
                    this.setStatus('phone-status', 'กำลังตรวจสอบ...', 'gray');
                    axios.post("{{ route('customer.check.phone') }}", { username: phone })
                        .then(response => {
                            const exists = response.data.data.exists;
                            if (exists) {
                                this.setStatus('phone-status', 'เบอร์นี้มีในระบบแล้ว', 'red');
                                this.isPhoneValid = false;
                            } else {
                                this.setStatus('phone-status', 'สามารถใช้เบอร์นี้ได้', 'green');
                                this.isPhoneValid = true;
                            }
                        })
                        .catch(() => {
                            this.setStatus('phone-status', 'เกิดข้อผิดพลาดในการตรวจสอบ', 'gray');
                        });
                },
                validateBank() {
                    const acc = this.form.acc_no.trim();

                    // ตั้งค่าเบื้องต้น
                    this.isBankValid = false;

                    // ตรวจสอบเงื่อนไขเบื้องต้น
                    if (!/^\d{10,12}$/.test(acc)) {
                        this.setStatus('account-status', 'กรุณากรอกเลขบัญชี 10-12 หลัก', 'red');
                        return;
                    }

                    // ตรวจสอบข้อมูลผ่าน API
                    this.setStatus('account-status', 'กำลังตรวจสอบ...', 'gray');
                    axios.post("{{ route('customer.check.bank') }}", { bank: this.form.bank, acc_no: acc })
                        .then(response => {
                            const valid = response.data.valid;
                            if (valid) {
                                this.setStatus('account-status', 'สามารถใช้ได้', 'green');
                                this.isBankValid = true;
                            } else {
                                this.setStatus('account-status', 'มีข้อมูลเลขบัญชีนี้ในระบบแล้ว', 'red');
                                this.isBankValid = false;
                            }
                        })
                        .catch(() => {
                            this.setStatus('account-status', 'เกิดข้อผิดพลาดในการตรวจสอบ', 'gray');
                        });
                },
                setStatus(id, msg, color) {
                    const el = document.getElementById(id);
                    if (el) {
                        el.innerText = msg;
                        el.style.color = color;
                    }
                },
                goNext() {
                    if (!this.disableNextBtn && this.swiper && this.step < 2) {
                        this.swiper.slideNext();
                    }
                },
                goPrev() {
                    if (this.swiper && this.step > 0) {
                        this.swiper.slidePrev();
                    }
                },
                onSubmit() {
                    if (this.isSubmitting) return;
                    this.$validator.validateAll().then(valid => {
                        if (valid && this.step === 2 && this.canSubmit) {
                            this.isSubmitting = true;
                            this.$refs.form.submit();
                        }
                    });
                }
            },
            mounted() {
                // เริ่มต้น Swiper
                this.swiper = new Swiper('#regForm', {
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                        type: 'progressbar'
                    },
                    navigation: {
                        nextEl: ".nextregis",
                        prevEl: ".preregis",
                    },
                    autoHeight: true,
                    allowTouchMove: false
                });

                this.step = this.swiper.activeIndex;

                // ฟังการเปลี่ยนแปลงของ slide
                this.swiper.on('slideChange', () => {
                    this.step = this.swiper.activeIndex;
                });

                // Debug ค่าเริ่มต้น
                console.log('Initial States:', {
                    isPhoneValid: this.isPhoneValid,
                    isBankValid: this.isBankValid,
                    disableNextBtn: this.disableNextBtn,
                    canSubmit: this.canSubmit
                });
            }
        });

    </script>


@endpush

