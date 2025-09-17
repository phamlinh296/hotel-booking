<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    // ===== Customer thanh toán =====
    public function store(Request $request)
    {
        $data = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'method' => 'required|string|in:credit,paypal,vnpay',
        ]);

        $booking = Booking::where('id', $data['booking_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($booking->payment_status === 'paid') {
            throw ValidationException::withMessages(['booking_id' => 'Booking is already paid']);
        }

        $payment = DB::transaction(function () use ($booking, $data) {
            // Create payment
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'amount' => $booking->total_price,
                'method' => $data['method'],
                'status' => 'completed', // pending nếu dùng gateway
                'transaction_ref' => 'TX' . strtoupper(uniqid())
            ]);

            // Update booking status & payment_status
            $booking->update([
                'payment_status' => 'paid',
                'status' => 'confirmed'
            ]);

            // Cập nhật Room status
            $booking->room->update(['status' => 'booked']);
            //Note: tích hợp payment gateway thực tế → nên có webhook để update Payment.status và Booking.status tự động.
            return $payment;
        });
        // Thêm Notification
        // Cho customer
        Notification::create([
            'user_id' => Auth::id(),
            'message' => 'Your payment for booking #' . $booking->id . ' was successful',
            'is_read' => false,
        ]);

        // Cho host
        $hotel = $booking->hotel()->first();
        Notification::create([
            'user_id' => $hotel->author_id,
            'message' => 'You have received a payment for booking #' . $booking->id . ' (hotel: ' . $hotel->title . ')',
            'is_read' => false,
        ]);

        return response()->json([
            'message' => 'Payment successful',
            'payment' => $payment
        ]);
    }

    // ===== Customer/Admin xem chi tiết payment =====
    public function show($id)
    {
        $payment = Payment::with('booking')->findOrFail($id);

        // Customer chỉ xem được payment của mình
        if (Auth::user()->role === 'user' && $payment->user_id !== Auth::id()) {
            abort(403, 'Forbidden');
        }

        return response()->json($payment);
    }
}
