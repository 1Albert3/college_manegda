import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterModule, Router } from '@angular/router';
import { ClassService } from '../../../core/services/class.service';

@Component({
  selector: 'app-college-student-list',
  standalone: true,
  imports: [CommonModule, RouterModule],
  template: `
    <div class="p-6 bg-gray-50 min-h-screen">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
           <button class="text-gray-500 hover:text-gray-800 mb-2 flex items-center gap-1 group transition-colors" (click)="goBack()">
              <i class="pi pi-arrow-left group-hover:-translate-x-1 transition-transform"></i> Retour aux classes
           </button>
           <div class="flex items-center gap-3">
             <h1 class="text-3xl font-extrabold text-gray-900" *ngIf="classData">
               {{ classData.niveau }} {{ classData.nom }}
             </h1>
             <span class="bg-blue-100 text-blue-700 text-xs font-black px-2 py-1 rounded uppercase tracking-widest border border-blue-200" *ngIf="classData">
               Cycle Collège
             </span>
           </div>
           <p class="text-gray-500 mt-1 font-medium" *ngIf="classData">
             <i class="pi pi-users mr-1"></i> Effectif actuel : <span class="text-blue-600">{{ students.length }}</span> élèves inscrits
           </p>
        </div>
        <div class="flex gap-3">
           <button class="bg-white border border-gray-200 text-gray-700 px-5 py-2.5 rounded-xl hover:bg-gray-50 transition shadow-sm flex items-center gap-2 font-bold text-sm">
              <i class="pi pi-print"></i>
              <span>Imprimer Liste</span>
           </button>
           <button class="bg-blue-600 text-white px-6 py-2.5 rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-100 flex items-center gap-2 font-bold text-sm">
              <i class="pi pi-user-plus"></i>
              <span>Inscrire un élève</span>
           </button>
        </div>
      </div>

      <!-- Students Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100 text-[10px] uppercase text-gray-400 font-black tracking-[0.2em]">
                    <th class="px-8 py-5">Matricule</th>
                    <th class="px-8 py-5">Identité de l'élève</th>
                    <th class="px-8 py-5">Date de Naissance</th>
                    <th class="px-8 py-5 text-center">Sexe</th>
                    <th class="px-8 py-5 text-center">Statut</th>
                    <th class="px-8 py-5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <tr *ngFor="let student of students" class="hover:bg-blue-50/30 transition-all duration-200 group">
                    <td class="px-8 py-5">
                      <span class="text-xs font-black text-blue-600 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">
                        {{ student.matricule }}
                      </span>
                    </td>
                    <td class="px-8 py-5">
                        <div class="font-bold text-gray-900 group-hover:text-blue-700 transition-colors uppercase italic">
                          {{ student.nom }} {{ student.prenoms }}
                        </div>
                    </td>
                    <td class="px-8 py-5 text-sm font-medium text-gray-500 italic">
                      {{ student.date_naissance | date:'dd MMMM yyyy' }}
                    </td>
                    <td class="px-8 py-5 text-center">
                        <span [class]="student.sexe === 'M' ? 'text-blue-600 bg-blue-50 border-blue-100' : 'text-rose-600 bg-rose-50 border-rose-100'" 
                              class="px-3 py-1 rounded-full text-[10px] font-black border tracking-widest lowercase">
                           {{ student.sexe === 'M' ? 'garçon' : 'fille' }}
                        </span>
                    </td>
                    <td class="px-8 py-5 text-center">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100 uppercase tracking-tight">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Inscrit
                        </span>
                    </td>
                    <td class="px-8 py-5 text-right">
                      <div class="flex justify-end gap-1 opacity-10 group-hover:opacity-100 transition-opacity">
                        <button class="text-blue-600 hover:bg-blue-100 p-2 rounded-lg transition-colors" title="Profil">
                            <i class="pi pi-id-card"></i>
                        </button>
                        <button class="text-gray-600 hover:bg-gray-100 p-2 rounded-lg transition-colors" title="Historique">
                            <i class="pi pi-history"></i>
                        </button>
                      </div>
                    </td>
                </tr>
                <tr *ngIf="students.length === 0 && !loading">
                    <td colspan="6" class="px-8 py-20 text-center">
                        <div class="flex flex-col items-center justify-center max-w-xs mx-auto">
                            <i class="pi pi-users text-4xl text-gray-200 mb-4"></i>
                            <h4 class="font-bold text-gray-800">Aucun élève trouvé</h4>
                            <p class="text-sm text-gray-500 mt-1">Aucun élève n'est encore inscrit dans cette classe du collège.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
      </div>

      <div *ngIf="loading" class="flex justify-center p-20">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    </div>
  `
})
export class CollegeStudentListComponent implements OnInit {
  classId: string | null = null;
  classData: any = null;
  students: any[] = [];
  loading = true;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private classService: ClassService
  ) {}

  ngOnInit() {
    this.classId = this.route.snapshot.paramMap.get('id');
    if (this.classId) {
      this.loadData();
    }
  }

  loadData() {
    this.loading = true;
    this.classService.getStudentsByClass('college', this.classId!).subscribe({
        next: (data) => {
           this.classData = data.class || data;
           this.students = data.students || (data.enrollments ? data.enrollments.map((e: any) => e.student) : []);
           this.loading = false;
        },
        error: (err) => {
            console.error('Erreur chargement élèves collège', err);
            this.loading = false;
        }
    });
  }

  goBack() {
    this.router.navigate(['/admin/college/classes']);
  }
}
