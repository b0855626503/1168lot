<template v-for="(reward, index) in rewards">
    <checkinlog-item :items="reward" :key="index"></checkinlog-item>
</template>
<script>

export default {
    data() {
        return {
            rewards: [],
        }
    },
    mounted() {
        this.loadGameId();
        console.log(this.rewards);
        console.log('-');
    },
    provide() {
        return {
            checkinlog: this
        };
    },
    methods: {
        loadGameId: function () {
            let this_this = this;

            this.$http.get(`${this.$root.baseUrl}/member/checkin/history`)
                .then(response => {
                    if (response.data) {

                        $.each(response.data, function(key, value) {
                            // console.log(value);
                            this_this.rewards.push(value);
                        });

                    }
                })
                .catch(exception => {
                    console.log('error');
                });

        },

    }
}


</script>
