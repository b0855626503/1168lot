<b-modal ref="addeditSource" id="addeditSource" centered size="lg" title="ตั้งค่า Auto Result Source" :hide-footer="true" @shown="onModalShown" @hidden="onModalHidden">
    <b-form v-if="showSourceForm" @submit.prevent="submitSourceForm">
        <b-tabs v-model="activeSourceTab" content-class="pt-3">
            <b-tab title="ทั่วไป">
                <b-row>
                    <b-col md="6">
                        <b-form-group label="ตลาด">
                            <select ref="marketSelect" class="form-control form-control-sm" required @change="onNativeMarketChange">
                                <option value="">-- เลือกตลาด --</option>
                                @foreach(($marketOptionsGrouped ?? []) as $group)
                                    <optgroup label="{{ $group['label'] ?? '-' }}">
                                        @foreach(($group['options'] ?? []) as $market)
                                            <option value="{{ (string) ($market['value'] ?? '') }}"
                                                    data-logo="{{ $market['logo'] ?? '' }}">
                                                {{ $market['text'] ?? '-' }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">เลือกตลาดหวยที่ source นี้จะใช้ดึงผล</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="3">
                        <b-form-group label="Priority">
                            <b-form-input size="sm" type="number" min="1" v-model="sourceForm.priority"></b-form-input>
                            <small class="text-muted d-block mt-1">เลขน้อยทำงานก่อน ใช้จัดลำดับหลาย source</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="3">
                        <b-form-group label="Timeout (sec)">
                            <b-form-input size="sm" type="number" min="1" max="60" v-model="sourceForm.timeout_seconds"></b-form-input>
                            <small class="text-muted d-block mt-1">เวลารอ response จาก source ก่อนถือว่าล้มเหลว</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-alert show variant="info" class="py-2">
                    โหมดฟอร์มนี้เป็น V2-only: ค่า <code>endpoint_url</code>, <code>http_method</code>, <code>parser_type</code>, <code>fetch_strategy</code>, <code>selection_stage</code> จะ derive จาก JSON config อัตโนมัติ
                </b-alert>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="Derived Endpoint URL">
                            <b-form-input size="sm" :value="derivedEndpointUrl" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">อ่านจาก <code>fetch_config_json.endpoint_url</code></small>
                        </b-form-group>
                    </b-col>
                    <b-col md="3">
                        <b-form-group label="Derived HTTP Method">
                            <b-form-input size="sm" :value="derivedHttpMethod" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">อ่านจาก <code>fetch_config_json.http_method</code></small>
                        </b-form-group>
                    </b-col>
                    <b-col md="3">
                        <b-form-group label="Derived Parser Type">
                            <b-form-input size="sm" :value="derivedParserType" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">อ่านจาก <code>parser_config_json.parser_type</code></small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="8">
                        <b-form-group label="Lookup Date Mode">
                            <b-form-select size="sm" :options="lookupDateModes" v-model="sourceForm.lookup_date_mode"></b-form-select>
                            <small class="text-muted d-block mt-1">กำหนดว่าจะอ้างวันงวดจากค่าไหนตอนยิง request</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="4">
                        <b-form-group label="Offset Days">
                            <b-form-input size="sm" type="number" min="-365" max="365" v-model="sourceForm.lookup_date_offset_days"></b-form-input>
                            <small class="text-muted d-block mt-1">เลื่อนวันงวดจากฐาน เช่น -1 คือวันก่อนหน้า</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="Effective From (Y-m-d H:i:s)">
                            <b-form-input size="sm" v-model="sourceForm.effective_from"></b-form-input>
                            <small class="text-muted d-block mt-1">วันเริ่มใช้งาน source นี้ (ว่างได้)</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="Effective To (Y-m-d H:i:s)">
                            <b-form-input size="sm" v-model="sourceForm.effective_to"></b-form-input>
                            <small class="text-muted d-block mt-1">วันสิ้นสุดใช้งาน source นี้ (ว่างได้)</small>
                        </b-form-group>
                    </b-col>
                </b-row>
            </b-tab>

            <b-tab title="Pipeline">
                <b-row>
                    <b-col md="4">
                        <b-form-group label="Pipeline Version" :class="{ 'source-risk-input': isRiskyCutover }">
                            <b-form-input size="sm" value="V2_CUTOVER" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">ล็อกเป็นเวอร์ชันล่าสุด</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="4">
                        <b-form-group label="Derived Fetch Strategy">
                            <b-form-input size="sm" :value="derivedFetchStrategy" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">อ่านจาก <code>fetch_config_json.fetch_strategy</code></small>
                        </b-form-group>
                    </b-col>
                    <b-col md="4">
                        <b-form-group label="Derived Selection Stage">
                            <b-form-input size="sm" :value="derivedSelectionStage" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">อ่านจาก <code>selection_config_json.selection_stage</code></small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="3">
                        <b-form-group label="Supports Partial">
                            <b-form-checkbox v-model="sourceForm.supports_partial" switch>เปิดใช้งาน</b-form-checkbox>
                            <small class="text-muted d-block mt-1">อนุญาตผลบางช่องไม่ครบได้</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="3">
                        <b-form-group label="Requires Browser">
                            <b-form-checkbox v-model="sourceForm.requires_browser" switch>เปิดใช้งาน</b-form-checkbox>
                            <small class="text-muted d-block mt-1">source นี้ต้องใช้ browser worker</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="3">
                        <b-form-group label="Shadow Enabled">
                            <b-form-checkbox v-model="sourceForm.shadow_enabled" switch>เปิดใช้งาน</b-form-checkbox>
                            <small class="text-muted d-block mt-1">รัน old+v2 เทียบผล แต่ยังไม่สลับผลจริง</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="3">
                        <b-form-group label="Cutover Enabled" :class="{ 'source-risk-input': isRiskyCutover }">
                            <b-form-checkbox v-model="sourceForm.cutover_enabled" switch>เปิดใช้งาน</b-form-checkbox>
                            <small class="text-muted d-block mt-1">ใช้ผลจาก v2 เป็นผลหลักของระบบ</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-alert v-if="isRiskyCutover" show variant="warning" class="py-2">
                    โหมด Cutover มีผลกับผลลัพธ์จริงของระบบ กรุณากด Validate Config และ Validate Cutover ก่อนบันทึกทุกครั้ง
                </b-alert>
            </b-tab>

            <b-tab title="Quick Setup">
                <b-alert show variant="success" class="py-2">
                    โหมดง่าย: กรอกข้อมูลพื้นฐาน แล้วกด <strong>Generate Pipeline JSON</strong> ระบบจะสร้าง config ให้ทันที
                </b-alert>

                <b-row>
                    <b-col md="8">
                        <b-form-group label="URL ผลหวย">
                            <b-form-input size="sm" v-model="sourceForm.quick_endpoint_url" placeholder="https://example.com/result"></b-form-input>
                            <small class="text-muted d-block mt-1">ลิงก์ API/เว็บที่ระบบจะดึงข้อมูล</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="4">
                        <b-form-group label="HTTP Method">
                            <b-form-select size="sm" v-model="sourceForm.quick_http_method" :options="httpMethods"></b-form-select>
                            <small class="text-muted d-block mt-1">ส่วนใหญ่ใช้ GET</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="Path ของวันที่">
                            <b-form-input size="sm" v-model="sourceForm.quick_draw_date_path" placeholder="$.date"></b-form-input>
                            <small class="text-muted d-block mt-1">JSONPath ของวันที่ในผลลัพธ์</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="รูปแบบวันที่ต้นทาง">
                            <b-form-select size="sm" v-model="sourceForm.quick_draw_date_from_format" :options="quickDateFormats"></b-form-select>
                            <small class="text-muted d-block mt-1">เช่น <code>d/m/Y</code> หรือ <code>Y-m-d</code></small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="8">
                        <b-form-group label="Path ของรางวัลที่ 1">
                            <b-form-input size="sm" v-model="sourceForm.quick_first_prize_paths" placeholder="$.results.prize_1st หรือ $.lotto_2,$.lotto_3,$.lotto_4"></b-form-input>
                            <small class="text-muted d-block mt-1">ถ้าต้องต่อหลายช่อง ให้คั่นด้วย comma</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="4">
                        <b-form-group label="เก็บท้ายกี่หลัก (รางวัลที่ 1)">
                            <b-form-input size="sm" type="number" min="1" max="10" v-model="sourceForm.quick_first_prize_take_right"></b-form-input>
                            <small class="text-muted d-block mt-1">ปกติใช้ 3</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="8">
                        <b-form-group label="Path ของเลขท้าย 2 ตัว">
                            <b-form-input size="sm" v-model="sourceForm.quick_last2_paths" placeholder="$.results.prize_2nd หรือ $.lotto_1,$.lotto_2"></b-form-input>
                            <small class="text-muted d-block mt-1">ถ้าต้องต่อหลายช่อง ให้คั่นด้วย comma</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="4">
                        <b-form-group label="เก็บท้ายกี่หลัก (เลขท้าย)">
                            <b-form-input size="sm" type="number" min="1" max="10" v-model="sourceForm.quick_last2_take_right"></b-form-input>
                            <small class="text-muted d-block mt-1">ปกติใช้ 2</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <div class="d-flex">
                    <button type="button" class="btn btn-primary btn-sm mr-2" @click="generateQuickPipelineJson">Generate Pipeline JSON</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="applyQuickPresetLaosVip">Preset: Laos VIP</button>
                </div>
            </b-tab>

            <b-tab title="Configs JSON">
                <b-form-group label="Pipeline Config JSON (Single Source of Truth)">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">แก้ config หลักที่ช่องนี้ช่องเดียว ระบบจะแตกไป field ย่อยให้อัตโนมัติก่อน preview/validate/save</small>
                        <div>
                            <button type="button" class="btn btn-outline-secondary btn-xs mr-1" @click="applyJsonExample('unified_pipeline_json')">Insert Starter</button>
                            <button type="button" class="btn btn-outline-secondary btn-xs mr-1" @click="syncUnifiedFromForm">Refresh From Current</button>
                            <button type="button" class="btn btn-outline-primary btn-xs" @click="applyUnifiedToForm">Apply To Fields</button>
                        </div>
                    </div>
                    <b-form-textarea rows="16" v-model="sourceForm.unified_pipeline_json"></b-form-textarea>
                    <small class="text-muted d-block mt-1">
                        โครงสร้างหลักที่ต้องมี: <code>fetch_config_json</code>, <code>parser_config_json</code>, <code>mapping_config_json</code>, <code>selection_config_json</code>, <code>validation_config_json</code>, <code>readiness_config_json</code>
                    </small>
                </b-form-group>

                <b-alert show variant="light" class="py-2">
                    ช่อง JSON ย่อยถูกซ่อนจากหน้า UI เพื่อลดความสับสน ระบบจะ map ให้เองจาก JSON ก้อนเดียวนี้
                </b-alert>
            </b-tab>

            <b-tab title="Governance">
                <b-row>
                    <b-col md="8">
                        <b-form-group label="Revision Reason">
                            <b-form-input size="sm" v-model="sourceForm.revision_reason" placeholder="เหตุผลการเปลี่ยนแปลง"></b-form-input>
                            <small class="text-muted d-block mt-1">อธิบายว่าปรับ config เพราะอะไร เพื่อเก็บประวัติ revision</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="4" class="d-flex align-items-end">
                        <small class="text-muted mb-3">แนะนำให้กด Preview และ Validate ทุกครั้งก่อนบันทึกหรือ cutover</small>
                    </b-col>
                </b-row>
            </b-tab>
        </b-tabs>

        <div class="d-flex justify-content-between">
            <div>
                <button type="button" class="btn btn-outline-primary btn-sm mr-1" @click="previewSourceConfig">Preview Config</button>
                <button type="button" class="btn btn-outline-info btn-sm mr-1" @click="validateSourceConfig">Validate Config</button>
                <button type="button" class="btn btn-outline-warning btn-sm" @click="validateSourceCutover">Validate Cutover</button>
            </div>
            <button type="submit" class="btn btn-success btn-sm">บันทึก</button>
        </div>
    </b-form>
</b-modal>

@push('styles')
    <style>
        #addeditSource .select2-container--default .select2-selection--single {
            height: calc(1.5em + .5rem + 2px);
            min-height: calc(1.5em + .5rem + 2px);
            padding: 0;
            display: flex;
            align-items: center;
        }

        #addeditSource .select2-container--default .select2-selection--single .select2-selection__rendered {
            width: 100%;
            padding-left: .5rem;
            padding-right: 1.75rem;
            line-height: normal;
            display: flex !important;
            align-items: center;
            min-height: calc(1.5em + .5rem + 2px);
            overflow: visible;
        }

        #addeditSource .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
            right: .35rem;
        }

        .select2-container .lotto-market-option {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .select2-container .lotto-market-option__logo {
            width: 20px;
            height: 20px;
            min-width: 20px;
            object-fit: cover;
            border-radius: 50%;
            border: 1px solid #e5e7eb;
            background: #fff;
        }

        .select2-container .lotto-market-option__text {
            display: block;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        #addeditSource .btn-xs {
            font-size: 11px;
            line-height: 1.2;
            padding: 2px 8px;
        }

        #addeditSource .source-risk-input label {
            color: #8a6d3b;
            font-weight: 600;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    <script type="module">
        window.sourceFormApp = new Vue({
            el: '#app',
            data() {
                return {
                    showSourceForm: true,
                    activeSourceTab: 0,
                    sourceFormMethod: 'add',
                    sourceId: null,
                    lookupDateModes: @json($lookupDateModes ?? []),
                    quickDateFormats: [
                        { value: 'd/m/Y', text: 'd/m/Y (เช่น 27/03/2026)' },
                        { value: 'Y-m-d', text: 'Y-m-d (เช่น 2026-03-27)' },
                        { value: 'd-m-Y', text: 'd-m-Y (เช่น 27-03-2026)' },
                        { value: 'Y/m/d', text: 'Y/m/d (เช่น 2026/03/27)' },
                    ],
                    parserTypes: @json($parserTypes ?? []),
                    sourceTypes: @json($sourceTypes ?? []),
                    httpMethods: @json($httpMethods ?? []),
                    pipelineVersions: @json($pipelineVersions ?? []),
                    fetchStrategies: @json($fetchStrategies ?? []),
                    selectionStages: @json($selectionStages ?? []),
                    jsonExamples: this.buildJsonExamples(),
                    sourceForm: this.newSourceForm(),
                    isSyncingMarketSelect: false,
                };
            },
            computed: {
                isRiskyCutover() {
                    return this.sourceForm.pipeline_version === 'V2_CUTOVER' || !!this.sourceForm.cutover_enabled;
                },
                parsedUnifiedConfig() {
                    return this.parseJsonSafe(this.sourceForm.unified_pipeline_json);
                },
                parsedFetchConfig() {
                    return this.parsedUnifiedConfig.fetch_config_json || this.parseJsonSafe(this.sourceForm.fetch_config_json);
                },
                parsedParserConfig() {
                    return this.parsedUnifiedConfig.parser_config_json || this.parseJsonSafe(this.sourceForm.parser_config_json);
                },
                parsedSelectionConfig() {
                    return this.parsedUnifiedConfig.selection_config_json || this.parseJsonSafe(this.sourceForm.selection_config_json);
                },
                derivedEndpointUrl() {
                    return String(this.parsedFetchConfig.endpoint_url || this.sourceForm.endpoint_url || '');
                },
                derivedHttpMethod() {
                    return String(this.parsedFetchConfig.http_method || this.sourceForm.http_method || 'GET').toUpperCase();
                },
                derivedParserType() {
                    return String(this.parsedParserConfig.parser_type || this.sourceForm.parser_type || 'JSON_PATH').toUpperCase();
                },
                derivedFetchStrategy() {
                    return String(this.parsedFetchConfig.fetch_strategy || this.sourceForm.fetch_strategy || 'JSON_HTTP').toUpperCase();
                },
                derivedSelectionStage() {
                    return String(this.parsedSelectionConfig.selection_stage || this.sourceForm.selection_stage || 'POST_MAPPING').toUpperCase();
                },
            },
            methods: {
                buildJsonExamples() {
                    const unifiedStarter = {
                        request_headers_json: {
                            Accept: 'application/json',
                            'User-Agent': 'LottoFetcher/2.0',
                        },
                        request_query_template_json: {
                            draw_date: '__DRAW_DATE__',
                            lang: 'th',
                        },
                        request_body_template_json: {
                            market_key: '__MARKET_KEY__',
                            draw_date: '__DRAW_DATE__',
                        },
                        fetch_config_json: {
                            fetch_strategy: 'JSON_HTTP',
                            endpoint_url: 'https://example.com/result',
                            http_method: 'GET',
                            headers: {},
                            query: {},
                            timeout_seconds: 10,
                        },
                        parser_config_json: {
                            version: 2,
                            mode: 'single_payload',
                            parser_type: 'JSON_PATH',
                            fields: {
                                draw_date_raw: { type: 'JSON_PATH', path: '$.date' },
                                first_prize_raw: { type: 'JSON_PATH', path: '$.results.prize_1st' },
                                last_2_raw: { type: 'JSON_PATH', path: '$.results.prize_2nd' },
                            },
                        },
                        mapping_config_json: {
                            fields: {
                                draw_date: { from: 'draw_date_raw', transforms: [{ op: 'date', from: 'Y-m-d', to: 'Y-m-d' }] },
                                first_prize: { from: 'first_prize_raw', transforms: [{ op: 'digits_only' }, { op: 'right', length: 3 }] },
                                last_2_digits: { from: 'last_2_raw', transforms: [{ op: 'digits_only' }, { op: 'right', length: 2 }] },
                            },
                        },
                        selection_config_json: {
                            selection_stage: 'PRE_MAPPING',
                            strategy: 'strict_single_match',
                            date_field: 'draw_date_raw',
                            required_fields: [],
                            meta: {
                                candidate_draw_date_offset_days: 0,
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

                    return {
                        unified_pipeline_json: unifiedStarter,
                        request_headers_json: {
                            Accept: 'application/json',
                            'User-Agent': 'LottoFetcher/2.0',
                        },
                        request_query_template_json: {
                            draw_date: '__DRAW_DATE__',
                            lang: 'th',
                        },
                        request_body_template_json: {
                            market_key: '__MARKET_KEY__',
                            draw_date: '__DRAW_DATE__',
                        },
                        fetch_config_json: {
                            fetch_strategy: 'JSON_HTTP',
                            endpoint_url: 'https://example.com/result',
                            http_method: 'GET',
                            headers: {},
                            query: {},
                            timeout_seconds: 10,
                        },
                        parser_config_json: {
                            version: 2,
                            mode: 'single_payload',
                            parser_type: 'JSON_PATH',
                            fields: {
                                draw_date_raw: { type: 'JSON_PATH', path: '$.date' },
                                first_prize_raw: { type: 'JSON_PATH', path: '$.results.prize_1st' },
                                last_2_raw: { type: 'JSON_PATH', path: '$.results.prize_2nd' },
                            },
                        },
                        mapping_config_json: {
                            fields: {
                                draw_date: { from: 'draw_date_raw', transforms: [{ op: 'date', from: 'Y-m-d', to: 'Y-m-d' }] },
                                first_prize: { from: 'first_prize_raw', transforms: [{ op: 'digits_only' }, { op: 'right', length: 3 }] },
                                last_2_digits: { from: 'last_2_raw', transforms: [{ op: 'digits_only' }, { op: 'right', length: 2 }] },
                            },
                        },
                        selection_config_json: {
                            selection_stage: 'PRE_MAPPING',
                            strategy: 'strict_single_match',
                            date_field: 'draw_date_raw',
                            required_fields: [],
                            meta: {
                                candidate_draw_date_offset_days: 0,
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
                },
                newSourceForm() {
                    return {
                        id: null,
                        market_id: '',
                        is_active: true,
                        priority: 100,
                        source_type: 'api',
                        endpoint_url: '',
                        http_method: 'GET',
                        request_headers_json: '',
                        request_query_template_json: '',
                        request_body_template_json: '',
                        lookup_date_mode: 'ROUND_DATE',
                        lookup_date_offset_days: 0,
                        parser_type: 'JSON_PATH',
                        parser_config_json: '',
                        mapping_config_json: '',
                        fetch_config_json: '',
                        selection_config_json: '',
                        validation_config_json: '',
                        readiness_config_json: '',
                        retry_policy_json: '',
                        unified_pipeline_json: '',
                        timeout_seconds: 10,
                        pipeline_version: 'V2_CUTOVER',
                        fetch_strategy: 'JSON_HTTP',
                        selection_stage: 'POST_MAPPING',
                        supports_partial: false,
                        requires_browser: false,
                        shadow_enabled: false,
                        cutover_enabled: false,
                        revision_reason: '',
                        effective_from: '',
                        effective_to: '',
                        quick_endpoint_url: '',
                        quick_http_method: 'GET',
                        quick_draw_date_path: '$.date',
                        quick_draw_date_from_format: 'd/m/Y',
                        quick_first_prize_paths: '',
                        quick_first_prize_take_right: 3,
                        quick_last2_paths: '',
                        quick_last2_take_right: 2,
                    };
                },
                toJsonText(value) {
                    if (!value || (Array.isArray(value) && value.length === 0) || (typeof value === 'object' && Object.keys(value).length === 0)) {
                        return '';
                    }

                    return JSON.stringify(value, null, 2);
                },
                parseJsonSafe(text) {
                    if (!text || String(text).trim() === '') {
                        return {};
                    }

                    try {
                        const parsed = JSON.parse(text);
                        return parsed && typeof parsed === 'object' ? parsed : {};
                    } catch (e) {
                        return {};
                    }
                },
                buildUnifiedConfigObject() {
                    return {
                        request_headers_json: this.parseJsonSafe(this.sourceForm.request_headers_json),
                        request_query_template_json: this.parseJsonSafe(this.sourceForm.request_query_template_json),
                        request_body_template_json: this.parseJsonSafe(this.sourceForm.request_body_template_json),
                        fetch_config_json: this.parseJsonSafe(this.sourceForm.fetch_config_json),
                        parser_config_json: this.parseJsonSafe(this.sourceForm.parser_config_json),
                        mapping_config_json: this.parseJsonSafe(this.sourceForm.mapping_config_json),
                        selection_config_json: this.parseJsonSafe(this.sourceForm.selection_config_json),
                        validation_config_json: this.parseJsonSafe(this.sourceForm.validation_config_json),
                        readiness_config_json: this.parseJsonSafe(this.sourceForm.readiness_config_json),
                        retry_policy_json: this.parseJsonSafe(this.sourceForm.retry_policy_json),
                    };
                },
                syncUnifiedFromForm() {
                    const unified = this.buildUnifiedConfigObject();
                    this.sourceForm.unified_pipeline_json = JSON.stringify(unified, null, 2);
                    this.populateQuickFromUnified(unified);
                },
                applyUnifiedToForm() {
                    const unified = this.parseJsonSafe(this.sourceForm.unified_pipeline_json);
                    if (!unified || typeof unified !== 'object') {
                        return;
                    }

                    const assignJson = (key) => {
                        const value = unified[key];
                        this.sourceForm[key] = value && typeof value === 'object'
                            ? JSON.stringify(value, null, 2)
                            : '';
                    };

                    assignJson('request_headers_json');
                    assignJson('request_query_template_json');
                    assignJson('request_body_template_json');
                    assignJson('fetch_config_json');
                    assignJson('parser_config_json');
                    assignJson('mapping_config_json');
                    assignJson('selection_config_json');
                    assignJson('validation_config_json');
                    assignJson('readiness_config_json');
                    assignJson('retry_policy_json');
                    this.populateQuickFromUnified(unified);
                },
                populateQuickFromUnified(unifiedConfig = null) {
                    const unified = unifiedConfig && typeof unifiedConfig === 'object'
                        ? unifiedConfig
                        : this.parseJsonSafe(this.sourceForm.unified_pipeline_json);

                    const fetchConfig = (unified.fetch_config_json && typeof unified.fetch_config_json === 'object')
                        ? unified.fetch_config_json
                        : {};
                    const parserConfig = (unified.parser_config_json && typeof unified.parser_config_json === 'object')
                        ? unified.parser_config_json
                        : {};
                    const mappingConfig = (unified.mapping_config_json && typeof unified.mapping_config_json === 'object')
                        ? unified.mapping_config_json
                        : {};

                    const fields = (parserConfig.fields && typeof parserConfig.fields === 'object')
                        ? parserConfig.fields
                        : {};
                    const mapFields = (mappingConfig.fields && typeof mappingConfig.fields === 'object')
                        ? mappingConfig.fields
                        : {};

                    this.sourceForm.quick_endpoint_url = String(fetchConfig.endpoint_url || this.sourceForm.quick_endpoint_url || '');
                    this.sourceForm.quick_http_method = String(fetchConfig.http_method || this.sourceForm.quick_http_method || 'GET').toUpperCase();
                    this.sourceForm.quick_draw_date_path = String((fields.draw_date_raw || {}).path || this.sourceForm.quick_draw_date_path || '$.date');

                    const drawDateTransforms = Array.isArray((mapFields.draw_date || {}).transforms)
                        ? mapFields.draw_date.transforms
                        : [];
                    const drawDateTransform = drawDateTransforms.find((item) => item && String(item.op || '').toLowerCase() === 'date');
                    if (drawDateTransform && drawDateTransform.from) {
                        this.sourceForm.quick_draw_date_from_format = String(drawDateTransform.from);
                    }

                    const resolvePathsFromRule = (rule, prefix) => {
                        if (!rule || typeof rule !== 'object') {
                            return [];
                        }

                        if (rule.from && fields[rule.from] && fields[rule.from].path) {
                            return [String(fields[rule.from].path)];
                        }

                        if (Array.isArray(rule.from_fields)) {
                            return rule.from_fields
                                .map((fieldName) => fields[fieldName] && fields[fieldName].path ? String(fields[fieldName].path) : '')
                                .filter((path) => path !== '');
                        }

                        return Object.keys(fields)
                            .filter((fieldName) => fieldName.startsWith(prefix) && fields[fieldName] && fields[fieldName].path)
                            .sort()
                            .map((fieldName) => String(fields[fieldName].path));
                    };

                    const resolveRightDigits = (rule, fallback) => {
                        const transforms = Array.isArray((rule || {}).transforms) ? rule.transforms : [];
                        const rightTransform = transforms.find((item) => item && String(item.op || '').toLowerCase() === 'right');
                        const len = rightTransform ? Number(rightTransform.length || 0) : 0;
                        return len > 0 ? len : fallback;
                    };

                    const firstPrizeRule = mapFields.first_prize || {};
                    const last2Rule = mapFields.last_2_digits || {};

                    const firstPrizePaths = resolvePathsFromRule(firstPrizeRule, 'first_prize_raw_');
                    const last2Paths = resolvePathsFromRule(last2Rule, 'last_2_raw_');

                    if (firstPrizePaths.length > 0) {
                        this.sourceForm.quick_first_prize_paths = firstPrizePaths.join(',');
                    }
                    if (last2Paths.length > 0) {
                        this.sourceForm.quick_last2_paths = last2Paths.join(',');
                    }

                    this.sourceForm.quick_first_prize_take_right = resolveRightDigits(firstPrizeRule, this.sourceForm.quick_first_prize_take_right || 3);
                    this.sourceForm.quick_last2_take_right = resolveRightDigits(last2Rule, this.sourceForm.quick_last2_take_right || 2);
                },
                splitQuickPaths(text) {
                    return String(text || '')
                        .split(',')
                        .map(v => String(v || '').trim())
                        .filter(v => v !== '');
                },
                applyQuickPresetLaosVip() {
                    this.sourceForm.quick_endpoint_url = 'https://laosviplot.com/result';
                    this.sourceForm.quick_http_method = 'GET';
                    this.sourceForm.quick_draw_date_path = '$.date';
                    this.sourceForm.quick_draw_date_from_format = 'd/m/Y';
                    this.sourceForm.quick_first_prize_paths = '$.lotto_2,$.lotto_3,$.lotto_4';
                    this.sourceForm.quick_first_prize_take_right = 3;
                    this.sourceForm.quick_last2_paths = '$.lotto_1,$.lotto_2';
                    this.sourceForm.quick_last2_take_right = 2;
                },
                generateQuickPipelineJson() {
                    try {
                        const endpointUrl = String(this.sourceForm.quick_endpoint_url || '').trim();
                        if (endpointUrl === '') {
                            throw new Error('กรุณาใส่ URL ผลหวยใน Quick Setup');
                        }

                        const drawDatePath = String(this.sourceForm.quick_draw_date_path || '').trim();
                        if (drawDatePath === '') {
                            throw new Error('กรุณาใส่ Path ของวันที่');
                        }

                        const firstPrizePaths = this.splitQuickPaths(this.sourceForm.quick_first_prize_paths);
                        const last2Paths = this.splitQuickPaths(this.sourceForm.quick_last2_paths);
                        if (firstPrizePaths.length === 0) {
                            throw new Error('กรุณาใส่ Path ของรางวัลที่ 1');
                        }
                        if (last2Paths.length === 0) {
                            throw new Error('กรุณาใส่ Path ของเลขท้าย 2 ตัว');
                        }

                        const parserFields = {
                            draw_date_raw: { type: 'JSON_PATH', path: drawDatePath },
                        };

                        const firstPrizeRawFields = firstPrizePaths.map((path, idx) => {
                            const key = `first_prize_raw_${idx + 1}`;
                            parserFields[key] = { type: 'JSON_PATH', path };
                            return key;
                        });

                        const last2RawFields = last2Paths.map((path, idx) => {
                            const key = `last_2_raw_${idx + 1}`;
                            parserFields[key] = { type: 'JSON_PATH', path };
                            return key;
                        });

                        const buildComposeRule = (rawFields, rightDigits) => {
                            const transforms = [{ op: 'digits_only' }];
                            if (rightDigits > 0) {
                                transforms.push({ op: 'right', length: Number(rightDigits) });
                            }

                            if (rawFields.length === 1) {
                                return {
                                    from: rawFields[0],
                                    transforms,
                                };
                            }

                            return {
                                from_fields: rawFields,
                                join: '',
                                transforms,
                            };
                        };

                        this.sourceForm.unified_pipeline_json = JSON.stringify({
                            request_headers_json: {},
                            request_query_template_json: {},
                            request_body_template_json: {},
                            fetch_config_json: {
                                fetch_strategy: 'JSON_HTTP',
                                endpoint_url: endpointUrl,
                                http_method: String(this.sourceForm.quick_http_method || 'GET').toUpperCase(),
                                headers: {},
                                query: {},
                                timeout_seconds: Number(this.sourceForm.timeout_seconds || 10),
                            },
                            parser_config_json: {
                                version: 2,
                                mode: 'single_payload',
                                parser_type: 'JSON_PATH',
                                fields: parserFields,
                            },
                            mapping_config_json: {
                                fields: {
                                    draw_date: {
                                        from: 'draw_date_raw',
                                        transforms: [
                                            { op: 'trim' },
                                            { op: 'date', from: String(this.sourceForm.quick_draw_date_from_format || 'd/m/Y'), to: 'Y-m-d' },
                                        ],
                                    },
                                    first_prize: buildComposeRule(firstPrizeRawFields, this.sourceForm.quick_first_prize_take_right),
                                    last_2_digits: buildComposeRule(last2RawFields, this.sourceForm.quick_last2_take_right),
                                },
                            },
                            selection_config_json: {
                                selection_stage: 'PRE_MAPPING',
                                strategy: 'strict_single_match',
                                date_field: 'draw_date_raw',
                                required_fields: [],
                                meta: {
                                    candidate_draw_date_offset_days: 0,
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
                        }, null, 2);

                        this.applyUnifiedToForm();
                        this.activeSourceTab = 3;
                    } catch (error) {
                        const message = error?.message || 'สร้าง Pipeline Config ไม่สำเร็จ';
                        this.$bvModal.msgBoxOk(message, {
                            title: 'Quick Setup',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true,
                        });
                    }
                },
                buildV2Payload() {
                    this.applyUnifiedToForm();

                    const unifiedConfig = this.parseJsonSafe(this.sourceForm.unified_pipeline_json);
                    if (!unifiedConfig || Object.keys(unifiedConfig).length === 0) {
                        throw new Error('กรุณาใส่ Pipeline Config JSON ให้ถูกต้อง');
                    }

                    const fetchConfig = this.parseJsonSafe(this.sourceForm.fetch_config_json);
                    const parserConfig = this.parseJsonSafe(this.sourceForm.parser_config_json);
                    const selectionConfig = this.parseJsonSafe(this.sourceForm.selection_config_json);

                    const endpointUrl = String(fetchConfig.endpoint_url || this.sourceForm.endpoint_url || '').trim();
                    if (endpointUrl === '') {
                        throw new Error('กรุณาใส่ endpoint_url ใน fetch_config_json');
                    }

                    const parserType = String(parserConfig.parser_type || this.sourceForm.parser_type || '').trim().toUpperCase();
                    if (parserType === '') {
                        throw new Error('กรุณาใส่ parser_type ใน parser_config_json');
                    }

                    return {
                        ...this.sourceForm,
                        id: this.sourceId,
                        market_id: this.sourceForm.market_id ? parseInt(this.sourceForm.market_id, 10) : null,
                        source_type: 'api',
                        endpoint_url: endpointUrl,
                        http_method: String(fetchConfig.http_method || this.sourceForm.http_method || 'GET').toUpperCase(),
                        parser_type: parserType,
                        pipeline_version: 'V2_CUTOVER',
                        fetch_strategy: String(fetchConfig.fetch_strategy || this.sourceForm.fetch_strategy || 'JSON_HTTP').toUpperCase(),
                        selection_stage: String(selectionConfig.selection_stage || this.sourceForm.selection_stage || 'POST_MAPPING').toUpperCase(),
                    };
                },
                applyJsonExample(field) {
                    const example = this.jsonExamples[field];
                    if (!example) {
                        return;
                    }

                    const current = String(this.sourceForm[field] || '').trim();
                    if (current !== '' && !window.confirm('ช่องนี้มีข้อมูลอยู่แล้ว ต้องการแทนที่ด้วยตัวอย่างหรือไม่?')) {
                        return;
                    }

                    this.sourceForm[field] = JSON.stringify(example, null, 2);
                },
                addSourceModal() {
                    this.sourceId = null;
                    this.sourceFormMethod = 'add';
                    this.activeSourceTab = 0;
                    this.sourceForm = this.newSourceForm();
                    this.showSourceForm = true;
                    this.syncUnifiedFromForm();

                    this.$nextTick(() => {
                        this.$refs.addeditSource.show();
                    });
                },
                async editSourceModal(id) {
                    this.sourceId = id;
                    this.sourceFormMethod = 'edit';
                    this.activeSourceTab = 0;

                    const response = await axios.post("{{ route('admin.lotto.result_sources.loaddata') }}", { id });
                    const item = response?.data?.data || {};

                    this.sourceForm = {
                        ...this.newSourceForm(),
                        ...item,
                        market_id: item.market_id ? String(item.market_id) : '',
                        request_headers_json: this.toJsonText(item.request_headers_json),
                        request_query_template_json: this.toJsonText(item.request_query_template_json),
                        request_body_template_json: this.toJsonText(item.request_body_template_json),
                        parser_config_json: this.toJsonText(item.parser_config_json),
                        mapping_config_json: this.toJsonText(item.mapping_config_json),
                        fetch_config_json: this.toJsonText(item.fetch_config_json),
                        selection_config_json: this.toJsonText(item.selection_config_json),
                        validation_config_json: this.toJsonText(item.validation_config_json),
                        readiness_config_json: this.toJsonText(item.readiness_config_json),
                        retry_policy_json: this.toJsonText(item.retry_policy_json),
                    };
                    this.syncUnifiedFromForm();

                    this.showSourceForm = true;

                    this.$nextTick(() => {
                        this.$refs.addeditSource.show();
                    });
                },
                onModalShown() {
                    this.initMarketSelect2();
                    this.syncMarketSelectValue();
                },
                onModalHidden() {
                    this.destroyMarketSelect2();
                    this.activeSourceTab = 0;
                },
                onNativeMarketChange(event) {
                    if (this.isSyncingMarketSelect) {
                        return;
                    }

                    const value = event?.target?.value || '';
                    this.sourceForm.market_id = value ? String(value) : '';
                },
                getMarketDropdownParent(selectEl) {
                    if (!window.jQuery || !selectEl) {
                        return null;
                    }

                    const $select = window.jQuery(selectEl);
                    const $modal = $select.closest('.modal');

                    if ($modal.length) {
                        return $modal;
                    }

                    const modalId = this.$refs.addeditSource && this.$refs.addeditSource.id
                        ? String(this.$refs.addeditSource.id)
                        : 'addeditSource';

                    const $fallbackModal = window.jQuery('#' + modalId).closest('.modal');

                    if ($fallbackModal.length) {
                        return $fallbackModal;
                    }

                    const $shownModal = window.jQuery('.modal.show').last();

                    if ($shownModal.length) {
                        return $shownModal;
                    }

                    return window.jQuery(document.body);
                },
                normalizeLogoUrl(rawUrl) {
                    const value = String(rawUrl || '').trim();
                    if (!value) {
                        return '';
                    }

                    if (/^https?:\/\//i.test(value)) {
                        return value;
                    }

                    if (value.startsWith('/')) {
                        return `${window.location.origin}${value}`;
                    }

                    return `${window.location.origin}/${value}`;
                },
                resolveLogoFromState(state, $select) {
                    if (state?.element) {
                        const byDataset = state.element.dataset ? state.element.dataset.logo : '';
                        if (byDataset) {
                            return byDataset;
                        }

                        const byAttr = state.element.getAttribute ? state.element.getAttribute('data-logo') : '';
                        if (byAttr) {
                            return byAttr;
                        }
                    }

                    if ($select && state?.id) {
                        const $opt = $select.find('option[value="' + String(state.id) + '"]');
                        if ($opt.length) {
                            return String($opt.attr('data-logo') || '');
                        }
                    }

                    return '';
                },
                renderMarketOption(state, $select) {
                    if (!state.id) {
                        return state.text || '';
                    }

                    const logoRaw = this.resolveLogoFromState(state, $select);
                    const logo = this.normalizeLogoUrl(logoRaw);
                    const text = String(state.text || '').trim();

                    const $wrapper = window.jQuery('<span class="lotto-market-option"></span>');

                    if (logo) {
                        const $img = window.jQuery('<img class="lotto-market-option__logo" alt="">');
                        $img.attr('src', logo);
                        $img.on('error', function () {
                            window.jQuery(this).remove();
                        });
                        $wrapper.append($img);
                    }

                    $wrapper.append(
                        window.jQuery('<span class="lotto-market-option__text"></span>').text(text)
                    );

                    return $wrapper;
                },
                initMarketSelect2() {
                    const selectEl = this.$refs.marketSelect;
                    if (!selectEl || !window.jQuery) {
                        return;
                    }

                    const $select = window.jQuery(selectEl);
                    if (!$select.length || typeof $select.select2 !== 'function') {
                        return;
                    }

                    this.destroyMarketSelect2();

                    const dropdownParent = this.getMarketDropdownParent(selectEl);
                    const self = this;

                    $select.select2({
                        width: '100%',
                        theme: 'bootstrap4',
                        dropdownParent: dropdownParent,
                        placeholder: '-- เลือกรายการหวย --',
                        allowClear: false,
                        templateResult(state) {
                            return self.renderMarketOption(state, $select);
                        },
                        templateSelection(state) {
                            return self.renderMarketOption(state, $select);
                        },
                        escapeMarkup(markup) {
                            return markup;
                        },
                    });

                    $select.on('change.resultSourceMarket', () => {
                        const value = $select.val();
                        const normalizedValue = value ? String(value) : '';

                        if (this.sourceForm.market_id === normalizedValue) {
                            return;
                        }

                        this.isSyncingMarketSelect = true;
                        this.sourceForm.market_id = normalizedValue;

                        this.$nextTick(() => {
                            this.isSyncingMarketSelect = false;
                        });
                    });
                },
                destroyMarketSelect2() {
                    const selectEl = this.$refs.marketSelect;
                    if (!selectEl || !window.jQuery) {
                        return;
                    }

                    const $select = window.jQuery(selectEl);
                    if (!$select.length) {
                        return;
                    }

                    $select.off('.resultSourceMarket');

                    if ($select.hasClass('select2-hidden-accessible') && typeof $select.select2 === 'function') {
                        $select.select2('destroy');
                    }
                },
                syncMarketSelectValue() {
                    const selectEl = this.$refs.marketSelect;
                    if (!selectEl || !window.jQuery) {
                        return;
                    }

                    const value = this.sourceForm.market_id ? String(this.sourceForm.market_id) : '';
                    const $select = window.jQuery(selectEl);

                    if (String($select.val() || '') === value) {
                        return;
                    }

                    this.isSyncingMarketSelect = true;
                    $select.val(value);

                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.trigger('change.select2');
                    }

                    this.$nextTick(() => {
                        this.isSyncingMarketSelect = false;
                    });
                },
                async submitSourceForm() {
                    const url = this.sourceFormMethod === 'add'
                        ? "{{ route('admin.lotto.result_sources.create') }}"
                        : "{{ route('admin.lotto.result_sources.update') }}";

                    try {
                        const payloadData = this.buildV2Payload();
                        const response = await axios.post(url, {
                            id: this.sourceId,
                            data: payloadData,
                        });

                        this.$refs.addeditSource.hide();
                        window.LaravelDataTables['dataTableBuilder'].draw(false);

                        await this.$bvModal.msgBoxOk(response?.data?.message || 'บันทึก source สำเร็จ', {
                            title: 'ผลการดำเนินการ',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'success',
                            centered: true,
                        });
                    } catch (error) {
                        const message = error?.response?.data?.message || error?.message || 'บันทึก source ไม่สำเร็จ';

                        await this.$bvModal.msgBoxOk(message, {
                            title: 'เกิดข้อผิดพลาด',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true,
                        });
                    }
                },
                async callConfigAction(url, successTitle) {
                    try {
                        const payloadData = this.buildV2Payload();
                        const response = await axios.post(url, {
                            id: this.sourceId,
                            data: payloadData,
                        });

                        await this.$bvModal.msgBoxOk(response?.data?.message || successTitle, {
                            title: 'ผลการดำเนินการ',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'success',
                            centered: true,
                        });
                    } catch (error) {
                        const message = error?.response?.data?.message || error?.message || 'ดำเนินการไม่สำเร็จ';
                        await this.$bvModal.msgBoxOk(message, {
                            title: 'เกิดข้อผิดพลาด',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true,
                        });
                    }
                },
                async previewSourceConfig() {
                    await this.callConfigAction("{{ route('admin.lotto.result_sources.preview_config') }}", 'Preview สำเร็จ');
                },
                async validateSourceConfig() {
                    await this.callConfigAction("{{ route('admin.lotto.result_sources.validate_config') }}", 'Validation สำเร็จ');
                },
                async validateSourceCutover() {
                    await this.callConfigAction("{{ route('admin.lotto.result_sources.validate_cutover') }}", 'Cutover validation สำเร็จ');
                },
                editSourceStatus(id, status) {
                    this.$bvModal.msgBoxConfirm('ต้องการเปลี่ยนสถานะ source หรือไม่?', {
                        title: 'ยืนยัน',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'ตกลง',
                        cancelTitle: 'ยกเลิก',
                        centered: true,
                    }).then(async (value) => {
                        if (!value) {
                            return;
                        }

                        try {
                            const response = await axios.post("{{ route('admin.lotto.result_sources.edit') }}", {
                                id: id,
                                status: status,
                                method: 'is_active',
                            });

                            window.LaravelDataTables['dataTableBuilder'].draw(false);

                            await this.$bvModal.msgBoxOk(response?.data?.message || 'อัปเดตสถานะ source สำเร็จ', {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                centered: true,
                            });
                        } catch (error) {
                            const message = error?.response?.data?.message || 'อัปเดตสถานะ source ไม่สำเร็จ';

                            await this.$bvModal.msgBoxOk(message, {
                                title: 'เกิดข้อผิดพลาด',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'danger',
                                centered: true,
                            });
                        }
                    });
                },
            },
            watch: {
                'sourceForm.market_id'() {
                    if (this.isSyncingMarketSelect) {
                        return;
                    }

                    this.$nextTick(() => this.syncMarketSelectValue());
                },
            },
        });

        window.addSourceModal = function () { window.sourceFormApp.addSourceModal(); };
        window.editSourceModal = function (id) { window.sourceFormApp.editSourceModal(id); };
        window.editSourceStatus = function (id, status) { window.sourceFormApp.editSourceStatus(id, status); };
    </script>
@endpush
