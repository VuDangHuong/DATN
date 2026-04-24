<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Mail\SubmissionReviewed;
use App\Models\Evaluation\Assignment;
use App\Models\Evaluation\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
class SubmissionReviewController extends Controller
{
    /**
     * PATCH /api/lecturer/submissions/{id}/review
     *
     * Duyệt hoặc từ chối 1 bài nộp cụ thể.
     *
     * Body: {
     *   "status":   "approved" | "rejected",
     *   "score":    8.5,           // nullable, 0-10
     *   "feedback": "Làm tốt!"    // nullable
     * }
     */
    public function review(Request $request, $id): JsonResponse
    {
        $submission = $this->resolveSubmission($id);
 
        $data = $request->validate([
            'status'   => 'required|in:approved,rejected',
            'score'    => 'nullable|numeric|min:0|max:10',
            'feedback' => 'nullable|string|max:2000',
        ]);
 
        $submission->update([
            'status'      => $data['status'],
            'score'       => $data['score'] ?? null,
            'feedback'    => $data['feedback'] ?? null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
 
        // Gửi email thông báo
        $this->sendNotification($submission->fresh(['assignment', 'reviewer', 'student', 'group.members']));
 
        $label = $data['status'] === 'approved' ? 'Đã chấp nhận' : 'Đã từ chối';
 
        return response()->json([
            'message'    => "{$label} bài nộp thành công",
            'submission' => $this->formatSubmission($submission),
        ]);
    }
 
    /**
     * POST /api/lecturer/assignments/{id}/review-all
     *
     * Duyệt toàn bộ bài nộp của 1 đợt cùng lúc.
     *
     * Body: {
     *   "status":   "approved" | "rejected",
     *   "feedback": "Nhận xét chung"   // áp dụng cho tất cả
     * }
     */
    public function reviewAll(Request $request,$assignmentId): JsonResponse
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
 
            $this->sendNotification($submission->load(['assignment', 'reviewer', 'student', 'group.members']));
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
     *
     * Danh sách bài nộp kèm trạng thái duyệt — có filter.
     * Query: ?status=pending|approved|rejected&type=group|individual
     */
    public function submissionList(Request $request, $assignmentId): JsonResponse
    {
        $assignment = $this->resolveAssignment($assignmentId);
 
        $query = $assignment->submissions()
            ->with(['group', 'student', 'reviewer'])
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
 
    private function resolveSubmission( $id): Submission
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
            // Tìm tên người nhận
            $name = $submission->submitter_type === 'group'
                ? ($submission->group?->members->firstWhere('email', $email)?->name ?? 'Sinh viên')
                : ($submission->student?->name ?? 'Sinh viên');
 
            Mail::to($email)->queue(new SubmissionReviewed($submission, $name));
        }
    }
 
    private function formatSubmission(Submission $s): array
    {
        return [
            'id'             => $s->id,
            'submitter_type' => $s->submitter_type,
            'submitter_name' => $s->submitter_name,
            'group'          => $s->group ? ['id' => $s->group->id, 'name' => $s->group->name] : null,
            'student'        => $s->student ? ['id' => $s->student->id, 'name' => $s->student->name, 'code' => $s->student->code] : null,
            'file_name'      => $s->file_name,
            'file_size'      => $s->file_size_readable,
            'note'           => $s->note,
            'is_late'        => $s->is_late,
            'submitted_at'   => $s->submitted_at,
            // Review
            'status'         => $s->status,
            'status_label'   => $s->status_label,
            'score'          => $s->score,
            'feedback'       => $s->feedback,
            'reviewer'       => $s->reviewer?->name,
            'reviewed_at'    => $s->reviewed_at,
        ];
    }
}
