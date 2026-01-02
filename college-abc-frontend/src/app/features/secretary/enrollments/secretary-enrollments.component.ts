import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators, FormGroup } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';
import { EnrollmentService } from '../../../core/services/enrollment.service';

interface Enrollment {
  id: number;
  firstName: string;
  lastName: string;
  birthDate: string;
  gender: string;
  parentName: string;
  parentPhone: string;
  requestedClass: string;
  status: 'pending' | 'processing' | 'approved' | 'rejected' | 'active';
  submittedAt: string;
  documents: string[];
}

interface ClassRoom {
  id: number;
  name: string;
  level?: string;
}

@Component({
  selector: 'app-secretary-enrollments',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  template: `
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Gestion des Inscriptions</h1>
          <p class="text-gray-500">Traitez les demandes d'inscription et de réinscription</p>
        </div>
        <button (click)="openNewModal()"
                class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 flex items-center gap-2">
          <i class="pi pi-plus"></i> Nouvelle Inscription
        </button>
      </div>

      <!-- Loading State -->
      <div *ngIf="isLoading()" class="text-center py-12">
        <i class="pi pi-spin pi-spinner text-4xl text-teal-600"></i>
        <p class="mt-4 text-gray-500">Chargement des inscriptions...</p>
      </div>

      <!-- Error State -->
      <div *ngIf="error()" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
        <i class="pi pi-exclamation-triangle text-4xl text-red-500"></i>
        <p class="mt-4 text-red-700">{{ error() }}</p>
        <button (click)="loadEnrollments()" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Réessayer
        </button>
      </div>

      <ng-container *ngIf="!isLoading() && !error()">
        <!-- Stats Bar -->
        <div class="grid grid-cols-4 gap-4">
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-center">
            <div class="text-2xl font-bold text-yellow-700">{{ getCountByStatus('pending') }}</div>
            <div class="text-xs text-yellow-600">En attente</div>
          </div>
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-center">
            <div class="text-2xl font-bold text-blue-700">{{ getCountByStatus('processing') }}</div>
            <div class="text-xs text-blue-600">En cours</div>
          </div>
          <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
            <div class="text-2xl font-bold text-green-700">{{ getCountByStatus('approved') + getCountByStatus('active') }}</div>
            <div class="text-xs text-green-600">Validées</div>
          </div>
          <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-center">
            <div class="text-2xl font-bold text-red-700">{{ getCountByStatus('rejected') }}</div>
            <div class="text-xs text-red-600">Rejetées</div>
          </div>
        </div>

        <!-- Enrollments Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr class="text-left text-sm text-gray-500 uppercase">
                  <th class="px-6 py-4">Élève</th>
                  <th class="px-6 py-4">Parent</th>
                  <th class="px-6 py-4">Classe</th>
                  <th class="px-6 py-4">Date</th>
                  <th class="px-6 py-4">Statut</th>
                  <th class="px-6 py-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr *ngIf="filteredEnrollments().length === 0" class="text-center">
                  <td colspan="6" class="px-6 py-8 text-gray-500">
                    <i class="pi pi-inbox text-4xl text-gray-300"></i>
                    <p class="mt-2">Aucune inscription trouvée</p>
                  </td>
                </tr>
                <tr *ngFor="let e of filteredEnrollments()" class="hover:bg-gray-50">
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center text-teal-700 font-bold">
                        {{ e.firstName?.charAt(0) || '?' }}{{ e.lastName?.charAt(0) || '' }}
                      </div>
                      <div>
                        <div class="font-medium text-gray-800">{{ e.lastName }} {{ e.firstName }}</div>
                        <div class="text-xs text-gray-500">{{ e.birthDate }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-gray-800">{{ e.parentName || 'N/A' }}</div>
                    <div class="text-xs text-gray-500">{{ e.parentPhone || '' }}</div>
                  </td>
                  <td class="px-6 py-4">
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">{{ e.requestedClass }}</span>
                  </td>
                  <td class="px-6 py-4 text-gray-600">{{ e.submittedAt }}</td>
                  <td class="px-6 py-4">
                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                          [ngClass]="{
                            'bg-yellow-100 text-yellow-700': e.status === 'pending',
                            'bg-blue-100 text-blue-700': e.status === 'processing',
                            'bg-green-100 text-green-700': e.status === 'approved' || e.status === 'active',
                            'bg-red-100 text-red-700': e.status === 'rejected'
                          }">
                      {{ getStatusLabel(e.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <div class="flex justify-end gap-2">
                      <button (click)="viewEnrollment(e)" class="p-2 text-gray-500 hover:text-teal-600 hover:bg-teal-50 rounded-lg" title="Voir">
                        <i class="pi pi-eye"></i>
                      </button>
                      <button *ngIf="e.status === 'pending'" (click)="processEnrollment(e)" class="p-2 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded-lg" title="Traiter">
                        <i class="pi pi-cog"></i>
                      </button>
                      <button *ngIf="e.status === 'processing'" (click)="approveEnrollment(e)" class="p-2 text-green-500 hover:text-green-700 hover:bg-green-50 rounded-lg" title="Valider">
                        <i class="pi pi-check"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </ng-container>

      <!-- View/Edit Modal (Existing) -->
      <div *ngIf="selectedEnrollment" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
          <div class="bg-gradient-to-r from-teal-600 to-cyan-600 px-6 py-4 flex items-center justify-between">
            <h2 class="text-white font-bold text-lg">Dossier d'inscription</h2>
            <button (click)="selectedEnrollment = null" class="text-white/80 hover:text-white">
              <i class="pi pi-times text-xl"></i>
            </button>
          </div>
          <div class="p-6 overflow-y-auto max-h-[60vh] space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                <input type="text" [value]="selectedEnrollment.lastName" readonly
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
                <input type="text" [value]="selectedEnrollment.firstName" readonly
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de naissance</label>
                <input type="text" [value]="selectedEnrollment.birthDate" readonly
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Classe</label>
                 <!-- Just showing value here, edit logic can be expanded if needed or re-use new form -->
                <input [value]="selectedEnrollment.requestedClass" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
              </div>
            </div>
          </div>
          <div class="border-t px-6 py-4 flex justify-end gap-3">
            <button (click)="rejectEnrollment(selectedEnrollment)" *ngIf="selectedEnrollment.status !== 'approved' && selectedEnrollment.status !== 'active'"
                    class="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50">
              Rejeter
            </button>
            <button (click)="approveEnrollment(selectedEnrollment)" *ngIf="selectedEnrollment.status !== 'approved' && selectedEnrollment.status !== 'active'"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
              <i class="pi pi-check mr-1"></i> Valider l'inscription
            </button>
          </div>
        </div>
      </div>

      <!-- New Enrollment Modal (Full Form) -->
      <div *ngIf="showNewModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[95vh] overflow-hidden flex flex-col">
          <div class="bg-gradient-to-r from-teal-600 to-cyan-600 px-6 py-4 flex items-center justify-between shrink-0">
            <h2 class="text-white font-bold text-xl">Nouvelle Inscription</h2>
            <button (click)="showNewModal = false" class="text-white/80 hover:text-white">
              <i class="pi pi-times text-xl"></i>
            </button>
          </div>
          
          <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
            <form [formGroup]="enrollmentForm" (ngSubmit)="createEnrollment()" class="space-y-8">
                <!-- Section Élève -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                  <h3 class="text-lg font-bold text-teal-700 mb-4 border-b pb-2 flex items-center gap-2">
                    <i class="pi pi-user"></i> Informations de l'Élève
                  </h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label class="block text-sm font-bold text-gray-700 mb-2">Nom de famille <span class="text-red-500">*</span></label>
                      <input formControlName="lastName" type="text" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500" placeholder="Ex: OUEDRAOGO" />
                    </div>
                    <div>
                      <label class="block text-sm font-bold text-gray-700 mb-2">Prénoms <span class="text-red-500">*</span></label>
                      <input formControlName="firstName" type="text" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500" placeholder="Ex: Jean Pierre" />
                    </div>
                    <div>
                      <label class="block text-sm font-bold text-gray-700 mb-2">Date de naissance <span class="text-red-500">*</span></label>
                      <input formControlName="birthDate" type="date" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500" />
                    </div>
                    <div>
                      <label class="block text-sm font-bold text-gray-700 mb-2">Lieu de naissance <span class="text-red-500">*</span></label>
                      <input formControlName="birthPlace" type="text" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500" placeholder="Ex: Ouagadougou" />
                    </div>
                    <div class="md:col-span-2">
                      <label class="block text-sm font-bold text-gray-700 mb-2">Adresse de résidence <span class="text-red-500">*</span></label>
                      <input formControlName="address" type="text" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500" placeholder="Ex: Secteur 12, Rue 12.34" />
                    </div>
                    <div>
                      <label class="block text-sm font-bold text-gray-700 mb-2">Sexe <span class="text-red-500">*</span></label>
                      <select formControlName="gender" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500">
                        <option value="">Sélectionner</option>
                        <option value="M">Masculin</option>
                        <option value="F">Féminin</option>
                      </select>
                    </div>
                    <div>
                      <label class="block text-sm font-bold text-gray-700 mb-2">Classe demandée <span class="text-red-500">*</span></label>
                      <select formControlName="requestedClass" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500">
                         <option value="">Sélectionner une classe</option>
                         <option *ngFor="let c of classes()" [value]="c.name">{{ c.name }}</option>
                         <!-- Fallback options if classes list is empty or fails -->
                         <option value="6eme">6ème</option>
                         <option value="5eme">5ème</option>
                         <option value="4eme">4ème</option>
                         <option value="3eme">3ème</option>
                         <option value="2nde">2nde</option>
                         <option value="1ere">1ère</option>
                         <option value="Tle">Terminale</option>
                         <option value="Tle D">Tle D</option>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- Section Parent -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                  <h3 class="text-lg font-bold text-teal-700 mb-4 border-b pb-2 flex items-center gap-2">
                    <i class="pi pi-users"></i> Responsable Légal
                  </h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label class="block text-sm font-bold text-gray-700 mb-2">Nom & Prénom <span class="text-red-500">*</span></label>
                      <input formControlName="parentName" type="text" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500" placeholder="Nom du parent" />
                    </div>
                    <div>
                      <label class="block text-sm font-bold text-gray-700 mb-2">Lien de parenté <span class="text-red-500">*</span></label>
                      <select formControlName="parentRelationship" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500">
                        <option value="pere">Père</option>
                        <option value="mere">Mère</option>
                        <option value="tuteur">Tuteur Légal</option>
                      </select>
                    </div>
                    <div>
                      <label class="block text-sm font-bold text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                      <input formControlName="parentEmail" type="email" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500" placeholder="email@exemple.com" />
                    </div>
                    <div>
                      <label class="block text-sm font-bold text-gray-700 mb-2">Téléphone <span class="text-red-500">*</span></label>
                      <input formControlName="parentPhone" type="tel" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-teal-500" placeholder="+226 ..." />
                    </div>
                  </div>
                </div>

                <!-- Error & Success Messages -->
                <div *ngIf="saveError()" class="bg-red-50 border border-red-200 rounded-lg p-3 text-red-700 text-sm">
                  {{ saveError() }}
                </div>
                
                <div *ngIf="saveSuccess()" class="bg-green-50 border border-green-200 rounded-lg p-3 text-green-700 text-sm">
                  {{ saveSuccess() }}
                </div>

                <!-- Buttons -->
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" (click)="showNewModal = false"
                            class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium">
                    Annuler
                    </button>
                    <button type="submit" [disabled]="enrollmentForm.invalid || isSaving()"
                            class="px-8 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-bold shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                    <i *ngIf="isSaving()" class="pi pi-spin pi-spinner mr-2"></i>
                    Inscrire
                    </button>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  `
})
export class SecretaryEnrollmentsComponent implements OnInit {
  private http = inject(HttpClient);
  private fb = inject(FormBuilder);
  private enrollmentService = inject(EnrollmentService);
  private apiUrl = environment.apiUrl || 'http://localhost:8000/api';

  enrollments = signal<Enrollment[]>([]);
  classes = signal<ClassRoom[]>([]);
  selectedEnrollment: Enrollment | null = null;
  showNewModal = false;
  
  isLoading = signal(false);
  error = signal<string | null>(null);
  isSaving = signal(false);
  saveError = signal<string | null>(null);
  saveSuccess = signal<string | null>(null);
  
  searchQuery = '';
  statusFilter = '';
  classFilter = '';

  enrollmentForm: FormGroup;

  constructor() {
    this.enrollmentForm = this.fb.group({
        lastName: ['', Validators.required],
        firstName: ['', Validators.required],
        birthDate: ['', Validators.required],
        birthPlace: ['', Validators.required],
        address: ['', Validators.required],
        gender: ['', Validators.required],
        requestedClass: ['', Validators.required],
        parentName: ['', Validators.required],
        parentRelationship: ['pere', Validators.required],
        parentEmail: ['', [Validators.required, Validators.email]],
        parentPhone: ['', Validators.required]
    });
  }

  ngOnInit() {
    this.loadEnrollments();
    this.loadClasses();
  }

  openNewModal() {
    this.showNewModal = true;
    this.enrollmentForm.reset({
        gender: '',
        requestedClass: '',
        parentRelationship: 'pere'
    });
    this.saveError.set(null);
    this.saveSuccess.set(null);
  }

  loadEnrollments() {
    this.isLoading.set(true);
    this.error.set(null);

    // Try to load from students and enrollments
    this.http.get<any>(`${this.apiUrl}/dashboard/secretary/students`).subscribe({
      next: (response) => {
        const students = response.data || response || [];
        const mappedEnrollments: Enrollment[] = students.map((s: any) => ({
          id: s.id,
          firstName: s.firstName || s.first_name || '',
          lastName: s.lastName || s.last_name || '',
          birthDate: s.birthDate || s.birth_date || '',
          gender: s.gender || 'M',
          parentName: s.parentName || s.parent_name || '',
          parentPhone: s.parentPhone || s.parent_phone || '',
          requestedClass: s.currentClass || s.class_name || s.class_room?.name || s.requestedClass || 'N/A',
          status: s.status || 'active',
          submittedAt: s.created_at ? new Date(s.created_at).toLocaleDateString('fr-FR') : new Date().toLocaleDateString('fr-FR'),
          documents: []
        }));
        this.enrollments.set(mappedEnrollments);
        this.isLoading.set(false);
      },
      error: (err) => {
        console.error('Error loading students:', err);
        this.error.set('Impossible de charger les inscriptions.');
        this.isLoading.set(false);
      }
    });
  }

  loadClasses() {
    this.http.get<any>(`${this.apiUrl}/academic/classrooms`).subscribe({
      next: (response) => {
        const classrooms = response.data || response || [];
        this.classes.set(classrooms.map((c: any) => ({
          id: c.id,
          name: c.name || c.level || `Classe ${c.id}`,
          level: c.level
        })));
      },
      error: (err) => {
        console.warn('Could not load classes, using defaults');
        this.classes.set([
          { id: 1, name: '6ème' },
          { id: 2, name: '5ème' },
          { id: 3, name: '4ème' },
          { id: 4, name: '3ème' },
          { id: 5, name: '2nde' },
          { id: 6, name: '1ere' },
          { id: 7, name: 'Tle' } // Changed to match form options but kept flexible
        ]);
      }
    });
  }

  filteredEnrollments = () => {
    let result = this.enrollments();
    if (this.searchQuery) {
      const q = this.searchQuery.toLowerCase();
      result = result.filter(e => 
        (e.firstName?.toLowerCase() || '').includes(q) || 
        (e.lastName?.toLowerCase() || '').includes(q) ||
        (e.parentName?.toLowerCase() || '').includes(q)
      );
    }
    if (this.statusFilter) {
      result = result.filter(e => e.status === this.statusFilter);
    }
    if (this.classFilter) {
      result = result.filter(e => e.requestedClass === this.classFilter);
    }
    return result;
  };

  getCountByStatus(status: string): number {
    return this.enrollments().filter(e => e.status === status).length;
  }

  getStatusLabel(status: string): string {
    const labels: Record<string, string> = {
      'pending': 'En attente', 
      'processing': 'En cours', 
      'approved': 'Validée', 
      'active': 'Actif',
      'rejected': 'Rejetée'
    };
    return labels[status] || status;
  }

  viewEnrollment(e: Enrollment) { 
    this.selectedEnrollment = { ...e }; 
  }
  
  processEnrollment(e: Enrollment) { 
    this.http.put(`${this.apiUrl}/students/${e.id}`, { status: 'processing' }).subscribe({
      next: () => { e.status = 'processing'; },
      error: (err) => { console.warn('Could not update status:', err); e.status = 'processing'; }
    });
  }
  
  approveEnrollment(e: Enrollment) { 
    this.http.put(`${this.apiUrl}/students/${e.id}`, { status: 'active' }).subscribe({
      next: () => {
        e.status = 'active';
        this.selectedEnrollment = null;
        this.loadEnrollments();
      },
      error: (err) => { console.warn('Could not approve:', err); e.status = 'active'; this.selectedEnrollment = null; }
    });
  }
  
  rejectEnrollment(e: Enrollment) { 
    this.http.put(`${this.apiUrl}/students/${e.id}`, { status: 'rejected' }).subscribe({
      next: () => {
        e.status = 'rejected';
        this.selectedEnrollment = null;
        this.loadEnrollments();
      },
      error: (err) => { console.warn('Could not reject:', err); e.status = 'rejected'; this.selectedEnrollment = null; }
    });
  }
  
  createEnrollment() {
    if (this.enrollmentForm.invalid) {
      this.saveError.set('Veuillez remplir tous les champs obligatoires.');
      return;
    }

    this.isSaving.set(true);
    this.saveError.set(null);
    this.saveSuccess.set(null);

    const formValue = this.enrollmentForm.value;
    
    // Construct EnrollmentRequest
    const requestData: any = {
      student: {
        lastName: formValue.lastName,
        firstName: formValue.firstName,
        birthDate: formValue.birthDate,
        birthPlace: formValue.birthPlace,
        address: formValue.address,
        gender: formValue.gender,
        requestedClass: formValue.requestedClass
      },
      parents: {
        fatherName: formValue.parentRelationship === 'pere' ? formValue.parentName : '',
        motherName: formValue.parentRelationship === 'mere' ? formValue.parentName : '',
        email: formValue.parentEmail,
        fatherPhone: formValue.parentRelationship === 'pere' ? formValue.parentPhone : '',
        motherPhone: formValue.parentRelationship === 'mere' ? formValue.parentPhone : '',
        address: formValue.address
      },
      documents: {
          reportCards: null,
          birthCertificate: null
      }
    };
    
    // Using EnrollmentService to submit to the correct endpoint /enroll
    this.enrollmentService.submitEnrollment(requestData).subscribe({
      next: (response) => {
        this.isSaving.set(false);
        this.saveSuccess.set('Inscription créée avec succès ! Matricule: ' + (response.data?.matricule || '?'));
        
        setTimeout(() => {
          this.showNewModal = false;
          this.saveSuccess.set(null);
          this.enrollmentForm.reset();
          this.loadEnrollments(); 
        }, 1500);
      },
      error: (err) => {
        this.isSaving.set(false);
        console.error('Error creating enrollment:', err);
        let errorMessage = 'Erreur lors de l\'inscription';
        
        if (err.status === 422 && err.error && err.error.errors) {
           errorMessage = 'Erreur de validation :\n' + Object.values(err.error.errors).join('\n');
        } else if (err.error && err.error.message) {
           errorMessage = err.error.message;
        }
        this.saveError.set(errorMessage);
      }
    });
  }
}
