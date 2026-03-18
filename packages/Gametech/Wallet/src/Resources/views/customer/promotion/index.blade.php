@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')
    @if($config->seamless == 'Y')
        @include('wallet::customer.promotion.seamless')
    @else
        @include('wallet::customer.promotion.normal')
    @endif
@endsection







