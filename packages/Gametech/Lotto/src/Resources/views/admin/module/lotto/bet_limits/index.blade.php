@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <lotto-bet-limits-dashboard></lotto-bet-limits-dashboard>
@endsection

@push('scripts')
    <script type="text/x-template" id="lotto-bet-limits-dashboard-template">
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
                        <label class="mb-0 mr-2">แสดงค่าในช่อง:</label>
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
                                    :colspan="columnSpan"
                                    class="text-center">
                                    @{{ type.label }}
                                </th>
                                <th rowspan="2" class="text-center align-middle" style="min-width: 80px;">จัดการ</th>
                            </tr>
                            <tr>
                                <template v-for="type in betTypes">
                                    <th v-if="showMinBet" :key="'min-' + type.key" class="text-center">ขั้นต่ำ</th>
                                    <th v-if="showMaxBet" :key="'max-' + type.key" class="text-center">สูงสุด</th>
                                    <th v-if="showMaxPerNumber" :key="'maxpn-' + type.key" class="text-center">สูงสุดต่อเลข</th>
                                    <th v-if="showMinMaxPair" :key="'pair-' + type.key" class="text-center">ขั้นต่ำ-สูงสุด</th>
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
                                    <td v-if="showMinBet" :key="'vmin-' + market.id + '-' + type.key" class="text-right">
                                        @{{ renderValue(getSettingValue(market, type.key, 'min_bet')) }}
                                    </td>
                                    <td v-if="showMaxBet" :key="'vmax-' + market.id + '-' + type.key" class="text-right">
                                        @{{ renderValue(getSettingValue(market, type.key, 'max_bet')) }}
                                    </td>
                                    <td v-if="showMaxPerNumber" :key="'vmaxpn-' + market.id + '-' + type.key" class="text-right">
                                        @{{ renderValue(getSettingValue(market, type.key, 'max_per_number')) }}
                                    </td>
                                    <td v-if="showMinMaxPair" :key="'vpair-' + market.id + '-' + type.key" class="text-right">
                                        @{{ renderPair(market, type.key) }}
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
                     id="bet-limit-edit-modal"
                     centered
                     size="lg"
                     title="แก้ไขขั้นต่ำ/สูงสุด/สูงสุดต่อเลข"
                     :no-close-on-backdrop="true"
                     :hide-footer="true">
                <div v-if="editingMarket">
                    <h6 class="mb-3">รายการหวย: <strong>@{{ editingMarket.market_name }}</strong></h6>
                    <b-form @submit.prevent="submitEdit">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-3">
                                <thead>
                                <tr>
                                    <th>ประเภท</th>
                                    <th>ขั้นต่ำ</th>
                                    <th>สูงสุด</th>
                                    <th>สูงสุดต่อเลข</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="type in betTypes" :key="'e-' + type.key">
                                    <td>@{{ type.label }}</td>
                                    <td><b-form-input v-model.number="editForm.settings[type.key].min_bet" type="number" step="0.01" min="0" size="sm" required></b-form-input></td>
                                    <td><b-form-input v-model.number="editForm.settings[type.key].max_bet" type="number" step="0.01" min="0" size="sm" required></b-form-input></td>
                                    <td><b-form-input v-model.number="editForm.settings[type.key].max_per_number" type="number" step="0.01" min="0" size="sm" required></b-form-input></td>
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
        Vue.component('lotto-bet-limits-dashboard', {
            template: '#lotto-bet-limits-dashboard-template',
            data() {
                return {
                    groups: @json($groupTabs ?? []),
                    betTypes: @json($betTypes ?? []),
                    activeGroupIndex: 0,
                    displayMode: 'min_max_pair',
                    displayModeOptions: [
                        { value: 'min_bet', text: 'ขั้นต่ำ' },
                        { value: 'max_bet', text: 'สูงสุด' },
                        { value: 'max_per_number', text: 'สูงสุดต่อเลข' },
                        { value: 'min_max_pair', text: 'ขั้นต่ำ-สูงสุด' },
                        { value: 'all', text: 'ทั้งหมด' },
                    ],
                    editingMarket: null,
                    editForm: {
                        market_id: null,
                        settings: {},
                    },
                };
            },
            computed: {
                activeGroup() { return this.groups[this.activeGroupIndex] || null; },
                showMinBet() { return this.displayMode === 'min_bet' || this.displayMode === 'all'; },
                showMaxBet() { return this.displayMode === 'max_bet' || this.displayMode === 'all'; },
                showMaxPerNumber() { return this.displayMode === 'max_per_number' || this.displayMode === 'all'; },
                showMinMaxPair() { return this.displayMode === 'min_max_pair'; },
                columnSpan() {
                    if (this.showMinMaxPair) return 1;
                    let span = 0;
                    if (this.showMinBet) span++;
                    if (this.showMaxBet) span++;
                    if (this.showMaxPerNumber) span++;
                    return span > 0 ? span : 1;
                },
            },
            methods: {
                formatNumber(value) {
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0,
                    }).format(Math.round(Number(value)));
                },
                setActiveGroup(index) { this.activeGroupIndex = index; },
                renderValue(value) { return (value === null || typeof value === 'undefined') ? '-' : this.formatNumber(value); },
                getSettingValue(market, betType, field) {
                    if (! market || ! market.settings || ! market.settings[betType]) return null;
                    return market.settings[betType][field];
                },
                renderPair(market, betType) {
                    const minVal = this.getSettingValue(market, betType, 'min_bet');
                    const maxVal = this.getSettingValue(market, betType, 'max_bet');
                    if (minVal === null || maxVal === null || typeof minVal === 'undefined' || typeof maxVal === 'undefined') return '-';
                    return this.formatNumber(minVal) + ' - ' + this.formatNumber(maxVal);
                },
                buildEmptySettings() {
                    const settings = {};
                    this.betTypes.forEach((type) => {
                        settings[type.key] = { min_bet: 0, max_bet: 0, max_per_number: 0 };
                    });
                    return settings;
                },
                async openEditModal(market) {
                    const response = await axios.post("{{ route('admin.lotto.bet_limits.load_market') }}", { market_id: market.id });
                    const responseData = response && response.data ? response.data : {};
                    const data = responseData.data || {};
                    const settings = this.buildEmptySettings();
                    Object.keys(settings).forEach((betType) => {
                        const row = data.bet_settings && data.bet_settings[betType] ? data.bet_settings[betType] : {};
                        settings[betType].min_bet = typeof row.min_bet !== 'undefined' ? row.min_bet : 0;
                        settings[betType].max_bet = typeof row.max_bet !== 'undefined' ? row.max_bet : 0;
                        settings[betType].max_per_number = typeof row.max_per_number !== 'undefined' ? row.max_per_number : 0;
                    });
                    this.editingMarket = { market_id: data.market_id, market_name: data.market_name };
                    this.editForm = { market_id: data.market_id, settings };
                    this.$refs.editModal.show();
                },
                async submitEdit() {
                    await axios.post("{{ route('admin.lotto.bet_limits.update_market') }}", this.editForm);
                    const group = this.activeGroup;
                    if (group) {
                        const target = group.markets.find((item) => item.id === this.editForm.market_id);
                        if (target) {
                            Object.keys(this.editForm.settings).forEach((betType) => {
                                target.settings[betType] = {
                                    min_bet: Number(this.editForm.settings[betType].min_bet || 0),
                                    max_bet: Number(this.editForm.settings[betType].max_bet || 0),
                                    max_per_number: Number(this.editForm.settings[betType].max_per_number || 0),
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
