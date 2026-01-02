import { Component, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-parent-attendance',
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
          <h1 class="text-2xl font-bold text-gray-800">Suivi des Absences</h1>
          <p class="text-gray-500">Historique des présences de vos enfants</p>
        </div>
        <select [(ngModel)]="selectedChild" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 bg-white transition cursor-pointer font-medium">
          <option *ngFor="let child of children()" [value]="child.id">{{ child.name }}</option>
        </select>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-5 text-white shadow-lg shadow-green-200">
          <p class="text-white/80 text-sm font-medium">Taux de présence</p>
          <p class="text-3xl font-black mt-1">{{ attendanceRate() }}%</p>
          <div class="w-full bg-white/20 h-1.5 rounded-full mt-3 overflow-hidden">
             <div class="h-full bg-white rounded-full" [style.width.%]="attendanceRate()"></div>
          </div>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-red-500 shadow-sm transition hover:shadow-md">
          <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Absences</p>
          <p class="text-3xl font-bold text-gray-800">{{ absenceCount() }}</p>
          <p class="text-xs font-medium text-gray-400 mt-1">ce trimestre</p>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-orange-500 shadow-sm transition hover:shadow-md">
          <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Retards</p>
          <p class="text-3xl font-bold text-gray-800">{{ lateCount() }}</p>
          <p class="text-xs font-medium text-gray-400 mt-1">ce trimestre</p>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-yellow-500 shadow-sm transition hover:shadow-md">
          <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Non justifiées</p>
          <p class="text-3xl font-bold text-gray-800">{{ unjustifiedCount() }}</p>
           <p class="text-xs font-medium text-gray-400 mt-1">Nécessite action</p>
        </div>
      </div>

      <!-- Attendance List -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="bg-gray-800 px-6 py-4 flex items-center justify-between">
          <h2 class="text-white font-bold flex items-center gap-2"><i class="pi pi-history"></i> Historique</h2>
          <select [(ngModel)]="periodFilter" class="px-3 py-1.5 bg-gray-700 text-white rounded-lg text-sm border-0 focus:ring-0 cursor-pointer text-white/90 font-medium">
            <option value="month">Ce mois</option>
            <option value="trimester">Ce trimestre</option>
            <option value="year">Cette année</option>
          </select>
        </div>
        <div class="divide-y divide-gray-100">
          <div *ngFor="let record of filteredRecords()" class="p-4 flex items-center gap-4 transition hover:bg-gray-50">
            <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0 shadow-sm"
                 [ngClass]="{
                   'bg-red-100 text-red-600': record.type === 'absent',
                   'bg-orange-100 text-orange-600': record.type === 'late'
                 }">
              <i [class]="record.type === 'absent' ? 'pi pi-times' : 'pi pi-clock'"></i>
            </div>
            <div class="flex-1">
              <div class="font-bold text-gray-800">{{ record.type === 'absent' ? 'Absence' : 'Retard' }}</div>
              <div class="text-sm text-gray-500 font-medium">{{ record.date }} • {{ record.subject }}</div>
              <div *ngIf="record.reason" class="text-sm text-gray-600 mt-1 flex items-center gap-1 bg-gray-100 w-fit px-2 py-0.5 rounded">
                <i class="pi pi-info-circle text-xs"></i>{{ record.reason }}
              </div>
            </div>
            <div class="text-right">
              <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wide"
                    [ngClass]="record.justified ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                {{ record.justified ? 'Justifiée' : 'Non justifiée' }}
              </span>
              <button *ngIf="!record.justified" (click)="justifyAbsence(record)"
                      class="block mt-2 text-sm text-blue-600 font-bold hover:underline hover:text-blue-800 transition ml-auto">
                Justifier
              </button>
            </div>
          </div>
          <div *ngIf="filteredRecords().length === 0" class="p-12 text-center text-gray-500">
            <i class="pi pi-check-circle text-4xl text-green-500 mb-2 opacity-50"></i>
            <p class="font-medium">Aucune absence enregistrée</p>
          </div>
        </div>
      </div>

      <!-- Justify Modal -->
      <div *ngIf="showJustifyModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showJustifyModal = false">
        <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden" (click)="$event.stopPropagation()">
          <div class="bg-blue-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">Justifier l'absence</h3>
            <button (click)="showJustifyModal = false" class="text-white/80 hover:text-white transition"><i class="pi pi-times"></i></button>
          </div>
          <form (ngSubmit)="submitJustification()" class="p-6 space-y-4">
            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 mb-4">
                 <p class="text-sm text-blue-800 font-medium">Absence du <span class="font-bold">{{ selectedRecord?.date }}</span> en <span class="font-bold">{{ selectedRecord?.subject }}</span></p>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Motif</label>
              <select [(ngModel)]="justification.reason" name="reason" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 bg-white transition">
                <option value="maladie">Maladie</option>
                <option value="rdv_medical">Rendez-vous médical</option>
                <option value="famille">Raison familiale</option>
                <option value="autre">Autre</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Commentaire</label>
              <textarea [(ngModel)]="justification.comment" name="comment" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 transition" placeholder="Précisions éventuelles..."></textarea>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Justificatif (optionnel)</label>
              <input type="file" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 transition file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 bg-gray-50 -mx-6 -mb-6 px-6 py-4 mt-2">
              <button type="button" (click)="showJustifyModal = false" class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-100 transition">Annuler</button>
              <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200">Envoyer</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  `
})
export class ParentAttendanceComponent {
  selectedChild = '1';
  periodFilter = 'trimester';
  
  showJustifyModal = false;
  showSuccessToast = false;
  successMessage = '';

  selectedRecord: any = null;

  justification = { reason: 'maladie', comment: '' };

  children = signal([
    { id: '1', name: 'Amadou Diallo' },
    { id: '2', name: 'Fatou Diallo' },
  ]);

  records = signal([
    { id: 1, childId: '1', date: '20/12/2024', type: 'absent', subject: 'Mathématiques', justified: true, reason: 'Rendez-vous médical' },
    { id: 2, childId: '1', date: '18/12/2024', type: 'late', subject: 'Français', justified: false, reason: '' },
    { id: 3, childId: '1', date: '15/12/2024', type: 'absent', subject: 'Histoire', justified: false, reason: '' },
    { id: 4, childId: '2', date: '19/12/2024', type: 'absent', subject: 'SVT', justified: true, reason: 'Maladie' },
  ]);

  filteredRecords = () => this.records().filter(r => r.childId === this.selectedChild);
  
  absenceCount = computed(() => this.filteredRecords().filter(r => r.type === 'absent').length);
  lateCount = computed(() => this.filteredRecords().filter(r => r.type === 'late').length);
  unjustifiedCount = computed(() => this.filteredRecords().filter(r => !r.justified).length);
  attendanceRate = signal(94);

  justifyAbsence(record: any) {
    this.selectedRecord = record;
    this.showJustifyModal = true;
  }

  submitJustification() {
    if (this.selectedRecord) {
      this.records.update(records => 
        records.map(r => r.id === this.selectedRecord.id ? { ...r, justified: true, reason: this.justification.reason } : r)
      );
    }
    this.showToast('Justification envoyée avec succès !');
    this.showJustifyModal = false;
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
