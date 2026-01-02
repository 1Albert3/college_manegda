import { Component, inject, signal, computed, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { StudentService, StudentDashboardData } from '../../../core/services/student.service';

@Component({
  selector: 'app-student-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink],
  template: `
    <div class="space-y-6" *ngIf="dashboardData() as data; else loading">
      
      <!-- Welcome Banner -->
      <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
        <div class="absolute top-0 right-0 w-48 h-48 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-20 w-32 h-32 bg-white/10 rounded-full translate-y-1/2"></div>
        <div class="relative z-10">
          <h1 class="text-2xl md:text-3xl font-bold mb-2">
            Salut {{ data.student.full_name.split(' ')[0] }} ! 🎓
          </h1>
          <p class="text-white/80">
            Tu as {{ data.upcoming_homework.length }} devoirs à rendre et {{ data.schedule_today.length }} cours aujourd'hui. Bonne journée !
          </p>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Ma Moyenne -->
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500 hover:shadow-md transition-all cursor-pointer" routerLink="/student/grades">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium">Ma Moyenne</p>
              <p class="text-3xl font-bold text-gray-800 mt-1">{{ data.grades_summary.moyenne_generale }}<span class="text-lg text-gray-400">/20</span></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
              <i class="pi pi-chart-line text-xl text-green-600"></i>
            </div>
          </div>
        </div>

        <!-- Devoirs à faire -->
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500 hover:shadow-md transition-all cursor-pointer" routerLink="/student/homework">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium">Devoirs</p>
              <p class="text-3xl font-bold text-gray-800 mt-1">{{ data.upcoming_homework.length }}</p>
              <p class="text-xs text-orange-600">à rendre</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center">
              <i class="pi pi-book text-xl text-orange-600"></i>
            </div>
          </div>
        </div>

        <!-- Cours aujourd'hui -->
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500 hover:shadow-md transition-all cursor-pointer" routerLink="/student/schedule">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium">Aujourd'hui</p>
              <p class="text-3xl font-bold text-gray-800 mt-1">{{ data.schedule_today.length }}</p>
              <p class="text-xs text-blue-600">cours</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
              <i class="pi pi-calendar text-xl text-blue-600"></i>
            </div>
          </div>
        </div>

        <!-- Absences -->
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-red-500 hover:shadow-md transition-all cursor-pointer" routerLink="/student/attendance">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium">Absences</p>
              <p class="text-3xl font-bold text-gray-800 mt-1">{{ data.attendance_summary.absences }}</p>
              <p class="text-xs text-red-600">ce trimestre</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
              <i class="pi pi-clock text-xl text-red-600"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Column (2/3) -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- Emploi du temps du jour -->
          <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4 flex items-center justify-between">
              <h2 class="text-white font-bold flex items-center gap-2">
                <i class="pi pi-calendar"></i>
                Mon Emploi du Temps - Aujourd'hui
              </h2>
              <a routerLink="/student/schedule" class="text-white/80 hover:text-white text-sm">
                Voir la semaine →
              </a>
            </div>
            <div class="divide-y divide-gray-100">
              <div *ngFor="let course of data.schedule_today" class="p-4 flex items-center gap-4 hover:bg-gray-50 transition-colors">
                <div class="w-24 text-center">
                  <div class="text-sm font-bold text-gray-700">{{ course.time }}</div>
                </div>
                <!-- <div class="w-1 h-10 rounded-full bg-indigo-500"></div> -->
                <div class="flex-1">
                  <div class="font-semibold text-gray-800">{{ course.subject }}</div>
                  <div class="text-sm text-gray-500">{{ course.teacher }} • {{ course.room }}</div>
                </div>
              </div>
              <div *ngIf="data.schedule_today.length === 0" class="p-6 text-center text-gray-500">
                Aucun cours prévu aujourd'hui.
              </div>
            </div>
          </div>

          <!-- Devoirs à faire -->
          <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-4 flex items-center justify-between">
              <h2 class="text-white font-bold flex items-center gap-2">
                <i class="pi pi-book"></i>
                Mes Devoirs à Faire
              </h2>
              <a routerLink="/student/homework" class="text-white/80 hover:text-white text-sm">
                Voir tout →
              </a>
            </div>
            <div class="divide-y divide-gray-100">
              <div *ngFor="let hw of data.upcoming_homework" class="p-4 flex items-start gap-4 hover:bg-gray-50">
                <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0 bg-gray-100">
                  <i class="pi pi-file-edit text-gray-500"></i>
                </div>
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <span class="font-semibold text-gray-800">{{ hw.subject }}</span>
                  </div>
                  <p class="text-sm text-gray-600 mt-1">{{ hw.title }}</p>
                  <p class="text-xs text-gray-400 mt-2 flex items-center gap-1">
                    <i class="pi pi-calendar"></i> Pour le {{ hw.due_date }}
                  </p>
                </div>
              </div>
              <div *ngIf="data.upcoming_homework.length === 0" class="p-6 text-center text-gray-500">
                Aucun devoir à faire.
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column (1/3) -->
        <div class="space-y-6">
          
          <!-- Mes dernières notes -->
          <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-4 py-3">
              <h3 class="text-white font-bold flex items-center gap-2">
                <i class="pi pi-star"></i>
                Mes Dernières Notes
              </h3>
            </div>
            <div class="divide-y divide-gray-100">
              <div *ngFor="let grade of data.recent_grades" class="p-4 hover:bg-gray-50">
                <div class="flex items-center justify-between">
                  <div>
                    <div class="font-medium text-gray-800 text-sm">{{ grade.subject }}</div>
                    <div class="text-xs text-gray-500">{{ grade.type }}</div>
                  </div>
                  <span class="text-lg font-bold"
                        [ngClass]="{
                          'text-green-600': grade.note >= 14,
                          'text-yellow-600': grade.note >= 10 && grade.note < 14,
                          'text-red-600': grade.note < 10
                        }">
                    {{ grade.note }}/20
                  </span>
                </div>
              </div>
               <div *ngIf="data.recent_grades.length === 0" class="p-6 text-center text-gray-500">
                Aucune note récente.
              </div>
            </div>
            <div class="p-3 bg-gray-50 border-t">
              <a routerLink="/student/grades" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                Voir toutes mes notes →
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <ng-template #loading>
      <div class="flex justify-center items-center h-64">
        <i class="pi pi-spin pi-spinner text-4xl text-indigo-600"></i>
      </div>
    </ng-template>
  `
})
export class StudentDashboardComponent implements OnInit {
  private studentService = inject(StudentService);
  
  // Signal to hold dashboard data
  dashboardData = signal<StudentDashboardData | null>(null);

  ngOnInit() {
    this.studentService.getDashboard().subscribe({
      next: (data) => this.dashboardData.set(data),
      error: (err) => console.error('Failed to load dashboard', err)
    });
  }
}
