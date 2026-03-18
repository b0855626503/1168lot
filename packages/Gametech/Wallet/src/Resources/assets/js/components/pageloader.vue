<template>
    <div class="vld-parent">
        <loading :active.sync="isLoading"
                 :can-cancel="true"
                 :on-cancel="onCancel"
                 :is-full-page="fullPage"></loading>
    </div>
</template>

<script>
// Import component
import Loading from 'vue-loading-overlay';
// Import stylesheet
import 'vue-loading-overlay/dist/vue-loading.css';

export default {
    data() {
        return {
            isLoading: false,
            fullPage: true
        }
    },
    components: {
        Loading
    },
    mounted: function () {
        // The spinner works if I don't added this event listener
        document.addEventListener("readystatechange", () => {
            this.isLoading = true
        });

        document.addEventListener("readystatechange", () => {
            if(document.readyState === 'complete'){
                setTimeout(() => {
                    this.isLoading = false
                },2000)

            }

        });
    },
    methods: {
        doAjax() {
            this.isLoading = true;
            // simulate AJAX
            setTimeout(() => {
                this.isLoading = false
            },2000)
        },
        onCancel() {
            console.log('User cancelled the loader.')
        }
    }
}
</script>
