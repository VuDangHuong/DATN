<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SignProfileDeactivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class AdminSignProfileController extends Controller
{
    public function __construct(
        private readonly SignProfileDeactivationService $service
    ) {}
 
    /**
     * GET /api/admin/sign-profiles
     *
     * Query: ?status=active|pending_deactivation|inactive|expired
     *        ?search=<tên hoặc code GV>
     *        ?per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'search']);
        $perPage = $request->integer('per_page', 20);
 
        $result = $this->service->getAllSignProfiles($filters, $perPage);
        return $this->toResponse($result);
    }
 
    /**
     * GET /api/admin/sign-profiles/stats
     */
    public function stats(): JsonResponse
    {
        $result = $this->service->getSignProfilesStats();
        return $this->toResponse($result);
    }
 
    // ─────────────────────────────────────────────
    // Deactivation requests
    // ─────────────────────────────────────────────
 
    /**
     * GET /api/admin/deactivation-requests
     *
     * Danh sách yêu cầu vô hiệu hóa
     */
    public function listRequests(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'search']);
        $perPage = $request->integer('per_page', 20);
 
        $result = $this->service->getAllRequests($filters, $perPage);
        return $this->toResponse($result);
    }
 
    /**
     * GET /api/admin/deactivation-requests/{id}
     */
    public function showRequest(int $id): JsonResponse
    {
        $result = $this->service->getRequestDetail($id);
        return $this->toResponse($result);
    }
 
    /**
     * POST /api/admin/deactivation-requests/{id}/approve
     */
    public function approve(int $id): JsonResponse
    {
        $result = $this->service->approveRequest(auth()->user(), $id);
        return $this->toResponse($result);
    }
 
    /**
     * POST /api/admin/deactivation-requests/{id}/reject
     * Body: { "note": "Lý do từ chối" }
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'note' => 'required|string|min:5|max:1000',
        ], [
            'note.required' => 'Vui lòng nhập lý do từ chối',
        ]);
 
        $result = $this->service->rejectRequest(auth()->user(), $id, $data['note']);
        return $this->toResponse($result);
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
