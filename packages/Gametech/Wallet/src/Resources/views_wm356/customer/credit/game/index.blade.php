@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('content')
@if($config->seamless == 'Y')
    @include('wallet::customer.credit.game.seamless')
@else
    @if($config->multigame_open == 'Y')
        @include('wallet::customer.credit.game.multi')
    @else
        @include('wallet::customer.credit.game.single')
    @endif
@endif
@endsection
