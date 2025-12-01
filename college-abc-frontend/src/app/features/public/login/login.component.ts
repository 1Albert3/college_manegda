import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, Router } from '@angular/router';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';

@Component({
    selector: 'app-login',
    standalone: true,
    imports: [CommonModule, RouterLink, ReactiveFormsModule],
    template: `
    <div class="min-h-screen flex items-center justify-center bg-neutral-light py-20 px-6">
      <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl overflow-hidden" data-aos="zoom-in">
        <!-- Header -->
        <div class="bg-primary p-8 text-center relative overflow-hidden">
          <div class="absolute inset-0 bg-secondary/20 mix-blend-overlay"></div>
          <i class="pi pi-shield text-5xl text-secondary mb-4 relative z-10"></i>
          <h2 class="text-3xl font-serif font-bold text-white relative z-10">Espace Parents</h2>
          <p class="text-blue-100 text-sm mt-2 relative z-10">Connectez-vous pour suivre la scolarité de votre enfant.</p>
        </div>

        <!-- Form -->
        <div class="p-8">
          <form [formGroup]="loginForm" (ngSubmit)="onSubmit()" class="space-y-6">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Email ou Téléphone</label>
              <div class="relative">
                <i class="pi pi-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input formControlName="identifier" type="text" class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-gray-50" placeholder="Identifiant" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Mot de passe</label>
              <div class="relative">
                <i class="pi pi-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input formControlName="password" type="password" class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-gray-50" placeholder="••••••••" />
              </div>
            </div>
            
            <div class="flex items-center justify-between text-sm">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" class="rounded text-primary focus:ring-primary" />
                <span class="text-gray-600">Se souvenir de moi</span>
              </label>
              <a href="#" class="text-primary hover:text-secondary font-bold">Mot de passe oublié ?</a>
            </div>

            <button type="submit" [disabled]="loginForm.invalid || isLoading()" class="block w-full py-4 bg-secondary text-white font-bold rounded-lg shadow-lg hover:bg-primary transition-all transform hover:scale-105 text-center disabled:opacity-50 disabled:cursor-not-allowed">
              <span *ngIf="!isLoading()">Se Connecter</span>
              <span *ngIf="isLoading()"><i class="pi pi-spin pi-spinner"></i> Connexion...</span>
            </button>
          </form>

          <div class="mt-8 text-center border-t pt-6">
            <p class="text-gray-600 text-sm">Pas encore de compte ?</p>
            <a routerLink="/inscription" class="text-primary font-bold hover:text-secondary mt-2 inline-block">
              Faire une demande d'inscription
            </a>
          </div>
        </div>
      </div>
    </div>
  `
})
export class LoginComponent {
  private fb = inject(FormBuilder);
  private authService = inject(AuthService);
  private router = inject(Router);

  isLoading = signal(false);

  loginForm = this.fb.group({
    identifier: ['', Validators.required],
    password: ['', Validators.required]
  });

  onSubmit() {
    console.log('Submitting form', this.loginForm.value);
    if (this.loginForm.valid) {
      this.isLoading.set(true);
      this.authService.login(this.loginForm.value).subscribe({
        next: (response) => {
          console.log('Login successful', response);
          this.isLoading.set(false);
          this.router.navigate(['/parents/dashboard']);
        },
        error: (error) => {
          console.error('Login failed', error);
          this.isLoading.set(false);
          // TODO: Show error message to user
        }
      });
    } else {
      this.loginForm.markAllAsTouched();
    }
  }
}
