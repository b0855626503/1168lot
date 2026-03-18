<template>
    <div class="col-6 mb-4 col-md-3 box-border" v-if="item.code" :key="item.code" ref="reward">
        <img
            loading="lazy"
            :alt="item.name"
            :src="item.image"
            :data-src="item.image"
            :onerror="`this.src='${this.$root.baseUrl}/storage/reward_img/default.png'`"
            class="d-block mx-auto rounded-circle transfer-slide-img h-90 w-90">
        <p class="text-main text-center mb-0 cut-text">{{ item.name }}</p>
        <p class="mb-0"></p>

        <p class="text-color-fixed text-center mb-0"> {{ item.point }} Point</p>
        <p class="mb-0"></p>
        <div class="text-center mb-0">
           <button class="btn btn-primary btn-sm" @click.stop.prevent="exChangeReward({details: item, event: $event})"><i class="fa fa-exchange"></i> แลกรางวัล</button>
        </div>
    </div>

    <div v-else></div>
</template>

<script>
export default {
    props: ["game"],
    data: function() {
        return {
            item: ''
        }
    },
    mounted () {
        this.item = this.game;
    },
    methods: {
        exChangeReward: function ({details, event}) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            this.$root.$bvModal.msgBoxConfirm('ต้องการดำเนินการ แลกรางวัลนี้หรือไม่.', {
                title: 'โปรดยืนยันการทำรายการ',
                size: 'sm',
                buttonSize: 'sm',
                okVariant: 'danger',
                okTitle: 'ตกลง',
                cancelTitle: 'ยกเลิก',
                footerClass: 'p-2',
                hideHeaderClose: false,
                centered: true
            })
                .then(value => {
                    if (value) {
                        this.$http.post(`${this.$root.baseUrl}/member/point` , { id : details.code })
                            .then(response => {
                                if (response.data.success) {
                                    this.$bvModal.msgBoxOk(response.data.message, {
                                        title: 'ผลการดำเนินการ',
                                        size: 'sm',
                                        buttonSize: 'sm',
                                        okVariant: 'success',
                                        headerClass: 'p-2 border-bottom-0',
                                        footerClass: 'p-2 border-top-0',
                                        centered: true
                                    });

                                }else{
                                    this.$bvModal.msgBoxOk(response.data.message, {
                                        title: 'ผลการดำเนินการ',
                                        size: 'sm',
                                        buttonSize: 'sm',
                                        okVariant: 'danger',
                                        headerClass: 'p-2 border-bottom-0',
                                        footerClass: 'p-2 border-top-0',
                                        centered: true
                                    });
                                }
                                this.$root.$refs.wallet.updateWalletHeader();

                            })
                            .catch(errors => console.log(errors));
                    }
                })
                .catch(errors => console.log(errors));

        },
    }
};
</script>

