<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ApiSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function login_endpoint_is_throttled_after_repeated_failed_attempts(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'blocked@example.local',
                'password' => 'invalid-password',
            ]);

            $response->assertStatus(401);
        }

        $throttled = $this->postJson('/api/v1/auth/login', [
            'email' => 'blocked@example.local',
            'password' => 'invalid-password',
        ]);

        $throttled->assertStatus(429);
    }

    #[Test]
    public function user_cannot_view_client_outside_company_scope(): void
    {
        $viewerRole = Role::query()->create([
            'role_name' => 'Scoped Viewer',
            'permissions' => ['clients.view'],
        ]);

        $companyA = Company::query()->create(['company_name' => 'Tenant A']);
        $companyB = Company::query()->create(['company_name' => 'Tenant B']);

        $user = User::factory()->create([
            'role_id' => $viewerRole->id,
            'company_id' => $companyA->id,
        ]);

        $clientInOtherCompany = Client::query()->create([
            'company_id' => $companyB->id,
            'client_type' => 'individual',
            'first_name' => 'External',
            'last_name' => 'Client',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/clients/'.$clientInOtherCompany->id);

        $response->assertStatus(403);
    }

    #[Test]
    public function bulk_import_rejects_rows_outside_user_company_scope(): void
    {
        $creatorRole = Role::query()->create([
            'role_name' => 'Scoped Creator',
            'permissions' => ['clients.create'],
        ]);

        $companyA = Company::query()->create(['company_name' => 'Tenant A']);
        $companyB = Company::query()->create(['company_name' => 'Tenant B']);

        $user = User::factory()->create([
            'role_id' => $creatorRole->id,
            'company_id' => $companyA->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/clients/bulk', [
                'default_company_id' => $companyB->id,
                'rows' => [
                    [
                        'client_type' => 'individual',
                        'first_name' => 'Blocked',
                        'last_name' => 'Import',
                    ],
                ],
            ]);

        $response->assertStatus(403);
    }
}
