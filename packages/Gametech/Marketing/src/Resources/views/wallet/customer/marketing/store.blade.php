{{-- extend layout --}}
@extends('wallet::layouts.app')

{{-- page title --}}
@section('title','')

@push('styles')
    <style>
        .homeregis { display: none !important; }
        .register-inner-content.card { max-width: 720px; margin: 0 auto; }

        .x-form-control { background: #111; color: #fff; }
        /* ปรับปุ่มให้แถวเดียวในเดสก์ท็อป */
        @media (min-width: 768px) {
            .register-container .btn { min-height: 44px; }
        }

        #page-register.register-container > .card {
            width: 100%;
            max-width: 720px;
            margin-inline: auto;
        }
        @media (max-width: 576px) {
            #page-register.register-container > .card { max-width: 100%; }
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        :root {
            --in-height: 50px;
            --in-radius: 28px;

            --in-bg: #0a0d14;
            --in-border: #2a2f3a;
            --in-text: #e6e9ee;
            --in-muted: #9aa3ad;

            --in-elev: #10131a;
            --in-hover: #182030;
            --in-selected: #0f172a;
            --in-accent: #3b82f6;
        }

        /* ===== 🎨 Dark Input Theme (Refined & Clean) ===== */
        .x-form-control,
        .input-group-text,
        .select2-selection--single {
            background: var(--in-bg) !important;
            border: 1px solid var(--in-border) !important;
            color: #fff !important;
            border-radius: var(--in-radius) !important;
            height: var(--in-height);
            transition: all 0.2s ease;
        }

        /* ✅ icon ด้านหน้า input */
        .input-group-text {
            background-color: var(--in-bg) !important;
            color: rgba(255,255,255,0.7) !important;
            border-right: none !important;
            border-color: var(--in-border) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-inline: 1rem;
            /*font-size: 14px !important;*/
        }

        /* ✅ ตัดเส้นซ้ำใน input-group */
        .input-group .form-control {
            border-left: none !important;
        }

        /* ✅ invalid */
        .x-form-control.is-invalid,
        .select2-selection.is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 .2rem rgba(220,53,69,.25) !important;
        }

        /* ✅ disabled */
        .x-form-control:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        /* ✅ focus effect */
        .x-form-control:focus {
            background-color: var(--in-hover) !important;
            border-color: var(--in-accent) !important;
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--in-accent) 25%, transparent) !important;
            color: #fff !important;
            -webkit-text-fill-color: #fff !important;
            caret-color: #fff !important;
            outline: none !important;
        }

        /* ===== 💡 Text + Placeholder + Autofill ===== */
        input.x-form-control,
        textarea.x-form-control,
        select.x-form-control {
            background-color: var(--in-bg) !important;
            border-color: var(--in-border) !important;
            color: #fff !important;
            -webkit-text-fill-color: #fff !important;
            caret-color: #fff !important;
        }

        /* === 🩶 Force Ultra-Ghost Placeholder (ทุก browser ทุก engine) === */
        input.x-form-control::placeholder,
        input.x-form-control::-webkit-input-placeholder,
        input.x-form-control::-moz-placeholder,
        input.x-form-control:-ms-input-placeholder,
        input.x-form-control:-moz-placeholder,
        textarea.x-form-control::placeholder,
        textarea.x-form-control::-webkit-input-placeholder,
        textarea.x-form-control::-moz-placeholder,
        textarea.x-form-control:-ms-input-placeholder,
        textarea.x-form-control:-moz-placeholder {
            color: rgba(255,255,255,0.30) !important;
            opacity: 1 !important;
            filter: brightness(0.85) contrast(0.95);
            transition: color 0.3s ease, filter 0.3s ease;
        }

        /* focus แล้วจางเพิ่มอีก */
        input.x-form-control:focus::placeholder,
        textarea.x-form-control:focus::placeholder {
            color: rgba(255,255,255,0.20) !important;
            filter: brightness(0.8);
        }

        /* Select2 placeholder ให้เท่ากัน */
        .select2-container--gt-dark .select2-selection__placeholder {
            color: rgba(255,255,255,0.30) !important;
            opacity: 1 !important;
        }


        /* focus แล้ว placeholder จางลงอีก */
        input.x-form-control:focus::placeholder,
        textarea.x-form-control:focus::placeholder {
            color: rgba(255,255,255,0.25) !important;
        }

        /* ✅ Autofill fix (Chrome/Edge/Safari) */
        input.x-form-control:-webkit-autofill,
        input.x-form-control:-webkit-autofill:hover,
        input.x-form-control:-webkit-autofill:focus {
            -webkit-text-fill-color: #fff !important;
            caret-color: #fff !important;
            font-size: 12px !important;
            -webkit-box-shadow: 0 0 0 1000px var(--in-bg) inset !important;
            box-shadow: 0 0 0 1000px var(--in-bg) inset !important;
            border: 1px solid var(--in-border) !important;
        }

        /* ===== 🎯 Select2 Dark Theme ===== */
        .s2-lg .select2-selection--single { height: var(--in-height); }

        .select2-container--gt-dark .select2-selection--single {
            background: var(--in-bg);
            border: 1px solid var(--in-border);
            border-radius: var(--in-radius);
            display: flex;
            align-items: center;
            height: var(--in-height);
        }

        .select2-container--gt-dark .select2-selection__rendered {
            color: var(--in-text);
            line-height: calc(var(--in-height) - 2px);
            padding-inline: 16px 40px;
            font-size: 14px;
        }

        .select2-container--gt-dark .select2-selection__placeholder {
            color: rgba(255,255,255,0.35) !important;
            opacity: 1 !important;
        }

        .select2-container--gt-dark .select2-selection__arrow {
            right: 12px;
            height: 100%;
        }

        .select2-container--gt-dark .select2-selection__arrow b {
            border-width: 6px 5px 0 5px;
            border-color: var(--in-muted) transparent transparent transparent;
        }

        .select2-container--gt-dark.select2-container--focus .select2-selection--single {
            border-color: var(--in-accent);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--in-accent) 24%, transparent);
            outline: 0;
        }

        /* Dropdown */
        .select2-container--gt-dark .select2-dropdown {
            background: var(--in-elev);
            border: 1px solid var(--in-border);
            color: var(--in-text);
            border-radius: 14px;
            overflow: hidden;
            max-height: 60vh;
            display: flex;
            flex-direction: column;
            z-index: 10000;
        }

        .select2-container--gt-dark .select2-search--dropdown { display: none !important; }

        .select2-container--gt-dark .select2-results__options {
            max-height: 40vh !important;
            overflow-y: auto !important;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

        .select2-container--gt-dark .select2-results__option {
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        .select2-container--open,
        .select2-container--gt-dark .select2-dropdown {
            z-index: 10000 !important;
        }

        .input-group-text i {
            font-size: 16px !important; /* ขนาดไอคอนคงที่ */
        }

        .x-form-control {
            font-size: 16px !important; /* ปรับเฉพาะ input */
        }
    </style>

@endpush



@section('content')

    <div id="page-register" class="register-container sub-page sub-footer vhm-100">
        <div id="block-register" class="register-inner-content card shadow">
            <h4 class="card-title text-center pt-3">@{{ trans('app.login.register') }}</h4>
            <div class="card-body pt-0 px-0">
                <div class="theme-form">
                    {{-- ✅ ปิด HTML5 validation ให้ vee-validate คุมเอง --}}
                    <form method="POST" ref="form" action="{{ route('customer.session.register') }}" @submit.prevent="onSubmit" novalidate>
                        @csrf
                        @if($id)
                            <div id="zone-contributor">
                                <input type="hidden" id="marketing" name="marketing" value="{!! $id !!}">
                            </div>
                        @endif

                        {{-- กล่องแจ้งเตือนสั้น ๆ (ถ้าจะใช้) --}}
                        <small id="form-global-status" class="form-text text-center mb-2"></small>

                        <div class="container-fluid">
                            <div class="row g-3">

                                {{-- ธนาคาร --}}
                                <div class="col-12 col-md-12">
                                    <div class="text-danger small">@{{ trans('app.register.warning') }}</div>
                                    <label>@{{ trans('app.register.bank') }}</label>
                                    <select style="width:100%" class="form-control x-form-control bank-select" id="bank" name="bank"
                                            v-validate="'required'"
                                            :class="[errors.has('bank') ? 'is-invalid select2-hidden-accessible' : 'select2-hidden-accessible']">
                                        <option value="">@{{ trans('app.register.select_bank') }}</option>
                                        @php
                                            $lang = Session::get('lang', 'th');
                                            if($lang != 'th'){ $lang = 'en'; }
                                            $field = "name_{$lang}";
                                        @endphp
                                        @foreach($banks as $i => $bank)
                                            <option
                                                    data-img="{{ url('/storage/bank_img/'.$bank->filepic) }}"
                                                    value="{{ $bank->code }}" {{ old('bank') == $bank->code ? 'selected' : '' }}>
                                                {{ $bank->name_th .' - '. $bank->name_en }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="control-error text-warning text-center" v-if="errors.has('bank')">
                                        @{{ errors.first('bank') }}
                                    </small>
                                </div>

                                {{-- เลขบัญชี --}}
                                <div class="col-12 col-md-6">
                                    <label>@{{ trans('app.register.bank_account') }}</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="bi bi-credit-card-2-front-fill"></i></span>
                                        <input
                                                inputmode="numeric"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                                autocomplete="off"
                                                class="form-control x-form-control" id="acc_no"
                                                minlength="5" maxlength="12"
                                                :data-vv-as="trans('app.register.bank_account')"
                                                value="{{ old('acc_no') }}"
                                                v-validate="'required|min:5|numeric'"
                                                :class="[errors.has('acc_no') ? 'is-invalid' : '']"
                                                name="acc_no"
                                                :placeholder="trans('app.register.bank_placeholder')"
                                                type="text">
                                    </div>
                                    <small id="account-status" class="form-text text-center"></small>
                                    <small class="control-error text-warning text-center" v-if="errors.has('acc_no')">
                                        @{{ errors.first('acc_no') }}
                                    </small>
                                </div>

                                {{-- ชื่อ-นามสกุล --}}
                                <div class="col-12 col-md-6">
                                    <label>@{{ trans('app.register.name') }} @{{ trans('app.register.surname') }}</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="bi bi-person-lines-fill"></i></span>
                                        <input autocomplete="off" class="form-control x-form-control" id="name"
                                               name="name"
                                               v-validate="'required'"
                                               :class="[errors.has('name') ? 'is-invalid' : '']"
                                               :data-vv-as="'name'"
                                               value="{{ old('name') }}"
                                               :placeholder="trans('app.register.no_position')" type="text">
                                    </div>
                                    <small class="control-error text-warning text-center" v-if="errors.has('name')">
                                        @{{ errors.first('name') }}
                                    </small>
                                </div>

                                {{-- เบอร์โทร --}}
                                <div class="col-12 col-md-6">
                                    <label class="text-content">@{{ trans('app.register.tel') }}</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="bi bi-phone-fill"></i></span>
                                        <input
                                                autocomplete="off"
                                                :data-vv-as="trans('app.register.tel')"
                                                class="form-control x-form-control" id="user_name1"
                                                name="user_name" maxlength="10" minlength="10"
                                                :placeholder="trans('app.register.username_placeholder')"
                                                value="{{ old('user_name') }}"
                                                v-validate="'required'"
                                                :class="[errors.has('user_name') ? 'is-invalid' : '']"
                                                pattern="[0-9]*" inputmode="numeric"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                                type="text">
                                    </div>
                                    <small id="phone-status" class="form-text text-center"></small>
                                    <small class="control-error text-warning text-center" v-if="errors.has('user_name')">
                                        @{{ errors.first('user_name') }}
                                    </small>
                                </div>

                                {{-- รหัสผ่าน --}}
                                <div class="col-12 col-md-6">
                                    <label>@{{ trans('app.register.password') }}</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                        <input autocomplete="off"
                                               minlength="6" maxlength="10"
                                               :data-vv-as="trans('app.register.password')"
                                               class="form-control x-form-control input-password" id="password1"
                                               v-validate="'required|min:6|max:10'"
                                               value="{{ old('password') }}"
                                               :class="[errors.has('password') ? 'is-invalid' : '' ]"
                                               name="password" :placeholder="trans('app.register.password')"
                                               type="text"
                                               ref="password">
                                    </div>
                                    <small class="control-error text-warning text-center" v-if="errors.has('password')">
                                        @{{ errors.first('password') }}
                                    </small>
                                </div>

                                {{-- refer (ซ่อน) --}}
                                <div class="col-12 col-md-6" style="display:none;">
                                    <label>@{{ trans('app.register.refer') }}</label>
                                    <select class="form-control x-form-control" id="refer" name="refer"
                                            v-validate="'required'"
                                            :data-vv-as="trans('app.register.refer')"
                                            :class="[errors.has('refer') ? 'is-invalid' : '']">
                                        @foreach($refers as $i => $refer)
                                            <option value="{{ $refer->code }}">{{ $refer->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="control-error text-warning text-center" v-if="errors.has('refer')">
                                        @{{ errors.first('refer') }}
                                    </small>
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex flex-column flex-md-row w-100 gap-2 mt-1">
                                <button type="submit" class="btn btn-success rounded-pill w-100 regisbtn">
                                    <i class="bi bi-person-plus-fill"></i> @{{ trans('app.login.register') }}
                                </button>
                            </div>

                        </div>
                    </form>

                    <div class="d-inline-flex w-100 mt-3 justify-content-between">
                        <div></div>
                        <div>
                            <a :href="'{{ $webconfig->linelink }}'" target="_blank"
                               class="btn btn-link btn-sm text-white">@{{ trans('app.login.help') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        if (window.Vue && !Vue.prototype.trans) {
            Vue.prototype.trans = function (key, replace) {
                replace = replace || {};
                try {
                    let parts = key.replace(/^app\./, '').split('.');
                    let cur = (window.I18nStore?.dict?.app || window.I18nStore?.dict) || {};
                    for (const p of parts) cur = cur?.[p];
                    let text = typeof cur === 'string' ? cur : key;
                    for (const k in replace) text = text.replace(':'+k, replace[k]);
                    return text;
                } catch {
                    return key;
                }
            };
        }
    </script>
    <script>
        function checkFormReady() {
            const name = $('#name').val().trim();
            const password = $('#password1').val().trim();
            const bank = $('#bank').val();
            const phoneOk = true;
            const bankOk  = true;

            const isReady = !!(name && password && bank && phoneOk && bankOk);
            $('.regisbtn').prop('disabled', !isReady);
            return isReady;
        }

        function isNumeric(str) {
            return /^\d+$/.test(str);
        }

        function syncBank18FromPhone() {
            const isBank18 = ($('#bank').val() === '18');
            const phone = $('#user_name1').val().trim();

            if (isBank18) {
                $('#acc_no').val(phone).prop('readonly', true).trigger('change');

                const phoneMsg = $('#phone-status').text();
                if (phoneMsg) {
                    $('#account-status')
                        .text(phoneMsg)
                        .css('color', window.__REG__?.phoneOK ? 'green' : 'red');
                } else {
                    $('#account-status').text(Vue.prototype.trans('app.status.check')).css('color', 'gray');
                }
                window.__REG__.bankOK = (window.__REG__?.phoneOK === true);

            } else {
                $('#acc_no').prop('readonly', false);
                window.__REG__.bankOK = false;
                $('#account-status').text('').css('color', '');
            }

            checkFormReady();
        }

        (function keepSelect2HiddenAccessible(){
            const el = document.getElementById('bank');
            if (!el) return;
            const fix = () => {
                if (!el.classList.contains('select2-hidden-accessible')) {
                    el.classList.add('select2-hidden-accessible');
                }
            };
            const obs = new MutationObserver(fix);
            obs.observe(el, { attributes: true, attributeFilter: ['class'] });
        })();

        (function waitForJQuery() {
            if (typeof window.jQuery !== 'undefined') {
                $(function () {
                    // --- Select2 init (ปิดช่อง search กัน input โผล่) ---
                    $(".bank-select").select2({
                        theme: 'gt-dark',
                        width: '100%',
                        placeholder: Vue.prototype.trans('app.register.select_bank'),
                        allowClear: false,
                        minimumResultsForSearch: Infinity, // ✅ ปิด search-box
                        containerCssClass: 's2-lg',
                        dropdownCssClass:  's2-lg s2-dropdown',
                        dropdownParent: $(document.body),
                        templateResult: function(option) {
                            if (!option.id) return option.text;
                            return $('<span><img src="' + $(option.element).data('img') + '" width="20"> ' + option.text + '</span>');
                        },
                        templateSelection: function(option) {
                            if (!option.id) return option.text;
                            return $('<span><img src="' + $(option.element).data('img') + '" width="20"> ' + option.text + '</span>');
                        }
                    });

                    // --- มิเรอร์ class is-invalid จาก <select> → กล่อง Select2 ---
                    (function mirrorInvalidToSelect2(){
                        const target = document.getElementById('bank');
                        if (!target) return;

                        const toggle = () => {
                            const has = target.classList.contains('is-invalid');
                            const $sel = $('#bank').data('select2')?.$container?.find('.select2-selection');
                            if ($sel) $sel.toggleClass('is-invalid', !!has);
                        };

                        // ดูการเปลี่ยนแปลง class ของ select
                        const obs = new MutationObserver(toggle);
                        obs.observe(target, { attributes: true, attributeFilter: ['class'] });

                        // ครั้งแรก
                        toggle();

                        // เมื่อผู้ใช้เปลี่ยนค่า ให้เอา invalid ออกทันที
                        $('#bank').on('change', () => {
                            const $sel = $('#bank').data('select2')?.$container?.find('.select2-selection');
                            if ($sel) $sel.removeClass('is-invalid');
                        });
                    })();

                    // ==== โค้ดตรวจเบอร์ ====
                    let phoneTimer;
                    $('#user_name1').on('input', function () {
                        clearTimeout(phoneTimer);
                        const phone = document.getElementById('user_name1').value.trim();
                        const status = document.getElementById('phone-status');
                        const status1 = document.getElementById('account-status');

                        if (!isNumeric(phone)) {
                            status.innerText =  Vue.prototype.trans('app.register.numberonly');
                            status.style.color = 'red';
                            return;
                        }

                        phoneTimer = setTimeout(function () {
                            if (phone.length === 10) {

                                axios.post("{{ route('customer.check.phone') }}", {username: phone})
                                    .then(res => {
                                        const status = document.getElementById('phone-status');
                                        if (res.data.exists) {
                                            if ($('#bank option:selected').val() === '18') {
                                                $('#acc_no').val(phone)
                                                status1.innerText = res.data.message;
                                                status1.style.color = 'red';
                                            }
                                            status.innerText = res.data.message;
                                            status.style.color = 'red';
                                        } else {
                                            if ($('#bank option:selected').val() === '18') {
                                                $('#acc_no').val(phone)
                                                status1.innerText = res.data.message;
                                                status1.style.color = 'green';
                                            }
                                            status.innerText = res.data.message;
                                            status.style.color = 'green';
                                        }
                                    })
                                    .catch(() => {
                                        status.innerText = Vue.prototype.trans('app.status.error');
                                        status.style.color = 'gray';
                                    });

                            } else {
                                if ($('#bank option:selected').val() === '18') {
                                    $('#acc_no').val('')
                                    status1.innerText = '';
                                }
                                status.innerText = '';
                            }
                        }, 500);
                    });

                    // ==== โค้ดตรวจบัญชีธนาคาร ====
                    let bankTimer;
                    $('#bank, #acc_no').on('input change', function () {
                        clearTimeout(bankTimer);

                        const bank = document.getElementById('bank').value;
                        const account = document.getElementById('acc_no').value.trim();
                        const status = document.getElementById('account-status');

                        if (!isNumeric(account)) {
                            status.innerText = Vue.prototype.trans('app.register.numberonly');
                            status.style.color = 'red';
                            return;
                        }

                        if (bank == 18) return;

                        if (bank && account.length >= 10) {
                            bankTimer = setTimeout(function () {

                                axios.post("{{ route('customer.check.bank') }}", {bank: bank, acc_no: account})
                                    .then(res => {

                                        if (res.data.valid) {
                                            status.innerText = res.data.message;
                                            status.style.color = 'red';
                                        } else {
                                            $('#name').val(res.data.firstname+' '+res.data.lastname);
                                            status.innerText = res.data.message;
                                            status.style.color = 'green';
                                        }
                                    })
                                    .catch(() => {
                                        status.innerText = Vue.prototype.trans('app.status.error');
                                        status.style.color = 'gray';
                                    });

                            }, 500);
                        } else {
                            status.innerText = '';
                        }
                    });

                    // toggle element สำหรับบางธนาคาร
                    $('#bank').on('change', function () {
                        if ($('#bank option:selected').val() === '18') {
                            $('.acc_no_tw').css('display', 'block');
                            $('.tw').css('display', 'block');
                        } else {
                            $('.acc_no_tw').css('display', 'none');
                            $('.tw').css('display', 'none');
                        }
                    });

                    // เมื่อสลับภาษา: รี-init select2 ให้ placeholder เปลี่ยนตามภาษา
                    window.addEventListener('app:locale:changed', function(){
                        try{
                            const placeholder = Vue.prototype.trans('app.register.select_bank');
                            const $bank = $('#bank');
                            if ($bank.data('select2')) $bank.select2('destroy');
                            $('.bank-select').select2({
                                theme: 'gt-dark',
                                width: '100%',
                                placeholder,
                                allowClear: false,
                                minimumResultsForSearch: Infinity,
                                containerCssClass: 's2-lg',
                                dropdownCssClass:  's2-lg s2-dropdown',
                                dropdownParent: $(document.body),
                                templateResult: function(option) {
                                    if (!option.id) return option.text;
                                    return $('<span><img src="' + $(option.element).data('img') + '" width="20"> ' + option.text + '</span>');
                                },
                                templateSelection: function(option) {
                                    if (!option.id) return option.text;
                                    return $('<span><img src="' + $(option.element).data('img') + '" width="20"> ' + option.text + '</span>');
                                }
                            });
                        }catch(e){}
                    });
                });
            } else {
                setTimeout(waitForJQuery, 50);
            }
        })();
    </script>
@endpush
