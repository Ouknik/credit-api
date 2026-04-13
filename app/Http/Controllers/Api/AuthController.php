<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use App\Services\AuthService;
use App\Services\WhatsAppOtpService;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private WhatsAppOtpService $otpService,
    ) {}

    /**
     * Send OTP to phone number via WhatsApp. Returns OTP to client for local verification.
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        try {
            if ($this->isStaticOtpPhone($request->phone)) {
                $staticOtp = $this->staticOtpCode();
                Log::warning('AuthController: static OTP used for test phone', ['phone' => $request->phone]);
                return $this->success(['otp' => $staticOtp], 'OTP sent successfully via test profile');
            }

            Log::info('AuthController: sendOtp called', ['phone' => $request->phone]);
            $otp = $this->otpService->sendOtp($request->phone);
            return $this->success(['otp' => $otp], 'OTP sent successfully via WhatsApp');
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
            if ($this->isStaticOtpPhone($request->phone)) {
                $expectedOtp = $this->staticOtpCode();
                if (trim($request->otp) !== $expectedOtp) {
                    return $this->error('Invalid or expired OTP code.', 400);
                }

                $token = Str::random(64);
                OtpVerification::where('phone', $request->phone)->delete();

                OtpVerification::create([
                    'phone' => $request->phone,
                    'verified' => true,
                    'verification_token' => $token,
                    'expires_at' => now()->addMinutes(10),
                ]);

                Log::warning('AuthController: static OTP verification accepted', ['phone' => $request->phone]);

                return $this->success(
                    ['verification_token' => $token],
                    'OTP verified successfully'
                );
            }

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
     * Login with phone. OTP verification is done client-side.
     */
    public function login(LoginRequest $request): JsonResponse
    {
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
        try {
            $result = $this->authService->refresh();
            return $this->success($result, 'Token refreshed successfully');
        } catch (TokenExpiredException|TokenInvalidException|JWTException $e) {
            return $this->error('Session expired. Please login again.', 401);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 401);
        }
    }

    public function me(): JsonResponse
    {
        $shop = $this->authService->me();
        return $this->success($shop);
    }

    private function isStaticOtpPhone(string $phone): bool
    {
        if (!$this->isStaticOtpEnabled()) {
            return false;
        }

        $configuredPhone = $this->normalizePhone((string) config('services.whatsotp.test_phone', ''));
        if ($configuredPhone === '') {
            return false;
        }

        return $this->normalizePhone($phone) === $configuredPhone;
    }

    private function staticOtpCode(): string
    {
        return trim((string) config('services.whatsotp.test_otp', ''));
    }

    private function isStaticOtpEnabled(): bool
    {
        return filter_var(config('services.whatsotp.test_enabled', false), FILTER_VALIDATE_BOOL);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
