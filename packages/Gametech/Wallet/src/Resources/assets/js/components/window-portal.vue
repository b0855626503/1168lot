<template>
    <div v-if="open">
        <slot />
    </div>
</template>

<script type="text/javascript">
export default {
    name: 'window-portal',
    props: {
        open: {
            type: Boolean,
            default: false,
        }
    },
    data() {
        return {
            windowRef: null,
        }
    },
    watch: {
        open(newOpen) {
            if(newOpen) {
                this.openPortal();
            } else {
                this.closePortal();
            }
        }
    },
    // created() {
    //     document.addEventListener('beforeunload', this.handler)
    // },
    methods: {
        openPortal() {

                const w = 900;
                const h = 500;
                const y = window.top.outerHeight / 2 + window.top.screenY - (h / 2);
                const x = window.top.outerWidth / 2 + window.top.screenX - (w / 2);
                // this.windowRef = window.open("", "gametech", `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=${w}, height=${h}, top=${y}, left=${x}`);
                this.windowRef = window.open("", "gametech", "width=600,height=400,left=200,top=200");
                this.windowRef.addEventListener('beforeunload', this.closePortal);
                // magic!
                this.windowRef.document.body.appendChild(this.$el);

        },
        closePortal() {
            console.log('start close');
            if(this.windowRef) {
                console.log('close in');
                this.windowRef.close();
                // this.windowRef = null;
                // this.$emit('close');
            }
        }
    },
    mounted() {
        if(this.open) {
            this.openPortal();
        }
    },
    beforeDestroy() {
        console.log('beforeDestroy');
        if (this.windowRef) {
            this.closePortal();
        }
    }
}
</script>
