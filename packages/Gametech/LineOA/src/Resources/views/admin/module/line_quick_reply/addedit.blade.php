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

        {{-- DESCRIPTION (ใช้เป็น "ชื่อ" และ seed สำหรับ gen key) --}}
        <b-form-group
                id="input-group-description"
                label="ชื่อ:"
                label-for="description"
                description="">
            <b-form-input
                    id="description"
                    v-model="formaddedit.description"
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
                        // key จะไม่ให้กรอก แต่เก็บไว้ใช้ยิง API
                        key: null,
                        message: '',
                        description: '',
                        enabled: true,
                    },
                    option: {
                        category: [
                            {value: 'quick_reply', text: 'ข้อความด่วน'},
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
                        key: null,
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
                        key: null,
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

                    this.formaddedit.category     = data.category || 'quick_reply';
                    this.formaddedit.key          = data.key || null;          // เก็บ key เดิมไว้ใช้ตอน update
                    this.formaddedit.message      = data.message || '';
                    this.formaddedit.description  = data.description || '';

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

                /**
                 * สร้าง slug จาก description (รองรับไทย + อังกฤษ)
                 */
                slugifyQuickReply(text) {
                    let slug = (text || '').toString().trim();

                    // space -> underscore
                    slug = slug.replace(/\s+/g, '_');

                    // เก็บเฉพาะ ก-๙ a-z A-Z 0-9 _
                    slug = slug.replace(/[^ก-๙a-zA-Z0-9_]/g, '');

                    // ลด _ ซ้ำ ๆ
                    slug = slug.replace(/_+/g, '_');

                    // ตัด _ ต้น–ท้าย
                    slug = slug.replace(/^_+|_+$/g, '');

                    return slug;
                },

                /**
                 * gen key อัตโนมัติ
                 * ตัวอย่าง: quick_reply.โปรฝากแรก_1733530000000
                 */
                generateQuickReplyKey(category, description) {
                    const cat = (category || 'quick_reply').trim() || 'quick_reply';

                    let slug = this.slugifyQuickReply(description || '');

                    if (!slug) {
                        slug = 'ข้อความด่วน';
                    }

                    // ตัดความยาวกันยาวเว่อร์
                    slug = slug.substring(0, 40);

                    const ts = Date.now(); // millisecond timestamp กันชนกันเบิ้ล

                    return `${cat}.${slug}_${ts}`;
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

                    const category    = (this.formaddedit.category || 'quick_reply').trim() || 'quick_reply';
                    let   key         = (this.formaddedit.key || '').trim();
                    const description = (this.formaddedit.description || '').trim();
                    const message     = (this.formaddedit.message || '').trim();

                    // กัน user ลืมใส่ข้อมูลหลัก
                    if (!description || !message) {
                        this.toggleButtonDisable(false);
                        this.$bvModal.msgBoxOk('กรุณากรอกชื่อ และข้อความให้ครบ', {
                            title: 'ข้อมูลไม่ครบ',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'warning',
                            centered: true,
                        });
                        return;
                    }

                    // ถ้าเป็น add หรือ key ว่าง → gen key ใหม่
                    if (this.formmethod === 'add' || !key) {
                        key = this.generateQuickReplyKey(category, description || message);
                    }

                    const payload = Object.assign({}, this.formaddedit, {
                        key: key,
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
