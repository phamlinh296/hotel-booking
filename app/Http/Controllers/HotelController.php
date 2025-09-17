<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\HotelImage;
use App\Models\Review;
use Illuminate\Http\Request;
use App\Dto\Requests\HotelRequest;
use App\Http\Resources\HotelResource;

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

        // return response()->json($hotels);
        return HotelResource::collection($hotels);
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
        // return response()->json($hotel);
        return new HotelResource($hotel);
    }

    // ===== Create Hotel =====
    public function store(HotelRequest $request)
    {
        $mappedData = HotelRequest::fromArray($request->validated());

        $hotel = Hotel::create(array_merge(
            $mappedData,
            ['author_id' => auth()->id()]
        ));

        return response()->json([
            'message' => 'Hotel created',
            'hotel' => new HotelResource($hotel)
        ]);
    }

    // ===== Update Hotel =====
    public function update(HotelRequest $request, $id)
    {
        $hotel = Hotel::findOrFail($id);
        // $hotel->update($request->validated());
        $mappedData = HotelRequest::fromArray($request->validated());
        $hotel->update($mappedData);


        return response()->json([
            'message' => 'Hotel updated',
            'hotel' => new HotelResource($hotel)
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

    public function updateReview(Request $request, $id)
    {
        $review = Review::where('id', $id)->where('user_id', auth()->id())->firstOrFail();

        $review->update($request->only('rating', 'comment'));

        return response()->json(['message' => 'Review updated']);
    }

    public function deleteReview($id)
    {
        $review = Review::where('id', $id)
            ->where(function ($q) {
                $q->where('user_id', auth()->id())->orWhereHas('user', fn($u) => $u->where('role', 'admin'));
            })->firstOrFail();

        $review->delete();

        return response()->json(['message' => 'Review deleted']);
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

    //search
    public function search(Request $request)
    {
        $request->validate([
            'location'   => 'nullable|string|max:255',
            'check_in'   => 'nullable|date',
            'check_out'  => 'nullable|date|after:check_in',
            'adults'     => 'nullable|integer|min:1',
            'children'   => 'nullable|integer|min:0',
            'infants'    => 'nullable|integer|min:0',
        ]);

        //tổng khách
        $adults   = (int) $request->input('adults', 1);
        $children = (int) $request->input('children', 0);
        $infants  = (int) $request->input('infants', 0);
        $totalGuests = $adults + $children + $infants;

        $checkIn  = $request->input('check_in');
        $checkOut = $request->input('check_out');

        //query
        $hotels = Hotel::query()
            //Filter theo location nếu user nhập
            ->when($request->filled('location'), function ($query) use ($request) {
                $location = $request->input('location');
                $query->where(function ($q) use ($location) {
                    $q->where('location_city', 'LIKE', "%{$location}%")
                        ->orWhere('location_country', 'LIKE', "%{$location}%")
                        ->orWhere('address', 'LIKE', "%{$location}%");
                });
            })
            //hotel sức chứa >= tổng khách
            ->when($totalGuests > 0, function ($query) use ($totalGuests) {
                $query->where('max_guests', '>=', $totalGuests);
            })
            //Filter theo khoảng ngày check-in / check-out
            ->when($checkIn && $checkOut, function ($query) use ($checkIn, $checkOut, $totalGuests) {
                $query->where(function ($q) use ($checkIn, $checkOut, $totalGuests) {
                    // Khách sạn chưa có booking trùng ngày
                    $q->whereDoesntHave('bookings', function ($sub) use ($checkIn, $checkOut) {
                        $sub->where(function ($b) use ($checkIn, $checkOut) {
                            $b->whereBetween('check_in_date', [$checkIn, $checkOut]) //Booking bắt đầu trong khoảng
                                ->orWhereBetween('check_out_date', [$checkIn, $checkOut]) // Booking kết thúc trong khoảng
                                ->orWhere(function ($nested) use ($checkIn, $checkOut) { // Booking bao trọn khoảng user muốn
                                    $nested->where('check_in_date', '<=', $checkIn)
                                        ->where('check_out_date', '>=', $checkOut);
                                });
                        });
                    })
                        // khách sạn có booking, kiểm tra còn đủ chỗ
                        ->orWhereHas('bookings', function ($sub) use ($checkIn, $checkOut, $totalGuests) {
                            $sub->selectRaw('hotel_id, SUM(guests) as total_booked')
                                ->where(function ($b) use ($checkIn, $checkOut) {
                                    $b->whereBetween('check_in_date', [$checkIn, $checkOut])
                                        ->orWhereBetween('check_out_date', [$checkIn, $checkOut])
                                        ->orWhere(function ($nested) use ($checkIn, $checkOut) {
                                            $nested->where('check_in_date', '<=', $checkIn)
                                                ->where('check_out_date', '>=', $checkOut);
                                        });
                                })
                                ->groupBy('hotel_id')
                                ->havingRaw('hotels.max_guests - total_booked >= ?', [$totalGuests]); //// Chỉ lấy khách sạn còn đủ sức chứa cho số khách yêu cầu
                        });
                });
            })
            ->with('author') //Load thông tin author (người tạo khách sạn)
            ->paginate(30);

        return response()->json([
            'message' => 'Danh sách khách sạn tìm được',
            'data'    => HotelResource::collection($hotels)
        ]);
    }
}
