<template>
    <div class="toplogin">
        <div class="containtoplogin">
            <div class="topdetaillogin" v-text="notice">

            </div>
            <div class="toploginbox">
                <div class="flexcenter mr-2">
                    <div class="-balance-container">
                        <div class="-user-balance js-user-balance f-sm-6 f-7">
                            <div class="-inner-box-wrapper">
                                <img class="img-fluid -ic-coin" src="/images/icon/coin.png" alt="customer image"
                                     width="26" height="21">
                                <span id="customer-balance"><span class="text-green-lighter"
                                                                  v-text="wallet_amount">0</span>
                                    </span>
                            </div>
                            <button @click="reLoad" class="-btn-balance" id="btn-customer-balance-reload">
                                <i id="reload" class="fas fa-sync-alt f-9 reloadcredit"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flexcenter mr-2" v-if="diamond_open">
                    <div class="-balance-container">
                        <div class="-user-balance js-user-balance f-sm-6 f-7">
                            <div class="-inner-box-wrapper">
                                <img class="img-fluid -ic-coin" src="/images/icon/diamond.png" alt="customer image"
                                     width="26" height="21">
                                <span id="customer-balance"><span class="text-green-lighter"
                                                                  v-text="diamond_amount">0</span>
                                    </span>
                            </div>
<!--                            <button @click="reLoad" class="-btn-balance" id="btn-customer-balance-reload">-->
<!--                                <i id="reload" class="fas fa-sync-alt f-9 reloadcredit"></i>-->
<!--                            </button>-->
                        </div>
                    </div>
                </div>
                <div v-else></div>
                <div class="flexcenter mr-1" v-if="multi">
                    <a href="/member/transfer/game">
                        <button class="btn red">
                            {{ __('app.home.transfer') }}
                        </button>
                    </a>
                </div>
                <div v-else></div>
                <div class="flexcenter mr-1">
                    <a href="/member/topup">
                        <button class="btn blue">
                            {{ __('app.home.refill') }}
                        </button>
                    </a>
                </div>
                <div class="flexcenter">
                    <a href="/member/withdraw">
                        <button class="btn gold">
                            {{ __('app.home.withdraw') }}
                        </button>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    data: function () {
        return {
            'wallet_amount': '0.00',
            'point_amount': '0.00',
            'diamond_amount': '0.00',
            'credit_amount': '0.00',
            'point_open': false,
            'diamond_open': false,
            'multi': false,
            'user_name': '',
            'notice': '',

        }
    },

    created: function () {
        this.$root.$refs.wallet = this;
        // this.updateWalletHeader();
    },

    mounted: function () {
        this.updateWalletHeader();

    },

    methods: {
        async updateWalletHeader() {
            // document.getElementsByClassName('reloadcredit').classList.add('fa-spin');
            document.getElementById('reload').classList.add('fa-spin');
            const response = await axios.get(`${this.$root.baseUrl}/member/loadcredit`);
            this.$nextTick(() => {
                this.wallet_amount = response.data.profile.balance;
                this.point_amount = response.data.profile.point_deposit;
                this.diamond_amount = response.data.profile.diamond;
                this.credit_amount = response.data.profile.credit;
                this.point_open = response.data.system.point;
                this.diamond_open = response.data.system.diamond;
                this.user_name = response.data.profile.user_name;
                this.notice = response.data.system.notice;
                this.multi = response.data.system.multi;

                document.getElementById('reload').classList.remove('fa-spin');
            })
        },
        reLoad: function () {
            this.updateWalletHeader();

            setTimeout(() => {
                document.getElementById('reload').classList.remove('fa-spin');
            }, 5000);
        },
        tranBonus: function () {
            this.$bvModal.msgBoxConfirm('ต้องการ โยกเงินโบนัสเข้า กระเป๋าหลัก หรือไม่', {
                title: 'ยืนยันการโยกโบนัส',
                size: 'sm',
                buttonSize: 'sm',
                okVariant: 'danger',
                okTitle: 'YES',
                cancelTitle: 'NO',
                footerClass: 'p-2',
                hideHeaderClose: false,
                centered: true
            })
                .then(value => {
                    if (!value) return;
                    this.$http.post(`${this.$root.baseUrl}/member/transfer/bonus/confirm`)
                        .then(response => {

                            if (response.data.success) {
                                Swal.fire(
                                    'ดำเนินการสำเร็จ',
                                    response.data.message,
                                    'success'
                                );
                            } else {
                                Swal.fire(
                                    'พบข้อผิดพลาด',
                                    response.data.message,
                                    'error'
                                );
                            }
                        })
                        .catch(response => {

                            $('.modal').modal('hide');
                            Swal.fire(
                                'พบข้อผิดพลาด',
                                response.data.message,
                                'error'
                            );
                        });
                })
                .catch(err => {
                    // An error occurred
                })
        }
    }
}
</script>
