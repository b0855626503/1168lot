{{-- extend layout --}}
@extends('admin::layouts.app')

{{-- page title --}}
@section('title','Login to Admin Zone')

@section('content')

    <div class="login-box">

        <div class="login-logo">
            <a href="{{ url('/') }}">{{ ucfirst(config('app.name')) }}</a>
        </div>

        <div class="card">
            <div class="card-body login-card-body">
                {!! core()->showImg($config->logo,'img','','','img-top') !!}
                <p class="login-box-msg">Sign in to start your session</p>

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
                            <button class="btn btn-primary btn-block">Sign In</button>
                        </div>
                        <!-- /.col -->
                    </div>

                </form>

            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>


@endsection
