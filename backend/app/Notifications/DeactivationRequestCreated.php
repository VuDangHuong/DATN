<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Sign\SignProfileDeactivationRequest;
use App\Notifications\Channels\CustomDatabaseChannel;
class DeactivationRequestCreated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public SignProfileDeactivationRequest $request)
    {
        //
    }

    public function via($notifiable): array
    {
        return ['mail', CustomDatabaseChannel::class];
    }
 
    public function toMail($notifiable): MailMessage
    {
        $lecturer = $this->request->lecturer;
        $frontend = config('app.frontend_url');
 
        return (new MailMessage)
            ->subject("[EduGroup] Yêu cầu vô hiệu hóa chữ ký số")
            ->greeting("Chào {$notifiable->name},")
            ->line("**{$lecturer->name}** vừa gửi yêu cầu vô hiệu hóa chữ ký số.")
            ->line("**Lý do:** " . $this->request->reason)
            ->action('Xem yêu cầu', "{$frontend}/admin/deactivation-requests/{$this->request->id}")
            ->line('Vui lòng xem xét và duyệt yêu cầu.');
    }
 
    public function toCustomDatabase($notifiable): array
    {
        $lecturer = $this->request->lecturer;
 
        return [
            'type'     => 'deactivation_requested',
            'title'    => "{$lecturer->name} yêu cầu vô hiệu hóa chữ ký số",
            'body'     => mb_strimwidth($this->request->reason, 0, 150, '…'),
            'link'     => "/admin/deactivation-requests/{$this->request->id}",
            'metadata' => [
                'request_id'   => $this->request->id,
                'lecturer_id'  => $lecturer->id,
                'lecturer_name'=> $lecturer->name,
            ],
        ];
    }
}
