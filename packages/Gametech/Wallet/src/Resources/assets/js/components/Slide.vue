<template>
    <div class="owl-item">
        <slot></slot>
    </div>
</template>

<script>
export default {
    name: "slide",
    props: ["title"],
    data() {
        return {
            width: null,
            selectedItem: null,
            name: ''
        };
    },
    inject: ["carousel"],
    mounted() {
        this.$el.addEventListener("mouseup", this.onTouchEnd);
        // this.$el.addEventListener("click", this.selectItem,true);

    },
    methods: {
        onTouchEnd(e) {
            /**
             * @event slideclick
             * @event slide-click
             * @type {Object}
             */

            this.$emit("slideclick", Object.assign({}, e.currentTarget));
            this.$emit("slide-click", Object.assign({}, e.currentTarget));

        }
    }
};
</script>
