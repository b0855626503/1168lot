<script>
    window.broadcastConfig = {
        key: @json(config('broadcasting.connections.reverb.key', config('broadcasting.connections.pusher.key', 'app-key'))),
        host: @json(config('broadcasting.connections.reverb.options.host', config('broadcasting.connections.pusher.options.host', request()->getHost()))),
        port: @json((int) config('broadcasting.connections.reverb.options.port', config('broadcasting.connections.pusher.options.port', 443))),
        scheme: @json(config('broadcasting.connections.reverb.options.scheme', config('broadcasting.connections.pusher.options.scheme', request()->isSecure() ? 'https' : 'http'))),
        path: @json(config('broadcasting.connections.reverb.options.path', '')),
    };
</script>
