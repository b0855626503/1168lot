<b-modal ref="addedit" id="addedit" centered scrollable size="lg" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.prevent="addEditSubmit" v-if="show">

        {{-- REWARD TYPE --}}
        <b-form-group
                id="input-group-reward-type"
                label="ประเภทรางวัล:"
                label-for="reward_type">
            <b-form-select
                    id="reward_type"
                    name="reward_type"
                    v-model="formaddedit.reward_type"
                    :options="option.reward_type"
                    size="sm"
                    required
            ></b-form-select>
        </b-form-group>

        {{-- NAME --}}
        <b-form-group
                id="input-group-name"
                label="ชื่อรางวัล:"
                label-for="name">
            <b-form-input
                    id="name"
                    v-model="formaddedit.name"
                    type="text"
                    size="sm"
                    autocomplete="off"
                    required
            ></b-form-input>
        </b-form-group>

        {{-- DESCRIPTION --}}
        <b-form-group
                id="input-group-description"
                label="รายละเอียด (ไม่บังคับ):"
                label-for="description">
            <b-form-textarea
                    id="description"
                    v-model="formaddedit.description"
                    size="sm"
                    rows="3"
                    max-rows="6"
                    autocomplete="off"
            ></b-form-textarea>
        </b-form-group>

        {{-- IMAGE UPLOAD --}}
        <b-form-group
                id="input-group-image"
                label="รูปภาพรางวัล:"
                label-for="image">
            <b-form-file
                    id="image"
                    v-model="formaddedit.image_file"
                    accept="image/*"
                    browse-text="เลือกไฟล์"
                    placeholder="ยังไม่ได้เลือกรูป"
                    size="sm"
            ></b-form-file>

            {{-- preview --}}
            <div class="mt-2" v-if="imagePreview">
                <img :src="imagePreview"
                     alt="preview"
                     style="max-width: 180px; border-radius: 8px;"
                >
                <div class="mt-2">
                    <b-button size="sm" variant="outline-danger" @click.prevent="clearImage">
                        ล้างรูปที่เลือก
                    </b-button>
                </div>
            </div>

            {{-- กรณี edit แล้วมีรูปเดิม --}}
            <div class="mt-2" v-else-if="formaddedit.image">
                <img :src="formaddedit.image"
                     alt="current"
                     style="max-width: 180px; border-radius: 8px;"
                >
            </div>
        </b-form-group>

        {{-- FULFILLMENT MODE --}}
        <b-form-group
                id="input-group-fulfillment-mode"
                label="โหมดการจ่ายรางวัล:"
                label-for="fulfillment_mode"
                description="auto = เติมให้ทันที, manual = รอทีมงานติดต่อ/ทำรายการ, approval = รออนุมัติ">
            <b-form-select
                    id="fulfillment_mode"
                    name="fulfillment_mode"
                    v-model="formaddedit.fulfillment_mode"
                    :options="option.fulfillment_mode"
                    size="sm"
                    required
            ></b-form-select>
        </b-form-group>

{{--        --}}{{-- AUTO CLAIM / REQUIRE CONTACT --}}
{{--        <div class="row">--}}
{{--            <div class="col-md-6">--}}
{{--                <b-form-group--}}
{{--                        id="input-group-auto-claim"--}}
{{--                        label="รับรางวัลทันที:"--}}
{{--                        label-for="auto_claim"--}}
{{--                        description="เปิด = แลกแล้วรับทันที (เหมาะกับเครดิต/เพชร)">--}}
{{--                    <b-form-checkbox--}}
{{--                            id="auto_claim"--}}
{{--                            v-model="formaddedit.auto_claim"--}}
{{--                            name="auto_claim"--}}
{{--                            switch--}}
{{--                            size="lg"--}}
{{--                            :disabled="formaddedit.fulfillment_mode !== 'auto'"--}}
{{--                    >--}}
{{--                        Auto claim--}}
{{--                    </b-form-checkbox>--}}
{{--                </b-form-group>--}}
{{--            </div>--}}
{{--            <div class="col-md-6">--}}
{{--                <b-form-group--}}
{{--                        id="input-group-require-contact"--}}
{{--                        label="ต้องให้ทีมงานติดต่อ:"--}}
{{--                        label-for="require_staff_contact"--}}
{{--                        description="เหมาะกับของรางวัลภายนอก/ต้องนัดรับ">--}}
{{--                    <b-form-checkbox--}}
{{--                            id="require_staff_contact"--}}
{{--                            v-model="formaddedit.require_staff_contact"--}}
{{--                            name="require_staff_contact"--}}
{{--                            switch--}}
{{--                            size="lg"--}}
{{--                            :disabled="formaddedit.fulfillment_mode === 'auto'"--}}
{{--                    >--}}
{{--                        Require staff contact--}}
{{--                    </b-form-checkbox>--}}
{{--                </b-form-group>--}}
{{--            </div>--}}
{{--        </div>--}}

{{--        --}}{{-- POINT COST --}}
        <b-form-group
                id="input-group-point-cost"
                label="แต้มที่ใช้แลก:"
                label-for="point_cost">
            <b-form-input
                    id="point_cost"
                    v-model.number="formaddedit.point_cost"
                    type="number"
                    min="0"
                    step="1"
                    size="sm"
                    required
            ></b-form-input>
        </b-form-group>

        {{-- AMOUNTS --}}
        <div class="row">
            <div class="col-md-6">
                <b-form-group
                        id="input-group-credit-amount"
                        label="เครดิตที่ได้รับ (ถ้าเป็นเครดิต):"
                        label-for="credit_amount">
                    <b-form-input
                            id="credit_amount"
                            v-model="formaddedit.credit_amount"
                            type="number"
                            min="0"
                            step="0.01"
                            size="sm"
                            :disabled="formaddedit.reward_type !== 'wallet_credit'"
                    ></b-form-input>
                </b-form-group>
            </div>
            <div class="col-md-6">
                <b-form-group
                        id="input-group-gem-amount"
                        label="เพชรที่ได้รับ (ถ้าเป็นเพชร):"
                        label-for="gem_amount">
                    <b-form-input
                            id="gem_amount"
                            v-model.number="formaddedit.gem_amount"
                            type="number"
                            min="0"
                            step="1"
                            size="sm"
                            :disabled="formaddedit.reward_type !== 'wallet_gem'"
                    ></b-form-input>
                </b-form-group>
            </div>
        </div>

        <hr class="my-2">

        {{-- LIMITS (ใหม่) --}}
        <b-form-group
                id="input-group-limit-type"
                label="จำกัดการแลก:"
                label-for="limit_type"
                description="เลือกกติกา: ไม่จำกัด / จำกัดต่อรายการ / จำกัดตามช่วงเวลา (วัน/สัปดาห์/เดือน)">
            <b-form-select
                    id="limit_type"
                    name="limit_type"
                    v-model="formaddedit.limit_type"
                    :options="option.limit_type"
                    size="sm"
                    required
            ></b-form-select>
        </b-form-group>

        <div class="row" v-if="formaddedit.limit_type === 'per_reward'">
            <div class="col-md-6">
                <b-form-group
                        id="input-group-limit-per-user"
                        label="จำกัดกี่ครั้งต่อคน (ต่อรายการนี้):"
                        label-for="limit_per_user">
                    <b-form-input
                            id="limit_per_user"
                            v-model.number="formaddedit.limit_per_user"
                            type="number"
                            min="1"
                            step="1"
                            size="sm"
                            required
                    ></b-form-input>
                </b-form-group>
            </div>
            <div class="col-md-6">
                <b-form-group
                        id="input-group-cooldown-minutes"
                        label="Cooldown (นาที) กันกดรัว:"
                        label-for="cooldown_minutes"
                        description="ปล่อยว่าง = ไม่จำกัด">
                    <b-form-input
                            id="cooldown_minutes"
                            v-model.number="formaddedit.cooldown_minutes"
                            type="number"
                            min="0"
                            step="1"
                            size="sm"
                    ></b-form-input>
                </b-form-group>
            </div>
        </div>

        <div class="row" v-if="formaddedit.limit_type === 'per_period'">
            <div class="col-md-6">
                <b-form-group
                        id="input-group-limit-period"
                        label="ช่วงเวลา:"
                        label-for="limit_period">
                    <b-form-select
                            id="limit_period"
                            name="limit_period"
                            v-model="formaddedit.limit_period"
                            :options="option.limit_period"
                            size="sm"
                            required
                    ></b-form-select>
                </b-form-group>
            </div>

            <div class="col-md-6">
                <b-form-group
                        id="input-group-limit-per-period"
                        label="จำกัดกี่ครั้งต่อช่วงเวลา:"
                        label-for="limit_per_period">
                    <b-form-input
                            id="limit_per_period"
                            v-model.number="formaddedit.limit_per_period"
                            type="number"
                            min="1"
                            step="1"
                            size="sm"
                            required
                    ></b-form-input>
                </b-form-group>
            </div>

            <div class="col-md-12">
                <b-form-group
                        id="input-group-strict-limit"
                        label="โหมดเข้ม (Strict):"
                        label-for="strict_limit"
                        description="เปิด = บังคับตรวจ limit แบบเข้ม (เหมาะกับรางวัลฮิต/กันแข่งกด)">
                    <b-form-checkbox
                            id="strict_limit"
                            v-model="formaddedit.strict_limit"
                            name="strict_limit"
                            switch
                            size="lg"
                    >
                        Strict limit
                    </b-form-checkbox>
                </b-form-group>
            </div>
        </div>

        {{-- LIMIT TOTAL --}}
        <b-form-group
                id="input-group-limit-total"
                label="จำกัดจำนวนรวม (ทั้งระบบ):"
                label-for="limit_total"
                description="ปล่อยว่าง = ไม่จำกัดจำนวนรวม">
            <b-form-input
                    id="limit_total"
                    v-model.number="formaddedit.limit_total"
                    type="number"
                    min="0"
                    step="1"
                    size="sm"
            ></b-form-input>
        </b-form-group>

        <hr class="my-2">

        {{-- AVAILABILITY --}}
        <div class="row">
            <div class="col-md-6">
                <b-form-group
                        id="input-group-start-at"
                        label="เริ่มให้แลกได้ตั้งแต่:"
                        label-for="start_at"
                        description="ปล่อยว่างได้ = เริ่มได้ทันที">
                    <b-form-input
                            id="start_at"
                            v-model="formaddedit.start_at"
                            type="datetime-local"
                            size="sm"
                    ></b-form-input>
                </b-form-group>
            </div>
            <div class="col-md-6">
                <b-form-group
                        id="input-group-end-at"
                        label="สิ้นสุดการแลก:"
                        label-for="end_at"
                        description="ปล่อยว่างได้ = ไม่สิ้นสุด">
                    <b-form-input
                            id="end_at"
                            v-model="formaddedit.end_at"
                            type="datetime-local"
                            size="sm"
                    ></b-form-input>
                </b-form-group>
            </div>
        </div>

        {{-- TIMEZONE --}}
        <b-form-group
                id="input-group-timezone"
                label="Timezone:"
                label-for="timezone"
                description="แนะนำใช้ Asia/Bangkok (มาตรฐานระบบ)">
            <b-form-input
                    id="timezone"
                    v-model="formaddedit.timezone"
                    type="text"
                    size="sm"
                    autocomplete="off"
            ></b-form-input>
        </b-form-group>

        <hr class="my-2">

        {{-- STOCK --}}
        <b-form-group
                id="input-group-stock-unlimited"
                label="สต๊อก:"
                label-for="stock_unlimited">
            <b-form-checkbox
                    id="stock_unlimited"
                    v-model="formaddedit.stock_unlimited"
                    name="stock_unlimited"
                    switch
                    size="lg"
            >
                ไม่จำกัดสต๊อก
            </b-form-checkbox>
        </b-form-group>

        <b-form-group
                id="input-group-stock"
                label="จำนวนสต๊อก (ถ้าไม่จำกัด):"
                label-for="stock">
            <b-form-input
                    id="stock"
                    v-model.number="formaddedit.stock"
                    type="number"
                    min="0"
                    step="1"
                    size="sm"
                    :disabled="formaddedit.stock_unlimited"
            ></b-form-input>
        </b-form-group>

        <b-form-group
                id="input-group-auto-disable-oos"
                label="ตัดการใช้งานเมื่อสต๊อกหมด:"
                label-for="auto_disable_when_out_of_stock"
                description="ถ้าเปิดไว้ ระบบสามารถ disable/ซ่อนอัตโนมัติ (ตาม logic ฝั่ง backend)">
            <b-form-checkbox
                    id="auto_disable_when_out_of_stock"
                    v-model="formaddedit.auto_disable_when_out_of_stock"
                    name="auto_disable_when_out_of_stock"
                    switch
                    size="lg"
            >
                Auto disable when out of stock
            </b-form-checkbox>
        </b-form-group>

        <hr class="my-2">

        {{-- FLAGS --}}
        <div class="row">
            <div class="col-md-4">
                <b-form-group
                        id="input-group-featured"
                        label="แนะนำ:"
                        label-for="is_featured">
                    <b-form-checkbox
                            id="is_featured"
                            v-model="formaddedit.is_featured"
                            name="is_featured"
                            switch
                            size="lg"
                    >
                        แสดงเป็นรายการแนะนำ
                    </b-form-checkbox>
                </b-form-group>
            </div>
            <div class="col-md-4">
                <b-form-group
                        id="input-group-hidden"
                        label="การมองเห็น:"
                        label-for="is_hidden">
                    <b-form-checkbox
                            id="is_hidden"
                            v-model="formaddedit.is_hidden"
                            name="is_hidden"
                            switch
                            size="lg"
                    >
                        ซ่อนจากหน้าแลก
                    </b-form-checkbox>
                </b-form-group>
            </div>
            <div class="col-md-4">
                <b-form-group
                        id="input-group-priority"
                        label="ลำดับแสดงผล:"
                        label-for="priority">
                    <b-form-input
                            id="priority"
                            v-model.number="formaddedit.priority"
                            type="number"
                            step="1"
                            size="sm"
                    ></b-form-input>
                </b-form-group>
            </div>
        </div>

        {{-- ENABLED --}}
        <b-form-group
                id="input-group-enabled"
                label="สถานะการใช้งาน:"
                label-for="enabled">
            <b-form-checkbox
                    id="enabled"
                    v-model="formaddedit.enabled"
                    name="enabled"
                    switch
                    size="lg"
            >
                เปิดใช้งานรางวัลนี้ (Active)
            </b-form-checkbox>
        </b-form-group>

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
                    formmethod: 'add',
                    code: null,
                    formaddedit: this.defaultForm(),
                    option: {
                        reward_type: [
                            { value: 'wallet_credit', text: 'เครดิตในเว็บ' },
                            { value: 'wallet_gem',    text: 'เพชร/หน่วยพิเศษ' },
                            { value: 'external',      text: 'รางวัลภายนอก (ทีมงานติดต่อกลับ)' },
                        ],
                        fulfillment_mode: [
                            { value: 'auto',     text: 'Auto (เติมให้ทันที)' },
                            { value: 'manual',   text: 'Manual (รอทีมงานดำเนินการ)' },
                            { value: 'approval', text: 'Approval (รออนุมัติ)' },
                        ],
                        limit_type: [
                            { value: 'unlimited',  text: 'ไม่จำกัด' },
                            { value: 'per_reward', text: 'จำกัดต่อรายการ (ต่อคน)' },
                            { value: 'per_period', text: 'จำกัดตามช่วงเวลา' },
                        ],
                        limit_period: [
                            { value: 'day',   text: 'ต่อวัน' },
                            { value: 'week',  text: 'ต่อสัปดาห์' },
                            { value: 'month', text: 'ต่อเดือน' },
                        ],
                    },
                    _imagePreviewUrl: null, // internal กัน memory leak
                };
            },
            computed: {
                imagePreview() {
                    // สร้าง preview url เมื่อมี file
                    const f = this.formaddedit?.image_file;
                    if (!f) {
                        this.revokePreview();
                        return null;
                    }

                    if (this._imagePreviewUrl) return this._imagePreviewUrl;

                    this._imagePreviewUrl = URL.createObjectURL(f);
                    return this._imagePreviewUrl;
                }
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            beforeDestroy() {
                this.revokePreview();
            },
            watch: {
                'formaddedit.reward_type'(val) {
                    if (val === 'wallet_credit') {
                        this.formaddedit.gem_amount = null;
                    } else if (val === 'wallet_gem') {
                        this.formaddedit.credit_amount = null;
                    } else {
                        this.formaddedit.credit_amount = null;
                        this.formaddedit.gem_amount = null;
                    }

                    if (val === 'external') {
                        if (this.formaddedit.fulfillment_mode === 'auto') {
                            this.formaddedit.fulfillment_mode = 'manual';
                        }
                        this.formaddedit.require_staff_contact = true;
                        this.formaddedit.auto_claim = false;
                    }
                },
                'formaddedit.limit_type'(val) {
                    if (val === 'unlimited') {
                        this.formaddedit.limit_per_user = null;
                        this.formaddedit.limit_period = null;
                        this.formaddedit.limit_per_period = null;
                        this.formaddedit.strict_limit = false;
                    } else if (val === 'per_reward') {
                        this.formaddedit.limit_period = null;
                        this.formaddedit.limit_per_period = null;
                        this.formaddedit.strict_limit = false;
                        if (!this.formaddedit.limit_per_user) this.formaddedit.limit_per_user = 1;
                    } else if (val === 'per_period') {
                        this.formaddedit.limit_per_user = null;
                        if (!this.formaddedit.limit_period) this.formaddedit.limit_period = 'day';
                        if (!this.formaddedit.limit_per_period) this.formaddedit.limit_per_period = 1;
                    }
                },
                watch: {
                    'formaddedit.fulfillment_mode'(val) {
                        if (val === 'auto') {
                            this.formaddedit.auto_claim = true;
                            this.formaddedit.require_staff_contact = false;
                        }

                        if (val === 'manual') {
                            this.formaddedit.auto_claim = false;
                            this.formaddedit.require_staff_contact = true;
                        }

                        if (val === 'approval') {
                            this.formaddedit.auto_claim = false;
                            this.formaddedit.require_staff_contact = false;
                        }
                    }
                }
            },
            methods: {
                revokePreview() {
                    if (this._imagePreviewUrl) {
                        try { URL.revokeObjectURL(this._imagePreviewUrl); } catch (e) {}
                        this._imagePreviewUrl = null;
                    }
                },
                clearImage() {
                    this.formaddedit.image_file = null;
                    this.revokePreview();
                },
                defaultForm() {
                    return {
                        code: null,
                        name: '',
                        description: '',

                        reward_type: 'wallet_credit',
                        fulfillment_mode: 'auto',

                        auto_claim: true,
                        require_staff_contact: false,

                        point_cost: 0,

                        credit_amount: null,
                        gem_amount: null,

                        // limits
                        limit_type: 'unlimited',
                        limit_per_user: null,
                        limit_period: null,
                        limit_per_period: null,
                        strict_limit: false,

                        limit_total: null,
                        cooldown_minutes: null,

                        start_at: null,
                        end_at: null,
                        timezone: 'Asia/Bangkok',

                        stock_unlimited: true,
                        stock: null,
                        auto_disable_when_out_of_stock: true,

                        is_featured: false,
                        is_hidden: false,
                        priority: 0,

                        // image
                        image: null,        // url จาก backend
                        image_file: null,   // file จาก input

                        enabled: true,
                    };
                },

                editModal(code) {
                    this.code = null;
                    this.formaddedit = this.defaultForm();
                    this.formmethod = 'edit';
                    this.revokePreview();

                    this.show = false;
                    this.$nextTick(() => {
                        this.code = code;
                        this.loadData();
                        this.$refs.addedit.show();
                        this.show = true;
                    });
                },

                addModal() {
                    this.code = null;
                    this.formaddedit = this.defaultForm();
                    this.formmethod = 'add';
                    this.revokePreview();

                    this.show = false;
                    this.$nextTick(() => {
                        this.$refs.addedit.show();
                        this.show = true;
                    });
                },

                async loadData() {
                    const response = await axios.post(
                        "{{ route('admin.'.$menu->currentRoute.'.loaddata') }}",
                        { id: this.code }
                    );

                    const data = response.data.data || {};

                    this.formaddedit.code = data.code || null;
                    this.formaddedit.name = data.name || '';
                    this.formaddedit.description = data.description || '';

                    this.formaddedit.reward_type = data.reward_type || 'wallet_credit';
                    this.formaddedit.fulfillment_mode = data.fulfillment_mode || 'auto';

                    this.formaddedit.auto_claim = this.toBool(data.auto_claim);
                    this.formaddedit.require_staff_contact = this.toBool(data.require_staff_contact);

                    this.formaddedit.point_cost = parseInt(data.point_cost || 0, 10);

                    this.formaddedit.credit_amount = data.credit_amount ?? null;
                    this.formaddedit.gem_amount = data.gem_amount ?? null;

                    // limits ใหม่
                    this.formaddedit.limit_type = data.limit_type || 'unlimited';
                    this.formaddedit.limit_per_user = data.limit_per_user ?? null;
                    this.formaddedit.limit_period = data.limit_period ?? null;
                    this.formaddedit.limit_per_period = data.limit_per_period ?? null;
                    this.formaddedit.strict_limit = this.toBool(data.strict_limit);

                    // limits เดิม
                    this.formaddedit.limit_total = data.limit_total ?? null;
                    this.formaddedit.cooldown_minutes = data.cooldown_minutes ?? null;

                    // time
                    this.formaddedit.start_at = this.toDateTimeLocal(data.start_at);
                    this.formaddedit.end_at   = this.toDateTimeLocal(data.end_at);

                    this.formaddedit.timezone = data.timezone || 'Asia/Bangkok';

                    // stock
                    this.formaddedit.stock_unlimited = this.toBool(data.stock_unlimited);
                    this.formaddedit.stock = data.stock ?? null;
                    this.formaddedit.auto_disable_when_out_of_stock = this.toBool(data.auto_disable_when_out_of_stock);

                    // flags
                    this.formaddedit.is_featured = this.toBool(data.is_featured);
                    this.formaddedit.is_hidden   = this.toBool(data.is_hidden);
                    this.formaddedit.priority    = parseInt(data.priority || 0, 10);

                    // image url (สำคัญ: เพื่อโชว์รูปเดิมตอน edit)
                    this.formaddedit.image = data.image || null;
                    this.formaddedit.image_file = null; // กันค้างไฟล์เก่า
                    this.revokePreview();

                    // status -> enabled
                    const status = (data.status || '').toString().trim().toLowerCase();
                    this.formaddedit.enabled = (status === 'active' || status === 'y' || status === '1');
                },

                toBool(v) {
                    if (v === true || v === 1 || v === '1' || v === 'Y') return true;
                    const s = (v ?? '').toString().trim().toLowerCase();
                    return ['true', 'yes', 'y', 'on'].includes(s);
                },

                toDateTimeLocal(v) {
                    if (!v) return null;
                    const s = v.toString().trim();
                    if (s.includes('T')) return s.substring(0, 16);
                    return s.replace(' ', 'T').substring(0, 16);
                },

                generateRewardCode(rewardType, name) {
                    const type = (rewardType || 'wallet_credit')
                        .replace('wallet_', '')
                        .replace(/[^a-z0-9_]/gi, '')
                        .toLowerCase();

                    let base = (name || '').toString().trim();
                    base = base.replace(/\s+/g, '_');
                    base = base.replace(/[^ก-๙a-zA-Z0-9_]/g, '');
                    base = base.replace(/_+/g, '_').replace(/^_+|_+$/g, '');
                    if (!base) base = 'reward';
                    base = base.substring(0, 24);

                    const ts = Date.now();
                    return `RW_${type}_${base}_${ts}`;
                },

                validateBusiness() {
                    const type = this.formaddedit.reward_type;

                    if (type === 'wallet_credit') {
                        const v = Number(this.formaddedit.credit_amount ?? 0);
                        if (!v || v <= 0) return 'กรุณากรอก "เครดิตที่ได้รับ" ให้มากกว่า 0';
                    }

                    if (type === 'wallet_gem') {
                        const v = Number(this.formaddedit.gem_amount ?? 0);
                        if (!v || v <= 0) return 'กรุณากรอก "เพชรที่ได้รับ" ให้มากกว่า 0';
                    }

                    if (type === 'external') {
                        if (this.formaddedit.fulfillment_mode === 'auto') return 'รางวัลภายนอกไม่ควรเป็นโหมด Auto';
                        if (!this.formaddedit.require_staff_contact) return 'รางวัลภายนอกควรเปิด "ต้องให้ทีมงานติดต่อ"';
                    }

                    return null;
                },

                addEditSubmit(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    const url = (this.formmethod === 'add')
                        ? "{{ route('admin.'.$menu->currentRoute.'.create') }}"
                        : "{{ route('admin.'.$menu->currentRoute.'.update') }}";

                    const name = (this.formaddedit.name || '').trim();
                    if (!name) {
                        this.toggleButtonDisable(false);
                        this.$bvModal.msgBoxOk('กรุณากรอกชื่อรางวัล', {
                            title: 'ข้อมูลไม่ครบ',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'warning',
                            centered: true,
                        });
                        return;
                    }

                    const bizErr = this.validateBusiness();
                    if (bizErr) {
                        this.toggleButtonDisable(false);
                        this.$bvModal.msgBoxOk(bizErr, {
                            title: 'ข้อมูลไม่ถูกต้อง',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'warning',
                            centered: true,
                        });
                        return;
                    }

                    // gen code ตอน add หรือ code ว่าง
                    let code = (this.formaddedit.code || '').trim();
                    if (this.formmethod === 'add' || !code) {
                        code = this.generateRewardCode(this.formaddedit.reward_type, name);
                    }

                    // map enabled -> status
                    const status = this.formaddedit.enabled ? 'active' : 'inactive';

                    // normalize amounts
                    let credit_amount = this.formaddedit.credit_amount;
                    let gem_amount = this.formaddedit.gem_amount;

                    if (this.formaddedit.reward_type === 'wallet_credit') {
                        gem_amount = null;
                        if (credit_amount === '' || credit_amount === undefined) credit_amount = null;
                    } else if (this.formaddedit.reward_type === 'wallet_gem') {
                        credit_amount = null;
                        if (gem_amount === '' || gem_amount === undefined) gem_amount = null;
                    } else {
                        credit_amount = null;
                        gem_amount = null;
                    }

                    // stock
                    const stock_unlimited = this.formaddedit.stock_unlimited ? 1 : 0;
                    const stock = this.formaddedit.stock_unlimited ? null : this.formaddedit.stock;

                    // limits
                    let limit_type = (this.formaddedit.limit_type || 'unlimited').trim();
                    let limit_per_user = this.formaddedit.limit_per_user;
                    let limit_period = this.formaddedit.limit_period;
                    let limit_per_period = this.formaddedit.limit_per_period;
                    let strict_limit = this.formaddedit.strict_limit ? 1 : 0;

                    if (limit_type === 'unlimited') {
                        limit_per_user = null;
                        limit_period = null;
                        limit_per_period = null;
                        strict_limit = 0;
                    } else if (limit_type === 'per_reward') {
                        limit_period = null;
                        limit_per_period = null;
                        strict_limit = 0;
                        if (!limit_per_user || Number(limit_per_user) < 1) limit_per_user = 1;
                    } else if (limit_type === 'per_period') {
                        limit_per_user = null;
                        if (!limit_period) limit_period = 'day';
                        if (!limit_per_period || Number(limit_per_period) < 1) limit_per_period = 1;
                    }

                    let limit_total = this.formaddedit.limit_total;
                    if (limit_total === '' || limit_total === undefined) limit_total = null;

                    let cooldown_minutes = this.formaddedit.cooldown_minutes;
                    if (cooldown_minutes === '' || cooldown_minutes === undefined) cooldown_minutes = null;

                    // ===== IMPORTANT: ส่งแบบ multipart เพื่ออัปโหลดรูป =====
                    const fd = new FormData();

                    // id สำหรับ update
                    if (this.formmethod === 'edit' && this.code) {
                        fd.append('id', this.code);
                    }

                    // data payload (เดิม)
                    const payload = Object.assign({}, this.formaddedit, {
                        code: code,
                        status: status,

                        credit_amount: credit_amount,
                        gem_amount: gem_amount,

                        stock_unlimited: stock_unlimited,
                        stock: stock,

                        limit_type: limit_type,
                        limit_per_user: limit_per_user,
                        limit_period: limit_period,
                        limit_per_period: limit_per_period,
                        strict_limit: strict_limit,

                        limit_total: limit_total,
                        cooldown_minutes: cooldown_minutes,

                        auto_claim: this.formaddedit.auto_claim ? 1 : 0,
                        require_staff_contact: this.formaddedit.require_staff_contact ? 1 : 0,
                        auto_disable_when_out_of_stock: this.formaddedit.auto_disable_when_out_of_stock ? 1 : 0,

                        is_featured: this.formaddedit.is_featured ? 1 : 0,
                        is_hidden: this.formaddedit.is_hidden ? 1 : 0,
                    });

                    Object.keys(payload).forEach(k => {
                        // image_file เป็นไฟล์ ส่งแยก
                        if (k === 'image_file') return;

                        const v = payload[k];
                        if (v === undefined) return;

                        // หมายเหตุ: null ให้ส่งเป็นค่าว่างเพื่อให้ backend เคลียร์ได้
                        fd.append(`data[${k}]`, (v === null) ? '' : v);
                    });

                    if (this.formaddedit.image_file) {
                        fd.append('image', this.formaddedit.image_file);
                    }

                    axios.post(url, fd, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    })
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
                            console.log('error', exception);
                            this.toggleButtonDisable(false);
                        });
                }
            },
        });
    </script>
@endpush
