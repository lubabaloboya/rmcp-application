<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class IncidentsEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function incidents_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/incidents');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_returns_filtered_sorted_paginated_incidents_for_authenticated_user(): void
    {
        $role = Role::query()->create(['role_name' => 'Tester', 'permissions' => ['incidents.view', 'incidents.create', 'incidents.edit']]);
        $user = User::factory()->create(['role_id' => $role->id]);

        Incident::query()->create([
            'incident_type' => 'PEP Review',
            'description' => 'Open high severity incident',
            'reported_by' => $user->id,
            'severity' => 'high',
            'status' => 'open',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        Incident::query()->create([
            'incident_type' => 'KYC Follow-up',
            'description' => 'Resolved medium severity incident',
            'reported_by' => $user->id,
            'severity' => 'medium',
            'status' => 'resolved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/incidents?status=open&severity=high&sort_by=incident_type&sort_dir=asc&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 10)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.incident_type', 'PEP Review');
    }

    #[Test]
    public function it_creates_and_updates_an_incident(): void
    {
        $role = Role::query()->create(['role_name' => 'Tester', 'permissions' => ['incidents.view', 'incidents.create', 'incidents.edit']]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $token = JWTAuth::fromUser($user);

        $createResponse = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/incidents', [
                'incident_type' => 'Sanctions Alert',
                'description' => 'Potential sanctions match',
                'severity' => 'high',
                'status' => 'open',
            ]);

        $createResponse
            ->assertStatus(201)
            ->assertJsonPath('incident_type', 'Sanctions Alert')
            ->assertJsonPath('status', 'open');

        $incidentId = $createResponse->json('id');

        $updateResponse = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/incidents/'.$incidentId, [
                'status' => 'resolved',
            ]);

        $updateResponse
            ->assertOk()
            ->assertJsonPath('id', $incidentId)
            ->assertJsonPath('status', 'resolved');

        $this->assertDatabaseHas('incidents', [
            'id' => $incidentId,
            'status' => 'resolved',
        ]);
    }
}
