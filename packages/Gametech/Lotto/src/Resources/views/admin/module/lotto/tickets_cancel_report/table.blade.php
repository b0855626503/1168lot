@section('css')
    @include('admin::layouts.datatables_css')
@endsection
{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}
<div class="modal fade" id="ticketsCancelDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title">รายละเอียดโพย</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-sm">
                <div id="ticketsCancelDetailLoading" class="alert alert-light border mb-2 d-none">กำลังโหลดรายละเอียด...</div>
                <div id="ticketsCancelDetailSummary"></div>
                <div class="table-responsive mt-2">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="thead-light">
                        <tr>
                            <th class="text-center">ประเภท</th>
                            <th class="text-center">เลข</th>
                            <th class="text-left">แพกเกจ</th>
                            <th class="text-right">ยอดแทง</th>
                            <th class="text-right">ส่วนลด</th>
                            <th class="text-right">ยอดรับ</th>
                            <th class="text-right">อัตราจ่าย</th>
                            <th class="text-right">ยอดถูก</th>
                            <th class="text-center">ผล</th>
                        </tr>
                        </thead>
                        <tbody id="ticketsCancelDetailItemsBody">
                        <tr>
                            <td colspan="9" class="text-center text-muted">ยังไม่มีข้อมูล</td>
                        </tr>
                        </tbody>
                    </table>
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

            $(document).off('click.ticketsCancelDetail', '.js-tickets-cancel-detail').on('click.ticketsCancelDetail', '.js-tickets-cancel-detail', function () {
                const ticketId = Number($(this).data('ticket-id') || 0);
                if (!ticketId) {
                    return;
                }

                $('#ticketsCancelDetailModal').modal('show');
                $('#ticketsCancelDetailLoading').removeClass('d-none');
                $('#ticketsCancelDetailSummary').empty();
                $('#ticketsCancelDetailItemsBody').html('<tr><td colspan="9" class="text-center text-muted">กำลังโหลด...</td></tr>');

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

                        $('#ticketsCancelDetailSummary').html(
                            `<div class="row">
                                <div class="col-md-6 mb-2"><strong>เลขโพย:</strong> ${escapeHtml(ticket.id || '-')}</div>
                                <div class="col-md-6 mb-2"><strong>สมาชิก:</strong> ${escapeHtml(ticket.member_display || '-')}</div>
                                <div class="col-md-6 mb-2"><strong>ตลาด:</strong> ${escapeHtml(ticket.market_name || '-')}</div>
                                <div class="col-md-6 mb-2"><strong>วันงวด:</strong> ${escapeHtml(ticket.draw_date || '-')}</div>
                                <div class="col-md-6 mb-2"><strong>สถานะ:</strong> ${escapeHtml(ticket.status_label || '-')}</div>
                                <div class="col-md-6 mb-2"><strong>ผู้ยกเลิก:</strong> ${escapeHtml(ticket.cancelled_by_name || '-')}</div>
                                <div class="col-md-6 mb-2"><strong>ยอดแทง:</strong> ${formatMoney(ticket.total_bet_amount)}</div>
                                <div class="col-md-6 mb-2"><strong>ส่วนลด:</strong> ${formatMoney(ticket.total_discount_amount)}</div>
                                <div class="col-md-6 mb-2"><strong>ยอดรับ:</strong> ${formatMoney(ticket.total_net_amount)}</div>
                                <div class="col-md-6 mb-2"><strong>ยอดถูก:</strong> ${formatMoney(ticket.total_win_amount)}</div>
                                <div class="col-md-12 mb-2"><strong>สาเหตุ:</strong> ${escapeHtml(ticket.reason || '-')}</div>
                            </div>`
                        );

                        if (items.length === 0) {
                            $('#ticketsCancelDetailItemsBody').html('<tr><td colspan="9" class="text-center text-muted">ไม่พบรายการย่อยในโพย</td></tr>');
                            return;
                        }

                        const rowsHtml = items.map((item) => {
                            const resultStatus = item.result_status ? String(item.result_status) : '-';
                            return `<tr>
                                <td class="text-center">${escapeHtml(item.bet_type_label || item.bet_type || '-')}</td>
                                <td class="text-center">${escapeHtml(item.number || '-')}</td>
                                <td class="text-left">${escapeHtml(item.package_name || '-')}</td>
                                <td class="text-right">${formatMoney(item.amount)}</td>
                                <td class="text-right">${formatMoney(item.discount_amount)}</td>
                                <td class="text-right">${formatMoney(item.net_amount)}</td>
                                <td class="text-right">${formatMoney(item.payout)}</td>
                                <td class="text-right">${formatMoney(item.win_amount)}</td>
                                <td class="text-center">${escapeHtml(resultStatus)}</td>
                            </tr>`;
                        }).join('');

                        $('#ticketsCancelDetailItemsBody').html(rowsHtml);
                    }).catch(() => {
                        $('#ticketsCancelDetailSummary').html('<div class="alert alert-danger mb-2">โหลดรายละเอียดโพยไม่สำเร็จ</div>');
                        $('#ticketsCancelDetailItemsBody').html('<tr><td colspan="9" class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</td></tr>');
                    }).then(() => {
                        $('#ticketsCancelDetailLoading').addClass('d-none');
                    });

                    return;
                }

                $('#ticketsCancelDetailLoading').addClass('d-none');
                $('#ticketsCancelDetailSummary').html('<div class="alert alert-danger mb-2">ไม่พบ HTTP client (axios)</div>');
                $('#ticketsCancelDetailItemsBody').html('<tr><td colspan="9" class="text-center text-muted">ไม่สามารถโหลดข้อมูลได้</td></tr>');
            });
        });
    </script>
@endpush
