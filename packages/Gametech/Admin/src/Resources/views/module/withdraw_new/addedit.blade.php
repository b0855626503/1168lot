<b-modal ref="refill" id="refill" centered size="md" title="โอนเงิน" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">

        <b-form @submit.prevent="refillSubmit" v-if="show">
{{--            <input type="hidden" id="id" :value="formrefill.id" required>--}}
            <b-form-row>
                <b-col>
                    <b-form-group id="input-group-2" label="บัญชีที่ใช้โอน:" label-for="account_code">
                        <b-form-select
                            id="account_code"
                            v-model="formrefill.account_code"
                            :options="option.account_code"
                            size="sm"
                            required
                            v-on:change="changeType($event)"
                        ></b-form-select>
                    </b-form-group>
                </b-col>
            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group id="input-group-2" label="โอนไปธนาคาร:" label-for="to_bank">
                        <b-form-select
                            id="to_bank"
                            v-model="formrefill.to_bank"
                            :options="option.banks"
                            size="sm"
                            required
                            disabled
                            v-on:change="changeTypes($event)"
                        ></b-form-select>
                    </b-form-group>
                </b-col>
            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-banks"
                        label="โอนไปเลขที่บัญชี:"
                        label-for="banks"
                        description="ระบุ เลขที่บัญชี">
                        <b-input-group>
                            <b-form-input
                                id="to_account"
                                v-model="formrefill.to_account"
                                type="text"
                                size="md"
                                placeholder="เลขที่บัญชี"
                                autocomplete="off"
                                disabled
                                required
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
                        label="ชื่อบัญชี:"
                        label-for="name"
                        description="">
                        <b-form-input
                            id="to_name"
                            v-model="formrefill.to_name"
                            type="text"
                            size="sm"
                            placeholder=""
                            autocomplete="off"
                            required
                            plaintext
                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-1"
                        label="จำนวนเงิน:"
                        label-for="amount"
                        description="ระบุจำนวนเงิน">
                        <b-form-input
                            id="amount"
                            v-model="formrefill.amount"
                            type="number"
                            size="sm"
                            placeholder="จำนวนเงิน"
                            min="1"
                            autocomplete="off"
                            required
                            disabled
                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>


            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-remark"
                        label="หมายเหตุ:"
                        label-for="remark"
                        description="">
                        <b-form-input
                            id="remark"
                            v-model="formrefill.remark"
                            type="text"
                            size="sm"
                            placeholder=""
                            autocomplete="off"

                            disabled
                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>


            <b-button type="submit" variant="primary" :disabled="btndisabled">บันทึก</b-button>

        </b-form>
    </b-container>
</b-modal>


@push('scripts')
    <script type="text/javascript">

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
                    formrefill: {
                        to_name: '',
                        to_account: '',
                        to_bank: '',
                        amount: 0,
                        account_code: '',
                        remark: ''
                    },
                    formatted: '',
                    selected: '',
                    option: {
                        banks: [],
                        account_code: []
                    },
                    btnsubmit : {
                        disabled : true
                    },
                    btndisabled : true
                };
            },
            mounted() {
                this.loadBanks();
            },
            methods: {
                changeType(event) {
                    if (event === '') {
                        $('#to_bank').prop('disabled', true);
                    } else {
                        $('#to_bank').prop('disabled', false);
                    }
                },
                changeTypes(event) {

                    if (event === '') {
                        $('#to_account').prop('disabled', true);
                    } else {
                        $('#to_account').prop('disabled', false);
                    }
                },

                refill() {
                    this.code = null;
                    this.formrefill = {
                        to_name: '',
                        to_account: '',
                        to_bank: '',
                        amount: 0,
                        account_code: '',
                        remark: ''
                    }
                    this.formrefill.disabled = false;
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.refill.show();

                    })
                },
                async loadUserRefill() {
                    const response = await axios.post("{{ url($menu->currentRoute.'/loaddata') }}", {
                        from_bank: this.formrefill.account_code,
                        to_bank: this.formrefill.to_bank,
                        to_account: this.formrefill.to_account
                    });
                    if(response.data.success === true){
                        $('#amount').prop('disabled', false);
                        $('#remark').prop('disabled', false);
                        // $('#btnsubmit').prop('disabled', false);
                        this.btndisabled = false;
                        this.formrefill.to_name = response.data.data.name;
                    }else{
                        $('#amount').prop('disabled', true);
                        $('#remark').prop('disabled', true);
                        // $('#btnsubmit').prop('disabled', true);
                        this.btndisabled = true;
                        this.formrefill.to_name = 'ไม่พบข้อมูลชื่อบัญชี โปรดตรวจสอบ';
                    }

                },

                async loadBanks() {
                    const response = await axios.post("{{ url($menu->currentRoute.'/loadbanks') }}");
                    this.option = {
                        account_code: response.data.bankss,
                        banks: response.data.banks
                    };
                },
                refillSubmit(event) {
                    event.preventDefault()
                    this.$http.post("{{ url($menu->currentRoute.'/refill') }}", this.formrefill)
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

