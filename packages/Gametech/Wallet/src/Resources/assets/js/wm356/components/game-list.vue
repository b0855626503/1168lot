<template>

    <li class="nav-item -provider-card-item"  :class="'-smm-' + product.id"  v-if="product.user_code">

        <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert text-center"
             data-status="-cannot-entry -untestable">
            <div class="-inner-wrapper">
                <div class="x-game-badge-component - -big">
                    <span></span>
                </div>

                <picture>
                    <source type="image/webp"
                            :data-srcset="product.image"/>
                    <source type="image/png"
                            :data-srcset="product.image"/>
                    <img
                        alt="smm-pg-soft cover image png"
                        class="img-fluid lazyload -cover-img"
                        width="400"
                        height="580"
                        :data-src="product.image"
                        src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                    />
                </picture>

                <div class="-overlay">
                    <div class="-overlay-inner">
                        <div class="-wrapper-container">
                            <button class="-btn -btn-play" @click="openQuickView({details: product, event: $event})" v-if="product.connect">
                                <i class="fas fa-play"></i>
                                <span class="-text-btn">เล่นเกม</span>
                            </button>

                            <button class="-btn -btn-play" v-else>
                                <i class="fas fa-times"></i>
                                <span class="-text-btn">เชื่อมต่อไม่ได้</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="-title">{{ product.name }}</div>
        </div>

    </li>

    <li class="nav-item -provider-card-item" :class="'-smm-' + product.id" v-else>

        <div class="x-game-list-item-macro-in-share js-game-list-toggle -big -cannot-entry -untestable -use-promotion-alert text-center"
             data-status="-cannot-entry -untestable">
            <div class="-inner-wrapper">
                <div class="x-game-badge-component - -big">
                    <span></span>
                </div>

                <picture>
                    <source type="image/webp"
                            :data-srcset="product.image"/>
                    <source type="image/png"
                            :data-srcset="product.image"/>
                    <img
                        alt="smm-pg-soft cover image png"
                        class="img-fluid lazyload -cover-img"
                        width="400"
                        height="580"
                        :data-src="product.image"
                        src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
                    />
                </picture>

                <div class="-overlay">
                    <div class="-overlay-inner">
                        <div class="-wrapper-container">
                            <button class="-btn -btn-play" @click="openQuickRegis({details: product, event: $event})">
                                <i class="fas fa-plus"></i>
                                <span class="-text-btn">สมัคร</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="-title">{{ product.name }}</div>
        </div>
    </li>

</template>

<script>

import to from "../toPromise.js";

export default {
    props: [
        'product', 'pass'
    ],

    data: function () {
        return {
            quickView: null,
            quickViewDetails: false,
            quickRegisDetails: false,
            quickPassDetails: false,
            copycontent: '',
            changepass: ''

        }
    },

    mounted: function () {
        this.quickView = $('.cd-quick-view');
        this.$nextTick(() => {
            this.changepass = this.pass;
            this.loadGameId();
        })
    },

    methods: {
        reload: function () {
            window.location.reload(true);
        },
        async loadGameId() {
            let err, res;
            [err, res] = await to(axios.get(`${this.$root.baseUrl}/member/loadgame/${this.product.code}`));
            if (err) {
                return this.product;
            }

            this.product = res.data;
            return this.product;
        },
        openQuickView: function ({details, event}) {
            console.log('open view');
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            this.$http.post(`${this.$root.baseUrl}/member/profile/view`, {id: details.code})
                .then(response => {
                    $('.modal').modal('hide');

                    if (response.data.success) {

                        var btn = '';
                        if (response.data.game.link_ios) {
                            btn += '<a class="btn btn-sm btn-success mx-1" target="_blank" href="' + response.data.game.link_ios + '"><i class="fab fa-apple"></i> iOS</a>';
                        }
                        if (response.data.game.link_android) {
                            btn += '<a class="btn btn-sm btn-primary mx-1" target="_blank" href="' + response.data.game.link_android + '"><i class="fab fa-android"></i> Android</a>';
                        }
                        if (response.data.game.link_web) {
                            btn += '<a class="btn btn-sm btn-secondary mx-1" target="_blank" href="' + response.data.game.link_web + '"><i class="fas fa-link"></i> Web</a>';
                        }
                        if (response.data.game.autologin === 'Y') {
                            btn = '<a class="btn btn-sm btn-secondary mx-1" target="_blank" href="' + `${this.$root.baseUrl}/member/game/login/` + response.data.game.id + '"><i class="fas fa-link"></i> Login</a>';
                        }

                        Swal.fire({
                            title: '<h5>ข้อมูลของเกม ' + details.name + '</h5>',
                            imageUrl: details.image,
                            imageWidth: 90,
                            imageHeight: 90,
                            html:
                                '<table class="table table-borderless text-sm">, ' +
                                '<tbody> ' +
                                '<tr> ' +
                                '<td>Username</td>' +
                                '<td id="user">' + response.data.user_name + '</td>' +
                                '<td style="text-align: center"><a class="user text-primary" href="javascript:void(0)">[คัดลอก]</a></td>' +
                                '</tr> ' +
                                '<tr> ' +
                                '<td>Password</td>' +
                                '<td id="pass">' + response.data.user_pass + '</td>' +
                                '<td style="text-align: center"><a class="pass text-primary" href="javascript:void(0)">[คัดลอก]</a></td>' +
                                '</tr> ' +
                                '<tr> ' +
                                '<td colspan="3">' + btn + '</td>' +
                                '</tr> ' +
                                '</tbody> ',
                            showConfirmButton: false,
                            showCloseButton: true,
                            showCancelButton: false,
                            focusConfirm: false,
                            scrollbarPadding: true,
                            customClass: {
                                container: 'text-sm',
                                popup: 'text-sm'
                            },
                            willOpen: () => {
                                const user = document.querySelector('.user')
                                const pass = document.querySelector('.pass')


                                user.addEventListener('click', () => {
                                    // console.log('this is copy');
                                    var copyText = document.getElementById('user');
                                    var input = document.createElement("textarea");
                                    input.value = copyText.textContent;
                                    this.copycontent = copyText.textContent;
                                    document.body.appendChild(input);
                                    input.select();
                                    input.setSelectionRange(0, 99999);
                                    document.execCommand("copy");
                                    input.remove();

                                })

                                pass.addEventListener('click', () => {
                                    // console.log('this is copy');
                                    var copyText = document.getElementById('pass');
                                    var input = document.createElement("textarea");
                                    input.value = copyText.textContent;
                                    this.copycontent = copyText.textContent;
                                    document.body.appendChild(input);
                                    input.select();
                                    input.setSelectionRange(0, 99999);
                                    document.execCommand("copy");
                                    input.remove();

                                })

                                $('.user , .pass').popover({
                                    container: 'body',
                                    delay: {"show": 100, "hide": 100},
                                    content: 'คัดลอกข้อมูล ' + this.copycontent + ' สำเร็จแล้ว',
                                    placement: 'top'
                                });
                                $('.user , .pass').on('shown.bs.popover', function () {
                                    setTimeout(function () {
                                        $('.user , .pass').popover('hide');
                                    }, 1000);
                                });

                            }
                        });

                    }

                })
                .catch(exception => {
                    $('.modal').modal('hide');
                    Swal.fire(
                        'เกิดปัญหาบางประการ',
                        'ไม่สามารถดำเนินการได้ โปรดลองใหม่อีกครั้ง',
                        'error'
                    );
                });


            this.quickViewDetails = details;

        },
        openQuickRegis: function ({details, event}) {
            console.log('open regis');
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            Swal.fire({
                title: 'ยืนยันการทำรายการนี้ ?',
                text: "คุณต้องการเปิดบัญชี เกม " + details.name + " หรือไม่",
                imageUrl: details.image,
                imageWidth: 90,
                imageHeight: 90,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ตกลง',
                cancelButtonText: 'ยกเลิก',
                customClass: {
                    container: 'text-sm',
                    popup: 'text-sm'
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    $('.modal').modal('hide');
                    this.$http.post(`${this.$root.baseUrl}/member/create`, {id: details.code})
                        .then(response => {

                            if (response.data.success) {
                                this.reload();
                            } else {
                                Swal.fire(
                                    'พบข้อผิดพลาด',
                                    response.data.message,
                                    'error'
                                );
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
                }
            })

            this.quickRegisDetails = details;
        },
        async openQuickPass({details, event}) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            const {value: password} = await Swal.fire({
                title: "คุณต้องการเปลี่ยนรหัสผ่าน เกม " + details.name + " หรือไม่",
                input: 'password',
                inputLabel: 'Password',
                inputPlaceholder: 'Enter your password',
                imageUrl: details.image,
                imageWidth: 90,
                imageHeight: 90,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ตกลง',
                cancelButtonText: 'ยกเลิก',
                customClass: {
                    container: 'text-sm',
                    popup: 'text-sm'
                },
                inputAttributes: {
                    maxlength: 15,
                    autocapitalize: 'off',
                    autocorrect: 'off',
                    autocomplete: 'off'
                }
            })

            if (password) {
                $('.modal').modal('hide');
                this.$http.post(`${this.$root.baseUrl}/member/profile/change`, {id: details.code, password: password})
                    .then(response => {

                        if (response.data.success) {
                            Swal.fire(
                                'ดำเนินการสำเร็จ',
                                response.data.message,
                                'success'
                            );
                        } else {
                            Swal.fire(
                                'พบข้อผิดพลาด',
                                response.data.message,
                                'error'
                            );
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
            }

            // Swal.fire({
            //     title: 'ยืนยันการทำรายการนี้ ?',
            //     text: "คุณต้องการเปลี่ยนรหัสผ่าน เกม " + details.name + " หรือไม่",
            //     imageUrl: details.image,
            //     imageWidth: 90,
            //     imageHeight: 90,
            //     showCancelButton: true,
            //     confirmButtonColor: '#3085d6',
            //     cancelButtonColor: '#d33',
            //     confirmButtonText: 'ตกลง',
            //     cancelButtonText: 'ยกเลิก',
            //     customClass: {
            //         container: 'text-sm',
            //         popup: 'text-sm'
            //     },
            // }).then((result) => {
            //     if (result.isConfirmed) {
            //         $('.modal').modal('hide');
            //         this.$http.post(`${this.$root.baseUrl}/member/profile/change`, {id: details.code})
            //             .then(response => {
            //
            //                 if (response.data.success) {
            //                     Swal.fire(
            //                         'ดำเนินการสำเร็จ',
            //                         response.data.message,
            //                         'success'
            //                     );
            //                 } else {
            //                     Swal.fire(
            //                         'พบข้อผิดพลาด',
            //                         response.data.message,
            //                         'error'
            //                     );
            //                 }
            //             })
            //             .catch(response => {
            //
            //                 $('.modal').modal('hide');
            //                 Swal.fire(
            //                     'การเชื่อมต่อระบบ มีปัญหา',
            //                     response.data.message,
            //                     'error'
            //                 );
            //             });
            //     }
            // })

            this.quickPassDetails = details;
        }
    }
}
</script>
