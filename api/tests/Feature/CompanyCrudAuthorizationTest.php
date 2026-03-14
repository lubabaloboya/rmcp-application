<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class CompanyCrudAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function non_admin_cannot_create_company(): void
    {
        $role = Role::query()->create(['role_name' => 'Individual User', 'permissions' => ['companies.view']]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $token = JWTAuth::fromUser($user);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/companies', [
                'company_name' => 'Blocked Company',
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_create_update_and_delete_company(): void
    {
        $adminRole = Role::query()->create(['role_name' => 'Super Admin', 'permissions' => ['companies.create', 'companies.edit', 'companies.delete', 'companies.view']]);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $token = JWTAuth::fromUser($admin);

        $createResponse = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/companies', [
                'company_name' => 'Admin Created Co',
                'registration_number' => 'ADM-001',
            ]);

        $createResponse
            ->assertStatus(201)
            ->assertJsonPath('company_name', 'Admin Created Co');

        $companyId = $createResponse->json('id');

        $updateResponse = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/companies/'.$companyId, [
                'company_name' => 'Admin Updated Co',
            ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('company_name', 'Admin Updated Co');

        $deleteResponse = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/companies/'.$companyId);

        $deleteResponse->assertStatus(204);
    }
}
