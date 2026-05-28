// Echo/Reverb integration disabled by default. Initialize only when
// a VITE_REVERB_APP_KEY environment variable is explicitly provided.
if (import.meta.env.VITE_REVERB_APP_KEY) {
    import Echo from 'laravel-echo';
    import Pusher from 'pusher-js';
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
} else {
    // Ensure no global Echo is present so client code won't attempt connections
    window.Echo = null;
}
