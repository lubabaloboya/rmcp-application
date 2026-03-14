<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function types(): JsonResponse
    {
        $types = DocumentType::query()
            ->select(['id', 'document_name', 'category'])
            ->orderBy('category')
            ->orderBy('document_name')
            ->get();

        return response()->json($types);
    }

    public function index(Client $client): JsonResponse
    {
        $documents = Document::query()
            ->where('client_id', $client->id)
            ->with('type:id,document_name,category')
            ->latest('id')
            ->get()
            ->map(fn (Document $document): array => $this->serializeDocument($document));

        return response()->json($documents);
    }

    public function store(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'expiry_date' => ['nullable', 'date'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $uploadedFile = $request->file('file');
        $original = $uploadedFile->getClientOriginalName();
        $extension = $uploadedFile->getClientOriginalExtension();
        $baseName = pathinfo($original, PATHINFO_FILENAME);
        $safeBaseName = Str::slug($baseName) ?: 'document';
        $storedName = $safeBaseName.'-'.Str::uuid()->toString().'.'.$extension;
        $storedPath = $uploadedFile->storeAs('documents/'.$client->id, $storedName, 'local');

        $document = Document::query()->create([
            'client_id' => $client->id,
            'company_id' => $client->company_id,
            'document_type_id' => (int) $validated['document_type_id'],
            'file_path' => $storedPath,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'uploaded_by' => auth('api')->id(),
        ]);

        $document->load('type:id,document_name,category');
        $this->recordVersion($document, 'uploaded');

        return response()->json($this->serializeDocument($document), 201);
    }

    public function replace(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'document_type_id' => ['nullable', 'integer', 'exists:document_types,id'],
            'expiry_date' => ['nullable', 'date'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $uploadedFile = $request->file('file');
        $original = $uploadedFile->getClientOriginalName();
        $extension = $uploadedFile->getClientOriginalExtension();
        $baseName = pathinfo($original, PATHINFO_FILENAME);
        $safeBaseName = Str::slug($baseName) ?: 'document';
        $storedName = $safeBaseName.'-'.Str::uuid()->toString().'.'.$extension;
        $storedPath = $uploadedFile->storeAs('documents/'.$document->client_id, $storedName, 'local');

        $oldFilePath = $document->file_path;

        if (Storage::disk('local')->exists($oldFilePath)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->update([
            'document_type_id' => (int) ($validated['document_type_id'] ?? $document->document_type_id),
            'expiry_date' => array_key_exists('expiry_date', $validated) ? $validated['expiry_date'] : $document->expiry_date,
            'file_path' => $storedPath,
            'uploaded_by' => auth('api')->id(),
        ]);

        $document->load('type:id,document_name,category');
        $this->recordVersion($document, 'replaced', [
            'old_file_path' => $oldFilePath,
        ]);

        return response()->json($this->serializeDocument($document));
    }

    public function destroy(Document $document): JsonResponse
    {
        $this->recordVersion($document, 'deleted');

        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        return response()->json(null, 204);
    }

    public function download(Document $document)
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404, 'File not found.');

        return response()->download(
            Storage::disk('local')->path($document->file_path),
            basename($document->file_path)
        );
    }

    public function versions(Document $document): JsonResponse
    {
        $history = DocumentVersion::query()
            ->where('document_id', $document->id)
            ->orderByDesc('id')
            ->get(['id', 'version_no', 'action', 'file_path', 'file_hash', 'immutable_payload', 'created_at']);

        return response()->json($history);
    }

    private function serializeDocument(Document $document): array
    {
        $expiryDate = $document->expiry_date;
        $expiresInDays = null;
        $expiryStatus = 'no-expiry';

        if ($expiryDate instanceof Carbon) {
            $expiresInDays = now()->startOfDay()->diffInDays($expiryDate->copy()->startOfDay(), false);

            if ($expiresInDays < 0) {
                $expiryStatus = 'expired';
            } elseif ($expiresInDays <= 30) {
                $expiryStatus = 'expiring';
            } else {
                $expiryStatus = 'valid';
            }
        }

        return [
            'id' => $document->id,
            'client_id' => $document->client_id,
            'document_type_id' => $document->document_type_id,
            'document_type_name' => $document->type?->document_name,
            'category' => $document->type?->category,
            'file_name' => basename($document->file_path),
            'expiry_date' => optional($expiryDate)->toDateString(),
            'expires_in_days' => $expiresInDays,
            'expiry_status' => $expiryStatus,
            'created_at' => optional($document->created_at)->toISOString(),
            'download_url' => route('documents.download', ['document' => $document->id], false),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function recordVersion(Document $document, string $action, array $payload = []): void
    {
        $nextVersion = ((int) DocumentVersion::query()->where('document_id', $document->id)->max('version_no')) + 1;

        $absolutePath = Storage::disk('local')->path($document->file_path);
        $hash = is_file($absolutePath) ? hash_file('sha256', $absolutePath) : null;

        DocumentVersion::query()->create([
            'document_id' => $document->id,
            'client_id' => $document->client_id,
            'document_type_id' => $document->document_type_id,
            'version_no' => max($nextVersion, 1),
            'action' => $action,
            'file_path' => $document->file_path,
            'file_hash' => $hash,
            'replaced_document_id' => $action === 'replaced' ? $document->id : null,
            'uploaded_by' => auth('api')->id(),
            'immutable_payload' => $payload,
            'created_at' => now(),
        ]);
    }
}