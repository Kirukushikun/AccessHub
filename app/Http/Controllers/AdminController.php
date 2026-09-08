<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admins.index', [
            'admins' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
        ]);

        $tempPassword = Str::password(16);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($tempPassword),
            'active' => true,
        ]);

        Audit::record('admin.added', $user->email, $user);

        return back()->with([
            'success' => "{$user->name} added. Give them this one-time password to sign in and change.",
            'connection_code' => $tempPassword,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ]);

        $wantsActive = $request->boolean('active');

        // Block self-revoke — an admin removing their own access locks everyone out.
        if (! $wantsActive && $user->is($request->user())) {
            return back()->withErrors(['active' => 'You cannot deactivate your own account.']);
        }

        $wasActive = $user->active;
        $user->update(['name' => $data['name'], 'active' => $wantsActive]);

        if ($wasActive !== $wantsActive) {
            Audit::record($wantsActive ? 'admin.reactivated' : 'admin.deactivated', $user->email, $user);
        }

        return back()->with('success', "{$user->name} updated.");
    }
}
