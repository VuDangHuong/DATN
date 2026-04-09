<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Academic\Classes;
use App\Services\ClassStudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller dùng chung cho Admin và Lecturer.
 *
 * Sự khác biệt duy nhất giữa hai role:
 *   - Admin   : truy cập mọi lớp
 *   - Lecturer: chỉ truy cập lớp mình phụ trách
 *
 * Logic này được xử lý bởi method resolveClass() bên dưới.
 */
class ClassStudentController extends Controller
{
    public function __construct(private readonly ClassStudentService $service) {}

    /**
     * GET /admin/classes/{id}/students
     * GET /lecturer/classes/{id}/students
     *
     * Lấy danh sách sinh viên trong lớp kèm sĩ số.
     */
    public function index(int $id): JsonResponse
    {
        $class  = $this->resolveClass($id);
        $result = $this->service->getStudents($class);
 
        return response()->json($result);
    } 

    /**
     * POST /admin/classes/{id}/students
     * POST /lecturer/classes/{id}/students
     *
     * Body: { "student_code": "SV001" }
     *
     * Thêm sinh viên bằng mã code — bắt buộc sinh viên phải tồn tại trong hệ thống.
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'student_code' => 'required|string|max:50',
        ]);

        $class  = $this->resolveClass($id);
        $result = $this->service->addStudent($class, $request->student_code);

        return $this->toResponse($result);
    }

    /**
     * PATCH /admin/classes/{id}/students/{studentId}
     * PATCH /lecturer/classes/{id}/students/{studentId}
     *
     * Body: { "has_group": true }
     *
     * Cập nhật trạng thái sinh viên trong lớp.
     */
    public function update(Request $request, int $id, int $studentId): JsonResponse
    {
        $request->validate([
            'has_group' => 'required|boolean',
        ]);

        $class  = $this->resolveClass($id);
        $result = $this->service->updateStudentPivot($class, $studentId, [
            'has_group' => $request->has_group,
        ]);

        return $this->toResponse($result);
    }

    /**
     * DELETE /admin/classes/{id}/students/{studentId}
     * DELETE /lecturer/classes/{id}/students/{studentId}
     *
     * Xóa sinh viên khỏi lớp.
     */
    public function destroy(int $id, int $studentId): JsonResponse
    {
        $class  = $this->resolveClass($id);
        $result = $this->service->removeStudent($class, $studentId);

        return $this->toResponse($result);
    }

    /**
     * POST /admin/classes/{id}/students/import
     * POST /lecturer/classes/{id}/students/import
     *
     * Body: multipart/form-data — file (xlsx|xls|csv)
     *
     * Import danh sách sinh viên từ file.
     * Cột A = Mã sinh viên (phải khớp users.code, role=student).
     */
    public function import(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $class  = $this->resolveClass($id);
        $result = $this->service->importStudents($class, $request->file('file'));

        return $this->toResponse($result);
    }

    // ─────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────

    /**
     * Phân quyền truy cập lớp theo role:
     *   - admin   → lấy bất kỳ lớp nào (404 nếu không tồn tại)
     *   - lecturer→ chỉ lấy lớp mình phụ trách (403 nếu không phải của mình)
     */
    private function resolveClass(int $id): Classes
    {
        $user = auth()->user();

        $query = Classes::with(['subjects', 'semester', 'students']);

        if ($user->role === 'lecturer') {
            $query->where('lecturer_id', $user->id);
        }

        return $query->findOrFail($id);
    }

    /**
     * Chuyển kết quả từ Service sang JsonResponse.
     */
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