<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Document;
use App\Models\DocumentChecklist;
use App\Models\DocumentType;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class DocumentGovernanceService
{
    /**
     * @return array{missing: array<int, string>, expired: array<int, string>}
     */
    public function evaluate(Client $client): array
    {
        $requiredTypeIds = DocumentChecklist::query()
            ->where('client_type', $client->client_type)
            ->where('required', true)
            ->pluck('document_type_id')
            ->all();

        if ($requiredTypeIds === []) {
            return [
                'missing' => [],
                'expired' => [],
            ];
        }

        $documents = Document::query()
            ->where('client_id', $client->id)
            ->whereIn('document_type_id', $requiredTypeIds)
            ->with('type:id,document_name')
            ->get()
            ->groupBy('document_type_id')
            ->map(static fn ($group) => $group->sortByDesc('id')->first());

        $typeNames = DocumentType::query()
            ->whereIn('id', $requiredTypeIds)
            ->pluck('document_name', 'id');

        $missing = [];
        $expired = [];

        foreach ($requiredTypeIds as $typeId) {
            /** @var Document|null $document */
            $document = $documents->get($typeId);

            if ($document === null) {
                $missing[] = (string) ($typeNames[$typeId] ?? ('Document type ID '.$typeId));
                continue;
            }

            $name = $document->type?->document_name ?? ('Document type ID '.$typeId);
            if ($document->expiry_date !== null && Carbon::parse((string) $document->expiry_date)->isPast()) {
                $expired[] = $name;
            }
        }

        return [
            'missing' => $missing,
            'expired' => $expired,
        ];
    }

    public function assertClientCanProceed(Client $client, string $actionLabel): void
    {
        $result = $this->evaluate($client);

        if ($result['missing'] === [] && $result['expired'] === []) {
            return;
        }

        throw ValidationException::withMessages([
            'documents' => [
                sprintf(
                    'Cannot %s. Required compliance documents are missing or expired.',
                    $actionLabel
                ),
            ],
            'missing_documents' => $result['missing'],
            'expired_documents' => $result['expired'],
        ]);
    }
}
