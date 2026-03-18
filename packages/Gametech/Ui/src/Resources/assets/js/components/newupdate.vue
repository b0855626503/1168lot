<template>
    <div class="vld-parent">
        <loading :active.sync="isLoading"
                 :is-full-page="fullPage"
                 :can-cancel="false"
                 :z-index="newindex"
                 :lock-scroll="true"
                 :color="color"
                 :loader="loader"
                 opacity="0.9"
                 :background-color="bgColor">
            <template #default v-if="useSlot">
                <h2 class="text-danger">ขณะนี้มีอัพเดท v .{{ version }}</h2>
                <br>
                <a :href="linkurl" class="btn btn-primary">กดตรงนี้เพื่ออัพเดท</a>
            </template>
        </loading>
    </div>
</template>

<script>
import Loading from 'vue-loading-overlay';
import 'vue-loading-overlay/dist/vue-loading.css';

export default {
    props: ['version','linkurl'],
    data() {
        return {
            isLoading: false,
            fullPage: true,
            useSlot: true,
            color: '#007bff',
            bgColor: '#ffffff',
            newindex: 999999,
        }
    },
    components: {
        Loading
    },
    mounted() {

        document.addEventListener("readystatechange", () => {
            this.isLoading = true;
            document.documentElement.classList.add('hide-scroll');
        });
    }

}
</script>
