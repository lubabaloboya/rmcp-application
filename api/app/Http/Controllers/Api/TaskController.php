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
            'q' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'in:created_at,due_date,status,title'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = TaskItem::query()->with('assignee:id,name,email')->latest('id');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['assigned_to'])) {
            $query->where('assigned_to', (int) $validated['assigned_to']);
        }

        if (! empty($validated['q'])) {
            $term = trim($validated['q']);
            $query->where(function ($inner) use ($term): void {
                $inner->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
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
