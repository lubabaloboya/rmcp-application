<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:50'],
            'severity' => ['nullable', 'string', 'max:50'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date'],
            'sort_by' => ['nullable', 'in:created_at,severity,status,incident_type'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = Incident::query()->with('reporter:id,name');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['severity'])) {
            $query->where('severity', $validated['severity']);
        }

        if (! empty($validated['created_from'])) {
            $query->whereDate('created_at', '>=', $validated['created_from']);
        }

        if (! empty($validated['created_to'])) {
            $query->whereDate('created_at', '<=', $validated['created_to']);
        }

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 20;

        $incidents = $query
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($incidents);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'incident_type' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'severity' => ['required', 'in:low,medium,high'],
            'status' => ['nullable', 'in:open,pending,resolved,closed'],
        ]);

        $incident = Incident::query()->create([
            ...$validated,
            'status' => $validated['status'] ?? 'open',
            'reported_by' => auth('api')->id(),
        ]);

        return response()->json($incident->load('reporter:id,name'), 201);
    }

    public function update(Request $request, Incident $incident): JsonResponse
    {
        $validated = $request->validate([
            'incident_type' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'severity' => ['sometimes', 'required', 'in:low,medium,high'],
            'status' => ['sometimes', 'required', 'in:open,pending,resolved,closed'],
        ]);

        $incident->update($validated);

        return response()->json($incident->fresh()->load('reporter:id,name'));
    }
}
