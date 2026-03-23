<b-modal ref="addedit" id="addedit" centered size="md" title="กลุ่มหวย" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true">
    <b-form @submit.prevent="addEditSubmit" v-if="show">
        <b-form-group label="ชื่อกลุ่ม:" label-for="name" description="ระบุชื่อกลุ่มหวย เช่น หวยไทย">
            <b-form-input
                id="name"
                v-model="formaddedit.name"
                type="text"
                size="sm"
                placeholder="ชื่อกลุ่ม"
                autocomplete="off"
                required
            ></b-form-input>
        </b-form-group>
        <b-row>
            <b-col cols="12" md="6">
                <b-form-group label="ชื่ออังกฤษ:" label-for="name_en">
                    <b-form-input id="name_en" v-model="formaddedit.name_en" type="text" size="sm" autocomplete="off"></b-form-input>
                </b-form-group>
            </b-col>
            <b-col cols="12" md="6">
                <b-form-group label="ชื่อเขมร:" label-for="name_kh">
                    <b-form-input id="name_kh" v-model="formaddedit.name_kh" type="text" size="sm" autocomplete="off"></b-form-input>
                </b-form-group>
            </b-col>
        </b-row>
        <b-row>
            <b-col cols="12" md="6">
                <b-form-group label="ชื่อลาว:" label-for="name_laos">
                    <b-form-input id="name_laos" v-model="formaddedit.name_laos" type="text" size="sm" autocomplete="off"></b-form-input>
                </b-form-group>
            </b-col>
            <b-col cols="12" md="6">
                <b-form-group label="Code:" label-for="code" description="ตัวอักษรภาษาอังกฤษ ตัวเลข หรือ underscore">
                    <b-form-input
                        id="code"
                        v-model="formaddedit.code"
                        type="text"
                        size="sm"
                        placeholder="เช่น thailand, stock"
                        autocomplete="off"
                        required
                    ></b-form-input>
                </b-form-group>
            </b-col>
        </b-row>
        <b-form-group label="Description รายภาษา">
            <ul class="nav nav-tabs mb-2">
                <li class="nav-item" v-for="langItem in option.languages" :key="'desc-tab-' + langItem.value">
                    <a href="javascript:void(0)"
                       class="nav-link py-1 px-2"
                       :class="{ active: activeDescriptionLang === langItem.value }"
                       @click.prevent="selectDescriptionLanguage(langItem.value)">
                        @{{ langItem.text }}
                        <span class="badge ml-1" :class="hasDescriptionLanguage(langItem.value) ? 'badge-success' : 'badge-secondary'">
                            @{{ hasDescriptionLanguage(langItem.value) ? 'มี' : 'ยังไม่มี' }}
                        </span>
                    </a>
                </li>
            </ul>
            <small class="text-muted d-block mt-1">เก็บเป็น JSON เช่น {"th":"...","la":"..."}</small>
        </b-form-group>
        <b-form-group v-if="activeDescriptionLang" label="คำอธิบายสั้นๆ">
            <div class="border rounded p-3 text-center">
                <div class="mb-2">
                    <strong>@{{ languageName(activeDescriptionLang) }} (@{{ activeDescriptionLang }})</strong>
                </div>
                <b-form-textarea
                    v-model="activeDescriptionValue"
                    rows="2"
                    max-rows="3"
                    size="sm"
                    placeholder="คำอธิบายสั้นๆ"
                    class="text-left"
                ></b-form-textarea>
                <div class="mt-2">
                    <b-button v-if="!hasDescriptionLanguage(activeDescriptionLang)"
                              type="button"
                              variant="outline-primary"
                              size="sm"
                              @click="addDescriptionLanguage(activeDescriptionLang)">
                        เพิ่มภาษานี้
                    </b-button>
                    <b-button v-else
                              type="button"
                              variant="outline-danger"
                              size="sm"
                              @click="removeDescriptionLanguage(activeDescriptionLang)">
                        ลบภาษานี้
                    </b-button>
                </div>
            </div>
        </b-form-group>
        <b-row>
            <b-col cols="12" md="6">
                <b-form-group label="Logo URL:" label-for="logo">
                    <b-form-input id="logo" v-model="formaddedit.logo" type="text" size="sm" autocomplete="off" placeholder="/storage/... หรือ URL"></b-form-input>
                    <b-form-file class="mt-2" size="sm" v-model="formaddedit.logo_file" accept="image/jpeg,image/png,image/gif,image/webp" placeholder="อัปโหลด Logo"></b-form-file>
                    <a v-if="formaddedit.logo" :href="formaddedit.logo" target="_blank" class="d-inline-block mt-1">ดูรูปปัจจุบัน</a>
                </b-form-group>
            </b-col>
            <b-col cols="12" md="6">
                <b-form-group label="Icon URL:" label-for="icon">
                    <b-form-input id="icon" v-model="formaddedit.icon" type="text" size="sm" autocomplete="off" placeholder="/storage/... หรือ URL"></b-form-input>
                    <b-form-file class="mt-2" size="sm" v-model="formaddedit.icon_file" accept="image/jpeg,image/png,image/gif,image/webp" placeholder="อัปโหลด Icon"></b-form-file>
                    <a v-if="formaddedit.icon" :href="formaddedit.icon" target="_blank" class="d-inline-block mt-1">ดูรูปปัจจุบัน</a>
                </b-form-group>
            </b-col>
        </b-row>
        <b-row>
            <b-col cols="12" md="6">
                <b-form-group label="Sort:" label-for="sort">
                    <b-form-input
                        id="sort"
                        v-model="formaddedit.sort"
                        type="number"
                        min="0"
                        size="sm"
                    ></b-form-input>
                </b-form-group>
            </b-col>
            <b-col cols="12" md="6" class="d-flex align-items-end">
                <b-form-group class="mb-2">
                    <b-form-checkbox v-model="formaddedit.is_enabled" :value="1" :unchecked-value="0">
                        เปิดใช้งาน
                    </b-form-checkbox>
                </b-form-group>
            </b-col>
        </b-row>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <b-button type="submit" variant="primary" size="sm">บันทึก</b-button>
            </div>
        </div>
    </b-form>
</b-modal>

@push('scripts')
    <script type="module">
        window.app = new Vue({
            el: '#app',
            data() {
                return {
                    show: true,
                    formmethod: 'add',
                    formaddedit: {
                        name: '',
                        description_translations: {},
                        name_en: '',
                        name_kh: '',
                        name_laos: '',
                        logo: '',
                        icon: '',
                        logo_file: null,
                        icon_file: null,
                        code: '',
                        sort: 0,
                        is_enabled: 1,
                    },
                    option: {
                        languages: @json($languageOptions ?? []),
                    },
                    activeDescriptionLang: '',
                };
            },
            computed: {
                activeDescriptionValue: {
                    get() {
                        if (!this.activeDescriptionLang) {
                            return '';
                        }

                        return this.formaddedit.description_translations[this.activeDescriptionLang] || '';
                    },
                    set(value) {
                        if (!this.activeDescriptionLang) {
                            return;
                        }

                        this.$set(this.formaddedit.description_translations, this.activeDescriptionLang, value || '');
                    },
                },
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
                this.activeDescriptionLang = this.option.languages.length > 0 ? this.option.languages[0].value : '';
            },
            methods: {
                selectDescriptionLanguage(lang) {
                    this.activeDescriptionLang = lang;
                },
                languageName(lang) {
                    const found = (this.option.languages || []).find((item) => item.value === lang);
                    return found ? found.text.replace(' (' + lang + ')', '') : lang;
                },
                hasDescriptionLanguage(lang) {
                    return Object.prototype.hasOwnProperty.call(this.formaddedit.description_translations || {}, lang);
                },
                addDescriptionLanguage(lang) {
                    if (!lang) {
                        return;
                    }

                    if (!this.hasDescriptionLanguage(lang)) {
                        this.$set(this.formaddedit.description_translations, lang, '');
                    }
                },
                removeDescriptionLanguage(lang) {
                    if (!lang) {
                        return;
                    }

                    this.$delete(this.formaddedit.description_translations, lang);
                },
                parseDescriptionPayload(raw) {
                    if (!raw || typeof raw !== 'string') {
                        return {};
                    }

                    try {
                        const decoded = JSON.parse(raw);
                        if (!decoded || typeof decoded !== 'object' || Array.isArray(decoded)) {
                            return {};
                        }

                        return Object.keys(decoded).reduce((carry, lang) => {
                            const value = decoded[lang];
                            if (typeof value !== 'string') {
                                return carry;
                            }

                            const text = value.trim();
                            if (text === '') {
                                return carry;
                            }

                            carry[lang] = text;
                            return carry;
                        }, {});
                    } catch (e) {
                        const fallbackLang = (this.option.languages && this.option.languages.length > 0)
                            ? this.option.languages[0].value
                            : 'th';
                        const fallbackText = raw.trim();
                        if (fallbackText === '') {
                            return {};
                        }

                        return {
                            [fallbackLang]: fallbackText,
                        };
                    }
                },
                serializeDescriptionPayload() {
                    const cleaned = Object.keys(this.formaddedit.description_translations || {}).reduce((carry, lang) => {
                        const value = this.formaddedit.description_translations[lang];
                        if (typeof value !== 'string') {
                            return carry;
                        }

                        const text = value.trim();
                        if (text === '') {
                            return carry;
                        }

                        carry[lang] = text;
                        return carry;
                    }, {});

                    return Object.keys(cleaned).length > 0 ? JSON.stringify(cleaned) : '';
                },
                editdata(id, status, method) {
                    this.$bvModal.msgBoxConfirm('ต้องการเปลี่ยนสถานะหรือไม่?', {
                        title: 'ยืนยัน', size: 'sm', buttonSize: 'sm',
                        okVariant: 'danger', okTitle: 'ตกลง', cancelTitle: 'ยกเลิก',
                        centered: true,
                    }).then(value => {
                        if (!value) return;
                        this.$http.post("{{ route('admin.lotto.groups.edit') }}", { id, status, method })
                            .then(() => {
                                window.LaravelDataTables['dataTableBuilder'].draw(false);
                            });
                    });
                },
                editModal(id) {
                    this.code = null;
                    this.formaddedit = { name: '', description_translations: {}, name_en: '', name_kh: '', name_laos: '', logo: '', icon: '', logo_file: null, icon_file: null, code: '', sort: 0, is_enabled: 1 };
                    this.activeDescriptionLang = this.option.languages.length > 0 ? this.option.languages[0].value : '';
                    this.formmethod = 'edit';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.code = id;
                        this.loadData();
                        this.$refs.addedit.show();
                    });
                },
                addModal() {
                    this.code = null;
                    this.formaddedit = { name: '', description_translations: {}, name_en: '', name_kh: '', name_laos: '', logo: '', icon: '', logo_file: null, icon_file: null, code: '', sort: 0, is_enabled: 1 };
                    this.activeDescriptionLang = this.option.languages.length > 0 ? this.option.languages[0].value : '';
                    this.formmethod = 'add';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();
                    });
                },
                async loadData() {
                    const response = await axios.post("{{ route('admin.lotto.groups.loaddata') }}", { id: this.code });
                    const d = response.data.data;
                    this.formaddedit = {
                        name:       d.name,
                        description_translations: this.parseDescriptionPayload(d.description || ''),
                        name_en:    d.name_en || '',
                        name_kh:    d.name_kh || '',
                        name_laos:  d.name_laos || '',
                        logo:       d.logo || '',
                        icon:       d.icon || '',
                        logo_file:  null,
                        icon_file:  null,
                        code:       d.code,
                        sort:       d.sort,
                        is_enabled: d.is_enabled ? 1 : 0,
                    };

                    const existingDescriptionLangs = Object.keys(this.formaddedit.description_translations || {});
                    this.activeDescriptionLang = existingDescriptionLangs.length > 0
                        ? existingDescriptionLangs[0]
                        : (this.option.languages.length > 0 ? this.option.languages[0].value : '');
                },
                addEditSubmit() {
                    const url = this.formmethod === 'add'
                        ? "{{ route('admin.lotto.groups.create') }}"
                        : "{{ route('admin.lotto.groups.update') }}";
                    const payload = {
                        ...this.formaddedit,
                        description: this.serializeDescriptionPayload(),
                    };

                    const formData = new FormData();
                    if (this.code) {
                        formData.append('id', this.code);
                    }

                    Object.keys(payload)
                        .filter((key) => !['logo_file', 'icon_file', 'description_translations'].includes(key))
                        .forEach((key) => {
                            formData.append(`data[${key}]`, payload[key] ?? '');
                        });

                    if (this.formaddedit.logo_file) {
                        formData.append('logo_file', this.formaddedit.logo_file);
                    }

                    if (this.formaddedit.icon_file) {
                        formData.append('icon_file', this.formaddedit.icon_file);
                    }

                    axios.post(url, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                    })
                        .then(response => {
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true,
                            });
                            this.$refs.addedit.hide();
                            window.LaravelDataTables['dataTableBuilder'].draw(false);
                        })
                        .catch(() => {
                            console.error('addEditSubmit error');
                        });
                },
            },
        });

        window.addModal = function () { window.app.addModal(); };
        window.editModal = function (id) { window.app.editModal(id); };
    </script>
@endpush
