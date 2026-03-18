{{-- extend layout --}}
@extends('wallet::layouts.app')

{{-- page title --}}
@section('title','')

@push('styles')
    <style>
        .homelogin {
            display: none !important;
        }
        .languages {
            gap: 8px; /* ระยะห่างระหว่างธง */
        }
        .flag {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid #555;
            cursor: pointer;
        }
    </style>
@endpush

@section('content')

    <div class="bg-member sub-page sub-footer vhm-100"
         style="display: flex; justify-content: center; align-items: center;">
        <div class="login-container card mt-2">
            <h3 class="text-center pt-3">@{{ trans('app.login.login') }}</h3>
            <img src="{{ url(core()->imgurl($webconfig->logo,'img')) }}" class="card-img-top px-1 w-100"
                 style="object-fit: contain; height: 7em;" alt="เข้าสู่ระบบ">
            <div class="card-body pt-0 px-0">
                <div>
                    <span class="text-content d-block text-center mb-2">@{{ trans('app.login.promote') }}</span>
                    <form class="theme-form" method="POST" action="{{ route('customer.session.create') }}" onsubmit="return;">
                        @csrf
                        <div class="input-group mb-1">
                            <span class="input-group-text"><i class="bi bi-person-fill bi-1-5x"></i></span>
                            <input class="form-control" type="tel" :placeholder="trans('app.register.tel')"
                                   id="user_name" name="user_name" maxlength="10" required="">
                        </div>
                        <div class="input-group mb-2">
                            <span class="input-group-text"><i class="bi bi-key-fill bi-1-5x"></i></span>
                            <input class="form-control" type="password" id="password" name="password"
                                   minlength="6" maxlength="10" required="" :placeholder="trans('app.register.password')">
                        </div>

                        <button class="btn btn-custom-primary w-100 mt-3 rounded-pill fw-bolder" id="btnLog"
                                type="submit">@{{ trans('app.login.login') }}</button>

                        <div class="languages d-flex flex-wrap justify-content-center gap-2 mt-3">
                            @foreach($languages as $code => $lang)
                                <img class="flag" data-lang="{{ $code }}" src="https://flagcdn.com/w40/{{ $lang['code'] }}.png"
                                     alt="{{ $code }}" title="{{ $lang['name'] }}" onclick="AppLocale.switchTo('{{ $code }}')">
                            @endforeach
                        </div>
                        <div class="d-inline-flex w-100 mt-3 justify-content-between">
                            <div>
                                <a href="{{ route('customer.session.store') }}"
                                   class="btn btn-link btn-sm">@{{ trans('app.login.register') }}</a>
                            </div>
                            <div>
                                <a href="{{ $webconfig->linelink }}" target="_blank"
                                   class="btn btn-link btn-sm text-white">@{{ trans('app.login.help') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
