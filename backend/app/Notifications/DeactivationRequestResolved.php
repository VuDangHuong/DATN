<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Sign\SignProfileDeactivationRequest;
use App\Notifications\Channels\CustomDatabaseChannel;
class DeactivationRequestResolved extends Notification
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
        $isApproved = $this->request->status === 'approved';
        $frontend = config('app.frontend_url');
 
        $mail = (new MailMessage)
            ->subject($isApproved
                ? '[EduGroup] Yêu cầu vô hiệu hóa được chấp thuận'
                : '[EduGroup] Yêu cầu vô hiệu hóa bị từ chối')
            ->greeting("Chào {$notifiable->name},");
 
        if ($isApproved) {
            $mail->line('Yêu cầu vô hiệu hóa chữ ký số của bạn đã được **chấp thuận**.')
                 ->line('Chữ ký số hiện tại đã ngừng hoạt động.')
                 ->line('Bạn có thể đăng ký chữ ký mới khi cần.');
        } else {
            $mail->line('Yêu cầu vô hiệu hóa chữ ký số của bạn đã bị **từ chối**.')
                 ->line('**Lý do từ chối:** ' . ($this->request->admin_note ?? 'Không có ghi chú'))
                 ->line('Chữ ký số của bạn vẫn còn hiệu lực. Bạn có thể tạo yêu cầu mới nếu cần.');
        }
 
        return $mail->action('Xem chi tiết', "{$frontend}/lecturer/sign-profile");
    }
 
    public function toCustomDatabase($notifiable): array
    {
        $isApproved = $this->request->status === 'approved';
 
        return [
            'type'     => 'deactivation_resolved',
            'title'    => $isApproved
                ? 'Yêu cầu vô hiệu hóa được chấp thuận'
                : 'Yêu cầu vô hiệu hóa bị từ chối',
            'body'     => $isApproved
                ? 'Chữ ký số của bạn đã ngừng hoạt động'
                : ('Lý do: ' . ($this->request->admin_note ?? '—')),
            'link'     => '/lecturer/sign-profile',
            'metadata' => [
                'request_id' => $this->request->id,
                'status'     => $this->request->status,
            ],
        ];
    }
}
