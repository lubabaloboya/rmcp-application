<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpiringDocumentsAlertMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int, array{client_name: string, document_type: string, expiry_date: string|null, days_left: int|null}> $documents
     */
    public function __construct(
        public string $adminName,
        public array $documents,
        public int $windowDays,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'RMCP Alert: Documents Expiring Soon',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.documents-expiring',
        );
    }
}