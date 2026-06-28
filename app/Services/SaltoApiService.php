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
            'action'            => 1,
            'accessPointIdList' => [$accessPointId],
        ]);

        return is_string($uuid) ? $uuid : '';
    }

    /**
     * Returns real-time battery status for all online RF3 locks.
     *
     * Key = SALTO DB lock id (tb_Locks.id_lock, same as AttachedAccessPointId).
     * Value = BatteryStatus enum derived from the API's BatteryStatus field:
     *   0 → Low, 1 → Normal, 2 → Flat, other → null (skip, keep DB value).
     *
     * Returns empty array on any API failure so the DB fallback is used.
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

            // Build maps: online_ap_id → db_lock_id  AND  online_ap_id list
            $apIds       = [];
            $apToLockId  = [];
            foreach ($doors as $door) {
                $apId   = (int) ($door['Id'] ?? 0);
                $lockId = (string) ($door['AttachedAccessPointId'] ?? '');
                if ($apId && $lockId !== '') {
                    $apIds[]            = $apId;
                    $apToLockId[$apId]  = $lockId;
                }
            }

            if (empty($apIds)) {
                return [];
            }

            $statuses = $this->rpc('GetOnlineAccessPointStatus', ['accessPointIdList' => $apIds]);
            if (! is_array($statuses)) {
                return [];
            }

            // API BatteryStatus meanings for RF3 online locks (confirmed against SALTO Space UI):
            //   0 = no fresh reading → skip, let DB value be used
            //   1 = Normal/OK
            //   2 = Normal/OK (different signal level, still good — SALTO shows "Normal")
            //   3 = Low warning (RF3 real-time battery alert)
            //   4+ = Flat/Dead (critical)
            $map = [1 => BatteryStatus::Normal, 2 => BatteryStatus::Normal, 3 => BatteryStatus::Low, 4 => BatteryStatus::Flat];

            $result = [];
            foreach ($statuses as $s) {
                $apId   = (int) ($s['AccessPointId'] ?? 0);
                $apiVal = $s['BatteryStatus'] ?? null;
                $lockId = $apToLockId[$apId] ?? null;
                if ($lockId !== null && isset($map[$apiVal])) {
                    $result[$lockId] = $map[$apiVal];
                }
            }

            return $result;
        } catch (\Throwable) {
            return [];
        }
    }
}
