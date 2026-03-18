@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')
    @if($config->seamless == 'Y')
        @include('wallet::customer.home.seamless')
    @else
        @if($config->multigame_open == 'Y')
            @include('wallet::customer.home.multi')
        @else
            @include('wallet::customer.home.single')
        @endif
    @endif

@endsection
