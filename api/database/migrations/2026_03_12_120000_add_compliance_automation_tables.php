<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('risk_scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_key')->unique();
            $table->string('label');
            $table->integer('weight')->default(0);
            $table->boolean('enabled')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('screening_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('check_type');
            $table->string('provider')->nullable();
            $table->string('status')->default('clear');
            $table->boolean('matched')->default(false);
            $table->unsignedInteger('score')->default(0);
            $table->json('metadata')->nullable();
            $table->string('monitoring_cycle')->default('onboarding');
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'check_type']);
            $table->index(['client_id', 'checked_at']);
        });

        Schema::create('document_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('client_type');
            $table->foreignId('document_type_id')->constrained('document_types')->cascadeOnDelete();
            $table->boolean('required')->default(true);
            $table->timestamps();

            $table->unique(['client_type', 'document_type_id']);
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->noActionOnDelete();
            $table->unsignedInteger('version_no')->default(1);
            $table->string('action');
            $table->string('file_path')->nullable();
            $table->string('file_hash')->nullable();
            $table->unsignedBigInteger('replaced_document_id')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('immutable_payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('risk_assessments', function (Blueprint $table) {
            $table->json('explanation_json')->nullable()->after('risk_level');
            $table->string('trigger_reason')->nullable()->after('explanation_json');
            $table->timestamp('last_screened_at')->nullable()->after('trigger_reason');
        });

        DB::table('risk_scoring_rules')->insert([
            [
                'rule_key' => 'pep_status',
                'label' => 'PEP status hit',
                'weight' => 40,
                'enabled' => true,
                'description' => 'Politically exposed person check was positive.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rule_key' => 'sanctions_check',
                'label' => 'Sanctions match',
                'weight' => 50,
                'enabled' => true,
                'description' => 'Client matched sanctions screening output.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rule_key' => 'country_risk',
                'label' => 'High-risk geography',
                'weight' => 30,
                'enabled' => true,
                'description' => 'Client profile indicates high-risk geography.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rule_key' => 'industry_risk',
                'label' => 'High-risk industry',
                'weight' => 20,
                'enabled' => true,
                'description' => 'Client belongs to an elevated-risk industry.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rule_key' => 'adverse_media_hit',
                'label' => 'Adverse media hit',
                'weight' => 25,
                'enabled' => true,
                'description' => 'Open-source adverse media checks found concerns.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('risk_assessments', function (Blueprint $table) {
            $table->dropColumn(['explanation_json', 'trigger_reason', 'last_screened_at']);
        });

        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('document_checklists');
        Schema::dropIfExists('screening_checks');
        Schema::dropIfExists('risk_scoring_rules');
    }
};
