@extends('admin::layouts.app')

@section('content')
    <div class="login-box">

        <div class="login-logo text-center">
            {!! core()->showImg($config->logo,'img','100px','100px','img-fluid') !!}
        </div>

        <div class="card">
            <div class="card-body login-card-body text-center">
                <p class="login-box-msg">สแกน QR Code ด้วย App Authenticator</p>
                @if(!$data['user']->google2fa_enable)
                    <img alt="Image of QR barcode" src="{{ $image }}"/>
                    <br/>
                    ถ้าไม่สามารถ สแกนได้ให้พิมรหัส ดังนี้ : <code>{{ $secret }}</code>
                    <br>
                    <small> เมื่อดำเนินการเสร็จแล้วจะได้ รหัส 8 ตัวใน App นำมากรอกเพื่อ เปิดใช้งาน User</small>
                    <form method="POST" action="{{ route('admin.2fa.enable') }}" @submit.prevent="onSubmit">
                        @csrf
                        <div class="input-group mb-3">

                            <input v-validate="'required'"
                                   type="password"
                                   class="form-control" :class="[errors.has('secret') ? 'is-invalid' : '']"
                                   id="secret" name="secret"
                                   data-vv-as="&quot;secret&quot;"
                                   value="{{ old('secret') }}" placeholder="" autocomplete="off">
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-key"></span>
                                </div>
                            </div>
                        </div>


                        <div class="row">

                            <!-- /.col -->
                            <div class="col-12">
                                <button class="btn btn-primary btn-block">Activate</button>
                            </div>


                            <!-- /.col -->
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <a href="{{ route('admin.session.destroy') }}"
                                   class="btn btn-default btn-block">Logout</a>
                            </div>
                        </div>

                    </form>
                @endif
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
@endsection
