{{-- extend layout --}}
@extends('wallet::layouts.app')

{{-- page title --}}
@section('title','')

@push('styles')
    <style>
        .bg-login {
            color: #fff;
            height: 100vh !important;
            font-size: 14px;
            overflow-y: hidden !important;
            overflow-x: hidden !important;
            background-size: contain;
            background-repeat: no-repeat;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12 offset-md-3 offset-lg-3 col-md-6 col-lg-6  col-sm-12">
                <div class="my-login-page">
                    <div class="card-wrapper">
                        <div class="brand"></div>
                        <div class="card fat">
                            <div class="card-header"></div>
                            <div class="card-body">
                                <h4 class="card-title"></h4>
                                <form method="POST" action="{{ route('customer.session.create') }}"
                                      @submit.prevent="onSubmit">
                                    @csrf
                                    <div class="input-group form-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input class="form-control text-lowercase" v-validate="'required'"
                                               :class="[errors.has('user_name') ? 'is-invalid' : '']"
                                               id="user_name" name="user_name" maxlength="10"
                                               data-vv-as="&quot;Username&quot;"
                                               value="{{ old('user_name') }}" placeholder="User Name">
                                    </div>
                                    <div class="input-group form-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        </div>
                                        <input type="password" v-validate="'required|min:6'"
                                               class="form-control"
                                               :class="[errors.has('password') ? 'is-invalid' : '']"
                                               id="password" name="password"
                                               data-vv-as="&quot;Password&quot;"
                                               placeholder="รหัสผ่าน">

                                    </div>

                                    <div class="row">

                                        <!-- /.col -->
                                        <div class="col-12">
                                            <button class="btn btn-primary btn-block" style="border: none"><i
                                                    class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                                            </button>
                                        </div>
                                        <!-- /.col -->
                                    </div>
                                </form>

                            </div>
                            <div class="card-footer d-flex">
                                <div class="d-flex" style="flex-grow:1; ">
                                    <a class="text-light btn-footer" href="{{ route('customer.session.store') }}">สมัครสมาชิก</a>
                                </div>
                                <div class="d-flex">
                                    <a class="text-light btn-footer" target="_blank" href="{{ $config->linelink }}">ลืมรหัสผ่าน?</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($config->notice)
                        <div class="card card-trans">
                            <div class="card-body">
                                <p class="card-title text-warning text-center w-100">{{ $config->notice }} </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
