<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'module' => ['nullable', 'string'],
            'action' => ['nullable', 'string'],
            'user_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $query = AuditLog::query()->with('user:id,name,email')->latest('id');

        if (! empty($validated['module'])) {
            $query->where('module', $validated['module']);
        }

        if (! empty($validated['action'])) {
            $query->where('action', $validated['action']);
        }

        if (! empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        return response()->json($query->paginate($validated['per_page'] ?? 25));
    }
}
