<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\User;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
class AuthController extends Controller
{
    /**
     * API Đăng ký tài khoản
     */
    public function register(RegisterRequest $request)
    {
        // Dữ liệu đã được validate ở RegisterRequest
        $userData = $request->validated();

        // AN TOÀN: Luôn ép role là student cho luồng đăng ký công khai
        $userData['role'] = 'student';
        $userData['password'] = Hash::make($request->password);

        $user = User::create($userData);

        // (Tùy chọn) Tạo token luôn để user đăng nhập ngay sau khi đăng ký
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng ký thành công!',
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * API Đăng nhập
     */
    public function login(LoginRequest $request)
    {
        // Kiểm tra thông tin đăng nhập (Email & Password)
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Thông tin đăng nhập không chính xác.'
            ], 401);
        }

        // Lấy thông tin user từ DB
        $user = User::where('email', $request->email)->firstOrFail();

        // Kiểm tra xem tài khoản có bị khóa không (is_active)
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Tài khoản của bạn đã bị khóa.'
            ], 403);
        }

        // Tạo token (Xóa token cũ nếu muốn chỉ cho phép 1 thiết bị đăng nhập)
        // $user->tokens()->delete(); 
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $user->role,
            ]
        ]);
    }

    /**
     * API Đăng xuất (Thu hồi token)
     */
    public function logout(Request $request)
    {
        // Xóa token hiện tại đang dùng để gọi request này
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Đăng xuất thành công!'
        ]);
    }

    /**
     * API Lấy thông tin user hiện tại (Profile)
     */
    public function updateAvatarById(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();

        // Xử lý lưu ảnh
        if ($request->hasFile('avatar')) {
            // Xóa ảnh cũ nếu có
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Lưu ảnh mới vào thư mục 'avatars' trong ổ đĩa public
            $path = $request->file('avatar')->store('avatars', 'public');

            // Cập nhật Database
            $user->avatar = $path;
            $user->save();
            return response()->json([
                'message' => 'Cập nhật ảnh đại diện thành công!',
                'avatar' => $path
            ]);
        }
        return response()->json(['message' => 'Vui lòng chọn ảnh.'], 400);
    }
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:15',
        ], [
            'email.unique' => 'Email này đã tồn tại trong hệ thống.',
            'phone.max' => 'Số điện thoại không được quá 15 ký tự.'
        ]);
        $user->fill($validated);
        $user->save();

        return response()->json([
            'message' => 'Cập nhật thông tin thành công!',
            'data' => $user
        ]);
    }
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Mật khẩu hiện tại không chính xác.',
                'errors' => ['current_password' => ['Mật khẩu sai.']]
            ], 422);
        }

        //Cập nhật mật khẩu mới
        $user->password = Hash::make($request->new_password);
        $user->save();

        //Xóa tất cả các token khác để bắt đăng nhập lại ở thiết bị khác
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json([
            'message' => 'Đổi mật khẩu thành công!'
        ]);
    }

    // --- API 1: Gửi yêu cầu quên mật khẩu ---
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.exists' => 'Địa chỉ email này không tồn tại trong hệ thống.',
            'email.email' => 'Email không đúng định dạng.',
            'email.required' => 'Vui lòng nhập email.'
        ]);

        // Gửi link reset password (Laravel tự xử lý token và bảng password_reset_tokens)
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Đường dẫn đặt lại mật khẩu đã được gửi vào email của bạn.']);
        }

        // Nếu lỗi (ví dụ email không tồn tại)
        return response()->json(['message' => 'Không thể gửi email. Vui lòng kiểm tra lại địa chỉ email.'], 400);
    }

    // --- API 2: Đặt lại mật khẩu mới (Sau khi user bấm link) ---
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                // Có thể thêm dòng này để xóa hết token cũ (bảo mật)
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Mật khẩu đã được đặt lại thành công.']);
        }

        return response()->json(['message' => 'Token không hợp lệ hoặc đã hết hạn.'], 400);
    }
}