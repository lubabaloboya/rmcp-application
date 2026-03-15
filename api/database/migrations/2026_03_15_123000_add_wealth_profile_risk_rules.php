<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('risk_scoring_rules')) {
            return;
        }

        $now = now();

        DB::table('risk_scoring_rules')->updateOrInsert(
            ['rule_key' => 'wealth_profile_in_review'],
            [
                'label' => 'Wealth profile under review',
                'weight' => 15,
                'enabled' => true,
                'description' => 'Wealth profile requires enhanced due diligence review.',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('risk_scoring_rules')->updateOrInsert(
            ['rule_key' => 'wealth_profile_rejected'],
            [
                'label' => 'Wealth profile rejected',
                'weight' => 35,
                'enabled' => true,
                'description' => 'Wealth profile was rejected and indicates elevated risk.',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('risk_scoring_rules')) {
            return;
        }

        DB::table('risk_scoring_rules')
            ->whereIn('rule_key', ['wealth_profile_in_review', 'wealth_profile_rejected'])
            ->delete();
    }
};
