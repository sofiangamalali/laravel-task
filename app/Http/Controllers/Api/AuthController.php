<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'access_token' => $result['token'],
            'token_type' => $result['token_type'],
            'expires_in' => $result['expires_in'],
        ], 'User registered successfully.', 201);
    }

    /**
     * Log in a user and return JWT token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return $this->successResponse([
                'user' => new UserResource($result['user']),
                'access_token' => $result['token'],
                'token_type' => $result['token_type'],
                'expires_in' => $result['expires_in'],
            ], 'Login successful.');
        } catch (UnauthorizedHttpException $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }
    }

    /**
     * Get authenticated user profile.
     */
    public function me(): JsonResponse
    {
        $user = $this->authService->me();

        if (!$user) {
            return $this->errorResponse('Unauthenticated.', 401);
        }

        return $this->successResponse(new UserResource($user), 'User profile retrieved.');
    }

    /**
     * Refresh current JWT token.
     */
    public function refresh(): JsonResponse
    {
        $result = $this->authService->refresh();

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'access_token' => $result['token'],
            'token_type' => $result['token_type'],
            'expires_in' => $result['expires_in'],
        ], 'Token refreshed successfully.');
    }

    /**
     * Logout and invalidate token.
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return $this->successResponse(null, 'Successfully logged out.');
    }
}
