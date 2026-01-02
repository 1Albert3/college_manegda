import { Component, signal, computed, inject, OnInit } from '@angular/core';
import { CommonModule, DecimalPipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { FinanceService } from '../../../core/services/finance.service';
import { AcademicService } from '../../../core/services/academic.service';
import { finalize } from 'rxjs/operators';
import { Router } from '@angular/router';

@Component({
  selector: 'app-accounting-invoices',
  standalone: true,
  imports: [CommonModule, FormsModule, DecimalPipe],
  template: `
    <div class="space-y-6 relative">
      <!-- Toast Notification -->
      <div *ngIf="showSuccessToast" class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-xl z-[100] flex items-center gap-3 transition-opacity duration-300">
        <i class="pi pi-check-circle text-xl"></i>
        <span class="font-medium">{{ successMessage }}</span>
      </div>

       <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">
            {{ getPageTitle() }}
          </h1>
          <p class="text-gray-500">{{ getPageSubtitle() }}</p>
        </div>
        <div class="flex gap-2">
            <button (click)="loadInvoices()" 
                  class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            <i class="pi pi-refresh mr-2" [class.spin]="loading()"></i>Actualiser
          </button>
          <button (click)="openNewModal()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 shadow-sm font-bold transition flex items-center gap-2">
            <i class="pi pi-plus"></i> Nouvelle facture
          </button>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-5 text-white shadow-lg shadow-blue-200">
          <div class="flex justify-between items-start">
            <div>
                 <p class="text-white/80 text-sm font-medium mb-1">Total facturé</p>
                 <p class="text-3xl font-black">{{ stats()?.total_invoiced || 0 | number }} <span class="text-sm font-normal opacity-80">FCFA</span></p>
            </div>
            <i class="pi pi-chart-line text-3xl opacity-20"></i>
          </div>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-green-500 shadow-sm">
           <div class="flex justify-between items-start">
             <div>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Payées (Total)</p>
                <p class="text-3xl font-bold text-gray-800">{{ stats()?.total_collected || 0 | number }} F</p>
             </div>
             <i class="pi pi-check-circle text-green-200 text-2xl"></i>
           </div>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-orange-500 shadow-sm">
           <div class="flex justify-between items-start">
             <div>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Impayées</p>
                <p class="text-3xl font-bold text-gray-800">{{ stats()?.total_pending || 0 | number }} F</p>
             </div>
             <i class="pi pi-chart-pie text-orange-200 text-2xl"></i>
           </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="relative">
             <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
             <input type="text" [(ngModel)]="searchQuery" placeholder="Rechercher..."
                 class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 transition">
          </div>
          <select [(ngModel)]="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white transition">
            <option value="">Tous les statuts</option>
            <option value="emise">Emise</option>
            <option value="payee">Payée</option>
          </select>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-100">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Référence</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Étudiant</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Montant</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Payé</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Statut</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr *ngIf="loading()" class="animate-pulse">
                <td colspan="5" class="px-6 py-4 text-center text-gray-500">Chargement...</td>
            </tr>
            <tr *ngIf="!loading() && filteredInvoices().length === 0">
                <td colspan="5" class="px-6 py-4 text-center text-gray-500">Aucune facture trouvée.</td>
            </tr>
            <tr *ngFor="let inv of filteredInvoices()" class="hover:bg-gray-50 transition">
              <td class="px-6 py-4 font-mono text-sm font-medium text-gray-600">{{ inv.number }}</td>
              <td class="px-6 py-4">
                <div class="font-bold text-gray-800">{{ inv.student?.first_name || 'Inconnu' }} {{ inv.student?.last_name }}</div>
                <div class="text-xs text-gray-500">{{ inv.description }}</div>
              </td>
              <td class="px-6 py-4 font-black">{{ inv.montant_ttc | number }} FCFA</td>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-800">{{ inv.montant_paye | number }} FCFA</div>
              </td>
              <td class="px-6 py-4">
                <span class="px-2.5 py-1 text-xs rounded-full font-bold uppercase tracking-wide" [ngClass]="getStatusClass(inv.statut)">
                  {{ getStatusLabel(inv.statut) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- New Invoice Modal -->
      <div *ngIf="showNewModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showNewModal = false">
        <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl overflow-hidden" (click)="$event.stopPropagation()">
            <div class="bg-emerald-600 px-6 py-4 flex justify-between items-center">
                <h3 class="text-xl font-bold text-white">Créer une Facture</h3>
                <button (click)="showNewModal = false" class="text-white/80 hover:text-white transition"><i class="pi pi-times"></i></button>
            </div>
            <form (ngSubmit)="createInvoice()" class="p-6 space-y-4">
                <div *ngIf="errorMessage" class="bg-red-100 text-red-800 p-3 rounded">{{ errorMessage }}</div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Étudiant *</label>
                    <select [(ngModel)]="newInvoice.studentId" name="student" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 bg-white transition">
                        <option value="">Sélectionner un étudiant</option>
                        <option *ngFor="let s of students()" [value]="s.id">
                            {{ s.firstName }} {{ s.lastName }} ({{ s.cycle }})
                        </option>
                    </select>
                </div>
                 <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Type *</label>
                    <select [(ngModel)]="newInvoice.type" name="type" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 bg-white transition">
                        <option value="scolarite">Scolarité</option>
                        <option value="cantine">Cantine</option>
                        <option value="transport">Transport</option>
                        <option value="fournitures">Fournitures</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Montant *</label>
                    <input type="number" [(ngModel)]="newInvoice.amount" name="amount" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 transition font-bold text-gray-800">
                </div>
                <div>
                   <label class="block text-sm font-bold text-gray-700 mb-1">Description</label>
                   <input type="text" [(ngModel)]="newInvoice.description" name="description" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 transition">
                </div>
                 <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Date d'émission</label>
                    <input type="date" [(ngModel)]="newInvoice.date_emission" name="date_emission" class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 transition">
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 bg-gray-50 -mx-6 -mb-6 px-6 py-4 mt-2">
                    <button type="button" (click)="showNewModal = false" class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-100 transition">Annuler</button>
                    <button type="submit" [disabled]="submitting" 
                        class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 transition shadow-lg shadow-emerald-200 flex items-center gap-2">
                        <i *ngIf="submitting" class="pi pi-spin pi-spinner"></i> Créer
                    </button>
                </div>
            </form>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
  `]
})
export class AccountingInvoicesComponent implements OnInit {
  private financeService = inject(FinanceService);
  private academicService = inject(AcademicService);
  private router = inject(Router);

  searchQuery = '';
  statusFilter = '';
  
  showNewModal = false;
  showSuccessToast = false;
  successMessage = '';
  errorMessage = '';

  loading = signal(false);
  submitting = false;

  stats = signal<any>(null);
  invoices = signal<any[]>([]);
  students = signal<any[]>([]);

  newInvoice = {
      studentId: '',
      type: 'scolarite',
      amount: 0,
      description: 'Frais de scolarité',
      date_emission: new Date().toISOString().split('T')[0]
  };

  currentSchoolYearId: string | null = null;

  filteredInvoices = computed(() => {
    let result = this.invoices();
    if (this.searchQuery) {
      const q = this.searchQuery.toLowerCase();
      result = result.filter(i => 
          (i.number?.toLowerCase() || '').includes(q) ||
          (i.description?.toLowerCase() || '').includes(q) ||
          (i.student?.first_name?.toLowerCase() || '').includes(q) ||
          (i.student?.last_name?.toLowerCase() || '').includes(q)
      );
    }
    if (this.statusFilter) result = result.filter(i => i.statut === this.statusFilter);
    return result;
  });

  ngOnInit() {
      this.loadInvoices();
      this.loadStudents();
      this.loadCurrentYear();
  }

  getPageTitle(): string {
       const url = this.router.url;
       if (url.includes('scholarships')) return 'Gestion des Bourses (CLEAN)';
       return 'Gestion des Factures (CLEAN)';
  }

  getPageSubtitle(): string {
       const url = this.router.url;
       if (url.includes('scholarships')) return 'Attribution et suivi des aides financières';
       return 'Création et suivi de la facturation';
  }

  loadCurrentYear() {
      // Assuming academicService has getCurrentYear which returns Observable or Signal
      this.academicService.getCurrentYear().subscribe({
          next: (year: any) => {
              if (year) this.currentSchoolYearId = year.id;
          }
      });
  }

  loadInvoices() {
      this.loading.set(true);
      
      this.financeService.getInvoiceStats().subscribe(stats => this.stats.set(stats));

      this.financeService.getInvoices({ per_page: 50 }).pipe(
          finalize(() => this.loading.set(false))
      ).subscribe({
          next: (res: any) => {
              this.invoices.set(res.data || []);
          },
          error: (err) => console.error(err)
      });
  }

  loadStudents() {
      this.financeService.getStudents().subscribe({
          next: (res: any) => this.students.set(res.data || []),
          error: (err) => console.error('Students load error', err)
      });
  }

  openNewModal() {
      this.showNewModal = true;
      this.newInvoice = {
          studentId: '',
          type: 'scolarite',
          amount: 0,
          description: 'Frais de scolarité',
          date_emission: new Date().toISOString().split('T')[0]
      };
  }

  createInvoice() {
      if (!this.newInvoice.studentId || !this.newInvoice.amount) {
          this.errorMessage = "Champs obligatoires manquants.";
          return;
      }
      if (!this.currentSchoolYearId) {
          this.errorMessage = "Année scolaire non chargée.";
          return;
      }

      this.submitting = true;
      const student = this.students().find(s => s.id === this.newInvoice.studentId);

      const payload = {
          student_id: this.newInvoice.studentId,
          student_database: student?.cycle || 'school_lycee', // Fallback
          school_year_id: this.currentSchoolYearId,
          type: this.newInvoice.type,
          montant_ttc: this.newInvoice.amount,
          date_emission: this.newInvoice.date_emission,
          description: this.newInvoice.description
      };

      this.financeService.createInvoice(payload).pipe(
          finalize(() => this.submitting = false)
      ).subscribe({
          next: (res) => {
              this.showToast('Facture créée avec succès.');
              this.showNewModal = false;
              this.loadInvoices();
          },
          error: (err) => {
              console.error(err);
              this.errorMessage = err.error?.message || "Erreur de création.";
          }
      });
  }

  getStatusClass(s: string) {
    return { 
        'bg-green-100 text-green-800 border-green-200': s === 'payee', 
        'bg-orange-100 text-orange-800 border-orange-200': s === 'partiellement_payee', 
        'bg-blue-100 text-blue-800 border-blue-200': s === 'emise',
        'bg-gray-100 text-gray-800 border-gray-200': s === 'brouillon',
        'bg-red-100 text-red-800 border-red-200': s === 'annulee' 
    };
  }
  
  getStatusLabel(s: string) { 
      const labels: any = { 
          payee: 'Payée', 
          partiellement_payee: 'Partielle', 
          emise: 'Émise', 
          brouillon: 'Brouillon', 
          annulee: 'Annulée' 
      };
      return labels[s] || s; 
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
