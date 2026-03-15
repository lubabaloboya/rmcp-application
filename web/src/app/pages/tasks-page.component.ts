import { CommonModule } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RmcpApiService, TaskRecord } from '../core/rmcp-api.service';
import { ToastService } from '../core/toast.service';

@Component({ selector: 'app-tasks-page', standalone: true, imports: [CommonModule, ReactiveFormsModule], templateUrl: './tasks-page.component.html', styleUrl: './tasks-page.component.scss' })
export class TasksPageComponent implements OnInit {
  private readonly api = inject(RmcpApiService);
  private readonly fb = inject(FormBuilder);
  private readonly toast = inject(ToastService);

  readonly loading = signal(false);
  readonly submitting = signal(false);
  readonly statusFilter = signal('all');
  readonly searchTerm = signal('');
  readonly sortBy = signal<'created_at' | 'due_date' | 'status' | 'title'>('created_at');
  readonly sortDir = signal<'asc' | 'desc'>('desc');
  readonly perPage = signal(20);
  readonly currentPage = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);
  readonly tasks = signal<TaskRecord[]>([]);

  readonly pendingCount = computed(() => this.tasks().filter((task) => task.status === 'pending').length);
  readonly inProgressCount = computed(() => this.tasks().filter((task) => task.status === 'in_progress').length);
  readonly doneCount = computed(() => this.tasks().filter((task) => task.status === 'done').length);
  readonly overdueCount = computed(() => this.tasks().filter((task) => this.isOverdue(task)).length);

  readonly form = this.fb.nonNullable.group({
    title: ['', [Validators.required, Validators.minLength(3)]],
    description: [''],
    status: ['pending', Validators.required],
    due_date: [''],
  });

  ngOnInit(): void { this.reload(); }

  setStatusFilter(status: string): void {
    this.statusFilter.set(status);
  }

  setSearch(value: string): void {
    this.searchTerm.set(value);
  }

  clearFilters(): void {
    this.statusFilter.set('all');
    this.searchTerm.set('');
    this.sortBy.set('created_at');
    this.sortDir.set('desc');
    this.perPage.set(20);
    this.currentPage.set(1);
    this.reload();
  }

  applyFilters(): void {
    this.currentPage.set(1);
    this.reload();
  }

  setSortBy(value: 'created_at' | 'due_date' | 'status' | 'title'): void {
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

  createTask(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      this.toast.error('Please fix the highlighted task fields.');
      return;
    }

    this.submitting.set(true);
    const v = this.form.getRawValue();
    this.api.createTask({ title: v.title, description: v.description || undefined, status: v.status, due_date: v.due_date || undefined }).subscribe({
      next: (item) => {
        this.submitting.set(false);
        this.form.patchValue({ title: '', description: '', status: 'pending', due_date: '' });
        this.form.markAsPristine();
        this.form.markAsUntouched();
        this.toast.success('Task created.');
        this.currentPage.set(1);
        this.reload();
      },
      error: () => {
        this.submitting.set(false);
        this.toast.error('Failed to create task.');
      },
    });
  }

  setStatus(item: TaskRecord, status: string): void {
    if (item.status === status) {
      return;
    }

    this.api.updateTask(item.id, { status }).subscribe({
      next: (updated) => {
        this.tasks.update((list) => list.map((t) => (t.id === updated.id ? updated : t)));
        this.toast.success('Task status updated.');
      },
      error: () => this.toast.error('Failed to update task.'),
    });
  }

  deleteTask(item: TaskRecord): void {
    if (!confirm(`Delete task "${item.title}"?`)) {
      return;
    }

    this.api.deleteTask(item.id).subscribe({
      next: () => {
        this.toast.success('Task deleted.');
        if (this.tasks().length <= 1 && this.currentPage() > 1) {
          this.currentPage.update((page) => page - 1);
        }
        this.reload();
      },
      error: () => this.toast.error('Failed to delete task.'),
    });
  }

  isFieldInvalid(field: 'title'): boolean {
    const control = this.form.controls[field];
    return control.invalid && (control.touched || control.dirty);
  }

  fieldError(field: 'title'): string {
    const control = this.form.controls[field];

    if (control.hasError('required')) {
      return 'Title is required.';
    }

    if (control.hasError('minlength')) {
      return 'Title must be at least 3 characters.';
    }

    return '';
  }

  statusLabel(status: string): string {
    if (status === 'in_progress') {
      return 'In Progress';
    }

    if (status === 'done') {
      return 'Done';
    }

    if (status === 'cancelled') {
      return 'Cancelled';
    }

    return 'Pending';
  }

  statusClass(status: string): string {
    return `task-pill status-${status}`;
  }

  dueBadge(task: TaskRecord): string {
    if (!task.due_date) {
      return 'No due date';
    }

    if (this.isOverdue(task)) {
      return 'Overdue';
    }

    const days = this.daysUntilDue(task);
    if (days <= 1) {
      return 'Due soon';
    }

    return `Due in ${days}d`;
  }

  dueClass(task: TaskRecord): string {
    if (!task.due_date) {
      return 'due-pill none';
    }

    if (this.isOverdue(task)) {
      return 'due-pill overdue';
    }

    if (this.daysUntilDue(task) <= 1) {
      return 'due-pill soon';
    }

    return 'due-pill ontrack';
  }

  private daysUntilDue(task: TaskRecord): number {
    if (!task.due_date) {
      return Number.MAX_SAFE_INTEGER;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const dueDate = new Date(task.due_date);
    dueDate.setHours(0, 0, 0, 0);

    const diffMs = dueDate.getTime() - today.getTime();
    return Math.ceil(diffMs / 86400000);
  }

  private isOverdue(task: TaskRecord): boolean {
    return !!task.due_date && this.daysUntilDue(task) < 0 && task.status !== 'done' && task.status !== 'cancelled';
  }

  private reload(): void {
    this.loading.set(true);
    this.api.getTasks({
      status: this.statusFilter() === 'all' ? undefined : this.statusFilter(),
      q: this.searchTerm().trim() || undefined,
      sort_by: this.sortBy(),
      sort_dir: this.sortDir(),
      per_page: this.perPage(),
      page: this.currentPage(),
    }).subscribe({
      next: (response) => {
        this.tasks.set(response.data ?? []);
        this.currentPage.set(response.current_page ?? this.currentPage());
        this.lastPage.set(response.last_page ?? 1);
        this.total.set(response.total ?? this.tasks().length);
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.toast.error('Failed to load tasks.');
      }
    });
  }
}
