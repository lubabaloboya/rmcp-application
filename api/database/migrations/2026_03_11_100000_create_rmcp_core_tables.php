<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name')->unique();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('registration_number')->nullable()->index();
            $table->string('tax_number')->nullable()->index();
            $table->string('industry')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->enum('client_type', ['individual', 'company']);
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('id_number')->nullable()->index();
            $table->string('passport_number')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('risk_level')->default('low');
            $table->timestamps();
        });

        Schema::create('company_directors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->noActionOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('id_number')->nullable();
            $table->string('position')->nullable();
            $table->timestamps();
        });

        Schema::create('shareholders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->noActionOnDelete();
            $table->string('shareholder_name');
            $table->decimal('ownership_percentage', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('beneficial_owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->noActionOnDelete();
            $table->string('name');
            $table->string('id_number')->nullable();
            $table->decimal('ownership_percentage', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('document_name')->unique();
            $table->string('category');
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->noActionOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->noActionOnDelete();
            $table->string('file_path');
            $table->date('expiry_date')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->boolean('pep_status')->default(false);
            $table->boolean('country_risk')->default(false);
            $table->boolean('industry_risk')->default(false);
            $table->boolean('sanctions_check')->default(false);
            $table->unsignedInteger('risk_score')->default(0);
            $table->string('risk_level')->default('Low');
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->date('due_date')->nullable();
            $table->timestamps();
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_type');
            $table->text('description');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('severity')->default('low');
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('module');
            $table->unsignedBigInteger('record_id')->nullable();
            $table->timestamps();
        });

        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            $table->string('email_subject')->nullable();
            $table->text('email_body')->nullable();
            $table->string('sender');
            $table->string('receiver');
            $table->foreignId('linked_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('linked_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('risk_assessments');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('beneficial_owners');
        Schema::dropIfExists('shareholders');
        Schema::dropIfExists('company_directors');
        Schema::dropIfExists('clients');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['company_id']);
        });

        Schema::dropIfExists('companies');
        Schema::dropIfExists('roles');
    }
};
