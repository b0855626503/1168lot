<b-modal ref="addedit" id="addedit" centered scrollable size="lg" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.prevent="addEditSubmit" v-if="show">
        <b-form-row>
            <b-col>
                <b-form-group
                        id="input-group-1"
                        label="ชื่อกิจกรรม:"
                        label-for="name"
                        description="">
                    <b-form-input
                            id="name"
                            v-model="formaddedit.name"
                            type="text"
                            size="sm"
                            placeholder="ชื่อกิจกรรม"
                            autocomplete="off"
                            required
                    ></b-form-input>
                </b-form-group>
            </b-col>
            <b-col>
                <b-form-group
                        id="input-group-1"
                        label="ประเภทคูปอง:"
                        label-for="cashback"
                        description="เฉพาะเวบที่เปิดเอเจ้นฟรี">
                    <b-form-checkbox
                            id="cashback"
                            v-model="formaddedit.cashback"
                            value="Y"
                            unchecked-value="N">
                        ฟรีเครดิต
                    </b-form-checkbox>
                </b-form-group>

            </b-col>
        </b-form-row>

        <b-form-row>
            <b-col>
                <b-form-group
                        id="input-group-2"
                        label="จำนวน:"
                        label-for="amount"
                        description="ไม่ควรเกิน 300">
                    <b-form-input
                            id="amount"
                            v-model="formaddedit.amount"
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
                        id="input-group-2"
                        label="ยอดเงินที่ได้:"
                        label-for="value"
                        description="">
                    <b-form-input
                            id="value"
                            v-model="formaddedit.value"
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
                        id="input-group-2"
                        label="ค่าเทิน:"
                        label-for="turnpro"
                        description="ไม่ทำงานในระบบ เกม แบบโยกเงิน">
                    <b-form-input
                            id="turnpro"
                            v-model="formaddedit.turnpro"
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
                        id="input-group-2"
                        label="อั้นถอน:"
                        label-for="amount_limit"
                        description="ไม่ทำงานในระบบ เกม แบบโยกเงิน">
                    <b-form-input
                            id="amount_limit"
                            v-model="formaddedit.amount_limit"
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
                        id="input-group-2"
                        label="สมาชิกใหม่:"
                        label-for="newuser"
                        description="เฉพาะผู้ที่ยังไม่เคยรับโปรสมาชิกใหม่">
                    <b-form-checkbox
                            id="newuser"
                            v-model="formaddedit.newuser"
                            value="Y"
                            unchecked-value="N">
                        ไม่เคยรับโปรสมาชิกใหม่
                    </b-form-checkbox>
                </b-form-group>
            </b-col>
            <b-col>
                <b-form-group
                        id="input-group-2"
                        label="ไม่เคยเติมเงิน:"
                        label-for="norefill"
                        description="ต้องไม่เคยเติมเงินมาก่อน">
                    <b-form-checkbox
                            id="norefill"
                            v-model="formaddedit.norefill"
                            value="Y"
                            unchecked-value="N">
                        ไม่เคยเติมเงิน
                    </b-form-checkbox>
                </b-form-group>
            </b-col>
        </b-form-row>
        <b-form-row>
            <b-col>
                <b-form-group
                        id="input-group-2"
                        label="ต้องเคยมียอดเติมเงิน (รวม):"
                        label-for="money"
                        description="">
                    <b-form-input
                            id="money"
                            v-model="formaddedit.money"
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
                        id="input-group-2"
                        label="ใช้รหัสคูปองเดียวกัน:"
                        label-for="same_coupon"
                        description="">
                    <b-form-checkbox
                            id="same_coupon"
                            v-model="formaddedit.same_coupon"
                            value="Y"
                            unchecked-value="N">
                        รหัสคูปองเดียวกัน
                    </b-form-checkbox>
                </b-form-group>
            </b-col>
        </b-form-row>

        <b-form-row>
            <b-col>
                <b-form-group
                        id="input-group-1"
                        label="วันที่เริ่มเติมเงิน (ไม่ถ้ากำหนดคือทั้งหมด):"
                        label-for="refill_start"
                        description="">
                    <b-form-datepicker id="refill_start" name="refill_start" v-model="formaddedit.refill_start"
                                       size="sm" locale="en-US" class="mb-2"
                                       :date-format-options="{ year: 'numeric', month: 'numeric', day: 'numeric' }"></b-form-datepicker>
                </b-form-group>

            </b-col>
            <b-col>
                <b-form-group
                        id="input-group-1"
                        label="วันทีสิ้นสุดเติมเงิน (ไม่ถ้ากำหนดคือทั้งหมด):"
                        label-for="refill_stop"
                        description="">
                    <b-form-datepicker id="refill_stop" name="refill_stop" v-model="formaddedit.refill_stop" size="sm"
                                       locale="en-US" class="mb-2"
                                       :date-format-options="{ year: 'numeric', month: 'numeric', day: 'numeric' }"></b-form-datepicker>
                </b-form-group>

            </b-col>
        </b-form-row>

        <b-form-row>
            <b-col>
                <b-form-group
                        id="input-group-1"
                        label="วันที่เริ่ม:"
                        label-for="date_start"
                        description="จำเป็นต้องระบุ">
                    <b-form-datepicker id="date_start" name="date_start" v-model="formaddedit.date_start" size="sm"
                                       locale="en-US" class="mb-2"
                                       :date-format-options="{ year: 'numeric', month: 'numeric', day: 'numeric' }"></b-form-datepicker>
                </b-form-group>

            </b-col>
            <b-col>
                <b-form-group
                        id="input-group-1"
                        label="วันทีสิ้นสุด:"
                        label-for="date_stop"
                        description="จำเป็นต้องระบุ">
                    <b-form-datepicker id="date_stop" name="date_stop" v-model="formaddedit.date_stop" size="sm"
                                       locale="en-US" class="mb-2"
                                       :date-format-options="{ year: 'numeric', month: 'numeric', day: 'numeric' }"></b-form-datepicker>
                </b-form-group>

            </b-col>
        </b-form-row>

        <b-form-row>
            <b-col>
                <b-form-group
                        id="input-group-2"
                        label="หมดอายุภายใน (วัน) หลังใช้คูปอง:"
                        label-for=''
                        description="">
                    <b-form-input
                            id="date_expire"
                            v-model="formaddedit.date_expire"
                            type="number"
                            size="sm"
                            placeholder=""
                            autocomplete="off"
                            required
                    ></b-form-input>
                </b-form-group>
            </b-col>
            <b-col>
                <b-form-checkbox
                        id="enable"
                        v-model="formaddedit.enable"
                        value="Y"
                        unchecked-value="N">
                    เปิดใช้งาน
                </b-form-checkbox>

            </b-col>
        </b-form-row>


        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-modal>

<b-modal
        ref="gamelog"
        id="gamelog"
        centered
        size="lg"
        :title="caption"
        :no-stacking="false"
        :no-close-on-backdrop="true"
        :ok-only="true"
        :lazy="true"
>
    <!-- FILTER BAR -->
    <div class="mb-2">
        <b-form inline class="flex-wrap">

            <!-- สถานะ -->
            <b-form-group
                    label="สถานะ :"
                    label-size="sm"
                    label-class="mb-0 mr-1"
                    class="mr-2 mb-1"
            >
                <b-form-select
                        v-model="filterStatus"
                        :options="statusOptions"
                        size="sm"
                        @change="onFilterChange"
                ></b-form-select>
            </b-form-group>

            <!-- Member / User -->
            <b-form-group
                    label="สมาชิก :"
                    label-size="sm"
                    label-class="mb-0 mr-1"
                    class="mr-2 mb-1"
            >
                <b-input-group size="sm">
                    <b-form-input
                            v-model="filterMember"
                            placeholder="ค้นหา member / user"
                            @keyup.enter="onFilterChange"
                    ></b-form-input>
                    <b-input-group-append>
                        <b-button size="sm" variant="primary" @click="onFilterChange">
                            ค้นหา
                        </b-button>
                    </b-input-group-append>
                </b-input-group>
            </b-form-group>
            <b-form-group
                    label="ล้างค่า"
                    label-size="sm"
                    label-class="mb-0 mr-1"
                    class="mr-2 mb-1"
            >
                <b-input-group size="sm">
                    <!-- ปุ่มล้าง filter -->
                    <b-button
                            size="sm"
                            variant="outline-secondary"
                            class="mb-1"
                            @click="resetFilter"
                    >
                        ล้างตัวกรอง
                    </b-button>
                </b-input-group>
            </b-form-group>
        </b-form>
    </div>

    <!-- TABLE -->
    <b-table
            striped
            hover
            small
            outlined
            sticky-header
            show-empty
            :items="items"
            :fields="fields"
            :busy="isBusy"
            ref="tbdatalog"
            v-if="show"
    >
        <template #table-busy>
            <div class="text-center text-danger my-2">
                <b-spinner class="align-middle"></b-spinner>
                <strong>Loading...</strong>
            </div>
        </template>

        <template #cell(transfer)="data">
            <span v-html="data.value"></span>
        </template>
        <template #cell(credit_type)="data">
            <span v-html="data.value"></span>
        </template>
        <template #cell(status)="data">
            <span v-html="data.value"></span>
        </template>
        <template #cell(action)="data">
            <span v-html="data.value"></span>
        </template>
        <template #cell(changepass)="data">
            <span v-html="data.value"></span>
        </template>
    </b-table>

    <!-- PAGINATION -->
    <div class="mt-2" v-if="totalRows > 0">
        <b-pagination
                v-model="page"
                :total-rows="totalRows"
                :per-page="perPage"
                align="center"
                size="sm"
                @change="onPageChange"
        />
    </div>
</b-modal>




@push('styles')
    {{--    <link rel="stylesheet" href="{{ asset('vendor/summernote/summernote-lite.css') }}">--}}
@endpush
@push('scripts')
    <script>
        function genModal(id) {
            window.app.genModal(id);
        }

        function listModal(id) {
            window.app.listModal(id);
        }

        function ViewModal(id) {
            window.app.ViewModal(id);
        }
    </script>
    {{--    <script src="{{ asset('vendor/summernote/summernote-lite.min.js') }}"></script>--}}
    <script type="module">

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: false,
                    fields: [],
                    items: [],
                    caption: null,
                    isBusy: false,
                    page: 1,
                    perPage: 20,
                    totalRows: 0,
                    filterStatus: 'all',
                    filterMember: '',
                    statusOptions: [
                        {value: 'all', text: 'ทุกสถานะ'},
                        {value: 'unused', text: 'ยังไม่ใช้งาน'},
                        {value: 'used', text: 'ใช้งานแล้ว'},
                    ],

                    formmethod: 'add',
                    formaddedit: {
                        name: '',
                        cashback: 'N',
                        amount: 1,
                        value: 0,
                        turnpro: 0,
                        amount_limit: 0,
                        date_start: '',
                        date_stop: '',
                        money: 0,
                        refill_start: '',
                        refill_stop: '',
                        date_expire: 0,
                        newuser: 'N',
                        norefill: 'N',
                        same_coupon: 'N',
                        enable: 'Y'
                    }
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            methods: {
                genModal(code) {
                    this.$bvModal.msgBoxConfirm('ต้องการ GEN รายการคูปองตามค่าที่ตั้งไว้หรือไม่ เมื่อยืนยันการ GEN คูปอง จะไม่สามารถแก้ไขข้อมูลได้อีก.', {
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
                                this.$http.post("{{ url($menu->currentRoute.'/gen') }}", {
                                    id: code
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
                                        // this.$refs.gamelog.refresh()
                                        window.LaravelDataTables["dataTableBuilder"].draw(false);
                                    })
                                    .catch(exception => {
                                        console.log('error');
                                    });
                            }
                        })
                        .catch(err => {
                            // An error occurred
                        })
                },
                listModal(code) {
                    this.code = code;

                    // reset pagination & filter เมื่อเปิดใหม่
                    this.page = 1;
                    this.filterStatus = 'all';
                    this.filterMember = '';

                    this.show = false;

                    this.$nextTick(() => {
                        this.show = true;
                        this.myLog(); // ดึงข้อมูลหน้าแรก
                        this.$refs.gamelog.show();
                    });
                },

                // โหลด log จาก backend (พร้อม pagination + filter)
                async myLog() {
                    this.isBusy = true;

                    try {
                        const response = await axios.get("{{ url($menu->currentRoute.'/couponlist') }}", {
                            params: {
                                id: this.code,
                                page: this.page,
                                per_page: this.perPage,
                                status: this.filterStatus,
                                member: this.filterMember,
                            },
                        });

                        this.caption = response.data.name;

                        this.fields = [
                            {key: 'code', label: 'รหัสคูปอง'},
                            {key: 'status', label: 'สถานะใช้งาน'},
                            {key: 'member_code', label: 'ใช้โดย'},
                            {key: 'date', label: 'วันที่ใช้'},
                        ];

                        this.items = response.data.list || [];

                        const pagination = response.data.pagination || {};
                        this.totalRows = pagination.total || this.items.length;
                        this.perPage = pagination.per_page || this.perPage;
                        this.page = pagination.current || this.page;
                    } catch (e) {
                        console.error('Load coupon log failed', e);
                        // จะ Toastify แจ้ง error ตรงนี้ก็ได้
                    } finally {
                        this.isBusy = false;
                    }
                },

                // เวลาเปลี่ยนหน้า
                onPageChange(page) {
                    this.page = page;
                    this.myLog();
                },

                // เวลาเปลี่ยน filter (select / กดปุ่มค้นหา / enter)
                onFilterChange() {
                    this.page = 1; // filter ใหม่ → reset กลับหน้าแรก
                    this.myLog();
                },

                // ล้าง filter ทั้งหมด
                resetFilter() {
                    this.filterStatus = 'all';
                    this.filterMember = '';
                    this.page = 1;
                    this.myLog();
                },
                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        name: '',
                        cashback: 'N',
                        amount: 1,
                        value: 0,
                        turnpro: 0,
                        amount_limit: 0,
                        date_start: '',
                        date_stop: '',
                        money: 0,
                        refill_start: '',
                        refill_stop: '',
                        date_expire: 0,
                        newuser: 'N',
                        norefill: 'N',
                        same_coupon: 'N',
                        enable: 'Y',
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
                ViewModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        name: '',
                        cashback: 'N',
                        amount: 1,
                        value: 0,
                        turnpro: 0,
                        amount_limit: 0,
                        date_start: '',
                        date_stop: '',
                        money: 0,
                        refill_start: '',
                        refill_stop: '',
                        date_expire: 0,
                        newuser: 'N',
                        norefill: 'N',
                        same_coupon: 'N',
                        enable: 'Y',
                    }

                    this.formmethod = 'view';

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
                        name: '',
                        cashback: 'N',
                        amount: 1,
                        value: 0,
                        turnpro: 0,
                        amount_limit: 0,
                        date_start: '',
                        date_stop: '',
                        money: 0,
                        refill_start: '',
                        refill_stop: '',
                        date_expire: 0,
                        newuser: 'N',
                        norefill: 'N',
                        same_coupon: 'N',
                        enable: 'Y',
                    }
                    this.formmethod = 'add';

                    this.show = false;
                    this.$nextTick(() => {
                        this.$refs.addedit.show();
                        this.show = true;

                    })
                },
                async loadData() {
                    const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loaddata') }}", {id: this.code});
                    this.formaddedit = {
                        name: response.data.data.name,
                        cashback: response.data.data.cashback,
                        amount: response.data.data.amount,
                        value: response.data.data.value,
                        turnpro: response.data.data.turnpro,
                        amount_limit: response.data.data.amount_limit,
                        date_start: response.data.data.date_start,
                        date_stop: response.data.data.date_stop,
                        money: response.data.data.money,
                        refill_start: response.data.data.refill_start,
                        refill_stop: response.data.data.refill_stop,
                        date_expire: response.data.data.date_expire,
                        same_coupon: response.data.data.same_coupon,
                        newuser: response.data.data.newuser,
                        norefill: response.data.data.norefill,
                        enable: response.data.data.enable,
                    }
                },
                addEditSubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    if (this.formmethod === 'add') {
                        var url = "{{ route('admin.'.$menu->currentRoute.'.create') }}";
                    } else if (this.formmethod === 'edit') {
                        var url = "{{ route('admin.'.$menu->currentRoute.'.update') }}";
                    }
                    this.$http.post(url, {id: this.code, data: this.formaddedit})
                        .then(response => {
                            this.$refs.addedit.hide();

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

