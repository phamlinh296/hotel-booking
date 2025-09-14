<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\HotelLike;
use App\Models\Bookmark;
use App\Models\RecentView;

class UserController extends Controller
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
