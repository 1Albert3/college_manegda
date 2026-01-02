import { Component, signal, computed, inject, OnInit } from '@angular/core';
import { CommonModule, DecimalPipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { FinanceService } from '../../../core/services/finance.service';
import { finalize } from 'rxjs/operators';
import { Router } from '@angular/router';

@Component({
  selector: 'app-accounting-unpaid',
  standalone: true,
  imports: [CommonModule, FormsModule, DecimalPipe],
  template: `
    <div class="space-y-6 relative">
      <!-- Toast Notification -->
      <div *ngIf="showSuccessToast" class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-xl z-[100] flex items-center gap-3 transition-opacity duration-300">
        <i class="pi pi-check-circle text-xl"></i>
        <span class="font-medium">{{ successMessage }}</span>
      </div>

      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">
             {{ getPageTitle() }}
          </h1>
          <p class="text-gray-500">{{ getPageSubtitle() }}</p>
        </div>
        <div class="flex gap-2">
            <button (click)="loadUnpaid()" 
                  class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            <i class="pi pi-refresh mr-2" [class.spin]="loading()"></i>Actualiser
          </button>
          <button (click)="sendBulkReminders()" class="px-5 py-2.5 bg-orange-500 text-white rounded-xl hover:bg-orange-600 font-bold shadow-sm transition flex items-center gap-2">
            <i class="pi pi-bell"></i> Rappels groupés
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-5 text-white shadow-lg shadow-red-200">
          <div class="flex justify-between items-start">
            <div>
                 <p class="text-white/80 text-sm font-medium mb-1">Total impayé</p>
                 <p class="text-3xl font-black">{{ stats()?.total_unpaid || 0 | number }} <span class="text-sm font-normal opacity-80">FCFA</span></p>
            </div>
            <i class="pi pi-money-bill text-3xl opacity-20"></i>
          </div>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-red-600 shadow-sm transition hover:shadow-md">
           <div class="flex justify-between items-start">
             <div>
               <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">> 90 jours (Critique)</p>
               <p class="text-3xl font-bold text-red-600">{{ criticalCount() }}</p>
             </div>
             <i class="pi pi-exclamation-circle text-red-200 text-2xl"></i>
           </div>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-orange-500 shadow-sm transition hover:shadow-md">
           <div class="flex justify-between items-start">
             <div>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Retard global</p>
                <p class="text-3xl font-bold text-orange-600">{{ stats()?.count_overdue || 0 }}</p>
             </div>
             <i class="pi pi-clock text-orange-200 text-2xl"></i>
           </div>
        </div>
      </div>

       <!-- Filters -->
      <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="relative">
             <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
             <input type="text" [(ngModel)]="searchQuery" placeholder="Rechercher..."
                 class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 transition">
          </div>
          <select [(ngModel)]="ageFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 bg-white transition">
            <option value="">Toutes les anciennetés</option>
            <option value="critical">> 90 jours</option>
            <option value="warning">30-90 jours</option>
            <option value="recent">< 30 jours</option>
          </select>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">REF</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Étudiant</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Montant dû</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Échéance</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Retard</th>
                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr *ngIf="loading()" class="animate-pulse">
                     <td colspan="6" class="px-6 py-4 text-center">Chargement...</td>
                </tr>
                 <tr *ngIf="!loading() && filteredUnpaid().length === 0">
                     <td colspan="6" class="px-6 py-4 text-center">Aucun impayé trouvé.</td>
                </tr>
                <tr *ngFor="let item of filteredUnpaid()" class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ item.number }}</td>
                <td class="px-6 py-4">
                    <div class="font-bold text-gray-800">{{ item.student_name }}</div>
                    <div class="text-xs text-gray-500 font-medium">{{ item.description }}</div>
                </td>
                <td class="px-6 py-4 font-black text-red-600">{{ item.solde | number }} FCFA</td>
                <td class="px-6 py-4 text-sm font-medium text-gray-600">{{ item.date_echeance | date:'dd/MM/yyyy' }}</td>
                <td class="px-6 py-4">
                    <span class="px-2.5 py-1 text-xs rounded-full font-bold uppercase tracking-wide" [ngClass]="getAgeClass(item.daysOverdue)">
                    {{ item.daysOverdue }} jours
                    </span>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end gap-2">
                        <button (click)="sendReminder(item)" class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition" title="Envoyer rappel">
                        <i class="pi pi-bell"></i>
                        </button>
                    </div>
                </td>
                </tr>
            </tbody>
            </table>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
  `]
})
export class AccountingUnpaidComponent implements OnInit {
  private financeService = inject(FinanceService);
  private router = inject(Router);

  searchQuery = '';
  ageFilter = '';
  
  showSuccessToast = false;
  successMessage = '';
  loading = signal(false);

  stats = signal<any>(null);
  // Computed counters
  criticalCount = computed(() => this.unpaidList().filter(u => u.daysOverdue > 90).length);

  unpaidList = signal<any[]>([]);

  filteredUnpaid = computed(() => {
    let result = this.unpaidList();
    if (this.searchQuery) {
      const q = this.searchQuery.toLowerCase();
      result = result.filter(u => 
          (u.student_name?.toLowerCase() || '').includes(q) ||
          (u.number?.toLowerCase() || '').includes(q)
      );
    }
    if (this.ageFilter === 'critical') result = result.filter(u => u.daysOverdue > 90);
    else if (this.ageFilter === 'warning') result = result.filter(u => u.daysOverdue >= 30 && u.daysOverdue <= 90);
    else if (this.ageFilter === 'recent') result = result.filter(u => u.daysOverdue < 30);
    return result;
  });

  ngOnInit() {
      this.checkRouteFilters();
      this.loadUnpaid();
  }

  checkRouteFilters() {
     const url = this.router.url;
     if (url.includes('reminders')) {
         this.ageFilter = 'critical'; // Show critical by default for reminders
     }
  }

  getPageTitle(): string {
       const url = this.router.url;
       if (url.includes('reminders')) return 'Relances Recouvrement';
       return 'Gestion des Impayés (CLEAN)';
  }

  getPageSubtitle(): string {
       const url = this.router.url;
       if (url.includes('reminders')) return 'Gérez les relances pour les retards critiques';
       return 'Suivi et recouvrement des créances';
  }

  loadUnpaid() {
      this.loading.set(true);
      this.financeService.getUnpaidInvoices().subscribe({
          next: (res: any) => {
              this.stats.set(res.stats);
              const invoices = (res.invoices?.data || []).map((inv: any) => ({
                  ...inv,
                  daysOverdue: this.calculateDaysOverdue(inv.date_echeance)
              }));
              this.unpaidList.set(invoices);
              this.loading.set(false);
          },
          error: (err) => {
              console.error(err);
              this.loading.set(false);
          }
      });
  }

  calculateDaysOverdue(dueDate: string): number {
    if (!dueDate) return 0;
    const due = new Date(dueDate);
    const now = new Date();
    const diffTime = now.getTime() - due.getTime();
    if (diffTime < 0) return 0;
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
  }

  getAgeClass(days: number) {
    if (days > 90) return 'bg-red-100 text-red-800 border-red-200';
    if (days >= 30) return 'bg-orange-100 text-orange-800 border-orange-200';
    return 'bg-yellow-100 text-yellow-800 border-yellow-200';
  }

  sendReminder(item: any) { 
      this.showToast('Rappel envoyé'); 
  }

  sendBulkReminders() { 
      this.showToast('Rappels groupés envoyés !'); 
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
