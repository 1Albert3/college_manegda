import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-parent-bulletins',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6 relative">
      <!-- Toast Notification -->
      <div *ngIf="showSuccessToast" class="fixed top-4 right-4 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-xl z-[100] flex items-center gap-3 transition-opacity duration-300">
        <i class="pi pi-download text-xl text-purple-400"></i>
        <span class="font-medium">{{ successMessage }}</span>
      </div>

      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Bulletins Scolaires</h1>
          <p class="text-gray-500">Résultats trimestriels de vos enfants</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-600 hidden md:inline">Élève :</span>
            <select [(ngModel)]="selectedChild" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 bg-white transition cursor-pointer font-medium">
              <option *ngFor="let child of children()" [value]="child.id">{{ child.name }}</option>
            </select>
        </div>
      </div>

      <!-- Current Period -->
      <div class="bg-gradient-to-r from-purple-600 to-indigo-700 rounded-2xl p-6 text-white shadow-lg shadow-indigo-200 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
         <div class="absolute bottom-0 left-20 w-32 h-32 bg-white/10 rounded-full translate-y-1/2"></div>
        <div class="flex items-center justify-between relative z-10">
          <div>
            <p class="text-indigo-200 font-bold uppercase tracking-wider text-xs mb-1">Moyenne générale - {{ currentPeriod() }}</p>
            <p class="text-5xl font-black mt-1 tracking-tight">{{ currentAverage() }}<span class="text-2xl text-indigo-300 font-medium">/20</span></p>
            <p class="text-white/90 mt-3 font-medium flex items-center gap-2 bg-white/10 w-fit px-3 py-1 rounded-full text-sm backdrop-blur-sm">
                <i class="pi pi-chart-line"></i> Rang: {{ currentRank() }}<span class="text-xs opacity-75">/{{ totalStudents() }}</span>
            </p>
          </div>
          <div class="text-right">
            <div class="w-24 h-24 rounded-full bg-white/10 backdrop-blur-md flex items-center justify-center border-2 border-white/20 shadow-inner">
              <span class="text-4xl filter drop-shadow-md">{{ getGradeEmoji() }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Bulletins Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div *ngFor="let bulletin of bulletins()" 
             class="bg-white rounded-xl shadow-sm overflow-hidden cursor-pointer transition-all duration-300 group border border-gray-100"
             [class.hover:shadow-lg]="bulletin.status === 'available'"
             [class.hover:border-purple-200]="bulletin.status === 'available'"
             [class.opacity-70]="bulletin.status !== 'available'"
             (click)="viewBulletin(bulletin)">
          <div class="p-4 border-b flex justify-between items-center"
               [ngClass]="bulletin.status === 'available' ? 'bg-purple-50 border-purple-100' : 'bg-gray-50 border-gray-100'">
            <div>
                 <h3 class="font-bold text-gray-800 group-hover:text-purple-700 transition">{{ bulletin.period }}</h3>
                 <p class="text-xs font-medium text-gray-500">{{ bulletin.year }}</p>
            </div>
            <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wide"
                    [ngClass]="bulletin.status === 'available' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'">
                {{ bulletin.status === 'available' ? 'Disponible' : 'En attente' }}
            </span>
          </div>
          <div class="p-6">
            <div *ngIf="bulletin.status === 'available'" class="text-center">
              <p class="text-4xl font-black text-gray-800 tracking-tight mb-1">{{ bulletin.average }}<span class="text-lg text-gray-400 font-medium">/20</span></p>
              <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Moyenne générale</p>
              <div class="mt-4 pt-4 border-t border-gray-100 flex justify-center">
                <span class="text-purple-600 font-bold bg-purple-50 px-3 py-1 rounded-full text-sm">Rang: {{ bulletin.rank }}/{{ bulletin.total }}</span>
              </div>
            </div>
            <div *ngIf="bulletin.status !== 'available'" class="text-center text-gray-400 py-6">
              <i class="pi pi-clock text-4xl mb-3 opacity-50 block"></i>
              <p class="text-sm font-medium">Bientôt disponible</p>
            </div>
          </div>
          <div *ngIf="bulletin.status === 'available'" class="px-4 pb-4">
            <button (click)="downloadBulletin(bulletin); $event.stopPropagation()" 
                    class="w-full py-2.5 bg-gray-100 text-gray-700 rounded-lg font-bold hover:bg-purple-600 hover:text-white transition flex items-center justify-center gap-2 group-hover:bg-purple-600 group-hover:text-white">
              <i class="pi pi-download"></i> Télécharger PDF
            </button>
          </div>
        </div>
      </div>

      <!-- Bulletin Detail Modal -->
      <div *ngIf="selectedBulletin" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="selectedBulletin = null">
        <div class="bg-white rounded-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden flex flex-col shadow-2xl" (click)="$event.stopPropagation()">
          <div class="bg-gradient-to-r from-purple-600 to-indigo-700 px-6 py-4 flex justify-between items-center shrink-0">
            <div>
              <p class="text-indigo-200 text-xs font-bold uppercase tracking-wide mb-0.5">{{ getChildName() }}</p>
              <h3 class="text-xl font-bold text-white">{{ selectedBulletin.period }}</h3>
            </div>
            <button (click)="selectedBulletin = null" class="text-white/80 hover:text-white transition bg-white/10 w-8 h-8 rounded-full flex items-center justify-center">
              <i class="pi pi-times"></i>
            </button>
          </div>
          <div class="p-6 overflow-y-auto custom-scrollbar">
            <!-- Summary -->
            <div class="grid grid-cols-3 gap-4 mb-8">
              <div class="text-center p-4 bg-purple-50 rounded-xl border border-purple-100">
                <p class="text-3xl font-black text-purple-600">{{ selectedBulletin.average }}</p>
                <p class="text-xs font-bold text-gray-600 uppercase tracking-widest mt-1">Moyenne</p>
              </div>
              <div class="text-center p-4 bg-blue-50 rounded-xl border border-blue-100">
                <p class="text-3xl font-black text-blue-600">{{ selectedBulletin.rank }}</p>
                 <p class="text-xs font-bold text-gray-600 uppercase tracking-widest mt-1">Rang</p>
              </div>
              <div class="text-center p-4 bg-green-50 rounded-xl border border-green-100">
                <p class="text-3xl font-black text-green-600">{{ selectedBulletin.appreciation }}</p>
                 <p class="text-xs font-bold text-gray-600 uppercase tracking-widest mt-1">Appréciation</p>
              </div>
            </div>
            <!-- Subjects -->
            <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-sm uppercase tracking-wide"><i class="pi pi-list"></i> Détail par matière</h4>
            <div class="space-y-3 mb-8">
              <div *ngFor="let subject of selectedBulletin.subjects" class="flex items-center gap-4 p-4 bg-white border border-gray-100 rounded-xl hover:border-gray-200 transition shadow-sm">
                <div class="flex-1">
                  <span class="font-bold text-gray-800 block">{{ subject.name }}</span>
                </div>
                <div class="text-right">
                  <div class="flex items-center justify-end gap-2">
                      <span class="font-black text-lg" [ngClass]="subject.average >= 10 ? 'text-green-600' : 'text-red-500'">
                        {{ subject.average }}
                      </span>
                      <span class="text-sm font-medium text-gray-400">/20</span>
                  </div>
                  <span class="text-xs font-medium text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded border border-gray-100">Coef. {{ subject.coef }}</span>
                </div>
              </div>
            </div>
            <!-- Teacher Comment -->
            <div class="p-5 bg-yellow-50 border-l-4 border-yellow-400 rounded-r-xl">
              <p class="font-bold text-gray-800 flex items-center gap-2 text-sm uppercase tracking-wide mb-2"><i class="pi pi-comment"></i> Appréciation du conseil</p>
              <p class="text-gray-700 leading-relaxed italic">"{{ selectedBulletin.comment }}"</p>
            </div>
          </div>
            <!-- Actions -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end shrink-0">
              <button (click)="downloadBulletin(selectedBulletin)" class="px-6 py-2.5 bg-purple-600 text-white rounded-xl font-bold hover:bg-purple-700 transition shadow-lg shadow-purple-200 flex items-center gap-2">
                <i class="pi pi-download"></i> Télécharger le PDF
              </button>
            </div>
        </div>
      </div>
    </div>
  `
})
export class ParentBulletinsComponent {
  selectedChild = '1';
  selectedBulletin: any = null;
  
  showSuccessToast = false;
  successMessage = '';

  children = signal([
    { id: '1', name: 'Amadou Diallo' },
    { id: '2', name: 'Fatou Diallo' },
  ]);

  currentPeriod = signal('1er Trimestre 2024-2025');
  currentAverage = signal(14.5);
  currentRank = signal(5);
  totalStudents = signal(35);

  bulletins = signal([
    { 
      id: 1, period: '1er Trimestre', year: '2024-2025', status: 'available', 
      average: 14.5, rank: 5, total: 35, appreciation: 'TB',
      comment: 'Excellent trimestre. Élève sérieux et appliqué. Continuer ainsi.',
      subjects: [
        { name: 'Mathématiques', average: 15, coef: 4 },
        { name: 'Français', average: 14, coef: 4 },
        { name: 'Histoire-Géo', average: 13, coef: 2 },
        { name: 'SVT', average: 16, coef: 2 },
        { name: 'Anglais', average: 14, coef: 2 },
      ]
    },
    { id: 2, period: '2ème Trimestre', year: '2024-2025', status: 'pending' },
    { id: 3, period: '3ème Trimestre', year: '2024-2025', status: 'pending' },
  ]);

  getChildName = () => this.children().find(c => c.id === this.selectedChild)?.name || '';

  getGradeEmoji() {
    const avg = this.currentAverage();
    if (avg >= 16) return '🏆';
    if (avg >= 14) return '⭐';
    if (avg >= 12) return '👍';
    if (avg >= 10) return '📚';
    return '💪';
  }

  viewBulletin(bulletin: any) {
    if (bulletin.status === 'available') {
      this.selectedBulletin = bulletin;
    }
  }

  downloadBulletin(bulletin: any) {
    this.showToast(`Téléchargement lancé pour : ${bulletin.period}`);
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
