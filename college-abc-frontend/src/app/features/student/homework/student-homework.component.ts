import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-student-homework',
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
          <h1 class="text-2xl font-bold text-gray-800">Mes Devoirs</h1>
          <p class="text-gray-500">Travaux à rendre et exercices</p>
        </div>
        <div class="flex gap-2">
          <select [(ngModel)]="filterSubject" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 bg-white transition cursor-pointer">
            <option value="">Toutes les matières</option>
            <option *ngFor="let s of subjects()" [value]="s">{{ s }}</option>
          </select>
          <select [(ngModel)]="filterStatus" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 bg-white transition cursor-pointer">
            <option value="">Tous les statuts</option>
            <option value="pending">À faire</option>
            <option value="done">Terminé</option>
            <option value="late">En retard</option>
          </select>
        </div>
      </div>

      <!-- Summary -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-orange-500 to-red-500 rounded-xl p-5 text-white shadow-lg shadow-orange-200">
          <p class="text-white/80 text-sm font-medium">À rendre cette semaine</p>
          <p class="text-3xl font-black mt-1">{{ pendingThisWeek() }}</p>
          <i class="pi pi-calendar-times text-2xl absolute right-5 top-5 opacity-20"></i>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-green-500 shadow-sm transition hover:shadow-md">
          <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Terminés ce mois</p>
          <p class="text-3xl font-bold text-gray-800">{{ completedCount() }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-red-500 shadow-sm transition hover:shadow-md">
          <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">En retard</p>
          <p class="text-3xl font-bold text-gray-800">{{ lateCount() }}</p>
        </div>
      </div>

      <!-- Homework List -->
      <div class="space-y-4">
        <div *ngFor="let hw of filteredHomework()" 
             class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transition hover:shadow-md"
             [ngClass]="{'border-l-4 border-l-red-500': hw.status === 'late'}">
          <div class="p-4 flex items-start gap-4">
            <div class="pt-1">
              <button (click)="toggleDone(hw)" 
                      class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors duration-200"
                      [ngClass]="hw.status === 'done' ? 'bg-green-500 border-green-500 text-white' : 'border-gray-300 hover:border-green-500'">
                <i *ngIf="hw.status === 'done'" class="pi pi-check text-[10px] font-bold"></i>
              </button>
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 text-xs font-bold rounded-full uppercase tracking-wide"
                      [style.background-color]="getSubjectColor(hw.subject) + '20'"
                      [style.color]="getSubjectColor(hw.subject)">
                  {{ hw.subject }}
                </span>
                <span *ngIf="hw.status === 'late'" class="px-2 py-0.5 text-xs font-bold bg-red-100 text-red-600 rounded-full uppercase tracking-wide flex items-center gap-1">
                  <i class="pi pi-exclamation-circle text-[10px]"></i> En retard
                </span>
              </div>
              <h3 class="font-bold text-gray-800 text-lg transition" [ngClass]="{'line-through text-gray-400': hw.status === 'done'}">
                {{ hw.title }}
              </h3>
              <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ hw.description }}</p>
              <div class="flex items-center gap-4 mt-3 text-xs font-medium text-gray-500">
                <span class="flex items-center gap-1"><i class="pi pi-calendar"></i> {{ hw.dueDate }}</span>
                <span *ngIf="hw.attachments" class="flex items-center gap-1"><i class="pi pi-paperclip"></i> {{ hw.attachments }} fichier(s)</span>
              </div>
            </div>
            <div class="flex flex-col gap-2">
              <button *ngIf="hw.status !== 'done'" (click)="submitHomework(hw)" 
                      class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-bold hover:bg-purple-700 transition shadow-sm flex items-center gap-2">
                <i class="pi pi-upload"></i> Rendre
              </button>
              <button (click)="viewDetails(hw)" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-bold text-gray-600 hover:bg-gray-50 transition">
                Détails
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Submit Modal -->
      <div *ngIf="showSubmitModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showSubmitModal = false">
        <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden" (click)="$event.stopPropagation()">
          <div class="bg-purple-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">Rendre le devoir</h3>
            <button (click)="showSubmitModal = false" class="text-white/80 hover:text-white transition"><i class="pi pi-times"></i></button>
          </div>
          <form (ngSubmit)="confirmSubmit()" class="p-6 space-y-4">
            <div class="bg-purple-50 p-4 rounded-xl border border-purple-100">
              <div class="font-bold text-purple-800">{{ selectedHomework?.title }}</div>
              <div class="text-xs font-bold uppercase tracking-wide text-purple-600 mt-1">{{ selectedHomework?.subject }}</div>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Fichiers à joindre</label>
              <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-purple-500 hover:bg-purple-50 transition cursor-pointer group">
                <i class="pi pi-cloud-upload text-3xl text-gray-400 mb-2 group-hover:text-purple-500 transition"></i>
                <p class="text-sm text-gray-500 group-hover:text-purple-700 font-medium">Glissez vos fichiers ici ou</p>
                <input type="file" multiple class="hidden" id="fileInput">
                <label for="fileInput" class="text-purple-600 font-bold hover:underline cursor-pointer">parcourez</label>
              </div>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Commentaire (optionnel)</label>
              <textarea [(ngModel)]="submitComment" name="comment" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 transition" placeholder="Ajouter une note..."></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 bg-gray-50 -mx-6 -mb-6 px-6 py-4 mt-2">
              <button type="button" (click)="showSubmitModal = false" class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-100 transition">Annuler</button>
              <button type="submit" class="px-5 py-2.5 bg-purple-600 text-white rounded-xl font-bold hover:bg-purple-700 transition shadow-lg shadow-purple-200">Soumettre</button>
            </div>
          </form>
        </div>
      </div>

       <!-- Details Modal -->
      <div *ngIf="showDetailsModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showDetailsModal = false">
        <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl overflow-hidden" (click)="$event.stopPropagation()">
            <div class="bg-white px-6 py-4 border-b border-gray-100 flex justify-between items-start">
                <div>
                     <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide mb-2"
                        [style.background-color]="getSubjectColor(selectedHomework?.subject) + '20'"
                        [style.color]="getSubjectColor(selectedHomework?.subject)">
                    {{ selectedHomework?.subject }}
                    </span>
                    <h3 class="text-xl font-bold text-gray-900">{{ selectedHomework?.title }}</h3>
                </div>
                <button (click)="showDetailsModal = false" class="text-gray-400 hover:text-gray-600 transition bg-gray-100 rounded-full w-8 h-8 flex items-center justify-center"><i class="pi pi-times"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <h4 class="text-sm font-bold text-gray-700 mb-1 uppercase tracking-wide">Description</h4>
                    <p class="text-gray-600 leading-relaxed bg-gray-50 p-4 rounded-xl border border-gray-100">{{ selectedHomework?.description }}</p>
                </div>
                <div>
                     <h4 class="text-sm font-bold text-gray-700 mb-1 uppercase tracking-wide">Date limite</h4>
                     <p class="text-gray-800 font-medium flex items-center gap-2"><i class="pi pi-calendar text-gray-400"></i> {{ selectedHomework?.dueDate }}</p>
                </div>
                <div *ngIf="selectedHomework?.attachments > 0">
                     <h4 class="text-sm font-bold text-gray-700 mb-1 uppercase tracking-wide">Pièces jointes</h4>
                     <div class="flex items-center gap-2 text-blue-600 font-medium cursor-pointer hover:underline">
                         <i class="pi pi-paperclip"></i> Voir {{ selectedHomework?.attachments }} fichier(s)
                     </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button (click)="showDetailsModal = false" class="px-5 py-2.5 bg-gray-800 text-white rounded-xl font-bold hover:bg-gray-700 transition">Fermer</button>
            </div>
        </div>
      </div>

    </div>
  `
})
export class StudentHomeworkComponent {
  filterSubject = '';
  filterStatus = '';
  
  showSubmitModal = false;
  showDetailsModal = false;
  showSuccessToast = false;
  successMessage = '';

  selectedHomework: any = null;
  submitComment = '';

  subjects = signal(['Mathématiques', 'Français', 'Histoire-Géo', 'SVT', 'Physique-Chimie', 'Anglais']);

  subjectColors: Record<string, string> = {
    'Mathématiques': '#4F46E5',
    'Français': '#DC2626',
    'Histoire-Géo': '#059669',
    'SVT': '#16A34A',
    'Physique-Chimie': '#D97706',
    'Anglais': '#7C3AED'
  };

  homework = signal([
    { id: 1, subject: 'Mathématiques', title: 'Exercices sur les fractions', description: 'Faire les exercices 1 à 10 page 47 du manuel.\nAttention à bien simplifier les fractions finales.', dueDate: '30/12/2024', status: 'pending', attachments: 1 },
    { id: 2, subject: 'Français', title: 'Rédaction', description: 'Écrire une rédaction de 300 mots sur le thème "Mon héros".\nRespecter la structure introduction, développement, conclusion.', dueDate: '28/12/2024', status: 'pending', attachments: 0 },
    { id: 3, subject: 'Histoire-Géo', title: 'Carte de l\'Afrique', description: 'Compléter la carte muette avec les pays et capitales.\nUtiliser des couleurs différentes pour les zones climatiques.', dueDate: '20/12/2024', status: 'late', attachments: 1 },
    { id: 4, subject: 'SVT', title: 'Schéma cellule', description: 'Dessiner et légender une cellule végétale observée au microscope.', dueDate: '18/12/2024', status: 'done', attachments: 0 },
  ]);

  pendingThisWeek = signal(2);
  completedCount = signal(8);
  lateCount = signal(1);

  filteredHomework = () => {
    let result = this.homework();
    if (this.filterSubject) result = result.filter(h => h.subject === this.filterSubject);
    if (this.filterStatus) result = result.filter(h => h.status === this.filterStatus);
    return result;
  };

  getSubjectColor(subject: string) { return this.subjectColors[subject] || '#6B7280'; }

  toggleDone(hw: any) {
    this.homework.update(list => list.map(h => h.id === hw.id ? { ...h, status: h.status === 'done' ? 'pending' : 'done' } : h));
  }

  submitHomework(hw: any) {
    this.selectedHomework = hw;
    this.showSubmitModal = true;
  }

  confirmSubmit() {
    this.homework.update(list => list.map(h => h.id === this.selectedHomework.id ? { ...h, status: 'done' } : h));
    this.showToast('Devoir soumis avec succès !');
    this.showSubmitModal = false;
  }

  viewDetails(hw: any) { 
      this.selectedHomework = hw;
      this.showDetailsModal = true;
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
