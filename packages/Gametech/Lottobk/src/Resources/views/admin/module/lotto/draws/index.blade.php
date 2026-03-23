@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <section class="content text-xs">
        <div class="card">
            <div class="card-body">
                @include('admin::module.lotto.draws.create')
                @include('admin::module.lotto.draws.table')
                @includeIf('admin::module.lotto.draws.addedit')
            </div>
        </div>
    </section>
@endsection

