<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Communication\Message;
class MessageSent implements ShouldBroadcast
{
    public function __construct(public Message $message) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('group.' . $this->message->group_id);
    }

    // Chỉ gửi những field cần thiết xuống client
    public function broadcastWith(): array
    {
        return [
            'id'         => $this->message->id,
            'content'    => $this->message->content,
            'created_at' => $this->message->created_at,
            'sender'     => [
                'id'   => $this->message->sender->id,
                'name' => $this->message->sender->name,
            ],
        ];
    }
}

