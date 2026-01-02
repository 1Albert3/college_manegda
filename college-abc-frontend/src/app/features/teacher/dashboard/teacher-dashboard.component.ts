import { Component, inject, signal, computed, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { TeacherService, TeacherDashboardData } from '../../../core/services/teacher.service';

@Component({
  selector: 'app-teacher-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink],
  template: `
    <div class="space-y-6" *ngIf="dashboardData() as data; else loading">
      
      <!-- Welcome Banner -->
      <div class="bg-gradient-to-r from-primary via-blue-600 to-indigo-600 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div class="relative z-10">
          <h1 class="text-2xl md:text-3xl font-bold mb-2">
            Bonjour, {{ data.teacher?.name?.split(' ')[0] || 'Enseignant' }} 👨‍🏫
          </h1>
          <p class="text-blue-100">
            Vous avez {{ data.pending_grades.length }} saisies de notes en attente et {{ data.today_schedule.length }} cours aujourd'hui.
          </p>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Mes Élèves -->
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500 hover:shadow-md transition-shadow cursor-pointer" routerLink="/teacher/classes">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium">Mes Élèves</p>
              <p class="text-3xl font-bold text-gray-800 mt-1">{{ data.stats.total_students }}</p>
              <p class="text-xs text-blue-600 mt-1">{{ data.stats.classes_count }} classes</p>
            </div>
            <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">
              <i class="pi pi-users text-2xl text-blue-600"></i>
            </div>
          </div>
        </div>

        <!-- Cours Aujourd'hui -->
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500 hover:shadow-md transition-shadow cursor-pointer" routerLink="/teacher/schedule">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium">Cours Aujourd'hui</p>
              <p class="text-3xl font-bold text-gray-800 mt-1">{{ data.today_schedule.length }}</p>
              <p class="text-xs text-green-600 mt-1">prochainement</p>
            </div>
            <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">
              <i class="pi pi-calendar text-2xl text-green-600"></i>
            </div>
          </div>
        </div>

        <!-- Notes à Saisir -->
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500 hover:shadow-md transition-shadow cursor-pointer" routerLink="/teacher/grades">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium">Notes à Saisir</p>
              <p class="text-3xl font-bold text-gray-800 mt-1">{{ data.pending_grades.length }}</p>
              <p class="text-xs text-orange-600 mt-1">en attente</p>
            </div>
            <div class="w-14 h-14 rounded-full bg-orange-100 flex items-center justify-center">
              <i class="pi pi-pencil text-2xl text-orange-600"></i>
            </div>
          </div>
        </div>

        <!-- Notes Saisies (Activités) -->
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500 hover:shadow-md transition-shadow cursor-pointer">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium">Notes Saisies</p>
              <p class="text-3xl font-bold text-gray-800 mt-1">{{ data.stats.total_grades }}</p>
              <p class="text-xs text-purple-600 mt-1">total</p>
            </div>
            <div class="w-14 h-14 rounded-full bg-purple-100 flex items-center justify-center relative">
              <i class="pi pi-check-circle text-2xl text-purple-600"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- Emploi du Temps du Jour -->
          <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-6 py-4 flex items-center justify-between">
              <h2 class="text-white font-bold flex items-center gap-2">
                <i class="pi pi-calendar"></i>
                Emploi du Temps - Aujourd'hui
              </h2>
              <a routerLink="/teacher/schedule" class="text-white/80 hover:text-white text-sm">
                Voir la semaine <i class="pi pi-arrow-right text-xs"></i>
              </a>
            </div>
            <div class="divide-y divide-gray-100">
              <div *ngFor="let course of data.today_schedule" 
                   class="p-4 flex items-center gap-4 hover:bg-gray-50">
                <div class="w-24 text-center">
                  <div class="text-sm font-bold text-gray-700">{{ course.time }}</div>
                </div>
                <!-- <div class="w-1 h-12 rounded-full bg-blue-500"></div> -->
                <div class="flex-1">
                  <div class="font-semibold text-gray-800">{{ course.subject }}</div>
                  <div class="text-sm text-gray-500">{{ course.class }} • Salle {{ course.room }}</div>
                </div>
              </div>
               <div *ngIf="data.today_schedule.length === 0" class="p-6 text-center text-gray-500">
                Aucun cours aujourd'hui.
              </div>
            </div>
          </div>

          <!-- Mes Classes -->
          <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4 flex items-center justify-between">
              <h2 class="text-white font-bold flex items-center gap-2">
                <i class="pi pi-users"></i>
                Mes Classes
              </h2>
              <a routerLink="/teacher/classes" class="text-white/80 hover:text-white text-sm">
                Voir tout <i class="pi pi-arrow-right text-xs"></i>
              </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
              <div *ngFor="let cls of data.classes" 
                   class="border border-gray-200 rounded-lg p-4 hover:border-primary hover:shadow-md transition-all cursor-pointer"
                   [routerLink]="['/teacher/classes', cls.id]">
                <div class="flex items-center justify-between mb-3">
                  <span class="font-bold text-gray-800">{{ cls.nom }}</span>
                  <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">{{ cls.niveau }} ({{cls.cycle}})</span>
                </div>
                <div class="flex items-center gap-4 text-sm text-gray-500">
                  <span><i class="pi pi-users mr-1"></i> {{ cls.effectif }} élèves</span>
                </div>
                <div class="mt-3 flex gap-2">
                  <button class="flex-1 py-1.5 text-xs font-medium bg-gray-100 hover:bg-gray-200 rounded text-gray-700">
                    Notes
                  </button>
                  <button class="flex-1 py-1.5 text-xs font-medium bg-gray-100 hover:bg-gray-200 rounded text-gray-700">
                    Absences
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
          
          <!-- Quick Actions -->
          <div class="bg-white rounded-xl shadow-sm p-5">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
              <i class="pi pi-bolt text-primary"></i>
              Actions Rapides
            </h3>
            <div class="space-y-2">
              <a routerLink="/teacher/grades" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-primary hover:bg-blue-50 transition-all">
                <i class="pi pi-pencil text-orange-500"></i>
                <span class="text-sm font-medium">Saisir des notes</span>
              </a>
              <a routerLink="/teacher/attendance" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-primary hover:bg-blue-50 transition-all">
                <i class="pi pi-clock text-red-500"></i>
                <span class="text-sm font-medium">Faire l'appel</span>
              </a>
            </div>
          </div>

          <!-- Notes à Saisir (Pending) -->
          <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="bg-gray-800 px-4 py-3">
              <h3 class="text-white font-bold flex items-center gap-2">
                <i class="pi pi-exclamation-circle"></i>
                Saisies en Attente
              </h3>
            </div>
            <div class="divide-y divide-gray-100">
              <div *ngFor="let pending of data.pending_grades" class="p-4 hover:bg-gray-50">
                <div class="flex items-center justify-between">
                  <div>
                    <div class="font-medium text-gray-800">{{ pending.class }}</div>
                    <div class="text-sm text-gray-500">{{ pending.subject }}</div>
                  </div>
                  <div class="text-right">
                    <div class="text-sm font-medium text-red-600">-{{ pending.missing }} notes</div>
                    <div class="text-xs text-gray-400">Trimestre {{ pending.trimestre }}</div>
                  </div>
                </div>
              </div>
              <div *ngIf="data.pending_grades.length === 0" class="p-6 text-center text-gray-500">
                Aucune saisie en attente.
              </div>
            </div>
          </div>

          <!-- Activité Récente -->
          <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-purple-500 to-pink-500 px-4 py-3 flex items-center justify-between">
              <h3 class="text-white font-bold flex items-center gap-2">
                <i class="pi pi-history"></i>
                Activités Récentes
              </h3>
            </div>
            <div class="divide-y divide-gray-100">
              <div *ngFor="let activity of data.recent_activity" class="p-4 hover:bg-gray-50">
                <div class="flex items-start gap-3">
                  <div class="flex-1">
                    <div class="text-sm text-gray-800">{{ activity.message }}</div>
                    <div class="text-xs text-gray-400">{{ activity.date }}</div>
                  </div>
                </div>
              </div>
               <div *ngIf="data.recent_activity.length === 0" class="p-6 text-center text-gray-500">
                Aucune activité récente.
              </div>
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
export class TeacherDashboardComponent implements OnInit {
  private teacherService = inject(TeacherService);
  
  // Signal to hold dashboard data
  dashboardData = signal<TeacherDashboardData | null>(null);

  ngOnInit() {
    this.teacherService.getDashboard().subscribe({
      next: (data) => this.dashboardData.set(data),
      error: (err) => console.error('Failed to load dashboard', err)
    });
  }
}
