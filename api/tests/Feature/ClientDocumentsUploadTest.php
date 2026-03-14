<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\DocumentType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ClientDocumentsUploadTest extends TestCase
{
    use RefreshDatabase;

    private function seedDocumentContext(): array
    {
        Storage::fake('local');

        $role = Role::query()->create([
            'role_name' => 'Compliance Officer',
            'permissions' => ['documents.view', 'documents.create', 'documents.edit', 'documents.delete'],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $token = JWTAuth::fromUser($user);

        $company = Company::query()->create(['company_name' => 'Docs Co']);
        $client = Client::query()->create([
            'company_id' => $company->id,
            'client_type' => 'individual',
            'first_name' => 'Docs',
            'last_name' => 'Client',
        ]);

        $documentType = DocumentType::query()->create([
            'document_name' => 'South African ID Document',
            'category' => 'individual',
        ]);

        return [$token, $client, $documentType];
    }

    #[Test]
    public function it_uploads_and_lists_documents_for_a_client(): void
    {
        [$token, $client, $documentType] = $this->seedDocumentContext();

        $uploadResponse = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/v1/clients/'.$client->id.'/documents', [
                'document_type_id' => $documentType->id,
                'expiry_date' => now()->addYear()->toDateString(),
                'file' => UploadedFile::fake()->create('id-document.pdf', 120, 'application/pdf'),
            ]);

        $uploadResponse
            ->assertStatus(201)
            ->assertJsonPath('client_id', $client->id)
            ->assertJsonPath('document_type_id', $documentType->id);

        $this->assertDatabaseCount('documents', 1);

        $listResponse = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/clients/'.$client->id.'/documents');

        $listResponse
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.document_type_name', 'South African ID Document');
    }

    #[Test]
    public function it_replaces_an_existing_document_file(): void
    {
        [$token, $client, $documentType] = $this->seedDocumentContext();

        $created = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/v1/clients/'.$client->id.'/documents', [
                'document_type_id' => $documentType->id,
                'expiry_date' => now()->addDays(20)->toDateString(),
                'file' => UploadedFile::fake()->create('old-id.pdf', 110, 'application/pdf'),
            ])
            ->assertStatus(201)
            ->json();

        $documentId = (int) $created['id'];

        $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/v1/documents/'.$documentId.'/replace', [
                'file' => UploadedFile::fake()->create('new-id.pdf', 130, 'application/pdf'),
            ])
            ->assertStatus(200)
            ->assertJsonPath('id', $documentId);

        $this->assertDatabaseCount('documents', 1);
    }

    #[Test]
    public function it_deletes_an_uploaded_document(): void
    {
        [$token, $client, $documentType] = $this->seedDocumentContext();

        $created = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/v1/clients/'.$client->id.'/documents', [
                'document_type_id' => $documentType->id,
                'expiry_date' => now()->addDays(15)->toDateString(),
                'file' => UploadedFile::fake()->create('delete-me.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(201)
            ->json();

        $documentId = (int) $created['id'];

        $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/documents/'.$documentId)
            ->assertStatus(204);

        $this->assertDatabaseCount('documents', 0);
    }
}