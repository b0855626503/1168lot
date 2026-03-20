@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <section class="content text-xs">
        <div class="card">
            <div class="card-body">
                @include('admin::module.lotto.member_rate_plans.create')
                @include('admin::module.lotto.member_rate_plans.table')
                @includeIf('admin::module.lotto.member_rate_plans.addedit')
            </div>
        </div>
    </section>
@endsection

