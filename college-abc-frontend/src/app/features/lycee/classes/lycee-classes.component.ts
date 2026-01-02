// ... imports
import { Component, OnInit, inject, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { ClassService } from '../../../core/services/class.service';
import { AcademicService } from '../../../core/services/academic.service';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-lycee-classes',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  template: `
    <div class="p-6 bg-gray-50 min-h-screen">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Cycle Lycée</h1>
          <p class="text-gray-500 mt-1 italic">Second Cycle de l'Enseignement Secondaire Général</p>
        </div>
        <div class="flex items-center gap-3">
          <div class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full text-sm font-bold border border-indigo-200 shadow-sm">
            {{ classes.length }} Classes actives
          </div>
          <button (click)="openModal('add')" class="bg-indigo-600 text-white px-5 py-2 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 flex items-center gap-2">
            <i class="pi pi-plus"></i>
            <span>Nouvelle Classe</span>
          </button>
        </div>
      </div>

      <div *ngIf="loading" class="flex flex-col items-center justify-center h-64">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mb-4"></div>
        <p class="text-gray-500 font-medium">Chargement des classes...</p>
      </div>

      <div *ngIf="!loading && classes.length === 0" class="bg-white rounded-2xl p-12 text-center shadow-sm border border-gray-200">
        <div class="bg-gray-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
          <i class="pi pi-folder-open text-3xl text-gray-300"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Aucune classe pour le moment</h3>
        <p class="text-gray-500 max-w-xs mx-auto mt-2">Les classes de 2nde, 1ère et Tle apparaîtront ici dès qu'elles seront configurées.</p>
        <button (click)="openModal('add')" class="mt-6 bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-indigo-700 transition">
          Ajouter une classe
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" *ngIf="!loading && classes.length > 0">
        <div *ngFor="let cls of classes" 
             class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden flex flex-col">
          
          <div [class]="getLevelColor(cls.niveau)" class="h-3 shadow-inner"></div>
          
          <div class="p-6 flex-grow">
            <div class="flex justify-between items-start mb-4">
              <div>
                <span class="text-xs font-black uppercase tracking-widest text-gray-400 mb-1 block">{{ cls.niveau }}</span>
                <h3 class="text-2xl font-bold text-gray-900 leading-tight group-hover:text-indigo-600 transition-colors">{{ cls.nom }}</h3>
              </div>
              <div class="bg-gray-100 rounded-lg px-3 py-1 flex flex-col items-center border border-gray-200">
                <span class="text-xl font-black text-gray-800">{{ cls.effectif || 0 }}</span>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Élèves</span>
              </div>
            </div>

            <div class="space-y-3 mb-6">
              <div class="flex items-center gap-3 text-sm text-gray-600">
                <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400">
                  <i class="pi pi-user text-xs"></i>
                </div>
                <div>
                  <p class="text-[10px] font-bold text-gray-400 uppercase leading-none mb-1">Professeur Principal</p>
                  <p class="font-medium text-gray-800">{{ cls.teacher?.name || cls.enseignant_principal || 'Non assigné' }}</p>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mt-auto">
              <a [routerLink]="['/admin/lycee/classes', cls.id, 'students']" 
                 class="flex items-center justify-center gap-2 bg-indigo-50 text-indigo-700 py-3 rounded-xl hover:bg-indigo-100 transition font-bold text-sm shadow-sm border border-indigo-100">
                <i class="pi pi-users"></i> Élèves
              </a>
              <a [routerLink]="['/admin/lycee/classes', cls.id, 'grades']"
                 class="flex items-center justify-center gap-2 bg-emerald-50 text-emerald-700 py-3 rounded-xl hover:bg-emerald-100 transition font-bold text-sm shadow-sm border border-emerald-100">
                <i class="pi pi-chart-line"></i> Notes
              </a>
            </div>
            
            <div class="flex gap-2 mt-3">
              <button (click)="openAssignments(cls)"
                 class="flex-1 flex items-center justify-center gap-2 bg-amber-50 border border-amber-100 text-amber-700 py-3 rounded-xl hover:bg-amber-100 transition font-bold text-sm shadow-sm">
                <i class="pi pi-briefcase text-xs"></i> Profs
              </button>
              <button (click)="openModal('edit', cls)"
                 class="flex-1 flex items-center justify-center gap-2 bg-white border border-gray-200 text-indigo-600 py-3 rounded-xl hover:bg-gray-50 transition font-bold text-sm shadow-sm">
                <i class="pi pi-pencil text-xs"></i> Modifier
              </button>
              <button (click)="deleteClass(cls)"
                 class="px-4 flex items-center justify-center bg-white border border-gray-200 text-red-600 py-3 rounded-xl hover:bg-red-50 transition font-bold text-sm shadow-sm">
                <i class="pi pi-trash text-xs"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Class Form -->
    <div *ngIf="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="closeModal()">
      <!-- ... (Existing class form) ... -->
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" (click)="$event.stopPropagation()">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
          <h2 class="text-xl font-bold text-white">
            {{ modalMode === 'add' ? 'Nouvelle Classe' : 'Modifier la Classe' }}
          </h2>
          <p class="text-indigo-100 text-sm">{{ modalMode === 'add' ? 'Créer une nouvelle classe pour le lycée' : 'Modifier les informations de la classe' }}</p>
        </div>
        
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Nom de la classe *</label>
            <input type="text" [(ngModel)]="formData.nom" placeholder="Ex: 2nde A" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 transition">
          </div>
          
          <div *ngIf="modalMode === 'add'">
            <label class="block text-sm font-bold text-gray-700 mb-2">Niveau *</label>
            <select [(ngModel)]="formData.niveau" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 transition bg-white">
              <option value="2nde">2nde</option>
              <option value="1ere">1ère</option>
              <option value="Tle">Terminale</option>
            </select>
          </div>

          <div *ngIf="modalMode === 'add' && formData.niveau !== '2nde'">
            <label class="block text-sm font-bold text-gray-700 mb-2">Série</label>
            <select [(ngModel)]="formData.serie" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 transition bg-white">
              <option value="">Aucune</option>
              <option value="A">A (Littéraire)</option>
              <option value="C">C (Mathématiques)</option>
              <option value="D">D (Scientifique)</option>
              <option value="E">E (Technique)</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4" *ngIf="modalMode === 'add'">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Seuil min</label>
              <input type="number" [(ngModel)]="formData.seuil_minimum" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Seuil max</label>
              <input type="number" [(ngModel)]="formData.seuil_maximum" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500">
            </div>
          </div>

          <div *ngIf="errorMessage" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm whitespace-pre-line">
            {{ errorMessage }}
          </div>
        </div>
        
        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
          <button (click)="closeModal()" class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-100 transition">Annuler</button>
          <button (click)="saveClass()" [disabled]="saving" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition disabled:opacity-50 flex items-center gap-2">
            <i *ngIf="saving" class="pi pi-spin pi-spinner"></i>
            {{ modalMode === 'add' ? 'Créer' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Assignments Modal -->
    <div *ngIf="showAssignmentsModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4 backdrop-blur-sm" (click)="closeAssignmentsModal()">
       <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden h-auto max-h-[90vh] flex flex-col" (click)="$event.stopPropagation()">
          <div class="bg-amber-600 px-6 py-4 flex justify-between items-center">
             <div>
                <h2 class="text-xl font-bold text-white">Enseignants & Matières</h2>
                <p class="text-amber-100 text-sm">Gestion des professeurs pour {{ selectedClass?.nom }}</p>
             </div>
             <button (click)="closeAssignmentsModal()" class="text-white hover:bg-amber-700 p-2 rounded-lg"><i class="pi pi-times"></i></button>
          </div>

          <div class="p-6 overflow-y-auto flex-grow">
             <!-- Add Form -->
             <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 mb-6">
                <h3 class="text-sm font-bold text-gray-700 uppercase mb-3">Ajouter un enseignant</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                   <select [(ngModel)]="assignData.teacher_id" class="px-3 py-2 rounded-lg border border-gray-300 text-sm">
                      <option value="">Sélectionnez un enseignant</option>
                      <option *ngFor="let t of availableTeachers" [value]="t.id">{{ t.name }}</option>
                   </select>
                   <select [(ngModel)]="assignData.subject_id" class="px-3 py-2 rounded-lg border border-gray-300 text-sm">
                      <option value="">Sélectionnez une matière</option>
                      <option *ngFor="let s of availableSubjects" [value]="s.id">{{ s.name }}</option>
                   </select>
                   <button (click)="assignTeacher()" [disabled]="saving || !assignData.teacher_id || !assignData.subject_id" class="bg-emerald-600 text-white font-bold py-2 rounded-lg hover:bg-emerald-700 disabled:opacity-50 text-sm">
                     <i class="pi pi-plus mr-1"></i> Assigner
                   </button>
                </div>
                <div *ngIf="successMessage" class="mt-3 p-3 rounded-lg text-sm font-semibold" [ngClass]="{'bg-green-100 text-green-800': successMessage.includes('✅'), 'bg-red-100 text-red-800': successMessage.includes('❌')}">
                   {{ successMessage }}
                </div>
             </div>
             
             <!-- List -->
             <div>
                <h3 class="text-sm font-bold text-gray-700 uppercase mb-3">Enseignants assignés ({{ assignments.length }})</h3>
                <div *ngIf="loadingAssignments" class="text-center py-4 text-gray-500"><i class="pi pi-spin pi-spinner"></i> Chargement...</div>
                
                <div *ngIf="!loadingAssignments && assignments.length === 0" class="text-center py-8 bg-gray-50 rounded-xl border border-dashed border-gray-300 text-gray-400">
                   Aucun enseignant assigné pour le moment.
                </div>

                <div class="space-y-2">
                   <div *ngFor="let a of assignments" class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-lg hover:shadow-sm transition">
                      <div class="flex items-center gap-3">
                         <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs">
                           {{ a.teacher_name?.charAt(0) }}
                         </div>
                         <div>
                            <p class="font-bold text-gray-800 text-sm">{{ a.teacher_name }}</p>
                            <p class="text-xs text-gray-500">{{ a.subject_name }}</p>
                         </div>
                      </div>
                      <button (click)="removeAssignment(a)" class="text-red-400 hover:text-red-600 hover:bg-red-50 p-2 rounded-lg transition" title="Retirer">
                         <i class="pi pi-trash"></i>
                      </button>
                   </div>
                </div>
             </div>
          </div>
       </div>
    </div>
  `
})
export class LyceeClassesComponent implements OnInit {
  private academicService = inject(AcademicService);
  private cdr = inject(ChangeDetectorRef);

  classes: any[] = [];
  loading = true;
  currentSchoolYearId: string | null = null;
  
  showModal = false;
  showAssignmentsModal = false;
  modalMode: 'add' | 'edit' = 'add';
  saving = false;
  errorMessage = '';
  selectedClass: any = null;
  
  // Assignment Data
  assignments: any[] = [];
  availableTeachers: any[] = [];
  availableSubjects: any[] = [];
  loadingAssignments = false;
  assignData = { teacher_id: '', subject_id: '' };
  successMessage = '';
  
  formData = {
    nom: '',
    niveau: '2nde',
    serie: '',
    seuil_minimum: 15,
    seuil_maximum: 45
  };

  constructor(private classService: ClassService, private http: HttpClient) {}

  ngOnInit() {
    this.loadCurrentSchoolYear();
    this.loadClasses();
    this.loadResources();
  }

  loadCurrentSchoolYear() {
    this.academicService.getCurrentYear().subscribe({
      next: (year) => {
        if (year?.id) {
          this.currentSchoolYearId = year.id;
        }
      }
    });
  }

  loadClasses() {
    this.loading = true;
    this.classService.getClassesLycee().subscribe({
      next: (data: any) => {
        this.classes = data.data || data;
        this.loading = false;
      },
      error: () => this.loading = false
    });
  }
  
  loadResources() {
    this.classService.getAvailableResourcesLycee().subscribe({
        next: (res: any) => {
            this.availableTeachers = res.teachers;
            this.availableSubjects = res.subjects;
        }
    });
  }

  openModal(mode: 'add' | 'edit', cls?: any) {
    this.modalMode = mode;
    this.errorMessage = '';
    this.saving = false;
    
    if (mode === 'edit' && cls) {
      this.selectedClass = cls;
      this.formData = {
        nom: cls.nom,
        niveau: cls.niveau,
        serie: cls.serie || '',
        seuil_minimum: cls.seuil_minimum || 15,
        seuil_maximum: cls.seuil_maximum || 45
      };
    } else {
      this.selectedClass = null;
      this.formData = {
        nom: '',
        niveau: '2nde',
        serie: '',
        seuil_minimum: 15,
        seuil_maximum: 45
      };
    }
    
    this.showModal = true;
  }

  closeModal() {
    this.showModal = false;
    this.errorMessage = '';
  }

  saveClass() {
    if (!this.formData.nom.trim()) {
      this.errorMessage = 'Le nom de la classe est requis.';
      return;
    }
    this.saving = true;
    this.errorMessage = '';

    if (this.modalMode === 'add') {
      if (!this.currentSchoolYearId) {
        this.errorMessage = 'Année scolaire non chargée.';
        this.saving = false;
        return;
      }
      const data = {
        nom: this.formData.nom,
        niveau: this.formData.niveau,
        serie: this.formData.serie || null,
        school_year_id: this.currentSchoolYearId,
        seuil_minimum: this.formData.seuil_minimum,
        seuil_maximum: this.formData.seuil_maximum
      };

      this.classService.createClass('lycee', data).subscribe({
        next: () => { this.closeModal(); this.loadClasses(); },
        error: (err) => { this.saving = false; this.errorMessage = err.error?.message || 'Erreur.'; }
      });
    } else {
      this.classService.updateClass('lycee', this.selectedClass.id, {
        ...this.selectedClass,
        nom: this.formData.nom,
        serie: this.formData.serie || null,
        seuil_minimum: this.formData.seuil_minimum,
        seuil_maximum: this.formData.seuil_maximum
      }).subscribe({
        next: () => { this.closeModal(); this.loadClasses(); },
        error: (err) => { this.saving = false; this.errorMessage = err.error?.message || 'Erreur.'; }
      });
    }
  }

  deleteClass(cls: any) {
    if (confirm(`Supprimer la classe "\${cls.nom}" ?`)) {
      this.classService.deleteClass('lycee', cls.id).subscribe({
        next: () => this.loadClasses(),
        error: (err) => alert('Erreur: ' + (err.error?.message || err.message))
      });
    }
  }

  getLevelColor(niveau: string): string {
    const n = niveau?.toUpperCase() || '';
    if (n.startsWith('2')) return 'bg-indigo-500';
    if (n.startsWith('1')) return 'bg-violet-600';
    if (n.startsWith('T')) return 'bg-purple-700';
    return 'bg-slate-400';
  }

  // --- ASSIGNMENTS LOGIC ---
  openAssignments(cls: any) {
    this.selectedClass = cls;
    this.loadingAssignments = true;
    this.showAssignmentsModal = true;
    this.assignData = { teacher_id: '', subject_id: '' };
    
    // Load available teachers and subjects
    this.loadResources();
    
    this.classService.getAssignmentsLycee(cls.id).subscribe({
       next: (res) => {
          this.assignments = res || [];
          this.loadingAssignments = false;
       },
       error: () => this.loadingAssignments = false
    });
  }

  closeAssignmentsModal() {
    this.showAssignmentsModal = false;
  }

  assignTeacher() {
     if (!this.selectedClass || !this.currentSchoolYearId) return;
     this.saving = true;
     this.successMessage = '';
     
     const payload = {
        teacher_id: this.assignData.teacher_id,
        subject_id: this.assignData.subject_id,
        school_year_id: this.currentSchoolYearId
     };

     this.classService.assignTeacherLycee(this.selectedClass.id, payload).subscribe({
        next: () => {
           console.log('✅ ASSIGN SUCCESS');
           this.saving = false;
           this.assignData = { teacher_id: '', subject_id: '' };
           this.successMessage = '✅ Enseignant assigné avec succès !';
           console.log('successMessage set to:', this.successMessage);
           this.cdr.detectChanges(); // Force update UI
           
           setTimeout(() => {
              this.successMessage = '';
              this.cdr.detectChanges();
           }, 5000);

           // Reload list
           this.classService.getAssignmentsLycee(this.selectedClass.id).subscribe(res => {
              this.assignments = res;
              this.cdr.detectChanges(); // Force update list
           });
        },
        error: (err) => {
           console.log('❌ ASSIGN ERROR:', err);
           this.saving = false;
           this.successMessage = '❌ ' + (err.error?.message || "Erreur d'assignation");
           console.log('successMessage set to:', this.successMessage);
           this.cdr.detectChanges(); // Force update UI

           setTimeout(() => {
              this.successMessage = '';
              this.cdr.detectChanges();
           }, 5000);
        }
     });
  }

  removeAssignment(a: any) {
     if(!confirm("Retirer cet enseignant ?")) return;
     this.classService.removeAssignmentLycee(this.selectedClass.id, a.assignment_id).subscribe({
        next: () => {
           this.assignments = this.assignments.filter(x => x.assignment_id !== a.assignment_id);
        },
        error: (err) => alert("Erreur suppression")
     });
  }
}
