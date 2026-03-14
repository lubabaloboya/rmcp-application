import { CommonModule } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { AuditLogRecord, RmcpApiService } from '../core/rmcp-api.service';
import { ToastService } from '../core/toast.service';

@Component({ selector: 'app-audit-logs-page', standalone: true, imports: [CommonModule], templateUrl: './audit-logs-page.component.html', styleUrl: './audit-logs-page.component.scss' })
export class AuditLogsPageComponent implements OnInit {
  private readonly api = inject(RmcpApiService);
  private readonly toast = inject(ToastService);

  readonly logs = signal<AuditLogRecord[]>([]);

  ngOnInit(): void {
    this.api.getAuditLogs().subscribe({
      next: (list) => this.logs.set(list),
      error: () => this.toast.error('Failed to load audit logs.'),
    });
  }
}
