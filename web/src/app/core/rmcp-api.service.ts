import { HttpClient, HttpParams } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { EMPTY, Observable } from 'rxjs';
import { expand, map, reduce, shareReplay, tap } from 'rxjs/operators';
import { environment } from '../../environments/environment';

export type RiskLevel = 'low' | 'medium' | 'high';

export interface DashboardData {
  total_clients: number;
  high_risk_clients: number;
  wealth_profiles_pending: number;
  wealth_profiles_in_review: number;
  wealth_profiles_approved: number;
  wealth_edd_cases: number;
  compliance_status: string;
  documents_expiring: number;
  blocked_clients: number;
  pending_tasks: number;
  recent_incidents: Array<{ incident_type: string; severity: string; status: string }>;
  recent_screening_matches: Array<{
    client_id: number;
    check_type: string;
    provider?: string | null;
    status: string;
    checked_at?: string | null;
  }>;
}

export interface ClientRecord {
  id: number;
  company_id?: number;
  first_name: string | null;
  last_name: string | null;
  client_type: string;
  email: string | null;
  phone: string | null;
  risk_level: RiskLevel;
  source_of_wealth?: string | null;
  source_of_funds?: string | null;
  annual_income_band?: string | null;
  net_worth_band?: string | null;
  investment_objective?: string | null;
  wealth_profile_status?: 'pending' | 'in_review' | 'approved' | 'rejected';
}

export interface CompanyRecord {
  id: number;
  company_name: string;
  registration_number?: string | null;
  tax_number?: string | null;
  industry?: string | null;
  address?: string | null;
  phone?: string | null;
  email?: string | null;
}

export interface CompanyPayload {
  company_name: string;
  registration_number?: string;
  tax_number?: string;
  industry?: string;
  address?: string;
  phone?: string;
  email?: string;
}

export interface IncidentRecord {
  id: number;
  incident_type: string;
  description: string;
  severity: string;
  status: string;
  created_at: string;
  reporter?: { name: string } | null;
}

export interface CreateIncidentPayload {
  incident_type: string;
  description: string;
  severity: 'low' | 'medium' | 'high';
  status?: 'open' | 'pending' | 'resolved' | 'closed';
}

export interface UpdateIncidentPayload {
  incident_type?: string;
  description?: string;
  severity?: 'low' | 'medium' | 'high';
  status?: 'open' | 'pending' | 'resolved' | 'closed';
}

export interface IncidentFilters {
  status?: string;
  severity?: string;
  created_from?: string;
  created_to?: string;
  sort_by?: 'created_at' | 'severity' | 'status' | 'incident_type';
  sort_dir?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

interface PaginatedClients {
  data: ClientRecord[];
  next_page_url?: string | null;
}

interface PaginatedIncidents {
  data: IncidentRecord[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface IncidentPage {
  data: IncidentRecord[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface ClientsPage {
  data: ClientRecord[];
  current_page: number;
  per_page: number;
  next_page_url: string | null;
  prev_page_url: string | null;
}

export interface CreateClientPayload {
  company_id: number;
  client_type: string;
  first_name?: string;
  last_name?: string;
  email?: string;
  phone?: string;
  address?: string;
  source_of_wealth?: string;
  source_of_funds?: string;
  annual_income_band?: string;
  net_worth_band?: string;
  investment_objective?: string;
  wealth_profile_status?: 'pending' | 'in_review' | 'approved' | 'rejected';
}

export interface BulkClientRow {
  company_id?: number;
  client_type: string;
  first_name?: string;
  last_name?: string;
  id_number?: string;
  passport_number?: string;
  email?: string;
  phone?: string;
  address?: string;
  source_of_wealth?: string;
  source_of_funds?: string;
  annual_income_band?: string;
  net_worth_band?: string;
  investment_objective?: string;
  wealth_profile_status?: 'pending' | 'in_review' | 'approved' | 'rejected';
}

export interface BulkCreateClientsPayload {
  default_company_id?: number;
  rows: BulkClientRow[];
}

export interface BulkCreateClientsResponse {
  message: string;
  created_count: number;
}

export interface RmcpCaseRecord {
  id: number;
  case_number: string;
  title: string;
  description?: string | null;
  stage: string;
  status: string;
  client_id: number;
  maker_id?: number | null;
  checker_id?: number | null;
  sla_due_at?: string | null;
  escalated_at?: string | null;
  review_notes?: string | null;
  client?: { id: number; first_name?: string | null; last_name?: string | null; client_type?: string } | null;
}

export interface DirectorRecord {
  id: number;
  company_id: number;
  first_name: string;
  last_name: string;
  id_number?: string | null;
  position?: string | null;
}

export interface ShareholderRecord {
  id: number;
  company_id: number;
  shareholder_name: string;
  ownership_percentage: number;
}

export interface BeneficialOwnerRecord {
  id: number;
  company_id: number;
  name: string;
  id_number?: string | null;
  ownership_percentage: number;
}

export interface TaskRecord {
  id: number;
  title: string;
  description?: string | null;
  status: string;
  due_date?: string | null;
  assigned_to?: number | null;
}

export interface CommunicationRecord {
  id: number;
  email_subject?: string | null;
  email_body?: string | null;
  sender: string;
  receiver: string;
  linked_client_id?: number | null;
  linked_task_id?: number | null;
}

export interface AuditLogRecord {
  id: number;
  action: string;
  module: string;
  record_id?: number | null;
  created_at: string;
  user?: { id: number; name: string; email: string } | null;
}

export interface RoleRecord {
  id: number;
  role_name: string;
  permissions: string[];
}

export interface RiskRuleRecord {
  id: number;
  rule_key: string;
  label: string;
  weight: number;
  enabled: boolean;
  description?: string | null;
}

export interface DocumentChecklistRecord {
  id: number;
  client_type: 'individual' | 'company';
  document_type_id: number;
  required: boolean;
  document_type?: DocumentTypeRecord;
}

export interface ScreeningCheckRecord {
  id: number;
  client_id: number;
  check_type: string;
  provider?: string | null;
  status: string;
  matched: boolean;
  score: number;
  monitoring_cycle: string;
  checked_at?: string | null;
}

export interface RiskAssessmentRecord {
  id: number;
  client_id: number;
  risk_score: number;
  risk_level: string;
  trigger_reason?: string | null;
  explanation_json?: Array<{ rule_key: string; label: string; weight: number; reason?: string | null }>;
}

export interface ScreeningRunResponse {
  screening: {
    sanctions_check: boolean;
    pep_status: boolean;
    adverse_media_hit: boolean;
    checks: ScreeningCheckRecord[];
  };
  risk_assessment: RiskAssessmentRecord;
}

export interface DocumentVersionRecord {
  id: number;
  version_no: number;
  action: string;
  file_path?: string | null;
  file_hash?: string | null;
  immutable_payload?: Record<string, unknown> | null;
  created_at?: string | null;
}

export interface DocumentTypeRecord {
  id: number;
  document_name: string;
  category: string;
}

export interface ClientDocumentRecord {
  id: number;
  client_id: number;
  document_type_id: number;
  document_type_name: string | null;
  category: string | null;
  file_name: string;
  expiry_date: string | null;
  expires_in_days: number | null;
  expiry_status: 'no-expiry' | 'valid' | 'expiring' | 'expired';
  created_at: string | null;
  download_url: string;
}

interface PaginatedResponse<T> {
  data: T[];
}

@Injectable({ providedIn: 'root' })
export class RmcpApiService {
  private readonly http = inject(HttpClient);
  private readonly apiBase = environment.apiBaseUrl;
  private companiesCache$?: Observable<CompanyRecord[]>;
  private dashboardCache$?: Observable<DashboardData>;
  private dashboardCacheAt = 0;
  private readonly dashboardTtlMs = 20_000;

  getDashboard(): Observable<DashboardData> {
    const now = Date.now();

    if (this.dashboardCache$ && now - this.dashboardCacheAt < this.dashboardTtlMs) {
      return this.dashboardCache$;
    }

    this.dashboardCacheAt = now;
    this.dashboardCache$ = this.http
      .get<DashboardData>(`${this.apiBase}/dashboard`)
      .pipe(shareReplay({ bufferSize: 1, refCount: true }));

    return this.dashboardCache$;
  }

  getClients(): Observable<ClientRecord[]> {
    const perPage = 100;
    const params = new HttpParams()
      .set('per_page', String(perPage))
      .set('page', '1');

    return this.http.get<PaginatedClients>(`${this.apiBase}/clients`, { params }).pipe(
      expand((response) => response.next_page_url ? this.http.get<PaginatedClients>(response.next_page_url) : EMPTY),
      map((response) => response.data ?? []),
      reduce((all, pageItems) => all.concat(pageItems), [] as ClientRecord[])
    );
  }

  getClientsPage(page = 1, perPage = 15): Observable<ClientsPage> {
    const params = new HttpParams()
      .set('page', String(page))
      .set('per_page', String(perPage));

    return this.http.get<ClientsPage>(`${this.apiBase}/clients`, { params }).pipe(
      map((response) => ({
        data: response.data ?? [],
        current_page: response.current_page ?? page,
        per_page: response.per_page ?? perPage,
        next_page_url: response.next_page_url ?? null,
        prev_page_url: response.prev_page_url ?? null,
      }))
    );
  }

  getCompanies(): Observable<CompanyRecord[]> {
    if (!this.companiesCache$) {
      this.companiesCache$ = this.http
        .get<CompanyRecord[]>(`${this.apiBase}/companies`)
        .pipe(shareReplay({ bufferSize: 1, refCount: true }));
    }

    return this.companiesCache$;
  }

  invalidateCompaniesCache(): void {
    this.companiesCache$ = undefined;
  }

  createCompany(payload: CompanyPayload): Observable<CompanyRecord> {
    return this.http.post<CompanyRecord>(`${this.apiBase}/companies`, payload).pipe(tap(() => this.invalidateCompaniesCache()));
  }

  updateCompany(companyId: number, payload: CompanyPayload): Observable<CompanyRecord> {
    return this.http
      .patch<CompanyRecord>(`${this.apiBase}/companies/${companyId}`, payload)
      .pipe(tap(() => this.invalidateCompaniesCache()));
  }

  deleteCompany(companyId: number): Observable<void> {
    return this.http.delete<void>(`${this.apiBase}/companies/${companyId}`).pipe(tap(() => this.invalidateCompaniesCache()));
  }

  invalidateDashboardCache(): void {
    this.dashboardCache$ = undefined;
    this.dashboardCacheAt = 0;
  }

  getIncidents(filters?: IncidentFilters): Observable<IncidentPage> {
    let params = new HttpParams();

    if (filters?.status) {
      params = params.set('status', filters.status);
    }

    if (filters?.severity) {
      params = params.set('severity', filters.severity);
    }

    if (filters?.created_from) {
      params = params.set('created_from', filters.created_from);
    }

    if (filters?.created_to) {
      params = params.set('created_to', filters.created_to);
    }

    if (filters?.sort_by) {
      params = params.set('sort_by', filters.sort_by);
    }

    if (filters?.sort_dir) {
      params = params.set('sort_dir', filters.sort_dir);
    }

    if (filters?.per_page) {
      params = params.set('per_page', String(filters.per_page));
    }

    if (filters?.page) {
      params = params.set('page', String(filters.page));
    }

    return this.http.get<PaginatedIncidents>(`${this.apiBase}/incidents`, { params }).pipe(
      map((response) => ({
        data: response.data ?? [],
        current_page: response.current_page,
        last_page: response.last_page,
        per_page: response.per_page,
        total: response.total,
      }))
    );
  }

  createIncident(payload: CreateIncidentPayload): Observable<IncidentRecord> {
    return this.http
      .post<IncidentRecord>(`${this.apiBase}/incidents`, payload)
      .pipe(tap(() => this.invalidateDashboardCache()));
  }

  updateIncident(incidentId: number, payload: UpdateIncidentPayload): Observable<IncidentRecord> {
    return this.http
      .patch<IncidentRecord>(`${this.apiBase}/incidents/${incidentId}`, payload)
      .pipe(tap(() => this.invalidateDashboardCache()));
  }

  createClient(payload: CreateClientPayload): Observable<ClientRecord> {
    return this.http
      .post<ClientRecord>(`${this.apiBase}/clients`, payload)
      .pipe(tap(() => this.invalidateDashboardCache()));
  }

  bulkCreateClients(payload: BulkCreateClientsPayload): Observable<BulkCreateClientsResponse> {
    return this.http
      .post<BulkCreateClientsResponse>(`${this.apiBase}/clients/bulk`, payload)
      .pipe(tap(() => this.invalidateDashboardCache()));
  }

  getDocumentTypes(): Observable<DocumentTypeRecord[]> {
    return this.http.get<DocumentTypeRecord[]>(`${this.apiBase}/document-types`);
  }

  getClientDocuments(clientId: number): Observable<ClientDocumentRecord[]> {
    return this.http.get<ClientDocumentRecord[]>(`${this.apiBase}/clients/${clientId}/documents`);
  }

  uploadClientDocument(clientId: number, payload: { document_type_id: number; file: File; expiry_date?: string }): Observable<ClientDocumentRecord> {
    const formData = new FormData();
    formData.append('document_type_id', String(payload.document_type_id));
    formData.append('file', payload.file);

    if (payload.expiry_date) {
      formData.append('expiry_date', payload.expiry_date);
    }

    return this.http.post<ClientDocumentRecord>(`${this.apiBase}/clients/${clientId}/documents`, formData);
  }

  replaceClientDocument(documentId: number, payload: { file: File; document_type_id?: number; expiry_date?: string }): Observable<ClientDocumentRecord> {
    const formData = new FormData();
    formData.append('file', payload.file);

    if (payload.document_type_id) {
      formData.append('document_type_id', String(payload.document_type_id));
    }

    if (payload.expiry_date) {
      formData.append('expiry_date', payload.expiry_date);
    }

    return this.http.post<ClientDocumentRecord>(`${this.apiBase}/documents/${documentId}/replace`, formData);
  }

  deleteClientDocument(documentId: number): Observable<void> {
    return this.http.delete<void>(`${this.apiBase}/documents/${documentId}`);
  }

  getCases(): Observable<RmcpCaseRecord[]> {
    return this.http.get<PaginatedResponse<RmcpCaseRecord>>(`${this.apiBase}/cases`).pipe(map((r) => r.data ?? []));
  }

  createCase(payload: { client_id: number; title: string; description?: string; checker_id?: number; sla_due_at?: string }): Observable<RmcpCaseRecord> {
    return this.http.post<RmcpCaseRecord>(`${this.apiBase}/cases`, payload);
  }

  submitCase(caseId: number): Observable<RmcpCaseRecord> {
    return this.http.post<RmcpCaseRecord>(`${this.apiBase}/cases/${caseId}/submit`, {});
  }

  startCaseEdd(caseId: number): Observable<RmcpCaseRecord> {
    return this.http.post<RmcpCaseRecord>(`${this.apiBase}/cases/${caseId}/start-edd`, {});
  }

  approveCase(caseId: number, review_notes?: string): Observable<RmcpCaseRecord> {
    return this.http.post<RmcpCaseRecord>(`${this.apiBase}/cases/${caseId}/approve`, { review_notes });
  }

  rejectCase(caseId: number, review_notes: string): Observable<RmcpCaseRecord> {
    return this.http.post<RmcpCaseRecord>(`${this.apiBase}/cases/${caseId}/reject`, { review_notes });
  }

  closeCase(caseId: number): Observable<RmcpCaseRecord> {
    return this.http.post<RmcpCaseRecord>(`${this.apiBase}/cases/${caseId}/close`, {});
  }

  getCompanyDirectors(company_id?: number): Observable<DirectorRecord[]> {
    const params = company_id ? new HttpParams().set('company_id', String(company_id)) : undefined;
    return this.http.get<DirectorRecord[]>(`${this.apiBase}/company-directors`, { params });
  }

  createCompanyDirector(payload: Omit<DirectorRecord, 'id'>): Observable<DirectorRecord> {
    return this.http.post<DirectorRecord>(`${this.apiBase}/company-directors`, payload);
  }

  updateCompanyDirector(id: number, payload: Partial<Omit<DirectorRecord, 'id' | 'company_id'>>): Observable<DirectorRecord> {
    return this.http.patch<DirectorRecord>(`${this.apiBase}/company-directors/${id}`, payload);
  }

  deleteCompanyDirector(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiBase}/company-directors/${id}`);
  }

  getShareholders(company_id?: number): Observable<ShareholderRecord[]> {
    const params = company_id ? new HttpParams().set('company_id', String(company_id)) : undefined;
    return this.http.get<ShareholderRecord[]>(`${this.apiBase}/shareholders`, { params });
  }

  createShareholder(payload: Omit<ShareholderRecord, 'id'>): Observable<ShareholderRecord> {
    return this.http.post<ShareholderRecord>(`${this.apiBase}/shareholders`, payload);
  }

  deleteShareholder(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiBase}/shareholders/${id}`);
  }

  getBeneficialOwners(company_id?: number): Observable<BeneficialOwnerRecord[]> {
    const params = company_id ? new HttpParams().set('company_id', String(company_id)) : undefined;
    return this.http.get<BeneficialOwnerRecord[]>(`${this.apiBase}/beneficial-owners`, { params });
  }

  createBeneficialOwner(payload: Omit<BeneficialOwnerRecord, 'id'>): Observable<BeneficialOwnerRecord> {
    return this.http.post<BeneficialOwnerRecord>(`${this.apiBase}/beneficial-owners`, payload);
  }

  deleteBeneficialOwner(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiBase}/beneficial-owners/${id}`);
  }

  getTasks(): Observable<TaskRecord[]> {
    return this.http.get<PaginatedResponse<TaskRecord>>(`${this.apiBase}/tasks`).pipe(map((r) => r.data ?? []));
  }

  createTask(payload: Partial<TaskRecord> & { title: string }): Observable<TaskRecord> {
    return this.http.post<TaskRecord>(`${this.apiBase}/tasks`, payload);
  }

  updateTask(id: number, payload: Partial<TaskRecord>): Observable<TaskRecord> {
    return this.http.patch<TaskRecord>(`${this.apiBase}/tasks/${id}`, payload);
  }

  deleteTask(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiBase}/tasks/${id}`);
  }

  getCommunications(): Observable<CommunicationRecord[]> {
    return this.http.get<PaginatedResponse<CommunicationRecord>>(`${this.apiBase}/communications`).pipe(map((r) => r.data ?? []));
  }

  createCommunication(payload: Partial<CommunicationRecord> & { sender: string; receiver: string }): Observable<CommunicationRecord> {
    return this.http.post<CommunicationRecord>(`${this.apiBase}/communications`, payload);
  }

  getAuditLogs(): Observable<AuditLogRecord[]> {
    return this.http.get<PaginatedResponse<AuditLogRecord>>(`${this.apiBase}/audit-logs`).pipe(map((r) => r.data ?? []));
  }

  getRolesPermissions(): Observable<{ permissions_catalog: string[]; roles: RoleRecord[] }> {
    return this.http.get<{ permissions_catalog: string[]; roles: RoleRecord[] }>(`${this.apiBase}/roles`);
  }

  updateRolePermissions(roleId: number, permissions: string[]): Observable<RoleRecord> {
    return this.http.patch<RoleRecord>(`${this.apiBase}/roles/${roleId}`, { permissions });
  }

  getRiskRules(): Observable<RiskRuleRecord[]> {
    return this.http.get<RiskRuleRecord[]>(`${this.apiBase}/risk-rules`);
  }

  updateRiskRule(ruleId: number, payload: Partial<Pick<RiskRuleRecord, 'label' | 'weight' | 'enabled' | 'description'>>): Observable<RiskRuleRecord> {
    return this.http.patch<RiskRuleRecord>(`${this.apiBase}/risk-rules/${ruleId}`, payload);
  }

  getDocumentChecklists(clientType?: 'individual' | 'company'): Observable<DocumentChecklistRecord[]> {
    const params = clientType ? new HttpParams().set('client_type', clientType) : undefined;
    return this.http.get<DocumentChecklistRecord[]>(`${this.apiBase}/document-checklists`, { params });
  }

  replaceDocumentChecklist(clientType: 'individual' | 'company', documentTypeIds: number[]): Observable<{ message: string; client_type: string }> {
    return this.http.put<{ message: string; client_type: string }>(`${this.apiBase}/document-checklists/${clientType}`, {
      document_type_ids: documentTypeIds,
    });
  }

  runClientScreening(clientId: number, monitoringCycle: 'onboarding' | 'ongoing' | 'manual' = 'manual'): Observable<ScreeningRunResponse> {
    return this.http.post<ScreeningRunResponse>(`${this.apiBase}/clients/${clientId}/screenings/run`, {
      monitoring_cycle: monitoringCycle,
    });
  }

  getClientScreenings(clientId: number): Observable<ScreeningCheckRecord[]> {
    return this.http
      .get<PaginatedResponse<ScreeningCheckRecord>>(`${this.apiBase}/clients/${clientId}/screenings`)
      .pipe(map((response) => response.data ?? []));
  }

  getDocumentVersions(documentId: number): Observable<DocumentVersionRecord[]> {
    return this.http.get<DocumentVersionRecord[]>(`${this.apiBase}/documents/${documentId}/versions`);
  }
}
