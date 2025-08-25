<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ProfileController extends Controller
{
    // Lấy thông tin profile
    public function getProfile()
    {
        return response()->json(Auth::user());
    }

    // Cập nhật profile
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'         => 'nullable|string|max:255',
            'nickname'     => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'phone'        => 'nullable|string|max:20',
            'gender'       => 'nullable|in:male,female,other',
            'avatar'       => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        if ($user instanceof User) {
            $user->fill($request->only([
                'name',
                'nickname',
                'date_of_birth',
                'phone',
                'gender',
            ]));
            $user->save();
        }

        return response()->json([
            'message' => 'Cập nhật profile thành công',
            'user'    => $user
        ]);
    }
}
