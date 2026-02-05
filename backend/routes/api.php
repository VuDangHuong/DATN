<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SemesterController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\MajorController;
use App\Http\Controllers\Admin\ModuleClassController;

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
        Route::apiResource('classes', ModuleClassController::class);
        
        // 3. Quản lý Học kỳ (Thêm/Sửa/Ẩn)
        Route::post('/semesters', [SemesterController::class, 'store']);
        Route::put('/semesters/{id}', [SemesterController::class, 'update']);
        Route::delete('/semesters/{id}', [SemesterController::class, 'destroy']);
    });

    // =================================================================
    // C. LECTURER AREA (Khu vực Giảng viên)
    // =================================================================
    // Admin cũng có thể vào đây nếu cần (role:lecturer,admin)
    Route::prefix('lecturer')->middleware('role:lecturer,admin')->group(function () {
        // Ví dụ: Lấy danh sách lớp mình dạy
        // Route::get('/my-classes', [ModuleClassController::class, 'getTeacherClasses']);
        
        // Chấm điểm, duyệt bài...
    });

    // =================================================================
    // D. STUDENT AREA (Khu vực Sinh viên)
    // =================================================================
    Route::prefix('student')->middleware('role:student,admin')->group(function () {
        // Ví dụ: Xem thời khóa biểu, điểm số
        // Route::get('/timetable', ...);
        // Route::post('/submission', ...);
    });

});