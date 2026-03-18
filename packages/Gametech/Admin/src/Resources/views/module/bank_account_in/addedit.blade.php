<b-modal ref="addedit" id="addedit" centered size="md" title="{{ $menu->currentName }}" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true" :lazy="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent="addEditSubmitNew" v-if="show" id="frmaddedit" ref="frmaddedit">

            <b-form-row>
                <b-col>
                    <b-form-group label="วันที่">
                        <b-form-datepicker
                                v-model="formaddedit.startdate"
                                locale="th-TH"
                                :date-format-options="{ year: 'numeric', month: '2-digit', day: '2-digit' }"
                                :min="minDate"
                                :max="maxDate"
                                required
                        />
                    </b-form-group>

                    <b-form-group label="เวลา">
                        <b-form-timepicker
                                v-model="formaddedit.starttime"
                                locale="th-TH"
                                :hour12="false"
                                show-seconds="false"
                                required
                        />
                    </b-form-group>

                    <b-form-group label="สรุป">
                        <b-form-input :value="dateStartDisplay" readonly/>
                    </b-form-group>
                </b-col>

                <b-col>
                    <b-form-group label="วันที่ สิ้นสุด">
                        <b-form-datepicker
                                v-model="formaddedit.enddate"
                                locale="th-TH"
                                :date-format-options="{ year: 'numeric', month: '2-digit', day: '2-digit' }"
                                :min="formaddedit.startdate || minDate"
                                :max="maxDate"
                                required
                        />
                    </b-form-group>

                    <b-form-group label="เวลา">
                        <b-form-timepicker
                                v-model="formaddedit.endtime"
                                locale="th-TH"
                                :hour12="false"
                                show-seconds="false"
                                required
                        />
                    </b-form-group>

                    <b-form-group label="สรุป">
                        <b-form-input :value="dateEndDisplay" readonly/>
                    </b-form-group>
                </b-col>
            </b-form-row>


            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-bonus"
                            label="โบนัสฝาก (%):"
                            label-for="bonus"
                            description="ระบุ โบนัส ที่ต้องการ ใส่มากกว่า 0 ถึงทำงาน">
                        <b-form-input
                                id="bonus"
                                v-model="formaddedit.bonus"
                                type="text"
                                size="sm"
                                placeholder="ระบุโบนัส"
                                autocomplete="off"

                        ></b-form-input>
                    </b-form-group>

                </b-col>
                <b-col>

                    <b-form-group
                            id="input-group-bonus_max"
                            label="โบนัสสูงสุด (บาท):"
                            label-for="bonus_max"
                            description="">
                        <b-form-input
                                id="bonus_max"
                                v-model="formaddedit.bonus_max"
                                type="text"
                                size="sm"
                                placeholder="ระบุโบนัส"
                                autocomplete="off"

                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>


            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-banks"
                            label="ธนาคาร:"
                            label-for="banks"
                            description="">

                        <b-form-select
                                id="banks"
                                v-model="formaddedit.banks"
                                :options="option.banks"
                                size="sm"
                                required
                        ></b-form-select>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                            id="input-group-deposit_min"
                            label="ยอดฝากขั้นต่ำ:"
                            label-for="deposit_min"
                            description="ระบุ ยอดฝากขั้นต่ำ">
                        <b-form-input
                                id="deposit_min"
                                v-model="formaddedit.deposit_min"
                                type="text"
                                size="sm"
                                placeholder="ยอดฝากขั้นต่ำ"
                                autocomplete="off"

                        ></b-form-input>
                    </b-form-group>
                </b-col>

            </b-form-row>


            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-1"
                            label="ชื่อบัญชี:"
                            label-for="acc_name"
                            description="ระบุ ชื่อบัญชี">
                        <b-form-input
                                id="acc_name"
                                v-model="formaddedit.acc_name"
                                type="text"
                                size="sm"
                                placeholder="ชื่อบัญชี"
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                            id="input-group-2"
                            label="เลขที่บัญชี:"
                            label-for="acc_no"
                            description="ระบุ เลขที่บัญชี">
                        <b-form-input
                                id="acc_no"
                                v-model="formaddedit.acc_no"
                                type="text"
                                size="sm"
                                placeholder="เลขที่บัญชี"
                                autocomplete="off"
                                required

                        ></b-form-input>
                    </b-form-group>
                </b-col>

            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-3"
                            label="User Name:"
                            label-for="user_name"
                            description="User เข้าเวบ Kbiz">
                        <b-form-input
                                id="user_name"
                                v-model="formaddedit.user_name"
                                type="text"
                                size="sm"
                                placeholder="ถ้าเป็นการดึงแบบ api ไม่ต้องใส่"
                                autocomplete="off"

                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col>
                    <b-form-group
                            id="input-group-3"
                            label="Password:"
                            label-for="user_pass"
                            description="">
                        <b-form-input
                                id="user_pass"
                                v-model="formaddedit.user_pass"
                                type="text"
                                size="sm"
                                placeholder="ถ้าเป็นการดึงแบบ api ไม่ต้องใส่"
                                autocomplete="off"

                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>

            <b-form-row>
                <b-col>
                    <b-form-group
                            id="input-group-3"
                            label="ลำดับการแสดงผล:"
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

                <b-col>

                    {{--                    <b-form-checkbox--}}
                    {{--                        id="local"--}}
                    {{--                        v-model="formaddedit.local"--}}
                    {{--                        value="Y"--}}
                    {{--                        unchecked-value="N">--}}
                    {{--                        เปิดใช้ระบบดึงภายใน<br>--}}
                    {{--                        <small>(ถ้าทำเป็น Api ให้เอาถูกออก)<br>--}}
                    {{--                        ส่วนกรุงศรี ตอนนี้ใช้ได้แต่ Api<br> (ให้ใส่ถูกไว้ไม่ต้องเอาออก)</small>--}}
                    {{--                    </b-form-checkbox>--}}

                    <b-form-checkbox
                            id="webhook"
                            name="webhook"
                            v-model="formaddedit.webhook"
                            value="Y"
                            unchecked-value="N">
                        เปิดใช้งาน Webhook
                    </b-form-checkbox>

                    <b-form-checkbox
                            id="qrcode"
                            name="qrcode"
                            v-model="formaddedit.qrcode"
                            value="Y"
                            unchecked-value="N">
                        เปิดใช้งาน QR Code (รูปภาพ)
                    </b-form-checkbox>

                </b-col>
            </b-form-row>

            <b-form-row>

                <b-col>
                    <b-form-group
                            id="input-group-3"
                            label="รหัสยืนยัน:"
                            label-for="one_time_password"
                            description="รหัสยืนยันจาก Google Auth">
                        <b-form-input
                                id="one_time_password"
                                v-model="formaddedit.one_time_password"
                                type="number"
                                placeholder="โปรดระบุ"
                                size="sm"
                                autocomplete="off"
                                required
                        ></b-form-input>
                    </b-form-group>
                </b-col>

                <b-col>
                    <div class="form-group {!! $errors->has('filepic.*') ? 'has-error' : '' !!}">
                        <label>รูปภาพ QR Code</label>
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

            </b-form-row>
            <b-form-row>

                <b-col>
                    <b-form-group
                            id="input-group-visibility_scope"
                            label="กลุ่มลูกค้า:"
                            label-for="visibility_scope"
                            description="">
                        <b-form-select
                                id="visibility_scope"
                                v-model="formaddedit.visibility_scope"
                                :options="option.visibility_scope"
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
                            label="หมายเหตุเพิ่มเติม:"
                            label-for="remark"
                            description="">
                        <b-form-input
                                id="remark"
                                v-model="formaddedit.remark"
                                type="text"
                                placeholder="โปรดระบุ"
                                size="sm"
                                autocomplete="off"
                        ></b-form-input>
                    </b-form-group>
                </b-col>

            </b-form-row>

            {{--            <b-form-row>--}}
            {{--                <b-col>--}}
            {{--                    <div class="form-group {!! $errors->has('filepic.*') ? 'has-error' : '' !!}">--}}
            {{--                        <label>รูปภาพ QR Code</label>--}}
            {{--                        <image-wrapper--}}
            {{--                                @clear="clearImage"--}}
            {{--                                @upload="handleUpload($event)"--}}
            {{--                                button-label="เพิ่มรูปภาพ"--}}
            {{--                                :removed="true"--}}
            {{--                                input-name="filepic"--}}
            {{--                                :multiple="false"--}}
            {{--                                :images="formaddedit.filepic"--}}
            {{--                                :imgpath="imgpath"--}}
            {{--                                v-bind:testProp.sync="trigger"></image-wrapper>--}}
            {{--                    </div>--}}
            {{--                </b-col>--}}

            {{--            </b-form-row>--}}

            {{--            <hr>--}}
            {{--            <p>สำหรับ ลูกค้าที่ใช้ทรู แบบ OTP ที่เวบ 168gametech และ เวบอื่นที่ รูปแบบคล้าย 168gametech</p>--}}
            {{--            <b-form-row>--}}
            {{--                <b-col>--}}
            {{--                    <b-form-group--}}
            {{--                        id="input-group-4"--}}
            {{--                        label="website:"--}}
            {{--                        label-for="website"--}}
            {{--                        description="สำหรับ TrueWallet ให้ใส่">--}}
            {{--                        <b-form-input--}}
            {{--                            id="website"--}}
            {{--                            v-model="formaddedit.website"--}}
            {{--                            type="text"--}}
            {{--                            size="sm"--}}
            {{--                            placeholder="ไม่ต้อง copy dashboard.php ติดมาใส่นะครับ"--}}
            {{--                            autocomplete="off"--}}
            {{--                        ></b-form-input>--}}
            {{--                    </b-form-group>--}}
            {{--                </b-col>--}}

            {{--                <b-col>--}}
            {{--                    <b-form-group--}}
            {{--                        id="input-group-pattern"--}}
            {{--                        label="รูปแบบ:"--}}
            {{--                        label-for="pattern"--}}
            {{--                        description="รูปแบบการดึง มีผลแค่ TrueWallet">--}}

            {{--                        <b-form-select--}}
            {{--                            id="pattern"--}}
            {{--                            name="pattern"--}}
            {{--                            v-model="formaddedit.pattern"--}}
            {{--                            :options="option.pattern"--}}
            {{--                            size="sm"--}}
            {{--                        ></b-form-select>--}}
            {{--                    </b-form-group>--}}
            {{--                </b-col>--}}
            {{--            </b-form-row>--}}


            <b-button type="submit" variant="primary">บันทึก</b-button>

        </b-form>
    </b-container>
</b-modal>
@php
    // เปิด/ปิด LINE Connect ต่อเว็บได้ผ่าน .env ของเว็บนั้น (ไม่กระทบเว็บอื่น)
    // ตั้งค่า: LINE_CONNECT_ENABLED=true|false
    $useLineConnect = in_array(strtolower((string) env('LINE_CONNECT_ENABLED', 'true')), ['1','true','yes','y'], true);
@endphp

@includeWhen($useLineConnect, 'admin::module.'.$menu->currentRoute.'._line_connect')

@push('scripts')
    <script>
        $(document).ready(function () {
            $("body").tooltip({
                selector: '[data-toggle="tooltip"]',
                container: 'body'
            });
        });
    </script>
    <script>
        // LINE CONNECT stubs (กรณีปิด feature ต่อเว็บ) — ถ้าเปิดใช้งานจริง จะถูก override โดย partial _line_connect
        window.lineConnectModal = window.lineConnectModal || function () {
            console && console.warn && console.warn('LINE CONNECT is disabled on this site.');
        };
        window.lineConnentModal = window.lineConnentModal || function (id) { return window.lineConnectModal(id); };
        // บางจุดเรียกแบบ function ธรรมดา
        if (typeof lineConnectModal === 'undefined') {
            var lineConnectModal = function (id) { return window.lineConnectModal(id); };
        }
        if (typeof lineConnentModal === 'undefined') {
            var lineConnentModal = function (id) { return window.lineConnentModal(id); };
        }
    </script>

    <script type="module">
        window.app = new Vue({
            el: '#app',
            mixins: (window.LineConnectAdminMixin ? [window.LineConnectAdminMixin] : []),

            data() {
                return {
                    show: false,
                    trigger: 0,
                    fileupload: '',
                    formmethod: 'edit',

                    minDate: null,
                    maxDate: null,

                    formaddedit: {
                        // ชุดวันเวลาเริ่ม/สิ้นสุด
                        startdate: null,   // "YYYY-MM-DD"
                        starttime: null,   // "HH:mm" หรือ "HH:mm:ss"
                        enddate: null,
                        endtime: null,
                        bonus_max: 0,
                        bonus: 0,
                        acc_name: '',
                        acc_no: '',
                        banks: '',
                        user_name: '',
                        user_pass: '',
                        smestatus: 'Y',
                        local: 'Y',
                        sort: 1,
                        deposit_min: 0,
                        website: '',
                        webhook: 'N',
                        pattern: '',
                        one_time_password: '',
                        visibility_scope: '',
                        qrcode: '',
                        filepic: '',
                        remark: '',
                    },
                    option: {
                        banks: [],
                        pattern: [
                            {value: 'G', text: 'GAMETECH'},
                            {value: 'O', text: 'OTHER'}
                        ],
                        visibility_scope: [
                            {value: 'null', text: 'ไม่ระบุ'},
                            {value: 'all', text: 'ทั้งหมด'},
                            {value: 'legacy', text: 'ลูกค้าเก่า'},
                            {value: 'new', text: 'ลูกค้าใหม่'},
                        ]
                    },
                    imgpath: '/storage/bank_qr/'
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            mounted() {
                this.loadBank();


                // ตั้ง minDate = วันนี้, maxDate = วันนี้ + 1 ปี (ปรับได้)
                const pad = n => String(n).padStart(2, '0');
                const now = new Date();
                const y = now.getFullYear(), m = pad(now.getMonth() + 1), d = pad(now.getDate());
                this.minDate = `${y}-${m}-${d}`;

                const nextYear = new Date(now);
                nextYear.setFullYear(now.getFullYear() + 1);
                this.maxDate = `${nextYear.getFullYear()}-${pad(nextYear.getMonth() + 1)}-${pad(nextYear.getDate())}`;

                // ค่าเริ่มต้น: ตอนนี้ และ +1 ชั่วโมงเป็นสิ้นสุด
                if (!this.formaddedit.startdate) {
                    this.formaddedit.startdate = this.minDate;
                    this.formaddedit.starttime = `${pad(now.getHours())}:${pad(now.getMinutes())}`;
                    const end = new Date(now.getTime() + 60 * 60 * 1000);
                    this.formaddedit.enddate = `${end.getFullYear()}-${pad(end.getMonth() + 1)}-${pad(end.getDate())}`;
                    this.formaddedit.endtime = `${pad(end.getHours())}:${pad(end.getMinutes())}`;
                }
            },
            computed: {
                // สร้าง Date object (local) จากชุด start
                startDateObj() {
                    const d = this.formaddedit.startdate, t = this.formaddedit.starttime;
                    if (!d || !t) return null;
                    const [Y, M, D] = d.split('-').map(Number);
                    const [h, m, s_] = (t.length > 5 ? t : (t + ':00')).split(':').map(Number);
                    return new Date(Y, (M - 1), D, h || 0, m || 0, s_ || 0);
                },
                // ชุด end
                endDateObj() {
                    const d = this.formaddedit.enddate, t = this.formaddedit.endtime;
                    if (!d || !t) return null;
                    const [Y, M, D] = d.split('-').map(Number);
                    const [h, m, s_] = (t.length > 5 ? t : (t + ':00')).split(':').map(Number);
                    return new Date(Y, (M - 1), D, h || 0, m || 0, s_ || 0);
                },

                // สรุปโชว์ในอินพุต readonly
                dateStartDisplay(){
                    const dt = this.makeLocalDate(this.formaddedit.startdate, this.formaddedit.starttime);
                    return dt ? dt.toLocaleString('th-TH', { dateStyle:'medium', timeStyle:'short' }) : '';
                },
                dateEndDisplay(){
                    const dt = this.makeLocalDate(this.formaddedit.enddate, this.formaddedit.endtime);
                    return dt ? dt.toLocaleString('th-TH', { dateStyle:'medium', timeStyle:'short' }) : '';
                },
                // แปลงเป็น ISO/ MySQL (เผื่อส่งแบ็กเอนด์)
                startISO() {
                    return this.startDateObj ? this.startDateObj.toISOString() : null;
                },
                endISO() {
                    return this.endDateObj ? this.endDateObj.toISOString() : null;
                },

                startMySQL() {
                    if (!this.startDateObj) return null;
                    const d = this.startDateObj, pad = n => String(n).padStart(2, '0');
                    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
                },
                endMySQL() {
                    if (!this.endDateObj) return null;
                    const d = this.endDateObj, pad = n => String(n).padStart(2, '0');
                    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
                },
            },


            methods: {

                normalizeDate(v){
                    if (!v) return null;
                    if (v instanceof Date && !isNaN(v)) {
                        const pad=n=>String(n).padStart(2,'0');
                        return `${v.getFullYear()}-${pad(v.getMonth()+1)}-${pad(v.getDate())}`;
                    }
                    // รองรับ "YYYY-MM-DD ..." (MySQL หรือ ISO)
                    const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})/);
                    return m ? `${m[1]}-${m[2]}-${m[3]}` : null;
                },

                // คืน 'HH:mm:ss' หรือ 'HH:mm' หรือ null
                normalizeTime(v){
                    if (!v) return null;
                    const s = String(v).trim();
                    // รูปแบบ HH:mm[:ss]
                    let m = s.match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/);
                    if (m) {
                        const pad=n=>String(n).padStart(2,'0');
                        return `${pad(+m[1])}:${m[2]}${m[3] ? ':'+m[3] : ''}`;
                    }
                    // ดึงเวลาจาก "YYYY-MM-DD[ T]HH:mm:ss"
                    m = s.match(/(?:T| )(\d{2}):(\d{2})(?::(\d{2}))?/);
                    if (m) return `${m[1]}:${m[2]}${m[3] ? ':'+m[3] : ''}`;
                    return null;
                },

                // ประกอบเป็น Date (local) จาก date+time ที่ normalize แล้ว
                makeLocalDate(dStr, tStr){
                    if (!dStr) return null;
                    const [Y,M,D] = dStr.split('-').map(Number);
                    let hh=0, mm=0, ss=0;
                    if (tStr) {
                        const parts = tStr.split(':').map(Number);
                        hh = parts[0]||0; mm = parts[1]||0; ss = parts[2]||0;
                    }
                    const dt = new Date(Y, (M-1), D, hh, mm, ss);
                    return isNaN(dt.getTime()) ? null : dt;
                },
            
                clearImage() {
                    this.trigger++;
                    this.formaddedit.filepic = '';
                },
                handleUpload(value) {
                    this.fileupload = value;
                },
                setImage(value) {
                    // this.trigger++;
                    this.formaddedit.filepic = value;
                    console.log('Set :' + this.formaddedit.filepic);
                },
                editModal(code) {
                    this.code = null;
                    this.formaddedit = {
                        acc_name: '',
                        acc_no: '',
                        banks: '',
                        user_name: '',
                        user_pass: '',
                        smestatus: 'Y',
                        local: 'Y',
                        sort: 1,
                        deposit_min: 0,
                        website: '',
                        webhook: 'Y',
                        pattern: '',
                        one_time_password: '',
                        visibility_scope: '',
                        remark: '',
                        qrcode: ''

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
                        acc_name: '',
                        acc_no: '',
                        banks: '',
                        user_name: '',
                        user_pass: '',
                        smestatus: 'Y',
                        local: 'Y',
                        webhook: 'N',
                        sort: 1,
                        deposit_min: 0,
                        website: '',
                        pattern: 'G',
                        one_time_password: '',
                        visibility_scope: '',
                        remark: '',
                        qrcode: '',
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
                        acc_name: response.data.data.acc_name,
                        acc_no: response.data.data.acc_no,
                        banks: response.data.data.banks,
                        user_name: response.data.data.user_name,
                        user_pass: response.data.data.user_pass,
                        smestatus: response.data.data.smestatus,
                        local: response.data.data.local,
                        sort: response.data.data.sort,
                        website: response.data.data.website,
                        webhook: response.data.data.webhook,
                        pattern: response.data.data.pattern,
                        startdate: response.data.data.date_start,
                        starttime: response.data.data.time_start,
                        enddate: response.data.data.date_end,
                        endtime: response.data.data.time_end,
                        bonus: response.data.data.bonus,
                        bonus_max: response.data.data.bonus_max,
                        qrcode: response.data.data.qrcode,
                        remark: response.data.data.remark,
                        deposit_min: response.data.data.deposit_min,
                        visibility_scope: response.data.data.visibility_scope,

                    };

                    if (response.data.data.filepic) {
                        console.log('มีรูป');
                        console.log(response.data.data.filepic);
                        this.trigger++;
                        this.formaddedit.filepic = response.data.data.filepic;
                    } else {
                        console.log('ไม่มีมีรูป');
                        console.log(response.data.data.filepic);
                        this.formaddedit.filepic = '';
                    }

                },

                // กันผิดพลาด: end ต้องไม่ก่อน start
                ensureValidRange() {
                    if (!this.startDateObj || !this.endDateObj) return false;
                    if (this.endDateObj.getTime() < this.startDateObj.getTime()) {
                        this.$bvToast && this.$bvToast.toast('เวลาสิ้นสุดต้องไม่น้อยกว่าเวลาเริ่ม', {variant: 'danger'});
                        return false;
                    }
                    return true;
                },

                addEditSubmitNew(event) {
                    event.preventDefault();
                    this.toggleButtonDisable && this.toggleButtonDisable(true);

                    // if (!this.ensureValidRange()) {
                    //     this.toggleButtonDisable && this.toggleButtonDisable(false);
                    //     return;
                    // }

                    const url = (this.formmethod === 'add')
                        ? "{{ route('admin.'.$menu->currentRoute.'.create') }}"
                        : "{{ route('admin.'.$menu->currentRoute.'.update') }}/" + this.code;

                    const payload = {
                        // วันเวลาแบบแยก (ถ้าหลังบ้านอยากเก็บแยกฟิลด์)
                        date_start: this.formaddedit.startdate,
                        time_start: this.formaddedit.starttime,
                        date_end: this.formaddedit.enddate,
                        time_end: this.formaddedit.endtime,

                        // วันเวลาแบบรวม (สะดวกเก็บคอลัมน์เดียว)
                        // start_at: this.startMySQL,   // "YYYY-MM-DD HH:mm:ss"
                        // end_at: this.endMySQL,

                        // …ฟิลด์เดิม…
                        acc_name: this.formaddedit.acc_name,
                        bonus: this.formaddedit.bonus,
                        bonus_max: this.formaddedit.bonus_max,
                        acc_no: this.formaddedit.acc_no,
                        banks: this.formaddedit.banks,
                        user_name: this.formaddedit.user_name,
                        user_pass: this.formaddedit.user_pass,
                        smestatus: this.formaddedit.smestatus,
                        local: this.formaddedit.local,
                        sort: this.formaddedit.sort,
                        website: this.formaddedit.website,
                        deposit_min: this.formaddedit.deposit_min,
                        webhook: this.formaddedit.webhook,
                        pattern: this.formaddedit.pattern,
                        one_time_password: this.formaddedit.one_time_password,
                        qrcode: this.formaddedit.qrcode,
                        remark: this.formaddedit.remark,
                        filepic: this.formaddedit.filepic,
                        visibility_scope: this.formaddedit.visibility_scope,
                    };

                    const formData = new FormData();
                    formData.append('data', JSON.stringify(payload));
                    formData.append('fileupload', this.fileupload);

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
                                $.each(response.data.message, (index) => {
                                    const el = document.getElementById(index);
                                    el && el.classList.add("is-invalid");
                                });
                                $('input').on('focus', (e) => {
                                    this.toggleButtonDisable && this.toggleButtonDisable(true);
                                    const id = $(e.target).attr('id');
                                    document.getElementById(id)?.classList.remove("is-invalid");
                                });
                            }
                        })
                        .catch(errors => console.log(errors));
                },
                async loadBank() {
                    const response = await axios.post("{{ route('admin.'.$menu->currentRoute.'.loadbank') }}");
                    this.option.banks = response.data.banks;
                    // this.option = {
                    //     banks: response.data.banks,
                    //
                    // };
                },
                addEditSubmitNew๘(event) {
                    event.preventDefault();
                    this.toggleButtonDisable(true);

                    if (this.formmethod === 'add') {
                        var url = "{{ route('admin.'.$menu->currentRoute.'.create') }}";
                    } else if (this.formmethod === 'edit') {
                        var url = "{{ route('admin.'.$menu->currentRoute.'.update') }}/" + this.code;
                    }

                    let formData = new FormData();
                    const json = JSON.stringify({
                        acc_name: this.formaddedit.acc_name,
                        acc_no: this.formaddedit.acc_no,
                        banks: this.formaddedit.banks,
                        user_name: this.formaddedit.user_name,
                        user_pass: this.formaddedit.user_pass,
                        smestatus: this.formaddedit.smestatus,
                        local: this.formaddedit.local,
                        sort: this.formaddedit.sort,
                        website: this.formaddedit.website,
                        deposit_min: this.formaddedit.deposit_min,
                        webhook: this.formaddedit.webhook,
                        pattern: this.formaddedit.pattern,
                        one_time_password: this.formaddedit.one_time_password,
                        visibility_scope: this.formaddedit.visibility_scope,
                        qrcode: this.formaddedit.qrcode,
                        remark: this.formaddedit.remark,
                        filepic: this.formaddedit.filepic,
                    });

                    formData.append('data', json);
                    formData.append('fileupload', this.fileupload);
                    // formData.append('filepic', $('input[name="filepic[image_0]"]')[1].files[0]);


                    // const config = {headers: {'Content-Type': `multipart/form-data; boundary=${formData._boundary}`}};
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
                        .catch(errors => console.log(errors));
                }
            },
        });


    </script>
@endpush

