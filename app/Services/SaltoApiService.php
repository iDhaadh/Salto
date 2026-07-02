<?php

namespace App\Services;

use App\Support\BatteryStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SaltoApiService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private int $timezoneOffset;

    public function __construct()
    {
        $cfg = config('salto.api');
        $this->baseUrl        = rtrim($cfg['url'], '/');
        $this->username       = $cfg['username'];
        $this->password       = $cfg['password'];
        $this->timezoneOffset = $cfg['timezone_offset'];
    }

    // salt (64 hex) + SHA256(salt + plaintext) — matches ProAccess Space JS bundle
    private function hashPassword(string $password): string
    {
        $salt = bin2hex(random_bytes(32));
        return $salt . hash('sha256', $salt . $password);
    }

    private function getToken(): string
    {
        return Cache::remember('salto_api_token', 3300, function () {
            $response = Http::asForm()->post($this->baseUrl . '/oauth/connect/token', [
                'client_id'  => 'webapp',
                'grant_type' => 'password',
                'username'   => base64_encode($this->username),
                'password'   => $this->hashPassword($this->password),
                'scope'      => 'offline_access global',
            ]);

            if (! $response->successful()) {
                throw new RuntimeException('SALTO API auth failed: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    private function rpc(string $method, array $body = []): mixed
    {
        $token = $this->getToken();

        $response = $this->post($method, $body, $token);

        if ($response->status() === 401) {
            Cache::forget('salto_api_token');
            $token    = $this->getToken();
            $response = $this->post($method, $body, $token);
        }

        if (! $response->successful()) {
            throw new RuntimeException("SALTO RPC $method failed ({$response->status()}): " . $response->body());
        }

        return $response->json();
    }

    private function post(string $method, array $body, string $token)
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'UMO'           => (string) $this->timezoneOffset,
            'Origin'        => $this->baseUrl,
            'Referer'       => $this->baseUrl . '/index.html',
        ])->post($this->baseUrl . '/rpc/' . $method, $body);
    }

    /** Returns array of door objects from ProAccess Space. */
    public function getDoors(): array
    {
        $result = $this->rpc('GetOnlineAccessPointList', ['filterCriteria' => '']);
        return is_array($result) ? $result : [];
    }

    /** Sends door-open command. Returns the async operation UUID. */
    public function openDoor(int $accessPointId): string
    {
        $uuid = $this->rpc('StartExecuteOnlineAccessPointAction', [
            'action'            => 0,
            'accessPointIdList' => [$accessPointId],
        ]);

        return is_string($uuid) ? $uuid : '';
    }

    /**
     * Returns real-time battery status for all online RF3 locks — the same
     * data source SALTO Space's Online Monitoring page uses.
     *
     * GetOnlineAccessPointStatus must be queried with AttachedAccessPointId
     * (= tb_Locks.id_lock), NOT the online access-point Id. Querying with the
     * wrong id silently returns all-zero (UNKNOWN) statuses. The webapp also
     * batches requests in chunks of 20, which we mirror here.
     *
     * BatteryStatus enum (from the SALTO Space JS bundle):
     *   0 = UNKNOWN  → skip, keep DB value
     *   1 = NORMAL
     *   2 = LOW
     *   3 = RUNOUT (flat/dead)
     *
     * Key = SALTO DB lock id. Returns empty array on any API failure so the
     * DB fallback is used.
     *
     * @return array<string, BatteryStatus>
     */
    public function getOnlineBatteryStatuses(): array
    {
        if (empty($this->password)) {
            return [];
        }

        try {
            $doors = $this->getDoors();
            if (empty($doors)) {
                return [];
            }

            $lockIds = [];
            foreach ($doors as $door) {
                $lockId = (int) ($door['AttachedAccessPointId'] ?? 0);
                if ($lockId) {
                    $lockIds[] = $lockId;
                }
            }

            if (empty($lockIds)) {
                return [];
            }

            $map = [1 => BatteryStatus::Normal, 2 => BatteryStatus::Low, 3 => BatteryStatus::Flat];

            $result = [];
            foreach (array_chunk($lockIds, 20) as $chunk) {
                $statuses = $this->rpc('GetOnlineAccessPointStatus', ['accessPointIdList' => $chunk]);
                if (! is_array($statuses)) {
                    continue;
                }
                foreach ($statuses as $s) {
                    $lockId = (int) ($s['AccessPointId'] ?? 0);
                    $apiVal = $s['BatteryStatus'] ?? null;
                    if ($lockId && isset($map[$apiVal])) {
                        $result[(string) $lockId] = $map[$apiVal];
                    }
                }
            }

            return $result;
        } catch (\Throwable) {
            return [];
        }
    }
}
