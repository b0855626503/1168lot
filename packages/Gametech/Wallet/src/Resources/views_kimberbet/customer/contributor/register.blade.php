{{-- extend layout --}}
@extends('wallet::layouts.app')

{{-- page title --}}
@section('title','')

@push('styles')
    <style>
        .homeregis {
            display: none !important;
        }
        .height-1 {
            height:302px !important;
        }
        .height-2 {
            height:380px !important;
        }
        .height-3 {
            height:300px !important;
        }
    </style>
@endpush


@section('content')

    <div class="register-container sub-page sub-footer vhm-100">
        <div id="block-register" class="register-inner-content card shadow">
            <h4 class="card-title text-center pt-3">{{ __('app.login.register') }}</h4>
            <div class="card-body pt-0 px-0">
                <div class="theme-form">
                    <form id="registerForm" ref="form" @submit.prevent="onSubmit">
                        @csrf
                        <div id="regForm" class="swiper-container">
                            <div class="swiper-wrapper">
                                <!-- Slide 1 -->
                                <div class="swiper-slide regis01 p-2">
                                    @if($id)
                                        <div class="mb-3">
                                            <label>{{ __('app.register.upline') }}</label>

                                            <input type="hidden" id="upline" name="upline" value="{!! $id !!}">
                                            <input autocomplete="off" class="form-control x-form-control" readonly
                                                   value="{!! $contributor !!}" type="text">
                                        </div>
                                    @endif
                                    <label class="text-content">{{ __('app.register.tel') }}</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="bi bi-phone-fill"></i></span>

                                        <input autocomplete="off"
                                               data-vv-as="&quot;{{ __('app.register.tel') }}&quot;"
                                               class="form-control x-form-control" id="user_name1"
                                               name="user_name" maxlength="10" minlength="10"
                                               placeholder="{{ __('app.register.username_placeholder') }}"
                                               value="{{ old('user_name') }}"
                                               v-validate="'required'"
                                               :class="[errors.has('user_name') ? 'is-invalid' : '']"
                                               pattern="[0-9]*" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                               type="text">
                                    </div>
                                    <small id="phone-status" class="form-text text-center"></small>
                                    <small class="control-error text-warning text-center" v-if="errors.has('user_name')">@{{ errors.first('user_name') }}</small><br>

                                    <label>{{ __('app.register.password') }}</label>
                                    <div class="input-group input-group-lg mb-3">
                                        <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                        <input autocomplete="off"
                                               minlength="6" maxlength="10"
                                               data-vv-as="&quot;{{ __('app.register.password') }}&quot;"
                                               class="form-control x-form-control input-password" id="password1"
                                               v-validate="'required|min:6|max:10'"
                                               value="{{ old('password') }}"
                                               :class="[errors.has('password') ? 'is-invalid' : '']"
                                               name="password" placeholder="{{ __('app.register.password') }}"
                                               type="text"
                                               ref="password">
                                    </div>
                                    <small class="control-error text-warning text-center" v-if="errors.has('password')">@{{ errors.first('password') }}</small>
                                    <br>
                                </div>

                                <!-- Slide 2 -->
                                <div class="swiper-slide regis02 p-2">
                                    <div class="text-danger small">{{ __('app.register.warning') }}</div>
                                    <label>{{ __('app.register.bank') }}</label>
                                    <div class="mb-3">
                                        <select class="form-control x-form-control" id="bank" name="bank"
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
                                        <label><label>{{ __('app.register.bank_account') }}</label></label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text"><i class="bi bi-credit-card-2-front-fill"></i></span>
                                            <input inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" autocomplete="off" class="form-control x-form-control" id="acc_no"
                                                   minlength="5"
                                                   maxlength="14"
                                                   data-vv-as="&quot;{{ __('app.register.bank_account') }}&quot;"
                                                   value="{{ old('acc_no') }}"
                                                   v-validate="'required|min:5|numeric'"
                                                   :class="[errors.has('acc_no') ? 'is-invalid' : '']"
                                                   name="acc_no"
                                                   placeholder="{{ __('app.register.bank_placeholder') }}"
                                                   type="text">
                                        </div>
                                        <small id="account-status" class="form-text text-center"></small>
                                        <small class="control-error text-warning text-center" v-if="errors.has('acc_no')">@{{ errors.first('acc_no') }}</small>
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
                                            <small class="control-error text-warning text-center" v-if="errors.has('firstname')">@{{ errors.first('firstname') }}</small>
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
                                            <small class="control-error text-warning" v-if="errors.has('lastname')">@{{ errors.first('lastname') }}</small>
                                        </div>
                                    </div>

                                </div>

                                <!-- Slide 3 -->
                                <div class="swiper-slide regis03 p-2">


                                    <br>
                                    <label>{{ __('app.register.refer') }}</label>
                                    <select class="form-control x-form-control" id="refer" name="refer"
                                            v-validate="'required'"
                                            data-vv-as="&quot;{{ __('app.register.refer') }}&quot;"
                                            :class="[errors.has('refer') ? 'is-invalid' : '']">
                                        @foreach($refers as $i => $refer)
                                            <option value="{{ $refer->code }}">{{ $refer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="swiper-pagination"></div>
                        </div>

                        <hr>
                        <div class="d-flex w-100 justify-content-between mt-auto pt-3">
                            <button
                                    type="button"
                                    class="btn btn-secondary w-100 rounded-pill mx-2 preregis" style="display:none">
                                <i class="bi bi-arrow-left"></i> {{ __('app.status.prev') }}
                            </button>

                            <button
                                    type="button"
                                    class="btn btn-custom-primary w-100 rounded-pill fw-bolder mx-2 nextregis"
                                    disabled>
                                {{ __('app.status.next') }} <i class="bi bi-arrow-right"></i>
                            </button>

                            <button id="regisbtn"
                                    type="button"
                                    class="btn btn-success rounded-pill w-100 mx-2 regisbtn" style="display:none"
                            >
                                <i class="bi bi-person-plus-fill"></i> {{ __('app.login.register') }}
                            </button>
                        </div>
                    </form>

                    <div class="d-inline-flex w-100 mt-3 justify-content-between">
                        <div></div>
                        <div>
                            <a href="{{ $webconfig->linelink }}" target="_blank"
                               class="btn btn-link btn-sm text-white">{{ __('app.login.help') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


@push('scripts')

    <script>
        function checkFinalFormReady() {
            const firstname = $('#firstname').val().trim();
            const lastname = $('#lastname').val().trim();
            const password = $('#password1').val().trim();
            const refer = $('#refer').val();

            const isReady = firstname && lastname && password && refer;

            $('.regisbtn').prop('disabled', !isReady);

            return isReady;
        }

        function validateFinalStepWithAlert() {
            let errors = [];

            const firstname = $('#firstname').val().trim();
            const lastname = $('#lastname').val().trim();
            const password = $('#password1').val().trim();
            const refer = $('#refer').val();

            if (!firstname) errors.push('{{ __('app.input.fill', ['field' => __('app.input.firstname')]) }}');
            if (!lastname) errors.push('{{ __('app.input.fill', ['field' => __('app.input.lastname')]) }}');
            if (!password) errors.push('{{ __('app.input.fill', ['field' => __('app.input.password')]) }}');
            if (!refer) errors.push('{{ __('app.input.select', ['field' => __('app.input.refer')]) }}');

            if (errors.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: '{{ __('app.status.nodata') }}',
                    html: '<ul style="text-align:left;">' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>',
                    confirmButtonText: '{{ __('app.status.ok') }}'
                });

                return false;
            }

            return true;
        }

        document.addEventListener("DOMContentLoaded", function () {
            let swiper = new Swiper("#regForm", {
                allowTouchMove: false,      // ❌ ลาก slide ด้วยนิ้ว/เมาส์ไม่ได้
                simulateTouch: false,       // ❌ ห้าม drag ด้วย mouse
                touchRatio: 0,              // ❌ ไม่ตอบสนองต่อ touch
                pagination: {
                    el: ".swiper-pagination",
                    clickable: false,       // ❌ ห้ามกดเปลี่ยนจาก pagination
                    type: 'progressbar'
                },
                spaceBetween: 100,
                watchOverflow: true,
                preventClicks: true,
                preventClicksPropagation: true,

                autoHeight: true
            });

            let isPhonePass = false;
            let isBankPass = false;
            let isFormReady = false;

            const $nextBtn = $('.nextregis');
            const $prevBtn = $('.preregis');
            const $submitBtn = $('.regisbtn');

            // เริ่มต้น: ล็อกปุ่มทั้งหมด
            $prevBtn.hide();
            $nextBtn.prop('disabled', true);
            $submitBtn.hide().prop('disabled', true);

            // slide change event
            swiper.on('slideChange', function () {
                const index = swiper.activeIndex;
                console.log('slide index:', index);

                $prevBtn.toggle(index > 0);

                if (index === 0) {
                    // document.getElementById('block-register').style.marginTop = '';
                    $('.swiper-wrapper').removeClass('height-2');
                    $('.swiper-wrapper').addClass('height-1');

                    $nextBtn.show().prop('disabled', !isPhonePass);
                    $submitBtn.hide();
                } else if (index === 1) {
                    // document.getElementById('block-register').style.marginTop = '30%';
                    $('.swiper-wrapper').removeClass('height-1');
                    $('.swiper-wrapper').removeClass('height-3');
                    $('.swiper-wrapper').addClass('height-2');

                    $nextBtn.show().prop('disabled', !isBankPass);
                    $submitBtn.hide();
                } else if (index === 2) {
                    // document.getElementById('block-register').style.marginTop = '';
                    $('.swiper-wrapper').removeClass('height-2');
                    $('.swiper-wrapper').addClass('height-3');

                    $nextBtn.hide();
                    $submitBtn.show().prop('disabled', !isFormReady);
                    checkFinalFormReady(); //
                }
            });

            // ===== PHONE VALIDATION =====
            let phoneTimer;
            $('#user_name1').on('input', function () {
                clearTimeout(phoneTimer);
                $nextBtn.prop('disabled', true);
                isPhonePass = false;

                const phone = $(this).val().trim();
                const status = document.getElementById('phone-status');

                if (!/^\d{10}$/.test(phone)) {
                    status.innerText = '';
                    status.style.color = 'red';
                    return;
                }

                status.innerText = '{{ __('app.status.check') }}';
                status.style.color = 'gray';

                phoneTimer = setTimeout(function () {
                    axios.post("{{ route('customer.check.phone') }}", { username: phone })
                        .then(res => {
                            if (res.data.exists) {
                                status.innerText = res.data.message;
                                status.style.color = 'red';
                                isPhonePass = false;
                                $nextBtn.prop('disabled', true);
                            } else {
                                status.innerText = res.data.message;
                                status.style.color = 'green';
                                isPhonePass = true;
                                $nextBtn.prop('disabled', false);
                            }
                        })
                        .catch(() => {
                            status.innerText = '{{ __('app.status.error') }}';
                            status.style.color = 'gray';
                        });
                }, 500);
            });

            // ===== BANK VALIDATION =====
            let bankTimer;
            $('#bank, #acc_no').on('input change', function () {
                clearTimeout(bankTimer);
                $nextBtn.prop('disabled', true);
                isBankPass = false;

                const phone = $('#user_name1').val();
                const bank = $('#bank').val();
                const acc = $('#acc_no').val().trim();
                const status = document.getElementById('account-status');

                console.log('bank:', bank);
                if(bank == 18){
                    $('#acc_no').val(phone);
                    $('#acc_no').prop('readonly', true);
                }else{
                    $('#acc_no').prop('readonly', false)
                }

                if (!/^\d{10,}$/.test(acc)) {
                    status.innerText = '';
                    status.style.color = 'red';
                    return;
                }

                if (!bank) {
                    status.innerText = '';
                    return;
                }



                status.innerText = '{{ __('app.status.check') }}';
                status.style.color = 'gray';

                bankTimer = setTimeout(function () {
                    axios.post("{{ route('customer.check.bank') }}", { bank: bank, acc_no: acc })
                        .then(res => {
                            if (res.data.valid) {
                                status.innerText = res.data.message;
                                status.style.color = 'red';
                                isBankPass = true;
                                $nextBtn.prop('disabled', false);
                            } else {
                                status.innerText = res.data.message;
                                status.style.color = 'green';
                                isBankPass = true;
                                $nextBtn.prop('disabled', false);
                            }
                        })
                        .catch(() => {
                            status.innerText = '{{ __('app.status.error') }}';
                            status.style.color = 'gray';
                        });
                }, 500);
            });

            // ===== STEP BUTTONS =====
            $nextBtn.on('click', function () {
                swiper.slideNext();
            });

            $prevBtn.on('click', function () {
                swiper.slidePrev();
            });

            // ===== FORM VALIDATION ON SUBMIT =====
            $('#regisbtn').on('click', function (e) {
                e.preventDefault();

                if (swiper.activeIndex === 2) {
                    if (!validateFinalStepWithAlert()) {
                        return;
                    }
                }

                isFormReady = true;
                // $submitBtn.prop('disabled', false);

                const $form = $('#registerForm');
                const formData = new FormData($form[0]);

                const $submitBtn = $form.find('button[type="submit"], button[type="button"]');
                $submitBtn.prop('disabled', true);

                $.ajax({
                    url: '{{ route('customer.session.register_api') }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': formData.get('_token')
                    },
                    success: function (res) {
                        // ✅ กรณีสมัครสำเร็จ
                        if (res.success) {
                            window.location.href = '{{ route('customer.home.index') }}';
                            // alert('สมัครเรียบร้อยแล้ว');
                            // หรือ redirect เช่น window.location.href = '/home';
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                text: res.message,
                                confirmButtonText: '{{ __('app.status.ok') }}'
                            });

                        }
                    },
                    error: function (xhr) {
                        // ❌ แจ้งเตือนเมื่อเกิด error จาก server หรือ network
                        console.error(xhr);
                        Swal.fire({
                            icon: 'error',
                            text: '{{ __('app.status.error') }}',
                            confirmButtonText: '{{ __('app.status.ok') }}'
                        });
                    },
                    complete: function () {
                        $submitBtn.prop('disabled', false); // เปิดปุ่มอีกครั้ง
                    }
                });
            });

            $('#password1, #refer, #firstname, #lastname').on('input change', function () {
                checkFinalFormReady();
            });
        });



    </script>

@endpush

