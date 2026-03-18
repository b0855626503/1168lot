<script type="text/x-template" id="promotion-page-template">
    <div class="sub-page sub-footer" style="min-height:100vh;">
        <div class="container promotion-member-container" style="max-width:720px;">

            <div v-for="item in list" :key="item.id"
                 class="promotion-item card mb-3 mx-auto border-none shadow rounded overflow-hidden bg-transaparent"
                 style="width:720px;max-width:95%;">
                <img :src="item.filepic" class="w-100 d-block mx-auto" alt="promotion">
                <div class="card-body bg-dark p-0">
                    <div class="card bg-dark-2">
                        <div class="card-body">
                            <h3 v-text="item.name_th"></h3>
                            <hr class="m-0">
                            <div v-html="item.content"></div>
                        </div>

                        <!-- ใช้ item.id ตามที่กำหนด -->
                        <div class="card-footer text-center" v-if="showClaim(item)">
                            <button class="btn btn-success" type="button"
                                    :disabled="isSubmitting"
                                    @click="openProConfirm(item)">
                                <span>{{ __('app.promotion.choose') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Bootstrap 5 modal (ไม่ใช้ Bootstrap-Vue) -->
        <div class="modal fade"
             id="modal-confirm-pro"
             ref="confirmPro"
             data-bs-backdrop="static"
             data-bs-keyboard="false"
             tabindex="-1"
             aria-labelledby="confirmProLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content bg-dark-2">
                    <div class="modal-header">
                        <h5 class="modal-title mb-0" id="confirmProLabel" v-text="modalProName"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        ต้องการกดรับโปรนี้หรือไม่?
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" :disabled="modalLoading" data-bs-dismiss="modal">
                            ยกเลิก
                        </button>
                        <button type="button" class="btn btn-success" @click="confirmProSubmit" :disabled="modalLoading">
                            <span v-if="!modalLoading">ยืนยัน</span>
                            <span v-else class="d-inline-flex align-items-center">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                รอสักครู่...
              </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- /modal -->
    </div>
</script>

@push('components')
    <script type="module">
        Vue.component('promotion-page', {
            template: '#promotion-page-template',

            props: {
                promotions:  { type: Array,  default: () => [] },
                proContents: { type: Array,  default: () => [] },
                currentTab:  { type: String, default: 'promotions' } // 'promotions' | 'proContents' | 'all'
            },

            data() {
                return {
                    modalProCode: null,
                    modalProName: null,
                    modalLoading: false,
                    isSubmitting: false,
                    _confirmModal: null  // เก็บ instance ของ bootstrap.Modal
                };
            },

            computed: {
                list() {
                    if (this.currentTab === 'proContents') return this.proContents;
                    if (this.currentTab === 'all')        return [...this.proContents, ...this.promotions];
                    return this.promotions;
                }
            },

            mounted() {
                // ต้องมี Bootstrap 5 JS bundle โหลดอยู่ให้ window.bootstrap ใช้งานได้
                if (!window.bootstrap || !window.bootstrap.Modal) {
                    console.error('Bootstrap 5 JS bundle ไม่พร้อม (window.bootstrap.Modal ไม่พบ)');
                    return;
                }
                this._confirmModal = window.bootstrap.Modal.getOrCreateInstance(this.$refs.confirmPro, {
                    backdrop: 'static',
                    keyboard: false,
                    focus: true
                });
            },

            methods: {
                showClaim(item) {
                    // ใช้ item.id ตามที่คุณยืนยัน
                    const hiddenIds = ['pro_cashback','pro_ic','pro_spin','pro_faststart'];
                    return !hiddenIds.includes(item.id);
                },

                openProConfirm(item) {
                    if (this.isSubmitting) return;
                    this.modalProCode = item.code;       // << ส่ง id เป็น promotion ตามสเปคเดิม
                    this.modalProName = item.name_th;

                    if (this._confirmModal) {
                        this._confirmModal.show();
                    } else {
                        // fallback (ถ้า bootstrap.Modal ยังไม่พร้อม)
                        if (confirm(`ยืนยันรับโปร: ${item.name_th} ?`)) {
                            this.confirmProSubmit();
                        }
                    }
                },

                async confirmProSubmit() {
                    if (!this.modalProCode || this.modalLoading) return;

                    this.modalLoading = true;
                    this.isSubmitting = true;

                    try {
                        // const t0 = performance.now();
                        const resp = await fetch("{{ route('customer.promotion.select') }}", { // ✅ JSON endpoint
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name=csrf-token]').content,
                                "Accept": "application/json"
                            },
                            body: JSON.stringify({ promotion: this.modalProCode })
                        });
                        // const t1 = performance.now();        // เวลาไป-กลับเครือข่าย (รวม TTFB)
                        // const data1 = await resp.json();
                        // const t2 = performance.now();
                        // console.log('[select-pro]', {
                        //     total_ms: (t2-t0).toFixed(1),
                        //     network_ms: (t1-t0).toFixed(1),
                        //     parse_ms: (t2-t1).toFixed(1),
                        //     server_timing: resp.headers.get('server-timing') // ถ้าตั้งใน backend แล้ว
                        // });

                        // ถ้า server ตอบ non-2xx ก็ยังพยายาม parse เพื่อโชว์ error ได้
                        const data = await resp.json().catch(() => ({}));

                        const ok = (data && (data.success === true || data.ok === true));

                        if (ok) {
                            this._confirmModal && this._confirmModal.hide();
                            const msg = data.message || "รับโปรสำเร็จ";
                            window.Toast?.fire?.({ icon: 'success', title: msg }) ?? alert(msg);
                            // location.reload();
                        } else {
                            const msg = (data && data.message) || "รับโปรไม่ได้";
                            window.Toast?.fire?.({ icon: 'info', title: msg }) ?? alert(msg);
                        }

                    } catch (e) {
                        const msg = e?.message || "ระบบขัดข้อง ลองใหม่ภายหลัง";
                        window.Toast?.fire?.({ icon: 'error', title: msg }) ?? alert(msg);
                    } finally {
                        this.modalLoading = false;
                        this.isSubmitting = false;
                    }
                },


                intToMoney(n) {
                    return parseFloat(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
                }
            }
        });
    </script>
@endpush
