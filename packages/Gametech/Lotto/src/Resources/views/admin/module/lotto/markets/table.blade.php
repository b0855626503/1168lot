@section('css')
    @include('admin::layouts.datatables_css')
@endsection
{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}
@push('scripts')
    @include('admin::layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    <script>
        $(function () {
            const tableSelector = '#dataTableBuilder';
            const tableKey = 'dataTableBuilder';
            const $groupSelect = $('#filter_group_id');
            const $marketNameInput = $('#filter_market_name');
            let marketNameTypingTimer = null;

            const redrawTable = function () {
                if (!window.LaravelDataTables || !window.LaravelDataTables[tableKey]) {
                    return;
                }

                window.LaravelDataTables[tableKey].draw(false);
            };

            $(document).off('preXhr.dt.lottoMarketsFilter', tableSelector).on('preXhr.dt.lottoMarketsFilter', tableSelector, function (_event, _settings, data) {
                data.group_id = $groupSelect.val() || '';
                data.market_name = ($marketNameInput.val() || '').trim();
            });

            $groupSelect.off('change.lottoMarketsFilter').on('change.lottoMarketsFilter', function () {
                redrawTable();
            });

            $marketNameInput.off('input.lottoMarketsFilter').on('input.lottoMarketsFilter', function () {
                if (marketNameTypingTimer) {
                    clearTimeout(marketNameTypingTimer);
                }

                marketNameTypingTimer = setTimeout(function () {
                    redrawTable();
                }, 250);
            });
        });
    </script>
@endpush
