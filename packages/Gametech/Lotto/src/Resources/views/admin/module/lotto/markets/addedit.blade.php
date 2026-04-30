@php
    $canAutoResultTestFetch = function_exists('bouncer')
        ? (bouncer()->hasPermission('lotto_settings.draws.auto_result_test_fetch') || bouncer()->hasPermission('lotto_draws.auto_result_test_fetch'))
        : false;
    $canAutoResultLogs = function_exists('bouncer')
        ? (bouncer()->hasPermission('lotto_settings.draws.auto_result_metrics') || bouncer()->hasPermission('lotto_draws.auto_result_metrics'))
        : false;
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
        <b-form-group label="ประเภทตลาด:" label-for="result_mode" description="หวยปกติจะไม่แสดงค่าตั้งต้นของยี่กี่ และหวยยี่กี่จะไม่ใช้ Auto Source แบบหวยปกติ">
            <b-form-select id="result_mode" v-model="formaddedit.result_mode" :options="option.resultModes" size="sm"></b-form-select>
        </b-form-group>
        <div v-if="formaddedit.result_mode === 'yeekee'" class="border rounded p-2 mb-3">
            <h6 class="mb-2">ตั้งค่ายี่กี่</h6>
            <b-row>
                <b-col cols="12" md="6">
                    <b-form-group label="ระยะเวลาต่อรอบ (นาที)">
                        <b-form-input v-model.number="formaddedit.yeekee_settings.round_duration_minutes" type="number" min="1" max="60" size="sm"></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="6">
                    <b-form-group label="ระยะเวลายิงเลขหลังปิดรับแทง (วินาที)">
                        <b-form-input v-model.number="formaddedit.yeekee_settings.shoot_window_after_bet_close_seconds" type="number" min="0" max="3600" size="sm"></b-form-input>
                    </b-form-group>
                </b-col>
            </b-row>
            <b-row>
                <b-col cols="12" md="6">
                    <b-form-group label="เวลารอก่อนเริ่มคำนวณผล (วินาที)">
                        <b-form-input v-model.number="formaddedit.yeekee_settings.settlement_delay_after_shoot_close_seconds" type="number" min="0" max="3600" size="sm"></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="6">
                    <b-form-group label="ระยะเวลาคาดหวังในการจ่ายรางวัล (นาที)">
                        <b-form-input v-model.number="formaddedit.yeekee_settings.expected_payout_sla_minutes" type="number" min="1" max="60" size="sm"></b-form-input>
                    </b-form-group>
                </b-col>
            </b-row>
            <b-row>
                <b-col cols="12" md="6">
                    <b-form-group label="สูตรคำนวณผล">
                        <b-form-select v-model="formaddedit.yeekee_settings.formula_preset" :options="option.yeekeeFormulaPresets" size="sm"></b-form-select>
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="6">
                    <b-form-group label="ลำดับเลขยิงที่ใช้ลบ">
                        <b-form-input v-model.number="formaddedit.yeekee_settings.subtract_position" type="number" min="1" size="sm"></b-form-input>
                    </b-form-group>
                </b-col>
            </b-row>
            <b-row>
                <b-col cols="12" md="6">
                    <b-form-group>
                        <b-form-checkbox v-model="formaddedit.yeekee_settings.reward_enabled" :value="true" :unchecked-value="false">เปิดรางวัลยิงเลข</b-form-checkbox>
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="6">
                    <b-form-group>
                        <b-form-checkbox v-model="formaddedit.yeekee_settings.refund_if_bet_entries_below_min" :value="true" :unchecked-value="false">เปิดเงื่อนไขงดออกผลและคืนโพย</b-form-checkbox>
                    </b-form-group>
                </b-col>
            </b-row>
            <b-row v-if="formaddedit.yeekee_settings.reward_enabled">
                <b-col cols="12" md="6">
                    <b-form-group label="ยอดเดิมพันขั้นต่ำในรอบเดียวกัน">
                        <b-form-input v-model.number="formaddedit.yeekee_settings.min_bet_amount" type="number" min="0" step="0.01" size="sm"></b-form-input>
                        <small class="text-muted d-block">สมาชิกต้องมียอดแทงขั้นต่ำในรอบเดียวกัน จึงจะได้รางวัลยิงเลข</small>
                    </b-form-group>
                </b-col>
            </b-row>
            <b-row v-if="formaddedit.yeekee_settings.refund_if_bet_entries_below_min">
                <b-col cols="12" md="4">
                    <b-form-group label="จำนวนรายการแทงขั้นต่ำ">
                        <b-form-input v-model.number="formaddedit.yeekee_settings.min_bet_entries_required" type="number" min="0" step="1" size="sm"></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="4">
                    <b-form-group label="รูปแบบการนับรายการแทง">
                        <b-form-select v-model="formaddedit.yeekee_settings.refund_count_mode" :options="option.yeekeeRefundCountModes" size="sm"></b-form-select>
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="4">
                    <b-form-group label="การดำเนินการเมื่อไม่ผ่านเงื่อนไข">
                        <b-form-select v-model="formaddedit.yeekee_settings.refund_action" :options="option.yeekeeRefundActions" size="sm"></b-form-select>
                    </b-form-group>
                </b-col>
            </b-row>
        </div>
        <div v-if="formaddedit.result_mode === 'yeekee'" class="alert alert-info py-2 px-3">
            การตั้งค่าเวลาเปิด/ปิด/ออกผลแบบหวยปกติ และ Auto Source จะไม่ถูกใช้กับตลาดยี่กี่
        </div>
        <b-form-group v-if="formaddedit.result_mode !== 'yeekee'" label="รูปแบบออกงวด:" label-for="draw_schedule_type" description="Manual = ทีมงานสร้างงวดเอง, Weekly = ออกรายสัปดาห์, Monthly = ออกรายเดือน">
            <b-form-select id="draw_schedule_type" v-model="formaddedit.draw_schedule_type" :options="option.drawScheduleTypes" size="sm"></b-form-select>
        </b-form-group>
        <b-form-group v-if="formaddedit.result_mode !== 'yeekee' && formaddedit.draw_schedule_type === 'weekly'" label="เลือกวันออกรายสัปดาห์:">
            <b-form-checkbox-group
                v-model="formaddedit.draw_days"
                :options="option.weeklyDays"
                size="sm"
                switches
                stacked
            ></b-form-checkbox-group>
        </b-form-group>
        <b-form-group v-if="formaddedit.result_mode !== 'yeekee' && formaddedit.draw_schedule_type === 'monthly'" label="เลือกวันที่ออกรายเดือน:">
            <b-form-select
                v-model="formaddedit.draw_dates"
                :options="option.monthlyDates"
                size="sm"
                multiple
                :select-size="8"
            ></b-form-select>
        </b-form-group>
        <b-row v-if="formaddedit.result_mode !== 'yeekee'">
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
        <b-form-group v-if="formaddedit.result_mode !== 'yeekee'" label="ลิงก์ออกผล:" label-for="result_url">
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
        <b-form-group v-if="formaddedit.result_mode !== 'yeekee'">
            <b-form-checkbox v-model="formaddedit.auto_settle_on_result" :value="1" :unchecked-value="0">
                ออกผลแล้วคำนวณยอดได้เสียอัตโนมัติทันที
            </b-form-checkbox>
            <small class="text-muted d-block">ถ้าปิดไว้ ระบบจะดึงและบันทึกผล แต่คงสถานะงวดเป็นปิดรับเพื่อให้ทีมงานกดออกผลเอง</small>
        </b-form-group>
        <b-form-group v-if="formaddedit.result_mode !== 'yeekee'">
            <b-form-checkbox v-model="formaddedit.auto_refund_on_no_result" :value="1" :unchecked-value="0">
                งดออกผลแล้วคืนเงินโพยอัตโนมัติ
            </b-form-checkbox>
            <small class="text-muted d-block">เมื่อผลเป็นงดออกผล ระบบจะยกเลิกโพย active และคืนเงินทั้งงวดทันที</small>
        </b-form-group>
        <b-form-group>
            <b-form-checkbox v-model="formaddedit.notify_result_telegram" :value="1" :unchecked-value="0">
                ส่งแจ้งเตือน Telegram เมื่อหวยนี้ออกผล
            </b-form-checkbox>
            <small class="text-muted d-block">ส่งผ่าน endpoint <code>notify/send</code></small>
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
                        <b-form-group label="รายการหวย (สรุป)">
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
                        <b-form-group label="รายการหวย">
                            <b-form-input size="sm" :value="selectedMarketText" readonly></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="ลำดับความสำคัญ">
                            <b-form-input size="sm" type="number" min="1" v-model="autoSourceForm.priority"></b-form-input>
                            <small class="text-muted d-block mt-1">เลขน้อยกว่าจะถูกเลือกก่อน (เช่น 1 สำคัญกว่า 2)</small>
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
                            <b-form-input size="sm" type="number" min="0" max="365" :disabled="!isLookupOffsetEditable" v-model="autoSourceForm.lookup_date_offset_days"></b-form-input>
                            <small class="text-muted d-block mt-1">โหมดตรงวัน/วันที่ประกาศผล จะล็อกค่าเป็น 0 อัตโนมัติ</small>
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
                            <b-form-select size="sm" :disabled="!isQuickHttpMethodEditable" v-model="autoSourceForm.quick_http_method" :options="autoSourceOptions.httpMethods"></b-form-select>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="รูปแบบวันที่ต้นทาง">
                            <b-form-select size="sm" :disabled="!isQuickJsonPathMode" v-model="autoSourceForm.quick_draw_date_from_format" :options="quickDateFormats"></b-form-select>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="4">
                        <b-form-group label="รูปแบบการดึงข้อมูล">
                            <b-form-select size="sm" v-model="autoSourceForm.quick_fetch_strategy" :options="quickFetchStrategies"></b-form-select>
                            <small class="text-muted d-block mt-1">@{{ quickFetchStrategyHelpText }}</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="4">
                        <b-form-group label="รูปแบบตัวแยกข้อมูล (Parser)">
                            <b-form-select size="sm" v-model="autoSourceForm.quick_parser_type" :options="quickParserTypes"></b-form-select>
                            <small class="text-muted d-block mt-1">@{{ quickParserTypeHelpText }}</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="4">
                        <b-form-group label="ขั้นเลือกข้อมูล (Selection Stage)">
                            <b-form-select size="sm" v-model="autoSourceForm.quick_selection_stage" :options="quickSelectionStages"></b-form-select>
                            <small class="text-muted d-block mt-1">@{{ quickSelectionStageHelpText }}</small>
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
                        <b-form-group label="Path ของวันที่">
                            <b-form-input size="sm" :disabled="!isQuickJsonPathMode" v-model="autoSourceForm.quick_draw_date_path" placeholder="$.date"></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="Path ของรางวัลที่ 1">
                            <b-form-input size="sm" :disabled="!isQuickJsonPathMode" v-model="autoSourceForm.quick_first_prize_paths" placeholder="$.lotData.DB"></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="เก็บท้ายกี่หลัก (รางวัลที่ 1)">
                            <b-form-input size="sm" :disabled="!isQuickJsonPathMode" type="number" min="0" max="10" v-model="autoSourceForm.quick_first_prize_take_right"></b-form-input>
                            <small class="text-muted d-block mt-1">ใส่ 0 = ไม่ตัดท้าย (ไม่ใส่ right transform)</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6">
                        <b-form-group label="Path ของเลขท้าย 2 ตัว">
                            <b-form-input size="sm" :disabled="!isQuickJsonPathMode" v-model="autoSourceForm.quick_last2_paths" placeholder="$.lotData.1"></b-form-input>
                        </b-form-group>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col md="6">
                        <b-form-group label="เก็บท้ายกี่หลัก (เลขท้าย)">
                            <b-form-input size="sm" :disabled="!isQuickJsonPathMode" type="number" min="1" max="10" v-model="autoSourceForm.quick_last2_take_right"></b-form-input>
                        </b-form-group>
                    </b-col>
                    <b-col md="6" class="pt-2">
                        <b-form-checkbox v-model="autoSourceForm.is_active" switch>เปิดใช้งาน</b-form-checkbox>
                        <b-form-checkbox v-model="autoSourceForm.supports_partial" switch class="mt-1">ยอมรับผลไม่ครบ (Partial)</b-form-checkbox>
                        <b-form-checkbox v-model="autoSourceForm.requires_browser" switch class="mt-1">ใช้ Browser Worker</b-form-checkbox>
                    </b-col>
                </b-row>

                <b-row class="mt-1">
                    <b-col md="6">
                        <b-form-group label="นโยบายการใช้ Browser Runtime">
                            <b-form-select size="sm" v-model="autoSourceForm.fetch_capability" :options="fetchCapabilityOptions"></b-form-select>
                            <small class="text-muted d-block mt-1">@{{ fetchCapabilityHelpText }}</small>
                        </b-form-group>
                    </b-col>
                    <b-col md="6" class="pt-4">
                        <b-form-checkbox :disabled="!canToggleDomFallback" v-model="autoSourceForm.allow_dom_fallback" switch>
                            อนุญาต DOM fallback
                        </b-form-checkbox>
                    </b-col>
                </b-row>

                <div v-if="shouldShowBrowserSettings" class="border rounded p-2 mb-2">
                    <h6 class="mb-2">Browser Worker Settings</h6>
                    <b-alert show variant="light" class="mb-0 py-2">
                        ระบบจะกำหนดค่า Browser Worker ที่เหมาะสมให้อัตโนมัติจากรูปแบบการดึงข้อมูลและ timeout ที่ตั้งไว้
                        โดยไม่ต้องปรับค่ารายละเอียดเอง
                    </b-alert>
                </div>

                <b-form-group label="เหตุผลการเปลี่ยนแปลง">
                    <b-form-input size="sm" v-model="autoSourceForm.revision_reason" placeholder="optional"></b-form-input>
                </b-form-group>

                <div class="d-flex flex-wrap">
                    <button type="button" class="btn btn-primary btn-sm mr-2 mb-2" @click="generateQuickPipelineJson">สร้าง JSON อัตโนมัติ</button>
                </div>
            </b-tab>

            <b-tab title="JSON หลัก">
                <div class="d-flex flex-wrap mb-2">
                    <button type="button" class="btn btn-outline-secondary btn-xs mr-1 mb-1" @click="applyAutoSourceExample">ใส่ตัวอย่าง</button>
                    <button type="button" class="btn btn-outline-secondary btn-xs mr-1 mb-1" @click="syncUnifiedFromForm">รีเฟรชค่าปัจจุบัน</button>
                </div>
                <b-form-group label="Pipeline Config JSON (Single Source of Truth)">
                    <b-form-textarea rows="14" v-model="autoSourceForm.unified_pipeline_json"></b-form-textarea>
                </b-form-group>
                <b-alert show variant="light" class="py-2">
                    ระบบจะ map ค่าใน JSON ก้อนนี้ไปยัง field ย่อยก่อน preview/validate/save อัตโนมัติ
                </b-alert>
                <b-alert show variant="warning" class="py-2 mb-0">
                    รูปแบบที่ต้องมี: <code>fetch_config_json</code>, <code>parser_config_json</code>, <code>mapping_config_json</code>, <code>selection_config_json</code>, <code>validation_config_json</code>, <code>readiness_config_json</code>
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
                    <button
                        type="button"
                        class="btn btn-info btn-sm mb-1 ml-1"
                        @click="dispatchBrowserTest">
                        Browser Test (Async)
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-info btn-sm mb-1 ml-1"
                        @click="checkBrowserTestStatus">
                        Check Browser Status
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

        #sourceEditorModal .form-group > label {
            min-height: 36px;
            display: flex;
            align-items: flex-end;
            margin-bottom: .25rem;
        }

        #sourceEditorModal small.text-muted.d-block.mt-1 {
            display: block;
            min-height: 34px;
            line-height: 1.25;
            margin-top: .35rem !important;
            word-break: break-word;
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
                        result_mode: 'normal',
                        yeekee_settings: {
                            round_duration_minutes: 15,
                            shoot_window_after_bet_close_seconds: 60,
                            settlement_delay_after_shoot_close_seconds: 60,
                            expected_payout_sla_minutes: 5,
                            formula_preset: 'SHOOTS_SUM_MINUS_POSITION',
                            subtract_position: 16,
                            reward_enabled: false,
                            refund_if_bet_entries_below_min: false,
                            min_bet_amount: 0,
                            min_bet_entries_required: 0,
                            refund_count_mode: 'count_bet_entries',
                            refund_action: 'VOID_AND_REFUND',
                        },
                        draw_schedule_type: 'manual',
                        draw_days: [],
                        draw_dates: [],
                        auto_open_time: '',
                        auto_close_time: '',
                        auto_result_time: '',
                        result_url: '',
                        auto_settle_on_result: 1,
                        auto_refund_on_no_result: 0,
                        notify_result_telegram: 1,
                        is_enabled: 1,
                    },
                    option: {
                        groups: [
                            { value: '', text: '-- เลือกกลุ่มหวย --' },
                            @foreach($groups as $g)
                            { value: {{ $g->id }}, text: '{{ $g->name }} ({{ $g->code }})' },
                            @endforeach
                        ],
                        drawScheduleTypes: [
                            { value: 'manual', text: 'Manual (เพิ่มงวดเอง)' },
                            { value: 'weekly', text: 'Weekly (เลือกรายวัน)' },
                            { value: 'monthly', text: 'Monthly (เลือกรายเดือน)' },
                        ],
                        resultModes: [
                            { value: 'normal', text: 'หวยปกติ (Normal)' },
                            { value: 'yeekee', text: 'หวยยี่กี่ (Yeekee)' },
                        ],
                        yeekeeFormulaPresets: [
                            { value: 'SHOOTS_SUM_MINUS_POSITION', text: 'หาผลรวมเลขยิงแล้วลบลำดับที่กำหนด' },
                            { value: 'PRECOMMITTED_BASE64_MD5', text: 'ตรวจสอบผลด้วย Signature + Payload' },
                        ],
                        yeekeeRefundCountModes: [
                            { value: 'count_bet_entries', text: 'นับทุกรายการแทง' },
                            { value: 'count_distinct_members', text: 'นับสมาชิกไม่ซ้ำ' },
                        ],
                        yeekeeRefundActions: [
                            { value: 'VOID_AND_REFUND', text: 'งดออกผลและคืนโพย' },
                        ],
                        weeklyDays: [
                            { value: 1, text: 'จันทร์' },
                            { value: 2, text: 'อังคาร' },
                            { value: 3, text: 'พุธ' },
                            { value: 4, text: 'พฤหัสบดี' },
                            { value: 5, text: 'ศุกร์' },
                            { value: 6, text: 'เสาร์' },
                            { value: 7, text: 'อาทิตย์' },
                        ],
                        monthlyDates: Array.from({ length: 31 }, (_, index) => {
                            const day = index + 1;
                            return { value: day, text: `วันที่ ${day}` };
                        }),
                    },
                    permissions: {
                        canTestFetch: @json($canAutoResultTestFetch),
                        canViewLogs: @json($canAutoResultLogs),
                    },
                    autoSourcesModal: {
                        marketId: null,
                        marketName: '',
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
                    browserWaitUntilOptions: [
                        { value: 'networkidle', text: 'networkidle' },
                        { value: 'domcontentloaded', text: 'domcontentloaded' },
                        { value: 'load', text: 'load' },
                        { value: 'commit', text: 'commit' },
                    ],
                    browserSelectionModes: [
                        { value: 'best', text: 'best' },
                        { value: 'first', text: 'first' },
                        { value: 'all', text: 'all' },
                    ],
                    fetchCapabilityOptions: [
                        { value: 'http_only', text: 'ใช้ HTTP เท่านั้น (http_only)' },
                        { value: 'prefer_browser_runtime', text: 'ลอง Browser ก่อน ถ้าเจอเหตุที่อนุญาตค่อย fallback (prefer_browser_runtime)' },
                        { value: 'require_browser_runtime', text: 'บังคับ Browser เท่านั้น (require_browser_runtime)' },
                    ],
                    quickFetchStrategies: [
                        { value: 'JSON_HTTP', text: 'ดึง JSON โดยตรง (JSON_HTTP)' },
                        { value: 'HTML_HTTP', text: 'ดึง HTML แล้วค่อย parse (HTML_HTTP)' },
                        { value: 'RENDERED_BROWSER', text: 'เปิดหน้าเว็บด้วย Browser Runtime (RENDERED_BROWSER)' },
                        { value: 'EMBEDDED_JSON', text: 'ดึง JSON ที่ฝังในหน้า (EMBEDDED_JSON)' },
                    ],
                    quickParserTypes: [
                        { value: 'JSON_PATH', text: 'อ่านค่าแบบเส้นทาง JSON (JSON_PATH)' },
                    ],
                    quickSelectionStages: [
                        { value: 'PRE_MAPPING', text: 'คัดเลือกก่อนแปลงค่า (PRE_MAPPING)' },
                        { value: 'POST_MAPPING', text: 'คัดเลือกหลังแปลงค่า (POST_MAPPING)' },
                    ],
                    sourceTestDate: '',
                    sourceTestRunId: '',
                    testRunResult: null,
                    sourceTestLogs: [],
                    browserTestReceiptKey: '',
                    browserTestResult: null,
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

                    const modalMarketName = String(this.autoSourcesModal.marketName || '').trim();
                    if (modalMarketName !== '') {
                        return modalMarketName;
                    }

                    const found = (this.autoSourcesItems || []).find((item) => Number(item.market_id || 0) === marketId);
                    if (found && found.market_name) {
                        return found.market_name;
                    }

                    return `Market #${marketId}`;
                },
                isLookupOffsetEditable() {
                    const mode = String(this.autoSourceForm.lookup_date_mode || 'ROUND_DATE');
                    return mode === 'ROUND_DATE_MINUS_DAYS' || mode === 'ROUND_DATE_PLUS_DAYS';
                },
                isQuickHttpMethodEditable() {
                    return String(this.autoSourceForm.quick_fetch_strategy || 'JSON_HTTP').toUpperCase() !== 'MANUAL_INPUT';
                },
                isQuickJsonPathMode() {
                    return String(this.autoSourceForm.quick_parser_type || 'JSON_PATH').toUpperCase() === 'JSON_PATH';
                },
                shouldShowBrowserSettings() {
                    const strategy = String(this.autoSourceForm.quick_fetch_strategy || this.autoSourceForm.fetch_strategy || 'JSON_HTTP').toUpperCase();
                    return !!this.autoSourceForm.requires_browser || strategy === 'RENDERED_BROWSER';
                },
                canToggleDomFallback() {
                    return this.normalizedFetchCapability !== 'http_only';
                },
                normalizedFetchCapability() {
                    return String(this.autoSourceForm.fetch_capability || 'http_only').trim().toLowerCase();
                },
                quickFetchStrategyHelpText() {
                    const strategy = String(this.autoSourceForm.quick_fetch_strategy || 'JSON_HTTP').toUpperCase();
                    const map = {
                        JSON_HTTP: 'เหมาะกับปลายทางที่ตอบ JSON โดยตรง เร็วและเสถียรกว่าแบบ Browser',
                        HTML_HTTP: 'เหมาะกับหน้าผลที่เป็น HTML แล้วดึงข้อมูลจากโครงสร้างหน้าเว็บ',
                        RENDERED_BROWSER: 'ใช้เมื่อหน้าเว็บต้องรัน JavaScript ก่อนข้อมูลจะแสดง ระบบจะใช้ Browser Worker',
                        EMBEDDED_JSON: 'ใช้เมื่อหน้า HTML มี JSON ฝังอยู่ใน script หรือ payload ภายใน',
                    };

                    return map[strategy] || strategy;
                },
                quickParserTypeHelpText() {
                    const parserType = String(this.autoSourceForm.quick_parser_type || 'JSON_PATH').toUpperCase();
                    const map = {
                        JSON_PATH: 'ระบุ path เช่น $.lotData.DB เพื่อดึงค่าจาก JSON',
                    };

                    return map[parserType] || parserType;
                },
                quickSelectionStageHelpText() {
                    const stage = String(this.autoSourceForm.quick_selection_stage || 'PRE_MAPPING').toUpperCase();
                    const map = {
                        PRE_MAPPING: 'คัดเลือกรายการก่อนแปลงข้อมูล เหมาะกับกรณี source ส่งหลาย candidate',
                        POST_MAPPING: 'คัดเลือกหลังแปลงข้อมูล เหมาะเมื่อเงื่อนไขอิงค่าที่ normalize แล้ว',
                    };

                    return map[stage] || stage;
                },
                fetchCapabilityHelpText() {
                    const capability = String(this.autoSourceForm.fetch_capability || 'http_only');
                    const map = {
                        http_only: 'วิ่ง HTTP อย่างเดียว ไม่ใช้ browser runtime',
                        prefer_browser_runtime: 'ลอง browser runtime ก่อน และ fallback ตาม reason code ที่อนุญาต',
                        require_browser_runtime: 'บังคับ browser runtime เท่านั้น หาก browser ใช้ไม่ได้จะ fail',
                    };

                    return map[capability] || capability;
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
                        priority: 1,
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
                        quick_fetch_strategy: 'JSON_HTTP',
                        quick_parser_type: 'JSON_PATH',
                        quick_selection_stage: 'PRE_MAPPING',
                        quick_draw_date_path: '$.date',
                        quick_draw_date_from_format: 'Y-m-d',
                        quick_first_prize_paths: '$.lotData.DB',
                        quick_first_prize_take_right: 0,
                        quick_last2_paths: '$.lotData.1',
                        quick_last2_take_right: 2,
                        browser_wait_for_selector: '',
                        browser_wait_until: 'networkidle',
                        browser_timeout_ms: 15000,
                        browser_selection_mode: 'best',
                        browser_capture_url_patterns: '',
                        browser_block_resource_types: '',
                        fetch_capability: 'http_only',
                        allow_dom_fallback: false,
                        request_headers_json: '{}',
                        request_query_template_json: '{}',
                        request_body_template_json: '{}',
                        parser_config_json: '{}',
                        mapping_config_json: '{}',
                        validation_config_json: '{"required_fields":["draw_date","first_prize","last_2_digits"]}',
                        retry_policy_json: '{"max_attempts":5,"backoff_seconds":[300,300,300,300,300]}',
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
                            max_attempts: 5,
                            backoff_seconds: [300, 300, 300, 300, 300],
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
                normalizeUnifiedConfig(unified) {
                    const normalized = (unified && typeof unified === 'object') ? { ...unified } : {};
                    const selectionConfig = (normalized.selection_config_json && typeof normalized.selection_config_json === 'object')
                        ? { ...normalized.selection_config_json }
                        : {};
                    const topLevelStage = String(normalized.selection_stage || '').trim().toUpperCase();
                    const selectionStage = String(selectionConfig.selection_stage || '').trim().toUpperCase();
                    if (!selectionStage && (topLevelStage === 'PRE_MAPPING' || topLevelStage === 'POST_MAPPING')) {
                        selectionConfig.selection_stage = topLevelStage;
                    }
                    normalized.selection_config_json = selectionConfig;
                    return normalized;
                },
                parseUnifiedConfigForPayload() {
                    const unifiedRaw = this.parseJsonSafe(this.autoSourceForm.unified_pipeline_json);
                    return this.normalizeUnifiedConfig(unifiedRaw);
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
                    const unified = this.normalizeUnifiedConfig(this.parseJsonSafe(this.autoSourceForm.unified_pipeline_json));
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
                    this.populateBrowserWorkerFromFetchConfig(unified.fetch_config_json || {});
                    this.populateQuickFromUnified(unified);
                },
                buildBrowserWorkerMetaFromForm() {
                    const strategy = String(this.autoSourceForm.quick_fetch_strategy || this.autoSourceForm.fetch_strategy || 'JSON_HTTP').toUpperCase();
                    const timeoutSeconds = Number(this.autoSourceForm.timeout_seconds || 10);
                    const timeoutMs = Math.max(5000, Math.min(120000, timeoutSeconds * 1500));

                    if (strategy !== 'RENDERED_BROWSER' && !this.autoSourceForm.requires_browser) {
                        return {
                            wait_for_selector: '',
                            wait_until: 'networkidle',
                            timeout_ms: timeoutMs,
                            capture_url_patterns: [],
                            block_resource_types: ['image', 'font', 'media'],
                            capture: {
                                selection_mode: 'best',
                            },
                        };
                    }

                    return {
                        wait_for_selector: '',
                        wait_until: 'networkidle',
                        timeout_ms: timeoutMs,
                        capture_url_patterns: [],
                        block_resource_types: ['image', 'font', 'media'],
                        capture: {
                            selection_mode: 'best',
                        },
                    };
                },
                populateBrowserWorkerFromFetchConfig(fetchConfig) {
                    const meta = (fetchConfig && typeof fetchConfig === 'object' && fetchConfig.meta && typeof fetchConfig.meta === 'object')
                        ? fetchConfig.meta
                        : {};
                    const browser = (meta.browser_worker && typeof meta.browser_worker === 'object') ? meta.browser_worker : {};
                    const runtime = (meta.runtime && typeof meta.runtime === 'object') ? meta.runtime : {};
                    const capturePatterns = Array.isArray(browser.capture_url_patterns) ? browser.capture_url_patterns : [];
                    const blockTypes = Array.isArray(browser.block_resource_types) ? browser.block_resource_types : [];
                    const captureObj = (browser.capture && typeof browser.capture === 'object') ? browser.capture : {};

                    this.autoSourceForm.browser_wait_for_selector = String(browser.wait_for_selector || '');
                    this.autoSourceForm.browser_wait_until = String(browser.wait_until || 'networkidle').toLowerCase();
                    this.autoSourceForm.browser_timeout_ms = Number(browser.timeout_ms || 15000);
                    this.autoSourceForm.browser_selection_mode = String(captureObj.selection_mode || 'best').toLowerCase();
                    this.autoSourceForm.browser_capture_url_patterns = capturePatterns.map((v) => String(v || '').trim()).filter((v) => v !== '').join('\n');
                    this.autoSourceForm.browser_block_resource_types = blockTypes.map((v) => String(v || '').trim()).filter((v) => v !== '').join(',');
                    this.autoSourceForm.fetch_capability = String(runtime.fetch_capability || 'http_only');
                    this.autoSourceForm.allow_dom_fallback = !!runtime.allow_dom_fallback;
                },
                populateQuickFromUnified(unified) {
                    const fetchConfig = (unified.fetch_config_json && typeof unified.fetch_config_json === 'object') ? unified.fetch_config_json : {};
                    const parserConfig = (unified.parser_config_json && typeof unified.parser_config_json === 'object') ? unified.parser_config_json : {};
                    const mappingConfig = (unified.mapping_config_json && typeof unified.mapping_config_json === 'object') ? unified.mapping_config_json : {};
                    const selectionConfig = (unified.selection_config_json && typeof unified.selection_config_json === 'object') ? unified.selection_config_json : {};
                    const fields = (parserConfig.fields && typeof parserConfig.fields === 'object') ? parserConfig.fields : {};
                    const mapFields = (mappingConfig.fields && typeof mappingConfig.fields === 'object') ? mappingConfig.fields : {};

                    this.autoSourceForm.quick_endpoint_url = String(fetchConfig.endpoint_url || this.autoSourceForm.quick_endpoint_url || '');
                    this.autoSourceForm.quick_http_method = String(fetchConfig.http_method || this.autoSourceForm.quick_http_method || 'GET').toUpperCase();
                    this.autoSourceForm.quick_fetch_strategy = String(fetchConfig.fetch_strategy || this.autoSourceForm.quick_fetch_strategy || 'JSON_HTTP').toUpperCase();
                    this.autoSourceForm.quick_parser_type = String(parserConfig.parser_type || this.autoSourceForm.quick_parser_type || 'JSON_PATH').toUpperCase();
                    this.autoSourceForm.quick_selection_stage = String(selectionConfig.selection_stage || this.autoSourceForm.quick_selection_stage || 'PRE_MAPPING').toUpperCase();
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
                    this.autoSourceForm.quick_first_prize_take_right = firstRight && Number((firstRight || {}).length || 0) > 0
                        ? Number((firstRight || {}).length || 0)
                        : 0;
                    this.autoSourceForm.quick_last2_take_right = Number((last2Right || {}).length || this.autoSourceForm.quick_last2_take_right || 2);
                    this.enforceQuickDependencyRules();
                    this.populateBrowserWorkerFromFetchConfig(fetchConfig);
                },
                splitQuickPaths(text) {
                    return String(text || '').split(',').map((v) => v.trim()).filter((v) => v !== '');
                },
                applyQuickPresetLaosVip() {
                    this.autoSourceForm.quick_endpoint_url = 'https://laosviplot.com/result';
                    this.autoSourceForm.quick_http_method = 'GET';
                    this.autoSourceForm.quick_fetch_strategy = 'JSON_HTTP';
                    this.autoSourceForm.quick_parser_type = 'JSON_PATH';
                    this.autoSourceForm.quick_selection_stage = 'PRE_MAPPING';
                    this.autoSourceForm.quick_draw_date_path = '$.date';
                    this.autoSourceForm.quick_draw_date_from_format = 'd/m/Y';
                    this.autoSourceForm.quick_first_prize_paths = '$.lotto_2';
                    this.autoSourceForm.quick_first_prize_take_right = 3;
                    this.autoSourceForm.quick_last2_paths = '$.lotto_1';
                    this.autoSourceForm.quick_last2_take_right = 2;
                },
                enforceQuickDependencyRules() {
                    const mode = String(this.autoSourceForm.lookup_date_mode || 'ROUND_DATE');
                    if (mode === 'ROUND_DATE' || mode === 'RESULT_AT_DATE') {
                        this.autoSourceForm.lookup_date_offset_days = 0;
                    } else {
                        this.autoSourceForm.lookup_date_offset_days = Math.abs(Number(this.autoSourceForm.lookup_date_offset_days || 0));
                    }

                    const strategy = String(this.autoSourceForm.quick_fetch_strategy || 'JSON_HTTP').toUpperCase();
                    let capability = String(this.autoSourceForm.fetch_capability || 'http_only').trim().toLowerCase();
                    if (!['http_only', 'prefer_browser_runtime', 'require_browser_runtime'].includes(capability)) {
                        capability = 'http_only';
                    }
                    this.autoSourceForm.fetch_capability = capability;

                    if (strategy === 'RENDERED_BROWSER') {
                        this.autoSourceForm.requires_browser = true;
                        if (capability === 'http_only') {
                            this.autoSourceForm.fetch_capability = 'prefer_browser_runtime';
                            capability = 'prefer_browser_runtime';
                        }
                    }

                    if (capability === 'http_only') {
                        this.autoSourceForm.allow_dom_fallback = false;
                        if (strategy !== 'RENDERED_BROWSER') {
                            this.autoSourceForm.requires_browser = false;
                        }
                    }

                    if (capability === 'require_browser_runtime') {
                        this.autoSourceForm.requires_browser = true;
                    }
                },
                generateQuickPipelineJson() {
                    this.enforceQuickDependencyRules();
                    const endpointUrl = String(this.autoSourceForm.quick_endpoint_url || '').trim();
                    if (!endpointUrl) {
                        this.showApiError(null, 'กรุณาใส่ URL ผลหวย');
                        return;
                    }

                    const firstPrizePaths = this.splitQuickPaths(this.autoSourceForm.quick_first_prize_paths);
                    const last2Paths = this.splitQuickPaths(this.autoSourceForm.quick_last2_paths);
                    const mode = String(this.autoSourceForm.lookup_date_mode || 'ROUND_DATE');
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
                            fetch_strategy: String(this.autoSourceForm.quick_fetch_strategy || 'JSON_HTTP').toUpperCase(),
                            endpoint_url: endpointUrl,
                            http_method: String(this.autoSourceForm.quick_http_method || 'GET').toUpperCase(),
                            headers: {},
                            query: {},
                            timeout_seconds: Number(this.autoSourceForm.timeout_seconds || 10),
                            meta: {
                                runtime: {
                                    fetch_capability: String(this.autoSourceForm.fetch_capability || 'http_only').trim(),
                                    allow_dom_fallback: !!this.autoSourceForm.allow_dom_fallback,
                                },
                            },
                        },
                        parser_config_json: {
                            version: 2,
                            mode: 'single_payload',
                            parser_type: String(this.autoSourceForm.quick_parser_type || 'JSON_PATH').toUpperCase(),
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
                                first_prize: (() => {
                                    const transforms = [{ op: 'digits_only' }];
                                    const rightDigits = Math.max(0, Number(this.autoSourceForm.quick_first_prize_take_right || 0));
                                    if (rightDigits > 0) {
                                        transforms.push({ op: 'right', length: rightDigits });
                                    }
                                    return {
                                        from: firstKey,
                                        transforms,
                                    };
                                })(),
                                last_2_digits: {
                                    from: last2Key,
                                    transforms: [{ op: 'digits_only' }, { op: 'right', length: Number(this.autoSourceForm.quick_last2_take_right || 2) }],
                                },
                            },
                        },
                        selection_config_json: {
                            selection_stage: String(this.autoSourceForm.quick_selection_stage || 'PRE_MAPPING').toUpperCase(),
                            strategy: 'strict_single_match',
                            date_field: String(this.autoSourceForm.quick_selection_stage || 'PRE_MAPPING').toUpperCase() === 'POST_MAPPING'
                                ? 'draw_date'
                                : 'draw_date_raw',
                            required_fields: [],
                            meta: {
                                candidate_draw_date_offset_days: mode === 'ROUND_DATE_MINUS_DAYS'
                                    ? Math.abs(Number(this.autoSourceForm.lookup_date_offset_days || 0))
                                    : (mode === 'ROUND_DATE_PLUS_DAYS' ? (-1 * Math.abs(Number(this.autoSourceForm.lookup_date_offset_days || 0))) : 0),
                                expected_draw_date_offset_days: 0,
                            },
                        },
                        validation_config_json: { required_fields: ['draw_date', 'first_prize', 'last_2_digits'] },
                        readiness_config_json: { enabled: true, minimum_required_keys: ['draw_date', 'first_prize', 'last_2_digits'] },
                        retry_policy_json: { max_attempts: 5, backoff_seconds: [300, 300, 300, 300, 300] },
                    };

                    this.autoSourceForm.unified_pipeline_json = JSON.stringify(unified, null, 2);
                    this.applyUnifiedToForm();
                    this.autoSourceForm.endpoint_url = endpointUrl;
                    this.autoSourceForm.http_method = String(this.autoSourceForm.quick_http_method || 'GET').toUpperCase();
                    this.autoSourceForm.parser_type = String(this.autoSourceForm.quick_parser_type || 'JSON_PATH').toUpperCase();
                    this.autoSourceForm.fetch_strategy = String(this.autoSourceForm.quick_fetch_strategy || 'JSON_HTTP').toUpperCase();
                    this.autoSourceForm.selection_stage = String(this.autoSourceForm.quick_selection_stage || 'PRE_MAPPING').toUpperCase();
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
                    const form = this.autoSourceForm;
                    const unified = this.parseUnifiedConfigForPayload();
                    const fetchConfig = (unified.fetch_config_json && typeof unified.fetch_config_json === 'object')
                        ? unified.fetch_config_json
                        : {};
                    const parserConfig = (unified.parser_config_json && typeof unified.parser_config_json === 'object')
                        ? unified.parser_config_json
                        : {};
                    const selectionConfig = (unified.selection_config_json && typeof unified.selection_config_json === 'object')
                        ? unified.selection_config_json
                        : {};
                    return {
                        id: form.id || null,
                        market_id: Number(form.market_id || 0),
                        is_active: !!form.is_active,
                        priority: Number(form.priority || 1),
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
                        request_headers_json: unified.request_headers_json || {},
                        request_query_template_json: unified.request_query_template_json || {},
                        request_body_template_json: unified.request_body_template_json || {},
                        parser_config_json: unified.parser_config_json || {},
                        mapping_config_json: unified.mapping_config_json || {},
                        validation_config_json: unified.validation_config_json || {},
                        retry_policy_json: unified.retry_policy_json || {},
                        fetch_config_json: unified.fetch_config_json || {},
                        selection_config_json: selectionConfig,
                        readiness_config_json: unified.readiness_config_json || {},
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
                    this.formaddedit = { group_id: '', name: '', name_en: '', name_kh: '', name_laos: '', logo: '', icon: '', logo_file: null, icon_file: null, code: '', result_mode: 'normal', yeekee_settings: { round_duration_minutes: 15, shoot_window_after_bet_close_seconds: 60, settlement_delay_after_shoot_close_seconds: 60, expected_payout_sla_minutes: 5, formula_preset: 'SHOOTS_SUM_MINUS_POSITION', subtract_position: 16, reward_enabled: false, refund_if_bet_entries_below_min: false, min_bet_amount: 0, min_bet_entries_required: 0, refund_count_mode: 'count_bet_entries', refund_action: 'VOID_AND_REFUND' }, draw_schedule_type: 'manual', draw_days: [], draw_dates: [], auto_open_time: '', auto_close_time: '', auto_result_time: '', result_url: '', auto_settle_on_result: 1, auto_refund_on_no_result: 0, notify_result_telegram: 1, is_enabled: 1 };
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
                    this.formaddedit = { group_id: '', name: '', name_en: '', name_kh: '', name_laos: '', logo: '', icon: '', logo_file: null, icon_file: null, code: '', result_mode: 'normal', yeekee_settings: { round_duration_minutes: 15, shoot_window_after_bet_close_seconds: 60, settlement_delay_after_shoot_close_seconds: 60, expected_payout_sla_minutes: 5, formula_preset: 'SHOOTS_SUM_MINUS_POSITION', subtract_position: 16, reward_enabled: false, refund_if_bet_entries_below_min: false, min_bet_amount: 0, min_bet_entries_required: 0, refund_count_mode: 'count_bet_entries', refund_action: 'VOID_AND_REFUND' }, draw_schedule_type: 'manual', draw_days: [], draw_dates: [], auto_open_time: '', auto_close_time: '', auto_result_time: '', result_url: '', auto_settle_on_result: 1, auto_refund_on_no_result: 0, notify_result_telegram: 1, is_enabled: 1 };
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
                    this.autoSourcesModal.marketName = label;
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
                    this.enforceQuickDependencyRules();
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
                        this.enforceQuickDependencyRules();
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
                sleepMs(ms) {
                    const timeout = Math.max(0, Number(ms || 0));
                    return new Promise((resolve) => setTimeout(resolve, timeout));
                },
                isDryRunPending(data) {
                    const status = String((data && data.status) || '').toUpperCase();
                    const errorCode = String((data && data.error_code) || '').toUpperCase();
                    return status === 'FETCH_DEFERRED' || errorCode === 'FETCH_DEFERRED';
                },
                async dispatchDryRunByDate(marketId, drawDate) {
                    const res = await axios.post("{{ route('admin.lotto.result_sources.test_fetch_by_date') }}", {
                        market_id: Number(marketId || 0),
                        draw_date: drawDate,
                    });
                    return ((res || {}).data || {}).data || {};
                },
                async pollBrowserReceipt(receiptKey, maxAttempts = 45, intervalMs = 2000) {
                    const receipt = String(receiptKey || '').trim();
                    if (!receipt) {
                        throw new Error('ยังไม่มี receipt_key สำหรับ polling');
                    }

                    for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
                        const res = await axios.get("{{ route('admin.lotto.result_sources.browser_test_status') }}", {
                            params: {
                                receipt_key: receipt,
                            },
                        });
                        const data = ((res || {}).data || {}).data || {};
                        const status = String(data.status || '').toUpperCase();
                        this.browserTestResult = data;
                        if (status !== 'FETCH_DEFERRED' && status !== '') {
                            return data;
                        }
                        await this.sleepMs(intervalMs);
                    }

                    throw new Error('หมดเวลารอผล Browser worker (dry-run async)');
                },
                async runAutoSourceTestByDate() {
                    if (!this.sourceTestDate) {
                        this.showApiError(null, 'กรุณาเลือกวันที่ทดสอบ');
                        return;
                    }

                    try {
                        const marketId = Number(this.autoSourceForm.market_id || this.autoSourcesModal.marketId || 0);
                        let data = await this.dispatchDryRunByDate(marketId, this.sourceTestDate);
                        this.browserTestReceiptKey = String(data.receipt_key || '');

                        let asyncPollSummary = '';
                        let guard = 0;
                        while (this.isDryRunPending(data) && this.browserTestReceiptKey && guard < 2) {
                            const browserStatus = await this.pollBrowserReceipt(this.browserTestReceiptKey);
                            asyncPollSummary = `\nasync_browser_status: ${String(browserStatus.status || '-')}`;
                            data = await this.dispatchDryRunByDate(marketId, this.sourceTestDate);
                            this.browserTestReceiptKey = String(data.receipt_key || this.browserTestReceiptKey || '');
                            guard += 1;
                        }

                        this.sourceTestRunId = data.run_id || '';
                        this.testRunResult = data;
                        const output = (String(data.output || '').trim() || 'Dry-run สำเร็จ') + asyncPollSummary;
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
                async dispatchBrowserTest() {
                    if (!this.sourceTestDate) {
                        this.showApiError(null, 'กรุณาเลือกวันที่ทดสอบ');
                        return;
                    }

                    try {
                        const payload = this.makeSourcePayload();
                        const res = await axios.post("{{ route('admin.lotto.result_sources.browser_test_dispatch') }}", {
                            market_id: Number(this.autoSourceForm.market_id || this.autoSourcesModal.marketId || 0),
                            draw_date: this.sourceTestDate,
                            data: payload,
                        });
                        const data = ((res || {}).data || {}).data || {};
                        this.browserTestReceiptKey = String(data.receipt_key || '');
                        this.browserTestResult = data;
                        this.$bvModal.msgBoxOk(JSON.stringify(data, null, 2), {
                            title: 'Browser Test Dispatch',
                            size: 'xl',
                            buttonSize: 'sm',
                            centered: true,
                        });
                    } catch (error) {
                        this.showApiError(error, 'ส่ง Browser test ไม่สำเร็จ');
                    }
                },
                async checkBrowserTestStatus() {
                    if (!this.browserTestReceiptKey) {
                        this.showApiError(null, 'ยังไม่มี receipt_key จาก Browser test');
                        return;
                    }

                    try {
                        const res = await axios.get("{{ route('admin.lotto.result_sources.browser_test_status') }}", {
                            params: {
                                receipt_key: this.browserTestReceiptKey,
                            },
                        });
                        const data = ((res || {}).data || {}).data || {};
                        this.browserTestResult = data;
                        this.$bvModal.msgBoxOk(JSON.stringify(data, null, 2), {
                            title: 'Browser Test Status',
                            size: 'xl',
                            buttonSize: 'sm',
                            centered: true,
                        });
                    } catch (error) {
                        this.showApiError(error, 'ตรวจสอบ Browser test ไม่สำเร็จ');
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
                    const trace = (log && typeof log.trace_json === 'object' && log.trace_json !== null)
                        ? log.trace_json
                        : {};
                    this.$bvModal.msgBoxOk(JSON.stringify(trace, null, 2), {
                        title: `Log #${log.id}`,
                        size: 'xl',
                        buttonSize: 'sm',
                        centered: true,
                    });
                },
                async loadData() {
                    const response = await axios.post("{{ route('admin.lotto.markets.loaddata') }}", { id: this.code });
                    const d = response.data.data;
                    const legacySchedule = this.resolveScheduleFromLegacyDrawMode(d.draw_mode);
                    const resolvedScheduleType = d.draw_schedule_type || legacySchedule.type;
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
                        result_mode: d.result_mode || 'normal',
                        yeekee_settings: {
                            round_duration_minutes: Number((d.yeekee_settings || {}).round_duration_minutes || 15),
                            shoot_window_after_bet_close_seconds: Number((d.yeekee_settings || {}).shoot_window_after_bet_close_seconds || 60),
                            settlement_delay_after_shoot_close_seconds: Number((d.yeekee_settings || {}).settlement_delay_after_shoot_close_seconds || 60),
                            expected_payout_sla_minutes: Number((d.yeekee_settings || {}).expected_payout_sla_minutes || 5),
                            formula_preset: String((d.yeekee_settings || {}).formula_preset || 'SHOOTS_SUM_MINUS_POSITION'),
                            subtract_position: Number((d.yeekee_settings || {}).subtract_position || 16),
                            reward_enabled: Boolean((d.yeekee_settings || {}).reward_enabled || false),
                            refund_if_bet_entries_below_min: Boolean((d.yeekee_settings || {}).refund_if_bet_entries_below_min || false),
                            min_bet_amount: Number((d.yeekee_settings || {}).min_bet_amount || 0),
                            min_bet_entries_required: Number((d.yeekee_settings || {}).min_bet_entries_required || 0),
                            refund_count_mode: String((d.yeekee_settings || {}).refund_count_mode || 'count_bet_entries'),
                            refund_action: String((d.yeekee_settings || {}).refund_action || 'VOID_AND_REFUND'),
                        },
                        draw_schedule_type: resolvedScheduleType,
                        draw_days: this.normalizeNumberArray(d.draw_days, 1, 7),
                        draw_dates: this.normalizeNumberArray(d.draw_dates, 1, 31),
                        auto_open_time: d.auto_open_time ? String(d.auto_open_time).substring(0, 5) : '',
                        auto_close_time: d.auto_close_time ? String(d.auto_close_time).substring(0, 5) : '',
                        auto_result_time: d.auto_result_time ? String(d.auto_result_time).substring(0, 5) : '',
                        result_url: d.result_url || '',
                        auto_settle_on_result: d.auto_settle_on_result === undefined || d.auto_settle_on_result === null ? 1 : (d.auto_settle_on_result ? 1 : 0),
                        auto_refund_on_no_result: d.auto_refund_on_no_result ? 1 : 0,
                        notify_result_telegram: d.notify_result_telegram === undefined || d.notify_result_telegram === null ? 1 : (d.notify_result_telegram ? 1 : 0),
                        is_enabled: d.is_enabled ? 1 : 0,
                    };

                    if (!d.draw_schedule_type) {
                        this.formaddedit.draw_days = legacySchedule.days;
                        this.formaddedit.draw_dates = legacySchedule.dates;
                    }

                    this.normalizeScheduleFormData();
                },
                normalizeNumberArray(value, min, max) {
                    if (!Array.isArray(value)) {
                        return [];
                    }

                    const normalized = value
                        .map((item) => parseInt(item, 10))
                        .filter((item) => Number.isInteger(item) && item >= min && item <= max);

                    return Array.from(new Set(normalized)).sort((a, b) => a - b);
                },
                resolveScheduleFromLegacyDrawMode(drawMode) {
                    const mode = String(drawMode || 'manual');
                    if (mode === 'daily') {
                        return { type: 'weekly', days: [1, 2, 3, 4, 5, 6, 7], dates: [] };
                    }

                    if (mode === 'weekdays') {
                        return { type: 'weekly', days: [1, 2, 3, 4, 5], dates: [] };
                    }

                    if (mode === 'wed_sat_sun') {
                        return { type: 'weekly', days: [3, 6, 7], dates: [] };
                    }

                    return { type: 'manual', days: [], dates: [] };
                },
                normalizeScheduleFormData() {
                    const type = String(this.formaddedit.draw_schedule_type || 'manual');
                    this.formaddedit.draw_days = this.normalizeNumberArray(this.formaddedit.draw_days, 1, 7);
                    this.formaddedit.draw_dates = this.normalizeNumberArray(this.formaddedit.draw_dates, 1, 31);

                    if (type === 'manual') {
                        this.formaddedit.draw_days = [];
                        this.formaddedit.draw_dates = [];
                    }

                    if (type === 'weekly') {
                        this.formaddedit.draw_dates = [];
                    }

                    if (type === 'monthly') {
                        this.formaddedit.draw_days = [];
                    }
                },
                addEditSubmit() {
                    this.normalizeScheduleFormData();
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

                    const appendPayload = (key, value) => {
                        if (Array.isArray(value)) {
                            value.forEach((item) => {
                                appendPayload(`${key}[]`, item);
                            });
                            return;
                        }

                        if (value !== null && typeof value === 'object') {
                            Object.keys(value).forEach((childKey) => {
                                appendPayload(`${key}[${childKey}]`, value[childKey]);
                            });
                            return;
                        }

                        formData.append(key, value ?? '');
                    };

                    Object.keys(this.formaddedit)
                        .filter((key) => !['logo_file', 'icon_file'].includes(key))
                        .forEach((key) => {
                            appendPayload(`data[${key}]`, this.formaddedit[key]);
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
                    const mode = this.formaddedit.draw_schedule_type || 'manual';
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

                    if (mode === 'weekly' && this.formaddedit.draw_days.length === 0) {
                        return 'โหมดรายสัปดาห์จำเป็นต้องเลือกวันออกงวดอย่างน้อย 1 วัน';
                    }

                    if (mode === 'monthly' && this.formaddedit.draw_dates.length === 0) {
                        return 'โหมดรายเดือนจำเป็นต้องเลือกวันที่ออกงวดอย่างน้อย 1 วัน';
                    }

                    return '';
                },
            },
            watch: {
                'formaddedit.draw_schedule_type'() {
                    this.normalizeScheduleFormData();
                },
                'autoSourceForm.lookup_date_mode'() {
                    this.enforceQuickDependencyRules();
                },
                'autoSourceForm.lookup_date_offset_days'() {
                    this.enforceQuickDependencyRules();
                },
                'autoSourceForm.quick_fetch_strategy'() {
                    this.enforceQuickDependencyRules();
                },
                'autoSourceForm.fetch_capability'() {
                    this.enforceQuickDependencyRules();
                },
            },
        });

        window.addModal = function () { window.app.addModal(); };
        window.editModal = function (id) { window.app.editModal(id); };
        window.openAutoSourcesModal = function (marketId, marketName) { window.app.openAutoSourcesModal(marketId, marketName); };
    </script>
@endpush
