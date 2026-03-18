<form method="POST" action="{{ route('customer.session.register') }}"
      @submit.prevent="onSubmit">
    @csrf
    @if($id)
        <div id="zone-contributor">
        <input type="hidden" id="upline" name="upline" value="{!! $id !!}">
            <div class="form-group my-2">
                <div>
                    <label> {{ __('app.register.upline') }} </label>
                    <div class="el-input my-1">
                        <i class="fas fa-hands-helping"></i>
                        <input autocomplete="off" class="inputstyle" readonly value="{!! $contributor !!}" type="text">
                    </div>
                </div>
            </div>
        </div>
        <hr class="x-hr-border-glow my-0">
    @endif
    {{--                            <input type="hidden" id="firstname" name="firstname">--}}
    {{--                            <input type="hidden" id="lastname" name="lastname">--}}
    <div id="zone-acc">

        <div class="form-group my-2">
            <div>
                <label> {{ __('app.register.bank') }} *</label>
                <div class="el-input my-1">
                    <i class="fal fa-university"></i>
                    <select class="inputstyle" id="bank" name="bank"
                            v-validate="'required'"
                            :class="[errors.has('bank') ? 'is-invalid' : '']">
                        <option value="">{{ __('app.register.select_bank') }}</option>
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
                <label> {{ __('app.register.bank_account') }} *</label>
                <div class="el-input my-1">
                    <i class="fal fa-money-check-alt"></i>
                    <input autocomplete="off" class="inputstyle" id="acc_no"
                           minlength="5"
                           data-vv-as="&quot;{{ __('app.register.bank_account') }}&quot;"
                           value="{{ old('acc_no') }}"
                           v-validate="'required|min:5|numeric'"
                           :class="[errors.has('acc_no') ? 'is-invalid' : '']"
                           name="acc_no"
                           placeholder="{{ __('app.register.bank_placeholder') }}"
                           type="text">
                </div>
                <span class="control-error text-warning"
                      v-if="errors.has('acc_no')">@{{ errors.first('acc_no') }}</span>

            </div>
        </div>

        <div class="form-group my-2 wallet_id">
            <div>
                <label> {{ __('app.register.wallet_id') }}</label>
                <div class="el-input my-1">
                    <i class="fal fa-wallet"></i>
                    <input autocomplete="off" class="inputstyle" id="wallet_id"
                           v-validate="'max:20'"
                           data-vv-as="&quot;{{ __('app.register.wallet_id') }}&quot;"
                           value="{{ old('wallet_id') }}"
                           :class="[errors.has('wallet_id') ? 'is-invalid' : '']"
                           name="wallet_id"
                           placeholder="{{ __('app.register.wallet_id_placeholder') }}" type="text">
                </div>
                <span class="control-error text-warning" v-if="errors.has('wallet_id')">@{{ errors.first('wallet_id') }}</span>

            </div>
        </div>

        <p class="wallet_id text-center">{{ __('app.register.wallet_id_1') }}</p>
        <p class="wallet_id text-center">{{ __('app.register.wallet_id_2') }}</p>
        <p class="tw" style="display:none">{{ __('app.register.wallet_id_3') }}</p>
    </div>

    <div id="zone-user">

        <div class="form-group my-2">
            <div>
                <label> {{ __('app.register.name') }} *</label>
                <div class="el-input my-1">
                    <i class="fal fa-user"></i>
                    <input autocomplete="off" class="inputstyle" id="firstname"
                           name="firstname"
                           v-validate="'required'"
                           :class="[errors.has('firstname') ? 'is-invalid' : '']"
                           data-vv-as="&quot;firstname&quot;"
                           value="{{ old('firstname') }}"
                           placeholder="{{ __('app.register.name') }}" type="text">

                </div>
            </div>
        </div>

        <div class="form-group my-2">
            <div>
                <label> {{ __('app.register.surname') }} *</label>
                <div class="el-input my-1">
                    <i class="fal fa-user"></i>
                    <input autocomplete="off" class="inputstyle"
                           name="lastname"
                           v-validate="'required'"
                           value="{{ old('lastname') }}"
                           :class="[errors.has('lastname') ? 'is-invalid' : '']"
                           id="lastname" placeholder="{{ __('app.register.surname') }}" type="text">
                </div>
            </div>
        </div>

        <div class="form-group my-2">
            <div>
                <label> {{ __('app.register.tel') }} *</label>
                <div class="el-input my-1">
                    <i class="fal fa-mobile-alt"></i>
                    <input autocomplete="off" class="inputstyle"
                           id="tel"
                           name="tel"
                           data-vv-as="&quot;{{ __('app.register.tel') }}&quot;"
                           placeholder="{{ __('app.register.tel') }}"
                           value="{{ old('tel') }}"
                           v-validate="'required'"
                           :class="[errors.has('tel') ? 'is-invalid' : '']"
                           type="text"
                           data-inputmask="'mask': '(999)-999-9999'">
                </div>
                <span class="control-error text-warning" v-if="errors.has('tel')">@{{ errors.first('tel') }}</span>

            </div>
        </div>

        <div class="form-group my-2">
            <div>
                <label> {{ __('app.register.line_id') }}</label>
                <div class="el-input my-1">
                    <i class="fab fa-line"></i>
                    <input autocomplete="off" class="inputstyle" id="lineid"
                           name="lineid"
                           data-vv-as="&quot;{{ __('app.register.line_id') }}&quot;"
                           value="{{ old('lineid') }}"
                           :class="[errors.has('lineid') ? 'is-invalid' : '']"
                           placeholder="{{ __('app.register.line_id') }}" type="text">
                </div>
            </div>
        </div>

        <div class="form-group my-2">
            <div>
                <label> {{ __('app.register.username') }} *</label>
                <div class="el-input my-1">
                    <i class="fal fa-user-alt"></i>
                    <input autocomplete="off"
                           data-vv-as="&quot;{{ __('app.register.username') }}&quot;"
                           class="inputstyle text-lowercase" id="user_name"
                           name="user_name" maxlength="10"
                           placeholder="{{ __('app.register.username_placeholder') }}"
                           value="{{ old('user_name') }}"
                           v-validate="'required|min:5|max:10'"
                           :class="[errors.has('user_name') ? 'is-invalid' : '']"
                           type="text">
                </div>
                <span class="control-error text-warning" v-if="errors.has('user_name')">@{{ errors.first('user_name') }}</span>
                <p class="text-center">{{ __('app.register.username_remark') }}</p>
            </div>
        </div>

        <div class="form-group my-2">
            <div>
                <label> {{ __('app.register.password') }} *</label>
                <div class="el-input my-1">
                    <i class="fal fa-lock"></i>
                    <input autocomplete="off"
                           data-vv-as="&quot;{{ __('app.register.password') }}&quot;"
                           class="inputstyle" id="password"
                           v-validate="'required|min:6'"
                           value="{{ old('password') }}"
                           :class="[errors.has('password') ? 'is-invalid' : '']"
                           name="password" placeholder="{{ __('app.register.password') }}" type="password" ref="password">

                </div>
                <span class="control-error text-warning" v-if="errors.has('password')">@{{ errors.first('password') }}</span>
            </div>
        </div>

        <div class="form-group my-2">
            <div>
                <label> {{ __('app.register.password_confirm') }} *</label>
                <div class="el-input my-1">
                    <i class="fal fa-lock"></i>
                    <input autocomplete="off"
                           class="inputstyle" id="password_confirm"
                           v-validate="'required|min:6|confirmed:password'"
                           :class="[errors.has('password_confirm') ? 'is-invalid' : '']"
                           name="password_confirm"
                           placeholder="{{ __('app.register.password_confirm') }}"
                           value="{{ old('password_confirm') }}"
                           type="password">
                </div>
                <span class="control-error text-warning" v-if="errors.has('password')">@{{ errors.first('password') }}</span>
            </div>
        </div>

        @if($config->seamless == 'Y')

            <div class="form-group my-2">
                <div>
                    <label> {{ __('app.register.promotion') }}</label>
                    <div class="el-input my-1">
                        <i class="fal fa-gift"></i>
                        <select class="inputstyle" id="promotion" name="promotion"
                                v-validate="'required'"
                                :class="[errors.has('promotion') ? 'is-invalid' : '']">
                            <option value="N">{{ __('app.register.promotion_no') }}</option>
                            <option value="Y">{{ __('app.register.promotion_yes') }}</option>
                        </select>
                    </div>
                </div>
            </div>

        @else

            @if($config->multigame_open == 'N')

                <div class="form-group my-2">
                    <div>
                        <label> {{ __('app.register.promotion') }}</label>
                        <div class="el-input my-1">
                            <i class="fal fa-gift"></i>
                            <select class="inputstyle" id="promotion" name="promotion"
                                    v-validate="'required'"
                                    :class="[errors.has('promotion') ? 'is-invalid' : '']">
                                <option value="N">{{ __('app.register.promotion_no') }}</option>
                                <option value="Y">{{ __('app.register.promotion_yes') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

            @endif

        @endif

        <div class="form-group my-2">
            <div>
                <label> {{ __('app.register.refer') }}</label>
                <div class="el-input my-1">
                    <i class="fas fa-asterisk"></i>
                    <select class="inputstyle" id="refer" name="refer"
                            v-validate="'required'"
                            data-vv-as="&quot;{{ __('app.register.refer') }}&quot;"
                            :class="[errors.has('refer') ? 'is-invalid' : '']">
                        @foreach($refers as $i => $refer)
                            <option value="{{ $refer->code }}">{{ __('app.other.'.$refer->name) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>


        <div class="row mt-2 text-center">
            <recapcha align="center" class="g-recaptcha" badge="inline" tabindex="9999" sitekey="{{ config('capcha.website') }}"></recapcha>
        </div>
        <div class="row mt-2">
            <button id="btnsubmit" class="btn btn-primary btn-block" style="border: none"
                    disabled><i
                    class="fas fa-user-plus"></i> {{ __('app.register.register') }}
            </button>
        </div>
    </div>

</form>

@once
    @push('scripts')
        <script src="https://www.google.com/recaptcha/api.js?onload=vueRecaptchaApiLoaded&render=explicit" async
                defer></script>
        <script src="{{ asset('vendor/inputmask/jquery.inputmask.js') }}"></script>
        <script>
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
