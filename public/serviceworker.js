// sw v2.0.3 — Asset-only Service Worker (ไม่แตะ document/navigation เลย)
const WEB = 'PANTER918';
const CACHE_VERSION = 'v2.0.3';
const STATIC_CACHE = `pwa-static-${WEB}-${CACHE_VERSION}`;
const API_CACHE    = `pwa-api-${WEB}-${CACHE_VERSION}`; // ถ้าไม่อยากแคช API ให้เลิกใช้และลบบล็อก API ด้านล่าง
const OFFLINE_URL  = '/offline';

// อนุญาตแคช static ข้ามโดเมน
const ALLOWED_CROSS_ORIGINS = [
    'https://fonts.googleapis.com',
    'https://fonts.gstatic.com',
    'https://cdn.jsdelivr.net',
    'https://cdnjs.cloudflare.com',
];

// Precache เฉพาะ static asset ที่ปลอดภัย
const filesToCache = [
    OFFLINE_URL,
    '/images/app/icon-192.png',
    '/images/app/badge-72.png',
    '/assets/ui/js/ui.js',
    '/assets/admin/css/web.css',
    '/assets/ui/css/ui.css',
    '/vendor/toasty/dist/toasty.min.css',
    '/vendor/daterangepicker/daterangepicker.css',
    '/vendor/daterangepicker/daterangepicker.js',
    '/vendor/datatables/buttons.server-side.js',
    '/storage/sound/alert.mp3',
];

/* ---------- helpers ---------- */
const isHttp = (req) => {
    const u = new URL(req.url);
    return u.protocol === 'http:' || u.protocol === 'https:';
};
const sameOrigin = (req) => new URL(req.url).origin === self.location.origin;
const isAllowedExternal = (req) => ALLOWED_CROSS_ORIGINS.includes(new URL(req.url).origin);

async function putSafe(cacheName, req, res) {
    try {
        const cache = await caches.open(cacheName);
        await cache.put(req, res.clone());
    } catch (e) {
        console.warn('[SW] putSafe fail:', e && e.message);
    }
}

self.addEventListener('install', (event) => {
    console.log('[SW] Installing (asset-only)…', CACHE_VERSION);
    self.skipWaiting();
    event.waitUntil((async () => {
        const cache = await caches.open(STATIC_CACHE);
        const results = await Promise.allSettled(
            filesToCache.map(async (url) => {
                try {
                    const req = new Request(url, { credentials: 'same-origin', cache: 'no-cache' });
                    const res = await fetch(req);
                    if (!res.ok && res.type !== 'opaque') throw new Error(`${url} ${res.status}`);
                    await cache.put(req, res.clone());
                } catch (err) {
                    console.warn('[SW] ❌ Precache fail:', url, err && err.message);
                }
            })
        );
        const ok = results.filter(r => r.status === 'fulfilled').length;
        console.log(`[SW] Precache done (${ok}/${filesToCache.length})`);
    })());
});

self.addEventListener('activate', (event) => {
    console.log('[SW] Activating (asset-only)…', CACHE_VERSION);
    event.waitUntil((async () => {
        if ('navigationPreload' in self.registration) {
            try { await self.registration.navigationPreload.disable(); } catch {}
        }
        const keys = await caches.keys();
        await Promise.all(
            keys.map(k => {
                const keep = [STATIC_CACHE, API_CACHE];
                if (!keep.includes(k) && k.startsWith('pwa-')) {
                    console.log('[SW] 🔥 delete', k);
                    return caches.delete(k);
                }
            })
        );
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    const msg = event.data || {};
    if (msg.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    if (msg.type === 'RESET_SW') {
        (async () => {
            try {
                const keys = await caches.keys();
                await Promise.all(keys.map(k => caches.delete(k)));
                await self.registration.unregister();
                event.ports?.[0]?.postMessage?.('OK');
            } catch (e) {
                event.ports?.[0]?.postMessage?.('ERR');
            }
        })();
    }
});

/* ---------- FETCH (ASSET-ONLY) ---------- */
self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (!isHttp(req)) return;

    // ❗ ไม่แตะ document/navigation เลย — ปล่อย browser จัดการเอง 100%
    if (req.destination === 'document' || req.mode === 'navigate') return;

    const url = new URL(req.url);
    const dest = req.destination;

    // ไม่ยุ่งกับ POST/PUT/DELETE
    if (req.method !== 'GET') return;

    // BYPASS เส้นเกม/โอน อื่น ๆ
    const BYPASS_PATHS = ['/play/', '/seamless/', '/game/', '/provider/', '/transfer/', '/bonus/', '/sanctum/', '/logout'];
    if (sameOrigin(req) && BYPASS_PATHS.some(p => url.pathname.startsWith(p))) return;

    // Same-origin STATIC: Cache-First
    if (sameOrigin(req) && (dest === 'script' || dest === 'style' || dest === 'image' || dest === 'font' || dest === 'audio')) {
        event.respondWith((async () => {
            const cached = await caches.match(req);
            if (cached) return cached;
            try {
                const res = await fetch(req, { cache: 'no-cache' });
                if (res && (res.ok || res.type === 'opaque')) {
                    putSafe(STATIC_CACHE, req, res.clone());
                }
                return res;
            } catch {
                const fallback = await caches.match(OFFLINE_URL);
                return fallback || Response.error();
            }
        })());
        return;
    }

    // Allowed CDN STATIC: Cache-First
    if (!sameOrigin(req) && isAllowedExternal(req) && (dest === 'script' || dest === 'style' || dest === 'font' || dest === 'image')) {
        event.respondWith((async () => {
            const cached = await caches.match(req);
            if (cached) return cached;
            try {
                let res;
                if (req.mode === 'no-cors') {
                    res = await fetch(req, { mode: 'no-cors', cache: 'no-cache' });
                    if (res) putSafe(STATIC_CACHE, req, res.clone());
                    return res;
                } else {
                    res = await fetch(req, { cache: 'no-cache' });
                    if (res && res.ok) putSafe(STATIC_CACHE, req, res.clone());
                    return res;
                }
            } catch {
                const fallback = await caches.match(OFFLINE_URL);
                return fallback || Response.error();
            }
        })());
        return;
    }

    // (ออปชัน) Same-origin API GET: ถ้าอยาก “ไม่แคช API เลย” ให้ลบบล็อกนี้ทิ้ง
    if (sameOrigin(req) && url.pathname.startsWith('/api/') && req.method === 'GET') {
        event.respondWith((async () => {
            try {
                const res = await fetch(req, { cache: 'no-cache' });
                // เลือกจะไม่ putSafe ก็ได้ เพื่อตัดปัญหา auth state
                return res;
            } catch {
                const cached = await caches.match(req);
                if (cached) return cached;
                return new Response(JSON.stringify({ offline: true, message: 'offline cache miss' }), {
                    headers: { 'Content-Type': 'application/json' },
                    status: 503
                });
            }
        })());
        return;
    }

    // อื่น ๆ → ปล่อยผ่าน
});

/* ---------- PUSH NOTIFICATIONS ---------- */
self.addEventListener('push', (event) => {
    let data = {};
    try {
        if (event.data) data = event.data.json();
    } catch {
        data = { title: 'แจ้งเตือน', body: event.data?.text() || '' };
    }

    const title = data.title || 'แจ้งเตือน';
    const options = {
        body: data.body || '',
        icon: data.icon || '/images/app/icon-192.png',
        badge: data.badge || '/images/app/badge-72.png',
        image: data.image, // big picture (Android/Chrome)
        data: {
            url: data.url || '/',
            extra: data.extra || null
        },
        actions: Array.isArray(data.actions) && data.actions.length ? data.actions : [
            { action: 'open', title: 'เปิด', icon: '/images/app/check.png' }
        ],
        requireInteraction: !!data.requireInteraction,
        vibrate: data.vibrate || [100, 50, 100],
        tag: data.tag || undefined,
        renotify: !!data.renotify
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

/* ---------- NOTIFICATION CLICK ---------- */
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const urlToOpen = event.notification.data?.url || '/';

    event.waitUntil((async () => {
        const target = new URL(urlToOpen, self.location.origin);
        const all = await clients.matchAll({ type: 'window', includeUncontrolled: true });

        // โฟกัสแท็บที่เปิด path เดียวกันถ้ามี
        for (const c of all) {
            try {
                const u = new URL(c.url);
                if (u.origin === target.origin && u.pathname === target.pathname) {
                    return c.focus();
                }
            } catch {}
        }
        // ไม่มีก็เปิดหน้าใหม่
        return clients.openWindow(target.toString());
    })());
});

/* ---------- PUSH SUBSCRIPTION CHANGED ---------- */
self.addEventListener('pushsubscriptionchange', async () => {
    const all = await clients.matchAll({ includeUncontrolled: true });
    for (const c of all) {
        c.postMessage({ type: 'PUSH_SUBSCRIPTION_EXPIRED' });
    }
});
