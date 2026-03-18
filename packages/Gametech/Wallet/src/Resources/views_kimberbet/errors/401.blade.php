@extends(auth()->guard('admin')->check() ? 'admin::layouts.app' : (auth()->guard('customer')->check() ? 'wallet::layouts.app' : 'wallet::layouts.minimal'))

@section('title', __('Unauthorized'))

@section('content', __('Unauthorized'))
