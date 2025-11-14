
<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>

<script>
    (() => {
        try {
            window.Pusher = Pusher;

            const cfg = {
                broadcaster: 'pusher',
                key: <?php echo json_encode(config('broadcasting.connections.pusher.key'), 15, 512) ?>,
                cluster: <?php echo json_encode(config('broadcasting.connections.pusher.options.cluster'), 15, 512) ?>,
                forceTLS: <?php echo json_encode(config('broadcasting.connections.pusher.options.useTLS', true), 512) ?>,
                authEndpoint: '/broadcasting/auth',
                authTransport: 'ajax',
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
                    },
                    // request should send cookies so Laravel can find session user
                    withCredentials: true
                }
            };

            <?php if(config('broadcasting.connections.pusher.options.host')): ?>
            cfg.wsHost = <?php echo json_encode(config('broadcasting.connections.pusher.options.host'), 15, 512) ?>;
            cfg.wsPort = <?php echo json_encode(config('broadcasting.connections.pusher.options.port', 6001), 512) ?>;
            cfg.wssPort = <?php echo json_encode(config('broadcasting.connections.pusher.options.port', 443), 512) ?>;
            cfg.forceTLS = <?php echo json_encode(config('broadcasting.connections.pusher.options.useTLS', false), 512) ?>;
            <?php endif; ?>

            window.Echo = new Echo(cfg);
            console.info('Echo initialized:', !!window.Echo);
        } catch (e) {
            console.warn('Echo init failed:', e);
            window.Echo = null;
        }
    })();
</script><?php /**PATH C:\xampp\htdocs\cosmetics-shop\resources\views/partials/echo.blade.php ENDPATH**/ ?>