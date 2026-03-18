<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
	<meta charset="utf-8">
	<meta name="description" content="{{ $webconfig->description }}">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="keywords"
		  content="slot, casino, pgslot, joker, บาคาร่าออนไลน์, พนันออนไลน์, เว็บพนันออนไลน์, คาสิโนออนไลน์, บาคาร่า, บอลออนไลน์, สล็อต, ค่าน้ำดีที่สุด, เว็บพนัน, เกมสล็อต, นักพนัน"/>
	<meta property="og:title" content="{{ ucwords($webconfig->sitename) }} - {{ $webconfig->title }}"/>
	<meta property="og:description"
		  content="{{ $webconfig->description }}"/>
	<meta property="og:locale" content="{{ config('app.locale') }}"/>
	<meta property="og:site_name" content="{{ ucwords($webconfig->sitename) }}"/>
	<meta property="og:url" content="{{ url('') }}"/>
	<meta property="og:image" content="{{ url(core()->imgurl($webconfig->logo,'img')) }}"/>

	<link rel="icon" type="image/png" sizes="32x32" href="{!! core()->imgurl($webconfig->favicon,'img') !!}">
	<link rel="icon" type="image/x-icon" href="{!! core()->imgurl($webconfig->favicon,'img') !!}">
	<link rel="apple-touch-icon" sizes="60x60" href="{!! core()->imgurl($webconfig->favicon,'img') !!}">
	<meta name="apple-mobile-web-app-title" content="{{ ucwords($webconfig->sitename) }} - {{ $webconfig->title }}"/>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

	<title>{{ ucwords($webconfig->sitename) }} - {{ $webconfig->title }}</title>

	<link rel="canonical" href="{{ url('/') }}"/>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css"
		  integrity="sha512-GQGU0fMMi238uA+a/bdWJfpUGKUkBdgfFdgBm72SUQ6BeyWjoY/ton0tEjH+OSH9iP4Dfh+7HM0I9f5eR0L/4w=="
		  crossorigin="anonymous" referrerpolicy="no-referrer"/>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/6.7.5/swiper-bundle.min.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css"
		  href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
	<link rel="stylesheet" type="text/css"
		  href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.5.0/font/bootstrap-icons.min.css">
	<link href="/assets/kimberbet/css/trans.css" rel="stylesheet">
	<link href="/assets/kimberbet/css/stylemain.css?v={{ filemtime(public_path('assets/kimberbet/css/stylemain.css')) }}"
		  rel="stylesheet">
	<link href="/assets/kimberbet/css/slide.css?v={{ filemtime(public_path('assets/kimberbet/css/slide.css')) }}"
		  rel="stylesheet">
	<link href="/assets/kimberbet/css/addon.css?v={{ filemtime(public_path('assets/kimberbet/css/addon.css')) }}"
		  rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css"
		  integrity="sha512-jU/7UFiaW5UBGODEopEqnbIAHOI8fO6T99m7Tsmqs2gkdujByJfkCbbfPSN4Wlqlb9TGnsuC0YgUgWkRBK7B9A=="
		  crossorigin="anonymous" referrerpolicy="no-referrer"/>
	<style>

	</style>
	@stack('style')
	@stack('styles')

	{{-- Inline initial i18n for instant usage --}}
	<script>
		window.i18n = @json(__('app'));
		document.documentElement.lang = "{{ app()->getLocale() }}";
	</script>

	<style>[v-cloak]{visibility:hidden;}</style>

	@laravelPWA
</head>
<body>

@include('wallet::layouts.component')

<div id="app" class="main" style="position: relative;" v-cloak>

	@include('wallet::layouts.header_member')

	{{--	@include('wallet::layouts.mobile')--}}

	<div id="member" class="member">
		@yield('content')
	</div>

	@include('wallet::layouts.footer')

</div>

@include('wallet::layouts.contact')


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
		integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p"
		crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/6.7.5/swiper-bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"
		integrity="sha512-U2WE1ktpMTuRBPoCFDzomoIorbOyUv0sP8B+INA3EzNAhehbzED1rOJg6bCqPf/Tuposxb5ja/MAUnC8THSbLQ=="
		crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="{{ asset('lang-').app()->getLocale() }}.js?v={{ date('YmdHi') }}"></script>
<script>
	window.flashMessages = [];
	window.serverErrors = [];

	@foreach (['success', 'warning', 'error', 'info'] as $key)
	@if ($value = session($key))
	window.flashMessages.push({'type': '{{ $key }}', 'message': "{{ $value }}"});
	@endif
			@endforeach

			@if (isset($errors))
			@if (count($errors))
			window.serverErrors = @json($errors->getMessages());
	@endif
	@endif

</script>
<script>
	window.vueData = @json([
        'menus' => $menus,
        'webconfig' => $webconfig,
        'lanuages' => $languages
    ]);
	window.vueData.i18n = window.i18n || {};

	Dropzone.autoDiscover = false;
</script>
<script src="{{ mix('assets/kimberbet/js/manifest.js') }}"></script>
<script src="{{ mix('assets/kimberbet/js/vendor.js') }}"></script>
<script src="{{ mix('assets/kimberbet/js/app.js') }}" id="mainscript" baseUrl="{{ url()->to('/') }}"></script>
<script>
	document.addEventListener('DOMContentLoaded', function () {
		if (!window.__APP_VM__) {
			const el = document.querySelector('#app');
			if (el && el.__vue__) {
				window.__APP_VM__ = el.__vue__;
			}
		}
	});
</script>

{{-- === i18n helpers + SPA-like language switch (ADD) === --}}
<script>
	// ให้แน่ใจว่า lang-xx.js โหลดมาก่อนบล็อกนี้ (จะมี window.i18n เริ่มต้น)
	window.i18n = window.i18n || {};

	// ทำ reactive store ให้ Vue รู้ว่ามีการเปลี่ยนแปลง
	window.I18nStore = (window.Vue && window.Vue.observable)
			? window.Vue.observable({ dict: window.i18n, version: 0 })
			: { dict: window.i18n, version: 0 }; // fallback ไม่ reactive แต่เราจะ forceUpdate ให้ด้วย

	// global mixin: trans แบบ reactive + รองรับ i18n.app และ i18n
	(function () {
		if (!window.Vue || window.__TRANS_MIXIN__) return;
		window.__TRANS_MIXIN__ = true;

		window.Vue.mixin({
			methods: {
				trans: function (key, replace) {
					// ดึงเวอร์ชันมา "ใช้" ในฟังก์ชัน เพื่อให้ Vue จับ dependency (ทำให้ reactive)
					/* eslint-disable no-unused-vars */
					var _v = window.I18nStore.version;
					/* eslint-enable no-unused-vars */

					replace = replace || {};

					try {
						// รองรับ key ที่มี prefix 'app.'
						if (typeof key === 'string' && key.indexOf('app.') === 0) {
							key = key.slice(4);
						}

						var dictRoot = (window.I18nStore.dict && (window.I18nStore.dict.app || window.I18nStore.dict)) || {};
						var parts = String(key).split('.');
						var t = dictRoot;

						for (var i = 0; i < parts.length; i++) {
							var p = parts[i];
							if (t && Object.prototype.hasOwnProperty.call(t, p)) {
								t = t[p];
							} else {
								t = null; break;
							}
						}
						var text = (typeof t === 'string') ? t : key;

						for (var ph in replace) {
							if (Object.prototype.hasOwnProperty.call(replace, ph)) {
								var search = ':' + ph;
								var value  = replace[ph];

								if (typeof value !== 'string') {
									value = String(value);
								}

								// แทนทุกตำแหน่ง
								text = text.split(search).join(value);
							}
						}
						return text;
					} catch (e) {
						return key;
					}
				}
			}
		});
	})();
</script>
<script>
	(function () {
		// ===== Navbar helper =====
		const NAV_SELECTOR = '#navbarSupportedContent';        // ต้องตรงกับ data-bs-target ของปุ่ม
		const TOGGLER_SELECTOR = '.navbar-toggler';

		function getNav() {
			const nav = document.querySelector(NAV_SELECTOR);
			if (!nav) return {};
			const inst = bootstrap.Collapse.getOrCreateInstance(nav, { toggle: false });
			return { nav, inst };
		}

		// ปิดอย่างสุภาพด้วย API และ sync ปุ่มเมื่อปิดเสร็จ
		function closeNavbar() {
			const { nav, inst } = getNav();
			if (!nav || !inst) return;

			// ถ้ากำลัง transition อยู่ ให้รอจบก่อน
			if (nav.classList.contains('collapsing')) {
				nav.addEventListener('hidden.bs.collapse', syncTogglers, { once: true });
				try { inst.hide(); } catch(_) {}
				return;
			}

			// ถ้าเปิดอยู่ค่อยปิด
			if (nav.classList.contains('show')) {
				nav.addEventListener('hidden.bs.collapse', syncTogglers, { once: true });
				inst.hide();
			} else {
				// ไม่ได้เปิด แต่เพื่อความแน่ใจ sync ปุ่มให้ถูก
				syncTogglers();
			}
		}

		function syncTogglers() {
			document.querySelectorAll(TOGGLER_SELECTOR + `[data-bs-target="${NAV_SELECTOR}"]`)
					.forEach(btn => {
						btn.classList.add('collapsed');
						btn.setAttribute('aria-expanded', 'false');
					});
		}

		// 1) คลิกลิงก์/ปุ่มในเมนู → ปิด (ยกเว้นปุ่ม dropdown)
		document.addEventListener('click', (e) => {
			const el = e.target.closest('.navbar-collapse a, .navbar-collapse button');
			if (!el) return;
			if (el.matches('[data-bs-toggle="dropdown"], .dropdown-toggle')) return;
			closeNavbar();
		}, false); // อย่าใช้ capture=true

		// ===== AppLocale (merge ไม่ทับของเดิม) =====
		window.I18nStore = window.I18nStore || { dict: (window.i18n||{}), version: 0 };

		window.AppLocale = (function(prev){
			const api = prev || {};

			if (!api._loadLangJs) {
				api._loadLangJs = function(locale){
					return new Promise((resolve,reject)=>{
						const s = document.createElement('script');
						s.src = `/lang-${locale}.js?v=${Date.now()}`;
						s.async = true;
						s.onload = () => resolve();
						s.onerror = (e) => reject(e);
						document.head.appendChild(s);
					});
				};
			}

			if (!api._applyDict) {
				api._applyDict = function(dict, locale){
					window.I18nStore.dict = dict?.app || dict || {};
					window.I18nStore.version++;
					document.documentElement.lang = locale;
					api.current = locale;

					if (window.__APP_VM__) {
						(function walk(vm){ vm.$forceUpdate(); vm.$children?.forEach(walk); })(window.__APP_VM__);
					}
					window.dispatchEvent(new CustomEvent('app:locale:changed', { detail:{ locale } }));
				};
			}

			// override switchTo ให้รวม "ปิด navbar" ด้วย
			api.switchTo = async function(locale){
				try{
					const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
					await axios.post("{{ route('locale.switch', [], false) }}",
							{ locale }, { headers: { 'X-CSRF-TOKEN': token } });

					try {
						const res = await axios.get(`/lang-${locale}.json?v=${Date.now()}`);
						api._applyDict(res.data, locale);
					} catch(e) {
						await api._loadLangJs(locale);
						api._applyDict(window.i18n, locale);
					}
				} catch(e){
					console.error('switch locale failed', e);
				} finally {
					// ปิดเมนูหลังสลับสำเร็จ/ไม่สำเร็จ ก็ปิดให้
					setTimeout(closeNavbar, 150);
				}
			};

			return api;
		})(window.AppLocale || { current: document.documentElement.lang || 'th' });

		// // ===== Auto-close behaviors =====
		// // คลิกในเมนู (ลิงก์/ปุ่ม) ให้ปิด ยกเว้น dropdown toggle
		// document.addEventListener('click', e => {
		// 	const el = e.target.closest('.navbar-collapse a, .navbar-collapse button');
		// 	if (!el) return;
		// 	if (el.matches('[data-bs-toggle="dropdown"], .dropdown-toggle')) return;
		// 	forceCloseNavbar();
		// }, true);

		// รับ broadcast locale เปลี่ยน → ปิดให้
		window.addEventListener('app:locale:changed', () => setTimeout(closeNavbar, 120));

		// // ถ้าเปิดอยู่แล้วกด hamburger อีก → ปิดทันที (ไม่ต้องกดสองครั้ง)
		// document.addEventListener('click', e => {
		// 	const toggler = e.target.closest(TOGGLER_SELECTOR);
		// 	const nav = document.querySelector(NAV_SELECTOR);
		// 	if (!toggler || !nav) return;
		// 	if (nav.classList.contains('show')) {
		// 		forceCloseNavbar();
		// 		e.stopImmediatePropagation();
		// 	}
		// }, true);

	})();
</script>



{{-- === /i18n helpers (ADD) === --}}

@stack('components')

<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js"></script>

<script src="{{ asset('assets/kimberbet/js/js.js?v='.filemtime(public_path('assets/kimberbet/js/js.js'))) }}"></script>

@if (isset($notice_new[Route::currentRouteName()]['messages']) && !empty($notice_new[Route::currentRouteName()]['messages']))
	<div class="modal fade announcement-modal" id="announcementModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="swiper mySwiper">
						<div class="swiper-wrapper">
							@foreach ($notice_new[Route::currentRouteName()]['messages'] as $item)
								<div class="swiper-slide">
									<div class="p-3 fs-5 text-center">{!! $item !!}</div>
								</div>
							@endforeach
						</div>
						<div class="swiper-pagination"></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	{{-- สั่งเปิด modal และ init swiper เมื่อโหลดหน้า --}}
	<script>
		let swiperInstance = null;

		window.addEventListener('DOMContentLoaded', () => {
			const modalEl = document.getElementById('announcementModal');

			modalEl.addEventListener('shown.bs.modal', function () {
				// ถ้ามีอยู่แล้วให้ destroy ก่อนเพื่อป้องกันซ้ำ
				if (swiperInstance) {
					swiperInstance.destroy(true, true);
				}

				swiperInstance = new Swiper(".mySwiper", {
					loop: true,
					autoplay: {
						delay: 5000,
						disableOnInteraction: false
					},
					pagination: {
						el: ".swiper-pagination",
						clickable: true
					}
				});
			});

			$('#announcementModal').modal('show');

			// swiper.on('slideChange', function () {
			//     $('#announcementModal').modal('handleUpdate');
			// });
		});
	</script>
@endif

<script>



	document.addEventListener("DOMContentLoaded", function () {

		const APP_NAME = '{{ config('app.name') }}';
		const USER_CODE = '{{ auth()->guard('customer')->user()->code }}';
		const private_channel = `${APP_NAME}_members.${USER_CODE}`;

		window.Echo.private(private_channel)
				.notification((notification) => {
					window.Toast.fire({
						icon: 'success',
						title: notification.message
					});
					window.reLoadCredit();
				});

		window.Echo.private(`${APP_NAME}_members`)
				.listen('.WalletDisplayChanged', (e) => {
					// console.log('ทุกคนที่ล็อกอินได้รับ:', e);
					// window.Toast.fire({ icon: 'info', title: e.message });
					window.reLoadCredit?.();
				});
	});

</script>

@stack('script')
@stack('scripts')
</body>
</html>
