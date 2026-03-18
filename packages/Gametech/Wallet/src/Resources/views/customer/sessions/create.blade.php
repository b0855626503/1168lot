{{-- extend layout --}}
@extends('wallet::layouts.app')

{{-- page title --}}
@section('title','')


@section('content')

    <div class="headregislogin">
        <div class="row m-0">
            <div class="col-6 p-1 active">
                <img class="gif" src="images/icon/login_{{ $lang }}.gif">
{{--                <img class="png" src="images/icon/login.png">--}}
                <img class="png" src="images/icon/login_{{ $lang }}.png">
            </div>
            <div class="col-6 p-1" onclick="location.href='{{ route('customer.session.store') }}'">
                <img class="gif" src="images/icon/regis_{{ $lang }}.gif">
{{--                <img class="png" src="images/icon/regis.png">--}}
                <img class="png" src="images/icon/regis_{{ $lang }}.png">
            </div>
        </div>


    </div>

    <div class="px-1">

        <section class="sectionpage login">
            <div class="bginputlogin">
                <img class="ic-lg-01" src="images/icon/chip.png">
                <img class="ic-lg-02" src="images/icon/card.png">
                <div class="logologin">
                    {!! core()->showImg($config->logo,'img','','','') !!}
                </div>
                <div class="inbgbeforelogin">

                    <div class="insidectloginmb">
                        <div class="headerlogin my-2"><h2>{{ __('app.login.login') }}</h2></div>
                        <form method="POST" action="{{ route('customer.session.create') }}"
                              @submit.prevent="onSubmit">
                            @csrf

                            <div>

                                <div class=" form-group my-2">
                                    <div>
                                        <label> {{ __('app.login.username') }}</label>
                                        <div class="el-input my-1">
                                            <i class="fas fa-user"></i>
                                            <input class="inputstyle text-lowercase" v-validate="'required'"
                                                   :class="[errors.has('user_name') ? 'is-invalid' : '']"
                                                   id="user_name" name="user_name" maxlength="10"
                                                   data-vv-as="&quot;Username&quot;"
                                                   value="{{ old('user_name') }}" placeholder="{{ __('app.login.login') }}">
                                        </div>
                                    </div>
                                </div>


                                <div class="form-group my-4">
                                    <div>
                                        <label>{{ __('app.login.password') }}</label>
                                        <div class="el-input my-1">
                                            <i class="fas fa-lock"></i>
                                            <input type="password" class="inputstyle"
                                                   v-validate="'required|min:6'"
                                                   :class="[errors.has('password') ? 'is-invalid' : '']"
                                                   id="password" name="password"
                                                   data-vv-as="&quot;Password&quot;"
                                                   placeholder="{{ __('app.login.password') }}">
                                        </div>
                                    </div>
                                </div>


                            </div>

                            <button class="loginbtn mt-3">
              <span>
              {{ __('app.login.login') }}
              </span>
                            </button>
                        </form>
                        <div class="wantregister">{{ __('app.login.no_username') }} <a class="loginbtn"
                                                                    href="{{ route('customer.session.store') }}">{{ __('app.login.register_now') }}</a>
                        </div>
                        @if($config->notice)
                            <div class="my-4">
                        <p class="text-center">{{ $config->notice }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

    </div>

@endsection
