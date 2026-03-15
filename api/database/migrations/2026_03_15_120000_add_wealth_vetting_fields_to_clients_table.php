<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->string('source_of_wealth')->nullable()->after('address');
            $table->string('source_of_funds')->nullable()->after('source_of_wealth');
            $table->string('annual_income_band')->nullable()->after('source_of_funds');
            $table->string('net_worth_band')->nullable()->after('annual_income_band');
            $table->string('investment_objective')->nullable()->after('net_worth_band');
            $table->string('wealth_profile_status')->default('pending')->after('investment_objective');

            $table->index('wealth_profile_status', 'clients_wealth_profile_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropIndex('clients_wealth_profile_status_idx');
            $table->dropColumn([
                'source_of_wealth',
                'source_of_funds',
                'annual_income_band',
                'net_worth_band',
                'investment_objective',
                'wealth_profile_status',
            ]);
        });
    }
};
