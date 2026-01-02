import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-admin-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  template: `
    <div class="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 flex items-center justify-center p-4">
      <div class="max-w-md w-full">
        <div class="text-center mb-8">
          <div class="mx-auto h-20 w-20 bg-white rounded-full flex items-center justify-center mb-4 shadow-lg">
            <svg class="h-12 w-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
          </div>
          <h2 class="text-3xl font-bold text-white mb-2">Collège ABC</h2>
          <p class="text-blue-200">Administration</p>
        </div>

        <div class="bg-white rounded-2xl shadow-2xl p-8">
          <form [formGroup]="loginForm" (ngSubmit)="onSubmit()" class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
              <input
                type="email"
                formControlName="email"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="admin@college-abc.bf"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe</label>
              <input
                type="password"
                formControlName="password"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="admin123"
              >
            </div>

            <div *ngIf="errorMessage" class="bg-red-50 border border-red-200 rounded-lg p-4">
              <p class="text-sm text-red-700">{{ errorMessage }}</p>
            </div>

            <button
              type="submit"
              [disabled]="isLoading"
              class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50"
            >
              <span *ngIf="!isLoading">Se connecter</span>
              <span *ngIf="isLoading">Connexion...</span>
            </button>
          </form>

          <div class="mt-6 p-4 bg-blue-50 rounded-lg">
            <p class="text-sm text-blue-800 font-medium mb-2">Identifiants :</p>
            <p class="text-xs text-blue-600">admin@college-abc.bf / admin123</p>
          </div>
        </div>
      </div>
    </div>
  `
})
export class AdminLoginComponent implements OnInit {
  private fb = inject(FormBuilder);
  private authService = inject(AuthService);
  private router = inject(Router);

  loginForm: FormGroup;
  isLoading = false;
  errorMessage = '';

  constructor() {
    this.loginForm = this.fb.group({
      email: ['admin@college-abc.bf', [Validators.required, Validators.email]],
      password: ['admin123', [Validators.required]]
    });
  }

  ngOnInit() {
    // Pas de vérification d'authentification pour simplifier
  }

  onSubmit() {
    if (this.loginForm.valid) {
      this.isLoading = true;
      this.errorMessage = '';

      const { email, password } = this.loginForm.value;
      const credentials = { identifier: email, password: password };

      this.authService.login(credentials).subscribe({
        next: (response) => {
          this.isLoading = false;
          if (response.success) {
            this.authService.redirectToDashboard();
          } else {
             // Fallback if success property is missing but no error thrown (unlikely with valid response)
             this.authService.redirectToDashboard();
          }
        },
        error: (error) => {
          this.isLoading = false;
          console.error(error);
          this.errorMessage = error.error?.message || 'Erreur de connexion';
        }
      });
    }
  }
}