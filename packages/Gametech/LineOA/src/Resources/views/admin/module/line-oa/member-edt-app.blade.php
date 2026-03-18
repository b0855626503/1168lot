<div id="member-edit-app">
    <b-modal
            ref="memberEditModal"
            id="line-oa-member-edit-modal"
            centered
            scrollable
            size="md"
            title="แก้ไขข้อมูลสมาชิก"
            :no-close-on-backdrop="true"
            :hide-footer="true"
            :lazy="true"
            @hidden="onMemberEditModalHidden"
    >
        <b-container class="bv-example-row">
            <b-form
                    @submit.prevent="memberEditSubmit"
                    v-if="memberEditShow"
                    id="member-edit-form"
                    ref="memberEditFormRef"
            >
                <input
                        type="hidden"
                        id="memberEdit_upline_code"
                        :value="memberEditForm.upline_code"
                >

                {{-- แถว: ผู้แนะนำ --}}
                <b-form-row>
                    <b-col>
                        <b-form-group
                                id="memberEdit-af-group"
                                label="เบอร์ ผู้แนะนำ:"
                                label-for="memberEdit-af"
                                description="ระบุ เบอร์ ผู้แนะนำ"
                        >
                            <b-form-input
                                    id="memberEdit-af"
                                    v-model="memberEditForm.af"
                                    type="text"
                                    size="sm"
                                    placeholder="เบอร์ ผู้แนะนำ"
                                    autocomplete="off"
                                    @input="memberEditLoadAF($event)"
                            ></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col>
                        <b-form-group
                                id="memberEdit-up_name-group"
                                label="ชื่อผู้แนะนำ:"
                                label-for="memberEdit-up_name"
                        >
                            <b-form-input
                                    id="memberEdit-up_name"
                                    v-model="memberEditForm.up_name"
                                    type="text"
                                    size="sm"
                                    autocomplete="off"
                                    readonly
                            ></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-form-row>

                {{-- แถว: ชื่อ / นามสกุล --}}
                <b-form-row>
                    <b-col>
                        <b-form-group
                                id="memberEdit-firstname-group"
                                label="ชื่อ:"
                                label-for="memberEdit-firstname"
                                description="ระบุ ชื่อ"
                        >
                            <b-form-input
                                    id="memberEdit-firstname"
                                    v-model="memberEditForm.firstname"
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
                                id="memberEdit-lastname-group"
                                label="นามสกุล:"
                                label-for="memberEdit-lastname"
                                description="ระบุ นามสกุล"
                        >
                            <b-form-input
                                    id="memberEdit-lastname"
                                    v-model="memberEditForm.lastname"
                                    type="text"
                                    size="sm"
                                    placeholder="นามสกุล"
                                    autocomplete="off"
                                    required
                            ></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-form-row>

                {{-- แถว: User / Password --}}
                <b-form-row>
                    <b-col>
                        <b-form-group
                                id="memberEdit-user_name-group"
                                label="User ID:"
                                label-for="memberEdit-user_name"
                                description="เปลี่ยนได้"
                        >
                            <b-form-input
                                    id="memberEdit-user_name"
                                    v-model="memberEditForm.user_name"
                                    type="text"
                                    size="sm"
                                    autocomplete="off"
                                    required
                            ></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col>
                        <b-form-group
                                id="memberEdit-user_pass-group"
                                label="รหัสผ่าน:"
                                label-for="memberEdit-user_pass"
                                description="ระบุ รหัสผ่าน กรณีต้องการเปลี่ยนเท่านั้น"
                        >
                            <b-form-input
                                    id="memberEdit-user_pass"
                                    v-model="memberEditForm.user_pass"
                                    type="text"
                                    size="sm"
                                    placeholder="รหัสผ่าน"
                                    autocomplete="off"
                            ></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-form-row>

                {{-- แถว: ธนาคาร / เลขบัญชี --}}
                <b-form-row>
                    <b-col>
                        <b-form-group
                                id="memberEdit-bank_code-group"
                                label="ธนาคาร:"
                                label-for="memberEdit-bank_code"
                        >
                            <b-form-select
                                    id="memberEdit-bank_code"
                                    v-model="memberEditForm.bank_code"
                                    :options="memberEditOption.bank_code"
                                    size="sm"
                                    required
                            ></b-form-select>
                        </b-form-group>
                    </b-col>
                    <b-col>
                        <b-form-group
                                id="memberEdit-acc_no-group"
                                label="เลขที่บัญชี:"
                                label-for="memberEdit-acc_no"
                                description="ระบบไม่ได้เชคซ้ำให้นะ"
                        >
                            <b-form-input
                                    id="memberEdit-acc_no"
                                    v-model="memberEditForm.acc_no"
                                    type="text"
                                    size="sm"
                                    autocomplete="off"
                                    required
                            ></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-form-row>

                {{-- แถว: Line ID / เบอร์โทร --}}
                <b-form-row>
                    <b-col>
                        <b-form-group
                                id="memberEdit-lineid-group"
                                label="Line ID:"
                                label-for="memberEdit-lineid"
                        >
                            <b-form-input
                                    id="memberEdit-lineid"
                                    v-model="memberEditForm.lineid"
                                    type="text"
                                    size="sm"
                                    autocomplete="off"
                            ></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col>
                        <b-form-group
                                id="memberEdit-tel-group"
                                label="เบอร์โทร:"
                                label-for="memberEdit-tel"
                                description="ระบบไม่ได้เชคซ้ำให้นะ"
                        >
                            <b-form-input
                                    id="memberEdit-tel"
                                    v-model="memberEditForm.tel"
                                    type="text"
                                    size="sm"
                                    autocomplete="off"
                                    required
                            ></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-form-row>

                {{-- แถว: Max withdraw / Refer --}}
                <b-form-row>
                    <b-col>
                        <b-form-group
                                id="memberEdit-maxwithdraw_day-group"
                                label="ยอดถอนสูงสุด / วัน:"
                                label-for="memberEdit-maxwithdraw_day"
                                description="ถ้าค่าเป็น 0 = ใช้ค่าถอนสูงสุด / วัน จาก ตั้งค่าพื้นฐานเวบไซต์"
                        >
                            <b-form-input
                                    id="memberEdit-maxwithdraw_day"
                                    v-model="memberEditForm.maxwithdraw_day"
                                    type="number"
                                    size="sm"
                                    autocomplete="off"
                            ></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col>
                        <b-form-group
                                id="memberEdit-refer_code-group"
                                label="Refer:"
                                label-for="memberEdit-refer_code"
                        >
                            <b-form-select
                                    id="memberEdit-refer_code"
                                    v-model="memberEditForm.refer_code"
                                    :options="memberEditOption.refer_code"
                                    size="sm"
                                    required
                            ></b-form-select>
                        </b-form-group>
                    </b-col>
                </b-form-row>

                {{-- แถว: Line ID (สำรอง) + รูป --}}
                <b-form-row>
                    <b-col>

                    </b-col>
                    <b-col>
                        <div class="d-flex align-items-center">
                            <img
                                    v-if="memberEditPic && memberEditPic.url"
                                    :src="memberEditPic.url"
                                    style="max-width:120px"
                            >
                            <small v-else class="text-muted">ยังไม่มีรูป</small>
                            <b-button
                                    class="ml-3"
                                    variant="primary"
                                    @click="memberEditOpenUpload"
                            >
                                อัปโหลด/เปลี่ยนรูป
                            </b-button>
                        </div>
                    </b-col>
                </b-form-row>

                <b-button type="submit" variant="primary">
                    บันทึก
                </b-button>
            </b-form>
        </b-container>
    </b-modal>

    <b-modal
            ref="memberEditUploadModal"
            title="อัปโหลดรูป"
            @shown="memberEditOnUploadShown"
            @hidden="memberEditOnUploadHidden"
            hide-footer
    >
        <div
                ref="memberEditDropzoneEl"
                class="dropzone border rounded p-4 text-center"
        >
            <div class="dz-message">
                ลากไฟล์มาวาง หรือคลิกเพื่อเลือกไฟล์
            </div>
            <div ref="memberEditDropzonePreviews" class="mt-3"></div>
        </div>
        <div class="text-center mt-2">
            <b-button ref="memberEditPickBtn" size="sm" variant="secondary">
                เลือกไฟล์
            </b-button>
        </div>
    </b-modal>
</div>