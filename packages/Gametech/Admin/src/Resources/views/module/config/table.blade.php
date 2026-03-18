@push('styles')
    <style>
        .card-title {
            margin-bottom: .75rem !important;
        }
    </style>
@endpush

<config :formaddedit="{{ json_encode($configs) }}"></config>

@push('scripts')
    <script type="text/x-template" id="config-template">

        <b-container class="bv-example-row" v-show="show=true">
            <b-form @submit.stop.prevent="addEditSubmitNew" id="frmaddedit">

                <b-form-row>
                    <b-col>
                        <b-card title="แก้ไขข้อมูลติดต่อ">
                            <b-card-text>
                                <b-form-group
                                    id="input-group-lineid"
                                    label="ID Line :"
                                    label-for="lineid"
                                    description="">
                                    <b-form-input
                                        id="lineid"
                                        name="lineid"
                                        v-model="formaddedit.lineid"
                                        type="text"
                                        size="sm"
                                        placeholder="ID Line"
                                        autocomplete="off"
                                        required

                                    ></b-form-input>
                                </b-form-group>
                                <b-form-group
                                    id="input-group-linelink"
                                    label="Link Line:"
                                    label-for="linelink"
                                    description="">
                                    <b-form-input
                                        id="linelink"
                                        name="linelink"
                                        v-model="formaddedit.linelink"
                                        type="text"
                                        size="sm"
                                        placeholder="Link Line"
                                        autocomplete="off"
                                        required
                                    ></b-form-input>
                                </b-form-group>
                                <b-form-group
                                    id="input-group-notice"
                                    label="ข้อความแจ้งเตือน:"
                                    label-for="notice"
                                    description="">
                                    <b-form-input
                                        id="notice"
                                        name="notice"
                                        v-model="formaddedit.notice"
                                        type="text"
                                        size="sm"
                                        placeholder=""
                                        autocomplete="off"

                                    ></b-form-input>
                                </b-form-group>
                            </b-card-text>
                        </b-card>
                    </b-col>
                </b-form-row>

                <b-form-row>
                    <b-col>
                        <b-card title="ข้อมูลการถอนเงิน">
                            <b-card-text>
                                <b-form-group
                                    id="input-group-minwithdraw"
                                    label="ขั้นต่ำ ถอนเงิน :"
                                    label-for="minwithdraw"
                                    description="">
                                    <b-form-input
                                        id="minwithdraw"
                                        name="minwithdraw"
                                        v-model="formaddedit.minwithdraw"
                                        type="number"
                                        size="sm"
                                        placeholder=""
                                        autocomplete="off"
                                    ></b-form-input>
                                </b-form-group>
                                <b-form-group
                                    id="input-group-maxwithdraw_day"
                                    label="วงเงินถอน / วัน:"
                                    label-for="maxwithdraw_day"
                                    description="">
                                    <b-form-input
                                        id="maxwithdraw_day"
                                        name="maxwithdraw_day"
                                        v-model="formaddedit.maxwithdraw_day"
                                        type="number"
                                        size="sm"
                                        placeholder=""
                                        autocomplete="off"
                                        required
                                    ></b-form-input>
                                </b-form-group>
                            </b-card-text>
                        </b-card>
                    </b-col>
                </b-form-row>

                <b-form-row>
                    <b-col>
                        <b-card title="ข้อมูลการโยกเงิน">
                            <b-card-text>
                                <b-form-group
                                    id="input-group-maxtransfer_time"
                                    label="สูงสุด โยก Wallet เข้า Game :"
                                    label-for="maxtransfer_time"
                                    description="">
                                    <b-form-input
                                        id="maxtransfer_time"
                                        name="maxtransfer_time"
                                        v-model="formaddedit.maxtransfer_time"
                                        type="number"
                                        size="sm"
                                        placeholder=""
                                        autocomplete="off"
                                    ></b-form-input>
                                </b-form-group>
                                <b-form-group
                                    id="input-group-mintransfer"
                                    label="ขั้นต่ำ โยก Wallet เข้า Game:"
                                    label-for="mintransfer"
                                    description="">
                                    <b-form-input
                                        id="mintransfer"
                                        name="mintransfer"
                                        v-model="formaddedit.mintransfer"
                                        type="number"
                                        size="sm"
                                        placeholder=""
                                        autocomplete="off"
                                        required
                                    ></b-form-input>
                                </b-form-group>
                                <b-form-group
                                    id="input-group-mintransferback"
                                    label="ขั้นต่ำ โยก Game เข้า Wallet:"
                                    label-for="mintransferback"
                                    description="">
                                    <b-form-input
                                        id="mintransferback"
                                        name="mintransferback"
                                        v-model="formaddedit.mintransferback"
                                        type="number"
                                        size="sm"
                                        placeholder=""
                                        autocomplete="off"
                                        required
                                    ></b-form-input>
                                </b-form-group>
                            </b-card-text>
                        </b-card>
                    </b-col>
                </b-form-row>

                <b-form-row>
                    <b-col>
                        <b-card title="Promotion ON/OFF">
                            <b-card-text>
                                <b-form-group
                                    id="input-group-pro_onoff"
                                    label="Promotion:"
                                    label-for="pro_onoff"
                                    description="">

                                    <b-form-select
                                        id="pro_onoff"
                                        name="pro_onoff"
                                        v-model="formaddedit.pro_onoff"
                                        :options="option.pro_onoff"
                                        size="sm"
                                        required
                                    ></b-form-select>
                                </b-form-group>
                            </b-card-text>
                        </b-card>
                    </b-col>
                </b-form-row>

                <b-form-row>
                    <b-col>
                        <b-card title="Special Config">
                            <b-card-text>
                                <b-form-group
                                    id="input-group-point_open"
                                    label="เปิดใช้งาน Point:"
                                    label-for="point_open"
                                    description="">

                                    <b-form-select
                                        id="point_open"
                                        name="point_open"
                                        v-model="formaddedit.point_open"
                                        :options="option.point_open"
                                        size="sm"
                                        required
                                    ></b-form-select>
                                </b-form-group>
                                <b-form-group
                                    id="input-group-points"
                                    label="เติมทุก xxx / 1 Point :"
                                    label-for="points"
                                    description="">
                                    <b-form-input
                                        id="points"
                                        name="points"
                                        v-model="formaddedit.points"
                                        type="number"
                                        size="sm"
                                        placeholder=""
                                        autocomplete="off"
                                    ></b-form-input>
                                </b-form-group>
                                <b-form-group
                                    id="input-group-diamond_open"
                                    label="เปิดใช้งาน Diamond:"
                                    label-for="diamond_open"
                                    description="">

                                    <b-form-select
                                        id="diamond_open"
                                        name="diamond_open"
                                        v-model="formaddedit.diamond_open"
                                        :options="option.diamond_open"
                                        size="sm"
                                        required
                                    ></b-form-select>

                                </b-form-group>
                                <b-form-group
                                    id="input-group-diamonds"
                                    label="เติมทุก xxx / 1 Diamond :"
                                    label-for="diamonds"
                                    description="">
                                    <b-form-input
                                        id="diamonds"
                                        name="diamonds"
                                        v-model="formaddedit.diamonds"
                                        type="number"
                                        size="sm"
                                        placeholder=""
                                        autocomplete="off"
                                    ></b-form-input>
                                </b-form-group>
                            </b-card-text>
                        </b-card>
                    </b-col>
                </b-form-row>

                <b-form-row>
                    <b-col>
                        <b-card title="Free Credit">
                            <b-card-text>
                                <b-form-group
                                    id="input-group-free_mintransfer"
                                    label="ขั้นต่่ำโยก Credit เข้า Game :"
                                    label-for="free_mintransfer"
                                    description="">
                                    <b-form-input
                                        id="free_mintransfer"
                                        name="free_mintransfer"
                                        v-model="formaddedit.free_mintransfer"
                                        type="number"
                                        size="sm"
                                        placeholder=""
                                        autocomplete="off"
                                    ></b-form-input>
                                </b-form-group>
                                <b-form-group
                                    id="input-group-free_maxtransfer"
                                    label="สูงสุดในการโยกเข้า / ครั้ง:"
                                    label-for="free_maxtransfer"
                                    description="">
                                    <b-form-input
                                        id="free_maxtransfer"
                                        name="free_maxtransfer"
                                        v-model="formaddedit.free_maxtransfer"
                                        type="number"
                                        size="sm"
                                        placeholder=""
                                        autocomplete="off"
                                        required
                                    ></b-form-input>
                                </b-form-group>
                                <b-form-group
                                    id="input-group-free_maxout"
                                    label="สูงสุดการโยกออก / ครั้ง:"
                                    label-for="free_maxout"
                                    description="">
                                    <b-form-input
                                        id="free_maxout"
                                        name="free_maxout"
                                        v-model="formaddedit.free_maxout"
                                        type="number"
                                        size="sm"
                                        placeholder=""
                                        autocomplete="off"
                                        required
                                    ></b-form-input>
                                </b-form-group>
                                <b-form-group
                                    id="input-group-free_minwithdraw"
                                    label="ขั้นต่ำ ถอนเงิน / ครั้ง:"
                                    label-for="free_minwithdraw"
                                    description="">
                                    <b-form-input
                                        id="free_minwithdraw"
                                        name="free_minwithdraw"
                                        v-model="formaddedit.free_minwithdraw"
                                        type="number"
                                        size="sm"
                                        placeholder=""
                                        autocomplete="off"
                                        required
                                    ></b-form-input>
                                </b-form-group>
                                <b-form-group
                                    id="input-group-free_maxwithdraw"
                                    label="จำกัด ถอนเงินทั้งหมด:"
                                    label-for="free_maxwithdraw"
                                    description="">
                                    <b-form-input
                                        id="free_maxwithdraw"
                                        name="free_maxwithdraw"
                                        v-model="formaddedit.free_maxwithdraw"
                                        type="number"
                                        size="sm"
                                        placeholder=""
                                        autocomplete="off"
                                        required
                                    ></b-form-input>
                                </b-form-group>
                            </b-card-text>
                        </b-card>
                    </b-col>
                </b-form-row>

                <b-button type="submit" variant="primary">บันทึก</b-button>

            </b-form>
        </b-container>
    </script>

    <script type="text/javascript">
        (() => {

            Vue.component('config', {
                'template': '#config-template',
                props: {
                    formaddedit: {},

                },
                data() {
                    return {
                        code: 1,
                        option: {
                            pro_onoff: [{value: 'Y', text: 'เปิด'}, {value: 'N', text: 'ปิด'}],
                            point_open: [{value: 'Y', text: 'เปิด'}, {value: 'N', text: 'ปิด'}],
                            diamond_open: [{value: 'Y', text: 'เปิด'}, {value: 'N', text: 'ปิด'}]
                        }
                    };
                },

                mounted() {
                    this.code = 1;
                    console.log(this.items);

                },
                methods: {
                    addEditSubmitNew(event) {
                        event.preventDefault();
                        this.toggleButtonDisable(true);
                        var url = "{{ url('admin/'.$menu->currentRoute.'/update') }}/" + this.code;

                        let form = $('#frmaddedit')[0];
                        let formData = new FormData(form);
                        // const json = JSON.stringify({
                        //     firstname: this.formaddedit.firstname,
                        //     lastname: this.formaddedit.lastname,
                        //     bank_code: this.formaddedit.bank_code,
                        //     user_pass: this.formaddedit.user_pass,
                        //     acc_no: this.formaddedit.acc_no,
                        // });

                        // formData.append('data', json);

                        // formData.append('fileupload', this.fileupload);

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
                            .catch(errors => {
                                Toast.fire({
                                    icon: 'error',
                                    title: errors.response.data
                                })
                            });

                    }
                }

            })

            {{--window.app = new Vue({--}}
            {{--    el: '#app',--}}

            {{--    // data() {--}}
            {{--    //     return {--}}
            {{--    //         show: true,--}}
            {{--    //         formmethod: 'edit',--}}
            {{--    //--}}
            {{--    //         // formaddedit: {--}}
            {{--    //         //     lineid: '22',--}}
            {{--    //         //     linelink: '',--}}
            {{--    //         //     minwithdraw: '',--}}
            {{--    //         //     maxwithdraw_day: '',--}}
            {{--    //         //     maxtransfer_time: '',--}}
            {{--    //         //     mintransfer: '',--}}
            {{--    //         //     mintransferback: '',--}}
            {{--    //         //     maxsetcredit: '',--}}
            {{--    //         //     free_mintransfer: '',--}}
            {{--    //         //     free_maxtransfer: '',--}}
            {{--    //         //     free_maxout: '',--}}
            {{--    //         //     free_minwithdraw: '',--}}
            {{--    //         //     free_maxwithdraw: '',--}}
            {{--    //         //     maxspin: '',--}}
            {{--    //         //     pro_onoff: '',--}}
            {{--    //         //     notice: '',--}}
            {{--    //         //     mintransfer_pro: '',--}}
            {{--    //         //     pro_all: '',--}}
            {{--    //         //     point_open: '',--}}
            {{--    //         //     diamond_open: '',--}}
            {{--    //         //     points: '',--}}
            {{--    //         //     diamonds: '',--}}
            {{--    //         // },--}}
            {{--    //         option : {--}}
            {{--    //             pro_onoff : [{ value: 'Y', text: 'เปิด' } , { value: 'N', text: 'ปิด' }]--}}
            {{--    //         }--}}
            {{--    //     };--}}
            {{--    // },--}}
            {{--    mounted() {--}}
            {{--       --}}{{-- console.log(this.items.lineid)--}}
            {{--       --}}{{--// this.editModal(1);--}}
            {{--       --}}{{-- this.formaddedit.lineid = @json($configs->lineid);--}}
            {{--    },--}}
            {{--    methods: {--}}
            {{--        editModal(code) {--}}
            {{--            this.code = null;--}}
            {{--            this.formaddedit.name = '';--}}
            {{--            this.formmethod = 'edit';--}}

            {{--            this.show = false;--}}
            {{--            this.$nextTick(() => {--}}
            {{--                this.show = true;--}}
            {{--                this.code = code;--}}

            {{--                // this.loadData();--}}

            {{--                // this.$refs.addedit.show();--}}

            {{--            })--}}
            {{--        },--}}
            {{--        addModal() {--}}
            {{--            this.code = null;--}}
            {{--            this.formaddedit.name = '';--}}
            {{--            this.formmethod = 'add';--}}

            {{--            this.show = false;--}}
            {{--            this.$nextTick(() => {--}}
            {{--                this.show = true;--}}
            {{--                this.$refs.addedit.show();--}}

            {{--            })--}}
            {{--        },--}}
            {{--        async loadData() {--}}
            {{--            const response = await  axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {id: this.code});--}}
            {{--            this.formaddedit = {--}}
            {{--                lineid: response.data.lineid,--}}
            {{--                linelink: response.data.linelink,--}}
            {{--                // minwithdraw: response.data.data.minwithdraw,--}}
            {{--                // maxwithdraw_day: response.data.data.maxwithdraw_day,--}}
            {{--                // maxtransfer_time: response.data.data.maxtransfer_time,--}}
            {{--                // mintransfer: response.data.data.mintransfer,--}}
            {{--                // mintransferback: response.data.data.mintransferback,--}}
            {{--                // maxsetcredit: response.data.data.maxsetcredit,--}}
            {{--                // free_mintransfer: response.data.data.free_mintransfer,--}}
            {{--                // free_maxtransfer: response.data.data.free_maxtransfer,--}}
            {{--                // free_maxout: response.data.data.free_maxout,--}}
            {{--                // free_minwithdraw: response.data.data.free_minwithdraw,--}}
            {{--                // free_maxwithdraw: response.data.data.free_maxwithdraw,--}}
            {{--                // maxspin: response.data.data.maxspin,--}}
            {{--                // pro_onoff: response.data.data.pro_onoff,--}}
            {{--                // notice: response.data.data.notice,--}}
            {{--                // mintransfer_pro: response.data.data.mintransfer_pro,--}}
            {{--                // pro_all: '',--}}
            {{--                // point_open: '',--}}
            {{--                // diamond_open: '',--}}
            {{--                // points: '',--}}
            {{--                // diamonds: '',--}}
            {{--            }--}}

            {{--        },--}}
            {{--    },--}}
            {{--});--}}
        })()
    </script>
@endpush
