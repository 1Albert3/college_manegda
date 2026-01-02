import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-teacher-appointments',
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
          <h1 class="text-2xl font-bold text-gray-800">Rendez-vous Parents</h1>
          <p class="text-gray-500">Gestion des créneaux et demandes</p>
        </div>
        <button (click)="showNewSlot = true" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-sm flex items-center gap-2">
          <i class="pi pi-plus"></i>Ajouter des créneaux
        </button>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-4 border-l-4 border-blue-500 shadow-sm">
          <p class="text-gray-500 text-sm font-medium">Aujourd'hui</p>
          <p class="text-3xl font-bold text-gray-800 mt-1">{{ todayCount() }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 border-l-4 border-orange-500 shadow-sm">
          <p class="text-gray-500 text-sm font-medium">En attente</p>
          <p class="text-3xl font-bold text-gray-800 mt-1">{{ pendingCount() }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 border-l-4 border-green-500 shadow-sm">
          <p class="text-gray-500 text-sm font-medium">Cette semaine</p>
          <p class="text-3xl font-bold text-gray-800 mt-1">{{ weekCount() }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 border-l-4 border-purple-500 shadow-sm">
          <p class="text-gray-500 text-sm font-medium">Créneaux libres</p>
          <p class="text-3xl font-bold text-gray-800 mt-1">{{ availableSlots() }}</p>
        </div>
      </div>

      <!-- Pending Requests -->
      <div *ngIf="pendingRequests().length > 0" class="bg-orange-50 border border-orange-200 rounded-xl p-4 animate-fade-in">
        <h3 class="font-bold text-orange-800 mb-3 flex items-center gap-2"><i class="pi pi-clock"></i>Demandes en attente</h3>
        <div class="space-y-2">
          <div *ngFor="let req of pendingRequests()" class="bg-white p-4 rounded-lg flex items-center justify-between shadow-sm">
            <div>
              <span class="font-bold text-gray-800">{{ req.parent }}</span>
              <span class="text-sm text-gray-500 font-medium"> - Parent de {{ req.student }}</span>
              <p class="text-sm text-gray-600 mt-1">{{ req.reason }}</p>
            </div>
            <div class="flex gap-2">
              <button (click)="acceptRequest(req)" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-bold transition">Accepter</button>
              <button (click)="confirmAction('reject', req)" class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg text-sm font-bold transition">Refuser</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Upcoming Appointments -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
          <h2 class="text-white font-bold flex items-center gap-2"><i class="pi pi-calendar-check mt-1"></i>Rendez-vous à venir</h2>
        </div>
        <div class="divide-y divide-gray-100">
          <div *ngFor="let apt of upcomingAppointments()" class="p-5 flex items-center gap-6 hover:bg-gray-50 transition">
            <div class="w-16 text-center shrink-0 bg-indigo-50 rounded-xl p-2">
              <div class="text-2xl font-black text-indigo-600 leading-none">{{ apt.day }}</div>
              <div class="text-xs font-bold text-indigo-400 uppercase mt-1">{{ apt.month }}</div>
            </div>
            <div class="flex-1">
              <div class="font-bold text-lg text-gray-800">{{ apt.parent }}</div>
              <div class="text-sm text-gray-500 font-medium flex items-center gap-2">
                <span><i class="pi pi-user mr-1"></i>{{ apt.student }}</span>
                <span>•</span>
                <span class="text-indigo-600"><i class="pi pi-clock mr-1"></i>{{ apt.time }}</span>
              </div>
              <div class="text-sm text-gray-600 mt-2 bg-gray-50 p-2 rounded-lg border border-gray-100 inline-block">{{ apt.reason }}</div>
            </div>
            <div class="flex gap-2">
              <button (click)="reschedule(apt)" class="p-2.5 text-blue-600 hover:bg-blue-50 rounded-xl transition bg-white border border-gray-200" title="Reporter">
                <i class="pi pi-calendar-plus"></i>
              </button>
              <button (click)="confirmAction('cancel', apt)" class="p-2.5 text-red-600 hover:bg-red-50 rounded-xl transition bg-white border border-gray-200" title="Annuler">
                <i class="pi pi-times"></i>
              </button>
            </div>
          </div>
          <div *ngIf="upcomingAppointments().length === 0" class="p-12 text-center text-gray-500">
            <div class="bg-gray-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="pi pi-calendar-minus text-2xl text-gray-400"></i>
            </div>
            <p>Aucun rendez-vous à venir</p>
          </div>
        </div>
      </div>

      <!-- Available Slots -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2"><i class="pi pi-list"></i>Mes créneaux disponibles</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <div *ngFor="let slot of slots()" 
               class="p-4 rounded-xl text-center border transition-all duration-300 cursor-default"
               [ngClass]="slot.available ? 'bg-green-50/50 border-green-200 hover:border-green-300 hover:shadow-sm' : 'bg-gray-50 border-gray-200 opacity-60'">
            <div class="font-medium text-gray-600 mb-1">{{ slot.day }}</div>
            <div class="text-xl font-black mb-2" [ngClass]="slot.available ? 'text-green-600' : 'text-gray-400'">{{ slot.time }}</div>
            <div class="text-[10px] uppercase font-bold tracking-widest" [ngClass]="slot.available ? 'text-green-600' : 'text-gray-400'">
              {{ slot.available ? 'Disponible' : 'Réservé' }}
            </div>
          </div>
        </div>
      </div>

      <!-- Add Slots Modal -->
      <div *ngIf="showNewSlot" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showNewSlot = false">
        <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl overflow-hidden" (click)="$event.stopPropagation()">
          <div class="bg-indigo-600 px-6 py-4 rounded-t-2xl flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">Ajouter des créneaux</h3>
            <button (click)="showNewSlot = false" class="text-white/80 hover:text-white transition"><i class="pi pi-times"></i></button>
          </div>
          <form (ngSubmit)="addSlots()" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Date</label>
              <input type="date" [(ngModel)]="newSlot.date" name="date" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Heure début</label>
                <input type="time" [(ngModel)]="newSlot.startTime" name="start" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500">
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Heure fin</label>
                <input type="time" [(ngModel)]="newSlot.endTime" name="end" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500">
              </div>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Durée par RDV</label>
              <select [(ngModel)]="newSlot.duration" name="duration" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 bg-white">
                <option value="15">15 min</option>
                <option value="20">20 min</option>
                <option value="30">30 min</option>
              </select>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
              <button type="button" (click)="showNewSlot = false" class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50 transition">Annuler</button>
              <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition">Ajouter</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Confirmation Modal -->
      <div *ngIf="showConfirmModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showConfirmModal = false">
        <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden" (click)="$event.stopPropagation()">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="pi pi-exclamation-triangle text-3xl text-red-600"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Confirmer l'action</h3>
                <p class="text-gray-500 mb-6">{{ confirmMessage }}</p>
                <div class="flex gap-3 justify-center">
                    <button (click)="showConfirmModal = false" class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50 transition">Annuler</button>
                    <button (click)="confirmActionExecute()" class="px-5 py-2.5 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 transition">Confirmer</button>
                </div>
            </div>
        </div>
      </div>
    </div>
  `
})
export class TeacherAppointmentsComponent {
  showNewSlot = false;
  newSlot = { date: '', startTime: '16:00', endTime: '18:00', duration: '20' };

  todayCount = signal(2);
  pendingCount = signal(3);
  weekCount = signal(8);
  availableSlots = signal(12);

  // UI State
  showConfirmModal = false;
  confirmMessage = '';
  actionType: 'reject' | 'cancel' | null = null;
  selectedItem: any = null;
  showSuccessToast = false;
  successMessage = '';

  pendingRequests = signal<any[]>([
    { id: 1, parent: 'M. Diallo', student: 'Amadou Diallo', reason: 'Discussion sur les résultats du trimestre' },
    { id: 2, parent: 'Mme Sawadogo', student: 'Fatou Sawadogo', reason: 'Orientation scolaire' },
  ]);

  upcomingAppointments = signal<any[]>([
    { id: 1, day: '24', month: 'Déc', parent: 'M. Ouédraogo', student: 'Ibrahim Ouédraogo', time: '16:00', reason: 'Suivi des notes' },
    { id: 2, day: '26', month: 'Déc', parent: 'Mme Koné', student: 'Aminata Koné', time: '17:00', reason: 'Comportement en classe' },
  ]);

  slots = signal<any[]>([
    { day: 'Lundi', time: '16:00', available: true },
    { day: 'Lundi', time: '16:30', available: false },
    { day: 'Mardi', time: '16:00', available: true },
    { day: 'Mardi', time: '16:30', available: true },
    { day: 'Jeudi', time: '17:00', available: true },
  ]);

  acceptRequest(req: any) {
    this.pendingRequests.update(list => list.filter(r => r.id !== req.id));
    this.showToast(`Demande acceptée pour ${req.parent}`);
  }

  confirmAction(type: 'reject' | 'cancel', item: any) {
    this.actionType = type;
    this.selectedItem = item;
    this.confirmMessage = type === 'reject' ? 'Voulez-vous vraiment refuser cette demande ?' : 'Voulez-vous vraiment annuler ce rendez-vous ?';
    this.showConfirmModal = true;
  }

  confirmActionExecute() {
    if (this.actionType === 'reject') {
        this.pendingRequests.update(list => list.filter(r => r.id !== this.selectedItem.id));
        this.showToast('Demande refusée.');
    } else if (this.actionType === 'cancel') {
        // Here we would move it to cancelled or remove same way
        this.upcomingAppointments.update(list => list.filter(a => a.id !== this.selectedItem.id));
        this.showToast('Rendez-vous annulé.');
    }
    this.showConfirmModal = false;
    this.selectedItem = null;
    this.actionType = null;
  }

  reschedule(apt: any) {
      this.showToast('Modification de rendez-vous bientôt disponible');
  }

  addSlots() {
    // Mock adding slots logic
    if (this.newSlot.date) {
        const dayName = new Date(this.newSlot.date).toLocaleDateString('fr-FR', { weekday: 'long' });
        const capitalizedDay = dayName.charAt(0).toUpperCase() + dayName.slice(1);
        this.slots.update(s => [...s, { 
            day: capitalizedDay, 
            time: this.newSlot.startTime, 
            available: true 
        }]);
    }
    this.showToast('Créneaux ajoutés avec succès !');
    this.showNewSlot = false;
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
