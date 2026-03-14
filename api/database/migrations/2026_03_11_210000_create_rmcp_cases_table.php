<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rmcp_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('case_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('stage')->default('onboarding');
            $table->string('status')->default('draft');
            $table->foreignId('maker_id')->nullable()->constrained('users')->noActionOnDelete();
            $table->foreignId('checker_id')->nullable()->constrained('users')->noActionOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rmcp_cases');
    }
};
