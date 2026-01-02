import { Component, signal, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { RouterLink } from '@angular/router';
import { finalize } from 'rxjs/operators';

import { ClassService } from '../../../core/services/class.service';
import { AcademicService } from '../../../core/services/academic.service';

@Component({
  selector: 'app-admin-academic',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  template: `
    <div class="space-y-8">
      <!-- Header -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Gestion Académique</h1>
          <p class="text-gray-500 mt-1">Structure pédagogique, cycles et classes de l'établissement</p>
        </div>
        <button (click)="newSchoolYear()" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 flex items-center gap-2">
          <i class="pi pi-plus"></i>
          <span>Nouvelle Année Scolaire</span>
        </button>
      </div>

      <!-- Années Scolaires -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-4 flex justify-between items-center">
          <div>
            <h2 class="text-lg font-bold text-white">Années Scolaires</h2>
            <p class="text-indigo-100 text-sm">Gérer les périodes académiques</p>
          </div>
          <button (click)="newSchoolYear()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-bold transition flex items-center gap-2">
            <i class="pi pi-plus"></i> Nouvelle Année
          </button>
        </div>
        
        <div class="p-4">
          <div *ngIf="loadingYears" class="text-center py-8 text-gray-500">
            <i class="pi pi-spin pi-spinner text-2xl"></i>
            <p class="mt-2">Chargement des années...</p>
          </div>
          
          <div *ngIf="!loadingYears && schoolYears().length === 0" class="text-center py-8 text-gray-500">
            <i class="pi pi-calendar text-4xl text-gray-300"></i>
            <p class="mt-2">Aucune année scolaire créée</p>
            <button (click)="newSchoolYear()" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold">
              Créer la première année
            </button>
          </div>
          
          <div *ngIf="!loadingYears && schoolYears().length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div *ngFor="let year of schoolYears()" 
                 class="p-4 rounded-xl border-2 transition-all"
                 [ngClass]="year.is_current ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-indigo-300'">
              <div class="flex justify-between items-start">
                <div>
                  <h3 class="font-black text-lg" [ngClass]="year.is_current ? 'text-green-700' : 'text-gray-900'">{{ year.name }}</h3>
                  <p class="text-xs text-gray-500 mt-1">{{ year.start_date | date:'dd/MM/yyyy' }} - {{ year.end_date | date:'dd/MM/yyyy' }}</p>
                </div>
                <span *ngIf="year.is_current" class="bg-green-500 text-white px-2 py-1 rounded text-[10px] font-black uppercase">
                  Courante
                </span>
              </div>
              <div class="mt-3 flex gap-2">
                <button *ngIf="!year.is_current" (click)="setAsCurrent(year)" 
                        class="flex-1 py-1.5 text-xs font-bold bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 transition">
                  Définir courante
                </button>
                <button *ngIf="year.is_current" class="flex-1 py-1.5 text-xs font-bold bg-green-100 text-green-700 rounded-lg cursor-default">
                  ✓ Année active
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cycles & Niveaux -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Maternelle & Primaire -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="bg-pink-50/50 p-4 border-b border-pink-100 flex justify-between items-center">
            <h3 class="font-bold text-pink-700">Maternelle & Primaire</h3>
            <span class="bg-pink-100 text-pink-700 px-2 py-1 rounded text-[10px] font-black uppercase tracking-wider">MP</span>
          </div>
          <div class="p-4">
             <div class="grid grid-cols-2 gap-2">
                <div *ngFor="let lvl of ['PS', 'MS', 'GS', 'CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2']" 
                     (click)="filterByLevel(lvl)"
                     class="p-2 bg-gray-50 rounded-lg text-xs font-bold text-gray-600 border border-transparent hover:border-pink-200 hover:bg-pink-50 transition-colors text-center cursor-pointer"
                     [class.bg-pink-100]="selectedLevel() === lvl">
                  {{ lvl }}
                </div>
             </div>
             <button routerLink="/admin/mp/classes" class="w-full mt-4 py-2 bg-pink-600 text-white rounded-xl text-xs font-bold hover:bg-pink-700 transition">
               Gérer les classes MP →
             </button>
          </div>
        </div>

        <!-- Collège -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="bg-blue-50/50 p-4 border-b border-blue-100 flex justify-between items-center">
            <h3 class="font-bold text-blue-700">Premier Cycle</h3>
            <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-[10px] font-black uppercase tracking-wider">Collège</span>
          </div>
          <div class="p-4">
             <div class="grid grid-cols-2 gap-2">
                <div *ngFor="let lvl of ['6ème', '5ème', '4ème', '3ème']" 
                     (click)="filterByLevel(lvl)"
                     class="p-3 bg-gray-50 rounded-lg text-sm font-bold text-gray-600 border border-transparent hover:border-blue-200 hover:bg-blue-50 transition-colors text-center cursor-pointer"
                     [class.bg-blue-100]="selectedLevel() === lvl">
                  {{ lvl }}
                </div>
             </div>
             <button routerLink="/admin/college/classes" class="w-full mt-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition">
               Gérer les classes Collège →
             </button>
          </div>
        </div>

        <!-- Lycée -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="bg-indigo-50/50 p-4 border-b border-indigo-100 flex justify-between items-center">
            <h3 class="font-bold text-indigo-700">Second Cycle</h3>
            <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded text-[10px] font-black uppercase tracking-wider">Lycée</span>
          </div>
          <div class="p-4">
             <div class="grid grid-cols-2 gap-2">
                <div *ngFor="let lvl of ['2nde', '1ère', 'Terminale']" 
                     (click)="filterByLevel(lvl)"
                     class="p-3 bg-gray-50 rounded-lg text-sm font-bold text-gray-600 border border-transparent hover:border-indigo-200 hover:bg-indigo-50 transition-colors text-center cursor-pointer"
                     [class.bg-indigo-100]="selectedLevel() === lvl">
                  {{ lvl }}
                </div>
             </div>
             <button routerLink="/admin/lycee/classes" class="w-full mt-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 transition">
               Gérer les classes Lycée →
             </button>
          </div>
        </div>
      </div>

      <!-- Liste des Classes -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/30">
          <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
               <h2 class="text-xl font-bold text-gray-900">Liste des Classes ({{ getCurrentYearName() }})</h2>
               <button *ngIf="selectedLevel()" (click)="clearFilter()" class="bg-gray-200 text-gray-700 px-2 py-1 rounded text-xs hover:bg-gray-300">
                  <i class="pi pi-times mr-1"></i> {{ selectedLevel() }}
               </button>
            </div>
            <p class="text-sm text-gray-500">Vue consolidée de toutes les classes actives</p>
          </div>
          <div class="flex gap-3">
            <button (click)="openModal('add')" class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-emerald-700 transition shadow-lg shadow-emerald-100 flex items-center gap-2">
              <i class="pi pi-plus"></i>
              <span>Ajouter une classe</span>
            </button>
          </div>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-gray-50 text-gray-400 text-[10px] font-black uppercase tracking-widest">
              <tr>
                <th class="px-6 py-4">Nom de la salle</th>
                <th class="px-6 py-4 text-center">Cycle</th>
                <th class="px-6 py-4">Niveau</th>
                <th class="px-6 py-4 text-center">Inscrits</th>
                <th class="px-6 py-4 text-center">Capacité</th>
                <th class="px-6 py-4">Status</th>
                <th class="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr *ngFor="let classroom of filteredClassrooms()" class="hover:bg-indigo-50/30 transition-colors group">
                <td class="px-6 py-4">
                  <span class="font-black text-gray-900 text-lg">{{ classroom.name }}</span>
                </td>
                <td class="px-6 py-4 text-center">
                  <span class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-tighter" [ngClass]="getCycleClass(classroom.cycle)">
                    {{ classroom.cycle }}
                  </span>
                </td>
                <td class="px-6 py-4 font-bold text-gray-600">{{ classroom.level }}</td>
                <td class="px-6 py-4 text-center">
                  <div class="flex flex-col items-center">
                    <span class="font-black text-indigo-600">{{ classroom.students }}</span>
                    <div class="w-12 h-1.5 bg-gray-100 rounded-full mt-1 overflow-hidden">
                       <div class="h-full bg-indigo-500 rounded-full" [style.width.%]="(classroom.students/classroom.capacity)*100"></div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-center font-bold text-gray-400">{{ classroom.capacity }}</td>
                <td class="px-6 py-4">
                   <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                     <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                   </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button (click)="openModal('edit', classroom)" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Modifier la salle">
                      <i class="pi pi-pencil"></i>
                    </button>
                    <button (click)="deleteClass(classroom)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Supprimer la salle">
                      <i class="pi pi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div *ngIf="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="closeModal()">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" (click)="$event.stopPropagation()">
        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4">
          <h2 class="text-xl font-bold text-white">
            {{ modalMode === 'add' ? 'Nouvelle Classe' : 'Modifier la Classe' }}
          </h2>
          <p class="text-emerald-100 text-sm">{{ modalMode === 'add' ? 'Créer une nouvelle classe' : 'Modifier les informations' }}</p>
        </div>
        
        <div class="p-6 space-y-4">
          <div *ngIf="modalMode === 'add'">
            <label class="block text-sm font-bold text-gray-700 mb-2">Cycle *</label>
            <select [(ngModel)]="formData.cycle" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition bg-white">
              <option value="mp">Maternelle & Primaire</option>
              <option value="college">Collège</option>
              <option value="lycee">Lycée</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Nom de la classe *</label>
            <input type="text" [(ngModel)]="formData.nom" placeholder="Ex: 6ème A" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
          </div>
          
          <div *ngIf="modalMode === 'add'">
            <label class="block text-sm font-bold text-gray-700 mb-2">Niveau *</label>
            <select [(ngModel)]="formData.niveau" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition bg-white">
              <option *ngIf="formData.cycle === 'mp'" value="PS">Petite Section</option>
              <option *ngIf="formData.cycle === 'mp'" value="MS">Moyenne Section</option>
              <option *ngIf="formData.cycle === 'mp'" value="GS">Grande Section</option>
              <option *ngIf="formData.cycle === 'mp'" value="CP">CP</option>
              <option *ngIf="formData.cycle === 'mp'" value="CE1">CE1</option>
              <option *ngIf="formData.cycle === 'mp'" value="CE2">CE2</option>
              <option *ngIf="formData.cycle === 'mp'" value="CM1">CM1</option>
              <option *ngIf="formData.cycle === 'mp'" value="CM2">CM2</option>
              <option *ngIf="formData.cycle === 'college'" value="6eme">6ème</option>
              <option *ngIf="formData.cycle === 'college'" value="5eme">5ème</option>
              <option *ngIf="formData.cycle === 'college'" value="4eme">4ème</option>
              <option *ngIf="formData.cycle === 'college'" value="3eme">3ème</option>
              <option *ngIf="formData.cycle === 'lycee'" value="2nde">2nde</option>
              <option *ngIf="formData.cycle === 'lycee'" value="1ere">1ère</option>
              <option *ngIf="formData.cycle === 'lycee'" value="Tle">Terminale</option>
            </select>
          </div>

          <!-- Série (Lycée uniquement) -->
          <div *ngIf="modalMode === 'add' && formData.cycle === 'lycee' && formData.niveau !== '2nde'">
            <label class="block text-sm font-bold text-gray-700 mb-2">Série</label>
            <select [(ngModel)]="formData.serie" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition bg-white">
              <option value="">Aucune</option>
              <option value="A">A (Littéraire)</option>
              <option value="C">C (Mathématiques)</option>
              <option value="D">D (Scientifique)</option>
              <option value="E">E (Technique)</option>
            </select>
          </div>

          <div *ngIf="errorMessage" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm whitespace-pre-line">
            {{ errorMessage }}
          </div>
        </div>
        
        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
          <button (click)="closeModal()" class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-100 transition">Annuler</button>
          <button (click)="saveClass()" [disabled]="saving" class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 transition disabled:opacity-50 flex items-center gap-2">
            <i *ngIf="saving" class="pi pi-spin pi-spinner"></i>
            {{ modalMode === 'add' ? 'Créer' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>
    <!-- New Year Modal -->
    <div *ngIf="showYearModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="closeYearModal()">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" (click)="$event.stopPropagation()">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
          <h2 class="text-xl font-bold text-white">Nouvelle Année Scolaire</h2>
          <p class="text-indigo-100 text-sm">Créer une nouvelle période académique</p>
        </div>
        
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Nom *</label>
            <input type="text" [(ngModel)]="yearData.name" placeholder="Ex: 2025-2026" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 transition">
          </div>
          
          <div class="grid grid-cols-2 gap-4">
             <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Début *</label>
                <input type="date" [(ngModel)]="yearData.start_date" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 transition">
             </div>
             <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Fin *</label>
                <input type="date" [(ngModel)]="yearData.end_date" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 transition">
             </div>
          </div>

          <div class="flex items-center gap-2 mt-2">
             <input type="checkbox" id="is_current" [(ngModel)]="yearData.is_current" class="w-5 h-5 rounded text-indigo-600 focus:ring-indigo-500">
             <label for="is_current" class="text-sm text-gray-700">Définir comme année courante</label>
          </div>

          <div *ngIf="errorMessage" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
            {{ errorMessage }}
          </div>
        </div>
        
        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
          <button (click)="closeYearModal()" class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-100 transition">Annuler</button>
          <button (click)="saveYear()" [disabled]="saving" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition disabled:opacity-50 flex items-center gap-2">
            <i *ngIf="saving" class="pi pi-spin pi-spinner"></i>
            Créer
          </button>
        </div>
      </div>
    </div>
  `
})
export class AdminAcademicComponent implements OnInit {
  private classService = inject(ClassService);
  private academicService = inject(AcademicService);
  private http = inject(HttpClient);
  
  classrooms = signal<any[]>([]);
  selectedLevel = signal<string | null>(null);
  currentSchoolYearId: string | null = null;
  
  // School Years
  schoolYears = signal<any[]>([]);
  loadingYears = false;
  
  // Modal state
  showModal = false;
  modalMode: 'add' | 'edit' = 'add';
  saving = false;
  errorMessage = '';
  selectedClass: any = null;
  
  formData = {
    cycle: 'mp' as 'mp' | 'college' | 'lycee',
    nom: '',
    niveau: 'CP',
    serie: ''
  };

  // Year Modal
  showYearModal = false;
  yearData = {
    name: '',
    start_date: '',
    end_date: '',
    is_current: false
  };

  ngOnInit() {
    this.loadSchoolYears();
    this.loadClasses();
  }

  loadSchoolYears() {
    this.loadingYears = true;
    this.academicService.getAcademicYears().subscribe({
      next: (years) => {
        this.schoolYears.set(years || []);
        const current = years?.find((y: any) => y.is_current);
        if (current) {
          this.currentSchoolYearId = current.id;
          console.log('Current school year loaded:', current.name, current.id);
        } else {
          // No current year found in list, try loading separately
          this.loadCurrentSchoolYear();
        }
        this.loadingYears = false;
      },
      error: (err) => {
        console.error('Error loading school years:', err);
        this.loadingYears = false;
        // Still try to load current year for class operations
        this.loadCurrentSchoolYear();
      }
    });
  }

  setAsCurrent(year: any) {
    if (!year?.id) return;
    this.academicService.setCurrentYear(year.id).subscribe({
      next: () => {
        this.currentSchoolYearId = year.id; // Update immediately
        alert('Année ' + year.name + ' définie comme courante !');
        this.loadSchoolYears();
      },
      error: (err) => {
        alert('Erreur: ' + (err.error?.message || err.message));
      }
    });
  }

  loadCurrentSchoolYear() {
    this.academicService.getCurrentYear().subscribe({
      next: (year) => {
        if (year?.id) {
          this.currentSchoolYearId = year.id;
          console.log('Current year from API:', year.name, year.id);
        }
      },
      error: (err) => {
        console.error('Error loading current year:', err);
      }
    });
  }

  getCurrentYearName(): string {
    const current = this.schoolYears().find(y => y.is_current);
    return current?.name || 'Non définie';
  }

  loadClasses() {
    this.classService.getClasses('mp').subscribe({
      next: (mpClasses) => {
        const mpData = (mpClasses.data || mpClasses).map((c: any) => ({
          id: c.id,
          name: c.nom || c.name,
          level: c.niveau || c.level,
          cycle: 'mp',
          students: c.effectif_actuel || 0,
          capacity: c.seuil_maximum || c.capacity || 40,
          _raw: c
        }));
        
        this.classService.getClasses('college').subscribe({
          next: (colClasses) => {
            const colData = (colClasses.data || colClasses).map((c: any) => ({
              id: c.id,
              name: c.nom || c.name,
              level: c.niveau || c.level,
              cycle: 'college',
              students: c.effectif_actuel || 0,
              capacity: c.seuil_maximum || c.capacity || 40,
              _raw: c
            }));
            
            this.classService.getClasses('lycee').subscribe({
              next: (lyceeClasses) => {
                const lyceeData = (lyceeClasses.data || lyceeClasses).map((c: any) => ({
                  id: c.id,
                  name: c.nom || c.name,
                  level: c.niveau || c.level,
                  cycle: 'lycee',
                  students: c.effectif_actuel || 0,
                  capacity: c.seuil_maximum || c.capacity || 40,
                  _raw: c
                }));
                this.classrooms.set([...mpData, ...colData, ...lyceeData]);
              },
              error: () => this.classrooms.set([...mpData, ...colData])
            });
          },
          error: () => this.classrooms.set(mpData)
        });
      },
      error: () => {
         this.classrooms.set([]);
      }
    });
  }

  filteredClassrooms = () => {
    const level = this.selectedLevel();
    if (!level) return this.classrooms();
    return this.classrooms().filter(c => c.level === level);
  };

  filterByLevel(level: string) {
    this.selectedLevel.set(level);
  }

  clearFilter() {
    this.selectedLevel.set(null);
  }

  getCycleClass(cycle: string) {
    switch (cycle.toLowerCase()) {
      case 'mp': return 'bg-pink-100 text-pink-700';
      case 'college': return 'bg-blue-100 text-blue-700';
      case 'lycee': return 'bg-indigo-100 text-indigo-700';
      default: return 'bg-gray-100 text-gray-700';
    }
  }

  openModal(mode: 'add' | 'edit', classroom?: any) {
    this.modalMode = mode;
    this.errorMessage = '';
    this.saving = false;
    
    if (mode === 'edit' && classroom) {
      this.selectedClass = classroom;
      this.formData = {
        cycle: classroom.cycle,
        nom: classroom.name,
        niveau: classroom.level,
        serie: classroom.serie || ''
      };
    } else {
      this.selectedClass = null;
      this.formData = {
        cycle: 'mp',
        nom: '',
        niveau: 'CP',
        serie: ''
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

      const data: any = {
        nom: this.formData.nom,
        niveau: this.formData.niveau,
        school_year_id: this.currentSchoolYearId,
        seuil_maximum: 40
      };

      if (this.formData.cycle === 'lycee') {
        data.serie = this.formData.serie || null;
      }

      this.classService.createClass(this.formData.cycle, data)
        .pipe(finalize(() => this.saving = false))
        .subscribe({
          next: () => {
            this.closeModal();
            this.loadClasses();
          },
          error: (err) => {
            console.error("Create class error:", err);
            if (err.error && err.error.errors) {
              this.errorMessage = Object.values(err.error.errors).flat().join('\\n');
            } else {
              this.errorMessage = err.error?.message || 'Erreur lors de la création.';
            }
          }
        });
    } else {
      this.classService.updateClass(this.selectedClass.cycle, this.selectedClass.id, {
        ...this.selectedClass._raw,
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

  deleteClass(classroom: any) {
    if (confirm(`Voulez-vous vraiment supprimer la salle \${classroom.name} ?`)) {
      this.classService.deleteClass(classroom.cycle, classroom.id).subscribe({
        next: () => this.loadClasses(),
        error: (err) => alert('Erreur suppression: ' + (err.error?.message || err.message))
      });
    }
  }

  newSchoolYear() {
    this.yearData = { name: '', start_date: '', end_date: '', is_current: false };
    this.showYearModal = true;
    this.errorMessage = '';
  }

  closeYearModal() {
    this.showYearModal = false;
    this.errorMessage = '';
  }

  saveYear() {
    if (!this.yearData.name || !this.yearData.start_date || !this.yearData.end_date) {
        this.errorMessage = 'Tous les champs sont requis.';
        return;
    }
    this.saving = true;
    this.errorMessage = '';
    
    this.academicService.createAcademicYear(this.yearData)
      .pipe(finalize(() => this.saving = false))
      .subscribe({
        next: (year) => {
            alert('Année scolaire ' + (year?.name || this.yearData.name) + ' créée avec succès !');
            this.closeYearModal();
            this.loadSchoolYears(); // Reload all years
        },
        error: (err) => {
            console.error('Error creating year:', err);
            this.errorMessage = err.error?.message || err.message || "Erreur lors de la création de l'année.";
        }
    });
  }
}
