<div class="row mb-2 align-items-end">
    <div class="col-md-3 mb-2 mb-md-0">
        <label class="mb-1">กลุ่มหวย</label>
        <select id="filter_group_id" class="form-control form-control-sm">
            <option value="">ทั้งหมด</option>
            @foreach($groups as $g)
                <option value="{{ $g->id }}">{{ $g->name }} ({{ $g->code }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-2 mb-md-0">
        <label class="mb-1">ค้นหาชื่อรายการหวย</label>
        <input type="text"
               id="filter_market_name"
               class="form-control form-control-sm"
               placeholder="พิมพ์ชื่อรายการหวย">
    </div>
    <div class="col-md-5 text-md-right text-left">
        <button type="button" class="btn bg-gradient-primary btn-xs" onclick="addModal()">
            <i class="fa fa-plus"></i> เพิ่มรายการหวย
        </button>
    </div>
</div>
