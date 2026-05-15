<?php

namespace App\Services;

use App\Models\Auth\User;
use App\Models\Communication\Message;
use App\Models\Group\Group;
use App\Notifications\UserMentionedInMessage;
use App\Models\Communication\MessageAttachment;
use Illuminate\Support\Facades\DB;
class MessageService
{
    public function getMessages(User $user, int $groupId, int $perPage = 30): array
    {
        $group = Group::findOrFail($groupId);
 
        if (!$group->isMember($user->id)) {
            return $this->error('Bạn không phải thành viên của nhóm này', 403);
        }
 
        $messages = Message::where('group_id', $groupId)
            ->with([
                'user:id,code,name,avatar',
                'attachments',                       //Eager load
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
 
        // ✅ Resolve mentioned users (1 query duy nhất)
        $allMentionIds = collect($messages->items())
            ->flatMap(fn ($m) => $m->mentions ?? [])
            ->unique()
            ->values()
            ->all();
 
        $mentionedUsers = User::whereIn('id', $allMentionIds)
            ->get(['id', 'name', 'code', 'avatar'])
            ->keyBy('id');
 
        //Attach mentioned_users info vào mỗi message
        $items = collect($messages->items())->map(function ($m) use ($mentionedUsers) {
            $arr = $m->toArray();
            $arr['mentioned_users'] = collect($m->mentions ?? [])
                ->map(fn ($id) => $mentionedUsers->get($id))
                ->filter()
                ->values()
                ->all();
            return $arr;
        })->all();
 
        return $this->success('Danh sách tin nhắn', [
            'messages'   => $items,
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
                'per_page'     => $messages->perPage(),
                'total'        => $messages->total(),
            ],
        ]);
    }
 
    /**
     *Gửi tin nhắn + upload files + parse mentions + notify
     */
    public function sendMessage(
        User $user,
        int $groupId,
        string $content,
        array $mentionedUserIds = [],
        array $files = []
    ): array {
        $group = Group::findOrFail($groupId);
 
        if (!$group->isMember($user->id)) {
            return $this->error('Bạn không phải thành viên của nhóm này', 403);
        }
 
        //Validate mentioned users phải là member của group
        if (!empty($mentionedUserIds)) {
            $memberIds = $group->members()->pluck('user_id')->push($group->leader_id)->unique();
            $mentionedUserIds = collect($mentionedUserIds)
                ->filter(fn ($id) => $memberIds->contains($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }
 
        try {
            $message = DB::transaction(function () use ($user, $groupId, $content, $mentionedUserIds, $files) {
                $message = Message::create([
                    'group_id' => $groupId,
                    'user_id'  => $user->id,
                    'content'  => $content,
                    'mentions' => !empty($mentionedUserIds) ? $mentionedUserIds : null,
                ]);
 
                // Upload attachments
                foreach ($files as $file) {
                    $this->saveAttachment($message->id, $file, $user->id);
                }
 
                return $message;
            });
 
            //Notify mentioned users — sau transaction (tránh race)
            if (!empty($mentionedUserIds)) {
                $this->notifyMentioned($message, $mentionedUserIds, $user->id);
            }
 
            $message->load(['user:id,code,name,avatar', 'attachments']);
 
            // Build response với mentioned_users
            $mentionedUsers = User::whereIn('id', $mentionedUserIds)
                ->get(['id', 'name', 'code', 'avatar']);
 
            $arr = $message->toArray();
            $arr['mentioned_users'] = $mentionedUsers->toArray();
 
            return $this->success('Gửi tin nhắn thành công', [
                'message' => $arr,
            ]);
        } catch (\Exception $e) {
            \Log::error('Send message failed: ' . $e->getMessage());
            return $this->error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
 
    /**
     *Xóa 1 attachment riêng
     */
    public function deleteAttachment(User $user, int $attachmentId): array
    {
        $attachment = MessageAttachment::with('message')->find($attachmentId);
        if (!$attachment) {
            return $this->error('File không tồn tại', 404);
        }
 
        $isUploader = $attachment->uploaded_by === $user->id;
        $isSender   = $attachment->message?->user_id === $user->id;
 
        if (!$isUploader && !$isSender) {
            return $this->error('Không có quyền xóa', 403);
        }
 
        $attachment->delete();   // event tự xóa file
 
        return $this->success('Đã xóa file đính kèm');
    }
 
    /**
     *Xóa tin nhắn (sender hoặc leader)
     */
    public function deleteMessage(User $user, int $messageId): array
    {
        $message = Message::with('group', 'attachments')->find($messageId);
        if (!$message) return $this->error('Tin nhắn không tồn tại', 404);
 
        $isSender = $message->user_id === $user->id;
        $isLeader = $message->group?->leader_id === $user->id;
 
        if (!$isSender && !$isLeader) {
            return $this->error('Không có quyền xóa', 403);
        }
 
        DB::transaction(function () use ($message) {
            foreach ($message->attachments as $att) {
                $att->delete();
            }
            $message->delete();
        });
 
        return $this->success('Đã xóa tin nhắn');
    }
 
    // ─────────────────────────────────────────────
 
    private function saveAttachment(int $messageId, $file, int $uploaderId): MessageAttachment
    {
        $path = $file->store("messages/{$messageId}", 'public');
 
        return MessageAttachment::create([
            'message_id'  => $messageId,
            'file_path'   => $path,
            'file_name'   => $file->getClientOriginalName(),
            'mime_type'   => $file->getMimeType() ?? 'application/octet-stream',
            'file_size'   => $file->getSize(),
            'uploaded_by' => $uploaderId,
        ]);
    }
 
    private function notifyMentioned(Message $message, array $userIds, int $senderId): void
    {
        // Loại trừ chính người gửi
        $idsToNotify = array_filter($userIds, fn ($id) => $id !== $senderId);
 
        if (empty($idsToNotify)) return;
 
        try {
            $users = User::whereIn('id', $idsToNotify)->get();
            foreach ($users as $u) {
                $u->notify(new UserMentionedInMessage($message));
            }
        } catch (\Exception $e) {
            \Log::error('Mention notification failed: ' . $e->getMessage());
            // Không throw — tin nhắn đã gửi rồi, notification fail không nên rollback
        }
    }
 
    private function success(string $message, array $data = []): array
    {
        return ['status' => 'success', 'message' => $message, 'data' => $data];
    }
 
    private function error(string $message, int $code = 400): array
    {
        return ['status' => 'error', 'message' => $message, 'code' => $code];
    }
}
