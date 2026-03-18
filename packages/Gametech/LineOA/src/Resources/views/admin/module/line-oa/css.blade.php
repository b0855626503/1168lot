@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css"
          integrity="sha512-jU/7UFiaW5UBGODEopEqnbIAHOI8fO6T99m7Tsmqs2gkdujByJfkCbbfPSN4Wlqlb9TGnsuC0YgUgWkRBK7B9A=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <style>

        .content-header {
            display:none !important;
        }
        .main-footer {
            display:block !important;
            margin-top: 15px;
        }

        .card-body {
            flex: 1 1 auto;
            min-height: 1px;
            padding: 0 !important;
        }
        /* ====== รายการห้องแชต (ด้านซ้าย) ====== */
        .list-group-item.gt-conv-active {
            background-color: #e7f1ff; /* ฟ้าอ่อนกว่า primary */
            border-color: #b6d4fe;
            color: #0c63e4;
        }

        .list-group-item.gt-conv-active .text-muted,
        .list-group-item.gt-conv-active small {
            color: #0c63e4 !important;
        }

        .list-group-item.active .text-muted {
            color: #fff !important;
        }

        .list-group-item.gt-conv-active .badge {
            background-color: #0d6efd;
            color: #fff;
        }

        /* ====== bubble ฝั่งทีมงาน (outbound) ====== */
        .gt-msg-agent {
            background-color: #d1e7ff;
            color: #084298;
        }

        .gt-msg-agent .text-muted {
            color: #084298 !important;
        }

        #line-oa-chat-overlay {
            position: fixed;
            inset: 0;
            z-index: 9998;
        }

        .lineoa-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
        }

        .lineoa-popup {
            position: fixed;
            inset: 20px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            z-index: 9999;
            overflow: hidden;
        }

        .list-group-item.gt-conv-active .oa-reg-badge {
            background-color: #ffc107 !important; /* สี warning */
            color: #212529 !important; /* ดำ */
        }

        .chat-line-original {
            white-space: pre-wrap;
            /*font-size: 14px;*/
        }

        .chat-line-translated {
            white-space: pre-wrap;
            /*font-size: 13px;*/
            border-left: 3px solid #e0e0e0;
            padding-left: 4px;
        }

        .gt-conv-last-message {
            /*font-size: 12px;*/
            color: #666;
            white-space: nowrap; /* ไม่ตัดขึ้นบรรทัดใหม่ */
            overflow: hidden; /* ถ้ายาวเกิน ก็ตัดส่วนที่ล้นทิ้ง */
            text-overflow: ellipsis; /* แสดง ... ท้ายบรรทัด */
            max-width: 100%; /* หรือกำหนดเป็น px ก็ได้เช่น 220px */
        }

        /* ฝั่ง sidebar ทั้งคอลัมน์ – ไม่ให้เลื่อนซ้ายขวา */
        .line-oa-sidebar {
            overflow-x: hidden;
            font-size: 16px;
        }

        /* ข้อความพรีวิวในแต่ละห้อง */
        .line-oa-sidebar .conversation-last-message {
            display: block;
            white-space: nowrap; /* บังคับบรรทัดเดียว */
            overflow: hidden; /* ซ่อนส่วนเกิน */
            text-overflow: ellipsis; /* ใส่ ... ท้ายประโยค */

        }

        /* ห้ามเลื่อนซ้าย-ขวา */
        .no-x-scroll {
            overflow-x: hidden !important;
        }

        /* บังคับ … ให้ทำงานเสมอ */
        .fixed-line {
            white-space: normal !important;
            overflow-wrap: anywhere;   /* modern + สวย */
            word-break: break-word;    /* fallback */
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
            display: block;
        }

        .btn-app {
            background-color: transparent !important;
            color: inherit !important;
            padding: 15px 10px 15px !important
        }

        .btn-icon-group {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            gap: 3px;               /* ระยะห่างระหว่าง + และ - */
            margin-bottom: 4px;     /* ระยะห่างระหว่างไอคอนกับข้อความ */
        }

        .color-red {
            background-color: red !important;
            color: white !important;
        }

    </style>
    <style>
        /* ให้คลิกทะลุข้อความได้แน่ ๆ */
        .dropzone .dz-message {
            pointer-events: auto;
        }

        /* กัน preview ทับพื้นที่คลิก */
        .dropzone .dz-preview {
            position: relative;
            z-index: 1;
        }

        .dropzone .dz-message {
            position: relative;
            z-index: 2;
        }

    </style>
    <style>
        /* wrapper ทั้งหน้าแชต */
        .line-chat-font {
            font-family: system-ui,
            -apple-system, /* iOS */ BlinkMacSystemFont, /* macOS */ "Segoe UI", /* Windows */ Roboto, /* Android */ "Helvetica Neue",
            Arial,
            "Noto Sans Thai",
            sans-serif;
            font-size: 18px; /* ขนาดใกล้เคียง LINE */
            line-height: 1.35; /* ระยะห่างบรรทัดแบบ LINE */
        }

        .line-oa-chat-page {
            height: calc(100vh - 60px); /* -60px = เผื่อ header/topbar ถ้ามี */
            display: flex;
            flex-direction: column;
            overflow: hidden; /* กันลูกทะลุ */
        }

        /* ให้ container + row ขยายเต็ม และยืดลูกทุกคอลัมน์ */
        .line-oa-chat-page > .container-fluid,
        .line-oa-chat-page .row.h-100 {
            flex: 1 1 auto;
            min-height: 0;
        }

        .line-oa-chat-page .line-oa-col {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        /* คอลัมน์กลาง: ไม่ให้ทะลุกรอบ */
        .line-oa-chat-page .chat-middle-col {
            height: calc(100vh - 0px);
            overflow: hidden;
        }

        /* ให้ list ข้อความเป็นตัว scroll เอง */
        .line-oa-chat-page .chat-middle-col .chat-message-list {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }

        .note-nav-btn {
            min-width: 24px !important;
            max-width: 24px !important;
            text-align: center;
        }

        .note-box {
            background: #f8f9fa;             /* เทาอ่อน อ่านง่าย */
            border: 1px solid #e3e6eb;       /* เส้นบางๆ แบบ modern */
            border-radius: 10px;             /* โค้งมน */
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);  /* เงานุ่มๆ */
            max-width: 100%;
            word-wrap: break-word;
        }

        .note-text {
            font-size: 14px;
            line-height: 1.45;
            color: #333;
        }

        .note-footer {
            border-top: 1px dashed #d0d0d0;  /* เส้นแบ่งสวย ๆ แบบโปร */
            padding-top: 6px;
        }

        .note-icon-btn.btn-icon {
            border: none !important;
            background: transparent none !important;
            color: #08c !important;
            padding: 0 !important;
            line-height: 20px !important;
            box-shadow: none !important;
            margin: 0 3px !important;
            min-width: 0 !important;
            width: auto !important;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;

        }

        .note-icon-btn i {
            font-size: 13px;
            pointer-events: none; /* กันไอคอนกินคลิก */
        }

        .no-resize {
            resize: none !important;
            overflow: auto !important; /* ป้องกันไม่ให้ browser ใส่ UI ของ resize */
        }

        /* Safari / iOS บางรุ่น */
        textarea.no-resize {
            -webkit-resize: none !important;
        }

        /* Firefox */
        textarea.no-resize {
            overflow: hidden !important; /* ถ้าต้องการซ่อน scrollbar แบบ UX ดีขึ้น */
        }

        .h-85 {
            height: 85% !important;
        }

        .h-90 {
            height: 90% !important;
        }

        .h-95 {
            height: 95% !important;
        }
        .chat-msg-footer {
            position: relative;
        }

        /* ปุ่ม … ให้ซ่อนอยู่ก่อน */
        .chat-msg-menu-toggle {
            opacity: 0;
            pointer-events: none;
            transition: opacity .15s ease-in-out;
        }

        /* พอ hover แถวเวลา ให้ปุ่มโผล่ */
        .chat-msg-footer:hover .chat-msg-menu-toggle {
            opacity: 1;
            pointer-events: auto;
        }

        /* ไอคอน ... ให้เล็กหน่อย */
        .chat-msg-menu-toggle .fa {
            font-size: 11px;
        }
        .chat-msg-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .chat-msg-in {
            justify-content: flex-start;
        }

        .chat-msg-out {
            justify-content: flex-end;
        }

        .chat-avatar {
            flex: 0 0 auto;
        }

        .chat-avatar-img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* จำกัดความกว้าง bubble ไม่ให้ยาวเต็มจอ */
        .chat-msg-main {
            max-width: 70%;
        }

        /* เวลาใต้ bubble ให้ดูเตี้ย ๆ คล้าย LINE */
        .chat-msg-time {
            line-height: 1.1;
        }

        .chat-time-wrapper {
            position: relative;
        }

        /* ปุ่ม ... ซ่อนก่อน */
        .chat-msg-menu-toggle {
            opacity: 0;
            pointer-events: none;
            transition: opacity .15s ease-in-out;
        }

        /* โผล่ตอน hover */
        .chat-time-wrapper:hover .chat-msg-menu-toggle {
            opacity: 1;
            pointer-events: auto;
        }

        /* ลดขนาด icon … */
        .chat-msg-menu-toggle .fa {
            font-size: 11px;
        }
        .no-resize {
            resize: none;
        }

        /* กล่อง textarea ให้ดูเป็นกรอบเดียวกับของ LINE */
        .chat-reply-textarea {
            border-radius: 4px;
            font-size: 14px;
        }

        /* ปุ่มไอคอนล่างซ้าย ให้ดูเป็นแค่ไอคอนบาง ๆ */
        .chat-tool-btn {
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        .chat-tool-btn i {
            font-size: 16px;
            color: #7a8699;
        }

        .chat-tool-btn:disabled i {
            opacity: .4;
        }

        /* ปุ่มส่งสีเขียว */
        .chat-send-btn {
            min-width: 64px;
            font-weight: 600;
            border-radius: 4px;
        }

        .assignee-list {
            max-height: 320px;
            overflow-y: auto;
        }

        .chat-reply-preview {
            border-left: 3px solid rgba(0,0,0,0.1);
            padding-left: 6px;
        }

        .chat-reply-quote {
            font-size: 12px;
            color: #555;
            background: #f5f5f5;
            border-radius: 4px;
            padding: 4px 6px;
            max-width: 260px;
            word-break: break-word;
        }

        .chat-reply-preview {
            border-radius: 10px;
            padding: 6px 8px;
            background-color: #ffffff; /* ให้เป็นพื้นขาวบน bubble ฟ้า */
        }

        .gt-msg-agent .chat-reply-preview {
            /* ถ้าอยากให้เนียนกับ bubble ฟ้า ปรับ opacity เล็กน้อย */
            background-color: rgba(255,255,255,0.9);
        }

        .chat-reply-avatar {
            width: 32px;
            height: 32px;
            border-radius: 4px; /* LINE จริงเป็นเหลี่ยมมน ไม่ใช่วงกลม */
            object-fit: cover;
        }

        .chat-reply-name {
            font-weight: 600;
            font-size: 13px;
        }

        .chat-reply-quote {
            font-size: 12px;
            color: #555;
            background: #f5f5f5;
            border-radius: 6px;
            padding: 4px 6px;
            margin-top: 2px;
            word-break: break-word;
        }
        /* ====== แถบด้านบน: ข้อความที่ปักหมุด ====== */
        .chat-pinned-bar {
            background-color: #fffbe6;          /* เหลืองอ่อนแบบแจ้งเตือน */
            border-radius: 6px;
            padding: 6px 8px;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .chat-pinned-bar > .d-flex i {
            font-size: 13px;
        }

        /* list ข้อความที่ปักหมุดด้านใน */
        .chat-pinned-list {
            max-height: 80px;                   /* จำกัดความสูง เผื่อปักหลายอัน */
            overflow-y: auto;
        }

        /* แต่ละแถวข้อความที่ปักหมุด */
        .chat-pinned-item {
            cursor: pointer;
            padding: 4px 6px;
            border-radius: 4px;
            transition: background-color .15s ease-in-out;
        }

        .chat-pinned-item:hover {
            background-color: #fff3cd;          /* hover เหลืองเข้มขึ้นนิดหน่อย */
        }

        /* จำกัดความกว้างตัวข้อความ ป้องกันลากยาวเกิน */
        .chat-pinned-item .text-truncate {
            max-width: 240px;
        }

        /* เวลาแสดงเวลาใน pinned bar ให้ดูเบาลงหน่อย */
        .chat-pinned-item .text-muted {
            font-size: 11px;
        }

        /* ====== ปรับ margin กับ separator ของวันนิดหน่อย ให้ไม่อัดกับ pinned bar ====== */
        .chat-day-separator {
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .chat-day-separator .badge {
            background-color: #f8f9fa;
            border-radius: 12px;
        }

        /* ====== layout message row (กลางจอ) เผื่อยังไม่มี ====== */
        .chat-msg-row {
            display: flex;
            align-items: flex-end;
        }

        /* ฝั่งลูกค้า (inbound) อยู่ซ้าย */
        .chat-msg-in {
            justify-content: flex-start;
        }

        /* ฝั่งพนักงาน (outbound) อยู่ขวา */
        .chat-msg-out {
            justify-content: flex-end;
        }

        /* กล่อง avatar ด้านหน้า bubble */
        .chat-avatar {
            width: 32px;
            flex: 0 0 32px;
        }

        .chat-avatar-img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* ตัว bubble จริง ๆ (ใช้ร่วมกับ class เดิม messageBubbleClass) */
        .chat-msg-main {
            max-width: 75%;
        }

        /* เวลา + ปุ่ม ... ด้านล่าง bubble */
        .chat-time-wrapper {
            font-size: 11px;
        }

        /* ปุ่มเมนู ... ให้ไอคอนดูเล็ก ๆ หน่อย */
        .chat-msg-menu-toggle {
            padding: 0 2px !important;
        }


        /* ====== ไฮไลต์ข้อความที่ถูก jump มาจากแถบปักหมุด ====== */
        .chat-msg-row.chat-msg-highlight .p-2.rounded {
            box-shadow: 0 0 0 2px #ffc107;      /* ขอบเหลืองบาง ๆ */
            background-color: #fff8e1 !important;
            transition: background-color .4s ease-out, box-shadow .4s ease-out;
        }

        /* เมื่อเอา class ออก ให้ค่อย ๆ จางคืนสภาพเดิม */
        .chat-msg-row .p-2.rounded {
            transition: background-color .4s ease-out, box-shadow .4s ease-out;
        }
        /* wrapper ให้มีระยะห่างจากขอบนิดหน่อย */
        .chat-pinned-wrapper {
            padding: 6px 16px 4px;
        }

        /* การ์ดหลัก – ให้มีเงาและโค้งแบบลอยเหนือแชต */
        .chat-pinned-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.16);
            overflow: hidden; /* กัน border-radius หลุด */
        }

        /* แถวบนของการ์ด (ข้อความปักหมุดหลัก) */
        .chat-pinned-main {
            padding: 8px 12px;
            cursor: pointer;
        }

        /* icon ปักหมุดเล็กลงหน่อย */
        .chat-pinned-icon {
            font-size: 13px;
        }

        /* ตัวอักษรข้อความ – เล็กลงกว่าฟอนต์แชตทั่วไปนิดหนึ่ง */
        .chat-pinned-text {
            font-size: 13px;
            font-weight: 500;
        }

        /* บรรทัดชื่อ + เวลา */
        .chat-pinned-meta {
            font-size: 11px;
            margin-top: 2px;
        }

        /* ปุ่มลูกศรขวา/ลง */
        .chat-pinned-toggle i {
            font-size: 12px;
        }

        /* list ด้านล่างของการ์ด (ข้อความปักหมุดอื่น ๆ) */
        .chat-pinned-list {
            border-top: 1px solid #f1f5f9;
        }

        /* แถวของข้อความปักหมุดอื่น ๆ */
        .chat-pinned-item {
            display: flex;
            align-items: flex-start;
            padding: 6px 12px 6px 16px;
            cursor: pointer;
            font-size: 12px;
        }

        .chat-pinned-item:hover {
            background-color: #f8fafc;
        }

        /* footer "ไม่แสดงอีก" */
        .chat-pinned-footer {
            padding: 6px 12px 8px 32px;
            font-size: 11px;
            color: #6b7280;
            cursor: pointer;
            border-top: 1px solid #f1f5f9;
        }

        .chat-pinned-footer:hover {
            background-color: #f9fafb;
        }

        /* optional: เอฟเฟกต์ fade ตอนขยาย/ยุบ */
        .fade-enter-active,
        .fade-leave-active {
            transition: opacity 0.15s ease;
        }
        .fade-enter,
        .fade-leave-to {
            opacity: 0;
        }

        /* wrapper ของ message + pinned overlay */
        .chat-message-wrapper {
            position: relative;
        }

        /* การ์ดปักหมุดลอยทับ content */
        .chat-pinned-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10; /* ให้สูงกว่า bubble แชต */
            padding: 4px 16px 0;
            pointer-events: auto;
        }

        /* การ์ด */
        .chat-pinned-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.16);
            overflow: hidden;
        }

        /* แถวบน */
        .chat-pinned-main {
            padding: 8px 12px;
            cursor: pointer;
        }

        .chat-pinned-icon {
            font-size: 13px;
        }

        .chat-pinned-text {
            font-size: 13px;
            font-weight: 500;
        }

        .chat-pinned-meta {
            font-size: 11px;
            margin-top: 2px;
        }

        /* แถวล่าง */
        .chat-pinned-list {
            border-top: 1px solid #f1f5f9;
        }

        .chat-pinned-item {
            display: flex;
            align-items: flex-start;
            padding: 6px 12px 6px 16px;
            cursor: pointer;
            font-size: 12px;
        }

        .chat-pinned-item:hover {
            background-color: #f8fafc;
        }

        .chat-pinned-footer {
            padding: 6px 12px 8px 32px;
            font-size: 11px;
            color: #6b7280;
            cursor: pointer;
            border-top: 1px solid #f1f5f9;
        }

        .chat-pinned-footer:hover {
            background-color: #f9fafb;
        }

        /* ให้ message list เผื่อที่ด้านบนสำหรับการ์ดลอย */
        .chat-message-list.chat-message-has-pinned {
            padding-top: 70px; /* ปรับเลขตามความสูงการ์ดจริง */
        }

        /* effect เปิด/ปิด list ด้านล่าง */
        .fade-enter-active,
        .fade-leave-active {
            transition: opacity 0.15s ease;
        }
        .fade-enter,
        .fade-leave-to {
            opacity: 0;
        }

        /* การ์ดปักหมุด */
        .chat-pinned-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.16);
            overflow: hidden;
            max-width: calc(100% - 24px);
            margin: 4px auto 0;
        }

        /* รายการปักหมุดหลายอันข้างล่าง – เลื่อนในกล่องเอง */
        .chat-pinned-list {
            border-top: 1px solid #f1f5f9;
            max-height: 150px;       /* ปรับเลขตามที่ชอบ */
            overflow-y: auto;
        }

        /* item ข้างใน */
        .chat-pinned-item {
            display: flex;
            align-items: flex-start;
            padding: 6px 12px 6px 16px;
            cursor: pointer;
            font-size: 12px;
        }
        .chat-pinned-item:hover {
            background-color: #f8fafc;
        }
        .chat-pinned-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10;
            padding: 4px 16px 0;
            pointer-events: none;      /* ไม่รับ event ทั้ง overlay */
        }

        .chat-pinned-card {
            pointer-events: auto;      /* ยอมให้คลิกเฉพาะที่ตัวการ์ด */
        }

        .chat-message-wrapper {
            position: relative;
            overflow: hidden;
        }

        .chat-pinned-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10;
            padding: 4px 16px 0;
            pointer-events: none;
        }

        .chat-pinned-card {
            pointer-events: auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.16);
            max-width: calc(100% - 24px);
            margin: 4px auto 0;
            font-size: 12px;
        }

        .chat-pinned-main {
            padding: 8px 12px;
        }

        .chat-pinned-icon {
            font-size: 12px;
        }

        .chat-pinned-text {
            font-size: 12px;
        }

        .chat-pinned-meta {
            font-size: 11px;
        }

        .chat-pinned-list {
            border-top: 1px solid #f1f5f9;
            max-height: 150px;
            overflow-y: auto;
        }

        .chat-pinned-item {
            display: flex;
            align-items: flex-start;
            padding: 6px 12px 6px 16px;
            cursor: pointer;
        }

        .chat-pinned-item:hover {
            background-color: #f8fafc;
        }

        .chat-pinned-footer {
            padding: 6px 12px;
            font-size: 11px;
            color: #64748b;
            border-top: 1px solid #f1f5f9;
            cursor: pointer;
        }

        .chat-pinned-footer:hover {
            background-color: #f8fafc;
        }

        .chat-pinned-toggle i {
            font-size: 12px;
        }

        .chat-message-list {
            height: 100%;
        }


    </style>
    <style>
        .gt-sticker-item {
            border-radius: 8px;
            padding: 4px;
            transition: background-color 0.15s ease, transform 0.15s ease;
        }

        .gt-sticker-item:hover {
            background-color: #f1f3f5;
            transform: translateY(-1px);
        }

        .chat-reply-thumb {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .chat-emoji-picker {
            max-width: 320px;
            font-size: 18px;
            z-index: 5;
        }
        .chat-emoji-picker-wrapper {
            position: absolute;
            z-index: 1050; /* ให้ลอยเหนือ card นิดนึง */
        }

        .emoji-picker-wrapper {
            position: relative;
        }

        .emoji-popover {
            position: absolute;
            top: 30px;            /* ระยะห่างจากปุ่ม */
            left: 0;
            z-index: 2000;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .emoji-popover .emoji-mart {
            width: 260px !important;   /* กำหนดขนาดถ้าต้องการ */
            font-size: 13px;
        }

        .chat-reply-wrapper {
            position: relative;
        }

        .chat-reply-wrapper #chatEmojiContainer {
            position: absolute;
            bottom: 100%;          /* วางเหนือ replybox */
            left: 0;
            width: 100%;           /* กว้างเท่า replybox */
            z-index: 200000;
        }

        /* emoji picker เอง */
        .emoji-overlay-chat {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            max-height: 50%;
            overflow-y: auto;
            width: 100%;           /* สำคัญ! ทำให้มันยืดเต็ม container */
            padding: 8px;
        }


        /* layout */
        .log-row {
            display: flex;
            gap: 12px;
            padding: 10px;
        }

        /* ปุ่มแบบการ์ด */
        .log-card {
            flex: 1;
            border: none !important;
            padding: 18px 0 14px;
            border-radius: 12px;

            display: flex;
            flex-direction: column;
            align-items: center;

            box-shadow: 0 3px 10px rgba(0,0,0,0.2);

            color: #fff !important;
            font-weight: 600;
            font-size: 15px;

            /* ปิดของ btn-app ที่เคยครอบอยู่ */
            background-image: none !important;
            background-size: initial !important;
            background-color: transparent !important;
        }

        /* ฝาก (เขียว) */
        .log-card-deposit {
            background-color: #16a34a !important; /* เขียว */
            border-color: #16a34a !important;
        }

        /* ถอน (แดง) */
        .log-card-withdraw {
            background-color: #dc2626 !important; /* แดง */
            border-color: #dc2626 !important;
        }

        /* ไอคอน */
        .log-icon {
            font-size: 22px;
            margin-bottom: 4px;
            display: flex;
            gap: 6px;
        }

        /* hover */
        .log-card:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.28);
        }


        /* ครอบทุกการ์ดใน modal */
        .adjust-card-wrapper {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 8px 4px 12px;
        }

        /* base ของแต่ละปุ่มการ์ด */
        .adjust-card {
            width: 100%;
            border: none !important;
            border-radius: 12px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            text-align: left;

            box-shadow: 0 2px 8px rgba(0,0,0,0.16);
            transition: all 0.15s ease;

            background-color: #f3f4f6 !important; /* default เผื่อไม่มี class type */
            color: #111827 !important;
        }

        /* icon ด้านซ้าย */
        .adjust-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 20px;
            background-color: rgba(255,255,255,0.25);
        }

        /* ข้อความด้านขวา */
        .adjust-card-content {
            display: flex;
            flex-direction: column;
        }

        .adjust-card-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .adjust-card-sub {
            font-size: 12px;
            opacity: 0.9;
        }

        /* hover แล้วดูเป็นปุ่มจริง ๆ */
        .adjust-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.22);
        }

        /* theme สีแต่ละประเภท */
        .adjust-money {
            background-color: #16a34a !important;   /* เขียว */
            color: #ffffff !important;
        }

        .adjust-money .adjust-card-icon {
            color: #16a34a;
            background-color: #ecfdf3;
        }

        .adjust-point {
            background-color: #2563eb !important;   /* น้ำเงิน */
            color: #ffffff !important;
        }

        .adjust-point .adjust-card-icon {
            color: #2563eb;
            background-color: #eff6ff;
        }

        .adjust-diamond {
            background-color: #7c3aed !important;   /* ม่วง */
            color: #ffffff !important;
        }

        .adjust-diamond .adjust-card-icon {
            color: #7c3aed;
            background-color: #f5f3ff;
        }
        .refill-form-col {
            border-right: 1px solid #e5e7eb;
            margin-bottom: 10px;
        }

        @media (max-width: 767.98px) {
            .refill-form-col {
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
                margin-bottom: 12px;
                padding-bottom: 8px;
            }
        }

        .refill-log-col {
            display: flex;
            flex-direction: column;
        }

        .refill-log-card {
            background: #f9fafb;
            border-radius: 10px;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .refill-log-title {
            font-size: 14px;
            font-weight: 600;
        }

        .refill-log-sub {
            font-size: 12px;
            color: #6b7280;
        }

        .refill-log-table {
            font-size: 11px;
            margin-top: 4px;
            max-height: 320px; /* ให้ sticky-header มีผล และไม่ล้น modal */
        }

        /* wrapper ของ replybox */
        .chat-reply-wrapper {
            position: relative;
        }

        /* emoji picker ลอยทับเหนือ replybox */
        .emoji-overlay-chat {
            position: absolute;
            bottom: calc(100% + 4px);   /* อยู่เหนือกล่องพิมพ์นิดหน่อย */
            left: 0;
            right: 0;                   /* = กว้างเท่ากับ replybox */
            z-index: 200000;            /* ให้อยู่ทับข้อความแชต */

            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);

            max-height: none;
            overflow: visible;
            padding: 6px;
            margin: 0;                  /* กัน margin ไปดัน layout */
        }

        /* emoji-mart ชอบกำหนด width เอง → บังคับให้เต็มกรอบ */
        .emoji-overlay-chat .emoji-mart {
            width: 100% !important;
            max-width: 100% !important;
            height: 250px !important;
            max-height: 250px !important;
            box-sizing: border-box;
        }

        .line-oa-chat-page {
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .line-oa-sidebar {
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
        }

        .line-oa-sidebar .conversation-list {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }

        .chat-image-lightbox {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.75);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .chat-image-lightbox-img {
            max-width: 96vw;
            max-height: 92vh;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,.4);
            cursor: zoom-out;
        }

        .chat-image-lightbox-close {
            position: fixed;
            top: 10px;
            right: 14px;
            width: 42px;
            height: 42px;
            border: 0;
            border-radius: 999px;
            background: rgba(0,0,0,.5);
            color: #fff;
            font-size: 28px;
            line-height: 42px;
            text-align: center;
            cursor: pointer;
        }

        .no-scroll {
            overflow: hidden;
        }

    </style>

@endpush