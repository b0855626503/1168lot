<template>
    <div class="row">
        <div class="col-sm-12 wallet">
            <h4 class="wallet-heading">MY CASHBACK <a class="float-right"> &nbsp;
                <i id="reload" class="fas fa-sync-alt text-color-fixed fa-2x pointer" @click="reLoad"></i></a></h4>
            <div style="opacity: 1;">
                <span class="wallet-money">฿ </span>
                <span class="wallet-balance text-color-fixed" v-text="credit_amount"></span>

            </div>
        </div>
    </div>
</template>
<script>
export default {

    data: function () {
        return {
            'credit_amount': '0.00'
        }
    },

    created: function () {
        this.$root.$refs.credit = this;
        // this.updateCreditHeader();
    },

    mounted: function () {
        this.updateCreditHeader();

    },

    methods: {
        async updateCreditHeader() {
            document.getElementById('reload').classList.add('fa-spin');
            const response = await axios.get(`${this.$root.baseUrl}/member/loadprofile`);
            this.$nextTick(() => {
                this.credit_amount = response.data.profile.balance_free;

                document.getElementById('reload').classList.remove('fa-spin');
            })
        },
        reLoad: function () {
            this.updateCreditHeader();

            setTimeout(() => {
                document.getElementById('reload').classList.remove('fa-spin');
            }, 5000);
        }
    }
}


</script>
