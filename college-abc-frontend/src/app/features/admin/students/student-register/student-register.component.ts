import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { StudentService } from '../../../../core/services/student.service';
import { ClassService } from '../../../../core/services/class.service';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../../environments/environment';

@Component({
  selector: 'app-student-register',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  template: `
    <div class="min-h-screen bg-gray-50 py-8">
      <div class="max-w-4xl mx-auto px-4">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
              <i class="pi pi-user-plus text-white text-xl"></i>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">{{ isEditing ? (studentForm.disabled ? "Dossier Élève" : "Modification de l'élève") : "Inscription d'un nouvel élève" }}</h1>
              <p class="text-gray-600">Remplissez tous les champs obligatoires pour inscrire l'élève</p>
            </div>
          </div>
        </div>

        <!-- Progress Steps -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div [class]="currentStep >= 1 ? 'w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold' : 'w-8 h-8 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center text-sm'">1</div>
              <span [class]="currentStep >= 1 ? 'text-blue-600 font-medium' : 'text-gray-500'">Informations personnelles</span>
            </div>
            <div class="flex-1 h-px bg-gray-200 mx-4"></div>
            <div class="flex items-center space-x-4">
              <div [class]="currentStep >= 2 ? 'w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold' : 'w-8 h-8 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center text-sm'">2</div>
              <span [class]="currentStep >= 2 ? 'text-blue-600 font-medium' : 'text-gray-500'">Informations scolaires</span>
            </div>
            <div class="flex-1 h-px bg-gray-200 mx-4"></div>
            <div class="flex items-center space-x-4">
              <div [class]="currentStep >= 3 ? 'w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-semibold' : 'w-8 h-8 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center text-sm'">3</div>
              <span [class]="currentStep >= 3 ? 'text-blue-600 font-medium' : 'text-gray-500'">Informations parents</span>
            </div>
          </div>
        </div>

        <!-- Form -->
        <form [formGroup]="studentForm" (ngSubmit)="onSubmit()">
          <!-- Step 1: Personal Information -->
          <div class="bg-white rounded-lg shadow-sm p-6 mb-6" *ngIf="currentStep === 1">
            <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
              <i class="pi pi-user mr-2 text-blue-500"></i>
              Informations personnelles de l'élève
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Prénom -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Prénom *</label>
                <input
                  type="text"
                  formControlName="firstName"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Ex: Jean"
                  [class.border-red-500]="studentForm.get('firstName')?.invalid && studentForm.get('firstName')?.touched">
                <div *ngIf="studentForm.get('firstName')?.invalid && studentForm.get('firstName')?.touched" class="text-red-500 text-sm mt-1">
                  Le prénom est requis
                </div>
              </div>

              <!-- Nom -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nom de famille *</label>
                <input
                  type="text"
                  formControlName="lastName"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Ex: KABORE"
                  [class.border-red-500]="studentForm.get('lastName')?.invalid && studentForm.get('lastName')?.touched">
                <div *ngIf="studentForm.get('lastName')?.invalid && studentForm.get('lastName')?.touched" class="text-red-500 text-sm mt-1">
                  Le nom est requis
                </div>
              </div>

              <!-- Date de naissance -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date de naissance *</label>
                <input
                  type="date"
                  formControlName="dateOfBirth"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  [class.border-red-500]="studentForm.get('dateOfBirth')?.invalid && studentForm.get('dateOfBirth')?.touched">
                <div *ngIf="studentForm.get('dateOfBirth')?.invalid && studentForm.get('dateOfBirth')?.touched" class="text-red-500 text-sm mt-1">
                  La date de naissance est requise
                </div>
              </div>

              <!-- Lieu de naissance -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Lieu de naissance *</label>
                <input
                  type="text"
                  formControlName="placeOfBirth"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Ex: Ouagadougou"
                  [class.border-red-500]="studentForm.get('placeOfBirth')?.invalid && studentForm.get('placeOfBirth')?.touched">
                <div *ngIf="studentForm.get('placeOfBirth')?.invalid && studentForm.get('placeOfBirth')?.touched" class="text-red-500 text-sm mt-1">
                  Le lieu de naissance est requis
                </div>
              </div>

              <!-- Nationalité -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nationalité</label>
                <input
                  type="text"
                  formControlName="nationality"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Ex: Burkinabè">
              </div>

              <!-- Genre -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Genre *</label>
                <select
                  formControlName="gender"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  [class.border-red-500]="studentForm.get('gender')?.invalid && studentForm.get('gender')?.touched">
                  <option value="">Sélectionner le genre</option>
                  <option value="M">Masculin</option>
                  <option value="F">Féminin</option>
                </select>
                <div *ngIf="studentForm.get('gender')?.invalid && studentForm.get('gender')?.touched" class="text-red-500 text-sm mt-1">
                  Le genre est requis
                </div>
              </div>

              <!-- Groupe sanguin -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Groupe sanguin</label>
                <select
                  formControlName="bloodGroup"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="">Sélectionner le groupe sanguin</option>
                  <option value="A+">A+</option>
                  <option value="A-">A-</option>
                  <option value="B+">B+</option>
                  <option value="B-">B-</option>
                  <option value="AB+">AB+</option>
                  <option value="AB-">AB-</option>
                  <option value="O+">O+</option>
                  <option value="O-">O-</option>
                </select>
              </div>

              <!-- Allergies -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Allergies / Problèmes de santé</label>
                <textarea
                  formControlName="allergies"
                  rows="2"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Ex: Asthme, Arachides... (Laisser vide si aucun)"></textarea>
              </div>

              <!-- Vaccinations -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Vaccinations à jour ? (Détails)</label>
                 <textarea
                  formControlName="vaccinations"
                  rows="2"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Ex: BCG, DTCoqPolio... (Laisser vide si inconnu)"></textarea>
              </div>
            </div>

            <!-- Adresse -->
            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Adresse complète *</label>
              <textarea
                formControlName="address"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Ex: Secteur 12, Rue 15.45, Maison n°123, Ouagadougou"
                [class.border-red-500]="studentForm.get('address')?.invalid && studentForm.get('address')?.touched"></textarea>
              <div *ngIf="studentForm.get('address')?.invalid && studentForm.get('address')?.touched" class="text-red-500 text-sm mt-1">
                L'adresse est requise
              </div>
            </div>
          </div>

          <!-- Step 2: School Information -->
          <div class="bg-white rounded-lg shadow-sm p-6 mb-6" *ngIf="currentStep === 2">
            <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
              <i class="pi pi-graduation-cap mr-2 text-blue-500"></i>
              Informations scolaires
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Cycle -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cycle d'enseignement *</label>
                <select
                  formControlName="cycle"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="mp">Maternelle / Primaire</option>
                  <option value="college">Collège</option>
                  <option value="lycee">Lycée</option>
                </select>
              </div>

              <!-- Classe -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Classe *</label>
                <select
                  formControlName="classId"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  [class.border-red-500]="studentForm.get('classId')?.invalid && studentForm.get('classId')?.touched">
                  <option value="">Sélectionner une classe</option>
                  <option *ngFor="let class of classes" [value]="class.id">{{ class.nom || class.name }}</option>
                </select>
                <div *ngIf="studentForm.get('classId')?.invalid && studentForm.get('classId')?.touched" class="text-red-500 text-sm mt-1">
                  La classe est requise
                </div>
              </div>

              <!-- Etablissement d'origine -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Établissement d'origine (Si nouvel élève)</label>
                <input
                  type="text"
                  formControlName="previousSchool"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Ex: École Privée Sainte-Anne">
              </div>

              <!-- Année scolaire -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Année scolaire *</label>
                <input
                  type="text"
                  formControlName="schoolYear"
                  readonly
                  class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 focus:outline-none"
                  placeholder="Ex: 2024-2025">
              </div>

              <!-- Régime -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Régime *</label>
                <select
                  formControlName="regime"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="externe">Externe</option>
                  <option value="demi_pensionnaire">Demi-pensionnaire</option>
                  <option value="interne">Interne</option>
                </select>
              </div>

              <!-- Mode de paiement -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mode de paiement *</label>
                <select
                  formControlName="modePaiement"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="comptant">Comptant (Totalité)</option>
                  <option value="tranches_3">En 3 tranches</option>
                </select>
              </div>

              <!-- Série (Lycée uniquement) -->
              <div *ngIf="studentForm.get('cycle')?.value === 'lycee'">
                <label class="block text-sm font-medium text-gray-700 mb-2">Série *</label>
                <select
                  formControlName="serie"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="">Sélectionner la série</option>
                  <option value="A">Série A (Littéraire)</option>
                  <option value="C">Série C (Mathématiques)</option>
                  <option value="D">Série D (Scientifique)</option>
                  <option value="E">Série E (Technique)</option>
                  <option value="F">Série F</option>
                  <option value="G">Série G</option>
                </select>
              </div>

              <!-- LV2 (Collège et Lycée) -->
              <div *ngIf="['college', 'lycee'].includes(studentForm.get('cycle')?.value)">
                <label class="block text-sm font-medium text-gray-700 mb-2">Seconde Langue (LV2)</label>
                <select
                  formControlName="lv2"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="">Sélectionner la LV2</option>
                  <option value="Allemand">Allemand</option>
                  <option value="Espagnol">Espagnol</option>
                  <option value="Aucune">Aucune (Si 6ème/5ème)</option>
                </select>
              </div>

              <!-- Documents & Photo -->
              <div class="md:col-span-2 mt-4 border-t pt-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Documents (Dossier numérique)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Photo d'identité</label>
                        <input type="file" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Extrait de naissance (PDF/Image)</label>
                        <input type="file" accept=".pdf,image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3: Parent Information -->
          <div class="bg-white rounded-lg shadow-sm p-6 mb-6" *ngIf="currentStep === 3">
            <h2 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
              <i class="pi pi-users mr-2 text-blue-500"></i>
              Informations des parents/tuteurs
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Nom du parent -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nom complet du parent/tuteur *</label>
                <input
                  type="text"
                  formControlName="parentName"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Ex: Paul KABORE"
                  [class.border-red-500]="studentForm.get('parentName')?.invalid && studentForm.get('parentName')?.touched">
                <div *ngIf="studentForm.get('parentName')?.invalid && studentForm.get('parentName')?.touched" class="text-red-500 text-sm mt-1">
                  Le nom du parent est requis
                </div>
              </div>

              <!-- Téléphone -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone *</label>
                <input
                  type="tel"
                  formControlName="parentPhone"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Ex: +226 70 00 00 00"
                  [class.border-red-500]="studentForm.get('parentPhone')?.invalid && studentForm.get('parentPhone')?.touched">
                <div *ngIf="studentForm.get('parentPhone')?.invalid && studentForm.get('parentPhone')?.touched" class="text-red-500 text-sm mt-1">
                  Le téléphone du parent est requis
                </div>
              </div>

              <!-- Email -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input
                  type="email"
                  formControlName="parentEmail"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Ex: paul.kabore@email.com">
              </div>

              <!-- Profession -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Profession</label>
                <input
                  type="text"
                  formControlName="parentProfession"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Ex: Enseignant">
              </div>
            </div>
          </div>

          <!-- Navigation Buttons -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex justify-between">
              <button
                type="button"
                (click)="previousStep()"
                *ngIf="currentStep > 1"
                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <i class="pi pi-arrow-left mr-2"></i>
                Précédent
              </button>
              <div></div>
              <button
                type="button"
                (click)="nextStep()"
                *ngIf="currentStep < 3"
                [disabled]="!isStepValid(currentStep)"
                class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-300 disabled:cursor-not-allowed">
                Suivant
                <i class="pi pi-arrow-right ml-2"></i>
              </button>
              <button
                type="submit"
                *ngIf="currentStep === 3 && !studentForm.disabled"
                [disabled]="studentForm.invalid || isLoading"
                class="px-6 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:bg-gray-300 disabled:cursor-not-allowed">
                <span *ngIf="!isLoading">
                  <i class="pi pi-check mr-2"></i>
                  {{ isEditing ? 'Mettre à jour' : "Inscrire l'élève" }}
                </span>
                <span *ngIf="isLoading">
                  <i class="pi pi-spin pi-spinner mr-2"></i>
                  {{ isEditing ? 'Mise à jour en cours...' : 'Inscription en cours...' }}
                </span>
              </button>
            </div>
          </div>
        </form>

        <!-- Success Message -->
        <div *ngIf="successMessage" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
          <div class="flex items-center">
            <i class="pi pi-check-circle text-green-500 text-xl mr-3"></i>
            <div>
              <h3 class="text-green-800 font-medium">{{ isEditing ? 'Opération réussie !' : 'Inscription réussie !' }}</h3>
              <p class="text-green-700">{{ successMessage }}</p>
            </div>
          </div>
        </div>

        <!-- Error Message -->
        <div *ngIf="errorMessage" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
          <div class="flex items-center">
            <i class="pi pi-exclamation-triangle text-red-500 text-xl mr-3"></i>
            <div>
              <h3 class="text-red-800 font-medium">Erreur d'inscription</h3>
              <p class="text-red-700">{{ errorMessage }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  `
})
export class StudentRegisterComponent implements OnInit {
  private fb = inject(FormBuilder);
  private studentService = inject(StudentService);
  private classService = inject(ClassService);
  private router = inject(Router);
  private route = inject(ActivatedRoute); // Added
  private http = inject(HttpClient);

  studentForm: FormGroup;
  currentStep = 1;
  classes: any[] = [];
  isLoading = false;
  successMessage = '';
  errorMessage = '';

  currentSchoolYear: any = null;
  
  // Edit Mode
  isEditing = false;
  studentId: string | null = null;
  editCycle: string = 'mp';

  constructor() {
    this.studentForm = this.fb.group({
      // Step 1: Personal Information
      firstName: ['', [Validators.required, Validators.minLength(2)]],
      lastName: ['', [Validators.required, Validators.minLength(2)]],
      dateOfBirth: ['', Validators.required],
      placeOfBirth: ['', Validators.required],
      nationality: ['Burkinabè'],
      gender: ['', Validators.required],
      bloodGroup: [''],
      allergies: [''],
      vaccinations: [''],
      address: ['', Validators.required], // Note: Not currently saved in backend for student directly
      
      // Step 2: School Information
      cycle: ['mp', Validators.required],
      classId: ['', Validators.required],
      schoolYear: ['2024-2025', Validators.required],
      previousSchool: [''],
      status: ['active', Validators.required],
      regime: ['externe', Validators.required],
      modePaiement: ['comptant', Validators.required],
      serie: [''],
      lv2: [''],
      
      // Step 3: Parent Information
      parentName: ['', Validators.required],
      parentPhone: ['', Validators.required],
      parentEmail: ['', Validators.email],
      parentProfession: ['']
    });
  }

  ngOnInit() {
    this.loadCurrentSchoolYear();
    this.loadClasses();
    
    // Listen for cycle changes to reload classes
    this.studentForm.get('cycle')?.valueChanges.subscribe(() => {
      this.loadClasses();
    });

    // Check for Edit/Details Mode
    this.route.paramMap.subscribe(params => {
      const id = params.get('id');
      const isDetails = this.router.url.includes('/details'); // Simple check

      if (id) {
        this.isEditing = true;
        this.studentId = id;
        
        if (isDetails) {
            this.studentForm.disable();
        }

        // get cycle from query params
        this.route.queryParams.subscribe(queryParams => {
            this.editCycle = queryParams['cycle'] || 'mp';
            this.loadStudentData(id, this.editCycle);
        });
      }
    });
  }

  loadStudentData(id: string, cycle: string) {
    this.isLoading = true;
    this.studentService.getStudent(id, cycle as any).subscribe({
      next: (student) => {
        this.isLoading = false;
        
        // Patch Personal Info
        this.studentForm.patchValue({
          firstName: student.prenoms,
          lastName: student.nom,
          dateOfBirth: student.date_naissance ? student.date_naissance.split('T')[0] : '',
          placeOfBirth: student.lieu_naissance,
          gender: student.sexe,
          bloodGroup: student.groupe_sanguin,
          address: 'Adresse non disponible' // Placeholder as it's not in Student model
        });

        // Patch School Info (Current Enrollment)
        const enrollment = student.enrollments && student.enrollments.length > 0 ? student.enrollments[0] : null;
        if (enrollment) {
           this.studentForm.patchValue({
             cycle: cycle, // Using the cycle we know
             classId: enrollment.class ? enrollment.class.id : '',
             regime: enrollment.regime,
             // schoolYear is typically readonly/current
           });
           
           // Disable School Info fields in Edit Mode as they require proper transfer process
           this.studentForm.get('cycle')?.disable();
           this.studentForm.get('classId')?.disable();
           this.studentForm.get('schoolYear')?.disable();
           this.studentForm.get('regime')?.disable();
           this.studentForm.get('modePaiement')?.disable();
           
           // Manually convert cycle for TS check if needed, but form is sloppy typed
        }

        // Patch Parent Info (Guardian)
        const guardian = student.guardians && student.guardians.length > 0 ? student.guardians.find((g: any) => g.type === 'pere') || student.guardians[0] : null;
        if (guardian) {
           this.studentForm.patchValue({
             parentName: guardian.nom_complet,
             parentPhone: guardian.telephone_1,
             parentEmail: guardian.email,
             parentProfession: guardian.profession
           });
           
           // If we have address from guardian
           if (guardian.adresse_physique) {
               this.studentForm.patchValue({ address: guardian.adresse_physique });
           }
        }
      },
      error: (err) => {
        this.isLoading = false;
        this.errorMessage = "Impossible de charger les données de l'élève.";
        console.error(err);
      }
    });
  }

  loadCurrentSchoolYear() {
    this.http.get(`${environment.apiUrl}/core/school-years/current`).subscribe((year: any) => {
      this.currentSchoolYear = year;
      if (year) {
        this.studentForm.patchValue({ schoolYear: year.name });
      }
    });
  }

  loadClasses() {
    const cycle = this.studentForm.get('cycle')?.value || 'mp';
    this.classService.getClasses(cycle).subscribe({
      next: (response: any) => {
        this.classes = Array.isArray(response) ? response : (response.data || []);
      },
      error: (error) => {
        console.error('Erreur lors du chargement des classes:', error);
      }
    });
  }

  nextStep() {
    if (this.isStepValid(this.currentStep)) {
      this.currentStep++;
    }
  }

  previousStep() {
    this.currentStep--;
  }

  isStepValid(step: number): boolean {
    const step1Fields = ['firstName', 'lastName', 'dateOfBirth', 'placeOfBirth', 'gender', 'address'];
    const step2Fields = ['cycle', 'classId', 'schoolYear', 'status', 'regime', 'modePaiement'];
    const step3Fields = ['parentName', 'parentPhone'];

    let fieldsToCheck: string[] = [];
    
    switch (step) {
      case 1:
        fieldsToCheck = step1Fields;
        break;
      case 2:
        fieldsToCheck = step2Fields;
        break;
      case 3:
        fieldsToCheck = step3Fields;
        break;
    }

    return fieldsToCheck.every(field => {
      const control = this.studentForm.get(field);
      // In edit mode, we might be lenient or strict. Strict is fine as we patched values.
      return control && (control.valid || control.disabled); 
    });
  }

  onSubmit() {
    if (this.studentForm.valid) {
      this.isLoading = true;
      this.errorMessage = '';
      
      const cycle = this.studentForm.get('cycle')?.value;

      if (this.isEditing && this.studentId) {
          // Update Logic
          const updateData = {
              nom: this.studentForm.value.lastName,
              prenoms: this.studentForm.value.firstName,
              date_naissance: this.studentForm.value.dateOfBirth,
              lieu_naissance: this.studentForm.value.placeOfBirth,
              sexe: this.studentForm.value.gender,
              groupe_sanguin: this.studentForm.value.bloodGroup,
              // Backend update might not handle parents/enrollments yet
          };
          
          this.studentService.updateStudent(this.studentId, updateData, cycle).subscribe({
              next: () => {
                  this.isLoading = false;
                  this.successMessage = "Élève mis à jour avec succès.";
                  setTimeout(() => {
                      this.router.navigate(['/admin/students']);
                  }, 2000);
              },
              error: (err) => {
                  this.isLoading = false;
                  this.errorMessage = err.error?.message || "Erreur lors de la mise à jour.";
              }
          });

      } else {
          // Create Logic
          const registrationData = {
            // INFO ELEVE
            nom: this.studentForm.value.lastName,
            prenoms: this.studentForm.value.firstName,
            date_naissance: this.studentForm.value.dateOfBirth,
            lieu_naissance: this.studentForm.value.placeOfBirth,
            sexe: this.studentForm.value.gender,
            nationalite: this.studentForm.value.nationality || 'Burkinabè',
            groupe_sanguin: this.studentForm.value.bloodGroup,
            allergies: this.studentForm.value.allergies,
            vaccinations: this.studentForm.value.vaccinations,
            etablissement_origine: this.studentForm.value.previousSchool,
            serie: this.studentForm.value.serie,
            lv2: this.studentForm.value.lv2,
            options: { lv2: this.studentForm.value.lv2 },
            statut_inscription: 'nouveau',
            
            // AFFECTATION
            class_id: this.studentForm.value.classId,
            school_year_id: this.currentSchoolYear?.id || '9df66f7f-9f7a-4b9e-bd8d-7a421b5853f5',
            regime: this.studentForm.value.regime,
            
            // PARENTS
            pere: {
              nom_complet: this.studentForm.value.parentName,
              telephone_1: this.studentForm.value.parentPhone,
              email: this.studentForm.value.parentEmail,
              profession: this.studentForm.value.parentProfession
            },
            
            // FINANCES
            mode_paiement: this.studentForm.value.modePaiement
          };

          this.studentService.register(cycle, registrationData).subscribe({
            next: (response) => {
              this.isLoading = false;
              this.successMessage = `L'élève ${this.studentForm.value.firstName} ${this.studentForm.value.lastName} a été inscrit avec succès. Le formulaire a été réinitialisé pour une nouvelle inscription.`;
              
              // Reset form for next student
              this.studentForm.reset({
                cycle: this.studentForm.get('cycle')?.value || 'mp',
                schoolYear: this.currentSchoolYear?.name || '2024-2025',
                classId: this.studentForm.get('classId')?.value, // Keep class/cycle/year for faster entry
                regime: 'externe',
                modePaiement: 'comptant',
                status: 'active'
              });
              this.currentStep = 1;
              window.scrollTo(0, 0);

              // Hide success message after 5 seconds
              setTimeout(() => {
                this.successMessage = '';
              }, 5000);
            },
            error: (error) => {
              this.isLoading = false;
              this.errorMessage = error.error?.message || 'Erreur lors de l\'inscription. Veuillez vérifier les places disponibles.';
            }
          });
      }
    }
  }
}