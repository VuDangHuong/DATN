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
        //Dùng submitter_name accessor thay vì access group trực tiếp
        $submitterName = $this->signRequest->submission->submitter_name ?? 'Không xác định';

        return [
            'title'   => 'Yêu cầu ký số mới',
            'content' => "Admin đã chuyển cho bạn yêu cầu ký số tài liệu của {$submitterName}.",
            'type'    => 'sign_request',
            'link'    => "/lecturer/sign-requests/{$this->signRequest->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        //Lấy document_category_label thay vì hardcode report/slide
        $documentLabel = $this->signRequest->document_category_label
            ?? $this->signRequest->document_type
            ?? 'Tài liệu';

        //null-safe cho cả group lẫn individual
        $submitterName = $this->signRequest->submission->submitter_name ?? 'Không xác định';
        $className     = $this->signRequest->classModel?->name ?? 'Lớp';
        $studentName   = $this->signRequest->requester?->name ?? 'Sinh viên';

        return (new MailMessage)
            ->subject("[Ký số] Yêu cầu ký tài liệu mới - #{$this->signRequest->id}")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("Admin đã chuyển cho bạn một yêu cầu ký số tài liệu.")
            ->line("- Loại tài liệu: {$documentLabel}")
            ->line("- Nhóm/SV: {$submitterName}")
            ->line("- Lớp: {$className}")
            ->line("- Người gửi: {$studentName}")
            ->line("- Thời gian chuyển: {$this->signRequest->forwarded_at?->format('d/m/Y H:i')}")
            ->action('Xem và ký tài liệu', url("/lecturer/sign-requests/{$this->signRequest->id}"))
            ->line('Vui lòng xem xét và thực hiện ký số sớm nhất có thể.')
            ->salutation('Trân trọng, Hệ thống quản lý đồ án');
    }

    public function toDatabase(object $notifiable): array
    {
        $documentLabel = $this->signRequest->document_category_label
            ?? $this->signRequest->document_type
            ?? 'tài liệu';

        //null-safe
        $submitterName = $this->signRequest->submission->submitter_name ?? null;
        $className     = $this->signRequest->classModel?->name ?? null;

        return [
            'type'            => 'sign_request_forwarded',
            'sign_request_id' => $this->signRequest->id,
            'document_type'   => $this->signRequest->document_type,
            'submitter_name'  => $submitterName,   // ← đổi group_name thành submitter_name
            'class_name'      => $className,
            'requester_name'  => $this->signRequest->requester?->name ?? null,
            'forwarded_at'    => $this->signRequest->forwarded_at?->toISOString(),
            'message'         => "Bạn có yêu cầu ký số {$documentLabel} mới từ Admin.",
        ];
    }
}
