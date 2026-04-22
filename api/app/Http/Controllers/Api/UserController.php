<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = max(5, min($perPage, 100));

        $users = User::with(['role', 'company'])
            ->select(['id', 'name', 'email', 'role_id', 'company_id', 'status', 'last_login_at', 'created_at', 'updated_at'])
            ->latest('id')
            ->simplePaginate($perPage);

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = $validated['status'] ?? 'active';

        $user = User::create($validated);

        return response()->json($user->load(['role', 'company']), 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user->load(['role', 'company']));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['sometimes', 'integer', 'exists:roles,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json($user->load(['role', 'company']));
    }

    public function destroy(User $user): JsonResponse
    {
        // Prevent deleting the current authenticated user
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete your own account'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function toggleStatus(User $user): JsonResponse
    {
        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active'
        ]);

        return response()->json($user->load(['role', 'company']));
    }
}
