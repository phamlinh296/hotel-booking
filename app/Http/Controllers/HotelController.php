<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\HotelImage;
use App\Models\Review;
use Illuminate\Http\Request;
use App\Dto\Requests\HotelRequest;

class HotelController extends Controller
{
    // ===== List Hotels =====
    public function index(Request $request)
    {
        $query = Hotel::query();

        if ($request->category_id) $query->where('listing_category_id', $request->category_id);
        if ($request->city) $query->where('location_city', 'like', "%{$request->city}%");
        if ($request->price_from) $query->where('price_from', '>=', $request->price_from);
        if ($request->rating) $query->where('rating_avg', '>=', $request->rating);
        if ($request->tag_id) $query->whereHas('tags', fn($q) => $q->where('tags.id', $request->tag_id));

        $hotels = $query->paginate(10);

        return response()->json($hotels);
    }

    // ===== Hotel Detail =====
    public function show($id)
    {
        $hotel = Hotel::with(['rooms', 'reviews', 'galleries', 'tags'])->findOrFail($id);
        // Ghi recent view
        //updateOrCreate → nếu user đã xem hotel này trước đó, chỉ update viewed_at mới.
        // Nếu chưa có, tạo mới record.
        if (auth()->check()) {
            \App\Models\RecentView::updateOrCreate(
                ['user_id' => auth()->id(), 'hotel_id' => $id],
                ['viewed_at' => now()]
            );
        }
        return response()->json($hotel);
    }

    // ===== Create Hotel =====
    public function store(HotelRequest $request)
    {
        $hotel = Hotel::create(array_merge(
            $request->validated(),
            ['author_id' => auth()->id()]
        ));
        return response()->json([
            'message' => 'Hotel created',
            'hotel' => $hotel
        ]);
    }

    // ===== Update Hotel =====
    public function update(HotelRequest $request, $id)
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->update($request->validated());

        return response()->json([
            'message' => 'Hotel updated',
            'hotel' => $hotel
        ]);
    }

    // ===== Delete Hotel =====
    public function destroy($id)
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->delete();

        return response()->json(['message' => 'Hotel deleted']);
    }

    // ===== Upload Hotel Images =====
    public function uploadImages(Request $request, $id)
    {
        $request->validate([
            'images.*' => 'required|image|max:2048'
        ]);

        $hotel = Hotel::findOrFail($id);
        $urls = [];

        foreach ($request->file('images', []) as $image) {
            $path = $image->store('hotels', 'public');
            $img = HotelImage::create([
                'hotel_id' => $hotel->id,
                'url' => asset("storage/$path")
            ]);
            $urls[] = $img->url;
        }

        return response()->json([
            'message' => 'Images uploaded',
            'urls' => $urls
        ]);
    }

    // ===== Reviews =====
    public function reviews($id)
    {
        $reviews = Review::where('hotel_id', $id)->with('user')->get();
        return response()->json($reviews);
    }

    public function addReview(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        $review = Review::create([
            'hotel_id' => $id,
            'user_id' => auth()->id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Review created',
            'review' => $review
        ]);
    }

    // ===== Bookmarks =====
    public function addBookmark($id)
    {
        \App\Models\Bookmark::firstOrCreate([
            'hotel_id' => $id,
            'user_id' => auth()->id()
        ]);
        return response()->json(['message' => 'Bookmark added']);
    }

    public function removeBookmark($id)
    {
        \App\Models\Bookmark::where('hotel_id', $id)->where('user_id', auth()->id())->delete();
        return response()->json(['message' => 'Bookmark removed']);
    }

    // ===== Likes =====
    public function addLike($id)
    {
        \App\Models\HotelLike::firstOrCreate([
            'hotel_id' => $id,
            'user_id' => auth()->id()
        ]);
        return response()->json(['message' => 'Hotel liked']);
    }

    public function removeLike($id)
    {
        \App\Models\HotelLike::where('hotel_id', $id)->where('user_id', auth()->id())->delete();
        return response()->json(['message' => 'Hotel unliked']);
    }

    // ===== Recent views =====
    public function recentViews()
    {
        $recent = \App\Models\RecentView::where('user_id', auth()->id())
            ->with('hotel')
            ->orderBy('viewed_at', 'desc')
            ->take(10)
            ->get();

        return response()->json($recent);
    }
}
