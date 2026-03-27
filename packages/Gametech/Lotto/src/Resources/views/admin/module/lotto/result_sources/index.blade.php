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
