<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BeneficialOwner;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeneficialOwnerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['company_id' => ['nullable', 'integer']]);

        $query = BeneficialOwner::query()->latest('id');
        if ($request->filled('company_id')) {
            $query->where('company_id', (int) $request->input('company_id'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:50'],
            'ownership_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $currentTotal = (float) BeneficialOwner::query()
            ->where('company_id', (int) $validated['company_id'])
            ->sum('ownership_percentage');

        $requested = (float) $validated['ownership_percentage'];
        if (($currentTotal + $requested) > 100.0) {
            return response()->json([
                'message' => 'Total beneficial ownership cannot exceed 100% for this company.',
                'current_total' => round($currentTotal, 2),
                'requested' => round($requested, 2),
                'remaining' => max(0, round(100.0 - $currentTotal, 2)),
            ], 422);
        }

        $owner = BeneficialOwner::query()->create($validated);
        AuditLogService::log(auth('api')->id(), 'create', 'beneficial_owners', $owner->id);

        return response()->json($owner, 201);
    }

    public function update(Request $request, BeneficialOwner $beneficialOwner): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:50'],
            'ownership_percentage' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
        ]);

        if (array_key_exists('ownership_percentage', $validated)) {
            $currentTotalExcludingRecord = (float) BeneficialOwner::query()
                ->where('company_id', $beneficialOwner->company_id)
                ->where('id', '!=', $beneficialOwner->id)
                ->sum('ownership_percentage');

            $requested = (float) $validated['ownership_percentage'];
            if (($currentTotalExcludingRecord + $requested) > 100.0) {
                return response()->json([
                    'message' => 'Total beneficial ownership cannot exceed 100% for this company.',
                    'current_total' => round($currentTotalExcludingRecord, 2),
                    'requested' => round($requested, 2),
                    'remaining' => max(0, round(100.0 - $currentTotalExcludingRecord, 2)),
                ], 422);
            }
        }

        $beneficialOwner->update($validated);
        AuditLogService::log(auth('api')->id(), 'update', 'beneficial_owners', $beneficialOwner->id);

        return response()->json($beneficialOwner);
    }

    public function destroy(BeneficialOwner $beneficialOwner): JsonResponse
    {
        $id = $beneficialOwner->id;
        $beneficialOwner->delete();
        AuditLogService::log(auth('api')->id(), 'delete', 'beneficial_owners', $id);

        return response()->json(null, 204);
    }
}
