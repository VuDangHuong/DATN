<?php

namespace App\Notifications\Channels;
use App\Models\Communication\Notification;
use Illuminate\Notifications\Notification as BaseNotification;
class CustomDatabaseChannel
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function send(object $notifiable, BaseNotification $notification): void
    {
        // Notification phải có method toCustomDatabase()
        if (!method_exists($notification, 'toCustomDatabase')) {
            return;
        }

        $data = $notification->toCustomDatabase($notifiable);

        Notification::create([
            'user_id' => $notifiable->id,
            'title'   => $data['title'],
            'content' => $data['content'],
            'type'    => $data['type'],
            'link'    => $data['link'] ?? null,
            'is_read' => false,
        ]);
    }
}
