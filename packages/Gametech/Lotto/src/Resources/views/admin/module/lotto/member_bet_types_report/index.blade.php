@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <member-bet-types-app ref="memberBetTypesApp"></member-bet-types-app>
@endsection

@push('styles')
    <style>
        .profit-loss-forecast__summary {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .profit-loss-forecast__summary-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 0.22rem 0.62rem;
            font-weight: 600;
            line-height: 1.2;
            border: 1px solid transparent;
        }

        .profit-loss-forecast__summary-chip.is-market {
            color: #1d4ed8;
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .profit-loss-forecast__summary-chip.is-date {
            color: #075985;
            background: #ecfeff;
            border-color: #a5f3fc;
        }

        .profit-loss-forecast__summary-chip.is-package {
            color: #92400e;
            background: #fffbeb;
            border-color: #fde68a;
        }

        .profit-loss-forecast__summary-chip.is-status {
            color: #374151;
            background: #f3f4f6;
            border-color: #d1d5db;
        }

        .profit-loss-forecast__summary-chip.is-types {
            color: #166534;
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .profit-loss-forecast__matrix th,
        .profit-loss-forecast__matrix td {
            vertical-align: middle;
            padding: 0.38rem 0.35rem;
            font-size: 0.78rem;
            line-height: 1.2;
            white-space: normal;
        }

        .profit-loss-forecast__matrix th {
            text-align: center;
        }

        .profit-loss-forecast__matrix th:first-child,
        .profit-loss-forecast__matrix td:first-child {
            text-align: left;
            font-weight: 600;
        }
    </style>
@endpush

@push('scripts')
    <script type="text/x-template" id="member-bet-types-app-template">
        <section class="content text-sm">
            <div class="container-fluid">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title mb-0">{{ $menu->currentName }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-lg-3 col-md-6 mb-3">
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
                            <div class="col-lg-3 col-md-6 mb-3">
                                <label class="mb-1">แพกเกจ</label>
                                <select
                                    ref="packageSelect"
                                    class="form-control form-control-sm"
                                    :disabled="!selectedMarketId || isLoadingPackageOptions"
                                    @change="onPackageChange($event.target.value)"
                                >
                                    <option value="">
                                        @{{ isLoadingPackageOptions ? 'กำลังโหลดแพกเกจ...' : 'เลือกแพกเกจ' }}
                                    </option>
                                    <option
                                        v-for="option in packageOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        @{{ option.text }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
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
                            <div class="col-lg-3 mb-3 text-lg-right">
                                <button type="button" class="btn bg-gradient-secondary btn-sm" @click="resetFilters">
                                    <i class="fa fa-refresh"></i> ล้างค่า
                                </button>
                            </div>
                        </div>

                        <div class="mb-3 text-muted">
                            เลือกตลาด แพกเกจ และงวดหวย แล้วระบบจะสรุปยอดเดิมพันตามประเภท พร้อมแสดงรายเลขของแพกเกจที่เลือก
                        </div>

                        <div v-if="!hasCompleteFilters" class="alert alert-info mb-0">
                            กรุณาเลือกตลาด แพกเกจ และงวดหวยก่อนดูรายงาน
                        </div>

                        <div v-else-if="isLoadingReport && !hasReportColumns" class="alert alert-light border mb-0">
                            กำลังโหลดข้อมูลรายงาน...
                        </div>

                        <template v-else-if="hasReportColumns">
                            <div class="mb-3 profit-loss-forecast__summary">
                                <span class="profit-loss-forecast__summary-chip is-market">
                                    <img
                                        v-if="report.package.image"
                                        :src="report.package.image"
                                        alt=""
                                        style="width:18px;height:18px;object-fit:cover;border-radius:50%;margin-right:6px;"
                                    >
                                    @{{ report.package.name || '-' }}
                                </span>
                                <span class="profit-loss-forecast__summary-chip is-market">
                                    <img
                                        v-if="report.draw.market_logo"
                                        :src="report.draw.market_logo"
                                        alt=""
                                        style="width:18px;height:18px;object-fit:cover;border-radius:50%;margin-right:6px;"
                                    >
                                    @{{ report.draw.market_name || '-' }}
                                </span>
                                <span class="profit-loss-forecast__summary-chip is-date">งวดวันที่: @{{ report.draw.draw_date_display || '-' }}</span>
                                <span class="profit-loss-forecast__summary-chip is-status">สถานะ: @{{ drawStatusLabel(report.draw.status) }}</span>
                                <span class="profit-loss-forecast__summary-chip is-types">ประเภทที่เปิดรับ: @{{ report.columns.length }}</span>
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
                                                <th>รายการ</th>
                                                <th class="text-right">รวมทั้งหมด</th>
                                                <th
                                                    v-for="column in report.columns"
                                                    :key="column.bet_type"
                                                    class="text-right"
                                                >
                                                    <div class="font-weight-bold">@{{ column.label }}</div>
                                                    <div class="small text-muted">จ่าย @{{ formatMoney(column.payout) }} | ส่วนลด @{{ formatPercent(column.discount_percent) }}</div>
                                                </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr v-for="row in report.summary_rows" :key="row.metric">
                                                <th>@{{ row.label }}</th>
                                                <td class="font-weight-bold text-right">@{{ formatMoney(row.overall) }}</td>
                                                <td
                                                    class="text-right"
                                                    v-for="column in report.columns"
                                                    :key="row.metric + '-' + column.bet_type"
                                                >
                                                    @{{ formatMoney(row.values[column.bet_type] || 0) }}
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-outline card-secondary mb-0 mt-3">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <h4 class="card-title mb-0">ประเภท-เลขตามยอดแทง</h4>
                                    <button type="button" class="btn btn-outline-primary btn-sm" @click="toggleTypeNumberSort">
                                        <i class="fa fa-sort-amount-asc mr-1"></i> @{{ typeNumberSortLabel }}
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm mb-0">
                                            <thead class="thead-light">
                                            <tr>
                                                <th class="text-center">ลำดับ</th>
                                                <th>ประเภท</th>
                                                <th>เลข</th>
                                                <th class="text-right">จำนวนเงิน</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr v-if="sortedTypeNumberRows.length === 0">
                                                <td colspan="4" class="text-center text-muted">ไม่พบข้อมูลเลขเดิมพัน</td>
                                            </tr>
                                            <tr v-for="(row, index) in sortedTypeNumberRows" :key="row.bet_type + '-' + row.number + '-' + index">
                                                <td class="text-center">@{{ index + 1 }}</td>
                                                <td>@{{ row.bet_type_label }}</td>
                                                <td>@{{ row.number }}</td>
                                                <td class="text-right">@{{ formatMoney(row.amount) }}</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div v-else class="alert alert-warning mb-0">
                            ไม่พบข้อมูลการเดิมพันของงวดที่เลือก
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </script>
    <script>
        const initialState = {
            marketOptions: @json($marketOptions ?? []),
            initialFilters: @json($initialFilters ?? ['market_id' => null, 'package_id' => null, 'draw_id' => null]),
            viewUrl: @json(route('admin.lotto.reports.member_bet_types')),
            loadPackageOptionsUrl: @json(route('admin.lotto.reports.member_bet_types.package_options')),
            loadDrawOptionsUrl: @json(route('admin.lotto.reports.member_bet_types.draw_options')),
            loadDataUrl: @json(route('admin.lotto.reports.member_bet_types.loaddata')),
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
                package: {
                    name: '',
                    image: '',
                },
                columns: [],
                summary_rows: [],
                type_number_rows: [],
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

        Vue.component('member-bet-types-app', {
            template: '#member-bet-types-app-template',
            data: function () {
                return {
                    marketOptions: Array.isArray(initialState.marketOptions) ? initialState.marketOptions : [],
                    packageOptions: [],
                    drawOptions: [],
                    selectedMarketId: normalizeId(initialState.initialFilters.market_id),
                    selectedPackageId: normalizeId(initialState.initialFilters.package_id),
                    selectedDrawId: normalizeId(initialState.initialFilters.draw_id),
                    pendingRequestedPackageId: normalizeId(initialState.initialFilters.package_id),
                    pendingRequestedDrawId: normalizeId(initialState.initialFilters.draw_id),
                    report: buildEmptyReport(),
                    typeNumberRows: [],
                    viewUrl: String(initialState.viewUrl || ''),
                    loadPackageOptionsUrl: String(initialState.loadPackageOptionsUrl || ''),
                    loadDrawOptionsUrl: String(initialState.loadDrawOptionsUrl || ''),
                    loadDataUrl: String(initialState.loadDataUrl || ''),
                    isLoadingPackageOptions: false,
                    isLoadingDrawOptions: false,
                    isLoadingReport: false,
                    sortDescending: false,
                };
            },
            computed: {
                hasCompleteFilters: function () {
                    return this.selectedMarketId !== '' && this.selectedPackageId !== '' && this.selectedDrawId !== '';
                },
                hasReportColumns: function () {
                    return Array.isArray(this.report.columns) && this.report.columns.length > 0;
                },
                sortedTypeNumberRows: function () {
                    const rows = Array.isArray(this.typeNumberRows) ? [...this.typeNumberRows] : [];
                    rows.sort((a, b) => {
                        if (this.sortDescending) {
                            return Number(b.amount || 0) - Number(a.amount || 0);
                        }
                        return Number(a.amount || 0) - Number(b.amount || 0);
                    });
                    return rows;
                },
                typeNumberSortLabel: function () {
                    return this.sortDescending ? 'เรียงจากมากไปน้อย' : 'เรียงจากน้อยไปมาก';
                },
            },
            mounted: function () {
                window.memberBetTypesApp = this;
                this.initializeMarketSelect();
                this.initializePackageSelect();
                this.initializeDrawSelect();
                this.syncSelect2Value(this.$refs.marketSelect, this.selectedMarketId);

                if (this.selectedMarketId !== '') {
                    const shouldLoadReport = this.pendingRequestedPackageId !== '' && this.pendingRequestedDrawId !== '';
                    this.loadPackageOptions(this.selectedMarketId, this.pendingRequestedPackageId, shouldLoadReport);
                    this.loadDrawOptions(this.selectedMarketId, this.pendingRequestedDrawId, shouldLoadReport);
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
                        $marketSelect.select2('destroy');
                    }

                    const component = this;

                    $marketSelect.select2({
                        width: '100%',
                        placeholder: 'เลือกตลาด',
                        templateResult: renderMarketOption,
                        templateSelection: renderMarketOption,
                        allowClear: true,
                        escapeMarkup: function (markup) {
                            return markup;
                        },
                    });

                    $marketSelect.on('select2:select select2:unselect', function () {
                        const value = $marketSelect.val();
                        component.onMarketChange(value);
                    });
                },
                initializePackageSelect: function () {
                    const $packageSelect = window.jQuery ? window.jQuery(this.$refs.packageSelect) : null;
                    if (! $packageSelect || typeof $packageSelect.select2 !== 'function') {
                        return;
                    }

                    if ($packageSelect.hasClass('select2-hidden-accessible')) {
                        $packageSelect.select2('destroy');
                    }

                    const component = this;

                    $packageSelect.select2({
                        width: '100%',
                        placeholder: 'เลือกแพกเกจ',
                        minimumResultsForSearch: Infinity,
                    });

                    $packageSelect.on('select2:select select2:unselect', function () {
                        const value = $packageSelect.val();
                        component.onPackageChange(value);
                    });
                },
                initializeDrawSelect: function () {
                    const $drawSelect = window.jQuery ? window.jQuery(this.$refs.drawSelect) : null;
                    if (! $drawSelect || typeof $drawSelect.select2 !== 'function') {
                        return;
                    }

                    if ($drawSelect.hasClass('select2-hidden-accessible')) {
                        $drawSelect.select2('destroy');
                    }

                    const component = this;

                    $drawSelect.select2({
                        width: '100%',
                        placeholder: 'เลือกงวดหวย',
                        minimumResultsForSearch: Infinity,
                    });

                    $drawSelect.on('select2:select select2:unselect', function () {
                        const value = $drawSelect.val();
                        component.onDrawChange(value);
                    });
                },
                syncSelect2Value: function (refElement, value) {
                    if (!refElement) {
                        return;
                    }

                    const $element = window.jQuery ? window.jQuery(refElement) : null;
                    if (!$element || typeof $element.select2 !== 'function') {
                        return;
                    }

                    $element.val(value).trigger('change.select2');
                },
                onMarketChange: function (value) {
                    this.selectedMarketId = normalizeId(value);
                    this.pendingRequestedPackageId = '';
                    this.pendingRequestedDrawId = '';
                    this.packageOptions = [];
                    this.drawOptions = [];
                    this.selectedPackageId = '';
                    this.selectedDrawId = '';
                    this.report = buildEmptyReport();
                    this.typeNumberRows = [];

                    this.syncSelect2Value(this.$refs.packageSelect, '');
                    this.syncSelect2Value(this.$refs.drawSelect, '');
                    this.updateBrowserUrl();

                    if (this.selectedMarketId === '') {
                        return;
                    }

                    this.loadPackageOptions(this.selectedMarketId, '', false);
                    this.loadDrawOptions(this.selectedMarketId, '', false);
                },
                onPackageChange: function (value) {
                    const packageId = normalizeId(value);

                    if (packageId === this.selectedPackageId && ((this.report.package && this.report.package.name) || this.isLoadingReport)) {
                        return;
                    }

                    this.selectedPackageId = packageId;
                    this.pendingRequestedPackageId = '';
                    this.updateBrowserUrl();

                    if (!this.hasCompleteFilters) {
                        this.report = buildEmptyReport();
                        this.typeNumberRows = [];
                        return;
                    }

                    this.fetchReport();
                },
                onDrawChange: function (value) {
                    const drawId = normalizeId(value);

                    if (drawId === this.selectedDrawId && ((this.report.draw && this.report.draw.id) || this.isLoadingReport)) {
                        return;
                    }

                    this.selectedDrawId = drawId;
                    this.pendingRequestedDrawId = '';
                    this.updateBrowserUrl();

                    if (!this.hasCompleteFilters) {
                        this.report = buildEmptyReport();
                        this.typeNumberRows = [];
                        return;
                    }

                    this.fetchReport();
                },
                loadPackageOptions: function (marketId, requestedPackageId, shouldAutoLoadReport) {
                    if (!marketId || !this.loadPackageOptionsUrl) {
                        return;
                    }

                    this.isLoadingPackageOptions = true;
                    const headers = {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                    const targetUrl = `${this.loadPackageOptionsUrl}?market_id=${encodeURIComponent(marketId)}`;

                    this.requestJson(targetUrl, headers)
                        .then((data) => {
                            this.packageOptions = Array.isArray(data.packages) ? data.packages : [];

                            const requestedId = normalizeId(requestedPackageId);
                            const packageExists = requestedId !== ''
                                && this.packageOptions.some((option) => String(option.value) === requestedId);

                            this.selectedPackageId = packageExists ? requestedId : '';

                            this.$nextTick(() => {
                                this.initializePackageSelect();
                                this.syncSelect2Value(this.$refs.packageSelect, this.selectedPackageId);
                            });

                            this.updateBrowserUrl();

                            if (shouldAutoLoadReport && this.hasCompleteFilters) {
                                this.fetchReport();
                            }
                        })
                        .catch(() => {
                            this.packageOptions = [];
                            this.selectedPackageId = '';
                            window.alert('โหลดรายการแพกเกจไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
                        })
                        .then(() => {
                            this.isLoadingPackageOptions = false;
                        });
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

                            if (shouldAutoLoadReport && this.hasCompleteFilters) {
                                this.fetchReport();
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
                fetchReport: function () {
                    if (!this.hasCompleteFilters || !this.loadDataUrl) {
                        return;
                    }

                    this.isLoadingReport = true;
                    const headers = {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                    const targetUrl = `${this.loadDataUrl}?market_id=${encodeURIComponent(this.selectedMarketId)}&draw_id=${encodeURIComponent(this.selectedDrawId)}`;
                    const query = `${targetUrl}&package_id=${encodeURIComponent(this.selectedPackageId)}`;

                    this.requestJson(query, headers)
                        .then((data) => {
                            this.report = data || buildEmptyReport();
                            this.typeNumberRows = Array.isArray(data?.type_number_rows) ? data.type_number_rows : [];
                        })
                        .catch(() => {
                            window.alert('โหลดรายงานไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
                        })
                        .then(() => {
                            this.isLoadingReport = false;
                        });
                },
                resetFilters: function () {
                    this.selectedMarketId = '';
                    this.selectedPackageId = '';
                    this.selectedDrawId = '';
                    this.pendingRequestedPackageId = '';
                    this.pendingRequestedDrawId = '';
                    this.packageOptions = [];
                    this.drawOptions = [];
                    this.report = buildEmptyReport();
                    this.typeNumberRows = [];
                    this.syncSelect2Value(this.$refs.marketSelect, '');

                    this.$nextTick(() => {
                        this.initializePackageSelect();
                        this.syncSelect2Value(this.$refs.packageSelect, '');
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

                    if (this.selectedPackageId !== '') {
                        searchParams.set('package_id', this.selectedPackageId);
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
                formatPercent: function (value) {
                    return `${Number(value || 0).toLocaleString('th-TH', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2,
                    })}%`;
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
                toggleTypeNumberSort: function () {
                    this.sortDescending = !this.sortDescending;
                },
            },
        });
    </script>
    @include('admin::layouts.loadcnt_js')
@endpush
