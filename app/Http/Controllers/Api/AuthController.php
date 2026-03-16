<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\WhatsAppOtpService;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private WhatsAppOtpService $otpService,
    ) {}

    /**
     * Send OTP to phone number via WhatsApp.
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        try {
            Log::info('AuthController: sendOtp called', ['phone' => $request->phone]);
            $this->otpService->sendOtp($request->phone);
            return $this->success(null, 'OTP sent successfully via WhatsApp');
        } catch (\RuntimeException $e) {
            Log::error('AuthController: sendOtp failed', ['phone' => $request->phone, 'error' => $e->getMessage()]);
            return $this->error($e->getMessage(), 429);
        }
    }

    /**
     * Verify OTP code. Returns a verification token for registration.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $token = $this->otpService->verifyOtp($request->phone, $request->otp);
            return $this->success(
                ['verification_token' => $token],
                'OTP verified successfully'
            );
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Register a new shop (requires OTP verification first).
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (!$this->otpService->isPhoneVerified($data['phone'], $data['verification_token'])) {
            return $this->error('Phone number has not been verified. Please complete OTP verification first.', 403);
        }

        $result = $this->authService->register($data);

        $this->otpService->consumeVerification($data['phone'], $data['verification_token']);

        return $this->success($result, 'Shop registered successfully', 201);
    }

    /**
     * Login with phone + OTP (no password).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $token = $this->otpService->verifyOtp($request->phone, $request->otp);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 401);
        }

        $result = $this->authService->loginByPhone($request->phone);
        Log::info('AuthController: login attempt', ['phone' => $request->phone, 'result' => $result ? 'success' : 'failure']);
        if (!$result) {
            return $this->error('Phone number not registered or account suspended', 401);
        }

        return $this->success($result, 'Login successful');
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();
        return $this->success(null, 'Logged out successfully');
    }

    public function refresh(): JsonResponse
    {
        $result = $this->authService->refresh();
        return $this->success($result, 'Token refreshed successfully');
    }

    public function me(): JsonResponse
    {
        $shop = $this->authService->me();
        return $this->success($shop);
    }
}
