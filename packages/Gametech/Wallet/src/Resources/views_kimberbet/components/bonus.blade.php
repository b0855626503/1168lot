<script type="text/x-template" id="bonus-modal-template">
    <div class="modal modal-custom fade" id="bonusModal" ref="bonusModal" data-bs-backdrop="static" data-bs-keyboard="false"
         tabindex="-1" aria-labelledby="bonusLabel" aria-hidden="true" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content bg-dark-2" style="min-height: 60vh;">
                <div class="modal-header">
                    <h5 class="modal-title text-center mb-0 text-dark lh-1"
                        id="bonusLabel" v-text="trans('app.home.bonus')"> </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-1">
                    <div class="container-fluid">
                        <div class="row g-2 mt-1">
                            <div class="col-6" v-for="tab in translatedTabs" :key="tab.id">
                                <div
                                        class="card h-100 text-center shadow-sm p-2"
                                        :class="[
										'bg-dark',
										'text-white',
										tab.id !== selected ? 'opacity-75' : 'opacity-100'
									]">

                                    <button
                                            @click="getBonus(tab)"
                                            :class="[
                                              'btn btn-for-bonus',
                                              item[tab.id] === 0 ? 'opacity-50 pointer-events-none' : ''
                                            ]"
                                            :disabled="submitting || item[tab.id] === 0">
                                        <img :src="tab.icon" class="card-img-top mx-auto"
                                             style="width: 50px; object-fit: contain;">
                                        <small class="-title text-white" v-text="item[tab.id]"></small>
                                        <p class="-title text-white" v-text="tab.title"></p>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

@push('components')
    <script type="module">
        Vue.component('bonus-modal', {
            template: '#bonus-modal-template',
            data() {
                return {
                    loading: false,
                    submitting: false,       // ★ ป้องกันกดย้ำตอนยืนยัน
                    selectedPro: false,
                    promotion: { name: '', min: 0 },
                    item: { cashback: 0, ic: 0, bonus: 0, faststart: 0 },
                    resetCounter: 0,
                    selected: '',
                    // ใช้ titleKey แล้วค่อยแปลใน computed เพื่อให้ reactive ต่อการเปลี่ยนภาษา
                    tabs: [
                        { id: 'bonus',     method: 'BONUS',     titleKey: 'app.bonus.wheel',     icon: '/assets/kimberbet/images/icon/icon-bonus.webp' },
                        { id: 'cashback',  method: 'CASHBACK',  titleKey: 'app.bonus.cashback',  icon: '/assets/kimberbet/images/icon/icon-cashback.webp' },
                        { id: 'ic',        method: 'IC',        titleKey: 'app.bonus.ic',        icon: '/assets/kimberbet/images/icon/icon-ic.webp' },
                        { id: 'faststart', method: 'FASTSTART', titleKey: 'app.bonus.faststart', icon: '/assets/kimberbet/images/icon/icon-faststart.webp' },
                    ]
                };
            },
            computed: {
                configs() {
                    return (this.$root && this.$root.$data && this.$root.$data.webconfig) || {};
                },
                // ★ แปลใน computed เพื่อรองรับการเปลี่ยนภาษาแบบ reactive
                translatedTabs() {
                    if (window.I18nStore) { I18nStore.version; } // ดึงเวอร์ชันให้ reactive
                    return this.tabs.map(t => ({
                        ...t,
                        title: this.trans(t.titleKey)
                    }));
                },
            },
            mounted() {
                this.loadData();
            },
            methods: {


                async loadData() {
                    this.loading = true;
                    try {
                        const res = await axios.get("{{ route('customer.home.credit') }}", {
                            headers: { 'Cache-Control': 'no-store' },
                            timeout: 10000
                        });

                        if (res.data?.success) {
                            this.item = {
                                cashback:  Number(res.data?.profile?.cashback ?? 0),
                                ic:        Number(res.data?.profile?.ic ?? 0),
                                bonus:     Number(res.data?.profile?.bonus ?? 0),
                                faststart: Number(res.data?.profile?.faststart ?? 0),
                            };
                        } else {
                            this.item = { cashback: 0, ic: 0, bonus: 0, faststart: 0 };
                        }
                    } catch (e) {
                        this.item = { cashback: 0, ic: 0, bonus: 0, faststart: 0 };
                    } finally {
                        this.loading = false;
                    }
                },

                getBonusByKey(key) {
                    return this.tabs.find(t => t.method === key || t.key === key || t.id === key);
                },

                async getBonus(tabOrKey) {
                    const tab = typeof tabOrKey === 'string' ? this.getBonusByKey(tabOrKey) : tabOrKey;
                    if (!tab) {
                        console.warn('Tab not found for:', tabOrKey);
                        return;
                    }

                    // ปิด modal แม่อย่างปลอดภัย (รองรับกรณี instance ยังไม่ถูกสร้าง)
                    const modalEl = document.getElementById('bonusModal');
                    const instance = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
                    if (instance && modalEl.classList.contains('show')) {
                        instance.hide();
                    }

                    // เตรียมข้อความ (กันค่าหาย)
                    const resetInfo = this.configs?.pro_reset ?? '';
                    const titleTxt  = `${this.trans('app.bonus.word')}${tab.title}${this.trans('app.bonus.word2')}`;
                    const htmlTxt   = `${this.trans('app.bonus.detail')}${resetInfo}`;

                    try {
                        const result = await Swal.fire({
                            title: titleTxt,
                            html:  htmlTxt,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: this.trans('app.bonus.yes'),
                            cancelButtonText:  this.trans('app.bonus.no'),
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            allowEnterKey: false
                        });

                        if (!result.isConfirmed) {
                            // ผู้ใช้ยกเลิก → เปิด modal กลับ
                            instance?.show();
                            return;
                        }

                        // ผู้ใช้ยืนยัน → ล็อกกดย้ำ
                        if (this.submitting) return;
                        this.submitting = true;

                        const resp = await axios.post("{{ route('customer.transfer.bonus.confirm') }}", { id: tab.method }, {
                            headers: { 'Cache-Control': 'no-store' },
                            timeout: 15000
                        });

                        if (resp.data?.success) {
                            await Swal.fire(
                                this.trans('app.bonus.success'),
                                resp.data?.message || '',
                                'success'
                            );
                            // โหลดยอดใหม่ เผื่อหลังโอน/ย้ายมีการเปลี่ยนค่า
                            this.loadData().catch(() => {});
                        } else {
                            await Swal.fire(
                                this.trans('app.bonus.fail'),
                                resp.data?.message || '',
                                'error'
                            );
                            // เปิด modal กลับให้ผู้ใช้เลือกใหม่
                            instance?.show();
                        }

                    } catch (err) {
                        console.error('bonus confirm error:', err);
                        await Swal.fire(
                            this.trans('app.bonus.fail'),
                            this.trans('app.common.something_wrong') || 'Something went wrong',
                            'error'
                        );
                        instance?.show();
                    } finally {
                        this.submitting = false;
                    }
                },

                async showModal() {
                    await this.$nextTick();
                    this.loadData();
                    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('bonusModal'));

                    modal.show();
                },
            },
        });
    </script>
@endpush
