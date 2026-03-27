import { CommonModule } from '@angular/common';
import { Component, effect, inject, OnInit, signal } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { fromEvent, merge } from 'rxjs';
import { AuthService } from './core/auth.service';
import { LoadingService } from './core/loading.service';
import { ToastService } from './core/toast.service';
import { ToastContainerComponent } from './shared/toast-container.component';

@Component({
  selector: 'app-root',
  imports: [CommonModule, RouterLink, RouterLinkActive, RouterOutlet, ToastContainerComponent],
  templateUrl: './app.component.html',
  styleUrl: './app.component.scss'
})
export class AppComponent implements OnInit {
    sidebarOpen = false;

    isMobile(): boolean {
      return window.innerWidth <= 760;
    }
  private readonly auth = inject(AuthService);
  private readonly loading = inject(LoadingService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly idleTimeoutMs = 2 * 60 * 1000;
  private idleTimer: ReturnType<typeof setTimeout> | null = null;

  readonly isAuthenticated = this.auth.isAuthenticated;
  readonly isAdmin = this.auth.isAdmin;
  readonly userName = this.auth.userName;
  readonly isProcessing = this.loading.isLoading;
  readonly booting = signal(true);

  constructor() {
    merge(
      fromEvent(document, 'click'),
      fromEvent(document, 'keydown'),
      fromEvent(document, 'mousemove'),
      fromEvent(document, 'scroll'),
      fromEvent(document, 'touchstart')
    ).subscribe(() => this.resetIdleTimer());

    effect(() => {
      if (this.isAuthenticated() && !this.booting()) {
        this.resetIdleTimer();
      } else {
        this.clearIdleTimer();
      }
    });
  }

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
    this.auth.logout().subscribe();
    this.clearIdleTimer();
    void this.router.navigateByUrl('/login');
  }

  private resetIdleTimer(): void {
    if (!this.isAuthenticated()) {
      return;
    }

    this.clearIdleTimer();
    this.idleTimer = setTimeout(() => {
      this.auth.clearSession();
      this.toast.info('Session locked due to inactivity. Please sign in again.');
      void this.router.navigateByUrl('/login');
    }, this.idleTimeoutMs);
  }

  private clearIdleTimer(): void {
    if (this.idleTimer) {
      clearTimeout(this.idleTimer);
      this.idleTimer = null;
    }
  }
}
