import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-teacher-homework',
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
          <h1 class="text-2xl font-bold text-gray-800">Cahier de Texte</h1>
          <p class="text-gray-500">Suivi des cours et devoirs</p>
        </div>
        <div class="flex gap-2">
          <select [(ngModel)]="selectedClass" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            <option value="">Toutes les classes</option>
            <option *ngFor="let c of classes()" [value]="c.id">{{ c.name }}</option>
          </select>
          <button (click)="openModal('create')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-sm flex items-center gap-2">
            <i class="pi pi-plus"></i>Nouvelle entrée
          </button>
        </div>
      </div>

      <!-- Entries List -->
      <div class="space-y-4">
        <div *ngFor="let entry of filteredEntries()" class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition">
          <div class="px-6 py-4 border-b flex items-center justify-between"
               [ngClass]="{'bg-indigo-50/50': entry.type === 'lesson', 'bg-orange-50/50': entry.type === 'homework'}">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full flex items-center justify-center shadow-sm"
                   [ngClass]="entry.type === 'lesson' ? 'bg-indigo-100 text-indigo-600' : 'bg-orange-100 text-orange-600'">
                <i [class]="entry.type === 'lesson' ? 'pi pi-book' : 'pi pi-pencil'"></i>
              </div>
              <div>
                <div class="font-bold text-gray-800">{{ entry.subject }}</div>
                <div class="text-sm text-gray-500 font-medium">{{ entry.class }} • {{ entry.date | date:'dd/MM/yyyy' }}</div>
              </div>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
                  [ngClass]="entry.type === 'lesson' ? 'bg-indigo-100 text-indigo-800' : 'bg-orange-100 text-orange-800'">
              {{ entry.type === 'lesson' ? 'Cours' : 'Devoir' }}
            </span>
          </div>
          <div class="p-6">
            <h3 class="font-bold text-lg text-gray-800 mb-2">{{ entry.title }}</h3>
            <p class="text-gray-600 leading-relaxed">{{ entry.content }}</p>
            <div *ngIf="entry.type === 'homework'" class="mt-4 flex items-center gap-4 text-sm bg-orange-50 p-3 rounded-lg border border-orange-100 inline-flex">
              <span class="text-orange-700 font-medium"><i class="pi pi-calendar mr-2"></i>À rendre le {{ entry.dueDate | date:'dd/MM/yyyy' }}</span>
              <span class="text-gray-500 border-l border-orange-200 pl-4"><i class="pi pi-file mr-2"></i>{{ entry.attachments }} pièce(s) jointe(s)</span>
            </div>
          </div>
          <div class="px-6 py-3 bg-gray-50 flex justify-end gap-2 border-t border-gray-100">
            <button (click)="openModal('edit', entry)" class="px-3 py-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg text-sm font-medium transition flex items-center gap-1">
              <i class="pi pi-pencil"></i>Modifier
            </button>
            <button (click)="confirmDelete(entry)" class="px-3 py-1.5 text-red-600 hover:bg-red-50 rounded-lg text-sm font-medium transition flex items-center gap-1">
              <i class="pi pi-trash"></i>Supprimer
            </button>
          </div>
        </div>
      </div>

      <!-- Entry Modal -->
      <div *ngIf="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="closeModal()">
        <div class="bg-white rounded-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto shadow-2xl" (click)="$event.stopPropagation()">
          <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 flex justify-between items-center sticky top-0">
            <h3 class="text-xl font-bold text-white">{{ modalMode === 'create' ? 'Nouvelle Entrée' : 'Modifier l\\'Entrée' }}</h3>
            <button (click)="closeModal()" class="text-white/80 hover:text-white transition"><i class="pi pi-times text-lg"></i></button>
          </div>
          <form (ngSubmit)="saveEntry()" class="p-6 space-y-4">
            <div class="flex gap-4 p-1 bg-gray-100 rounded-lg">
              <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer py-2 rounded-md transition"
                     [ngClass]="formData.type === 'lesson' ? 'bg-white shadow-sm text-indigo-700 font-bold' : 'text-gray-500 hover:bg-gray-200'">
                <input type="radio" [(ngModel)]="formData.type" name="type" value="lesson" class="hidden">
                <i class="pi pi-book"></i>
                <span>Cours</span>
              </label>
              <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer py-2 rounded-md transition"
                     [ngClass]="formData.type === 'homework' ? 'bg-white shadow-sm text-orange-600 font-bold' : 'text-gray-500 hover:bg-gray-200'">
                <input type="radio" [(ngModel)]="formData.type" name="type" value="homework" class="hidden">
                <i class="pi pi-pencil"></i>
                <span>Devoir</span>
              </label>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Classe</label>
                <select [(ngModel)]="formData.classId" name="class" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 transition">
                  <option *ngFor="let c of classes()" [value]="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Date</label>
                <input type="date" [(ngModel)]="formData.date" name="date" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 transition">
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Titre</label>
              <input type="text" [(ngModel)]="formData.title" name="title" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 transition" placeholder="Titre de la leçon ou du devoir">
            </div>
            
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Contenu</label>
              <textarea [(ngModel)]="formData.content" name="content" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 transition" placeholder="Description détaillée..."></textarea>
            </div>
            
            <div *ngIf="formData.type === 'homework'" class="animate-fade-in">
              <label class="block text-sm font-bold text-orange-700 mb-1">Date limite</label>
              <input type="date" [(ngModel)]="formData.dueDate" name="dueDate" class="w-full px-4 py-2 border border-orange-300 rounded-xl focus:ring-2 focus:ring-orange-500 transition bg-orange-50">
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
                    <i class="pi pi-exclamation-triangle text-3xl text-red-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Supprimer l'entrée ?</h3>
                <p class="text-gray-500 mb-6">Cette action est irréversible. Voulez-vous vraiment supprimer cet élément ?</p>
                <div class="flex gap-3 justify-center">
                    <button (click)="showDeleteModal = false" class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50 transition">Annuler</button>
                    <button (click)="deleteEntry()" class="px-5 py-2.5 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 transition">Supprimer</button>
                </div>
            </div>
        </div>
      </div>
    </div>
  `
})
export class TeacherHomeworkComponent {
  selectedClass = '';
  
  // UI State
  showModal = false;
  modalMode: 'create' | 'edit' = 'create';
  showDeleteModal = false;
  entryToDelete: any = null;
  showSuccessToast = false;
  successMessage = '';

  classes = signal([
    { id: '1', name: '6ème A' },
    { id: '2', name: '5ème B' },
    { id: '3', name: '4ème A' },
  ]);

  entries = signal<any[]>([
    { id: 1, type: 'lesson', class: '6ème A', classId: '1', subject: 'Mathématiques', date: '2024-12-23', title: 'Les fractions', content: 'Introduction aux fractions : numérateur et dénominateur. Exercices pages 45-46.', dueDate: '', attachments: 0 },
    { id: 2, type: 'homework', class: '6ème A', classId: '1', subject: 'Mathématiques', date: '2024-12-23', title: 'Exercices sur les fractions', content: 'Faire les exercices 1 à 10 page 47.', dueDate: '2024-12-30', attachments: 1 },
    { id: 3, type: 'lesson', class: '5ème B', classId: '2', subject: 'Mathématiques', date: '2024-12-22', title: 'Équations du 1er degré', content: 'Résolution des équations simples. Méthode de transposition.', dueDate: '', attachments: 2 },
  ]);

  formData = {
    id: null as number | null,
    type: 'lesson',
    classId: '1',
    date: '',
    title: '',
    content: '',
    dueDate: '',
    attachments: 0
  };

  filteredEntries = () => {
    if (!this.selectedClass) return this.entries();
    return this.entries().filter(e => e.classId === this.selectedClass);
  };

  openModal(mode: 'create' | 'edit', entry?: any) {
    this.modalMode = mode;
    this.showModal = true;
    
    if (mode === 'edit' && entry) {
      this.formData = { ...entry };
    } else {
      this.formData = {
        id: null,
        type: 'lesson',
        classId: this.selectedClass || '1',
        date: new Date().toISOString().split('T')[0],
        title: '',
        content: '',
        dueDate: '',
        attachments: 0
      };
    }
  }

  closeModal() {
    this.showModal = false;
  }

  saveEntry() {
    console.log('Saving entry:', this.formData);
    
    if (this.modalMode === 'create') {
        const newId = Math.max(...this.entries().map(e => Number(e.id)), 0) + 1;
        this.entries.update(curr => [
            { 
               ...this.formData, 
               id: newId, 
               class: this.getClassName(this.formData.classId),
               subject: 'Mathématiques' // Default subject for mock
            },
            ...curr
        ]);
        this.showToast('Entrée créée avec succès !');
    } else {
        this.entries.update(curr => curr.map(e => e.id === this.formData.id ? { 
            ...e, 
            ...this.formData, 
            class: this.getClassName(this.formData.classId) 
        } : e));
        this.showToast('Entrée modifiée avec succès !');
    }
    
    this.showModal = false;
  }

  confirmDelete(entry: any) {
    this.entryToDelete = entry;
    this.showDeleteModal = true;
  }

  deleteEntry() {
    if (this.entryToDelete) {
      console.log('Deleting:', this.entryToDelete);
      this.entries.update(curr => curr.filter(e => e.id !== this.entryToDelete.id));
      this.showDeleteModal = false;
      this.entryToDelete = null;
      this.showToast('Entrée supprimée avec succès !');
    }
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }

  private getClassName(id: string) {
    return this.classes().find(c => c.id === id)?.name || '';
  }
}
