@extends('wallet::layouts.wheel')

{{-- page title --}}
@section('title','')

@push('styles')
    <style>
        .game {
            position: relative;
            height: 70vh;
            width: 100%;
            background-image: url('https://user.168csn.com/storage/event/busketgamebg.webp'); /* 👈 ใส่ภาพพื้นหลัง */
            background-size: cover;
            background-position: center;
            overflow: hidden;
        }

        .bucket {
            position: absolute;
            width: 64px;
            height: 64px;
            cursor: pointer;
            animation: fall 5s linear forwards;
        }

        .bucket img {
            width: 100%;
            height: auto;
        }

        @media (max-width: 768px) {
            .bucket {
                width: 60px;
                height: 60px;
            }
        }

        @media (min-width: 769px) {
            .bucket {
                width: 80px;
                height: 80px;
            }
        }

        .reward-box {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 22px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }

        .credit-box {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.8); /* พื้นหลังดำโปร่งใส */
            text-align: center;
            padding: 10px;
            box-sizing: border-box;
            z-index: 20;
        }

        .credit-box p {
            margin: 0;
            color: red;
            font-weight: bold;
            border: 2px solid black;
            display: inline-block;
            padding: 8px 16px;
            background: white;
            border-radius: 8px;
        }

        .splash {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none; /* ไม่บังการคลิก */
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .splash-img {
            width: 80vw;
            max-width: 400px;
            animation: splash-pop 0.6s ease-out;
        }

        @keyframes splash-pop {
            0% {
                opacity: 0;
                transform: scale(0.5) rotate(-15deg);
            }
            50% {
                opacity: 1;
                transform: scale(1.1) rotate(3deg);
            }
            100% {
                opacity: 0;
                transform: scale(1) rotate(0deg);
            }
        }

        .droplet {
            position: absolute;
            width: 12px;
            height: 12px;
            background: #00ccff;
            border-radius: 50%;
            opacity: 0;
            animation: drop-pop 0.5s ease-out forwards;
        }

        @keyframes drop-pop {
            0% {
                transform: scale(0);
                opacity: 1;
            }
            100% {
                transform: scale(1.2) translateY(-80px);
                opacity: 0;
            }
        }

        /* กำหนดทิศทางหยดแต่ละอัน */
        .d1 {
            top: 50%;
            left: 50%;
            animation-delay: 0s;
            transform: translate(-60px, -20px);
        }

        .d2 {
            top: 50%;
            left: 50%;
            animation-delay: 0.05s;
            transform: translate(60px, -20px);
        }

        .d3 {
            top: 50%;
            left: 50%;
            animation-delay: 0.1s;
            transform: translate(-40px, -50px);
        }

        .d4 {
            top: 50%;
            left: 50%;
            animation-delay: 0.15s;
            transform: translate(40px, -50px);
        }

        .d5 {
            top: 50%;
            left: 50%;
            animation-delay: 0.2s;
            transform: translate(-20px, -70px);
        }

        .d6 {
            top: 50%;
            left: 50%;
            animation-delay: 0.25s;
            transform: translate(20px, -70px);
        }

        .d7 {
            top: 50%;
            left: 50%;
            animation-delay: 0.3s;
            transform: translate(0px, -90px);
        }

        .d8 {
            top: 50%;
            left: 50%;
            animation-delay: 0.35s;
            transform: translate(0px, -60px);
        }

        .small-swal {
            width: 280px !important;
            padding: 1.5em !important;
            font-size: 14px;
        }

    </style>
@endpush


@section('content')
    <div id="main__content" data-bgset="/assets/wm356/images/index-bg.jpg?v=2"
         class="lazyload x-bg-position-center x-bg-index lazyload"
         style="background-image: url(&quot;/assets/wm356/images/index-bg.jpg?v=2&quot;);">


        <div class="x-index-content-main-container -logged">

            <div class="x-category-total-game -v2">
                <div class="container-fluid">

                    <div class="ctpersonal" id="app">
                        <busket-game></busket-game>
                    </div>
                </div>
            </div>

            <div class="x-provider-category -provider_casinos">
                <div class="container-fluid">

                    <div class="x-account-coupon">
                        <div data-animatable="fadeInModal" class="-coupon-container animated fadeInModal">
                            <a href="javascript:void(0)" onclick="openPopup('BONUS','โบนัส')">
                                <div class="-coupon-member-detail mb-3 mt-3">
                                    <div class="-coupon-box d-flex">
                                        <img alt="คูปอง เว็บไซต์พนันออนไลน์ คาสิโนออนไลน์"
                                             class="img-fluid -ic-coupon m-auto"
                                             src="\assets\wm356\web\ezl-wm-356\img\ic-menu-promotion.png"/>
                                    </div>
                                    <div class="-text-box-container">
                                        <div class="-title-member">{{ __('app.home.sum_bonus') }}</div>
                                        <div class="-text-member">{{ $userdata->bonus }}</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </div>

@endsection

@push('scripts')
    <script type="text/x-template" id="busket-template">
        <div class="game">
            <div class="row">
                <audio ref="splashSound" preload="auto"
                       :src="`https://user.168csn.com/storage/sound/splash.mp3`"></audio>
                <div class="mx-auto text-center">
                    <div id="canvasContainer mt-5">
                        <div
                                v-for="bucket in buckets"
                                :key="bucket.id"
                                class="bucket"
                                :style="{ left: bucket.left + '%', top: bucket.top + 'px' }"
                                @click="selectBucket(bucket.id)"
                        >
                            <img src="https://user.168csn.com/storage/event/busket.webp"/>
                        </div>
                        <div v-if="showSplash" class="splash">
                            <img :src="splashSrc" class="splash-img"/>
                            <div class="droplet" v-for="n in 8" :key="n" :class="'d' + n"></div>
                        </div>
                    </div>
                </div>
                <div class="credit-box">
                    <p>ใช้เพชร 1 เม็ดในการร่วมสนุก (เพชรคงเหลือ <span v-text="diamond"></span> เม็ด)</p>
                    <p>กดที่ขันที่ตกลงมา เพื่อลุ้นรางวัล</p>
                </div>

            </div>
        </div>

    </script>

    <script>

        Vue.component('busket-game', {
            'template': '#busket-template',

            data() {
                return {
                    diamond: 0,
                    buckets: [],
                    format: [],
                    rewardMessage: '',
                    splashImages: [
                        'https://user.168csn.com/storage/event/splash_1.png',
                        'https://user.168csn.com/storage/event/splash_2.png',
                        'https://user.168csn.com/storage/event/splash_3.png',
                        'https://user.168csn.com/storage/event/splash_4.png',
                        'https://user.168csn.com/storage/event/splash_5.webp',
                    ],
                    splashSrc: '',
                    showSplash: false,
                    bucketInterval: null,
                    fallInterval: null,
                }
            },
            mounted() {
                this.loadCredits()
                this.startFalling()
                this.spawnBucket()
                document.addEventListener('visibilitychange', this.handleTabVisibility);
            },
            beforeDestroy() {
                document.removeEventListener('visibilitychange', this.handleTabVisibility);
                clearInterval(this.bucketInterval);
                clearInterval(this.fallInterval);
            },
            methods: {
                handleTabVisibility() {
                    if (document.hidden) {
                        // พับหน้าจอ: หยุด interval
                        clearInterval(this.bucketInterval);
                        clearInterval(this.fallInterval);
                    } else {
                        // กลับมา: เริ่มใหม่
                        this.startFalling();
                        this.spawnBucket();
                    }
                },
                async loadCredits() {
                    const res = await axios.get("{{ route('customer.home.credit') }}")
                    this.diamond = res.data.profile.diamond;
                },
                spawnBucket() {
                    this.bucketInterval = setInterval(() => {
                        if (this.diamond > 0) {
                            this.buckets.push({
                                id: Date.now(),
                                left: Math.random() * 80 + 5,
                                top: -100
                            });
                        }
                    }, 1500);
                },
                startFalling() {
                    this.fallInterval = setInterval(() => {
                        this.buckets.forEach(b => (b.top += 3));
                        this.buckets = this.buckets.filter(b => b.top < window.innerHeight);
                    }, 30);
                },
                async selectBucket(id) {
                    if (this.diamond <= 0) {
                        alert('คุณไม่มีสิทธิ์เล่นแล้ว')
                        return
                    }

                    try {
                        const res = await axios.post("{{ route('customer.spin.store') }}")
                        this.format = res.data.format
                        this.diamond = parseInt(res.data.diamond)
                        $('#diamond_amount').text(this.diamond);
                        this.splashSrc = this.splashImages[Math.floor(Math.random() * this.splashImages.length)];
                        this.showSplash = true;

// เล่นเสียง splash
                        this.$refs.splashSound.currentTime = 0;
                        this.$refs.splashSound.play();

                        setTimeout(() => {
                            this.showSplash = false;

                            Swal.fire({
                                title: this.format.title,
                                text: this.format.msg,
                                imageUrl: this.format.img,
                                imageWidth: 150,
                                imageHeight: 150,
                                imageAlt: this.format.title,
                                customClass: {
                                    popup: 'small-swal', // 👈 class ที่จะไปกำหนดใน CSS
                                }
                            })
                        }, 2000);


                    } catch (err) {

                        Swal.fire({
                            title: 'เกิดข้อผิดพลาด',
                            text: 'ขออภัยในความไม่สะดวก',
                        })

                    }

                    setTimeout(() => (this.rewardMessage = ''), 2000)
                    this.buckets = this.buckets.filter(b => b.id !== id)
                }
            }
        })

    </script>
@endpush





