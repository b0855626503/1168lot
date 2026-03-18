<script type="text/x-template" id="coupon-modal-template">
    <div class="modal modal-custom fade"
         id="couponModal"
         ref="couponModal"
         data-bs-backdrop="static"
         data-bs-keyboard="false"
         tabindex="-1"
         aria-labelledby="couponLabel"
         aria-hidden="true"
         data-bs-focus="false">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content bg-dark-2">
                <div class="modal-header">
                    <h5 class="modal-title text-center mb-0 text-dark lh-1"
                        id="couponLabel"
                        v-text="trans('app.home.coupon')">
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-1">
                    <div class="container-fluid">

                        <!-- ★ เพิ่ม id="frmcoupon" และ name="coupon" -->
                        <form id="frmcoupon" @submit.prevent="submitCoupon">
                            <div class="theme-form mt-4">
                                <div class="input-group input-group-lg mx-auto custom-style-input"
                                     style="max-width: 20em;">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-ticket"></i>
                                    </span>
                                    <input
                                            v-model="coupon"
                                            id="coupon"
                                            name="coupon"
                                            type="text"
                                            autocomplete="off"
                                            required
                                            class="form-control"
                                            :disabled="isSubmitting"
                                            maxlength="14"
                                            placeholder="XXXX-XXXX-XXXX"
                                            @input="formatCouponInput"
                                            @paste.prevent="onPasteCoupon"
                                            @drop.prevent
                                    >
                                </div>
                            </div>

                            <div class="text-center mt-3 pb-1">
                                <button type="submit"
                                        class="btn btn-primary btn-custom-primary w-100 rounded-pill"
                                        :disabled="isSubmitting"
                                        style="max-width: 20em;">
                                    @{{ trans('app.status.ok') }}
                                </button>
                            </div>
                        </form>

                    </div>
                </div> <!-- /modal-body -->

                <div class="modal-footer p-2">
                    <button type="button" class="btn btn-success" @click="openCouponListModal" aria-label="Back">
                        @{{ trans('app.bonus.bonus_list') }}
                    </button>
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
        Vue.component('coupon-modal', {
            template: '#coupon-modal-template',
            data() {
                return {
                    isShown: false,
                    loading: false,
                    coupon: '',
                    isSubmitting: false
                };
            },
            methods: {
                waitHiddenOnce(el) {
                    return new Promise(resolve => {
                        const handler = () => {
                            el.removeEventListener('hidden.bs.modal', handler);
                            resolve();
                        };
                        el.addEventListener('hidden.bs.modal', handler, { once: true });
                    });
                },
                async openCouponListModal() {
                    const el = this.$refs.couponModal;
                    const inst = bootstrap.Modal.getInstance(el);
                    if (inst) {
                        const hidden = this.waitHiddenOnce(el);
                        inst.hide();
                        await hidden;
                    }
                    this.$root?.$refs?.couponListModalComponent?.open?.();
                },
                // เปิดโมดัลจากที่อื่น
                show() {
                    const el = this.$refs.couponModal;
                    if (!el) return;
                    const m = bootstrap.Modal.getOrCreateInstance(el);
                    m.show();
                },
                // ปิดโมดัล
                close() {
                    const el = this.$refs.couponModal;
                    const m = bootstrap.Modal.getInstance(el) || bootstrap.Modal.getOrCreateInstance(el);
                    m.hide();
                },
                // ย้อนกลับไป event-modal
                goBack() {
                    this.close();
                    this.$nextTick(() => this.$root?.$refs?.eventModalComponent?.showModal?.());
                },
                formatCouponInput(e) {
                    this.coupon = this.formatCouponValue(e.target.value);
                },

                // ★ ฟังก์ชันใช้ซ้ำได้ทั้ง input/paste
                formatCouponValue(value) {
                    let raw = (value || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
                    raw = raw.slice(0, 12); // จำกัด 12 ตัวจริง
                    const groups = raw.match(/.{1,4}/g) || [];
                    return groups.join('-');
                },

                // ★ กัน paste
                async onPasteCoupon(e) {
                    e.preventDefault();
                    let pasted = '';
                    if (e.clipboardData && e.clipboardData.getData) {
                        pasted = e.clipboardData.getData('text');
                    } else if (window.clipboardData && window.clipboardData.getData) {
                        pasted = window.clipboardData.getData('Text');
                    }
                    this.coupon = this.formatCouponValue(pasted);
                },
                // ★★ ส่งคูปองด้วย Axios (แทน jQuery submit)
                async submitCoupon() {
                    if (this.isSubmitting) return;
                    this.isSubmitting = true;


                    const url = '{{ route('customer.coupon.redeem') }}';
                    const axios$ = (window.axios || window.Axios || axios);

                    try {
                        const resp = await axios$.post(url, {coupon: this.coupon});
                        const data = resp?.data || {};

                        if (data.success) {
                            Swal.fire(
                                this.trans('app.bonus.success'),
                                data.message || '',
                                'success'
                            );
                        } else {
                            Swal.fire(
                                this.trans('app.bonus.fail'),
                                data.message || '',
                                'error'
                            );
                        }
                    } catch (err) {
                        // ข้อความ fallback กรณีไม่มีข้อความจากเซิร์ฟเวอร์
                        Swal.fire(
                            this.trans('app.status.fail'),
                            this.trans('app.status.tryagain') || this.trans('app.status.tryagain'),
                            'error'
                        );
                    } finally {
                        // คงพฤติกรรมเดิม: เคลียร์อินพุตและปลดล็อกปุ่มหลัง 2 วิ
                        setTimeout(() => {
                            this.coupon = '';
                            this.isSubmitting = false;
                        }, 2000);
                    }
                }
            }
        });
    </script>
@endpush
