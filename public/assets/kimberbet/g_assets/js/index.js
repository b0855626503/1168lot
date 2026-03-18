let md = new MobileDetect(window.navigator.userAgent);
const refs = ['member_ref', 'zean_ref', 'partner_ref'];

window._V = {
    el: '#app', data() {
        return {
            socket: null,
            sch: new URLSearchParams(window.location.search),
            easy: null,
            logged: null,
            loginFm: {username: '', password: '', captcha: ''},
            user: {},
            user_return: null,
            el: {loginModal: null,},
            device: {is_mobile: null, is_ios: null},
            loading: false,
            version_control: HASH,
            force_target: false,
            game_theme: {type: 1},
            redirect_hold: false,
            slot_formular: false
        }
    }, methods: {
        async logout(force = false) {
            if (!force) {
                let r = await modal.confirm('', 'ออกจากระบบ');
                if (!r) return;
            }
            await this.easy.callApi('logout');
        }, saveRef() {
            let $this = this;
            let schObj = Object.fromEntries($this.sch);
            const contain = Object.keys(schObj).some(o => {
                return refs.includes(o);
            });
            refs.forEach(o => {
                if (contain) localStorage.removeItem(o);
                if ($this.sch.has(o)) localStorage.setItem(o, $this.sch.get(o));
            })
        }, refClear() {
            refs.forEach(o => {
                localStorage.removeItem(o);
            })
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
    }, created() {
        let ua = navigator.userAgent.toLowerCase();
        this.isSafari = ua.indexOf('safari') > -1 ? ua.indexOf('chrome') == -1 : false;
        let md = new MobileDetect(window.navigator.userAgent);
        this.device = {is_mobile: md.mobile(), is_ios: md.is('iOS') || md.versionStr('iOS')};
        let $this = this;
    }
};