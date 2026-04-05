{{-- extend layout --}}
@extends('admin::layouts.app')

{{-- page title --}}
@section('title','Login to Admin Zone')

@section('content')

    @php($adminLogo = data_get($webconfig, 'logo'))

    <div class="login-box">

        <div class="login-logo text-center">
            @if($adminLogo)
                {!! core()->showImg($adminLogo,'img','100px','100px','img-fluid') !!}
            @else
                <div class="h3 mb-0">{{ ucfirst(config('app.name')) }}</div>
            @endif
        </div>

        <div class="card">
            <div class="card-body login-card-body">

                <p class="login-box-msg">ระบบการจัดการ</p>

                <form method="POST" action="{{ route('admin.session.create') }}" @submit.prevent="onSubmit">
                    @csrf
                    <div class="input-group mb-3">
                        <input  v-validate="'required'"
                                class="form-control" :class="[errors.has('user_name') ? 'is-invalid' : '']"
                                id="user_name" name="user_name"
                                data-vv-as="&quot;Username&quot;"
                                value="{{ old('user_name') }}" placeholder="Username">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" v-validate="'required|min:6'"
                               class="form-control" :class="[errors.has('password') ? 'is-invalid' : '']"
                               id="password" name="password"
                               data-vv-as="&quot;Password&quot;"
                               placeholder="Password">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">

                        <!-- /.col -->
                        <div class="col-12">
                            <button class="btn btn-primary btn-block">เข้าสู่ระบบ</button>
                        </div>
                        <!-- /.col -->
                    </div>

                </form>

            </div>
            <!-- /.card-body -->
            <p class="text-center">{!! $current !!}</p>

        </div>
        <!-- /.card -->
    </div>


@endsection
