<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com/" crossorigin=""/>
    <link rel="dns-prefetch" href="//fonts.googleapis.com/"/>


    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Kanit&display=swap"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit&display=swap">
    </noscript>

    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" href="{!! core()->imgurl($config->favicon,'img') !!}">
    <link rel="icon" type="image/x-icon" href="{!! core()->imgurl($config->favicon,'img') !!}">
    <link rel="apple-touch-icon" sizes="60x60" href="{!! core()->imgurl($config->favicon,'img') !!}">
    <meta name="apple-mobile-web-app-title" content="{{ ucwords($config->sitename) }} - {{ $config->title }}"/>
    <title>{{ ucwords($config->sitename) }} - {{ $config->title }}</title>
    <meta name="description" content="{{ $config->description }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="keywords"
          content="slot, casino, pgslot, joker, บาคาร่าออนไลน์, พนันออนไลน์, เว็บพนันออนไลน์, คาสิโนออนไลน์, บาคาร่า, บอลออนไลน์, สล็อต, ค่าน้ำดีที่สุด, เว็บพนัน, เกมสล็อต, นักพนัน"/>

    <meta property="og:title" content="{{ ucwords($config->sitename) }} - {{ $config->title }}"/>
    <meta property="og:description"
          content="{{ $config->description }}"/>
    <meta property="og:locale" content="{{ config('app.locale') }}"/>
    <meta property="og:site_name" content="{{ ucwords($config->sitename) }}"/>
    <meta property="og:url" content="{{ url('') }}"/>
    <meta property="og:image" content="{{ url(core()->imgurl($config->logo,'img')) }}"/>

    <link rel="canonical" href="{{ url('/') }}"/>

    <meta name="twitter:site" content="@twitter"/>
    <meta name="twitter:card" content="summary"/>
    <meta name="twitter:title" content="{{ ucwords($config->sitename) }} - {{ $config->title }}"/>
    <meta name="twitter:description"
          content="{{ $config->description }}"/>
    <meta name="twitter:image" content="{{ url(core()->imgurl($config->logo,'img')) }}"/>


    <link preload href="{!! url(core()->imgurl($config->favicon,'img')) !!}" as="style"
          onload="this.onload=null;this.rel='icon'" crossorigin=""/>
    <noscript>
        <link rel="icon" href="{!! url(core()->imgurl($config->favicon,'img')) !!}"/>
    </noscript>
    <meta name="msapplication-TileColor" content="#ffffff"/>
    <meta name="msapplication-TileImage" content="/assets/wm356/images/ms-icon-144x144.png"/>
    <meta name="theme-color" content="#ffffff"/>

    <meta name="format-detection" content="telephone=no"/>

    <link rel="preload" as="style"
          href="/assets/wm356/css/style.css?v={{ filemtime(public_path('assets/wm356/css/style.css')) }}"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet"
              href="/assets/wm356/css/style.css?v={{ filemtime(public_path('assets/wm356/css/style.css')) }}">
    </noscript>

    <link rel="preload" as="style"
          href="/assets/wm356/css/addon.css?v={{ filemtime(public_path('assets/wm356/css/addon.css')) }}"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet"
              href="/assets/wm356/css/addon.css?v={{ filemtime(public_path('assets/wm356/css/addon.css')) }}">
    </noscript>

    <link rel="preload" as="style"
          href="/assets/wm356/css/mobile.css?v={{ filemtime(public_path('assets/wm356/css/mobile.css')) }}"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet"
              href="/assets/wm356/css/mobile.css?v={{ filemtime(public_path('assets/wm356/css/mobile.css')) }}">
    </noscript>

    <link rel="preload" as="style"
          href="/vendor/izitoast/iziToast.min.css?v={{ filemtime(public_path('vendor/izitoast/iziToast.min.css')) }}"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet"
              href="/vendor/izitoast/iziToast.min.css?v={{ filemtime(public_path('vendor/izitoast/iziToast.min.css')) }}">
    </noscript>

    <link
            rel="preload"
            href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/7.5.0/css/flag-icons.min.css"
            as="style"
            type="text/css"
            integrity="sha512-+WVTaUIzUw5LFzqIqXOT3JVAc5SrMuvHm230I9QAZa6s+QRk8NDPswbHo2miIZj3yiFyV9lAgzO1wVrjdoO4tw=="
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
    />

    <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/7.5.0/css/flag-icons.min.css"
            integrity="sha512-+WVTaUIzUw5LFzqIqXOT3JVAc5SrMuvHm230I9QAZa6s+QRk8NDPswbHo2miIZj3yiFyV9lAgzO1wVrjdoO4tw=="
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
    />

    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin="anonymous"/>
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com"/>

    <link
            rel="preload"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
            as="style"
            type="text/css"
            integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
    />

    <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
            integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
    />


    <script type="text/javascript">
        window["gif64"] = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";
        window["Bonn"] = {
            boots: [],
            inits: [],
        };
    </script>

    <style>
        .x-header {
            background: {{ ($config->wallet_navbar_color? $config->wallet_navbar_color :'#1d1d1d') }}             !important;
        }

        .x-footer.-ezl .-copy-right-container {
            background-color: {{ ($config->wallet_footer_color?$config->wallet_footer_color:'#255b48') }}             !important;
        }

    </style>


    @if($config->header_code)
        {!! $config->header_code !!}
    @endif


    @stack('styles')
    @stack('script')


    <link href="{{ asset('lang-').app()->getLocale() }}.js?v={{ date('Ymdh') }}" as="script">
    <link rel="preload"
          href="{{ asset('assets/wm356/js/minified_safe_optimized_no_jquery_bundle.js?v='. filemtime(public_path('assets/wm356/js/minified_safe_optimized_no_jquery_bundle.js')) ) }}"
          as="script">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
            integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script src="{{ asset('lang-').app()->getLocale() }}.js?v={{ date('Ymdhi') }}" defer></script>
    <script src="{{ asset('assets/wm356/js/minified_safe_optimized_no_jquery_bundle.js?v='.filemtime(public_path('assets/wm356/js/minified_safe_optimized_no_jquery_bundle.js')) ) }}"
            defer></script>
    {{--    <link rel="stylesheet" href="vendor/izitoast/iziToast.min.css">--}}
    <script src="vendor/izitoast/iziToast.min.js" type="text/javascript"></script>
    @laravelPWA

</head>

<body>

@include('wallet::layouts.header')
<div
        class="a2h-topbar-mount"
        data-header-selector=".x-header"
        data-dismiss-days="1"
        style="display:block"
></div>
@yield('content')

@include('wallet::layouts.footer')


<script></script>

<script>
    var IS_ANDROID = false;
    var IS_MOBILE = false;
</script>

<script type="text/javascript" defer>
    window.flashMessages = [];
    window.serverErrors = [];

    @foreach (['success', 'warning', 'error', 'info'] as $key)
    @if ($value = session($key))
    window.flashMessages.push({'type': '{{ $key }}', 'message': "{{ $value }}"});

    iziToast.error({
        position: 'center',
        title: 'สถานะ',
        message: "{{ $value }}",
    });
    @endif
            @endforeach

            @if (isset($errors))
            @if (count($errors))
        window.serverErrors = @json($errors->getMessages());


    @endif
    @endif

</script>

@stack('scripts')

<script defer>

    document.addEventListener('DOMContentLoaded', () => {
        // สมมติว่า 'e' คือ context DOM หรือ document ทั้งหมด (ถ้าไม่มีก็ใช้ document)
        setTimeout(() => {

            $("[data-animatable]").each(function () {
                var $el = $(this);

                // ตั้ง delay เล็กน้อยก่อนสร้าง waypoint (เพื่อให้ DOM พร้อม)
                setTimeout(function () {
                    new Waypoint({
                        element: $el[0], // element DOM จริง
                        handler: function () {
                            // เมื่อ scroll เข้ามาใน viewport
                            setTimeout(function () {
                                // เรียก animateCss ด้วยชื่อ animation จาก data-animatable หรือ default เป็น fadeInUp
                                $el.animateCss($el.data("animatable") || "fadeInUp");
                            }, $el.data("delay") || 50);

                            // ป้องกัน waypoint รันซ้ำหลายครั้ง (destroy หลังทำงาน)
                            this.destroy();
                        },
                        offset: $el.data("offset") || "100%" // ตำแหน่ง trigger
                    });
                }, 100);
            });

        }, 1000);
    });

</script>
<script>
    // เก็บ beforeinstallprompt ไว้ (กันยิงก่อนสคริปต์หลักโหลด)
    (function () {
        window.__A2H_DEFERRED_PROMPT__ = null;
        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            window.__A2H_DEFERRED_PROMPT__ = e;
            // เผื่อสคริปต์หลักอยากรู้
            window.dispatchEvent(new Event('a2h:ready'));
        }, {once: true});
    })();
</script>

<script>
    (function ($) {
        if (window.__A2H_TOPBAR_BOUND__) return;
        window.__A2H_TOPBAR_BOUND__ = true;

        const $mount = $('.a2h-topbar-mount').first();
        if (!$mount.length) return;

        const dismissDays = parseInt($mount.attr('data-dismiss-days') || '1', 10);
        const headerSelector = $mount.attr('data-header-selector') || '.x-header';

        const KEY = {
            session: 'session_user',
            installed: 'a2h_installed',
            dismissedUntil: 'a2h_dismissed_until',
        };

        // ===== Utils =====
        const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
        const isStandalone =
            (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) ||
            (typeof navigator.standalone !== 'undefined' && navigator.standalone === true);

        const nowMs = () => Date.now();
        const blockedByDismiss = () => {
            const until = localStorage.getItem(KEY.dismissedUntil);
            if (!until) return false;
            const n = parseInt(until, 10);
            return Number.isFinite(n) && nowMs() < n;
        };

        // reset dismiss ถ้าผู้ใช้เปลี่ยน
        (function resetIfUserChanged() {
            const currentUser = (window && window.app && window.app.user && window.app.user.id) ? String(window.app.user.id) : '';
            const saved = localStorage.getItem(KEY.session) || '';
            if (saved !== currentUser) {
                localStorage.removeItem(KEY.dismissedUntil);
                localStorage.setItem(KEY.session, currentUser);
            }
        })();

        // ถ้าติดตั้งแล้ว ไม่สร้างบาร์
        if (isStandalone) {
            localStorage.setItem(KEY.installed, 'true');
            $('#a2h-topbar').remove();
            $mount.remove();
            return;
        }

        // ===== สร้าง DOM บาร์เดียว =====
        const $bar = $(`
    <div class="a2h-topbar" id="a2h-topbar">
      <div class="a2h-topbar__inner">
        <button class="a2h-close-mobile" aria-label="ปิด">✕</button>
        <span class="a2h-topbar__icon" aria-hidden="true"></span>
        <span class="a2h-topbar__text">โหลดเลย! แอปเดียวจบ ครบทุกบริการ</span>
        <span class="a2h-ios-hint" style="display:none">iPhone/iPad: แตะ <strong>Share</strong> → <strong>Add to Home Screen</strong></span>
        <div class="a2h-topbar__actions">
          <button class="a2h-btn -install">
            <span class="a2h-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 3a1 1 0 0 1 1 1v9.586l2.293-2.293a1 1 0 0 1 1.414 1.414l-4.005 4.005a1.5 1.5 0 0 1-2.121 0L6.576 12.707a1 1 0 1 1 1.414-1.414L10.293 13.6V4a1 1 0 0 1 1-1z"></path><path d="M5 18a1 1 0 1 0 0 2h14a1 1 0 1 0 0-2H5z"></path></svg>
            </span>
            ติดตั้ง&nbsp;APP
          </button>
          <button class="a2h-btn -later">ภายหลัง</button>
        </div>
      </div>
    </div>
  `);
        $mount.after($bar);

        // ปรับ top ตาม header
        function setHeaderH() {
            const $h = $(headerSelector).first();
            const h = $h.length ? ($h.outerHeight() || 60) : 60;
            document.documentElement.style.setProperty('--a2h-header-h', h + 'px');
        }

        setHeaderH();
        $(window).on('resize', setHeaderH);

        function showBar() {
            if (localStorage.getItem(KEY.installed) === 'true' || blockedByDismiss()) return;
            $bar.addClass('-show');
        }

        function hideBar() {
            $bar.removeClass('-show');
        }

        function dismiss(days) {
            const until = nowMs() + (days * 24 * 60 * 60 * 1000);
            localStorage.setItem(KEY.dismissedUntil, String(until));
            hideBar();
        }

        // ===== ตรรกะการโชว์ =====
        // iOS: ไม่มี native prompt → โชว์ hint ได้เลยถ้าไม่โดน cooldown
        if (isIOS && !blockedByDismiss() && localStorage.getItem(KEY.installed) !== 'true') {
            $('.a2h-ios-hint').show();
            showBar();
        }

        // Chromium/Android: รอ beforeinstallprompt หรือสัญญาณ a2h:ready
        window.addEventListener('a2h:ready', () => {
            if (localStorage.getItem(KEY.installed) === 'true' || blockedByDismiss()) return;
            if (!isIOS) {
                $('.a2h-ios-hint').hide();
                showBar();
            }
        });

        // ถ้าก่อนหน้านี้จับ event ไว้แล้ว (เช่นเกิดขึ้นก่อนสคริปต์โหลด)
        if (window.__A2H_DEFERRED_PROMPT__ && !isIOS && !blockedByDismiss() && localStorage.getItem(KEY.installed) !== 'true') {
            $('.a2h-ios-hint').hide();
            showBar();
        }

        // ===== ปุ่มต่าง ๆ =====
        // ปิด/ภายหลัง
        $(document).on('click', '.a2h-btn.-later, .a2h-close-mobile', function (e) {
            e.preventDefault();
            const days = parseInt($mount.attr('data-dismiss-days') || dismissDays, 10);
            dismiss(days);
        });

        // ติดตั้ง
        $(document).on('click', '.a2h-btn.-install', async function (e) {
            e.preventDefault();

            if (isIOS) {
                // iOS: กระพริบ hint
                const $hint = $('.a2h-ios-hint');
                $hint.show().stop(true, true).fadeOut(80).fadeIn(180);
                return;
            }

            const evt = window.__A2H_DEFERRED_PROMPT__;
            if (!evt) {
                // ยังไม่เข้าเกณฑ์ หรือ event ถูกใช้ไปแล้ว
                hideBar();
                return;
            }

            try {
                evt.prompt();
                const choice = await evt.userChoice;
                if (choice && choice.outcome === 'accepted') {
                    localStorage.setItem(KEY.installed, 'true');
                    hideBar();
                } else {
                    hideBar(); // ปฏิเสธรอบนี้
                }
            } catch (err) {
                console.warn('[A2H] prompt() failed:', err);
                hideBar();
            } finally {
                window.__A2H_DEFERRED_PROMPT__ = null; // ใช้ได้ครั้งเดียว
            }
        });

        // ติดตั้งสำเร็จ (เดสก์ท็อป/แอนดรอยด์บางเวอร์ชันจะยิง)
        window.addEventListener('appinstalled', function () {
            localStorage.setItem(KEY.installed, 'true');
            hideBar();
            $('#a2h-topbar').remove();
            $mount.remove();
        });
       
    })(jQuery);
</script>

</body>
</html>