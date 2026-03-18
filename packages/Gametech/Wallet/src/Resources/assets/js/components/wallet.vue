<template>
    <div class="row">
        <div class="col-sm-12 wallet">
            <h4 class="wallet-heading">MY WALLET
                <a class="float-right"> &nbsp;
                    <i id="reload" class="fas fa-sync-alt text-color-fixed fa-2x pointer" @click="reLoad"></i>
                </a>
            </h4>
            <div style="opacity: 1;">
                <span class="wallet-money">฿ </span>
                <span class="wallet-balance text-color-fixed" v-text="wallet_amount"></span>
                <div class="text-right">
                    <span class="point" v-if="point_open">
                        <i class="fas fa-coins"></i> แต้มสะสม
                        <span class="text-color-fixed" v-text="point_amount"></span> แต้ม
                    </span>
                    <span v-else></span>

                    <span class="diamond" v-if="diamond_open">
                        <i class="fas fa-gem"></i> เพชรสะสม
                        <span class="text-color-fixed"
                              v-text="diamond_amount"></span> เพชร
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
            'point_open':false,
            'diamond_open':false

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
            document.getElementById('reload').classList.add('fa-spin');
            const response = await axios.get(`${this.$root.baseUrl}/member/loadprofile`);
            this.$nextTick(() => {
                this.wallet_amount = response.data.profile.balance;
                this.point_amount = response.data.profile.point_deposit;
                this.diamond_amount = response.data.profile.diamond;
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
        }
    }
}


</script>
