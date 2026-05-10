<div class="table-responsive">
    {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], true) !!}
</div>

@push('scripts')
    @include('admin::layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    <script>
        $(function () {
            const detailEndpointTemplate = @json(route('admin.lotto.result_corrections.show', ['id' => '__ID__']));
            const retryEndpointTemplate = @json(route('admin.lotto.result_corrections.retry_debit', ['id' => '__ID__']));
            let activeCorrectionId = 0;
            let activeCanRetryDebit = false;

            const renderMoney = function (value) {
                const numberValue = Number(value || 0);
                return numberValue.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };

            const renderSummary = function (summary) {
                $('#rcSummaryDeducted').text(summary && summary.deducted_count ? summary.deducted_count : 0);
                $('#rcSummaryRemaining').text(summary && summary.remaining_count ? summary.remaining_count : 0);
                $('#rcSummaryCompleted').text(summary && summary.completed_count ? summary.completed_count : 0);
            };

            const renderItems = function (items) {
                const $tbody = $('#rcDetailBody');
                $tbody.empty();

                if (!Array.isArray(items) || items.length === 0) {
                    $tbody.append('<tr><td colspan="11" class="text-center text-muted">ไม่พบข้อมูล</td></tr>');
                    return;
                }

                items.forEach(function (item) {
                    const remaining = Number(item.reverse_remaining_amount || 0);
                    const remainingClass = remaining > 0 ? 'text-danger font-weight-bold' : 'text-success';
                    const canRetryRow = activeCanRetryDebit && remaining > 0;
                    const actionCell = canRetryRow
                        ? '<button type="button" class="btn btn-outline-danger btn-xs js-retry-remaining-member" data-member-id="' + (item.member_id || 0) + '"><i class="fa fa-refresh"></i> หักคืนค้าง</button>'
                        : '<span class="text-muted">-</span>';

                    $tbody.append(
                        '<tr>'
                        + '<td>' + (item.id || 0) + '</td>'
                        + '<td>' + (item.member_username || ('MEM-' + (item.member_id || 0))) + '</td>'
                        + '<td>' + (item.ticket_count || 0) + '</td>'
                        + '<td class="text-right">' + renderMoney(item.initial_member_balance) + '</td>'
                        + '<td class="text-right">' + renderMoney(item.reverse_required_amount) + '</td>'
                        + '<td class="text-right">' + renderMoney(item.reverse_debited_amount) + '</td>'
                        + '<td class="text-right ' + remainingClass + '">' + renderMoney(item.reverse_remaining_amount) + '</td>'
                        + '<td class="text-right">' + renderMoney(item.new_credit_amount) + '</td>'
                        + '<td class="text-right">' + renderMoney(item.latest_member_balance) + '</td>'
                        + '<td>' + (item.status || '-') + '</td>'
                        + '<td class="text-center">' + actionCell + '</td>'
                        + '</tr>'
                    );
                });
            };

            const toggleRetryAllButton = function (summary) {
                const hasRemaining = Number(summary && summary.remaining_count ? summary.remaining_count : 0) > 0;
                const shouldShow = activeCanRetryDebit && hasRemaining && activeCorrectionId > 0;
                $('#rcRetryAllRemainingBtn').toggleClass('d-none', !shouldShow);
            };

            const reloadCorrectionDetail = async function () {
                if (!activeCorrectionId) {
                    return;
                }

                const detailUrl = detailEndpointTemplate.replace('__ID__', String(activeCorrectionId));
                const response = await axios.get(detailUrl);
                const data = response && response.data && response.data.data ? response.data.data : null;

                if (!data) {
                    $('#rcDetailBody').html('<tr><td colspan="11" class="text-center text-danger">โหลดข้อมูลไม่สำเร็จ</td></tr>');
                    return;
                }

                activeCanRetryDebit = Boolean(data.can_retry_debit);
                renderSummary(data.summary || {});
                renderItems(data.items || []);
                toggleRetryAllButton(data.summary || {});
            };

            $(document).off('click.resultCorrectionDetail', '.js-result-correction-detail').on('click.resultCorrectionDetail', '.js-result-correction-detail', async function () {
                const correctionId = Number($(this).data('correction-id') || 0);
                if (!correctionId) {
                    return;
                }

                activeCorrectionId = correctionId;
                activeCanRetryDebit = false;
                $('#resultCorrectionDetailTitle').text('รายละเอียดการแก้ไขผลหวย #' + correctionId);
                $('#rcDetailBody').html('<tr><td colspan="11" class="text-center text-muted">กำลังโหลด...</td></tr>');
                renderSummary({ deducted_count: 0, remaining_count: 0, completed_count: 0 });
                $('#rcRetryAllRemainingBtn').addClass('d-none');
                $('#resultCorrectionDetailModal').modal('show');

                try {
                    await reloadCorrectionDetail();
                } catch (error) {
                    $('#rcDetailBody').html('<tr><td colspan="11" class="text-center text-danger">โหลดข้อมูลไม่สำเร็จ</td></tr>');
                }
            });

            const triggerRetry = async function (payload) {
                if (!activeCorrectionId) {
                    return;
                }

                const retryUrl = retryEndpointTemplate.replace('__ID__', String(activeCorrectionId));
                await axios.post(retryUrl, payload || {});
                await reloadCorrectionDetail();
                if (window.LaravelDataTables && window.LaravelDataTables.dataTableBuilder) {
                    window.LaravelDataTables.dataTableBuilder.ajax.reload(null, false);
                }
            };

            $('#rcRetryAllRemainingBtn').off('click').on('click', async function () {
                const $button = $(this);
                $button.prop('disabled', true);
                try {
                    await triggerRetry({});
                } catch (error) {
                    alert('หักคืนยอดคงค้างทั้งหมดไม่สำเร็จ');
                } finally {
                    $button.prop('disabled', false);
                }
            });

            $(document).off('click.resultCorrectionRetryMember', '.js-retry-remaining-member').on('click.resultCorrectionRetryMember', '.js-retry-remaining-member', async function () {
                const memberId = Number($(this).data('member-id') || 0);
                if (!memberId) {
                    return;
                }

                const $button = $(this);
                $button.prop('disabled', true);
                try {
                    await triggerRetry({ member_id: memberId });
                } catch (error) {
                    alert('หักคืนยอดคงค้างรายคนไม่สำเร็จ');
                } finally {
                    $button.prop('disabled', false);
                }
            });
        });
    </script>
@endpush
