<?php

namespace App\Services;

use App\Models\RiskScoringRule;

class RiskScoringService
{
    public function calculate(array $input): array
    {
        $score = 0;
        $explanations = [];

        $rules = $this->rules();

        foreach ($rules as $rule) {
            $triggered = (bool) ($input[$rule['rule_key']] ?? false);

            if (! $triggered) {
                continue;
            }

            $score += (int) $rule['weight'];
            $explanations[] = [
                'rule_key' => $rule['rule_key'],
                'label' => $rule['label'],
                'weight' => (int) $rule['weight'],
                'reason' => $rule['description'],
            ];
        }

        $mediumThreshold = 30;
        $highThreshold = 60;

        if ($score <= $mediumThreshold) {
            $level = 'Low';
        } elseif ($score <= $highThreshold) {
            $level = 'Medium';
        } else {
            $level = 'High';
        }

        return [
            'risk_score' => $score,
            'risk_level' => $level,
            'explanations' => $explanations,
        ];
    }

    private function rules(): array
    {
        if (! $this->rulesTableExists()) {
            return $this->defaultRules();
        }

        $rules = RiskScoringRule::query()
            ->where('enabled', true)
            ->orderBy('id')
            ->get(['rule_key', 'label', 'weight', 'description'])
            ->map(static fn (RiskScoringRule $rule): array => [
                'rule_key' => $rule->rule_key,
                'label' => $rule->label,
                'weight' => (int) $rule->weight,
                'description' => $rule->description,
            ])
            ->all();

        return $rules === [] ? $this->defaultRules() : $rules;
    }

    private function rulesTableExists(): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable('risk_scoring_rules');
        } catch (\Throwable) {
            return false;
        }
    }

    private function defaultRules(): array
    {
        return [
            [
                'rule_key' => 'pep_status',
                'label' => 'PEP status hit',
                'weight' => 40,
                'description' => 'Politically exposed person check was positive.',
            ],
            [
                'rule_key' => 'sanctions_check',
                'label' => 'Sanctions match',
                'weight' => 50,
                'description' => 'Client matched sanctions screening output.',
            ],
            [
                'rule_key' => 'country_risk',
                'label' => 'High-risk geography',
                'weight' => 30,
                'description' => 'Client profile indicates high-risk geography.',
            ],
            [
                'rule_key' => 'industry_risk',
                'label' => 'High-risk industry',
                'weight' => 20,
                'description' => 'Client belongs to an elevated-risk industry.',
            ],
            [
                'rule_key' => 'adverse_media_hit',
                'label' => 'Adverse media hit',
                'weight' => 25,
                'description' => 'Open-source adverse media checks found concerns.',
            ],
        ];
    }
}
