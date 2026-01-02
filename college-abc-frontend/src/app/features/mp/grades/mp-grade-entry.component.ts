import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { ClassService } from '../../../core/services/class.service';
import { GradeService } from '../../../core/services/grade.service';
import { AcademicService } from '../../../core/services/academic.service';

interface StudentGradeInput {
  student_id: string;
  matricule: string;
  full_name: string;
  note_obtenue: number | null;
  commentaire: string;
}

@Component({
  selector: 'app-mp-grade-entry',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormsModule, RouterModule],
  template: `
    <div class="p-6 bg-gray-50 min-h-screen">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <button (click)="goBack()" class="text-gray-500 hover:text-gray-800 mb-2 flex items-center gap-1">
            <i class="pi pi-arrow-left"></i> Retour aux classes
          </button>
          <h1 class="text-2xl font-bold text-gray-800" *ngIf="classData">
            Saisie des Notes - {{ classData.nom }} ({{ classData.niveau }})
          </h1>
        </div>
        <div class="bg-blue-50 text-blue-700 px-4 py-2 rounded-lg font-medium border border-blue-100" *ngIf="currentYear">
          Année: {{ currentYear.name }}
        </div>
      </div>

      <!-- Settings Card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <form [formGroup]="gradeForm" class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Trimestre</label>
            <select formControlName="trimestre" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
              <option value="1">1er Trimestre</option>
              <option value="2">2ème Trimestre</option>
              <option value="3">3ème Trimestre</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Matière</label>
            <select formControlName="subject_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
              <option value="" disabled>Sélectionner une matière</option>
              <option *ngFor="let sub of subjects" [value]="sub.id">{{ sub.nom }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Type d'Évaluation</label>
            <select formControlName="type_evaluation" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
              <option value="IO">Interrogation Orale (/10)</option>
              <option value="DV">Devoir (/20)</option>
              <option value="CP">Composition (/100)</option>
              <option value="TP">Travaux Pratiques (/20)</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Date d'Évaluation</label>
            <input type="date" formControlName="date_evaluation" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
          </div>
        </form>
      </div>

      <!-- Students Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold tracking-wider">
              <th class="px-6 py-4 w-32">Matricule</th>
              <th class="px-6 py-4">Nom & Prénoms</th>
              <th class="px-6 py-4 w-40 text-center">Note / {{ getScoreBase() }}</th>
              <th class="px-6 py-4">Appréciation / Commentaire</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr *ngFor="let s of studentInputs; let i = index" class="hover:bg-blue-50/20 transition-colors">
              <td class="px-6 py-4 text-sm font-medium text-gray-600">{{ s.matricule }}</td>
              <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ s.full_name }}</td>
              <td class="px-6 py-4">
                <input type="number" [(ngModel)]="s.note_obtenue" [min]="0" [max]="getScoreBase()" 
                       class="w-full text-center border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-bold text-gray-800"
                       [class.border-red-500]="s.note_obtenue && s.note_obtenue > getScoreBase()">
              </td>
              <td class="px-6 py-4">
                <input type="text" [(ngModel)]="s.commentaire" placeholder="Ex: Excellent, Travail soigné..."
                       class="w-full border-gray-100 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm italic py-1">
              </td>
            </tr>
            <tr *ngIf="studentInputs.length === 0">
              <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">
                Aucun élève trouvé dans cette classe.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer Actions -->
      <div class="flex justify-end gap-3 sticky bottom-6">
        <button (click)="goBack()" 
                class="bg-white border border-gray-300 text-gray-700 px-6 py-2.5 rounded-xl hover:bg-gray-50 transition shadow-md font-medium">
          Annuler
        </button>
        <button (click)="submitGrades()" 
                [disabled]="isSubmitting || gradeForm.invalid || studentInputs.length === 0"
                class="bg-blue-600 text-white px-8 py-2.5 rounded-xl hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-md shadow-blue-200 font-bold flex items-center gap-2">
          <i class="pi pi-check-circle"></i>
          <span>Enregistrer les Notes</span>
          <i *ngIf="isSubmitting" class="pi pi-spin pi-spinner ml-2"></i>
        </button>
      </div>
    </div>
  `
})
export class MpGradeEntryComponent implements OnInit {
  classId: string | null = null;
  classData: any = null;
  currentYear: any = null;
  subjects: any[] = [];
  studentInputs: StudentGradeInput[] = [];
  
  gradeForm: FormGroup;
  isSubmitting = false;

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private classService: ClassService,
    private gradeService: GradeService,
    private academicService: AcademicService
  ) {
    this.gradeForm = this.fb.group({
      trimestre: ['1', Validators.required],
      subject_id: ['', Validators.required],
      type_evaluation: ['DV', Validators.required],
      date_evaluation: [new Date().toISOString().split('T')[0], Validators.required]
    });
  }

  ngOnInit() {
    this.classId = this.route.snapshot.paramMap.get('id');
    if (this.classId) {
      this.loadInitialData();
    }
  }

  loadInitialData() {
    // 1. Get current academic year
    this.academicService.getCurrentYear().subscribe(year => {
      this.currentYear = year;
    });

    // 2. Get class and students
    this.classService.getStudentsByClass('mp', this.classId!).subscribe((res: any) => {
      // res is { class: {}, students: [] } if using our new route, or {} if using show
      // Using ClassMPController@show style: res.enrollments exists
      if (res.enrollments) {
        this.classData = res;
        this.studentInputs = res.enrollments.map((e: any) => ({
          student_id: e.student.id,
          matricule: e.student.matricule,
          full_name: `${e.student.nom} ${e.student.prenoms}`,
          note_obtenue: null,
          commentaire: ''
        }));
      } else if (res.class) {
        // ClassMPController@students style
        this.classData = res.class;
        this.studentInputs = res.students.map((s: any) => ({
          student_id: s.id,
          matricule: s.matricule,
          full_name: `${s.nom} ${s.prenoms}`,
          note_obtenue: null,
          commentaire: ''
        }));
      }

      // Load subjects based on class level
      if (this.classData) {
        this.loadSubjects(this.classData.niveau);
      }
    });
  }

  loadSubjects(niveau: string) {
    this.gradeService.getSubjectsMP(niveau).subscribe(subs => {
      this.subjects = subs;
    });
  }

  getScoreBase(): number {
    const type = this.gradeForm.get('type_evaluation')?.value;
    switch (type) {
      case 'IO': return 10;
      case 'CP': return 100;
      case 'DV':
      case 'TP':
      default: return 20;
    }
  }

  submitGrades() {
    // Basic validation: at least one grade entered and all entered grades are valid
    const validNotes = this.studentInputs.filter(s => s.note_obtenue !== null);
    
    if (validNotes.length === 0) {
      alert('Veuillez saisir au moins une note.');
      return;
    }

    const base = this.getScoreBase();
    if (validNotes.some(s => s.note_obtenue! > base || s.note_obtenue! < 0)) {
      alert(`Certaines notes sont invalides (doivent être entre 0 et ${base})`);
      return;
    }

    this.isSubmitting = true;
    
    const payload = {
      ...this.gradeForm.value,
      class_id: this.classId,
      school_year_id: 'current', // Backend handles 'current'
      notes: validNotes.map(s => ({
        student_id: s.student_id,
        note_obtenue: s.note_obtenue,
        commentaire: s.commentaire
      }))
    };

    this.gradeService.submitGradesMPBulk(payload).subscribe({
      next: (res) => {
        alert(`${res.created} notes ont été enregistrées avec succès !`);
        this.isSubmitting = false;
        this.goBack();
      },
      error: (err) => {
        console.error('Erreur lors de la soumission', err);
        alert('Une erreur est survenue lors de l\'enregistrement des notes.');
        this.isSubmitting = false;
      }
    });
  }

  goBack() {
    this.router.navigate(['/admin/mp/classes']);
  }
}
