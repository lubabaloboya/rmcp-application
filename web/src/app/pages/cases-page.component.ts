import { CommonModule } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RmcpApiService, RmcpCaseRecord } from '../core/rmcp-api.service';
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
  readonly loading = signal(false);

  readonly form = this.fb.nonNullable.group({
    client_id: [0, [Validators.required, Validators.min(1)]],
    title: ['', [Validators.required]],
    description: [''],
    sla_due_at: [''],
  });

  ngOnInit(): void {
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

  private patchCase(updated: RmcpCaseRecord): void {
    this.cases.update((list) => list.map((item) => (item.id === updated.id ? updated : item)));
  }
}
