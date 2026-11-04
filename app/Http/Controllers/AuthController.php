<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Activity;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login', ['accounts' => config('salon.demo_accounts'), 'password' => config('salon.demo_password')]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);

        if (! Auth::attempt([...$credentials, 'active' => true], $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Those details do not match our records.']);
        }

        $request->session()->regenerate();
        Activity::log('login', $request->user()->name, 'Signed in', null, $request);

        // Team members always land in the back office, unless they were heading to a specific admin page
        $user = $request->user();
        if ($user->isStaff() && ! str_contains((string) $request->session()->get('url.intended'), '/admin')) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended($user->homeRoute());
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::query()->create([...$data, 'role' => Role::Customer, 'is_demo' => false]);
        // Link an existing client record (booked before as a guest), or start a new one
        $client = Client::query()->where('email', $user->email)->whereNull('user_id')->first();
        $client ? $client->update(['user_id' => $user->id]) : Client::query()->create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone, 'source' => 'web']);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.dashboard')->with('success', 'Welcome to Lumière Studio, '.explode(' ', $user->name)[0].'!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Activity::log('logout', $request->user()->name, 'Signed out', null, $request);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
