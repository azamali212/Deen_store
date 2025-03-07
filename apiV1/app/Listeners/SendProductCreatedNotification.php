<?php

namespace App\Listeners;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Models\User;
use App\Notifications\ProductNotification;
use App\Repositories\Email\EmailRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendProductCreatedNotification
{
    protected $emailRepository;

    /**
     * Create the event listener.
     *
     * @param  EmailRepository  $emailRepository
     * @return void
     */
    public function __construct(EmailRepository $emailRepository)
    {
        $this->emailRepository = $emailRepository;
    }

    /**
     * Handle the event.
     *
     * @param  mixed  $event
     * @return void
     */
    public function handle($event)
    {
        $product = null;
        $notificationType = null;

        // Determine the type of event
        if ($event instanceof ProductCreated) {
            $product = $event->product;
            $notificationType = 'created';
        } elseif ($event instanceof ProductUpdated) {
            $product = $event->product;
            $notificationType = 'updated';
        } elseif ($event instanceof ProductDeleted) {
            $product = $event->product;
            $notificationType = 'deleted';
        }

        // If no event was matched, return
        if (!$product) {
            return;
        }

        // Get all users who should receive the notification (admins, staff, etc.)
        $users = User::where('role', 'admin')->get();

        foreach ($users as $user) {
            // Send email notification (using a custom notification class)
            Notification::send($user, new ProductNotification($product, $notificationType));

            // Optionally, store email record in the database
            $this->emailRepository->sendEmail(
                $user->id,
                $user->id,
                ucfirst($notificationType) . ' Product: ' . $product->name,
                $this->getEmailMessage($notificationType, $product)
            );
        }

        // Trigger Pusher event for real-time notification
        broadcast(new ProductCreated($product, $notificationType));
    }

    /**
     * Get the email message based on the notification type.
     *
     * @param  string  $notificationType
     * @param  Product  $product
     * @return string
     */
    protected function getEmailMessage($notificationType, $product)
    {
        switch ($notificationType) {
            case 'created':
                return 'A new product has been created: ' . $product->description;
            case 'updated':
                return 'The product has been updated: ' . $product->description;
            case 'deleted':
                return 'The product has been deleted: ' . $product->name;
            default:
                return '';
        }
    }
}
