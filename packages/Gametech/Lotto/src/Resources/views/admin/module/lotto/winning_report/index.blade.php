@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <winning-report-app></winning-report-app>
@endsection

@push('scripts')
    <script type="text/x-template" id="winning-report-app-template">
        <section class="content text-sm">
            <div class="container-fluid">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Winning Report</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 mb-2">
                                <label>Round ID</label>
                                <input v-model="filters.round_id" type="number" min="1" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label>Date</label>
                                <input v-model="filters.date" type="date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label>Lottery Type</label>
                                <select v-model="filters.lottery_type" class="form-control form-control-sm">
                                    <option value="">ทั้งหมด</option>
                                    <option v-for="item in lotteryTypeOptions" :key="item" :value="item">@{{ item }}</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label>Market</label>
                                <select v-model="filters.market" class="form-control form-control-sm">
                                    <option value="">ทั้งหมด</option>
                                    <option v-for="item in marketOptions" :key="item" :value="item">@{{ item }}</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2 d-flex align-items-end">
                                <button class="btn btn-sm btn-primary mr-1" @click="loadAll">Load</button>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-2 col-6 mb-2"><div class="small text-muted">total_stake</div><div class="font-weight-bold">@{{ fm(summary.total_stake) }}</div></div>
                            <div class="col-md-2 col-6 mb-2"><div class="small text-muted">total_payout</div><div class="font-weight-bold">@{{ fm(summary.total_payout) }}</div></div>
                            <div class="col-md-2 col-6 mb-2"><div class="small text-muted">net_profit_loss</div><div class="font-weight-bold">@{{ fm(summary.net_profit_loss) }}</div></div>
                            <div class="col-md-2 col-6 mb-2"><div class="small text-muted">winner_count</div><div class="font-weight-bold">@{{ summary.winner_count || 0 }}</div></div>
                            <div class="col-md-2 col-6 mb-2"><div class="small text-muted">winning_ticket_count</div><div class="font-weight-bold">@{{ summary.winning_ticket_count || 0 }}</div></div>
                            <div class="col-md-2 col-6 mb-2"><div class="small text-muted">settlement_status</div><div class="font-weight-bold">@{{ summary.settlement_status || '-' }}</div></div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Users</h5>
                            <div>
                                <button class="btn btn-sm btn-outline-success" @click="exportReport('users','csv')">Export CSV</button>
                                <button class="btn btn-sm btn-outline-primary" @click="exportReport('users','xlsx')">Export XLSX</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                <tr>
                                    <th>username</th>
                                    <th>total_stake</th>
                                    <th>total_payout</th>
                                    <th>net_by_user</th>
                                    <th>winning_bet_count</th>
                                    <th>winning_numbers</th>
                                    <th>credited_status</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="row in users" :key="row.user_id">
                                    <td>@{{ row.username || row.user_id }}</td>
                                    <td>@{{ fm(row.total_stake) }}</td>
                                    <td>@{{ fm(row.total_payout) }}</td>
                                    <td>@{{ fm(row.net_by_user) }}</td>
                                    <td>@{{ row.winning_bet_count }}</td>
                                    <td>@{{ row.winning_numbers }}</td>
                                    <td>@{{ row.credited_status }}</td>
                                </tr>
                                <tr v-if="users.length === 0"><td colspan="7" class="text-center">ไม่มีข้อมูล</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="mb-2 mt-4">Bets</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                <tr>
                                    <th>ticket_no</th>
                                    <th>bet_type</th>
                                    <th>number</th>
                                    <th>stake</th>
                                    <th>odds</th>
                                    <th>payout</th>
                                    <th>result_number</th>
                                    <th>matched_rule</th>
                                    <th>settlement_batch_id</th>
                                    <th>settled_at</th>
                                    <th>credited_at</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="row in bets" :key="row.ticket_no + '-' + row.number + '-' + row.settlement_batch_id">
                                    <td>@{{ row.ticket_no }}</td>
                                    <td>@{{ row.bet_type }}</td>
                                    <td>@{{ row.number }}</td>
                                    <td>@{{ fm(row.stake) }}</td>
                                    <td>@{{ row.odds }}</td>
                                    <td>@{{ fm(row.payout) }}</td>
                                    <td>@{{ row.result_number }}</td>
                                    <td>@{{ row.matched_rule }}</td>
                                    <td>@{{ row.settlement_batch_id }}</td>
                                    <td>@{{ row.settled_at || '-' }}</td>
                                    <td>@{{ row.credited_at || '-' }}</td>
                                </tr>
                                <tr v-if="bets.length === 0"><td colspan="11" class="text-center">ไม่มีข้อมูล</td></tr>
                                </tbody>
                            </table>
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
                        date: '',
                        lottery_type: '',
                        market: '',
                    },
                    summary: {},
                    users: [],
                    bets: [],
                };
            },
            mounted() {
                if (this.filters.round_id) {
                    this.loadAll();
                }
            },
            methods: {
                fm(value) {
                    if (value === null || value === undefined || value === '') {
                        return 'Pending';
                    }

                    const num = Number(value);
                    return Number.isNaN(num) ? '-' : num.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                },
                query(params) {
                    return Object.keys(params)
                        .filter((key) => params[key] !== null && params[key] !== '' && params[key] !== undefined)
                        .map((key) => encodeURIComponent(key) + '=' + encodeURIComponent(params[key]))
                        .join('&');
                },
                async loadAll() {
                    try {
                        const summaryQ = this.query(this.filters);
                        const summaryRes = await axios.get('{{ route('admin.lotto.winning_report.summary') }}?' + summaryQ);
                        this.summary = summaryRes.data.summary || {};

                        if (!this.filters.round_id) {
                            this.users = [];
                            this.bets = [];
                            return;
                        }

                        const userQ = this.query({round_id: this.filters.round_id, per_page: 100});
                        const usersRes = await axios.get('{{ route('admin.lotto.winning_report.users') }}?' + userQ);
                        this.users = usersRes.data.data || [];

                        const betQ = this.query({round_id: this.filters.round_id, per_page: 100});
                        const betsRes = await axios.get('{{ route('admin.lotto.winning_report.bets') }}?' + betQ);
                        this.bets = betsRes.data.data || [];
                    } catch (error) {
                        const message = error?.response?.data?.message || 'โหลดรายงานไม่สำเร็จ';
                        alert(message);
                    }
                },
                exportReport(level, format) {
                    if (!this.filters.round_id) {
                        alert('กรุณาระบุ round_id ก่อน export');
                        return;
                    }

                    const q = this.query({round_id: this.filters.round_id, level, format});
                    window.open('{{ route('admin.lotto.winning_report.export') }}?' + q, '_blank');
                },
            },
        });
    </script>
@endpush
