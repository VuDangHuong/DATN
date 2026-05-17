<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Academic\Classes;
use App\Models\Academic\Semester;
use App\Models\Academic\Subject;
use App\Models\Auth\User;
use App\Models\Communication\Group;
use App\Models\Evaluation\Assignment;
use App\Models\Evaluation\Submission;
use App\Models\Sign\DocumentSignRequest;
use App\Models\Sign\LecturerSignProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
class AdminDashboardController extends Controller
{
    /**
     * GET /api/admin/dashboard
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'stats'              => $this->buildStats(),
            'charts'             => $this->buildCharts(),
            'recent_users'       => $this->getRecentUsers(),
            'recent_classes'     => $this->getRecentClasses(),
            'recent_activities'  => $this->buildRecentActivities(),
            'top_lecturers'      => $this->getTopLecturers(),
        ]);
    }
 
    // ─── Stats cards ─────────────────────────
    private function buildStats(): array
    {
        return [
            // Users
            'total_users'      => User::count(),
            'total_admins'     => User::where('role', 'admin')->count(),
            'total_lecturers'  => User::where('role', 'lecturer')->count(),
            'total_students'   => User::where('role', 'student')->count(),
            'active_users'     => User::where('is_active', true)->count(),
 
            // Academic
            'total_classes'    => Classes::count(),
            'active_classes'   => Classes::where('is_active', true)->count(),
            'total_subjects'   => Subject::count(),
            'total_semesters'  => Semester::count(),
            'total_groups'     => Group::count(),
 
            // Assignments + Submissions
            'total_assignments'    => Assignment::count(),
            'total_submissions'    => Submission::count(),
            'submissions_pending'  => Submission::where('status', 'pending')->count(),
 
            // Sign module
            'total_sign_requests'   => DocumentSignRequest::count(),
            'sign_requests_pending' => DocumentSignRequest::whereIn('status', ['pending', 'lecturer_reviewing'])->count(),
            'sign_requests_signed'  => DocumentSignRequest::where('status', 'signed')->count(),
            'lecturers_with_pki'    => LecturerSignProfile::where('is_active', true)->count(),
 
            // Today
            'new_users_today'       => User::whereDate('created_at', today())->count(),
            'submissions_today'     => Submission::whereDate('created_at', today())->count(),
            'sign_requests_today'   => DocumentSignRequest::whereDate('created_at', today())->count(),
        ];
    }
 
    // ─── Charts ──────────────────────────────
    private function buildCharts(): array
    {
        return [
            'users_growth_12months'  => $this->usersGrowth12Months(),
            'system_activity_30days' => $this->systemActivity30Days(),
            'users_by_role'          => $this->usersByRole(),
            'sign_requests_status'   => $this->signRequestsStatus(),
            'submissions_status'     => $this->submissionsStatus(),
            'top_classes_by_groups'  => $this->topClassesByGroups(),
        ];
    }
 
    private function usersGrowth12Months(): array
    {
        $startDate = Carbon::today()->startOfMonth()->subMonths(11);
 
        // Tổng tích lũy users theo tháng
        $rows = User::where('created_at', '>=', $startDate)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();
 
        // Build 12 tháng liên tục
        $labels = [];
        $newUsers = [];
 
        for ($i = 11; $i >= 0; $i--) {
            $d = Carbon::today()->startOfMonth()->subMonths($i);
            $key = $d->format('Y-m');
            $labels[]   = $d->format('m/Y');
            $newUsers[] = (int) ($rows[$key] ?? 0);
        }
 
        return [
            'labels'   => $labels,
            'datasets' => [
                ['name' => 'User mới', 'data' => $newUsers, 'color' => '#0d9488'],
            ],
        ];
    }
 
    private function systemActivity30Days(): array
    {
        $startDate = Carbon::today()->subDays(29);
 
        $submissions = Submission::whereDate('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')->pluck('count', 'date')->toArray();
 
        $signs = DocumentSignRequest::whereDate('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')->pluck('count', 'date')->toArray();
 
        $labels = [];
        $subArr = [];
        $signArr = [];
 
        for ($i = 29; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $key = $d->toDateString();
            $labels[]  = $d->format('d/m');
            $subArr[]  = (int) ($submissions[$key] ?? 0);
            $signArr[] = (int) ($signs[$key] ?? 0);
        }
 
        return [
            'labels'   => $labels,
            'datasets' => [
                ['name' => 'Bài nộp',   'data' => $subArr,  'color' => '#3b82f6'],
                ['name' => 'Yêu cầu ký','data' => $signArr, 'color' => '#10b981'],
            ],
        ];
    }
 
    private function usersByRole(): array
    {
        $counts = User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')->pluck('count', 'role')->toArray();
 
        return [
            ['label' => 'Admin',     'value' => (int) ($counts['admin'] ?? 0),    'color' => '#ef4444'],
            ['label' => 'Giảng viên', 'value' => (int) ($counts['lecturer'] ?? 0), 'color' => '#0d9488'],
            ['label' => 'Sinh viên',  'value' => (int) ($counts['student'] ?? 0),  'color' => '#3b82f6'],
        ];
    }
 
    private function signRequestsStatus(): array
    {
        $counts = DocumentSignRequest::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->pluck('count', 'status')->toArray();
 
        return [
            ['label' => 'Chờ xử lý', 'value' => (int) ($counts['pending'] ?? 0),            'color' => '#f59e0b'],
            ['label' => 'Đang xem',  'value' => (int) ($counts['lecturer_reviewing'] ?? 0), 'color' => '#3b82f6'],
            ['label' => 'Đã ký',     'value' => (int) ($counts['signed'] ?? 0),             'color' => '#10b981'],
            ['label' => 'Từ chối',   'value' => (int) ($counts['rejected'] ?? 0),           'color' => '#ef4444'],
        ];
    }
 
    private function submissionsStatus(): array
    {
        $counts = Submission::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->pluck('count', 'status')->toArray();
 
        return [
            ['label' => 'Chờ duyệt', 'value' => (int) ($counts['pending'] ?? 0),  'color' => '#f59e0b'],
            ['label' => 'Đã duyệt',  'value' => (int) ($counts['approved'] ?? 0), 'color' => '#10b981'],
            ['label' => 'Từ chối',   'value' => (int) ($counts['rejected'] ?? 0), 'color' => '#ef4444'],
        ];
    }
 
    private function topClassesByGroups(): array
    {
        $classes = Classes::withCount('groups')
            ->orderByDesc('groups_count')
            ->limit(5)
            ->get(['id', 'code', 'name']);
 
        return $classes->map(fn($c) => [
            'label' => $c->code,
            'value' => (int) $c->groups_count,
        ])->toArray();
    }
 
    // ─── Recent users (5 mới nhất) ──────────
    private function getRecentUsers()
    {
        return User::orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'code', 'email', 'role', 'avatar', 'created_at']);
    }
 
    // ─── Recent classes ─────────────────────
    private function getRecentClasses()
    {
        return Classes::with('lecturer:id,name,code,avatar')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'code', 'name', 'lecturer_id', 'is_active', 'created_at'])
            ->map(fn($c) => [
                'id'         => $c->id,
                'code'       => $c->code,
                'name'       => $c->name,
                'lecturer'   => $c->lecturer,
                'is_active'  => $c->is_active,
                'created_at' => $c->created_at,
            ]);
    }
 
    // ─── Activity feed ───────────────────────
    private function buildRecentActivities(): array
    {
        // User mới
        $users = User::orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'code', 'role', 'created_at'])
            ->map(fn($u) => [
                'type'     => 'new_user',
                'icon'     => $u->role === 'lecturer' ? '👨‍🏫' : ($u->role === 'admin' ? '👑' : '👨‍🎓'),
                'title'    => "{$u->name} đã đăng ký",
                'subtitle' => ucfirst($u->role) . ' · ' . ($u->code ?? '—'),
                'time'     => $u->created_at,
                'link'     => "/admin/users/{$u->id}",
            ]);
 
        // Class mới
        $classes = Classes::with('lecturer:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'type'     => 'new_class',
                'icon'     => '🎓',
                'title'    => "Lớp {$c->code} được tạo",
                'subtitle' => "Phụ trách: " . ($c->lecturer?->name ?? 'Chưa gán'),
                'time'     => $c->created_at,
                'link'     => "/admin/classes/{$c->id}",
            ]);
 
        // Sign requests đã ký gần đây
        $signs = DocumentSignRequest::with('lecturer:id,name', 'requester:id,name')
            ->where('status', 'signed')
            ->orderBy('signed_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'type'     => 'sign_request',
                'icon'     => '✅',
                'title'    => "{$r->lecturer?->name} đã ký tài liệu",
                'subtitle' => "Cho: " . ($r->requester?->name ?? '—'),
                'time'     => $r->signed_at,
                'link'     => "/admin/sign-requests/{$r->id}",
            ]);
 
        return collect()
            ->concat($users)
            ->concat($classes)
            ->concat($signs)
            ->sortByDesc('time')
            ->take(20)
            ->values()
            ->toArray();
    }
 
    // ─── Top lecturers by activity ──────────
    private function getTopLecturers()
    {
        // Top 5 GV có nhiều sign requests đã ký nhất
        return User::where('role', 'lecturer')
            ->withCount(['signRequestsAsLecturer as signed_count' => function ($q) {
                $q->where('status', 'signed');
            }])
            ->withCount(['classes as classes_count'])
            ->orderByDesc('signed_count')
            ->limit(5)
            ->get(['id', 'name', 'code', 'avatar'])
            ->map(fn($u) => [
                'id'            => $u->id,
                'name'          => $u->name,
                'code'          => $u->code,
                'avatar'        => $u->avatar,
                'avatar_url'    => $u->avatar_url ?? null,
                'classes_count' => $u->classes_count ?? 0,
                'signed_count'  => $u->signed_count ?? 0,
            ]);
    }
}
