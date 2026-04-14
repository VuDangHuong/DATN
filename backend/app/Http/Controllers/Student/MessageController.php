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
        $request->validate([
            'content' => 'required|string|max:5000',
        ]);
 
        $result = $this->service->sendMessage(
            auth()->user(),
            $groupId,
            $request->input('content')
        );
 
        return $this->toResponse($result, 201);
    }
 
    // ─────────────────────────────────────────────
 
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
