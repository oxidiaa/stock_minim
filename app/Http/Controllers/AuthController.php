<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);
    
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
    
            return redirect()->route('dashboard');
        }
    
        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->onlyInput('username');
    }

    /**
     * Handle guest mode login (read-only access).
     */
    public function guestLogin(Request $request)
    {
        // Find or create guest user
        $guest = \App\Models\User::firstOrCreate(
            ['username' => 'guest'],
            [
                'name' => 'Guest User',
                'email' => 'guest@stockmin.com',
                'password' => \Illuminate\Support\Facades\Hash::make('guest_password_' . time()),
            ]
        );

        // Login as guest
        Auth::login($guest, false);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('info', 'Anda masuk sebagai Guest Mode. Akses terbatas hanya untuk melihat data.');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();
    
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    
        return redirect()->route('login');
    }
}
