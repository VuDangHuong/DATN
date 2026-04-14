<?php

use App\Http\Controllers\Shared\ClassStudentController;
use App\Http\Controllers\Student\GroupController;
use App\Http\Controllers\Student\MessageController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Student\TaskController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SemesterController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\MajorController;
use App\Http\Controllers\Admin\ModuleClassController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\KnowledgeController;
use App\Http\Controllers\Admin\AdminChatbotController;
use App\Http\Controllers\Admin\AdminSignController;
use App\Http\Controllers\Lecturers\LecturerSignController;
use App\Http\Controllers\Students\SignRequestController;
use App\Http\Controllers\Lecturers\SignProfileController;
/*
|--------------------------------------------------------------------------
| 1. PUBLIC ROUTES (Không cần đăng nhập)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

/*
|--------------------------------------------------------------------------
| 2. PROTECTED ROUTES (Yêu cầu đăng nhập - auth:sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {

    // =================================================================
    // A. COMMON ROUTES (Dành cho TẤT CẢ user đã đăng nhập)
    // =================================================================
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/update-avatar', [AuthController::class, 'updateAvatarById']);
        Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    });

    // API Tra cứu dữ liệu chung (Ai cũng cần xem để lọc/đăng ký)
    // VD: SV cần xem danh sách Khoa/Ngành để chọn, xem Học kỳ để lọc
    Route::prefix('general')->group(function () {
        Route::get('/faculties', [FacultyController::class, 'index']);
        Route::get('/majors', [MajorController::class, 'index']);
        Route::get('/semesters', [SemesterController::class, 'index']);
        Route::get('/semesters/{id}', [SemesterController::class, 'show']);
    });

    // =================================================================
    // B. ADMIN AREA (Chỉ Admin - Quản lý hệ thống & Đào tạo)
    // =================================================================
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        
        // 1. Quản lý Người dùng (Users)
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::post('/users/import', [UserController::class, 'import']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // 2. Quản lý Cấu trúc Đào tạo (CRUD đầy đủ)
        // Đường dẫn sẽ là: /api/admin/faculties, /api/admin/majors...
        Route::apiResource('faculties', FacultyController::class);
        Route::apiResource('majors', MajorController::class);
        Route::apiResource('subjects', SubjectController::class);
        Route::apiResource('classes', ModuleClassController::class);
        
        //sdasdas
        Route::prefix('classes/{classId}/students')->group(function () {
            // GET    /api/admin/classes/{classId}/students
            // → Danh sách SV trong lớp kèm sĩ số hiện tại
            Route::get('/', [ClassStudentController::class, 'index']);
 
            // POST   /api/admin/classes/{classId}/students
            // Body: { "student_code": "2251172367" }
            // → Thêm 1 SV vào lớp theo mã code (phải tồn tại trong hệ thống)
            Route::post('/', [ClassStudentController::class, 'store']);
 
            // POST   /api/admin/classes/{classId}/students/import
            // Body: multipart/form-data — file (xlsx|xls|csv)
            // Cột A = Mã sinh viên (users.code, role=student)
            // → Import nhiều SV từ file Excel
            Route::post('/import', [ClassStudentController::class, 'import']);
 
            // PATCH  /api/admin/classes/{classId}/students/{studentId}
            // Body: { "has_group": true }
            // → Cập nhật trạng thái SV trong lớp (vd: đã có nhóm chưa)
            Route::patch('/{studentId}', [ClassStudentController::class, 'update']);
 
            // DELETE /api/admin/classes/{classId}/students/{studentId}
            // → Xóa SV khỏi lớp
            Route::delete('/{studentId}', [ClassStudentController::class, 'destroy']);
        });
        // 3. Quản lý Học kỳ (Thêm/Sửa/Ẩn)
        Route::get('/semesters', [SemesterController::class, 'index']);
        Route::post('/semesters', [SemesterController::class, 'store']);
        Route::put('/semesters/{id}', [SemesterController::class, 'update']);
        Route::delete('/semesters/{id}', [SemesterController::class, 'destroy']);

        //4: QUản lý ChatBot
        Route::get   ('knowledge',            [KnowledgeController::class, 'index']);
        Route::get   ('knowledge/categories', [KnowledgeController::class, 'categories']);
        Route::post  ('knowledge',            [KnowledgeController::class, 'store']);
        Route::put   ('knowledge/{id}',       [KnowledgeController::class, 'update']);
        Route::delete('knowledge/{id}',       [KnowledgeController::class, 'destroy']);
        Route::post  ('knowledge/import',     [KnowledgeController::class, 'importCsv']);

        // === Admin ChatBot ===
        Route::post  ('chatbot/ask',              [AdminChatbotController::class, 'ask']);
        Route::get   ('chatbot/history',          [AdminChatbotController::class, 'history']);
        Route::post  ('chatbot/{id}/feedback',    [AdminChatbotController::class, 'feedback']);

        Route::post('chatbot/suggested-questions', [AdminChatbotController::class, 'suggestedQuestions']);

        // Xem tất cả yêu cầu + filter theo status
        Route::get('/sign-requests', [AdminSignController::class, 'index']);
        Route::get('/sign-requests/{id}', [AdminSignController::class, 'show']);
        // Duyệt & chuyển cho GV
        Route::post('/sign-requests/{id}/forward', [AdminSignController::class, 'forward']);
        // Từ chối
        Route::post('/sign-requests/{id}/reject', [AdminSignController::class, 'reject']);
        // Phát hành file đã ký về cho SV
        Route::post('/sign-requests/{id}/complete', [AdminSignController::class, 'complete']);
    });

    // =================================================================
    // C. LECTURER AREA (Khu vực Giảng viên)
    // =================================================================
    // Admin cũng có thể vào đây nếu cần (role:lecturer,admin)
        Route::prefix('lecturer')->middleware('role:lecturer,admin')->group(function () {
            // Xem danh sách yêu cầu được giao
        Route::get('/sign-requests', [LecturerSignController::class, 'index']);
        Route::get('/sign-requests/{id}', [LecturerSignController::class, 'show']);
        // Preview file trước khi ký
        Route::get('/sign-requests/{id}/preview', [LecturerSignController::class, 'preview']);
        // Upload file đã ký số (GV ký offline rồi upload)
        Route::post('/sign-requests/{id}/sign', [LecturerSignController::class, 'sign']);
        // Từ chối ký
        Route::post('/sign-requests/{id}/reject', [LecturerSignController::class, 'reject']);
        // Quản lý profile chữ ký
        Route::get('/sign-profile', [SignProfileController::class, 'show']);
        Route::post('/sign-profile', [SignProfileController::class, 'upsert']);

        Route::prefix('classes/{classId}/students')->group(function () {
            // GET    /api/lecturer/classes/{classId}/students
            Route::get('/', [ClassStudentController::class, 'index']);
 
            // POST   /api/lecturer/classes/{classId}/students
            // Body: { "student_code": "2251172367" }
            Route::post('/', [ClassStudentController::class, 'store']);
 
            // POST   /api/lecturer/classes/{classId}/students/import
            // Body: multipart/form-data — file (xlsx|xls|csv)
            Route::post('/import', [ClassStudentController::class, 'import']);
 
            // PATCH  /api/lecturer/classes/{classId}/students/{studentId}
            // Body: { "has_group": true }
            Route::patch('/{studentId}', [ClassStudentController::class, 'update']);
 
            // DELETE /api/lecturer/classes/{classId}/students/{studentId}
            Route::delete('/{studentId}', [ClassStudentController::class, 'destroy']);
        });
    });

    // =================================================================
    // D. STUDENT AREA (Khu vực Sinh viên)
    // =================================================================
        Route::prefix('student')->middleware('role:student')->group(function () {
            // Tạo yêu cầu số hóa
        Route::post('/sign-requests', [SignRequestController::class, 'store']);
        // Xem trạng thái yêu cầu của mình
        Route::get('/sign-requests', [SignRequestController::class, 'myRequests']);
        Route::get('/sign-requests/{id}', [SignRequestController::class, 'show']);
        // Tải file đã ký
        Route::get('/sign-requests/{id}/download', [SignRequestController::class, 'download']);

        // ─────────────────────────────────────────
        // Dashboard — Sinh viên xem lớp/môn/GV
        // ─────────────────────────────────────────
        Route::get('my-classes', [StudentDashboardController::class, 'myClasses']);
 
        // ─────────────────────────────────────────
        // Quản lý nhóm
        // ─────────────────────────────────────────
        Route::get('classes/{classId}/groups', [GroupController::class, 'index']);       // DS nhóm trong lớp
        Route::post('groups',                  [GroupController::class, 'store']);        // Tạo nhóm
        Route::get('groups/{groupId}',         [GroupController::class, 'show']);         // Chi tiết nhóm
 
        // Quản lý thành viên (chỉ leader)
        Route::post('groups/{groupId}/members',              [GroupController::class, 'addMember']);     // Thêm TV
        Route::delete('groups/{groupId}/members/{memberId}', [GroupController::class, 'removeMember']); // Xóa TV
 
        // ─────────────────────────────────────────
        // Chat nhóm
        // ─────────────────────────────────────────
        Route::get('groups/{groupId}/messages',  [MessageController::class, 'index']);   // Lấy tin nhắn
        Route::post('groups/{groupId}/messages', [MessageController::class, 'store']);   // Gửi tin nhắn
 
        // ─────────────────────────────────────────
        // Quản lý công việc (Task Management)
        // ─────────────────────────────────────────
        Route::get('groups/{groupId}/tasks',  [TaskController::class, 'index']);         // DS task trong nhóm
        Route::post('groups/{groupId}/tasks', [TaskController::class, 'store']);         // Tạo task (leader)
 
        Route::get('tasks/{taskId}',          [TaskController::class, 'show']);          // Chi tiết task
        Route::put('tasks/{taskId}',          [TaskController::class, 'update']);        // Sửa task (leader)
        Route::patch('tasks/{taskId}/status', [TaskController::class, 'updateStatus']); // Đổi status
        Route::delete('tasks/{taskId}',       [TaskController::class, 'destroy']);       // Xóa task (leader)
    });

});