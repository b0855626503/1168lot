@section('css')
    @include('admin::layouts.datatables_css')
@endsection
{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}
@push('scripts')
    @include('admin::layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    <script>
        const syncProfitLossForecastFilterUi = function (element) {
            if (!element || typeof window.jQuery !== 'function') {
                return;
            }

            const $element = window.jQuery(element);
            if ($element.hasClass('select2-hidden-accessible')) {
                $element.trigger('change.select2');
            }
        };

        $(function () {
            const redrawProfitLossForecastTable = function () {
                if (!window.LaravelDataTables || !window.LaravelDataTables['dataTableBuilder']) {
                    return;
                }

                window.LaravelDataTables['dataTableBuilder'].draw(false);
            };

            $(document).off('preXhr.dt.profitLossForecastFilter', '#dataTableBuilder').on('preXhr.dt.profitLossForecastFilter', '#dataTableBuilder', function (_e, _settings, data) {
                data.draw_date = $('#filter_draw_date').val() || '';
                data.market_id = $('#filter_market_id').val() || '';
                data.bet_type = $('#filter_bet_type').val() || '';
            });

            $('#filter_draw_date, #filter_market_id, #filter_bet_type')
                .off('change.profitLossForecastFilter')
                .on('change.profitLossForecastFilter', function () {
                    redrawProfitLossForecastTable();
                });

            window.resetProfitLossForecastFilters = function () {
                ['filter_draw_date', 'filter_market_id', 'filter_bet_type'].forEach((id) => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.value = '';
                        syncProfitLossForecastFilterUi(element);
                    }
                });

                redrawProfitLossForecastTable();
            };
        });
    </script>
@endpush
