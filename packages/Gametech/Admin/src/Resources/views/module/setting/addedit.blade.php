{{--<b-modal ref="addeditsub" id="addeditsub" centered size="sm" title="เพิ่มรายการ" :no-stacking="false"--}}
{{--         :no-close-on-backdrop="true" :hide-footer="true">--}}
{{--    <b-form @submit.stop.prevent="addEditSubmitNewSub">--}}

{{--        <b-form-group--}}
{{--            id="input-group-bank_code"--}}
{{--            label="ลูกค้าที่สมัครด้วย:"--}}
{{--            label-for="bank_code"--}}
{{--            description="ลูกค้าที่สมัครด้วย ธนาคารนี้">--}}

{{--            <b-form-select--}}
{{--                id="bank_code"--}}
{{--                name="bank_code"--}}
{{--                v-model="formsub.bank_code"--}}
{{--                :options="option.bank_code"--}}
{{--                size="sm"--}}
{{--                required--}}
{{--            ></b-form-select>--}}

{{--        </b-form-group>--}}

{{--        <b-form-group--}}
{{--            id="input-group-method"--}}
{{--            label="รูปแบบ:"--}}
{{--            label-for="method"--}}
{{--            description="">--}}

{{--            <b-form-select--}}
{{--                id="method"--}}
{{--                name="method"--}}
{{--                v-model="formsub.method"--}}
{{--                :options="option.method"--}}
{{--                size="sm"--}}
{{--                required--}}
{{--            ></b-form-select>--}}

{{--        </b-form-group>--}}


{{--        <b-form-group--}}
{{--            id="input-group-bank_number"--}}
{{--            label="ธนาคาร:"--}}
{{--            label-for="bank_number"--}}
{{--            description="">--}}
{{--            <b-form-select--}}
{{--                class="select2"--}}
{{--                multiple="multiple"--}}
{{--                id="bank_number"--}}
{{--                name="bank_number"--}}
{{--                v-model="formsub.bank_number"--}}
{{--                :options="option.bank_number"--}}
{{--                size="sm"--}}
{{--                required--}}
{{--            ></b-form-select>--}}

{{--        </b-form-group>--}}

{{--        <b-button type="submit" variant="primary">บันทึก</b-button>--}}

{{--    </b-form>--}}
{{--</b-modal>--}}

@push('scripts')
    <script type="text/javascript">
        function addSubModal() {
            window.app.addSubModal();
        }
    </script>
    <script type="module">

        window.app = new Vue({
            el: '#app',

            data: function () {
                return {
                    loopcnts: 0,
                    announce: '',
                    pushmenu: '',
                    toast: '',
                    withdraw_cnt: 0,
                    played: false,
                    showsub: false,
                    formsub: {
                        deposit_amount: 0,
                        deposit_stop: 0,
                        amount: 0,
                    },
                    option: {
                        bank_code: [],
                        bank_number: [],
                    }
                }
            },
            created() {
                const self = this;
                self.autoCnt(false);

                this.loadBank();
            },
            watch: {
                withdraw_cnt: function (event) {
                    if (event > 0) {
                        this.ToastPlay();
                    }
                }
            },

            methods: {
                addSubModal() {
                    this.formsub = {
                        deposit_amount: 0,
                        deposit_stop: 0,
                        amount: 0
                    }
                    this.formmethodsub = 'add';

                    this.showsub = false;
                    this.$nextTick(() => {
                        this.showsub = true;
                        this.$refs.addeditsub.show();

                    })
                },
                delSub(code, table) {
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
                                this.$http.post("{{ url($menu->currentRoute.'/deletesub') }}", {
                                    id: code, method: table
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
                                        this.$refs.tbdata.refresh();

                                    })
                                    .catch(errors => console.log(errors));
                            }
                        })
                        .catch(errors => console.log(errors));
                },
                autoCnt(draw) {
                    const self = this;
                    this.toast = new Toasty({
                        classname: "toast",
                        transition: "fade",
                        insertBefore: true,
                        duration: 1000,
                        enableSounds: true,
                        autoClose: true,
                        progressBar: true,
                        sounds: {
                            info: "sound/alert.mp3",
                            success: "sound/alert.mp3",
                            warning: "vendor/toasty/dist/sounds/warning/1.mp3",
                            error: "storage/sound/alert.mp3",
                        }
                    });
                    this.loadCnt();

                    setInterval(function () {
                        self.loadCnt();
                        self.loopcnts++;
                        // self.$refs.deposit.loadData();
                    }, 50000);

                },

                runMarquee() {
                    this.announce = $('#announce');
                    this.announce.marquee({
                        duration: 20000,
                        startVisible: false
                    });
                },
                ToastPlay() {

                    this.toast.error('<span class="text-danger">มีการถอนรายการใหม่</span>');
                },
                async loadCnt() {
                    const response = await axios.get("{{ url('loadcnt') }}");
                    if(document.getElementById('badge_bank_in')){
                        document.getElementById('badge_bank_in').textContent = response.data.bank_in_today + ' / ' + response.data.bank_in;
                    }
                    if(document.getElementById('badge_bank_out')){
                        document.getElementById('badge_bank_out').textContent = response.data.bank_out;
                    }
                    if(document.getElementById('badge_withdraw')){
                        document.getElementById('badge_withdraw').textContent = response.data.withdraw;
                    }
                    if(document.getElementById('badge_withdraw_free')){
                        document.getElementById('badge_withdraw_free').textContent = response.data.withdraw_free;
                    }
                    if(document.getElementById('badge_confirm_wallet')){
                        document.getElementById('badge_confirm_wallet').textContent = response.data.payment_waiting;
                    }
                    if(document.getElementById('badge_member_confirm')){
                        document.getElementById('badge_member_confirm').textContent = response.data.member_confirm;
                    }
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

                }
            }
        });

    </script>
@endpush

