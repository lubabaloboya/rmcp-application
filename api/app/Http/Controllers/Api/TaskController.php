<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskItem;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:50'],
            'assigned_to' => ['nullable', 'integer'],
        ]);

        $query = TaskItem::query()->with('assignee:id,name,email')->latest('id');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['assigned_to'])) {
            $query->where('assigned_to', (int) $validated['assigned_to']);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pending,in_progress,done,cancelled'],
            'due_date' => ['nullable', 'date'],
        ]);

        $task = TaskItem::query()->create([
            ...$validated,
            'status' => $validated['status'] ?? 'pending',
        ]);

        AuditLogService::log(auth('api')->id(), 'create', 'tasks', $task->id);

        return response()->json($task->load('assignee:id,name,email'), 201);
    }

    public function update(Request $request, TaskItem $task): JsonResponse
    {
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pending,in_progress,done,cancelled'],
            'due_date' => ['nullable', 'date'],
        ]);

        $task->update($validated);
        AuditLogService::log(auth('api')->id(), 'update', 'tasks', $task->id);

        return response()->json($task->load('assignee:id,name,email'));
    }

    public function destroy(TaskItem $task): JsonResponse
    {
        $id = $task->id;
        $task->delete();
        AuditLogService::log(auth('api')->id(), 'delete', 'tasks', $id);

        return response()->json(null, 204);
    }
}
