@extends(auth()->guard('admin')->check() ? 'admin::layouts.app' : (auth()->guard('customer')->check() ? 'wallet::layouts.app' : 'wallet::layouts.minimal'))


@section('title', __('Page Expired'))
@section('code', '419')
@section('content', __('Page Expired'))
