<b-modal ref="addedit" id="addedit" centered scrollable size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-form @submit.stop.prevent="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">
        <b-form-row>
            <b-col cols="12" md="6">
                <b-form-group
                    id="input-group-types"
                    label="ประเภทรางวัล:"
                    label-for="types"
                    description="">

                    <b-form-select
                        id="types"
                        v-model="formaddedit.types"
                        :options="option.types"
                        size="sm"
                    ></b-form-select>
                </b-form-group>
                <b-form-group
                    id="input-group-1"
                    label="ชื่อของรางวัล:"
                    label-for="name"
                    description="ชื่อของรางวัล">
                    <b-form-input
                        id="name"

                        v-model="formaddedit.name"
                        type="text"
                        size="sm"
                        placeholder="ชื่อของรางวัล"
                        autocomplete="off"
                        required
                    ></b-form-input>
                </b-form-group>
            </b-col>
            <b-col cols="12" md="6">
                <b-form-group
                    id="input-group-2"
                    label="จำนวนที่ได้:"
                    label-for="amount"
                    description="จำนวนที่ได้">
                    <b-form-input
                        id="amount"
                        v-model="formaddedit.amount"
                        type="number"
                        size="sm"
                        placeholder="จำนวนที่ได้"
                        autocomplete="off"
                        required
                    ></b-form-input>
                </b-form-group>
            </b-col>
        </b-form-row>

        <b-form-row>
            <b-col cols="12" md="6">
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
            </b-col>
            <b-col cols="12" md="6">
                <b-form-group
                    id="input-group-3"
                    label="โอกาสออก:"
                    label-for="name"
                    description="ระบุโอกาสที่จะได้รับรางวัล">
                    <b-form-input
                        id="winloss"
                        v-model="formaddedit.winloss"
                        type="number"
                        size="sm"
                        placeholder="โอกาส"
                        autocomplete="off"
                        required
                    ></b-form-input>
                </b-form-group>
            </b-col>
        </b-form-row>
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
                        name: '',
                        amount: '',
                        winloss: 0,
                        filepic: '',
                        types: 'WALLET'
                    },
                    option: {
                        @if($webconfig->seamless == 'Y')
                        types: [
                            {text: 'Wallet', value: 'WALLET'},
                            {text: 'BONUS', value: 'BONUS'},
                            {text: 'Diamond', value: 'DIAMOND'},
                            {text: 'Point', value: 'POINT'},
                        ],
                        @else
                        types: [
                            {text: 'Wallet', value: 'WALLET'},
                            {text: 'Credit', value: 'CREDIT'},
                            {text: 'Diamond', value: 'DIAMOND'},
                            {text: 'Point', value: 'POINT'},
                            // {text: 'รางวัลจริง', value: 'REAL'}
                        ],
                        @endif
                    },
                    imgpath: '/storage/spin_img/'
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
                        name: '',
                        amount: '',
                        winloss: 0,
                        types: ''

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
                        amount: '',
                        winloss: 0,
                        filepic: '',
                        types: ''
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
                        amount: response.data.data.amount,
                        winloss: response.data.data.winloss,
                        types: response.data.data.types
                    }

                    if (response.data.data.filepic) {
                        this.trigger++;
                        this.formaddedit.filepic = response.data.data.filepic;

                        // $('img.preview').attr('src','/assets/images/spin_img/'+ this.formaddedit.filepic);
                        // this.getImg(this.formaddedit.filepic);
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
                    var url = "{{ route('admin.'.$menu->currentRoute.'.update') }}/" + this.code;


                    let formData = new FormData();
                    const json = JSON.stringify({
                        name: this.formaddedit.name,
                        amount: this.formaddedit.amount,
                        winloss: this.formaddedit.winloss,
                        types: this.formaddedit.types,

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

