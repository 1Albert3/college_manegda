import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { StudentService } from '../../../../core/services/student.service';
// Temporairement désactivé

@Component({
  selector: 'app-bulletins',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  template: `
    <div class="p-6">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Génération des Bulletins</h1>
        <p class="text-gray-600">Générez et téléchargez les bulletins de notes des élèves</p>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form [formGroup]="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Élève</label>
            <select formControlName="studentId" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">Sélectionner un élève</option>
              <option *ngFor="let student of students" [value]="student.id">
                {{ student.firstName }} {{ student.lastName }} ({{ student.matricule }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Semestre</label>
            <select formControlName="semester" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="1">1er Semestre</option>
              <option value="2">2ème Semestre</option>
              <option value="3">3ème Semestre</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Année scolaire</label>
            <select formControlName="schoolYear" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="2024-2025">2024-2025</option>
              <option value="2023-2024">2023-2024</option>
            </select>
          </div>

          <div class="flex items-end space-x-2">
            <button
              type="button"
              (click)="previewBulletin()"
              [disabled]="!filterForm.valid || isLoading"
              class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:bg-gray-300">
              <i class="pi pi-eye mr-2"></i>
              Prévisualiser
            </button>
            <button
              type="button"
              (click)="generateBulletin()"
              [disabled]="!filterForm.valid || isLoading"
              class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 disabled:bg-gray-300">
              <i class="pi pi-download mr-2"></i>
              Télécharger
            </button>
          </div>
        </form>
      </div>

      <!-- Preview -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6" *ngIf="previewData">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Prévisualisation du Bulletin</h2>
        
        <!-- Student Info -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <span class="text-sm text-gray-600">Élève:</span>
              <p class="font-medium">{{ previewData.student.full_name }}</p>
            </div>
            <div>
              <span class="text-sm text-gray-600">Matricule:</span>
              <p class="font-medium">{{ previewData.student.matricule }}</p>
            </div>
            <div>
              <span class="text-sm text-gray-600">Classe:</span>
              <p class="font-medium">{{ previewData.student.class }}</p>
            </div>
            <div>
              <span class="text-sm text-gray-600">Moyenne:</span>
              <p class="font-medium text-blue-600">{{ previewData.averages.general_average }}/20</p>
            </div>
          </div>
        </div>

        <!-- Grades Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matière</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Coef</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Devoir 1</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Devoir 2</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Composition</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Moyenne</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr *ngFor="let subject of previewData.grades">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ subject.subject }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">{{ subject.coefficient }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center" 
                    [class]="getGradeClass(subject.grades[0].score)">
                  {{ subject.grades[0].score }}/{{ subject.grades[0].max }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center"
                    [class]="getGradeClass(subject.grades[1].score)">
                  {{ subject.grades[1].score }}/{{ subject.grades[1].max }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center"
                    [class]="getGradeClass(subject.grades[2].score)">
                  {{ subject.grades[2].score }}/{{ subject.grades[2].max }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium"
                    [class]="getGradeClass(subject.average)">
                  {{ subject.average.toFixed(2) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Summary -->
        <div class="mt-6 bg-blue-50 rounded-lg p-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
              <p class="text-sm text-gray-600">Moyenne Générale</p>
              <p class="text-2xl font-bold text-blue-600">{{ previewData.averages.general_average }}/20</p>
            </div>
            <div class="text-center">
              <p class="text-sm text-gray-600">Mention</p>
              <p class="text-xl font-semibold" [class]="getMentionClass(previewData.averages.mention)">
                {{ previewData.averages.mention }}
              </p>
            </div>
            <div class="text-center">
              <p class="text-sm text-gray-600">Classement</p>
              <p class="text-xl font-semibold text-gray-700">
                {{ previewData.averages.rank }}e / {{ previewData.averages.class_size }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div *ngIf="isLoading" class="bg-white rounded-lg shadow-sm p-6 text-center">
        <i class="pi pi-spin pi-spinner text-2xl text-blue-500 mb-2"></i>
        <p class="text-gray-600">{{ loadingMessage }}</p>
      </div>

      <!-- Error Message -->
      <div *ngIf="errorMessage" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
          <i class="pi pi-exclamation-triangle text-red-500 text-xl mr-3"></i>
          <div>
            <h3 class="text-red-800 font-medium">Erreur</h3>
            <p class="text-red-700">{{ errorMessage }}</p>
          </div>
        </div>
      </div>

      <!-- Success Message -->
      <div *ngIf="successMessage" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
          <i class="pi pi-check-circle text-green-500 text-xl mr-3"></i>
          <div>
            <h3 class="text-green-800 font-medium">Succès</h3>
            <p class="text-green-700">{{ successMessage }}</p>
          </div>
        </div>
      </div>
    </div>
  `
})
export class BulletinsComponent implements OnInit {
  private fb = inject(FormBuilder);
  private studentService = inject(StudentService);

  filterForm: FormGroup;
  students: any[] = [];
  previewData: any = null;
  isLoading = false;
  loadingMessage = '';
  errorMessage = '';
  successMessage = '';

  constructor() {
    this.filterForm = this.fb.group({
      studentId: ['', Validators.required],
      semester: ['1', Validators.required],
      schoolYear: ['2024-2025', Validators.required]
    });
  }

  ngOnInit() {
    this.loadStudents();
  }

  loadStudents() {
    this.studentService.getStudents().subscribe({
      next: (students) => {
        this.students = students;
      },
      error: (error) => {
        this.errorMessage = 'Erreur lors du chargement des élèves';
      }
    });
  }

  previewBulletin() {
    if (this.filterForm.valid) {
      this.isLoading = true;
      this.loadingMessage = 'Génération de la prévisualisation...';
      this.errorMessage = '';
      this.previewData = null;

      const { studentId, semester, schoolYear } = this.filterForm.value;

      // Simuler un appel API pour la prévisualisation
      setTimeout(() => {
        this.previewData = {
          student: {
            full_name: 'Jean KABORE',
            matricule: 'STD-2024-0001',
            class: '6ème A',
            date_of_birth: '15/05/2010'
          },
          grades: [
            {
              subject: 'Mathématiques',
              coefficient: 4,
              grades: [
                { score: 15, max: 20 },
                { score: 12, max: 20 },
                { score: 14, max: 20 }
              ],
              average: 13.67
            },
            {
              subject: 'Français',
              coefficient: 4,
              grades: [
                { score: 16, max: 20 },
                { score: 14, max: 20 },
                { score: 15, max: 20 }
              ],
              average: 15.00
            }
          ],
          averages: {
            general_average: 14.33,
            mention: 'Bien',
            rank: 5,
            class_size: 35
          }
        };
        this.isLoading = false;
      }, 2000);
    }
  }

  generateBulletin() {
    if (this.filterForm.valid) {
      this.isLoading = true;
      this.loadingMessage = 'Génération du bulletin PDF...';
      this.errorMessage = '';
      this.successMessage = '';

      const { studentId, semester, schoolYear } = this.filterForm.value;

      // Simuler la génération et le téléchargement
      setTimeout(() => {
        this.isLoading = false;
        this.successMessage = 'Bulletin généré et téléchargé avec succès !';
        
        // Simuler le téléchargement d'un fichier
        const link = document.createElement('a');
        link.href = 'data:text/plain;charset=utf-8,Bulletin PDF simulé';
        link.download = `bulletin_${studentId}_S${semester}_${schoolYear}.pdf`;
        link.click();

        setTimeout(() => {
          this.successMessage = '';
        }, 3000);
      }, 3000);
    }
  }

  getGradeClass(score: number): string {
    if (score >= 16) return 'text-green-600 font-semibold';
    if (score >= 14) return 'text-blue-600 font-semibold';
    if (score >= 10) return 'text-yellow-600 font-semibold';
    return 'text-red-600 font-semibold';
  }

  getMentionClass(mention: string): string {
    switch (mention) {
      case 'Très Bien': return 'text-green-600';
      case 'Bien': return 'text-blue-600';
      case 'Assez Bien': return 'text-yellow-600';
      case 'Passable': return 'text-orange-600';
      default: return 'text-red-600';
    }
  }
}