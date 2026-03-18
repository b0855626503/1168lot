@extends(auth()->guard('admin')->check() ? 'admin::layouts.app' : (auth()->guard('customer')->check() ? 'wallet::layouts.app' : 'wallet::layouts.minimal'))


@section('title', __('Server Error'))
@section('code', '500')
@section('content', __('Server Error'))
