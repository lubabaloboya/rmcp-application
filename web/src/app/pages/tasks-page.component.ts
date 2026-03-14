import { CommonModule } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RmcpApiService, TaskRecord } from '../core/rmcp-api.service';
import { ToastService } from '../core/toast.service';

@Component({ selector: 'app-tasks-page', standalone: true, imports: [CommonModule, ReactiveFormsModule], templateUrl: './tasks-page.component.html', styleUrl: './tasks-page.component.scss' })
export class TasksPageComponent implements OnInit {
  private readonly api = inject(RmcpApiService);
  private readonly fb = inject(FormBuilder);
  private readonly toast = inject(ToastService);

  readonly tasks = signal<TaskRecord[]>([]);

  readonly form = this.fb.nonNullable.group({
    title: ['', Validators.required],
    description: [''],
    status: ['pending'],
    due_date: [''],
  });

  ngOnInit(): void { this.reload(); }

  createTask(): void {
    if (this.form.invalid) { return; }
    const v = this.form.getRawValue();
    this.api.createTask({ title: v.title, description: v.description || undefined, status: v.status, due_date: v.due_date || undefined }).subscribe({
      next: (item) => { this.tasks.update((list) => [item, ...list]); this.form.patchValue({ title: '', description: '' }); this.toast.success('Task created.'); },
      error: () => this.toast.error('Failed to create task.'),
    });
  }

  setStatus(item: TaskRecord, status: string): void {
    this.api.updateTask(item.id, { status }).subscribe({
      next: (updated) => this.tasks.update((list) => list.map((t) => (t.id === updated.id ? updated : t))),
      error: () => this.toast.error('Failed to update task.'),
    });
  }

  deleteTask(item: TaskRecord): void {
    this.api.deleteTask(item.id).subscribe({
      next: () => this.tasks.update((list) => list.filter((t) => t.id !== item.id)),
      error: () => this.toast.error('Failed to delete task.'),
    });
  }

  private reload(): void {
    this.api.getTasks().subscribe({ next: (list) => this.tasks.set(list), error: () => this.toast.error('Failed to load tasks.') });
  }
}
