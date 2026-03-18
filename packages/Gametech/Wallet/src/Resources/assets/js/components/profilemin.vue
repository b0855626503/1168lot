<template>
    <div class="mr-1">
        <div class="-balance-container">
            <div class="-user-balance js-user-balance f-sm-6 f-7 ">
                <div class="-inner-box-wrapper">
                    <img class="img-fluid -ic-coin" src="/images/icon/coin.png" alt="customer image"
                         width="26" height="21">
                    <span id="customer-balance"><span class="text-green-lighter" v-text="wallet_amount">0</span>
                                    </span>
                </div>
                <button @click="reLoad" type="button" class="-btn-balance" id="btn-customer-balance-reload">
                    <i id="reloadmin" class="fas fa-sync-alt f-9 reloadcredit"></i>
                </button>
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
            'user_name': ''

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
            document.getElementById('reloadmin').classList.add('fa-spin');
            const response = await axios.get(`${this.$root.baseUrl}/member/loadcreditmin`);
            this.$nextTick(() => {
                this.wallet_amount = response.data.profile.balance;
                this.point_amount = response.data.profile.point_deposit;
                this.diamond_amount = response.data.profile.diamond;
                this.credit_amount = response.data.profile.credit;
                this.point_open = response.data.system.point;
                this.diamond_open = response.data.system.diamond;
                this.user_name = response.data.profile.user_name;
                document.getElementById('reloadmin').classList.remove('fa-spin');
            })
        },
        reLoad: function () {
            this.updateWalletHeader();

            setTimeout(() => {
                document.getElementById('reloadmin').classList.remove('fa-spin');
            }, 5000);
        }
    }
}
</script>
