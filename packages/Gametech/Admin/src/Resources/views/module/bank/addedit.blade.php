<b-modal ref="addedit" id="addedit" centered size="sm" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.prevent="addEditSubmitNew" v-if="show">

        <b-form-group
            id="input-group-1"
            label="อักษรย่อ (EN):"
            label-for="shortcode"
            description="ระบุอักษรย่อ (EN)">
            <b-form-input
                id="shortcode"
                v-model="formaddedit.shortcode"
                type="text"
                size="sm"
                placeholder="อักษรย่อ (EN)"
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>

        <b-form-group
            id="input-group-2"
            label="ชื่อธนาคาร (TH):"
            label-for="name_th"
            description="ระบุชื่อธนาคาร">
            <b-form-input
                id="name"
                v-model="formaddedit.name_th"
                type="text"
                size="sm"
                placeholder="ชื่อธนาคาร"
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>


        <b-form-group
            id="input-group-3"
            label="ชื่อธนาคาร (EN):"
            label-for="name_en"
            description="ระบุชื่อธนาคาร">
            <b-form-input
                id="name_en"
                v-model="formaddedit.name_en"
                type="text"
                size="sm"
                placeholder="ชื่อธนาคาร (EN)"
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>

        <b-form-group
            id="input-group-4"
            label="เวบไซต์:"
            label-for="website"
            description="ระบุเวบไซต์">
            <b-form-input
                id="website"
                v-model="formaddedit.website"
                type="text"
                size="sm"
                placeholder="เวบไซต์"
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>

        <div class="form-group {!! $errors->has('filepic.*') ? 'has-error' : '' !!}">
            <label>รูปภาพ</label>
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

        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-modal>

@push('scripts')
    <script type="module">

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: false,
                    trigger: 0,
                    fileupload: '',
                    formmethod: 'edit',
                    formaddedit: {
                        name_th: '',
                        name_en: '',
                        shortcode: '',
                        website: '',
                        filepic: ''
                    },
                    imgpath: '/storage/bank_img/'
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
                        name_th: '',
                        name_en: '',
                        shortcode: '',
                        website: ''

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
                        name_th: '',
                        name_en: '',
                        shortcode: '',
                        website: '',
                        filepic: ''
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
                    const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {id: this.code});
                    this.formaddedit = {
                        name_th: response.data.data.name_th,
                        name_en: response.data.data.name_en,
                        shortcode: response.data.data.shortcode,
                        website: response.data.data.website
                    };
                    if (response.data.data.filepic) {
                        this.trigger++;
                        this.formaddedit.filepic = response.data.data.filepic;
                    }
                },
                clearImage() {
                    this.trigger++;
                    this.formaddedit.filepic = '';
                    // console.log('Clear :' + this.formaddedit.filepic);
                },
                handleUpload(value) {
                    this.fileupload = value;
                },
                addEditSubmitNew(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    if (this.formmethod === 'add') {
                        var url = "{{ route('admin.'.$menu->currentRoute.'.create') }}";
                    } else if (this.formmethod === 'edit') {
                        var url = "{{ route('admin.'.$menu->currentRoute.'.update') }}/" + this.code;
                    }




                    let formData = new FormData();
                    const json = JSON.stringify({
                        name_th: this.formaddedit.name_th,
                        name_en: this.formaddedit.name_en,
                        shortcode: this.formaddedit.shortcode,
                        website: this.formaddedit.website,
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

