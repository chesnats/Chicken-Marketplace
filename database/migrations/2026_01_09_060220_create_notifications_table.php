<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification
{
    use Queueable;

    protected $item;
    protected $status;

    public function __construct($item, $status)
    {
        $this->item = $item;
        $this->status = $status;
    }

    public function via($notifiable)
    {
        return ['database']; // Store in database
    }

    public function toArray($notifiable)
    {
        return [
            'item_id' => $this->item->id,
            'listing_breed' => $this->item->listing->breed,
            'status' => $this->status,
            'message' => $this->getMessage(),
        ];
    }

    protected function getMessage()
    {
        return match($this->status) {
            'accepted' => "Your order for {$this->item->listing->breed} has been accepted!",
            'on_delivery' => "Your chicken is now on the way!",
            'delivered' => "Order #{$this->item->id} has been successfully delivered.",
            default => "Update on your order.",
        };
    }
}