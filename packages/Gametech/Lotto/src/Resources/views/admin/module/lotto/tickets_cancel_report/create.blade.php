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
            @foreach(($marketOptions ?? []) as $group)
                <optgroup label="{{ $group['label'] ?? '-' }}">
                    @foreach(($group['options'] ?? []) as $option)
                        <option value="{{ $option['value'] }}"
                                data-logo="{{ $option['logo'] ?? '' }}">
                            {{ $option['text'] }}
                        </option>
                    @endforeach
                </optgroup>
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
@push('scripts')
    <script>
        $(function () {
            const $marketSelect = $('#filter_market_id');
            if (!$marketSelect.length || typeof $marketSelect.select2 !== 'function') {
                return;
            }

            if ($marketSelect.hasClass('select2-hidden-accessible')) {
                $marketSelect.select2('destroy');
            }

            const renderMarketOption = function (state) {
                if (!state.id) {
                    return state.text;
                }

                const optionEl = state.element;
                const logo = optionEl ? String(optionEl.getAttribute('data-logo') || '') : '';
                const safeText = $('<span/>').text(state.text || '').html();

                if (!logo) {
                    return $('<span>' + safeText + '</span>');
                }

                return $(
                    '<span style="display:flex;align-items:center;gap:8px;">'
                    + '<img src="' + logo + '" alt="" style="width:20px;height:20px;object-fit:cover;border-radius:50%;border:1px solid #e5e7eb;">'
                    + '<span>' + safeText + '</span>'
                    + '</span>'
                );
            };

            $marketSelect.select2({
                width: '100%',
                placeholder: 'เลือกตลาด',
                allowClear: true,
                templateResult: renderMarketOption,
                templateSelection: renderMarketOption,
                escapeMarkup: function (markup) {
                    return markup;
                },
            });
        });
    </script>
@endpush
