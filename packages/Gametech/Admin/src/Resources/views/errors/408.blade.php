@extends(auth()->guard('admin')->check() ? 'admin::layouts.app' : (auth()->guard('customer')->check() ? 'wallet::layouts.app' : 'wallet::layouts.minimal'))


@section('title', __('Request Timeout'))
@section('code', '408')
@section('content', __('Request Timeout'))
