@extends('admin::layouts.master')

@section('title')
    เปิด-ปิด หวย
@endsection

@section('content')
    <lotto-switch-dashboard></lotto-switch-dashboard>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-switch/css/bootstrap3/bootstrap-switch.min.css') }}">
    <style>
        .lotto-switch-dashboard .card-body {
            padding: 0.75rem;
        }

        .lotto-switch-dashboard .alert {
            padding: 0.45rem 0.65rem;
            margin-bottom: 0;
            font-size: 0.85rem;
            line-height: 1.2;
        }

        .lotto-switch-dashboard .group-tab-wrap {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 0.2rem;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .lotto-switch-dashboard .btn-group-tab {
            margin-right: 0.25rem;
            margin-bottom: 0;
            flex: 0 0 auto;
        }

        .lotto-switch-dashboard .market-row {
            padding: 0.4rem 0.6rem;
            margin-bottom: 0.3rem;
            gap: 0.5rem;
        }

        .lotto-switch-dashboard .market-label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            line-height: 1.1;
            min-width: 0;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lotto-switch-dashboard .market-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #dee2e6;
            flex: 0 0 20px;
        }

        .lotto-switch-dashboard .market-label span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }

        .lotto-switch-dashboard .market-label small {
            margin: 0;
            white-space: nowrap;
        }

        .lotto-switch-dashboard .section-title {
            margin-bottom: 0.35rem;
            font-size: 0.8rem;
            line-height: 1.1;
        }

        .lotto-switch-dashboard .active-group-head {
            margin-bottom: 0.4rem;
        }

        .lotto-switch-dashboard .active-group-name {
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1.15;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('vendor/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
    <script type="text/x-template" id="lotto-switch-dashboard-template">
        <section class="content text-sm lotto-switch-dashboard">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            เมนูนี้ใช้ควบคุมสถานะเปิด-ปิดของกลุ่มหวยและรายการหวยโดยตรง เมื่อปรับแล้วมีผลกับผู้เล่นทุกคนทันที
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">กลุ่มหวยและรายการหวย</h3>
                            </div>
                            <div class="card-body">
                                <div class="group-tab-wrap">
                                    <button v-for="item in groups"
                                            :key="'g-tab-' + item.id"
                                            type="button"
                                            class="btn btn-sm btn-group-tab"
                                            :class="selectedGroupId === item.id ? 'btn-primary' : 'btn-outline-primary'"
                                            @click="selectGroup(item)">
                                        <span>@{{ item.name }}</span>
                                        <span class="ml-2 badge" :class="item.is_enabled ? 'badge-success' : 'badge-danger'">
                                            @{{ item.is_enabled ? 'เปิด' : 'ปิด' }}
                                        </span>
                                    </button>
                                    <span v-if="groups.length === 0" class="text-muted">ไม่มีข้อมูลกลุ่มหวย</span>
                                </div>

                                <div v-if="activeGroup">
                                    <div class="d-flex align-items-center justify-content-between active-group-head">
                                        <h5 class="mb-0 mr-3 active-group-name">@{{ activeGroup.name }}</h5>
                                        <input type="checkbox"
                                               class="js-lotto-switch"
                                               data-type="group"
                                               :data-id="activeGroup.id"
                                               :checked="Boolean(activeGroup.is_enabled)"
                                               :disabled="Boolean(activeGroup._busy)"
                                               @change="onNativeSwitchChange($event, 'group', activeGroup.id)">
                                    </div>
                                    <div class="text-muted section-title">รายการหวยในกลุ่ม</div>
                                    <div v-for="item in filteredMarkets" :key="'m-row-' + item.id"
                                         class="d-flex align-items-center justify-content-between border rounded market-row">
                                        <div class="market-label">
                                            <img v-if="item.logo || item.icon" :src="item.logo || item.icon" alt="" class="market-thumb">
                                            <span>@{{ item.name }}</span>
                                            <small class="text-muted">@{{ item.code }}</small>
                                        </div>
                                        <input type="checkbox"
                                               class="js-lotto-switch"
                                               data-type="market"
                                               :data-id="item.id"
                                               :checked="Boolean(item.is_enabled)"
                                               :disabled="Boolean(item._busy)"
                                               @change="onNativeSwitchChange($event, 'market', item.id)">
                                    </div>
                                    <div v-if="filteredMarkets.length === 0" class="text-muted">ไม่มีรายการหวยในกลุ่มนี้</div>
                                </div>
                                <div v-else class="text-muted">กรุณาเลือกกลุ่มหวย</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </script>

    <script type="module">
        Vue.component('lotto-switch-dashboard', {
            template: '#lotto-switch-dashboard-template',
            data() {
                return {
                    groups: @json($groups),
                    markets: @json($markets),
                    selectedGroupId: null,
                };
            },
            computed: {
                activeGroup() {
                    if (!this.selectedGroupId && this.groups.length > 0) {
                        return this.groups[0];
                    }

                    return this.groups.find((group) => group.id === this.selectedGroupId) || null;
                },
                filteredMarkets() {
                    if (!this.activeGroup) {
                        return [];
                    }

                    return this.markets.filter((market) => Number(market.group_id) === Number(this.activeGroup.id));
                },
            },
            mounted() {
                if (this.groups.length > 0) {
                    this.selectedGroupId = this.groups[0].id;
                }

                this.$nextTick(() => this.initBootstrapSwitch());
            },
            updated() {
                this.$nextTick(() => this.syncBootstrapSwitchState());
            },
            methods: {
                selectGroup(item) {
                    this.selectedGroupId = item.id;
                },
                resolveSwitchItem(type, id) {
                    const list = type === 'group' ? this.groups : this.markets;
                    return list.find((entry) => Number(entry.id) === Number(id)) || null;
                },
                onNativeSwitchChange(event, type, id) {
                    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.bootstrapSwitch === 'function') {
                        return;
                    }

                    this.applySwitchChange(type, id, event.target.checked);
                },
                onBootstrapSwitchChange(type, id, state, $input) {
                    this.applySwitchChange(type, id, state, $input);
                },
                async applySwitchChange(type, id, nextState, $input = null) {
                    const item = this.resolveSwitchItem(type, id);
                    if (!item) {
                        return;
                    }

                    const previous = Boolean(item.is_enabled);
                    const next = Boolean(nextState);
                    if (previous === next || item._busy) {
                        this.syncBootstrapSwitchState();
                        return;
                    }

                    this.$set(item, '_busy', true);
                    this.$set(item, 'is_enabled', next);
                    this.syncBootstrapSwitchState();

                    try {
                        if (type === 'group') {
                            await this.updateGroup(item, next);
                        } else {
                            await this.updateMarket(item, next);
                        }
                    } catch (error) {
                        this.$set(item, 'is_enabled', previous);

                        if ($input && $input.bootstrapSwitch) {
                            $input.bootstrapSwitch('state', previous, true);
                        }
                    } finally {
                        this.$set(item, '_busy', false);
                        this.syncBootstrapSwitchState();
                    }
                },
                async updateGroup(item, nextEnabled) {
                    await axios.post("{{ route('admin.lotto.groups.edit') }}", {
                        id: item.id,
                        method: 'is_enabled',
                        status: nextEnabled ? 1 : 0,
                    });
                },
                async updateMarket(item, nextEnabled) {
                    await axios.post("{{ route('admin.lotto.markets.edit') }}", {
                        id: item.id,
                        method: 'is_enabled',
                        status: nextEnabled ? 1 : 0,
                    });
                },
                initBootstrapSwitch() {
                    if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.bootstrapSwitch !== 'function') {
                        return;
                    }

                    const $ = window.jQuery;
                    $(this.$el).find('input.js-lotto-switch').each((_, element) => {
                        const $input = $(element);
                        if ($input.data('bootstrap-switch')) {
                            return;
                        }

                        $input.bootstrapSwitch({
                            size: 'mini',
                            onText: 'เปิด',
                            offText: 'ปิด',
                            onColor: 'success',
                            offColor: 'danger',
                        });

                        $input.on('switchChange.bootstrapSwitch', (event, state) => {
                            const type = $input.data('type');
                            const id = $input.data('id');
                            this.onBootstrapSwitchChange(type, id, state, $input);
                        });
                    });
                },
                syncBootstrapSwitchState() {
                    if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.bootstrapSwitch !== 'function') {
                        return;
                    }

                    this.initBootstrapSwitch();

                    const $ = window.jQuery;
                    $(this.$el).find('input.js-lotto-switch').each((_, element) => {
                        const $input = $(element);
                        const type = $input.data('type');
                        const id = $input.data('id');
                        const item = this.resolveSwitchItem(type, id);
                        if (!item || !$input.data('bootstrap-switch')) {
                            return;
                        }

                        $input.bootstrapSwitch('disabled', Boolean(item._busy));
                        $input.bootstrapSwitch('state', Boolean(item.is_enabled), true);
                    });
                },
            },
        });
    </script>
    @include('admin::layouts.loadcnt_js')
@endpush
