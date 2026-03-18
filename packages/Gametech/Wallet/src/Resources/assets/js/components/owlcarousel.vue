<template>

        <div :id="elementHandle" :class="['owl-carousel', 'owl-theme']">
            <div :class="['item']"  v-for="game in games" v-bind:key="game.code">
                <game :item="game" v-bind:key="game.code"></game>
            </div>
        </div>

</template>
<script>
import 'owl.carousel/dist/assets/owl.carousel.css';
import 'owl.carousel/dist/assets/owl.theme.default.css';
import 'owl.carousel';


import events from 'vue-owl-carousel2/src/utils/events';

export default {
    name: 'carousel',
    props: {
        games: {},
        items: {
            type: Number,
            default: 3,
        },
        margin: {
            type: Number,
            default: 0,
        },
        loop: {
            type: Boolean,
            default: false,
        },
        center: {
            type: Boolean,
            default: false,
        },
        mouseDrag: {
            type: Boolean,
            default: true,
        },
        touchDrag: {
            type: Boolean,
            default: true,
        },
        pullDrag: {
            type: Boolean,
            default: true,
        },
        freeDrag: {
            type: Boolean,
            default: false,
        },
        stagePadding: {
            type: Number,
            default: 0,
        },
        merge: {
            type: Boolean,
            default: false,
        },
        mergeFit: {
            type: Boolean,
            default: false,
        },
        autoWidth: {
            type: Boolean,
            default: false,
        },
        startPosition: {
            type: Number,
            default: 0,
        },
        uRLhashListener: {
            type: Boolean,
            default: false,
        },
        nav: {
            type: Boolean,
            default: true,
        },
        rewind: {
            type: Boolean,
            default: true,
        },
        navText: {
            type: Array,
            default: () => ['next', 'prev'],
        },
        navElement: {
            type: String,
            default: 'div',
        },
        slideBy: {
            type: [Number, String],
            default: 1,
        },
        slideTransition: {
            type: String,
            default: '',
        },
        dots: {
            type: Boolean,
            default: true,
        },
        dotsEach: {
            type: [Number, Boolean],
            default: false,
        },
        dotsData: {
            type: Boolean,
            default: false,
        },
        lazyLoad: {
            type: Boolean,
            default: false,
        },
        lazyLoadEager: {
            type: Number,
            default: 0,
        },
        autoplay: {
            type: Boolean,
            default: false,
        },
        autoplaySpeed: {
            type: Boolean,
            default: false,
        },
        autoplayTimeout: {
            type: Number,
            default: 5000,
        },
        autoplayHoverPause: {
            type: Boolean,
            default: false,
        },
        smartSpeed: {
            type: Number,
            default: 250,
        },
        fluidSpeed: {
            type: [Number, Boolean],
            default: false,
        },
        navSpeed: {
            type: [Number, Boolean],
            default: false,
        },
        dragEndSpeed: {
            type: [Number, Boolean],
            default: false,
        },
        callbacks: {
            type: Boolean,
            default: true,
        },
        responsive: {
            type: Object,
            default: () => {},
        },
        responsiveRefreshRate: {
            type: Number,
            default: 200,
        },
        responsiveBaseElement: {
            type: String,
            "default": "window"
        },
        video: {
            type: Boolean,
            default: false,
        },
        videoHeight: {
            type: [Number, Boolean],
            default: false,
        },
        videoWidth: {
            type: [Number, Boolean],
            default: false,
        },
        animateOut: {
            type: [String, Boolean],
            default: false,
        },
        animateIn: {
            type: [String, Boolean],
            default: false,
        },
        fallbackEasing: {
            type: String,
            default: 'swing',
        },
        info: {
            type: Function,
            default: () => {},
        },
        itemElement: {
            type: String,
            default: 'div',
        },
        stageElement: {
            type: String,
            default: 'div',
        },
        navContainer: {
            type: [String, Boolean],
            default: false,
        },
        dotsContainer: {
            type: [String, Boolean],
            default: false,
        },
        checkVisible: {
            type: Boolean,
            default: true,
        },
        slidesCount: {}
    },
    provide() {
        return {
            carousel: this
        };
    },
    data()
    {
        return {
            owl: {},
            showPrev: false,
            showNext: true,
            selectItem: null,
            prevHandler: 'carousel_prev_' + this.generateUniqueId(),
            elementHandle: 'carousel_' + this.generateUniqueId(),
            nextHandler: 'carousel_next_' + this.generateUniqueId(),
        };
    },

    mounted()
    {
        this.owl = $("#" + this.elementHandle);

        this.owl.on('initialize.owl.carousel', () => {
            this.$emit('initialize');
        });

        this.owl.on('initialized.owl.carousel', (event) => {
            this.$emit('initialized');
            var value = $(event.target).find('.owl-item').eq(event.item.index).children().children()[0].__vue__.$vnode.key;
            document.getElementById("game").value = value;
        });



        this.create();


        $('#' + this.prevHandler).click(function() {
            this.owl.trigger('prev.owl.carousel');
        });

        $('#' + this.nextHandler).click(function() {
            this.owl.trigger('next.owl.carousel');
        });


        events.forEach((eventName) => {
            this.owl.on(`${eventName}.owl.carousel`, (event) => {

                this.$emit(eventName, event);
            });
        });

        // this.$emit("clicked", this.selectTab);




        this.owl.on('translate.owl.carousel', (event) => {
            var value = $(event.target).find('.owl-item').eq(event.item.index).children().children()[0].__vue__.$vnode.key;
            document.getElementById("game").value = value;

        });

        this.owl.on('initialized.owl.carousel', (event) => {
            var value = $(event.target).find('.owl-item').eq(event.item.index).children().children()[0].__vue__.$vnode.key;
            document.getElementById("game").value = value;

        });

        this.owl.on("dragged.owl.carousel", function(event) {

            // console.log(e);
            // console.log('center item is:'+ (e.item.index + 1));
            // var carousel = $('.owl-carousel').data('owl.carousel');
            // event.preventDefault();
            // console.log(carousel);
            // console.log($(this));
            // console.log(event);
            //
            // carousel.to(carousel.relative($(this).index()));

        });

        this.owl.on('click','.owl-item', function (event){
            var carousel = $('.owl-carousel').data('owl.carousel');
            event.preventDefault();
            // console.log(carousel);
            // console.log($(this));
            // console.log(event);

            carousel.to(carousel.relative($(this).index()));

        });



        if (!this.loop) {
            this.owl.on('changed.owl.carousel', (event) => {

                if (event.item.index === 0) {
                    this.showPrev = false;
                    this.showNext = true;
                } else {
                    const currnetel = Math.floor(event.item.index + event.page.size);
                    // last
                    if (currnetel === event.item.count) {
                        this.showPrev = true;
                        this.showNext = false;
                    } else {
                        this.showPrev = true;
                        this.showNext = true;
                    }
                }
            });
        }else{
            this.owl.on('changed.owl.carousel', (event) => {
                // var carousel = $('.owl-carousel').data('owl.carousel');
                // event.preventDefault();
                // console.log(carousel);
                // console.log($(this));
                // console.log(event);
                //
                // carousel.to(carousel.relative($(this).index()));
                // var value = $(event.target).find('.owl-item').eq(event.item.index).children().children()[0].__vue__.$vnode.key;
                // document.getElementById("game").value = value;
            });
        }

        this.owl.on('translated.owl.carousel', (event) => {
            var value = $(event.target).find('.owl-item').eq(event.item.index).children().children()[0].__vue__.$vnode.key;
            document.getElementById("game").value = value;
        });
    },

    methods: {
        generateUniqueId()
        {
            return Math.random().toString(36).substring(2, 15);
        },

        refresh()
        {
            this.destroy();
            this.create();
        },

        destroy()
        {
            this.owl.trigger("destroy.owl.carousel");
        },

        create()
        {

            this.owl.owlCarousel({
                items: this.items,
                margin: this.margin,
                loop: this.loop,
                center: this.center,
                mouseDrag: this.mouseDrag,
                touchDrag: this.touchDrag,
                pullDrag: this.pullDrag,
                freeDrag: this.freeDrag,
                stagePadding: this.stagePadding,
                merge: this.merge,
                mergeFit: this.mergeFit,
                autoWidth: this.autoWidth,
                startPosition: this.startPosition,
                uRLhashListener: this.uRLhashListener,
                nav: this.nav,
                rewind: this.rewind,
                navText: this.navText,
                navElement: this.navElement,
                slideBy: this.slideBy,
                slideTransition: this.slideTransition,
                dots: this.dots,
                dotsEach: this.dotsEach,
                dotsData: this.dotsData,
                lazyLoad: this.lazyLoad,
                lazyLoadEager: this.lazyLoadEager,
                autoplay: this.autoplay,
                autoplaySpeed: this.autoplaySpeed,
                autoplayTimeout: this.autoplayTimeout,
                autoplayHoverPause: this.autoplayHoverPause,
                smartSpeed: this.smartSpeed,
                fluidSpeed: this.fluidSpeed,
                navSpeed: this.navSpeed,
                dragEndSpeed: this.dragEndSpeed,
                callbacks: this.callbacks,
                responsive: this.responsive,
                responsiveRefreshRate: this.responsiveRefreshRate,
                responsiveBaseElement: this.responsiveBaseElement,
                video: this.video,
                videoHeight: this.videoHeight,
                videoWidth: this.videoWidth,
                animateOut: this.animateOut,
                animateIn: this.animateIn,
                fallbackEasing: this.fallbackEasing,
                info: this.info,
                itemElement: this.itemElement,
                stageElement: this.stageElement,
                navContainer: this.navContainer,
                dotsContainer: this.dotsContainer,
                checkVisible: this.checkVisible,
            });

        },
        'getGamePromotion': function () {
            this.$http.post(`${this.$root.baseUrl}/member/transfer/game/check`, {'id':  this.item})
                .then(response => {
                    if (response.data.status) {
                        this.cartItems = response.data.mini_cart.cart_items;
                        this.cartInformation = response.data.mini_cart.cart_details;
                    }
                })
                .catch(exception => {
                    console.log(this.__('error.something_went_wrong'));
                });
        }


    },
};

</script>
