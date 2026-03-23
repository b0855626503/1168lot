<b-modal ref="addedit" id="addedit" centered size="md" :title="modalTitle" :no-stacking="true"
         :no-close-on-backdrop="true"
         @shown="onModalShown"
         @hidden="onModalHidden"
         :hide-footer="true">
    <b-form v-if="show" @submit.prevent="formmethod === 'settle' ? submitSettleForm() : submitDrawForm()">
        <template v-if="formmethod !== 'settle'">
            <b-row>
                <b-col cols="12" md="6">
                    <b-form-group label="ตลาด:" label-for="market_id">
                        <select id="market_id"
                                ref="marketSelect"
                                class="form-control form-control-sm"
                                :value="formaddedit.market_id ? String(formaddedit.market_id) : ''"
                                @change="onNativeMarketChange"
                                required>
                            <option value="">-- เลือกรายการหวย --</option>
                            <optgroup v-for="group in markets" :key="group.label" :label="group.label">
                                <option v-for="option in group.options"
                                        :key="option.value"
                                        :value="String(option.value)">
                                    @{{ option.text }}
                                </option>
                            </optgroup>
                        </select>
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="6">
                    <b-form-group label="วันงวด:" label-for="draw_date">
                        <b-form-input id="draw_date" v-model="formaddedit.draw_date" type="date" size="sm" required></b-form-input>
                    </b-form-group>
                </b-col>
            </b-row>
            <b-row>
                <b-col cols="12" md="6">
                    <b-form-group label="เปิดรับ:" label-for="open_at">
                        <b-form-input id="open_at" v-model="formaddedit.open_at" type="datetime-local" size="sm" required></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="6">
                    <b-form-group label="ปิดรับ:" label-for="close_at">
                        <b-form-input id="close_at" v-model="formaddedit.close_at" type="datetime-local" size="sm" required></b-form-input>
                    </b-form-group>
                </b-col>
            </b-row>
            <b-form-group label="เวลาออกผล (คาดการณ์):" label-for="result_at">
                <b-form-input id="result_at" v-model="formaddedit.result_at" type="datetime-local" size="sm"></b-form-input>
            </b-form-group>

            <div class="d-flex justify-content-end">
                <b-button type="submit" variant="primary" size="sm">บันทึก</b-button>
            </div>
        </template>

        <template v-else>
            <div class="mb-2 text-muted">
                <div>ตลาด: <strong>@{{ currentDraw.market_name || '-' }}</strong></div>
                <div>วันงวด: <strong>@{{ currentDraw.draw_date || '-' }}</strong></div>
                <div>สถานะ: <strong>@{{ currentDraw.status_label || '-' }}</strong></div>
            </div>

            <b-row>
                <b-col cols="12" md="8">
                    <b-form-group label="รางวัลที่ 1 (6 หลัก)" label-for="result_first_prize">
                        <b-form-input id="result_first_prize" v-model="formaddedit.result_number.first_prize" type="text" maxlength="6" size="sm" placeholder="เช่น 123456"></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="4">
                    <b-form-group label="เลขท้าย 2 ตัว" label-for="result_last_2_digits">
                        <b-form-input id="result_last_2_digits" v-model="formaddedit.result_number.last_2_digits" type="text" maxlength="2" size="sm" placeholder="เช่น 89"></b-form-input>
                    </b-form-group>
                </b-col>
            </b-row>

            <b-form-group label="ประกาศผลเมื่อ:" label-for="settle_result_at">
                <b-form-input id="settle_result_at" v-model="formaddedit.result_at" type="datetime-local" size="sm"></b-form-input>
            </b-form-group>

            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted" v-if="!canCalculate">กรอกรางวัลที่ 1 และเลขท้าย 2 ตัวให้ครบก่อน จึงจะแสดงปุ่มคำนวณ</small>
                <b-button v-if="canCalculate" type="submit" variant="success" size="sm">
                    คำนวณรางวัล
                </b-button>
            </div>
        </template>
    </b-form>
</b-modal>

<b-modal ref="blockedNumbersModal" id="blockedNumbersModal" centered size="xl" title="รายการเลขอั้นในงวด" ok-only ok-title="ปิด" modal-class="lotto-blocked-summary-modal">
    <div class="row no-gutters mb-2 lotto-summary-row">
        <div class="col-4 lotto-blocked-summary-item"><span>งวด :</span><strong>@{{ blockedNumbersData.draw.draw_date || '-' }}</strong></div>
        <div class="col-4 lotto-blocked-summary-item"><span>ตลาด :</span><strong>@{{ blockedNumbersData.draw.market_name || '-' }}</strong></div>
        <div class="col-4 lotto-blocked-summary-item"><span>จำนวนเลขอั้น :</span><strong class="lotto-summary-value-primary">@{{ blockedNumbersData.count || 0 }}</strong></div>
    </div>
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
        <div class="text-muted small mb-1">แสดง @{{ filteredBlockedNumbersItems.length }} / @{{ blockedNumbersData.count || 0 }} รายการ</div>
        <div class="lotto-blocked-search mb-1">
            <b-input-group size="sm">
                <b-form-input
                    id="blocked-number-search"
                    v-model.trim="blockedSearchKeyword"
                    placeholder="ค้นหาเลข / ประเภท / หมายเหตุ"></b-form-input>
            </b-input-group>
        </div>
    </div>
    <div class="table-responsive member-list-scroll">
        <b-table
            class="mb-0 member-list-table lotto-blocked-summary-table"
            striped
            hover
            small
            outlined
            show-empty
            head-variant="light"
            :items="filteredBlockedNumbersItems"
            :fields="blockedNumbersFields"
            empty-text="ไม่พบรายการเลขอั้นในงวดนี้">
            <template #cell(index)="row">
                <div class="text-center">@{{ row.index + 1 }}</div>
            </template>
            <template #cell(bet_type_label)="row">
                @{{ row.item.bet_type_label || row.item.bet_type || '-' }}
            </template>
            <template #cell(number)="row">
                <div class="text-center">@{{ row.item.number || '-' }}</div>
            </template>
            <template #cell(mode)="row">
                <div class="text-center">@{{ row.item.mode || '-' }}</div>
            </template>
            <template #cell(blocked_at)="row">
                <div class="text-center">@{{ row.item.blocked_at || '-' }}</div>
            </template>
            <template #cell(reason)="row">
                @{{ row.item.reason || '-' }}
            </template>
        </b-table>
    </div>
</b-modal>

<b-modal ref="ticketsSummaryModal" id="ticketsSummaryModal" centered size="xl" title="รายการแทงในงวด" ok-only ok-title="ปิด" modal-class="lotto-ticket-summary-modal">
    <div class="row no-gutters mb-2 lotto-summary-row">
        <div class="col-4 lotto-ticket-summary-item"><span>งวด :</span><strong>@{{ ticketsSummaryData.draw.draw_date || '-' }}</strong></div>
        <div class="col-4 lotto-ticket-summary-item"><span>ตลาด :</span><strong>@{{ ticketsSummaryData.draw.market_name || '-' }}</strong></div>
        <div class="col-4 lotto-ticket-summary-item"><span>จำนวนรายการแทง :</span><strong class="lotto-summary-value-primary">@{{ ticketsSummaryData.count || 0 }}</strong></div>
    </div>
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
        <div class="text-muted small mb-1">แสดง @{{ filteredTicketsSummaryItems.length }} / @{{ ticketsSummaryData.count || 0 }} รายการ</div>
        <div class="lotto-ticket-search mb-1">
            <b-input-group size="sm">
                <b-form-input
                    id="ticket-member-search"
                    v-model.trim="ticketsSearchKeyword"
                    placeholder="ค้นหา username หรือชื่อสมาชิก"></b-form-input>
            </b-input-group>
        </div>
    </div>

    <div class="table-responsive member-list-scroll">
        <b-table
            class="mb-0 member-list-table lotto-ticket-summary-table"
            striped
            hover
            small
            outlined
            show-empty
            head-variant="light"
            :items="filteredTicketsSummaryItems"
            :fields="ticketsSummaryFields"
            empty-text="ไม่พบรายการแทงในงวดนี้">
            <template #cell(id)="row">
                <div class="text-center">@{{ row.item.id || '-' }}</div>
            </template>
            <template #cell(member_username)="row">
                @{{ row.item.member_username || '-' }}
            </template>
            <template #cell(member_name)="row">
                @{{ row.item.member_name || '-' }}
            </template>
            <template #cell(bet_types)="row">
                @{{ row.item.bet_types || '-' }}
            </template>
            <template #cell(bet_numbers)="row">
                @{{ row.item.bet_numbers || '-' }}
            </template>
            <template #cell(total_amount)="row">
                <div class="text-right">@{{ formatMoney(row.item.total_amount) }}</div>
            </template>
            <template #cell(status)="row">
                <div class="text-center">@{{ row.item.status || '-' }}</div>
            </template>
            <template #cell(created_at)="row">
                <div class="text-center">@{{ row.item.created_at || '-' }}</div>
            </template>
        </b-table>
    </div>
</b-modal>

@push('css')
    <style>
        .member-list-table th,
        .member-list-table td {
            font-size: 12px;
            white-space: nowrap;
        }
        .member-list-scroll {
            flex: 1 1 auto;
            min-height: 0;
            max-height: 360px;
            overflow: auto;
            border: 1px solid #e9ecef;
            border-radius: 4px;
        }
        .lotto-blocked-summary-modal .modal-dialog {
            max-width: 1120px;
        }
        .lotto-blocked-summary-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 14px;
            white-space: nowrap;
            min-height: 32px;
        }
        .lotto-blocked-summary-item span {
            color: #334155;
            font-weight: 700;
        }
        .lotto-blocked-summary-item strong {
            color: #0f172a;
            font-size: 16px;
        }
        .lotto-blocked-search {
            min-width: 320px;
            max-width: 420px;
            width: 100%;
        }
        .lotto-blocked-summary-table .table {
            margin-bottom: 0;
        }
        .lotto-blocked-summary-table thead th {
            font-weight: 700;
            background: #eef1f5;
            border-top: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
            color: #374151;
        }
        .lotto-blocked-summary-table tbody td {
            vertical-align: middle;
        }
        .lotto-ticket-summary-modal .modal-dialog {
            max-width: 1220px;
        }
        .lotto-ticket-summary-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 14px;
            white-space: nowrap;
            min-height: 32px;
        }
        .lotto-ticket-summary-item span {
            color: #334155;
            font-weight: 700;
        }
        .lotto-ticket-summary-item strong {
            color: #0f172a;
            font-size: 16px;
        }
        .lotto-summary-row {
            border: 1px solid #dbe3ef;
            border-radius: 4px;
            background: linear-gradient(90deg, #f8fbff 0%, #f3f8ff 100%);
            padding: 4px 0;
        }
        .lotto-summary-value-primary {
            color: #0d6efd !important;
            font-weight: 800;
        }
        .lotto-ticket-search {
            min-width: 320px;
            max-width: 420px;
            width: 100%;
        }
        .lotto-ticket-summary-table .table {
            margin-bottom: 0;
        }
        .lotto-ticket-summary-table thead th {
            font-weight: 700;
            background: #eef1f5;
            border-top: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
            color: #374151;
        }
        .lotto-ticket-summary-table tbody td {
            vertical-align: middle;
        }
    </style>
@endpush

@push('scripts')
    <script type="module">
        const toDateTimeLocal = (value) => {
            if (!value) return '';
            return String(value).replace(' ', 'T').substring(0, 16);
        };

        const onlyDigits = (value) => String(value || '').replace(/\D+/g, '');

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: true,
                    code: null,
                    formmethod: 'add',
                    markets: @json($marketOptions ?? []),
                    formaddedit: {
                        market_id: null,
                        draw_date: '',
                        open_at: '',
                        close_at: '',
                        result_number: {
                            first_prize: '',
                            last_2_digits: '',
                        },
                        result_at: '',
                    },
                    currentDraw: {
                        market_name: '',
                        draw_date: '',
                        status_label: '',
                    },
                    blockedNumbersData: {
                        draw: {},
                        count: 0,
                        items: [],
                    },
                    blockedSearchKeyword: '',
                    blockedNumbersFields: [
                        { key: 'index', label: '#', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '60px' } },
                        { key: 'bet_type_label', label: 'ประเภท' },
                        { key: 'number', label: 'เลข', thClass: 'text-center', tdClass: 'text-center' },
                        { key: 'mode', label: 'โหมด', thClass: 'text-center', tdClass: 'text-center' },
                        { key: 'blocked_at', label: 'เวลาอั้น', thClass: 'text-center', tdClass: 'text-center' },
                        { key: 'reason', label: 'หมายเหตุ' },
                    ],
                    ticketsSummaryData: {
                        draw: {},
                        count: 0,
                        items: [],
                    },
                    ticketsSearchKeyword: '',
                    ticketsSummaryFields: [
                        { key: 'id', label: 'โพย #', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '80px' } },
                        { key: 'member_username', label: 'user' },
                        { key: 'member_name', label: 'ชื่อสมาชิก' },
                        { key: 'bet_types', label: 'ประเภท' },
                        { key: 'bet_numbers', label: 'เลขที่แทง' },
                        { key: 'total_amount', label: 'ยอดแทง', thClass: 'text-right', tdClass: 'text-right' },
                        { key: 'status', label: 'สถานะ', thClass: 'text-center', tdClass: 'text-center' },
                        { key: 'created_at', label: 'เวลาแทง', thClass: 'text-center', tdClass: 'text-center' },
                    ],
                };
            },
            computed: {
                modalTitle() {
                    if (this.formmethod === 'settle') {
                        return 'ประกาศผล / คำนวณรางวัล';
                    }

                    return 'งวดหวย';
                },
                canCalculate() {
                    if (this.formmethod !== 'settle') {
                        return false;
                    }

                    return onlyDigits(this.formaddedit.result_number.first_prize).length === 6
                        && onlyDigits(this.formaddedit.result_number.last_2_digits).length === 2;
                },
                firstMarketOption() {
                    for (const group of this.markets) {
                        if (Array.isArray(group.options) && group.options.length > 0) {
                            return group.options[0];
                        }
                    }

                    return null;
                },
                filteredTicketsSummaryItems() {
                    const rows = Array.isArray(this.ticketsSummaryData.items) ? this.ticketsSummaryData.items : [];
                    const keyword = String(this.ticketsSearchKeyword || '').trim().toLowerCase();
                    if (!keyword) {
                        return rows;
                    }

                    return rows.filter((item) => {
                        const username = String(item.member_username || '').toLowerCase();
                        const fullName = String(item.member_name || '').toLowerCase();
                        const memberId = String(item.member_id || '').toLowerCase();
                        const betTypes = String(item.bet_types || '').toLowerCase();
                        const betNumbers = String(item.bet_numbers || '').toLowerCase();
                        return username.includes(keyword)
                            || fullName.includes(keyword)
                            || memberId.includes(keyword)
                            || betTypes.includes(keyword)
                            || betNumbers.includes(keyword);
                    });
                },
                filteredBlockedNumbersItems() {
                    const rows = Array.isArray(this.blockedNumbersData.items) ? this.blockedNumbersData.items : [];
                    const keyword = String(this.blockedSearchKeyword || '').trim().toLowerCase();
                    if (!keyword) {
                        return rows;
                    }

                    return rows.filter((item) => {
                        const number = String(item.number || '').toLowerCase();
                        const betType = String(item.bet_type_label || item.bet_type || '').toLowerCase();
                        const reason = String(item.reason || '').toLowerCase();
                        return number.includes(keyword) || betType.includes(keyword) || reason.includes(keyword);
                    });
                },
            },
            watch: {
                'formaddedit.market_id'() {
                    this.$nextTick(() => this.syncMarketSelectValue());
                },
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            methods: {
                resetForm() {
                    const firstMarketId = this.firstMarketOption ? this.firstMarketOption.value : null;
                    this.formaddedit = {
                        market_id: firstMarketId,
                        draw_date: '',
                        open_at: '',
                        close_at: '',
                        result_number: {
                            first_prize: '',
                            last_2_digits: '',
                        },
                        result_at: '',
                    };
                    this.currentDraw = {
                        market_name: '',
                        draw_date: '',
                        status_label: '',
                    };

                    this.$nextTick(() => this.syncMarketSelectValue());
                },
                editModal(id) {
                    this.code = id;
                    this.formmethod = 'edit';
                    this.show = false;
                    this.$nextTick(async () => {
                        this.show = true;
                        await this.loadData();
                        this.$refs.addedit.show();
                    });
                },
                addModal() {
                    this.code = null;
                    this.formmethod = 'add';
                    this.resetForm();
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();
                    });
                },
                settleModal(id) {
                    this.code = id;
                    this.formmethod = 'settle';
                    this.show = false;
                    this.$nextTick(async () => {
                        this.show = true;
                        await this.loadData();
                        this.$refs.addedit.show();
                    });
                },
                onModalShown() {
                    this.initMarketSelect2();
                    this.syncMarketSelectValue();
                },
                onModalHidden() {
                    this.destroyMarketSelect2();
                },
                onNativeMarketChange(event) {
                    const value = event?.target?.value || '';
                    this.formaddedit.market_id = value ? parseInt(value, 10) : null;
                },
                initMarketSelect2() {
                    const selectEl = this.$refs.marketSelect;
                    if (!selectEl || !window.jQuery) {
                        return;
                    }

                    const $select = window.jQuery(selectEl);
                    if (!$select.length || typeof $select.select2 !== 'function') {
                        return;
                    }

                    this.destroyMarketSelect2();

                    $select.select2({
                        width: '100%',
                        dropdownParent: window.jQuery(this.$refs.addedit.$el),
                        placeholder: '-- เลือกรายการหวย --',
                        allowClear: false,
                    });

                    $select.on('change.drawMarket', () => {
                        const value = $select.val();
                        this.formaddedit.market_id = value ? parseInt(value, 10) : null;
                    });
                },
                destroyMarketSelect2() {
                    const selectEl = this.$refs.marketSelect;
                    if (!selectEl || !window.jQuery) {
                        return;
                    }

                    const $select = window.jQuery(selectEl);
                    if (!$select.length) {
                        return;
                    }

                    $select.off('.drawMarket');
                    if ($select.hasClass('select2-hidden-accessible') && typeof $select.select2 === 'function') {
                        $select.select2('destroy');
                    }
                },
                syncMarketSelectValue() {
                    const selectEl = this.$refs.marketSelect;
                    if (!selectEl || !window.jQuery) {
                        return;
                    }

                    const value = this.formaddedit.market_id ? String(this.formaddedit.market_id) : '';
                    const $select = window.jQuery(selectEl);
                    $select.val(value);

                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.trigger('change.select2');
                    }
                },
                statusLabel(status) {
                    const map = {
                        draft: 'ร่าง',
                        open: 'เปิดรับ',
                        closed: 'ปิดรับ',
                        resulted: 'ประกาศผลแล้ว',
                    };

                    return map[status] || status;
                },
                async loadData() {
                    const response = await axios.post("{{ route('admin.lotto.draws.loaddata') }}", { id: this.code });
                    const d = response?.data?.data || {};

                    this.currentDraw = {
                        market_name: d.market?.name || '-',
                        draw_date: d.draw_date ? String(d.draw_date).substring(0, 10) : '-',
                        status_label: this.statusLabel(d.status || '-'),
                    };

                    this.formaddedit = {
                        market_id: d.market_id,
                        draw_date: d.draw_date ? String(d.draw_date).substring(0, 10) : '',
                        open_at: toDateTimeLocal(d.open_at),
                        close_at: toDateTimeLocal(d.close_at),
                        result_number: {
                            first_prize: d.result_number?.first_prize || '',
                            last_2_digits: d.result_number?.last_2_digits || d.result_number?.bottom_2 || '',
                        },
                        result_at: toDateTimeLocal(d.result_at),
                    };

                    this.$nextTick(() => this.syncMarketSelectValue());
                },
                validateDrawWindow() {
                    if (!this.formaddedit.open_at || !this.formaddedit.close_at) {
                        return 'กรุณาระบุเวลาเปิดรับและปิดรับให้ครบ';
                    }

                    if (this.formaddedit.open_at >= this.formaddedit.close_at) {
                        return 'เวลาเปิดรับต้องน้อยกว่าเวลาปิดรับ';
                    }

                    return '';
                },
                async openBlockedNumbersModal(drawId) {
                    const response = await axios.post("{{ route('admin.lotto.draws.blocked_numbers') }}", { id: drawId });
                    this.blockedNumbersData = response?.data?.data || { draw: {}, count: 0, items: [] };
                    this.blockedSearchKeyword = '';
                    this.$refs.blockedNumbersModal.show();
                },
                async openTicketsSummaryModal(drawId) {
                    const response = await axios.post("{{ route('admin.lotto.draws.tickets_summary') }}", { id: drawId });
                    this.ticketsSummaryData = response?.data?.data || { draw: {}, count: 0, items: [] };
                    this.ticketsSearchKeyword = '';
                    this.$refs.ticketsSummaryModal.show();
                },
                formatMoney(value) {
                    return Number(value || 0).toLocaleString('th-TH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },
                async submitDrawForm() {
                    const validationMessage = this.validateDrawWindow();
                    if (validationMessage) {
                        await this.$bvModal.msgBoxOk(validationMessage, {
                            title: 'ข้อมูลไม่ถูกต้อง',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true,
                        });
                        return;
                    }

                    const payload = {
                        market_id: this.formaddedit.market_id,
                        draw_date: this.formaddedit.draw_date,
                        open_at: this.formaddedit.open_at ? this.formaddedit.open_at.replace('T', ' ') : null,
                        close_at: this.formaddedit.close_at ? this.formaddedit.close_at.replace('T', ' ') : null,
                        result_at: this.formaddedit.result_at ? this.formaddedit.result_at.replace('T', ' ') : null,
                    };

                    const url = this.formmethod === 'add'
                        ? "{{ route('admin.lotto.draws.create') }}"
                        : "{{ route('admin.lotto.draws.update') }}";

                    const response = await this.$http.post(url, { id: this.code, data: payload });

                    await this.$bvModal.msgBoxOk(response.data.message, {
                        title: 'ผลการดำเนินการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'success',
                        centered: true,
                    });

                    this.$refs.addedit.hide();
                    window.LaravelDataTables['lottoDrawsTable'].draw(false);
                },
                async submitSettleForm() {
                    if (!this.canCalculate) {
                        return;
                    }

                    const payload = {
                        result_number: {
                            first_prize: onlyDigits(this.formaddedit.result_number.first_prize),
                            last_2_digits: onlyDigits(this.formaddedit.result_number.last_2_digits),
                        },
                        result_at: this.formaddedit.result_at ? this.formaddedit.result_at.replace('T', ' ') : null,
                    };

                    const response = await this.$http.post("{{ route('admin.lotto.draws.settle') }}", {
                        id: this.code,
                        data: payload,
                    });

                    const summary = response?.data?.data || {};
                    const message = [
                        response.data.message || 'คำนวณรางวัลเรียบร้อยแล้ว',
                        `จำนวนโพย: ${summary.ticket_count || 0}`,
                        `โพยที่ถูกรางวัล: ${summary.winning_ticket_count || 0}`,
                        `ยอดจ่ายรวม: ${summary.total_win_amount || 0}`,
                    ].join('\n');

                    await this.$bvModal.msgBoxOk(message, {
                        title: 'ผลการคำนวณ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'success',
                        centered: true,
                    });

                    this.$refs.addedit.hide();
                    window.LaravelDataTables['lottoDrawsTable'].draw(false);
                },
                openDraw(id) {
                    this.$http.post("{{ route('admin.lotto.draws.open') }}", { id })
                        .then(response => {
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                centered: true,
                            });
                            window.LaravelDataTables['lottoDrawsTable'].draw(false);
                        });
                },
                closeDraw(id) {
                    this.$http.post("{{ route('admin.lotto.draws.close') }}", { id })
                        .then(response => {
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                centered: true,
                            });
                            window.LaravelDataTables['lottoDrawsTable'].draw(false);
                        });
                },
                async generateAutoDraws(dryRun = false) {
                    const confirmed = await this.$bvModal.msgBoxConfirm(
                        dryRun ? 'ต้องการตรวจสอบรายการงวดที่จะสร้างอัตโนมัติหรือไม่?' : 'ต้องการสร้างงวดอัตโนมัติเลยหรือไม่?',
                        {
                            title: 'ยืนยันการทำงาน',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: dryRun ? 'info' : 'success',
                            okTitle: 'ยืนยัน',
                            cancelTitle: 'ยกเลิก',
                            centered: true,
                        }
                    );

                    if (!confirmed) {
                        return;
                    }

                    const payload = {
                        days: 7,
                        dry_run: dryRun ? 1 : 0,
                    };

                    const response = await axios.post("{{ route('admin.lotto.draws.generate_auto') }}", payload);
                    const summary = response?.data?.data?.summary || null;

                    const message = summary
                        ? `ตลาดที่เข้าเกณฑ์: ${summary.market_count}, สร้างใหม่: ${summary.created}, มีอยู่แล้ว: ${summary.exists}, ข้าม: ${summary.skipped}`
                        : (response?.data?.message || 'ดำเนินการเสร็จสิ้น');

                    this.$bvModal.msgBoxOk(message, {
                        title: dryRun ? 'ผล Dry-run' : 'ผลการ Generate',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'success',
                        centered: true,
                    });

                    window.LaravelDataTables['lottoDrawsTable'].draw(false);
                },
            },
        });

        window.addModal = function () { window.app.addModal(); };
        window.editModal = function (id) { window.app.editModal(id); };
        window.settleModal = function (id) { window.app.settleModal(id); };
        window.openDraw = function (id) { window.app.openDraw(id); };
        window.closeDraw = function (id) { window.app.closeDraw(id); };
        window.generateAutoDraws = function (dryRun) { window.app.generateAutoDraws(dryRun); };
        window.showDrawBlockedNumbers = function (id) { window.app.openBlockedNumbersModal(id); };
        window.showDrawTicketList = function (id) { window.app.openTicketsSummaryModal(id); };
    </script>
@endpush
