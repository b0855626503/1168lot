<b-modal ref="addedit" id="addedit" centered size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">


            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-1"
                            label="ชื่อทีม:"
                            label-for="name"
                            description="ระบุชื่อทีม">
                        <b-form-input
                                id="name"
                                v-model="formaddedit.name"
                                type="text"
                                size="sm"
                                placeholder="ชื่อทีม"
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>

                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-username"
                            label="User ID:"
                            label-for="username"
                            description="ระบุ User ID สำหรับ เข้าระบบทีม">
                        <b-form-input
                                id="username"
                                v-model="formaddedit.username"
                                type="text"
                                size="sm"
                                placeholder=""
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                            id="input-group-password_hash"
                            label="รหัสผ่าน:"
                            label-for="password_hash"
                            description="ระบุ รหัสผ่าน สำหรับ เข้าระบบทีม">
                        <b-form-input
                                id="password_hash"
                                v-model="formaddedit.password_hash"
                                type="text"
                                size="sm"
                                placeholder=""
                                autocomplete="off"
                                :required="formmethod === 'add'"
                        ></b-form-input>
                    </b-form-group>
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-commission_rate"
                            label="ค่าคอม %:"
                            label-for="commission_rate"
                            description="ระบุ % ค่าคอม">
                        <b-form-input
                                id="commission_rate"
                                v-model="formaddedit.commission_rate"
                                type="number"
                                size="sm"
                                step="0.01"
                                placeholder=""
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                            id="input-group-bank_code"
                            label="ระบุ ธนาคาร:"
                            label-for="bank_code"
                            description="ระบุ ธนาคาร">
                        <b-form-select
                                id="bank_code"
                                v-model="formaddedit.bank_code"
                                :options="banks"
                                size="sm"
                                required
                        ></b-form-select>
                    </b-form-group>
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-bank_account_name"
                            label="ระบุ ชื่อเจ้าของบัญชี:"
                            label-for="bank_account_name"
                            description="ระบุ ชื่อบัญชี">
                        <b-form-input
                                id="bank_account_name"
                                v-model="formaddedit.bank_account_name"
                                type="text"
                                size="sm"
                                placeholder=""
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                            id="input-group-bank_account_no"
                            label="ระบุ เลขที่บัญชี:"
                            label-for="bank_account_no"
                            description="ระบุ เลขที่บัญชี">
                        <b-form-input
                                id="bank_account_no"
                                v-model="formaddedit.bank_account_no"
                                type="text"
                                size="sm"
                                placeholder=""
                                autocomplete="off"
                                maxlength="12"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>

            </b-form-row>


            <b-button type="submit" variant="primary">บันทึก</b-button>

        </b-form>
    </b-container>
</b-modal>


@push('scripts')

    <script type="module">

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: false,
                    trigger: 0,
                    formmethod: 'edit',
                    fileupload: '',
                    formaddedit: {
                        name: '',
                        username: '',
                        password_hash: '',
                        commission_rate: '',
                        bank_code: '',
                        bank_account_name: '',
                        bank_account_no: '',
                    },
                    banks: [{value: '', text: '== ธนาคาร =='}],

                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            mounted() {
                this.loadBank();
            },
            methods: {
                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        name: '',
                        username: '',
                        password_hash: '',
                        commission_rate: '',
                        bank_code: '',
                        bank_account_name: '',
                        bank_account_no: '',
                    }
                    this.formmethod = 'edit';
                    this.fileupload = '';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.code = code;
                        this.loadData();
                        this.$refs.addedit.show();

                    })
                },
                addModal() {
                    this.code = null;
                    this.formaddedit = {
                        name: '',
                        username: '',
                        password_hash: '',
                        commission_rate: '',
                        bank_code: '',
                        bank_account_name: '',
                        bank_account_no: '',
                    }
                    this.formmethod = 'add';
                    this.fileupload = '';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();

                    })
                },

                async loadData() {

                    try {
                        const responses = axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {id: this.code});

                        const response = await responses;

                        this.formaddedit = {
                            name: response.data.data.name,
                            username: response.data.data.username,
                            commission_rate: response.data.data.commission_rate,
                            bank_code: response.data.data.bank_code,
                            bank_account_name: response.data.data.bank_account_name,
                            bank_account_no: response.data.data.bank_account_no
                        };


                    } catch (error) {
                        console.log(error)
                    }
                },
                async loadBank() {
                    const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loadBank') }}");
                    this.banks = response.data.banks;
                },
                addEditSubmitNew(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    if (this.formmethod === 'add') {
                        var url = "{{ route('admin.'.$menu->currentRoute.'.create') }}";
                    } else if (this.formmethod === 'edit') {
                        var url = "{{ route('admin.'.$menu->currentRoute.'.update') }}/" + this.code;
                    }

                    let formData = new FormData();
                    const json = JSON.stringify({
                        name: this.formaddedit.name,
                        username: this.formaddedit.username,
                        password_hash: this.formaddedit.password_hash,
                        commission_rate: this.formaddedit.commission_rate,
                        bank_code: this.formaddedit.bank_code,
                        bank_account_name: this.formaddedit.bank_account_name,
                        bank_account_no: this.formaddedit.bank_account_no,
                    });

                    formData.append('data', json);


                    const config = {headers: {'Content-Type': `multipart/form-data; boundary=${formData._boundary}`}};

                    axios.post(url, formData, config)
                        .then(response => {
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
                        .catch(errors => console.log(errors));

                }
            },
        });

    </script>
@endpush

