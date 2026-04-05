<div class="row mb-2 align-items-end">
    <div class="col-md-3">
        <label class="mb-1">วันที่เริ่ม</label>
        <input id="filter_date_start" type="date" class="form-control form-control-sm">
    </div>
    <div class="col-md-3">
        <label class="mb-1">วันที่สิ้นสุด</label>
        <input id="filter_date_stop" type="date" class="form-control form-control-sm">
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
        <label class="mb-1">สถานะโพย</label>
        <select id="filter_status" class="form-control form-control-sm">
            <option value="">ทั้งหมด</option>
            @foreach(($statusOptions ?? []) as $option)
                <option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1 text-right">
        <button type="button" class="btn bg-gradient-primary btn-xs" onclick="applyTicketsCancelFilters()">
            <i class="fa fa-search"></i>
        </button>
        <button type="button" class="btn bg-gradient-secondary btn-xs" onclick="resetTicketsCancelFilters()">
            <i class="fa fa-refresh"></i>
        </button>
    </div>
    <div class="col-12 mt-2 text-muted">
        รายงานโพยทุกสถานะ พร้อมผู้ยกเลิกสำหรับรายการที่ถูกยกเลิก
    </div>
</div>
