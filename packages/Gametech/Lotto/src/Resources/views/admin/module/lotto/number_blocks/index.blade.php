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
                        <label class="mb-1">วันที่</label>
                        <input type="date" id="filter_draw_date" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="mb-1">รายการหวย</label>
                        <select id="filter_market_id" class="form-control form-control-sm">
                            <option value="">ทั้งหมด</option>
                            @foreach(($marketOptionsGrouped ?? []) as $group)
                                <optgroup label="{{ $group['label'] ?? '-' }}">
                                    <option value="group:{{ $group['group_id'] ?? 0 }}">
                                        ทั้งกลุ่ม: {{ $group['label'] ?? '-' }}
                                    </option>
                                    @foreach(($group['options'] ?? []) as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="mb-1">ประเภทเดิมพัน</label>
                        <select id="filter_bet_type" class="form-control form-control-sm">
                            <option value="">ทั้งหมด</option>
                            @foreach(($betTypeOptions ?? []) as $option)
                                <option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="mb-1">ค้นหาเลข</label>
                        <input type="text" id="filter_number_search" class="form-control form-control-sm" placeholder="เช่น 12">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="mb-2">
                    <button type="button" id="bulk-delete-number-blocks-btn" class="btn btn-danger btn-sm d-none">
                        <i class="fas fa-trash"></i> ลบที่เลือก
                    </button>
                </div>
                @include('admin::module.lotto.number_blocks.create')
                @include('admin::module.lotto.number_blocks.table')
                @includeIf('admin::module.lotto.number_blocks.addedit')
            </div>
        </div>
    </section>
@endsection
