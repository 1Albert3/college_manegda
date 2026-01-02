import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterModule, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { GradeService } from '../../../core/services/grade.service';
import { ClassService } from '../../../core/services/class.service';

@Component({
  selector: 'app-mp-report-cards',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  template: `
    <div class="p-6 bg-gray-50 min-h-screen">
      <!-- En-tête -->
      <div class="flex items-center justify-between mb-6">
        <div>
           <button class="text-gray-500 hover:text-gray-800 mb-2 flex items-center gap-1" (click)="goBack()">
              <i class="pi pi-arrow-left"></i> Retour aux classes
           </button>
           <h1 class="text-2xl font-bold text-gray-800" *ngIf="classData">
             Gestion des Bulletins - {{ classData.nom }} ({{ classData.niveau }})
           </h1>
        </div>
        <div class="flex gap-2">
           <button (click)="downloadAll()" [disabled]="!hasGeneratedBulletins" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition shadow-sm flex items-center gap-2">
              <i class="pi pi-download"></i>
              <span>Télécharger Tout (ZIP)</span>
           </button>
           <button (click)="generateBulletins()" [disabled]="!hasReadyStudents || isGenerating" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition shadow-sm flex items-center gap-2">
              <i class="pi pi-sync" [class.pi-spin]="isGenerating"></i>
              <span>{{ isGenerating ? 'Génération...' : 'Générer la Sélection' }}</span>
           </button>
        </div>
      </div>

      <!-- Filtres -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6 flex items-center gap-4">
          <div class="flex items-center gap-2">
              <label class="text-sm font-medium text-gray-600">Trimestre :</label>
              <select [(ngModel)]="trimestre" (change)="loadPreview()" class="border-gray-300 rounded-lg text-sm shadow-sm">
                  <option value="1">1er Trimestre</option>
                  <option value="2">2ème Trimestre</option>
                  <option value="3">3ème Trimestre</option>
              </select>
          </div>
          <div class="h-6 w-px bg-gray-200"></div>
          <div class="text-sm text-gray-500">
              Prêt: <span class="text-green-600 font-bold ml-1">{{ countReady }}</span>
              | Généré: <span class="text-blue-600 font-bold ml-1">{{ countGenerated }}</span>
              | Incomplet: <span class="text-orange-600 font-bold ml-1">{{ countIncomplete }}</span>
          </div>
      </div>

      <!-- Liste des élèves -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold tracking-wider">
                    <th class="px-6 py-4 w-10">
                        <input type="checkbox" (change)="toggleAll($event)" [checked]="allSelected" class="rounded text-blue-600 focus:ring-blue-500">
                    </th>
                    <th class="px-6 py-4">Élève</th>
                    <th class="px-6 py-4 text-center">Notes</th>
                    <th class="px-6 py-4 text-center">Moyenne</th>
                    <th class="px-6 py-4 text-center">Rang</th>
                    <th class="px-6 py-4 text-center">Statut</th>
                    <th class="px-6 py-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr *ngFor="let p of previews" class="hover:bg-blue-50/30 transition-colors">
                    <td class="px-6 py-4">
                        <input type="checkbox" [(ngModel)]="p.selected" [disabled]="p.status === 'incomplete'" class="rounded text-blue-600 focus:ring-blue-500">
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-semibold text-gray-900">{{ p.student_name }}</div>
                        <div class="text-xs text-gray-500">{{ p.matricule }}</div>
                    </td>
                    <td class="px-6 py-4 text-center text-sm text-gray-600">
                        {{ p.grades_count }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-sm font-bold" [class.text-red-600]="p.moyenne_generale < 10 && p.status !== 'incomplete'">
                            {{ p.status === 'incomplete' ? '-' : p.moyenne_generale }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center text-sm font-medium text-gray-700">
                        {{ p.status === 'incomplete' ? '-' : (p.rang + (p.rang === 1 ? 'er' : 'ème')) }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span *ngIf="p.status === 'generated'" class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-blue-100 text-blue-800">
                            GÉNÉRÉ
                        </span>
                        <span *ngIf="p.status === 'ready'" class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-green-100 text-green-800">
                            PRÊT
                        </span>
                        <span *ngIf="p.status === 'incomplete'" class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-orange-100 text-orange-800" title="Pas assez de notes">
                            INCOMPLET
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button *ngIf="p.status === 'generated'" (click)="viewPdf(p.pdf_url)" class="text-blue-600 hover:text-blue-800 p-1.5 rounded hover:bg-blue-50" title="Voir PDF">
                            <i class="pi pi-file-pdf text-xl"></i>
                        </button>
                        <button *ngIf="p.status === 'ready'" (click)="generateOne(p)" class="text-green-600 hover:text-green-800 p-1.5 rounded hover:bg-green-50" title="Générer">
                            <i class="pi pi-plus-circle text-xl"></i>
                        </button>
                    </td>
                </tr>
                <tr *ngIf="previews.length === 0 && !loading">
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500 italic">
                        Chargement des aperçus...
                    </td>
                </tr>
            </tbody>
        </table>
      </div>
    </div>
  `
})
export class MpReportCardsComponent implements OnInit {
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
    this.gradeService.getReportCardPreviewMP(this.classId!, this.trimestre).subscribe({
      next: (res) => {
        this.classData = res.class;
        this.previews = res.data.map((p: any) => ({
            ...p,
            selected: false
        }));
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur preview bulletins', err);
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
    this.gradeService.generateReportCardsMP({
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
            alert('Une erreur est survenue lors de la génération.');
        }
    });
  }

  generateOne(preview: any) {
      this.isGenerating = true;
      this.gradeService.generateReportCardsMP({
          class_id: this.classId!,
          trimestre: this.trimestre,
          student_ids: [preview.student_id]
      }).subscribe({
          next: () => {
              this.isGenerating = false;
              this.loadPreview();
          },
          error: (err) => {
              console.error('Erreur génération', err);
              this.isGenerating = false;
          }
      });
  }

  viewPdf(url: string) {
      if (url) window.open(url, '_blank');
  }

  downloadAll() {
      this.gradeService.downloadAllReportCardsMP(this.classId!, this.trimestre).subscribe({
          next: (blob) => {
              const url = window.URL.createObjectURL(blob);
              const a = document.createElement('a');
              a.href = url;
              a.download = `bulletins_${this.classData.nom}_T${this.trimestre}.zip`;
              a.click();
              window.URL.revokeObjectURL(url);
          },
          error: (err) => console.error('Erreur téléchargement ZIP', err)
      });
  }

  goBack() {
    this.router.navigate(['/admin/mp/classes']);
  }
}
