<template>

    <div class="my-1 owl-expansion-panel" v-if="item.date" :key="item.date">
        <div class="card-header">
            <p data-toggle="collapse" class="my-auto align-middle owl-expansion-panel-header-title text-sm top-0">
                <strong class="m-0 my-auto align-middle" v-text="item.date"></strong>
                <span class="align-middle my-auto text-right text-danger owl-expansion-panel-header-description">Check In Complete</span>
            </p>
        </div>
    </div>
    <div v-else></div>

</template>
<script>

export default {
    props: {
        items: []
    },
    data() {
        return {
            item: [{date:''}]
        }
    },
    mounted() {
        this.item.date = this.items;
        console.log('in');
        console.log(this.items);
    },
}

</script>
