import { CommonModule } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { RoleRecord, RmcpApiService } from '../core/rmcp-api.service';
import { ToastService } from '../core/toast.service';

@Component({ selector: 'app-roles-page', standalone: true, imports: [CommonModule], templateUrl: './roles-page.component.html', styleUrl: './roles-page.component.scss' })
export class RolesPageComponent implements OnInit {
  private readonly api = inject(RmcpApiService);
  private readonly toast = inject(ToastService);

  readonly roles = signal<RoleRecord[]>([]);
  readonly catalog = signal<string[]>([]);
  readonly savingRoleId = signal<number | null>(null);

  ngOnInit(): void {
    this.reload();
  }

  hasPermission(role: RoleRecord, permission: string): boolean {
    return role.permissions?.includes('*') || role.permissions?.includes(permission);
  }

  togglePermission(role: RoleRecord, permission: string): void {
    if (role.permissions?.includes('*')) {
      this.toast.error('Cannot edit wildcard permissions directly.');
      return;
    }

    const current = new Set(role.permissions || []);
    if (current.has(permission)) {
      current.delete(permission);
    } else {
      current.add(permission);
    }

    this.savingRoleId.set(role.id);
    this.api.updateRolePermissions(role.id, Array.from(current)).subscribe({
      next: (updated) => {
        this.roles.update((list) => list.map((r) => (r.id === updated.id ? updated : r)));
        this.savingRoleId.set(null);
      },
      error: () => {
        this.savingRoleId.set(null);
        this.toast.error('Failed to update role permissions.');
      },
    });
  }

  private reload(): void {
    this.api.getRolesPermissions().subscribe({
      next: (response) => {
        this.catalog.set(response.permissions_catalog || []);
        this.roles.set(response.roles || []);
      },
      error: () => this.toast.error('Failed to load roles.'),
    });
  }
}
