<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Task\Task;
use App\Notifications\Channels\CustomDatabaseChannel;
class TaskReviewResolved extends Notification
{
    use Queueable;
 
    public function __construct(
        public Task $task,
        public string $action,   // 'approved' | 'rejected'
    ) {}
 
    public function via($notifiable): array
    {
        return ['mail',CustomDatabaseChannel::class];
    }
    public function toMail($notifiable): MailMessage
    {
        $isApproved = $this->action === 'approved';
        $reviewer   = $this->task->reviewer;
        $frontend   = config('app.frontend_url');
        $taskLink   = "{$frontend}";
 
        $mail = (new MailMessage)
            ->subject($isApproved
                ? '[EduGroup] ✅ Công việc đã được duyệt hoàn thành'
                : '[EduGroup] ❌ Công việc bị từ chối duyệt'
            )
            ->greeting("Chào {$notifiable->name},");
 
        if ($isApproved) {
            $mail->line("**{$reviewer?->name}** đã duyệt hoàn thành công việc của bạn.")
                 ->line("📋 **Công việc:** {$this->task->title}")
                 ->when($this->task->review_note, function ($m) {
                     return $m->line("💬 **Ghi chú từ nhóm trưởng:**")
                              ->line($this->task->review_note);
                 })
                 ->action('Xem chi tiết', $taskLink)
                 ->line('Cảm ơn bạn đã hoàn thành công việc!');
        } else {
            $mail->line("**{$reviewer?->name}** đã từ chối yêu cầu hoàn thành công việc.")
                 ->line("📋 **Công việc:** {$this->task->title}")
                 ->line("🔴 **Lý do từ chối:**")
                 ->line($this->task->review_note ?? '(Không có ghi chú)')
                 ->action('Xem & Tiếp tục làm', $taskLink)
                 ->line('Vui lòng kiểm tra phản hồi và cập nhật công việc trước khi báo lại.');
        }
 
        return $mail;
    }
    public function toCustomDatabase($notifiable): array
    {
        $isApproved = $this->action === 'approved';
        $reviewer   = $this->task->reviewer;
 
        return [
            'type'     => $isApproved ? 'task_review_approved' : 'task_review_rejected',
            'title'    => $isApproved
                ? "Công việc đã được duyệt hoàn thành"
                : "Công việc bị từ chối duyệt",
            'body'     => $isApproved
                ? "{$reviewer?->name} đã duyệt: {$this->task->title}"
                : "{$reviewer?->name} đã từ chối: " . mb_strimwidth($this->task->review_note ?? '', 0, 100, '…'),
            'link'     => "/student/groups/{$this->task->group_id}/tasks?task=" . $this->task->id,
            'metadata' => [
                'task_id'      => $this->task->id,
                'group_id'     => $this->task->group_id,
                'action'       => $this->action,
                'reviewer_id'  => $reviewer?->id,
                'review_note'  => $this->task->review_note,
            ],
        ];
    }
}
