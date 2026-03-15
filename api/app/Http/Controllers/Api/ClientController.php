<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\RiskAssessmentAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function __construct(private readonly RiskAssessmentAutomationService $riskAutomation)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = max(5, min($perPage, 100));

        return response()->json(Client::query()->latest('id')->simplePaginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'client_type' => ['required', 'in:individual,company'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:50'],
            'passport_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'source_of_wealth' => ['nullable', 'string', 'max:255'],
            'source_of_funds' => ['nullable', 'string', 'max:255'],
            'annual_income_band' => ['nullable', 'string', 'max:100'],
            'net_worth_band' => ['nullable', 'string', 'max:100'],
            'investment_objective' => ['nullable', 'string', 'max:255'],
            'wealth_profile_status' => ['nullable', 'in:pending,in_review,approved,rejected'],
        ]);

        $client = Client::create($validated);
        $this->riskAutomation->reassess($client, [], 'profile_created');

        return response()->json($client, 201);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'default_company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'rows' => ['required', 'array', 'min:1', 'max:1000'],
            'rows.*' => ['required', 'array'],
        ]);

        $errors = [];
        $toCreate = [];

        foreach ($validated['rows'] as $index => $row) {
            $payload = [
                'company_id' => $row['company_id'] ?? $validated['default_company_id'] ?? null,
                'client_type' => $row['client_type'] ?? null,
                'first_name' => $row['first_name'] ?? null,
                'last_name' => $row['last_name'] ?? null,
                'id_number' => $row['id_number'] ?? null,
                'passport_number' => $row['passport_number'] ?? null,
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'] ?? null,
                'address' => $row['address'] ?? null,
                'source_of_wealth' => $row['source_of_wealth'] ?? null,
                'source_of_funds' => $row['source_of_funds'] ?? null,
                'annual_income_band' => $row['annual_income_band'] ?? null,
                'net_worth_band' => $row['net_worth_band'] ?? null,
                'investment_objective' => $row['investment_objective'] ?? null,
                'wealth_profile_status' => $row['wealth_profile_status'] ?? 'pending',
            ];

            if (($payload['client_type'] ?? null) === 'corporate') {
                $payload['client_type'] = 'company';
            }

            $rowValidator = Validator::make($payload, [
                'company_id' => ['nullable', 'integer', 'exists:companies,id'],
                'client_type' => ['required', 'in:individual,company'],
                'first_name' => ['nullable', 'string', 'max:255'],
                'last_name' => ['nullable', 'string', 'max:255'],
                'id_number' => ['nullable', 'string', 'max:50'],
                'passport_number' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email'],
                'phone' => ['nullable', 'string', 'max:50'],
                'address' => ['nullable', 'string'],
                'source_of_wealth' => ['nullable', 'string', 'max:255'],
                'source_of_funds' => ['nullable', 'string', 'max:255'],
                'annual_income_band' => ['nullable', 'string', 'max:100'],
                'net_worth_band' => ['nullable', 'string', 'max:100'],
                'investment_objective' => ['nullable', 'string', 'max:255'],
                'wealth_profile_status' => ['nullable', 'in:pending,in_review,approved,rejected'],
            ]);

            if ($rowValidator->fails()) {
                $errors[] = [
                    'row' => $index + 1,
                    'errors' => $rowValidator->errors()->toArray(),
                ];
                continue;
            }

            $toCreate[] = $rowValidator->validated();
        }

        if (! empty($errors)) {
            return response()->json([
                'message' => 'Bulk import validation failed.',
                'errors' => $errors,
            ], 422);
        }

        DB::transaction(function () use ($toCreate): void {
            foreach ($toCreate as $payload) {
                $client = Client::query()->create($payload);
                $this->riskAutomation->reassess($client, [], 'profile_created_bulk');
            }
        });

        return response()->json([
            'message' => 'Bulk clients import successful.',
            'created_count' => count($toCreate),
        ], 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json($client->load('riskAssessment'));
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'source_of_wealth' => ['nullable', 'string', 'max:255'],
            'source_of_funds' => ['nullable', 'string', 'max:255'],
            'annual_income_band' => ['nullable', 'string', 'max:100'],
            'net_worth_band' => ['nullable', 'string', 'max:100'],
            'investment_objective' => ['nullable', 'string', 'max:255'],
            'wealth_profile_status' => ['nullable', 'in:pending,in_review,approved,rejected'],
            'risk_level' => ['nullable', 'in:low,medium,high'],
        ]);

        $client->update($validated);
        $this->riskAutomation->reassess($client->fresh(), [], 'profile_updated');

        return response()->json($client->fresh());
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json(null, 204);
    }
}
