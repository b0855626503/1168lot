<b-modal ref="addedit" id="addedit" centered scrollable size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent.once="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">
            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-turnpro"
                        label="เทรินโปร:"
                        label-for="turnpro"
                        description="ระบุ เทรินโปร">
                        <b-form-input
                            id="turnpro"
                            v-model="formaddedit.turnpro"
                            type="number"
                            size="sm"
                            autocomplete="off"
                            required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                        id="input-group-amount_balance"
                        label="ยอดเทรินทั้งหมด:"
                        label-for="amount_balance"
                        description="ระบุ ยอดเทรินทั้งหมด">
                        <b-form-input
                            id="amount_balance"
                            v-model="formaddedit.amount_balance"
                            type="number"
                            size="sm"
                            autocomplete="off"
                            required
                        ></b-form-input>
                    </b-form-group>
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-withdraw_limit_rate"
                        label="อัตราอั้นถอน (เท่า):"
                        label-for="withdraw_limit_rate"
                        description="ระบุ อัตราอั้นถอน (เท่า)">
                        <b-form-input
                            id="withdraw_limit_rate"
                            v-model="formaddedit.withdraw_limit_rate"
                            type="number"
                            size="sm"
                            autocomplete="off"
                            required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                        id="input-group-withdraw_limit_amount"
                        label="ยอดอั้นถอนทั้งหมด:"
                        label-for="withdraw_limit_amount"
                        description="ระบุ ยอดอั้นถอนทั้งหมด">
                        <b-form-input
                            id="withdraw_limit_amount"
                            v-model="formaddedit.withdraw_limit_amount"
                            type="number"
                            size="sm"
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
    <script>
        function showModalNew(id, method) {
            window.app.showModalNew(id, method);
        }

        function refill(id) {
            window.app.refill(id);
        }

        function money(id) {
            window.app.money(id);
        }

        function point(id) {
            window.app.point(id);
        }

        function diamond(id) {
            window.app.diamond(id);
        }

        function commentModal(id) {
            window.app.commentModal(id);
        }

        function delSub(id, table) {
            window.app.delSub(id, table);
        }

        function editdatasub(id, status, method) {
            window.app.editdatasub(id, status, method);
        }

        function changegamepass(id) {
            window.app.showModalChange(id);
        }

        $(document).ready(function () {
            $('body').addClass('sidebar-collapse');
        });

    </script>
    <script type="module">

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: false,
                    showsub: false,
                    showremark: false,
                    fieldsRemark: [],
                    fields: [],
                    items: [],
                    caption: null,
                    isBusy: false,
                    isBusyRemark: false,
                    formmethodsub: 'edit',
                    formsub: {
                        remark: ''
                    },
                    formchange: {
                        id: null,
                        password: ''
                    },
                    formmethod: 'edit',
                    formaddedit: {
                        turnpro: 0,
                        amount_balance: 0,
                        withdraw_limit_rate: 0,
                        withdraw_limit_amount: 0,
                    },
                    option: {
                        bank_code: ''
                    },
                    formrefill: {
                        id: null,
                        amount: 0,
                        account_code: '',
                        remark_admin: ''
                    },
                    formmoney: {
                        id: null,
                        amount: 0,
                        type: 'D',
                        remark: ''
                    },
                    formpoint: {
                        id: null,
                        amount: 0,
                        type: 'D',
                        remark: ''
                    },
                    formdiamond: {
                        id: null,
                        amount: 0,
                        type: 'D',
                        remark: ''
                    },
                    banks: [{value: '', text: '== ธนาคาร =='}],
                    typesmoney: [{value: 'D', text: 'เพิ่ม Wallet'}, {value: 'W', text: 'ลด Wallet'}],
                    typespoint: [{value: 'D', text: 'เพิ่ม Point'}, {value: 'W', text: 'ลด Point'}],
                    typesdiamond: [{value: 'D', text: 'เพิ่ม Diamond'}, {value: 'W', text: 'ลด Diamond'}]
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            mounted() {
                // this.loadBank();
                // this.loadBankAccount();
            },
            methods: {
                showModalChange(code) {
                    this.formchange = {
                        id: null,
                        password: '',
                    }

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.formchange.id = code;
                        this.$refs.changepass.show();
                    })

                },


                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        turnpro: 0,
                        amount_balance: 0,
                        withdraw_limit_rate: 0,
                        withdraw_limit_amount: 0,
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
                        turnpro: 0,
                        amount_balance: 0,
                        withdraw_limit_rate: 0,
                        withdraw_limit_amount: 0,
                    }
                    this.formmethod = 'add';

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();

                    })
                },
                async loadData() {
                    const response = await axios.get("{{ url($menu->currentRoute.'/loaddata') }}", {
                        params: {
                            id: this.code
                        }
                    });
                    this.formaddedit = {
                        turnpro: response.data.data.turnpro,
                        amount_balance: response.data.data.amount_balance,
                        withdraw_limit_rate: response.data.data.withdraw_limit_rate,
                        withdraw_limit_amount: response.data.data.withdraw_limit_amount,
                    }

                },
                addSubModal() {

                    this.formsub = {
                        remark: ''
                    }
                    this.formmethodsub = 'add';

                    this.showsub = false;
                    this.$nextTick(() => {
                        this.showsub = true;
                        this.$refs.addeditsub.show();

                    })
                },
                delModal(code) {
                    this.$bvModal.msgBoxConfirm('ต้องการดำเนินการ รีเซตยอดเทรินและอั้นหรือไม่.', {
                        title: 'โปรดยืนยันการทำรายการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'ตกลง',
                        cancelTitle: 'ยกเลิก',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        centered: true
                    })
                        .then(value => {
                            if (value) {
                                this.$http.post("{{ url($menu->currentRoute.'/delete') }}", {
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
                            // An error occurred
                        })
                },
                delSub(code, table) {
                    this.$bvModal.msgBoxConfirm('ต้องการดำเนินการ รีเซตยอดเทรินและอั้นหรือไม่.', {
                        title: 'โปรดยืนยันการทำรายการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'ตกลง',
                        cancelTitle: 'ยกเลิก',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        centered: true
                    })
                        .then(value => {
                            if (value) {
                                this.$http.post("{{ url($menu->currentRoute.'/deletesub') }}", {
                                    id: code, method: table
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
                                        this.$refs.tbdata.refresh();

                                    })
                                    .catch(errors => console.log(errors));
                            }
                        })
                        .catch(errors => console.log(errors));
                },
                addEditSubmitNew(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);
                    var url = "{{ url($menu->currentRoute.'/update') }}/" + this.code;


                    let formData = new FormData();
                    const json = JSON.stringify({
                        turnpro: this.formaddedit.turnpro,
                        amount_balance: this.formaddedit.amount_balance,
                        withdraw_limit_rate: this.formaddedit.withdraw_limit_rate,
                        withdraw_limit_amount: this.formaddedit.withdraw_limit_amount,
                    });

                    formData.append('data', json);

                    // formData.append('fileupload', this.fileupload);

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
                        .catch(errors => {
                            this.toggleButtonDisable(false);
                            Toast.fire({
                                icon: 'error',
                                title: errors.response.data
                            })
                        });

                },
                addEditSubmitNewSub(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    var url = "{{ url($menu->currentRoute.'/createsub') }}";

                    this.$http.post(url, {id: this.code, data: this.formsub})
                        .then(response => {
                            this.$bvModal.hide('addeditsub');
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });

                            this.$refs.tbdataremark.refresh()

                        })
                        .catch(errors => console.log(errors));

                },
            },
        });


    </script>
@endpush

