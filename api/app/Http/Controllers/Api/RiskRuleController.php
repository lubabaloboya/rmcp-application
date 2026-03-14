<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RiskScoringRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiskRuleController extends Controller
{
    public function index(): JsonResponse
    {
        $rules = RiskScoringRule::query()
            ->orderBy('id')
            ->get(['id', 'rule_key', 'label', 'weight', 'enabled', 'description']);

        return response()->json($rules);
    }

    public function update(Request $request, RiskScoringRule $riskRule): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'weight' => ['sometimes', 'required', 'integer', 'min:0', 'max:200'],
            'enabled' => ['sometimes', 'required', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $riskRule->update($validated);

        return response()->json($riskRule->fresh());
    }
}
