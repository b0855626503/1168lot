@extends('admin::layouts.master')

@section('title')
    {{ $title }}
@endsection

@section('content')
    @include('admin::module.lotto._shared.page')
@endsection
