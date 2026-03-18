<script type="text/x-template" id="wheel-modal-template">
    <div class="modal modal-custom fade"
         id="wheelModal"
         ref="wheelModal"
         data-bs-backdrop="static"
         data-bs-keyboard="false"
         tabindex="-1"
         aria-labelledby="wheelLabel"
         aria-hidden="true"
         data-bs-focus="false">
        <div class="modal-dialog  modal-dialog-centered">
            <div class="modal-content bg-dark-2" style="min-height: 60vh;">
                <div class="modal-header">
                    <h5 class="modal-title text-center mb-0 text-dark lh-1"
                        id="wheelLabel"
                        v-text="trans('app.home.wheel')">
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-1">
                    <div class="container-fluid">

                        <!-- ใน wheel-modal-template -->
                        <keep-alive>
                            <div class="row" v-if="isShown">
                                <template v-if="system?.wheel === false">
                                    <div class="text-center text-light py-5">
                                        <i class="fa fa-exclamation-circle text-warning fs-2 mb-2"></i>
                                        <p>@{{ trans('app.home.no_list')  }}</p>
                                    </div>
                                </template>

                                <template v-else>
                                    <!-- เนื้อเดิมของวงล้อ -->
                                    <audio hidden preload="none"
                                           ref="audioTick"
                                           :src="`${$root.baseUrl}/storage/spin_img/tick.mp3`"
                                           type="audio/mp3"></audio>

                                    <div class="mx-auto text-center">
                                        <div id="canvasContainer" class="mt-1">
                                            <canvas ref="canvas"
                                                    id="spinwheel"
                                                    width="340"
                                                    height="390"
                                                    data-responsiveMinWidth="180"
                                                    data-responsiveScaleHeight="true"
                                                    data-responsiveMargin="0">
                                                <p style="color: white" align="center">
                                                    Sorry, your browser doesn't support canvas.
                                                </p>
                                            </canvas>
                                        </div>
                                    </div>

                                    <div class="mx-auto w-100 text-center">
                                        <p class="text-light m-0">
                                            @{{ trans('app.home.play_spin_1') }}  @{{ diamond }} @{{ trans('app.home.play_spin_2') }}
                                        </p>

                                        <button ref="btnSpin"
                                                id="btnspin"
                                                class="btn btn-success m-1"
                                                :disabled="Number(diamond) < 1 || wheelSpinning || isRequesting"
                                                @click="startSpin">
                                            @{{ trans('app.home.play_spin') }}
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </keep-alive>


                    </div>
                </div> <!-- /modal-body -->
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
    <!-- GSAP (ต้องการโดย Winwheel) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/latest/TweenMax.min.js"></script>
    <!-- Winwheel.js ควรมีโหลดไว้ก่อนใช้งาน (ถ้ายังไม่มี ให้ใส่สคริปต์นี้ใน layout รวม) -->
    {{-- <script src="/js/vendor/Winwheel.min.js"></script> --}}

    <script type="module">
        Vue.component('wheel-modal', {
            template: '#wheel-modal-template',
            data() {
                return {
                    isShown: false,
                    winwheel: null,
                    wheelSpinning: false,
                    isRequesting: false,
                    currentIdemKey: null,
                    diamond: 0,
                    segments: [],
                    format: {},
                    system: null, // ★ เพิ่ม
                    loading: false
                };
            },
            provide() { return { wheel: this }; },
            mounted() {
                const el = this.$refs.wheelModal;
                if (!el) return;

                el.addEventListener('shown.bs.modal', async () => {
                    this.isShown = true;
                    await this.$nextTick();
                    this.initSafe();
                });

                el.addEventListener('hidden.bs.modal', () => {
                    // ถ้าต้องการล้างทุกครั้ง: uncomment
                    // this.teardown();
                });
            },
            methods: {

                // ---------- Utils ----------
                genIdemKey() {
                    if (window.crypto?.randomUUID) return crypto.randomUUID();
                    // fallback minimal
                    const s4 = () => Math.floor((1 + Math.random()) * 0x10000).toString(16).slice(-4);
                    return `${Date.now().toString(16)}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
                },
                sleep(ms) {
                    return new Promise(resolve => setTimeout(resolve, ms));
                },

                // ---------- Init / Data ----------
                async initSafe() {
                    try {
                        if (!this.winwheel) {
                            await this.loadSpin();
                            this.createWheel();
                        } else {
                            await this.loadSpin(true); // refresh แค่ credit
                        }
                    } catch (e) {
                        console.warn('initSafe error:', e);
                    }
                },

                async loadSpin(onlyCredit = false) {
                    this.loading = true;
                    try {
                        const res = await axios.get("{{ route('customer.home.credit') }}", {
                            headers: { 'Cache-Control': 'no-store' },
                            timeout: 10000
                        });

                        if (res.data?.success) {
                            // ดึงค่า system ถ้ามีใน response
                            this.system = res.data?.system || {};

                            const d = res.data?.profile?.diamond;
                            this.diamond = Number(Array.isArray(d) ? d[0] : d ?? 0);

                            if (!onlyCredit) {
                                if (this.system?.wheel === false) {
                                    // ไม่สร้าง segments หรือวงล้อเลย
                                    this.segments = [];
                                    return;
                                }

                                const raw = res.data?.spin ?? [];
                                this.segments = raw.map(it => {
                                    const seg = {};
                                    if (it.image) seg.image = it.image;
                                    if (it.text) seg.text = String(it.text);
                                    return seg;
                                });
                            }
                        } else {
                            this.diamond = 0;
                            this.system = {};
                            if (!onlyCredit) this.segments = [];
                        }
                    } catch (e) {
                        this.diamond = 0;
                        this.system = {};
                        if (!onlyCredit) this.segments = [];
                    } finally {
                        this.loading = false;
                    }
                },

                ensureWinwheelAvailable() {
                    if (typeof Winwheel === 'undefined') {
                        console.error('Winwheel.js ไม่พร้อมใช้งาน: กรุณาโหลดสคริปต์ Winwheel ก่อน');
                        return false;
                    }
                    return true;
                },
                goBack() {
                    this.close();
                    this.$nextTick(() => this.$root.$refs.eventModalComponent.showModal());
                },
                close() {
                    const el = this.$refs.wheelModal;
                    const m = bootstrap.Modal.getInstance(el) || bootstrap.Modal.getOrCreateInstance(el);
                    m.hide();
                },
                createWheel() {
                    if (!this.ensureWinwheelAvailable()) return;
                    if (!this.$refs.canvas) return;

                    const segments = (this.segments && this.segments.length)
                        ? this.segments
                        : Array.from({ length: 10 }, () => ({ fillStyle: '#333' }));

                    this.winwheel = new Winwheel({
                        canvasId: 'spinwheel',
                        numSegments: segments.length,
                        lineWidth: 3,
                        drawText: false,
                        textFontSize: 16,
                        textOrientation: 'curved',
                        textAlignment: 'inner',
                        textMargin: 60,
                        textFontFamily: 'monospace',
                        textStrokeStyle: 'white',
                        textLineWidth: 3,
                        textFillStyle: 'white',
                        outerRadius: 190,
                        responsive: true,
                        drawMode: 'segmentImage',
                        segments: segments,
                        animation: {
                            type: 'spinToStop',
                            duration: 10,
                            spins: 20,
                            callbackSound: this.playSound,
                            callbackFinished: this.alertPrize,
                            soundTrigger: 'pin'
                        },
                        pins: {
                            margin: 20,
                            number: segments.length,
                            fillStyle: 'red',
                            strokeStyle: 'white',
                            outerRadius: 8,
                            responsive: true
                        }
                    });

                    this.winwheel.draw();
                },

                // ---------- Server interaction with Retry + Idempotency ----------
                async updateResultWithRetry({ maxRetries = 2, baseDelay = 400 } = {}) {
                    // ใช้คีย์เดิมทุกครั้งในรอบนี้
                    if (!this.currentIdemKey) this.currentIdemKey = this.genIdemKey();

                    let attempt = 0;
                    // loop: initial try + retries
                    // รวมจำนวนครั้งสูงสุด = maxRetries + 1
                    while (true) {
                        if (!this.isShown || this.wheelSpinning === true) {
                            // โมดัลถูกปิดหรือสถานะไม่พร้อมแล้ว → ยกเลิก
                            throw new Error('spin-cancelled');
                        }

                        try {
                            const response = await axios.post(
                                `${this.$root.baseUrl}/member/reward`,
                                {},
                                {
                                    headers: {
                                        'Cache-Control': 'no-store',
                                        'X-Idempotency-Key': this.currentIdemKey   // ★ สำคัญ
                                    },
                                    timeout: 15000
                                }
                            );

                            // ----- success path -----
                            this.diamond = Number(response.data?.diamond ?? this.diamond);

                            if (this.winwheel && response.data?.format?.point != null) {
                                this.winwheel.animation.stopAngle = Number(response.data.format.point);
                            }

                            this.format = response.data?.format ?? {};

                            if (this.winwheel) {
                                this.wheelSpinning = true;   // เริ่มหมุนจริง
                                this.winwheel.startAnimation();
                            }

                            return; // ออก loop สำเร็จ
                        } catch (e) {
                            // ถ้าเป็น error ที่ไม่ควร retry (เช่น 4xx ยกเว้น 408/429), ให้ break
                            const status = e?.response?.status;
                            const retriable =
                                e.code === 'ECONNABORTED' || // timeout
                                status === 408 || status === 429 || // request timeout / rate limit
                                (status >= 500 || !status); // server error / network error no status

                            if (!retriable || attempt >= maxRetries) {
                                throw e; // หมดสิทธิ์ retry
                            }

                            // คำนวณ delay แบบ exponential backoff
                            const delay = baseDelay * Math.pow(2, attempt); // 400, 800, 1600...
                            attempt += 1;

                            // ถ้าระหว่างรอ modal ปิด ให้ยกเลิกซะ
                            await this.sleep(delay);
                            if (!this.isShown) throw new Error('spin-cancelled');
                            // จากนั้น loop ต่อไปจะลองใหม่ ด้วย idempotency key เดิม
                        }
                    }
                },

                // ---------- UI flow ----------
                async startSpin() {
                    // กันกดย้ำทันที
                    if (Number(this.diamond) < 1 || this.wheelSpinning || this.isRequesting) return;

                    this.pauseSound();
                    this.isRequesting = true;       // ล็อกปุ่มทันที
                    this.currentIdemKey = null;     // reset คีย์สำหรับรอบใหม่

                    try {
                        await this.updateResultWithRetry({ maxRetries: 2, baseDelay: 400 });
                        // เมื่อเริ่มหมุนจริง isRequesting จะถูกคุมด้วย wheelSpinning ต่อ
                    } catch (e) {
                        // ล้มเหลวทั้งหมด → ปลดล็อก
                        this.isRequesting = false;
                        console.error('startSpin error:', e);
                        // แจ้งเตือนผู้ใช้ตาม UX ศูนย์ (swal/toast) ได้
                    }
                },

                resetWheel() {
                    if (!this.winwheel) return;
                    this.winwheel.stopAnimation(false);
                    this.winwheel.rotationAngle = 0;
                    this.wheelSpinning = false;
                    this.isRequesting = false; // ปลดล็อกปุ่มหลังจบกระบวนการ
                    this.currentIdemKey = null;
                    const comp = this.$root?.$refs?.memberComponent;
                    if (comp?.loadCredit) comp.loadCredit();
                },

                alertPrize() {
                    const f = this.format || {};
                    Swal.fire({
                        title: f.title || this.trans('app.home.wheel_result'),
                        text: f.msg || '',
                        imageUrl: f.img || '',
                        imageWidth: f.img ? 150 : undefined,
                        imageHeight: f.img ? 150 : undefined,
                        imageAlt: f.title || '',
                    });

                    this.resetWheel();
                    this.loadSpin(true).catch(() => {});
                },

                // ---------- Audio ----------
                playSound() {
                    const audio = this.$refs.audioTick;
                    if (!audio) return;
                    try {
                        audio.currentTime = 0;
                        audio.play().catch(() => {});
                    } catch (_) {}
                },
                pauseSound() {
                    const audio = this.$refs.audioTick;
                    if (!audio) return;
                    try { audio.pause(); } catch (_) {}
                },

                // ---------- Optional pointer ----------
                drawTriangle() {
                    if (!this.winwheel) return;
                    const ctx = this.winwheel.ctx;
                    ctx.strokeStyle = 'white';
                    ctx.fillStyle = 'aqua';
                    ctx.lineWidth = 2;
                    ctx.beginPath();
                    ctx.moveTo(170, 5);
                    ctx.lineTo(230, 5);
                    ctx.lineTo(200, 40);
                    ctx.lineTo(171, 5);
                    ctx.stroke();
                    ctx.fill();
                },

                teardown() {
                    if (this.winwheel) {
                        try { this.winwheel.stopAnimation(false); } catch(_) {}
                        this.winwheel = null;
                    }
                    this.wheelSpinning = false;
                    this.isRequesting = false;
                    this.currentIdemKey = null;
                    this.segments = [];
                }
            }
        });
    </script>
@endpush
