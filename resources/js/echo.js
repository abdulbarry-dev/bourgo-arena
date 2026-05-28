// Echo/Reverb integration disabled by default. Initialize only when
// a VITE_REVERB_APP_KEY environment variable is explicitly provided.
if (import.meta.env.VITE_REVERB_APP_KEY) {
    // Dynamically import Echo and Pusher only when explicitly enabled in env.
    Promise.all([
        import('laravel-echo').then(m => m.default || m),
        import('pusher-js').then(m => m.default || m),
    ]).then(([EchoLib, PusherLib]) => {
        window.Pusher = PusherLib;

        window.Echo = new EchoLib({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    }).catch((err) => {
        // If packages are not installed or import fails, avoid throwing in runtime.
        // Keep window.Echo null and log a debug message.
        // eslint-disable-next-line no-console
        console.debug('Echo/Pusher not loaded:', err && err.message ? err.message : err);
        window.Echo = null;
    });
} else {
    window.Echo = null;
}
