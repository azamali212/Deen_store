// resources/js/app.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    app_id:"1950383",
    key:"0de15578f51853403134",
    secret:"be568fc96ee74d7d4a0a",
    cluster:"us2",
    forceTLS: true, 
});

// Replace with dynamic receiverId (you can get this from the authenticated user or email context)
const receiverId = 1; // Example receiverId

echo.channel('emails.' + receiverId)
    .listen('EmailStatusUpdated', (event) => {
        console.log('Email status updated: ', event);
        // Handle the event, e.g., update UI
    });