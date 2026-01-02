import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, RouterModule, Router } from '@angular/router';
import { GradeService } from '../../../core/services/grade.service';
import { ClassService } from '../../../core/services/class.service';
import { AcademicService } from '../../../core/services/academic.service';

@Component({
  selector: 'app-college-grade-entry',
  standalone: true,
  imports: [CommonModule, FormsModule, ReactiveFormsModule, RouterModule],
  template: `
    <div class="p-6 bg-gray-50 min-h-screen">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
           <button class="text-gray-500 hover:text-gray-800 mb-2 flex items-center gap-1 group transition-colors" (click)="goBack()">
              <i class="pi pi-arrow-left group-hover:-translate-x-1 transition-transform text-xs"></i> Retour aux classes
           </button>
           <h1 class="text-3xl font-extrabold text-gray-900" *ngIf="classData">
             Saisie de Notes Collège - {{ classData.nom }}
           </h1>
           <p class="text-gray-500 mt-1 font-medium italic" *ngIf="classData">
             Cycle Collège • Niveau {{ classData.niveau }}
           </p>
        </div>
        <div>
           <button (click)="submitGrades()" [disabled]="gradeForm.invalid || isSubmitting" 
                   class="bg-blue-600 text-white px-8 py-3 rounded-xl hover:bg-blue-700 disabled:opacity-50 transition shadow-lg shadow-blue-100 font-bold flex items-center gap-2">
              <i class="pi pi-check-circle" *ngIf="!isSubmitting"></i>
              <i class="pi pi-spin pi-spinner" *ngIf="isSubmitting"></i>
              <span>Enregistrer le lot</span>
           </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Configuration Saisie -->
        <div class="lg:col-span-1 space-y-6">
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] mb-4">Paramètres de l'Évaluation</h3>
            <form [formGroup]="gradeForm" class="space-y-5 text-sm">
              <div>
                <label class="block font-bold text-gray-700 mb-2">Trimestre</label>
                <select formControlName="trimestre" class="w-full border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 py-2.5">
                  <option value="1">1er Trimestre</option>
                  <option value="2">2ème Trimestre</option>
                  <option value="3">3ème Trimestre</option>
                </select>
              </div>

              <div>
                <label class="block font-bold text-gray-700 mb-2">Matière</label>
                <select formControlName="subject_id" class="w-full border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 py-2.5">
                  <option value="">Choisir une matière</option>
                  <option *ngFor="let s of subjects" [value]="s.id">{{ s.nom }}</option>
                </select>
              </div>

              <div>
                <label class="block font-bold text-gray-700 mb-2">Type d'évaluation</label>
                <div class="grid grid-cols-2 gap-2">
                  <button type="button" (click)="gradeForm.get('type_evaluation')?.setValue('DS')"
                          [class.bg-blue-600]="gradeForm.get('type_evaluation')?.value === 'DS'"
                          [class.text-white]="gradeForm.get('type_evaluation')?.value === 'DS'"
                          [class.bg-gray-50]="gradeForm.get('type_evaluation')?.value !== 'DS'"
                          class="py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition border border-transparent">
                    Devoir (DS)
                  </button>
                  <button type="button" (click)="gradeForm.get('type_evaluation')?.setValue('Comp')"
                          [class.bg-blue-600]="gradeForm.get('type_evaluation')?.value === 'Comp'"
                          [class.text-white]="gradeForm.get('type_evaluation')?.value === 'Comp'"
                          [class.bg-gray-50]="gradeForm.get('type_evaluation')?.value !== 'Comp'"
                          class="py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition border border-transparent">
                    Composition
                  </button>
                </div>
              </div>

              <div>
                <label class="block font-bold text-gray-700 mb-2">Date</label>
                <input type="date" formControlName="date_evaluation" class="w-full border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 py-2.5">
              </div>
            </form>
          </div>
          
          <div class="bg-blue-900 rounded-2xl shadow-xl p-6 text-white relative overflow-hidden">
             <div class="relative z-10">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-300 mb-4">Stats de Saisie</h3>
                <div class="space-y-4">
                  <div class="flex justify-between items-center text-sm border-b border-blue-800 pb-2">
                    <span class="text-blue-200 font-medium">Effectif :</span>
                    <span class="font-black">{{ students.length }}</span>
                  </div>
                  <div class="flex justify-between items-center text-sm border-b border-blue-800 pb-2">
                    <span class="text-blue-200 font-medium">Saisies :</span>
                    <span class="font-black">{{ getCountEntered() }}</span>
                  </div>
                  <div class="flex justify-between items-center text-sm">
                    <span class="text-blue-200 font-medium">Moy. Lot :</span>
                    <span class="font-black text-blue-100">{{ getAverageEntered() }} / 20</span>
                  </div>
                </div>
             </div>
             <i class="pi pi-chart-line absolute -right-2 -bottom-2 text-6xl text-blue-800 opacity-30 rotate-12"></i>
          </div>
        </div>

        <!-- Table de Saisie -->
        <div class="lg:col-span-3">
          <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100 text-[10px] uppercase text-gray-400 font-black tracking-[0.2em]">
                  <th class="px-8 py-5">Élève</th>
                  <th class="px-8 py-5 text-center w-32">Note / 20</th>
                  <th class="px-8 py-5">Appréciation / Observation</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr *ngFor="let s of students" class="hover:bg-blue-50/30 transition-all duration-200 group">
                  <td class="px-8 py-5">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-[10px] font-black text-blue-600 border border-blue-100">
                        {{ s.matricule.slice(-2) }}
                      </div>
                      <div>
                        <div class="font-bold text-gray-900 group-hover:text-blue-700 transition-colors uppercase italic">{{ s.nom }} {{ s.prenoms }}</div>
                        <div class="text-[10px] font-black text-gray-300 uppercase mt-0.5 tracking-tighter">{{ s.matricule }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-8 py-5">
                    <input type="number" [(ngModel)]="s.note" min="0" max="20" step="0.25"
                           class="w-full border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-center font-black py-2.5 text-lg"
                           [class.text-red-600]="s.note !== null && s.note < 10"
                           [class.text-emerald-600]="s.note !== null && s.note >= 10">
                  </td>
                  <td class="px-8 py-5">
                    <input type="text" [(ngModel)]="s.appreciation" placeholder="Remarque sur le travail..."
                           class="w-full border-gray-50 bg-gray-50/50 rounded-xl focus:ring-blue-500 focus:border-blue-500 py-2.5 text-sm font-medium border-transparent focus:bg-white transition-all">
                  </td>
                </tr>
              </tbody>
            </table>
            
            <div *ngIf="loading" class="p-20 flex justify-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
            
            <div *ngIf="!loading && students.length === 0" class="p-20 text-center text-gray-400 italic">
               Aucun élève à afficher. Veuillez vérifier la classe.
            </div>
          </div>
        </div>
      </div>
    </div>
  `
})
export class CollegeGradeEntryComponent implements OnInit {
  gradeForm: FormGroup;
  classId: string | null = null;
  classData: any = null;
  subjects: any[] = [];
  students: any[] = [];
  loading = true;
  isSubmitting = false;
  currentSchoolYearId: string = '';

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
      type_evaluation: ['DS', Validators.required],
      date_evaluation: [new Date().toISOString().split('T')[0], Validators.required]
    });
  }

  ngOnInit() {
    this.classId = this.route.snapshot.paramMap.get('id');
    this.loadInitialData();
  }

  loadInitialData() {
    this.loading = true;
    
    // Année en cours
    this.academicService.getCurrentYear().subscribe(year => {
        if (year) this.currentSchoolYearId = year.id.toString();
    });

    // Classe et Élèves
    this.classService.getStudentsByClass('college', this.classId!).subscribe({
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

            if (this.classData) {
                this.loadSubjects(this.classData.niveau);
            }
            this.loading = false;
        },
        error: () => this.loading = false
    });
  }

  loadSubjects(niveau: string) {
    // API Call pour les matières du collège selon niveau
    this.gradeService.getSubjectsCollege({ niveau }).subscribe(subs => {
        this.subjects = subs;
    });
  }

  getCountEntered() { return this.students.filter(s => s.note !== null).length; }

  getAverageEntered() {
      const notes = this.students.filter(s => s.note !== null).map(s => s.note);
      if (notes.length === 0) return '0.00';
      const sum = notes.reduce((a, b) => a + Number(b), 0);
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
        trimestre: Number(formValue.trimestre),
        type_evaluation: formValue.type_evaluation,
        date_evaluation: formValue.date_evaluation,
        grades: validGrades.map(s => ({
            student_id: s.student_id,
            note: s.note,
            appreciation: s.appreciation
        }))
    };

    this.gradeService.submitGradesCollegeBulk(payload).subscribe({
        next: (res) => {
            alert(res.message || 'Notes enregistrées avec succès.');
            this.isSubmitting = false;
            this.router.navigate(['/admin/college/classes']);
        },
        error: (err) => {
            console.error('Erreur soumission notes collège', err);
            this.isSubmitting = false;
            alert(err.error?.message || 'Une erreur est survenue.');
        }
    });
  }

  goBack() {
    this.router.navigate(['/admin/college/classes']);
  }
}
