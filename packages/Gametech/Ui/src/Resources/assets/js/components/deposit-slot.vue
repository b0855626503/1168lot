<template>
    <div class="info-box" v-if="show">
        <span class="info-box-icon bg-info elevation-1"><i class="fas fa-plus-circle"></i></span>

        <div class="info-box-content">
            <span class="info-box-text">ยอดฝาก</span>
            <span class="info-box-number">
                {{ deposit_sum }}
                  <small>บาท</small>
                </span>
        </div>
        <!-- /.info-box-content -->
    </div>
</template>

<script>
export default {
    data: function () {
        return {
            deposit_sum : 0,
            show : true
        }
    },
    mounted: function () {
        this.loadDeposit();
    },
    methods: {
        async loadDeposit() {
            const response = await axios.post(`/admin/dashboard/loadsum`, {method: 'deposit'});
            this.$nextTick(() => {
                this.deposit_sum = response.data.sum;
            })
        }
    }
}
</script>

