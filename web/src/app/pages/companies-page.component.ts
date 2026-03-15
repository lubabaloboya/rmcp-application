import { CommonModule } from '@angular/common';
import { Component, computed, inject, OnInit, signal } from '@angular/core';
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
  readonly searchTerm = signal('');
  readonly perPage = signal(15);
  readonly currentPage = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);
  readonly sortBy = signal<'company_name' | 'industry' | 'email' | 'created_at'>('company_name');
  readonly sortDir = signal<'asc' | 'desc'>('asc');

  readonly totalCount = computed(() => this.total());
  readonly withEmailCount = computed(() => this.companies().filter((item) => !!(item.email ?? '').trim()).length);
  readonly withIndustryCount = computed(() => this.companies().filter((item) => !!(item.industry ?? '').trim()).length);

  readonly form = this.fb.nonNullable.group({
    company_name: ['', [Validators.required]],
    registration_number: [''],
    tax_number: [''],
    industry: [''],
    address: [''],
    phone: [''],
    email: ['', [Validators.email]],
  });

  ngOnInit(): void {
    this.loadCompanies();
  }

  setSearch(value: string): void {
    this.searchTerm.set(value);
  }

  applySearch(): void {
    this.currentPage.set(1);
    this.loadCompanies();
  }

  clearSearch(): void {
    this.searchTerm.set('');
    this.currentPage.set(1);
    this.loadCompanies();
  }

  setPerPage(value: number): void {
    if (!Number.isFinite(value) || value <= 0) {
      return;
    }

    this.perPage.set(value);
    this.currentPage.set(1);
    this.loadCompanies();
  }

  setSortBy(value: 'company_name' | 'industry' | 'email' | 'created_at'): void {
    this.sortBy.set(value);
  }

  setSortDir(value: 'asc' | 'desc'): void {
    this.sortDir.set(value);
  }

  applySort(): void {
    this.currentPage.set(1);
    this.loadCompanies();
  }

  goToPreviousPage(): void {
    if (this.currentPage() <= 1) {
      return;
    }

    this.currentPage.update((page) => page - 1);
    this.loadCompanies();
  }

  goToNextPage(): void {
    if (this.currentPage() >= this.lastPage()) {
      return;
    }

    this.currentPage.update((page) => page + 1);
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
      this.form.markAllAsTouched();
      this.toast.error('Please fix the highlighted fields.');
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
    const company = this.companies().find((item) => item.id === companyId);
    if (!confirm(`Delete ${company?.company_name ?? 'this company'}?`)) {
      return;
    }

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

    this.api.getCompaniesPage(
      this.currentPage(),
      this.perPage(),
      this.searchTerm().trim() || undefined,
      this.sortBy(),
      this.sortDir()
    ).subscribe({
      next: (response) => {
        this.companies.set(response.data ?? []);
        this.currentPage.set(response.current_page ?? this.currentPage());
        this.lastPage.set(response.last_page ?? 1);
        this.total.set(response.total ?? this.companies().length);
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        this.toast.error('Failed to load companies.');
      },
    });
  }

  isFieldInvalid(field: 'company_name' | 'email'): boolean {
    const control = this.form.controls[field];
    return control.invalid && (control.touched || control.dirty);
  }

  fieldError(field: 'company_name' | 'email'): string {
    const control = this.form.controls[field];

    if (control.hasError('required')) {
      return 'Company name is required.';
    }

    if (control.hasError('email')) {
      return 'Enter a valid email address.';
    }

    return '';
  }
}
