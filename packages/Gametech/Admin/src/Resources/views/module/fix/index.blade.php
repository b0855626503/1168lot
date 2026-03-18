@extends('admin::layouts.master')

{{-- page title --}}
@section('title','แก้ไขปัญหาเบื้องต้น')



@section('content')

    <b-modal ref="moc" id="moc" centered :no-close-on-backdrop="true" :no-stacking="true" :hide-footer="true"
             :lazy="true" title="เคลียค่าแคชต่างๆ ของเวบไซต์">
        <div v-html="content"></div>
    </b-modal>

    <b-modal ref="notice" id="notice" centered :no-close-on-backdrop="true" :no-stacking="true" :hide-footer="true"
             :lazy="true" :title="title">
        <div v-html="content"></div>
    </b-modal>
    <!--suppress ALL -->
    <section class="content text-xs">
        <div class="row">
            <div class="col-lg-4 col-6">
                <div class="card card-primary card-outline h-100">

                    <div class="card-body">
                        <p class="card-text">1. หน้า Wallet ขึ้นขาว</p>
                        <p class="card-text">2. แก้ไขสีโลโก้แล้ว หน้า Wallet ไม่เปลี่ยนตาม</p>

                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary" onclick="Cpm()">กดเพื่อแก้ไข</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-6">
                <div class="card card-primary card-outline h-100">

                    <div class="card-body">
                        <p class="card-text">1. CashBack เชคแล้วหลัง ตี 1 ยังมอบไม่ครบ</p>
                        <p class="card-text">2. กดแค่ 1 ครั้งพอ</p>

                    </div>
                    <div class="card-footer">

                        @if(strtotime(now()->toTimeString()) > strtotime('01:00:00') && strtotime(now()->toTimeString()) < strtotime('03:00:00'))
                            <button class="btn btn-primary" onclick="Cashback()">กดเพื่อแก้ไข</button>
                        @else
                            <button class="btn btn-primary" disabled>กดเพื่อแก้ไข</button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-6">
                <div class="card card-primary card-outline h-100">

                    <div class="card-body">
                        <p class="card-text">1. IC เชคแล้วหลัง ตี 1 ยังมอบไม่ครบ</p>
                        <p class="card-text">2. กดแค่ 1 ครั้งพอ</p>

                    </div>
                    <div class="card-footer">
                        @if(strtotime(now()->toTimeString()) > strtotime('01:00:00') && strtotime(now()->toTimeString()) < strtotime('03:00:00'))
                            <button class="btn btn-primary" onclick="IC()">กดเพื่อแก้ไข</button>
                        @else
                            <button class="btn btn-primary" disabled>กดเพื่อแก้ไข</button>
                        @endif

                    </div>
                </div>
            </div>

        </div>

    </section>
@endsection

@push('scripts')


    <script type="text/javascript">
        function Cpm() {
            window.app.confirmOptimize();
        }

        function Cashback() {
            window.app.confirmCashback();
        }

        function IC() {
            window.app.confirmIC();
        }
    </script>
    <script type="module">
        import to from "./js/toPromise.js";

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
                    content: '',
                    title: '',
                }
            },
            created() {
                const self = this;
                self.autoCnt(false);
            },
            watch: {
                withdraw_cnt: function (event) {
                    if (event > 0) {
                        this.ToastPlay();
                    }
                }
            },
            methods: {
                confirmIC() {
                    // let moc = this.$refs.moc;

                    this.$bvModal.msgBoxConfirm('ยืนยันการทำรายการแก้ไขปัญหานี้.', {
                        title: 'กดตกลงเพื่อยืนยัน',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'YES',
                        cancelTitle: 'NO',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        centered: true
                    })
                        .then(value => {
                            if (value) {
                                this.$nextTick(() => {
                                    this.content = '';
                                    this.title = 'มอบ IC ให้กับลูกค้าใหม่';
                                    this.runIC();
                                    this.$refs.notice.show();
                                })

                            }
                        })
                        .catch(err => {
                            // An error occurred
                        })

                },
                async runIC() {
                    let self = this;
                    self.items = [];
                    const response = await axios.get("{{ route('admin.'.$menu->currentRoute.'.ic') }}");
                    this.content = response.data.data.toString().replace(/\n/g, "<br>");
                },
                confirmCashback() {
                    // let moc = this.$refs.moc;

                    this.$bvModal.msgBoxConfirm('ยืนยันการทำรายการแก้ไขปัญหานี้.', {
                        title: 'กดตกลงเพื่อยืนยัน',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'YES',
                        cancelTitle: 'NO',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        centered: true
                    })
                        .then(value => {
                            if (value) {
                                this.$nextTick(() => {
                                    this.content = '';
                                    this.title = 'มอบ Cashback ให้กับลูกค้าใหม่';
                                    this.runCashback();
                                    this.$refs.notice.show();
                                })

                            }
                        })
                        .catch(err => {
                            // An error occurred
                        })

                },
                async runCashback() {
                    let self = this;
                    self.items = [];
                    const response = await axios.get("{{ route('admin.'.$menu->currentRoute.'.cashback') }}");
                    this.content = response.data.data.toString().replace(/\n/g, "<br>");
                },
                confirmOptimize() {
                    // let moc = this.$refs.moc;

                    this.$bvModal.msgBoxConfirm('ยืนยันการทำรายการแก้ไขปัญหานี้.', {
                        title: 'กดตกลงเพื่อยืนยัน',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        okTitle: 'YES',
                        cancelTitle: 'NO',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        centered: true
                    })
                        .then(value => {
                            if (value) {
                                this.$nextTick(() => {
                                    this.content = '';
                                    this.runOptimize();
                                    this.$refs.moc.show();
                                })

                            }
                        })
                        .catch(err => {
                            // An error occurred
                        })

                },
                async runOptimize() {
                    let self = this;
                    self.items = [];
                    const response = await axios.get("{{ route('admin.'.$menu->currentRoute.'.optimize') }}");
                    this.content = response.data.data.toString().replace(/\n/g, "<br>");
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
                    let err, response;
                    [err, response] = await to(axios.get("{{ url('loadcnt') }}"));
                    if (err) {
                        return 0;
                    }
                    if (document.getElementById('badge_bank_in')) {
                        document.getElementById('badge_bank_in').textContent = response.data.bank_in_today + ' / ' + response.data.bank_in;
                    }
                    if (document.getElementById('badge_bank_out')) {
                        document.getElementById('badge_bank_out').textContent = response.data.bank_out;
                    }
                    if (document.getElementById('badge_withdraw')) {
                        document.getElementById('badge_withdraw').textContent = response.data.withdraw;
                    }
                    if (document.getElementById('badge_withdraw_free')) {
                        document.getElementById('badge_withdraw_free').textContent = response.data.withdraw_free;
                    }
                    if (document.getElementById('badge_confirm_wallet')) {
                        document.getElementById('badge_confirm_wallet').textContent = response.data.payment_waiting;
                    }
                    if (document.getElementById('badge_member_confirm')) {
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


