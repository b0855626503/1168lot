<b-modal ref="addedit" id="addedit" centered size="md" title="รายการหวย" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.prevent="addEditSubmit" v-if="show">
        <b-form-group label="กลุ่มหวย:" label-for="group_id">
            <b-form-select
                id="group_id"
                v-model="formaddedit.group_id"
                :options="option.groups"
                size="sm"
                required
            ></b-form-select>
        </b-form-group>
        <b-form-group label="ชื่อรายการหวย:" label-for="name" description="เช่น ออมสิน, ธกส, ดาวโจนส์">
            <b-form-input
                id="name"
                v-model="formaddedit.name"
                type="text"
                size="sm"
                placeholder="ชื่อรายการหวย"
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>
        <b-row>
            <b-col cols="12" md="6">
                <b-form-group label="ชื่ออังกฤษ:" label-for="name_en">
                    <b-form-input id="name_en" v-model="formaddedit.name_en" type="text" size="sm" autocomplete="off"></b-form-input>
                </b-form-group>
            </b-col>
            <b-col cols="12" md="6">
                <b-form-group label="ชื่อเขมร:" label-for="name_kh">
                    <b-form-input id="name_kh" v-model="formaddedit.name_kh" type="text" size="sm" autocomplete="off"></b-form-input>
                </b-form-group>
            </b-col>
        </b-row>
        <b-row>
            <b-col cols="12" md="6">
                <b-form-group label="ชื่อลาว:" label-for="name_laos">
                    <b-form-input id="name_laos" v-model="formaddedit.name_laos" type="text" size="sm" autocomplete="off"></b-form-input>
                </b-form-group>
            </b-col>
            <b-col cols="12" md="6">
                <b-form-group label="Code:" label-for="code" description="ตัวอักษรภาษาอังกฤษ ตัวเลข หรือ underscore">
                    <b-form-input
                        id="code"
                        v-model="formaddedit.code"
                        type="text"
                        size="sm"
                        placeholder="เช่น gsb, kbank"
                        autocomplete="off"
                        required
                    ></b-form-input>
                </b-form-group>
            </b-col>
        </b-row>
        <b-form-group label="โหมดงวด:" label-for="draw_mode" description="manual = ทีมงานสร้างงวดเอง, daily = ทุกวัน, weekdays = จันทร์-ศุกร์">
            <b-form-select id="draw_mode" v-model="formaddedit.draw_mode" :options="option.drawModes" size="sm"></b-form-select>
        </b-form-group>
        <b-row>
            <b-col cols="12" md="4">
                <b-form-group label="เวลาเปิดรับอัตโนมัติ:" label-for="auto_open_time">
                    <b-form-input id="auto_open_time" v-model="formaddedit.auto_open_time" type="time" size="sm"></b-form-input>
                </b-form-group>
            </b-col>
            <b-col cols="12" md="4">
                <b-form-group label="เวลาปิดรับอัตโนมัติ:" label-for="auto_close_time">
                    <b-form-input id="auto_close_time" v-model="formaddedit.auto_close_time" type="time" size="sm"></b-form-input>
                </b-form-group>
            </b-col>
            <b-col cols="12" md="4">
                <b-form-group label="เวลาออกผลอัตโนมัติ:" label-for="auto_result_time">
                    <b-form-input id="auto_result_time" v-model="formaddedit.auto_result_time" type="time" size="sm"></b-form-input>
                </b-form-group>
            </b-col>
        </b-row>
        <b-form-group label="ลิงก์ออกผล:" label-for="result_url">
            <b-form-input id="result_url" v-model="formaddedit.result_url" type="url" size="sm" placeholder="https://..."></b-form-input>
        </b-form-group>
        <b-row>
            <b-col cols="12" md="6">
                <b-form-group label="Logo URL:" label-for="logo">
                    <b-form-input id="logo" v-model="formaddedit.logo" type="text" size="sm" autocomplete="off" placeholder="/storage/... หรือ URL"></b-form-input>
                    <b-form-file class="mt-2" size="sm" v-model="formaddedit.logo_file" accept="image/jpeg,image/png,image/gif,image/webp" placeholder="อัปโหลด Logo"></b-form-file>
                    <a v-if="formaddedit.logo" :href="formaddedit.logo" target="_blank" class="d-inline-block mt-1">ดูรูปปัจจุบัน</a>
                </b-form-group>
            </b-col>
            <b-col cols="12" md="6">
                <b-form-group label="Icon URL:" label-for="icon">
                    <b-form-input id="icon" v-model="formaddedit.icon" type="text" size="sm" autocomplete="off" placeholder="/storage/... หรือ URL"></b-form-input>
                    <b-form-file class="mt-2" size="sm" v-model="formaddedit.icon_file" accept="image/jpeg,image/png,image/gif,image/webp" placeholder="อัปโหลด Icon"></b-form-file>
                    <a v-if="formaddedit.icon" :href="formaddedit.icon" target="_blank" class="d-inline-block mt-1">ดูรูปปัจจุบัน</a>
                </b-form-group>
            </b-col>
        </b-row>
        <b-form-group>
            <b-form-checkbox v-model="formaddedit.is_enabled" :value="1" :unchecked-value="0">
                เปิดใช้งาน
            </b-form-checkbox>
        </b-form-group>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <b-button type="submit" variant="primary" size="sm">บันทึก</b-button>
            </div>
        </div>
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
                    formaddedit: {
                        group_id:   '',
                        name:       '',
                        name_en:    '',
                        name_kh:    '',
                        name_laos:  '',
                        logo:       '',
                        icon:       '',
                        logo_file:  null,
                        icon_file:  null,
                        code:       '',
                        draw_mode: 'manual',
                        auto_open_time: '',
                        auto_close_time: '',
                        auto_result_time: '',
                        result_url: '',
                        is_enabled: 1,
                    },
                    option: {
                        groups: [
                            { value: '', text: '-- เลือกกลุ่มหวย --' },
                            @foreach($groups as $g)
                            { value: {{ $g->id }}, text: '{{ $g->name }} ({{ $g->code }})' },
                            @endforeach
                        ],
                        drawModes: [
                            { value: 'manual', text: 'Manual (เพิ่มงวดเอง)' },
                            { value: 'daily', text: 'Auto ทุกวัน' },
                            { value: 'weekdays', text: 'Auto จันทร์-ศุกร์' },
                        ],
                    },
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            methods: {
                editdata(id, status, method) {
                    this.$bvModal.msgBoxConfirm('ต้องการเปลี่ยนสถานะหรือไม่?', {
                        title: 'ยืนยัน', size: 'sm', buttonSize: 'sm',
                        okVariant: 'danger', okTitle: 'ตกลง', cancelTitle: 'ยกเลิก',
                        centered: true,
                    }).then(value => {
                        if (!value) return;
                        this.$http.post("{{ route('admin.lotto.markets.edit') }}", { id, status, method })
                            .then(() => {
                                window.LaravelDataTables['dataTableBuilder'].draw(false);
                            });
                    });
                },
                editModal(id) {
                    this.code = null;
                    this.formaddedit = { group_id: '', name: '', name_en: '', name_kh: '', name_laos: '', logo: '', icon: '', logo_file: null, icon_file: null, code: '', draw_mode: 'manual', auto_open_time: '', auto_close_time: '', auto_result_time: '', result_url: '', is_enabled: 1 };
                    this.formmethod = 'edit';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.code = id;
                        this.loadData();
                        this.$refs.addedit.show();
                    });
                },
                addModal() {
                    this.code = null;
                    this.formaddedit = { group_id: '', name: '', name_en: '', name_kh: '', name_laos: '', logo: '', icon: '', logo_file: null, icon_file: null, code: '', draw_mode: 'manual', auto_open_time: '', auto_close_time: '', auto_result_time: '', result_url: '', is_enabled: 1 };
                    this.formmethod = 'add';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();
                    });
                },
                async loadData() {
                    const response = await axios.post("{{ route('admin.lotto.markets.loaddata') }}", { id: this.code });
                    const d = response.data.data;
                    this.formaddedit = {
                        group_id:   d.group_id,
                        name:       d.name,
                        name_en:    d.name_en || '',
                        name_kh:    d.name_kh || '',
                        name_laos:  d.name_laos || '',
                        logo:       d.logo || '',
                        icon:       d.icon || '',
                        logo_file:  null,
                        icon_file:  null,
                        code:       d.code,
                        draw_mode:  d.draw_mode || 'manual',
                        auto_open_time: d.auto_open_time ? String(d.auto_open_time).substring(0, 5) : '',
                        auto_close_time: d.auto_close_time ? String(d.auto_close_time).substring(0, 5) : '',
                        auto_result_time: d.auto_result_time ? String(d.auto_result_time).substring(0, 5) : '',
                        result_url: d.result_url || '',
                        is_enabled: d.is_enabled ? 1 : 0,
                    };
                },
                addEditSubmit() {
                    const validationMessage = this.validateAutoDrawConfig();
                    if (validationMessage) {
                        this.$bvModal.msgBoxOk(validationMessage, {
                            title: 'ข้อมูลไม่ครบ',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true,
                        });
                        return;
                    }

                    const url = this.formmethod === 'add'
                        ? "{{ route('admin.lotto.markets.create') }}"
                        : "{{ route('admin.lotto.markets.update') }}";
                    const formData = new FormData();
                    if (this.code) {
                        formData.append('id', this.code);
                    }

                    Object.keys(this.formaddedit)
                        .filter((key) => !['logo_file', 'icon_file'].includes(key))
                        .forEach((key) => {
                            formData.append(`data[${key}]`, this.formaddedit[key] ?? '');
                        });

                    if (this.formaddedit.logo_file) {
                        formData.append('logo_file', this.formaddedit.logo_file);
                    }

                    if (this.formaddedit.icon_file) {
                        formData.append('icon_file', this.formaddedit.icon_file);
                    }

                    axios.post(url, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                    })
                        .then(response => {
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm', buttonSize: 'sm', okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true,
                            });
                            this.$refs.addedit.hide();
                            window.LaravelDataTables['dataTableBuilder'].draw(false);
                        })
                        .catch(() => console.error('addEditSubmit error'));
                },
                validateAutoDrawConfig() {
                    const mode = this.formaddedit.draw_mode || 'manual';
                    const open = (this.formaddedit.auto_open_time || '').trim();
                    const close = (this.formaddedit.auto_close_time || '').trim();

                    if (mode === 'manual') {
                        return '';
                    }

                    if (!close) {
                        return 'โหมดงวดอัตโนมัติจำเป็นต้องระบุเวลาปิดรับ';
                    }

                    if (open && open >= close) {
                        return 'เวลาเปิดรับต้องน้อยกว่าเวลาปิดรับ';
                    }

                    return '';
                },
            },
        });

        window.addModal = function () { window.app.addModal(); };
        window.editModal = function (id) { window.app.editModal(id); };
    </script>
@endpush
