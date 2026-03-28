@php
    $canAutoResultTestFetch = function_exists('bouncer') ? bouncer()->hasPermission('lotto_draws.auto_result_test_fetch') : false;
    $canAutoResultLogs = function_exists('bouncer') ? bouncer()->hasPermission('lotto_draws.auto_result_metrics') : false;
@endphp

<b-modal ref="addedit" id="addedit" centered size="md" title="รายการหวย" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.prevent="addEditSubmit" v-if="show">
        <b-form-group label="กลุ่มหวย:" label-for="group_id">
            <b-form-select
                id="group_id"
                v-model="formaddedit.group_id"
                :options="option.groups"
                size="sm"
                required
            ></b-form-select>
        </b-form-group>
        <b-form-group label="ชื่อรายการหวย:" label-for="name" description="เช่น ออมสิน, ธกส, ดาวโจนส์">
            <b-form-input
                id="name"
                v-model="formaddedit.name"
                type="text"
                size="sm"
                placeholder="ชื่อรายการหวย"
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>
        <b-row>
            <b-col cols="12" md="6">
                <b-form-group label="ชื่ออังกฤษ:" label-for="name_en">
                    <b-form-input id="name_en" v-model="formaddedit.name_en" type="text" size="sm" autocomplete="off"></b-form-input>
                </b-form-group>
            </b-col>
            <b-col cols="12" md="6">
                <b-form-group label="ชื่อเขมร:" label-for="name_kh">
                    <b-form-input id="name_kh" v-model="formaddedit.name_kh" type="text" size="sm" autocomplete="off"></b-form-input>
                </b-form-group>
            </b-col>
        </b-row>
        <b-row>
            <b-col cols="12" md="6">
                <b-form-group label="ชื่อลาว:" label-for="name_laos">
                    <b-form-input id="name_laos" v-model="formaddedit.name_laos" type="text" size="sm" autocomplete="off"></b-form-input>
                </b-form-group>
            </b-col>
            <b-col cols="12" md="6">
                <b-form-group label="Code:" label-for="code" description="ตัวอักษรภาษาอังกฤษ ตัวเลข หรือ underscore">
                    <b-form-input
                        id="code"
                        v-model="formaddedit.code"
                        type="text"
                        size="sm"
                        placeholder="เช่น gsb, kbank"
                        autocomplete="off"
                        required
                    ></b-form-input>
                </b-form-group>
            </b-col>
        </b-row>
        <b-form-group label="โหมดงวด:" label-for="draw_mode" description="manual = ทีมงานสร้างงวดเอง, daily = ทุกวัน, weekdays = จันทร์-ศุกร์">
            <b-form-select id="draw_mode" v-model="formaddedit.draw_mode" :options="option.drawModes" size="sm"></b-form-select>
        </b-form-group>
        <b-row>
            <b-col cols="12" md="4">
                <b-form-group label="เวลาเปิดรับอัตโนมัติ:" label-for="auto_open_time">
                    <b-form-input id="auto_open_time" v-model="formaddedit.auto_open_time" type="time" size="sm"></b-form-input>
                </b-form-group>
            </b-col>
            <b-col cols="12" md="4">
                <b-form-group label="เวลาปิดรับอัตโนมัติ:" label-for="auto_close_time">
                    <b-form-input id="auto_close_time" v-model="formaddedit.auto_close_time" type="time" size="sm"></b-form-input>
                </b-form-group>
            </b-col>
            <b-col cols="12" md="4">
                <b-form-group label="เวลาออกผลอัตโนมัติ:" label-for="auto_result_time">
                    <b-form-input id="auto_result_time" v-model="formaddedit.auto_result_time" type="time" size="sm"></b-form-input>
                </b-form-group>
            </b-col>
        </b-row>
        <b-form-group label="ลิงก์ออกผล:" label-for="result_url">
            <b-form-input id="result_url" v-model="formaddedit.result_url" type="url" size="sm" placeholder="https://..."></b-form-input>
        </b-form-group>
        <b-row>
            <b-col cols="12" md="6">
                <b-form-group label="Logo URL:" label-for="logo">
                    <b-form-input id="logo" v-model="formaddedit.logo" type="text" size="sm" autocomplete="off" placeholder="/storage/... หรือ URL"></b-form-input>
                    <b-form-file class="mt-2" size="sm" v-model="formaddedit.logo_file" accept="image/jpeg,image/png,image/gif,image/webp" placeholder="อัปโหลด Logo"></b-form-file>
                    <a v-if="formaddedit.logo" :href="formaddedit.logo" target="_blank" class="d-inline-block mt-1">ดูรูปปัจจุบัน</a>
                </b-form-group>
            </b-col>
            <b-col cols="12" md="6">
                <b-form-group label="Icon URL:" label-for="icon">
                    <b-form-input id="icon" v-model="formaddedit.icon" type="text" size="sm" autocomplete="off" placeholder="/storage/... หรือ URL"></b-form-input>
                    <b-form-file class="mt-2" size="sm" v-model="formaddedit.icon_file" accept="image/jpeg,image/png,image/gif,image/webp" placeholder="อัปโหลด Icon"></b-form-file>
                    <a v-if="formaddedit.icon" :href="formaddedit.icon" target="_blank" class="d-inline-block mt-1">ดูรูปปัจจุบัน</a>
                </b-form-group>
            </b-col>
        </b-row>
        <b-form-group>
            <b-form-checkbox v-model="formaddedit.is_enabled" :value="1" :unchecked-value="0">
                เปิดใช้งาน
            </b-form-checkbox>
        </b-form-group>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <b-button type="submit" variant="primary" size="sm">บันทึก</b-button>
            </div>
        </div>
    </b-form>
</b-modal>

<b-modal ref="autoSourcesModal" id="autoSourcesModal" centered size="xl" :title="autoSourcesModal.title" hide-footer>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <small class="text-muted">จัดการ Auto Result Sources ของรายการหวยนี้ (native modal)</small>
        <div>
            <button type="button" class="btn btn-primary btn-sm mr-1" @click="addAutoSource">
                <i class="fas fa-plus"></i> เพิ่ม Source
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="loadAutoSources">
                <i class="fas fa-sync-alt"></i> รีโหลด
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-sm dataTable no-footer mb-0 align-middle">
            <thead class="thead-light">
            <tr>
                <th class="text-center" style="width:80px;">#</th>
                <th class="text-center" style="width:90px;">Priority</th>
                <th>Endpoint</th>
                <th class="text-center" style="width:160px;">อัปเดตล่าสุด</th>
                <th class="text-center" style="width:90px;">สถานะ</th>
                <th class="text-center" style="width:220px;">จัดการ</th>
            </tr>
            </thead>
            <tbody>
            <tr v-if="autoSourcesLoading">
                <td colspan="6" class="text-center text-muted py-3">กำลังโหลด...</td>
            </tr>
            <tr v-else-if="autoSourcesItems.length === 0">
                <td colspan="6" class="text-center text-muted py-3">ยังไม่มี Auto Result Source ของรายการหวยนี้</td>
            </tr>
            <tr v-else v-for="item in autoSourcesItems" :key="item.id">
                <td class="text-center">@{{ item.id }}</td>
                <td class="text-center">@{{ item.priority }}</td>
                <td class="text-break">@{{ item.endpoint_url || '-' }}</td>
                <td class="text-center">@{{ item.updated_at || '-' }}</td>
                <td class="text-center">
                    <button type="button"
                            class="btn btn-xs"
                            :class="item.is_active ? 'btn-success' : 'btn-danger'"
                            :title="item.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน'"
                            @click="toggleAutoSource(item)">
                        <i class="fas" :class="item.is_active ? 'fa-check' : 'fa-times'"></i>
                    </button>
                </td>
                <td class="text-center">
                    <button type="button" class="btn-xs btn btn-info mr-1" title="แก้ไข" @click="editAutoSource(item.id)">
                        <i class="fa fa-edit"></i> แก้ไข
                    </button>
                    <button type="button" class="btn-xs btn btn-danger" title="ลบ" @click="deleteAutoSource(item)">
                        <i class="fa fa-trash"></i> ลบ
                    </button>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</b-modal>

<b-modal ref="sourceEditorModal" id="sourceEditorModal" centered size="xl" :title="sourceEditorTitle" hide-footer>
    <b-form @submit.prevent="submitAutoSourceForm">
        <b-tabs v-model="activeSourceTab" content-class="pt-3">
            <b-tab title="ทั่วไป">
                <b-row>
                    <b-col md="6">
                        <b-form-group label="ตลาด (สรุป)">
                            <b-form-input size="sm" :value="selectedMarketText" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">แก้ค่าได้ที่แท็บ <code>ตั้งค่าด่วน</code></small>
                        </b-form-group>
                    </b-col>
                    <b-col md="3">
                        <b-form-group label="ลำดับความสำคัญ (สรุป)">
                            <b-form-input size="sm" :value="autoSourceForm.priority" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">แก้ค่าได้ที่แท็บ <code>ตั้งค่าด่วน</code></small>
                        </b-form-group>
                    </b-col>
                    <b-col md="3">
                        <b-form-group label="หมดเวลารอ (สรุป)">
                            <b-form-input size="sm" :value="autoSourceForm.timeout_seconds" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">แก้ค่าได้ที่แท็บ <code>ตั้งค่าด่วน</code></small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="URL ปลายทาง (สรุป)">
                            <b-form-input size="sm" :value="autoSourceForm.endpoint_url || '-'" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">แก้ค่าได้ที่แท็บ <code>ตั้งค่าด่วน</code> หรือ <code>JSON หลัก</code></small>
                        </b-form-group>
                    </b-col>
                    <b-col md="3">
                        <b-form-group label="วิธีเรียก API (สรุป)">
                            <b-form-input size="sm" :value="autoSourceForm.http_method" readonly></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col md="3">
                        <b-form-group label="ชนิด Parser (สรุป)">
                            <b-form-input size="sm" :value="autoSourceForm.parser_type" readonly></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-row>
            </b-tab>

            <b-tab title="ตั้งค่าด่วน">
                <b-row>
                    <b-col md="6">
                        <b-form-group label="ตลาด">
                            <b-form-input size="sm" :value="selectedMarketText" readonly></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="ลำดับความสำคัญ">
                            <b-form-input size="sm" type="number" min="1" v-model="autoSourceForm.priority"></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="หมดเวลารอ (วินาที)">
                            <b-form-input size="sm" type="number" min="1" max="60" v-model="autoSourceForm.timeout_seconds"></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="โหมดอ้างอิงวันงวด">
                            <b-form-select size="sm" :options="autoSourceOptions.lookupDateModes" v-model="autoSourceForm.lookup_date_mode"></b-form-select>
                            <small class="text-muted d-block mt-1">@{{ lookupModeHelpText }}</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="เลื่อนวัน (วัน)">
                            <b-form-input size="sm" type="number" min="-365" max="365" v-model="autoSourceForm.lookup_date_offset_days"></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="URL ผลหวย">
                            <b-form-input size="sm" v-model="autoSourceForm.quick_endpoint_url" placeholder="https://example.com/result"></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="วิธีเรียก API">
                            <b-form-select size="sm" v-model="autoSourceForm.quick_http_method" :options="autoSourceOptions.httpMethods"></b-form-select>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="Path ของวันที่">
                            <b-form-input size="sm" v-model="autoSourceForm.quick_draw_date_path" placeholder="$.date"></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="เริ่มใช้งานตั้งแต่ (Y-m-d H:i:s)">
                            <b-form-input size="sm" v-model="autoSourceForm.effective_from"></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="สิ้นสุดใช้งาน (Y-m-d H:i:s)">
                            <b-form-input size="sm" v-model="autoSourceForm.effective_to"></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="รูปแบบวันที่ต้นทาง">
                            <b-form-select size="sm" v-model="autoSourceForm.quick_draw_date_from_format" :options="quickDateFormats"></b-form-select>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="Path ของรางวัลที่ 1">
                            <b-form-input size="sm" v-model="autoSourceForm.quick_first_prize_paths" placeholder="$.lotData.DB"></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="เก็บท้ายกี่หลัก (รางวัลที่ 1)">
                            <b-form-input size="sm" type="number" min="1" max="10" v-model="autoSourceForm.quick_first_prize_take_right"></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="Path ของเลขท้าย 2 ตัว">
                            <b-form-input size="sm" v-model="autoSourceForm.quick_last2_paths" placeholder="$.lotData.1"></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="เก็บท้ายกี่หลัก (เลขท้าย)">
                            <b-form-input size="sm" type="number" min="1" max="10" v-model="autoSourceForm.quick_last2_take_right"></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col md="6" class="pt-2">
                        <b-form-checkbox v-model="autoSourceForm.is_active" switch>เปิดใช้งาน</b-form-checkbox>
                        <b-form-checkbox v-model="autoSourceForm.supports_partial" switch class="mt-1">ยอมรับผลไม่ครบ (Partial)</b-form-checkbox>
                        <b-form-checkbox v-model="autoSourceForm.requires_browser" switch class="mt-1">ใช้ Browser Worker</b-form-checkbox>
                    </b-col>
                </b-row>

                <b-form-group label="เหตุผลการเปลี่ยนแปลง">
                    <b-form-input size="sm" v-model="autoSourceForm.revision_reason" placeholder="optional"></b-form-input>
                </b-form-group>

                <div class="d-flex flex-wrap">
                    <button type="button" class="btn btn-primary btn-sm mr-2 mb-2" @click="generateQuickPipelineJson">สร้าง JSON อัตโนมัติ</button>
                    <button type="button" class="btn btn-secondary btn-sm mb-2" @click="applyQuickPresetLaosVip">ตั้งค่าอัตโนมัติ: Laos VIP</button>
                </div>
            </b-tab>

            <b-tab title="JSON หลัก">
                <div class="d-flex flex-wrap mb-2">
                    <button type="button" class="btn btn-outline-secondary btn-xs mr-1 mb-1" @click="applyAutoSourceExample">ใส่ตัวอย่าง</button>
                    <button type="button" class="btn btn-outline-secondary btn-xs mr-1 mb-1" @click="syncUnifiedFromForm">รีเฟรชค่าปัจจุบัน</button>
                </div>
                <b-form-group label="Pipeline Config JSON">
                    <b-form-textarea rows="14" v-model="autoSourceForm.unified_pipeline_json"></b-form-textarea>
                </b-form-group>
                <b-alert show variant="light" class="py-2">
                    ระบบจะ map ค่าใน JSON ก้อนนี้ไปยัง field ย่อยก่อน preview/validate/save อัตโนมัติ
                </b-alert>
            </b-tab>

            <b-tab title="ทดสอบตามวันที่">
                <b-row>
                    <b-col md="6">
                        <b-form-group label="วันที่งวดที่ใช้ทดสอบ">
                            <b-form-input size="sm" type="date" v-model="sourceTestDate"></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="สรุปผลการทดสอบล่าสุด">
                            <b-form-textarea size="sm" rows="4" :value="testRunSummary" readonly></b-form-textarea>
                        </b-form-group>
                    </b-col>
                </b-row>

                <div class="d-flex flex-wrap">
                    <button
                        v-if="permissions.canTestFetch"
                        type="button"
                        class="btn btn-warning btn-sm mr-1 mb-1"
                        @click="runAutoSourceTestByDate">
                        Dry Run ตามวันที่
                    </button>
                    <button
                        v-if="permissions.canViewLogs"
                        type="button"
                        class="btn btn-secondary btn-sm mb-1"
                        @click="showAutoSourceLogsByDate">
                        ดู Log ตามวันที่
                    </button>
                </div>
            </b-tab>
        </b-tabs>

        <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
            <div class="d-flex flex-wrap">
                <button type="button" class="btn btn-primary btn-sm mr-1 mb-1" @click="previewAutoSource">พรีวิว</button>
                <button type="button" class="btn btn-info btn-sm mr-1 mb-1 text-white" @click="validateAutoSource">ตรวจสอบค่า</button>
                <button type="button" class="btn btn-warning btn-sm mb-1" @click="validateAutoSourceCutover">ตรวจสอบก่อนเปิดใช้งานจริง</button>
            </div>
            <button type="submit" class="btn btn-success btn-sm mb-1">บันทึก Source</button>
        </div>
    </b-form>
</b-modal>

<b-modal ref="sourceTestLogsModal" id="sourceTestLogsModal" centered size="xl" title="Auto Result Test Logs" hide-footer>
    <div class="table-responsive">
        <table class="table table-striped table-sm mb-0">
            <thead>
            <tr>
                <th class="text-center" style="width:70px;">#</th>
                <th style="width:170px;">เวลา</th>
                <th style="width:110px;">สถานะ</th>
                <th style="width:120px;">Stage</th>
                <th style="width:120px;">Run ID</th>
                <th>Error</th>
                <th class="text-center" style="width:100px;">Detail</th>
            </tr>
            </thead>
            <tbody>
            <tr v-if="sourceTestLogs.length === 0">
                <td colspan="7" class="text-center text-muted py-3">ไม่พบ log</td>
            </tr>
            <tr v-for="row in sourceTestLogs" :key="row.id">
                <td class="text-center">@{{ row.id }}</td>
                <td>@{{ row.created_at || '-' }}</td>
                <td>@{{ row.status || '-' }}</td>
                <td>@{{ row.pipeline_stage || '-' }}</td>
                <td>@{{ row.run_id || '-' }}</td>
                <td class="text-truncate" style="max-width:260px;">@{{ row.error_message || '-' }}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-xs btn-outline-primary" @click="showLogDetail(row)">ดู</button>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</b-modal>

@push('styles')
    <style>
        #sourceEditorModal .nav-tabs .nav-link {
            border-radius: .4rem .4rem 0 0;
            font-weight: 600;
            color: #4b5563;
            padding: .45rem .75rem;
        }

        #sourceEditorModal .nav-tabs .nav-link.active {
            color: #0f4c81;
            background: #eef6ff;
            border-color: #bfdcff #bfdcff #eef6ff;
        }
    </style>
@endpush

@push('scripts')
    <script type="module">
        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: true,
                    formmethod: 'add',
                    code: null,
                    formaddedit: {
                        group_id:   '',
                        name:       '',
                        name_en:    '',
                        name_kh:    '',
                        name_laos:  '',
                        logo:       '',
                        icon:       '',
                        logo_file:  null,
                        icon_file:  null,
                        code:       '',
                        draw_mode: 'manual',
                        auto_open_time: '',
                        auto_close_time: '',
                        auto_result_time: '',
                        result_url: '',
                        is_enabled: 1,
                    },
                    option: {
                        groups: [
                            { value: '', text: '-- เลือกกลุ่มหวย --' },
                            @foreach($groups as $g)
                            { value: {{ $g->id }}, text: '{{ $g->name }} ({{ $g->code }})' },
                            @endforeach
                        ],
                        drawModes: [
                            { value: 'manual', text: 'Manual (เพิ่มงวดเอง)' },
                            { value: 'daily', text: 'Auto ทุกวัน' },
                            { value: 'weekdays', text: 'Auto จันทร์-ศุกร์' },
                        ],
                    },
                    permissions: {
                        canTestFetch: @json($canAutoResultTestFetch),
                        canViewLogs: @json($canAutoResultLogs),
                    },
                    autoSourcesModal: {
                        marketId: null,
                        title: 'Auto Result Sources',
                    },
                    autoSourcesLoading: false,
                    autoSourcesItems: [],
                    sourceEditorMode: 'add',
                    sourceEditorTitle: 'เพิ่ม Auto Result Source',
                    activeSourceTab: 0,
                    quickDateFormats: [
                        { value: 'd/m/Y', text: 'd/m/Y (เช่น 27/03/2026)' },
                        { value: 'Y-m-d', text: 'Y-m-d (เช่น 2026-03-27)' },
                        { value: 'd-m-Y', text: 'd-m-Y (เช่น 27-03-2026)' },
                        { value: 'Y/m/d', text: 'Y/m/d (เช่น 2026/03/27)' },
                    ],
                    sourceTestDate: '',
                    sourceTestRunId: '',
                    testRunResult: null,
                    sourceTestLogs: [],
                    autoSourceOptions: {
                        lookupDateModes: [
                            { value: 'ROUND_DATE', text: 'ROUND_DATE' },
                            { value: 'ROUND_DATE_MINUS_DAYS', text: 'ROUND_DATE_MINUS_DAYS' },
                            { value: 'ROUND_DATE_PLUS_DAYS', text: 'ROUND_DATE_PLUS_DAYS' },
                            { value: 'RESULT_AT_DATE', text: 'RESULT_AT_DATE' },
                        ],
                        parserTypes: [
                            { value: 'JSON_PATH', text: 'JSON_PATH' },
                            { value: 'CSS_SELECTOR', text: 'CSS_SELECTOR' },
                            { value: 'REGEX', text: 'REGEX' },
                            { value: 'SCRIPT_JSON_PATH', text: 'SCRIPT_JSON_PATH' },
                        ],
                        sourceTypes: [
                            { value: 'api', text: 'api' },
                            { value: 'html', text: 'html' },
                        ],
                        httpMethods: [
                            { value: 'GET', text: 'GET' },
                            { value: 'POST', text: 'POST' },
                            { value: 'PUT', text: 'PUT' },
                        ],
                        fetchStrategies: [
                            { value: 'JSON_HTTP', text: 'JSON_HTTP' },
                            { value: 'HTML_HTTP', text: 'HTML_HTTP' },
                            { value: 'RENDERED_BROWSER', text: 'RENDERED_BROWSER' },
                            { value: 'EMBEDDED_JSON', text: 'EMBEDDED_JSON' },
                            { value: 'MANUAL_INPUT', text: 'MANUAL_INPUT' },
                        ],
                        selectionStages: [
                            { value: 'PRE_MAPPING', text: 'PRE_MAPPING' },
                            { value: 'POST_MAPPING', text: 'POST_MAPPING' },
                        ],
                    },
                    autoSourceForm: this.defaultAutoSourceForm(),
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            computed: {
                selectedMarketText() {
                    const marketId = Number(this.autoSourceForm.market_id || 0);
                    if (!marketId) {
                        return '-';
                    }

                    const found = (this.autoSourcesItems || []).find((item) => Number(item.market_id || 0) === marketId);
                    if (found && found.market_name) {
                        return found.market_name;
                    }

                    return `Market #${marketId}`;
                },
                lookupModeHelpText() {
                    const mode = String(this.autoSourceForm.lookup_date_mode || 'ROUND_DATE');
                    const map = {
                        ROUND_DATE: 'เทียบวันงวดตรง ๆ',
                        ROUND_DATE_MINUS_DAYS: 'ใช้วันงวดลบจำนวนวันที่ตั้งไว้',
                        ROUND_DATE_PLUS_DAYS: 'ใช้วันงวดบวกจำนวนวันที่ตั้งไว้',
                        RESULT_AT_DATE: 'ใช้วันที่ผลประกาศ (result_at)',
                    };

                    return map[mode] || mode;
                },
                testRunSummary() {
                    if (!this.testRunResult || typeof this.testRunResult !== 'object') {
                        return '';
                    }

                    const runId = String(this.testRunResult.run_id || '-');
                    const drawId = String(this.testRunResult.draw_id || '-');
                    const output = String(this.testRunResult.output || '').trim();
                    return `run_id: ${runId}\ndraw_id: ${drawId}\n${output}`;
                },
            },
            methods: {
                defaultAutoSourceForm() {
                    return {
                        id: null,
                        market_id: '',
                        is_active: true,
                        priority: 100,
                        source_type: 'api',
                        endpoint_url: '',
                        http_method: 'GET',
                        lookup_date_mode: 'ROUND_DATE',
                        lookup_date_offset_days: 0,
                        parser_type: 'JSON_PATH',
                        timeout_seconds: 10,
                        effective_from: '',
                        effective_to: '',
                        pipeline_version: 'V2_CUTOVER',
                        fetch_strategy: 'JSON_HTTP',
                        selection_stage: 'POST_MAPPING',
                        supports_partial: false,
                        requires_browser: false,
                        revision_reason: '',
                        unified_pipeline_json: '',
                        quick_endpoint_url: '',
                        quick_http_method: 'GET',
                        quick_draw_date_path: '$.date',
                        quick_draw_date_from_format: 'Y-m-d',
                        quick_first_prize_paths: '$.lotData.DB',
                        quick_first_prize_take_right: 3,
                        quick_last2_paths: '$.lotData.1',
                        quick_last2_take_right: 2,
                        request_headers_json: '{}',
                        request_query_template_json: '{}',
                        request_body_template_json: '{}',
                        parser_config_json: '{}',
                        mapping_config_json: '{}',
                        validation_config_json: '{"required_fields":["draw_date","first_prize","last_2_digits"]}',
                        retry_policy_json: '{"max_attempts":3,"backoff_seconds":[10,30,60]}',
                        fetch_config_json: '{}',
                        selection_config_json: '{"selection_stage":"PRE_MAPPING","strategy":"strict_single_match","date_field":"draw_date_raw","required_fields":[],"meta":{"candidate_draw_date_offset_days":0,"expected_draw_date_offset_days":0}}',
                        readiness_config_json: '{"enabled":true,"minimum_required_keys":["draw_date","first_prize","last_2_digits"]}',
                    };
                },
                applyAutoSourceExample() {
                    const example = {
                        request_headers_json: {},
                        request_query_template_json: {},
                        request_body_template_json: {},
                        fetch_config_json: {
                            fetch_strategy: 'JSON_HTTP',
                            endpoint_url: this.autoSourceForm.endpoint_url || 'https://example.com/result',
                            http_method: this.autoSourceForm.http_method || 'GET',
                            headers: [],
                            query: [],
                            timeout_seconds: Number(this.autoSourceForm.timeout_seconds || 10),
                        },
                        parser_config_json: {
                            version: 2,
                            mode: 'single_payload',
                            parser_type: this.autoSourceForm.parser_type || 'JSON_PATH',
                            fields: {
                                draw_date_raw: { type: 'JSON_PATH', path: '$.resultDate' },
                                first_prize_raw_1: { type: 'JSON_PATH', path: '$.lotData.DB' },
                                last_2_raw_1: { type: 'JSON_PATH', path: '$.lotData.1' },
                            },
                        },
                        mapping_config_json: {
                            fields: {
                                draw_date: {
                                    from: 'draw_date_raw',
                                    transforms: [
                                        { op: 'trim' },
                                        { op: 'date', from: 'Y-m-d', to: 'Y-m-d' },
                                    ],
                                },
                                first_prize: {
                                    from: 'first_prize_raw_1',
                                    transforms: [
                                        { op: 'digits_only' },
                                        { op: 'right', length: 3 },
                                    ],
                                },
                                last_2_digits: {
                                    from: 'last_2_raw_1',
                                    transforms: [
                                        { op: 'digits_only' },
                                        { op: 'right', length: 2 },
                                    ],
                                },
                            },
                        },
                        selection_config_json: {
                            selection_stage: 'PRE_MAPPING',
                            strategy: 'strict_single_match',
                            date_field: 'draw_date_raw',
                            required_fields: [],
                            meta: {
                                candidate_draw_date_offset_days: 0,
                                expected_draw_date_offset_days: 0,
                            },
                        },
                        validation_config_json: {
                            required_fields: ['draw_date', 'first_prize', 'last_2_digits'],
                        },
                        readiness_config_json: {
                            enabled: true,
                            minimum_required_keys: ['draw_date', 'first_prize', 'last_2_digits'],
                        },
                        retry_policy_json: {
                            max_attempts: 3,
                            backoff_seconds: [10, 30, 60],
                        },
                    };

                    this.autoSourceForm.unified_pipeline_json = JSON.stringify(example, null, 2);
                    this.applyUnifiedToForm();
                },
                parseJsonSafe(text) {
                    try {
                        const raw = String(text || '').trim();
                        if (!raw) {
                            return {};
                        }
                        const parsed = JSON.parse(raw);
                        return parsed && typeof parsed === 'object' ? parsed : {};
                    } catch (_error) {
                        return {};
                    }
                },
                buildUnifiedConfigObject() {
                    return {
                        request_headers_json: this.parseJsonSafe(this.autoSourceForm.request_headers_json),
                        request_query_template_json: this.parseJsonSafe(this.autoSourceForm.request_query_template_json),
                        request_body_template_json: this.parseJsonSafe(this.autoSourceForm.request_body_template_json),
                        fetch_config_json: this.parseJsonSafe(this.autoSourceForm.fetch_config_json),
                        parser_config_json: this.parseJsonSafe(this.autoSourceForm.parser_config_json),
                        mapping_config_json: this.parseJsonSafe(this.autoSourceForm.mapping_config_json),
                        selection_config_json: this.parseJsonSafe(this.autoSourceForm.selection_config_json),
                        validation_config_json: this.parseJsonSafe(this.autoSourceForm.validation_config_json),
                        readiness_config_json: this.parseJsonSafe(this.autoSourceForm.readiness_config_json),
                        retry_policy_json: this.parseJsonSafe(this.autoSourceForm.retry_policy_json),
                    };
                },
                syncUnifiedFromForm() {
                    const unified = this.buildUnifiedConfigObject();
                    this.autoSourceForm.unified_pipeline_json = JSON.stringify(unified, null, 2);
                    this.populateQuickFromUnified(unified);
                },
                applyUnifiedToForm() {
                    const unified = this.parseJsonSafe(this.autoSourceForm.unified_pipeline_json);
                    this.autoSourceForm.request_headers_json = JSON.stringify(unified.request_headers_json || {}, null, 2);
                    this.autoSourceForm.request_query_template_json = JSON.stringify(unified.request_query_template_json || {}, null, 2);
                    this.autoSourceForm.request_body_template_json = JSON.stringify(unified.request_body_template_json || {}, null, 2);
                    this.autoSourceForm.fetch_config_json = JSON.stringify(unified.fetch_config_json || {}, null, 2);
                    this.autoSourceForm.parser_config_json = JSON.stringify(unified.parser_config_json || {}, null, 2);
                    this.autoSourceForm.mapping_config_json = JSON.stringify(unified.mapping_config_json || {}, null, 2);
                    this.autoSourceForm.selection_config_json = JSON.stringify(unified.selection_config_json || {}, null, 2);
                    this.autoSourceForm.validation_config_json = JSON.stringify(unified.validation_config_json || {}, null, 2);
                    this.autoSourceForm.readiness_config_json = JSON.stringify(unified.readiness_config_json || {}, null, 2);
                    this.autoSourceForm.retry_policy_json = JSON.stringify(unified.retry_policy_json || {}, null, 2);
                    this.populateQuickFromUnified(unified);
                },
                populateQuickFromUnified(unified) {
                    const fetchConfig = (unified.fetch_config_json && typeof unified.fetch_config_json === 'object') ? unified.fetch_config_json : {};
                    const parserConfig = (unified.parser_config_json && typeof unified.parser_config_json === 'object') ? unified.parser_config_json : {};
                    const mappingConfig = (unified.mapping_config_json && typeof unified.mapping_config_json === 'object') ? unified.mapping_config_json : {};
                    const fields = (parserConfig.fields && typeof parserConfig.fields === 'object') ? parserConfig.fields : {};
                    const mapFields = (mappingConfig.fields && typeof mappingConfig.fields === 'object') ? mappingConfig.fields : {};

                    this.autoSourceForm.quick_endpoint_url = String(fetchConfig.endpoint_url || this.autoSourceForm.quick_endpoint_url || '');
                    this.autoSourceForm.quick_http_method = String(fetchConfig.http_method || this.autoSourceForm.quick_http_method || 'GET').toUpperCase();
                    this.autoSourceForm.quick_draw_date_path = String((fields.draw_date_raw || {}).path || this.autoSourceForm.quick_draw_date_path || '$.date');

                    const firstRule = mapFields.first_prize || {};
                    const last2Rule = mapFields.last_2_digits || {};

                    const firstPath = firstRule.from && fields[firstRule.from] ? String(fields[firstRule.from].path || '') : '';
                    const last2Path = last2Rule.from && fields[last2Rule.from] ? String(fields[last2Rule.from].path || '') : '';
                    if (firstPath) {
                        this.autoSourceForm.quick_first_prize_paths = firstPath;
                    }
                    if (last2Path) {
                        this.autoSourceForm.quick_last2_paths = last2Path;
                    }

                    const firstRight = (Array.isArray(firstRule.transforms) ? firstRule.transforms : []).find((t) => String((t || {}).op || '').toLowerCase() === 'right');
                    const last2Right = (Array.isArray(last2Rule.transforms) ? last2Rule.transforms : []).find((t) => String((t || {}).op || '').toLowerCase() === 'right');
                    this.autoSourceForm.quick_first_prize_take_right = Number((firstRight || {}).length || this.autoSourceForm.quick_first_prize_take_right || 3);
                    this.autoSourceForm.quick_last2_take_right = Number((last2Right || {}).length || this.autoSourceForm.quick_last2_take_right || 2);
                },
                splitQuickPaths(text) {
                    return String(text || '').split(',').map((v) => v.trim()).filter((v) => v !== '');
                },
                applyQuickPresetLaosVip() {
                    this.autoSourceForm.quick_endpoint_url = 'https://laosviplot.com/result';
                    this.autoSourceForm.quick_http_method = 'GET';
                    this.autoSourceForm.quick_draw_date_path = '$.date';
                    this.autoSourceForm.quick_draw_date_from_format = 'd/m/Y';
                    this.autoSourceForm.quick_first_prize_paths = '$.lotto_2';
                    this.autoSourceForm.quick_first_prize_take_right = 3;
                    this.autoSourceForm.quick_last2_paths = '$.lotto_1';
                    this.autoSourceForm.quick_last2_take_right = 2;
                },
                generateQuickPipelineJson() {
                    const endpointUrl = String(this.autoSourceForm.quick_endpoint_url || '').trim();
                    if (!endpointUrl) {
                        this.showApiError(null, 'กรุณาใส่ URL ผลหวย');
                        return;
                    }

                    const firstPrizePaths = this.splitQuickPaths(this.autoSourceForm.quick_first_prize_paths);
                    const last2Paths = this.splitQuickPaths(this.autoSourceForm.quick_last2_paths);
                    if (firstPrizePaths.length === 0 || last2Paths.length === 0) {
                        this.showApiError(null, 'กรุณาระบุ Path ของรางวัลที่ 1 และเลขท้าย 2 ตัว');
                        return;
                    }

                    const firstKey = 'first_prize_raw_1';
                    const last2Key = 'last_2_raw_1';
                    const unified = {
                        request_headers_json: {},
                        request_query_template_json: {},
                        request_body_template_json: {},
                        fetch_config_json: {
                            fetch_strategy: 'JSON_HTTP',
                            endpoint_url: endpointUrl,
                            http_method: String(this.autoSourceForm.quick_http_method || 'GET').toUpperCase(),
                            headers: {},
                            query: {},
                            timeout_seconds: Number(this.autoSourceForm.timeout_seconds || 10),
                        },
                        parser_config_json: {
                            version: 2,
                            mode: 'single_payload',
                            parser_type: 'JSON_PATH',
                            fields: {
                                draw_date_raw: { type: 'JSON_PATH', path: String(this.autoSourceForm.quick_draw_date_path || '$.date') },
                                [firstKey]: { type: 'JSON_PATH', path: firstPrizePaths[0] },
                                [last2Key]: { type: 'JSON_PATH', path: last2Paths[0] },
                            },
                        },
                        mapping_config_json: {
                            fields: {
                                draw_date: {
                                    from: 'draw_date_raw',
                                    transforms: [
                                        { op: 'trim' },
                                        { op: 'date', from: String(this.autoSourceForm.quick_draw_date_from_format || 'Y-m-d'), to: 'Y-m-d' },
                                    ],
                                },
                                first_prize: {
                                    from: firstKey,
                                    transforms: [{ op: 'digits_only' }, { op: 'right', length: Number(this.autoSourceForm.quick_first_prize_take_right || 3) }],
                                },
                                last_2_digits: {
                                    from: last2Key,
                                    transforms: [{ op: 'digits_only' }, { op: 'right', length: Number(this.autoSourceForm.quick_last2_take_right || 2) }],
                                },
                            },
                        },
                        selection_config_json: {
                            selection_stage: 'PRE_MAPPING',
                            strategy: 'strict_single_match',
                            date_field: 'draw_date_raw',
                            required_fields: [],
                            meta: { candidate_draw_date_offset_days: 0, expected_draw_date_offset_days: 0 },
                        },
                        validation_config_json: { required_fields: ['draw_date', 'first_prize', 'last_2_digits'] },
                        readiness_config_json: { enabled: true, minimum_required_keys: ['draw_date', 'first_prize', 'last_2_digits'] },
                        retry_policy_json: { max_attempts: 3, backoff_seconds: [10, 30, 60] },
                    };

                    this.autoSourceForm.unified_pipeline_json = JSON.stringify(unified, null, 2);
                    this.applyUnifiedToForm();
                    this.autoSourceForm.endpoint_url = endpointUrl;
                    this.autoSourceForm.http_method = String(this.autoSourceForm.quick_http_method || 'GET').toUpperCase();
                    this.autoSourceForm.parser_type = 'JSON_PATH';
                    this.autoSourceForm.fetch_strategy = 'JSON_HTTP';
                    this.autoSourceForm.selection_stage = 'PRE_MAPPING';
                    this.activeSourceTab = 2;
                },
                parseJsonInput(text, fieldName) {
                    const raw = String(text || '').trim();
                    if (!raw) {
                        return null;
                    }

                    try {
                        return JSON.parse(raw);
                    } catch (_error) {
                        throw new Error(`${fieldName} JSON ไม่ถูกต้อง`);
                    }
                },
                makeSourcePayload() {
                    this.applyUnifiedToForm();
                    const form = this.autoSourceForm;
                    const fetchConfig = this.parseJsonSafe(form.fetch_config_json);
                    const parserConfig = this.parseJsonSafe(form.parser_config_json);
                    const selectionConfig = this.parseJsonSafe(form.selection_config_json);
                    return {
                        id: form.id || null,
                        market_id: Number(form.market_id || 0),
                        is_active: !!form.is_active,
                        priority: Number(form.priority || 100),
                        source_type: form.source_type || 'api',
                        endpoint_url: String(fetchConfig.endpoint_url || form.endpoint_url || form.quick_endpoint_url || '').trim(),
                        http_method: String(fetchConfig.http_method || form.http_method || form.quick_http_method || 'GET').toUpperCase(),
                        lookup_date_mode: form.lookup_date_mode || 'ROUND_DATE',
                        lookup_date_offset_days: Number(form.lookup_date_offset_days || 0),
                        parser_type: String(parserConfig.parser_type || form.parser_type || 'JSON_PATH').toUpperCase(),
                        timeout_seconds: Number(form.timeout_seconds || 10),
                        effective_from: String(form.effective_from || '').trim(),
                        effective_to: String(form.effective_to || '').trim(),
                        pipeline_version: 'V2_CUTOVER',
                        fetch_strategy: String(fetchConfig.fetch_strategy || form.fetch_strategy || 'JSON_HTTP').toUpperCase(),
                        selection_stage: String(selectionConfig.selection_stage || form.selection_stage || 'POST_MAPPING').toUpperCase(),
                        supports_partial: !!form.supports_partial,
                        requires_browser: !!form.requires_browser,
                        revision_reason: String(form.revision_reason || '').trim(),
                        request_headers_json: this.parseJsonInput(form.request_headers_json, 'request_headers_json'),
                        request_query_template_json: this.parseJsonInput(form.request_query_template_json, 'request_query_template_json'),
                        request_body_template_json: this.parseJsonInput(form.request_body_template_json, 'request_body_template_json'),
                        parser_config_json: this.parseJsonInput(form.parser_config_json, 'parser_config_json'),
                        mapping_config_json: this.parseJsonInput(form.mapping_config_json, 'mapping_config_json'),
                        validation_config_json: this.parseJsonInput(form.validation_config_json, 'validation_config_json'),
                        retry_policy_json: this.parseJsonInput(form.retry_policy_json, 'retry_policy_json'),
                        fetch_config_json: this.parseJsonInput(form.fetch_config_json, 'fetch_config_json'),
                        selection_config_json: this.parseJsonInput(form.selection_config_json, 'selection_config_json'),
                        readiness_config_json: this.parseJsonInput(form.readiness_config_json, 'readiness_config_json'),
                    };
                },
                showApiMessage(res, fallbackTitle = 'ผลการทำงาน') {
                    const message = (res && res.data && res.data.message) ? res.data.message : 'ดำเนินการเสร็จสิ้น';
                    this.$bvModal.msgBoxOk(message, {
                        title: fallbackTitle,
                        size: 'sm',
                        buttonSize: 'sm',
                        centered: true,
                    });
                },
                assertApiSuccess(res, fallbackMessage = 'ดำเนินการไม่สำเร็จ') {
                    const data = (res && res.data) ? res.data : {};
                    const isSuccess = data && (data.success === true || data.status === true);
                    if (!isSuccess) {
                        throw new Error(String(data.message || fallbackMessage));
                    }
                },
                showApiError(error, fallback = 'เกิดข้อผิดพลาด') {
                    let message = fallback;
                    if (error && error.response && error.response.data) {
                        message = error.response.data.message || error.response.data.error || fallback;
                    }

                    this.$bvModal.msgBoxOk(message, {
                        title: 'ผิดพลาด',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        centered: true,
                    });
                },
                editdata(id, status, method) {
                    this.$bvModal.msgBoxConfirm('ต้องการเปลี่ยนสถานะหรือไม่?', {
                        title: 'ยืนยัน', size: 'sm', buttonSize: 'sm',
                        okVariant: 'danger', okTitle: 'ตกลง', cancelTitle: 'ยกเลิก',
                        centered: true,
                    }).then(value => {
                        if (!value) return;
                        this.$http.post("{{ route('admin.lotto.markets.edit') }}", { id, status, method })
                            .then(() => {
                                window.LaravelDataTables['dataTableBuilder'].draw(false);
                            });
                    });
                },
                editModal(id) {
                    this.code = null;
                    this.formaddedit = { group_id: '', name: '', name_en: '', name_kh: '', name_laos: '', logo: '', icon: '', logo_file: null, icon_file: null, code: '', draw_mode: 'manual', auto_open_time: '', auto_close_time: '', auto_result_time: '', result_url: '', is_enabled: 1 };
                    this.formmethod = 'edit';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.code = id;
                        this.loadData();
                        this.$refs.addedit.show();
                    });
                },
                addModal() {
                    this.code = null;
                    this.formaddedit = { group_id: '', name: '', name_en: '', name_kh: '', name_laos: '', logo: '', icon: '', logo_file: null, icon_file: null, code: '', draw_mode: 'manual', auto_open_time: '', auto_close_time: '', auto_result_time: '', result_url: '', is_enabled: 1 };
                    this.formmethod = 'add';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();
                    });
                },
                openAutoSourcesModal(marketId, marketName = '') {
                    const id = parseInt(marketId, 10);
                    if (!id) {
                        return;
                    }

                    this.autoSourcesModal.marketId = id;
                    const label = String(marketName || '').trim();
                    this.autoSourcesModal.title = label !== ''
                        ? `Auto Result Sources: ${label} (#${id})`
                        : `Auto Result Sources: Market #${id}`;
                    this.sourceTestDate = '';
                    this.sourceTestRunId = '';
                    this.$refs.autoSourcesModal.show();
                    this.loadAutoSources();
                },
                async loadAutoSources() {
                    const marketId = Number(this.autoSourcesModal.marketId || 0);
                    if (!marketId) {
                        return;
                    }

                    this.autoSourcesLoading = true;
                    try {
                        const res = await axios.get("{{ route('admin.lotto.result_sources.list') }}", {
                            params: {
                                market_id: marketId,
                            },
                        });

                        this.autoSourcesItems = (((res || {}).data || {}).data || {}).items || [];
                    } catch (error) {
                        this.showApiError(error, 'โหลดรายการ source ไม่สำเร็จ');
                    } finally {
                        this.autoSourcesLoading = false;
                    }
                },
                addAutoSource() {
                    this.sourceEditorMode = 'add';
                    this.sourceEditorTitle = 'เพิ่ม Auto Result Source';
                    this.activeSourceTab = 0;
                    this.autoSourceForm = this.defaultAutoSourceForm();
                    this.autoSourceForm.market_id = Number(this.autoSourcesModal.marketId || 0);
                    this.applyAutoSourceExample();
                    this.sourceTestDate = '{{ now()->format('Y-m-d') }}';
                    this.sourceTestRunId = '';
                    this.testRunResult = null;
                    this.$refs.sourceEditorModal.show();
                },
                async editAutoSource(sourceId) {
                    try {
                        const res = await axios.post("{{ route('admin.lotto.result_sources.loaddata') }}", { id: sourceId });
                        const d = ((res || {}).data || {}).data || {};
                        this.sourceEditorMode = 'edit';
                        this.sourceEditorTitle = `แก้ไข Auto Result Source #${d.id || sourceId}`;
                        this.activeSourceTab = 0;
                        this.autoSourceForm = {
                            ...this.defaultAutoSourceForm(),
                            ...d,
                            is_active: !!d.is_active,
                            supports_partial: !!d.supports_partial,
                            requires_browser: !!d.requires_browser,
                            request_headers_json: JSON.stringify(d.request_headers_json || {}, null, 2),
                            request_query_template_json: JSON.stringify(d.request_query_template_json || {}, null, 2),
                            request_body_template_json: JSON.stringify(d.request_body_template_json || {}, null, 2),
                            parser_config_json: JSON.stringify(d.parser_config_json || {}, null, 2),
                            mapping_config_json: JSON.stringify(d.mapping_config_json || {}, null, 2),
                            validation_config_json: JSON.stringify(d.validation_config_json || {}, null, 2),
                            retry_policy_json: JSON.stringify(d.retry_policy_json || {}, null, 2),
                            fetch_config_json: JSON.stringify(d.fetch_config_json || {}, null, 2),
                            selection_config_json: JSON.stringify(d.selection_config_json || {}, null, 2),
                            readiness_config_json: JSON.stringify(d.readiness_config_json || {}, null, 2),
                        };
                        this.syncUnifiedFromForm();
                        this.autoSourceForm.quick_endpoint_url = this.autoSourceForm.endpoint_url || this.autoSourceForm.quick_endpoint_url;
                        this.autoSourceForm.quick_http_method = this.autoSourceForm.http_method || this.autoSourceForm.quick_http_method;
                        this.sourceTestDate = '{{ now()->format('Y-m-d') }}';
                        this.sourceTestRunId = '';
                        this.testRunResult = null;
                        this.$refs.sourceEditorModal.show();
                    } catch (error) {
                        this.showApiError(error, 'โหลดข้อมูล source ไม่สำเร็จ');
                    }
                },
                async toggleAutoSource(item) {
                    const nextStatus = item.is_active ? 0 : 1;
                    try {
                        const res = await axios.post("{{ route('admin.lotto.result_sources.edit') }}", {
                            id: item.id,
                            status: nextStatus,
                        });
                        this.showApiMessage(res, 'ผลการเปลี่ยนสถานะ');
                        await this.loadAutoSources();
                        window.LaravelDataTables['dataTableBuilder'].draw(false);
                    } catch (error) {
                        this.showApiError(error, 'เปลี่ยนสถานะ source ไม่สำเร็จ');
                    }
                },
                deleteAutoSource(item) {
                    const sourceId = Number(item && item.id ? item.id : 0);
                    if (!sourceId) {
                        return;
                    }

                    this.$bvModal.msgBoxConfirm('ต้องการลบ Auto Result Source นี้หรือไม่?', {
                        title: 'ยืนยันการลบ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'ลบ',
                        cancelTitle: 'ยกเลิก',
                        centered: true,
                    }).then(async (confirmed) => {
                        if (!confirmed) {
                            return;
                        }

                        try {
                            const res = await axios.post("{{ route('admin.lotto.result_sources.delete') }}", { id: sourceId });
                            this.showApiMessage(res, 'ลบสำเร็จ');
                            await this.loadAutoSources();
                            window.LaravelDataTables['dataTableBuilder'].draw(false);
                        } catch (error) {
                            this.showApiError(error, 'ลบ source ไม่สำเร็จ');
                        }
                    });
                },
                async submitAutoSourceForm() {
                    try {
                        const payload = this.makeSourcePayload();
                        if (!payload.market_id) {
                            throw new Error('market_id ไม่ถูกต้อง');
                        }
                        if (!payload.endpoint_url) {
                            throw new Error('กรุณาระบุ Endpoint URL');
                        }

                        const url = this.sourceEditorMode === 'add'
                            ? "{{ route('admin.lotto.result_sources.create') }}"
                            : "{{ route('admin.lotto.result_sources.update') }}";

                        const res = await axios.post(url, { id: payload.id, data: payload });
                        this.assertApiSuccess(res, 'บันทึก source ไม่สำเร็จ');
                        this.showApiMessage(res, 'บันทึกสำเร็จ');
                        this.$refs.sourceEditorModal.hide();
                        await this.loadAutoSources();
                        window.LaravelDataTables['dataTableBuilder'].draw(false);
                    } catch (error) {
                        if (error instanceof Error && !error.response) {
                            this.showApiError(null, error.message);
                            return;
                        }
                        this.showApiError(error, 'บันทึก source ไม่สำเร็จ');
                    }
                },
                async previewAutoSource() {
                    try {
                        const payload = this.makeSourcePayload();
                        const res = await axios.post("{{ route('admin.lotto.result_sources.preview_config') }}", { data: payload });
                        const compiled = (((res || {}).data || {}).data || {}).compiled || {};
                        this.$bvModal.msgBoxOk(JSON.stringify(compiled, null, 2), {
                            title: 'Preview config',
                            size: 'xl',
                            buttonSize: 'sm',
                            centered: true,
                        });
                    } catch (error) {
                        if (error instanceof Error && !error.response) {
                            this.showApiError(null, error.message);
                            return;
                        }
                        this.showApiError(error, 'Preview ไม่สำเร็จ');
                    }
                },
                async validateAutoSource() {
                    try {
                        const payload = this.makeSourcePayload();
                        const res = await axios.post("{{ route('admin.lotto.result_sources.validate_config') }}", { data: payload });
                        this.showApiMessage(res, 'Validate config');
                    } catch (error) {
                        if (error instanceof Error && !error.response) {
                            this.showApiError(null, error.message);
                            return;
                        }
                        this.showApiError(error, 'Validate config ไม่ผ่าน');
                    }
                },
                async validateAutoSourceCutover() {
                    try {
                        const payload = this.makeSourcePayload();
                        const res = await axios.post("{{ route('admin.lotto.result_sources.validate_cutover') }}", {
                            id: payload.id,
                            data: payload,
                        });
                        this.showApiMessage(res, 'Validate cutover');
                    } catch (error) {
                        if (error instanceof Error && !error.response) {
                            this.showApiError(null, error.message);
                            return;
                        }
                        this.showApiError(error, 'Validate cutover ไม่ผ่าน');
                    }
                },
                async runAutoSourceTestByDate() {
                    if (!this.sourceTestDate) {
                        this.showApiError(null, 'กรุณาเลือกวันที่ทดสอบ');
                        return;
                    }

                    try {
                        const res = await axios.post("{{ route('admin.lotto.result_sources.test_fetch_by_date') }}", {
                            market_id: Number(this.autoSourceForm.market_id || this.autoSourcesModal.marketId || 0),
                            draw_date: this.sourceTestDate,
                        });

                        const data = ((res || {}).data || {}).data || {};
                        this.sourceTestRunId = data.run_id || '';
                        this.testRunResult = data;
                        const output = String(data.output || '').trim() || 'Dry-run สำเร็จ';
                        this.$bvModal.msgBoxOk(output, {
                            title: 'Dry-run Result',
                            size: 'xl',
                            buttonSize: 'sm',
                            centered: true,
                        });
                    } catch (error) {
                        this.showApiError(error, 'Dry-run ไม่สำเร็จ');
                    }
                },
                async showAutoSourceLogsByDate() {
                    if (!this.sourceTestDate) {
                        this.showApiError(null, 'กรุณาเลือกวันที่ก่อนดู log');
                        return;
                    }

                    try {
                        const res = await axios.get("{{ route('admin.lotto.result_sources.test_fetch_logs_by_date') }}", {
                            params: {
                                market_id: Number(this.autoSourceForm.market_id || this.autoSourcesModal.marketId || 0),
                                draw_date: this.sourceTestDate,
                                run_id: this.sourceTestRunId || undefined,
                                limit: 100,
                            },
                        });

                        const data = ((res || {}).data || {}).data || {};
                        this.sourceTestLogs = data.items || [];
                        this.$refs.sourceTestLogsModal.show();
                    } catch (error) {
                        this.showApiError(error, 'โหลด logs ไม่สำเร็จ');
                    }
                },
                showLogDetail(log) {
                    const detail = {
                        id: log.id,
                        request_url: log.request_url,
                        response_http_status: log.response_http_status,
                        duration_ms: log.duration_ms,
                        error_message: log.error_message,
                        response_body_preview: log.response_body_preview,
                        parsed_payload_json: log.parsed_payload_json,
                        normalized_result_json: log.normalized_result_json,
                        selection_debug_json: log.selection_debug_json,
                        trace_json: log.trace_json,
                    };

                    this.$bvModal.msgBoxOk(JSON.stringify(detail, null, 2), {
                        title: `Log #${log.id}`,
                        size: 'xl',
                        buttonSize: 'sm',
                        centered: true,
                    });
                },
                async loadData() {
                    const response = await axios.post("{{ route('admin.lotto.markets.loaddata') }}", { id: this.code });
                    const d = response.data.data;
                    this.formaddedit = {
                        group_id:   d.group_id,
                        name:       d.name,
                        name_en:    d.name_en || '',
                        name_kh:    d.name_kh || '',
                        name_laos:  d.name_laos || '',
                        logo:       d.logo || '',
                        icon:       d.icon || '',
                        logo_file:  null,
                        icon_file:  null,
                        code:       d.code,
                        draw_mode:  d.draw_mode || 'manual',
                        auto_open_time: d.auto_open_time ? String(d.auto_open_time).substring(0, 5) : '',
                        auto_close_time: d.auto_close_time ? String(d.auto_close_time).substring(0, 5) : '',
                        auto_result_time: d.auto_result_time ? String(d.auto_result_time).substring(0, 5) : '',
                        result_url: d.result_url || '',
                        is_enabled: d.is_enabled ? 1 : 0,
                    };
                },
                addEditSubmit() {
                    const validationMessage = this.validateAutoDrawConfig();
                    if (validationMessage) {
                        this.$bvModal.msgBoxOk(validationMessage, {
                            title: 'ข้อมูลไม่ครบ',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true,
                        });
                        return;
                    }

                    const url = this.formmethod === 'add'
                        ? "{{ route('admin.lotto.markets.create') }}"
                        : "{{ route('admin.lotto.markets.update') }}";
                    const formData = new FormData();
                    if (this.code) {
                        formData.append('id', this.code);
                    }

                    Object.keys(this.formaddedit)
                        .filter((key) => !['logo_file', 'icon_file'].includes(key))
                        .forEach((key) => {
                            formData.append(`data[${key}]`, this.formaddedit[key] ?? '');
                        });

                    if (this.formaddedit.logo_file) {
                        formData.append('logo_file', this.formaddedit.logo_file);
                    }

                    if (this.formaddedit.icon_file) {
                        formData.append('icon_file', this.formaddedit.icon_file);
                    }

                    axios.post(url, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                    })
                        .then(response => {
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm', buttonSize: 'sm', okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true,
                            });
                            this.$refs.addedit.hide();
                            window.LaravelDataTables['dataTableBuilder'].draw(false);
                        })
                        .catch(() => console.error('addEditSubmit error'));
                },
                validateAutoDrawConfig() {
                    const mode = this.formaddedit.draw_mode || 'manual';
                    const open = (this.formaddedit.auto_open_time || '').trim();
                    const close = (this.formaddedit.auto_close_time || '').trim();

                    if (mode === 'manual') {
                        return '';
                    }

                    if (!close) {
                        return 'โหมดงวดอัตโนมัติจำเป็นต้องระบุเวลาปิดรับ';
                    }

                    if (open && open === close) {
                        return 'เวลาเปิดรับและเวลาปิดรับต้องไม่เท่ากัน';
                    }

                    return '';
                },
            },
        });

        window.addModal = function () { window.app.addModal(); };
        window.editModal = function (id) { window.app.editModal(id); };
        window.openAutoSourcesModal = function (marketId, marketName) { window.app.openAutoSourcesModal(marketId, marketName); };
    </script>
@endpush
