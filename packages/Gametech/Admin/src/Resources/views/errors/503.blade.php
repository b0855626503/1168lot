@extends(auth()->guard('admin')->check() ? 'admin::layouts.app' : (auth()->guard('customer')->check() ? 'wallet::layouts.app' : 'wallet::layouts.minimal'))


@section('title', __('Service Unavailable'))
@section('code', '503')
@section('content', __($exception->getMessage() ?: 'Service Unavailable'))
