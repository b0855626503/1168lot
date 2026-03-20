<b-modal ref="addedit" id="addedit" centered size="sm" title="กลุ่มหวย" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.prevent="addEditSubmit" v-if="show">
        <b-form-group label="ชื่อกลุ่ม:" label-for="name" description="ระบุชื่อกลุ่มหวย เช่น หวยไทย">
            <b-form-input
                id="name"
                v-model="formaddedit.name"
                type="text"
                size="sm"
                placeholder="ชื่อกลุ่ม"
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
                placeholder="เช่น thailand, stock"
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>
        <b-form-group label="Sort:" label-for="sort">
            <b-form-input
                id="sort"
                v-model="formaddedit.sort"
                type="number"
                min="0"
                size="sm"
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
                        name: '',
                        code: '',
                        sort: 0,
                        is_enabled: 1,
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
                        this.$http.post("{{ route('admin.lotto.groups.edit') }}", { id, status, method })
                            .then(response => {
                                window.LaravelDataTables['dataTableBuilder'].draw(false);
                            });
                    });
                },
                editModal(id) {
                    this.code = null;
                    this.formaddedit = { name: '', code: '', sort: 0, is_enabled: 1 };
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
                    this.formaddedit = { name: '', code: '', sort: 0, is_enabled: 1 };
                    this.formmethod = 'add';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();
                    });
                },
                async loadData() {
                    const response = await axios.post("{{ route('admin.lotto.groups.loaddata') }}", { id: this.code });
                    const d = response.data.data;
                    this.formaddedit = {
                        name:       d.name,
                        code:       d.code,
                        sort:       d.sort,
                        is_enabled: d.is_enabled ? 1 : 0,
                    };
                },
                addEditSubmit(event) {
                    const url = this.formmethod === 'add'
                        ? "{{ route('admin.lotto.groups.create') }}"
                        : "{{ route('admin.lotto.groups.update') }}";
                    this.$http.post(url, { id: this.code, data: this.formaddedit })
                        .then(response => {
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true,
                            });
                            this.$refs.addedit.hide();
                            window.LaravelDataTables['dataTableBuilder'].draw(false);
                        })
                        .catch(() => {
                            console.error('addEditSubmit error');
                        });
                },
            },
        });
    </script>
@endpush
