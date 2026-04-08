<?php

namespace App\Jobs;

use App\Mail\BookingReceivedMail;
use App\Mail\BookingStatusChangedMail;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendBookingNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $bookingId,
        public string $type,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $booking = Booking::query()->find($this->bookingId);

        if (! $booking || ! $booking->customer_email) {
            return;
        }

        if ($this->type === 'created') {
            Mail::to($booking->customer_email)->send(new BookingReceivedMail($booking));

            return;
        }

        Mail::to($booking->customer_email)->send(new BookingStatusChangedMail($booking));
    }
}
