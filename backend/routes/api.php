<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminSignProfileController;
use App\Http\Controllers\Lecturers\AssignmentController;
use App\Http\Controllers\Lecturers\ClassController;
use App\Http\Controllers\Lecturers\ClassMaterialController;
use App\Http\Controllers\Lecturers\LecturerClassController;
use App\Http\Controllers\Lecturers\LecturerClassStudentController;
use App\Http\Controllers\Lecturers\LecturerDashboardController;
use App\Http\Controllers\Lecturers\LecturerGroupController;
use App\Http\Controllers\Public\PublicVerificationController;
use App\Http\Controllers\Shared\ClassStudentController;
use App\Http\Controllers\Shared\LecturerAssignmentController;
use App\Http\Controllers\Shared\StudentSubmissionController;
use App\Http\Controllers\Shared\SubmissionReviewController;
use App\Http\Controllers\Student\GroupController;
use App\Http\Controllers\Student\MessageController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Student\TaskCommentController;
use App\Http\Controllers\Student\TaskController;
use App\Http\Controllers\Students\StudentMaterialController;
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
// ── PUBLIC (không cần auth) ─────────────────────────
Route::prefix('public/verify')->group(function () {
    Route::get('/{serial}',       [PublicVerificationController::class, 'lookupBySerial']);
    Route::post('/file',          [PublicVerificationController::class, 'verifyFile']);
});
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
        Route::get('/document-categories', [LecturerSignController::class, 'getDocumentCategories']);
    });

    Route::middleware(['auth:sanctum'])->prefix('chatbot')->group(function () {
        Route::post('ask',                  [AdminChatbotController::class, 'ask']);
        Route::get ('history',              [AdminChatbotController::class, 'history']);
        Route::post('suggested-questions',  [AdminChatbotController::class, 'suggestedQuestions']);
        Route::post('{id}/feedback',        [AdminChatbotController::class, 'feedback']);
        Route::delete('history', [AdminChatbotController::class, 'clearHistory']);
    });
    // =================================================================
    // B. ADMIN AREA (Chỉ Admin - Quản lý hệ thống & Đào tạo)
    // =================================================================
    Route::prefix('admin')->middleware('role:admin')->group(function () {
         Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        // 1. Quản lý Người dùng (Users)
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::post('/users/import', [UserController::class, 'import']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // 2. Quản lý Cấu trúc Đào tạo (CRUD đầy đủ)
        // Đường dẫn sẽ là: /api/admin/faculties, /api/admin/majors...
        Route::post('/faculties/import', [FacultyController::class, 'import']);
        Route::get('/faculties/template', [FacultyController::class, 'downloadTemplate']);
        Route::apiResource('faculties', FacultyController::class);
        Route::post('/majors/import', [MajorController::class, 'import']);
        Route::get('/majors/template', [MajorController::class, 'downloadTemplate']);
        Route::apiResource('majors', MajorController::class);
        Route::post('/subjects/import', [SubjectController::class, 'import']);
        Route::get('/subjects/template', [SubjectController::class, 'downloadTemplate']);
        Route::apiResource('subjects', SubjectController::class);
        Route::post('/classes/import', [ModuleClassController::class, 'import']);
        Route::get('/classes/template', [ModuleClassController::class, 'downloadTemplate']);
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
        // Route::post  ('chatbot/ask',              [AdminChatbotController::class, 'ask']);
        // Route::get   ('chatbot/history',          [AdminChatbotController::class, 'history']);
        // Route::post  ('chatbot/{id}/feedback',    [AdminChatbotController::class, 'feedback']);

        // Route::post('chatbot/suggested-questions', [AdminChatbotController::class, 'suggestedQuestions']);

        // Xem tất cả yêu cầu + filter theo status
        // Route::get('/sign-requests/stats',          [AdminSignController::class, 'stats']);    // ← trước {id}
        Route::get('/sign-requests', [AdminSignController::class, 'index']);
        Route::get('/sign-requests/{id}', [AdminSignController::class, 'show']);
        // // Duyệt & chuyển cho GV
        // Route::post('/sign-requests/{id}/forward', [AdminSignController::class, 'forward']);
        // // Từ chối
        // Route::post('/sign-requests/{id}/reject', [AdminSignController::class, 'reject']);
        // // Phát hành file đã ký về cho SV
        // Route::post('/sign-requests/{id}/complete', [AdminSignController::class, 'complete']);
        // Sign profiles management
        Route::get('/sign-profiles',
            [AdminSignProfileController::class, 'index']);
        Route::get('/sign-profiles/stats',
            [AdminSignProfileController::class, 'stats']);
    
        // Deactivation requests
        Route::get('/deactivation-requests',
            [AdminSignProfileController::class, 'listRequests']);
        Route::get('/deactivation-requests/{id}',
            [AdminSignProfileController::class, 'showRequest']);
        Route::post('/deactivation-requests/{id}/approve',
            [AdminSignProfileController::class, 'approve']);
        Route::post('/deactivation-requests/{id}/reject',
            [AdminSignProfileController::class, 'reject']);
    });

    // =================================================================
    // C. LECTURER AREA (Khu vực Giảng viên)
    // =================================================================
    // Admin cũng có thể vào đây nếu cần (role:lecturer,admin)
        Route::prefix('lecturer')->middleware('role:lecturer,admin')->group(function () {
        // Ký số
        // Route::get('/sign-profile/categories',      [SignProfileController::class, 'categories']); // ← trước sign-profile
        // Route::get('/sign-profile',                 [SignProfileController::class, 'show']);
        // Route::post('/sign-profile',                [SignProfileController::class, 'upsert']);
        // Route::delete('/sign-profile',              [SignProfileController::class, 'deactivate']); // ← thêm
        Route::get('/dashboard', [LecturerDashboardController::class, 'index']);
        Route::get('/sign-requests',                [LecturerSignController::class, 'index']);
        Route::get('/sign-requests/{id}',           [LecturerSignController::class, 'show']);
        Route::get('/sign-requests/{id}/preview',   [LecturerSignController::class, 'preview']);
        Route::post('/sign-requests/{id}/sign',     [LecturerSignController::class, 'sign']);
        Route::post('/sign-requests/{id}/reject',   [LecturerSignController::class, 'reject']);

        Route::get('/classes', [ClassController::class, 'index']);
        Route::get('assignments/pending-count', [AssignmentController::class, 'pendingCount']);
        // Sinh viên trong lớp
        Route::get('/my-classes', [LecturerClassController::class, 'index']);
 
        // ✅ THÊM MỚI: CRUD SV trong lớp - dùng prefix /my-classes (TÁCH BIỆT với /classes cũ)
        Route::prefix('my-classes/{classId}/students')->group(function () {
            Route::get('/',                [LecturerClassStudentController::class, 'index']);
            Route::post('/',               [LecturerClassStudentController::class, 'store']);
            Route::post('/import',         [LecturerClassStudentController::class, 'import']);
            // Route::patch('/{studentId}',   [LecturerClassStudentController::class, 'update']);
            Route::delete('/{studentId}',  [LecturerClassStudentController::class, 'destroy']);
        });

        // Assignments nằm NGANG cấp với students, không lồng bên trong
        Route::prefix('classes/{classId}/assignments')->group(function () {
            Route::get('/',  [LecturerAssignmentController::class, 'index']);
            Route::post('/', [LecturerAssignmentController::class, 'store']);
        });

        Route::prefix('assignments/{id}')->group(function () {
            Route::get('/',    [LecturerAssignmentController::class, 'show']);
            Route::patch('/',  [LecturerAssignmentController::class, 'update']);
            Route::delete('/', [LecturerAssignmentController::class, 'destroy']);
        });

        Route::get('classes/{classId}/groups', [GroupController::class, 'index']);
        Route::get('submissions/{id}/download', [LecturerAssignmentController::class, 'download']);
        Route::get('/submissions/{id}/member-grades', [SubmissionReviewController::class, 'getMemberGrades']);

        Route::patch('submissions/{id}/review', [SubmissionReviewController::class, 'review']);
        // Duyệt toàn bộ bài đang pending của 1 đợt nộp
        // POST /api/lecturer/assignments/{id}/review-all
        // Body: { "status": "approved|rejected", "feedback": "Nhận xét chung" }
        Route::post('assignments/{id}/review-all', [SubmissionReviewController::class, 'reviewAll']);
        
        // Danh sách bài nộp kèm trạng thái duyệt
        // GET /api/lecturer/assignments/{id}/submissions?status=pending&type=group
        Route::get('assignments/{id}/submissions', [SubmissionReviewController::class, 'submissionList']);

        Route::post('groups/{groupId}/members',              [ClassController::class, 'addMemberToGroup']);     // Thêm TV
        Route::delete('groups/{groupId}/members/{memberId}', [GroupController::class, 'removeMember']); // Xóa TV

        // Danh sách nhóm theo lớp
        Route::get('/classes/{classId}/groups', [ClassController::class, 'groups']);
        // Cài đặt định mức TV/nhóm
        Route::patch('/classes/{id}/max-members-per-group',
            [ClassController::class, 'updateMaxMembersPerGroup']);
        
        // GV thêm SV vào nhóm (bypass max)
        Route::post('/groups/{id}/add-member',
            [ClassController::class, 'addMemberToGroup']);

        // Danh sách thành viên nhóm
        Route::get('/groups/{groupId}/members', [LecturerGroupController::class, 'members']);
        Route::get('/groups/{groupId}/tasks', [LecturerGroupController::class, 'tasks']);
        Route::get('/tasks/{taskId}',         [LecturerGroupController::class, 'taskDetail']);

         Route::prefix('sign-profile')->group(function () {
            Route::get('/',                  [SignProfileController::class, 'show']);
            Route::get('/history',           [SignProfileController::class, 'history']);
            //NEW: Parse cert preview
            Route::post('/parse-cert',       [SignProfileController::class, 'parseCertificate']);
            //Generate test key pair
            Route::post('/generate-test',    [SignProfileController::class, 'generateTest']);
            //Verify signing password
            Route::post('/verify-password',  [SignProfileController::class, 'verifySigningPassword']);
            // Register thay cho upsert cũ (đổi tên rõ ràng hơn)
            Route::post('/',                 [SignProfileController::class, 'register']);
            // Route::delete('/',               [SignProfileController::class, 'deactivate']);
            Route::post('/deactivation-request',
                [SignProfileController::class, 'requestDeactivation']);
        
            Route::get('/deactivation-requests',
                [SignProfileController::class, 'deactivationRequests']);
        
            Route::get('/deactivation-request/current',
                [SignProfileController::class, 'currentDeactivationRequest']);
        });

        Route::get('/classes/{classId}/materials',     [ClassMaterialController::class, 'index']);
        Route::post('/classes/{classId}/materials',    [ClassMaterialController::class, 'store']);
        Route::get('/classes/{classId}/copy-targets',  [ClassMaterialController::class, 'copyTargets']);
        
        Route::patch('/materials/{id}',                [ClassMaterialController::class, 'update']);
        Route::delete('/materials/{id}',               [ClassMaterialController::class, 'destroy']);
        Route::post('/materials/{id}/files',           [ClassMaterialController::class, 'addFiles']);
        Route::post('/materials/copy',                 [ClassMaterialController::class, 'copy']);
        
        Route::delete('/material-files/{id}',          [ClassMaterialController::class, 'deleteFile']);
        Route::get('/material-files/{id}/download',    [ClassMaterialController::class, 'downloadFile']);
    });

    // Student routes
    Route::middleware(['auth:sanctum', 'role:student,admin'])
        ->prefix('student')
        ->group(function () {
            Route::get('classes/{classId}/assignments',        [StudentSubmissionController::class, 'index']);
            Route::post('assignments/{id}/submit',             [StudentSubmissionController::class, 'submitIndividual']);
            Route::post('assignments/{id}/submit-group',       [StudentSubmissionController::class, 'submitGroup']);
            Route::get('assignments/{id}/submission/history',  [StudentSubmissionController::class, 'history']);
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
        Route::put('groups/{groupId}',         [GroupController::class, 'update']);       // Sửa nhóm (leader)
        Route::delete('groups/{groupId}',      [GroupController::class, 'destroy']);      // Xóa nhóm (leader)
 
        // Quản lý thành viên (chỉ leader)
        Route::post('groups/{groupId}/members',              [GroupController::class, 'addMember']);     // Thêm TV
        Route::delete('groups/{groupId}/members/{memberId}', [GroupController::class, 'removeMember']); // Xóa TV
        Route::post('groups/{groupId}/leave',                [GroupController::class, 'leave']);           // Rời nhóm (member)
        Route::post('groups/{groupId}/transfer-leader',      [GroupController::class, 'transferLeader']); // Chuyển quyền (leader)
 
        // ─────────────────────────────────────────
        // Chat nhóm
        // ─────────────────────────────────────────
        Route::get('groups/{groupId}/messages',  [MessageController::class, 'index']);   // Lấy tin nhắn
        Route::post('groups/{groupId}/messages', [MessageController::class, 'store']);   // Gửi tin nhắn
        Route::delete('/messages/{messageId}',    [MessageController::class, 'destroy']);
        Route::delete('/messages/attachments/{attachmentId}',
            [MessageController::class, 'deleteAttachment']);
        // ─────────────────────────────────────────
        // Quản lý công việc (Task Management)
        // ─────────────────────────────────────────
        Route::get('groups/{groupId}/tasks',  [TaskController::class, 'index']);         // DS task trong nhóm
        Route::post('groups/{groupId}/tasks', [TaskController::class, 'store']);         // Tạo task (leader)
        Route::post('groups/{groupId}/tasks/bulk',    [TaskController::class, 'storeBulk']);

        Route::get('tasks/{taskId}/comments',    [TaskCommentController::class, 'index']);   // DS bình luận
        Route::post('tasks/{taskId}/comments',   [TaskCommentController::class, 'store']);   // Thêm bình luận
        Route::put('comments/{commentId}',       [TaskCommentController::class, 'update']);  // Sửa bình luận
        Route::delete('comments/{commentId}',    [TaskCommentController::class, 'destroy']); // Xóa bình luận
        Route::delete('/comments/attachments/{attachmentId}',[TaskCommentController::class, 'deleteAttachment']);
        //Task
        Route::post('tasks/{id}/submit-review', [TaskController::class, 'submitForReview']);
        Route::post('tasks/{id}/approve',       [TaskController::class, 'approve']);
        Route::post('tasks/{id}/reject',        [TaskController::class, 'reject']);
        Route::patch('tasks/{taskId}/status',   [TaskController::class, 'updateStatus']);

        // Đặt SAU các route cụ thể
        Route::get('tasks/{taskId}',    [TaskController::class, 'show']);
        Route::put('tasks/{taskId}',    [TaskController::class, 'update']);
        Route::delete('tasks/{taskId}', [TaskController::class, 'destroy']);

        Route::get('/classes/{classId}/materials',     [StudentMaterialController::class, 'index']);
        Route::get('/material-files/{id}/download',    [StudentMaterialController::class, 'downloadFile']);
    });
});