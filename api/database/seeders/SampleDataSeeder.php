<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['registration_number' => '2019/123456/07'],
            [
                'company_name' => 'Apex Compliance (Pty) Ltd',
                'tax_number' => '9922334455',
                'industry' => 'Financial Services',
                'address' => '45 Main Street, Cape Town',
                'phone' => '+27 21 555 0199',
                'email' => 'ops@apex-compliance.local',
            ]
        );

        $role = Role::query()->where('role_name', 'Compliance Officer')->first();

        $officer = User::firstOrCreate(
            ['email' => 'officer@rmcp.local'],
            [
                'name' => 'Compliance Officer',
                'password' => Hash::make('Officer@12345'),
                'role_id' => $role?->id,
                'company_id' => $company->id,
                'status' => 'active',
            ]
        );

        $existingClients = Client::query()->where('company_id', $company->id)->count();

        if ($existingClients > 0) {
            return;
        }

        $clients = [
            [
                'client_type' => 'individual',
                'first_name' => 'Thabo',
                'last_name' => 'Mokoena',
                'id_number' => '9001015800088',
                'passport_number' => null,
                'email' => 'thabo.mokoena@example.local',
                'phone' => '+27 82 100 2001',
                'address' => '12 Cedar Road, Johannesburg',
                'risk_level' => 'low',
                'risk_assessment' => [
                    'pep_status' => false,
                    'country_risk' => false,
                    'industry_risk' => false,
                    'sanctions_check' => false,
                    'risk_score' => 0,
                    'risk_level' => 'Low',
                ],
            ],
            [
                'client_type' => 'individual',
                'first_name' => 'Aisha',
                'last_name' => 'Patel',
                'id_number' => '8704120099082',
                'passport_number' => null,
                'email' => 'aisha.patel@example.local',
                'phone' => '+27 83 210 4421',
                'address' => '8 Oxford Street, Durban',
                'risk_level' => 'medium',
                'risk_assessment' => [
                    'pep_status' => false,
                    'country_risk' => true,
                    'industry_risk' => true,
                    'sanctions_check' => false,
                    'risk_score' => 50,
                    'risk_level' => 'Medium',
                ],
            ],
            [
                'client_type' => 'company',
                'first_name' => null,
                'last_name' => null,
                'id_number' => null,
                'passport_number' => null,
                'email' => 'directors@bluecrest-trading.local',
                'phone' => '+27 11 444 2211',
                'address' => '90 Rivonia Road, Sandton',
                'risk_level' => 'high',
                'risk_assessment' => [
                    'pep_status' => true,
                    'country_risk' => true,
                    'industry_risk' => false,
                    'sanctions_check' => true,
                    'risk_score' => 120,
                    'risk_level' => 'High',
                ],
            ],
        ];

        foreach ($clients as $entry) {
            $client = Client::create([
                'company_id' => $company->id,
                'client_type' => $entry['client_type'],
                'first_name' => $entry['first_name'],
                'last_name' => $entry['last_name'],
                'id_number' => $entry['id_number'],
                'passport_number' => $entry['passport_number'],
                'email' => $entry['email'],
                'phone' => $entry['phone'],
                'address' => $entry['address'],
                'risk_level' => $entry['risk_level'],
            ]);

            DB::table('risk_assessments')->insert([
                'client_id' => $client->id,
                'pep_status' => $entry['risk_assessment']['pep_status'],
                'country_risk' => $entry['risk_assessment']['country_risk'],
                'industry_risk' => $entry['risk_assessment']['industry_risk'],
                'sanctions_check' => $entry['risk_assessment']['sanctions_check'],
                'risk_score' => $entry['risk_assessment']['risk_score'],
                'risk_level' => $entry['risk_assessment']['risk_level'],
                'assessed_by' => $officer->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('tasks')->insert([
            [
                'assigned_to' => $officer->id,
                'title' => 'Review expiring KYC documents',
                'description' => 'Review client documents due within 30 days and request updates.',
                'status' => 'pending',
                'due_date' => now()->addDays(5)->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'assigned_to' => $officer->id,
                'title' => 'Escalate high-risk profile',
                'description' => 'Prepare escalation pack for risk committee.',
                'status' => 'pending',
                'due_date' => now()->addDays(2)->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('incidents')->insert([
            'incident_type' => 'Sanctions Flag Review',
            'description' => 'Potential sanctions match requires manual verification.',
            'reported_by' => $officer->id,
            'severity' => 'high',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
