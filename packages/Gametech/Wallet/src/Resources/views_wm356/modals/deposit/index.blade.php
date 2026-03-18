@push('scripts')
    {{--	<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css">--}}
    <style type="text/css">
        .table-responsive-new {
            max-height: 300px;
            overflow-y: auto;
            overflow-x: unset;
        }

        .dropzone {
            border: 2px dashed #5c6ac4;
            border-radius: 10px;
            background: #f9fafe;
            padding: 30px;
            text-align: center;
            font-size: 16px;
            color: #5c6ac4;
            transition: background 0.3s;
            cursor: pointer;
            min-height: 180px;
            position: relative;
        }

        .dropzone:hover {
            background: #eef2fc;
        }

        .dropzone .dz-message {
            margin: 0;
            font-weight: bold;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .dropzone .dz-message i {
            font-size: 3rem;
            color: #5c6ac4;
        }

        .img25 {
            width: 50px;
            height: 50px;
        }

        .-promotion-box-wrapper small {
            font-size: 50%;
        }

        .-bank-info-container span {
            font-size: 70%;
        }

        .x-deposit-promotion.-v2.-slide {
            display: flex;
            position: relative;
            max-height: unset;
            overflow: unset;
            width: 100%;
            padding-left: 4rem !important;
            right: 2rem !important;
        }


        .x-deposit-promotion.-v2.-slide .-promotion-box-wrapper {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: calc(33.33333% - 6px);
            max-width: calc(33.33333% - 6px);
            border-radius: 5px;
            background: #333;
            border: 1px solid #686868;
            padding: 0 !important;
        }
    </style>
    {{--	<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>--}}
    <script type="text/x-template" id="deposit-modal-template">

        <div class="x-modal modal -v2 -with-backdrop -with-separator -with-more-than-half-size"
             id="depositModal"
             tabindex="-1"
             role="dialog"
             data-loading-container=".modal-body"
             data-ajax-modal-always-reload="true"
             data="deposit"
             data-container="#depositModal"
             aria-hidden="false">
            <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-deposit -modal-mobile"
                 role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-header -modal-header">
                        <h3 class="x-title-modal m-auto">
                            {{ __('app.home.topup_channel') }}
                        </h3>
                    </div>


                    <div class="modal-body -modal-body">
                        <div class="x-deposit-form -v2">
                            <div class="-deposit-container">
                                <div data-animatable="fadeInModal"
                                     class="order-lg-2 -form order-0 animated fadeInModal">
                                    <div class="container">

                                        <!-- ปุ่มเลือกหัวข้อ -->
                                        <topup-tabs :tabs="filteredTabs" :selected="selected"
                                                    @select="selected = $event"></topup-tabs>
                                        {{--										<topup-tabs :tabs="tabs" :selectedTab="selectedTab" @select="selectedTab = $event"></topup-tabs>--}}


                                        <component :is="selectedComponent" :key="selectedKey"
                                                   @footer-message="footerMsg = $event"/>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer -modal-footer justify-content-center">
                        <div class="text-center" v-if="selected === 'topup_tw' && footerMsg">@{{ footerMsg }}</div>

                        <div class="text-warning fw-light w-100 text-center" v-if="selectedPro">
                            {{ __('app.promotion.select') }} <span v-text="promotion.name"></span></br>
                            {{ __('app.promotion.min') }} <span v-text="promotion.min"></span></br>
                            <div class="d-grid gap-2 mt-1">
                                <button class="btn btn-danger"
                                        @click="deSelectPro">{{ __('app.promotion.delete') }}</button>
                            </div>
                        </div>
                        <div class="text-warning fw-light w-100 text-center" v-else>
                            {{ __('app.promotion.suggest') }}
                            @if($userdata->pro == true)
                                <p class="text-center text-warning" style="font-size: smaller">
                                    {{ $userdata->pro_name }}
                                </p>
                                <p class="text-center text-warning" style="font-size: smaller">
                                    {{ __('app.home.withdraw_turn') }} {{number_format($userdata->turnpro,2) }}
                                    /
                                    {{ __('app.home.withdraw_max') }} {{ number_format($userdata->limit,2) }}</p>
                            @endif
                        </div>
                    </div>
                    {{--                        <div class="text-center">{{ __('app.promotion.suggest') }} </div>--}}
                </div>

            </div>
        </div>
        </div>

    </script>


    <script type="text/x-template" id="topup-tabs-template">
        <div class="x-deposit-promotion-outer-container js-scroll-ltr -fade -on-left -on-right">
            <div class="x-deposit-promotion -v2 -slide pt-0 -has-promotion"
                 data-scroll-booster-container=".x-deposit-promotion-outer-container"
                 data-scroll-booster-content=".x-deposit-promotion"
                 style="transform: translate(0px, 0px);">
                <div v-for="tab in tabs" :key="tab.id" class="-promotion-box-wrapper">
                    <button class="btn btn-for-deposit"
                            @click="$emit('select', tab.id)"
                            :class="{ 'is-selected': selected === tab.id }">
                        <img :src="tab.icon" class="-img img25"/><br>
                        <small class="-title" v-text="tab.title"></small>
                    </button>
                </div>
            </div>
        </div>
    </script>


    <script type="text/x-template" id="topup-bank-template">
        <div v-if="loading" class="text-center">{{ __('app.status.loading') }}</div>
        <div v-else>

            <div v-if="items">
                <div id="topup_bank" class="-deposit-form-inner-wrapper table-responsive-new" data-animatable="fadeInUp"
                     data-delay="150" v-if="items">

                    @if($webconfig->deposit_min > 0)
                        <div class="text-center">
                            <div class="min-deposit-box text-center text-muted py-2 px-3 mt-2">
                                {{ __('app.topup.min_deposit',['amount' => $webconfig->deposit_min]) }}
                            </div>
                        </div>
                    @endif

                    <div class="-bank-info-container mt-3 ml-3 mr-3" v-for="(item, index) in items">
                        <div class="x-customer-bank-info-container -v2">
                            <div class="media m-auto">
                                <img loading="lazy" fetchpriority="low"
                                     :src="item.bank_pic"
                                     class="-img rounded-circle" width="50" height="50"
                                     alt="bank-ktb"/>
                                <div class="-content-wrapper">
                                    <span class="-name" v-text="item.bank_name"></span>
                                    <span class="-name" v-text="item.acc_name"></span>
                                    <span class="-number" v-text="item.acc_no"></span>
                                    <button @click="copylink(item.acc_no)" class="btncopy btn btn-flat"
                                            :data-clipboard-text="item.acc_no"><i
                                                class="fa fa-copy"></i> {{ __('app.con.copy') }}
                                    </button>
                                    <span class="-name" v-text="'ยอดฝากขั้นต่ำ ' + item.deposit_min + ' บาท'"
                                          v-if="item.deposit_min > 0"></span>
                                </div>
                            </div>

                            <div class="bank-info mt-2" v-if="item.qrcode">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="d-flex justify-content-center mb-2" style="gap: 8px;">
                                        <!-- ปุ่มดาวน์โหลด QR -->
                                        <a
                                                class="btn btn-outline-secondary shadow"
                                                :href="item.qr_pic"
                                                :download="`qr-${item.acc_no}.png`"
                                                target="_blank"
                                                rel="noopener"
                                                v-if="item.qr_pic"
                                        >
                                            <i class="bi bi-download"></i> {{ __('app.topup.qrscan_download') }}
                                        </a>
                                        <!-- ปุ่มแสดง QR SCAN -->
                                        <button
                                                class="btn btn-outline-secondary shadow"
                                                type="button"
                                                data-toggle="collapse"
                                                :data-target="`#qrzone-pic-${index}`"
                                                aria-expanded="true"
                                                :aria-controls="`qrzone-pic-${index}`">
                                            {{ __('app.topup.qrscan') }}
                                        </button>
                                    </div>
                                    <div class="collapse w-100" :id="`qrzone-pic-${index}`">
                                        <div class="card card-body d-flex justify-content-center align-items-center">
                                            <img :src="item.qr_pic" class="img-fluid" style="max-width:220px;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

            <div v-else class="text-center text-muted py-4">
                {{ __('app.home.no_list') }}
            </div>

        </div>

    </script>


    <script type="text/x-template" id="topup-tw-template">
        <div v-if="loading" class="text-center">{{ __('app.status.loading') }}</div>
        <div v-else>

            <div v-if="items">
                <div id="topup_tw" class="-deposit-form-inner-wrapper table-responsive-new" data-animatable="fadeInUp"
                     data-delay="150" v-if="items">

                    @if($webconfig->deposit_min > 0)
                        <div class="text-center">
                            <div class="min-deposit-box text-center text-muted py-2 px-3 mt-2">
                                {{ __('app.topup.min_deposit',['amount' => $webconfig->deposit_min]) }}
                            </div>
                        </div>
                    @endif


                    <div class="-bank-info-container mt-3 ml-3 mr-3" v-for="item in items">
                        <div class="x-customer-bank-info-container -v2">
                            <div class="media m-auto">
                                <img loading="lazy" fetchpriority="low"
                                     :src="item.bank_pic"
                                     class="-img rounded-circle" width="50" height="50"
                                     alt="bank-ktb"/>
                                <div class="-content-wrapper">
                                    <span class="-name" v-text="item.bank_name"></span>
                                    <span class="-name" v-text="item.acc_name"></span>
                                    <span class="-number" v-text="item.acc_no"></span>
                                    <button @click="copylink(item.acc_no)" class="btncopy btn btn-flat"
                                            :data-clipboard-text="item.acc_no"><i
                                                class="fa fa-copy"></i> {{ __('app.con.copy') }}
                                    </button>
                                    <span class="-name" v-text="'ยอดฝากขั้นต่ำ ' + item.deposit_min + ' บาท'"
                                          v-if="item.deposit_min > 0"></span>
                                </div>
                            </div>
                            <div class="media m-auto" v-if="item.qrcode">
                                <img loading="lazy" fetchpriority="low"
                                     :src="item.qr_pic"
                                     class="img-fluid"
                                     alt="bank-ktb"/>
                            </div>
                            <div v-else></div>
                        </div>


                    </div>
                </div>
            </div>

            <div v-else class="text-center text-muted py-4">
                {{ __('app.home.no_list') }}
            </div>
        </div>

    </script>

    <script type="text/x-template" id="topup-slip-template">
        <div v-if="loading" class="text-center"{{ __('app.status.loading') }}</div>
        <div v-else>
            <div id="topup_slip" class="-deposit-form-inner-wrapper table-responsive-new" v-if="item">
                <div class="-bank-info-container mt-3 ml-3 mr-3">
                    <div class="x-customer-bank-info-container -v2">
                        <div class="media m-auto">
                            <img loading="lazy" fetchpriority="low"
                                 :src="item.bank_pic"
                                 class="-img rounded-circle" width="50" height="50"
                                 alt="bank-ktb"/>
                            <div class="-content-wrapper">
                                <span class="-name" v-text="item.bank_name"></span>
                                <span class="-name" v-text="item.acc_name"></span>
                                <span class="-number" v-text="item.acc_no"></span>
                                <button @click="copylink(item.acc_no)" class="btncopy btn btn-flat"
                                        :data-clipboard-text="item.acc_no"><i
                                            class="fa fa-copy"></i> {{ __('app.con.copy') }}
                                </button>
                            </div>
                        </div>
                        <div class="media m-auto" v-if="item.qrcode">
                            <img loading="lazy" fetchpriority="low"
                                 :src="item.qr_pic"
                                 class="img-fluid"
                                 alt="bank-ktb"/>
                        </div>
                        <div v-else></div>
                    </div>
                </div>

                <div class="-bank-info-container mt-3 ml-3 mr-3">
                    <div class="x-customer-bank-info-container -v2">
                        <upload-slip :account-info="item"></upload-slip>
                    </div>
                </div>

            </div>
        </div>

    </script>

    <script type="text/x-template" id="upload-slip-template">
        <form ref="dropzoneRef" class="dropzone">
            <div class="dz-message">
                <i class="fas fa-upload"></i>
                <span>ลากรูปสลิปมาวาง หรือคลิกเพื่ออัปโหลด</span>
            </div>
        </form>
    </script>

    <script type="text/x-template" id="topup-payment-template">
        <div v-if="loading" class="text-center text-muted py-4">{{ __('app.status.loading') }}</div>
        <div v-else>

            <div v-if="item">

                <div id="topup_payment" class="-deposit-form-inner-wrapper table-responsives" data-animatable="fadeInUp"
                     data-delay="150">


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

                    <div class="text-center" v-if="selectedPayment">
                        <div class="min-deposit-box text-center text-muted py-2 px-3 mt-2"
                             v-text="'ยอดฝากขั้นต่ำ ' + selectedPayment.min_deposit + ' บาท'">

                        </div>
                    </div>

                    <div class="-bank-info-container mt-3 ml-3 mr-3" v-if="selectedPayment">
                        <form @submit.prevent.stop="submitDeposit">
                            <div class="-fake-bg-bottom-wrapper">
                                <div class="x-modal-separator-container">
                                    <div class="-top">
                                        <div class="-promotion-intro-deposit -spacer">
                                            <div class="js-promotion-active-html"></div>
                                        </div>

                                        <div class="pt-2">
                                            <div
                                                    class="-x-input-icon x-input-operator mb-3 flex-column">
                                                {{--                                                <button type="button"--}}
                                                {{--                                                        class="-icon-left -btn-icon js-adjust-amount-by-operator"--}}
                                                {{--                                                        :data-operator="'-'"--}}
                                                {{--                                                        :data-value="1"--}}
                                                {{--                                                        @click="adjustAmount($event)">--}}
                                                {{--                                                    <i class="fas fa-minus-circle"></i>--}}
                                                {{--                                                </button>--}}

                                                <input
                                                        v-model="depositAmount"
                                                        ref="depositInput"
                                                        step="1"
                                                        id="deposit"
                                                        type="number"
                                                        autocomplete="off"
                                                        placeholder="{{ __('app.input.fill',['field' => __('app.topup.amount')]) }}"
                                                        required
                                                        min="minDeposit"
                                                        class="form-control"
                                                        @keydown="preventDot"
                                                >

                                                {{--                                                <button type="button"--}}
                                                {{--                                                        :data-operator="'+'"--}}
                                                {{--                                                        :data-value="1"--}}
                                                {{--                                                        @click="adjustAmount($event)"--}}
                                                {{--                                                        class="-icon-right -btn-icon js-adjust-amount-by-operator"--}}
                                                {{--                                                >--}}
                                                {{--                                                    <i class="fas fa-plus-circle"></i>--}}
                                                {{--                                                </button>--}}
                                            </div>
                                        </div>

                                        {{--                                        <div class="">--}}
                                        {{--                                            <div class="x-select-amount js-quick-amount -v2"--}}
                                        {{--                                                 data-target-input="#deposit">--}}

                                        {{--                                                <div class="-amount-container"--}}
                                        {{--                                                     v-for="amt in depositRange" :key="amt">--}}
                                        {{--                                                    <button type="button"--}}
                                        {{--                                                            :class="{ 'active': parseInt(depositAmount) === amt }"--}}
                                        {{--                                                            @click="depositAmount = amt"--}}
                                        {{--                                                            class="btn btn-block -btn-select-amount"--}}
                                        {{--                                                            :data-amount="amt">--}}
                                        {{--                                                        <span class="-no">@{{ amt.toLocaleString() }}</span>--}}
                                        {{--                                                    </button>--}}
                                        {{--                                                </div>--}}

                                        {{--                                            </div>--}}
                                        {{--                                        </div>--}}

                                        <div class="">
                                            <hr class="-liner"/>
                                        </div>
                                        <p style="margin:0 auto;text-align:center;font-size:smaller;"> {{ __('app.topup.maintenance') }}</p>

                                        <div class="text-center mt-3 pb-3">
                                            <button type="submit"
                                                    class="btn btn-primary btn-custom-primary w-100 rounded-pill"
                                                    :disabled="isSubmitting"
                                                    style="max-width: 20em;">
                                                {{ __('app.home.deposit') }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="-bottom"></div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>

            </div>

            <div v-else class="text-center text-muted py-4">
                {{ __('app.home.no_list') }}
            </div>

        </div>
    </script>


    <script type="module">
        function getUTCISOStringFromThailandTime() {
            const bangkokDate = new Date().toLocaleString("en-US", {timeZone: "Asia/Bangkok"});
            const date = new Date(bangkokDate);
            return date.toISOString(); // จะได้แบบ "2025-10-05T07:48:00.000Z" (ตรงกับ 14:48 GMT+7)
        }

        Dropzone.autoDiscover = false;

        Vue.component('upload-slip', {
            props: ['accountInfo'], // รับ object จาก parent
            template: '#upload-slip-template',
            data() {
                return {
                    dz: null,
                };
            },
            mounted() {
                this.initDropzone();
            },
            methods: {
                initDropzone() {
                    const self = this;
                    this.dz = new Dropzone(this.$refs.dropzoneRef, {
                        url: "{{ route('customer.slip.upload') }}",
                        method: 'post',
                        maxFiles: 1,
                        acceptedFiles: 'image/*',
                        addRemoveLinks: true,
                        autoProcessQueue: true,
                        init: function () {
                            this.on('sending', function (file, xhr, formData) {
                                formData.append('_token', '{{ csrf_token() }}');


                                const info = {
                                    code: self.accountInfo?.code || ''
                                };

                                const payload = {
                                    checkDuplicate: true,
                                    checkReceiver: [{
                                        accountType: self.accountInfo?.slip_bank || '',
                                        accountNumber: self.accountInfo?.acc_no || ''
                                    }],
                                    checkDate: {
                                        type: 'gte',
                                        date: new Date().toISOString()
                                    }
                                };

                                formData.append('payload', JSON.stringify(payload));
                                formData.append('info', JSON.stringify(info));

                            });

                            this.on('success', function (file, response) {
                                this.removeFile(file);

                                if (response.code === '200200') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'ตรวจสอบสำเร็จ',
                                        text: 'ระบบได้รับสลิปเรียบร้อยแล้ว',
                                        timer: 2500,
                                        showConfirmButton: false
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'ข้อมูลสลิปไม่ถูกต้อง',
                                        text: 'โปรดตรวจสอบ หรือติดต่อทีมงาน',
                                        timer: 2500,
                                        showConfirmButton: false
                                    });
                                }

                            });

                            this.on('error', function (file, errorMessage) {
                                this.removeFile(file);
                                Swal.fire({
                                    icon: 'info',
                                    title: 'ผิดพลาด',
                                    text: 'เกิดปัญหาบางประการ โปรดลองใหม่',
                                    timer: 2500,
                                    showConfirmButton: false
                                });
                            });
                        },
                    });
                },
                resetUpload() {
                    if (this.dz) {
                        this.dz.removeAllFiles(true);
                    }
                }
            }
        });


        Vue.component('deposit-modal', {
            template: '#deposit-modal-template',

            data() {
                return {
                    selectedPro: false,
                    promotion: {
                        name: '',
                        min: 0
                    },
                    resetCounter: 0,
                    selected: null,
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
                        // {
                        //     id: 'topup_slip',
                        //     title: this.trans('app.topup.slip'),
                        //     icon: 'https://img5.pic.in.th/file/secure-sv1/qr0068bdbf0cc6226d.png',
                        //     component: 'topup-slip'
                        // },


                    ],
                    qrscan: window.QRSCAN_ENABLED ?? false,
                    bank: window.BANK_ENABLED ?? false,
                    tw: window.TW_ENABLED ?? false,
                };
            },
            computed: {
                filteredTabs() {
                    // ถ้า qrscan = false จะ filter ทิ้ง topup_payment ออก
                    return this.tabs
                        .filter(tab => {
                            if (tab.id === 'topup_payment' && !this.qrscan) return false;
                            if (tab.id === 'topup_bank' && !this.bank) return false;
                            if (tab.id === 'topup_tw' && !this.tw) return false;
                            return true;
                        })
                        .sort((a, b) => a.order - b.order); // sort by order
                },
                selectedComponent() {
                    const tab = this.filteredTabs.find(t => t.id === this.selected);
                    return tab ? tab.component : null;
                },
                selectedKey() {
                    return `${this.selected}-${this.resetCounter}`;
                }
            },
            watch: {
                selected() {
                    this.footerMsg = ''
                }   // เปลี่ยนแท็บเมื่อไหร่ เคลียร์ footer
            },
            methods: {
                resetModal() {
                    // เคลียร์ค่า + รีโหลด
                    this.resetCounter++;
                    this.selected = ''; // clear tab
                },
                trans(key, replace = {}) {
                    var translation = key.split('.').reduce((t, i) => t[i] || null, window.i18n);

                    for (var placeholder in replace) {
                        translation = translation.replace(`:${placeholder}`, replace[placeholder]);
                    }
                    return translation;
                },
                async deSelectPro() {
                    try {
                        const res = await axios.post("{{ route('customer.promotion.deselect') }}");
                        if (res.data.success) {
                            window.Toast.fire({
                                icon: 'success',
                                title: res.data.message
                            });
                            this.selectedPro = false;
                        }

                    } catch (err) {
                        console.error("โหลดข้อมูลผิดพลาด", err);
                    } finally {

                    }

                },
                async loadMemberData() {

                    try {
                        const res = await axios.get("{{ route('customer.home.credit') }}");
                        if (res.data.success) {
                            this.selectedPro = res.data.promotion.select;
                            this.promotion = res.data.promotion;
                        }
                    } catch (err) {
                        console.error("โหลดข้อมูลผิดพลาด", err);
                    } finally {

                    }
                },
                resetModal() {
                    // เคลียร์ค่า + รีโหลด
                    this.resetCounter++;
                    this.selected = ''; // clear tab
                    this.promotion = ''; // clear tab
                    this.selectedPro = false;
                },
                async showModal() {
                    this.resetModal();
                    this.loading = true;
                    try {
                        await this.loadMemberData();           // โหลดก่อน
                        this.$nextTick(() => openModalById('depositModal')); // แล้วค่อยเปิด
                    } catch (e) {
                        this.error = 'โหลดข้อมูลไม่สำเร็จ';
                        this.$nextTick(() => openModalById('depositModal')); // ยังเปิดเพื่อแจ้ง error ได้
                    } finally {
                        this.loading = false;
                    }


                    // await this.loadMemberData(); // โหลดข้อมูลทุกครั้งที่เปิด
                    // $(document.getElementById('depositModal')).modal('show');
                    // const modal = new bootstrap.Modal(document.getElementById('depositModal'));
                    // modal.show();
                },
            },
            mounted() {


                $('#depositModal').on('shown.bs.modal', () => {
                    if (!window._clipboardInitialized) {
                        new ClipboardJS('.btncopy', {
                            container: document.getElementById('depositModal')
                        });
                        window._clipboardInitialized = true;
                    }

                    this.resetModal();
                });


            },
        });

        Vue.component('topup-tabs', {
            props: ['tabs', 'selected'],
            template: '#topup-tabs-template'
        });


        Vue.component('topup-bank', {
            template: '#topup-bank-template',
            data() {
                return {
                    item: false,
                    items: '',
                    content: '',
                    loading: true
                };
            },
            mounted() {
                this.loadBank();

            },
            methods: {
                fadeIn() {
                    $("[data-animatable]").each(function () {
                        var $el = $(this);

                        // ตั้ง delay เล็กน้อยก่อนสร้าง waypoint (เพื่อให้ DOM พร้อม)
                        setTimeout(function () {
                            new Waypoint({
                                element: $el[0], // element DOM จริง
                                handler: function () {
                                    // เมื่อ scroll เข้ามาใน viewport
                                    setTimeout(function () {
                                        // เรียก animateCss ด้วยชื่อ animation จาก data-animatable หรือ default เป็น fadeInUp
                                        $el.animateCss($el.data("animatable") || "fadeInUp");
                                    }, $el.data("delay") || 50);

                                    // ป้องกัน waypoint รันซ้ำหลายครั้ง (destroy หลังทำงาน)
                                    this.destroy();
                                },
                                offset: $el.data("offset") || "100%" // ตำแหน่ง trigger
                            });
                        }, 100);
                    });
                },
                copylink() {
                    $(".myAlert-top").show();
                    setTimeout(function () {
                        $(".myAlert-top").hide();
                    }, 1000);
                },

                async loadBank() {
                    try {
                        const res = await axios.post("{{ route('customer.slip.loadbank') }}", {method: 'bank'})
                        if (res.data.success) {
                            this.items = res.data.bank;
                            this.loading = false;
                            this.$nextTick(() => {
                                this.fadeIn();
                            });
                        } else {
                            this.items = false;
                        }
                    } catch (e) {
                        this.items = false; // << handle error กรณี exception
                    } finally {
                        this.loading = false;  // << เพิ่มใน finally ให้ปิดสถานะ loading เสมอ
                        this.$nextTick(() => {
                            this.fadeIn();
                        });
                    }
                },

            }
        });

        Vue.component('topup-tw', {
            template: '#topup-tw-template',
            data() {
                return {
                    item: false,
                    items: '',
                    content: '',
                    loading: true,
                    message: ''
                };
            },
            mounted() {
                this.loadBank();

            },
            methods: {
                fadeIn() {
                    $("[data-animatable]").each(function () {
                        var $el = $(this);

                        // ตั้ง delay เล็กน้อยก่อนสร้าง waypoint (เพื่อให้ DOM พร้อม)
                        setTimeout(function () {
                            new Waypoint({
                                element: $el[0], // element DOM จริง
                                handler: function () {
                                    // เมื่อ scroll เข้ามาใน viewport
                                    setTimeout(function () {
                                        // เรียก animateCss ด้วยชื่อ animation จาก data-animatable หรือ default เป็น fadeInUp
                                        $el.animateCss($el.data("animatable") || "fadeInUp");
                                    }, $el.data("delay") || 50);

                                    // ป้องกัน waypoint รันซ้ำหลายครั้ง (destroy หลังทำงาน)
                                    this.destroy();
                                },
                                offset: $el.data("offset") || "100%" // ตำแหน่ง trigger
                            });
                        }, 100);
                    });
                },
                copylink() {
                    $(".myAlert-top").show();
                    setTimeout(function () {
                        $(".myAlert-top").hide();
                    }, 1000);
                },

                async loadBank() {
                    try {
                        const res = await axios.post("{{ route('customer.slip.loadbank') }}", {method: 'tw'})
                        if (res.data.success) {
                            this.items = res.data.bank;
                            this.message = res.data.message;
                            this.loading = false;
                            this.$emit('footer-message', this.message)
                            this.$nextTick(() => {
                                this.fadeIn();
                            });
                        } else {
                            this.items = false;
                            this.message = '';
                        }
                    } catch (e) {
                        this.items = false; // << handle error กรณี exception
                        this.message = '';
                    } finally {
                        this.loading = false;  // << เพิ่มใน finally ให้ปิดสถานะ loading เสมอ
                        this.$nextTick(() => {
                            this.fadeIn();
                        });
                    }
                },

            }
        });

        Vue.component('topup-slip', {
            template: '#topup-slip-template',
            data() {
                return {
                    item: '',
                    content: '',
                    loading: true,
                };
            },
            mounted() {
                this.loadBank();
            },
            methods: {
                copylink() {
                    $(".myAlert-top").show();
                    setTimeout(function () {
                        $(".myAlert-top").hide();
                    }, 1000);
                },

                async loadBank() {
                    const res = await axios.post("{{ route('customer.slip.loadbank') }}", {method: 'slip'});
                    if (res.data.success) {
                        this.item = res.data.bank;
                        this.loading = false;
                    }
                }
            }
        });


        Vue.component('topup-payment', {
            template: '#topup-payment-template',
            data() {
                return {
                    paymentOptions: [],     // เก็บ array ช่องทาง
                    selectedPayment: null,  // ช่องทางที่เลือก
                    paymentApiUrl: '',
                    item: false,
                    content: '',
                    loading: true,
                    minDeposit: 0,
                    depositAmount: '',
                    depositRange: [],
                    isSubmitting: false,
                };
            },
            mounted() {
                this.loadBank();

            },
            methods: {
                formatAmount(v){
                    const n = Number(v);
                    return isNaN(n) ? v : n.toLocaleString('th-TH');
                },
                labelOption(o){
                    if(!o) return '';
                    const name = o.name ?? '';
                    const md   = o.min_deposit;
                    if(md == null) return name;            // ไม่มีค่า → แสดงชื่ออย่างเดียว
                    return `ฝากขั้นต่ำ ${this.formatAmount(md)}`; // kingpay (50)
                    // return `${name} (${this.formatAmount(md)})`; // kingpay (50)
                    // ถ้าจะโชว์เฉพาะ > 0: if(Number(md) > 0) return `${name} (${...})`; else return name;
                },
                fadeIn() {
                    $("[data-animatable]").each(function () {
                        var $el = $(this);

                        // ตั้ง delay เล็กน้อยก่อนสร้าง waypoint (เพื่อให้ DOM พร้อม)
                        setTimeout(function () {
                            new Waypoint({
                                element: $el[0], // element DOM จริง
                                handler: function () {
                                    // เมื่อ scroll เข้ามาใน viewport
                                    setTimeout(function () {
                                        // เรียก animateCss ด้วยชื่อ animation จาก data-animatable หรือ default เป็น fadeInUp
                                        $el.animateCss($el.data("animatable") || "fadeInUp");
                                    }, $el.data("delay") || 50);

                                    // ป้องกัน waypoint รันซ้ำหลายครั้ง (destroy หลังทำงาน)
                                    this.destroy();
                                },
                                offset: $el.data("offset") || "100%" // ตำแหน่ง trigger
                            });
                        }, 100);
                    });
                },
                adjustAmount(event) {
                    const value = Number(event.currentTarget.dataset.value)
                    const operator = event.currentTarget.dataset.operator
                    if (operator === '+') {
                        this.depositAmount += value
                    } else if (operator === '-') {
                        this.depositAmount -= value
                    }
                },
                trans(key, replace = {}) {
                    var translation = key.split('.').reduce((t, i) => t[i] || null, window.i18n);

                    for (var placeholder in replace) {
                        translation = translation.replace(`:${placeholder}`, replace[placeholder]);
                    }
                    return translation;
                },
                preventDot(event) {
                    if (event.key === '.' || event.key === ',' || event.key === 'e') {
                        event.preventDefault();
                    }
                },
                copylink(acc_no) {

                    navigator.clipboard.writeText(acc_no);

                    $(".myAlert-top").show();
                    setTimeout(function () {
                        $(".myAlert-top").hide();
                    }, 1000);
                },
                selectPayment(option) {
                    this.selectedPayment = option;
                    // ดึงค่าต่างๆ มาเซตในฟอร์ม
                    this.minDeposit = option.min_deposit;
                    this.depositAmount = '';
                    this.depositRange = option.deposit_range;
                    this.paymentApiUrl = option.payment_url;
                    this.$nextTick(() => {
                        setTimeout(() => {
                            this.$refs.depositInput?.focus();
                        }, 50);

                    });
                },
                async loadBank() {
                    try {
                        const res = await axios.post("{{ route('customer.slip.loadbank') }}", {method: 'payment'});
                        if (res.data.success && res.data.bank) {
                            // แปลง object เป็น array
                            this.paymentOptions = Object.values(res.data.bank);
                            if (this.paymentOptions.length === 1) {
                                this.selectPayment(this.paymentOptions[0]);
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
                        this.$nextTick(() => {
                            this.fadeIn();
                        });
                    }
                },
                async submitDeposit(force = false) {
                    try {
                        this.isSubmitting = true;


                        const amount = parseFloat(this.depositAmount);
                        if (!amount || isNaN(amount) || amount < this.minDeposit) {
                            window.Toast.fire({
                                icon: 'info',
                                title: this.trans('app.withdraw.wrong_amount') || 'กรุณากรอกจำนวนเงินที่ถูกต้อง (ขั้นต่ำ 200)'
                            });
                            return;
                        }

                        const res = await axios.post(this.paymentApiUrl, {
                            amount: amount,
                            force: force
                        }, {
                            timeout: 10000
                        });

                        if (res.data.success) {
                            const msg = res.data.msg || this.trans('app.topup.create');
                            window.Toast.fire({
                                icon: 'success',
                                title: msg
                            });

                            setTimeout(() => {
                                if(res.data.target){
                                    window.open(res.data.url, '_blank');
                                }else{
                                    window.location.href = res.data.url
                                }
                                // window.location.href = res.data.url
                                // window.open(res.data.url, '_blank');
                            }, 500);

                        } else if (res.data.status === 'has_pending') {
                            console.log('has_pending');
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

                            if (result.isConfirmed) {
                                // 🔁 กดยืนยัน → ส่งใหม่พร้อม force = true
                                await this.submitDeposit(true);
                            } else if (result.dismiss === Swal.DismissReason.cancel) {
                                // 👁‍🗨 ไปหน้ารายการเดิม
                                window.location.href = d.url
                                // window.open(d.url, '_blank');
                            }

                        } else {
                            window.Toast.fire({
                                icon: 'error',
                                title: res.data.msg || this.trans('app.status.error')
                            });
                        }

                    } catch (err) {
                        let message = this.trans('app.status.error');
                        if (err.code === 'ECONNABORTED') {
                            message = 'การเชื่อมต่อช้า หรือไม่ตอบสนอง กรุณาลองใหม่อีกครั้ง';
                        } else if (err?.response?.data?.message) {
                            message = err.response.data.message;
                        }

                        window.Toast.fire({
                            icon: 'error',
                            title: message
                        });
                        console.error(err);
                    } finally {
                        this.isSubmitting = false;
                    }
                }
            }
        });

        window.QRSCAN_ENABLED = {{ $webconfig->qrscan === 'Y' ? 'true' : 'false' }};
        window.BANK_ENABLED = {{ count($topupbanks) > 0 ? 'true' : 'false' }};
        window.TW_ENABLED = {{ count($topuptws) > 0 ? 'true' : 'false' }};
        console.log({{ count($topuptws) }});
        const VueApp = new Vue({
            el: '#app_deposit',
            methods: {
                async openDepositModal() {
                    if (this._opening) return;       // กันคลิกรัว
                    this._opening = true;
                    try {
                        await this.$refs.depositModalComponent.showModal(); // ให้คอมโพเนนต์จัดการ load+เปิด
                    } finally {
                        this._opening = false;
                    }
                }
            }
        });

    </script>

    <script>
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.-btn-deposit');
            if (!btn) return;

            e.preventDefault(); // กันกระโดด #deposit และกันเปิดด้วย data-attr
            const vueRoot = document.querySelector('#app_deposit'); // หรือ id ที่ครอบ Vue
            const vueInstance = vueRoot.__vue__; // Vue 2 เท่านั้น

            await   vueInstance.$refs.depositModalComponent.showModal(); // ภายในจะไปเรียก $refs.depositModal.showModal()
        });
    </script>


    <script>
        window.openModalById = function (id) {
            const el = document.getElementById(id);
            if (!el) return;

            if (window.bootstrap?.Modal) {
                const modal = bootstrap.Modal.getOrCreateInstance(el); modal.show(); return;
            }
            if (window.jQuery && typeof jQuery.fn.modal === 'function') {
                jQuery(el).modal('show'); return;
            }
            el.classList.add('show'); el.style.display = 'block'; // fallback
        };
    </script>
@endpush