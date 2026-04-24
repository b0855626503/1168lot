@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <winning-report-app></winning-report-app>
@endsection

@push('styles')
    <style>
        :root {
            --wr-ink: #111827;
            --wr-muted: #64748b;
            --wr-line: #d8dee8;
            --wr-panel: #ffffff;
            --wr-surface: #f5f7fb;
            --wr-soft: #eef6f5;
            --wr-accent: #0f766e;
            --wr-accent-dark: #115e59;
            --wr-danger: #b91c1c;
            --wr-warning: #b45309;
            --wr-success: #15803d;
        }

        .wr-shell {
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.82), rgba(255, 255, 255, 0.96)),
                linear-gradient(135deg, #ecfeff 0%, #f8fafc 42%, #f0fdf4 100%);
            border: 1px solid #e5edf4;
            border-radius: 8px;
            padding: 16px;
        }

        .wr-card {
            border: 1px solid var(--wr-line);
            border-radius: 8px;
            background: var(--wr-panel);
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.07);
        }

        .wr-hero {
            background: linear-gradient(135deg, #0f766e 0%, #155e75 100%);
            color: #ffffff;
            border: 0;
            overflow: hidden;
        }

        .wr-hero .wr-title,
        .wr-hero .wr-subtitle {
            color: #ffffff;
        }

        .wr-title {
            font-weight: 700;
            color: var(--wr-ink);
            letter-spacing: 0;
        }

        .wr-subtitle {
            color: var(--wr-muted);
            font-size: 0.85rem;
        }

        .wr-meta-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .wr-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 999px;
            padding: 4px 10px;
            color: #e0f2fe;
            background: rgba(255, 255, 255, 0.1);
            font-size: 0.78rem;
            font-weight: 600;
        }

        .wr-kpi-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 12px;
        }

        .wr-kpi {
            border: 1px solid var(--wr-line);
            border-radius: 8px;
            padding: 12px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        .wr-kpi:hover {
            box-shadow: 0 10px 22px rgba(30, 41, 59, 0.09);
            transform: translateY(-1px);
        }

        .wr-kpi__label {
            color: var(--wr-muted);
            font-size: 0.74rem;
            letter-spacing: 0;
        }

        .wr-kpi__value {
            color: var(--wr-ink);
            font-weight: 700;
            font-size: 1.06rem;
            margin-top: 4px;
        }

        .wr-kpi--money .wr-kpi__value {
            color: var(--wr-accent-dark);
            font-variant-numeric: tabular-nums;
        }

        .wr-kpi--loss .wr-kpi__value {
            color: var(--wr-danger);
        }

        .wr-status {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 3px 10px;
            font-size: 0.75rem;
            font-weight: 700;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .wr-status--settled,
        .wr-status--credited {
            color: #166534;
            background: #dcfce7;
            border-color: #86efac;
        }

        .wr-status--pending {
            color: #854d0e;
            background: #fef3c7;
            border-color: #fcd34d;
        }

        .wr-status--failed,
        .wr-status--voided {
            color: #991b1b;
            background: #fee2e2;
            border-color: #fca5a5;
        }

        .wr-filter-block {
            border: 1px solid var(--wr-line);
            border-radius: 8px;
            padding: 10px;
            background: var(--wr-panel);
            height: 100%;
        }

        .wr-filter-heading {
            font-size: 0.8rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
        }

        .wr-filter-label {
            color: #334155;
            font-size: 0.78rem;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .wr-filter-block .form-control {
            border-color: #cbd5e1;
            border-radius: 6px;
        }

        .wr-filter-block .form-control:focus {
            border-color: var(--wr-accent);
            box-shadow: 0 0 0 0.12rem rgba(15, 118, 110, 0.15);
        }

        .wr-table-wrap {
            border: 1px solid var(--wr-line);
            border-radius: 8px;
            overflow: hidden;
        }

        .wr-table {
            margin-bottom: 0;
            font-size: 0.83rem;
        }

        .wr-table thead th {
            position: sticky;
            top: 0;
            background: #f1f5f9;
            z-index: 2;
            font-size: 0.74rem;
            letter-spacing: 0;
            color: #475569;
            border-bottom: 1px solid #cbd5e1;
            white-space: nowrap;
        }

        .wr-table tbody tr:hover {
            background: #f8fafc;
        }

        .wr-users-table thead th {
            background: #e0f2fe;
            color: #0c4a6e;
            border-bottom-color: #bae6fd;
        }

        .wr-users-table tbody tr:nth-child(odd) {
            background: #f0f9ff;
        }

        .wr-users-table tbody tr:hover {
            background: #e0f2fe;
        }

        .wr-bets-table thead th {
            background: #ecfdf5;
            color: #14532d;
            border-bottom-color: #bbf7d0;
        }

        .wr-bets-table tbody tr:nth-child(odd) {
            background: #f0fdf4;
        }

        .wr-bets-table tbody tr:hover {
            background: #dcfce7;
        }

        .wr-table td,
        .wr-table th {
            vertical-align: middle;
            white-space: nowrap;
        }

        .wr-empty {
            color: var(--wr-muted);
            text-align: center;
            padding: 28px 10px;
            background: #f8fafc;
        }

        .wr-actions-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .wr-actions-grid .btn {
            min-width: 0;
            width: 100%;
            border-radius: 6px;
            font-weight: 600;
        }

        .wr-actions-grid .btn-primary {
            background: var(--wr-accent);
            border-color: var(--wr-accent);
        }

        .wr-actions-grid .btn-primary:hover {
            background: var(--wr-accent-dark);
            border-color: var(--wr-accent-dark);
        }

        .wr-loading {
            color: #334155;
            border: 1px solid #bae6fd;
            background: #f0f9ff;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 0.83rem;
        }

        .wr-loading-float {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            min-width: 320px;
            max-width: 92vw;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid #bae6fd;
            box-shadow: 0 12px 25px rgba(15, 23, 42, 0.25);
            padding: 14px 16px;
            text-align: center;
        }

        .wr-loading-float__spinner {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 3px solid #cbd5e1;
            border-top-color: #0f766e;
            margin: 0 auto 10px;
            animation: wr-spin 0.8s linear infinite;
        }

        .wr-loading-float__title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #0f172a;
        }

        .wr-loading-float__desc {
            margin-top: 4px;
            font-size: 0.8rem;
            color: #475569;
        }

        @keyframes wr-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .wr-error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 0.82rem;
        }

        .wr-note {
            border: 1px solid #fed7aa;
            background: #fff7ed;
            color: #9a3412;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 0.82rem;
        }

        .wr-pending {
            color: #854d0e;
            font-weight: 600;
        }

        .wr-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .wr-row-count {
            border-radius: 999px;
            padding: 3px 9px;
            color: #334155;
            background: #e2e8f0;
            font-size: 0.76rem;
            font-weight: 700;
        }

        .wr-money {
            font-variant-numeric: tabular-nums;
            font-weight: 700;
        }

        @media (max-width: 1199.98px) {
            .wr-kpi-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .wr-shell {
                padding: 10px;
                border-radius: 12px;
            }

            .wr-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .wr-title {
                font-size: 1rem;
            }

            .wr-subtitle {
                font-size: 0.78rem;
            }

            .wr-actions-grid {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .wr-kpi {
                transition: none;
            }
        }
    </style>
@endpush

@push('scripts')
    <script type="text/x-template" id="winning-report-app-template">
        <section class="content text-sm">
            <div class="container-fluid">
                <div class="wr-shell">
                    <div class="wr-card wr-hero p-3 mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-start mb-2">
                            <div>
                                <div class="wr-title">รายงานผู้ถูกรางวัล / รายงานสรุปผลจ่าย</div>
                                <div class="wr-subtitle">ข้อมูลรายงานจากรายการที่สรุปผลแล้วเท่านั้น</div>
                            </div>
                            <div class="wr-subtitle mt-1">อัปเดตล่าสุด: @{{ nowLabel }}</div>
                        </div>
                        <div class="wr-meta-strip">
                            <span class="wr-meta-pill"><i class="fas fa-calendar-day"></i> วันที่รายงาน: @{{ filters.date || '-' }}</span>
                            <span class="wr-meta-pill"><i class="fas fa-check-circle"></i> สถานะ: @{{ statusLabel(summary.settlement_status) }}</span>
                        </div>
                    </div>

                    <div class="wr-card p-3">
                        <div class="wr-filter-heading">ตัวกรองรายงาน</div>
                        <div class="row">
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-2">
                                <div class="wr-filter-block">
                                    <div class="wr-filter-label">วันที่งวด</div>
                                    <input v-model="filters.date" type="date" class="form-control form-control-sm" @change="onSummaryFilterChange">
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-2">
                                <div class="wr-filter-block">
                                    <div class="wr-filter-label">ประเภทหวย</div>
                                    <select v-model="filters.lottery_type" class="form-control form-control-sm" @change="onSummaryFilterChange">
                                        <option value="">ทั้งหมด</option>
                                        <option v-for="item in lotteryTypeOptions" :key="item.value" :value="item.value">@{{ item.text }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-2">
                                <div class="wr-filter-block">
                                    <div class="wr-filter-label">ตลาด / หวย</div>
                                    <select ref="marketSelect" v-model="filters.market" class="form-control form-control-sm" @change="onSummaryFilterChange">
                                        <option value="">ทั้งหมด</option>
                                        <optgroup v-for="group in marketOptions" :key="group.label" :label="group.label">
                                            <option v-for="option in group.options" :key="option.value" :value="option.value" :data-logo="option.logo || ''">
                                                @{{ option.text }}
                                            </option>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-2">
                                <div class="wr-filter-block">
                                    <div class="wr-filter-label">รหัสสมาชิก (ไอดีลูกค้า)</div>
                                    <input v-model.trim="detailFilters.user_id" type="text" class="form-control form-control-sm" placeholder="เช่น 12345 หรือ boat001" @keyup.enter.prevent="loadDetails">
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-2">
                                <div class="wr-filter-block">
                                    <div class="wr-filter-label">ประเภทแทง</div>
                                    <select v-model="detailFilters.bet_type" class="form-control form-control-sm" @change="loadDetails">
                                        <option value="">ทั้งหมด</option>
                                        <option v-for="item in betTypeOptions" :key="item.value" :value="item.value">@{{ item.text }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-2">
                                <div class="wr-filter-block">
                                    <div class="wr-filter-label">เลขที่แทง</div>
                                    <input v-model="detailFilters.number" type="text" class="form-control form-control-sm" placeholder="123" @keyup.enter.prevent="loadDetails">
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-2">
                                <div class="wr-filter-block">
                                    <div class="wr-filter-label">สถานะรายการ</div>
                                    <select v-model="detailFilters.status" class="form-control form-control-sm" @change="loadDetails">
                                        <option value="">ทั้งหมด</option>
                                        <option value="pending">รอดำเนินการ</option>
                                        <option value="settled">สรุปผลแล้ว</option>
                                        <option value="credited">จ่ายเงินแล้ว</option>
                                        <option value="failed">ผิดพลาด</option>
                                        <option value="voided">ยกเลิก</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 mb-2 d-flex align-items-stretch">
                                <div class="wr-filter-block w-100">
                                    <div class="wr-filter-label">การทำงาน</div>
                                    <div class="wr-actions-grid">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click.prevent="resetFilters"><i class="fas fa-undo-alt"></i> ล้างค่า</button>
                                        <button type="button" class="btn btn-sm btn-primary" :disabled="isLoading" @click.prevent="loadAll"><i class="fas fa-search"></i> แสดงรายงาน</button>
                                        <button type="button" class="btn btn-sm btn-success" :disabled="isLoading" @click.prevent="exportReport('summary', 'csv')"><i class="fas fa-file-csv"></i> สรุป CSV</button>
                                        <button type="button" class="btn btn-sm btn-outline-success" :disabled="isLoading" @click.prevent="exportReport('users', 'csv')"><i class="fas fa-users"></i> สมาชิก CSV</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" :disabled="isLoading" @click.prevent="exportReport('bets', 'xlsx')"><i class="fas fa-file-excel"></i> รายการ XLSX</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="wr-subtitle" style="font-size:0.78rem;">
                                    เปลี่ยนตัวกรองแล้วข้อมูลจะอัปเดตโดยอัตโนมัติ กด “แสดงรายงาน” เพื่อรีโหลดแบบเต็มอีกครั้งได้
                                </div>
                            </div>
                        </div>

                        <div v-if="isLoading" class="wr-loading-float">
                            <div class="wr-loading-float__spinner"></div>
                            <div class="wr-loading-float__title">กำลังอัปเดตข้อมูลรายงาน</div>
                            <div class="wr-loading-float__desc">โปรดรอสักครู่ ระบบกำลังประมวลผลข้อมูลล่าสุด</div>
                        </div>
                        <div v-if="!hasMaterializedReportData" class="wr-error mb-2">
                            ยังไม่มีข้อมูลรายงานที่จัดเก็บไว้แล้ว กรุณาสรุปผลรอบใหม่ หรือเติมข้อมูลย้อนหลังสำหรับรอบเก่าก่อน
                        </div>
                        <div v-if="errorMessage" class="wr-error mb-2">@{{ errorMessage }}</div>
                        <div v-if="hasMaterializedReportData && !isLoading && users.length === 0 && bets.length === 0 && summary.latest_round_id && summary.winning_ticket_count === 0" class="wr-note mb-2">
                            รอบที่เลือกมีการสรุปผลแล้ว แต่ยังไม่มีผู้ถูกรางวัลในเงื่อนไขนี้
                        </div>
                        <div class="wr-kpi-grid mt-2">
                            <div class="wr-kpi wr-kpi--money">
                                <div class="wr-kpi__label">ยอดแทงที่ถูกรางวัล</div>
                                <div class="wr-kpi__value">@{{ fm(summary.total_stake) }}</div>
                            </div>
                            <div class="wr-kpi wr-kpi--money">
                                <div class="wr-kpi__label">ยอดจ่ายรางวัล</div>
                                <div class="wr-kpi__value">@{{ fm(summary.total_payout) }}</div>
                            </div>
                            <div class="wr-kpi wr-kpi--loss">
                                <div class="wr-kpi__label">กำไร / ขาดทุนสุทธิ</div>
                                <div class="wr-kpi__value">@{{ fm(summary.net_profit_loss) }}</div>
                            </div>
                            <div class="wr-kpi">
                                <div class="wr-kpi__label">จำนวนผู้ถูกรางวัล</div>
                                <div class="wr-kpi__value">@{{ intValue(summary.winner_count) }}</div>
                            </div>
                            <div class="wr-kpi">
                                <div class="wr-kpi__label">จำนวนรายการถูกรางวัล</div>
                                <div class="wr-kpi__value">@{{ intValue(summary.winning_ticket_count) }}</div>
                            </div>
                            <div class="wr-kpi">
                                <div class="wr-kpi__label">สถานะสรุปผล</div>
                                <div class="wr-kpi__value">
                                    <span :class="statusClass(summary.settlement_status)">@{{ statusLabel(summary.settlement_status) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12 mb-3">
                            <div class="wr-card p-3">
                                <div class="wr-section-head">
                                    <div>
                                        <div class="wr-title">สรุปตามสมาชิก</div>
                                        <div class="wr-subtitle">รวมยอดถูกรางวัลแยกตามสมาชิกในรอบที่เลือก</div>
                                    </div>
                                    <div class="wr-row-count">@{{ users.length }} รายการ</div>
                                </div>
                                <div class="wr-table-wrap table-responsive" style="max-height: 380px;">
                                    <table class="table table-sm wr-table wr-users-table">
                                        <thead>
                                            <tr>
                                                <th>สมาชิก</th>
                                                <th class="text-right">ยอดแทง</th>
                                                <th class="text-right">ยอดจ่าย</th>
                                                <th class="text-right">สุทธิรายสมาชิก</th>
                                                <th class="text-center">จำนวนรายการ</th>
                                                <th>เลขที่ถูกรางวัล</th>
                                                <th>สถานะ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="row in users" :key="row.user_id">
                                                <td>@{{ row.username || ('USER-' + row.user_id) }}</td>
                                                <td class="text-right wr-money">@{{ fm(row.total_stake) }}</td>
                                                <td class="text-right wr-money">@{{ fm(row.total_payout) }}</td>
                                                <td class="text-right wr-money">@{{ fm(row.net_by_user) }}</td>
                                                <td class="text-center">@{{ intValue(row.winning_bet_count) }}</td>
                                                <td style="max-width: 280px; overflow: hidden; text-overflow: ellipsis;">@{{ row.winning_numbers || '-' }}</td>
                                                <td><span :class="statusClass(row.credited_status)">@{{ statusLabel(row.credited_status) }}</span></td>
                                            </tr>
                                            <tr v-if="users.length === 0">
                                                <td colspan="7" class="wr-empty">ไม่มีข้อมูลสมาชิกตามเงื่อนไขนี้</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="wr-card p-3">
                                <div class="wr-section-head">
                                    <div>
                                        <div class="wr-title">รายละเอียดรายการถูกรางวัล</div>
                                        <div class="wr-subtitle">รายการระดับโพย/เลข พร้อมชุดสรุปผลอ้างอิง</div>
                                    </div>
                                    <div class="wr-row-count">@{{ bets.length }} รายการ</div>
                                </div>
                                <div class="wr-table-wrap table-responsive" style="max-height: 420px;">
                                    <table class="table table-sm wr-table wr-bets-table">
                                        <thead>
                                            <tr>
                                                <th>สมาชิก</th>
                                                <th>เลขโพย</th>
                                                <th>ประเภทแทง</th>
                                                <th class="text-center">เลขที่แทง / ถูกรางวัล</th>
                                                <th class="text-right">ยอดแทง</th>
                                                <th class="text-right">อัตราจ่าย</th>
                                                <th class="text-right">ยอดจ่าย</th>
                                                <th>ชุดสรุปผล</th>
                                                <th>เวลาสรุปผล</th>
                                                <th>เวลาจ่ายเงิน</th>
                                                <th>สถานะ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="row in bets" :key="(row.user_id || '-') + '-' + row.ticket_no + '-' + row.number + '-' + row.settlement_batch_id">
                                                <td>@{{ row.username || ('USER-' + row.user_id) }}</td>
                                                <td>@{{ row.ticket_no || '-' }}</td>
                                                <td>@{{ betTypeLabel(row.bet_type) }}</td>
                                                <td class="text-center">@{{ row.number || '-' }}</td>
                                                <td class="text-right wr-money">@{{ fm(row.stake) }}</td>
                                                <td class="text-right wr-money">@{{ fm(row.odds, 4) }}</td>
                                                <td class="text-right wr-money">@{{ fm(row.payout) }}</td>
                                                <td>@{{ row.settlement_batch_id || '-' }}</td>
                                                <td>@{{ dt(row.settled_at) }}</td>
                                                <td>@{{ dt(row.credited_at) }}</td>
                                                <td><span :class="statusClass(row.status)">@{{ statusLabel(row.status) }}</span></td>
                                            </tr>
                                            <tr v-if="bets.length === 0">
                                                <td colspan="11" class="wr-empty">ไม่มีรายการถูกรางวัลตามเงื่อนไขนี้</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </script>

    <script>
        Vue.component('winning-report-app', {
            template: '#winning-report-app-template',
            data() {
                return {
                    lotteryTypeOptions: @json($lotteryTypeOptions ?? []),
                    marketOptions: @json($marketOptions ?? []),
                    betTypeOptions: [
                        { value: 'top_3', text: '3 ตัวบน' },
                        { value: 'tod_3', text: '3 ตัวโต๊ด' },
                        { value: 'top_2', text: '2 ตัวบน' },
                        { value: 'bottom_2', text: '2 ตัวล่าง' },
                        { value: 'run_top', text: 'วิ่งบน' },
                        { value: 'run_bottom', text: 'วิ่งล่าง' },
                    ],
                    filters: {
                        date: @json($initialDate ?? ''),
                        lottery_type: @json($initialLotteryType ?? ''),
                        market: @json($initialMarket ?? ''),
                    },
                    detailFilters: {
                        user_id: '',
                        bet_type: '',
                        number: '',
                        status: '',
                    },
                    summary: {},
                    users: [],
                    bets: [],
                    isLoading: false,
                    errorMessage: '',
                    nowLabel: '-',
                    hasMaterializedReportData: @json($hasMaterializedReportData ?? false),
                    effectiveRoundId: null,
                };
            },
            mounted() {
                this.nowLabel = this.formatNow();
                this.initMarketSelect2();
                this.loadAll();
            },
            watch: {
                marketOptions() {
                    this.$nextTick(() => {
                        this.initMarketSelect2();
                    });
                },
            },
            methods: {
                initMarketSelect2() {
                    const marketSelect = this.$refs.marketSelect;
                    if (!marketSelect || typeof window.$ !== 'function') {
                        return;
                    }

                    const $market = window.$(marketSelect);
                    if (typeof $market.select2 !== 'function') {
                        return;
                    }

                    if ($market.hasClass('select2-hidden-accessible')) {
                        $market.off('change.winning-report');
                        $market.select2('destroy');
                    }

                    const renderMarketOption = function (state) {
                        if (!state.id) {
                            return state.text;
                        }

                        const optionEl = state.element;
                        const logo = optionEl ? String(optionEl.getAttribute('data-logo') || '') : '';
                        const safeText = window.$('<span/>').text(state.text || '').html();

                        if (!logo) {
                            return window.$('<span>' + safeText + '</span>');
                        }

                        return window.$(
                            '<span style="display:flex;align-items:center;gap:8px;">'
                            + '<img src="' + logo + '" alt="" style="width:20px;height:20px;object-fit:cover;border-radius:50%;border:1px solid #e5e7eb;">'
                            + '<span>' + safeText + '</span>'
                            + '</span>'
                        );
                    };

                    $market.select2({
                        width: '100%',
                        placeholder: 'ทั้งหมด',
                        allowClear: true,
                        templateResult: renderMarketOption,
                        templateSelection: renderMarketOption,
                        escapeMarkup(markup) {
                            return markup;
                        },
                    });

                    $market.val(this.filters.market || '').trigger('change.select2');
                    $market.on('change.winning-report', (event) => {
                        this.filters.market = String(window.$(event.currentTarget).val() || '');
                    });
                },
                formatNow() {
                    return new Date().toLocaleString();
                },
                betTypeLabel(betType) {
                    const normalized = String(betType || '');
                    const item = this.betTypeOptions.find((option) => option.value === normalized);

                    return item ? item.text : (normalized || '-');
                },
                fm(value, fraction = 2) {
                    if (value === null || value === undefined || value === '') {
                        return '-';
                    }

                    const num = Number(value);
                    if (Number.isNaN(num)) {
                        return '-';
                    }

                    return num.toLocaleString(undefined, {
                        minimumFractionDigits: fraction,
                        maximumFractionDigits: fraction,
                    });
                },
                intValue(value) {
                    const num = Number(value || 0);
                    return Number.isNaN(num) ? 0 : num;
                },
                dt(value) {
                    if (!value) {
                        return '-';
                    }

                    return value;
                },
                statusLabel(status) {
                    const normalized = String(status || '').toLowerCase();
                    const labels = {
                        pending: 'รอดำเนินการ',
                        settled: 'สรุปผลแล้ว',
                        credited: 'จ่ายเงินแล้ว',
                        failed: 'ผิดพลาด',
                        voided: 'ยกเลิก',
                    };

                    return labels[normalized] || '-';
                },
                statusClass(status) {
                    const normalized = String(status || '').toLowerCase();
                    if (normalized === 'settled' || normalized === 'credited') {
                        return 'wr-status wr-status--settled';
                    }
                    if (normalized === 'pending') {
                        return 'wr-status wr-status--pending';
                    }
                    if (normalized === 'failed' || normalized === 'voided') {
                        return 'wr-status wr-status--failed';
                    }

                    return 'wr-status';
                },
                query(params) {
                    return Object.keys(params)
                        .filter((key) => {
                            const value = params[key];
                            if (value === null || value === '' || value === undefined) {
                                return false;
                            }

                            if (typeof value === 'number' && Number.isNaN(value)) {
                                return false;
                            }

                            return true;
                        })
                        .map((key) => encodeURIComponent(key) + '=' + encodeURIComponent(params[key]))
                        .join('&');
                },
                async refreshFilterOptions() {
                    const filterRes = await axios.get('{{ route('admin.lotto.winning_report.filter_options') }}?' + this.query({
                        date: this.filters.date,
                        lottery_type: this.filters.lottery_type,
                    }));

                    this.lotteryTypeOptions = filterRes.data.lottery_type_options || [];
                    this.marketOptions = filterRes.data.market_options || [];

                    const marketExists = this.marketOptions.some((group) => {
                        return Array.isArray(group.options) && group.options.some((option) => option.value === this.filters.market);
                    });
                    if (!marketExists) {
                        this.filters.market = '';
                    }
                },
                async onSummaryFilterChange() {
                    this.errorMessage = '';
                    this.isLoading = true;
                    this.nowLabel = this.formatNow();

                    try {
                        await this.refreshFilterOptions();
                        await this.loadAll();
                    } catch (error) {
                        this.handleError(error, 'โหลดตัวกรองไม่สำเร็จ');
                    } finally {
                        this.isLoading = false;
                    }
                },
                async loadDetails() {
                    const roundIds = this.resolveDetailRoundIds();
                    if (roundIds.length === 0) {
                        this.effectiveRoundId = null;
                        this.users = [];
                        this.bets = [];
                        return;
                    }

                    this.effectiveRoundId = roundIds[0] || null;
                    const usersRows = [];
                    const betsRows = [];

                    for (const roundId of roundIds) {
                        const common = {
                            round_id: roundId,
                            user_id: this.detailFilters.user_id,
                        };

                        const usersQuery = this.query({
                            ...common,
                            per_page: 100,
                        });

                        const betsQuery = this.query({
                            ...common,
                            bet_type: this.detailFilters.bet_type,
                            number: this.detailFilters.number,
                            status: this.detailFilters.status,
                            per_page: 100,
                        });

                        const [usersRes, betsRes] = await Promise.all([
                            axios.get('{{ route('admin.lotto.winning_report.users') }}?' + usersQuery),
                            axios.get('{{ route('admin.lotto.winning_report.bets') }}?' + betsQuery),
                        ]);

                        usersRows.push(...(usersRes.data.data || []));
                        betsRows.push(...(betsRes.data.data || []));
                    }

                    this.users = this.mergeUsersRows(usersRows);
                    this.bets = betsRows;
                },
                resolveDetailRoundIds() {
                    if (Array.isArray(this.summary.round_ids) && this.summary.round_ids.length > 0) {
                        return this.summary.round_ids
                            .map((id) => Number(id))
                            .filter((id) => Number.isInteger(id) && id > 0);
                    }

                    if (this.summary.latest_round_id) {
                        return [Number(this.summary.latest_round_id)];
                    }

                    return [];
                },
                mergeUsersRows(rows) {
                    const byUser = {};

                    rows.forEach((row) => {
                        const userId = Number(row.user_id || 0);
                        if (!userId) {
                            return;
                        }

                        if (!byUser[userId]) {
                            byUser[userId] = {
                                user_id: userId,
                                username: row.username || '',
                                total_stake: 0,
                                total_payout: 0,
                                net_by_user: 0,
                                has_pending_financial: false,
                                winning_bet_count: 0,
                                winning_numbers: [],
                                credited_status: 'settled',
                            };
                        }

                        byUser[userId].total_stake += Number(row.total_stake || 0);

                        if (row.total_payout === null || row.net_by_user === null) {
                            byUser[userId].has_pending_financial = true;
                        } else if (!byUser[userId].has_pending_financial) {
                            byUser[userId].total_payout += Number(row.total_payout || 0);
                            byUser[userId].net_by_user += Number(row.net_by_user || 0);
                        }

                        byUser[userId].winning_bet_count += Number(row.winning_bet_count || 0);

                        if (row.winning_numbers) {
                            String(row.winning_numbers).split(',').forEach((item) => {
                                const normalized = String(item).trim();
                                if (normalized && !byUser[userId].winning_numbers.includes(normalized)) {
                                    byUser[userId].winning_numbers.push(normalized);
                                }
                            });
                        }

                        const status = String(row.credited_status || '').toLowerCase();
                        if (status === 'pending') {
                            byUser[userId].credited_status = 'pending';
                        } else if (status === 'failed' && byUser[userId].credited_status !== 'pending') {
                            byUser[userId].credited_status = 'failed';
                        } else if (status === 'voided' && !['pending', 'failed'].includes(byUser[userId].credited_status)) {
                            byUser[userId].credited_status = 'voided';
                        }
                    });

                    return Object.values(byUser).map((row) => {
                        const totalPayout = row.has_pending_financial ? null : Number(row.total_payout.toFixed(2));
                        const netByUser = row.has_pending_financial ? null : Number(row.net_by_user.toFixed(2));

                        return {
                            ...row,
                            total_stake: Number(row.total_stake.toFixed(2)),
                            total_payout: totalPayout,
                            net_by_user: netByUser,
                            winning_numbers: row.winning_numbers.join(', '),
                        };
                    }).sort((a, b) => {
                        const left = a.total_payout === null ? -1 : Number(a.total_payout);
                        const right = b.total_payout === null ? -1 : Number(b.total_payout);

                        return right - left;
                    });
                },
                async loadAll() {
                    this.errorMessage = '';
                    this.isLoading = true;
                    this.nowLabel = this.formatNow();

                    try {
                        const summaryQ = this.query(this.filters);
                        const summaryRes = await axios.get('{{ route('admin.lotto.winning_report.summary') }}?' + summaryQ);
                        this.summary = summaryRes.data.summary || {};
                        this.summary.round_ids = summaryRes.data.round_ids || [];
                        this.summary.latest_round_id = summaryRes.data.latest_round_id || null;

                        await this.loadDetails();
                    } catch (error) {
                        this.handleError(error, 'โหลดรายงานไม่สำเร็จ');
                    } finally {
                        this.isLoading = false;
                    }
                },
                handleError(error, fallbackMessage) {
                    const message = error?.response?.data?.message || fallbackMessage;
                    this.errorMessage = message;
                },
                resetFilters() {
                    this.filters = {
                        date: @json($initialDate ?? ''),
                        lottery_type: '',
                        market: '',
                    };
                    this.detailFilters = {
                        user_id: '',
                        bet_type: '',
                        number: '',
                        status: '',
                    };
                    this.summary = {};
                    this.effectiveRoundId = null;
                    this.users = [];
                    this.bets = [];
                    this.errorMessage = '';
                    this.nowLabel = this.formatNow();
                    this.$nextTick(() => {
                        this.initMarketSelect2();
                    });
                },
                exportReport(level, format) {
                    const roundId = this.effectiveRoundId || this.summary.latest_round_id || null;
                    if (!roundId) {
                        this.errorMessage = 'ยังไม่พบรอบข้อมูลสำหรับส่งออกไฟล์';
                        return;
                    }

                    const q = this.query({
                        round_id: roundId,
                        user_id: this.detailFilters.user_id,
                        bet_type: this.detailFilters.bet_type,
                        number: this.detailFilters.number,
                        status: this.detailFilters.status,
                        level,
                        format,
                    });

                    window.open('{{ route('admin.lotto.winning_report.export') }}?' + q, '_blank');
                },
            },
        });
    </script>
@endpush
