<b-modal ref="addedit" id="addedit" centered scrollable size="lg" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.prevent="addEditSubmit" v-if="show">
        <b-form-row>
            <b-col>
                <b-form-group
                    id="input-group-1"
                    label="ชื่อกิจกรรม:"
                    label-for="name"
                    description="">
                    <b-form-input
                        id="free_game_name"
                        v-model="formaddedit.free_game_name"
                        type="text"
                        size="sm"
                        placeholder="ชื่อกิจกรรม"
                        autocomplete="off"
                        required
                    ></b-form-input>
                </b-form-group>
            </b-col>
            <b-col>
                <b-form-group
                        id="input-group-1"
                        label="User Name:"
                        label-for="name"
                        description="">
                    <b-form-input
                            id="member_user"
                            v-model="formaddedit.member_user"
                            type="text"
                            size="sm"
                            placeholder="User ID"
                            autocomplete="off"
                            required
                    ></b-form-input>
                </b-form-group>
            </b-col>
        </b-form-row>

        <b-form-row>
            <b-col>
                <b-form-group id="input-group-2" label="ค่ายเกม :" label-for="product_id">
                    <b-form-select
                            id="product_id"
                            v-model="formaddedit.product_id"
                            :options="products"
                            size="sm"
                            v-on:change="changeProduct($event)"
                            required
                    ></b-form-select>
                </b-form-group>
            </b-col>
            <b-col>
                <b-form-group id="input-group-2" label="ค่ายเกม :" label-for="game_ids">
                    <b-form-select
                            id="game_ids"
                            v-model="formaddedit.game_ids"
                            :options="games"
                            size="sm"
                            required
                    ></b-form-select>
                </b-form-group>
            </b-col>
        </b-form-row>

        <b-form-row>
            <b-col>
                <b-form-group
                    id="input-group-2"
                    label="ยอดเงิน Bet:"
                    label-for="bet_amount"
                    description="ตาละกี่บาท">
                    <b-form-input
                        id="bet_amount"
                        v-model="formaddedit.bet_amount"
                        type="number"
                        size="sm"
                        placeholder=""
                        autocomplete="off"
                        required
                    ></b-form-input>
                </b-form-group>
            </b-col>
            <b-col>
                <b-form-group
                    id="input-group-2"
                    label="จำนวนฟรีเกม:"
                    label-for="game_count"
                    description="">
                    <b-form-input
                        id="game_count"
                        v-model="formaddedit.game_count"
                        type="number"
                        size="sm"
                        placeholder=""
                        autocomplete="off"
                        required
                    ></b-form-input>
                </b-form-group>
            </b-col>
        </b-form-row>
        <b-form-row>
            <b-col>
                <b-form-group
                        id="input-group-2"
                        label="ระยะเวลา (ชม):"
                        label-for="expired_date"
                        description="หมดอายุในกี่ชั่วโมง">
                    <b-form-input
                            id="expired_date"
                            v-model="formaddedit.expired_date"
                            type="number"
                            size="sm"
                            placeholder=""
                            autocomplete="off"
                            required
                    ></b-form-input>
                </b-form-group>
            </b-col>
            <b-col>

            </b-col>
        </b-form-row>



        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-modal>



@push('styles')
{{--    <link rel="stylesheet" href="{{ asset('vendor/summernote/summernote-lite.css') }}">--}}
@endpush
@push('scripts')
<script>
    function genModal(id) {
        window.app.genModal(id);
    }

    function listModal(id) {
        window.app.listModal(id);
    }

    function ViewModal(id) {
        window.app.ViewModal(id);
    }
</script>
{{--    <script src="{{ asset('vendor/summernote/summernote-lite.min.js') }}"></script>--}}
    <script type="module">

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: false,
                    fields: [],
                    items: [],
                    caption: null,
                    isBusy: false,
                    formmethod: 'add',
                    formaddedit: {
                        member_code: 0,
                        member_user: '',
                        gameuser_code: 0,
                        free_game_name: '',
                        expired_date: 0,
                        bet_amount: 0,
                        game_count: 0,
                        game_ids: '',
                        product_id: '',
                        game_name: ''
                    },
                    products: [{value: '', text: '== ค่ายเกม =='}],
                    games: [{value: '', text: '== ชื่อเกม =='}],

                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            mounted() {
                this.loadProduct();
            },
            methods: {
                async loadProduct() {
                    const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loadProduct') }}");
                    this.products = response.data.products;
                    // this.option = {
                    //     banks: response.data.banks,
                    //
                    // };
                },
                async loadGame(e) {
                    const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loadGame') }}", { product: e});
                    this.games = response.data.games;
                    // this.option = {
                    //     banks: response.data.banks,
                    //
                    // };
                },
                changeProduct(event){
                    console.log(event)
                    if(event!== ''){
                        this.loadGame(event);

                    }
                },
                // changeGame(event){
                //     console.log(event)
                //     if(event !== ''){
                //         this.formaddedit.game_name = this.$els.elSelect.text;
                //
                //     }
                // },
                genModal(code) {
                    this.$bvModal.msgBoxConfirm('ต้องการ GEN รายการคูปองตามค่าที่ตั้งไว้หรือไม่ เมื่อยืนยันการ GEN คูปอง จะไม่สามารถแก้ไขข้อมูลได้อีก.', {
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
                                this.$http.post("{{ url($menu->currentRoute.'/gen') }}", {
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
                                        // this.$refs.gamelog.refresh()
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
                listModal(code) {
                    this.code = code;
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.myLog();
                        this.$refs.gamelog.show();
                    })

                },
                async myLog() {
                    let self = this;
                    self.items = [];
                    const response = await axios.get("{{ url($menu->currentRoute.'/couponlist') }}", {
                        params: {
                            id: this.code
                        }
                    });


                    this.caption = response.data.name;

                    this.fields = [
                        {key: 'code', label: 'รหัสคูปอง'},
                        {key: 'status', label: 'สถานะใช้งาน'},
                        {key: 'member_code', label: 'ใช้โดย'},
                        {key: 'date', label: 'วันที่ใช้'}
                    ];
                    this.items = response.data.list;

                },
                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        name: '',
                        cashback: 'N',
                        amount: 1,
                        value: 0,
                        turnpro: 0,
                        amount_limit: 0,
                        date_start: '',
                        date_stop: '',
                        money: 0,
                        refill_start: '',
                        refill_stop: '',
                        date_expire: 0,
                        newuser: 'N',
                        norefill: 'N',
                        same_coupon: 'N',
                        enable: 'Y',
                    }

                    this.formmethod = 'edit';

                    this.show = false;
                    this.$nextTick(() => {

                        this.code = code;
                        this.loadData();
                        this.$refs.addedit.show();
                        this.show = true;

                    })
                },
                ViewModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        name: '',
                        cashback: 'N',
                        amount: 1,
                        value: 0,
                        turnpro: 0,
                        amount_limit: 0,
                        date_start: '',
                        date_stop: '',
                        money: 0,
                        refill_start: '',
                        refill_stop: '',
                        date_expire: 0,
                        newuser: 'N',
                        norefill: 'N',
                        same_coupon: 'N',
                        enable: 'Y',
                    }

                    this.formmethod = 'view';

                    this.show = false;
                    this.$nextTick(() => {

                        this.code = code;
                        this.loadData();
                        this.$refs.addedit.show();
                        this.show = true;

                    })
                },
                addModal() {
                    this.code = null;
                    this.formaddedit = {
                        name: '',
                        cashback: 'N',
                        amount: 1,
                        value: 0,
                        turnpro: 0,
                        amount_limit: 0,
                        date_start: '',
                        date_stop: '',
                        money: 0,
                        refill_start: '',
                        refill_stop: '',
                        date_expire: 0,
                        newuser: 'N',
                        norefill: 'N',
                        same_coupon: 'N',
                        enable: 'Y',
                    }
                    this.formmethod = 'add';

                    this.show = false;
                    this.$nextTick(() => {
                        this.$refs.addedit.show();
                        this.show = true;

                    })
                },
                async loadData() {
                    const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {id: this.code});
                    this.formaddedit = {
                        member_user: response.data.data.member_user,
                        free_game_name: response.data.data.free_game_name,
                        expired_date: response.data.data.expired_date,
                        bet_amount: response.data.data.bet_amount,
                        game_count: response.data.data.game_count,
                        product_id: response.data.data.product_id,
                        game_ids: response.data.data.game_ids,
                        game_name: response.data.data.game_name,
                        emp_code: response.data.data.emp_code
                    }
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

