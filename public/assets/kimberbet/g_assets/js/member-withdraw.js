var member_withdraw = {
    template: `<div class="sub-page min-h-100">
    <div class="container custom-max-width-container withdraw-container">
        <div id="withdrawPanel" class="swiper-container mt-3">
            <div id="withdrawPagination" class="btn-group btn-group-lg rounded"></div>
            <div class="swiper-wrapper">
                <div class="swiper-slide p-2 rounded bg-dark-2">
                    <div class="fs-6 text-content pt-2 w-100 text-center pt-4">จำนวนเงินที่ถอนได้ปัจจุบันคือ : <span class="fw-bolder text-custom-primary" v-html="intToMoney($root.credit)"></span> บาท </div>
                    <hr class="w-75 mx-auto my-1">
                    <div class="fs-6 text-content w-100 text-center">จำนวนสิทธ์การถอนคงเหลือ <span class="fw-bolder text-primary">{{ $root.withdraw.info.limit_cnt - $root.withdraw.info.today_cnt }}/{{$root.withdraw.info.limit_cnt}}</span> ครั้ง
                    (รีเซ็ตเวลา 00.00 ทุกวัน)</div>
                   
                    <div class="theme-form mt-4">
                        <div class="input-group input-group-lg mx-auto custom-style-input" style="max-width: 20em;">
                            <span class="input-group-text">&#3647;</span>
                            <input id="reg-tel" class="form-control" type="number" autocomplete="off" placeholder="จำนวนเงินถอน" 
                                v-model.number="$root.withdraw.fm.amount" 
                                :min="member_style.min_withdraw_true_wallet ? ($root.user.bank_id == 999 ? 100 : 10) : 10" max="99999" 
                                :step="member_style.min_withdraw_true_wallet ? ($root.user.bank_id == 999 ? 10 : 1) : 1" required>
                            
                        </div> 
                    </div> 
                    
                    <div class="text-center mt-3">
                        <button class="btn btn-primary btn-custom-primary w-100 rounded-pill" type="button" style="max-width: 20em;" :disabled="!$root.withdraw.info.withdrawable" @click="$root.withdrawSubmit()">ถอนเงิน</button>
                    </div>
                </div>
                <div class="swiper-slide p-2 rounded bg-dark-2" v-if="$root.user_return">
                    <div class="fs-6 text-content pt-2 pb-2 w-100 text-center pt-4 position-relative" >
                        เงินโบนัสยอดเสีย : <span class="fw-bolder text-custom-primary px-2" v-html="$root.user_return.available">loading...</span> บาท
                        <hr class="w-75 mx-auto my-1">
                        <small class="text-mute w-100">ขั้นต่ำในการถอนโบนัสยอดเสีย {{$root.user_return.withdraw_min}} บาท</small>
                       
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-custom-primary w-100 rounded-pill"
                             type="button" v-show="$root.user_return.in_range"
                             style="max-width: 20em;" 
                             :disabled="$root.user_return.available < $root.user_return.withdraw_min || $root.user_return.credit_limit < $root.credit" 
                             @click="withdrawReturnSubmit()">ถอนยอดเสีย</button>
                    </div>
                    <div class="withdraw-betloss mt-2">
                        <div class="small text-danger">* ท่านจะสามารถถอนคืนยอดเสียได้ เมื่อเครดิตเหลือต่ำกว่า: {{ $root.user_return.credit_limit }} บาท</div>   
                        <div class="small text-danger">* หากท่านทำรายการ "ถอน" โบนัสยอดเสียจะกลายเป็น 0</div>   
                    </div>
                    <div class="withdraw-betloss-detail w-100 mt-2">
                        <div class="withdraw-betloss-detail_title mb-2 fs-6 text-content pt-2" v-html="member_style.withdraw_betloss_title"></div>
                        <div class="withdraw-betloss-detail_content px-4 text-mute pb-2" v-html="member_style.withdraw_betloss_detail"></div>
                    </div>
                    <div style="opacity: 0">{{$root.user_return}}</div>
                </div>
            </div>
        </div>
        <div class="small text-danger mt-3 fw-light">* หากทำการถอนไม่ได้หรือติดปัญหาใดๆกรุณาติดต่อพนักงาน</div>
    </div>
</div>
`,
    data() {
        return {
            tabs:{},
            is_withdraw_betloss:false
        }
    },
    methods: {
        async submit() {
            this.$root.submitWithdraw();
        },
        async withdrawReturnSubmit() {
            let md = await modal.confirm('ยืนยันการถอนยอดเสีย?');
            if (!md) return;
            let res = await this.$root.easy.callApi('customer_req_withdraw');
            if (!res.success) return modal.error(res.data || res.code);
            modal.success(res.data || 'สำเร็จ');
        },
        setupTabs(wait = false){
            let $this = this;
            let t = 0;
            if(wait) t = 1000
            setTimeout(()=>{
                $this.tabs.slider = new Swiper("#withdrawPanel", {
                    spaceBetween: 20,
                    pagination: {
                        el: "#withdrawPagination",
                        clickable: true,
                        bulletClass: 'btn btn-line-secondary',
                        bulletElement: 'button',
                        renderBullet: function (index, className) {
                            return `<span class="${className} withdraw-selection-tab" style="width: 1.2em;">${ index == 0 ? ' ถอนเงิน':'ถอนยอดเสีย' }</span>`;
                        },
                    }
                });
                if($this.is_withdraw_betloss) $this.tabs.slider.slideTo(1); $this.is_withdraw_betloss = false;
            },t)
            
        }
    },
    computed: {},
    watch: {
        selected_ip(val) {
            this.cmd = '';
        },
        'filter.group'(val) {
            
        },
        '$root.user_return': function(value){
            if(value){
                this.setupTabs(1);
            }
        }
    },
    beforeRouteEnter(to, from, next) {
        next(async $this => {
            $this.$root.withdrawInfo();
            if(to.query.betloss) $this.is_withdraw_betloss = true;      
            next();
        })
    },
    beforeRouteLeave(to, from, next) {
        this.$destroy();
        this.$root.withdrawReset();
        next();
    },
    mounted() {
        this.setupTabs();
    },
};
