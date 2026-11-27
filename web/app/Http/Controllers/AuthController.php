<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:' . $request->ip() . ':' . strtolower($credentials['email']);
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return back()->withErrors([
                'email' => 'Terlalu banyak percobaan masuk. Silakan tunggu beberapa saat.',
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Akun Anda sedang dinonaktifkan oleh Administrator.']);
            }
            RateLimiter::clear($key);
            $request->session()->regenerate();
            AuditLog::record('auth.login', $user->email, ['role' => $user->role], $user->id);

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($key, 60);
        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan tidak sesuai.',
        ])->onlyInput('email');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'consent' => ['required', 'accepted'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'pengguna',
            'is_active' => true,
            'consent_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        AuditLog::record('auth.register', $user->email, ['role' => 'pengguna'], $user->id);

        return redirect()->route('dashboard')->with('success', 'Selamat datang di DiaXplain! Akun Anda berhasil dibuat.');
    }

    public function quickDemoLogin(Request $request, string $role): RedirectResponse
    {
        $emailMap = [
            'admin' => 'admin@diaxplain.id',
            'nakes' => 'nakes@diaxplain.id',
            'pengguna' => 'pengguna@diaxplain.id',
        ];
        $email = $emailMap[$role] ?? 'pengguna@diaxplain.id';
        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'Akun demo belum di-seed.']);
        }

        Auth::login($user);
        $request->session()->regenerate();
        AuditLog::record('auth.demo_switch', $user->email, ['role' => $user->role], $user->id);

        return redirect()->route('dashboard')->with('success', "Berhasil masuk sebagai akun demo: {$user->name} ({$user->roleBadgeLabel()}).");
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            AuditLog::record('auth.logout', Auth::user()->email);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}

