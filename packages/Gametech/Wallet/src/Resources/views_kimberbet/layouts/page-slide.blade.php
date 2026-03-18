<script type="text/x-template" id="page-slide-template">

    <div class="container-fluid position-relative member-menu-bg py-1 mt-3 mb-1 pb-2" v-show="isShow">
        <div class="swiper-container" id="pageSlide">
            <div class="swiper-wrapper">
                <div class="swiper-slide" v-for="(item, index) in items" :key="index">
                    <img :src="item.image">
                </div>
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-pagination"></div>
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

                        // ✅ รอ DOM render ก่อน update swiper
                        this.$nextTick(() => {
                            if (this.swiper) {
                                this.swiper.update();
                            }
                        });
                    } else {
                        this.isShow = false;
                    }
                },
                create() {
                    this.swiper = new Swiper('#pageSlide', {
                        slidesPerView: 'auto',
                        spaceBetween: 10,
                        pagination: {
                            el: ".swiper-pagination",
                            clickable: true,
                        },
                        navigation: {
                            nextEl: ".swiper-button-next",
                            prevEl: ".swiper-button-prev",
                        },
                    });
                }
            },

            mounted() {
                this.$nextTick(() => {
                    this.create();
                })
                this.loadData();
            }

        });


    </script>
@endpush

