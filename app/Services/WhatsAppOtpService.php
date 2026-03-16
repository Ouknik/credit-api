<?php

namespace App\Services;

use App\Models\OtpVerification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;

class WhatsAppOtpService
{
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl   = rtrim(config('services.whatsotp.base_url', ''), '/');
        $this->apiKey    = config('services.whatsotp.api_key', '');
        $this->apiSecret = config('services.whatsotp.api_secret', '');
        $this->timeout   = (int) config('services.whatsotp.timeout', 15);
    }

    /**
     * Send OTP to a phone number via WhatsApp.
     * Generates OTP locally, sends it as custom OTP to WhatsApp API, returns it to client.
     * No database interaction - OTP comparison is done client-side.
     *
     * @throws RuntimeException
     */
    public function sendOtp(string $phone): string
    {
        $rateLimitKey = 'otp_send:' . $phone;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);
            throw new RuntimeException(
                "Too many OTP requests. Please wait {$retryAfter} seconds."
            );
        }

        Log::info('WhatsAppOtp: sending OTP', ['phone' => $phone]);

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key'    => $this->apiKey,
                    'X-API-Secret' => $this->apiSecret,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/otp/send", [
                    'phone' => $phone,
                    'otp'   => $otp,
                ]);

            if (!$response->successful()) {
                Log::error('WhatsAppOtp: send failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                throw new RuntimeException(
                    "OTP service returned HTTP {$response->status()}"
                );
            }

            RateLimiter::hit($rateLimitKey, 300);

            Log::info('WhatsAppOtp: OTP sent successfully', ['phone' => $phone]);

            return $otp;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('WhatsAppOtp: connection error', ['error' => $e->getMessage()]);
            throw new RuntimeException("OTP service connection failed: {$e->getMessage()}");
        }
    }

    /**
     * Verify an OTP code via WhatsApp API. Returns a verification token on success.
     * Used by the registration flow.
     *
     * @throws RuntimeException
     */
    public function verifyOtp(string $phone, string $otp): string
    {
        Log::info('WhatsAppOtp: verifying OTP', ['phone' => $phone]);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-Key'    => $this->apiKey,
                    'X-API-Secret' => $this->apiSecret,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/otp/verify", [
                    'phone' => $phone,
                    'otp'   => $otp,
                ]);

            if (!$response->successful()) {
                throw new RuntimeException('Invalid or expired OTP code.');
            }

            $token = Str::random(64);

            // Clean old records for this phone, then store the verified one
            OtpVerification::where('phone', $phone)->where('verified', false)->delete();

            OtpVerification::create([
                'phone'              => $phone,
                'verified'           => true,
                'verification_token' => $token,
                'expires_at'         => now()->addMinutes(10),
            ]);

            Log::info('WhatsAppOtp: OTP verified', ['phone' => $phone]);

            return $token;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('WhatsAppOtp: verify error', ['error' => $e->getMessage()]);
            throw new RuntimeException("OTP service connection failed: {$e->getMessage()}");
        }
    }

    /**
     * Check if a verification token is valid for a given phone.
     */
    public function isPhoneVerified(string $phone, string $token): bool
    {
        $record = OtpVerification::where('phone', $phone)
            ->where('verification_token', $token)
            ->where('verified', true)
            ->first();

        return $record !== null && !$record->isExpired();
    }

    /**
     * Consume a verification token after successful registration.
     */
    public function consumeVerification(string $phone, string $token): void
    {
        OtpVerification::where('phone', $phone)
            ->where('verification_token', $token)
            ->delete();
    }
}
