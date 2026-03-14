import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { AuthService } from './auth.service';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthService);
  const router = inject(Router);
  const token = auth.token();

  const request = !token || req.headers.has('Authorization')
    ? req
    : req.clone({
        setHeaders: {
          Authorization: `Bearer ${token}`,
        },
      });

  return next(request).pipe(
    catchError((error: { status?: number }) => {
      const isAuthEndpoint = req.url.includes('/auth/login') || req.url.includes('/auth/register');

      if (error?.status === 401 && !isAuthEndpoint && auth.isAuthenticated()) {
        auth.clearSession();
        void router.navigateByUrl('/login');
      }

      return throwError(() => error);
    })
  );
};
