<b-modal ref="addeditSource" id="addeditSource" centered size="lg" title="ตั้งค่า Auto Result Source" :hide-footer="true" @shown="onModalShown" @hidden="onModalHidden">
    <b-form v-if="showSourceForm" @submit.prevent="submitSourceForm">
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
                            <b-form-input size="sm" :value="sourceForm.priority" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">แก้ค่าได้ที่แท็บ <code>ตั้งค่าด่วน</code></small>
                        </b-form-group>
                    </b-col>
                    <b-col md="3">
                        <b-form-group label="หมดเวลารอ (สรุป)">
                            <b-form-input size="sm" :value="sourceForm.timeout_seconds" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">แก้ค่าได้ที่แท็บ <code>ตั้งค่าด่วน</code></small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-alert show variant="info" class="py-2">
                    โหมดฟอร์มนี้เป็น V2-only: หน้าจอนี้แสดงค่าแบบสรุป (read-only) จาก config ปัจจุบันเท่านั้น
                </b-alert>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="URL ปลายทาง (สรุป)">
                            <b-form-input size="sm" :value="derivedEndpointUrl" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">แก้ค่าได้ที่แท็บ <code>ตั้งค่าด่วน</code> หรือ <code>JSON หลัก</code></small>
                        </b-form-group>
                    </b-col>
                    <b-col md="3">
                        <b-form-group label="วิธีเรียก API (สรุป)">
                            <b-form-input size="sm" :value="derivedHttpMethod" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">แก้ค่าได้ที่แท็บ <code>ตั้งค่าด่วน</code> หรือ <code>JSON หลัก</code></small>
                        </b-form-group>
                    </b-col>
                    <b-col md="3">
                        <b-form-group label="ชนิด Parser (สรุป)">
                            <b-form-input size="sm" :value="derivedParserType" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">แก้ค่าได้ที่แท็บ <code>JSON หลัก</code></small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="8">
                        <b-form-group label="โหมดอ้างอิงวันงวด (สรุป)">
                            <b-form-input size="sm" :value="lookupModeLabel(sourceForm.lookup_date_mode)" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">แก้ค่าได้ที่แท็บ <code>ตั้งค่าด่วน</code></small>
                        </b-form-group>
                    </b-col>
                    <b-col md="4">
                        <b-form-group label="เลื่อนวัน (สรุป)">
                            <b-form-input size="sm" :value="sourceForm.lookup_date_offset_days" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">แก้ค่าได้ที่แท็บ <code>ตั้งค่าด่วน</code></small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="เริ่มใช้งานตั้งแต่ (Y-m-d H:i:s)">
                            <b-form-input size="sm" :value="sourceForm.effective_from" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">แก้ค่าได้ที่แท็บ <code>ตั้งค่าด่วน</code></small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="สิ้นสุดใช้งาน (Y-m-d H:i:s)">
                            <b-form-input size="sm" :value="sourceForm.effective_to" readonly></b-form-input>
                            <small class="text-muted d-block mt-1">แก้ค่าได้ที่แท็บ <code>ตั้งค่าด่วน</code></small>
                        </b-form-group>
                    </b-col>
                </b-row>
            </b-tab>

            <b-tab title="ตั้งค่าด่วน">
                <b-alert show variant="success" class="py-2">
                    โหมดง่าย: กรอกข้อมูลพื้นฐาน แล้วกด <strong>สร้าง JSON อัตโนมัติ</strong> ระบบจะสร้าง config ให้ทันที
                </b-alert>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="ตลาด">
                            <b-form-select size="sm" :options="marketSelectOptions" v-model="sourceForm.market_id" :disabled="sourceFormMethod === 'edit'"></b-form-select>
                            <small class="text-muted d-block mt-1">สร้างใหม่เลือกได้, โหมดแก้ไขจะล็อกตลาดเดิมเพื่อกันย้าย source ผิดตลาด</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="ลำดับความสำคัญ">
                            <b-form-input size="sm" type="number" min="1" v-model="sourceForm.priority"></b-form-input>
                            <small class="text-muted d-block mt-1">เลขน้อยทำงานก่อน ใช้จัดลำดับหลาย source</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="หมดเวลารอ (วินาที)">
                            <b-form-input size="sm" type="number" min="1" max="60" v-model="sourceForm.timeout_seconds"></b-form-input>
                            <small class="text-muted d-block mt-1">เวลารอ response จาก source ก่อนถือว่าล้มเหลว</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="โหมดอ้างอิงวันงวด">
                            <b-form-select size="sm" :options="lookupDateModesLocalized" v-model="sourceForm.lookup_date_mode"></b-form-select>
                            <small class="text-muted d-block mt-1">@{{ lookupModeHelpText }}</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="เลื่อนวัน (วัน)">
                            <b-form-input size="sm" type="number" min="-365" max="365" v-model="sourceForm.lookup_date_offset_days"></b-form-input>
                            <small class="text-muted d-block mt-1">เลื่อนวันงวดจากฐาน เช่น -1 คือวันก่อนหน้า</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="URL ผลหวย">
                            <b-form-input size="sm" v-model="sourceForm.quick_endpoint_url" placeholder="https://example.com/result"></b-form-input>
                            <small class="text-muted d-block mt-1">ลิงก์ API/เว็บที่ระบบจะดึงข้อมูล</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="เริ่มใช้งานตั้งแต่ (Y-m-d H:i:s)">
                            <b-form-input size="sm" v-model="sourceForm.effective_from"></b-form-input>
                            <small class="text-muted d-block mt-1">วันเริ่มใช้งาน source นี้ (ว่างได้)</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="วิธีเรียก API">
                            <b-form-select size="sm" v-model="sourceForm.quick_http_method" :options="httpMethodsLocalized"></b-form-select>
                            <small class="text-muted d-block mt-1">ส่วนใหญ่ใช้ GET</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="สิ้นสุดใช้งาน (Y-m-d H:i:s)">
                            <b-form-input size="sm" v-model="sourceForm.effective_to"></b-form-input>
                            <small class="text-muted d-block mt-1">วันสิ้นสุดใช้งาน source นี้ (ว่างได้)</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="Path ของวันที่">
                            <b-form-input size="sm" v-model="sourceForm.quick_draw_date_path" placeholder="$.date"></b-form-input>
                            <small class="text-muted d-block mt-1">JSONPath ของวันที่ในผลลัพธ์</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="รูปแบบวันที่ต้นทาง">
                            <b-form-select size="sm" v-model="sourceForm.quick_draw_date_from_format" :options="quickDateFormats"></b-form-select>
                            <small class="text-muted d-block mt-1">เช่น <code>d/m/Y</code> หรือ <code>Y-m-d</code></small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="ตัวเลือกเพิ่มเติม">
                            <div class="pt-1">
                                <b-form-checkbox v-model="sourceForm.supports_partial" switch class="mb-1">ยอมรับผลไม่ครบ (Partial)</b-form-checkbox>
                                <b-form-checkbox v-model="sourceForm.requires_browser" switch>ใช้ Browser Worker</b-form-checkbox>
                            </div>
                            <small class="text-muted d-block mt-1">เปิดเฉพาะกรณี source นี้ต้องใช้เงื่อนไขพิเศษ</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="Path ของรางวัลที่ 1">
                            <b-form-input size="sm" v-model="sourceForm.quick_first_prize_paths" placeholder="$.results.prize_1st หรือ $.lotto_2,$.lotto_3,$.lotto_4"></b-form-input>
                            <small class="text-muted d-block mt-1">ถ้าต้องต่อหลายช่อง ให้คั่นด้วย comma</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="เก็บท้ายกี่หลัก (รางวัลที่ 1)">
                            <b-form-input size="sm" type="number" min="1" max="10" v-model="sourceForm.quick_first_prize_take_right"></b-form-input>
                            <small class="text-muted d-block mt-1">ปกติใช้ 3</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="Path ของเลขท้าย 2 ตัว">
                            <b-form-input size="sm" v-model="sourceForm.quick_last2_paths" placeholder="$.results.prize_2nd หรือ $.lotto_1,$.lotto_2"></b-form-input>
                            <small class="text-muted d-block mt-1">ถ้าต้องต่อหลายช่อง ให้คั่นด้วย comma</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="เก็บท้ายกี่หลัก (เลขท้าย)">
                            <b-form-input size="sm" type="number" min="1" max="10" v-model="sourceForm.quick_last2_take_right"></b-form-input>
                            <small class="text-muted d-block mt-1">ปกติใช้ 2</small>
                        </b-form-group>
                    </b-col>
                </b-row>

                <div class="d-flex flex-wrap quick-setup-actions">
                    <button type="button" class="btn btn-primary btn-sm quick-action-btn mr-2 mb-2" @click="generateQuickPipelineJson">สร้าง JSON อัตโนมัติ</button>
                    <button type="button" class="btn btn-secondary btn-sm quick-action-btn mb-2" @click="applyQuickPresetLaosVip">ตั้งค่าอัตโนมัติ: Laos VIP</button>
                </div>
            </b-tab>

            <b-tab title="JSON หลัก">
                <b-form-group label="Pipeline Config JSON (จุดตั้งค่าหลักช่องเดียว)">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-1 unified-header-row">
                        <small class="text-muted unified-header-note">แก้ config หลักที่ช่องนี้ช่องเดียว ระบบจะแตกไป field ย่อยให้อัตโนมัติทุกครั้งก่อนพรีวิว/ตรวจสอบ/บันทึก (ไม่ต้องกดนำค่าไปใช้ก่อน)</small>
                        <div class="d-flex flex-wrap unified-header-actions">
                            <button type="button" class="btn btn-outline-secondary btn-xs mr-1 mb-1 json-action-btn" @click="applyJsonExample('unified_pipeline_json')">ใส่ตัวอย่าง</button>
                            <button type="button" class="btn btn-outline-secondary btn-xs mr-1 mb-1 json-action-btn" @click="syncUnifiedFromForm">รีเฟรชค่าปัจจุบัน</button>
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

            <b-tab title="ประวัติการเปลี่ยนแปลง">
                <b-row>
                    <b-col md="8">
                        <b-form-group label="เหตุผลการแก้ไข">
                            <b-form-input size="sm" v-model="sourceForm.revision_reason" placeholder="เหตุผลการเปลี่ยนแปลง"></b-form-input>
                            <small class="text-muted d-block mt-1">อธิบายว่าปรับ config เพราะอะไร เพื่อเก็บประวัติ revision</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="4" class="d-flex align-items-end">
                        <small class="text-muted mb-3">แนะนำให้กด พรีวิว + ตรวจสอบค่า ก่อนบันทึกหรือเปิดใช้งานจริงทุกครั้ง</small>
                    </b-col>
                </b-row>
            </b-tab>
        </b-tabs>

        <div class="d-flex flex-wrap justify-content-between align-items-center action-bar">
            <div class="d-flex flex-wrap action-bar-left">
                <button type="button" class="btn btn-primary btn-sm mr-1 mb-2 action-main-btn" @click="previewSourceConfig">พรีวิว</button>
                <button type="button" class="btn btn-info btn-sm mr-1 mb-2 action-main-btn text-white" @click="validateSourceConfig">ตรวจสอบค่า</button>
                <button type="button" class="btn btn-warning btn-sm mb-2 action-main-btn" @click="validateSourceCutover">ตรวจสอบก่อนเปิดใช้งานจริง</button>
            </div>
            <button type="submit" class="btn btn-success btn-sm mb-2 action-bar-save action-main-btn">บันทึก</button>
        </div>
    </b-form>
</b-modal>

@push('styles')
    <style>
        #addeditSource .btn-xs {
            font-size: 11px;
            line-height: 1.2;
            padding: 2px 8px;
        }

        #addeditSource .nav-tabs .nav-link {
            border-radius: .4rem .4rem 0 0;
            font-weight: 600;
            color: #4b5563;
            padding: .45rem .75rem;
        }

        #addeditSource .nav-tabs .nav-link.active {
            color: #0f4c81;
            background: #eef6ff;
            border-color: #bfdcff #bfdcff #eef6ff;
        }

        #addeditSource .form-group {
            margin-bottom: 1rem;
        }

        #addeditSource small.text-muted.d-block.mt-1 {
            display: block;
            min-height: 34px;
            line-height: 1.25;
            margin-top: .35rem !important;
            word-break: break-word;
        }

        #addeditSource .unified-header-row {
            gap: 8px;
        }

        #addeditSource .unified-header-note {
            flex: 1 1 320px;
            margin-bottom: 0;
        }

        #addeditSource .unified-header-actions {
            flex: 0 1 auto;
            gap: 0;
        }

        #addeditSource .json-action-btn {
            font-size: 12px !important;
            line-height: 1.2 !important;
            min-height: 26px;
            min-width: 118px;
            text-align: center;
        }

        #addeditSource .quick-setup-actions {
            gap: 0;
            width: 100%;
        }

        #addeditSource .quick-action-btn {
            flex: 1 1 220px;
            min-height: 36px;
            font-weight: 600;
        }

        #addeditSource .action-bar {
            gap: 8px;
            margin-top: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 10px 4px;
        }

        #addeditSource .action-bar-left {
            gap: 0;
        }

        #addeditSource .action-main-btn {
            min-height: 34px;
            font-weight: 600;
        }

        @media (max-width: 767.98px) {
            #addeditSource .action-bar {
                justify-content: flex-start !important;
            }

            #addeditSource .action-bar-save {
                width: 100%;
            }
        }

        #addeditSource .source-risk-input label {
            color: #8a6d3b;
            font-weight: 600;
        }
    </style>
@endpush

@push('scripts')
    <script type="module">
        window.sourceFormApp = new Vue({
            el: '#app',
            data() {
                return {
                    showSourceForm: true,
                    activeSourceTab: 0,
                    sourceFormMethod: 'add',
                    sourceId: null,
                    marketOptionsGrouped: @json($marketOptionsGrouped ?? []),
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
                lookupDateModesLocalized() {
                    const labelMap = {
                        ROUND_DATE: 'ใช้วันงวดในระบบ (ตรงวัน)',
                        ROUND_DATE_MINUS_DAYS: 'ใช้วันงวดในระบบ - จำนวนวันที่เลื่อน',
                        ROUND_DATE_PLUS_DAYS: 'ใช้วันงวดในระบบ + จำนวนวันที่เลื่อน',
                        RESULT_AT_DATE: 'ใช้วันที่ตามเวลาประกาศผล (result_at)',
                    };

                    if (!Array.isArray(this.lookupDateModes)) {
                        if (this.lookupDateModes && typeof this.lookupDateModes === 'object') {
                            return Object.keys(this.lookupDateModes).map((key) => ({
                                value: key,
                                text: labelMap[key] || String(this.lookupDateModes[key] || key),
                            }));
                        }

                        return [];
                    }

                    return this.lookupDateModes.map((item) => {
                        if (item && typeof item === 'object') {
                            const value = item.value ?? item.id ?? item.key ?? '';
                            const fallbackText = item.text ?? item.label ?? String(value);
                            return {
                                ...item,
                                value,
                                text: labelMap[value] || fallbackText,
                            };
                        }

                        const raw = String(item || '');
                        return {
                            value: raw,
                            text: labelMap[raw] || raw,
                        };
                    });
                },
                lookupModeHelpText() {
                    const helpMap = {
                        ROUND_DATE: 'เทียบด้วยวันงวดเดียวกันตรงๆ ระหว่างระบบเราและปลายทาง',
                        ROUND_DATE_MINUS_DAYS: 'ปลายทางช้ากว่าเรา: ระบบจะเอาวันงวดลบตามค่า "เลื่อนวัน"',
                        ROUND_DATE_PLUS_DAYS: 'ปลายทางเร็วกว่าเรา: ระบบจะเอาวันงวดบวกตามค่า "เลื่อนวัน"',
                        RESULT_AT_DATE: 'ใช้อ้างอิงจากวันที่ของ result_at ในงวดนั้นแทน draw_date',
                    };

                    const mode = String(this.sourceForm.lookup_date_mode || 'ROUND_DATE');
                    return helpMap[mode] || 'กำหนดว่าจะอ้างวันงวดจากค่าไหนตอนยิง request';
                },
                marketSelectOptions() {
                    const groups = Array.isArray(this.marketOptionsGrouped) ? this.marketOptionsGrouped : [];
                    const optionGroups = groups.map((group) => ({
                        label: String(group?.label || '-'),
                        options: Array.isArray(group?.options)
                            ? group.options.map((market) => ({
                                value: String(market?.value ?? ''),
                                text: String(market?.text ?? '-'),
                            }))
                            : [],
                    }));

                    return [
                        { value: '', text: '-- เลือกตลาด --' },
                        ...optionGroups,
                    ];
                },
                selectedMarketText() {
                    const marketId = String(this.sourceForm.market_id || '');
                    if (!marketId) {
                        return '';
                    }

                    const groups = Array.isArray(this.marketOptionsGrouped) ? this.marketOptionsGrouped : [];
                    for (const group of groups) {
                        const options = Array.isArray(group?.options) ? group.options : [];
                        const found = options.find((market) => String(market?.value ?? '') === marketId);
                        if (found) {
                            return String(found?.text ?? marketId);
                        }
                    }

                    return marketId;
                },
                httpMethodsLocalized() {
                    const list = Array.isArray(this.httpMethods) ? this.httpMethods : [];
                    return list.map((item) => {
                        if (item && typeof item === 'object') {
                            const value = String(item.value ?? item.id ?? item.key ?? '');
                            return {
                                ...item,
                                value,
                                text: value === 'GET' ? 'GET (แนะนำ)' : String(item.text ?? item.label ?? value),
                            };
                        }

                        const raw = String(item || '').toUpperCase();
                        return { value: raw, text: raw === 'GET' ? 'GET (แนะนำ)' : raw };
                    });
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
                lookupModeLabel(mode) {
                    const value = String(mode || '');
                    const found = this.lookupDateModesLocalized.find((item) => String(item.value || '') === value);
                    return found ? String(found.text || value) : value;
                },
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
                            throw new Error('กรุณาใส่ URL ผลหวยในแท็บตั้งค่าด่วน');
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
                        this.activeSourceTab = 2;
                    } catch (error) {
                        const message = error?.message || 'สร้าง Pipeline Config ไม่สำเร็จ';
                        this.$bvModal.msgBoxOk(message, {
                            title: 'ตั้งค่าด่วน',
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
                    await this.callConfigAction("{{ route('admin.lotto.result_sources.preview_config') }}", 'พรีวิวสำเร็จ');
                },
                async validateSourceConfig() {
                    await this.callConfigAction("{{ route('admin.lotto.result_sources.validate_config') }}", 'ตรวจสอบค่าสำเร็จ');
                },
                async validateSourceCutover() {
                    await this.callConfigAction("{{ route('admin.lotto.result_sources.validate_cutover') }}", 'ตรวจสอบก่อนเปิดใช้งานจริงสำเร็จ');
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
