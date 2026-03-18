<div class="container" id="app">
    <div class="x-register-tab-container2 -login js-tab-pane-checker-v3">

        <form method="POST" action="{{ route('customer.session.register') }}" @submit.prevent="onSubmit">
            @csrf
            @if($id)
                <div id="zone-contributor">
                    <input type="hidden" id="upline" name="upline" value="{!! $id !!}">
                    <div class="form-group my-2">
                        <div>
                            <label> {{ __('app.register.upline') }} </label>
                            <div class="el-input my-1">

                                <input autocomplete="off" class="form-control x-form-control" readonly
                                       value="{!! $contributor !!}" type="text">
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="x-hr-border-glow my-0">
            @endif

            <div id="zone-acc">

                <div class="form-group my-2">
                    <div>
                        <label> {{ __('app.register.bank') }} *</label>
                        <div class="el-input my-1">

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
                    </div>
                </div>

                <div class="form-group my-2">
                    <div>
                        <label> {{ __('app.register.bank_account') }} *</label>
                        <div class="el-input my-1">

                            <input autocomplete="off" class="form-control x-form-control" id="acc_no"
                                   minlength="5"

                                   data-vv-as="&quot;{{ __('app.register.bank_account') }}&quot;"
                                   value="{{ old('acc_no') }}"
                                   v-validate="'required|min:5|numeric'"
                                   :class="[errors.has('acc_no') ? 'is-invalid' : '']"
                                   name="acc_no"
                                   placeholder="{{ __('app.register.bank_placeholder') }}"
                                   type="text">
                        </div>
                        <span class="control-error text-warning" v-if="errors.has('acc_no')">@{{ errors.first('acc_no') }}</span>
                        <small id="account-status" class="form-text"></small>
                        {{--                        @error('acc_no')--}}
                        {{--                        <span--}}
                        {{--                            class="control-error text-warning @error('acc_no') is-invalid @enderror">{{ $message }}</span>--}}
                        {{--                        @enderror--}}
                    </div>
                </div>

                <div class="form-group my-2 wallet_id" style="display:none">
                    <div>
                        <label> {{ __('app.register.wallet_id') }}</label>
                        <div class="el-input my-1">

                            <input autocomplete="off" class="form-control x-form-control" id="wallet_id"
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

                <p class="tw" style="display:none">{{ __('app.register.wallet_id_3') }}</p>
            </div>

            <div id="zone-user">

                <div class="form-group my-2 mt-5">
                    <div>
                        <label> {{ __('app.register.name') }} *</label>
                        <div class="el-input my-1">

                            <input autocomplete="off" class="form-control x-form-control" id="firstname"
                                   name="firstname"
                                   v-validate="'required'"
                                   :class="[errors.has('firstname') ? 'is-invalid' : '']"
                                   data-vv-as="&quot;firstname&quot;"
                                   value="{{ old('firstname') }}"
                                   placeholder="{{ __('app.register.name') }}" type="text">

                        </div>
                        <span class="control-error text-warning" v-if="errors.has('firstname')">@{{ errors.first('firstname') }}</span>
                    </div>
                </div>

                <div class="form-group my-2">
                    <div>
                        <label> {{ __('app.register.surname') }} *</label>
                        <div class="el-input my-1">

                            <input autocomplete="off" class="form-control x-form-control"
                                   name="lastname"
                                   v-validate="'required'"
                                   value="{{ old('lastname') }}"
                                   :class="[errors.has('lastname') ? 'is-invalid' : '']"
                                   id="lastname" placeholder="{{ __('app.register.surname') }}" type="text">
                        </div>
                        <span class="control-error text-warning" v-if="errors.has('lastname')">@{{ errors.first('lastname') }}</span>
                    </div>
                </div>


                <div class="form-group my-2" style="display:none">
                    <div>
                        <label> {{ __('app.register.line_id') }}</label>
                        <div class="el-input my-1">

                            <input autocomplete="off" class="form-control x-form-control" id="lineid"
                                   name="lineid"
                                   data-vv-as="&quot;{{ __('app.register.line_id') }}&quot;"
                                   value="{{ old('lineid') }}"
                                   :class="[errors.has('lineid') ? 'is-invalid' : '']"
                                   placeholder="{{ __('app.register.line_id') }}" type="text">
                        </div>
                    </div>
                </div>


                <div class="form-group my-2 mt-5">
                    <div>
                        <label> {{ __('app.register.username') }} *</label>
                        <div class="el-input my-1">

                            <input autocomplete="off"
                                   data-vv-as="&quot;{{ __('app.register.username') }}&quot;"
                                   class="form-control x-form-control" id="user_name1"
                                   name="user_name" maxlength="10" minlength="10"
                                   placeholder="{{ __('app.register.username_placeholder') }}"
                                   value="{{ old('user_name') }}"
                                   v-validate="'required'"
                                   :class="[errors.has('user_name') ? 'is-invalid' : '']"
                                   type="text">
                        </div>

                        <span class="control-error text-warning" v-if="errors.has('user_name')">@{{ errors.first('user_name') }}</span>
                        <small id="phone-status" class="form-text"></small>
                        {{--                        @error('user_name')--}}
                        {{--                        <span--}}
                        {{--                            class="control-error text-warning @error('user_name') is-invalid @enderror">{{ $message }}</span>--}}
                        {{--                        @enderror--}}
                        {{--                        <span class="control-error text-warning" v-if="errors.has('user_name')">@{{ errors.first('user_name') }}</span>--}}
                        {{--                        <p class="text-center">{{ __('app.register.username_remark') }}</p>--}}
                    </div>
                </div>


                <div class="form-group my-2">
                    <div>
                        <label> {{ __('app.register.password') }} *</label>
                        <div class="el-input my-1 input-wrapper">

                            <input autocomplete="off"
                                   maxlength="10"
                                   data-vv-as="&quot;{{ __('app.register.password') }}&quot;"
                                   class="form-control x-form-control input-password" id="password1"
                                   v-validate="'required|min:6|max:10'"
                                   value="{{ old('password') }}"
                                   :class="[errors.has('password') ? 'is-invalid' : '']"
                                   name="password" placeholder="{{ __('app.register.password') }}" type="password"
                                   ref="password">
                            <span class="toggle-icon" onclick="togglePassword()">
                                 <i id="toggle-icon" class="fa fa-eye"></i>
                            </span>
                        </div>
                        <span class="control-error text-warning" v-if="errors.has('password')">@{{ errors.first('password') }}</span>


                        {{--                        @error('password')--}}
                        {{--                        <span--}}
                        {{--                            class="control-error text-warning @error('password') is-invalid @enderror">{{ $message }}</span>--}}
                        {{--                        @enderror--}}
                        {{--                        <span class="control-error text-warning" v-if="errors.has('password')">@{{ errors.first('password') }}</span>--}}
                    </div>
                </div>

                {{--                <div class="form-group my-2">--}}
                {{--                    <div>--}}
                {{--                        <label> {{ __('app.register.password_confirm') }} *</label>--}}
                {{--                        <div class="el-input my-1">--}}

                {{--                            <input autocomplete="off"--}}
                {{--                                   class="form-control x-form-control" id="password_confirm"--}}
                {{--                                   v-validate="'required|min:6|confirmed:password'"--}}
                {{--                                   :class="[errors.has('password_confirm') ? 'is-invalid' : '']"--}}
                {{--                                   name="password_confirm"--}}
                {{--                                   placeholder="{{ __('app.register.password_confirm') }}"--}}
                {{--                                   value="{{ old('password_confirm') }}"--}}
                {{--                                   type="password">--}}
                {{--                        </div>--}}
                {{--                        <span class="control-error text-warning" v-if="errors.has('password_confirm')">@{{ errors.first('password_confirm') }}</span>--}}

                {{--                        @error('password_confirm')--}}
                {{--                        <span--}}
                {{--                            class="control-error text-warning @error('password_confirm') is-invalid @enderror">{{ $message }}</span>--}}
                {{--                        @enderror--}}
                {{--                        --}}{{--                        <span class="control-error text-warning" v-if="errors.has('password')">@{{ errors.first('password') }}</span>--}}
                {{--                    </div>--}}
                {{--                </div>--}}

                @if($config->seamless == 'Y')

                    <div class="form-group my-2 mt-5" style="display:none">
                        <div>
                            <label> {{ __('app.register.promotion') }}</label>
                            <div class="el-input my-1">
                                <select class="form-control x-form-control" id="promotion" name="promotion"
                                        v-validate="'required'"
                                        :class="[errors.has('promotion') ? 'is-invalid' : '']">
                                    <option value="Y" selected>{{ __('app.register.promotion_no') }}</option>
                                    <option value="N">{{ __('app.register.promotion_yes') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                @else

                    @if($config->multigame_open == 'N')

                        <div class="form-group my-2 mt-5">
                            <div>
                                <label> {{ __('app.register.promotion') }}</label>
                                <div class="el-input my-1">

                                    <select class="form-control x-form-control" id="promotion" name="promotion"
                                            v-validate="'required'"
                                            :class="[errors.has('promotion') ? 'is-invalid' : '']">
                                        <option value="N" selected>{{ __('app.register.promotion_no') }}</option>
                                        <option value="Y">{{ __('app.register.promotion_yes') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    @endif

                @endif

                <div class="form-group my-2 mt-5">
                    <div>
                        <label> {{ __('app.register.refer') }}</label>
                        <div class="el-input my-1">

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
                </div>


                <div class="row mt-5">
                    <button id="btnsubmit" class="btn btn-primary btn-block" style="border: none"
                    ><i
                                class="fas fa-user-plus"></i> {{ __('app.register.register') }}
                    </button>
                </div>
            </div>

        </form>

    </div>
</div>


