<?php

namespace App\Services;

use App\Models\Auth\User;
use App\Models\Sign\LecturerSignProfile;
use App\Models\Sign\SignProfileDeactivationRequest;
use App\Notifications\DeactivationRequestCreated;
use App\Notifications\DeactivationRequestResolved;
use Illuminate\Support\Facades\DB;

class SignProfileDeactivationService
{
    // ─────────────────────────────────────────────
    // GIẢNG VIÊN
    // ─────────────────────────────────────────────

    /**
     * GV gửi yêu cầu vô hiệu hóa
     */
    public function requestDeactivation(User $lecturer, string $reason): array
    {
        // Tìm profile đang active của GV
        $profile = LecturerSignProfile::where('lecturer_id', $lecturer->id)
            ->where('is_active', true)
            ->first();

        if (!$profile) {
            return $this->error('Bạn chưa có chữ ký số nào đang hoạt động', 404);
        }

        // Check đã có request pending chưa
        $existing = SignProfileDeactivationRequest::where('profile_id', $profile->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return $this->error('Bạn đã có yêu cầu vô hiệu hóa đang chờ duyệt', 409);
        }

        try {
            $request = DB::transaction(function () use ($lecturer, $profile, $reason) {
                // Tạo request
                $req = SignProfileDeactivationRequest::create([
                    'lecturer_id' => $lecturer->id,
                    'profile_id'  => $profile->id,
                    'reason'      => $reason,
                    'status'      => 'pending',
                ]);

                // Đánh dấu profile đang chờ vô hiệu → không ký được
                $profile->update(['pending_deactivation' => true]);

                return $req;
            });

            // Notify tất cả admin
            $this->notifyAdmins($request);

            return $this->success('Đã gửi yêu cầu vô hiệu hóa. Vui lòng chờ Admin duyệt.', [
                'request' => $this->formatRequest($request->fresh(['lecturer', 'profile'])),
            ]);
        } catch (\Exception $e) {
            \Log::error('Request deactivation failed: ' . $e->getMessage());
            return $this->error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lấy danh sách request của 1 GV
     */
    public function getRequestsByLecturer(User $lecturer): array
    {
        $requests = SignProfileDeactivationRequest::with(['profile', 'admin:id,name'])
            ->where('lecturer_id', $lecturer->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($r) => $this->formatRequest($r));

        return $this->success('Danh sách yêu cầu', ['requests' => $requests]);
    }

    /**
     * Lấy request pending hiện tại của GV (nếu có)
     */
    public function getCurrentPendingRequest(User $lecturer): ?array
    {
        $request = SignProfileDeactivationRequest::with(['profile'])
            ->where('lecturer_id', $lecturer->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        return $request ? $this->formatRequest($request) : null;
    }

    // ─────────────────────────────────────────────
    // ADMIN
    // ─────────────────────────────────────────────

    /**
     * Admin lấy danh sách tất cả request (có filter)
     */
    public function getAllRequests(array $filters = [], int $perPage = 20): array
    {
        $query = SignProfileDeactivationRequest::with([
            'lecturer:id,name,code,email,avatar',
            'profile',
            'admin:id,name',
        ]);

        // Filter status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter theo tên/code GV
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('lecturer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->success('Danh sách yêu cầu', [
            'requests'   => collect($paginator->items())->map(fn($r) => $this->formatRequest($r)),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * Admin xem chi tiết 1 request
     */
    public function getRequestDetail(int $requestId): array
    {
        $request = SignProfileDeactivationRequest::with([
            'lecturer:id,name,code,email,avatar',
            'profile',
            'admin:id,name',
        ])->find($requestId);

        if (!$request) {
            return $this->error('Yêu cầu không tồn tại', 404);
        }

        return $this->success('Chi tiết yêu cầu', [
            'request' => $this->formatRequest($request),
        ]);
    }

    /**
     * Admin chấp thuận
     */
    public function approveRequest(User $admin, int $requestId): array
    {
        $request = SignProfileDeactivationRequest::with('profile')->find($requestId);

        if (!$request) {
            return $this->error('Yêu cầu không tồn tại', 404);
        }

        if ($request->status !== 'pending') {
            return $this->error('Yêu cầu đã được xử lý', 409);
        }

        try {
            DB::transaction(function () use ($admin, $request) {
                // Update request
                $request->update([
                    'status'      => 'approved',
                    'admin_id'    => $admin->id,
                    'resolved_at' => now(),
                ]);

                // Vô hiệu hóa profile
                $request->profile->update([
                    'is_active'             => false,
                    'pending_deactivation'  => false,
                ]);
            });

            // Notify GV
            $request->lecturer->notify(new DeactivationRequestResolved($request->fresh()));

            return $this->success('Đã chấp thuận vô hiệu hóa chữ ký số');
        } catch (\Exception $e) {
            \Log::error('Approve deactivation failed: ' . $e->getMessage());
            return $this->error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Admin từ chối
     */
    public function rejectRequest(User $admin, int $requestId, string $note): array
    {
        $request = SignProfileDeactivationRequest::with('profile')->find($requestId);

        if (!$request) {
            return $this->error('Yêu cầu không tồn tại', 404);
        }

        if ($request->status !== 'pending') {
            return $this->error('Yêu cầu đã được xử lý', 409);
        }

        try {
            DB::transaction(function () use ($admin, $request, $note) {
                // Update request
                $request->update([
                    'status'      => 'rejected',
                    'admin_id'    => $admin->id,
                    'admin_note'  => $note,
                    'resolved_at' => now(),
                ]);

                // Profile trở lại bình thường (có thể ký tiếp)
                $request->profile->update(['pending_deactivation' => false]);
            });

            // Notify GV
            $request->lecturer->notify(new DeactivationRequestResolved($request->fresh()));

            return $this->success('Đã từ chối yêu cầu vô hiệu hóa');
        } catch (\Exception $e) {
            \Log::error('Reject deactivation failed: ' . $e->getMessage());
            return $this->error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Admin xem danh sách tất cả chữ ký số (có filter)
     */
    public function getAllSignProfiles(array $filters = [], int $perPage = 20): array
    {
        $query = LecturerSignProfile::with([
            'lecturer:id,name,code,email,avatar',
            'pendingDeactivationRequest:id,profile_id,reason,created_at',
        ]);

        // Filter theo tên/code GV
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('lecturer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter trạng thái
        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'active':
                    $query->where('is_active', true)
                          ->where('pending_deactivation', false);
                    break;
                case 'pending_deactivation':
                    $query->where('pending_deactivation', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'expired':
                    $query->where('is_active', true)
                          ->where('cert_valid_to', '<', now());
                    break;
            }
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->success('Danh sách chữ ký số', [
            'profiles'   => collect($paginator->items())->map(fn($p) => $this->formatProfile($p)),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * Admin thống kê chữ ký số
     */
    public function getSignProfilesStats(): array
    {
        $total              = LecturerSignProfile::count();
        $active             = LecturerSignProfile::where('is_active', true)
                                                 ->where('pending_deactivation', false)->count();
        $pendingDeactivation= LecturerSignProfile::where('pending_deactivation', true)->count();
        $inactive           = LecturerSignProfile::where('is_active', false)->count();
        $expired            = LecturerSignProfile::where('is_active', true)
                                                 ->where('cert_valid_to', '<', now())->count();

        $pendingRequests    = SignProfileDeactivationRequest::where('status', 'pending')->count();

        return $this->success('Thống kê', [
            'stats' => [
                'total'                 => $total,
                'active'                => $active,
                'pending_deactivation'  => $pendingDeactivation,
                'inactive'              => $inactive,
                'expired'               => $expired,
                'pending_requests'      => $pendingRequests,
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    private function notifyAdmins(SignProfileDeactivationRequest $request): void
    {
        try {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new DeactivationRequestCreated($request));
            }
        } catch (\Exception $e) {
            \Log::error('Notify admins failed: ' . $e->getMessage());
        }
    }

    private function formatRequest(SignProfileDeactivationRequest $r): array
    {
        return [
            'id'          => $r->id,
            'lecturer'    => $r->lecturer ? [
                'id'         => $r->lecturer->id,
                'name'       => $r->lecturer->name,
                'code'       => $r->lecturer->code,
                'email'      => $r->lecturer->email,
                'avatar_url' => $r->lecturer->avatar_url ?? null,
            ] : null,
            'profile'     => $r->profile ? [
                'id'             => $r->profile->id,
                'subject_cn'     => $r->profile->subject_cn,
                'serial_number'  => $r->profile->serial_number,
                'cert_valid_to'  => $r->profile->cert_valid_to,
            ] : null,
            'reason'      => $r->reason,
            'status'      => $r->status,
            'admin'       => $r->admin ? ['id' => $r->admin->id, 'name' => $r->admin->name] : null,
            'admin_note'  => $r->admin_note,
            'resolved_at' => $r->resolved_at,
            'created_at'  => $r->created_at,
        ];
    }

    private function formatProfile(LecturerSignProfile $p): array
    {
        // Tính trạng thái hiển thị
        $status = 'active';
        if (!$p->is_active) {
            $status = 'inactive';
        } elseif ($p->pending_deactivation) {
            $status = 'pending_deactivation';
        } elseif ($p->cert_valid_to && $p->cert_valid_to->lt(now())) {
            $status = 'expired';
        }

        return [
            'id'                  => $p->id,
            'lecturer'            => $p->lecturer ? [
                'id'         => $p->lecturer->id,
                'name'       => $p->lecturer->name,
                'code'       => $p->lecturer->code,
                'email'      => $p->lecturer->email,
                'avatar_url' => $p->lecturer->avatar_url ?? null,
            ] : null,
            'subject_cn'          => $p->subject_cn,
            'issuer_cn'           => $p->issuer_cn,
            'serial_number'       => $p->serial_number,
            'algorithm'           => $p->algorithm,
            'cert_valid_from'     => $p->cert_valid_from,
            'cert_valid_to'       => $p->cert_valid_to,
            'is_active'           => $p->is_active,
            'pending_deactivation'=> $p->pending_deactivation,
            'status'              => $status,
            'pending_request'     => $p->pendingDeactivationRequest ? [
                'id'         => $p->pendingDeactivationRequest->id,
                'reason'     => $p->pendingDeactivationRequest->reason,
                'created_at' => $p->pendingDeactivationRequest->created_at,
            ] : null,
            'created_at'          => $p->created_at,
        ];
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