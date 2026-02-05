<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Auth\User;
use Illuminate\Support\Facades\Hash;
use App\Imports\StudentsImport;
use Maatwebsite\Excel\Facades\Excel;
class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // 1. Lọc theo Role (lecturer, student, admin)
        if ($request->has('role') && $request->role != 'all') {
            $query->where('role', $request->role);
        }

        // 2. Tìm kiếm
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        // Sắp xếp
        $query->orderBy('created_at', 'desc');

        if ($request->has('all') && $request->all == 'true') {
            $users = $query->get();
        } else {
            $users = $query->paginate(10);
        }

        return response()->json($users);
    }
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }
    public function store(Request $request)
    {
        // 1. Validate dữ liệu
        $validated = $request->validate([
            'code' => 'required|string|unique:users,code',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:lecturer,student',
        ], [
            'role.in' => 'Vai trò không hợp lệ (Chỉ chấp nhận: student hoặc lecturer).'
        ]);
        // 2. Tạo User
        $user = User::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            // Mật khẩu mặc định, GV sẽ đổi sau
            'password' => Hash::make('123456789'),
            'role' => $validated['role'], // Chắc chắn là 'lecturer' do validate ở trên
            'is_active' => true,
        ]);

        // (Tùy chọn) Gửi email thông báo cho GV tại đây...
        $roleName = $validated['role'] === 'lecturer' ? 'Giảng viên' : 'Sinh viên';
        return response()->json([
            'message' => "Đã tạo tài khoản $roleName thành công",
            'data' => $user
        ], 201);
    }

    public function update(Request $request, $id)
    {
        // 1. Tìm user (Nếu không thấy sẽ báo lỗi 404 ngay)
        $user = User::findOrFail($id);

        // 2. Validate dữ liệu (Logic cập nhật từng phần - Partial Update)
        $validated = $request->validate([
            // 'sometimes': Chỉ validate nếu dữ liệu ĐƯỢC GỬI LÊN
            // unique:...,$id: Cho phép trùng với chính user hiện tại (bỏ qua check trùng với bản thân)
            'code'      => 'sometimes|required|string|unique:users,code,' . $id,
            'email'     => 'sometimes|required|email|unique:users,email,' . $id,
            'name'      => 'sometimes|required|string|max:255',
            
            // Trường PHONE bạn cần thêm đây
            'phone'     => 'nullable|string|max:15', 

            'role'      => 'sometimes|required|in:lecturer,student,admin',
            'is_active' => 'sometimes|boolean',
            
            // Password: Có thể null (không đổi), nếu nhập thì phải ít nhất 6 ký tự
            'password'  => 'nullable|string|min:6',
        ], [
            // Tùy chỉnh thông báo lỗi tiếng Việt cho dễ hiểu
            'code.unique' => 'Mã số này đã được sử dụng.',
            'email.unique' => 'Email này đã tồn tại trong hệ thống.',
            'phone.max' => 'Số điện thoại không được quá 15 ký tự.'
        ]);

        // 3. Cập nhật thông tin (Trừ password xử lý riêng)
        // Hàm fill() sẽ tự động lấy các key có trong $validated để gán vào $user
        // Nó tự bỏ qua các trường không được gửi lên -> Rất an toàn
        $user->fill($validated);

        // 4. Xử lý riêng Mật khẩu
        // Chỉ khi nào Admin nhập password mới vào thì mới Hash và lưu
        // Nếu để trống hoặc không gửi -> Giữ nguyên pass cũ
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        } else {
            // Đảm bảo không bị ghi đè null vào password (dù hàm fill đã lo, nhưng thêm cho chắc)
            unset($user->password);
        }

        // 5. Lưu xuống DB
        $user->save();

        return response()->json([
            'message' => 'Cập nhật thông tin thành công!',
            'data' => $user
        ]);
    }
    public function destroy($id)
    {
        // Chặn không cho tự xóa chính mình
        if (auth()->id() == $id) {
            return response()->json(['message' => 'Bạn không thể tự xóa tài khoản của chính mình!'], 400);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Xóa người dùng thành công!']);
    }
    public function import(Request $request)
    {
        // 1. Validate file gửi lên
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048', // Chỉ nhận file excel, tối đa 2MB
        ]);

        try {
            // 2. Thực hiện Import
            Excel::import(new StudentsImport, $request->file('file'));

            return response()->json([
                'message' => 'Import danh sách sinh viên thành công!',
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Bắt lỗi nếu dữ liệu trong Excel bị trùng hoặc sai
            $failures = $e->failures();
            return response()->json([
                'message' => 'Dữ liệu trong file Excel bị lỗi.',
                'errors' => $failures
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
        }
    }
}