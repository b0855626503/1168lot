@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <lotto-bet-limits-dashboard></lotto-bet-limits-dashboard>
@endsection

@push('styles')
    <style>
        .lotto-bet-limits-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .lotto-bet-limits-tabs {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            margin-bottom: 0;
            flex: 1;
        }

        .lotto-bet-limits-display-mode {
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
            gap: 0.35rem;
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .lotto-bet-limits-market-label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            white-space: nowrap;
        }

        .lotto-bet-limits-market-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #dee2e6;
            flex: 0 0 20px;
        }

        .lotto-bet-copy-group-box {
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .lotto-bet-copy-group-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.35rem;
        }

        .lotto-bet-copy-market-list {
            max-height: 180px;
            overflow-y: auto;
            padding-right: 0.25rem;
        }
    </style>
@endpush

@push('scripts')
    <script type="text/x-template" id="lotto-bet-limits-dashboard-template">
        <section class="content text-xs">
            <div class="card">
                <div class="card-body">
                    <div class="lotto-bet-limits-toolbar">
                        <ul class="nav nav-tabs lotto-bet-limits-tabs" role="tablist">
                            <li class="nav-item" v-for="(group, index) in groups" :key="'tab-' + group.id">
                                <a href="javascript:void(0)"
                                   class="nav-link"
                                   :class="{ active: activeGroupIndex === index }"
                                   @click.prevent="setActiveGroup(index)">
                                    @{{ group.name }}
                                </a>
                            </li>
                        </ul>

                        <div class="lotto-bet-limits-display-mode">
                            <label class="mb-0 mr-1">แสดงค่าในช่อง:</label>
                            <b-form-radio-group
                                v-model="displayMode"
                                :options="displayModeOptions"
                                buttons
                                button-variant="outline-primary"
                                size="sm">
                            </b-form-radio-group>
                            <button type="button" class="btn btn-info btn-xs ml-2" @click="openCopyModal">
                                <i class="fa-solid fa-copy mr-1"></i>
                                คัดลอกค่าที่กรอก
                            </button>
                        </div>
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
                                    <div class="lotto-bet-limits-market-label">
                                        <img v-if="market.logo || market.icon" :src="market.logo || market.icon" alt="" class="lotto-bet-limits-market-thumb">
                                        <strong>@{{ market.name }}</strong>
                                        <small class="text-muted">@{{ market.code }}</small>
                                    </div>
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
                                    <button type="button" class="btn btn-info btn-xs" @click="openEditModal(market)">
                                        <i class="fa-solid fa-pen-to-square mr-1"></i>
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

            <b-modal ref="copyModal"
                     id="bet-limit-copy-modal"
                     centered
                     size="lg"
                     title="คัดลอกค่าที่กรอกไปยังหลายรายการหวย"
                     :no-close-on-backdrop="true"
                     :hide-footer="true">
                <b-form @submit.prevent="submitCopyTemplate">
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                            <tr>
                                <th style="min-width: 120px;">ประเภท</th>
                                <th style="min-width: 130px;">ขั้นต่ำ</th>
                                <th style="min-width: 130px;">สูงสุด</th>
                                <th style="min-width: 130px;">สูงสุดต่อเลข</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="type in betTypes" :key="'copy-setting-' + type.key">
                                <td>@{{ type.label }}</td>
                                <td>
                                    <b-form-input v-model.number="copyForm.settings[type.key].min_bet" type="number" step="0.01" min="0" size="sm" required></b-form-input>
                                </td>
                                <td>
                                    <b-form-input v-model.number="copyForm.settings[type.key].max_bet" type="number" step="0.01" min="0" size="sm" required></b-form-input>
                                </td>
                                <td>
                                    <b-form-input v-model.number="copyForm.settings[type.key].max_per_number" type="number" step="0.01" min="0" size="sm" required></b-form-input>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-2">
                        <strong>เลือกรายการหวยปลายทาง</strong>
                    </div>

                    <div v-for="group in groups" :key="'copy-group-' + group.id" class="lotto-bet-copy-group-box">
                        <div class="lotto-bet-copy-group-head">
                            <strong>@{{ group.name }}</strong>
                            <b-form-checkbox
                                :checked="isGroupFullySelected(group)"
                                @change="toggleGroupTargets(group, $event)"
                                switch
                                size="sm">
                                เลือกทั้งกลุ่ม
                            </b-form-checkbox>
                        </div>

                        <div class="lotto-bet-copy-market-list">
                            <b-form-checkbox v-for="market in group.markets"
                                             :key="'copy-market-' + market.id"
                                             v-model="copyForm.target_market_ids"
                                             :value="market.id"
                                             size="sm">
                                <span class="lotto-bet-limits-market-label">
                                    <img v-if="market.logo || market.icon" :src="market.logo || market.icon" alt="" class="lotto-bet-limits-market-thumb">
                                    <span>@{{ market.name }}</span>
                                    <small class="text-muted">@{{ market.code }}</small>
                                </span>
                            </b-form-checkbox>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">เลือกแล้ว @{{ copyForm.target_market_ids.length }} รายการ</small>
                        <button type="submit" class="btn btn-primary btn-sm">บันทึก</button>
                    </div>
                </b-form>
            </b-modal>
        </section>
    </script>

    <script type="module">
        Vue.component('lotto-bet-limits-dashboard', {
            template: '#lotto-bet-limits-dashboard-template',
            data() {
                const initialBetTypes = @json($betTypes ?? []);
                const initialCopySettings = {};
                initialBetTypes.forEach((type) => {
                    initialCopySettings[type.key] = {
                        min_bet: 0,
                        max_bet: 0,
                        max_per_number: 0,
                    };
                });

                return {
                    groups: @json($groupTabs ?? []),
                    betTypes: initialBetTypes,
                    activeGroupIndex: 0,
                    displayMode: 'min_bet',
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
                    copyForm: {
                        settings: initialCopySettings,
                        target_market_ids: [],
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
                findMarketById(marketId) {
                    const id = Number(marketId);
                    for (const group of this.groups) {
                        const market = (group.markets || []).find((item) => Number(item.id) === id);
                        if (market) {
                            return market;
                        }
                    }

                    return null;
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
                openCopyModal() {
                    this.copyForm = {
                        settings: this.buildEmptySettings(),
                        target_market_ids: [],
                    };
                    this.$refs.copyModal.show();
                },
                isGroupFullySelected(group) {
                    const candidates = (group.markets || [])
                        .map((market) => Number(market.id))
                        .filter((id) => id > 0);

                    if (candidates.length === 0) {
                        return false;
                    }

                    const selected = new Set((this.copyForm.target_market_ids || []).map((id) => Number(id)));
                    return candidates.every((id) => selected.has(id));
                },
                toggleGroupTargets(group, checked) {
                    const targetIds = new Set((this.copyForm.target_market_ids || []).map((id) => Number(id)));
                    (group.markets || []).forEach((market) => {
                        const marketId = Number(market.id);
                        if (marketId <= 0) {
                            return;
                        }

                        if (checked) {
                            targetIds.add(marketId);
                        } else {
                            targetIds.delete(marketId);
                        }
                    });

                    this.copyForm.target_market_ids = Array.from(targetIds).sort((a, b) => a - b);
                },
                applyCopiedSettingsLocally(targetMarketIds, settings) {
                    targetMarketIds.forEach((targetId) => {
                        const target = this.findMarketById(targetId);
                        if (!target) {
                            return;
                        }

                        if (!target.settings) {
                            this.$set(target, 'settings', {});
                        }

                        this.betTypes.forEach((type) => {
                            const key = type.key;
                            const sourceRow = settings[key] || {};
                            const targetRow = target.settings[key] || { min_bet: 0, max_bet: 0, max_per_number: 0 };
                            targetRow.min_bet = Number(sourceRow.min_bet || 0);
                            targetRow.max_bet = Number(sourceRow.max_bet || 0);
                            targetRow.max_per_number = Number(sourceRow.max_per_number || 0);
                            this.$set(target.settings, key, targetRow);
                        });
                    });
                },
                async submitCopyTemplate() {
                    const targetMarketIds = (this.copyForm.target_market_ids || [])
                        .map((id) => Number(id))
                        .filter((id) => id > 0);
                    const settings = this.copyForm.settings || {};

                    if (targetMarketIds.length === 0) {
                        await this.$bvModal.msgBoxOk('กรุณาเลือกรายการหวยปลายทางอย่างน้อย 1 รายการ', {
                            title: 'ข้อมูลไม่ครบ',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true,
                        });
                        return;
                    }

                    const response = await axios.post("{{ route('admin.lotto.bet_limits.copy_from_template') }}", {
                        target_market_ids: targetMarketIds,
                        settings,
                    });

                    this.applyCopiedSettingsLocally(targetMarketIds, settings);

                    await this.$bvModal.msgBoxOk(response?.data?.message || 'คัดลอกค่าที่กรอกเรียบร้อยแล้ว', {
                        title: 'ผลการดำเนินการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'success',
                        centered: true,
                    });

                    this.$refs.copyModal.hide();
                },
            },
        });
    </script>
    @include('admin::layouts.loadcnt_js')
@endpush
