{{-- extend layout --}}
@extends('wallet::layouts.app')

{{-- page title --}}
@section('title','')

@push('styles')
    <style>
        body {
            height: initial !important;
            width: initial !important;
        }

        html {
            height: initial !important;
            width: initial !important;
        }

        .g-recaptcha > div {
            margin-top: 1em;
            text-align: center;
            width: auto !important;
            height: auto !important;
        }
    </style>
@endpush

@section('content')
    <div class="headregislogin">
        <div class="row m-0">
            <div class="col-6 p-1" onclick="location.href='{{ route('customer.home.index') }}'">
                <img class="gif" src="images/icon/login_{{ $lang }}.gif">
                <img class="png" src="images/icon/login_{{ $lang }}.png">
            </div>
            <div class="col-6 p-1 active">
                <img class="gif" src="images/icon/regis_{{ $lang }}.gif">
                <img class="png" src="images/icon/regis_{{ $lang }}.png">
            </div>
        </div>


    </div>

    <div class="px-1">

        <section class="sectionpage login">
            <div class="inbgbeforelogin">
                <div class="logopopup">
                    {!! core()->showImg($config->logo,'img','','','') !!}
                </div>
                <h1>{{ __('app.register.register') }}</h1>

                @if($config->verify_sms == 'Y')
                    @include('wallet::customer.sessions.step')
                @else
                    @include('wallet::customer.sessions.normal')
                @endif

                <div class="mt-4">
                    <div class="modalspanbox">{{ __('app.register.have_username') }} <a class="loginbtn"
                                                                     href="{{ route('customer.home.index') }}">{{ __('app.register.login') }}</a>
                    </div>
                </div>
            </div>

        </section>

    </div>
@endsection

