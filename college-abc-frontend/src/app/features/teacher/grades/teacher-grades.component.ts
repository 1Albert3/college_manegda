import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

interface TeacherClass {
  id: string;
  name: string;
  cycle: string;
  students_count: number;
}

interface Subject {
  id: string;
  nom: string;
  code: string;
}

interface Student {
  id: string;
  nom: string;
  prenoms: string;
  matricule: string;
}

interface GradeEntry {
  student_id: string;
  student_name: string;
  matricule: string;
  score: number | null;
  comment?: string;
}

interface Evaluation {
  id: string;
  title: string;
  subject: string;
  subject_id: string;
  class_name: string;
  class_id: string;
  date: string;
  max_score: number;
  type: 'devoir' | 'controle' | 'examen';
  status: 'draft' | 'ongoing' | 'completed';
  trimestre: number;
}

@Component({
  selector: 'app-teacher-grades',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6 relative">
      <!-- Toast Notification -->
      <div *ngIf="showSuccessToast" class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-xl z-[100] flex items-center gap-3 animate-fade-in-down">
        <i class="pi pi-check-circle text-xl"></i>
        <span class="font-medium">{{ successMessage }}</span>
      </div>

      <!-- Error Toast -->
      <div *ngIf="showErrorToast" class="fixed top-4 right-4 bg-red-600 text-white px-6 py-3 rounded-lg shadow-xl z-[100] flex items-center gap-3 animate-fade-in-down">
        <i class="pi pi-times-circle text-xl"></i>
        <span class="font-medium">{{ errorMessage }}</span>
      </div>

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Saisie des Notes</h1>
          <p class="text-gray-500">Enregistrez les notes de vos évaluations</p>
        </div>
        <button (click)="openCreateModal()"
                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 flex items-center gap-2"
                [disabled]="classes().length === 0">
          <i class="pi pi-plus"></i> Nouvelle Évaluation
        </button>
      </div>

      <!-- Loading State -->
      <div *ngIf="isLoading" class="bg-white rounded-xl shadow-sm p-8 text-center">
        <div class="animate-spin w-8 h-8 border-4 border-primary border-t-transparent rounded-full mx-auto mb-4"></div>
        <p class="text-gray-500">Chargement des données...</p>
      </div>

      <!-- No Classes Message -->
      <div *ngIf="!isLoading && classes().length === 0" class="bg-white rounded-xl shadow-sm p-8 text-center">
        <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <i class="pi pi-exclamation-triangle text-amber-600 text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Aucune classe assignée</h3>
        <p class="text-gray-500">Vous n'avez pas encore de classes assignées. Contactez l'administration.</p>
      </div>

      <!-- Main Content -->
      <ng-container *ngIf="!isLoading && classes().length > 0">
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 flex flex-wrap gap-4">
          <select [(ngModel)]="selectedClassId" (change)="onClassChange()"
                  class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
            <option value="">Sélectionnez une classe</option>
            <option *ngFor="let cls of classes()" [value]="cls.id">{{ cls.name }} ({{ cls.cycle }})</option>
          </select>
          <select [(ngModel)]="selectedSubjectId"
                  class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
            <option value="">Toutes les matières</option>
            <option *ngFor="let subj of subjects()" [value]="subj.id">{{ subj.nom }}</option>
          </select>
          <select [(ngModel)]="selectedTrimestre"
                  class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
            <option value="1">Trimestre 1</option>
            <option value="2">Trimestre 2</option>
            <option value="3">Trimestre 3</option>
          </select>
        </div>

        <!-- Quick Grade Entry Card -->
        <div *ngIf="selectedClassId && students().length > 0" class="bg-white rounded-xl shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-primary to-blue-600 px-6 py-4">
            <h2 class="text-white font-bold text-lg flex items-center gap-2">
              <i class="pi pi-pencil"></i>
              Saisie rapide des notes
            </h2>
            <p class="text-white/80 text-sm">{{ getSelectedClassName() }} - {{ students().length }} élèves</p>
          </div>
          
          <!-- Quick entry form -->
          <div class="p-4 bg-gray-50 border-b grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Matière *</label>
              <select [(ngModel)]="quickEntry.subject_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">Sélectionnez</option>
                <option *ngFor="let subj of subjects()" [value]="subj.id">{{ subj.nom }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
              <select [(ngModel)]="quickEntry.type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="IE">Interrogation Écrite</option>
                <option value="DS">Devoir Surveillé</option>
                <option value="TP">Travaux Pratiques</option>
                <option value="Comp">Composition</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
              <input type="date" [(ngModel)]="quickEntry.date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Coefficient</label>
              <input type="number" [(ngModel)]="quickEntry.coefficient" min="1" max="5" 
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
          </div>

          <!-- Students table -->
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr class="text-left text-sm text-gray-500 uppercase">
                  <th class="px-6 py-4 w-12">#</th>
                  <th class="px-6 py-4">Élève</th>
                  <th class="px-6 py-4">Matricule</th>
                  <th class="px-6 py-4 text-center w-32">Note / 20</th>
                  <th class="px-6 py-4">Appréciation</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr *ngFor="let entry of gradeEntries(); let i = index" class="hover:bg-gray-50">
                  <td class="px-6 py-3 text-gray-500">{{ i + 1 }}</td>
                  <td class="px-6 py-3 font-medium text-gray-800">{{ entry.student_name }}</td>
                  <td class="px-6 py-3 text-gray-500">{{ entry.matricule }}</td>
                  <td class="px-6 py-3">
                    <input type="number" [(ngModel)]="entry.score" max="20" min="0" step="0.5"
                           class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-primary mx-auto block"
                           placeholder="--">
                  </td>
                  <td class="px-6 py-3">
                    <input type="text" [(ngModel)]="entry.comment" placeholder="Optionnel"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Save button -->
          <div class="border-t px-6 py-4 flex justify-between items-center bg-gray-50">
            <div class="text-sm text-gray-500">
              <span class="font-medium text-primary">{{ getFilledGradesCount() }}</span> / {{ gradeEntries().length }} notes saisies
            </div>
            <div class="flex gap-3">
              <button (click)="clearGrades()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                <i class="pi pi-refresh mr-1"></i> Effacer
              </button>
              <button (click)="saveGrades()" [disabled]="isSaving || !canSaveGrades()"
                      class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                <i class="pi pi-save" *ngIf="!isSaving"></i>
                <i class="pi pi-spinner pi-spin" *ngIf="isSaving"></i>
                {{ isSaving ? 'Enregistrement...' : 'Enregistrer les notes' }}
              </button>
            </div>
          </div>
        </div>

        <!-- No students message -->
        <div *ngIf="selectedClassId && students().length === 0 && !isLoadingStudents" class="bg-white rounded-xl shadow-sm p-8 text-center">
          <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="pi pi-users text-blue-600 text-2xl"></i>
          </div>
          <h3 class="text-lg font-semibold text-gray-800 mb-2">Aucun élève dans cette classe</h3>
          <p class="text-gray-500">Cette classe n'a pas encore d'élèves inscrits.</p>
        </div>

        <!-- Loading students -->
        <div *ngIf="isLoadingStudents" class="bg-white rounded-xl shadow-sm p-8 text-center">
          <div class="animate-spin w-8 h-8 border-4 border-primary border-t-transparent rounded-full mx-auto mb-4"></div>
          <p class="text-gray-500">Chargement des élèves...</p>
        </div>

        <!-- Select class prompt -->
        <div *ngIf="!selectedClassId" class="bg-white rounded-xl shadow-sm p-8 text-center">
          <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="pi pi-arrow-up text-gray-400 text-2xl"></i>
          </div>
          <h3 class="text-lg font-semibold text-gray-800 mb-2">Sélectionnez une classe</h3>
          <p class="text-gray-500">Choisissez une classe ci-dessus pour commencer la saisie des notes.</p>
        </div>
      </ng-container>

      <!-- Create Evaluation Modal -->
      <div *ngIf="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
          <div class="border-b px-6 py-4">
            <h2 class="text-xl font-bold text-gray-800">Nouvelle Évaluation</h2>
          </div>
          <div class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Titre</label>
              <input type="text" [(ngModel)]="newEvaluation.title"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary"
                     placeholder="Ex: Contrôle de Mathématiques">
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Classe</label>
                <select [(ngModel)]="newEvaluation.class_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                  <option value="">Sélectionnez</option>
                  <option *ngFor="let cls of classes()" [value]="cls.id">{{ cls.name }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Matière</label>
                <select [(ngModel)]="newEvaluation.subject_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                  <option value="">Sélectionnez</option>
                  <option *ngFor="let subj of subjects()" [value]="subj.id">{{ subj.nom }}</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select [(ngModel)]="newEvaluation.type"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                  <option value="IE">Interrogation Écrite</option>
                  <option value="DS">Devoir Surveillé</option>
                  <option value="TP">Travaux Pratiques</option>
                  <option value="Comp">Composition</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Trimestre</label>
                <select [(ngModel)]="newEvaluation.trimestre"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                  <option value="1">Trimestre 1</option>
                  <option value="2">Trimestre 2</option>
                  <option value="3">Trimestre 3</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" [(ngModel)]="newEvaluation.date"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Coefficient</label>
                <input type="number" [(ngModel)]="newEvaluation.coefficient" min="1" max="5"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
              </div>
            </div>
          </div>
          <div class="border-t px-6 py-4 flex justify-end gap-3">
            <button (click)="showCreateModal = false"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
              Annuler
            </button>
            <button (click)="createEvaluation()"
                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
              Créer et Saisir les Notes
            </button>
          </div>
        </div>
      </div>
    </div>
  `
})
export class TeacherGradesComponent implements OnInit {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  classes = signal<TeacherClass[]>([]);
  subjects = signal<Subject[]>([]);
  students = signal<Student[]>([]);
  gradeEntries = signal<GradeEntry[]>([]);
  evaluations = signal<Evaluation[]>([]);

  selectedClassId = '';
  selectedSubjectId = '';
  selectedTrimestre = '1';

  isLoading = true;
  isLoadingStudents = false;
  isSaving = false;

  showCreateModal = false;
  showSuccessToast = false;
  showErrorToast = false;
  successMessage = '';
  errorMessage = '';

  quickEntry = {
    subject_id: '',
    type: 'DS' as 'IE' | 'DS' | 'TP' | 'Comp',
    date: new Date().toISOString().split('T')[0],
    coefficient: 1
  };

  newEvaluation = {
    title: '',
    class_id: '',
    subject_id: '',
    type: 'DS' as 'IE' | 'DS' | 'TP' | 'Comp',
    trimestre: '1',
    date: new Date().toISOString().split('T')[0],
    coefficient: 1
  };

  ngOnInit() {
    this.loadTeacherData();
  }

  loadTeacherData() {
    this.isLoading = true;
    
    // Load teacher dashboard to get classes
    this.http.get<any>(`${this.apiUrl}/dashboard/teacher`).subscribe({
      next: (response) => {
        const data = response.data || response;
        
        // Extract classes from teacher dashboard
        if (data.classes && Array.isArray(data.classes)) {
          this.classes.set(data.classes.map((c: any) => ({
            id: c.id,
            name: c.name || c.nom,
            cycle: c.cycle || 'college',
            students_count: c.students_count || c.effectif || 0
          })));
        }
        
        this.loadSubjects();
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error loading teacher data:', err);
        this.showError('Erreur lors du chargement des données');
        this.isLoading = false;
      }
    });
  }

  loadSubjects() {
    // Load subjects for college by default (since we are in college context mostly)
    // The loadSubjectsForCycle handles switching
    this.http.get<any>(`${this.apiUrl}/college/subjects`).subscribe({
      next: (response) => {
        const subjects = response.data || response;
        if (Array.isArray(subjects)) {
          this.subjects.set(subjects);
        }
      },
      error: () => {
        // Fallback or retry with MP if needed, but for now log error
        console.error('Failed to load college subjects');
      }
    });
  }

  onClassChange() {
    if (!this.selectedClassId) {
      this.students.set([]);
      this.gradeEntries.set([]);
      return;
    }

    this.isLoadingStudents = true;
    const selectedClass = this.classes().find(c => c.id === this.selectedClassId);
    const cycle = selectedClass?.cycle || 'college';

    // Load students for the selected class
    this.http.get<any>(`${this.apiUrl}/${cycle}/classes/${this.selectedClassId}/students`).subscribe({
      next: (response) => {
        const studentsData = response.data || response.students || response;
        
        if (Array.isArray(studentsData)) {
          this.students.set(studentsData);
          
          // Create grade entries for each student
          this.gradeEntries.set(studentsData.map((s: any) => ({
            student_id: s.id,
            student_name: `${s.nom} ${s.prenoms}`,
            matricule: s.matricule || 'N/A',
            score: null,
            comment: ''
          })));
        }
        
        this.isLoadingStudents = false;
      },
      error: (err) => {
        console.error('Error loading students:', err);
        this.showError('Erreur lors du chargement des élèves');
        this.isLoadingStudents = false;
      }
    });

    // Also load subjects for this cycle
    this.loadSubjectsForCycle(cycle);
  }

  loadSubjectsForCycle(cycle: string) {
    const endpoint = `${this.apiUrl}/${cycle}/subjects`;
    
    this.http.get<any>(endpoint).subscribe({
      next: (response) => {
        const subjects = response.data || response;
        if (Array.isArray(subjects)) {
          this.subjects.set(subjects);
        }
      },
      error: (err) => console.error(`Error loading subjects for ${cycle}:`, err)
    });
  }

  getSelectedClassName(): string {
    const cls = this.classes().find(c => c.id === this.selectedClassId);
    return cls ? cls.name : '';
  }

  getFilledGradesCount(): number {
    return this.gradeEntries().filter(e => e.score !== null && e.score !== undefined).length;
  }

  canSaveGrades(): boolean {
    return this.quickEntry.subject_id !== '' && 
           this.quickEntry.date !== '' && 
           this.getFilledGradesCount() > 0;
  }

  clearGrades() {
    this.gradeEntries.update(entries => 
      entries.map(e => ({ ...e, score: null, comment: '' }))
    );
  }

  saveGrades() {
    if (!this.canSaveGrades()) {
      this.showError('Veuillez remplir tous les champs obligatoires et saisir au moins une note');
      return;
    }

    this.isSaving = true;
    const selectedClass = this.classes().find(c => c.id === this.selectedClassId);
    const cycle = selectedClass?.cycle || 'college';

    // Prepare grades data
    const gradesData = this.gradeEntries()
      .filter(e => e.score !== null && e.score !== undefined)
      .map(e => ({
        student_id: e.student_id,
        note: e.score,
        appreciation: e.comment || null
      }));

    // Get current school year
    this.http.get<any>(`${this.apiUrl}/core/school-years/current`).subscribe({
      next: (schoolYearResponse) => {
        const schoolYear = schoolYearResponse.data || schoolYearResponse;
        
        const payload = {
          class_id: this.selectedClassId,
          subject_id: this.quickEntry.subject_id,
          school_year_id: schoolYear.id,
          trimestre: parseInt(this.selectedTrimestre),
          type_evaluation: this.quickEntry.type,
          date_evaluation: this.quickEntry.date,
          coefficient: this.quickEntry.coefficient,
          grades: gradesData
        };

        // Save grades via API
        this.http.post<any>(`${this.apiUrl}/${cycle}/grades/bulk`, payload).subscribe({
          next: (response) => {
            this.showSuccess(response.message || `${gradesData.length} notes enregistrées avec succès !`);
            this.isSaving = false;
            this.clearGrades();
          },
          error: (err) => {
            console.error('Error saving grades:', err);
            let errorMsg = err.error?.message || err.error?.error || 'Erreur lors de l\'enregistrement';
            
            // Handle validation errors from Laravel
            if (err.status === 422 && err.error?.errors) {
              const validationErrors = Object.values(err.error.errors).flat();
              if (validationErrors.length > 0) {
                errorMsg = validationErrors[0] as string;
              }
            }
            
            this.showError(errorMsg);
            this.isSaving = false;
          }
        });
      },
      error: (err) => {
        console.error('Error getting school year:', err);
        this.showError('Erreur: impossible de récupérer l\'année scolaire');
        this.isSaving = false;
      }
    });
  }

  openCreateModal() {
    this.newEvaluation = {
      title: '',
      class_id: this.selectedClassId || '',
      subject_id: '',
      type: 'DS',
      trimestre: this.selectedTrimestre,
      date: new Date().toISOString().split('T')[0],
      coefficient: 1
    };
    this.showCreateModal = true;
  }

  createEvaluation() {
    if (!this.newEvaluation.class_id || !this.newEvaluation.subject_id) {
      this.showError('Veuillez sélectionner une classe et une matière');
      return;
    }

    // Select the class and pre-fill the quick entry form
    this.selectedClassId = this.newEvaluation.class_id;
    this.quickEntry = {
      subject_id: this.newEvaluation.subject_id,
      type: this.newEvaluation.type,
      date: this.newEvaluation.date,
      coefficient: this.newEvaluation.coefficient
    };
    this.selectedTrimestre = this.newEvaluation.trimestre;

    this.showCreateModal = false;
    this.onClassChange();
    this.showSuccess('Évaluation préparée. Saisissez les notes ci-dessous.');
  }

  private showSuccess(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 4000);
  }

  private showError(message: string) {
    this.errorMessage = message;
    this.showErrorToast = true;
    setTimeout(() => this.showErrorToast = false, 5000);
  }
}
