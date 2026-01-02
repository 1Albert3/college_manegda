import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { ClassService } from '../../../core/services/class.service';
import { AcademicService } from '../../../core/services/academic.service';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-college-classes',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  template: `
    <div class="p-6 bg-gray-50 min-h-screen">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Cycle Collège</h1>
          <p class="text-gray-500 mt-1 italic">Premier Cycle de l'Enseignement Secondaire Général (6ème à 3ème)</p>
        </div>
        <div class="flex items-center gap-3">
          <div class="bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-sm font-bold border border-blue-200 shadow-sm">
            {{ classes.length }} Classes actives
          </div>
          <button (click)="openModal('add')" class="bg-blue-600 text-white px-5 py-2 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100 flex items-center gap-2">
            <i class="pi pi-plus"></i>
            <span>Nouvelle Classe</span>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div *ngIf="loading" class="flex flex-col items-center justify-center h-64">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-gray-500 font-medium">Chargement des classes du collège...</p>
      </div>

      <!-- Empty State -->
      <div *ngIf="!loading && classes.length === 0" class="bg-white rounded-2xl p-12 text-center shadow-sm border border-gray-200">
        <div class="bg-gray-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
          <i class="pi pi-briefcase text-3xl text-gray-300"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Aucune classe pour le moment</h3>
        <p class="text-gray-500 max-w-xs mx-auto mt-2">Le collège n'a pas encore de classes configurées pour cette année scolaire.</p>
        <button (click)="openModal('add')" class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-blue-700">Ajouter une classe</button>
      </div>

      <!-- Classes Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" *ngIf="!loading && classes.length > 0">
        <div *ngFor="let cls of classes" 
             class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden flex flex-col">
          
          <div [class]="getLevelColor(cls.niveau)" class="h-3 shadow-inner"></div>
          
          <div class="p-6 flex-grow">
            <div class="flex justify-between items-start mb-4">
              <div>
                <span class="text-xs font-black uppercase tracking-widest text-gray-400 mb-1 block">Niveau {{ cls.niveau }}</span>
                <h3 class="text-2xl font-bold text-gray-900 leading-tight group-hover:text-blue-600 transition-colors">{{ cls.nom }}</h3>
              </div>
              <div class="bg-gray-50 rounded-lg px-3 py-1 flex flex-col items-center border border-gray-100">
                <span class="text-xl font-black text-gray-800">{{ cls.effectif_actuel || 0 }}</span>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Élèves</span>
              </div>
            </div>

            <div class="space-y-3 mb-6">
              <div class="flex items-center gap-3 text-sm text-gray-600">
                <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-blue-50 group-hover:text-blue-400 transition-colors">
                  <i class="pi pi-user text-xs"></i>
                </div>
                <div>
                  <p class="text-[10px] font-bold text-gray-400 uppercase leading-none mb-1">Professeur Principal</p>
                  <p class="font-medium text-gray-800">{{ cls.prof_principal?.user?.name || 'Non assigné' }}</p>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mt-auto">
              <a [routerLink]="['/admin/college/classes', cls.id, 'students']" 
                 class="flex items-center justify-center gap-2 bg-blue-50 text-blue-700 py-3 rounded-xl hover:bg-blue-100 transition font-bold text-sm shadow-sm border border-blue-100">
                <i class="pi pi-users text-xs"></i> Élèves
              </a>
              <a [routerLink]="['/admin/college/classes', cls.id, 'grades']"
                 class="flex items-center justify-center gap-2 bg-emerald-50 text-emerald-700 py-3 rounded-xl hover:bg-emerald-100 transition font-bold text-sm shadow-sm border border-emerald-100">
                <i class="pi pi-chart-line text-xs"></i> Notes
              </a>
            </div>
            
            <div class="flex gap-2 mt-3">
              <button (click)="openModal('edit', cls)"
                 class="flex-1 flex items-center justify-center gap-2 bg-white border border-gray-200 text-blue-600 py-3 rounded-xl hover:bg-gray-50 transition font-bold text-sm shadow-sm">
                <i class="pi pi-pencil text-xs"></i> Modifier
              </button>
              <button (click)="deleteClass(cls)"
                 class="flex-1 flex items-center justify-center gap-2 bg-white border border-gray-200 text-red-600 py-3 rounded-xl hover:bg-red-50 transition font-bold text-sm shadow-sm">
                <i class="pi pi-trash text-xs"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div *ngIf="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="closeModal()">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" (click)="$event.stopPropagation()">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
          <h2 class="text-xl font-bold text-white">
            {{ modalMode === 'add' ? 'Nouvelle Classe' : 'Modifier la Classe' }}
          </h2>
          <p class="text-blue-100 text-sm">{{ modalMode === 'add' ? 'Créer une nouvelle classe pour le collège' : 'Modifier les informations de la classe' }}</p>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Nom de la classe *</label>
            <input 
              type="text" 
              [(ngModel)]="formData.nom" 
              placeholder="Ex: 6ème A"
              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
            >
          </div>
          
          <div *ngIf="modalMode === 'add'">
            <label class="block text-sm font-bold text-gray-700 mb-2">Niveau *</label>
            <select 
              [(ngModel)]="formData.niveau" 
              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition bg-white"
            >
              <option value="6eme">6ème</option>
              <option value="5eme">5ème</option>
              <option value="4eme">4ème</option>
              <option value="3eme">3ème</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4" *ngIf="modalMode === 'add'">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Seuil minimum</label>
              <input 
                type="number" 
                [(ngModel)]="formData.seuil_minimum" 
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
              >
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Seuil maximum</label>
              <input 
                type="number" 
                [(ngModel)]="formData.seuil_maximum" 
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
              >
            </div>
          </div>

          <!-- Error message -->
          <div *ngIf="errorMessage" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
            {{ errorMessage }}
          </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
          <button 
            (click)="closeModal()" 
            class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-100 transition"
          >
            Annuler
          </button>
          <button 
            (click)="saveClass()" 
            [disabled]="saving"
            class="px-5 py-2.5 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <i *ngIf="saving" class="pi pi-spin pi-spinner"></i>
            {{ modalMode === 'add' ? 'Créer' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>
  `
})
export class CollegeClassesComponent implements OnInit {
  private academicService = inject(AcademicService);

  classes: any[] = [];
  loading = true;
  currentSchoolYearId: string | null = null;
  
  // Modal state
  showModal = false;
  modalMode: 'add' | 'edit' = 'add';
  saving = false;
  errorMessage = '';
  selectedClass: any = null;
  
  formData = {
    nom: '',
    niveau: '6eme',
    seuil_minimum: 15,
    seuil_maximum: 40
  };

  constructor(private classService: ClassService, private http: HttpClient) {}

  ngOnInit() {
    this.loadCurrentSchoolYear();
    this.loadClasses();
  }

  loadCurrentSchoolYear() {
    this.academicService.getCurrentYear().subscribe({
      next: (year) => {
        if (year?.id) {
          this.currentSchoolYearId = year.id;
          console.log('College loaded year:', year.name);
        }
      },
      error: (err) => console.error('Error loading year:', err)
    });
  }

  loadClasses() {
    this.loading = true;
    this.classService.getClassesCollege().subscribe({
      next: (data: any) => {
        this.classes = data.data || data;
        this.loading = false;
      },
      error: (err) => {
        console.error('Error fetching college classes', err);
        this.loading = false;
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
        seuil_minimum: cls.seuil_minimum || 15,
        seuil_maximum: cls.seuil_maximum || 40
      };
    } else {
      this.selectedClass = null;
      this.formData = {
        nom: '',
        niveau: '6eme',
        seuil_minimum: 15,
        seuil_maximum: 40
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
        this.errorMessage = 'Année scolaire non chargée. Veuillez rafraîchir la page.';
        this.saving = false;
        return;
      }

      const data = {
        nom: this.formData.nom,
        niveau: this.formData.niveau,
        school_year_id: this.currentSchoolYearId,
        seuil_minimum: this.formData.seuil_minimum,
        seuil_maximum: this.formData.seuil_maximum
      };

      this.classService.createClass('college', data).subscribe({
        next: () => {
          this.closeModal();
          this.loadClasses();
        },
        error: (err) => {
          this.saving = false;
          this.errorMessage = err.error?.message || 'Erreur lors de la création.';
        }
      });
    } else {
      // Edit mode
      this.classService.updateClass('college', this.selectedClass.id, {
        ...this.selectedClass,
        nom: this.formData.nom
      }).subscribe({
        next: () => {
          this.closeModal();
          this.loadClasses();
        },
        error: (err) => {
          this.saving = false;
          this.errorMessage = err.error?.message || 'Erreur lors de la modification.';
        }
      });
    }
  }

  deleteClass(cls: any) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer la classe "\${cls.nom}" ?`)) {
      this.classService.deleteClass('college', cls.id).subscribe({
        next: () => this.loadClasses(),
        error: (err) => alert('Erreur: ' + (err.error?.message || err.message))
      });
    }
  }

  getLevelColor(niveau: string): string {
    const n = niveau?.toUpperCase() || '';
    if (n.startsWith('6')) return 'bg-blue-400';
    if (n.startsWith('5')) return 'bg-blue-500';
    if (n.startsWith('4')) return 'bg-blue-600';
    if (n.startsWith('3')) return 'bg-blue-800';
    return 'bg-slate-400';
  }
}
