<?php

namespace App\Notifications;

use App\Models\Sign\DocumentSignRequest;
use App\Notifications\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        $category = $this->signRequest->document_category_label
            ?? $this->signRequest->document_category
            ?? 'tài liệu';

        return [
            'title'   => 'Tài liệu đã được ký số',
            'content' => "Giảng viên đã ký số {$category} của bạn. Bạn có thể tải tài liệu đã ký từ hệ thống.",
            'type'    => 'sign_request',
            'link'    => "/student/assignments",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $category = $this->signRequest->document_category_label
            ?? $this->signRequest->document_category
            ?? 'Tài liệu';

        $lecturerName = $this->signRequest->lecturer?->name ?? 'Giảng viên';
        $signedAt     = $this->signRequest->signed_at?->format('d/m/Y H:i')
            ?? now()->format('d/m/Y H:i');
        $signHash     = $this->signRequest->sign_hash ?? '';

        return (new MailMessage)
            ->subject("[EduGroup] Tài liệu của bạn đã được ký số")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("Giảng viên **{$lecturerName}** đã ký số tài liệu của bạn thành công.")
            ->line("**Loại tài liệu:** {$category}")
            ->line("**Thời gian ký:** {$signedAt}")
            ->line("**Mã xác thực (SHA-256):**")
            ->line("`{$signHash}`")
            ->action(
                'Tải tài liệu đã ký',
                config('app.frontend_url') . '/student/assignments'
            )
            ->line('Mã xác thực dùng để kiểm tra tính toàn vẹn của tài liệu. Vui lòng lưu mã này để đối chiếu khi cần.')
            ->salutation('Trân trọng, Hệ thống EduGroup');
    }

    public function toDatabase(object $notifiable): array
    {
        $category = $this->signRequest->document_category_label
            ?? $this->signRequest->document_category
            ?? 'tài liệu';

        return [
            'type'            => 'sign_request_signed',
            'sign_request_id' => $this->signRequest->id,
            'document_type'   => $this->signRequest->document_type,
            'category'        => $category,
            'signed_by'       => $this->signRequest->lecturer?->name,
            'signed_at'       => $this->signRequest->signed_at?->toISOString(),
            'sign_hash'       => $this->signRequest->sign_hash,
            'message'         => "Giảng viên đã ký số {$category} của bạn.",
        ];
    }
}