import { CommonModule } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import * as XLSX from 'xlsx';
import {
  BulkClientRow,
  ClientDocumentRecord,
  ClientRecord,
  CompanyRecord,
  CreateClientPayload,
  DocumentTypeRecord,
  RmcpApiService,
} from '../core/rmcp-api.service';
import { AuthService } from '../core/auth.service';
import { ToastService } from '../core/toast.service';
import { environment } from '../../environments/environment';

@Component({
  selector: 'app-clients-page',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './clients-page.component.html',
  styleUrl: './clients-page.component.scss'
})
export class ClientsPageComponent implements OnInit {
  private readonly api = inject(RmcpApiService);
  private readonly auth = inject(AuthService);
  private readonly fb = inject(FormBuilder);
  private readonly toast = inject(ToastService);

  readonly loading = signal(false);
  readonly loadingList = signal(false);
  readonly bulkUploading = signal(false);
  readonly errorMessage = signal('');
  readonly clients = signal<ClientRecord[]>([]);
  readonly companies = signal<CompanyRecord[]>([]);
  readonly documentTypes = signal<DocumentTypeRecord[]>([]);
  readonly clientDocuments = signal<ClientDocumentRecord[]>([]);
  readonly loadingDocuments = signal(false);
  readonly uploadingDocument = signal(false);
  readonly deletingDocumentId = signal<number | null>(null);
  readonly replacingDocumentId = signal<number | null>(null);
  readonly selectedDocumentFileName = signal('');
  readonly selectedClientForDocs = signal<number>(0);
  private selectedDocumentFile: File | null = null;

  readonly lowCount = computed(() => this.clients().filter((item) => item.risk_level === 'low').length);
  readonly mediumCount = computed(() => this.clients().filter((item) => item.risk_level === 'medium').length);
  readonly highCount = computed(() => this.clients().filter((item) => item.risk_level === 'high').length);

  readonly form = this.fb.nonNullable.group({
    company_id: [0, [Validators.required, Validators.min(1)]],
    client_type: ['individual', [Validators.required]],
    first_name: [''],
    last_name: [''],
    email: [''],
    phone: [''],
    address: [''],
  });

  readonly documentForm = this.fb.nonNullable.group({
    client_id: [0, [Validators.required, Validators.min(1)]],
    document_type_id: [0, [Validators.required, Validators.min(1)]],
    expiry_date: [''],
  });

  ngOnInit(): void {
    if (this.auth.hasPermission('companies.view')) {
      this.loadCompanies();
    }
    this.loadClients();
    this.loadDocumentTypes();
  }

  downloadBulkTemplate(): void {
    const rows = [
      {
        company_id: this.form.controls.company_id.value || '',
        client_type: 'individual',
        first_name: 'John',
        last_name: 'Doe',
        email: 'john.doe@example.local',
        phone: '+27110000000',
        address: '1 Main Street',
        id_number: '9001015009087',
        passport_number: '',
      },
      {
        company_id: this.form.controls.company_id.value || '',
        client_type: 'corporate',
        first_name: '',
        last_name: '',
        email: 'corp@example.local',
        phone: '+27119999999',
        address: '99 Corporate Ave',
        id_number: '',
        passport_number: '',
      },
    ];

    const worksheet = XLSX.utils.json_to_sheet(rows);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Clients');
    XLSX.writeFile(workbook, 'rmcp-clients-bulk-template.xlsx');
  }

  onBulkFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
      return;
    }

    if (this.companies().length === 0) {
      this.toast.error('No companies available for bulk import.');
      input.value = '';
      return;
    }

    this.bulkUploading.set(true);

    const reader = new FileReader();
    reader.onload = () => {
      try {
        const binary = reader.result;
        const workbook = XLSX.read(binary, { type: 'array' });
        const firstSheetName = workbook.SheetNames[0];

        if (!firstSheetName) {
          throw new Error('Excel file has no sheets.');
        }

        const sheet = workbook.Sheets[firstSheetName];
        const rawRows = XLSX.utils.sheet_to_json<Record<string, unknown>>(sheet, { defval: '' });

        const rows: BulkClientRow[] = rawRows.map((raw) => this.normalizeBulkRow(raw));

        if (rows.length === 0) {
          throw new Error('Excel sheet has no data rows.');
        }

        this.api.bulkCreateClients({
          default_company_id: this.form.controls.company_id.value,
          rows,
        }).subscribe({
          next: (response) => {
            this.bulkUploading.set(false);
            this.toast.success(`Bulk import successful: ${response.created_count} clients added.`);
            this.loadClients();
            input.value = '';
          },
          error: (error) => {
            this.bulkUploading.set(false);
            const backendMessage = error?.error?.message ?? 'Bulk import failed.';
            this.toast.error(backendMessage);
            input.value = '';
          },
        });
      } catch {
        this.bulkUploading.set(false);
        this.toast.error('Could not parse Excel file. Please check format.');
        input.value = '';
      }
    };

    reader.onerror = () => {
      this.bulkUploading.set(false);
      this.toast.error('Failed to read selected file.');
      input.value = '';
    };

    reader.readAsArrayBuffer(file);
  }

  createClient(): void {
    if (this.form.invalid) {
      this.errorMessage.set('Please provide required client fields.');
      this.toast.error('Please complete required client fields.');
      return;
    }

    if (this.companies().length === 0) {
      this.errorMessage.set('No companies available. Please create or seed a company first.');
      this.toast.error('No companies available for client creation.');
      return;
    }

    this.loading.set(true);
    this.errorMessage.set('');

    const payload = this.form.getRawValue() as CreateClientPayload;

    this.api.createClient(payload).subscribe({
      next: (created) => {
        this.form.patchValue({
          first_name: '',
          last_name: '',
          email: '',
          phone: '',
          address: '',
        });
        this.toast.success('Client created successfully.');
        this.loading.set(false);

        // Optimistically prepend new client so UI responds immediately.
        this.clients.update((list) => {
          const updated = [created, ...list];

          if (this.documentForm.controls.client_id.value <= 0) {
            this.selectClientForDocuments(created.id);
          }

          return updated;
        });
      },
      error: () => {
        this.errorMessage.set('Unable to create client.');
        this.toast.error('Unable to create client.');
        this.loading.set(false);
      },
    });
  }

  private loadClients(): void {
    this.loadingList.set(true);
    this.errorMessage.set('');

    this.api.getClients().subscribe({
      next: (response) => {
        this.clients.set(response);
        const selected = this.documentForm.controls.client_id.value;
        const selectedExists = response.some((client) => client.id === selected);

        if (!selectedExists) {
          const firstClientId = response[0]?.id ?? 0;
          this.selectClientForDocuments(firstClientId);
        } else if (selected > 0) {
          this.loadClientDocuments(selected);
        }

        this.loadingList.set(false);
      },
      error: () => {
        this.errorMessage.set('Failed to load clients.');
        this.loadingList.set(false);
      },
    });
  }

  private normalizeBulkRow(raw: Record<string, unknown>): BulkClientRow {
    const value = (key: string): string => {
      const v = raw[key];
      return v == null ? '' : String(v).trim();
    };

    const clientTypeRaw = value('client_type').toLowerCase();
    const clientType = clientTypeRaw === 'corporate' ? 'company' : clientTypeRaw;
    const companyIdRaw = value('company_id');
    const companyId = companyIdRaw ? Number(companyIdRaw) : undefined;

    return {
      company_id: Number.isFinite(companyId ?? NaN) ? companyId : undefined,
      client_type: clientType || 'individual',
      first_name: value('first_name') || undefined,
      last_name: value('last_name') || undefined,
      id_number: value('id_number') || undefined,
      passport_number: value('passport_number') || undefined,
      email: value('email') || undefined,
      phone: value('phone') || undefined,
      address: value('address') || undefined,
    };
  }

  private loadCompanies(): void {
    this.api.getCompanies().subscribe({
      next: (response) => {
        this.companies.set(response ?? []);

        if ((response?.length ?? 0) > 0 && this.form.controls.company_id.value <= 0) {
          this.form.patchValue({ company_id: response[0].id });
        }
      },
      error: (error) => {
        if (error?.status === 403) {
          this.companies.set([]);
          return;
        }

        this.errorMessage.set('Failed to load companies.');
      },
    });
  }

  onDocumentClientChanged(clientIdRaw: string): void {
    const clientId = Number(clientIdRaw);
    this.selectClientForDocuments(Number.isFinite(clientId) ? clientId : 0);
  }

  onDocumentFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    this.selectedDocumentFile = file;
    this.selectedDocumentFileName.set(file?.name ?? '');
  }

  uploadDocument(fileInput: HTMLInputElement): void {
    if (this.documentForm.invalid) {
      this.toast.error('Select a client and document type before uploading.');
      return;
    }

    if (!this.selectedDocumentFile) {
      this.toast.error('Please choose a file to upload.');
      return;
    }

    const { client_id, document_type_id, expiry_date } = this.documentForm.getRawValue();

    this.uploadingDocument.set(true);

    this.api
      .uploadClientDocument(client_id, {
        document_type_id,
        file: this.selectedDocumentFile,
        expiry_date: expiry_date || undefined,
      })
      .subscribe({
        next: (document) => {
          this.uploadingDocument.set(false);
          this.toast.success('Document uploaded successfully.');
          this.clientDocuments.update((list) => [document, ...list]);
          this.selectedDocumentFile = null;
          this.selectedDocumentFileName.set('');
          this.replacingDocumentId.set(null);
          fileInput.value = '';
          this.documentForm.patchValue({ expiry_date: '' });
          this.api.invalidateDashboardCache();
        },
        error: (error) => {
          this.uploadingDocument.set(false);
          const message = error?.error?.message ?? 'Upload failed.';
          this.toast.error(message);
        },
      });
  }

  replaceDocument(document: ClientDocumentRecord, event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (!file) {
      return;
    }

    this.replacingDocumentId.set(document.id);

    this.api.replaceClientDocument(document.id, {
      file,
      document_type_id: document.document_type_id,
      expiry_date: document.expiry_date ?? undefined,
    }).subscribe({
      next: (updated) => {
        this.clientDocuments.update((list) => list.map((item) => (item.id === document.id ? updated : item)));
        this.replacingDocumentId.set(null);
        this.toast.success('Document replaced successfully.');
        input.value = '';
      },
      error: () => {
        this.replacingDocumentId.set(null);
        this.toast.error('Failed to replace document.');
        input.value = '';
      },
    });
  }

  deleteDocument(document: ClientDocumentRecord): void {
    if (!confirm(`Delete ${document.file_name}?`)) {
      return;
    }

    this.deletingDocumentId.set(document.id);

    this.api.deleteClientDocument(document.id).subscribe({
      next: () => {
        this.clientDocuments.update((list) => list.filter((item) => item.id !== document.id));
        this.deletingDocumentId.set(null);
        this.toast.success('Document deleted.');
      },
      error: () => {
        this.deletingDocumentId.set(null);
        this.toast.error('Failed to delete document.');
      },
    });
  }

  expiryBadgeClass(document: ClientDocumentRecord): string {
    switch (document.expiry_status) {
      case 'expired':
        return 'expiry-pill expired';
      case 'expiring':
        return 'expiry-pill expiring';
      case 'valid':
        return 'expiry-pill valid';
      default:
        return 'expiry-pill none';
    }
  }

  expiryBadgeLabel(document: ClientDocumentRecord): string {
    if (document.expiry_status === 'expired') {
      return 'Expired';
    }

    if (document.expiry_status === 'expiring') {
      return `Expiring in ${document.expires_in_days ?? 0} day(s)`;
    }

    if (document.expiry_status === 'valid') {
      return `Valid (${document.expires_in_days ?? '-'} day(s) left)`;
    }

    return 'No expiry date';
  }

  clientDisplayName(client: ClientRecord): string {
    const first = (client.first_name ?? '').trim();
    const last = (client.last_name ?? '').trim();
    const fullName = `${first} ${last}`.trim();

    if (fullName.length > 0) {
      return fullName;
    }

    return `${client.client_type} #${client.id}`;
  }

  documentDownloadUrl(document: ClientDocumentRecord): string {
    return new URL(document.download_url, environment.apiBaseUrl).toString();
  }

  private loadDocumentTypes(): void {
    this.api.getDocumentTypes().subscribe({
      next: (types) => {
        this.documentTypes.set(types ?? []);
        if ((types?.length ?? 0) > 0 && this.documentForm.controls.document_type_id.value <= 0) {
          this.documentForm.patchValue({ document_type_id: types[0].id });
        }
      },
      error: () => {
        this.toast.error('Failed to load document types.');
      },
    });
  }

  private selectClientForDocuments(clientId: number): void {
    this.selectedClientForDocs.set(clientId);
    this.documentForm.patchValue({ client_id: clientId });

    if (clientId > 0) {
      this.loadClientDocuments(clientId);
      return;
    }

    this.clientDocuments.set([]);
  }

  private loadClientDocuments(clientId: number): void {
    this.loadingDocuments.set(true);
    this.api.getClientDocuments(clientId).subscribe({
      next: (documents) => {
        this.clientDocuments.set(documents ?? []);
        this.loadingDocuments.set(false);
      },
      error: () => {
        this.loadingDocuments.set(false);
        this.toast.error('Failed to load documents for selected client.');
      },
    });
  }
}
