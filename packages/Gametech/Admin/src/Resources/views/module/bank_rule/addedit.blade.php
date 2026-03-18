<b-modal ref="addedit" id="addedit" centered scrollable size="lg" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.stop.prevent="addEditSubmit" v-if="show" id="frmaddedit">

        <b-form-group
            id="input-group-types"
            label="เงื่อนไข:"
            label-for="types"
            description="">

            <b-form-select
                id="types"
                name="types"
                v-model="formaddedit.types"
                :options="option.types"
                size="sm"
                required
            ></b-form-select>

        </b-form-group>

        <b-form-group
            id="input-group-bank_code"
            label="ลูกค้าที่สมัครด้วย:"
            label-for="bank_code"
            description="ลูกค้าที่สมัครด้วย ธนาคารนี้">

            <b-form-select
                id="bank_code"
                name="bank_code"
                v-model="formaddedit.bank_code"
                :options="option.bank_code"
                size="sm"
                required
            ></b-form-select>

        </b-form-group>

        <b-form-group
            id="input-group-method"
            label="รูปแบบ:"
            label-for="method"
            description="">

            <b-form-select
                id="method"
                name="method"
                v-model="formaddedit.method"
                :options="option.method"
                size="sm"
                required
            ></b-form-select>

        </b-form-group>


        <b-form-group
            id="input-group-bank_number"
            label="ธนาคาร:"
            label-for="bank_number"
            description="">
            <b-form-select
                class="select2"
                multiple="multiple"
                id="bank_number"
                v-model="formaddedit.bank_number"
                :options="option.bank_number"
                size="sm"
                required
            ></b-form-select>

        </b-form-group>

        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-modal>

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

@endpush
@push('scripts')

    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    <script type="module">

            window.app = new Vue({
                el: '#app',
                data() {
                    return {
                        show: false,
                        formmethod: 'add',
                        formaddedit: {
                            bank_code: '',
                            method: 'CAN',
                            types: 'IF',
                            bank_number: ''
                        },
                        option: {
                            types: [{text: 'ถ้าเป็น', value: 'IF'}, {text: 'ถ้าไม่เป็น', value: 'IFNOT'}],
                            method: [{text: 'จะสามารถเห็น', value: 'CAN'}, {text: 'จะไม่สามารถเห็น', value: 'CANNOT'}],
                            bank_code: [],
                            bank_number: []
                        }
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
                            bank_code: '',
                            bank_number: '',
                            method: 'CAN',
                            types: 'IF',
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
                            bank_code: '',
                            bank_number: '',
                            method: 'CAN',
                            types: 'IF',
                        }
                        this.formmethod = 'add';

                        this.show = false;
                        this.$nextTick(() => {

                            this.show = true;
                            setTimeout(() => {
                                $('.select2').select2({
                                    theme: 'bootstrap4'
                                });
                            }, 0);
                            this.$refs.addedit.show();
                        })
                    },
                    async loadData() {
                        const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {id: this.code});

                        this.formaddedit = {
                            bank_code: response.data.data.bank_code,
                            bank_number: response.data.data.bank_number,
                            method: response.data.data.method,
                            types: response.data.data.types,
                        }


                        setTimeout(() => {
                            $('.select2').select2({
                                theme: 'bootstrap4'
                            });
                            var arrayArea = response.data.data.bank_number.split(',');
                            console.log(arrayArea);
                            $(".select2").val(arrayArea).trigger('change');
                        }, 0);

                    },
                    async loadBank() {
                        const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loadbank') }}");
                        this.option = {
                            bank_code: response.data.banks,
                            bank_number: response.data.banks,
                            types: [{text: 'ถ้าเป็น', value: 'IF'}, {text: 'ถ้าไม่เป็น', value: 'IFNOT'}],
                            method: [{text: 'จะสามารถเห็น', value: 'CAN'}, {text: 'จะไม่สามารถเห็น', value: 'CANNOT'}],
                        };


                    },
                    addEditSubmit(event) {
                        event.preventDefault();
                        this.toggleButtonDisable(true);

                        if (this.formmethod === 'add') {
                            var url = "{{ route('admin.'.$menu->currentRoute.'.create') }}";
                        } else if (this.formmethod === 'edit') {
                            var url = "{{ route('admin.'.$menu->currentRoute.'.update') }}/" + this.code;
                        }

                        let form = $('#frmaddedit')[0];
                        let formData = new FormData(form);
                        formData.append('bank_number', $("#bank_number").select2('val'));

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
                                        event.stopPropagation();
                                        var id = $(this).attr('id');
                                        document.getElementById(id).classList.remove("is-invalid");
                                    });
                                }
                            })
                            .catch(errors => {
                                Toast.fire({
                                    icon: 'error',
                                    title: errors.response.data
                                })
                            });

                        // this.$http.post(url, {id: this.code, data: formData})
                        //     .then(response => {
                        //         this.$refs.addedit.hide();
                        //
                        //         this.$bvModal.msgBoxOk(response.data.message, {
                        //             title: 'ผลการดำเนินการ',
                        //             size: 'sm',
                        //             buttonSize: 'sm',
                        //             okVariant: 'success',
                        //             headerClass: 'p-2 border-bottom-0',
                        //             footerClass: 'p-2 border-top-0',
                        //             centered: true
                        //         });
                        //         window.LaravelDataTables["dataTableBuilder"].draw(false);
                        //     })
                        //     .catch(exception => {
                        //         console.log('error');
                        //     });

                    }


                },
            });

    </script>
@endpush

