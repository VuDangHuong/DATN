<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Mail\SubmissionReviewed;
use App\Models\Evaluation\Assignment;
use App\Models\Evaluation\StudentGrade;
use Illuminate\Support\Facades\DB;
use App\Models\Evaluation\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
class SubmissionReviewController extends Controller
{
    /**
     * PATCH /api/lecturer/submissions/{id}/review
     *
     * Cá nhân: { status, score, feedback }
     * Nhóm:   { status, feedback, member_grades: [{ student_id, score, note }] }
     */
    public function review(Request $request, $id): JsonResponse
    {
        $submission = $this->resolveSubmission($id);
        $isGroup    = $submission->submitter_type === 'group';
 
        // ── Validate ─────────────────────────────────────
        $rules = [
            'status'   => 'required|in:approved,rejected',
            'feedback' => 'nullable|string|max:2000',
        ];
 
        if ($isGroup) {
            $rules['member_grades']              = 'nullable|array';
            $rules['member_grades.*.student_id'] = 'required|integer|exists:users,id';
            $rules['member_grades.*.score']      = 'nullable|numeric|min:0|max:10';
            $rules['member_grades.*.note']       = 'nullable|string|max:500';
        } else {
            $rules['score'] = 'nullable|numeric|min:0|max:10';
        }
 
        $data = $request->validate($rules);
 
        DB::transaction(function () use ($submission, $data, $isGroup) {
            // 1. Cập nhật submission
            $submission->update([
                'status'      => $data['status'],
                'feedback'    => $data['feedback'] ?? null,
                'score'       => $isGroup ? null : ($data['score'] ?? null),
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
 
            // 2. Nếu là nhóm → lưu điểm riêng từng thành viên vào student_grades
            if ($isGroup && !empty($data['member_grades'])) {
                $classId = $submission->assignment->class_id;
 
                foreach ($data['member_grades'] as $grade) {
                    StudentGrade::updateOrCreate(
                        [
                            'student_id' => $grade['student_id'],
                            'group_id'   => $submission->group_id,
                            'class_id'   => $classId,
                        ],
                        [
                            'score'        => $grade['score'] ?? null,
                            'lecturer_note' => $grade['note'] ?? null,
                            'is_final'     => $data['status'] === 'approved',
                        ]
                    );
                }
            }
        });
 
        // 3. Gửi email thông báo
        $this->sendNotification(
            $submission->fresh(['assignment', 'reviewer', 'student', 'group.members'])
        );
 
        $label = $data['status'] === 'approved' ? 'Đã chấp nhận' : 'Đã từ chối';
 
        return response()->json([
            'message'    => "{$label} bài nộp thành công",
            'submission' => $this->formatSubmission(
                $submission->fresh(['group.members.user', 'student', 'reviewer'])
            ),
        ]);
    }
 
    /**
     * GET /api/lecturer/submissions/{id}/member-grades
     * Lấy điểm từng thành viên nhóm để pre-fill khi mở modal chấm lại
     */
    public function getMemberGrades(int $id): JsonResponse
    {
        $submission = $this->resolveSubmission($id);
 
        if ($submission->submitter_type !== 'group') {
            return response()->json(['grades' => []]);
        }
 
        $submission->load(['group.members.user', 'assignment']);
        $classId = $submission->assignment->class_id;
 
        $grades = $submission->group->members->map(function ($member) use ($submission, $classId) {
            $grade = StudentGrade::where('student_id', $member->user_id)
                ->where('group_id', $submission->group_id)
                ->where('class_id', $classId)
                ->first();
 
            return [
                'student_id'   => $member->user_id,
                'student_name' => $member->user->name ?? '—',
                'student_code' => $member->user->code ?? '—',
                'role'         => $member->role,
                'score'        => $grade?->score,
                'note'         => $grade?->lecturer_note,
            ];
        })->values();
 
        return response()->json(['grades' => $grades]);
    }
 
    /**
     * POST /api/lecturer/assignments/{id}/review-all
     * Duyệt toàn bộ bài nộp đang pending (không chấm điểm từng thành viên)
     */
    public function reviewAll(Request $request, $assignmentId): JsonResponse
    {
        $assignment = $this->resolveAssignment($assignmentId);
 
        $data = $request->validate([
            'status'   => 'required|in:approved,rejected',
            'feedback' => 'nullable|string|max:2000',
        ]);
 
        $submissions = $assignment->submissions()
            ->where('status', Submission::STATUS_PENDING)
            ->with(['assignment', 'student', 'group.members'])
            ->get();
 
        if ($submissions->isEmpty()) {
            return response()->json(['message' => 'Không có bài nộp nào đang chờ duyệt'], 422);
        }
 
        $count = 0;
        foreach ($submissions as $submission) {
            $submission->update([
                'status'      => $data['status'],
                'feedback'    => $data['feedback'] ?? null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
 
            $this->sendNotification(
                $submission->load(['assignment', 'reviewer', 'student', 'group.members'])
            );
            $count++;
        }
 
        $label = $data['status'] === 'approved' ? 'chấp nhận' : 'từ chối';
 
        return response()->json([
            'message' => "Đã {$label} {$count} bài nộp",
            'count'   => $count,
        ]);
    }
 
    /**
     * GET /api/lecturer/assignments/{id}/submissions
     */
    public function submissionList(Request $request, $assignmentId): JsonResponse
    {
        $assignment = $this->resolveAssignment($assignmentId);
 
        $query = $assignment->submissions()
            ->with(['group.members.user', 'student', 'reviewer'])
            ->latest('submitted_at');
 
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->type) {
            $query->where('submitter_type', $request->type);
        }
 
        $submissions = $query->get()->map(fn($s) => $this->formatSubmission($s));
 
        $stats = [
            'total'    => $assignment->submissions()->count(),
            'pending'  => $assignment->submissions()->where('status', 'pending')->count(),
            'approved' => $assignment->submissions()->where('status', 'approved')->count(),
            'rejected' => $assignment->submissions()->where('status', 'rejected')->count(),
            'late'     => $assignment->submissions()->where('is_late', true)->count(),
        ];
 
        return response()->json([
            'assignment'  => ['id' => $assignment->id, 'title' => $assignment->title],
            'stats'       => $stats,
            'submissions' => $submissions,
        ]);
    }
 
    // ── Private helpers ───────────────────────────────────
 
    private function resolveSubmission($id): Submission
    {
        return Submission::whereHas('assignment.class', fn($q) =>
            $q->where('lecturer_id', auth()->id())
        )->findOrFail($id);
    }
 
    private function resolveAssignment($id): Assignment
    {
        return Assignment::whereHas('class', fn($q) =>
            $q->where('lecturer_id', auth()->id())
        )->findOrFail($id);
    }
 
    private function sendNotification(Submission $submission): void
    {
        $emails = $submission->notify_emails;
        if (empty($emails)) return;
 
        foreach ($emails as $email) {
            $name = $submission->submitter_type === 'group'
                ? ($submission->group?->members->firstWhere('email', $email)?->name ?? 'Sinh viên')
                : ($submission->student?->name ?? 'Sinh viên');
 
            Mail::to($email)->queue(new SubmissionReviewed($submission, $name));
        }
    }
 
    private function formatSubmission(Submission $s): array
    {
        $base = [
            'id'             => $s->id,
            'submitter_type' => $s->submitter_type,
            'submitter_name' => $s->submitter_name,
            'group'          => $s->group  ? ['id' => $s->group->id,  'name' => $s->group->name]  : null,
            'student'        => $s->student ? ['id' => $s->student->id, 'name' => $s->student->name, 'code' => $s->student->code] : null,
            'file_name'      => $s->file_name,
            'file_size'      => $s->file_size_readable,
            'note'           => $s->note,
            'is_late'        => $s->is_late,
            'submitted_at'   => $s->submitted_at,
            'status'         => $s->status,
            'status_label'   => $s->status_label,
            'score'          => $s->score,
            'feedback'       => $s->feedback,
            'reviewer'       => $s->reviewer?->name,
            'reviewed_at'    => $s->reviewed_at,
        ];
 
        //Thêm điểm từng thành viên nếu là nhóm
        if ($s->submitter_type === 'group' && $s->group && $s->group->members->isNotEmpty()) {
            $classId = $s->assignment?->class_id;
 
            $base['member_grades'] = $s->group->members->map(function ($member) use ($s, $classId) {
                $grade = $classId
                    ? StudentGrade::where('student_id', $member->user_id)
                        ->where('group_id', $s->group_id)
                        ->where('class_id', $classId)
                        ->first()
                    : null;
 
                return [
                    'student_id'   => $member->user_id,
                    'student_name' => $member->user->name ?? '—',
                    'student_code' => $member->user->code ?? '—',
                    'role'         => $member->role,
                    'score'        => $grade?->score,
                    'note'         => $grade?->lecturer_note,
                ];
            })->values();
        }
 
        return $base;
    }
}
