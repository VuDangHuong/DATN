<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Channels\CustomDatabaseChannel;
use App\Models\Task\Task;
class TaskSubmittedForReview extends Notification
{
    use Queueable;
 
    public function __construct(public Task $task) {}
 
    public function via($notifiable): array
    {
        return ['mail',CustomDatabaseChannel::class];   // Chỉ in-app, không email
    }
    public function toMail($notifiable): MailMessage
    {
        $assignee  = $this->task->assignee;
        $frontend  = config('app.frontend_url');
        $taskLink  = "{$frontend}";
        $deadline  = $this->task->deadline?->format('d/m/Y H:i') ?? 'Không có';
 
        return (new MailMessage)
            ->subject('[EduGroup] ' . ($assignee?->name ?? 'Thành viên') . ' báo hoàn thành công việc')
            ->greeting("Chào {$notifiable->name},")
            ->line("**{$assignee?->name}** vừa báo hoàn thành công việc trong nhóm và cần bạn xác nhận.")
            ->line("📋 **Công việc:** {$this->task->title}")
            ->line("⏰ **Hạn cuối:** {$deadline}")
            ->when($this->task->submission_note, function ($mail) {
                return $mail->line("💬 **Ghi chú từ thành viên:**")
                            ->line($this->task->submission_note);
            })
            ->action('Xem & Duyệt công việc', $taskLink)
            ->line('Vui lòng kiểm tra và duyệt/từ chối yêu cầu này.');
    }
    public function toCustomDatabase($notifiable): array
    {
        $assignee = $this->task->assignee;
 
        return [
            'type'     => 'task_submitted_for_review',
            'title'    => "{$assignee?->name} báo hoàn thành công việc",
            'body'     => mb_strimwidth($this->task->title, 0, 100, '…'),
            'link'     => "/student/groups/{$this->task->group_id}/tasks?task=" . $this->task->id,
            'metadata' => [
                'task_id'     => $this->task->id,
                'group_id'    => $this->task->group_id,
                'assignee_id' => $assignee?->id,
                'note'        => $this->task->submission_note,
            ],
        ];
    }
}
