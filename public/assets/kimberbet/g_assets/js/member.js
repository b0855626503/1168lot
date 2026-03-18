Vue.component('entrance-games-theme1', () => import('/components/entrance_game_original.js?v=' + HASH));
Vue.component('entrance-games-theme2', () => import('/components/entrance_game_minitab.js?v=' + HASH));
Vue.component('entrance-games-theme3', () => import('/components/entrance_game_landscape.js?v=' + HASH));
const router = new VueRouter({
    routes: [{path: '/', name: 'home', component: member_index}, {
        path: '/deposit',
        name: 'deposit',
        component: member_deposit
    }, {path: '/deposit/slip', name: 'deposit-slip', component: member_deposit_slip}, {
        path: '/history',
        name: 'history',
        component: member_history
    }, {path: '/withdraw', name: 'withdraw', component: member_withdraw}, {
        path: '/profile',
        name: 'profile',
        component: member_profile
    }, {path: '/notify', name: 'notify', component: member_notify}, {
        path: '/ref',
        name: 'ref',
        component: member_ref
    }, {path: '/promotion', name: 'promotion', component: member_promotion}, {
        path: '/games',
        name: 'game',
        component: member_game
    }, {path: '/games/:type', name: 'game-sub', component: member_game_sub}, {
        path: '/games/:type/:vendor',
        name: 'game-sub-vendor',
        component: member_game_sub_list
    }, {path: '/games-enter', name: 'game-box', component: member_game_box},],
});

function authRouteFail(route) {
    if (route === 'home') return location.href = '/';
    if (!_.includes(['game', 'game-sub', 'game-sub-vendor'], route)) return location.href = '/login';
}

router.beforeEach((to, from, next) => {
    Vue.nextTick(function () {
        if (router.app.logged === false) {
            authRouteFail(to.name);
        }
        if (this.isSafari) window.scrollTo({top: 0, left: 0, behavior: 'smooth'});
        if ($('#navbarSupportedContent').length) {
            new bootstrap.Collapse(document.querySelector('#navbarSupportedContent'), {toggle: false}).hide();
        }
        next();
    });
});
var _member = new Vue({
    mixins: [_V], router, data() {
        return {
            user: {},
            next_game: null,
            next_meta: {},
            credit: 0,
            notify: {},
            el: {loginModal: null, sliderShow: true,},
            loading: false,
            select2: {
                bank_id: {
                    templateResult: (opt) => {
                        if (!opt.id) return opt.text;
                        let bank_id = $(opt.element).data('bank_id');
                        return `<img class='flag' src='/g_assets/img/bank-icon/${bank_id}.png'/><span class="str">${opt.text}</span>`;
                    }, templateSelection: (opt) => {
                        if (!opt.id) return opt.text;
                        let bank_id = $(opt.element).data('bank_id');
                        let acc_no = $(opt.element).data('acc_no');
                        let name = $(opt.element).data('name');
                        return $(opt.element).val() === '0' ? opt.text : `<img class='flag' src='/g_assets/img/bank-icon/${bank_id}.png'><span class="str">${opt.text}</span>`;
                    }, minimumResultsForSearch: -1, width: '100%', escapeMarkup: function (m) {
                        return m;
                    }
                }, imgLeft: {
                    templateResult: (opt) => {
                        if (!opt.id) return opt.text;
                        let logo = $(opt.element).data('img');
                        return `<img class="sel-img" src="${logo}"><span class="str">${opt.text}</span>`;
                    }, templateSelection: (opt) => {
                        if (!opt.id) return opt.text;
                        let logo = $(opt.element).data('img');
                        return $(opt.element).val() === '0' ? opt.text : `<img class="sel-img" src="${logo}"><span class="str">${opt.text}</span>`;
                    }, minimumResultsForSearch: -1, width: '100%', escapeMarkup: function (m) {
                        return m;
                    }
                },
            },
            latestDateUpdateCredit: '-',
            bankList: [],
            isSafari: false,
            playgame: false,
            fullscreen: false,
            currentPlay: '',
            showNavInGame: false,
            addHomeScreen: true,
            depositBank: [],
            deposit: {info: {}, bank_list: []},
            withdraw: {info: {}, fm: {amount: 0},},
            formWithdraw: {amount: ''},
            force_target: false,
            game_theme: {type: 1},
            mobile_menu_active: false
        }
    }, methods: {
        async logout(force = false) {
            if (!force) {
                let r = await modal.confirm('', 'ออกจากระบบ');
                if (!r) return;
            }
            await this.easy.callApi('logout');
        }, notifyRead() {
            if (this.notify.unread > 0) {
                socket.emit('notify_read');
                this.notify.unread = 0;
            }
        }, async play(g) {
            if (!this.logged) return this.$router.push({name: 'login'});
            this.loading = true;
            if (g.vendor) {
                this.currentPlay = g.vendor;
            }
            let res = await this.easy.callApi('game_enter', {
                vendor: g.vendor,
                game_id: g.game_id,
                game_code: g.game_code,
                mobile: this.device.is_mobile
            });
            this.loading = false;
            if (!res.success) return modal.error(res.data || 'ไม่สามารถเข้าเล่นเกมได้ในขณะนี้ เกมอาจปิดปรับปรุงชั่วคราว!', 'ไม่สามารถเข้าเล่นเกม');
            let arr_sub_keno = ['keno', 'atom', 'number', 'rng'];
            if (this.device.is_ios && arr_sub_keno.includes(g.vendor)) return window.open(res.data, '_blank');
            if (g.vendor === 'pg_slot' && !this.device.is_mobile && !res.fake) return window.open(res.data, '_blank');
            if (_.includes(['hotgraph', 'micro_gaming', 'esport'], g.vendor)) {
                if (this.device.is_ios) return location.href = res.data;
                return window.open(res.data, '_blank');
            }
            if (g.new_tab) return window.open(res.data, '_blank');
            this.next_meta = {};
            if (this.user.feature[0] == '1' && _.includes(['slot'], g.type)) {
                this.next_meta.soot = true;
                this.next_meta.gid = g.id;
            }
            this.next_game = res.data;
            this.$router.push({name: 'game-box'});
            this.fullscreen = true;
        }, editBankId(userBankId, systemBankId) {
            if (systemBankId == 999) {
                return 999;
            } else {
                return userBankId;
            }
        }, copyBankAcc(bankNum, taget = '') {
            let input = document.querySelector(`input.ip-copyfrom${taget}`);
            let copyVal = bankNum;
            input.value = copyVal;
            input.select();
            document.execCommand("copy");
            let toast_el = document.querySelector('.custom-toast-member');
            let toast_body = toast_el.querySelector('.toast-body');
            toast_body.innerText = `คัดลอกเลขบัญชี "${copyVal}" สำเร็จ`;
            new bootstrap.Toast(toast_el).show();
        }, async depositInfo() {
            let res = await this.easy.callApi('deposit_info');
            if (!res.success) return modal.error(res.data, res.title);
            this.deposit.info = res.data;
            console.log('depositInfo', res);
        }, depositModal() {
            let depositModal = new bootstrap.Modal(document.getElementById("depositModal"), {});
            depositModal.show();
            this.depositInfo();
        }, async withdrawInfo() {
            let res = await this.easy.callApi('withdraw_info');
            console.log('ddd', res)
            if (!res.success) return modal.error(res.data, res.title);
            this.withdraw.info = res.data;
        }, async withdrawSubmit() {
            if (!this.withdraw.info.withdrawable) return modal.error('ไม่สามารถถอนได้');
            if (member_style.min_withdraw_true_wallet && this.$root.user.bank_id == 999 && (this.$root.withdraw.fm.amount < 100 || (this.$root.withdraw.fm.amount % 10 !== 0))) return modal.error('ลูกค้าทรูต้องถอนเงินขั้นต่ำ 100 และต้องเป็นจำนวนเต็ม 10 เช่น 150,190');
            let a = await modal.confirmLoading('ยืนยันการถอนเงิน?', 'ยืนยัน');
            if (!a) return;
            let res = await this.easy.callApi('withdraw_req', this.withdraw.fm);
            this.withdrawReset();
            if (!res.success) return modal.error(res.data, res.title);
            modal.success(res.data, res.title);
        }, withdrawReset() {
            this.withdraw.fm = {amount: ''};
        }, withdrawModal() {
            this.withdrawReset();
            let withdrawModal = new bootstrap.Modal(document.getElementById("withdrawModal"), {});
            withdrawModal.show();
            this.withdrawInfo();
        }, convertGameName(name) {
            let obj = {
                lotto: 'แทงหวย',
                slot: 'สล็อต',
                baccarat: 'บาคาร่า',
                card: 'เกมส์ไพ่',
                poker: 'เกมส์ไพ่',
                sport: 'แทงบอล',
                keno: 'คีโน่',
                hotgraph: 'เทรด',
                fish: 'ยิงปลา'
            }
            if (!obj[name]) return name;
            return obj[name];
        }, async getBankList() {
            let res = await this.easy.callApi('bank_list');
            if (!res.success) return modal.error(res.data, res.title);
            this.deposit.bank_list = res.data;
        }, openSideMenu() {
            console.log('open side menu!');
            this.mobile_menu_active = !this.mobile_menu_active;
        }
    }, computed: {
        isLogged() {
            return (this.logged !== null && (typeof this.logged == 'boolean') && this.logged);
        }, cntNotifyUnread() {
            if (!this.notify.data) {
                return 0;
            } else {
                return (this.notify.data).filter(i => i.saw == 0).length;
            }
        }, getBankNameByBankId() {
            if (this.user.bank_id == 0) return '-';
            if (jQuery.isEmptyObject(this.deposit.bank_list)) return '-';
            if (!this.deposit.bank_list[this.user.bank_id]) return '-';
            return (this.deposit.bank_list[this.user.bank_id]).name;
        }
    }, mounted() {
        setTimeout(() => {
            let not_show_today = Cookies.get('not_show_pop_today');
            let member_popup = document.getElementById('memberPopup');
            if (member_popup && !not_show_today) {
                let pop = new bootstrap.Modal(member_popup);
                pop.show();
                if ($('#close_pop_count').length) {
                    let count_time = 3;
                    $('#pop_count_time').html(count_time);
                    let loop_count = setInterval(() => {
                        if ($('#pop_count_time').html() == 1) {
                            clearInterval(loop_count);
                            pop.hide();
                        }
                        count_time = count_time - 1;
                        $('#pop_count_time').html(count_time);
                    }, 1000);
                }
                $('#hide_pop_today input').on('click', () => {
                    Cookies.set('not_show_pop_today', 1, {expires: 1})
                    pop.hide();
                })
            }
        }, 1200)
        setTimeout(() => {
            let game_entrance_2_el = document.querySelector('#game_entrance_2');
            if (game_entrance_2_el) {
                this.game_entrance_minitab.init(game_entrance_2_el);
            }
        }, 500)
    }, updated() {
    }, created() {
        let ua = navigator.userAgent.toLowerCase();
        this.isSafari = ua.indexOf('safari') > -1 ? ua.indexOf('chrome') == -1 : false;
        let $this = this;
        this.$router.afterEach((to, from) => {
            $this.loading = false;
        });
        this.easy.on('error', dt => {
        }).on('notify', dt => {
            toast.info(dt.text || 'ไม่มีข้อความ', dt.title || 'แจ้ง');
            $this.last_event = dt;
        }).on('unread_push', dt => {
            if ($this.user) $this.user.unread = dt;
        }).on('easy_wheel_push', dt => {
            if ($this.user) $this.user.can_wheel = dt;
        }).on('login_status', (logged) => {
            if (!logged) {
                authRouteFail($this.$route.name);
            }
        }).on('kick', (data) => {
        }).on('chat_response', ({customer_id, data}) => {
        }).on('customer_data', data => {
            $this.user = data;
            $this.user.registerTime = humantime.full_th(data.created_at, true);
            $this.user.feature = typeof $this.user.feature === 'undefined' ? [] : $this.user.feature.split('');
        }).on('credit_push', dt => {
            if (!dt.success) return;
            $this.credit = dt.data['total_credit'];
            $this.latestDateUpdateCredit = moment().format('HH:mm:ss')
        });
        this.getBankList();
    }
});