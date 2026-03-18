<b-modal ref="addedit" id="addedit" centered scrollable size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent="addEditSubmit" v-if="show" id="frmaddedit" ref="frmaddedit" name="frmaddedit">

            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-name"
                        label="ชื่อ:"
                        label-for="name"
                        description="ระบุ ชื่อ">
                        <b-form-input
                            id="name"
                            name="name"
                            v-model="formaddedit.name"
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
                        id="input-group-description"
                        label="รายละเอียด:"
                        label-for="description"
                        description="">
                        <b-form-input
                            id="description"
                            name="description"
                            v-model="formaddedit.description"
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
                        id="input-group-permission_type"
                        label="สิทธิ์การใช้งาน:"
                        label-for="permission_type"
                        description="">

                        <b-form-select
                            id="permission_type"
                            name="permission_type"
                            v-model="formaddedit.permission_type"
                            :options="option.permission_type"
                            size="sm"
                            required
                            v-on:change="changeType($event)"
                        ></b-form-select>
                    </b-form-group>
                </b-col>
                <b-col>

                </b-col>
            </b-form-row>

            <div class="control-group tree-wrapper">
                <tree-view value-field="key" id-field="key" v-bind:items="formaddedit.acl"
                           v-bind:value="formaddedit.permissions"></tree-view>
            </div>


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
                        description: '',
                        permission_type: 'custom',
                        permissions: '',
                        acl: ''
                    },
                    option: {
                        permission_type: [{value: 'custom', text: 'Custom'}, {value: 'all', text: 'All'}]
                    },
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },

            methods: {
                changeType(event) {
                    if (event == 'custom') {
                        $('.tree-container').removeClass('hide')
                    } else {
                        $('.tree-container').addClass('hide')
                    }
                },
                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        name: '',
                        description: '',
                        permission_type: 'custom'
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
                        description: '',
                        permission_type: 'custom',
                    }
                    this.formmethod = 'add';

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.formaddedit.acl =  @json($acl->items);
                        this.$refs.addedit.show();

                    })
                },
                async loadData() {
                    const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {id: this.code});
                    this.formaddedit = {
                        name: response.data.data.name,
                        description: response.data.data.description,
                        permission_type: response.data.data.permission_type,
                        permissions: response.data.data.permissions,
                        acl: JSON.parse(response.data.data.acl)

                    }
                    this.changeType(response.data.data.permission_type);

                },
                addEditSubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);
                    if (this.formmethod === 'add') {
                        var url = "{{ route('admin.'.$menu->currentRoute.'.create') }}";
                    } else if (this.formmethod === 'edit') {
                        var url = "{{ url($menu->currentRoute.'/update') }}/" + this.code;
                    }
                    var form = $('#frmaddedit')[0];
                    let formData = new FormData(form);
                    // const json = JSON.stringify({
                    //     firstname: this.formaddedit.firstname,
                    //     lastname: this.formaddedit.lastname,
                    //     bank_code: this.formaddedit.bank_code,
                    //     user_pass: this.formaddedit.user_pass,
                    //     acc_no: this.formaddedit.acc_no,
                    // });
                    //
                    // formData.append('data', json);


                    const config = {headers: {'Content-Type': `multipart/form-data; boundary=${formData._boundary}`}};

                    axios.post(url, formData, config)
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

