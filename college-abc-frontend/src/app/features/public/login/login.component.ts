import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, Router } from '@angular/router';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

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
          <h2 class="text-3xl font-serif font-bold text-white relative z-10">Espace Numérique</h2>
          <p class="text-blue-100 text-sm mt-2 relative z-10">Collège Privé Wend-Manegda</p>
        </div>

        <!-- Mode Selection Tabs -->
        <div class="flex border-b border-gray-200">
          <button (click)="setMode('staff')" [class.text-primary]="mode() === 'staff'" [class.border-primary]="mode() === 'staff'" class="flex-1 py-4 text-sm font-bold text-gray-500 border-b-2 border-transparent hover:text-primary transition-colors focus:outline-none">
            Personnel
          </button>
          <button (click)="setMode('student')" [class.text-primary]="mode() === 'student'" [class.border-primary]="mode() === 'student'" class="flex-1 py-4 text-sm font-bold text-gray-500 border-b-2 border-transparent hover:text-primary transition-colors focus:outline-none">
            Élève
          </button>
          <button (click)="setMode('parent')" [class.text-primary]="mode() === 'parent'" [class.border-primary]="mode() === 'parent'" class="flex-1 py-4 text-sm font-bold text-gray-500 border-b-2 border-transparent hover:text-primary transition-colors focus:outline-none">
            Parent
          </button>
        </div>

        <!-- Form Container -->
        <div class="p-8">
          
          <!-- PARENT FLOW STEP 1: Child Matricule -->
          <div *ngIf="mode() === 'parent' && parentStep() === 1" class="animate-fade-in">
            <h3 class="text-lg font-bold text-gray-800 mb-4 text-center">Identification de l'enfant</h3>
            <p class="text-sm text-gray-600 mb-6 text-center">Pour accéder à l'espace parent, veuillez d'abord renseigner le matricule de votre enfant.</p>
            
            <form [formGroup]="parentCheckForm" (ngSubmit)="checkStudent()" class="space-y-6">
               <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Matricule de l'élève</label>
                  <div class="relative">
                    <i class="pi pi-id-card absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input formControlName="childMatricule" type="text" class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-gray-50 uppercase" placeholder="Ex: 25-A-1234" />
                  </div>
               </div>
               
               <div *ngIf="errorMessage()" class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center">
                  <i class="pi pi-exclamation-circle mr-2"></i>
                  <span>{{ errorMessage() }}</span>
               </div>

               <button type="submit" [disabled]="parentCheckForm.invalid || isLoading()" class="block w-full py-4 bg-secondary text-white font-bold rounded-lg shadow-lg hover:bg-primary transition-all">
                  <span *ngIf="!isLoading()">Vérifier & Continuer <i class="pi pi-arrow-right ml-2"></i></span>
                  <span *ngIf="isLoading()"><i class="pi pi-spin pi-spinner"></i> Vérification...</span>
               </button>
            </form>
          </div>

          <!-- STANDARD LOGIN (Staff, Student, Parent Step 2) -->
          <div *ngIf="mode() !== 'parent' || parentStep() === 2" class="animate-fade-in">
            
            <!-- Parent Success Banner -->
            <div *ngIf="mode() === 'parent' && verifiedStudent()" class="bg-green-50 border border-green-200 rounded-lg p-3 mb-6 flex items-center gap-3 animate-fade-in">
              <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-bold shrink-0">
                <i class="pi pi-check"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs text-green-800 font-bold uppercase tracking-wide">Compte Élève identifié</p>
                <p class="text-sm text-green-700 font-medium truncate">{{ verifiedStudent() }}</p>
              </div>
              <button (click)="resetParentStep()" class="ml-2 text-gray-400 hover:text-red-500 transition-colors" title="Changer d'élève">
                <i class="pi pi-times"></i>
              </button>
            </div>

            <h3 class="text-lg font-bold text-gray-800 mb-6 text-center">
              {{ getTitle() }}
            </h3>

            <form [formGroup]="loginForm" (ngSubmit)="onSubmit()" class="space-y-6">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">{{ getIdentifierLabel() }}</label>
                <div class="relative">
                  <i class="pi pi-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                  <input formControlName="identifier" type="text" class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary focus:border-transparent transition-all bg-gray-50" [placeholder]="getIdentifierPlaceholder()" />
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

              <!-- General Error Message -->
              <div *ngIf="errorMessage()" class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center">
                <i class="pi pi-exclamation-circle mr-2"></i>
                <span>{{ errorMessage() }}</span>
              </div>

              <button type="submit" [disabled]="loginForm.invalid || isLoading()" class="block w-full py-4 bg-secondary text-white font-bold rounded-lg shadow-lg hover:bg-primary transition-all transform hover:scale-105 text-center disabled:opacity-50 disabled:cursor-not-allowed">
                <span *ngIf="!isLoading()">Se Connecter</span>
                <span *ngIf="isLoading()"><i class="pi pi-spin pi-spinner"></i> Connexion...</span>
              </button>
            </form>
          </div>

          <div class="mt-8 text-center border-t pt-6" *ngIf="mode() === 'parent' && parentStep() === 1">
            <p class="text-gray-600 text-sm">Votre enfant n'est pas encore inscrit ?</p>
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
  private http = inject(HttpClient); 
  private apiUrl = environment.apiUrl || 'http://localhost:8000/api';

  isLoading = signal(false);
  errorMessage = signal<string | null>(null);
  
  // UI State
  mode = signal<'staff' | 'student' | 'parent'>('student'); 
  parentStep = signal<number>(1);
  verifiedStudent = signal<string | null>(null);

  loginForm = this.fb.group({
    identifier: ['', Validators.required],
    password: ['', Validators.required]
  });

  parentCheckForm = this.fb.group({
    childMatricule: ['', Validators.required]
  });

  setMode(m: 'staff' | 'student' | 'parent') {
    this.mode.set(m);
    this.errorMessage.set(null);
    this.loginForm.reset();
    
    // Reset specific states
    if (m === 'parent') {
      this.resetParentStep();
    }
  }

  resetParentStep() {
    this.parentStep.set(1);
    this.verifiedStudent.set(null);
    this.parentCheckForm.reset();
    this.errorMessage.set(null);
  }

  getTitle(): string {
    if (this.mode() === 'student') return 'Espace Élève';
    if (this.mode() === 'parent') return 'Espace Parent';
    return 'Espace Personnel';
  }

  getIdentifierLabel(): string {
    if (this.mode() === 'student') return 'Matricule';
    if (this.mode() === 'parent') return 'Votre Email ou Téléphone';
    return 'Email Professionnel';
  }

  getIdentifierPlaceholder(): string {
    if (this.mode() === 'student') return 'Ex: 25-A-1234';
    if (this.mode() === 'parent') return 'parent@exemple.com';
    return 'nom@manegda.bf';
  }

  checkStudent() {
    if (this.parentCheckForm.invalid) return;

    this.isLoading.set(true);
    this.errorMessage.set(null);

    const matricule = this.parentCheckForm.value.childMatricule;
    
    this.http.post<any>(`${this.apiUrl}/auth/verify-student`, { matricule }).subscribe({
      next: (res) => {
        this.isLoading.set(false);
        if (res.valid) {
          this.verifiedStudent.set(res.student.name);
          this.parentStep.set(2);
        } else {
          this.errorMessage.set('Matricule introuvable. Veuillez vérifier.');
        }
      },
      error: (err) => {
        this.isLoading.set(false);
        if (err.status === 404) {
           this.errorMessage.set('Aucun élève trouvé avec ce matricule.');
        } else {
           this.errorMessage.set('Erreur de vérification. Veuillez réessayer.');
        }
      }
    });
  }

  onSubmit() {
    this.errorMessage.set(null);
    
    if (this.loginForm.valid) {
      this.isLoading.set(true);
      const credentials = {
        identifier: this.loginForm.value.identifier || '',
        password: this.loginForm.value.password || ''
      };
      
      this.authService.login(credentials).subscribe({
        next: (response) => {
          this.isLoading.set(false);

          if (response?.must_change_password) {
            this.router.navigate(['/change-password']);
          } else {
            this.authService.redirectToDashboard();
          }
        },
        error: (error) => {
          console.error('Login failed', error);
          this.isLoading.set(false);
          
          if (error.status === 401 || error.status === 422) {
            const msg = error.error?.errors?.identifier?.[0] || error.error?.message || 'Identifiants incorrects.';
            this.errorMessage.set(msg);
          } else {
            this.errorMessage.set('Une erreur est survenue.');
          }
        }
      });
    } else {
      this.loginForm.markAllAsTouched();
    }
  }
}
