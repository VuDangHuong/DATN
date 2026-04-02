<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Sign\DocumentSignRequest;
use App\Notifications\Channels\CustomDatabaseChannel;
class SignRequestRejected extends Notification
{
   use Queueable;

    /**
     * @param DocumentSignRequest $signRequest
     * @param string $rejectedBy  'admin' | 'lecturer'
     */
    public function __construct(
        public DocumentSignRequest $signRequest,
        public string $rejectedBy = 'admin'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', CustomDatabaseChannel::class];
    }

    public function toCustomDatabase(object $notifiable): array
    {
        $rejectorLabel = $this->rejectedBy === 'admin' ? 'Admin' : 'Giảng viên';
        $documentLabel = $this->signRequest->document_type === 'report' ? 'báo cáo' : 'slide';

        return [
            'title'   => 'Yêu cầu số hóa bị từ chối',
            'content' => "{$rejectorLabel} đã từ chối yêu cầu số hóa {$documentLabel} của bạn. Lý do: {$this->signRequest->reject_reason}",
            'type'    => 'sign_request',
            'link'    => "/sign-requests/{$this->signRequest->id}",
        ];
    }

    // ==================== MAIL ====================

    public function toMail(object $notifiable): MailMessage
    {
        $rejectorLabel = $this->rejectedBy === 'admin' ? 'Admin' : 'Giảng viên';
        $documentLabel = $this->signRequest->document_type === 'report'
            ? 'Báo cáo'
            : 'Slide';

        return (new MailMessage)
            ->subject("[Số hóa tài liệu] Yêu cầu #{$this->signRequest->id} bị từ chối")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("{$rejectorLabel} đã từ chối yêu cầu số hóa {$documentLabel} của nhóm bạn.")
            ->line("**Lý do:** {$this->signRequest->reject_reason}")
            ->action(
                'Xem chi tiết yêu cầu',
                url("/sign-requests/{$this->signRequest->id}")
            )
            ->line('Bạn có thể chỉnh sửa và gửi lại yêu cầu sau khi khắc phục vấn đề.')
            ->salutation('Trân trọng, Hệ thống quản lý đồ án');
    }

    // ==================== DATABASE ====================

    public function toDatabase(object $notifiable): array
    {
        $rejectorLabel = $this->rejectedBy === 'admin' ? 'Admin' : 'Giảng viên';
        $documentLabel = $this->signRequest->document_type === 'report'
            ? 'báo cáo'
            : 'slide';

        return [
            'type'            => 'sign_request_rejected',
            'sign_request_id' => $this->signRequest->id,
            'rejected_by'     => $this->rejectedBy,
            'document_type'   => $this->signRequest->document_type,
            'reason'          => $this->signRequest->reject_reason,
            'message'         => "{$rejectorLabel} đã từ chối yêu cầu số hóa {$documentLabel} của bạn.",
        ];
    }
}
