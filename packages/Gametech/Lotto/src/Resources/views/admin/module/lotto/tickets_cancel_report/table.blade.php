@section('css')
    @include('admin::layouts.datatables_css')
@endsection
{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}
@push('scripts')
    @include('admin::layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    <script>
        $(document).on('preXhr.dt', '#dataTableBuilder', function (_e, _settings, data) {
            data.date_start = $('#filter_date_start').val() || '';
            data.date_stop = $('#filter_date_stop').val() || '';
            data.market_id = $('#filter_market_id').val() || '';
            data.status = $('#filter_status').val() || '';
        });

        window.applyTicketsCancelFilters = function () {
            window.LaravelDataTables['dataTableBuilder'].draw();
        };

        window.resetTicketsCancelFilters = function () {
            ['filter_date_start', 'filter_date_stop', 'filter_market_id', 'filter_status'].forEach((id) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = '';
                }
            });

            window.LaravelDataTables['dataTableBuilder'].draw();
        };
    </script>
@endpush
