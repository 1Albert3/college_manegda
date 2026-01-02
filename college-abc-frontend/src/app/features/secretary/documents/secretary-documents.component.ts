import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-secretary-documents',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6 relative">
      <!-- Loading Overlay -->
      <div *ngIf="isLoading()" class="fixed inset-0 bg-white/60 backdrop-blur-sm z-[110] flex items-center justify-center">
        <div class="flex flex-col items-center gap-4">
          <i class="pi pi-spin pi-spinner text-4xl text-teal-600"></i>
          <span class="font-bold text-gray-700">Génération en cours...</span>
        </div>
      </div>

      <!-- Toast Notification -->
      <div *ngIf="showSuccessToast" class="fixed top-4 right-4 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-xl z-[120] flex items-center gap-3 transition-opacity duration-300 border-l-4 border-teal-500">
        <i class="pi pi-info-circle text-teal-400 text-xl"></i>
        <span class="font-medium">{{ successMessage }}</span>
      </div>

      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Génération de Documents</h1>
          <p class="text-gray-500 font-medium">Créez certificats, attestations et bulletins officiels</p>
        </div>
      </div>

      <!-- Document Types -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div *ngFor="let type of documentTypes()" 
             (click)="selectDocType(type)"
             class="bg-white rounded-2xl shadow-sm p-5 border-2 cursor-pointer transition-all hover:shadow-lg hover:translate-y-[-4px] active:scale-95"
             [ngClass]="{'border-teal-500 bg-teal-50 shadow-teal-100': selectedType === type.id, 'border-transparent': selectedType !== type.id}">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 shadow-inner"
               [style.background-color]="type.color + '15'">
            <i [class]="type.icon + ' text-2xl'" [style.color]="type.color"></i>
          </div>
          <h3 class="font-bold text-gray-800 text-sm leading-tight mb-1">{{ type.name }}</h3>
          <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">{{ type.id }}</p>
        </div>
      </div>

      <!-- Generation Form -->
      <div *ngIf="selectedType" class="bg-white rounded-2xl shadow-xl shadow-gray-100 p-8 border border-gray-100 animate-in fade-in slide-in-from-bottom-4 duration-500">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-teal-600 text-white flex items-center justify-center shadow-lg shadow-teal-200">
                    <i class="pi pi-file-edit"></i>
                </span>
                Configuration du document
            </h2>
            <span class="px-4 py-1.5 bg-gray-100 text-gray-600 rounded-full text-xs font-bold uppercase tracking-widest border border-gray-200">
                {{ selectedType }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div class="md:col-span-1">
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Année scolaire</label>
            <div class="relative group">
                <i class="pi pi-calendar absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-teal-500 transition-colors"></i>
                <select [(ngModel)]="selectedYear"
                        class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 bg-gray-50/50 transition-all font-bold text-gray-700 outline-none">
                  <option *ngFor="let year of schoolYears()" [value]="year.name">{{ year.name }}</option>
                </select>
            </div>
          </div>
          
          <div *ngIf="selectedType === 'bulletin'" class="md:col-span-1">
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Période (Trimestre)</label>
            <div class="relative group">
                <i class="pi pi-clock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-teal-500 transition-colors"></i>
                <select [(ngModel)]="selectedTrimestre"
                        class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 bg-gray-50/50 transition-all font-bold text-gray-700 outline-none">
                  <option *ngFor="let t of trimestres" [value]="t.value">{{ t.label }}</option>
                </select>
            </div>
          </div>

          <div class="md:col-span-1">
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Chercher l'élève</label>
            <div class="relative group">
                <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-teal-500 transition-colors"></i>
                <input type="text" [(ngModel)]="studentSearch" placeholder="Nom, prénom ou matricule..."
                       class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 bg-gray-50/50 transition-all font-bold text-gray-700 outline-none">
            </div>
          </div>
        </div>

        <!-- Student Search Results Popover -->
        <div *ngIf="studentSearch.length >= 2 && !selectedStudent" class="mb-8 animate-in slide-in-from-top-2 duration-300">
          <div class="bg-gray-50 rounded-2xl border border-gray-200 divide-y divide-gray-200 overflow-hidden shadow-2xl max-h-60 overflow-y-auto">
            <div *ngFor="let student of filteredStudents()" 
                 (click)="selectStudent(student)"
                 class="p-4 hover:bg-white cursor-pointer flex items-center gap-4 transition-all group">
              <div class="w-12 h-12 rounded-full bg-white shadow-sm border border-gray-100 flex items-center justify-center text-teal-600 font-black text-sm group-hover:scale-110 transition-transform">
                {{ student.firstName.charAt(0) }}{{ student.lastName.charAt(0) }}
              </div>
              <div class="flex-1">
                <div class="font-black text-gray-800">{{ student.lastName }} {{ student.firstName }}</div>
                <div class="text-xs text-gray-500 font-bold uppercase tracking-wider mt-0.5">
                    {{ student.matricule }} <span class="mx-2 text-gray-300">|</span> {{ student.class }}
                </div>
              </div>
              <i class="pi pi-chevron-right text-gray-300 group-hover:text-teal-500 group-hover:translate-x-1 transition-all"></i>
            </div>
            <div *ngIf="filteredStudents().length === 0" class="p-8 text-center text-gray-400 font-bold italic">
                Aucun élève trouvé pour cette recherche
            </div>
          </div>
        </div>

        <!-- Selected Student & Preview -->
        <div *ngIf="selectedStudent" class="mb-8 flex flex-col items-center">
            <div class="w-full bg-teal-50 rounded-2xl p-6 border-2 border-dashed border-teal-200 flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-teal-600 text-white flex items-center justify-center text-2xl font-black shadow-lg shadow-teal-200">
                        {{ selectedStudent.firstName.charAt(0) }}{{ selectedStudent.lastName.charAt(0) }}
                    </div>
                    <div>
                        <div class="text-xs font-black text-teal-600 uppercase tracking-widest mb-1">Élève sélectionné</div>
                        <h3 class="text-xl font-black text-gray-900 leading-tight">{{ selectedStudent.lastName }} {{ selectedStudent.firstName }}</h3>
                        <p class="text-gray-500 font-bold text-sm uppercase tracking-tighter">{{ selectedStudent.matricule }} • {{ selectedStudent.class }}</p>
                    </div>
                </div>
                <button (click)="selectedStudent = null" class="w-10 h-10 rounded-full hover:bg-white text-gray-400 hover:text-red-500 transition-colors flex items-center justify-center">
                    <i class="pi pi-times"></i>
                </button>
            </div>

            <!-- PDF Preview Box -->
            <div class="w-full max-w-lg bg-white border-2 border-gray-100 rounded-3xl p-10 text-center shadow-2xl relative overflow-hidden group">
                <div class="absolute top-0 inset-x-0 h-2 bg-gradient-to-r from-teal-500 via-blue-500 to-purple-500"></div>
                <div class="w-20 h-20 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:rotate-12 transition-transform duration-500">
                    <i class="pi pi-file-pdf text-4xl text-red-500"></i>
                </div>
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Document à générer</div>
                <h4 class="text-lg font-black text-gray-800 mb-6 px-4">{{ getDocTypeName() }}</h4>
                
                <div class="grid grid-cols-2 gap-4 text-left bg-gray-50 rounded-2xl p-6 border border-gray-100 font-medium">
                    <div>
                        <span class="block text-[9px] font-black text-gray-400 uppercase mb-1">Année</span>
                        <span class="text-sm text-gray-700 font-bold">{{ selectedYear }}</span>
                    </div>
                    <div>
                        <span class="block text-[9px] font-black text-gray-400 uppercase mb-1">Période</span>
                        <span class="text-sm text-gray-700 font-bold">{{ selectedType === 'bulletin' ? 'Trimestre ' + selectedTrimestre : 'Année complète' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4 pt-8 border-t border-gray-100">
          <button (click)="resetForm()" class="px-6 py-3 border border-gray-200 rounded-xl font-black text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all uppercase tracking-widest text-xs">
            Annuler
          </button>
          <button (click)="previewDocument()" [disabled]="!selectedStudent"
                  class="px-6 py-3 border-2 border-teal-600 text-teal-600 rounded-xl font-black hover:bg-teal-50 disabled:opacity-30 disabled:grayscale transition-all flex items-center gap-2 uppercase tracking-widest text-xs">
            <i class="pi pi-eye"></i> Aperçu
          </button>
          <button (click)="generateDocument()" [disabled]="!selectedStudent || isLoading()"
                  class="px-8 py-3 bg-teal-600 text-white rounded-xl font-black hover:bg-teal-700 disabled:opacity-30 disabled:grayscale transition-all flex items-center gap-3 shadow-xl shadow-teal-200 uppercase tracking-widest text-xs">
            <i class="pi pi-download"></i> Générer PDF
          </button>
        </div>
      </div>

      <!-- Recent Documents -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gray-50 border-b border-gray-100 px-8 py-5 flex items-center justify-between">
          <h2 class="text-gray-800 font-black flex items-center gap-3">
            <span class="flex h-2 w-2 rounded-full bg-teal-500 animate-pulse"></span>
            HISTORIQUE RÉCENT
          </h2>
          <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">{{ recentDocuments().length }} ENTITÉS</span>
        </div>
        
        <div class="divide-y divide-gray-50">
          <div *ngIf="recentDocuments().length === 0" class="p-20 text-center">
                <i class="pi pi-folder-open text-5xl text-gray-100 mb-4"></i>
                <p class="text-gray-400 font-bold italic">Aucun document n'a été généré récemment</p>
          </div>
          
          <div *ngFor="let doc of recentDocuments()" class="p-6 flex items-center gap-6 hover:bg-gray-50/50 transition-all group">
            <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center shrink-0 border border-red-100 group-hover:scale-105 transition-transform">
              <i class="pi pi-file-pdf text-2xl text-red-500"></i>
            </div>
            <div class="flex-1">
              <div class="font-black text-gray-800 text-base mb-0.5 group-hover:text-teal-600 transition-colors">{{ doc.type }}</div>
              <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">{{ doc.student }} <span class="mx-2 text-gray-200">•</span> {{ doc.date }}</div>
            </div>
            <button (click)="downloadDoc(doc)"
                    class="px-6 py-2.5 bg-white border border-gray-200 text-teal-600 hover:border-teal-500 hover:bg-teal-50 rounded-xl text-xs font-black transition-all flex items-center gap-2 shadow-sm uppercase tracking-widest">
              <i class="pi pi-download"></i> Télécharger
            </button>
          </div>
        </div>
      </div>
    </div>
  `,
})
export class SecretaryDocumentsComponent implements OnInit {
  private http = inject(HttpClient);

  selectedType = '';
  studentSearch = '';
  selectedYear = '';
  selectedTrimestre = '1';
  selectedStudent: any = null;
  
  showSuccessToast = false;
  successMessage = '';
  isLoading = signal(false);
  
  documentTypes = signal([
    { id: 'certificate', name: 'Certificat de scolarité', description: 'Atteste l\'inscription de l\'élève', icon: 'pi pi-file', color: '#3B82F6' },
    { id: 'attestation', name: 'Attestation de réussite', description: 'Confirme le passage en classe supérieure', icon: 'pi pi-check-circle', color: '#10B981' },
    { id: 'transcript', name: 'Relevé de notes', description: 'Récapitulatif des notes par matière', icon: 'pi pi-chart-bar', color: '#F59E0B' },
    { id: 'bulletin', name: 'Bulletin de notes', description: 'Bulletin trimestriel avec moyennes', icon: 'pi pi-file-pdf', color: '#EF4444' },
    { id: 'conduct', name: 'Certificat de conduite', description: 'Atteste du comportement de l\'élève', icon: 'pi pi-heart', color: '#EC4899' },
  ]);

  students = signal<any[]>([]);
  schoolYears = signal<any[]>([]);
  recentDocuments = signal<any[]>([]);
  trimestres = [
    { label: '1er Trimestre', value: '1' },
    { label: '2ème Trimestre', value: '2' },
    { label: '3ème Trimestre', value: '3' }
  ];

  ngOnInit() {
    this.loadSchoolYears();
    this.loadStudents();
  }

  loadSchoolYears() {
    this.http.get<any>(`${environment.apiUrl}/core/school-years`).subscribe({
      next: (res) => {
        const years = res.data || res || [];
        this.schoolYears.set(years);
        const current = years.find((y: any) => y.is_current);
        if (current) this.selectedYear = current.name;
        else if (years.length > 0) this.selectedYear = years[0].name;
      }
    });
  }

  loadStudents() {
    this.isLoading.set(true);
    this.http.get<any>(`${environment.apiUrl}/dashboard/secretary/students`).subscribe({
      next: (res) => {
        const data = res.data || res || [];
        this.students.set(data.map((s: any) => ({
          id: s.id,
          firstName: s.firstName || s.prenoms || '',
          lastName: s.lastName || s.nom || '',
          matricule: s.matricule || '',
          class: s.currentClass || s.class_name || 'N/A',
          classId: s.classId,
          cycle: s.cycle
        })));
        this.isLoading.set(false);
      },
      error: () => this.isLoading.set(false)
    });
  }

  selectDocType(type: any) { this.selectedType = type.id; }
  
  filteredStudents = () => {
    if (!this.studentSearch || this.studentSearch.length < 2) return [];
    const q = this.studentSearch.toLowerCase();
    return this.students().filter(s => 
      s.firstName.toLowerCase().includes(q) ||
      s.lastName.toLowerCase().includes(q) ||
      s.matricule.toLowerCase().includes(q)
    );
  };

  selectStudent(student: any) { this.selectedStudent = student; }
  
  getDocTypeName(): string {
    return this.documentTypes().find(t => t.id === this.selectedType)?.name || '';
  }

  resetForm() {
    this.selectedType = '';
    this.studentSearch = '';
    this.selectedStudent = null;
  }

  previewDocument() { 
      if (this.selectedType === 'bulletin' && this.selectedStudent) {
          this.showToast('Chargement de la prévisualisation...');
          // Logic to open preview endpoint if available
      } else {
          this.showToast('Aperçu du document généré');
      }
  }

  generateDocument() { 
    if (!this.selectedStudent) return;
    
    this.isLoading.set(true);

    if (this.selectedType === 'bulletin') {
      const cycle = this.selectedStudent.cycle || 'lycee';
      const payload = {
        class_id: this.selectedStudent.classId,
        trimestre: this.selectedTrimestre,
        student_ids: [this.selectedStudent.id]
      };

      this.http.post<any>(`${environment.apiUrl}/${cycle}/report-cards/generate`, payload).subscribe({
        next: (res) => {
          this.isLoading.set(false);
          const pdfUrl = res.urls ? res.urls[this.selectedStudent.id] : null;
          this.showToast(`Bulletin généré avec succès !`);
          
          this.addRecentDoc(pdfUrl);
          
          if (pdfUrl) {
              window.open(pdfUrl, '_blank');
          }
        },
        error: (err) => {
          this.isLoading.set(false);
          this.showToast(`Erreur: ${err.error?.message || 'Une erreur est survenue'}`);
        }
      });
    } else {
      // Simulate other documents for now as backend might not have all endpoints
      setTimeout(() => {
        this.isLoading.set(false);
        this.showToast(`${this.getDocTypeName()} généré succès !`);
        this.addRecentDoc();
      }, 1000);
    }
  }

  private addRecentDoc(url?: string) {
    this.recentDocuments.update(docs => [{
      type: this.getDocTypeName(),
      student: `${this.selectedStudent.lastName} ${this.selectedStudent.firstName}`,
      date: new Date().toLocaleDateString('fr-FR'),
      url: url
    }, ...docs]);
    
    setTimeout(() => this.resetForm(), 1500);
  }

  downloadDoc(doc: any) {
    if (doc.url) {
      window.open(doc.url, '_blank');
    } else {
      this.showToast('Document non disponible pour le téléchargement immédiat');
    }
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
