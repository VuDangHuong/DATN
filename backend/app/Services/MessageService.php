<?php

namespace App\Services;

use App\Models\Auth\User;
use App\Models\Communication\Message;
use App\Models\Group\Group;

class MessageService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function getMessages(User $user, int $groupId, int $perPage = 30): array
    {
        $group = Group::findOrFail($groupId);
 
        if (!$group->isMember($user->id)) {
            return $this->error('Bạn không phải thành viên của nhóm này', 403);
        }
 
        $messages = Message::where('group_id', $groupId)
            ->with('user:id,code,name,avatar')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
 
        return $this->success('Danh sách tin nhắn', [
            'messages' => $messages->items(),
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
                'per_page'     => $messages->perPage(),
                'total'        => $messages->total(),
            ],
        ]);
    }
 
    /**
     * Gửi tin nhắn vào nhóm.
     *
     * Chỉ thành viên nhóm mới được gửi.
     */
    public function sendMessage(User $user, int $groupId, string $content): array
    {
        $group = Group::findOrFail($groupId);
 
        if (!$group->isMember($user->id)) {
            return $this->error('Bạn không phải thành viên của nhóm này', 403);
        }
 
        $message = Message::create([
            'group_id' => $groupId,
            'user_id'  => $user->id,
            'content'  => $content,
        ]);
 
        $message->load('user:id,code,name,avatar');
 
        return $this->success('Gửi tin nhắn thành công', [
            'message' => [
                'id'         => $message->id,
                'content'    => $message->content,
                'user'       => [
                    'id'   => $message->user->id,
                    'code' => $message->user->code,
                    'name' => $message->user->name,
                ],
                'created_at' => $message->created_at,
            ],
        ]);
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
