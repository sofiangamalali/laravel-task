<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthService
{
    /**
     * Register a new user.
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = auth('api')->login($user);

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
    }

    /**
     * Authenticate user and return token.
     */
    public function login(array $credentials): array
    {
        if (!$token = auth('api')->attempt($credentials)) {
            throw new UnauthorizedHttpException('JWT', 'Invalid email or password.');
        }

        return [
            'user' => auth('api')->user(),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(): void
    {
        auth('api')->logout();
    }

    /**
     * Refresh a token.
     */
    public function refresh(): array
    {
        $newToken = auth('api')->refresh();

        return [
            'user' => auth('api')->user(),
            'token' => $newToken,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
    }

    /**
     * Get the authenticated User.
     */
    public function me(): ?User
    {
        return auth('api')->user();
    }
}
