<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, true)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->password = $data['password'];
        $user->save();

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return response()->json(['message' => 'Password updated.']);
    }
}
