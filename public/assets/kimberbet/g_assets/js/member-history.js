var member_history = {
    template: `<div class="sub-page sub-footer" style="min-height: 100vh;">
    <div class="container pt-3 px-0 history-container" style="max-width: 720px;">
        
        <div class="card bg-transparent">
            <div class="card-body container">
                <div id="historySlide" class="swiper-container">
                    <div id="history-side" class="btn-group btn-group-lg mb-2 rounded"></div>
                    
                    <div class="swiper-wrapper">
                        <div class="swiper-slide p-2 rounded">
                            <h3 class="text-success fs-5">รายการ ฝาก ล่าสุด</h3>
                            
                            <div v-if="deposit.data.length" style="min-height: 25em;">
                                <div v-for="i in deposit.data" class="card bg-dark mb-1">
                                    <div class="card-body d-flex justify-content-between" style="padding: 6px 1em;">
                                        <div>
                                            <div>{{i.is_bonus ? 'ได้รับโบนัส':'ฝากเข้า'}} <span :class="status[i.status].cls">[ {{status[i.status].name}} ]</span></div>
                                            <div><i class="bi bi-clock"></i> <span class="fw-light">{{i.time}}</span> <small>({{i.time_ago}})</small></div>
                                        </div>
                                        <div class="text-success d-flex align-items-center">
                                            <div class="fs-4" v-html="'+ ฿ '+intToMoney(i.amount)"></div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                            <div v-else class="card bg-dark" style="min-height: 25em;">
                                <div class="card-body text-center">
                                    <em>ไม่มีรายการ</em>
                                </div>
                            </div>
                            <zpagenav class="mt-auto" :page="deposit.page" :page-size="deposit.length" :total="deposit.totalRecords" :max-link="5" :page-handler="depList"><zpagenav>
                        </div>
                        <div class="swiper-slide p-2 rounded">
                            <h3 class="text-danger">รายการ ถอน ล่าสุด</h3>
                            
                            <div v-if="withdraw.data.length" style="min-height: 25em;">
                                <div v-for="i in withdraw.data" class="card bg-dark mb-1">
                                    <div class="card-body d-flex justify-content-between" style="padding: 6px 1em;">
                                        <div>
                                            <div>{{i.is_bonus ? 'ได้รับโบนัส':'ถอนเงิน'}} <span :class="status[i.status].cls">[ {{status[i.status].name}} ]</span></div>
                                            <div><i class="bi bi-clock"></i> <span class="fw-light">{{i.created_at}}</span> <small>({{i.created_ago}})</small></div>
                                        </div>
                                        <div class="text-danger d-flex align-items-center">
                                            <div class="fs-4" v-html="'- ฿ '+intToMoney(i.amount)"></div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                            <div v-else class="card bg-dark" style="min-height: 25em;">
                                <div class="card-body text-center">
                                    <em>ไม่มีรายการ</em>
                                </div>
                            </div>
                            <zpagenav class="mt-auto" :page="withdraw.page" :page-size="withdraw.length" :total="withdraw.totalRecords" :max-link="5" :page-handler="witList"><zpagenav>
                        </div>
                    </div>
                </div>
                
                <em class="small fw-light text-muted">หมายเหตุ: รายการมีอายุ 7 วัน</em>
                
            </div>
        </div>
    </div>
    
</div>
`,
    data() {
        return {
            deposit: {page: 1, length: 5, data: []},
            withdraw: {page: 1, length: 5, data: []},
            obj: {slide: null},
            status: {
                P: {name: 'รอตรวจสอบ', cls: 'text-warning'},
                W: {name: 'รอเข้าระบบ', cls: 'text-warning'},
                OK: {name: 'อนุมัติแล้ว', cls: 'text-success'},
                RJ: {name: 'ปฏิเสธ', cls: 'text-secondary'},
                C: {name: 'ยกเลิก', cls: 'text-secondary'},
                ER: {name: 'ผิดพลาด', cls: 'text-danger'},
                WT: {name: 'รอตรวจสถานะ', cls: 'text-danger'},
            },
        }
    },
    methods: {
        async depList(page = 1) {
            let r = await this.$root.easy.callApi('deposit_history', {page, length: this.deposit.length});
            if (!r.success) return;
            r.data = r.data.map(o=>{
                o.created_ago = moment(o.created_at).fromNow();
                o.time_ago = moment(o.time).fromNow();
                return o;
            });
            this.deposit = r;

            console.log('ttttt', r);
        },
        async witList(page = 1) {
            let r = await this.$root.easy.callApi('withdraw_history', {page, length: this.withdraw.length});
            console.log('withdraw_list', r);
            if (!r.success) return;
            r.data = r.data.map(o=>{
                o.created_ago = moment(o.created_at).fromNow();
                return o;
            });
            this.withdraw = r;
            
        },
        goPage(page) {

        },
    },
    computed: {

    },
    watch: {
        selected_ip(val) {
            this.cmd = '';
        },
        'filter.group'(val) {
            console.log('chchc', val);
        }
    },
    beforeRouteEnter (to, from, next) {
        Vue.nextTick(function() {
            next(async $this => {
                $this.depList();
                $this.witList();
                next();
            })
        });
    },
    beforeRouteLeave (to, from, next) {
        // this.$destroy();
        next();
    },
    mounted() {
        let $this = this;
        $this.obj.slider = new Swiper("#historySlide", {
            spaceBetween: 20,
            pagination: {
                el: "#history-side",
                clickable: true,
                bulletClass: 'btn btn-line-secondary',
                bulletElement: 'button',
                renderBullet: function (index, className) {
                    return `<span class="${className} text-secondary" style="width: 1.2em;">${index==0?' ฝาก':'ถอน'}</span>`;
                    //bi bi-layer-forward
                   // return `<span class="${className}"><img src="/assets/img/icon/${index==0?'deposit':'withdraw'}-black.svg" style="width: 1.2em;"> ${index==0?' ฝาก':'ถอน'}</span>`;
                },
            },
        });
    },
};
