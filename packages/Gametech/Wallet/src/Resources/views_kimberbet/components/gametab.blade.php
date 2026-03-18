<script type="text/x-template" id="gametab-template">

    <div class="member__games_entrance entrance_games_theme game_type-container container-fluid position-relative member-menu-bg py-1 mt-2">
        <div class="fs-4 mt-3 text-start" v-text="trans('app.home.game')"></div>
        <div class="interior" style="min-height: 750px;">
            <div class="input-group mb-3 mt-3 position-absolute top-0 end-0 me-3 custon-input-search"
                 style="min-width: 250px; max-width: 50%;">

                <input type="search" maxlength="30" v-model="searchGame" :placeholder="trans('app.input.search')"
                       class="form-control" v-if="selectedProvider">
                <input type="search" maxlength="30" v-model="searchProvider" :placeholder="trans('app.input.search')"
                       class="form-control" v-else>
                <label class="input-group-text">
                    <i class="bi bi-search fw-bolder"></i>
                </label>
            </div>
            <div class="entrance_games_theme-container">
                <div class="entrance_games_theme-2 d-flex pt-2" style="min-height: 750px;">
                    <ul id="menuTypeGame_theme2" class="menu_games ps-0">

                        <li class="menu_item p-2 d-flex flex-column justify-content-center align-items-center tabgamelink"
                            :class="{ active: selectedTab === type.key }" v-for="(type, i) in categories"
                            :key="type.key" @click="selectTabScroll(type.key)">
                            <img
                                    :src="`/assets/kimberbet/images/icon/mini_${type.key}.svg`"
                                    alt=""
                                    class="menu-icon"
                                    loading="lazy"
                                    decoding="async"
                                    v-on:error="handleImgError($event)"/>
                            <span class="menu-name lh-1 pt-1 small fw-light text-white"
                                  v-text="getGameLabel(type.key)"></span>
                        </li>

                    </ul>

                    <div class="list_games_wrapper col ps-3 position-relative"
                         style="min-height: 750px;">

                        <div v-if="selectedProvider">
                            <div class="gamecontent" style="display:block;">
                                <div class="list_games_wrapper col ps-3 position-relative" style="min-height: 750px;">
                                    <div class="title-provider-name text-center d-flex justify-content-center">
                                        <span class="text-content title py-1 lead" v-text="selectedProvider"></span>
                                        <button class="btn-back-to-provider reset-btn" @click="selectedProvider = null">
                                            <i class="bi bi-arrow-left"></i>
                                        </button>
                                    </div>
                                    <ul class="list_games w-100 d-flex justify-content-center list-unstyled sub-list"
                                        style="">

                                        <li
                                                v-for="(item, index) in limitedGames"
                                                :key="(item.id || item.gameId || index) + '-' + (selectedProvider?.provider || selectedProvider || '')"
                                                :class="['game-item', getGameItemClass(item)]"
                                        >
                                            <div class="game-title w-100 text-content text-center" v-text="item.gameName"></div>

                                            <div class="img-wrap ratio-3x4">
                                                <img
                                                        v-lazyimg="item.image.vertical"
                                                        alt=""
                                                        class="game-img w-100 fade-in-img"
                                                        loading="lazy"
                                                        decoding="async"
                                                        v-on:load="onImgLoad"
                                                        v-on:error="handleImgError($event)"
                                                />
                                            </div>

                                            <div class="d-flex justify-content-center">
                                                <button class="py-1 btn btn-custom-primary btn-play d-flex justify-content-center align-items-center"
                                                        v-on:click.prevent="openGamePopup(item)">
                                                    <i class="bi bi-controller me-1"></i> <span v-text="trans('app.home.playgame')"></span>
                                                </button>
                                            </div>
                                        </li>

                                        <!-- sentinel ท้ายลิสต์ -->
                                        <li class="games-sentinel" ref="gamesSentinel" aria-hidden="true"></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div v-else>
                            <div class="gamecontent" style="display:block;">

                                <div class="title-provider-name text-center d-flex justify-content-center">
                                    <span class="text-content title py-1 lead"
                                          v-text="getGameLabel(selectedTab)"></span>
                                </div>

                                <ul class="list_providers w-100 d-flex justify-content-center list-unstyled sub-list"
                                    style="">

                                    <li class="provider_item d-flex flex-column fade-in"
                                        v-for="(item, index) in filteredProviders"
                                        :key="item.provider"
                                        @click.prevent="item.gameList === false ? openGamePopup(item) : loadGames(item)">
                                        <div class="game-title w-100  text-content text-center"
                                             v-text="item.providerName"></div>
                                        <div class="img-wrap ratio-1x1">
                                            <img v-lazyimg="item.logoURL"
                                                 alt=""
                                                 class="game-img w-100 fade-in-img"
                                                 loading="lazy"
                                                 decoding="async"
                                                 v-on:load="onImgLoad"
                                                 v-on:error="handleImgError($event)"/>
                                        </div>
                                    </li>

                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

</script>

@push('styles')
    @verbatim
        <style>
            /* Reserve space to avoid layout shifts */
            .img-wrap {
                position: relative;
                width: 100%;
                overflow: hidden;
                background: rgba(255, 255, 255, 0.04);
            }

            .img-wrap.ratio-3x4 {
                aspect-ratio: 3 / 4;
            }

            .img-wrap.ratio-1x1 {
                aspect-ratio: 1 / 1;
            }

            .fade-in-img {
                opacity: 0;
                transition: opacity .25s ease;
                display: block;
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .fade-in-img.is-loaded {
                opacity: 1;
            }

            /* Optional: containment to reduce paint cost on many cards */
            .gamecontent, .list_providers {
                contain: content;
            }

            /* Graceful image placeholder */
            .img-wrap::before {
                content: "";
                position: absolute;
                inset: 0;
                background: linear-gradient(90deg, rgba(255, 255, 255, .08), rgba(255, 255, 255, .16), rgba(255, 255, 255, .08));
                background-size: 200% 100%;
                animation: shimmer 1.2s infinite;
            }

            .img-wrap.loaded::before {
                display: none;
            }

            @keyframes shimmer {
                0% {
                    background-position: 200% 0;
                }
                100% {
                    background-position: -200% 0;
                }
            }

            /* ข้าม layout/paint ของการ์ดที่อยู่นอกจอ (เบราว์เซอร์ใหม่รองรับ) */
            .game-item{
                content-visibility: auto;
                contain-intrinsic-size: 360px 240px; /* ประมาณการความสูงกว้างของการ์ด ช่วยให้ scrollBar คงที่ */
            }

            /* ลด motion เพื่อเครื่องช้า/ผู้ใช้ตั้งค่าไว้ */
            @media (prefers-reduced-motion: reduce){
                .fade-in-img{ transition: none !important; }
                .img-wrap::before{ animation: none !important; }
            }
        </style>
    @endverbatim
@endpush

@push('components')

    <script type="module">
        Vue.component('gametab', {
            template: '#gametab-template',
            props: {
                apiGetgameTemplate: String,
                apiGetproviderTemplate: String,
                apiGetloginTemplate: String,
            },
            directives: {
                lazyimg: {
                    inserted(el, binding) {
                        // If browser supports native loading=lazy, still use IntersectionObserver for better control
                        const load = () => {
                            const src = binding.value;
                            if (!src) return;
                            el.setAttribute('src', src);
                        };
                        // If already has src (SSR) skip
                        if (el.getAttribute('src')) return;

                        // Prepare element for lazy
                        el.setAttribute('alt', el.getAttribute('alt') || '');
                        // Observer
                        const io = new IntersectionObserver((entries) => {
                            entries.forEach(entry => {
                                if (entry.isIntersecting) {
                                    load();
                                    io.disconnect();
                                }
                            });
                        }, {rootMargin: '200px 0px', threshold: 0.01});
                        io.observe(el);
                    }
                }
            },

            data: function () {
                return {
                    providerList: [],
                    selectedTab: 'slot',
                    selectedProvider: null,
                    providerGames: [],
                    searchGame: '',
                    searchProvider: '',
                    shownItems: [],
                    renderedGameIds: [],
                    renderKey: 0,
                    customClassMap: {},
                    visibleGames: [],         // รายการที่แสดงจริง
                    pageSize: 24,             // จำนวนชิ้นตอนเริ่ม
                    pageIncrement: 24,        // จำนวนชิ้นที่โหลดเพิ่มแต่ละครั้ง
                    pageEnd: 0,
                    ioGames: null,            // IntersectionObserver ของ sentinel
                    appendLock: false,        // กันยิงโหลดซ้อน
                }
            },
            computed: {
                categories() {
                    return (this.$root && this.$root.$data && this.$root.$data.menus) || [];
                },
                getGameLabel() {
                    return key => window.translations[key] || key;
                },
                sortedProviders() {
                    return this.providerList;
                },
                // sortedProviders() {
                //     return this.providerList.slice().sort((a, b) =>
                //         a.providerName.localeCompare(b.providerName, 'en', {sensitivity: 'base'})
                //     );
                // },
                limitedGames() {
                    const list = this.filteredGames || [];
                    return list.slice(0, this.pageEnd);
                },
                filteredGames() {
                    if (!this.searchGame) return this.providerGames;

                    return this.providerGames.filter(g =>
                        (g.gameName || '').toLowerCase().includes(this.searchGame.toLowerCase())
                    );
                },
                filteredProviders() {
                    let filtered = this.sortedProviders;
                    if (this.searchProvider.trim() !== '') {
                        const search = this.searchProvider.trim().toLowerCase();
                        filtered = filtered.filter(p => (p.providerName || '').toLowerCase().includes(search));
                    }
                    return filtered;
                },
            },
            methods: {
                setupGamesInfinite() {
                    const el = this.$refs.gamesSentinel;
                    if (!el) return;

                    // disconnect เดิมก่อน
                    if (this.ioGames) {
                        this.ioGames.disconnect();
                        this.ioGames = null;
                    }

                    const loadMore = () => {
                        const total = (this.filteredGames || []).length;
                        if (this.pageEnd >= total) return;
                        this.pageEnd = Math.min(this.pageEnd + this.pageIncrement, total);
                    };

                    if ('IntersectionObserver' in window) {
                        this.ioGames = new IntersectionObserver((entries) => {
                            entries.forEach(entry => {
                                if (entry.isIntersecting) loadMore();
                            });
                        }, { root: null, rootMargin: '600px 0px', threshold: 0 });
                        this.ioGames.observe(el);
                    } else {
                        // fallback: scroll passive
                        const onScroll = this.throttle(() => {
                            const rect = el.getBoundingClientRect();
                            if (rect.top < window.innerHeight + 600) loadMore();
                        }, 150);
                        window.addEventListener('scroll', onScroll, { passive: true });
                        this.$once('hook:beforeDestroy', () => window.removeEventListener('scroll', onScroll));
                    }
                },

                throttle(fn, wait = 100) {
                    let last = 0, timer = null;
                    return function (...args) {
                        const now = Date.now();
                        if (now - last >= wait) {
                            last = now; fn.apply(this, args);
                        } else if (!timer) {
                            timer = setTimeout(() => { last = Date.now(); timer = null; fn.apply(this, args); }, wait - (now - last));
                        }
                    };
                },
                resetVisibleGames() {
                    if (this.ioGames) this.ioGames.disconnect();
                    this.visibleGames = [];
                    // เติมหน้าแรกแบบ time-sliced เพื่อไม่บล็อก
                    this.appendMoreGames({ initial: true }).then(() => this.setupGamesInfinite());
                },

                // เติมรายการแบบเป็นก้อน + time-slicing
                async appendMoreGames({ initial = false } = {}) {
                    if (this.appendLock) return;
                    this.appendLock = true;

                    const start = this.visibleGames.length;
                    const batch = initial ? this.pageSize : this.pageIncrement;
                    const end = Math.min(start + batch, this.filteredGames.length);
                    const slice = this.filteredGames.slice(start, end);

                    // time-slice: แบ่งย่อยเป็นชิ้นเล็ก ๆ ต่อเฟรม ลด jank
                    const chunks = 6; // แบ่ง 6 ครั้งต่อ page
                    const perChunk = Math.ceil(slice.length / chunks);

                    await new Promise((resolve) => {
                        let i = 0;
                        const step = () => {
                            const from = i * perChunk;
                            const to = Math.min(from + perChunk, slice.length);
                            if (from >= to) {
                                this.appendLock = false;
                                return resolve();
                            }
                            // ต่อข้อมูลก้อนนี้เข้า visibleGames ทีละก้อน (ลด reactivity overhead)
                            const add = slice.slice(from, to);
                            this.visibleGames = this.visibleGames.concat(add);

                            // ใช้ rAF / rIC เพื่อปล่อยเฟรม
                            if ('requestIdleCallback' in window) {
                                requestIdleCallback(step, { timeout: 100 });
                            } else {
                                requestAnimationFrame(step);
                            }
                            i++;
                        };
                        step();
                    });
                },

                onImgLoad(evt) {
                    const img = evt.target;
                    img.classList.add('is-loaded');
                    const wrap = img.closest('.img-wrap');
                    if (wrap) wrap.classList.add('loaded');
                },

                getItemStyle(index) {
                    return {
                        animationDelay: `${index * 100}ms`
                    };
                },
                getGameItemClass(item) {
                    if (this.customClassMap[item.id]) {
                        return this.customClassMap[item.id];
                    }

                    return ['game_item', 'd-flex', 'flex-column', 'fade-in'];
                },
                markAsRendered(item) {
                    if (item.rtp && item.rtp > 100) {
                        // ถ้า RTP > 95 → ใส่ bounce
                        this.$set(this.customClassMap, item.id, [
                            'game_item',
                            'd-flex',
                            'flex-column',
                            'animate__animated',
                            'animate__bounce',
                            'animate__infinite'
                        ]);
                    } else {
                        // ค่า default หลัง animation
                        this.$set(this.customClassMap, item.id, [
                            'game_item',
                            'd-flex',
                            'flex-column'
                        ]);
                    }
                },
                handleImgError(event) {
                    event.target.src = '/assets/kimberbet/images/icon/mini_slot.svg';
                },
                trans(key, replace = {}) {
                    try {
                        // รองรับกรณีมี prefix 'app.' ใน key → ตัดออกอัตโนมัติ
                        if (typeof key === 'string' && key.startsWith('app.')) {
                            key = key.slice(4); // 'app.'.length = 4
                        }

                        // รองรับทั้งโครงสร้าง i18n และ i18n.app
                        const dictRoot = (window.i18n && (window.i18n.app || window.i18n)) || {};

                        // เดินหา key แบบ a.b.c
                        const parts = String(key).split('.');
                        let t = dictRoot;
                        for (let i = 0; i < parts.length; i++) {
                            const p = parts[i];
                            if (t && Object.prototype.hasOwnProperty.call(t, p)) {
                                t = t[p];
                            } else {
                                t = null; break;
                            }
                        }

                        let text = (typeof t === 'string') ? t : key; // fallback เป็น key ถ้าไม่เจอ

                        // แทนที่ :placeholder
                        for (const ph in replace) {
                            if (Object.prototype.hasOwnProperty.call(replace, ph)) {
                                text = text.replace(`:${ph}`, replace[ph]);
                            }
                        }
                        return text;
                    } catch (e) {
                        return key; // กันพังทุกกรณี
                    }
                },
                selectTab(key) {
                    console.log('select' + key);
                    // const el = document.querySelector('#gametab');
                    // if (el) {
                    //     el.scrollIntoView({behavior: 'smooth', block: 'start'});
                    // }
                    this.searchGame = '';
                    this.searchProvider = '';
                    this.selectedTab = key;
                    this.selectedProvider = null;
                    this.fetchProviderData(key); // เรียก API ใหม่ทุกครั้งเมื่อเปลี่ยน tab

                },
                selectTabScroll(key) {
                    console.log('select' + key);
                    const el = document.querySelector('#gametab');
                    if (el) {
                        el.scrollIntoView({behavior: 'smooth', block: 'start'});
                    }
                    this.searchGame = '';
                    this.searchProvider = '';
                    this.selectedTab = key;
                    this.selectedProvider = null;
                    this.fetchProviderData(key); // เรียก API ใหม่ทุกครั้งเมื่อเปลี่ยน tab

                },
                fetchProviderData(type) {
                    this.providerList = [];

                    const url = this.apiGetproviderTemplate
                        .replace('__TYPE__', type);
                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            this.providerList = data || []; // สมมติว่า API ยังคงส่งในรูปแบบ { type: [...] }
                        })
                        .catch(err => console.error('โหลด provider ล้มเหลว:', err));
                },
                loadGames(item) {
                    const el = document.querySelector('#gametab');
                    if (el) {
                        el.scrollIntoView({behavior: 'smooth', block: 'start'});
                    }
                    console.log('loadGame ' + item.provider);
                    this.selectedProvider = item.providerName;
                    this.searchGame = '';
                    this.providerGames = [];
                    this.customClassMap = {};
                    const url = this.apiGetgameTemplate
                        .replace('__TYPE__', item.providerType)
                        .replace('__PROVIDER__', item.provider);

                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            this.providerGames = data || []; // สมมติว่า API ยังคงส่งในรูปแบบ { type: [...] }
                        })
                        .catch(err => console.error('โหลด provider ล้มเหลว:', err));


                },
                enterGame(game) {
                    console.log('กำลังโหลดเกม:', game);

                    fetch(`/api/login-to-game/${game.id}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.url) {
                                const gameUrl = data.url;

                                if (this.isMobile()) {
                                    // 👉 บนมือถือ เปิดแท็บใหม่
                                    window.open(gameUrl, '_blank');
                                } else {
                                    // 👉 บน desktop เปิด popup window
                                    const width = 1024;
                                    const height = 720;
                                    const left = (screen.width / 2) - (width / 2);
                                    const top = (screen.height / 2) - (height / 2);
                                    window.open(
                                        gameUrl,
                                        'GameWindow',
                                        `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`
                                    );
                                }
                            } else {
                                alert('ไม่สามารถเข้าสู่เกมได้');
                            }
                        })
                        .catch(err => {
                            console.error('Login API ล้มเหลว:', err);
                            alert('เกิดข้อผิดพลาด');
                        });
                },
                savePlayedGame(item) {
                    const key = 'playedGames';
                    let list = JSON.parse(localStorage.getItem(key)) || [];

                    if (!list.some(g => g.id === item.id)) {
                        list.push({
                            id: item.id,
                            gameName: item.gameName,
                            image: item.image,
                            provider: item.provider,
                            gameCategory: item.gameCategory
                        });

                        if (list.length > 10) list = list.slice(-10); // เก็บล่าสุด 10 เกม

                        localStorage.setItem(key, JSON.stringify(list));
                    }
                },
                openGamePopup(game) {
                    // const url = `https://api.leo918.com/api//${game.id}`;
                    const url = this.apiGetloginTemplate
                        .replace('__TYPE__', game.gameType ?? game.providerType)
                        .replace('__PROVIDER__', game.provider)
                        .replace('__ID__', game.id ?? 'lobby');

                    this.savePlayedGame(game);

                    if (this.isMobile()) {
                        // มือถือ: เปิดในแท็บใหม่
                        window.open(url, '_blank');
                    } else {
                        // Desktop: เปิด popup ขนาดกำหนด
                        const width = 1024;
                        const height = 720;
                        const left = (screen.width / 2) - (width / 2);
                        const top = (screen.height / 2) - (height / 2);

                        window.open(
                            url,
                            'GamePopup',
                            `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`
                        );
                    }
                },
                isMobile() {
                    return /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                }
            },
            mounted() {
                this.customClassMap = {};
                if (this.categories.length) {
                    this.selectTab(this.categories[0].key);
                }
                this.$nextTick(() => this.setupGamesInfinite());
            },
            beforeDestroy() {
                if (this.ioGames) {
                    this.ioGames.disconnect();
                    this.ioGames = null;
                }
            },
            watch: {
                // เมื่อกรอง/เปลี่ยนค่าย/ค้นหา → รีเซ็ตหน้าใหม่
                filteredGames: {
                    handler(newVal) {
                        // เซ็ตหน้าแรกทันที (ถ้าไม่มีเกมก็เป็น 0)
                        const len = Array.isArray(newVal) ? newVal.length : 0;
                        this.pageEnd = Math.min(this.pageSize, len);
                        // รอ DOM วาง sentinel แล้วค่อยผูก observer
                        this.$nextTick(() => this.setupGamesInfinite());
                    },
                    immediate: true, // เรียกครั้งแรกด้วย
                },
            },
        });
    </script>
@endpush

