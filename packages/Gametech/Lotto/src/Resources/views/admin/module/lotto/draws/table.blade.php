@section('css')
    @include('admin::layouts.datatables_css')
    <style>
        #lottoDrawsTable thead th,
        #lottoDrawsTable tbody td {
            vertical-align: middle !important;
        }

        #lottoDrawsTable tbody td {
            text-align: center;
        }

        #lottoDrawsTable .draw-status-toggle-btn {
            border: 0;
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.3;
            color: #fff;
            cursor: pointer;
            transition: transform .12s ease, opacity .12s ease;
        }

        #lottoDrawsTable .draw-status-toggle-btn:hover {
            transform: translateY(-1px);
            opacity: .92;
        }

        #lottoDrawsTable .draw-status-toggle-open {
            background: #1d9d57;
            box-shadow: 0 1px 0 rgba(0, 0, 0, .08);
        }

        #lottoDrawsTable .draw-status-toggle-closed {
            background: #d97706;
            box-shadow: 0 1px 0 rgba(0, 0, 0, .08);
        }

        #lottoDrawsTable .draw-status-static {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.3;
        }

        #lottoDrawsTable .draw-status-static-draft {
            color: #4b5563;
            background: #eef2f7;
        }

        #lottoDrawsTable .draw-status-static-open {
            color: #166534;
            background: #dcfce7;
        }

        #lottoDrawsTable .draw-status-static-closed {
            color: #92400e;
            background: #fef3c7;
        }

        #lottoDrawsTable .draw-status-static-resulted {
            color: #1d4ed8;
            background: #dbeafe;
        }

        #lottoDrawsTable .draw-status-static-default {
            color: #374151;
            background: #e5e7eb;
        }

        #lottoDrawsTable tbody tr.draw-status-draft td {
            background-color: #e9edf5 !important;
        }

        #lottoDrawsTable tbody tr.draw-status-open td {
            background-color: #dff5e7 !important;
        }

        #lottoDrawsTable tbody tr.draw-status-closed td {
            background-color: #ffecc9 !important;
        }

        #lottoDrawsTable tbody tr.draw-status-resulted td {
            background-color: #dcecff !important;
        }
    </style>
@endsection


{!! $dataTable->table(['id' => 'lottoDrawsTable', 'width' => '100%', 'class' => 'table table-striped table-sm table-border']) !!}

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
            const $statusSelect = $('#filter_status');

            const initMarketSelect = function () {
                // temporary: keep list filter as native select.
                // select2 for market will run only in add/edit modal.
            };

            const rebuildMarketFilterOptions = function (groupId) {
                const selectedGroup = String(groupId || '');
                const selectedMarket = String($marketSelect.val() || '');

                $marketSelect.find('option').each(function () {
                    const $option = $(this);
                    const value = String($option.val() || '');

                    if (value === '') {
                        $option.prop('hidden', false).prop('disabled', false);
                        return;
                    }

                    const optionGroupId = String($option.data('group-id') || '');
                    const visible = !selectedGroup || optionGroupId === selectedGroup;
                    $option.prop('hidden', !visible).prop('disabled', !visible);
                });

                $marketSelect.find('optgroup').each(function () {
                    const $optgroup = $(this);
                    const hasVisibleOptions = $optgroup.find('option').filter(function () {
                        const value = String($(this).val() || '');
                        if (value === '') {
                            return false;
                        }

                        return !$(this).prop('hidden');
                    }).length > 0;

                    $optgroup.prop('hidden', !hasVisibleOptions);
                });

                if (selectedMarket) {
                    const $selectedOption = $marketSelect.find('option[value="' + selectedMarket + '"]');
                    if (! $selectedOption.length || $selectedOption.prop('hidden')) {
                        $marketSelect.val('');
                    }
                }
            };

            const redrawTable = function () {
                if (!window.LaravelDataTables || !window.LaravelDataTables[tableKey]) {
                    return;
                }

                window.LaravelDataTables[tableKey].draw(false);
            };

            const applyStatusRowBackground = function () {
                if (!window.LaravelDataTables || !window.LaravelDataTables[tableKey]) {
                    return;
                }

                const tableApi = window.LaravelDataTables[tableKey];
                tableApi.rows({ page: 'current' }).every(function () {
                    const rowData = this.data() || {};
                    const statusHtml = String(rowData.status || '');
                    const $row = $(this.node());

                    $row.removeClass('draw-status-draft draw-status-open draw-status-closed draw-status-resulted');

                    if (statusHtml.includes('เปิดรับ')) {
                        $row.addClass('draw-status-open');
                        return;
                    }

                    if (statusHtml.includes('ปิดรับ')) {
                        $row.addClass('draw-status-closed');
                        return;
                    }

                    if (statusHtml.includes('ประกาศผล')) {
                        $row.addClass('draw-status-resulted');
                        return;
                    }

                    $row.addClass('draw-status-draft');
                });
            };

            $(document)
                .off('preXhr.dt.lottoDrawsFilter', tableSelector)
                .on('preXhr.dt.lottoDrawsFilter', tableSelector, function (_event, _settings, data) {
                    data.group_id = $groupSelect.val() || '';
                    data.market_id = $marketSelect.val() || '';
                    data.draw_date = $drawDateInput.val() || '';
                    data.status = $statusSelect.val() || '';
                });

            $groupSelect
                .off('change.lottoDrawsFilter')
                .on('change.lottoDrawsFilter', function () {
                    rebuildMarketFilterOptions($(this).val() || '');
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

            $statusSelect
                .off('change.lottoDrawsFilter')
                .on('change.lottoDrawsFilter', function () {
                    redrawTable();
                });

            initMarketSelect();
            rebuildMarketFilterOptions($groupSelect.val() || '');

            $(tableSelector).off('draw.dt.lottoDrawsStatusTint').on('draw.dt.lottoDrawsStatusTint', function () {
                applyStatusRowBackground();
            });

            applyStatusRowBackground();

            // DataTable first request may fire before custom filters are attached.
            // Force one redraw so default draw_date filter is applied on initial page load.
            if ($drawDateInput.val()) {
                redrawTable();
            }
        });
    </script>
@endpush
