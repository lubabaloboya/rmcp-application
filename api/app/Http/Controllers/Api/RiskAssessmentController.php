<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\DocumentGovernanceService;
use App\Services\RiskAssessmentAutomationService;
use App\Services\ScreeningIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiskAssessmentController extends Controller
{
    public function __construct(
        private readonly RiskAssessmentAutomationService $automationService,
        private readonly ScreeningIntegrationService $screeningService,
        private readonly DocumentGovernanceService $documentGovernance,
    )
    {
    }

    public function store(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'pep_status' => ['required', 'boolean'],
            'country_risk' => ['required', 'boolean'],
            'industry_risk' => ['required', 'boolean'],
            'sanctions_check' => ['required', 'boolean'],
            'run_screening' => ['sometimes', 'boolean'],
        ]);

        $this->documentGovernance->assertClientCanProceed($client, 'save risk assessment');

        $runScreening = (bool) ($validated['run_screening'] ?? true);
        if ($runScreening) {
            $screening = $this->screeningService->runForClient($client, 'onboarding');
            $validated['pep_status'] = $validated['pep_status'] || $screening['pep_status'];
            $validated['sanctions_check'] = $validated['sanctions_check'] || $screening['sanctions_check'];
            $validated['adverse_media_hit'] = $screening['adverse_media_hit'];
        }

        $assessment = $this->automationService->reassess($client, $validated, 'manual_assessment');

        return response()->json($assessment);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json($client->riskAssessment);
    }
}
