import { CommonModule } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { BeneficialOwnerRecord, DirectorRecord, RmcpApiService, ShareholderRecord } from '../core/rmcp-api.service';
import { ToastService } from '../core/toast.service';

@Component({
  selector: 'app-governance-page',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
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
    this.reloadAll();
  }

  addDirector(): void {
    if (this.directorForm.invalid) { return; }
    this.api.createCompanyDirector(this.directorForm.getRawValue()).subscribe({
      next: (item) => { this.directors.update((x) => [item, ...x]); this.toast.success('Director added.'); },
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
    if (this.shareholderForm.invalid) { return; }
    this.api.createShareholder(this.shareholderForm.getRawValue()).subscribe({
      next: (item) => { this.shareholders.update((x) => [item, ...x]); this.toast.success('Shareholder added.'); },
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
    if (this.ownerForm.invalid) { return; }
    this.api.createBeneficialOwner(this.ownerForm.getRawValue()).subscribe({
      next: (item) => { this.owners.update((x) => [item, ...x]); this.toast.success('Beneficial owner added.'); },
      error: () => this.toast.error('Failed to add beneficial owner.'),
    });
  }

  deleteOwner(item: BeneficialOwnerRecord): void {
    this.api.deleteBeneficialOwner(item.id).subscribe({
      next: () => this.owners.update((x) => x.filter((d) => d.id !== item.id)),
      error: () => this.toast.error('Failed to delete owner.'),
    });
  }

  private reloadAll(): void {
    this.api.getCompanyDirectors().subscribe({ next: (list) => this.directors.set(list) });
    this.api.getShareholders().subscribe({ next: (list) => this.shareholders.set(list) });
    this.api.getBeneficialOwners().subscribe({ next: (list) => this.owners.set(list) });
  }
}
