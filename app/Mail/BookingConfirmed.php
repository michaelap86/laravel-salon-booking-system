<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingConfirmed extends Mailable
{
    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your appointment is confirmed, '.$this->appointment->start_at->format('D, M j'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking', with: [
            'headline' => 'You\'re all booked!',
            'intro' => 'We can\'t wait to see you. Here are your appointment details.',
            'appointment' => $this->appointment->loadMissing(['client', 'service', 'staff']),
        ]);
    }
}
