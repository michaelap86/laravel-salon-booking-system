<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingReminder extends Mailable
{
    public function __construct(public Appointment $appointment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reminder: your visit is coming up');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking', with: [
            'headline' => 'See you soon!',
            'intro' => 'A friendly reminder about your upcoming appointment.',
            'appointment' => $this->appointment->loadMissing(['client', 'service', 'staff']),
        ]);
    }
}
