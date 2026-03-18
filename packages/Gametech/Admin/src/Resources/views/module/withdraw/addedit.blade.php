<b-modal ref="addedit" id="addedit" centered size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent.once="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">
            <b-form-row>
                <div class="info-box">
                    <span class="info-box-icon"><b-img :src="formaddedit.member_bank_pic"></b-img></span>

                    <div class="info-box-content">
                        <span class="info-box-text" v-text="formaddedit.member_bank"></span>
                        <span class="info-box-number" v-text="formaddedit.member_account"></span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <table class="table">
                    <tbody>
                    <tr>
                        <td width="50%">User :</td>
                        <td width="50%" align="right" v-text="formaddedit.member_username"></td>
                    </tr>
                    <tr>
                        <td>ชื่อลูกค้า :</td>
                        <td align="right" v-text="formaddedit.member_name"></td>
                    </tr>
                    <tr>
                        <td>จำนวนเงินที่แจ้งถอน :</td>
                        <td align="right" v-text="formaddedit.balance"></td>
                    </tr>
                    <tr>
                        <td>จำนวนเงินที่จะได้รับ :</td>
                        <td align="right" v-text="formaddedit.amount"></td>
                    </tr>
                    <tr v-if="formaddedit.member_qr_pic">
                        <td colspan="2" align="center"><b-img :src="formaddedit.member_qr_pic" class="img-fluid" style="max-width: 200px; display: block; margin: 0 auto;"></b-img></td>
                    </tr>
                    </tbody>
                </table>
            </b-form-row>


            <b-form-row>
                <b-col cols="12">

                    <b-form-group
                        id="input-group-account_code"
                        label="บัญชีที่ใช้ดำเนินการ:"
                        label-for="account_code"
                        description="">

                        <b-form-select
                            id="account_code"
                            name="account_code"
                            v-model="formaddedit.account_code"
                            :options="option.account_code"
                            size="sm"
                            required
                        ></b-form-select>

                    </b-form-group>

                    <b-form-group
                            id="input-group-1"
                            label="ยอดเงินที่จะได้รับจริง:"
                            label-for="amount"
                            description="">
                        <b-form-input
                                id="amount"
                                v-model="formaddedit.amount"
                                type="number"
                                size="sm"
                                placeholder=""
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>

                    <b-form-group
                        id="input-group-1"
                        label="ค่าธรรมเนียม:"
                        label-for="fee"
                        description="">
                        <b-form-input
                            id="fee"
                            v-model="formaddedit.fee"
                            type="number"
                            size="sm"
                            placeholder=""
                            autocomplete="off"
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col cols="12">

                    <b-form-group
                        id="input-group-2"
                        label="วันที่โอน:"
                        label-for="date_bank"
                        description="">
                        <b-form-datepicker
                            id="date_bank"
                            v-model="formaddedit.date_bank"
                            size="sm"
                            placeholder=""
                            autocomplete="off"
                            locale="en-US"
                            @context="onContext"
                        ></b-form-datepicker>
                    </b-form-group>
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col cols="12">
                    <b-form-group
                        id="input-group-3"
                        label="เวลาที่โอน:"
                        label-for="time_bank"
                        description="">
                        <b-form-timepicker
                            id="time_bank"
                            v-model="formaddedit.time_bank"
                            type="text"
                            size="sm"
                            placeholder=""
                            autocomplete="off"
                            :hour12="false"
                        ></b-form-timepicker>
                    </b-form-group>
                </b-col>
                <b-col cols="12">
                    <b-form-group
                        id="input-group-3"
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

<b-modal ref="addeditnew" id="addeditnew" centered size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent.once="addEditSubmit" v-if="show" id="frmaddeditnew" ref="frmaddeditnew">

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-banks"
                            label="User ID / Game ID:"
                            label-for="banks"
                            description="ระบุ User / Game ID ที่ต้องการ แจ้งถอน">
                        <b-input-group>
                            <b-form-input
                                    id="user_name"
                                    v-model="formaddeditnew.user_name"
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
                <b-col cols="4">
                    <div class="info-box">
                        <span class="info-box-icon"><b-img :src="formaddeditnew.member_bank_pic"
                                                           width="50px"></b-img></span>

                        <div class="info-box-content">
                            <span class="info-box-text" v-text="formaddeditnew.member_bank"></span>
                            <span class="info-box-number" v-text="formaddeditnew.member_account"></span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                </b-col>
                <b-col cols="8">
                    <table class="table">
                        <tbody>
                        <tr>
                            <td width="50%">User ID :</td>
                            <td width="50%" align="right" v-text="formaddeditnew.member_username"></td>
                        </tr>
                        <tr>
                            <td width="50%">Game ID :</td>
                            <td width="50%" align="right" v-text="formaddeditnew.member_gameuser"></td>
                        </tr>
                        <tr>
                            <td>ชื่อลูกค้า :</td>
                            <td align="right" v-text="formaddeditnew.member_name"></td>
                        </tr>
                        <tr>
                            <td>ยอดที่มีอยู่ :</td>
                            <td align="right" v-text="formaddeditnew.balance"></td>
                        </tr>
                        </tbody>
                    </table>
                </b-col>
            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-1"
                            label="ยอดที่จะถอน:"
                            label-for="amount"
                            description="">
                        <b-form-input
                                id="amount"
                                v-model="formaddeditnew.amount"
                                type="number"
                                size="sm"
                                placeholder="จำนวนเงิน"
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-1"
                            label="วันที่:"
                            label-for="date_record"
                            description="">
                        <b-form-datepicker
                                id="date_record"
                                v-model="formaddeditnew.date_record"
                                size="sm"
                                placeholder=""
                                autocomplete="off"
                                locale="en-US"
                                @context="onContext"
                        ></b-form-datepicker>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                            id="input-group-1"
                            label="เวลา:"
                            label-for="timedept"
                            description="">
                        <b-form-timepicker
                                id="timedept"
                                v-model="formaddeditnew.timedept"
                                type="text"
                                size="sm"
                                placeholder=""
                                autocomplete="off"
                                :hour12="false"
                        ></b-form-timepicker>
                    </b-form-group>
                </b-col>
            </b-form-row>


            <b-button type="submit" variant="primary">บันทึก</b-button>

        </b-form>
    </b-container>
</b-modal>



<b-modal ref="clear" id="clear" centered size="md" title="โปรดระบุหมายเหตุ ในการทำรายการ" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-form @submit.prevent.once="clearSubmit" v-if="show" id="frmclear" ref="frmclear">
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
    <script type="text/javascript">
        function clearModal(id) {
            window.app.clearModal(id);
        }

        function fixModal(id) {
            window.app.fixSubmit(id);
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
                        fee: 0,
                        date_bank: '',
                        time_bank: '',
                        remark_admin: '',
                        member_username: '',
                        member_code: '',
                        member_name: '',
                        member_account: '',
                        member_bank: '',
                        member_bank_pic: '',
                        amount: 0,
                        balance: 0,
                        account_code: 0,
                        member_qr_pic: '',
                    },
                    formaddeditnew: {
                        member_code: '',
                        member_username: '',
                        member_gameuser: '',
                        member_name: '',
                        member_account: '',
                        member_bank: '',
                        member_bank_pic: '',
                        amount: 0,
                        balance: 0,
                        date_record: '',
                        timedept: '',
                    },
                    formclear: {
                        remark: ''

                    },
                    formatted: '',
                    selected: '',
                    option: {
                        banks: [],
                        account_code: []
                    },
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(true);
            },
            mounted() {
                this.loadBank();
            },
            methods: {
                onContext(ctx) {
                    // The date formatted in the locale, or the `label-no-date-selected` string
                    this.formatted = ctx.selectedFormatted
                    // The following will be an empty string until a valid date is entered
                    this.selected = ctx.selectedYMD
                },
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
                        fee: 0,
                        date_bank: '',
                        time_bank: '',
                        remark_admin: '',
                        member_username: '',
                        member_code: '',
                        member_name: '',
                        member_account: '',
                        member_bank: '',
                        member_bank_pic: '',
                        balance: 0,
                        amount: 0,
                        account_code: 0,
                        member_qr_pic: '',
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
                    this.formaddeditnew = {
                        member_code: '',
                        member_username: '',
                        member_gameuser: '',
                        member_name: '',
                        member_account: '',
                        member_bank: '',
                        member_bank_pic: '',
                        amount: '',
                        balance: 0,
                        date_record: '',
                        timedept: '',
                    }
                    this.formmethod = 'add';

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addeditnew.show();

                    })
                },
                async loadUser() {
                    const response = await axios.post("{{ url($menu->currentRoute.'/loaduser') }}", {id: this.formaddeditnew.user_name});
                    this.formaddeditnew = {
                        member_code: response.data.data.member_code,
                        member_username: response.data.data.member_username,
                        member_gameuser: response.data.data.member_gameuser,
                        member_name: response.data.data.member_name,
                        member_account: response.data.data.member_account,
                        member_bank: response.data.data.member_bank,
                        member_bank_pic: '/storage/bank_img/' + response.data.data.member_bank_pic,
                        balance: response.data.data.balance,
                        date_record: moment().format('YYYY-MM-DD'),
                        timedept: moment().format('HH:mm')
                    }
                },
                async loadData() {
                    const response = await axios.post("{{ url($menu->currentRoute.'/loaddata') }}", {id: this.code});
                    this.formaddedit = {
                        member_username: response.data.data.member.user_name,
                        member_code: response.data.data.member.code,
                        member_name: response.data.data.member.name,
                        member_account: response.data.data.member.acc_no,
                        member_bank: response.data.data.bank.name_th,
                        tel: response.data.data.member.tel,
                        member_bank_pic: '/storage/bank_img/' + response.data.data.bank.filepic,
                        member_qr_pic: (response.data.data.member.pic_id ? '/storage/' + response.data.data.member.pic_id+'?v={{ time() }}' : ''),
                        amount: response.data.data.amount,
                        balance: response.data.data.balance,
                        account_code: response.data.data.account_code ,
                        fee: 0,
                        date_bank: moment().format('YYYY-MM-DD'),
                        time_bank: moment().format('HH:mm'),


                    };

                },
                async loadBank() {
                    const response = await axios.post("{{ url($menu->currentRoute.'/loadbank') }}");
                    this.option = {
                        account_code: response.data.banks
                    };
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
                        fee: this.formaddedit.fee,
                        amount: this.formaddedit.amount,
                        date_bank: this.formaddedit.date_bank,
                        time_bank: this.formaddedit.time_bank,
                        remark_admin: this.formaddedit.remark_admin,
                        account_code: this.formaddedit.account_code,
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
                addEditSubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    var url = "{{ url($menu->currentRoute.'/create') }}";

                    let formData = new FormData();
                    const json = JSON.stringify({
                        member_code: this.formaddeditnew.member_code,
                        amount: this.formaddeditnew.amount,
                        date_record: this.formaddeditnew.date_record,
                        timedept: this.formaddeditnew.timedept,
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
                },
                fixSubmit(id) {
                    this.$bvModal.msgBoxConfirm('รายการนี้ กำลังประมวลผลการถอนออโต้ ถ้ามั่นใจว่าเป็นรายการ ค้าง โปรดกด Yes เพื่อแก้ไข', {
                        title: 'โปรดแน่ใจ ว่ารายการนี้ ค้างแน่นอน',
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

                                this.$http.post("{{ url($menu->currentRoute.'/fix') }}", {
                                    id: id
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
                        })
                        .catch(err => {
                            // An error occurred
                        })
                }
            }
        });
    </script>
@endpush

