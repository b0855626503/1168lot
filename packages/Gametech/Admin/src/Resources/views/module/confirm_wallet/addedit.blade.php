<b-modal ref="addedit" id="addedit" centered size="sm" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">

            <b-form-group
                id="input-group-remark"
                label="หมายเหตุ:"
                label-for="remark"
                description="โปรดระบุ หมายเหตุในการอนุมัติ">
                <b-form-input
                    id="remark"
                    v-model="formaddedit.remark"
                    type="text"
                    size="sm"
                    placeholder=""
                    autocomplete="off"
                    required
                ></b-form-input>
            </b-form-group>


            <b-button type="submit" variant="primary">บันทึก</b-button>

        </b-form>
    </b-container>
</b-modal>

<b-modal ref="clear" id="clear" centered size="sm" title="โปรดระบุหมายเหตุ ในการทำรายการ" :no-stacking="true"
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
    <script type="text/javascript">
        function clearModal(id) {
            window.app.clearModal(id);
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
                        remark: ''
                    },
                    formclear: {
                        remark: ''

                    },
                    option: {
                        banks: [],
                    },
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(true);
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
                        remark: '',

                    }
                    this.formmethod = 'edit';

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.code = code;
                        // this.loadData();
                        this.$refs.addedit.show();

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
                async loadData() {
                    const response = await axios.post("{{ url($menu->currentRoute.'/loaddata') }}", {id: this.code});
                    this.formaddedit = {
                        acc_name: response.data.data.acc_name,
                        acc_no: response.data.data.acc_no,
                        banks: response.data.data.banks,
                        user_name: response.data.data.user_name,
                        user_pass: response.data.data.user_pass,
                        sort: response.data.data.sort
                    };

                },
                async loadBank() {
                    const response = await axios.post("{{ url($menu->currentRoute.'/loadbank') }}");
                    this.option = {
                        banks: response.data.banks
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
                        remark: this.formaddedit.remark
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

