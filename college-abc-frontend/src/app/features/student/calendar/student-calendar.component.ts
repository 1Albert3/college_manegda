import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-student-calendar',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6 relative">
      <!-- Toast Notification -->
      <div *ngIf="showSuccessToast" class="fixed top-4 right-4 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-xl z-[100] flex items-center gap-3 transition-opacity duration-300">
        <i class="pi pi-calendar text-xl text-purple-400"></i>
        <span class="font-medium">{{ successMessage }}</span>
      </div>

      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Calendrier Scolaire</h1>
          <p class="text-gray-500">Événements et dates importantes</p>
        </div>
        <div class="flex gap-2 items-center bg-white p-1 rounded-xl border border-gray-200 shadow-sm">
          <button (click)="previousMonth()" class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-600 transition">
            <i class="pi pi-chevron-left"></i>
          </button>
          <span class="px-4 font-bold text-gray-800 min-w-[140px] text-center">{{ currentMonth() }}</span>
          <button (click)="nextMonth()" class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-600 transition">
            <i class="pi pi-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- Upcoming Events -->
      <div class="bg-gradient-to-r from-purple-600 to-indigo-700 rounded-2xl p-6 text-white shadow-lg shadow-indigo-200">
        <h3 class="font-bold text-lg mb-4 flex items-center gap-2"><i class="pi pi-clock"></i> Prochains événements</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div *ngFor="let event of upcomingEvents()" class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10 hover:bg-white/20 transition">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 bg-white/20 rounded-xl flex flex-col items-center justify-center shadow-inner">
                <span class="text-xl font-bold">{{ event.day }}</span>
                <span class="text-[10px] font-bold uppercase tracking-wider">{{ event.month }}</span>
              </div>
              <div>
                <div class="font-bold text-lg leading-tight">{{ event.title }}</div>
                <div class="text-sm text-indigo-100 mt-1 font-medium bg-indigo-500/30 px-2 py-0.5 rounded w-fit">{{ event.type }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Calendar Grid -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="grid grid-cols-7 gap-3 mb-2">
           <div *ngFor="let day of weekDays" class="text-center text-xs font-bold text-gray-400 uppercase tracking-widest py-2">
            {{ day }}
          </div>
        </div>
        <div class="grid grid-cols-7 gap-3">
          <div *ngFor="let day of calendarDays()" 
               class="min-h-[100px] rounded-xl p-2 text-sm cursor-pointer border transition-all duration-200 group relative"
               [ngClass]="{
                   'bg-purple-50 border-purple-200 hover:border-purple-300': day.isToday, 
                   'border-gray-100 hover:border-gray-300 hover:shadow-md bg-white': !day.isToday,
                   'opacity-40 bg-gray-50': !day.date
               }"
               (click)="selectDay(day)">
            
            <div *ngIf="day.date" class="font-bold mb-2 flex justify-between items-center" 
                 [ngClass]="day.isToday ? 'text-purple-600' : 'text-gray-700'">
                <span>{{ day.date }}</span>
                <span *ngIf="day.isToday" class="w-2 h-2 rounded-full bg-purple-600"></span>
            </div>
            
            <div *ngIf="day.date" class="space-y-1">
                <div *ngFor="let event of day.events" 
                     class="px-2 py-1 text-[10px] font-bold rounded-lg truncate transition-all group-hover:scale-[1.02]"
                     [style.background-color]="event.color + '20'"
                     [style.color]="event.color">
                  {{ event.title }}
                </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Legend -->
      <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 flex items-center gap-6 overflow-x-auto">
        <h4 class="font-bold text-gray-700 text-sm uppercase tracking-wide">Légende :</h4>
        <div class="flex flex-wrap gap-4">
          <div *ngFor="let type of eventTypes()" class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200">
            <div class="w-3 h-3 rounded-full shadow-sm" [style.background-color]="type.color"></div>
            <span class="text-xs font-bold text-gray-600">{{ type.label }}</span>
          </div>
        </div>
      </div>

      <!-- Day Details Modal -->
      <div *ngIf="selectedDay" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="selectedDay = null">
        <div class="bg-white rounded-2xl w-full max-w-sm mx-4 shadow-2xl overflow-hidden transform transition-all scale-100" (click)="$event.stopPropagation()">
            <div class="bg-purple-600 px-6 py-4 flex justify-between items-center">
                <div>
                     <p class="text-purple-200 text-xs font-bold uppercase tracking-wide mb-0.5">Détails du jour</p>
                    <h3 class="text-xl font-bold text-white">{{ selectedDay.fullDate }}</h3>
                </div>
                <button (click)="selectedDay = null" class="text-white/80 hover:text-white transition bg-white/10 rounded-full w-8 h-8 flex items-center justify-center"><i class="pi pi-times"></i></button>
            </div>
          <div class="p-6">
            <div *ngIf="selectedDay.events.length > 0" class="space-y-3">
              <div *ngFor="let event of selectedDay.events" 
                   class="p-4 rounded-xl border-l-4 shadow-sm"
                   [style.background-color]="event.color + '05'"
                   [style.border-color]="event.color">
                <div class="font-bold text-lg" [style.color]="event.color">{{ event.title }}</div>
                <div class="text-sm text-gray-600 mt-1 leading-relaxed font-medium">{{ event.description }}</div>
                <div *ngIf="event.time" class="text-xs font-bold text-gray-400 mt-2 flex items-center gap-1 uppercase tracking-wide">
                  <i class="pi pi-clock"></i>{{ event.time }}
                </div>
              </div>
            </div>
            <div *ngIf="selectedDay.events.length === 0" class="text-center text-gray-400 py-8">
               <i class="pi pi-calendar-times text-4xl mb-2 opacity-50"></i>
               <p class="font-medium">Aucun événement ce jour</p>
            </div>
            
            <div class="mt-6 pt-4 border-t border-gray-100 text-center">
                <button (click)="selectedDay = null" class="text-gray-500 font-bold text-sm hover:text-gray-800 transition">Fermer</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  `
})
export class StudentCalendarComponent {
  selectedDay: any = null;
  weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
  
  showSuccessToast = false;
  successMessage = '';

  currentMonth = signal('Décembre 2024');

  eventTypes = signal([
    { type: 'exam', label: 'Examens', color: '#DC2626' },
    { type: 'holiday', label: 'Vacances', color: '#16A34A' },
    { type: 'event', label: 'Événements', color: '#7C3AED' },
    { type: 'deadline', label: 'Échéances', color: '#F59E0B' },
  ]);

  upcomingEvents = signal([
    { day: '25', month: 'Déc', title: 'Vacances de Noël', type: 'Début des vacances' },
    { day: '06', month: 'Jan', title: 'Rentrée', type: 'Reprise des cours' },
    { day: '15', month: 'Jan', title: 'Conseil de classe', type: 'Événement' },
  ]);

  calendarDays = signal([
    { date: 23, isToday: true, fullDate: '23 Décembre 2024', events: [{ title: 'Dernier jour', color: '#7C3AED', description: 'Dernier jour avant les vacances', time: '08:00' }] },
    { date: 24, isToday: false, fullDate: '24 Décembre 2024', events: [{ title: 'Veille Noël', color: '#16A34A', description: 'Vacances de Noël débutent', time: '' }] },
    { date: 25, isToday: false, fullDate: '25 Décembre 2024', events: [{ title: 'Noël', color: '#16A34A', description: 'Jour férié', time: '' }] },
    { date: 26, isToday: false, fullDate: '26 Décembre 2024', events: [] },
    { date: 27, isToday: false, fullDate: '27 Décembre 2024', events: [] },
    { date: 28, isToday: false, fullDate: '28 Décembre 2024', events: [{ title: 'Devoir Maths', color: '#F59E0B', description: 'À rendre en ligne', time: '23:59' }] },
    { date: 29, isToday: false, fullDate: '29 Décembre 2024', events: [] },
    { date: 30, isToday: false, fullDate: '30 Décembre 2024', events: [{ title: 'Devoir Français', color: '#F59E0B', description: 'Rédaction à rendre', time: '23:59' }] },
    { date: 31, isToday: false, fullDate: '31 Décembre 2024', events: [{ title: 'Réveillon', color: '#7C3AED', description: 'Saint Sylvestre', time: '' }] },
    ...Array(6).fill({ date: null, events: [] }),
  ]);

  previousMonth() { this.showToast('Chargement mois précédent (simulation)...'); }
  nextMonth() { this.showToast('Chargement mois suivant (simulation)...'); }
  
  selectDay(day: any) { if (day.date) this.selectedDay = day; }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 2000);
  }
}
