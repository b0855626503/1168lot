<b-modal ref="addedit" id="addedit" centered size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">
            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-banks"
                        label="ธนาคาร:"
                        label-for="banks"
                        description="">

                        <b-form-select
                            id="banks"
                            v-model="formaddedit.banks"
                            :options="option.banks"
                            size="sm"
                            required
{{--                            disabled--}}
                        ></b-form-select>
                    </b-form-group>
                </b-col>
                <b-col>

                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-1"
                        label="ชื่อบัญชี:"
                        label-for="acc_name"
                        description="ระบุ ชื่อบัญชี">
                        <b-form-input
                            id="acc_name"
                            v-model="formaddedit.acc_name"
                            type="text"
                            size="sm"
                            placeholder="ชื่อบัญชี"
                            autocomplete="off"
                            required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                        id="input-group-2"
                        label="เลขที่บัญชี:"
                        label-for="acc_no"
                        description="ระบุ เลขที่บัญชี">
                        <b-form-input
                            id="acc_no"
                            v-model="formaddedit.acc_no"
                            type="text"
                            size="sm"
                            placeholder="เลขที่บัญชี"
                            autocomplete="off"
                            required

                        ></b-form-input>
                    </b-form-group>
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-3"
                        label="User Name:"
                        label-for="user_name"
                        description="">
                        <b-form-input
                            id="user_name"
                            v-model="formaddedit.user_name"
                            type="text"
                            size="sm"
                            placeholder=""
                            autocomplete="off"

                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                        id="input-group-3"
                        label="Password:"
                        label-for="user_pass"
                        description="">
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
                        id="input-group-3"
                        label="ยอดต่ำสุด:"
                        label-for="min_amount"
                        description="ยอดต่ำสุดที่จะให้ระบบโอนถอนออโต้">
                        <b-form-input
                            id="min_amount"
                            v-model="formaddedit.min_amount"
                            type="number"
                            size="sm"
                            placeholder=""
                            autocomplete="off"

                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                        id="input-group-3"
                        label="ยอดสูงสุด:"
                        label-for="max_amount"
                        description="ยอดสูงสุดที่จะให้ระบบโอนถอนออโต้">
                        <b-form-input
                            id="max_amount"
                            v-model="formaddedit.max_amount"
                            type="number"
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
                        id="input-group-3"
                        label="ลำดับการแสดงผล:"
                        label-for="sort"
                        description="">
                        <b-form-input
                            id="sort"
                            v-model="formaddedit.sort"
                            type="number"
                            size="sm"
                            placeholder=""
                            autocomplete="off"
                            required
                        ></b-form-input>
                    </b-form-group>
                </b-col>

                <b-col>
                    <b-form-checkbox
                        id="auto_transfer"
                        v-model="formaddedit.auto_transfer"
                        value="Y"
                        unchecked-value="N">
                        ตั้งค่าธนาคารหลักในการโอนถอน ออโต้
                    </b-form-checkbox>
                </b-col>
            </b-form-row>


            <b-button type="submit" variant="primary">บันทึก</b-button>

        </b-form>
    </b-container>
</b-modal>
@push('scripts')
    <script>
        $(document).ready(function () {
            $("body").tooltip({
                selector: '[data-toggle="tooltip"]',
                container: 'body'
            });
        });
    </script>
    <script type="module">

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: false,
                    trigger: 0,
                    formmethod: 'edit',
                    formaddedit: {
                        acc_name: '',
                        acc_no: '',
                        banks: '',
                        user_name: '',
                        user_pass: '',
                        min_amount: '',
                        max_amount: '',
                        auto_transfer: 'N',
                        sort: 1,
                    },
                    option: {
                        banks: [],
                    },
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
                        acc_name: '',
                        acc_no: '',
                        banks: '',
                        user_name: '',
                        user_pass: '',
                        min_amount: '',
                        max_amount: '',
                        auto_transfer: 'N',
                        sort: 1,
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
                        acc_name: '',
                        acc_no: '',
                        banks: '1',
                        user_name: '',
                        user_pass: '',
                        min_amount: '',
                        max_amount: '',
                        auto_transfer: 'N',
                        sort: 1,
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
                        acc_name: response.data.data.acc_name,
                        acc_no: response.data.data.acc_no,
                        banks: response.data.data.banks,
                        user_name: response.data.data.user_name,
                        user_pass: response.data.data.user_pass,
                        min_amount: response.data.data.min_amount,
                        max_amount: response.data.data.max_amount,
                        auto_transfer: response.data.data.auto_transfer,
                        sort: response.data.data.sort
                    };

                },
                async loadBank() {
                    const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loadbank') }}");
                    this.option = {
                        banks: response.data.banks
                    };
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
                        acc_name: this.formaddedit.acc_name,
                        acc_no: this.formaddedit.acc_no,
                        banks: this.formaddedit.banks,
                        user_name: this.formaddedit.user_name,
                        user_pass: this.formaddedit.user_pass,
                        min_amount: this.formaddedit.min_amount,
                        max_amount: this.formaddedit.max_amount,
                        auto_transfer: this.formaddedit.auto_transfer,
                        sort: this.formaddedit.sort
                    });

                    formData.append('data', json);
                    // formData.append('filepic', $('input[name="filepic[image_0]"]')[1].files[0]);


                    // const config = {headers: {'Content-Type': `multipart/form-data; boundary=${formData._boundary}`}};
                    const config = {headers: {'Content-Type': `multipart/form-data; boundary=${formData._boundary}`}};

                    axios.post(url, formData, config)
                        .then(response => {
                            if (response.data.success === true) {
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
                            } else {
                                $.each(response.data.message, function (index, value) {
                                    document.getElementById(index).classList.add("is-invalid");
                                });
                                $('input').on('focus', function (event) {
                                    event.preventDefault();
                                    this.toggleButtonDisable(true);
                                    event.stopPropagation();
                                    var id = $(this).attr('id');
                                    document.getElementById(id).classList.remove("is-invalid");
                                });
                            }

                        })
                        .catch(errors => console.log(errors));
                }
            },
        });


    </script>
@endpush

