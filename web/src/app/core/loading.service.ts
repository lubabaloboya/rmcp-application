import { computed, Injectable, signal } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class LoadingService {
  private readonly pending = signal(0);
  private readonly visible = signal(false);
  private showTimer: ReturnType<typeof setTimeout> | null = null;
  private readonly showDelayMs = 400;

  readonly pendingRequests = computed(() => this.pending());
  readonly isLoading = computed(() => this.visible());

  start(): void {
    this.pending.update((value) => value + 1);

    if (this.pending() === 1) {
      if (this.showTimer) {
        clearTimeout(this.showTimer);
      }

      this.showTimer = setTimeout(() => {
        if (this.pending() > 0) {
          this.visible.set(true);
        }
      }, this.showDelayMs);
    }
  }

  stop(): void {
    this.pending.update((value) => Math.max(0, value - 1));

    if (this.pending() === 0) {
      if (this.showTimer) {
        clearTimeout(this.showTimer);
        this.showTimer = null;
      }

      this.visible.set(false);
    }
  }
}
