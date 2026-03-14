import { CommonModule } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from './core/auth.service';
import { ToastContainerComponent } from './shared/toast-container.component';

@Component({
  selector: 'app-root',
  imports: [CommonModule, RouterLink, RouterLinkActive, RouterOutlet, ToastContainerComponent],
  templateUrl: './app.component.html',
  styleUrl: './app.component.scss'
})
export class AppComponent implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  readonly isAuthenticated = this.auth.isAuthenticated;
  readonly isAdmin = this.auth.isAdmin;
  readonly userName = this.auth.userName;
  readonly booting = signal(true);

  hasPermission(permission: string): boolean {
    return this.auth.hasPermission(permission);
  }

  ngOnInit(): void {
    this.auth.restoreSession().subscribe({
      next: () => {
        this.booting.set(false);
      },
      error: () => {
        this.auth.clearSession();
        this.booting.set(false);
      },
    });
  }

  logout(): void {
    this.auth.logout().subscribe({
      next: () => void this.router.navigateByUrl('/login'),
      error: () => {
        this.auth.clearSession();
        void this.router.navigateByUrl('/login');
      },
    });
  }
}
