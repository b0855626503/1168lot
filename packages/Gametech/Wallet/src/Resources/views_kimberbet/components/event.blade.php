<script type="text/x-template" id="event-modal-template">
    <div class="modal modal-custom fade"
         id="eventModal"
         ref="eventModal"
         data-bs-backdrop="static"
         data-bs-keyboard="false"
         tabindex="-1"
         aria-labelledby="eventLabel"
         aria-hidden="true"
         data-bs-focus="false">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content bg-dark-2" style="min-height: 60vh;">
                <div class="modal-header">
                    <h5 class="modal-title text-center mb-0 text-dark lh-1"
                        id="eventLabel"
                        v-text="trans('app.home.event')">
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-1">
                    <div class="container-fluid">
                        <div class="row g-2 mt-1">
                            <div class="col-6" v-for="tab in translatedTabs" :key="tab.id">
                                <div class="card h-100 text-center shadow-sm p-2"
                                     :class="['bg-dark','text-white', tab.id !== selected ? 'opacity-75' : 'opacity-100']">
                                    <button class="btn btn-for-bonus" @click="getBonus(tab)">
                                        <img :src="tab.icon" class="card-img-top mx-auto"
                                             style="width: 50px; object-fit: contain;">
                                        <p class="-title text-white" v-text="tab.title"></p>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- /modal-body -->
            </div>
        </div>
    </div>
</script>



@push('components')
    <script type="module">

        // === โมดัลแม่: แสดงรายการอีเวนต์ และเปิดโมดัลปลายทางเมื่อคลิก ===
        Vue.component('event-modal', {
            template: '#event-modal-template',
            data() {
                return {
                    selectedPro: false,
                    promotion: { name: '', min: 0 },
                    resetCounter: 0,
                    selected: '',
                    tabs: this.getTabs(), // ✅ เปลี่ยนแค่นี้
                };
            },

            computed: {
                configs() {
                    return (this.$root && this.$root.$data && this.$root.$data.webconfig) || [];
                },
                // แปลใน computed → reactive เมื่อเปลี่ยนภาษา
                translatedTabs() {
                    if (window.I18nStore) { /* eslint-disable no-unused-expressions */ I18nStore.version; /* eslint-enable */ }
                    return this.tabs.map(t => ({
                        ...t,
                        title: this.trans(t.titleKey)
                    }));
                }
            },
            methods: {
                getTabs() {
                    const base = [
                        {
                            id: 'wheel',
                            method: 'WHEEL',
                            type: 'button',
                            titleKey: 'app.home.wheels',
                            icon: '/assets/kimberbet/images/icon/icon-bonus.webp',
                            targetModalId: 'wheelModal',
                            emitEvent: 'open-wheel'
                        },
                        {
                            id: 'coupon',
                            method: 'COUPON',
                            type: 'button',
                            titleKey: 'app.home.coupon',
                            icon: '/assets/kimberbet/images/icon/icon-coupon.webp',
                            targetModalId: 'couponModal',
                            emitEvent: 'open-coupon'
                        },
                    ];

                    const ext = Array.isArray(window.__EVENT_TABS__) ? window.__EVENT_TABS__ : [];

                    // กัน id ซ้ำ: เอา ext มาเติมท้ายแต่ไม่ทับของเดิม
                    const existed = new Set(base.map(t => t.id));
                    ext.forEach(t => {
                        if (t && t.id && !existed.has(t.id)) base.push(t);
                    });

                    return base;
                },
                getBonusByKey(key) {
                    return this.tabs.find(t => t.method === key || t.key === key || t.id === key);
                },
                openModalById(id) {
                    const target = document.getElementById(id);
                    if (!target) return false;
                    const m = bootstrap.Modal.getOrCreateInstance(target);
                    m.show();
                    return true;
                },
                hideSelf() {
                    const el = this.$refs.eventModal;
                    const inst = el ? bootstrap.Modal.getInstance(el) : null;
                    if (inst) inst.hide();
                },
                getBonus(tabOrKey) {
                    const tab = typeof tabOrKey === 'string' ? this.getBonusByKey(tabOrKey) : tabOrKey;
                    if (!tab) {
                        console.warn('Tab not found for:', tabOrKey);
                        return;
                    }

                    // ซ่อนตัวเองก่อน แล้วพยายามเปิดปลายทาง
                    this.hideSelf();

                    // 1) ถ้ามี targetModalId และเจอ element → เปิดทันที
                    if (tab.type === 'button' && tab.targetModalId) {
                        const opened = this.openModalById(tab.targetModalId);
                        if (opened) return;
                    }

                    // 2) ถ้าไม่เจอโมดัลปลายทาง → fallback เป็น event เดิม (ไม่ทำลาย interface เก่า)
                    if (tab.emitEvent) {
                        this.$emit(tab.emitEvent);
                        return;
                    }

                    // 3) fallback สุดท้ายจาก method (เข้ากับของเดิม)
                    switch (tab.method) {
                        case 'WHEEL':
                            this.$emit('open-wheel');
                            break;
                        case 'COUPON':
                            this.$emit('open-coupon');
                            break;
                        default:
                            console.warn('No handler for method:', tab.method);
                    }
                },
                async showModal() {
                    await this.$nextTick();
                    const el = this.$refs.eventModal;
                    if (!el) {
                        console.warn('event modal element not found');
                        return;
                    }
                    const modal = bootstrap.Modal.getOrCreateInstance(el);
                    modal.show();
                },
            },
        });
    </script>
@endpush