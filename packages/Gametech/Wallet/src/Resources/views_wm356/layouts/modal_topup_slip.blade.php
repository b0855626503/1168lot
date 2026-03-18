@push('scripts')
    <script type="text/x-template" id="topup-slip-top-template">
        <div class="-promotion-box-wrapper">
            <button type="button"
                    @click="topupSelect('topup_slip')"
                    class="btn -promotion-box-apply-btn js-promotion-apply btn-for-deposit"
                    data-url="/promotion/2/apply" data-type="deposit"
                    data-display-slide-mode="true">
                <picture>
                    <source type="image/webp"
                            srcset="https://img2.pic.in.th/pic/twa6cf4bb54c16ae4b.png"/>
                    <source type="image/png"
                            srcset="https://img2.pic.in.th/pic/twa6cf4bb54c16ae4b.png"/>
                    <img class="-img img50" alt="BONUS" width="26" height="26"
                         loading="lazy" fetchpriority="low"
                         src="https://img2.pic.in.th/pic/twa6cf4bb54c16ae4b.png"/>
                </picture>

                <span class="-title">{{ __('app.home.topup_slip') }}</span>

            </button>
            <a href="javascript:void(0)"
               class="-promotion-box-cancel-btn js-cancel-promotion"

               data-display-slide-mode="true">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </script>

    <script type="text/x-template" id="topup-slip-down-template">

        <div id="topup_slip" class="-deposit-form-inner-wrapper table-responsive-new" style="display:none" v-if="item">


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


        </div>

    </script>

    <script type="module">

        Vue.component('topup-slip', {
            'template': '#topup-slip-top-template',


            methods: {
                topupSelect(type){
                    $('.btn-for-deposit').prop("disabled", false);
                    $('.-deposit-form-inner-wrapper').css('display', 'none');
                    $('#'+type).css('display', 'block');
                }

            }
        })

        Vue.component('topup-slip-content', {
            'template': '#topup-slip-down-template',

            data() {
                return {
                    item: [],
                }
            },
            mounted() {
                this.loadBank()
                if (!window._clipboardInitialized) {
                    new ClipboardJS('.btncopy', {
                        container: document.getElementById('depositModal')
                    });
                    window._clipboardInitialized = true;
                }
            },

            methods: {

                async loadBank() {
                    const res = await axios.get("{{ route('customer.slip.loadbank') }}")
                    this.item = res.data;
                },
                copylink(acc_no) {
                    $(".myAlert-top").show();
                    setTimeout(function () {
                        $(".myAlert-top").hide();
                    }, 1000);
                },

            }
        })

        new Vue({
            el: '#app_model'
        });

    </script>

@endpush