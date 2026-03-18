var member_promotion={template:`<div class="sub-page sub-footer" style="display: flex; justify-content: center; align-items: center;">
    <div class="container promotion-member-container">
        <div class="promotion-item card mb-3 mx-auto border-none shadow rounded overflow-hidden bg-transaparent" v-for="i,idx in list.data" style="width:720px; max-width:95%;">
            <img :src="i.thumbnail || '/assets/img/banner/default1.png'" class="w-100">
            <div class="card-body bg-dark p-0">
                <div class="card bg-dark-2">
                    <div class="card-body">
                        <h3>{{i.title}}</h3>
                        <hr class="m-0">
                        <div class="text-start" v-html="i.description"></div>
                        <div class="small text-muted mt-1">หมดเขต: {{i.time_left}}</div>
                    </div>

                    <div class="card-footer text-center " v-if="$root.isLogged">
                        
                        <div v-if="i.sub_required">
                            <div v-if="i.accept===null">
                                <button class="btn btn-custom-primary" @click="acceptPro(i)">รับโปร</button>
                                <button class="btn btn-custom-secondary text-white" @click="rejectPro(i)">ปฏิเสธโปร</button>
                            </div>
                            <div class="text-center text-content" v-if="i.accept==1"><i class="bi bi-check-lg"></i> รับโปรแล้ว</div>
                            <div class="text-center text-muted" v-if="i.accept==0"><i class="bi bi-x"></i> ปฏิเสธโปรแล้ว</div>
                        </div>

<!--                        <button class="btn btn-custom-secondary text-white" v-if="i.accept && i.sub_required" @click="leavePro(i.id)">ออกจากโปร</button>-->
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>
`,data(){return{list:{},}},methods:{async load(){this.$root.loading=true;let res=await this.$root.easy.callApi('promotion_list');this.$root.loading=false;res.data=res.data.map(o=>{o.description=nl2br(o.description);o.time_left=moment(o.end_at).fromNow();return o;});this.list=res;},async acceptPro(pro){let r=await modal.confirm('ยืนยันการรับโปร?','รับโปร');if(!r)return;this.$root.loading=true;let res=await this.$root.easy.callApi('promotion_subscribe',{promotion_id:pro.id,accept:true});this.$root.loading=false;if(!res.success)return modal.error(res.data,'การปฏิเสธโปรโมชั่นล้มเหลว');modal.success(res.data||'รับโปรสำเร็จ');this.load();},async rejectPro(pro){let r=await modal.confirm('ยืนยันไม่รับโปร?','ไม่รับโปร');if(!r)return;this.$root.loading=true;let res=await this.$root.easy.callApi('promotion_subscribe',{promotion_id:pro.id,accept:false});this.$root.loading=false;if(!res.success)return modal.error(res.data,'ไม่รับโปรล้มเหลว');modal.success(res.data||'ไม่รับโปรสำเร็จ');this.load();},async leavePro(pro){let r=await modal.confirm('ยืนยันออกโปร?','ออกจากโปร');if(!r)return;this.$root.loading=true;let res=await this.$root.easy.callApi('promotion_unsubscribe',{promotion_id:pro.id});this.$root.loading=false;if(!res.success)return modal.error(res.data,'ออกจากโปรโมชั่นล้มเหลว');modal.success(res.data||'ออกโปรโมชั่นสำเร็จ');this.load();},},computed:{},beforeRouteEnter(to,from,next){next(async $this=>{next();})},beforeRouteLeave(to,from,next){console.log('leave',from);next();},created(){let $this=this;Vue.nextTick(()=>{$this.load();});},mounted(){}}