import { Component, inject, signal, OnInit, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
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
  comment?: string;
}

interface SubjectAverage {
  subject: string;
  average: number;
  grades_count: number;
  trend: 'up' | 'down' | 'stable';
}

@Component({
  selector: 'app-parent-grades',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6">
      <!-- Header with Child Selector -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Notes & Résultats</h1>
          <p class="text-gray-500">Suivez les notes de votre enfant en temps réel</p>
        </div>
        <div class="flex items-center gap-4">
          <select [(ngModel)]="selectedChildId" (change)="loadGrades()"
                  class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
            <option *ngFor="let child of children()" [value]="child.id">
              {{ child.firstName }} {{ child.lastName }}
            </option>
          </select>
        </div>
      </div>

      <!-- Average Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-5 text-white">
          <p class="text-white/80 text-sm">Moyenne Générale</p>
          <p class="text-4xl font-bold mt-2">{{ overallAverage() | number:'1.1-1' }}<span class="text-xl">/20</span></p>
          <p class="text-sm mt-2 flex items-center gap-1">
            <i class="pi pi-arrow-up"></i> +0.5 ce trimestre
          </p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-gray-500 text-sm">Meilleure Matière</p>
          <p class="text-2xl font-bold text-gray-800 mt-2">Anglais</p>
          <p class="text-sm text-green-600 mt-1">16.5/20</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-gray-500 text-sm">À améliorer</p>
          <p class="text-2xl font-bold text-gray-800 mt-2">Histoire-Géo</p>
          <p class="text-sm text-orange-600 mt-1">10.2/20</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5">
          <p class="text-gray-500 text-sm">Évaluations</p>
          <p class="text-2xl font-bold text-gray-800 mt-2">{{ grades().length }}</p>
          <p class="text-sm text-gray-500 mt-1">ce trimestre</p>
        </div>
      </div>

      <!-- Subject Averages -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-primary to-blue-600 px-6 py-4">
          <h2 class="text-white font-bold">Moyennes par Matière</h2>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div *ngFor="let avg of subjectAverages()" 
                 class="border border-gray-200 rounded-lg p-4 hover:border-primary/50 transition-colors">
              <div class="flex items-center justify-between">
                <span class="font-medium text-gray-800">{{ avg.subject }}</span>
                <span class="text-lg font-bold"
                      [ngClass]="{
                        'text-green-600': avg.average >= 14,
                        'text-yellow-600': avg.average >= 10 && avg.average < 14,
                        'text-red-600': avg.average < 10
                      }">
                  {{ avg.average | number:'1.1-1' }}/20
                </span>
              </div>
              <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full rounded-full"
                     [style.width.%]="(avg.average / 20) * 100"
                     [ngClass]="{
                       'bg-green-500': avg.average >= 14,
                       'bg-yellow-500': avg.average >= 10 && avg.average < 14,
                       'bg-red-500': avg.average < 10
                     }"></div>
              </div>
              <p class="text-xs text-gray-500 mt-2">{{ avg.grades_count }} notes</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Grades Table -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-6 py-4 flex items-center justify-between">
          <h2 class="text-white font-bold flex items-center gap-2">
            <i class="pi pi-list"></i>
            Détail des Notes
          </h2>
          <div class="flex gap-2">
            <select class="px-3 py-1 bg-white/20 text-white rounded-lg text-sm border-0">
              <option>Trimestre 1</option>
              <option>Trimestre 2</option>
              <option>Trimestre 3</option>
            </select>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr class="text-left text-sm text-gray-500 uppercase">
                <th class="px-6 py-4">Date</th>
                <th class="px-6 py-4">Matière</th>
                <th class="px-6 py-4">Évaluation</th>
                <th class="px-6 py-4">Type</th>
                <th class="px-6 py-4 text-center">Note</th>
                <th class="px-6 py-4">Professeur</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr *ngFor="let grade of grades()" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-gray-600">{{ grade.date }}</td>
                <td class="px-6 py-4 font-medium text-gray-800">{{ grade.subject }}</td>
                <td class="px-6 py-4 text-gray-600">{{ grade.evaluation_title }}</td>
                <td class="px-6 py-4">
                  <span class="px-2 py-1 rounded-full text-xs font-medium"
                        [ngClass]="{
                          'bg-blue-100 text-blue-700': grade.type === 'Devoir',
                          'bg-purple-100 text-purple-700': grade.type === 'Contrôle',
                          'bg-red-100 text-red-700': grade.type === 'Examen'
                        }">
                    {{ grade.type }}
                  </span>
                </td>
                <td class="px-6 py-4 text-center">
                  <span class="text-lg font-bold"
                        [ngClass]="{
                          'text-green-600': (grade.score / grade.max_score) >= 0.7,
                          'text-yellow-600': (grade.score / grade.max_score) >= 0.5 && (grade.score / grade.max_score) < 0.7,
                          'text-red-600': (grade.score / grade.max_score) < 0.5
                        }">
                    {{ grade.score }}/{{ grade.max_score }}
                  </span>
                </td>
                <td class="px-6 py-4 text-gray-500">{{ grade.teacher }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `
})
export class ParentGradesComponent implements OnInit {
  private authService = inject(AuthService);
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  children = computed(() => this.authService.currentUser()?.children || []);
  selectedChildId: string | null = null;
  loading = signal(false);
  
  grades = signal<Grade[]>([]);
  subjectAverages = signal<SubjectAverage[]>([]);
  overallAverage = signal(0);

  ngOnInit() {
    const kids = this.children();
    if (kids.length > 0) {
      this.selectedChildId = kids[0].id;
      this.loadGrades();
    }
  }


  loadGrades() {
    if (!this.selectedChildId) return;
    
    this.loading.set(true);

    // API call to get grades
    this.http.get<any>(`${this.apiUrl}/grades/${this.selectedChildId}/1`).subscribe({
      next: (response) => {
        // Transform API response to Grade[] format
        const grades = response.notes?.map((n: any) => ({
          id: n.id,
          subject: n.matiere,
          evaluation_title: n.evaluation || 'Évaluation',
          score: n.note,
          max_score: n.sur || 20,
          date: n.date,
          type: n.type || 'Contrôle',
          teacher: n.professeur || 'Enseignant',
          comment: n.commentaire
        })) || [];
        
        this.grades.set(grades);
        this.calculateAverages();
        this.loading.set(false);
      },
      error: (err) => {
        console.error('Error loading grades:', err);
        // Fallback to mock data
        this.grades.set([
          { id: 1, subject: 'Mathématiques', evaluation_title: 'Contrôle - Équations', score: 15, max_score: 20, date: '20/12/2024', type: 'Contrôle', teacher: 'M. Sawadogo' },
          { id: 2, subject: 'Français', evaluation_title: 'Dictée - Noël', score: 12, max_score: 20, date: '18/12/2024', type: 'Devoir', teacher: 'Mme Diallo' },
          { id: 3, subject: 'Anglais', evaluation_title: 'Oral - Présentation', score: 16, max_score: 20, date: '17/12/2024', type: 'Devoir', teacher: 'M. Kone' },
          { id: 4, subject: 'Histoire-Géo', evaluation_title: 'Contrôle - WWII', score: 8, max_score: 20, date: '15/12/2024', type: 'Contrôle', teacher: 'Mme Ouedraogo' },
          { id: 5, subject: 'SVT', evaluation_title: 'TP - Écosystème', score: 14, max_score: 20, date: '14/12/2024', type: 'Devoir', teacher: 'Mme Zorome' },
          { id: 6, subject: 'Physique-Chimie', evaluation_title: 'Contrôle - Électricité', score: 13, max_score: 20, date: '12/12/2024', type: 'Contrôle', teacher: 'M. Traore' },
        ]);
        this.calculateAverages();
        this.loading.set(false);
      }
    });
  }

  calculateAverages() {
    const grades = this.grades();
    const subjectMap = new Map<string, { total: number; count: number }>();
    
    grades.forEach(g => {
      const existing = subjectMap.get(g.subject) || { total: 0, count: 0 };
      existing.total += (g.score / g.max_score) * 20;
      existing.count++;
      subjectMap.set(g.subject, existing);
    });

    const averages: SubjectAverage[] = Array.from(subjectMap.entries()).map(([subject, data]) => ({
      subject,
      average: data.total / data.count,
      grades_count: data.count,
      trend: 'stable' as const
    }));

    this.subjectAverages.set(averages);
    
    // Calculate overall average
    if (averages.length > 0) {
      const overall = averages.reduce((sum, a) => sum + a.average, 0) / averages.length;
      this.overallAverage.set(overall);
    } else {
      this.overallAverage.set(0);
    }
  }
}
