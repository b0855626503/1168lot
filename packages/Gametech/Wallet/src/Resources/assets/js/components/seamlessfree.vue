<template>
    <div class="row">
        <div class="col-sm-12 wallet">
            <h4 class="wallet-heading">ยอดฟรีเครดิตของฉัน
                <a class="float-right"> &nbsp;
                    <i id="reload" class="fas fa-sync-alt text-color-fixed fa-2x pointer" @click="reLoad"></i>
                </a>
            </h4>
            <div style="opacity: 1;">
                <span class="wallet-money">฿ </span>
                <span class="wallet-balance text-color-fixed" v-text="wallet_amount"></span>
                <div class="text-right">

                    <span class="bonus pointer" v-if="bonus_open" @click="tranBonus">
                        <i class="fa fa-bitcoin"></i> โบนัส
                        <span class="text-color-fixed"
                              v-text="bonus_amount"></span> บาท
                    </span>
                    <span v-else></span>

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
            'bonus_amount': '0.00',
            'bonus_open': false,
            'faststart_amount': '0.00',
            'faststart_open': false,

        }
    },

    created: function () {
        this.$root.$refs.wallet = this;
        // this.updateWalletHeader();
    },

    mounted: function () {
        const self = this;
        this.updateWalletHeader();
        setInterval(function () {
            self.reLoad();
        }, 100000);
    },

    methods: {
        async updateWalletHeader() {
            document.getElementById('reload').classList.add('fa-spin');
            const response = await axios.get(`${this.$root.baseUrl}/member/loadcredit`);
            this.$nextTick(() => {
                this.wallet_amount = response.data.profile.balance_free;
                this.point_amount = response.data.profile.point_deposit;
                this.diamond_amount = response.data.profile.diamond;
                this.credit_amount = response.data.profile.credit;
                this.point_open = response.data.system.point;
                this.diamond_open = response.data.system.diamond;

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
