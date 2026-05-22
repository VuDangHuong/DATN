<?php

namespace App\Http\Controllers\Lecturers;
use App\Http\Controllers\Controller;
use App\Models\Academic\Classes;
use App\Models\Evaluation\Submission;
use Illuminate\Http\Request;
use App\Models\Auth\User;
use Illuminate\Support\Carbon;
use App\Models\Group\Group;
use App\Models\Sign\DocumentSignRequest;
use Illuminate\Http\JsonResponse;
class LecturerDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $lecturerId = auth()->id();
        $classIds   = Classes::where('lecturer_id', $lecturerId)->pluck('id');
 
        return response()->json([
            'stats'                 => $this->buildStats($lecturerId, $classIds),
            'charts'                => $this->buildCharts($lecturerId, $classIds),
            'pending_submissions'   => $this->getPendingSubmissions($classIds),
            'pending_sign_requests' => $this->getPendingSignRequests($lecturerId),
            'recent_activities'     => $this->buildRecentActivities($lecturerId, $classIds),
        ]);
    }
 
    // ─── Stats ───────────────────────────────
    private function buildStats(int $lecturerId, $classIds): array
    {
        return [
            'classes_count'  => $classIds->count(),

            //Đếm SV qua bảng pivot class_students thay vì User::classes()
            'students_count' => \DB::table('class_students')
                ->whereIn('class_id', $classIds)
                ->distinct('student_id')
                ->count('student_id'),

            'groups_count'   => Group::whereIn('class_id', $classIds)->count(),

            'submissions_pending' => Submission::whereHas('assignment', fn($q) =>
                $q->whereIn('class_id', $classIds))->where('status', 'pending')->count(),

            'sign_requests_pending' => DocumentSignRequest::where('lecturer_id', $lecturerId)
                ->whereIn('status', ['pending', 'lecturer_reviewing'])->count(),

            'sign_requests_signed_today' => DocumentSignRequest::where('lecturer_id', $lecturerId)
                ->where('status', 'signed')->whereDate('signed_at', today())->count(),
        ];
    }
 
    // ─── Charts ──────────────────────────────
    private function buildCharts(int $lecturerId, $classIds): array
    {
        return [
            'sign_activity_7days'  => $this->signActivityLast7Days($lecturerId),
            'sign_requests_status' => $this->signRequestsStatusDistribution($lecturerId),
            'submissions_status'   => $this->submissionsStatusDistribution($classIds),
            'submissions_by_class' => $this->submissionsByClass($classIds),
        ];
    }
 
    private function signActivityLast7Days(int $lecturerId): array
    {
        $startDate = Carbon::today()->subDays(6);
 
        $signed = DocumentSignRequest::where('lecturer_id', $lecturerId)
            ->where('status', 'signed')
            ->whereDate('signed_at', '>=', $startDate)
            ->selectRaw('DATE(signed_at) as date, COUNT(*) as count')
            ->groupBy('date')->pluck('count', 'date')->toArray();
 
        $rejected = DocumentSignRequest::where('lecturer_id', $lecturerId)
            ->where('status', 'rejected')
            ->whereDate('updated_at', '>=', $startDate)
            ->selectRaw('DATE(updated_at) as date, COUNT(*) as count')
            ->groupBy('date')->pluck('count', 'date')->toArray();
 
        $labels = [];
        $signedArr = [];
        $rejectedArr = [];
 
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $key = $d->toDateString();
            $labels[]      = $d->format('d/m');
            $signedArr[]   = (int) ($signed[$key]   ?? 0);
            $rejectedArr[] = (int) ($rejected[$key] ?? 0);
        }
 
        return [
            'labels'   => $labels,
            'datasets' => [
                ['name' => 'Đã ký',   'data' => $signedArr,   'color' => '#10b981'],
                ['name' => 'Từ chối', 'data' => $rejectedArr, 'color' => '#ef4444'],
            ],
        ];
    }
 
    private function signRequestsStatusDistribution(int $lecturerId): array
    {
        $counts = DocumentSignRequest::where('lecturer_id', $lecturerId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->pluck('count', 'status')->toArray();
 
        return [
            ['label' => 'Chờ xử lý', 'value' => (int) ($counts['pending'] ?? 0),            'color' => '#f59e0b'],
            ['label' => 'Đang xem',  'value' => (int) ($counts['lecturer_reviewing'] ?? 0), 'color' => '#3b82f6'],
            ['label' => 'Đã ký',     'value' => (int) ($counts['signed'] ?? 0),             'color' => '#10b981'],
            ['label' => 'Từ chối',   'value' => (int) ($counts['rejected'] ?? 0),           'color' => '#ef4444'],
        ];
    }
 
    private function submissionsStatusDistribution($classIds): array
    {
        $counts = Submission::whereHas('assignment', fn($q) => $q->whereIn('class_id', $classIds))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->pluck('count', 'status')->toArray();
 
        return [
            ['label' => 'Chờ duyệt', 'value' => (int) ($counts['pending'] ?? 0),  'color' => '#f59e0b'],
            ['label' => 'Đã duyệt',  'value' => (int) ($counts['approved'] ?? 0), 'color' => '#10b981'],
            ['label' => 'Từ chối',   'value' => (int) ($counts['rejected'] ?? 0), 'color' => '#ef4444'],
        ];
    }
 
    private function submissionsByClass($classIds): array
    {
        $classes = Classes::whereIn('id', $classIds)->get(['id', 'code', 'name']);
 
        return $classes->map(function ($c) {
            $count = Submission::whereHas('assignment', fn($q) => $q->where('class_id', $c->id))->count();
            return [
                'label' => $c->code,
                'value' => $count,
            ];
        })->toArray();
    }
 
    // ─── Pending lists ───────────────────────
    private function getPendingSubmissions($classIds)
    {
        return Submission::with([
                'assignment:id,title,class_id',
                'assignment.class:id,name,code',
                'student:id,name,code,avatar',
                'group:id,name',
            ])
            ->whereHas('assignment', fn($q) => $q->whereIn('class_id', $classIds))
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($s) => [
                'id'              => $s->id,
                'assignment_id'   => $s->assignment_id,
                'assignment_title'=> $s->assignment?->title,
                'class_name'      => $s->assignment?->class?->name,
                'submitter_name'  => $s->group?->name ?? $s->student?->name,
                'submitter_type'  => $s->group_id ? 'group' : 'individual',
                'submitted_at'    => $s->created_at,
            ]);
    }
 
    private function getPendingSignRequests(int $lecturerId)
    {
        return DocumentSignRequest::with('requester:id,name,code,avatar')
            ->where('lecturer_id', $lecturerId)
            ->whereIn('status', ['pending', 'lecturer_reviewing'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'id'                => $r->id,
                'document_category' => $r->document_category_label,
                'requester'         => $r->requester,
                'status'            => $r->status,
                'created_at'        => $r->created_at,
            ]);
    }
 
    // ─── Activity feed ───────────────────────
    private function buildRecentActivities(int $lecturerId, $classIds): array
    {
        $submissions = Submission::with([
                'assignment:id,title',
                'student:id,name',
                'group:id,name',
            ])
            ->whereHas('assignment', fn($q) => $q->whereIn('class_id', $classIds))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($s) => [
                'type'     => 'submission',
                'icon'     => 'upload',
                'title'    => ($s->group?->name ?? $s->student?->name) . ' nộp bài',
                'subtitle' => $s->assignment?->title,
                'time'     => $s->created_at,
                'link'     => "/lecturer/assignments/{$s->assignment_id}",
            ]);
 
        $signs = DocumentSignRequest::with('requester:id,name')
            ->where('lecturer_id', $lecturerId)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($r) {
                $statusText = match($r->status) {
                    'pending'            => 'gửi yêu cầu ký',
                    'lecturer_reviewing' => 'đang xem xét',
                    'signed'             => 'đã ký',
                    'rejected'           => 'đã từ chối',
                    default              => $r->status,
                };
                return [
                    'type'     => 'sign_request',
                    'icon'     => $r->status === 'signed' ? 'check-circle' : ($r->status === 'rejected' ? 'x-circle' : 'document'),
                    'title'    => "{$r->requester?->name} {$statusText}",
                    'subtitle' => $r->document_category_label,
                    'time'     => $r->updated_at,
                    'link'     => "/lecturer/sign-requests/{$r->id}",
                ];
            });
 
        return $submissions->concat($signs)
            ->sortByDesc('time')
            ->take(15)
            ->values()
            ->toArray();
    }
}
