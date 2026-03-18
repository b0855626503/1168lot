<script type="text/x-template" id="navbar-template">
    <nav id="main-nav" class="navbar navbar-expand-sm navbar-light">
        <div class="container" style="max-height: 100%">
            <div class="d-inline-flex align-items-center ham-menu">
                <button class="navbar-toggler p-0" type="button" @click="toggleNavbar"
                        aria-controls="navbarSupportedContent" :aria-expanded="isOpen.toString()"
                        aria-label="Toggle navigation" style="height: 35px; width: 35px;">
                    <span class="bi bi-list bi-2x text-light"></span>
                </button>
            </div>

            <a href="{{ route('customer.session.index') }}"
               class="navbar-brand m-0 d-flex align-items-center router-link-exact-active">
                <img id="main-logo" src="{{ url(core()->imgurl($webconfig->logo,'img')) }}">
            </a>

            <div id="auth-wrapper" class="group-button-user p-1 rounded-pill login-b">
                <div class="d-none d-md-inline-flex">
                    <a href="{{ route('customer.session.destroy') }}"
                       class="nav-link register-btn btn btn-custom-secondary rounded-pill d-flex align-items-center pt-1 pb-1 text-white justify-content-center homeregis"
                       aria-label="logout">
            <span class="fw-bold text-highlight d-flex align-items-center">
              <i class="bi bi-box-arrow-right me-2 nav-icon text-white"></i> @{{ trans('app.home.logout') }}
            </span>
                    </a>
                </div>
            </div>

            <div class="collapse navbar-collapse navbar-content-index" :class="{ show: isOpen }"
                 id="navbarSupportedContent">
                <div class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item header-group-menu pt-3">
                        <span>Pages</span>
                    </li>

                    <li class="nav-item bg-box-1 nc-home btn-home">
                        <a href="{{ route('customer.home.index') }}"
                           class="nav-link btn btn-box-1 d-flex align-items-center btn-lg position-relative">
                            <span class="text-highlight">@{{ trans('app.login.home') }}</span>
                        </a>
                    </li>

                    <li class="nav-item bg-box-1 btn-contact">
                        <a href="{{ $webconfig->linelink }}"
                           class="nav-link btn btn-box-1 d-flex align-items-center btn-lg position-relative"
                           target="_blank">
                            <span class="text-highlight">@{{ trans('app.home.contact') }}</span>
                        </a>
                    </li>

                    <li class="nav-item bg-box-1 w-100 d-flex justify-content-center btn-deposit">
                        <button type="button" class="btn nav-link custom btn-box-1 d-flex align-items-center btn-lg"
                                @click="openDepositModal">
                            <span class="nav-item-text text-highlight">@{{ trans('app.home.deposit') }}</span>
                        </button>
                    </li>

                    <li class="nav-item bg-box-1 w-100 d-flex justify-content-center flex-lg-fill btn-withdraw">
                        <button type="button" class="btn nav-link custom btn-box-1 d-flex align-items-center btn-lg"
                                @click="openWithdrawModal">
                            <span class="nav-item-text text-highlight">@{{ trans('app.home.withdraw') }}</span>
                        </button>
                    </li>

                    <li class="nav-item bg-box-1 btn-language dropdown d-none d-md-block">
                        <a class="nav-link btn btn-box-1 d-flex align-items-center btn-lg position-relative dropdown-toggle"
                           href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="text-highlight">@{{ trans('app.login.language') }}</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            @foreach($languages as $code => $lang)
								<li>
									<a class="dropdown-item"
									   href="javascript:void(0)"
									   onclick="AppLocale.switchTo('{{ $code }}')">
										<img src="/images/flag/{{ $code }}.png" width="32" height="32"
											 class="img img-fluid img-sm">
										{{ $lang['name'] }}
									</a>
								</li>
                            @endforeach
                        </ul>
                    </li>


                    <li class="nav-item header-group-menu pt-3">
                        <span>@{{ trans('app.login.language') }}</span>
                    </li>
                    @foreach($languages as $code => $lang)
                        <li class="nav-item bg-box-1 nc-home btn-language d-block d-md-none">
                            <a href="javascript:void(0)"
                               class="nav-link btn btn-box-1 d-flex align-items-center btn-lg position-relative"
                               onclick="AppLocale.switchTo('{{ $code }}')">
        <span class="text-highlight">
            <img src="/images/flag/{{ $code }}.png" width="32" height="32"
                 class="img img-fluid img-sm">
            {{ $lang['name'] }}
        </span>
                            </a>
                        </li>
                    @endforeach


                    <li class="nav-item header-group-menu pt-3">
                        <span>Games</span>
                    </li>

                    <li v-for="menu in menus" :key="menu.key" :class="`btn-${menu.key}`"
                        class="nav-item cutom-game-entry d-flex justify-content-center invert-color position-relative">
                        <a href="javascript:void(0)"
                           class="nav-link btn btn-box-1 d-flex align-items-center btn-lg"
                           @click="selectTabOrRedirect(menu.key)">
                            <span class="nav-item-text text-highlight" v-text="getMenuLabel(menu.key)"></span>
                        </a>
                    </li>

                    <li class="nav-item bg-box-2 d-inline-flex ms-2 logout-mobile" style="margin-bottom:5em;">

                        <a href="{{ route('customer.session.destroy') }}"
                           class="border-0 text-decoration-none shadow px-3 btn-custom-secondary rounded-pill d-flex justify-content-center align-items-center nav-link btn btn-box-2 flex-grow-1">
                            <i class="bi bi-box-arrow-right me-1 nav-icon pe-1"
                               style="color: rgb(181, 60, 60) !important;"></i>
                            <span style="color: rgb(244, 170, 170) !important;padding-left:0 !important;">@{{ trans('app.home.logout') }}</span>
                        </a>


                    </li>
                </div>
            </div>
        </div>
    </nav>
</script>

@push('components')

    <script type="module">

        Vue.component('navbar-component', {
            template: '#navbar-template',
            data() {
                return {
                    isOpen: false,
                    showLangList: false
                };
            },
            computed: {
                getMenuLabel() { return key => window.translations[key] || key; },
                menus() {
                    return (this.$root && this.$root.$data && this.$root.$data.menus) || [];
                },
            },
            methods: {
                // ✅ ปิดให้ชัวร์ ไม่ toggle
                hardClose() { this.isOpen = false; },

                toggleNavbar() { this.isOpen = !this.isOpen; },

                // ✅ ใช้ตัวนี้แทน onclick เดิมของภาษา
                async switchLang(code) {
                    try {
                        await window.AppLocale?.switchTo?.(code);
                    } finally {
                        this.hardClose();
                    }
                },

                selectTabOrRedirect(tabKey) {
                    localStorage.setItem('selectTabKey', tabKey);

                    if (window.location.pathname === '/member') {
                        // ❗ เดิมเคย toggle → เปลี่ยนเป็นปิดชัวร์
                        this.hardClose();
                        const vueInstance = this.$root;
                        vueInstance.$refs.gameTabComponent?.selectTab(tabKey);
                        document.querySelector('#gametab')?.scrollIntoView({ behavior: 'smooth' });
                    } else {
                        window.location.href = '/member';
                    }
                },

                selectGameType(key) {
                    // ❗ เดิมเคย toggle → เปลี่ยนเป็นปิดชัวร์
                    this.hardClose();
                    this.$root.$refs.gameTabComponent.selectTab(key);
                },

                openDepositModal() {
                    this.hardClose();
                    this.$root.$refs.depositModalComponent.showModal();
                },

                openWithdrawModal() {
                    this.hardClose();
                    this.$root.$refs.withdrawModalComponent.showModal();
                },

                redirectTo(url) {
                    this.hardClose();
                    window.location.href = url;
                },

                submitLogout() {
                    this.hardClose();
                    this.$refs.logoutForm?.submit?.();
                }
            },

            mounted() {
                // ✅ ปิด navbar เมื่อภาษาเปลี่ยน (มาจากที่อื่นก็ได้)
                window.addEventListener('app:locale:changed', this.hardClose);

                // ✅ เดลิเกต: คลิกลิงก์/ปุ่มใดๆ ภายในเมนูให้ปิด (ยกเว้นปุ่ม dropdown-toggle)
                this.$nextTick(() => {
                    const root = this.$el.querySelector('.navbar-content-index');
                    if (!root) return;
                    root.addEventListener('click', (e) => {
                        const hit = e.target.closest('a,button');
                        if (!hit) return;
                        if (hit.matches('[data-bs-toggle="dropdown"], .dropdown-toggle')) return;
                        this.hardClose();
                    });
                });
            },

            beforeDestroy() {
                window.removeEventListener('app:locale:changed', this.hardClose);
            }
        });


    </script>
@endpush

