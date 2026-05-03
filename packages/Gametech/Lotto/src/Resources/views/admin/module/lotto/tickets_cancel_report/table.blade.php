@section('css')
    @include('admin::layouts.datatables_css')
    <style>
        /* === Tickets Cancel Detail Modal — all rules scoped under #ticketsCancelDetailModal === */
        #ticketsCancelDetailModal .modal-dialog {
            max-width: 1050px;
        }
        #ticketsCancelDetailModal .modal-body {
            padding: 14px;
            background: #f4f6f9;
        }
        #ticketsCancelDetailModal .tcd-header {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 10px;
        }
        #ticketsCancelDetailModal .tcd-header-id {
            font-size: 15px;
            font-weight: 700;
            color: #253247;
            margin-right: 8px;
        }
        #ticketsCancelDetailModal .tcd-header-meta {
            font-size: 12px;
            color: #6c757d;
            line-height: 1.8;
        }
        #ticketsCancelDetailModal .tcd-header-meta .sep {
            margin: 0 6px;
            color: #ced4da;
        }
        #ticketsCancelDetailModal .tcd-section {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 10px;
        }
        #ticketsCancelDetailModal .tcd-section-title {
            font-size: 10px;
            font-weight: 700;
            color: #8a94a6;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding-bottom: 6px;
            margin-bottom: 8px;
            border-bottom: 1px solid #f0f2f5;
        }
        #ticketsCancelDetailModal .tcd-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 8px;
        }
        #ticketsCancelDetailModal .tcd-info-grid .full { grid-column: 1 / -1; }
        #ticketsCancelDetailModal .tcd-info-label {
            display: block;
            font-size: 10px;
            color: #8a94a6;
            line-height: 1.2;
            margin-bottom: 1px;
        }
        #ticketsCancelDetailModal .tcd-info-value {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #253247;
            line-height: 1.4;
            word-break: break-word;
        }
        #ticketsCancelDetailModal .tcd-amount-card {
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: right;
            background: #f8f9fa;
        }
        #ticketsCancelDetailModal .tcd-amount-card.is-win {
            background: #fff5f5;
            border-color: #f5c6cb;
        }
        #ticketsCancelDetailModal .tcd-amount-label {
            display: block;
            font-size: 10px;
            color: #8a94a6;
            margin-bottom: 2px;
        }
        #ticketsCancelDetailModal .tcd-amount-value {
            display: block;
            font-size: 16px;
            font-weight: 700;
            color: #253247;
        }
        #ticketsCancelDetailModal .tcd-amount-value.is-danger { color: #dc3545; }
        #ticketsCancelDetailModal .tcd-reason-text {
            font-size: 12px;
            color: #495057;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 6px 10px;
            min-height: 34px;
        }
        #ticketsCancelDetailModal .tcd-reason-text.is-empty {
            color: #adb5bd;
            font-style: italic;
        }
        #ticketsCancelDetailModal .tcd-round-badge {
            font-size: 10px;
            vertical-align: middle;
            line-height: 1.2;
        }
        #ticketsCancelDetailModal .tcd-count-badge {
            font-weight: 600;
        }
        #ticketsCancelDetailTableWrapper {
            max-height: 280px;
            overflow-y: auto;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        #ticketsCancelDetailTableWrapper thead th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 2;
            font-size: 11px;
            border-bottom: 2px solid #dee2e6;
        }
        #ticketsCancelDetailTableWrapper .table td,
        #ticketsCancelDetailTableWrapper .table th {
            padding: 0.3rem 0.45rem;
            font-size: 12px;
        }
        #ticketsCancelDetailTableWrapper td.col-number {
            font-weight: 700;
            font-size: 13px;
            color: #253247;
            letter-spacing: 0.03em;
        }
        #ticketsCancelDetailTableWrapper tr.win-row td {
            background: #fff3cd !important;
        }
        @media (max-width: 767px) {
            #ticketsCancelDetailModal .modal-dialog {
                max-width: 100%;
                margin: 8px;
            }
            #ticketsCancelDetailModal .tcd-info-grid {
                grid-template-columns: 1fr;
            }
            #ticketsCancelDetailModal .tcd-info-grid .full { grid-column: auto; }
        }
    </style>
@endsection
{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}
<div id="ticketsCancelTotals" class="mt-2 p-2 border rounded bg-light text-sm">
    <div class="row">
        <div class="col-md-3 col-6 mb-1">
            <span class="text-muted">ผลรวมยอดแทง:</span>
            <strong id="ticketsCancelTotalBet" class="d-inline-block ml-1">0.00</strong>
        </div>
        <div class="col-md-3 col-6 mb-1">
            <span class="text-muted">ผลรวมส่วนลด:</span>
            <strong id="ticketsCancelTotalDiscount" class="d-inline-block ml-1">0.00</strong>
        </div>
        <div class="col-md-3 col-6 mb-1">
            <span class="text-muted">ผลรวมสุทธิ:</span>
            <strong id="ticketsCancelTotalNet" class="d-inline-block ml-1">0.00</strong>
        </div>
        <div class="col-md-3 col-6 mb-1">
            <span class="text-muted">ผลรวมยอดถูก:</span>
            <strong id="ticketsCancelTotalWin" class="d-inline-block ml-1 text-danger">0.00</strong>
        </div>
    </div>
</div>
<div class="modal fade" id="ticketsCancelDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title">รายละเอียดโพย</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="ticketsCancelDetailLoading" class="alert alert-light border mb-2 py-1 px-2 d-none">กำลังโหลดรายละเอียด...</div>
                <div id="ticketsCancelDetailSummary"></div>
            </div>
        </div>
    </div>
</div>
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
            const detailUrl = @json($ticketDetailUrl ?? route('admin.lotto.reports.tickets_cancel.ticket_detail'));

            const formatMoney = function (value) {
                return Number(value || 0).toLocaleString('th-TH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            };

            const isPositive = function (value) {
                return Number(value || 0) > 0;
            };

            const escapeHtml = function (value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const redrawTicketsCancelTable = function () {
                if (!window.LaravelDataTables || !window.LaravelDataTables['dataTableBuilder']) {
                    return;
                }

                window.LaravelDataTables['dataTableBuilder'].draw(false);
            };

            const parseMoney = function (value) {
                const normalized = String(value ?? '')
                    .replace(/<[^>]+>/g, '')
                    .replace(/[^\d.-]/g, '');
                const parsed = Number(normalized);

                return Number.isFinite(parsed) ? parsed : 0;
            };

            const refreshTotals = function () {
                if (!window.LaravelDataTables || !window.LaravelDataTables['dataTableBuilder']) {
                    return;
                }

                const api = window.LaravelDataTables['dataTableBuilder'];
                const ajaxJson = api.ajax.json ? api.ajax.json() : null;
                const serverTotals = ajaxJson && ajaxJson.totals ? ajaxJson.totals : null;

                if (serverTotals) {
                    $('#ticketsCancelTotalBet').text(formatMoney(serverTotals.total_bet_amount));
                    $('#ticketsCancelTotalDiscount').text(formatMoney(serverTotals.total_discount_amount));
                    $('#ticketsCancelTotalNet').text(formatMoney(serverTotals.total_net_amount));
                    $('#ticketsCancelTotalWin').text(formatMoney(serverTotals.total_win_amount));
                    return;
                }

                const rows = api.rows({ page: 'current' }).data().toArray();

                let totalBet = 0;
                let totalDiscount = 0;
                let totalNet = 0;
                let totalWin = 0;

                rows.forEach((row) => {
                    totalBet += parseMoney(row.total_bet_amount);
                    totalDiscount += parseMoney(row.total_discount_amount);
                    totalNet += parseMoney(row.total_net_amount);
                    totalWin += parseMoney(row.total_win_amount);
                });

                $('#ticketsCancelTotalBet').text(formatMoney(totalBet));
                $('#ticketsCancelTotalDiscount').text(formatMoney(totalDiscount));
                $('#ticketsCancelTotalNet').text(formatMoney(totalNet));
                $('#ticketsCancelTotalWin').text(formatMoney(totalWin));
            };

            const statusBadgeHtml = function (status, label) {
                const map = { active: 'warning', cancelled: 'secondary', resulted: 'primary', won: 'success' };
                const cls = 'badge-' + (map[String(status)] || 'light');

                return '<span class="badge ' + cls + '">' + escapeHtml(label || status || '-') + '</span>';
            };

            /**
             * Build market display HTML with optional yeekee round badge.
             *
             * TODO: The round number should ideally come from a dedicated `round_no` field
             * in the ticket payload (e.g. ticket.draw_round_no) so this display is resilient
             * to format changes. Currently it falls back to parsing the plain-text market_name
             * produced by LottoMarketDisplayFormatter::formatPlain(), which returns the format
             * "{name} (รอบ {N})" for yeekee draws. Update the controller to expose the field
             * and remove the regex fallback once the backend change is made.
             */
            const marketDisplayHtml = function (rawName) {
                const m = String(rawName || '').match(/^(.*?)\s*\(รอบ\s*(\d+)\)\s*$/);
                if (m) {
                    return escapeHtml(m[1].trim()) +
                        ' <span class="badge badge-info ml-1 tcd-round-badge">รอบ ' +
                        escapeHtml(m[2]) + '</span>';
                }

                return escapeHtml(rawName || '-');
            };

            /* ── Section builders ── */

            const buildHeaderHtml = function (ticket) {
                return '<div class="tcd-header">' +
                    '<div class="d-flex flex-wrap align-items-start">' +
                    '<div class="mr-2">' +
                    '<span class="tcd-header-id">#' + escapeHtml(ticket.id || '-') + '</span>' +
                    statusBadgeHtml(ticket.status, ticket.status_label) +
                    '</div>' +
                    '<div class="tcd-header-meta flex-grow-1 mt-1 mt-sm-0">' +
                    '<span>วันที่แทง: ' + escapeHtml(ticket.created_at || '-') + '</span>' +
                    '<span class="sep">|</span>' +
                    '<span>สมาชิก: ' + escapeHtml(ticket.member_display || '-') + '</span>' +
                    '<span class="sep">|</span>' +
                    '<span>ตลาด: ' + marketDisplayHtml(ticket.market_name) + '</span>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            };

            const buildInfoHtml = function (ticket) {
                return '<div class="tcd-section">' +
                    '<div class="tcd-section-title">ข้อมูลโพย / สมาชิก</div>' +
                    '<div class="tcd-info-grid">' +
                    '<div><span class="tcd-info-label">เลขโพย</span><span class="tcd-info-value">#' + escapeHtml(ticket.id || '-') + '</span></div>' +
                    '<div><span class="tcd-info-label">สถานะ</span><span class="tcd-info-value">' + statusBadgeHtml(ticket.status, ticket.status_label) + '</span></div>' +
                    '<div><span class="tcd-info-label">วันที่แทง</span><span class="tcd-info-value">' + escapeHtml(ticket.created_at || '-') + '</span></div>' +
                    '<div><span class="tcd-info-label">วันงวด</span><span class="tcd-info-value">' + escapeHtml(ticket.draw_date || '-') + '</span></div>' +
                    '<div><span class="tcd-info-label">ผู้ยกเลิก</span><span class="tcd-info-value">' + escapeHtml(ticket.cancelled_by_name || '-') + '</span></div>' +
                    '<div><span class="tcd-info-label">วันที่ยกเลิก</span><span class="tcd-info-value">' + escapeHtml(ticket.cancelled_at || '-') + '</span></div>' +
                    '<div class="full"><span class="tcd-info-label">สมาชิก</span><span class="tcd-info-value">' + escapeHtml(ticket.member_display || '-') + '</span></div>' +
                    '<div class="full"><span class="tcd-info-label">ตลาด / หวย</span><span class="tcd-info-value">' + marketDisplayHtml(ticket.market_name) + '</span></div>' +
                    '<div class="full"><span class="tcd-info-label">แพกเกจ</span><span class="tcd-info-value">' + escapeHtml(ticket.packages || '-') + '</span></div>' +
                    '</div>' +
                    '</div>';
            };

            const buildAmountsHtml = function (ticket) {
                const winValueClass = isPositive(ticket.total_win_amount) ? 'tcd-amount-value is-danger' : 'tcd-amount-value';

                return '<div class="tcd-section">' +
                    '<div class="tcd-section-title">ยอดเงิน</div>' +
                    '<div class="row mx-n1">' +
                    '<div class="col-6 col-md-3 px-1 mb-2"><div class="tcd-amount-card"><span class="tcd-amount-label">ยอดแทง</span><span class="tcd-amount-value">' + formatMoney(ticket.total_bet_amount) + '</span></div></div>' +
                    '<div class="col-6 col-md-3 px-1 mb-2"><div class="tcd-amount-card"><span class="tcd-amount-label">ส่วนลด</span><span class="tcd-amount-value">' + formatMoney(ticket.total_discount_amount) + '</span></div></div>' +
                    '<div class="col-6 col-md-3 px-1 mb-2"><div class="tcd-amount-card"><span class="tcd-amount-label">ยอดรับ</span><span class="tcd-amount-value">' + formatMoney(ticket.total_net_amount) + '</span></div></div>' +
                    '<div class="col-6 col-md-3 px-1 mb-2"><div class="tcd-amount-card is-win"><span class="tcd-amount-label">ยอดถูก</span><span class="' + winValueClass + '">' + formatMoney(ticket.total_win_amount) + '</span></div></div>' +
                    '</div>' +
                    '</div>';
            };

            const buildReasonHtml = function (ticket) {
                const empty = !ticket.reason || ticket.reason === '-';
                const cls = empty ? 'tcd-reason-text is-empty' : 'tcd-reason-text';

                return '<div class="tcd-section">' +
                    '<div class="tcd-section-title">หมายเหตุ / สาเหตุยกเลิก</div>' +
                    '<div class="' + cls + '">' + escapeHtml(empty ? '-' : ticket.reason) + '</div>' +
                    '</div>';
            };

            const buildItemsTableHtml = function (items) {
                let tbodyHtml;
                if (items.length === 0) {
                    tbodyHtml = '<tr><td colspan="7" class="text-center text-muted py-3">ไม่พบรายการย่อยในโพย</td></tr>';
                } else {
                    tbodyHtml = items.map(function (item) {
                        const hasWin = isPositive(item.win_amount);
                        const rowClass = hasWin ? ' class="win-row"' : '';
                        const winTdClass = hasWin ? 'text-right text-danger font-weight-bold' : 'text-right';

                        return '<tr' + rowClass + '>' +
                            '<td class="text-center">' + escapeHtml(item.bet_type_label || item.bet_type || '-') + '</td>' +
                            '<td class="text-center col-number">' + escapeHtml(item.number || '-') + '</td>' +
                            '<td class="text-right">' + formatMoney(item.amount) + '</td>' +
                            '<td class="text-right">' + formatMoney(item.discount_amount) + '</td>' +
                            '<td class="text-right">' + formatMoney(item.net_amount) + '</td>' +
                            '<td class="text-right">' + formatMoney(item.payout) + '</td>' +
                            '<td class="' + winTdClass + '">' + formatMoney(item.win_amount) + '</td>' +
                            '</tr>';
                    }).join('');
                }

                const countBadge = items.length > 0
                    ? ' <span class="badge badge-light tcd-count-badge">' + items.length + ' รายการ</span>'
                    : '';

                return '<div class="tcd-section">' +
                    '<div class="tcd-section-title">รายละเอียดเลขแทง' + countBadge + '</div>' +
                    '<div id="ticketsCancelDetailTableWrapper">' +
                    '<table class="table table-bordered table-sm mb-0">' +
                    '<thead class="thead-light"><tr>' +
                    '<th class="text-center">ประเภท</th>' +
                    '<th class="text-center">เลข</th>' +
                    '<th class="text-right">ยอดแทง</th>' +
                    '<th class="text-right">ส่วนลด</th>' +
                    '<th class="text-right">ยอดรับ</th>' +
                    '<th class="text-right">อัตราจ่าย</th>' +
                    '<th class="text-right">ยอดถูก</th>' +
                    '</tr></thead>' +
                    '<tbody>' + tbodyHtml + '</tbody>' +
                    '</table>' +
                    '</div>' +
                    '</div>';
            };

            const buildDetailHtml = function (ticket, items) {
                return buildHeaderHtml(ticket) +
                    '<div class="row">' +
                    '<div class="col-lg-5">' + buildInfoHtml(ticket) + '</div>' +
                    '<div class="col-lg-7">' + buildAmountsHtml(ticket) + buildReasonHtml(ticket) + '</div>' +
                    '</div>' +
                    buildItemsTableHtml(items);
            };

            $(document).off('preXhr.dt.ticketsCancelFilter', '#dataTableBuilder').on('preXhr.dt.ticketsCancelFilter', '#dataTableBuilder', function (_e, _settings, data) {
                data.date_start = $('#filter_date_start').val() || '';
                data.date_stop = $('#filter_date_stop').val() || '';
                data.market_id = $('#filter_market_id').val() || '';
                data.status = $('#filter_status').val() || '';
                data.member_username = $('#filter_member_username').val() || '';
            });

            $('#filter_date_start, #filter_date_stop, #filter_market_id, #filter_status, #filter_member_username')
                .off('change.ticketsCancelFilter')
                .on('change.ticketsCancelFilter', function () {
                    redrawTicketsCancelTable();
                });

            $('#filter_member_username')
                .off('input.ticketsCancelFilter')
                .on('input.ticketsCancelFilter', function () {
                    redrawTicketsCancelTable();
                });

            $(document).off('draw.dt.ticketsCancelTotals', '#dataTableBuilder').on('draw.dt.ticketsCancelTotals', '#dataTableBuilder', function () {
                refreshTotals();
            });

            window.resetTicketsCancelFilters = function () {
                ['filter_date_start', 'filter_date_stop', 'filter_market_id', 'filter_status', 'filter_member_username'].forEach((id) => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.value = '';
                        syncTicketsCancelFilterUi(element);
                    }
                });

                redrawTicketsCancelTable();
            };

            $(document).off('click.ticketsCancelDetail', '.js-tickets-cancel-detail').on('click.ticketsCancelDetail', '.js-tickets-cancel-detail', function () {
                const ticketId = Number($(this).data('ticket-id') || 0);
                if (!ticketId) {
                    return;
                }

                $('#ticketsCancelDetailModal').modal('show');
                $('#ticketsCancelDetailLoading').removeClass('d-none');
                $('#ticketsCancelDetailSummary').empty();

                const requestUrl = `${detailUrl}?id=${encodeURIComponent(ticketId)}`;
                if (window.axios && typeof window.axios.get === 'function') {
                    window.axios.get(requestUrl, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        timeout: 15000,
                    }).then((response) => {
                        const payload = response && response.data ? response.data : {};
                        const ticket = payload.ticket || {};
                        const items = Array.isArray(payload.items) ? payload.items : [];

                        $('#ticketsCancelDetailSummary').html(buildDetailHtml(ticket, items));
                    }).catch(() => {
                        $('#ticketsCancelDetailSummary').html('<div class="alert alert-danger mb-0">โหลดรายละเอียดโพยไม่สำเร็จ กรุณาลองใหม่</div>');
                    }).then(() => {
                        $('#ticketsCancelDetailLoading').addClass('d-none');
                    });

                    return;
                }

                $('#ticketsCancelDetailLoading').addClass('d-none');
                $('#ticketsCancelDetailSummary').html('<div class="alert alert-danger mb-0">ไม่พบ HTTP client (axios)</div>');
            });
        });
    </script>
@endpush
