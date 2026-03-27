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

                <b-row>
                    <b-col md="4">
                        <b-form-group label="Source Type">
                            <b-form-select size="sm" :options="sourceTypes" v-model="sourceForm.source_type"></b-form-select>
                            <small class="text-muted d-block mt-1">ระบุประเภทแหล่งข้อมูล เช่น API หรือ HTML</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="4">
                        <b-form-group label="HTTP Method">
                            <b-form-select size="sm" :options="httpMethods" v-model="sourceForm.http_method"></b-form-select>
                            <small class="text-muted d-block mt-1">วิธีส่ง request ไป endpoint เช่น GET/POST</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="4">
                        <b-form-group label="Parser Type">
                            <b-form-select size="sm" :options="parserTypes" v-model="sourceForm.parser_type"></b-form-select>
                            <small class="text-muted d-block mt-1">เครื่องมือดึง field จาก payload ที่ fetch มา</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-form-group label="Endpoint URL">
                    <b-form-input size="sm" v-model="sourceForm.endpoint_url" required></b-form-input>
                    <small class="text-muted d-block mt-1">URL หลักของ source ที่ใช้ดึงผลลอตเตอรี่</small>
                </b-form-group>

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
                            <b-form-select size="sm" :options="pipelineVersions" v-model="sourceForm.pipeline_version"></b-form-select>
                            <small class="text-muted d-block mt-1">โหมดการรัน: legacy, shadow เทียบผล, หรือ cutover ใช้งาน v2</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="4">
                        <b-form-group label="Fetch Strategy">
                            <b-form-select size="sm" :options="fetchStrategies" v-model="sourceForm.fetch_strategy"></b-form-select>
                            <small class="text-muted d-block mt-1">วิธีดึงข้อมูลหลัก เช่น JSON_HTTP หรือ RENDERED_BROWSER</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="4">
                        <b-form-group label="Selection Stage">
                            <b-form-select size="sm" :options="selectionStages" v-model="sourceForm.selection_stage"></b-form-select>
                            <small class="text-muted d-block mt-1">เลือก candidate ก่อน mapping หรือหลัง mapping</small>
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

            <b-tab title="Configs JSON">
                <b-row>
                    <b-col md="6">
                        <b-form-group label="Headers JSON">
                            <div class="d-flex justify-content-end mb-1">
                                <button type="button" class="btn btn-outline-secondary btn-xs" @click="applyJsonExample('request_headers_json')">Insert Example</button>
                            </div>
                            <b-form-textarea rows="4" v-model="sourceForm.request_headers_json"></b-form-textarea>
                            <small class="text-muted d-block mt-1">HTTP headers ที่ต้องส่งเพิ่ม เช่น token หรือ content-type</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="Query Template JSON">
                            <div class="d-flex justify-content-end mb-1">
                                <button type="button" class="btn btn-outline-secondary btn-xs" @click="applyJsonExample('request_query_template_json')">Insert Example</button>
                            </div>
                            <b-form-textarea rows="4" v-model="sourceForm.request_query_template_json"></b-form-textarea>
                            <small class="text-muted d-block mt-1">template query string โดยระบบจะแทนค่าตาม context</small>
                        </b-form-group>
                    </b-col>
                </b-row>
                <b-row>
                    <b-col md="6">
                        <b-form-group label="Body Template JSON">
                            <div class="d-flex justify-content-end mb-1">
                                <button type="button" class="btn btn-outline-secondary btn-xs" @click="applyJsonExample('request_body_template_json')">Insert Example</button>
                            </div>
                            <b-form-textarea rows="4" v-model="sourceForm.request_body_template_json"></b-form-textarea>
                            <small class="text-muted d-block mt-1">template request body สำหรับ method ที่มี payload</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="Fetch Config JSON">
                            <div class="d-flex justify-content-end mb-1">
                                <button type="button" class="btn btn-outline-secondary btn-xs" @click="applyJsonExample('fetch_config_json')">Insert Example</button>
                            </div>
                            <b-form-textarea rows="4" v-model="sourceForm.fetch_config_json"></b-form-textarea>
                            <small class="text-muted d-block mt-1">config ราย fetch strategy เช่น html/json/rendered browser</small>
                        </b-form-group>
                    </b-col>
                </b-row>
                <b-row>
                    <b-col md="6">
                        <b-form-group label="Parser Config JSON">
                            <div class="d-flex justify-content-end mb-1">
                                <button type="button" class="btn btn-outline-secondary btn-xs" @click="applyJsonExample('parser_config_json')">Insert Example</button>
                            </div>
                            <b-form-textarea rows="4" v-model="sourceForm.parser_config_json"></b-form-textarea>
                            <small class="text-muted d-block mt-1">กำหนด extractor และ parse mode เพื่อแตก raw field</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="Mapping Config JSON">
                            <div class="d-flex justify-content-end mb-1">
                                <button type="button" class="btn btn-outline-secondary btn-xs" @click="applyJsonExample('mapping_config_json')">Insert Example</button>
                            </div>
                            <b-form-textarea rows="4" v-model="sourceForm.mapping_config_json"></b-form-textarea>
                            <small class="text-muted d-block mt-1">แปลง raw field เป็น canonical field พร้อม transform chain</small>
                        </b-form-group>
                    </b-col>
                </b-row>
                <b-row>
                    <b-col md="6">
                        <b-form-group label="Selection Config JSON">
                            <div class="d-flex justify-content-end mb-1">
                                <button type="button" class="btn btn-outline-secondary btn-xs" @click="applyJsonExample('selection_config_json')">Insert Example</button>
                            </div>
                            <b-form-textarea rows="4" v-model="sourceForm.selection_config_json"></b-form-textarea>
                            <small class="text-muted d-block mt-1">กติกาเลือก candidate ที่ถูกต้องในแต่ละ run</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="Validation Config JSON">
                            <div class="d-flex justify-content-end mb-1">
                                <button type="button" class="btn btn-outline-secondary btn-xs" @click="applyJsonExample('validation_config_json')">Insert Example</button>
                            </div>
                            <b-form-textarea rows="4" v-model="sourceForm.validation_config_json"></b-form-textarea>
                            <small class="text-muted d-block mt-1">ตรวจรูปแบบ/schema ของผลลัพธ์ canonical</small>
                        </b-form-group>
                    </b-col>
                </b-row>
                <b-row>
                    <b-col md="6">
                        <b-form-group label="Readiness Config JSON">
                            <div class="d-flex justify-content-end mb-1">
                                <button type="button" class="btn btn-outline-secondary btn-xs" @click="applyJsonExample('readiness_config_json')">Insert Example</button>
                            </div>
                            <b-form-textarea rows="4" v-model="sourceForm.readiness_config_json"></b-form-textarea>
                            <small class="text-muted d-block mt-1">ตรวจความพร้อมเชิงธุรกิจว่าใช้ผลได้หรือยัง</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="Retry Policy JSON">
                            <div class="d-flex justify-content-end mb-1">
                                <button type="button" class="btn btn-outline-secondary btn-xs" @click="applyJsonExample('retry_policy_json')">Insert Example</button>
                            </div>
                            <b-form-textarea rows="4" v-model="sourceForm.retry_policy_json"></b-form-textarea>
                            <small class="text-muted d-block mt-1">กำหนดนโยบาย retry เมื่อ fetch/parse ไม่สำเร็จ</small>
                        </b-form-group>
                    </b-col>
                </b-row>
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
            },
            methods: {
                buildJsonExamples() {
                    return {
                        request_headers_json: {
                            Accept: 'application/json',
                            'User-Agent': 'LottoFetcher/2.0',
                        },
                        request_query_template_json: {
                            draw_date: '{{draw_date:YYYY-MM-DD}}',
                            lang: 'th',
                        },
                        request_body_template_json: {
                            market_key: '{{market_key}}',
                            draw_date: '{{draw_date:YYYY-MM-DD}}',
                        },
                        fetch_config_json: {
                            strategy: 'json_http',
                            url_override: null,
                            response_json_path: '$',
                        },
                        parser_config_json: {
                            parse_mode: 'single_payload',
                            fields: {
                                draw_date_raw: { type: 'JSON_PATH', expr: '$.date' },
                                first_3_raw: { type: 'JSON_PATH', expr: '$.first_3' },
                                last_2_raw: { type: 'JSON_PATH', expr: '$.last_2' },
                            },
                        },
                        mapping_config_json: {
                            draw_date: { from: 'draw_date_raw', transforms: [{ op: 'date', from: 'd/m/Y', to: 'Y-m-d' }] },
                            first_prize: { from_fields: ['lotto_2', 'lotto_3', 'lotto_4'], join: '' },
                            last_2_digits: { from: 'last_2_raw', transforms: ['trim', 'digits_only', { op: 'right', value: 2 }] },
                        },
                        selection_config_json: {
                            strategy: 'strict_single_match',
                            expected_draw_date_field: 'draw_date_raw',
                        },
                        validation_config_json: {
                            required_fields: ['draw_date', 'first_prize', 'last_2_digits'],
                            rules: {
                                draw_date: 'date:Y-m-d',
                                first_prize: 'digits:3,6',
                                last_2_digits: 'digits:2',
                            },
                        },
                        readiness_config_json: {
                            allow_partial: false,
                            required_for_ready: ['draw_date', 'first_prize', 'last_2_digits'],
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
                        timeout_seconds: 10,
                        pipeline_version: 'LEGACY',
                        fetch_strategy: 'JSON_HTTP',
                        selection_stage: 'POST_MAPPING',
                        supports_partial: false,
                        requires_browser: false,
                        shadow_enabled: false,
                        cutover_enabled: false,
                        revision_reason: '',
                        effective_from: '',
                        effective_to: '',
                    };
                },
                toJsonText(value) {
                    if (!value || (Array.isArray(value) && value.length === 0) || (typeof value === 'object' && Object.keys(value).length === 0)) {
                        return '';
                    }

                    return JSON.stringify(value, null, 2);
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
                        const response = await axios.post(url, {
                            id: this.sourceId,
                            data: {
                                ...this.sourceForm,
                                id: this.sourceId,
                                market_id: this.sourceForm.market_id ? parseInt(this.sourceForm.market_id, 10) : null,
                            },
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
                        const message = error?.response?.data?.message || 'บันทึก source ไม่สำเร็จ';

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
                        const response = await axios.post(url, {
                            id: this.sourceId,
                            data: {
                                ...this.sourceForm,
                                id: this.sourceId,
                                market_id: this.sourceForm.market_id ? parseInt(this.sourceForm.market_id, 10) : null,
                            },
                        });

                        await this.$bvModal.msgBoxOk(response?.data?.message || successTitle, {
                            title: 'ผลการดำเนินการ',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'success',
                            centered: true,
                        });
                    } catch (error) {
                        const message = error?.response?.data?.message || 'ดำเนินการไม่สำเร็จ';
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
