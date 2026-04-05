@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <profit-loss-forecast-app ref="profitLossForecastApp"></profit-loss-forecast-app>
@endsection

@push('styles')
    <style>
        .profit-loss-forecast__matrix th,
        .profit-loss-forecast__matrix td,
        .profit-loss-forecast__numbers th,
        .profit-loss-forecast__numbers td {
            vertical-align: middle;
            white-space: nowrap;
        }

        .profit-loss-forecast__sticky-col {
            position: sticky;
            left: 0;
            z-index: 2;
            background: #fff;
        }

        .profit-loss-forecast__number-cell {
            min-width: 120px;
            padding: 0.5rem 0.6rem;
            border-radius: 0.4rem;
            background: #f8fafc;
        }

        .profit-loss-forecast__number-cell.is-active {
            background: #eefbf3;
            border: 1px solid #b7efcc;
        }
    </style>
@endpush

@push('scripts')
    <script type="text/x-template" id="profit-loss-forecast-app-template">
        <section class="content text-sm">
            <div class="container-fluid">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title mb-0">{{ $menu->currentName }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-lg-4 col-md-6 mb-3">
                                <label class="mb-1">ตลาด</label>
                                <select ref="marketSelect" class="form-control form-control-sm" @change="onMarketChange($event.target.value)">
                                    <option value="">เลือกตลาด</option>
                                    <optgroup
                                        v-for="group in marketOptions"
                                        :key="group.label"
                                        :label="group.label || '-'"
                                    >
                                        <option
                                            v-for="option in group.options"
                                            :key="option.value"
                                            :value="option.value"
                                            :data-logo="option.logo || ''"
                                        >
                                            @{{ option.text }}
                                        </option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-lg-4 col-md-6 mb-3">
                                <label class="mb-1">งวดหวย</label>
                                <select
                                    ref="drawSelect"
                                    class="form-control form-control-sm"
                                    :disabled="!selectedMarketId || isLoadingDrawOptions"
                                    @change="onDrawChange($event.target.value)"
                                >
                                    <option value="">
                                        @{{ isLoadingDrawOptions ? 'กำลังโหลดงวดหวย...' : 'เลือกงวดหวย' }}
                                    </option>
                                    <option
                                        v-for="option in drawOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        @{{ option.text }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-lg-4 mb-3 text-lg-right">
                                <button type="button" class="btn bg-gradient-secondary btn-sm" @click="resetFilters">
                                    <i class="fa fa-refresh"></i> ล้างค่า
                                </button>
                            </div>
                        </div>

                        <div class="mb-3 text-muted">
                            เลือก `ตลาด` และ `งวดหวย` ก่อน แล้วระบบจะโหลดตารางคาดการณ์กำไร/ขาดทุนของงวดนั้นทันที
                        </div>

                        <div v-if="!hasCompleteFilters" class="alert alert-info mb-0">
                            กรุณาเลือกตลาดและงวดหวยเพื่อดูรายงาน
                        </div>

                        <div v-else-if="isLoadingReport" class="alert alert-light border mb-0">
                            กำลังโหลดข้อมูลรายงาน...
                        </div>

                        <template v-else-if="hasReportColumns">
                            <div class="mb-3 d-flex flex-wrap align-items-center" style="gap:8px;">
                                <span class="badge badge-primary">
                                    <img
                                        v-if="report.draw.market_logo"
                                        :src="report.draw.market_logo"
                                        alt=""
                                        style="width:18px;height:18px;object-fit:cover;border-radius:50%;margin-right:6px;"
                                    >
                                    @{{ report.draw.market_name || '-' }}
                                </span>
                                <span class="badge badge-info">งวดวันที่: @{{ report.draw.draw_date_display || '-' }}</span>
                                <span class="badge badge-secondary">สถานะ: @{{ drawStatusLabel(report.draw.status) }}</span>
                                <span class="badge badge-success">ประเภทที่เปิดรับ: @{{ report.columns.length }}</span>
                            </div>

                            <div class="card card-outline card-secondary">
                                <div class="card-header py-2">
                                    <h4 class="card-title mb-0">สรุปตามประเภท</h4>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm mb-0 profit-loss-forecast__matrix">
                                            <thead class="thead-light">
                                            <tr>
                                                <th class="profit-loss-forecast__sticky-col" style="min-width:160px;">รายการ</th>
                                                <th style="min-width:140px;">รวมทั้งหมด</th>
                                                <th v-for="column in report.columns" :key="column.bet_type" style="min-width:160px;">
                                                    <div class="font-weight-bold">@{{ column.label }}</div>
                                                    <div class="small text-muted">จ่าย @{{ formatMoney(column.payout) }}</div>
                                                </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr v-for="row in report.summary_rows" :key="row.metric">
                                                <th class="profit-loss-forecast__sticky-col">@{{ row.label }}</th>
                                                <td class="font-weight-bold">@{{ formatMoney(row.overall) }}</td>
                                                <td v-for="column in report.columns" :key="row.metric + '-' + column.bet_type">
                                                    @{{ formatMoney(row.values[column.bet_type] || 0) }}
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-outline card-secondary mb-0">
                                <div class="card-header py-2">
                                    <h4 class="card-title mb-0">ยอดแทงสะสมรายหมายเลข</h4>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm mb-0 profit-loss-forecast__numbers">
                                            <thead class="thead-light">
                                            <tr>
                                                <th class="profit-loss-forecast__sticky-col" style="min-width:72px;">#</th>
                                                <th v-for="column in report.columns" :key="column.bet_type" style="min-width:150px;">
                                                    <div class="font-weight-bold">@{{ column.label }}</div>
                                                    <div class="small text-muted">อั้น @{{ formatMoney(column.max_per_number) }}</div>
                                                </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr v-for="row in report.number_rows" :key="row.index">
                                                <th class="profit-loss-forecast__sticky-col">@{{ row.index }}</th>
                                                <td v-for="column in report.columns" :key="row.index + '-' + column.bet_type">
                                                    <div
                                                        class="profit-loss-forecast__number-cell"
                                                        :class="{ 'is-active': (row.cells[column.bet_type] || {}).amount > 0 }"
                                                    >
                                                        <div class="font-weight-bold text-primary">
                                                            @{{ displayCellNumber(row.cells[column.bet_type]) }}
                                                        </div>
                                                        <div class="small text-muted">
                                                            @{{ formatMoney((row.cells[column.bet_type] || {}).amount || 0) }}
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div v-else class="alert alert-warning mb-0">
                            ไม่พบข้อมูลตั้งค่าเดิมพันหรือยอดแทงของงวดที่เลือก
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </script>
    <script type="module">
        const initialState = {
            marketOptions: @json($marketOptions ?? []),
            initialFilters: @json($initialFilters ?? ['market_id' => null, 'draw_id' => null]),
            viewUrl: @json(route('admin.lotto.reports.profit_loss_forecast')),
            loadDrawOptionsUrl: @json(route('admin.lotto.reports.profit_loss_forecast.draw_options')),
            loadDataUrl: @json(route('admin.lotto.reports.profit_loss_forecast.loaddata')),
        };

        const normalizeId = function (value) {
            const normalized = String(value || '').trim();
            return /^\d+$/.test(normalized) && Number(normalized) > 0 ? normalized : '';
        };

        const buildEmptyReport = function () {
            return {
                draw: {
                    market_name: '',
                    market_logo: '',
                    draw_date_display: '',
                    status: '',
                },
                columns: [],
                summary_rows: [],
                number_rows: [],
            };
        };

        const renderMarketOption = function (state) {
            if (!state.id) {
                return state.text;
            }

            const optionEl = state.element;
            const logo = optionEl ? String(optionEl.getAttribute('data-logo') || '') : '';
            const safeText = $('<span/>').text(state.text || '').html();

            if (!logo) {
                return $('<span>' + safeText + '</span>');
            }

            return $(
                '<span style="display:flex;align-items:center;gap:8px;">'
                + '<img src="' + logo + '" alt="" style="width:20px;height:20px;object-fit:cover;border-radius:50%;border:1px solid #e5e7eb;">'
                + '<span>' + safeText + '</span>'
                + '</span>'
            );
        };

        Vue.component('profit-loss-forecast-app', {
            template: '#profit-loss-forecast-app-template',
            data: function () {
                return {
                    marketOptions: Array.isArray(initialState.marketOptions) ? initialState.marketOptions : [],
                    drawOptions: [],
                    selectedMarketId: normalizeId(initialState.initialFilters.market_id),
                    selectedDrawId: normalizeId(initialState.initialFilters.draw_id),
                    pendingRequestedDrawId: normalizeId(initialState.initialFilters.draw_id),
                    report: buildEmptyReport(),
                    viewUrl: String(initialState.viewUrl || ''),
                    loadDrawOptionsUrl: String(initialState.loadDrawOptionsUrl || ''),
                    loadDataUrl: String(initialState.loadDataUrl || ''),
                    isLoadingDrawOptions: false,
                    isLoadingReport: false,
                };
            },
            computed: {
                hasCompleteFilters: function () {
                    return this.selectedMarketId !== '' && this.selectedDrawId !== '';
                },
                hasReportColumns: function () {
                    return Array.isArray(this.report.columns) && this.report.columns.length > 0;
                },
            },
            mounted: function () {
                window.profitLossForecastApp = this;
                this.initializeMarketSelect();
                this.initializeDrawSelect();
                this.syncSelect2Value(this.$refs.marketSelect, this.selectedMarketId);

                if (this.selectedMarketId !== '') {
                    this.loadDrawOptions(this.selectedMarketId, this.pendingRequestedDrawId, this.pendingRequestedDrawId !== '');
                }
            },
            methods: {
                requestJson: function (targetUrl, headers) {
                    if (window.axios && typeof window.axios.get === 'function') {
                        return window.axios.get(targetUrl, {
                            headers: headers,
                            timeout: 15000,
                        }).then(function (response) {
                            return response && response.data ? response.data : {};
                        });
                    }

                    if (!window.fetch) {
                        return Promise.reject(new Error('HTTP_CLIENT_NOT_AVAILABLE'));
                    }

                    return window.fetch(targetUrl, {
                        method: 'GET',
                        headers: headers,
                    }).then(function (response) {
                        if (!response.ok) {
                            throw new Error('FETCH_FAILED');
                        }

                        return response.json();
                    });
                },
                initializeMarketSelect: function () {
                    const $marketSelect = window.jQuery ? window.jQuery(this.$refs.marketSelect) : null;
                    if (! $marketSelect || typeof $marketSelect.select2 !== 'function') {
                        return;
                    }

                    if ($marketSelect.hasClass('select2-hidden-accessible')) {
                        $marketSelect.off('.profitLossForecastMarket');
                        $marketSelect.select2('destroy');
                    }

                    $marketSelect.select2({
                        width: '100%',
                        placeholder: 'เลือกตลาด',
                        allowClear: true,
                        templateResult: renderMarketOption,
                        templateSelection: renderMarketOption,
                        escapeMarkup: function (markup) {
                            return markup;
                        },
                    });

                    $marketSelect.off('change.profitLossForecastMarket').on('change.profitLossForecastMarket', (event) => {
                        this.onMarketChange(event.target.value);
                    });
                },
                initializeDrawSelect: function () {
                    const $drawSelect = window.jQuery ? window.jQuery(this.$refs.drawSelect) : null;
                    if (! $drawSelect || typeof $drawSelect.select2 !== 'function') {
                        return;
                    }

                    if ($drawSelect.hasClass('select2-hidden-accessible')) {
                        $drawSelect.off('.profitLossForecastDraw');
                        $drawSelect.select2('destroy');
                    }

                    $drawSelect.select2({
                        width: '100%',
                        placeholder: 'เลือกงวดหวย',
                        allowClear: true,
                    });

                    $drawSelect.off('change.profitLossForecastDraw').on('change.profitLossForecastDraw', (event) => {
                        this.onDrawChange(event.target.value);
                    });

                    this.syncSelect2Value(this.$refs.drawSelect, this.selectedDrawId);
                },
                syncSelect2Value: function (element, value) {
                    if (!element || typeof window.jQuery !== 'function') {
                        return;
                    }

                    const $element = window.jQuery(element);
                    element.value = value || '';

                    if ($element.hasClass('select2-hidden-accessible')) {
                        $element.val(value || '').trigger('change.select2');
                    }
                },
                onMarketChange: function (value) {
                    const marketId = normalizeId(value);

                    if (marketId === this.selectedMarketId && (this.drawOptions.length > 0 || this.isLoadingDrawOptions)) {
                        return;
                    }

                    this.selectedMarketId = marketId;
                    this.selectedDrawId = '';
                    this.pendingRequestedDrawId = '';
                    this.drawOptions = [];
                    this.report = buildEmptyReport();
                    this.syncSelect2Value(this.$refs.marketSelect, this.selectedMarketId);

                    this.$nextTick(() => {
                        this.initializeDrawSelect();
                        this.syncSelect2Value(this.$refs.drawSelect, '');
                    });

                    this.updateBrowserUrl();

                    if (this.selectedMarketId === '') {
                        return;
                    }

                    this.loadDrawOptions(this.selectedMarketId, '', false);
                },
                onDrawChange: function (value) {
                    const drawId = normalizeId(value);

                    if (drawId === this.selectedDrawId && ((this.report.draw && this.report.draw.id) || this.isLoadingReport)) {
                        return;
                    }

                    this.selectedDrawId = drawId;
                    this.pendingRequestedDrawId = '';
                    this.syncSelect2Value(this.$refs.drawSelect, this.selectedDrawId);
                    this.updateBrowserUrl();

                    if (!this.hasCompleteFilters) {
                        this.report = buildEmptyReport();
                        return;
                    }

                    this.fetchReport();
                },
                loadDrawOptions: function (marketId, requestedDrawId, shouldAutoLoadReport) {
                    if (!marketId || !this.loadDrawOptionsUrl) {
                        return;
                    }

                    this.isLoadingDrawOptions = true;
                    const headers = {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                    const targetUrl = `${this.loadDrawOptionsUrl}?market_id=${encodeURIComponent(marketId)}`;

                    this.requestJson(targetUrl, headers)
                        .then((data) => {
                            this.drawOptions = Array.isArray(data.draws) ? data.draws : [];

                            const requestedId = normalizeId(requestedDrawId);
                            const drawExists = requestedId !== ''
                                && this.drawOptions.some((option) => String(option.value) === requestedId);

                            this.selectedDrawId = drawExists ? requestedId : '';

                            this.$nextTick(() => {
                                this.initializeDrawSelect();
                                this.syncSelect2Value(this.$refs.drawSelect, this.selectedDrawId);
                            });

                            this.updateBrowserUrl();

                            if (shouldAutoLoadReport && this.selectedDrawId !== '') {
                                this.fetchReport(false);
                            }
                        })
                        .catch(() => {
                            this.drawOptions = [];
                            this.selectedDrawId = '';
                            window.alert('โหลดรายการงวดหวยไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
                        })
                        .then(() => {
                            this.isLoadingDrawOptions = false;
                        });
                },
                fetchReport: function (shouldUpdateUrl = true) {
                    if (!this.hasCompleteFilters || !this.loadDataUrl) {
                        return;
                    }

                    this.isLoadingReport = true;
                    const headers = {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                    const targetUrl = `${this.loadDataUrl}?market_id=${encodeURIComponent(this.selectedMarketId)}&draw_id=${encodeURIComponent(this.selectedDrawId)}`;

                    this.requestJson(targetUrl, headers)
                        .then((data) => {
                            this.report = data || buildEmptyReport();

                            if (shouldUpdateUrl) {
                                this.updateBrowserUrl();
                            }
                        })
                        .catch(() => {
                            this.report = buildEmptyReport();
                            window.alert('โหลดรายงานไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
                        })
                        .then(() => {
                            this.isLoadingReport = false;
                        });
                },
                resetFilters: function () {
                    this.selectedMarketId = '';
                    this.selectedDrawId = '';
                    this.pendingRequestedDrawId = '';
                    this.drawOptions = [];
                    this.report = buildEmptyReport();
                    this.syncSelect2Value(this.$refs.marketSelect, '');

                    this.$nextTick(() => {
                        this.initializeDrawSelect();
                        this.syncSelect2Value(this.$refs.drawSelect, '');
                    });

                    this.updateBrowserUrl();
                },
                updateBrowserUrl: function () {
                    if (!window.history || !this.viewUrl) {
                        return;
                    }

                    const searchParams = new URLSearchParams();

                    if (this.selectedMarketId !== '') {
                        searchParams.set('market_id', this.selectedMarketId);
                    }

                    if (this.selectedDrawId !== '') {
                        searchParams.set('draw_id', this.selectedDrawId);
                    }

                    const queryString = searchParams.toString();
                    const nextUrl = queryString !== '' ? `${this.viewUrl}?${queryString}` : this.viewUrl;
                    window.history.replaceState({}, '', nextUrl);
                },
                formatMoney: function (value) {
                    const amount = Number(value || 0);

                    return amount.toLocaleString('th-TH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },
                displayCellNumber: function (cell) {
                    if (!cell || !cell.number) {
                        return '-';
                    }

                    return String(cell.number);
                },
                drawStatusLabel: function (status) {
                    const normalized = String(status || '');

                    if (normalized === 'draft') {
                        return 'ฉบับร่าง';
                    }

                    if (normalized === 'open') {
                        return 'เปิดรับ';
                    }

                    if (normalized === 'closed') {
                        return 'ปิดรับแล้ว';
                    }

                    if (normalized === 'resulted') {
                        return 'ออกผลแล้ว';
                    }

                    return normalized !== '' ? normalized : '-';
                },
            },
        });
    </script>
    <script>
        window.app = new Vue({
            el: '#app',
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
                        })
                },

            }
        });
    </script>
    @include('admin::layouts.loadcnt_js')
@endpush
