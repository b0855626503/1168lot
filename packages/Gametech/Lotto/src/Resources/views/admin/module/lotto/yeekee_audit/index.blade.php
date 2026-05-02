@extends('admin::layouts.master')

@section('title')
    Yeekee Audit
@endsection

@section('content')
    <yeekee-audit-app ref="auditApp"></yeekee-audit-app>
@endsection

@push('scripts')
    <script type="text/x-template" id="yeekee-audit-app-template">
        <section class="content text-sm">
            <div class="container-fluid">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Yeekee Audit — ตรวจสอบการยิงเลข</h3>
                    </div>
                    <div class="card-body">

                        <div class="row align-items-end mb-3">
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label class="mb-1">ตลาด</label>
                                <select v-model="filterMarketId" class="form-control form-control-sm">
                                    <option value="">— ทุกตลาด —</option>
                                    <option v-for="m in markets" :key="m.value" :value="m.value">@{{ m.text }}</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <label class="mb-1">วันที่รอบ</label>
                                <input type="date" v-model="filterRoundDate" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2 col-sm-6 mb-2">
                                <button @click="searchRounds" class="btn btn-primary btn-sm" :disabled="isLoadingRounds">
                                    <span v-if="isLoadingRounds">กำลังโหลด...</span>
                                    <span v-else>ค้นหา</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="rounds.length === 0 && !isLoadingRounds" class="alert alert-warning mb-0">
                            ไม่พบรอบ Yeekee ที่ตรงกับเงื่อนไข
                        </div>

                        <div v-if="rounds.length > 0" class="table-responsive mb-3">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="thead-light">
                                <tr>
                                    <th>ตลาด</th>
                                    <th>วันที่</th>
                                    <th>รอบที่</th>
                                    <th>สถานะ</th>
                                    <th>ยิงแล้ว</th>
                                    <th>Position สุดท้าย</th>
                                    <th>ปิดยิงเมื่อ</th>
                                    <th>Snapshot</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="round in rounds" :key="round.id">
                                    <td>@{{ round.market_name }}</td>
                                    <td>@{{ round.round_date }}</td>
                                    <td>@{{ round.round_no }}</td>
                                    <td>
                                        <span :class="statusBadgeClass(round.status)">@{{ round.status }}</span>
                                    </td>
                                    <td>@{{ round.shoot_count }}</td>
                                    <td>@{{ round.last_shoot_position }}</td>
                                    <td>@{{ round.shoot_closed_at || '-' }}</td>
                                    <td>
                                        <span v-if="round.has_snapshot" class="badge badge-success">มี</span>
                                        <span v-else class="badge badge-secondary">ไม่มี</span>
                                    </td>
                                    <td class="text-nowrap">
                                        @if($canViewShoots)
                                        <button
                                            class="btn btn-xs btn-info mr-1"
                                            @click="viewShoots(round)"
                                            :disabled="isLoadingDetail"
                                        >ดูยิงเลข</button>
                                        @endif
                                        @if($canViewSnapshot)
                                        <button
                                            v-if="round.has_snapshot"
                                            class="btn btn-xs btn-warning"
                                            @click="viewSnapshot(round)"
                                            :disabled="isLoadingDetail"
                                        >ดู Snapshot</button>
                                        @endif
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Shoots Modal -->
                        @if($canViewShoots)
                        <div v-if="shootsPanel.show" class="card card-outline card-info mt-3">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    รายการยิงเลข — @{{ shootsPanel.round ? shootsPanel.round.market_name : '' }}
                                    วันที่ @{{ shootsPanel.round ? shootsPanel.round.round_date : '' }}
                                    รอบที่ @{{ shootsPanel.round ? shootsPanel.round.round_no : '' }}
                                </h5>
                                <button @click="shootsPanel.show = false" class="btn btn-xs btn-secondary">ปิด</button>
                            </div>
                            <div class="card-body p-2">
                                <div v-if="isLoadingDetail" class="alert alert-light">กำลังโหลด...</div>
                                <template v-else>
                                    <p class="mb-2 text-muted">
                                        ยิงทั้งหมด @{{ shootsPanel.shoots.length }} รายการ
                                        | shoot_count (round): @{{ shootsPanel.round ? shootsPanel.round.shoot_count : '-' }}
                                        | last_position: @{{ shootsPanel.round ? shootsPanel.round.last_shoot_position : '-' }}
                                    </p>
                                    <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Position</th>
                                                <th>เลข</th>
                                                <th>Member ID</th>
                                                <th>เวลายิง</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr v-for="(s, idx) in shootsPanel.shoots" :key="s.id">
                                                <td>@{{ idx + 1 }}</td>
                                                <td>@{{ s.position }}</td>
                                                <td><strong>@{{ s.number_text }}</strong></td>
                                                <td>@{{ s.member_id }}</td>
                                                <td>@{{ s.submitted_at || '-' }}</td>
                                            </tr>
                                            <tr v-if="shootsPanel.shoots.length === 0">
                                                <td colspan="5" class="text-center text-muted">ไม่มีข้อมูลการยิง</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </template>
                            </div>
                        </div>
                        @endif

                        <!-- Snapshot Panel -->
                        @if($canViewSnapshot)
                        <div v-if="snapshotPanel.show" class="card card-outline card-warning mt-3">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    Snapshot — @{{ snapshotPanel.round ? snapshotPanel.round.market_name : '' }}
                                    วันที่ @{{ snapshotPanel.round ? snapshotPanel.round.round_date : '' }}
                                    รอบที่ @{{ snapshotPanel.round ? snapshotPanel.round.round_no : '' }}
                                </h5>
                                <button @click="snapshotPanel.show = false" class="btn btn-xs btn-secondary">ปิด</button>
                            </div>
                            <div class="card-body p-2">
                                <div v-if="isLoadingDetail" class="alert alert-light">กำลังโหลด...</div>
                                <template v-else-if="snapshotPanel.hasSnapshot">
                                    <div class="mb-2">
                                        <span class="text-muted">Hash: </span>
                                        <code class="text-break">@{{ snapshotPanel.hash }}</code>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted">ปิดยิงเมื่อ: </span>
                                        @{{ snapshotPanel.round ? snapshotPanel.round.shoot_closed_at : '-' }}
                                    </div>
                                    <pre class="bg-light p-2 border rounded" style="max-height:500px;overflow:auto;font-size:11px;white-space:pre-wrap;">@{{ snapshotJson }}</pre>
                                </template>
                                <div v-else class="alert alert-warning mb-0">รอบนี้ยังไม่มี Snapshot</div>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>

            </div>
        </section>
    </script>

    <script type="module">
        const initialState = {
            markets: @json($markets),
            canViewShoots: @json($canViewShoots),
            canViewSnapshot: @json($canViewSnapshot),
            loadRoundsUrl: @json(route('admin.lotto.yeekee_audit.rounds')),
            loadShootsBaseUrl: @json(route('admin.lotto.yeekee_audit.rounds', [])),
            snapshotBaseUrl: @json(route('admin.lotto.yeekee_audit.rounds', [])),
        };

        Vue.component('yeekee-audit-app', {
            template: '#yeekee-audit-app-template',
            data: function () {
                return {
                    markets: initialState.markets || [],
                    filterMarketId: '',
                    filterRoundDate: '',
                    rounds: [],
                    isLoadingRounds: false,
                    isLoadingDetail: false,
                    shootsPanel: { show: false, round: null, shoots: [] },
                    snapshotPanel: { show: false, round: null, hasSnapshot: false, hash: '', data: null },
                };
            },
            computed: {
                snapshotJson: function () {
                    if (!this.snapshotPanel.data) { return ''; }
                    try { return JSON.stringify(this.snapshotPanel.data, null, 2); } catch (e) { return String(this.snapshotPanel.data); }
                },
            },
            methods: {
                statusBadgeClass: function (status) {
                    const map = { resulted: 'badge badge-success', closed: 'badge badge-secondary', open: 'badge badge-primary', pending: 'badge badge-warning' };
                    return map[status] || 'badge badge-light';
                },
                requestJson: function (url) {
                    const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
                    if (window.axios) {
                        return window.axios.get(url, { headers, timeout: 15000 }).then(function (r) { return r.data; });
                    }
                    return window.fetch(url, { headers }).then(function (r) { if (!r.ok) { throw new Error('FETCH_FAILED'); } return r.json(); });
                },
                searchRounds: function () {
                    this.isLoadingRounds = true;
                    this.rounds = [];
                    this.shootsPanel.show = false;
                    this.snapshotPanel.show = false;
                    const params = new URLSearchParams();
                    if (this.filterMarketId) { params.set('market_id', this.filterMarketId); }
                    if (this.filterRoundDate) { params.set('round_date', this.filterRoundDate); }
                    const url = initialState.loadRoundsUrl + (params.toString() ? '?' + params.toString() : '');
                    this.requestJson(url)
                        .then((data) => { this.rounds = Array.isArray(data.rounds) ? data.rounds : []; })
                        .catch(function () { window.alert('โหลดข้อมูลรอบล้มเหลว'); })
                        .then(() => { this.isLoadingRounds = false; });
                },
                viewShoots: function (round) {
                    this.shootsPanel = { show: true, round: round, shoots: [] };
                    this.snapshotPanel.show = false;
                    this.isLoadingDetail = true;
                    const url = initialState.loadShootsBaseUrl + '/' + round.id + '/shoots';
                    this.requestJson(url)
                        .then((data) => {
                            this.shootsPanel.round = data.round || round;
                            this.shootsPanel.shoots = Array.isArray(data.shoots) ? data.shoots : [];
                        })
                        .catch(function () { window.alert('โหลดรายการยิงล้มเหลว'); })
                        .then(() => { this.isLoadingDetail = false; });
                },
                viewSnapshot: function (round) {
                    this.snapshotPanel = { show: true, round: round, hasSnapshot: false, hash: '', data: null };
                    this.shootsPanel.show = false;
                    this.isLoadingDetail = true;
                    const url = initialState.snapshotBaseUrl + '/' + round.id + '/snapshot';
                    this.requestJson(url)
                        .then((data) => {
                            this.snapshotPanel.hasSnapshot = !!data.has_snapshot;
                            this.snapshotPanel.hash = String(data.snapshot_hash || '');
                            this.snapshotPanel.round = data.round || round;
                            this.snapshotPanel.data = data.snapshot || null;
                        })
                        .catch(function () { window.alert('โหลด Snapshot ล้มเหลว'); })
                        .then(() => { this.isLoadingDetail = false; });
                },
            },
        });
    </script>

    <script>
        window.app = new Vue({
            el: '#app',
            data: function () {
                return { loopcnts: 0, announce: '', pushmenu: '', toast: '', withdraw_cnt: 0, played: false };
            },
            created() {},
            watch: {
                withdraw_cnt: function (event) { if (event > 0) { this.ToastPlay(); } },
            },
            methods: {},
        });
    </script>
    @include('admin::layouts.loadcnt_js')
@endpush
