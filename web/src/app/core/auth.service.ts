import { HttpClient, HttpHeaders } from '@angular/common/http';
import { computed, inject, Injectable, signal } from '@angular/core';
import { Observable, of } from 'rxjs';
import { map, tap } from 'rxjs/operators';
import { environment } from '../../environments/environment';

interface LoginResponse {
  token: string;
  user: {
    name: string;
    role_name?: string | null;
    permissions?: string[];
  };
}

interface AuthMeResponse {
  name: string;
  role_name?: string | null;
  permissions?: string[];
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly apiBase = environment.apiBaseUrl;
  private readonly tokenStorageKey = 'rmcp.jwt';

  readonly token = signal<string>(localStorage.getItem(this.tokenStorageKey) ?? '');
  readonly userName = signal<string>('');
  readonly userRole = signal<string>('');
  readonly permissions = signal<string[]>([]);
  readonly isAuthenticated = computed(() => this.token().length > 0);
  readonly isAdmin = computed(() => this.hasPermission('*') || this.hasPermission('roles.edit'));

  login(credentials: { email: string; password: string }): Observable<void> {
    return this.http.post<LoginResponse>(`${this.apiBase}/auth/login`, credentials).pipe(
      tap((response) => {
        this.setSession(
          response.token,
          response.user?.name ?? 'RMCP User',
          response.user?.role_name ?? '',
          response.user?.permissions ?? []
        );
      }),
      map(() => void 0)
    );
  }

  restoreSession(): Observable<boolean> {
    if (!this.token()) {
      return of(false);
    }

    return this.http.get<AuthMeResponse>(`${this.apiBase}/auth/me`, { headers: this.authHeaders() }).pipe(
      tap((user) => {
        this.userName.set(user?.name ?? 'RMCP User');
        this.userRole.set(user?.role_name ?? '');
        this.permissions.set(user?.permissions ?? []);
      }),
      map(() => true)
    );
  }

  logout(): Observable<void> {
    if (!this.token()) {
      this.clearSession();
      return of(void 0);
    }

    return this.http.post(`${this.apiBase}/auth/logout`, {}).pipe(
      tap(() => this.clearSession()),
      map(() => void 0)
    );
  }

  clearSession(): void {
    localStorage.removeItem(this.tokenStorageKey);
    this.token.set('');
    this.userName.set('');
    this.userRole.set('');
    this.permissions.set([]);
  }

  hasPermission(permission: string): boolean {
    const permissions = this.permissions();
    if (permissions.includes('*') || permissions.includes(permission)) {
      return true;
    }

    const [module] = permission.split('.');

    return permissions.includes(`${module}.*`);
  }

  private setSession(token: string, userName: string, roleName: string, permissions: string[]): void {
    localStorage.setItem(this.tokenStorageKey, token);
    this.token.set(token);
    this.userName.set(userName);
    this.userRole.set(roleName);
    this.permissions.set(Array.isArray(permissions) ? permissions : []);
  }

  private authHeaders(): HttpHeaders {
    return new HttpHeaders({
      Authorization: `Bearer ${this.token()}`,
    });
  }
}
