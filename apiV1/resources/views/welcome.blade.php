<!DOCTYPE html>

<head>
    <title>Pusher Test</title>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        // Enable pusher logging - don't include this in production
        Pusher.logToConsole = true;

        var pusher = new Pusher('0de15578f51853403134', {
            cluster: 'us2'
        });

        // Dynamically set the receiver_id from the backend
        var receiverId = 123; // Replace with the receiver ID from your backend
        var channel = pusher.subscribe('emails.' + receiverId);

        // Bind to the 'email.status.updated' event defined in the Laravel event
        channel.bind('email.status.updated', function(data) {
            alert('Email ID: ' + data.email_id + ' Status: ' + data.status);
        });
    </script>
</head>

<body>
    <h1>Pusher Test</h1>
    <p>
        Try publishing an event to channel <code>emails.{receiver_id}</code>
        with event name <code>email.status.updated</code>.
    </p>
</body>