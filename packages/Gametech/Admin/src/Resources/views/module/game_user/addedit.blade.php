<b-modal ref="addedit" id="addedit" centered scrollable size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent.once="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">
            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-pro_code"
                            label="ชื่อโปร:"
                            label-for="pro_code"
                            description="ระบุ โปรที่รับ">
                        <b-form-select
                                id="id"
                                v-model="formaddedit.pro_code"
                                :options="option.pro_code"
                                size="sm"
                                required
                        ></b-form-select>
                    </b-form-group>
                </b-col>
                <b-col></b-col>
            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-turnpro"
                            label="เทรินโปร:"
                            label-for="turnpro"
                            description="ระบุ เทรินโปร">
                        <b-form-input
                                id="turnpro"
                                v-model="formaddedit.turnpro"
                                type="number"
                                size="sm"
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>

                <b-col>
                    <b-form-group
                            id="input-group-amount_balance"
                            label="ยอดเทรินทั้งหมด:"
                            label-for="amount_balance"
                            description="ระบุ ยอดเทรินทั้งหมด">
                        <b-form-input
                                id="amount_balance"
                                v-model="formaddedit.amount_balance"
                                type="number"
                                size="sm"
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-withdraw_limit_rate"
                            label="อัตราอั้นถอน (เท่า):"
                            label-for="withdraw_limit_rate"
                            description="ระบุ อัตราอั้นถอน (เท่า)">
                        <b-form-input
                                id="withdraw_limit_rate"
                                v-model="formaddedit.withdraw_limit_rate"
                                type="number"
                                size="sm"
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>

                <b-col>
                    <b-form-group
                            id="input-group-withdraw_limit_amount"
                            label="ยอดอั้นถอนทั้งหมด:"
                            label-for="withdraw_limit_amount"
                            description="ระบุ ยอดอั้นถอนทั้งหมด">
                        <b-form-input
                                id="withdraw_limit_amount"
                                v-model="formaddedit.withdraw_limit_amount"
                                type="number"
                                size="sm"
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>

            <b-button type="submit" variant="primary">บันทึก</b-button>
        </b-form>
    </b-container>
</b-modal>


{{-- ✅ Modal ใหม่: ใส่โปรโมชั่นย้อนหลังให้ "รายการฝากล่าสุด" ของสมาชิก (ดึงจาก admin.game_user.lastpayment) --}}
<b-modal ref="addpro" id="addpro" centered scrollable size="lg"
         title="ใส่โปรโมชั่นย้อนหลัง (ผูกกับรายการฝากล่าสุด)"
         :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true"
         :lazy="true">

    <b-container class="bv-example-row">
        <b-form @submit.prevent="applyRetroPromotion" v-if="show">

            {{-- ซ่อนไว้เฉย ๆ เพื่อกันลืมว่า modal นี้ผูกกับรายการฝาก --}}
            <input type="hidden" :value="addPro.bank_payment_code">

            {{-- ข้อมูลรายการฝากล่าสุด --}}
            <div class="mb-3 p-2 border rounded bg-light" v-if="addProLoaded">
                <div v-if="addPro.bank_payment_code">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="font-weight-bold">
                                รายการฝากล่าสุด: <span v-text="addPro.bank_payment_code"></span>
                            </div>
                            <div class="text-muted">
                                สมาชิก: <span v-text="addPro.member_name"></span>
                                (<span v-text="addPro.member_id"></span>)
                            </div>
                            <div class="text-muted" v-if="addPro.bank_time">
                                เวลาโอน/ฝาก: <span v-text="addPro.bank_time"></span>
                            </div>
                        </div>

                        <div class="text-right">
                            <div>
                                ยอดฝาก:
                                <span class="font-weight-bold" v-text="addPro.deposit_amount"></span>
                            </div>

                            <div class="text-muted" v-if="addPro.current_pro_id">
                                ผูกโปรแล้ว:
                                <span v-text="addPro.current_pro_name"></span>
                            </div>

                            <div class="text-danger" v-else>
                                ยังไม่ผูกโปรโมชั่น
                            </div>
                        </div>
                    </div>

                    <div class="mt-2" v-if="addPro.current_pro_id">
                        <small class="text-danger">
                            รายการนี้ผูกโปรไว้แล้ว เพื่อกันจ่ายซ้ำ ระบบจะไม่ให้ใส่เพิ่ม/สลับ (ถ้าต้องการแก้ ต้องทำโหมด reverse/ยกเลิกแยก)
                        </small>
                    </div>
                </div>

                <div v-else class="text-danger">
                    ไม่พบรายการฝากล่าสุดของสมาชิกนี้
                </div>
            </div>

            <div v-else class="text-center my-3">
                <b-spinner small></b-spinner>
                <span class="ml-2">กำลังโหลดข้อมูลรายการฝากล่าสุด...</span>
            </div>

            {{-- เลือกโปร --}}
            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-retro-promo"
                            label="เลือกโปรโมชั่น:"
                            label-for="retro_promotion_id"
                            description="เมื่อเลือกแล้วระบบจะเช็คเงื่อนไขและเตรียมข้อมูล Preview ให้">
                        <b-form-select
                                id="retro_promotion_id"
                                v-model="addPro.promotion_id"
                                :options="promotions"
                                size="sm"
                                :disabled="promotionLoading || !addPro.bank_payment_code || !!addPro.current_pro_id"
                                @change="onRetroPromotionChanged"
                                required
                        ></b-form-select>

                        <small class="text-muted" v-if="!addPro.bank_payment_code">
                            ยังไม่มีรายการฝากล่าสุดให้ผูกโปร
                        </small>
                    </b-form-group>
                </b-col>
            </b-form-row>

            {{-- Preview --}}
            <div class="mb-3 p-2 border rounded" v-if="addProPreviewReady">
                <div class="font-weight-bold mb-2">สรุปที่จะได้รับ (Preview)</div>

                <b-form-row>
                    <b-col md="4">
                        <div class="text-muted">โบนัสเพิ่ม</div>
                        <div class="font-weight-bold" v-text="addPro.preview.bonus_amount"></div>
                    </b-col>
                    <b-col md="4">
                        <div class="text-muted">เทริน</div>
                        <div class="font-weight-bold" v-text="addPro.preview.turnpro"></div>
                    </b-col>
                    <b-col md="4">
                        <div class="text-muted">ยอดเทรินทั้งหมด</div>
                        <div class="font-weight-bold" v-text="addPro.preview.amount_balance"></div>
                    </b-col>
                </b-form-row>

                <b-form-row class="mt-2">
                    <b-col md="6">
                        <div class="text-muted">อั้นถอน (เท่า)</div>
                        <div class="font-weight-bold" v-text="addPro.preview.withdraw_limit_rate"></div>
                    </b-col>
                    <b-col md="6">
                        <div class="text-muted">ยอดอั้นถอนทั้งหมด</div>
                        <div class="font-weight-bold" v-text="addPro.preview.withdraw_limit_amount"></div>
                    </b-col>
                </b-form-row>

                <div class="text-muted mt-2">
                    หมายเหตุ: Preview ควรมาจาก backend เพื่อความถูกต้อง (ไม่คำนวณใน JS)
                </div>
            </div>

            {{-- เหตุผล --}}
            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-retro-reason"
                            label="เหตุผล:"
                            label-for="retro_reason"
                            description="แนะนำบังคับกรอกเพื่อ audit (เช่น ฝากแล้วลืมกดรับโปร)">
                        <b-form-input
                                id="retro_reason"
                                v-model="addPro.reason"
                                type="text"
                                size="sm"
                                autocomplete="off"
                                placeholder="เช่น ลูกค้าฝากแล้วลืมกดรับโปร ขอใส่ย้อนหลัง"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>

            <div class="d-flex justify-content-between">
                <b-button variant="secondary" @click="$refs.addpro.hide()">ปิด</b-button>

                <b-button
                        type="submit"
                        variant="primary"
                        :disabled="
                            addProSaving
                            || !addProPreviewReady
                            || !!addPro.current_pro_id
                            || !addPro.bank_payment_code
                            || !(addPro.reason && addPro.reason.toString().trim().length > 0)
                        "
                >
                    <b-spinner small v-if="addProSaving"></b-spinner>
                    <span class="ml-1">บันทึกใส่โปรย้อนหลัง</span>
                </b-button>
            </div>

        </b-form>
    </b-container>
</b-modal>



@push('scripts')
    <script>
        function showModalNew(id, method) { window.app.showModalNew(id, method); }
        function refill(id) { window.app.refill(id); }
        function money(id) { window.app.money(id); }
        function point(id) { window.app.point(id); }
        function diamond(id) { window.app.diamond(id); }
        function commentModal(id) { window.app.commentModal(id); }
        function delSub(id, table) { window.app.delSub(id, table); }
        function editdatasub(id, status, method) { window.app.editdatasub(id, status, method); }
        function changegamepass(id) { window.app.showModalChange(id); }
        function resetpro(id) { window.app.resetPro(id); }

        // ✅ global function เปิด modal ใส่โปรย้อนหลัง
        function addProModal(memberCode) { window.app.addProModal(memberCode); }

        $(document).ready(function () {
            $('body').addClass('sidebar-collapse');
        });
    </script>

    <script type="module">
        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: false,
                    showsub: false,
                    showremark: false,
                    fieldsRemark: [],
                    fields: [],
                    items: [],
                    caption: null,
                    isBusy: false,
                    isBusyRemark: false,

                    formmethodsub: 'edit',
                    formsub: { remark: '' },

                    formchange: { id: null, password: '' },

                    formmethod: 'edit',
                    formaddedit: {
                        pro_code: 0,
                        turnpro: 0,
                        amount_balance: 0,
                        withdraw_limit_rate: 0,
                        withdraw_limit_amount: 0,
                    },

                    option: { pro_code: '' },

                    formrefill: { id: null, amount: 0, account_code: '', remark_admin: '' },

                    formmoney: { id: null, amount: 0, type: 'D', remark: '' },
                    formpoint: { id: null, amount: 0, type: 'D', remark: '' },
                    formdiamond: { id: null, amount: 0, type: 'D', remark: '' },

                    banks: [{value: '', text: '== ธนาคาร =='}],
                    typesmoney: [{value: 'D', text: 'เพิ่ม Wallet'}, {value: 'W', text: 'ลด Wallet'}],
                    typespoint: [{value: 'D', text: 'เพิ่ม Point'}, {value: 'W', text: 'ลด Point'}],
                    typesdiamond: [{value: 'D', text: 'เพิ่ม Diamond'}, {value: 'W', text: 'ลด Diamond'}],

                    // ✅ promotions (ต้องส่ง value = promotion.code)
                    promotions: [{value: '', text: '== เลือกโปรโมชั่น =='}],
                    promotionLoading: false,

                    // ✅ State สำหรับ modal ใส่โปรย้อนหลัง (ผูกกับรายการฝากล่าสุด)
                    addPro: {
                        bank_payment_code: null,
                        member_id: null,
                        member_name: '',
                        deposit_amount: 0,
                        bank_time: '',
                        current_pro_id: 0,
                        current_pro_name: '',
                        promotion_id: '',
                        reason: '',
                        preview: {
                            bonus_amount: 0,
                            turnpro: 0,
                            amount_balance: 0,
                            withdraw_limit_rate: 0,
                            withdraw_limit_amount: 0,
                        }
                    },
                    addProLoaded: false,
                    addProSaving: false,

                    _retroInternalChange: false,
                    _retroLastPromotionId: ''
                };
            },

            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },

            mounted() {
                this.loadPromotion();   // ของเดิม
                this.loadPromotions();  // ✅ ของใหม่
            },

            computed: {
                // ✅ ไม่ใช้ !!promotion_id ตรง ๆ กันเคส 0 / number
                addProPreviewReady() {
                    const promo = (this.addPro.promotion_id ?? '').toString().trim();
                    return !!this.addPro.member_id
                        && !!this.addPro.bank_payment_code
                        && promo.length > 0;
                }
            },

            methods: {
                showModalChange(code) {
                    this.formchange = { id: null, password: '' };
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.formchange.id = code;
                        this.$refs.changepass.show();
                    });
                },

                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        pro_code: 0,
                        turnpro: 0,
                        amount_balance: 0,
                        withdraw_limit_rate: 0,
                        withdraw_limit_amount: 0,
                    };
                    this.formmethod = 'edit';

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.code = code;
                        this.loadData();
                        this.$refs.addedit.show();
                    });
                },

                addModal() {
                    this.code = null;
                    this.formaddedit = {
                        turnpro: 0,
                        amount_balance: 0,
                        withdraw_limit_rate: 0,
                        withdraw_limit_amount: 0,
                    };
                    this.formmethod = 'add';

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();
                    });
                },

                // =========================================================
                // ✅ เปิด modal ใส่โปรย้อนหลัง (รับ member_code)
                // =========================================================
                async addProModal(memberCode) {
                    this.addProLoaded = false;

                    this.addPro = {
                        bank_payment_code: null,
                        member_id: memberCode,
                        member_name: '',
                        deposit_amount: 0,
                        bank_time: '',
                        current_pro_id: 0,
                        current_pro_name: '',
                        promotion_id: '',
                        reason: '',
                        preview: {
                            bonus_amount: 0,
                            turnpro: 0,
                            amount_balance: 0,
                            withdraw_limit_rate: 0,
                            withdraw_limit_amount: 0,
                        }
                    };

                    this._retroLastPromotionId = '';
                    this._setRetroPromotionSilently('');
                    this._resetRetroPreview();

                    this.show = false;
                    this.$nextTick(async () => {
                        this.show = true;
                        this.$refs.addpro.show();
                        await this.loadLastPaymentForMember(memberCode);
                    });
                },

                async loadLastPaymentForMember(memberCode) {
                    try {
                        const resp = await axios.get("{{ route('admin.game_user.lastpayment') }}", {
                            params: { member_code: memberCode }
                        });

                        const d = resp?.data?.data || {};

                        this.addPro.bank_payment_code = d.bank_payment_code ?? d.code ?? null;
                        this.addPro.member_id = d.member_code ?? memberCode;
                        this.addPro.member_name = d.member_name ?? d.name ?? '';
                        this.addPro.deposit_amount = d.amount ?? d.value ?? d.deposit_amount ?? 0;
                        this.addPro.bank_time = d.bank_time ?? '';

                        this.addPro.current_pro_id = d.pro_id ?? 0;
                        this.addPro.current_pro_name = d.pro_name ?? '';

                        this.addProLoaded = true;
                    } catch (e) {
                        console.log(e);
                        this.addProLoaded = true;
                    }
                },

                // =========================================================
                // ✅ โหลดรายการโปรโมชั่น (ต้องให้ value = promotion.code)
                // =========================================================
                async loadPromotions() {
                    this.promotionLoading = true;
                    try {
                        const response = await axios.post("{{ route('admin.promotion.loadpromotion') }}");

                        let opts = [{value: '', text: '== เลือกโปรโมชั่น =='}];

                        // ถ้า backend ส่งรูปที่พร้อมใช้ (value/text) มาแล้ว
                        if (response?.data?.promotions && Array.isArray(response.data.promotions)) {
                            // ✅ ควรยืนยันว่า value เป็น code อยู่แล้ว
                            opts = opts.concat(response.data.promotions);
                        } else if (response?.data?.data && Array.isArray(response.data.data)) {
                            // ✅ map ให้ value เป็น code เพื่อให้ selectPromotion() findOneWhere(['code'=>...]) ผ่านแน่ ๆ
                            opts = opts.concat(
                                response.data.data.map(p => ({
                                    value: p.code ?? p.value ?? '', // ✅ code มาก่อน
                                    text: p.name_th ?? p.name ?? p.text ?? `PROMO#${p.code ?? ''}`
                                })).filter(x => x.value !== '')
                            );
                        }

                        this.promotions = opts;
                    } catch (e) {
                        console.log(e);
                        this.promotions = [{value: '', text: '== เลือกโปรโมชั่น =='}];
                    } finally {
                        this.promotionLoading = false;
                    }
                },

                // =========================================================
                // ✅ เปลี่ยนโปรย้อนหลัง: selectpromotion + preview
                // =========================================================
                async onRetroPromotionChanged(newVal) {
                    if (this._retroInternalChange) return;

                    const memberId = this.addPro.member_id;
                    const bankPaymentCode = this.addPro.bank_payment_code;

                    if (!memberId || !bankPaymentCode) {
                        this._setRetroPromotionSilently('');
                        this._retroLastPromotionId = '';
                        this._resetRetroPreview();
                        return;
                    }

                    const nextPromotionId = (newVal ?? '').toString();
                    const prevPromotionId = (this._retroLastPromotionId ?? '').toString();

                    if (nextPromotionId === '') {
                        if (prevPromotionId !== '') {
                            await this.deselectPromotion(memberId);
                        }
                        this._retroLastPromotionId = '';
                        this._resetRetroPreview();
                        return;
                    }

                    if (prevPromotionId !== '' && prevPromotionId !== nextPromotionId) {
                        await this.deselectPromotion(memberId);
                        this._resetRetroPreview();
                    }

                    const resp = await this.selectPromotionWithPreview(memberId, nextPromotionId, bankPaymentCode);

                    if (!resp.ok) {
                        this._setRetroPromotionSilently('');
                        this._retroLastPromotionId = '';
                        this._resetRetroPreview();
                        await this.deselectPromotion(memberId);
                        return;
                    }

                    this._retroLastPromotionId = nextPromotionId;

                    // ✅ รองรับทั้ง payload.preview และ payload.data.preview
                    const payload = resp.data || {};
                    const preview = payload.preview || payload.data?.preview;

                    // ✅ กรณี backend ส่งเป็นแผ่นๆ (bonus_amount, turnpro, ...) ไม่ได้ห่อ preview
                    const flatPreview = {
                        bonus_amount: payload.bonus_amount ?? payload.data?.bonus_amount,
                        turnpro: payload.turnpro ?? payload.data?.turnpro,
                        amount_balance: payload.amount_balance ?? payload.data?.amount_balance,
                        withdraw_limit_rate: payload.withdraw_limit_rate ?? payload.data?.withdraw_limit_rate,
                        withdraw_limit_amount: payload.withdraw_limit_amount ?? payload.data?.withdraw_limit_amount,
                    };

                    if (preview) {
                        this.addPro.preview = Object.assign({}, this.addPro.preview, preview);
                    } else if (
                        flatPreview.bonus_amount != null
                        || flatPreview.turnpro != null
                        || flatPreview.amount_balance != null
                        || flatPreview.withdraw_limit_rate != null
                        || flatPreview.withdraw_limit_amount != null
                    ) {
                        this.addPro.preview = Object.assign({}, this.addPro.preview, flatPreview);
                    }
                },

                _resetRetroPreview() {
                    this.addPro.preview = {
                        bonus_amount: 0,
                        turnpro: 0,
                        amount_balance: 0,
                        withdraw_limit_rate: 0,
                        withdraw_limit_amount: 0,
                    };
                },

                _setRetroPromotionSilently(val) {
                    this._retroInternalChange = true;
                    this.addPro.promotion_id = val;
                    this.$nextTick(() => {
                        this._retroInternalChange = false;
                    });
                },

                // ✅ normalize response: sendResponse มักอยู่ใน resp.data.data
                async selectPromotionWithPreview(memberId, promotionId, bankPaymentCode) {
                    try {
                        const resp = await axios.post("{{ route('admin.promotion.selectpromotion') }}", {
                            id: memberId,
                            promotion: promotionId,         // ✅ promotion.code
                            bank_payment_code: bankPaymentCode,
                            mode: 'retro',
                        });

                        const ok = resp?.data?.success === true;
                        const payload = resp?.data || {};
                        const data = payload.data ?? payload; // ✅ normalize ให้ใช้ data เป็นหลัก

                        return { ok, data };
                    } catch (e) {
                        console.log(e);
                        return { ok: false, data: null };
                    }
                },

                async deselectPromotion(memberId) {
                    try {
                        await axios.post("{{ route('admin.promotion.deselectpromotion') }}", { id: memberId });
                    } catch (e) {
                        console.log(e);
                    }
                },

                // =========================================================
                // ✅ กดบันทึก: apply โปรย้อนหลังให้ bank_payment ล่าสุด
                // =========================================================
                async applyRetroPromotion() {
                    if (this.addProSaving) return;

                    const promo = (this.addPro.promotion_id ?? '').toString().trim();
                    const reason = (this.addPro.reason ?? '').toString().trim();

                    if (!this.addPro.bank_payment_code || !this.addPro.member_id || promo.length === 0 || reason.length === 0) {
                        return;
                    }

                    this.addProSaving = true;

                    try {
                        const resp = await axios.post("{{ route('admin.promotion.applyretro') }}", {
                            bank_payment_code: this.addPro.bank_payment_code,
                            member_id: this.addPro.member_id,
                            promotion_id: promo,   // ✅ promotion.code
                            reason: reason
                        });

                        if (resp?.data?.success === true) {
                            this.$bvModal.msgBoxOk(resp.data.message || 'บันทึกเรียบร้อย', {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });

                            if (this.$refs.tbdata && typeof this.$refs.tbdata.refresh === 'function') {
                                this.$refs.tbdata.refresh();
                            } else if (window.LaravelDataTables && window.LaravelDataTables["dataTableBuilder"]) {
                                window.LaravelDataTables["dataTableBuilder"].draw(false);
                            }

                            this.$refs.addpro.hide();
                        } else {
                            this.$bvModal.msgBoxOk(resp?.data?.message || 'ไม่สามารถใส่โปรย้อนหลังได้', {
                                title: 'ไม่สำเร็จ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'danger',
                                centered: true
                            });
                        }
                    } catch (e) {
                        console.log(e);
                        this.$bvModal.msgBoxOk('เกิดข้อผิดพลาดในการบันทึก', {
                            title: 'Error',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true
                        });
                    } finally {
                        this.addProSaving = false;
                    }
                },

                // =========================================================
                // ของเดิม: loadPromotion / loadData / etc.
                // =========================================================
                async loadPromotion() {
                    try {
                        const responses = axios.post("{{ route('admin.'.$menu->currentRoute.'.loadpromotion') }}");
                        const response = await responses;
                        this.option.pro_code = response.data.data;
                    } catch (error) {
                        console.log(error);
                    }
                },

                async loadData() {
                    const response = await axios.get("{{ url($menu->currentRoute.'/loaddata') }}", {
                        params: { id: this.code }
                    });

                    this.formaddedit = {
                        pro_code: response.data.data.pro_code,
                        turnpro: response.data.data.turnpro,
                        amount_balance: response.data.data.amount_balance,
                        withdraw_limit_rate: response.data.data.withdraw_limit_rate,
                        withdraw_limit_amount: response.data.data.withdraw_limit_amount,
                    };
                },

                addSubModal() {
                    this.formsub = { remark: '' };
                    this.formmethodsub = 'add';

                    this.showsub = false;
                    this.$nextTick(() => {
                        this.showsub = true;
                        this.$refs.addeditsub.show();
                    });
                },

                delModal(code) {
                    this.$bvModal.msgBoxConfirm('ต้องการดำเนินการ รีเซตยอดเทรินและอั้นหรือไม่.', {
                        title: 'โปรดยืนยันการทำรายการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'ตกลง',
                        cancelTitle: 'ยกเลิก',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        centered: true
                    })
                        .then(value => {
                            if (value) {
                                this.$http.post("{{ url($menu->currentRoute.'/delete') }}", { id: code })
                                    .then(response => {
                                        this.$bvModal.msgBoxOk(response.data.message, {
                                            title: 'ผลการดำเนินการ',
                                            size: 'sm',
                                            buttonSize: 'sm',
                                            okVariant: 'success',
                                            headerClass: 'p-2 border-bottom-0',
                                            footerClass: 'p-2 border-top-0',
                                            centered: true
                                        });
                                        window.LaravelDataTables["dataTableBuilder"].draw(false);
                                    })
                                    .catch(() => console.log('error'));
                            }
                        })
                        .catch(() => {});
                },

                delSub(code, table) {
                    this.$bvModal.msgBoxConfirm('ต้องการดำเนินการ รีเซตยอดเทรินและอั้นหรือไม่.', {
                        title: 'โปรดยืนยันการทำรายการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'ตกลง',
                        cancelTitle: 'ยกเลิก',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        centered: true
                    })
                        .then(value => {
                            if (value) {
                                this.$http.post("{{ url($menu->currentRoute.'/deletesub') }}", { id: code, method: table })
                                    .then(response => {
                                        this.$bvModal.msgBoxOk(response.data.message, {
                                            title: 'ผลการดำเนินการ',
                                            size: 'sm',
                                            buttonSize: 'sm',
                                            okVariant: 'success',
                                            headerClass: 'p-2 border-bottom-0',
                                            footerClass: 'p-2 border-top-0',
                                            centered: true
                                        });
                                        window.LaravelDataTables["dataTableBuilder"].draw(false);
                                    })
                                    .catch(error => {
                            const message = error.response?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่';
                            this.$bvModal.msgBoxOk(message, {
                                title: 'ข้อผิดพลาด',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'danger',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });
                            this.toggleButtonDisable && this.toggleButtonDisable(false);
                        });
                            }
                        })
                        .catch(error => {
                            const message = error.response?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่';
                            this.$bvModal.msgBoxOk(message, {
                                title: 'ข้อผิดพลาด',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'danger',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });
                            this.toggleButtonDisable && this.toggleButtonDisable(false);
                        });
                },

                resetPro(code) {
                    this.$bvModal.msgBoxConfirm('ต้องการดำเนินการ ยกเลิกโปร และล้างเทิน อั้นถอน ปลดปล่อย ลูกแกะให้เป็นอิสระ.', {
                        title: 'โปรดยืนยันการทำรายการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'ตกลง',
                        cancelTitle: 'ยกเลิก',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        centered: true
                    })
                        .then(value => {
                            if (value) {
                                this.$http.post("{{ url($menu->currentRoute.'/edit') }}", { id: code })
                                    .then(response => {
                                        this.$bvModal.msgBoxOk(response.data.message, {
                                            title: 'ผลการดำเนินการ',
                                            size: 'sm',
                                            buttonSize: 'sm',
                                            okVariant: 'success',
                                            headerClass: 'p-2 border-bottom-0',
                                            footerClass: 'p-2 border-top-0',
                                            centered: true
                                        });
                                        window.LaravelDataTables["dataTableBuilder"].draw(false);
                                    })
                                    .catch(error => {
                            const message = error.response?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่';
                            this.$bvModal.msgBoxOk(message, {
                                title: 'ข้อผิดพลาด',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'danger',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });
                            this.toggleButtonDisable && this.toggleButtonDisable(false);
                        });
                            }
                        })
                        .catch(error => {
                            const message = error.response?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่';
                            this.$bvModal.msgBoxOk(message, {
                                title: 'ข้อผิดพลาด',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'danger',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });
                            this.toggleButtonDisable && this.toggleButtonDisable(false);
                        });
                },

                addEditSubmitNew(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    var url = "{{ url($menu->currentRoute.'/update') }}/" + this.code;

                    let formData = new FormData();
                    const json = JSON.stringify({
                        pro_code: this.formaddedit.pro_code,
                        turnpro: this.formaddedit.turnpro,
                        amount_balance: this.formaddedit.amount_balance,
                        withdraw_limit_rate: this.formaddedit.withdraw_limit_rate,
                        withdraw_limit_amount: this.formaddedit.withdraw_limit_amount,
                    });

                    formData.append('data', json);

                    const config = {headers: {'Content-Type': `multipart/form-data; boundary=${formData._boundary}`}};

                    axios.post(url, formData, config)
                        .then(response => {
                            if (response.data.success === true) {
                                this.$bvModal.msgBoxOk(response.data.message, {
                                    title: 'ผลการดำเนินการ',
                                    size: 'sm',
                                    buttonSize: 'sm',
                                    okVariant: 'success',
                                    headerClass: 'p-2 border-bottom-0',
                                    footerClass: 'p-2 border-top-0',
                                    centered: true
                                });
                                window.LaravelDataTables["dataTableBuilder"].draw(false);
                            } else {
                                $.each(response.data.message, function (index, value) {
                                    document.getElementById(index).classList.add("is-invalid");
                                });
                                $('input').on('focus', function (event) {
                                    event.preventDefault();
                                    window.app.toggleButtonDisable(true);
                                    event.stopPropagation();
                                    var id = $(this).attr('id');
                                    document.getElementById(id).classList.remove("is-invalid");
                                });
                            }
                        })
                        .catch(errors => {
                            this.toggleButtonDisable(false);
                            Toast.fire({
                                icon: 'error',
                                title: errors.response.data
                            });
                        });
                },

                addEditSubmitNewSub(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    var url = "{{ url($menu->currentRoute.'/createsub') }}";

                    this.$http.post(url, {id: this.code, data: this.formsub})
                        .then(response => {
                            this.$bvModal.hide('addeditsub');
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });

                            window.LaravelDataTables["dataTableBuilder"].draw(false);
                        })
                        .catch(error => {
                            const message = error.response?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่';
                            this.$bvModal.msgBoxOk(message, {
                                title: 'ข้อผิดพลาด',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'danger',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });
                            this.toggleButtonDisable && this.toggleButtonDisable(false);
                        });
                },
            },
        });
    </script>
@endpush
