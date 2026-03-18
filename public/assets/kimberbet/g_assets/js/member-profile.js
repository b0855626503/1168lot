var member_profile={template:`<div class="sub-page sub-footer" :class="[$route.name]">
    <div class="container profile-container">
        <div class="card mt-2 bg-transparent" style="width: 100%;">
            <div class="card-body p-2">
                <div class="credit-box" style="max-width: 32em;">
                    <img src="/assets/img/background/bg_card_profile.png" class="w-100">
                    <div class="profile-content container position-absolute top-0 bottom-0">
                        <div class="row w-100 g-0">
                            <div class="col-4"><span>ข้อมูลบัญชีผู้ใช้</span></div>
                            <div class="col-8 d-inline-flex">
                                <div class="text-end pe-2" style="flex: 1">
                                    <span class="d-block lh-1">ธนาคาร</span>
                                    <span class="d-block  lh-1 fs-bold">{{ $root.getBankNameByBankId }}</span>
                                </div>
                                <div>
                                    <img :src="'/g_assets/img/bank-icon/'+$root.user.bank_id+'.png'" style="width: 2.7em; object-fit:contain;">
                                </div>
                            </div>
                        </div>
                        <div class="profile-center-content row w-100 g-0 d-flex flex-column align-items-center justify-content-center">
                            <div class="bank-number fw-bold w-fit-content lh-1 position-relative" v-html="$root.user.acc_no"></div>
                            <div class="bank-name w-fit-content lh-1 position-relative" v-html="$root.user.first_name+' '+$root.user.last_name"></div> 
                        </div>
                        <div class="profile-content-footer d-flex flex-column w-100">
                            <div class="w-100 lh-2 fw-light member_primary_text_color">ยูสเซอร์สมาชิก : {{ $root.user.tel }}</div>
                            <div class="w-100 lh-1 fw-light member_primary_text_color">เป็นสมาชิกตั้งแต่ : {{ $root.user.registerTime }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="change-pin-container mt-3">
                    <div class="header-block-content d-block rounded-top py-2">
                        <h5 class="text-center mb-0 text-dark lh-1">เปลี่ยน PIN</h5>
                    </div>
                    <div class="card bg-dark-2 rounded-0 rounded-bottom">
                        <div class="card-body p-3">
                        <span class="text-center d-block lh-1 text-content mb-2"><small>หากต้องการเปลี่ยนรหัส PIN สามารถระบุเพื่อเปลี่ยนใหม่ได้</small></span>
                            <form class="theme-form mx-auto" style="max-width: 25em;">

                                <div class="input-group mb-3 custom-style-input">
                                    <span class="input-group-text p-0"><i class="bi bi-lock-fill fs-4"></i></span>
                                    <input class="form-control" type="tel" placeholder="รหัสเก่า (Pin 4 หลัก)" v-model="fm.old_pin" maxlength="4" required>
                                </div>
                                <div class="input-group mb-3 custom-style-input">
                                    <span class="input-group-text"><i class="bi bi-key-fill bi-1-5x fs-4"></i></span>
                                    <input class="form-control" type="tel" placeholder="รหัสใหม่ (Pin 4 หลัก)" v-model="fm.new_pin" maxlength="4" required>
                                </div>
                                <div class="input-group mb-3 custom-style-input">
                                    <span class="input-group-text"><i class="bi bi-key-fill bi-1-5x fs-4"></i></span>
                                    <input class="form-control" type="tel" placeholder="ยืนยันรหัสใหม่ (Pin 4 หลัก)" v-model="fm.conf_pin" maxlength="4" required>
                                </div>
                                <button class="btn btn-custom-primary w-100 mt-4 rounded-pill" type="button" @click="resetPin()"> เปลี่ยนรหัส</button>
                            </form>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
</div>
`,data(){return{fm:{old_pin:'',new_pin:'',conf_pin:''}}},methods:{async resetPin(){if(!/^\d{4}$/.test(this.fm.old_pin))return modal.error('กรุณากรอก รหัสเก่า ให้ถูกต้อง');if(!/^\d{4}$/.test(this.fm.new_pin))return modal.error('กรุณากรอก รหัสใหม่ ใหม่ให้ถูกต้อง');if(this.fm.new_pin!==this.fm.conf_pin)return modal.error('กรุณากรอก รหัสยืนยัน ให้ตรงกับรหัสใหม่ของท่าน');let r=await this.$root.easy.callApi('pin_reset',this.fm);if(!r.success)return modal.error(r.data,r.title);await modal.success(r.data,r.title);this.$root.logout(true);}},beforeRouteEnter(to,from,next){Vue.nextTick(function(){next(async $this=>{next();})});},beforeRouteLeave(to,from,next){this.$destroy();next();},created(){let $this=this;},};