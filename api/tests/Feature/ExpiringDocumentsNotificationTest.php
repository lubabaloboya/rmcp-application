<?php

namespace Tests\Feature;

use App\Mail\ExpiringDocumentsAlertMail;
use App\Models\Client;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpiringDocumentsNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_expiring_document_notifications_to_admin_users(): void
    {
        Mail::fake();

        $adminRole = Role::query()->create(['role_name' => 'Super Admin']);
        $officerRole = Role::query()->create(['role_name' => 'Compliance Officer']);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin-notify@example.local',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        User::factory()->create([
            'name' => 'Officer User',
            'email' => 'officer-notify@example.local',
            'role_id' => $officerRole->id,
            'status' => 'active',
        ]);

        $company = Company::query()->create(['company_name' => 'Notify Co']);
        $client = Client::query()->create([
            'company_id' => $company->id,
            'client_type' => 'individual',
            'first_name' => 'Expiry',
            'last_name' => 'Candidate',
        ]);

        $type = DocumentType::query()->create([
            'document_name' => 'Passport',
            'category' => 'individual',
        ]);

        Document::query()->create([
            'client_id' => $client->id,
            'company_id' => $company->id,
            'document_type_id' => $type->id,
            'file_path' => 'documents/test-passport.pdf',
            'expiry_date' => now()->addDays(7)->toDateString(),
            'uploaded_by' => $admin->id,
        ]);

        $this->artisan('rmcp:notify-expiring-documents --days=30')
            ->assertExitCode(0);

        Mail::assertSent(ExpiringDocumentsAlertMail::class, function (ExpiringDocumentsAlertMail $mail) use ($admin): bool {
            return $mail->hasTo($admin->email)
                && count($mail->documents) === 1
                && $mail->documents[0]['document_type'] === 'Passport';
        });

        Mail::assertSent(ExpiringDocumentsAlertMail::class, 1);
    }
}
