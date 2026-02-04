<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
// Giả sử bạn đã tạo UserController để Admin tạo tài khoản cho GV
use App\Http\Controllers\Admin\UserController; 
use App\Http\Controllers\Admin\SemesterController;

/*
|--------------------------------------------------------------------------
| 1. AUTHENTICATION ROUTES (Xác thực & Tài khoản)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/update-avatar', [AuthController::class, 'updateAvatarById']);
        Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    });
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        // API tạo tài khoản cho Giảng viên/Admin (Thay thế register)
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);   // Xem chi tiết
        Route::put('/users/{id}', [UserController::class, 'update']); // Cập nhật
        Route::delete('/users/{id}', [UserController::class, 'destroy']); // Xóa
        Route::post('/users/import', [UserController::class, 'import']);
    });

    Route::prefix('semesters')->group(function () {
        Route::get('/', [SemesterController::class, 'index']);
        Route::get('/{id}', [SemesterController::class, 'show']);

        Route::middleware('role:admin')->group(function () {
            Route::post('/', [SemesterController::class, 'store']);
            Route::put('/{id}', [SemesterController::class, 'update']);
            Route::delete('/{id}', [SemesterController::class, 'destroy']);
        });
    });
    // Lưu ý: Nếu Admin cũng cần quyền làm việc của GV, có thể để 'role:lecturer,admin'
    Route::prefix('lecturer')->middleware('role:lecturer,admin')->group(function () {
        // API chấm điểm, duyệt đề tài...
        // Route::post('/grading', [GradingController::class, 'store']);
    });

    // ==========================================
    // KHU VỰC SINH VIÊN (Middleware: role:student)
    // ==========================================
    Route::prefix('student')->middleware('role:student,admin,lecture')->group(function () {
        // API nộp bài, tham gia nhóm...
        // Route::post('/submission', [SubmissionController::class, 'store']);
    });
});