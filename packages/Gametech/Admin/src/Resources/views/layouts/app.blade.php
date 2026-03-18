<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="UTF-8">
    <title>{{ ucwords($config->sitename) }} - @yield('title')</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ core()->imgurl($config->favicon,'img') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Admin Zone">
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.7.2/css/all.css"
          integrity="sha384-6jHF7Z3XI3fF4XZixAuSu0gGKrXwoX/w3uFPxC56OtjChio7wtTGJWRW53Nhx6Ev" crossorigin="anonymous">
    <!-- Theme style -->

    <link rel="stylesheet" href="{{ asset('assets/admin/css/web.css') }}">

    @yield('css')
</head>

<body class="hold-transition login-page">
<div id="app">

    @yield('content')

</div>

<script type="text/javascript">
    window.flashMessages = [];
    window.serverErrors = [];

    @foreach (['success', 'warning', 'error', 'info'] as $key)
        @if ($value = session($key))
        window.flashMessages.push({'type': '{{ $key }}', 'message': "{{ $value }}" });
        @endif
    @endforeach

    @if (isset($errors))
        @if (count($errors))
        window.serverErrors = @json($errors->getMessages());
        @endif
    @endif
</script>


<script src="{{ mix('assets/admin/js/manifest.js') }}"></script>
<script src="{{ mix('assets/admin/js/vendor.js') }}"></script>
<script baseUrl="{{ url()->to('/') }}" id="mainscript" src="{{ mix('assets/admin/js/app.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/ui/js/ui.js') }}"></script>

@stack('scripts')
</body>
</html>
