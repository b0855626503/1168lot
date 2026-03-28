@section('css')
    @include('admin::layouts.datatables_css')
    <style>
        .market-source-indicator {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-width: 30px;
        }

        .market-source-light {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            display: inline-block;
            position: relative;
        }

        .market-source-indicator-on .market-source-light {
            background: #22c55e;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.18), 0 0 10px rgba(34, 197, 94, 0.65);
            animation: market-source-pulse 1.8s ease-in-out infinite;
        }

        .market-source-indicator-off .market-source-light {
            background: #9ca3af;
            box-shadow: 0 0 0 2px rgba(156, 163, 175, 0.18);
        }

        .market-source-count {
            font-size: 11px;
            font-weight: 700;
            color: #166534;
            line-height: 1;
        }

        @keyframes market-source-pulse {
            0% { transform: scale(1); opacity: 0.95; }
            50% { transform: scale(1.12); opacity: 1; }
            100% { transform: scale(1); opacity: 0.95; }
        }
    </style>
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
