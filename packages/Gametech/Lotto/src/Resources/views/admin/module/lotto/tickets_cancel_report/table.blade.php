@section('css')
    @include('admin::layouts.datatables_css')
    <style>
        #ticketsCancelDetailModal .modal-header {
            border-bottom: 1px solid #e9ecef;
        }
        #ticketsCancelDetailSummary .ticket-detail-card {
            border: 1px solid #edf0f5;
            border-radius: 6px;
            padding: 6px 8px;
            height: 100%;
            background: #fff;
        }
        #ticketsCancelDetailSummary .ticket-detail-label {
            display: block;
            font-size: 10px;
            color: #7a8599;
            margin-bottom: 1px;
            line-height: 1.2;
        }
        #ticketsCancelDetailSummary .ticket-detail-value {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #253247;
            line-height: 1.3;
        }
        #ticketsCancelDetailSummary .ticket-detail-value.is-danger {
            color: #dc3545;
        }
        #ticketsCancelDetailModal .modal-body {
            padding: 10px;
        }
        #ticketsCancelDetailModal .table td,
        #ticketsCancelDetailModal .table th {
            padding: 0.3rem;
        }
        #ticketsCancelDetailTableWrapper {
            max-height: 320px;
            overflow-y: auto;
            border-radius: 0 0 6px 6px;
            border: 1px solid #e9ecef;
            background: #fff;
        }
        #ticketsCancelDetailTableWrapper thead th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 2;
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
    <div class="modal-dialog modal-md modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title">รายละเอียดโพย</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-sm">
                <div id="ticketsCancelDetailLoading" class="alert alert-light border mb-1 py-1 px-2 d-none">กำลังโหลดรายละเอียด...</div>
                <div id="ticketsCancelDetailSummary"></div>
                <div class="table-responsive mt-1">
                    <div id="ticketsCancelDetailTableWrapper">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="thead-light">
                        <tr>
                            <th class="text-center">ประเภท</th>
                            <th class="text-center">เลข</th>
                            <th class="text-right">ยอดแทง</th>
                            <th class="text-right">ส่วนลด</th>
                            <th class="text-right">ยอดรับ</th>
                            <th class="text-right">อัตราจ่าย</th>
                            <th class="text-right">ยอดถูก</th>
                        </tr>
                        </thead>
                        <tbody id="ticketsCancelDetailItemsBody">
                        <tr>
                            <td colspan="7" class="text-center text-muted">ยังไม่มีข้อมูล</td>
                        </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
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
                $('#ticketsCancelDetailItemsBody').html('<tr><td colspan="7" class="text-center text-muted">กำลังโหลด...</td></tr>');

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
                        const winAmountClass = isPositive(ticket.total_win_amount) ? 'ticket-detail-value is-danger' : 'ticket-detail-value';

                        $('#ticketsCancelDetailSummary').html(
                            `<div class="row mx-n1">
                                <div class="col-6 px-1 mb-1"><div class="ticket-detail-card"><span class="ticket-detail-label">เลขโพย</span><span class="ticket-detail-value">${escapeHtml(ticket.id || '-')}</span></div></div>
                                <div class="col-6 px-1 mb-1"><div class="ticket-detail-card"><span class="ticket-detail-label">สถานะ</span><span class="ticket-detail-value">${escapeHtml(ticket.status_label || '-')}</span></div></div>
                                <div class="col-6 px-1 mb-1"><div class="ticket-detail-card"><span class="ticket-detail-label">วันงวด</span><span class="ticket-detail-value">${escapeHtml(ticket.draw_date || '-')}</span></div></div>
                                <div class="col-6 px-1 mb-1"><div class="ticket-detail-card"><span class="ticket-detail-label">ผู้ยกเลิก</span><span class="ticket-detail-value">${escapeHtml(ticket.cancelled_by_name || '-')}</span></div></div>
                                <div class="col-12 px-1 mb-1"><div class="ticket-detail-card"><span class="ticket-detail-label">สมาชิก</span><span class="ticket-detail-value">${escapeHtml(ticket.member_display || '-')}</span></div></div>
                                <div class="col-12 px-1 mb-1"><div class="ticket-detail-card"><span class="ticket-detail-label">ตลาด</span><span class="ticket-detail-value">${escapeHtml(ticket.market_name || '-')}</span></div></div>
                                <div class="col-12 px-1 mb-1"><div class="ticket-detail-card"><span class="ticket-detail-label">แพกเกจ</span><span class="ticket-detail-value">${escapeHtml(ticket.packages || '-')}</span></div></div>
                                <div class="col-4 px-1 mb-1"><div class="ticket-detail-card text-right"><span class="ticket-detail-label">ยอดแทง</span><span class="ticket-detail-value">${formatMoney(ticket.total_bet_amount)}</span></div></div>
                                <div class="col-4 px-1 mb-1"><div class="ticket-detail-card text-right"><span class="ticket-detail-label">ส่วนลด</span><span class="ticket-detail-value">${formatMoney(ticket.total_discount_amount)}</span></div></div>
                                <div class="col-4 px-1 mb-1"><div class="ticket-detail-card text-right"><span class="ticket-detail-label">ยอดรับ</span><span class="ticket-detail-value">${formatMoney(ticket.total_net_amount)}</span></div></div>
                                <div class="col-12 px-1 mb-1"><div class="ticket-detail-card text-right"><span class="ticket-detail-label">ยอดถูก</span><span class="${winAmountClass}">${formatMoney(ticket.total_win_amount)}</span></div></div>
                                <div class="col-12 px-1 mb-0"><div class="ticket-detail-card"><span class="ticket-detail-label">สาเหตุ</span><span class="ticket-detail-value">${escapeHtml(ticket.reason || '-')}</span></div></div>
                            </div>`
                        );

                        if (items.length === 0) {
                            $('#ticketsCancelDetailItemsBody').html('<tr><td colspan="7" class="text-center text-muted">ไม่พบรายการย่อยในโพย</td></tr>');
                            return;
                        }

                        const rowsHtml = items.map((item) => {
                            const itemWinClass = isPositive(item.win_amount) ? 'text-right text-danger font-weight-bold' : 'text-right';
                            return `<tr>
                                <td class="text-center">${escapeHtml(item.bet_type_label || item.bet_type || '-')}</td>
                                <td class="text-center">${escapeHtml(item.number || '-')}</td>
                                <td class="text-right">${formatMoney(item.amount)}</td>
                                <td class="text-right">${formatMoney(item.discount_amount)}</td>
                                <td class="text-right">${formatMoney(item.net_amount)}</td>
                                <td class="text-right">${formatMoney(item.payout)}</td>
                                <td class="${itemWinClass}">${formatMoney(item.win_amount)}</td>
                            </tr>`;
                        }).join('');

                        $('#ticketsCancelDetailItemsBody').html(rowsHtml);
                    }).catch(() => {
                        $('#ticketsCancelDetailSummary').html('<div class="alert alert-danger mb-2">โหลดรายละเอียดโพยไม่สำเร็จ</div>');
                        $('#ticketsCancelDetailItemsBody').html('<tr><td colspan="7" class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</td></tr>');
                    }).then(() => {
                        $('#ticketsCancelDetailLoading').addClass('d-none');
                    });

                    return;
                }

                $('#ticketsCancelDetailLoading').addClass('d-none');
                $('#ticketsCancelDetailSummary').html('<div class="alert alert-danger mb-2">ไม่พบ HTTP client (axios)</div>');
                $('#ticketsCancelDetailItemsBody').html('<tr><td colspan="7" class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</td></tr>');
            });
        });
    </script>
@endpush
