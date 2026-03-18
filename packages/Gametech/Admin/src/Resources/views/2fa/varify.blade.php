@extends('admin::layouts.app')

@section('content')

    <div class="login-box">

        <div class="login-logo text-center">
            {!! core()->showImg($config->logo,'img','100px','100px','img-fluid') !!}
        </div>

        <div class="card">
            <div class="card-body login-card-body">
{{--                {!! core()->showImg($config->logo,'img','','','img-fluid') !!}--}}
                <p class="login-box-msg">One Time Password</p>

                <form method="POST" action="{{ route('2fa') }}" @submit.prevent="onSubmit">
                    @csrf
                    <div class="input-group mb-3">

                        <input v-validate="'required'"
                               type="number"
                               class="form-control" :class="[errors.has('one_time_password') ? 'is-invalid' : '']"
                               id="one_time_password" name="one_time_password"
                               data-vv-as="&quot;one_time_password&quot;"
                               value="{{ old('one_time_password') }}" placeholder="" autocomplete="off">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-key"></span>
                            </div>
                        </div>
                    </div>


                    <div class="row">

                        <!-- /.col -->
                        <div class="col-12">
                            <button class="btn btn-primary btn-block">Validate</button>
                        </div>

                        <!-- /.col -->
                    </div>

                </form>

                <div class="row text-center mt-2">
                    <div class="col-6">
{{--                        <a href="{{ route('admin.2fa.activate') }}">Re-Activate</a>--}}
                    </div>
                    <div class="col-6">
                        <a href="{{ route('admin.session.destroy') }}" class="text-center">Logout</a>
                    </div>
                </div>

            </div>

            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>


@endsection
