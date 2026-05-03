<b-modal ref="addedit" id="addedit" centered size="xl" title="รายละเอียดโพย" :no-stacking="true"
         :no-close-on-backdrop="true"
         ok-only
         ok-title="ปิด">
    {{--
        Wrapper class "lotto-ticket-detail-modal" is required for CSS double-scoping.
        All .tcd-* styles in table.blade.php are scoped as:
          #addedit .lotto-ticket-detail-modal .tcd-*
        so they cannot leak to other elements or modals.
    --}}
    <div v-if="ticket" class="lotto-ticket-detail-modal">
        {{-- Summary header bar --}}
        <div class="tcd-header">
            <div class="d-flex flex-wrap align-items-start">
                <div class="mr-2">
                    <span class="tcd-header-id">#@{{ ticket.id }}</span>
                    <b-badge :variant="statusBadgeVariant">@{{ statusLabel }}</b-badge>
                </div>
                <div class="tcd-header-meta flex-grow-1 mt-1 mt-sm-0">
                    <span>วันงวด: @{{ drawDate }}</span>
                    <span class="sep">|</span>
                    <span>สมาชิก: @{{ memberDisplay }}</span>
                    <span class="sep">|</span>
                    {{--
                        TODO: หวยยี่กี่ควรแสดง badge รอบ แต่ loadData() ปัจจุบันส่ง draw.market
                        เป็นชื่อตลาดธรรมดา ไม่ผ่าน LottoMarketDisplayFormatter::formatPlain()
                        จึงไม่มี "(รอบ N)" ใน string — ควรเพิ่ม draw.yeekee_round_no ใน
                        LottoTicketController::loadData() แล้ว bind badge จาก field นั้นแทน
                        computed marketRoundNo ตอนนี้ทำ fallback parse ไว้รองรับอนาคต
                    --}}
                    <span>ตลาด: @{{ marketDisplayName
                        }}<template v-if="hasMarketRound"
                        > <b-badge variant="info" class="tcd-round-badge">รอบ @{{ marketRoundNo }}</b-badge></template></span>
                </div>
            </div>
        </div>

        {{-- Info + Amounts layout --}}
        <div class="row">
            <div class="col-lg-5">
                <div class="tcd-section">
                    <div class="tcd-section-title">ข้อมูลโพย / สมาชิก</div>
                    <div class="tcd-info-grid">
                        <div>
                            <span class="tcd-info-label">เลขโพย</span>
                            <span class="tcd-info-value">#@{{ ticket.id }}</span>
                        </div>
                        <div>
                            <span class="tcd-info-label">สถานะ</span>
                            <span class="tcd-info-value">
                                <b-badge :variant="statusBadgeVariant">@{{ statusLabel }}</b-badge>
                            </span>
                        </div>
                        <div>
                            <span class="tcd-info-label">วันงวด</span>
                            <span class="tcd-info-value">@{{ drawDate }}</span>
                        </div>
                        <div>
                            <span class="tcd-info-label">งวด #</span>
                            <span class="tcd-info-value">@{{ drawId }}</span>
                        </div>
                        <div class="full">
                            <span class="tcd-info-label">สมาชิก</span>
                            <span class="tcd-info-value">@{{ memberDisplay }}</span>
                        </div>
                        <div class="full">
                            <span class="tcd-info-label">ตลาด / หวย</span>
                            <span class="tcd-info-value">
                                @{{ marketDisplayName
                                }}<template v-if="hasMarketRound"
                                > <b-badge variant="info" class="tcd-round-badge">รอบ @{{ marketRoundNo }}</b-badge></template>
                            </span>
                        </div>
                        <template v-if="ticket.cancelled_by_name || ticket.cancelled_at">
                            <div v-if="ticket.cancelled_by_name">
                                <span class="tcd-info-label">ผู้ยกเลิก</span>
                                <span class="tcd-info-value">@{{ ticket.cancelled_by_name }}</span>
                            </div>
                            <div v-if="ticket.cancelled_at">
                                <span class="tcd-info-label">วันที่ยกเลิก</span>
                                <span class="tcd-info-value">@{{ ticket.cancelled_at }}</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                {{-- Amount summary cards --}}
                <div class="tcd-section">
                    <div class="tcd-section-title">ยอดเงิน</div>
                    <div class="row mx-n1">
                        <div class="col-6 col-md-3 px-1 mb-2">
                            <div class="tcd-amount-card">
                                <span class="tcd-amount-label">ยอดแทง</span>
                                <span class="tcd-amount-value">@{{ formatMoney(ticket.total_bet_amount) }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 px-1 mb-2">
                            <div class="tcd-amount-card">
                                <span class="tcd-amount-label">ส่วนลด</span>
                                <span class="tcd-amount-value">@{{ formatMoney(ticket.total_discount_amount) }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 px-1 mb-2">
                            <div class="tcd-amount-card">
                                <span class="tcd-amount-label">ยอดรับ</span>
                                <span class="tcd-amount-value">@{{ formatMoney(ticket.total_net_amount) }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 px-1 mb-2">
                            <div class="tcd-amount-card" :class="hasWin ? 'is-win' : ''">
                                <span class="tcd-amount-label">ยอดถูก</span>
                                <span class="tcd-amount-value" :class="hasWin ? 'is-danger' : ''">@{{ formatMoney(ticket.total_win_amount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Reason / notes --}}
                <div class="tcd-section">
                    <div class="tcd-section-title">หมายเหตุ / สาเหตุ</div>
                    <div class="tcd-reason-text" :class="reasonEmpty ? 'is-empty' : ''">@{{ reasonText }}</div>
                </div>
            </div>
        </div>

        {{-- Items table — 10 columns preserved from original --}}
        <div class="tcd-section">
            <div class="tcd-section-title">
                รายละเอียดเลขแทง
                <span v-if="ticket.items && ticket.items.length > 0" class="badge badge-light tcd-count-badge">
                    @{{ ticket.items.length }} รายการ
                </span>
            </div>
            <div class="tcd-table-wrapper">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light">
                    <tr>
                        <th>ประเภท</th>
                        <th class="text-center">เลข</th>
                        <th class="text-right">ยอดแทง</th>
                        <th class="text-right">อัตราจ่าย</th>
                        <th class="text-right">ส่วนลด(%)</th>
                        <th class="text-right">ส่วนลด(฿)</th>
                        <th class="text-right">จ่ายจริง</th>
                        <th class="text-right">ถูก(อ้างอิง)</th>
                        <th class="text-center">ผล</th>
                        <th class="text-right">ยอดถูก</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(item, index) in ticket.items" :key="index"
                        :class="{ 'win-row': Number(item.win_amount || 0) > 0 }">
                        <td>@{{ item.bet_type_label || item.bet_type }}</td>
                        <td class="text-center col-number">@{{ item.number }}</td>
                        <td class="text-right">@{{ formatMoney(item.amount) }}</td>
                        <td class="text-right">@{{ formatMoney(item.payout_at_time) }}</td>
                        <td class="text-right">@{{ formatMoney(item.discount_percent_at_time) }}</td>
                        <td class="text-right">@{{ formatMoney(item.discount_amount_at_time) }}</td>
                        <td class="text-right">@{{ formatMoney(item.payable_amount_at_time) }}</td>
                        <td class="text-right">@{{ formatMoney(item.potential_win_amount_at_time) }}</td>
                        <td class="text-center">@{{ item.result_status || '-' }}</td>
                        <td :class="Number(item.win_amount || 0) > 0 ? 'text-right text-danger font-weight-bold' : 'text-right'">
                            @{{ formatMoney(item.win_amount) }}
                        </td>
                    </tr>
                    <tr v-if="!ticket.items || ticket.items.length === 0">
                        <td colspan="10" class="text-center text-muted py-3">ไม่พบรายการย่อยในโพย</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</b-modal>
<b-modal ref="cancelTicketModal" id="cancelTicketModal" centered size="md" title="ยกเลิกโพย"
         :no-stacking="true"
         :no-close-on-backdrop="true"
         :ok-disabled="cancelSubmitting || !cancelForm.reason.trim()"
         :busy="cancelSubmitting"
         ok-title="ยืนยันยกเลิก"
         cancel-title="ปิด"
         @ok.prevent="submitCancelTicket">
    <div>
        <div class="mb-3" v-if="cancelForm.id">
            <div><strong>เลขโพย:</strong> #@{{ cancelForm.id }}</div>
            <div><strong>สมาชิก:</strong> @{{ cancelForm.member_name }}</div>
            <div><strong>งวด:</strong> @{{ cancelForm.draw_label }}</div>
            <div><strong>ยอดคืน:</strong> @{{ formatMoney(cancelForm.total_net_amount) }}</div>
        </div>
        <b-form-group label="สาเหตุการยกเลิก" label-for="cancel-ticket-reason">
            <b-form-textarea
                id="cancel-ticket-reason"
                v-model="cancelForm.reason"
                rows="3"
                max-rows="6"
                placeholder="ระบุสาเหตุที่ต้องยกเลิกโพย"
            ></b-form-textarea>
            <small class="text-muted">บังคับกรอก และจะถูกเก็บไว้ในรายการโพยที่ถูกยกเลิก</small>
        </b-form-group>
    </div>
</b-modal>
@push('scripts')
    <script type="module">
        const loadDataRoute = @json(route($loadDataRouteName ?? 'admin.lotto.tickets.loaddata'));
        const cancelRouteTemplate = @json(route('admin.lotto.tickets.cancel', ['id' => '__ID__']));

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    ticket: null,
                    cancelSubmitting: false,
                    cancelForm: {
                        id: null,
                        reason: '',
                        member_name: '',
                        draw_label: '',
                        total_net_amount: 0,
                    },
                };
            },
            computed: {
                statusLabel() {
                    const map = { active: 'รอผล', cancelled: 'ยกเลิก', resulted: 'ตัดสินแล้ว', won: 'ถูกรางวัล' };
                    return map[this.ticket?.status] || this.ticket?.status || '-';
                },
                statusBadgeVariant() {
                    const map = { active: 'warning', cancelled: 'secondary', resulted: 'primary', won: 'success' };
                    return map[this.ticket?.status] || 'light';
                },
                memberDisplay() {
                    const name = String(this.ticket?.member_name || '').trim();
                    const id = this.ticket?.member_id;
                    if (name && id) {
                        return `${name} (${id})`;
                    }

                    return name || (id ? `MEM-${id}` : '-');
                },
                drawDate() {
                    return String(this.ticket?.draw?.date || '').trim() || '-';
                },
                drawId() {
                    const id = this.ticket?.draw?.id;
                    return id != null ? id : '-';
                },
                /**
                 * Raw market name string from the API response.
                 * loadData() currently returns draw.market as a plain name (no round suffix),
                 * because LottoTicketController::loadData() does not use
                 * LottoMarketDisplayFormatter::formatPlain(). The computed below parses
                 * "(รอบ N)" defensively so it works automatically once the backend is updated.
                 *
                 * TODO: add draw.yeekee_round_no (int|null) to LottoTicketController::loadData()
                 * and bind the badge from that field instead of relying on string parsing.
                 */
                marketRawText() {
                    return String(this.ticket?.draw?.market || '').trim();
                },
                marketDisplayName() {
                    if (!this.marketRawText) {
                        return '-';
                    }

                    const m = this.marketRawText.match(/^(.*?)\s*\(รอบ\s*\d+\)\s*$/);
                    return m ? m[1].trim() || '-' : this.marketRawText;
                },
                marketRoundNo() {
                    if (!this.marketRawText) {
                        return null;
                    }

                    const m = this.marketRawText.match(/\(รอบ\s*(\d+)\)\s*$/);
                    return m ? m[1] : null;
                },
                hasMarketRound() {
                    return this.marketRoundNo !== null;
                },
                hasWin() {
                    return Number(this.ticket?.total_win_amount || 0) > 0;
                },
                reasonEmpty() {
                    const r = String(this.ticket?.reason || '').trim();
                    return r === '' || r === '-';
                },
                reasonText() {
                    const r = String(this.ticket?.reason || '').trim();
                    return r !== '' && r !== '-' ? r : '-';
                },
            },
            methods: {
                async editModal(id) {
                    const response = await axios.post(loadDataRoute, { id });
                    this.ticket = response.data.data;
                    this.$refs.addedit.show();
                },
                async openCancelTicketModal(id) {
                    try {
                        const response = await axios.post(loadDataRoute, { id });
                        const data = response?.data?.data || null;

                        if (!data) {
                            await this.showSubmitErrorModal(null, 'ไม่พบข้อมูลโพยดังกล่าว');
                            return;
                        }

                        if (!data.can_cancel) {
                            await this.showSubmitErrorModal(null, 'โพยนี้ไม่สามารถยกเลิกได้');
                            return;
                        }

                        this.cancelForm = {
                            id: Number(data.id || 0),
                            reason: '',
                            member_name: String(data.member_name || '-'),
                            draw_label: [String(data.draw?.market || '').trim(), String(data.draw?.date || '').trim()].filter(Boolean).join(' / '),
                            total_net_amount: Number(data.total_net_amount || 0),
                        };
                        this.$refs.cancelTicketModal.show();
                    } catch (error) {
                        await this.showSubmitErrorModal(error, 'โหลดข้อมูลโพยไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
                    }
                },
                resolveCancelRoute(id) {
                    return cancelRouteTemplate.replace('__ID__', String(id));
                },
                redrawTicketTable() {
                    const dataTable = window.LaravelDataTables?.lottoTicketsTable;
                    if (dataTable) {
                        dataTable.draw(false);
                    }
                },
                resolveApiErrorMessage(error, fallbackMessage) {
                    const backendMessage = error?.response?.data?.message;
                    if (typeof backendMessage === 'string' && backendMessage.trim() !== '') {
                        return backendMessage;
                    }

                    return fallbackMessage;
                },
                async showSubmitErrorModal(error, fallbackMessage = 'บันทึกไม่สำเร็จ กรุณาลองใหม่อีกครั้ง') {
                    const message = this.resolveApiErrorMessage(error, fallbackMessage);
                    await this.$bvModal.msgBoxOk(message, {
                        title: 'เกิดข้อผิดพลาด',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        centered: true,
                    });
                },
                async submitCancelTicket() {
                    const reason = String(this.cancelForm.reason || '').trim();
                    if (reason === '') {
                        await this.showSubmitErrorModal(null, 'กรุณาระบุสาเหตุการยกเลิกโพย');
                        return;
                    }

                    this.cancelSubmitting = true;
                    try {
                        await axios.post(this.resolveCancelRoute(this.cancelForm.id), { reason });
                        this.$refs.cancelTicketModal.hide();
                        if (this.ticket && Number(this.ticket.id || 0) === Number(this.cancelForm.id || 0)) {
                            this.ticket = null;
                            this.$refs.addedit.hide();
                        }
                        this.redrawTicketTable();
                        await this.$bvModal.msgBoxOk('ยกเลิกโพยสำเร็จ', {
                            title: 'สำเร็จ',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'success',
                            centered: true,
                        });
                    } catch (error) {
                        await this.showSubmitErrorModal(error, 'ยกเลิกโพยไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
                    } finally {
                        this.cancelSubmitting = false;
                    }
                },
                formatMoney(value) {
                    return Number(value || 0).toLocaleString('th-TH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },
            },
        });

        window.editModal = function (id) { window.app.editModal(id); };
        window.cancelTicketModal = function (id) { window.app.openCancelTicketModal(id); };
    </script>
@endpush
