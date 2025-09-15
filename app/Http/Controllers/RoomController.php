<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\Hotel;
use App\Helpers\NotiHelper;

class RoomController extends Controller
{
    // GET /api/hotels/{hotel_id}/rooms
    public function index($hotel_id)
    {
        $hotel = Hotel::findOrFail($hotel_id);
        $rooms = $hotel->rooms()->get(['id', 'name', 'price']);
        return response()->json($rooms);
    }

    // POST /api/hotels/{hotel_id}/rooms
    public function store(Request $request, $hotel_id)
    {
        $hotel = Hotel::findOrFail($hotel_id);

        // Optional: check if auth()->id() is owner of hotel or admin
        if (auth()->user()->role !== 'admin' && $hotel->author_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'max_guests' => 'required|integer',
            'bed_count' => 'nullable|integer',
            'bathroom_count' => 'nullable|integer',
            'status' => 'nullable|string|in:available,booked,maintenance',
        ]);

        $data['hotel_id'] = $hotel->id;

        $room = Room::create($data);
        // Gửi noti cho owner hotel
        NotiHelper::push($hotel->author_id, "Phòng '{$room->name}' vừa được thêm vào khách sạn '{$hotel->title}'.");

        return response()->json([
            'message' => 'Room created',
            'room' => $room
        ]);
    }

    // PUT /api/rooms/{id}
    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        // Optional: check owner or admin
        if (auth()->user()->role !== 'admin' && $room->hotel->author_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric',
            'max_guests' => 'sometimes|integer',
            'bed_count' => 'sometimes|integer',
            'bathroom_count' => 'sometimes|integer',
            'status' => 'nullable|string|in:available,booked,maintenance',
        ]);

        $room->update($data);
        // Gửi noti
        NotiHelper::push($room->hotel->author_id, "Phòng '{$room->name}' của khách sạn '{$room->hotel->title}' đã được cập nhật.");


        return response()->json([
            'message' => 'Room updated',
            'room' => $room
        ]);
    }

    // DELETE /api/rooms/{id}
    public function destroy($id)
    {
        $room = Room::findOrFail($id);

        // Optional: check owner or admin
        if (auth()->user()->role !== 'admin' && $room->hotel->author_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        //Khi xóa phòng có booking confirmed → check tránh xóa nhầm:
        if ($room->bookings()->where('status', 'confirmed')->exists()) {
            return response()->json(['message' => 'Cannot delete room with confirmed bookings'], 400);
        }


        $room->delete();
        // Gửi noti
        NotiHelper::push($room->hotel->author_id, "Phòng '{$room->name}' của khách sạn '{$room->hotel->title}' đã bị xóa.");


        return response()->json(['message' => 'Room deleted']);
    }
}
