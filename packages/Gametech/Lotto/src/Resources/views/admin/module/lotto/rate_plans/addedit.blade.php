
<b-modal ref="addedit" id="addedit" centered size="lg" title="อัตราจ่าย" :no-stacking="true"
         :no-close-on-backdrop="true"
         :hide-footer="true"
         :lazy="true">
    <b-container class="bv-example-row">
        <b-form @submit.prevent="addEditSubmit" v-if="show" id="frmaddedit" ref="frmaddedit">
            <b-form-row>
                <b-col md="6">
                    <b-form-group label="กลุ่มหวย:" label-for="group_id">
                        <b-form-select id="group_id" v-model="formaddedit.group_id" :options="option.groups" size="sm" required></b-form-select>
                    </b-form-group>
                </b-col>
                <b-col md="6">
                    <b-form-group label="ชื่อแผนอัตราจ่าย:" label-for="name">
                        <b-form-input id="name" v-model="formaddedit.name" type="text" size="sm" placeholder="เช่น แผนมาตรฐาน" autocomplete="off" required></b-form-input>
                    </b-form-group>
                </b-col>
            </b-form-row>

            <b-form-group label="คำอธิบาย:" label-for="description">
                <b-form-textarea id="description" v-model="formaddedit.description" rows="2" max-rows="4" size="sm" placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)"></b-form-textarea>
            </b-form-group>

            <div class="table-responsive mb-2">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                    <tr>
                        <th style="width: 220px;">ประเภทเดิมพัน</th>
                        <th style="width: 180px;">อัตราจ่าย</th>
                        <th style="width: 180px;">ส่วนลด (%)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="type in betTypes" :key="type.key">
                        <td>@{{ type.label }}</td>
                        <td>
                            <b-form-input
                                :id="'payout_' + type.key"
                                v-model="formaddedit.items[type.key].payout"
                                type="number"
                                min="0"
                                step="0.01"
                                size="sm"
                            ></b-form-input>
                        </td>
                        <td>
                            <b-form-input
                                :id="'discount_' + type.key"
                                v-model="formaddedit.items[type.key].discount_percent"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                size="sm"
                            ></b-form-input>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <b-form-group>
                <b-form-checkbox v-model="formaddedit.is_enabled" :value="1" :unchecked-value="0">
                    เปิดใช้งาน
                </b-form-checkbox>
            </b-form-group>

            <b-button type="submit" variant="primary" size="sm">บันทึก</b-button>
        </b-form>
    </b-container>
</b-modal>

@push('scripts')
    <script type="module">
        window.app = new Vue({
            el: '#app',
            data() {
                const betTypes = @json($betTypes ?? []);
                const defaultItems = {};

                betTypes.forEach(type => {
                    defaultItems[type.key] = {
                        payout: 0,
                        discount_percent: 0,
                    };
                });

                return {
                    show: true,
                    betTypes: betTypes,
                    formmethod: 'add',
                    formaddedit: {
                        group_id: '',
                        name: '',
                        description: '',
                        is_enabled: 1,
                        items: defaultItems,
                    },
                    option: {
                        groups: [
                            { value: '', text: '-- เลือกกลุ่มหวย --' },
                            @foreach(($groups ?? []) as $group)
                            { value: {{ $group->id }}, text: '{{ $group->name }} ({{ $group->code }})' },
                            @endforeach
                        ],
                    },
                };
            },
            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            },
            methods: {
                emptyItems(types) {
                    const rows = {};

                    types.forEach(type => {
                        rows[type.key] = {
                            payout: 0,
                            discount_percent: 0,
                        };
                    });

                    return rows;
                },
                normalizeItems(rawItems) {
                    const rows = this.emptyItems(this.betTypes);
                    const list = Array.isArray(rawItems) ? rawItems : [];

                    list.forEach(item => {
                        if (!rows[item.bet_type]) {
                            return;
                        }

                        rows[item.bet_type] = {
                            payout: item.payout ?? 0,
                            discount_percent: item.discount_percent ?? 0,
                        };
                    });

                    return rows;
                },
                editdata(id, status, method) {
                    this.$bvModal.msgBoxConfirm('ต้องการเปลี่ยนสถานะหรือไม่?', {
                        title: 'ยืนยัน',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'ตกลง',
                        cancelTitle: 'ยกเลิก',
                        centered: true,
                    }).then(value => {
                        if (!value) {
                            return;
                        }

                        this.$http.post("{{ route('admin.lotto.rate_plans.edit') }}", { id, status, method })
                            .then(() => {
                                window.LaravelDataTables['dataTableBuilder'].draw(false);
                            });
                    });
                },
                editModal(id) {
                    this.code = null;
                    this.formaddedit = {
                        group_id: '',
                        name: '',
                        description: '',
                        is_enabled: 1,
                        items: this.emptyItems(this.betTypes),
                    };
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
                    this.formaddedit = {
                        group_id: '',
                        name: '',
                        description: '',
                        is_enabled: 1,
                        items: this.emptyItems(this.betTypes),
                    };
                    this.formmethod = 'add';

                    this.show = false;
                    this.$nextTick(() => {
                        this.show = true;
                        this.$refs.addedit.show();
                    });
                },
                async loadData() {
                    const response = await axios.post("{{ route('admin.lotto.rate_plans.loaddata') }}", { id: this.code });
                    const d = response.data.data;

                    this.formaddedit = {
                        group_id: d.group_id,
                        name: d.name,
                        description: d.description || '',
                        is_enabled: d.is_enabled ? 1 : 0,
                        items: this.normalizeItems(d.items),
                    };
                },
                addEditSubmit() {
                    const url = this.formmethod === 'add'
                        ? "{{ route('admin.lotto.rate_plans.create') }}"
                        : "{{ route('admin.lotto.rate_plans.update') }}";

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
            },
        });
    </script>
@endpush
