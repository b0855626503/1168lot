var member_game_sub={template:`<div class="sub-page sub-footer" style="min-height:100vh;">
    <div class="container my-3 position-relative">
        <h3 class="text-center txt-game-recommend">เกมแนะนำ</h3>
        <button type="button" style="background: #7d7d7d;" v-if="game.vendor" class="btn-back-to-provider-select shadow btn-custom nav-link btn btn-sm btn-dark rounded-pill d-flex align-items-center pt-1 pb-1 text-white justify-content-center" @click="$router.go(-1)">
            <i class="bi bi-arrow-left me-2 nav-icon"></i>
            <span>ค่ายเกมส์</span>
        </button>
        <div id="suggestSlider" class="swiper-container">
            <div class="swiper-wrapper">
                <div class="swiper-slide" v-for="i in recList" @click="$root.play(i)">
                    <img class="w-100" :src="i.logo_src" style="max-height: 12em; object-fit: contain" />
                </div>
            </div>
            <div class="swiper-pagination"></div>
        </div>
        <hr>
        <div v-if="display.lotto && display.lotto.thai" class="display-lotto-container">
            <div class="display-thai-lotto">
                <div class="display-thai-lotto__title">
                    <span class="title--text">ผลการออกสลากกินแบ่งรัฐบาล</span> 
                    <span class="title--date">{{ display.lotto.thai.date_show }}</span>
                </div>
                <div class="display-thai-lotto__content">
                    <div class="content--left">

                        <div class="reward-first">
                            <span class="reward-title">
                                <span class="top">รางวัลที่</span>
                                <span class="bottom">1</span>
                            </span>
                            <span class="reward-number">{{ display.lotto.thai.first }}</span>
                        </div>
                        <div class="reward-three_front_back">
                            <div class="reward-front">
                                <span class="front-title">เลขหน้า 3 ตัว</span>
                                <span class="front-number">{{ display.lotto.thai.three_front[0] }}&nbsp;&nbsp;{{ display.lotto.thai.three_front[1] }}</span>
                            </div>
                            <div class="reward-back">
                                <span class="back-title">เลขท้าย 3 ตัว</span>
                                <span class="back-number">{{ display.lotto.thai.three_back[0] }}&nbsp;&nbsp;{{ display.lotto.thai.three_back[1] }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="content--right reward-two_back">
                       
                        <div class="reward-two_back-title">
                            <span>เลขท้าย</span>
                            <span>2 ตัว</span>
                        </div>
                        <span class="reward-two_back-number">
                            {{ display.lotto.thai.two_back }}
                        </span>
                    </div>
                </div>
            </div>
            <!--
            <div class="display-lotto-countdown" v-if="display.lotto.end_ts">
                <div class="countdown--head">เวลาออกผลรอบต่อไป</div>
                <div class="countdown--body">
                    <div class="countdown-days">
                        <span class="countdown-lotto-title">วัน</span>
                        <span class="countdown-lotto-amount" >{{ display.lotto.end_ts.days }}</span>
                    </div>
                    <div class="countdown-hours">
                        <span class="countdown-lotto-title">ชั่วโมง</span>
                        <span class="countdown-lotto-amount">{{ display.lotto.end_ts.hours }}</span>
                    </div>
                    <div class="countdown-minutes">
                        <span class="countdown-lotto-title">นาที</span>
                        <span class="countdown-lotto-amount">{{ display.lotto.end_ts.minutes }}</span>
                    </div>
                    <div class="countdown-seconds">
                        <span class="countdown-lotto-title">วินาที</span>
                        <span class="countdown-lotto-amount">{{ display.lotto.end_ts.seconds }}</span>
                    </div>
                </div>
            </div>
            -->
        </div>

        <div class="row g-2">
            <div class="game-item col-6 col-md-3 col-lg-2" v-for="i in list.data" style="cursor: pointer;" :class="i.id ? 'game-list' : 'game-type'">
                
                <!-- no id = game type -->
                <router-link :to="'/games/'+game.type+'/'+i.code" tag="a" class="btn btn-img" v-if="!i.id">
                    <div class="game-preview">
                        <div class="preview-head text-content" v-html="i.title"></div>
                        <img :src="i.logo_src">
                    </div>
                </router-link>
                
                <div class="card w-100 position-relative" style="" @click="$root.play(i)" :class="{'animate__animated animate__bounce animate__infinite': i.perc==100,'animate__bounce_remove': !$root.slot_formular }" v-else>
                    <div class="card-header text-center py-0" style="text-overflow: ellipsis;white-space: nowrap;overflow: hidden;">
                        <span class="card-title m-0 text-content " v-html="i.title" style="text-overflow: ellipsis;white-space: nowrap;overflow: hidden;"></span>
                    </div>

                    <div v-if="i.lotto">
                    <div class="display-lotto-countdown" v-if="i.lotto.end_ts">
                        <div class="countdown--head">ออกผลรอบต่อไป</div>
                        <div class="countdown--body">
                            <div class="countdown-days" v-if="i.lotto.end_ts.day">
                                <span class="countdown-lotto-title">วัน</span>
                                <span class="countdown-lotto-amount" >{{ i.lotto.end_ts.day }}</span>
                            </div>
                            <div class="countdown-hours">
                                <span class="countdown-lotto-title">ชั่วโมง</span>
                                <span class="countdown-lotto-amount">{{ i.lotto.end_ts.hour }}</span>
                            </div>
                            <div class="countdown-minutes">
                                <span class="countdown-lotto-title">นาที</span>
                                <span class="countdown-lotto-amount">{{ i.lotto.end_ts.minute }}</span>
                            </div>
                            <div class="countdown-seconds">
                                <span class="countdown-lotto-title">วินาที</span>
                                <span class="countdown-lotto-amount">{{ i.lotto.end_ts.second }}</span>
                            </div>
                        </div>
                    </div>
                    </div>
                    
                    <img :src="i.logo_src" class="card-img" alt="...">
                    <div class="progress" v-if="typeof i.perc!=='undefined'"  :class="{ 'slot-formular' : !$root.slot_formular }">
                        <div v-if="$root.slot_formular" class="progress-bar progress-bar-striped progress-bar-animated" :class="{'bg-success': i.perc==100, 'bg-danger': i.perc < 30, 'bg-warning': i.perc > 30 && i.perc < 70}" role="progressbar" :style="{'width': i.perc+'%'}" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">{{i.perc}}%</div>
                    </div>
                    <ul class="list-group list-group-flush" v-if="show_play_button">
                        <li class="list-group-item d-flex justify-content-center"><button class="btn btn-custom-primary"><i class="bi bi-controller"></i> เล่น</button></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
`,data(){return{game:{type:null,vendor:null},list:{data:[]},recList:[],obj:{},show_play_button:true,_int:null,display:{},slot_formular:false}},methods:{async load(){this.game.type=this.$route.params.type;this.game.vendor=this.$route.params.vendor;if(this.$route.params.type==='slot_formular'){this.game.type='slot';this.slot_formular=true;this.$root.slot_formular=true;}
this.show_play_button=!_.includes(['baccarat'],this.game.vendor);let $this=this;let lst;if(this.game.type==='hits'){lst=await this.$root.easy.callApi('game_top');console.log(lst)}else{lst=await this.$root.easy.callApi('game_list',this.game);}
if(!lst.success){modal.error('ไม่สามารถเข้าเล่นเกมได้ในขณะนี้ เกมอาจปิดปรับปรุงชั่วคราว!','ไม่สามารถเข้าเล่นเกม');this.$router.replace({name:'login'});return false;}
this.display={};this.list=lst;if(this.game.type==='lotto'){this.display.lotto={thai:null,};let thai_gov=_.find(lst.data,o=>o['lotto']&&o['lotto']['thai_gov']);if(thai_gov){this.display.lotto.thai=thai_gov['lotto']['thai_gov'];this.display.lotto.thai.date_show=humantime.full_th(thai_gov['lotto']['prev_round'],true);}}
let rec=await this.$root.easy.callApi('game_recommend');if(!rec.success)return alert('Get2 Error '+rec.data);this.recList=_.orderBy(rec.data,'idx');Vue.nextTick(()=>{$this.obj.recommend=new Swiper("#suggestSlider",{effect:"coverflow",grabCursor:true,centeredSlides:true,loop:true,slidesPerView:3,coverflowEffect:{rotate:50,stretch:0,depth:100,modifier:1,slideShadows:true,},pagination:{el:".swiper-pagination",},breakpoints:{992:{slidesPerView:4,coverflowEffect:{rotate:30,}},},});});},},computed:{},watch:{selected_ip(val){this.cmd='';},'filter.group'(val){console.log('chchc',val);},$route(to,from){this.load();},},beforeRouteEnter(to,from,next){next(async $this=>{$this.load();$this._int=setInterval(()=>{let d=new Date();if(d.getMinutes()%10===0&&d.getSeconds()===0){setTimeout(()=>{$this.load();},4000);}
for(let o of $this.list.data){if(!o.lotto)continue;$this.$set(o.lotto,'end_ts',humantime.getTimeRemaining(new Date(o.lotto.end_timestamp)));}},1000);next();})},beforeRouteLeave(to,from,next){console.log('leave',from);if(!this.slot_formular)this.$root.slot_formular=false;clearInterval(this._int);this.$destroy();next();},mounted(){let $this=this;},created(){let $this=this;console.log('Loaded Game');Vue.nextTick(()=>{$this.obj.swiper=new Swiper("#gameSlide",{autoplay:{delay:3500,disableOnInteraction:false,},navigation:{nextEl:".swiper-button-next",prevEl:".swiper-button-prev",},});});},};var member_game_sub_list={mixins:[member_game_sub]};