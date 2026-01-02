import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterModule, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { GradeService } from '../../../core/services/grade.service';
import { ClassService } from '../../../core/services/class.service';

@Component({
  selector: 'app-college-report-cards',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  template: `
    <div class="p-6 bg-gray-50 min-h-screen">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
           <button class="text-gray-500 hover:text-gray-800 mb-2 flex items-center gap-1 group transition-colors" (click)="goBack()">
              <i class="pi pi-arrow-left group-hover:-translate-x-1 transition-transform"></i> Retour aux classes
           </button>
           <h1 class="text-3xl font-extrabold text-gray-900" *ngIf="classData">
             Bulletins Collège - {{ classData.niveau }} {{ classData.nom }}
           </h1>
           <p class="text-gray-500 mt-1 font-medium" *ngIf="classData">
             Génération et consultation des rapports de performance trimestriels
           </p>
        </div>
        <div class="flex gap-3">
           <button (click)="downloadAll()" [disabled]="!hasGeneratedBulletins" 
                   class="bg-white border border-gray-200 text-gray-700 px-5 py-2.5 rounded-xl hover:bg-gray-50 transition shadow-sm flex items-center gap-2 font-bold text-sm">
              <i class="pi pi-download"></i>
              <span>Télécharger Tout (ZIP)</span>
           </button>
           <button (click)="generateBulletins()" [disabled]="!hasReadyStudents || isGenerating" 
                   class="bg-blue-600 text-white px-6 py-2.5 rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-100 flex items-center gap-2 font-bold text-sm">
              <i class="pi pi-sync" [class.pi-spin]="isGenerating"></i>
              <span>{{ isGenerating ? 'Génération...' : 'Générer la Sélection' }}</span>
           </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
             <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Trimestre</div>
             <select [(ngModel)]="trimestre" (change)="loadPreview()" 
                     class="w-full border-gray-100 bg-gray-50 rounded-xl font-bold text-blue-800 focus:ring-blue-500 py-3 px-4">
                <option value="1">1er Trimestre</option>
                <option value="2">2ème Trimestre</option>
                <option value="3">3ème Trimestre</option>
             </select>
          </div>
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
             <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 border border-emerald-100">
               <i class="pi pi-check text-xl"></i>
             </div>
             <div>
               <div class="text-2xl font-black text-gray-900 leading-none">{{ countReady }}</div>
               <div class="text-[10px] font-bold text-gray-400 uppercase mt-1">Élèves Prêts</div>
             </div>
          </div>
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
             <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 border border-blue-100">
               <i class="pi pi-file text-xl"></i>
             </div>
             <div>
               <div class="text-2xl font-black text-gray-900 leading-none">{{ countGenerated }}</div>
               <div class="text-[10px] font-bold text-gray-400 uppercase mt-1">Déjà Générés</div>
             </div>
          </div>
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
             <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center text-orange-600 border border-orange-100">
               <i class="pi pi-exclamation-triangle text-xl"></i>
             </div>
             <div>
               <div class="text-2xl font-black text-gray-900 leading-none">{{ countIncomplete }}</div>
               <div class="text-[10px] font-bold text-gray-400 uppercase mt-1">Incomplets</div>
             </div>
          </div>
      </div>

      <!-- Main List -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100 text-[10px] uppercase text-gray-400 font-black tracking-[0.2em]">
                    <th class="px-8 py-5 w-10">
                        <input type="checkbox" (change)="toggleAll($event)" [checked]="allSelected" class="rounded text-blue-600 focus:ring-blue-500">
                    </th>
                    <th class="px-8 py-5">Identité de l'élève</th>
                    <th class="px-8 py-5 text-center">Notes saisies</th>
                    <th class="px-8 py-5 text-center">Moyenne Prévue</th>
                    <th class="px-8 py-5 text-center">Rang</th>
                    <th class="px-8 py-5 text-center">Statut</th>
                    <th class="px-8 py-5 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <tr *ngFor="let p of previews" class="hover:bg-blue-50/30 transition-all duration-200 group">
                    <td class="px-8 py-5">
                        <input type="checkbox" [(ngModel)]="p.selected" [disabled]="p.status === 'incomplete'" class="rounded text-blue-600 focus:ring-blue-500">
                    </td>
                    <td class="px-8 py-5">
                        <div class="font-bold text-gray-900 group-hover:text-blue-700 transition-colors">{{ p.student_name }}</div>
                        <div class="text-[10px] text-gray-400 font-black tracking-widest uppercase mt-0.5">{{ p.matricule }}</div>
                    </td>
                    <td class="px-8 py-5 text-center">
                        <span class="text-xs font-black text-gray-600 bg-gray-100 px-2 py-1 rounded shadow-inner">{{ p.grades_count }}</span>
                    </td>
                    <td class="px-8 py-5 text-center">
                        <span class="text-base font-black" [class.text-rose-600]="p.moyenne_generale < 10 && p.status !== 'incomplete'" 
                              [class.text-emerald-600]="p.moyenne_generale >= 10">
                            {{ p.status === 'incomplete' ? '-' : (p.moyenne_generale | number:'1.2-2') }}
                        </span>
                    </td>
                    <td class="px-8 py-5 text-center">
                        <span class="text-xs font-bold text-gray-700" *ngIf="p.status !== 'incomplete'">
                            {{ p.rang }}{{ p.rang === 1 ? 'er' : 'eme' }}
                        </span>
                        <span *ngUnless="p.status !== 'incomplete'">-</span>
                    </td>
                    <td class="px-8 py-5 text-center">
                        <div [class]="getStatusClass(p.status)" class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter border">
                            {{ p.status }}
                        </div>
                    </td>
                    <td class="px-8 py-5 text-right">
                        <div class="flex justify-end gap-2">
                          <button *ngIf="p.status === 'generated'" (click)="viewPdf(p.pdf_url)" 
                                  class="text-blue-600 hover:bg-blue-100 p-2 rounded-lg transition-colors border border-blue-100 shadow-sm bg-white" title="Voir PDF">
                              <i class="pi pi-file-pdf"></i>
                          </button>
                          <button *ngIf="p.status === 'ready'" (click)="generateOne(p)" 
                                  class="text-emerald-600 hover:bg-emerald-100 p-2 rounded-lg transition-colors border border-emerald-100 shadow-sm bg-white" title="Calculer">
                              <i class="pi pi-bolt"></i>
                          </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div *ngIf="loading" class="p-20 flex justify-center">
           <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
      </div>
    </div>
  `
})
export class CollegeReportCardsComponent implements OnInit {
  classId: string | null = null;
  classData: any = null;
  trimestre = '1';
  previews: any[] = [];
  loading = false;
  isGenerating = false;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private gradeService: GradeService,
    private classService: ClassService
  ) {}

  ngOnInit() {
    this.classId = this.route.snapshot.paramMap.get('id');
    if (this.classId) {
      this.loadPreview();
    }
  }

  loadPreview() {
    this.loading = true;
    this.gradeService.getReportCardPreviewCollege(this.classId!, this.trimestre).subscribe({
      next: (res) => {
        this.classData = res.class;
        this.previews = res.data.map((p: any) => ({
            ...p,
            selected: false
        }));
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur preview bulletins collège', err);
        this.loading = false;
      }
    });
  }

  get countReady() { return this.previews.filter(p => p.status === 'ready').length; }
  get countGenerated() { return this.previews.filter(p => p.status === 'generated').length; }
  get countIncomplete() { return this.previews.filter(p => p.status === 'incomplete').length; }
  get hasReadyStudents() { return this.previews.some(p => p.selected && p.status === 'ready'); }
  get hasGeneratedBulletins() { return this.previews.some(p => p.status === 'generated'); }
  get allSelected() { return this.previews.length > 0 && this.previews.filter(p => p.status !== 'incomplete').every(p => p.selected); }

  toggleAll(event: any) {
    const checked = event.target.checked;
    this.previews.forEach(p => {
        if (p.status !== 'incomplete') p.selected = checked;
    });
  }

  generateBulletins() {
    const selectedIds = this.previews.filter(p => p.selected && p.status === 'ready').map(p => p.student_id);
    if (selectedIds.length === 0) return;

    this.isGenerating = true;
    this.gradeService.generateReportCardsCollege({
        class_id: this.classId!,
        trimestre: this.trimestre,
        student_ids: selectedIds
    }).subscribe({
        next: (res) => {
            alert(res.message);
            this.isGenerating = false;
            this.loadPreview();
        },
        error: (err) => {
            console.error('Erreur génération', err);
            this.isGenerating = false;
            alert('Une erreur est survenue.');
        }
    });
  }

  generateOne(preview: any) {
      this.isGenerating = true;
      this.gradeService.generateReportCardsCollege({
          class_id: this.classId!,
          trimestre: this.trimestre,
          student_ids: [preview.student_id]
      }).subscribe({
          next: () => {
              this.isGenerating = false;
              this.loadPreview();
          },
          error: () => this.isGenerating = false
      });
  }

  getStatusClass(status: string) {
      switch(status) {
          case 'generated': return 'bg-blue-50 text-blue-700 border-blue-100';
          case 'ready': return 'bg-emerald-50 text-emerald-700 border-emerald-100';
          default: return 'bg-orange-50 text-orange-700 border-orange-100';
      }
  }

  viewPdf(url: string) { if (url) window.open(url, '_blank'); }

  downloadAll() {
      this.gradeService.downloadAllReportCardsCollege(this.classId!, this.trimestre).subscribe({
          next: (blob) => {
              const url = window.URL.createObjectURL(blob);
              const a = document.createElement('a');
              a.href = url;
              a.download = `bulletins_college_${this.classData.nom}_T${this.trimestre}.zip`;
              a.click();
              window.URL.revokeObjectURL(url);
          },
          error: (err) => console.error('Erreur téléchargement ZIP', err)
      });
  }

  goBack() {
    this.router.navigate(['/admin/college/classes']);
  }
}
