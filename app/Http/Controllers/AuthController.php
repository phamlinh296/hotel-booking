<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Helpers\NotiHelper;

class AuthController extends Controller
{
    // Đăng ký
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('api_token')->plainTextToken;
        // Gửi noti
        NotiHelper::push($user->id, "Chào mừng bạn đến với hệ thống Hotel Booking!");


        return response()->json([
            'message' => 'Đăng ký thành công',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }

    // Đăng nhập
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email hoặc mật khẩu không đúng.'],
            ]);
        }

        // Xóa token cũ nếu muốn (tránh login nhiều device)
        $user->tokens()->delete();

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công',
            'user'    => $user,
            'token'   => $token,
        ]);
    }

    // Đăng xuất
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Đăng xuất thành công']);
    }

    // Lấy profile
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    // Cập nhật profile
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'   => 'sometimes|string|max:255',
            'phone'  => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:255',
        ]);

        $user->update($request->only(['name', 'phone', 'avatar']));

        return response()->json([
            'message' => 'Cập nhật profile thành công',
            'user'    => $user,
        ]);
    }

    // Đổi mật khẩu
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Mật khẩu cũ không đúng'], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);
        // Gửi noti
        NotiHelper::push($user->id, "Bạn đã đổi mật khẩu thành công!");

        return response()->json(['message' => 'Đổi mật khẩu thành công']);
    }

    // Quên mật khẩu - gửi link reset
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Email không tồn tại'], 404);
        }

        // Tạo token
        $token = Str::random(64);

        // Lưu vào bảng password_resets
        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        // Tạo URL reset (frontend hoặc API endpoint)
        $resetUrl = config('app.frontend_url') . "/reset-password?token=$token&email=" . urlencode($user->email);

        // Gửi mail (nếu có setup Mail)
        // Mail::to($user->email)->send(new ResetPasswordMail($resetUrl));

        // Test API dev: trả luôn token + link để Postman reset
        return response()->json([
            'message' => 'Link reset password đã được tạo',
            'reset_url' => $resetUrl,
            'token' => $token
        ]);
    }

    // Reset mật khẩu
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $record = DB::table('password_resets')->where('email', $request->email)->first();
        if (!$record) {
            return response()->json(['message' => 'Token không hợp lệ'], 400);
        }

        // Kiểm tra token hash
        if (!Hash::check($request->token, $record->token)) {
            return response()->json(['message' => 'Token không hợp lệ'], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User không tồn tại'], 404);
        }

        // Cập nhật mật khẩu
        $user->password = Hash::make($request->password);
        $user->save();

        // Xóa token sau khi reset
        DB::table('password_resets')->where('email', $request->email)->delete();

        // Logout tất cả session cũ
        $user->tokens()->delete();
        // Gửi noti
        NotiHelper::push($user->id, "Mật khẩu của bạn đã được reset thành công!");

        return response()->json(['message' => 'Đặt lại mật khẩu thành công']);
    }
}
