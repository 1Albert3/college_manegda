import { Component, signal, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ScheduleService, CourseSlot } from '../../../core/services/schedule.service';
import { AuthService } from '../../../core/services/auth.service';

interface ScheduleWithStatus extends CourseSlot {
  passed?: boolean;
}

@Component({
  selector: 'app-student-schedule',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="space-y-6 relative">
      <!-- Course Details Modal -->
      <div *ngIf="showCourseModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showCourseModal = false">
        <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden animate-fade-in-up" (click)="$event.stopPropagation()">
          <div class="h-32 flex items-end p-6 relative" [style.background-color]="selectedCourse?.color || '#9333ea'">
            <button (click)="showCourseModal = false" class="absolute top-4 right-4 text-white/80 hover:text-white transition bg-black/10 hover:bg-black/20 rounded-full p-2"><i class="pi pi-times"></i></button>
            <div class="w-full">
                <h3 class="text-2xl font-black text-white shadow-sm mb-1 leading-tight">{{ selectedCourse?.subject }}</h3>
                <p class="text-white/90 font-medium flex items-center gap-2"><i class="pi pi-user"></i> {{ selectedCourse?.teacher }}</p>
            </div>
          </div>
          <div class="p-6 space-y-5">
            <div class="flex items-center gap-4 text-gray-700">
              <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center text-purple-600">
                <i class="pi pi-clock text-xl"></i>
              </div>
              <div>
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Horaire</p>
                <p class="font-bold text-gray-900 text-lg">{{ selectedCourse?.startTime }} - {{ selectedCourse?.endTime }}</p>
                <p class="text-sm text-gray-500 font-medium">{{ selectedCourse?.day }}</p>
              </div>
            </div>
             <div class="flex items-center gap-4 text-gray-700">
              <div class="w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center text-purple-600">
                <i class="pi pi-map-marker text-xl"></i>
              </div>
              <div>
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Salle</p>
                <p class="font-bold text-gray-900 text-lg">{{ selectedCourse?.room }}</p>
              </div>
            </div>
            <div class="pt-2">
                <button (click)="showCourseModal = false" class="w-full py-3 bg-gray-50 hover:bg-gray-100 text-gray-800 rounded-xl font-bold transition border border-gray-200">Fermer</button>
            </div>
          </div>
        </div>
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
          <button (click)="goToToday()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-bold shadow-sm transition">Aujourd'hui</button>
          <button (click)="nextWeek()" class="p-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            <i class="pi pi-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div *ngIf="loading()" class="bg-white rounded-xl p-12 text-center border border-gray-100">
        <i class="pi pi-spin pi-spinner text-4xl text-purple-600 mb-4"></i>
        <p class="text-gray-500">Chargement de l'emploi du temps...</p>
      </div>

      <!-- Weekly Schedule -->
      <div *ngIf="!loading()" class="bg-white rounded-xl shadow-sm overflow-x-auto border border-gray-100">
        <table class="w-full min-w-[800px]">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100">
              <th class="p-3 text-left text-sm font-bold text-gray-500 border-r border-gray-100 w-24 pl-5">Heures</th>
              <th *ngFor="let day of days" class="p-3 text-center text-sm font-bold border-r border-gray-100 last:border-r-0 transition-colors"
                  [ngClass]="{'bg-purple-100 text-purple-700': day.isToday, 'text-gray-700': !day.isToday}">
                <div class="uppercase tracking-wide text-xs mb-1 opacity-80">{{ day.name }}</div>
                <div class="text-sm font-black">{{ day.date }}</div>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr *ngFor="let slot of timeSlots" class="border-b border-gray-100 last:border-0 hover:bg-gray-50/50 transition">
              <td class="p-2 text-center text-xs font-bold text-gray-500 bg-gray-50 border-r border-gray-100">{{ slot }}</td>
              <td *ngFor="let day of days" class="p-1 border-r border-gray-100 last:border-r-0 h-28 align-top relative"
                  [ngClass]="{'bg-purple-50/30': day.isToday}">
                <div *ngFor="let course of getCourse(day.name, slot)"
                     class="m-1 p-2 rounded-lg text-xs h-[calc(100%-8px)] cursor-pointer hover:opacity-90 transition shadow-sm hover:shadow active:scale-[0.98] flex flex-col justify-center"
                     [style.background-color]="course.color + '15'"
                     [style.border-left]="'3px solid ' + course.color"
                     (click)="viewCourse(course)">
                  <div class="font-bold mb-1 truncate leading-tight" [style.color]="course.color">{{ course.subject }}</div>
                  <div class="text-gray-600 truncate mb-0.5"><i class="pi pi-user text-[9px] mr-1"></i>{{ course.teacher }}</div>
                  <div class="text-gray-500 truncate"><i class="pi pi-map-marker text-[9px] mr-1"></i>{{ course.room }}</div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Next Class Card -->
      <div *ngIf="nextClass() && !loading()" class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl p-6 text-white shadow-lg shadow-purple-200">
        <div class="flex items-center gap-5">
          <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center shrink-0 backdrop-blur-sm">
            <i class="pi pi-clock text-3xl"></i>
          </div>
          <div class="flex-1">
            <p class="text-purple-100 text-xs font-bold uppercase tracking-wider mb-1">Prochain cours</p>
            <p class="text-2xl font-black">{{ nextClass()?.subject }}</p>
            <p class="text-white/90 font-medium flex items-center gap-2 mt-1"><i class="pi pi-clock"></i> {{ nextClass()?.time }} <span class="opacity-50">|</span> <i class="pi pi-map-marker"></i> {{ nextClass()?.room }}</p>
          </div>
          <div class="text-right bg-white/10 px-4 py-2 rounded-xl backdrop-blur-sm border border-white/10">
            <p class="text-4xl font-black">{{ nextClass()?.countdown }}</p>
            <p class="text-purple-100 text-xs font-bold uppercase">minutes</p>
          </div>
        </div>
      </div>

      <!-- Today Summary -->
      <div *ngIf="!loading()" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h2 class="font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="pi pi-list"></i> Programme du jour</h2>
        <div class="space-y-3">
          <div *ngFor="let course of todaysCourses(); let i = index" 
               class="flex items-center gap-4 p-4 rounded-xl transition"
               [ngClass]="course.passed ? 'bg-gray-50 opacity-60' : 'bg-purple-50/50 border border-purple-100'">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shadow-sm"
                 [ngClass]="course.passed ? 'bg-gray-200 text-gray-500' : 'bg-purple-500 text-white'">
              {{ i + 1 }}
            </div>
            <div class="w-1.5 h-10 rounded-full" [style.background-color]="course.color"></div>
            <div class="flex-1">
              <div class="font-bold text-lg" [ngClass]="course.passed ? 'text-gray-500' : 'text-gray-800'">{{ course.subject }}</div>
              <div class="text-sm font-medium" [ngClass]="course.passed ? 'text-gray-400' : 'text-gray-500'">{{ course.startTime }} - {{ course.endTime }}</div>
            </div>
            <div class="text-right text-sm text-gray-600">
              <div class="font-medium">{{ course.teacher }}</div>
              <div class="text-gray-400 font-medium"><i class="pi pi-map-marker mr-1"></i>{{ course.room }}</div>
            </div>
          </div>
          <div *ngIf="todaysCourses().length === 0" class="text-center text-gray-500 py-8">
            <p class="font-medium">Pas de cours aujourd'hui</p>
          </div>
        </div>
      </div>
    </div>
  `
})
export class StudentScheduleComponent implements OnInit {
  private scheduleService = inject(ScheduleService);
  private authService = inject(AuthService);

  weekStart = '';
  weekEnd = '';
  currentWeekOffset = 0;
  
  showCourseModal = false;
  selectedCourse: ScheduleWithStatus | null = null;

  days: { name: string; date: string; isToday: boolean }[] = [];
  timeSlots = ['08:00-09:00', '09:00-10:00', '10:15-11:15', '11:15-12:15', '15:00-16:00', '16:00-17:00'];

  schedule = signal<ScheduleWithStatus[]>([]);
  loading = signal(true);

  nextClass = signal<{ subject: string; time: string; room: string; countdown: number } | null>(null);

  ngOnInit() {
    this.calculateWeek();
    this.loadSchedule();
    this.updateNextClass();
  }

  calculateWeek() {
    const now = new Date();
    now.setDate(now.getDate() + (this.currentWeekOffset * 7));
    
    const monday = new Date(now);
    monday.setDate(now.getDate() - now.getDay() + 1);
    
    const today = new Date();
    const dayNames = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
    
    this.days = dayNames.map((name, i) => {
      const date = new Date(monday);
      date.setDate(monday.getDate() + i);
      return {
        name,
        date: `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1).toString().padStart(2, '0')}`,
        isToday: date.toDateString() === today.toDateString()
      };
    });

    this.weekStart = this.days[0].date;
    this.weekEnd = this.days[4].date;
  }

  loadSchedule() {
    this.loading.set(true);
    
    // Get student ID from auth service
    const user = this.authService.currentUser();
    const studentId = user?.student_id || user?.id || 1;

    this.scheduleService.getSchedule(studentId).subscribe({
      next: (courses) => {
        // Mark passed courses
        const now = new Date();
        const currentHour = now.getHours();
        const todayName = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'][now.getDay()];

        const scheduleCourses: ScheduleWithStatus[] = courses.map(c => {
          const courseHour = parseInt(c.startTime?.split(':')[0] || '0');
          const passed = c.day === todayName && courseHour < currentHour;
          return { ...c, passed };
        });

        this.schedule.set(scheduleCourses);
        this.loading.set(false);
        this.updateNextClass();
      },
      error: (err) => {
        console.error('Error loading schedule:', err);
        // Fallback to mock data
        this.schedule.set([
          { day: 'Lundi', startTime: '08:00', endTime: '09:00', subject: 'Maths', teacher: 'M. Ouédraogo', room: 'Salle 101', color: '#4F46E5', passed: true },
          { day: 'Lundi', startTime: '09:00', endTime: '10:00', subject: 'Français', teacher: 'Mme Sawadogo', room: 'Salle 102', color: '#DC2626', passed: true },
          { day: 'Lundi', startTime: '10:15', endTime: '11:15', subject: 'Histoire', teacher: 'M. Kaboré', room: 'Salle 103', color: '#059669', passed: false },
          { day: 'Lundi', startTime: '15:00', endTime: '16:00', subject: 'Anglais', teacher: 'Mme Diallo', room: 'Salle 104', color: '#7C3AED', passed: false },
          { day: 'Mardi', startTime: '08:00', endTime: '09:00', subject: 'SVT', teacher: 'M. Traoré', room: 'Labo', color: '#16A34A', passed: false },
          { day: 'Mardi', startTime: '09:00', endTime: '10:00', subject: 'Physique', teacher: 'M. Koné', room: 'Labo', color: '#D97706', passed: false },
          { day: 'Mercredi', startTime: '08:00', endTime: '09:00', subject: 'Français', teacher: 'Mme Sawadogo', room: 'Salle 102', color: '#DC2626', passed: false },
        ]);
        this.loading.set(false);
      }
    });
  }

  updateNextClass() {
    const now = new Date();
    const currentHour = now.getHours();
    const currentMinute = now.getMinutes();
    const todayName = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'][now.getDay()];

    const todaysCourses = this.schedule().filter(c => c.day === todayName && !c.passed);
    
    if (todaysCourses.length > 0) {
      const next = todaysCourses[0];
      const [hour, minute] = (next.startTime || '08:00').split(':').map(Number);
      const countdown = Math.max(0, (hour * 60 + minute) - (currentHour * 60 + currentMinute));
      
      this.nextClass.set({
        subject: next.subject,
        time: `${next.startTime} - ${next.endTime}`,
        room: next.room,
        countdown
      });
    } else {
      this.nextClass.set(null);
    }
  }

  todaysCourses = () => {
    const todayName = this.days.find(d => d.isToday)?.name || 'Lundi';
    return this.schedule().filter(c => c.day === todayName);
  };

  getCourse(day: string, timeSlot: string) {
    const [start] = timeSlot.split('-');
    return this.schedule().filter(c => c.day === day && c.startTime?.startsWith(start.split(':')[0]));
  }

  previousWeek() {
    this.currentWeekOffset--;
    this.calculateWeek();
    this.loadSchedule();
  }

  nextWeek() {
    this.currentWeekOffset++;
    this.calculateWeek();
    this.loadSchedule();
  }

  goToToday() {
    this.currentWeekOffset = 0;
    this.calculateWeek();
    this.loadSchedule();
  }

  viewCourse(course: ScheduleWithStatus) {
    this.selectedCourse = course;
    this.showCourseModal = true;
  }
}
