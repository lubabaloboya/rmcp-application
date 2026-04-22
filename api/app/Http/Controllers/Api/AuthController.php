<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $defaultRole = Role::query()->firstOrCreate(
            ['role_name' => 'Individual User'],
            ['permissions' => []]
        );

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'role_id' => $defaultRole->id,
            'status' => 'active',
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'token' => $token,
            'user' => $this->serializeUser($user->load('role')),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = JWTAuth::setToken($token)->toUser();
        if ($user instanceof User) {
            $user->update(['last_login_at' => now()]);
            $user->load('role');
        }

        return response()->json([
            'token' => $token,
            'user' => $this->serializeUser($user instanceof User ? $user : null),
        ]);
    }

    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        if ($user instanceof User) {
            $user->load('role');
        }

        return response()->json($this->serializeUser($user instanceof User ? $user : null));
    }

    public function logout(): JsonResponse
    {
        if ($token = JWTAuth::getToken()) {
            JWTAuth::invalidate($token);
        }

        return response()->json(['message' => 'Logged out']);
    }

    private function serializeUser(?User $user): array
    {
        return [
            'id' => $user?->id,
            'name' => $user?->name,
            'email' => $user?->email,
            'role_id' => $user?->role_id,
            'role_name' => $user?->role?->role_name,
            'permissions' => $user?->role?->permissions ?? [],
            'status' => $user?->status,
        ];
    }
}
