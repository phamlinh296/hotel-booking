<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingRefundMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    /**
     * Create a new message instance.
     *
     * @param Booking $booking
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Refund for Booking #{$this->booking->id}")
            ->view('emails.booking_refund') // file Blade email
            //tìm file resources/views/emails/booking_refund.blade.php.
            // File này chứa HTML/format text bạn muốn gửi cho user. Bạn có thể customize: màu sắc, logo, layout email, …
            // Tóm lại: Blade là “nội dung email”, class Mailable (BookingRefundMail) chỉ định nghĩa dữ liệu và subject.
            ->with([
                'bookingId' => $this->booking->id,
                'hotelName' => $this->booking->hotel->title,
                'checkIn' => $this->booking->check_in_date,
                'checkOut' => $this->booking->check_out_date,
                'totalPrice' => $this->booking->total_price,
            ]);
    }
}
