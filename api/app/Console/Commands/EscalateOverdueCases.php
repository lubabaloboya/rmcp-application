<?php

namespace App\Console\Commands;

use App\Mail\OverdueCasesEscalationMail;
use App\Models\RmcpCase;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EscalateOverdueCases extends Command
{
    protected $signature = 'rmcp:escalate-overdue-cases';

    protected $description = 'Escalate overdue RMCP cases based on SLA timers';

    public function handle(): int
    {
        $overdueCases = RmcpCase::query()
            ->with('client:id,first_name,last_name,client_type')
            ->whereNull('escalated_at')
            ->whereNotIn('status', ['closed'])
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now())
            ->get();

        if ($overdueCases->isEmpty()) {
            $this->info('No overdue cases found.');

            return self::SUCCESS;
        }

        foreach ($overdueCases as $case) {
            /** @var RmcpCase $case */
            $case->update([
                'status' => 'escalated',
                'escalated_at' => now(),
            ]);

            AuditLogService::log(null, 'escalate', 'cases', $case->id);
        }

        $admins = User::query()
            ->whereNotNull('email')
            ->where('status', 'active')
            ->whereHas('role', static function ($query): void {
                $query->where('role_name', 'like', '%admin%');
            })
            ->get(['name', 'email']);

        if ($admins->isNotEmpty()) {
            $payload = $overdueCases->map(static function (RmcpCase $case): array {
                $clientName = trim(($case->client?->first_name ?? '').' '.($case->client?->last_name ?? ''));
                if ($clientName === '') {
                    $clientName = ($case->client?->client_type ?? 'client').' #'.$case->client_id;
                }

                return [
                    'case_number' => $case->case_number,
                    'title' => $case->title,
                    'client_name' => $clientName,
                    'sla_due_at' => optional($case->sla_due_at)->toDateTimeString(),
                ];
            })->all();

            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new OverdueCasesEscalationMail($admin->name, $payload));
            }
        }

        $this->info('Escalated '.$overdueCases->count().' case(s).');

        return self::SUCCESS;
    }
}
