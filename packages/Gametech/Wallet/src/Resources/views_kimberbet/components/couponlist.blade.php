<script type="text/x-template" id="couponlist-modal-template">
    <div class="modal modal-custom fade"
         id="couponListModal"
         ref="couponListModal"
         data-bs-backdrop="static"
         data-bs-keyboard="false"
         tabindex="-1"
         aria-labelledby="couponlistLabel"
         aria-hidden="true"
         data-bs-focus="false">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content bg-dark-2">
                <div class="modal-header">
                    <h5 class="modal-title text-center mb-0 text-dark lh-1"
                        id="couponlistLabel"
                        v-text="trans('app.bonus.bonus_list')">
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-2">
                    <div class="container-fluid">

                        <!-- Loading -->
                        <div v-if="loading" class="py-2">
                            <div class="card bg-dark-2 border-0 mb-2 shadow-sm" v-for="n in 3" :key="'s'+n">
                                <div class="card-body">
                                    <div class="placeholder-glow">
                                        <span class="placeholder col-4 rounded-pill"></span>
                                    </div>
                                    <div class="placeholder-glow mt-2">
                                        <span class="placeholder col-8"></span>
                                        <span class="placeholder col-6 mt-1"></span>
                                        <span class="placeholder col-5 mt-1"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty -->
                        <div v-else-if="!bonuses.length" class="text-center py-3">
                            <div class="card bg-dark-2 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted" v-text="trans('app.coupon.notfound')"></div>
                                </div>
                            </div>
                        </div>

                        <!-- === แทนที่บล็อก list เดิมใน couponlist-modal ด้วยบล็อกนี้ === -->
                        <!-- === แทนที่บล็อก list ใน couponlist-modal ด้วยบล็อกนี้ (พร้อม i18n) === -->
                        <div v-else class="container-fluid">
                            <div class="row g-2 mt-1">
                                <div class="card bg-dark bank-deposit-item mb-1"
                                     v-for="b in bonuses"
                                     :key="b.code">
                                    <div class="card-body bank-item-container container p-3">
                                        <div class="bank-info d-flex align-items-center">

                                            <!-- ไอคอนซ้าย -->
                                            <div class="bank-icon d-flex align-items-center">
                                                <img :src="iconFor(b)" width="50" height="50" style="object-fit: contain;" alt="">
                                            </div>

                                            <!-- รายละเอียดกลาง -->
                                            <div class="bank-detail ps-4 col text-white">
                                                <!-- หัวเรื่อง: แสดงเฉพาะชนิดโบนัส -->
{{--                                                <div class="text-start fw-semibold" v-text="typeLabel(b.type)"></div>--}}

                                                <!-- เนื้อหา -->
                                                <div class="mt-1">
                                                    <!-- จำนวน {value} เครดิต -->
                                                    <div class="text-start lh-1">
                                                        <span class="text-white-75" v-text="trans('app.coupon.amount')"></span>
                                                        <span class="text-primary fw-semibold"> @{{ currency(b.value) }} </span>
                                                        <span class="text-white-75" v-text="trans('app.coupon.credit')"></span>
                                                    </div>

                                                    <!-- ยอดเทิร์น N (เท่า) -->
                                                    <div class="text-start lh-1 mt-1">
                                                        <span class="text-white-75" v-text="trans('app.coupon.turn')"></span>
                                                        <span class="text-white"> @{{ number(b.turnpro) }} </span>
                                                        <span class="text-white-75" v-text="trans('app.coupon.rate')"></span>
                                                    </div>

                                                    <!-- อั้นถอน N (เท่า) -->
                                                    <div class="text-start lh-1 mt-1">
                                                        <span class="text-white-75" v-text="trans('app.coupon.limit')"></span>
                                                        <span class="text-white"> @{{ number(b.limit) }} </span>
                                                        <span class="text-white-75" v-text="trans('app.coupon.rate')"></span>
                                                    </div>

                                                    <!-- รับได้ถึงวันที่ ... -->
                                                    <div class="text-start lh-1 mt-1" v-if="b.date_expire">
                                                        <span class="text-white-75" v-text="trans('app.coupon.canget')"></span>
                                                        <span class="text-warning"> @{{ formatDate(b.date_expire) }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ปุ่มขวาสุด -->
                                            <div class="btn-copy-bank d-flex align-items-center">
                                                <button class="py-2 shadow rounded-pill btn btn-warning text-dark fw-semibold d-flex align-items-center"
                                                        style="min-width: unset;"
                                                        @click="apply(b)"
                                                        :disabled="applying === b.code">
                                                    <i class="bi bi-gift me-2"></i>
                                                    <span v-text="trans('app.coupon.get')"></span>
                                                </button>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- Error -->
                        <div v-if="error && !loading" class="alert alert-danger my-2" role="alert">
                            @{{ error }}
                        </div>

                    </div>
                </div>

                <div class="modal-footer p-2">
                    <button type="button" class="btn btn-secondary" @click="goBack" aria-label="Back">
                        @{{ trans('app.home.back') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</script>

@push('components')
    <script type="module">
        Vue.component('couponlist-modal', {
            template: '#couponlist-modal-template',
            data() {
                return {
                    loading: false,
                    error: null,
                    bonuses: [],
                    applying: null,
                };
            },
            methods: {
                iconFor(b) {
                    // ปรับพาธไอคอนให้เข้าธีม/แบรนด์ของโบ๊ท
                    // เช่น ใช้ไอคอนเดียวกับปุ่มส้มที่หน้าอื่นเพื่อความสอดคล้อง
                    return b.type === 'freecredit'
                        ? '/assets/kimberbet/images/icon/icon-bonus.webp'
                        : '/assets/kimberbet/images/icon/icon-coupon.webp';
                },
                number(v)   { const n = Number(v); return Number.isFinite(n) ? n.toLocaleString() : v; },
                currency(v) { const n = Number(v); return Number.isFinite(n) ? n.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) : v; },
                formatDate(d){ return d; }, // ถ้าใช้ dayjs/moment อยู่แล้ว ค่อยเปลี่ยนมา format ตาม locale ได้
                typeLabel(t){ return t === 'freecredit' ? this.trans('app.coupon.freecredit') : this.trans('app.coupon.credit'); },

                // เปิดโมดัลสาธารณะ
                open() {
                    const el = document.getElementById('couponListModal');
                    const modal = bootstrap.Modal.getOrCreateInstance(el);
                    modal.show();
                    this.fetchBonusList();
                },
                // เปิดผ่าน ref ภายใน
                show() {
                    const el = this.$refs.couponListModal;
                    if (!el) return;
                    const m = bootstrap.Modal.getOrCreateInstance(el);
                    m.show();
                    this.fetchBonusList();
                },
                close() {
                    const el = this.$refs.couponListModal;
                    const m = bootstrap.Modal.getInstance(el) || bootstrap.Modal.getOrCreateInstance(el);
                    m.hide();
                },
                goBack() {
                    this.close();
                    this.$nextTick(() => this.$root?.$refs?.couponModalComponent?.show?.());
                },

                async fetchBonusList() {
                    this.loading = true;
                    this.error = null;
                    this.bonuses = [];
                    try {

                        const url = '{{ route('customer.coupon.bonuslist') }}';
                        const axios$ = (window.axios || window.Axios || axios);
                        const resp = await axios$.get(url);
                        const rows = resp?.data?.data || [];
                        this.bonuses = Array.isArray(rows) ? rows : [];
                    } catch (e) {
                        this.error = this.trans('app.status.fail');
                    } finally {
                        this.loading = false;
                    }
                },

                async apply(bonus) {
                    if (this.applying) return;
                    this.applying = bonus.code;
                    try {
                        const url = '{{ route('customer.coupon.getbonus') }}';
                        const axios$ = (window.axios || window.Axios || axios);
                        const resp = await axios$.post(url, { id : String(bonus.code) });
                        const data = resp?.data || {};
                        if (data.success) {
                            Swal.fire(this.trans('app.bonus.success'), data.message || '', 'success');
                            // รีโหลดรายการหลังรับสำเร็จ (ถ้าต้องการ)
                            const comp = this.$root?.$refs?.memberComponent;
                            if (comp?.loadCredit) comp.loadCredit();

                            this.fetchBonusList();
                        } else {
                            Swal.fire(this.trans('app.bonus.fail'), data.message || '', 'error');
                        }
                    } catch (e) {
                        Swal.fire(this.trans('app.status.fail'), this.trans('app.status.tryagain') || 'Try again', 'error');
                    } finally {
                        // ปลดล็อกปุ่ม
                        setTimeout(() => { this.applying = null; }, 800);
                    }
                },
            }
        });
    </script>
@endpush
