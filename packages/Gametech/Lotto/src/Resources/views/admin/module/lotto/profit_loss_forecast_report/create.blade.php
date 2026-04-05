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
    <div class="col-md-3">
        <label class="mb-1">ประเภท</label>
        <select id="filter_bet_type" class="form-control form-control-sm">
            <option value="">ทั้งหมด</option>
            @foreach(($betTypeOptions ?? []) as $option)
                <option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 text-right">
        <button type="button" class="btn bg-gradient-primary btn-xs" onclick="applyProfitLossForecastFilters()">
            <i class="fa fa-search"></i> ค้นหา
        </button>
        <button type="button" class="btn bg-gradient-secondary btn-xs" onclick="resetProfitLossForecastFilters()">
            <i class="fa fa-refresh"></i> ล้างค่า
        </button>
    </div>
    <div class="col-12 mt-2 text-muted">
        คาดการณ์ความเสี่ยงจ่ายจาก exposure จริงเทียบกับยอดแทงสะสมของแต่ละงวด/ประเภท
    </div>
</div>
