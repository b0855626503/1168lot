<div class="row mb-2 align-items-end">
    <div class="col-md-3 mb-2 mb-md-0">
        <label class="mb-1">กลุ่มหวย</label>
        <select id="filter_group_id" class="form-control form-control-sm">
            <option value="">ทั้งหมด</option>
            @foreach(($groupOptions ?? []) as $option)
                <option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-2 mb-md-0">
        <label class="mb-1">รายการหวย</label>
        <select id="filter_market_id" class="form-control form-control-sm">
            <option value="">ทั้งหมด</option>
            @foreach(($marketOptions ?? []) as $group)
                <optgroup label="{{ $group['label'] ?? '-' }}">
                    @foreach(($group['options'] ?? []) as $option)
                        <option value="{{ $option['value'] }}" data-group-id="{{ $option['group_id'] ?? '' }}">
                            {{ $option['text'] }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
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
