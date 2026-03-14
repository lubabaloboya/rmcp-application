<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ClientsBulkImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_imports_clients_in_bulk_when_payload_is_valid(): void
    {
        $role = Role::query()->create(['role_name' => 'Individual User', 'permissions' => ['clients.create']]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $company = Company::query()->create(['company_name' => 'Bulk Co']);
        $token = JWTAuth::fromUser($user);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/clients/bulk', [
                'default_company_id' => $company->id,
                'rows' => [
                    [
                        'client_type' => 'individual',
                        'first_name' => 'Alpha',
                        'last_name' => 'One',
                        'email' => 'alpha1@example.local',
                    ],
                    [
                        'client_type' => 'corporate',
                        'email' => 'corp@example.local',
                    ],
                ],
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('created_count', 2);

        $this->assertDatabaseHas('clients', [
            'first_name' => 'Alpha',
            'client_type' => 'individual',
            'company_id' => $company->id,
        ]);

        $this->assertDatabaseHas('clients', [
            'email' => 'corp@example.local',
            'client_type' => 'company',
            'company_id' => $company->id,
        ]);
    }

    #[Test]
    public function it_returns_validation_errors_for_invalid_bulk_rows(): void
    {
        $role = Role::query()->create(['role_name' => 'Individual User', 'permissions' => ['clients.create']]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $token = JWTAuth::fromUser($user);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/clients/bulk', [
                'rows' => [
                    [
                        'client_type' => 'individual',
                        'email' => 'invalid-no-company@example.local',
                    ],
                ],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Bulk import validation failed.');
    }
}
