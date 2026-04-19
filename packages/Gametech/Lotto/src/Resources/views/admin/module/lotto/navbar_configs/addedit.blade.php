<b-modal ref="addedit" id="addedit" centered size="xl" title="Lotto Navbar Config" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.prevent="addEditSubmit" v-if="show">
        <b-alert show variant="info" class="py-2 text-xs mb-2">
            <strong>Draft Mode:</strong> การแก้ไขหน้านี้กระทบเฉพาะ draft และจะขึ้น live หลัง Publish เท่านั้น
        </b-alert>

        <div class="row">
            <div class="col-md-4">
                <b-form-group label="Code:" label-for="code" description="ถ้าไม่ส่ง code ฝั่ง API จะใช้ mobile_bottom_nav">
                    <b-form-input id="code" v-model="formaddedit.code" type="text" size="sm" autocomplete="off" required></b-form-input>
                </b-form-group>
            </div>
            <div class="col-md-5">
                <b-form-group label="Name:" label-for="name">
                    <b-form-input id="name" v-model="formaddedit.name" type="text" size="sm" autocomplete="off"></b-form-input>
                </b-form-group>
            </div>
            <div class="col-md-3">
                <b-form-group label="Draft Enabled:" label-for="is_active">
                    <b-form-checkbox id="is_active" v-model="formaddedit.is_active" switch>
                        เปิดใช้งาน draft นี้
                    </b-form-checkbox>
                </b-form-group>
            </div>
        </div>

        <div class="mb-2 text-xs">
            <span class="badge badge-success mr-1">Live Version: @{{ publishedVersionText }}</span>
            <span class="badge badge-secondary">Last Published: @{{ publishedAtText }}</span>
        </div>

        <div v-if="diffSummary" class="alert alert-light border py-2 text-xs mb-2">
            <strong>Draft vs Live:</strong>
            <span class="ml-2 badge badge-info">เพิ่ม @{{ diffSummary.added }}</span>
            <span class="ml-1 badge badge-warning">แก้ไข @{{ diffSummary.changed }}</span>
            <span class="ml-1 badge badge-danger">ลบ @{{ diffSummary.removed }}</span>
        </div>

        <div v-if="validationErrors.length > 0" class="alert alert-danger py-2 text-xs mb-2">
            <div><strong>ยังบันทึกไม่ได้:</strong></div>
            <div v-for="(err, idx) in validationErrors.slice(0, 5)" :key="'err_'+idx">- @{{ err }}</div>
            <div v-if="validationErrors.length > 5">- และอีก @{{ validationErrors.length - 5 }} รายการ</div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">รายการเมนู Navbar (Draft)</h6>
            <div class="d-flex align-items-center">
                <b-form-select v-model="newLanguage" size="sm" class="mr-2" :options="languageOptionsForAdd"></b-form-select>
                <button type="button" class="btn btn-outline-primary btn-xs mr-2" @click="addLanguageColumn()" :disabled="!newLanguage">
                    <i class="fa fa-plus"></i> เพิ่มภาษา
                </button>
                <button type="button" class="btn btn-success btn-xs" @click="addItemRow()">
                    <i class="fa fa-plus"></i> เพิ่มรายการ
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered text-xs">
                <thead class="thead-light">
                <tr>
                    <th style="min-width: 120px;">Key</th>
                    <th style="min-width: 120px;">Type</th>
                    <th style="min-width: 120px;">Icon Type</th>
                    <th style="min-width: 150px;">Icon</th>
                    <th v-for="lang in formaddedit.languages" :key="'lang_head_'+lang" style="min-width: 160px;">
                        Label (@{{ lang }})
                        <button v-if="lang !== 'th'" type="button" class="btn btn-link btn-xs text-danger p-0 ml-1" @click="removeLanguageColumn(lang)">
                            <i class="fa fa-times"></i>
                        </button>
                    </th>
                    <th style="min-width: 120px;">Action Type</th>
                    <th style="min-width: 140px;">Action Value</th>
                    <th style="min-width: 100px;">Sort</th>
                    <th style="min-width: 95px;">Active (Draft)</th>
                    <th style="min-width: 75px;">จัดการ</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(item, index) in formaddedit.items" :key="item.row_key">
                    <td><b-form-input v-model="item.key" size="sm" required></b-form-input></td>
                    <td><b-form-select v-model="item.item_type" size="sm" :options="getItemTypeOptions(index)"></b-form-select></td>
                    <td><b-form-select v-model="item.icon_type" size="sm" :options="iconTypeOptions"></b-form-select></td>
                    <td>
                        <b-form-select v-if="item.icon_type === 'preset'" v-model="item.icon" size="sm" :options="presetIconOptions"></b-form-select>
                        <b-form-input v-else v-model="item.icon" size="sm" :placeholder="item.icon_type === 'emoji' ? 'เช่น 🏠' : 'URL/Path'" ></b-form-input>
                    </td>
                    <td v-for="lang in formaddedit.languages" :key="'label_'+index+'_'+lang">
                        <b-form-input v-model="item.labels[lang]" size="sm" :required="lang === 'th'"></b-form-input>
                    </td>
                    <td><b-form-input v-model="item.action_type" size="sm" required></b-form-input></td>
                    <td><b-form-input v-model="item.action_value" size="sm"></b-form-input></td>
                    <td class="text-center">
                        <div class="d-flex align-items-center justify-content-center">
                            <button type="button" class="btn btn-light btn-xs mr-1" @click="moveItemUp(index)" :disabled="index === 0"><i class="fa fa-chevron-up"></i></button>
                            <span class="badge badge-secondary">@{{ item.sort_order }}</span>
                            <button type="button" class="btn btn-light btn-xs ml-1" @click="moveItemDown(index)" :disabled="index === formaddedit.items.length - 1"><i class="fa fa-chevron-down"></i></button>
                        </div>
                    </td>
                    <td class="text-center"><b-form-checkbox v-model="item.is_active" switch></b-form-checkbox></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-xs" @click="removeItemRow(index)" :disabled="formaddedit.items.length <= 1"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="row mt-3">
            <div class="col-md-6 mb-2 mb-md-0">
                <div class="card card-body p-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-xs">Draft Preview</strong>
                        <b-form-select v-model="previewLanguage" size="sm" class="w-auto" :options="formaddedit.languages"></b-form-select>
                    </div>
                    <div class="border rounded p-2" style="background:#f8f9fa;">
                        <div class="d-flex justify-content-between align-items-end">
                            <div v-for="item in draftPreviewItems" :key="'draft_preview_'+item.key" class="text-center px-1">
                                <div class="font-weight-bold" :class="item.item_type === 'center_cta' ? 'text-primary' : ''">@{{ previewIcon(item) }}</div>
                                <div style="font-size:11px;">@{{ previewLabel(item, previewLanguage) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-body p-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-xs">Live Preview</strong>
                        <span class="badge badge-success">v@{{ publishedVersionText }}</span>
                    </div>
                    <div class="border rounded p-2" style="background:#f8f9fa;">
                        <div class="d-flex justify-content-between align-items-end" v-if="publishedPreviewItems.length > 0">
                            <div v-for="item in publishedPreviewItems" :key="'published_preview_'+item.key" class="text-center px-1">
                                <div class="font-weight-bold" :class="item.item_type === 'center_cta' ? 'text-primary' : ''">@{{ previewIcon(item) }}</div>
                                <div style="font-size:11px;">@{{ previewLabel(item, previewLanguage) }}</div>
                            </div>
                        </div>
                        <div v-else class="text-muted text-xs">ยังไม่มี live published</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <b-button type="submit" variant="primary" size="sm" :disabled="validationErrors.length > 0">บันทึก Draft</b-button>
        </div>
    </b-form>
</b-modal>

@push('scripts')
    <script type="module">
        const languageCandidates = {!! json_encode(array_values(array_unique(array_merge(['th', 'en'], array_keys((array) config('languages.available', [])))))) !!};

        const itemTypeOptions = [
            { value: 'normal', text: 'normal' },
            { value: 'center_cta', text: 'center_cta' },
        ];

        const iconTypeOptions = [
            { value: 'preset', text: 'preset' },
            { value: 'emoji', text: 'emoji' },
            { value: 'image', text: 'image' },
        ];

        const presetIconOptions = [
            { value: 'home', text: 'home' },
            { value: 'ticket', text: 'ticket' },
            { value: 'wallet', text: 'wallet' },
            { value: 'user', text: 'user' },
            { value: 'menu', text: 'menu' },
            { value: 'star', text: 'star' },
            { value: 'gift', text: 'gift' },
            { value: 'settings', text: 'settings' },
        ];

        const createItem = (sortOrder = 1, languages = ['th', 'en']) => {
            const labels = {};
            languages.forEach((lang) => { labels[lang] = ''; });

            return {
                row_key: `row_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`,
                id: null,
                key: '',
                item_type: 'normal',
                icon_type: 'preset',
                icon: 'home',
                labels,
                action_type: 'route',
                action_value: '',
                sort_order: sortOrder,
                is_active: true,
            };
        };

        const createDefaultForm = () => ({
            code: 'mobile_bottom_nav',
            name: 'Mobile Bottom Nav',
            is_active: true,
            languages: ['th', 'en'],
            items: [createItem(1, ['th', 'en'])],
            published_snapshot: null,
        });

        const hasEmoji = (value) => /\p{Extended_Pictographic}/u.test(String(value || ''));

        const resequenceItems = (items) => {
            items.forEach((item, index) => {
                item.sort_order = index + 1;
            });
        };

        const extractLanguagesFromItems = (items = []) => {
            const found = new Set(['th', 'en']);
            items.forEach((item) => {
                const labels = item && item.label_i18n ? item.label_i18n : {};
                Object.keys(labels).forEach((lang) => {
                    const key = String(lang || '').trim().toLowerCase();
                    if (key !== '') {
                        found.add(key);
                    }
                });
            });

            return Array.from(found);
        };

        const normalizeItemForForm = (item, index, languages) => {
            const labels = {};
            const rowLabels = item && item.label_i18n ? item.label_i18n : {};
            languages.forEach((lang) => {
                labels[lang] = rowLabels[lang] ? rowLabels[lang] : '';
            });

            return {
                row_key: `row_${Date.now()}_${index}_${Math.random().toString(36).slice(2, 8)}`,
                id: item && item.id ? Number(item.id) : null,
                key: item && item.key ? item.key : '',
                item_type: item && item.item_type ? item.item_type : 'normal',
                icon_type: item && item.icon_type ? item.icon_type : 'preset',
                icon: item && item.icon ? item.icon : 'home',
                labels,
                action_type: item && item.action_type ? item.action_type : 'route',
                action_value: item && item.action_value ? item.action_value : '',
                sort_order: item && item.sort_order ? Number(item.sort_order) : (index + 1),
                is_active: item && typeof item.is_active !== 'undefined' ? Boolean(item.is_active) : true,
            };
        };

        const normalizePreviewItem = (item) => ({
            key: String(item.key || ''),
            item_type: String(item.item_type || 'normal'),
            icon_type: String(item.icon_type || 'preset'),
            icon: String(item.icon || ''),
            label_i18n: item.label_i18n || {},
            sort_order: Number(item.sort_order || 1),
            is_active: Boolean(item.is_active),
        });

        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: true,
                    code: null,
                    formmethod: 'add',
                    formaddedit: createDefaultForm(),
                    itemTypeOptions,
                    iconTypeOptions,
                    presetIconOptions,
                    newLanguage: '',
                    previewLanguage: 'th',
                };
            },
            computed: {
                validationErrors() {
                    return this.collectValidationErrors();
                },
                languageOptionsForAdd() {
                    const used = new Set(this.formaddedit.languages || []);
                    return [{ value: '', text: 'เลือกภาษา' }].concat(languageCandidates
                        .filter((lang) => !used.has(lang))
                        .map((lang) => ({ value: lang, text: lang })));
                },
                publishedVersionText() {
                    const snapshot = this.formaddedit.published_snapshot;
                    if (!snapshot || !snapshot.published_version) {
                        return '-';
                    }

                    return String(snapshot.published_version);
                },
                publishedAtText() {
                    const snapshot = this.formaddedit.published_snapshot;
                    return snapshot && snapshot.published_at ? snapshot.published_at : '-';
                },
                draftPreviewItems() {
                    return (this.formaddedit.items || [])
                        .filter((item) => item.is_active)
                        .sort((a, b) => a.sort_order - b.sort_order)
                        .map((item) => ({
                            key: item.key,
                            item_type: item.item_type,
                            icon_type: item.icon_type,
                            icon: item.icon,
                            label_i18n: item.labels,
                            sort_order: item.sort_order,
                            is_active: item.is_active,
                        }));
                },
                publishedPreviewItems() {
                    const snapshot = this.formaddedit.published_snapshot;
                    if (!snapshot || !Array.isArray(snapshot.items)) {
                        return [];
                    }

                    return snapshot.items
                        .filter((item) => item.is_active)
                        .sort((a, b) => a.sort_order - b.sort_order)
                        .map((item) => normalizePreviewItem(item));
                },
                diffSummary() {
                    const snapshot = this.formaddedit.published_snapshot;
                    if (!snapshot || !Array.isArray(snapshot.items)) {
                        return null;
                    }

                    const draftMap = new Map((this.formaddedit.items || []).map((item) => [item.key, item]));
                    const liveMap = new Map((snapshot.items || []).map((item) => [item.key, normalizePreviewItem(item)]));

                    let added = 0;
                    let changed = 0;
                    let removed = 0;

                    draftMap.forEach((draft, key) => {
                        if (!liveMap.has(key)) {
                            added += 1;
                            return;
                        }

                        const live = liveMap.get(key);
                        const draftSig = JSON.stringify({
                            item_type: draft.item_type,
                            icon_type: draft.icon_type,
                            icon: draft.icon,
                            labels: draft.labels,
                            action_type: draft.action_type,
                            action_value: draft.action_value,
                            sort_order: draft.sort_order,
                            is_active: draft.is_active,
                        });
                        const liveSig = JSON.stringify({
                            item_type: live.item_type,
                            icon_type: live.icon_type,
                            icon: live.icon,
                            labels: live.label_i18n,
                            action_type: live.action_type,
                            action_value: live.action_value,
                            sort_order: live.sort_order,
                            is_active: live.is_active,
                        });

                        if (draftSig !== liveSig) {
                            changed += 1;
                        }
                    });

                    liveMap.forEach((_live, key) => {
                        if (!draftMap.has(key)) {
                            removed += 1;
                        }
                    });

                    return { added, changed, removed };
                },
            },
            methods: {
                previewIcon(item) {
                    if (item.icon_type === 'emoji') {
                        return item.icon || '🙂';
                    }

                    return (item.icon || 'icon').slice(0, 12);
                },
                previewLabel(item, lang) {
                    const labels = item.label_i18n || {};
                    return labels[lang] || labels.th || labels.en || item.key;
                },
                getItemTypeOptions(index) {
                    const hasOtherActiveCenter = this.formaddedit.items.some((row, rowIndex) => {
                        return rowIndex !== index && row.is_active && row.item_type === 'center_cta';
                    });

                    return this.itemTypeOptions.map((opt) => {
                        if (opt.value === 'center_cta') {
                            return { ...opt, disabled: hasOtherActiveCenter };
                        }

                        return { ...opt };
                    });
                },
                collectValidationErrors() {
                    const errors = [];
                    const items = Array.isArray(this.formaddedit.items) ? this.formaddedit.items : [];

                    if (items.length < 1) {
                        errors.push('ต้องมีรายการเมนูอย่างน้อย 1 รายการ');
                        return errors;
                    }

                    const keyCount = {};
                    let activeCenterCtaCount = 0;
                    const presetIcons = this.presetIconOptions.map((opt) => opt.value);

                    items.forEach((item, index) => {
                        const row = index + 1;
                        const key = String(item.key || '').trim().toLowerCase();

                        if (key === '') {
                            errors.push(`แถว ${row}: key ห้ามว่าง`);
                        } else {
                            keyCount[key] = (keyCount[key] || 0) + 1;
                        }

                        const labelTh = item.labels && item.labels.th ? String(item.labels.th).trim() : '';
                        if (labelTh === '') {
                            errors.push(`แถว ${row}: ต้องมี Label (th)`);
                        }

                        if (String(item.action_type || '').trim() === '') {
                            errors.push(`แถว ${row}: action_type ห้ามว่าง`);
                        }

                        const iconValue = String(item.icon || '').trim();
                        if (item.icon_type === 'preset' && !presetIcons.includes(iconValue)) {
                            errors.push(`แถว ${row}: preset icon ไม่ถูกต้อง`);
                        }

                        if (item.icon_type === 'emoji' && !hasEmoji(iconValue)) {
                            errors.push(`แถว ${row}: icon_type=emoji ต้องเป็น emoji จริง`);
                        }

                        if (item.is_active && item.item_type === 'center_cta') {
                            activeCenterCtaCount += 1;
                        }
                    });

                    Object.keys(keyCount).forEach((key) => {
                        if (keyCount[key] > 1) {
                            errors.push(`key ซ้ำ: ${key}`);
                        }
                    });

                    if (activeCenterCtaCount > 1) {
                        errors.push('active item ประเภท center_cta มีได้สูงสุด 1 รายการ');
                    }

                    return errors;
                },
                addLanguageColumn() {
                    const lang = String(this.newLanguage || '').trim().toLowerCase();
                    if (lang === '' || this.formaddedit.languages.includes(lang)) {
                        return;
                    }

                    this.formaddedit.languages.push(lang);
                    this.formaddedit.items.forEach((item) => {
                        if (!item.labels) {
                            item.labels = {};
                        }
                        if (typeof item.labels[lang] === 'undefined') {
                            item.labels[lang] = '';
                        }
                    });
                    this.newLanguage = '';
                },
                removeLanguageColumn(lang) {
                    if (lang === 'th') {
                        return;
                    }

                    this.formaddedit.languages = this.formaddedit.languages.filter((code) => code !== lang);
                    this.formaddedit.items.forEach((item) => {
                        if (item.labels && typeof item.labels[lang] !== 'undefined') {
                            delete item.labels[lang];
                        }
                    });
                },
                editModal(id) {
                    this.code = id;
                    this.formmethod = 'edit';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.loadData();
                        this.$refs.addedit.show();
                    });
                },
                addModal() {
                    this.code = null;
                    this.formmethod = 'add';
                    this.formaddedit = createDefaultForm();
                    this.previewLanguage = 'th';
                    this.newLanguage = '';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();
                    });
                },
                addItemRow() {
                    const nextSort = this.formaddedit.items.length + 1;
                    this.formaddedit.items.push(createItem(nextSort, this.formaddedit.languages));
                    resequenceItems(this.formaddedit.items);
                },
                moveItemUp(index) {
                    if (index <= 0) {
                        return;
                    }

                    const items = this.formaddedit.items;
                    [items[index - 1], items[index]] = [items[index], items[index - 1]];
                    resequenceItems(items);
                },
                moveItemDown(index) {
                    if (index >= this.formaddedit.items.length - 1) {
                        return;
                    }

                    const items = this.formaddedit.items;
                    [items[index + 1], items[index]] = [items[index], items[index + 1]];
                    resequenceItems(items);
                },
                removeItemRow(index) {
                    if (this.formaddedit.items.length <= 1) {
                        return;
                    }

                    this.formaddedit.items.splice(index, 1);
                    resequenceItems(this.formaddedit.items);
                },
                async loadData() {
                    const response = await axios.post("{{ route('admin.lotto.navbar_configs.loaddata') }}", { id: this.code });
                    const d = response.data.data;
                    const publishedSnapshot = d.published_snapshot || null;

                    const languages = extractLanguagesFromItems([...(Array.isArray(d.items) ? d.items : []), ...((publishedSnapshot && Array.isArray(publishedSnapshot.items)) ? publishedSnapshot.items : [])]);
                    const items = Array.isArray(d.items) ? d.items.map((item, index) => normalizeItemForForm(item, index, languages)) : [];
                    resequenceItems(items);

                    this.formaddedit = {
                        code: d.code,
                        name: d.name || '',
                        is_active: Boolean(d.is_active),
                        languages,
                        items: items.length > 0 ? items : [createItem(1, languages)],
                        published_snapshot: publishedSnapshot,
                    };

                    if (!this.formaddedit.languages.includes(this.previewLanguage)) {
                        this.previewLanguage = this.formaddedit.languages.includes('th') ? 'th' : this.formaddedit.languages[0];
                    }
                },
                addEditSubmit() {
                    const errors = this.collectValidationErrors();
                    if (errors.length > 0) {
                        this.$bvModal.msgBoxOk(errors[0], {
                            title: 'Validation Error',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true,
                        });
                        return;
                    }

                    const items = this.formaddedit.items.map((item) => {
                        const labels = {};
                        this.formaddedit.languages.forEach((lang) => {
                            const value = item.labels && typeof item.labels[lang] !== 'undefined'
                                ? String(item.labels[lang]).trim()
                                : '';
                            if (value !== '') {
                                labels[lang] = value;
                            }
                        });

                        const payload = {
                            key: String(item.key || '').trim(),
                            item_type: String(item.item_type || 'normal'),
                            icon_type: String(item.icon_type || 'preset'),
                            icon: String(item.icon || ''),
                            label_i18n: labels,
                            action_type: String(item.action_type || '').trim(),
                            action_value: String(item.action_value || ''),
                            sort_order: Number(item.sort_order || 1),
                            is_active: Boolean(item.is_active),
                        };

                        if (item.id) {
                            payload.id = Number(item.id);
                        }

                        return payload;
                    });

                    const payload = {
                        code: this.formaddedit.code,
                        name: this.formaddedit.name,
                        is_active: this.formaddedit.is_active,
                        items,
                    };

                    const url = this.formmethod === 'add'
                        ? "{{ route('admin.lotto.navbar_configs.create') }}"
                        : "{{ route('admin.lotto.navbar_configs.update') }}";

                    this.$http.post(url, { id: this.code, data: payload })
                        .then(response => {
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                centered: true,
                            });
                            this.$refs.addedit.hide();
                            window.LaravelDataTables['dataTableBuilder'].draw(false);
                        })
                        .catch(error => {
                            const message = (error && error.response && error.response.data && error.response.data.message)
                                ? error.response.data.message
                                : 'บันทึกข้อมูลไม่สำเร็จ';
                            this.$bvModal.msgBoxOk(message, {
                                title: 'ผิดพลาด',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'danger',
                                centered: true,
                            });
                        });
                },
                publishNavbar(id) {
                    this.$bvModal.msgBoxConfirm('ต้องการ publish draft นี้หรือไม่?', {
                        title: 'ยืนยันการเผยแพร่',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'success',
                        okTitle: 'Publish',
                        cancelTitle: 'ยกเลิก',
                        centered: true,
                    }).then((confirmed) => {
                        if (!confirmed) return;

                        axios.post("{{ route('admin.lotto.navbar_configs.publish') }}", { id })
                            .then((response) => {
                                this.$bvModal.msgBoxOk(response.data.message, {
                                    title: 'ผลการดำเนินการ',
                                    size: 'sm',
                                    buttonSize: 'sm',
                                    okVariant: 'success',
                                    centered: true,
                                });
                                window.LaravelDataTables['dataTableBuilder'].draw(false);
                            })
                            .catch((error) => {
                                const message = (error && error.response && error.response.data && error.response.data.message)
                                    ? error.response.data.message
                                    : 'Publish ไม่สำเร็จ';
                                this.$bvModal.msgBoxOk(message, {
                                    title: 'ผิดพลาด',
                                    size: 'sm',
                                    buttonSize: 'sm',
                                    okVariant: 'danger',
                                    centered: true,
                                });
                            });
                    });
                },
                unpublishNavbar(id) {
                    this.$bvModal.msgBoxConfirm('ต้องการยกเลิกการเผยแพร่หรือไม่?', {
                        title: 'ยืนยันการยกเลิกเผยแพร่',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'warning',
                        okTitle: 'Unpublish',
                        cancelTitle: 'ยกเลิก',
                        centered: true,
                    }).then((confirmed) => {
                        if (!confirmed) return;

                        axios.post("{{ route('admin.lotto.navbar_configs.unpublish') }}", { id })
                            .then((response) => {
                                this.$bvModal.msgBoxOk(response.data.message, {
                                    title: 'ผลการดำเนินการ',
                                    size: 'sm',
                                    buttonSize: 'sm',
                                    okVariant: 'success',
                                    centered: true,
                                });
                                window.LaravelDataTables['dataTableBuilder'].draw(false);
                            })
                            .catch((error) => {
                                const message = (error && error.response && error.response.data && error.response.data.message)
                                    ? error.response.data.message
                                    : 'Unpublish ไม่สำเร็จ';
                                this.$bvModal.msgBoxOk(message, {
                                    title: 'ผิดพลาด',
                                    size: 'sm',
                                    buttonSize: 'sm',
                                    okVariant: 'danger',
                                    centered: true,
                                });
                            });
                    });
                },
                deleteNavbar(id) {
                    this.$bvModal.msgBoxConfirm('ต้องการลบ draft นี้หรือไม่?', {
                        title: 'ยืนยันการลบ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'ลบ',
                        cancelTitle: 'ยกเลิก',
                        centered: true,
                    }).then((confirmed) => {
                        if (!confirmed) return;

                        axios.post("{{ route('admin.lotto.navbar_configs.delete') }}", { id })
                            .then((response) => {
                                this.$bvModal.msgBoxOk(response.data.message, {
                                    title: 'ผลการดำเนินการ',
                                    size: 'sm',
                                    buttonSize: 'sm',
                                    okVariant: 'success',
                                    centered: true,
                                });
                                window.LaravelDataTables['dataTableBuilder'].draw(false);
                            })
                            .catch((error) => {
                                const message = (error && error.response && error.response.data && error.response.data.message)
                                    ? error.response.data.message
                                    : 'ลบข้อมูลไม่สำเร็จ';
                                this.$bvModal.msgBoxOk(message, {
                                    title: 'ผิดพลาด',
                                    size: 'sm',
                                    buttonSize: 'sm',
                                    okVariant: 'danger',
                                    centered: true,
                                });
                            });
                    });
                }
            },
        });

        window.addNavbarModal = function () { window.app.addModal(); };
        window.editNavbarModal = function (id) { window.app.editModal(id); };
        window.publishNavbar = function (id) { window.app.publishNavbar(id); };
        window.unpublishNavbar = function (id) { window.app.unpublishNavbar(id); };
        window.deleteNavbar = function (id) { window.app.deleteNavbar(id); };
    </script>
@endpush
