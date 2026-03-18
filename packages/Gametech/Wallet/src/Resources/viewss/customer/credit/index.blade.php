@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('back')
    <a class="nav-link p-2 text-light mx-auto hand-point" href="{{ route('customer.home.index') }}">
        <i class="fas fa-chevron-left"></i> กลับ</a>
@endsection

@section('content')
    <div class="container">
        <h3 class="text-center text-light">ฟรีเครดิต</h3>
        <p class="text-center text-color-fixed">Free Credit</p>
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">
                <div class="card text-light card-trans">
                    <div class="card-body py-3 px-2">
                        @if($config->seamless == 'Y')
                            <seamlessfree></seamlessfree>
                        @else
                            <cashback></cashback>
                        @endif


                    </div>
                </div>

                @if($config->seamless == 'Y')
                    @include('wallet::customer.credit.seamless')
                @else
                    @include('wallet::customer.credit.single')
                @endif

            </div>
        </div>
    </div>

@endsection

