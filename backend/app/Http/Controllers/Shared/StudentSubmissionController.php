<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Academic\Classes;
use App\Models\Evaluation\Assignment;
use App\Models\Evaluation\Submission;
use App\Models\Evaluation\Submissionhistory;
use App\Models\Sign\DocumentSignRequest;
use App\Services\DocumentSignService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentSubmissionController extends Controller
{
    public function __construct(protected DocumentSignService $signService) {}

    public function index(int $classId): JsonResponse
    {
        $user = auth()->user();
        $class = Classes::whereHas('students', fn($q) => $q->where('student_id', $user->id))
            ->findOrFail($classId);
        $myGroup = $user->groups()->where('class_id', $classId)->first();
 
        $assignments = $class->assignments()
            ->where('is_active', true)
            ->latest()
            ->get()
            ->map(function ($a) use ($user, $myGroup) {
                $formatted = [
                    'id'                 => $a->id,
                    'title'              => $a->title,
                    'description'        => $a->description,
                    'deadline'           => $a->deadline,
                    'allow_late'         => $a->allow_late,
                    'submission_type'    => $a->submission_type,
                    'max_file_size'      => $a->max_file_size,
                    'allowed_extensions' => $a->allowed_extensions,
                    'is_expired'         => now()->gt($a->deadline),
                    'requires_signing'        => (bool) $a->requires_signing,
                    'document_category'       => $a->document_category,
                    'document_category_label' => $a->document_category_label,
                ];

                if (in_array($a->submission_type, ['individual', 'both'])) {
                    $sub = $a->submissions()->where('student_id', $user->id)->first();
                    $formatted['my_submission'] = $sub ? $this->formatMySubmission($sub) : null;
                }
 
                // Trạng thái nộp nhóm
                if ($myGroup && in_array($a->submission_type, ['group', 'both'])) {
                    $sub = $a->submissions()->where('group_id', $myGroup->id)->first();
                    $formatted['group_submission'] = $sub ? $this->formatMySubmission($sub) : null;
                    $formatted['is_leader'] = $myGroup->leader_id === auth()->id();
                }
 
                return $formatted;
            });
 
        return response()->json($assignments);
    }
 
    // POST /api/student/assignments/{id}/submit  (cá nhân)
    public function submitIndividual(Request $request, int $id): JsonResponse
    {
        $assignment = $this->resolveStudentAssignment($id);
 
        if (!in_array($assignment->submission_type, ['individual', 'both'])) {
            return response()->json(['message' => 'Đợt nộp này không cho phép nộp cá nhân'], 422);
        }

        // SV có thể tự chọn category (tuỳ chọn), hoặc lấy từ assignment
        $request->validate([
            'document_category' => [
                'nullable', 'string',
                'in:' . implode(',', array_keys(Assignment::DOCUMENT_CATEGORIES)),
            ],
        ]);

        $file = $this->validateAndStoreFile($request, $assignment);
        $isLate = now()->gt($assignment->deadline);
 
        $submission = Submission::updateOrCreate(
            ['assignment_id' => $id, 'student_id' => auth()->id()],
            [
                'submitter_type' => 'individual',
                'file_path'      => $file['path'],
                'file_name'      => $file['name'],
                'file_size'      => $file['size'],
                'file_type'      => $file['type'],
                'note'           => $request->note,
                'is_late'        => $isLate,
                'submitted_at'   => now(),
                'status'         => Submission::STATUS_PENDING,
                'score'          => null,
                'feedback'       => null,
                'reviewed_by'    => null,
                'reviewed_at'    => null,
            ]
        );
 
        // Ghi lịch sử
        Submissionhistory::create([
            'submission_id' => $submission->id,
            'submitted_by'  => auth()->id(),
            'file_path'     => $file['path'],
            'file_name'     => $file['name'],
            'file_size'     => $file['size'],
            'note'          => $request->note,
            'is_late'       => $isLate,
            'submitted_at'  => now(),
        ]);

        //Ưu tiên category SV chọn, nếu không thì lấy từ assignment
        $signRequest = null;
        $category = $request->document_category
            ?: ($assignment->requires_signing ? $assignment->document_category : null);

        if ($category) {
            $categoryLabel = Assignment::DOCUMENT_CATEGORIES[$category] ?? $category;
            $signRequest   = $this->createSignRequestIfNeeded(
                $submission, $assignment, $category, $categoryLabel
            );
        }

        return response()->json([
            'message'    => $isLate ? 'Nộp bài thành công (trễ hạn)' : 'Nộp bài thành công',
            'submission' => $this->formatMySubmission($submission),
            'is_late'    => $isLate,
            'sign_request' => $signRequest ? $this->formatSignRequest($signRequest) : null,
        ]);
    }
 
    // POST /api/student/assignments/{id}/submit-group  (nhóm — chỉ leader)
    public function submitGroup(Request $request, int $id): JsonResponse
    {
        $assignment = $this->resolveStudentAssignment($id);
 
        if (!in_array($assignment->submission_type, ['group', 'both'])) {
            return response()->json(['message' => 'Đợt nộp này không cho phép nộp theo nhóm'], 422);
        }
 
        $myGroup = auth()->user()->groups()
            ->where('class_id', $assignment->class_id)
            ->where('leader_id', auth()->id())
            ->first();
 
        if (!$myGroup) {
            return response()->json(['message' => 'Bạn không phải trưởng nhóm hoặc chưa có nhóm'], 403);
        }

        $request->validate([
            'document_category' => [
                'nullable', 'string',
                'in:' . implode(',', array_keys(Assignment::DOCUMENT_CATEGORIES)),
            ],
        ]);

        $file = $this->validateAndStoreFile($request, $assignment);
        $isLate = now()->gt($assignment->deadline);
 
        $submission = Submission::updateOrCreate(
            ['assignment_id' => $id, 'group_id' => $myGroup->id],
            [
                'submitter_type' => 'group',
                'file_path'      => $file['path'],
                'file_name'      => $file['name'],
                'file_size'      => $file['size'],
                'file_type'      => $file['type'],
                'note'           => $request->note,
                'is_late'        => $isLate,
                'submitted_at'   => now(),
                'status'         => Submission::STATUS_PENDING,
                'score'          => null,
                'feedback'       => null,
                'reviewed_by'    => null,
                'reviewed_at'    => null,
            ]
        );
 
        Submissionhistory::create([
            'submission_id' => $submission->id,
            'submitted_by'  => auth()->id(),
            'file_path'     => $file['path'],
            'file_name'     => $file['name'],
            'file_size'     => $file['size'],
            'note'          => $request->note,
            'is_late'       => $isLate,
            'submitted_at'  => now(),
        ]);

        //Ưu tiên category SV chọn, nếu không thì lấy từ assignment
        $signRequest = null;
        $category = $request->document_category
            ?: ($assignment->requires_signing ? $assignment->document_category : null);

        if ($category) {
            $categoryLabel = Assignment::DOCUMENT_CATEGORIES[$category] ?? $category;
            $signRequest   = $this->createSignRequestIfNeeded(
                $submission, $assignment, $category, $categoryLabel
            );
        }

        return response()->json([
            'message'    => $isLate ? 'Nộp bài nhóm thành công (trễ hạn)' : 'Nộp bài nhóm thành công',
            'submission' => $this->formatMySubmission($submission),
            'is_late'    => $isLate,
            'sign_request' => $signRequest ? $this->formatSignRequest($signRequest) : null,
        ]);
    }
 
    // GET /api/student/assignments/{id}/submission/history
    public function history(int $id): JsonResponse
    {
        $assignment = $this->resolveStudentAssignment($id);
        $user = auth()->user();
 
        $myGroup = $user->groups()->where('class_id', $assignment->class_id)->first();
 
        $submission = Submission::where('assignment_id', $id)
            ->where(fn($q) => $q
                ->where('student_id', $user->id)
                ->orWhere('group_id', $myGroup?->id)
            )
            ->with(['history.submittedBy'])
            ->first();
 
        if (!$submission) {
            return response()->json(['history' => []]);
        }
 
        $history = $submission->history->map(fn($h) => [
            'id'           => $h->id,
            'file_name'    => $h->file_name,
            'file_size'    => $h->file_size,
            'note'         => $h->note,
            'is_late'      => $h->is_late,
            'submitted_by' => $h->submittedBy->name,
            'submitted_at' => $h->submitted_at,
        ]);
 
        return response()->json(['history' => $history]);
    }
 
    // ── Helpers ─────────────────────────────────────────────
    private function resolveStudentAssignment(int $id): Assignment
    {
        $assignment = Assignment::with('class')->findOrFail($id);
 
        // Kiểm tra sv có trong lớp
        $inClass = $assignment->class->students()
            ->where('student_id', auth()->id())
            ->exists();
 
        abort_unless($inClass, 403, 'Bạn không thuộc lớp này');
        abort_unless($assignment->is_active, 403, 'Đợt nộp đã bị đóng');
 
        if (!$assignment->allow_late && now()->gt($assignment->deadline)) {
            abort(422, 'Đã hết hạn nộp bài');
        }
 
        return $assignment;
    }
 
    private function validateAndStoreFile(Request $request, Assignment $assignment): array
    {
        $maxKB = $assignment->max_file_size * 1024;
        $mimes = $assignment->allowed_extensions
            ? implode(',', $assignment->allowed_extensions)
            : 'pdf,doc,docx,zip,rar,png,jpg';
 
        $request->validate([
            'file' => "required|file|mimes:{$mimes}|max:{$maxKB}",
            'note' => 'nullable|string|max:500',
        ]);
 
        $file = $request->file('file');
        $path = $file->store("submissions/{$assignment->id}", 'private');
 
        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'type' => $file->getClientMimeType(),
        ];
    }
 
    private function formatMySubmission(Submission $s): array
    {
        return [
            'id'           => $s->id,
            'file_name'    => $s->file_name,
            'file_size'    => $s->file_size,
            'file_type'    => $s->file_type,
            'note'         => $s->note,
            'is_late'      => $s->is_late,
            'submitted_at' => $s->submitted_at,
            'status'       => $s->status,
            'status_label' => $s->status_label,
            'score'        => $s->score,
            'feedback'     => $s->feedback,
        ];
    }

    /**
     * Tạo DocumentSignRequest tự động:
     * - Nếu SV chọn document_category → dùng category SV chọn
     * - Nếu assignment.requires_signing = true → dùng category của assignment
     * - Xóa request cũ pending/admin_reviewing trước khi tạo mới (khi nộp lại)
     */
    private function createSignRequestIfNeeded(
        Submission $submission,
        Assignment $assignment,
        string $category,
        string $categoryLabel
    ): ?DocumentSignRequest {
        DocumentSignRequest::where('submission_id', $submission->id)
            ->whereIn('status', [
                DocumentSignRequest::STATUS_PENDING,
                DocumentSignRequest::STATUS_ADMIN_REVIEWING,
            ])
            ->delete();
        $lecturerId = $assignment->class->lecturer_id ?? null;
        $signRequest = DocumentSignRequest::create([
            'submission_id'           => $submission->id,
            'requester_id'            => auth()->id(),
            'lecturer_id'             => $lecturerId,
            'class_id'                => $assignment->class_id,
            'document_type'           => pathinfo($submission->file_name, PATHINFO_EXTENSION),
            'document_category'       => $category,
            'document_category_label' => $categoryLabel,
            'original_file'           => $submission->file_path,
            'status'                  => DocumentSignRequest::STATUS_PENDING,
        ]);

        $this->signService->log($signRequest->id, auth()->id(), 'created');

        return $signRequest;
    }

    private function formatSignRequest(DocumentSignRequest $sr): array
    {
        return [
            'id'                      => $sr->id,
            'status'                  => $sr->status,
            'status_label'            => $sr->status_label,
            'document_category'       => $sr->document_category,
            'document_category_label' => $sr->document_category_label,
            'created_at'              => $sr->created_at,
        ];
    }
}