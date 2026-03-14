import { CommonModule } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { IncidentFilters, IncidentRecord, RmcpApiService } from '../core/rmcp-api.service';
import { ToastService } from '../core/toast.service';

@Component({
  selector: 'app-incidents-page',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './incidents-page.component.html',
  styleUrl: './incidents-page.component.scss'
})
export class IncidentsPageComponent implements OnInit {
  private readonly api = inject(RmcpApiService);
  private readonly fb = inject(FormBuilder);
  private readonly toast = inject(ToastService);

  readonly loading = signal(true);
  readonly errorMessage = signal('');
  readonly incidents = signal<IncidentRecord[]>([]);
  readonly status = signal('');
  readonly severity = signal('');
  readonly createdFrom = signal('');
  readonly createdTo = signal('');
  readonly sortBy = signal<'created_at' | 'severity' | 'status' | 'incident_type'>('created_at');
  readonly sortDir = signal<'asc' | 'desc'>('desc');
  readonly perPage = signal(20);
  readonly currentPage = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);
  readonly submitting = signal(false);

  readonly openCount = computed(() => this.incidents().filter((item) => item.status.toLowerCase() === 'open').length);
  readonly highSeverityCount = computed(() => this.incidents().filter((item) => item.severity.toLowerCase() === 'high').length);

  readonly form = this.fb.nonNullable.group({
    incident_type: ['', [Validators.required]],
    description: ['', [Validators.required]],
    severity: ['medium' as const, [Validators.required]],
    status: ['open' as const, [Validators.required]],
  });

  ngOnInit(): void {
    this.loadIncidents();
  }

  setStatus(value: string): void {
    this.status.set(value);
  }

  setSeverity(value: string): void {
    this.severity.set(value);
  }

  setCreatedFrom(value: string): void {
    this.createdFrom.set(value);
  }

  setCreatedTo(value: string): void {
    this.createdTo.set(value);
  }

  setSortBy(value: 'created_at' | 'severity' | 'status' | 'incident_type'): void {
    this.sortBy.set(value);
  }

  setSortDir(value: 'asc' | 'desc'): void {
    this.sortDir.set(value);
  }

  setPerPage(value: number): void {
    this.perPage.set(value);
    this.currentPage.set(1);
  }

  createIncident(): void {
    if (this.form.invalid) {
      this.toast.error('Please complete all incident fields.');
      return;
    }

    this.submitting.set(true);

    this.api.createIncident(this.form.getRawValue()).subscribe({
      next: () => {
        this.submitting.set(false);
        this.toast.success('Incident created successfully.');
        this.form.patchValue({
          incident_type: '',
          description: '',
          severity: 'medium',
          status: 'open',
        });
        this.currentPage.set(1);
        this.loadIncidents();
      },
      error: () => {
        this.submitting.set(false);
        this.toast.error('Unable to create incident.');
      },
    });
  }

  updateIncidentStatus(incidentId: number, status: string): void {
    this.api.updateIncident(incidentId, { status: status as 'open' | 'pending' | 'resolved' | 'closed' }).subscribe({
      next: () => {
        this.toast.success('Incident status updated.');
        this.loadIncidents();
      },
      error: () => {
        this.toast.error('Unable to update incident status.');
      },
    });
  }

  applyFilters(): void {
    this.currentPage.set(1);
    this.loadIncidents();
  }

  resetFilters(): void {
    this.status.set('');
    this.severity.set('');
    this.createdFrom.set('');
    this.createdTo.set('');
    this.sortBy.set('created_at');
    this.sortDir.set('desc');
    this.perPage.set(20);
    this.currentPage.set(1);
    this.loadIncidents();
  }

  goToPreviousPage(): void {
    if (this.currentPage() <= 1) {
      return;
    }

    this.currentPage.update((page) => page - 1);
    this.loadIncidents();
  }

  goToNextPage(): void {
    if (this.currentPage() >= this.lastPage()) {
      return;
    }

    this.currentPage.update((page) => page + 1);
    this.loadIncidents();
  }

  private loadIncidents(): void {
    this.loading.set(true);
    this.errorMessage.set('');

    const filters: IncidentFilters = {
      status: this.status() || undefined,
      severity: this.severity() || undefined,
      created_from: this.createdFrom() || undefined,
      created_to: this.createdTo() || undefined,
      sort_by: this.sortBy(),
      sort_dir: this.sortDir(),
      per_page: this.perPage(),
      page: this.currentPage(),
    };

    this.api.getIncidents(filters).subscribe({
      next: (response) => {
        this.incidents.set(response.data);
        this.currentPage.set(response.current_page);
        this.lastPage.set(response.last_page);
        this.total.set(response.total);
        this.loading.set(false);
      },
      error: () => {
        this.errorMessage.set('Unable to load incidents.');
        this.loading.set(false);
      },
    });
  }
}
