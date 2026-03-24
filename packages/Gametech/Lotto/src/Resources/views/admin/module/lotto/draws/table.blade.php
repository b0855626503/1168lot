@section('css')
	@include('admin::layouts.datatables_css')
@endsection
{!! $dataTable->table(['id' => 'lottoDrawsTable', 'width' => '100%', 'class' => 'table table-striped table-sm']) !!}
@push('scripts')
	@include('admin::layouts.datatables_js')
	{!! $dataTable->scripts() !!}
    <script>
        $(function () {
            const tableSelector = '#lottoDrawsTable';
            const tableKey = 'lottoDrawsTable';
            const $groupSelect = $('#filter_group_id');
            const $marketSelect = $('#filter_market_id');
            const $drawDateInput = $('#filter_draw_date');

            const initMarketSelect = function () {
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
                    placeholder: 'ค้นหารายการหวย',
                    allowClear: true,
                    templateResult: renderMarketOption,
                    templateSelection: renderMarketOption,
                    escapeMarkup: function (markup) {
                        return markup;
                    },
                });
            };

            const redrawTable = function () {
                if (!window.LaravelDataTables || !window.LaravelDataTables[tableKey]) {
                    return;
                }

                window.LaravelDataTables[tableKey].draw(false);
            };

            $(document).off('preXhr.dt.lottoDrawsFilter', tableSelector).on('preXhr.dt.lottoDrawsFilter', tableSelector, function (_event, _settings, data) {
                data.group_id = $groupSelect.val() || '';
                data.market_id = $marketSelect.val() || '';
                data.draw_date = $drawDateInput.val() || '';
            });

            $groupSelect.off('change.lottoDrawsFilter').on('change.lottoDrawsFilter', function () {
                const selectedGroupId = String($(this).val() || '');
                const selectedMarketOption = $marketSelect.find('option:selected');
                const selectedMarketGroupId = String(selectedMarketOption.data('group-id') || '');

                if (selectedGroupId && selectedMarketGroupId && selectedGroupId !== selectedMarketGroupId) {
                    $marketSelect.val('').trigger('change.select2');
                }

                redrawTable();
            });

            $marketSelect.off('change.lottoDrawsFilter').on('change.lottoDrawsFilter', function () {
                redrawTable();
            });

            $drawDateInput.off('change.lottoDrawsFilter').on('change.lottoDrawsFilter', function () {
                redrawTable();
            });

            initMarketSelect();
        });
    </script>
@endpush
