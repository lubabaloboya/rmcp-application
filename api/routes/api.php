<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentChecklistController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\RiskRuleController;
use App\Http\Controllers\Api\RiskAssessmentController;
use App\Http\Controllers\Api\ScreeningController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BeneficialOwnerController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\CommunicationController;
use App\Http\Controllers\Api\CompanyDirectorController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ShareholderController;
use App\Http\Controllers\Api\TaskController;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/document-types', [DocumentController::class, 'types'])->middleware('permission:documents.view');
        Route::get('/companies', [CompanyController::class, 'index'])->middleware('permission:companies.view');
        Route::post('/companies', [CompanyController::class, 'store'])->middleware('permission:companies.create');
        Route::patch('/companies/{company}', [CompanyController::class, 'update'])->middleware('permission:companies.edit');
        Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->middleware('permission:companies.delete');
        Route::get('/incidents', [IncidentController::class, 'index'])->middleware('permission:incidents.view');
        Route::post('/incidents', [IncidentController::class, 'store'])->middleware('permission:incidents.create');
        Route::patch('/incidents/{incident}', [IncidentController::class, 'update'])->middleware('permission:incidents.edit');

        Route::get('/cases', [CaseController::class, 'index'])->middleware('permission:cases.view');
        Route::post('/cases', [CaseController::class, 'store'])->middleware('permission:cases.create');
        Route::patch('/cases/{case}', [CaseController::class, 'update'])->middleware('permission:cases.edit');
        Route::post('/cases/{case}/submit', [CaseController::class, 'submitForReview'])->middleware('permission:cases.submit');
        Route::post('/cases/{case}/approve', [CaseController::class, 'approve'])->middleware('permission:cases.approve');
        Route::post('/cases/{case}/reject', [CaseController::class, 'reject'])->middleware('permission:cases.approve');
        Route::post('/cases/{case}/close', [CaseController::class, 'close'])->middleware('permission:cases.close');

        Route::get('/company-directors', [CompanyDirectorController::class, 'index'])->middleware('permission:directors.view');
        Route::post('/company-directors', [CompanyDirectorController::class, 'store'])->middleware('permission:directors.create');
        Route::patch('/company-directors/{director}', [CompanyDirectorController::class, 'update'])->middleware('permission:directors.edit');
        Route::delete('/company-directors/{director}', [CompanyDirectorController::class, 'destroy'])->middleware('permission:directors.delete');

        Route::get('/shareholders', [ShareholderController::class, 'index'])->middleware('permission:shareholders.view');
        Route::post('/shareholders', [ShareholderController::class, 'store'])->middleware('permission:shareholders.create');
        Route::patch('/shareholders/{shareholder}', [ShareholderController::class, 'update'])->middleware('permission:shareholders.edit');
        Route::delete('/shareholders/{shareholder}', [ShareholderController::class, 'destroy'])->middleware('permission:shareholders.delete');

        Route::get('/beneficial-owners', [BeneficialOwnerController::class, 'index'])->middleware('permission:beneficial_owners.view');
        Route::post('/beneficial-owners', [BeneficialOwnerController::class, 'store'])->middleware('permission:beneficial_owners.create');
        Route::patch('/beneficial-owners/{beneficialOwner}', [BeneficialOwnerController::class, 'update'])->middleware('permission:beneficial_owners.edit');
        Route::delete('/beneficial-owners/{beneficialOwner}', [BeneficialOwnerController::class, 'destroy'])->middleware('permission:beneficial_owners.delete');

        Route::get('/tasks', [TaskController::class, 'index'])->middleware('permission:tasks.view');
        Route::post('/tasks', [TaskController::class, 'store'])->middleware('permission:tasks.create');
        Route::patch('/tasks/{task}', [TaskController::class, 'update'])->middleware('permission:tasks.edit');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->middleware('permission:tasks.delete');

        Route::get('/communications', [CommunicationController::class, 'index'])->middleware('permission:communications.view');
        Route::post('/communications', [CommunicationController::class, 'store'])->middleware('permission:communications.create');
        Route::patch('/communications/{communication}', [CommunicationController::class, 'update'])->middleware('permission:communications.edit');
        Route::delete('/communications/{communication}', [CommunicationController::class, 'destroy'])->middleware('permission:communications.delete');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit_logs.view');
        Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view');
        Route::patch('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.edit');
        Route::get('/risk-rules', [RiskRuleController::class, 'index'])->middleware('permission:roles.view');
        Route::patch('/risk-rules/{riskRule}', [RiskRuleController::class, 'update'])->middleware('permission:roles.edit');
        Route::get('/document-checklists', [DocumentChecklistController::class, 'index'])->middleware('permission:documents.view');
        Route::put('/document-checklists/{clientType}', [DocumentChecklistController::class, 'replaceForClientType'])->middleware('permission:documents.edit');

        Route::post('/clients/bulk', [ClientController::class, 'bulkStore'])->middleware('permission:clients.create');
        Route::get('/clients', [ClientController::class, 'index'])->middleware('permission:clients.view');
        Route::post('/clients', [ClientController::class, 'store'])->middleware('permission:clients.create');
        Route::get('/clients/{client}', [ClientController::class, 'show'])->middleware('permission:clients.view');
        Route::put('/clients/{client}', [ClientController::class, 'update'])->middleware('permission:clients.edit');
        Route::patch('/clients/{client}', [ClientController::class, 'update'])->middleware('permission:clients.edit');
        Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->middleware('permission:clients.delete');

        Route::get('/clients/{client}/documents', [DocumentController::class, 'index'])->middleware('permission:documents.view');
        Route::post('/clients/{client}/documents', [DocumentController::class, 'store'])->middleware('permission:documents.create');
        Route::post('/documents/{document}/replace', [DocumentController::class, 'replace'])->middleware('permission:documents.edit');
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->middleware('permission:documents.delete');
        Route::get('/documents/{document}/versions', [DocumentController::class, 'versions'])->middleware('permission:documents.view');
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->middleware('permission:documents.view')->name('documents.download');
        Route::post('/clients/{client}/screenings/run', [ScreeningController::class, 'run'])->middleware('permission:clients.edit');
        Route::get('/clients/{client}/screenings', [ScreeningController::class, 'history'])->middleware('permission:clients.view');
        Route::post('/clients/{client}/risk-assessment', [RiskAssessmentController::class, 'store'])->middleware('permission:clients.edit');
        Route::get('/clients/{client}/risk-assessment', [RiskAssessmentController::class, 'show'])->middleware('permission:clients.view');
    });
});
