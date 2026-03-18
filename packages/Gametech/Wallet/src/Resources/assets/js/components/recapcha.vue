<template>
    <vue-recaptcha
        :class="['mx-auto']"
        ref="recaptcha"
        @verify="onVerify"
        @expired="onExpired"
        size="normal"
        :sitekey="sitekey">
    </vue-recaptcha>
</template>
<script type="text/javascript">
import VueRecaptcha from 'vue-recaptcha';

export default {
    props : ['sitekey'],

    components: { VueRecaptcha },
    methods: {
        onSubmit: function () {
            this.$refs.recaptcha.execute()
             },
        onVerify: function (response) {
            document.getElementById("btnsubmit").disabled = false;
        },
        onExpired: function () {
            this.$refs.recaptcha.reset()
        },
        resetRecaptcha () {
            this.$refs.recaptcha.reset() // Direct call reset method
        }
    }
}

</script>
