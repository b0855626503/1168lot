@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')
    

    <promotion-page
            :promotions='@json($promotions)'
            :pro-contents='@json($pro_contents)'
            current-tab="promotions">
    </promotion-page>

@endsection




