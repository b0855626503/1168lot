{{-- extend layout --}}
@extends('wallet::layouts.cont')

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


    <div class="px-1 mt-5">

        <section class="sectionpage login">
            <div class="inbgbeforelogin">
                <div class="logopopup">
                    {!! core()->showImg($config->logo,'img','','','') !!}
                </div>
                <h1>สมัครสมาชิก</h1>

                @if($config->verify_sms == 'Y')
                    @include('wallet::customer.sessions.step')
                @else
                    @include('wallet::customer.sessions.normal')
                @endif

            </div>

        </section>

    </div>
@endsection

