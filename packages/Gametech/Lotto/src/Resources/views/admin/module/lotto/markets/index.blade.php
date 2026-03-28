@extends('admin::layouts.master')
@section('title')
    {{ $menu->currentName }}
@endsection
@section('content')
    <section class="content text-xs">
        <div class="card card-primary">

            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="mb-1">กลุ่มหวย</label>
                        <select id="filter_group_id" class="form-control form-control-sm">
                            <option value="">ทั้งหมด</option>
                            @foreach($groups as $g)
                                <option value="{{ $g->id }}">{{ $g->name }} ({{ $g->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @include('admin::module.lotto.markets.create')
                @include('admin::module.lotto.markets.table')
                @includeIf('admin::module.lotto.markets.addedit')
            </div>
        </div>
    </section>
@endsection
