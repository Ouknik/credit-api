<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Gateway client for the Raspberry Pi recharge system (SIM800L modem).
 *
 * The Pi exposes a REST API via ngrok:
 *   POST /recharge            → queue a recharge
 *   GET  /status/{order_id}   → check recharge status
 */
class CadeauxGateway
{
    private string $baseUrl;
    private string $token;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.cadeaux.base_url', ''), '/');
        $this->token   = config('services.cadeaux.token', '');
        $this->timeout = (int) config('services.cadeaux.timeout', 30);
    }

    // ════════════════════════════════════════════════
    //  POST /recharge  —  Queue a recharge on the Pi
    // ════════════════════════════════════════════════

    /**
     * @param  string  $orderId   Unique order reference
     * @param  string  $phone     Target phone number
     * @param  float   $price     Amount in MAD
     * @param  string  $offer     Offer code (e.g. "3")
     * @return array              ['order_id', 'queue', 'status']
     *
     * @throws RuntimeException
     */
    public function sendRecharge(string $orderId, string $phone, float $price, string $offer): array
    {
        Log::info('CadeauxGateway: sending recharge', compact('orderId', 'phone', 'price', 'offer'));

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'token'        => $this->token,
                    'Content-Type' => 'application/json',
                    'ngrok-skip-browser-warning' => 'true',
                ])
                ->post("{$this->baseUrl}/recharge", [
                    'order_id' => $orderId,
                    'phone'    => $phone,
                    'price'    => (string) $price,
                    'offer'    => $offer,
                ]);

            if (!$response->successful()) {
                Log::error('CadeauxGateway: recharge request failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                throw new RuntimeException("Gateway returned HTTP {$response->status()}");
            }

            $data = $response->json();

            Log::info('CadeauxGateway: recharge queued', $data);

            return $data;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('CadeauxGateway: connection error', ['error' => $e->getMessage()]);
            throw new RuntimeException("Gateway connection failed: {$e->getMessage()}");
        }
    }

    // ════════════════════════════════════════════════
    //  GET /status/{order_id}  —  Poll status
    // ════════════════════════════════════════════════

    /**
     * @param  string  $orderId
     * @return array   Full gateway response, always includes 'status' key
     *
     * @throws RuntimeException
     */
    public function checkStatus(string $orderId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'token' => $this->token,
                    'ngrok-skip-browser-warning' => 'true',
                ])
                ->get("{$this->baseUrl}/status/{$orderId}");

            if (!$response->successful()) {
                Log::warning('CadeauxGateway: status check failed', [
                    'order_id' => $orderId,
                    'status'   => $response->status(),
                ]);
                throw new RuntimeException("Gateway status check returned HTTP {$response->status()}");
            }

            $data = $response->json() ?? [];
            if (!isset($data['status'])) {
                $data['status'] = 'unknown';
            }

            Log::info('CadeauxGateway: status check', [
                'order_id' => $orderId,
                'status'   => $data['status'],
            ]);

            return $data;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('CadeauxGateway: status check connection error', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);
            throw new RuntimeException("Gateway connection failed: {$e->getMessage()}");
        }
    }

    /**
     * Check if the gateway is reachable.
     */
    public function ping(): bool
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'token' => $this->token,
                    'ngrok-skip-browser-warning' => 'true',
                ])
                ->get("{$this->baseUrl}/status/ping-test");

            return $response->successful() || $response->status() === 404;
        } catch (\Exception) {
            return false;
        }
    }
}
