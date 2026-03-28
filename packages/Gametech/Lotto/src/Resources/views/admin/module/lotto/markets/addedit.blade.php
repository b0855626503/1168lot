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
        <table class="table table-bordered table-sm mb-0 align-middle">
            <thead class="thead-light">
            <tr>
                <th class="text-center" style="width:80px;">#</th>
                <th class="text-center" style="width:90px;">Priority</th>
                <th class="text-center" style="width:90px;">สถานะ</th>
                <th>Endpoint</th>
                <th class="text-center" style="width:160px;">อัปเดตล่าสุด</th>
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
                <td class="text-center">
                    <span class="badge" :class="item.is_active ? 'badge-success' : 'badge-secondary'">@{{ item.is_active ? 'Active' : 'Inactive' }}</span>
                </td>
                <td class="text-break">@{{ item.endpoint_url || '-' }}</td>
                <td class="text-center">@{{ item.updated_at || '-' }}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-info btn-xs mr-1" @click="editAutoSource(item.id)">แก้ไข</button>
                    <button type="button" class="btn btn-xs"
                            :class="item.is_active ? 'btn-outline-danger' : 'btn-outline-success'"
                            @click="toggleAutoSource(item)">
                        @{{ item.is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}
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
                    <b-col cols="12" md="3">
                        <b-form-group label="Market ID">
                            <b-form-input size="sm" :value="autoSourceForm.market_id" readonly></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col cols="12" md="3">
                        <b-form-group label="Priority">
                            <b-form-input size="sm" type="number" min="1" max="9999" v-model="autoSourceForm.priority"></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col cols="12" md="3">
                        <b-form-group label="HTTP Method">
                            <b-form-select size="sm" :options="autoSourceOptions.httpMethods" v-model="autoSourceForm.http_method"></b-form-select>
                        </b-form-group>
                    </b-col>
                    <b-col cols="12" md="3">
                        <b-form-group label="Parser Type">
                            <b-form-select size="sm" :options="autoSourceOptions.parserTypes" v-model="autoSourceForm.parser_type"></b-form-select>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col cols="12" md="4">
                        <b-form-group label="Source Type">
                            <b-form-select size="sm" :options="autoSourceOptions.sourceTypes" v-model="autoSourceForm.source_type"></b-form-select>
                        </b-form-group>
                    </b-col>
                    <b-col cols="12" md="4">
                        <b-form-group label="Lookup Date Mode">
                            <b-form-select size="sm" :options="autoSourceOptions.lookupDateModes" v-model="autoSourceForm.lookup_date_mode"></b-form-select>
                        </b-form-group>
                    </b-col>
                    <b-col cols="12" md="4">
                        <b-form-group label="Lookup Offset (days)">
                            <b-form-input size="sm" type="number" min="-365" max="365" v-model="autoSourceForm.lookup_date_offset_days"></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col cols="12" md="4">
                        <b-form-group label="Timeout (seconds)">
                            <b-form-input size="sm" type="number" min="1" max="60" v-model="autoSourceForm.timeout_seconds"></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col cols="12" md="4">
                        <b-form-group label="Fetch Strategy">
                            <b-form-select size="sm" :options="autoSourceOptions.fetchStrategies" v-model="autoSourceForm.fetch_strategy"></b-form-select>
                        </b-form-group>
                    </b-col>
                    <b-col cols="12" md="4">
                        <b-form-group label="Selection Stage">
                            <b-form-select size="sm" :options="autoSourceOptions.selectionStages" v-model="autoSourceForm.selection_stage"></b-form-select>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-form-group label="Endpoint URL">
                    <b-form-input size="sm" v-model="autoSourceForm.endpoint_url" placeholder="https://..."></b-form-input>
                </b-form-group>

                <b-row>
                    <b-col cols="12" md="4">
                        <b-form-group label="Effective From (Y-m-d H:i:s)">
                            <b-form-input size="sm" v-model="autoSourceForm.effective_from"></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col cols="12" md="4">
                        <b-form-group label="Effective To (Y-m-d H:i:s)">
                            <b-form-input size="sm" v-model="autoSourceForm.effective_to"></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col cols="12" md="4" class="pt-3">
                        <b-form-checkbox v-model="autoSourceForm.is_active" switch>เปิดใช้งาน</b-form-checkbox>
                        <b-form-checkbox v-model="autoSourceForm.supports_partial" switch>supports_partial</b-form-checkbox>
                        <b-form-checkbox v-model="autoSourceForm.requires_browser" switch>requires_browser</b-form-checkbox>
                    </b-col>
                </b-row>

                <b-form-group label="เหตุผลการเปลี่ยนแปลง">
                    <b-form-input size="sm" v-model="autoSourceForm.revision_reason" placeholder="optional"></b-form-input>
                </b-form-group>
            </b-tab>

            <b-tab title="JSON หลัก">
                <div class="d-flex flex-wrap mb-2">
                    <button type="button" class="btn btn-outline-secondary btn-xs mr-1 mb-1" @click="applyAutoSourceExample">ใส่ตัวอย่าง JSON</button>
                    <button type="button" class="btn btn-outline-primary btn-xs mr-1 mb-1" @click="previewAutoSource">Preview</button>
                    <button type="button" class="btn btn-outline-info btn-xs mr-1 mb-1" @click="validateAutoSource">Validate</button>
                    <button type="button" class="btn btn-outline-warning btn-xs mr-1 mb-1" @click="validateAutoSourceCutover">Validate Cutover</button>
                </div>

                <b-row>
                    <b-col cols="12" md="6">
                        <b-form-group label="request_headers_json">
                            <b-form-textarea rows="4" v-model="autoSourceForm.request_headers_json"></b-form-textarea>
                        </b-form-group>
                    </b-col>
                    <b-col cols="12" md="6">
                        <b-form-group label="request_query_template_json">
                            <b-form-textarea rows="4" v-model="autoSourceForm.request_query_template_json"></b-form-textarea>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col cols="12" md="6">
                        <b-form-group label="request_body_template_json">
                            <b-form-textarea rows="4" v-model="autoSourceForm.request_body_template_json"></b-form-textarea>
                        </b-form-group>
                    </b-col>
                    <b-col cols="12" md="6">
                        <b-form-group label="fetch_config_json">
                            <b-form-textarea rows="4" v-model="autoSourceForm.fetch_config_json"></b-form-textarea>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col cols="12" md="6">
                        <b-form-group label="parser_config_json">
                            <b-form-textarea rows="4" v-model="autoSourceForm.parser_config_json"></b-form-textarea>
                        </b-form-group>
                    </b-col>
                    <b-col cols="12" md="6">
                        <b-form-group label="mapping_config_json">
                            <b-form-textarea rows="4" v-model="autoSourceForm.mapping_config_json"></b-form-textarea>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col cols="12" md="6">
                        <b-form-group label="selection_config_json">
                            <b-form-textarea rows="4" v-model="autoSourceForm.selection_config_json"></b-form-textarea>
                        </b-form-group>
                    </b-col>
                    <b-col cols="12" md="6">
                        <b-form-group label="validation_config_json">
                            <b-form-textarea rows="4" v-model="autoSourceForm.validation_config_json"></b-form-textarea>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col cols="12" md="6">
                        <b-form-group label="readiness_config_json">
                            <b-form-textarea rows="4" v-model="autoSourceForm.readiness_config_json"></b-form-textarea>
                        </b-form-group>
                    </b-col>
                    <b-col cols="12" md="6">
                        <b-form-group label="retry_policy_json">
                            <b-form-textarea rows="4" v-model="autoSourceForm.retry_policy_json"></b-form-textarea>
                        </b-form-group>
                    </b-col>
                </b-row>
            </b-tab>

            <b-tab title="ทดสอบตามวันที่">
                <b-row>
                    <b-col cols="12" md="4">
                        <b-form-group label="วันที่ทดสอบ">
                            <b-form-input size="sm" type="date" v-model="sourceTestDate"></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col cols="12" md="8">
                        <label class="d-block">ทดสอบ</label>
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
                        <small class="text-muted">ใช้แทนปุ่ม dry run / log จากเมนูงวดหวย โดยเลือกวันที่ที่นี่ได้ทันที</small>
                    </b-col>
                </b-row>
            </b-tab>
        </b-tabs>

        <div class="d-flex justify-content-end mt-3">
            <button type="submit" class="btn btn-success btn-sm">บันทึก Source</button>
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
                    sourceTestDate: '',
                    sourceTestRunId: '',
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

                    this.autoSourceForm.request_headers_json = JSON.stringify(example.request_headers_json, null, 2);
                    this.autoSourceForm.request_query_template_json = JSON.stringify(example.request_query_template_json, null, 2);
                    this.autoSourceForm.request_body_template_json = JSON.stringify(example.request_body_template_json, null, 2);
                    this.autoSourceForm.fetch_config_json = JSON.stringify(example.fetch_config_json, null, 2);
                    this.autoSourceForm.parser_config_json = JSON.stringify(example.parser_config_json, null, 2);
                    this.autoSourceForm.mapping_config_json = JSON.stringify(example.mapping_config_json, null, 2);
                    this.autoSourceForm.selection_config_json = JSON.stringify(example.selection_config_json, null, 2);
                    this.autoSourceForm.validation_config_json = JSON.stringify(example.validation_config_json, null, 2);
                    this.autoSourceForm.readiness_config_json = JSON.stringify(example.readiness_config_json, null, 2);
                    this.autoSourceForm.retry_policy_json = JSON.stringify(example.retry_policy_json, null, 2);
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
                    const form = this.autoSourceForm;
                    return {
                        id: form.id || null,
                        market_id: Number(form.market_id || 0),
                        is_active: !!form.is_active,
                        priority: Number(form.priority || 100),
                        source_type: form.source_type || 'api',
                        endpoint_url: String(form.endpoint_url || '').trim(),
                        http_method: String(form.http_method || 'GET').toUpperCase(),
                        lookup_date_mode: form.lookup_date_mode || 'ROUND_DATE',
                        lookup_date_offset_days: Number(form.lookup_date_offset_days || 0),
                        parser_type: String(form.parser_type || 'JSON_PATH').toUpperCase(),
                        timeout_seconds: Number(form.timeout_seconds || 10),
                        effective_from: String(form.effective_from || '').trim(),
                        effective_to: String(form.effective_to || '').trim(),
                        pipeline_version: 'V2_CUTOVER',
                        fetch_strategy: String(form.fetch_strategy || 'JSON_HTTP').toUpperCase(),
                        selection_stage: String(form.selection_stage || 'POST_MAPPING').toUpperCase(),
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
                openAutoSourcesModal(marketId) {
                    const id = parseInt(marketId, 10);
                    if (!id) {
                        return;
                    }

                    this.autoSourcesModal.marketId = id;
                    this.autoSourcesModal.title = `Auto Result Sources: Market #${id}`;
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
                        this.sourceTestDate = '';
                        this.sourceTestRunId = '';
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
        window.openAutoSourcesModal = function (marketId) { window.app.openAutoSourcesModal(marketId); };
    </script>
@endpush
