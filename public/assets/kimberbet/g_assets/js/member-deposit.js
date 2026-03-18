var member_deposit={template:`<div class="sub-page min-h-100">
    <div class="container custom-max-width-container deposit-container">
        <div class="mt-3 text-center">
            <div class="header-block-content d-block rounded-top py-2">
                <h5 class="text-center mb-0 text-dark lh-1">ฝากเงิน</h5>
            </div>
         
            <div class="bg-dark-2" style="min-height: 60vh;">
                <div class="fs-6 text-content pt-2 px-2 lh-1">ลูกค้าต้องใช้บัญชีที่ทำการลงทะเบียนไว้เท่านั้นในการฝากเงิน</div>
                <div class="group-bank_user-wrapper">
                    <img class="user-bank-icon" :src="'/g_assets/img/bank-icon/'+$root.user.bank_id+'.png'" style="width: 100%; max-width: 45px;">
                    <div class="group-bank_user">
                        <div class="fs-6 fw-lighter text-start">{{ $root.getBankNameByBankId }} <i class="bi bi-check-circle-fill text-custom-success"></i></div>
                        <div class="fs-5 text-start lh-1 member_primary_text_color">{{ $root.user.first_name }} {{ $root.user.last_name }}</div>
                        <div v-html="$root.user.acc_no" class="fs-5 text-start text-custom-primary lh-1"></div>
                    </div>
                </div>
                <div class="group-bank_user-wrapper">
                    <span class="text-danger px-2 lh-1 py-1 text-center"> {{ member_style.member_note_deposit }}</span>
                </div>
                <div class="card bg-dark bank-deposit-item" v-for="i in $root.deposit.info">
                    
                    <div class="card-body bank-item-container container">
                        <div class="bank-info d-flex">
                            <div class="bank-icon"><img :src="'/g_assets/img/bank-icon/'+i.bank_id+'.png'" style="width: 100%; max-width: 5em;"></div>
                            <div class="bank-detail ps-4">
                                <div class="fs-6 text-start fw-light member_primary_text_color">{{i.bank_name}}</div>
                                <div class="fs-6 text-start mt-auto pt-2 member_primary_text_color">{{i.name}}</div>
                                <div class="text-warning fs-5 text-start lh-1">{{i.acc_no}}</div>
                            </div>
                            <div class="btn-copy-bank d-flex flex-column">
                            <button class="px-2 py-1 mb-2 btn_copy_bankcode1  shadow rounded-pill btn btn-outline-secondary btn-custom-secondary text-white fw-light" :data-bankcode="i.acc_no" @click="$root.copyBankAcc(i.acc_no,'.deposit-page')" style="min-width: unset;">
                                <span class="w-100 flex-row-center-xy ">
                                    <i class="bi bi-clipboard-check text-light fw-light btn_copy_bankcode" :data-bankcode="i.acc_no"></i> คัดลอก
                                    <input class='ip-copyfrom deposit-page' tabindex='-1' aria-hidden='true'>
                                </span>         
                            </button>
                            <button class="header-block-content px-2 py-1 mt-1 btn_copy_bankcode1 shadow rounded-pill btn btn-outline-secondary btn-custom-secondary fw-light" :data-bankcode="i.acc_no" @click="$root.copyBankAcc(i.acc_no,'.deposit-page')" style="min-width: unset;" v-if="$root.device.is_mobile">                  
                                <span class="w-100 flex-row-center-xy" v-if="$root.device.is_mobile">
                                
                                    <a :href="member_style.true_wallet_link_active ? member_style.true_wallet_link_url : '/open_app?id='+$root.editBankId($root.user.bank_id,i.bank_id)" target="_bank" 
                                    class="true_money-open-app d-flex justify-content-center text-dark text-decoration-none align-items-center" v-if="i.bank_code ==='TRUE'">
                                        <i class="text-dark bi bi-phone-fill fw-light btn_copy_bankcode" :data-bankcode="i.acc_no"></i> เปิดแอพ
                                        <input class='ip-copyfrom deposit-page' tabindex='-1' aria-hidden='true'>
                                    </a>
                                    <a :href="'/open_app?id='+$root.editBankId($root.user.bank_id,i.bank_id)" target="_bank" 
                                    class="true_money-open-app d-flex justify-content-center text-dark text-decoration-none align-items-center" v-else>
                                        <i class="text-dark bi bi-phone-fill fw-light btn_copy_bankcode" :data-bankcode="i.acc_no"></i> เปิดแอพ
                                        <input class='ip-copyfrom deposit-page' tabindex='-1' aria-hidden='true'>
                                    </a>
                                </span>  
                            </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center py-2 w-100" v-if="member_style.member_deposit_slip_active">
                    <a href="#/deposit/slip" class="btn btn-outline-warning"><i class="fa fa-exclamation-circle"></i> แจ้งเงินไม่เข้า/แนบสลิป</a>
                </div>
            </div>
        </div>
    
        <div class="small text-danger mt-3 fw-light">* หากต้องการเปลี่ยนข้อมูลบัญชี สามารถแจ้งเปลี่ยนกับเจ้าหน้าที่ได้</div>
    </div>
    
</div>
`,data(){return{bankList:[]}},methods:{},computed:{},watch:{selected_ip(val){this.cmd='';},'filter.group'(val){console.log('chchc',val);}},beforeRouteLeave(to,from,next){this.$destroy();next();},mounted(){let $this=this;},created(){let $this=this;$this.$root.depositInfo();$this.$root.force_target=false;}};