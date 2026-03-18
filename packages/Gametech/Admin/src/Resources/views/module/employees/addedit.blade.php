<b-modal ref="addedit" id="addedit" centered scrollable size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent="addEditSubmit" v-if="show" id="frmaddedit" ref="frmaddedit">
            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-role_id"
                        label="ระดับ:"
                        label-for="role_id"
                        description="">

                        <b-form-select
                            id="role_id"
                            v-model="formaddedit.role_id"
                            :options="option.role_id"
                            size="sm"
                            required
                        ></b-form-select>
                    </b-form-group>
                </b-col>
                <b-col>

                </b-col>
            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-name"
                        label="ชื่อ:"
                        label-for="name"
                        description="ระบุ ชื่อ">
                        <b-form-input
                            id="firstname"
                            v-model="formaddedit.name"
                            type="text"
                            size="sm"
                            placeholder=""
                            autocomplete="off"

                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                        id="input-group-surname"
                        label="นามสกุล:"
                        label-for="surname"
                        description="ระบุ นามสกุล">
                        <b-form-input
                            id="surname"
                            v-model="formaddedit.surname"
                            type="text"
                            size="sm"
                            placeholder=""
                            autocomplete="off"

                        ></b-form-input>
                    </b-form-group>
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-user_name"
                        label="User เข้าระบบ:"
                        label-for="user_name"
                        description="">
                        <b-form-input
                            id="user_pass"
                            v-model="formaddedit.user_name"
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
                        id="input-group-user_pass"
                        label="รหัสผ่าน:"
                        label-for="user_pass"
                        description="ระบุ รหัสผ่าน กรณีต้องการเปลี่ยนเท่านั้น">
                        <b-form-input
                            id="user_pass"
                            v-model="formaddedit.user_pass"
                            type="text"
                            size="sm"
                            placeholder=""
                            autocomplete="off"

                        ></b-form-input>
                    </b-form-group>
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-mobile"
                        label="เบอร์โทร:"
                        label-for="mobile"
                        description="จำเป็นต้องกรอก">
                        <b-form-input
                            id="mobile"
                            v-model="formaddedit.mobile"
                            type="tel"
                            size="sm"
                            placeholder="ใช้ในการ Authen User"
                            autocomplete="off"
                            required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                        id="input-group-email"
                        label="Email:"
                        label-for="email"
                        description="จำเป็นต้องกรอก">
                        <b-form-input
                            id="email"
                            v-model="formaddedit.email"
                            type="email"
                            size="sm"
                            placeholder="ใช้ในการ Authen User"
                            autocomplete="off"
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
                    formmethod: 'add',
                    formaddedit: {
                        name: '',
                        surname: '',
                        mobile: '',
                        email: '',
                        user_name: '',
                        user_pass: '',
                        role_id: '',
                    },
                    option: {
                        role_id: ''
                    },
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            mounted() {
                this.loadRole();

            },
            methods: {
                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        name: '',
                        surname: '',
                        mobile: '',
                        email: '',
                        user_name: '',
                        user_pass: '',
                        role_id: '',
                    }

                    this.formmethod = 'edit';

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
                        surname: '',
                        mobile: '',
                        email: '',
                        user_name: '',
                        user_pass: '',
                        role_id: '',
                    }
                    this.formmethod = 'add';

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();

                    })
                },
                async loadData() {
                    const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {id: this.code});
                    this.formaddedit = {
                        name: response.data.data.name,
                        surname: response.data.data.surname,
                        mobile: response.data.data.mobile,
                        email: response.data.data.email,
                        user_name: response.data.data.user_name,
                        role_id: response.data.data.role_id,
                        user_pass: '',

                    }
                },
                async loadRole() {
                    const response = await axios.post("{{ url($menu->currentRoute.'/loadrole') }}");
                    this.option.role_id = response.data.roles;
                },
                addEditSubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);
                    if (this.formmethod === 'add') {
                        var url = "{{ route('admin.'.$menu->currentRoute.'.create') }}";
                    } else if (this.formmethod === 'edit') {
                        var url = "{{ route('admin.'.$menu->currentRoute.'.update') }}";
                    }
                    this.$http.post(url, {id: this.code, data: this.formaddedit})
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
                            console.log('error');
                            this.toggleButtonDisable(false);
                        });

                }


            },
        });

    </script>
@endpush


