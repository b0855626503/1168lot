<div class="row mb-3 align-items-end">
    <div class="col-md-4 mb-2 mb-md-0">
        <label class="mb-1">รายการหวย</label>
        <select id="filter_market_id" class="form-control form-control-sm">
            <option value="">ทั้งหมด</option>
            @foreach(($marketOptions ?? []) as $group)
                <optgroup label="{{ $group['label'] ?? '-' }}">
                    @foreach(($group['options'] ?? []) as $option)
                        <option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-2 mb-md-0">
        <label class="mb-1">งวดหวย</label>
        <select id="filter_draw_id" class="form-control form-control-sm" disabled>
            <option value="">เลือกงวดหวย</option>
        </select>
    </div>
    <div class="col-md-4 text-right text-muted">
        <small>เลือกแล้วกรองทันที ไม่ต้องกดค้นหา</small>
    </div>
</div>
