{{-- Echo / Pusher init --}}
<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>

<script>
    (() => {
        try {
            window.Pusher = Pusher;

            const cfg = {
                broadcaster: 'pusher',
                key: @json(config('broadcasting.connections.pusher.key')),
                cluster: @json(config('broadcasting.connections.pusher.options.cluster')),
                forceTLS: @json(config('broadcasting.connections.pusher.options.useTLS', true)),
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

            @if(config('broadcasting.connections.pusher.options.host'))
            cfg.wsHost = @json(config('broadcasting.connections.pusher.options.host'));
            cfg.wsPort = @json(config('broadcasting.connections.pusher.options.port', 6001));
            cfg.wssPort = @json(config('broadcasting.connections.pusher.options.port', 443));
            cfg.forceTLS = @json(config('broadcasting.connections.pusher.options.useTLS', false));
            @endif

            window.Echo = new Echo(cfg);
            console.info('Echo initialized:', !!window.Echo);
        } catch (e) {
            console.warn('Echo init failed:', e);
            window.Echo = null;
        }
    })();
</script>