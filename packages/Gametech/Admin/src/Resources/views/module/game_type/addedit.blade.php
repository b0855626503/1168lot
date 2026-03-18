<b-modal ref="addedit" id="addedit" centered size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">
            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-id"
                        label="ประเภทของเกม:"
                        label-for="id"
                        description="">

                        <b-form-select
                            id="id"
                            v-model="formaddedit.id"
                            :options="option.id"
                            size="sm"
                            required
                            disabled
                        ></b-form-select>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-checkbox
                        id="status_open"
                        v-model="formaddedit.status_open"
                        value="Y"
                        unchecked-value="N">
                        สถานะแสดงผล
                    </b-form-checkbox>
                    @if(auth()->guard('admin')->user()->superadmin == 'Y')
                        <b-form-checkbox
                            id="enable"
                            v-model="formaddedit.enable"
                            value="Y"
                            unchecked-value="N">
                            สถานะใช้งาน
                        </b-form-checkbox>
                    @endif
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <div class="form-group {!! $errors->has('filepic.*') ? 'has-error' : '' !!}">
                        <label>รูปภาพ png (1921 x 451)</label>
                        <image-wrapper
                            @clear="clearImage"
                            @upload="handleUpload($event)"
                            button-label="เพิ่มรูปภาพ"
                            :removed="true"
                            input-name="filepic"
                            :multiple="false"
                            :images="formaddedit.filepic"
                            :imgpath="imgpath"
                            v-bind:testProp.sync="trigger"></image-wrapper>
                    </div>
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-1"
                        label="หัวข้อ:"
                        label-for="title"
                        description="">
                        <b-form-input
                            id="title"
                            v-model="formaddedit.title"
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
                        id="input-group-1"
                        label="รายละเอียด:"
                        label-for="content"
                        description="">
                        <b-form-input
                            id="content"
                            v-model="formaddedit.content"
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

<b-modal ref="debug" id="debug" centered scrollable size="lg" title="Debug API" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">

    <div class="card">
        <div class="card-header">
            Body Response
        </div>
        <div class="card-body">
            <pre id="body"></pre>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            Json Response
        </div>
        <div class="card-body">
            <pre id="json"></pre>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Successful Response
        </div>
        <div class="card-body" id="successful">

        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Failed Response
        </div>
        <div class="card-body" id="failed">

        </div>
    </div>

    <div class="card">
        <div class="card-header">
            ServerError Response
        </div>
        <div class="card-body" id="serverError">

        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Client Response
        </div>
        <div class="card-body" id="clientError">

        </div>
    </div>


</b-modal>

<b-modal ref="debug_free" id="debug_free" centered scrollable size="lg" title="Debug API Free" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">

    <div class="card">
        <div class="card-header">
            Body Response
        </div>
        <div class="card-body">
            <pre id="body_free"></pre>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            Json Response
        </div>
        <div class="card-body">
            <pre id="json_free"></pre>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Successful Response
        </div>
        <div class="card-body" id="successful_free">

        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Failed Response
        </div>
        <div class="card-body" id="failed_free">

        </div>
    </div>

    <div class="card">
        <div class="card-header">
            ServerError Response
        </div>
        <div class="card-body" id="serverError_free">

        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Client Response
        </div>
        <div class="card-body" id="clientError_free">

        </div>
    </div>


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
                    formmethod: 'edit',
                    fileupload: '',
                    formaddedit: {
                        id: '',
                        status_open: 'Y',
                        enable: 'Y',
                        filepic: '',
                        title:'',
                        content:''
                    },
                    option: {
                        id: [{text: '== เลือก ==', value: ''}, {
                            text: 'SLOT',
                            value: 'SLOT'
                        }, {text: 'CASINO', value: 'CASINO'}, {text: 'SPORT', value: 'SPORT'}],
                    },
                    imgpath: '/storage/gametype_img/'
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
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
                handleUpload(value) {
                    this.fileupload = value;
                },
                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        id: '',
                        status_open: 'Y',
                        enable: 'Y'

                    }
                    this.formmethod = 'edit';
                    this.fileupload = '';
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
                        id: '',
                        status_open: 'Y',
                        enable: 'Y',
                        title:'',
                        content:''
                    }
                    this.formmethod = 'add';
                    this.fileupload = '';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();

                    })
                },

                async loadData() {

                    try {
                        const responses = axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {id: this.code});

                        const response = await responses;

                        this.formaddedit = {
                            id: response.data.data.id,
                            status_open: response.data.data.status_open,
                            enable: response.data.data.enable,
                            title: response.data.data.title,
                            content: response.data.data.content,

                        };
                        if (response.data.data.filepic) {
                            this.trigger++;
                            this.formaddedit.filepic = response.data.data.filepic;
                        }

                    } catch (error) {
                        console.log(error)
                    }
                },
                addEditSubmitNew(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);
                    var url = "{{ route('admin.'.$menu->currentRoute.'.update') }}/" + this.code;


                    let formData = new FormData();
                    const json = JSON.stringify({
                        id: this.formaddedit.id,
                        status_open: this.formaddedit.status_open,
                        enable: this.formaddedit.enable,
                        title: this.formaddedit.title,
                        content: this.formaddedit.content,
                    });

                    formData.append('data', json);
                    formData.append('fileupload', this.fileupload);


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
                        .catch(errors => console.log(errors));

                }
            },
        });

    </script>
@endpush

