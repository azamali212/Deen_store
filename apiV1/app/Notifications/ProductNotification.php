<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ProductNotification extends Notification
{
    public $product;
    public $notificationType;

    /**
     * Create a new notification instance.
     *
     * @param  Product  $product
     * @param  string  $notificationType
     * @return void
     */
    public function __construct(Product $product, $notificationType)
    {
        $this->product = $product;
        $this->notificationType = $notificationType;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(ucfirst($this->notificationType) . ' Product: ' . $this->product->name)
            ->line($this->getEmailMessage());
    }

    /**
     * Get the email message based on the notification type.
     *
     * @return string
     */
    protected function getEmailMessage()
    {
        switch ($this->notificationType) {
            case 'created':
                return 'A new product has been created: ' . $this->product->description;
            case 'updated':
                return 'The product has been updated: ' . $this->product->description;
            case 'deleted':
                return 'The product has been deleted: ' . $this->product->name;
            default:
                return '';
        }
    }
}