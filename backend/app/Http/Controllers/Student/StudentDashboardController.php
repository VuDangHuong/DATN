<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function __construct(private readonly StudentDashboardService $service) {}
 
    /**
     * GET /student/my-classes
     *
     * Khi sinh viên login, gọi API này để biết:
     *   - Mình đang thuộc lớp nào (admin/giảng viên đã add/import)
     *   - Lớp đó học môn gì
     *   - Ai dạy lớp đó
     *   - Kỳ học nào
     *   - Nhóm của mình trong lớp (nếu có)
     */
    public function myClasses(): JsonResponse
    {
        $result = $this->service->getMyClasses(auth()->user());
 
        return response()->json($result);
    }
}
