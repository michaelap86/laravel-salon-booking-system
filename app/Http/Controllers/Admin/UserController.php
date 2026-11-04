<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->query('role');

        return view('admin.users', [
            'users' => User::query()->with(['staff:id,user_id,color'])->when($role, fn ($q) => $q->where('role', $role))
                ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'manager' THEN 2 WHEN 'stylist' THEN 3 ELSE 4 END")->orderBy('name')->paginate(20)->withQueryString(),
            'counts' => User::query()->selectRaw('role, count(*) c')->groupBy('role')->pluck('c', 'role'),
            'roles' => Role::cases(),
            'role' => $role,
        ]);
    }

    public function role(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::enum(Role::class)]]);

        if ($user->is($request->user()) || $user->is_demo) {
            return back()->with('error', $user->is_demo ? 'Demo accounts keep their roles so every visitor sees the same demo.' : 'You cannot change your own role.');
        }
        $old = $user->role->label();
        $user->update(['role' => $data['role']]);
        Activity::log('user', $user->name, $user->name.': '.$old.' → '.$user->role->label(), route('admin.users.index'));

        return back()->with('success', $user->name.' is now a '.$user->role->label().'.');
    }

    public function active(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user()) || $user->is_demo) {
            return back()->with('error', 'This account cannot be deactivated.');
        }
        $user->update(['active' => ! $user->active]);
        Activity::log('user', $user->name, ($user->active ? 'Reactivated ' : 'Deactivated ').$user->name, route('admin.users.index'));

        return back()->with('success', $user->active ? 'Account reactivated.' : 'Account deactivated.');
    }
}
