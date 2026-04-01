@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <lotto-group-packages-dashboard></lotto-group-packages-dashboard>
@endsection

@push('styles')
    <style>
        .lotto-group-packages-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .lotto-group-packages-tabs {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            margin-bottom: 0;
            flex: 1;
        }

        .lotto-group-packages-actions {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .lotto-group-packages-package-tabs {
            margin-bottom: 0.75rem;
        }

        .lotto-group-packages-display-mode {
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
            gap: 0.35rem;
            white-space: nowrap;
            margin-bottom: 0.75rem;
        }

        .lotto-group-packages-market-label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            white-space: nowrap;
        }

        .lotto-group-packages-market-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #dee2e6;
            flex: 0 0 20px;
        }

        .lotto-group-packages-package-thumb {
            width: 56px;
            height: 56px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #dee2e6;
        }
    </style>
@endpush

@push('scripts')
    <script type="text/x-template" id="lotto-group-packages-dashboard-template">
        <section class="content text-xs">
            <div class="card">
                <div class="card-body">
                    <div class="lotto-group-packages-toolbar">
                        <ul class="nav nav-tabs lotto-group-packages-tabs" role="tablist">
                            <li class="nav-item" v-for="(group, index) in groups" :key="'group-tab-' + group.id">
                                <a href="javascript:void(0)"
                                   class="nav-link"
                                   :class="{ active: activeGroupIndex === index }"
                                   @click.prevent="setActiveGroup(index)">
                                    @{{ group.name }}
                                </a>
                            </li>
                        </ul>

                        <div class="lotto-group-packages-actions" v-if="activeGroup">
                            <button type="button" class="btn btn-primary btn-xs" @click="openCreatePackageModal">
                                <i class="fa-solid fa-plus mr-1"></i>
                                เพิ่มแพกเกจ
                            </button>
                        </div>
                    </div>

                    <template v-if="activeGroup && activeGroup.packages && activeGroup.packages.length > 0">
                        <ul class="nav nav-pills lotto-group-packages-package-tabs" role="tablist">
                            <li class="nav-item" v-for="(pkg, index) in activeGroup.packages" :key="'package-tab-' + pkg.id">
                                <a href="javascript:void(0)"
                                   class="nav-link"
                                   :class="{ active: getActivePackageIndex(activeGroup.id) === index }"
                                   @click.prevent="setActivePackage(activeGroup.id, index)">
                                    @{{ pkg.name }}
                                </a>
                            </li>
                        </ul>

                        <div class="card card-outline card-info" v-if="activePackage">
                            <div class="card-header py-2">
                                <h3 class="card-title text-sm mb-0">รายละเอียดแพกเกจ: @{{ activePackage.name }}</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-info btn-xs" @click="openEditPackageModal">
                                        <i class="fa-solid fa-pen-to-square mr-1"></i>
                                        แก้ไขแพกเกจ
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="mb-2" v-if="activePackage.image">
                                    <img :src="activePackage.image" alt="" class="lotto-group-packages-package-thumb">
                                </p>
                                <p class="mb-3" v-if="activePackage.description">@{{ activePackage.description }}</p>

                                <div class="lotto-group-packages-display-mode">
                                    <label class="mb-0 mr-1">แสดงค่าในตาราง:</label>
                                    <b-form-radio-group
                                        v-model="displayMode"
                                        :options="displayModeOptions"
                                        buttons
                                        button-variant="outline-primary"
                                        size="sm">
                                    </b-form-radio-group>
                                </div>

                                <div class="table-responsive mb-3">
                                    <table class="table table-bordered table-sm mb-0">
                                        <thead>
                                        <tr>
                                            <th rowspan="2" class="text-center align-middle" style="min-width: 220px;">รายการหวย</th>
                                            <th v-for="type in betTypes"
                                                :key="'h-' + type.key"
                                                :colspan="isBothMode ? 2 : 1"
                                                class="text-center">
                                                @{{ type.label }}
                                            </th>
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
                                                <div class="lotto-group-packages-market-label">
                                                    <img v-if="market.logo || market.icon" :src="market.logo || market.icon" alt="" class="lotto-group-packages-market-thumb">
                                                    <strong>@{{ market.name }}</strong>
                                                    <small class="text-muted">@{{ market.code }}</small>
                                                </div>
                                            </td>
                                            <template v-for="type in betTypes">
                                                <td v-if="displayMode !== 'discount'" :key="'pv-' + market.id + '-' + type.key" class="text-right">
                                                    @{{ renderValue(getSettingValue(activePackage, type.key, 'payout')) }}
                                                </td>
                                                <td v-if="displayMode !== 'payout'" :key="'dv-' + market.id + '-' + type.key" class="text-right">
                                                    @{{ renderValue(getSettingValue(activePackage, type.key, 'discount_percent')) }}
                                                </td>
                                            </template>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <b-modal ref="createPackageModal"
                     id="group-package-create-modal"
                     centered
                     size="md"
                     title="เพิ่มแพกเกจ"
                     :no-close-on-backdrop="true"
                     :hide-footer="true">
                <b-form @submit.prevent="submitCreatePackage">
                    <b-form-group label="ชื่อแพกเกจ" label-for="group-package-name">
                        <b-form-input id="group-package-name"
                                      v-model.trim="createForm.name"
                                      type="text"
                                      size="sm"
                                      required>
                        </b-form-input>
                    </b-form-group>

                    <b-form-group label="คำอธิบาย" label-for="group-package-description">
                        <b-form-textarea id="group-package-description"
                                         v-model.trim="createForm.description"
                                         rows="3"
                                         max-rows="5"
                                         size="sm">
                        </b-form-textarea>
                    </b-form-group>

                    <b-form-group label="รูปภาพแพกเกจ">
                        <div v-if="createForm.image" class="mb-2">
                            <img :src="createForm.image" alt="" class="lotto-group-packages-package-thumb">
                        </div>
                        <b-form-file v-model="createForm.image_file"
                                     accept="image/jpeg,image/png,image/gif,image/webp"
                                     size="sm"
                                     placeholder="อัปโหลดรูปภาพแพกเกจ">
                        </b-form-file>
                    </b-form-group>

                    <b-form-checkbox v-model="createForm.is_active" switch size="sm" class="mb-3">
                        เปิดใช้งานทันที
                    </b-form-checkbox>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                            <tr>
                                <th style="min-width: 140px;">ประเภทเดิมพัน</th>
                                <th style="min-width: 120px;">อัตราจ่าย</th>
                                <th style="min-width: 120px;">ส่วนลด(%)</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="type in betTypes" :key="'create-setting-' + type.key">
                                <td>@{{ type.label }}</td>
                                <td>
                                    <b-form-input v-model.number="createForm.settings[type.key].payout"
                                                  type="number"
                                                  step="0.01"
                                                  min="0.01"
                                                  size="sm"
                                                  required>
                                    </b-form-input>
                                </td>
                                <td>
                                    <b-form-input v-model.number="createForm.settings[type.key].discount_percent"
                                                  type="number"
                                                  step="0.01"
                                                  min="0"
                                                  max="100"
                                                  size="sm"
                                                  required>
                                    </b-form-input>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">บันทึก</button>
                </b-form>
            </b-modal>

            <b-modal ref="editPackageModal"
                     id="group-package-edit-modal"
                     centered
                     size="md"
                     title="แก้ไขแพกเกจ"
                     :no-close-on-backdrop="true"
                     :hide-footer="true">
                <b-form @submit.prevent="submitEditPackage">
                    <b-form-group label="ชื่อแพกเกจ" label-for="group-package-edit-name">
                        <b-form-input id="group-package-edit-name"
                                      v-model.trim="editForm.name"
                                      type="text"
                                      size="sm"
                                      required>
                        </b-form-input>
                    </b-form-group>

                    <b-form-group label="คำอธิบาย" label-for="group-package-edit-description">
                        <b-form-textarea id="group-package-edit-description"
                                         v-model.trim="editForm.description"
                                         rows="3"
                                         max-rows="5"
                                         size="sm">
                        </b-form-textarea>
                    </b-form-group>

                    <b-form-group label="รูปภาพแพกเกจ">
                        <div v-if="editForm.image" class="mb-2">
                            <img :src="editForm.image" alt="" class="lotto-group-packages-package-thumb">
                        </div>
                        <b-form-file v-model="editForm.image_file"
                                     accept="image/jpeg,image/png,image/gif,image/webp"
                                     size="sm"
                                     placeholder="อัปโหลดรูปภาพแพกเกจ">
                        </b-form-file>
                    </b-form-group>

                    <b-form-checkbox v-model="editForm.is_active" switch size="sm" class="mb-3">
                        เปิดใช้งาน
                    </b-form-checkbox>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                            <tr>
                                <th style="min-width: 140px;">ประเภทเดิมพัน</th>
                                <th style="min-width: 120px;">อัตราจ่าย</th>
                                <th style="min-width: 120px;">ส่วนลด(%)</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="type in betTypes" :key="'edit-setting-' + type.key">
                                <td>@{{ type.label }}</td>
                                <td>
                                    <b-form-input v-model.number="editForm.settings[type.key].payout"
                                                  type="number"
                                                  step="0.01"
                                                  min="0.01"
                                                  size="sm"
                                                  required>
                                    </b-form-input>
                                </td>
                                <td>
                                    <b-form-input v-model.number="editForm.settings[type.key].discount_percent"
                                                  type="number"
                                                  step="0.01"
                                                  min="0"
                                                  max="100"
                                                  size="sm"
                                                  required>
                                    </b-form-input>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">บันทึก</button>
                </b-form>
            </b-modal>
        </section>
    </script>

    <script type="module">
        Vue.component('lotto-group-packages-dashboard', {
            template: '#lotto-group-packages-dashboard-template',
            data() {
                const groups = @json($groupTabs ?? []);
                const activePackageIndexByGroup = {};
                const betTypes = @json($betTypes ?? []);
                (groups || []).forEach((group) => {
                    activePackageIndexByGroup[group.id] = 0;
                });

                const defaultSettings = {};
                (betTypes || []).forEach((type) => {
                    defaultSettings[type.key] = {
                        payout: null,
                        discount_percent: 0,
                    };
                });

                return {
                    groups,
                    betTypes,
                    activeGroupIndex: 0,
                    activePackageIndexByGroup,
                    displayMode: 'payout',
                    displayModeOptions: [
                        { value: 'payout', text: 'อัตราจ่าย' },
                        { value: 'discount', text: 'ส่วนลด(%)' },
                        { value: 'both', text: 'ทั้งคู่' },
                    ],
                    createForm: {
                        name: '',
                        description: '',
                        image: '',
                        image_file: null,
                        is_active: true,
                        settings: defaultSettings,
                    },
                    editForm: {
                        id: null,
                        group_id: null,
                        name: '',
                        description: '',
                        image: '',
                        image_file: null,
                        is_active: true,
                        settings: JSON.parse(JSON.stringify(defaultSettings)),
                    },
                };
            },
            computed: {
                activeGroup() {
                    return this.groups[this.activeGroupIndex] || null;
                },
                activePackage() {
                    if (!this.activeGroup || !Array.isArray(this.activeGroup.packages) || this.activeGroup.packages.length === 0) {
                        return null;
                    }

                    const index = this.getActivePackageIndex(this.activeGroup.id);
                    return this.activeGroup.packages[index] || this.activeGroup.packages[0] || null;
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
                renderValue(value) {
                    if (value === null || typeof value === 'undefined') {
                        return '-';
                    }

                    return this.formatNumber(value);
                },
                setActiveGroup(index) {
                    this.activeGroupIndex = index;
                    const group = this.groups[index];
                    if (group && typeof this.activePackageIndexByGroup[group.id] === 'undefined') {
                        this.$set(this.activePackageIndexByGroup, group.id, 0);
                    }
                },
                getActivePackageIndex(groupId) {
                    const index = Number(this.activePackageIndexByGroup[groupId] || 0);
                    return Number.isNaN(index) ? 0 : Math.max(0, index);
                },
                setActivePackage(groupId, index) {
                    this.$set(this.activePackageIndexByGroup, groupId, index);
                },
                getSettingValue(pkg, betType, field) {
                    if (!pkg || !pkg.settings || !pkg.settings[betType]) {
                        return null;
                    }

                    return pkg.settings[betType][field];
                },
                resetCreateForm() {
                    const settings = {};
                    this.betTypes.forEach((type) => {
                        settings[type.key] = {
                            payout: null,
                            discount_percent: 0,
                        };
                    });

                    this.createForm = {
                        name: '',
                        description: '',
                        image: '',
                        image_file: null,
                        is_active: true,
                        settings,
                    };
                },
                openCreatePackageModal() {
                    this.resetCreateForm();
                    this.$refs.createPackageModal.show();
                },
                resetEditForm() {
                    const settings = {};
                    this.betTypes.forEach((type) => {
                        settings[type.key] = {
                            payout: null,
                            discount_percent: 0,
                        };
                    });

                    this.editForm = {
                        id: null,
                        group_id: null,
                        name: '',
                        description: '',
                        image: '',
                        image_file: null,
                        is_active: true,
                        settings,
                    };
                },
                openEditPackageModal() {
                    if (!this.activePackage || !this.activeGroup) {
                        return;
                    }

                    this.resetEditForm();
                    const settings = {};
                    this.betTypes.forEach((type) => {
                        const row = this.activePackage.settings && this.activePackage.settings[type.key]
                            ? this.activePackage.settings[type.key]
                            : {};

                        settings[type.key] = {
                            payout: row.payout,
                            discount_percent: row.discount_percent ?? 0,
                        };
                    });

                    this.editForm = {
                        id: Number(this.activePackage.id),
                        group_id: Number(this.activeGroup.id),
                        name: this.activePackage.name || '',
                        description: this.activePackage.description || '',
                        image: this.activePackage.image || '',
                        image_file: null,
                        is_active: Boolean(this.activePackage.is_active),
                        settings,
                    };

                    this.$refs.editPackageModal.show();
                },
                normalizePackageRow(row) {
                    const settings = {};
                    this.betTypes.forEach((type) => {
                        const key = type.key;
                        const current = row && row.settings && row.settings[key] ? row.settings[key] : null;
                        const source = !current && row && row.bet_settings
                            ? (row.bet_settings || []).find((item) => item.bet_type === key)
                            : current;

                        settings[key] = {
                            payout: source && typeof source.payout !== 'undefined' ? Number(source.payout) : null,
                            discount_percent: source && typeof source.discount_percent !== 'undefined' ? Number(source.discount_percent) : null,
                            is_enabled: source ? Boolean(source.is_enabled) : false,
                        };
                    });

                    return {
                        id: Number(row.id || 0),
                        group_id: Number(row.group_id || 0),
                        name: row.name || '',
                        description: row.description || '',
                        image: row.image || '',
                        is_active: Boolean(row.is_active),
                        settings,
                    };
                },
                async reloadGroupPackages(groupId, preferredPackageId = null) {
                    const response = await axios.post("{{ route('admin.lotto.group_packages.list') }}", {
                        group_id: groupId,
                    });

                    const rows = Array.isArray(response?.data?.data) ? response.data.data : [];
                    const normalized = rows.map((row) => this.normalizePackageRow(row));
                    const groupIndex = this.groups.findIndex((group) => Number(group.id) === Number(groupId));
                    if (groupIndex < 0) {
                        return;
                    }

                    this.$set(this.groups[groupIndex], 'packages', normalized);

                    if (normalized.length === 0) {
                        this.$set(this.activePackageIndexByGroup, groupId, 0);
                        return;
                    }

                    if (preferredPackageId) {
                        const selectedIndex = normalized.findIndex((item) => Number(item.id) === Number(preferredPackageId));
                        this.$set(this.activePackageIndexByGroup, groupId, selectedIndex >= 0 ? selectedIndex : 0);
                        return;
                    }

                    const maxIndex = normalized.length - 1;
                    const currentIndex = this.getActivePackageIndex(groupId);
                    this.$set(this.activePackageIndexByGroup, groupId, Math.min(currentIndex, maxIndex));
                },
                extractErrorMessage(error) {
                    const data = error?.response?.data || {};
                    if (data.message) {
                        return data.message;
                    }

                    if (data.errors && typeof data.errors === 'object') {
                        const first = Object.values(data.errors)[0];
                        if (Array.isArray(first) && first.length > 0) {
                            return first[0];
                        }
                    }

                    return 'เกิดข้อผิดพลาดระหว่างดำเนินการ';
                },
                appendFormDataValue(formData, key, value) {
                    if (Array.isArray(value)) {
                        value.forEach((item, index) => {
                            this.appendFormDataValue(formData, `${key}[${index}]`, item);
                        });
                        return;
                    }

                    if (value && typeof value === 'object') {
                        Object.keys(value).forEach((childKey) => {
                            this.appendFormDataValue(formData, `${key}[${childKey}]`, value[childKey]);
                        });
                        return;
                    }

                    formData.append(key, value ?? '');
                },
                buildPackageFormData(id, payload, imageFile) {
                    const formData = new FormData();
                    if (id) {
                        formData.append('id', id);
                    }

                    Object.keys(payload).forEach((key) => {
                        this.appendFormDataValue(formData, `data[${key}]`, payload[key]);
                    });

                    if (imageFile) {
                        formData.append('image_file', imageFile);
                    }

                    return formData;
                },
                async submitCreatePackage() {
                    if (!this.activeGroup) {
                        return;
                    }

                    const betSettings = this.betTypes.map((type) => {
                        const row = this.createForm.settings[type.key] || {};
                        return {
                            bet_type: type.key,
                            payout: row.payout,
                            discount_percent: row.discount_percent,
                            is_enabled: true,
                        };
                    });

                    try {
                        const payload = {
                            group_id: this.activeGroup.id,
                            name: this.createForm.name,
                            description: this.createForm.description,
                            image: this.createForm.image,
                            is_active: this.createForm.is_active,
                            bet_settings: betSettings,
                        };
                        const formData = this.buildPackageFormData(null, payload, this.createForm.image_file);
                        const response = await axios.post("{{ route('admin.lotto.group_packages.create') }}", formData, {
                            headers: { 'Content-Type': 'multipart/form-data' },
                        });

                        const createdId = response?.data?.data?.id || null;
                        await this.reloadGroupPackages(this.activeGroup.id, createdId);

                        this.$refs.createPackageModal.hide();
                        await this.$bvModal.msgBoxOk(response?.data?.message || 'เพิ่มแพกเกจเรียบร้อยแล้ว', {
                            title: 'ผลการดำเนินการ',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'success',
                            centered: true,
                        });
                    } catch (error) {
                        await this.$bvModal.msgBoxOk(this.extractErrorMessage(error), {
                            title: 'ไม่สามารถบันทึกข้อมูลได้',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true,
                        });
                    }
                },
                async submitEditPackage() {
                    if (!this.activeGroup || !this.editForm.id) {
                        return;
                    }

                    const betSettings = this.betTypes.map((type) => {
                        const row = this.editForm.settings[type.key] || {};
                        return {
                            bet_type: type.key,
                            payout: row.payout,
                            discount_percent: row.discount_percent,
                            is_enabled: true,
                        };
                    });

                    try {
                        const payload = {
                            group_id: this.editForm.group_id,
                            name: this.editForm.name,
                            description: this.editForm.description,
                            image: this.editForm.image,
                            is_active: this.editForm.is_active,
                            bet_settings: betSettings,
                        };
                        const formData = this.buildPackageFormData(this.editForm.id, payload, this.editForm.image_file);
                        const response = await axios.post("{{ route('admin.lotto.group_packages.update') }}", formData, {
                            headers: { 'Content-Type': 'multipart/form-data' },
                        });

                        await this.reloadGroupPackages(this.activeGroup.id, this.editForm.id);
                        this.$refs.editPackageModal.hide();

                        await this.$bvModal.msgBoxOk(response?.data?.message || 'อัปเดตแพกเกจเรียบร้อยแล้ว', {
                            title: 'ผลการดำเนินการ',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'success',
                            centered: true,
                        });
                    } catch (error) {
                        await this.$bvModal.msgBoxOk(this.extractErrorMessage(error), {
                            title: 'ไม่สามารถบันทึกข้อมูลได้',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true,
                        });
                    }
                },
            },
        });
    </script>
@endpush
