@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <lotto-rate-plans-dashboard></lotto-rate-plans-dashboard>
@endsection

@push('scripts')
    <script type="text/x-template" id="lotto-rate-plans-dashboard-template">
        <section class="content text-xs">
            <div class="card">
                <div class="card-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" v-for="(group, index) in groups" :key="'tab-' + group.id">
                            <a href="javascript:void(0)"
                               class="nav-link"
                               :class="{ active: activeGroupIndex === index }"
                               @click.prevent="setActiveGroup(index)">
                                @{{ group.name }}
                            </a>
                        </li>
                    </ul>

                    <div class="d-flex align-items-center mb-3">
                        <label class="mb-0 mr-2">แสดงค่าในตาราง:</label>
                        <b-form-radio-group
                            v-model="displayMode"
                            :options="displayModeOptions"
                            buttons
                            button-variant="outline-primary"
                            size="sm">
                        </b-form-radio-group>
                    </div>

                    <div v-if="activeGroup && activeGroup.markets.length > 0" class="table-responsive">
                        <table class="table table-bordered table-sm table-striped">
                            <thead>
                            <tr>
                                <th rowspan="2" class="text-center align-middle" style="min-width: 200px;">รายการหวย</th>
                                <th v-for="type in betTypes"
                                    :key="'h-' + type.key"
                                    :colspan="isBothMode ? 2 : 1"
                                    class="text-center">
                                    @{{ type.label }}
                                </th>
                                <th rowspan="2" class="text-center align-middle" style="min-width: 80px;">จัดการ</th>
                            </tr>
                            <tr>
                                <template v-for="type in betTypes">
                                    <th v-if="displayMode !== 'discount'" :key="'p-' + type.key" class="text-center" style="min-width: 95px;">อัตราจ่าย</th>
                                    <th v-if="displayMode !== 'payout'" :key="'d-' + type.key" class="text-center" style="min-width: 95px;">ส่วนลด(%)</th>
                                </template>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="market in activeGroup.markets" :key="'m-' + market.id">
                                <td>
                                    <strong>@{{ market.name }}</strong>
                                    <br>
                                    <small class="text-muted">@{{ market.code }}</small>
                                </td>
                                <template v-for="type in betTypes">
                                    <td v-if="displayMode !== 'discount'" :key="'pv-' + market.id + '-' + type.key" class="text-right">
                                        @{{ renderValue(getSettingValue(market, type.key, 'payout')) }}
                                    </td>
                                    <td v-if="displayMode !== 'payout'" :key="'dv-' + market.id + '-' + type.key" class="text-right">
                                        @{{ renderValue(getSettingValue(market, type.key, 'discount_percent')) }}
                                    </td>
                                </template>
                                <td class="text-center">
                                    <button type="button" class="btn btn-primary btn-xs" @click="openEditModal(market)">
                                        แก้ไข
                                    </button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="alert alert-info mb-0">
                        ไม่พบรายการหวยในกลุ่มนี้
                    </div>
                </div>
            </div>

            <b-modal ref="editModal"
                     id="rate-plan-edit-modal"
                     centered
                     size="lg"
                     title="แก้ไขอัตราจ่าย / ส่วนลด"
                     :no-close-on-backdrop="true"
                     :hide-footer="true">
                <div v-if="editingMarket">
                    <h6 class="mb-3">
                        รายการหวย: <strong>@{{ editingMarket.market_name }}</strong>
                    </h6>
                    <b-form @submit.prevent="submitEdit">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-3">
                                <thead>
                                <tr>
                                    <th style="min-width: 130px;">ประเภท</th>
                                    <th style="min-width: 140px;">อัตราจ่าย</th>
                                    <th style="min-width: 140px;">ส่วนลด(%)</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="type in betTypes" :key="'e-' + type.key">
                                    <td>@{{ type.label }}</td>
                                    <td>
                                        <b-form-input v-model.number="editForm.settings[type.key].payout"
                                                      type="number"
                                                      step="0.01"
                                                      min="0"
                                                      size="sm"
                                                      required></b-form-input>
                                    </td>
                                    <td>
                                        <b-form-input v-model.number="editForm.settings[type.key].discount_percent"
                                                      type="number"
                                                      step="0.01"
                                                      min="0"
                                                      max="100"
                                                      size="sm"></b-form-input>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm">บันทึก</button>
                    </b-form>
                </div>
            </b-modal>
        </section>
    </script>

    <script type="module">
        Vue.component('lotto-rate-plans-dashboard', {
            template: '#lotto-rate-plans-dashboard-template',
            data() {
                return {
                    groups: @json($groupTabs ?? []),
                    betTypes: @json($betTypes ?? []),
                    activeGroupIndex: 0,
                    displayMode: 'both',
                    displayModeOptions: [
                        { value: 'payout', text: 'อัตราจ่าย' },
                        { value: 'discount', text: 'ส่วนลด(%)' },
                        { value: 'both', text: 'ทั้งคู่' },
                    ],
                    editingMarket: null,
                    editForm: {
                        market_id: null,
                        settings: {},
                    },
                };
            },
            computed: {
                activeGroup() {
                    return this.groups[this.activeGroupIndex] || null;
                },
                isBothMode() {
                    return this.displayMode === 'both';
                },
            },
            methods: {
                formatNumber(value) {
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }).format(Number(value));
                },
                setActiveGroup(index) {
                    this.activeGroupIndex = index;
                },
                renderValue(value) {
                    if (value === null || typeof value === 'undefined') {
                        return '-';
                    }

                    return this.formatNumber(value);
                },
                getSettingValue(market, betType, field) {
                    if (! market || ! market.settings || ! market.settings[betType]) {
                        return null;
                    }

                    return market.settings[betType][field];
                },
                buildEmptySettings() {
                    const settings = {};
                    this.betTypes.forEach((type) => {
                        settings[type.key] = {
                            payout: 0,
                            discount_percent: 0,
                        };
                    });

                    return settings;
                },
                async openEditModal(market) {
                    const response = await axios.post("{{ route('admin.lotto.rate_plans.load_market') }}", {
                        market_id: market.id,
                    });

                    const responseData = response && response.data ? response.data : {};
                    const data = responseData.data || {};
                    const settings = this.buildEmptySettings();

                    Object.keys(settings).forEach((betType) => {
                        const row = data.bet_settings && data.bet_settings[betType] ? data.bet_settings[betType] : {};
                        settings[betType].payout = typeof row.payout !== 'undefined' ? row.payout : 0;
                        settings[betType].discount_percent = typeof row.discount_percent !== 'undefined' ? row.discount_percent : 0;
                    });

                    this.editingMarket = {
                        market_id: data.market_id,
                        market_name: data.market_name,
                    };
                    this.editForm = {
                        market_id: data.market_id,
                        settings,
                    };

                    this.$refs.editModal.show();
                },
                async submitEdit() {
                    await axios.post("{{ route('admin.lotto.rate_plans.update_market') }}", this.editForm);

                    const group = this.activeGroup;
                    if (group) {
                        const target = group.markets.find((item) => item.id === this.editForm.market_id);
                        if (target) {
                            Object.keys(this.editForm.settings).forEach((betType) => {
                                target.settings[betType] = {
                                    payout: Number(this.editForm.settings[betType].payout || 0),
                                    discount_percent: Number(this.editForm.settings[betType].discount_percent || 0),
                                };
                            });
                        }
                    }

                    this.$bvModal.msgBoxOk('บันทึกข้อมูลเรียบร้อยแล้ว', {
                        title: 'ผลการดำเนินการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'success',
                        centered: true,
                    });
                    this.$refs.editModal.hide();
                },
            },
        });
    </script>
@endpush
