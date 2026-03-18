<!-- Web Application Manifest -->
<link rel="manifest" href="{{ route('laravelpwa.manifest') }}">
<!-- Chrome for Android theme color -->
<meta name="theme-color" content="{{ $config['theme_color'] }}">

<!-- Add to homescreen for Chrome on Android -->
<meta name="mobile-web-app-capable" content="{{ $config['display'] == 'standalone' ? 'yes' : 'no' }}">
<meta name="application-name" content="{{ $config['short_name'] }}">


<!-- Add to homescreen for Safari on iOS -->
<meta name="apple-mobile-web-app-capable" content="{{ $config['display'] == 'standalone' ? 'yes' : 'no' }}">
<meta name="apple-mobile-web-app-status-bar-style" content="{{  $config['status_bar'] }}">
<meta name="apple-mobile-web-app-title" content="{{ $config['short_name'] }}">
{{--<link rel="apple-touch-icon" href="{{ data_get(end($config['icons']), 'src') }}">--}}


<script>
    (async () => {
        if (!('serviceWorker' in navigator)) return;

        const url = "/serviceworker.js?v={{ filemtime(public_path('serviceworker.js')) }}";
        try {
            const reg = await navigator.serviceWorker.register(url, { scope: '/' });
            // อัปเดต-เข้าคุมไว
            try { reg.update(); } catch {}
            if (reg.waiting) reg.waiting.postMessage({ type: 'SKIP_WAITING' });
            reg.addEventListener('updatefound', () => {
                reg.installing?.addEventListener('statechange', () => {
                    if (reg.waiting) reg.waiting.postMessage({ type: 'SKIP_WAITING' });
                });
            });
        } catch (e) {
            console.error('[SW] register failed:', e);
        }

        navigator.serviceWorker.addEventListener('controllerchange', () => {
            console.log('[SW] controller changed');
            // ต้องการรีเฟรชทันทีเปิดบรรทัดล่าง
            // location.reload();
        });
    })();
</script>

{{--<script>--}}
{{--    (() => {--}}
{{--        // ===== CONFIG =====--}}
{{--        const VAPID_PUBLIC_KEY = '{{ env('VAPID_PUBLIC_KEY') }}';--}}
{{--        const API_TRACK_BASE   = '{{ config('services.track.base_url') }}'; // e.g. https://api789.autokuu.com--}}
{{--        const PRESENCE_URL     = `${API_TRACK_BASE}/api/track/presence`;--}}
{{--        const EVENT_URL        = `${API_TRACK_BASE}/api/track/event`;--}}
{{--        const SUBSCRIBE_URL    = `${API_TRACK_BASE}/api/push/subscribe`;--}}

{{--        // กัน include ซ้ำ--}}
{{--        if (window.__PUSH_BUNDLE_BOUND__) return;--}}
{{--        window.__PUSH_BUNDLE_BOUND__ = 'v3';--}}

{{--        // ===== LOGIN GATE =====--}}
{{--        const USER_CODE = @json(optional(auth('customer')->user())->code);--}}
{{--        const USER_ID   = @json(optional(auth('customer')->user())->id);--}}
{{--        const LOGGED_IN = !!USER_CODE;--}}

{{--        // ===== Capability =====--}}
{{--        const hasSW    = 'serviceWorker' in navigator;--}}
{{--        const hasPush  = 'PushManager' in window;--}}
{{--        const hasNotif = 'Notification' in window;--}}

{{--        // ===== State =====--}}
{{--        let inFlight = false;--}}

{{--        // ===== Helpers =====--}}
{{--        function sameOrigin(url){ try { return new URL(url, location.origin).origin === location.origin; } catch { return false; } }--}}
{{--        const sleep = (ms) => new Promise(r => setTimeout(r, ms));--}}

{{--        function urlBase64ToUint8Array(base64String) {--}}
{{--            const padding = '='.repeat((4 - (base64String.length % 4)) % 4);--}}
{{--            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');--}}
{{--            const raw = atob(base64);--}}
{{--            const out = new Uint8Array(raw.length);--}}
{{--            for (let i=0;i<raw.length;i++) out[i] = raw.charCodeAt(i);--}}
{{--            return out;--}}
{{--        }--}}

{{--        async function fetchJSON(url, { method='POST', data=null, keepalive=false, timeout=8000 } = {}) {--}}
{{--            const isSame = sameOrigin(url);--}}
{{--            const headers = { 'Accept':'application/json', 'Content-Type':'application/json' };--}}
{{--            if (isSame && window.csrfToken) headers['X-CSRF-TOKEN'] = window.csrfToken;--}}

{{--            const ctrl = new AbortController();--}}
{{--            const id = setTimeout(() => ctrl.abort('timeout'), timeout);--}}
{{--            try {--}}
{{--                const res = await fetch(url, {--}}
{{--                    method, headers,--}}
{{--                    body: data ? JSON.stringify(data) : null,--}}
{{--                    credentials: isSame ? 'same-origin' : 'omit',--}}
{{--                    keepalive,--}}
{{--                    signal: ctrl.signal--}}
{{--                });--}}
{{--                if (res.status === 204) return null;--}}
{{--                let json = null; try { json = await res.json(); } catch {}--}}
{{--                if (!res.ok) throw new Error((json && (json.message || json.error)) || `HTTP ${res.status}`);--}}
{{--                return json;--}}
{{--            } finally { clearTimeout(id); }--}}
{{--        }--}}

{{--        function sendBeaconJSON(url, data) {--}}
{{--            const blob = new Blob([JSON.stringify(data)], { type:'application/json' });--}}
{{--            return (navigator.sendBeacon && navigator.sendBeacon(url, blob))--}}
{{--                || fetchJSON(url, { method:'POST', data, keepalive:true }).catch(()=>{});--}}
{{--        }--}}

{{--        // ใช้ SW ที่ Bootstrap ลงทะเบียนไว้แล้ว (ไม่ register ซ้ำ)--}}
{{--        async function readySW() {--}}
{{--            if (!hasSW) return null;--}}
{{--            return await navigator.serviceWorker.ready;--}}
{{--        }--}}

{{--        // ===== Telemetry (รันได้เฉพาะตอนล็อกอิน) =====--}}
{{--        (function telemetry(){--}}
{{--            if (!LOGGED_IN) return;--}}

{{--            const CID_KEY = 'cid_v1';--}}
{{--            let CID = localStorage.getItem(CID_KEY);--}}
{{--            if (!CID) { CID = (crypto?.randomUUID?.() || Math.random().toString(36).slice(2)); localStorage.setItem(CID_KEY, CID); }--}}

{{--            const DM  = (m => m && m.matches)(window.matchMedia?.('(display-mode: standalone)')) ? 'standalone' : 'browser';--}}
{{--            const IS_IOS_STANDALONE = (typeof navigator.standalone !== 'undefined' && navigator.standalone === true);--}}
{{--            const MODE = (DM === 'standalone' || IS_IOS_STANDALONE) ? 'pwa' : 'web';--}}

{{--            const SWV = (() => {--}}
{{--                const url = navigator.serviceWorker?.controller?.scriptURL || '';--}}
{{--                const q = url.split('?')[1] || '';--}}
{{--                return new URLSearchParams(q).get('v') || null;--}}
{{--            })();--}}

{{--            function baseProps(){--}}
{{--                return {--}}
{{--                    mode: MODE, display_mode: DM,--}}
{{--                    sw: !!navigator.serviceWorker?.controller,--}}
{{--                    permission: (typeof Notification!=='undefined') ? Notification.permission : 'unsupported',--}}
{{--                    path: location.pathname,--}}
{{--                    viewport: { w: innerWidth, h: innerHeight, dpr: devicePixelRatio || 1 },--}}
{{--                    net: navigator.connection?.effectiveType || 'unknown',--}}
{{--                    cid: CID, sw_version: SWV--}}
{{--                };--}}
{{--            }--}}
{{--            function logEvent(name, extra={}) {--}}
{{--                return sendBeaconJSON(EVENT_URL, {--}}
{{--                    name, mode: MODE, props: Object.assign({}, baseProps(), extra),--}}
{{--                    cid: CID, user_id: USER_ID ?? null, code: USER_CODE ?? null--}}
{{--                });--}}
{{--            }--}}
{{--            function ping(stage) {--}}
{{--                sendBeaconJSON(PRESENCE_URL, {--}}
{{--                    client_id: CID, mode: MODE, display_mode: DM,--}}
{{--                    sw: !!navigator.serviceWorker?.controller,--}}
{{--                    stage, path: location.pathname,--}}
{{--                    user_id: USER_ID ?? null, code: USER_CODE ?? null--}}
{{--                });--}}
{{--            }--}}

{{--            ping('enter'); logEvent('page_loaded');--}}
{{--            setInterval(() => ping('heartbeat'), 60_000);--}}
{{--            addEventListener('pagehide', () => ping('leave'));--}}
{{--            addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') ping('leave'); });--}}
{{--            addEventListener('beforeinstallprompt', () => logEvent('a2h_prompt_ready'));--}}
{{--            addEventListener('appinstalled', () => logEvent('app_installed'));--}}

{{--            // ===== subscribeWithLog (ขอสิทธิ/สมัคร push เฉพาะตอนล็อกอิน) =====--}}
{{--            window.subscribeWithLog = async function(reason='manual'){--}}
{{--                if (!LOGGED_IN) return { ok:false, reason:'not_logged_in' };--}}
{{--                if (!(hasSW && hasPush && hasNotif)) return { ok:false, reason:'unsupported' };--}}
{{--                if (inFlight) return { ok:false, reason:'busy' };--}}
{{--                inFlight = true;--}}

{{--                try {--}}
{{--                    const perm = Notification.permission;--}}
{{--                    if (perm === 'denied') return { ok:false, reason:'permission_denied' };--}}

{{--                    const reg = await readySW();--}}
{{--                    if (!reg) return { ok:false, reason:'sw_failed' };--}}

{{--                    let finalPerm = perm;--}}
{{--                    if (perm === 'default') {--}}
{{--                        finalPerm = await Notification.requestPermission();--}}
{{--                        logEvent('notify_permission_prompted', { result: finalPerm, reason });--}}
{{--                    }--}}
{{--                    if (finalPerm !== 'granted') return { ok:false, reason:'permission_not_granted' };--}}

{{--                    let sub = await reg.pushManager.getSubscription();--}}
{{--                    if (!sub) {--}}
{{--                        sub = await reg.pushManager.subscribe({--}}
{{--                            userVisibleOnly: true,--}}
{{--                            applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)--}}
{{--                        });--}}
{{--                        logEvent('notify_subscribed_browser', { reason });--}}
{{--                    }--}}

{{--                    const payload = {--}}
{{--                        endpoint: sub.endpoint,--}}
{{--                        keys: sub.toJSON().keys,--}}
{{--                        user_id: USER_ID ?? null,--}}
{{--                        code: USER_CODE ?? null,--}}
{{--                        ua: navigator.userAgent,--}}
{{--                        lang: navigator.language || null--}}
{{--                    };--}}
{{--                    await fetchJSON(SUBSCRIBE_URL, { data: payload });--}}
{{--                    logEvent('notify_subscribed_backend', { reason });--}}
{{--                    return { ok:true };--}}
{{--                } catch (e) {--}}
{{--                    console.warn('[push] subscribe error:', e);--}}
{{--                    return { ok:false, reason:'error', error: e?.message || String(e) };--}}
{{--                } finally {--}}
{{--                    inFlight = false;--}}
{{--                }--}}
{{--            };--}}

{{--            // รับสัญญาณจาก SW ว่าหมดอายุ → สมัครใหม่เงียบ ๆ (เฉพาะล็อกอิน)--}}
{{--            navigator.serviceWorker?.addEventListener('message', async (e) => {--}}
{{--                if (e?.data?.type === 'PUSH_SUBSCRIPTION_EXPIRED') {--}}
{{--                    try { await window.subscribeWithLog('resub_from_sw'); } catch {}--}}
{{--                }--}}
{{--            });--}}

{{--            // เผย util ไว้ debug--}}
{{--            window.__pushDebug = { readySW };--}}
{{--        })();--}}

{{--        // ===== ปุ่มสมัคร push บนหน้า (ทำงานได้แม้ไม่ได้ล็อกอิน แต่จะขึ้น reason ชัด) =====--}}
{{--        document.addEventListener('DOMContentLoaded', () => {--}}
{{--            const enableBtns = document.querySelectorAll('[data-push-enable]');--}}
{{--            enableBtns.forEach(btn => {--}}
{{--                btn.addEventListener('click', async (e) => {--}}
{{--                    e.preventDefault();--}}
{{--                    btn.setAttribute('disabled', 'disabled');--}}
{{--                    btn.classList.add('is-loading');--}}

{{--                    const fn = window.subscribeWithLog;--}}
{{--                    const res = await (typeof fn === 'function' ? fn('button_click') : Promise.resolve({ ok:false, reason:'no_func' }));--}}

{{--                    btn.classList.remove('is-loading');--}}
{{--                    btn.removeAttribute('disabled');--}}

{{--                    if (res.ok) {--}}
{{--                        try { new Notification('เปิดการแจ้งเตือนแล้ว', { body: 'คุณจะได้รับการอัปเดตแบบเรียลไทม์' }); } catch {}--}}
{{--                    } else {--}}
{{--                        const msg = ({--}}
{{--                            no_func: 'ไม่พบระบบสมัครแจ้งเตือน',--}}
{{--                            not_logged_in: 'กรุณาเข้าสู่ระบบก่อน',--}}
{{--                            unsupported: 'อุปกรณ์/เบราว์เซอร์ไม่รองรับการแจ้งเตือน',--}}
{{--                            permission_denied: 'คุณปฏิเสธการอนุญาตแจ้งเตือนไว้',--}}
{{--                            permission_not_granted: 'ยังไม่ได้อนุญาตการแจ้งเตือน',--}}
{{--                            busy: 'กำลังดำเนินการ โปรดลองอีกครั้ง',--}}
{{--                            error: 'สมัครแจ้งเตือนล้มเหลว',--}}
{{--                            sw_failed: 'Service Worker ไม่พร้อม'--}}
{{--                        })[res.reason] || 'ไม่สามารถเปิดการแจ้งเตือนได้';--}}
{{--                        if (window.VueApp?.$toast) VueApp.$toast.error(msg); else alert(msg);--}}
{{--                    }--}}
{{--                }, { passive:false });--}}
{{--            });--}}
{{--        });--}}

{{--    })();--}}
{{--</script>--}}

<script>
    (() => {
        // ===== CONFIG =====
        const LOGGED_IN = !!@json(optional(auth('customer')->user())->code);
        const DECISION_KEY = 'notify_popup_decision_v1';
        const EXPIRE_DAYS_ALLOWED = 365;
        const EXPIRE_DAYS_DENIED  = 30;
        const AUTO_SHOW_DELAY_MS  = 1200;

        // ===== helpers =====
        const ls = {
            set(value, days){
                const rec = { v:value, at:Date.now(), ttl:days*86400e3 };
                localStorage.setItem(DECISION_KEY, JSON.stringify(rec));
            },
            get(){
                try{
                    const rec = JSON.parse(localStorage.getItem(DECISION_KEY) || 'null');
                    if (!rec) return null;
                    if ((Date.now() - rec.at) > rec.ttl) { localStorage.removeItem(DECISION_KEY); return null; }
                    return rec.v; // 'allowed' | 'denied' | 'dismissed' | 'permission_denied' | 'unsupported'
                } catch { return null; }
            },
            reset(){ localStorage.removeItem(DECISION_KEY); }
        };

        function shouldShowPopup(){
            if (!('Notification' in window)) return false;
            if (Notification.permission === 'granted' || Notification.permission === 'denied') return false;
            return !ls.get();
        }

        // ===== wire after DOM ready =====
        document.addEventListener('DOMContentLoaded', () => {
            const wrapper   = document.querySelector('.notify-popup-wrapper');
            if (!wrapper) { console.debug('[notify] no wrapper in DOM'); return; }

            // 1) ถ้าไม่ล็อกอิน -> ซ่อนเงียบ ๆ (ไม่ลบทิ้ง เพื่อรองรับ SPA)
            if (!LOGGED_IN) {
                wrapper.style.display = 'none';
                wrapper.dataset.requiresLogin = '1';
                console.debug('[notify] hidden: require login');
                return;
            } else {
                // ล็อกอินแล้ว -> ให้ wrapper พร้อมแสดงได้
                wrapper.style.display = '';
                delete wrapper.dataset.requiresLogin;
            }

            // 2) เตรียม element/ปุ่ม
            const container = wrapper.querySelector('.notify-popup-container');
            const btnAllow  = wrapper.querySelector('.allow-notify');
            const btnDeny   = wrapper.querySelector('.not-allow-notify');
            const btnClose  = wrapper.querySelector('.close-notify');
            const errorHolder = ensureErrorHolder();
            let busy = false;

            function ensureErrorHolder(){
                let el = wrapper.querySelector('.notify-error');
                if (!el) {
                    el = document.createElement('div');
                    el.className = 'notify-error';
                    wrapper.querySelector('.content-detail')?.appendChild(el);
                }
                el.style.display = 'none';
                return el;
            }
            function showError(msg){ errorHolder.textContent = msg; errorHolder.style.display = msg ? 'block':'none'; }
            function setBusy(s){ busy = s; if (s) btnAllow?.setAttribute('disabled','disabled'); else btnAllow?.removeAttribute('disabled'); }

            // a11y focus trap
            let prevActive=null;
            function trapFocus(panel){
                prevActive = document.activeElement;
                const focusable = panel.querySelectorAll('button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])');
                const first=focusable[0], last=focusable[focusable.length-1];
                (first||panel).focus();
                function handler(e){
                    if (e.key!=='Tab') return;
                    if (e.shiftKey && document.activeElement===first) { last.focus(); e.preventDefault(); }
                    else if (!e.shiftKey && document.activeElement===last) { first.focus(); e.preventDefault(); }
                }
                panel.__trapHandler = handler; panel.addEventListener('keydown', handler);
                wrapper.addEventListener('click', onBackdrop, { passive:true });
            }
            function releaseFocus(){
                const panel = container;
                if (panel?.__trapHandler) { panel.removeEventListener('keydown', panel.__trapHandler); delete panel.__trapHandler; }
                wrapper.removeEventListener('click', onBackdrop);
                if (prevActive && prevActive.focus) try { prevActive.focus(); } catch {}
            }
            function onBackdrop(e){ if (!container.contains(e.target)) closePopup('dismissed'); }

            function openPopup(reason='auto'){
                wrapper.classList.add('show');
                trapFocus(container);
                console.debug('[notify] popup open:', { reason });
                try { window.dispatchEvent(new CustomEvent('notify:popup_shown', { detail:{ reason } })); } catch {}
            }
            function closePopup(reason='dismissed'){
                wrapper.classList.remove('show');
                releaseFocus();
                // เก็บ decision เฉพาะกรณีที่ไม่ใช่ allow
                if (reason !== 'allowed') ls.set(reason, EXPIRE_DAYS_DENIED);
                console.debug('[notify] popup close:', { reason });
            }

            // 3) bind ปุ่ม
            btnClose?.addEventListener('click', () => closePopup('dismissed'));
            btnDeny ?.addEventListener('click', () => { ls.set('denied', EXPIRE_DAYS_DENIED); closePopup('denied'); });
            btnAllow?.addEventListener('click', async () => {
                if (busy) return;
                showError(''); setBusy(true);
                try{
                    const fn = window.subscribeWithLog;
                    if (typeof fn !== 'function') {
                        showError('ไม่พบระบบสมัครแจ้งเตือน (subscribeWithLog)');
                        ls.set('unsupported', EXPIRE_DAYS_DENIED);
                        return;
                    }
                    const res = await fn('popup_click');
                    if (res?.ok) {
                        ls.set('allowed', EXPIRE_DAYS_ALLOWED);
                        closePopup('allowed');
                        Toast.fire({
                            icon: 'success',
                            title: "ขอบคุณที่ ลงทะเบียนรับข่าวสารกับทางเวบ"
                        });
                        try { new Notification('เปิดการแจ้งเตือนแล้ว', { body:'คุณจะได้รับข่าวสารและโปรโมชั่นพิเศษ' }); } catch {}
                    } else {
                        const map = {
                            not_logged_in:'กรุณาเข้าสู่ระบบก่อนใช้งานการแจ้งเตือน',
                            unsupported:'อุปกรณ์/เบราว์เซอร์นี้ยังไม่รองรับการแจ้งเตือน',
                            permission_denied:'คุณปฏิเสธสิทธิการแจ้งเตือน',
                            permission_not_granted:'ยังไม่ได้อนุญาตการแจ้งเตือน',
                            busy:'กำลังดำเนินการ โปรดลองใหม่อีกครั้ง',
                            error:'สมัครการแจ้งเตือนล้มเหลว',
                            sw_failed:'Service Worker ไม่พร้อม',
                            no_func:'ไม่พบระบบสมัครแจ้งเตือน'
                        };
                        Toast.fire({
                            icon: 'error',
                            title: map[res?.reason] || 'ไม่สามารถเปิดการแจ้งเตือนได้'
                        });
                        if (['permission_denied','unsupported','not_logged_in'].includes(res?.reason)) {
                            ls.set(res.reason, EXPIRE_DAYS_DENIED);
                        }
                    }
                } catch(e) {
                    showError('เกิดข้อผิดพลาดที่ไม่คาดคิด');
                } finally {
                    setBusy(false);
                }
            });

            // 4) Auto-open เฉพาะล็อกอิน + เงื่อนไขผ่าน
            try {
                if (Notification && Notification.permission === 'default' && shouldShowPopup()) {
                    setTimeout(() => openPopup('auto'), AUTO_SHOW_DELAY_MS);
                } else {
                    console.debug('[notify] skip auto-open', {
                        permission: (window.Notification ? Notification.permission : 'unsupported'),
                        decision: ls.get()
                    });
                }
            } catch {}

            // 5) Debug API (ช่วยเทสปัญหา)
            window.__notifyPopup = {
                open: () => openPopup('manual'),
                close: () => closePopup('manual'),
                reset: () => { ls.reset(); console.debug('[notify] decision reset'); },
                state: () => ({
                    logged_in: LOGGED_IN,
                    permission: (window.Notification ? Notification.permission : 'unsupported'),
                    decision: ls.get(),
                    wrapper: !!document.querySelector('.notify-popup-wrapper')
                })
            };
        });
    })();
</script>







