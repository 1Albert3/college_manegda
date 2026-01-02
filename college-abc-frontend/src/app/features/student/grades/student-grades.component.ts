import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

interface Grade {
  id: number;
  subject: string;
  evaluation_title: string;
  score: number;
  max_score: number;
  date: string;
  type: string;
  teacher: string;
  class_average?: number;
}

@Component({
  selector: 'app-student-grades',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="space-y-6">
      <!-- Header -->
      <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-6 text-white">
        <h1 class="text-2xl font-bold">Mes Notes 📊</h1>
        <p class="text-white/80 mt-1">Trimestre 1 - 2024/2025</p>
        <div class="grid grid-cols-3 gap-4 mt-4">
          <div class="bg-white/10 rounded-lg p-3">
            <p class="text-white/70 text-sm">Moyenne Générale</p>
            <p class="text-2xl font-bold">{{ overallAverage() | number:'1.1-1' }}/20</p>
          </div>
          <div class="bg-white/10 rounded-lg p-3">
            <p class="text-white/70 text-sm">Rang</p>
            <p class="text-2xl font-bold">{{ rank() }}<span class="text-lg">/{{ totalStudents() }}</span></p>
          </div>
          <div class="bg-white/10 rounded-lg p-3">
            <p class="text-white/70 text-sm">Notes ce mois</p>
            <p class="text-2xl font-bold">{{ recentGradesCount() }}</p>
          </div>
        </div>
      </div>

      <!-- Subject Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div *ngFor="let subject of subjectStats()" 
             class="bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                   [style.background-color]="subject.color + '20'">
                <i [class]="subject.icon" [style.color]="subject.color"></i>
              </div>
              <span class="font-semibold text-gray-800">{{ subject.name }}</span>
            </div>
            <span class="text-xl font-bold"
                  [ngClass]="{
                    'text-green-600': subject.average >= 14,
                    'text-yellow-600': subject.average >= 10 && subject.average < 14,
                    'text-red-600': subject.average < 10
                  }">
              {{ subject.average | number:'1.1-1' }}
            </span>
          </div>
          <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all"
                 [style.width.%]="(subject.average / 20) * 100"
                 [style.background-color]="subject.color"></div>
          </div>
          <div class="flex justify-between text-sm text-gray-500 mt-2">
            <span>{{ subject.gradesCount }} notes</span>
            <span *ngIf="subject.trend === 'up'" class="text-green-600"><i class="pi pi-arrow-up"></i> En hausse</span>
            <span *ngIf="subject.trend === 'down'" class="text-red-600"><i class="pi pi-arrow-down"></i> En baisse</span>
          </div>
        </div>
      </div>

      <!-- Recent Grades -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
          <h2 class="text-white font-bold flex items-center gap-2">
            <i class="pi pi-history"></i>
            Dernières Notes
          </h2>
        </div>
        <div class="divide-y divide-gray-100">
          <div *ngFor="let grade of grades()" 
               class="p-4 flex items-center gap-4 hover:bg-gray-50">
            <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                 [ngClass]="{
                   'bg-green-100': (grade.score / grade.max_score) >= 0.7,
                   'bg-yellow-100': (grade.score / grade.max_score) >= 0.5 && (grade.score / grade.max_score) < 0.7,
                   'bg-red-100': (grade.score / grade.max_score) < 0.5
                 }">
              <span class="font-bold text-lg"
                    [ngClass]="{
                      'text-green-600': (grade.score / grade.max_score) >= 0.7,
                      'text-yellow-600': (grade.score / grade.max_score) >= 0.5 && (grade.score / grade.max_score) < 0.7,
                      'text-red-600': (grade.score / grade.max_score) < 0.5
                    }">
                {{ grade.score }}
              </span>
            </div>
            <div class="flex-1">
              <div class="font-semibold text-gray-800">{{ grade.subject }}</div>
              <div class="text-sm text-gray-500">{{ grade.evaluation_title }}</div>
            </div>
            <div class="text-right">
              <div class="text-sm text-gray-600">{{ grade.date }}</div>
              <div class="text-xs text-gray-400">Moy. classe: {{ grade.class_average }}/20</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `
})
export class StudentGradesComponent implements OnInit {
  private http = inject(HttpClient);
  
  grades = signal<Grade[]>([]);
  overallAverage = signal(14.2);
  rank = signal(8);
  totalStudents = signal(32);
  recentGradesCount = signal(6);

  subjectStats = signal([
    { name: 'Mathématiques', average: 15.5, gradesCount: 4, trend: 'up', color: '#3B82F6', icon: 'pi pi-calculator' },
    { name: 'Français', average: 12.8, gradesCount: 3, trend: 'stable', color: '#10B981', icon: 'pi pi-book' },
    { name: 'Anglais', average: 16.0, gradesCount: 3, trend: 'up', color: '#8B5CF6', icon: 'pi pi-globe' },
    { name: 'Histoire-Géo', average: 10.5, gradesCount: 2, trend: 'down', color: '#F59E0B', icon: 'pi pi-map' },
    { name: 'SVT', average: 13.5, gradesCount: 2, trend: 'up', color: '#EC4899', icon: 'pi pi-heart' },
    { name: 'Physique-Chimie', average: 12.0, gradesCount: 2, trend: 'stable', color: '#06B6D4', icon: 'pi pi-bolt' },
  ]);

  ngOnInit() {
    this.loadGrades();
  }

  loadGrades() {
    this.grades.set([
      { id: 1, subject: 'Mathématiques', evaluation_title: 'Contrôle - Équations', score: 15, max_score: 20, date: '20/12/2024', type: 'Contrôle', teacher: 'M. Sawadogo', class_average: 12.5 },
      { id: 2, subject: 'Français', evaluation_title: 'Dictée', score: 12, max_score: 20, date: '18/12/2024', type: 'Devoir', teacher: 'Mme Diallo', class_average: 11.0 },
      { id: 3, subject: 'Anglais', evaluation_title: 'Oral - Présentation', score: 16, max_score: 20, date: '17/12/2024', type: 'Oral', teacher: 'M. Kone', class_average: 13.5 },
      { id: 4, subject: 'Histoire-Géo', evaluation_title: 'Contrôle WWII', score: 8, max_score: 20, date: '15/12/2024', type: 'Contrôle', teacher: 'Mme Ouedraogo', class_average: 10.5 },
      { id: 5, subject: 'Mathématiques', evaluation_title: 'Devoir - Fonctions', score: 17, max_score: 20, date: '10/12/2024', type: 'Devoir', teacher: 'M. Sawadogo', class_average: 11.0 },
    ]);
  }
}
