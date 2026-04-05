@section('css')
	@include('admin::layouts.datatables_css')
@endsection
{!! $dataTable->table(['id' => 'lottoTicketsTable', 'width' => '100%', 'class' => 'table table-striped table-sm']) !!}
@push('scripts')
	@include('admin::layouts.datatables_js')
	{!! $dataTable->scripts() !!}
    <script>
        $(function () {
            const tableSelector = '#lottoTicketsTable';
            const tableKey = 'lottoTicketsTable';
            const menuBadgeKey = @json($menuBadgeKey ?? 'lotto_tickets');
            const $marketSelect = $('#filter_market_id');
            const $drawSelect = $('#filter_draw_id');
            const drawOptionsByMarket = @json($drawOptionsByMarket ?? []);
            const drawDefaultOption = '<option value="">เลือกงวดหวย</option>';
            const initMarketSelect = function () {
                if (!$marketSelect.length || typeof $marketSelect.select2 !== 'function') {
                    return;
                }

                if ($marketSelect.hasClass('select2-hidden-accessible')) {
                    $marketSelect.select2('destroy');
                }

                const renderMarketOption = function (state) {
                    if (!state.id) {
                        return state.text;
                    }

                    const optionEl = state.element;
                    const logo = optionEl ? String(optionEl.getAttribute('data-logo') || '') : '';
                    const safeText = $('<span/>').text(state.text || '').html();

                    if (!logo) {
                        return $('<span>' + safeText + '</span>');
                    }

                    return $(
                        '<span style="display:flex;align-items:center;gap:8px;">'
                        + '<img src="' + logo + '" alt="" style="width:20px;height:20px;object-fit:cover;border-radius:50%;border:1px solid #e5e7eb;">'
                        + '<span>' + safeText + '</span>'
                        + '</span>'
                    );
                };

                $marketSelect.select2({
                    width: '100%',
                    placeholder: 'เลือกรายการหวย',
                    allowClear: true,
                    templateResult: renderMarketOption,
                    templateSelection: renderMarketOption,
                    escapeMarkup: function (markup) {
                        return markup;
                    },
                });
            };

            const renderDrawOptions = function (marketId) {
                const normalizedMarketId = String(marketId || '');
                const options = normalizedMarketId !== '' ? (drawOptionsByMarket[normalizedMarketId] || []) : [];

                $drawSelect.empty().append(drawDefaultOption);

                if (!normalizedMarketId) {
                    $drawSelect.prop('disabled', true);
                    return;
                }

                if (!options.length) {
                    $drawSelect.prop('disabled', true);
                    return;
                }

                options.forEach(function (option) {
                    $drawSelect.append(
                        $('<option>', {
                            value: String(option.value),
                            text: String(option.text || '-'),
                        })
                    );
                });

                // auto เลือกงวดล่าสุด (รายการแรกของข้อมูลที่ sort desc ไว้แล้ว)
                $drawSelect.prop('disabled', false).val(String(options[0].value));
            };

            const redrawTicketTable = function () {
                if (!window.LaravelDataTables || !window.LaravelDataTables[tableKey]) {
                    return;
                }

                window.LaravelDataTables[tableKey].draw(false);
            };

            $(document).off('xhr.dt.lottoTicketsBadge', tableSelector).on('xhr.dt.lottoTicketsBadge', tableSelector, function (_event, _settings, json) {
                const total = Number(json && typeof json.recordsTotal !== 'undefined' ? json.recordsTotal : 0);
                const value = Number.isFinite(total) ? total : 0;

                if (typeof window.update === 'function') {
                    window.update(menuBadgeKey, value);
                }
            });

            $(document).off('preXhr.dt.lottoTicketsFilter', tableSelector).on('preXhr.dt.lottoTicketsFilter', tableSelector, function (_event, _settings, data) {
                data.market_id = $marketSelect.val() || '';
                data.draw_id = $drawSelect.val() || '';
            });

            $marketSelect.off('change.lottoTicketsFilter').on('change.lottoTicketsFilter', function () {
                renderDrawOptions($(this).val());
                redrawTicketTable();
            });

            $drawSelect.off('change.lottoTicketsFilter').on('change.lottoTicketsFilter', function () {
                redrawTicketTable();
            });

            renderDrawOptions($marketSelect.val());
            initMarketSelect();

            $(document).off('click.lottoTicketsRow', tableSelector + ' tbody tr').on('click.lottoTicketsRow', tableSelector + ' tbody tr', function (event) {
                if ($(event.target).closest('a, button, input, select, textarea, label, .js-no-row-open').length) {
                    return;
                }

                if (!window.LaravelDataTables || !window.LaravelDataTables[tableKey]) {
                    return;
                }

                const dt = window.LaravelDataTables[tableKey];
                const rowData = dt.row(this).data();

                if (!rowData || typeof rowData.id === 'undefined' || rowData.id === null) {
                    return;
                }

                if (typeof window.editModal === 'function') {
                    window.editModal(rowData.id);
                }
            });
        });
    </script>
@endpush
