<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyDirector;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyDirectorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['company_id' => ['nullable', 'integer']]);

        $query = CompanyDirector::query()->latest('id');
        if ($request->filled('company_id')) {
            $query->where('company_id', (int) $request->input('company_id'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:255'],
        ]);

        $director = CompanyDirector::query()->create($validated);
        AuditLogService::log(auth('api')->id(), 'create', 'company_directors', $director->id);

        return response()->json($director, 201);
    }

    public function update(Request $request, CompanyDirector $director): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:255'],
        ]);

        $director->update($validated);
        AuditLogService::log(auth('api')->id(), 'update', 'company_directors', $director->id);

        return response()->json($director);
    }

    public function destroy(CompanyDirector $director): JsonResponse
    {
        $id = $director->id;
        $director->delete();
        AuditLogService::log(auth('api')->id(), 'delete', 'company_directors', $id);

        return response()->json(null, 204);
    }
}
