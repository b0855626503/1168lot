<script type="text/x-template" id="member-credit-template">

    <div class="member__header credit-box container-fluid mt-1 px-2 shadow">
        <div class="credit-content p-3 my-0 mx-auto">
            <div class="slider position-absolute w-100 h-100 top-0 start-0"></div>

            <div class="row w-100 g-0">
                <div class="profile-badge-new position-relative">
                    <div class="txt-number-phone rounded-pill bg-white shadow d-flex align-items-center"
                         v-text="item?.user_name || ''"></div>
                    <div class="front shadow">
                        <img src="/assets/kimberbet/images/icon/profile_user.svg" style="width: 3em;" alt="">
                    </div>
                </div>
            </div>

            <div class="d-flex latest_update_data d_flex align_items_center pt-1">
                <span class="txt_update">@{{ trans('app.home.lastupdate') }} &nbsp;&nbsp;&nbsp;</span>
                <span class="latest_date" v-text="item?.lastupdate || ''"></span>
                <span class="latest_time"></span>
            </div>

            <div class="member__header--showmoney mt-2 g-0 align-items-center moneymb">

                <button class="reloadmoney" @click="loadCredit" :disabled="isSpinning"
                        :aria-busy="isSpinning" :title="isSpinning ? 'Loading...' : 'Reload'">
                    <i :class="['fas', 'fa-sync-alt', { 'fa-spin': isSpinning }]"></i>
                </button>

                <table>
                    <tr>
                        <td class="bordermbleft">
                            <!-- balance -->
                            <span v-if="itemLoaded" class="num" v-text="formatNumber(display.balance)"></span>
                            <span v-else class="skeleton skeleton-num"></span>
                            <li>@{{ trans('app.home.credit') }}</li>
                        </td>
                        <td>
                            <!-- diamond -->
                            <span v-if="itemLoaded" class="num" v-text="formatNumber(display.diamond,0)"></span>
                            <span v-else class="skeleton skeleton-num"></span>
                            <li>@{{ trans('app.profile.diamond') }}</li>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <!-- cashback -->
                            <span v-if="itemLoaded" class="num" v-text="formatNumber(display.cashback)"></span>
                            <span v-else class="skeleton skeleton-num"></span>
                            <li>@{{ trans('app.home.cashback') }}</li>
                        </td>
                        <td class="bordermbright">
                            <li>@{{ trans('app.home.suggest') }}
                                <credit-box>
                                    <span v-if="itemLoaded" class="num"
                                          v-text="formatNumber(display.downline, 0)"></span>
                                    <span v-else class="skeleton skeleton-num"></span>
                                </credit-box>
                            </li>
                            <li>@{{ trans('app.home.commission') }}
                                <credit-box>
                                    <span v-if="itemLoaded" class="num" v-text="formatNumber(display.faststart)"></span>
                                    <span v-else class="skeleton skeleton-num"></span>
                                </credit-box>
                            </li>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

</script>

@push('styles')
    @verbatim
        <style>
            /* ทำให้ตัวเลขนิ่ง ไม่เด้งความกว้างระหว่างโหลด/อัปเดต */
            .num {
                font-variant-numeric: tabular-nums;
                -webkit-font-feature-settings: "tnum";
                font-feature-settings: "tnum";
                display: inline-block;
                min-width: 8ch; /* กว้างพอสำหรับ 0,000.00 */
                text-align: right;
            }

            /* Skeleton shimmer สำหรับเลข */
            .skeleton {
                display: inline-block;
                border-radius: .25rem;
                background: linear-gradient(90deg, rgba(255, 255, 255, .08), rgba(255, 255, 255, .18), rgba(255, 255, 255, .08));
                background-size: 200% 100%;
                animation: skeleton-shimmer 1.2s infinite;
            }

            .skeleton-num {
                height: 1.2em;
                min-width: 8ch;
            }

            @keyframes skeleton-shimmer {
                0% {
                    background-position: 200% 0;
                }
                100% {
                    background-position: -200% 0;
                }
            }
        </style>
    @endverbatim
@endpush

@push('components')
    <script type="module">
        Vue.component('member-credit', {
            template: '#member-credit-template',
            data() {
                return {
                    item: null,                 // โปรไฟล์ล่าสุด (จริง)
                    display: {                  // ค่าที่โชว์ (สำหรับทำ tween)
                        balance: 0,
                        diamond: 0,
                        cashback: 0,
                        downline: 0,
                        faststart: 0,
                    },
                    isSpinning: false,
                    enableTween: true,          // ปิด/เปิดอนิเมชันนับวิ่ง
                    _rafIds: {},                // เก็บ requestAnimationFrame id ต่อฟิลด์
                    cacheKey: 'member:profile', // ใช้คีย์รวม หากกังวลข้ามผู้ใช้ ให้เติม user_id ภายหลัง
                };
            },
            computed: {
                itemLoaded() {
                    return !!this.item;
                }
            },
            mounted() {
                // 1) โหลดจากแคชให้เห็นค่าทันที
                this.readCache();

                // 2) แล้วค่อยรีเฟรชจากเซิร์ฟเวอร์
                this.loadCredit();
            },
            beforeDestroy() {
                // ยกเลิก RAF ที่ค้าง (กัน memory leak)
                for (const k in this._rafIds) {
                    if (this._rafIds[k]) cancelAnimationFrame(this._rafIds[k]);
                }
            },
            methods: {
                formatNumber(n, fractionDigits = 2) {
                    const num = Number(n ?? 0);
                    return num.toLocaleString(undefined, {
                        minimumFractionDigits: fractionDigits,
                        maximumFractionDigits: fractionDigits
                    });
                },

                readCache() {
                    try {
                        const raw = localStorage.getItem(this.cacheKey);
                        if (!raw) return;
                        const cached = JSON.parse(raw);

                        // ปรับ state จริง
                        this.item = cached;

                        // เซ็ตค่าที่โชว์ให้ตรงกับแคชทันที (ไม่เห็น 0)
                        this.display.balance = Number(cached?.balance ?? 0);
                        this.display.diamond = Number(cached?.diamond ?? 0);
                        this.display.cashback = Number(cached?.cashback ?? 0);
                        this.display.downline = Number(cached?.downline ?? 0);
                        this.display.faststart = Number(cached?.faststart ?? 0);
                    } catch (e) {
                        console.warn('readCache failed', e);
                    }
                },

                writeCache(profile) {
                    try {
                        localStorage.setItem(this.cacheKey, JSON.stringify(profile));
                    } catch (e) {
                        console.warn('writeCache failed', e);
                    }
                },

                async loadCredit(){
                    this.isSpinning = true;
                    try{
                        const res = await axios.get("{{ route('customer.home.credit') }}", {
                            headers: { 'Cache-Control': 'no-store' }, timeout: 10000
                        });
                        if (res.data?.success) {
                            const profile = res.data.profile ?? {};  // <-- กัน null
                            const prev = this.item ? { ...this.item } : {}; // กัน undefined
                            const deposit = res.data.deposit ?? {};
                            const promotion = res.data.promotion ?? {};

                            this.$root.$emit('credit:update', {
                                success: true,
                                profile  : profile,    // ส่วนอื่นอาจใช้
                                deposit  : deposit,    // ✅ deposit-modal ใช้
                                promotion: promotion,  // ✅ deposit-modal ใช้
                            });

                            this.item = profile;

                            this.writeCache(profile);

                            // ถ้า payload ว่าง ให้เซ็ตตรง ๆ แล้วจบ
                            const hasNumbers =
                                ['balance','diamond','cashback','downline','faststart']
                                    .some(k => Number.isFinite(Number(profile?.[k])));
                            if (!hasNumbers) {
                                // เซ็ตตรง ๆ กันพัง
                                this.$set(this.display, 'balance',   Number(profile?.balance   ?? 0));
                                this.$set(this.display, 'diamond',   Number(profile?.diamond   ?? 0));
                                this.$set(this.display, 'cashback',  Number(profile?.cashback  ?? 0));
                                this.$set(this.display, 'downline',  Number(profile?.downline  ?? 0));
                                this.$set(this.display, 'faststart', Number(profile?.faststart ?? 0));
                                return; // ยังไม่ tween
                            }

                            this.updateDisplayWithTween(prev, profile); // ค่อย tween
                        }
                    } catch (err){
                        console.error('loadCredit error', err);
                    } finally{
                        this.isSpinning = false;
                    }
                },


                updateDisplayWithTween(prev, next){
                    if (!next || typeof next !== 'object') return;

                    // กันกรณี display หายไป
                    if (!this.display || typeof this.display !== 'object') {
                        this.display = { balance:0, diamond:0, cashback:0, downline:0, faststart:0 };
                    }

                    const fields = [
                        ['balance',   2],
                        ['diamond',   2],
                        ['cashback',  2],
                        ['downline',  0],
                        ['faststart', 2],
                    ];

                    for (const [key] of fields){
                        // ให้มีค่าตั้งต้นเสมอ
                        if (typeof this.display[key] !== 'number') this.$set(this.display, key, 0);

                        const from = Number(this.display[key] ?? 0);
                        const to   = Number(next?.[key] ?? 0);

                        // ถ้าค่าไม่ใช่ตัวเลขที่ใช้ tween ได้ เซ็ตตรง ๆ ไปเลย
                        if (!Number.isFinite(from) || !Number.isFinite(to) || !this.enableTween) {
                            this.$set(this.display, key, Number.isFinite(to) ? to : 0);
                            continue;
                        }

                        this.tweenNumber(key, from, to, 420);
                    }
                },
                tweenNumber(field, from, to, durationMs = 420){
                    // safety net
                    if (!this._rafIds) this._rafIds = {};
                    if (!this.display || typeof this.display !== 'object') this.display = {};

                    from = Number.isFinite(from) ? from : 0;
                    to   = Number.isFinite(to)   ? to   : 0;

                    if (this._rafIds[field]) cancelAnimationFrame(this._rafIds[field]);

                    const start = performance.now();
                    const step = (now) => {
                        const t = Math.min((now - start) / durationMs, 1);
                        const eased = t * (2 - t); // easeOutQuad
                        this.$set(this.display, field, from + (to - from) * eased);
                        if (t < 1) {
                            this._rafIds[field] = requestAnimationFrame(step);
                        } else {
                            this.$set(this.display, field, to);
                            this._rafIds[field] = null;
                        }
                    };
                    this._rafIds[field] = requestAnimationFrame(step);
                },

            }
        });
    </script>
@endpush
