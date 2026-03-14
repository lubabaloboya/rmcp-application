<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\DocumentChecklist;
use App\Models\DocumentType;
use App\Models\RmcpCase;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentGovernanceEnforcementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_blocks_case_submission_when_required_documents_are_missing(): void
    {
        Storage::fake('local');

        $role = Role::query()->create([
            'role_name' => 'Compliance Officer',
            'permissions' => ['cases.create', 'cases.submit', 'documents.create'],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $token = JWTAuth::fromUser($user);

        $company = Company::query()->create(['company_name' => 'Governance Co']);
        $client = Client::query()->create([
            'company_id' => $company->id,
            'client_type' => 'individual',
            'first_name' => 'Sam',
            'last_name' => 'Client',
        ]);

        $documentType = DocumentType::query()->create([
            'document_name' => 'South African ID Document',
            'category' => 'individual',
        ]);

        DocumentChecklist::query()->create([
            'client_type' => 'individual',
            'document_type_id' => $documentType->id,
            'required' => true,
        ]);

        $createdCase = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/cases', [
                'client_id' => $client->id,
                'title' => 'Onboarding Case',
                'description' => 'Testing governance gate.',
            ])
            ->assertStatus(201)
            ->json();

        $case = RmcpCase::query()->findOrFail($createdCase['id']);

        $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/cases/'.$case->id.'/submit')
            ->assertStatus(422)
            ->assertJsonPath('errors.documents.0', 'Cannot submit case for review. Required compliance documents are missing or expired.');

        $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/v1/clients/'.$client->id.'/documents', [
                'document_type_id' => $documentType->id,
                'file' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(201);

        $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/cases/'.$case->id.'/submit')
            ->assertStatus(200)
            ->assertJsonPath('status', 'pending_review');
    }
}
