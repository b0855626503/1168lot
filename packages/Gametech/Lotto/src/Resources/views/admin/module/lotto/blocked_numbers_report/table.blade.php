@section('css')
    @include('admin::layouts.datatables_css')
@endsection
{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}
@push('scripts')
    @include('admin::layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    <script>
        const syncBlockedNumbersReportFilterUi = function (element) {
            if (!element || typeof window.jQuery !== 'function') {
                return;
            }

            const $element = window.jQuery(element);
            if ($element.hasClass('select2-hidden-accessible')) {
                $element.trigger('change.select2');
            }
        };

        $(function () {
            const redrawBlockedNumbersTable = function () {
                if (!window.LaravelDataTables || !window.LaravelDataTables['dataTableBuilder']) {
                    return;
                }

                window.LaravelDataTables['dataTableBuilder'].draw(false);
            };

            $(document).off('preXhr.dt.blockedNumbersFilter', '#dataTableBuilder').on('preXhr.dt.blockedNumbersFilter', '#dataTableBuilder', function (_e, _settings, data) {
                data.draw_date = $('#filter_draw_date').val() || '';
                data.market_id = $('#filter_market_id').val() || '';
                data.bet_type = $('#filter_bet_type').val() || '';
                data.mode = $('#filter_mode').val() || '';
            });

            $('#filter_draw_date, #filter_market_id, #filter_bet_type, #filter_mode')
                .off('change.blockedNumbersFilter')
                .on('change.blockedNumbersFilter', function () {
                    redrawBlockedNumbersTable();
                });

            window.resetBlockedNumbersReportFilters = function () {
                ['filter_draw_date', 'filter_market_id', 'filter_bet_type', 'filter_mode'].forEach((id) => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.value = '';
                        syncBlockedNumbersReportFilterUi(element);
                    }
                });

                redrawBlockedNumbersTable();
            };
        });
    </script>
@endpush
