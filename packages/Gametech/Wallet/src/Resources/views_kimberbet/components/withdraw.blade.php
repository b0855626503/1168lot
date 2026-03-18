<script type="text/x-template" id="withdraw-modal-template">
    <div class="modal modal-custom fade" id="withdrawModal" data-bs-backdrop="static" data-bs-keyboard="false"
         tabindex="-1" aria-labelledby="withdrawLabel" aria-hidden="true" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content bg-dark-2" style="min-height: 60vh;">
                <div class="modal-header">
                    <h5 class="modal-title text-center mb-0 text-dark lh-1" id="withdrawLabel">@{{
                        trans('app.home.withdraw') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-1">
                    <div class="fs-6 text-content pt-2 w-100 text-center pt-4">
                        @{{ trans('app.home.withdraw_credit') }} :
                        <span class="fw-bolder text-custom-primary" v-text="member.balance">0.00</span>
                    </div>
                    <hr class="w-75 mx-auto my-1">

                    <div class="fs-6 text-content w-100 text-center">
                        @{{ trans('app.home.withdraw_max_day') }} :
                        <span class="fw-bolder text-danger" v-text="member.maxwithdraw_day">0</span>
                        - @{{ trans('app.home.withdraw_sum_day') }} :
                        <span class="fw-bolder text-danger" v-text="member.withdraw_sum_today">0</span>
                    </div>
                    <hr class="w-75 mx-auto my-1">

                    <div class="fs-6 text-content w-100 text-center">
                        @{{ trans('app.home.withdraw_remain_day') }} :
                        <span class="fw-bolder text-danger" v-text="member.withdraw_remain_today">0</span>
                    </div>

                    <div v-if="member.getpro">
                        <hr class="w-75 mx-auto my-1">
                        <div class="fs-6 text-content w-100 text-center">
                            @{{ trans('app.promotion.me') }} :
                            <span class="fw-bolder text-danger" v-text="member.pro_name"></span><br>
                            @{{ trans('app.home.withdraw_turn') }} :
                            <span class="fw-bolder text-danger" v-text="member.amount_balance"></span><br>
                            @{{ trans('app.home.limit_withdraw') }} :
                            <span class="fw-bolder text-custom-primary" v-text="member.withdraw_limit_amount"></span>
                        </div>
                    </div>

                    <form @submit.prevent="submitWithdraw">
                        <div class="theme-form mt-4">
                            <div class="input-group input-group-lg mx-auto custom-style-input" style="max-width: 20em;">
                <span class="input-group-text">
                  <img src="/assets/kimberbet/images/icon/coin.svg" width="40" height="40" alt="">
                </span>
                                <input
                                        v-model="withdrawAmount"
                                        step="1"
                                        id="withdraw"
                                        type="number"
                                        autocomplete="off"
                                        placeholder="@{{ trans('app.home.withdraw_amount') }}"
                                        :min="member.withdraw_min"
                                        :max="member.withdraw_max"
                                        required
                                        class="form-control"
                                        :readonly="member.pro"
                                        @keydown="preventDot"
                                >
                            </div>
                        </div>

                        <div class="text-center mt-3 pb-1">
                            <button type="submit"
                                    class="btn btn-primary btn-custom-primary w-100 rounded-pill"
                                    :disabled="isSubmitting || !withdrawStatus"
                                    style="max-width: 20em;">
                                @{{ trans('app.home.withdraw') }}
                            </button>
                        </div>
                    </form>

                    <!-- ปุ่มเปิดโมดัลอัปโหลด QR/สลิป -->
                    <div class="text-center mt-2" v-if="showUploadButton">
                        <button type="button"
                                class="btn btn-qr-gold  rounded-pill"
                                @click="openQrModal">
                            @{{ trans('app.qrscan.upload') }}
                        </button>
                    </div>
                </div>

                <div class="modal-footer p-1 w-100 text-center flex-column">
                    <div class="small text-danger fw-light w-100">
                        @{{ trans('app.home.withdraw_min') }} <span v-text="member.withdraw_min"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<!-- โมดัลใหม่: มี Dropzone + รูปวิธีทำ -->
<script type="text/x-template" id="withdraw-qr-modal-template">
    <div class="modal modal-custom fade" id="withdrawQrModal" data-bs-backdrop="static" data-bs-keyboard="false"
         tabindex="-1" aria-labelledby="withdrawQrLabel" aria-hidden="true" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content bg-dark-2">
                <div class="modal-header">
                    <h5 class="modal-title text-center mb-0 text-dark lh-1" id="withdrawQrLabel">
                        @{{ trans('app.qrscan.upload') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-2">
                    <!-- Dropzone -->
                    <form id="withdraw-qr-dropzone"
                          class="dropzone rounded-3 border border-secondary-subtle"
                          enctype="multipart/form-data">
                        <div class="dz-message needsclick">
                            <p class="mb-0">@{{ trans('app.qrscan.upload') }}</p>
                            <p class="small text-black-50 mb-0">@{{ trans('app.qrscan.filesize') }}</p>
                        </div>
                    </form>

                    <!-- สเต็ป -->
{{--                    <div class="text-center mt-3">--}}
{{--                        <img src="/images/qr/step2.jpg" class="img-fluid mb-2" loading="lazy" alt="วิธีส่ง True QR">--}}
{{--                        <img src="/images/qr/step1.jpg" class="img-fluid" loading="lazy" alt="วิธีส่ง True QR">--}}
{{--                    </div>--}}
                </div>

                <div class="modal-footer p-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        @{{ trans('app.home.back') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</script>

@push('styles')
    <style>
        /* กระชับ Dropzone ในโมดัลใหม่ให้สูงไม่เกิน 100px */
        #withdraw-qr-dropzone.dropzone {
            min-height: 0 !important;
            max-height: 150px !important;
            overflow: auto !important;
            padding: 6px !important;
            background: #0f0f0f;
        }

        #withdraw-qr-dropzone .dz-message {
            min-height: 0 !important;
            padding: 0 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            line-height: 1.2;
        }

        #withdraw-qr-dropzone .dz-preview {
            margin: 2px !important;
            padding: 0 !important;
        }

        #withdraw-qr-dropzone .dz-preview .dz-image {
            width: 72px !important;
            height: 72px !important;
            border-radius: 6px;
            overflow: hidden;
        }

        #withdraw-qr-dropzone.dz-started .dz-message {
            display: none !important;
        }

    </style>
@endpush

@push('components')
    <script type="module">
        /** โมดัลถอนเงิน (ปรับให้เปิดโมดัลอัปโหลด) */
        Vue.component('withdraw-modal', {
            template: '#withdraw-modal-template',
            data() {
                return {
                    member: {
                        balance: 0, maxwithdraw_day: 0, withdraw_sum_today: 0,
                        withdraw_remain_today: 0, withdraw_min: 0, withdraw_max: 10000,
                        pro: false, pro_name: '', amount_balance: 0, withdraw_limit_amount: 0,
                        pic_id: '',
                    },
                    withdrawStatus: true,
                    isLoading: false,
                    withdrawAmount: 0,
                    isSubmitting: false,
                };
            },
            methods: {
                preventDot(e) {
                    if (['.', ',', 'e'].includes(e.key)) e.preventDefault();
                },
                async loadMemberData() {
                    this.isLoading = true;
                    try {
                        const res = await axios.get("{{ route('customer.home.credit') }}", {
                            headers: {'Cache-Control': 'no-store'}, timeout: 10000,
                        });
                        if (res.data?.success) {
                            this.member = res.data.profile;
                            if (this.member.pro) this.withdrawAmount = this.member.balance;
                            this.withdrawStatus = res.data.withdraw;
                        }
                    } finally {
                        this.isLoading = false;
                    }
                },
                showModal() {
                    this.withdrawAmount = 0;
                    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('withdrawModal'));
                    this.$nextTick(async () => {
                        await this.loadMemberData();
                        modal.show();
                    });
                },
                async submitWithdraw() {
                    try {
                        this.isSubmitting = true;
                        const min = +this.member.withdraw_min, max = +this.member.withdraw_max,
                            amt = +this.withdrawAmount;
                        if (!Number.isFinite(amt) || amt < min || amt > max) {
                            window.Toast?.fire?.({icon: 'info', title: this.trans('app.withdraw.wrong_amount')});
                            return;
                        }
                        const res = await axios.post("{{ route('customer.withdraw.storeapi') }}", {amount: amt}, {timeout: 10000});
                        if (res.data?.success) {
                            window.Toast?.fire?.({icon: 'success', title: res.data.message});
                            const comp = this.$root?.$refs?.memberComponent;
                            if (comp?.loadCredit) comp.loadCredit();
                            bootstrap.Modal.getInstance(document.getElementById('withdrawModal'))?.hide();
                        } else {
                            window.Toast?.fire?.({
                                icon: 'info',
                                title: res.data?.message || this.trans('app.status.error')
                            });
                        }
                    } catch (err) {
                        window.Toast?.fire?.({
                            icon: 'info',
                            title: err?.response?.data?.message || this.trans('app.status.error')
                        });
                    } finally {
                        this.isSubmitting = false;
                    }
                },
                openQrModal() {
                    // เปิดโมดัลใหม่ผ่าน ref บน root
                    this.$root?.$refs?.withdrawQrModalComponent?.open();
                }
            },
            mounted() {
                // เมื่ออัปโหลดสำเร็จจากโมดัลใหม่ อัปเดตสถานะในนี้
                this.$root.$on?.('qr:uploaded', (payload) => {
                    this.member.pic_id = payload?.id || '1';
                });

                const applyProfile = ({profile}) => {
                    const modalEl = document.getElementById('withdrawModal');
                    const isShown = modalEl?.classList?.contains('show');
                    if (!isShown || !profile) return;

                    // merge เฉพาะ profile
                    this.member = {...this.member, ...profile};

                    // ถ้าต้องการ sync ช่องถอนเมื่อเป็นโปรฯ และไม่ได้พิมพ์อยู่
                    const isTyping = document.activeElement?.id === 'withdraw';
                    if (this.member.pro && !isTyping) {
                        this.withdrawAmount = this.member.balance;
                    }

                };

                this.$root.$on?.('credit:update', applyProfile);

                // DOM event (สำรอง)
                this._onDomCreditUpdate = (e) => applyProfile(e.detail || {});
                window.addEventListener('credit:update', this._onDomCreditUpdate);
            },
            beforeDestroy() {
                this.$root.$off?.('credit:update');
                if (this._onDomCreditUpdate) {
                    window.removeEventListener('credit:update', this._onDomCreditUpdate);
                }
            },
            computed: {
                showUploadButton() {
                    const code = Number(this.member?.bank_code);
                    const hasPic = !!this.member?.pic_id; // true ถ้ามีค่าไม่ว่าง
                    return !hasPic && code === 18;
                }
            }
        });

        /** โมดัลอัปโหลดสลิป/QR (ใหม่) */
        Vue.component('withdraw-qr-modal', {
            template: '#withdraw-qr-modal-template',
            data() {
                return {dz: null, uploading: false, _inited: false};
            },
            methods: {
                open() {
                    const el = document.getElementById('withdrawQrModal');
                    const modal = bootstrap.Modal.getOrCreateInstance(el);
                    modal.show();
                    this.$nextTick(() => this.initDropzone());
                },
                close() {
                    const el = document.getElementById('withdrawQrModal');
                    bootstrap.Modal.getInstance(el)?.hide();
                },
                initDropzone() {
                    if (this._inited || !window.Dropzone) return;
                    if (window.Dropzone?.autoDiscover) window.Dropzone.autoDiscover = false;

                    const el = document.getElementById('withdraw-qr-dropzone');
                    if (!el) return;

                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    this.dz = new Dropzone(el, {
                        url: "{{ route('customer.qr.upload') }}",
                        method: 'post',
                        paramName: 'file',
                        maxFiles: 1,
                        maxFilesize: 5,
                        acceptedFiles: 'image/*',
                        addRemoveLinks: true,
                        dictDefaultMessage: 'แตะหรือลากไฟล์มาวาง',
                        headers: {'X-CSRF-TOKEN': csrf},
                        timeout: 60_000,
                        autoProcessQueue: true,
                        createImageThumbnails: true,
                        thumbnailWidth: 72,
                        thumbnailHeight: 72,
                    });

                    // บีบพรีวิว
                    el.style.setProperty('max-height', '150px', 'important');
                    el.style.setProperty('min-height', '0', 'important');
                    el.style.setProperty('overflow', 'auto', 'important');
                    el.style.setProperty('padding', '6px', 'important');

                    this.dz.on('addedfile', () => {
                        if (this.dz.files.length > 1) this.dz.removeFile(this.dz.files[0]);
                        el.querySelectorAll('.dz-preview .dz-image').forEach(img => {
                            img.style.setProperty('width', '72px', 'important');
                            img.style.setProperty('height', '72px', 'important');
                        });
                    });

                    this.dz.on('sending', () => {
                        this.uploading = true;
                    });

                    this.dz.on('success', (file, response) => {
                        this.uploading = false;
                        if (response?.success) {
                            window.Toast?.fire?.({icon: 'success', title: response.message || "อัปโหลดสำเร็จ"});
                            // แจ้งทั้งระบบว่ามีรูปแล้ว
                            this.$root.$emit('qr:uploaded', {id: response.id || 1, url: response.img_url});
                            // ปิดโมดัล (หรือจะคงไว้ให้ดูรูปก็ได้)
                            // this.close();
                        } else {
                            window.Toast?.fire?.({icon: 'info', title: response?.message || "อัปโหลดไม่สำเร็จ"});
                        }
                    });

                    this.dz.on('error', (file, errorMessage, xhr) => {
                        this.uploading = false;
                        const msg = (xhr?.response?.message) || (typeof errorMessage === 'string' ? errorMessage : this.trans('app.status.error'));
                        window.Toast?.fire?.({icon: 'error', title: msg});
                    });

                    this.dz.on('maxfilesexceeded', (file) => {
                        this.dz.removeAllFiles();
                        this.dz.addFile(file);
                    });

                    this._inited = true;
                }
            }
        });
    </script>
@endpush
