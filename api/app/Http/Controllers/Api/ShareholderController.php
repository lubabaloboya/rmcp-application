<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shareholder;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareholderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['company_id' => ['nullable', 'integer']]);

        $query = Shareholder::query()->latest('id');
        if ($request->filled('company_id')) {
            $query->where('company_id', (int) $request->input('company_id'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'shareholder_name' => ['required', 'string', 'max:255'],
            'ownership_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $currentTotal = (float) Shareholder::query()
            ->where('company_id', (int) $validated['company_id'])
            ->sum('ownership_percentage');

        $requested = (float) $validated['ownership_percentage'];
        if (($currentTotal + $requested) > 100.0) {
            return response()->json([
                'message' => 'Total shareholder ownership cannot exceed 100% for this company.',
                'current_total' => round($currentTotal, 2),
                'requested' => round($requested, 2),
                'remaining' => max(0, round(100.0 - $currentTotal, 2)),
            ], 422);
        }

        $shareholder = Shareholder::query()->create($validated);
        AuditLogService::log(auth('api')->id(), 'create', 'shareholders', $shareholder->id);

        return response()->json($shareholder, 201);
    }

    public function update(Request $request, Shareholder $shareholder): JsonResponse
    {
        $validated = $request->validate([
            'shareholder_name' => ['sometimes', 'required', 'string', 'max:255'],
            'ownership_percentage' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
        ]);

        if (array_key_exists('ownership_percentage', $validated)) {
            $currentTotalExcludingRecord = (float) Shareholder::query()
                ->where('company_id', $shareholder->company_id)
                ->where('id', '!=', $shareholder->id)
                ->sum('ownership_percentage');

            $requested = (float) $validated['ownership_percentage'];
            if (($currentTotalExcludingRecord + $requested) > 100.0) {
                return response()->json([
                    'message' => 'Total shareholder ownership cannot exceed 100% for this company.',
                    'current_total' => round($currentTotalExcludingRecord, 2),
                    'requested' => round($requested, 2),
                    'remaining' => max(0, round(100.0 - $currentTotalExcludingRecord, 2)),
                ], 422);
            }
        }

        $shareholder->update($validated);
        AuditLogService::log(auth('api')->id(), 'update', 'shareholders', $shareholder->id);

        return response()->json($shareholder);
    }

    public function destroy(Shareholder $shareholder): JsonResponse
    {
        $id = $shareholder->id;
        $shareholder->delete();
        AuditLogService::log(auth('api')->id(), 'delete', 'shareholders', $id);

        return response()->json(null, 204);
    }
}
