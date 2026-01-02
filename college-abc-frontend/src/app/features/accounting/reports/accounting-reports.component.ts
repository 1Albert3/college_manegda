import { Component, signal, inject, OnInit } from '@angular/core';
import { CommonModule, DecimalPipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { FinanceService } from '../../../core/services/finance.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-accounting-reports',
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
          <select [(ngModel)]="selectedPeriod" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white transition cursor-pointer">
            <option value="month">Ce mois</option>
            <option value="trimester">Ce trimestre</option>
            <option value="year">Cette année</option>
          </select>
          <button (click)="generateReport()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 shadow-sm font-bold transition flex items-center gap-2">
            <i class="pi pi-file-pdf"></i> Générer PDF
          </button>
        </div>
      </div>

      <!-- KPIs -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl p-5 text-white shadow-lg shadow-emerald-200">
          <p class="text-white/80 text-sm font-medium">Chiffre d'affaires</p>
          <p class="text-3xl font-black mt-1">{{ revenue() | number }} <span class="text-sm font-normal opacity-80">FCFA</span></p>
          <p class="text-sm mt-2 flex items-center bg-white/10 w-fit px-2 py-0.5 rounded-lg"><i class="pi pi-info-circle mr-1 text-xs"></i>Global</p>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-blue-500 shadow-sm transition hover:shadow-md">
          <p class="text-gray-500 text-sm font-medium">Taux de recouvrement</p>
          <p class="text-3xl font-bold text-gray-800 mt-1">{{ collectionRate() }}%</p>
          <div class="mt-3 h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full bg-blue-500 rounded-full" [style.width.%]="collectionRate()"></div>
          </div>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-purple-500 shadow-sm transition hover:shadow-md">
          <p class="text-gray-500 text-sm font-medium">Nb. Transactions</p>
          <p class="text-3xl font-bold text-gray-800 mt-1">{{ transactionCount() }}</p>
          <i class="pi pi-wallet text-purple-200 text-2xl float-right -mt-8"></i>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-orange-500 shadow-sm transition hover:shadow-md">
          <p class="text-gray-500 text-sm font-medium">Ticket moyen</p>
          <p class="text-3xl font-bold text-gray-800 mt-1">{{ averageTicket() | number }} FCFA</p>
          <i class="pi pi-tag text-orange-200 text-2xl float-right -mt-8"></i>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Revenue by Fee Type -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
          <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2"><i class="pi pi-chart-pie text-emerald-600"></i> Recettes par Type</h3>
          <div *ngIf="revenueByType().length === 0" class="text-center text-gray-500 py-8">Aucune donnée disponible.</div>
          <div class="space-y-5">
            <div *ngFor="let item of revenueByType()">
              <div class="flex justify-between text-sm mb-2 font-medium text-gray-700">
                <span>{{ item.label || item.name }}</span>
                <span class="font-bold text-gray-900">{{ item.amount | number }} FCFA</span>
              </div>
              <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700" [style.width.%]="item.percentage" [style.background-color]="item.color"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Methods -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
          <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2"><i class="pi pi-credit-card text-blue-600"></i> Modes de Paiement</h3>
          <div *ngIf="paymentMethods().length === 0" class="text-center text-gray-500 py-8">Aucun paiement enregistré.</div>
          <div class="space-y-4">
            <div *ngFor="let method of paymentMethods()" class="flex items-center gap-4 p-3 hover:bg-gray-50 rounded-xl transition">
              <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl shrink-0" [style.background-color]="method.color + '20'">
                <i [class]="method.icon" [style.color]="method.color"></i>
              </div>
              <div class="flex-1">
                <div class="flex justify-between items-center mb-1">
                  <span class="font-bold text-gray-800">{{ method.label }}</span>
                  <span class="font-bold text-gray-900">{{ method.amount | number }} FCFA</span>
                </div>
                <div class="text-xs font-medium text-gray-500">{{ method.count }} transactions</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Monthly Comparison (Chart) -->
      <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2"><i class="pi pi-chart-bar text-purple-600"></i> Comparaison Mensuelle</h3>
        
        <div *ngIf="monthlyComparison().length === 0" class="text-center text-gray-500 py-8">Pas de données mensuelles.</div>

        <div class="flex items-end justify-between h-48 gap-4 px-4 overflow-x-auto">
          <div *ngFor="let m of monthlyComparison()" class="flex-1 flex flex-col items-center group min-w-[50px]">
            <div class="w-full bg-emerald-100 rounded-t-xl relative transition-all duration-500 group-hover:bg-emerald-200" 
                 [style.height.%]="maxMonthValue() > 0 ? (m.value / maxMonthValue()) * 100 : 0">
              <div class="absolute inset-0 bg-emerald-500 rounded-t-xl opacity-90 group-hover:opacity-100 transition-opacity"></div>
              <!-- Tooltip -->
              <div class="absolute -top-10 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10">
                  {{ m.value | number }} FCFA
              </div>
            </div>
            <span class="text-xs font-bold text-gray-500 mt-3">{{ m.month }}</span>
            <span class="text-xs font-bold text-emerald-600 mt-1" *ngIf="m.value > 0">{{ (m.value / 1000) | number:'1.0-0' }}k</span>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <button (click)="downloadReport('balance')" class="bg-white p-5 rounded-xl border border-gray-200 hover:border-emerald-500 hover:shadow-md text-left transition group">
          <div class="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
            <i class="pi pi-file text-2xl text-emerald-600"></i>
          </div>
          <h4 class="font-bold text-gray-800 group-hover:text-emerald-700 transition">Balance générale</h4>
          <p class="text-xs font-medium text-gray-500 mt-1">État complet des comptes</p>
        </button>
        <button (click)="downloadReport('unpaid')" class="bg-white p-5 rounded-xl border border-gray-200 hover:border-orange-500 hover:shadow-md text-left transition group">
          <div class="w-12 h-12 bg-orange-50 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
             <i class="pi pi-exclamation-triangle text-2xl text-orange-600"></i>
          </div>
          <h4 class="font-bold text-gray-800 group-hover:text-orange-700 transition">Rapport impayés</h4>
          <p class="text-xs font-medium text-gray-500 mt-1">Créances pa ancienneté</p>
        </button>
        <button (click)="downloadReport('class')" class="bg-white p-5 rounded-xl border border-gray-200 hover:border-blue-500 hover:shadow-md text-left transition group">
          <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
            <i class="pi pi-users text-2xl text-blue-600"></i>
          </div>
          <h4 class="font-bold text-gray-800 group-hover:text-blue-700 transition">Rapport par classe</h4>
          <p class="text-xs font-medium text-gray-500 mt-1">Situation financière par classe</p>
        </button>
      </div>
    </div>
  `
})
export class AccountingReportsComponent implements OnInit {
  private financeService = inject(FinanceService);
  private router = inject(Router);

  selectedPeriod = 'month';
  showSuccessToast = false;
  successMessage = '';

  revenue = signal(0);
  collectionRate = signal(0);
  transactionCount = signal(0);
  averageTicket = signal(0);

  revenueByType = signal<any[]>([]);
  paymentMethods = signal<any[]>([]);
  monthlyComparison = signal<any[]>([]);

  maxMonthValue = () => {
    const data = this.monthlyComparison();
    return data.length > 0 ? Math.max(...data.map(m => m.value)) : 100000;
  };

  getPageTitle(): string {
     const url = this.router.url;
     if (url.includes('budget')) return 'Suivi Budgétaire (CLEAN)';
     return 'Rapports Financiers (CLEAN)';
  }

  getPageSubtitle(): string {
       const url = this.router.url;
       if (url.includes('budget')) return 'Analyse des recettes et prévisions';
       return 'Analyses et statistiques financières (Données Réelles)';
  }

  ngOnInit() {
      this.financeService.getDashboardStats().subscribe({
          next: (res: any) => {
              if (res.success) {
                  const data = res.data;
                  const f = data.finance;
                  this.revenue.set(f.monthly_revenue);
                  this.collectionRate.set(f.collection_rate);
                  this.transactionCount.set(f.transaction_count || 0);
                  
                   if (f.transaction_count > 0) {
                       this.averageTicket.set( Math.round(f.monthly_revenue / f.transaction_count) );
                   } else {
                       this.averageTicket.set(0);
                   }
                   
                   this.revenueByType.set(data.fee_breakdown || []);
                   this.paymentMethods.set(data.payment_methods || []);
                   this.monthlyComparison.set(data.monthly_data || []);
              }
          },
          error: (err) => console.error("Error loading report data", err)
      });
  }

  generateReport() { 
      // Placeholder for PDF generation
      this.showToast('Fonctionnalité de génération PDF à venir.'); 
  }
  
  downloadReport(type: string) { 
       // Placeholder for download
      const labels: Record<string, string> = {
          'balance': 'Balance générale',
          'unpaid': 'Rapport impayés',
          'class': 'Rapport par classe'
      };
      this.showToast(`Téléchargement de "${labels[type]}" simulé (PDF à implémenter).`); 
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
