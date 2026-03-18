@extends(auth()->guard('admin')->check() ? 'admin::layouts.app' : (auth()->guard('customer')->check() ? 'wallet::layouts.app' : 'wallet::layouts.minimal'))


@section('title', __('Forbidden'))
@section('code', '403')
@section('content', __($exception->getMessage() ?: 'Forbidden'))
