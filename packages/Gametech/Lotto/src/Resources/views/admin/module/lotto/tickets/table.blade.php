@section('css')
    @include('admin::layouts.datatables_css')
    <style>
        /* === Ticket Detail Modal (#addedit) — all rules scoped === */
        #addedit .modal-dialog {
            max-width: 1050px;
        }
        #addedit .modal-body {
            padding: 14px;
            background: #f4f6f9;
        }
        #addedit .tcd-header {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 10px;
        }
        #addedit .tcd-header-id {
            font-size: 15px;
            font-weight: 700;
            color: #253247;
            margin-right: 8px;
        }
        #addedit .tcd-header-meta {
            font-size: 12px;
            color: #6c757d;
            line-height: 1.8;
        }
        #addedit .tcd-header-meta .sep {
            margin: 0 6px;
            color: #ced4da;
        }
        #addedit .tcd-section {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 10px;
        }
        #addedit .tcd-section-title {
            font-size: 10px;
            font-weight: 700;
            color: #8a94a6;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding-bottom: 6px;
            margin-bottom: 8px;
            border-bottom: 1px solid #f0f2f5;
        }
        #addedit .tcd-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 8px;
        }
        #addedit .tcd-info-grid .full { grid-column: 1 / -1; }
        #addedit .tcd-info-label {
            display: block;
            font-size: 10px;
            color: #8a94a6;
            line-height: 1.2;
            margin-bottom: 1px;
        }
        #addedit .tcd-info-value {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #253247;
            line-height: 1.4;
            word-break: break-word;
        }
        #addedit .tcd-amount-card {
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: right;
            background: #f8f9fa;
        }
        #addedit .tcd-amount-card.is-win {
            background: #fff5f5;
            border-color: #f5c6cb;
        }
        #addedit .tcd-amount-label {
            display: block;
            font-size: 10px;
            color: #8a94a6;
            margin-bottom: 2px;
        }
        #addedit .tcd-amount-value {
            display: block;
            font-size: 16px;
            font-weight: 700;
            color: #253247;
        }
        #addedit .tcd-amount-value.is-danger { color: #dc3545; }
        #addedit .tcd-reason-text {
            font-size: 12px;
            color: #495057;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 6px 10px;
            min-height: 34px;
        }
        #addedit .tcd-reason-text.is-empty {
            color: #adb5bd;
            font-style: italic;
        }
        #addedit .tcd-count-badge {
            font-weight: 600;
        }
        #addedit .tcd-table-wrapper {
            max-height: 280px;
            overflow: auto;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        #addedit .tcd-table-wrapper thead th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 2;
            font-size: 11px;
            border-bottom: 2px solid #dee2e6;
        }
        #addedit .tcd-table-wrapper .table td,
        #addedit .tcd-table-wrapper .table th {
            padding: 0.3rem 0.45rem;
            font-size: 12px;
        }
        #addedit .tcd-table-wrapper td.col-number {
            font-weight: 700;
            font-size: 13px;
            color: #253247;
            letter-spacing: 0.03em;
        }
        #addedit .tcd-table-wrapper tr.win-row td {
            background: #fff3cd !important;
        }
        @media (max-width: 767px) {
            #addedit .modal-dialog {
                max-width: 100%;
                margin: 8px;
            }
            #addedit .tcd-info-grid {
                grid-template-columns: 1fr;
            }
            #addedit .tcd-info-grid .full { grid-column: auto; }
        }
    </style>
@endsection
{!! $dataTable->table(['id' => 'lottoTicketsTable', 'width' => '100%', 'class' => 'table table-striped table-sm']) !!}
@push('scripts')
	@include('admin::layouts.datatables_js')
	{!! $dataTable->scripts() !!}
    <script>
        $(function () {
            const tableSelector = '#lottoTicketsTable';
            const tableKey = 'lottoTicketsTable';
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
