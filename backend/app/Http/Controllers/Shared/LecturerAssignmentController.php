<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Academic\Classes;
use App\Models\Evaluation\Assignment;
use App\Models\Evaluation\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
class LecturerAssignmentController extends Controller
{
    public function index($classId): JsonResponse
    {
        $class = Classes::where('lecturer_id', auth()->id())->findOrFail($classId);
 
        $assignments = $class->assignments()
            ->withCount(['submissions as group_count' => fn($q) => $q->where('submitter_type', 'group')])
            ->withCount(['submissions as individual_count' => fn($q) => $q->where('submitter_type', 'individual')])
            ->withCount(['submissions as pending_count' => fn($q) => $q->where('status', 'pending')])
            ->latest()
            ->get()
            ->map(fn($a) => $this->formatAssignment($a));
 
        return response()->json($assignments);
    }
 
    // POST /api/lecturer/classes/{classId}/assignments
    public function store(Request $request,$classId): JsonResponse
    {
        $class = Classes::where('lecturer_id', auth()->id())->findOrFail($classId);
 
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'deadline'            => 'required|date|after:now',
            'allow_late'          => 'boolean',
            'submission_type'     => 'in:group,individual,both',
            'max_file_size'       => 'integer|min:1|max:500',
            'allowed_extensions'  => 'nullable|array',
            'allowed_extensions.*'=> 'string',
            'document_category'       => 'nullable|string|in:' . implode(',', array_keys(Assignment::DOCUMENT_CATEGORIES)),
            'document_category_label' => 'nullable|string|max:100',
        ]);
        //Tự động set requires_signing dựa vào document_category
        $data['requires_signing'] = !empty($data['document_category']);
        $assignment = $class->assignments()->create([
            ...$data,
            'created_by' => auth()->id(),
        ]);
 
        return response()->json([
            'message'    => 'Tạo đợt nộp bài thành công',
            'assignment' => $this->formatAssignment($assignment),
        ], 201);
    }
 
    // GET /api/lecturer/assignments/{id}
    public function show($id): JsonResponse
    {
        $assignment = $this->resolveAssignment($id);
 
        $submissions = $assignment->submissions()
            ->with(['group', 'student', 'history'])
            ->latest('submitted_at')
            ->get()
            ->map(fn($s) => $this->formatSubmission($s));
 
        // Tính missing: ai chưa nộp
        $missing = $this->getMissing($assignment);
 
        return response()->json([
            'assignment'  => $this->formatAssignment($assignment),
            'submissions' => $submissions,
            'missing'     => $missing,
            'stats' => [
                'submitted' => $submissions->count(),
                'missing'   => $missing->count(),
                'late'      => $submissions->where('is_late', true)->count(),
            ],
        ]);
    }
 
    // PATCH /api/lecturer/assignments/{id}
    public function update(Request $request, $id): JsonResponse
    {
        $assignment = $this->resolveAssignment($id);
 
        $data = $request->validate([
            'title'               => 'string|max:255',
            'description'         => 'nullable|string',
            'deadline'            => 'date',
            'allow_late'          => 'boolean',
            'submission_type'     => 'in:group,individual,both',
            'max_file_size'       => 'integer|min:1|max:500',
            'allowed_extensions'  => 'nullable|array',
            'is_active'           => 'boolean',
            'document_category'       => 'nullable|string|in:' . implode(',', array_keys(Assignment::DOCUMENT_CATEGORIES)),
            'document_category_label' => 'nullable|string|max:100',
        ]);
        if (array_key_exists('document_category', $data)) {
            $data['requires_signing'] = !empty($data['document_category']);
        }
        $assignment->update($data);
 
        return response()->json([
            'message'    => 'Cập nhật thành công',
            'assignment' => $this->formatAssignment($assignment->fresh()),
        ]);
    }
 
    // DELETE /api/lecturer/assignments/{id}
    public function destroy($id): JsonResponse
    {
        $assignment = $this->resolveAssignment($id);
        $assignment->delete();
        return response()->json(['message' => 'Đã xóa đợt nộp bài']);
    }
 
    // GET /api/lecturer/submissions/{id}/download
    public function download($submissionId): mixed
    {
        $submission = Submission::findOrFail($submissionId);
 
        // Kiểm tra quyền
        $this->resolveAssignment($submission->assignment_id);
 
        return Storage::download($submission->file_path, $submission->file_name);
    }
 
    // ── Helpers ─────────────────────────────────────────────
    private function resolveAssignment($id): Assignment
    {
        return Assignment::whereHas('class', fn($q) =>
            $q->where('lecturer_id', auth()->id())
        )->findOrFail($id);
    }
 
    private function getMissing(Assignment $assignment): \Illuminate\Support\Collection
    {
        $submittedIds = $assignment->submissions->pluck(
            $assignment->submission_type === 'group' ? 'group_id' : 'student_id'
        )->filter();
 
        if (in_array($assignment->submission_type, ['group', 'both'])) {
            return $assignment->class->groups()
                ->whereNotIn('id', $submittedIds)
                ->select('id', 'name')
                ->get()
                ->map(fn($g) => ['type' => 'group', 'id' => $g->id, 'name' => $g->name]);
        }
 
        return $assignment->class->students()
            ->whereNotIn('users.id', $submittedIds)
            ->select('users.id', 'users.name', 'users.code')
            ->get()
            ->map(fn($s) => ['type' => 'individual', 'id' => $s->id, 'name' => $s->name, 'code' => $s->code]);
    }
 
    private function formatAssignment(Assignment $a): array
    {
        return [
            'id'                  => $a->id,
            'class_id'            => $a->class_id,
            'title'               => $a->title,
            'description'         => $a->description,
            'deadline'            => $a->deadline,
            'allow_late'          => $a->allow_late,
            'submission_type'     => $a->submission_type,
            'max_file_size'       => $a->max_file_size,
            'allowed_extensions'  => $a->allowed_extensions,
            'is_active'           => $a->is_active,
            'is_expired'          => now()->gt($a->deadline),
            'group_count'         => $a->group_count ?? 0,
            'individual_count'    => $a->individual_count ?? 0,
            'pending_count'       => $a->pending_count ?? 0,
            'created_at'          => $a->created_at,
        ];
    }
 
    private function formatSubmission(Submission $s): array
    {
        return [
            'id'             => $s->id,
            'submitter_type' => $s->submitter_type,
            'group'          => $s->group ? ['id' => $s->group->id, 'name' => $s->group->name] : null,
            'student'        => $s->student ? ['id' => $s->student->id, 'name' => $s->student->name, 'code' => $s->student->code] : null,
            'file_name'      => $s->file_name,
            'file_size'      => $s->file_size,
            'file_type'      => $s->file_type,
            'note'           => $s->note,
            'is_late'        => $s->is_late,
            'submitted_at'   => $s->submitted_at,
            'history_count'  => $s->history->count(),
        ];
    }
}
