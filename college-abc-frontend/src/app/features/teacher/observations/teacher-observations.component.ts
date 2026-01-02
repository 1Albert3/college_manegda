import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { TeacherService } from '../../../core/services/teacher.service';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-teacher-observations',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6 relative">
      <!-- Toast Notification -->
      <div *ngIf="showSuccessToast" class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-xl z-[100] flex items-center gap-3 transition-opacity duration-300">
        <i class="pi pi-check-circle text-xl"></i>
        <span class="font-medium">{{ successMessage }}</span>
      </div>

      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Observations</h1>
          <p class="text-gray-500">Notes et remarques sur les élèves</p>
        </div>
        <button (click)="openModal('create')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-sm flex items-center gap-2">
          <i class="pi pi-plus"></i>Nouvelle observation
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl p-4 flex flex-wrap gap-4 border border-gray-100 shadow-sm">
        <select [(ngModel)]="filterClass" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
          <option value="">Toutes les classes</option>
          <option *ngFor="let c of classes()" [value]="c">{{ c }}</option>
        </select>
        <select [(ngModel)]="filterType" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
          <option value="">Tous les types</option>
          <option value="positive">Positive</option>
          <option value="warning">Avertissement</option>
          <option value="negative">Négative</option>
        </select>
        <input type="text" [(ngModel)]="searchQuery" placeholder="Rechercher un élève..." 
               class="px-4 py-2 border border-gray-300 rounded-lg flex-1 min-w-[200px] focus:ring-2 focus:ring-indigo-500">
      </div>

      <!-- Observations List -->
      <div class="space-y-4">
        <div *ngIf="loading" class="text-center py-8 text-gray-500">
            <i class="pi pi-spin pi-spinner text-2xl mb-2"></i><br>Chargement...
        </div>
        <div *ngIf="!loading && filteredObservations().length === 0" class="text-center py-8 text-gray-500 italic">
            Aucune observation trouvée.
        </div>

        <div *ngFor="let obs of filteredObservations()" 
             class="bg-white rounded-xl shadow-sm p-5 border-l-4 transition hover:shadow-md"
             [ngClass]="{
               'border-green-500': obs.type === 'positive',
               'border-orange-500': obs.type === 'warning',
               'border-red-500': obs.type === 'negative'
             }">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-sm shrink-0"
                 [ngClass]="{
                   'bg-green-100 text-green-600': obs.type === 'positive',
                   'bg-orange-100 text-orange-600': obs.type === 'warning',
                   'bg-red-100 text-red-600': obs.type === 'negative'
                 }">
              <i [class]="getTypeIcon(obs.type)"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1 flex-wrap">
                <span class="font-bold text-gray-800 text-lg">{{ obs.student }}</span>
                <span class="text-sm text-gray-500 font-medium">• {{ obs.class }}</span>
                <span class="px-2 py-0.5 text-xs rounded-full font-bold uppercase tracking-wider"
                      [ngClass]="{
                        'bg-green-100 text-green-700': obs.type === 'positive',
                        'bg-orange-100 text-orange-700': obs.type === 'warning',
                        'bg-red-100 text-red-700': obs.type === 'negative'
                      }">
                  {{ getTypeLabel(obs.type) }}
                </span>
              </div>
              <p class="text-gray-700 leading-relaxed">{{ obs.content }}</p>
              <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
                <span class="flex items-center gap-1"><i class="pi pi-calendar"></i>{{ obs.date }}</span>
                <span *ngIf="obs.notifyParent" class="flex items-center gap-1 text-indigo-600 font-medium"><i class="pi pi-send"></i>Parent notifié</span>
              </div>
            </div>
            <div class="flex gap-2">
              <button (click)="openModal('edit', obs)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Modifier">
                <i class="pi pi-pencil"></i>
              </button>
              <button (click)="confirmDelete(obs)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Supprimer">
                <i class="pi pi-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Observation Modal -->
      <div *ngIf="showNewObservation" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="closeModal()">
        <div class="bg-white rounded-2xl w-full max-w-lg mx-4 overflow-hidden shadow-2xl" (click)="$event.stopPropagation()">
          <div class="bg-indigo-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">{{ modalMode === 'create' ? 'Nouvelle Observation' : 'Modifier l\\'Observation' }}</h3>
            <button (click)="closeModal()" class="text-white/80 hover:text-white transition"><i class="pi pi-times text-lg"></i></button>
          </div>
          <form (ngSubmit)="saveObservation()" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Classe</label>
              <select [(ngModel)]="newObs.class" (change)="onClassChange()" name="class" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
                 <option value="">Sélectionner une classe</option>
                 <option *ngFor="let c of classes()" [value]="c">{{ c }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Élève</label>
              <select [disabled]="!newObs.class" [(ngModel)]="newObs.studentId" name="student" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white">
                <option [ngValue]="null">Sélectionner un élève</option>
                <option *ngFor="let s of getStudentsForClass(newObs.class)" [ngValue]="s.id">{{ s.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Type d'observation</label>
              <div class="grid grid-cols-3 gap-3">
                <label class="flex flex-col items-center justify-center gap-2 cursor-pointer p-3 rounded-xl border border-gray-200 transition hover:bg-green-50"
                       [class.bg-green-50]="newObs.type === 'positive'"
                       [class.border-green-200]="newObs.type === 'positive'">
                  <input type="radio" [(ngModel)]="newObs.type" name="type" value="positive" class="hidden">
                  <i class="pi pi-star text-2xl text-green-500"></i>
                  <span class="text-xs font-bold text-green-700">Positive</span>
                </label>
                <label class="flex flex-col items-center justify-center gap-2 cursor-pointer p-3 rounded-xl border border-gray-200 transition hover:bg-orange-50"
                       [class.bg-orange-50]="newObs.type === 'warning'"
                       [class.border-orange-200]="newObs.type === 'warning'">
                  <input type="radio" [(ngModel)]="newObs.type" name="type" value="warning" class="hidden">
                  <i class="pi pi-exclamation-triangle text-2xl text-orange-500"></i>
                  <span class="text-xs font-bold text-orange-700">Avertissement</span>
                </label>
                <label class="flex flex-col items-center justify-center gap-2 cursor-pointer p-3 rounded-xl border border-gray-200 transition hover:bg-red-50"
                       [class.bg-red-50]="newObs.type === 'negative'"
                       [class.border-red-200]="newObs.type === 'negative'">
                  <input type="radio" [(ngModel)]="newObs.type" name="type" value="negative" class="hidden">
                  <i class="pi pi-times-circle text-2xl text-red-500"></i>
                  <span class="text-xs font-bold text-red-700">Négative</span>
                </label>
              </div>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Contenu</label>
              <textarea [(ngModel)]="newObs.content" name="content" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        placeholder="Décrivez l'observation..."></textarea>
            </div>
            <div>
              <label class="flex items-center gap-3 cursor-pointer p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <input type="checkbox" [(ngModel)]="newObs.notifyParent" name="notify" class="w-5 h-5 rounded text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm font-medium text-gray-700">Notifier les parents par email</span>
              </label>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
              <button type="button" (click)="closeModal()" class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50 transition">Annuler</button>
              <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition flex items-center gap-2">
                <i class="pi pi-check"></i> Enregistrer
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div *ngIf="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showDeleteModal = false">
        <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden" (click)="$event.stopPropagation()">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="pi pi-trash text-3xl text-red-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Supprimer l'observation ?</h3>
                <p class="text-gray-500 mb-6">Cette action est irréversible.</p>
                <div class="flex gap-3 justify-center">
                    <button (click)="showDeleteModal = false" class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50 transition">Annuler</button>
                    <button (click)="deleteObservation()" class="px-5 py-2.5 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 transition">Supprimer</button>
                </div>
            </div>
        </div>
      </div>
    </div>
  `
})
export class TeacherObservationsComponent implements OnInit {
  private teacherService = inject(TeacherService);
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  filterClass = '';
  filterType = '';
  searchQuery = '';
  loading = false;
  
  // UI State
  showNewObservation = false;
  modalMode: 'create' | 'edit' = 'create';
  showDeleteModal = false;
  obsToDelete: any = null;
  showSuccessToast = false;
  successMessage = '';
  editingId: number | null = null;

  newObs = { class: '', studentId: null as any, type: 'positive', content: '', notifyParent: false };

  classes = signal<string[]>([]);
  students = signal<any[]>([]); // { id, name, class }
  observations = signal<any[]>([]);

  ngOnInit() {
      this.loadData();
  }

  loadData() {
      this.loading = true;
      this.teacherService.getDashboard().subscribe({
          next: (data) => {
              if (data.classes) {
                  const classNames = data.classes.map((c: any) => c.nom || c.name);
                  this.classes.set(classNames);
                  
                  // Load students for all active classes
                  const requests = data.classes.map((c: any) => {
                       let endpoint = '/mp/classes';
                       if(c.cycle === 'lycee') endpoint = '/lycee/classes';
                       else if(c.cycle === 'college') endpoint = '/college/classes';
                       
                       return this.http.get<any>(`${this.apiUrl}${endpoint}/${c.id}/students`).toPromise().then(res => {
                           // Handle API response structure: { students: [...] } or { data: [...] }
                           const students = res.students || res.data || [];
                           return students.map((s: any) => ({
                               id: s.id,
                               name: `${s.nom} ${s.prenoms}`,
                               class: c.nom || c.name,
                               cycle: c.cycle
                           }));
                       }).catch(() => []);
                  });

                  Promise.all(requests).then(results => {
                      const allStudents = results.flat();
                      this.students.set(allStudents);
                      this.loading = false;
                  });
              } else {
                  this.loading = false;
              }
          },
          error: (err) => {
              console.error(err);
              this.loading = false;
          }
      });
  }

  getStudentsForClass(className: string) {
      if(!className) return [];
      return this.students().filter(s => s.class === className);
  }
  
  onClassChange() {
      this.newObs.studentId = null;
  }

  filteredObservations = () => {
    let result = this.observations();
    if (this.filterClass) result = result.filter(o => o.class === this.filterClass);
    if (this.filterType) result = result.filter(o => o.type === this.filterType);
    if (this.searchQuery) {
      const q = this.searchQuery.toLowerCase();
      result = result.filter(o => o.student.toLowerCase().includes(q));
    }
    return result;
  };

  getTypeIcon(type: string) {
    return { positive: 'pi pi-star', warning: 'pi pi-exclamation-triangle', negative: 'pi pi-times-circle' }[type] || 'pi pi-circle';
  }

  getTypeLabel(type: string) {
    return { positive: 'Positive', warning: 'Avertissement', negative: 'Négative' }[type] || type;
  }

  openModal(mode: 'create' | 'edit', obs?: any) {
    this.modalMode = mode;
    this.showNewObservation = true;
    
    if (mode === 'edit' && obs) {
      this.editingId = obs.id;
      // Find student and map back properties
      const student = this.students().find(s => s.name === obs.student && s.class === obs.class);
      
      this.newObs = {
        class: obs.class,
        studentId: student ? student.id : null,
        type: obs.type,
        content: obs.content,
        notifyParent: obs.notifyParent
      };
    } else {
      this.editingId = null;
      this.newObs = { class: '', studentId: null, type: 'positive', content: '', notifyParent: false };
    }
  }

  closeModal() {
    this.showNewObservation = false;
  }

  saveObservation() {
    const student = this.students().find(s => s.id === this.newObs.studentId);
    if (!student) {
        return; 
    }

    if (this.modalMode === 'create') {
        const newId = (this.observations().length > 0 ? Math.max(...this.observations().map(o => o.id)) : 0) + 1;
        const newEntry = {
            id: newId,
            student: student.name,
            class: student.class,
            type: this.newObs.type,
            content: this.newObs.content,
            date: new Date().toLocaleDateString('fr-FR'),
            notifyParent: this.newObs.notifyParent
        };
        this.observations.update(obs => [newEntry, ...obs]);
        this.showToast('Observation enregistrée ! (Simulation)');
    } else {
        this.observations.update(obs => obs.map(o => o.id === this.editingId ? {
            ...o,
            student: student.name,
            class: student.class,
            type: this.newObs.type,
            content: this.newObs.content,
            notifyParent: this.newObs.notifyParent
        } : o));
        this.showToast('Observation modifiée avec succès ! (Simulation)');
    }
    this.closeModal();
  }

  confirmDelete(obs: any) {
    this.obsToDelete = obs;
    this.showDeleteModal = true;
  }

  deleteObservation() {
    if (this.obsToDelete) {
        this.observations.update(obs => obs.filter(o => o.id !== this.obsToDelete.id));
        this.showDeleteModal = false;
        this.obsToDelete = null;
        this.showToast('Observation supprimée.');
    }
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
