<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScreeningAutomationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_runs_screening_and_reassesses_risk(): void
    {
        $role = Role::query()->create([
            'role_name' => 'Compliance Officer',
            'permissions' => ['clients.edit'],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $token = JWTAuth::fromUser($user);

        $company = Company::query()->create(['company_name' => 'Screening Co']);
        $client = Client::query()->create([
            'company_id' => $company->id,
            'client_type' => 'individual',
            'first_name' => 'Ava',
            'last_name' => 'Risk',
            'email' => 'ava.risk@example.local',
            'id_number' => '9101015009087',
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/clients/'.$client->id.'/screenings/run', [
                'monitoring_cycle' => 'manual',
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonCount(3, 'screening.checks')
            ->assertJsonPath('risk_assessment.client_id', $client->id)
            ->assertJsonPath('risk_assessment.trigger_reason', 'screening_manual');

        $this->assertDatabaseCount('screening_checks', 3);
        $this->assertDatabaseHas('risk_assessments', [
            'client_id' => $client->id,
        ]);
    }
}
