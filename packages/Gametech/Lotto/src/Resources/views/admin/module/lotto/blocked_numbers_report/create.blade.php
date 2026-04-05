<div class="row mb-2 align-items-end">
    <div class="col-md-3">
        <label class="mb-1">วันงวด</label>
        <select id="filter_draw_date" class="form-control form-control-sm">
            <option value="">ทั้งหมด</option>
            @foreach(($drawDateOptions ?? []) as $option)
                <option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="mb-1">ตลาด</label>
        <select id="filter_market_id" class="form-control form-control-sm">
            <option value="">ทั้งหมด</option>
            @foreach(($marketOptions ?? []) as $option)
                <option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="mb-1">ประเภท</label>
        <select id="filter_bet_type" class="form-control form-control-sm">
            <option value="">ทั้งหมด</option>
            @foreach(($betTypeOptions ?? []) as $option)
                <option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="mb-1">โหมดบล็อก</label>
        <select id="filter_mode" class="form-control form-control-sm">
            <option value="">ทั้งหมด</option>
            @foreach(($modeOptions ?? []) as $option)
                <option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 text-right">
        <button type="button" class="btn bg-gradient-primary btn-xs" onclick="applyBlockedNumbersReportFilters()">
            <i class="fa fa-search"></i> ค้นหา
        </button>
        <button type="button" class="btn bg-gradient-secondary btn-xs" onclick="resetBlockedNumbersReportFilters()">
            <i class="fa fa-refresh"></i> ล้างค่า
        </button>
    </div>
    <div class="col-12 mt-2 text-muted">
        รายงานเลขอั้นและเลขจำกัดอนาคตจากข้อมูลจริง พร้อมวันงวดและเวลาการแก้ไขล่าสุด
    </div>
</div>
