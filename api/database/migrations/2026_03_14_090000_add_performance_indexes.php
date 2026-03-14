<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->index('expiry_date', 'documents_expiry_date_idx');
            $table->index(['client_id', 'document_type_id', 'id'], 'documents_client_type_id_idx');
        });

        if (Schema::hasTable('screening_checks')) {
            Schema::table('screening_checks', function (Blueprint $table) {
                $table->index(['matched', 'checked_at'], 'screening_checks_match_checked_idx');
            });
        }

        if (Schema::hasTable('document_checklists')) {
            Schema::table('document_checklists', function (Blueprint $table) {
                $table->index(['client_type', 'required'], 'document_checklists_type_required_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('documents_expiry_date_idx');
            $table->dropIndex('documents_client_type_id_idx');
        });

        if (Schema::hasTable('screening_checks')) {
            Schema::table('screening_checks', function (Blueprint $table) {
                $table->dropIndex('screening_checks_match_checked_idx');
            });
        }

        if (Schema::hasTable('document_checklists')) {
            Schema::table('document_checklists', function (Blueprint $table) {
                $table->dropIndex('document_checklists_type_required_idx');
            });
        }
    }
};
