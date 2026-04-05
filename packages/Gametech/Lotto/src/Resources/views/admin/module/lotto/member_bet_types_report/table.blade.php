@section('css')
    @include('admin::layouts.datatables_css')
@endsection
{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}
@push('scripts')
    @include('admin::layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    <script>
        $(document).on('preXhr.dt', '#dataTableBuilder', function (_e, _settings, data) {
            data.member_keyword = $('#filter_member_keyword').val() || '';
            data.date_start = $('#filter_date_start').val() || '';
            data.date_stop = $('#filter_date_stop').val() || '';
            data.market_id = $('#filter_market_id').val() || '';
            data.bet_type = $('#filter_bet_type').val() || '';
        });

        window.applyMemberBetTypesFilters = function () {
            window.LaravelDataTables['dataTableBuilder'].draw();
        };

        window.resetMemberBetTypesFilters = function () {
            ['filter_member_keyword', 'filter_date_start', 'filter_date_stop', 'filter_market_id', 'filter_bet_type'].forEach((id) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = '';
                }
            });

            window.LaravelDataTables['dataTableBuilder'].draw();
        };
    </script>
@endpush
