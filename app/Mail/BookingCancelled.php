<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingCancelled extends Mailable
{
    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your appointment was cancelled');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking', with: [
            'headline' => 'Appointment cancelled',
            'intro' => 'Your appointment has been cancelled. We hope to see you again soon.',
            'appointment' => $this->appointment->loadMissing(['client', 'service', 'staff']),
        ]);
    }
}
