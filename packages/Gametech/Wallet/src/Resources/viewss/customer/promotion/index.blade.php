@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('back')
    <a class="nav-link p-2 text-light mx-auto hand-point" href="{{ route('customer.home.index') }}">
        <i class="fas fa-chevron-left"></i> กลับ</a>
@endsection


@section('content')
    <div class="container">
        <h1 class="text-center text-light">โปรโมชั่น</h1>
        <p class="text-center text-color-fixed">Promotion</p>
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">

                @if($config->seamless == 'Y')
                    @include('wallet::customer.promotion.seamless')
                @else
                    @include('wallet::customer.promotion.normal')
                @endif

            </div>
        </div>
    </div>

@endsection





