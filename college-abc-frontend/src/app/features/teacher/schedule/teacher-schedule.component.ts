import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-teacher-schedule',
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
          <h1 class="text-2xl font-bold text-gray-800">Mon Emploi du Temps</h1>
          <p class="text-gray-500">Semaine du {{ weekStart }} au {{ weekEnd }}</p>
        </div>
        <div class="flex gap-2">
          <button (click)="previousWeek()" class="p-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            <i class="pi pi-chevron-left"></i>
          </button>
          <button (click)="today()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition">Aujourd'hui</button>
          <button (click)="nextWeek()" class="p-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            <i class="pi pi-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- Weekly View -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="grid grid-cols-6 border-b border-gray-100">
          <div class="p-3 bg-gray-50 font-bold text-gray-500 text-center border-r border-gray-100 flex items-center justify-center">Heures</div>
          <div *ngFor="let day of days" class="p-3 bg-gray-50 text-center border-r border-gray-100 last:border-r-0 transition-colors"
               [ngClass]="{'bg-indigo-50': isToday(day.date)}">
            <div class="font-bold" [ngClass]="{'text-indigo-600': isToday(day.date)}">{{ day.name }}</div>
            <div class="text-xs text-gray-500 font-medium">{{ day.date }}</div>
          </div>
        </div>
        <div class="divide-y divide-gray-100">
          <div *ngFor="let slot of timeSlots" class="grid grid-cols-6 min-h-[100px]">
            <div class="p-2 bg-gray-50 text-center text-xs font-bold text-gray-500 border-r border-gray-100 flex items-center justify-center">{{ slot }}</div>
            <div *ngFor="let day of days" class="p-1 border-r border-gray-100 last:border-r-0 relative group transition-colors hover:bg-gray-50/50"
                 [ngClass]="{'bg-indigo-50/30': isToday(day.date)}">
              <div *ngFor="let course of getCoursesForSlot(day.name, slot)"
                   class="absolute inset-1 rounded-lg p-2 text-white text-xs cursor-pointer hover:opacity-90 transition shadow-sm hover:shadow-md flex flex-col justify-center"
                   [style.background-color]="course.color"
                   (click)="viewCourse(course)">
                <div class="font-bold truncate">{{ course.subject }}</div>
                <div class="truncate">{{ course.class }}</div>
                <div class="opacity-90 truncate text-[10px]"><i class="pi pi-map-marker mr-1"></i>{{ course.room }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Today's Classes Summary -->
      <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h2 class="font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="pi pi-calendar"></i> Cours du jour</h2>
        <div class="space-y-3">
          <div *ngFor="let course of todaysCourses()" class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl border border-gray-100 hover:shadow-sm transition">
            <div class="w-1.5 h-12 rounded-full" [style.background-color]="course.color"></div>
            <div class="flex-1">
              <div class="font-bold text-gray-800">{{ course.subject }} <span class="text-gray-400 font-normal">|</span> {{ course.class }}</div>
              <div class="text-sm text-gray-500 font-medium flex items-center gap-3">
                  <span><i class="pi pi-clock mr-1"></i>{{ course.time }}</span>
                  <span><i class="pi pi-map-marker mr-1"></i>{{ course.room }}</span>
              </div>
            </div>
            <button (click)="viewCourse(course)" class="px-4 py-2 text-indigo-600 hover:bg-indigo-50 rounded-lg text-sm font-bold transition">
              Détails
            </button>
          </div>
          <div *ngIf="todaysCourses().length === 0" class="text-center text-gray-500 py-8 bg-gray-50 rounded-xl border border-dashed border-gray-200">
            <i class="pi pi-calendar-times text-2xl mb-2 text-gray-400"></i>
            <p>Aucun cours prévu aujourd'hui</p>
          </div>
        </div>
      </div>

      <!-- Course Details Modal -->
      <div *ngIf="showCourseModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showCourseModal = false">
        <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden" (click)="$event.stopPropagation()">
          <div class="h-24 flex items-end p-6 relative" [style.background-color]="selectedCourse?.color || '#4F46E5'">
            <button (click)="showCourseModal = false" class="absolute top-4 right-4 text-white/80 hover:text-white transition bg-black/10 hover:bg-black/20 rounded-full p-2"><i class="pi pi-times"></i></button>
            <div>
                <h3 class="text-2xl font-black text-white shadow-sm">{{ selectedCourse?.subject }}</h3>
                <p class="text-white/90 font-medium">{{ selectedCourse?.class }}</p>
            </div>
          </div>
          <div class="p-6 space-y-4">
            <div class="flex items-center gap-4 text-gray-700">
              <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500">
                <i class="pi pi-clock text-xl"></i>
              </div>
              <div>
                <p class="text-xs text-gray-500 font-bold uppercase">Horaire</p>
                <p class="font-medium">{{ selectedCourse?.time }}</p>
                <p class="text-sm text-gray-500">{{ selectedCourse?.day }}</p>
              </div>
            </div>
             <div class="flex items-center gap-4 text-gray-700">
              <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500">
                <i class="pi pi-map-marker text-xl"></i>
              </div>
              <div>
                <p class="text-xs text-gray-500 font-bold uppercase">Salle</p>
                <p class="font-medium">{{ selectedCourse?.room }}</p>
              </div>
            </div>
            <div class="pt-2">
                <button (click)="showCourseModal = false" class="w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl font-bold transition">Fermer</button>
            </div>
          </div>
        </div>
      </div>

    </div>
  `
})
export class TeacherScheduleComponent {
  weekStart = '23/12';
  weekEnd = '27/12';
  
  showCourseModal = false;
  selectedCourse: any = null;
  showSuccessToast = false;
  successMessage = '';

  days = [
    { name: 'Lundi', date: '23/12' },
    { name: 'Mardi', date: '24/12' },
    { name: 'Mercredi', date: '25/12' },
    { name: 'Jeudi', date: '26/12' },
    { name: 'Vendredi', date: '27/12' },
  ];

  timeSlots = ['08:00-09:00', '09:00-10:00', '10:15-11:15', '11:15-12:15', '15:00-16:00', '16:00-17:00'];

  schedule = signal([
    { day: 'Lundi', time: '08:00-09:00', subject: 'Maths', class: '6ème A', room: 'Salle 101', color: '#4F46E5' },
    { day: 'Lundi', time: '09:00-10:00', subject: 'Maths', class: '5ème B', room: 'Salle 101', color: '#4F46E5' },
    { day: 'Lundi', time: '15:00-16:00', subject: 'Maths', class: '4ème A', room: 'Salle 102', color: '#4F46E5' },
    { day: 'Mardi', time: '10:15-11:15', subject: 'Maths', class: '6ème A', room: 'Salle 101', color: '#4F46E5' },
    { day: 'Mardi', time: '16:00-17:00', subject: 'Maths', class: '3ème A', room: 'Salle 103', color: '#4F46E5' },
    { day: 'Mercredi', time: '08:00-09:00', subject: 'Maths', class: '5ème B', room: 'Salle 101', color: '#4F46E5' },
    { day: 'Jeudi', time: '09:00-10:00', subject: 'Maths', class: '4ème A', room: 'Salle 102', color: '#4F46E5' },
    { day: 'Jeudi', time: '11:15-12:15', subject: 'Maths', class: '6ème A', room: 'Salle 101', color: '#4F46E5' },
    { day: 'Vendredi', time: '10:15-11:15', subject: 'Maths', class: '3ème A', room: 'Salle 103', color: '#4F46E5' },
  ]);

  todaysCourses = () => {
    const today = 'Lundi'; // Would be dynamic in a real app
    return this.schedule().filter(c => c.day === today);
  };

  isToday(date: string) { return date === '23/12'; }

  getCoursesForSlot(day: string, time: string) {
    return this.schedule().filter(c => c.day === day && c.time === time);
  }

  previousWeek() { 
      this.showToast('Semaine précédente (simulation)');
  }
  nextWeek() { 
      this.showToast('Semaine suivante (simulation)');
  }
  today() { 
      this.showToast('Retour à aujourd\'hui');
  }
  
  viewCourse(course: any) { 
      this.selectedCourse = course;
      this.showCourseModal = true;
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
