<script type="text/x-template" id="deposit-modal-template">
    <div class="modal modal-custom fade" id="depositModal" data-bs-backdrop="static" data-bs-keyboard="false"
         tabindex="-1" aria-labelledby="depositLabel" aria-hidden="true" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content bg-dark-2" style="min-height: 60vh;">
                <div class="modal-header">
                    <h5 class="modal-title text-center mb-0 text-dark lh-1"
                        id="depositLabel" v-text="trans('app.topup.refill')"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-1">
                    {{--                    <topup-tabs  :key="tabVersion" :tabs="filteredTabs" :selected="selected" @select="onSelect"></topup-tabs>--}}

                    <!-- === Skeleton ชั่วคราวตอนกำลังโหลดและยังไม่รู้ผลช่องทาง === -->
                    <div v-if="!isChannelReady && loading" class="py-3">
                        <div class="container-fluid">
                            <div class="row g-2 mt-1">
                                <div class="card bg-dark mb-1" v-for="n in 3" :key="'skel-b-'+n">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="placeholder col-2 rounded" style="height:50px;"></span>
                                            <div class="flex-grow-1">
                                                <div class="placeholder-glow"><span class="placeholder col-4"></span>
                                                </div>
                                                <div class="placeholder-glow mt-1"><span
                                                            class="placeholder col-6"></span></div>
                                                <div class="placeholder-glow mt-1"><span
                                                            class="placeholder col-3"></span></div>
                                            </div>
                                            <span class="placeholder col-2 rounded-pill" style="height:36px;"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- === ตัวเลือกช่องฝาก (รีเรนเดอร์ด้วย tabVersion) === -->
                    <div v-else>
                        <topup-tabs
                                :key="'tabs-'+tabVersion"
                                :tabs="filteredTabs"
                                :selected="selected"
                                @select="onSelect"
                        />
                    </div>

                    {{--                    <div v-if="!hasSelection" key="splash" class="text-center mt-3">--}}
                    {{--                        <img src="/images/qr/howtoqr.jpg"--}}
                    {{--                             class="img-fluid"--}}
                    {{--                             loading="lazy"--}}
                    {{--                             alt="วิธีสแกน QR"/>--}}
                    {{--                    </div>--}}
                </div>

                <div class="modal-footer p-1 w-100 text-center " v-if="selectedPro">
                    <div class="text-warning fw-light w-100">
                        <span v-text="trans('app.promotion.select')"></span>
                        <span v-text="promotion.name"></span><br>

                        <span v-text="trans('app.promotion.min')"></span>
                        <span v-text="promotion.min"></span><br>

                        <div class="d-grid gap-2 mt-1">
                            <button class="btn btn-danger"
                                    v-on:click="deSelectPro"
                                    v-text="trans('app.promotion.delete')"></button>
                        </div>
                    </div>

                </div>
                <div v-else class="modal-footer p-1 w-100 text-center">
                    <div class="text-warning fw-light w-100" v-text="trans('app.promotion.suggest')"></div>
                    <div v-if="member.getpro" class="w-100">
                        <hr class="w-100 mx-auto my-1">
                        <div class="fs-6 text-content w-100 text-center">
                            <span v-text="trans('app.promotion.me')"></span> :
                            <span class="fw-bolder text-danger" v-text="member.pro_name"></span><br>
                            <span v-text="trans('app.home.withdraw_turn')"></span> : <span class="fw-bolder text-danger"
                                                                                           v-text="member.amount_balance"></span><br>
                            <span v-text="trans('app.home.limit_withdraw')"></span> : <span
                                    class="fw-bolder text-custom-primary"
                                    v-text="member.withdraw_limit_amount"></span>
                        </div>
                    </div>
                    <div v-else></div>
                </div>

            </div>
        </div>
    </div>
</script>

<script type="text/x-template" id="topup-tabs-template">
    <div class="container-fluid">
        <div class="row g-2 mt-1">
            <div class="col-4" v-for="tab in tabs" :key="tab.id">
                <div
                        class="card h-100 text-center shadow-sm p-2"
                        :class="[
                        'bg-dark',
                        'text-white',
                        tab.id !== selected ? 'opacity-50' : 'opacity-100'
                    ]">
                    <button class="btn btn-for-deposit" @click="$emit('select', tab.id)"
                            :class="{ 'is-selected': selected === tab.id }">
                        <img :src="tab.icon" class="card-img-top mx-auto" style="width: 50px; object-fit: contain;">
                        <p class="-title text-white" v-text="tab.title"></p>
                    </button>
                </div>
            </div>
        </div>
    </div>
</script>

<script type="text/x-template" id="topup-bank-template">
    <div v-if="loading" class="py-3">
        <!-- Skeleton list -->
        <div class="container-fluid">
            <div class="row g-2 mt-1">
                <div class="card bg-dark mb-1" v-for="n in 3" :key="'skel-b-'+n">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <span class="placeholder col-2 rounded" style="height:50px;"></span>
                            <div class="flex-grow-1">
                                <div class="placeholder-glow">
                                    <span class="placeholder col-4"></span>
                                </div>
                                <div class="placeholder-glow mt-1">
                                    <span class="placeholder col-6"></span>
                                </div>
                                <div class="placeholder-glow mt-1">
                                    <span class="placeholder col-3"></span>
                                </div>
                            </div>
                            <span class="placeholder col-2 rounded-pill" style="height:32px;"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-else>
        <div v-if="items.length > 0">
            <div class="container-fluid">
                <div class="row g-2 mt-1">
                    <div class="card bg-dark bank-deposit-item mb-1"
                         v-for="item in items"
                         :key="item.code || item.acc_no"
                         v-if="item">
                        <div class="card-body bank-item-container container p-3">
                            <div class="bank-info d-flex">
                                <div class="bank-icon d-flex align-items-center">
                                    <img :src="item.bank_pic" width="50" height="50" style="object-fit: contain;"
                                         alt="">
                                </div>
                                <div class="bank-detail ps-4 col text-white">
                                    <div class="text-start fw-light" v-text="item.bank_name"></div>
                                    <div class="text-start mt-auto pt-1" v-text="item.acc_name"></div>
                                    <div class="text-warning fs-6 text-start lh-1" v-text="item.acc_no"></div>
                                </div>

                                <div class="btn-copy-bank d-flex align-items-center">
                                    <button class="py-1 shadow rounded-pill btn btn-outline-secondary btn-custom-secondary text-white fw-light d-flex"
                                            style="min-width: unset;"
                                            @click="copylink(item.acc_no)">
                                        <span class="w-100 flex-row-center-xy">
                                            <i class="bi bi-clipboard-check text-light fw-light"></i>
                                            <span class="ms-1" v-text="trans('app.con.copy')"></span>
                                            <b class="ms-1" v-text="item.acc_no"></b>
                                            <input tabindex="-1" aria-hidden="true" class="ip-copyfrom modal-deposit">
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- แสดงยอดฝากขั้นต่ำ (ต่อบัญชี) -->
                            <div class="w-100 text-center small text-muted"
                                 v-if="shouldShowMinDeposit(item)"
                                 v-text="minDepositText(item)">
                            </div>
                            <div class="w-100 text-center small text-muted"
                                 v-if="item.remark"
                                 v-text="item.remark">
                            </div>

                            <div class="bank-info mt-2" v-if="item.qrcode">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="d-flex justify-content-center mb-2" style="gap: 8px;">
                                        <a class="btn btn-outline-secondary shadow"
                                           :href="item.qr_pic"
                                           :download="`qr-${item.acc_no}.png`"
                                           target="_blank"
                                           rel="noopener"
                                           v-if="item.qr_pic">
                                            <i class="bi bi-download"></i>
                                            <span class="ms-1" v-text="trans('app.topup.qrscan_download')"></span>
                                        </a>
                                        <button class="btn btn-outline-secondary shadow"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                :data-bs-target="`#qrzone-${item.code || item.acc_no}`"
                                                aria-expanded="false"
                                                :aria-controls="`qrzone-${item.code || item.acc_no}`">
                                            <span v-text="trans('app.topup.qrscan')"></span>
                                        </button>
                                    </div>
                                    <div class="collapse w-100" :id="`qrzone-${item.code || item.acc_no}`">
                                        <div class="card card-body d-flex justify-content-center align-items-center">
                                            <img :src="item.qr_pic" class="img-fluid" style="max-width:220px;" alt="QR">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- /.card-body -->
                    </div><!-- /.card -->
                </div>
            </div>
        </div>

        <div v-else class="text-center text-muted py-4">
            <span v-text="trans('app.home.no_list')"></span>
        </div>
    </div>
</script>

<script type="text/x-template" id="topup-tw-template">
    <div v-if="loading" class="py-3">
        <!-- Skeleton list -->
        <div class="container-fluid">
            <div class="row g-2 mt-1">
                <div class="card bg-dark mb-1" v-for="n in 3" :key="'skel-tw-'+n">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <span class="placeholder col-2 rounded" style="height:50px;"></span>
                            <div class="flex-grow-1">
                                <div class="placeholder-glow">
                                    <span class="placeholder col-4"></span>
                                </div>
                                <div class="placeholder-glow mt-1">
                                    <span class="placeholder col-6"></span>
                                </div>
                                <div class="placeholder-glow mt-1">
                                    <span class="placeholder col-3"></span>
                                </div>
                            </div>
                            <span class="placeholder col-2 rounded-pill" style="height:32px;"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-else>
        <div v-if="items.length > 0">
            <div class="container-fluid">
                <div class="row g-2 mt-1">
                    <div class="card bg-dark bank-deposit-item mb-1" v-for="item in items"
                         :key="item.code || item.acc_no" v-if="item">
                        <div class="card-body bank-item-container container p-3">
                            <div class="bank-info d-flex">
                                <div class="bank-icon d-flex align-items-center">
                                    <img :src="item.bank_pic" width="50" height="50" style="object-fit: contain;"
                                         alt="">
                                </div>
                                <div class="bank-detail ps-4 col text-white">
                                    <div class="text-start fw-light" v-text="item.bank_name"></div>
                                    <div class="text-start mt-auto pt-1" v-text="item.acc_name"></div>
                                    <div class="text-warning fs-6 text-start lh-1" v-text="item.acc_no"></div>
                                </div>
                                <div class="btn-copy-bank d-flex align-items-center">
                                    <button class="py-1 shadow rounded-pill btn btn-outline-secondary btn-custom-secondary text-white fw-light d-flex"
                                            style="min-width: unset;" @click="copylink(item.acc_no)">
                                        <span class="w-100 flex-row-center-xy">
                                            <i class="bi bi-clipboard-check text-light fw-light"></i> <span
                                                    v-text="trans('app.con.copy')"></span>
                                            <b v-text="item.acc_no"></b>
                                            <input tabindex="-1" aria-hidden="true" class="ip-copyfrom modal-deposit">
                                        </span>
                                    </button>
                                </div>

                            </div>

                            <!-- แสดงยอดฝากขั้นต่ำ (ต่อบัญชี) -->
                            <div class="w-100 text-center small text-muted"
                                 v-if="shouldShowMinDeposit(item)"
                                 v-text="minDepositText(item)">
                            </div>
                            <div class="w-100 text-center small text-muted"
                                 v-if="item.remark"
                                 v-text="item.remark">
                            </div>

                            <div class="bank-info mt-2" v-if="item.qrcode">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="d-flex justify-content-center mb-2" style="gap: 8px;">
                                        <a class="btn btn-outline-secondary shadow"
                                           :href="item.qr_pic"
                                           :download="`qr-${item.acc_no}.png`"
                                           target="_blank"
                                           rel="noopener"
                                           v-if="item.qr_pic">
                                            <i class="bi bi-download"></i>
                                            <span class="ms-1" v-text="trans('app.topup.qrscan_download')"></span>
                                        </a>
                                        <button class="btn btn-outline-secondary shadow"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                :data-bs-target="`#qrzone-${item.code || item.acc_no}`"
                                                aria-expanded="false"
                                                :aria-controls="`qrzone-${item.code || item.acc_no}`">
                                            <span v-text="trans('app.topup.qrscan')"></span>
                                        </button>
                                    </div>
                                    <div class="collapse w-100" :id="`qrzone-${item.code || item.acc_no}`">
                                        <div class="card card-body d-flex justify-content-center align-items-center">
                                            <img :src="item.qr_pic" class="img-fluid" style="max-width:220px;" alt="QR">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- /.card-body -->
                    </div><!-- /.card -->
                </div>
            </div>
        </div>

        <div v-else class="text-center text-muted py-4">
            <span v-text="trans('app.home.no_list')"></span>

        </div>
    </div>
</script>

<script type="text/x-template" id="topup-payment-template">
    <div v-if="loading" class="py-3">
        <div class="container">
            <div class="row g-2">
                <div class="col-6" v-for="n in 2" :key="'skel-pay-btn-'+n">
                    <div class="placeholder-glow">
                        <span class="placeholder col-12" style="height:40px;border-radius:999px;display:block;"></span>
                    </div>
                </div>
            </div>
            <div class="placeholder-glow mt-3 d-flex justify-content-center">
                <span class="placeholder col-6" style="height:48px;border-radius:8px;display:block;"></span>
            </div>
        </div>
    </div>

    <div v-else>
        <div v-if="item">
            <div class="container-fluid">
                <div class="row g-2 mt-1">
                    <div class="card bg-dark bank-deposit-item mb-1">

                        <div v-if="paymentOptions.length > 1" class="-bank-info-container mt-3 ml-3 mr-3 row">
                            <div v-for="option in paymentOptions" :key="option.id"
                                 class="mb-3 col-6 d-flex justify-content-center align-items-center">
                                <button
                                        @click="selectPayment(option)"
                                        class="btn amount-btn"
                                        :class="{
                                        'active': selectedPayment && selectedPayment.id === option.id,
                                        'deactive': selectedPayment && selectedPayment.id !== option.id
                                    }"
                                        style="max-width:200px;">
                                    <span v-text="labelOption(option)"></span>
                                </button>
                            </div>
                        </div>

                        <div class="card-body bank-item-container container p-1" v-if="selectedPayment">
                            <div class="bank-info">
                                <form @submit.prevent.stop="submitDeposit">
                                    <div class="theme-form mt-4 text-center">
                                        <div class="input-group input-group-lg mx-auto custom-style-input"
                                             style="max-width: 20em;">
                                            <span class="input-group-text">
                                                <img src="/assets/kimberbet/images/icon/coin.svg" width="40" height="40"
                                                     alt="">
                                            </span>
                                            <input
                                                    v-model.number="depositAmount"
                                                    ref="depositInput"
                                                    step="1"
                                                    id="deposit"
                                                    type="number"
                                                    autocomplete="off"
                                                    :placeholder="minDepositPlaceholder"
                                                    required
                                                    :min="minDeposit"
                                                    class="form-control"
                                                    @keydown="preventDot"
                                            >

                                        </div>
                                        <p style="margin:0 auto;text-align:center;font-size:smaller;" class="mt-2"
                                           v-text="selectedPayment.remark" v-if="selectedPayment.remark"></p>
                                    </div>

                                    <div class="text-center mt-3 pb-3">
                                        <button type="submit"
                                                class="btn btn-primary btn-custom-primary w-100 rounded-pill"
                                                :disabled="isSubmitting"
                                                style="max-width: 20em;" v-text="trans('app.home.deposit')">
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div><!-- /.card -->
                </div>
            </div>
        </div>

        <div v-else class="text-center text-muted py-4">
            <span v-text="trans('app.home.no_list')"></span>

        </div>
    </div>
</script>

<script type="text/x-template" id="topup-slip-template">
    <div v-if="loading" class="py-3">
        <!-- Skeleton for slip header card -->
        <div class="container-fluid">
            <div class="row g-2 mt-1">
                <div class="card bg-dark mb-1">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <span class="placeholder col-2 rounded" style="height:50px;"></span>
                            <div class="flex-grow-1">
                                <div class="placeholder-glow">
                                    <span class="placeholder col-4"></span>
                                </div>
                                <div class="placeholder-glow mt-1">
                                    <span class="placeholder col-6"></span>
                                </div>
                                <div class="placeholder-glow mt-1">
                                    <span class="placeholder col-3"></span>
                                </div>
                            </div>
                            <span class="placeholder col-2 rounded-pill" style="height:32px;"></span>
                        </div>
                    </div>
                </div>
                <!-- fake dropzone placeholder -->
                <div class="card bg-dark mb-1">
                    <div class="card-body">
                        <div class="placeholder-glow">
                            <span class="placeholder col-12" style="height:120px;"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-else>
        <div v-if="item">
            <div class="container-fluid">
                <div class="row g-2 mt-1">
                    <div class="card bg-dark bank-deposit-item mb-1">
                        <div class="card-body bank-item-container container p-3">
                            <div class="bank-info d-flex">
                                <div class="bank-icon d-flex align-items-center">
                                    <img :src="item.bank_pic" width="50" height="50" style="object-fit: contain;"
                                         alt="">
                                </div>
                                <div class="bank-detail ps-4 col text-white">
                                    <div class="text-start fw-light" v-text="item.bank_name"></div>
                                    <div class="text-start mt-auto pt-1" v-text="item.acc_name"></div>
                                    <div class="text-warning fs-6 text-start lh-1" v-text="item.acc_no"></div>
                                </div>
                                <div class="btn-copy-bank d-flex align-items-center">
                                    <button class="py-1 shadow rounded-pill btn btn-outline-secondary btn-custom-secondary text-white fw-light d-flex"
                                            style="min-width: unset;" @click="copylink(item.acc_no)">
                                        <span class="w-100 flex-row-center-xy">
                                            <i class="bi bi-clipboard-check text-light fw-light"></i> <span
                                                    v-text="trans('app.con.copy')"></span>
                                            <b v-text="item.acc_no"></b>
                                            <input tabindex="-1" aria-hidden="true" class="ip-copyfrom modal-deposit">
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- lazy init dropzone via activated() -->
                    <upload-slip ref="upload" :account-info="item"></upload-slip>
                </div>

            </div>
        </div>

        <div v-else class="text-center text-muted py-4">
            <span v-text="trans('app.home.no_list')"></span>

        </div>
    </div>
</script>

<script type="text/x-template" id="upload-slip-template">
    <form ref="dropzoneRef" class="dropzone">
        <div class="dz-message">
            <i class="fas fa-upload"></i>
            <span v-text="trans('app.topup.dragslip')"></span>
        </div>
    </form>
</script>

<script type="text/x-template" id="channel-list-modal-tpl">
    <div class="modal modal-custom fade" id="channelListModal" ref="channelListModal" data-bs-backdrop="static"
         data-bs-keyboard="false"
         tabindex="-1" aria-labelledby="depositLabel" aria-hidden="true" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content bg-dark-2" style="min-height: 60vh;">
                <div class="modal-header">
                    <h5 class="modal-title text-center mb-0 text-dark lh-1" id="depositSubLabel" v-text="headerTitle">
                        @{{ trans('app.qrscan.upload') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-1">
                    <!-- keep-alive เพื่อจำสถานะภายในของคอมโพเนนต์ -->
                    <keep-alive>
                        <component :is="componentName"
                                   ref="activeComponent"
                                   :key="selectedId"
                                   @footer-message="$emit('footer-message', $event)"/>
                    </keep-alive>
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
        /* ================== CreditStore: ensure-before-open ================== */
        const CreditStore = {
            state: {ready: false, at: 0, data: null, inflight: null},
            ttl: 60 * 1000, // 60s

            get() {
                return this.state.data;
            },

            async fetch() {
                // ดึงข้อมูลเครดิต/ช่องทาง แบบ network-no-store
                const cfg = {headers: {'Cache-Control': 'no-store'}, timeout: 10000, withCredentials: true};
                const res = await axios.get("{{ route('customer.home.credit') }}", cfg);
                if (!res.data?.success) throw new Error('credit fetch failed');
                this.state.data = res.data;
                this.state.ready = true;
                this.state.at = Date.now();
                return res.data;
            },

            async ensure(maxAgeMs = this.ttl) {
                const fresh = this.state.ready && (Date.now() - this.state.at) < maxAgeMs;
                if (fresh) return this.state.data;
                if (this.state.inflight) return this.state.inflight; // กันยิงซ้ำ

                this.state.inflight = this.fetch()
                    .catch(e => {
                        this.state.ready = false;
                        this.state.data = null;
                        throw e;
                    })
                    .finally(() => {
                        this.state.inflight = null;
                    });

                return this.state.inflight;
            }
        };

        /* ===== upload-slip: lazy init via exposed .init() ===== */
        Vue.component('upload-slip', {
            props: ['accountInfo'],
            template: '#upload-slip-template',
            data() {
                return {dz: null, _inited: false};
            },
            methods: {
                init() {
                    if (this._inited) return;
                    this._inited = true;

                    const self = this;


                    // กันกรณีที่ AutoDiscover ถูกเปิดไว้จากที่อื่น (อาจทำให้ init ซ้ำ/พฤติกรรมเพี้ยน)
                    if (typeof Dropzone !== 'undefined' && Dropzone.autoDiscover) {
                        Dropzone.autoDiscover = false;
                    }
                    this.dz = new Dropzone(this.$refs.dropzoneRef, {
                        url: "{{ route('customer.slip.upload') }}",
                        method: 'post',
                        maxFiles: 1,
                        acceptedFiles: 'image/*',
                        addRemoveLinks: true,
                        autoProcessQueue: true,
                        init: function () {
                            this.on('sending', function (file, xhr, formData) {
                                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                                const info = {code: self.accountInfo?.code || ''};
                                const payload = {
                                    checkDuplicate: false,
                                    checkReceiver: [{
                                        accountType: self.accountInfo?.slip_bank || '',
                                        accountNumber: self.accountInfo?.acc_no || ''
                                    }],
                                    checkDate: {type: 'gte', date: new Date().toISOString()}
                                };

                                formData.append('payload', JSON.stringify(payload));
                                formData.append('info', JSON.stringify(info));
                            });

                            this.on('success', function (file, response) {
                                this.removeFile(file);
                                const dm = document.getElementById('depositModal');
                                if (dm) bootstrap.Modal.getInstance(dm)?.hide();

                                if (response.code === '200200') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'ผลการตรวจสอบ',
                                        text: 'ตรวจสอบสำเร็จ โปรดตรวจสอบยอดเครดิต',
                                        timer: 2500,
                                        showConfirmButton: false
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'ผลการตรวจสอบ',
                                        text: response.message,
                                        timer: 2500,
                                        showConfirmButton: false
                                    });
                                }
                            });

                            this.on('error', function () {
                                this.removeAllFiles(true);
                                const dm = document.getElementById('depositModal');
                                if (dm) bootstrap.Modal.getInstance(dm)?.hide();
                                Swal.fire({
                                    icon: 'info',
                                    title: 'ผลการตรวจสอบ',
                                    text: 'ผิดพลาด',
                                    timer: 2500,
                                    showConfirmButton: false
                                });
                            });
                        },
                    });
                },
                resetUpload() {
                    if (this.dz) this.dz.removeAllFiles(true);
                },
            }
        });

        Vue.component('channel-list-modal', {
            template: '#channel-list-modal-tpl',
            props: {},
            data() {
                return {
                    selectedId: null,
                    _slipInitQueued: false,
                    _shownHandler: null,
                };
            },
            computed: {
                headerTitle() {
                    switch (this.selectedId) {
                        case 'topup_bank':
                            return this.trans('app.home.topup_bank');
                        case 'topup_tw':
                            return this.trans('app.home.topup_wallet');
                        case 'topup_payment':
                            return this.trans('app.home.topup_scan');
                        case 'topup_slip':
                            return this.trans('app.topup.slip');
                        default:
                            return this.trans('app.home.topup_channel');
                    }
                },
                componentName() {
                    switch (this.selectedId) {
                        case 'topup_bank':
                            return 'topup-bank';
                        case 'topup_tw':
                            return 'topup-tw';
                        case 'topup_payment':
                            return 'topup-payment';
                        case 'topup_slip':
                            return 'topup-slip';
                        default:
                            return null;
                    }
                }
            },
            watch: {
                // ถ้าผู้ใช้สลับไปแท็บสลิปในขณะที่ modal เปิดอยู่ ให้ init อีกชั้น (กันเคส keep-alive/transition)
                selectedId(val) {
                    if (val !== 'topup_slip') return;

                    this._slipInitQueued = true;

                    const el = this.$refs.channelListModal;
                    const isShown = !!(el && el.classList && el.classList.contains('show'));
                    if (isShown) {
                        this.$nextTick(() => {
                            this.initSlipDropzone();
                            this._slipInitQueued = false;
                        });
                    }
                },
            },
            mounted() {
                const el = this.$refs.channelListModal;

                // Init หลัง modal "shown" เท่านั้น เพื่อหลีกเลี่ยง init ตอน element ยัง hidden
                this._shownHandler = () => {
                    if (this.selectedId !== 'topup_slip') return;

                    if (!this._slipInitQueued) this._slipInitQueued = true;

                    this.$nextTick(() => {
                        this.initSlipDropzone();
                        this._slipInitQueued = false;
                    });
                };

                el?.addEventListener?.('shown.bs.modal', this._shownHandler);
            },
            beforeDestroy() {
                const el = this.$refs.channelListModal;
                if (this._shownHandler) {
                    el?.removeEventListener?.('shown.bs.modal', this._shownHandler);
                }
            },
            methods: {
                open(tabId) {
                    this.selectedId = tabId;
                    this._slipInitQueued = (tabId === 'topup_slip');

                    const el = this.$refs.channelListModal;
                    const modal = bootstrap.Modal.getOrCreateInstance(el);
                    modal.show();

                    // เผื่อบางสภาพแวดล้อม modal ขึ้นทันที (ไม่รอ event) ก็พยายาม init อีกชั้นแบบปลอดภัย
                    if (tabId === 'topup_slip') {
                        this.$nextTick(() => this.initSlipDropzone());
                    }
                },
                initSlipDropzone() {
                    if (this.selectedId !== 'topup_slip') return false;

                    // ทางหลัก: ใช้ ref ที่เสถียรที่สุด (ไม่ไล่ $children แบบเดา ๆ)
                    const active = this.$refs?.activeComponent;
                    if (active?.$refs?.upload?.init) {
                        active.$refs.upload.init();
                        return true;
                    }

                    // fallback: เผื่อ ref ยังไม่พร้อมจริง ๆ / โครงสร้างเก่าบางหน้า
                    const slip = (this.$children || []).find(c => {
                        const tag = c?.$options?._componentTag;
                        const name = c?.$options?.name;
                        return tag === 'topup-slip' || name === 'topup-slip';
                    });

                    if (slip?.$refs?.upload?.init) {
                        slip.$refs.upload.init();
                        return true;
                    }

                    return false;
                },
                close() {
                    const el = this.$refs.channelListModal;
                    const m = bootstrap.Modal.getInstance(el) || bootstrap.Modal.getOrCreateInstance(el);
                    m.hide();
                },
                goBack() {
                    this.close();
                    // คงพฤติกรรมเดิม: กลับไปเปิด deposit modal
                    this.$nextTick(() => this.$root.$refs.depositModalComponent.showModal());
                }
            }
        });

        {{--Vue.component('deposit-modal', {--}}
        {{--    template: '#deposit-modal-template',--}}
        {{--    data() {--}}
        {{--        return {--}}
        {{--            member: {pro: false, pro_name: '', amount_balance: 0, withdraw_limit_amount: 0, getpro: false},--}}
        {{--            selectedPro: false,--}}
        {{--            promotion: {name: '', min: 0},--}}
        {{--            resetCounter: 0,--}}
        {{--            selected: null,          // ← ใช้ null แทน '' เพื่อบอก "ยังไม่เคยเลือก"--}}
        {{--            selectedTab: null,--}}
        {{--            footerMsg: '',--}}
        {{--            tabs: [--}}
        {{--                {--}}
        {{--                    id: 'topup_payment',--}}
        {{--                    title: this.trans('app.home.topup_scan'),--}}
        {{--                    icon: 'https://img5.pic.in.th/file/secure-sv1/qr0068bdbf0cc6226d.png',--}}
        {{--                    component: 'topup-payment',--}}
        {{--                    order: 1--}}
        {{--                },--}}
        {{--                {--}}
        {{--                    id: 'topup_bank',--}}
        {{--                    title: this.trans('app.home.topup_bank'),--}}
        {{--                    icon: 'https://img2.pic.in.th/pic/bank19da438c9e295f0b.png',--}}
        {{--                    component: 'topup-bank',--}}
        {{--                    order: 2--}}
        {{--                },--}}
        {{--                {--}}
        {{--                    id: 'topup_tw',--}}
        {{--                    title: this.trans('app.home.topup_wallet'),--}}
        {{--                    icon: 'https://img2.pic.in.th/pic/twa6cf4bb54c16ae4b.png',--}}
        {{--                    component: 'topup-tw',--}}
        {{--                    order: 3--}}
        {{--                },--}}
        {{--                {--}}
        {{--                    id: 'topup_slip',--}}
        {{--                    title: this.trans('app.topup.slip'),--}}
        {{--                    icon: 'https://img5.pic.in.th/file/secure-sv1/qr0068bdbf0cc6226d.png',--}}
        {{--                    component: 'topup-slip',--}}
        {{--                    order: 4--}}
        {{--                },--}}
        {{--            ],--}}
        {{--            qrscan: false,--}}
        {{--            slip: false,--}}
        {{--            bank: false,--}}
        {{--            tw: false,--}}
        {{--            tabVersion: 0,--}}
        {{--            sortMap: {payment: null, bank: null, tw: null, slip: null},--}}
        {{--            loading: false,--}}
        {{--            error: null,--}}
        {{--            isInitialOpen: false,--}}
        {{--        };--}}
        {{--    },--}}
        {{--    computed: {--}}
        {{--        filteredTabs() {--}}
        {{--            return this.tabs--}}
        {{--                .filter(tab => {--}}
        {{--                    if (tab.id === 'topup_payment' && !this.qrscan) return false;--}}
        {{--                    if (tab.id === 'topup_bank' && !this.bank) return false;--}}
        {{--                    if (tab.id === 'topup_tw' && !this.tw) return false;--}}
        {{--                    if (tab.id === 'topup_slip' && !this.slip) return false;--}}
        {{--                    return true;--}}
        {{--                })--}}
        {{--                .sort((a, b) => this.getEffectiveOrder(a) - this.getEffectiveOrder(b));--}}
        {{--        },--}}
        {{--        selectedComponent() {--}}
        {{--            const tab = this.filteredTabs.find(t => t.id === this.selected);--}}
        {{--            return tab ? tab.component : null;--}}
        {{--        },--}}
        {{--        selectedKey() {--}}
        {{--            return `${this.selected}-${this.resetCounter}`;--}}
        {{--        },--}}
        {{--        hasSelection() {--}}
        {{--            return !!this.selected;--}}
        {{--        },--}}
        {{--    },--}}
        {{--    watch: {--}}
        {{--        selected() {--}}
        {{--            this.footerMsg = ''--}}
        {{--        }--}}
        {{--    },--}}
        {{--    methods: {--}}
        {{--        async onSelect(tabId) {--}}
        {{--            const el = document.getElementById('depositModal');--}}
        {{--            const m = bootstrap.Modal.getInstance(el) || bootstrap.Modal.getOrCreateInstance(el);--}}
        {{--            m.hide();--}}
        {{--            setTimeout(() => this.$root.$refs.channelListModal.open(tabId), 120);--}}
        {{--        },--}}
        {{--        showAgain() {--}}
        {{--            try {--}}
        {{--                const cached = CreditStore.get();--}}
        {{--                if (cached) this.applyCreditData(cached, { initial: false });--}}
        {{--            } catch (e) { /* เงียบไว้ */ }--}}

        {{--            // บังคับ re-render ให้ชัวร์ (กรณี flags เปลี่ยนตอนเราไปหน้า channel)--}}
        {{--            this.tabVersion++;--}}

        {{--            const el = document.getElementById('depositModal');--}}
        {{--            const modal = bootstrap.Modal.getOrCreateInstance(el);--}}
        {{--            modal.show();--}}
        {{--        },--}}
        {{--        async showModal() {--}}
        {{--            // ✅ รับประกันความพร้อมของข้อมูลก่อนเปิด (แก้กดแล้วว่าง 1 วิ)--}}
        {{--            this.resetModal();--}}
        {{--            this.loading = true;--}}
        {{--            this.isInitialOpen = true;                    // ← บอกว่าเป็นรอบแรก--}}

        {{--            try {--}}
        {{--                const data = await CreditStore.ensure(60 * 1000);--}}
        {{--                this.applyCreditData(data, {initial: false});  // ← อนุญาต auto-select--}}
        {{--            } catch (e) {--}}
        {{--                console.error('credit ensure failed', e);--}}
        {{--                this.error = 'โหลดข้อมูลไม่สำเร็จ';--}}
        {{--            } finally {--}}
        {{--                this.loading = false;--}}
        {{--            }--}}

        {{--            this.$nextTick(() => {--}}
        {{--                const el = document.getElementById('depositModal');--}}
        {{--                const modal = bootstrap.Modal.getOrCreateInstance(el);--}}
        {{--                modal.show();--}}
        {{--                this.isInitialOpen = false;                 // ← หลังแสดงแล้วปิดโหมด initial--}}
        {{--            });--}}

        {{--        },--}}

        {{--        coerceBool(v) {--}}
        {{--            if (v === true || v === false) return v;--}}
        {{--            const n = Number(v);--}}
        {{--            return Number.isFinite(n) ? n > 0 : !!v;--}}
        {{--        },--}}
        {{--        toIntOrNull(v) {--}}
        {{--            const n = Number(v);--}}
        {{--            return Number.isFinite(n) ? n : null;--}}
        {{--        },--}}
        {{--        applyCreditData(payload, opts = {}) {--}}
        {{--            const { initial = false } = opts;--}}

        {{--            // 1) member--}}
        {{--            const prof = payload?.profile || {};--}}
        {{--            const keep = ['pro','pro_name','amount_balance','withdraw_limit_amount','getpro'];--}}
        {{--            const nextMember = { ...this.member };--}}
        {{--            keep.forEach(k => { if (k in prof) nextMember[k] = prof[k]; });--}}
        {{--            this.member = nextMember;--}}

        {{--            // 2) promotion--}}
        {{--            const promo = payload?.promotion;--}}
        {{--            if (promo && 'select' in promo) {--}}
        {{--                if (promo.select) {--}}
        {{--                    this.selectedPro = true;--}}
        {{--                    this.promotion   = { name: promo.name ?? '', min: Number(promo.min ?? 0) };--}}
        {{--                } else {--}}
        {{--                    this.selectedPro = false;--}}
        {{--                    this.promotion   = { name: '', min: 0 };--}}
        {{--                }--}}
        {{--            }--}}

        {{--            // 3) deposit flags + sort — อัปเดตเฉพาะ key ที่ส่งมาเท่านั้น--}}
        {{--            const dep = payload?.deposit;--}}
        {{--            if (dep) {--}}
        {{--                if ('bank'    in dep) this.bank   = this.coerceBool(dep.bank);--}}
        {{--                if ('tw'      in dep) this.tw     = this.coerceBool(dep.tw);--}}
        {{--                if ('slip'    in dep) this.slip   = this.coerceBool(dep.slip);--}}
        {{--                if ('payment' in dep) this.qrscan = this.coerceBool(dep.payment);--}}

        {{--                if ('sort' in dep && dep.sort) {--}}
        {{--                    const s = dep.sort;--}}
        {{--                    this.sortMap = {--}}
        {{--                        payment: ('payment' in s) ? this.toIntOrNull(s.payment) : this.sortMap.payment,--}}
        {{--                        bank   : ('bank'    in s) ? this.toIntOrNull(s.bank)    : this.sortMap.bank,--}}
        {{--                        tw     : ('tw'      in s) ? this.toIntOrNull(s.tw)      : this.sortMap.tw,--}}
        {{--                        slip   : ('slip'    in s) ? this.toIntOrNull(s.slip)    : this.sortMap.slip,--}}
        {{--                    };--}}
        {{--                }--}}

        {{--                // re-render <topup-tabs> เมื่อมีการเปลี่ยนกลุ่ม deposit จริง ๆ--}}
        {{--                this.tabVersion++;--}}
        {{--            }--}}

        {{--            // 4) ไม่ auto-select; ถ้าแท็บที่เลือกถูกปิด ให้เคลียร์--}}
        {{--            const stillVisible = this.filteredTabs.some(t => t.id === this.selected);--}}
        {{--            if (this.selected && !stillVisible) this.selected = null;--}}
        {{--        },--}}
        {{--        async deSelectPro() {--}}
        {{--            try {--}}
        {{--                const res = await axios.post("{{ route('customer.promotion.deselect') }}", null, {--}}
        {{--                    headers: {'Cache-Control': 'no-store'}, timeout: 10000,--}}
        {{--                });--}}
        {{--                if (res.data?.success) {--}}
        {{--                    window.Toast.fire({icon: 'success', title: res.data.message});--}}
        {{--                    this.selectedPro = false;--}}
        {{--                    // โหลดเครดิตใหม่ให้ทั้งระบบ sync--}}
        {{--                    window.reLoadCredit?.();--}}
        {{--                }--}}
        {{--            } catch (err) {--}}
        {{--                console.error("โหลดข้อมูลผิดพลาด", err);--}}
        {{--            }--}}
        {{--        },--}}

        {{--        tabIdToSortKey(id) {--}}
        {{--            switch (id) {--}}
        {{--                case 'topup_payment':--}}
        {{--                    return 'payment';--}}
        {{--                case 'topup_bank':--}}
        {{--                    return 'bank';--}}
        {{--                case 'topup_tw':--}}
        {{--                    return 'tw';--}}
        {{--                case 'topup_slip':--}}
        {{--                    return 'slip';--}}
        {{--                default:--}}
        {{--                    return null;--}}
        {{--            }--}}
        {{--        },--}}
        {{--        getEffectiveOrder(tab) {--}}
        {{--            const key = this.tabIdToSortKey(tab.id);--}}
        {{--            const s = key ? this.sortMap[key] : null;--}}
        {{--            const n = Number(s);--}}
        {{--            return Number.isFinite(n) ? n : (100 + (tab.order || 999));--}}
        {{--        },--}}

        {{--        --}}{{--async deSelectPro() {--}}
        {{--                --}}{{--    try {--}}
        {{--                --}}{{--        const res = await axios.post("{{ route('customer.promotion.deselect') }}", null, {--}}
        {{--                --}}{{--            headers: { 'Cache-Control': 'no-store' }, timeout: 10000,--}}
        {{--                --}}{{--        });--}}
        {{--                --}}{{--        if (res.data?.success) {--}}
        {{--                --}}{{--            window.Toast.fire({ icon: 'success', title: res.data.message });--}}
        {{--                --}}{{--            this.selectedPro = false;--}}
        {{--                --}}{{--        }--}}
        {{--                --}}{{--    } catch (err) { console.error("โหลดข้อมูลผิดพลาด", err); }--}}
        {{--                --}}{{--},--}}

        {{--        resetModal() {--}}
        {{--            this.resetCounter++;--}}
        {{--            this.selected     = null;--}}
        {{--            this.footerMsg = '';--}}
        {{--            this.selectedPro = false;--}}
        {{--            this.promotion = {name: '', min: 0};--}}
        {{--            this.error = null;--}}
        {{--        },--}}
        {{--    },--}}
        {{--    mounted() {--}}
        {{--        const onCreditUpdate = ({ profile, deposit, promotion, success } = {}) => {--}}
        {{--            const el = document.getElementById('depositModal');--}}
        {{--            const isShown = el?.classList?.contains('show');--}}
        {{--            if (!isShown || success === false) return;--}}

        {{--            // การ์ด: ถ้า payload ไม่มีอะไรเปลี่ยนเลย ก็ไม่ต้องทำงาน--}}
        {{--            if (!profile && !promotion && !deposit) return;--}}

        {{--            // ใช้งานได้เลย (initial: false)--}}
        {{--            this.applyCreditData({ profile, deposit, promotion }, { initial: false });--}}
        {{--        };--}}

        {{--        this.$root.$on?.('credit:update', onCreditUpdate);--}}
        {{--        // ✅ ใช้แค่นี้พอ--}}

        {{--        CreditStore.ensure(60 * 1000).catch(() => {});--}}

        {{--        this.$once('hook:beforeDestroy', () => {--}}
        {{--            this.$root.$off?.('credit:update');--}}
        {{--        });--}}
        {{--    },--}}
        {{--    beforeDestroy() {--}}
        {{--        this.$root.$off?.('credit:update');--}}
        {{--    },--}}

        {{--});--}}


        Vue.component('deposit-modal', {
            template: '#deposit-modal-template',
            data() {
                return {
                    member: {pro: false, pro_name: '', amount_balance: 0, withdraw_limit_amount: 0, getpro: false},
                    selectedPro: false,
                    promotion: {name: '', min: 0},
                    resetCounter: 0,
                    selected: null,          // ← ใช้ null แทน '' เพื่อบอก "ยังไม่เคยเลือก"
                    selectedTab: null,
                    footerMsg: '',
                    tabs: [
                        {
                            id: 'topup_payment',
                            title: this.trans('app.home.topup_scan'),
                            icon: 'https://img5.pic.in.th/file/secure-sv1/qr0068bdbf0cc6226d.png',
                            component: 'topup-payment',
                            order: 1
                        },
                        {
                            id: 'topup_bank',
                            title: this.trans('app.home.topup_bank'),
                            icon: 'https://img2.pic.in.th/pic/bank19da438c9e295f0b.png',
                            component: 'topup-bank',
                            order: 2
                        },
                        {
                            id: 'topup_tw',
                            title: this.trans('app.home.topup_wallet'),
                            icon: 'https://img2.pic.in.th/pic/twa6cf4bb54c16ae4b.png',
                            component: 'topup-tw',
                            order: 3
                        },
                        {
                            id: 'topup_slip',
                            title: this.trans('app.topup.slip'),
                            icon: 'https://img5.pic.in.th/file/secure-sv1/qr0068bdbf0cc6226d.png',
                            component: 'topup-slip',
                            order: 4
                        },
                    ],

                    // === เปลี่ยนค่าเริ่มต้นเป็น null เพื่อตัด race/หน้าโล่งตอนเปิดครั้งแรก ===
                    qrscan: null,
                    slip: null,
                    bank: null,
                    tw: null,

                    tabVersion: 0,
                    sortMap: {payment: null, bank: null, tw: null, slip: null},
                    loading: false,
                    error: null,
                    isInitialOpen: false,
                };
            },

            computed: {
                // helper: flag ที่ยังไม่รู้ (null) จะ "ไม่กรองออก" ชั่วคราว
                filteredTabs() {
                    const isOn = v => v === true;
                    const isUnknown = v => v === null;

                    return this.tabs
                        .filter(tab => {
                            if (tab.id === 'topup_payment') return isUnknown(this.qrscan) || isOn(this.qrscan);
                            if (tab.id === 'topup_bank') return isUnknown(this.bank) || isOn(this.bank);
                            if (tab.id === 'topup_tw') return isUnknown(this.tw) || isOn(this.tw);
                            if (tab.id === 'topup_slip') return isUnknown(this.slip) || isOn(this.slip);
                            return true;
                        })
                        .sort((a, b) => this.getEffectiveOrder(a) - this.getEffectiveOrder(b));
                },
                selectedComponent() {
                    const tab = this.filteredTabs.find(t => t.id === this.selected);
                    return tab ? tab.component : null;
                },
                selectedKey() {
                    return `${this.selected}-${this.resetCounter}`;
                },
                hasSelection() {
                    return !!this.selected;
                },
                // ใช้กำกับการแสดง splash/skeleton ตอนกำลังโหลด
                isChannelReady() {
                    const known = [this.qrscan, this.bank, this.tw, this.slip].filter(v => v !== null);
                    const anyOn = [this.qrscan, this.bank, this.tw, this.slip].some(v => v === true);
                    return known.length > 0 && anyOn;
                }
            },

            watch: {
                selected() {
                    this.footerMsg = ''
                }
            },

            methods: {
                async onSelect(tabId) {
                    const el = document.getElementById('depositModal');
                    const m = bootstrap.Modal.getInstance(el) || bootstrap.Modal.getOrCreateInstance(el);
                    m.hide();
                    setTimeout(() => this.$root.$refs.channelListModal.open(tabId), 120);
                },

                showAgain() {
                    try {
                        const cached = CreditStore.get();
                        if (cached) this.applyCreditData(cached, {initial: false});
                    } catch (e) { /* เงียบไว้ */
                    }

                    this.tabVersion++; // บังคับ re-render

                    const el = document.getElementById('depositModal');
                    const modal = bootstrap.Modal.getOrCreateInstance(el);
                    modal.show();
                },

                // === ปรับ: อุ่น cache ก่อน จากนั้น ensure() ===
                async showModal() {
                    this.resetModal();
                    this.loading = true;
                    this.isInitialOpen = true;

                    // 1) อุ่นด้วย cache เพื่อให้ปุ่มขึ้นทันที (ถ้าเคยมีข้อมูล)
                    try {
                        const cached = CreditStore.get?.();
                        if (cached) this.applyCreditData(cached, {initial: true});
                    } catch (_) {
                    }

                    // 2) sync สดด้วย ensure()
                    try {
                        const data = await CreditStore.ensure(60 * 1000);
                        this.applyCreditData(data, {initial: false});
                    } catch (e) {
                        console.error('credit ensure failed', e);
                        this.error = 'โหลดข้อมูลไม่สำเร็จ';
                    } finally {
                        this.loading = false;
                    }

                    this.$nextTick(() => {
                        const el = document.getElementById('depositModal');
                        const modal = bootstrap.Modal.getOrCreateInstance(el);
                        modal.show();
                        this.isInitialOpen = false;
                    });
                },

                coerceBool(v) {
                    if (v === true || v === false) return v;
                    const n = Number(v);
                    return Number.isFinite(n) ? n > 0 : !!v;
                },
                toIntOrNull(v) {
                    const n = Number(v);
                    return Number.isFinite(n) ? n : null;
                },

                // === ปรับ: อัปเดตเฉพาะ key ที่ส่งมา, map payment→qrscan, และรีเรนเดอร์เมื่อ deposit เปลี่ยน ===
                applyCreditData(payload, opts = {}) {
                    const {initial = false} = opts;

                    // 1) member
                    const prof = payload?.profile || {};
                    const keep = ['pro', 'pro_name', 'amount_balance', 'withdraw_limit_amount', 'getpro'];
                    const nextMember = {...this.member};
                    keep.forEach(k => {
                        if (k in prof) nextMember[k] = prof[k];
                    });
                    this.member = nextMember;

                    // 2) promotion
                    const promo = payload?.promotion;
                    if (promo && 'select' in promo) {
                        if (promo.select) {
                            this.selectedPro = true;
                            this.promotion = {name: promo.name ?? '', min: Number(promo.min ?? 0)};
                        } else {
                            this.selectedPro = false;
                            this.promotion = {name: '', min: 0};
                        }
                    }

                    // 3) deposit flags + sort
                    const dep = payload?.deposit;
                    if (dep) {
                        // อัปเดตเฉพาะ key ที่ส่งมาเท่านั้น
                        if ('bank' in dep) this.bank = this.coerceBool(dep.bank);
                        if ('tw' in dep) this.tw = this.coerceBool(dep.tw);
                        if ('slip' in dep) this.slip = this.coerceBool(dep.slip);
                        // map payment → qrscan (ถ้าส่งมา)
                        if ('payment' in dep) this.qrscan = this.coerceBool(dep.payment);

                        if ('sort' in dep && dep.sort) {
                            const s = dep.sort;
                            this.sortMap = {
                                payment: ('payment' in s) ? this.toIntOrNull(s.payment) : this.sortMap.payment,
                                bank: ('bank' in s) ? this.toIntOrNull(s.bank) : this.sortMap.bank,
                                tw: ('tw' in s) ? this.toIntOrNull(s.tw) : this.sortMap.tw,
                                slip: ('slip' in s) ? this.toIntOrNull(s.slip) : this.sortMap.slip,
                            };
                        }

                        this.tabVersion++; // บังคับ <topup-tabs> re-render เมื่อ deposit เปลี่ยนจริง
                    }

                    // initial + ไม่มีข้อมูลช่องทางเลย → ปล่อยให้ filteredTabs แสดงชั่วคราว (ไม่กรองออก)
                    const allUnknown = [this.qrscan, this.bank, this.tw, this.slip].every(v => v === null);
                    if (initial && allUnknown) {
                        // no-op เพื่อให้หน้าไม่โล่ง
                    }

                    // ถ้าแท็บที่เลือกถูกปิด ให้เคลียร์
                    const stillVisible = this.filteredTabs.some(t => t.id === this.selected);
                    if (this.selected && !stillVisible) this.selected = null;
                },

                async deSelectPro() {
                    try {
                        const res = await axios.post("{{ route('customer.promotion.deselect') }}", null, {
                            headers: {'Cache-Control': 'no-store'}, timeout: 10000,
                        });
                        if (res.data?.success) {
                            window.Toast.fire({icon: 'success', title: res.data.message});
                            this.selectedPro = false;
                            window.reLoadCredit?.(); // sync ให้ทั้งระบบ
                        }
                    } catch (err) {
                        console.error("โหลดข้อมูลผิดพลาด", err);
                    }
                },

                tabIdToSortKey(id) {
                    switch (id) {
                        case 'topup_payment':
                            return 'payment';
                        case 'topup_bank':
                            return 'bank';
                        case 'topup_tw':
                            return 'tw';
                        case 'topup_slip':
                            return 'slip';
                        default:
                            return null;
                    }
                },
                getEffectiveOrder(tab) {
                    const key = this.tabIdToSortKey(tab.id);
                    const s = key ? this.sortMap[key] : null;
                    const n = Number(s);
                    return Number.isFinite(n) ? n : (100 + (tab.order || 999));
                },

                resetModal() {
                    this.resetCounter++;
                    this.selected = null;
                    this.footerMsg = '';
                    this.selectedPro = false;
                    this.promotion = {name: '', min: 0};
                    this.error = null;

                    // ไม่รีเซ็ต qrscan/bank/tw/slip ให้เป็น false — คงค่าเดิม/ค่า null ไว้จนกว่าจะมีข้อมูลใหม่
                },
            },

            mounted() {
                const onCreditUpdate = ({profile, deposit, promotion, success} = {}) => {
                    const el = document.getElementById('depositModal');
                    const isShown = el?.classList?.contains('show');
                    if (!isShown || success === false) return;
                    if (!profile && !promotion && !deposit) return; // ไม่มีอะไรเปลี่ยน

                    this.applyCreditData({profile, deposit, promotion}, {initial: false});
                };

                this.$root.$on?.('credit:update', onCreditUpdate);

                // เตรียม cache ไว้ล่วงหน้าแบบเงียบ ๆ
                CreditStore.ensure(60 * 1000).catch(() => {
                });

                this.$once('hook:beforeDestroy', () => {
                    this.$root.$off?.('credit:update', onCreditUpdate);
                });
            },

            beforeDestroy() {
                this.$root.$off?.('credit:update');
            },
        });

        Vue.component('topup-tabs', {
            template: '#topup-tabs-template',
            props: {
                tabs: {type: Array, default: () => []},
                selected: {type: [String, null], default: null}
            },
            emits: ['select']
        });

        Vue.component('topup-payment', {
            template: '#topup-payment-template',
            data() {
                return {
                    paymentOptions: [],
                    selectedPayment: null,
                    paymentApiUrl: '',
                    item: false,
                    content: '',
                    loading: true,
                    minDeposit: 0,
                    depositAmount: '',
                    depositRange: [],
                    isSubmitting: false,

                    // internal (กัน event ผูกซ้ำ / ถอด listener ได้)
                    _modalEl: null,
                    _onModalShown: null,
                    _onModalHidden: null,
                };
            },
            mounted() {
                this.bindModalLifecycle();
                // โหลดครั้งแรกตามเดิม
                // this.loadBank({ refresh: true });
            },
            beforeDestroy() {
                this.unbindModalLifecycle();
            },
            computed: {
                minDepositPlaceholder() {
                    return this.trans('app.topup.min_deposit', {amount: this.intToMoney(this.minDeposit)});
                },
            },
            methods: {
                // =========================
                // Modal lifecycle (สำคัญ)
                // =========================
                bindModalLifecycle() {
                    // หา modal parent ที่ครอบ component นี้อยู่ (Bootstrap)
                    const el = this.$el && this.$el.closest ? this.$el.closest('.modal') : null;
                    if (!el) return;

                    this._modalEl = el;

                    this._onModalShown = () => {
                        // เปิด modal ทุกครั้ง: รีเฟรชข้อมูล bank เพื่อให้ min_deposit ล่าสุด
                        this.loadBank({ refresh: true });
                    };

                    this._onModalHidden = () => {
                        // ปิด modal: เคลียร์ state ที่ไม่ควรค้าง
                        this.depositAmount = '';
                        this.isSubmitting = false;
                    };

                    el.addEventListener('shown.bs.modal', this._onModalShown);
                    el.addEventListener('hidden.bs.modal', this._onModalHidden);
                },

                unbindModalLifecycle() {
                    const el = this._modalEl;
                    if (!el) return;

                    if (this._onModalShown) el.removeEventListener('shown.bs.modal', this._onModalShown);
                    if (this._onModalHidden) el.removeEventListener('hidden.bs.modal', this._onModalHidden);

                    this._modalEl = null;
                    this._onModalShown = null;
                    this._onModalHidden = null;
                },

                // =========================
                // Helpers
                // =========================
                toNumber(v, fallback = 0) {
                    const n = Number(v);
                    return Number.isFinite(n) ? n : fallback;
                },

                adjustAmount(event) {
                    const value = this.toNumber(event.currentTarget.dataset.value, 0);
                    const operator = event.currentTarget.dataset.operator;

                    const current = this.toNumber(this.depositAmount, 0);
                    let next = current;

                    if (operator === '+') next = current + value;
                    else if (operator === '-') next = current - value;

                    if (next < 0) next = 0;
                    this.depositAmount = next;
                },

                intToMoney(n) {
                    const v = this.toNumber(n, 0);
                    return v.toLocaleString(undefined, {minimumFractionDigits: 2});
                },

                preventDot(event) {
                    if (event.key === '.' || event.key === ',' || event.key === 'e') event.preventDefault();
                },

                formatAmount(v) {
                    const n = this.toNumber(v, NaN);
                    return isNaN(n) ? v : n.toLocaleString('th-TH');
                },

                labelOption(o) {
                    if (!o) return '';
                    const name = o.name ?? '';
                    const md = o.min_deposit;
                    if (md == null) return name;
                    return `ฝากขั้นต่ำ ${this.formatAmount(md)}`;
                },

                // =========================
                // Selection
                // =========================
                selectPayment(option) {
                    this.selectedPayment = option || null;

                    const md = this.toNumber(option?.min_deposit, 0);
                    this.minDeposit = md;

                    this.depositAmount = '';
                    this.depositRange = option?.deposit_range || [];
                    this.paymentApiUrl = option?.payment_url || '';

                    this.$nextTick(() => setTimeout(() => this.$refs.depositInput?.focus(), 50));
                },

                syncSelectedFromOptions() {
                    // ถ้าเคยเลือกไว้ ให้ sync ไปยัง object ใหม่จาก paymentOptions
                    const selectedId = this.selectedPayment?.id;
                    if (!selectedId) return;

                    const found = (this.paymentOptions || []).find(o => o && o.id === selectedId);
                    if (!found) return;

                    // อัปเดต reference + ค่าที่เกี่ยวข้อง โดยไม่ reset ทุกอย่างหนักมือเกินไป
                    this.selectedPayment = found;

                    const newMin = this.toNumber(found.min_deposit, 0);
                    this.minDeposit = newMin;

                    this.depositRange = found.deposit_range || [];
                    this.paymentApiUrl = found.payment_url || '';

                    // ถ้าผู้ใช้กรอกไว้ แล้วขั้นต่ำเพิ่มจนไม่ผ่าน -> เคลียร์ให้ชัด
                    const currentAmount = this.toNumber(this.depositAmount, 0);
                    if (currentAmount > 0 && currentAmount < newMin) {
                        this.depositAmount = '';
                    }
                },

                // =========================
                // Data load
                // =========================
                async loadBank({ refresh = false } = {}) {
                    // หมายเหตุ: ใน modal เรา refresh ทุกครั้งที่เปิด เพื่อให้ min_deposit ล่าสุด
                    try {
                        if (refresh) {
                            this.loading = true;
                        }

                        const res = await axios.post("{{ route('customer.slip.loadbank') }}", {method: 'payment'}, {
                            headers: {'Cache-Control': 'no-store'},
                            timeout: 10000,
                        });

                        if (res.data.success && res.data.bank) {
                            this.paymentOptions = Object.values(res.data.bank);

                            if (this.paymentOptions.length === 1) {
                                // 1 ตัว: เลือกอัตโนมัติ
                                this.selectPayment(this.paymentOptions[0]);
                            } else {
                                // หลายตัว: ถ้าเคยเลือกไว้ ให้ sync ค่าใหม่ (min_deposit เปลี่ยนตามที่นี่)
                                this.syncSelectedFromOptions();
                            }

                            this.item = true;
                        } else {
                            this.paymentOptions = [];
                            this.item = false;
                        }
                    } catch (e) {
                        this.paymentOptions = [];
                        this.item = false;
                    } finally {
                        this.loading = false;
                    }
                },

                // =========================
                // Submit
                // =========================
                async submitDeposit(force = false) {
                    try {
                        this.isSubmitting = true;

                        const amount = this.toNumber(this.depositAmount, 0);
                        const min = this.toNumber(this.minDeposit, 0);

                        if (!amount || amount < min) {
                            window.Toast.fire({
                                icon: 'info',
                                title: this.trans('app.withdraw.wrong_amount') || 'กรุณากรอกจำนวนเงินที่ถูกต้อง'
                            });
                            return;
                        }

                        const res = await axios.post(this.paymentApiUrl, {amount, force}, {timeout: 10000});

                        if (res.data.success) {
                            const msg = res.data.msg || this.trans('app.topup.create');
                            window.Toast.fire({icon: 'success', title: msg, customClass: {popup: 'my-toast'}});
                            setTimeout(() => {
                                if(res.data.target === 'blank'){
                                    window.open(res.data.url, '_blank');
                                }else {
                                    window.location.href = res.data.url;
                                }
                            }, 500);
                        } else if (res.data.status === 'has_pending') {
                            const d = res.data.data;
                            const result = await Swal.fire({
                                title: this.trans('app.topup.dup_topic'),
                                html: `
                            <p>${this.trans('app.topup.amount')} <strong>${d.amount}</strong></p>
                            <p>${this.trans('app.topup.amount_pay')} <strong>${d.payamount}</strong></p>
                            <p>${this.trans('app.topup.txnid')} <strong>${d.txid}</strong></p>
                            <p>${this.trans('app.topup.dup_detail')}</p>
                            <p><small>${this.trans('app.topup.dup_detail_2')}</small></p>
                        `,
                                icon: 'warning',
                                showCloseButton: true,
                                showCancelButton: true,
                                confirmButtonText: this.trans('app.topup.confirm_new'),
                                cancelButtonText: this.trans('app.topup.view_old'),
                                reverseButtons: true
                            });

                            if (result.isConfirmed) await this.submitDeposit(true);
                            else if (result.dismiss === Swal.DismissReason.cancel) window.location.href = d.url;
                        } else {
                            window.Toast.fire({
                                icon: 'error',
                                title: res.data.msg || this.trans('app.status.error'),
                                customClass: {popup: 'my-toast'}
                            });
                        }
                    } catch (err) {
                        let message = this.trans('app.status.error');
                        if (err.code === 'ECONNABORTED') message = 'การเชื่อมต่อช้า หรือไม่ตอบสนอง กรุณาลองใหม่อีกครั้ง';
                        else if (err?.response?.data?.message) message = err.response.data.message;

                        window.Toast.fire({icon: 'error', title: message, customClass: {popup: 'my-toast'}});
                        console.error(err);
                    } finally {
                        this.isSubmitting = false;
                    }
                }
            }
        });

        Vue.component('topup-bank', {
            template: '#topup-bank-template',
            data() {
                return {
                    items: [],
                    content: '',
                    loading: true,
                };
            },
            mounted() {
                this.loadBank();
            },
            methods: {
                copylink(acc_no) {
                    navigator.clipboard.writeText(acc_no);
                    $(".myAlert-top").show();
                    setTimeout(() => $(".myAlert-top").hide(), 1000);
                },

                async loadBank() {
                    this.loading = true;
                    this.items = [];
                    try {
                        const res = await axios.post("{{ route('customer.slip.loadbank') }}", {method: 'bank'}, {
                            headers: {'Cache-Control': 'no-store'},
                            timeout: 10000,
                        });
                        this.items = res.data && res.data.success ? Object.values(res.data.bank || {}) : [];
                    } catch (err) {
                        console.error('โหลดข้อมูลล้มเหลว', err);
                    }
                    this.loading = false;
                },

                // === NEW: แปลงจำนวนเงินให้อ่านง่าย ===
                formatMoney(value, {currency = null} = {}) {
                    // รับทั้ง string/number; คืน string พร้อม 2 ตำแหน่งทศนิยม
                    const num = Number(value);
                    if (!isFinite(num)) return String(value ?? '');
                    if (currency) {
                        // อยากได้สัญลักษณ์สกุลเงิน → ส่ง { currency: 'THB' }
                        return new Intl.NumberFormat('th-TH', {style: 'currency', currency}).format(num);
                    }
                    // ค่าเริ่มต้น: ไม่ติดสกุลเงิน แต่คงทศนิยม 2 ตำแหน่ง
                    return new Intl.NumberFormat('th-TH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(num);
                },

                shouldShowMinDeposit(item) {
                    const val = Number(item.deposit_min);
                    return !isNaN(val) && val > 0;
                },

                minDepositText(item) {
                    const val = Number(item.deposit_min);
                    if (!isFinite(val) || val <= 0) return ''; // กรองค่า 0, null, NaN
                    const base = this.trans ? this.trans('app.topup.min_deposit') : 'ยอดฝากขั้นต่ำ :amount';
                    const amountTxt = this.formatMoney(val);
                    return String(base).replace(':amount', amountTxt);
                },

                // NOTE: สมมุติว่ามี this.trans อยู่แล้วจากโค้ดเดิมของคุณ
                // ถ้าไม่มี ให้ผูก method trans(key) เองให้คืน key เดิม หรือข้อความ fallback
            }
        });

        Vue.component('topup-tw', {
            template: '#topup-tw-template',
            data() {
                return {items: [], content: '', loading: true};
            },
            mounted() {
                this.loadBank();
            },
            methods: {
                copylink(acc_no) {
                    navigator.clipboard.writeText(acc_no);
                    $(".myAlert-top").show();
                    setTimeout(() => $(".myAlert-top").hide(), 1000);
                },
                async loadBank() {
                    this.loading = true;
                    this.items = [];
                    try {
                        const res = await axios.post("{{ route('customer.slip.loadbank') }}", {method: 'tw'}, {
                            headers: {'Cache-Control': 'no-store'},
                            timeout: 10000,
                        });
                        this.items = res.data && res.data.success ? Object.values(res.data.bank || {}) : [];
                    } catch (err) {
                        console.error('โหลดข้อมูลล้มเหลว', err);
                    }
                    this.loading = false;
                },

                // === NEW: แปลงจำนวนเงินให้อ่านง่าย ===
                formatMoney(value, {currency = null} = {}) {
                    // รับทั้ง string/number; คืน string พร้อม 2 ตำแหน่งทศนิยม
                    const num = Number(value);
                    if (!isFinite(num)) return String(value ?? '');
                    if (currency) {
                        // อยากได้สัญลักษณ์สกุลเงิน → ส่ง { currency: 'THB' }
                        return new Intl.NumberFormat('th-TH', {style: 'currency', currency}).format(num);
                    }
                    // ค่าเริ่มต้น: ไม่ติดสกุลเงิน แต่คงทศนิยม 2 ตำแหน่ง
                    return new Intl.NumberFormat('th-TH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(num);
                },

                shouldShowMinDeposit(item) {
                    const val = Number(item.deposit_min);
                    return !isNaN(val) && val > 0;
                },

                minDepositText(item) {
                    const val = Number(item.deposit_min);
                    if (!isFinite(val) || val <= 0) return ''; // กรองค่า 0, null, NaN
                    const base = this.trans ? this.trans('app.topup.min_deposit') : 'ยอดฝากขั้นต่ำ :amount';
                    const amountTxt = this.formatMoney(val);
                    return String(base).replace(':amount', amountTxt);
                },
            }
        });

        Vue.component('topup-slip', {
            template: '#topup-slip-template',
            data() {
                return {item: '', content: '', loading: true, _dropInited: false};
            },
            mounted() {
                this.loadBank();
            },
            activated() {
                // เรียก init dropzone ครั้งแรกเมื่อคอมโพเนนต์ถูกแสดง (keep-alive)
                if (!_dropInitedSafe(this)) {
                    this.$nextTick(() => this.$refs?.upload?.init?.());
                    this._dropInited = true;
                }
            },
            methods: {
                copylink(acc_no) {
                    navigator.clipboard.writeText(acc_no);
                    $(".myAlert-top").show();
                    setTimeout(() => $(".myAlert-top").hide(), 1000);
                },
                async loadBank() {
                    this.loading = true;
                    this.item = '';
                    try {
                        const res = await axios.post("{{ route('customer.slip.loadbank') }}", {method: 'slip'}, {
                            headers: {'Cache-Control': 'no-store'}, timeout: 10000,
                        });
                        this.item = res.data.success ? res.data.bank : '';
                    } catch (err) {
                        console.error('โหลดข้อมูลล้มเหลว', err)
                    }
                    this.loading = false;
                }
            }
        });

        // helper ปลอดภัยเพื่อเช็คสถานะ inited
        function _dropInitedSafe(vm) {
            try {
                return !!vm._dropInited;
            } catch {
                return false;
            }
        }
    </script>
@endpush
