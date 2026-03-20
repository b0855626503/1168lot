<b-modal ref="addedit" id="addedit" centered size="sm" title="รายการหวย" :no-stacking="true"
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
        <b-form-group>
            <b-form-checkbox v-model="formaddedit.is_enabled" :value="1" :unchecked-value="0">
                เปิดใช้งาน
            </b-form-checkbox>
        </b-form-group>
        <b-button type="submit" variant="primary" size="sm">บันทึก</b-button>
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
                        code:       '',
                        is_enabled: 1,
                    },
                    option: {
                        groups: [
                            { value: '', text: '-- เลือกกลุ่มหวย --' },
                            @foreach($groups as $g)
                            { value: {{ $g->id }}, text: '{{ $g->name }} ({{ $g->code }})' },
                            @endforeach
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
                    this.formaddedit = { group_id: '', name: '', code: '', is_enabled: 1 };
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
                    this.formaddedit = { group_id: '', name: '', code: '', is_enabled: 1 };
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
                        code:       d.code,
                        is_enabled: d.is_enabled ? 1 : 0,
                    };
                },
                addEditSubmit() {
                    const url = this.formmethod === 'add'
                        ? "{{ route('admin.lotto.markets.create') }}"
                        : "{{ route('admin.lotto.markets.update') }}";
                    this.$http.post(url, { id: this.code, data: this.formaddedit })
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
            },
        });
    </script>
@endpush
