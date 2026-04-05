@section('css')
    @include('admin::layouts.datatables_css')
@endsection
{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}
@push('scripts')
    @include('admin::layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    <script>
        const syncTicketsCancelFilterUi = function (element) {
            if (!element || typeof window.jQuery !== 'function') {
                return;
            }

            const $element = window.jQuery(element);
            if ($element.hasClass('select2-hidden-accessible')) {
                $element.trigger('change.select2');
            }
        };

        $(function () {
            const redrawTicketsCancelTable = function () {
                if (!window.LaravelDataTables || !window.LaravelDataTables['dataTableBuilder']) {
                    return;
                }

                window.LaravelDataTables['dataTableBuilder'].draw(false);
            };

            $(document).off('preXhr.dt.ticketsCancelFilter', '#dataTableBuilder').on('preXhr.dt.ticketsCancelFilter', '#dataTableBuilder', function (_e, _settings, data) {
                data.date_start = $('#filter_date_start').val() || '';
                data.date_stop = $('#filter_date_stop').val() || '';
                data.market_id = $('#filter_market_id').val() || '';
                data.status = $('#filter_status').val() || '';
            });

            $('#filter_date_start, #filter_date_stop, #filter_market_id, #filter_status')
                .off('change.ticketsCancelFilter')
                .on('change.ticketsCancelFilter', function () {
                    redrawTicketsCancelTable();
                });

            window.resetTicketsCancelFilters = function () {
                ['filter_date_start', 'filter_date_stop', 'filter_market_id', 'filter_status'].forEach((id) => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.value = '';
                        syncTicketsCancelFilterUi(element);
                    }
                });

                redrawTicketsCancelTable();
            };
        });
    </script>
@endpush
