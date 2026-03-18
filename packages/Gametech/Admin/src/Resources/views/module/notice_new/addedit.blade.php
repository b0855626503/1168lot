<b-modal ref="addedit" id="addedit" centered scrollable size="lg" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.prevent="addEditSubmit" v-if="show">

        <b-form-group
            id="input-group-route"
            label="หน้าเวบที่แสดงข้อความ:"
            label-for="route"
            description="">

            <b-form-select
                id="route"
                name="route"
                v-model="formaddedit.route"
                :options="option.route"
                size="sm"
                required

            ></b-form-select>
        </b-form-group>


        <b-form-group
            id="input-group-content"
            label="รายละเอียดของโปรโมชั่น:"
            label-for="content"
            description="">

            <summernote id="message" v-model="formaddedit.message" ref="editor"></summernote>

        </b-form-group>

        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-modal>

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.1/summernote-bs4.min.css" integrity="sha512-rDHV59PgRefDUbMm2lSjvf0ZhXZy3wgROFyao0JxZPGho3oOuWejq/ELx0FOZJpgaE5QovVtRN65Y3rrb7JhdQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.1/summernote-bs4.min.js" integrity="sha512-/DlF8zrT3XyUWEK7bmU1v7Q0kMXctQfqNwyzCNBB/mdUFxz87bq3X4TqadyuQBJW39g29t1tLNbHYLpXLs1zVA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.9.1/lang/summernote-en-US.min.js" integrity="sha512-IAPKzC0kpHVJzPKcRDuv3dzcFbU85cgFqbwAf+w5TM45/UOYiek4dZT2Xrz3Lxpn/VaBWikCWesWV/hub2uBQA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ asset('vendor/summernote/plugin/attribute/summernote-image-attributes.js') }}"></script>

    <script type="module">

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: false,
                    formmethod: 'add',
                    formaddedit: {
                        message: '',
                        route: '',
                    },
                    option: {
                        route: [
                            {value: 'customer.home.index', text: 'หน้าแรกสมาชิก'},
                            {value: 'customer.topup.index', text: 'หน้าเติมเงิน'},
                            {value: 'customer.topup.index_papayapay', text: 'หน้าเติมเงิน PromptPay'},
                            {value: 'customer.profile.index', text: 'หน้าข้อมูลส่วนตัว'},
                            {value: 'customer.spin.index', text: 'หน้าวงล้อมหาสนุก'},
                            {value: 'customer.promotion.index', text: 'หน้าโปรโมชั่น'},
                            {value: 'customer.transfer.game.index', text: 'หน้าโยก Wallet เข้าเกม'},
                            {value: 'customer.transfer.wallet.index', text: 'หน้าโยก เกม เข้า Wallet'},
                            {value: 'customer.withdraw.index', text: 'หน้าแจ้งถอน Wallet'},
                            {value: 'customer.credit.index', text: 'หน้า Cashback'},
                            {value: 'customer.credit.transfer.game.index', text: 'หน้าโยก Cashback เข้า เกม'},
                            {value: 'customer.credit.transfer.wallet.index', text: 'หน้าโยก เกม เข้า Cashback'},
                            {value: 'customer.credit.withdraw.index', text: 'หน้าแจ้งถอน Cashback'},
                            {value: 'customer.contributor.index', text: 'หน้าแนะนำเพื่อน'}

                        ]
                    },

                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            methods: {

                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        message: '',
                        route: '',
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
                addModal() {
                    this.code = null;
                    this.formaddedit = {
                        message: '',
                        route: '',
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
                    this.formaddedit.route = response.data.data.route;
                    this.formaddedit.message = response.data.data.message;
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

