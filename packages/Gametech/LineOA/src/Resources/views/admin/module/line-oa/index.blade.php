@extends('admin::layouts.line-oa')

{{-- page title --}}
@section('title')
    {{ $menu->currentName }}
@endsection

@section('css')
    @include('admin::layouts.datatables_css')
@endsection

@include('admin::module.line-oa.css')

@php
    $registerMode = config('website.register.mode', 'phone'); // phone หรือ username
    $regPhoneConfig = config('website.register.phone');
    $stickerPacks = config('line_oa_stickers.packs', []);
@endphp
@section('content')
    <section class="content text-xs">
        <div class="card">
            <div class="card-body">
                <div id="line-oa-chat-app" class="line-chat-font">
                    <line-oa-chat ref="lineOaChat"
                                  :register-mode="'{{ $registerMode }}'"
                                  :phone-config='@json($regPhoneConfig)'
                                  :sticker-packs='@json($stickerPacks)'
                    >
                    </line-oa-chat>
                </div>
                @include('admin::module.line-oa.member-edt-app')
                @include('admin::module.line-oa.member-refill-app')

            </div>
        </div>
    </section>

@endsection


@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"
            integrity="sha512-U2WE1ktpMTuRBPoCFDzomoIorbOyUv0sP8B+INA3EzNAhehbzED1rOJg6bCqPf/Tuposxb5ja/MAUnC8THSbLQ=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>


    <script>
        (function () {
            $('body').addClass('sidebar-collapse');

            function findAnyVueRoot() {
                // 1) พยายามหา element ที่มี __vue__ โดย scanning ทั่วหน้า (เริ่มจาก body)
                var all = document.querySelectorAll('body, body *');
                for (var i = 0; i < all.length; i++) {
                    if (all[i].__vue__) {
                        return all[i].__vue__;
                    }
                }
                console.warn('ไม่พบ Vue root instance เลย');
                return null;
            }

            function findLineOaChatVm(vm) {
                if (!vm) return null;

                // ถ้าตัวนี้คือ line-oa-chat เอง
                var name = vm.$options && (vm.$options.name || vm.$options._componentTag);
                if (name === 'line-oa-chat') {
                    return vm;
                }

                // ลองไล่ children
                if (vm.$children && vm.$children.length) {
                    for (var i = 0; i < vm.$children.length; i++) {
                        var found = findLineOaChatVm(vm.$children[i]);
                        if (found) return found;
                    }
                }

                return null;
            }

            function getLineOaChatComponent() {
                var rootVm = findAnyVueRoot();
                if (!rootVm) {
                    console.warn('ยังหา Vue root ไม่เจอ');
                    return null;
                }

                // ถ้ามี ref แบบ lineOaChat ก็ลองก่อน
                if (rootVm.$refs && rootVm.$refs.lineOaChat) {
                    return rootVm.$refs.lineOaChat;
                }

                var comp = findLineOaChatVm(rootVm);
                if (!comp) {
                    console.warn('ไม่พบ component line-oa-chat จาก Vue tree');
                }
                return comp;
            }

            window.editModal = function (code) {
                if (!window.memberRefillApp) {
                    console.warn('memberRefillApp ยังไม่พร้อมใช้งาน');
                    return;
                }

                // 1) หา line-oa-chat เพื่อตรวจว่ามีห้องไหนถูกเลือกอยู่
                var comp = getLineOaChatComponent();
                var prefill = null;

                if (comp && comp.selectedConversation && comp.selectedConversation.contact) {
                    var c = comp.selectedConversation.contact;

                    // สมมติ structure: contact.member_id / contact.member_user
                    prefill = {
                        member_id: c.member_id || null,
                        member_username: c.member_username || null,
                    };
                }

                // 2) ส่ง topupId + prefill (ถ้ามี) เข้า memberRefillApp
                window.memberRefillApp.openAssignTopupTargetModal(code, prefill);
            };

            window.addModal = function () {
                if (!window.memberRefillApp) {
                    console.warn('memberRefillApp ยังไม่พร้อมใช้งาน');
                    return;
                }

                // addModal = เลือก target ใหม่ ไม่ผูกบิล (code = null)
                window.memberRefillApp.openAssignTopupTargetModal(null);
            };

            window.refill = function () {
                if (!window.memberRefillApp) {
                    console.warn('memberRefillApp ยังไม่พร้อมใช้งาน');
                    return;
                }

                // 1) พยายามหา line-oa-chat component
                var comp = getLineOaChatComponent();
                var prefill = null;

                if (comp && comp.selectedConversation && comp.selectedConversation.contact) {
                    var c = comp.selectedConversation.contact;

                    prefill = {
                        member_id: c.member_id || null,
                        member_username: c.member_username || null,
                    };
                }

                // 2) เรียก refillModal โดยส่ง prefill (ถ้าไม่มีจะเป็น null)
                window.memberRefillApp.openRefillModal(prefill);
            };

            window.clearModal = function (code) {
                if (!window.memberRefillApp) {
                    console.warn('memberRefillApp ยังไม่พร้อมใช้งาน');
                    return;
                }

                // clear = modal ระบุหมายเหตุ
                window.memberRefillApp.openClearRemarkModal(code);
            };

// เผื่อมีปุ่ม delete
            window.delModal = function (code) {
                if (!window.memberRefillApp) {
                    console.warn('memberRefillApp ยังไม่พร้อมใช้งาน');
                    return;
                }

                // ถ้าต้องสร้าง modal ลบ ให้ map จากตรงนี้ได้
                if (typeof window.memberRefillApp.openDeleteModal === 'function') {
                    window.memberRefillApp.openDeleteModal(code);
                } else {
                    console.warn('memberRefillApp ไม่มี method openDeleteModal');
                }
            };

            // สร้าง global helper สำหรับให้ DataTables เรียกใช้
            window.LineOaChatActions = {
                edit: function (code) {
                    var comp = getLineOaChatComponent();
                    if (!comp || typeof comp.editModal !== 'function') {
                        console.warn('editModal() ไม่พร้อมใช้งานบน line-oa-chat');
                        return;
                    }
                    comp.editModal(code);
                },
                approve: function (code) {
                    var comp = getLineOaChatComponent();
                    if (!comp || typeof comp.approveModal !== 'function') {
                        console.warn('approveModal() ไม่พร้อมใช้งานบน line-oa-chat');
                        return;
                    }
                    comp.approveModal(code);
                },
                cancel: function (code) {
                    var comp = getLineOaChatComponent();
                    if (!comp || typeof comp.clearModal !== 'function') {
                        console.warn('clearModal() ไม่พร้อมใช้งานบน line-oa-chat');
                        return;
                    }
                    comp.clearModal(code);
                },
                delete: function (code) {
                    var comp = getLineOaChatComponent();
                    if (!comp || typeof comp.delModal !== 'function') {
                        console.warn('delModal() ไม่พร้อมใช้งานบน line-oa-chat');
                        return;
                    }
                    comp.delModal(code);
                }
            };
        })();
    </script>
    <script>
        window.LineDefaultAvatar = "{{ asset('storage/img/'.$webconfig->logo) }}";
        window.LineOAEventsChannel = "{{ config('app.name') }}_events";
        window.LineOAEmployee = {
            id: '{{ auth('admin')->user()->code ?? '' }}',
            name: '{{ auth('admin')->user()->user_name ?? '' }}',
        };

    </script>


    <script type="text/x-template" id="line-oa-chat-template">
        <div class="line-oa-chat-page">
            <b-container fluid class="px-0 h-100">
                <b-row no-gutters class="h-100 align-items-stretch">
                    {{-- ====== LEFT: CONVERSATION LIST ====== --}}
                    <b-col cols="12"
                           md="3"
                           class="border-right line-oa-col line-oa-sidebar">
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
                                            <b-badge variant="primary" v-if="filters.status === 'open'">ทั้งหมด
                                            </b-badge>
                                            <b-badge variant="info" v-else-if="filters.status === 'assigned'">ดำเนินการ
                                            </b-badge>
                                            <b-badge variant="secondary" v-else>เสร็จสิ้น</b-badge>
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
                                        ที่รับผิดชอบ
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
                                            {{--                                            @change="fetchConversations(1,{ silent : true})"--}}
                                    ></b-form-select>

                                    <b-form-select
                                            v-model="filters.account_id"
                                            :options="accountOptions"
                                            size="sm"
                                            {{--                                            @change="fetchConversations(1,{ silent : true})"--}}
                                    >
                                        <template #first>
                                            <option :value="null">ทุก OA</option>
                                        </template>
                                    </b-form-select>
                                </div>
                            </div>

                            {{-- LIST --}}
                            <div
                                    class="flex-fill overflow-auto"
                                    ref="conversationList"
                                    @scroll="onConversationListScroll">

                                <!-- loading รอบแรก -->
                                <div v-if="loadingList && !conversations.length" class="text-center my-3 text-muted">
                                    <b-spinner small class="mr-1"></b-spinner>
                                    กำลังโหลดห้องแชต...
                                </div>

                                <!-- ไม่มีรายการ -->
                                <div v-else-if="!conversations.length" class="text-center text-muted my-3 small">
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
                                                        v-on:error="onProfileImageError"
                                                        class="rounded-circle"
                                                        style="width: 40px; height: 40px; object-fit: cover;"
                                                >
                                                <div v-else
                                                     class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                                     style="width: 40px; height: 40px;">
                                                    <i class="far fa-user"></i>
                                                </div>
                                            </div>

                                            <!-- ปุ่มปักหมุด -->
                                            <button
                                                    type="button"
                                                    class="btn btn-link btn-sm p-0 ml-2"
                                                    @click.stop="togglePinConversation(conv)"
                                                    v-b-tooltip.hover
                                                    :title="conv.is_pinned ? 'เลิกปักหมุดห้องนี้' : 'ปักหมุดห้องนี้'"
                                            >
                                                <i
                                                        :class="conv.is_pinned
                ? 'fa fa-star text-warning'
                : 'far fa-star text-muted'"
                                                ></i>
                                            </button>

                                            <div class="flex-fill">
                                                <div class="d-flex justify-content-between">
                                                    <strong>
                                                        @{{ (conv.contact && (conv.contact.display_name ||
                                                        conv.contact.member_username)) || 'ไม่ทราบชื่อ' }}
                                                    </strong>
                                                    <small class="text-muted" v-if="conv.last_message_at">
                                                        @{{ formatMessageDate(conv.last_message_at) }}
                                                    </small>
                                                </div>
                                                <!-- แสดงสถานะสมัครกับบอท -->
                                                <div v-if="conv.is_registering" class="mt-1">
                                                    <b-badge variant="warning" class="text-dark oa-reg-badge">
                                                        <i class="fa fa-robot"></i>
                                                        กำลังสมัครสมาชิกกับบอท
                                                    </b-badge>
                                                </div>
                                                <div class="text-muted no-x-scroll text-truncate fixed-line">
                                            <span v-if="conv.line_account && conv.line_account.name">
                                                [@{{ conv.line_account.name }}]
                                            </span>
                                                    <span v-if="conv.typing && conv.typing.employee_name">
    @{{ conv.typing.employee_name }} กำลังพิมพ์...
  </span>
                                                    <span v-else>
    @{{ conv.last_message }}
  </span>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center mt-1">
                                                    <div>
                                                        <p class="text-muted d-block mb-1">
                                                            ยูส: @{{ conv.contact && conv.contact.member_username || '-'
                                                            }}
                                                        </p>

                                                        {{-- แสดงชื่อคนปิด + เวลา ถ้าห้องปิดแล้ว --}}
                                                        {{--                                                        <div--}}
                                                        {{--                                                                v-if="conv.status === 'closed'"--}}
                                                        {{--                                                                class="text-muted small"--}}
                                                        {{--                                                        >--}}
                                                        {{--                                                            ปิดโดย @{{ conv.closed_by_employee_name || 'พนักงาน' }}--}}
                                                        {{--                                                            <span v-if="conv.closed_at">--}}
                                                        {{--                                                        เมื่อ @{{ formatDateTime(conv.closed_at) }}--}}
                                                        {{--                                                    </span>--}}
                                                        {{--                                                        </div>--}}
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <b-badge
                                                                v-if="conv.status === 'assigned'"
                                                                variant="info"
                                                                class="mr-1"
                                                        >
                                                            ดำเนินการ
                                                        </b-badge>
                                                        <b-badge
                                                                v-if="conv.status === 'closed'"
                                                                variant="success"
                                                                class="mr-1"
                                                        >
                                                            เสร็จสิ้น
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

                            <!-- loading เพิ่มตอนเลื่อนลง -->
                            <div v-if="loadingMore" class="text-center my-2 text-muted small">
                                <b-spinner small class="mr-1"></b-spinner>
                                กำลังโหลดเพิ่ม...
                            </div>

                            <!-- ข้อความท้ายสุดเมื่อโหลดครบทุกหน้าแล้ว -->
                            <div
                                    v-if="!loadingMore && !loadingList && pagination.current_page >= pagination.last_page && conversations.length"
                                    class="text-center text-muted tiny my-2"
                            >
                                แสดงครบ @{{ conversations.length }} ห้องแล้ว
                            </div>
                        </div>
                    </b-col>


                    {{-- ====== MIDDLE: CHAT WINDOW ====== --}}
                    <b-col cols="12" md="6" class="chat-middle-col line-oa-col">

                        <div class="d-flex flex-column h-85">

                            <div class="p-2 border-bottom bg-light" v-if="selectedConversation">
                                <div class="d-flex align-items-center">
                                    <div class="mr-2"
                                         v-if="selectedConversation.contact"
                                         @click="openMemberModal"
                                         style="cursor: pointer;">
                                        <img
                                                v-if="selectedConversation.contact.picture_url"
                                                :src="selectedConversation.contact.picture_url"
                                                v-on:error="onProfileImageError"
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
                                            <h3 class="mb-0">
                            <span class="text-primary">
                                @{{ (selectedConversation.contact &&
                                (selectedConversation.contact.display_name ||
                                selectedConversation.contact.member_username)) || 'ไม่ทราบชื่อ' }}
                            </span>
                                                <b-button
                                                        size="sm"
                                                        class="note-icon-btn btn-icon"
                                                        :disabled="!selectedConversation"
                                                        @click="oNOffSound"
                                                        v-b-tooltip.hover
                                                        :title="selectedConversation && isConversationMuted(selectedConversation.id)
                                    ? 'เปิดเสียงห้องนี้'
                                    : 'ปิดเสียงห้องนี้'"
                                                >
                                                    <i
                                                            :class="[
                                        'fa',
                                        selectedConversation && isConversationMuted(selectedConversation.id)
                                            ? 'fa-volume-mute text-muted'
                                            : 'fa-volume-up'
                                    ]"
                                                    ></i>
                                                </b-button>
                                            </h3>
                                        </div>
                                        <div class="text-muted small mt-1" v-if="selectedConversation.line_account">
                                            OA: @{{ selectedConversation.line_account.name }}
                                        </div>
                                    </div>
                                    <div class="text-right ml-3">
                                        <div class="d-flex justify-content-end flex-wrap">
                                            <b-button
                                                    v-if="selectedConversation.status === 'open'"
                                                    size="sm"
                                                    variant="outline-primary"
                                                    class="mr-1 mb-1 btn-app"
                                                    @click="acceptConversation"
                                            >
                                                <i class="fa fa-list-check"></i>
                                                ต้องดำเนินการ
                                            </b-button>
                                            <b-button
                                                    v-if="selectedConversation.status !== 'closed'"
                                                    size="sm"
                                                    variant="outline-danger"
                                                    class="mr-1 mb-1 btn-app"
                                                    @click="closeConversation"
                                            >
                                                <i class="fa fa-check-circle"></i>
                                                ดำเนินการแล้ว
                                            </b-button>
                                        </div>

                                        <div
                                                class="mt-1 small text-muted"
                                                v-if="selectedConversation.status === 'closed' && selectedConversation.closed_at"
                                        >
                                            เสร็จสิ้น @{{ formatChatDateTime(selectedConversation.closed_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-2 border-bottom bg-light text-muted text-center" v-else>
                                เลือกห้องแชตจากด้านซ้ายเพื่อเริ่มสนทนา
                            </div>

                            {{-- WRAPPER: การ์ดปักหมุดลอย + message list --}}
                            <div class="flex-fill position-relative chat-message-wrapper">

                                {{-- การ์ดปักหมุดลอย --}}
                                <div
                                        v-if="selectedConversation && primaryPinned && !hidePinnedPanel"
                                        class="chat-pinned-overlay"
                                >
                                    <div class="chat-pinned-card">
                                        <div
                                                class="d-flex align-items-center chat-pinned-main"
                                                @click="scrollToMessage(primaryPinned.id)"
                                        >
                                            <i class="fa fa-thumbtack mr-2 text-muted chat-pinned-icon"></i>

                                            <div class="flex-fill">
                                                <div class="chat-pinned-text text-truncate">
                                                    @{{ buildPinnedPreviewText(primaryPinned) }}
                                                </div>
                                                <div class="chat-pinned-meta text-muted">
                                                    @{{ pinnedSenderLabel(primaryPinned) }}
                                                    <span v-if="primaryPinned.sent_at">
                                    • @{{ formatChatTime(primaryPinned.sent_at) }}
                                </span>
                                                </div>
                                            </div>

                                            <b-button
                                                    variant="link"
                                                    size="sm"
                                                    class="p-0 chat-pinned-toggle"
                                                    @click.stop="pinnedPanelExpanded = !pinnedPanelExpanded"
                                            >
                                                <i :class="pinnedPanelExpanded ? 'fa fa-chevron-up' : 'fa fa-chevron-down'"></i>
                                            </b-button>
                                        </div>

                                        <transition name="fade">
                                            <div
                                                    v-if="pinnedPanelExpanded && otherPinnedMessages.length"
                                                    class="chat-pinned-list"
                                            >
                                                <div
                                                        v-for="pm in otherPinnedMessages"
                                                        :key="'pinned-more-' + pm.id"
                                                        class="chat-pinned-item"
                                                        @click="scrollToMessage(pm.id)"
                                                >
                                                    <i class="fa fa-thumbtack mr-2 text-muted chat-pinned-icon"></i>
                                                    <div class="flex-fill">
                                                        <div class="chat-pinned-text text-truncate">
                                                            @{{ buildPinnedPreviewText(pm) }}
                                                        </div>
                                                        <div class="chat-pinned-meta text-muted">
                                                            @{{ pinnedSenderLabel(pm) }}
                                                            <span v-if="pm.sent_at">
                                            • @{{ formatChatTime(pm.sent_at) }}
                                        </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div
                                                        class="chat-pinned-footer"
                                                        @click="hidePinnedPanel = true"
                                                >
                                                    ไม่แสดงอีก
                                                </div>
                                            </div>
                                        </transition>
                                    </div>
                                </div>

                                {{-- MESSAGE LIST --}}
                                <div class="overflow-auto px-2 py-2 chat-message-list" ref="messageContainer">

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

                                            <div v-for="item in messagesWithSeparators"
                                                 :key="item.kind === 'date' ? ('d-' + item.dateKey) : ('m-' + item.message.id)"
                                                 class="mb-2">

                                                <div v-if="item.kind === 'date'"
                                                     class="chat-day-separator text-center my-2">
                                <span class="badge badge-light px-3 py-1">
                                    @{{ formatChatDay(item.date) }}
                                </span>
                                                </div>

                                                <div v-else
                                                     :data-msg-id="item.message.id"
                                                     :class="[
                                    'chat-msg-row',
                                    item.message.direction === 'inbound' ? 'chat-msg-in' : 'chat-msg-out'
                                 ]">

                                                    {{-- INBOUND (ลูกค้า) --}}
                                                    <template v-if="item.message.direction === 'inbound'">
                                                        <div class="chat-avatar mr-2">
                                                            <img
                                                                    :src="(selectedConversation.contact && selectedConversation.contact.picture_url) || '/images/default-avatar.png'"
                                                                    class="chat-avatar-img"
                                                                    alt="avatar"
                                                                    v-on:error="onProfileImageError"
                                                            >
                                                        </div>

                                                        <div class="chat-msg-main">
                                                            <div :class="messageBubbleClass(item.message)">

                                                                <div v-if="item.message.is_pinned"
                                                                     class="small text-warning mb-1 d-flex align-items-center">
                                                                    <i class="fa fa-thumbtack mr-1"></i>
                                                                    <span>ปักหมุดแล้ว</span>
                                                                </div>

                                                                <div
                                                                        v-if="item.message.meta && item.message.meta.reply_to"
                                                                        class="chat-reply-preview mb-1"
                                                                >
                                                                    <div class="d-flex align-items-start mb-1">
                                                                        <img
                                                                                v-if="replyToAvatarUrl(item.message)"
                                                                                :src="replyToAvatarUrl(item.message)"
                                                                                class="chat-reply-avatar mr-2"
                                                                                v-on:error="onProfileImageError"
                                                                                alt="avatar"
                                                                        >
                                                                        <div>
                                                                            <div class="chat-reply-name">
                                                                                @{{ replyToDisplayName(item.message) }}
                                                                            </div>
                                                                            <div class="small text-muted">
                                                                                ตอบกลับข้อความก่อนหน้า
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="chat-reply-quote d-flex align-items-center">
                                                                        <template
                                                                                v-if="replyHasPreviewThumb(item.message.meta.reply_to)">
                                                                            <img
                                                                                    :src="replyPreviewThumb(item.message.meta.reply_to)"
                                                                                    class="chat-reply-thumb mr-2"
                                                                                    alt="preview"
                                                                            >

                                                                            <!-- ถ้าไม่ใช่สติ๊กเกอร์ ค่อยแสดงข้อความตามปกติ -->
                                                                            <span
                                                                                    v-if="item.message.meta.reply_to.type !== 'sticker'"
                                                                                    class="text-truncate"
                                                                            >
            @{{ buildReplyPreviewText(item.message.meta.reply_to) }}
        </span>
                                                                        </template>

                                                                        <template v-else>
                                                                            @{{
                                                                            buildReplyPreviewText(item.message.meta.reply_to)
                                                                            }}
                                                                        </template>
                                                                    </div>

                                                                </div>


                                                                <div class="whitespace-pre-wrap">
                                                                    <template v-if="item.message.type === 'text'">
                                                                        <div class="chat-line-original">
                                                        <span v-if="getMessageDisplay(item.message).lang"
                                                              class="text-primary font-weight-bold mr-1">
                                                            [@{{ getMessageDisplay(item.message).lang.toUpperCase() }}]
                                                        </span>
                                                                            <span>@{{ getMessageDisplay(item.message).original }}</span>
                                                                        </div>

                                                                        <div v-if="getMessageDisplay(item.message).translated"
                                                                             class="chat-line-translated text-muted mt-1">
                                                        <span v-if="getMessageDisplay(item.message).target"
                                                              class="text-success font-weight-bold mr-1">
                                                            [@{{ getMessageDisplay(item.message).target.toUpperCase() }}]
                                                        </span>
                                                                            <span>@{{ getMessageDisplay(item.message).translated }}</span>
                                                                        </div>
                                                                    </template>

                                                                    <template
                                                                            v-else-if="item.message.type === 'sticker'">
                                                                        <img
                                                                                :src="stickerUrl(item.message)"
                                                                                class="img-fluid"
                                                                                style="max-width:130px;"
                                                                                alt="[Sticker]"
                                                                        >
                                                                    </template>

                                                                    <template v-else-if="item.message.type === 'image'">
                                                                        <img
                                                                                :src="item.message.payload?.message?.contentUrl || item.message.payload?.message?.previewUrl"
                                                                                class="img-fluid rounded chat-image-thumb"
                                                                                style="max-width: 180px; cursor: zoom-in;"
                                                                                alt="[Image]"
                                                                                @click="openImagePreview(item)"
                                                                                v-on:error="onImageError(item)"
                                                                        >
                                                                    </template>

                                                                    <template v-else-if="item.message.type === 'video'">
                                                                        <video
                                                                                controls
                                                                                class="img-fluid rounded"
                                                                                style="max-width:260px;"
                                                                                :poster="item.message.payload?.message?.previewUrl"
                                                                        >
                                                                            <source :src="item.message.payload?.message?.contentUrl">
                                                                        </video>
                                                                    </template>

                                                                    <template v-else-if="item.message.type === 'audio'">
                                                                        <audio controls
                                                                               :src="item.message.payload?.message?.contentUrl"></audio>
                                                                    </template>

                                                                    <template v-else-if="item.message.type === 'location'
                                                    && item.message.payload
                                                    && item.message.payload.message">
                                                                        <div>
                                                                            <strong>@{{
                                                                                item.message.payload.message.title ||
                                                                                'ตำแหน่ง' }}</strong><br>
                                                                            @{{ item.message.payload.message.address
                                                                            }}<br>
                                                                            <a :href="'https://maps.google.com/?q='
                                                            + item.message.payload.message.latitude
                                                            + ',' + item.message.payload.message.longitude"
                                                                               target="_blank">
                                                                                เปิดแผนที่
                                                                            </a>
                                                                        </div>
                                                                    </template>

                                                                    <template v-else>
                                                                        [@{{ item.message.type }}]
                                                                    </template>
                                                                </div>

                                                                <div class="chat-time-wrapper d-flex align-items-center mt-1">
                                                <span class="chat-msg-time text-muted mr-1">
                                                    @{{ formatChatTime(item.message.sent_at) }}
                                                </span>

                                                                    <b-dropdown
                                                                            right
                                                                            size="sm"
                                                                            variant="link"
                                                                            no-caret
                                                                            toggle-class="p-0 chat-msg-menu-toggle"
                                                                    >
                                                                        <template #button-content>
                                                                            <i class="fa fa-ellipsis-h"></i>
                                                                        </template>

                                                                        <b-dropdown-item
                                                                                @click="startReply(item.message)">
                                                                            ตอบกลับ
                                                                        </b-dropdown-item>

                                                                        <b-dropdown-item v-if="!item.message.is_pinned"
                                                                                         @click="pinMessage(item.message)">
                                                                            ปักหมุด
                                                                        </b-dropdown-item>

                                                                        <b-dropdown-item v-if="item.message.is_pinned"
                                                                                         @click="unpinMessage(item.message)">
                                                                            เลิกปักหมุด
                                                                        </b-dropdown-item>
                                                                    </b-dropdown>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    {{-- OUTBOUND (พนักงาน) --}}
                                                    <template v-else>
                                                        <div class="chat-msg-main">
                                                            <div :class="messageBubbleClass(item.message)">

                                                                <div v-if="item.message.is_pinned"
                                                                     class="small text-warning mb-1 d-flex align-items-center">
                                                                    <i class="fa fa-thumbtack mr-1"></i>
                                                                    <span>ปักหมุดแล้ว</span>
                                                                </div>

                                                                <div
                                                                        v-if="item.message.meta && item.message.meta.reply_to"
                                                                        class="chat-reply-preview mb-1"
                                                                >
                                                                    <div class="d-flex align-items-start mb-1">
                                                                        <img
                                                                                v-if="replyToAvatarUrl(item.message)"
                                                                                :src="replyToAvatarUrl(item.message)"
                                                                                class="chat-reply-avatar mr-2"
                                                                                alt="avatar"
                                                                        >
                                                                        <div>
                                                                            <div class="chat-reply-name">
                                                                                @{{ replyToDisplayName(item.message) }}
                                                                            </div>
                                                                            <div class="small text-muted">
                                                                                ตอบกลับข้อความก่อนหน้า
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="chat-reply-quote d-flex align-items-center">
                                                                        <template
                                                                                v-if="replyHasPreviewThumb(item.message.meta.reply_to)">
                                                                            <img
                                                                                    :src="replyPreviewThumb(item.message.meta.reply_to)"
                                                                                    class="chat-reply-thumb mr-2"
                                                                                    alt="preview"
                                                                            >

                                                                            <!-- ถ้าไม่ใช่สติ๊กเกอร์ ค่อยแสดงข้อความตามปกติ -->
                                                                            <span
                                                                                    v-if="item.message.meta.reply_to.type !== 'sticker'"
                                                                                    class="text-truncate"
                                                                            >
            @{{ buildReplyPreviewText(item.message.meta.reply_to) }}
        </span>
                                                                        </template>

                                                                        <template v-else>
                                                                            @{{
                                                                            buildReplyPreviewText(item.message.meta.reply_to)
                                                                            }}
                                                                        </template>
                                                                    </div>

                                                                </div>


                                                                <div class="small" v-if="item.message.source === 'bot'">
                                                                    <strong>บอท</strong>
                                                                </div>

                                                                <div class="small"
                                                                     v-if="item.message.meta && item.message.meta.employee_name">
                                                                    <strong>@{{ item.message.meta.employee_name
                                                                        }}</strong>
                                                                </div>

                                                                <div class="whitespace-pre-wrap">
                                                                    <template v-if="item.message.type === 'text'">
                                                                        <div class="chat-line-original">
                                                        <span v-if="getMessageDisplay(item.message).lang"
                                                              class="text-primary font-weight-bold mr-1">
                                                            [@{{ getMessageDisplay(item.message).lang.toUpperCase() }}]
                                                        </span>
                                                                            <span>@{{ getMessageDisplay(item.message).original }}</span>
                                                                        </div>

                                                                        <div v-if="getMessageDisplay(item.message).translated"
                                                                             class="chat-line-translated text-muted mt-1">
                                                        <span v-if="getMessageDisplay(item.message).target"
                                                              class="text-success font-weight-bold mr-1">
                                                            [@{{ getMessageDisplay(item.message).target.toUpperCase() }}]
                                                        </span>
                                                                            <span>@{{ getMessageDisplay(item.message).translated }}</span>
                                                                        </div>
                                                                    </template>

                                                                    <template
                                                                            v-else-if="item.message.type === 'sticker'">
                                                                        <img
                                                                                :src="stickerUrl(item.message)"
                                                                                class="img-fluid"
                                                                                style="max-width:130px;"
                                                                                alt="[Sticker]"
                                                                        >
                                                                    </template>

                                                                    <template v-else-if="item.message.type === 'image'">
                                                                        <img
                                                                                :src="item.message.payload?.message?.contentUrl || item.message.payload?.message?.previewUrl"
                                                                                class="img-fluid rounded chat-image-thumb"
                                                                                style="max-width: 180px; cursor: zoom-in;"
                                                                                alt="[Image]"
                                                                                @click="openImagePreview(item)"
                                                                                v-on:error="onImageError(item)"
                                                                        >
                                                                    </template>

                                                                    <template v-else-if="item.message.type === 'video'">
                                                                        <video
                                                                                controls
                                                                                class="img-fluid rounded"
                                                                                style="max-width:260px;"
                                                                                :poster="item.message.payload?.message?.previewUrl"
                                                                        >
                                                                            <source :src="item.message.payload?.message?.contentUrl">
                                                                        </video>
                                                                    </template>

                                                                    <template v-else-if="item.message.type === 'audio'">
                                                                        <audio controls
                                                                               :src="item.message.payload?.message?.contentUrl"></audio>
                                                                    </template>

                                                                    <template v-else-if="item.message.type === 'location'
                                                    && item.message.payload
                                                    && item.message.payload.message">
                                                                        <div>
                                                                            <strong>@{{
                                                                                item.message.payload.message.title ||
                                                                                'ตำแหน่ง' }}</strong><br>
                                                                            @{{ item.message.payload.message.address
                                                                            }}<br>
                                                                            <a :href="'https://maps.google.com/?q='
                                                            + item.message.payload.message.latitude
                                                            + ',' + item.message.payload.message.longitude"
                                                                               target="_blank">
                                                                                เปิดแผนที่
                                                                            </a>
                                                                        </div>
                                                                    </template>

                                                                    <template v-else>
                                                                        [@{{ item.message.type }}]
                                                                    </template>
                                                                </div>

                                                                <div class="chat-time-wrapper d-flex align-items-center mt-1">
                                                <span class="chat-msg-time text-muted mr-1">
                                                    @{{ formatChatTime(item.message.sent_at) }}
                                                </span>

                                                                    <b-dropdown
                                                                            right
                                                                            size="sm"
                                                                            variant="link"
                                                                            no-caret
                                                                            toggle-class="p-0 chat-msg-menu-toggle"
                                                                    >
                                                                        <template #button-content>
                                                                            <i class="fa fa-ellipsis-h"></i>
                                                                        </template>

                                                                        <b-dropdown-item
                                                                                @click="startReply(item.message)">
                                                                            ตอบกลับ
                                                                        </b-dropdown-item>

                                                                        <b-dropdown-item
                                                                                @click="pinMessage(item.message)">
                                                                            ปักหมุด
                                                                        </b-dropdown-item>

                                                                        <b-dropdown-item v-if="item.message.is_pinned"
                                                                                         @click="unpinMessage(item.message)">
                                                                            เลิกปักหมุด
                                                                        </b-dropdown-item>
                                                                    </b-dropdown>
                                                                </div>

                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                            </div>

                            {{-- REPLY BOX --}}
                            <div class="border-top p-2 bg-white" v-if="selectedConversation">

                                <div
                                        v-if="replyingToMessage"
                                        class="border-left border-primary pl-2 pr-2 py-1 mb-1 small d-flex justify-content-between align-items-start"
                                >
                                    <div class="mr-2">
                                        <div class="text-muted">
                                            ตอบกลับ:
                                        </div>
                                        <div class="font-italic text-truncate" style="max-width: 260px;">
                                            @{{ replyingToMessage.text || '[ข้อความเดิม]' }}
                                        </div>
                                    </div>
                                    <b-button
                                            variant="link"
                                            size="sm"
                                            class="p-0 text-muted"
                                            @click="cancelReply"
                                    >
                                        ยกเลิก
                                    </b-button>
                                </div>
                                <div class="chat-reply-wrapper">
                                    <!-- EMOJI PICKER: ลอยทับเหนือกล่องพิมพ์ -->
                                    <div
                                            v-if="showEmojiPicker"
                                            class="emoji-overlay-chat"
                                            ref="emojiPopup"
                                    >
                                        <emoji-picker
                                                :data="emojiIndex"
                                                :show-preview="false"
                                                :show-skin-tones="false"
                                                :emoji-size="20"
                                                :per-line="8"
                                                @select="onEmojiSelect"
                                        />
                                    </div>
                                    <div v-if="uploading" class="text-muted mt-1">
                                        กำลังอัปโหลดรูป...
                                    </div>
                                    <!-- REPLY BOX -->
                                    <b-form-textarea
                                            ref="replyBox"
                                            v-model="replyText"
                                            rows="1"
                                            max-rows="4"
                                            class="no-resize chat-reply-textarea"
                                            placeholder="Enter: ส่ง, Shift + Enter: ขึ้นบรรทัดใหม่..."
                                            :disabled="!canReply"
                                            @input="onReplyInput"
                                            @paste="onPaste"
                                            @dragover.prevent
                                            @drop.prevent="onDrop"
                                            @keydown.enter.exact.prevent="canReply && sendReply()"
                                    ></b-form-textarea>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="d-flex align-items-center">
                                        <b-button
                                                size="sm"
                                                variant="link"
                                                class="chat-tool-btn px-1"
                                                :disabled="!canReply"
                                                @click="openStickerModal"
                                        >
                                            <i class="far fa-smile"></i>
                                        </b-button>
                                        {{-- ปุ่ม emoji ใหม่ --}}
                                        <b-button
                                                ref="emojiBtn"
                                                size="sm"
                                                variant="link"
                                                class="chat-tool-btn px-1"
                                                :disabled="!canReply"
                                                @click="toggleEmojiPicker"
                                        >
                                            <i class="far fa-grin-alt"></i>
                                        </b-button>


                                        <b-button
                                                size="sm"
                                                variant="link"
                                                class="chat-tool-btn px-1"
                                                :disabled="!canReply"
                                                @click="$refs.imageInput.click()"
                                        >
                                            <i class="fa fa-paperclip"></i>
                                        </b-button>

                                        <b-button
                                                size="sm"
                                                variant="link"
                                                class="chat-tool-btn px-1"
                                                :disabled="!canReply"
                                                @click="openQuickReplyModal"
                                        >
                                            <i class="fas fa-comment-dots"></i>
                                        </b-button>
                                    </div>

                                    <div>
                                        <b-button
                                                variant="success"
                                                class="chat-send-btn"
                                                :disabled="!canSendReply || sending"
                                                @click="sendReply"
                                        >
                        <span v-if="sending">
                            <b-spinner small class="mr-1"></b-spinner> กำลังส่ง...
                        </span>
                                            <span v-else>
                            ส่ง
                        </span>
                                        </b-button>
                                    </div>
                                </div>


                                <input
                                        type="file"
                                        ref="imageInput"
                                        class="d-none"
                                        accept="image/*"
                                        @change="onSelectImage"
                                >
                            </div>

                        </div>
                    </b-col>

                    {{-- ====== RIGHT: PROFILE + ACTIONS + NOTES ====== --}}
                    <b-col cols="12" md="3" class="border-left line-oa-col">

                        <div class="d-flex flex-column h-100" v-if="selectedConversation">
                            {{-- PROFILE --}}
                            <div class="border-bottom p-3 text-center">
                                <div class="d-flex justify-content-center">
                                    <img
                                            v-if="selectedConversation.contact && selectedConversation.contact.picture_url"
                                            :src="selectedConversation.contact.picture_url"
                                            v-on:error="onProfileImageError"
                                            class="rounded-circle"
                                            style="width: 64px; height: 64px; object-fit: cover;"
                                    >
                                    <div v-else
                                         class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                         style="width: 64px; height: 64px;">
                                        <i class="far fa-user fa-lg"></i>
                                    </div>
                                </div>
                                <div class="text-center d-flex align-items-center justify-content-center">
                                    <h4 class="mt-2 mb-1 mb-0 mr-1">
                                        @{{ (selectedConversation.contact &&
                                        (selectedConversation.contact.display_name ||
                                        selectedConversation.contact.member_username)) || 'ไม่ทราบชื่อ' }}
                                    </h4>

                                    <a @click="openMemberModal" class="icon-only" style="cursor: pointer;">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                </div>
                                <div class="small text-muted" v-if="selectedConversation.contact.member_username">
                                    ยูส: @{{ selectedConversation.contact &&
                                    selectedConversation.contact.member_username ||
                                    '-' }}
                                </div>
                                <div class="small text-muted" v-if="selectedConversation.contact.member_mobile">
                                    เบอร์: @{{ selectedConversation.contact &&
                                    selectedConversation.contact.member_mobile ||
                                    '-' }}
                                </div>
                            </div>

                            {{-- ACCOUNT DETAIL --}}
                            <div class="border-bottom p-2 small" v-if="selectedConversation.contact.member_id">
                                <div class="mb-1">
                                    <span class="text-muted">ชื่อจริง:</span>
                                    <span class="font-weight-bold">
                                @{{ selectedConversation.contact &&
                                selectedConversation.contact.member_name || '-' }}
                            </span>
                                </div>
                                <div class="mb-1">
                                    <span class="text-muted">ธนาคาร:</span>
                                    <span class="font-weight-bold">
                                @{{ selectedConversation.contact &&
                                selectedConversation.contact.member_bank_name || '-' }}
                            </span>
                                </div>
                                <div>
                                    <span class="text-muted">เลขบัญชี:</span>
                                    <span class="font-weight-bold">
                                @{{ selectedConversation.contact &&
                                selectedConversation.contact.member_acc_no || '-' }}
                            </span>
                                </div>
                            </div>

                            {{-- ACTION BUTTONS --}}
                            <div class="border-bottom p-2">
                                <div class="d-flex flex-wrap gap-2">   <!-- เพิ่ม flex-wrap ให้แตกบรรทัดได้ -->

                                    <div v-if="selectedConversation.is_registering && canControlRegister()">
                                        <b-button
                                                class="btn-app"
                                                variant="outline-danger"
                                                @click="cancelRegisterFlow"
                                        >
                                            <div class="btn-icon-group">
                                                <i class="fa fa-times"></i>
                                                <i class="fa fa-user-plus"></i>
                                            </div>
                                            สมัคร (บอท)
                                        </b-button>
                                    </div>

                                    <div v-else-if="canControlRegister()">
                                        <b-button
                                                variant="success"
                                                @click="openRegisterModal"
                                                class="btn-app"
                                        >
                                            <i class="fa fa-user-plus"></i> สมัคร
                                        </b-button>
                                    </div>

                                    <div v-if="canControlRegister() && selectedConversation.contact.member_id">
                                        <b-button
                                                variant="outline-primary"
                                                @click="openMemberFromConversation"
                                                class="btn-app"
                                        >
                                            <i class="fa fa-user-edit"></i> แก้ไขข้อมูล
                                        </b-button>
                                    </div>

                                    <div v-if="canControlRegister() && selectedConversation.contact.member_id">
                                        <b-button
                                                variant="outline-dark"
                                                @click="openBalanceModal"
                                                class="btn-app"
                                        >
                                            <i class="fa fa-dollar"></i> ดูยอดเงิน
                                        </b-button>
                                    </div>

                                    <div v-if="canControlRegister()">
                                        <b-button
                                                variant="outline-success"
                                                @click="openRefillModal"
                                                class="btn-app"
                                        >
                                            <i class="fa fa-money-check"></i> เพิ่มฝาก
                                        </b-button>
                                    </div>

                                    <div v-if="canControlRegister()">
                                        <b-button
                                                variant="outline-success"
                                                @click="showAdjustSelector"
                                                class="btn-app"
                                        >
                                            <div class="btn-icon-group">
                                                <i class="fa fa-plus"></i>
                                                <i class="fa fa-minus"></i>
                                            </div>
                                            เพิ่ม/ลด
                                        </b-button>
                                    </div>

                                    <div v-if="canControlRegister()">
                                        <b-button
                                                variant="outline-success"
                                                @click="$refs.memberLogModal.show()"
                                                class="btn-app"
                                        >
                                            <i class="fa fa-history"></i> ประวัติ
                                        </b-button>
                                    </div>

                                </div>
                            </div>


                            {{-- STATUS / ASSIGNEE --}}
                            <div class="border-bottom p-2 small">
                                {{--                                <div class="mb-1">--}}
                                {{--                                    <span class="text-muted">สถานะเคส:</span>--}}
                                {{--                                    <span class="font-weight-bold text-uppercase ml-1">--}}
                                {{--                                @{{ selectedConversation.status || '-' }}--}}
                                {{--                            </span>--}}
                                {{--                                </div>--}}
                                <div class="mb-1">
                                    <span class="text-muted">ผู้รับผิดชอบ:</span>
                                    <span class="font-weight-bold ml-1">
                                @{{ selectedConversation.assigned_employee_name || 'ไม่มีผู้รับผิดชอบ' }}
                            </span>
                                    <a @click="openAssigneeModal" class="icon-only" style="cursor: pointer;">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                </div>
                                <div v-if="selectedConversation.closed_at">
                                    <span class="text-muted">เสร็จสิ้น:</span>
                                    <span class="ml-1">
                                @{{ formatChatDateTime(selectedConversation.closed_at) }}
                            </span>
                                </div>
                            </div>

                            {{-- NOTES --}}
                            <div class="flex-fill d-flex flex-column p-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center">
                                        <small class="mb-0 mr-2">โน้ต</small>
                                        <small v-if="notesCount">
                                            @{{ activeNotePosition }}
                                        </small>
                                    </div>

                                    <b-button
                                            size="sm"
                                            class="note-icon-btn btn-icon"
                                            variant="outline-success"
                                            @click="openNoteCreateModal"
                                    >
                                        <i class="fa fa-plus"></i>
                                    </b-button>
                                </div>
                                {{-- ส่วนแสดงโน้ต --}}
                                <div class="flex-fill d-flex flex-column">

                                    <div v-if="notesError" class="text-danger small mb-1">
                                        @{{ notesError }}
                                    </div>

                                    <div class="flex-fill overflow-auto">
                                        <div v-if="notesLoading" class="text-muted text-center my-2">
                                            <b-spinner small class="mr-1"></b-spinner>
                                            กำลังโหลดโน้ต...
                                        </div>

                                        <div v-else-if="!notes.length" class="text-muted small text-center">
                                            ยังไม่มีโน้ตสำหรับเคสนี้
                                        </div>

                                        <div v-else class="d-flex flex-column h-100">

                                            <!-- NOTE CONTENT BOX -->
                                            <div class="note-box mb-2 p-3">
                                                <div class="note-text">
                                                    @{{ activeNote.body || activeNote.text || '' }}
                                                </div>

                                                <!-- footer: writer + date + edit/delete -->
                                                <div class="note-footer d-flex justify-content-between align-items-center mt-2 small text-muted">

                                                    <!-- left: name -->
                                                    <span>
            @{{ activeNote.employee_name || activeNote.created_by_name || 'พนักงาน' }}
        </span>

                                                    <!-- right: date + buttons -->
                                                    <div class="d-flex align-items-center">

            <span v-if="activeNote.created_at" class="mr-2">
                @{{ formatChatDateTime(activeNote.created_at) }}
            </span>

                                                        <b-button
                                                                v-if="activeNote.id"
                                                                size="sm"
                                                                class="note-icon-btn btn-icon"
                                                                @click="openNoteEditModal(activeNote)"
                                                        >
                                                            <i class="fa fa-edit text-muted"></i>
                                                        </b-button>

                                                        <b-button
                                                                v-if="activeNote.id"
                                                                size="sm"
                                                                class="note-icon-btn btn-icon"
                                                                @click="confirmDeleteNote(activeNote)"
                                                        >
                                                            <i class="fa fa-trash text-muted"></i>
                                                        </b-button>
                                                    </div>

                                                </div>
                                            </div>
                                            <!-- ปุ่มสไลด์ซ้าย/ขวา แคบ ๆ ตรงกลางล่าง -->
                                            <div class="mt-2 d-flex justify-content-center align-items-center">
                                                <b-button
                                                        size="sm"
                                                        variant="outline-secondary"
                                                        class="px-2 py-1 mx-1"
                                                        @click="prevNote"
                                                        :disabled="activeNoteIndex <= 0"
                                                >
                                                    <i class="fa fa-chevron-left"></i>
                                                </b-button>

                                                <span class="small text-muted">
                    @{{ activeNotePosition }}
                </span>

                                                <b-button
                                                        size="sm"
                                                        variant="outline-secondary"
                                                        class="px-2 py-1 mx-1"
                                                        @click="nextNote"
                                                        :disabled="activeNoteIndex >= notesCount - 1"
                                                >
                                                    <i class="fa fa-chevron-right"></i>
                                                </b-button>
                                            </div>

                                        </div>
                                    </div>
                                </div>


                            </div>

                        </div>

                        <div v-else class="d-flex h-100 align-items-center justify-content-center text-muted small">
                            เลือกลูกค้าจากด้านซ้ายเพื่อดูรายละเอียด
                        </div>
                    </b-col>

                </b-row>

            </b-container>

            <div
                    v-if="imagePreview.open"
                    class="chat-image-lightbox"
                    @click.self="closeImagePreview"
            >
                <button type="button" class="chat-image-lightbox-close" @click="closeImagePreview">×</button>

                <img
                        :src="imagePreview.url"
                        class="chat-image-lightbox-img"
                        alt="[Preview]"
                        @click.stop
                >
            </div>

            {{-- MODAL: ผูก contact กับ member --}}
            @include('admin::module.line-oa.addon-modal')
        </div>
    </script>


    <script type="module">
        Vue.component('line-oa-chat', {
            template: '#line-oa-chat-template',
            props: {
                registerMode: {
                    type: String,
                    default: 'phone', // 'phone' หรือ 'username'
                },
                phoneConfig: {
                    type: Object,
                    default: () => ({
                        length: 10,
                        min_length: 10,
                        max_length: 10,
                    }),
                },
                stickerPacks: {
                    type: Array,
                    default: () => [],
                },
            },
            data() {
                return {
                    conversations: [],
                    pagination: {
                        current_page: 1,
                        last_page: 1,
                        per_page: 5,
                        total: 0,
                    },
                    filters: {
                        status: 'open',
                        q: '',
                        account_id: null,
                        scope: 'all', // 'all' | 'mine'
                    },
                    statusOptions: [
                        // {value: 'all', text: 'ทั้งหมด'},
                        {value: 'open', text: 'ทั้งหมด'},
                        {value: 'assigned', text: 'ดำเนินการ'},
                        {value: 'closed', text: 'เสร็จสิ้น'},
                    ],
                    accountOptions: [],
                    bankOptions: [],
                    depositTable: null,
                    currentActiveConversationId: null,
                    loadingList: false,
                    loadingMore: false,
                    selectedConversation: null,
                    messages: [],
                    loadingMessages: false,
                    replyText: '',
                    replyTextFromTemplate: false,
                    replyingToMessage: null,
                    sending: false,
                    uploadingImage: false,
                    uploading: false,
                    autoRefreshTimer: null,
                    formatted: '',
                    selected: '',
                    fields: [
                        {key: 'time', label: 'วันที่รายการ'},
                        {key: 'bank', label: 'ช่องทางฝาก', class: 'text-center'},
                        {key: 'amount', label: 'จำนวนเงิน', class: 'text-right'},
                        {key: 'user_id', label: 'ผู้ทำรายการ', class: 'text-center'},
                        {key: 'status', label: 'สถานะ', class: 'text-center'},
                    ],
                    items: [],
                    caption: null,
                    isBusy: false,
                    show: false,
                    userFound: {addedit: false, deposit: false},
                    userTimer: null,

                    submittingSearch: false,
                    submittingAddEdit: false,
                    submittingDeposit: false,
                    submittingClear: false,

                    searchingDeposit: false,
                    searchedDeposit: false,
                    // debounce การค้นหา
                    searchDelayTimer: null,

                    // modal ผูก member
                    memberModal: {
                        member_id: '',
                        display_name: '',
                        member: null,
                        loading: false,
                        saving: false,
                        error: '',
                    },

                    // modal สมัครสมาชิกแทนลูกค้า
                    registerModal: {
                        username: '',
                        phone: '',
                        bank_code: '',
                        account_no: '',
                        name: '',
                        surname: '',
                        loading: false,
                        error: '',
                        checkingDuplicate: false, // เช็คซ้ำเบอร์/บัญชี

                        checkingUsername: false,
                        usernameStatus: null,          // 'ok' | 'duplicate' | 'invalid' | null
                        usernameStatusMessage: '',

                        checkingPhone: false,
                        phoneStatus: null,          // 'ok' | 'duplicate' | 'invalid' | null
                        phoneStatusMessage: '',

                        // สถานะการเช็คเลขบัญชี
                        checkingAccount: false,
                        accountStatus: null,        // 'ok' | 'invalid' | 'error' | null
                        accountStatusMessage: '',
                    },
                    balanceLoading: false,
                    balanceData: null,
                    bankAccountCheckTimer: null,
                    // modal เติมเงิน
                    topupModal: {
                        pendingItems: [],
                        selectedItem: null,
                        memberSearch: '',
                        member: null,
                        bank: '',
                        account_code: '',
                        date_bank: '',
                        time_bank: '',
                        amount: null,
                        loading: false,
                        error: '',
                    },
                    banks: [{value: '', text: '== ธนาคาร =='}],
                    // จะ set เป็น function ใน subscribeRealtime()
                    unsubscribeRealtime: null,

                    // ===== Quick Reply state =====
                    quickReplies: [],
                    quickRepliesLoading: false,
                    quickReplySearch: '',
                    quickRepliesLoadedForConvId: null,
                    selectedQuickReply: null,
                    sendingQuickReply: false,

                    // ===== Notes state =====
                    notes: [],
                    notesLoading: false,
                    notesError: '',
                    activeNoteIndex: 0,

                    // popup note
                    noteModalMode: 'create', // 'create' | 'edit'
                    noteModalText: '',
                    noteModalSaving: false,
                    noteEditingId: null,

                    quickReplySaving: false,
                    quickReplySaveError: null,
                    quickReplyImageError: null,
                    quickReplyItemsError: null,

                    quickReplyTypeOptions: [
                        {value: 'text', text: 'ข้อความ (Text)'},
                        {value: 'image', text: 'รูปภาพ (Image)'},
                        {value: 'text_quick_reply', text: 'ข้อความ + ปุ่มลัด (Text + Quick Reply)'},
                        {value: 'image_quick_reply', text: 'รูป + ปุ่มลัด (Image + Quick Reply)'},
                    ],

                    quickReplyActionTypeOptions: [
                        {value: 'message', text: 'ส่งข้อความ (message)'},
                        {value: 'postback', text: 'Postback (data)'},
                        {value: 'uri', text: 'เปิดลิงก์ (uri)'},
                    ],

                    quickReplyForm: {
                        type: 'text',
                        description: '',
                        message: '',
                        enabled: true,

                        image_file: null,
                        image_preview_url: '',
                        image_url: '',          // ✅ URL หลังอัปโหลดเสร็จ (เอาไปยัดใน message JSON)
                        uploading_image: false, // ✅ สถานะกำลังอัปโหลด

                        quick_reply_items: [],
                    },

                    // สำหรับ modal ผู้รับผิดชอบ
                    assigneeOptions: [],
                    assigneeLoading: false,
                    assigneeSearch: '',
                    selectedAssigneeId: null,
                    savingAssignee: false,

                    // เปิด/ปิดเสียงทั้งระบบหน้าเว็บนี้ (เผื่ออยากใช้ทีหลัง)
                    soundEnabled: true,

                    // เก็บรายการห้องที่ mute สำหรับ admin คนนี้ (ตาม id)
                    mutedConversationIds: [],

                    // key สำหรับเก็บใน localStorage (ผูกกับรหัสพนักงาน ถ้ามี)
                    muteStorageKey: 'lineoa_mute_' + (
                        (window.AdminUser && window.AdminUser.code)
                            ? window.AdminUser.code
                            : 'guest'
                    ),

                    pinnedPanelExpanded: false,
                    hidePinnedPanel: false,
                    selectedStickerPackId: null,
                    stickerLoading: false,
                    // ===== Emoji picker ส่วนกลาง =====
                    showEmojiPicker: false,
                    showEmojiPickerModal: false,
                    showEmojiPickerNoteModal: false,
                    emojiPickerStyle: {
                        // default โผล่กลางจอ
                        top: '20%',
                        left: '50%',
                        transform: 'translateX(-50%)',
                        width: '320px',
                        zIndex: 200000,
                    },
                    emojiTarget: 'chat', // 'chat' หรือ 'quickReply'
                    emojiIndex: window.__emojiIndex || null,


                    typingTimer: null,
                    typingStopTimer: null,
                    typingActive: false,

                    imagePreview: {
                        open: false,
                        url: null,
                        messageId: null,
                    },

                    draftAttachment: null,
                    replyDraftAttachment: null,   // เก็บ payload ที่ไม่ใช่ text (รูป/quick reply/json)
                    replyDraftType: null,
                };
            },
            created() {
                this.fetchConversations(1);
                // this.startAutoRefresh();
                this.subscribeRealtime();
                this.fetchBanks();
                this.loadMutedConversations();
            },
            beforeDestroy() {
                // this.stopAutoRefresh();

                if (this.selectedConversation) {
                    this.unlockConversation(this.selectedConversation);
                }

                if (typeof this.unsubscribeRealtime === 'function') {
                    this.unsubscribeRealtime();
                }
                document.removeEventListener('click', this.handleClickOutside);

            },
            mounted() {
                // default เลือก pack แรกถ้ามี
                if (this.stickerPacks.length > 0 && !this.selectedStickerPackId) {
                    this.selectedStickerPackId = this.stickerPacks[0].id;
                }
                document.addEventListener('click', this.handleClickOutside);

                window.memberAdjustBridge = {
                    onChildShown: () => {
                        // กันเคส modal หลักยังค้างอยู่
                        if (this.$refs.memberAdjustModal) {
                            this.$refs.memberAdjustModal.hide();
                        }
                    },
                    onChildHidden: () => {
                        // เวลา modal ลูกถูกปิด → เปิด modal หลักกลับมา
                        if (this.$refs.memberAdjustModal) {
                            this.$refs.memberAdjustModal.show();
                        }
                    }
                };

                window.memberLogBridge = {
                    onChildShown: () => {
                        // กันเคส modal หลักยังค้างอยู่
                        if (this.$refs.memberLogModal) {
                            this.$refs.memberLogModal.hide();
                        }
                    },
                    onChildHidden: () => {
                        // เวลา modal ลูกถูกปิด → เปิด modal หลักกลับมา
                        if (this.$refs.memberLogModal) {
                            this.$refs.memberLogModal.show();
                        }
                    }
                };
            },
            computed: {
                needsImage() {
                    return this.quickReplyForm.type === 'image' || this.quickReplyForm.type === 'image_quick_reply';
                },
                needsText() {
                    return this.quickReplyForm.type === 'text' || this.quickReplyForm.type === 'text_quick_reply';
                },
                needsQuickReply() {
                    return this.quickReplyForm.type === 'text_quick_reply' || this.quickReplyForm.type === 'image_quick_reply';
                },
                stickerPackOptions() {
                    return (this.stickerPacks || []).map(p => ({
                        value: p.id,
                        text: p.title,
                    }));
                },

                activePack() {
                    if (!this.stickerPacks || this.stickerPacks.length === 0) {
                        return null;
                    }
                    return (
                        this.stickerPacks.find(p => p.id === this.selectedStickerPackId) ||
                        this.stickerPacks[0]
                    );
                },

                activePackageId() {
                    return this.activePack ? this.activePack.package_id : null;
                },

                activeStickers() {
                    return this.activePack ? (this.activePack.stickers || []) : [];
                },
                notesCount() {
                    return this.notes ? this.notes.length : 0;
                },
                activeNote() {
                    if (!this.notesCount) {
                        return {};
                    }
                    const idx = Math.max(0, Math.min(this.activeNoteIndex, this.notesCount - 1));
                    return this.notes[idx] || {};
                },
                activeNotePosition() {
                    if (!this.notesCount) {
                        return '0/0';
                    }
                    return (this.activeNoteIndex + 1) + '/' + this.notesCount;
                },

                filteredQuickReplies() {
                    const term = (this.quickReplySearch || '').toLowerCase().trim();
                    if (!term) {
                        return this.quickReplies;
                    }

                    return this.quickReplies.filter(item => {
                        return (
                            (item.label && item.label.toLowerCase().includes(term)) ||
                            (item.preview && item.preview.toLowerCase().includes(term))
                        );
                    });
                },
                currentEmployeeId() {
                    const emp = window.LineOAEmployee || null;
                    if (!emp) return null;

                    if (emp.code) {
                        return String(emp.code);
                    }
                    if (emp.id) {
                        return String(emp.id);
                    }
                    return null;
                },
                canReply() {
                    const conv = this.selectedConversation;
                    if (!conv) return false;

                    // ปิดเคส → ห้ามตอบ
                    // if (conv.status === 'closed') return false;

                    // ต้องมีคนรับเรื่องก่อน
                    // if (!conv.assigned_employee_id) return true;

                    const me = this.currentEmployeeId;
                    if (!me) return false;

                    // ถ้ามีการล็อกห้อง → ให้เฉพาะคนล็อกตอบได้
                    // if (conv.locked_by_employee_id) {
                    //     return true;
                    //     // return String(conv.locked_by_employee_id) === String(me);
                    // }
                    //
                    // // ถ้าไม่มีการล็อก → ให้เฉพาะผู้รับเรื่องตอบได้
                    return true;
                    // return String(conv.assigned_employee_id) === String(me);
                },
                isTwBank() {
                    const code = String(this.registerModal.bank_code || '').toUpperCase();
                    return code === '18' || code === 'TW';
                },

                phoneStatusClass() {
                    const s = this.registerModal.phoneStatus;
                    if (s === 'ok') return 'text-success';
                    if (s === 'duplicate' || s === 'invalid') return 'text-danger';
                    return '';
                },

                accountStatusClass() {
                    const s = this.registerModal.accountStatus;
                    if (s === 'ok') return 'text-success';
                    if (s === 'invalid' || s === 'error') return 'text-danger';
                    return '';
                },
                usernameStatusClass() {
                    const s = this.registerModal.usernameStatus;
                    if (s === 'ok') return 'text-success';
                    if (s === 'duplicate' || s === 'invalid') return 'text-danger';
                    return '';
                },
                canSendReply() {
                    const hasText = (this.replyText || '').trim().length > 0;
                    const hasDraft = !!(this.replyDraftAttachment && this.replyDraftAttachment.template_id);
                    return hasText || hasDraft;
                },
                // ของเดิมที่คุณมีอยู่แล้ว ปรับให้คิดสถานะด้วย
                canSubmitRegister() {
                    const m = this.registerModal;
                    const mode = this.registerMode || 'phone';   // phone | username | both

                    const bankOk = !!m.bank_code;
                    const nameOk = !!m.name;
                    const snameOk = !!m.surname;

                    const accDigits = (m.account_no || '').replace(/\D/g, '');
                    let accountOkLength = false;

                    if (this.isTwBank) {
                        // TW ต้องเท่ากับ phone length ในระบบไทย = 10
                        accountOkLength = accDigits.length === 10;
                    } else {
                        // ธนาคารทั่วไป
                        accountOkLength = accDigits.length >= 10;
                    }

                    const noPendingCheck =
                        !m.checkingPhone &&
                        !m.checkingAccount &&
                        !m.checkingUsername; // เพิ่ม username check

                    // Validation แบบเฉพาะโหมด
                    if (mode === 'phone') {
                        const phoneDigits = (m.phone || '').replace(/\D/g, '');

                        // ความยาวต้องสอดคล้อง config
                        const minLen = this.phoneConfig?.min_length || 8;
                        const maxLen = this.phoneConfig?.max_length || 15;
                        const phoneOk = phoneDigits.length >= minLen && phoneDigits.length <= maxLen;

                        // status จาก backend ต้อง ok
                        const phoneStatusOk = !['duplicate', 'invalid', 'error'].includes(m.phoneStatus);

                        const accountStatusOk = !['invalid', 'error'].includes(m.accountStatus);

                        return phoneOk
                            && bankOk
                            && accountOkLength
                            && nameOk
                            && snameOk
                            && noPendingCheck
                            && phoneStatusOk
                            && accountStatusOk;
                    }

                    if (mode === 'username') {
                        const username = (m.username || '').trim();

                        // username ต้องยาวไม่น้อยกว่า 4 ตัวขึ้นไป
                        const usernameLenOk = username.length >= 4;

                        // username ผ่าน regex (ล่างสุด)
                        const usernameFormatOk = /^[a-z0-9]+$/.test(username);

                        // ห้ามซ้ำ / ห้าม invalid / ห้าม error
                        const usernameStatusOk = m.usernameStatus === 'ok';

                        const accountStatusOk = !['invalid', 'error'].includes(m.accountStatus);

                        return usernameLenOk
                            && usernameFormatOk
                            && usernameStatusOk
                            && bankOk
                            && accountOkLength
                            && nameOk
                            && snameOk
                            && accountStatusOk
                            && noPendingCheck;
                    }

                    // ถ้ามี mode "both" เพิ่มมาในอนาคต (สมัครแบบใดแบบหนึ่งได้)
                    if (mode === 'both') {
                        // ให้เลือกอย่างใดอย่างหนึ่ง
                        // กรณีนี้กำหนดให้ "username มีค่า" → ใช้ username flow
                        if ((m.username || '').trim() !== '') {
                            return this.canSubmitRegisterUsernameMode(m);
                        }

                        // ถ้า username ว่าง → ใช้ phone flow
                        return this.canSubmitRegisterPhoneMode(m);
                    }

                    return false;
                },
                getMessageDisplay() {
                    return (msg) => {
                        const lines = {
                            original: msg.text || '',
                            translated: null,
                            lang: null,
                            target: null,
                        };

                        // === inbound (ลูกค้าพิมมา) ===
                        if (msg.direction === 'inbound' &&
                            msg.meta &&
                            msg.meta.translation_inbound
                        ) {
                            const t = msg.meta.translation_inbound;
                            lines.original = t.original_text || msg.text;
                            lines.translated = t.translated_text || null;
                            lines.lang = t.detected_source || t.source_language || null;  // เช่น 'ja'
                        }

                        // === outbound (พนักงานพิม) ===
                        if (msg.direction === 'outbound' &&
                            msg.meta &&
                            msg.meta.translation_outbound
                        ) {
                            const t = msg.meta.translation_outbound;
                            lines.original = t.original_text || msg.text;         // ไทย
                            lines.translated = t.translated_text || null;           // ภาษาเป้าหมาย
                            lines.target = t.target_language || null;           // เช่น 'en'
                        }

                        return lines;
                    };
                },
                messagesWithSeparators() {
                    const out = [];
                    let lastDateKey = null;

                    (this.messages || []).forEach(msg => {
                        if (!msg || !msg.sent_at) return;

                        const key = this.dateKey(msg.sent_at);

                        if (key !== lastDateKey) {
                            out.push({
                                kind: 'date',
                                dateKey: key,
                                date: msg.sent_at,
                            });
                            lastDateKey = key;
                        }

                        out.push({
                            kind: 'msg',
                            message: msg,
                        });
                    });

                    return out;
                },
                filteredAssignees() {
                    const q = (this.assigneeSearch || '').toLowerCase();
                    if (!q) {
                        return this.assigneeOptions;
                    }
                    return this.assigneeOptions.filter(emp => {
                        return (
                            (emp.display && emp.display.toLowerCase().includes(q)) ||
                            (emp.sub && emp.sub.toLowerCase().includes(q)) ||
                            (emp.code && emp.code.toLowerCase().includes(q)) ||
                            (emp.user_name && emp.user_name.toLowerCase().includes(q))
                        );
                    });
                },
                pinnedMessages() {
                    return (this.messages || []).filter(m => {
                        if (!m) return false;
                        return m.is_pinned === true || m.is_pinned === 1 || m.is_pinned === '1';
                    });
                },
                primaryPinned() {
                    const list = this.pinnedMessages || [];
                    return list.length ? list[0] : null;
                },
                otherPinnedMessages() {
                    const list = this.pinnedMessages || [];
                    return list.length > 1 ? list.slice(1) : [];
                },
            },
            watch: {
                'filters.status': function () {
                    this.reloadConversations();
                },
                // 'filters.scope': function () {
                //     this.reloadConversations();
                // },
                'filters.account_id': function () {
                    this.reloadConversations();
                },
                // search ให้ debounce หน่อย
                // 'filters.q': _.debounce(function () {
                //     this.reloadConversations();
                // }, 400),
                registerMode() {
                    this.saveRegisterDraft();
                },

                // เปลี่ยนห้องแล้วอยากให้ draft แยกตามห้อง (key มันแยกอยู่แล้ว)
                selectedConversation() {
                    // ไม่ต้องทำอะไรมาก แต่จะเรียก save เพื่อกัน state ค้างก็ได้
                    this.saveRegisterDraft();
                },

                // ฟิลด์สำคัญ ๆ: บันทึกเมื่อเปลี่ยนค่า
                'registerModal.username': function () { this.saveRegisterDraft(); },
                'registerModal.phone': function () { this.saveRegisterDraft(); },
                'registerModal.bank_code': function () { this.saveRegisterDraft(); },
                'registerModal.account_no': function () { this.saveRegisterDraft(); },
                'registerModal.name': function () { this.saveRegisterDraft(); },
                'registerModal.surname': function () { this.saveRegisterDraft(); },
            },
            methods: {
                openImagePreview(item) {
                    const url =
                        item?.message?.payload?.message?.contentUrl ||
                        item?.message?.payload?.message?.previewUrl ||
                        item?.message?.payload?.message?.originalContentUrl ||
                        null;

                    if (!url) return;

                    this.imagePreview = {
                        open: true,
                        url,
                        messageId: item?.message?.id || item?.message?.line_message_id || null,
                    };

                    document.body.classList.add('no-scroll');
                },

                closeImagePreview() {
                    this.imagePreview.open = false;
                    this.imagePreview.url = null;
                    this.imagePreview.messageId = null;

                    document.body.classList.remove('no-scroll');
                },

                onImageError(item) {
                    // กันรูปเสีย/โหลดไม่ได้ (คุณจะ log หรือ fallback เป็น placeholder ก็ได้)
                    // console.warn('[chat] image load failed', item);
                },

                handleClickOutside(e) {
                    const btnMain = this.$refs.emojiBtn?.$el || this.$refs.emojiBtn;
                    const btnQuick = this.$refs.quickReplyEmojiBtn?.$el || this.$refs.quickReplyEmojiBtn;
                    const btnNote = this.$refs.noteEmojiBtn?.$el || this.$refs.noteEmojiBtn;
                    const popupMain = this.$refs.emojiPopup;
                    const popupModal = this.$refs.emojiPopupModal;
                    const popupNoteModal = this.$refs.emojiPopupNoteModal;

                    if (!this.showEmojiPicker && !this.showEmojiPickerModal && !this.showEmojiPickerNoteModal) return;

                    // กดโดนปุ่มใดปุ่มหนึ่ง → ไม่ปิด
                    if (btnMain && btnMain.contains(e.target)) return;
                    if (btnQuick && btnQuick.contains(e.target)) return;
                    if (btnNote && btnNote.contains(e.target)) return;

                    // กดโดน popup ใด popup หนึ่ง → ไม่ปิด
                    if (popupMain && popupMain.contains(e.target)) return;
                    if (popupModal && popupModal.contains(e.target)) return;
                    if (popupNoteModal && popupNoteModal.contains(e.target)) return;

                    // ที่เหลือคือคลิกนอก → ปิดทั้งสอง
                    this.showEmojiPicker = false;
                    this.showEmojiPickerModal = false;
                    this.showEmojiPickerNoteModal = false;
                },

                loadMutedConversations() {
                    try {
                        const raw = localStorage.getItem(this.muteStorageKey);
                        if (!raw) {
                            this.mutedConversationIds = [];
                            return;
                        }

                        const arr = JSON.parse(raw);
                        if (Array.isArray(arr)) {
                            this.mutedConversationIds = arr
                                .map(id => Number(id))
                                .filter(id => !isNaN(id));
                        } else {
                            this.mutedConversationIds = [];
                        }
                    } catch (e) {
                        console.error('[LineOA] loadMutedConversations error', e);
                        this.mutedConversationIds = [];
                    }
                },

                saveMutedConversations() {
                    try {
                        const arr = (this.mutedConversationIds || [])
                            .map(id => Number(id))
                            .filter(id => !isNaN(id));

                        localStorage.setItem(this.muteStorageKey, JSON.stringify(arr));
                    } catch (e) {
                        console.error('[LineOA] saveMutedConversations error', e);
                    }
                },

                isConversationMuted(convId) {
                    if (!convId) return false;
                    const id = Number(convId);
                    return this.mutedConversationIds.includes(id);
                },

                toggleMuteConversation(convId) {
                    if (!convId) return;

                    const id = Number(convId);
                    const idx = this.mutedConversationIds.indexOf(id);

                    if (idx === -1) {
                        // ยังไม่ถูก mute → เพิ่มเข้า list
                        this.mutedConversationIds.push(id);
                    } else {
                        // เคย mute แล้ว → เอาออก (unmute)
                        this.mutedConversationIds.splice(idx, 1);
                    }

                    this.saveMutedConversations();
                },

                // ใช้กับปุ่มใน UI (อิง selectedConversation)
                oNOffSound() {
                    if (!this.selectedConversation) return;
                    this.toggleMuteConversation(this.selectedConversation.id);
                },

                // helper สำหรับใช้ guard ก่อนเล่นเสียง
                shouldPlaySoundForMessage(msg) {
                    if (!msg) return false;

                    // เล่นเฉพาะข้อความจากลูกค้า
                    if (msg.direction !== 'inbound' || msg.source !== 'user') {
                        return false;
                    }

                    return true;
                },

                // เล่นเสียง (เช็คทุกเงื่อนไขก่อน)
                playNewMessageSound(msg) {
                    console.log('[SOUND] เรียก playNewMessageSound() → msg:', msg);

                    // 1) ต้องมี message
                    if (!msg) {
                        console.log('[SOUND] ❌ ไม่มี msg → ไม่เล่นเสียง');
                        return;
                    }

                    // 2) ต้องเป็นข้อความ inbound จากลูกค้าเท่านั้น
                    if (msg.direction !== 'inbound' || msg.source !== 'user') {
                        console.log('[SOUND] ❌ ไม่ใช่ข้อความลูกค้า → direction:', msg.direction, 'source:', msg.source);
                        return;
                    }
                    console.log('[SOUND] ✔ เป็นข้อความจากลูกค้า OK');

                    // หา conversation ID
                    const convId =
                        msg.line_conversation_id ||
                        msg.conversation_id ||
                        (msg.conversation && msg.conversation.id) ||
                        null;

                    console.log('[SOUND] convId =', convId);

                    if (!convId) {
                        console.log('[SOUND] ❌ หา conversation id ไม่ได้');
                        return;
                    }

                    // 3) ตรวจว่าเปิดเสียงระบบอยู่ไหม
                    console.log('[SOUND] soundEnabled =', this.soundEnabled);
                    if (!this.soundEnabled) {
                        console.log('[SOUND] ❌ ระบบปิดเสียงทั้งหมด → ไม่เล่น');
                        return;
                    }

                    // 4) ห้องนี้โดน mute หรือเปล่า
                    const isMuted = this.isConversationMuted && this.isConversationMuted(convId);
                    console.log('[SOUND] ห้องนี้ mute ไหม? →', isMuted);

                    if (isMuted) {
                        console.log('[SOUND] ❌ ห้องนี้โดน mute → ไม่เล่น');
                        return;
                    }

                    // 5) เล่นเสียงจริง
                    const audio = document.getElementById('line-noti-audio');

                    if (!audio) {
                        console.log('[SOUND] ❌ หา element #line-noti-audio ไม่เจอ → ไม่เล่น');
                        return;
                    }

                    try {
                        audio.muted = false;
                        audio.currentTime = 0;
                        audio.play().then(() => {
                            console.log('[SOUND] ✔ เล่นเสียงสำเร็จ');
                        }).catch(err => {
                            console.log('[SOUND] ❌ audio.play() error:', err);
                        });
                    } catch (e) {
                        console.log('[SOUND] ❌ exception ตอนเล่นเสียง:', e);
                    }
                },

                dateKey(dateString) {
                    if (!dateString) return null;
                    const d = new Date(dateString);

                    const y = d.getFullYear();
                    const m = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');

                    // ใช้เป็น key สำหรับเปรียบเทียบวันเดียวกัน
                    return `${y}-${m}-${day}`;
                },

                formatChatDay(dateString) {
                    if (!dateString) return '';

                    const d = new Date(dateString);

                    return d.toLocaleDateString('th-TH', {
                        day: '2-digit',
                        month: 'short',
                        // ถ้าอยากให้ขึ้นปีเมื่อข้ามปี:
                        // year: (d.getFullYear() !== new Date().getFullYear()) ? '2-digit' : undefined,
                    });
                },

                formatChatTime(dateString) {
                    if (!dateString) return '';
                    const d = new Date(dateString);

                    return d.toLocaleTimeString('th-TH', {
                        hour: '2-digit',
                        minute: '2-digit',
                    });
                },

                formatChatDateTime(dateString) {
                    if (!dateString) return '';
                    const d = new Date(dateString);

                    const day = d.getDate();
                    const monthShort = d.toLocaleDateString('th-TH', {month: 'short'}); // ธ.ค.
                    const year = d.getFullYear();

                    const time = d.toLocaleTimeString('th-TH', {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    }).replace(':', '.') + ' น.';

                    return `${day} ${monthShort} ${year} ${time}`;
                },

                // ===== Notes API (ต้องมี backend: GET/POST /line-oa/conversations/{id}/notes) =====
                async loadNotes() {
                    if (!this.selectedConversation || !this.selectedConversation.id) {
                        this.notes = [];
                        this.activeNoteIndex = 0;
                        return;
                    }

                    this.notesLoading = true;
                    this.notesError = '';

                    try {
                        const url = this.apiUrl(`conversations/${this.selectedConversation.id}/notes`);
                        const resp = await axios.get(url);

                        if (resp.data && resp.data.success) {
                            this.notes = resp.data.data || [];
                            this.activeNoteIndex = this.notes.length ? 0 : 0;
                        } else {
                            this.notes = [];
                            this.activeNoteIndex = 0;
                            this.notesError = resp.data.message || 'โหลดโน้ตไม่สำเร็จ';
                        }
                    } catch (e) {
                        this.notesError = 'โหลดโน้ตไม่สำเร็จ กรุณาลองใหม่';
                    } finally {
                        this.notesLoading = false;
                    }
                },

                openNoteCreateModal() {
                    if (!this.selectedConversation || !this.selectedConversation.id) {
                        this.notesError = 'กรุณาเลือกห้องก่อน';
                        return;
                    }
                    this.noteModalMode = 'create';
                    this.noteModalText = '';
                    this.noteEditingId = null;
                    this.notesError = '';
                    this.$refs.noteModal && this.$refs.noteModal.show();
                },

                openNoteEditModal(note) {
                    if (!note || !note.id) {
                        return;
                    }
                    this.noteModalMode = 'edit';
                    this.noteEditingId = note.id;
                    this.noteModalText = note.body || note.text || '';
                    this.notesError = '';
                    this.$refs.noteModal && this.$refs.noteModal.show();
                },

                async saveNoteModal() {
                    const body = (this.noteModalText || '').trim();
                    if (!body) {
                        this.notesError = 'ข้อความโน้ตห้ามเว้นว่าง';
                        return;
                    }

                    if (!this.selectedConversation || !this.selectedConversation.id) {
                        this.notesError = 'ไม่พบห้องสนทนา';
                        return;
                    }

                    this.noteModalSaving = true;
                    this.notesError = '';

                    try {
                        let resp;

                        if (this.noteModalMode === 'create') {
                            const url = this.apiUrl(`conversations/${this.selectedConversation.id}/notes`);
                            resp = await axios.post(url, {body});
                        } else {
                            const noteId = this.noteEditingId;
                            if (!noteId) {
                                throw new Error('ไม่พบโน้ตที่จะแก้ไข');
                            }
                            const url = this.apiUrl(`conversations/${this.selectedConversation.id}/notes/${noteId}`);
                            resp = await axios.patch(url, {body});
                        }

                        const ok = resp.data && resp.data.success;
                        if (!ok) {
                            this.notesError = resp.data.message || 'บันทึกโน้ตไม่สำเร็จ';
                            return;
                        }

                        const note = resp.data.data;

                        if (this.noteModalMode === 'create') {
                            // จะให้ล่าสุดไปอยู่หน้าสุดหรือท้ายสุดก็ได้
                            this.notes.unshift(note);
                            this.activeNoteIndex = 0;
                        } else {
                            const idx = this.notes.findIndex(n => n.id === note.id);
                            if (idx !== -1) {
                                this.$set(this.notes, idx, note);
                                this.activeNoteIndex = idx;
                            }
                        }

                        this.$refs.noteModal && this.$refs.noteModal.hide();
                    } catch (e) {
                        this.notesError = 'บันทึกโน้ตไม่สำเร็จ กรุณาลองใหม่';
                    } finally {
                        this.noteModalSaving = false;
                    }
                },

                async confirmDeleteNote(note) {
                    if (!note || !note.id) {
                        return;
                    }

                    if (!this.selectedConversation || !this.selectedConversation.id) {
                        this.notesError = 'ไม่พบห้องสนทนา';
                        return;
                    }

                    if (!window.confirm('ยืนยันการลบโน้ตนี้หรือไม่?')) {
                        return;
                    }

                    this.notesError = '';

                    try {
                        const url = this.apiUrl(`conversations/${this.selectedConversation.id}/notes/${note.id}`);
                        const resp = await axios.delete(url);

                        if (!resp.data || !resp.data.success) {
                            this.notesError = resp.data?.message || 'ลบโน้ตไม่สำเร็จ';
                            return;
                        }

                        const idx = this.notes.findIndex(n => n.id === note.id);
                        if (idx !== -1) {
                            this.notes.splice(idx, 1);

                            if (this.activeNoteIndex >= this.notes.length) {
                                this.activeNoteIndex = this.notes.length ? this.notes.length - 1 : 0;
                            }
                        }
                    } catch (e) {
                        this.notesError = 'ลบโน้ตไม่สำเร็จ กรุณาลองใหม่';
                    }
                },

                prevNote() {
                    if (this.activeNoteIndex > 0) {
                        this.activeNoteIndex--;
                    }
                },

                nextNote() {
                    if (this.activeNoteIndex < this.notesCount - 1) {
                        this.activeNoteIndex++;
                    }
                },

                async fetchNotes(conversationId) {
                    if (!conversationId) return;

                    this.notesLoading = true;
                    this.notesError = '';
                    this.notes = [];

                    try {
                        const res = await axios.get(this.apiUrl('conversations/' + conversationId + '/notes'));
                        const body = res.data || {};
                        const items = body.data || body.notes || [];

                        this.notes = items.map((n, idx) => ({
                            id: n.id || n.note_id || null,
                            _local_id: n.id ? null : ('local-' + idx),
                            body: n.body || n.text || '',
                            employee_name: n.employee_name || n.created_by_name || null,
                            created_at: n.created_at || null,
                        }));
                    } catch (e) {
                        console.error('[LineOA] fetchNotes error', e);
                        this.notesError = 'โหลดโน้ตไม่สำเร็จ';
                    } finally {
                        this.notesLoading = false;
                    }
                },

// ===== Quick Reply =====
                openQuickReplyModal() {
                    if (!this.selectedConversation) {
                        this.showAlert({
                            success: false,
                            message: 'กรุณาเลือกห้องสนทนาก่อน'
                        });
                        return;
                    }

                    if (!this.canReply) {
                        this.showAlert({
                            success: false,
                            message: 'คุณไม่มีสิทธิ์ตอบในห้องสนทนานี้'
                        });
                        return;
                    }

                    // ถ้าเปลี่ยนห้องใหม่ หรือยังไม่เคยโหลดของห้องนี้ → โหลดใหม่
                    if (this.quickRepliesLoadedForConvId !== this.currentActiveConversationId) {
                        this.fetchQuickReplies();
                    }

                    this.selectedQuickReply = null;
                    this.quickReplySearch = '';

                    if (this.$refs.quickReplyModal) {
                        this.$refs.quickReplyModal.show();
                    }
                },

                async fetchQuickReplies() {
                    if (!this.selectedConversation) return;

                    this.quickRepliesLoading = true;
                    this.quickReplies = [];
                    this.quickRepliesLoadedForConvId = this.selectedConversation.id;

                    try {
                        const convId = this.selectedConversation.id;

                        // ให้ backend ทำ route: GET /line-oa/conversations/{conversation}/quick-replies
                        const res = await axios.get(
                            this.apiUrl('conversations/' + convId + '/quick-replies')
                        );

                        const body = res.data || {};
                        const items = body.data || body.templates || [];

                        this.quickReplies = items.map(t => {
                            // ปรับ mapping ให้ทนต่อชื่อ field ต่าง ๆ ของ backend
                            const label =
                                t.label ||
                                t.title ||
                                t.name ||
                                t.key ||
                                ('Template #' + t.id);

                            const preview =
                                t.preview ||
                                t.preview_text ||
                                t.body_preview ||
                                t.text_preview ||
                                '';

                            const bodyPreview =
                                t.body_preview ||
                                t.body ||
                                t.text ||
                                preview ||
                                '';

                            return {
                                id: t.id,
                                label: label,
                                preview: preview,
                                body_preview: bodyPreview,
                                category: t.category || null,
                                meta: t.meta || {},
                            };
                        });
                    } catch (e) {
                        console.error('[LineOA] fetchQuickReplies error', e);
                        this.showAlert({
                            success: false,
                            message: 'โหลดข้อความตอบกลับไม่สำเร็จ กรุณาลองใหม่'
                        });
                    } finally {
                        this.quickRepliesLoading = false;
                    }
                },

                selectQuickReply(item) {
                    this.selectedQuickReply = item || null;
                },

                async sendQuickReply() {
                    if (!this.selectedConversation || !this.selectedQuickReply) return;

                    if (!this.canReply) {
                        this.showAlert({
                            success: false,
                            message: 'คุณไม่มีสิทธิ์ตอบในห้องสนทนานี้'
                        });
                        return;
                    }

                    if (this.sendingQuickReply) return;
                    this.sendingQuickReply = true;

                    const convId = this.selectedConversation.id;

                    try {
                        // vars สำหรับแทน placeholder ใน template เช่น {display_name}, {username}
                        const vars = {
                            display_name:
                                (this.selectedConversation.contact &&
                                    this.selectedConversation.contact.display_name) ||
                                this.selectedConversation.contact_display_name ||
                                '',
                            username:
                                (this.selectedConversation.contact &&
                                    this.selectedConversation.contact.member_username) ||
                                this.selectedConversation.contact_member_username ||
                                '',
                        };

                        // ให้ backend ทำ route: POST /line-oa/conversations/{conversation}/reply-template
                        const res = await axios.post(
                            this.apiUrl('conversations/' + convId + '/reply-template'),
                            {
                                template_id: this.selectedQuickReply.id,
                                vars: vars,
                                override_text: this.replyText,
                            }
                        );

                        const body = res.data || {};
                        const msg = body.data || body.message || null;

                        if (msg) {
                            // ใส่ message ลงในห้องแชต

                            this.messages.push(msg);

                            if (this.selectedConversation) {
                                this.selectedConversation.last_message = msg.text || this.selectedConversation.last_message;
                                this.selectedConversation.last_message_at = msg.sent_at || this.selectedConversation.last_message_at;
                                this.selectedConversation.unread_count = 0;
                            }

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


                            if (this.$refs.quickReplyModal) {
                                this.$refs.quickReplyModal.hide();
                            }

                            this.$nextTick(() => {
                                this.scrollToBottom();
                            });
                        } else {
                            this.showAlert({
                                success: false,
                                message: 'ส่งข้อความตอบกลับไม่สำเร็จ (ไม่พบข้อมูลข้อความจากเซิร์ฟเวอร์)'
                            });
                        }
                    } catch (e) {
                        console.error('[LineOA] sendQuickReply error', e);

                        const msg =
                            e?.response?.data?.message ??
                            e?.response?.data?.msg ??
                            e?.response?.data?.error ??
                            'ส่งข้อความตอบกลับไม่สำเร็จ กรุณาลองใหม่';

                        this.showAlert({
                            success: false,
                            message: msg
                        });
                    } finally {
                        this.sendingQuickReply = false;
                    }
                },


                async confirmQuickReplyFromModal() {
                    // ✅ ถ้าเป็นรูป: ส่งออกไปทันทีจาก modal (ไม่ผ่าน textarea)
                    // ✅ ถ้าเป็น text/อื่น ๆ: ใช้ flow เดิม (แปะลง textarea / draft) เพื่อให้แก้ไขก่อนส่ง
                    if (!this.selectedConversation || !this.selectedQuickReply) return;

                    const type = (this.selectedQuickReply?.meta?.type || '').toString().toLowerCase();

                    if (type === 'image') {
                        // ใช้เมธอดส่งจริงที่มีอยู่แล้ว เพื่อให้ logging / update UI / broadcast เหมือนเดิม
                        return await this.sendQuickReply();
                    }

                    return await this.sendQuickReplyToText();
                },

                resetReplyComposer() {
                    this.replyText = '';
                    this.replyTextFromTemplate = false;
                    this.replyDraftAttachment = null;
                    this.replyDraftType = null;

                    // ถ้ามี error state / preview state ก็เคลียร์ด้วย
                    this.replyError = null;

                    this.$nextTick(() => {
                        if (this.$refs.replyInput) this.$refs.replyInput.focus();
                    });
                },


                async sendQuickReplyToText() {
                    if (!this.selectedConversation || !this.selectedQuickReply) return;

                    if (!this.canReply) {
                        this.showAlert({
                            success: false,
                            message: 'คุณไม่มีสิทธิ์ตอบในห้องสนทนานี้'
                        });
                        return;
                    }

                    if (this.sendingQuickReply) return;
                    this.sendingQuickReply = true;

                    const convId = this.selectedConversation.id;

                    try {
                        // เตรียม vars สำหรับแทน placeholder ใน template เช่น {display_name}, {username}
                        const vars = {
                            display_name:
                                (this.selectedConversation.contact &&
                                    this.selectedConversation.contact.display_name) ||
                                this.selectedConversation.contact_display_name ||
                                '',
                            username:
                                (this.selectedConversation.contact &&
                                    this.selectedConversation.contact.member_username) ||
                                this.selectedConversation.contact_member_username ||
                                '',
                        };

                        // เรียก backend ให้ render template (แต่ "ไม่ส่งข้อความออกไป")
                        const res = await axios.post(
                            this.apiUrl(`conversations/${convId}/reply-template`),
                            {
                                template_id: this.selectedQuickReply.id,
                                vars: vars,
                                preview_only: true, // render เฉย ๆ
                            }
                        );

                        const body = res.data || {};
                        const data = body.data || null;

                        if (!data) {
                            this.showAlert({
                                success: false,
                                message: 'โหลดข้อความจากเทมเพลตไม่สำเร็จ'
                            });
                            return;
                        }

                        /**
                         * ==========================================================
                         * ✅ รองรับ 2 โหมด
                         * 1) TEXT เดิม: { text: "..." }
                         * 2) JSON/IMAGE/QR: อาจเป็น { type, image_url, quick_reply_items } หรือ { messages:[...] } หรืออื่น ๆ
                         * ==========================================================
                         */

                            // --- (A) TEXT โหมดเดิม ---
                        const isImageTemplate = (
                                ((this.selectedQuickReply?.meta?.type || '').toString().toLowerCase() === 'image') ||
                                (this.selectedQuickReply?.meta?.has_image === true) ||
                                ((data && typeof data === 'object' && (data.type || '').toString().toLowerCase() === 'image'))
                            );
                        if (typeof data.text === 'string' && data.text.trim() !== '' && !isImageTemplate) {
                            this.replyText = data.text;
                            this.replyTextFromTemplate = true;

                            // ✅ ถ้า template ที่ preview มี quickReply (text + quick reply)
                            // ให้ mark เป็น draftAttachment เพื่อให้ตอนกดส่งจริง ใช้ /reply-template (ส่ง quickReply ออกไปด้วย)
                            if (data && Array.isArray(data.line_messages) && data.line_messages.length > 0) {
                                const firstMsg = data.line_messages[0] || null;
                                if (firstMsg && firstMsg.quickReply && firstMsg.quickReply.items && firstMsg.quickReply.items.length > 0) {
                                    this.replyDraftAttachment = {
                                        template_id: this.selectedQuickReply.id,
                                        meta: this.selectedQuickReply?.meta || {},
                                        rendered: data,
                                    };
                                    this.replyDraftType = 'text_quick_reply';
                                } else {
                                    // text ธรรมดา → ไม่ต้องเป็น draftAttachment
                                    this.replyDraftAttachment = null;
                                    this.replyDraftType = null;
                                }
                            } else {
                                this.replyDraftAttachment = null;
                                this.replyDraftType = null;
                            }


                            // เคลียร์ draft attachment เดิม (กันส่งผิด)
                            // this.replyDraftAttachment = null;
                            // this.replyDraftType = null;

                            if (this.$refs.quickReplyModal) this.$refs.quickReplyModal.hide();

                            this.$nextTick(() => {
                                if (this.$refs.replyInput) this.$refs.replyInput.focus();
                            });

                            return;
                        }

                        // --- (B) โหมด JSON แบบ { messages:[{kind:'text',text:'...'}] } (รองรับของเก่า/บางระบบ) ---
                        if (Array.isArray(data.messages)) {
                            const firstText = data.messages.find(m => (m && m.kind === 'text' && m.text)) || null;
                            if (firstText && typeof firstText.text === 'string' && firstText.text.trim() !== '') {
                                this.replyText = firstText.text;
                                this.replyTextFromTemplate = true;

                                // ✅ รองรับกรณี backend คืน line_messages (พร้อม quickReply) แต่ไม่มี data.text
                                if (data && Array.isArray(data.line_messages) && data.line_messages.length > 0) {
                                    const firstMsg = data.line_messages[0] || null;
                                    if (firstMsg && firstMsg.quickReply && firstMsg.quickReply.items && firstMsg.quickReply.items.length > 0) {
                                        this.replyDraftAttachment = {
                                            template_id: this.selectedQuickReply.id,
                                            meta: this.selectedQuickReply?.meta || {},
                                            rendered: data,
                                        };
                                        this.replyDraftType = 'text_quick_reply';
                                    } else {
                                        this.replyDraftAttachment = null;
                                        this.replyDraftType = null;
                                    }
                                } else {
                                    this.replyDraftAttachment = null;
                                    this.replyDraftType = null;
                                }


                                this.replyDraftAttachment = null;
                                this.replyDraftType = null;

                                if (this.$refs.quickReplyModal) this.$refs.quickReplyModal.hide();
                                this.$nextTick(() => {
                                    if (this.$refs.replyInput) this.$refs.replyInput.focus();
                                });

                                return;
                            }

                            // ถ้า messages ไม่มี text (เช่นเป็น image) → ไป draft attachment
                            this.replyDraftAttachment = {
                                template_id: this.selectedQuickReply.id,
                                meta: this.selectedQuickReply?.meta || {},
                                rendered: data,
                            };
                            this.replyDraftType = 'json_messages';
                        }
                        // --- (C) โหมด JSON ใหม่ของเรา { type, image_url, quick_reply_items, text } ---
                        else if (typeof data === 'object') {
                            const type = data.type || (this.selectedQuickReply?.meta?.type) || null;

                            // ถ้าเป็น image/quick reply/json → ไม่ยัด textarea
                            this.replyDraftAttachment = {
                                template_id: this.selectedQuickReply.id,
                                meta: this.selectedQuickReply?.meta || {},
                                rendered: data,
                            };
                            this.replyDraftType = type || 'json';
                        } else {
                            // fallback
                            this.showAlert({
                                success: false,
                                message: 'รูปแบบเทมเพลตไม่รองรับสำหรับการเลือกใช้งาน'
                            });
                            return;
                        }

                        // ปิด modal
                        if (this.$refs.quickReplyModal) {
                            this.$refs.quickReplyModal.hide();
                        }

                        // แจ้งทีมงานแบบชัด ๆ ว่า “แนบแล้ว” (ไม่ใช่ลง textarea)
                        this.showAlert({
                            success: true,
                            message: 'แนบเทมเพลตเรียบร้อย (รูป/ปุ่มลัด) ให้กด “ส่ง” เพื่อส่งให้ลูกค้า'
                        });

                        // โฟกัส textarea เผื่อพิมพ์ข้อความเสริม (แต่ไม่บังคับ)
                        this.$nextTick(() => {
                            if (this.$refs.replyInput) {
                                this.$refs.replyInput.focus();
                            }
                        });

                    } catch (e) {
                        console.error('[LineOA] sendQuickReply error', e);

                        const msg =
                            e?.response?.data?.message ??
                            e?.response?.data?.msg ??
                            e?.response?.data?.error ??
                            'เกิดข้อผิดพลาดระหว่างโหลดข้อความจากเทมเพลต';

                        this.showAlert({
                            success: false,
                            message: msg
                        });
                    } finally {
                        this.sendingQuickReply = false;
                    }
                },


                onProfileImageError(event) {
                    const fallback = window.LineDefaultAvatar || '/images/avatar-default.png';

                    if (event.target.src && event.target.src.includes(fallback)) {
                        event.target.onerror = null;
                        return;
                    }

                    event.target.src = fallback;
                    event.target.onerror = null;
                },

                removeFocusFromTrigger() {
                    // ลอง blur องค์ประกอบที่กำลัง focus อยู่ตอนนี้
                    if (document.activeElement) {
                        document.activeElement.blur();
                    }
                },

                onContext(ctx) {
                    this.formatted = ctx.selectedFormatted || '';
                    this.selected = ctx.selectedYMD || '';
                },
                apiUrl(path) {
                    return '/line-oa/' + path.replace(/^\/+/, '');
                },
                async fetchBanks() {
                    try {
                        const {data} = await axios.get(this.apiUrl('register/load-bank')); // route backend

                        this.bankOptions = data.bank;
                    } catch (e) {
                        console.error('โหลดรายการธนาคารไม่สำเร็จ', e);
                    }
                },
                onBankChange() {
                    // รีเซ็ตค่าที่เกี่ยวกับเลขบัญชี/การเช็ค
                    this.registerModal.account_no = '';
                    this.registerModal.checkingDuplicate = false;
                    this.registerModal.checkingAccount = false;
                    this.registerModal.error = null;

                    if (this.registerModal.bank_code == '18') {
                        this.registerModal.account_no = this.registerModal.phone;
                    }

                    if (this.bankAccountCheckTimer) {
                        clearTimeout(this.bankAccountCheckTimer);
                    }
                },
                onUsernameInput() {
                    // reset state ทุกครั้งที่พิมพ์
                    this.registerModal.error = null;
                    this.registerModal.usernameStatus = null;
                    this.registerModal.usernameStatusMessage = '';

                    let value = (this.registerModal.username || '').toString();

                    // แปลงเป็นตัวเล็กหมด
                    value = value.toLowerCase();

                    // อนุญาตเฉพาะ a-z และ 0-9
                    value = value.replace(/[^a-z0-9]/g, '');

                    // จำกัดความยาวตาม policy (ปรับได้)
                    const maxLen = 10;
                    if (value.length > maxLen) {
                        value = value.substring(0, maxLen);
                    }

                    this.registerModal.username = value;

                    // ถ้ายาวพอ ตามที่ต้องการค่อยยิงไปเช็ค
                    if (value.length >= 5) {
                        this.checkUsernameStatus(value);
                    }
                },
                async checkUsernameStatus(username) {
                    this.registerModal.checkingUsername = true;
                    this.registerModal.usernameStatus = null;
                    this.registerModal.usernameStatusMessage = '';

                    try {
                        // route นี้ให้ชี้ไปที่ ChatController::checkUsername (หรือชื่อที่คุณใช้จริง)
                        const {data} = await axios.post(this.apiUrl('register/check-user'), {
                            username: username,
                        });

                        // สมมติ backend คืน { message: 'success', duplicate: true|false }
                        if (data.message !== 'success') {
                            this.registerModal.usernameStatus = 'invalid';
                            this.registerModal.usernameStatusMessage =
                                data.message || 'ยูสเซอร์ไม่ถูกต้อง';
                            return;
                        }

                        if (data.duplicate === true) {
                            this.registerModal.usernameStatus = 'duplicate';
                            this.registerModal.usernameStatusMessage =
                                'ยูสเซอร์นี้มีอยู่ในระบบแล้ว';
                        } else {
                            this.registerModal.usernameStatus = 'ok';
                            this.registerModal.usernameStatusMessage =
                                'สามารถใช้ยูสเซอร์นี้สมัครสมาชิกได้';
                        }
                    } catch (e) {
                        console.error('checkUsernameStatus error', e);
                        this.registerModal.usernameStatus = 'error';
                        this.registerModal.usernameStatusMessage =
                            'ตรวจสอบยูสเซอร์ไม่สำเร็จ กรุณาลองใหม่';
                        this.registerModal.error =
                            'ตรวจสอบยูสเซอร์ไม่สำเร็จ กรุณาลองใหม่';
                    } finally {
                        this.registerModal.checkingUsername = false;
                    }
                },
                async checkUsernameDuplicate(username) {
                    try {
                        this.registerModal.checkingDuplicate = true;

                        const {data} = await axios.post(this.apiUrl('register/check-user'), {
                            username: username,
                        });

                        if (data.duplicate === true) {
                            this.registerModal.error = 'ยูสเซอร์นี้สมัครสมาชิกแล้ว';
                            return false;
                        }

                        return true;
                    } catch (e) {
                        console.error('เช็คยูสเซอร์ซ้ำไม่สำเร็จ', e);
                        this.registerModal.error = 'ไม่สามารถตรวจสอบยูสเซอร์ได้ กรุณาลองใหม่';
                        return false;
                    } finally {
                        this.registerModal.checkingDuplicate = false;
                    }
                },
                onPhoneInput() {
                    this.registerModal.error = null;
                    this.registerModal.phoneStatus = null;
                    this.registerModal.phoneStatusMessage = '';

                    let digits = (this.registerModal.phone || '').replace(/\D/g, '');

                    const maxLen = this.phoneConfig?.max_length || 15;
                    const minLen = this.phoneConfig?.min_length || 8;

                    if (digits.length > maxLen) {
                        digits = digits.substring(0, maxLen);
                    }
                    this.registerModal.phone = digits;

                    // ยิงเช็คเมื่อความยาวถึงขั้นต่ำที่กำหนด
                    if (digits.length >= minLen) {
                        this.checkPhoneStatus(digits);
                    }
                },
                async checkPhoneStatus(phoneDigits) {
                    this.registerModal.checkingPhone = true;
                    this.registerModal.phoneStatus = null;
                    this.registerModal.phoneStatusMessage = '';

                    try {
                        // route นี้ให้ชี้ไปที่ ChatController::checkPhone
                        const {data} = await axios.post(this.apiUrl('register/check-phone'), {
                            phone: phoneDigits,
                        });

                        if (data.message !== 'success') {
                            this.registerModal.phoneStatus = 'invalid';
                            this.registerModal.phoneStatusMessage =
                                data.message || 'เบอร์โทรไม่ถูกต้อง';
                            return;
                        }

                        if (data.bank === true) {
                            this.registerModal.phoneStatus = 'duplicate';
                            this.registerModal.phoneStatusMessage = 'เบอร์นี้สมัครสมาชิกแล้วในระบบ';
                        } else {
                            this.registerModal.phoneStatus = 'ok';
                            this.registerModal.phoneStatusMessage = 'สามารถใช้เบอร์นี้สมัครสมาชิกได้';
                        }
                    } catch (e) {
                        console.error('checkPhoneStatus error', e);
                        this.registerModal.phoneStatus = 'error';
                        this.registerModal.phoneStatusMessage = 'ตรวจสอบเบอร์ไม่สำเร็จ กรุณาลองใหม่';
                        this.registerModal.error = 'ตรวจสอบเบอร์ไม่สำเร็จ กรุณาลองใหม่';
                    } finally {
                        this.registerModal.checkingPhone = false;
                    }
                },
                async checkPhoneDuplicate(phoneDigits) {
                    try {
                        this.registerModal.checkingDuplicate = true;

                        const {data} = await axios.post(this.apiUrl('register/check-phone'), {
                            phone: phoneDigits,
                        });

                        if (data.bank) {
                            this.registerModal.error = 'เบอร์นี้สมัครสมาชิกแล้ว';
                        }
                    } catch (e) {
                        console.error('เช็คเบอร์ซ้ำไม่สำเร็จ', e);
                        this.registerModal.error = 'ไม่สามารถตรวจสอบเบอร์ได้ กรุณาลองใหม่';
                    } finally {
                        this.registerModal.checkingDuplicate = false;
                    }
                },
                onAccountNoInput() {
                    this.registerModal.error = null;
                    this.registerModal.accountStatus = null;
                    this.registerModal.accountStatusMessage = '';

                    const accDigits = (this.registerModal.account_no || '').replace(/\D/g, '');
                    this.registerModal.account_no = accDigits;

                    if (this.bankAccountCheckTimer) {
                        clearTimeout(this.bankAccountCheckTimer);
                    }

                    if (accDigits.length >= 10) {
                        this.bankAccountCheckTimer = setTimeout(() => {
                            this.checkBankAccount(accDigits);
                        }, 400);
                    }
                },
                async checkBankAccount(accDigits) {
                    this.registerModal.checkingAccount = true;
                    this.registerModal.accountStatus = null;
                    this.registerModal.accountStatusMessage = '';

                    try {
                        const {data} = await axios.post(this.apiUrl('register/check-bank'), {
                            bank_code: this.registerModal.bank_code,
                            account_no: accDigits,
                        });

                        if (data.success) {
                            // autofill ชื่อ–นามสกุล ถ้ามี
                            if (data.firstname) {
                                this.registerModal.name = data.firstname;
                            }
                            if (data.lastname) {
                                this.registerModal.surname = data.lastname;
                            }

                            this.registerModal.accountStatus = 'ok';
                            this.registerModal.accountStatusMessage =
                                'ตรวจสอบเลขบัญชีกับธนาคารเรียบร้อย';
                        } else {
                            this.registerModal.accountStatus = 'invalid';
                            this.registerModal.accountStatusMessage =
                                data.message || 'ไม่พบข้อมูลบัญชี';
                        }
                    } catch (e) {
                        console.error('checkBankAccount error', e);
                        this.registerModal.accountStatus = 'error';
                        this.registerModal.accountStatusMessage =
                            'ไม่สามารถตรวจสอบเลขบัญชีได้';
                        this.registerModal.error = 'ไม่สามารถตรวจสอบเลขบัญชีได้';
                    } finally {
                        this.registerModal.checkingAccount = false;
                    }
                },
                canControlRegister() {
                    const conv = this.selectedConversation;
                    if (!conv) return false;

                    // ต้องเป็นห้องที่รับเรื่องแล้วเท่านั้น
                    // if (conv.status !== 'assigned') return false;

                    // ต้องมีคนรับเรื่อง (assigned_employee_id)
                    // if (!conv.assigned_employee_id) return false;

                    // if (!this.currentEmployeeId) return false;

                    // อนุญาตเฉพาะคนที่เป็นคนรับเรื่อง
                    return true;
                    // return String(conv.assigned_employee_id) === String(this.currentEmployeeId);
                },
                /**
                 * โหลดรายการห้องแชต
                 * options.silent = true จะไม่โชว์ spinner (ใช้กับ auto-refresh)
                 */

                fetchConversations(page = 1, options = {}) {
                    const silent = options.silent === true;
                    const merge = options.merge === true;
                    const append = options.append === true;

                    if (!silent) {
                        this.loadingList = true;
                    }

                    return axios.get(this.apiUrl('conversations'), {
                        params: {
                            page: page,
                            status: this.filters.status,
                            q: this.filters.q,
                            account_id: this.filters.account_id,
                            scope: this.filters.scope,
                            per_page: this.pagination?.per_page || 20,
                        }
                    }).then(res => {
                        const body = res.data || {};
                        const newList = body.data || [];

                        // ===== อัปเดต pagination =====
                        const meta = body.meta || {};

                        const currentPage = meta.current_page != null ? meta.current_page : page;
                        const lastPage = meta.last_page != null ? meta.last_page : (this.pagination?.last_page || page);

                        this.pagination = Object.assign({}, this.pagination || {}, meta, {
                            current_page: currentPage,
                            last_page: lastPage,
                        });

                        console.log('[LineOA] fetchConversations result', {
                            page: page,
                            meta: this.pagination,
                            newCount: newList.length,
                        });

                        // ===== จัดการ conversations =====
                        if (append && Array.isArray(this.conversations)) {
                            const beforeLen = this.conversations.length;
                            const existing = this.conversations.slice();
                            const existingIds = new Set(
                                existing
                                    .filter(c => c && c.id != null)
                                    .map(c => c.id)
                            );

                            console.log('[LineOA] existing ids:', Array.from(existingIds));
                            console.log('[LineOA] new ids:', newList.map(i => i && i.id));

                            newList.forEach(item => {
                                if (!item || item.id == null) return;
                                if (!existingIds.has(item.id)) {
                                    existing.push(item);
                                }
                            });

                            const added = existing.length - beforeLen;

                            console.log('[LineOA] append conversations', {
                                before: beforeLen,
                                added,
                                after: existing.length,
                            });

                            this.conversations = existing;

                            // ❗ ถ้าหน้านี้ไม่มี id ใหม่เลย → ถือว่าถึงหน้าสุดท้ายแล้ว
                            if (added === 0) {
                                this.pagination.current_page = this.pagination.last_page;
                            }
                        } else if (merge && Array.isArray(this.conversations) && this.conversations.length > 0) {
                            // โหมด merge เดิม
                            const oldById = {};
                            this.conversations.forEach(conv => {
                                if (conv && conv.id != null) {
                                    oldById[conv.id] = conv;
                                }
                            });

                            const mergedList = newList.map(item => {
                                if (!item || item.id == null) {
                                    return item;
                                }
                                const old = oldById[item.id];
                                return old
                                    ? Object.assign({}, old, item)
                                    : item;
                            });

                            this.conversations = mergedList;

                            this.resortConversations();

                        } else {
                            // โหมดปกติ: แทนที่ทั้ง list
                            this.conversations = newList;
                        }
                        this.resortConversations();

                        // ===== สร้าง accountOptions จาก list ปัจจุบัน (เฉพาะตอนยังไม่ได้เลือก OA) =====
                        if (this.filters.account_id === null) {
                            const accounts = {};
                            this.conversations.forEach(conv => {
                                if (conv.line_account && conv.line_account.id) {
                                    accounts[conv.line_account.id] =
                                        conv.line_account.name || ('OA #' + conv.line_account.id);
                                }
                            });
                            this.accountOptions = Object.keys(accounts).map(id => ({
                                value: parseInt(id, 10),
                                text: accounts[id],
                            }));
                        }

                    }).catch(err => {
                        console.error('fetchConversations error', err);

                    }).finally(() => {
                        if (!silent) {
                            this.loadingList = false;
                        }
                        // กันกรณีใช้ loadingMore ใน onScroll
                        this.loadingMore = false;
                    });
                },


                /**
                 * เวลาเปลี่ยน filter เช่น status / scope / account / ค้นหา
                 * ให้รีโหลดหน้า 1 ใหม่
                 */
                async reloadConversations() {
                    this.pagination.current_page = 1;
                    this.conversations = [];           // เคลียร์ list เดิม (กันรู้สึกว่าข้อมูลเก่าแปะค้าง)
                    await this.fetchConversations(1);  // โหลดหน้าแรกใหม่
                },

                /**
                 * handler เวลา scroll list ซ้าย
                 */
                onConversationListScroll(event) {
                    const el = event.target;

                    console.log('[LineOA] scroll list left', el.scrollTop, el.scrollHeight, el.clientHeight);

                    if (this.loadingList || this.loadingMore) {
                        return;
                    }

                    const pag = this.pagination || {};
                    const current = pag.current_page || 1;
                    const last = pag.last_page || 1;

                    console.log('[LineOA] pagination', {current, last});

                    if (current >= last) {
                        return;
                    }

                    const threshold = 10;
                    const bottom = el.scrollTop + el.clientHeight;
                    const isNearBottom = bottom + threshold >= el.scrollHeight;

                    console.log('[LineOA] near bottom ?', {bottom, scrollHeight: el.scrollHeight, isNearBottom});

                    if (!isNearBottom) {
                        return;
                    }

                    const nextPage = current + 1;

                    console.log('[LineOA] load more page', nextPage);

                    this.loadingMore = true;

                    this.fetchConversations(nextPage, {
                        silent: true,
                        append: true,
                    }).catch(err => {
                        console.error('[LineOA] load more error', err);
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

                selectConversation(conv, options = {}) {
                    if (!conv) return;

                    const reloadMessages = options.reloadMessages !== false; // default = true
                    const previousId = this.currentActiveConversationId;

                    this.currentActiveConversationId = conv.id;
                    this.selectedConversation = conv;

                    // รีเซ็ต state ของโน้ตทุกครั้งที่เปลี่ยนห้อง
                    this.notes = [];
                    this.activeNote = {};
                    this.activeNoteIndex = 0;
                    this.notesError = null;

                    // ถ้าไม่ต้องโหลดข้อความใหม่ (เช่น แค่ refresh header)
                    if (!reloadMessages) {
                        this.loadNotes(); // ← ใช้เมทอดเดียวกับฝั่งอื่น
                        this.$nextTick(() => {
                            this.scrollToBottom();
                            this.autoFocusRef('replyBox');
                        });
                        return;
                    }

                    // โหลดข้อความ + แล้วค่อยโหลดโน้ต
                    this.fetchMessages(conv.id, {limit: 50, previous_id: previousId})
                        .then(() => {
                            this.loadNotes(); // ← ดึงโน้ตของห้องนี้
                            this.$nextTick(() => {
                                this.scrollToBottom();
                                this.autoFocusRef('replyBox');
                            });
                        });
                },

                autoFocusRef(refName) {
                    this.$nextTick(() => {
                        const r = this.$refs[refName];
                        if (!r) return;

                        if (typeof r.focus === 'function') {
                            try {
                                r.focus();
                                return;
                            } catch (_) {
                            }
                        }

                        const el =
                            r.$el?.querySelector?.('input,textarea') ||
                            (r instanceof HTMLElement ? r : null);

                        el?.focus?.();
                    });
                },

                fetchMessages(conversationId, options = {}) {
                    if (!conversationId) return Promise.resolve();

                    const silent = options.silent === true;
                    const isLoadOlder = !!options.before_id;

                    if (!silent) {
                        this.loadingMessages = true;
                    }

                    const params = {
                        limit: options.limit || 50,
                    };

                    if (options.before_id) {
                        params.before_id = options.before_id;
                    }

                    if (options.previous_id) {
                        params.previous_id = options.previous_id;
                    }

                    let prevScrollHeight = null;
                    let prevScrollTop = null;
                    const containerEl = this.$refs.messageContainer;

                    if (isLoadOlder && containerEl) {
                        prevScrollHeight = containerEl.scrollHeight;
                        prevScrollTop = containerEl.scrollTop;
                    }

                    return axios.get(this.apiUrl('conversations/' + conversationId), {params})
                        .then(res => {
                            const body = res.data || {};
                            const messages = body.messages || [];
                            const convFromServer = body.conversation || null;

                            if (isLoadOlder) {
                                this.messages = messages.concat(this.messages || []);
                            } else {
                                this.messages = messages;
                            }

                            if (convFromServer) {
                                if (this.selectedConversation && this.selectedConversation.id === convFromServer.id) {
                                    this.selectedConversation = Object.assign(
                                        {},
                                        this.selectedConversation,
                                        convFromServer
                                    );
                                } else if (!this.selectedConversation || this.selectedConversation.id === conversationId) {
                                    this.selectedConversation = convFromServer;
                                }
                            }

                            if (!isLoadOlder &&
                                this.selectedConversation &&
                                this.selectedConversation.id === conversationId
                            ) {
                                this.selectedConversation.unread_count = 0;

                                const idx = this.conversations.findIndex(c => c.id === conversationId);
                                if (idx !== -1) {
                                    const updated = Object.assign({}, this.conversations[idx], {
                                        unread_count: 0,
                                    });
                                    this.$set(this.conversations, idx, updated);
                                }
                            }

                            this.$nextTick(() => {
                                if (isLoadOlder && containerEl && prevScrollHeight !== null && prevScrollTop !== null) {
                                    const newScrollHeight = containerEl.scrollHeight;
                                    containerEl.scrollTop = newScrollHeight - prevScrollHeight + prevScrollTop;
                                    return;
                                }

                                if (!silent) {
                                    this.scrollToBottom();
                                }
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

                scrollToBottom() {
                    const el = this.$refs.messageContainer;
                    if (!el) return;
                    el.scrollTop = el.scrollHeight;
                },

                formatDateTime(dt) {
                    if (!dt) return '';
                    const d = new Date(dt);
                    if (isNaN(d.getTime())) {
                        return dt;
                    }
                    const pad = n => String(n).padStart(2, '0');
                    return d.getFullYear() + '-' +
                        pad(d.getMonth() + 1) + '-' +
                        pad(d.getDate()) + ' ' +
                        pad(d.getHours()) + ':' +
                        pad(d.getMinutes()) + ':' +
                        pad(d.getSeconds());
                },

                formatMessageDate(dateString) {
                    if (!dateString) return '';

                    const date = new Date(dateString);
                    const now = new Date();

                    const isToday =
                        date.getDate() === now.getDate() &&
                        date.getMonth() === now.getMonth() &&
                        date.getFullYear() === now.getFullYear();

                    if (isToday) {
                        // HH:mm
                        return date.toLocaleTimeString('th-TH', {
                            hour: '2-digit',
                            minute: '2-digit',
                        });
                    }

                    // แสดง 05 ธ.ค
                    return date.toLocaleDateString('th-TH', {
                        day: '2-digit',
                        month: 'short'
                    });
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
                        this.fetchConversations(this.pagination.current_page || 1, {silent: true, merge: true});
                        if (this.selectedConversation) {
                            this.fetchMessages(this.selectedConversation.id, {limit: 50, silent: true});
                        }
                    }, 600000); // ตอนนี้มี realtime แล้ว ใช้ sync ระยะยาว
                },

                stopAutoRefresh() {
                    if (this.autoRefreshTimer) {
                        clearInterval(this.autoRefreshTimer);
                        this.autoRefreshTimer = null;
                    }
                },

                compareConversation(a, b) {
                    const isPinned = (v) => (
                        v === true ||
                        v === 1 || v === '1' ||
                        v === 'Y' || v === 'y' ||
                        v === 'true'
                    );

                    const toTs = (val) => {
                        if (!val) return 0;

                        // ถ้าเป็นตัวเลข timestamp อยู่แล้ว
                        if (typeof val === 'number') return val;
                        if (typeof val === 'string' && /^\d+$/.test(val)) return Number(val);

                        // รองรับ ISO / "YYYY-MM-DD HH:mm:ss"
                        const t = Date.parse(val);
                        return Number.isFinite(t) ? t : 0;
                    };

                    const ap = isPinned(a && a.is_pinned) ? 1 : 0;
                    const bp = isPinned(b && b.is_pinned) ? 1 : 0;
                    if (ap !== bp) return bp - ap;

                    const at = toTs(a && a.last_message_at);
                    const bt = toTs(b && b.last_message_at);
                    if (at !== bt) return bt - at;

                    const aid = a && a.id ? Number(a.id) : 0;
                    const bid = b && b.id ? Number(b.id) : 0;
                    return bid - aid;
                },

                // auto-search: debounce ตอนพิมพ์ค้นหา
                onSearchInput() {
                    if (this.searchDelayTimer) {
                        clearTimeout(this.searchDelayTimer);
                    }
                    this.searchDelayTimer = setTimeout(() => {
                        this.fetchConversations(1, {silent: true, merge: false});
                    }, 500);
                },

                onSelectImage(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    this.$refs.imageInput.value = '';

                    if (!file.type.startsWith('image/')) {

                        const msg = 'กรุณาเลือกไฟล์รูปภาพเท่านั้น';
                        this.showAlert({
                            success: false,
                            message: msg
                        });

                        return;
                    }
                    if (file.size > 5 * 1024 * 1024) {
                        const msg = 'ไฟล์ใหญ่เกินไป สูงสุด 5MB';

                        this.showAlert({
                            success: false,
                            message: msg
                        });

                        return;
                    }

                    this.sendImage(file);
                },

                sendImage(file) {
                    if (!this.selectedConversation || this.uploadingImage) return;

                    if (!this.canReply) {
                        alert('ห้องนี้ยังไม่ได้รับเรื่อง หรือคุณไม่ได้เป็นผู้รับเรื่อง ไม่สามารถตอบลูกค้าได้');
                        return;
                    }

                    const convId = this.selectedConversation.id;
                    this.uploadingImage = true;

                    const form = new FormData();
                    form.append('image', file);

                    // 👇 ถ้าเลือก “ตอบกลับข้อความ” ไว้ ให้ส่ง id ต้นทางไปด้วย
                    if (this.replyingToMessage && this.replyingToMessage.id) {
                        form.append('reply_to_message_id', this.replyingToMessage.id);
                    }

                    axios.post(this.apiUrl('conversations/' + convId + '/reply-image'), form, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    }).then(res => {
                        const msg = res.data && res.data.data ? res.data.data : null;
                        if (msg) {
                            this.messages.push(msg);

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

                            // ✅ ส่งรูปเสร็จแล้ว ให้ถือว่าตอบกลับเสร็จ เคลียร์ state
                            this.replyingToMessage = null;

                            this.$nextTick(() => this.scrollToBottom());
                        }
                    }).catch(err => {
                        console.error('sendImage error', err);

                        const msg =
                            err?.response?.data?.message ??
                            err?.response?.data?.msg ??
                            err?.response?.data?.error ??
                            'ส่งรูปไม่สำเร็จ กรุณาลองใหม่';

                        this.showAlert({
                            success: false,
                            message: msg
                        });
                    }).finally(() => {
                        this.uploadingImage = false;
                    });
                },


                // ====== สร้าง preview จาก message เวลา event ไม่ส่ง last_message มา ======
                buildPreviewFromMessage(msg) {
                    if (!msg) return '';
                    if (msg.type === 'text' && msg.text) {
                        const text = msg.text;
                        return text.length > 50 ? text.substr(0, 45) + '...' : text;
                    }
                    return '[' + (msg.type || 'message') + ']';
                },

                // ====== URL สติ๊กเกอร์ LINE ======
                stickerUrl(msg) {
                    if (!msg) return null;

                    let payload = msg.payload || {};

                    // กันเคส payload เก็บเป็น string JSON
                    if (typeof payload === 'string') {
                        try {
                            payload = JSON.parse(payload);
                        } catch (e) {
                            // ถ้า parse ไม่ได้ก็ปล่อยไป ใช้ object เดิม
                        }
                    }

                    // 1) พยายามดึงแบบ inbound: payload.message
                    let message = payload.message || payload || {};

                    let pkg =
                        message.packageId ||
                        message.package_id ||
                        null;

                    let sid =
                        message.stickerId ||
                        message.sticker_id ||
                        null;

                    let type =
                        message.stickerResourceType ||
                        null;

                    // 2) ถ้ายังไม่มี pkg/sid ให้ fallback ไปดูใน meta.sticker (เคส outbound agent)
                    if ((!pkg || !sid) && msg.meta) {
                        let meta = msg.meta;
                        if (typeof meta === 'string') {
                            try {
                                meta = JSON.parse(meta);
                            } catch (e) {
                                // ignore
                            }
                        }

                        const stickerMeta = meta && meta.sticker ? meta.sticker : null;

                        if (stickerMeta) {
                            pkg =
                                pkg ||
                                stickerMeta.packageId ||
                                stickerMeta.package_id ||
                                null;

                            sid =
                                sid ||
                                stickerMeta.stickerId ||
                                stickerMeta.sticker_id ||
                                null;

                            type =
                                type ||
                                stickerMeta.stickerResourceType ||
                                null;
                        }
                    }

                    if (!pkg || !sid) return null;

                    // default type
                    type = type || 'STATIC';

                    // จากนี่ไปเหมือนเดิม
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
                                vm.playNewMessageSound(e.message);
                            }
                        })
                        .listen('.LineOAChatConversationUpdated', (e) => {
                            const conv = e.conversation || {};
                            if (!conv || !conv.id) {
                                return;
                            }

                            const isActive =
                                this.selectedConversation &&
                                this.selectedConversation.id === conv.id;

                            if (isActive) {
                                conv.unread_count = 0;
                            }
                            console.log('[Echo] conv keys', Object.keys(conv), conv);
                            this.updateOrInsertConversation(conv);

                            if (isActive) {
                                this.selectedConversation = Object.assign(
                                    {},
                                    this.selectedConversation,
                                    conv
                                );
                            }
                        })
                        .listen('.LineOAConversationAssigned', (e) => {
                            vm.handleConversationAssigned(e);
                        })
                        .listen('.LineOAConversationClosed', (e) => {
                            vm.handleConversationClosed(e);
                        })
                        .listen('.LineOAConversationOpen', (e) => {
                            vm.handleConversationOpen(e);
                        })
                        .listen('.LineOAChatTypingUpdated', (e) => {
                            const conversationId = e.conversation_id;
                            if (!conversationId) return;

                            const idx = this.conversations.findIndex(c => c.id === conversationId);
                            if (idx === -1) return; // ถ้าอยากให้เด้งแม้ยังไม่มีใน list ก็สามารถ fetch/insert เพิ่มได้

                            const current = this.conversations[idx];
                            const typing = Object.assign({}, current.typing || {});

                            // เก็บชื่อคนพิมพ์ + timestamp เพื่อ auto-clear
                            if (e.is_typing) {
                                typing.employee_id = e.employee_id;
                                typing.employee_name = e.employee_name;
                                typing.at = e.at || new Date().toISOString();
                            } else {
                                // stop
                                delete typing.employee_id;
                                delete typing.employee_name;
                                delete typing.at;
                            }

                            this.$set(this.conversations, idx, {
                                ...current,
                                typing,
                            });

                            // auto-clear ถ้าไม่มี update ใหม่ใน 5 วิ (กันกรณีปิดแท็บ/เน็ตหลุด)
                            if (e.is_typing) {
                                const stamp = typing.at;
                                setTimeout(() => {
                                    const i2 = this.conversations.findIndex(c => c.id === conversationId);
                                    if (i2 === -1) return;
                                    const t2 = (this.conversations[i2].typing || {});
                                    if (t2.at === stamp) {
                                        const clone = {...this.conversations[i2]};
                                        clone.typing = {};
                                        this.$set(this.conversations, i2, clone);
                                    }
                                }, 5000);
                            }
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

                resortConversations() {
                    if (!Array.isArray(this.conversations) || this.conversations.length <= 1) return;
                    const next = this.conversations.slice().sort((a, b) => this.compareConversation(a, b));
                    this.conversations = next;
                },

                updateOrInsertConversation(conv) {
                    if (!conv || !conv.id) return;

                    const id = conv.id;
                    const idx = this.conversations.findIndex(c => c.id === id);

                    if (idx !== -1) {
                        this.$set(this.conversations, idx, {
                            ...this.conversations[idx],
                            ...conv
                        });
                    } else {
                        // อย่า unshift ตรง ๆ เพราะมันทำให้ pinned ตก / ลำดับเพี้ยน
                        this.conversations.push(conv);
                    }

                    this.resortConversations();
                },

                handleRealtimeIncoming(e) {
                    if (!e || !e.conversation_id || !e.message) {
                        return;
                    }

                    const convId = e.conversation_id;
                    const newMsg = e.message;
                    const newConvRaw = e.conversation || {};

                    const idx = this.conversations.findIndex(c => c.id === convId);
                    const existing = idx !== -1 ? this.conversations[idx] : null;

                    const isActive = this.selectedConversation && this.selectedConversation.id === convId;

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

                    let unread;
                    if (isActive) {
                        unread = 0;
                    } else if (newConvRaw.unread_count != null) {
                        unread = newConvRaw.unread_count;
                    } else {
                        const oldUnread = existing && existing.unread_count ? existing.unread_count : 0;
                        unread = oldUnread + 1;
                    }

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

                    if (idx !== -1) {
                        this.$set(this.conversations, idx, mergedConv);
                    } else if (this.filters.status === 'open') {
                        // อย่า unshift ตรง ๆ เพราะจะทำให้ pinned ตก / ลำดับเพี้ยน
                        this.conversations.push(mergedConv);
                        this.pagination.total += 1;
                    }

                    // รีเรียงทุกครั้งให้ลำดับนิ่ง (pinned อยู่บนสุดเสมอ)
                    this.resortConversations();

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

                handleConversationOpen(e) {
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
                    this.fetchConversations(1, {silent: true, merge: false});
                },
                onMemberModalShown() {
                    this.autoFocusRef('memberIdInput');
                },
                onMemberModalHidden() {
                    this.$nextTick(() => {
                        this.autoFocusRef('replyBox');
                    });
                },
                onQuickReplyModalHidden() {
                    this.$nextTick(() => {
                        this.autoFocusRef('replyBox');
                    });
                },
                onBalanceModalHidden() {
                    this.$nextTick(() => {
                        this.autoFocusRef('replyBox');
                    });
                },
                // ====== modal: ผูก contact กับ member ======
                openMemberModal() {
                    if (!this.selectedConversation || !this.selectedConversation.contact) {
                        return;
                    }
                    const conv = this.selectedConversation.contact;

                    this.memberModal.display_name = conv.display_name;
                    this.memberModal.error = '';
                    this.memberModal.member = null;
                    this.memberModal.member_id = conv.member_username || '';

                    this.$nextTick(() => {
                        if (this.$refs.memberModal) {
                            this.$refs.memberModal.show();
                        }
                    });
                },

                resetMemberModal() {
                    this.memberModal = {
                        display_name: '',
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
                            display_name: this.memberModal.display_name,
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

                    axios.post(this.apiUrl('contacts/' + contactId + '/attach-member'), {
                        member_id: member.id,
                        display_name: member.display_name,
                    }).then(res => {
                        const data = res.data || {};
                        const contact = data.data || data.contact || null;

                        if (contact) {
                            this.selectedConversation.contact = contact;
                        } else {
                            const c = this.selectedConversation.contact;
                            c.member_id = member.id;
                            c.member_username = member.username || c.member_username;
                            c.member_mobile = member.mobile || c.member_mobile;
                            c.display_name = member.display_name || c.display_name;
                            this.selectedConversation.contact = Object.assign({}, c);
                        }

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

                async openBalanceModal() {
                    if (!this.selectedConversation) {
                        return;
                    }

                    this.balanceLoading = true;
                    this.balanceData = null;

                    try {
                        const res = await axios.get(this.apiUrl('get-balance'), {
                            params: {
                                conversation_id: this.selectedConversation.id,
                            },
                        });

                        if (!res.data || !res.data.ok) {
                            const msg = res.data && res.data.message
                                ? res.data.message
                                : 'ไม่สามารถดึงยอดเงินได้';
                            // this.showToastError && this.showToastError(msg);
                            this.showAlert({success: false, message: msg});
                            return;
                        }

                        this.balanceData = res.data.data || null;

                        // แสดง popup แบบง่าย ๆ: ใช้ b-modal
                        if (this.$refs.balanceModal) {
                            this.$refs.balanceModal.show();
                        } else {
                            // กันไว้ ถ้าไม่มี modal จริง ๆ ก็ alert ไปก่อน
                            // alert(
                            //     `ยอดเงินคงเหลือ: ${this.balanceData.balance_text} บาท`
                            // );

                            this.showAlert({
                                success: true,
                                message: `ยอดเงินคงเหลือ: ${this.balanceData.balance_text} บาท`
                            });
                        }
                    } catch (e) {
                        console.error(e);
                        this.showAlert({success: false, message: 'เกิดข้อผิดพลาดในการดึงยอดเงิน'});
                        // this.showToastError && this.showToastError('เกิดข้อผิดพลาดในการดึงยอดเงิน');
                    } finally {
                        this.balanceLoading = false;
                    }
                },

                acceptConversation() {
                    if (!this.selectedConversation) return;

                    const id = this.selectedConversation.id;

                    axios.post(this.apiUrl('conversations/' + id + '/accept'))
                        .then(res => {
                            const conv = res.data.data || res.data.conversation || null;
                            if (!conv) return;

                            this.updateConversationLocal(conv);

                            this.fetchConversations(1, {silent: true, merge: true})
                                .then(() => {
                                    const idx = this.conversations.findIndex(c => c.id === conv.id);
                                    if (idx !== -1) {
                                        this.selectConversation(this.conversations[idx], {reloadMessages: false});
                                    }
                                });
                        })
                        .catch(err => {
                            console.error('acceptConversation error', err);
                            const msg =
                                err?.response?.data?.message ??
                                err?.response?.data?.msg ??
                                err?.response?.data?.error ??
                                'รับเรื่องไม่สำเร็จ';

                            this.showAlert({success: false, message: msg});
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

                            const msg =
                                err?.response?.data?.message ??
                                err?.response?.data?.msg ??
                                err?.response?.data?.error ??
                                'ไม่สามารถล็อกห้องได้';

                            this.showAlert({success: false, message: msg});
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
                async closeConversation() {
                    if (!this.selectedConversation) return;

                    const id = this.selectedConversation.id;

                    // const ok = await this.showConfirm({message: 'ยืนยันปิดเคสนี้ ?'});
                    // if (!ok) return;

                    try {
                        const {data} = await axios.post(this.apiUrl('conversations/' + id + '/close'));
                        const conv = data.data || null;
                        if (!conv) return;

                        // 1) อัปเดตห้องปัจจุบัน + list ซ้าย
                        this.updateConversationLocal(conv);

                        // 2) เปลี่ยน filter ไปแท็บปิดเคส
                        // this.filters.status = 'closed';

                        // 3) โหลด list ใหม่แบบ merge แล้วเลือกห้องเดิม
                        await this.fetchConversations(1, {silent: true, merge: false});

                        // const idx = this.conversations.findIndex(c => c.id === conv.id);
                        // if (idx !== -1) {
                        //     this.selectConversation(this.conversations[idx], {reloadMessages: false});
                        // }
                    } catch (err) {
                        const msg =
                            err?.response?.data?.message ??
                            err?.response?.data?.msg ??
                            err?.response?.data?.error ??
                            'ปิดเคสไม่สำเร็จ';

                        this.showAlert({success: false, message: msg});

                    } finally {
                        this.autoFocusRef('replyBox');
                    }
                },

                async openConversation() {
                    if (!this.selectedConversation) return;

                    const id = this.selectedConversation.id;

                    const ok = await this.showConfirm({message: 'ยืนยันเปิดเคสนี้ ?'});
                    if (!ok) return;

                    try {
                        const {data} = await axios.post(this.apiUrl('conversations/' + id + '/open'));
                        const conv = data.data || null;
                        if (!conv) return;

                        this.updateConversationLocal(conv);
                        this.filters.status = 'open';

                        await this.fetchConversations(1, {silent: true, merge: true});

                        const idx = this.conversations.findIndex(c => c.id === conv.id);
                        if (idx !== -1) {
                            this.selectConversation(this.conversations[idx], {reloadMessages: false});
                        }
                    } catch (err) {
                        const msg =
                            err?.response?.data?.message ??
                            err?.response?.data?.msg ??
                            err?.response?.data?.error ??
                            'เปิดเคสไม่สำเร็จ';

                        this.showAlert({success: false, message: msg});
                    } finally {
                        this.autoFocusRef('replyBox');
                    }
                },
                onRegisterModalShown() {
                    this.autoFocusRef('registerPhoneInput');
                },
                // ====== สมัครสมาชิก / ยกเลิกสมัคร / เติมเงิน ======
                // ====== Draft Register (localStorage + TTL) ======
                registerDraftTtlMs() {
                    // อายุสั้น ๆ: 1 นาที (ปรับได้)
                    return 1 * 60 * 1000;
                },

                getRegisterDraftKey() {
                    const conv = this.selectedConversation || null;
                    const cid = conv && conv.id ? String(conv.id) : 'no-conv';
                    // แยกตาม conversation กัน draft ปนกัน
                    return `lineoa:register_draft:v1:conv:${cid}`;
                },

                buildRegisterDraftPayload() {
                    // เลือกเก็บเฉพาะฟิลด์ที่จำเป็น (ไม่ต้องเก็บ loading/error)
                    const m = this.registerModal || {};
                    return {
                        registerMode: this.registerMode || 'phone',

                        username: m.username || '',
                        phone: m.phone || '',
                        bank_code: m.bank_code || '',
                        account_no: m.account_no || '',
                        name: m.name || '',
                        surname: m.surname || '',

                        // ถ้าคุณอยากเก็บ status ด้วยก็เพิ่มได้ แต่ผมไม่แนะนำให้ยึดติด status เก่า
                        // usernameStatus: m.usernameStatus || null,
                        // phoneStatus: m.phoneStatus || null,
                    };
                },

                saveRegisterDraft() {
                    try {
                        const key = this.getRegisterDraftKey();
                        const now = Date.now();

                        const record = {
                            v: 1,
                            created_at: now,
                            expires_at: now + this.registerDraftTtlMs(),
                            payload: this.buildRegisterDraftPayload(),
                        };

                        localStorage.setItem(key, JSON.stringify(record));
                    } catch (e) {
                        // localStorage อาจเต็ม/ถูกบล็อก (private mode บางแบบ)
                        console.warn('[LineOA] saveRegisterDraft failed', e);
                    }
                },

                loadRegisterDraft() {
                    try {
                        const key = this.getRegisterDraftKey();
                        const raw = localStorage.getItem(key);
                        if (!raw) return null;

                        const record = JSON.parse(raw);
                        if (!record || !record.expires_at || Date.now() > Number(record.expires_at)) {
                            localStorage.removeItem(key);
                            return null;
                        }

                        return record.payload || null;
                    } catch (e) {
                        console.warn('[LineOA] loadRegisterDraft failed', e);
                        return null;
                    }
                },

                clearRegisterDraft() {
                    try {
                        const key = this.getRegisterDraftKey();
                        localStorage.removeItem(key);
                    } catch (e) {
                        console.warn('[LineOA] clearRegisterDraft failed', e);
                    }
                },

                openRegisterModal() {
                    if (!this.selectedConversation) return;

                    // เคลียร์สถานะ UI ที่ไม่ควรจำ
                    this.registerModal.error = '';
                    this.registerModal.loading = false;

                    // 1) พยายามโหลด draft ก่อน
                    const draft = this.loadRegisterDraft();

                    if (draft) {
                        // เอา draft กลับมาใส่ฟอร์ม
                        // ระวัง: ไม่ควร set registerMode ทับแบบมั่ว ถ้าคุณต้องการล็อกโหมดไว้ก็ทำได้
                        if (draft.registerMode) {
                            this.registerMode = draft.registerMode;
                        }

                        this.registerModal.username = draft.username || '';
                        this.registerModal.phone = draft.phone || '';
                        this.registerModal.bank_code = draft.bank_code || '';
                        this.registerModal.account_no = draft.account_no || '';
                        this.registerModal.name = draft.name || '';
                        this.registerModal.surname = draft.surname || '';
                    } else {
                        // 2) ถ้าไม่มี draft ค่อยรีเซ็ตเหมือนเดิม
                        this.registerModal.username = '';
                        this.registerModal.phone = '';
                        this.registerModal.bank_code = '';
                        this.registerModal.account_no = '';
                        this.registerModal.name = '';
                        this.registerModal.surname = '';
                    }

                    this.$nextTick(() => {
                        if (this.$refs.registerModal) {
                            this.$refs.registerModal.show();
                        }
                    });
                },


                async cancelRegisterFlow() {
                    if (!this.selectedConversation) return;

                    const ok = await this.showConfirm({
                        message: 'ยืนยันยกเลิกการสมัครกับบอทสำหรับห้องนี้ ?'
                    });
                    if (!ok) return;

                    try {
                        await axios.post(
                            this.apiUrl('conversations/' + this.selectedConversation.id + '/cancel-register')
                        );

                        this.selectedConversation.is_registering = false;
                        this.updateConversationLocal(this.selectedConversation);

                    } catch (err) {
                        const msg =
                            err?.response?.data?.message ??
                            err?.response?.data?.msg ??
                            err?.response?.data?.error ??
                            'ไม่สามารถยกเลิกการสมัครได้';

                        this.showAlert({success: false, message: msg});

                    } finally {
                        this.autoFocusRef('replyBox');
                    }
                },

                submitRegisterByStaff() {
                    if (this.registerModal.loading) {
                        return;
                    }

                    // โหมดการสมัคร: 'phone' หรือ 'username'
                    const mode = this.registerMode || 'phone';

                    // ถ้ามี canSubmitRegister ก็ใช้เป็นด่านแรกเหมือนเดิม
                    if (typeof this.canSubmitRegister !== 'undefined' && !this.canSubmitRegister) {
                        return;
                    }

                    // เคลียร์ error เดิมก่อน
                    this.registerModal.error = null;

                    // ด่านตรวจเพิ่มกัน user/pass ที่ไม่ผ่าน แต่ดันหลุดมา submit
                    if (mode === 'username') {
                        // ต้องมี username
                        if (!this.registerModal.username) {
                            this.registerModal.error = 'กรุณากรอกยูสเซอร์เนม';
                            return;
                        }

                        // ถ้าเคยยิงเช็คแล้ว และสถานะไม่ใช่ ok ให้บล็อก
                        if (
                            this.registerModal.usernameStatus &&
                            this.registerModal.usernameStatus !== 'ok'
                        ) {
                            this.registerModal.error =
                                this.registerModal.usernameStatusMessage ||
                                'ยูสเซอร์เนมนี้ไม่สามารถใช้สมัครได้';
                            return;
                        }
                    } else if (mode === 'phone') {
                        // กรณีใช้เบอร์เป็นตัวหลัก จะกันเคสเบอร์ไม่ ok นิดนึง
                        if (!this.registerModal.phone) {
                            this.registerModal.error = 'กรุณากรอกเบอร์โทร';
                            return;
                        }

                        if (
                            this.registerModal.phoneStatus &&
                            this.registerModal.phoneStatus !== 'ok'
                        ) {
                            this.registerModal.error =
                                this.registerModal.phoneStatusMessage ||
                                'เบอร์โทรนี้ไม่สามารถใช้สมัครได้';
                            return;
                        }
                    }

                    const m = this.registerModal;

                    const payload = {
                        // บอก backend ให้รู้ว่าโหมดอะไร
                        register_mode: mode,          // 'phone' หรือ 'username'

                        // ถ้าเป็นโหมด username ก็ส่ง username ไปด้วย
                        username: mode === 'username' ? (m.username || null) : null,

                        // เบอร์ยังส่งไปเหมือนเดิม (จะบังคับหรือไม่ให้ backend เป็นคนตัดสินต่อ)
                        phone: m.phone || null,

                        bank_code: m.bank_code,
                        account_no: m.account_no,
                        name: m.name,
                        surname: m.surname,
                    };

                    const conv = this.selectedConversation || null;
                    if (conv) {
                        payload.conversation_id = conv.id || null;
                        payload.line_contact_id =
                            conv.line_contact_id ||
                            conv.contact_id ||
                            (conv.contact ? conv.contact.id : null) ||
                            null;

                        payload.line_account_id =
                            conv.line_account_id ||
                            conv.account_id ||
                            (conv.account ? conv.account.id : null) ||
                            null;
                    }

                    this.registerModal.loading = true;

                    axios.post(this.apiUrl('register/member'), payload)
                        .then((response) => {
                            const data = response.data || {};

                            if (!data.success) {
                                this.registerModal.error = data.message || 'สมัครสมาชิกไม่สำเร็จ';
                                this.showAlert(data);
                                return;
                            }

                            this.showAlert(data);

                            this.clearRegisterDraft();

                            if (conv && data.member) {
                                // ถ้าจะ sync contact/conversation เพิ่มเติม ก็ทำต่อได้ที่นี่
                            }

                            if (this.$refs.registerModal) {
                                this.$refs.registerModal.hide();
                            }
                        })
                        .catch((error) => {
                            console.error('[LineOA] submitRegisterByStaff error', error);
                            this.registerModal.error = 'ไม่สามารถสมัครสมาชิกได้ กรุณาลองใหม่';
                        })
                        .finally(() => {
                            this.registerModal.loading = false;
                        });
                },

                onRegisterModalHidden() {
                    // รอ 1 tick ให้ DOM stable
                    this.$nextTick(() => {
                        this.autoFocusRef('replyBox');
                    });
                },
                onTopupModalHidden() {
                    this.$nextTick(() => {
                        this.autoFocusRef('replyBox');
                    });
                },
                openMemberFromConversation() {
                    if (!this.selectedConversation) return;
                    const conv = this.selectedConversation.contact || {};

                    if (window.memberEditApp && typeof window.memberEditApp.memberEditOpen === 'function') {
                        window.memberEditApp.memberEditOpen(conv.member_id);
                    }
                },
                openTopupModal() {
                    if (!this.selectedConversation) return;

                    this.topupModal.error = '';
                    this.topupModal.loading = false;
                    this.topupModal.selectedItem = null;

                    const c = this.selectedConversation.contact || {};

                    this.topupModal.memberSearch = c.member_username || '';
                    this.topupModal.member = c.member_username ? {
                        username: c.member_username,
                        mobile: c.member_mobile,
                        name: c.member_name,
                        bank_name: c.member_bank_name,
                        acc_no: c.member_acc_no,
                    } : null;

                    this.topupModal.bank = '';
                    this.topupModal.amount = null;

                    this.$nextTick(() => {
                        if (this.$refs.topupModal) {
                            this.$refs.topupModal.show();
                        }
                    });
                },

                selectTopupItem(item) {
                    this.topupModal.selectedItem = item || null;
                },

                searchTopupMember() {
                    if (!this.topupModal.memberSearch) {
                        this.topupModal.error = 'กรุณากรอกไอดีสมาชิกก่อนค้นหา';
                        return;
                    }
                    this.topupModal.error = '';
                    console.log('[LineOA] searchTopupMember', this.topupModal.memberSearch);
                },

                submitTopup() {
                    if (this.topupModal.loading) return;

                    if (!this.topupModal.member && !this.topupModal.memberSearch) {
                        this.topupModal.error = 'กรุณาระบุไอดีสมาชิก';
                        return;
                    }
                    if (!this.topupModal.selectedItem) {
                        if (!this.topupModal.bank) {
                            this.topupModal.error = 'กรุณากรอกธนาคารที่เติม';
                            return;
                        }
                        if (!this.topupModal.amount || this.topupModal.amount <= 0) {
                            this.topupModal.error = 'กรุณากรอกจำนวนเงินที่ถูกต้อง';
                            return;
                        }
                    }

                    this.topupModal.error = '';
                    this.topupModal.loading = true;

                    console.log('[LineOA] submitTopup payload', this.topupModal);

                    setTimeout(() => {
                        this.topupModal.loading = false;
                        if (this.$refs.topupModal) {
                            this.$refs.topupModal.hide();
                        }
                    }, 500);
                },
                showAlert(data) {
                    const hasSuccess = typeof (data?.success) !== 'undefined';
                    const ok = hasSuccess && data.success === true;

                    const msg = data?.message
                        ?? data?.msg
                        ?? (hasSuccess
                            ? (ok ? 'ทำรายการสำเร็จ' : 'ทำรายการไม่สำเร็จ')
                            : 'แจ้งเตือนจากระบบ');

                    const variant = hasSuccess
                        ? (ok ? 'success' : 'danger')
                        : 'info';

                    this.$bvModal.msgBoxOk(msg, {
                        title: 'สถานะการทำรายการ',
                        okVariant: variant,
                        size: 'sm',
                        buttonSize: 'sm',
                        centered: true
                    });
                },
                async showConfirm(data) {
                    const hasSuccess = typeof (data?.success) !== 'undefined';
                    const ok = hasSuccess && data.success === true;

                    const msg = data?.message
                        ?? data?.msg
                        ?? (hasSuccess
                            ? (ok ? 'ทำรายการสำเร็จ' : 'ทำรายการไม่สำเร็จ')
                            : 'ยืนยันดำเนินการต่อหรือไม่');

                    const variant = hasSuccess
                        ? (ok ? 'success' : 'danger')
                        : 'info';

                    try {
                        const confirmed = await this.$bvModal.msgBoxConfirm(msg, {
                            title: 'ยืนยันการดำเนินการ',
                            size: 'sm',
                            buttonSize: 'sm',
                            okTitle: 'ยืนยัน',
                            cancelTitle: 'ยกเลิก',
                            okVariant: variant,
                            cancelVariant: 'danger',
                            centered: true,
                            noCloseOnBackdrop: true,
                            noCloseOnEsc: true,
                            returnFocus: true,
                        });

                        return confirmed === true;
                    } catch (e) {
                        return false;
                    }
                },
                openRefillModal() {
                    if (!this.selectedConversation) return;
                    const conv = this.selectedConversation.contact || {};
                    var prefill = null;
                    prefill = {
                        member_id: conv.member_id || null,
                        member_username: conv.member_username || null,
                    };

                    if (window.memberRefillApp && typeof window.memberRefillApp.openRefillModal === 'function') {
                        window.memberRefillApp.openRefillModal(prefill);
                    }
                },
                openQuickReplyCreateModal() {
                    // reset form ทุกครั้งก่อนเปิด
                    this.resetQuickReplyForm();
                    this.$nextTick(() => {
                        this.$refs.quickReplyAddModal && this.$refs.quickReplyAddModal.show();
                    });
                },


                /**
                 * ใส่ placeholder ลงใน textarea quick reply (ตำแหน่งเคอร์เซอร์)
                 */
                insertQuickReplyPlaceholder(token) {
                    const current = this.quickReplyForm.message || '';

                    let el = this.$refs.quickReplyMessageInput;
                    if (el && el.$el) {
                        el = el.$el;
                    }

                    if (!el || !el.tagName || el.tagName.toLowerCase() !== 'textarea') {
                        this.quickReplyForm.message = current + token;
                        return;
                    }

                    const start = el.selectionStart != null ? el.selectionStart : current.length;
                    const end = el.selectionEnd != null ? el.selectionEnd : current.length;

                    const before = current.substring(0, start);
                    const after = current.substring(end);

                    this.quickReplyForm.message = before + token + after;

                    this.$nextTick(() => {
                        el.focus();
                        const pos = start + token.length;
                        el.selectionStart = pos;
                        el.selectionEnd = pos;
                    });
                },


                canAssignConversation() {
                    // ถ้ามี logic permission จริงให้มาเช็กที่นี่ เช่น เช็ก role / permission
                    // ตอนนี้เอาเบา ๆ: มีห้อง และ user login admin อยู่ก็ให้เปลี่ยนได้
                    return !!this.selectedConversation;
                },

                openAssigneeModal() {
                    if (!this.selectedConversation || !this.canAssignConversation()) {
                        return;
                    }

                    // preset ค่าเริ่มต้นเป็นคนเดิม (ถ้ามี)
                    this.selectedAssigneeId = this.selectedConversation.assigned_employee_id || null;

                    this.assigneeSearch = '';
                    this.assigneeLoading = true;

                    this.loadAssignees()
                        .then(() => {
                            this.$refs.assigneeModal.show();
                        })
                        .finally(() => {
                            this.assigneeLoading = false;
                        });
                },

                async loadAssignees() {
                    // แนะนำให้มี route backend ประมาณนี้:
                    // GET /line-oa/assignees   หรือ   /line-oa/conversations/{conversation}/assignees
                    // ให้คืน data: [{ id, code, name, user_name, role_name }, ...]
                    try {
                        const res = await axios.get(this.apiUrl('assignees'));
                        const body = res.data || {};
                        const items = body.data || body.employees || [];


                        const base = [{
                            code: null,
                            user_name: '',
                            display: 'ไม่มีผู้รับผิดชอบ',
                            sub: '',
                            role: '',
                        }];

                        this.assigneeOptions = base.concat(
                            items.map(e => {
                                const name = e.name || e.full_name || e.user_name || e.code || ('พนักงาน #' + e.code);

                                return {
                                    code: e.code || '',
                                    user_name: e.user_name || '',
                                    display: name,
                                    sub: e.code
                                        ? (e.user_name ? `${e.code} • ${e.user_name}` : e.code)
                                        : (e.user_name || ''),
                                    role: e.role_name || e.role || '',
                                };
                            })
                        );
                    } catch (e) {
                        console.error('[LineOA] loadAssignees error', e);
                        this.assigneeOptions = [];
                        this.showAlert && this.showAlert({
                            success: false,
                            message: 'โหลดรายชื่อผู้รับผิดชอบไม่สำเร็จ',
                        });
                    }
                },

                async saveAssignee() {
                    if (!this.selectedConversation) {
                        return;
                    }

                    this.savingAssignee = true;

                    try {
                        const convId = this.selectedConversation.id;
                        // แนะนำ route backend:
                        // POST /line-oa/conversations/{conversation}/assign
                        const res = await axios.post(
                            this.apiUrl('conversations/' + convId + '/assign'),
                            {employee_id: this.selectedAssigneeId}
                        );

                        const conv = res.data.data || res.data.conversation || null;
                        if (!conv) return;

                        this.updateConversationLocal(conv);

                        this.fetchConversations(1, {silent: true, merge: true})
                            .then(() => {
                                const idx = this.conversations.findIndex(c => c.id === conv.id);
                                if (idx !== -1) {
                                    this.selectConversation(this.conversations[idx], {reloadMessages: false});
                                }
                            });


                        this.$refs.assigneeModal.hide();

                    } catch (e) {
                        console.error('[LineOA] saveAssignee error', e);
                        this.showAlert && this.showAlert({
                            success: false,
                            message: 'เกิดข้อผิดพลาดระหว่างบันทึกผู้รับผิดชอบ',
                        });
                    } finally {
                        this.savingAssignee = false;
                    }
                },

                startReply(msg) {
                    this.replyingToMessage = {
                        id: msg.id,
                        text: msg.text || '',
                        direction: msg.direction,
                        source: msg.source,
                    };

                    // โฟกัส textarea เพื่อให้พิมพ์ต่อได้เลย
                    this.$nextTick(() => {
                        this.$refs.replyBox && this.$refs.replyBox.focus && this.$refs.replyBox.focus();
                    });
                },

                // กดยกเลิกแถบ "กำลังตอบกลับ"
                cancelReply() {
                    this.replyingToMessage = null;
                },

                sendReply() {
                    if (!this.selectedConversation || this.sending) return;

                    const convId = this.selectedConversation.id;

                    // ✅ 1) ถ้ามี draftAttachment (รูป/quick reply/json) ให้ส่งเทมเพลตก่อน
                    if (this.replyDraftAttachment && this.replyDraftAttachment.template_id) {
                        this.sending = true;

                        // vars แบบเดียวกับที่คุณใช้ใน sendQuickReplyToText()
                        const vars = {
                            display_name:
                                (this.selectedConversation.contact &&
                                    this.selectedConversation.contact.display_name) ||
                                this.selectedConversation.contact_display_name ||
                                '',
                            username:
                                (this.selectedConversation.contact &&
                                    this.selectedConversation.contact.member_username) ||
                                this.selectedConversation.contact_member_username ||
                                '',
                        };

                        const payload = {
                            template_id: this.replyDraftAttachment.template_id,
                            vars: vars,
                            preview_only: false, // ✅ ส่งจริง
                            override_text: this.replyText,
                        };

                        // ถ้าคุณอยากให้ template ส่งแบบ "reply to message" ด้วย (แล้วแต่ backend รองรับ)
                        if (this.replyingToMessage && this.replyingToMessage.id) {
                            payload.reply_to_message_id = this.replyingToMessage.id;
                        }

                        axios
                            .post(this.apiUrl(`conversations/${convId}/reply-template`), payload)
                            .then(res => {
                                // backend อาจคืน message ตัวที่ส่งจริงกลับมา หรือไม่คืนก็ได้
                                const msg = res.data && res.data.data ? res.data.data : null;

                                if (msg) {
                                    this.messages.push(msg);

                                    if (this.selectedConversation) {
                                        this.selectedConversation.last_message =
                                            msg.text || this.selectedConversation.last_message;
                                        this.selectedConversation.last_message_at =
                                            msg.sent_at || this.selectedConversation.last_message_at;
                                        this.selectedConversation.unread_count = 0;
                                    }

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

                                // ✅ เคลียร์ draft + state reply-to
                                this.replyDraftAttachment = null;
                                this.replyDraftType = null;
                                this.replyTextFromTemplate = false;
                                this.replyingToMessage = null;

                                // ไม่จำเป็นต้องล้าง replyText แต่ถ้าจะให้ชัวร์ก็ล้าง
                                this.replyText = '';

                                this.$nextTick(() => {
                                    this.scrollToBottom();
                                });
                            })
                            .catch(err => {
                                const status = err.response?.status;
                                const data = err.response?.data || {};

                                if (status === 403) {
                                    const msg =
                                        data.message ||
                                        'ไม่สามารถตอบห้องนี้ได้ เนื่องจากถูกล็อกโดยพนักงานคนอื่น';
                                    this.showAlert({ success: false, message: msg });
                                    return;
                                }

                                console.error('sendReply (template) error', err);
                                const msg = data.message || 'ส่งเทมเพลตไม่สำเร็จ กรุณาลองใหม่';
                                this.showAlert({ success: false, message: msg });
                            })
                            .finally(() => {
                                this.sending = false;
                            });

                        return; // ✅ จบ flow ส่ง template
                    }

                    // ✅ 2) ถ้าไม่มี draftAttachment → ส่งข้อความ text แบบเดิม (ของคุณเดิมทุกบรรทัด)
                    const text = (this.replyText || '').trim();
                    if (text === '') return;

                    this.sending = true;

                    const payload = { text: text };

                    if (this.replyingToMessage && this.replyingToMessage.id) {
                        payload.reply_to_message_id = this.replyingToMessage.id;
                    }

                    axios
                        .post(this.apiUrl('conversations/' + convId + '/reply'), payload)
                        .then(res => {
                            const msg = res.data && res.data.data ? res.data.data : null;

                            if (msg) {
                                this.messages.push(msg);

                                if (this.selectedConversation) {
                                    this.selectedConversation.last_message =
                                        msg.text || this.selectedConversation.last_message;
                                    this.selectedConversation.last_message_at =
                                        msg.sent_at || this.selectedConversation.last_message_at;
                                    this.selectedConversation.unread_count = 0;
                                }

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
                            this.replyTextFromTemplate = false;
                            this.replyingToMessage = null;

                            this.$nextTick(() => {
                                this.scrollToBottom();
                            });
                        })
                        .catch(err => {
                            const status = err.response?.status;
                            const data = err.response?.data || {};

                            if (status === 403) {
                                const msg =
                                    data.message ||
                                    'ไม่สามารถตอบห้องนี้ได้ เนื่องจากถูกล็อกโดยพนักงานคนอื่น';
                                this.showAlert({ success: false, message: msg });
                                return;
                            }

                            console.error('sendReply error', err);
                            this.showAlert({ success: false, message: 'ส่งข้อความไม่สำเร็จ กรุณาลองใหม่' });
                        })
                        .finally(() => {
                            this.sending = false;
                        });
                },



                replyToDisplayName(message) {
                    const meta = message.meta || {};
                    const r = meta.reply_to || null;
                    if (!r) return '';

                    const isCustomer =
                        r.direction === 'inbound' &&
                        r.source === 'user';

                    if (isCustomer) {
                        const c = this.selectedConversation && this.selectedConversation.contact
                            ? this.selectedConversation.contact
                            : null;

                        return (
                            (c && (c.display_name ||
                                c.member_username ||
                                c.member_name)) ||
                            'ลูกค้า'
                        );
                    }

                    // ข้อความต้นทางไม่ใช่ของลูกค้า → ถือเป็นฝั่งพนักงาน / ระบบ
                    return 'พนักงาน';
                },

                replyHasPreviewThumb(rt) {
                    if (!rt) return false;
                    // ถ้า backend ใส่ preview_image มาให้
                    if (rt.preview_image) return true;

                    // กันเผื่ออนาคต ถ้าอยาก derive จาก field ย่อยเอง
                    if (rt.sticker && (rt.sticker.stickerId || rt.sticker.sticker_id)) {
                        return true;
                    }
                    if (rt.image && (rt.image.preview || rt.image.original)) {
                        return true;
                    }
                    if (rt.video && rt.video.preview) {
                        return true;
                    }
                    return false;
                },

                replyPreviewThumb(rt) {
                    if (!rt) return null;

                    // เคสหลัก: backend ส่ง preview_image มาให้แล้ว
                    if (rt.preview_image) {
                        return rt.preview_image;
                    }

                    // Fallback เผื่อบาง case
                    if (rt.image) {
                        return rt.image.preview || rt.image.original || null;
                    }
                    if (rt.video) {
                        return rt.video.preview || null;
                    }

                    // sticker: ถ้า backend ให้แค่ stickerId/packageId มา
                    if (rt.sticker) {
                        const sid = rt.sticker.stickerId || rt.sticker.sticker_id;
                        const type = rt.sticker.stickerResourceType || 'STATIC';

                        if (!sid) return null;

                        if (type === 'STATIC') {
                            return `https://stickershop.line-scdn.net/stickershop/v1/sticker/${sid}/android/sticker.png`;
                        }
                        if (type === 'ANIMATION' || type === 'ANIMATION_SOUND') {
                            return `https://stickershop.line-scdn.net/stickershop/v1/sticker/${sid}/android/sticker_animation.png`;
                        }
                        if (type === 'POPUP') {
                            return `https://stickershop.line-scdn.net/stickershop/v1/sticker/${sid}/android/sticker_popup.png`;
                        }
                        return `https://stickershop.line-scdn.net/stickershop/v1/sticker/${sid}/android/sticker.png`;
                    }

                    return null;
                },

                // แนะนำให้ update buildReplyPreviewText ให้ใช้ rt.text เป็นหลัก
                buildReplyPreviewText(rt) {
                    if (!rt) return '';

                    // 1) เคสสติ๊กเกอร์: ถ้ามี preview_image แล้ว → ไม่ต้องแสดง text ซ้ำ
                    if (rt.type === 'sticker' && rt.preview_image) {
                        return '';
                    }

                    // 2) ถ้ามี text ที่ backend สรุปมาแล้ว ใช้เลย
                    if (rt.text) {
                        return rt.text;
                    }

                    // 3) กันเคสไม่มี text แต่มี raw_text
                    if (rt.raw_text) {
                        // ใช้ Array.from เพื่อไม่ตัด emoji ครึ่งตัว (split surrogate pair)
                        const chars = Array.from(rt.raw_text);
                        if (chars.length > 80) {
                            return chars.slice(0, 80).join('') + '...';
                        }
                        return rt.raw_text;
                    }

                    // 4) fallback ตาม type (เผื่อกรณีไม่มี text จริง ๆ)
                    switch (rt.type) {
                        case 'image':
                            return '[รูปภาพ]';
                        case 'sticker':
                            return '[สติ๊กเกอร์]';
                        case 'video':
                            return '[วิดีโอ]';
                        case 'audio':
                            return '[เสียง]';
                        case 'location':
                            return '[ตำแหน่งที่ตั้ง]';
                        default:
                            return '[ข้อความ]';
                    }
                },

                // url รูปที่อยู่หัว (ใช้ของลูกค้าเท่านั้น)
                replyToAvatarUrl(message) {
                    const meta = message.meta || {};
                    const r = meta.reply_to || null;
                    if (!r) return null;

                    const isCustomer =
                        r.direction === 'inbound' &&
                        r.source === 'user';

                    if (!isCustomer) {
                        // ถ้าตอบข้อความพนักงานเอง → ไม่ต้องมี avatar
                        return null;
                    }

                    const c = this.selectedConversation && this.selectedConversation.contact
                        ? this.selectedConversation.contact
                        : null;

                    return c && c.picture_url ? c.picture_url : null;
                },
                async togglePinConversation(conv) {
                    if (!conv || !conv.id) return;

                    const convId = conv.id;
                    const pinned = !!conv.is_pinned;

                    try {
                        const url = this.apiUrl(
                            'conversations/' + convId + (pinned ? '/unpin' : '/pin')
                        );

                        const res = await axios.post(url);
                        const body = res.data || {};
                        const updated = body.data || body.conversation || null;

                        if (!updated) return;

                        this.updateConversationLocal(updated);

                        // ถ้าคุณอยากให้ห้องที่ปัก ขึ้นบนสุดทันที:
                        this.sortConversationsByPin();
                    } catch (e) {
                        console.error('[LineOA] togglePinConversation error', e);
                        this.showAlert && this.showAlert({
                            success: false,
                            message: 'เปลี่ยนสถานะปักหมุดห้องไม่สำเร็จ',
                        });
                    }
                },
                updateConversationLocal(conv) {
                    // กันเคส conv ว่าง หรือไม่มี id
                    if (!conv || !conv.id) return;

                    const idx = this.conversations.findIndex(c => c.id === conv.id);
                    if (idx === -1) {
                        return;
                    }

                    // merge ครั้งเดียว ใช้ร่วมกันทั้ง list และ selected
                    const merged = Object.assign({}, this.conversations[idx], conv);

                    // อัปเดตใน conversations list
                    this.$set(this.conversations, idx, merged);

                    // ถ้า selectedConversation เป็นห้องเดียวกัน → sync ด้วย object เดียวกัน
                    if (this.selectedConversation && this.selectedConversation.id === conv.id) {
                        this.selectedConversation = merged;
                    }
                },

                sortConversationsByPin() {
                    this.conversations.sort((a, b) => {
                        // pinned ก่อน
                        if (!!a.is_pinned !== !!b.is_pinned) {
                            return a.is_pinned ? -1 : 1;
                        }
                        // จากนั้น sort ตามเวลาเดิม
                        const at = a.last_message_at || '';
                        const bt = b.last_message_at || '';
                        return (at < bt) ? 1 : (at > bt ? -1 : 0);
                    });
                },
                async pinMessage(msg) {
                    if (!msg || !msg.id) return;

                    try {
                        const res = await axios.post(this.apiUrl('messages/' + msg.id + '/pin'));
                        const body = res.data || {};
                        const updated = body.data || body.message || null;

                        if (!updated) return;

                        this.updateMessageLocal(updated);
                    } catch (e) {
                        console.error('[LineOA] pinMessage error', e);
                        this.showAlert && this.showAlert({
                            success: false,
                            message: 'ปักหมุดข้อความไม่สำเร็จ',
                        });
                    }
                },

                async unpinMessage(msg) {
                    if (!msg || !msg.id) return;

                    try {
                        const res = await axios.post(this.apiUrl('messages/' + msg.id + '/unpin'));
                        const body = res.data || {};
                        const updated = body.data || body.message || null;

                        if (!updated) return;

                        this.updateMessageLocal(updated);
                    } catch (e) {
                        console.error('[LineOA] unpinMessage error', e);
                        this.showAlert && this.showAlert({
                            success: false,
                            message: 'เลิกปักหมุดข้อความไม่สำเร็จ',
                        });
                    }
                },

                updateMessageLocal(updated) {
                    const id = updated.id;

                    // แล้วแต่ structure ของ this.messages:
                    // ถ้าเป็น array ของ message ตรง ๆ
                    let idx = this.messages.findIndex(m => m.id === id);
                    if (idx !== -1) {
                        const merged = Object.assign({}, this.messages[idx], updated);
                        this.$set(this.messages, idx, merged);
                        return;
                    }

                    // ถ้าเป็น array ของ { message: {...} }
                    idx = this.messages.findIndex(it => it.message && it.message.id === id);
                    if (idx !== -1) {
                        const row = this.messages[idx];
                        const merged = Object.assign({}, row.message, updated);
                        this.$set(this.messages[idx], 'message', merged);
                    }
                },
                buildPinnedPreviewText(msg) {
                    if (!msg) return '';

                    // ถ้าเป็น text ใช้ text/translation ตามที่มี
                    if (msg.type === 'text') {
                        try {
                            const disp = this.getMessageDisplay
                                ? this.getMessageDisplay(msg)
                                : null;

                            const base = (disp && (disp.translated || disp.original)) || msg.text || '';
                            if (!base) return '[ข้อความ]';

                            return base.length > 60
                                ? base.slice(0, 60) + '…'
                                : base;
                        } catch (e) {
                            // กัน error เผื่อ getMessageDisplay ไม่มี
                            const base = msg.text || '';
                            return base.length > 60 ? base.slice(0, 60) + '…' : base;
                        }
                    }

                    // media อื่น ๆ
                    if (msg.type === 'image') return '[รูปภาพ]';
                    if (msg.type === 'sticker') return '[สติกเกอร์]';
                    if (msg.type === 'video') return '[วิดีโอ]';
                    if (msg.type === 'audio') return '[เสียง]';
                    if (msg.type === 'location') return '[ตำแหน่งที่ตั้ง]';

                    return '[' + (msg.type || 'ข้อความ') + ']';
                },

                scrollToMessage(messageId) {
                    if (!messageId) return;

                    this.$nextTick(() => {
                        const container = this.$refs.messageContainer;
                        if (!container) return;

                        const selector = '[data-msg-id="' + messageId + '"]';
                        const el = container.querySelector(selector);

                        if (!el) {
                            console.warn('[LineOA] scrollToMessage: ไม่พบ element สำหรับ id =', messageId);
                            return;
                        }

                        // เลื่อนให้อยู่ประมาณกลาง ๆ กล่องข้อความ
                        const containerRect = container.getBoundingClientRect();
                        const elRect = el.getBoundingClientRect();
                        const offset = elRect.top - containerRect.top;

                        container.scrollTop = container.scrollTop + offset - container.clientHeight / 3;

                        // ---- ไฮไลต์บับเบิลให้เห็นชัด ๆ สักพัก ----
                        el.classList.add('chat-msg-highlight');

                        setTimeout(() => {
                            el.classList.remove('chat-msg-highlight');
                        }, 2000); // 2 วินาทีแล้วค่อยจาง
                    });
                },
                pinnedSenderLabel(msg) {
                    if (!msg) return '';

                    // ลูกค้า
                    if (msg.direction === 'inbound' && msg.source === 'user') {
                        const c = this.selectedConversation && this.selectedConversation.contact
                            ? this.selectedConversation.contact
                            : null;

                        return (
                            (c && (c.display_name || c.member_username || c.member_name)) ||
                            'ลูกค้า'
                        );
                    }

                    // บอท
                    if (msg.source === 'bot') {
                        return 'บอท';
                    }

                    // พนักงาน (ดูจาก meta.employee_name ถ้ามี)
                    if (msg.meta && msg.meta.employee_name) {
                        return msg.meta.employee_name;
                    }

                    return 'พนักงาน';
                },

                // URL รูป preview ของสติกเกอร์
                buildStickerThumbnailUrl(stickerId) {
                    return (
                        'https://stickershop.line-scdn.net/stickershop/v1/sticker/' +
                        stickerId +
                        '/android/sticker.png'
                    );
                },


                openStickerModal() {
                    if (!this.selectedConversation) {
                        return;
                    }
                    this.$refs.stickerModal && this.$refs.stickerModal.show();
                },

                async selectStickerFromPack(packageId, stickerId) {
                    if (!this.selectedConversation) return;

                    try {
                        const convId = this.selectedConversation.id;

                        const resp = await axios.post(
                            this.apiUrl('conversations/' + convId + '/reply-sticker'),
                            {
                                package_id: packageId,
                                sticker_id: stickerId,
                            }
                        );

                        const body = resp.data || {};
                        const msg = body.data || null;

                        if (msg && typeof this.appendMessage === 'function') {
                            this.appendMessage(msg);
                        } else if (Array.isArray(this.messages) && msg) {
                            this.messages.push(msg);
                        }

                        this.$refs.stickerModal && this.$refs.stickerModal.hide();
                    } catch (e) {
                        console.error('send sticker failed', e);
                        this.$bvToast &&
                        this.$bvToast.toast('ส่งสติกเกอร์ไม่สำเร็จ กรุณาลองใหม่', {
                            title: 'เกิดข้อผิดพลาด',
                            variant: 'danger',
                            solid: true,
                        });
                    }
                },
                toggleEmojiPicker() {
                    if (!this.canReply) return;

                    this.emojiTarget = 'chat';
                    this.showEmojiPicker = !this.showEmojiPicker;

                    this.$nextTick(() => {
                        if (!this.showEmojiPicker) return;

                        const btnEl = this.$refs.emojiBtn?.$el || this.$refs.emojiBtn;
                        if (!btnEl) {
                            console.warn('[emoji] ไม่พบ ref emojiBtn');
                            return;
                        }

                        // this.positionEmojiPicker(btnEl);

                        const input = this.getReplyInputEl();
                        if (input && input.focus) {
                            input.focus();
                        }
                    });
                },

                // ================= EMOJI: เปิดจาก Quick Reply Modal =================
                openEmojiPickerForQuickReply() {
                    console.log('[quickReply] openEmojiPickerForQuickReply() fired');

                    this.emojiTarget = 'quickReply';
                    this.showEmojiPickerModal = !this.showEmojiPickerModal;

                    this.$nextTick(() => {
                        if (!this.showEmojiPickerModal) return;

                        const btnEl = this.$refs.quickReplyEmojiBtn?.$el || this.$refs.quickReplyEmojiBtn;
                        if (!btnEl) {
                            console.warn('[emoji] ไม่พบ ref quickReplyEmojiBtn');
                            return;
                        }

                        // this.positionEmojiPicker(btnEl);

                        // // ตำแหน่งกลางจอแบบ fix ไปเลย ไม่ต้องอิงปุ่ม
                        // this.emojiPickerStyle = {
                        //     top: '20%',
                        //     left: '50%',
                        //     transform: 'translateX(-50%)',
                        //     width: '320px',
                        //     zIndex: 200000,
                        // };

                        const input = this.getQuickReplyInputEl();
                        if (input && input.focus) {
                            input.focus();
                        }
                    });
                },

                openEmojiPickerForNote() {
                    console.log('[quickReply] openEmojiPickerForNote() fired');

                    this.emojiTarget = 'note';
                    this.showEmojiPickerNoteModal = !this.showEmojiPickerNoteModal;

                    this.$nextTick(() => {
                        if (!this.showEmojiPickerNoteModal) return;

                        const btnEl = this.$refs.noteEmojiBtn?.$el || this.$refs.noteEmojiBtn;
                        if (!btnEl) {
                            console.warn('[emoji] ไม่พบ ref noteEmojiBtn');
                            return;
                        }

                        // this.positionEmojiPicker(btnEl);


                        const input = this.getNoteInputEl();
                        if (input && input.focus) {
                            input.focus();
                        }
                    });
                },

                // ================= ใช้ร่วม: จัดตำแหน่ง popup รอบปุ่ม =================
                positionEmojiPicker(btnEl) {
                    const rect = btnEl.getBoundingClientRect();
                    const vw = window.innerWidth || document.documentElement.clientWidth;
                    const vh = window.innerHeight || document.documentElement.clientHeight;

                    const pickerWidth = 320;
                    const pickerHeight = 340;

                    let top = rect.bottom + 6;
                    let left = rect.left + rect.width / 2 - pickerWidth / 2;

                    if (top + pickerHeight > vh - 8) {
                        top = rect.top - pickerHeight - 6;
                    }
                    if (left < 8) {
                        left = 8;
                    }
                    if (left + pickerWidth > vw - 8) {
                        left = vw - 8 - pickerWidth;
                    }

                    this.emojiPickerStyle = {
                        top: top + 'px',
                        left: left + 'px',
                        width: pickerWidth + 'px',
                        zIndex: 200000,
                    };
                },

                // ================= ตอนเลือก emoji จาก emoji-picker =================
                onEmojiSelect(emoji) {
                    const char = emoji?.native;
                    if (!char) return;

                    if (this.emojiTarget === 'quickReply') {
                        this.insertEmojiIntoQuickReply(char);
                    } else if (this.emojiTarget === 'chat') {
                        this.insertEmojiIntoReplyText(char);
                    } else if (this.emojiTarget === 'note') {
                        this.insertEmojiIntoNote(char);
                    }

                    // ถ้าอยากให้ค้างให้กดหลายตัว ก็ comment บรรทัดนี้
                    // this.showEmojiPicker = false;
                    // this.showEmojiPickerModal = false;
                    // this.showEmojiPickerNoteModal = false;
                },

                // ---------- แทรก emoji เข้า “ช่องตอบแชตหลัก” ----------
                insertEmojiIntoReplyText(char) {
                    const input = this.getReplyInputEl();
                    const current = this.replyText || '';

                    if (!input || typeof input.selectionStart !== 'number') {
                        this.replyText = current + char;
                        return;
                    }

                    const start = input.selectionStart;
                    const end = input.selectionEnd;

                    this.replyText =
                        current.slice(0, start) +
                        char +
                        current.slice(end);

                    this.$nextTick(() => {
                        try {
                            input.focus();
                            const pos = start + char.length;
                            input.setSelectionRange(pos, pos);
                        } catch (e) {
                        }
                    });
                },

                getReplyInputEl() {
                    try {
                        return this.$refs.replyBox && this.$refs.replyBox.$refs
                            ? this.$refs.replyBox.$refs.input
                            : this.$refs.replyBox;
                    } catch (e) {
                        return null;
                    }
                },


                // ---------- แทรก emoji เข้า “quickReplyForm.message” ----------
                insertEmojiIntoQuickReply(char) {
                    const input = this.getQuickReplyInputEl();
                    const current = this.quickReplyForm.message || '';

                    if (!input || typeof input.selectionStart !== 'number') {
                        this.quickReplyForm.message = current + char;
                        return;
                    }

                    const start = input.selectionStart;
                    const end = input.selectionEnd;

                    this.quickReplyForm.message =
                        current.slice(0, start) +
                        char +
                        current.slice(end);

                    this.$nextTick(() => {
                        try {
                            input.focus();
                            const pos = start + char.length;
                            input.setSelectionRange(pos, pos);
                        } catch (e) {
                        }
                    });
                },

                // ---------- แทรก emoji เข้า “quickReplyForm.message” ----------
                insertEmojiIntoNote(char) {
                    const input = this.getNoteInputEl();
                    const current = this.noteModalText || '';

                    if (!input || typeof input.selectionStart !== 'number') {
                        this.noteModalText = current + char;
                        return;
                    }

                    const start = input.selectionStart;
                    const end = input.selectionEnd;

                    this.noteModalText =
                        current.slice(0, start) +
                        char +
                        current.slice(end);

                    this.$nextTick(() => {
                        try {
                            input.focus();
                            const pos = start + char.length;
                            input.setSelectionRange(pos, pos);
                        } catch (e) {
                        }
                    });
                },

                getQuickReplyInputEl() {
                    const comp = this.$refs.quickReplyMessageInput;
                    if (!comp) return null;

                    if (comp.$refs && comp.$refs.input) {
                        return comp.$refs.input;
                    }

                    return comp.$el ? comp.$el.querySelector('textarea') : null;
                },

                getNoteInputEl() {
                    const comp = this.$refs.noteMessageInput;
                    if (!comp) return null;

                    if (comp.$refs && comp.$refs.input) {
                        return comp.$refs.input;
                    }

                    return comp.$el ? comp.$el.querySelector('textarea') : null;
                },

                showAdjustSelector() {
                    this.$refs.memberAdjustModal && this.$refs.memberAdjustModal.show();
                },

                // เรียกจากปุ่มใน modal หลัก
                openAdjust(type) {
                    const memberId = this.selectedConversation?.contact?.member_id || null;
                    if (!memberId) {
                        // กันไว้หน่อย
                        return;
                    }

                    // ปิด modal หลักก่อน
                    if (this.$refs.memberAdjustModal) {
                        this.$refs.memberAdjustModal.hide();
                    }

                    // แล้วค่อยเปิด modal เป้าหมาย
                    this.$nextTick(() => {
                        if (type === 'money' && window.memberRefillApp?.money) {
                            window.memberRefillApp.money({member_id: memberId});
                        } else if (type === 'point' && window.memberRefillApp?.point) {
                            window.memberRefillApp.point({member_id: memberId});
                        } else if (type === 'diamond' && window.memberRefillApp?.diamond) {
                            window.memberRefillApp.diamond({member_id: memberId});
                        }
                    });
                },

                // อันนี้จะถูกเรียกตอน modal ลูก (money/point/diamond) ถูกปิด
                onAdjustChildHidden() {
                    // เปิด modal หลักกลับขึ้นมาใหม่
                    if (this.$refs.memberAdjustModal) {
                        this.$refs.memberAdjustModal.show();
                    }
                },

                showLogSelector() {
                    this.$refs.memberLogModal && this.$refs.memberLogModal.show();
                },

                // เรียกจากปุ่มใน modal หลัก
                openLog(type) {
                    const memberId = this.selectedConversation?.contact?.member_id || null;
                    if (!memberId) {
                        // กันไว้หน่อย
                        return;
                    }

                    // ปิด modal หลักก่อน
                    if (this.$refs.memberLogModal) {
                        this.$refs.memberLogModal.hide();
                    }

                    // แล้วค่อยเปิด modal เป้าหมาย
                    this.$nextTick(() => {
                        if (type === 'deposit' && window.memberRefillApp?.openGameLog) {
                            window.memberRefillApp.openGameLog('deposit', {member_id: memberId});
                        } else if (type === 'withdraw' && window.memberRefillApp?.point) {
                            window.memberRefillApp.openGameLog('withdraw', {member_id: memberId});
                        }
                    });
                },

                // อันนี้จะถูกเรียกตอน modal ลูก (money/point/diamond) ถูกปิด
                onLogChildHidden() {
                    // เปิด modal หลักกลับขึ้นมาใหม่
                    if (this.$refs.memberLogModal) {
                        this.$refs.memberLogModal.show();
                    }
                },
                onReplyInput() {
                    const conv = this.selectedConversation;
                    if (!conv || !conv.id) return;


                    // ✅ ถ้าข้อความในกล่องถูก "เติมมาจาก template ที่มี quickReply" แล้วผู้ใช้เริ่มแก้ไข
                    // ให้ล้าง draftAttachment เพื่อกันการส่ง quickReply ที่ไม่ตรงกับข้อความใหม่
                    // if (this.replyDraftType === 'text_quick_reply' && this.replyTextFromTemplate === true) {
                    //     this.replyDraftAttachment = null;
                    //     this.replyDraftType = null;
                    //     this.replyTextFromTemplate = false;
                    // }

                    // เริ่ม typing ถ้ายังไม่ active
                    if (!this.typingActive) {
                        this.typingActive = true;
                        this.sendTyping(conv.id, true);
                    }

                    // รีเซ็ตตัวจับเวลา stop (ไม่มีพิมพ์ 4 วิให้หยุด)
                    clearTimeout(this.typingStopTimer);
                    this.typingStopTimer = setTimeout(() => {
                        this.typingActive = false;
                        this.sendTyping(conv.id, false);
                    }, 4000);

                    // debounce กันยิงถี่ (ส่ง keep-alive ทุก ~1200ms ขณะพิมพ์)
                    clearTimeout(this.typingTimer);
                    this.typingTimer = setTimeout(() => {
                        if (this.typingActive) this.sendTyping(conv.id, true);
                    }, 1200);
                },

                async sendTyping(conversationId, isTyping) {
                    try {
                        await axios.post("{{ route('admin.line-oa.typing') }}", {
                            conversation_id: conversationId,
                            is_typing: isTyping,
                        });
                    } catch (e) {
                        // เงียบไว้ ไม่ต้องรบกวน agent
                    }
                },

                // เรียกตอนกดส่งข้อความสำเร็จ
                clearTypingAfterSend() {
                    const conv = this.selectedConversation;
                    if (!conv || !conv.id) return;
                    this.typingActive = false;
                    clearTimeout(this.typingTimer);
                    clearTimeout(this.typingStopTimer);
                    this.sendTyping(conv.id, false);
                },
                async onPaste(e) {
                    try {
                        const cd = e.clipboardData;
                        if (!cd || !cd.items || !cd.items.length) return;

                        const imageFiles = [];
                        for (const item of cd.items) {
                            if (item.kind === 'file' && item.type && item.type.startsWith('image/')) {
                                const blob = item.getAsFile();
                                if (blob) imageFiles.push(blob);
                            }
                        }

                        if (!imageFiles.length) {
                            // ไม่มีรูป → ปล่อยให้ paste ข้อความทำงานปกติ
                            return;
                        }

                        // มีรูป → กันไม่ให้ browser แทรกอะไรแปลก ๆ ลง textarea
                        e.preventDefault();

                        // รองรับหลายรูป: ส่งทีละรูป (ปลอดภัยสุด)
                        for (const file of imageFiles) {
                            await this.uploadAndSendImage(file);
                        }
                    } catch (err) {
                        console.error('[chat] onPaste error', err);
                        this.toastError('วางรูปไม่สำเร็จ');
                    }
                },

                async onDrop(e) {
                    try {
                        const files = Array.from(e.dataTransfer?.files || []);
                        const images = files.filter(f => f.type && f.type.startsWith('image/'));
                        if (!images.length) return;

                        for (const file of images) {
                            await this.uploadAndSendImage(file);
                        }
                    } catch (err) {
                        console.error('[chat] onDrop error', err);
                        this.toastError('ลากรูปไม่สำเร็จ');
                    }
                },

                async uploadAndSendImage(file) {
                    if (!this.selectedConversation || !this.selectedConversation.id) {
                        this.toastError('ยังไม่ได้เลือกห้องแชต');
                        return;
                    }

                    // กันพลาด: ป้องกันรูปใหญ่เกิน (ปรับตาม policy)
                    const maxMb = 10;
                    if (file.size > maxMb * 1024 * 1024) {
                        this.toastError(`รูปใหญ่เกิน ${maxMb}MB`);
                        return;
                    }

                    const convId = this.selectedConversation.id;
                    const url = this.apiUrl('conversations/' + convId + '/reply-image'); // คุณ map route เองได้

                    const fd = new FormData();
                    // ตั้งชื่อให้มีนามสกุล (clipboard มักไม่มีชื่อ)
                    const ext = (file.type.split('/')[1] || 'png').toLowerCase();
                    const namedFile = new File([file], `paste_${Date.now()}.${ext}`, {type: file.type});
                    fd.append('image', namedFile);

                    // optimistic UI: แสดงรูปหลอกทันที
                    const tempId = `tmp_${Date.now()}_${Math.random().toString(16).slice(2)}`;
                    const previewUrl = URL.createObjectURL(file);
                    this.appendLocalTempImageMessage({tempId, previewUrl});

                    this.uploading = true;

                    try {
                        const res = await axios.post(url, fd, {
                            headers: {'Content-Type': 'multipart/form-data'},
                        });

                        // replace temp message ด้วยของจริง
                        this.replaceTempMessage(tempId, res.data.data);

                        // อัปเดตห้องใน sidebar ให้เด้งบนสุด (ถ้าคุณมีฟังก์ชันนี้อยู่แล้ว)
                        if (res.data.conversation) {
                            this.updateConversationLocal(res.data.conversation);
                        }
                    } catch (err) {
                        console.error('[chat] uploadAndSendImage error', err);
                        this.removeTempMessage(tempId);
                        this.toastError('ส่งรูปไม่สำเร็จ');
                    } finally {
                        this.uploading = false;
                        URL.revokeObjectURL(previewUrl);
                    }
                },

                // ===== helpers ตัวอย่าง (ผูกกับระบบ UI เดิมของคุณได้เลย) =====
                appendLocalTempImageMessage({tempId, previewUrl}) {
                    const m = {
                        id: tempId,
                        direction: 'outbound',
                        source: 'agent',
                        type: 'image',
                        text: null,
                        sent_at: new Date().toISOString(),
                        meta: {
                            preview_url: previewUrl,
                            is_temp: true,
                        }
                    };
                    this.messages.push(m);
                    this.scrollToBottom && this.scrollToBottom();
                },

                replaceTempMessage(tempId, serverMessage) {
                    const idx = this.messages.findIndex(x => x.id === tempId);
                    if (idx !== -1) this.$set(this.messages, idx, serverMessage);
                    else this.messages.push(serverMessage);
                },

                removeTempMessage(tempId) {
                    const idx = this.messages.findIndex(x => x.id === tempId);
                    if (idx !== -1) this.messages.splice(idx, 1);
                },

                toastError(msg) {
                    // ผูกกับ toastr / b-toast / sweetalert ตามที่คุณใช้
                    alert(msg);
                },
                resetQuickReplyForm() {
                    this.quickReplySaveError = null;
                    this.quickReplyImageError = null;
                    this.quickReplyItemsError = null;
                    this.quickReplySaving = false;

                    this.quickReplyForm = {
                        type: 'text',
                        description: '',
                        message: '',
                        enabled: true,
                        image_file: null,
                        image_preview_url: '',
                        quick_reply_items: [],
                    };

                    // ถ้า picker เปิดอยู่ในโหมด quickReply ให้ปิด
                    if (this.emojiTarget === 'quickReply') {
                        this.showEmojiPicker = false;
                        this.emojiTarget = 'chat'; // หรือ null ก็ได้ ตามสะดวก
                    }
                },

                actionValuePlaceholder(type) {
                    if (type === 'message') return 'เช่น ฝากเงิน';
                    if (type === 'postback') return 'เช่น ACT_DEPOSIT';
                    if (type === 'uri') return 'https://...';
                    return '';
                },

                // ---------- Quick Reply Items ----------
                addQuickReplyItem() {
                    this.quickReplyItemsError = null;
                    if (this.quickReplyForm.quick_reply_items.length >= 13) return;

                    this.quickReplyForm.quick_reply_items.push({
                        label: '',
                        action_type: 'message',
                        value: '',
                        image_url: '',
                    });
                },

                removeQuickReplyItem(idx) {
                    this.quickReplyForm.quick_reply_items.splice(idx, 1);
                },

                validateQuickReplyItems() {
                    if (!this.needsQuickReply) return true;

                    const items = this.quickReplyForm.quick_reply_items;
                    if (!items || items.length === 0) {
                        this.quickReplyItemsError = 'กรุณาเพิ่มปุ่มอย่างน้อย 1 ปุ่ม';
                        return false;
                    }
                    if (items.length > 13) {
                        this.quickReplyItemsError = 'ปุ่มลัดเกิน 13 ปุ่ม';
                        return false;
                    }
                    for (let i = 0; i < items.length; i++) {
                        const it = items[i];
                        if (!it.label || !it.value) {
                            this.quickReplyItemsError = `ปุ่มที่ ${i + 1} ต้องมี label และค่า action`;
                            return false;
                        }
                        if (it.action_type === 'uri' && !/^https?:\/\//i.test(it.value)) {
                            this.quickReplyItemsError = `ปุ่มที่ ${i + 1} แบบ uri ต้องขึ้นต้นด้วย http/https`;
                            return false;
                        }
                    }
                    return true;
                },


                clearSelectedImage() {
                    try {
                        if (this.quickReplyForm.image_preview_url) {
                            URL.revokeObjectURL(this.quickReplyForm.image_preview_url);
                        }
                    } catch (e) {
                    }

                    this.quickReplyForm.image_file = null;
                    this.quickReplyForm.image_preview_url = '';
                },

                onImageDragOver() {
                    // เผื่อทำ UI highlight ได้ภายหลัง
                },

                async uploadTemplateImage(file) {
                    const uploadUrl = '{{ route('admin.line_template.upload_image') }}';
                    if (!uploadUrl) {
                        this.quickReplyImageError = 'ไม่พบ route อัปโหลดรูป';
                        return;
                    }

                    this.quickReplyForm.uploading_image = true;
                    this.quickReplyImageError = null;

                    try {
                        const fd = new FormData();
                        fd.append('image', file);

                        const res = await axios.post(uploadUrl, fd, {
                            headers: { 'Content-Type': 'multipart/form-data' },
                        });

                        const rawUrl = res?.data?.data?.url || res?.data?.url;
                        if (!rawUrl) throw new Error('upload ok but missing url in response');

                        // ✅ ทำให้เป็น absolute เพื่อส่ง LINE ได้ชัวร์
                        const absoluteUrl = /^https?:\/\//i.test(rawUrl)
                            ? rawUrl
                            : (window.location.origin + rawUrl);

                        // ✅ เก็บ URL ที่ได้จาก server
                        this.quickReplyForm.image_url = absoluteUrl;

                        // ✅ ทำให้ preview “ไม่หาย” โดยใช้ url จาก server
                        this.quickReplyForm.image_preview_url = absoluteUrl;

                        // ✅ จะเคลียร์ file ก็ได้ แต่ตอนนี้ preview ไม่พึ่ง file แล้ว
                        this.quickReplyForm.image_file = null;
                    } catch (e) {
                        console.error('[quickReply] upload image error', e);
                        this.quickReplyImageError = (e?.response?.data?.message) || e.message || 'อัปโหลดรูปไม่สำเร็จ';
                        this.quickReplyForm.image_url = '';
                        // ไม่จำเป็นต้องลบ preview ทันที เผื่อยังอยากให้เห็น
                    } finally {
                        this.quickReplyForm.uploading_image = false;
                    }
                },

                async onImageSelected(file) {
                    this.quickReplyImageError = null;

                    if (!file) return;

                    if (!file.type || !file.type.startsWith('image/')) {
                        this.quickReplyImageError = 'ไฟล์ที่เลือกไม่ใช่รูปภาพ';
                        return;
                    }

                    // preview ชั่วคราวได้ แต่ไม่จำเป็นแล้วก็ได้
                    this.quickReplyForm.image_preview_url = URL.createObjectURL(file);

                    await this.uploadTemplateImage(file);
                },

                onImageDrop(e) {
                    const file = e.dataTransfer?.files?.[0] || null;
                    if (!file) return;
                    this.quickReplyForm.image_file = file;
                    this.onImageSelected(file);
                },

                onImagePaste(e) {
                    if (!this.needsImage) return;

                    const items = e.clipboardData?.items || [];
                    for (let i = 0; i < items.length; i++) {
                        const it = items[i];
                        if (it.kind === 'file' && it.type?.startsWith('image/')) {
                            const file = it.getAsFile();
                            if (file) {
                                this.quickReplyForm.image_file = file;
                                this.onImageSelected(file);
                                e.preventDefault();
                                return;
                            }
                        }
                    }
                },


                // ---------- Submit ----------
                async submitQuickReplyForm() {
                    this.quickReplySaveError = null;
                    this.quickReplyImageError = null;
                    this.quickReplyItemsError = null;

                    // validate ตามประเภท
                    if (this.quickReplyForm.uploading_image) {
                        this.quickReplyImageError = 'กำลังอัปโหลดรูป กรุณารอสักครู่';
                        return;
                    }

                    if (this.needsImage && !this.quickReplyForm.image_url) {
                        this.quickReplyImageError = 'กรุณาอัปโหลดรูปให้เสร็จก่อน';
                        return;
                    }

                    // ถ้าประเภทต้องมีข้อความจริง ๆ
                    if (this.needsText && !String(this.quickReplyForm.message || '').trim()) {
                        this.quickReplySaveError = 'กรุณากรอกข้อความ';
                        return;
                    }

                    if (!this.validateQuickReplyItems()) {
                        return;
                    }

                    this.quickReplySaving = true;

                    try {
                        // ✅ ประกอบ message เป็น JSON string (Controller จะ detect แล้วตั้ง message_type=json ให้)
                        const payload = {
                            type: this.quickReplyForm.type,
                            text: this.quickReplyForm.message || '',
                            image_url: this.quickReplyForm.image_url || '',
                            quick_reply_items: this.needsQuickReply ? (this.quickReplyForm.quick_reply_items || []) : [],
                        };

                        // ✅ ส่งตามรูปแบบที่ Controller รับ: input('data')
                        const postBody = {
                            data: {
                                category: 'quick_reply',                  // หรือ 'reply_template' ตามที่คุณใช้จริง
                                key: (this.quickReplyForm.description || '').trim(), // ถ้าคุณใช้ key แยกจาก description ให้เปลี่ยนตรงนี้
                                description: this.quickReplyForm.description,        // ถ้า table มี field นี้
                                message: JSON.stringify(payload),
                                enabled: this.quickReplyForm.enabled ? 1 : 0,
                            }
                        };

                        const url = '{{ route('admin.line_template.create') }}';
                        const res = await axios.post(url, postBody);

                        this.$refs.quickReplyAddModal.hide();
                        this.fetchQuickReplies && this.fetchQuickReplies();
                    } catch (e) {
                        console.error('[quickReply] submit error', e);
                        this.quickReplySaveError =
                            (e?.response?.data?.message) || e.message || 'บันทึกไม่สำเร็จ';
                    } finally {
                        this.quickReplySaving = false;
                    }
                },


            }
        });

    </script>

    <script type="module">
        Dropzone.autoDiscover = false;

        window.memberEditApp = new Vue({
            el: '#member-edit-app',
            data() {
                return {
                    csrf: document.head.querySelector('meta[name="csrf-token"]').content,

                    // state หลักของ member edit
                    memberEditShow: false,
                    memberEditMode: 'edit',  // 'add' หรือ 'edit'
                    memberEditCode: null,    // member id ที่กำลังแก้ไข

                    memberEditForm: {
                        firstname: '',
                        lastname: '',
                        bank_code: '',
                        user_name: '',
                        user_pass: '',
                        acc_no: '',
                        wallet_id: '',
                        lineid: '',
                        pic_id: '',
                        tel: '',
                        one_time_password: '',
                        refer_code: 0,
                        maxwithdraw_day: 0,
                        af: '',
                        up_name: '',
                        upline_code: '',
                    },

                    // รูปปัจจุบัน
                    memberEditPic: null,

                    // Dropzone
                    memberEditDropzone: null,
                    memberEditSuppressDelete: false,

                    // options select ต่าง ๆ
                    memberEditOption: {
                        bank_code: [],
                        refer_code: [],
                    },
                };
            },
            mounted() {
                this.memberEditLoadBank();
                this.memberEditLoadRefer();
            },
            methods: {
                /* ============================
                 *  ส่วน Dropzone / Upload รูป
                 * ============================ */
                autoFocusOnLineOA(refName) {
                    this.$nextTick(() => {
                        // ===== helper หา Vue root / line-oa-chat ภายในฟังก์ชันนี้เอง =====
                        function findAnyVueRoot() {
                            var all = document.querySelectorAll('body, body *');
                            for (var i = 0; i < all.length; i++) {
                                if (all[i].__vue__) {
                                    return all[i].__vue__;
                                }
                            }
                            console.warn('[memberEditApp] ไม่พบ Vue root instance เลย');
                            return null;
                        }

                        function findLineOaChatVm(vm) {
                            if (!vm) return null;

                            var name = vm.$options && (vm.$options.name || vm.$options._componentTag);
                            if (name === 'line-oa-chat') {
                                return vm;
                            }

                            if (vm.$children && vm.$children.length) {
                                for (var i = 0; i < vm.$children.length; i++) {
                                    var found = findLineOaChatVm(vm.$children[i]);
                                    if (found) return found;
                                }
                            }
                            return null;
                        }

                        function getLineOaChatComponentLocal() {
                            var rootVm = findAnyVueRoot();
                            if (!rootVm) return null;

                            if (rootVm.$refs && rootVm.$refs.lineOaChat) {
                                return rootVm.$refs.lineOaChat;
                            }

                            var comp = findLineOaChatVm(rootVm);
                            if (!comp) {
                                console.warn('[memberEditApp] ไม่พบ component line-oa-chat จาก Vue tree');
                            }
                            return comp;
                        }

                        // ===== ใช้งานจริง =====
                        const comp = getLineOaChatComponentLocal();
                        if (!comp) {
                            return;
                        }

                        const target = comp.$refs && comp.$refs[refName];
                        if (!target) {
                            console.warn(`[memberEditApp] line-oa-chat ไม่มี $refs["${refName}"]`);
                            return;
                        }

                        // 1) ถ้า ref เป็น component ที่มี .focus()
                        if (typeof target.focus === 'function') {
                            try {
                                target.focus();
                                return;
                            } catch (e) {
                                console.warn('[memberEditApp] focus() บน component ล้มเหลว', e);
                            }
                        }

                        // 2) ถ้า ref เป็น element ตรง ๆ
                        if (target instanceof HTMLElement) {
                            target.focus?.();
                            return;
                        }

                        // 3) ref เป็น Vue component → หา input/textarea ข้างใน
                        const el =
                            target.$el?.querySelector?.('input,textarea,select,[tabindex]') ||
                            target.$el ||
                            null;

                        if (el && typeof el.focus === 'function') {
                            el.focus();
                        } else {
                            console.warn('[memberEditApp] ไม่พบ element ที่ focus ได้ใน ref', refName);
                        }
                    });
                },

                autoFocusRef(refName) {
                    this.$nextTick(() => {
                        const r = this.$refs[refName];
                        if (!r) return;

                        if (typeof r.focus === 'function') {
                            try {
                                r.focus();
                                return;
                            } catch (_) {
                            }
                        }

                        const el =
                            r.$el?.querySelector?.('input,textarea') ||
                            (r instanceof HTMLElement ? r : null);

                        el?.focus?.();
                    });
                },
                onMemberEditModalHidden() {
                    this.$nextTick(() => {
                        this.autoFocusOnLineOA('replyBox');
                    });
                },
                memberEditOpenUpload() {
                    this.$refs.memberEditUploadModal.show();
                },

                memberEditEnsureDropzone() {
                    if (this.memberEditDropzone) return;

                    this.memberEditDropzone = new Dropzone(this.$refs.memberEditDropzoneEl, {
                        // url: "",
                        url: "{{ route('admin.upload.pic') }}",
                        method: 'post',
                        maxFiles: 1,
                        acceptedFiles: 'image/*',
                        addRemoveLinks: true,
                        dictRemoveFile: 'ลบรูป',
                        previewsContainer: this.$refs.memberEditDropzonePreviews,
                        clickable: [this.$refs.memberEditDropzoneEl, this.$refs.memberEditPickBtn],
                        headers: {'X-CSRF-TOKEN': this.csrf},
                    });

                    this.memberEditDropzone.on('sending', (file, xhr, formData) => {
                        formData.append('id', this.memberEditCode || '');
                    });

                    this.memberEditDropzone.on('success', (file, resp) => {
                        file.serverId = resp.id;
                        // file.deleteUrl = resp.delete_url
                        //     || "".replace(':id', resp.id);
                        file.deleteUrl = resp.delete_url
                            || "{{ route('admin.delete.pic', ['id' => ':id']) }}".replace(':id', resp.id);

                        this.memberEditPic = {
                            id: resp.id,
                            name: file.name,
                            size: file.size,
                            url: resp.url,
                        };

                        // เก็บ path / url ไว้ในฟอร์มเพื่อนำไปใช้ด้านหลัง
                        this.memberEditForm.pic_id = resp.path || resp.url || '';
                    });

                    this.memberEditDropzone.on('maxfilesexceeded', file => {
                        this.memberEditSuppressDelete = true;
                        this.memberEditDropzone.removeAllFiles(true);
                        this.memberEditSuppressDelete = false;
                        this.memberEditDropzone.addFile(file);
                    });

                    const onRemovedFile = (file) => {
                        if (this.memberEditSuppressDelete) return;
                        if (!file.serverId) return;

                        // const url = file.deleteUrl
                        //     || "".replace(':id', file.serverId);
                        const url = file.deleteUrl
                            || "{{ route('admin.delete.pic', ['id' => ':id']) }}".replace(':id', file.serverId);

                        fetch(url, {
                            method: 'POST',
                            headers: {'X-CSRF-TOKEN': this.csrf},
                        }).then(() => {
                            if (this.memberEditPic && String(this.memberEditPic.id) === String(file.serverId)) {
                                this.memberEditPic = null;
                                this.memberEditForm.pic_id = '';
                            }
                        });
                    };

                    this.memberEditDropzone.on('removedfile', onRemovedFile);
                },

                memberEditOnUploadShown() {
                    this.memberEditEnsureDropzone();

                    this.memberEditSuppressDelete = true;
                    this.memberEditDropzone.removeAllFiles(true);
                    this.memberEditSuppressDelete = false;

                    const dzEl = this.$refs.memberEditDropzoneEl;
                    if (dzEl && dzEl.classList) dzEl.classList.remove('dz-started');
                    const msg = dzEl ? dzEl.querySelector('.dz-message') : null;
                    if (msg) msg.style.display = '';

                    if (this.memberEditDropzone.hiddenFileInput) {
                        this.memberEditDropzone.hiddenFileInput.disabled = false;
                    }
                    if (typeof this.memberEditDropzone.enable === 'function') {
                        this.memberEditDropzone.enable();
                    }

                    // preload รูปเดิม
                    if (this.memberEditPic && this.memberEditPic.url) {
                        const f = this.memberEditPic;
                        const mock = {
                            name: f.name || 'existing.jpg',
                            size: f.size || 12345,
                            serverId: f.id,
                            isExisting: true,
                            url: f.url,
                        };
                        this.memberEditDropzone.emit('addedfile', mock);
                        this.memberEditDropzone.emit('thumbnail', mock, f.url);
                        this.memberEditDropzone.emit('complete', mock);
                        this.memberEditDropzone.files.push(mock);
                    }
                },

                memberEditOnUploadHidden() {
                    if (this.memberEditDropzone) {
                        this.memberEditSuppressDelete = true;
                        this.memberEditDropzone.removeAllFiles(true);
                        this.memberEditSuppressDelete = false;
                    }
                    const dzEl = this.$refs.memberEditDropzoneEl;
                    if (dzEl && dzEl.classList) dzEl.classList.remove('dz-started');
                    const msg = dzEl ? dzEl.querySelector('.dz-message') : null;
                    if (msg) msg.style.display = '';
                },

                memberEditSetPicFromPath(path) {
                    if (!path) {
                        this.memberEditPic = null;
                        this.memberEditForm.pic_id = '';
                        return;
                    }
                    const fileName = path.split('/').pop();
                    const url = this.memberEditFileUrl(path);
                    this.memberEditPic = {
                        id: this.memberEditCode,
                        name: fileName,
                        url,
                        size: 12345,
                    };
                    this.memberEditForm.pic_id = path;
                },

                memberEditFileUrl(path) {
                    // ปรับตามที่เก็บไฟล์จริง ถ้าใช้ storage/public
                    return `{{ url('/storage') }}/${path}`;
                },

                /* ============================
                 *  ส่วนเปิด / โหลดข้อมูล member
                 * ============================ */

                // เรียกใช้จากภายนอก: window.memberEditApp.memberEditOpen(memberId)
                memberEditOpen(code) {
                    console.log('memberEditOpen', code);
                    // ตั้งค่า state เบื้องต้น
                    this.memberEditCode = code || null;
                    this.memberEditMode = 'edit';

                    // เคลียร์ฟอร์ม + รูป
                    this.memberEditResetForm();
                    this.memberEditPic = null;

                    // เปิด modal ทันที ไม่รอ axios
                    this.memberEditShow = true;
                    if (this.$refs.memberEditModal && typeof this.$refs.memberEditModal.show === 'function') {
                        this.$refs.memberEditModal.show();
                    } else {
                        console.error('memberEditModal ref not found');
                    }

                    // ถ้ามี code → ค่อยยิงโหลดข้อมูล async ตามหลัง
                    if (code) {
                        this.memberEditLoadData().catch(err => {
                            console.error('memberEditLoadData error:', err);
                        });
                    }
                },

                // alias สำหรับ “เพิ่มใหม่” (ใช้ id = null)
                memberEditNew() {
                    this.memberEditOpen(null);
                },

                memberEditResetForm() {
                    this.memberEditForm = {
                        firstname: '',
                        lastname: '',
                        bank_code: '',
                        user_name: '',
                        user_pass: '',
                        acc_no: '',
                        wallet_id: '',
                        lineid: '',
                        pic_id: '',
                        tel: '',
                        one_time_password: '',
                        refer_code: 0,
                        maxwithdraw_day: 0,
                        af: '',
                        up_name: '',
                        upline_code: '',
                    };
                },

                async memberEditLoadData() {
                    if (!this.memberEditCode) return;

                    const response = await axios.get("{{ route('admin.member.loaddata') }}", {
                        params: {id: this.memberEditCode},
                    });

                    const u = response.data.data;

                    this.memberEditForm = {
                        firstname: u.firstname,
                        lastname: u.lastname,
                        bank_code: u.bank_code,
                        user_name: u.user_name,
                        user_pass: '',
                        acc_no: u.acc_no,
                        wallet_id: u.wallet_id,
                        lineid: u.lineid,
                        pic_id: u.pic_id,
                        tel: u.tel,
                        one_time_password: '',
                        refer_code: u.refer_code,
                        maxwithdraw_day: u.maxwithdraw_day,
                        af: u.af || '',
                        up_name: u.up_name || '',
                        upline_code: u.upline_code || '',
                    };

                    if (u.pic_id) {
                        this.memberEditSetPicFromPath(u.pic_id);
                    } else {
                        this.memberEditPic = null;
                    }
                },

                async memberEditLoadBank() {
                    const url = @json(\Route::has('admin.member.loadbank') ? route('admin.member.loadbank') : null);

                    if (!url) {
                        console.warn('[memberEditLoadBank] route admin.member.loadbank not found');
                        this.memberEditOption.bank_code = [];
                        return;
                    }

                    try {
                        const response = await axios.get(url);
                        const banks = response?.data?.banks;

                        this.memberEditOption.bank_code = Array.isArray(banks) ? banks : [];
                    } catch (e) {
                        console.error('[memberEditLoadBank] failed', e?.response?.status, e?.response?.data || e);
                        this.memberEditOption.bank_code = [];
                    }
                },

                async memberEditLoadRefer() {
                    const url = @json(\Route::has('admin.member.loadrefer') ? route('admin.member.loadrefer') : null);

                    if (!url) {
                        console.warn('[memberEditLoadRefer] route admin.member.loadrefer not found');
                        this.memberEditOption.refer_code = [];
                        return;
                    }

                    try {
                        const response = await axios.get(url);
                        const refers = response?.data?.refers;

                        this.memberEditOption.refer_code = Array.isArray(refers) ? refers : [];
                    } catch (e) {
                        console.error('[memberEditLoadRefer] failed', e?.response?.status, e?.response?.data || e);
                        this.memberEditOption.refer_code = [];
                    }
                },

                async memberEditLoadAF(afValue) {
                    const url = @json(\Route::has('admin.member.loadaf') ? route('admin.member.loadaf') : null);

                    if (!url) {
                        console.warn('[memberEditLoadAF] route admin.member.loadaf not found');
                        this.memberEditForm.up_name = '';
                        this.memberEditForm.upline_code = 0;
                        return;
                    }

                    try {
                        const response = await axios.get(url, {
                            params: {af: afValue},
                        });

                        const ok = !!response?.data?.success;
                        const data = response?.data?.data;

                        if (ok && data) {
                            this.memberEditForm.up_name = data.name || '';
                            this.memberEditForm.upline_code = data.code || 0;
                        } else {
                            this.memberEditForm.up_name = '';
                            this.memberEditForm.upline_code = 0;
                        }
                    } catch (e) {
                        console.error('[memberEditLoadAF] failed', e?.response?.status, e?.response?.data || e);
                        this.memberEditForm.up_name = '';
                        this.memberEditForm.upline_code = 0;
                    }
                },

                /* ============================
                 *  ส่วน submit / error handling
                 * ============================ */

                memberEditShowError(response) {
                    let message = response?.data?.message || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ';

                    if (typeof message === 'object') {
                        try {
                            message = Object.values(message).flat().join('\n');
                        } catch (e) {
                            message = [].concat(...Object.values(message)).join('\n');
                        }
                    }
                    if (Array.isArray(message)) {
                        message = message.join('\n');
                    }

                    this.$bvModal.msgBoxOk(message, {
                        title: 'ผลการดำเนินการ',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'danger',
                        headerClass: 'p-2 border-bottom-0',
                        footerClass: 'p-2 border-top-0',
                        centered: true,
                    });
                },

                memberEditSubmit(event) {
                    event.preventDefault();

                    let url;
                    if (this.memberEditMode === 'add') {
                        url = "{{ route('admin.member.create') }}";
                    } else {
                        url = "{{ route('admin.member.update') }}/" + this.memberEditCode;
                    }

                    const payload = {
                        firstname: this.memberEditForm.firstname,
                        lastname: this.memberEditForm.lastname,
                        bank_code: this.memberEditForm.bank_code,
                        user_name: this.memberEditForm.user_name,
                        user_pass: this.memberEditForm.user_pass,
                        acc_no: this.memberEditForm.acc_no,
                        wallet_id: this.memberEditForm.wallet_id,
                        lineid: this.memberEditForm.lineid,
                        pic_id: this.memberEditForm.pic_id,
                        tel: this.memberEditForm.tel,
                        one_time_password: this.memberEditForm.one_time_password,
                        maxwithdraw_day: this.memberEditForm.maxwithdraw_day,
                        refer_code: this.memberEditForm.refer_code,
                        upline_code: this.memberEditForm.upline_code,
                    };

                    const formData = new FormData();
                    formData.append('data', JSON.stringify(payload));

                    const config = {
                        headers: {'Content-Type': 'multipart/form-data'},
                    };

                    axios.post(url, formData, config)
                        .then(response => {
                            if (response.data.success === true) {
                                this.$bvModal.msgBoxOk(response.data.message, {
                                    title: 'ผลการดำเนินการ',
                                    size: 'sm',
                                    buttonSize: 'sm',
                                    okVariant: 'success',
                                    headerClass: 'p-2 border-bottom-0',
                                    footerClass: 'p-2 border-top-0',
                                    centered: true,
                                });

                                this.$refs.memberEditModal.hide();
                            } else {
                                this.memberEditShowError(response);
                            }
                        })
                        .catch(error => {
                            this.memberEditShowError(error.response || {});
                        });
                },
            },
        });
    </script>

    <script type="module">
        window.memberRefillApp = new Vue({
            el: '#member-refill-app',

            data() {
                return {
                    showRefillUI: false,     // ใช้ control v-if ของฟอร์มใน modal
                    currentTopupId: null,    // code ของบิลแจ้งฝากที่กำลังจัดการ
                    currentClearId: null,    // code ของรายการที่จะ clear
                    currentMemberId: null,
                    // ฟอร์มสำหรับผูกบิลเติมเงินกับ Member/Game ID
                    assignTopupTargetForm: {
                        user_name: '',
                        name: '',
                        member_topup: '',
                        remark_admin: '',
                    },

                    // ฟอร์มเติมเงินตามปกติ
                    refillForm: {
                        id: '',
                        user_name: '',
                        name: '',
                        amount: 0,
                        account_code: '',
                        remark_admin: '',
                        one_time_password: '',
                    },

                    // ฟอร์มระบุหมายเหตุเวลา clear
                    clearRemarkForm: {
                        remark: '',
                    },

                    formmoney: {
                        id: null,
                        amount: 0,
                        type: 'D',
                        remark: '',
                        one_time_password: '',
                    },
                    formpoint: {
                        id: null,
                        amount: 0,
                        type: 'D',
                        remark: '',
                    },
                    formdiamond: {
                        id: null,
                        amount: 0,
                        type: 'D',
                        remark: '',
                    },

                    // NEW: options ประเภทของรายการ
                    typesmoney: [
                        {value: 'D', text: 'เพิ่ม ยอดเงิน'},
                        {value: 'W', text: 'ลด ยอดเงิน'},
                    ],
                    typespoint: [
                        {value: 'D', text: 'เพิ่ม Point'},
                        {value: 'W', text: 'ลด Point'},
                    ],
                    typesdiamond: [
                        {value: 'D', text: 'เพิ่ม Diamond'},
                        {value: 'W', text: 'ลด Diamond'},
                    ],

                    // ธนาคารที่ใช้ใน select
                    banks: [{value: '', text: '== ธนาคาร =='}],

                    fields: [],
                    // modal log
                    logType: null,      // 'deposit' หรือ 'withdraw'
                    caption: '',
                    items: [],
                    isBusy: false,
                    show: false,
                };
            },

            created() {
                this.audio = document.getElementById('alertsound');
                // this.autoCnt(false);
                // ถ้าหน้านี้ยังใช้ alertsound / autoCnt เดิมอยู่ สามารถเรียกจาก window ตัวอื่นได้
                // ไม่ผูกกับ memberRefillApp เพื่อลด side-effect
            },

            mounted() {
                this.loadBankAccount();

            },

            methods: {
                openGameLog(type, prefill = null) {
                    this.setupLogFields(type);

                    this.showRefillUI = true;

                    if (prefill && prefill.member_id) {
                        this.currentMemberId = prefill.member_id;
                    }

                    this.$nextTick(async () => {
                        this.$refs.gamelog.show();
                        await this.fetchGameLog();   // ใช้เมธอดเดิม
                    });
                },
                async fetchGameLog() {
                    if (!this.logType) return;

                    this.isBusy = true;
                    this.items = [];

                    try {
                        const response = await axios.get('{{ route('admin.gamelog.log') }}', {
                            params: {
                                id: this.currentMemberId,
                                method: this.logType,

                                // อาจจะส่ง member_id ไปด้วย ถ้าต้องการจำกัด log ตามสมาชิก
                                // member_id: this.member.id
                            },
                        });

                        // สมมติ backend คืนเป็น { data: [...] }
                        this.items = response.data.list || [];
                    } catch (e) {
                        console.error('โหลด log ไม่สำเร็จ', e);
                        this.$bvToast && this.$bvToast.toast('ไม่สามารถโหลดประวัติได้', {
                            title: 'เกิดข้อผิดพลาด',
                            variant: 'danger',
                            solid: true,
                        });
                    } finally {
                        this.isBusy = false;
                    }
                },


                /**
                 * helper แปลงตัวเลขเป็น string เงิน (อาจมีอยู่แล้วใน app)
                 */
                intToMoney(value) {
                    if (value === null || value === undefined) return '0.00';
                    const n = Number(value) || 0;
                    return n.toLocaleString('th-TH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },
                autoFocusOnLineOA(refName) {
                    this.$nextTick(() => {
                        // ===== helper หา Vue root / line-oa-chat ภายในฟังก์ชันนี้เอง =====
                        function findAnyVueRoot() {
                            var all = document.querySelectorAll('body, body *');
                            for (var i = 0; i < all.length; i++) {
                                if (all[i].__vue__) {
                                    return all[i].__vue__;
                                }
                            }
                            console.warn('[memberEditApp] ไม่พบ Vue root instance เลย');
                            return null;
                        }

                        function findLineOaChatVm(vm) {
                            if (!vm) return null;

                            var name = vm.$options && (vm.$options.name || vm.$options._componentTag);
                            if (name === 'line-oa-chat') {
                                return vm;
                            }

                            if (vm.$children && vm.$children.length) {
                                for (var i = 0; i < vm.$children.length; i++) {
                                    var found = findLineOaChatVm(vm.$children[i]);
                                    if (found) return found;
                                }
                            }
                            return null;
                        }

                        function getLineOaChatComponentLocal() {
                            var rootVm = findAnyVueRoot();
                            if (!rootVm) return null;

                            if (rootVm.$refs && rootVm.$refs.lineOaChat) {
                                return rootVm.$refs.lineOaChat;
                            }

                            var comp = findLineOaChatVm(rootVm);
                            if (!comp) {
                                console.warn('[memberEditApp] ไม่พบ component line-oa-chat จาก Vue tree');
                            }
                            return comp;
                        }

                        // ===== ใช้งานจริง =====
                        const comp = getLineOaChatComponentLocal();
                        if (!comp) {
                            return;
                        }

                        const target = comp.$refs && comp.$refs[refName];
                        if (!target) {
                            console.warn(`[memberEditApp] line-oa-chat ไม่มี $refs["${refName}"]`);
                            return;
                        }

                        // 1) ถ้า ref เป็น component ที่มี .focus()
                        if (typeof target.focus === 'function') {
                            try {
                                target.focus();
                                return;
                            } catch (e) {
                                console.warn('[memberEditApp] focus() บน component ล้มเหลว', e);
                            }
                        }

                        // 2) ถ้า ref เป็น element ตรง ๆ
                        if (target instanceof HTMLElement) {
                            target.focus?.();
                            return;
                        }

                        // 3) ref เป็น Vue component → หา input/textarea ข้างใน
                        const el =
                            target.$el?.querySelector?.('input,textarea,select,[tabindex]') ||
                            target.$el ||
                            null;

                        if (el && typeof el.focus === 'function') {
                            el.focus();
                        } else {
                            console.warn('[memberEditApp] ไม่พบ element ที่ focus ได้ใน ref', refName);
                        }
                    });
                },

                autoFocusRef(refName) {
                    this.$nextTick(() => {
                        const r = this.$refs[refName];
                        if (!r) return;

                        if (typeof r.focus === 'function') {
                            try {
                                r.focus();
                                return;
                            } catch (_) {
                            }
                        }

                        const el =
                            r.$el?.querySelector?.('input,textarea') ||
                            (r instanceof HTMLElement ? r : null);

                        el?.focus?.();
                    });
                },
                onRefillModalHidden() {
                    this.$nextTick(() => {
                        this.autoFocusOnLineOA('replyBox');
                    });
                },
                /* -----------------------------------
                 * เปิด MODAL แบบชื่อใหม่
                 * ----------------------------------- */

                // เดิมคือ editModal() / addModal() แต่ความหมายคือเลือกเป้าหมายของบิลที่เลือก
                openAssignTopupTargetModal(topupId = null, prefill = null) {
                    this.currentTopupId = topupId || null;

                    // reset ฟอร์ม
                    this.assignTopupTargetForm = {
                        user_name: '',
                        name: '',
                        member_topup: '',
                        remark_admin: '',
                    };

                    this.showRefillUI = true;

                    // ถ้ามีข้อมูลจากห้องแชต (prefill)
                    if (prefill && (prefill.member_username || prefill.member_id)) {
                        // ให้เอา member_user มาใส่ช่องค้นหาไว้ก่อน
                        if (prefill.member_username) {
                            this.assignTopupTargetForm.user_name = prefill.member_username;
                        }

                        // เปิด modal แล้วค่อย auto ค้นหา
                        this.$nextTick(async () => {
                            if (this.$refs.assignTopupTargetModal) {
                                this.$refs.assignTopupTargetModal.show();
                            }

                            // ถ้ามี member_user → ให้ยิง loadUserForAssignTarget เลย
                            if (this.assignTopupTargetForm.user_name) {
                                try {
                                    await this.loadUserForAssignTarget();
                                } catch (e) {
                                    console.warn('auto loadUserForAssignTarget failed', e);
                                }
                            }
                        });
                    } else {
                        // กรณีไม่มี prefill → เปิด modal เฉย ๆ ให้แอดมินกรอกเอง
                        this.$nextTick(() => {
                            if (this.$refs.assignTopupTargetModal) {
                                this.$refs.assignTopupTargetModal.show();
                            }
                        });
                    }
                },


                // เดิม refill()
                openRefillModal(prefill = null) {
                    console.log('[memberRefillApp] openRefillModal(prefill =', prefill, ')');

                    this.currentTopupId = null;

                    // reset form
                    this.refillForm = {
                        id: '',
                        user_name: '',
                        name: '',
                        amount: 0,
                        account_code: '',
                        remark_admin: '.',
                        one_time_password: '',
                    };

                    this.showRefillUI = true;

                    // เคลียร์ log ก่อน
                    this.items = [];
                    this.isBusy = false;

                    if (prefill && prefill.member_username) {
                        this.refillForm.user_name = prefill.member_username;
                        if (prefill.member_id) {
                            this.currentMemberId = prefill.member_id;
                        }

                        console.warn('[memberRefillApp] refillForm user_name', prefill.member_username);

                        this.$nextTick(async () => {
                            if (this.$refs.refillModal) {
                                this.$refs.refillModal.show();
                            }

                            // auto ค้นหา user + เติมข้อมูลฝั่งซ้าย
                            if (this.refillForm.user_name) {
                                try {
                                    await this.loadUserForRefill();
                                } catch (e) {
                                    console.warn('[memberRefillApp] auto loadUserForRefill failed', e);
                                }
                            }

                            // --- ตรงนี้คือส่วน log ฝั่งขวา ---
                            // ถ้ามี member_id แล้ว ให้ดึง log ฝากมาแสดง
                            if (this.currentMemberId) {
                                this.setupLogFields('topup');
                                await this.fetchGameLog();
                            }
                        });

                    } else {
                        // ไม่มี prefill
                        this.$nextTick(() => {
                            if (this.$refs.refillModal) {
                                this.$refs.refillModal.show();
                            }
                        });
                    }
                },

                // เดิม clearModal(code)
                openClearRemarkModal(code) {
                    this.currentClearId = code;
                    this.clearRemarkForm = {
                        remark: '',
                    };

                    this.showRefillUI = true;
                    if (this.$refs.clearRemarkModal) {
                        this.$refs.clearRemarkModal.show();
                    }
                },

                openMoneyModal(prefill = null) {
                    console.log('openMoney');
                    // reset form
                    this.formmoney = {
                        id: null,
                        amount: 0,
                        type: 'D',
                        remark: '',
                        one_time_password: '',
                    };

                    // ถ้ามีข้อมูล member จาก chat ให้ prefill id ไว้
                    if (prefill && prefill.member_id) {
                        this.formmoney.id = prefill.member_id;
                    }

                    this.showRefillUI = true;
                    this.$nextTick(() => {
                        this.$refs.memberRefillMoneyModal && this.$refs.memberRefillMoneyModal.show();
                    });
                },

                openPointModal(prefill = null) {
                    this.formpoint = {
                        id: null,
                        amount: 0,
                        type: 'D',
                        remark: '',
                    };

                    if (prefill && prefill.member_id) {
                        this.formpoint.id = prefill.member_id;
                    }

                    this.showRefillUI = true;
                    this.$nextTick(() => {
                        this.$refs.memberRefillPointModal && this.$refs.memberRefillPointModal.show();
                    });
                },

                openDiamondModal(prefill = null) {
                    this.formdiamond = {
                        id: null,
                        amount: 0,
                        type: 'D',
                        remark: '',
                    };

                    if (prefill && prefill.member_id) {
                        this.formdiamond.id = prefill.member_id;
                    }

                    this.showRefillUI = true;
                    this.$nextTick(() => {
                        this.$refs.memberRefillDiamondModal && this.$refs.memberRefillDiamondModal.show();
                    });
                },


                /* -----------------------------------
                 * LOAD user / bank
                 * ----------------------------------- */

                async loadUserForAssignTarget() {
                    const response = await axios.post("{{ route('admin.bank_in.loaddata') }}", {
                        id: this.assignTopupTargetForm.user_name,
                    });

                    this.assignTopupTargetForm = {
                        ...this.assignTopupTargetForm,
                        name: response.data.data.name,
                        member_topup: response.data.data.code,
                    };
                },

                async loadUserForRefill() {
                    // ป้องกันกรณีไม่มีค่าอะไรเลย
                    if (!this.refillForm.user_name) {
                        console.warn('[memberRefillApp] loadUserForRefill(): ไม่มี user_name ให้ค้นหา');
                        return;
                    }

                    console.log('[memberRefillApp] loadUserForRefill(): search', this.refillForm.user_name);

                    try {
                        const response = await axios.post("{{ route('admin.bank_in.loaddata') }}", {
                            id: this.refillForm.user_name,
                        });

                        const data = response.data && response.data.data;

                        if (!data) {
                            console.warn('[memberRefillApp] loadUserForRefill(): response ไม่มี data', response.data);
                            return;
                        }

                        // เซ็ตข้อมูลกลับเข้าฟอร์ม
                        this.refillForm = {
                            ...this.refillForm,
                            name: data.name,
                            id: data.code,
                        };

                        console.log('[memberRefillApp] loadUserForRefill(): loaded', this.refillForm);

                        // ====== เพิ่มส่วนนี้ เพื่อโหลด LOG ฝั่งขวา ======
                        // สมมติ backend ส่ง member_id กลับมาด้วย
                        if (data.member_id) {
                            this.currentMemberId = data.member_id;
                        }

                        // ถ้ามี currentMemberId แล้ว → ตั้ง field log เป็น deposit และดึง log
                        if (this.currentMemberId) {
                            this.setupLogFields('topup');
                            await this.fetchGameLog();
                        } else {
                            console.warn('[memberRefillApp] ไม่มี currentMemberId จาก loaddata → ยังไม่โหลด log');
                        }
                        // ===============================================

                    } catch (e) {
                        console.error('[memberRefillApp] loadUserForRefill(): error', e);
                    }
                },


                async loadBankAccount() {
                    const response = await axios.get("{{ route('admin.member.loadbankaccount') }}");
                    this.banks = response.data.banks;
                },

                /* -----------------------------------
                 * SUBMIT: ผูกบิลกับ Member (เดิม addEditSubmitNew)
                 * ----------------------------------- */

                submitAssignTopupTarget(event) {
                    event && event.preventDefault && event.preventDefault();

                    if (typeof this.toggleButtonDisable === 'function') {
                        this.toggleButtonDisable(true);
                    }

                    // logic เดิม: ถ้ามี code = update, ถ้าไม่มี = create
                    let url;
                    if (!this.currentTopupId) {
                        url = "{{ route('admin.bank_in.create') }}";
                    } else {
                        url = "{{ route('admin.bank_in.update') }}/" + this.currentTopupId;
                    }

                    const payload = {
                        member_topup: this.assignTopupTargetForm.member_topup,
                        remark_admin: this.assignTopupTargetForm.remark_admin,
                    };

                    const formData = new FormData();
                    formData.append('data', JSON.stringify(payload));

                    const config = {
                        headers: {'Content-Type': `multipart/form-data; boundary=${formData._boundary}`},
                    };

                    axios.post(url, formData, config)
                        .then(response => {
                            if (response.data.success === true) {
                                this.$bvModal.msgBoxOk(response.data.message, {
                                    title: 'ผลการดำเนินการ',
                                    size: 'sm',
                                    buttonSize: 'sm',
                                    okVariant: 'success',
                                    headerClass: 'p-2 border-bottom-0',
                                    footerClass: 'p-2 border-top-0',
                                    centered: true,
                                });

                                this.$refs.assignTopupTargetModal.hide();
                            } else {
                                // logic เดิม: mark invalid field ตาม key ใน response.data.message
                                $.each(response.data.message, function (index) {
                                    const el = document.getElementById(index);
                                    el && el.classList.add("is-invalid");
                                });
                                $('input').on('focus', (ev) => {
                                    ev.preventDefault();
                                    ev.stopPropagation();
                                    const id = $(ev.target).attr('id');
                                    const el = document.getElementById(id);
                                    el && el.classList.remove("is-invalid");
                                });
                            }
                        })
                        .catch(errors => {
                            console.log(errors);
                        });
                },

                /* -----------------------------------
                 * SUBMIT: เติมเงิน (เดิม refillSubmit)
                 * ----------------------------------- */

                submitRefillForm(event) {
                    event && event.preventDefault && event.preventDefault();

                    if (typeof this.toggleButtonDisable === 'function') {
                        this.toggleButtonDisable(true);
                    }

                    this.$http.post("{{ route('admin.member.refill') }}", this.refillForm)
                        .then(response => {
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true,
                            });


                            this.$refs.refillModal.hide();

                        })
                        .catch(exception => {
                            console.log('error', exception);
                            if (typeof this.toggleButtonDisable === 'function') {
                                this.toggleButtonDisable(false);
                            }
                        });
                },

                /* -----------------------------------
                 * SUBMIT: clear (เดิม clearSubmit)
                 * ----------------------------------- */

                submitClearRemarkForm(event) {
                    event && event.preventDefault && event.preventDefault();

                    if (typeof this.toggleButtonDisable === 'function') {
                        this.toggleButtonDisable(true);
                    }

                    this.$http.post("{{ route('admin.bank_in.clear') }}", {
                        id: this.currentClearId,
                        remark: this.clearRemarkForm.remark,
                    })
                        .then(response => {
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true,
                            });


                            this.$refs.clearRemarkModal.hide();
                        })
                        .catch(exception => {
                            console.log('error', exception);
                            if (typeof this.toggleButtonDisable === 'function') {
                                this.toggleButtonDisable(false);
                            }
                        });
                },

                moneySubmit(event) {
                    event && event.preventDefault && event.preventDefault();
                    if (typeof this.toggleButtonDisable === 'function') {
                        this.toggleButtonDisable(true);
                    }

                    this.$http.post("{{ route('admin.member.setwallet') }}", this.formmoney)
                        .then(response => {
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });


                            this.$refs.money && this.$refs.money.hide();
                        })
                        .catch(exception => {
                            console.log('error', exception);
                            if (typeof this.toggleButtonDisable === 'function') {
                                this.toggleButtonDisable(false);
                            }
                        });
                },

                pointSubmit(event) {
                    event && event.preventDefault && event.preventDefault();
                    if (typeof this.toggleButtonDisable === 'function') {
                        this.toggleButtonDisable(true);
                    }

                    this.$http.post("{{ route('admin.member.setpoint') }}", this.formpoint)
                        .then(response => {
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });


                            this.$refs.point && this.$refs.point.hide();
                        })
                        .catch(exception => {
                            console.log('error', exception);
                            if (typeof this.toggleButtonDisable === 'function') {
                                this.toggleButtonDisable(false);
                            }
                        });
                },

                diamondSubmit(event) {
                    event && event.preventDefault && event.preventDefault();
                    if (typeof this.toggleButtonDisable === 'function') {
                        this.toggleButtonDisable(true);
                    }

                    this.$http.post("{{ route('admin.member.setdiamond') }}", this.formdiamond)
                        .then(response => {
                            this.$bvModal.msgBoxOk(response.data.message, {
                                title: 'ผลการดำเนินการ',
                                size: 'sm',
                                buttonSize: 'sm',
                                okVariant: 'success',
                                headerClass: 'p-2 border-bottom-0',
                                footerClass: 'p-2 border-top-0',
                                centered: true
                            });


                            this.$refs.diamond && this.$refs.diamond.hide();
                        })
                        .catch(exception => {
                            console.log('error', exception);
                            if (typeof this.toggleButtonDisable === 'function') {
                                this.toggleButtonDisable(false);
                            }
                        });
                },

                openDeleteModal(code) {
                    // popup confirm
                    this.$bvModal.msgBoxConfirm(
                        'คุณต้องการลบรายการนี้ใช่หรือไม่?',
                        {
                            title: 'โปรดยืนยันการทำรายการ',
                            size: 'sm',
                            okVariant: 'danger',
                            okTitle: 'ลบรายการ',
                            cancelTitle: 'ยกเลิก',
                            footerClass: 'p-2',
                            centered: true,
                        }
                    ).then(value => {
                        if (!value) {
                            return;
                        }

                        // ยิง API ลบรายการ
                        axios.post("{{ route('admin.bank_in.delete') }}", {
                            id: code
                        })
                            .then(response => {
                                this.$bvModal.msgBoxOk(response.data.message, {
                                    title: 'ผลการดำเนินการ',
                                    size: 'sm',
                                    buttonSize: 'sm',
                                    okVariant: 'success',
                                    headerClass: 'p-2 border-bottom-0',
                                    footerClass: 'p-2 border-top-0',
                                    centered: true,
                                });

                            })
                            .catch(error => {
                                this.$bvModal.msgBoxOk('เกิดข้อผิดพลาด ไม่สามารถลบรายการได้', {
                                    title: 'ข้อผิดพลาด',
                                    size: 'sm',
                                    buttonSize: 'sm',
                                    okVariant: 'danger',
                                    centered: true,
                                });
                            });
                    })
                        .catch(err => {
                            console.warn('Cancel delete', err);
                        });
                },

                /* -----------------------------------
                 * ALIAS ชื่อเดิม ให้ของเก่าไม่พัง
                 * ----------------------------------- */

                // clearModal(code) เดิม → ใช้ชื่อใหม่
                clearModal(code) {
                    this.openClearRemarkModal(code);
                },
                removeFocusFromTrigger() {
                    // ลอง blur องค์ประกอบที่กำลัง focus อยู่ตอนนี้
                    if (document.activeElement) {
                        document.activeElement.blur();
                    }
                },
                // refill() เดิม
                refill() {
                    this.openRefillModal();
                },

                // addModal() เดิม → เปิด assign modal โดยไม่ผูกกับบิลเดิม
                addModal() {
                    this.openAssignTopupTargetModal(null);
                },

                // editModal(code) เดิม
                editModal(code) {
                    this.openAssignTopupTargetModal(code);
                },

                // refillSubmit() เดิม
                refillSubmit(event) {
                    this.submitRefillForm(event);
                },

                // addEditSubmitNew() เดิม
                addEditSubmitNew(event) {
                    this.submitAssignTopupTarget(event);
                },

                // loadUser() เดิม
                loadUser() {
                    this.loadUserForAssignTarget();
                },

                // loadUserRefill() เดิม
                loadUserRefill() {
                    this.loadUserForRefill();
                },

                // NEW: alias money/point/diamond แบบเดิม
                money(prefill = null) {
                    this.openMoneyModal(prefill);
                },

                point(prefill = null) {
                    this.openPointModal(prefill);
                },

                diamond(prefill = null) {
                    this.openDiamondModal(prefill);
                },
                setupLogFields(type) {
                    this.logType = type;

                    if (type === 'deposit') {
                        this.caption = 'ประวัติฝากเครดิต';
                        this.fields = [
                            {key: 'id', label: 'รหัส', sortable: false},
                            {key: 'date_create', label: 'เวลา', sortable: true},
                            {key: 'amount', label: 'ยอดฝาก', sortable: false},
                            {key: 'pro_name', label: 'โปรโมชั่น', sortable: true},
                            {key: 'credit_bonus', label: 'โบนัสที่ได้', sortable: false},
                            {key: 'credit_before', label: 'เครดิตก่อน', sortable: false},
                            {key: 'credit_after', label: 'เครดิตหลัง', sortable: false},
                            {key: 'status_display', label: 'สถานะ', sortable: true},
                        ];
                    } else if (type === 'withdraw') {
                        this.caption = 'ประวัติถอนเครดิต';
                        this.fields = [
                            {key: 'id', label: 'รหัส', sortable: false},
                            {key: 'date_create', label: 'เวลา', sortable: true},
                            {key: 'amount_request', label: 'ยอดแจ้ง', sortable: false},
                            {key: 'amount', label: 'ยอดถอนที่ได้รับ', sortable: false},
                            {key: 'credit_before', label: 'เครดิตก่อน', sortable: false},
                            {key: 'credit_after', label: 'เครดิตหลัง', sortable: false},
                            {key: 'status_display', label: 'สถานะ', sortable: true},
                        ];
                    } else if (type === 'topup') {
                        this.caption = 'ประวัติรายการฝาก';
                        this.fields = [
                            {key: 'date_create', label: 'เวลา', sortable: true},
                            {key: 'bank', label: 'ธนาคาร', sortable: false},
                            {key: 'amount', label: 'ยอดฝาก', sortable: false},
                            {key: 'status_display', label: 'สถานะ', sortable: true},
                        ];
                    } else {
                        this.caption = 'ประวัติรายการ';
                        this.fields = [];
                    }
                },
            },
        });
    </script>

@endpush
