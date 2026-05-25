<?php

namespace App\Http\Controllers\Lecturers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Academic\Classes;
use Illuminate\Http\JsonResponse;
class LecturerClassController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
 
        $query = Classes::with(['subjects', 'semester', 'lecturer'])
            ->withCount('students')                      //Đếm sĩ số
            ->where('lecturer_id', $user->id);           //Chỉ lớp của GV này
 
        // Mặc định ẩn học kỳ không active
        if (!$request->has('include_inactive')) {
            $query->whereHas('semester', function ($q) {
                $q->where('is_active', true);
            });
        }
 
        // Filter theo học kỳ
        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }
 
        // Filter theo ngành
        if ($request->filled('major_id')) {
            $query->whereHas('subjects', function ($q) use ($request) {
                $q->where('major_id', $request->major_id);
            });
        }
 
        // Search theo tên/mã lớp
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('code', 'like', "%$search%");
            });
        }
 
        return response()->json([
            'message' => 'Danh sách lớp tôi phụ trách',
            'data'    => $query->orderByDesc('created_at')->get(),
        ]);
    }
}
