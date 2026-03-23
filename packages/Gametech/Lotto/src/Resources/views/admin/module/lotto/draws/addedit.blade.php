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
                    window.LaravelDataTables['dataTableBuilder'].draw(false);
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
                    window.LaravelDataTables['dataTableBuilder'].draw(false);
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
                            window.LaravelDataTables['dataTableBuilder'].draw(false);
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
                            window.LaravelDataTables['dataTableBuilder'].draw(false);
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

                    window.LaravelDataTables['dataTableBuilder'].draw(false);
                },
            },
        });

        window.addModal = function () { window.app.addModal(); };
        window.editModal = function (id) { window.app.editModal(id); };
        window.settleModal = function (id) { window.app.settleModal(id); };
        window.openDraw = function (id) { window.app.openDraw(id); };
        window.closeDraw = function (id) { window.app.closeDraw(id); };
        window.generateAutoDraws = function (dryRun) { window.app.generateAutoDraws(dryRun); };
    </script>
@endpush
