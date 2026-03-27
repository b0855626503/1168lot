@section('css')
    @include('admin::layouts.datatables_css')
@endsection

{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm'], true) !!}

@push('scripts')
    @include('admin::layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    <script>
        $(function () {
            const tableKey = 'dataTableBuilder';
            const $groupSelect = $('#source_group_filter');
            const $marketSelect = $('#source_market_filter');

            const redrawTable = function () {
                if (!window.LaravelDataTables || !window.LaravelDataTables[tableKey]) {
                    return;
                }

                window.LaravelDataTables[tableKey].draw(false);
            };

            const rebuildMarketFilterOptions = function (groupId) {
                const selectedMarket = String($marketSelect.val() || '');
                const selectedGroup = String(groupId || '');

                $marketSelect.find('option').each(function () {
                    const $option = $(this);
                    const value = String($option.val() || '');
                    if (value === '') {
                        $option.prop('hidden', false);
                        return;
                    }

                    const optionGroupId = String($option.data('group-id') || '');
                    const visible = !selectedGroup || optionGroupId === selectedGroup;
                    $option.prop('hidden', !visible);
                });

                if (selectedMarket) {
                    const selectedOption = $marketSelect.find('option[value="' + selectedMarket + '"]');
                    if (!selectedOption.length || selectedOption.prop('hidden')) {
                        $marketSelect.val('');
                    }
                }
            };

            $(document).off('preXhr.dt.resultSourcesFilter', '#dataTableBuilder').on('preXhr.dt.resultSourcesFilter', '#dataTableBuilder', function (_event, _settings, data) {
                data.group_id = $groupSelect.val() || '';
                data.market_id = $marketSelect.val() || '';
            });

            $groupSelect.off('change.resultSourcesFilter').on('change.resultSourcesFilter', function () {
                rebuildMarketFilterOptions($(this).val());
                redrawTable();
            });

            $marketSelect.off('change.resultSourcesFilter').on('change.resultSourcesFilter', function () {
                redrawTable();
            });

            rebuildMarketFilterOptions($groupSelect.val() || '');
        });
    </script>
@endpush
