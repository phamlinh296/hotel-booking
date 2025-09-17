<!DOCTYPE html>
<html>

<head>
    <title>Booking Refund</title>
</head>

<body>
    <p>Dear {{ $booking->user->name }},</p>

    <p>Your booking #{{ $bookingId }} at hotel "{{ $hotelName }}" has been refunded.</p>

    <p>Details:</p>
    <ul>
        <li>Check-in: {{ $checkIn }}</li>
        <li>Check-out: {{ $checkOut }}</li>
        <li>Total amount refunded: ${{ number_format($totalPrice, 2) }}</li>
    </ul>

    <p>Thank you for using our service.</p>
</body>

</html>