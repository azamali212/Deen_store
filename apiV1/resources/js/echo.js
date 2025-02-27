import Echo from 'laravel-echo';

// Remove Pusher import, as you're using Reverb now
// import Pusher from 'pusher-js'; 

import Reverb from 'reverb-client'; // Ensure you have this package installed for Reverb
window.Reverb = Reverb;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080
    ,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

