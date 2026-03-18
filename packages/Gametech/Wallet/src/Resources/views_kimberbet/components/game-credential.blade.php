<script type="text/x-template" id="game-credential-modal-template">
    <div>
        <!-- Modal: Credential -->
        <div class="modal modal-custom fade"
             id="gameCredentialModal"
             ref="gameCredentialModal"
             data-bs-backdrop="static"
             data-bs-keyboard="false"
             tabindex="-1"
             aria-labelledby="gameCredentialLabel"
             aria-hidden="true"
             data-bs-focus="false">

            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content gt-cred-modal">

                    <!-- ปุ่มปิดมุมขวาบน -->
                    <button type="button"
                            class="gt-cred-close"
                            aria-label="Close"
                            @click="closeCredential">
                        ×
                    </button>

                    <div class="modal-body gt-cred-body text-center">

                        <!-- Logo -->
                        <div class="gt-cred-logo">
                            <img v-if="logoUrl" :src="logoUrl" alt="Game Logo">
                        </div>

                        <!-- Title -->
                        <h5 id="gameCredentialLabel" class="gt-cred-title" v-text="title"></h5>

                        <!-- Loading -->
                        <div v-if="loading" class="gt-cred-loading" v-text="loadingText"></div>

                        <!-- Credential rows -->
                        <div class="gt-cred-table" v-else>
                            <div class="gt-cred-row">
                                <div class="gt-cred-label" v-text="labels.username"></div>
                                <div class="gt-cred-value" v-text="username"></div>
                                <button type="button"
                                        class="gt-cred-copy"
                                        :disabled="!username"
                                        @click="copy(username, 'username')">
                                    [คัดลอก]
                                </button>
                            </div>

                            <div class="gt-cred-row">
                                <div class="gt-cred-label" v-text="labels.password"></div>
                                <div class="gt-cred-value" v-text="password"></div>
                                <button type="button"
                                        class="gt-cred-copy"
                                        :disabled="!password"
                                        @click="copy(password, 'password')">
                                    [คัดลอก]
                                </button>
                            </div>
                        </div>

                        <!-- actions -->
                        <div class="gt-cred-actions">
                            <a class="btn gt-cred-btn gt-cred-ios"
                               :class="{ 'disabled': loading || !iosUrl }"
                               :href="iosUrl || '#'"
                               target="_blank"
                               rel="noopener"
                               @click.prevent="openLink(iosUrl)">
                                iOS
                            </a>

                            <a class="btn gt-cred-btn gt-cred-android"
                               :class="{ 'disabled': loading || !androidUrl }"
                               :href="androidUrl || '#'"
                               target="_blank"
                               rel="noopener"
                               @click.prevent="openLink(androidUrl)">
                                Android
                            </a>

                            <!-- NEW: Change Password -->
                            <button type="button"
                                    class="btn gt-cred-btn gt-cred-change"
                                    :disabled="loading || !gameId"
                                    @click="openChangePasswordFlow">
                                เปลี่ยนรหัส
                            </button>
                        </div>

                        <div class="gt-cred-hint" v-if="copiedHint" v-text="copiedHint"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Change Password (แบบในภาพ) -->
        <div class="modal modal-custom fade"
             id="gameChangePasswordModal"
             ref="gameChangePasswordModal"
             data-bs-backdrop="static"
             data-bs-keyboard="false"
             tabindex="-1"
             aria-labelledby="gameChangePasswordLabel"
             aria-hidden="true"
             data-bs-focus="false">

            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content gt-chg-modal">

                    <!-- ปุ่มปิดมุมขวาบน (ถ้าต้องการให้เหมือนภาพเป๊ะ: ปิดด้วย ยกเลิกอย่างเดียว ก็ลบปุ่มนี้ได้) -->
                    <button type="button"
                            class="gt-cred-close"
                            aria-label="Close"
                            @click="cancelChangePassword">
                        ×
                    </button>

                    <div class="modal-body gt-chg-body text-center">
                        <div class="gt-cred-logo">
                            <img v-if="logoUrl" :src="logoUrl" alt="Game Logo">
                        </div>

                        <h5 id="gameChangePasswordLabel" class="gt-chg-title" v-text="changeTitle"></h5>

                        <div class="gt-chg-label" v-text="labels.password"></div>

                        <input type="password"
                               class="form-control gt-chg-input"
                               autocomplete="new-password"
                               :disabled="changing"
                               v-model="newPassword"
                               maxlength="10"
                               placeholder="Enter your password"
                               @keyup.enter="submitChangePassword"
                        >

                        <div class="gt-chg-actions">
                            <button type="button"
                                    class="btn gt-chg-ok"
                                    :disabled="changing || !newPassword"
                                    @click="submitChangePassword">
                                ตกลง
                            </button>
                            <button type="button"
                                    class="btn gt-chg-cancel"
                                    :disabled="changing"
                                    @click="cancelChangePassword">
                                ยกเลิก
                            </button>
                        </div>

                        <div class="gt-chg-hint" v-if="changeHint" v-text="changeHint"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

@push('components')
    <script type="module">
        Vue.component('game-credential-modal', {
            template: '#game-credential-modal-template',
            data() {
                return {
                    // ui state
                    loading: false,
                    loadingText: 'กำลังโหลดข้อมูล...',

                    // display data
                    title: 'ข้อมูลของเกม',
                    gameName: '',
                    logoUrl: '',
                    username: '',
                    password: '',
                    iosUrl: '',
                    androidUrl: '',
                    webUrl: '',
                    gameId: null,

                    // change password modal state
                    newPassword: '',
                    changing: false,
                    changeHint: '',

                    labels: {
                        username: 'Username',
                        password: 'Password',
                    },

                    copiedHint: '',
                    _hintTimer: null,
                    _hintTimer2: null,

                    // routes
                    profileRoute: "{{ route('customer.profile.view') }}",
                    changeRoute: "{{ route('customer.profile.change') }}",

                    // image base
                    gameImageBaseUrl: "/storage/game_img/",
                };
            },
            computed: {
                changeTitle() {
                    // ให้เหมือนภาพ: “คุณต้องการเปลี่ยนรหัสผ่าน เกม 918Kiss หรือไม่”
                    const name = this.gameName || '';
                    return name
                        ? ('คุณต้องการเปลี่ยนรหัสผ่าน เกม ' + name + ' หรือไม่')
                        : 'คุณต้องการเปลี่ยนรหัสผ่านหรือไม่';
                }
            },
            methods: {
                waitHiddenOnce(el) {
                    return new Promise(resolve => {
                        const handler = () => {
                            el.removeEventListener('hidden.bs.modal', handler);
                            resolve();
                        };
                        el.addEventListener('hidden.bs.modal', handler, { once: true });
                    });
                },

                // -------- Credential Modal --------
                showCredential() {
                    const el = this.$refs.gameCredentialModal;
                    if (!el) return;
                    bootstrap.Modal.getOrCreateInstance(el).show();
                },

                closeCredential() {
                    const el = this.$refs.gameCredentialModal;
                    if (!el) return;
                    const m = bootstrap.Modal.getInstance(el) || bootstrap.Modal.getOrCreateInstance(el);
                    m.hide();

                    // ลดการค้างรหัสใน DOM (ถ้าคุณอยากให้ค้างไว้ ให้คอมเมนต์ทิ้ง)
                    // this.password = '';
                    this.copiedHint = '';
                },

                // เปิดแบบยิง API แล้วโชว์
                async showByGameId(gameId) {
                    this.gameId = gameId;

                    // reset
                    this.loading = true;
                    this.copiedHint = '';
                    this.username = '';
                    this.password = '';
                    this.iosUrl = '';
                    this.androidUrl = '';
                    this.webUrl = '';
                    this.logoUrl = '';
                    this.title = 'ข้อมูลของเกม';
                    this.gameName = '';

                    // เปิด modal ก่อนให้เห็น loading
                    this.showCredential();

                    const axios$ = (window.axios || window.Axios || axios);

                    try {
                        const resp = await axios$.post(this.profileRoute, { id: gameId });
                        const data = resp?.data || {};

                        if (!data.success) {
                            this.closeCredential();
                            Swal?.fire?.('ไม่สำเร็จ', data.message || 'ไม่สามารถดึงข้อมูลได้', 'error');
                            return;
                        }

                        this.applyResponse(data);

                    } catch (err) {
                        this.closeCredential();
                        Swal?.fire?.('ผิดพลาด', 'เชื่อมต่อไม่สำเร็จ กรุณาลองใหม่', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                applyResponse(data) {
                    this.username = data.user_name || '';
                    this.password = data.user_pass || '';

                    const g = data.game || {};
                    this.gameName = g.name || '';
                    this.title = this.gameName ? ('ข้อมูลของเกม ' + this.gameName) : 'ข้อมูลของเกม';

                    if (g.filepic) {
                        this.logoUrl = this.gameImageBaseUrl + String(g.filepic).replace(/^\/+/, '');
                    } else {
                        this.logoUrl = '';
                    }

                    this.iosUrl = g.link_ios || '';
                    this.androidUrl = g.link_android || '';
                    this.webUrl = g.link_web || '';
                },

                openLink(url) {
                    if (!url || this.loading) return;
                    window.open(url, '_blank', 'noopener');
                },

                async copy(text, which) {
                    if (!text || this.loading) return;

                    try {
                        const value = String(text);

                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(value);
                        } else {
                            const el = document.createElement('textarea');
                            el.value = value;
                            el.setAttribute('readonly', '');
                            el.style.position = 'fixed';
                            el.style.top = '-9999px';
                            el.style.left = '-9999px';
                            document.body.appendChild(el);
                            el.select();
                            document.execCommand('copy');
                            document.body.removeChild(el);
                        }

                        this.copiedHint = which === 'username'
                            ? 'คัดลอก Username แล้ว'
                            : 'คัดลอก Password แล้ว';

                        clearTimeout(this._hintTimer);
                        this._hintTimer = setTimeout(() => { this.copiedHint = ''; }, 1200);

                    } catch (e) {
                        this.copiedHint = 'คัดลอกไม่สำเร็จ (ลองคัดลอกเอง)';
                        clearTimeout(this._hintTimer);
                        this._hintTimer = setTimeout(() => { this.copiedHint = ''; }, 1600);
                    }
                },

                // -------- Change Password Modal Flow --------
                async openChangePasswordFlow() {
                    // ซ่อน credential ก่อน แล้วค่อยเปิด change modal (กัน backdrop เพี้ยน)
                    const credEl = this.$refs.gameCredentialModal;
                    if (credEl) {
                        const inst = bootstrap.Modal.getInstance(credEl);
                        if (inst) {
                            const hidden = this.waitHiddenOnce(credEl);
                            inst.hide();
                            await hidden;
                        }
                    }

                    // reset change modal state
                    this.newPassword = '';
                    this.changeHint = '';

                    // เปิด change modal
                    const chgEl = this.$refs.gameChangePasswordModal;
                    if (!chgEl) return;
                    bootstrap.Modal.getOrCreateInstance(chgEl).show();

                    // โฟกัส input หลังเปิด (UX ดีขึ้น)
                    this.$nextTick(() => {
                        try {
                            const input = chgEl.querySelector('input[type="password"]');
                            input && input.focus();
                        } catch (e) {}
                    });
                },

                async cancelChangePassword() {
                    // ปิด change modal แล้วกลับไป credential
                    const chgEl = this.$refs.gameChangePasswordModal;
                    if (chgEl) {
                        const inst = bootstrap.Modal.getInstance(chgEl);
                        if (inst) {
                            const hidden = this.waitHiddenOnce(chgEl);
                            inst.hide();
                            await hidden;
                        }
                    }
                    this.showCredential();
                },

                async submitChangePassword() {
                    if (this.changing) return;
                    if (!this.gameId) {
                        this.changeHint = 'ไม่พบรหัสเกม (gameId)';
                        return;
                    }
                    if (!this.newPassword) return;

                    this.changing = true;
                    this.changeHint = 'กำลังบันทึก...';

                    const axios$ = (window.axios || window.Axios || axios);

                    try {
                        const resp = await axios$.post(this.changeRoute, {
                            id: this.gameId,
                            password: this.newPassword
                        });

                        const data = resp?.data || {};
                        if (!data.success) {
                            this.changeHint = data.message || 'เปลี่ยนรหัสไม่สำเร็จ';
                            Swal?.fire?.('ไม่สำเร็จ', this.changeHint, 'error');
                            return;
                        }

                        // สำเร็จ: อัปเดตรหัสใน modal แรก (ถ้าต้องการ)
                        this.password = this.newPassword;

                        this.changeHint = data.message || 'บันทึกสำเร็จ';
                        Swal?.fire?.('สำเร็จ', this.changeHint, 'success');

                        // ปิด change modal แล้วกลับไป credential
                        const chgEl = this.$refs.gameChangePasswordModal;
                        if (chgEl) {
                            const inst = bootstrap.Modal.getInstance(chgEl) || bootstrap.Modal.getOrCreateInstance(chgEl);
                            const hidden = this.waitHiddenOnce(chgEl);
                            inst.hide();
                            await hidden;
                        }

                        // เคลียร์ newPassword ทิ้งหลังใช้ (ลดค้างใน memory/DOM)
                        this.newPassword = '';

                        this.showCredential();

                    } catch (err) {
                        this.changeHint = 'เชื่อมต่อไม่สำเร็จ กรุณาลองใหม่';
                        Swal?.fire?.('ผิดพลาด', this.changeHint, 'error');
                    } finally {
                        this.changing = false;

                        // เคลียร์ hint อัตโนมัติเล็กน้อย (optional)
                        clearTimeout(this._hintTimer2);
                        this._hintTimer2 = setTimeout(() => { this.changeHint = ''; }, 1800);
                    }
                },
            },
            beforeDestroy() {
                clearTimeout(this._hintTimer);
                clearTimeout(this._hintTimer2);
            }
        });
    </script>
@endpush


