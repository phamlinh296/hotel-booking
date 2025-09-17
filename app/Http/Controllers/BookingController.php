<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Mail\BookingRefundMail;

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

        $totalPrice = $room->price * (strtotime($data['check_out_date']) - strtotime($data['check_in_date'])) / 86400;
        //cho vào transanction 
        $booking = DB::transaction(function () use ($data, $totalPrice) {
            return Booking::create([
                'hotel_id' => $data['hotel_id'],
                'room_id' => $data['room_id'],
                'user_id' => Auth::id(),
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'guests' => $data['guests'],
                'total_price' => $totalPrice,
                'payment_status' => 'pending',
                'status' => 'pending',
            ]);
        });

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

    // ===== Customer xem danh sách booking (pagination + filter)=====
    public function index(Request $request)
    {
        $query = Booking::with('hotel')->where('user_id', Auth::id());

        // Filter theo status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter theo date range
        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('check_in_date', [$request->from, $request->to]);
        }

        $bookings = $query->orderBy('check_in_date', 'desc')->paginate($request->get('per_page', 10));
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
        //đk: k dc hủy trc checkin 24h
        $checkIn = Carbon::parse($booking->check_in_date);
        if ($checkIn->diffInHours(now()) < 24) {
            return response()->json([
                'message' => 'Cannot cancel booking less than 24 hours before check-in'
            ], 403);
        }

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'cancelled',
                'payment_status' => $booking->payment_status === 'paid' ? 'refunded' : 'pending',
            ]);

            // Update room status: Trả phòng về available nếu chưa check-in
            $room = $booking->room;
            if ($room->status === 'booked') {
                $room->update(['status' => 'available']);
            }

            // Refund email/notification: Nếu payment đã thanh toán, trigger refund logic
            if ($booking->payment_status === 'paid' && $booking->payment) {
                $booking->payment->update(['status' => 'refunded']);
                Notification::create([
                    'user_id' => Auth::id(),
                    'message' => "Your booking #{$booking->id} has been refunded",
                    'is_read' => false,
                ]);
                Mail::to(Auth::user()->email)->send(new BookingRefundMail($booking));
            }
        });

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

    // ===== Customer bổ sung thông tin cá nhân cho booking (B2 trong flow UI) =====
    public function updateCustomerInfo(Request $request, $id)
    {
        $booking = Booking::where('user_id', Auth::id())->findOrFail($id);

        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'phone'   => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'city'    => 'nullable|string|max:100',
        ]);

        $booking->update($data);

        return response()->json([
            'message' => 'Customer info updated',
            'booking' => $booking
        ]);
    }

    // ===== Host xác nhận booking =====
    public function confirm($id)
    {
        $booking = Booking::findOrFail($id);

        // Check quyền host
        if ($booking->hotel->author_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking cannot be confirmed'], 400);
        }

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'confirmed',
            ]);

            // Update room status
            $booking->room->update(['status' => 'booked']);

            // Thêm notification cho customer
            \App\Models\Notification::create([
                'user_id' => $booking->user_id,
                'message' => "Your booking #{$booking->id} has been confirmed",
                'is_read' => false,
            ]);
        });

        return response()->json(['message' => 'Booking confirmed']);
    }

    // ===== Host từ chối booking =====
    public function reject($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->hotel->author_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking cannot be rejected'], 400);
        }

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'cancelled',
            ]);

            // Thêm notification cho customer
            \App\Models\Notification::create([
                'user_id' => $booking->user_id,
                'message' => "Your booking #{$booking->id} has been rejected by the host",
                'is_read' => false,
            ]);
        });

        return response()->json(['message' => 'Booking rejected']);
    }


    // ===== Host xem booking liên quan hotels =====
    public function hostBookings(Request $request)
    {
        $hotels = Hotel::where('author_id', Auth::id())->pluck('id');
        $query = Booking::with(['user', 'hotel'])->whereIn('hotel_id', $hotels);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('check_in_date', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json($bookings);
    }

    // ===== Admin xem tất cả booking =====
    public function adminBookings(Request $request)
    {
        $query = Booking::with(['user', 'hotel']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('check_in_date', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json($bookings);
    }

    // ===== Payment callback/update status - Update trạng thái payment sau khi thanh toán thành công =====
    public function updatePaymentStatus(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $data = $request->validate([
            'payment_status' => 'required|in:pending,paid,cancelled,refunded'
        ]);

        DB::transaction(function () use ($booking, $data) {
            $booking->update([
                'payment_status' => $data['payment_status'],
            ]);

            if ($data['payment_status'] === 'paid') {
                $booking->update(['status' => 'confirmed']);
                $booking->room->update(['status' => 'booked']);

                \App\Models\Notification::create([
                    'user_id' => $booking->user_id,
                    'message' => "Your booking #{$booking->id} has been paid successfully",
                    'is_read' => false,
                ]);
            }
        });

        return response()->json(['message' => 'Payment status updated']);
    }
}
