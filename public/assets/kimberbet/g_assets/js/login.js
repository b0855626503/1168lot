new Vue({
    el: '#app', mixins: [_V], data() {
        return {
            convert_redirect: {
                '/member/baccarat': '/member#/games/baccarat',
                '/member/poker': '/member#/games/poker',
                '/member/slot': '/member#/games/slot',
                '/member?game=sport': 'sport',
                '/member?game=lotto': 'lotto',
                '/member/recommended': '/member#/ref',
                '/member/deposit': '/member#/deposit',
                '/member/withdraw': '/member#/withdraw',
                'member/baccarat': '/member#/games/baccarat',
                'member/poker': '/member#/games/poker',
                'member/slot': '/member#/games/slot',
                'member?game=sport': 'sport',
                'member?game=lotto': 'lotto',
                'member/recommended': '/member#/ref',
                'member/deposit': '/member#/deposit',
                'member/withdraw': '/member#/withdraw',
            }, fm: {user_name: '', password: ''}, remember: false,
        }
    }, methods: {
        async submit() {
            let _this = this;
            this.redirect_hold = true;
            if (!/0\d{9}/.test(this.fm.user_name)) return modal.error('กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง');
            if (!/^\d{4}$/.test(this.fm.password)) return modal.error('กรุณากรอก Pin ให้ถูกต้อง');
            if (this.sch.has('target') || this.sch.has('game')) this.force_target = true;
            let res = await this.easy.callApi('login_pin', this.fm);
            if (!res.success) {
                return modal.error(res.data, res.title).then(r => {
                    if (res.code === 'ALREADY_LOGIN') return location.replace('/member');
                });
            }
            await modal.success('', res.title, {allowOutsideClick: false, showConfirmButton: false, timer: 1200});
            if (this.remember) localStorage.setItem('usr_remember', JSON.stringify(this.fm)); else localStorage.removeItem('usr_remember');
            this.redirect_hold = false;
            let sch = new URLSearchParams(window.location.search);
            let redirect = sch.get('redirect');
            if (!redirect) return location.replace('/member');
            let pathname = this.convert_redirect[redirect] ? this.convert_redirect[redirect] : '/member#';
            let dest = location.origin + pathname;
            switch (this.convert_redirect[redirect]) {
                case 'sport':
                    this.$root.play({vendor: 'sport'});
                    break;
                case 'lotto':
                    this.$root.play({vendor: 'lotto', new_tab: true});
                    break;
                default:
                    location.replace(dest);
            }
        }
    }, watch: {
        'fm.tel'(val) {
            if (val.length >= 10) $('#pinBox').focus();
        }, 'fm.pin'(val) {
            if (val.length === 4) $('#btnLog').focus();
        }
    }, async created() {
        let $this = this;
        let remember = localStorage.getItem('usr_remember');
        if (remember) {
            this.fm = JSON.parse(remember);
            this.remember = true;
        }
        const sch = this.sch;
        if (sch.has('tel')) {
            this.fm.tel = sch.get('tel');
            if (!sch.has('pin')) return;
            this.fm.pin = sch.get('pin');
            this.submit();
        }
        if (sch.has('lid') && sch.has('hid')) {
            if (!sch.has('redirect')) return;
            let to = sch.get('redirect');
            if (!this.convert_redirect[to]) return;
            await this.$root.logout(true);
            let rs = await this.$root.easy.callApi('login_line', {lid: sch.get('lid'), hid: sch.get('hid')});
            if (!rs.success) return;
            switch (this.convert_redirect[to]) {
                case 'sport':
                    this.$root.play({vendor: 'sport'});
                    break;
                case 'lotto':
                    this.$root.play({vendor: 'lotto', new_tab: true});
                    break;
                default:
                    this.convert_redirect[to] ? location.replace(location.origin + this.convert_redirect[to]) : location.replace(location.origin + '/member#');
            }
        }
    },
});