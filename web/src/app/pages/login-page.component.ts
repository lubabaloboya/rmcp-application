import { CommonModule } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../core/auth.service';
import { ToastService } from '../core/toast.service';

@Component({
  selector: 'app-login-page',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './login-page.component.html',
  styleUrl: './login-page.component.scss'
})
export class LoginPageComponent {
  private readonly auth = inject(AuthService);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  readonly loading = signal(false);
  readonly errorMessage = signal('');

  readonly form = this.fb.nonNullable.group({
    email: ['admin@rmcp.local', [Validators.required, Validators.email]],
    password: ['Admin@12345', [Validators.required]],
  });

  login(): void {
    if (this.form.invalid) {
      this.errorMessage.set('Please provide valid credentials.');
      return;
    }

    this.loading.set(true);
    this.errorMessage.set('');

    this.auth.login(this.form.getRawValue()).subscribe({
      next: () => {
        this.loading.set(false);
        this.toast.success('Signed in successfully.');
        void this.router.navigateByUrl('/dashboard');
      },
      error: () => {
        this.loading.set(false);
        this.errorMessage.set('Login failed. Confirm credentials and try again.');
        this.toast.error('Sign in failed. Please verify your credentials.');
      },
    });
  }
}
