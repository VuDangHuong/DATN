<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Sign\DocumentSignRequest;
use App\Notifications\Channels\CustomDatabaseChannel;
class SignRequestSigned extends Notification
{
    use Queueable;

    public function __construct(
        public DocumentSignRequest $signRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', CustomDatabaseChannel::class];
    }

    public function toCustomDatabase(object $notifiable): array
    {
        $documentLabel = $this->signRequest->document_type === 'report' ? 'báo cáo' : 'slide';

        return [
            'title'   => 'Tài liệu đã được ký số',
            'content' => "Tài liệu {$documentLabel} của bạn đã được ký số thành công và sẵn sàng tải về.",
            'type'    => 'sign_request',
            'link'    => "/sign-requests/{$this->signRequest->id}/download",
        ];
    }

    // ==================== MAIL ====================

    public function toMail(object $notifiable): MailMessage
    {
        $documentLabel = $this->signRequest->document_type === 'report'
            ? 'Báo cáo'
            : 'Slide';

        $lecturerName = $this->signRequest->lecturer?->name ?? 'Giảng viên';

        return (new MailMessage)
            ->subject("[Số hóa tài liệu] {$documentLabel} đã được ký số thành công")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("{$lecturerName} đã ký số {$documentLabel} của nhóm bạn thành công.")
            ->line("Tài liệu đã được Admin phát hành và sẵn sàng để tải về.")
            ->action(
                'Tải tài liệu đã ký',
                url("/sign-requests/{$this->signRequest->id}/download")
            )
            ->line("Mã xác thực tài liệu: `{$this->signRequest->sign_hash}`")
            ->line('Vui lòng lưu mã này để xác minh tính toàn vẹn của tài liệu khi cần.')
            ->salutation('Trân trọng, Hệ thống quản lý đồ án');
    }

    // ==================== DATABASE ====================

    public function toDatabase(object $notifiable): array
    {
        $documentLabel = $this->signRequest->document_type === 'report'
            ? 'báo cáo'
            : 'slide';

        return [
            'type'            => 'sign_request_signed',
            'sign_request_id' => $this->signRequest->id,
            'document_type'   => $this->signRequest->document_type,
            'signed_by'       => $this->signRequest->lecturer?->name,
            'signed_at'       => $this->signRequest->signed_at?->toISOString(),
            'sign_hash'       => $this->signRequest->sign_hash,
            'message'         => "Tài liệu {$documentLabel} của bạn đã được ký số và sẵn sàng tải về.",
        ];
    }
}
