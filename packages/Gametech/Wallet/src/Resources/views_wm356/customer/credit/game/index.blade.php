@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('content')
@if($webconfig->seamless == 'Y')
    @include('wallet::customer.credit.game.seamless')
@else
    @if($webconfig->multigame_open == 'Y')
        @include('wallet::customer.credit.game.multi')
    @else
        @include('wallet::customer.credit.game.single')
    @endif
@endif
@endsection
