@extends(auth()->guard('admin')->check() ? 'admin::layouts.app' : (auth()->guard('customer')->check() ? 'wallet::layouts.app' : 'wallet::layouts.minimal'))


@section('title', __('Too Many Requests'))
@section('code', '429')
@section('content', __('Too Many Requests'))
