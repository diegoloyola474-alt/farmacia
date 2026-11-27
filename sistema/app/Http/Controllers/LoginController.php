<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña no es válida.',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Renueva la sesión para evitar reutilizar el identificador previo al login.
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard.index'));
        }

        return back()
            ->withErrors(['email' => 'El correo o la contraseña no son correctos.'])
            ->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        // Invalida los datos de sesión y renueva el token CSRF al cerrar sesión.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('ok', 'Cerraste sesión correctamente.');
    }
}
