<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('admin.users.index', [
            'users' => User::query()
                ->orderBy('name')
                ->paginate(30),
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('admin.users.form', [
            'adminUser' => new User(['role' => User::ROLE_USER]),
            'roles' => User::roles(),
            'title' => 'Új felhasználó',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(User::roles()))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        AdminAudit::log($request, 'user.created', $user, null, $user->only(['id', 'name', 'email', 'role']));

        return redirect()
            ->route('admin.users')
            ->with('status', 'A felhasználó létrejött.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureAdmin($request);

        return view('admin.users.form', [
            'adminUser' => $user,
            'roles' => User::roles(),
            'title' => 'Felhasználó szerkesztése',
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(User::roles()))],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($user->isAdmin() && $data['role'] !== User::ROLE_ADMIN && $this->adminCount() <= 1) {
            return back()
                ->withErrors(['role' => 'Az utolsó admin jogosultság nem vehető el.'])
                ->withInput();
        }

        $oldValues = $user->only(['name', 'email', 'role']);
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        AdminAudit::log($request, 'user.updated', $user, $oldValues, $user->fresh()->only(['name', 'email', 'role']));

        return redirect()
            ->route('admin.users')
            ->with('status', 'A felhasználó adatai frissültek.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        if ((int) $request->user()?->id === (int) $user->id) {
            return back()->withErrors(['user' => 'A saját felhasználó nem törölhető.']);
        }

        if ($user->isAdmin() && $this->adminCount() <= 1) {
            return back()->withErrors(['user' => 'Az utolsó admin felhasználó nem törölhető.']);
        }

        $oldValues = $user->only(['id', 'name', 'email', 'role']);
        AdminAudit::log($request, 'user.deleted', $user, $oldValues, null);
        $user->delete();

        return redirect()
            ->route('admin.users')
            ->with('status', 'A felhasználó törölve lett.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    private function adminCount(): int
    {
        return User::query()
            ->where('role', User::ROLE_ADMIN)
            ->count();
    }
}

