<b-modal ref="addedit" id="addedit" centered size="md" title="เติมเงิน" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">

        <b-form @submit.prevent="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">
            <input type="hidden" id="member_topup" :value="formaddedit.member_topup" required>
            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-banks"
                            label="User ID / Game ID:"
                            label-for="banks"
                            description="ระบุ User / Game ID ที่ต้องการ เติมเงินรายการนี้">
                        <b-input-group>
                            <b-form-input
                                    id="user_name"
                                    v-model="formaddedit.user_name"
                                    type="text"
                                    size="md"
                                    placeholder="User / Game ID"
                                    autocomplete="off"

                            ></b-form-input>
                            <b-input-group-append>
                                <b-button variant="success" @click="loadUser">ค้นหา</b-button>
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
                            description="">
                        <b-form-textarea
                                id="name"
                                v-model="formaddedit.name"
                                size="sm"
                                row="6"
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
                            id="input-group-remark"
                            label="หมายเหตุ:"
                            label-for="remark_admin"
                            description="">
                        <b-form-input
                                id="remark_admin"
                                v-model="formaddedit.remark_admin"
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

<b-modal ref="refill" id="refill" centered size="md" title="เติมเงิน" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">

        <b-form @submit.prevent="refillSubmit" v-if="show">
            <input type="hidden" id="id" :value="formrefill.id" required>
            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-banks"
                            label="User ID / Game ID:"
                            label-for="banks"
                            description="ระบุ User / Game ID ที่ต้องการ เติมเงินรายการนี้">
                        <b-input-group>
                            <b-form-input
                                    id="user_name"
                                    v-model="formrefill.user_name"
                                    type="text"
                                    size="md"
                                    placeholder="User / Game ID"
                                    autocomplete="off"

                            ></b-form-input>
                            <b-input-group-append>
                                <b-button variant="success" @click="loadUserRefill">ค้นหา</b-button>
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
                            description="">

                        <b-form-textarea
                                id="name"
                                v-model="formrefill.name"
                                size="sm"
                                row="6"
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
                            id="input-group-1"
                            label="จำนวนเงิน:"
                            label-for="amount"
                            description="ระบุจำนวนเงิน ระหว่าง 1 - 10,000">
                        <b-form-input
                                id="amount"
                                v-model="formrefill.amount"
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
                    <b-form-group id="input-group-2" label="ช่องทางที่ฝาก:" label-for="account_code">
                        <b-form-select
                                id="account_code"
                                v-model="formrefill.account_code"
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
                            id="input-group-remark"
                            label="หมายเหตุ:"
                            label-for="remark_admin"
                            description="">
                        <b-form-input
                                id="remark_admin"
                                v-model="formrefill.remark_admin"
                                type="text"
                                size="sm"
                                placeholder=""
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>

            {{--            <b-form-group--}}
            {{--                id="input-group-3"--}}
            {{--                label="รหัสยืนยัน:"--}}
            {{--                label-for="one_time_password"--}}
            {{--                description="รหัสยืนยันจาก Google Auth">--}}
            {{--                <b-form-input--}}
            {{--                    id="one_time_password"--}}
            {{--                    v-model="formrefill.one_time_password"--}}
            {{--                    type="number"--}}
            {{--                    placeholder="โปรดระบุ"--}}
            {{--                    size="sm"--}}
            {{--                    autocomplete="off"--}}

            {{--                ></b-form-input>--}}
            {{--            </b-form-group>--}}


            <b-button type="submit" variant="primary">บันทึก</b-button>

        </b-form>
    </b-container>
</b-modal>


<b-modal ref="clear" id="clear" centered size="md" title="โปรดระบุหมายเหตุ ในการทำรายการ" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-form @submit.stop.prevent="clearSubmit" v-if="show" id="frmclear" ref="frmclear">
        <b-form-group
                id="input-group-remark"
                label="หมายเหตุ:"
                label-for="remark"
                description="">
            <b-form-input
                    id="remark"
                    v-model="formclear.remark"
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




