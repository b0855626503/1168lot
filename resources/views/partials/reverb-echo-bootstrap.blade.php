<script>
    (function () {
        if (!window.Echo || !window.Echo.constructor || !window.Pusher) {
            return;
        }

        var config = window.broadcastConfig || {};
        var scheme = config.scheme || window.location.protocol.replace(':', '') || 'http';
        var port = Number(config.port || (scheme === 'https' ? 443 : 8080));
        var host = (config.host || 'websocket.168csn.com').toString();
        var path = (config.path || '').toString();

        if (path.length > 0 && path.charAt(0) !== '/') {
            path = '/' + path;
        }

        if (typeof window.Echo.disconnect === 'function') {
            window.Echo.disconnect();
        }

        window.Echo = new window.Echo.constructor({
            broadcaster: 'pusher',
            key: config.key || 'app-key',
            wsHost: host,
            wsPort: port,
            wssPort: port,
            wsPath: path,
            forceTLS: scheme === 'https',
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
            authEndpoint: @json($authEndpoint),
        });
    })();
</script>
