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
                                                data-logo="{{ $option['logo'] ?? '' }}"
                                                data-result-mode="{{ $option['result_mode'] ?? 'normal' }}">
                                            {{ $option['text'] }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="mb-1">วันงวด</label>
                        <input type="date" id="filter_draw_date" class="form-control form-control-sm" value="{{ now(config('app.timezone', 'Asia/Bangkok'))->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="mb-1">สถานะ</label>
                        <select id="filter_status" class="form-control form-control-sm">
                            <option value="">ทั้งหมด</option>
                            <option value="draft">ร่าง</option>
                            <option value="open">เปิดรับ</option>
                            <option value="closed">ปิดรับ</option>
                            <option value="resulted">ประกาศผล</option>
                        </select>
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

        @include('admin::module.lotto.draws.yeekee_audit_modal')
    </section>
@endsection

@push('scripts')
    @include('admin::layouts.loadcnt_js')
@endpush
