<?php

namespace App\Services;

use App\Models\Client;
use App\Models\RiskAssessment;
use App\Models\ScreeningCheck;

class RiskAssessmentAutomationService
{
    public function __construct(
        private readonly RiskScoringService $riskScoringService,
    ) {
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public function reassess(Client $client, array $overrides = [], string $triggerReason = 'manual'): RiskAssessment
    {
        $existing = $client->riskAssessment;

        $latestChecks = ScreeningCheck::query()
            ->where('client_id', $client->id)
            ->whereIn('check_type', ['sanctions', 'pep', 'adverse_media'])
            ->latest('checked_at')
            ->latest('id')
            ->get()
            ->groupBy('check_type')
            ->map(fn ($group) => (bool) optional($group->first())->matched)
            ->all();

        $input = [
            'pep_status' => (bool) ($overrides['pep_status'] ?? $existing?->pep_status ?? false),
            'country_risk' => (bool) ($overrides['country_risk'] ?? $existing?->country_risk ?? false),
            'industry_risk' => (bool) ($overrides['industry_risk'] ?? $existing?->industry_risk ?? false),
            'sanctions_check' => (bool) ($overrides['sanctions_check'] ?? $existing?->sanctions_check ?? false),
            'adverse_media_hit' => (bool) ($overrides['adverse_media_hit'] ?? false),
        ];

        // Screening hits always elevate risk indicators unless an explicit override was supplied.
        if (! array_key_exists('pep_status', $overrides) && ! empty($latestChecks['pep'])) {
            $input['pep_status'] = true;
        }
        if (! array_key_exists('sanctions_check', $overrides) && ! empty($latestChecks['sanctions'])) {
            $input['sanctions_check'] = true;
        }
        if (! array_key_exists('adverse_media_hit', $overrides) && ! empty($latestChecks['adverse_media'])) {
            $input['adverse_media_hit'] = true;
        }

        $scoreData = $this->riskScoringService->calculate($input);

        $assessment = RiskAssessment::query()->updateOrCreate(
            ['client_id' => $client->id],
            [
                'pep_status' => $input['pep_status'],
                'country_risk' => $input['country_risk'],
                'industry_risk' => $input['industry_risk'],
                'sanctions_check' => $input['sanctions_check'],
                'risk_score' => $scoreData['risk_score'],
                'risk_level' => $scoreData['risk_level'],
                'explanation_json' => $scoreData['explanations'],
                'trigger_reason' => $triggerReason,
                'last_screened_at' => ScreeningCheck::query()->where('client_id', $client->id)->max('checked_at'),
                'assessed_by' => auth('api')->id(),
            ]
        );

        $client->update(['risk_level' => strtolower($scoreData['risk_level'])]);

        return $assessment;
    }
}
