<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RmcpCase;
use App\Services\AuditLogService;
use App\Services\DocumentGovernanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CaseController extends Controller
{
    public function __construct(private readonly DocumentGovernanceService $documentGovernance)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'stage' => ['nullable', 'string'],
            'client_id' => ['nullable', 'integer'],
            'escalated' => ['nullable', 'boolean'],
        ]);

        $query = RmcpCase::query()
            ->with(['client:id,first_name,last_name,client_type', 'maker:id,name', 'checker:id,name'])
            ->latest('id');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['stage'])) {
            $query->where('stage', $validated['stage']);
        }
        if (! empty($validated['client_id'])) {
            $query->where('client_id', (int) $validated['client_id']);
        }
        if (array_key_exists('escalated', $validated)) {
            $validated['escalated'] ? $query->whereNotNull('escalated_at') : $query->whereNull('escalated_at');
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'checker_id' => ['nullable', 'integer', 'exists:users,id'],
            'sla_due_at' => ['nullable', 'date'],
        ]);

        $case = RmcpCase::query()->create([
            ...$validated,
            'case_number' => 'CASE-'.Str::upper(Str::random(8)),
            'stage' => 'onboarding_review',
            'status' => 'draft',
            'maker_id' => auth('api')->id(),
            'sla_due_at' => $validated['sla_due_at'] ?? now()->addDays(7),
        ]);

        AuditLogService::log(auth('api')->id(), 'create', 'cases', $case->id);

        return response()->json($case->load(['client:id,first_name,last_name,client_type', 'maker:id,name', 'checker:id,name']), 201);
    }

    public function update(Request $request, RmcpCase $case): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'checker_id' => ['nullable', 'integer', 'exists:users,id'],
            'sla_due_at' => ['nullable', 'date'],
            'stage' => ['nullable', 'in:onboarding_review,enhanced_due_diligence,compliance_committee,approved,rejected,ongoing_monitoring,closure'],
        ]);

        $case->update($validated);
        AuditLogService::log(auth('api')->id(), 'update', 'cases', $case->id);

        return response()->json($case->fresh()->load(['client:id,first_name,last_name,client_type', 'maker:id,name', 'checker:id,name']));
    }

    public function submitForReview(RmcpCase $case): JsonResponse
    {
        $case->loadMissing('client');
        $this->documentGovernance->assertClientCanProceed($case->client, 'submit case for review');

        $case->update([
            'stage' => 'onboarding_review',
            'status' => 'pending_review',
            'submitted_at' => now(),
        ]);

        AuditLogService::log(auth('api')->id(), 'submit', 'cases', $case->id);

        return response()->json($case->fresh());
    }

    public function startEnhancedDueDiligence(RmcpCase $case): JsonResponse
    {
        if (! in_array($case->status, ['pending_review', 'draft'], true)) {
            return response()->json(['message' => 'Only pending or draft cases can start EDD.'], 422);
        }

        $case->loadMissing('client');
        $this->documentGovernance->assertClientCanProceed($case->client, 'start enhanced due diligence');

        $case->update([
            'stage' => 'enhanced_due_diligence',
            'status' => 'edd_in_progress',
        ]);

        AuditLogService::log(auth('api')->id(), 'start_edd', 'cases', $case->id);

        return response()->json($case->fresh());
    }

    public function approve(Request $request, RmcpCase $case): JsonResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string'],
        ]);

        $actorId = auth('api')->id();
        if ($actorId === $case->maker_id) {
            return response()->json(['message' => 'Maker-checker policy violation.'], 422);
        }

        $case->loadMissing('client');
        $this->documentGovernance->assertClientCanProceed($case->client, 'approve case');

        $case->update([
            'stage' => 'ongoing_monitoring',
            'status' => 'approved',
            'approved_at' => now(),
            'checker_id' => $actorId,
            'review_notes' => $validated['review_notes'] ?? $case->review_notes,
        ]);

        AuditLogService::log($actorId, 'approve', 'cases', $case->id);

        return response()->json($case->fresh());
    }

    public function reject(Request $request, RmcpCase $case): JsonResponse
    {
        $validated = $request->validate([
            'review_notes' => ['required', 'string'],
        ]);

        $actorId = auth('api')->id();
        if ($actorId === $case->maker_id) {
            return response()->json(['message' => 'Maker-checker policy violation.'], 422);
        }

        $case->update([
            'stage' => 'rejected',
            'status' => 'rejected',
            'checker_id' => $actorId,
            'review_notes' => $validated['review_notes'],
        ]);

        AuditLogService::log($actorId, 'reject', 'cases', $case->id);

        return response()->json($case->fresh());
    }

    public function close(RmcpCase $case): JsonResponse
    {
        if ($case->status !== 'approved') {
            return response()->json(['message' => 'Only approved cases can be closed.'], 422);
        }

        $case->update([
            'stage' => 'closure',
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        AuditLogService::log(auth('api')->id(), 'close', 'cases', $case->id);

        return response()->json($case->fresh());
    }
}
