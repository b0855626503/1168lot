<div id="member-refill-app">

    {{-- ฟอร์มเลือกเป้าหมายที่จะเติมให้ (เดิม addedit) --}}
    <b-modal
            ref="assignTopupTargetModal"
            id="assignTopupTargetModal"
            centered
            size="md"
            title="เลือกระบุไอดีที่จะเติมให้"
            :no-close-on-backdrop="true"
            :hide-footer="true"
            :lazy="true"
            @shown="removeFocusFromTrigger"
    >
        <b-container class="bv-example-row">
            <b-form
                    v-if="showRefillUI"
                    ref="assignTopupTargetFormRef"
                    id="assignTopupTargetForm"
                    @submit.prevent="submitAssignTopupTarget"
            >
                <input
                        type="hidden"
                        id="member_topup"
                        :value="assignTopupTargetForm.member_topup"
                        required
                >

                <b-form-row>
                    <b-col>
                        <b-form-group
                                id="input-group-user_name"
                                label="User ID / Game ID:"
                                label-for="user_name"
                                description="ระบุ User / Game ID ที่ต้องการเติมให้บิลนี้"
                        >
                            <b-input-group>
                                <b-form-input
                                        id="user_name"
                                        v-model="assignTopupTargetForm.user_name"
                                        type="text"
                                        size="md"
                                        placeholder="User / Game ID"
                                        autocomplete="off"
                                ></b-form-input>
                                <b-input-group-append>
                                    <b-button
                                            variant="success"
                                            @click="loadUserForAssignTarget"
                                    >
                                        ค้นหา
                                    </b-button>
                                </b-input-group-append>
                            </b-input-group>
                        </b-form-group>
                    </b-col>
                </b-form-row>

                <b-form-row>
                    <b-col>
                        <b-form-group
                                id="input-group-name"
                                label="ข้อมูลลูกค้า:"
                                label-for="name"
                        >
                            <b-form-textarea
                                    id="name"
                                    v-model="assignTopupTargetForm.name"
                                    size="sm"
                                    rows="6"
                                    max-rows="6"
                                    required
                                    plaintext
                            ></b-form-textarea>
                        </b-form-group>
                    </b-col>
                </b-form-row>

                <b-form-row>
                    <b-col>
                        <b-form-group
                                id="input-group-remark_admin"
                                label="หมายเหตุ:"
                                label-for="remark_admin"
                        >
                            <b-form-input
                                    id="remark_admin"
                                    v-model="assignTopupTargetForm.remark_admin"
                                    type="text"
                                    size="sm"
                                    autocomplete="off"
                                    placeholder=""
                            ></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-form-row>

                <b-button type="submit" variant="primary">
                    บันทึก
                </b-button>
            </b-form>
        </b-container>
    </b-modal>

    {{-- ฟอร์มเติมเงิน (เดิม refill) --}}
    <b-modal
            ref="refillModal"
            id="refillModal"
            centered
            size="lg"
            title="เติมเงิน"
            :no-close-on-backdrop="true"
            :hide-footer="true"
            :lazy="true"
            @shown="removeFocusFromTrigger"
            @hidden="onRefillModalHidden"
    >
        <b-container class="bv-example-row">
            <b-form-row>

                {{-- ฝั่งซ้าย: ฟอร์มเติมเงิน --}}
                <b-col cols="12" md="5" class="refill-form-col">
                    <b-form
                            v-if="showRefillUI"
                            ref="refillFormRef"
                            id="refillForm"
                            @submit.prevent="submitRefillForm"
                    >
                        <input
                                type="hidden"
                                id="id"
                                :value="refillForm.id"
                                required
                        >

                        <b-form-row>
                            <b-col>
                                <b-form-group
                                        id="input-group-refill-user"
                                        label="User ID / Game ID:"
                                        label-for="refill_user_name"
                                        description="ระบุ User / Game ID ที่ต้องการเติมเงินรายการนี้"
                                >
                                    <b-input-group>
                                        <b-form-input
                                                id="refill_user_name"
                                                v-model="refillForm.user_name"
                                                type="text"
                                                size="md"
                                                placeholder="User / Game ID"
                                                autocomplete="off"
                                        ></b-form-input>
                                        <b-input-group-append>
                                            <b-button
                                                    variant="success"
                                                    @click="loadUserForRefill"
                                            >
                                                ค้นหา
                                            </b-button>
                                        </b-input-group-append>
                                    </b-input-group>
                                </b-form-group>
                            </b-col>
                        </b-form-row>

                        <b-form-row>
                            <b-col>
                                <b-form-group
                                        id="input-group-refill-name"
                                        label="ข้อมูลลูกค้า:"
                                        label-for="refill_name"
                                >
                                    <b-form-textarea
                                            id="refill_name"
                                            v-model="refillForm.name"
                                            size="sm"
                                            rows="6"
                                            max-rows="6"
                                            required
                                            plaintext
                                    ></b-form-textarea>
                                </b-form-group>
                            </b-col>
                        </b-form-row>

                        <b-form-row>
                            <b-col>
                                <b-form-group
                                        id="input-group-amount"
                                        label="จำนวนเงิน:"
                                        label-for="amount"
                                        description="ระบุจำนวนเงิน ระหว่าง 1 - 10,000"
                                >
                                    <b-form-input
                                            id="amount"
                                            v-model="refillForm.amount"
                                            type="number"
                                            size="sm"
                                            placeholder="จำนวนเงิน"
                                            min="1"
                                            max="10000"
                                            autocomplete="off"
                                            required
                                    ></b-form-input>
                                </b-form-group>
                            </b-col>
                        </b-form-row>

                        <b-form-row>
                            <b-col>
                                <b-form-group
                                        id="input-group-account_code"
                                        label="ช่องทางที่ฝาก:"
                                        label-for="account_code"
                                >
                                    <b-form-select
                                            id="account_code"
                                            v-model="refillForm.account_code"
                                            :options="banks"
                                            size="sm"
                                            required
                                    ></b-form-select>
                                </b-form-group>
                            </b-col>
                        </b-form-row>

                        <b-form-row>
                            <b-col>
                                <b-form-group
                                        id="input-group-refill-remark"
                                        label="หมายเหตุ:"
                                        label-for="refill_remark_admin"
                                >
                                    <b-form-input
                                            id="refill_remark_admin"
                                            v-model="refillForm.remark_admin"
                                            type="text"
                                            size="sm"
                                            autocomplete="off"
                                    ></b-form-input>
                                </b-form-group>
                            </b-col>
                        </b-form-row>

                        <b-button type="submit" variant="primary">
                            บันทึก
                        </b-button>
                    </b-form>
                </b-col>

                {{-- ฝั่งขวา: Log ประวัติเติมเงิน --}}
                <b-col cols="12" md="7" class="refill-log-col">
                    <div class="refill-log-card" v-if="showRefillUI">
                        <div class="refill-log-header d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <div class="refill-log-title">ประวัติการเติมเงิน</div>
                                <div class="refill-log-sub" v-if="refillForm.user_name">
                                    User: <span v-text="refillForm.user_name"></span>
                                </div>
                            </div>
                            <div class="refill-log-status" v-if="isBusy">
                                <b-spinner class="mr-1"></b-spinner>
                                <span>กำลังโหลด...</span>
                            </div>
                        </div>

                        <b-table
                                striped
                                hover
                                outlined
                                sticky-header
                                show-empty
                                :items="items"
                                :fields="fields"
                                :busy="isBusy"
                                ref="tbdatalog"
                                class="refill-log-table"
                        >
                            <template #table-busy>
                                <div class="text-center text-muted my-2">
                                    <b-spinner class="align-middle"></b-spinner>
                                    <strong>กำลังโหลดข้อมูล...</strong>
                                </div>
                            </template>

                            <!-- รหัสรายการ -->
                            <template #cell(id)="{ item }">
                                <span v-text="item.id"></span>
                            </template>

                            <!-- เวลา -->
                            <template #cell(date_create)="{ item }">
                                <span v-text="item.date_create"></span>
                            </template>

                            <!-- ช่องทาง เช่น AUTO / แอดมิน / คิวอาร์ -->
                            <template #cell(method)="{ item }">
                                <span v-text="item.method"></span>
                            </template>

                            <!-- ยอดที่ลูกค้าแจ้ง (ฝาก/ถอน) -->
                            <template #cell(amount_request)="{ item }">
                                <span v-text="intToMoney(item.amount_request)"></span>
                            </template>

                            <!-- โบนัส / โปร (ถ้ามี) -->
                            <template #cell(credit_bonus)="{ item }">
                                <span v-if="item.credit_bonus" v-text="intToMoney(item.credit_bonus)"></span>
                                <span v-else class="text-muted">-</span>
                            </template>

                            <!-- ยอดสุทธิหลังดำเนินการ -->
                            <template #cell(amount)="{ item }">
                                <span v-text="intToMoney(item.amount)"></span>
                            </template>

                            <!-- เครดิตก่อน/หลัง -->
                            <template #cell(credit_before)="{ item }">
                                <span v-text="intToMoney(item.credit_before)"></span>
                            </template>

                            <template #cell(credit_after)="{ item }">
                                <span v-text="intToMoney(item.credit_after)"></span>
                            </template>

                            <!-- สถานะ -->
                            <template #cell(status)="{ item }">
                            <span
                                    class="badge"
                                    :class="'bg-' + (item.status_color || 'secondary')"
                                    v-text="item.status_display"
                            ></span>
                            </template>
                        </b-table>
                    </div>
                </b-col>

            </b-form-row>
        </b-container>
    </b-modal>


    {{-- ฟอร์มหมายเหตุยกเลิกรายการ (เดิม clear) --}}
    <b-modal
            ref="clearRemarkModal"
            id="clearRemarkModal"
            centered
            size="md"
            title="โปรดระบุหมายเหตุ ในการทำรายการ"
            :no-close-on-backdrop="true"
            :hide-footer="true"
            :lazy="true"
            @shown="removeFocusFromTrigger"
    >
        <b-form
                v-if="showRefillUI"
                ref="clearRemarkFormRef"
                id="clearRemarkForm"
                @submit.stop.prevent="submitClearRemarkForm"
        >
            <b-form-group
                    id="input-group-clear-remark"
                    label="หมายเหตุ:"
                    label-for="clear_remark"
            >
                <b-form-input
                        id="clear_remark"
                        v-model="clearRemarkForm.remark"
                        type="text"
                        size="sm"
                        placeholder=""
                        autocomplete="off"
                        required
                ></b-form-input>
            </b-form-group>

            <b-button type="submit" variant="primary">
                บันทึก
            </b-button>
        </b-form>
    </b-modal>

    {{-- NEW: ฟอร์มเพิ่ม - ลด ยอดเงิน --}}
    <b-modal
            id="member-refill-money-modal"
            ref="memberRefillMoneyModal"
            title="เพิ่ม / ลด เครดิต"
            size="sm"
            centered
            :no-close-on-backdrop="true"
            :hide-footer="true"
            @shown="
        removeFocusFromTrigger();
        window.memberAdjustBridge
            && window.memberAdjustBridge.onChildShown
            && window.memberAdjustBridge.onChildShown()
    "
            @hidden="
        window.memberAdjustBridge
            && window.memberAdjustBridge.onChildHidden
            && window.memberAdjustBridge.onChildHidden()
    "
    >
        <b-form @submit.prevent.once="moneySubmit" v-if="showRefillUI">
            <b-form-group
                    id="input-group-money-type"
                    label="ประเภทรายการ:"
                    label-for="money_type"
            >
                <b-form-select
                        id="money_type"
                        v-model="formmoney.type"
                        :options="typesmoney"
                        size="sm"
                        required
                ></b-form-select>
            </b-form-group>

            <b-form-group
                    id="input-group-money-amount"
                    label="จำนวนเงิน:"
                    label-for="money_amount"
                    description="ระบุจำนวนเงิน ต่ำสุดคือ 1"
            >
                <b-form-input
                        id="money_amount"
                        v-model="formmoney.amount"
                        type="number"
                        size="sm"
                        placeholder="จำนวนเงิน"
                        min="1"
                        step="0.01"
                        autocomplete="off"
                        required
                ></b-form-input>
            </b-form-group>

            <b-form-group
                    id="input-group-money-remark"
                    label="หมายเหตุ:"
                    label-for="money_remark"
                    description="ระบุสาเหตุที่ทำรายการ"
            >
                <b-form-input
                        id="money_remark"
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

    {{-- NEW: ฟอร์มเพิ่ม - ลด Point --}}
    <b-modal
            id="member-refill-point-modal"
            ref="memberRefillPointModal"
            title="เพิ่ม / ลด Point"
            size="sm"
            centered
            :no-close-on-backdrop="true"
            :hide-footer="true"
            @shown="
        removeFocusFromTrigger();
        window.memberAdjustBridge
            && window.memberAdjustBridge.onChildShown
            && window.memberAdjustBridge.onChildShown()
    "
            @hidden="
        window.memberAdjustBridge
            && window.memberAdjustBridge.onChildHidden
            && window.memberAdjustBridge.onChildHidden()
    "
    >
        <b-form @submit.prevent.once="pointSubmit" v-if="showRefillUI">
            <b-form-group
                    id="input-group-point-type"
                    label="ประเภทรายการ:"
                    label-for="point_type"
            >
                <b-form-select
                        id="point_type"
                        v-model="formpoint.type"
                        :options="typespoint"
                        size="sm"
                        required
                ></b-form-select>
            </b-form-group>

            <b-form-group
                    id="input-group-point-amount"
                    label="จำนวน:"
                    label-for="point_amount"
                    description="ระบุจำนวน ระหว่าง 0 - 10,000"
            >
                <b-form-input
                        id="point_amount"
                        v-model="formpoint.amount"
                        type="number"
                        size="sm"
                        placeholder="จำนวน"
                        min="1"
                        max="10000"
                        autocomplete="off"
                        required
                ></b-form-input>
            </b-form-group>

            <b-form-group
                    id="input-group-point-remark"
                    label="หมายเหตุ:"
                    label-for="point_remark"
                    description="ระบุสาเหตุที่ทำรายการ"
            >
                <b-form-input
                        id="point_remark"
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

    {{-- NEW: ฟอร์มเพิ่ม - ลด Diamond --}}
    <b-modal
            id="member-refill-diamond-modal"
            ref="memberRefillDiamondModal"
            title="เพิ่ม / ลด Diamond"
            size="sm"
            centered
            :no-close-on-backdrop="true"
            :hide-footer="true"
            @shown="
        removeFocusFromTrigger();
        window.memberAdjustBridge
            && window.memberAdjustBridge.onChildShown
            && window.memberAdjustBridge.onChildShown()
    "
            @hidden="
        window.memberAdjustBridge
            && window.memberAdjustBridge.onChildHidden
            && window.memberAdjustBridge.onChildHidden()
    "
    >
        <b-form @submit.prevent.once="diamondSubmit" v-if="showRefillUI">
            <b-form-group
                    id="input-group-diamond-type"
                    label="ประเภทรายการ:"
                    label-for="diamond_type"
            >
                <b-form-select
                        id="diamond_type"
                        v-model="formdiamond.type"
                        :options="typesdiamond"
                        size="sm"
                        required
                ></b-form-select>
            </b-form-group>

            <b-form-group
                    id="input-group-diamond-amount"
                    label="จำนวน:"
                    label-for="diamond_amount"
                    description="ระบุจำนวนเงิน ระหว่าง 1 - 10,000"
            >
                <b-form-input
                        id="diamond_amount"
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
                    id="input-group-diamond-remark"
                    label="หมายเหตุ:"
                    label-for="diamond_remark"
                    description="ระบุสาเหตุที่ทำรายการ"
            >
                <b-form-input
                        id="diamond_remark"
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

    {{-- NEW: ประวัติ ฝาก - ถอน --}}
    <b-modal
            ref="gamelog"
            id="gamelog"
            :title="caption"
            size="lg"
            centered
            :no-close-on-backdrop="true"
            :hide-footer="true"
            @shown="
        removeFocusFromTrigger();
        window.memberLogBridge
            && window.memberLogBridge.onChildShown
            && window.memberLogBridge.onChildShown()
    "
            @hidden="
        window.memberLogBridge
            && window.memberLogBridge.onChildHidden
            && window.memberLogBridge.onChildHidden()
    "
            :ok-only="true"
            :lazy="true"
    >
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
                v-if="showRefillUI"
        >
            <template #table-busy>
                <div class="text-center text-danger my-2">
                    <b-spinner class="align-middle"></b-spinner>
                    <strong>Loading...</strong>
                </div>
            </template>

            <!-- รหัสรายการ -->
            <template #cell(id)="{ item }">
                <span v-text="item.id"></span>
            </template>

            <!-- เวลา -->
            <template #cell(date_create)="{ item }">
                <span v-text="item.date_create"></span>
            </template>

            <!-- ช่องทาง เช่น AUTO / แอดมิน / คิวอาร์ -->
            <template #cell(method)="{ item }">
                <span v-text="item.method"></span>
            </template>

            <!-- ยอดที่ลูกค้าแจ้ง (ฝาก/ถอน) -->
            <template #cell(amount_request)="{ item }">
                <span v-text="intToMoney(item.amount_request)"></span>
            </template>

            <!-- โบนัส / โปร (ถ้ามี) -->
            <template #cell(credit_bonus)="{ item }">
                <span v-if="item.credit_bonus" v-text="intToMoney(item.credit_bonus)"></span>
                <span v-else class="text-muted">-</span>
            </template>

            <!-- ยอดสุทธิหลังดำเนินการ -->
            <template #cell(amount)="{ item }">
                <span v-text="intToMoney(item.amount)"></span>
            </template>

            <!-- เครดิตก่อน/หลัง -->
            <template #cell(credit_before)="{ item }">
                <span v-text="intToMoney(item.credit_before)"></span>
            </template>

            <template #cell(credit_after)="{ item }">
                <span v-text="intToMoney(item.credit_after)"></span>
            </template>

            <!-- สถานะ -->
            <template #cell(status)="{ item }">
            <span
                    class="badge"
                    :class="'bg-' + (item.status_color || 'secondary')"
                    v-text="item.status_display"
            ></span>
            </template>
        </b-table>
    </b-modal>

</div>