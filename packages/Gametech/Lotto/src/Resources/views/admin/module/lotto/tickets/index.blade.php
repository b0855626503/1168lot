@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <section class="content text-xs">
        <div class="card">
            <div class="card-body">
                @include('admin::module.lotto.tickets.create')
                @include('admin::module.lotto.tickets.table')
                @includeIf('admin::module.lotto.tickets.addedit')
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @include('admin::layouts.loadcnt_js')
@endpush
