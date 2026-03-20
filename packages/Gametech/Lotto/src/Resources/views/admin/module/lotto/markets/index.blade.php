@extends('admin::layouts.master')
@section('title')
    {{ $menu->currentName }}
@endsection
@section('content')
    <section class="content text-xs">
        <div class="card">
            <div class="card-body">
                @include('admin::module.lotto.markets.create')
                @include('admin::module.lotto.markets.table')
                @includeIf('admin::module.lotto.markets.addedit')
            </div>
        </div>
    </section>
@endsection
