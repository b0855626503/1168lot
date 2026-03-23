@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <lotto-rate-plans-dashboard></lotto-rate-plans-dashboard>
@endsection

@push('styles')
    <style>
        .lotto-rate-plans-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .lotto-rate-plans-tabs {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            margin-bottom: 0;
            flex: 1;
        }

        .lotto-rate-plans-display-mode {
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
            gap: 0.35rem;
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .lotto-rate-plans-market-label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            white-space: nowrap;
        }

        .lotto-copy-group-box {
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .lotto-copy-group-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.35rem;
        }

        .lotto-copy-market-list {
            max-height: 180px;
            overflow-y: auto;
            padding-right: 0.25rem;
        }
    </style>
@endpush

@push('scripts')
    <script type="text/x-template" id="lotto-rate-plans-dashboard-template">
        <section class="content text-xs">
            <div class="card">
                <div class="card-body">
                    <div class="lotto-rate-plans-toolbar">
                        <ul class="nav nav-tabs lotto-rate-plans-tabs" role="tablist">
                            <li class="nav-item" v-for="(group, index) in groups" :key="'tab-' + group.id">
                                <a href="javascript:void(0)"
                                   class="nav-link"
                                   :class="{ active: activeGroupIndex === index }"
                                   @click.prevent="setActiveGroup(index)">
                                    @{{ group.name }}
                                </a>
                            </li>
                        </ul>

                        <div class="lotto-rate-plans-display-mode">
                            <label class="mb-0 mr-1">แสดงค่าในตาราง:</label>
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
                                    <div class="lotto-rate-plans-market-label">
                                        <strong>@{{ market.name }}</strong>
                                        <small class="text-muted">@{{ market.code }}</small>
                                    </div>
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

            <b-modal ref="copyModal"
                     id="rate-plan-copy-modal"
                     centered
                     size="lg"
                     title="คัดลอกค่าที่กรอกไปยังหลายรายการหวย"
                     :no-close-on-backdrop="true"
                     :hide-footer="true">
                <b-form @submit.prevent="submitCopyFromReference">
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                            <tr>
                                <th style="min-width: 120px;">ประเภท</th>
                                <th style="min-width: 130px;">อัตราจ่าย</th>
                                <th style="min-width: 130px;">ส่วนลด(%)</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="type in betTypes" :key="'copy-setting-' + type.key">
                                <td>@{{ type.label }}</td>
                                <td>
                                    <b-form-input v-model.number="copyForm.settings[type.key].payout"
                                                  type="number"
                                                  step="0.01"
                                                  min="0"
                                                  size="sm"
                                                  required>
                                    </b-form-input>
                                </td>
                                <td>
                                    <b-form-input v-model.number="copyForm.settings[type.key].discount_percent"
                                                  type="number"
                                                  step="0.01"
                                                  min="0"
                                                  max="100"
                                                  size="sm">
                                    </b-form-input>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-2">
                        <strong>เลือกรายการหวยปลายทาง</strong>
                    </div>

                    <div v-for="group in groups" :key="'copy-group-' + group.id" class="lotto-copy-group-box">
                        <div class="lotto-copy-group-head">
                            <strong>@{{ group.name }}</strong>
                            <b-form-checkbox
                                :checked="isGroupFullySelected(group)"
                                @change="toggleGroupTargets(group, $event)"
                                switch
                                size="sm">
                                เลือกทั้งกลุ่ม
                            </b-form-checkbox>
                        </div>

                        <div class="lotto-copy-market-list">
                            <b-form-checkbox v-for="market in group.markets"
                                             :key="'copy-market-' + market.id"
                                             v-model="copyForm.target_market_ids"
                                             :value="market.id"
                                             size="sm">
                                @{{ market.name }} <small class="text-muted">@{{ market.code }}</small>
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
        Vue.component('lotto-rate-plans-dashboard', {
            template: '#lotto-rate-plans-dashboard-template',
            data() {
                const initialBetTypes = @json($betTypes ?? []);
                const initialCopySettings = {};
                initialBetTypes.forEach((type) => {
                    initialCopySettings[type.key] = {
                        payout: 0,
                        discount_percent: 0,
                    };
                });

                return {
                    groups: @json($groupTabs ?? []),
                    betTypes: initialBetTypes,
                    activeGroupIndex: 0,
                    displayMode: 'payout',
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
                    copyForm: {
                        settings: initialCopySettings,
                        target_market_ids: [],
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
                            const targetRow = target.settings[key] || { payout: 0, discount_percent: 0 };
                            targetRow.payout = Number(sourceRow.payout || 0);
                            targetRow.discount_percent = Number(sourceRow.discount_percent || 0);
                            this.$set(target.settings, key, targetRow);
                        });
                    });
                },
                async submitCopyFromReference() {
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

                    const response = await axios.post("{{ route('admin.lotto.rate_plans.copy_from_reference') }}", {
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
@endpush
