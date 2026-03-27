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
                    const selectedGroupId = String($(this).val() || '');
                    const selectedMarketOption = $marketSelect.find('option:selected');
                    const selectedMarketGroupId = String(selectedMarketOption.data('group-id') || '');

                    if (selectedGroupId && selectedMarketGroupId && selectedGroupId !== selectedMarketGroupId) {
                        $marketSelect.val('');
                    }

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

            // DataTable first request may fire before custom filters are attached.
            // Force one redraw so default draw_date filter is applied on initial page load.
            if ($drawDateInput.val()) {
                redrawTable();
            }
        });
    </script>
@endpush
