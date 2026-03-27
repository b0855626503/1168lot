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
                // temporary: keep list filter as native select.
                // select2 for market will run only in add/edit modal.
            };

            const rebuildMarketFilterOptions = function (groupId) {
                const selectedGroup = String(groupId || '');
                const selectedMarket = String($marketSelect.val() || '');

                $marketSelect.find('option').each(function () {
                    const $option = $(this);
                    const value = String($option.val() || '');

                    if (value === '') {
                        $option.prop('hidden', false).prop('disabled', false);
                        return;
                    }

                    const optionGroupId = String($option.data('group-id') || '');
                    const visible = !selectedGroup || optionGroupId === selectedGroup;
                    $option.prop('hidden', !visible).prop('disabled', !visible);
                });

                $marketSelect.find('optgroup').each(function () {
                    const $optgroup = $(this);
                    const hasVisibleOptions = $optgroup.find('option').filter(function () {
                        const value = String($(this).val() || '');
                        if (value === '') {
                            return false;
                        }

                        return !$(this).prop('hidden');
                    }).length > 0;

                    $optgroup.prop('hidden', !hasVisibleOptions);
                });

                if (selectedMarket) {
                    const $selectedOption = $marketSelect.find('option[value="' + selectedMarket + '"]');
                    if (! $selectedOption.length || $selectedOption.prop('hidden')) {
                        $marketSelect.val('');
                    }
                }
            };

            const redrawTable = function () {
                if (!window.LaravelDataTables || !window.LaravelDataTables[tableKey]) {
                    return;
                }

                window.LaravelDataTables[tableKey].draw(false);
            };

            $(document)
                .off('preXhr.dt.lottoDrawsFilter', tableSelector)
                .on('preXhr.dt.lottoDrawsFilter', tableSelector, function (_event, _settings, data) {
                    data.group_id = $groupSelect.val() || '';
                    data.market_id = $marketSelect.val() || '';
                    data.draw_date = $drawDateInput.val() || '';
                });

            $groupSelect
                .off('change.lottoDrawsFilter')
                .on('change.lottoDrawsFilter', function () {
                    rebuildMarketFilterOptions($(this).val() || '');
                    redrawTable();
                });

            $marketSelect
                .off('change.lottoDrawsFilter')
                .on('change.lottoDrawsFilter', function () {
                    redrawTable();
                });

            $drawDateInput
                .off('change.lottoDrawsFilter')
                .on('change.lottoDrawsFilter', function () {
                    redrawTable();
                });

            initMarketSelect();
            rebuildMarketFilterOptions($groupSelect.val() || '');

            // DataTable first request may fire before custom filters are attached.
            // Force one redraw so default draw_date filter is applied on initial page load.
            if ($drawDateInput.val()) {
                redrawTable();
            }
        });
    </script>
@endpush
