<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;

class AdminAuthController
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $errors = [];
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email tidak valid.';
        }
        if (! $password) {
            $errors['password'] = 'Password diperlukan.';
        }
        if (! empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }
        $credentials = ['email' => $email, 'password' => $password];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            if (($user->role ?? '') !== 'admin') {
                Auth::logout();
                return back()->withErrors(['email' => 'Akun tidak memiliki akses admin.']);
            }
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors(['email' => 'Kredensial tidak cocok.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    // Password reset web for admin
    public function showForgot()
    {
        return view('admin.forgot');
    }

    public function sendResetLink(Request $request)
    {
        $email = $request->input('email');
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors(['email' => 'Email tidak valid.'])->withInput();
        }
        $status = Password::sendResetLink($request->only('email'));
        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }
        return back()->withErrors(['email' => __($status)]);
    }

    public function showResetForm($token)
    {
        return view('admin.reset', compact('token'));
    }

    public function resetPassword(Request $request)
    {
        $token = $request->input('token');
        $email = $request->input('email');
        $password = $request->input('password');
        $passwordConfirmation = $request->input('password_confirmation');
        $errors = [];
        if (! $token) {
            $errors['token'] = 'Token diperlukan.';
        }
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email tidak valid.';
        }
        if (! $password || strlen($password) < 8) {
            $errors['password'] = 'Password harus minimal 8 karakter.';
        }
        if ($password !== $passwordConfirmation) {
            $errors['password_confirmation'] = 'Konfirmasi password tidak cocok.';
        }
        if (! empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        $status = Password::reset($request->only('email','password','password_confirmation','token'), function ($user, $password) {
            $user->password = Hash::make($password);
            $user->save();
        });

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('admin.login')->with('status', __($status));
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
