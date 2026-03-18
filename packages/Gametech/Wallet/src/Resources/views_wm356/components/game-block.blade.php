<script type="text/x-template" id="game-block-template">
	
	<div class="category-block" v-if="isShow">
		<div :class="`x-lotto-category x-provider-category -provider_${gameType}`">
			
			<div class="container-fluid">
				
				<div class="category-bar" data-animatable="fadeInUp" data-delay="150">
					
					
					<div class="category-title" v-text="getGameLabel(gameType)"></div>
				
				
				</div>
				
				
				<div class="-lotto-category-wrapper" data-animatable="fadeInUp" data-delay="150">
					<ul class="navbar-nav">
						
						<li class="nav-item -lotto-card-item" v-for="(item, index) in items" :key="index">
							<div
									class="x-game-list-item-macro-in-share js-game-list-toggle -big-with-countdown-dark -cannot-entry -untestable -use-promotion-alert"
									data-status="-cannot-entry -untestable">
								<div class="-inner-wrapper">
									
									
									<picture>
										<img loading="lazy"
										     alt="เกมค่ายดัง แตกง่าย"
										     class="img-fluid lazyload -cover-img"
										     width="400"
										     height="580"
										     :data-src="item.logoURL"
										     src="https://asset.cloudigame.co/build/admin/img/ezs-default-loading-big.png"
										/>
									</picture>
									
									<div class="-overlay">
										<div class="-overlay-inner">
											<div class="-wrapper-container">
												@auth
													<button @click.prevent="loadGames(item.provider)"
													   class="js-account-approve-aware -btn -btn-play"
													>
														<i class="fas fa-play"></i>
														<span
																class="-text-btn">{{ __('app.home.join') }}</span>
													</button>
												@endauth
												@guest
													<a href="#loginModal"
													   class="js-account-approve-aware -btn -btn-play"
													   data-toggle="modal" data-target="#loginModal">
														<i class="fas fa-play"></i>
														<span
																class="-text-btn">{{ __('app.home.join') }}</span>
													</a>
												@endguest
											</div>
										</div>
									</div>
								
								</div>
								<div class="-title" v-text="item.providerName"></div>
							</div>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>

</script>

@push('components')
	
	<script type="module">

        Vue.component('game-block', {
            template: '#game-block-template',
            props: {
                gameType: String

            },
            data() {
                return {
                    items: {},
                    isShow: false,
                    apiGetproviderTemplate: '{{ route('api.providers.public', ['type' => '__TYPE__']) }}',
                    apiGetgameTemplate: '{{ route('customer.game.list', ['id' => '__PROVIDER__']) }}',
                    apiGetloginTemplate: '{{ route('customer.game.redirect', ['method' => '__TYPE__', 'name' => '__PROVIDER__', 'id' => '__ID__']) }}'
                };
            },
            methods: {
                async loadData(gameType) {
                    const url = this.apiGetproviderTemplate.replace('__TYPE__', gameType);
                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            // this.items = data || []; // สมมติว่า API ยังคงส่งในรูปแบบ { type: [...] }


                            if (data && Array.isArray(data) && data.length > 0) {
                                this.items = data;
                                this.isShow = true;
                            } else {
                                this.items = [];
                                this.isShow = false;
                            }

                        })
                        .catch(err => console.error('โหลด provider ล้มเหลว:', err));
                },
                loadGames(provider) {
                    const url = this.apiGetgameTemplate.replace('__PROVIDER__', provider);
                    window.location.href = url;
                },
                openGamePopup(game) {
                    // const url = `https://api.leo918.com/api//${game.id}`;
                    const url = this.apiGetloginTemplate
                        .replace('__TYPE__', game.gameCategory)
                        .replace('__PROVIDER__', game.provider)
                        .replace('__ID__', game.id);

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
                this.loadData(this.gameType); // ✅ ต้องโหลดก่อน
            },
            computed: {
                getGameLabel() {
                    return key => window.translations[key] || key;
                },

            },

        });
	
	</script>
@endpush