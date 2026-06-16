<b-modal ref="addedit" id="addedit" centered scrollable size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit" class="dropzone">
            <input type="hidden" id="upline_code" :value="formaddedit.upline_code">
            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-af"
                            label="เบอร์ ผู้แนะนำ:"
                            label-for="af"
                            description="ระบุ เบอร์ ผู้แนะนำ">
                        <b-form-input
                                id="af"
                                v-model="formaddedit.af"
                                type="text"
                                size="sm"
                                placeholder="เบอร์ ผู้แนะนำ"
                                autocomplete="off"
                                v-on:input="changeType($event)"
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                            id="input-group-up_name"
                            label="ชื่อผู้แนะนำ:"
                            label-for="up_name"
                            description="">
                        <b-form-input
                                id="up_name"
                                v-model="formaddedit.up_name"
                                type="text"
                                size="sm"
                                placeholder=""
                                autocomplete="off"
                                readonly
                        ></b-form-input>
                    </b-form-group>
                </b-col>

            </b-form-row>
            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-firstname"
                            label="ชื่อ:"
                            label-for="firstname"
                            description="ระบุ ชื่อ">
                        <b-form-input
                                id="firstname"
                                v-model="formaddedit.firstname"
                                type="text"
                                size="sm"
                                placeholder="ชื่อ"
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                            id="input-group-lastname"
                            label="นามสกุล:"
                            label-for="lastname"
                            description="ระบุ นามสกุล">
                        <b-form-input
                                id="lastname"
                                v-model="formaddedit.lastname"
                                type="text"
                                size="sm"
                                placeholder="นามสกุล"
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>

            </b-form-row>
            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-firstname_addon"
                            label="ชื่อ (ภาษาอื่น):"
                            label-for="firstname_addon"
                            description="ระบุ ชื่อ (other)">
                        <b-form-input
                                id="firstname_addon"
                                v-model="formaddedit.firstname_addon"
                                type="text"
                                size="sm"
                                placeholder="ชื่อ"
                                autocomplete="off"

                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                            id="input-group-lastname_addon"
                            label="นามสกุล (ภาษาอื่น):"
                            label-for="lastname_addon"
                            description="ระบุ นามสกุล">
                        <b-form-input
                                id="lastname_addon"
                                v-model="formaddedit.lastname_addon"
                                type="text"
                                size="sm"
                                placeholder="นามสกุล"
                                autocomplete="off"

                        ></b-form-input>
                    </b-form-group>
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-user_name"
                            label="User ID:"
                            label-for="user_name"
                            description="เปลี่ยนไม่ได้">
                        <b-form-input
                                id="user_name"
                                v-model="formaddedit.user_name"
                                type="text"
                                size="sm"
                                placeholder=""
                                autocomplete="off"
                                plaintext
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                            id="input-group-user_pass"
                            label="รหัสผ่าน:"
                            label-for="user_pass"
                            description="ระบุ รหัสผ่าน กรณีต้องการเปลี่ยนเท่านั้น">
                        <b-form-input
                                id="user_pass"
                                v-model="formaddedit.user_pass"
                                type="text"
                                size="sm"
                                placeholder="รหัสผ่าน"
                                autocomplete="off"

                        ></b-form-input>
                    </b-form-group>
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-bank_code"
                            label="ธนาคาร:"
                            label-for="bank_code"
                            description="">

                        <b-form-select
                                id="bank_code"
                                v-model="formaddedit.bank_code"
                                :options="option.bank_code"
                                size="sm"
                                required
                        ></b-form-select>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                            id="input-group-acc_no"
                            label="เลขที่บัญชี:"
                            label-for="acc_no"
                            description="ระบบไม่ได้เชคซ้ำให้นะ">
                        <b-form-input
                                id="acc_no"
                                v-model="formaddedit.acc_no"
                                type="text"
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
                            id="input-group-lineid"
                            label="Line ID:"
                            label-for="lineid"
                            description="">

                        <b-form-input
                                id="lineid"
                                v-model="formaddedit.lineid"
                                type="text"
                                size="sm"
                                placeholder=""
                                autocomplete="off"
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                            id="input-group-tel"
                            label="เบอร์โทร:"
                            label-for="tel"
                            description="ระบบไม่ได้เชคซ้ำให้นะ">

                        <b-form-input
                                id="tel"
                                v-model="formaddedit.tel"
                                type="text"
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
                            id="input-group-maxwithdraw_day"
                            label="ยอดถอนสูงสุด / วัน:"
                            label-for="maxwithdraw_day"
                            description="ถ้าค่าเป็น 0 = ใช้ค่าถอนสูงสุด / วัน จาก ตั้งค่าพื้นฐานเวบไซต์">

                        <b-form-input
                                id="maxwithdraw_day"
                                v-model="formaddedit.maxwithdraw_day"
                                type="number"
                                size="sm"
                                placeholder=""
                                autocomplete="off"
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                            id="input-group-refer"
                            label="Refer:"
                            label-for="refer_code"
                            description="">

                        <b-form-select
                                id="refer_code"
                                v-model="formaddedit.refer_code"
                                :options="option.refer_code"
                                size="sm"
                                required
                        ></b-form-select>
                    </b-form-group>
                </b-col>
            </b-form-row>

            <b-form-row>
                <b-col>
                </b-col>
                <b-col>
                    <div class="d-flex align-items-center">
                        <img v-if="currentPic && currentPic.url" :src="currentPic.url" style="max-width:120px">
                        <small v-else class="text-muted">ยังไม่มีรูป</small>
                        <b-button class="ml-3" variant="primary" @click="openUpload">อัปโหลด/เปลี่ยนรูป</b-button>


                    </div>
                    <small class="text-muted d-block mt-1">
                        ปุ่มนี้ใช้ได้เมื่อเป็นโหมดแก้ไข (มี code แล้ว)
                    </small>
                </b-col>
            </b-form-row>

            <b-button type="submit" variant="primary">บันทึก</b-button>

        </b-form>
    </b-container>

</b-modal>

<!-- =========================
     ✅ Modal: Dropzone Upload
========================= -->
<b-modal ref="dzModal" title="อัปโหลดรูป" @shown="onDzShown" @hidden="onDzHidden" hide-footer>
    <div ref="dz" class="dropzone border rounded p-4 text-center">
        <div class="dz-message">ลากไฟล์มาวาง หรือคลิกเพื่อเลือกไฟล์</div>
        <div ref="dzPreviews" class="mt-3"></div>
    </div>
    <div class="text-center mt-2">
        <b-button ref="pickBtn" size="sm" variant="secondary">เลือกไฟล์</b-button>
    </div>
</b-modal>

<!-- =========================
     ✅ Modal: คืนยอดกิจกรรม
========================= -->
<b-modal ref="eventRefund" id="eventRefund" centered size="sm" title="คืนยอดกิจกรรม"
         :no-stacking="true" :no-close-on-backdrop="true" :hide-footer="true" :lazy="true">
    <b-form @submit.prevent.once="eventRefundSubmit" v-if="show">
        <b-form-group
                id="input-group-event-code"
                label="กิจกรรม:"
                label-for="event_code"
                description="เลือกกิจกรรมที่ต้องการคืนยอด">
            <b-form-select
                    id="event_code"
                    v-model="formevent.event_code"
                    :options="eventOptions"
                    size="sm"
                    required
            ></b-form-select>
        </b-form-group>

        <b-form-group
                id="input-group-event-amount"
                label="จำนวนเงินที่คืน:"
                label-for="event_amount"
                description="ระบุจำนวนเงิน (ตัวเลขเท่านั้น)">
            <b-form-input
                    id="event_amount"
                    v-model.number="formevent.amount"
                    type="number"
                    size="sm"
                    min="1"
                    step="0.01"
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        <b-form-group
                id="input-group-event-remark"
                label="หมายเหตุ:"
                label-for="event_remark"
                description="ระบุสาเหตุ/รายละเอียดการคืนยอด">
            <b-form-input
                    id="event_remark"
                    v-model="formevent.remark_admin"
                    type="text"
                    size="sm"
                    placeholder="โปรดระบุ"
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        <b-button type="submit" variant="primary" id="btnEventRefundSubmit">บันทึก</b-button>
    </b-form>
</b-modal>




<b-modal ref="gamelog" id="gamelog" centered size="lg" :title="caption" :no-stacking="false"
         :no-close-on-backdrop="true"
         :ok-only="true" :lazy="true">
    <b-table striped hover small outlined sticky-header show-empty v-bind:items="items" :fields="fields" :busy="isBusy"
             ref="tbdatalog" v-if="show">
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
</b-modal>

<b-modal ref="refill" id="refill" centered size="sm" title="ทำรายการฝากเงิน" :no-stacking="true"
         :no-close-on-backdrop="true" :hide-footer="true">
    <b-form @submit.prevent="refillSubmit" v-if="show">
        <b-form-group
                id="input-group-1"
                label="จำนวนเงิน:"
                label-for="amount"
                description="ระบุจำนวนเงิน ต่ำสุด 1">
            <b-form-input
                    id="amount"
                    v-model="formrefill.amount"
                    type="number"
                    size="sm"
                    placeholder="จำนวนเงิน"
                    min="1"
                    step="00.01"
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        <b-form-group id="input-group-2" label="ช่องทางที่ฝาก:" label-for="account_code">
            <b-form-select
                    id="account_code"
                    v-model="formrefill.account_code"
                    :options="banks"
                    size="sm"
                    required
            ></b-form-select>
        </b-form-group>

        <b-form-group
                id="input-group-bank-time"
                label="วันและเวลาโอน (bank_time):"
                label-for="bank_time"
                description="ระบุวันและเวลาที่ลูกค้าโอนเงินจริง">
            <b-form-input
                    id="bank_time"
                    v-model="formrefill.bank_time"
                    type="datetime-local"
                    size="sm"
                    required
            ></b-form-input>
        </b-form-group>

        <b-form-group
                id="input-group-3"
                label="หมายเหตุ:"
                label-for="remark_admin"
                description="ระบุสาเหตุที่ทำรายการ">
            <b-form-input
                    id="remark_admin"
                    v-model="formrefill.remark_admin"
                    type="text"
                    placeholder="โปรดระบุ"
                    size="sm"
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        <b-button type="submit" variant="primary" id="btnsubmit">บันทึก</b-button>

    </b-form>
</b-modal>

<b-modal ref="money" id="money" centered size="sm" title="เพิ่ม - ลด ยอดเงิน" :no-stacking="true"
         :no-close-on-backdrop="true" :hide-footer="true">
    <b-form @submit.prevent.once="moneySubmit" v-if="show">

        <b-form-group id="input-group-2" label="ประเภทรายการ:" label-for="type">
            <b-form-select
                    id="account_code"
                    v-model="formmoney.type"
                    :options="typesmoney"
                    size="sm"
                    required
            ></b-form-select>
        </b-form-group>

        <b-form-group
                id="input-group-1"
                label="จำนวนเงิน:"
                label-for="amount"
                description="ระบุจำนวนเงิน ต่ำสุดคือ 1">
            <b-form-input
                    id="amount"
                    v-model="formmoney.amount"
                    type="number"
                    size="sm"
                    placeholder="จำนวนเงิน"
                    min="1"
                    step="00.01"
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        <b-form-group
                id="input-group-3"
                label="หมายเหตุ:"
                label-for="remark"
                description="ระบุสาเหตุที่ทำรายการ">
            <b-form-input
                    id="remark"
                    v-model="formmoney.remark"
                    type="text"
                    placeholder="โปรดระบุ"
                    size="sm"
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-modal>

<b-modal ref="point" id="point" centered size="sm" title="เพิ่ม - ลด Point" :no-stacking="true"
         :no-close-on-backdrop="true" :hide-footer="true">
    <b-form @submit.prevent.once="pointSubmit" v-if="show">

        <b-form-group id="input-group-2" label="ประเภทรายการ:" label-for="type">
            <b-form-select
                    id="account_code"
                    v-model="formpoint.type"
                    :options="typespoint"
                    size="sm"
                    required
            ></b-form-select>
        </b-form-group>

        <b-form-group
                id="input-group-1"
                label="จำนวน:"
                label-for="amount"
                description="ระบุจำนวน ระหว่าง 0 - 10,000">
            <b-form-input
                    id="amount"
                    v-model="formpoint.amount"
                    type="number"
                    size="sm"
                    placeholder="จำนวนเงิน"
                    min="1"
                    max="10000"
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        <b-form-group
                id="input-group-3"
                label="หมายเหตุ:"
                label-for="remark"
                description="ระบุสาเหตุที่ทำรายการ">
            <b-form-input
                    id="remark"
                    v-model="formpoint.remark"
                    type="text"
                    placeholder="โปรดระบุ"
                    size="sm"
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-modal>

<b-modal ref="diamond" id="diamond" centered size="sm" title="เพิ่ม - ลด Diamond" :no-stacking="true"
         :no-close-on-backdrop="true" :hide-footer="true">
    <b-form @submit.prevent.once="diamondSubmit" v-if="show">

        <b-form-group id="input-group-2" label="ประเภทรายการ:" label-for="type">
            <b-form-select
                    id="account_code"
                    v-model="formdiamond.type"
                    :options="typesdiamond"
                    size="sm"
                    required
            ></b-form-select>
        </b-form-group>

        <b-form-group
                id="input-group-1"
                label="จำนวน:"
                label-for="amount"
                description="ระบุจำนวนเงิน ระหว่าง 1 - 10,000">
            <b-form-input
                    id="amount"
                    v-model="formdiamond.amount"
                    type="number"
                    size="sm"
                    placeholder="จำนวนเงิน"
                    min="1"
                    max="10000"
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        <b-form-group
                id="input-group-3"
                label="หมายเหตุ:"
                label-for="remark"
                description="ระบุสาเหตุที่ทำรายการ">
            <b-form-input
                    id="remark"
                    v-model="formdiamond.remark"
                    type="text"
                    placeholder="โปรดระบุ"
                    size="sm"
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-modal>

<b-modal ref="remark" id="remark" centered size="md" title="หมายเหตุ" :no-stacking="true"
         :no-close-on-backdrop="true" :hide-footer="true">
    <b-table striped hover small outlined sticky-header show-empty v-bind:items="myRemark" :fields="fieldsRemark"
             :busy="isBusyRemark"
             ref="tbdataremark" v-if="showremark">
        <template #table-busy>
            <div class="text-center text-danger my-2">
                <b-spinner class="align-middle"></b-spinner>
                <strong>Loading...</strong>
            </div>
        </template>

        <template #cell(action)="data">
            <span v-html="data.value"></span>
        </template>

        <template #thead-top="data">
            <b-tr>
                <b-th colspan="3"></b-th>
                <b-th variant="secondary" class="text-center">
                    <button type="button" class="btn btn-xs btn-primary"
                            @click.once="addSubModal()"><i class="fa fa-plus"></i> Add
                    </button>
                </b-th>

            </b-tr>
        </template>

    </b-table>
</b-modal>

<b-modal ref="addeditsub" id="addeditsub" centered size="sm" title="เพิ่มรายการ" :no-stacking="false"
         :no-close-on-backdrop="true" :hide-footer="true">
    <b-form @submit.prevent.once="addEditSubmitNewSub" v-if="showsub">
        <b-form-group
                id="input-group-remark"
                label="หมายเหตุ:"
                label-for="remark"
                description="">
            <b-form-textarea
                    id="remark"
                    name="remark"
                    v-model="formsub.remark"
                    placeholder=""
                    rows="3"
                    max-rows="6"
                    required
            ></b-form-textarea>
        </b-form-group>

        <b-button type="submit" variant="primary">บันทึก</b-button>

    </b-form>
</b-modal>

<b-modal ref="changepass" id="changepass" centered size="md" title="ระบุรหัสผ่านใหม่ที่ต้องการ" :no-stacking="false"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-form @submit.stop.prevent="changeSubmit" v-if="show" id="frmchange" ref="frmchange">
        <b-form-group
                id="input-group-password"
                label="รหัสผ่านใหม่:"
                label-for="password"
                description="">
            <b-form-input
                    id="remark"
                    v-model="formchange.password"
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
    <script>
        function showModalNew(id, method) {
            window.app.showModalNew(id, method);
        }

        function refill(id) {
            window.app.refill(id);
        }

        function money(id) {
            window.app.money(id);
        }

        function point(id) {
            window.app.point(id);
        }

        function diamond(id) {
            window.app.diamond(id);
        }

        function commentModal(id) {
            window.app.commentModal(id);
        }

        function delSub(id, table) {
            window.app.delSub(id, table);
        }

        function editdatasub(id, status, method) {
            window.app.editdatasub(id, status, method);
        }

        function changegamepass(id) {
            window.app.showModalChange(id);
        }

        // ✅ เพิ่ม: คืนยอดกิจกรรม
        function eventRefund(id) {
            window.app.eventRefund(id);
        }

        $(document).ready(function () {
            $('body').addClass('sidebar-collapse');
        });

    </script>
    <script type="module">
        Dropzone.autoDiscover = false;

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    dz: null,
                    currentPic: null,
                    suppressServerDelete: false,
                    csrf: document.head.querySelector('meta[name="csrf-token"]').content,
                    show: false,
                    showsub: false,
                    showremark: false,
                    fieldsRemark: [],
                    fields: [],
                    items: [],
                    caption: null,
                    isBusy: false,
                    isBusyRemark: false,
                    formmethodsub: 'edit',
                    formsub: {
                        remark: ''
                    },
                    formchange: {
                        id: null,
                        password: ''
                    },

                    // ✅ เพิ่ม: ฟอร์มคืนยอดกิจกรรม
                    formevent: {
                        id: null,
                        event_code: '',
                        amount: 0,
                        remark_admin: ''
                    },
                    // ✅ options สำหรับกิจกรรม
                    eventOptions: [{ value: '', text: '== เลือกกิจกรรม ==' }],

                    formmethod: 'edit',
                    formaddedit: {
                        firstname: '',
                        lastname: '',
                        firstname_addon: '',
                        lastname_addon: '',
                        bank_code: '',
                        user_name: '',
                        user_pass: '',
                        acc_no: '',
                        wallet_id: '',
                        lineid: '',
                        tel: '',
                        pic_id: '',
                        one_time_password: '',
                        refer_code: 0,
                        maxwithdraw_day: 0,
                        af: '',
                        up_name: '',
                        upline_code: '',
                    },
                    option: {
                        bank_code: '',
                        refer_code: '',
                    },
                    formrefill: {
                        id: null,
                        amount: 0,
                        account_code: '',
                        bank_time: '',
                        remark_admin: '',
                        one_time_password: ''
                    },
                    formmoney: {
                        id: null,
                        amount: 0,
                        type: 'D',
                        remark: '',
                        one_time_password: ''
                    },
                    formpoint: {
                        id: null,
                        amount: 0,
                        type: 'D',
                        remark: ''
                    },
                    formdiamond: {
                        id: null,
                        amount: 0,
                        type: 'D',
                        remark: ''
                    },
                    banks: [{value: '', text: '== ธนาคาร =='}],
                    typesmoney: [{value: 'D', text: 'เพิ่ม ยอดเงิน'}, {value: 'W', text: 'ลด ยอดเงิน'}],
                    typespoint: [{value: 'D', text: 'เพิ่ม Point'}, {value: 'W', text: 'ลด Point'}],
                    typesdiamond: [{value: 'D', text: 'เพิ่ม Diamond'}, {value: 'W', text: 'ลด Diamond'}]
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            mounted() {
                this.loadBank();
                this.loadBankAccount();
                this.loadRefer();
                // (ออปชัน) preload รายการกิจกรรมไว้เลยก็ได้
                // this.loadEventOptions();
            },
            methods: {
                openUpload() {
                    this.$refs.dzModal.show()
                },

                // =========================
                // ✅ คืนยอดกิจกรรม
                // =========================
                async loadEventOptions() {
                    try {
                        // ปรับ endpoint ให้ตรงกับของจริงในระบบคุณ
                        // แนะนำให้ backend ส่งเป็น [{value:1,text:'กิจกรรม A'}, ...]
                        const response = await axios.get("{{ url($menu->currentRoute.'/loadevents') }}");
                        if (response?.data?.events) {
                            this.eventOptions = response.data.events;
                            // กันกรณี backend ส่ง array ว่าง
                            if (!Array.isArray(this.eventOptions) || this.eventOptions.length === 0) {
                                this.eventOptions = [{ value: '', text: '== เลือกกิจกรรม ==' }];
                            }
                        }
                    } catch (e) {
                        // fallback
                        this.eventOptions = [{ value: '', text: '== เลือกกิจกรรม ==' }];
                    }
                },
                eventRefund(code) {
                    // เปิด modal คืนยอดกิจกรรม (ต้องมี member code)
                    this.code = code;

                    this.formevent = {
                        id: null,
                        event_code: '',
                        amount: 0,
                        remark_admin: ''
                    };

                    this.show = false;
                    this.$nextTick(async () => {
                        this.show = true;
                        this.formevent.id = code;

                        // โหลดรายการกิจกรรมก่อนเปิด (เพื่อให้ select มีข้อมูล)
                        await this.loadEventOptions();

                        this.$refs.eventRefund.show();
                    });
                },
                eventRefundSubmit(event) {
                    event.preventDefault();

                    // กันคนกดรัว
                    const btn = document.getElementById('btnEventRefundSubmit');
                    if (btn) btn.disabled = true;

                    const url = "{{ route('admin.member.addevent') }}";

                    this.$http.post(url, this.formevent)
                        .then(response => {
                            this.$refs.eventRefund.hide();

                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: (response.data.success ? 'success' : 'danger'),
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });

                            // refresh ตารางหลักถ้ามี
                            try {
                                window.LaravelDataTables["dataTableBuilder"].draw(false);
                            } catch (e) {}

                            // refresh log ถ้ากำลังเปิดดูอยู่
                            try {
                                this.$refs.gamelog && this.$refs.gamelog.refresh && this.$refs.gamelog.refresh();
                            } catch (e) {}

                        })
                        .catch(err => {
                            this.showErrorMessage(err?.response || err);
                        })
                        .finally(() => {
                            if (btn) btn.disabled = false;
                        });
                },

                ensureDropzone() {
                    this.dz = new Dropzone(this.$refs.dz, {
                        url: "{{ route('admin.upload.pic') }}",
                        method: 'post',
                        maxFiles: 1,
                        acceptedFiles: 'image/*',
                        addRemoveLinks: true,
                        dictRemoveFile: 'ลบรูป',
                        previewsContainer: this.$refs.dzPreviews,
                        clickable: [this.$refs.dz, this.$refs.pickBtn],
                        headers: { 'X-CSRF-TOKEN': this.csrf },
                    })

                    this.dz.on('sending', (file, xhr, formData) => {
                        formData.append('id', this.code)
                    })

                    this.dz.on('success', (file, resp) => {
                        file.serverId  = resp.id
                        file.deleteUrl = resp.delete_url || "{{ route('admin.delete.pic', ['id' => ':id']) }}".replace(':id', resp.id)
                        this.currentPic = { id: resp.id, name: file.name, size: file.size, url: resp.url }
                    })

                    this.dz.on('maxfilesexceeded', file => {
                        this.suppressServerDelete = true
                        this.dz.removeAllFiles(true)
                        this.suppressServerDelete = false
                        this.dz.addFile(file)
                    })

                    this.onRemovedFile = (file) => {
                        if (this.suppressServerDelete) return
                        if (!file.serverId) return
                        const url = (file.deleteUrl || "{{ route('admin.delete.pic', ['id' => ':id']) }}".replace(':id', file.serverId))
                        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf } })
                            .then(() => {
                                if (this.currentPic && String(this.currentPic.id) === String(file.serverId)) {
                                    this.currentPic = null
                                }
                            })
                    }
                    this.dz.on('removedfile', this.onRemovedFile)
                },
                onDzShown() {
                    this.ensureDropzone()

                    this.suppressServerDelete = true
                    this.dz.removeAllFiles(true)
                    this.suppressServerDelete = false

                    this.$refs.dz.classList.remove('dz-started')
                    const msg = this.$refs.dz.querySelector('.dz-message')
                    if (msg) msg.style.display = ''

                    if (this.dz.hiddenFileInput) {
                        this.dz.hiddenFileInput.disabled = false
                    }
                    if (typeof this.dz.enable === 'function') {
                        this.dz.enable()
                    }

                    if (this.currentPic && this.currentPic.url) {
                        const f = this.currentPic
                        const mock = { name: f.name || 'existing.jpg', size: f.size || 12345, serverId: f.id, isExisting: true, url: f.url }
                        this.dz.emit('addedfile', mock)
                        this.dz.emit('thumbnail', mock, f.url)
                        this.dz.emit('complete', mock)
                        this.dz.files.push(mock)
                    }
                },
                onDzHide() {
                    if (!this.dz) return
                    this.suppressServerDelete = true
                    this.dz.removeAllFiles(true)
                    this.suppressServerDelete = false

                    const dzEl = this.$refs?.dz
                    if (dzEl && dzEl.classList) dzEl.classList.remove('dz-started')
                    const msg = dzEl ? dzEl.querySelector('.dz-message') : null
                    if (msg) msg.style.display = ''
                },
                onDzHidden() {
                    if (this.dz) {
                        this.suppressServerDelete = true
                        this.dz.removeAllFiles(true)
                        this.suppressServerDelete = false
                    }
                    const dzEl = this.$refs?.dz
                    if (dzEl?.classList) dzEl.classList.remove('dz-started')
                    const msg = dzEl?.querySelector?.('.dz-message')
                    if (msg) msg.style.display = ''
                },
                setCurrentPicFromPath(path) {
                    if (!path) {
                        this.currentPic = null;
                        return
                    }
                    const fileName = path.split('/').pop()
                    const url = `{{ url('/storage') }}/${path}`
                    this.currentPic = {id: this.code, name: fileName, url, size: 12345}
                },
                showModalChange(code) {
                    this.formchange = {
                        id: null,
                        password: '',
                    }

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.formchange.id = code;
                        this.$refs.changepass.show();
                    })

                },
                async changeType(event) {
                    const response = await axios.get("{{ url($menu->currentRoute.'/loadaf') }}", {
                        params: {
                            af: event
                        }
                    });

                    if(response.data.success) {
                        this.formaddedit.up_name = response.data.data.name;
                        this.formaddedit.upline_code = response.data.data.code;
                    }else{
                        this.formaddedit.up_name = '';
                        this.formaddedit.upline_code = 0;
                    }
                },
                changeSubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);
                    this.$http.post("{{ url($menu->currentRoute.'/changegamepass') }}", this.formchange)
                        .then(response => {
                            this.$refs.changepass.hide();
                            this.$refs.tbdatalog.refresh();

                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });

                        })
                        .catch(exception => {
                            console.log('error');
                            this.toggleButtonDisable(false);
                        });
                },
                async changegamepass(id) {
                    const {value: password} = await Swal.fire({
                        title: "ระบุรหัสผ่านใหม่ที่ต้องการ ไม่เกิน 15 ตัว",
                        input: 'password',
                        inputLabel: 'Password',
                        inputPlaceholder: 'Enter your password',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'ตกลง',
                        cancelButtonText: 'ยกเลิก',
                        customClass: {
                            container: 'text-sm',
                            popup: 'text-sm'
                        },
                        inputAttributes: {
                            maxlength: 15,
                            autocapitalize: 'off',
                            autocorrect: 'off',
                            autocomplete: 'off'
                        }
                    })

                    if (password) {
                        $('.modal').modal('hide');

                        this.$http.post("{{ url($menu->currentRoute.'/changegamepass') }}", {
                            id: id,
                            password: password
                        })
                            .then(response => {

                                if (response.data.success) {
                                    Swal.fire(
                                        'ดำเนินการสำเร็จ',
                                        response.data.message,
                                        'success'
                                    );
                                    this.$refs.gamelog.refresh();
                                } else {
                                    Swal.fire(
                                        'พบข้อผิดพลาด',
                                        response.data.message,
                                        'error'
                                    );
                                }
                            })
                            .catch(response => {

                                $('.modal').modal('hide');
                                Swal.fire(
                                    'การเชื่อมต่อระบบ มีปัญหา',
                                    response.data.message,
                                    'error'
                                );
                            });
                    }
                },
                editdatasub(code, status, method) {

                    this.$bvModal.msgBoxConfirm('ต้องการดำเนินการ ยกเลิก GAME ID นี้หรือไม่ เมื่อยกเลิกแล้ว ลูกค้าสามารถกด สมัครเข้ามาใหม่ได้.', {
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
                                this.$http.post("{{ url($menu->currentRoute.'/editsub') }}", {
                                    id: code,
                                    status: status,
                                    method: method
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
                                        this.$refs.gamelog.refresh()
                                    })
                                    .catch(exception => {
                                        console.log('error');
                                    });
                            }
                        })
                        .catch(err => {
                        })

                },
                showModalNew(code, method) {
                    this.code = code;
                    this.method = method;
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.myLog();
                        this.$refs.gamelog.show();
                    })

                },
                commentModal(code) {
                    this.code = code;

                    this.showremark = false;
                    this.$nextTick(() => {
                        this.showremark = true;
                        this.$refs.remark.show();
                    })

                },
                refill(code) {
                    this.code = code;
                    this.formrefill = {
                        id: code,
                        amount: 0,
                        account_code: '',
                        bank_time: this.nowDatetimeLocal(),
                        remark_admin: '',
                        one_time_password: ''
                    }
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.refill.show();

                    })
                },
                nowDatetimeLocal() {
                    const now = new Date();
                    const pad = (value) => String(value).padStart(2, '0');
                    const year = now.getFullYear();
                    const month = pad(now.getMonth() + 1);
                    const day = pad(now.getDate());
                    const hour = pad(now.getHours());
                    const minute = pad(now.getMinutes());

                    return `${year}-${month}-${day}T${hour}:${minute}`;
                },
                money(code) {
                    this.formmoney.id = null;
                    this.formmoney.amount = 0;
                    this.formmoney.remark = '';
                    this.formmoney.type = 'D';
                    this.formmoney.one_time_password = '';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.formmoney.id = code;
                        this.$refs.money.show();

                    })
                },
                point(code) {
                    this.formpoint.id = null;
                    this.formpoint.amount = 0;
                    this.formpoint.remark = '';
                    this.formpoint.type = 'D';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.formpoint.id = code;
                        this.$refs.point.show();
                    })

                },
                diamond(code) {
                    this.formdiamond.id = null;
                    this.formdiamond.amount = 0;
                    this.formdiamond.remark = '';
                    this.formdiamond.type = 'D';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.formdiamond.id = code;
                        this.$refs.diamond.show();
                    })

                },
                refillSubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);
                    const payload = Object.assign({}, this.formrefill, {id: this.code});
                    this.$http.post("{{ url($menu->currentRoute.'/refill') }}", payload)
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
                },
                moneySubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    this.$http.post("{{ url($menu->currentRoute.'/setwallet') }}", this.formmoney)
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

                },
                pointSubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    this.$http.post("{{ url($menu->currentRoute.'/setpoint') }}", this.formpoint)
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

                },
                diamondSubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    this.$http.post("{{ url($menu->currentRoute.'/setdiamond') }}", this.formdiamond)
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

                },
                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        firstname: '',
                        lastname: '',
                        firstname_addon: '',
                        lastname_addon: '',
                        bank_code: '',
                        user_name: '',
                        user_pass: '',
                        acc_no: '',
                        wallet_id: '',
                        lineid: '',
                        pic_id: '',
                        tel: '',
                        refer_code: 0,
                        maxwithdraw_day: 0,
                        af: '',
                        up_name: '',
                        upline_code: '',
                    }
                    this.formmethod = 'edit';

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
                        firstname: '',
                        lastname: '',
                        firstname_addon: '',
                        lastname_addon: '',
                        bank_code: '',
                        user_name: '',
                        user_pass: '',
                        acc_no: '',
                        wallet_id: '',
                        lineid: '',
                        pic_id: '',
                        tel: '',
                        refer_code: 0,
                        maxwithdraw_day: 0,
                        af: '',
                        up_name: '',
                        upline_code: '',
                    }
                    this.formmethod = 'add';

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();

                    })
                },
                async loadData() {
                    const response = await axios.get("{{ url($menu->currentRoute.'/loaddata') }}", {
                        params: {
                            id: this.code
                        }
                    });
                    const u = response.data.data;

                    this.formaddedit = {
                        firstname: response.data.data.firstname,
                        lastname: response.data.data.lastname,
                        firstname_addon: response.data.data.firstname_addon,
                        lastname_addon: response.data.data.lastname_addon,
                        bank_code: response.data.data.bank_code,
                        user_name: response.data.data.user_name,
                        user_pass: '',
                        acc_no: response.data.data.acc_no,
                        wallet_id: response.data.data.wallet_id,
                        lineid: response.data.data.lineid,
                        pic_id: response.data.data.pic_id,
                        tel: response.data.data.tel,
                        maxwithdraw_day: response.data.data.maxwithdraw_day,
                        refer_code: response.data.data.refer_code,
                    }
                    if (u.pic_id) {
                        const fileName = u.pic_id.split('/').pop();
                        const fileUrl = this.fileUrl(u.pic_id);

                        this.currentPic = {
                            id: this.code,
                            name: fileName,
                            url: fileUrl,
                            size: 12345,
                            isExisting: true
                        };
                    } else {
                        this.currentPic = null;
                    }
                },
                fileUrl(path) {
                    return `{{ url('/storage') }}/${path}`;
                },
                async loadBank() {
                    const response = await axios.get("{{ url($menu->currentRoute.'/loadbank') }}");
                    this.option.bank_code = response.data.banks;
                },
                async loadRefer() {
                    const response = await axios.get("{{ url($menu->currentRoute.'/loadrefer') }}");
                    this.option.refer_code = response.data.refers;
                },
                async loadBankAccount() {
                    const response = await axios.get("{{ url($menu->currentRoute.'/loadbankaccount') }}");
                    this.banks = response.data.banks;
                },
                async myLog() {
                    let self = this;
                    self.items = [];
                    const response = await axios.get("{{ url($menu->currentRoute.'/gamelog') }}", {
                        params: {
                            id: this.code,
                            method: this.method
                        }
                    });

                    this.caption = response.data.name;
                    if (this.method === 'transfer') {
                        this.fields = [
                            {key: 'date_create', label: 'วันที่'},
                            {key: 'id', label: 'บิลเลขที่'},
                            {key: 'transfer', label: 'ประเภท'},
                            {key: 'game_name', label: 'เกม'},
                            {key: 'amount', label: 'จำนวนเงิน', class: 'text-right'},
                            {key: 'status', label: 'สถานะบิล', class: 'text-center'},

                        ];
                        this.items = response.data.list;
                    } else if (this.method === 'gameuser') {

                        this.fields = [
                            {key: 'game', label: 'เกม'},
                            {key: 'user_name', label: 'บัญชีเกม'},
                            {key: 'user_pass', label: 'รหัสผ่าน'},
                            {key: 'status', label: 'ข้อมูลจาก', class: 'text-center'},
                            {key: 'balance', label: 'ยอดคงเหลือ', class: 'text-right'},
                            {key: 'promotion', label: 'โปรที่รับมา', class: 'text-left'},
                            {key: 'turn', label: 'Turn', class: 'text-center'},
                            {key: 'amount_balance', label: 'ยอดเทินขั้นต่ำ', class: 'text-right'},
                            {key: 'withdraw_limit', label: 'ถอนได้รับไม่เกิน', class: 'text-right'},
                            {key: 'action', label: 'ยกเลิก ID', class: 'text-center'},
                            {key: 'changepass', label: 'เปลี่ยนรหัส', class: 'text-center'},
                        ];

                        $.each(response.data.list, function (key, value) {
                            self.getbalancenew(key, value);
                        });

                    } else if (this.method === 'deposit') {
                        @if($webconfig->multigame_open == 'Y')
                            this.fields = [
                            {key: 'date_create', label: 'วันที่'},
                            {key: 'id', label: 'บิลเลขที่'},
                            {key: 'amount', label: 'จำนวนเงิน', class: 'text-right'},
                            {key: 'credit_before', label: 'ก่อนฝาก', class: 'text-right'},
                            {key: 'credit_after', label: 'หลังฝาก', class: 'text-right'},

                        ];
                        @else
                            this.fields = [
                            { key: 'id',            label: 'รหัส',          sortable: false },
                            { key: 'date_create',   label: 'เวลา',          sortable: true },
                            { key: 'amount',        label: 'ยอดฝาก',      sortable: false },
                            { key: 'pro_name',        label: 'โปรโมชั่น',       sortable: true },
                            { key: 'credit_bonus',  label: 'โบนัสที่ได้',         sortable: false },
                            { key: 'credit_before', label: 'เครดิตก่อน',    sortable: false },
                            { key: 'credit_after',  label: 'เครดิตหลัง',    sortable: false },
                            { key: 'status_display',        label: 'สถานะ',         sortable: true },

                        ];
                        @endif
                            this.items = response.data.list;
                    } else if (this.method === 'withdraw') {
                        this.fields = [
                            { key: 'id',            label: 'รหัส',          sortable: false },
                            { key: 'date_create',   label: 'เวลา',          sortable: true },
                            { key: 'amount_request',label: 'ยอดแจ้ง',       sortable: false },
                            { key: 'amount',        label: 'ยอดถอนที่ได้รับ',      sortable: false },
                            { key: 'credit_before', label: 'เครดิตก่อน',    sortable: false },
                            { key: 'credit_after',  label: 'เครดิตหลัง',    sortable: false },
                            { key: 'status_display',        label: 'สถานะ',         sortable: true },
                        ];
                        this.items = response.data.list;
                    } else if (this.method === 'setwallet') {

                        @if($webconfig->multigame_open == 'Y')
                            this.fields = [
                            {key: 'date_create', label: 'วันที่'},
                            {key: 'credit_type', label: 'ประเภทรายการ'},
                            {key: 'remark', label: 'หมายเหตุ'},
                            {key: 'credit_amount', label: 'จำนวน Wallet', class: 'text-right'},
                            {key: 'credit_before', label: 'Wallet ก่อนหน้า', class: 'text-right'},
                            {key: 'credit_balance', label: 'รวม Wallet', class: 'text-right'}
                        ];
                        @else
                            this.fields = [
                            {key: 'date_create', label: 'วันที่'},
                            {key: 'credit_type', label: 'ประเภทรายการ'},
                            {key: 'remark', label: 'หมายเหตุ'},
                            {key: 'credit_amount', label: 'จำนวน Credit', class: 'text-right'},
                            {key: 'credit_before', label: 'Credit ก่อนหน้า', class: 'text-right'},
                            {key: 'credit_balance', label: 'รวม Credit', class: 'text-right'}
                        ];
                        @endif

                            this.items = response.data.list;
                    } else if (this.method === 'setpoint') {
                        this.fields = [
                            {key: 'date_create', label: 'วันที่'},
                            {key: 'credit_type', label: 'ประเภทรายการ'},
                            {key: 'remark', label: 'หมายเหตุ'},
                            {key: 'credit_amount', label: 'จำนวน Point', class: 'text-right'},
                            {key: 'credit_before', label: 'Point ก่อนหน้า', class: 'text-right'},
                            {key: 'credit_balance', label: 'รวม Point', class: 'text-right'}
                        ];

                        this.items = response.data.list;
                    } else if (this.method === 'setdiamond') {
                        this.fields = [
                            {key: 'date_create', label: 'วันที่'},
                            {key: 'credit_type', label: 'ประเภทรายการ'},
                            {key: 'remark', label: 'หมายเหตุ'},
                            {key: 'credit_amount', label: 'จำนวน Diamond', class: 'text-right'},
                            {key: 'credit_before', label: 'Diamond ก่อนหน้า', class: 'text-right'},
                            {key: 'credit_balance', label: 'รวม Diamond', class: 'text-right'}
                        ];
                        this.items = response.data.list;
                    } else {
                        this.fields = [];
                        this.items = [];
                    }

                },
                async getbalance(key, value) {

                    let game = [];
                    const response = await axios.get("{{ url($menu->currentRoute.'/balance') }}", {
                        params: {
                            game_code: value.game_code,
                            member_code: value.member_code
                        }
                    })

                    this.items = game;

                },
                getbalancenew(key, value) {

                    var game = this.items;
                    axios.get("{{ url($menu->currentRoute.'/balance') }}", {
                        params: {
                            game_code: value.game_code,
                            member_code: value.member_code
                        }
                    }).then(function (response) {
                        if (response.data.success) {
                            game.push(response.data.list);
                        } else {
                            game.push(value);
                        }
                    }).catch(error => {
                        game.push(value);
                    });

                },
                async myRemark() {
                    const response = await axios.get("{{ url($menu->currentRoute.'/remark') }}", {
                        params: {
                            id: this.code
                        }
                    });


                    this.fieldsRemark = [
                        {key: 'date_create', label: 'วันที่'},
                        {key: 'remark', label: 'หมายเหตุ'},
                        {key: 'emp_code', label: 'ผู้เพิ่มรายการ'},
                        {key: 'action', label: '', class: 'text-center'}
                    ];

                    this.items = response.data.list;
                    return this.items;

                },
                addSubModal() {

                    this.formsub = {
                        remark: ''
                    }
                    this.formmethodsub = 'add';

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
                },
                showErrorMessage(response) {
                    let message = response?.data?.message || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ';

                    if (typeof message === 'object') {
                        try {
                            message = Object.values(message).flat().join('\n');
                        } catch (e) {
                            message = [].concat(...Object.values(message)).join('\n');
                        }
                    }

                    if (Array.isArray(message)) {
                        message = message.join('\n');
                    }

                    this.$bvModal.msgBoxOk(message, {
                        title: 'ผลการดำเนินการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        headerClass: 'p-2 border-bottom-0',
                        footerClass: 'p-2 border-top-0',
                        centered: true
                    });

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
                        firstname: this.formaddedit.firstname,
                        lastname: this.formaddedit.lastname,
                        firstname_addon: this.formaddedit.firstname_addon,
                        lastname_addon: this.formaddedit.lastname_addon,
                        bank_code: this.formaddedit.bank_code,
                        user_name: this.formaddedit.user_name,
                        user_pass: this.formaddedit.user_pass,
                        acc_no: this.formaddedit.acc_no,
                        wallet_id: this.formaddedit.wallet_id,
                        lineid: this.formaddedit.lineid,
                        pic_id: this.formaddedit.pic_id,
                        tel: this.formaddedit.tel,
                        one_time_password: this.formaddedit.one_time_password,
                        maxwithdraw_day: this.formaddedit.maxwithdraw_day,
                        refer_code: this.formaddedit.refer_code,
                        upline_code: this.formaddedit.upline_code,
                    });

                    formData.append('data', json);

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
                                this.showErrorMessage(response);
                            }
                        })
                        .catch(errors => {
                            this.toggleButtonDisable(false);
                            Toast.fire({
                                icon: 'error',
                                title: errors.data.message
                            })
                        });

                },
                addEditSubmitNewSub(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    var url = "{{ url($menu->currentRoute.'/createsub') }}";

                    this.$http.post(url, {id: this.code, data: this.formsub})
                        .then(response => {
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

                            this.$refs.tbdataremark.refresh()

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

                },
            },
        })
        ;
    </script>
@endpush
