{{-- =========================
     LINE CONNECT MODAL (extracted)
     - แยกออกจาก addedit โดยไม่เปลี่ยน interface เดิม
     - เปิด/ปิดต่อเว็บ: .env => LINE_CONNECT_ENABLED=true|false
     ========================= --}}

<b-modal
    ref="lineConnectModal"
    id="lineConnectModal"
    centered
    size="lg"
    title="เชื่อมต่อกับ LINE"
    :no-stacking="true"
    :no-close-on-backdrop="lineConnectUiLocked"
    :no-close-on-esc="lineConnectUiLocked"
    :hide-header-close="lineConnectUiLocked"
    :hide-footer="true"
    :lazy="true"
    :data-bank="lineConnect.bank"
    :data-acc="lineConnect.acc"
    :data-baseapi="lineConnect.baseapi"
    @hidden="onLineConnectHidden"
>
    <b-container class="bv-example-row">
        <b-form v-if="lineConnect.show" id="frmLineConnect" ref="frmLineConnect" @submit.prevent>

            {{-- READY OVERLAY --}}
            <div v-if="lineConnect.status === 'ready'" class="line-connect-modal-admin__ready">
                LINE : ONLINE
            </div>

            {{-- MAIN LAYOUT (hidden when ready) --}}
            <div v-else class="line-connect-modal-admin__panel" :class="{ 'is-locked': lineConnectUiLocked }">

                {{-- LOCK OVERLAY: ล็อกเฉพาะตอนยิง request --}}
                <div v-if="lineConnectUiLocked" class="line-connect-modal-admin__lock">
                    <div class="line-connect-modal-admin__lock-box">
                        <div class="line-connect-modal-admin__lock-title">กำลังดำเนินการ</div>
                        <div class="line-connect-modal-admin__lock-text">กำลังติดต่อระบบ… กรุณารอสักครู่</div>
                    </div>
                </div>

                <div class="line-connect-modal-admin__card line-connect-modal-admin__left">
                    <div class="line-connect-modal-admin__section-title">การเชื่อมต่อ</div>

                    <b-button
                        type="button"
                        class="line-connect-modal-admin__btn -status mb-2"
                        :disabled="lineConnectUiLocked"
                        @click="lineConnectCheckStatus"
                    >
                        <i class="fas fa-search"></i>
                        ตรวจสอบสถานะ
                    </b-button>

                    <b-button
                        v-if="lineConnect.canConnect && !lineConnect.showLoginForm"
                        type="button"
                        class="line-connect-modal-admin__btn -connect-qr mb-2"
                        :disabled="lineConnectUiLocked"
                        @click="lineConnectConnectQr"
                    >
                        <i class="fas fa-qrcode"></i>
                        เชื่อมต่อด้วย QR
                    </b-button>

                    <b-button
                        v-if="lineConnect.canConnect && !lineConnect.showLoginForm"
                        type="button"
                        class="line-connect-modal-admin__btn -connect-email"
                        :disabled="lineConnectUiLocked"
                        @click="lineConnectOpenLoginForm"
                    >
                        <i class="fas fa-envelope"></i>
                        เชื่อมต่อด้วย Email
                    </b-button>

                    {{-- LOGIN FORM (Email flow) --}}
                    <div v-if="lineConnect.showLoginForm" class="line-connect-modal-admin__login">
                        <b-form-group label="Email" label-for="line_login_email" class="mb-2">
                            <b-form-input
                                id="line_login_email"
                                v-model="lineConnect.email"
                                type="email"
                                size="sm"
                                autocomplete="off"
                                placeholder="กรอกอีเมล"
                                :disabled="lineConnectUiLocked"
                                required
                            ></b-form-input>
                        </b-form-group>

                        <b-form-group label="Password" label-for="line_login_password" class="mb-2">
                            <b-form-input
                                id="line_login_password"
                                v-model="lineConnect.password"
                                type="password"
                                size="sm"
                                autocomplete="off"
                                placeholder="กรอกรหัสผ่าน"
                                :disabled="lineConnectUiLocked"
                                required
                                @keyup.enter="lineConnectSubmitLogin"
                            ></b-form-input>
                        </b-form-group>

                        <b-button
                            type="button"
                            class="line-connect-modal-admin__btn -send"
                            :disabled="lineConnectUiLocked"
                            @click="lineConnectSubmitLogin"
                        >
                            <i class="fas fa-paper-plane"></i>
                            ส่ง
                        </b-button>

                        <b-button
                            type="button"
                            class="line-connect-modal-admin__btn -cancel"
                            :disabled="lineConnectUiLocked"
                            @click="lineConnectCancelLoginForm"
                        >
                            ย้อนกลับ
                        </b-button>
                    </div>

                    <div class="line-connect-modal-admin__meta">
                        <div><strong>ธนาคาร :</strong> <span v-text="lineConnect.bank || '-'"></span> <strong>เลขบัญชี :</strong> <span v-text="lineConnect.acc || '-'"></span></div>
                        <div><strong>วิธีการใช้งานระบบดึงรายการฝาก จากไลน์</strong></div>
                        <div>1. สมัครแจ้งเตือนเงินเข้าผ่านไลน์ ก่อนเชื่อมระบบ</div>
                        <div>2. ดึงจาก ต้องแสดงคำว่า Line Webhook</div>
                        <div>3. เปิดดึงยอด</div>

                    </div>
                </div>

                <div class="line-connect-modal-admin__card line-connect-modal-admin__right">
                    <div v-if="lineConnect.loadingAny" class="line-connect-modal-admin__status">
                        <div class="line-connect-modal-admin__status-title">กำลังดำเนินการ</div>
                        <div class="line-connect-modal-admin__status-text">โปรดรอสักครู่…</div>
                    </div>

                    <template v-else>

                        {{-- ✅ FIX (B): ระหว่าง polling อย่าทับ qr_required / pincode_required --}}
                        <template v-if="lineConnect.pollActive && lineConnect.status === 'unknown'">
                            <div class="line-connect-modal-admin__status">
                                <div class="line-connect-modal-admin__status-title">กำลังรอผลการเชื่อมต่อ</div>
                                <div class="line-connect-modal-admin__status-text">
                                    ระบบกำลังตรวจสอบสถานะ… กรุณารอสักครู่
                                </div>
                            </div>
                        </template>

                        <template v-else-if="lineConnect.status === 'unknown'">
                            <div class="line-connect-modal-admin__status">
                                <div class="line-connect-modal-admin__status-title">ยังไม่เริ่ม</div>
                                <div class="line-connect-modal-admin__status-text">
                                    กด “ตรวจสอบสถานะ” เพื่อเริ่มเช็ค
                                </div>

                            </div>
                        </template>

                        <template v-else-if="lineConnect.status === 'starting'">
                            <div class="line-connect-modal-admin__status">
                                <div class="line-connect-modal-admin__status-title">กำลังเริ่มเชื่อมต่อ</div>
                                <div class="line-connect-modal-admin__status-text">
                                    @{{ lineConnectStageText || 'ระบบกำลังเตรียมการเชื่อมต่อ…' }}
                                </div>
                            </div>
                        </template>

                        <template v-else-if="lineConnect.status === 'qr_required'">
                            <div v-if="lineConnect.qrSrc" class="line-connect-modal-admin__qr">
                                <img :src="lineConnect.qrSrc" class="line-connect-modal-admin__qr-img" alt="LINE QR" />
                            </div>
                            <div v-else class="line-connect-modal-admin__status">
                                <div class="line-connect-modal-admin__status-title">ต้องสแกน QR</div>
                                <div class="line-connect-modal-admin__status-text">
                                    @{{ lineConnectStageText || 'กำลังสร้างรูป QR…' }}
                                </div>
                            </div>
                            <div class="line-connect-modal-admin__qr-caption">สแกน QR Code ด้วย LINE</div>
                        </template>

                        <template v-else-if="lineConnect.status === 'pincode_required'">
                            <div class="line-connect-modal-admin__pin">
                                <div class="line-connect-modal-admin__pin-title">PIN</div>
                                <div class="line-connect-modal-admin__pin-code" v-text="lineConnect.pin || '----'"></div>
                                <div class="line-connect-modal-admin__qr-caption">กรอกรหัส PIN ในแอป LINE</div>
                            </div>
                        </template>

                        <template v-else-if="lineConnect.status === 'error'">
                            <div class="line-connect-modal-admin__status">
                                <div class="line-connect-modal-admin__status-title">เกิดข้อผิดพลาด</div>
                                <div class="line-connect-modal-admin__status-text" v-text="lineConnect.message || 'error'"></div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="line-connect-modal-admin__status">
                                <div class="line-connect-modal-admin__status-title">สถานะ</div>
                                <div class="line-connect-modal-admin__status-text" v-text="lineConnect.status"></div>
                            </div>
                        </template>
                    </template>
                </div>
            </div>

            <div class="line-connect-modal-admin__footer">
                <b-button variant="secondary" :disabled="lineConnectUiLocked" @click="closeLineConnectModal">ปิด</b-button>
            </div>
        </b-form>
    </b-container>
</b-modal>

@push('styles')
    <style>
        #lineConnectModal .modal-content {
            background: linear-gradient(180deg, #141823 0%, #0c0f18 100%);
            border: 1px solid #2c3140;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.55);
            color: #e9edf5;
        }

        #lineConnectModal .modal-header {
            border-bottom: 1px solid #2a2f3f;
        }

        #lineConnectModal .modal-title {
            color: #f2f4f8;
            font-weight: 700;
        }

        #lineConnectModal .modal-header .close,
        #lineConnectModal .modal-header .close span {
            color: #cdd3e3;
            text-shadow: none;
            opacity: 0.85;
        }

        #lineConnectModal .modal-header .close:hover,
        #lineConnectModal .modal-header .close:focus {
            opacity: 1;
        }

        .line-connect-modal-admin__panel { display: grid; grid-template-columns: 360px 1fr; gap: 16px; position: relative; }

        .line-connect-modal-admin__card {
            background: linear-gradient(180deg, #2b2f3c 0%, #1b1f2b 100%);
            border: 1px solid #3a3f4f;
            border-radius: 12px;
            padding: 18px;
            min-height: 240px;
            display: flex;
            flex-direction: column;
            color: #e9edf5;
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.25);
        }

        .line-connect-modal-admin__section-title { font-weight: 600; margin-bottom: 12px; }

        .line-connect-modal-admin__btn {
            display: flex !important;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: none;
            color: #ffffff;
            font-weight: 600;
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 10px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 2px 6px rgba(0, 0, 0, 0.4);
            width: 100%;
        }

        .line-connect-modal-admin__btn.-status { background: linear-gradient(180deg, #2c6bd8 0%, #1c4ea8 100%); }
        .line-connect-modal-admin__btn.-connect-qr { background: linear-gradient(180deg, #35b850 0%, #1f8b37 100%); }
        .line-connect-modal-admin__btn.-connect-email { background: linear-gradient(180deg, #22b3b0 0%, #14807e 100%); }
        .line-connect-modal-admin__btn.-send { background: linear-gradient(180deg, #8c5cff 0%, #5b2fd6 100%); }
        .line-connect-modal-admin__btn.-cancel { background: linear-gradient(180deg, #5a6072 0%, #3a3f4f 100%); }

        .line-connect-modal-admin__btn:hover { filter: brightness(1.05); }

        .line-connect-modal-admin__right { align-items: center; text-align: center; justify-content: center; }

        .line-connect-modal-admin__qr {
            background: #ffffff;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .line-connect-modal-admin__qr-img { width: 260px; height: 260px; object-fit: contain; display: block; }
        .line-connect-modal-admin__qr-caption { margin-top: 12px; color: #cfd6e6; font-size: 0.95rem; }
        .line-connect-modal-admin__footer { display: flex; justify-content: center; margin-top: 16px; }
        .line-connect-modal-admin__meta { margin-top: 8px; font-size: 12px; color: #cfd6e6; word-break: break-all; }

        .line-connect-modal-admin__pin-title,
        .line-connect-modal-admin__status-title { font-weight: 700; margin-bottom: 10px; }

        .line-connect-modal-admin__pin-code { font-size: 44px; letter-spacing: 6px; font-weight: 800; }
        .line-connect-modal-admin__status-text { color: #cfd6e6; }

        .line-connect-modal-admin__ready {
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            font-weight: 800;
            color: #df0a2f;
        }

        .line-connect-modal-admin__login {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(207, 214, 230, 0.15);
        }

        /* ===== LOCK OVERLAY ===== */
        .line-connect-modal-admin__lock{
            position: absolute;
            inset: 0;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(10, 12, 18, 0.55);
            border-radius: 12px;
        }
        .line-connect-modal-admin__lock-box{
            width: min(520px, 92%);
            background: linear-gradient(180deg, rgba(43,47,60,0.95) 0%, rgba(27,31,43,0.95) 100%);
            border: 1px solid rgba(58,63,79,0.85);
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 12px 28px rgba(0,0,0,0.35);
            text-align: center;
            color: #e9edf5;
        }
        .line-connect-modal-admin__lock-title{ font-weight: 800; font-size: 16px; margin-bottom: 6px; }
        .line-connect-modal-admin__lock-text{ color: #cfd6e6; font-size: 13px; }

        @media (max-width: 991.98px) {
            .line-connect-modal-admin__panel { grid-template-columns: 1fr; }
            .line-connect-modal-admin__card { min-height: 0; }
            .line-connect-modal-admin__qr-img { width: 220px; height: 220px; }
        }
    </style>
@endpush

@push('scripts')
    {{-- CDN QRCode generator --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.4.4/qrcode.min.js"></script>

    <script>
        function lineConnectModal(id) {
            try {
                const el = (window.event && window.event.currentTarget) ? window.event.currentTarget : null;
                window.app && window.app.lineConnectModal(id, el);
            } catch (e) {
                window.app && window.app.lineConnectModal(id, null);
            }
        }
        function lineConnentModal(id) {
            return lineConnectModal(id);
        }
    </script>

    <script>
        window.LineConnectAdminMixin = {
            data() {
                return {
                    lineConnect: {
                        show: false,
                        code: null,
                        bank: '',
                        acc: '',
                        baseapi: '',
                        canConnect: false,
                        status: 'unknown',
                        message: '',
                        pin: '',
                        qrSrc: '',
                        qrText: '',
                        showLoginForm: false,
                        email: '',
                        password: '',
                        device: 'DESKTOPWIN',
                        loadingAny: false,
                        pollActive: false,
                        pollStartedAt: 0,
                        pollHardTimeoutMs: 180000,
                        pollNextDelayMs: 500,
                        pollServerTimeoutMs: 5000,
                        pollServerIntervalMs: 500,
                        pollClientTimeoutMs: 10000,
                        pollErrorDelayBaseMs: 1000,
                        pollErrorDelayMaxMs: 5000,
                        pollErrorDelayCurrentMs: 0,
                        lastStage: '',
                        updatedAt: 0,
                        noProgressCount: 0,
                        loginMode: '',
                    },
                };
            },
            computed: {
                lineConnectUiLocked() {
                    return !!this.lineConnect.loadingAny;
                },
                lineConnectStageText() {
                    const stage = (this.lineConnect.lastStage || '').toLowerCase();
                    if (!stage) return '';
                    const map = {
                        'listener:queued': 'กำลังเตรียมเริ่มตัวเชื่อมต่อ',
                        'listener:connecting': 'กำลังเชื่อมต่อ LINE',
                        'login:qr_waiting': 'กำลังรอ QR จาก LINE',
                        'login:qr_issued': 'ได้รับ QR แล้ว กรุณาสแกนใน LINE',
                        'login:pin_required': 'ได้รับ PIN แล้ว กรุณากรอกใน LINE',
                        'listener:running': 'เชื่อมต่อสำเร็จ กำลังรอข้อความเข้า',
                        'recover:queued': 'กำลังกู้คืนการเชื่อมต่อ',
                    };
                    return map[stage] || (`ขั้นตอนล่าสุด: ${this.lineConnect.lastStage}`);
                },
            },
            methods: {
                deriveStatusFromStage(status, lastStage) {
                    const s = (status || '').toLowerCase();
                    const stage = (lastStage || '').toLowerCase();
                    if (s !== 'starting') return status || 'unknown';
                    if (stage.includes('pin_required')) return 'pincode_required';
                    if (stage.includes('qr_waiting') || stage.includes('qr_issued')) return 'qr_required';
                    return status || 'unknown';
                },

                // ===== QR render from text (qrUrl) via CDN =====
                async renderQrFromText(text) {
                    const t = (text || '').trim();
                    if (!t) return;

                    if (this.lineConnect.qrSrc && this.lineConnect.qrText === t) return;

                    this.lineConnect.qrText = t;
                    this.lineConnect.qrSrc = '';

                    try {
                        if (window.QRCode && typeof window.QRCode.toDataURL === 'function') {
                            const dataUrl = await window.QRCode.toDataURL(t, {
                                width: 260,
                                margin: 1,
                                errorCorrectionLevel: 'M',
                            });
                            this.lineConnect.qrSrc = dataUrl;
                            return;
                        }

                        this.lineConnect.qrSrc = `https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=${encodeURIComponent(t)}`;
                    } catch (e) {
                        this.lineConnect.qrSrc = `https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=${encodeURIComponent(t)}`;
                    }
                },

                // ===== LINE CONNECT: open modal =====
                lineConnectModal(id, el) {
                    this.resetLineConnectState();

                    this.lineConnect.code = id;

                    const bank = el && el.dataset ? (el.dataset.bank || '') : '';
                    const acc = el && el.dataset ? (el.dataset.acc || '') : '';
                    const baseapi = el && el.dataset ? (el.dataset.baseapi || '') : '';

                    this.lineConnect.bank = bank;
                    this.lineConnect.acc = acc;
                    this.lineConnect.baseapi = baseapi;

                    this.lineConnect.show = true;
                    this.lineConnect.canConnect = false;
                    this.lineConnect.status = 'unknown';
                    this.lineConnect.message = '';
                    this.lineConnect.pin = '';
                    this.lineConnect.qrSrc = '';
                    this.lineConnect.qrText = '';

                    this.lineConnect.showLoginForm = false;
                    this.lineConnect.email = '';
                    this.lineConnect.password = '';
                    this.lineConnect.device = 'DESKTOPWIN';

                    this.lineConnect.pollErrorDelayCurrentMs = 0;
                    this.lineConnect.lastStage = '';
                    this.lineConnect.updatedAt = 0;
                    this.lineConnect.noProgressCount = 0;
                    this.lineConnect.loginMode = '';

                    this.$bvModal.show('lineConnectModal');
                },

                closeLineConnectModal() {
                    this.$bvModal.hide('lineConnectModal');
                },

                onLineConnectHidden() {
                    this.stopLineConnectPolling();
                    this.resetLineConnectState();
                },

                resetLineConnectState() {
                    this.lineConnect.show = false;
                    this.lineConnect.code = null;
                    this.lineConnect.bank = '';
                    this.lineConnect.acc = '';
                    this.lineConnect.baseapi = '';
                    this.lineConnect.canConnect = false;
                    this.lineConnect.status = 'unknown';
                    this.lineConnect.message = '';
                    this.lineConnect.pin = '';
                    this.lineConnect.qrSrc = '';
                    this.lineConnect.qrText = '';

                    this.lineConnect.showLoginForm = false;
                    this.lineConnect.email = '';
                    this.lineConnect.password = '';
                    this.lineConnect.device = 'DESKTOPWIN';

                    this.lineConnect.loadingAny = false;

                    this.lineConnect.pollErrorDelayCurrentMs = 0;
                    this.lineConnect.lastStage = '';
                    this.lineConnect.updatedAt = 0;
                    this.lineConnect.noProgressCount = 0;
                    this.lineConnect.loginMode = '';

                    this.stopLineConnectPolling();
                },

                stopLineConnectPolling() {
                    this.lineConnect.pollActive = false;
                    this.lineConnect.pollStartedAt = 0;
                },

                extractLineStatusPayload(resp) {
                    const data = resp && resp.data ? resp.data : {};
                    const payload = (data && typeof data === 'object' && data.data && typeof data.data === 'object')
                        ? data.data
                        : data;

                    const status = payload.status || data.status || 'unknown';

                    const qrUrl = payload.qr_url || payload.qrUrl || payload.qr || payload.qrcode_url || payload.qrcodeUrl || '';
                    const qrBase64 = payload.qr_base64 || payload.qrBase64 || payload.qrcode_base64 || payload.qrcodeBase64 || '';

                    const pin = payload.pincode || payload.pin_code || payload.pinCode || payload.pin || '';
                    const message = payload.message || payload.error || data.message || '';

                    const timedOut = !!(payload.timedOut || payload.timed_out);
                    const lastStage = payload.lastStage || payload.last_stage || '';
                    const loginMode = (payload.loginMode || payload.login_mode || '').toLowerCase();
                    const updatedAtRaw = payload.updatedAt || payload.updated_at || 0;
                    const updatedAt = Number(updatedAtRaw) || 0;

                    let qrSrc = '';
                    if (typeof qrBase64 === 'string' && qrBase64.length) {
                        qrSrc = qrBase64.startsWith('data:') ? qrBase64 : `data:image/png;base64,${qrBase64}`;
                    }

                    const qrText = (typeof qrUrl === 'string' && qrUrl.length) ? qrUrl : '';

                    return { status, pin, qrSrc, qrText, timedOut, message, lastStage, loginMode, updatedAt, raw: payload };
                },

                applyLinePayload(p) {
                    this.lineConnect.status = this.deriveStatusFromStage(p.status, p.lastStage);
                    this.lineConnect.pin = p.pin || '';
                    this.lineConnect.message = p.message || '';
                    this.lineConnect.lastStage = p.lastStage || '';
                    this.lineConnect.loginMode = p.loginMode || this.lineConnect.loginMode || '';
                    this.lineConnect.updatedAt = p.updatedAt || 0;

                    if (p.qrSrc) this.lineConnect.qrSrc = p.qrSrc;
                    if (p.qrText) this.lineConnect.qrText = p.qrText;

                    if (this.lineConnect.status === 'qr_required') {
                        if (!this.lineConnect.qrSrc && this.lineConnect.qrText) {
                            this.renderQrFromText(this.lineConnect.qrText);
                        }
                    }
                },

                async lineConnectCheckStatus() {
                    if (!this.lineConnect.bank || !this.lineConnect.acc) {
                        this.lineConnect.status = 'error';
                        this.lineConnect.message = 'missing bank/acc';
                        this.lineConnect.canConnect = true;
                        return;
                    }

                    if (this.lineConnect.loadingAny) return;

                    this.stopLineConnectPolling();
                    this.lineConnect.noProgressCount = 0;
                    this.lineConnect.loadingAny = true;
                    this.lineConnect.message = '';

                    try {
                        const resp = await axios.get('https://line.168csn.com/auth/status', {
                            params: { bank: this.lineConnect.bank, acc: this.lineConnect.acc },
                            timeout: 12000,
                        });

                        const p = this.extractLineStatusPayload(resp);
                        this.applyLinePayload(p);

                        if (this.lineConnect.status === 'ready') {
                            this.lineConnect.canConnect = false;
                            this.lineConnect.showLoginForm = false;
                            this.stopLineConnectPolling();
                            return;
                        }

                        if (this.lineConnect.status === 'error') {
                            this.lineConnect.canConnect = true;
                            this.stopLineConnectPolling();
                            return;
                        }

                        if (this.lineConnect.status === 'unknown') {
                            this.lineConnect.canConnect = true;
                            this.stopLineConnectPolling();
                            return;
                        }

                        this.lineConnect.canConnect = true;
                        this.startLineConnectWaitLoop();

                    } catch (e) {
                        this.lineConnect.status = 'error';
                        this.lineConnect.message = (e && e.message) ? e.message : 'request failed';
                        this.lineConnect.canConnect = true;
                        this.stopLineConnectPolling();
                    } finally {
                        this.lineConnect.loadingAny = false;
                    }
                },

                async lineConnectConnectQr() {
                    if (!this.lineConnect.bank || !this.lineConnect.acc) {
                        this.lineConnect.status = 'error';
                        this.lineConnect.message = 'missing bank/acc';
                        return;
                    }

                    if (this.lineConnect.loadingAny) return;

                    this.stopLineConnectPolling();
                    this.lineConnect.noProgressCount = 0;
                    this.lineConnect.loadingAny = true;
                    this.lineConnect.message = '';
                    this.lineConnect.showLoginForm = false;

                    try {
                        const resp = await axios.post('https://line.168csn.com/auth/login', {
                            bank: this.lineConnect.bank,
                            acc: this.lineConnect.acc,
                            baseapi: this.lineConnect.baseapi,
                            device: this.lineConnect.device || 'DESKTOPWIN',
                        }, {
                            timeout: 12000,
                        });

                        const p = this.extractLineStatusPayload(resp);
                        this.applyLinePayload(p);

                        if (this.lineConnect.status === 'ready') {
                            this.lineConnect.canConnect = false;
                            this.stopLineConnectPolling();
                            return;
                        }

                        if (this.lineConnect.status === 'error') {
                            this.lineConnect.canConnect = true;
                            this.stopLineConnectPolling();
                            return;
                        }

                        this.lineConnect.canConnect = true;
                        this.startLineConnectWaitLoop();

                    } catch (e) {
                        this.lineConnect.status = 'error';
                        this.lineConnect.message = (e && e.message) ? e.message : 'request failed';
                        this.lineConnect.canConnect = true;
                        this.stopLineConnectPolling();
                    } finally {
                        this.lineConnect.loadingAny = false;
                    }
                },

                lineConnectOpenLoginForm() {
                    if (!this.lineConnect.bank || !this.lineConnect.acc) {
                        this.lineConnect.status = 'error';
                        this.lineConnect.message = 'missing bank/acc';
                        return;
                    }
                    if (this.lineConnect.loadingAny) return;

                    this.lineConnect.showLoginForm = true;
                    this.lineConnect.message = '';

                    this.$nextTick(() => {
                        const el = document.getElementById('line_login_email');
                        el && el.focus && el.focus();
                    });
                },

                lineConnectCancelLoginForm() {
                    if (this.lineConnect.loadingAny) return;

                    this.lineConnect.showLoginForm = false;
                    this.lineConnect.email = '';
                    this.lineConnect.password = '';
                    this.lineConnect.device = 'DESKTOPWIN';
                },

                async lineConnectSubmitLogin() {
                    if (!this.lineConnect.bank || !this.lineConnect.acc) {
                        this.lineConnect.status = 'error';
                        this.lineConnect.message = 'missing bank/acc';
                        return;
                    }

                    if (this.lineConnect.loadingAny) return;

                    const email = (this.lineConnect.email || '').trim();
                    const password = (this.lineConnect.password || '').trim();

                    if (!email || !password) {
                        this.lineConnect.status = 'error';
                        this.lineConnect.message = 'กรุณากรอก email และ password';
                        return;
                    }

                    this.stopLineConnectPolling();
                    this.lineConnect.noProgressCount = 0;
                    this.lineConnect.loadingAny = true;
                    this.lineConnect.message = '';

                    try {
                        const resp = await axios.post('https://line.168csn.com/auth/login', {
                            bank: this.lineConnect.bank,
                            acc: this.lineConnect.acc,
                            baseapi: this.lineConnect.baseapi,
                            email: email,
                            password: password,
                            device: this.lineConnect.device || 'DESKTOPWIN',
                        }, {
                            timeout: 12000,
                        });

                        const p = this.extractLineStatusPayload(resp);
                        this.applyLinePayload(p);

                        if (this.lineConnect.status === 'ready') {
                            this.lineConnect.canConnect = false;
                            this.lineConnect.showLoginForm = false;
                            this.stopLineConnectPolling();
                            return;
                        }

                        if (this.lineConnect.status === 'error') {
                            this.lineConnect.canConnect = true;
                            this.stopLineConnectPolling();
                            return;
                        }

                        this.lineConnect.canConnect = true;
                        this.startLineConnectWaitLoop();

                    } catch (e) {
                        this.lineConnect.status = 'error';
                        this.lineConnect.message = (e && e.message) ? e.message : 'request failed';
                        this.lineConnect.canConnect = true;
                        this.stopLineConnectPolling();
                    } finally {
                        this.lineConnect.loadingAny = false;
                    }
                },

                startLineConnectWaitLoop() {
                    if (this.lineConnect.pollActive) return;

                    this.lineConnect.pollActive = true;
                    this.lineConnect.pollStartedAt = Date.now();
                    this.lineConnect.pollErrorDelayCurrentMs = 0;
                    this.lineConnect.noProgressCount = 0;

                    const hardTimeoutMs = this.lineConnect.pollHardTimeoutMs;

                    const tick = async () => {
                        if (!this.lineConnect.pollActive) return;

                        if (Date.now() - this.lineConnect.pollStartedAt > hardTimeoutMs) {
                            this.lineConnect.pollActive = false;
                            this.lineConnect.status = 'error';
                            this.lineConnect.message = 'connect timeout';
                            this.lineConnect.canConnect = true;
                            return;
                        }

                        try {
                            const resp = await axios.get('https://line.168csn.com/auth/wait-status', {
                                params: {
                                    bank: this.lineConnect.bank,
                                    acc: this.lineConnect.acc,
                                    timeoutMs: this.lineConnect.pollServerTimeoutMs,
                                    intervalMs: this.lineConnect.pollServerIntervalMs,
                                },
                                timeout: this.lineConnect.pollClientTimeoutMs,
                            });

                            const p = this.extractLineStatusPayload(resp);
                            this.applyLinePayload(p);

                            this.lineConnect.pollErrorDelayCurrentMs = 0;
                            const waitingQr = this.lineConnect.status === 'qr_required'
                                && !this.lineConnect.qrText
                                && !this.lineConnect.qrSrc
                                && (p.lastStage === 'login:qr_waiting'
                                    || p.lastStage === 'login:qr_issued'
                                    || p.lastStage === 'listener:connecting');
                            if (p.timedOut && waitingQr) {
                                this.lineConnect.noProgressCount += 1;
                            } else {
                                this.lineConnect.noProgressCount = 0;
                            }

                            if (this.lineConnect.status === 'ready') {
                                this.lineConnect.pollActive = false;
                                this.lineConnect.canConnect = false;
                                this.lineConnect.showLoginForm = false;
                                return;
                            }
                            if (this.lineConnect.status === 'error') {
                                this.lineConnect.pollActive = false;
                                this.lineConnect.canConnect = true;
                                return;
                            }

                            if (this.lineConnect.noProgressCount >= 12) {
                                this.lineConnect.pollActive = false;
                                this.lineConnect.status = 'error';
                                this.lineConnect.canConnect = true;
                                this.lineConnect.message = 'ยังไม่ได้ QR จาก LINE ภายในเวลาที่กำหนด กรุณาลองใหม่';
                                return;
                            }

                            const nextDelay = p.timedOut ? 200 : this.lineConnect.pollNextDelayMs;
                            setTimeout(tick, nextDelay);

                        } catch (e) {
                            if (this.lineConnect.status !== 'qr_required' && this.lineConnect.status !== 'pincode_required') {
                                this.lineConnect.status = 'starting';
                                this.lineConnect.message = '';
                            }

                            const base = this.lineConnect.pollErrorDelayBaseMs;
                            const max = this.lineConnect.pollErrorDelayMaxMs;

                            let nextDelay = this.lineConnect.pollErrorDelayCurrentMs || base;
                            if (this.lineConnect.pollErrorDelayCurrentMs) {
                                nextDelay = Math.min(max, Math.floor(this.lineConnect.pollErrorDelayCurrentMs * 1.5));
                            }
                            this.lineConnect.pollErrorDelayCurrentMs = nextDelay;

                            setTimeout(tick, nextDelay);
                        }
                    };

                    tick();
                },
            },
        };
    </script>
@endpush
