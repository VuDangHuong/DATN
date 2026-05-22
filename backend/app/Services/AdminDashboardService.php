<?php
// app/Services/AdminDashboardService.php

namespace App\Services;

use App\Models\Auth\User;
use App\Models\Academic\Classes;
use App\Models\Communication\Group;
use App\Models\Evaluation\Assignment;
use App\Models\Evaluation\Submission;
use App\Models\Sign\LecturerSignProfile;
use App\Models\Sign\DocumentSignRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDashboardService
{
    public function getDashboard(): array
    {
        return [
            'stats'              => $this->getStats(),
            'charts'             => $this->getCharts(),
            'recent_users'       => $this->getRecentUsers(),
            'recent_classes'     => $this->getRecentClasses(),
            'top_lecturers'      => $this->getTopLecturers(),
            'recent_activities'  => $this->getRecentActivities(),
        ];
    }

    // ─────────────────────────────────────────────
    // STATS (cards)
    // ─────────────────────────────────────────────
    private function getStats(): array
    {
        $todayStart = Carbon::today();

        return [
            // Users section
            'total_users'          => User::count(),
            'total_admins'         => User::where('role', 'admin')->count(),
            'total_lecturers'      => User::where('role', 'lecturer')->count(),
            'total_students'       => User::where('role', 'student')->count(),
            'active_users'         => Schema::hasColumn('users', 'is_active')
                                        ? User::where('is_active', true)->count()
                                        : User::count(),
            'new_users_today'      => User::where('created_at', '>=', $todayStart)->count(),

            // Academic section
            'total_classes'        => Classes::count(),
            'active_classes'       => Schema::hasColumn('classes', 'is_active')
                                        ? Classes::where('is_active', true)->count()
                                        : Classes::count(),
            'total_subjects'       => $this->safeCount('subjects'),
            'total_semesters'      => $this->safeCount('semesters'),
            'total_groups'         => Group::count(),

            // Activity section
            'total_assignments'    => $this->safeCount('assignments'),
            'total_submissions'    => Submission::count(),
            'submissions_pending'  => Submission::where('status', 'submitted')->count(),
            'total_sign_requests'  => class_exists(DocumentSignRequest::class)
                                        ? DocumentSignRequest::count() : 0,
            'sign_requests_today'  => class_exists(DocumentSignRequest::class)
                                        ? DocumentSignRequest::where('created_at', '>=', $todayStart)->count()
                                        : 0,
            'lecturers_with_pki'   => LecturerSignProfile::where('is_active', true)->distinct('lecturer_id')->count('lecturer_id'),
        ];
    }

    // ─────────────────────────────────────────────
    // CHARTS
    // ─────────────────────────────────────────────
    private function getCharts(): array
    {
        return [
            'users_growth_12months'  => $this->getUsersGrowth12Months(),
            'system_activity_30days' => $this->getSystemActivity30Days(),
            'users_by_role'          => $this->getUsersByRole(),
            'sign_requests_status'   => $this->getSignRequestsStatus(),
            'submissions_status'     => $this->getSubmissionsStatus(),
            'top_classes_by_groups'  => $this->getTopClassesByGroups(),
        ];
    }

    private function getUsersGrowth12Months(): array
    {
        $start = Carbon::now()->subMonths(11)->startOfMonth();

        $raw = User::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw("COUNT(*) as count")
            )
            ->where('created_at', '>=', $start)
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $labels = [];
        $values = [];
        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            $labels[] = $month->format('m/Y');
            $values[] = $raw[$month->format('Y-m')] ?? 0;
        }

        return [
            'labels'   => $labels,
            'datasets' => [[
                'label' => 'User mới',
                'data'  => $values,
                'color' => '#6366f1',
            ]],
        ];
    }

    private function getSystemActivity30Days(): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();

        $submissions = Submission::select(
                DB::raw("DATE(created_at) as date"),
                DB::raw("COUNT(*) as count")
            )
            ->where('created_at', '>=', $start)
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $signRequests = [];
        if (class_exists(DocumentSignRequest::class)) {
            $signRequests = DocumentSignRequest::select(
                    DB::raw("DATE(created_at) as date"),
                    DB::raw("COUNT(*) as count")
                )
                ->where('created_at', '>=', $start)
                ->groupBy('date')
                ->pluck('count', 'date')
                ->toArray();
        }

        $labels = [];
        $subData = [];
        $signData = [];

        for ($i = 0; $i < 30; $i++) {
            $date = $start->copy()->addDays($i);
            $key  = $date->format('Y-m-d');
            $labels[]   = $date->format('d/m');
            $subData[]  = $submissions[$key] ?? 0;
            $signData[] = $signRequests[$key] ?? 0;
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                ['label' => 'Bài nộp',    'data' => $subData,  'color' => '#10b981'],
                ['label' => 'Yêu cầu ký', 'data' => $signData, 'color' => '#f59e0b'],
            ],
        ];
    }

    private function getUsersByRole(): array
    {
        $roles = User::select('role', DB::raw('COUNT(*) as count'))
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        return [
            'labels' => ['Admin', 'Giảng viên', 'Sinh viên'],
            'values' => [
                $roles['admin']    ?? 0,
                $roles['lecturer'] ?? 0,
                $roles['student']  ?? 0,
            ],
            'colors' => ['#ef4444', '#14b8a6', '#3b82f6'],
        ];
    }

    private function getSignRequestsStatus(): array
    {
        $active   = LecturerSignProfile::where('is_active', true)
                        ->where('pending_deactivation', false)->count();
        $pending  = LecturerSignProfile::where('pending_deactivation', true)->count();
        $inactive = LecturerSignProfile::where('is_active', false)->count();
        $expired  = LecturerSignProfile::where('is_active', true)
                        ->where('cert_expires_at', '<', now())->count();

        return [
            'labels' => ['Hoạt động', 'Chờ vô hiệu', 'Đã vô hiệu', 'Hết hạn'],
            'values' => [$active, $pending, $inactive, $expired],
            'colors' => ['#10b981', '#f59e0b', '#94a3b8', '#ef4444'],
        ];
    }

    private function getSubmissionsStatus(): array
{
    // ✅ Lấy số lượng theo từng status có thật trong DB
    $raw = Submission::select('status', \DB::raw('COUNT(*) as count'))
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray();
 
    // Debug: log để verify
    \Log::info('Submission statuses found:', $raw);
 
    // ✅ Mapping label tiếng Việt cho status phổ biến
    // Mở rộng nếu DB có status khác
    $labelMap = [
        'pending'    => 'Chờ duyệt',
        'submitted'  => 'Đã nộp',
        'graded'     => 'Đã chấm',
        'returned'   => 'Trả lại',
        'late'       => 'Trễ hạn',
        'approved'   => 'Đã duyệt',
        'rejected'   => 'Từ chối',
        'draft'      => 'Bản nháp',
        'in_review'  => 'Đang chấm',
        'done'       => 'Hoàn thành',
    ];
 
    $colorMap = [
        'pending'    => '#94a3b8',
        'submitted'  => '#3b82f6',
        'graded'     => '#10b981',
        'returned'   => '#f59e0b',
        'late'       => '#ef4444',
        'approved'   => '#10b981',
        'rejected'   => '#ef4444',
        'draft'      => '#94a3b8',
        'in_review'  => '#8b5cf6',
        'done'       => '#10b981',
    ];
 
    $labels = [];
    $values = [];
    $colors = [];
 
    foreach ($raw as $status => $count) {
        $labels[] = $labelMap[$status] ?? ucfirst($status);   // ← Fallback: hiển thị tên status nếu chưa map
        $values[] = $count;
        $colors[] = $colorMap[$status] ?? '#6366f1';
    }
 
    return [
        'labels' => $labels,
        'values' => $values,
        'colors' => $colors,
    ];
}

    private function getTopClassesByGroups(): array
    {
        $list = Classes::select('id', 'code', 'name')
            ->withCount('groups')
            ->orderByDesc('groups_count')
            ->limit(5)
            ->get();

        return [
            'labels' => $list->pluck('code')->toArray(),
            'values' => $list->pluck('groups_count')->toArray(),
            'colors' => ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316'],
        ];
    }

    // ─────────────────────────────────────────────
    // LISTS
    // ─────────────────────────────────────────────
    private function getRecentUsers(int $limit = 5): array
    {
        return User::select('id', 'name', 'email', 'role', 'avatar', 'created_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'role'       => $u->role,
                'avatar_url' => $u->avatar_url ?? null,
                'created_at' => $u->created_at,
            ])
            ->toArray();
    }

    private function getRecentClasses(int $limit = 5): array
    {
        return Classes::select('id', 'code', 'name', 'lecturer_id', 'is_active', 'created_at')
            ->with('lecturer:id,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($c) => [
                'id'         => $c->id,
                'code'       => $c->code,
                'name'       => $c->name,
                'is_active'  => (bool) $c->is_active,
                'lecturer'   => $c->lecturer ? ['id' => $c->lecturer->id, 'name' => $c->lecturer->name] : null,
                'created_at' => $c->created_at,
            ])
            ->toArray();
    }

    private function getTopLecturers(int $limit = 5): array
{
    return User::where('role', 'lecturer')
        ->select('id', 'name', 'email', 'avatar')
        ->withCount([
            'taughtClasses as classes_count',          // ✅ Đúng tên
            'signRequestsAsLecturer as signed_count' => function ($q) {
                $q->where('status', 'signed');         // ✅ Đếm doc đã ký
            },
        ])
        ->orderByDesc('classes_count')
        ->limit($limit)
        ->get()
        ->map(fn($u) => [
            'id'             => $u->id,
            'name'           => $u->name,
            'avatar_url'     => $u->avatar_url ?? null,
            'classes_count'  => $u->classes_count ?? 0,
            'signed_count'   => $u->signed_count ?? 0,
        ])
        ->toArray();
}

    private function getRecentActivities(int $limit = 20): array
    {
        $activities = collect();

        // User mới
        User::orderByDesc('created_at')->limit($limit)->get()
            ->each(fn($u) => $activities->push([
                'title'    => "Người dùng mới: {$u->name}",
                'subtitle' => "Vai trò: {$u->role} · {$u->email}",
                'link'     => "/admin/users",
                'time'     => $u->created_at,
            ]));

        // Lớp mới
        Classes::with('lecturer:id,name')->orderByDesc('created_at')->limit($limit)->get()
            ->each(fn($c) => $activities->push([
                'title'    => "Lớp mới: {$c->name}",
                'subtitle' => "Mã: {$c->code}" . ($c->lecturer ? " · GV: {$c->lecturer->name}" : ''),
                'link'     => "/admin/classes",
                'time'     => $c->created_at,
            ]));

        // Bài nộp mới
        if (Schema::hasTable('submissions')) {
            Submission::with(['student:id,name', 'assignment:id,title'])
                ->orderByDesc('created_at')->limit($limit)->get()
                ->each(fn($s) => $activities->push([
                    'title'    => "Bài nộp mới: " . ($s->assignment->title ?? '—'),
                    'subtitle' => "SV: " . ($s->student->name ?? '—'),
                    'link'     => "/admin/submissions",
                    'time'     => $s->created_at,
                ]));
        }

        // Sort + limit
        return $activities
            ->filter(fn($a) => $a['time'])
            ->sortByDesc('time')
            ->take($limit)
            ->values()
            ->toArray();
    }

    // ─────────────────────────────────────────────
    private function safeCount(string $table): int
    {
        if (!Schema::hasTable($table)) return 0;
        return DB::table($table)->count();
    }
}