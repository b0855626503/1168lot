<div class="row mb-2 align-items-end">
    <div class="col-md-3 mb-2 mb-md-0">
        <label class="mb-1">กลุ่มหวย</label>
        <select id="source_group_filter" class="form-control form-control-sm">
            <option value="">ทั้งหมด</option>
            @foreach(($groupOptions ?? []) as $group)
                <option value="{{ $group['value'] }}">{{ $group['text'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-2 mb-md-0">
        <label class="mb-1">รายการหวย</label>
        <select id="source_market_filter" class="form-control form-control-sm">
            <option value="">ทั้งหมด</option>
            @foreach(($marketOptionsGrouped ?? []) as $group)
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
    <div class="col-md-5 text-md-right text-left">
        <button type="button" class="btn bg-gradient-primary btn-xs" onclick="addSourceModal()">
            <i class="fa fa-plus"></i> เพิ่ม Source
        </button>
    </div>
</div>
