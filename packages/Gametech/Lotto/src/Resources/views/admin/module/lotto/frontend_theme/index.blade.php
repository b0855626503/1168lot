@extends('admin::layouts.master')

@section('title')
    Frontend Theme
@endsection

@section('content')
    <section class="content text-sm">
        <div class="card">
            <div class="card-body">
                <frontend-theme-setting></frontend-theme-setting>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script type="text/x-template" id="frontend-theme-setting-template">
        <div>
            <div class="form-group">
                <label>Preset</label>
                <select v-model="form.preset_key" class="form-control form-control-sm" @change="applyPresetToInputs">
                    <option v-for="preset in presets" :key="preset.key" :value="preset.key">@{{ preset.name }}</option>
                </select>
            </div>

            <div class="row">
                <div v-for="tokenKey in tokenKeys" :key="tokenKey" class="col-md-6 mb-2">
                    <label class="mb-1 d-block">@{{ tokenKey }}</label>
                    <small class="text-muted d-block mb-1">@{{ tokenMeta[tokenKey] ? tokenMeta[tokenKey].description : '' }}</small>
                    <div class="d-flex align-items-center">
                        <input
                            v-model="editableTokens[tokenKey]"
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="#000000 หรือ rgba(...)">
                        <input
                            :value="hexColorValue(tokenKey)"
                            type="color"
                            class="ml-2"
                            style="width: 42px; height: 30px; border: 0; background: transparent; padding: 0;"
                            @input="onColorPickerChange(tokenKey, $event.target.value)">
                    </div>
                </div>
            </div>

            <button class="btn btn-primary btn-sm mt-2" @click="save">บันทึก</button>
        </div>
    </script>

    <script type="module">
        Vue.component('frontend-theme-setting', {
            template: '#frontend-theme-setting-template',
            data() {
                const initial = @json($initialTheme);
                return {
                    presets: Array.isArray(initial.presets) ? initial.presets : [],
                    tokenKeys: Object.keys(initial.tokens || {}),
                    tokenMeta: {
                        'surface-subtle': { description: 'พื้นเทาอ่อน (input, secondary card)' },
                        'surface-card': { description: 'พื้นขาวของการ์ด/แผง' },
                        'surface-page': { description: 'พื้นหลังหน้าเว็บ (body)' },
                        'surface-navbar': { description: 'พื้นหลังแถบเมนู' },
                        'surface-highlight': { description: 'การ์ดเด่น เช่น balance' },
                        'brand-primary': { description: 'สีแบรนด์หลัก' },
                        'brand-primary-hover': { description: 'สีแบรนด์ตอน hover' },
                        'text-strong': { description: 'ตัวหนังสือเข้มสุด' },
                        'text-default': { description: 'ตัวหนังสือปกติ' },
                        'text-muted': { description: 'ตัวหนังสือจาง' },
                        'border-default': { description: 'สีเส้นขอบ' },
                        'status-error': { description: 'สีสถานะ error' },
                        'status-success': { description: 'สีสถานะ success' },
                        'status-warning': { description: 'สีสถานะ warning' },
                    },
                    form: {
                        preset_key: initial.preset_key || 'midnight',
                    },
                    editableTokens: { ...(initial.tokens || {}) },
                };
            },
            methods: {
                selectedPreset() {
                    return this.presets.find((preset) => preset.key === this.form.preset_key) || null;
                },
                applyPresetToInputs() {
                    const preset = this.selectedPreset();
                    if (!preset || !preset.tokens) {
                        return;
                    }

                    this.tokenKeys.forEach((tokenKey) => {
                        this.$set(this.editableTokens, tokenKey, String(preset.tokens[tokenKey] || ''));
                    });
                },
                hexColorValue(tokenKey) {
                    const value = String(this.editableTokens[tokenKey] || '').trim();
                    if (/^#([0-9a-fA-F]{6})$/.test(value)) {
                        return value;
                    }

                    if (/^#([0-9a-fA-F]{3})$/.test(value)) {
                        return '#' + value.substring(1).split('').map((ch) => ch + ch).join('');
                    }

                    return '#000000';
                },
                onColorPickerChange(tokenKey, hexValue) {
                    this.$set(this.editableTokens, tokenKey, String(hexValue || '').trim());
                },
                buildCustomTokens() {
                    const preset = this.selectedPreset();
                    const presetTokens = preset && preset.tokens ? preset.tokens : {};
                    const customTokens = {};

                    this.tokenKeys.forEach((tokenKey) => {
                        const current = String(this.editableTokens[tokenKey] || '').trim();
                        const presetValue = String(presetTokens[tokenKey] || '').trim();

                        if (current !== presetValue) {
                            customTokens[tokenKey] = current;
                        }
                    });

                    return customTokens;
                },
                async save() {
                    try {
                        await axios.post("{{ route('admin.lotto.frontend_theme.update') }}", {
                            data: {
                                preset_key: this.form.preset_key,
                                custom_tokens: this.buildCustomTokens(),
                            },
                        });

                        this.$bvModal.msgBoxOk('บันทึกเรียบร้อยแล้ว', {
                            title: 'ผลการดำเนินการ',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'success',
                            headerClass: 'p-2 border-bottom-0',
                            footerClass: 'p-2 border-top-0',
                            centered: true,
                        });
                    } catch (error) {
                        const message = (error && error.response && error.response.data && error.response.data.message)
                            ? error.response.data.message
                            : 'บันทึกไม่สำเร็จ';

                        this.$bvModal.msgBoxOk(message, {
                            title: 'ผิดพลาด',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            headerClass: 'p-2 border-bottom-0',
                            footerClass: 'p-2 border-top-0',
                            centered: true,
                        });
                    }
                },
            },
        });
    </script>
    @include('admin::layouts.loadcnt_js')
@endpush
