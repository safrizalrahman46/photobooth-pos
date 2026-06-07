<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect($this->resolvePostLoginUrl());
        }

        return view('web.admin-login');
    }

    /**
     * @throws ValidationException
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect($this->resolvePostLoginUrl());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function resolvePostLoginUrl(): string
    {
        $user = Auth::user();

        if ($user === null) {
            return route('admin.dashboard');
        }

        $role = (string) ($user->getRoleNames()->first() ?? '');

        return match ($role) {
            'cashier' => url('/admin/bookings'),
            'viewer' => url('/admin/bookings'),
            default => route('admin.dashboard'),
        };
    }
}
