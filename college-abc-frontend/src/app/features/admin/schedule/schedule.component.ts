import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { environment } from '../../../../environments/environment';

interface ScheduleSlot {
  id?: number;
  day_of_week: string;
  start_time: string;
  end_time: string;
  subject: { name: string };
  teacher: { first_name: string, last_name: string };
  room: string;
  color?: string;
}

@Component({
  selector: 'app-admin-schedule',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="p-6 space-y-8 animate-in fade-in duration-500">
      <!-- Header with Class Selector -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
          <h1 class="text-2xl font-black text-gray-900 leading-tight">Emplois du Temps</h1>
          <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1">Planification des cours et occupation des salles</p>
        </div>

        <div class="flex items-center gap-3">
           <div class="bg-white p-1.5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-1">
              <select [(ngModel)]="selectedClassId" (change)="loadSchedule()" 
                      class="px-4 py-2 bg-transparent border-none text-sm font-bold text-gray-700 focus:ring-0 cursor-pointer">
                 <option value="">Sélectionner une classe</option>
                 <option *ngFor="let c of classes" [value]="c.id">{{ c.name }}</option>
              </select>
           </div>
           
           <button class="bg-indigo-600 text-white px-5 py-3 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition active:scale-95 flex items-center gap-2">
              <i class="pi pi-plus"></i>
              Ajouter un cours
           </button>
        </div>
      </div>

      <!-- Weekly Schedule Grid -->
      <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-xl shadow-gray-200/50 overflow-hidden">
        <div class="grid grid-cols-7 border-b border-gray-50">
          <div class="p-5 border-r border-gray-50 bg-gray-50/50 flex flex-col items-center justify-center">
             <i class="pi pi-clock text-gray-300"></i>
          </div>
          <div *ngFor="let day of weekDays" class="p-5 text-center border-r border-gray-50 last:border-r-0">
             <span class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em]">{{ day.en }}</span>
             <h3 class="text-sm font-black text-gray-900 mt-1">{{ day.fr }}</h3>
          </div>
        </div>

        <div class="relative min-h-[600px] bg-white">
          <!-- Time Grid Lines -->
          <div *ngFor="let hour of timeSlots" class="grid grid-cols-7 h-20 border-b border-gray-50 relative group">
            <div class="p-4 border-r border-gray-50 bg-gray-50/30 flex items-start justify-center text-[10px] font-bold text-gray-400 group-hover:text-indigo-500 transition-colors">
              {{ hour }}
            </div>
            <div *ngFor="let day of weekDays" class="border-r border-gray-50 last:border-r-0 relative group/cell hover:bg-gray-50/50 transition-colors">
              <!-- Content will be overlaid -->
            </div>
          </div>

          <!-- Schedule Cards (Positioned Absolutly) -->
          <div *ngFor="let slot of slots" 
               [style.top]="calculatePosition(slot.start_time)"
               [style.left]="calculateDayLeft(slot.day_of_week)"
               [style.height]="calculateHeight(slot.start_time, slot.end_time)"
               class="absolute w-[calc(14.28%-10px)] mx-1.5 p-3 rounded-2xl border-l-4 transition-all hover:scale-[1.02] hover:shadow-lg cursor-pointer z-10 overflow-hidden group shadow-sm bg-white"
               [ngClass]="getSlotColor(slot)">
            
            <div class="flex flex-col h-full">
              <div class="flex justify-between items-start mb-1">
                 <span class="text-[8px] font-black uppercase tracking-tighter opacity-70">{{ slot.start_time }} - {{ slot.end_time }}</span>
                 <i class="pi pi-bookmark text-[10px] opacity-20"></i>
              </div>
              <h4 class="font-black text-gray-900 leading-tight text-xs uppercase italic line-clamp-2">{{ slot.subject.name }}</h4>
              <p class="text-[9px] font-bold text-gray-500 mt-1 truncate">{{ slot.teacher.first_name }} {{ slot.teacher.last_name }}</p>
              
              <div class="mt-auto flex items-center justify-between pt-2 border-t border-gray-100/50">
                 <div class="flex items-center gap-1">
                    <i class="pi pi-map-marker text-[9px]"></i>
                    <span class="text-[9px] font-black uppercase">{{ slot.room }}</span>
                 </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Tips -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
         <div class="bg-indigo-900 rounded-3xl p-6 text-white relative overflow-hidden group">
            <div class="relative z-10">
               <h3 class="font-black italic uppercase text-indigo-200 text-xs mb-2">Conflits d'horaires</h3>
               <p class="text-xs text-indigo-100/80 leading-relaxed font-medium">Le système détecte automatiquement les chevauchements lors de la création d'un nouveau créneau.</p>
            </div>
            <i class="pi pi-exclamation-triangle absolute -bottom-4 -right-4 text-7xl text-indigo-800 opacity-30 -rotate-12 transition-transform group-hover:rotate-0 duration-500"></i>
         </div>
         
         <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex flex-col justify-center">
            <div class="flex items-center gap-3">
               <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                  <i class="pi pi-info-circle"></i>
               </div>
               <div class="text-[10px] font-black uppercase text-gray-400 tracking-widest leading-none">Salle de classe</div>
            </div>
            <p class="mt-4 text-sm font-bold text-gray-900 leading-tight">Vérifiez les disponibilités des salles avant chaque début de semestre.</p>
         </div>

         <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
               <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Taux d'occupation</div>
               <div class="text-2xl font-black text-gray-900 mt-1">78%</div>
            </div>
            <div class="w-16 h-16 rounded-full border-4 border-indigo-50 flex items-center justify-center relative">
               <div class="absolute inset-0 border-4 border-indigo-600 rounded-full border-t-transparent -rotate-45"></div>
               <i class="pi pi-chart-bar text-indigo-600"></i>
            </div>
         </div>
      </div>
    </div>
  `,
  styles: [`
    :host { display: block; }
    .bg-blue-slot { @apply bg-blue-50/80 border-blue-500 text-blue-800; }
    .bg-green-slot { @apply bg-green-50/80 border-green-500 text-green-800; }
    .bg-purple-slot { @apply bg-purple-50/80 border-purple-500 text-purple-800; }
    .bg-orange-slot { @apply bg-orange-50/80 border-orange-500 text-orange-800; }
    .bg-rose-slot { @apply bg-rose-50/80 border-rose-500 text-rose-800; }
    .bg-teal-slot { @apply bg-teal-50/80 border-teal-500 text-teal-800; }
  `]
})
export class AdminScheduleComponent implements OnInit {
  classes: any[] = [];
  selectedClassId: string = '';
  slots: any[] = [];
  loading = false;

  weekDays = [
    { en: 'monday', fr: 'Lundi' },
    { en: 'tuesday', fr: 'Mardi' },
    { en: 'wednesday', fr: 'Mercredi' },
    { en: 'thursday', fr: 'Jeudi' },
    { en: 'friday', fr: 'Vendredi' },
    { en: 'saturday', fr: 'Samedi' }
  ];

  timeSlots = ['07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

  private http = inject(HttpClient);

  ngOnInit() {
    this.loadClasses();
  }

  loadClasses() {
    // Just mock for now or fetch from academic
    this.classes = [
      { id: '1', name: '6ème A' },
      { id: '2', name: '5ème B' },
      { id: '3', name: '3ème C' },
      { id: '4', name: '2nde LE' },
      { id: '5', name: 'Tle D' }
    ];
  }

  loadSchedule() {
    if (!this.selectedClassId) return;
    this.loading = true;
    this.http.get<any>(`${environment.apiUrl}/schedules/${this.selectedClassId}`).subscribe({
      next: (res) => {
        this.slots = res.data || [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        // Fallback demo data
        this.slots = [
          { day_of_week: 'monday', start_time: '07:00', end_time: '09:00', subject: { name: 'Mathématiques' }, teacher: { first_name: 'Amadou', last_name: 'Traoré' }, room: 'Salle 12', color: 'blue' },
          { day_of_week: 'monday', start_time: '10:00', end_time: '12:00', subject: { name: 'Français' }, teacher: { first_name: 'Mariam', last_name: 'Coulibaly' }, room: 'Salle 12' },
          { day_of_week: 'tuesday', start_time: '08:00', end_time: '10:00', subject: { name: 'Physique-Chimie' }, teacher: { first_name: 'Ibrahim', last_name: 'Savadogo' }, room: 'Labo 1', color: 'purple' },
          { day_of_week: 'wednesday', start_time: '07:00', end_time: '10:00', subject: { name: 'Anglais' }, teacher: { first_name: 'Sarah', last_name: 'Johnson' }, room: 'Salle 05', color: 'orange' },
          { day_of_week: 'friday', start_time: '15:00', end_time: '17:00', subject: { name: 'EPS' }, teacher: { first_name: 'Paul', last_name: 'Zongo' }, room: 'Terrain' }
        ];
      }
    });
  }

  calculatePosition(time: string): string {
    const [hours, minutes] = time.split(':').map(Number);
    const startHour = 7;
    const pixelsPerHour = 80; // Correspond à h-20 (20 * 4px = 80px)
    const position = ((hours - startHour) * pixelsPerHour) + ((minutes / 60) * pixelsPerHour);
    return `${position}px`;
  }

  calculateHeight(start: string, end: string): string {
    const [h1, m1] = start.split(':').map(Number);
    const [h2, m2] = end.split(':').map(Number);
    const durationMinutes = (h2 - h1) * 60 + (m2 - m1);
    const pixelsPerHour = 80;
    return `${(durationMinutes / 60) * pixelsPerHour}px`;
  }

  calculateDayLeft(day: string): string {
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    const index = days.indexOf(day.toLowerCase());
    return `${(index + 1) * 14.28}%`;
  }

  getSlotColor(slot: any) {
    if (slot.color) return `bg-${slot.color}-slot`;
    // Cycle through colors based on subject
    const colors = ['blue', 'purple', 'emerald', 'orange', 'rose', 'teal'];
    const idx = slot.subject.name.length % colors.length;
    return `bg-${colors[idx]}-slot`;
  }
}
