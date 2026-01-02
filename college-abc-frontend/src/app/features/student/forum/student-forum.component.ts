import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-student-forum',
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
          <h1 class="text-2xl font-bold text-gray-800">Forum de Classe</h1>
          <p class="text-gray-500">Discussions et entraide entre élèves</p>
        </div>
        <button (click)="showNewTopic = true" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 shadow-sm font-bold transition flex items-center gap-2">
          <i class="pi pi-plus"></i> Nouveau sujet
        </button>
      </div>

      <!-- Categories -->
      <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
        <button *ngFor="let cat of categories()" (click)="selectedCategory = cat.id"
                class="px-5 py-2.5 rounded-full whitespace-nowrap text-sm font-bold transition-all duration-200 border"
                [ngClass]="selectedCategory === cat.id ? 'bg-purple-600 text-white border-purple-600 shadow-md transform scale-105' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:border-gray-300'">
          <i [class]="cat.icon + ' mr-2'"></i>{{ cat.name }}
        </button>
      </div>

      <!-- Topics List -->
      <div class="space-y-4">
        <div *ngFor="let topic of filteredTopics()" 
             class="bg-white rounded-xl shadow-sm p-5 cursor-pointer hover:shadow-md transition-all duration-200 border border-gray-100 hover:border-purple-200"
             (click)="viewTopic(topic)">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-100 to-indigo-100 flex items-center justify-center text-purple-600 font-black text-lg border-2 border-white shadow-sm shrink-0">
              {{ topic.author.charAt(0) }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-gray-100 text-gray-600 uppercase tracking-wide">{{ topic.category }}</span>
                <span *ngIf="topic.pinned" class="px-2.5 py-0.5 text-xs font-bold bg-yellow-100 text-yellow-700 rounded-full flex items-center gap-1 uppercase tracking-wide">
                  <i class="pi pi-bookmark-fill text-[10px]"></i> Épinglé
                </span>
              </div>
              <h3 class="font-bold text-gray-800 text-lg hover:text-purple-600 transition truncate">{{ topic.title }}</h3>
              <p class="text-sm text-gray-500 mt-1 line-clamp-2 leading-relaxed">{{ topic.preview }}</p>
              <div class="flex items-center gap-4 mt-3 text-xs font-medium text-gray-400">
                <span class="flex items-center gap-1"><i class="pi pi-user"></i> {{ topic.author }}</span>
                <span class="flex items-center gap-1"><i class="pi pi-clock"></i> {{ topic.date }}</span>
                <span class="flex items-center gap-1"><i class="pi pi-comment"></i> {{ topic.replies }} réponses</span>
                <span class="flex items-center gap-1"><i class="pi pi-eye"></i> {{ topic.views }} vues</span>
              </div>
            </div>
            <div class="self-center text-gray-300">
                <i class="pi pi-chevron-right"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- New Topic Modal -->
      <div *ngIf="showNewTopic" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showNewTopic = false">
        <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl overflow-hidden" (click)="$event.stopPropagation()">
          <div class="bg-purple-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">Nouveau Sujet</h3>
            <button (click)="showNewTopic = false" class="text-white/80 hover:text-white transition"><i class="pi pi-times"></i></button>
          </div>
          <form (ngSubmit)="createTopic()" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Catégorie</label>
              <select [(ngModel)]="newTopic.category" name="category" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 bg-white transition">
                <option *ngFor="let cat of categories()" [value]="cat.name">{{ cat.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Titre</label>
              <input type="text" [(ngModel)]="newTopic.title" name="title" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 transition" placeholder="De quoi voulez-vous discuter ?">
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Message</label>
              <textarea [(ngModel)]="newTopic.content" name="content" rows="5" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 transition" placeholder="Décrivez votre question ou sujet..."></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 bg-gray-50 -mx-6 -mb-6 px-6 py-4 mt-2">
              <button type="button" (click)="showNewTopic = false" class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-100 transition">Annuler</button>
              <button type="submit" class="px-5 py-2.5 bg-purple-600 text-white rounded-xl font-bold hover:bg-purple-700 transition shadow-lg shadow-purple-200">Publier</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Topic View Modal -->
      <div *ngIf="selectedTopic" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="selectedTopic = null">
        <div class="bg-white rounded-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden flex flex-col shadow-2xl" (click)="$event.stopPropagation()">
          <div class="bg-purple-600 px-6 py-4 flex justify-between items-center shrink-0">
            <h3 class="text-xl font-bold text-white truncate pr-4">{{ selectedTopic.title }}</h3>
            <button (click)="selectedTopic = null" class="text-white/80 hover:text-white transition">
              <i class="pi pi-times"></i>
            </button>
          </div>
          <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-6">
            <!-- Original post -->
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-100">
              <div class="flex items-center gap-3 mb-4 border-b border-gray-200 pb-3">
                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-black">
                  {{ selectedTopic.author.charAt(0) }}
                </div>
                <div>
                  <div class="font-bold text-gray-900">{{ selectedTopic.author }}</div>
                  <div class="text-xs font-medium text-gray-500">{{ selectedTopic.date }}</div>
                </div>
              </div>
              <p class="text-gray-800 leading-relaxed whitespace-pre-line">{{ selectedTopic.preview }}</p>
            </div>
            <!-- Replies -->
            <div>
              <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-sm uppercase tracking-wide">
                  <i class="pi pi-comments text-purple-600"></i> {{ selectedTopic.replies }} Réponses
              </h4>
              <!-- Mock replies listing if we had them, for now placeholder -->
              <div class="space-y-4">
                  <!-- Fake reply 1 -->
                  <div class="flex gap-3">
                      <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold shrink-0 text-sm">P</div>
                      <div class="flex-1 bg-white border border-gray-200 rounded-lg rounded-tl-none p-3 shadow-sm">
                          <p class="text-xs font-bold text-gray-900 mb-1">Professeur</p>
                          <p class="text-sm text-gray-700">C'est une excellente question. Je vous conseille de revoir le chapitre 3.</p>
                      </div>
                  </div>
              </div>
              
              <div class="text-center text-gray-400 py-8 bg-gray-50 rounded-xl border border-dashed border-gray-200 mt-4">
                <p class="text-sm font-medium">Rejoignez la discussion !</p>
              </div>
            </div>
          </div>
           <!-- Reply form -->
          <div class="border-t border-gray-100 p-4 bg-gray-50 shrink-0">
              <div class="flex gap-2">
                 <textarea [(ngModel)]="replyContent" rows="2" class="flex-1 px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 transition resize-none text-sm" placeholder="Écrire une réponse..."></textarea>
                 <button (click)="submitReply()" class="px-4 bg-purple-600 text-white rounded-xl font-bold hover:bg-purple-700 transition shadow-sm self-end h-[46px]">
                     <i class="pi pi-send"></i>
                 </button>
              </div>
          </div>
        </div>
      </div>
    </div>
  `
})
export class StudentForumComponent {
  selectedCategory = 'all';
  showNewTopic = false;
  selectedTopic: any = null;
  replyContent = '';
  
  showSuccessToast = false;
  successMessage = '';

  newTopic = { category: '', title: '', content: '' };

  categories = signal([
    { id: 'all', name: 'Tous', icon: 'pi pi-list' },
    { id: 'help', name: 'Aide aux devoirs', icon: 'pi pi-question-circle' },
    { id: 'exams', name: 'Examens', icon: 'pi pi-file' },
    { id: 'general', name: 'Discussion', icon: 'pi pi-comments' },
    { id: 'resources', name: 'Ressources', icon: 'pi pi-book' },
  ]);

  topics = signal([
    { id: 1, category: 'Aide aux devoirs', title: 'Aide pour l\'exercice de maths page 47', preview: 'Je n\'arrive pas à comprendre comment résoudre les équations avec des fractions. Est-ce que quelqu\'un peut m\'expliquer la méthode ? Merci !', author: 'Amadou D.', date: '23/12/2024', replies: 5, views: 32, pinned: false },
    { id: 2, category: 'Examens', title: 'Conseils pour réviser l\'examen de français', preview: 'L\'examen approche et je cherche des conseils pour bien me préparer. Quels sont les sujets les plus probables ?', author: 'Fatou S.', date: '22/12/2024', replies: 8, views: 45, pinned: true },
    { id: 3, category: 'Ressources', title: 'Partage de fiches de révision SVT', preview: 'J\'ai fait des fiches sur le chapitre des cellules, je les partage ici pour ceux que ça intéresse. Bon courage !', author: 'Ibrahim O.', date: '21/12/2024', replies: 12, views: 67, pinned: false },
    { id: 4, category: 'Discussion', title: 'Club de lecture - Propositions de livres', preview: 'Quels livres aimeriez-vous lire ensemble ce trimestre ? J\'ai pensé à quelque chose de science-fiction.', author: 'Aminata K.', date: '20/12/2024', replies: 15, views: 84, pinned: true },
  ]);

  filteredTopics = () => {
    if (this.selectedCategory === 'all') return this.topics();
    const cat = this.categories().find(c => c.id === this.selectedCategory);
    return this.topics().filter(t => t.category === cat?.name);
  };

  viewTopic(topic: any) { this.selectedTopic = topic; }

  createTopic() {
    console.log('New topic:', this.newTopic);
    this.showToast('Sujet créé avec succès !');
    this.showNewTopic = false;
    this.newTopic = { category: '', title: '', content: '' };
  }

  submitReply() {
    if (this.replyContent.trim()) {
      this.showToast('Réponse publiée !');
      this.replyContent = '';
    }
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
