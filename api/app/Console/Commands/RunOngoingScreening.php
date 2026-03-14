<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\RiskAssessmentAutomationService;
use App\Services\ScreeningIntegrationService;
use Illuminate\Console\Command;

class RunOngoingScreening extends Command
{
    protected $signature = 'rmcp:run-ongoing-screening {--limit=200 : Max clients to process per run}';

    protected $description = 'Run sanctions, PEP, and adverse media monitoring for existing clients';

    public function __construct(
        private readonly ScreeningIntegrationService $screeningService,
        private readonly RiskAssessmentAutomationService $automationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = max((int) $this->option('limit'), 1);

        $clients = Client::query()->latest('id')->limit($limit)->get();

        if ($clients->isEmpty()) {
            $this->info('No clients found for monitoring.');

            return self::SUCCESS;
        }

        foreach ($clients as $client) {
            if (! $client instanceof Client) {
                continue;
            }

            $result = $this->screeningService->runForClient($client, 'ongoing');
            $this->automationService->reassess($client, [
                'pep_status' => $result['pep_status'],
                'sanctions_check' => $result['sanctions_check'],
                'adverse_media_hit' => $result['adverse_media_hit'],
            ], 'ongoing_monitoring');
        }

        $this->info('Processed ongoing monitoring for '.$clients->count().' client(s).');

        return self::SUCCESS;
    }
}
