import { CommonModule } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { CommunicationRecord, RmcpApiService } from '../core/rmcp-api.service';
import { ToastService } from '../core/toast.service';

@Component({ selector: 'app-communications-page', standalone: true, imports: [CommonModule, ReactiveFormsModule], templateUrl: './communications-page.component.html', styleUrl: './communications-page.component.scss' })
export class CommunicationsPageComponent implements OnInit {
  private readonly api = inject(RmcpApiService);
  private readonly fb = inject(FormBuilder);
  private readonly toast = inject(ToastService);

  readonly communications = signal<CommunicationRecord[]>([]);

  readonly form = this.fb.nonNullable.group({
    sender: ['', Validators.required],
    receiver: ['', Validators.required],
    email_subject: [''],
    email_body: [''],
  });

  ngOnInit(): void { this.reload(); }

  createCommunication(): void {
    if (this.form.invalid) { return; }
    this.api.createCommunication(this.form.getRawValue()).subscribe({
      next: (item) => { this.communications.update((list) => [item, ...list]); this.form.patchValue({ email_subject: '', email_body: '' }); this.toast.success('Communication saved.'); },
      error: () => this.toast.error('Failed to save communication.'),
    });
  }

  private reload(): void {
    this.api.getCommunications().subscribe({ next: (list) => this.communications.set(list), error: () => this.toast.error('Failed to load communications.') });
  }
}
