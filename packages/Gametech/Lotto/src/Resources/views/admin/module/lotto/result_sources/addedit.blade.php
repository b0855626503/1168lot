<b-modal ref="addeditSource" id="addeditSource" centered size="lg" title="ตั้งค่า Auto Result Source" :hide-footer="true" @shown="onModalShown" @hidden="onModalHidden">
    <b-form v-if="showSourceForm" @submit.prevent="submitSourceForm">
        <b-row>
            <b-col md="6">
                <b-form-group label="ตลาด">
                    <select ref="marketSelect" class="form-control form-control-sm" required @change="onNativeMarketChange">
                        <option value="">-- เลือกตลาด --</option>
                        @foreach(($marketOptionsGrouped ?? []) as $group)
                            <optgroup label="{{ $group['label'] ?? '-' }}">
                                @foreach(($group['options'] ?? []) as $market)
                                    <option value="{{ (string) ($market['value'] ?? '') }}"
                                            data-logo="{{ $market['logo'] ?? '' }}">
                                        {{ $market['text'] ?? '-' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </b-form-group>
            </b-col>
            <b-col md="3">
                <b-form-group label="Priority">
                    <b-form-input size="sm" type="number" min="1" v-model="sourceForm.priority"></b-form-input>
                </b-form-group>
            </b-col>
            <b-col md="3">
                <b-form-group label="Timeout (sec)">
                    <b-form-input size="sm" type="number" min="1" max="60" v-model="sourceForm.timeout_seconds"></b-form-input>
                </b-form-group>
            </b-col>
        </b-row>

        <b-row>
            <b-col md="4"><b-form-group label="Source Type"><b-form-select size="sm" :options="sourceTypes" v-model="sourceForm.source_type"></b-form-select></b-form-group></b-col>
            <b-col md="4"><b-form-group label="HTTP Method"><b-form-select size="sm" :options="httpMethods" v-model="sourceForm.http_method"></b-form-select></b-form-group></b-col>
            <b-col md="4"><b-form-group label="Parser Type"><b-form-select size="sm" :options="parserTypes" v-model="sourceForm.parser_type"></b-form-select></b-form-group></b-col>
        </b-row>

        <b-form-group label="Endpoint URL">
            <b-form-input size="sm" v-model="sourceForm.endpoint_url" required></b-form-input>
        </b-form-group>

        <b-row>
            <b-col md="8"><b-form-group label="Lookup Date Mode"><b-form-select size="sm" :options="lookupDateModes" v-model="sourceForm.lookup_date_mode"></b-form-select></b-form-group></b-col>
            <b-col md="4"><b-form-group label="Offset Days"><b-form-input size="sm" type="number" min="-365" max="365" v-model="sourceForm.lookup_date_offset_days"></b-form-input></b-form-group></b-col>
        </b-row>

        <b-row>
            <b-col md="6"><b-form-group label="Effective From (Y-m-d H:i:s)"><b-form-input size="sm" v-model="sourceForm.effective_from"></b-form-input></b-form-group></b-col>
            <b-col md="6"><b-form-group label="Effective To (Y-m-d H:i:s)"><b-form-input size="sm" v-model="sourceForm.effective_to"></b-form-input></b-form-group></b-col>
        </b-row>

        <b-row>
            <b-col md="6"><b-form-group label="Headers JSON"><b-form-textarea rows="4" v-model="sourceForm.request_headers_json"></b-form-textarea></b-form-group></b-col>
            <b-col md="6"><b-form-group label="Query Template JSON"><b-form-textarea rows="4" v-model="sourceForm.request_query_template_json"></b-form-textarea></b-form-group></b-col>
        </b-row>
        <b-row>
            <b-col md="6"><b-form-group label="Body Template JSON"><b-form-textarea rows="4" v-model="sourceForm.request_body_template_json"></b-form-textarea></b-form-group></b-col>
            <b-col md="6"><b-form-group label="Parser Config JSON"><b-form-textarea rows="4" v-model="sourceForm.parser_config_json"></b-form-textarea></b-form-group></b-col>
        </b-row>
        <b-row>
            <b-col md="6"><b-form-group label="Mapping Config JSON"><b-form-textarea rows="4" v-model="sourceForm.mapping_config_json"></b-form-textarea></b-form-group></b-col>
            <b-col md="6"><b-form-group label="Retry Policy JSON"><b-form-textarea rows="4" v-model="sourceForm.retry_policy_json"></b-form-textarea></b-form-group></b-col>
        </b-row>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-success btn-sm">บันทึก</button>
        </div>
    </b-form>
</b-modal>

@push('styles')
    <style>
        #addeditSource .select2-container--default .select2-selection--single {
            height: calc(1.5em + .5rem + 2px);
            min-height: calc(1.5em + .5rem + 2px);
            padding: 0;
            display: flex;
            align-items: center;
        }

        #addeditSource .select2-container--default .select2-selection--single .select2-selection__rendered {
            width: 100%;
            padding-left: .5rem;
            padding-right: 1.75rem;
            line-height: normal;
            display: flex !important;
            align-items: center;
            min-height: calc(1.5em + .5rem + 2px);
            overflow: visible;
        }

        #addeditSource .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
            right: .35rem;
        }

        #addeditSource .lotto-market-option {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        #addeditSource .lotto-market-option__logo {
            width: 20px;
            height: 20px;
            min-width: 20px;
            object-fit: cover;
            border-radius: 50%;
            border: 1px solid #e5e7eb;
            background: #fff;
        }

        #addeditSource .lotto-market-option__text {
            display: block;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
@endpush

@push('scripts')
    <script type="module">
        window.sourceFormApp = new Vue({
            el: '#app',
            data() {
                return {
                    showSourceForm: true,
                    sourceFormMethod: 'add',
                    sourceId: null,
                    lookupDateModes: @json($lookupDateModes ?? []),
                    parserTypes: @json($parserTypes ?? []),
                    sourceTypes: @json($sourceTypes ?? []),
                    httpMethods: @json($httpMethods ?? []),
                    sourceForm: this.newSourceForm(),
                    isSyncingMarketSelect: false,
                };
            },
            methods: {
                newSourceForm() {
                    return {
                        id: null,
                        market_id: '',
                        is_active: true,
                        priority: 100,
                        source_type: 'api',
                        endpoint_url: '',
                        http_method: 'GET',
                        request_headers_json: '',
                        request_query_template_json: '',
                        request_body_template_json: '',
                        lookup_date_mode: 'ROUND_DATE',
                        lookup_date_offset_days: 0,
                        parser_type: 'JSON_PATH',
                        parser_config_json: '',
                        mapping_config_json: '',
                        validation_config_json: '',
                        retry_policy_json: '',
                        timeout_seconds: 10,
                        effective_from: '',
                        effective_to: '',
                    };
                },
                toJsonText(value) {
                    if (!value || (Array.isArray(value) && value.length === 0) || (typeof value === 'object' && Object.keys(value).length === 0)) {
                        return '';
                    }

                    return JSON.stringify(value, null, 2);
                },
                addSourceModal() {
                    this.sourceId = null;
                    this.sourceFormMethod = 'add';
                    this.sourceForm = this.newSourceForm();
                    this.showSourceForm = true;

                    this.$nextTick(() => {
                        this.$refs.addeditSource.show();
                    });
                },
                async editSourceModal(id) {
                    this.sourceId = id;
                    this.sourceFormMethod = 'edit';

                    const response = await axios.post("{{ route('admin.lotto.result_sources.loaddata') }}", { id });
                    const item = response?.data?.data || {};

                    this.sourceForm = {
                        ...this.newSourceForm(),
                        ...item,
                        market_id: item.market_id ? String(item.market_id) : '',
                        request_headers_json: this.toJsonText(item.request_headers_json),
                        request_query_template_json: this.toJsonText(item.request_query_template_json),
                        request_body_template_json: this.toJsonText(item.request_body_template_json),
                        parser_config_json: this.toJsonText(item.parser_config_json),
                        mapping_config_json: this.toJsonText(item.mapping_config_json),
                        validation_config_json: this.toJsonText(item.validation_config_json),
                        retry_policy_json: this.toJsonText(item.retry_policy_json),
                    };

                    this.showSourceForm = true;

                    this.$nextTick(() => {
                        this.$refs.addeditSource.show();
                    });
                },
                onModalShown() {
                    this.initMarketSelect2();
                    this.syncMarketSelectValue();
                },
                onModalHidden() {
                    this.destroyMarketSelect2();
                },
                onNativeMarketChange(event) {
                    if (this.isSyncingMarketSelect) {
                        return;
                    }

                    const value = event?.target?.value || '';
                    this.sourceForm.market_id = value ? String(value) : '';
                },
                getMarketDropdownParent() {
                    const modalRef = this.$refs.addeditSource;
                    const modalEl = modalRef && modalRef.$el ? modalRef.$el : null;

                    if (!modalEl || !window.jQuery) {
                        return window.jQuery ? window.jQuery(document.body) : null;
                    }

                    const $modalEl = window.jQuery(modalEl);
                    const $content = $modalEl.find('.modal-content');

                    if ($content.length) {
                        return $content;
                    }

                    const $dialog = $modalEl.find('.modal-dialog');
                    if ($dialog.length) {
                        return $dialog;
                    }

                    return $modalEl;
                },
                normalizeLogoUrl(rawUrl) {
                    const value = String(rawUrl || '').trim();
                    if (!value) {
                        return '';
                    }

                    if (/^https?:\/\//i.test(value)) {
                        return value;
                    }

                    if (value.startsWith('/')) {
                        return `${window.location.origin}${value}`;
                    }

                    return `${window.location.origin}/${value}`;
                },
                resolveLogoFromState(state, $select) {
                    if (state?.element) {
                        const byDataset = state.element.dataset ? state.element.dataset.logo : '';
                        if (byDataset) {
                            return byDataset;
                        }

                        const byAttr = state.element.getAttribute ? state.element.getAttribute('data-logo') : '';
                        if (byAttr) {
                            return byAttr;
                        }
                    }

                    if ($select && state?.id) {
                        const $opt = $select.find('option[value="' + String(state.id) + '"]');
                        if ($opt.length) {
                            return String($opt.attr('data-logo') || '');
                        }
                    }

                    return '';
                },
                renderMarketOption(state, $select) {
                    if (!state.id) {
                        return state.text || '';
                    }

                    const logoRaw = this.resolveLogoFromState(state, $select);
                    const logo = this.normalizeLogoUrl(logoRaw);
                    const text = String(state.text || '').trim();

                    const $wrapper = window.jQuery('<span class="lotto-market-option"></span>');

                    if (logo) {
                        const $img = window.jQuery('<img class="lotto-market-option__logo" alt="">');
                        $img.attr('src', logo);
                        $img.on('error', function () {
                            window.jQuery(this).remove();
                        });
                        $wrapper.append($img);
                    }

                    $wrapper.append(
                        window.jQuery('<span class="lotto-market-option__text"></span>').text(text)
                    );

                    return $wrapper;
                },
                initMarketSelect2() {
                    const selectEl = this.$refs.marketSelect;
                    if (!selectEl || !window.jQuery) {
                        return;
                    }

                    const $select = window.jQuery(selectEl);
                    if (!$select.length || typeof $select.select2 !== 'function') {
                        return;
                    }

                    this.destroyMarketSelect2();

                    const dropdownParent = this.getMarketDropdownParent();
                    const self = this;

                    $select.select2({
                        width: '100%',
                        dropdownParent: dropdownParent,
                        placeholder: '-- เลือกรายการหวย --',
                        allowClear: false,
                        templateResult(state) {
                            return self.renderMarketOption(state, $select);
                        },
                        templateSelection(state) {
                            return self.renderMarketOption(state, $select);
                        },
                        escapeMarkup(markup) {
                            return markup;
                        },
                    });

                    $select.on('change.resultSourceMarket', () => {
                        const value = $select.val();
                        const normalizedValue = value ? String(value) : '';

                        if (this.sourceForm.market_id === normalizedValue) {
                            return;
                        }

                        this.isSyncingMarketSelect = true;
                        this.sourceForm.market_id = normalizedValue;

                        this.$nextTick(() => {
                            this.isSyncingMarketSelect = false;
                        });
                    });
                },
                destroyMarketSelect2() {
                    const selectEl = this.$refs.marketSelect;
                    if (!selectEl || !window.jQuery) {
                        return;
                    }

                    const $select = window.jQuery(selectEl);
                    if (!$select.length) {
                        return;
                    }

                    $select.off('.resultSourceMarket');

                    if ($select.hasClass('select2-hidden-accessible') && typeof $select.select2 === 'function') {
                        $select.select2('destroy');
                    }
                },
                syncMarketSelectValue() {
                    const selectEl = this.$refs.marketSelect;
                    if (!selectEl || !window.jQuery) {
                        return;
                    }

                    const value = this.sourceForm.market_id ? String(this.sourceForm.market_id) : '';
                    const $select = window.jQuery(selectEl);

                    if (String($select.val() || '') === value) {
                        return;
                    }

                    this.isSyncingMarketSelect = true;
                    $select.val(value);

                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.trigger('change.select2');
                    }

                    this.$nextTick(() => {
                        this.isSyncingMarketSelect = false;
                    });
                },
                async submitSourceForm() {
                    const url = this.sourceFormMethod === 'add'
                        ? "{{ route('admin.lotto.result_sources.create') }}"
                        : "{{ route('admin.lotto.result_sources.update') }}";

                    try {
                        const response = await axios.post(url, {
                            id: this.sourceId,
                            data: {
                                ...this.sourceForm,
                                id: this.sourceId,
                                market_id: this.sourceForm.market_id ? parseInt(this.sourceForm.market_id, 10) : null,
                            },
                        });

                        this.$refs.addeditSource.hide();
                        window.LaravelDataTables['dataTableBuilder'].draw(false);

                        await this.$bvModal.msgBoxOk(response?.data?.message || 'บันทึก source สำเร็จ', {
                            title: 'ผลการดำเนินการ',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'success',
                            centered: true,
                        });
                    } catch (error) {
                        const message = error?.response?.data?.message || 'บันทึก source ไม่สำเร็จ';

                        await this.$bvModal.msgBoxOk(message, {
                            title: 'เกิดข้อผิดพลาด',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true,
                        });
                    }
                },
                editSourceStatus(id, status) {
                    this.$bvModal.msgBoxConfirm('ต้องการเปลี่ยนสถานะ source หรือไม่?', {
                        title: 'ยืนยัน',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'ตกลง',
                        cancelTitle: 'ยกเลิก',
                        centered: true,
                    }).then(async (value) => {
                        if (!value) {
                            return;
                        }

                        try {
                            const response = await axios.post("{{ route('admin.lotto.result_sources.edit') }}", {
                                id: id,
                                status: status,
                                method: 'is_active',
                            });

                            window.LaravelDataTables['dataTableBuilder'].draw(false);

                            await this.$bvModal.msgBoxOk(response?.data?.message || 'อัปเดตสถานะ source สำเร็จ', {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                centered: true,
                            });
                        } catch (error) {
                            const message = error?.response?.data?.message || 'อัปเดตสถานะ source ไม่สำเร็จ';

                            await this.$bvModal.msgBoxOk(message, {
                                title: 'เกิดข้อผิดพลาด',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'danger',
                                centered: true,
                            });
                        }
                    });
                },
            },
            watch: {
                'sourceForm.market_id'() {
                    if (this.isSyncingMarketSelect) {
                        return;
                    }

                    this.$nextTick(() => this.syncMarketSelectValue());
                },
            },
        });

        window.addSourceModal = function () { window.sourceFormApp.addSourceModal(); };
        window.editSourceModal = function (id) { window.sourceFormApp.editSourceModal(id); };
        window.editSourceStatus = function (id, status) { window.sourceFormApp.editSourceStatus(id, status); };
    </script>
@endpush
