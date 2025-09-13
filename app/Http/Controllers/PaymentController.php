<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
            'amount' => $booking->total_price,
            'method' => $data['method'],
            'status' => 'completed',
            'transaction_ref' => 'TX' . strtoupper(uniqid())
        ]);

        $booking->update(['payment_status' => 'paid']);

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
        if (Auth::user()->role === 'customer' && $payment->user_id !== Auth::id()) {
            abort(403, 'Forbidden');
        }

        return response()->json($payment);
    }
}
