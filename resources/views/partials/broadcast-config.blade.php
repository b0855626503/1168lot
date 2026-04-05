<script>
    window.broadcastConfig = {
        key: @json(env('REVERB_APP_KEY', env('PUSHER_APP_KEY', 'app-key'))),
        host: @json(request()->getHost()),
        port: @json((int) env('REVERB_PORT', env('PUSHER_PORT', 8080))),
        scheme: @json(env('REVERB_SCHEME', request()->isSecure() ? 'https' : 'http')),
    };
</script>
