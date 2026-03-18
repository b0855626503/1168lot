<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">

<head>
    <meta charset="utf-8">
    <title>{{ ucwords($config->sitename) }} - {{ $config->title }}</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <link rel="icon" type="image/png" sizes="32x32" href="{!! core()->imgurl($config->favicon,'img') !!}">
    <meta name="description" content="{{ $config->description }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Scrollbar Custom CSS -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">

    <!-- Font Awesome JS -->
    <link href="https://kit-pro.fontawesome.com/releases/v5.15.3/css/pro.min.css" rel="stylesheet">

    <!-- AOS JS -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css"/>

    <link rel="stylesheet" href="{{ mix('css/web.css') }}">
    @stack('styles')


    <!-- Facebook shared -->
    <meta property="og:url" content=""/>
    <meta property="og:type" content="article"/>
    <meta property="og:title" content="{{ $config->title }}"/>
    <meta property="og:description" content="{{ $config->description }}"/>
    <meta property="og:image" content="img"/>
    <meta name='robots' content='max-image-preview:large'/>

</head>

<body class="custom-theme">
<div id="app">
    <div class="wrapper">

        @yield('content')

    </div>
</div>
<footer class="x-footer -anon mt-auto bg-black">
    <div class="copyright">
        COPYRIGHT©2022, GAMETECH
    </div>
</footer>


<div class="overlay"></div>
<script type="text/javascript">
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
<script src="{{ mix('js/manifest.js') }}"></script>
<script src="{{ mix('js/vendor.js') }}"></script>
<script src="{{ mix('js/app.js') }}" id="mainscript" baseUrl="{{ url()->to('/') }}"></script>
@stack('scripts')
</body>
</html>


