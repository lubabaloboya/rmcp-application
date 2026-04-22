import { CommonModule } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../core/auth.service';
import { LoadingService } from '../core/loading.service';
import { ToastService } from '../core/toast.service';
import { environment } from '../../environments/environment';

interface User {
  id: number;
  name: string;
  email: string;
  role_name: string;
  role_id?: number;
  company_id?: number;
  status: string;
  last_login_at: string;
  created_at: string;
  updated_at: string;
}

@Component({
  selector: 'app-users-page',
  imports: [CommonModule, FormsModule, ReactiveFormsModule],
  templateUrl: './users-page.component.html',
  styleUrls: ['./users-page.component.scss']
})
export class UsersPageComponent {
  private readonly http = inject(HttpClient);
  private readonly auth = inject(AuthService);
  private readonly loading = inject(LoadingService);
  private readonly toast = inject(ToastService);
  private readonly fb = inject(FormBuilder);
  private readonly apiBase = environment.apiBaseUrl;

  readonly users = signal<User[]>([]);
  readonly showCreateModal = signal(false);
  readonly showEditModal = signal(false);
  readonly editingUser = signal<User | null>(null);
  readonly createForm: FormGroup;
  readonly editForm: FormGroup;

  constructor() {
    this.createForm = this.fb.group({
      name: ['', [Validators.required, Validators.minLength(2)]],
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(8)]],
      role_id: ['', [Validators.required]],
      company_id: [''],
      status: ['active']
    });

    this.editForm = this.fb.group({
      name: ['', [Validators.required, Validators.minLength(2)]],
      email: ['', [Validators.required, Validators.email]],
      role_id: ['', [Validators.required]],
      company_id: [''],
      status: ['']
    });

    this.loadUsers();
  }

  loadUsers() {
    this.http.get<{ data: User[] }>(`${this.apiBase}/users`).subscribe({
      next: (response) => {
        this.users.set(response.data);
      },
      error: (error) => {
        this.toast.error('Failed to load users');
        console.error('Error loading users:', error);
      }
    });
  }

  openCreateUserModal() {
    this.createForm.reset({ status: 'active' });
    this.showCreateModal.set(true);
  }

  closeCreateModal() {
    this.showCreateModal.set(false);
    this.createForm.reset();
  }

  createUser() {
    if (this.createForm.valid) {
      this.loading.start();
      this.http.post<User>(`${this.apiBase}/users`, this.createForm.value).subscribe({
        next: (user) => {
          this.toast.success('User created successfully');
          this.closeCreateModal();
          this.loadUsers();
        },
        error: (error) => {
          this.toast.error('Failed to create user');
          console.error('Error creating user:', error);
        },
        complete: () => {
          this.loading.stop();
        }
      });
    }
  }

  editUser(user: User) {
    this.editingUser.set(user);
    this.editForm.patchValue({
      name: user.name,
      email: user.email,
      role_id: user.role_id,
      company_id: user.company_id,
      status: user.status
    });
    this.showEditModal.set(true);
  }

  closeEditModal() {
    this.showEditModal.set(false);
    this.editingUser.set(null);
    this.editForm.reset();
  }

  updateUser() {
    if (this.editForm.valid && this.editingUser()) {
      this.loading.start();
      const userId = this.editingUser()!.id;
      this.http.patch<User>(`${this.apiBase}/users/${userId}`, this.editForm.value).subscribe({
        next: (user) => {
          this.toast.success('User updated successfully');
          this.closeEditModal();
          this.loadUsers();
        },
        error: (error) => {
          this.toast.error('Failed to update user');
          console.error('Error updating user:', error);
        },
        complete: () => {
          this.loading.stop();
        }
      });
    }
  }

  toggleUserStatus(user: User) {
    this.loading.start();
    this.http.patch<User>(`${this.apiBase}/users/${user.id}/toggle-status`, {}).subscribe({
      next: (updatedUser) => {
        this.toast.success(`User ${updatedUser.status === 'active' ? 'activated' : 'deactivated'} successfully`);
        this.loadUsers();
      },
      error: (error) => {
        this.toast.error('Failed to update user status');
        console.error('Error toggling user status:', error);
      },
      complete: () => {
        this.loading.stop();
      }
    });
  }
}