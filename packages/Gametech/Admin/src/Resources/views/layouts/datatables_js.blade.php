<script src="{{ asset('vendor/datatables/buttons.server-side.js') }}"></script>
<script>
    function editdata(id, status, method) {
        window.app.editdata(id, status, method);
    }

    function editModal(id) {
        window.app.editModal(id);
    }

    function addModal() {
        window.app.addModal();
    }

    function delModal(id) {
        window.app.delModal(id);
    }

    $(document).on('click', '[data-widget="pushmenu"]', event => {
        window.LaravelDataTables["dataTableBuilder"].columns.adjust().draw(false);
    })
</script>
<script type="module">

        Vue.mixin({
            data() {
                return {
                    code: null,
                    method: null,
                    formmethod: 'add',
                    announce: '',
                    loopcnts: 0,
                    firsttime: true,
                    playPromise: undefined,
                    audio: {},
                    toast: '',
                    withdraw_cnt: 0
                };
            },

            beforeDestroy() {
                clearInterval(this.loopcnts);
            },
            watch: {
                withdraw_cnt: function (event) {
                    if (event > 0) {
                        this.ToastPlay();
                    }
                }
            },
            methods: {
                ToastPlay() {
                    this.toast.error('<span class="text-danger">มีการถอนรายการใหม่</span>');
                },
                playSound() {

                    // if (this.playPromise !== undefined) {
                    //     this.playPromise.then(_ => {
                    //
                    //         this.playPromise = this.audio.play();
                    //     }).catch(error => {
                    //         this.playPromise = this.audio.play();
                    //     });
                    //
                    // } else {
                    //     this.audio = document.querySelector('audio');
                    //     this.playPromise = this.audio.play();
                    // }


                },
                toggleButtonDisable(value) {
                    // $(event.currentTarget).find('button[type=submit]').prop('disabled', true);
                    var buttons = document.querySelectorAll('[type="submit"]');

                    for (var i = 0; i < buttons.length; i++) {
                        buttons[i].disabled = value;
                    }
                },
                delModal(code) {
                    this.$bvModal.msgBoxConfirm('ต้องการดำเนินการ ลบข้อมูลหรือไม่.', {
                        title: 'โปรดยืนยันการทำรายการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'ตกลง',
                        cancelTitle: 'ยกเลิก',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        centered: true
                    })
                        .then(value => {
                            if (value) {
                                this.$http.post("{{ url($menu->currentRoute.'/delete') }}", {
                                    id: code
                                })
                                    .then(response => {
                                        this.$bvModal.msgBoxOk(response.data.message, {
                                            title: 'ผลการดำเนินการ',
                                            size: 'sm',
                                            buttonSize: 'sm',
                                            okVariant: 'success',
                                            headerClass: 'p-2 border-bottom-0',
                                            footerClass: 'p-2 border-top-0',
                                            centered: true
                                        });
                                        window.LaravelDataTables["dataTableBuilder"].draw(false);
                                    })
                                    .catch(exception => {
                                        console.log('error');
                                    });
                            }
                        })
                        .catch(err => {
                            // An error occurred
                        })
                },
                editdata(code, status, method) {

                    this.$bvModal.msgBoxConfirm('ต้องการดำเนินการ ปรับข้อมูลหรือไม่.', {
                        title: 'โปรดยืนยันการทำรายการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'ตกลง',
                        cancelTitle: 'ยกเลิก',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        centered: true
                    })
                        .then(value => {
                            if (value) {
                                this.$http.post("{{ url($menu->currentRoute.'/edit') }}", {
                                    id: code,
                                    status: status,
                                    method: method
                                })
                                    .then(response => {
                                        this.$bvModal.msgBoxOk(response.data.message, {
                                            title: 'ผลการดำเนินการ',
                                            size: 'sm',
                                            buttonSize: 'sm',
                                            okVariant: 'success',
                                            headerClass: 'p-2 border-bottom-0',
                                            footerClass: 'p-2 border-top-0',
                                            centered: true
                                        });
                                        window.LaravelDataTables["dataTableBuilder"].draw(false);
                                    })
                                    .catch(exception => {
                                        console.log('error');
                                    });
                            }
                        })
                        .catch(err => {
                            // An error occurred
                        })

                },
                addEditSubmit(event) {
                    event.preventDefault();
                    if (this.formmethod === 'add') {
                        var url = "{{ url($menu->currentRoute.'/create') }}";


                    } else if (this.formmethod === 'edit') {
                        var url = "{{ url($menu->currentRoute.'/update') }}";

                    }
                    this.$http.post(url, {id: this.code, data: this.formaddedit})
                        .then(response => {
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });
                            window.LaravelDataTables["dataTableBuilder"].draw(false);
                        })
                        .catch(exception => {
                            console.log('error');
                            this.toggleButtonDisable(false);
                        });


                },
                autoCnt(draw) {
                    const self = this;
                    this.toast = window.Toasty;
                    // this.toast = window.Toasty({
                    //     classname: "toast",
                    //     transition: "fade",
                    //     insertBefore: true,
                    //     duration: 1000,
                    //     enableSounds: true,
                    //     autoClose: true,
                    //     progressBar: true,
                    //     sounds: {
                    //         info: "storage/sound/alert.mp3",
                    //         success: "storage/sound/alert.mp3",
                    //         warning: "storage/sound/alert.mp3",
                    //         error: "storage/sound/alert.mp3",
                    //     }
                    // });

                    this.loadCnt();
                    // this.loadPing();

                    // setInterval(function () {
                    //     self.loadCnt();
                    //     // self.loadPing();
                    //     self.loopcnts++;
                    //     if (draw) {
                    //         window.LaravelDataTables["dataTableBuilder"].draw(false);
                    //     }
                    // }, 100000);

                },
                runMarquee() {
                    this.announce = $('#announce');
                    this.announce.marquee({
                        duration: 20000,
                        delayBeforeStart: 2000
                    });
                },
                async loadCnt() {
                    let err, response;
                    [err, response] = await axios.get("{{ url('loadcnt') }}").then(data => {
                        return [null, data];
                    }).catch(err => [err]);
                    if (err) {
                        return 0;
                    }

                    const res = response.data;
                    if(res.bank_in_today > 0){
                        updateBadge('bank_in', res.bank_in_today);
                    }else{
                        update('bank_in', res.bank_in_today);
                    }
                    if(res.bank_in > 0){
                        updateBadge('bank_in_old', res.bank_in);
                    }else{
                        update('bank_in_old', res.bank_in);
                    }
                    if(res.withdraw > 0){
                        updateBadge('withdraw', res.withdraw);
                    }else{
                        update('withdraw', res.withdraw);
                    }

                    // if(document.getElementById('badge_bank_in')){
                    //     document.getElementById('badge_bank_in').textContent = response.data.bank_in_today;
                    // }
                    // if(document.getElementById('badge_bank_in_old')){
                    //     document.getElementById('badge_bank_in_old').textContent = response.data.bank_in;
                    // }
                    // if(document.getElementById('badge_bank_out')){
                    //     document.getElementById('badge_bank_out').textContent = response.data.bank_out;
                    // }
                    // if(document.getElementById('badge_withdraw')){
                    //     document.getElementById('badge_withdraw').textContent = response.data.withdraw;
                    // }
                    // if(document.getElementById('badge_withdraw_seamless')){
                    //     document.getElementById('badge_withdraw_seamless').textContent = response.data.withdraw;
                    // }
                    // if(document.getElementById('badge_withdraw_free')){
                    //     document.getElementById('badge_withdraw_free').textContent = response.data.withdraw_free;
                    // }
                    // if(document.getElementById('badge_withdraw_seamless_free')){
                    //     document.getElementById('badge_withdraw_seamless_free').textContent = response.data.withdraw_free;
                    // }
                    // if(document.getElementById('badge_confirm_wallet')){
                    //     document.getElementById('badge_confirm_wallet').textContent = response.data.payment_waiting;
                    // }
                    // if(document.getElementById('badge_member_confirm')){
                    //     document.getElementById('badge_member_confirm').textContent = response.data.member_confirm;
                    // }

                    // document.getElementById('badge_bank_in').textContent = response.data.bank_in_today + ' / ' + response.data.bank_in;
                    // document.getElementById('badge_bank_out').textContent = response.data.bank_out;
                    // document.getElementById('badge_withdraw').textContent = response.data.withdraw;
                    // document.getElementById('badge_withdraw_free').textContent = response.data.withdraw_free;
                    // document.getElementById('badge_confirm_wallet').textContent = response.data.payment_waiting;
                    // document.getElementById('badge_member_confirm').textContent = response.data.member_confirm;
                    if (this.loopcnts == 0) {
                        document.getElementById('announce').textContent = response.data.announce;
                        this.runMarquee();
                    } else {
                        if (response.data.announce_new == 'Y') {
                            this.announce.on('finished', (event) => {
                                document.getElementById('announce').textContent = response.data.announce;
                                this.announce.trigger('destroy');
                                this.announce.off('finished');
                                this.runMarquee();
                            });

                        }
                    }
                    this.withdraw_cnt = response.data.withdraw;


                },
                async loadPing() {
                    var modals = [];
                    var self = this.toast.configure({
                        transition: "pinItUp",
                        duration: 4000
                    });

                    try {
                        let response = await axios.get("{{ url('ping') }}");

                        var count = (response.data.data.length + 1);

                        for (let i = 1; i < count; i++) {
                            setTimeout(function timer() {
                                self.warning(response.data.data[(i-1)])
                            }, i * 3000);
                        }

                    } catch (error) {
                        console.log(error)
                    }

                }
            },
        });

</script>



