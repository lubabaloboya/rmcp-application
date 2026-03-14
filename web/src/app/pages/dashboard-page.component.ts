import { CommonModule } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { DashboardData, RmcpApiService } from '../core/rmcp-api.service';

@Component({
  selector: 'app-dashboard-page',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './dashboard-page.component.html',
  styleUrl: './dashboard-page.component.scss'
})
export class DashboardPageComponent implements OnInit {
  private readonly api = inject(RmcpApiService);

  readonly loading = signal(true);
  readonly errorMessage = signal('');
  readonly dashboard = signal<DashboardData | null>(null);
  readonly incidentCount = computed(() => this.dashboard()?.recent_incidents.length ?? 0);
  readonly screeningMatchCount = computed(() => this.dashboard()?.recent_screening_matches.length ?? 0);

  ngOnInit(): void {
    this.loadData();
  }

  private loadData(): void {
    this.loading.set(true);
    this.errorMessage.set('');

    this.api.getDashboard().subscribe({
      next: (response) => {
        this.dashboard.set(response);
        this.loading.set(false);
      },
      error: () => {
        this.errorMessage.set('Unable to load dashboard data.');
        this.loading.set(false);
      },
    });
  }
}
