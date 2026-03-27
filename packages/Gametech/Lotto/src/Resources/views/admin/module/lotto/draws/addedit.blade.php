<b-modal ref="addedit" id="addedit" centered size="md" :title="modalTitle" :no-stacking="true"
         :no-close-on-backdrop="true"
         @shown="onModalShown"
         @hidden="onModalHidden"
         :hide-footer="true">
    <b-form v-if="show" @submit.prevent="formmethod === 'settle' ? submitSettleForm() : submitDrawForm()">
        <template v-if="formmethod !== 'settle'">
            <b-row>
                <b-col cols="12" md="6">
                    <b-form-group label="ตลาด">
                        <select ref="marketSelect" class="form-control form-control-sm" required>
                            <option value="">-- เลือกตลาด --</option>
                            @foreach(($marketOptions ?? []) as $group)
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
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="6">
                    <b-form-group label="วันงวด:" label-for="draw_date">
                        <b-form-input id="draw_date" v-model="formaddedit.draw_date" type="date" size="sm" required></b-form-input>
                    </b-form-group>
                </b-col>
            </b-row>
            <b-row>
                <b-col cols="12" md="6">
                    <b-form-group label="เปิดรับ:" label-for="open_at">
                        <b-form-input id="open_at" v-model="formaddedit.open_at" type="datetime-local" size="sm" required></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="6">
                    <b-form-group label="ปิดรับ:" label-for="close_at">
                        <b-form-input id="close_at" v-model="formaddedit.close_at" type="datetime-local" size="sm" required></b-form-input>
                    </b-form-group>
                </b-col>
            </b-row>
            <b-form-group label="เวลาออกผล (คาดการณ์):" label-for="result_at">
                <b-form-input id="result_at" v-model="formaddedit.result_at" type="datetime-local" size="sm"></b-form-input>
            </b-form-group>

            <div class="d-flex justify-content-end">
                <b-button type="submit" variant="primary" size="sm">บันทึก</b-button>
            </div>
        </template>

        <template v-else>
            <div class="mb-2 text-muted">
                <div>ตลาด: <strong>@{{ currentDraw.market_name || '-' }}</strong></div>
                <div>วันงวด: <strong>@{{ currentDraw.draw_date || '-' }}</strong></div>
                <div>สถานะ: <strong>@{{ currentDraw.status_label || '-' }}</strong></div>
            </div>

            <b-row>
                <b-col cols="12" md="8">
                    <b-form-group label="รางวัลที่ 1 (5-6 หลัก)" label-for="result_first_prize">
                        <b-form-input id="result_first_prize" v-model="formaddedit.result_number.first_prize" type="text" maxlength="6" size="sm" placeholder="เช่น 12345 หรือ 123456"></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col cols="12" md="4">
                    <b-form-group label="เลขท้าย 2 ตัว" label-for="result_last_2_digits">
                        <b-form-input id="result_last_2_digits" v-model="formaddedit.result_number.last_2_digits" type="text" maxlength="2" size="sm" placeholder="เช่น 89"></b-form-input>
                    </b-form-group>
                </b-col>
            </b-row>

            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted" v-if="!canCalculate">กรอกรางวัลที่ 1 และเลขท้าย 2 ตัวให้ครบก่อน จึงจะแสดงปุ่มคำนวณ</small>
                <b-button v-if="canCalculate" type="submit" variant="success" size="sm">
                    คำนวณรางวัล
                </b-button>
            </div>
        </template>
    </b-form>
</b-modal>

<b-modal ref="blockedNumbersModal" id="blockedNumbersModal" centered size="xl" title="รายการเลขอั้นในงวด" ok-only ok-title="ปิด" modal-class="lotto-blocked-summary-modal">
    <div class="row no-gutters mb-2 lotto-summary-row">
        <div class="col-4 lotto-blocked-summary-item"><span>งวด :</span><strong>@{{ blockedNumbersData.draw.draw_date || '-' }}</strong></div>
        <div class="col-4 lotto-blocked-summary-item"><span>ตลาด :</span><strong>@{{ blockedNumbersData.draw.market_name || '-' }}</strong></div>
        <div class="col-4 lotto-blocked-summary-item"><span>จำนวนเลขอั้น :</span><strong class="lotto-summary-value-primary">@{{ blockedNumbersData.count || 0 }}</strong></div>
    </div>
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
        <div class="text-muted small mb-1">แสดง @{{ filteredBlockedNumbersItems.length }} / @{{ blockedNumbersData.count || 0 }} รายการ</div>
        <div class="lotto-blocked-search mb-1">
            <b-input-group size="sm">
                <b-form-input
                    id="blocked-number-search"
                    v-model.trim="blockedSearchKeyword"
                    placeholder="ค้นหาเลข / ประเภท / หมายเหตุ"></b-form-input>
            </b-input-group>
        </div>
    </div>
    <div class="table-responsive member-list-scroll">
        <b-table
            class="mb-0 member-list-table lotto-blocked-summary-table"
            striped
            hover
            small
            outlined
            show-empty
            head-variant="light"
            :items="filteredBlockedNumbersItems"
            :fields="blockedNumbersFields"
            empty-text="ไม่พบรายการเลขอั้นในงวดนี้">
            <template #cell(index)="row">
                <div class="text-center">@{{ row.index + 1 }}</div>
            </template>
            <template #cell(bet_type_label)="row">
                @{{ row.item.bet_type_label || row.item.bet_type || '-' }}
            </template>
            <template #cell(number)="row">
                <div class="text-center">@{{ row.item.number || '-' }}</div>
            </template>
            <template #cell(mode)="row">
                <div class="text-center">@{{ row.item.mode || '-' }}</div>
            </template>
            <template #cell(blocked_at)="row">
                <div class="text-center">@{{ row.item.blocked_at || '-' }}</div>
            </template>
            <template #cell(reason)="row">
                @{{ row.item.reason || '-' }}
            </template>
        </b-table>
    </div>
</b-modal>

<b-modal ref="ticketsSummaryModal" id="ticketsSummaryModal" centered size="xl" title="รายการแทงในงวด" ok-only ok-title="ปิด" modal-class="lotto-ticket-summary-modal">
    <div class="row no-gutters mb-2 lotto-summary-row">
        <div class="col-4 lotto-ticket-summary-item"><span>งวด :</span><strong>@{{ ticketsSummaryData.draw.draw_date || '-' }}</strong></div>
        <div class="col-4 lotto-ticket-summary-item"><span>ตลาด :</span><strong>@{{ ticketsSummaryData.draw.market_name || '-' }}</strong></div>
        <div class="col-4 lotto-ticket-summary-item"><span>จำนวนรายการแทง :</span><strong class="lotto-summary-value-primary">@{{ ticketsSummaryData.count || 0 }}</strong></div>
    </div>
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
        <div class="text-muted small mb-1">แสดง @{{ filteredTicketsSummaryItems.length }} / @{{ ticketsSummaryData.count || 0 }} รายการ</div>
        <div class="lotto-ticket-search mb-1">
            <b-input-group size="sm">
                <b-form-input
                    id="ticket-member-search"
                    v-model.trim="ticketsSearchKeyword"
                    placeholder="ค้นหา username หรือชื่อสมาชิก"></b-form-input>
            </b-input-group>
        </div>
    </div>

    <div class="table-responsive member-list-scroll">
        <b-table
            class="mb-0 member-list-table lotto-ticket-summary-table"
            striped
            hover
            small
            outlined
            show-empty
            head-variant="light"
            :items="filteredTicketsSummaryItems"
            :fields="ticketsSummaryFields"
            empty-text="ไม่พบรายการแทงในงวดนี้">
            <template #cell(id)="row">
                <div class="text-center">@{{ row.item.id || '-' }}</div>
            </template>
            <template #cell(member_username)="row">
                @{{ row.item.member_username || '-' }}
            </template>
            <template #cell(member_name)="row">
                @{{ row.item.member_name || '-' }}
            </template>
            <template #cell(bet_types)="row">
                @{{ row.item.bet_types || '-' }}
            </template>
            <template #cell(bet_numbers)="row">
                @{{ row.item.bet_numbers || '-' }}
            </template>
            <template #cell(total_amount)="row">
                <div class="text-right">@{{ formatMoney(row.item.total_amount) }}</div>
            </template>
            <template #cell(status)="row">
                <div class="text-center">@{{ row.item.status || '-' }}</div>
            </template>
            <template #cell(created_at)="row">
                <div class="text-center">@{{ row.item.created_at || '-' }}</div>
            </template>
        </b-table>
    </div>
</b-modal>

<b-modal ref="autoResultLogsModal" id="autoResultLogsModal" centered size="xl" title="Auto Result Fetch Logs" ok-only ok-title="ปิด" modal-class="lotto-auto-result-log-modal">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="text-muted small">แสดง @{{ autoResultLogsData.items.length }} รายการล่าสุด</div>
        <div class="text-muted small">Draw ID: @{{ autoResultLogsData.draw_id || '-' }}</div>
    </div>
    <div class="table-responsive member-list-scroll">
        <b-table
            class="mb-0 member-list-table lotto-ticket-summary-table"
            striped
            hover
            small
            outlined
            show-empty
            head-variant="light"
            :items="autoResultLogsData.items"
            :fields="autoResultLogFields"
            empty-text="ไม่พบ fetch logs">
            <template #cell(id)="row"><div class="text-center">@{{ row.item.id }}</div></template>
            <template #cell(attempt_no)="row"><div class="text-center">@{{ row.item.attempt_no }}</div></template>
            <template #cell(status)="row"><div class="text-center">@{{ row.item.status }}</div></template>
            <template #cell(pipeline_stage)="row"><div class="text-center">@{{ row.item.pipeline_stage || '-' }}</div></template>
            <template #cell(response_http_status)="row"><div class="text-center">@{{ row.item.response_http_status || '-' }}</div></template>
            <template #cell(duration_ms)="row"><div class="text-center">@{{ row.item.duration_ms || '-' }}</div></template>
            <template #cell(created_at)="row"><div class="text-center">@{{ row.item.created_at || '-' }}</div></template>
            <template #cell(error_message)="row">@{{ row.item.error_message || '-' }}</template>
            <template #cell(preview)="row"><small>@{{ row.item.preview || '-' }}</small></template>
            <template #cell(run_id)="row"><small>@{{ row.item.run_id || '-' }}</small></template>
            <template #cell(action)="row">
                <div class="text-center">
                    <button type="button" class="btn btn-info btn-xs" @click="openAutoResultLogDetail(row.item)">ดูรายละเอียด</button>
                </div>
            </template>
        </b-table>
    </div>
</b-modal>

<b-modal ref="autoResultLogDetailModal" id="autoResultLogDetailModal" centered size="xl" title="รายละเอียด Fetch Log" ok-only ok-title="ปิด">
    <div class="mb-2 small text-muted">
        Log ID: @{{ autoResultLogDetail.id || '-' }} | Run ID: @{{ autoResultLogDetail.run_id || '-' }}
    </div>
    <b-row>
        <b-col md="6">
            <b-form-group label="Request URL">
                <b-form-textarea rows="3" no-resize readonly :value="autoResultLogDetail.request_url || '-'"></b-form-textarea>
            </b-form-group>
        </b-col>
        <b-col md="6">
            <b-form-group label="Error Message">
                <b-form-textarea rows="3" no-resize readonly :value="autoResultLogDetail.error_message || '-'"></b-form-textarea>
            </b-form-group>
        </b-col>
    </b-row>
    <b-row>
        <b-col md="6">
            <b-form-group label="Request Meta JSON">
                <b-form-textarea rows="8" no-resize readonly :value="autoResultLogDetail.request_meta_json || '-'"></b-form-textarea>
            </b-form-group>
        </b-col>
        <b-col md="6">
            <b-form-group label="Parsed Payload JSON">
                <b-form-textarea rows="8" no-resize readonly :value="autoResultLogDetail.parsed_payload_json || '-'"></b-form-textarea>
            </b-form-group>
        </b-col>
    </b-row>
    <b-row>
        <b-col md="6">
            <b-form-group label="Normalized Result JSON">
                <b-form-textarea rows="6" no-resize readonly :value="autoResultLogDetail.normalized_result_json || '-'"></b-form-textarea>
            </b-form-group>
        </b-col>
        <b-col md="6">
            <b-form-group label="Response Body (preview)">
                <b-form-textarea rows="6" no-resize readonly :value="autoResultLogDetail.response_body_preview || '-'"></b-form-textarea>
            </b-form-group>
        </b-col>
    </b-row>
</b-modal>

<b-modal ref="autoGenSummaryModal" id="autoGenSummaryModal" centered size="xl" :title="autoGenModalTitle" ok-only ok-title="ปิด" modal-class="lotto-autogen-summary-modal">
    <div class="row no-gutters mb-2 lotto-summary-row">
        <div class="col-3 lotto-ticket-summary-item"><span>ตลาดเข้าเกณฑ์ :</span><strong>@{{ autoGenSummary.market_count || 0 }}</strong></div>
        <div class="col-3 lotto-ticket-summary-item"><span>จะสร้าง :</span><strong class="lotto-summary-value-primary">@{{ autoGenCreateItems.length }}</strong></div>
        <div class="col-3 lotto-ticket-summary-item"><span>ขาด :</span><strong class="text-danger">@{{ autoGenMissingItems.length }}</strong></div>
        <div class="col-3 lotto-ticket-summary-item"><span>ไม่เข้าเกณฑ์ :</span><strong>@{{ autoGenNotInCriteriaItems.length }}</strong></div>
    </div>

    <b-tabs content-class="pt-2" small>
        <b-tab :title="`จะสร้าง (${autoGenCreateItems.length})`" active>
            <div class="table-responsive member-list-scroll">
                <b-table
                    class="mb-0 member-list-table lotto-ticket-summary-table"
                    striped
                    hover
                    small
                    outlined
                    show-empty
                    head-variant="light"
                    :items="autoGenCreateItems"
                    :fields="autoGenSummaryFields"
                    empty-text="ไม่มีรายการที่จะสร้าง">
                    <template #cell(index)="row">
                        <div class="text-center">@{{ row.index + 1 }}</div>
                    </template>
                    <template #cell(market_id)="row">
                        <div class="text-center">@{{ row.item.market_id || '-' }}</div>
                    </template>
                    <template #cell(market_name)="row">
                        @{{ row.item.market_name || '-' }}
                    </template>
                    <template #cell(draw_date)="row">
                        <div class="text-center">@{{ row.item.draw_date || '-' }}</div>
                    </template>
                    <template #cell(draw_mode_label)="row">
                        <div class="text-center">@{{ row.item.draw_mode_label || '-' }}</div>
                    </template>
                    <template #cell(open_at)="row">
                        <div class="text-center">@{{ row.item.open_at || '-' }}</div>
                    </template>
                    <template #cell(close_at)="row">
                        <div class="text-center">@{{ row.item.close_at || '-' }}</div>
                    </template>
                    <template #cell(result_at)="row">
                        <div class="text-center">@{{ row.item.result_at || '-' }}</div>
                    </template>
                    <template #cell(status_label)="row">
                        <div class="text-center">@{{ row.item.status_label || '-' }}</div>
                    </template>
                </b-table>
            </div>
        </b-tab>

        <b-tab :title="`ขาด (${autoGenMissingItems.length})`">
            <div class="table-responsive member-list-scroll">
                <b-table
                    class="mb-0 member-list-table lotto-ticket-summary-table"
                    striped
                    hover
                    small
                    outlined
                    show-empty
                    head-variant="light"
                    :items="autoGenMissingItems"
                    :fields="autoGenSummaryFields"
                    empty-text="ไม่มีรายการขาด">
                    <template #cell(index)="row">
                        <div class="text-center">@{{ row.index + 1 }}</div>
                    </template>
                    <template #cell(market_id)="row">
                        <div class="text-center">@{{ row.item.market_id || '-' }}</div>
                    </template>
                    <template #cell(market_name)="row">
                        @{{ row.item.market_name || '-' }}
                    </template>
                    <template #cell(draw_date)="row">
                        <div class="text-center">@{{ row.item.draw_date || '-' }}</div>
                    </template>
                    <template #cell(draw_mode_label)="row">
                        <div class="text-center">@{{ row.item.draw_mode_label || '-' }}</div>
                    </template>
                    <template #cell(open_at)="row">
                        <div class="text-center">@{{ row.item.open_at || '-' }}</div>
                    </template>
                    <template #cell(close_at)="row">
                        <div class="text-center">@{{ row.item.close_at || '-' }}</div>
                    </template>
                    <template #cell(result_at)="row">
                        <div class="text-center">@{{ row.item.result_at || '-' }}</div>
                    </template>
                    <template #cell(status_label)="row">
                        <div class="text-center">@{{ row.item.status_label || '-' }}</div>
                    </template>
                </b-table>
            </div>
        </b-tab>

        <b-tab :title="`ไม่เข้าเกณฑ์ (${autoGenNotInCriteriaItems.length})`">
            <div class="table-responsive member-list-scroll">
                <b-table
                    class="mb-0 member-list-table lotto-ticket-summary-table"
                    striped
                    hover
                    small
                    outlined
                    show-empty
                    head-variant="light"
                    :items="autoGenNotInCriteriaItems"
                    :fields="autoGenSummaryFields"
                    empty-text="ไม่มีรายการที่ไม่เข้าเกณฑ์">
                    <template #cell(index)="row">
                        <div class="text-center">@{{ row.index + 1 }}</div>
                    </template>
                    <template #cell(market_id)="row">
                        <div class="text-center">@{{ row.item.market_id || '-' }}</div>
                    </template>
                    <template #cell(market_name)="row">
                        @{{ row.item.market_name || '-' }}
                    </template>
                    <template #cell(draw_date)="row">
                        <div class="text-center">@{{ row.item.draw_date || '-' }}</div>
                    </template>
                    <template #cell(draw_mode_label)="row">
                        <div class="text-center">@{{ row.item.draw_mode_label || '-' }}</div>
                    </template>
                    <template #cell(open_at)="row">
                        <div class="text-center">@{{ row.item.open_at || '-' }}</div>
                    </template>
                    <template #cell(close_at)="row">
                        <div class="text-center">@{{ row.item.close_at || '-' }}</div>
                    </template>
                    <template #cell(result_at)="row">
                        <div class="text-center">@{{ row.item.result_at || '-' }}</div>
                    </template>
                    <template #cell(status_label)="row">
                        <div class="text-center">@{{ row.item.status_label || '-' }}</div>
                    </template>
                </b-table>
            </div>
        </b-tab>
    </b-tabs>
</b-modal>

@push('css')
    <style>
        .member-list-table th,
        .member-list-table td {
            font-size: 12px;
            white-space: nowrap;
        }
        .member-list-scroll {
            flex: 1 1 auto;
            min-height: 0;
            max-height: 360px;
            overflow: auto;
            border: 1px solid #e9ecef;
            border-radius: 4px;
        }
        .lotto-blocked-summary-modal .modal-dialog {
            max-width: 1120px;
        }
        .lotto-blocked-summary-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 14px;
            white-space: nowrap;
            min-height: 32px;
        }
        .lotto-blocked-summary-item span {
            color: #334155;
            font-weight: 700;
        }
        .lotto-blocked-summary-item strong {
            color: #0f172a;
            font-size: 16px;
        }
        .lotto-blocked-search {
            min-width: 320px;
            max-width: 420px;
            width: 100%;
        }
        .lotto-blocked-summary-table .table {
            margin-bottom: 0;
        }
        .lotto-blocked-summary-table thead th {
            font-weight: 700;
            background: #eef1f5;
            border-top: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
            color: #374151;
        }
        .lotto-blocked-summary-table tbody td {
            vertical-align: middle;
        }
        .lotto-ticket-summary-modal .modal-dialog {
            max-width: 1220px;
        }
        .lotto-ticket-summary-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 14px;
            white-space: nowrap;
            min-height: 32px;
        }
        .lotto-ticket-summary-item span {
            color: #334155;
            font-weight: 700;
        }
        .lotto-ticket-summary-item strong {
            color: #0f172a;
            font-size: 16px;
        }
        .lotto-summary-row {
            border: 1px solid #dbe3ef;
            border-radius: 4px;
            background: linear-gradient(90deg, #f8fbff 0%, #f3f8ff 100%);
            padding: 4px 0;
        }
        .lotto-summary-value-primary {
            color: #0d6efd !important;
            font-weight: 800;
        }
        .lotto-ticket-search {
            min-width: 320px;
            max-width: 420px;
            width: 100%;
        }
        .lotto-ticket-summary-table .table {
            margin-bottom: 0;
        }
        .lotto-ticket-summary-table thead th {
            font-weight: 700;
            background: #eef1f5;
            border-top: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
            color: #374151;
        }
        .lotto-ticket-summary-table tbody td {
            vertical-align: middle;
        }
        .lotto-autogen-summary-modal .modal-dialog {
            max-width: 1220px;
        }

        #addedit .select2-container--default .select2-selection--single {
            height: calc(1.5em + .5rem + 2px);
            min-height: calc(1.5em + .5rem + 2px);
            padding: 0;
            display: flex;
            align-items: center;
        }

        #addedit .select2-container--default .select2-selection--single .select2-selection__rendered {
            width: 100%;
            padding-left: .5rem;
            padding-right: 1.75rem;
            line-height: normal;
            display: flex !important;
            align-items: center;
            min-height: calc(1.5em + .5rem + 2px);
            overflow: visible;
        }

        #addedit .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
            right: .35rem;
        }

        .select2-container .lotto-market-option {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        #addedit .select2-container .lotto-market-option__logo,
        .select2-container .lotto-market-option__logo {
            width: 20px !important;
            height: 20px !important;
            min-width: 20px !important;
            max-width: 20px !important;
            min-height: 20px !important;
            max-height: 20px !important;
            flex: 0 0 20px !important;
            display: block !important;
            object-fit: cover !important;
            border-radius: 50% !important;
            border: 1px solid #e5e7eb !important;
            background: #fff !important;
        }

        .select2-container .lotto-market-option__text {
            display: block;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>

    <script type="module">
        const toDateTimeLocal = (value) => {
            if (!value) return '';
            return String(value).replace(' ', 'T').substring(0, 16);
        };

        const onlyDigits = (value) => String(value || '').replace(/\D+/g, '');

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: true,
                    code: null,
                    formmethod: 'add',
                    markets: @json($marketOptions ?? []),
                    formaddedit: {
                        market_id: null,
                        draw_date: '',
                        open_at: '',
                        close_at: '',
                        result_number: {
                            first_prize: '',
                            last_2_digits: '',
                        },
                        result_at: '',
                    },
                    currentDraw: {
                        market_name: '',
                        draw_date: '',
                        status_label: '',
                    },
                    blockedNumbersData: {
                        draw: {},
                        count: 0,
                        items: [],
                    },
                    blockedSearchKeyword: '',
                    blockedNumbersFields: [
                        { key: 'index', label: '#', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '60px' } },
                        { key: 'bet_type_label', label: 'ประเภท' },
                        { key: 'number', label: 'เลข', thClass: 'text-center', tdClass: 'text-center' },
                        { key: 'mode', label: 'โหมด', thClass: 'text-center', tdClass: 'text-center' },
                        { key: 'blocked_at', label: 'เวลาอั้น', thClass: 'text-center', tdClass: 'text-center' },
                        { key: 'reason', label: 'หมายเหตุ' },
                    ],
                    ticketsSummaryData: {
                        draw: {},
                        count: 0,
                        items: [],
                    },
                    ticketsSearchKeyword: '',
                    ticketsSummaryFields: [
                        { key: 'id', label: 'โพย #', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '80px' } },
                        { key: 'member_username', label: 'user' },
                        { key: 'member_name', label: 'ชื่อสมาชิก' },
                        { key: 'bet_types', label: 'ประเภท' },
                        { key: 'bet_numbers', label: 'เลขที่แทง' },
                        { key: 'total_amount', label: 'ยอดแทง', thClass: 'text-right', tdClass: 'text-right' },
                        { key: 'status', label: 'สถานะ', thClass: 'text-center', tdClass: 'text-center' },
                        { key: 'created_at', label: 'เวลาแทง', thClass: 'text-center', tdClass: 'text-center' },
                    ],
                    autoGenModalTitle: 'ผล Dry-run',
                    autoGenSummary: {
                        market_count: 0,
                        items: [],
                    },
                    autoGenSummaryFields: [
                        { key: 'index', label: '#', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '60px' } },
                        { key: 'market_id', label: 'Market ID', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '120px' } },
                        { key: 'market_name', label: 'รายการหวย' },
                        { key: 'draw_date', label: 'งวดหวย', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '140px' } },
                        { key: 'draw_mode_label', label: 'โหมดงวด', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '120px' } },
                        { key: 'open_at', label: 'เปิดรับ', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '170px' } },
                        { key: 'close_at', label: 'ปิดรับ', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '170px' } },
                        { key: 'result_at', label: 'ออกผล', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '170px' } },
                        { key: 'status_label', label: 'สถานะ', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '180px' } },
                    ],
                    autoResultLogsData: {
                        draw_id: null,
                        items: [],
                    },
                    autoResultLogDetail: {},
                    autoResultLogFields: [
                        { key: 'id', label: '#', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '70px' } },
                        { key: 'attempt_no', label: 'Attempt', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '85px' } },
                        { key: 'status', label: 'Status', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '130px' } },
                        { key: 'pipeline_stage', label: 'Stage', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '110px' } },
                        { key: 'response_http_status', label: 'HTTP', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '80px' } },
                        { key: 'duration_ms', label: 'ms', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '80px' } },
                        { key: 'preview', label: 'Parsed/Normalized Preview' },
                        { key: 'error_message', label: 'Error' },
                        { key: 'run_id', label: 'Run ID' },
                        { key: 'created_at', label: 'เวลา', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '150px' } },
                        { key: 'action', label: 'จัดการ', thClass: 'text-center', tdClass: 'text-center', thStyle: { width: '95px' } },
                    ],
                    isSyncingMarketSelect: false,
                };
            },
            computed: {
                modalTitle() {
                    if (this.formmethod === 'settle') {
                        return 'ประกาศผล / คำนวณรางวัล';
                    }

                    return 'งวดหวย';
                },
                canCalculate() {
                    if (this.formmethod !== 'settle') {
                        return false;
                    }

                    const firstPrizeLen = onlyDigits(this.formaddedit.result_number.first_prize).length;

                    return (firstPrizeLen === 5 || firstPrizeLen === 6)
                        && onlyDigits(this.formaddedit.result_number.last_2_digits).length === 2;
                },
                firstMarketOption() {
                    for (const group of this.markets) {
                        if (Array.isArray(group.options) && group.options.length > 0) {
                            return group.options[0];
                        }
                    }

                    return null;
                },
                filteredTicketsSummaryItems() {
                    const rows = Array.isArray(this.ticketsSummaryData.items) ? this.ticketsSummaryData.items : [];
                    const keyword = String(this.ticketsSearchKeyword || '').trim().toLowerCase();
                    if (!keyword) {
                        return rows;
                    }

                    return rows.filter((item) => {
                        const username = String(item.member_username || '').toLowerCase();
                        const fullName = String(item.member_name || '').toLowerCase();
                        const memberId = String(item.member_id || '').toLowerCase();
                        const betTypes = String(item.bet_types || '').toLowerCase();
                        const betNumbers = String(item.bet_numbers || '').toLowerCase();
                        return username.includes(keyword)
                            || fullName.includes(keyword)
                            || memberId.includes(keyword)
                            || betTypes.includes(keyword)
                            || betNumbers.includes(keyword);
                    });
                },
                filteredBlockedNumbersItems() {
                    const rows = Array.isArray(this.blockedNumbersData.items) ? this.blockedNumbersData.items : [];
                    const keyword = String(this.blockedSearchKeyword || '').trim().toLowerCase();
                    if (!keyword) {
                        return rows;
                    }

                    return rows.filter((item) => {
                        const number = String(item.number || '').toLowerCase();
                        const betType = String(item.bet_type_label || item.bet_type || '').toLowerCase();
                        const reason = String(item.reason || '').toLowerCase();
                        return number.includes(keyword) || betType.includes(keyword) || reason.includes(keyword);
                    });
                },
                autoGenNormalizedItems() {
                    const rows = Array.isArray(this.autoGenSummary.items) ? this.autoGenSummary.items : [];
                    return rows.map((item) => {
                        const status = String(item.status || '');
                        return {
                            market_id: item.market_id || null,
                            market_name: item.market_name || '-',
                            draw_date: item.draw_date || '-',
                            draw_mode_label: this.autoGenDrawModeLabel(String(item.draw_mode || '')),
                            open_at: item.open_at || '-',
                            close_at: item.close_at || '-',
                            result_at: item.result_at || '-',
                            status,
                            status_label: this.autoGenStatusLabel(status),
                        };
                    });
                },
                autoGenCreateItems() {
                    return this.autoGenNormalizedItems.filter((item) => item.status === 'will_create' || item.status === 'created');
                },
                autoGenMissingItems() {
                    return this.autoGenNormalizedItems.filter((item) =>
                        item.status === 'skip_group_disabled'
                        || item.status === 'skip_missing_close_time'
                        || item.status === 'unknown'
                    );
                },
                autoGenNotInCriteriaItems() {
                    return this.autoGenNormalizedItems.filter((item) => item.status === 'skip_not_in_schedule');
                },
            },
            watch: {
                'formaddedit.market_id'() {
                    if (this.isSyncingMarketSelect) {
                        return;
                    }

                    this.$nextTick(() => this.syncMarketSelectValue());
                },
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            methods: {
                resetForm() {
                    const firstMarketId = this.firstMarketOption ? parseInt(this.firstMarketOption.value, 10) : null;

                    this.formaddedit = {
                        market_id: Number.isNaN(firstMarketId) ? null : firstMarketId,
                        draw_date: '',
                        open_at: '',
                        close_at: '',
                        result_number: {
                            first_prize: '',
                            last_2_digits: '',
                        },
                        result_at: '',
                    };
                    this.currentDraw = {
                        market_name: '',
                        draw_date: '',
                        status_label: '',
                    };

                    this.$nextTick(() => this.syncMarketSelectValue());
                },
                editModal(id) {
                    this.code = id;
                    this.formmethod = 'edit';
                    this.show = false;

                    this.$nextTick(async () => {
                        this.show = true;
                        await this.loadData();
                        this.$refs.addedit.show();
                    });
                },
                addModal() {
                    this.code = null;
                    this.formmethod = 'add';
                    this.resetForm();
                    this.show = false;

                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();
                    });
                },
                settleModal(id) {
                    this.code = id;
                    this.formmethod = 'settle';
                    this.show = false;

                    this.$nextTick(async () => {
                        this.show = true;
                        await this.loadData();
                        this.$refs.addedit.show();
                    });
                },
                onModalShown() {
                    this.initMarketSelect2();
                },
                onModalHidden() {
                    this.destroyMarketSelect2();
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

                    const modalId = this.$refs.addedit && this.$refs.addedit.id
                        ? String(this.$refs.addedit.id)
                        : 'addedit';

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

                    $select.on('change.drawMarket', () => {
                        const value = $select.val();
                        const normalizedValue = value ? parseInt(value, 10) : null;

                        if (this.formaddedit.market_id === normalizedValue) {
                            return;
                        }

                        this.isSyncingMarketSelect = true;
                        this.formaddedit.market_id = normalizedValue;

                        this.$nextTick(() => {
                            this.isSyncingMarketSelect = false;
                        });
                    });

                    this.syncMarketSelectValue();
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

                    $select.off('.drawMarket');

                    if ($select.hasClass('select2-hidden-accessible') && typeof $select.select2 === 'function') {
                        $select.select2('destroy');
                    }
                },
                syncMarketSelectValue() {
                    const selectEl = this.$refs.marketSelect;
                    if (!selectEl || !window.jQuery) {
                        return;
                    }

                    const value = this.formaddedit.market_id ? String(this.formaddedit.market_id) : '';
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
                statusLabel(status) {
                    const map = {
                        draft: 'ร่าง',
                        open: 'เปิดรับ',
                        closed: 'ปิดรับ',
                        resulted: 'ประกาศผลแล้ว',
                    };

                    return map[status] || status;
                },
                async loadData() {
                    const response = await axios.post("{{ route('admin.lotto.draws.loaddata') }}", { id: this.code });
                    const d = response?.data?.data || {};

                    this.currentDraw = {
                        market_name: d.market?.name || '-',
                        draw_date: d.draw_date ? String(d.draw_date).substring(0, 10) : '-',
                        status_label: this.statusLabel(d.status || '-'),
                    };

                    this.formaddedit = {
                        market_id: d.market_id ? parseInt(d.market_id, 10) : null,
                        draw_date: d.draw_date ? String(d.draw_date).substring(0, 10) : '',
                        open_at: toDateTimeLocal(d.open_at),
                        close_at: toDateTimeLocal(d.close_at),
                        result_number: {
                            first_prize: d.result_number?.first_prize || '',
                            last_2_digits: d.result_number?.last_2_digits || d.result_number?.bottom_2 || '',
                        },
                        result_at: toDateTimeLocal(d.result_at),
                    };

                    this.$nextTick(() => this.syncMarketSelectValue());
                },
                validateDrawWindow() {
                    if (!this.formaddedit.open_at || !this.formaddedit.close_at) {
                        return 'กรุณาระบุเวลาเปิดรับและปิดรับให้ครบ';
                    }

                    if (this.formaddedit.open_at >= this.formaddedit.close_at) {
                        return 'เวลาเปิดรับต้องน้อยกว่าเวลาปิดรับ';
                    }

                    return '';
                },
                async openBlockedNumbersModal(drawId) {
                    const response = await axios.post("{{ route('admin.lotto.draws.blocked_numbers') }}", { id: drawId });
                    this.blockedNumbersData = response?.data?.data || { draw: {}, count: 0, items: [] };
                    this.blockedSearchKeyword = '';
                    this.$refs.blockedNumbersModal.show();
                },
                async openTicketsSummaryModal(drawId) {
                    const response = await axios.post("{{ route('admin.lotto.draws.tickets_summary') }}", { id: drawId });
                    this.ticketsSummaryData = response?.data?.data || { draw: {}, count: 0, items: [] };
                    this.ticketsSearchKeyword = '';
                    this.$refs.ticketsSummaryModal.show();
                },
                formatMoney(value) {
                    return Number(value || 0).toLocaleString('th-TH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },
                autoGenStatusLabel(status) {
                    const map = {
                        created: 'สร้างแล้ว',
                        will_create: 'จะสร้าง',
                        exists: 'มีอยู่แล้ว',
                        skip_not_in_schedule: 'ไม่เข้าเกณฑ์วันนั้น',
                        skip_group_disabled: 'ขาด: กลุ่มปิด',
                        skip_missing_close_time: 'ขาด: ไม่มีเวลาปิด',
                        unknown: 'ขาด: ไม่ทราบสาเหตุ',
                    };

                    return map[status] || `ขาด: ไม่ทราบ (${status || '-'})`;
                },
                autoGenDrawModeLabel(mode) {
                    const map = {
                        daily: 'ทุกวัน',
                        weekdays: 'จ.-ศ.',
                        manual: 'แมนนวล',
                    };

                    return map[mode] || mode || '-';
                },
                prepareAutoGenSummary(summary, dryRun) {
                    const knownStatuses = [
                        'created',
                        'will_create',
                        'exists',
                        'skip_not_in_schedule',
                        'skip_group_disabled',
                        'skip_missing_close_time',
                    ];

                    const items = Array.isArray(summary?.items) ? summary.items : [];
                    const normalizedItems = items.map((item) => {
                        const status = String(item?.status || '');
                        return {
                            ...item,
                            status: knownStatuses.includes(status) ? status : 'unknown',
                        };
                    });

                    this.autoGenSummary = {
                        market_count: Number(summary?.market_count || 0),
                        created: Number(summary?.created || 0),
                        exists: Number(summary?.exists || 0),
                        skipped: Number(summary?.skipped || 0),
                        not_in_schedule: Number(summary?.not_in_schedule || 0),
                        items: normalizedItems,
                    };
                    this.autoGenModalTitle = dryRun ? 'ผล Dry-run Auto งวด' : 'ผล Generate Auto งวด';
                },
                async submitDrawForm() {
                    const validationMessage = this.validateDrawWindow();
                    if (validationMessage) {
                        await this.$bvModal.msgBoxOk(validationMessage, {
                            title: 'ข้อมูลไม่ถูกต้อง',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true,
                        });
                        return;
                    }

                    const payload = {
                        market_id: this.formaddedit.market_id,
                        draw_date: this.formaddedit.draw_date,
                        open_at: this.formaddedit.open_at ? this.formaddedit.open_at.replace('T', ' ') : null,
                        close_at: this.formaddedit.close_at ? this.formaddedit.close_at.replace('T', ' ') : null,
                        result_at: this.formaddedit.result_at ? this.formaddedit.result_at.replace('T', ' ') : null,
                    };

                    const url = this.formmethod === 'add'
                        ? "{{ route('admin.lotto.draws.create') }}"
                        : "{{ route('admin.lotto.draws.update') }}";

                    const response = await this.$http.post(url, { id: this.code, data: payload });

                    await this.$bvModal.msgBoxOk(response.data.message, {
                        title: 'ผลการดำเนินการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'success',
                        centered: true,
                    });

                    this.$refs.addedit.hide();
                    window.LaravelDataTables['lottoDrawsTable'].draw(false);
                },
                async submitSettleForm() {
                    if (!this.canCalculate) {
                        return;
                    }

                    const payload = {
                        result_number: {
                            first_prize: onlyDigits(this.formaddedit.result_number.first_prize),
                            last_2_digits: onlyDigits(this.formaddedit.result_number.last_2_digits),
                        },
                    };

                    const response = await this.$http.post("{{ route('admin.lotto.draws.settle') }}", {
                        id: this.code,
                        data: payload,
                    });

                    const summary = response?.data?.data || {};
                    const message = [
                        response.data.message || 'คำนวณรางวัลเรียบร้อยแล้ว',
                        `จำนวนโพย: ${summary.ticket_count || 0}`,
                        `โพยที่ถูกรางวัล: ${summary.winning_ticket_count || 0}`,
                        `ยอดจ่ายรวม: ${summary.total_win_amount || 0}`,
                    ].join('\n');

                    await this.$bvModal.msgBoxOk(message, {
                        title: 'ผลการคำนวณ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'success',
                        centered: true,
                    });

                    this.$refs.addedit.hide();
                    window.LaravelDataTables['lottoDrawsTable'].draw(false);
                },
                openDraw(id) {
                    this.$http.post("{{ route('admin.lotto.draws.open') }}", { id })
                        .then(response => {
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                centered: true,
                            });
                            window.LaravelDataTables['lottoDrawsTable'].draw(false);
                        });
                },
                closeDraw(id) {
                    this.$http.post("{{ route('admin.lotto.draws.close') }}", { id })
                        .then(response => {
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                centered: true,
                            });
                            window.LaravelDataTables['lottoDrawsTable'].draw(false);
                        });
                },
                async generateAutoDraws(dryRun = false) {
                    const confirmed = await this.$bvModal.msgBoxConfirm(
                        dryRun ? 'ต้องการตรวจสอบรายการงวดที่จะสร้างอัตโนมัติหรือไม่?' : 'ต้องการสร้างงวดอัตโนมัติเลยหรือไม่?',
                        {
                            title: 'ยืนยันการทำงาน',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: dryRun ? 'info' : 'success',
                            okTitle: 'ยืนยัน',
                            cancelTitle: 'ยกเลิก',
                            centered: true,
                        }
                    );

                    if (!confirmed) {
                        return;
                    }

                    const payload = {
                        days: 1,
                        dry_run: dryRun ? 1 : 0,
                    };

                    const response = await axios.post("{{ route('admin.lotto.draws.generate_auto') }}", payload);
                    const summary = response?.data?.data?.summary || null;

                    if (summary) {
                        this.prepareAutoGenSummary(summary, dryRun);
                        this.$refs.autoGenSummaryModal.show();
                    } else {
                        await this.$bvModal.msgBoxOk(response?.data?.message || 'ดำเนินการเสร็จสิ้น', {
                            title: dryRun ? 'ผล Dry-run' : 'ผลการ Generate',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'success',
                            centered: true,
                        });
                    }

                    window.LaravelDataTables['lottoDrawsTable'].draw(false);
                },
                async runAutoResultTestFetch(id) {
                    const response = await axios.post("{{ route('admin.lotto.draws.auto_result_test_fetch') }}", { draw_id: id });
                    const runId = response?.data?.data?.run_id || '';
                    await this.$bvModal.msgBoxOk(`ส่งคำสั่ง Dry-run แล้ว\nRun ID: ${runId}`, {
                        title: 'Auto Result Dry-run',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'warning',
                        centered: true,
                    });
                },
                async runAutoResultManualRetry(id) {
                    const response = await axios.post("{{ route('admin.lotto.draws.auto_result_manual_retry') }}", { draw_id: id });
                    const runId = response?.data?.data?.run_id || '';
                    await this.$bvModal.msgBoxOk(`ส่งคำสั่ง Retry แล้ว\nRun ID: ${runId}`, {
                        title: 'Auto Result Retry',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'dark',
                        centered: true,
                    });
                },
                async showAutoResultLogs(id) {
                    const response = await axios.get("{{ route('admin.lotto.draws.auto_result_logs') }}", {
                        params: { draw_id: id, limit: 100 },
                    });
                    const rows = (response?.data?.data?.items || []).map((item) => {
                        const parsed = item?.parsed_payload_json ? JSON.stringify(item.parsed_payload_json) : '';
                        const normalized = item?.normalized_result_json ? JSON.stringify(item.normalized_result_json) : '';
                        const joined = [parsed, normalized].filter(Boolean).join(' | ');
                        return {
                            ...item,
                            preview: joined.length > 180 ? `${joined.substring(0, 180)}...` : (joined || '-'),
                        };
                    });
                    this.autoResultLogsData = {
                        draw_id: id,
                        items: rows,
                    };
                    this.$refs.autoResultLogsModal.show();
                },
                openAutoResultLogDetail(item) {
                    this.autoResultLogDetail = {
                        ...item,
                        request_meta_json: item?.request_meta_json ? JSON.stringify(item.request_meta_json, null, 2) : '',
                        parsed_payload_json: item?.parsed_payload_json ? JSON.stringify(item.parsed_payload_json, null, 2) : '',
                        normalized_result_json: item?.normalized_result_json ? JSON.stringify(item.normalized_result_json, null, 2) : '',
                    };
                    this.$refs.autoResultLogDetailModal.show();
                },
            },
        });

        window.addModal = function () { window.app.addModal(); };
        window.editModal = function (id) { window.app.editModal(id); };
        window.settleModal = function (id) { window.app.settleModal(id); };
        window.openDraw = function (id) { window.app.openDraw(id); };
        window.closeDraw = function (id) { window.app.closeDraw(id); };
        window.generateAutoDraws = function (dryRun) { window.app.generateAutoDraws(dryRun); };
        window.showDrawBlockedNumbers = function (id) { window.app.openBlockedNumbersModal(id); };
        window.showDrawTicketList = function (id) { window.app.openTicketsSummaryModal(id); };
        window.runAutoResultTestFetch = function (id) { window.app.runAutoResultTestFetch(id); };
        window.runAutoResultManualRetry = function (id) { window.app.runAutoResultManualRetry(id); };
        window.showAutoResultLogs = function (id) { window.app.showAutoResultLogs(id); };
    </script>
@endpush
