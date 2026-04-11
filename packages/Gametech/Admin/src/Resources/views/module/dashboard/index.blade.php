@extends('admin::layouts.master')

{{-- page title --}}
@section('title','Dashboard')


@section('content')
    <admin-dashboard ref="dashboard"></admin-dashboard>
@endsection
@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('/vendor/chart.js/Chart.css') }}">
    <style>
        .kpi-card {
            background: #fff;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            border-left-width: 4px;
            padding: 12px;
            margin-bottom: 12px;
            min-height: 140px;
            width: 100%;
        }
        .kpi-title { font-weight: 600; font-size: 12px; color: #6c757d; }
        .kpi-value { font-size: 18px; font-weight: 700; margin-top: 6px; }
        .kpi-value--deposit { color: #1f9d4e; }
        .kpi-value--withdraw { color: #dc3545; }
        .kpi-value--positive { color: #1f9d4e; }
        .kpi-value--negative { color: #dc3545; }
        .kpi-sub { font-size: 12px; color: #6c757d; }
        .kpi-paren { color: #0d6efd; font-weight: 600; }
        .metric-deposit { border-left-color: #28a745; }
        .metric-withdraw { border-left-color: #dc3545; }
        .metric-bonus { border-left-color: #6f42c1; }
        .metric-lotto { border-left-color: #ff7f0e; }
        .metric-net { border-left-color: #17a2b8; }
        .metric-register { border-left-color: #17a2b8; }
        .metric-ftd { border-left-color: #20c997; }
        .lotto-section-card { border-color: #ffcf9c; }
        .lotto-section-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }
        .lotto-block {
            border: 1px solid #ffe3c5;
            border-radius: 6px;
            padding: 10px;
            background: #fffaf4;
        }
        .lotto-block-title {
            font-size: 12px;
            font-weight: 700;
            color: #d16a00;
            margin-bottom: 6px;
        }
        .lotto-block-main {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .lotto-block-line {
            font-size: 12px;
            color: #6c757d;
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }
        .lotto-recent-wrap {
            margin-top: 10px;
            border: 1px solid #ffe3c5;
            border-radius: 6px;
            background: #fff;
            overflow: hidden;
        }
        .lotto-recent-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 10px;
            background: #fff6eb;
            border-bottom: 1px solid #ffe3c5;
        }
        .lotto-recent-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            min-width: 0;
            flex: 1 1 auto;
        }
        .lotto-recent-title {
            font-size: 12px;
            font-weight: 700;
            color: #d16a00;
            flex: 0 1 auto;
            min-width: 0;
        }
        .lotto-recent-count {
            font-size: 11px;
            color: #6c757d;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 52px;
            padding: 2px 8px;
            border: 1px solid #f1d0ac;
            border-radius: 999px;
            background: #fff;
            flex: 0 0 auto;
        }
        .lotto-recent-filter {
            min-width: 260px;
            max-width: 320px;
            flex: 0 1 320px;
        }
        .lotto-recent-table {
            margin-bottom: 0;
        }
        .lotto-recent-table th,
        .lotto-recent-table td {
            font-size: 12px;
            white-space: nowrap;
            vertical-align: middle;
        }
        .lotto-recent-table .summary-col {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-teal { border-color: #20c997; }
        .dashboard-table { table-layout: fixed; width: 100%; }
        .dashboard-table th,
        .dashboard-table td {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            font-size: 12px;
        }
        .dashboard-table th { font-weight: 600; }
        .dashboard-table td img { max-width: 22px; height: auto; }
        .dashboard-tabs .nav-link {
            padding: 4px 10px;
            font-size: 12px;
        }
        .deposit-activity-scroll {
            max-height: 360px;
            overflow-y: auto;
        }
        .bank-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            line-height: 1.2;
        }
        .bank-meta-logo {
            width: 18px;
            height: 18px;
            object-fit: contain;
            border-radius: 50%;
            border: 1px solid #e9ecef;
            background: #fff;
            flex: 0 0 auto;
        }
        .bank-meta-account {
            display: inline-block;
            margin-top: 0;
            font-size: 11px;
            color: #6c757d;
        }
        .dashboard-amount {
            text-align: right;
            font-weight: 700;
            white-space: nowrap;
        }
        .dashboard-amount--deposit {
            color: #28a745;
        }
        .dashboard-amount--withdraw {
            color: #dc3545;
        }
        .status-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .status-light {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            display: inline-block;
            position: relative;
            animation: status-dot-core 1.8s ease-in-out infinite;
        }
        .status-light::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 0 0 var(--status-wave-color, rgba(39, 194, 90, 0.45));
            animation: status-dot-wave 1.8s ease-out infinite;
            pointer-events: none;
        }
        .status-light--success {
            background: #28a745;
            box-shadow: 0 0 0 1px rgba(40, 167, 69, 0.25);
            --status-wave-color: rgba(40, 167, 69, 0.45);
        }
        .status-light--waiting {
            background: #ffc107;
            box-shadow: 0 0 0 1px rgba(255, 193, 7, 0.3);
            --status-wave-color: rgba(255, 193, 7, 0.45);
        }
        .table-responsive { -webkit-overflow-scrolling: touch; }
        .dashboard-equal-row {
            align-items: flex-start !important;
        }
        .dashboard-equal-row > [class*="col-"] {
            display: flex;
            align-items: flex-start !important;
            padding-top: 0 !important;
        }
        .dashboard-equal-row .card {
            width: 100%;
            height: auto;
            align-self: flex-start;
            margin-top: 0 !important;
        }
        .dashboard-equal-row .kpi-card {
            align-self: flex-start;
        }
        .dashboard-activity-row {
            align-items: flex-start;
        }
        .dashboard-activity-row > [class*="col-"] {
            display: flex;
            align-items: flex-start;
        }
        .dashboard-activity-row .card {
            width: 100%;
            margin-top: 0;
            height: auto;
            align-self: flex-start;
        }
        .dashboard-top-align-row {
            align-items: flex-start !important;
        }
        .dashboard-top-align-row > [class*="col-"] {
            display: flex;
            align-items: flex-start !important;
            padding-top: 0 !important;
        }
        .dashboard-top-align-row .card {
            width: 100%;
            margin-top: 0 !important;
            height: auto;
            align-self: flex-start;
        }
        .dashboard-clickable {
            cursor: pointer;
        }
        .dashboard-clickable:hover {
            color: #0056b3;
        }
        .online-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 999px;
            padding: 4px 12px;
            color: #495057;
            font-size: 12px;
            font-weight: 600;
        }
        .status-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #27c25a;
            display: inline-block;
            margin-right: 6px;
            position: relative;
            box-shadow: 0 0 0 1px rgba(39, 194, 90, 0.28);
            animation: status-dot-core 1.8s ease-in-out infinite;
            vertical-align: middle;
        }
        .status-dot::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 0 0 rgba(39, 194, 90, 0.45);
            animation: status-dot-wave 1.8s ease-out infinite;
            pointer-events: none;
        }
        .online-status .status-dot {
            margin-right: 0;
            width: 10px;
            height: 10px;
        }
        .online-status .status-sep {
            color: #adb5bd;
            font-weight: 400;
        }
        .online-status .status-sub {
            font-weight: 500;
            color: #6c757d;
        }
        .dashboard-status-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #78c8ff;
            background: linear-gradient(135deg, #eff9ff 0%, #dff3ff 100%);
            box-shadow: 0 6px 16px rgba(33, 150, 243, 0.15);
            color: #0b4f7a;
            font-size: 14px;
            font-weight: 700;
        }
        .dashboard-status-icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            font-size: 20px;
            line-height: 1;
        }
        .dashboard-status-icon--spinner {
            animation: dashboard-clock-spin 1.2s linear infinite;
            transform-origin: 50% 50%;
            filter: drop-shadow(0 1px 2px rgba(11, 79, 122, 0.25));
        }
        .dashboard-status-text {
            line-height: 1.35;
            letter-spacing: 0.1px;
        }
        .dashboard-status-slot {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        .dashboard-status-placeholder {
            height: 56px;
            display: flex;
            align-items: center;
        }
        .dashboard-status-placeholder .dashboard-status-bar {
            width: 100%;
        }
        .block-live-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 999px;
            border: 1px solid #b7e7c7;
            background: #eaf8ef;
            color: #198754;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.2;
            margin-right: 6px;
        }
        .block-live-badge i {
            font-size: 10px;
            line-height: 1;
        }
        .card-header .card-title {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
        }
        @keyframes dashboard-clock-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes status-dot-core {
            0%, 100% {
                transform: scale(1);
                filter: brightness(0.95);
            }
            50% {
                transform: scale(1.08);
                filter: brightness(1.35);
            }
        }
        @keyframes status-dot-wave {
            0% {
                width: 100%;
                height: 100%;
                opacity: 0.75;
                box-shadow: 0 0 0 0 rgba(39, 194, 90, 0.45);
            }
            70% {
                width: 270%;
                height: 270%;
                opacity: 0;
                box-shadow: 0 0 0 10px rgba(39, 194, 90, 0);
            }
            100% {
                width: 270%;
                height: 270%;
                opacity: 0;
                box-shadow: 0 0 0 10px rgba(39, 194, 90, 0);
            }
        }
        .dashboard-fade-enter-active,
        .dashboard-fade-leave-active {
            transition: opacity 0.2s ease;
        }
        .dashboard-fade-enter,
        .dashboard-fade-leave-to {
            opacity: 0;
        }
        @media (prefers-reduced-motion: reduce) {
            .status-dot,
            .status-dot::after {
                animation: none;
            }
        }
        .dashboard-content {
            position: relative;
            transition: opacity 0.25s ease;
        }
        .dashboard-content.dashboard-loading {
            opacity: 0.92;
        }
        .dashboard-clickable .click-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #0d6efd;
        }
        .dashboard-clickable .click-indicator i {
            font-size: 11px;
        }
        .metric-count {
            min-width: 90px;
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
        }
        .metric-count .metric-icon {
            color: #0d6efd;
            font-size: 12px;
        }
        .metric-count .metric-icon-placeholder {
            width: 12px;
            height: 12px;
            display: inline-block;
        }
        .front-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .front-dot-cell {
            vertical-align: middle;
            padding-left: 6px;
            padding-right: 10px;
            text-align: center;
            width: 32px;
        }
        .front-dot-wrap {
            width: 12px;
            height: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .card-body.p-0 .table tbody>tr>td.front-dot-cell,
        .card-body.p-0 .table thead>tr>th.front-dot-cell {
            padding-left: 6px !important;
            padding-right: 10px !important;
            text-align: center;
        }
        .front-dot--on {
            background: #28a745;
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.15);
        }
        .front-dot--off {
            background: #adb5bd;
            box-shadow: 0 0 0 2px rgba(173, 181, 189, 0.15);
        }
        .member-list-table th,
        .member-list-table td {
            font-size: 12px;
            white-space: nowrap;
        }
        .modal-member-list .modal-dialog {
            max-width: 1140px;
        }
        .modal-member-list .modal-content {
            height: 82vh;
            max-height: 82vh;
            display: flex;
            flex-direction: column;
        }
        .modal-member-list .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .member-list-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }
        .member-list-toolbar .member-search {
            width: 280px;
            max-width: 100%;
        }
        .member-list-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            border: 1px solid #e9ecef;
            border-radius: 4px;
        }
        .top-risky-detail-scroll {
            flex: 1 1 auto;
            min-height: 0;
            max-height: 62vh;
            overflow: auto;
            position: relative;
            border: 1px solid #e9ecef;
            border-radius: 4px;
        }
        .top-risky-detail-scroll .member-list-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f8f9fa;
            box-shadow: inset 0 -1px 0 #dee2e6;
        }
        .top-risky-detail-sortable {
            cursor: pointer;
            user-select: none;
        }
        .top-risky-detail-empty {
            padding: 16px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
        }
        @media (max-width: 767.98px) {
            .lotto-section-grid {
                grid-template-columns: 1fr;
            }
            .dashboard-equal-row {
                margin-left: -4px;
                margin-right: -4px;
            }
            .dashboard-equal-row > [class*="col-"] {
                flex: 0 0 50%;
                max-width: 50%;
                padding-left: 4px;
                padding-right: 4px;
            }
            .dashboard-equal-row .kpi-card {
                margin-bottom: 10px;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                grid-template-areas:
                    "title value"
                    "meta meta";
                column-gap: 10px;
                row-gap: 4px;
                align-items: start;
                min-height: 100%;
            }
            .dashboard-equal-row .kpi-title {
                grid-area: title;
                margin-bottom: 0;
                padding-top: 2px;
            }
            .dashboard-equal-row .kpi-value {
                grid-area: value;
                margin-top: 0;
                text-align: right;
                justify-self: end;
                align-self: center;
                min-width: 92px;
            }
            .dashboard-equal-row .kpi-sub {
                grid-column: 1 / -1;
                line-height: 1.45;
            }
            .modal-member-list .modal-content {
                height: 86vh;
                max-height: 86vh;
            }
            .member-list-toolbar .member-search {
                width: 100%;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('/vendor/chart.js/Chart.js') }}"></script>
    <script src="{{ asset('vendor/daterangepicker/daterangepicker.js') }}"></script>
    <script>
        const lottoRecentMarketOptions = @json($lottoRecentMarketOptions ?? []);
    </script>
    <script type="text/x-template" id="admin-dashboard-template">
        @php
            $permDeposit = bouncer()->hasPermission('dashboard.deposit');
            $permWithdraw = bouncer()->hasPermission('dashboard.withdraw');
            $permBonus = bouncer()->hasPermission('dashboard.bonus');
            $permBalance = bouncer()->hasPermission('dashboard.balance');
            $permRegis = bouncer()->hasPermission('dashboard.regis');
            $permRegisterToday = bouncer()->hasPermission('dashboard.register-today');
            $permRegisterDeposit = bouncer()->hasPermission('dashboard.register-deposit');
            $permRegisterNotDeposit = bouncer()->hasPermission('dashboard.register-not-deposit');
            $permSetDeposit = bouncer()->hasPermission('dashboard.setdeposit');
            $permSetWithdraw = bouncer()->hasPermission('dashboard.setwithdraw');
            $permBankIn = bouncer()->hasPermission('dashboard.bankin');
            $permBankOut = bouncer()->hasPermission('dashboard.bankout');
            $permIncome = bouncer()->hasPermission('dashboard.income');
            $permTopup = bouncer()->hasPermission('dashboard.topup');
            $permDepositWait = bouncer()->hasPermission('dashboard.deposit_wait');
            $permSummary = bouncer()->hasPermission('dashboard.summary');
            $permAdjust = bouncer()->hasPermission('dashboard.adjust');

            $permRegisterBlock = $permRegis || $permRegisterToday || $permRegisterDeposit || $permRegisterNotDeposit;
            $permStaffAdjust = $permSetDeposit || $permSetWithdraw;
            $permMoney = $permIncome || $permTopup || $permDeposit || $permWithdraw || $permBonus || $permBalance;
            $permAlerts = bouncer()->hasPermission('dashboard.alert');
            $adminUser = auth()->guard('admin')->user();
            $canSummaryResync = $adminUser && (int) ($adminUser->role_id ?? 0) === 1;
            $freeCreditOpen = ($webconfig->freecredit_open ?? 'N') === 'Y';
        @endphp
        <section class="content text-xs" id="dashboard-app">
            <div class="container-fluid">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <div class="h4 mb-0">ภาพรวมระบบ (เรียลไทม์)</div>
                        <div class="text-muted">@{{ rangeLabel }}</div>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" @click="refreshAll({ reason: 'manual', skeleton: false })">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        @if($canSummaryResync)
                            <button class="btn btn-outline-primary" :disabled="ui.syncing" @click="runSummarySync">
                                <i class="fas fa-redo-alt"></i> ซิงค์ข้อมูลใหม่
                            </button>
                        @endif
                    </div>
                </div>

                <div class="card card-outline card-primary">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4">
                                <label class="mb-1">Date Range</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="far fa-clock"></i></span>
                                    </div>
                                    <input type="text" class="form-control form-control-sm" id="search_date" readonly>
                                </div>
                                <input type="hidden" id="startDate">
                                <input type="hidden" id="endDate">
                            </div>
                            <div class="col-lg-8">
                                <label class="mb-1">Preset</label>
                                <div class="btn-group btn-group-sm flex-wrap">
                                    <button type="button" class="btn btn-outline-secondary" @click="applyPreset('today')">วันนี้</button>
                                    <button type="button" class="btn btn-outline-secondary" @click="applyPreset('yesterday')">เมื่อวาน</button>
                                    <button type="button" class="btn btn-outline-secondary" @click="applyPreset('7d')">7 วัน</button>
                                    <button type="button" class="btn btn-outline-secondary" @click="applyPreset('30d')">30 วัน</button>
                                    <button type="button" class="btn btn-outline-primary" @click="openCustom">กำหนดเอง</button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-lg-4 mb-2 mb-lg-0">
                                <online-slot ref="online"></online-slot>
                            </div>
                            <div class="col-lg-8">
                                <div class="dashboard-status-placeholder">
                                    <transition name="dashboard-fade">
                                        <div v-if="ui.loading.active" class="dashboard-status-slot w-100" role="status" aria-live="polite">
                                            <div class="dashboard-status-bar">
                                                <span class="dashboard-status-icon dashboard-status-icon--spinner" aria-hidden="true">⏳</span>
                                                <span class="dashboard-status-text">@{{ ui.loading.message }}</span>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="dashboard-content" :class="{ 'dashboard-loading': ui.loading.active }">
                    <div class="row dashboard-equal-row">
                        @if($permDeposit)
                            <div class="col-12 col-md-6 col-xl-2">
                                <div class="kpi-card metric-deposit">
                                    <div class="kpi-title"><span class="status-dot"></span> ยอดฝาก</div>
                                    <div class="kpi-value kpi-value--deposit">@{{ uiAnimatedAmount('deposit') }}</div>
                                    <div class="kpi-sub">จำนวนรายการ: @{{ uiAnimatedCount('deposit_count') }}</div>
                                    <div class="kpi-sub">จำนวนยูสที่ฝาก: @{{ uiAnimatedCount('deposit_users') }}</div>
                                    <div class="kpi-sub">ฝากสำเร็จ: @{{ uiAnimatedAmount('deposit_success_amount') }}</div>
                                    <div class="kpi-sub">ฝากยังไม่สำเร็จ: @{{ uiAnimatedAmount('deposit_problem_amount') }}</div>
                                </div>
                            </div>
                        @endif

                        @if($permWithdraw)
                            <div class="col-12 col-md-6 col-xl-2">
                                <div class="kpi-card metric-withdraw">
                                    <div class="kpi-title"><span class="status-dot"></span> ยอดถอน</div>
                                    <div class="kpi-value kpi-value--withdraw">@{{ uiAnimatedAmount('withdraw') }}</div>
                                    <div class="kpi-sub">จำนวนรายการ: @{{ uiAnimatedCount('withdraw_count') }}</div>
                                    <div class="kpi-sub">จำนวนยูสที่ถอน: @{{ uiAnimatedCount('withdraw_users') }}</div>
                                    <div class="kpi-sub">รออนุมัติ: @{{ uiValue(summary.withdraw.pending.amount, '0.00') }} <span class="kpi-paren">(@{{ uiAnimatedCount('withdraw_pending_count') }})</span></div>
                                    @if($freeCreditOpen)
                                        <div class="kpi-sub">เครดิต: @{{ (summary.withdraw && summary.withdraw.main && summary.withdraw.main.amount) ? summary.withdraw.main.amount : '0.00' }} <span class="kpi-paren">(@{{ (summary.withdraw && summary.withdraw.main && summary.withdraw.main.count) ? summary.withdraw.main.count : 0 }})</span></div>
                                        <div class="kpi-sub">เครดิตฟรี: @{{ (summary.withdraw && summary.withdraw.free && summary.withdraw.free.amount) ? summary.withdraw.free.amount : '0.00' }} <span class="kpi-paren">(@{{ (summary.withdraw && summary.withdraw.free && summary.withdraw.free.count) ? summary.withdraw.free.count : 0 }})</span></div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($permBonus)
                            <div class="col-12 col-md-6 col-xl-2">
                                <div class="kpi-card metric-bonus">
                                    <div class="kpi-title"><span class="status-dot"></span> ยอดโบนัส</div>
                                    <div class="kpi-value">@{{ uiAnimatedAmount('bonus') }}</div>
                                    <div class="kpi-sub">จำนวนรายการ: @{{ uiCount(summary.bonus.count) }}</div>
                                    <div class="kpi-sub">โบนัสฝาก: @{{ uiAnimatedAmount('bonus_deposit_amount') }} <span class="kpi-paren">(@{{ uiCount(summary.bonus.deposit.count) }})</span></div>
                                    <div class="kpi-sub">กิจกรรม: @{{ uiAnimatedAmount('bonus_activity_amount') }} <span class="kpi-paren">(@{{ uiCount(summary.bonus.activity.count) }})</span></div>
                                </div>
                            </div>
                        @endif

                        @if($permBalance)
                            <div class="col-12 col-md-6 col-xl-2">
                                <div class="kpi-card metric-net">
                                    <div class="kpi-title"><span class="status-dot"></span> คงเหลือสุทธิ</div>
                                    <div class="kpi-value" :class="Number(summary.net.amount_raw || 0) >= 0 ? 'kpi-value--positive' : 'kpi-value--negative'">@{{ uiAnimatedAmount('net') }}</div>
                                    <div class="kpi-sub">ฝากสำเร็จ - ถอนสำเร็จ</div>
                                    <div class="kpi-sub">เทียบช่วงก่อนหน้า: @{{ uiAnimatedPercent('net_change_pct') }}</div>
                                </div>
                            </div>
                        @endif

                        @if($permRegis)
                            <div class="col-12 col-md-6 col-xl-2">
                                <div class="kpi-card metric-register">
                                    <div class="kpi-title"><span class="status-dot"></span> สมัครสมาชิก</div>
                                    <div class="kpi-value">@{{ uiAnimatedCount('register') }}</div>
                                    <div class="kpi-sub">สมัครตรง: @{{ uiAnimatedCount('register_normal') }}</div>
                                    <div class="kpi-sub">การแนะนำ: @{{ uiAnimatedCount('register_referral') }}</div>
                                    <div class="kpi-sub">แคมเปญ: @{{ uiAnimatedCount('register_campaign') }}</div>
                                </div>
                            </div>
                        @endif

                        @if($permRegisterDeposit)
                            <div class="col-12 col-md-6 col-xl-2">
                                <div class="kpi-card metric-ftd">
                                    <div class="kpi-title"><span class="status-dot"></span> ฝากครั้งแรก</div>
                                    <div class="kpi-value">@{{ uiAnimatedCount('ftd') }}</div>
                                    <div class="kpi-sub">(ฝากครั้งแรก / สมัครทั้งหมด x 100)</div>
                                    <div class="kpi-sub">คิดเป็น: @{{ uiAnimatedPercent('ftd_rate') }}</div>
                                    <div class="kpi-sub">&nbsp;</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if($permSummary || $permBalance)
                        <div class="row mt-2">
                            <div class="col-12">
                                <div class="card card-outline lotto-section-card">
                                    <div class="card-header py-2">
                                        <div class="card-title"><span class="status-dot"></span> Lotto Section</div>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="lotto-section-grid">
                                            <div class="lotto-block">
                                                <div class="lotto-block-title">Lotto Cash</div>
                                                <div class="lotto-block-main" :class="Number(summary.lotto.net_cash_raw || 0) >= 0 ? 'kpi-value--positive' : 'kpi-value--negative'">@{{ uiAnimatedAmount('lotto_net_cash') }}</div>
                                                <div class="lotto-block-line"><span>ยอดแทง</span><strong>@{{ uiAnimatedAmount('lotto_sales_cash') }}</strong></div>
                                                <div class="lotto-block-line"><span>จ่ายรางวัล</span><strong>@{{ uiAnimatedAmount('lotto_payout_cash') }}</strong></div>
                                                <div class="lotto-block-line"><span>เงินคืน</span><strong>@{{ uiAnimatedAmount('lotto_refund_cash') }}</strong></div>
                                            </div>
                                            <div class="lotto-block">
                                                <div class="lotto-block-title">Lotto Product</div>
                                                <div class="lotto-block-main">@{{ uiValue(summary.lotto_product.total_sales, '0.00') }}</div>
                                                <div class="lotto-block-line"><span>ยอดจ่ายรวม</span><strong>@{{ uiValue(summary.lotto_product.total_payout, '0.00') }}</strong></div>
                                                <div class="lotto-block-line"><span>จำนวนโพย</span><strong>@{{ uiCount(summary.lotto_product.total_tickets) }}</strong></div>
                                                <div class="lotto-block-line"><span>จำนวนผู้เล่น</span><strong>@{{ uiCount(summary.lotto_product.total_players) }}</strong></div>
                                                <div class="lotto-block-line"><span>รอตัดสิน/ตัดสินแล้ว</span><strong>@{{ uiCount(summary.lotto_product.pending_tickets) }} / @{{ uiCount(summary.lotto_product.settled_tickets) }}</strong></div>
                                            </div>
                                            <div class="lotto-block">
                                                <div class="lotto-block-title">Lotto Risk (ความเสี่ยงหวย)</div>
                                                <div class="lotto-block-main">@{{ uiValue(summary.lotto_risk.max_risk_per_number || summary.lotto_risk.liability_max, '0.00') }}</div>
                                                <div class="lotto-block-line"><span>ยอดเสี่ยงสูงสุดต่อเลข</span><strong>@{{ uiValue(summary.lotto_risk.max_risk_per_number || summary.lotto_risk.liability_max, '0.00') }}</strong></div>
                                                <div class="lotto-block-line"><span>เลขเสี่ยงสูงสุด</span><strong>@{{ uiValue(summary.lotto_risk.max_risk_number, '-') }}</strong></div>
                                                <div class="lotto-block-line"><span>ยอดจ่ายถ้าถูกทั้งหมด</span><strong>@{{ uiValue(summary.lotto_risk.total_exposure || summary.lotto_risk.exposure_total, '0.00') }}</strong></div>
                                                <div v-if="!summary.lotto_risk.liability_total_same_as_exposure" class="lotto-block-line"><span>ยอดความเสี่ยงรวมทุกเลข</span><strong>@{{ uiValue(summary.lotto_risk.liability_total, '0.00') }}</strong></div>
                                                <div class="lotto-block-line"><span>จำนวนตลาด/งวด/เลขที่ติดตาม</span><strong>@{{ uiCount(summary.lotto_risk.tracked_market_count || summary.lotto_risk.markets) }} / @{{ uiCount(summary.lotto_risk.tracked_round_count || summary.lotto_risk.rounds) }} / @{{ uiCount(summary.lotto_risk.tracked_number_count || summary.lotto_risk.numbers) }}</strong></div>
                                                <div class="lotto-block-line"><span>เทียบงวดก่อนหน้า (Risk)</span><strong>@{{ uiValue(summary.lotto_risk_trend.risk_delta, '0.00') }} (@{{ uiValue(summary.lotto_risk_trend.risk_direction, '-') }})</strong></div>
                                                <div class="lotto-block-line"><span>เทียบงวดก่อนหน้า (Sales)</span><strong>@{{ uiValue(summary.lotto_risk_trend.sales_delta, '0.00') }} (@{{ uiValue(summary.lotto_risk_trend.sales_direction, '-') }})</strong></div>
                                                <div v-if="summary.lotto_risk_alerts.length > 0" class="lotto-block-line text-danger"><span>Risk Alert</span><strong>@{{ uiValue(summary.lotto_risk_alerts[0].message, '-') }}</strong></div>
                                            </div>
                                        </div>
                                        <div class="lotto-recent-wrap mt-2">
                                            <div class="lotto-recent-head">
                                                <div class="lotto-recent-title">เลขเสี่ยง (Top 10 Risky Numbers)</div>
                                                <ul class="nav nav-pills nav-sm">
                                                    <li class="nav-item">
                                                        <a
                                                            href="#"
                                                            class="nav-link"
                                                            :class="{ active: lottoRiskTab === 'today' }"
                                                            @click.prevent="switchLottoRiskTab('today')"
                                                        >
                                                            วันนี้
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a
                                                            href="#"
                                                            class="nav-link"
                                                            :class="{ active: lottoRiskTab === 'highest' }"
                                                            @click.prevent="switchLottoRiskTab('highest')"
                                                        >
                                                            เสี่ยงสูงสุด
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="text-muted small mb-2">
                                                @{{ lottoRiskTab === 'today' ? 'ยอดเสี่ยงวันนี้ หรือช่วงวันที่ที่เลือก' : 'ยอดเสี่ยงสูงสุดแบบเดิม' }}
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover lotto-recent-table">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>เลข</th>
                                                            <th>ประเภท</th>
                                                            <th class="text-right">ยอดแทง</th>
                                                            <th class="text-right">ความเสี่ยง</th>
                                                            <th class="text-right" title="จำนวนตลาดที่เลขนี้ติดความเสี่ยงในช่วงวันที่ที่เลือก">จำนวนตลาด</th>
                                                            <th class="text-right" title="จำนวนรอบออกรางวัล (draw) ที่เลขนี้ติดความเสี่ยงในช่วงวันที่ที่เลือก">จำนวนงวด (เสี่ยง)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="(row, idx) in activeLottoRiskRows()" :key="'top-risky-' + lottoRiskTab + '-' + idx + '-' + row.number + '-' + row.bet_type">
                                                            <td>@{{ uiValue(row.number, '-') }}</td>
                                                            <td>@{{ formatLottoRiskBetType(row.bet_type) }}</td>
                                                            <td class="text-right">@{{ uiValue(row.stake_total, '0.00') }}</td>
                                                            <td class="text-right">@{{ uiValue(row.exposure_total, '0.00') }}</td>
                                                            <td class="text-right">
                                                                <a href="#" class="dashboard-clickable" @click.prevent="openTopRiskyDetail('markets', row)">@{{ uiCount(row.market_count) }}</a>
                                                            </td>
                                                            <td class="text-right">
                                                                <a href="#" class="dashboard-clickable" @click.prevent="openTopRiskyDetail('rounds', row)">@{{ uiCount(row.round_count) }}</a>
                                                            </td>
                                                        </tr>
                                                        <tr v-if="activeLottoRiskRows().length === 0">
                                                            <td colspan="6" class="text-center text-muted">@{{ lottoRiskTab === 'today' ? 'ไม่มีข้อมูลความเสี่ยงในช่วงวันที่ที่เลือก' : 'ไม่มีข้อมูลเลขเสี่ยงสูงสุด' }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="lotto-recent-wrap mt-2">
                                            <div class="lotto-recent-head">
                                                <div class="lotto-recent-title">สรุปรายการโพยตามประเภท (Lotto Bet Type Insights)</div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover lotto-recent-table">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>ประเภท</th>
                                                            <th class="text-right">รายการ</th>
                                                            <th class="text-right">ยอดรวม</th>
                                                            <th class="text-right">ผู้เล่น</th>
                                                            <th>เลขแทงสูงสุด</th>
                                                            <th class="text-right">ยอดเลขแทงสูงสุด</th>
                                                            <th class="text-right">Max Risk (per number)</th>
                                                            <th>เลขเสี่ยงสูงสุด</th>
                                                            <th class="text-right">มูลค่าเสี่ยงสูงสุด</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="row in summary.lotto_bet_type_insights" :key="'lotto-insight-' + row.bet_type">
                                                            <td>@{{ uiValue(row.label, '-') }}</td>
                                                            <td class="text-right">@{{ uiCount(row.item_count) }}</td>
                                                            <td class="text-right">@{{ uiValue(row.total_amount, '0.00') }}</td>
                                                            <td class="text-right">@{{ row.unique_players === null ? '-' : uiCount(row.unique_players) }}</td>
                                                            <td>@{{ uiValue(row.hottest_number || row.top_number, '-') }}</td>
                                                            <td class="text-right">@{{ uiValue(row.hottest_number_amount || row.top_number_amount, '0.00') }}</td>
                                                            <td class="text-right">@{{ uiValue(row.risk_exposure_total, '0.00') }}</td>
                                                            <td>@{{ uiValue(row.max_risk_number, '-') }}</td>
                                                            <td class="text-right">@{{ uiValue(row.max_risk_value, '0.00') }}</td>
                                                        </tr>
                                                        <tr v-if="summary.lotto_bet_type_insights.length === 0">
                                                            <td colspan="9" class="text-center text-muted">ไม่มีข้อมูลสรุปตามประเภทในช่วงวันที่ที่เลือก</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="lotto-recent-wrap mt-2">
                                            <div class="lotto-recent-head">
                                                <div class="lotto-recent-title">ผู้เล่นเสี่ยงสูงสุด (Top Risk Users)</div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover lotto-recent-table">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th class="text-right">อันดับ</th>
                                                            <th>สมาชิก</th>
                                                            <th class="text-right">Max Risk (per user-number)</th>
                                                            <th class="text-right">สัดส่วนความเสี่ยง</th>
                                                            <th>ตลาดหลัก</th>
                                                            <th class="text-right">จำนวนโพย</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="row in summary.lotto_top_risk_users" :key="'lotto-risk-user-' + row.member_id">
                                                            <td class="text-right">@{{ uiCount(row.rank) }}</td>
                                                            <td>@{{ uiValue(row.member_username, '-') }}</td>
                                                            <td class="text-right">@{{ uiValue(row.total_exposure, '0.00') }}</td>
                                                            <td class="text-right">@{{ uiValue(row.contribution_percent, 0).toFixed ? uiValue(row.contribution_percent, 0).toFixed(2) : uiValue(row.contribution_percent, '0.00') }}%</td>
                                                            <td>@{{ uiValue(row.main_market, '-') }}</td>
                                                            <td class="text-right">@{{ uiCount(row.bet_count) }}</td>
                                                        </tr>
                                                        <tr v-if="summary.lotto_top_risk_users.length === 0">
                                                            <td colspan="6" class="text-center text-muted">ไม่มีข้อมูลผู้เล่นเสี่ยงสูงสุดในช่วงวันที่ที่เลือก</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="lotto-recent-wrap">
                                            <div class="lotto-recent-head">
                                                <div class="lotto-recent-title">รายการโพยล่าสุด (Recent Lotto Bets)</div>
                                                <div class="lotto-recent-actions">
                                                    <div class="lotto-recent-count">@{{ uiCount(activity.lotto_recent_bets.length) }} / 20</div>
                                                    <select ref="lottoRecentMarketSelect" class="form-control form-control-sm lotto-recent-filter">
                                                        <option value="">ทั้งหมดทุกรายการหวย</option>
                                                        <optgroup v-for="group in lottoRecentMarketOptions" :key="'lotto-opt-' + group.label" :label="group.label">
                                                            <option v-for="market in group.options" :key="'lotto-opt-' + market.value" :value="market.value">
                                                                @{{ market.text }}
                                                            </option>
                                                        </optgroup>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover lotto-recent-table">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>เวลา</th>
                                                            <th>สมาชิก</th>
                                                            <th>กลุ่ม/รายการ</th>
                                                            <th>ประเภท</th>
                                                            <th class="text-right">ยอดแทง</th>
                                                            <th class="text-right">ยอดจ่ายถ้าถูกทั้งหมด</th>
                                                            <th class="text-right">ยอดจ่ายจริง</th>
                                                            <th class="text-right">กำไร/ขาดทุนสุทธิ</th>
                                                            <th class="text-center">สถานะ</th>
                                                            <th class="text-right">ยอดถูก</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="row in activity.lotto_recent_bets" :key="'lotto-recent-' + row.ticket_id">
                                                            <td>@{{ uiValue(row.bet_at, '-') }}</td>
                                                            <td>@{{ uiValue(row.member_username, '-') }}</td>
                                                            <td>@{{ uiValue(row.group_name, '-') }} / @{{ uiValue(row.market_name, '-') }}</td>
                                                            <td class="summary-col" :title="uiValue(row.bet_type_summary, '-')">@{{ uiValue(row.bet_type_summary, '-') }}</td>
                                                            <td class="text-right">@{{ uiValue(row.amount, '0.00') }}</td>
                                                            <td class="text-right">@{{ uiValue(row.potential_payout, '0.00') }}</td>
                                                            <td class="text-right">@{{ uiValue(row.actual_payout, '0.00') }}</td>
                                                            <td class="text-right">@{{ uiValue(row.net_result, '0.00') }}</td>
                                                            <td class="text-center">
                                                                <span class="badge"
                                                                      :class="{
                                                                        'badge-warning': row.status === 'pending',
                                                                        'badge-success': row.status === 'win',
                                                                        'badge-secondary': row.status === 'lose',
                                                                        'badge-danger': row.status === 'cancel'
                                                                      }">
                                                                    @{{ row.status === 'pending' ? 'รอผล' : (row.status === 'win' ? 'ถูกรางวัล' : (row.status === 'lose' ? 'ไม่ถูก' : 'คืนโพย')) }}
                                                                </span>
                                                            </td>
                                                            <td class="text-right">@{{ uiValue(row.win_amount, '0.00') }}</td>
                                                        </tr>
                                                        <tr v-if="activity.lotto_recent_bets.length === 0">
                                                            <td colspan="10" class="text-center text-muted">ไม่มีรายการแทงล่าสุด</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="row mt-2 dashboard-top-align-row">
                        @if($permSummary)
                            <div class="col-lg-4">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <div class="card-title"><span class="block-live-badge"><i class="fas fa-pen"></i>อัปเดต</span><span class="status-dot"></span> สมัครสมาชิก</div>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <span>สมัครทั้งหมด</span>
                                            <span class="metric-count">
                                        <strong>@{{ uiCount(conversion.register.total) }}</strong>
                                        <span class="metric-icon-placeholder"></span>
                                    </span>
                                        </div>
                                        <div class="d-flex justify-content-between dashboard-clickable" role="button" tabindex="0" title="คลิกเพื่อดูรายชื่อ" @click="openMemberList('register_deposit')">
                                            <span>สมัครแล้วฝาก</span>
                                            <span class="metric-count">
                                        <strong>@{{ uiCount(conversion.register.deposit) }}</strong>
                                        <i class="fas fa-search metric-icon"></i>
                                    </span>
                                        </div>
                                        <div class="d-flex justify-content-between dashboard-clickable" role="button" tabindex="0" title="คลิกเพื่อดูรายชื่อ" @click="openMemberList('register_repeat_deposit')">
                                            <span>สมัครแล้วฝากซ้ำ</span>
                                            <span class="metric-count">
                                        <strong>@{{ uiCount(conversion.register.repeat_deposit) }}</strong>
                                        <i class="fas fa-search metric-icon"></i>
                                    </span>
                                        </div>
                                        <div class="d-flex justify-content-between dashboard-clickable" role="button" tabindex="0" title="คลิกเพื่อดูรายชื่อ" @click="openMemberList('register_not_deposit')">
                                            <span>สมัครยังไม่ฝาก</span>
                                            <span class="metric-count">
                                        <strong>@{{ uiCount(conversion.register.not_deposit) }}</strong>
                                        <i class="fas fa-search metric-icon"></i>
                                    </span>
                                        </div>
                                        <div class="d-flex justify-content-between dashboard-clickable" role="button" tabindex="0" title="คลิกเพื่อดูรายชื่อ" @click="openMemberList('first_deposit')">
                                            <span>ฝากครั้งแรก</span>
                                            <span class="metric-count">
                                        <strong>@{{ uiCount(summary.first_deposit.count) }}</strong>
                                        <i class="fas fa-search metric-icon"></i>
                                    </span>
                                        </div>
                                        <div class="d-flex justify-content-between dashboard-clickable" role="button" tabindex="0" title="คลิกเพื่อดูรายชื่อ" @click="openMemberList('repeat_deposit')">
                                            <span>ฝากซ้ำ</span>
                                            <span class="metric-count">
                                        <strong>@{{ uiCount(funnel.funnel.repeat_deposit) }}</strong>
                                        <i class="fas fa-search metric-icon"></i>
                                    </span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>อัตราสมัครแล้วฝาก</span>
                                            <span class="metric-count">
                                        <strong>@{{ uiPercent(conversion.register.rate) }}</strong>
                                        <span class="metric-icon-placeholder"></span>
                                    </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if($permSummary)
                            <div class="col-lg-4">
                                <div class="card card-outline card-teal">
                                    <div class="card-header">
                                        <div class="card-title"><span class="block-live-badge"><i class="fas fa-pen"></i>อัปเดต</span><span class="status-dot"></span> เพื่อนชวนมา</div>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between dashboard-clickable" role="button" tabindex="0" title="คลิกเพื่อดูรายชื่อ" @click="openMemberList('referral_total')">
                                            <span>สมัครจากเพื่อนชวน</span>
                                            <span class="metric-count">
                                            <strong>@{{ uiCount(conversion.referral.total) }}</strong>
                                            <i class="fas fa-search metric-icon"></i>
                                        </span>
                                        </div>
                                        <div class="d-flex justify-content-between dashboard-clickable" role="button" tabindex="0" title="คลิกเพื่อดูรายชื่อ" @click="openMemberList('referral_deposit')">
                                            <span>เพื่อนชวนแล้วฝาก</span>
                                            <span class="metric-count">
                                            <strong>@{{ uiCount(conversion.referral.deposit) }}</strong>
                                            <i class="fas fa-search metric-icon"></i>
                                        </span>
                                        </div>
                                        <div class="d-flex justify-content-between dashboard-clickable" role="button" tabindex="0" title="คลิกเพื่อดูรายชื่อ" @click="openMemberList('referral_not_deposit')">
                                            <span>เพื่อนชวนยังไม่ฝาก</span>
                                            <span class="metric-count">
                                            <strong>@{{ uiCount(conversion.referral.not_deposit) }}</strong>
                                            <i class="fas fa-search metric-icon"></i>
                                        </span>
                                        </div>
                                        <div class="d-flex justify-content-between"><span>อัตราสมัครแล้วฝาก (แนะนำ)</span><strong>@{{ uiPercent(conversion.referral.rate) }}</strong></div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if($permAdjust)
                            <div class="col-lg-4">
                                <div class="card card-outline card-warning">
                                    <div class="card-header">
                                        <div class="card-title"><span class="block-live-badge"><i class="fas fa-pen"></i>อัปเดต</span><span class="status-dot"></span> ทีมงานปรับยอด</div>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between"><span>ทีมงานเพิ่มยอด</span><strong class="text-success">@{{ uiValue(conversion.staff.main.add, '-') }}</strong></div>
                                        <div class="d-flex justify-content-between"><span>ทีมงานลดยอด</span><strong class="text-danger">@{{ uiValue(conversion.staff.main.reduce, '-') }}</strong></div>
                                        <div class="d-flex justify-content-between"><span>ปรับยอดสุทธิ</span><strong>@{{ uiValue(conversion.staff.main.net, '-') }}</strong></div>
                                        <div class="d-flex justify-content-between"><span>จำนวนรายการ</span><strong>@{{ uiCount(conversion.staff.main.count) }}</strong></div>
                                        @if($freeCreditOpen)
                                            <div class="text-center text-muted my-1">-------ฟรีเครดิต---------</div>
                                            <div class="d-flex justify-content-between"><span>ทีมงานเพิ่มยอด</span><strong class="text-success">@{{ uiValue(conversion.staff.free.add, '-') }}</strong></div>
                                            <div class="d-flex justify-content-between"><span>ทีมงานลดยอด</span><strong class="text-danger">@{{ uiValue(conversion.staff.free.reduce, '-') }}</strong></div>
                                            <div class="d-flex justify-content-between"><span>ปรับยอดสุทธิ</span><strong>@{{ uiValue(conversion.staff.free.net, '-') }}</strong></div>
                                            <div class="d-flex justify-content-between"><span>จำนวนรายการ</span><strong>@{{ uiCount(conversion.staff.free.count) }}</strong></div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="row dashboard-equal-row">
                        @if($permSummary)
                            <div class="col-lg-8">
                                <div class="card card-outline card-primary">
                                    <div class="card-header d-flex align-items-center justify-content-between">
                                        <div class="card-title"><span class="block-live-badge"><i class="fas fa-pen"></i>อัปเดต</span><span class="status-dot"></span> กราฟเงิน</div>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary" :class="{'active': trendMode==='hour'}" @click="setTrendMode('hour')">รายชั่วโมง</button>
                                            <button type="button" class="btn btn-outline-secondary" :class="{'active': trendMode==='day'}" @click="setTrendMode('day')">รายวัน</button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="chart-money" height="120"></canvas>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if($permSummary)
                            <div class="col-lg-4">
                                <div class="card card-outline card-secondary">
                                    <div class="card-header">
                                        <div class="card-title"><span class="block-live-badge"><i class="fas fa-pen"></i>อัปเดต</span><span class="status-dot"></span> สรุปเงิน</div>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between"><span>ฝากเฉลี่ยต่อรายการ</span><strong>@{{ uiValue(summarySide.avg_deposit, '-') }}</strong></div>
                                        <div class="d-flex justify-content-between"><span>ถอนเฉลี่ยต่อรายการ</span><strong>@{{ uiValue(summarySide.avg_withdraw, '-') }}</strong></div>
                                        <div class="d-flex justify-content-between"><span>โบนัสต่อยอดฝาก</span><strong>@{{ uiPercent(summary.bonus.ratio) }}</strong></div>
                                        <div class="d-flex justify-content-between"><span>ยอดฝากถูกปฏิเสธ</span><strong>@{{ uiValue(summary.deposit.reject.amount, '-') }}</strong></div>
                                        <div class="d-flex justify-content-between"><span>ยอดฝากถูกลบ</span><strong>@{{ uiValue(summary.deposit.deleted.amount, '-') }}</strong></div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="row dashboard-top-align-row">
                        @if($permSummary)
                            <div class="col-lg-8">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <div class="card-title"><span class="block-live-badge"><i class="fas fa-pen"></i>อัปเดต</span><span class="status-dot"></span> เส้นทางการแปลงสมาชิก</div>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="chart-funnel" height="140"></canvas>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if($permSummary)
                            <div class="col-lg-4">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <div class="card-title"><span class="block-live-badge"><i class="fas fa-pen"></i>อัปเดต</span><span class="status-dot"></span> แหล่งที่มาของผู้สมัคร</div>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between"><span>สมัครตรง</span><strong>@{{ funnel.sources.direct }}</strong></div>
                                        <div class="d-flex justify-content-between"><span>แคมเปญ</span><strong>@{{ funnel.sources.campaign }}</strong></div>
                                        <div class="d-flex justify-content-between"><span>การแนะนำ</span><strong>@{{ funnel.sources.referral }}</strong></div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="row dashboard-activity-row align-items-start">
                        @if($permDeposit)
                            <div class="col-lg-6">
                                <div class="card card-outline card-success">
                                    <div class="card-header d-flex align-items-center justify-content-between">
                                        <div class="card-title"><span class="block-live-badge"><i class="fas fa-pen"></i>อัปเดต</span><span class="status-dot"></span> ฝากล่าสุด</div>
                                        <ul class="nav nav-pills dashboard-tabs" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link" :class="{active: depositTab === 'all'}" href="#" @click.prevent="depositTab = 'all'">ทั้งหมด</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" :class="{active: depositTab === 'manual'}" href="#" @click.prevent="depositTab = 'manual'">เพิ่มโดยทีมงาน</a>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive deposit-activity-scroll">
                                            <table class="table table-sm table-striped mb-0">
                                                <thead>
                                                <tr>
                                                    <th>วัน / เวลา</th>
                                                    <th>username</th>
                                                    <th>จำนวน</th>
                                                    <th>ต้นทาง</th>
                                                    <th>ปลายทาง</th>
                                                    <th v-text="depositTab === 'manual' ? 'ทีมงาน' : ''"></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr v-for="row in depositRows" :key="row.time + row.username + row.channel">
                                                    <td>@{{ row.time }}</td>
                                                    <td>@{{ row.username }}</td>
                                                    <td class="dashboard-amount dashboard-amount--deposit">@{{ row.amount ? ('+' + row.amount) : '-' }}</td>
                                                    <td>
                                                        <div class="bank-meta">
                                                            <img v-if="row.from_bank_logo" :src="row.from_bank_logo" class="bank-meta-logo" alt="from-bank">
                                                            <span class="bank-meta-account">@{{ row.from_account_no || '-' }}</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="bank-meta">
                                                            <img v-if="row.to_bank_logo" :src="row.to_bank_logo" class="bank-meta-logo" alt="to-bank">
                                                            <span class="bank-meta-account">@{{ row.to_account_no || '-' }}</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <template v-if="depositTab === 'manual'">
                                                            <span class="text-muted">@{{ row.staff || '-' }}</span>
                                                        </template>
                                                        <template v-else>
                                                            <span class="status-chip">
                                                                <span class="status-light" :class="row.status === 'สำเร็จ' ? 'status-light--success' : 'status-light--waiting'"></span>
                                                            </span>
                                                        </template>
                                                    </td>
                                                </tr>
                                                <tr v-if="depositRows.length === 0">
                                                    <td colspan="6" class="text-center text-muted">ไม่มีข้อมูล</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if($permWithdraw)
                            <div class="col-lg-6">
                                <div class="card card-outline card-danger">
                                    <div class="card-header"><div class="card-title"><span class="block-live-badge"><i class="fas fa-pen"></i>อัปเดต</span><span class="status-dot"></span> ถอนล่าสุด</div></div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0">
                                                <thead>
                                                <tr>
                                                    <th>วัน / เวลา</th>
                                                    <th>username</th>
                                                    <th>จำนวน</th>
                                                    <th>ต้นทาง</th>
                                                    <th>ปลายทาง</th>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr v-for="row in activity.withdraws" :key="row.time + row.username">
                                                    <td>@{{ row.time }}</td>
                                                    <td>@{{ row.username }}</td>
                                                    <td class="dashboard-amount dashboard-amount--withdraw">@{{ row.amount ? ('-' + row.amount) : '-' }}</td>
                                                    <td>
                                                        <div class="bank-meta">
                                                            <img v-if="row.from_bank_logo" :src="row.from_bank_logo" class="bank-meta-logo" alt="from-bank">
                                                            <span class="bank-meta-account">@{{ row.from_account_no || '-' }}</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="bank-meta">
                                                            <img v-if="row.to_bank_logo" :src="row.to_bank_logo" class="bank-meta-logo" alt="to-bank">
                                                            <span class="bank-meta-account">@{{ row.to_account_no || '-' }}</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="status-chip">
                                                            <span class="status-light" :class="row.status === 'สำเร็จ' ? 'status-light--success' : 'status-light--waiting'"></span>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr v-if="activity.withdraws.length === 0">
                                                    <td colspan="6" class="text-center text-muted">ไม่มีข้อมูล</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if($permRegis)
                            <div class="col-lg-6">
                                <div class="card card-outline card-info">
                                    <div class="card-header"><div class="card-title"><span class="block-live-badge"><i class="fas fa-pen"></i>อัปเดต</span><span class="status-dot"></span> สมัครล่าสุด</div></div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0 dashboard-table">
                                                <thead>
                                                <tr>
                                                    <th>วันที่</th>
                                                    <th>username</th>
                                                    <th>ที่มา</th>
                                                    <th>สถานะฝาก</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr v-for="row in activity.registers" :key="row.time + row.username">
                                                    <td>@{{ row.time }}</td>
                                                    <td>@{{ row.username }}</td>
                                                    <td>@{{ row.source }}</td>
                                                    <td>@{{ row.status }}</td>
                                                </tr>
                                                <tr v-if="activity.registers.length === 0">
                                                    <td colspan="4" class="text-center text-muted">ไม่มีข้อมูล</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if($permAdjust)
                            <div class="col-lg-6">
                                <div class="card card-outline card-warning">
                                    <div class="card-header"><div class="card-title"><span class="block-live-badge"><i class="fas fa-pen"></i>อัปเดต</span><span class="status-dot"></span> ทีมงานปรับยอดล่าสุด</div></div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0">
                                                <thead>
                                                <tr>
                                                    <th>วัน / เวลา</th>
                                                    <th>สมาชิก</th>
                                                    <th>ถูกดำเนินการ</th>
                                                    <th>จำนวนเงิน</th>
                                                    <th>โดยทีมงาน</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr v-for="row in activity.staff" :key="row.time + row.member">
                                                    <td>@{{ row.time }}</td>
                                                    <td>@{{ row.member }}</td>
                                                    <td>@{{ row.type }}</td>
                                                    <td>@{{ row.amount }}</td>
                                                    <td>@{{ row.staff }}</td>
                                                </tr>
                                                <tr v-if="activity.staff.length === 0">
                                                    <td colspan="5" class="text-center text-muted">ไม่มีข้อมูล</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="row dashboard-activity-row align-items-start">
                        @if($permBankIn)
                            <div class="col-lg-6">
                                <div class="card card-outline card-primary">
                                    <div class="card-header"><div class="card-title">บัญชีเงินเข้า</div></div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0 dashboard-table">
                                                <thead>
                                                <tr>
                                                    <th class="text-center front-dot-cell" style="width: 32px;"></th>
                                                    <th>ธนาคาร</th>
                                                    <th>ชื่อบัญชี</th>
                                                    <th>เลขที่บัญชี</th>
                                                    <th class="text-right">ยอดเงิน</th>
{{--                                                    <th class="text-center">API Refresh</th>--}}
                                                    <th class="text-center">อัพเดทเมื่อ</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr v-for="row in bank.in" :key="row.acc_no + row.date_update">
                                                    <td class="front-dot-cell">
                                                    <span class="front-dot-wrap">
                                                        <span class="front-dot" :class="row.front_display === 'Y' ? 'front-dot--on' : 'front-dot--off'"></span>
                                                    </span>
                                                    </td>
                                                    <td v-html="row.bank"></td>
                                                    <td>@{{ row.acc_name }}</td>
                                                    <td>@{{ row.acc_no }}</td>
                                                    <td class="text-right">@{{ row.balance }}</td>
{{--                                                    <td class="text-center">@{{ row.status }}</td>--}}
                                                    <td class="text-center">@{{ row.date_update }}</td>
                                                </tr>
                                                <tr v-if="bank.in.length === 0">
                                                    <td colspan="7" class="text-center text-muted">ไม่มีข้อมูล</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if($permBankOut)
                            <div class="col-lg-6">
                                <div class="card card-outline card-primary">
                                    <div class="card-header"><div class="card-title">บัญชีเงินออก</div></div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0 dashboard-table">
                                                <thead>
                                                <tr>
                                                    <th>ธนาคาร</th>
                                                    <th>ชื่อบัญชี</th>
                                                    <th>เลขที่บัญชี</th>
                                                    <th class="text-right">ยอดเงิน</th>
                                                    <th class="text-center">อัพเดทเมื่อ</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr v-for="row in bank.out" :key="row.acc_no + row.date_update">
                                                    <td v-html="row.bank"></td>
                                                    <td>@{{ row.acc_name }}</td>
                                                    <td>@{{ row.acc_no }}</td>
                                                    <td class="text-right">@{{ row.balance }}</td>
                                                    <td class="text-center">@{{ row.date_update }}</td>
                                                </tr>
                                                <tr v-if="bank.out.length === 0">
                                                    <td colspan="5" class="text-center text-muted">ไม่มีข้อมูล</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="row dashboard-activity-row align-items-start">
                        <div class="col-lg-6">
                            <div class="card card-outline card-info">
                                <div class="card-header"><div class="card-title">Admin Login</div></div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped mb-0 dashboard-table">
                                            <thead>
                                            <tr>
                                                <th>Admin</th>
                                                <th class="text-center">วัน-เวลา</th>
                                                <th class="text-center">IP</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr v-for="row in adminLogs.login" :key="row.user_name + row.date_update">
                                                <td>@{{ row.user_name }}</td>
                                                <td class="text-center">@{{ row.date_update }}</td>
                                                <td class="text-center">@{{ row.ip }}</td>
                                            </tr>
                                            <tr v-if="adminLogs.login.length === 0">
                                                <td colspan="3" class="text-center text-muted">ไม่มีข้อมูล</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card card-outline card-info">
                                <div class="card-header"><div class="card-title">Admin Logout</div></div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped mb-0 dashboard-table">
                                            <thead>
                                            <tr>
                                                <th>Admin</th>
                                                <th class="text-center">วัน-เวลา</th>
                                                <th class="text-center">IP</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr v-for="row in adminLogs.logout" :key="row.user_name + row.date_update">
                                                <td>@{{ row.user_name }}</td>
                                                <td class="text-center">@{{ row.date_update }}</td>
                                                <td class="text-center">@{{ row.ip }}</td>
                                            </tr>
                                            <tr v-if="adminLogs.logout.length === 0">
                                                <td colspan="3" class="text-center text-muted">ไม่มีข้อมูล</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <member-list-modal
                        :type="memberList.type"
                        :title="memberList.title"
                        :items="memberList.items"
                        :total="memberList.total"
                        :limit="memberList.limit"
                        :loading="memberList.loading"
                ></member-list-modal>

                <top-risky-detail-modal
                        :title="topRiskyDetail.title"
                        :subtitle="topRiskyDetail.subtitle"
                        :items="topRiskyDetail.items"
                        :fields="topRiskyDetail.fields"
                ></top-risky-detail-modal>
            </div>
        </section>

    </script>

    <script type="text/x-template" id="top-risky-detail-modal-template">
        <div class="modal fade modal-member-list" id="top-risky-detail-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <div class="modal-title">@{{ title }}</div>
                            <small class="text-muted">@{{ subtitle }}</small>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="top-risky-detail-scroll">
                            <table class="table table-sm table-hover table-bordered mb-0 member-list-table">
                                <thead class="thead-light">
                                <tr>
                                    <th v-for="field in normalizedFields"
                                        :key="field.key"
                                        :class="[field.class || '', field.sortable ? 'top-risky-detail-sortable' : '']"
                                        @click="toggleSort(field)">
                                        @{{ field.label }}
                                        <span v-if="field.sortable">
                                            <span v-if="sortKey === field.key && sortDesc">▼</span>
                                            <span v-else-if="sortKey === field.key">▲</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="(item, index) in sortedItems" :key="'top-risky-row-' + index">
                                    <td v-for="field in normalizedFields"
                                        :key="field.key"
                                        :class="field.class || ''">
                                        @{{ item[field.key] }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <div v-if="sortedItems.length === 0" class="top-risky-detail-empty">ไม่มีข้อมูลที่เกี่ยวข้อง</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="text/x-template" id="online-slot-template">
        <div>
            <span class="online-status">
                <span class="status-dot"></span>
                <span>Online @{{ online.count }}</span>
                <span class="status-sep">|</span>
                <span class="status-sub">Last update @{{ online.updated_at }}</span>
            </span>
        </div>
    </script>

    <script type="text/x-template" id="member-list-modal-template">
        <div class="modal fade modal-member-list" id="member-list-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-title">@{{ title }}</div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="member-list-toolbar">
                            <div class="text-muted">แสดง @{{ filteredItems.length }} / @{{ total }} รายการ</div>
                            <input
                                    type="text"
                                    class="form-control form-control-sm member-search"
                                    v-model.trim="searchUsername"
                                    placeholder="ค้นหา user_name"
                            >
                            <div v-if="loading" class="text-muted">กำลังโหลด...</div>
                        </div>
                        <div class="table-responsive member-list-scroll">
                            <b-table
                                    striped
                                    hover
                                    small
                                    outlined
                                    show-empty
                                    head-variant="light"
                                    :items="filteredItems"
                                    :fields="memberListFields"
                                    class="mb-0 member-list-table"
                                    empty-text="ไม่มีข้อมูล"
                            ></b-table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script>
        function editdata(id, status, method) {
            window.app.editdata(id, status, method);
        }
    </script>

    <script type="module">
        import to from "./js/toPromise.js";

        const dashboardRoutes = {
            memberList: @json(
                \Illuminate\Support\Facades\Route::has('admin.dashboard.member-list')
                    ? route('admin.dashboard.member-list')
                    : ''
            ),
            syncSummary: @json(
                \Illuminate\Support\Facades\Route::has('admin.dashboard.sync-summary')
                    ? route('admin.dashboard.sync-summary')
                    : ''
            )
        };

        const dashboardRealtime = {
            webCode: @json($dashboardWebCode ?? ''),
            canSyncSummary: @json($canSummaryResync ?? false),
            debounceMs: 600,
            fallbackPollMs: 90000,
        };

        // ✅ helper: อ่านช่วงวันจาก hidden input (source of truth)
        function getInputValue(id) {
            const el = document.getElementById(id);
            return el && typeof el.value !== 'undefined' ? el.value : '';
        }
        function getDateRange() {
            return { start: getInputValue('startDate'), end: getInputValue('endDate') };
        }

        // ✅ helper: ให้ทุกกล่องรีโหลดทันทีเมื่อเลือกวัน
        function onDateChanged(handler) {
            window.addEventListener('dashboard:date-changed', handler);
        }
        function offDateChanged(handler) {
            window.removeEventListener('dashboard:date-changed', handler);
        }

        Vue.component('online-slot', {
            template: '#online-slot-template',
            data: function () {
                return {
                    online: {
                        count: '-',
                        updated_at: '-'
                    },
                    loading: false
                };
            },
            mounted() {
                this.reload();
            },
            methods: {
                normalizeResponse(res) {
                    if (!res || !res.data) return null;
                    if (typeof res.data === 'string') {
                        try {
                            const parsed = JSON.parse(res.data);
                            if (parsed && typeof parsed === 'object') {
                                if (Object.prototype.hasOwnProperty.call(parsed, 'data')) return parsed.data;
                                return parsed;
                            }
                        } catch (e) {
                            return null;
                        }
                    }
                    if (res.data && typeof res.data === 'object' && Object.prototype.hasOwnProperty.call(res.data, 'data')) {
                        return res.data.data;
                    }
                    return res.data;
                },
                formatNow() {
                    if (typeof moment !== 'undefined') {
                        return moment().format('HH:mm');
                    }
                    const d = new Date();
                    try {
                        return d.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
                    } catch (e) {
                        return d.toLocaleTimeString();
                    }
                },
                reload() {
                    if (!axios || typeof axios.post !== 'function') return;
                    this.loading = true;
                    axios.post("{{ route('admin.dashboard.loadsum') }}", { method: 'online' })
                        .then((res) => {
                            const data = this.normalizeResponse(res);
                            let value = '-';
                            if (data && Object.prototype.hasOwnProperty.call(data, 'sum')) {
                                value = data.sum;
                            } else if (typeof data !== 'undefined' && data !== null) {
                                value = data;
                            }
                            this.online.count = value;
                            this.online.updated_at = this.formatNow();
                        })
                        .catch(() => {})
                        .then(() => {
                            this.loading = false;
                        });
                }
            }
        });

        Vue.component('member-list-modal', {
            template: '#member-list-modal-template',
            props: {
                type: { type: String, default: '' },
                title: { type: String, default: '' },
                items: { type: Array, default: () => [] },
                total: { type: Number, default: 0 },
                limit: { type: Number, default: 0 },
                loading: { type: Boolean, default: false }
            },
            data: function () {
                return {
                    searchUsername: '',
                };
            },
            computed: {
                filteredItems: function () {
                    const keyword = (this.searchUsername || '').toString().trim().toLowerCase();
                    if (!keyword) {
                        return this.items || [];
                    }

                    return (this.items || []).filter((row) => {
                        const username = (row && row.username ? String(row.username) : '').toLowerCase();
                        return username.includes(keyword);
                    });
                },
                memberListFields: function () {
                    const buildFields = (keys) => keys.map((key) => this.memberListFieldMap[key]).filter(Boolean);

                    if (['register_deposit', 'register_repeat_deposit', 'repeat_deposit'].includes(this.type)) {
                        return buildFields([
                            'username', 'name', 'register_at', 'first_deposit_at', 'first_deposit_amount', 'last_deposit_at', 'deposit_count', 'deposit_sum'
                        ]);
                    }

                    if (['referral_total', 'referral_deposit'].includes(this.type)) {
                        return buildFields([
                            'username', 'name', 'register_at', 'inviter_id', 'inviter_name', 'first_deposit_at', 'first_deposit_amount', 'last_deposit_at', 'deposit_count', 'deposit_sum'
                        ]);
                    }

                    if (this.type === 'register_not_deposit') {
                        return buildFields([
                            'username', 'name', 'register_at', 'tel', 'channel', 'no_deposit_age'
                        ]);
                    }

                    if (this.type === 'referral_not_deposit') {
                        return buildFields([
                            'username', 'name', 'register_at', 'inviter_id', 'inviter_name', 'tel', 'channel', 'no_deposit_age'
                        ]);
                    }

                    return buildFields([
                        'username', 'name', 'register_at', 'first_deposit_at', 'time_to_first', 'first_deposit_amount'
                    ]);
                },
                memberListFieldMap: function () {
                    return {
                        username: { key: 'username', label: 'username', sortable: true },
                        name: { key: 'name', label: 'ชื่อ', sortable: true },
                        register_at: { key: 'register_at', label: 'วันที่สมัคร', sortable: true },
                        first_deposit_at: { key: 'first_deposit_at', label: 'วันที่ฝากครั้งแรก', sortable: true },
                        first_deposit_amount: { key: 'first_deposit_amount', label: 'ยอดฝากครั้งแรก', sortable: true },
                        last_deposit_at: { key: 'last_deposit_at', label: 'ฝากล่าสุด', sortable: true },
                        deposit_count: { key: 'deposit_count', label: 'ฝากทั้งหมดกี่ครั้ง', sortable: true },
                        deposit_sum: { key: 'deposit_sum', label: 'ฝากรวม', sortable: true },
                        inviter_id: { key: 'inviter_id', label: 'ไอดีคนชวน', sortable: true },
                        inviter_name: { key: 'inviter_name', label: 'ชื่อคนชวน', sortable: true },
                        tel: { key: 'tel', label: 'เบอร์', sortable: true },
                        channel: { key: 'channel', label: 'ช่องทางสมัคร', sortable: true },
                        no_deposit_age: { key: 'no_deposit_age', label: 'ไม่ฝากมาแล้ว', sortable: true },
                        time_to_first: { key: 'time_to_first', label: 'ใช้เวลาจากสมัครถึงฝาก', sortable: true },
                    };
                }
            },
            watch: {
                type: function () {
                    this.searchUsername = '';
                }
            }
        });

        Vue.component('top-risky-detail-modal', {
            template: '#top-risky-detail-modal-template',
            props: {
                title: { type: String, default: '' },
                subtitle: { type: String, default: '' },
                items: { type: Array, default: () => [] },
                fields: { type: Array, default: () => [] },
            },
            data: function () {
                return {
                    sortKey: '',
                    sortDesc: false,
                };
            },
            computed: {
                normalizedFields: function () {
                    return Array.isArray(this.fields) ? this.fields : [];
                },
                sortedItems: function () {
                    const rows = Array.isArray(this.items) ? this.items.slice() : [];
                    if (!this.sortKey) {
                        return rows;
                    }

                    const sortKey = this.sortKey;
                    const multiplier = this.sortDesc ? -1 : 1;

                    return rows.sort((left, right) => {
                        const leftValue = left && left[sortKey] !== undefined && left[sortKey] !== null ? left[sortKey] : '';
                        const rightValue = right && right[sortKey] !== undefined && right[sortKey] !== null ? right[sortKey] : '';

                        const leftNumber = typeof leftValue === 'number' ? leftValue : Number(String(leftValue).replace(/,/g, ''));
                        const rightNumber = typeof rightValue === 'number' ? rightValue : Number(String(rightValue).replace(/,/g, ''));
                        const leftIsNumber = Number.isFinite(leftNumber);
                        const rightIsNumber = Number.isFinite(rightNumber);

                        if (leftIsNumber && rightIsNumber) {
                            if (leftNumber === rightNumber) {
                                return 0;
                            }

                            return leftNumber > rightNumber ? multiplier : -multiplier;
                        }

                        const leftText = String(leftValue);
                        const rightText = String(rightValue);

                        if (leftText === rightText) {
                            return 0;
                        }

                        return leftText.localeCompare(rightText, undefined, { numeric: true }) * multiplier;
                    });
                }
            },
            watch: {
                fields: function (nextFields) {
                    const firstSortable = (Array.isArray(nextFields) ? nextFields : []).find((field) => field && field.sortable);
                    this.sortKey = firstSortable ? firstSortable.key : '';
                    this.sortDesc = false;
                }
            },
            mounted: function () {
                const firstSortable = this.normalizedFields.find((field) => field && field.sortable);
                this.sortKey = firstSortable ? firstSortable.key : '';
            },
            methods: {
                toggleSort: function (field) {
                    if (!field || !field.sortable) {
                        return;
                    }

                    if (this.sortKey === field.key) {
                        this.sortDesc = !this.sortDesc;
                        return;
                    }

                    this.sortKey = field.key;
                    this.sortDesc = false;
                }
            }
        });

        Vue.component('admin-dashboard', {
            template: '#admin-dashboard-template',
            data: function () {
                return {
                    filters: {
                        start: '',
                        end: ''
                    },
                    summary: {
                        deposit: {
                            amount: '-', amount_raw: 0, count: 0, users: 0,
                            success: { amount: '-', amount_raw: 0, count: 0, users: 0 },
                            pending: { amount: '-', amount_raw: 0, count: 0, users: 0 },
                            reject: { amount: '-', amount_raw: 0, count: 0, users: 0 },
                            deleted: { amount: '-', amount_raw: 0, count: 0, users: 0 },
                            problem: { amount: '-', amount_raw: 0, count: 0, users: 0 },
                        },
                        withdraw: {
                            amount: '-', amount_raw: 0, count: 0, users: 0,
                            pending: { amount: '-', amount_raw: 0, count: 0 },
                            main: { amount: '0', amount_raw: 0, count: 0, users: 0, pending: { amount: '0', amount_raw: 0, count: 0 } },
                            free: { amount: '0', amount_raw: 0, count: 0, users: 0, pending: { amount: '0', amount_raw: 0, count: 0 } },
                        },
                        bonus: {
                            amount: '-', amount_raw: 0, count: 0, ratio: 0,
                            deposit: { amount: '-', amount_raw: 0, count: 0 },
                            activity: { amount: '-', amount_raw: 0, count: 0 },
                            manual: { amount: '-', amount_raw: 0, count: 0 },
                        },
                        lotto: {
                            sales_cash: '-', sales_cash_raw: 0,
                            payout_cash: '-', payout_cash_raw: 0,
                            refund_cash: '-', refund_cash_raw: 0,
                            net_cash: '-', net_cash_raw: 0,
                        },
                        lotto_product: {
                            total_sales: '0.00', total_sales_raw: 0,
                            total_payout: '0.00', total_payout_raw: 0,
                            total_tickets: 0, total_players: 0,
                            win_tickets: 0, lose_tickets: 0,
                            pending_tickets: 0, settled_tickets: 0,
                        },
                        lotto_risk: {
                            markets: 0, rounds: 0, numbers: 0,
                            tracked_market_count: 0, tracked_round_count: 0, tracked_number_count: 0,
                            exposure_total: '0.00', exposure_total_raw: 0,
                            total_exposure: '0.00', total_exposure_raw: 0,
                            liability_total: '0.00', liability_total_raw: 0,
                            liability_max: '0.00', liability_max_raw: 0,
                            max_risk_per_number: '0.00', max_risk_per_number_raw: 0,
                            max_risk_number: '',
                            liability_total_deprecated: true,
                            liability_total_same_as_exposure: true,
                            deprecated_fields: [],
                            last_snapshot_at: '',
                        },
                        top_risky_numbers: [],
                        lotto_top_risky_numbers: [],
                        lotto_risk_alerts: [],
                        lotto_risk_trend: {
                            current_date: '',
                            previous_date: '',
                            risk_current: '0.00', risk_current_raw: 0,
                            risk_previous: '0.00', risk_previous_raw: 0,
                            risk_delta: '0.00', risk_delta_raw: 0,
                            risk_direction: 'flat',
                            sales_current: '0.00', sales_current_raw: 0,
                            sales_previous: '0.00', sales_previous_raw: 0,
                            sales_delta: '0.00', sales_delta_raw: 0,
                            sales_direction: 'flat',
                        },
                        lotto_bet_type_insights: [],
                        lotto_top_risk_users: [],
                        net: { amount: '-', amount_raw: 0, change_pct: 0 },
                        register: { total: 0, normal: 0, referral: 0, campaign: 0 },
                        first_deposit: { count: 0, rate: 0 }
                    },
                    conversion: {
                        register: { total: 0, deposit: 0, repeat_deposit: 0, not_deposit: 0, rate: 0 },
                        referral: { total: 0, deposit: 0, not_deposit: 0, rate: 0 },
                        staff: {
                            add: '-', reduce: '-', net: '-', count: 0,
                            main: { add: '-', reduce: '-', net: '-', count: 0 },
                            free: { add: '-', reduce: '-', net: '-', count: 0 }
                        }
                    },
                    trends: { labels: [], deposit: [], withdraw: [], bonus: [] },
                    funnel: { funnel: { register: 0, register_deposit: 0, register_repeat_deposit: 0, confirmed: 0, first_deposit: 0, repeat_deposit: 0 }, sources: { direct: 0, campaign: 0, referral: 0 } },
                    activity: { deposits: [], deposits_manual: [], withdraws: [], registers: [], staff: [], lotto_recent_bets: [] },
                    lottoRecentMarketId: '',
                    lottoRecentMarketOptions: lottoRecentMarketOptions,
                    depositTab: 'all',
                    lottoRiskTab: 'today',
                    bank: { in: [], out: [] },
                    adminLogs: { login: [], logout: [] },
                    canAlertToast: @json($permAlerts),
                    freeCreditOpen: @json($freeCreditOpen),
                    alertToastSeen: {},
                    memberList: {
                        type: 'register_deposit',
                        title: 'รายชื่อสมาชิกที่ฝากแล้ว',
                        items: [],
                        total: 0,
                        limit: 200,
                        loading: false
                    },
                    topRiskyDetail: {
                        title: '',
                        subtitle: '',
                        items: [],
                        fields: [],
                    },
                    trendMode: 'hour',
                    charts: { money: null, funnel: null },
                    refreshTimer: null,
                    realtime: {
                        channelName: '',
                        channel: null,
                        pendingSections: [],
                        debounceTimer: null,
                        pollTimer: null,
                        activityDirtyTimer: null,
                        activityDirtyHandler: null
                    },
                    kpiAnimated: {
                        deposit: 0,
                        deposit_count: 0,
                        deposit_users: 0,
                        deposit_success_amount: 0,
                        deposit_problem_amount: 0,
                        withdraw: 0,
                        withdraw_count: 0,
                        withdraw_users: 0,
                        withdraw_pending_count: 0,
                        bonus: 0,
                        bonus_deposit_amount: 0,
                        bonus_activity_amount: 0,
                        bonus_manual_amount: 0,
                        lotto_sales_cash: 0,
                        lotto_payout_cash: 0,
                        lotto_refund_cash: 0,
                        lotto_net_cash: 0,
                        net: 0,
                        net_change_pct: 0,
                        register: 0,
                        register_normal: 0,
                        register_referral: 0,
                        register_campaign: 0,
                        ftd: 0,
                        ftd_rate: 0
                    },
                    kpiAnimationRaf: {
                        deposit: null,
                        deposit_count: null,
                        deposit_users: null,
                        deposit_success_amount: null,
                        deposit_problem_amount: null,
                        withdraw: null,
                        withdraw_count: null,
                        withdraw_users: null,
                        withdraw_pending_count: null,
                        bonus: null,
                        bonus_deposit_amount: null,
                        bonus_activity_amount: null,
                        bonus_manual_amount: null,
                        lotto_sales_cash: null,
                        lotto_payout_cash: null,
                        lotto_refund_cash: null,
                        lotto_net_cash: null,
                        net: null,
                        net_change_pct: null,
                        register: null,
                        register_normal: null,
                        register_referral: null,
                        register_campaign: null,
                        ftd: null,
                        ftd_rate: null
                    },
                    ui: {
                        syncing: false,
                        loading: {
                            active: false,
                            skeleton: true,
                            message: '',
                            reason: '',
                            token: 0,
                            longTimer: null,
                            timeoutTimer: null
                        }
                    }
                };
            },
            computed: {
                rangeLabel() {
                    if (this.filters.start && this.filters.end) {
                        return `${this.formatDisplayDate(this.filters.start)} - ${this.formatDisplayDate(this.filters.end)}`;
                    }
                    return this.filters.start ? this.formatDisplayDate(this.filters.start) : '-';
                },
                depositRows() {
                    if (this.depositTab === 'manual') {
                        return Array.isArray(this.activity.deposits_manual) ? this.activity.deposits_manual : [];
                    }
                    return Array.isArray(this.activity.deposits) ? this.activity.deposits : [];
                },
                kpiCards() {
                    return [
                        {
                            key: 'deposit',
                            title: 'ยอดฝาก',
                            class: 'metric-deposit',
                            value: this.summary.deposit.amount,
                            sub: `จำนวนรายการ: ${this.summary.deposit.count} | จำนวนยูสที่ฝาก: ${this.summary.deposit.users}`,
                            sub2: `ฝากสำเร็จ: ${(this.summary.deposit.success && this.summary.deposit.success.amount) ? this.summary.deposit.success.amount : '-'} | ฝากมีปัญหา: ${(this.summary.deposit.problem && this.summary.deposit.problem.amount) ? this.summary.deposit.problem.amount : '-'}`
                        },
                        {
                            key: 'withdraw',
                            title: 'ยอดถอน',
                            class: 'metric-withdraw',
                            value: this.summary.withdraw.amount,
                            sub: `จำนวนรายการ: ${this.summary.withdraw.count} | จำนวนยูสที่ถอน: ${this.summary.withdraw.users}`,
                            sub2: `รออนุมัติ: ${(this.summary.withdraw.pending && this.summary.withdraw.pending.count) ? this.summary.withdraw.pending.count : 0} รายการ${this.freeCreditOpen ? ' | รวมถอนเครดิตฟรีแล้ว' : ''}`
                        },
                        {
                            key: 'bonus',
                            title: 'ยอดโบนัส',
                            class: 'metric-bonus',
                            value: this.summary.bonus.amount,
                            sub: `โบนัสฝาก: ${(this.summary.bonus.deposit && this.summary.bonus.deposit.amount) ? this.summary.bonus.deposit.amount : '-'}`,
                            sub2: `กิจกรรม: ${(this.summary.bonus.activity && this.summary.bonus.activity.amount) ? this.summary.bonus.activity.amount : '-'} | Manual: ${(this.summary.bonus.manual && this.summary.bonus.manual.amount) ? this.summary.bonus.manual.amount : '-'}`
                        },
                        {
                            key: 'lotto_net_cash',
                            title: 'Lotto Cash',
                            class: 'metric-lotto',
                            value: this.summary.lotto.net_cash,
                            sub: `ยอดแทง: ${(this.summary.lotto && this.summary.lotto.sales_cash) ? this.summary.lotto.sales_cash : '-'}`,
                            sub2: `จ่ายรางวัล: ${(this.summary.lotto && this.summary.lotto.payout_cash) ? this.summary.lotto.payout_cash : '-'} | เงินคืน: ${(this.summary.lotto && this.summary.lotto.refund_cash) ? this.summary.lotto.refund_cash : '-'}`
                        },
                        {
                            key: 'net',
                            title: 'คงเหลือสุทธิ',
                            class: 'metric-net',
                            value: this.summary.net.amount,
                            sub: `ฝากสำเร็จ - ถอนสำเร็จ`,
                            sub2: `เทียบช่วงก่อนหน้า: ${this.summary.net.change_pct}%`
                        },
                        {
                            key: 'register',
                            title: 'สมัครสมาชิก',
                            class: 'metric-register',
                            value: this.summary.register.total,
                            sub: `สมัครตรง: ${this.summary.register.normal}`,
                            sub2: `การแนะนำ: ${this.summary.register.referral} | แคมเปญ: ${this.summary.register.campaign || 0}`
                        },
                        {
                            key: 'ftd',
                            title: 'ฝากครั้งแรก',
                            class: 'metric-ftd',
                            value: this.summary.first_deposit.count,
                            sub: `FTD Rate: ${this.summary.first_deposit.rate}%`,
                            sub2: '&nbsp;'
                        }
                    ];
                },
                summarySide() {
                    const avgDeposit = this.summary.deposit.count > 0
                        ? (this.summary.deposit.amount_raw / this.summary.deposit.count)
                        : 0;
                    const avgWithdraw = this.summary.withdraw.count > 0
                        ? (this.summary.withdraw.amount_raw / this.summary.withdraw.count)
                        : 0;
                    const avgBonus = this.summary.deposit.users > 0
                        ? (this.summary.bonus.amount_raw / this.summary.deposit.users)
                        : 0;

                    return {
                        avg_deposit: this.formatCurrency(avgDeposit),
                        avg_withdraw: this.formatCurrency(avgWithdraw),
                        avg_bonus_per_user: this.formatCurrency(avgBonus),
                    };
                }
            },
            watch: {
                filters: {
                    deep: true,
                    handler() {
                        this.scheduleRefresh();
                    }
                }
            },
            mounted() {
                console.log(this.freeCreditOpen);
                this.$nextTick(() => {
                    this.alertToastSeen = this.loadAlertToastSeen();
                    this.initDatepicker();
                    this.initLottoRecentMarketSelect();
                    this.animateKpiValues(this.summaryAnimationSnapshot(), 0);
                    this.refreshAll({ reason: 'initial', skeleton: false });
                    this.subscribeRealtime();
                    this.bindActivityRealtimeSignal();
                    this.startRealtimePollingFallback();
                });
            },
            beforeDestroy() {
                if (this.refreshTimer) clearTimeout(this.refreshTimer);
                this.stopRealtimePollingFallback();
                this.unsubscribeRealtime();
                this.unbindActivityRealtimeSignal();
                this.destroyLottoRecentMarketSelect();
                if (this.realtime.debounceTimer) clearTimeout(this.realtime.debounceTimer);
                this.stopKpiAnimations();
            },
            methods: {
                initLottoRecentMarketSelect() {
                    this.$nextTick(() => {
                        const $select = $(this.$refs.lottoRecentMarketSelect);
                        if (!$select || !$select.length) return;

                        if ($.fn.select2) {
                            $select.select2({
                                width: '100%',
                                placeholder: 'เลือกรายการหวย',
                                allowClear: true,
                            });
                        }

                        $select.val(this.lottoRecentMarketId || '').trigger('change.select2');
                        $select.on('change.dashboardLottoRecentMarket', (event) => {
                            const value = String($(event.currentTarget).val() || '');
                            this.lottoRecentMarketId = value;
                            this.refreshActivityOnly();
                        });
                    });
                },
                destroyLottoRecentMarketSelect() {
                    const $select = $(this.$refs.lottoRecentMarketSelect);
                    if (!$select || !$select.length) return;

                    $select.off('.dashboardLottoRecentMarket');
                    if ($.fn.select2 && $select.data('select2')) {
                        $select.select2('destroy');
                    }
                },

                normalizeResponse(res) {
                    if (!res || !res.data) return null;
                    if (typeof res.data === 'string') {
                        try {
                            const parsed = JSON.parse(res.data);
                            if (parsed && typeof parsed === 'object') {
                                if (Object.prototype.hasOwnProperty.call(parsed, 'data')) return parsed.data;
                                return parsed;
                            }
                        } catch (e) {
                            return null;
                        }
                    }
                    if (res.data && typeof res.data === 'object' && Object.prototype.hasOwnProperty.call(res.data, 'data')) {
                        return res.data.data;
                    }
                    return res.data;
                },
                alertToastStorageKey() {
                    return 'dashboard_alert_toast_seen_v1';
                },
                alertToastCooldownMs() {
                    return 30 * 60 * 1000;
                },
                normalizeAlerts(data) {
                    if (Array.isArray(data)) return data;
                    if (data && typeof data === 'object') {
                        return Object.values(data).filter((item) => item && typeof item === 'object' && item.title);
                    }
                    return [];
                },
                loadAlertToastSeen() {
                    if (typeof window === 'undefined' || !window.localStorage) return {};
                    try {
                        const raw = window.localStorage.getItem(this.alertToastStorageKey());
                        if (!raw) return {};
                        const parsed = JSON.parse(raw);
                        return parsed && typeof parsed === 'object' ? parsed : {};
                    } catch (e) {
                        return {};
                    }
                },
                saveAlertToastSeen() {
                    if (typeof window === 'undefined' || !window.localStorage) return;
                    try {
                        window.localStorage.setItem(this.alertToastStorageKey(), JSON.stringify(this.alertToastSeen || {}));
                    } catch (e) {}
                },
                pruneAlertToastSeen(nowMs = Date.now()) {
                    const cooldown = this.alertToastCooldownMs();
                    const next = {};
                    Object.keys(this.alertToastSeen || {}).forEach((key) => {
                        const ts = Number(this.alertToastSeen[key] || 0);
                        if (ts > 0 && (nowMs - ts) < cooldown) {
                            next[key] = ts;
                        }
                    });
                    this.alertToastSeen = next;
                },
                alertToastKey(alert) {
                    const code = String((alert && (alert.code || alert.key || alert.type)) || '').trim().toLowerCase();
                    if (code) {
                        return `code|${code}`;
                    }
                    const level = String((alert && alert.level) || '').trim().toLowerCase();
                    const title = String((alert && alert.title) || '').trim();
                    const message = String((alert && alert.message) || '').trim();
                    const normalizedMessage = message
                        .replace(/-?\d[\d,]*(\.\d+)?/g, '#')
                        .replace(/\s+/g, ' ')
                        .trim();
                    return `${level}|${title}|${normalizedMessage}`;
                },
                shouldShowAlertToast(alert, nowMs = Date.now()) {
                    const key = this.alertToastKey(alert);
                    if (!key || key === '||') return false;
                    const previous = Number((this.alertToastSeen && this.alertToastSeen[key]) || 0);
                    if (previous > 0 && (nowMs - previous) < this.alertToastCooldownMs()) {
                        return false;
                    }
                    this.alertToastSeen = Object.assign({}, this.alertToastSeen, { [key]: nowMs });
                    return true;
                },
                alertToastMessage(alert) {
                    const title = String((alert && alert.title) || '').trim();
                    const message = String((alert && alert.message) || '').trim();
                    if (title && message) return `${title}: ${message}`;
                    return title || message || 'มีแจ้งเตือนใหม่';
                },
                emitAlertToast(alert) {
                    const payload = {
                        ui: 'toast',
                        as: 'RealTime.Message.All',
                        level: String((alert && alert.level) || 'warning').toLowerCase(),
                        message: this.alertToastMessage(alert),
                        toast: {
                            className: 'gt-toast gt-toast-alert',
                            duration: 10000,
                            gravity: 'top',
                            position: 'right',
                            avatar: '/assets/admin/icons/alert.webp?v=1'
                        }
                    };

                    if (typeof window !== 'undefined' && typeof window.handleRT === 'function') {
                        window.handleRT(payload);
                        return;
                    }
                    if (typeof Toastify !== 'undefined') {
                        Toastify({
                            text: payload.message,
                            duration: payload.toast.duration,
                            newWindow: true,
                            close: true,
                            gravity: payload.toast.gravity,
                            position: payload.toast.position,
                            stopOnFocus: true,
                            className: payload.toast.className,
                            avatar: payload.toast.avatar
                        }).showToast();
                    }
                },
                processAlertsAsToast(responseData) {
                    if (!this.canAlertToast) return;
                    const alerts = this.normalizeAlerts(responseData);
                    if (!alerts.length) return;

                    const nowMs = Date.now();
                    this.pruneAlertToastSeen(nowMs);

                    let hasNew = false;
                    alerts.forEach((alert) => {
                        if (!this.shouldShowAlertToast(alert, nowMs)) return;
                        hasNew = true;
                        this.emitAlertToast(alert);
                    });

                    if (hasNew) {
                        this.saveAlertToastSeen();
                    }
                },
                formatAmountNumber(value) {
                    const number = Number(value || 0);
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(number);
                },
                formatCountNumber(value) {
                    const number = Number(value || 0);
                    return new Intl.NumberFormat('en-US', {
                        maximumFractionDigits: 0
                    }).format(Math.round(number));
                },
                summaryAnimationSnapshot(summaryData = null) {
                    const source = summaryData || this.summary;
                    return {
                        deposit: Number((source && source.deposit && source.deposit.amount_raw) || 0),
                        deposit_count: Number((source && source.deposit && source.deposit.count) || 0),
                        deposit_users: Number((source && source.deposit && source.deposit.users) || 0),
                        deposit_success_amount: Number((source && source.deposit && source.deposit.success && source.deposit.success.amount_raw) || 0),
                        deposit_problem_amount: Number((source && source.deposit && source.deposit.problem && source.deposit.problem.amount_raw) || 0),
                        withdraw: Number((source && source.withdraw && source.withdraw.amount_raw) || 0),
                        withdraw_count: Number((source && source.withdraw && source.withdraw.count) || 0),
                        withdraw_users: Number((source && source.withdraw && source.withdraw.users) || 0),
                        withdraw_pending_count: Number((source && source.withdraw && source.withdraw.pending && source.withdraw.pending.count) || 0),
                        bonus: Number((source && source.bonus && source.bonus.amount_raw) || 0),
                        bonus_deposit_amount: Number((source && source.bonus && source.bonus.deposit && source.bonus.deposit.amount_raw) || 0),
                        bonus_activity_amount: Number((source && source.bonus && source.bonus.activity && source.bonus.activity.amount_raw) || 0),
                        bonus_manual_amount: Number((source && source.bonus && source.bonus.manual && source.bonus.manual.amount_raw) || 0),
                        lotto_sales_cash: Number((source && source.lotto && source.lotto.sales_cash_raw) || 0),
                        lotto_payout_cash: Number((source && source.lotto && source.lotto.payout_cash_raw) || 0),
                        lotto_refund_cash: Number((source && source.lotto && source.lotto.refund_cash_raw) || 0),
                        lotto_net_cash: Number((source && source.lotto && source.lotto.net_cash_raw) || 0),
                        net: Number((source && source.net && source.net.amount_raw) || 0),
                        net_change_pct: Number((source && source.net && source.net.change_pct) || 0),
                        register: Number((source && source.register && source.register.total) || 0),
                        register_normal: Number((source && source.register && source.register.normal) || 0),
                        register_referral: Number((source && source.register && source.register.referral) || 0),
                        register_campaign: Number((source && source.register && source.register.campaign) || 0),
                        ftd: Number((source && source.first_deposit && source.first_deposit.count) || 0),
                        ftd_rate: Number((source && source.first_deposit && source.first_deposit.rate) || 0),
                    };
                },
                stopKpiAnimations() {
                    Object.keys(this.kpiAnimationRaf).forEach((key) => {
                        const frameId = this.kpiAnimationRaf[key];
                        if (frameId) {
                            cancelAnimationFrame(frameId);
                            this.kpiAnimationRaf[key] = null;
                        }
                    });
                },
                animateKpiMetric(key, fromValue, toValue, duration = 750) {
                    if (!Object.prototype.hasOwnProperty.call(this.kpiAnimated, key)) {
                        return;
                    }

                    const from = Number(fromValue || 0);
                    const to = Number(toValue || 0);
                    const delta = to - from;

                    if (!Number.isFinite(from) || !Number.isFinite(to)) {
                        this.kpiAnimated[key] = 0;
                        return;
                    }

                    if (this.kpiAnimationRaf[key]) {
                        cancelAnimationFrame(this.kpiAnimationRaf[key]);
                        this.kpiAnimationRaf[key] = null;
                    }

                    if (Math.abs(delta) < 0.0001) {
                        this.kpiAnimated[key] = to;
                        return;
                    }

                    if (typeof requestAnimationFrame !== 'function' || duration <= 0) {
                        this.kpiAnimated[key] = to;
                        return;
                    }

                    const startAt = (typeof performance !== 'undefined' && performance.now)
                        ? performance.now()
                        : Date.now();
                    const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);
                    const tick = (timestamp) => {
                        const nowMs = timestamp || ((typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now());
                        const progress = Math.min(1, (nowMs - startAt) / duration);
                        this.kpiAnimated[key] = from + (delta * easeOutCubic(progress));

                        if (progress < 1) {
                            this.kpiAnimationRaf[key] = requestAnimationFrame(tick);
                            return;
                        }

                        this.kpiAnimated[key] = to;
                        this.kpiAnimationRaf[key] = null;
                    };

                    this.kpiAnimationRaf[key] = requestAnimationFrame(tick);
                },
                animateKpiValues(nextSnapshot, duration = 750) {
                    if (!nextSnapshot || typeof nextSnapshot !== 'object') {
                        return;
                    }

                    Object.keys(nextSnapshot).forEach((key) => {
                        this.animateKpiMetric(
                            key,
                            Number(this.kpiAnimated[key] || 0),
                            Number(nextSnapshot[key] || 0),
                            duration
                        );
                    });
                },
                uiAnimatedAmount(key) {
                    return this.formatAmountNumber(this.kpiAnimated[key] || 0);
                },
                uiAnimatedCount(key) {
                    return this.formatCountNumber(this.kpiAnimated[key] || 0);
                },
                uiAnimatedPercent(key) {
                    const value = Number(this.kpiAnimated[key] || 0);
                    return `${value.toFixed(2)}%`;
                },
                uiPrimary(value) {
                    return this.uiValue(value, '-');
                },
                uiValue(value, placeholder = '-') {
                    if (value === null || value === undefined || value === '') return placeholder;
                    return value;
                },
                formatLottoRiskBetType(value) {
                    const key = String(value || '').trim().toLowerCase();
                    if (!key) return '-';

                    const map = {
                        top_2: '2 ตัวบน',
                        top_3: '3 ตัวบน',
                        bottom_2: '2 ตัวล่าง',
                        bottom_3: '3 ตัวล่าง',
                        run_top: 'วิ่งบน',
                        run_bottom: 'วิ่งล่าง',
                    };

                    return map[key] || value;
                },
                uiCount(value) {
                    if (value === null || value === undefined || value === '') return 0;
                    return value;
                },
                uiPercent(value) {
                    if (value === null || value === undefined || value === '') return '0%';
                    return `${value}%`;
                },
                clearLoadingTimers() {
                    if (this.ui.loading.longTimer) {
                        clearTimeout(this.ui.loading.longTimer);
                        this.ui.loading.longTimer = null;
                    }
                    if (this.ui.loading.timeoutTimer) {
                        clearTimeout(this.ui.loading.timeoutTimer);
                        this.ui.loading.timeoutTimer = null;
                    }
                },
                formatStatusDate(value) {
                    if (!value) return '';
                    if (!moment) return value;

                    const date = moment(value, 'YYYY-MM-DD');
                    if (!date.isValid()) return value;

                    const monthNames = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                    return `${date.format('D')} ${monthNames[date.month()]} ${date.format('YYYY')}`;
                },
                loadingMessageFor(reason = 'manual') {
                    if (reason === 'date-change') {
                        if (this.filters.start && this.filters.end && this.filters.start === this.filters.end) {
                            return `กำลังคำนวณข้อมูลของวันที่ ${this.formatStatusDate(this.filters.start)}...`;
                        }
                        if (this.filters.start && this.filters.end) {
                            return `กำลังคำนวณข้อมูลช่วง ${this.formatStatusDate(this.filters.start)} - ${this.formatStatusDate(this.filters.end)}...`;
                        }
                        return 'กำลังคำนวณข้อมูล...';
                    }

                    if (reason === 'realtime') {
                        return 'กำลังอัปเดตข้อมูลล่าสุด...';
                    }
                    if (reason === 'resync') {
                        return 'กำลังซิงค์และคำนวณข้อมูลใหม่...';
                    }

                    return 'กำลังคำนวณข้อมูล...';
                },
                startLoading(reason = 'manual', options = {}) {
                    const useSkeleton = options.skeleton !== false;
                    const token = (this.ui.loading.token || 0) + 1;

                    this.ui.loading.token = token;
                    this.ui.loading.active = true;
                    this.ui.loading.skeleton = useSkeleton;
                    this.ui.loading.reason = reason;
                    this.ui.loading.message = this.loadingMessageFor(reason);

                    this.clearLoadingTimers();

                    this.ui.loading.longTimer = setTimeout(() => {
                        if (!this.ui.loading.active || this.ui.loading.token !== token) return;
                        const longMessage = reason === 'realtime'
                            ? 'กำลังอัปเดตข้อมูลล่าสุด...'
                            : 'กำลังเตรียมข้อมูล Dashboard...';
                        this.ui.loading.message = longMessage;
                    }, 3000);

                    this.ui.loading.timeoutTimer = setTimeout(() => {
                        if (!this.ui.loading.active || this.ui.loading.token !== token) return;
                        const timeoutMessage = 'กำลังคำนวณข้อมูลจำนวนมาก อาจใช้เวลาสักครู่...';
                        this.ui.loading.message = timeoutMessage;
                    }, 5000);

                    return token;
                },
                stopLoading(token = null) {
                    if (token !== null && token !== this.ui.loading.token) return;

                    this.clearLoadingTimers();
                    this.ui.loading.active = false;
                    this.ui.loading.skeleton = true;
                    this.ui.loading.reason = '';
                    this.ui.loading.message = '';
                },
                scheduleRefresh(reason = 'date-change') {
                    if (this.refreshTimer) clearTimeout(this.refreshTimer);
                    const token = this.startLoading(reason, { skeleton: false });
                    this.refreshTimer = setTimeout(() => this.refreshAll({
                        reason,
                        startLoading: false,
                        loadingToken: token
                    }), 300);
                },
                subscribeRealtime() {
                    if (!window.Echo || !dashboardRealtime.webCode) return;

                    try {
                        const channelName = `dashboard.summary.${dashboardRealtime.webCode}`;
                        this.realtime.channelName = channelName;
                        this.realtime.channel = window.Echo.private(channelName);
                        this.realtime.channel.listen('.dashboard.summary.updated', (event) => {
                            this.onRealtimeSummaryUpdated(event);
                        });
                    } catch (e) {
                        console.warn('dashboard realtime subscribe failed', e);
                    }
                },
                unsubscribeRealtime() {
                    if (!window.Echo || !this.realtime.channelName) return;

                    try {
                        window.Echo.leave(this.realtime.channelName);
                        window.Echo.leave(`private-${this.realtime.channelName}`);
                    } catch (e) {
                        console.warn('dashboard realtime unsubscribe failed', e);
                    }

                    this.realtime.channel = null;
                    this.realtime.channelName = '';
                },
                startRealtimePollingFallback() {
                    if (this.realtime.pollTimer) clearInterval(this.realtime.pollTimer);
                    this.realtime.pollTimer = setInterval(() => {
                        this.refreshAll({ reason: 'realtime', skeleton: false });
                    }, dashboardRealtime.fallbackPollMs);
                },
                stopRealtimePollingFallback() {
                    if (!this.realtime.pollTimer) return;
                    clearInterval(this.realtime.pollTimer);
                    this.realtime.pollTimer = null;
                },
                bindActivityRealtimeSignal() {
                    if (typeof window === 'undefined') return;
                    if (this.realtime.activityDirtyHandler) return;

                    this.realtime.activityDirtyHandler = () => {
                        if (this.realtime.activityDirtyTimer) clearTimeout(this.realtime.activityDirtyTimer);
                        this.realtime.activityDirtyTimer = setTimeout(() => {
                            this.refreshActivityOnly();
                        }, 180);
                    };

                    window.addEventListener('dashboard:activity-dirty', this.realtime.activityDirtyHandler);
                },
                unbindActivityRealtimeSignal() {
                    if (typeof window === 'undefined') return;
                    if (this.realtime.activityDirtyHandler) {
                        window.removeEventListener('dashboard:activity-dirty', this.realtime.activityDirtyHandler);
                        this.realtime.activityDirtyHandler = null;
                    }
                    if (this.realtime.activityDirtyTimer) {
                        clearTimeout(this.realtime.activityDirtyTimer);
                        this.realtime.activityDirtyTimer = null;
                    }
                },
                refreshActivityOnly() {
                    if (!axios || typeof axios.post !== 'function') return;

                    axios.post("{{ route('admin.dashboard.activity') }}", this.buildPayload({
                        lotto_market_id: this.lottoRecentMarketId || '',
                    }))
                        .then((res) => {
                            const data = this.normalizeResponse(res);
                            if (data && data.deposits) {
                                this.activity = Object.assign({}, this.activity, data);
                            }
                        })
                        .catch(() => {});
                },
                onRealtimeSummaryUpdated(event) {
                    if (!event || !event.summary_date) return;
                    if (event.web_code && dashboardRealtime.webCode && event.web_code !== dashboardRealtime.webCode) return;
                    if (!this.isDateInCurrentRange(event.summary_date)) return;

                    const sections = Array.isArray(event.updated_sections) ? event.updated_sections : [];
                    this.queueRealtimeRefresh(sections);
                },
                isDateInCurrentRange(summaryDate) {
                    if (!summaryDate) return false;
                    if (!this.filters.start || !this.filters.end) return true;
                    if (!moment) return true;

                    const date = moment(summaryDate, 'YYYY-MM-DD');
                    const start = moment(this.filters.start, 'YYYY-MM-DD');
                    const end = moment(this.filters.end, 'YYYY-MM-DD');
                    if (!date.isValid() || !start.isValid() || !end.isValid()) return true;

                    return date.isBetween(start, end, 'day', '[]');
                },
                queueRealtimeRefresh(sections = []) {
                    const merged = new Set([...(this.realtime.pendingSections || []), ...sections]);
                    this.realtime.pendingSections = Array.from(merged);

                    if (this.realtime.debounceTimer) clearTimeout(this.realtime.debounceTimer);
                    this.realtime.debounceTimer = setTimeout(() => {
                        const flushSections = Array.from(new Set(this.realtime.pendingSections));
                        this.realtime.pendingSections = [];
                        this.refreshRealtimeSections(flushSections);
                    }, dashboardRealtime.debounceMs);
                },
                refreshRealtimeSections(sections = []) {
                    if (!axios || typeof axios.post !== 'function') return;

                    const needRegisterFlow = sections.includes('register')
                        || sections.includes('conversion')
                        || sections.includes('funnel')
                        || sections.includes('deposit');
                    const lottoOnlySections = new Set([
                        'lotto_cash',
                        'lotto_product',
                        'lotto_risk',
                        'lotto_operations',
                        'lotto_bet_type_insights',
                        'net'
                    ]);
                    const lottoOnly = sections.length > 0
                        && sections.every((section) => lottoOnlySections.has(section));
                    const payload = this.buildPayload();
                    const activityPayload = this.buildPayload({
                        lotto_market_id: this.lottoRecentMarketId || '',
                    });
                    const loadingToken = this.startLoading('realtime', { skeleton: false });
                    const requests = lottoOnly
                        ? [axios.post("{{ route('admin.dashboard.summary') }}", payload)]
                        : [
                            axios.post("{{ route('admin.dashboard.summary') }}", payload),
                            axios.post("{{ route('admin.dashboard.trends') }}", payload),
                            axios.post("{{ route('admin.dashboard.alerts') }}", payload),
                            axios.post("{{ route('admin.dashboard.activity') }}", activityPayload)
                        ];

                    if (!lottoOnly && needRegisterFlow) {
                        requests.push(axios.post("{{ route('admin.dashboard.conversion') }}", payload));
                        requests.push(axios.post("{{ route('admin.dashboard.funnel') }}", payload));
                    }

                    const settle = Promise.allSettled
                        ? Promise.allSettled.bind(Promise)
                        : (list) => Promise.all(list.map((p) => p
                            .then((value) => ({ status: 'fulfilled', value }))
                            .catch((reason) => ({ status: 'rejected', reason }))
                        ));

                    settle(requests).then((results) => {
                        const [summaryRes, trendRes, alertsRes, activityRes, conversionRes, funnelRes] = results;

                        if (summaryRes && summaryRes.status === 'fulfilled') {
                            const data = this.normalizeResponse(summaryRes.value);
                            if (data && data.deposit) {
                                this.summary = Object.assign({}, this.summary, data);
                                this.animateKpiValues(this.summaryAnimationSnapshot(this.summary), 700);
                            }
                        }

                        if (!lottoOnly && trendRes && trendRes.status === 'fulfilled') {
                            const data = this.normalizeResponse(trendRes.value);
                            if (data && data.labels) {
                                this.trends = Object.assign({}, this.trends, data);
                            }
                        }

                        if (!lottoOnly && alertsRes && alertsRes.status === 'fulfilled') {
                            const data = this.normalizeResponse(alertsRes.value);
                            this.processAlertsAsToast(data);
                        }

                        if (!lottoOnly && activityRes && activityRes.status === 'fulfilled') {
                            const data = this.normalizeResponse(activityRes.value);
                            if (data && data.deposits) {
                                this.activity = Object.assign({}, this.activity, data);
                            }
                        }

                        if (!lottoOnly && needRegisterFlow && conversionRes && conversionRes.status === 'fulfilled') {
                            const data = this.normalizeResponse(conversionRes.value);
                            if (data && data.register) {
                                this.conversion = Object.assign({}, this.conversion, data);
                            }
                        }

                        if (!lottoOnly && needRegisterFlow && funnelRes && funnelRes.status === 'fulfilled') {
                            const data = this.normalizeResponse(funnelRes.value);
                            if (data && data.funnel) {
                                this.funnel = Object.assign({}, this.funnel, data);
                            }
                        }

                        if (!lottoOnly) {
                            this.renderMoneyChart();
                        }
                        if (!lottoOnly && needRegisterFlow) {
                            this.renderFunnelChart();
                        }
                    }).catch(() => {}).then(() => {
                        this.stopLoading(loadingToken);
                    });
                },
                buildPayload(extra = {}) {
                    return {
                        date_start: this.filters.start,
                        date_end: this.filters.end,
                        trend_mode: this.trendMode,
                        lotto_risk_mode: this.lottoRiskTab === 'highest' ? 'peak' : 'today',
                        ...extra
                    };
                },
                switchLottoRiskTab(nextTab) {
                    const normalizedTab = nextTab === 'highest' ? 'highest' : 'today';
                    if (this.lottoRiskTab === normalizedTab) {
                        return;
                    }

                    this.lottoRiskTab = normalizedTab;
                    this.refreshLottoRiskSummary(normalizedTab);
                },
                refreshLottoRiskSummary(tab) {
                    if (!axios || typeof axios.post !== 'function') return;

                    const normalizedTab = tab === 'highest' ? 'highest' : 'today';
                    const previousTab = this.lottoRiskTab;
                    this.lottoRiskTab = normalizedTab;
                    const payload = this.buildPayload();
                    const loadingToken = this.startLoading('lotto-risk-tab', { skeleton: false });

                    axios.post("{{ route('admin.dashboard.summary') }}", payload)
                        .then((res) => {
                            const data = this.normalizeResponse(res);
                            if (data && data.deposit) {
                                this.summary = Object.assign({}, this.summary, data);
                                this.animateKpiValues(this.summaryAnimationSnapshot(this.summary), 500);
                            }
                        })
                        .catch(() => {
                            this.lottoRiskTab = previousTab;
                        })
                        .then(() => {
                            this.stopLoading(loadingToken);
                        });
                },
                refreshAll(options = {}) {
                    if (!axios || typeof axios.post !== 'function') return;

                    const config = options && options.target
                        ? {}
                        : (options || {});
                    const reason = config.reason || 'manual';
                    const useSkeleton = config.skeleton === true;
                    const shouldStartLoading = config.startLoading !== false;
                    const loadingToken = shouldStartLoading
                        ? this.startLoading(reason, { skeleton: useSkeleton })
                        : (config.loadingToken || null);

                    const payload = this.buildPayload();
                    const activityPayload = this.buildPayload({
                        lotto_market_id: this.lottoRecentMarketId || '',
                    });
                    const settle = Promise.allSettled
                        ? Promise.allSettled.bind(Promise)
                        : (list) => Promise.all(list.map((p) => p
                            .then((value) => ({ status: 'fulfilled', value }))
                            .catch((reason) => ({ status: 'rejected', reason }))
                        ));

                    settle([
                        axios.post("{{ route('admin.dashboard.summary') }}", payload),
                        axios.post("{{ route('admin.dashboard.conversion') }}", payload),
                        axios.post("{{ route('admin.dashboard.trends') }}", payload),
                        axios.post("{{ route('admin.dashboard.funnel') }}", payload),
                        axios.post("{{ route('admin.dashboard.activity') }}", activityPayload),
                        axios.post("{{ route('admin.dashboard.alerts') }}", payload)
                    ]).then((results) => {
                        const [summaryRes, convRes, trendRes, funnelRes, activityRes, alertsRes] = results;
                        if (summaryRes.status === 'fulfilled') {
                            const data = this.normalizeResponse(summaryRes.value);
                            if (data && data.deposit) {
                                this.summary = Object.assign({}, this.summary, data);
                                this.animateKpiValues(this.summaryAnimationSnapshot(this.summary), 800);
                            }
                        }
                        if (convRes.status === 'fulfilled') {
                            const data = this.normalizeResponse(convRes.value);
                            if (data && data.register) {
                                this.conversion = Object.assign({}, this.conversion, data);
                            }
                        }
                        if (trendRes.status === 'fulfilled') {
                            const data = this.normalizeResponse(trendRes.value);
                            if (data && data.labels) {
                                this.trends = Object.assign({}, this.trends, data);
                            }
                        }
                        if (funnelRes.status === 'fulfilled') {
                            const data = this.normalizeResponse(funnelRes.value);
                            if (data && data.funnel) {
                                this.funnel = Object.assign({}, this.funnel, data);
                            }
                        }
                        if (activityRes.status === 'fulfilled') {
                            const data = this.normalizeResponse(activityRes.value);
                            if (data && data.deposits) {
                                this.activity = Object.assign({}, this.activity, data);
                            }
                        }
                        if (alertsRes.status === 'fulfilled') {
                            const data = this.normalizeResponse(alertsRes.value);
                            this.processAlertsAsToast(data);
                        }
                        this.renderMoneyChart();
                        this.renderFunnelChart();
                    }).catch(() => {}).then(() => {
                        this.stopLoading(loadingToken);
                    });

                    this.refreshBankAndLogin();
                    if (this.$refs && this.$refs.online && typeof this.$refs.online.reload === 'function') {
                        this.$refs.online.reload();
                    }

                },
                refreshBankAndLogin() {
                    if (!axios || typeof axios.post !== 'function') return;
                    Promise.all([
                        axios.post("{{ route('admin.dashboard.loadbank') }}", { method: 'bankin' }),
                        axios.post("{{ route('admin.dashboard.loadbank') }}", { method: 'bankout' }),
                        axios.post("{{ route('admin.dashboard.loadlogin') }}", { method: 'login' }),
                        axios.post("{{ route('admin.dashboard.loadlogin') }}", { method: 'logout' })
                    ]).then(([bankInRes, bankOutRes, loginRes, logoutRes]) => {
                        const bankIn = this.normalizeResponse(bankInRes);
                        const bankOut = this.normalizeResponse(bankOutRes);
                        const login = this.normalizeResponse(loginRes);
                        const logout = this.normalizeResponse(logoutRes);
                        this.bank.in = (bankIn && bankIn.list) ? bankIn.list : [];
                        this.bank.out = (bankOut && bankOut.list) ? bankOut.list : [];
                        this.adminLogs.login = (login && login.list) ? login.list : [];
                        this.adminLogs.logout = (logout && logout.list) ? logout.list : [];
                    }).catch(() => {});
                },
                runSummarySync() {
                    if (this.ui.syncing) return;
                    if (!dashboardRealtime.canSyncSummary || !dashboardRoutes.syncSummary) return;
                    if (!axios || typeof axios.post !== 'function') return;

                    this.ui.syncing = true;
                    const token = this.startLoading('resync', { skeleton: false });
                    const payload = this.buildPayload();

                    axios.post(dashboardRoutes.syncSummary, payload)
                        .then(() => {
                            this.refreshAll({
                                reason: 'resync',
                                skeleton: false,
                                startLoading: false,
                                loadingToken: token
                            });
                        })
                        .catch(() => {
                            this.stopLoading(token);
                        })
                        .then(() => {
                            this.ui.syncing = false;
                        });
                },
                setTrendMode(mode) {
                    this.trendMode = mode;
                    this.refreshAll({ reason: 'manual', skeleton: false });
                },
                formatCurrency(val) {
                    return new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB', maximumFractionDigits: 2 }).format(val || 0);
                },
                formatDisplayDate(value) {
                    if (!moment) return value;
                    return moment(value, 'YYYY-MM-DD').format('DD/MM/YYYY');
                },
                applyPreset(type) {
                    if (!moment) return;
                    let start = moment();
                    let end = moment();
                    if (type === 'yesterday') {
                        start = moment().subtract(1, 'days');
                        end = moment().subtract(1, 'days');
                    }
                    if (type === '7d') {
                        start = moment().subtract(6, 'days');
                        end = moment();
                    }
                    if (type === '30d') {
                        start = moment().subtract(29, 'days');
                        end = moment();
                    }
                    this.setDateRange(start, end);
                },
                openCustom() {
                    const $root = $(this.$el || document);
                    let $input = $root.find('#search_date');
                    if (!$input.length) {
                        $input = $(document).find('#search_date');
                    }
                    const drp = $input.data('daterangepicker');
                    if (drp && typeof drp.show === 'function') drp.show();
                },
                setDateRangeValues(startString, endString) {
                    this.filters.start = startString;
                    this.filters.end = endString;
                    const text = `${startString} ถึง ${endString}`;
                    document.querySelectorAll('#search_date').forEach((el) => { el.value = text; });
                    document.querySelectorAll('#startDate').forEach((el) => { el.value = startString; });
                    document.querySelectorAll('#endDate').forEach((el) => { el.value = endString; });
                },
                setDateRange(start, end) {
                    const s = start.format('YYYY-MM-DD');
                    const e = end.format('YYYY-MM-DD');
                    if (this.filters.start === s && this.filters.end === e) return;
                    this.setDateRangeValues(s, e);
                },
                initDatepicker() {
                    if (!$ || !moment) return;
                    if (this._dateInit) return;
                    this._dateInit = true;
                    const self = this;

                    const setHiddenDates = (start, end) => {
                        document.querySelectorAll('#startDate').forEach((el) => { el.value = start || ''; });
                        document.querySelectorAll('#endDate').forEach((el) => { el.value = end || ''; });
                    };

                    const getHiddenDates = () => {
                        const root = this.$el || document;
                        let sEl = null;
                        let eEl = null;
                        if (root && typeof root.querySelector === 'function') {
                            sEl = root.querySelector('#startDate');
                            eEl = root.querySelector('#endDate');
                        }
                        if (!sEl) sEl = document.querySelector('#startDate');
                        if (!eEl) eEl = document.querySelector('#endDate');
                        const s = sEl && typeof sEl.value !== 'undefined' ? sEl.value : '';
                        const e = eEl && typeof eEl.value !== 'undefined' ? eEl.value : '';
                        return { start: s, end: e };
                    };

                    const broadcastDateChanged = () => {
                        window.dispatchEvent(new CustomEvent('dashboard:date-changed'));
                    };

                    const ensureDefaultToday = (force = false) => {
                        const cur = String($(document).find('#search_date').first().val() || '').trim();
                        if (!force && cur !== '') return;
                        const today = moment().format('YYYY-MM-DD');
                        setHiddenDates(today, today);
                        document.querySelectorAll('#search_date').forEach((el) => { el.value = `${today} ถึง ${today}`; });
                    };

                    const initOn = ($input) => {
                        if (!$input || !$input.length) return;
                        if (typeof $input.daterangepicker !== 'function') return;

                        try {
                            $input.off('.daterangepicker');
                            const old = $input.data('daterangepicker');
                            if (old && typeof old.remove === 'function') old.remove();
                        } catch (e) {}
                        try { $('.daterangepicker').remove(); } catch (e) {}

                        const { start, end } = getHiddenDates();
                        const startDate = start ? moment(start, 'YYYY-MM-DD') : moment();
                        const endDate = end ? moment(end, 'YYYY-MM-DD') : moment();

                        $input.daterangepicker({
                            parentEl: 'body',
                            autoUpdateInput: false,
                            startDate,
                            endDate,
                            locale: {
                                format: 'YYYY-MM-DD',
                                cancelLabel: 'ล้าง',
                                applyLabel: 'เลือก',
                                daysOfWeek: ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'],
                                monthNames: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
                                firstDay: 0
                            },
                            ranges: {
                                'วันนี้': [moment().startOf('day'), moment().endOf('day')],
                                'เมื่อวาน': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                                '7 วันที่ผ่านมา': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
                                '30 วันที่ผ่านมา': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
                                'เดือนนี้': [moment().startOf('month').startOf('day'), moment().endOf('month').endOf('day')],
                                'เดือนที่ผ่านมา': [moment().subtract(1, 'month').startOf('month').startOf('day'), moment().subtract(1, 'month').endOf('month').endOf('day')]
                            }
                        });

                        // ผูก event apply/cancel
                        $input.off('apply.daterangepicker.dashboard').on('apply.daterangepicker.dashboard', function (ev, picker) {
                            const s = picker.startDate.format('YYYY-MM-DD');
                            const e = picker.endDate.format('YYYY-MM-DD');
                            $(this).val(s + ' ถึง ' + e);
                            setHiddenDates(s, e);
                            broadcastDateChanged();
                        });

                        $input.off('cancel.daterangepicker.dashboard').on('cancel.daterangepicker.dashboard', function () {
                            $(this).val('');
                            setHiddenDates('', '');
                            broadcastDateChanged();
                        });

                        // คืนค่า instance (ถ้าได้)
                        return $input.data('daterangepicker');
                    };



                    ensureDefaultToday(true);

                    // คืนค่า instance (ถ้าได้)
                    // return $input.data('daterangepicker');

                    // $input.off('apply.daterangepicker.dashboard', '#search_date').on('apply.daterangepicker.dashboard', '#search_date', function (ev, picker) {
                    //     const s = picker.startDate.format('YYYY-MM-DD');
                    //     const e = picker.endDate.format('YYYY-MM-DD');
                    //     $(this).val(`${s} ถึง ${e}`);
                    //     setHiddenDates(s, e);
                    //     broadcastDateChanged();
                    //     if (picker && typeof picker.hide === 'function') picker.hide();
                    // });
                    //
                    // $(document).off('cancel.daterangepicker.dashboard', '#search_date').on('cancel.daterangepicker.dashboard', '#search_date', function () {
                    //     $(this).val('');
                    //     setHiddenDates('', '');
                    //     broadcastDateChanged();
                    //     const drp = $(this).data('daterangepicker');
                    //     if (drp && typeof drp.hide === 'function') drp.hide();
                    // });
                    //
                    $(document).off('click.dashboardDate', '#search_date').on('click.dashboardDate', '#search_date', function () {
                        const $input = $(this);
                        initOn($input);
                        ensureDefaultToday(false);
                        const drp = $input.data('daterangepicker');
                        if (drp && typeof drp.show === 'function') drp.show();
                    });
                    //
                    // $(document).off('mousedown.dashboardDateClose').on('mousedown.dashboardDateClose', (ev) => {
                    //     if ($(ev.target).closest('.daterangepicker').length) return;
                    //     if ($(ev.target).closest('#search_date').length) return;
                    //     $(document).find('#search_date').each(function () {
                    //         const drp = $(this).data('daterangepicker');
                    //         if (drp && typeof drp.hide === 'function') drp.hide();
                    //     });
                    // });

                    window.addEventListener('dashboard:date-changed', () => {
                        const { start, end } = getHiddenDates();
                        if (start && end) {
                            if (self.filters.start !== start || self.filters.end !== end) {
                                self.setDateRangeValues(start, end);
                            } else {
                                self.scheduleRefresh('date-change');
                            }
                        }
                    });

                    broadcastDateChanged();
                },
                renderMoneyChart() {
                    const ctx = document.getElementById('chart-money');
                    if (!ctx) return;
                    if (!this.charts.money) {
                        this.charts.money = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: this.trends.labels,
                                datasets: [
                                    { label: 'ฝาก', data: this.trends.deposit, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.1)', fill: true },
                                    { label: 'ถอน', data: this.trends.withdraw, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.08)', fill: true },
                                    { label: 'โบนัส', data: this.trends.bonus, borderColor: '#6f42c1', backgroundColor: 'rgba(111,66,193,0.08)', fill: true },
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                legend: { display: true },
                                scales: {
                                    yAxes: [{ ticks: { beginAtZero: true } }]
                                }
                            }
                        });
                    } else {
                        this.charts.money.data.labels = this.trends.labels;
                        this.charts.money.data.datasets[0].data = this.trends.deposit;
                        this.charts.money.data.datasets[1].data = this.trends.withdraw;
                        this.charts.money.data.datasets[2].data = this.trends.bonus;
                        this.charts.money.update();
                    }
                },
                renderFunnelChart() {
                    const ctx = document.getElementById('chart-funnel');
                    if (!ctx) return;
                    const registerDeposit = Object.prototype.hasOwnProperty.call(this.funnel.funnel, 'register_deposit')
                        ? this.funnel.funnel.register_deposit
                        : this.funnel.funnel.register;
                    const registerRepeatDeposit = Object.prototype.hasOwnProperty.call(this.funnel.funnel, 'register_repeat_deposit')
                        ? this.funnel.funnel.register_repeat_deposit
                        : 0;
                    const labels = ['สมัครทั้งหมด', 'สมัครแล้วฝาก', 'สมัครแล้วฝากซ้ำ'];
                    const values = [
                        this.funnel.funnel.register,
                        registerDeposit,
                        registerRepeatDeposit
                    ];

                    if (!this.charts.funnel) {
                        this.charts.funnel = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels,
                                datasets: [{
                                    label: 'จำนวนคน',
                                    data: values,
                                    backgroundColor: ['#20c997', '#17a2b8', '#17a2b8']
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                legend: { display: false },
                                scales: { yAxes: [{ ticks: { beginAtZero: true } }] }
                            }
                        });
                    } else {
                        this.charts.funnel.data.labels = labels;
                        this.charts.funnel.data.datasets[0].data = values;
                        this.charts.funnel.update();
                    }
                },
                memberListTitle(type) {
                    if (type === 'referral_total') {
                        return 'รายชื่อสมาชิกจากเพื่อนชวน';
                    }
                    if (type === 'referral_deposit') {
                        return 'รายชื่อสมาชิกจากเพื่อนชวนที่ฝากแล้ว';
                    }
                    if (type === 'referral_not_deposit') {
                        return 'รายชื่อสมาชิกจากเพื่อนชวนที่ยังไม่ฝาก';
                    }
                    if (type === 'register_not_deposit') {
                        return 'รายชื่อสมาชิกที่ยังไม่ฝาก';
                    }
                    if (type === 'register_repeat_deposit') {
                        return 'รายชื่อสมาชิกที่สมัครแล้วฝากซ้ำ (ครั้งที่ 2 ขึ้นไป)';
                    }
                    if (type === 'first_deposit') {
                        return 'รายชื่อสมาชิกที่ฝากครั้งแรก';
                    }
                    if (type === 'repeat_deposit') {
                        return 'รายชื่อสมาชิกที่ฝากซ้ำ (ครั้งที่ 2 ขึ้นไป)';
                    }
                    return 'รายชื่อสมาชิกที่ฝากแล้ว';
                },
                activeLottoRiskRows() {
                    if (this.lottoRiskTab === 'highest') {
                        return Array.isArray(this.summary.lotto_top_risky_numbers)
                            ? this.summary.lotto_top_risky_numbers
                            : [];
                    }

                    return Array.isArray(this.summary.top_risky_numbers)
                        ? this.summary.top_risky_numbers
                        : [];
                },
                openMemberList(type) {
                    if (!axios || typeof axios.post !== 'function') return;
                    if (!dashboardRoutes.memberList) return;
                    const listType = type || 'register_deposit';
                    this.memberList.type = listType;
                    this.memberList.title = this.memberListTitle(listType);
                    this.memberList.items = [];
                    this.memberList.total = 0;
                    this.memberList.loading = true;

                    const payload = this.buildPayload({ type: listType, limit: this.memberList.limit });
                    const $modal = $('#member-list-modal');
                    if ($modal && typeof $modal.modal === 'function') {
                        $modal.modal('show');
                    }

                    axios.post(dashboardRoutes.memberList, payload)
                        .then((res) => {
                            const data = this.normalizeResponse(res);
                            if (data && Array.isArray(data.items)) {
                                this.memberList.items = data.items;
                                this.memberList.total = typeof data.total === 'number' ? data.total : data.items.length;
                                this.memberList.limit = typeof data.limit === 'number' ? data.limit : this.memberList.limit;
                            } else {
                                this.memberList.items = [];
                                this.memberList.total = 0;
                            }
                        })
                        .catch(() => {
                            this.memberList.items = [];
                            this.memberList.total = 0;
                        })
                        .then(() => {
                            this.memberList.loading = false;
                        });
                },
                openTopRiskyDetail(type, row) {
                    const detailType = type === 'rounds' ? 'rounds' : 'markets';
                    const rowNumber = this.uiValue(row && row.number, '-');
                    const rowBetType = this.formatLottoRiskBetType(row && row.bet_type);
                    const rounds = Array.isArray(row && row.rounds) ? row.rounds : [];

                    if (detailType === 'markets') {
                        const markets = Array.isArray(row && row.markets) ? row.markets : [];
                        this.topRiskyDetail.title = 'รายการความเสี่ยงรายตลาด';
                        this.topRiskyDetail.subtitle = `เลข ${rowNumber} (${rowBetType})`;
                        this.topRiskyDetail.fields = [
                            { key: 'rank', label: 'อันดับ', sortable: true, class: 'text-right' },
                            { key: 'name', label: 'ตลาด', sortable: true },
                            { key: 'total_stake', label: 'ยอดแทง', sortable: true, class: 'text-right' },
                            { key: 'total_risk', label: 'Liability (ประมาณการ)', sortable: true, class: 'text-right' },
                            { key: 'contribution_display', label: 'สัดส่วนความเสี่ยง', sortable: true, class: 'text-right' },
                        ];
                        this.topRiskyDetail.items = markets.map((market) => ({
                            rank: Number((market && market.rank) || 0),
                            name: this.uiValue(market && market.name, '-'),
                            total_stake: this.uiValue(market && market.total_stake, '0.00'),
                            total_risk: this.uiValue(market && market.total_risk, '0.00'),
                            contribution_display: `${Number((market && market.contribution_percent) || 0).toFixed(2)}%`,
                        }));
                    } else {
                        this.topRiskyDetail.title = 'รายการความเสี่ยงรายงวด';
                        this.topRiskyDetail.subtitle = `เลข ${rowNumber} (${rowBetType})`;
                        this.topRiskyDetail.fields = [
                            { key: 'rank', label: 'อันดับ', sortable: true, class: 'text-right' },
                            { key: 'id', label: 'รหัสงวด', sortable: true },
                            { key: 'draw_date', label: 'วันที่ออกรางวัล', sortable: true },
                            { key: 'market_name', label: 'ตลาด', sortable: true },
                            { key: 'result_number_display', label: 'เลขที่ออก', sortable: true },
                            { key: 'total_risk', label: 'Liability (ประมาณการ)', sortable: true, class: 'text-right' },
                            { key: 'potential_payout', label: 'Potential Payout', sortable: true, class: 'text-right' },
                            { key: 'actual_payout', label: 'Actual Payout', sortable: true, class: 'text-right' },
                            { key: 'net_result', label: 'Gap (Potential-Actual)', sortable: true, class: 'text-right' },
                            { key: 'draw_status', label: 'สถานะงวด', sortable: true },
                        ];
                        this.topRiskyDetail.items = rounds.map((round) => ({
                            rank: Number((round && round.rank) || 0),
                            id: Number((round && round.id) || 0),
                            draw_date: this.uiValue(round && round.draw_date, '-'),
                            market_name: this.uiValue(round && round.market_name, '-'),
                            result_number_display: this.uiValue(round && round.result_number_display, '-'),
                            total_risk: this.uiValue(round && round.total_risk, '0.00'),
                            potential_payout: this.uiValue(round && round.potential_payout, '0.00'),
                            actual_payout: round && round.actual_settlement_pending ? '-' : this.uiValue(round && round.actual_payout, '0.00'),
                            net_result: round && round.actual_settlement_pending ? '-' : this.uiValue(round && round.net_result, '0.00'),
                            draw_status: this.topRiskyRoundStatusLabel(round),
                        }));
                    }

                    const $modal = $('#top-risky-detail-modal');
                    if ($modal && typeof $modal.modal === 'function') {
                        $modal.modal('show');
                    }
                },
                topRiskyRoundStatusLabel(round) {
                    const status = String((round && round.status) || '').trim().toLowerCase();
                    if (status === 'resulted') return 'ออกผลแล้ว';
                    if (status === 'closed') return 'ปิดรับรอผล';
                    if (status === 'open') return 'เปิดรับแทง';
                    if (status === 'cancelled') return 'ยกเลิก';
                    return status ? status : '-';
                },
                topRiskyRoundResultTime(round) {
                    const resultAt = String((round && round.result_at) || '').trim();
                    if (resultAt) {
                        return resultAt;
                    }

                    const status = String((round && round.status) || '').trim().toLowerCase();
                    if (status === 'resulted') {
                        return 'ออกผลแล้ว (ไม่พบเวลา)';
                    }

                    const closeAt = String((round && round.close_at) || '').trim();
                    if (closeAt && (status === 'closed' || status === 'open' || status === 'draft')) {
                        return `ยังไม่ออกผล (ปิดรับ ${closeAt})`;
                    }

                    return 'ยังไม่ออกผล';
                }
            }
        });

    </script>

    {{--    <script type="module">--}}
    {{--        // --- Global Auto-Reload (ทุก component ที่มี loadData()) -----}}
    {{--        Vue.mixin({--}}
    {{--            refreshMs: 15000,--}}

    {{--            mounted() {--}}
    {{--                if (typeof this.loadData !== 'function') return;--}}

    {{--                const ms = Number(this.$options.refreshMs ?? this.$options.refreshInterval ?? this.refreshMs);--}}
    {{--                if (!ms || ms <= 0) return;--}}

    {{--                this.__autoBusy = false;--}}

    {{--                try { this.loadData(); } catch(e){}--}}

    {{--                this.__autoTimer = setInterval(async () => {--}}
    {{--                    if (this.__autoBusy || this._isBeingDestroyed || this._isDestroyed) return;--}}
    {{--                    try {--}}
    {{--                        this.__autoBusy = true;--}}
    {{--                        const ret = this.loadData();--}}
    {{--                        if (ret && typeof ret.then === 'function') await ret;--}}
    {{--                    } catch(e) {--}}
    {{--                        console.warn('[auto-reload] loadData error:', e?.message || e);--}}
    {{--                    } finally {--}}
    {{--                        this.__autoBusy = false;--}}
    {{--                    }--}}
    {{--                }, ms);--}}
    {{--            },--}}

    {{--            beforeDestroy() {--}}
    {{--                if (this.__autoTimer) clearInterval(this.__autoTimer);--}}
    {{--            }--}}
    {{--        });--}}
    {{--    </script>--}}

    {{-- ✅ daterangepicker: อัปเดต hidden input และ broadcast event ให้ทุกกล่องโหลดใหม่ --}}
    <script>
        (function dashboardDatepickerOnDemand() {
            if (window.__DASHBOARD_DATEPICKER_INITED__) return;
            window.__DASHBOARD_DATEPICKER_INITED__ = true;

            function setHiddenDates(start, end) {
                const s = start || '';
                const e = end || '';
                const elS = document.getElementById('startDate');
                const elE = document.getElementById('endDate');
                if (elS) elS.value = s;
                if (elE) elE.value = e;
            }

            function getHiddenDates() {
                const elS = document.getElementById('startDate');
                const elE = document.getElementById('endDate');
                return {
                    start: elS && typeof elS.value !== 'undefined' ? elS.value : '',
                    end: elE && typeof elE.value !== 'undefined' ? elE.value : ''
                };
            }

            function broadcastDateChanged() {
                window.dispatchEvent(new CustomEvent('dashboard:date-changed'));
            }

            function ensureDefaultToday(force = false) {
                const $el = $('#search_date');
                if (!$el.length) return;

                // ถ้าช่องว่าง (หรือ force) ให้เติม วันนี้ ถึง วันนี้
                const cur = String($el.val() || '').trim();
                if (!force && cur !== '') return;

                const today = moment().format('YYYY-MM-DD');
                setHiddenDates(today, today);
                $el.val(today + ' ถึง ' + today);
            }

            function initOn($input) {
                // กันค้างซ้อนจาก element เก่าที่ถูก replace
                try {
                    $input.off('.daterangepicker');
                    const old = $input.data('daterangepicker');
                    if (old && typeof old.remove === 'function') old.remove();
                } catch (e) {}

                // ลบ popup เก่าที่ orphan ทิ้งไว้ (ช่วยเคส data undefined + popup ค้าง)
                try { $('.daterangepicker').remove(); } catch (e) {}

                // init ใหม่
                $input.daterangepicker({
                    parentEl: 'body',
                    autoUpdateInput: false,
                    startDate: moment(),
                    endDate: moment(),
                    locale: {
                        format: 'YYYY-MM-DD',
                        cancelLabel: 'ล้าง',
                        applyLabel: 'เลือก',
                        daysOfWeek: ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'],
                        monthNames: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
                        firstDay: 0
                    },
                    ranges: {
                        'วันนี้': [moment().startOf('day'), moment().endOf('day')],
                        'เมื่อวาน': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                        '7 วันที่ผ่านมา': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
                        '30 วันที่ผ่านมา': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
                        'เดือนนี้': [moment().startOf('month').startOf('day'), moment().endOf('month').endOf('day')],
                        'เดือนที่ผ่านมา': [moment().subtract(1, 'month').startOf('month').startOf('day'), moment().subtract(1, 'month').endOf('month').endOf('day')]
                    }
                });

                // ผูก event apply/cancel
                $input.off('apply.daterangepicker.dashboard').on('apply.daterangepicker.dashboard', function (ev, picker) {
                    const s = picker.startDate.format('YYYY-MM-DD');
                    const e = picker.endDate.format('YYYY-MM-DD');
                    $(this).val(s + ' ถึง ' + e);
                    setHiddenDates(s, e);
                    broadcastDateChanged();
                });

                $input.off('cancel.daterangepicker.dashboard').on('cancel.daterangepicker.dashboard', function () {
                    $(this).val('');
                    setHiddenDates('', '');
                    broadcastDateChanged();
                });

                // คืนค่า instance (ถ้าได้)
                return $input.data('daterangepicker');
            }

            // ตั้งค่า default วันแรกเข้า (ให้ payload มีวันตั้งแต่แรก)
            const today = (window.moment ? moment().format('YYYY-MM-DD') : new Date().toISOString().slice(0,10));

            setHiddenDates(today, today);

            // ✅ เติมค่าให้ทันที
            ensureDefaultToday(true);

// ✅ เผื่อ Vue/สคริปต์อื่น replace DOM ทีหลัง
            $(document).ready(function () { ensureDefaultToday(false); });
            $(window).on('load', function () { ensureDefaultToday(false); });

// ✅ กันเคส replace หลัง ready แบบเร็ว ๆ
            requestAnimationFrame(() => ensureDefaultToday(false));
            requestAnimationFrame(() => ensureDefaultToday(false));

            // $('#search_date').val(today + ' ถึง ' + today);

            // ✅ On-demand: ใช้ delegated handler กัน element ถูก replace
            $(document).off('click.dashboardDate', '#search_date').on('click.dashboardDate', '#search_date', function () {
                const $input = $(this);

                let drp = $input.data('daterangepicker');
                if (!drp) {
                    drp = initOn($input);
                }

                // ✅ ถ้าก่อนเปิดยังว่าง ให้เติม default
                ensureDefaultToday(false);

                if (drp && typeof drp.show === 'function') {
                    drp.show();
                } else {
                    try { $input.trigger('focus'); } catch (e) {}
                }
            });


            // ถ้ามี hidden date อยู่แล้ว ให้ sync text input ตามนั้น (กันรีเฟรชบางแบบ)
            // const { start, end } = getHiddenDates();
            // if (start && end) {
            //     $('#search_date').val(start + ' ถึง ' + end);
            // }

            // ยิงโหลดรอบแรก
            broadcastDateChanged();
        })();
    </script>



    <script>
        window.app = new Vue({
            data: function () {
                return {
                    loopcnts: 0,
                    announce: '',
                    pushmenu: '',
                    toast: '',
                    withdraw_cnt: 0,
                    played: false
                }
            },
            created() {
                const self = this;
                self.autoCnt(false);
            },
            watch: {
                withdraw_cnt: function (event) {
                    if (event > 0) {
                        this.ToastPlay();
                    }
                }
            },
            methods: {
                editdata(code, status, method) {
                    this.$bvModal.msgBoxConfirm('ต้องการดำเนินการ ใช่หรือไม่.', {
                        title: 'โปรดยืนยันการทำรายการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'ตกลง',
                        cancelTitle: 'ยกเลิก',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        centered: true
                    })
                        .then(value => {
                            if (value) {
                                this.$http.post("{{ url($menu->currentRoute.'/edit') }}", {
                                    id: code,
                                    status: status,
                                    method: method
                                })
                                    .then(response => {
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
                                        console.log('error');
                                    });
                            }
                        })
                        .catch(err => {
                            // An error occurred
                        })
                },

                autoCnt(draw) {
                    const self = this;
                    this.toast = window.Toasty;
                    this.loadCnt();
                },

                runMarquee() {
                    this.announce = $('#announce');
                    this.announce.marquee({
                        duration: 20000,
                        startVisible: false
                    });
                },

                ToastPlay() {
                    this.toast.error('<span class="text-danger">มีการถอนรายการใหม่</span>');
                },

                async loadCnt() {
                    let err, response;
                    [err, response] = await axios.get("{{ route('admin.home.loadcnt') }}").then(data => {
                        return [null, data];
                    }).catch(err => [err]);
                    if (err) {
                        return 0;
                    }

                    const res = response.data;

                    if(res.bank_in_today > 0){
                        updateBadge('bank_in', res.bank_in_today);
                    }else{
                        update('bank_in', res.bank_in_today);
                    }
                    if(res.bank_in > 0){
                        updateBadge('bank_in_old', res.bank_in);
                    }else{
                        update('bank_in_old', res.bank_in);
                    }
                    if(res.withdraw > 0){
                        updateBadge('withdraw', res.withdraw);
                    }else{
                        update('withdraw', res.withdraw);
                    }
                    if(res.lotto_tickets > 0){
                        updateBadge('lotto_tickets', res.lotto_tickets);
                    }else{
                        update('lotto_tickets', res.lotto_tickets);
                    }
                    if(res.withdraw > 0){
                        updateBadge('withdraw_free', res.withdraw_free);
                    }else{
                        update('withdraw_free', res.withdraw_free);
                    }

                    // ✅ กันพัง: หน้าไหนไม่มี #announce ก็ไม่ต้องทำต่อ
                    const announceEl = document.getElementById('announce');

                    if (this.loopcnts == 0) {
                        if (announceEl) {
                            announceEl.textContent = response.data.announce;
                            this.runMarquee();
                        }
                    } else {
                        if (announceEl && response.data.announce_new == 'Y') {
                            this.announce.on('finished', (event) => {
                                announceEl.textContent = response.data.announce;
                                this.announce.trigger('destroy');
                                this.announce.off('finished');
                                this.runMarquee();
                            });
                        }
                    }

                    this.withdraw_cnt = response.data.withdraw;
                }
            }
        });
    </script>
@endpush

