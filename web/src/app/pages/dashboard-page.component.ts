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
  readonly metrics = computed(() => {
    const dashboard = this.dashboard();

    return [
      { key: 'total', label: 'Total Clients', value: dashboard?.total_clients ?? 0, tone: 'info' },
      { key: 'risk', label: 'High Risk Clients', value: dashboard?.high_risk_clients ?? 0, tone: 'danger' },
      { key: 'tasks', label: 'Pending Tasks', value: dashboard?.pending_tasks ?? 0, tone: 'accent' },
      { key: 'docs', label: 'Documents Expiring', value: dashboard?.documents_expiring ?? 0, tone: 'warn' },
      { key: 'blocked', label: 'Blocked Clients', value: dashboard?.blocked_clients ?? 0, tone: 'danger' },
    ];
  });
  readonly maxMetricValue = computed(() => Math.max(1, ...this.metrics().map((metric) => metric.value)));
  readonly chartData = computed(() => {
    const max = this.maxMetricValue();

    return this.metrics().map((metric) => ({
      ...metric,
      percent: Math.max(6, Math.round((metric.value / max) * 100)),
    }));
  });

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
