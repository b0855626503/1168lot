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
                    <b-form-group
                        id="input-group-id"
                        label="รหัสโปร:"
                        label-for="id"
                        description="ระบุรหัสโปร">
                        @if(auth()->guard('admin')->user()->superadmin == 'N')
                            <b-form-select
                                    id="id"
                                    v-model="formaddedit.id"
                                    :options="option.id"
                                    size="sm"
                                    required
                            ></b-form-select>
{{--                            <b-form-input--}}
{{--                                id="id"--}}
{{--                                v-model="formaddedit.id"--}}
{{--                                type="text"--}}
{{--                                size="sm"--}}
{{--                                placeholder="รหัสโปร"--}}
{{--                                autocomplete="off"--}}
{{--                                required--}}
{{--                                readonly--}}

{{--                            ></b-form-input>--}}
                        @else
                            <b-form-select
                                    id="id"
                                    v-model="formaddedit.id"
                                    :options="option.id"
                                    size="sm"
                                    required
                            ></b-form-select>
{{--                            <b-form-input--}}
{{--                                id="id"--}}
{{--                                v-model="formaddedit.id"--}}
{{--                                type="text"--}}
{{--                                size="sm"--}}
{{--                                placeholder="รหัสโปร"--}}
{{--                                autocomplete="off"--}}
{{--                                required--}}


{{--                            ></b-form-input>--}}
                        @endif
                    </b-form-group>
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col cols="12" md="6">
                    <b-form-group
                        id="input-group-length_type"
                        label="ประเภท:"
                        label-for="length_type"
                        description="">
                        @if(auth()->guard('admin')->user()->superadmin == 'Y')
                            <b-form-select
                                id="length_type"
                                v-model="formaddedit.length_type"
                                :options="option.length_type"
                                size="sm"

                                v-on:change="changeType($event)"
                            ></b-form-select>
                        @else

                            <b-form-select
                                id="length_type"
                                v-model="formaddedit.length_type"
                                :options="option.length_type"
                                size="sm"

                                v-on:change="changeType($event)"

                            ></b-form-select>
                        @endif
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="6">
                    <b-form-group
                        id="input-group-bonus_price"
                        class="hide"
                        label="ยอดจ่าย (บาท):"
                        label-for="bonus_price"
                        description="">
                        <b-form-input
                            id="bonus_price"
                            v-model="formaddedit.bonus_price"
                            type="number"
                            size="sm"
                            placeholder=""
                            autocomplete="off">

                        </b-form-input>
                    </b-form-group>
                    <b-form-group
                        id="input-group-bonus_percent"
                        class="hide"
                        label="ยอดจ่าย (%):"
                        label-for="bonus_percent"
                        description="">
                        <b-form-input
                            id="bonus_percent"
                            v-model="formaddedit.bonus_percent"
                            type="number"
                            size="sm"
                            placeholder=""
                            autocomplete="off"

                        ></b-form-input>
                    </b-form-group>
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-table striped hover small outlined show-empty v-bind:items="myTable" :fields="fields"
                             :busy="isBusy" ref="tbdata" v-if="showtable">
                        <template #table-busy>
                            <div class="text-center text-danger my-2">
                                <b-spinner class="align-middle"></b-spinner>
                                <strong>Loading...</strong>
                            </div>
                        </template>
                        <template #thead-top="data">
                            <b-tr>
                                <b-th colspan="4"></b-th>
                                <b-th variant="secondary" class="text-center">
                                    <button type="button" class="btn btn-xs btn-primary"
                                            @click="addSubModal()"><i class="fa fa-plus"></i> Add
                                    </button>
                                </b-th>

                            </b-tr>
                        </template>
                        <template #cell(action)="data">
                            <span v-html="data.value"></span>
                        </template>
                    </b-table>
                </b-col>
            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                        id="input-group-bonus_min"
                        label="ยอดโบนัสขั้นต่ำ:"
                        label-for="bonus_min"
                        description="โบนัสที่ได้ต้องมากกว่าขั้นต่ำ">
                        <b-form-input
                            id="bonus_min"
                            v-model="formaddedit.bonus_min"
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
                        id="input-group-bonus_max"
                        label="ยอดโบนัสสูงสุด:"
                        label-for="bonus_max"
                        description="โบนัสที่ได้จะไม่มากกว่าสูงสุด">
                        <b-form-input
                            id="bonus_max"
                            v-model="formaddedit.bonus_max"
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
                        id="input-group-turnpro"
                        label="เทรินโปร:"
                        label-for="turnpro"
                        description="">
                        <b-form-input
                            id="turnpro"
                            v-model="formaddedit.turnpro"
                            type="text"
                            no-wheel="true"
                            number="true"
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
                <b-col>
                    <b-form-group
                        id="input-group-withdraw_limit"
                        label="จำกัดยอดถอนได้:"
                        label-for="withdraw_limit"
                        description="หมายถึงตอนถอนออกมา จะได้ยอดที่เซตไว้ตรงนี้">
                        <b-form-input
                            id="withdraw_limit"
                            v-model="formaddedit.withdraw_limit"
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

                    <b-form-checkbox
                        id="use_auto"
                        v-model="formaddedit.use_auto"
                        value="Y"
                        unchecked-value="N">
                        ใช้งาน Auto
                    </b-form-checkbox>

                    <b-form-checkbox
                        id="use_wallet"
                        v-model="formaddedit.use_wallet"
                        value="Y"
                        unchecked-value="N">
                        แสดงผลหน้าเวบ
                    </b-form-checkbox>

                        <b-form-checkbox
                            id="active"
                            v-model="formaddedit.active"
                            value="Y"
                            unchecked-value="N">
                            สถานะทำงาน
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

<b-modal ref="addeditsub" id="addeditsub" centered size="sm" title="เพิ่มรายการ" :no-stacking="false"
         :no-close-on-backdrop="true" :hide-footer="true">
    <b-form @submit.stop.prevent="addEditSubmitNewSub" v-if="showsub">
        <b-form-group
            id="input-group-deposit_amount"
            label="ยอดฝากเริ่่มต้น:"
            label-for="deposit_amount"
            description="">
            <b-form-input
                id="deposit_amount"
                v-model="formsub.deposit_amount"
                type="number"
                size="sm"
                placeholder="จำนวนเงิน"
                min="0"
                max="10000"
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>

        <b-form-group
            id="input-group-deposit_stop"
            label="ยอดฝากไม่เกิน:"
            label-for="deposit_stop"
            description="">
            <b-form-input
                id="deposit_stop"
                v-model="formsub.deposit_stop"
                type="number"
                size="sm"
                placeholder="จำนวนเงิน"
                min="0"
                max="10000"
                autocomplete="off"
                required>

            </b-form-input>
        </b-form-group>

        <b-form-group
            id="input-group-amount"
            label="โบนัสที่ได้รับ:"
            label-for="amount"
            description="">
            <b-form-input
                id="amount"
                v-model="formsub.amount"
                type="number"
                size="sm"
                placeholder="จำนวนเงิน"
                min="1"
                max="10000"
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>

        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-modal>

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/summernote/summernote-lite.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('vendor/summernote/summernote-lite.min.js') }}"></script>

    <script>

        function delSub(id, table) {
            window.app.delSub(id, table);
        }
    </script>
    <script type="module">

        import to from "./js/toPromise.js";

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    showtable: false,
                    showtabletime: false,
                    fields: [],
                    items: [],
                    isBusy: false,
                    show: false,
                    showsub: false,
                    trigger: 0,
                    table: '',
                    methods: '',
                    fileupload: '',
                    formmethod: 'edit',
                    formmethodsub: 'edit',
                    formsub: {
                        deposit_amount: 0,
                        deposit_stop: 0,
                        amount: 0,
                    },
                    formaddedit: {
                        id: '',
                        name_th: '',
                        turnpro: 0,
                        length_type: '',
                        content: '',
                        sort: 0,
                        bonus_min: 0,
                        bonus_max: 0,
                        bonus_price: 0,
                        bonus_percent: 0,
                        withdraw_limit: 0,
                        use_manual: 'N',
                        use_wallet: 'N',
                        use_auto: 'N',
                        active: 'N',
                        enable: 'Y'
                    },
                    option: {
                        length_type: [
                            {text: '== เลือก ==', value: ''},
                            {text: 'จ่ายเป็น บาท', value: 'PRICE'},
                            {text: 'จ่ายเป็น %', value: 'PERCENT'},
                            {text: 'ช่วงเวลา จ่ายเป็น บาท', value: 'TIME'},
                            {text: 'ช่วงเวลา จ่ายเป็น %', value: 'TIMEPC'},
                            {text: 'ช่วงราคาตรงกัน จ่ายเป็น บาท', value: 'AMOUNT'},
                            {text: 'ช่วงราคาตรงกัน จ่ายเป็น %', value: 'AMOUNTPC'},
                            {text: 'ช่วงระหว่างราคา จ่ายเป็น บาท', value: 'BETWEEN'},
                            {text: 'ช่วงระหว่างราคา จ่ายเป็น %', value: 'BETWEENPC'},
                        ],
                        id: [
                            {text: '== เลือก ==', value: ''},
                            {text: 'สมาชิกใหม่ (ใช้ได้)', value: 'pro_newuser'},
                            {text: 'ฝากแรกของวัน (ใช้ได้)', value: 'pro_firstday'},
                            {text: 'โปรทั่วไป (ใช้ได้)', value: 'pro_allbonus'},
                            {text: 'ห้ามเลือก', value: 'pro_faststart'},
                            {text: 'ห้ามเลือก', value: 'pro_cashback'},
                            {text: 'ห้ามเลือก', value: 'pro_ic'},
                        ]
                    },
                    imgpath: '/storage/promotion_img/'
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            methods: {
                changeType(event) {
                    if (event == 'PRICE') {
                        $('#input-group-bonus_price').removeClass('hide');
                        $('#bonus_price').prop('required', true);
                        $('#input-group-bonus_percent').addClass('hide');
                        $('#bonus_percent').prop('required', false);
                        this.showtable = false;
                    } else if (event == 'PERCENT') {
                        $('#input-group-bonus_price').addClass('hide');
                        $('#bonus_price').prop('required', false);
                        $('#input-group-bonus_percent').removeClass('hide');
                        $('#bonus_percent').prop('required', true);
                        this.showtable = false;
                    } else if (event == '') {
                        $('#input-group-bonus_price').addClass('hide');
                        $('#bonus_price').prop('required', false);
                        $('#input-group-bonus_percent').addClass('hide');
                        $('#bonus_percent').prop('required', false);
                        this.showtable = false;
                    } else {
                        $('#input-group-bonus_price').addClass('hide');
                        $('#bonus_price').prop('required', false);
                        $('#input-group-bonus_percent').addClass('hide');
                        $('#bonus_percent').prop('required', false);
                        this.showtable = true;
                    }
                },
                addModal() {
                    this.formaddedit = {
                        id: '',
                        name_th: '',
                        turnpro: 0,
                        length_type: '',
                        content: '',
                        sort: 0,
                        bonus_min: 0,
                        bonus_max: 0,
                        bonus_price: 0,
                        bonus_percent: 0,
                        withdraw_limit: 0,
                        use_manual: 'N',
                        use_wallet: 'N',
                        use_auto: 'N',
                        active: 'N',
                        enable: 'Y'
                    }
                    this.formmethod = 'add';
                    this.fileupload = '';

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();

                    })
                },
                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        id: '',
                        name_th: '',
                        turnpro: 0,
                        length_type: '',
                        content: '',
                        sort: 0,
                        bonus_min: 0,
                        bonus_max: 0,
                        bonus_price: 0,
                        bonus_percent: 0,
                        withdraw_limit: 0,
                        use_manual: 'N',
                        use_wallet: 'N',
                        use_auto: 'N',
                        active: 'N',
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
                addSubModal() {

                    this.formsub = {
                        deposit_amount: 0,
                        deposit_stop: 0,
                        amount: 0
                    }
                    this.formmethodsub = 'add';
                    this.fileupload = '';

                    this.showsub = false;
                    this.$nextTick(() => {
                        this.showsub = true;
                        this.$refs.addeditsub.show();

                    })
                },
                delSub(code, table) {
                    this.$bvModal.msgBoxConfirm('ต้องการดำเนินการ ลบข้อมูลหรือไม่.', {
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
                                this.$http.post("{{ url($menu->currentRoute.'/deletesub') }}", {
                                    id: code, method: table
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
                                        this.$refs.tbdata.refresh();

                                    })
                                    .catch(errors => console.log(errors));
                            }
                        })
                        .catch(errors => console.log(errors));
                },
                async myTable() {
                    const response = await axios.post("{{ url($menu->currentRoute.'/loadpro') }}", {
                        id: this.code,
                        method: this.methods
                    });
                    this.caption = response.data.name;
                    this.fields = [
                        {key: 'no', label: '#', class: 'text-center'},
                        {key: 'deposit_amount', label: 'ฝากเริ่ม (฿)', class: 'text-right'},
                        {key: 'deposit_stop', label: 'ไม่เกิน (฿)', class: 'text-right'},
                        {key: 'amount', label: 'ได้โบนัส (฿)', class: 'text-right'},
                        {key: 'action', label: '', class: 'text-center'},

                    ];


                    return response.data.list;

                },
                async loadData() {

                    const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {id: this.code});

                    this.formaddedit = {
                        id: response.data.data.id,
                        name_th: response.data.data.name_th,
                        turnpro: response.data.data.turnpro,
                        length_type: response.data.data.length_type,
                        content: response.data.data.content,
                        sort: response.data.data.sort,
                        use_manual: response.data.data.use_manual,
                        use_wallet: response.data.data.use_wallet,
                        use_auto: response.data.data.use_auto,
                        active: response.data.data.active,
                        enable: response.data.data.enable,
                        bonus_min: response.data.data.bonus_min,
                        bonus_max: response.data.data.bonus_max,
                        bonus_price: response.data.data.bonus_price,
                        withdraw_limit: response.data.data.withdraw_limit,
                        bonus_percent: response.data.data.bonus_percent,

                    }

                    this.code = response.data.data.code;
                    this.table = response.data.data.table;
                    this.methods = response.data.data.length_type;

                    this.changeType(response.data.data.length_type);

                    if (response.data.data.filepic) {
                        // this.trigger++;
                        this.formaddedit.filepic = response.data.data.filepic;

                    } else {
                        this.formaddedit.filepic = '';
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

                    {{--let url = "{{ route('admin.'.$menu->currentRoute.'.update') }}/" + this.code;--}}


                    let formData = new FormData();
                    const json = JSON.stringify({

                        id: this.formaddedit.id,
                        name_th: this.formaddedit.name_th,
                        turnpro: this.formaddedit.turnpro,
                        bonus_min: this.formaddedit.bonus_min,
                        bonus_max: this.formaddedit.bonus_max,
                        bonus_price: this.formaddedit.bonus_price,
                        bonus_percent: this.formaddedit.bonus_percent,
                        length_type: this.formaddedit.length_type,
                        content: this.formaddedit.content,
                        sort: this.formaddedit.sort,
                        withdraw_limit: this.formaddedit.withdraw_limit,
                        use_manual: this.formaddedit.use_manual,
                        use_wallet: this.formaddedit.use_wallet,
                        use_auto: this.formaddedit.use_auto,
                        active: this.formaddedit.active,
                        enable: this.formaddedit.enable,
                        filepic: this.formaddedit.filepic
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
                addEditSubmitNewSub(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    var url = "{{ url($menu->currentRoute.'/createsub') }}";

                    this.$http.post(url, {id: this.code, data: this.formsub, table: this.table})
                        .then(response => {
                            this.toggleButtonDisable(false);
                            this.$bvModal.hide('addeditsub');
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });

                            this.$refs.tbdata.refresh()

                        })
                        .catch(errors => console.log(errors));

                },
            },
        });

    </script>
@endpush

