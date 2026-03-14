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
