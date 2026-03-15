import { CommonModule } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import {
  ClientRecord,
  DocumentChecklistRecord,
  DocumentTypeRecord,
  RiskAssessmentRecord,
  RiskRuleRecord,
  RmcpApiService,
  ScreeningCheckRecord,
} from '../core/rmcp-api.service';
import { ToastService } from '../core/toast.service';

@Component({
  selector: 'app-compliance-automation-page',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './compliance-automation-page.component.html',
  styleUrl: './compliance-automation-page.component.scss'
})
export class ComplianceAutomationPageComponent implements OnInit {
    // For user-friendly filtering
    readonly ruleFilter = signal('');
    readonly clientFilter = signal('');

    filteredRules = computed(() => {
      const filter = this.ruleFilter().toLowerCase();
      if (!filter) return this.riskRules();
      return this.riskRules().filter(rule =>
        rule.label.toLowerCase().includes(filter) ||
        (rule.description || '').toLowerCase().includes(filter)
      );
    });

    filteredClients = computed(() => {
      const filter = this.clientFilter().toLowerCase();
      if (!filter) return this.clients();
      return this.clients().filter(client =>
        ((client.first_name || '') + ' ' + (client.last_name || '')).toLowerCase().includes(filter) ||
        String(client.id).includes(filter)
      );
    });

    filterRules(value: string): void {
      this.ruleFilter.set(value);
    }

    filterClients(value: string): void {
      this.clientFilter.set(value);
    }
  private readonly api = inject(RmcpApiService);
  private readonly toast = inject(ToastService);

  readonly loading = signal(false);
  readonly savingRuleId = signal<number | null>(null);
  readonly savingChecklist = signal(false);
  readonly screeningInProgress = signal(false);

  readonly riskRules = signal<RiskRuleRecord[]>([]);
  readonly documentTypes = signal<DocumentTypeRecord[]>([]);
  readonly clients = signal<ClientRecord[]>([]);
  readonly screeningHistory = signal<ScreeningCheckRecord[]>([]);
  readonly latestAssessment = signal<RiskAssessmentRecord | null>(null);

  readonly checklistClientType = signal<'individual' | 'company'>('individual');
  readonly requiredTypeIds = signal<number[]>([]);

  readonly selectedClientId = signal<number>(0);

  readonly selectedClientName = computed(() => {
    const id = this.selectedClientId();
    const client = this.clients().find((item) => item.id === id);
    if (!client) {
      return 'No client selected';
    }

    const name = `${client.first_name ?? ''} ${client.last_name ?? ''}`.trim();
    return name || `Client #${client.id}`;
  });

  ngOnInit(): void {
    this.reloadAll();
  }

  reloadAll(): void {
    this.loading.set(true);

    this.api.getRiskRules().subscribe({
      next: (rules) => this.riskRules.set(rules ?? []),
      error: () => this.toast.error('Failed to load risk rules.'),
    });

    this.api.getDocumentTypes().subscribe({
      next: (types) => this.documentTypes.set(types ?? []),
      error: () => this.toast.error('Failed to load document types.'),
    });

    this.api.getClients().subscribe({
      next: (clients) => {
        this.clients.set(clients ?? []);
        if (this.selectedClientId() <= 0 && clients.length > 0) {
          this.selectedClientId.set(clients[0].id);
          this.loadScreeningHistory(clients[0].id);
        }
      },
      error: () => this.toast.error('Failed to load clients.'),
    });

    this.loadChecklist(this.checklistClientType());
    this.loading.set(false);
  }

  loadChecklist(type: 'individual' | 'company'): void {
    this.checklistClientType.set(type);
    this.api.getDocumentChecklists(type).subscribe({
      next: (items) => {
        this.requiredTypeIds.set((items ?? []).map((item) => item.document_type_id));
      },
      error: () => this.toast.error('Failed to load checklist.'),
    });
  }

  toggleRequiredType(documentTypeId: number): void {
    const set = new Set(this.requiredTypeIds());
    if (set.has(documentTypeId)) {
      set.delete(documentTypeId);
    } else {
      set.add(documentTypeId);
    }

    this.requiredTypeIds.set(Array.from(set).sort((a, b) => a - b));
  }

  saveChecklist(): void {
    this.savingChecklist.set(true);
    this.api.replaceDocumentChecklist(this.checklistClientType(), this.requiredTypeIds()).subscribe({
      next: () => {
        this.savingChecklist.set(false);
        this.toast.success('Checklist updated.');
      },
      error: () => {
        this.savingChecklist.set(false);
        this.toast.error('Failed to update checklist.');
      },
    });
  }

  updateRule(rule: RiskRuleRecord, field: 'weight' | 'enabled' | 'description', value: number | boolean | string): void {
    const payload: Partial<RiskRuleRecord> = {
      [field]: value,
    };

    this.savingRuleId.set(rule.id);
    this.api.updateRiskRule(rule.id, payload).subscribe({
      next: (updated) => {
        this.riskRules.update((list) => list.map((item) => (item.id === updated.id ? updated : item)));
        this.savingRuleId.set(null);
      },
      error: () => {
        this.savingRuleId.set(null);
        this.toast.error('Failed to update risk rule.');
      },
    });
  }

  onClientChange(value: string): void {
    const clientId = Number(value);
    if (!Number.isFinite(clientId) || clientId <= 0) {
      this.selectedClientId.set(0);
      this.screeningHistory.set([]);
      this.latestAssessment.set(null);
      return;
    }

    this.selectedClientId.set(clientId);
    this.loadScreeningHistory(clientId);
  }

  runManualScreening(): void {
    const clientId = this.selectedClientId();
    if (clientId <= 0) {
      this.toast.error('Select a client first.');
      return;
    }

    this.screeningInProgress.set(true);
    this.api.runClientScreening(clientId, 'manual').subscribe({
      next: (response) => {
        this.screeningInProgress.set(false);
        this.latestAssessment.set(response.risk_assessment);
        this.toast.success('Screening completed and risk reassessed.');
        this.loadScreeningHistory(clientId);
      },
      error: () => {
        this.screeningInProgress.set(false);
        this.toast.error('Screening run failed.');
      },
    });
  }

  private loadScreeningHistory(clientId: number): void {
    this.api.getClientScreenings(clientId).subscribe({
      next: (history) => this.screeningHistory.set(history ?? []),
      error: () => this.toast.error('Failed to load screening history.'),
    });
  }
}
