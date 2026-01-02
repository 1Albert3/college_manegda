import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-parent-appointments',
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
          <h1 class="text-2xl font-bold text-gray-800">Rendez-vous</h1>
          <p class="text-gray-500">Demandez un RDV avec les enseignants</p>
        </div>
        <button (click)="showNewRequest = true" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 shadow-sm flex items-center gap-2 transition">
          <i class="pi pi-plus"></i>Demander un RDV
        </button>
      </div>

      <!-- Upcoming Appointments -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
          <h2 class="text-white font-bold flex items-center gap-2"><i class="pi pi-calendar-check"></i> Mes rendez-vous</h2>
        </div>
        <div class="divide-y divide-gray-100">
          <div *ngFor="let apt of appointments()" class="p-5 flex items-center gap-5 hover:bg-gray-50 transition">
            <div class="w-16 h-16 bg-purple-50 rounded-xl flex flex-col items-center justify-center shrink-0 border border-purple-100">
              <span class="text-xl font-bold text-purple-600">{{ apt.day }}</span>
              <span class="text-xs font-bold text-purple-400 uppercase tracking-wider">{{ apt.month }}</span>
            </div>
            <div class="flex-1">
              <div class="font-bold text-lg text-gray-800">{{ apt.teacher }}</div>
              <div class="text-sm text-gray-500 font-medium flex items-center gap-2">
                  <span>{{ apt.subject }}</span>
                  <span class="text-gray-300">|</span>
                  <span class="flex items-center gap-1"><i class="pi pi-clock"></i> {{ apt.time }}</span>
              </div>
              <div class="text-sm text-gray-600 mt-2 bg-gray-50 p-2 rounded-lg inline-block border border-gray-100">{{ apt.reason }}</div>
            </div>
            <div>
              <span class="px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-full shadow-sm"
                    [ngClass]="{
                      'bg-green-100 text-green-700 border border-green-200': apt.status === 'confirmed',
                      'bg-orange-100 text-orange-700 border border-orange-200': apt.status === 'pending',
                      'bg-red-100 text-red-700 border border-red-200': apt.status === 'cancelled'
                    }">
                {{ getStatusLabel(apt.status) }}
              </span>
            </div>
          </div>
          <div *ngIf="appointments().length === 0" class="p-12 text-center text-gray-500">
            <i class="pi pi-calendar-times text-4xl text-gray-300 mb-3"></i>
            <p>Aucun rendez-vous prévu</p>
          </div>
        </div>
      </div>

      <!-- Available Teachers -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2"><i class="pi pi-users"></i> Enseignants disponibles</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div *ngFor="let teacher of teachers()" 
               class="p-5 border border-gray-100 rounded-xl hover:border-purple-500 hover:shadow-md transition-all cursor-pointer group bg-gray-50/50 hover:bg-white"
               (click)="requestAppointment(teacher)">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 font-bold shadow-sm group-hover:border-purple-200 group-hover:text-purple-600 transition">
                {{ teacher.name.charAt(0) }}
              </div>
              <div>
                <div class="font-bold text-gray-800 group-hover:text-purple-700 transition">{{ teacher.name }}</div>
                <div class="text-xs text-gray-500 font-medium uppercase tracking-wide">{{ teacher.subject }}</div>
              </div>
            </div>
            <div class="mt-4 flex items-center text-sm font-bold text-green-600 bg-green-50 py-1 px-2 rounded-lg w-fit">
              <i class="pi pi-clock mr-1"></i>
              {{ teacher.availableSlots }} créneau(x)
            </div>
          </div>
        </div>
      </div>

      <!-- New Request Modal -->
      <div *ngIf="showNewRequest" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showNewRequest = false">
        <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl overflow-hidden" (click)="$event.stopPropagation()">
          <div class="bg-purple-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">Demander un RDV</h3>
            <button (click)="showNewRequest = false" class="text-white/80 hover:text-white transition"><i class="pi pi-times text-lg"></i></button>
          </div>
          <form (ngSubmit)="submitRequest()" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Enseignant</label>
              <select [(ngModel)]="newRequest.teacherId" name="teacher" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 bg-white transition">
                <option value="">Sélectionner un enseignant</option>
                <option *ngFor="let t of teachers()" [value]="t.id">{{ t.name }} - {{ t.subject }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Enfant concerné</label>
              <select [(ngModel)]="newRequest.childId" name="child" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 bg-white transition">
                <option *ngFor="let c of children()" [value]="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Créneau souhaité</label>
              <select [(ngModel)]="newRequest.slotId" name="slot" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 bg-white transition">
                <option value="">Sélectionner un créneau</option>
                <option *ngFor="let s of availableSlots()" [value]="s.id">{{ s.day }} {{ s.date }} à {{ s.time }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Motif</label>
              <textarea [(ngModel)]="newRequest.reason" name="reason" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 transition"
                        placeholder="Décrivez brièvement le sujet du rendez-vous..."></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
              <button type="button" (click)="showNewRequest = false" class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50 transition">Annuler</button>
              <button type="submit" class="px-5 py-2.5 bg-purple-600 text-white rounded-xl font-bold hover:bg-purple-700 transition flex items-center gap-2">
                <i class="pi pi-send"></i> Envoyer
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  `
})
export class ParentAppointmentsComponent {
  showNewRequest = false;
  newRequest = { teacherId: '', childId: '1', slotId: '', reason: '' };
  
  showSuccessToast = false;
  successMessage = '';

  children = signal([
    { id: '1', name: 'Amadou Diallo' },
    { id: '2', name: 'Fatou Diallo' },
  ]);

  appointments = signal<any[]>([
    { id: 1, teacher: 'M. Ouédraogo', subject: 'Mathématiques', day: '26', month: 'Déc', time: '16:30', reason: 'Suivi des résultats', status: 'confirmed' },
    { id: 2, teacher: 'Mme Sawadogo', subject: 'Français', day: '28', month: 'Déc', time: '17:00', reason: 'Orientation', status: 'pending' },
  ]);

  teachers = signal([
    { id: 1, name: 'M. Ouédraogo', subject: 'Mathématiques', availableSlots: 5 },
    { id: 2, name: 'Mme Sawadogo', subject: 'Français', availableSlots: 3 },
    { id: 3, name: 'M. Kaboré', subject: 'Histoire-Géo', availableSlots: 4 },
    { id: 4, name: 'M. Traoré', subject: 'SVT', availableSlots: 2 },
  ]);

  availableSlots = signal([
    { id: 1, day: 'Lundi', date: '30/12', time: '16:00' },
    { id: 2, day: 'Mardi', date: '31/12', time: '16:30' },
    { id: 3, day: 'Jeudi', date: '02/01', time: '17:00' },
  ]);

  getStatusLabel(status: string) {
    return { confirmed: 'Confirmé', pending: 'En attente', cancelled: 'Annulé' }[status] || status;
  }

  requestAppointment(teacher: any) {
    this.newRequest.teacherId = teacher.id;
    this.showNewRequest = true;
  }

  submitRequest() {
    this.showToast('Demande de RDV envoyée !');
    
    // Simulate adding request
    const teacher = this.teachers().find(t => t.id === Number(this.newRequest.teacherId));
    if (teacher) {
        this.appointments.update(list => [{
            id: Date.now(),
            teacher: teacher.name,
            subject: teacher.subject,
            day: '30', // Mock
            month: 'Déc',
            time: '16:00',
            reason: this.newRequest.reason,
            status: 'pending'
        }, ...list]);
    }

    this.showNewRequest = false;
    this.newRequest = { teacherId: '', childId: '1', slotId: '', reason: '' };
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
