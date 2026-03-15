import { CommonModule } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ClientRecord, RmcpApiService, RmcpCaseRecord } from '../core/rmcp-api.service';
import { ToastService } from '../core/toast.service';

@Component({
  selector: 'app-cases-page',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './cases-page.component.html',
  styleUrl: './cases-page.component.scss'
})
export class CasesPageComponent implements OnInit {
  private readonly api = inject(RmcpApiService);
  private readonly fb = inject(FormBuilder);
  private readonly toast = inject(ToastService);

  readonly cases = signal<RmcpCaseRecord[]>([]);
  readonly clients = signal<ClientRecord[]>([]);
  readonly loading = signal(false);
  readonly selectedStatus = signal('');
  readonly selectedStage = signal('');
  readonly selectedClientFilter = signal(0);
  readonly perPage = signal(20);
  readonly currentPage = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);

  readonly workflowRules = [
    'Draft or Rejected -> Submit for Review',
    'Pending Review -> Start EDD (optional)',
    'Pending Review or EDD In Progress -> Approve or Reject',
    'Approved (Ongoing Monitoring) -> Close Case',
  ];

  readonly form = this.fb.nonNullable.group({
    client_id: [0, [Validators.required, Validators.min(1)]],
    title: ['', [Validators.required]],
    description: [''],
    sla_due_at: [''],
  });

  ngOnInit(): void {
    this.loadClients();
    this.loadCases();
  }

  setStatusFilter(value: string): void {
    this.selectedStatus.set(value);
  }

  setStageFilter(value: string): void {
    this.selectedStage.set(value);
  }

  setClientFilter(value: string): void {
    const clientId = Number(value);
    this.selectedClientFilter.set(Number.isFinite(clientId) ? clientId : 0);
  }

  applyFilters(): void {
    this.currentPage.set(1);
    this.loadCases();
  }

  resetFilters(): void {
    this.selectedStatus.set('');
    this.selectedStage.set('');
    this.selectedClientFilter.set(0);
    this.perPage.set(20);
    this.currentPage.set(1);
    this.loadCases();
  }

  setPerPage(value: number): void {
    if (!Number.isFinite(value) || value <= 0) {
      return;
    }

    this.perPage.set(value);
    this.currentPage.set(1);
    this.loadCases();
  }

  goToPreviousPage(): void {
    if (this.currentPage() <= 1) {
      return;
    }

    this.currentPage.update((page) => page - 1);
    this.loadCases();
  }

  goToNextPage(): void {
    if (this.currentPage() >= this.lastPage()) {
      return;
    }

    this.currentPage.update((page) => page + 1);
    this.loadCases();
  }

  createCase(): void {
    if (this.form.invalid) {
      this.toast.error('Client and title are required.');
      return;
    }

    const v = this.form.getRawValue();
    this.api.createCase({
      client_id: v.client_id,
      title: v.title,
      description: v.description || undefined,
      sla_due_at: v.sla_due_at || undefined,
    }).subscribe({
      next: (item) => {
        this.toast.success('Case created.');
        this.cases.update((list) => [item, ...list]);
        this.form.patchValue({ title: '', description: '', sla_due_at: '' });
      },
      error: () => this.toast.error('Failed to create case.'),
    });
  }

  submitCase(item: RmcpCaseRecord): void {
    if (!this.canSubmit(item)) {
      this.toast.error('This case cannot be submitted in its current state.');
      return;
    }

    this.api.submitCase(item.id).subscribe({
      next: (updated) => {
        this.patchCase(updated);
        this.toast.success('Case submitted for review.');
      },
      error: () => this.toast.error('Failed to submit case.'),
    });
  }

  startEdd(item: RmcpCaseRecord): void {
    if (!this.canStartEdd(item)) {
      this.toast.error('EDD can only start for pending review onboarding cases.');
      return;
    }

    this.api.startCaseEdd(item.id).subscribe({
      next: (updated) => {
        this.patchCase(updated);
        this.toast.success('Enhanced due diligence started.');
      },
      error: (e) => this.toast.error(e?.error?.message ?? 'Failed to start EDD.'),
    });
  }

  approveCase(item: RmcpCaseRecord): void {
    if (!this.canApprove(item)) {
      this.toast.error('This case cannot be approved in its current state.');
      return;
    }

    this.api.approveCase(item.id).subscribe({
      next: (updated) => {
        this.patchCase(updated);
        this.toast.success('Case approved.');
      },
      error: (e) => this.toast.error(e?.error?.message ?? 'Failed to approve case.'),
    });
  }

  rejectCase(item: RmcpCaseRecord): void {
    if (!this.canReject(item)) {
      this.toast.error('This case cannot be rejected in its current state.');
      return;
    }

    const note = prompt('Rejection reason:') ?? '';
    if (!note.trim()) {
      return;
    }

    this.api.rejectCase(item.id, note).subscribe({
      next: (updated) => {
        this.patchCase(updated);
        this.toast.success('Case rejected.');
      },
      error: (e) => this.toast.error(e?.error?.message ?? 'Failed to reject case.'),
    });
  }

  closeCase(item: RmcpCaseRecord): void {
    if (!this.canClose(item)) {
      this.toast.error('Only approved ongoing-monitoring cases can be closed.');
      return;
    }

    this.api.closeCase(item.id).subscribe({
      next: (updated) => {
        this.patchCase(updated);
        this.toast.success('Case closed.');
      },
      error: (e) => this.toast.error(e?.error?.message ?? 'Failed to close case.'),
    });
  }

  private loadCases(): void {
    this.loading.set(true);
    this.api.getCases({
      status: this.selectedStatus() || undefined,
      stage: this.selectedStage() || undefined,
      client_id: this.selectedClientFilter() || undefined,
      per_page: this.perPage(),
      page: this.currentPage(),
    }).subscribe({
      next: (response) => {
        this.cases.set(response.data ?? []);
        this.currentPage.set(response.current_page);
        this.lastPage.set(response.last_page);
        this.total.set(response.total);
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.toast.error('Failed to load cases.');
      },
    });
  }

  private loadClients(): void {
    this.api.getClients().subscribe({
      next: (list) => {
        this.clients.set(list ?? []);

        const currentClientId = this.form.controls.client_id.value;
        if (currentClientId <= 0 && list.length > 0) {
          this.form.patchValue({ client_id: list[0].id });
        }
      },
      error: () => this.toast.error('Failed to load clients.'),
    });
  }

  formatClientLabel(client: ClientRecord): string {
    const name = `${client.first_name ?? ''} ${client.last_name ?? ''}`.trim();
    return name ? `${name} (#${client.id})` : `Client #${client.id}`;
  }

  formatCaseClient(item: RmcpCaseRecord): string {
    if (item.client) {
      const name = `${item.client.first_name ?? ''} ${item.client.last_name ?? ''}`.trim();
      return name ? `${name} (#${item.client.id})` : `Client #${item.client.id}`;
    }

    const client = this.clients().find((candidate) => candidate.id === item.client_id);
    if (client) {
      return this.formatClientLabel(client);
    }

    return `Client #${item.client_id}`;
  }

  stageLabel(stage: string): string {
    const map: Record<string, string> = {
      onboarding_review: 'Onboarding Review',
      enhanced_due_diligence: 'Enhanced Due Diligence',
      compliance_committee: 'Compliance Committee',
      ongoing_monitoring: 'Ongoing Monitoring',
      approved: 'Approved',
      rejected: 'Rejected',
      closure: 'Closure',
    };

    return map[stage] ?? stage;
  }

  statusLabel(status: string): string {
    const map: Record<string, string> = {
      draft: 'Draft',
      pending_review: 'Pending Review',
      edd_in_progress: 'EDD In Progress',
      approved: 'Approved',
      rejected: 'Rejected',
      closed: 'Closed',
    };

    return map[status] ?? status;
  }

  stageClass(stage: string): string {
    return `stage-pill stage-${stage}`;
  }

  statusClass(status: string): string {
    return `status-pill status-${status}`;
  }

  canSubmit(item: RmcpCaseRecord): boolean {
    return item.status === 'draft' || item.status === 'rejected';
  }

  canStartEdd(item: RmcpCaseRecord): boolean {
    return item.status === 'pending_review' && item.stage === 'onboarding_review';
  }

  canApprove(item: RmcpCaseRecord): boolean {
    return (item.status === 'pending_review' || item.status === 'edd_in_progress')
      && (item.stage === 'onboarding_review' || item.stage === 'enhanced_due_diligence');
  }

  canReject(item: RmcpCaseRecord): boolean {
    return this.canApprove(item);
  }

  canClose(item: RmcpCaseRecord): boolean {
    return item.status === 'approved' && item.stage === 'ongoing_monitoring';
  }

  nextActionHint(item: RmcpCaseRecord): string {
    if (this.canSubmit(item)) {
      return 'Next: Submit for Review';
    }

    if (this.canStartEdd(item)) {
      return 'Next: Start EDD or Approve/Reject';
    }

    if (this.canApprove(item)) {
      return 'Next: Approve or Reject';
    }

    if (this.canClose(item)) {
      return 'Next: Close Case';
    }

    if (item.status === 'closed') {
      return 'Completed';
    }

    return 'No action available';
  }

  private patchCase(updated: RmcpCaseRecord): void {
    this.cases.update((list) => list.map((item) => (item.id === updated.id ? updated : item)));
  }
}
