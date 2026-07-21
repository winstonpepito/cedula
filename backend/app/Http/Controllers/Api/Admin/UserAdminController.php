<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $query = User::query()->orderBy('name');

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        return response()->json([
            'data' => $query->get(['id', 'name', 'email', 'role', 'created_at', 'updated_at']),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_DELIVERY])],
        ]);

        $user = User::query()->create($data);

        return response()->json([
            'data' => $user->only(['id', 'name', 'email', 'role', 'created_at', 'updated_at']),
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::defaults()],
            'role' => ['sometimes', Rule::in([User::ROLE_ADMIN, User::ROLE_DELIVERY])],
        ]);

        if (($data['role'] ?? null) === User::ROLE_DELIVERY
            && $user->isAdmin()
            && $this->adminCount() <= 1
        ) {
            return response()->json([
                'message' => 'Cannot demote the only admin account.',
            ], 422);
        }

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        unset($data['password']);

        $user->fill($data);
        $user->save();

        return response()->json([
            'data' => $user->fresh()->only(['id', 'name', 'email', 'role', 'created_at', 'updated_at']),
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        if ($user->isAdmin() && $this->adminCount() <= 1) {
            return response()->json(['message' => 'Cannot delete the only admin account.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Deleted']);
    }

    private function adminCount(): int
    {
        return User::query()->where('role', User::ROLE_ADMIN)->count();
    }
}
