@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <section class="content text-xs">
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 mb-2 mb-md-0">
                        <label class="mb-1">กลุ่มหวย</label>
                        <select id="filter_group_id" class="form-control form-control-sm">
                            <option value="">ทั้งหมด</option>
                            @foreach(($groupOptions ?? []) as $option)
                                <option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="mb-1">รายการหวย</label>
                        <select id="filter_market_id" class="form-control form-control-sm">
                            <option value="">ทั้งหมด</option>
                            @foreach(($marketOptions ?? []) as $group)
                                <optgroup label="{{ $group['label'] ?? '-' }}">
                                    @foreach(($group['options'] ?? []) as $option)
                                        <option value="{{ $option['value'] }}"
                                                data-group-id="{{ $option['group_id'] ?? '' }}"
                                                data-logo="{{ $option['logo'] ?? '' }}">
                                            {{ $option['text'] }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 mb-md-0">
                        <label class="mb-1">วันงวด</label>
                        <input type="date" id="filter_draw_date" class="form-control form-control-sm" value="{{ now(config('app.timezone', 'Asia/Bangkok'))->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-5 text-md-right text-left">
                        <button type="button" class="btn bg-gradient-info btn-xs" onclick="generateAutoDraws(true)">
                            <i class="fa fa-search"></i> Auto งวด (Dry-run)
                        </button>
                        <button type="button" class="btn bg-gradient-success btn-xs" onclick="generateAutoDraws(false)">
                            <i class="fa fa-magic"></i> Auto งวด (Generate)
                        </button>
                        <button type="button" class="btn bg-gradient-primary btn-xs" onclick="addModal()">
                            <i class="fa fa-plus"></i> เพิ่มงวดหวย
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @include('admin::module.lotto.draws.create')
                @include('admin::module.lotto.draws.table')
                @includeIf('admin::module.lotto.draws.addedit')
            </div>
        </div>
    </section>
@endsection

