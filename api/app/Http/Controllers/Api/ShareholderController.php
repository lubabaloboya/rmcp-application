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
