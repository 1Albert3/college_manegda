import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterModule, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { GradeService } from '../../../core/services/grade.service';
import { ClassService } from '../../../core/services/class.service';

@Component({
  selector: 'app-lycee-report-cards',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  template: `
    <div class="p-6 bg-gray-50 min-h-screen">
      <!-- En-tête -->
      <div class="flex items-center justify-between mb-8">
        <div>
           <button class="text-gray-500 hover:text-gray-800 mb-2 flex items-center gap-1 group transition-colors" (click)="goBack()">
              <i class="pi pi-arrow-left group-hover:-translate-x-1 transition-transform"></i> Retour aux classes
           </button>
           <h1 class="text-3xl font-extrabold text-gray-900" *ngIf="classData">
             Gestion des Bulletins Lycée - {{ classData.nom }}
           </h1>
           <p class="text-gray-500 mt-1 font-medium" *ngIf="classData">
             Session de validation et génération des moyennes trimestrielles
           </p>
        </div>
        <div class="flex gap-3">
           <button (click)="downloadAll()" [disabled]="!hasGeneratedBulletins" 
                   class="bg-white border border-gray-200 text-gray-700 px-5 py-2.5 rounded-xl hover:bg-gray-50 transition shadow-sm flex items-center gap-2 font-bold text-sm">
              <i class="pi pi-download"></i>
              <span>Tout exporter (ZIP)</span>
           </button>
           <button (click)="generateBulletins()" [disabled]="!hasReadyStudents || isGenerating" 
                   class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 flex items-center gap-2 font-bold text-sm">
              <i class="pi pi-sync" [class.pi-spin]="isGenerating"></i>
              <span>{{ isGenerating ? 'Génération en cours...' : 'Générer la Sélection' }}</span>
           </button>
        </div>
      </div>

      <!-- Filtres & Stats -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8 flex flex-col md:flex-row md:items-center gap-6">
          <div class="flex items-center gap-4">
              <label class="text-sm font-black text-gray-400 uppercase tracking-widest">Trimestre :</label>
              <select [(ngModel)]="trimestre" (change)="loadPreview()" 
                      class="border-gray-200 rounded-xl text-sm font-bold focus:ring-indigo-500 focus:border-indigo-500 py-2 shadow-sm">
                  <option value="1">1er Trimestre</option>
                  <option value="2">2ème Trimestre</option>
                  <option value="3">3ème Trimestre</option>
              </select>
          </div>
          <div class="hidden md:block h-8 w-px bg-gray-100"></div>
          <div class="flex items-center gap-8 text-xs font-bold uppercase tracking-tight">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span class="text-gray-400">Prêt:</span> <span class="text-emerald-700">{{ countReady }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                <span class="text-gray-400">Généré:</span> <span class="text-indigo-700">{{ countGenerated }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-orange-400"></span>
                <span class="text-gray-400">Incomplet:</span> <span class="text-orange-700">{{ countIncomplete }}</span>
              </div>
          </div>
      </div>

      <!-- Liste des élèves -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100 text-[10px] uppercase text-gray-400 font-black tracking-[0.2em]">
                    <th class="px-8 py-5 w-10">
                        <input type="checkbox" (change)="toggleAll($event)" [checked]="allSelected" class="rounded text-indigo-600 focus:ring-indigo-500">
                    </th>
                    <th class="px-8 py-5">Élève</th>
                    <th class="px-8 py-5 text-center">Évaluations</th>
                    <th class="px-8 py-5 text-center">Moyenne / 20</th>
                    <th class="px-8 py-5 text-center">Rang Prévu</th>
                    <th class="px-8 py-5 text-center">Statut</th>
                    <th class="px-8 py-5 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <tr *ngFor="let p of previews" class="hover:bg-indigo-50/30 transition-all duration-200 group">
                    <td class="px-8 py-5">
                        <input type="checkbox" [(ngModel)]="p.selected" [disabled]="p.status === 'incomplete'" class="rounded text-indigo-600 focus:ring-indigo-500">
                    </td>
                    <td class="px-8 py-5">
                        <div class="font-bold text-gray-900">{{ p.student_name }}</div>
                        <div class="text-[10px] text-gray-400 font-black tracking-widest uppercase mt-0.5">{{ p.matricule }}</div>
                    </td>
                    <td class="px-8 py-5 text-center">
                        <span class="text-xs font-black text-gray-600 bg-gray-100 px-2 py-1 rounded">{{ p.grades_count }}</span>
                    </td>
                    <td class="px-8 py-5 text-center">
                        <span class="text-sm font-black" [class.text-rose-600]="p.moyenne_generale < 10 && p.status !== 'incomplete'" 
                              [class.text-emerald-600]="p.moyenne_generale >= 10">
                            {{ p.status === 'incomplete' ? '-' : (p.moyenne_generale | number:'1.2-2') }}
                        </span>
                    </td>
                    <td class="px-8 py-5 text-center">
                        <span class="text-xs font-bold text-gray-700" *ngIf="p.status !== 'incomplete'">
                            {{ p.rang }}{{ p.rang === 1 ? 'er' : 'ème' }}
                        </span>
                        <span *ngIf="p.status === 'incomplete'">-</span>
                    </td>
                    <td class="px-8 py-5 text-center">
                        <span *ngIf="p.status === 'generated'" class="text-[10px] font-black px-2 py-1 rounded bg-indigo-50 text-indigo-700 border border-indigo-100 uppercase tracking-tighter">
                            GÉNÉRÉ
                        </span>
                        <span *ngIf="p.status === 'ready'" class="text-[10px] font-black px-2 py-1 rounded bg-emerald-50 text-emerald-700 border border-emerald-100 uppercase tracking-tighter">
                            PRÊT
                        </span>
                        <span *ngIf="p.status === 'incomplete'" class="text-[10px] font-black px-2 py-1 rounded bg-orange-50 text-orange-700 border border-orange-100 uppercase tracking-tighter" title="Note manquante">
                            INCOMPLET
                        </span>
                    </td>
                    <td class="px-8 py-5 text-right">
                        <button *ngIf="p.status === 'generated'" (click)="viewPdf(p.pdf_url)" 
                                class="text-indigo-600 hover:bg-indigo-100 p-2 rounded-lg transition-colors shadow-sm bg-white border border-gray-100" title="Consulter PDF">
                            <i class="pi pi-file-pdf"></i>
                        </button>
                        <button *ngIf="p.status === 'ready'" (click)="generateOne(p)" 
                                class="text-emerald-600 hover:bg-emerald-100 p-2 rounded-lg transition-colors shadow-sm bg-white border border-gray-100" title="Lancer le calcul">
                            <i class="pi pi-bolt"></i>
                        </button>
                    </td>
                </tr>
                <tr *ngIf="previews.length === 0 && !loading">
                    <td colspan="7" class="px-8 py-20 text-center text-gray-400 italic">
                        Chargement de l'état des élèves...
                    </td>
                </tr>
            </tbody>
        </table>
      </div>
    </div>
  `
})
export class LyceeReportCardsComponent implements OnInit {
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
    this.gradeService.getReportCardPreviewLycee(this.classId!, this.trimestre).subscribe({
      next: (res) => {
        this.classData = res.class;
        this.previews = res.data.map((p: any) => ({
            ...p,
            selected: false
        }));
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur preview bulletins lycée', err);
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
    this.gradeService.generateReportCardsLycee({
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
      this.gradeService.generateReportCardsLycee({
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
      this.gradeService.downloadAllReportCardsLycee(this.classId!, this.trimestre).subscribe({
          next: (blob) => {
              const url = window.URL.createObjectURL(blob);
              const a = document.createElement('a');
              a.href = url;
              a.download = `bulletins_lycee_${this.classData.nom}_T${this.trimestre}.zip`;
              a.click();
              window.URL.revokeObjectURL(url);
          },
          error: (err) => console.error('Erreur téléchargement ZIP', err)
      });
  }

  goBack() {
    this.router.navigate(['/admin/lycee/classes']);
  }
}
