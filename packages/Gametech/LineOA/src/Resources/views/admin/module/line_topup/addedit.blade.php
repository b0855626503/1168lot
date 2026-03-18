<b-modal ref="addedit" id="addedit" centered size="md" title="เติมเงิน" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">

        <b-form @submit.prevent="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">
            <input type="hidden" id="member_topup" :value="formaddedit.member_topup" required>
            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-banks"
                            label="User ID / Game ID:"
                            label-for="banks"
                            description="ระบุ User / Game ID ที่ต้องการ เติมเงินรายการนี้">
                        <b-input-group>
                            <b-form-input
                                    id="user_name"
                                    v-model="formaddedit.user_name"
                                    type="text"
                                    size="md"
                                    placeholder="User / Game ID"
                                    autocomplete="off"

                            ></b-form-input>
                            <b-input-group-append>
                                <b-button variant="success" @click="loadUser">ค้นหา</b-button>
                            </b-input-group-append>
                        </b-input-group>
                    </b-form-group>
                </b-col>


            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-name"
                            label="ข้อมูลลูกค้า:"
                            label-for="name"
                            description="">
                        <b-form-textarea
                                id="name"
                                v-model="formaddedit.name"
                                size="sm"
                                row="6"
                                max-rows="6"
                                required
                                plaintext
                        ></b-form-textarea>
                    </b-form-group>
                </b-col>
            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-remark"
                            label="หมายเหตุ:"
                            label-for="remark_admin"
                            description="">
                        <b-form-input
                                id="remark_admin"
                                v-model="formaddedit.remark_admin"
                                type="text"
                                size="sm"
                                placeholder=""
                                autocomplete="off"

                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>


            <b-button type="submit" variant="primary">บันทึก</b-button>

        </b-form>
    </b-container>
</b-modal>

<b-modal ref="refill" id="refill" centered size="md" title="เติมเงิน" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">

        <b-form @submit.prevent="refillSubmit" v-if="show">
            <input type="hidden" id="id" :value="formrefill.id" required>
            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-banks"
                            label="User ID / Game ID:"
                            label-for="banks"
                            description="ระบุ User / Game ID ที่ต้องการ เติมเงินรายการนี้">
                        <b-input-group>
                            <b-form-input
                                    id="user_name"
                                    v-model="formrefill.user_name"
                                    type="text"
                                    size="md"
                                    placeholder="User / Game ID"
                                    autocomplete="off"

                            ></b-form-input>
                            <b-input-group-append>
                                <b-button variant="success" @click="loadUserRefill">ค้นหา</b-button>
                            </b-input-group-append>
                        </b-input-group>
                    </b-form-group>
                </b-col>


            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-name"
                            label="ข้อมูลลูกค้า:"
                            label-for="name"
                            description="">

                        <b-form-textarea
                                id="name"
                                v-model="formrefill.name"
                                size="sm"
                                row="6"
                                max-rows="6"
                                required
                                plaintext
                        ></b-form-textarea>
                    </b-form-group>
                </b-col>
            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-1"
                            label="จำนวนเงิน:"
                            label-for="amount"
                            description="ระบุจำนวนเงิน ระหว่าง 1 - 10,000">
                        <b-form-input
                                id="amount"
                                v-model="formrefill.amount"
                                type="number"
                                size="sm"
                                placeholder="จำนวนเงิน"
                                min="1"
                                max="10000"
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group id="input-group-2" label="ช่องทางที่ฝาก:" label-for="account_code">
                        <b-form-select
                                id="account_code"
                                v-model="formrefill.account_code"
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
                            id="input-group-remark"
                            label="หมายเหตุ:"
                            label-for="remark_admin"
                            description="">
                        <b-form-input
                                id="remark_admin"
                                v-model="formrefill.remark_admin"
                                type="text"
                                size="sm"
                                placeholder=""
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>

            {{--            <b-form-group--}}
            {{--                id="input-group-3"--}}
            {{--                label="รหัสยืนยัน:"--}}
            {{--                label-for="one_time_password"--}}
            {{--                description="รหัสยืนยันจาก Google Auth">--}}
            {{--                <b-form-input--}}
            {{--                    id="one_time_password"--}}
            {{--                    v-model="formrefill.one_time_password"--}}
            {{--                    type="number"--}}
            {{--                    placeholder="โปรดระบุ"--}}
            {{--                    size="sm"--}}
            {{--                    autocomplete="off"--}}

            {{--                ></b-form-input>--}}
            {{--            </b-form-group>--}}


            <b-button type="submit" variant="primary">บันทึก</b-button>

        </b-form>
    </b-container>
</b-modal>


<b-modal ref="clear" id="clear" centered size="md" title="โปรดระบุหมายเหตุ ในการทำรายการ" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-form @submit.stop.prevent="clearSubmit" v-if="show" id="frmclear" ref="frmclear">
        <b-form-group
                id="input-group-remark"
                label="หมายเหตุ:"
                label-for="remark"
                description="">
            <b-form-input
                    id="remark"
                    v-model="formclear.remark"
                    type="text"
                    size="sm"
                    placeholder=""
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        <b-button type="submit" variant="primary">บันทึก</b-button>
    </b-form>
</b-modal>


@push('scripts')
    <script>
        function clearModal(id) {
            window.app.clearModal(id);
        }

        function approveModal(id) {
            window.app.approveModal(id);
        }

        function refill() {
            window.app.refill();
        }

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
                        user_name: '',
                        name: '',
                        member_topup: '',
                        remark_admin: ''

                    },
                    formrefill: {
                        id: '',
                        name: '',
                        amount: 0,
                        account_code: '',
                        remark_admin: '',
                        one_time_password: ''
                    },
                    formclear: {
                        remark: ''

                    },
                    option: {
                        banks: [],
                    },
                    banks: [{value: '', text: '== ธนาคาร =='}],
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(true);
            },
            mounted() {

                this.loadBankAccount();
            },
            methods: {
                clearModal(code) {
                    this.code = null;
                    this.formclear = {
                        remark: '',

                    }
                    this.formmethod = 'clear';

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.code = code;
                        this.$refs.clear.show();

                    })
                },
                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        user_name: '',
                        name: '',
                        member_topup: '',
                        remark_admin: ''
                    }
                    this.formmethod = 'edit';

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.code = code;
                        this.$refs.addedit.show();

                    })
                },
                refill() {
                    this.code = null;
                    this.formrefill = {
                        id: '',
                        name: '',
                        amount: 0,
                        account_code: '',
                        remark_admin: '.',
                        one_time_password: ''
                    }
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.refill.show();

                    })
                },
                addModal() {
                    this.code = null;
                    this.formaddedit = {
                        acc_name: '',
                        acc_no: '',
                        banks: '',
                        user_name: '',
                        user_pass: '',
                        sort: 1,
                    }
                    this.formmethod = 'add';

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();

                    })
                },
                approveModal(code) {
                    this.$bvModal.msgBoxConfirm('อนุมัติรายการแจ้งฝาก รายการนี้ ใช่หรือไม่.', {
                        title: 'โปรดตรวจสอบก่อนทำรายการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'YES',
                        cancelTitle: 'NO',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        centered: true
                    })
                        .then(value => {
                            if (value) {
                                this.$http.post("{{ url($menu->currentRoute.'/approve') }}", {
                                    id: code
                                })
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
                                    .catch(exception => {
                                        console.log('error');
                                    });
                            }
                        })
                        .catch(err => {
                            console.log('error');
                        })
                },
                async loadUser(event) {
                    const response = await axios.post("{{ url($menu->currentRoute.'/loaddata') }}", {id: this.formaddedit.user_name});
                    this.formaddedit = {
                        name: response.data.data.name,
                        member_topup: response.data.data.code
                    }
                },
                async loadUserRefill(event) {
                    const response = await axios.post("{{ url($menu->currentRoute.'/loaddata') }}", {id: this.formrefill.user_name});
                    this.formrefill = {
                        name: response.data.data.name,
                        id: response.data.data.code
                    }
                },
                async loadBankAccount() {
                    const response = await axios.get("{{ url('member/loadbankaccount') }}");
                    this.banks = response.data.banks;
                },
                refillSubmit(event) {
                    event.preventDefault()
                    this.toggleButtonDisable(true);
                    this.$http.post("{{ url('member/refill') }}", this.formrefill)
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
                        .catch(exception => {
                            console.log('error');
                            this.toggleButtonDisable(false);
                        });
                },
                addEditSubmitNew(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    if (this.formmethod === 'add') {
                        var url = "{{ url($menu->currentRoute.'/create') }}";
                    } else if (this.formmethod === 'edit') {
                        var url = "{{ url($menu->currentRoute.'/update') }}/" + this.code;
                    }

                    let formData = new FormData();
                    const json = JSON.stringify({
                        member_topup: this.formaddedit.member_topup,
                        remark_admin: this.formaddedit.remark_admin
                    });

                    formData.append('data', json);
                    // formData.append('filepic', $('input[name="filepic[image_0]"]')[1].files[0]);

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
                },

                clearSubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    this.$http.post("{{ url($menu->currentRoute.'/clear') }}", {
                        id: this.code,
                        remark: this.formclear.remark
                    })
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
                        .catch(exception => {
                            console.log('error');
                            this.toggleButtonDisable(false);
                        });
                }
            },
        });
    </script>
@endpush

