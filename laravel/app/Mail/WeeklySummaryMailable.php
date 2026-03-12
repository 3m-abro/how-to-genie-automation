<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklySummaryMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array{posts_published: int, top_performer_title: string, top_performer_url: string, revenue_estimate: float, streak: string|null} $summary
     */
    public function __construct(
        public array $summary
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'HowTo-Genie Weekly Summary',
            from: config('mail.from.address'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-summary'
        );
    }
}
