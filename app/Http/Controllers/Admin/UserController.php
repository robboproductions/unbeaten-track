<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roleOptions' => config('unbeaten_auth.roles', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(array_keys(config('unbeaten_auth.roles', [])))],
        ]);

        User::query()->create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roleOptions' => config('unbeaten_auth.roles', []),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(array_keys(config('unbeaten_auth.roles', [])))],
        ]);

        if ($user->is($request->user())
            && $user->isSuperAdmin()
            && $validated['role'] !== User::ROLE_SUPER_ADMIN
            && User::query()->where('role', User::ROLE_SUPER_ADMIN)->count() <= 1
        ) {
            return redirect()->route('admin.users.edit', $user)
                ->withErrors(['role' => 'You cannot remove super admin from the only super admin account.'])
                ->withInput();
        }

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            abort(403, 'You cannot delete your own account.');
        }

        if ($user->isSuperAdmin() && User::query()->where('role', User::ROLE_SUPER_ADMIN)->count() <= 1) {
            return redirect()->route('admin.users.index')
                ->withErrors(['delete' => 'Cannot delete the only super admin.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User removed.');
    }
}
