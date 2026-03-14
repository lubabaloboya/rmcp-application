<?php

namespace App\Console\Commands;

use App\Mail\ExpiringDocumentsAlertMail;
use App\Models\Document;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class NotifyExpiringDocuments extends Command
{
    protected $signature = 'rmcp:notify-expiring-documents {--days=30 : Number of days ahead to alert}';

    protected $description = 'Send expiring document notifications to admin users';

    public function handle(): int
    {
        $days = max((int) $this->option('days'), 1);

        $expiringDocuments = Document::query()
            ->with(['client:id,first_name,last_name,client_type', 'type:id,document_name'])
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString())
            ->orderBy('expiry_date')
            ->get();

        if ($expiringDocuments->isEmpty()) {
            $this->info('No expiring documents found.');

            return self::SUCCESS;
        }

        $adminUsers = User::query()
            ->whereNotNull('email')
            ->where('status', 'active')
            ->whereHas('role', static function ($query): void {
                $query->where('role_name', 'like', '%admin%');
            })
            ->get(['id', 'name', 'email']);

        if ($adminUsers->isEmpty()) {
            $this->warn('No active admin users found for notifications.');

            return self::SUCCESS;
        }

        $payload = $expiringDocuments->map(static function (Document $document): array {
            $clientName = trim(($document->client?->first_name ?? '').' '.($document->client?->last_name ?? ''));
            if ($clientName === '') {
                $clientName = ($document->client?->client_type ?? 'client').' #'.$document->client_id;
            }

            $expiryDate = $document->expiry_date;
            $daysLeft = null;
            if ($expiryDate !== null) {
                $daysLeft = now()->startOfDay()->diffInDays(Carbon::parse((string) $expiryDate)->startOfDay(), false);
            }

            return [
                'client_name' => $clientName,
                'document_type' => $document->type?->document_name ?? 'Document',
                'expiry_date' => optional($expiryDate)->toDateString(),
                'days_left' => $daysLeft,
            ];
        })->all();

        foreach ($adminUsers as $admin) {
            Mail::to($admin->email)->send(new ExpiringDocumentsAlertMail(
                adminName: $admin->name,
                documents: $payload,
                windowDays: $days,
            ));
        }

        $this->info('Expiring document alerts sent to '.$adminUsers->count().' admin user(s).');

        return self::SUCCESS;
    }
}