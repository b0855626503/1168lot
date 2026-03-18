<b-modal ref="addedit" id="addedit" centered scrollable size="lg" title="เพิ่มข้อมูล Line OA" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.prevent="addEditSubmit" v-if="show">

        {{-- Name --}}
        <b-form-group
                id="input-group-name"
                label="ชื่อเรียก OA ในระบบ ใส่ค่านี้เสร็จแล้วกด บันทึกไปก่อน แล้วกดแก้ไขเข้ามา:"
                label-for="name"
                description="เช่น ชื่อเวบที่ใช้ หรืออะไรก็ได้ ที่อยากตั้ง">
            <b-form-input
                    id="name"
                    v-model="formaddedit.name"
                    type="text"
                    size="sm"
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        <p>1. <a href="https://manager.line.biz" target="_blank">https://manager.line.biz</a> สำหรับสร้าง Line Official Account ให้กดที่นี่ แล้วสร้าง Line OA ก่อน</p>
        <p>2. พอสร้างเสร็จแล้ว ให้ เลือก Tab แชท <a href="{{ asset('vendor/line-oa/images/step1.png') }}" target="_blank">ดูภาพประกอบ 1</a></p>
        <p>3. กด Messaging API แล้วตั้งค่าจนเสร็จ <a href="{{ asset('vendor/line-oa/images/step2.png') }}" target="_blank">ดูภาพประกอบ 2</a> <a href="{{ asset('vendor/line-oa/images/step3.png') }}" target="_blank">ดูภาพประกอบ 3</a> จะได้ Channel ID และ Channel Secret แล้วกด Copy Webhook ไปแปะได้เลย</p>
        <p>4. กด ตั้งค่าเพิ่มเติมได้ที่ <a href="https://developers.line.biz/console" target="_blank">LINE Developers Console</a></p>
        <p>5. กดหา Line OA ที่เรา สร้างมา เมื่อสักครู่ แล้ว เลือก Messaging Api <a href="{{ asset('vendor/line-oa/images/step4.png') }}" target="_blank">ดูภาพประกอบ 4</a> แล้วเลื่อนหน้าจอไปล่างสุด จะเจอ Channel access token (long-lived) กด Issue จะได้ Key ให้เอามา ใส่ที่ Channel Access Token</p>


        {{-- Channel ID --}}
        <b-form-group
                id="input-group-channel-id"
                label="Channel ID:"
                label-for="channel_id"
                description="ระบุ Channel ID จาก LINE Developers ">
            <b-form-input
                    id="channel_id"
                    v-model="formaddedit.channel_id"
                    type="text"
                    size="sm"
                    autocomplete="off"
                    required
            ></b-form-input>

        </b-form-group>

        {{-- Channel Secret --}}
        <b-form-group
                id="input-group-channel-secret"
                label="Channel Secret:"
                label-for="channel_secret"
                description="ระบุ Channel Secret จาก LINE Developers">
            <b-form-input
                    id="channel_secret"
                    v-model="formaddedit.channel_secret"
                    type="text"
                    size="sm"
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        {{-- Access Token --}}
        <b-form-group
                id="input-group-access-token"
                label="Channel Access Token:"
                label-for="access_token"
                description="วาง long-lived access token (Messaging API)">
            <b-form-textarea
                    id="access_token"
                    v-model="formaddedit.access_token"
                    rows="3"
                    max-rows="6"
                    size="sm"
                    autocomplete="off"
            ></b-form-textarea>
        </b-form-group>

        {{-- Webhook URL (readonly, แสดงเต็มเป็น URL) --}}
        <b-form-group
                id="input-group-webhook-token"
                label="Webhook URL:"
                label-for="webhook_url"
                description="">
            <template v-if="formmethod === 'edit' && formaddedit.webhook_token">
                <b-input-group size="sm">
                    <b-form-input
                            id="webhook_url"
                            :value="fullWebhookUrl"
                            readonly
                    ></b-form-input>
                    <b-input-group-append>
                        <b-button variant="outline-secondary" @click="copyWebhookUrl">
                            คัดลอก
                        </b-button>
                    </b-input-group-append>
                </b-input-group>
                <small class="text-muted">
                    URL นี้ใช้ตั้งค่าในหน้า LINE Developers (Messaging API &gt; Webhook URL)
                </small>
            </template>
            <template v-else>
                <p class="mb-0">
                    <small class="text-muted">
                        Webhook token จะถูกสร้างอัตโนมัติหลังจากบันทึกข้อมูล OA แล้ว จากนั้นสามารถกลับมาเปิดหน้าจอนี้เพื่อคัดลอก URL ได้
                    </small>
                </p>
            </template>
        </b-form-group>

        {{-- Status --}}
        <b-form-group
                id="input-group-status"
                label="สถานะ OA:"
                label-for="status">
            <b-form-select
                    id="status"
                    v-model="formaddedit.status"
                    :options="[
                    { value: 'active', text: 'เปิดใช้งาน' },
                    { value: 'inactive', text: 'ปิดใช้งาน' },
                ]"
                    size="sm"
                    required
            ></b-form-select>
        </b-form-group>

        {{-- Remark --}}
        <b-form-group
                id="input-group-remark"
                label="หมายเหตุ:"
                label-for="remark"
                description="ระบุเพิ่มเติม เช่น ใช้กับเว็บอะไร, กลุ่มลูกค้าไหน">
            <b-form-textarea
                    id="remark"
                    v-model="formaddedit.remark"
                    rows="2"
                    max-rows="6"
                    size="sm"
                    autocomplete="off"
            ></b-form-textarea>
        </b-form-group>

        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-modal>


@push('scripts')

    <script type="module">
        window.WEBHOOK_BASE = @json(
            route('api.line-oa.webhook', ['token' => '__TOKEN__'])
        );
        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: false,
                    formmethod: 'add',
                    code: null,

                    // base URL สำหรับ webhook (ไม่เอา token)
                    webhookBaseUrl: window.WEBHOOK_BASE,

                    formaddedit: {
                        name: '',
                        channel_id: '',
                        channel_secret: '',
                        access_token: '',
                        webhook_token: '',
                        status: 'active',
                        remark: '',
                    },
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            computed: {
                fullWebhookUrl() {
                    if (!this.formaddedit.webhook_token) return '';

                    return this.webhookBaseUrl.replace('__TOKEN__', this.formaddedit.webhook_token);
                }
            },
            methods: {
                editModal(code) {
                    this.code = null;
                    this.resetForm();

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
                    this.resetForm();

                    this.formmethod = 'add';
                    this.show = false;

                    this.$nextTick(() => {
                        this.$refs.addedit.show();
                        this.show = true;
                    });
                },
                resetForm() {
                    this.formaddedit = {
                        name: '',
                        channel_id: '',
                        channel_secret: '',
                        access_token: '',
                        webhook_token: '',
                        status: 'active',
                        remark: '',
                    };
                },
                async loadData() {
                    const response = await axios.post(
                        "{{ route('admin.'.$menu->currentRoute.'.loaddata') }}",
                        { id: this.code }
                    );

                    const data = response.data.data || {};

                    this.formaddedit.name = data.name || '';
                    this.formaddedit.channel_id = data.channel_id || '';
                    this.formaddedit.channel_secret = data.channel_secret || '';
                    this.formaddedit.access_token = data.access_token || '';
                    this.formaddedit.webhook_token = data.webhook_token || '';
                    this.formaddedit.status = data.status || 'active';
                    this.formaddedit.remark = data.remark || '';
                },
                addEditSubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    let url;
                    if (this.formmethod === 'add') {
                        url = "{{ route('admin.'.$menu->currentRoute.'.create') }}";
                    } else if (this.formmethod === 'edit') {
                        url = "{{ route('admin.'.$menu->currentRoute.'.update') }}";
                    }

                    this.$http.post(url, { id: this.code, data: this.formaddedit })
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
                copyWebhookUrl() {
                    const text = this.fullWebhookUrl;
                    if (!text) return;

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(() => {
                            this.$bvToast && this.$bvToast.toast('คัดลอก Webhook URL แล้ว', {
                                title: 'สำเร็จ',
                                variant: 'success',
                                solid: true,
                                autoHideDelay: 2000,
                            });
                        }).catch(() => {
                            this.fallbackCopyText(text);
                        });
                    } else {
                        this.fallbackCopyText(text);
                    }
                },
                fallbackCopyText(text) {
                    const el = document.createElement('textarea');
                    el.value = text;
                    el.setAttribute('readonly', '');
                    el.style.position = 'absolute';
                    el.style.left = '-9999px';
                    document.body.appendChild(el);
                    el.select();
                    try {
                        document.execCommand('copy');
                        this.$bvToast && this.$bvToast.toast('คัดลอก Webhook URL แล้ว', {
                            title: 'สำเร็จ',
                            variant: 'success',
                            solid: true,
                            autoHideDelay: 2000,
                        });
                    } catch (e) {
                        console.error('ไม่สามารถคัดลอกข้อความได้', e);
                    }
                    document.body.removeChild(el);
                },
            },
        });
    </script>
@endpush
