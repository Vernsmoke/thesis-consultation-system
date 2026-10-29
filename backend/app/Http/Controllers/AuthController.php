<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        abort_unless($request->hasSession(), 419, 'Start a browser session before signing in.');
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        if (! Auth::guard('web')->attempt($credentials)) {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }
        $request->session()->regenerate();

        return response()->json(['data' => $request->user()->only(['id', 'name', 'role'])]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->only(['id', 'name', 'role'])]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Signed out.']);
    }
}
