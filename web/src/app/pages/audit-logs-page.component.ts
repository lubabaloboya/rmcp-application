import { CommonModule } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { AuditLogRecord, RmcpApiService } from '../core/rmcp-api.service';
import { ToastService } from '../core/toast.service';

@Component({
  selector: 'app-audit-logs-page',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './audit-logs-page.component.html',
  styleUrl: './audit-logs-page.component.scss',
})
export class AuditLogsPageComponent implements OnInit {
  private readonly api = inject(RmcpApiService);
  private readonly toast = inject(ToastService);

  readonly loading = signal(false);
  readonly logs = signal<AuditLogRecord[]>([]);
  readonly query = signal('');
  readonly moduleFilter = signal('');
  readonly actionFilter = signal('');
  readonly createdFrom = signal('');
  readonly createdTo = signal('');
  readonly sortBy = signal<'created_at' | 'module' | 'action' | 'record_id'>('created_at');
  readonly sortDir = signal<'asc' | 'desc'>('desc');
  readonly perPage = signal(25);
  readonly currentPage = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);

  readonly totalVisible = computed(() => this.total());
  readonly uniqueModules = computed(() => new Set(this.logs().map((item) => item.module)).size);
  readonly systemEvents = computed(() => this.logs().filter((item) => !item.user?.id).length);

  readonly moduleOptions = [
    'companies',
    'clients',
    'cases',
    'tasks',
    'communications',
    'incidents',
    'documents',
    'shareholders',
    'beneficial_owners',
  ];

  readonly actionOptions = [
    'create',
    'update',
    'delete',
    'submit',
    'approve',
    'reject',
    'close',
    'start_edd',
  ];

  ngOnInit(): void {
    this.loadLogs();
  }

  setQuery(value: string): void {
    this.query.set(value);
  }

  setModuleFilter(value: string): void {
    this.moduleFilter.set(value);
  }

  setActionFilter(value: string): void {
    this.actionFilter.set(value);
  }

  setCreatedFrom(value: string): void {
    this.createdFrom.set(value);
  }

  setCreatedTo(value: string): void {
    this.createdTo.set(value);
  }

  setSortBy(value: 'created_at' | 'module' | 'action' | 'record_id'): void {
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
    this.loadLogs();
  }

  applyFilters(): void {
    this.currentPage.set(1);
    this.loadLogs();
  }

  resetFilters(): void {
    this.query.set('');
    this.moduleFilter.set('');
    this.actionFilter.set('');
    this.createdFrom.set('');
    this.createdTo.set('');
    this.sortBy.set('created_at');
    this.sortDir.set('desc');
    this.perPage.set(25);
    this.currentPage.set(1);
    this.loadLogs();
  }

  goToPreviousPage(): void {
    if (this.currentPage() <= 1) {
      return;
    }
    this.currentPage.update((page) => page - 1);
    this.loadLogs();
  }

  goToNextPage(): void {
    if (this.currentPage() >= this.lastPage()) {
      return;
    }
    this.currentPage.update((page) => page + 1);
    this.loadLogs();
  }

  actionClass(action: string): string {
    return `action-pill action-${action}`;
  }

  // Saved Views for quick filtering
  readonly savedViews = [
    {
      label: 'Today',
      apply: () => {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const dateStr = `${yyyy}-${mm}-${dd}`;
        this.createdFrom.set(dateStr);
        this.createdTo.set(dateStr);
        this.moduleFilter.set('');
        this.actionFilter.set('');
        this.query.set('');
        this.currentPage.set(1);
        this.loadLogs();
      },
    },
    {
      label: 'Company Changes',
      apply: () => {
        this.moduleFilter.set('companies');
        this.actionFilter.set('update');
        this.createdFrom.set('');
        this.createdTo.set('');
        this.query.set('');
        this.currentPage.set(1);
        this.loadLogs();
      },
    },
    {
      label: 'Access Control Actions',
      apply: () => {
        this.moduleFilter.set('roles');
        this.actionFilter.set('');
        this.createdFrom.set('');
        this.createdTo.set('');
        this.query.set('');
        this.currentPage.set(1);
        this.loadLogs();
      },
    },
  ];

  applySavedView(index: string): void {
    const idx = Number(index);
    if (!isNaN(idx) && idx >= 0 && idx < this.savedViews.length) {
      this.savedViews[idx].apply();
    }
  }

  private loadLogs(): void {
    this.loading.set(true);
    this.api.getAuditLogs({
      module: this.moduleFilter() || undefined,
      action: this.actionFilter() || undefined,
      q: this.query().trim() || undefined,
      created_from: this.createdFrom() || undefined,
      created_to: this.createdTo() || undefined,
      sort_by: this.sortBy(),
      sort_dir: this.sortDir(),
      per_page: this.perPage(),
      page: this.currentPage(),
    }).subscribe({
      next: (response) => {
        this.logs.set(response.data ?? []);
        this.currentPage.set(response.current_page ?? this.currentPage());
        this.lastPage.set(response.last_page ?? 1);
        this.total.set(response.total ?? this.logs().length);
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.toast.error('Failed to load audit logs.');
      },
    });
  }
}
