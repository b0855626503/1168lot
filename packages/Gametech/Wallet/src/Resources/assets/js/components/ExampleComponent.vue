<template>

    <div class="col-6 mb-4 col-md-3">
        <img
             loading="lazy"
             :alt="product.name"
             :src="product.image"
             :data-src="product.image"
             class="cd-block mx-auto rounded-circle transfer-slide-img lzy_img h-90 w-90"
             :onerror="`this.src='${this.$root.baseUrl}/assets/admin/images/large-product-placeholder.png'`"/>
        <p class="text-main text-center mb-0 cut-text">{{ product.name }}</p>
        <p class="mb-0"></p>


        <p class="text-color-fixed text-center mb-0" @click="openQuickView({details: product, event: $event})" v-if="product.user_code"> {{ product.balance }} </p>

        <div class="text-center mb-0" v-else>
            <button class="btn btn-link p-0 mx-auto"><i class="fas fa-user-plus text-light"></i></button>
        </div>
    </div>

</template>

<script type="text/javascript">
export default {
    props: [
        'list',
        'product',
    ],

    data: function () {
        return {
            quickView: null,
            quickViewDetails: false,
        }
    },
}
</script>
