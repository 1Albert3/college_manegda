import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, RouterModule, Router } from '@angular/router';
import { GradeService } from '../../../core/services/grade.service';
import { ClassService } from '../../../core/services/class.service';
import { AcademicService } from '../../../core/services/academic.service';

@Component({
  selector: 'app-lycee-grade-entry',
  standalone: true,
  imports: [CommonModule, FormsModule, ReactiveFormsModule, RouterModule],
  template: `
    <div class="p-6 bg-gray-50 min-h-screen">
      <!-- En-tête -->
      <div class="flex items-center justify-between mb-8">
        <div>
           <button class="text-gray-500 hover:text-gray-800 mb-2 flex items-center gap-1 group transition-colors" (click)="goBack()">
              <i class="pi pi-arrow-left group-hover:-translate-x-1 transition-transform"></i> Retour aux classes
           </button>
           <h1 class="text-3xl font-extrabold text-gray-900" *ngIf="classData">
             Saisie de Notes Lycée - {{ classData.nom }}
           </h1>
           <p class="text-gray-500 mt-1 font-medium italic" *ngIf="classData">
             Cycle Lycée • Niveau {{ classData.niveau }} {{ classData.serie ? '(Série ' + classData.serie + ')' : '' }}
           </p>
        </div>
        <div>
           <button (click)="submitGrades()" [disabled]="gradeForm.invalid || isSubmitting" 
                   class="bg-indigo-600 text-white px-8 py-3 rounded-xl hover:bg-indigo-700 disabled:opacity-50 transition shadow-lg shadow-indigo-100 font-bold flex items-center gap-2">
              <i class="pi pi-check-circle" *ngIf="!isSubmitting"></i>
              <i class="pi pi-spin pi-spinner" *ngIf="isSubmitting"></i>
              <span>Enregistrer les notes</span>
           </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Formulaire de configuration -->
        <div class="lg:col-span-1 space-y-6">
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-xs font-black uppercase text-gray-400 tracking-[0.2em] mb-4">Configuration</h3>
            <form [formGroup]="gradeForm" class="space-y-5 text-sm">
              <div>
                <label class="block font-bold text-gray-700 mb-2">Trimestre</label>
                <select formControlName="trimestre" class="w-full border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
                  <option value="1">1er Trimestre</option>
                  <option value="2">2ème Trimestre</option>
                  <option value="3">3ème Trimestre</option>
                </select>
              </div>

              <div>
                <label class="block font-bold text-gray-700 mb-2">Matière</label>
                <select formControlName="subject_id" class="w-full border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
                  <option value="">Sélectionnez une matière</option>
                  <option *ngFor="let s of subjects" [value]="s.id">{{ s.nom }}</option>
                </select>
              </div>

              <div>
                <label class="block font-bold text-gray-700 mb-2">Type d'évaluation</label>
                <div class="grid grid-cols-2 gap-2">
                  <button type="button" (click)="gradeForm.get('type_evaluation')?.setValue('devoir')"
                          [class.bg-indigo-600]="gradeForm.get('type_evaluation')?.value === 'devoir'"
                          [class.text-white]="gradeForm.get('type_evaluation')?.value === 'devoir'"
                          [class.bg-gray-50]="gradeForm.get('type_evaluation')?.value !== 'devoir'"
                          class="py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition border border-transparent">
                    Devoir (DS)
                  </button>
                  <button type="button" (click)="gradeForm.get('type_evaluation')?.setValue('compo')"
                          [class.bg-indigo-600]="gradeForm.get('type_evaluation')?.value === 'compo'"
                          [class.text-white]="gradeForm.get('type_evaluation')?.value === 'compo'"
                          [class.bg-gray-50]="gradeForm.get('type_evaluation')?.value !== 'compo'"
                          class="py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition border border-transparent">
                    Composition
                  </button>
                </div>
              </div>

              <div>
                <label class="block font-bold text-gray-700 mb-2">Date de l'évaluation</label>
                <input type="date" formControlName="date_evaluation" class="w-full border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 py-2.5">
              </div>
              
              <div *ngIf="gradeForm.get('type_evaluation')?.value === 'devoir'">
                <label class="block font-bold text-gray-700 mb-2">Coefficient (DS)</label>
                <input type="number" formControlName="coefficient" class="w-full border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 py-2.5" placeholder="Par défaut: 1">
              </div>
            </form>
          </div>
          
          <!-- Statistiques rapides -->
          <div class="bg-indigo-900 rounded-2xl shadow-xl p-6 text-white overflow-hidden relative">
            <div class="relative z-10">
              <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-300 mb-4 text-center">Récapitulatif</h3>
              <div class="space-y-4">
                <div class="flex justify-between items-center text-sm border-b border-indigo-800 pb-2">
                  <span class="text-indigo-200">Élèves :</span>
                  <span class="font-black">{{ students.length }}</span>
                </div>
                <div class="flex justify-between items-center text-sm border-b border-indigo-800 pb-2">
                  <span class="text-indigo-200">Notes saisies :</span>
                  <span class="font-black">{{ getCountEntered() }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                  <span class="text-indigo-200">Moyenne lot :</span>
                  <span class="font-black text-indigo-100">{{ getAverageEntered() }} / 20</span>
                </div>
              </div>
            </div>
            <div class="absolute -right-4 -bottom-4 opacity-10">
              <i class="pi pi-chart-bar text-7xl rotate-12"></i>
            </div>
          </div>
        </div>

        <!-- Tableau des notes -->
        <div class="lg:col-span-3">
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100 text-[10px] uppercase text-gray-400 font-black tracking-[0.2em]">
                  <th class="px-8 py-5">Identité de l'élève</th>
                  <th class="px-8 py-5 text-center w-32">Note / 20</th>
                  <th class="px-8 py-5">Appréciation</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr *ngFor="let s of students" class="hover:bg-indigo-50/30 transition-all duration-200 group">
                  <td class="px-8 py-5">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-[10px] font-black text-indigo-600 border border-indigo-100">
                        {{ s.matricule.slice(-2) }}
                      </div>
                      <div>
                        <div class="font-bold text-gray-900 leading-none group-hover:text-indigo-700 transition-colors uppercase italic">{{ s.nom }} {{ s.prenoms }}</div>
                        <div class="text-[10px] font-black text-gray-400 uppercase mt-1 tracking-wider">{{ s.matricule }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-8 py-5">
                    <input type="number" [(ngModel)]="s.note" min="0" max="20" step="0.25"
                           class="w-full border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 text-center font-black py-2.5 text-lg"
                           [class.text-red-600]="s.note !== null && s.note < 10"
                           [class.text-emerald-600]="s.note !== null && s.note >= 10">
                  </td>
                  <td class="px-8 py-5">
                    <input type="text" [(ngModel)]="s.appreciation" placeholder="Observation..."
                           class="w-full border-gray-50 bg-gray-50/50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 py-2.5 text-sm font-medium border-transparent focus:bg-white transition-all">
                  </td>
                </tr>
                <tr *ngIf="students.length === 0 && !loading">
                  <td colspan="3" class="px-8 py-20 text-center text-gray-400 italic">
                    Aucun élève trouvé pour cette classe.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  `
})
export class LyceeGradeEntryComponent implements OnInit {
  gradeForm: FormGroup;
  classId: string | null = null;
  classData: any = null;
  subjects: any[] = [];
  students: any[] = [];
  loading = true;
  isSubmitting = false;
  currentSchoolYearId: string = 'current';

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private gradeService: GradeService,
    private classService: ClassService,
    private academicService: AcademicService
  ) {
    this.gradeForm = this.fb.group({
      trimestre: ['1', Validators.required],
      subject_id: ['', Validators.required],
      type_evaluation: ['devoir', Validators.required],
      date_evaluation: [new Date().toISOString().split('T')[0], Validators.required],
      coefficient: [1]
    });
  }

  ngOnInit() {
    this.classId = this.route.snapshot.paramMap.get('id');
    this.loadInitialData();
  }

  loadInitialData() {
    this.loading = true;
    
    // Charger l'année académique active
    this.academicService.getCurrentYear().subscribe(year => {
        if (year) this.currentSchoolYearId = year.id.toString();
    });

    // Charger les détails de la classe et les élèves
    this.classService.getStudentsByClass('lycee', this.classId!).subscribe({
        next: (data) => {
            this.classData = data.class || data;
            const studentsList = data.students || (data.enrollments ? data.enrollments.map((e: any) => e.student) : []);
            this.students = studentsList.map((s: any) => ({
                student_id: s.id,
                nom: s.nom,
                prenoms: s.prenoms,
                matricule: s.matricule,
                note: null,
                appreciation: ''
            }));

            // Une fois qu'on a le niveau, on charge les matières
            if (this.classData) {
                this.loadSubjects(this.classData.niveau, this.classData.serie);
            }
            this.loading = false;
        }
    });
  }

  loadSubjects(niveau: string, serie: string) {
    this.gradeService.getSubjectsLycee({ niveau, serie }).subscribe(subs => {
        this.subjects = subs;
    });
  }

  getCountEntered() {
      return this.students.filter(s => s.note !== null).length;
  }

  getAverageEntered() {
      const notes = this.students.filter(s => s.note !== null).map(s => s.note);
      if (notes.length === 0) return '0.00';
      const sum = notes.reduce((a, b) => a + b, 0);
      return (sum / notes.length).toFixed(2);
  }

  submitGrades() {
    const validGrades = this.students.filter(s => s.note !== null);
    if (validGrades.length === 0) {
        alert('Veuillez saisir au moins une note.');
        return;
    }

    this.isSubmitting = true;
    const formValue = this.gradeForm.value;

    const payload = {
        class_id: this.classId!,
        subject_id: formValue.subject_id,
        school_year_id: this.currentSchoolYearId,
        trimestre: formValue.trimestre,
        type_evaluation: formValue.type_evaluation,
        date_evaluation: formValue.date_evaluation,
        coefficient: formValue.coefficient || 1,
        grades: validGrades.map(s => ({
            student_id: s.student_id,
            note: s.note,
            appreciation: s.appreciation
        }))
    };

    this.gradeService.submitGradesLyceeBulk(payload).subscribe({
        next: (res) => {
            alert(res.message || 'Notes enregistrées avec succès.');
            this.isSubmitting = false;
            this.router.navigate(['/admin/lycee/classes']);
        },
        error: (err) => {
            console.error('Erreur soumission notes lycée', err);
            this.isSubmitting = false;
            alert('Une erreur est survenue lors de l\'enregistrement.');
        }
    });
  }

  goBack() {
    this.router.navigate(['/admin/lycee/classes']);
  }
}
