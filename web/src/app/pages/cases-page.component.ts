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
    this.api.submitCase(item.id).subscribe({
      next: (updated) => {
        this.patchCase(updated);
        this.toast.success('Case submitted for review.');
      },
      error: () => this.toast.error('Failed to submit case.'),
    });
  }

  approveCase(item: RmcpCaseRecord): void {
    this.api.approveCase(item.id).subscribe({
      next: (updated) => {
        this.patchCase(updated);
        this.toast.success('Case approved.');
      },
      error: (e) => this.toast.error(e?.error?.message ?? 'Failed to approve case.'),
    });
  }

  rejectCase(item: RmcpCaseRecord): void {
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
    this.api.getCases().subscribe({
      next: (list) => {
        this.cases.set(list);
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

  private patchCase(updated: RmcpCaseRecord): void {
    this.cases.update((list) => list.map((item) => (item.id === updated.id ? updated : item)));
  }
}
