<?php

namespace App\Notifications;

use App\Models\Sign\DocumentSignRequest;
use App\Notifications\Channels\CustomDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SignRequestRejected extends Notification
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
            'title'   => 'Yêu cầu ký số bị từ chối',
            'content' => "Giảng viên đã từ chối yêu cầu ký số {$category}. Lý do: {$this->signRequest->reject_reason}",
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
        $reason       = $this->signRequest->reject_reason ?? 'Không có lý do cụ thể';

        return (new MailMessage)
            ->subject("[EduGroup] Yêu cầu ký số #{$this->signRequest->id} bị từ chối")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("Giảng viên **{$lecturerName}** đã từ chối yêu cầu ký số tài liệu của bạn.")
            ->line("**Loại tài liệu:** {$category}")
            ->line("**Lý do từ chối:** {$reason}")
            ->action(
                'Xem chi tiết',
                config('app.frontend_url') . '/student/assignments'
            )
            ->line('Bạn có thể chỉnh sửa tài liệu và gửi lại yêu cầu mới từ hệ thống.')
            ->salutation('Trân trọng, Hệ thống EduGroup');
    }

    public function toDatabase(object $notifiable): array
    {
        $category = $this->signRequest->document_category_label
            ?? $this->signRequest->document_category
            ?? 'tài liệu';

        return [
            'type'            => 'sign_request_rejected',
            'sign_request_id' => $this->signRequest->id,
            'document_type'   => $this->signRequest->document_type,
            'category'        => $category,
            'reason'          => $this->signRequest->reject_reason,
            'lecturer_name'   => $this->signRequest->lecturer?->name,
            'message'         => "Giảng viên đã từ chối yêu cầu ký số {$category} của bạn.",
        ];
    }
}