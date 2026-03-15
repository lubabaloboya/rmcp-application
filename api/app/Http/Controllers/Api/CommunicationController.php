<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Communication;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'linked_client_id' => ['nullable', 'integer'],
            'linked_task_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'in:created_at,sender,receiver,email_subject'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = Communication::query()
            ->with(['client:id,first_name,last_name,client_type', 'task:id,title'])
            ->latest('id');

        if (! empty($validated['linked_client_id'])) {
            $query->where('linked_client_id', (int) $validated['linked_client_id']);
        }

        if (! empty($validated['linked_task_id'])) {
            $query->where('linked_task_id', (int) $validated['linked_task_id']);
        }

        if (! empty($validated['q'])) {
            $term = trim($validated['q']);
            $query->where(function ($inner) use ($term): void {
                $inner->where('sender', 'like', "%{$term}%")
                    ->orWhere('receiver', 'like', "%{$term}%")
                    ->orWhere('email_subject', 'like', "%{$term}%")
                    ->orWhere('email_body', 'like', "%{$term}%");
            });
        }

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $perPage = (int) ($validated['per_page'] ?? 20);

        $query->reorder()->orderBy($sortBy, $sortDir);

        return response()->json($query->paginate($perPage)->withQueryString());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_body' => ['nullable', 'string'],
            'sender' => ['required', 'string', 'max:255'],
            'receiver' => ['required', 'string', 'max:255'],
            'linked_client_id' => ['required', 'integer', 'exists:clients,id'],
            'linked_task_id' => ['nullable', 'integer', 'exists:tasks,id'],
        ]);

        $communication = Communication::query()->create($validated);
        AuditLogService::log(auth('api')->id(), 'create', 'communications', $communication->id);

        return response()->json($communication->load(['client:id,first_name,last_name,client_type', 'task:id,title']), 201);
    }

    public function update(Request $request, Communication $communication): JsonResponse
    {
        $validated = $request->validate([
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_body' => ['nullable', 'string'],
            'sender' => ['sometimes', 'required', 'string', 'max:255'],
            'receiver' => ['sometimes', 'required', 'string', 'max:255'],
            'linked_client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'linked_task_id' => ['nullable', 'integer', 'exists:tasks,id'],
        ]);

        $effectiveLinkedClient = $validated['linked_client_id'] ?? $communication->linked_client_id;
        if (empty($effectiveLinkedClient)) {
            return response()->json([
                'message' => 'Communication must be linked to a client.',
            ], 422);
        }

        $communication->update($validated);
        AuditLogService::log(auth('api')->id(), 'update', 'communications', $communication->id);

        return response()->json($communication->fresh()->load(['client:id,first_name,last_name,client_type', 'task:id,title']));
    }

    public function destroy(Communication $communication): JsonResponse
    {
        $id = $communication->id;
        $communication->delete();
        AuditLogService::log(auth('api')->id(), 'delete', 'communications', $id);

        return response()->json(null, 204);
    }
}
