<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'permissions_catalog' => config('rmcp.permissions', []),
            'roles' => Role::query()->orderBy('role_name')->get(['id', 'role_name', 'permissions']),
        ]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'],
        ]);

        $catalog = config('rmcp.permissions', []);
        $permissions = array_values(array_filter($validated['permissions'], static fn (string $permission): bool => in_array($permission, $catalog, true) || $permission === '*'));

        $role->update(['permissions' => $permissions]);

        AuditLogService::log(auth('api')->id(), 'update_permissions', 'roles', $role->id);

        return response()->json($role->fresh());
    }
}
