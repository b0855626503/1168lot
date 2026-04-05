<script>
    (function () {
        if (!window.Echo || !window.Echo.constructor || !window.Pusher) {
            return;
        }

        var config = window.broadcastConfig || {};
        var scheme = config.scheme || window.location.protocol.replace(':', '') || 'http';
        var port = Number(config.port || (scheme === 'https' ? 443 : 8080));

        if (typeof window.Echo.disconnect === 'function') {
            window.Echo.disconnect();
        }

        window.Echo = new window.Echo.constructor({
            broadcaster: 'pusher',
            key: config.key || 'app-key',
            wsHost: config.host || window.location.hostname,
            wsPort: port,
            wssPort: port,
            forceTLS: scheme === 'https',
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
            authEndpoint: @json($authEndpoint),
        });
    })();
</script>
