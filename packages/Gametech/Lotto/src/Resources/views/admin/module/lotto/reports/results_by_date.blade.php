@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')

        <result-app ref="resultApp"></result-app>

@endsection

@push('scripts')
    <script type="text/x-template" id="result-app-template">
        <section class="content text-sm">
            <div class="container-fluid">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title mb-0">{{ $menu->currentName }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="row align-items-end">
                                <div class="col-md-3 col-sm-6">
                                    <label class="mb-1">วันที่งวด</label>
                                    <input
                                        type="date"
                                        v-model="drawDate"
                                        @change="searchByDate($event.target.value)"
                                        @keydown.enter.prevent="search(drawDate, true)"
                                        class="form-control form-control-sm"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="badge badge-info">วันที่: @{{ displayDrawDate || '-' }}</span>
                            <span class="badge badge-secondary">กลุ่มหวย: @{{ summary.group_count || 0 }}</span>
                            <span class="badge badge-secondary">รายการหวย: @{{ summary.market_count || 0 }}</span>
                            <span class="badge badge-success">มีผลแล้ว: @{{ summary.result_count || 0 }}</span>
                            <span class="text-muted ml-2">อัปเดตล่าสุด @{{ serverTime || '-' }}</span>
                        </div>

                        <div v-if="isLoading" class="alert alert-light border mb-0">
                            กำลังโหลดข้อมูล...
                        </div>

                        <template v-else-if="groups.length > 0">
                            <div v-for="group in groups" :key="group.group_id" class="card card-outline card-secondary mb-3">
                                <div class="card-header py-2">
                                    <h4 class="card-title mb-0">
                                        @{{ group.group_name }}
                                        <small v-if="group.group_code" class="text-muted">(@{{ group.group_code }})</small>
                                    </h4>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm mb-0">
                                            <thead>
                                            <tr>
                                                <th style="width: 18%">รายการหวย</th>
                                                <th style="width: 10%">งวด</th>
                                                <th style="width: 14%">เวลาออกผล</th>
                                                <th style="width: 14%">รางวัลที่ 1</th>
                                                <th style="width: 12%">3 ตัวบน</th>
                                                <th style="width: 12%">2 ตัวบน</th>
                                                <th style="width: 12%">2 ตัวล่าง</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr v-for="market in group.markets" :key="market.market_id">
                                                <td>
                                                    <img
                                                        v-if="market.market_logo"
                                                        :src="market.market_logo"
                                                        :alt="market.market_name"
                                                        style="width:22px;height:22px;object-fit:cover;border-radius:4px;margin-right:6px;"
                                                    >
                                                    @{{ market.market_name }}
                                                </td>
                                                <td>@{{ market.draw_date || '-' }}</td>
                                                <td>@{{ market.result_at || '-' }}</td>
                                                <td>@{{ displayResultValue(market, 'first_prize') }}</td>
                                                <td>@{{ displayResultValue(market, 'top_3') }}</td>
                                                <td>@{{ displayResultValue(market, 'top_2') }}</td>
                                                <td>@{{ displayResultValue(market, 'bottom_2') }}</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div v-else class="alert alert-warning mb-0">
                            ไม่พบผลรางวัลที่ออกแล้วในวันที่ @{{ displayDrawDate || '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </script>
    <script type="module">


            const initialState = {
                drawDate: @json($drawDate),
                groups: @json($groups),
                summary: @json($summary),
                serverTime: @json($serverTime),
                viewUrl: @json(route('admin.lotto.reports.results_by_date')),
                loadDataUrl: @json(route('admin.lotto.reports.results_by_date.loaddata')),
            };

            const getLocalToday = function () {
                const now = new Date();
                const localTime = new Date(now.getTime() - (now.getTimezoneOffset() * 60000));
                return localTime.toISOString().slice(0, 10);
            };

            const isValidDateString = function (value) {
                return /^\d{4}-\d{2}-\d{2}$/.test(String(value || '').trim());
            };

            const getRequestedDrawDate = function () {
                if (!window.URLSearchParams) {
                    return '';
                }

                const searchParams = new URLSearchParams(window.location.search || '');
                const drawDate = String(searchParams.get('draw_date') || '').trim();

                return isValidDateString(drawDate) ? drawDate : '';
            };

            Vue.component('result-app', {
                template: '#result-app-template',
                data: function () {
                    return {
                        drawDate: String(initialState.drawDate || ''),
                        displayDrawDate: String(initialState.drawDate || ''),
                        groups: Array.isArray(initialState.groups) ? initialState.groups : [],
                        summary: initialState.summary || { group_count: 0, market_count: 0, result_count: 0 },
                        serverTime: String(initialState.serverTime || ''),
                        viewUrl: String(initialState.viewUrl || ''),
                        loadDataUrl: String(initialState.loadDataUrl || ''),
                        isLoading: false,
                    };
                },
                mounted: function () {
                    const requestedDrawDate = getRequestedDrawDate();
                    const targetDate = requestedDrawDate || getLocalToday();

                    window.resultsByDateApp = this;
                    this.drawDate = targetDate;
                    this.displayDrawDate = targetDate;
                    this.$nextTick(() => {
                        this.search(targetDate, false);
                    });
                },
                methods: {
                    searchByDate: function (dateValue) {
                        const value = String(dateValue || '').trim();
                        if (!isValidDateString(value)) {
                            return;
                        }

                        this.drawDate = value;
                        this.displayDrawDate = value;
                        this.search(value, true);
                    },
                    isNoResult: function (market) {
                        return !!(market && market.no_result);
                    },
                    displayResultValue: function (market, key) {
                        if (this.isNoResult(market)) {
                            return 'งดออกผล';
                        }

                        const value = market && market[key] ? String(market[key]) : '';
                        return value !== '' ? value : '-';
                    },
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
                    search: function (forcedDate, shouldUpdateUrl) {
                        const selectedDate = String(forcedDate || this.drawDate || '').trim();
                        const allowReplaceUrl = shouldUpdateUrl !== false;

                        if (!isValidDateString(selectedDate) || !this.loadDataUrl) {
                            return;
                        }

                        this.drawDate = selectedDate;
                        this.displayDrawDate = selectedDate;
                        this.isLoading = true;

                        const headers = {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        };
                        const targetUrl = `${this.loadDataUrl}?draw_date=${encodeURIComponent(selectedDate)}`;

                        this.requestJson(targetUrl, headers)
                            .then((data) => {
                                this.groups = Array.isArray(data.groups) ? data.groups : [];
                                this.summary = data.summary || { group_count: 0, market_count: 0, result_count: 0 };
                                this.serverTime = String(data.serverTime || '');
                                this.drawDate = String(data.drawDate || this.drawDate);
                                this.displayDrawDate = this.drawDate;

                                if (allowReplaceUrl) {
                                    const nextUrlBase = this.viewUrl || this.loadDataUrl;
                                    const nextUrl = `${nextUrlBase}?draw_date=${encodeURIComponent(this.drawDate)}`;
                                    window.history.replaceState({}, '', nextUrl);
                                }
                            })
                            .catch(() => {
                                window.alert('ค้นหาไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
                            })
                            .then(() => {
                                this.isLoading = false;
                            });
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
