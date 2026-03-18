<template>

    <div class="col-6 mb-3 col-md-4" v-if="product.code">
        <a v-bind:href="link" :data-code="product.code" :id="this.number" target="gametech" onclick="openRequestedSinglePopup(this.id); return false;">
            <img
                loading="lazy"
                :alt="product.name"
                :src="product.image"
                :data-src="product.image"
                class="d-block mx-auto img-fluid img-full"
                :onerror="`this.src='${this.$root.baseUrl}/storage/game_img/default.png'`"/>
            <p class="text-main text-center mb-0 cut-text small">{{ product.name }}</p>
            <p class="mb-0"></p>

        </a>
    </div>

    <div class="col-4 mb-4 col-md-3" v-else>
        <img
            loading="lazy"
            :alt="product.name"
            :src="product.image"
            :data-src="product.image"
            class="d-block mx-auto"
            :onerror="`this.src='${this.$root.baseUrl}/storage/game_img/default.png'`"/>
        <p class="text-main text-center mb-0 cut-text small">{{ product.name }}</p>
        <p class="mb-0"></p>
    </div>

</template>

<script type="text/javascript">

import to from "../toPromise.js";

export default {
    props: [
        'product', 'product_id', 'number'
    ],

    data: function () {
        return {
            isOpen: false,
            link: ''
        }
    },

    mounted: function () {

        // if (localStorage.isOpen) {
        //     this.isOpen = localStorage.isOpen;
        // }
        // if (localStorage.gametechPopup) {
        //     this.windowRef = localStorage.gametechPopup;
        // }
        // if (localStorage.isLink) {
        //     this.link = localStorage.isLink;
        // }

    },

    methods: {
        gameRedirect: function ({details, event}) {

            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            // this.$root.$refs.gamepopup.open = false;
            // console.log(this.$root.$refs.gamepopup);

            // if (this.$root.$refs.gamepopup.windowRef == null) {
            //     this.$root.$refs.gamepopup.openPortal();
            //
            // } else {
            //     this.$root.$refs.gamepopup.windowRef.focus();
            // }
            this.$nextTick(() => {
                this.openQuickView(details);
            })


            // this.$http.post(`${this.$root.baseUrl}/member/game/autologin`, {
            //     id: this.product_id,
            //     game: details.code
            // })
            //     .then(response => {
            //
            //         if (response.data.success) {
            //
            //             self.$root.$refs.gamepopup.windowRef.location.replace(response.data.url);
            //             self.$root.$refs.gamepopup.focus();
            //         }
            //     })
            //     .catch(response => {
            //
            //         $('.modal').modal('hide');
            //         Swal.fire(
            //             'การเชื่อมต่อระบบ มีปัญหา',
            //             response.data.message,
            //             'error'
            //         );
            //     });
        },
        async openQuickView() {
            let err, res;
            [err, res] = await to(axios.post(`${this.$root.baseUrl}/member/game/autologin`, {
                id: this.product_id,
                game: this.product.code
            }));

            if (err) {
                this.link = '';
            }
            if (res.data.success) {
                // window.windowObjectReference.location.href = res.data.url;
                // this.$root.$refs.gamepopup.windowRef.location.href = res.data.url;
                this.link = res.data.url;
                // window.openRequestedSinglePopup(res.data.url);
            } else {
                this.link = '';
            }

            // this.$root.$refs.gamepopup.windowRef.location.href = this.link;
            // window.windowObjectReference = this.windowRef;

            // if (localStorage.isLink) {
            //     if (localStorage.isLink !== this.link) {
            //         console.log('has islink ' + this.isOpen);
            //         console.log('windowref ' + this.windowRef);
            //         this.windowRef.location.href = this.link;
            //     }
            // } else {
            //     console.log('no islink ' + this.isOpen);
            //     console.log('windowref ' + this.windowRef);
            //     if (this.link !== '') {
            //         localStorage.isLink = this.link;
            //     }
            //     this.windowRef.location.href = this.link;
            // }

        },
        openUrl: function() {
            let routeData = this.$router.resolve({name: 'member/game/autologin', query: {id: this.product_id,game: this.product.code}});
            window.open(routeData.href, 'gametech');

            let url =  document.getElementById("mainscript").getAttribute('baseUrl');
            return this.$http.post(`${url}/member/game/autologin`, {
                id: this.product_id,
                game: this.product.code
            })
                .then(response => {

                    if (response.data.success) {
                        this.link = response.data.url;
                        return response.data.url;

                    }
                })
                .catch(response => {

                    $('.modal').modal('hide');
                    Swal.fire(
                        'การเชื่อมต่อระบบ มีปัญหา',
                        response.data.message,
                        'error'
                    );
                });

            // let err, res;
            // [err, res] = await to(axios.post(`${this.$root.baseUrl}/member/game/autologin`, {
            //     id: this.product_id,
            //     game: this.product.code
            // }));
            //
            // if (err) {
            //     this.link = '';
            // }
            // if (res.data.success) {
            //     // window.windowObjectReference.location.href = res.data.url;
            //     // this.$root.$refs.gamepopup.windowRef.location.href = res.data.url;
            //     this.link = res.data.url;
            //     // window.openRequestedSinglePopup(res.data.url);
            // } else {
            //     this.link = '';
            // }

            // this.$root.$refs.gamepopup.windowRef.location.href = this.link;
            // window.windowObjectReference = this.windowRef;

            // if (localStorage.isLink) {
            //     if (localStorage.isLink !== this.link) {
            //         console.log('has islink ' + this.isOpen);
            //         console.log('windowref ' + this.windowRef);
            //         this.windowRef.location.href = this.link;
            //     }
            // } else {
            //     console.log('no islink ' + this.isOpen);
            //     console.log('windowref ' + this.windowRef);
            //     if (this.link !== '') {
            //         localStorage.isLink = this.link;
            //     }
            //     this.windowRef.location.href = this.link;
            // }

        },
        // openPortal: function ({details, event}) {
        //     if (event) {
        //         event.preventDefault();
        //         event.stopPropagation();
        //     }
        //
        //     this.isOpen = localStorage.isOpen;
        //     console.log('check isopen ' + this.isOpen);
        //     if (this.isOpen !== true) {
        //         const w = 900;
        //         const h = 500;
        //         const y = window.top.outerHeight / 2 + window.top.screenY - (h / 2);
        //         const x = window.top.outerWidth / 2 + window.top.screenX - (w / 2);
        //         this.windowRef = window.open("", "gametech", `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=${w}, height=${h}, top=${y}, left=${x}`);
        //         // this.windowRef = window.windowObjectReference;
        //         this.windowRef.addEventListener('beforeunload', this.closePortal);
        //         this.windowRef.document.body.appendChild(this.$el);
        //         this.isOpen = true;
        //         localStorage.isOpen = this.isOpen;
        //
        //
        //         // const parsed = JSON.stringify(this.windowRef);
        //         // localStorage.setItem('gametechPopup', this.windowRef);
        //
        //     }
        //
        //     this.$nextTick(() => {
        //         this.openQuickView(details);
        //     })
        // },
        // closePortal() {
        //     console.log('Close Portal');
        //     console.log(this.windowRef);
        //     if (this.windowRef) {
        //         this.windowRef.close();
        //         this.windowRef = null;
        //         this.$emit('close');
        //         localStorage.isOpen = false;
        //         localStorage.gametechPopup = this.windowRef;
        //     }
        //
        // }
    }
}
</script>
