import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { finalize } from 'rxjs/operators';
import { ClassService } from '../../../core/services/class.service';
import { AcademicService } from '../../../core/services/academic.service';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-mp-classes',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  template: `
    <div class="p-6 bg-gray-50 min-h-screen">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Gestion Maternelle & Primaire</h1>
          <p class="text-gray-600">Liste des classes et suivi pédagogique</p>
        </div>
        <button (click)="openModal('add')" class="bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700 transition shadow-sm flex items-center gap-2">
          <i class="pi pi-plus"></i>
          <span>Nouvelle Classe</span>
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div *ngFor="let classe of classes" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-300">
            <div class="h-2 w-full" [ngClass]="getHeaderColor(classe.level)"></div>
            
            <div class="p-5">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">{{classe.name}}</h3>
                        <p class="text-sm text-gray-500 font-medium">{{ getLevelLabel(classe.level) }}</p>
                    </div>
                    <span class="bg-pink-50 text-pink-700 px-2 py-1 rounded text-xs font-semibold">
                        {{ classe.capacity }} places
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2 mt-4 text-sm">
                    <a [routerLink]="['/admin/mp/classes', classe.id, 'students']" class="flex flex-col items-center p-2 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors cursor-pointer text-center">
                        <i class="pi pi-id-card text-blue-600 mb-1 text-lg"></i>
                        <span class="text-gray-700 font-medium text-xs">Élèves</span>
                    </a>
                    <a [routerLink]="['/admin/mp/classes', classe.id, 'grades']" class="flex flex-col items-center p-2 bg-green-50 rounded-lg hover:bg-green-100 transition-colors cursor-pointer text-center">
                        <i class="pi pi-chart-bar text-green-600 mb-1 text-lg"></i>
                        <span class="text-gray-700 font-medium text-xs">Notes</span>
                    </a>
                    <a [routerLink]="['/admin/mp/classes', classe.id, 'attendance']" class="flex flex-col items-center p-2 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors cursor-pointer text-center">
                        <i class="pi pi-calendar-times text-purple-600 mb-1 text-lg"></i>
                        <span class="text-gray-700 font-medium text-xs">Absences</span>
                    </a>
                    <a [routerLink]="['/admin/mp/classes', classe.id, 'bulletins']" class="flex flex-col items-center p-2 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors cursor-pointer text-center">
                        <i class="pi pi-book text-orange-600 mb-1 text-lg"></i>
                        <span class="text-gray-700 font-medium text-xs">Bulletins</span>
                    </a>
                </div>
            </div>
            
            <div class="bg-gray-50 px-5 py-3 border-t border-gray-100 flex justify-between">
                <button (click)="openModal('edit', classe)" class="text-pink-600 hover:text-pink-800 text-sm font-medium flex items-center gap-1">
                    <i class="pi pi-pencil"></i> Modifier
                </button>
                <button (click)="deleteClass(classe)" class="text-red-600 hover:text-red-800 text-sm font-medium flex items-center gap-1">
                    <i class="pi pi-trash"></i>
                </button>
            </div>
        </div>
      </div>

      <div *ngIf="classes.length === 0" class="text-center py-12">
        <div class="bg-white rounded-full h-24 w-24 flex items-center justify-center mx-auto mb-4 shadow-sm border border-gray-100">
            <i class="pi pi-briefcase text-4xl text-gray-300"></i>
        </div>
        <h3 class="text-xl font-medium text-gray-800">Aucune classe trouvée</h3>
        <p class="text-gray-500 mt-2">Commencez par créer une classe pour ce cycle.</p>
        <button (click)="openModal('add')" class="mt-4 bg-pink-600 text-white px-4 py-2 rounded-lg">Créer une classe</button>
      </div>
    </div>

    <!-- Modal -->
    <div *ngIf="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="closeModal()">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" (click)="$event.stopPropagation()">
        <div class="bg-gradient-to-r from-pink-500 to-rose-500 px-6 py-4">
          <h2 class="text-xl font-bold text-white">
            {{ modalMode === 'add' ? 'Nouvelle Classe' : 'Modifier la Classe' }}
          </h2>
          <p class="text-pink-100 text-sm">{{ modalMode === 'add' ? 'Créer une nouvelle classe MP' : 'Modifier les informations de la classe' }}</p>
        </div>
        
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Nom de la classe *</label>
            <input 
              type="text" 
              [(ngModel)]="formData.nom" 
              placeholder="Ex: CP2 A"
              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition"
            >
          </div>
          
          <div *ngIf="modalMode === 'add'">
            <label class="block text-sm font-bold text-gray-700 mb-2">Niveau *</label>
            <select 
              [(ngModel)]="formData.niveau" 
              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition bg-white"
            >
              <option value="PS">Petite Section</option>
              <option value="MS">Moyenne Section</option>
              <option value="GS">Grande Section</option>
              <option value="CP">Cours Préparatoire</option>
              <option value="CE1">CE1</option>
              <option value="CE2">CE2</option>
              <option value="CM1">CM1</option>
              <option value="CM2">CM2</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4" *ngIf="modalMode === 'add'">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Capacité</label>
              <input 
                type="number" 
                [(ngModel)]="formData.capacity" 
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition"
              >
            </div>
          </div>

          <div *ngIf="errorMessage" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm whitespace-pre-line">
            {{ errorMessage }}
          </div>
        </div>
        
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
            class="px-5 py-2.5 bg-pink-600 text-white rounded-xl font-bold hover:bg-pink-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <i *ngIf="saving" class="pi pi-spin pi-spinner"></i>
            {{ modalMode === 'add' ? 'Créer' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>
  `
})
export class MpClassesComponent implements OnInit {
  private academicService = inject(AcademicService);

  classes: any[] = [];
  currentSchoolYearId: string | null = null;
  
  showModal = false;
  modalMode: 'add' | 'edit' = 'add';
  saving = false;
  errorMessage = '';
  selectedClass: any = null;
  
  formData = {
    nom: '',
    niveau: 'CP',
    capacity: 40
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
          console.log('MP loaded year:', year.name);
        }
      },
      error: (err) => console.error('Error loading year:', err)
    });
  }

  loadClasses() {
    this.classService.getClassesMP().subscribe({
      next: (data) => {
        const rawData = Array.isArray(data) ? data : (data.data || []);
        this.classes = rawData.map((c: any) => ({
            ...c,
            name: c.nom || c.name,
            level: c.niveau || c.level,
            capacity: c.seuil_maximum || c.capacity || 40
        }));
      },
      error: (err) => console.error('Erreur chargement classes MP', err)
    });
  }

  openModal(mode: 'add' | 'edit', cls?: any) {
    this.modalMode = mode;
    this.errorMessage = '';
    this.saving = false;
    
    if (mode === 'edit' && cls) {
      this.selectedClass = cls;
      this.formData = {
        nom: cls.name || cls.nom,
        niveau: cls.level || cls.niveau,
        capacity: cls.capacity || 40
      };
    } else {
      this.selectedClass = null;
      this.formData = {
        nom: '',
        niveau: 'CP',
        capacity: 40
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
        seuil_maximum: this.formData.capacity
      };

      this.classService.createClass('mp', data)
        .pipe(finalize(() => this.saving = false))
        .subscribe({
          next: () => {
            this.closeModal();
            this.loadClasses();
          },
          error: (err) => {
            console.error("Create class error:", err);
            if (err.error && err.error.errors) {
               // Detailed Laravel errors
               this.errorMessage = Object.values(err.error.errors).flat().join('\\n');
            } else {
               this.errorMessage = err.error?.message || 'Erreur lors de la création.';
            }
          }
        });
    } else {
      this.classService.updateClass('mp', this.selectedClass.id, {
        ...this.selectedClass,
        nom: this.formData.nom
      })
      .pipe(finalize(() => this.saving = false))
      .subscribe({
        next: () => {
          this.closeModal();
          this.loadClasses();
        },
        error: (err) => {
          this.errorMessage = err.error?.message || 'Erreur lors de la modification.';
        }
      });
    }
  }

  deleteClass(cls: any) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer la classe "\${cls.name}" ?`)) {
      this.classService.deleteClass('mp', cls.id).subscribe({
        next: () => this.loadClasses(),
        error: (err) => alert('Erreur: ' + (err.error?.message || err.message))
      });
    }
  }

  getLevelLabel(level: string): string {
    const labels: {[key: string]: string} = {
        'PS': 'Petite Section',
        'MS': 'Moyenne Section',
        'GS': 'Grande Section',
        'CP': 'Cours Préparatoire',
        'CE1': 'Cours Élémentaire 1',
        'CE2': 'Cours Élémentaire 2',
        'CM1': 'Cours Moyen 1',
        'CM2': 'Cours Moyen 2'
    };
    return labels[level] || level;
  }

  getHeaderColor(level: string): string {
    if (['PS', 'MS', 'GS'].includes(level)) return 'bg-pink-400';
    if (['CP', 'CE1'].includes(level)) return 'bg-blue-400';
    if (['CE2'].includes(level)) return 'bg-green-400';
    if (['CM1', 'CM2'].includes(level)) return 'bg-yellow-400';
    return 'bg-gray-400';
  }
}
