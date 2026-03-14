<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $documentTypes = [
            ['South African ID Document', 'individual'],
            ['Passport', 'individual'],
            ['Driver\'s License', 'individual'],
            ['Proof of Address', 'individual'],
            ['Utility Bill', 'individual'],
            ['Bank Statement', 'individual'],
            ['Source of Funds Declaration', 'individual'],
            ['Tax Number', 'individual'],
            ['Company Registration Certificate', 'company'],
            ['Memorandum of Incorporation (MOI)', 'company'],
            ['Director ID Documents', 'company'],
            ['Shareholder Register', 'company'],
            ['Beneficial Ownership Declaration', 'company'],
            ['Tax Clearance Certificate', 'company'],
            ['Business Address Proof', 'company'],
            ['Bank Confirmation Letter', 'company'],
            ['FICA Client Information Form', 'compliance'],
            ['KYC Verification Form', 'compliance'],
            ['Risk Assessment Form', 'compliance'],
            ['PEP Declaration', 'compliance'],
            ['Sanctions Screening Report', 'compliance'],
            ['Client Due Diligence Report', 'compliance'],
            ['Enhanced Due Diligence Report', 'compliance'],
            ['Consent to Process Personal Information', 'popia'],
            ['Privacy Policy', 'popia'],
            ['Data Processing Agreement', 'popia'],
            ['Data Retention Policy', 'popia'],
            ['POPIA Compliance Checklist', 'popia'],
        ];

        foreach ($documentTypes as [$name, $category]) {
            DocumentType::firstOrCreate(
                ['document_name' => $name],
                ['category' => $category]
            );
        }
    }
}
