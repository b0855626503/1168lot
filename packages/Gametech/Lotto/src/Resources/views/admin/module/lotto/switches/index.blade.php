@extends('admin::layouts.master')

@section('title')
    เปิด-ปิด หวย
@endsection

@section('content')
    <lotto-switch-dashboard></lotto-switch-dashboard>
@endsection

@push('styles')
    <style>
        /* ========================================
           GT Toggle Switch — Pure CSS, GPU-accelerated
           ======================================== */

        .gt-toggle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .gt-toggle__input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .gt-toggle__track {
            position: relative;
            display: inline-flex;
            align-items: center;
            width: 50px;
            height: 26px;
            border-radius: 999px;
            background: #e2e8f0;
            transition: background 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                        box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: inset 0 1px 3px rgba(0,0,0,.08);
            flex-shrink: 0;
        }

        .gt-toggle__thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,.18), 0 0 0 1px rgba(0,0,0,.04);
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1),
                        box-shadow 0.3s ease;
            will-change: transform;
        }

        .gt-toggle:hover .gt-toggle__track {
            box-shadow: inset 0 1px 3px rgba(0,0,0,.16);
        }

        .gt-toggle:hover .gt-toggle__thumb {
            box-shadow: 0 2px 8px rgba(0,0,0,.22), 0 0 0 1px rgba(0,0,0,.06);
        }

        /* ON state */
        .gt-toggle--on .gt-toggle__track {
            background: #00bc8c;
            box-shadow: inset 0 1px 3px rgba(0,188,140,.25);
        }

        .gt-toggle--on .gt-toggle__thumb {
            transform: translateX(24px);
        }

        .gt-toggle--on:hover .gt-toggle__track {
            background: #00a87d;
            box-shadow: inset 0 1px 4px rgba(0,188,140,.35);
        }

        /* Busy state */
        .gt-toggle--busy {
            cursor: wait;
            opacity: 0.72;
        }

        .gt-toggle--busy .gt-toggle__thumb {
            animation: gt-toggle-pulse 0.9s ease-in-out infinite;
        }

        @keyframes gt-toggle-pulse {
            0%, 100% { box-shadow: 0 1px 4px rgba(0,0,0,.18), 0 0 0 0 rgba(0,188,140,.4); }
            50%      { box-shadow: 0 1px 4px rgba(0,0,0,.18), 0 0 0 6px rgba(0,188,140,0); }
        }

        .gt-toggle--on.gt-toggle--busy .gt-toggle__thumb {
            animation: gt-toggle-pulse-on 0.9s ease-in-out infinite;
        }

        @keyframes gt-toggle-pulse-on {
            0%, 100% { box-shadow: 0 1px 4px rgba(0,0,0,.18), 0 0 0 0 rgba(0,188,140,.4); }
            50%      { box-shadow: 0 1px 4px rgba(0,0,0,.18), 0 0 0 6px rgba(0,188,140,0); }
        }

        /* Status label */
        .gt-toggle__label {
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            min-width: 32px;
            color: #94a3b8;
            transition: color 0.25s ease;
        }

        .gt-toggle--on .gt-toggle__label {
            color: #00a87d;
        }

        /* Focus visible */
        .gt-toggle__input:focus-visible + .gt-toggle__track {
            box-shadow: inset 0 1px 3px rgba(0,0,0,.12), 0 0 0 3px rgba(0,188,140,.35);
        }

        /* ========================================
           Dark Mode
           ======================================== */

        body.dark-mode .gt-toggle__track {
            background: #4a5568;
            box-shadow: inset 0 1px 3px rgba(0,0,0,.35);
        }

        body.dark-mode .gt-toggle--on .gt-toggle__track {
            background: #00bc8c;
            box-shadow: inset 0 1px 3px rgba(0,188,140,.3);
        }

        body.dark-mode .gt-toggle__thumb {
            background: #e2e8f0;
            box-shadow: 0 1px 4px rgba(0,0,0,.35);
        }

        body.dark-mode .gt-toggle__label {
            color: #a0aec0;
        }

        body.dark-mode .gt-toggle--on .gt-toggle__label {
            color: #4fd1a5;
        }

        /* ========================================
           Dashboard Layout
           ======================================== */

        .lotto-switch-dashboard .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06), 0 2px 12px rgba(0,0,0,.04);
            overflow: hidden;
        }

        body.dark-mode .lotto-switch-dashboard .card {
            background: #1e293b;
            box-shadow: 0 1px 4px rgba(0,0,0,.3), 0 2px 12px rgba(0,0,0,.2);
        }

        .lotto-switch-dashboard .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(0,0,0,.05);
            padding: 0.85rem 1.1rem;
        }

        body.dark-mode .lotto-switch-dashboard .card-header {
            border-bottom-color: rgba(255,255,255,.06);
        }

        .lotto-switch-dashboard .card-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -0.01em;
        }

        body.dark-mode .lotto-switch-dashboard .card-title {
            color: #e2e8f0;
        }

        .lotto-switch-dashboard .card-body {
            padding: 1rem 1.1rem;
        }

        /* ========================================
           Alert
           ======================================== */

        .lotto-switch-dashboard .alert {
            background: linear-gradient(135deg, #f0f9ff 0%, #ecfdf5 100%);
            border: 1px solid rgba(0,188,140,.15);
            border-radius: 8px;
            padding: 0.6rem 0.9rem;
            margin-bottom: 0;
            font-size: 0.84rem;
            color: #475569;
            line-height: 1.4;
        }

        body.dark-mode .lotto-switch-dashboard .alert {
            background: linear-gradient(135deg, rgba(59,130,246,.08) 0%, rgba(0,188,140,.08) 100%);
            border-color: rgba(0,188,140,.15);
            color: #a0aec0;
        }

        /* ========================================
           Group Tabs
           ======================================== */

        .lotto-switch-dashboard .group-tab-wrap {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            gap: 6px;
            scroll-behavior: smooth;
        }

        .lotto-switch-dashboard .group-tab-wrap::-webkit-scrollbar {
            height: 4px;
        }

        .lotto-switch-dashboard .group-tab-wrap::-webkit-scrollbar-track {
            background: transparent;
        }

        .lotto-switch-dashboard .group-tab-wrap::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,.12);
            border-radius: 999px;
        }

        body.dark-mode .lotto-switch-dashboard .group-tab-wrap::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,.1);
        }

        .lotto-switch-dashboard .btn-group-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.4rem 0.85rem;
            font-size: 0.82rem;
            font-weight: 600;
            border-radius: 999px;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            color: #64748b;
            white-space: nowrap;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            flex: 0 0 auto;
            line-height: 1.3;
        }

        body.dark-mode .lotto-switch-dashboard .btn-group-tab {
            background: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }

        .lotto-switch-dashboard .btn-group-tab:hover {
            border-color: #00bc8c;
            color: #00a87d;
            background: #f0fdf9;
        }

        body.dark-mode .lotto-switch-dashboard .btn-group-tab:hover {
            background: rgba(0,188,140,.1);
            color: #4fd1a5;
        }

        .lotto-switch-dashboard .btn-group-tab.btn-primary {
            background: #00bc8c;
            border-color: #00bc8c;
            color: #fff;
            box-shadow: 0 2px 8px rgba(0,188,140,.3);
        }

        .lotto-switch-dashboard .btn-group-tab.btn-primary:hover {
            background: #00a87d;
            border-color: #00a87d;
            box-shadow: 0 3px 12px rgba(0,188,140,.4);
        }

        .lotto-switch-dashboard .btn-group-tab .badge {
            font-size: 0.68rem;
            font-weight: 700;
            padding: 0.15em 0.5em;
            border-radius: 999px;
            letter-spacing: 0.02em;
        }

        .lotto-switch-dashboard .btn-group-tab .badge-success {
            background: #00bc8c;
            color: #fff;
        }

        .lotto-switch-dashboard .btn-group-tab.btn-primary .badge-success {
            background: rgba(255,255,255,.28);
            color: #fff;
        }

        .lotto-switch-dashboard .btn-group-tab .badge-danger {
            background: #fee2e2;
            color: #dc2626;
        }

        body.dark-mode .lotto-switch-dashboard .btn-group-tab .badge-danger {
            background: rgba(239,68,68,.2);
            color: #f87171;
        }

        /* ========================================
           Group Header
           ======================================== */

        .lotto-switch-dashboard .active-group-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(0,0,0,.06);
        }

        body.dark-mode .lotto-switch-dashboard .active-group-head {
            border-bottom-color: rgba(255,255,255,.06);
        }

        .lotto-switch-dashboard .active-group-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -0.01em;
        }

        body.dark-mode .lotto-switch-dashboard .active-group-name {
            color: #e2e8f0;
        }

        /* ========================================
           Section Title
           ======================================== */

        .lotto-switch-dashboard .section-title {
            font-size: 0.76rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #94a3b8;
            margin-bottom: 0.6rem;
        }

        body.dark-mode .lotto-switch-dashboard .section-title {
            color: #64748b;
        }

        /* ========================================
           Market Rows
           ======================================== */

        .lotto-switch-dashboard .market-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.6rem 0.85rem;
            margin-bottom: 5px;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,.05);
            background: #fff;
            gap: 0.75rem;
            transition: transform 0.2s ease,
                        box-shadow 0.2s ease,
                        border-color 0.2s ease,
                        background 0.2s ease;
            animation: marketRowIn 0.4s ease both;
        }

        body.dark-mode .lotto-switch-dashboard .market-row {
            background: #0f172a;
            border-color: rgba(255,255,255,.05);
        }

        .lotto-switch-dashboard .market-row:hover {
            transform: translateX(3px);
            border-color: rgba(0,188,140,.15);
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
            background: #fafdfc;
        }

        body.dark-mode .lotto-switch-dashboard .market-row:hover {
            background: #162032;
            border-color: rgba(0,188,140,.2);
            box-shadow: 0 2px 12px rgba(0,0,0,.25);
        }

        @keyframes marketRowIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ========================================
           Market Label
           ======================================== */

        .lotto-switch-dashboard .market-label {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            min-width: 0;
            flex: 1;
        }

        .lotto-switch-dashboard .market-thumb {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #f1f5f9;
            flex-shrink: 0;
            transition: border-color 0.2s ease;
        }

        body.dark-mode .lotto-switch-dashboard .market-thumb {
            border-color: #334155;
        }

        .lotto-switch-dashboard .market-row:hover .market-thumb {
            border-color: rgba(0,188,140,.25);
        }

        .lotto-switch-dashboard .market-label span {
            font-weight: 600;
            font-size: 0.87rem;
            color: #334155;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }

        body.dark-mode .lotto-switch-dashboard .market-label span {
            color: #e2e8f0;
        }

        .lotto-switch-dashboard .market-label small {
            font-size: 0.72rem;
            color: #94a3b8;
            font-weight: 500;
            white-space: nowrap;
            letter-spacing: 0.03em;
        }

        body.dark-mode .lotto-switch-dashboard .market-label small {
            color: #64748b;
        }

        /* ========================================
           Empty State
           ======================================== */

        .lotto-switch-dashboard .empty-state {
            text-align: center;
            padding: 1.5rem 1rem;
            color: #94a3b8;
            font-size: 0.85rem;
        }
    </style>
@endpush

@push('scripts')
    <script type="text/x-template" id="lotto-switch-dashboard-template">
        <section class="content text-sm lotto-switch-dashboard">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-12">
                        <div class="alert alert-info mb-0 d-flex align-items-center gap-2">
                            <i class="fas fa-info-circle" style="opacity:.6;font-size:1rem"></i>
                            <span>เมนูนี้ใช้ควบคุมสถานะเปิด-ปิดของกลุ่มหวยและรายการหวยโดยตรง เมื่อปรับแล้วมีผลกับผู้เล่นทุกคนทันที</span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title mb-0">กลุ่มหวยและรายการหวย</h3>
                            </div>
                            <div class="card-body">
                                <div class="group-tab-wrap">
                                    <button v-for="item in groups"
                                            :key="'g-tab-' + item.id"
                                            type="button"
                                            class="btn btn-sm btn-group-tab"
                                            :class="selectedGroupId === item.id ? 'btn-primary' : ''"
                                            @click="selectGroup(item)">
                                        <span>@{{ item.name }}</span>
                                        <span class="badge" :class="item.is_enabled ? 'badge-success' : 'badge-danger'">
                                            @{{ item.is_enabled ? 'เปิด' : 'ปิด' }}
                                        </span>
                                    </button>
                                    <span v-if="groups.length === 0" class="text-muted">ไม่มีข้อมูลกลุ่มหวย</span>
                                </div>

                                <div v-if="activeGroup">
                                    <div class="active-group-head">
                                        <h5 class="mb-0 active-group-name">@{{ activeGroup.name }}</h5>
                                        <label class="gt-toggle"
                                               :class="{ 'gt-toggle--on': Boolean(activeGroup.is_enabled), 'gt-toggle--busy': Boolean(activeGroup._busy) }">
                                            <input type="checkbox"
                                                   class="gt-toggle__input"
                                                   :checked="Boolean(activeGroup.is_enabled)"
                                                   :disabled="Boolean(activeGroup._busy)"
                                                   @change="onToggleChange($event, 'group', activeGroup)">
                                            <span class="gt-toggle__track">
                                                <span class="gt-toggle__thumb"></span>
                                            </span>
                                            <span class="gt-toggle__label">@{{ activeGroup.is_enabled ? 'เปิด' : 'ปิด' }}</span>
                                        </label>
                                    </div>

                                    <div class="section-title">
                                        <i class="fas fa-list-ul mr-1" style="font-size:0.65rem"></i>
                                        รายการหวยในกลุ่ม
                                    </div>

                                    <div v-for="(item, index) in filteredMarkets"
                                         :key="'m-row-' + item.id"
                                         class="market-row"
                                         :style="{ animationDelay: (index * 35) + 'ms' }">
                                        <div class="market-label">
                                            <img v-if="item.logo || item.icon"
                                                 :src="item.logo || item.icon"
                                                 alt=""
                                                 class="market-thumb">
                                            <span>@{{ item.name }}</span>
                                            <small>@{{ item.code }}</small>
                                        </div>
                                        <label class="gt-toggle"
                                               :class="{ 'gt-toggle--on': Boolean(item.is_enabled), 'gt-toggle--busy': Boolean(item._busy) }">
                                            <input type="checkbox"
                                                   class="gt-toggle__input"
                                                   :checked="Boolean(item.is_enabled)"
                                                   :disabled="Boolean(item._busy)"
                                                   @change="onToggleChange($event, 'market', item)">
                                            <span class="gt-toggle__track">
                                                <span class="gt-toggle__thumb"></span>
                                            </span>
                                            <span class="gt-toggle__label">@{{ item.is_enabled ? 'เปิด' : 'ปิด' }}</span>
                                        </label>
                                    </div>
                                    <div v-if="filteredMarkets.length === 0" class="empty-state">
                                        <i class="fas fa-inbox d-block mb-1" style="font-size:1.5rem;opacity:.3"></i>
                                        ไม่มีรายการหวยในกลุ่มนี้
                                    </div>
                                </div>
                                <div v-else class="empty-state">กรุณาเลือกกลุ่มหวย</div>
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
            },
            methods: {
                selectGroup(item) {
                    if (this.selectedGroupId === item.id) {
                        return;
                    }
                    this.selectedGroupId = item.id;
                },

                resolveSwitchItem(type, id) {
                    const list = type === 'group' ? this.groups : this.markets;
                    return list.find((entry) => Number(entry.id) === Number(id)) || null;
                },

                onToggleChange(event, type, item) {
                    this.applySwitchChange(type, item.id, event.target.checked);
                },

                async applySwitchChange(type, id, nextState) {
                    const item = this.resolveSwitchItem(type, id);
                    if (!item) {
                        return;
                    }

                    const previous = Boolean(item.is_enabled);
                    const next = Boolean(nextState);
                    if (previous === next || item._busy) {
                        return;
                    }

                    this.$set(item, '_busy', true);
                    this.$set(item, 'is_enabled', next);

                    try {
                        if (type === 'group') {
                            await this.updateGroup(item, next);
                        } else {
                            await this.updateMarket(item, next);
                        }
                    } catch (error) {
                        this.$set(item, 'is_enabled', previous);
                    } finally {
                        this.$set(item, '_busy', false);
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
            },
        });
    </script>
    @include('admin::layouts.loadcnt_js')
@endpush
