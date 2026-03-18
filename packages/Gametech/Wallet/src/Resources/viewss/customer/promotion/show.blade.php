@extends('wallet::layouts.app')

{{-- page title --}}
@section('title','')

@section('back')

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





