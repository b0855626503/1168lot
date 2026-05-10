@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <section class="content text-xs">
        <div class="card">
            <div class="card-body">
                @include('admin::module.lotto.result_corrections.create')
                @include('admin::module.lotto.result_corrections.table')
                @includeIf('admin::module.lotto.result_corrections.addedit')
            </div>
        </div>
    </section>
@endsection
