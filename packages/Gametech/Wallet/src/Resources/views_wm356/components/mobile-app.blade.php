<script type="text/x-template" id="mobile-app-template">
    <div class="add-home-screen-container add-home bg-dark-2 p-2 p-md-3 w-100 align-items-center d-flex"
         v-if="shouldShow">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="col left position-relative">
                <button type="button" class="close-add-to-home" @click="dismissPrompt"><span>&times;</span></button>
                <div class="fs-5 text-white">{{ __('app.pwa.addhome') }}</div>
                <div class="fw-light text-white">{{ __('app.pwa.text') }}</div>
            </div>
            <button class="right btn btn-white d-flex align-items-center rounded-pill shadow btn-add-to-home"
                    @click="promptInstall">
                <i class="bi bi-cloud-arrow-down-fill me-2"></i>
                <span class="d-flex flex-column lh-0 align-items-start">
            <span>APP</span>
            <span class="text-muted fst-italic" style="font-size: .575rem;">INSTALL</span>
          </span>
            </button>
        </div>
    </div>
    <div class="how-to-install-a2h-ios p-3 pt-3 pb-3" v-if="isIOS">
        <div class="header-content position-relative">
            <h4 class="text-white text-center">How to Install?</h4>
            <button class="reset-btn close-how-to-install" @click="dismissPrompt">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="body-content">
            <img class="d-block logo-app mx-auto rounded my-2" src="" alt="">
            <p class="fw-normal lh-1 mt-4 px-3">This is a new format that can be installed right from the browser.</p>
        </div>
        <div class="footer-content-ios-chrome justify-content-center align-items-center mt-auto p-3">
            <img class="ic_footer" src="/assets/kimberbet/img/icon/ic_safari_rounded.svg" alt="">
            <p class="px-3 lh-1 my-0">Please open website in <span class="text-danger">safari</span> to install app.</p>
        </div>
        <div class="footer-content-ios-safari justify-content-center align-items-center mt-auto p-3">
            <img class="ic_footer" src="/assets/kimberbet/img/icon/btn_a2h_safari.svg" alt="">
            <p class="px-3 lh-1 my-0">To install the App simply tap and then Add to home screen</p>
            <img class="ic_footer a2h" src="/assets/kimberbet/img/icon/btn_plus_safari.svg" alt="">
        </div>
    </div>
</script>

@push('components')

    <script type="module">
        Vue.component('mobile-app-prompt', {
            template: '#mobile-app-template',
            data() {
                return {
                    deferredPrompt: null,
                    isIOS: false,
                    shouldShow: false,
                };
            },
            mounted() {
                this.isIOS = /iphone|ipad|ipod/.test(navigator.userAgent.toLowerCase());

                // Optional: clear dismissed if login or new session
                const userSessionKey = localStorage.getItem('session_user') || '';
                const currentUser = window?.app?.user?.id || '';
                if (userSessionKey !== currentUser) {
                    localStorage.removeItem('a2h_dismissed_until');
                    localStorage.setItem('session_user', currentUser);
                }

                const dismissed = localStorage.getItem('a2h_dismissed');
                const installed = localStorage.getItem('a2h_installed');
                const dismissedUntil = localStorage.getItem('a2h_dismissed_until');
                const now = Date.now();

                if (!installed && (!dismissedUntil || now >= parseInt(dismissedUntil))) {
                    window.addEventListener('beforeinstallprompt', (e) => {
                        e.preventDefault();
                        this.deferredPrompt = e;
                        this.shouldShow = true;
                        this.$nextTick(() => {
                            const el = document.querySelector('.add-home-screen-container');
                            if (el) {
                                el.style.opacity = '1';
                                el.style.visibility = 'visible';
                            }
                        });
                    });
                }

                window.addEventListener('appinstalled', () => {
                    localStorage.setItem('a2h_installed', 'true');
                    this.shouldShow = false;
                });
            },
            methods: {
                dismissPrompt() {
                    this.shouldShow = false;
                    const days = 1;
                    const expiration = Date.now() + days * 24 * 60 * 60 * 1000;
                    localStorage.setItem('a2h_dismissed_until', expiration.toString());
                },
                promptInstall() {
                    if (this.deferredPrompt) {
                        this.deferredPrompt.prompt();
                        this.deferredPrompt.userChoice.then(choice => {
                            if (choice.outcome === 'accepted') {
                                localStorage.setItem('a2h_installed', 'true');
                                this.shouldShow = false;
                            }
                            this.deferredPrompt = null;
                        });
                    }
                }
            }
        });
    </script>
@endpush