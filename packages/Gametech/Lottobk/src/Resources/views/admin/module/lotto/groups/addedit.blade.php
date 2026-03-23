<b-modal ref="addedit" id="addedit" centered size="sm" title="กลุ่มหวย" :no-stacking="true"
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
        <b-form-group label="Sort:" label-for="sort">
            <b-form-input
                id="sort"
                v-model="formaddedit.sort"
                type="number"
                min="0"
                size="sm"
            ></b-form-input>
        </b-form-group>
        <b-form-group>
            <b-form-checkbox v-model="formaddedit.is_enabled" :value="1" :unchecked-value="0">
                เปิดใช้งาน
            </b-form-checkbox>
        </b-form-group>
        <b-form-group label="นโยบายตลาดใหม่:" label-for="rollout_mode">
            <b-form-select
                id="rollout_mode"
                v-model="formaddedit.rollout_mode"
                :options="option.rolloutModes"
                size="sm"
                required
            ></b-form-select>
        </b-form-group>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <b-button type="submit" variant="primary" size="sm">บันทึก</b-button>
            </div>
            <b-form-group v-if="formmethod === 'edit'" class="text-right mb-0">
                <b-button type="button" variant="outline-warning" size="sm" @click="applyRolloutAll">ใช้กับสมาชิกเดิมทั้งหมด</b-button>
                <b-button type="button" variant="outline-secondary" size="sm" @click="applyRolloutSelected">ใช้กับสมาชิกที่ระบุ</b-button>
            </b-form-group>
        </div>
    </b-form>
</b-modal>

<b-modal ref="rolloutSelector" id="group-rollout-selector" centered size="md" title="เลือกสมาชิกสำหรับ Rollout" :hide-footer="true">
    <b-form @submit.prevent="submitSelectedRollout">
        <b-form-group label="ค้นหาสมาชิก" label-for="rollout-keyword">
            <div class="d-flex">
                <b-form-input
                    id="rollout-keyword"
                    v-model.trim="rolloutSearchKeyword"
                    size="sm"
                    placeholder="ค้นหาจาก code / user_name / ชื่อ / นามสกุล"
                ></b-form-input>
                <b-button type="button" variant="outline-primary" size="sm" class="ml-2" @click="searchRolloutMembers">ค้นหา</b-button>
            </div>
        </b-form-group>

        <b-form-group label="สมาชิกที่ค้นพบ">
            <small class="text-muted">@{{ rolloutMemberOptions.length === 0 ? 'ยังไม่มีข้อมูล กรุณากดค้นหา' : '' }}</small>
            <b-form-checkbox-group
                v-model="rolloutSelectedMemberIds"
                :options="rolloutMemberOptions"
                value-field="value"
                text-field="text"
                stacked
                size="sm"
            ></b-form-checkbox-group>
        </b-form-group>

        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">เลือกแล้ว @{{ rolloutSelectedMemberIds.length }} รายการ</span>
            <b-button type="submit" variant="primary" size="sm">ยืนยัน Rollout</b-button>
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
                        code: '',
                        sort: 0,
                        is_enabled: 1,
                        rollout_mode: 'new_only',
                    },
                    option: {
                        rolloutModes: [
                            { value: 'new_only', text: 'ใช้กับสมาชิกใหม่เท่านั้น' },
                            { value: 'all', text: 'สมาชิกใหม่ + สมาชิกเดิมทั้งหมด' },
                            { value: 'selected', text: 'สมาชิกใหม่ + ค่อยเลือกสมาชิกเดิมภายหลัง' },
                        ],
                    },
                    rolloutSearchKeyword: '',
                    rolloutMemberOptions: [],
                    rolloutSelectedMemberIds: [],
                    rolloutTargetIds: [],
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            methods: {
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
                    this.formaddedit = { name: '', code: '', sort: 0, is_enabled: 1, rollout_mode: 'new_only' };
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
                    this.formaddedit = { name: '', code: '', sort: 0, is_enabled: 1, rollout_mode: 'new_only' };
                    this.formmethod = 'add';
                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();
                    });
                },
                async searchRolloutMembers() {
                    const response = await axios.post("{{ route('admin.lotto.groups.search_members') }}", {
                        keyword: this.rolloutSearchKeyword,
                        limit: 30,
                    });

                    this.rolloutMemberOptions = response?.data?.data || [];
                },
                async loadData() {
                    const response = await axios.post("{{ route('admin.lotto.groups.loaddata') }}", { id: this.code });
                    const d = response.data.data;
                    this.formaddedit = {
                        name:       d.name,
                        code:       d.code,
                        sort:       d.sort,
                        is_enabled: d.is_enabled ? 1 : 0,
                        rollout_mode: d.rollout_mode || 'new_only',
                    };
                },
                addEditSubmit() {
                    const url = this.formmethod === 'add'
                        ? "{{ route('admin.lotto.groups.create') }}"
                        : "{{ route('admin.lotto.groups.update') }}";
                    this.$http.post(url, { id: this.code, data: this.formaddedit })
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
                async applyRolloutAll() {
                    if (!this.code) {
                        return;
                    }

                    const confirmed = await this.$bvModal.msgBoxConfirm('ยืนยันใช้ค่าปัจจุบันกับสมาชิกเดิมทั้งหมด?', {
                        title: 'ยืนยันการ rollout',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'warning',
                        okTitle: 'ยืนยัน',
                        cancelTitle: 'ยกเลิก',
                        centered: true,
                    });

                    if (!confirmed) {
                        return;
                    }

                    this.rolloutTargetIds = [];
                    await this.runRolloutForTargets('all', []);
                },
                async applyRolloutSelected() {
                    if (!this.code) {
                        return;
                    }

                    this.rolloutTargetIds = [];
                    await this.openRolloutSelector();
                },
                async batchRolloutFromTable(scope) {
                    const ids = (window.getSelectedGroupIds ? window.getSelectedGroupIds() : []);
                    if (ids.length === 0) {
                        this.$bvModal.msgBoxOk('กรุณาเลือกอย่างน้อย 1 แถว', {
                            title: 'ยังไม่ได้เลือกข้อมูล',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true,
                        });
                        return;
                    }

                    this.rolloutTargetIds = ids;

                    if (scope === 'all') {
                        const confirmed = await this.$bvModal.msgBoxConfirm('ยืนยันใช้ค่าปัจจุบันกับสมาชิกเดิมทั้งหมดในแถวที่เลือก?', {
                            title: 'ยืนยันการ rollout',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'warning',
                            okTitle: 'ยืนยัน',
                            cancelTitle: 'ยกเลิก',
                            centered: true,
                        });

                        if (!confirmed) {
                            return;
                        }

                        await this.runRolloutForTargets('all', []);

                        return;
                    }

                    await this.openRolloutSelector();
                },
                async openRolloutSelector() {
                    this.rolloutSearchKeyword = '';
                    this.rolloutMemberOptions = [];
                    this.rolloutSelectedMemberIds = [];
                    await this.searchRolloutMembers();
                    this.$refs.rolloutSelector.show();
                },
                resolveRolloutTargetIds() {
                    const source = this.rolloutTargetIds.length > 0
                        ? this.rolloutTargetIds
                        : [this.code];

                    return Array.from(new Set(source
                        .map((id) => parseInt(id, 10))
                        .filter((id) => Number.isInteger(id) && id > 0)));
                },
                async runRolloutForTargets(scope, memberIds) {
                    const targetIds = this.resolveRolloutTargetIds();
                    if (targetIds.length === 0) {
                        return;
                    }

                    const requests = targetIds.map((id) => this.$http.post("{{ route('admin.lotto.groups.apply_rollout') }}", {
                        id,
                        scope,
                        member_ids: memberIds,
                    }));

                    const results = await Promise.allSettled(requests);
                    const successItems = results.filter((result) => result.status === 'fulfilled');
                    const success = successItems.length;
                    const failed = results.length - success;
                    const affected = successItems.reduce((carry, result) => {
                        return carry + ((result.value?.data?.data?.affected_members ?? 0) | 0);
                    }, 0);

                    this.$bvModal.msgBoxOk(`สำเร็จ ${success} รายการ / ไม่สำเร็จ ${failed} รายการ / สมาชิกที่ได้รับผลรวม ${affected} รายการ`, {
                        title: 'ผลการ rollout',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: failed > 0 ? 'warning' : 'success',
                        centered: true,
                    });

                    this.rolloutTargetIds = [];
                },
                async submitSelectedRollout() {
                    const targetIds = this.resolveRolloutTargetIds();
                    if (targetIds.length === 0) {
                        return;
                    }

                    if (this.rolloutSelectedMemberIds.length === 0) {
                        this.$bvModal.msgBoxOk('กรุณาเลือกสมาชิกอย่างน้อย 1 รายการ', {
                            title: 'ข้อมูลไม่ครบ',
                            size: 'sm',
                            buttonSize: 'sm',
                            okVariant: 'danger',
                            centered: true,
                        });
                        return;
                    }

                    await this.runRolloutForTargets('selected', this.rolloutSelectedMemberIds);
                    this.$refs.rolloutSelector.hide();
                },
            },
        });

        window.addModal = function () { window.app.addModal(); };
        window.editModal = function (id) { window.app.editModal(id); };
        window.applyGroupBatchRollout = function (scope) { window.app.batchRolloutFromTable(scope); };
    </script>
@endpush
