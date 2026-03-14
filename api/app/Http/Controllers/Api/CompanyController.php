<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CompanyController extends Controller
{
    public function index(): JsonResponse
    {
        $companies = Cache::remember('api:companies:index', now()->addMinutes(5), static function () {
            return Company::query()
                ->select(['id', 'company_name'])
                ->orderBy('company_name')
                ->get();
        });

        return response()->json($companies);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255', 'unique:companies,registration_number'],
            'tax_number' => ['nullable', 'string', 'max:255', 'unique:companies,tax_number'],
            'industry' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
        ]);

        $company = Company::query()->create($validated);
        $this->flushCompanyCaches();

        return response()->json($company, 201);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => ['sometimes', 'required', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255', 'unique:companies,registration_number,'.$company->id],
            'tax_number' => ['nullable', 'string', 'max:255', 'unique:companies,tax_number,'.$company->id],
            'industry' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
        ]);

        $company->update($validated);
        $this->flushCompanyCaches();

        return response()->json($company);
    }

    public function destroy(Company $company): JsonResponse
    {
        if (Client::query()->where('company_id', $company->id)->exists()) {
            return response()->json(['message' => 'Cannot delete a company with linked clients.'], 422);
        }

        $company->delete();
        $this->flushCompanyCaches();

        return response()->json(null, 204);
    }

    private function flushCompanyCaches(): void
    {
        Cache::forget('api:companies:index');
        Cache::forget('api:dashboard:index');
    }
}
