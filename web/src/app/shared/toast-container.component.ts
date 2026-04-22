import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { ToastService } from '../core/toast.service';

@Component({
  selector: 'app-toast-container',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './toast-container.component.html', styleUrls: ['./toast-container.component.scss']
})
export class ToastContainerComponent {
  readonly toast = inject(ToastService);
}


