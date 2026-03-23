@extends('admin::layouts.master')

@section('title')
    เปิด-ปิด หวย
@endsection

@section('content')
    <lotto-switch-dashboard></lotto-switch-dashboard>
@endsection

@push('scripts')
    <script type="text/x-template" id="lotto-switch-dashboard-template">
        <section class="content text-sm">
            <div class="container-fluid">
                <div class="row mb-3">
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
                                <h3 class="card-title">กลุ่มหวย</h3>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center border-bottom pb-2 mb-3">
                                    <span class="mr-2 mb-2 font-weight-bold">tab กลุ่มหวย</span>
                                    <button v-for="item in groups"
                                            :key="'g-tab-' + item.id"
                                            type="button"
                                            class="btn btn-sm mr-2 mb-2"
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
                                    <div class="d-flex align-items-center mb-3">
                                        <h5 class="mb-0 mr-3">@{{ activeGroup.name }}</h5>
                                        <button type="button"
                                                class="btn btn-sm"
                                                :class="activeGroup.is_enabled ? 'btn-success' : 'btn-danger'"
                                                @click="toggleGroup(activeGroup)">
                                            @{{ activeGroup.is_enabled ? 'เปิด' : 'ปิด' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">รายการหวยในกลุ่ม</h3>
                            </div>
                            <div class="card-body">
                                <div v-if="activeGroup">
                                    <div v-for="item in filteredMarkets" :key="'m-row-' + item.id"
                                         class="d-flex align-items-center justify-content-between border rounded px-3 py-2 mb-2">
                                        <div>
                                            <div>@{{ item.name }}</div>
                                            <small class="text-muted">@{{ item.code }}</small>
                                        </div>
                                        <button type="button"
                                                class="btn btn-sm"
                                                :class="item.is_enabled ? 'btn-success' : 'btn-danger'"
                                                @click="toggleMarket(item)">
                                            @{{ item.is_enabled ? 'เปิด' : 'ปิด' }}
                                        </button>
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
            },
            methods: {
                selectGroup(item) {
                    this.selectedGroupId = item.id;
                },
                async toggleGroup(item) {
                    const nextStatus = item.is_enabled ? 0 : 1;
                    await axios.post("{{ route('admin.lotto.groups.edit') }}", {
                        id: item.id,
                        method: 'is_enabled',
                        status: nextStatus,
                    });
                    item.is_enabled = Boolean(nextStatus);
                },
                async toggleMarket(item) {
                    const nextStatus = item.is_enabled ? 0 : 1;
                    await axios.post("{{ route('admin.lotto.markets.edit') }}", {
                        id: item.id,
                        method: 'is_enabled',
                        status: nextStatus,
                    });
                    item.is_enabled = Boolean(nextStatus);
                },
            },
        });
    </script>
@endpush
