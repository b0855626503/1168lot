<b-modal ref="addedit" id="addedit" centered scrollable size="lg" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.prevent="addEditSubmit" v-if="show">

        {{-- CATEGORY --}}
        <b-form-group
                id="input-group-category"
                label="หมวดหมู่ข้อความ:"
                label-for="category"
                description="">
            <b-form-select
                    id="category"
                    name="category"
                    v-model="formaddedit.category"
                    :options="option.category"
                    size="sm"
                    required
            ></b-form-select>
        </b-form-group>

        {{-- KEY --}}
        <b-form-group
                id="input-group-key"
                label="คีย์:"
                label-for="key"
                description="ระบุ key สำหรับนำไปเรียกใช้ข้อความ (เช่น welcome_banner, topup_notice)">
            <b-form-input
                    id="key"
                    v-model="formaddedit.key"
                    type="text"
                    size="sm"
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        {{-- MESSAGE (TEXTAREA) --}}
        <b-form-group
                id="input-group-message"
                label="ข้อความ:"
                label-for="message"
                description="ระบุข้อความที่ต้องการแสดง">
            <b-form-textarea
                    id="message"
                    v-model="formaddedit.message"
                    ref="messageInput"
                    size="sm"
                    rows="3"
                    max-rows="6"
                    autocomplete="off"
                    required
            ></b-form-textarea>

            {{-- ปุ่มแทรก placeholder --}}
            <div class="mt-2">
                <span class="text-muted mr-2">ตัวแปรที่ใช้ได้:</span>

                <b-button-group size="sm">

                        <b-button
                                variant="outline-secondary"
                                @click.prevent="insertPlaceholder('{display_name}')"
                                title="ชื่อที่โชว์ใน LINE ของลูกค้า"
                        >
                            {ชื่อแชตไลน์}
                        </b-button>
                        <b-button
                                variant="outline-secondary"
                                @click.prevent="insertPlaceholder('{username}')"
                                title="UserName สำหรับ Login เวบ"
                        >
                            {ไอดีเข้าเวบ}
                        </b-button>
                        <b-button
                                variant="outline-secondary"
                                @click.prevent="insertPlaceholder('{phone}')"
                                title="เบอร์โทร ของลูกค้า"
                        >
                            {เบอร์โทร}
                        </b-button>
                        <b-button
                                variant="outline-secondary"
                                @click.prevent="insertPlaceholder('{bank_name}')"
                                title="ชื่อธนาคาร ของลูกค้า"
                        >
                            {ชื่อธนาคาร}
                        </b-button>
                        <b-button
                                variant="outline-secondary"
                                @click.prevent="insertPlaceholder('{account_no}')"
                                title="เลขที่บัญชี ของลูกค้า"
                        >
                            {เลขบัญชี}
                        </b-button>
                        <b-button
                                variant="outline-secondary"
                                @click.prevent="insertPlaceholder('{game_user}')"
                                title="Game ID ของลูกค้า"
                        >
                            {ไอดีเกม}
                        </b-button>
                        <b-button
                                variant="outline-secondary"
                                @click.prevent="insertPlaceholder('{site_name}')"
                                title="ชื่อ เวบไซต์"
                        >
                            {ชื่อเวบ}
                        </b-button>
                    <b-button
                            variant="outline-secondary"
                            @click.prevent="insertPlaceholder('{login_url}')"
                            title="หน้าเข้าระบบ login"
                    >
                        {ทางเข้าเล่น}
                    </b-button>
                    {{-- ถ้าอนาคตมีเพิ่มเช่น {username}, {amount} ก็เพิ่มปุ่มตรงนี้ได้ --}}
                </b-button-group>
            </div>
        </b-form-group>

        {{-- DESCRIPTION --}}
        <b-form-group
                id="input-group-description"
                label="คำอธิบาย:"
                label-for="description"
                description="คำอธิบายเพิ่มเติมสำหรับทีมงาน (ไม่จำเป็นต้องแสดงหน้าเว็บ)">
            <b-form-textarea
                    id="description"
                    v-model="formaddedit.description"
                    size="sm"
                    rows="2"
                    max-rows="4"
                    autocomplete="off"
            ></b-form-textarea>
        </b-form-group>

        {{-- ENABLED CHECKBOX --}}
        <b-form-group
                id="input-group-enabled"
                label="สถานะการใช้งาน:"
                label-for="enabled"
                description="">
            <b-form-checkbox
                    id="enabled"
                    v-model="formaddedit.enabled"
                    name="enabled"
                    switch
                    size="lg"
            >
                เปิดใช้งานข้อความนี้
            </b-form-checkbox>
        </b-form-group>

        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-modal>


@push('scripts')
    <script type="module">
        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: false,
                    formmethod: 'add',
                    code: null,
                    formaddedit: {
                        category: 'quick_reply',
                        key: '',
                        message: '',
                        description: '',
                        enabled: true,
                    },
                    option: {
                        category: [
                            {value: 'register', text: 'ข้อความบอทสมัคร'},
                            {value: 'welcome', text: 'ข้อความทักทายครั้งแรก'},
                        ]
                    },
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            methods: {
                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        category: 'quick_reply',
                        key: '',
                        message: '',
                        description: '',
                        enabled: true,
                    };

                    this.formmethod = 'edit';

                    this.show = false;
                    this.$nextTick(() => {
                        this.code = code;
                        this.loadData();
                        this.$refs.addedit.show();
                        this.show = true;
                    });
                },

                addModal() {
                    this.code = null;
                    this.formaddedit = {
                        category: 'quick_reply',
                        key: '',
                        message: '',
                        description: '',
                        enabled: true,
                    };

                    this.formmethod = 'add';

                    this.show = false;
                    this.$nextTick(() => {
                        this.$refs.addedit.show();
                        this.show = true;
                    });
                },

                async loadData() {
                    const response = await axios.post(
                        "{{ route('admin.'.$menu->currentRoute.'.loaddata') }}",
                        {id: this.code}
                    );

                    const data = response.data.data || {};

                    this.formaddedit.category = data.category || 'quick_reply';

                    // key ที่อยู่ใน DB เป็นแบบ category.key เช่น quick_reply.hello
                    // เวลาแสดงให้ user อยากให้เห็นแค่ส่วนหลัง (hello)
                    const fullKey   = data.key || '';
                    const category  = this.formaddedit.category || '';
                    let   shortKey  = fullKey;

                    if (fullKey && category && fullKey.indexOf(category + '.') === 0) {
                        shortKey = fullKey.substring((category + '.').length);
                    }

                    this.formaddedit.key = shortKey || '';

                    this.formaddedit.message     = data.message || '';
                    this.formaddedit.description = data.description || '';

                    // รองรับทั้งแบบ boolean, 0/1, 'Y'/'N'
                    const enabled = data.enabled;
                    this.formaddedit.enabled =
                        enabled === true ||
                        enabled === 1 ||
                        enabled === '1' ||
                        enabled === 'Y';
                },

                /**
                 * แทรก placeholder (เช่น {display_name}) ลงใน textarea
                 * โดยพยายามใส่ที่ตำแหน่งเคอร์เซอร์ ถ้าหาไม่ได้ให้ต่อท้ายข้อความ
                 */
                insertPlaceholder(token) {
                    const current = this.formaddedit.message || '';

                    // พยายามหาตัว textarea จริงจาก ref
                    let el = this.$refs.messageInput;
                    if (el && el.$el) {
                        // b-form-textarea เป็น component → ตัวจริงอยู่ใน $el
                        el = el.$el;
                    }

                    if (!el || !el.tagName || el.tagName.toLowerCase() !== 'textarea') {
                        // กันเหนียว ถ้า ref ไม่เจอ DOM element ก็แค่ต่อท้าย
                        this.formaddedit.message = current + token;
                        return;
                    }

                    const start = el.selectionStart != null ? el.selectionStart : current.length;
                    const end   = el.selectionEnd   != null ? el.selectionEnd   : current.length;

                    const before = current.substring(0, start);
                    const after  = current.substring(end);

                    this.formaddedit.message = before + token + after;

                    this.$nextTick(() => {
                        el.focus();
                        const pos = start + token.length;
                        el.selectionStart = pos;
                        el.selectionEnd   = pos;
                    });
                },

                addEditSubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    let url = '';
                    if (this.formmethod === 'add') {
                        url = "{{ route('admin.'.$menu->currentRoute.'.create') }}";
                    } else if (this.formmethod === 'edit') {
                        url = "{{ route('admin.'.$menu->currentRoute.'.update') }}";
                    }

                    const category = (this.formaddedit.category || '').trim();
                    let   rawKey   = (this.formaddedit.key || '').trim();

                    // prefix key ด้วย category. ถ้ายังไม่ได้ prefix
                    if (category && rawKey) {
                        const prefix = category + '.';
                        if (rawKey.indexOf(prefix) !== 0) {
                            rawKey = prefix + rawKey;
                        }
                    }

                    const payload = Object.assign({}, this.formaddedit, {
                        key: rawKey,
                        enabled: this.formaddedit.enabled ? 1 : 0,
                    });

                    this.$http.post(url, {id: this.code, data: payload})
                        .then(response => {
                            this.$refs.addedit.hide();

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
                        .catch(exception => {
                            console.log('error', exception);
                            this.toggleButtonDisable(false);
                        });
                }
            },
        });
    </script>
@endpush

