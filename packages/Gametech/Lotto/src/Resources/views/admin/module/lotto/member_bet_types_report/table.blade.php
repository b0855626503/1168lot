@section('css')
    @include('admin::layouts.datatables_css')
@endsection
{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}
@push('scripts')
    @include('admin::layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    <script>
        const syncMemberBetTypesFilterUi = function (element) {
            if (!element || typeof window.jQuery !== 'function') {
                return;
            }

            const $element = window.jQuery(element);
            if ($element.hasClass('select2-hidden-accessible')) {
                $element.trigger('change.select2');
            }
        };

        $(function () {
            let memberKeywordTimer = null;

            const redrawMemberBetTypesTable = function () {
                if (!window.LaravelDataTables || !window.LaravelDataTables['dataTableBuilder']) {
                    return;
                }

                window.LaravelDataTables['dataTableBuilder'].draw(false);
            };

            $(document).off('preXhr.dt.memberBetTypesFilter', '#dataTableBuilder').on('preXhr.dt.memberBetTypesFilter', '#dataTableBuilder', function (_e, _settings, data) {
                data.member_keyword = $('#filter_member_keyword').val() || '';
                data.date_start = $('#filter_date_start').val() || '';
                data.date_stop = $('#filter_date_stop').val() || '';
                data.market_id = $('#filter_market_id').val() || '';
                data.bet_type = $('#filter_bet_type').val() || '';
            });

            $('#filter_date_start, #filter_date_stop, #filter_market_id, #filter_bet_type')
                .off('change.memberBetTypesFilter')
                .on('change.memberBetTypesFilter', function () {
                    redrawMemberBetTypesTable();
                });

            $('#filter_member_keyword')
                .off('input.memberBetTypesFilter')
                .on('input.memberBetTypesFilter', function () {
                    window.clearTimeout(memberKeywordTimer);
                    memberKeywordTimer = window.setTimeout(function () {
                        redrawMemberBetTypesTable();
                    }, 250);
                });

            window.resetMemberBetTypesFilters = function () {
                ['filter_member_keyword', 'filter_date_start', 'filter_date_stop', 'filter_market_id', 'filter_bet_type'].forEach((id) => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.value = '';
                        syncMemberBetTypesFilterUi(element);
                    }
                });

                window.clearTimeout(memberKeywordTimer);
                redrawMemberBetTypesTable();
            };
        });
    </script>
@endpush
