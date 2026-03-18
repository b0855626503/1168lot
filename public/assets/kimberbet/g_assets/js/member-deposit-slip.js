var member_deposit_slip={template:`<div class="sub-page min-h-100">
    <div class="container custom-max-width-container deposit-container">
        <div class="mt-3 text-center">
            <div class="header-block-content d-block rounded-top py-2">
                <h5 class="text-center mb-0 text-dark lh-1">แจ้งเงินไม่เข้าด้วยสลิป</h5>
            </div>
         
            <div class="bg-dark-2" style="min-height: 60vh;">
                <div class="fs-6 text-content py-3 px-2 lh-1">ส่งไฟล์สลิปของท่านที่นี่</div>
                
                <div style="max-width: 30em;" class="m-auto">
                    <input type="file" class="form-control" id="slipFile" accept="image/*" @change="slipUpdate()" ref="slipFile"/>
                    <div class="mt-3">
                        <div class="text-danger" v-if="slipImg.err_big"><i class="fa fa-exclamation"></i> ไม่สามารถเลือกภาพได้, ไฟล์มีขนาดใหญ่เกินไป</div>
                        <img v-if="slipImg.base64" :src="slipImg.base64" class="img-thumbnail d-block mb-3" style="max-width: 20em; margin: auto;">
                        <button class="btn btn-primary mb-3" :disabled="!slipImg.base64" @click="slipSend()"><i class="fa fa-send"></i> ส่งสลิป</button>
                    </div>
                </div>
                
<!--                <div class="card bg-dark bank-deposit-item" v-for="i in $root.deposit.info">-->
<!--                    -->
<!--                    <div class="card-body bank-item-container container">-->
<!--                        <div class="bank-info d-flex">-->
<!--                            <div class="bank-icon"><img :src="'/g_assets/img/bank-icon/'+i.bank_id+'.png'" style="width: 100%; max-width: 5em;"></div>-->
<!--                            <div class="bank-detail ps-4">-->
<!--                                <div class="fs-6 text-start fw-light member_primary_text_color">{{i.bank_name}}</div>-->
<!--                                <div class="fs-6 text-start mt-auto pt-2 member_primary_text_color">{{i.name}}</div>-->
<!--                                <div class="text-warning fs-5 text-start lh-1">{{i.acc_no}}</div>-->
<!--                            </div>-->
<!--                            <div class="btn-copy-bank d-flex flex-column">-->
<!--                            <button class="px-2 py-1 mb-2 btn_copy_bankcode1  shadow rounded-pill btn btn-outline-secondary btn-custom-secondary text-white fw-light" :data-bankcode="i.acc_no" @click="$root.copyBankAcc(i.acc_no,'.deposit-page')" style="min-width: unset;">-->
<!--                                <span class="w-100 flex-row-center-xy ">-->
<!--                                    <i class="bi bi-clipboard-check text-light fw-light btn_copy_bankcode" :data-bankcode="i.acc_no"></i> คัดลอก-->
<!--                                    <input class='ip-copyfrom deposit-page' tabindex='-1' aria-hidden='true'>-->
<!--                                </span>         -->
<!--                            </button>-->
<!--                            <button class="header-block-content px-2 py-1 mt-1 btn_copy_bankcode1 shadow rounded-pill btn btn-outline-secondary btn-custom-secondary fw-light" :data-bankcode="i.acc_no" @click="$root.copyBankAcc(i.acc_no,'.deposit-page')" style="min-width: unset;" v-if="$root.device.is_mobile">                  -->
<!--                                <span class="w-100 flex-row-center-xy" v-if="$root.device.is_mobile">-->
<!--                                -->
<!--                                    <a :href="member_style.true_wallet_link_active ? member_style.true_wallet_link_url : '/open_app?id='+$root.editBankId($root.user.bank_id,i.bank_id)" target="_bank" -->
<!--                                    class="true_money-open-app d-flex justify-content-center text-dark text-decoration-none align-items-center" v-if="i.bank_code ==='TRUE'">-->
<!--                                        <i class="text-dark bi bi-phone-fill fw-light btn_copy_bankcode" :data-bankcode="i.acc_no"></i> เปิดแอพ-->
<!--                                        <input class='ip-copyfrom deposit-page' tabindex='-1' aria-hidden='true'>-->
<!--                                    </a>-->
<!--                                    <a :href="'/open_app?id='+$root.editBankId($root.user.bank_id,i.bank_id)" target="_bank" -->
<!--                                    class="true_money-open-app d-flex justify-content-center text-dark text-decoration-none align-items-center" v-else>-->
<!--                                        <i class="text-dark bi bi-phone-fill fw-light btn_copy_bankcode" :data-bankcode="i.acc_no"></i> เปิดแอพ-->
<!--                                        <input class='ip-copyfrom deposit-page' tabindex='-1' aria-hidden='true'>-->
<!--                                    </a>-->
<!--                                </span>  -->
<!--                            </button>-->
<!--                            </div>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
                
                
            </div>
        </div>
    
        <div class="small text-danger fw-light">* ระบบจะใช้เวลาตรวจ 3-5 นาที หากสลิปไม่มี QR-Code อาจใช้เวลานานกว่านั้น</div>
        <div class="small text-danger fw-light">* ส่งสลิปได้นาทีละครั้งเท่านั้น</div>
    </div>
    
</div>
`,data(){return{bankList:[],slipImg:{raw:null,base64:null,err_big:false,},}},methods:{async slipSend(){console.log('img',this.slipImg.raw);let md=await modal.confirm('ยืนยันการส่งสลิป');if(!md)return;let rs=await this.$root.easy.callApi('deposit_slip_req',{data:this.slipImg.base64,size:this.slipImg.raw.size});console.log('slipRes',rs);if(!rs.success)return modal.error(rs.data||rs.code);modal.success(rs.data);},async slipUpdate(){this.slipImg.raw=this.$refs.slipFile.files[0];this.slipImg.err_big=this.slipImg.size/1024>4;if(this.slipImg.err_big)return;this.slipImg.base64=await fileToBase64(this.slipImg.raw);}},computed:{},watch:{selected_ip(val){this.cmd='';},'filter.group'(val){console.log('chchc',val);}},beforeRouteLeave(to,from,next){this.$destroy();next();},mounted(){let $this=this;},created(){let $this=this;$this.$root.depositInfo();$this.$root.force_target=false;}};