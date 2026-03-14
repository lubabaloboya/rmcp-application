import { CommonModule } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { CompanyRecord, RmcpApiService } from '../core/rmcp-api.service';
import { ToastService } from '../core/toast.service';

interface CompanyCrudPayload {
  company_name: string;
  registration_number?: string;
  tax_number?: string;
  industry?: string;
  address?: string;
  phone?: string;
  email?: string;
}

@Component({
  selector: 'app-companies-page',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './companies-page.component.html',
  styleUrl: './companies-page.component.scss'
})
export class CompaniesPageComponent implements OnInit {
  private readonly api = inject(RmcpApiService);
  private readonly fb = inject(FormBuilder);
  private readonly toast = inject(ToastService);

  readonly loading = signal(false);
  readonly saving = signal(false);
  readonly deletingId = signal<number | null>(null);
  readonly editingId = signal<number | null>(null);
  readonly companies = signal<CompanyRecord[]>([]);

  readonly form = this.fb.nonNullable.group({
    company_name: ['', [Validators.required]],
    registration_number: [''],
    tax_number: [''],
    industry: [''],
    address: [''],
    phone: [''],
    email: [''],
  });

  ngOnInit(): void {
    this.loadCompanies();
  }

  startCreate(): void {
    this.editingId.set(null);
    this.form.reset({
      company_name: '',
      registration_number: '',
      tax_number: '',
      industry: '',
      address: '',
      phone: '',
      email: '',
    });
  }

  startEdit(company: CompanyRecord): void {
    this.editingId.set(company.id);
    this.form.patchValue({
      company_name: company.company_name ?? '',
      registration_number: company.registration_number ?? '',
      tax_number: company.tax_number ?? '',
      industry: company.industry ?? '',
      address: company.address ?? '',
      phone: company.phone ?? '',
      email: company.email ?? '',
    });
  }

  saveCompany(): void {
    if (this.form.invalid) {
      this.toast.error('Company name is required.');
      return;
    }

    this.saving.set(true);
    const payload = this.form.getRawValue() as CompanyCrudPayload;
    const editingId = this.editingId();

    const request$ = editingId
      ? this.api.updateCompany(editingId, payload)
      : this.api.createCompany(payload);

    request$.subscribe({
      next: () => {
        this.saving.set(false);
        this.toast.success(editingId ? 'Company updated.' : 'Company created.');
        this.editingId.set(null);
        this.startCreate();
        this.api.invalidateCompaniesCache();
        this.loadCompanies();
      },
      error: () => {
        this.saving.set(false);
        this.toast.error('Unable to save company.');
      },
    });
  }

  deleteCompany(companyId: number): void {
    this.deletingId.set(companyId);

    this.api.deleteCompany(companyId).subscribe({
      next: () => {
        this.deletingId.set(null);
        this.toast.success('Company deleted.');
        this.api.invalidateCompaniesCache();
        this.loadCompanies();
      },
      error: () => {
        this.deletingId.set(null);
        this.toast.error('Unable to delete company. It may have linked clients.');
      },
    });
  }

  private loadCompanies(): void {
    this.loading.set(true);

    this.api.getCompanies().subscribe({
      next: (response) => {
        this.companies.set(response ?? []);
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.toast.error('Failed to load companies.');
      },
    });
  }
}
