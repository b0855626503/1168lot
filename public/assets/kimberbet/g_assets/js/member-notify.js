var member_notify = {
    template: `<div class="sub-page sub-footer min-h-100">
    <div class="container custom-max-width-container notify-container">

        <div class="card bg-dark">
            <div class="card-body px-0">
                <div class="header-block-content d-block rounded-top py-2 mt-3">
                    <h5 class="text-center mb-0 text-dark lh-1">การแจ้งเตือน</h5>
                </div>
                <div class="bg-dark-2" style="min-height: 60vh;">
                    <div v-if="msg.data.length" class="list-message">
                        <div v-for="i in msg.data" class="card text-dark bd-callout shadow-sm" :class="'bd-callout-'+i.cls">
                            <div class="card-body d-flex justify-content-between row p-2">
                                <div class="col-sm-6 d-flex">
                                    <strong>{{i.title}}</strong>
                                    <div class="ms-4" v-html="i.text"></div>
                                </div>
                                <div class="col-sm-6 text-end"><i class="bi bi-clock"></i> <span class="fw-light">{{i.created_at}}</span> <small>({{i.created_ago}})</small></div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="card text-dark" style="min-height: 25em;">
                        <div class="card-body text-center">
                            <em>ไม่มีรายการ</em>
                        </div>
                    </div>
                </div>
               
                <zpagenav class="mt-2" :page="msg.page" :page-size="msg.length" :total="msg.totalRecords" :max-link="5" :page-handler="load"><zpagenav>
                <em class="small fw-light text-muted">หมายเหตุ: รายการมีอายุ 7 วัน</em>

            </div>
        </div>
    </div>

</div>
`,
    data() {
        return {
            msg: {page: 1, length: 10, data: []},
            obj: {slide: null},
            status: {
                P: {name: 'รอตรวจสอบ', cls: 'text-warning'},
                OK: {name: 'อนุมัติแล้ว', cls: 'text-success'},
                C: {name: 'ยกเลิก', cls: 'text-secondary'},
                ER: {name: 'ผิดพลาก', cls: 'text-danger'},
            },
        }
    },
    methods: {
        async load(page = 1) {
            if (this.$root.user.unread) this.$root.easy.callApi('msg_read');
            let r = await this.$root.easy.callApi('msg_list', {page, length: this.msg.length});
            r.data.map(o=>{
                o.created_ago = moment(o.created_at).fromNow();
                return o;
            });
            this.msg = r;
        },
    },
    computed: {

    },
    watch: {},
    beforeRouteEnter (to, from, next) {
        Vue.nextTick(function() {
            next(async $this => {
                $this.load();
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
    },
};
