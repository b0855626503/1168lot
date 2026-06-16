<b-modal ref="addedit" id="addedit" centered size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">
            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-id"
                        label="ค่ายเกม:"
                        label-for="id"
                        description="">

                        <b-form-select
                            id="id"
                            v-model="formaddedit.id"
                            :options="option.id"
                            size="sm"
                            required
                        ></b-form-select>
                    </b-form-group>
                </b-col>
                <b-col>

                </b-col>

            </b-form-row>


            <b-button type="submit" variant="primary">บันทึก</b-button>

        </b-form>
    </b-container>
</b-modal>


@push('scripts')
    <script type="text/javascript">
        function debug(id, method) {
            window.app.debug(id, method);
        }

        function debug_free(id, method) {
            window.app.debug_free(id, method);
        }
    </script>
    <script type="module">

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: false,
                    trigger: 0,
                    formmethod: 'add',
                    fileupload: '',
                    fileupload2: '',
                    product:'',
                    formaddedit: {
                        id: '',
                    },
                    option: {
                        id: [{text: 'ไม่ระบุ', value: ''}],
                        method: [{text: 'AmbSuperApi', value: 'seamless'}, {text: 'CloneApi', value: 'seamlesss'}],
                        mobile: [{text: 'ใช้งาน', value: 'Y'}, {text: 'ปิดใช้งาน', value: 'N'}],
                        batch_game: [{text: 'Yes', value: 'Y'}, {text: 'No', value: 'N'}],
                        auto_open: [{text: 'Yes', value: 'Y'}, {text: 'No', value: 'N'}],
                        game_type: [{text: '== เลือก ==', value: ''}, {
                            text: 'Slot',
                            value: 'SLOT'
                        }, {text: 'Casino', value: 'CASINO'}, {text: 'Sport', value: 'SPORT'}],
                    },
                    imgpath: '/storage/game_img/',
                    iconpath: '/storage/icon_img/',
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            mounted() {

                this.loadProvider();
            },
            methods: {
                debug(id, method) {
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.loadDebug(id, method);
                        this.$refs.debug.show();
                    })
                },
                debug_free(id, method) {
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.loadDebugFree(id, method);
                        this.$refs.debug_free.show();
                    })
                },
                clearImage() {
                    this.trigger++;
                    this.formaddedit.filepic = '';

                },
                clearImage2() {
                    this.trigger++;
                    this.formaddedit.icon = '';

                },
                handleUpload(value) {
                    this.fileupload = value;
                },
                handleUpload2(value) {
                    this.fileupload2 = value;
                },
                addModal() {
                    this.code = null;
                    this.formaddedit = {
                        id: '',
                    }
                    this.formmethod = 'add';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();

                    })
                },
                async loadDebug(id, method) {

                    try {

                        const responses = axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddebug') }}", {
                            id: id,
                            method: method
                        });

                        const response = await responses;

                        $.each(response.data.debug, function (k, v) {
                            document.getElementById('body').textContent += JSON.stringify(JSON.parse(v.body), null, 2);
                            document.getElementById('json').textContent += JSON.stringify(v.json, null, 2);
                            document.getElementById('successful').textContent += v.successful.toString();
                            document.getElementById('failed').textContent += v.failed.toString();
                            document.getElementById('clientError').textContent += v.clientError.toString();
                            document.getElementById('serverError').textContent += v.serverError.toString();
                        });

                    } catch (error) {
                        console.error(error.message)
                    }

                },
                async loadDebugFree(id, method) {

                    try {

                        const responses = axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddebugfree') }}", {
                            id: id,
                            method: method
                        });

                        const response = await responses;

                        $.each(response.data.debug, function (k, v) {
                            document.getElementById('body_free').textContent = JSON.stringify(JSON.parse(v.body), null, 2);
                            document.getElementById('json_free').textContent = JSON.stringify(v.json, null, 2);
                            document.getElementById('successful_free').textContent = v.successful.toString();
                            document.getElementById('failed_free').textContent = v.failed.toString();
                            document.getElementById('clientError_free').textContent = v.clientError.toString();
                            document.getElementById('serverError_free').textContent = v.serverError.toString();
                        });

                    } catch (error) {
                        console.error(error.message)
                    }

                },
                async loadData() {

                    try {
                        const responses = axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {id: this.code});

                        const response = await responses;

                        this.product = response.data.data.id;
                        this.loadBetLimit();

                        this.formaddedit = {
                            name: response.data.data.name,
                            game_type: response.data.data.game_type,
                            method: response.data.data.method,
                            sort: response.data.data.sort,
                            status_open: response.data.data.status_open,
                            enable: response.data.data.enable,
                            limit: response.data.data.limit,
                            mobile: response.data.data.mobile

                        };




                        if (response.data.data.filepic) {
                            this.trigger++;
                            this.formaddedit.filepic = response.data.data.filepic;
                        }

                        if (response.data.data.icon) {
                            this.trigger++;
                            this.formaddedit.icon = response.data.data.icon;
                        }

                    } catch (error) {
                        console.log(error)
                    }
                },
                async loadProvider() {

                    try {
                        const responses = axios.post("{{ route('admin.'.$menu->currentRoute.'.loadprovider') }}");

                        const response = await responses;

                        this.option.id = response.data.data;
                        // this.loadBetLimit();


                    } catch (error) {
                        console.log(error)
                    }
                },
                async loadBetLimit() {
                    const response = await axios.post("{{ url($menu->currentRoute.'/loadBetLimit') }}", {id: this.product});
                    this.option.limit = response.data.limit;
                },
                addEditSubmitNew(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);
                    var url = "{{ route('admin.'.$menu->currentRoute.'.create') }}";


                    let formData = new FormData();
                    const json = JSON.stringify({
                        id: this.formaddedit.id,
                    });

                    formData.append('data', json);
                    // formData.append('fileupload', this.fileupload);
                    // formData.append('fileupload2', this.fileupload2);


                    const config = {headers: {'Content-Type': `multipart/form-data; boundary=${formData._boundary}`}};

                    axios.post(url, formData, config)
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
                        .catch(error => {
                            const message = error.response?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่';
                            this.$bvModal.msgBoxOk(message, {
                                title: 'ข้อผิดพลาด',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'danger',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });
                            this.toggleButtonDisable && this.toggleButtonDisable(false);
                        });

                }
            },
        });

    </script>
@endpush

