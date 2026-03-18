<script type="text/x-template" id="recent-games-template">
	
	<div class="container-fluid position-relative member-menu-bg py-1 mt-3 mb-1 pb-2">
		<div class="fs-4 mt-3 text-start">{{ __('app.game.recent') }}</div>
		<div class="swiper-container" id="lastSlide">
			<div class="swiper-wrapper">
				<div class="latest_game_item swiper-slide"
				     v-for="game in playedGames"
				     :key="game.id"
				     style="width: 6em; margin-right: 15px;"
				     @click="openGamePopup(game)">
					<div>
						<img :src="game.image.vertical" class="w-100" :alt="game.gameName">
					</div>
				</div>
			</div>
		</div>
	</div>

</script>

@push('components')
	
	<script type="module">

        Vue.component('recent-games', {
            template: '#recent-games-template',
            props: {
                apiGetloginTemplate: String,
            },
            data() {
                return {};
            },
            computed: {
                playedGames() {
                    const key = 'playedGames';
                    const list = JSON.parse(localStorage.getItem(key)) || [];
                    return list.slice(-10).reverse(); // แสดงเกมล่าสุด 10 เกม
                }
            },
            methods: {
                openGamePopup(game) {
                    const url = this.apiGetloginTemplate
                        .replace('__TYPE__', game.gameCategory)
                        .replace('__PROVIDER__', game.provider)
                        .replace('__ID__', game.id);


                    if (this.isMobile()) {
                        window.open(url, '_blank');
                    } else {
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
                // ถ้าใช้ Swiper.js ต้อง init หลัง DOM ready
                this.$nextTick(() => {
                    if (window.Swiper) {
                        new Swiper('#lastSlide', {
                            slidesPerView: 'auto',
                            spaceBetween: 10,
                            freeMode: true,
                        });
                    }
                });
            }
        });
	
	</script>
@endpush

