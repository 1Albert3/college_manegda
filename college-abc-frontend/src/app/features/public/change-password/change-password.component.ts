import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-change-password',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  template: `
    <div class="min-h-screen flex items-center justify-center bg-neutral-light py-20 px-6">
      <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-secondary p-8 text-center">
          <i class="pi pi-lock text-4xl text-white mb-4"></i>
          <h2 class="text-2xl font-bold text-white">Changer votre mot de passe</h2>
          <p class="text-blue-100 text-sm mt-2">Pour votre sécurité, vous devez changer votre mot de passe par défaut.</p>
        </div>

        <div class="p-8">
          <form [formGroup]="passwordForm" (ngSubmit)="onSubmit()" class="space-y-6">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Nouveau mot de passe</label>
              <input formControlName="newPassword" type="password" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-secondary" placeholder="••••••••" />
              <div *ngIf="passwordForm.get('newPassword')?.touched && passwordForm.get('newPassword')?.errors?.['minlength']" class="text-red-500 text-xs mt-1">
                Le mot de passe doit contenir au moins 8 caractères.
              </div>
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Confirmer le mot de passe</label>
              <input formControlName="confirmPassword" type="password" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-secondary" placeholder="••••••••" />
              <div *ngIf="passwordForm.errors?.['mismatch'] && passwordForm.get('confirmPassword')?.touched" class="text-red-500 text-xs mt-1">
                Les mots de passe ne correspondent pas.
              </div>
            </div>

            <button type="submit" [disabled]="passwordForm.invalid || isLoading()" class="block w-full py-4 bg-primary text-white font-bold rounded-lg shadow-lg hover:bg-secondary transition-all disabled:opacity-50">
              <span *ngIf="!isLoading()">Changer le mot de passe</span>
              <span *ngIf="isLoading()">Traitement...</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  `
})
export class ChangePasswordComponent {
  private fb = inject(FormBuilder);
  private authService = inject(AuthService);
  private router = inject(Router);

  isLoading = signal(false);

  passwordForm = this.fb.group({
    newPassword: ['', [Validators.required, Validators.minLength(8)]],
    confirmPassword: ['', Validators.required]
  }, { validators: this.passwordMatchValidator });

  passwordMatchValidator(g: any) {
    return g.get('newPassword').value === g.get('confirmPassword').value
      ? null : { 'mismatch': true };
  }

  onSubmit() {
    if (this.passwordForm.valid) {
      this.isLoading.set(true);
      const newPassword = this.passwordForm.get('newPassword')?.value || '';
      const confirmPassword = this.passwordForm.get('confirmPassword')?.value || '';
      
      // Pour un changement de mot de passe initial, on passe une chaîne vide comme ancien mot de passe
      this.authService.changePassword('', newPassword, confirmPassword).subscribe({
        next: () => {
          alert('Mot de passe changé avec succès !');
          this.authService.redirectToDashboard();
        },
        error: (err) => {
          console.error(err);
          this.isLoading.set(false);
          alert('Erreur lors du changement de mot de passe.');
        }
      });
    }
  }

}
