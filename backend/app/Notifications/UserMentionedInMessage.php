<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Channels\CustomDatabaseChannel;
use App\Models\Communication\Message;
class UserMentionedInMessage extends Notification
{
    use Queueable;
 
    public function __construct(public Message $message) {}
 
    public function via($notifiable): array
    {
        return ['mail', CustomDatabaseChannel::class];
    }
 
    /**
     * Mail
     */
    public function toMail($notifiable): MailMessage
    {
        $sender    = $this->message->user;
        $group     = $this->message->group;
        $frontend  = config('app.frontend_url');
        $preview   = mb_strimwidth($this->message->content, 0, 100, '…');
 
        return (new MailMessage)
            ->subject("[EduGroup] {$sender->name} đã nhắc đến bạn")
            ->greeting("Chào {$notifiable->name},")
            ->line("**{$sender->name}** đã nhắc đến bạn trong nhóm **{$group->name}**:")
            ->line("\"{$preview}\"")
            ->action('Xem tin nhắn', "{$frontend}")
            ->line('Hãy phản hồi nếu cần thiết.');
    }
 
    /**
     * Database — custom format
     */
    public function toCustomDatabase($notifiable): array
    {
        $sender = $this->message->user;
        $group  = $this->message->group;
 
        return [
            'type'       => 'message_mention',
            'title'      => "{$sender->name} đã nhắc đến bạn",
            'body'       => mb_strimwidth($this->message->content, 0, 150, '…'),
            'link'       => "/student/groups/{$group->id}/chat",
            'metadata'   => [
                'message_id' => $this->message->id,
                'group_id'   => $group->id,
                'group_name' => $group->name,
                'sender_id'  => $sender->id,
                'sender_name'=> $sender->name,
            ],
        ];
    }
}
