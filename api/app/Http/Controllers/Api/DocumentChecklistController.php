<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentChecklistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_type' => ['nullable', 'in:individual,company'],
        ]);

        $query = DocumentChecklist::query()
            ->with('documentType:id,document_name,category')
            ->orderBy('client_type')
            ->orderBy('id');

        if (! empty($validated['client_type'])) {
            $query->where('client_type', $validated['client_type']);
        }

        return response()->json($query->get());
    }

    public function replaceForClientType(Request $request, string $clientType): JsonResponse
    {
        abort_unless(in_array($clientType, ['individual', 'company'], true), 422, 'Invalid client type.');

        $validated = $request->validate([
            'document_type_ids' => ['required', 'array'],
            'document_type_ids.*' => ['required', 'integer', 'exists:document_types,id'],
        ]);

        DocumentChecklist::query()->where('client_type', $clientType)->delete();

        foreach (array_unique($validated['document_type_ids']) as $documentTypeId) {
            DocumentChecklist::query()->create([
                'client_type' => $clientType,
                'document_type_id' => (int) $documentTypeId,
                'required' => true,
            ]);
        }

        return response()->json([
            'message' => 'Checklist updated.',
            'client_type' => $clientType,
        ]);
    }
}
