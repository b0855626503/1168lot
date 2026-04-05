@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <section class="content text-xs">
        <div class="card">
            <div class="card-body">
                @include('admin::module.lotto.result_sources.create')
                @include('admin::module.lotto.result_sources.table')
                @includeIf('admin::module.lotto.result_sources.addedit')
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @include('admin::layouts.loadcnt_js')
@endpush

@if(request()->boolean('embed'))
    @push('styles')
        <style>
            .main-header,
            .main-sidebar,
            .main-footer,
            .content-header,
            .breadcrumb {
                display: none !important;
            }

            .content-wrapper,
            .right-side,
            .main-footer {
                margin-left: 0 !important;
            }

            .content-wrapper {
                min-height: auto !important;
                background: #fff !important;
            }

            .content {
                padding: 0 !important;
                margin: 0 !important;
            }

            .card {
                box-shadow: none !important;
                margin: 0 !important;
            }
        </style>
    @endpush
@endif
