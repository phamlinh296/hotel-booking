<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\Notification;

class BookingController extends Controller
{
    // ===== Customer tạo booking =====
    public function store(Request $request)
    {
        $data = $request->validate([
            'hotel_id' => 'required|exists:hotels,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'guests' => 'required|integer|min:1',
        ]);

        $room = Room::findOrFail($data['room_id']);
        if ($data['guests'] > $room->max_guests) {
            throw ValidationException::withMessages([
                'guests' => "Number of guests exceeds room's max capacity."
            ]);
        }
        //Check room availability trước khi tạo để tránh double booking:
        $overlap = Booking::where('room_id', $data['room_id'])
            ->where('status', 'confirmed')
            ->where(function ($q) use ($data) {
                $q->whereBetween('check_in_date', [$data['check_in_date'], $data['check_out_date']])
                    ->orWhereBetween('check_out_date', [$data['check_in_date'], $data['check_out_date']]);
            })->exists();

        if ($overlap) {
            throw ValidationException::withMessages(['room_id' => 'Room is already booked for selected dates']);
        }

        $booking = Booking::create([
            'hotel_id' => $data['hotel_id'],
            'room_id' => $data['room_id'],
            'user_id' => Auth::id(),
            'check_in_date' => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'guests' => $data['guests'],
            'total_price' => ($room->price * (strtotime($data['check_out_date']) - strtotime($data['check_in_date'])) / 86400),
            'payment_status' => 'pending',
            'status' => 'pending', // thêm
        ]);

        //noti
        // Cho customer
        Notification::create([
            'user_id' => Auth::id(),
            'message' => 'Your booking #' . $booking->id . ' has been created and is pending payment',
            'is_read' => false,
        ]);

        // Cho host
        $hotel = $booking->hotel()->first();
        Notification::create([
            'user_id' => $hotel->author_id,
            'message' => 'A new booking #' . $booking->id . ' has been made for your hotel: ' . $hotel->title,
            'is_read' => false,
        ]);

        return response()->json([
            'message' => 'Booking created',
            'booking' => $booking
        ]);
    }

    // ===== Customer xem danh sách booking =====
    public function index()
    {
        $bookings = Booking::with('hotel')->where('user_id', Auth::id())->get();
        return response()->json($bookings);
    }

    // ===== Customer xem chi tiết booking =====
    public function show($id)
    {
        $booking = Booking::with(['hotel', 'room', 'payment'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json($booking);
    }

    // ===== Customer hủy booking =====
    public function cancel($id)
    {
        $booking = Booking::where('user_id', Auth::id())->findOrFail($id);
        $booking->update([
            'payment_status' => 'cancelled',
            'status' => 'cancelled'
        ]);

        // Trả phòng về available nếu chưa check-in
        $room = $booking->room;
        if ($room->status === 'booked') {
            $room->update(['status' => 'available']);
        }

        // Nếu payment đã thanh toán, trigger refund logic
        if ($booking->payment_status === 'paid') {
            $payment = $booking->payment;
            if ($payment) {
                $payment->update(['status' => 'refunded']);
                // Optional: gọi payment gateway refund API
            }
        }

        //noti
        // Cho customer
        Notification::create([
            'user_id' => Auth::id(),
            'message' => 'You have cancelled booking #' . $booking->id,
            'is_read' => false,
        ]);

        // Cho host
        $hotel = $booking->hotel()->first();
        Notification::create([
            'user_id' => $hotel->author_id,
            'message' => 'Booking #' . $booking->id . ' for your hotel ' . $hotel->title . ' has been cancelled',
            'is_read' => false,
        ]);

        return response()->json(['message' => 'Booking cancelled']);
    }

    // ===== Host xem booking liên quan hotels =====
    public function hostBookings()
    {
        $hotels = Hotel::where('author_id', Auth::id())->pluck('id');
        $bookings = Booking::with(['user', 'hotel'])->whereIn('hotel_id', $hotels)->get();

        return response()->json($bookings);
    }

    // ===== Admin xem tất cả booking =====
    public function adminBookings()
    {
        $bookings = Booking::with(['user', 'hotel'])->get();
        return response()->json($bookings);
    }
}
