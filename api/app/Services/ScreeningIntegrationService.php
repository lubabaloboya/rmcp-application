<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ScreeningCheck;
use Illuminate\Support\Facades\Http;

class ScreeningIntegrationService
{
    /**
     * @return array{sanctions_check: bool, pep_status: bool, adverse_media_hit: bool, checks: array<int, array<string, mixed>>}
     */
    public function runForClient(Client $client, string $monitoringCycle = 'onboarding'): array
    {
        $types = ['sanctions', 'pep', 'adverse_media'];
        $checks = [];

        foreach ($types as $type) {
            $result = $this->performCheck($type, $client);

            $record = ScreeningCheck::query()->create([
                'client_id' => $client->id,
                'check_type' => $type,
                'provider' => $result['provider'],
                'status' => $result['status'],
                'matched' => $result['matched'],
                'score' => $result['score'],
                'metadata' => $result['metadata'],
                'monitoring_cycle' => $monitoringCycle,
                'checked_at' => now(),
            ]);

            $checks[] = [
                'id' => $record->id,
                'check_type' => $type,
                'status' => $result['status'],
                'matched' => $result['matched'],
                'score' => $result['score'],
                'provider' => $result['provider'],
            ];
        }

        return [
            'sanctions_check' => (bool) collect($checks)->firstWhere('check_type', 'sanctions')['matched'] ?? false,
            'pep_status' => (bool) collect($checks)->firstWhere('check_type', 'pep')['matched'] ?? false,
            'adverse_media_hit' => (bool) collect($checks)->firstWhere('check_type', 'adverse_media')['matched'] ?? false,
            'checks' => $checks,
        ];
    }

    /**
     * @return array{provider: string, status: string, matched: bool, score: int, metadata: array<string, mixed>}
     */
    private function performCheck(string $type, Client $client): array
    {
        $endpoint = config("services.screening.{$type}.url");

        if (is_string($endpoint) && $endpoint !== '') {
            try {
                $response = Http::timeout(10)
                    ->acceptJson()
                    ->post($endpoint, [
                        'client_id' => $client->id,
                        'client_type' => $client->client_type,
                        'first_name' => $client->first_name,
                        'last_name' => $client->last_name,
                        'email' => $client->email,
                        'id_number' => $client->id_number,
                        'passport_number' => $client->passport_number,
                    ]);

                $payload = $response->json() ?? [];
                $matched = (bool) ($payload['matched'] ?? $payload['hit'] ?? false);

                return [
                    'provider' => (string) ($payload['provider'] ?? 'external-api'),
                    'status' => $response->successful() ? ($matched ? 'match' : 'clear') : 'error',
                    'matched' => $matched,
                    'score' => (int) ($payload['score'] ?? ($matched ? $this->defaultScoreFor($type) : 0)),
                    'metadata' => [
                        'endpoint' => $endpoint,
                        'response_code' => $response->status(),
                        'payload' => $payload,
                    ],
                ];
            } catch (\Throwable $e) {
                return [
                    'provider' => 'external-api',
                    'status' => 'error',
                    'matched' => false,
                    'score' => 0,
                    'metadata' => [
                        'endpoint' => $endpoint,
                        'error' => $e->getMessage(),
                    ],
                ];
            }
        }

        $matched = $this->localHeuristicMatch($type, $client);

        return [
            'provider' => 'local-simulated',
            'status' => $matched ? 'match' : 'clear',
            'matched' => $matched,
            'score' => $matched ? $this->defaultScoreFor($type) : 0,
            'metadata' => [
                'mode' => 'simulated',
            ],
        ];
    }

    private function defaultScoreFor(string $type): int
    {
        return match ($type) {
            'sanctions' => 50,
            'pep' => 40,
            'adverse_media' => 25,
            default => 0,
        };
    }

    private function localHeuristicMatch(string $type, Client $client): bool
    {
        $seed = strtolower((string) $client->first_name.(string) $client->last_name.(string) $client->email.(string) $client->id_number);
        $mod = abs(crc32($type.'|'.$seed)) % 100;

        return match ($type) {
            'sanctions' => $mod < 3,
            'pep' => $mod < 5,
            'adverse_media' => $mod < 8,
            default => false,
        };
    }
}
