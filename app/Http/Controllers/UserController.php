<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\HotelLike;
use App\Models\Bookmark;
use App\Models\RecentView;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    // Lấy thông tin profile
    public function getProfile()
    {
        return response()->json(Auth::user());
    }

    // Cập nhật profile
    // public function updateProfile(Request $request)
    // {
    //     $user = Auth::user();

    //     $request->validate([
    //         'name'         => 'nullable|string|max:255',
    //         'nickname'     => 'nullable|string|max:255',
    //         'date_of_birth' => 'nullable|date',
    //         'phone'        => 'nullable|string|max:20',
    //         'gender'       => 'nullable|in:male,female,other',
    //         'avatar'       => 'nullable|image|max:2048'
    //         // 'avatar'       => 'nullable|file'
    //     ]);

    //     if ($request->hasFile('avatar')) {
    //         Log::debug('Avatar file received', [
    //             'original_name' => $request->file('avatar')->getClientOriginalName(),
    //             'mime_type' => $request->file('avatar')->getMimeType()
    //         ]);

    //         $path = $request->file('avatar')->store('avatars', 'public');
    //         echo "Stored path: " . $path;
    //         $user->avatar = $path;
    //         Log::info('Avatar stored', ['path' => $path]);
    //     }

    //     if ($user instanceof User) {
    //         $user->fill($request->only([
    //             'name',
    //             'nickname',
    //             'date_of_birth',
    //             'phone',
    //             'gender',
    //         ]));
    //         $user->save();
    //     }
    //     Log::info('Profile updated successfully', ['user_id' => $user->id]);

    //     return response()->json([
    //         'message' => 'Cập nhật profile thành công',
    //         'user'    => $user
    //     ]);
    // }

    public function updateProfile(Request $request)
    {
        $user = User::find(Auth::id());

        // Validate input
        $request->validate([
            'name'         => 'nullable|string|max:255',
            'nickname'     => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'phone'        => 'nullable|string|max:20',
            'gender'       => 'nullable|string', // FE có thể gửi "Nam"/"Nữ"
            'avatar'       => 'nullable|image|max:2048',
            'count'        => 'nullable|integer|min:0',
        ]);

        // Map gender từ FE sang enum BE
        $genderMap = [
            'Nam'   => 'male',
            'Nữ'    => 'female',
            'male'  => 'male',
            'female' => 'female',
            'other' => 'other'
        ];

        if ($request->has('gender')) {
            $user->gender = $genderMap[$request->gender] ?? null;
        }

        // Upload avatar nếu có file
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        // Update các field còn lại
        $user->fill($request->only(['name', 'nickname', 'date_of_birth', 'phone', 'count']));
        $user->save();

        // Response camelCase giống JSON FE mong muốn
        return response()->json([
            'message'      => 'Cập nhật profile thành công',
            'id'           => $user->id,
            'displayName'  => $user->name,
            'nickname'     => $user->nickname,
            'firstName'    => $user->first_name,
            'lastName'     => $user->last_name,
            'email'        => $user->email,
            'gender'       => $user->gender,
            'avatar'       => $user->avatar,
            'bgImage'      => $user->background_image,
            'desc'         => $user->desc,
            'jobName'      => $user->job_title,
            'href'         => $user->href,
            'address'      => $user->address,
            'count'        => $user->count,
            'date_of_birth' => $user->date_of_birth,
            'phone'        => $user->phone,
        ]);
    }



    // GET /api/me/likes - Lấy danh sách likes của tôi
    public function myLikes(Request $request)
    {
        $likes = HotelLike::with('hotel:id,title')
            ->where('user_id', $request->user()->id)
            ->get()
            ->map(fn($like) => [
                'id' => $like->hotel->id,
                'title' => $like->hotel->title,
            ]);

        return response()->json($likes);
    }

    // GET /api/me/bookmarks - Lấy danh sách bookmarks của user
    public function myBookmarks(Request $request)
    {
        $bookmarks = Bookmark::with('hotel:id,title')
            ->where('user_id', $request->user()->id)
            ->get()
            ->map(fn($bm) => [
                'id' => $bm->hotel->id,
                'title' => $bm->hotel->title,
            ]);

        return response()->json($bookmarks);
    }

    // GET /api/me/recent-views - Lấy danh sách recent views
    public function myRecentViews(Request $request)
    {
        $views = RecentView::with('hotel:id,title')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('viewed_at')
            ->limit(20)
            ->get()
            ->map(fn($view) => [
                'id' => $view->hotel->id,
                'title' => $view->hotel->title,
                'viewed_at' => $view->viewed_at->toDateTimeString(),
            ]);

        return response()->json($views);
    }
}
