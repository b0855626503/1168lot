<b-modal ref="addedit" id="addedit" centered scrollable size="lg" title="{{ $menu->currentName }}" :no-stacking="false"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">
        <b-form @submit.stop.prevent="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">
            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-name"
                        label="ชื่อโปร:"
                        label-for="name_th"
                        description="ระบุ ชื่อโปร">
                        <b-form-input
                            id="name_th"
                            v-model="formaddedit.name_th"
                            type="text"
                            size="sm"
                            placeholder="ชื่อโปร"
                            autocomplete="off"
                            required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>

                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-2"
                        label="ลำดับ:"
                        label-for="sort"
                        description="">
                        <b-form-input
                            id="sort"
                            v-model="formaddedit.sort"
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

                <b-col cols="12" md="6">
                    <div class="form-group {!! $errors->has('filepic.*') ? 'has-error' : '' !!}">
                        <label>รูปภาพ (300 x 250)</label>
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
                <b-col cols="12" md="6">


                    <b-form-checkbox
                        id="enable"
                        v-model="formaddedit.enable"
                        value="Y"
                        unchecked-value="N">
                        สถานะใช้งาน
                    </b-form-checkbox>

                </b-col>

            </b-form-row>

            <hr>

            <b-form-row>

                <b-col>

                    <b-form-group
                        id="input-group-content"
                        label="รายละเอียดของโปรโมชั่น:"
                        label-for="content"
                        description="">

                        <summernote id="content" v-model="formaddedit.content" ref="editor"></summernote>

                    </b-form-group>

                </b-col>

            </b-form-row>


            <b-button type="submit" variant="primary">บันทึก</b-button>

        </b-form>
    </b-container>
</b-modal>


@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/summernote/summernote-lite.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('vendor/summernote/summernote-lite.min.js') }}"></script>

    <script type="text/javascript">

        function delSub(id, table) {
            window.app.delSub(id, table);
        }
    </script>
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
                        content: '',
                        sort: 0,
                        enable: 'Y'
                    },
                    option: {
                        length_type: [
                            {text: '== เลือก ==', value: ''},
                            {text: 'จ่ายเป็น บาท', value: 'PRICE'},
                            {text: 'จ่ายเป็น %', value: 'PERCENT'},
                            {text: 'ช่วงเวลา จ่ายเป็น บาท', value: 'TIME'},
                            {text: 'ช่วงเวลา จ่ายเป็น %', value: 'TIMEPC'},
                            {text: 'ช่วงราคาตรงกัน จ่ายเป็น %', value: 'AMOUNT'},
                            {text: 'ช่วงราคาตรงกัน จ่ายเป็น %', value: 'AMOUNTPC'},
                            {text: 'ช่วงระหว่างราคา จ่ายเป็น บาท', value: 'BETWEEN'},
                            {text: 'ช่วงระหว่างราคา จ่ายเป็น %', value: 'BETWEENPC'},
                        ],
                    },
                    imgpath: '/storage/procontent_img/'
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
                        content: '',
                        sort: 0,
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

                    this.formaddedit = {

                        name_th: '',
                        content: '',
                        sort: 0,
                        enable: 'Y',
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
                        content: response.data.data.content,
                        sort: response.data.data.sort,
                        enable: response.data.data.enable
                    }


                    if (response.data.data.filepic) {
                        // this.trigger++;
                        this.formaddedit.filepic = response.data.data.filepic;

                    }
                },
                setImage(value) {
                    // this.trigger++;
                    this.formaddedit.filepic = value;
                    console.log('Set :' + this.formaddedit.filepic);
                },
                clearImage() {
                    this.trigger++;
                    this.formaddedit.filepic = '';
                    console.log('Clear :' + this.formaddedit.filepic);
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
                        content: this.formaddedit.content,
                        sort: this.formaddedit.sort,
                        enable: this.formaddedit.enable
                    });

                    formData.append('data', json);
                    // formData.append('filepic', $('input[type="file"]')[0].files[0]);
                    formData.append('fileupload', this.fileupload);


                    const config = {headers: {'Content-Type': `multipart/form-data; boundary=${formData._boundary}`}};

                    axios.post(url, formData, config)
                        .then(response => {
                            this.$bvModal.hide('addedit');
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

                },

            },
        });
    </script>
@endpush

