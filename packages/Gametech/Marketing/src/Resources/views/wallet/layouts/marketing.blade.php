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
	{{--    <link rel="canonical" href="{{ url('/') }}"/>--}}

	<title>{{ ucwords($webconfig->sitename) }} - {{ $webconfig->title }}</title>


	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.0.0-beta3/css/bootstrap.min.css">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/6.7.5/swiper-bundle.min.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css"
		  href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
	<link rel="stylesheet" type="text/css"
		  href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.5.0/font/bootstrap-icons.min.css">


	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


	<link href="/assets/kimberbet/css/trans.css?v={{ filemtime(public_path('assets/kimberbet/css/trans.css'))  }}" rel="stylesheet">
	<link href="/assets/kimberbet/css/stylemain.css?v={{ filemtime(public_path('assets/kimberbet/css/stylemain.css'))  }}" rel="stylesheet">
	@stack('styles')
	{{--    @laravelPWA--}}
</head>
<body>

@stack('template')
<script type="text/x-template" id="mobile-app-template">
	<div class="add-home-screen-container add-home bg-dark-2 p-2 p-md-3 w-100 align-items-center d-flex" v-if="shouldShow">
		<div class="container d-flex justify-content-between align-items-center">
			<div class="col left position-relative">
				<button type="button" class="close-add-to-home" @click="dismissPrompt"><span>&times;</span></button>
				<div class="fs-5 text-white">{{ __('app.pwa.addhome') }}</div>
				<div class="fw-light text-white">{{ __('app.pwa.text') }}</div>
			</div>
			<button class="right btn btn-white d-flex align-items-center rounded-pill shadow btn-add-to-home" @click="promptInstall">
				<i class="bi bi-cloud-arrow-down-fill me-2"></i>
				<span class="d-flex flex-column lh-0 align-items-start">
            <span>APP</span>
            <span class="text-muted fst-italic" style="font-size: .575rem;">INSTALL</span>
          </span>
			</button>
		</div>
	</div>
	<div class="how-to-install-a2h-ios p-3 pt-3 pb-3" v-if="isIOS">
		<div class="header-content position-relative">
			<h4 class="text-white text-center">How to Install?</h4>
			<button class="reset-btn close-how-to-install" @click="dismissPrompt">
				<i class="bi bi-x"></i>
			</button>
		</div>
		<div class="body-content">
			<img class="d-block logo-app mx-auto rounded my-2" src="" alt="">
			<p class="fw-normal lh-1 mt-4 px-3">This is a new format that can be installed right from the browser.</p>
		</div>
		<div class="footer-content-ios-chrome justify-content-center align-items-center mt-auto p-3">
			<img class="ic_footer" src="/assets/kimberbet/img/icon/ic_safari_rounded.svg" alt="">
			<p class="px-3 lh-1 my-0">Please open website in <span class="text-danger">safari</span> to install app.</p>
		</div>
		<div class="footer-content-ios-safari justify-content-center align-items-center mt-auto p-3">
			<img class="ic_footer" src="/assets/kimberbet/img/icon/btn_a2h_safari.svg" alt="">
			<p class="px-3 lh-1 my-0">To install the App simply tap and then Add to home screen</p>
			<img class="ic_footer a2h" src="/assets/kimberbet/img/icon/btn_plus_safari.svg" alt="">
		</div>
	</div>
</script>

<div id="app" class="main" style="position: relative;">

	@include('wallet::layouts.header')
	{{--    <mobile-app-prompt></mobile-app-prompt>--}}

	@yield('content')

</div>
@include('wallet::layouts.contact')
@stack('components')
<script type="module">
	Vue.component('mobile-app-prompt', {
		template: '#mobile-app-template',
		data() {
			return {
				deferredPrompt: null,
				isIOS: false,
				shouldShow: false,
			};
		},
		mounted() {
			this.isIOS = /iphone|ipad|ipod/.test(navigator.userAgent.toLowerCase());

			// Optional: clear dismissed if login or new session
			const userSessionKey = localStorage.getItem('session_user') || '';
			const currentUser = window?.app?.user?.id || '';
			if (userSessionKey !== currentUser) {
				localStorage.removeItem('a2h_dismissed_until');
				localStorage.setItem('session_user', currentUser);
			}

			const dismissed = localStorage.getItem('a2h_dismissed');
			const installed = localStorage.getItem('a2h_installed');
			const dismissedUntil = localStorage.getItem('a2h_dismissed_until');
			const now = Date.now();

			if (!installed && (!dismissedUntil || now >= parseInt(dismissedUntil))) {
				window.addEventListener('beforeinstallprompt', (e) => {
					e.preventDefault();
					this.deferredPrompt = e;
					this.shouldShow = true;
					this.$nextTick(() => {
						const el = document.querySelector('.add-home-screen-container');
						if (el) {
							el.style.opacity = '1';
							el.style.visibility = 'visible';
						}
					});
				});
			}

			window.addEventListener('appinstalled', () => {
				localStorage.setItem('a2h_installed', 'true');
				this.shouldShow = false;
			});
		},
		methods: {
			dismissPrompt() {
				this.shouldShow = false;
				const days = 1;
				const expiration = Date.now() + days * 24 * 60 * 60 * 1000;
				localStorage.setItem('a2h_dismissed_until', expiration.toString());
			},
			promptInstall() {
				if (this.deferredPrompt) {
					this.deferredPrompt.prompt();
					this.deferredPrompt.userChoice.then(choice => {
						if (choice.outcome === 'accepted') {
							localStorage.setItem('a2h_installed', 'true');
							this.shouldShow = false;
						}
						this.deferredPrompt = null;
					});
				}
			}
		}
	});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
		integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p"
		crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/6.7.5/swiper-bundle.min.js"></script>
<script src="{{ asset('lang-').app()->getLocale() }}.js?v={{ date('Ymd') }}"></script>
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
        'menus' => [],
        'lanuages' => $languages,
    ]);


</script>
<script src="{{ mix('assets/kimberbet/js/manifest.js') }}"></script>
<script src="{{ mix('assets/kimberbet/js/vendor.js') }}"></script>
<script src="{{ mix('assets/kimberbet/js/app.js') }}" id="mainscript" baseUrl="{{ url()->to('/') }}"></script>

{{--<script>--}}
{{--    document.addEventListener("DOMContentLoaded", function () {--}}
{{--        @if(isset($notice_new[Route::currentRouteName()]['route']) === true)--}}
{{--        window.Swal.fire({--}}
{{--            html: '{!! $notice_new[Route::currentRouteName()]['msg'] !!}',--}}
{{--            focusConfirm: false,--}}
{{--            showCloseButton: true,--}}
{{--            showConfirmButton: false--}}
{{--        });--}}

{{--        @endif--}}

{{--    });--}}

{{--</script>--}}

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



@stack('script')
@stack('scripts')
</body>
</html>