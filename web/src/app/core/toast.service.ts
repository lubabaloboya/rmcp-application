import { Injectable, signal } from '@angular/core';

export type ToastType = 'success' | 'error' | 'info';

export interface ToastMessage {
  id: number;
  type: ToastType;
  text: string;
}

@Injectable({ providedIn: 'root' })
export class ToastService {
  readonly toasts = signal<ToastMessage[]>([]);
  private nextId = 1;

  success(text: string): void {
    this.push('success', text);
  }

  error(text: string): void {
    this.push('error', text);
  }

  info(text: string): void {
    this.push('info', text);
  }

  remove(id: number): void {
    this.toasts.update((list) => list.filter((item) => item.id !== id));
  }

  private push(type: ToastType, text: string): void {
    const id = this.nextId++;
    this.toasts.update((list) => [...list, { id, type, text }]);

    setTimeout(() => this.remove(id), 3200);
  }
}
