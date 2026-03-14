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
        ]);

        $query = Communication::query()->latest('id');

        if (! empty($validated['linked_client_id'])) {
            $query->where('linked_client_id', (int) $validated['linked_client_id']);
        }

        if (! empty($validated['linked_task_id'])) {
            $query->where('linked_task_id', (int) $validated['linked_task_id']);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_body' => ['nullable', 'string'],
            'sender' => ['required', 'string', 'max:255'],
            'receiver' => ['required', 'string', 'max:255'],
            'linked_client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'linked_task_id' => ['nullable', 'integer', 'exists:tasks,id'],
        ]);

        $communication = Communication::query()->create($validated);
        AuditLogService::log(auth('api')->id(), 'create', 'communications', $communication->id);

        return response()->json($communication, 201);
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

        $communication->update($validated);
        AuditLogService::log(auth('api')->id(), 'update', 'communications', $communication->id);

        return response()->json($communication);
    }

    public function destroy(Communication $communication): JsonResponse
    {
        $id = $communication->id;
        $communication->delete();
        AuditLogService::log(auth('api')->id(), 'delete', 'communications', $id);

        return response()->json(null, 204);
    }
}
