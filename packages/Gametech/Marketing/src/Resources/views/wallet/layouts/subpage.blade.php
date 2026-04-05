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
	<link rel="icon" type="image/png" sizes="32x32" href="{!! core()->imgurl($webconfig->favicon,'img') !!}">
	<link rel="icon" type="image/x-icon" href="{!! core()->imgurl($webconfig->favicon,'img') !!}">
	<link rel="apple-touch-icon" sizes="60x60" href="{!! core()->imgurl($webconfig->favicon,'img') !!}">
	<meta name="apple-mobile-web-app-title" content="{{ ucwords($webconfig->sitename) }} - {{ $webconfig->title }}"/>
	<title>{{ ucwords($webconfig->sitename) }} - {{ $webconfig->title }}</title>
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
	
	<link rel="canonical" href="{{ url('/') }}"/>
	
	<meta name="twitter:site" content="@twitter"/>
	<meta name="twitter:card" content="summary"/>
	<meta name="twitter:title" content="{{ ucwords($webconfig->sitename) }} - {{ $webconfig->title }}"/>
	<meta name="twitter:description"
	      content="{{ $webconfig->description }}"/>
	<meta name="twitter:image" content="{{ url(core()->imgurl($webconfig->logo,'img')) }}"/>
	
	
	<link preload href="{!! url(core()->imgurl($webconfig->favicon,'img')) !!}" as="style"
	      onload="this.onload=null;this.rel='icon'" crossorigin=""/>
	<noscript>
		<link rel="icon" href="{!! url(core()->imgurl($webconfig->favicon,'img')) !!}"/>
	</noscript>
	<meta name="msapplication-TileColor" content="#ffffff"/>
	<meta name="msapplication-TileImage" content="/assets/wm356/images/ms-icon-144x144.png"/>
	<meta name="theme-color" content="#ffffff"/>
	
	<meta name="format-detection" content="telephone=no"/>
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

		* { box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
		body { margin: 0; background: #000; color: #fff; }
		.container {
			width: calc(100% - 30px); /* เว้นซ้ายขวาอย่างละ 15px */
			max-width: 400px;
			margin: 40px auto;
			padding: 30px 20px;
			background: #1a1a1a;
			border-radius: 25px;
			border: 1px solid #444;
			box-shadow: 0 0 10px #000;
			text-align: center;
		}

		.logo { margin-top: 10px; margin-bottom: 20px; }
		.logo img {
			max-width: 260px;
			width: 100%;
			height: auto;
			display: block;
			margin: auto;
		}
		h3 { color: gold; margin-bottom: 20px; font-size: 18px; }
		.input-group { position: relative; margin-bottom: 15px; }
		.input-group input {
			width: 100%;
			padding: 12px 45px;
			border-radius: 30px;
			border: 1px solid gold;
			background: transparent;
			color: #fff;
			font-size: 16px;
		}
		.input-group i { position: absolute; left: 15px; top: 12px; color: gold; }
		.input-group .eye-icon {
			position: absolute; right: 15px; top: 12px; color: gold; cursor: pointer;
		}
		.forgot { text-align: right; font-size: 13px; margin-bottom: 15px; }
		.forgot a { color: #ccc; text-decoration: none; }
		.forgot a:hover { text-decoration: underline; }
		.login-btn {
			width: 100%;
			padding: 12px;
			background: linear-gradient(to bottom, #ffe680, #d4af37);
			border: none;
			border-radius: 30px;
			font-weight: bold;
			font-size: 16px;
			color: #000;
			cursor: pointer;
		}
		.language-label { margin-top: 20px; font-size: 14px; color: #ccc; }
		.languages { display: flex; justify-content: center; gap: 12px; margin: 10px 0 20px; flex-wrap: wrap; }
		.flag { width: 40px; height: 40px; border-radius: 50%; border: 1px solid #555; cursor: pointer; }
		.bottom-buttons { display: flex; justify-content: space-between; gap: 10px; padding: 0 10px; }
		.bottom-buttons a {
			flex: 1; text-align: center; padding: 10px 0; border-radius: 25px;
			font-weight: bold; text-decoration: none; display: inline-block; font-size: 14px;
			transition: transform 0.2s ease;
		}
		.btn-register {
			background: #ff1744; color: #fff;
			animation: bounce 1.2s infinite ease-in-out;
		}
		.btn-contact { background: #00c853; color: #fff; }
		@keyframes bounce {
			0%, 100% { transform: translateY(0); }
			50% { transform: translateY(-6px); }
		}
	
	</style>
	
	@if($webconfig->header_code)
		{!! $webconfig->header_code !!}
	@endif
	
	@stack('styles')
	@stack('script')
	
{{--	<link rel="preload" href="{{ asset('lang-').app()->getLocale() }}.js?v={{ date('Ymdhi') }}" as="script">--}}
{{--	<link rel="preload" href="{{ asset('assets/wm356/js/login.js') }}" as="script">--}}
{{--	<link rel="preload"--}}
{{--	      href="{{ asset('assets/wm356/js/minified_safe_optimized_no_jquery_bundle.js?v='. filemtime(public_path('assets/wm356/js/minified_safe_optimized_no_jquery_bundle.js')) ) }}"--}}
{{--	      as="script">--}}
{{--	<link rel="preload" href="{{ mix('assets/wm356/js/manifest.js') }}" as="script">--}}
{{--	<link rel="preload" href="{{ mix('assets/wm356/js/vendor.js') }}" as="script">--}}
{{--	<link rel="preload" href="{{ mix('assets/wm356/js/app.js') }}" as="script">--}}
{{--	<script src="{{ mix('assets/wm356/js/manifest.js') }}"></script>--}}
{{--	<script src="{{ mix('assets/wm356/js/vendor.js') }}"></script>--}}
{{--	<script src="{{ mix('assets/wm356/js/app.js') }}" id="mainscript" baseUrl="{{ url()->to('/') }}"></script>--}}
{{--	<script src="{{ asset('assets/wm356/js/minified_safe_optimized_no_jquery_bundle.js?v='.filemtime(public_path('assets/wm356/js/minified_safe_optimized_no_jquery_bundle.js')) ) }}"></script>--}}
{{--	<script src="{{ asset('lang-').app()->getLocale() }}.js?v={{ date('Ymdhi') }}"></script>--}}
{{--	<script src="{{ asset('assets/wm356/js/login.js') }}"></script>--}}
{{--	@laravelPWA--}}
</head>

<body>

{{--@include('wallet::layouts.header')--}}

@yield('content')

{{--@include('wallet::layouts.footer')--}}
@include('wallet::layouts.contact')

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
	@endif
			@endforeach
			
			@if (isset($errors))
			@if (count($errors))
        window.serverErrors = @json($errors->getMessages());
	@endif
	@endif

</script>

@stack('scripts')
</body>
</html>