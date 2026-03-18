var member_ref={template:`<div class="sub-page sub-footer" style="min-height: 100vh;">
    <div class="container" style="max-width: 720px;">
        <div class="mt-3 text-center">
            <h2>แนะนำเพื่อน</h2>
        </div>

        <div class="card bg-dark">
            <div class="card-body">
                <div class=" card bg-dark-2 mt-2">
                    <div class="card-body p-1">
                        <div class="row g-2">
                            <div class="col-12 text-center">
                                <div class="card bg-dark py-2">
                                    <div class="small text-muted">เปอร์เซ็นแนะนำเพื่อน</div>
                                    <div class="text-dark text-warning fs-5 bg-light rounded-pill w-100 mx-auto" style="max-width: 14em;">รับ {{info.commission_percent}}%</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 text-center">
                                <div class="card bg-dark bg-circle bg-circle-danger">
                                    <div class="small text-muted">รายได้ทั้งหมด</div>
                                    <div class="text-warning fs-5" v-html="intToNum(info.stake_credit_alltime)"></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4 text-center">
                                <div class="card bg-dark bg-circle bg-circle-success">
                                    <div class="small text-muted">รายได้ปัจจุบัน</div>
                                    <div class="text-warning fs-5" v-html="intToNum(info.stake_credit)"></div>
                                </div>

                            </div>
                            <div class="col-6 col-md-4 text-center">
                                <div class="card bg-dark bg-circle bg-circle-info">
                                    <div class="small text-muted">สมาชิกแนะนำ</div>
                                    <div class="text-warning fs-5 fw-bold" v-html="intToNum(info.ref_cnt)"></div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
             <div class="theme-form mb-4">
                <div class="text-center mt-1 mb-1">
                    <button class="btn btn-primary w-100 rounded-pill btn-custom-primary" style="max-width: 20em;" @click="refWithdraw()">รับเงินแนะนำเพื่อน</button>
                </div>
            </div>
        </div>

       

        <div class="card bg-dark mt-2">
            <div class="card-body">
                <div class="fs-6 fw-light">
                    <div class="w-100 link-ref-coppy">
                        <span class="link-text lh-1" v-if="member_style.member_reference_original_link_active"> 
                            {{ getRefLink() }}
                        </span> 
                        <button type="button" class="btn btn-custom btn-custom-primary btn_copy_ref" 
                            :data-linkref="getRefLink()"
                            v-if="member_style.member_reference_original_link_active"
                             @click="copyLinkRef">
                            <i aria-hidden="true" :data-linkref="getRefLink()" class="fa fa-clone me-1" style="font-size: 22px;"></i> 
                            COPY
                        </button>
                       
                        
                        <span class="link-text" v-if="member_style.member_reference_custom_link_active">
                            {{ member_style.member_reference_custom_link_url }}?member_ref={{this.$root.user.mm_user}}
                        </span>
                        <button type="button" class="btn btn-custom btn-custom-primary btn_copy_ref" 
                            v-if="member_style.member_reference_custom_link_active"
                        :data-linkref="member_style.member_reference_custom_link_url+'?member_ref='+this.$root.user.mm_user" @click="copyLinkRef(member_style.member_reference_custom_link_url+'?member_ref='+$root.user.mm_user)">
                            <i aria-hidden="true" :data-linkref="member_style.member_reference_custom_link_url+'?member_ref='+this.$root.user.mm_user" class="fa fa-clone me-1" style="font-size: 22px;"></i>
                            COPY
                        </button>
                        
                        <input class='ip-copyfrom' tabindex='-1' aria-hidden='true'>
                    </div>
                    <hr>
                    {{ member_style.member_reference_text }}
                </div>
            </div>
        </div>

    </div>

</div>
`,data(){return{info:{},fm:{amount:10}}},methods:{async load(){let r=await this.$root.easy.callApi('ref_withdraw_info');if(!r.success)return modal.error(r.data,'');this.info=r.data;},async refWithdraw(){if(!this.info.stake_credit||this.info.stake_credit<50)return modal.error('ยอดแนะนำควรมีมากกว่า 50 บาทขึ้นไป','');let a=await modal.confirmLoading('การขอถอนแนะนำจะเป็นการขอถอดยอดทั้งหมด<br>ดำเนินการต่อ?','ยืนยันถอนแนะนำ');if(!a)return;let res=await this.$root.easy.callApi('ref_withdraw_req');if(!res.success)return modal.error(res.data,res.title);modal.success(res.data,res.title);},getRefLink(){return `${window.location.origin}/register?member_ref=${this.$root.user.mm_user}`;},copyLinkRef(text=false){if(!text){text=this.getRefLink();}
toast.success(text,'คัดลอกลิ้งค์สมัครสมาชิกสำเร็จ',{closeOnClick:true,timeout:3000,position:'topCenter',class:'custom-copy'});}},computed:{},watch:{selected_ip(val){this.cmd='';},'filter.group'(val){console.log('chchc',val);}},beforeRouteEnter(to,from,next){next(async $this=>{$this.load();next();})},beforeRouteLeave(to,from,next){this.$destroy();next();},mounted(){let $this=this;},created(){$(document).on('click','.btn_copy_ref',()=>{let _this=this;let input=document.querySelector("input.ip-copyfrom");let copyVal=event.target.dataset.linkref;input.value=copyVal;input.select();console.log(document.execCommand("copy"))})}};