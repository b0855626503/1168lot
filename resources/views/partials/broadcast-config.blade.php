<script>
    window.broadcastConfig = {
        key: @json(config('reverb.apps.apps.0.key', config('broadcasting.connections.reverb.key', 'app-key'))),
        host: @json(config('reverb.apps.apps.0.options.host', request()->getHost())),
        port: @json((int) config('reverb.apps.apps.0.options.port', config('broadcasting.connections.reverb.options.port', 8080))),
        scheme: @json(config('reverb.apps.apps.0.options.scheme', request()->isSecure() ? 'https' : 'http')),
        path: @json(config('reverb.servers.reverb.path', '')),
    };
</script>
