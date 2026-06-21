<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
class MessageController extends Controller
{
    public function __construct(private readonly MessageService $service) {}
 
    /**
     * GET /student/groups/{groupId}/messages?page=1&per_page=30
     *
     * Lấy tin nhắn trong nhóm (phân trang, mới nhất trước).
     * Chỉ thành viên nhóm mới xem được.
     */
    public function index(Request $request, int $groupId): JsonResponse
    {
        $groupId = (int) $groupId;
        $perPage = $request->integer('per_page', 30);
 
        $result = $this->service->getMessages(auth()->user(), $groupId, $perPage);
 
        return $this->toResponse($result);
    }
 
    /**
     * POST /student/groups/{groupId}/messages
     *
     * Body: { "content": "Hello nhóm!" }
     *
     * Gửi tin nhắn vào nhóm. Chỉ thành viên nhóm mới gửi được.
     */
    public function store(Request $request, int $groupId): JsonResponse
    {
        $groupId = (int) $groupId;
        $data = $request->validate([
            'content'      => 'nullable|string|max:5000',
            'mentions'     => 'nullable|array|max:20',
            'mentions.*'   => 'integer|exists:users,id',
            'files'        => 'nullable|array|max:5',
            'files.*'      => [
                'file',
                'max:20480',          // 20MB
                'mimes:jpg,jpeg,png,gif,webp,pdf',  //Chỉ image + PDF
            ],
        ], [
            'files.max'      => 'Tối đa 5 file mỗi tin nhắn',
            'files.*.max'    => 'Mỗi file không vượt quá 20MB',
            'files.*.mimes'  => 'Chỉ cho phép image (JPG/PNG/GIF/WEBP) và PDF',
        ]);
 
        // Phải có content HOẶC file
        if (empty($data['content']) && empty($request->file('files'))) {
            return response()->json([
                'message' => 'Tin nhắn phải có nội dung hoặc file đính kèm',
            ], 422);
        }
 
        $result = $this->service->sendMessage(
            auth()->user(),
            $groupId,
            $data['content'] ?? '',
            $data['mentions'] ?? [],
            $request->file('files', []),
        );
 
        return $this->toResponse($result, 201);
    }
 
    // ─────────────────────────────────────────────
    public function destroy(int $messageId): JsonResponse
    {
        $result = $this->service->deleteMessage(auth()->user(), $messageId);
        return $this->toResponse($result);
    }
 
    /**
     * DELETE /student/messages/attachments/{attachmentId}
     */
    public function deleteAttachment(int $attachmentId): JsonResponse
    {
        $result = $this->service->deleteAttachment(auth()->user(), $attachmentId);
        return $this->toResponse($result);
    }
    
    private function toResponse(array $result, int $successCode = 200): JsonResponse
    {
        if ($result['status'] === 'error') {
            return response()->json(['message' => $result['message']], $result['code']);
        }
 
        return response()->json([
            'message' => $result['message'],
            ...$result['data'],
        ], $successCode);
    }
}
