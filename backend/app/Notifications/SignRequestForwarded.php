<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Sign\DocumentSignRequest;
use App\Notifications\Channels\CustomDatabaseChannel;
class SignRequestForwarded extends Notification
{
    use Queueable;

    public function __construct(
        public DocumentSignRequest $signRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', CustomDatabaseChannel::class];  // ← dùng class thay vì string 'database'
    }
    public function toCustomDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Yêu cầu ký số mới',
            'content' => "Admin đã chuyển cho bạn yêu cầu ký số tài liệu của nhóm {$this->signRequest->submission->group->name}.",
            'type'    => 'sign_request',
            'link'    => "/lecturer/sign-requests/{$this->signRequest->id}",
        ];
    }
    // ==================== MAIL ====================

    public function toMail(object $notifiable): MailMessage
    {
        $documentLabel = $this->signRequest->document_type === 'report'
            ? 'Báo cáo'
            : 'Slide';

        $groupName     = $this->signRequest->submission->group->name ?? 'Nhóm';
        $className     = $this->signRequest->classModel->name ?? 'Lớp';
        $studentName   = $this->signRequest->requester->name ?? 'Sinh viên';

        return (new MailMessage)
            ->subject("[Ký số] Yêu cầu ký tài liệu mới - #{$this->signRequest->id}")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("Admin đã chuyển cho bạn một yêu cầu ký số tài liệu.")
            ->line("**Thông tin yêu cầu:**")
            ->line("- Loại tài liệu: {$documentLabel}")
            ->line("- Nhóm: {$groupName}")
            ->line("- Lớp: {$className}")
            ->line("- Sinh viên gửi: {$studentName}")
            ->line("- Thời gian chuyển: {$this->signRequest->forwarded_at?->format('d/m/Y H:i')}")
            ->action(
                'Xem và ký tài liệu',
                url("/lecturer/sign-requests/{$this->signRequest->id}")
            )
            ->line('Vui lòng xem xét và thực hiện ký số sớm nhất có thể.')
            ->salutation('Trân trọng, Hệ thống quản lý đồ án');
    }

    // ==================== DATABASE ====================

    public function toDatabase(object $notifiable): array
    {
        $documentLabel = $this->signRequest->document_type === 'report'
            ? 'báo cáo'
            : 'slide';

        return [
            'type'            => 'sign_request_forwarded',
            'sign_request_id' => $this->signRequest->id,
            'document_type'   => $this->signRequest->document_type,
            'group_name'      => $this->signRequest->submission->group->name ?? null,
            'class_name'      => $this->signRequest->classModel->name ?? null,
            'requester_name'  => $this->signRequest->requester->name ?? null,
            'forwarded_at'    => $this->signRequest->forwarded_at?->toISOString(),
            'message'         => "Bạn có yêu cầu ký số {$documentLabel} mới từ Admin.",
        ];
    }
}
