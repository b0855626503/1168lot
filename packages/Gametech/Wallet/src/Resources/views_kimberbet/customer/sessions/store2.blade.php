{{-- extend layout --}}
@extends('wallet::layouts.app')

@section('title','สมัครสมาชิก')

@push('styles')
    <style>
        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: calc(100vh - 40px) !important; /* ปรับความสูงของ container */
            padding: 20px 0 !important;
        }

        .register-inner-content {
            max-height: 95% !important; /* เพิ่มความสูงของฟอร์ม */
            width: 100% !important;
            max-width: 400px !important; /* จำกัดความกว้าง */
            background: rgba(0, 0, 0, 0.85) !important;
            border-radius: 12px !important;
            overflow-y: auto !important; /* เปิดการ scroll */
            padding: 20px !important;
            box-sizing: border-box !important;
        }

        .swiper-container {
            max-height: calc(100% - 20px) !important; /* จำกัดความสูงของ Swiper */
            overflow-y: auto !important; /* เปิดการ scroll */
        }

        .swiper-slide {
            max-height: 100% !important; /* ให้ slide ใช้พื้นที่เต็ม */
            overflow-y: auto !important; /* เปิดการ scroll */
        }

        @media (max-height: 600px) {
            .register-container {
                padding: 10px !important; /* เพิ่ม padding สำหรับหน้าจอเล็ก */
            }
        }
    </style>
@endpush

@section('content')
    <div class="register-container" style="height: calc(100vh - 40px); display: flex; justify-content: center; align-items: center; padding: 20px 0;">
        <div id="block-register" class="register-inner-content" style="max-height: 95%; width: 100%; max-width: 400px; background: rgba(0, 0, 0, 0.85); border-radius: 12px; overflow-y: auto; padding: 20px; box-sizing: border-box;">
            <h4 class="card-title text-center pt-3">{{ __('app.login.register') }}</h4>
            <div class="card-body pt-0 px-0">
                <div class="theme-form">
                    <div id="regForm" class="swiper-container">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide regis01 p-2">
                                <label class="text-content">{{ __('app.register.username') }}</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="bi bi-phone-fill bi-1-5x"></i></span>
                                    <input id="reg-tel" autocomplete="off" class="form-control" type="tel" placeholder="{{ __('app.register.username') }}"  maxlength="10" required="">
                                </div>
                                <em class="small fw-light text-content">{{ __('app.register.ex') }}</em>
                            </div>
                            <div class="swiper-slide regis02 p-2">
                                <div class="text-danger small">{{ __('app.register.warning') }}</div>
                                <label>ธนาคาร</label>
                                <div class="mb-3 " style="font-size: 1.2em;">
                                    <select class="form-select form-select-lg" id="bank" name="bank"
                                            v-validate="'required'"
                                            :class="[errors.has('bank') ? 'is-invalid' : '']">
                                        <option value="">{{ __('app.register.select_bank') }}</option>
                                        @foreach($banks as $i => $bank)
                                            <option
                                                    value="{{ $bank->code }}" {{ old('bank') == $bank->code ? 'selected' : '' }}>{{ $bank->name_th }}</option>
                                        @endforeach
                                    </select>

                                </div>
                                <div class="mb-3">
                                    <label>{{ __('app.register.bank_account') }}</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="bi bi-credit-card-2-front-fill"></i></span>
                                        <input autocomplete="off" class="form-control" id="acc_no"
                                               minlength="5"
                                               maxlength="12"
                                               data-vv-as="&quot;{{ __('app.register.bank_account') }}&quot;"
                                               value="{{ old('acc_no') }}"
                                               v-validate="'required|min:5|numeric'"
                                               :class="[errors.has('acc_no') ? 'is-invalid' : '']"
                                               name="acc_no"
                                               placeholder="{{ __('app.register.bank_placeholder') }}"
                                               type="text">
                                    </div>
                                    <em class="fw-light text-content">เลขล้วนไม่มีช่องว่าง</em>
                                </div>
                                <div class="row g-0">
                                    <div class="col-12">
                                        <label>{{ __('app.register.name') }}</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text"><i class="bi bi-person-lines-fill"></i></span>
                                            <input autocomplete="off" class="form-control x-form-control" id="firstname"
                                                   name="firstname"
                                                   v-validate="'required'"
                                                   :class="[errors.has('firstname') ? 'is-invalid' : '']"
                                                   data-vv-as="&quot;firstname&quot;"
                                                   value="{{ old('firstname') }}"
                                                   placeholder="{{ __('app.register.name') }}" type="text">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label>{{ __('app.register.surname') }}</label>
                                        <div class="input-group input-group-lg">
                                            <input autocomplete="off" class="form-control x-form-control"
                                                   name="lastname"
                                                   v-validate="'required'"
                                                   value="{{ old('lastname') }}"
                                                   :class="[errors.has('lastname') ? 'is-invalid' : '']"
                                                   id="lastname" placeholder="{{ __('app.register.surname') }}" type="text">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide regis03 p-2">
                                <div class="mb-3">
                                    <label>{{ __('app.register.password') }}</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                        <input autocomplete="off"
                                               minlength="6"
                                               maxlength="10"
                                               data-vv-as="&quot;{{ __('app.register.password') }}&quot;"
                                               class="form-control x-form-control input-password" id="password1"
                                               v-validate="'required|min:6|max:10'"
                                               value="{{ old('password') }}"
                                               :class="[errors.has('password') ? 'is-invalid' : '']"
                                               name="password" placeholder="{{ __('app.register.password') }}" type="password"
                                               ref="password">
                                    </div>
                                    <em class="fw-light">ใช้สำหรับ Login</em>
                                </div>
                                <hr>
                                <div class="mb-3" v-if="!(found.member_ref || found.zean_ref)">
                                    <label>{{ __('app.register.refer') }}</label>
                                    <div class="input-group input-group-lg">
                                        <select class="form-select form-select-lg" id="refer" name="refer"
                                                v-validate="'required'"
                                                data-vv-as="&quot;{{ __('app.register.refer') }}&quot;"
                                                :class="[errors.has('refer') ? 'is-invalid' : '']">
                                            @foreach($refers as $i => $refer)
                                                <option value="{{ $refer->code }}">{{ $refer->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                    <hr>
                    <div class="d-inline-flex w-100">
                        <button class="btn btn-secondary rounded-pill w-100 mx-2 preregis" v-show="page>1" type="button" style="display: none;"><i class="bi bi-arrow-left"></i> ก่อนหน้า</button>
                        <a href="dashboard.php">
                            <button class="btn btn-custom-primary w-100 rounded-pill fw-bolder mx-2 regisbtn" type="button" style="display:none;"><i class="bi bi-person-plus-fill"></i> สมัครสมาชิก</button>
                        </a>
                        <button class="btn btn-custom-primary w-100 rounded-pill fw-bolder mx-2 nextregis" type="button">ถัดไป <i class="bi bi-arrow-right"></i></button>
                    </div>
                    <div class="d-inline-flex w-100 mt-3 justify-content-between">
                        <div></div>
                        <div>
                            <a href="#" target="_blank" class="btn btn-link btn-sm text-white">ต้องการความช่วยเหลือ</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let step = 0;
        const swiper = new Swiper('#regForm', {
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
                type: 'progressbar'
            },
            navigation: {
                nextEl: ".nextregis",
                prevEl: ".preregis",
            },
            spaceBetween: 30,
            allowTouchMove: false,
            autoHeight: true
        });

        function updateStepButtons() {
            $('.preregis').toggle(step > 0);
            $('.nextregis').toggle(step < 2);
            $('.regisbtn').toggle(step === 2);
        }

        $('.nextregis').click(() => {
            if (step < 2) {
                step++;
                swiper.slideTo(step);
                updateStepButtons();
            }
        });

        $('.preregis').click(() => {
            if (step > 0) {
                step--;
                swiper.slideTo(step);
                updateStepButtons();
            }
        });

        $(document).ready(() => {
            updateStepButtons();
        });
    </script>
@endpush
