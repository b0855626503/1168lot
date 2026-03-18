<template>

    <div class="col-4 mb-4 col-md-3" v-if="product.user_code">
        <div @click="openQuickView({details: product, event: $event})" v-if="product.connect">

            <img
                loading="lazy"
                :alt="product.name"
                :src="product.image"
                :data-src="product.image"
                class="d-block mx-auto rounded-circle transfer-slide-img h-90 w-90"
                :onerror="`this.src='${this.$root.baseUrl}/storage/game_img/default.png'`"/>
            <p class="text-main text-center mb-0 cut-text">{{ product.name }}</p>
            <p class="mb-0"></p>

            <div class="text-center mb-0" v-if="changepass === 'password'">
                <button class="btn btn-link p-0 mx-auto" @click="openQuickPass({details: product, event: $event})"><i
                    class="fas fa-key text-light"></i> เปลี่ยนรหัส
                </button>
            </div>
            <div v-else>
                <p class="text-color-fixed text-center mb-0"> {{ product.balance }} ฿</p>
            </div>
        </div>

        <div style="opacity: 0.1;" v-else>
            <img
                loading="lazy"
                :alt="product.name"
                :src="product.image"
                :data-src="product.image"
                class="d-block mx-auto rounded-circle transfer-slide-img h-90 w-90"
                :onerror="`this.src='${this.$root.baseUrl}/storage/game_img/default.png'`"/>
            <p class="text-main text-center mb-0 cut-text">{{ product.name }}</p>
            <p class="mb-0"></p>


            <p class="text-color-fixed text-center mb-0">ระบบเกมมีปัญหา</p>
        </div>
    </div>

    <div class="col-4 mb-4 col-md-3" v-else>
        <img
            loading="lazy"
            :alt="product.name"
            :src="product.image"
            :data-src="product.image"
            class="d-block mx-auto rounded-circle transfer-slide-img h-90 w-90"
            :onerror="`this.src='${this.$root.baseUrl}/storage/game_img/default.png'`"/>
        <p class="text-main text-center mb-0 cut-text">{{ product.name }}</p>
        <p class="mb-0"></p>

        <div class="text-center mb-0">
            <button class="btn btn-link p-0 mx-auto" @click="openQuickRegis({details: product, event: $event})"><i
                class="fas fa-user-plus text-light"></i></button>
        </div>
    </div>

</template>

<script type="text/javascript">
import to from "../toPromise";

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
            [err, res] = await to(axios.get(`${this.$root.baseUrl}/member/loadgamefree/${this.product.code}`));
            if (err) {
                return this.product;
            }
            this.product = res.data;
            return this.product;

        },
        openQuickView: function ({details, event}) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            this.$http.post(`${this.$root.baseUrl}/member/profile/viewfree`, {id: details.code})
                .then(response => {
                    $('.modal').modal('hide');

                    if (response.data.success) {
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
                    this.$http.post(`${this.$root.baseUrl}/member/createfree`, {id: details.code})
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
        openQuickPass: function ({details, event}) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            Swal.fire({
                title: 'ยืนยันการทำรายการนี้ ?',
                text: "คุณต้องการเปลี่ยนรหัสผ่าน เกม " + details.name + " หรือไม่",
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
                    this.$http.post(`${this.$root.baseUrl}/member/profile/changefree`, {id: details.code})
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
            })

            this.quickRPassDetails = details;
        }
    }
}
</script>
