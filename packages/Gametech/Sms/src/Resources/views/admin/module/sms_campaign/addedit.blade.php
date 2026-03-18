{{-- Modal Add/Edit SMS Campaign --}}
<b-modal ref="addedit" id="addedit" centered scrollable size="lg"
         title="เพิ่ม/แก้ไข SMS Campaign"
         :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">

    <b-form @submit.prevent="addEditSubmit">

        {{-- ✅ Workflow hint --}}
        <b-alert show variant="info" class="py-2">
            <div class="d-flex justify-content-between align-items-center">
                <div class="font-weight-bold">
                    ขั้นตอนการทำงาน:
                    <span v-if="workflowStep === 1">1) กรอกข้อมูล + อัปโหลด/พรีวิว</span>
                    <span v-else-if="workflowStep === 2">2) บันทึกแล้ว → กด Build</span>
                    <span v-else>3) Build แล้ว → กด Dispatch</span>
                </div>
                <div class="small text-muted">
                    Mode: <strong>@{{ formmethod === 'add' ? 'ADD' : 'EDIT' }}</strong>
                    <span v-if="code"> | Campaign ID: <strong>#@{{ code }}</strong></span>
                </div>
            </div>
            <div class="small mt-1">
                <span v-if="workflowStep === 1">หลังบันทึกสำเร็จ ระบบจะเปิดปุ่ม Build ให้อัตโนมัติในหน้าต่างเดิม</span>
                <span v-else-if="workflowStep === 2">กด Build เพื่อสร้างรายชื่อผู้รับ (sms_recipients) แล้วจะเห็นปุ่ม Dispatch</span>
                <span v-else>พร้อมส่งแล้ว สามารถ Dispatch เข้า queue:sms ได้</span>
            </div>
        </b-alert>

        {{-- Campaign Name --}}
        <b-form-group
                id="input-group-name"
                label="ชื่อแคมเปญ:"
                label-for="name"
                description="ตั้งชื่อเพื่อให้ทีมงานจำได้ เช่น โปรโมชันปลายปี / แจ้งระบบ / เตือนฝาก">
            <b-form-input
                    id="name"
                    v-model="formaddedit.name"
                    type="text"
                    size="sm"
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        {{-- Sender --}}
        <b-form-group
                id="input-group-sender"
                label="Sender (from):"
                label-for="sender_name"
                description="ชื่อผู้ส่งตามที่อนุญาตใน Vonage (หรือค่า default ที่ตั้งใน config)">
            <b-form-input
                    id="sender_name"
                    v-model="formaddedit.sender_name"
                    type="text"
                    size="sm"
                    autocomplete="off"
                    placeholder="เช่น GAMETECH"
            ></b-form-input>
        </b-form-group>

        {{-- Message --}}
        <b-form-group
                id="input-group-message"
                label="ข้อความ SMS:"
                label-for="message"
                description="ระวังความยาว (SMS แบ่งเป็นหลายส่วนได้) แนะนำให้สั้น กระชับ และมีจุดประสงค์ชัด">
            <b-form-textarea
                    id="message"
                    v-model="formaddedit.message"
                    rows="4"
                    max-rows="8"
                    size="sm"
                    autocomplete="off"
                    required
            ></b-form-textarea>
            <small class="text-muted">
                ความยาว: @{{ (formaddedit.message || '').length }} ตัวอักษร
            </small>
        </b-form-group>

        <hr>

        {{-- Audience mode --}}
        <b-form-group
                id="input-group-audience"
                label="กลุ่มเป้าหมาย (Audience Mode):"
                label-for="audience_mode"
                description="เลือกว่าจะยิงจากสมาชิก, จากไฟล์, หรือผสม">
            <b-form-select
                    id="audience_mode"
                    v-model="formaddedit.audience_mode"
                    :options="audienceModeOptions"
                    size="sm"
                    required
                    :disabled="lockAudienceMode"
            ></b-form-select>
            <small class="text-muted d-block mt-1" v-if="lockAudienceMode">
                หมายเหตุ: หลังจาก Build แล้ว จะล็อกการเปลี่ยน Audience Mode เพื่อป้องกัน workflow เพี้ยน
            </small>
        </b-form-group>

        {{-- Opt-out / Consent --}}
        <b-form-group
                id="input-group-rules"
                label="กฎการส่ง:"
                label-for="rules">
            <b-form-checkbox v-model="formaddedit.respect_opt_out" switch size="sm">
                เคารพ Opt-out (STOP/Unsub) — แนะนำให้เปิดตลอด
            </b-form-checkbox>

            <b-form-checkbox v-model="formaddedit.require_consent" switch size="sm" class="mt-1">
                Require Consent (ใช้เมื่อระบบคุณมีฟิลด์ consent จริง)
            </b-form-checkbox>
        </b-form-group>

        <hr>

        {{-- Upload import (เฉพาะ upload_only/mixed) --}}
        <b-form-group
                v-if="showUploadSection"
                id="input-group-upload"
                label="อัปโหลดไฟล์เบอร์ (CSV/XLS/XLSX):"
                label-for="import_file"
                description="อัปโหลดเพื่อสร้าง Import Batch และนำไป Build recipients"
        >
            <b-input-group size="sm" class="mb-2">
                <b-form-file
                        id="import_file"
                        v-model="importFile"
                        accept=".csv,.xls,.xlsx,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        browse-text="เลือกไฟล์"
                        placeholder="ยังไม่ได้เลือกไฟล์"
                        :disabled="lockUpload"
                ></b-form-file>

                <b-input-group-append>
                    <b-button variant="outline-primary" :disabled="!importFile || importUploading || lockUpload"
                              @click="uploadImport">
                        @{{ importUploading ? 'กำลังอัปโหลด...' : 'อัปโหลด/พรีวิว' }}
                    </b-button>
                </b-input-group-append>
            </b-input-group>

            <b-form-row>
                <b-col cols="4">
                    <b-form-group label="Country code" label-for="country_code" class="mb-0">
                        <b-form-input id="country_code" v-model="importOptions.country_code" size="sm" :disabled="lockUpload"></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col cols="4">
                    <b-form-group label="Has header" class="mb-0">
                        <b-form-select v-model="importOptions.has_header" :options="yesNoOptions"
                                       size="sm" :disabled="lockUpload"></b-form-select>
                    </b-form-group>
                </b-col>
                <b-col cols="4">
                    <b-form-group label="Phone column (optional)" class="mb-0">
                        <b-form-input v-model="importOptions.phone_column" size="sm"
                                      placeholder="phone/tel/mobile" :disabled="lockUpload"></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>

            <div v-if="importBatch.id" class="mt-2">
                <div class="small text-muted">
                    Import Batch: <strong>#@{{ importBatch.id }}</strong>
                    (valid: @{{ importBatch.valid_phones }}, invalid: @{{ importBatch.invalid_phones }}, dup: @{{ importBatch.duplicate_phones }})
                </div>
            </div>

            <div v-if="importBatch.preview && importBatch.preview.length" class="mt-2">
                <div class="font-weight-bold mb-1">Preview</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Raw</th>
                            <th>E164</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(p, idx) in importBatch.preview" :key="idx">
                            <td>@{{ idx+1 }}</td>
                            <td>@{{ p.raw }}</td>
                            <td class="text-monospace">@{{ p.e164 }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <small class="text-muted d-block mt-2">
                หมายเหตุ: ตอน Add สามารถอัปโหลด/พรีวิวได้เลย แต่การ Build/Dispatch จะเปิดให้หลัง “บันทึกสำเร็จ”
            </small>

            <small class="text-muted d-block mt-1" v-if="lockUpload">
                หลัง Build แล้ว ระบบจะล็อกการอัปโหลดไฟล์ เพื่อป้องกันการสลับ batch โดยไม่ตั้งใจ
            </small>
        </b-form-group>

        {{-- ✅ Recipient summary (เฉพาะเมื่อเริ่ม workflow step 2 แล้ว) --}}
        <b-card v-if="workflowStep >= 2" class="mb-3" body-class="p-2">
            <div class="d-flex justify-content-between align-items-center">
                <div class="font-weight-bold">สรุปผู้รับของแคมเปญ</div>
                <b-button size="sm" variant="outline-secondary" :disabled="loadingStats" @click="refreshStats">
                    @{{ loadingStats ? 'กำลังโหลด...' : 'รีเฟรช' }}
                </b-button>
            </div>
            <div class="small text-muted mt-1">
                ผู้รับทั้งหมด: <strong>@{{ stats.recipients_total }}</strong>
                | ค้างส่ง (queued): <strong>@{{ stats.queued_total }}</strong>
                | Delivered: <strong>@{{ stats.delivered_total }}</strong>
                | Failed: <strong>@{{ stats.failed_total }}</strong>
            </div>
            <div class="small text-muted" v-if="stats.recipients_total === 0">
                ยังไม่มีรายชื่อผู้รับ — กรุณา Build recipients ก่อน
            </div>
        </b-card>

        {{-- ✅ Build recipients actions (เปิดหลังบันทึกสำเร็จเท่านั้น) --}}
        <b-form-group
                v-if="workflowStep >= 2"
                id="input-group-build"
                label="สร้างรายชื่อผู้รับ (Build recipients):"
                description="ระบบจะสร้างแถวใน sms_recipients ตามแคมเปญนี้">
            <b-button size="sm" variant="outline-success" class="mr-1"
                      v-if="showBuildMembersBtn"
                      @click="buildRecipients('member_all')"
                      :disabled="!canBuildBase || building">
                @{{ building ? 'กำลังทำ...' : 'Build จากสมาชิกทั้งหมด' }}
            </b-button>

            <b-button size="sm" variant="outline-success" class="mr-1"
                      v-if="showBuildUploadBtn"
                      @click="buildRecipients('upload_only')"
                      :disabled="!canBuildUpload || building">
                @{{ building ? 'กำลังทำ...' : 'Build จากไฟล์ที่อัปโหลด' }}
            </b-button>

            <b-button size="sm" variant="outline-success"
                      v-if="showBuildMixedBtn"
                      @click="buildRecipients('mixed')"
                      :disabled="!canBuildMixed || building">
                @{{ building ? 'กำลังทำ...' : 'Build แบบผสม (สมาชิก+ไฟล์)' }}
            </b-button>

            <small class="text-muted d-block mt-2">
                หลัง Build สำเร็จ ระบบจะเปิดส่วน Dispatch ให้ทันที
            </small>
        </b-form-group>

        {{-- ✅ Dispatch (เปิดหลัง Build แล้วเท่านั้น) --}}
        <b-form-group
                v-if="workflowStep >= 3"
                id="input-group-dispatch"
                label="เริ่มส่ง (Dispatch queued):"
                description="ปล่อยคิวส่งเข้า queue:sms ตามจำนวนที่กำหนดต่อครั้ง">
            <b-input-group size="sm">
                <b-form-input type="number" min="1" max="5000" v-model.number="dispatchLimit"></b-form-input>
                <b-input-group-append>
                    <b-button variant="primary" @click="dispatchQueued"
                              :disabled="!canDispatch || dispatching">
                        @{{ dispatching ? 'กำลังปล่อยคิว...' : 'Dispatch' }}
                    </b-button>
                </b-input-group-append>
            </b-input-group>

            <small class="text-muted d-block mt-2">
                แนะนำ: dispatch @{{ recommendedDispatch }} รายการ (อิงจาก queued ที่ค้างส่ง)
            </small>
        </b-form-group>

        {{-- Remark --}}
        <b-form-group
                id="input-group-remark"
                label="หมายเหตุ:"
                label-for="remark"
                description="บันทึกเพิ่มเติม เช่น ใช้กับเว็บไหน กลุ่มลูกค้าไหน">
            <b-form-textarea
                    id="remark"
                    v-model="formaddedit.remark"
                    rows="2"
                    max-rows="6"
                    size="sm"
                    autocomplete="off"
            ></b-form-textarea>
        </b-form-group>

        <b-button type="submit" variant="primary">
            @{{ formmethod === 'add' ? 'บันทึก (สร้างแคมเปญ)' : 'บันทึก (อัปเดต)' }}
        </b-button>
        <b-button type="button" variant="outline-secondary" class="ml-1" @click="$refs.addedit.hide()">ปิด</b-button>

    </b-form>
</b-modal>

@push('scripts')
    <script type="module">
        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: true,

                    formmethod: 'add',
                    code: null,

                    building: false,
                    dispatching: false,
                    loadingStats: false,
                    loadingData: false,

                    loadToken: 0,

                    dispatchLimit: 1000,

                    importFile: null,
                    importUploading: false,
                    importOptions: {
                        country_code: '66',
                        has_header: 1,
                        phone_column: '',
                    },
                    importBatch: {
                        id: null,
                        valid_phones: 0,
                        invalid_phones: 0,
                        duplicate_phones: 0,
                        preview: [],
                    },

                    stats: {
                        recipients_total: 0,
                        queued_total: 0,
                        delivered_total: 0,
                        failed_total: 0,
                    },

                    yesNoOptions: [
                        {value: 1, text: 'Yes'},
                        {value: 0, text: 'No'},
                    ],
                    audienceModeOptions: [
                        {value: 'member_all', text: 'สมาชิกทั้งหมด (member_all)'},
                        {value: 'member_filter', text: 'กรองสมาชิก (member_filter) - ยังไม่เปิดในตัวอย่างนี้'},
                        {value: 'upload_only', text: 'จากไฟล์ (upload_only)'},
                        {value: 'mixed', text: 'ผสม (mixed)'},
                    ],

                    formaddedit: {
                        name: '',
                        sender_name: '',
                        message: '',
                        audience_mode: 'member_all',
                        respect_opt_out: true,
                        require_consent: false,
                        remark: '',
                    },
                };
            },

            computed: {
                showUploadSection() {
                    return this.formaddedit.audience_mode === 'upload_only' || this.formaddedit.audience_mode === 'mixed';
                },

                // ✅ workflow step:
                // 1 = add (ยังไม่มี campaign id)
                // 2 = edit/saved (มี id แล้ว แต่ยังไม่ build)
                // 3 = built (มี recipients แล้ว)
                workflowStep() {
                    if (this.formmethod !== 'edit' || !this.code) return 1;

                    const recipients = parseInt(this.stats.recipients_total || 0, 10);
                    const queued = parseInt(this.stats.queued_total || 0, 10);

                    if (recipients > 0 || queued > 0) return 3;

                    return 2;
                },

                lockAudienceMode() {
                    // หลัง build แล้ว lock audience_mode กัน workflow เพี้ยน
                    return this.workflowStep >= 3;
                },

                lockUpload() {
                    // หลัง build แล้ว lock upload
                    return this.workflowStep >= 3;
                },

                canBuildBase() {
                    return this.formmethod === 'edit' && !!this.code && !this.loadingData;
                },

                canBuildUpload() {
                    return this.canBuildBase && !!this.importBatch.id;
                },

                canBuildMixed() {
                    return this.canBuildBase && !!this.importBatch.id;
                },

                showBuildMembersBtn() {
                    return this.formaddedit.audience_mode === 'member_all'
                        || this.formaddedit.audience_mode === 'member_filter'
                        || this.formaddedit.audience_mode === 'mixed';
                },

                showBuildUploadBtn() {
                    return this.formaddedit.audience_mode === 'upload_only'
                        || this.formaddedit.audience_mode === 'mixed';
                },

                showBuildMixedBtn() {
                    return this.formaddedit.audience_mode === 'mixed';
                },

                canDispatch() {
                    if (this.formmethod !== 'edit' || !this.code || this.loadingData) return false;

                    // ถ้ามี queued ก็ dispatch ได้
                    if ((this.stats.queued_total || 0) > 0) return true;

                    // ถ้ามี recipients แล้ว ถึงแม้ queued จะ 0 ก็ให้ dispatch ได้ (เผื่อระบบตั้ง queued ตอน dispatch)
                    return (this.stats.recipients_total || 0) > 0;
                },

                recommendedDispatch() {
                    const q = parseInt(this.stats.queued_total || 0, 10);
                    if (q <= 0) return 0;
                    return Math.min(1000, q);
                },
            },

            watch: {
                'formaddedit.audience_mode'(val) {
                    // ถ้า build แล้ว ห้ามเปลี่ยน mode (กัน state เพี้ยน)
                    if (this.lockAudienceMode) {
                        // revert กลับ (นิ่งกว่า msgBox ใน watch)
                        // แต่เพื่อความชัดเจน แสดงแจ้งเตือนด้วย
                        this.$bvToast && this.$bvToast.toast('ไม่สามารถเปลี่ยน Audience Mode หลังจาก Build แล้ว', {
                            title: 'แจ้งเตือน',
                            variant: 'warning',
                            solid: true,
                            autoHideDelay: 2500,
                        });
                        return;
                    }

                    // ถ้าเปลี่ยนไปไม่ใช้ไฟล์ → ล้างไฟล์/preview กันเข้าใจผิด
                    if (val !== 'upload_only' && val !== 'mixed') {
                        this.importFile = null;
                        this.importBatch = {
                            id: null,
                            valid_phones: 0,
                            invalid_phones: 0,
                            duplicate_phones: 0,
                            preview: []
                        };
                    }
                },

                'stats.queued_total'(val) {
                    const q = parseInt(val || 0, 10);
                    if (q > 0) {
                        this.dispatchLimit = Math.min(this.dispatchLimit || 1000, q, 5000);
                        if (!this.dispatchLimit || this.dispatchLimit < 1) this.dispatchLimit = Math.min(1000, q);
                    }
                }
            },

            methods: {
                async editModal(code) {
                    this.resetForm();
                    this.formmethod = 'edit';
                    this.code = code;

                    this.$refs.addedit.show();
                    await this.loadDataOnce();
                    await this.refreshStatsOnce();
                },

                addModal() {
                    this.resetForm();
                    this.formmethod = 'add';
                    this.code = null;

                    this.$refs.addedit.show();
                },

                resetForm() {
                    this.formaddedit = {
                        name: '',
                        sender_name: '',
                        message: '',
                        audience_mode: 'member_all',
                        respect_opt_out: true,
                        require_consent: false,
                        remark: '',
                    };

                    this.importFile = null;
                    this.importUploading = false;
                    this.importBatch = {id: null, valid_phones: 0, invalid_phones: 0, duplicate_phones: 0, preview: []};

                    this.stats = {recipients_total: 0, queued_total: 0, delivered_total: 0, failed_total: 0};
                    this.loadingStats = false;
                    this.loadingData = false;

                    this.dispatchLimit = 1000;
                    this.building = false;
                    this.dispatching = false;
                },

                async loadDataOnce() {
                    if (this.loadingData) return;
                    if (!this.code) return;

                    this.loadingData = true;
                    const token = ++this.loadToken;

                    try {
                        const response = await axios.post(
                            "{{ route('admin.'.$menu->currentRoute.'.loaddata') }}",
                            {id: this.code}
                        );

                        if (token !== this.loadToken) return;

                        const data = response.data.data || {};

                        this.formaddedit.name = data.name || '';
                        this.formaddedit.sender_name = data.sender_name || '';
                        this.formaddedit.message = data.message || '';
                        this.formaddedit.audience_mode = data.audience_mode || 'member_all';
                        this.formaddedit.respect_opt_out = (data.respect_opt_out ?? 1) ? true : false;
                        this.formaddedit.require_consent = (data.require_consent ?? 0) ? true : false;
                        this.formaddedit.remark = data.remark || '';

                        if (typeof data.recipients_total !== 'undefined') this.stats.recipients_total = parseInt(data.recipients_total || 0, 10);
                        if (typeof data.queued_total !== 'undefined') this.stats.queued_total = parseInt(data.queued_total || 0, 10);
                        if (typeof data.delivered_total !== 'undefined') this.stats.delivered_total = parseInt(data.delivered_total || 0, 10);
                        if (typeof data.failed_total !== 'undefined') this.stats.failed_total = parseInt(data.failed_total || 0, 10);

                        // ✅ ทำให้ import batch “ไม่ว่าง” เมื่อเคย upload มาก่อน
                        if (data.last_import_batch_id && !this.importBatch.id) {
                            this.importBatch.id = parseInt(data.last_import_batch_id, 10);
                        }
                    } catch (e) {
                        console.log('loadData error', e);
                    } finally {
                        if (token === this.loadToken) {
                            this.loadingData = false;
                        }
                    }
                },

                async refreshStatsOnce() {
                    if (this.formmethod !== 'edit' || !this.code) return;
                    if (this.loadingStats) return;

                    this.loadingStats = true;

                    try {
                        const res = await axios.post("{{ route('admin.'.$menu->currentRoute.'.stats') }}", {id: this.code});
                        const s = res.data.data || {};
                        this.stats = {
                            recipients_total: parseInt(s.recipients_total || 0, 10),
                            queued_total: parseInt(s.queued_total || 0, 10),
                            delivered_total: parseInt(s.delivered_total || 0, 10),
                            failed_total: parseInt(s.failed_total || 0, 10),
                        };
                    } catch (e) {
                        console.log('refreshStats error', e);
                    } finally {
                        this.loadingStats = false;
                    }
                },

                async refreshStats() {
                    await this.refreshStatsOnce();
                },

                addEditSubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    let url;
                    if (this.formmethod === 'add') {
                        url = "{{ route('admin.'.$menu->currentRoute.'.create') }}";
                    } else {
                        url = "{{ route('admin.'.$menu->currentRoute.'.update') }}";
                    }

                    this.$http.post(url, {
                        id: this.code,
                        data: this.formaddedit,
                        import_batch_id: this.importBatch.id || null
                    })
                        .then(async response => {
                            // ✅ เปลี่ยน: ไม่ปิด modal เสมอ
                            const payload = (response.data && response.data.data) ? response.data.data : {};
                            const campaignId = payload.campaign_id || payload.id || null;

                            this.$bvToast && this.$bvToast.toast(response.data.message || 'บันทึกสำเร็จ', {
                                title: 'สำเร็จ',
                                variant: 'success',
                                solid: true,
                                autoHideDelay: 2000,
                            });

                            // ถ้า add -> สลับเป็น edit แล้วโหลดข้อมูลต่อทันที (เปิด build)
                            if (this.formmethod === 'add') {
                                if (campaignId) {
                                    this.formmethod = 'edit';
                                    this.code = campaignId;

                                    await this.loadDataOnce();
                                    await this.refreshStatsOnce();
                                } else {
                                    // ถ้า backend ไม่ส่ง campaign_id กลับมา จะไหลต่อไม่ได้
                                    // ให้แจ้งเตือนชัด ๆ (กันเงียบแล้วงง)
                                    this.$bvModal.msgBoxOk('บันทึกสำเร็จ แต่ไม่พบ campaign_id ใน response — กรุณารีเฟรชหน้าแล้วแก้ไขจากตาราง', {
                                        title: 'แจ้งเตือน',
                                        size: 'sm',
                                        okVariant: 'warning',
                                        centered: true
                                    });
                                }
                            } else {
                                // edit -> แค่ refresh stats ให้
                                await this.refreshStatsOnce();
                            }

                            if (window.LaravelDataTables && window.LaravelDataTables["dataTableBuilder"]) {
                                window.LaravelDataTables["dataTableBuilder"].draw(false);
                            }

                            this.toggleButtonDisable(false);
                        })
                        .catch(exception => {
                            console.log('error', exception);
                            this.toggleButtonDisable(false);
                        });
                },

                async uploadImport() {
                    if (!this.importFile) return;
                    if (this.lockUpload) return;

                    this.importUploading = true;

                    try {
                        const fd = new FormData();
                        fd.append('file', this.importFile);

                        if (this.formmethod === 'edit' && this.code) {
                            fd.append('campaign_id', this.code);
                        }

                        fd.append('country_code', this.importOptions.country_code || '66');
                        fd.append('has_header', this.importOptions.has_header ? 1 : 0);
                        if (this.importOptions.phone_column) fd.append('phone_column', this.importOptions.phone_column);

                        const res = await axios.post("{{ route('admin.sms_import.parse') }}", fd, {
                            headers: {'Content-Type': 'multipart/form-data'}
                        });

                        const payload = res.data.data || res.data || {};
                        const batch = payload.batch || payload;

                        this.importBatch.id = batch.id || payload.batch_id || null;

                        if (payload.counters) {
                            this.importBatch.valid_phones = payload.counters.valid_phones || 0;
                            this.importBatch.invalid_phones = payload.counters.invalid_phones || 0;
                            this.importBatch.duplicate_phones = payload.counters.duplicate_phones || 0;
                        } else {
                            this.importBatch.valid_phones = batch.valid_phones || 0;
                            this.importBatch.invalid_phones = batch.invalid_phones || 0;
                            this.importBatch.duplicate_phones = batch.duplicate_phones || 0;
                        }

                        this.importBatch.preview = payload.preview || batch.preview || (batch.meta && batch.meta.preview) || [];

                        this.$bvToast && this.$bvToast.toast('อัปโหลดสำเร็จ', {
                            title: 'สำเร็จ',
                            variant: 'success',
                            solid: true,
                            autoHideDelay: 2000,
                        });

                    } catch (e) {
                        console.log('uploadImport error', e);

                        this.$bvModal.msgBoxOk('อัปโหลดไม่สำเร็จ โปรดตรวจสอบชนิดไฟล์/ขนาดไฟล์', {
                            title: 'ผิดพลาด',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true
                        });
                    } finally {
                        this.importUploading = false;
                    }
                },

                async buildRecipients(mode) {
                    if (this.formmethod !== 'edit' || !this.code) return;

                    if ((mode === 'upload_only' || mode === 'mixed') && !this.importBatch.id) {
                        this.$bvModal.msgBoxOk('กรุณาอัปโหลดไฟล์ให้ได้ Import Batch ก่อน', {
                            title: 'แจ้งเตือน',
                            size: 'sm',
                            okVariant: 'warning',
                            centered: true
                        });
                        return;
                    }

                    this.building = true;

                    try {
                        const res = await axios.post("{{ route('admin.'.$menu->currentRoute.'.build_recipients') }}", {
                            id: this.code,
                            mode: mode,
                            import_batch_id: this.importBatch.id || null
                        });

                        this.$bvToast && this.$bvToast.toast(res.data.message || 'Build สำเร็จ', {
                            title: 'สำเร็จ',
                            variant: 'success',
                            solid: true,
                            autoHideDelay: 2000,
                        });

                        await this.refreshStatsOnce();

                    } catch (e) {
                        console.log('buildRecipients error', e);
                        this.$bvModal.msgBoxOk('Build recipients ไม่สำเร็จ', {
                            title: 'ผิดพลาด',
                            size: 'sm',
                            okVariant: 'danger',
                            centered: true
                        });
                    } finally {
                        this.building = false;
                    }
                },

                async dispatchQueued() {
                    if (this.formmethod !== 'edit' || !this.code) return;

                    if (!this.canDispatch) {
                        this.$bvModal.msgBoxOk('ยังไม่มีผู้รับค้างส่ง กรุณา Build recipients ก่อน', {
                            title: 'แจ้งเตือน',
                            size: 'sm',
                            okVariant: 'warning',
                            centered: true
                        });
                        return;
                    }

                    this.dispatching = true;

                    try {
                        const limit = this.dispatchLimit || this.recommendedDispatch || 1000;

                        const res = await axios.post("{{ route('admin.'.$menu->currentRoute.'.dispatch') }}", {
                            id: this.code,
                            limit: limit
                        });

                        this.$bvToast && this.$bvToast.toast(res.data.message || 'Dispatch สำเร็จ', {
                            title: 'สำเร็จ',
                            variant: 'success',
                            solid: true,
                            autoHideDelay: 2000,
                        });

                        await this.refreshStatsOnce();

                    } catch (e) {
                        console.log('dispatchQueued error', e);
                        this.$bvModal.msgBoxOk('Dispatch ไม่สำเร็จ', {
                            title: 'ผิดพลาด',
                            size: 'sm',
                            okVariant: 'danger',
                            centered: true
                        });
                    } finally {
                        this.dispatching = false;
                    }
                },
            },
        });
    </script>
@endpush
