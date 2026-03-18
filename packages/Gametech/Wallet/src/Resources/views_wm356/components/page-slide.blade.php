<script type="text/x-template" id="page-slide-template">
	
	<div class="js-replace-cover-seo-container" v-if="isShow">
		<div class="x-homepage-banner-container">
			<div
					data-slickable='{"arrows":false,"dots":true,"slidesToShow":1,"centerMode":true,"infinite":true,"autoplay":true,"autoplaySpeed":4000,"pauseOnHover":false,"focusOnSelect":true,"variableWidth":true,"responsive":{"sm":{"fade":true,"variableWidth":false}}}'
					class="x-banner-slide-wrapper -single" data-animatable="fadeInUp" data-delay="150"
					ref="mySlider"
			>
				<div class="-slide-inner-wrapper -slick-item" v-for="(item, index) in items" :key="index">
					<div class="-link-wrapper">
						<img  class="img-fluid -slick-item -item-" alt="banner"
						      width="1200"
						      height="590"
						      :src="item.image"/>
					</div>
				</div>
			
			</div>
		</div>
	</div>

</script>

@push('components')
	
	<script type="module">

        Vue.component('page-slide', {
            template: '#page-slide-template',
            data() {
                return {
                    items: {},
                    isShow: false,
                    swiper: null
                };
            },
            // created() {
            //     this.create();
            // },
            methods: {
                async loadData() {
                    const res = await axios.get("{{ route('customer.slide.load') }}");
                    if (res.data.success) {
                        this.items = res.data.data;
                        this.isShow = true;

                        this.$nextTick(() => {
                            this.waitImagesAndInitSlick();
                         
                        });
                    } else {
                        this.isShow = false;
                    }
                },

                waitImagesAndInitSlick() {
                    const el = this.$refs.mySlider;
                    if (!el) return;

                    const images = el.querySelectorAll('img');

                    const promises = Array.from(images).map(img => {
                        return new Promise(resolve => {
                            if (img.complete) return resolve();
                            img.onload = img.onerror = resolve;
                        });
                    });

                    Promise.all(promises).then(() => {
                        this.create();
                    });
                },

                create() {
                    if (this.swiper) return;

                    const el = this.$refs.mySlider;
                    if (!el) return;

                    this.swiper = $(el).slick({
                        slidesToShow: 1,
                        slidesToScroll: 1,
                        autoplay: true,
                        autoplaySpeed: 2000,
                        dots: true,
                        arrows: false,
                        centerMode: true,
                        infinite: true,
                        variableWidth: true,
                        responsive: [
                            {
                                breakpoint: 768,
                                settings: {
                                    fade: true,
                                    variableWidth: false
                                }
                            }
                        ]
                    });
                },
                animateVisibleSlides() {
                    const el = this.$refs.mySlider;
                    console.log(el);
                    if (!el) return;
                    const $visible = $(el).find('[data-animatable]');

                    console.log($visible);
                    const animation = $visible.dataset.animatable;
                    $visible.classList.add('animate__animated', `animate__${animation}`);
                    // $visible.forEach(el => {
                    //     const animation = el.dataset.animatable;
                    //     console.log(animation);
                    //     el.classList.add('animate__animated', `animate__${animation}`);
                    // });
                }

            },

            mounted() {
                this.loadData(); // ✅ ต้องโหลดก่อน
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.create(); // ❗โหลดข้อมูลเสร็จก่อนจึง init slick
                    }, 100); // หรือรอ this.items render

                  
                });
            },
            destroyed() {
                // cleanup slick ก่อน component ถูกถอดออก
                if (this.swiper) {
                    $(this.$el).slick('unslick');
                    this.swiper = null;
                }
            }
            ,

        });
	
	</script>
@endpush