<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OverdueCasesEscalationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int, array{case_number: string, title: string, client_name: string, sla_due_at: string|null}> $cases
     */
    public function __construct(
        public string $adminName,
        public array $cases,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'RMCP Alert: Overdue Cases Escalated');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.overdue-cases-escalated');
    }
}
