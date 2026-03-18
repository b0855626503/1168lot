<script>
    window.LineOAEventsChannel = "{{ config('app.name') }}_events";
</script>

<script type="text/x-template" id="line-oa-chat-template">
    <b-container fluid class="px-0">
        <b-row no-gutters>
            {{-- ====== LEFT: CONVERSATION LIST ====== --}}
            <b-col cols="12" md="4" class="border-right" style="height: calc(100vh - 180px);">
                <div class="d-flex flex-column h-100">

                    {{-- HEADER + FILTERS --}}
                    <div class="p-2 border-bottom bg-light">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="mb-0">
                                <i class="far fa-comments"></i>
                                แชตลูกค้า
                            </h5>
                            <div class="text-right">
                                <div>
                                    <b-badge variant="primary" v-if="filters.status === 'open'">เปิดอยู่</b-badge>
                                    <b-badge variant="secondary" v-else>ปิดแล้ว</b-badge>
                                </div>
                            </div>
                        </div>

                        {{-- Scope tab: ทั้งหมด / ที่รับเรื่อง --}}
                        <b-nav pills small class="mt-2">
                            <b-nav-item
                                    :active="filters.scope === 'all'"
                                    @click="changeScope('all')"
                            >
                                ทั้งหมด
                            </b-nav-item>
                            <b-nav-item
                                    :active="filters.scope === 'mine'"
                                    @click="changeScope('mine')"
                            >
                                ที่รับเรื่อง
                            </b-nav-item>
                        </b-nav>

                        <b-input-group size="sm" class="mt-2">
                            <b-form-input
                                    v-model="filters.q"
                                    placeholder="ค้นหา ชื่อลูกค้า / ยูส / เบอร์"
                                    @input="onSearchInput"
                            ></b-form-input>
                            <b-input-group-append>
                                <b-button size="sm" variant="outline-secondary" @click="fetchConversations(1)">
                                    <i class="fa fa-search"></i>
                                </b-button>
                            </b-input-group-append>
                        </b-input-group>

                        <div class="d-flex mt-2">
                            <b-form-select
                                    v-model="filters.status"
                                    :options="statusOptions"
                                    size="sm"
                                    class="mr-2"
                                    @change="fetchConversations(1)"
                            ></b-form-select>

                            <b-form-select
                                    v-model="filters.account_id"
                                    :options="accountOptions"
                                    size="sm"
                                    @change="fetchConversations(1)"
                            >
                                <template #first>
                                    <option :value="null">ทุก OA</option>
                                </template>
                            </b-form-select>
                        </div>
                    </div>

                    {{-- LIST --}}
                    <div class="flex-fill overflow-auto">
                        <div v-if="loadingList" class="text-center text-muted py-3">
                            <b-spinner small class="mr-2"></b-spinner>
                            กำลังโหลดรายการแชต...
                        </div>

                        <div v-else-if="conversations.length === 0" class="text-center text-muted py-3">
                            ไม่พบห้องแชต
                        </div>

                        <b-list-group flush v-else>
                            <b-list-group-item
                                    v-for="conv in conversations"
                                    :key="conv.id"
                                    button
                                    @click="selectConversation(conv)"
                                    :class="conversationItemClass(conv)"
                            >
                                <div class="d-flex">
                                    <div class="mr-2">
                                        <img
                                                v-if="conv.contact && conv.contact.picture_url"
                                                :src="conv.contact.picture_url"
                                                class="rounded-circle"
                                                style="width: 40px; height: 40px; object-fit: cover;"
                                        >
                                        <div v-else
                                             class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                             style="width: 40px; height: 40px;">
                                            <i class="far fa-user"></i>
                                        </div>
                                    </div>

                                    <div class="flex-fill">
                                        <div class="d-flex justify-content-between">
                                            <strong>
                                                @{{ (conv.contact && (conv.contact.display_name ||
                                                conv.contact.member_username)) || 'ไม่ทราบชื่อ' }}
                                            </strong>
                                            <small class="text-muted" v-if="conv.last_message_at">
                                                @{{ formatDateTime(conv.last_message_at) }}
                                            </small>
                                        </div>

                                        <div class="text-muted small text-truncate">
                                                <span v-if="conv.line_account && conv.line_account.name">
                                                    [@{{ conv.line_account.name }}]
                                                </span>
                                            @{{ conv.last_message || 'ยังไม่มีข้อความ' }}
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <div>
                                                <small class="text-muted d-block">
                                                    ยูส: @{{ conv.contact && conv.contact.member_username || '-' }}
                                                </small>
                                                {{-- แสดงชื่อคนปิด + เวลา ถ้าห้องปิดแล้ว --}}
                                                <div
                                                        v-if="conv.status === 'closed'"
                                                        class="text-muted small"
                                                >
                                                    ปิดโดย @{{ conv.closed_by_employee_name || 'พนักงาน' }}
                                                    <span v-if="conv.closed_at">
                                                            เมื่อ @{{ formatDateTime(conv.closed_at) }}
                                                        </span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <b-badge
                                                        v-if="conv.assigned_employee_name && conv.status !== 'closed'"
                                                        variant="info"
                                                        class="mr-1"
                                                >
                                                    รับเรื่องโดย @{{ conv.assigned_employee_name }}
                                                </b-badge>
                                                <b-badge v-if="conv.unread_count > 0" variant="danger">
                                                    @{{ conv.unread_count }}
                                                </b-badge>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </b-list-group-item>
                        </b-list-group>
                    </div>

                    {{-- PAGINATION --}}
                    <div class="border-top p-1 d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            หน้า @{{ pagination.current_page }} / @{{ pagination.last_page }} (รวม @{{
                            pagination.total }} ห้อง)
                        </small>
                        <div>
                            <b-button size="sm" variant="outline-secondary"
                                      :disabled="pagination.current_page <= 1 || loadingList"
                                      @click="fetchConversations(pagination.current_page - 1)">
                                <i class="fa fa-chevron-left"></i>
                            </b-button>
                            <b-button size="sm" variant="outline-secondary"
                                      :disabled="pagination.current_page >= pagination.last_page || loadingList"
                                      @click="fetchConversations(pagination.current_page + 1)">
                                <i class="fa fa-chevron-right"></i>
                            </b-button>
                        </div>
                    </div>
                </div>
            </b-col>

            {{-- ====== RIGHT: CHAT WINDOW ====== --}}
            <b-col cols="12" md="8" style="height: calc(100vh - 180px);">
                <div class="d-flex flex-column h-100">

                    {{-- HEADER --}}
                    <div class="p-2 border-bottom bg-light" v-if="selectedConversation">
                        <div class="d-flex align-items-center">
                            <div class="mr-2"
                                 v-if="selectedConversation.contact"
                                 @click="openMemberModal"
                                 style="cursor: pointer;">
                                <img
                                        v-if="selectedConversation.contact.picture_url"
                                        :src="selectedConversation.contact.picture_url"
                                        class="rounded-circle"
                                        style="width: 40px; height: 40px; object-fit: cover;"
                                >
                                <div v-else
                                     class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                     style="width: 40px; height: 40px;">
                                    <i class="far fa-user"></i>
                                </div>
                            </div>
                            <div class="flex-fill">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">

                                        {{-- ถ้ายังไม่มี member_id ให้คลิกชื่อเพื่อผูกสมาชิก --}}
                                        <template
                                                v-if="selectedConversation.contact && !selectedConversation.contact.member_id">
                                                <span
                                                        class="text-primary"
                                                        style="cursor: pointer; text-decoration: underline;"
                                                        @click="openMemberModal"
                                                >
                                                    @{{ (selectedConversation.contact &&
                                                    (selectedConversation.contact.display_name ||
                                                    selectedConversation.contact.member_username)) || 'ไม่ทราบชื่อ' }}
                                                </span>
                                        </template>
                                        <template v-else>
                                                <span>
                                                    @{{ (selectedConversation.contact &&
                                                    (selectedConversation.contact.display_name ||
                                                    selectedConversation.contact.member_username)) || 'ไม่ทราบชื่อ' }}
                                                </span>
                                        </template>

                                    </h5>
                                    <div class="text-right">
                                        <small class="text-muted d-block" v-if="selectedConversation.line_account">
                                            OA: @{{ selectedConversation.line_account.name }}
                                        </small>
                                        <div class="mt-1">
                                            <b-badge
                                                    v-if="selectedConversation.status === 'closed'"
                                                    variant="secondary"
                                                    class="mr-1"
                                            >
                                                ปิดโดย @{{ selectedConversation.closed_by_employee_name || 'พนักงาน' }}
                                            </b-badge>
                                            <b-badge
                                                    v-else-if="selectedConversation.assigned_employee_name"
                                                    variant="info"
                                                    class="mr-1"
                                            >
                                                รับเรื่องโดย @{{ selectedConversation.assigned_employee_name }}
                                            </b-badge>

                                            <b-button
                                                    v-if="selectedConversation.status === 'open'"
                                                    size="sm"
                                                    variant="outline-primary"
                                                    class="mr-1"
                                                    @click="acceptConversation"
                                            >
                                                รับเรื่อง
                                            </b-button>
                                            <b-button
                                                    v-if="selectedConversation.status !== 'closed'"
                                                    size="sm"
                                                    variant="outline-danger"
                                                    @click="closeConversation"
                                            >
                                                ปิดเคส
                                            </b-button>
                                        </div>

                                        <div
                                                class="mt-1"
                                                v-if="selectedConversation.status === 'closed' && selectedConversation.closed_at"
                                        >
                                            <small class="text-muted">
                                                ปิดเมื่อ @{{ formatDateTime(selectedConversation.closed_at) }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <div class="text-muted">
                                        ยูส: @{{ selectedConversation.contact &&
                                        selectedConversation.contact.member_username || '-' }}
                                        /
                                        เบอร์: @{{ selectedConversation.contact &&
                                        selectedConversation.contact.member_mobile || '-' }}
                                        /
                                        ชื่อ: @{{ selectedConversation.contact &&
                                        selectedConversation.contact.member_name || '-' }}
                                        /
                                        ธนาคาร: @{{ selectedConversation.contact &&
                                        selectedConversation.contact.member_bank_name || '-' }}
                                        /
                                        เลขบัญชี: @{{ selectedConversation.contact &&
                                        selectedConversation.contact.member_acc_no || '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-2 border-bottom bg-light text-muted text-center" v-else>
                        เลือกห้องแชตจากด้านซ้ายเพื่อเริ่มสนทนา
                    </div>

                    {{-- MESSAGE LIST --}}
                    <div class="flex-fill overflow-auto px-2 py-2" ref="messageContainer">
                        <div v-if="!selectedConversation"
                             class="h-100 d-flex align-items-center justify-content-center text-muted">
                            ยังไม่ได้เลือกห้องแชต
                        </div>

                        <template v-else>
                            <div v-if="loadingMessages" class="text-center text-muted py-3">
                                <b-spinner small class="mr-2"></b-spinner>
                                กำลังโหลดข้อความ...
                            </div>

                            <div v-else-if="messages.length === 0" class="text-center text-muted py-3">
                                ยังไม่มีประวัติการสนทนา
                            </div>

                            <div v-else>
                                <div v-for="msg in messages" :key="msg.id" class="mb-2">
                                    <div :class="messageWrapperClass(msg)">
                                        <div :class="messageBubbleClass(msg)">
                                            <div class="small" v-if="msg.direction === 'outbound'">
                                                <strong>พนักงาน</strong>
                                                <strong v-if="msg.meta && msg.meta.employee_name">
                                                    - @{{ msg.meta.employee_name }}
                                                </strong>
                                            </div>
                                            <div class="small" v-else-if="msg.source === 'bot'">
                                                <strong>บอท</strong>
                                            </div>

                                            <div class="whitespace-pre-wrap">
                                                <!-- TEXT -->
                                                <template v-if="msg.type === 'text'">
                                                    @{{ msg.text }}
                                                </template>

                                                <!-- STICKER -->
                                                <template v-else-if="msg.type === 'sticker'">
                                                    <img
                                                            :src="stickerUrl(msg)"
                                                            class="img-fluid"
                                                            style="max-width:130px;"
                                                            alt="[Sticker]"
                                                    >
                                                </template>

                                                <!-- IMAGE -->
                                                <template v-else-if="msg.type === 'image'">
                                                    <img
                                                            :src="msg.payload?.message?.contentUrl || msg.payload?.message?.previewUrl"
                                                            class="img-fluid rounded"
                                                            style="max-width:240px;"
                                                            alt="[Image]"
                                                    >
                                                </template>

                                                <!-- VIDEO -->
                                                <template v-else-if="msg.type === 'video'">
                                                    <video
                                                            controls
                                                            class="img-fluid rounded"
                                                            style="max-width:260px;"
                                                            :poster="msg.payload?.message?.previewUrl"
                                                    >
                                                        <source :src="msg.payload?.message?.contentUrl">
                                                    </video>
                                                </template>

                                                <!-- AUDIO -->
                                                <template v-else-if="msg.type === 'audio'">
                                                    <audio controls :src="msg.payload?.message?.contentUrl"></audio>
                                                </template>

                                                <!-- LOCATION -->
                                                <template
                                                        v-else-if="msg.type === 'location' && msg.payload && msg.payload.message">
                                                    <div>
                                                        <strong>@{{ msg.payload.message.title || 'ตำแหน่ง'
                                                            }}</strong><br>
                                                        @{{ msg.payload.message.address }}
                                                        <br>
                                                        <a
                                                                :href="'https://maps.google.com/?q=' + msg.payload.message.latitude + ',' + msg.payload.message.longitude"
                                                                target="_blank"
                                                        >
                                                            เปิดแผนที่
                                                        </a>
                                                    </div>
                                                </template>

                                                <!-- UNSUPPORTED -->
                                                <template v-else>
                                                    [@{{ msg.type }}]
                                                </template>
                                            </div>

                                            <div class="text-right text-muted small mt-1">
                                                @{{ formatDateTime(msg.sent_at) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>


                    {{-- REPLY BOX --}}
                    <div class="border-top p-2 bg-white" v-if="selectedConversation">
                        <b-input-group>

                            {{-- ปุ่มแนบรูป --}}
                            <b-input-group-prepend>
                                <b-button variant="outline-secondary" size="sm"
                                          @click="$refs.imageInput.click()"
                                          :disabled="selectedConversation && selectedConversation.status === 'closed'">
                                    <i class="fa fa-paperclip"></i>
                                </b-button>
                            </b-input-group-prepend>

                            <b-form-textarea
                                    v-model="replyText"
                                    rows="1"
                                    max-rows="4"
                                    :placeholder="selectedConversation && selectedConversation.status === 'closed'
            ? 'เคสนี้ถูกปิดแล้ว ไม่สามารถส่งข้อความได้'
            : 'พิมพ์ข้อความเพื่อตอบลูกค้า แล้วกด Enter หรือปุ่ม ส่ง'"
                                    :disabled="selectedConversation && selectedConversation.status === 'closed'"
                                    @keydown.enter.exact.prevent="selectedConversation && selectedConversation.status !== 'closed' && sendReply()"
                            ></b-form-textarea>

                            <b-input-group-append>
                                <b-button variant="primary"
                                          :disabled="sending
                  || replyText.trim() === ''
                  || (selectedConversation && selectedConversation.status === 'closed')"
                                          @click="sendReply">
        <span v-if="sending">
            <b-spinner small class="mr-1"></b-spinner> กำลังส่ง...
        </span>
                                    <span v-else>
            <i class="fa fa-paper-plane"></i> ส่ง
        </span>
                                </b-button>
                            </b-input-group-append>

                        </b-input-group>

                        {{-- input file ซ่อน --}}
                        <input type="file"
                               ref="imageInput"
                               class="d-none"
                               accept="image/*"
                               @change="onSelectImage">
                    </div>

                </div>
            </b-col>
        </b-row>

        {{-- MODAL: ผูก contact กับ member --}}
        <b-modal
                id="line-oa-member-modal"
                ref="memberModal"
                title="เชื่อมลูกค้ากับสมาชิก"
                size="sm"
                centered
                hide-footer
                body-class="pt-2 pb-2"
                @hide="resetMemberModal"
        >
            <b-form @submit.prevent="saveMemberLink">
                <b-form-group label="Member ID:" label-for="member_id" label-cols="4" label-class="pt-1">
                    <b-input-group>
                        <b-form-input
                                id="member_id"
                                v-model="memberModal.member_id"
                                placeholder="เช่น 12345"
                                autocomplete="off"
                                size="sm"
                        ></b-form-input>
                        <b-input-group-append>
                            <b-button
                                    variant="secondary"
                                    size="sm"
                                    @click.prevent="searchMember"
                                    :disabled="memberModal.loading || !memberModal.member_id"
                                    class="px-3"
                            >
                                <b-spinner v-if="memberModal.loading" small class="mr-1"></b-spinner>
                                <span v-else>ค้นหา</span>
                            </b-button>
                        </b-input-group-append>
                    </b-input-group>
                </b-form-group>

                <b-alert
                        v-if="memberModal.error"
                        show
                        variant="danger"
                        class="py-1 mb-2"
                >
                    @{{ memberModal.error }}
                </b-alert>

                <b-card
                        v-if="memberModal.member"
                        class="mb-2"
                        body-class="py-2 px-2"
                >
                    <div class="small">
                        <div><strong>ชื่อจริง:</strong> @{{ memberModal.member.name || '-' }}</div>
                        <div><strong>Username:</strong> @{{ memberModal.member.username || '-' }}</div>
                        <div><strong>เบอร์:</strong> @{{ memberModal.member.mobile || '-' }}</div>
                    </div>
                </b-card>

                <div class="d-flex justify-content-end mt-2">
                    <b-button
                            variant="secondary"
                            size="sm"
                            class="mr-2"
                            @click="$refs.memberModal.hide()"
                    >
                        ยกเลิก
                    </b-button>
                    <b-button
                            variant="primary"
                            size="sm"
                            type="submit"
                            :disabled="memberModal.saving || !memberModal.member"
                    >
                        <b-spinner v-if="memberModal.saving" small class="mr-1"></b-spinner>
                        <span v-else>บันทึก</span>
                    </b-button>
                </div>
            </b-form>
        </b-modal>


    </b-container>
</script>

<script type="module">
    Vue.component('line-oa-chat', {
        template: '#line-oa-chat-template',
        data() {
            return {
                conversations: [],
                pagination: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 20,
                    total: 0,
                },
                filters: {
                    status: 'open',
                    q: '',
                    account_id: null,
                    scope: 'all', // 'all' | 'mine'
                },
                statusOptions: [
                    {value: 'open', text: 'ห้องเปิดอยู่'},
                    {value: 'closed', text: 'ห้องปิดแล้ว'},
                ],
                accountOptions: [],

                loadingList: false,
                selectedConversation: null,
                messages: [],
                loadingMessages: false,

                replyText: '',
                sending: false,
                uploadingImage: false,
                autoRefreshTimer: null,

                // debounce การค้นหา
                searchDelayTimer: null,

                // modal ผูก member
                memberModal: {
                    member_id: '',
                    member: null,
                    loading: false,
                    saving: false,
                    error: '',
                },

                // จะ set เป็น function ใน subscribeRealtime()
                unsubscribeRealtime: null,
            };
        },
        created() {
            this.fetchConversations(1);
            this.startAutoRefresh();
            this.subscribeRealtime();
        },
        beforeDestroy() {
            this.stopAutoRefresh();

            if (this.selectedConversation) {
                this.unlockConversation(this.selectedConversation);
            }

            if (typeof this.unsubscribeRealtime === 'function') {
                this.unsubscribeRealtime();
            }
        },
        methods: {
            apiUrl(path) {
                return '/admin/line-oa/' + path.replace(/^\/+/, '');
            },

            /**
             * โหลดรายการห้องแชต
             * options.silent = true จะไม่โชว์ spinner (ใช้กับ auto-refresh)
             */
            fetchConversations(page = 1, options = {}) {
                const silent = options.silent === true;

                if (!silent) {
                    this.loadingList = true;
                }

                return axios.get(this.apiUrl('conversations'), {
                    params: {
                        page: page,
                        status: this.filters.status,
                        q: this.filters.q,
                        account_id: this.filters.account_id,
                        scope: this.filters.scope, // ให้ backend ใช้ filter ได้
                    }
                }).then(res => {
                    const body = res.data || {};

                    this.conversations = body.data || [];
                    this.pagination = Object.assign(this.pagination, body.meta || {});
                    if (this.filters.account_id === null) {
                        const accounts = {};
                        this.conversations.forEach(conv => {
                            if (conv.line_account && conv.line_account.id) {
                                accounts[conv.line_account.id] = conv.line_account.name || ('OA #' + conv.line_account.id);
                            }
                        });
                        this.accountOptions = Object.keys(accounts).map(id => ({
                            value: parseInt(id),
                            text: accounts[id],
                        }));
                    }

                    if (!this.selectedConversation && this.conversations.length > 0) {
                        this.selectConversation(this.conversations[0]);
                    }
                }).catch(err => {
                    console.error('fetchConversations error', err);
                }).finally(() => {
                    if (!silent) {
                        this.loadingList = false;
                    }
                });
            },

            // ใช้สำหรับกรณี backend ต้องดึง content เอง (ตอนนี้ template ใช้ payload อยู่แล้ว)
            imageUrl(msg) {
                const payloadMsg = msg.payload && msg.payload.message ? msg.payload.message : null;

                if (payloadMsg) {
                    if (payloadMsg.contentUrl) {
                        return payloadMsg.contentUrl;
                    }
                    if (payloadMsg.previewUrl) {
                        return payloadMsg.previewUrl;
                    }
                }

                return this.apiUrl('messages/' + msg.id + '/content');
            },

            selectConversation(conv) {
                this.selectedConversation = conv;

                // โหลดข้อความให้เสร็จก่อน แล้วค่อย scroll ลงล่างสุด
                this.fetchMessages(conv.id, {limit: 50}).then(() => {
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                });
            },

            fetchMessages(conversationId, options = {}) {
                if (!conversationId) return Promise.resolve();

                const silent = options.silent === true;

                if (!silent) {
                    this.loadingMessages = true;
                }

                const params = {
                    limit: options.limit || 50,
                };

                if (options.before_id) {
                    params.before_id = options.before_id;
                }

                return axios.get(this.apiUrl('conversations/' + conversationId), {params})
                    .then(res => {
                        const body = res.data || {};
                        const messages = body.messages || [];

                        this.messages = messages;
                        this.selectedConversation = body.conversation || this.selectedConversation;

                        // ===== เคลียร์ตัวเลข unread ฝั่ง list ทันที =====
                        if (this.selectedConversation && this.selectedConversation.id === conversationId) {
                            this.selectedConversation.unread_count = 0;

                            const idx = this.conversations.findIndex(c => c.id === conversationId);
                            if (idx !== -1) {
                                const updated = Object.assign({}, this.conversations[idx], {
                                    unread_count: 0,
                                });
                                this.$set(this.conversations, idx, updated);
                            }
                        }
                        // ===============================================

                        this.$nextTick(() => {
                            this.scrollToBottom();
                        });
                    })
                    .catch(err => {
                        console.error('fetchMessages error', err);
                    })
                    .finally(() => {
                        if (!silent) {
                            this.loadingMessages = false;
                        }
                    });
            },

            sendReply() {
                if (!this.selectedConversation || this.sending) return;

                const text = this.replyText.trim();
                if (text === '') return;

                this.sending = true;

                axios.post(this.apiUrl('conversations/' + this.selectedConversation.id + '/reply'), {
                    text: text,
                }).then(res => {
                    const msg = res.data && res.data.data ? res.data.data : null;

                    if (msg) {
                        // เพิ่มข้อความฝั่ง agent ทันที
                        this.messages.push(msg);

                        // อัปเดตข้อมูลห้องที่เลือก (last_message / last_message_at / unread_count)
                        if (this.selectedConversation) {
                            this.selectedConversation.last_message = msg.text || this.selectedConversation.last_message;
                            this.selectedConversation.last_message_at = msg.sent_at || this.selectedConversation.last_message_at;
                            this.selectedConversation.unread_count = 0;
                        }

                        // sync กับ list ด้านซ้าย
                        const idx = this.conversations.findIndex(c => c.id === this.selectedConversation.id);
                        if (idx !== -1) {
                            const conv = this.conversations[idx];
                            const updated = Object.assign({}, conv, {
                                last_message: this.selectedConversation.last_message,
                                last_message_at: this.selectedConversation.last_message_at,
                                unread_count: 0,
                            });
                            this.$set(this.conversations, idx, updated);
                        }
                    }

                    this.replyText = '';

                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });

                    // ไม่ reload list ทั้งก้อน เพื่อกันกระพริบ
                }).catch(err => {
                    console.error('sendReply error', err);
                    alert('ส่งข้อความไม่สำเร็จ กรุณาลองใหม่');
                }).finally(() => {
                    this.sending = false;
                });
            },

            scrollToBottom() {
                const el = this.$refs.messageContainer;
                if (!el) return;
                el.scrollTop = el.scrollHeight;
            },

            formatDateTime(dt) {
                if (!dt) return '';
                const d = new Date(dt);
                if (isNaN(d.getTime())) {
                    return dt; // กันเคส string แปลก ๆ
                }
                const pad = n => String(n).padStart(2, '0');
                return d.getFullYear() + '-' +
                    pad(d.getMonth() + 1) + '-' +
                    pad(d.getDate()) + ' ' +
                    pad(d.getHours()) + ':' +
                    pad(d.getMinutes()) + ':' +
                    pad(d.getSeconds());
            },

            messageWrapperClass(msg) {
                if (msg.direction === 'outbound') {
                    return 'd-flex justify-content-end';
                }
                return 'd-flex justify-content-start';
            },

            messageBubbleClass(msg) {
                let base = 'p-2 rounded mb-1';
                if (msg.direction === 'outbound') {
                    return base + ' gt-msg-agent';
                }
                if (msg.source === 'bot') {
                    return base + ' bg-warning';
                }
                return base + ' bg-light';
            },

            conversationItemClass(conv) {
                const classes = ['py-2'];
                if (this.selectedConversation && this.selectedConversation.id === conv.id) {
                    classes.push('gt-conv-active');
                }
                return classes;
            },

            startAutoRefresh() {
                this.stopAutoRefresh();
                this.autoRefreshTimer = setInterval(() => {
                    // ใช้ silent เพื่อไม่ให้ list กระพริบ
                    this.fetchConversations(this.pagination.current_page || 1, {silent: true});
                    if (this.selectedConversation) {
                        this.fetchMessages(this.selectedConversation.id, {limit: 50, silent: true});
                    }
                }, 60000); // ตอนนี้มี realtime แล้ว ใช้แค่ sync ระยะยาว ทุก 60 วิพอ
            },

            stopAutoRefresh() {
                if (this.autoRefreshTimer) {
                    clearInterval(this.autoRefreshTimer);
                    this.autoRefreshTimer = null;
                }
            },
            updateOrInsertConversation(conv) {
                if (!conv || !conv.id) return;

                const id = conv.id;

                // หา index เดิมจาก list
                const idx = this.conversations.findIndex(c => c.id === id);

                if (idx !== -1) {
                    // --- อัปเดตเฉพาะฟิลด์ที่เปลี่ยน ไม่ replace object ทิ้ง ---
                    this.$set(this.conversations, idx, {
                        ...this.conversations[idx],
                        ...conv
                    });
                } else {
                    // --- ถ้ายังไม่มี → insert ด้านบนสุดของ list ---
                    this.conversations.unshift(conv);
                }
            },
            // auto-search: debounce ตอนพิมพ์ค้นหา
            onSearchInput() {
                if (this.searchDelayTimer) {
                    clearTimeout(this.searchDelayTimer);
                }
                this.searchDelayTimer = setTimeout(() => {
                    this.fetchConversations(1);
                }, 500); // 0.5 วิหลังหยุดพิมพ์
            },

            onSelectImage(e) {
                const file = e.target.files[0];
                if (!file) return;

                // reset input ให้เลือกไฟล์เดิมซ้ำได้
                this.$refs.imageInput.value = '';

                // validate ง่าย ๆ ก่อน
                if (!file.type.startsWith('image/')) {
                    alert('กรุณาเลือกไฟล์รูปภาพเท่านั้น');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) { // 5 MB
                    alert('ไฟล์ใหญ่เกินไป สูงสุด 5MB');
                    return;
                }

                this.sendImage(file);
            },

            sendImage(file) {
                if (!this.selectedConversation || this.uploadingImage) return;

                const convId = this.selectedConversation.id;
                this.uploadingImage = true;

                const form = new FormData();
                form.append('image', file);

                axios.post(this.apiUrl('conversations/' + convId + '/reply-image'), form, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }).then(res => {
                    const msg = res.data && res.data.data ? res.data.data : null;
                    if (msg) {
                        // เติม message รูปเข้า list ทันที
                        this.messages.push(msg);

                        // อัปเดตข้อมูลห้อง (last_message / last_message_at / unread_count)
                        if (this.selectedConversation) {
                            this.selectedConversation.last_message =
                                this.buildPreviewFromMessage(msg) || this.selectedConversation.last_message;
                            this.selectedConversation.last_message_at = msg.sent_at || this.selectedConversation.last_message_at;
                            this.selectedConversation.unread_count = 0;
                        }

                        const idx = this.conversations.findIndex(c => c.id === convId);
                        if (idx !== -1) {
                            const conv = this.conversations[idx];
                            const updated = Object.assign({}, conv, {
                                last_message: this.selectedConversation.last_message,
                                last_message_at: this.selectedConversation.last_message_at,
                                unread_count: 0,
                            });
                            this.$set(this.conversations, idx, updated);
                        }

                        this.$nextTick(() => this.scrollToBottom());
                    }
                }).catch(err => {
                    console.error('sendImage error', err);
                    alert('ส่งรูปไม่สำเร็จ กรุณาลองใหม่');
                }).finally(() => {
                    this.uploadingImage = false;
                });
            },

            // ====== สร้าง preview จาก message เวลา event ไม่ส่ง last_message มา ======
            buildPreviewFromMessage(msg) {
                if (!msg) return '';
                if (msg.type === 'text' && msg.text) {
                    const text = msg.text;
                    return text.length > 100 ? text.substr(0, 97) + '...' : text;
                }
                return '[' + (msg.type || 'message') + ']';
            },

            // ====== URL สติ๊กเกอร์ LINE ======
            stickerUrl(msg) {
                if (!msg || !msg.payload || !msg.payload.message) return null;

                const pkg = msg.payload.message.packageId;
                const sid = msg.payload.message.stickerId;
                const type = msg.payload.message.stickerResourceType || 'STATIC';

                if (!pkg || !sid) return null;

                if (type === 'STATIC') {
                    return `https://stickershop.line-scdn.net/stickershop/v1/sticker/${sid}/android/sticker.png`;
                }

                if (type === 'ANIMATION' || type === 'ANIMATION_SOUND') {
                    return `https://stickershop.line-scdn.net/stickershop/v1/sticker/${sid}/android/sticker_animation.png`;
                }

                if (type === 'POPUP') {
                    return `https://stickershop.line-scdn.net/stickershop/v1/sticker/${sid}/android/sticker_popup.png`;
                }

                // fallback
                return `https://stickershop.line-scdn.net/stickershop/v1/sticker/${sid}/android/sticker.png`;
            },
            playNewMessageSound() {
                const audio = document.getElementById('line-noti-audio');
                if (!audio) return;
                audio.muted = false;
                // reset cursor เพื่อให้เล่นซ้ำได้
                audio.currentTime = 0;

                const playSound = () => {
                    audio.currentTime = 0;
                    audio.play().catch(() => {});
                };

                playSound();

            },
            // ====== Realtime จาก Echo ======
            subscribeRealtime() {
                if (!window.Echo || !window.LineOAEventsChannel) return;

                const channelName = window.LineOAEventsChannel;
                const vm = this;

                console.log('[LineOA] subscribeRealtime to', channelName);

                window.Echo.channel(channelName)
                    .listen('.LineOAChatMessageReceived', (e) => {
                        console.log('[LineOA] รับ event จาก websocket:', e);
                        vm.handleRealtimeIncoming(e);
                        if (e.message && e.message.direction === 'inbound') {
                            vm.playNewMessageSound();
                        }
                    })
                    .listen('.LineOAChatConversationUpdated', (e) => {
                        const conv = e.conversation;
                        this.updateOrInsertConversation(conv);
                        if (this.selectedConversation &&
                            this.selectedConversation.id === conv.id)
                        {
                            this.fetchMessages(conv.id, { limit: 50, silent: true });
                        }
                    })
                    .listen('.LineOAConversationAssigned', (e) => {
                        vm.handleConversationAssigned(e);
                    })
                    .listen('.LineOAConversationClosed', (e) => {
                        vm.handleConversationClosed(e);
                    })
                    .listen('.LineOAConversationLocked', (e) => {
                        vm.handleConversationLocked(e);
                    });

                console.log('[LineOA] subscribeRealtime ตั้งค่าเรียบร้อย');

                this.unsubscribeRealtime = () => {
                    try {
                        window.Echo.leaveChannel(channelName);
                    } catch (err) {
                        // เงียบไว้
                    }
                };
            },

            handleRealtimeIncoming(e) {
                if (!e || !e.conversation_id || !e.message) {
                    return;
                }

                const convId = e.conversation_id;
                const newMsg = e.message;
                const newConvRaw = e.conversation || {};

                // หาใน list ซ้าย
                const idx = this.conversations.findIndex(c => c.id === convId);
                const existing = idx !== -1 ? this.conversations[idx] : null;

                const isActive = this.selectedConversation && this.selectedConversation.id === convId;

                // ===== last_message / last_message_at =====
                const lastMessage =
                    newConvRaw.last_message ??
                    newConvRaw.last_message_preview ??
                    this.buildPreviewFromMessage(newMsg) ??
                    (existing && existing.last_message) ??
                    null;

                const lastMessageAt =
                    newConvRaw.last_message_at ??
                    newMsg.sent_at ??
                    (existing && existing.last_message_at) ??
                    null;

                // ===== unread_count =====
                let unread;
                if (isActive) {
                    // ถ้ากำลังเปิดห้องนี้อยู่ ถือว่าอ่านข้อความใหม่ทันที
                    unread = 0;
                } else if (newConvRaw.unread_count != null) {
                    unread = newConvRaw.unread_count;
                } else {
                    const oldUnread = existing && existing.unread_count ? existing.unread_count : 0;
                    unread = oldUnread + 1;
                }

                // merge ข้อมูลห้อง
                const mergedConv = Object.assign(
                    {},
                    existing || {},
                    newConvRaw,
                    {
                        last_message: lastMessage,
                        last_message_at: lastMessageAt,
                        unread_count: unread,
                    }
                );

                // อัปเดต list ซ้าย
                if (idx !== -1) {
                    this.$set(this.conversations, idx, mergedConv);
                } else if (this.filters.status === 'open') {
                    this.conversations.unshift(mergedConv);
                    this.pagination.total += 1;
                }

                // ถ้ามีเปิดห้องนี้อยู่ → เพิ่มข้อความ + sync selectedConversation
                if (isActive) {
                    this.messages.push(newMsg);
                    this.selectedConversation = mergedConv;

                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                }
            },

            handleConversationAssigned(e) {
                if (!e || !e.conversation) return;
                const conv = e.conversation;

                this.updateOrInsertConversation(conv);

                if (this.selectedConversation && this.selectedConversation.id === conv.id) {
                    this.selectedConversation = Object.assign({}, this.selectedConversation, conv);
                }
            },

            handleConversationClosed(e) {
                if (!e || !e.conversation) return;
                const conv = e.conversation;

                this.updateOrInsertConversation(conv);

                if (this.selectedConversation && this.selectedConversation.id === conv.id) {
                    this.selectedConversation = Object.assign({}, this.selectedConversation, conv);
                }
            },

            handleConversationLocked(e) {
                if (!e || !e.conversation) return;
                const conv = e.conversation;

                this.updateOrInsertConversation(conv);

                if (this.selectedConversation && this.selectedConversation.id === conv.id) {
                    this.selectedConversation = Object.assign({}, this.selectedConversation, conv);
                }
            },

            // ====== scope tab: ทั้งหมด / ที่รับเรื่อง ======
            changeScope(scope) {
                if (this.filters.scope === scope) return;
                this.filters.scope = scope;
                this.fetchConversations(1);
            },

            // ====== modal: ผูก contact กับ member ======
            openMemberModal() {
                if (!this.selectedConversation || !this.selectedConversation.contact) {
                    return;
                }

                const contact = this.selectedConversation.contact;
                this.memberModal.error = '';
                this.memberModal.member = null;
                this.memberModal.member_id = contact.member_id || '';

                this.$nextTick(() => {
                    if (this.$refs.memberModal) {
                        this.$refs.memberModal.show();
                    }
                });
            },

            resetMemberModal() {
                this.memberModal = {
                    member_id: '',
                    member: null,
                    loading: false,
                    saving: false,
                    error: '',
                };
            },

            searchMember() {
                if (!this.memberModal.member_id) return;
                this.memberModal.error = '';
                this.memberModal.member = null;
                this.memberModal.loading = true;

                // TODO: ปรับ path ให้ตรงกับ backend จริง
                axios.get(this.apiUrl('members/find'), {
                    params: {
                        member_id: this.memberModal.member_id,
                    }
                }).then(res => {
                    const data = res.data || {};
                    const member = data.data || data.member || null;

                    if (!member) {
                        this.memberModal.error = 'ไม่พบสมาชิกตาม Member ID ที่ระบุ';
                        return;
                    }

                    this.memberModal.member = {
                        name: member.name || member.full_name || '',
                        username: member.username || member.user || '',
                        mobile: member.mobile || member.tel || '',
                        id: member.id || member.code || this.memberModal.member_id,
                    };
                }).catch(err => {
                    console.error('searchMember error', err);
                    this.memberModal.error = 'ค้นหาสมาชิกไม่สำเร็จ กรุณาลองใหม่';
                }).finally(() => {
                    this.memberModal.loading = false;
                });
            },

            saveMemberLink() {
                if (!this.selectedConversation || !this.selectedConversation.contact) return;
                if (!this.memberModal.member) return;

                const contactId = this.selectedConversation.contact.id;
                const member = this.memberModal.member;

                this.memberModal.saving = true;

                // TODO: ปรับ path ให้ตรงกับ backend จริง
                axios.post(this.apiUrl('contacts/' + contactId + '/attach-member'), {
                    member_id: member.id,
                }).then(res => {
                    const data = res.data || {};
                    const contact = data.data || data.contact || null;

                    // ถ้า backend ส่ง contact กลับมา ใช้ค่าตรงนั้น
                    if (contact) {
                        this.selectedConversation.contact = contact;
                    } else {
                        // ถ้าไม่ ก็อัปเดตเองจาก member ที่หาได้
                        const c = this.selectedConversation.contact;
                        c.member_id = member.id;
                        c.member_username = member.username || c.member_username;
                        c.member_mobile = member.mobile || c.member_mobile;
                        this.selectedConversation.contact = Object.assign({}, c);
                    }

                    // sync กับ list ซ้าย
                    const idx = this.conversations.findIndex(c => c.id === this.selectedConversation.id);
                    if (idx !== -1) {
                        const merged = Object.assign({}, this.conversations[idx], {
                            contact: this.selectedConversation.contact,
                        });
                        this.$set(this.conversations, idx, merged);
                    }

                    if (this.$refs.memberModal) {
                        this.$refs.memberModal.hide();
                    }
                }).catch(err => {
                    console.error('saveMemberLink error', err);
                    this.memberModal.error = 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
                }).finally(() => {
                    this.memberModal.saving = false;
                });
            },

            // ====== รับเรื่อง / ปิดเคส ======
            updateConversationLocal(conv) {
                if (!conv || !conv.id) return;

                if (this.selectedConversation && this.selectedConversation.id === conv.id) {
                    this.selectedConversation = Object.assign({}, this.selectedConversation, conv);
                }

                const idx = this.conversations.findIndex(c => c.id === conv.id);
                if (idx !== -1) {
                    const merged = Object.assign({}, this.conversations[idx], conv);
                    this.$set(this.conversations, idx, merged);
                }
            },
            acceptConversation() {
                if (!this.selectedConversation) return;

                const id = this.selectedConversation.id;

                axios.post(this.apiUrl('conversations/' + id + '/accept'))
                    .then(res => {
                        const conv = res.data.data || res.data.conversation || null;
                        if (conv) {
                            this.selectedConversation = conv;
                            // อัปเดตใน list ซ้ายด้วย (ใช้ method เดิมของโบ๊ท)
                            this.updateConversationLocal(conv);
                        }
                    })
                    .catch(err => {
                        console.error('acceptConversation error', err);
                        const msg = err.response?.data?.message || 'รับเรื่องไม่สำเร็จ';
                        alert(msg);
                    });
            },
            acceptConversation_() {
                if (!this.selectedConversation) return;

                const id = this.selectedConversation.id;

                // TODO: ปรับ path ให้ตรงกับ backend จริง
                axios.post(this.apiUrl('conversations/' + id + '/accept'))
                    .then(res => {
                        const data = res.data || {};
                        const conv = data.data || data.conversation || null;
                        if (conv) {
                            this.updateConversationLocal(conv);
                        } else {
                            // กันกรณี backend ไม่ส่ง object กลับมา
                            this.selectedConversation.assigned_employee_name = this.selectedConversation.assigned_employee_name || 'คุณ';
                            this.updateConversationLocal(this.selectedConversation);
                        }
                    })
                    .catch(err => {
                        console.error('acceptConversation error', err);
                        alert('ไม่สามารถรับเรื่องได้ กรุณาลองใหม่');
                    });
            },
            lockConversation(conv) {
                if (!conv || !conv.id) return;

                return axios.post(this.apiUrl('conversations/' + conv.id + '/lock'))
                    .then(res => {
                        const convNew = res.data.data || res.data.conversation || null;
                        if (convNew) {
                            this.updateConversationLocal(convNew);
                        }
                    })
                    .catch(err => {
                        console.error('lockConversation error', err);
                        const msg = err.response?.data?.message || 'ไม่สามารถล็อกห้องได้';
                        alert(msg);
                    });
            },

            unlockConversation(conv) {
                if (!conv || !conv.id) return;

                return axios.post(this.apiUrl('conversations/' + conv.id + '/unlock'))
                    .then(res => {
                        const convNew = res.data.data || res.data.conversation || null;
                        if (convNew) {
                            this.updateConversationLocal(convNew);
                        }
                    })
                    .catch(err => {
                        console.error('unlockConversation error', err);
                    });
            },
            closeConversation() {
                if (!this.selectedConversation) return;

                const id = this.selectedConversation.id;

                if (!confirm('ยืนยันปิดเคสนี้?')) {
                    return;
                }

                axios.post(this.apiUrl('conversations/' + id + '/close'))
                    .then(res => {
                        const conv = res.data.data || null;
                        if (conv) {
                            this.selectedConversation = conv;

                            // sync list ซ้ายด้วย
                            this.updateConversationLocal(conv);
                        }
                    })
                    .catch(err => {
                        const msg = err.response?.data?.message || 'ปิดเคสไม่สำเร็จ';
                        alert(msg);
                    });
            },

            closeConversation_() {
                if (!this.selectedConversation) return;

                const id = this.selectedConversation.id;

                if (!confirm('ต้องการปิดเคสห้องแชตนี้หรือไม่?')) {
                    return;
                }

                // TODO: ปรับ path ให้ตรงกับ backend จริง
                axios.post(this.apiUrl('conversations/' + id + '/close'))
                    .then(res => {
                        const data = res.data || {};
                        const conv = data.data || data.conversation || null;
                        if (conv) {
                            this.updateConversationLocal(conv);
                        } else {
                            this.selectedConversation.status = 'closed';
                            this.updateConversationLocal(this.selectedConversation);
                        }
                    })
                    .catch(err => {
                        console.error('closeConversation error', err);
                        alert('ไม่สามารถปิดเคสได้ กรุณาลองใหม่');
                    });
            },

        }
    });
</script>