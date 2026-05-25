<?php

namespace App\Http\Controllers\Lecturers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ClassStudentService;
use Illuminate\Http\JsonResponse;
use App\Models\Academic\Classes;
class LecturerClassStudentController extends Controller
{
    public function __construct(private readonly ClassStudentService $service) {}
 
    /**
     * GET /api/lecturer/my-classes/{classId}/students
     */
    public function index(Request $request, int $classId): JsonResponse
    {
        $class  = $this->resolveOwnedClass($request->user(), $classId);
        $result = $this->service->getStudents($class);
 
        return response()->json($result);
    }
 
    /**
     * POST /api/lecturer/my-classes/{classId}/students
     * Body: { "student_code": "2251172367" }
     */
    public function store(Request $request, int $classId): JsonResponse
    {
        $request->validate([
            'student_code' => 'required|string|max:50',
        ]);
 
        $class  = $this->resolveOwnedClass($request->user(), $classId);
        $result = $this->service->addStudent($class, $request->student_code);
 
        return $this->toResponse($result);
    }
 
    /**
     * PATCH /api/lecturer/my-classes/{classId}/students/{studentId}
     * Body: { "has_group": true }
     */
    // public function update(Request $request, int $classId, int $studentId): JsonResponse
    // {
    //     $request->validate([
    //         'has_group' => 'required|boolean',
    //     ]);
 
    //     $class  = $this->resolveOwnedClass($request->user(), $classId);
    //     $result = $this->service->updateStudentPivot($class, $studentId, [
    //         'has_group' => $request->has_group,
    //     ]);
 
    //     return $this->toResponse($result);
    // }
 
    /**
     * DELETE /api/lecturer/my-classes/{classId}/students/{studentId}
     */
    public function destroy(Request $request, int $classId, int $studentId): JsonResponse
    {
        $class  = $this->resolveOwnedClass($request->user(), $classId);
        $result = $this->service->removeStudent($class, $studentId);
 
        return $this->toResponse($result);
    }
 
    /**
     * POST /api/lecturer/my-classes/{classId}/students/import
     * Body: multipart/form-data — file (xlsx|xls|csv)
     */
    public function import(Request $request, int $classId): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);
 
        $class  = $this->resolveOwnedClass($request->user(), $classId);
        $result = $this->service->importStudents($class, $request->file('file'));
 
        return $this->toResponse($result);
    }
 
    // ─────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────
 
    /**
     * Tìm lớp + verify quyền phụ trách (404 nếu không phải của GV).
     */
    private function resolveOwnedClass($user, int $classId): Classes
    {
        return Classes::with(['subjects', 'semester', 'students'])
            ->where('lecturer_id', $user->id)
            ->findOrFail($classId);
    }
 
    private function toResponse(array $result): JsonResponse
    {
        if ($result['status'] === 'error') {
            return response()->json(['message' => $result['message']], $result['code']);
        }
 
        return response()->json([
            'message' => $result['message'],
            ...$result['data'],
        ]);
    }
}
