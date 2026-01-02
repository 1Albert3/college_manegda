import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-student-resources',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6 relative">
      <!-- Toast Notification -->
      <div *ngIf="showSuccessToast" class="fixed top-4 right-4 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-xl z-[100] flex items-center gap-3 transition-opacity duration-300">
        <i class="pi pi-download text-xl text-green-400"></i>
        <span class="font-medium">{{ successMessage }}</span>
      </div>

      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Ressources Pédagogiques</h1>
          <p class="text-gray-500">Documents et supports de cours</p>
        </div>
        <div class="flex gap-2">
          <div class="relative">
             <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
             <input type="text" [(ngModel)]="searchQuery" placeholder="Rechercher..." 
                 class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 transition w-full md:w-64">
          </div>
          <select [(ngModel)]="filterSubject" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 bg-white transition cursor-pointer">
            <option value="">Toutes les matières</option>
            <option *ngFor="let s of subjects()" [value]="s">{{ s }}</option>
          </select>
        </div>
      </div>

      <!-- Categories -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div *ngFor="let cat of categories()" 
             class="bg-white rounded-xl p-5 cursor-pointer border hover:border-purple-300 hover:shadow-md transition-all duration-200 group"
             [ngClass]="{'ring-2 ring-purple-500 border-transparent': selectedCategory === cat.id, 'border-gray-200': selectedCategory !== cat.id}"
             (click)="selectedCategory = cat.id">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-3 transition-transform group-hover:scale-110"
               [style.background-color]="cat.color + '20'" [style.color]="cat.color">
            <i [class]="cat.icon + ' text-xl'"></i>
          </div>
          <h3 class="font-bold text-gray-800 group-hover:text-purple-700 transition">{{ cat.name }}</h3>
          <p class="text-sm text-gray-500 font-medium">{{ cat.count }} fichiers</p>
        </div>
      </div>

      <!-- Resources List -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="bg-gray-800 px-6 py-4 flex items-center justify-between">
          <h2 class="text-white font-bold flex items-center gap-2"><i class="pi pi-folder-open"></i> {{ getCategoryName() }}</h2>
          <span class="text-white/60 text-sm font-bold bg-white/10 px-2 py-0.5 rounded">{{ filteredResources().length }} fichier(s)</span>
        </div>
        <div class="divide-y divide-gray-100">
          <div *ngFor="let resource of filteredResources()" 
               class="p-4 flex items-center gap-4 hover:bg-gray-50 cursor-pointer transition group"
               (click)="viewResource(resource)">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 shadow-sm group-hover:shadow-md transition"
                 [ngClass]="{
                   'bg-red-100 text-red-600': resource.type === 'pdf',
                   'bg-blue-100 text-blue-600': resource.type === 'doc',
                   'bg-green-100 text-green-600': resource.type === 'video',
                   'bg-purple-100 text-purple-600': resource.type === 'link'
                 }">
              <i [class]="getTypeIcon(resource.type) + ' text-xl'"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-bold text-gray-800 text-lg truncate group-hover:text-purple-600 transition">{{ resource.title }}</div>
              <div class="flex items-center gap-2 text-sm text-gray-500 mt-0.5">
                  <span class="font-medium text-gray-700">{{ resource.subject }}</span>
                  <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                  <span>{{ resource.date }}</span>
              </div>
              <div class="text-xs font-medium text-gray-400 mt-1 flex items-center gap-1"><i class="pi pi-user"></i> {{ resource.teacher }}</div>
            </div>
            <div class="text-right shrink-0">
              <span class="text-xs font-bold text-gray-400 bg-gray-100 px-2 py-1 rounded">{{ resource.size }}</span>
              <button (click)="downloadResource(resource); $event.stopPropagation()" 
                      class="block mt-2 text-purple-600 hover:text-purple-800 hover:bg-purple-50 px-3 py-1.5 rounded-lg transition font-bold text-sm flex items-center gap-1 ml-auto">
                <i class="pi pi-download"></i> Télécharger
              </button>
            </div>
          </div>
          <div *ngIf="filteredResources().length === 0" class="p-12 text-center text-gray-500">
            <i class="pi pi-search text-4xl text-gray-300 mb-2"></i>
            <p class="font-medium">Aucune ressource trouvée</p>
          </div>
        </div>
      </div>

      <!-- Recent Additions -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="pi pi-clock"></i> Récemment ajoutés</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div *ngFor="let resource of recentResources()" 
               class="p-4 border border-gray-200 rounded-xl hover:border-purple-500 cursor-pointer hover:shadow-md transition group bg-gray-50"
               (click)="viewResource(resource)">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
                   [style.background-color]="getSubjectColor(resource.subject) + '20'"
                   [style.color]="getSubjectColor(resource.subject)">
                <i class="pi pi-file"></i>
              </div>
              <div class="flex-1 min-w-0">
                <div class="font-bold text-gray-800 truncate group-hover:text-purple-600 transition">{{ resource.title }}</div>
                <div class="text-xs font-bold text-gray-500 uppercase tracking-wide">{{ resource.subject }}</div>
              </div>
            </div>
            <div class="mt-3 text-xs font-medium text-gray-400 flex items-center justify-end gap-1">Ajouté le {{ resource.date }}</div>
          </div>
        </div>
      </div>
    </div>
  `
})
export class StudentResourcesComponent {
  searchQuery = '';
  filterSubject = '';
  selectedCategory = 'all';
  
  showSuccessToast = false;
  successMessage = '';

  subjects = signal(['Mathématiques', 'Français', 'Histoire-Géo', 'SVT', 'Anglais']);

  subjectColors: Record<string, string> = {
    'Mathématiques': '#4F46E5',
    'Français': '#DC2626',
    'Histoire-Géo': '#059669',
    'SVT': '#16A34A',
    'Anglais': '#7C3AED'
  };

  categories = signal([
    { id: 'all', name: 'Tous', icon: 'pi pi-folder', color: '#6B7280', count: 24 },
    { id: 'courses', name: 'Cours', icon: 'pi pi-book', color: '#4F46E5', count: 12 },
    { id: 'exercises', name: 'Exercices', icon: 'pi pi-pencil', color: '#F59E0B', count: 8 },
    { id: 'videos', name: 'Vidéos', icon: 'pi pi-video', color: '#10B981', count: 4 },
  ]);

  resources = signal([
    { id: 1, title: 'Cours - Les fractions', subject: 'Mathématiques', type: 'pdf', size: '1.2 MB', date: '23/12/2024', teacher: 'M. Ouédraogo', category: 'courses' },
    { id: 2, title: 'Exercices chapitre 5', subject: 'Mathématiques', type: 'pdf', size: '0.8 MB', date: '22/12/2024', teacher: 'M. Ouédraogo', category: 'exercises' },
    { id: 3, title: 'Conjugaison - Le passé simple', subject: 'Français', type: 'doc', size: '0.5 MB', date: '21/12/2024', teacher: 'Mme Sawadogo', category: 'courses' },
    { id: 4, title: 'Tutoriel - Expérience SVT', subject: 'SVT', type: 'video', size: '45 MB', date: '20/12/2024', teacher: 'M. Traoré', category: 'videos' },
    { id: 5, title: 'Vocabulaire anglais', subject: 'Anglais', type: 'pdf', size: '0.3 MB', date: '19/12/2024', teacher: 'Mme Diallo', category: 'courses' },
  ]);

  recentResources = () => this.resources().slice(0, 3);

  filteredResources = () => {
    let result = this.resources();
    if (this.selectedCategory !== 'all') result = result.filter(r => r.category === this.selectedCategory);
    if (this.filterSubject) result = result.filter(r => r.subject === this.filterSubject);
    if (this.searchQuery) {
      const q = this.searchQuery.toLowerCase();
      result = result.filter(r => r.title.toLowerCase().includes(q));
    }
    return result;
  };

  getCategoryName = () => this.categories().find(c => c.id === this.selectedCategory)?.name || 'Tous';

  getSubjectColor(subject: string) { return this.subjectColors[subject] || '#6B7280'; }

  getTypeIcon(type: string) {
    return { pdf: 'pi pi-file-pdf', doc: 'pi pi-file-word', video: 'pi pi-video', link: 'pi pi-link' }[type] || 'pi pi-file';
  }

  viewResource(resource: any) { 
      this.showToast(`Ouverture de : ${resource.title}`);
  }
  
  downloadResource(resource: any) { 
      this.showToast(`Téléchargement lancé : ${resource.title}`);
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
