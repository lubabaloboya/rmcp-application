<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $payload = Cache::remember('api:dashboard:index', now()->addSeconds(30), static function (): array {
            $recentIncidents = DB::table('incidents')->orderByDesc('created_at')->limit(5)->get();
            $blockedClients = 0;
            $recentScreeningMatches = collect();
            $today = now()->toDateString();

            if (Schema::hasTable('document_checklists') && Schema::hasTable('documents')) {
                $requiredChecklistRows = DB::table('document_checklists')->where('required', 1)->get(['client_type', 'document_type_id']);
                $requiredByType = $requiredChecklistRows
                    ->groupBy('client_type')
                    ->map(static fn ($rows) => collect($rows)->pluck('document_type_id')->unique()->values()->all());

                $allClients = Client::query()->get(['id', 'client_type']);

                $requiredDocumentTypeIds = collect($requiredByType->all())->flatten()->unique()->values()->all();

                $latestDocumentRows = [];
                if ($requiredDocumentTypeIds !== []) {
                    $latestDocumentRows = DB::table('documents as d')
                        ->joinSub(
                            DB::table('documents')
                                ->selectRaw('client_id, document_type_id, MAX(id) as latest_id')
                                ->whereIn('document_type_id', $requiredDocumentTypeIds)
                                ->groupBy('client_id', 'document_type_id'),
                            'latest',
                            static function ($join): void {
                                $join->on('d.id', '=', 'latest.latest_id');
                            }
                        )
                        ->select(['d.client_id', 'd.document_type_id', 'd.expiry_date'])
                        ->get();
                }

                $latestDocumentMap = [];
                foreach ($latestDocumentRows as $row) {
                    $latestDocumentMap[(int) $row->client_id][(int) $row->document_type_id] = $row->expiry_date;
                }

                foreach ($allClients as $client) {
                    $requiredIds = $requiredByType[$client->client_type] ?? [];
                    if ($requiredIds === []) {
                        continue;
                    }

                    $hasBlockingIssue = false;
                    foreach ($requiredIds as $requiredId) {
                        $expiryDate = $latestDocumentMap[$client->id][$requiredId] ?? null;
                        if ($expiryDate === null) {
                            $hasBlockingIssue = true;
                            break;
                        }

                        if ((string) $expiryDate < $today) {
                            $hasBlockingIssue = true;
                            break;
                        }
                    }

                    if ($hasBlockingIssue) {
                        $blockedClients++;
                    }
                }
            }

            if (Schema::hasTable('screening_checks')) {
                $recentScreeningMatches = DB::table('screening_checks')
                    ->where('matched', 1)
                    ->orderByDesc('checked_at')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get(['client_id', 'check_type', 'provider', 'status', 'checked_at']);
            }

            return [
                'total_clients' => Client::count(),
                'high_risk_clients' => Client::where('risk_level', 'high')->count(),
                'compliance_status' => 'In Progress',
                'documents_expiring' => DB::table('documents')->whereDate('expiry_date', '<=', now()->addDays(30))->count(),
                'blocked_clients' => $blockedClients,
                'pending_tasks' => DB::table('tasks')->where('status', 'pending')->count(),
                'recent_incidents' => $recentIncidents,
                'recent_screening_matches' => $recentScreeningMatches,
            ];
        });

        return response()->json($payload);
    }
}
