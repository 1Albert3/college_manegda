import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-parent-homework',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6 relative">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Devoirs & Exercices</h1>
          <p class="text-gray-500">Suivi des travaux scolaires</p>
        </div>
        <div class="flex gap-2">
          <select [(ngModel)]="selectedChild" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 bg-white transition cursor-pointer">
            <option *ngFor="let child of children()" [value]="child.id">{{ child.name }}</option>
          </select>
          <select [(ngModel)]="filterSubject" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 bg-white transition cursor-pointer">
            <option value="">Toutes les matières</option>
            <option *ngFor="let s of subjects()" [value]="s">{{ s }}</option>
          </select>
        </div>
      </div>

      <!-- Summary -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-orange-500 to-red-500 rounded-xl p-5 text-white shadow-lg shadow-orange-200">
          <p class="text-white/80 text-sm font-medium">À rendre cette semaine</p>
          <p class="text-3xl font-black mt-1">{{ pendingThisWeek() }}</p>
          <i class="pi pi-calendar-times absolute right-5 top-5 text-2xl opacity-20"></i>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-green-500 shadow-sm transition hover:shadow-md">
          <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Rendus ce mois</p>
          <p class="text-2xl font-bold text-gray-800">{{ completedCount() }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-red-500 shadow-sm transition hover:shadow-md">
          <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">En retard</p>
          <p class="text-2xl font-bold text-red-600">{{ lateCount() }}</p>
        </div>
      </div>

      <!-- Homework List -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="bg-gray-800 px-6 py-4">
          <h2 class="text-white font-bold flex items-center gap-2"><i class="pi pi-list"></i> Travaux à faire</h2>
        </div>
        <div class="divide-y divide-gray-100">
          <div *ngFor="let hw of filteredHomework()" class="p-4 flex items-start gap-4 transition hover:bg-gray-50"
               [ngClass]="{'bg-red-50/50': hw.status === 'late', 'bg-green-50/50': hw.status === 'done'}">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
                 [style.background-color]="getSubjectColor(hw.subject) + '20'"
                 [style.color]="getSubjectColor(hw.subject)">
              <i class="pi pi-book text-xl"></i>
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
                <span *ngIf="hw.status === 'done'" class="px-2 py-0.5 text-xs font-bold bg-green-100 text-green-600 rounded-full uppercase tracking-wide flex items-center gap-1">
                  <i class="pi pi-check text-[10px]"></i> Rendu
                </span>
              </div>
              <h3 class="font-bold text-gray-800 text-lg">{{ hw.title }}</h3>
              <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ hw.description }}</p>
              <div class="flex items-center gap-4 mt-2 text-xs font-medium text-gray-500">
                <span class="flex items-center gap-1"><i class="pi pi-calendar"></i> À rendre: {{ hw.dueDate }}</span>
                <span class="flex items-center gap-1"><i class="pi pi-user"></i> {{ hw.teacher }}</span>
              </div>
            </div>
            <div class="text-right">
              <div *ngIf="hw.grade" class="text-2xl font-black" 
                   [ngClass]="hw.grade >= 10 ? 'text-green-600' : 'text-red-600'">
                {{ hw.grade }}<span class="text-sm font-medium text-gray-400">/20</span>
              </div>
              <button *ngIf="!hw.grade && hw.status !== 'done'" (click)="viewDetails(hw)" 
                      class="text-sm text-purple-600 font-bold hover:underline mt-2 flex items-center justify-end gap-1">
                Voir détails <i class="pi pi-arrow-right text-xs"></i>
              </button>
            </div>
          </div>
          <div *ngIf="filteredHomework().length === 0" class="p-12 text-center text-gray-500">
            <i class="pi pi-check-circle text-4xl text-gray-300 mb-2"></i>
            <p>Aucun devoir à afficher</p>
          </div>
        </div>
      </div>

      <!-- Recent Lessons -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="pi pi-history"></i> Cahier de texte récent</h3>
        <div class="space-y-4">
          <div *ngFor="let lesson of recentLessons()" class="p-4 bg-gray-50 rounded-xl border border-gray-100 hover:border-indigo-200 transition">
            <div class="flex items-center justify-between mb-2">
               <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-indigo-100 text-indigo-700 uppercase tracking-wide">{{ lesson.subject }}</span>
               </div>
               <span class="text-xs font-medium text-gray-500">{{ lesson.date }}</span>
            </div>
            <h4 class="font-bold text-gray-800">{{ lesson.title }}</h4>
            <p class="text-sm text-gray-600 mt-1">{{ lesson.content }}</p>
          </div>
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
                 <div class="grid grid-cols-2 gap-4">
                    <div>
                         <h4 class="text-sm font-bold text-gray-700 mb-1 uppercase tracking-wide">Date limite</h4>
                         <p class="text-gray-800 font-medium flex items-center gap-2"><i class="pi pi-calendar text-gray-400"></i> {{ selectedHomework?.dueDate }}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-1 uppercase tracking-wide">Enseignant</h4>
                        <p class="text-gray-800 font-medium flex items-center gap-2"><i class="pi pi-user text-gray-400"></i> {{ selectedHomework?.teacher }}</p>
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
export class ParentHomeworkComponent {
  selectedChild = '1';
  filterSubject = '';
  
  showDetailsModal = false;
  selectedHomework: any = null;

  children = signal([
    { id: '1', name: 'Amadou Diallo' },
    { id: '2', name: 'Fatou Diallo' },
  ]);

  subjects = signal(['Mathématiques', 'Français', 'Histoire-Géo', 'SVT', 'Anglais']);

  subjectColors: Record<string, string> = {
    'Mathématiques': '#4F46E5',
    'Français': '#DC2626',
    'Histoire-Géo': '#059669',
    'SVT': '#16A34A',
    'Anglais': '#7C3AED'
  };

  pendingThisWeek = signal(3);
  completedCount = signal(12);
  lateCount = signal(1);

  homework = signal([
    { id: 1, subject: 'Mathématiques', title: 'Exercices sur les fractions', description: 'Faire les exercices 1 à 10 page 47. Bien réviser les règles de simplification.', dueDate: '30/12/2024', teacher: 'M. Ouédraogo', status: 'pending', grade: null },
    { id: 2, subject: 'Français', title: 'Rédaction', description: 'Écrire une rédaction de 300 mots. Sujet: "Ma meilleure vacances".', dueDate: '28/12/2024', teacher: 'Mme Sawadogo', status: 'pending', grade: null },
    { id: 3, subject: 'Histoire-Géo', title: 'Carte de l\'Afrique', description: 'Compléter la carte muette des régions climatiques.', dueDate: '20/12/2024', teacher: 'M. Kaboré', status: 'late', grade: null },
    { id: 4, subject: 'SVT', title: 'Schéma cellule', description: 'Dessiner et légender une cellule végétale.', dueDate: '18/12/2024', teacher: 'M. Traoré', status: 'done', grade: 16 },
  ]);

  recentLessons = signal([
    { subject: 'Mathématiques', date: '23/12/2024', title: 'Les fractions', content: 'Introduction aux fractions : numérateur et dénominateur' },
    { subject: 'Français', date: '22/12/2024', title: 'La description', content: 'Techniques de description, vocabulaire descriptif' },
  ]);

  filteredHomework = () => {
    let result = this.homework();
    if (this.filterSubject) result = result.filter(h => h.subject === this.filterSubject);
    return result;
  };

  getSubjectColor(subject: string) {
    return this.subjectColors[subject] || '#6B7280';
  }

  viewDetails(hw: any) {
    this.selectedHomework = hw;
    this.showDetailsModal = true;
  }
}
