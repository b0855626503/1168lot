{{-- =========================
     MODAL: Process / Approve Redemption (ใช้ฟอร์มเดียว รองรับ approval/manual/external)
========================= --}}
{{-- =========================
     MODAL: Process Redemption (External / Manual / Approval)
========================= --}}
<b-modal ref="processRedeemModal"
         id="processRedeemModal"
         centered
         scrollable
         size="lg"
         title="ดำเนินการรางวัล (ทีมงาน)"
         :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">

    <b-form @submit.prevent="processRedeemSubmit" v-if="process && process.show">

        <div class="p-2 mb-2" style="background: rgba(0,0,0,.06); border-radius: 10px;">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <div class="fw-bold">
                        Redemption #<span v-text="process.form.id"></span>
                    </div>
                    <div class="text-muted small">
                        สร้างเมื่อ: <span v-text="process.form.created_at || '-'"></span>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-secondary" v-text="process.form.status || '-'"></span>
                    <div class="text-muted small">
                        โหมด: <span v-text="process.form.fulfillment_mode_snapshot || '-'"></span>
                    </div>
                </div>
            </div>
        </div>

        <b-form-group label="ผู้แลก (Member):" label-for="proc_member">
            <div id="proc_member" class="p-2" style="background: rgba(0,0,0,.04); border-radius: 10px;">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                        <div class="fw-bold">
                            <span v-text="process.form.member_username || '-'"></span>
                            <span class="text-muted">(#</span><span class="text-muted" v-text="process.form.member_code || process.form.member_id || '-'"></span><span class="text-muted">)</span>
                        </div>
                        <div class="text-muted small">ชื่อ: <span v-text="process.form.member_name || '-'"></span></div>
                        <div class="text-muted small">เบอร์: <span v-text="process.form.member_tel || '-'"></span></div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">
                            แต้มที่ใช้: <span class="fw-bold" v-text="process.form.point_cost_snapshot || 0"></span>
                        </div>
                        <div class="text-muted small">
                            ประเภท: <span class="fw-bold" v-text="process.form.reward_type_snapshot || '-'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </b-form-group>

        <b-form-group label="รางวัลที่แลก (Snapshot):" label-for="proc_reward">
            <div id="proc_reward" class="p-2" style="background: rgba(0,0,0,.04); border-radius: 10px;">
                <div class="fw-bold">
                    <span v-text="process.form.reward_name_snapshot || '-'"></span>
                    <span class="text-muted">(<span v-text="process.form.reward_code_snapshot || '-'"></span>)</span>
                </div>
            </div>
        </b-form-group>

        <hr class="my-2">

        <b-alert show variant="warning" class="small mb-2" v-if="process && process.warnNotStaffFlow">
            รายการนี้ไม่ได้เป็น “ภายนอก/ทีมงาน/รออนุมัติ” แต่ยังสามารถอัปเดตสถานะได้หากต้องการ
        </b-alert>

        <b-form-group label="อัปเดตสถานะ:" label-for="proc_status"
                      description="pending = รอดำเนินการ, approved = อนุมัติ, fulfilled = เสร็จแล้ว, rejected/cancelled = ไม่ผ่าน/ยกเลิก">
            <b-form-select id="proc_status"
                           v-model="process.form.status"
                           :options="process.optionStatus"
                           size="sm"
                           required>
            </b-form-select>
        </b-form-group>

        <div class="row">
            <div class="col-md-6">
                <b-form-group label="ช่องทาง/วิธีดำเนินการ:" label-for="proc_channel">
                    <b-form-input id="proc_channel" v-model.trim="process.form.result_channel" size="sm"></b-form-input>
                </b-form-group>
            </div>
            <div class="col-md-6">
                <b-form-group label="รหัสอ้างอิง/Tracking:" label-for="proc_ref">
                    <b-form-input id="proc_ref" v-model.trim="process.form.result_ref" size="sm"></b-form-input>
                </b-form-group>
            </div>
        </div>

        <b-form-group label="รายละเอียดการดำเนินการ / หมายเหตุทีมงาน:" label-for="proc_note">
            <b-form-textarea id="proc_note"
                             v-model.trim="process.form.result_note"
                             size="sm"
                             rows="4"
                             max-rows="8"></b-form-textarea>
        </b-form-group>

        <div class="row">
            <div class="col-md-6">
                <b-form-group label="วันที่ดำเนินการ:" label-for="proc_fulfilled_at">
                    <b-form-input id="proc_fulfilled_at"
                                  v-model="process.form.fulfilled_at_local"
                                  type="datetime-local"
                                  size="sm"></b-form-input>
                </b-form-group>
            </div>
            <div class="col-md-6">
                <b-form-group label="ผู้ดำเนินการ (handled_by):" label-for="proc_handled_by">
                    <b-form-input id="proc_handled_by" v-model.trim="process.form.handled_by" size="sm"></b-form-input>
                </b-form-group>
            </div>
        </div>

        <hr class="my-2">

        <div class="d-flex justify-content-between align-items-center">
            <b-button variant="secondary" @click.prevent="$refs.processRedeemModal.hide()">ปิด</b-button>

            <div class="d-flex gap-2">
                <b-button variant="outline-danger" :disabled="process.saving" @click.prevent="processReset()">ล้างค่า</b-button>

                <b-button type="submit" variant="primary" :disabled="process.saving">
                    <span v-if="process.saving" class="spinner-border spinner-border-sm me-1"></span>
                    บันทึกการดำเนินการ
                </b-button>
            </div>
        </div>

    </b-form>
</b-modal>


@push('scripts')
    <script>
        // endpoint (ชัด ๆ)
        window.rewardRedemptionEndpoints = {
            loaddata: "{{ route('admin.reward_redemption.loaddata') }}",
            process:  "{{ route('admin.reward_redemption.process') }}",
        };

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    // ====== ของเดิมคุณมีอะไรอยู่ก็เก็บไว้ ======
                    show: false,
                    // ...

                    // ====== เพิ่มตัวนี้เข้าไป ======
                    process: {
                        show: false,
                        saving: false,

                        // ถ้าไม่ใช่ external/manual/approval จะขึ้นเตือน
                        warnNotStaffFlow: false,

                        optionStatus: [
                            { value: 'pending',   text: 'pending (รอดำเนินการ)' },
                            { value: 'approved',  text: 'approved (อนุมัติ)' },
                            { value: 'fulfilled', text: 'fulfilled (เสร็จแล้ว)' },
                            { value: 'rejected',  text: 'rejected (ไม่อนุมัติ)' },
                            { value: 'cancelled', text: 'cancelled (ยกเลิก)' },
                        ],

                        form: this.defaultProcessForm(),
                    },
                };
            },

            // computed/watch ของเดิมคุณมีอะไรอยู่ก็อยู่ต่อได้
            // computed: { ... },
            // watch: { ... },

            methods: {
                // ====== ของเดิมคุณมี methods อะไรอยู่ก็เก็บไว้ ======
                // loadData(), addEditSubmit(), ...

                // ====== เพิ่มชุดนี้เข้าไป ======
                defaultProcessForm() {
                    return {
                        id: null,
                        reward_id: null,
                        member_id: null,
                        member_code: null,

                        member_username: null,
                        member_name: null,
                        member_tel: null,

                        status: 'pending',
                        fulfillment_mode_snapshot: '',
                        reward_type_snapshot: '',

                        reward_code_snapshot: '',
                        reward_name_snapshot: '',
                        point_cost_snapshot: 0,

                        created_at: '',

                        // result fields (ทีมงาน)
                        result_channel: '',
                        result_ref: '',
                        result_note: '',

                        fulfilled_at_local: null,
                        handled_by: '',
                    };
                },

                processReset() {
                    if (!this.process) return;
                    this.process.saving = false;
                    this.process.warnNotStaffFlow = false;
                    this.process.form = this.defaultProcessForm();
                },

                toDateTimeLocal(v) {
                    if (!v) return null;
                    const s = String(v).trim();
                    if (s.includes('T')) return s.substring(0, 16);
                    return s.replace(' ', 'T').substring(0, 16);
                },

                // ✅ เรียกจากปุ่ม action ใน datatable: window.app.openProcessRedeemModal(ID)
                async openProcessRedeemModal(redemptionId) {
                    this.processReset();

                    this.process.form.id = redemptionId;
                    this.process.show = false;

                    // เปิด modal ก่อน (ให้ UX ไว) แล้วค่อยโหลดข้อมูล
                    this.$nextTick(() => {
                        this.$refs.processRedeemModal.show();
                        this.process.show = true;
                    });

                    try {
                        const res = await axios.post(window.rewardRedemptionEndpoints.loaddata, { id: redemptionId });

                        // รองรับ response ทั้งแบบ sendResponse ของคุณ และแบบ data ตรง ๆ
                        const d = (res.data && res.data.data) ? res.data.data : (res.data || {});

                        this.process.form.id = d.id ?? redemptionId;
                        this.process.form.reward_id = d.reward_id ?? null;
                        this.process.form.member_id = d.member_id ?? null;
                        this.process.form.member_code = d.member_code ?? d.member_id ?? null;

                        this.process.form.member_username = d.member_username ?? d.user_name ?? null;
                        this.process.form.member_name = d.member_name ?? d.name ?? null;
                        this.process.form.member_tel = d.member_tel ?? d.tel ?? null;

                        this.process.form.status = String(d.status || 'pending');
                        this.process.form.fulfillment_mode_snapshot = String(d.fulfillment_mode_snapshot || '');
                        this.process.form.reward_type_snapshot = String(d.reward_type_snapshot || '');

                        this.process.form.reward_code_snapshot = String(d.reward_code_snapshot || '');
                        this.process.form.reward_name_snapshot = String(d.reward_name_snapshot || '');
                        this.process.form.point_cost_snapshot = Number(d.point_cost_snapshot || 0);
                        this.process.form.created_at = String(d.created_at || '');

                        // ถ้ามี field ผลการดำเนินการอยู่ใน DB ก็ map มาด้วย
                        this.process.form.result_channel = String(d.result_channel || '');
                        this.process.form.result_ref = String(d.result_ref || '');
                        this.process.form.result_note = String(d.result_note || '');

                        this.process.form.fulfilled_at_local = this.toDateTimeLocal(d.fulfilled_at);
                        this.process.form.handled_by = String(d.handled_by || '');

                        // logic เตือน: ไม่ใช่ external/manual/approval
                        const isExternal = (this.process.form.reward_type_snapshot === 'external');
                        const isManualish = (this.process.form.fulfillment_mode_snapshot === 'manual');
                        const isApproval = (this.process.form.fulfillment_mode_snapshot === 'approval');

                        this.process.warnNotStaffFlow = !(isExternal || isManualish || isApproval);

                    } catch (e) {
                        console.log('openProcessRedeemModal error', e);
                        alert('โหลดข้อมูลไม่สำเร็จ');
                    }
                },

                async processRedeemSubmit(e) {
                    e.preventDefault();

                    const id = Number(this.process.form.id || 0);
                    if (!id) return;

                    // ถ้ากดเป็นสถานะที่ “ถือว่าเสร็จ/อนุมัติ” แต่ยังไม่ใส่เวลา → เติมให้อัตโนมัติ
                    if ((this.process.form.status === 'fulfilled' || this.process.form.status === 'approved')
                        && !this.process.form.fulfilled_at_local) {

                        const now = new Date();
                        const pad = n => String(n).padStart(2, '0');
                        const y = now.getFullYear();
                        const m = pad(now.getMonth() + 1);
                        const d = pad(now.getDate());
                        const hh = pad(now.getHours());
                        const mm = pad(now.getMinutes());
                        this.process.form.fulfilled_at_local = `${y}-${m}-${d}T${hh}:${mm}`;
                    }

                    this.process.saving = true;

                    try {
                        const payload = {
                            id: id,
                            status: this.process.form.status,

                            // ส่งเป็น datetime string
                            fulfilled_at: this.process.form.fulfilled_at_local
                                ? (this.process.form.fulfilled_at_local.replace('T', ' ') + ':00')
                                : null,

                            handled_by: this.process.form.handled_by || null,
                            result_channel: this.process.form.result_channel || null,
                            result_ref: this.process.form.result_ref || null,
                            result_note: this.process.form.result_note || null,
                        };

                        const res = await axios.post(window.rewardRedemptionEndpoints.process, payload);

                        alert((res.data && res.data.message) ? res.data.message : 'บันทึกเรียบร้อย');

                        this.$refs.processRedeemModal.hide();

                        if (window.LaravelDataTables && window.LaravelDataTables["dataTableBuilder"]) {
                            window.LaravelDataTables["dataTableBuilder"].draw(false);
                        }

                    } catch (e) {
                        console.log('processRedeemSubmit error', e);
                        alert((e.response && e.response.data && e.response.data.message) ? e.response.data.message : 'บันทึกไม่สำเร็จ');
                    } finally {
                        this.process.saving = false;
                    }
                },
            }
        });
    </script>
@endpush

