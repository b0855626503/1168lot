@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <section class="content text-xs">
        <div class="card">
            <div class="card-body">
                @include('admin::module.lotto.number_blocks.create')
                @include('admin::module.lotto.number_blocks.table')
                @includeIf('admin::module.lotto.number_blocks.addedit')
            </div>
        </div>
    </section>
@endsection
