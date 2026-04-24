@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <winning-report-app></winning-report-app>
@endsection

@push('styles')
    <style>
        .wr-shell {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 50%, #f0fdf4 100%);
            border-radius: 16px;
            padding: 14px;
        }

        .wr-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .wr-title {
            font-weight: 700;
            color: #0f172a;
            letter-spacing: 0.2px;
        }

        .wr-subtitle {
            color: #475569;
            font-size: 0.85rem;
        }

        .wr-kpi-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
        }

        .wr-kpi {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 12px;
            background: #f8fafc;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        .wr-kpi:hover {
            box-shadow: 0 10px 22px rgba(30, 41, 59, 0.09);
            transform: translateY(-1px);
        }

        .wr-kpi__label {
            color: #64748b;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .wr-kpi__value {
            color: #0f172a;
            font-weight: 700;
            font-size: 1rem;
            margin-top: 4px;
        }

        .wr-status {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 2px 10px;
            font-size: 0.75rem;
            font-weight: 700;
            border: 1px solid transparent;
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
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px;
            background: #ffffff;
        }

        .wr-filter-label {
            color: #334155;
            font-size: 0.78rem;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .wr-table-wrap {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }

        .wr-table {
            margin-bottom: 0;
            font-size: 0.83rem;
        }

        .wr-table thead th {
            position: sticky;
            top: 0;
            background: #f8fafc;
            z-index: 2;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.45px;
            color: #475569;
            border-bottom: 1px solid #cbd5e1;
            white-space: nowrap;
        }

        .wr-table tbody tr:hover {
            background: #f8fafc;
        }

        .wr-table td,
        .wr-table th {
            vertical-align: middle;
            white-space: nowrap;
        }

        .wr-empty {
            color: #64748b;
            text-align: center;
            padding: 18px 10px;
        }

        .wr-actions .btn {
            min-width: 110px;
        }

        .wr-loading {
            color: #334155;
            font-size: 0.83rem;
        }

        .wr-error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 0.82rem;
        }

        .wr-pending {
            color: #854d0e;
            font-weight: 600;
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

            .wr-actions .btn {
                min-width: 0;
                width: 100%;
                margin-bottom: 6px;
            }

            .wr-actions {
                width: 100%;
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
                    <div class="wr-card p-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-start mb-2">
                            <div>
                                <div class="wr-title">Winning Report / Settlement Report</div>
                                <div class="wr-subtitle">อ่านข้อมูลจาก materialized records เท่านั้น</div>
                            </div>
                            <div class="wr-subtitle mt-1">Updated: @{{ nowLabel }}</div>
                        </div>

                        <div class="row">
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <div class="wr-filter-block">
                                    <div class="wr-filter-label">Round ID</div>
                                    <input v-model.number="filters.round_id" type="number" min="1" class="form-control form-control-sm" @keyup.enter.prevent="loadAll">
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <div class="wr-filter-block">
                                    <div class="wr-filter-label">Date</div>
                                    <input v-model="filters.date" type="date" class="form-control form-control-sm" @change="loadSummaryOnly">
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <div class="wr-filter-block">
                                    <div class="wr-filter-label">Lottery Type</div>
                                    <select v-model="filters.lottery_type" class="form-control form-control-sm" @change="loadSummaryOnly">
                                        <option value="">ทั้งหมด</option>
                                        <option v-for="item in lotteryTypeOptions" :key="item" :value="item">@{{ item }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <div class="wr-filter-block">
                                    <div class="wr-filter-label">Market</div>
                                    <select v-model="filters.market" class="form-control form-control-sm" @change="loadSummaryOnly">
                                        <option value="">ทั้งหมด</option>
                                        <option v-for="item in marketOptions" :key="item" :value="item">@{{ item }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <div class="wr-filter-block">
                                    <div class="wr-filter-label">User ID</div>
                                    <input v-model.number="detailFilters.user_id" type="number" min="1" class="form-control form-control-sm" @keyup.enter.prevent="loadDetails">
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <div class="wr-filter-block">
                                    <div class="wr-filter-label">Bet Type</div>
                                    <input v-model="detailFilters.bet_type" type="text" class="form-control form-control-sm" placeholder="top_3" @keyup.enter.prevent="loadDetails">
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <div class="wr-filter-block">
                                    <div class="wr-filter-label">Number</div>
                                    <input v-model="detailFilters.number" type="text" class="form-control form-control-sm" placeholder="123" @keyup.enter.prevent="loadDetails">
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                                <div class="wr-filter-block">
                                    <div class="wr-filter-label">Status</div>
                                    <select v-model="detailFilters.status" class="form-control form-control-sm" @change="loadDetails">
                                        <option value="">ทั้งหมด</option>
                                        <option value="pending">pending</option>
                                        <option value="settled">settled</option>
                                        <option value="credited">credited</option>
                                        <option value="failed">failed</option>
                                        <option value="voided">voided</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-8 col-md-9 col-sm-6 mb-2 d-flex align-items-end">
                                <div class="w-100 d-flex flex-wrap justify-content-end wr-actions">
                                    <button type="button" class="btn btn-sm btn-outline-secondary mr-2" @click.prevent="resetFilters">Reset</button>
                                    <button type="button" class="btn btn-sm btn-primary mr-2" @click.prevent="loadAll">Apply</button>
                                    <button type="button" class="btn btn-sm btn-success mr-2" @click.prevent="exportReport('summary', 'csv')">Summary CSV</button>
                                    <button type="button" class="btn btn-sm btn-outline-success mr-2" @click.prevent="exportReport('users', 'csv')">Users CSV</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click.prevent="exportReport('bets', 'xlsx')">Bets XLSX</button>
                                </div>
                            </div>
                        </div>

                        <div v-if="isLoading" class="wr-loading mb-2">กำลังโหลดข้อมูล...</div>
                        <div v-if="!hasMaterializedReportData" class="wr-error mb-2">
                            ยังไม่มีข้อมูลรายงานที่ materialized แล้ว กรุณา settle รอบใหม่ หรือรัน backfill สำหรับรอบเก่าก่อน
                        </div>
                        <div v-if="errorMessage" class="wr-error mb-2">@{{ errorMessage }}</div>

                        <div class="wr-kpi-grid mt-2">
                            <div class="wr-kpi">
                                <div class="wr-kpi__label">Total Stake</div>
                                <div class="wr-kpi__value">@{{ fm(summary.total_stake) }}</div>
                            </div>
                            <div class="wr-kpi">
                                <div class="wr-kpi__label">Total Payout</div>
                                <div class="wr-kpi__value">@{{ fm(summary.total_payout) }}</div>
                            </div>
                            <div class="wr-kpi">
                                <div class="wr-kpi__label">Net Profit/Loss</div>
                                <div class="wr-kpi__value">@{{ fm(summary.net_profit_loss) }}</div>
                            </div>
                            <div class="wr-kpi">
                                <div class="wr-kpi__label">Winner Count</div>
                                <div class="wr-kpi__value">@{{ intValue(summary.winner_count) }}</div>
                            </div>
                            <div class="wr-kpi">
                                <div class="wr-kpi__label">Winning Ticket Count</div>
                                <div class="wr-kpi__value">@{{ intValue(summary.winning_ticket_count) }}</div>
                            </div>
                            <div class="wr-kpi">
                                <div class="wr-kpi__label">Settlement Status</div>
                                <div class="wr-kpi__value">
                                    <span :class="statusClass(summary.settlement_status)">@{{ summary.settlement_status || '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12 mb-3">
                            <div class="wr-card p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="wr-title">Users</div>
                                    <div class="wr-subtitle">Rows: @{{ users.length }}</div>
                                </div>
                                <div class="wr-table-wrap table-responsive" style="max-height: 380px;">
                                    <table class="table table-sm wr-table">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th class="text-right">Total Stake</th>
                                                <th class="text-right">Total Payout</th>
                                                <th class="text-right">Net by User</th>
                                                <th class="text-center">Winning Bet Count</th>
                                                <th>Winning Numbers</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="row in users" :key="row.user_id">
                                                <td>@{{ row.username || ('USER-' + row.user_id) }}</td>
                                                <td class="text-right">@{{ fm(row.total_stake) }}</td>
                                                <td class="text-right">@{{ fm(row.total_payout) }}</td>
                                                <td class="text-right">@{{ fm(row.net_by_user) }}</td>
                                                <td class="text-center">@{{ intValue(row.winning_bet_count) }}</td>
                                                <td style="max-width: 280px; overflow: hidden; text-overflow: ellipsis;">@{{ row.winning_numbers || '-' }}</td>
                                                <td><span :class="statusClass(row.credited_status)">@{{ row.credited_status || '-' }}</span></td>
                                            </tr>
                                            <tr v-if="users.length === 0">
                                                <td colspan="7" class="wr-empty">ไม่มีข้อมูลผู้ใช้</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="wr-card p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="wr-title">Winning Bets</div>
                                    <div class="wr-subtitle">Rows: @{{ bets.length }}</div>
                                </div>
                                <div class="wr-table-wrap table-responsive" style="max-height: 420px;">
                                    <table class="table table-sm wr-table">
                                        <thead>
                                            <tr>
                                                <th>Ticket No</th>
                                                <th>Bet Type</th>
                                                <th>Number</th>
                                                <th class="text-right">Stake</th>
                                                <th class="text-right">Odds</th>
                                                <th class="text-right">Payout</th>
                                                <th>Result Number</th>
                                                <th>Matched Rule</th>
                                                <th>Batch</th>
                                                <th>Settled At</th>
                                                <th>Credited At</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="row in bets" :key="row.ticket_no + '-' + row.number + '-' + row.settlement_batch_id">
                                                <td>@{{ row.ticket_no || '-' }}</td>
                                                <td>@{{ row.bet_type || '-' }}</td>
                                                <td>@{{ row.number || '-' }}</td>
                                                <td class="text-right">@{{ fm(row.stake) }}</td>
                                                <td class="text-right">@{{ fm(row.odds, 4) }}</td>
                                                <td class="text-right">@{{ fm(row.payout) }}</td>
                                                <td>@{{ row.result_number || '-' }}</td>
                                                <td>@{{ row.matched_rule || '-' }}</td>
                                                <td>@{{ row.settlement_batch_id || '-' }}</td>
                                                <td>@{{ dt(row.settled_at) }}</td>
                                                <td>@{{ dt(row.credited_at) }}</td>
                                                <td><span :class="statusClass(row.status)">@{{ row.status || '-' }}</span></td>
                                            </tr>
                                            <tr v-if="bets.length === 0">
                                                <td colspan="12" class="wr-empty">ไม่มีข้อมูลรายการเดิมพัน</td>
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
                    filters: {
                        round_id: @json($initialRoundId ?? null),
                        date: @json($initialDate ?? ''),
                        lottery_type: '',
                        market: '',
                    },
                    detailFilters: {
                        user_id: null,
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
                };
            },
            mounted() {
                this.nowLabel = this.formatNow();
                if (this.filters.round_id) {
                    this.loadAll();
                } else {
                    this.loadSummaryOnly();
                }
            },
            methods: {
                formatNow() {
                    return new Date().toLocaleString();
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
                async loadSummaryOnly() {
                    this.errorMessage = '';
                    this.isLoading = true;
                    this.nowLabel = this.formatNow();

                    try {
                        const summaryQ = this.query(this.filters);
                        const summaryRes = await axios.get('{{ route('admin.lotto.winning_report.summary') }}?' + summaryQ);
                        this.summary = summaryRes.data.summary || {};
                        this.summary.round_ids = summaryRes.data.round_ids || [];
                        this.summary.latest_round_id = summaryRes.data.latest_round_id || null;
                    } catch (error) {
                        this.handleError(error, 'โหลด summary ไม่สำเร็จ');
                    } finally {
                        this.isLoading = false;
                    }
                },
                async loadDetails() {
                    if (!this.filters.round_id) {
                        this.users = [];
                        this.bets = [];
                        return;
                    }

                    const common = {
                        round_id: this.filters.round_id,
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

                    const usersRes = await axios.get('{{ route('admin.lotto.winning_report.users') }}?' + usersQuery);
                    this.users = usersRes.data.data || [];

                    const betsRes = await axios.get('{{ route('admin.lotto.winning_report.bets') }}?' + betsQuery);
                    this.bets = betsRes.data.data || [];
                },
                async loadAll() {
                    this.errorMessage = '';
                    this.isLoading = true;
                    this.nowLabel = this.formatNow();

                    try {
                        await this.loadSummaryOnly();

                         if (!this.filters.round_id && this.summary.latest_round_id) {
                            this.filters.round_id = this.summary.latest_round_id;
                        }

                        if (!this.filters.round_id) {
                            this.users = [];
                            this.bets = [];
                            this.errorMessage = 'ไม่พบ Round ที่พร้อมรายงานตามเงื่อนไขที่เลือก';
                            return;
                        }

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
                        round_id: null,
                        date: '',
                        lottery_type: '',
                        market: '',
                    };
                    this.detailFilters = {
                        user_id: null,
                        bet_type: '',
                        number: '',
                        status: '',
                    };
                    this.summary = {};
                    this.users = [];
                    this.bets = [];
                    this.errorMessage = '';
                    this.nowLabel = this.formatNow();
                },
                exportReport(level, format) {
                    if (!this.filters.round_id) {
                        this.errorMessage = 'กรุณาระบุ Round ID ก่อน export';
                        return;
                    }

                    const q = this.query({
                        round_id: this.filters.round_id,
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
