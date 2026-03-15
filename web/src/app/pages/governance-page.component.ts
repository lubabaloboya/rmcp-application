import { CommonModule } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { BeneficialOwnerRecord, CompanyRecord, DirectorRecord, RmcpApiService, ShareholderRecord } from '../core/rmcp-api.service';
import { ToastService } from '../core/toast.service';

@Component({
  selector: 'app-governance-page',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './governance-page.component.html',
  styleUrl: './governance-page.component.scss'
})
export class GovernancePageComponent implements OnInit {
  private readonly api = inject(RmcpApiService);
  private readonly fb = inject(FormBuilder);
  private readonly toast = inject(ToastService);

  readonly directors = signal<DirectorRecord[]>([]);
  readonly shareholders = signal<ShareholderRecord[]>([]);
  readonly owners = signal<BeneficialOwnerRecord[]>([]);
  readonly companies = signal<CompanyRecord[]>([]);
  readonly selectedCompanyId = signal(0);
  readonly selectedCompanyName = computed(() => {
    const current = this.companies().find((company) => company.id === this.selectedCompanyId());
    return current?.company_name ?? 'No company selected';
  });
  readonly shareholderTotal = computed(() => this.shareholders().reduce((sum, row) => sum + Number(row.ownership_percentage || 0), 0));
  readonly ownerTotal = computed(() => this.owners().reduce((sum, row) => sum + Number(row.ownership_percentage || 0), 0));
  readonly shareholderRemaining = computed(() => Math.max(0, Number((100 - this.shareholderTotal()).toFixed(2))));
  readonly ownerRemaining = computed(() => Math.max(0, Number((100 - this.ownerTotal()).toFixed(2))));

  readonly directorForm = this.fb.nonNullable.group({
    company_id: [0, [Validators.required, Validators.min(1)]],
    first_name: ['', Validators.required],
    last_name: ['', Validators.required],
    id_number: [''],
    position: [''],
  });

  readonly shareholderForm = this.fb.nonNullable.group({
    company_id: [0, [Validators.required, Validators.min(1)]],
    shareholder_name: ['', Validators.required],
    ownership_percentage: [0, [Validators.required, Validators.min(0), Validators.max(100)]],
  });

  readonly ownerForm = this.fb.nonNullable.group({
    company_id: [0, [Validators.required, Validators.min(1)]],
    name: ['', Validators.required],
    id_number: [''],
    ownership_percentage: [0, [Validators.required, Validators.min(0), Validators.max(100)]],
  });

  ngOnInit(): void {
    this.loadCompanies();
  }

  onCompanyChange(raw: string): void {
    const companyId = Number(raw);
    const normalized = Number.isFinite(companyId) && companyId > 0 ? companyId : 0;
    this.selectedCompanyId.set(normalized);
    this.syncFormsCompanyId(normalized);
    this.reloadAll(normalized);
  }

  addDirector(): void {
    if (this.selectedCompanyId() <= 0) {
      this.toast.error('Select a company first.');
      return;
    }

    if (this.directorForm.invalid) {
      this.directorForm.markAllAsTouched();
      this.toast.error('Please fix director form errors.');
      return;
    }
    this.api.createCompanyDirector(this.directorForm.getRawValue()).subscribe({
      next: (item) => {
        this.directors.update((x) => [item, ...x]);
        this.directorForm.patchValue({ first_name: '', last_name: '', id_number: '', position: '' });
        this.toast.success('Director added.');
      },
      error: () => this.toast.error('Failed to add director.'),
    });
  }

  deleteDirector(item: DirectorRecord): void {
    this.api.deleteCompanyDirector(item.id).subscribe({
      next: () => this.directors.update((x) => x.filter((d) => d.id !== item.id)),
      error: () => this.toast.error('Failed to delete director.'),
    });
  }

  addShareholder(): void {
    if (this.selectedCompanyId() <= 0) {
      this.toast.error('Select a company first.');
      return;
    }

    const requested = Number(this.shareholderForm.controls.ownership_percentage.value || 0);
    if ((this.shareholderTotal() + requested) > 100) {
      this.toast.error(`Shareholder ownership exceeds 100%. Remaining: ${this.shareholderRemaining()}%`);
      return;
    }

    if (this.shareholderForm.invalid) {
      this.shareholderForm.markAllAsTouched();
      this.toast.error('Please fix shareholder form errors.');
      return;
    }
    this.api.createShareholder(this.shareholderForm.getRawValue()).subscribe({
      next: (item) => {
        this.shareholders.update((x) => [item, ...x]);
        this.shareholderForm.patchValue({ shareholder_name: '', ownership_percentage: 0 });
        this.toast.success('Shareholder added.');
      },
      error: () => this.toast.error('Failed to add shareholder.'),
    });
  }

  deleteShareholder(item: ShareholderRecord): void {
    this.api.deleteShareholder(item.id).subscribe({
      next: () => this.shareholders.update((x) => x.filter((d) => d.id !== item.id)),
      error: () => this.toast.error('Failed to delete shareholder.'),
    });
  }

  addOwner(): void {
    if (this.selectedCompanyId() <= 0) {
      this.toast.error('Select a company first.');
      return;
    }

    const requested = Number(this.ownerForm.controls.ownership_percentage.value || 0);
    if ((this.ownerTotal() + requested) > 100) {
      this.toast.error(`Beneficial ownership exceeds 100%. Remaining: ${this.ownerRemaining()}%`);
      return;
    }

    if (this.ownerForm.invalid) {
      this.ownerForm.markAllAsTouched();
      this.toast.error('Please fix beneficial owner form errors.');
      return;
    }
    this.api.createBeneficialOwner(this.ownerForm.getRawValue()).subscribe({
      next: (item) => {
        this.owners.update((x) => [item, ...x]);
        this.ownerForm.patchValue({ name: '', id_number: '', ownership_percentage: 0 });
        this.toast.success('Beneficial owner added.');
      },
      error: () => this.toast.error('Failed to add beneficial owner.'),
    });
  }

  deleteOwner(item: BeneficialOwnerRecord): void {
    this.api.deleteBeneficialOwner(item.id).subscribe({
      next: () => this.owners.update((x) => x.filter((d) => d.id !== item.id)),
      error: () => this.toast.error('Failed to delete owner.'),
    });
  }

  ownershipUsedPercent(total: number): number {
    if (!Number.isFinite(total) || total <= 0) {
      return 0;
    }

    return Math.min(100, Number(total.toFixed(2)));
  }

  ownershipStatusLabel(remaining: number): string {
    if (remaining <= 0) {
      return 'Fully Allocated';
    }

    if (remaining <= 10) {
      return 'Near Limit';
    }

    return 'Available';
  }

  private loadCompanies(): void {
    this.api.getCompanies().subscribe({
      next: (companies) => {
        this.companies.set(companies ?? []);
        const firstCompanyId = companies?.[0]?.id ?? 0;
        this.selectedCompanyId.set(firstCompanyId);
        this.syncFormsCompanyId(firstCompanyId);
        this.reloadAll(firstCompanyId);
      },
      error: () => {
        this.companies.set([]);
        this.selectedCompanyId.set(0);
        this.syncFormsCompanyId(0);
        this.reloadAll(0);
        this.toast.error('Failed to load companies for governance.');
      },
    });
  }

  private syncFormsCompanyId(companyId: number): void {
    this.directorForm.patchValue({ company_id: companyId });
    this.shareholderForm.patchValue({ company_id: companyId });
    this.ownerForm.patchValue({ company_id: companyId });
  }

  private reloadAll(companyId: number): void {
    if (companyId <= 0) {
      this.directors.set([]);
      this.shareholders.set([]);
      this.owners.set([]);
      return;
    }

    this.api.getCompanyDirectors(companyId).subscribe({ next: (list) => this.directors.set(list) });
    this.api.getShareholders(companyId).subscribe({ next: (list) => this.shareholders.set(list) });
    this.api.getBeneficialOwners(companyId).subscribe({ next: (list) => this.owners.set(list) });
  }
}
