import { CommonModule } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ClientRecord, CommunicationRecord, RmcpApiService } from '../core/rmcp-api.service';
import { ToastService } from '../core/toast.service';

@Component({ selector: 'app-communications-page', standalone: true, imports: [CommonModule, ReactiveFormsModule], templateUrl: './communications-page.component.html', styleUrl: './communications-page.component.scss' })
export class CommunicationsPageComponent implements OnInit {
  private readonly api = inject(RmcpApiService);
  private readonly fb = inject(FormBuilder);
  private readonly toast = inject(ToastService);

  readonly loading = signal(false);
  readonly submitting = signal(false);
  readonly clients = signal<ClientRecord[]>([]);
  readonly communications = signal<CommunicationRecord[]>([]);
  readonly searchTerm = signal('');
  readonly selectedClientFilter = signal(0);
  readonly sortBy = signal<'created_at' | 'sender' | 'receiver' | 'email_subject'>('created_at');
  readonly sortDir = signal<'asc' | 'desc'>('desc');
  readonly perPage = signal(20);
  readonly currentPage = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);

  readonly totalCount = computed(() => this.total());
  readonly withSubjectCount = computed(() => this.communications().filter((item) => !!(item.email_subject ?? '').trim()).length);
  readonly withTaskCount = computed(() => this.communications().filter((item) => !!item.linked_task_id).length);

  readonly form = this.fb.nonNullable.group({
    linked_client_id: [0, [Validators.required, Validators.min(1)]],
    sender: ['', [Validators.required, Validators.email]],
    receiver: ['', [Validators.required, Validators.email]],
    email_subject: [''],
    email_body: [''],
  });

  ngOnInit(): void {
    this.loadClients();
    this.reload();
  }

  setSearch(value: string): void {
    this.searchTerm.set(value);
  }

  setClientFilter(raw: string): void {
    const clientId = Number(raw);
    this.selectedClientFilter.set(Number.isFinite(clientId) ? clientId : 0);
  }

  applyFilters(): void {
    this.currentPage.set(1);
    this.reload();
  }

  clearSearch(): void {
    this.searchTerm.set('');
    this.selectedClientFilter.set(0);
    this.sortBy.set('created_at');
    this.sortDir.set('desc');
    this.perPage.set(20);
    this.currentPage.set(1);
    this.reload();
  }

  setSortBy(value: 'created_at' | 'sender' | 'receiver' | 'email_subject'): void {
    this.sortBy.set(value);
  }

  setSortDir(value: 'asc' | 'desc'): void {
    this.sortDir.set(value);
  }

  setPerPage(value: number): void {
    if (!Number.isFinite(value) || value <= 0) {
      return;
    }

    this.perPage.set(value);
    this.currentPage.set(1);
    this.reload();
  }

  goToPreviousPage(): void {
    if (this.currentPage() <= 1) {
      return;
    }

    this.currentPage.update((page) => page - 1);
    this.reload();
  }

  goToNextPage(): void {
    if (this.currentPage() >= this.lastPage()) {
      return;
    }

    this.currentPage.update((page) => page + 1);
    this.reload();
  }

  createCommunication(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      this.toast.error('Please complete the required communication fields.');
      return;
    }

    this.submitting.set(true);
    const payload = this.form.getRawValue();
    this.api.createCommunication(payload).subscribe({
      next: (item) => {
        this.submitting.set(false);
        this.form.patchValue({ sender: '', receiver: '', email_subject: '', email_body: '' });
        this.form.markAsPristine();
        this.form.markAsUntouched();
        this.toast.success('Communication saved.');
        this.currentPage.set(1);
        this.reload();
      },
      error: () => {
        this.submitting.set(false);
        this.toast.error('Failed to save communication.');
      },
    });
  }

  isFieldInvalid(field: 'linked_client_id' | 'sender' | 'receiver'): boolean {
    const control = this.form.controls[field];
    return control.invalid && (control.touched || control.dirty);
  }

  fieldError(field: 'linked_client_id' | 'sender' | 'receiver'): string {
    const control = this.form.controls[field];

    if (control.hasError('required')) {
      if (field === 'linked_client_id') {
        return 'Linked client is required.';
      }

      return field === 'sender' ? 'Sender email is required.' : 'Receiver email is required.';
    }

    if (control.hasError('min')) {
      return 'Choose a client from the list.';
    }

    if (control.hasError('email')) {
      return field === 'sender' ? 'Sender must be a valid email address.' : 'Receiver must be a valid email address.';
    }

    return '';
  }

  clientDisplayName(client: ClientRecord): string {
    const first = (client.first_name ?? '').trim();
    const last = (client.last_name ?? '').trim();
    const fullName = `${first} ${last}`.trim();

    if (fullName.length > 0) {
      return `${fullName} (ID: ${client.id})`;
    }

    return `${client.client_type} #${client.id}`;
  }

  clientDisplayNameById(clientId: number): string {
    const fromRecord = this.communications().find((item) => item.linked_client_id === clientId)?.client;
    if (fromRecord) {
      const first = (fromRecord.first_name ?? '').trim();
      const last = (fromRecord.last_name ?? '').trim();
      const fullName = `${first} ${last}`.trim();
      return fullName.length > 0 ? fullName : `${fromRecord.client_type ?? 'client'} #${clientId}`;
    }

    const fromList = this.clients().find((item) => item.id === clientId);
    if (fromList) {
      return this.clientDisplayName(fromList);
    }

    return `Client #${clientId}`;
  }

  private reload(): void {
    this.loading.set(true);
    this.api.getCommunications({
      linked_client_id: this.selectedClientFilter() > 0 ? this.selectedClientFilter() : undefined,
      q: this.searchTerm().trim() || undefined,
      sort_by: this.sortBy(),
      sort_dir: this.sortDir(),
      per_page: this.perPage(),
      page: this.currentPage(),
    }).subscribe({
      next: (response) => {
        this.communications.set(response.data ?? []);
        this.currentPage.set(response.current_page ?? this.currentPage());
        this.lastPage.set(response.last_page ?? 1);
        this.total.set(response.total ?? this.communications().length);
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.toast.error('Failed to load communications.');
      }
    });
  }

  private loadClients(): void {
    this.api.getClients().subscribe({
      next: (list) => {
        this.clients.set(list ?? []);
        if ((list?.length ?? 0) > 0 && this.form.controls.linked_client_id.value <= 0) {
          this.form.patchValue({ linked_client_id: list[0].id });
        }
      },
      error: () => {
        this.toast.error('Failed to load clients for communication linkage.');
      }
    });
  }
}
