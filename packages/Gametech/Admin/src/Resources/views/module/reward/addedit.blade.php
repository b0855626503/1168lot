<b-modal ref="addedit" id="addedit" centered scrollable size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-form @submit.stop.prevent="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">

        <b-form-group
            id="input-group-1"
            label="ชื่อรางวัล:"
            label-for="name"
            description="">
            <b-form-input
                id="name"
                v-model="formaddedit.name"
                type="text"
                size="sm"
                placeholder=""
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>

        <b-form-group
            id="input-group-2"
            label="จำนวนรางวัล:"
            label-for="qty"
            description="">
            <b-form-input
                id="qty"

                v-model="formaddedit.qty"
                type="number"
                size="sm"
                placeholder=""
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>

        <b-form-group
            id="input-group-2"
            label="แต้มที่ใช้แลกรางวัล:"
            label-for="points"
            description="">
            <b-form-input
                id="points"
                v-model="formaddedit.points"
                type="number"
                size="sm"
                placeholder=""
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>

        <b-form-group
            id="input-group-3"
            label="รายละเอียด (ย่อ):"
            label-for="short_details"
            description="">
            <b-form-input
                id="short_details"
                v-model="formaddedit.short_details"
                type="text"
                size="sm"
                placeholder=""
                autocomplete="off"

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

        <hr>
        <b-form-group
            id="input-group-3"
            label="รายละเอียด:"
            label-for="details"
            description="">

            <summernote id="details" v-model="formaddedit.details" ref="editor"></summernote>

        </b-form-group>

        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-modal>

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/summernote/summernote-lite.min.css') }}">
@endpush
@push('scripts')
    <script src="{{ asset('vendor/summernote/summernote-lite.min.js') }}"></script>

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
                        name: '',
                        qty: 1,
                        points: '',
                        short_details: '',
                        details: '',
                        filepic: ''
                    },
                    imgpath: '/storage/reward_img/'
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            methods: {
                editModal(code) {
                    // this.show = false;
                    this.code = null;
                    this.formaddedit = {
                        name: '',
                        qty: '',
                        points: '',
                        short_details: '',
                        details: '',
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
                        name: '',
                        qty: 1,
                        points: '',
                        short_details: '',
                        details: '',
                        filepic: '',
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
                        name: response.data.data.name,
                        qty: response.data.data.qty,
                        points: response.data.data.points,
                        short_details: response.data.data.short_details,
                        details: response.data.data.details,
                    }

                    if (response.data.data.filepic) {
                        this.trigger++;
                        this.formaddedit.filepic = response.data.data.filepic;

                        // $('img.preview').attr('src','/assets/images/spin_img/'+ this.formaddedit.filepic);
                        // this.getImg(this.formaddedit.filepic);
                    }

                },
                setImage(value) {
                    this.trigger++;
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
                        name: this.formaddedit.name,
                        qty: this.formaddedit.qty,
                        points: this.formaddedit.points,
                        short_details: this.formaddedit.short_details,
                        details: this.formaddedit.details
                    });

                    formData.append('data', json);

                    formData.append('fileupload', this.fileupload);

                    // const formData = new FormData(this.$refs.addedit);

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

