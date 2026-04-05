@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <section class="content text-xs">
        <div class="card">
            <div class="card-body">
                @include('admin::module.lotto.groups.create')
                @include('admin::module.lotto.groups.table')
                @includeIf('admin::module.lotto.groups.addedit')
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @include('admin::layouts.loadcnt_js')
@endpush
