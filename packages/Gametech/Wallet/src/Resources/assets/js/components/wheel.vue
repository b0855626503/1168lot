<template>
    <div class="row">
        <audio hidden preload="none" id="tick" :src="`${this.$root.baseUrl}/storage/spin_img/tick.mp3`"
               type='audio/mp3'>

        </audio>
        <div class="mx-auto text-center">
            <div id="canvasContainer mt-5">
                <canvas height="390" id="spinwheel" width="340" data-responsiveMinWidth="180"
                        data-responsiveScaleHeight="true" data-responsiveMargin="0">
                    <p style="{color: white}" align="center">Sorry, your browser doesn't support canvas. Please try
                        another.</p>
                </canvas>
            </div>
        </div>
        <div class="mx-auto w-100 text-center">
            <p class="text-light m-0">ใช้เพชร 1 เม็ดในการร่วมสนุก (เพชรคงเหลือ {{ diamond }} เม็ด)</p>
            <button id="btnspin" class="btn btn-success m-1" @click="startSpin">หมุนวงล้อ</button>
        </div>
    </div>


</template>

<script>

export default {
    name: 'wheel',
    props: ['items', 'spincount'],

    provide() {
        return {
            wheel: this
        };
    },
    data: function () {
        return {
            winwheel: {},
            audio: {},
            wheelSpinning: false,
            playPromise: undefined,
            diamond: 0,
            format: {}

        }
    },
    mounted() {
        // this.audio = new Audio(`${this.$root.baseUrl}/storage/spin_img/tick.mp3`);
        this.create();
        this.diamond = this.spincount;
        document.getElementById('btnspin').disabled = this.diamond < 1;
        this.audio = document.getElementById('tick');

    },
    methods: {
        create() {
            this.winwheel = new Winwheel({
                'canvasId': 'spinwheel',
                'numSegments': 10,                 // Specify number of segments.
                'fillStyle': '#e7706f',
                'outerRadius': 190,               // Set outer radius so wheel fits inside the background.
                'lineWidth': 3,              // Code drawn text can be used with segment images.
                'drawText': false,              // Code drawn text can be used with segment images.
                'textFontSize': 16,
                'textOrientation': 'curved',
                'textAlignment': 'inner',
                'textMargin': 60,
                'textFontFamily': 'monospace',
                'textStrokeStyle': 'white',
                'textLineWidth': 3,
                'textFillStyle': 'white',
                'responsive': true,
                'drawMode': 'segmentImage',    // Must be segmentImage to draw wheel using one image per segemnt.
                'segments': this.items,
                'animation':           // Specify the animation to use.
                    {
                        'type': 'spinToStop',
                        'duration': 10,     // Duration in seconds.
                        'spins': 20,     // Number of complete spins.
                        'callbackSound': this.playSound,
                        'callbackFinished': this.alertPrize,
                        'soundTrigger': 'pin'
                    },
                'pins':
                    {
                        'margin': 20,
                        'number': 10,
                        'fillStyle': 'red',
                        'strokeStyle': 'white',
                        'outerRadius': 8,
                        'responsive': true, // This must be set to true if pin size is to be responsive.
                    },
            });
            this.winwheel.draw();
            // this.drawTriangle();
        },

        updateResult() {
            this.$http.post(`${this.$root.baseUrl}/member/reward`)
                .then(response => {
                    this.diamond = parseInt(response.data.diamond);

                    this.winwheel.animation.stopAngle = response.data.format.point;
                    this.winwheel.startAnimation();
                    this.format = response.data.format;

                })
                .catch(() => {
                    console.log('error');
                });
        },
        startSpin() {
            this.audio.pause();
            this.updateResult();
            if (this.diamond > 0) {
                if (this.wheelSpinning === false) {
                    // this.winwheel.animation.spins = 15;
                    this.winwheel.startAnimation();
                    this.wheelSpinning = true;
                }
            }
            document.getElementById('btnspin').classList.add('hidden');

        },
        resetWheel() {
            this.winwheel.stopAnimation(false);
            this.winwheel.rotationAngle = 0;

            this.wheelSpinning = false;
            document.getElementById('btnspin').disabled = this.diamond < 1;
            document.getElementById('btnspin').classList.remove('hidden');

        },
        alertPrize(event) {
            Swal.fire({
                title: this.format.title,
                text: this.format.msg,
                imageUrl: this.format.img,
                imageWidth: 150,
                imageHeight: 150,
                imageAlt: this.format.title,
            })
            this.resetWheel();
        },
        playSound() {

            // var isPlaying = this.audio.currentTime > 0 && !this.audio.paused && !this.audio.ended
            //     && this.audio.readyState > this.audio.HAVE_CURRENT_DATA;
            // console.log(isPlaying);
            // if (!isPlaying) {
            //     this.audio.play();
            // }
            this.audio.currentTime = 0;
            this.audio.play();


            // // var playPromise = audio.play();
            // let playPromise = this.audio.play();
            // console.log(playPromise);
            // if (playPromise !== undefined) {
            //     playPromise.then(_ => {
            //          this.audio.pause();
            //          this.audio.currentTime = 0;
            //         playPromise = this.audio.play();
            //         console.log('then');
            //         console.log(playPromise);
            //     })
            // }
            //
            // return this.audio.play();


            // this.audio = document.getElementById('tick');
            // let playPromise = this.audio.play();
            // console.log('play');
            // if (playPromise !== undefined) {
            //     playPromise.then(_ => {
            //         this.audio.pause();
            //         this.audio.currentTime = 0;
            //         console.log('pause');
            //     }).catch(error => {
            //
            //     });
            //
            // }


        },
        drawTriangle() {

            let ctx = this.winwheel.ctx;

            ctx.strokeStyle = 'white';  // Set line colour.
            ctx.fillStyle = 'aqua';  // Set fill colour.
            ctx.lineWidth = 2;
            ctx.beginPath();           // Begin path.
            ctx.moveTo(170, 5);        // Move to initial position.
            ctx.lineTo(230, 5);        // Draw lines to make the shape.
            ctx.lineTo(200, 40);
            ctx.lineTo(171, 5);
            ctx.stroke();              // Complete the path by stroking (draw lines).
            ctx.fill();                // Then fill.
        }


    }
};
</script>

