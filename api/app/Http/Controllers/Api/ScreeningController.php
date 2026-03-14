<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ScreeningCheck;
use App\Services\RiskAssessmentAutomationService;
use App\Services\ScreeningIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScreeningController extends Controller
{
    public function __construct(
        private readonly ScreeningIntegrationService $screeningService,
        private readonly RiskAssessmentAutomationService $automationService,
    ) {
    }

    public function run(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'monitoring_cycle' => ['nullable', 'in:onboarding,ongoing,manual'],
        ]);

        $cycle = $validated['monitoring_cycle'] ?? 'manual';
        $screening = $this->screeningService->runForClient($client, $cycle);

        $assessment = $this->automationService->reassess($client, [
            'pep_status' => $screening['pep_status'],
            'sanctions_check' => $screening['sanctions_check'],
            'adverse_media_hit' => $screening['adverse_media_hit'],
        ], 'screening_'.$cycle);

        return response()->json([
            'screening' => $screening,
            'risk_assessment' => $assessment,
        ]);
    }

    public function history(Client $client): JsonResponse
    {
        $history = ScreeningCheck::query()
            ->where('client_id', $client->id)
            ->latest('checked_at')
            ->latest('id')
            ->paginate(20);

        return response()->json($history);
    }
}
