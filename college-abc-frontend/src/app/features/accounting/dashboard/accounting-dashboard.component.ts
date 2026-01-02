import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-accounting-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink],
  template: `
    <div class="space-y-6">
      
      <!-- Loading State -->
      <div *ngIf="isLoading()" class="text-center py-12">
        <i class="pi pi-spin pi-spinner text-4xl text-emerald-600"></i>
        <p class="mt-4 text-gray-500">Chargement des données financières...</p>
      </div>
      
      <!-- Error State -->
      <div *ngIf="error()" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
        <i class="pi pi-exclamation-triangle text-4xl text-red-500"></i>
        <p class="mt-4 text-red-700">{{ error() }}</p>
        <button (click)="loadData()" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Réessayer
        </button>
      </div>

      <ng-container *ngIf="!isLoading() && !error()">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-emerald-600 to-green-600 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
          <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
          <div class="absolute bottom-0 left-20 w-32 h-32 bg-white/10 rounded-full translate-y-1/2"></div>
          <div class="relative z-10">
            <h1 class="text-2xl md:text-3xl font-bold mb-2">Tableau de Bord Financier 💰</h1>
            <p class="text-emerald-100">
              {{ stats().pendingPayments }} paiements à valider • {{ stats().unpaidCount }} factures impayées
            </p>
          </div>
        </div>

        <!-- Financial Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          
          <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-sm p-5 text-white">
            <p class="text-white/80 text-sm">Recettes du mois</p>
            <p class="text-3xl font-bold mt-1">{{ stats().monthlyRevenue | number }}</p>
            <p class="text-sm mt-1 flex items-center gap-1">
              FCFA collectés
            </p>
          </div>

          <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
            <p class="text-gray-500 text-sm">Taux recouvrement</p>
            <p class="text-3xl font-bold text-gray-800 mt-1">{{ stats().collectionRate }}%</p>
            <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
              <div class="h-full bg-blue-500 rounded-full" [style.width.%]="stats().collectionRate"></div>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500 cursor-pointer hover:shadow-md" routerLink="/accounting/unpaid">
            <p class="text-gray-500 text-sm">Impayés</p>
            <p class="text-3xl font-bold text-gray-800 mt-1">{{ stats().totalUnpaid | number }}</p>
            <p class="text-sm text-orange-600 mt-1">{{ stats().unpaidCount }} factures</p>
          </div>

          <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500 cursor-pointer hover:shadow-md" routerLink="/accounting/payments">
            <p class="text-gray-500 text-sm">À valider</p>
            <p class="text-3xl font-bold text-gray-800 mt-1">{{ stats().pendingPayments }}</p>
            <p class="text-sm text-purple-600 mt-1">paiements en attente</p>
          </div>
        </div>

        <!-- Charts and Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          
          <!-- Revenue Chart (Left) -->
          <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-emerald-500 to-green-600 px-6 py-4">
              <h2 class="text-white font-bold flex items-center gap-2">
                <i class="pi pi-chart-line"></i>
                Évolution des Recettes
              </h2>
            </div>
            <div class="p-6">
              <!-- Simple bar chart representation -->
              <div class="flex items-end justify-between h-48 gap-2">
                <div *ngFor="let data of monthlyData()" class="flex-1 flex flex-col items-center">
                  <div class="w-full bg-emerald-100 rounded-t-lg relative"
                       [style.height.%]="maxMonthlyValue() > 0 ? (data.value / maxMonthlyValue()) * 100 : 0">
                    <div class="absolute inset-0 bg-emerald-500 rounded-t-lg opacity-80"></div>
                  </div>
                  <span class="text-xs text-gray-500 mt-2">{{ data.month }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Stats (Right) -->
          <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm p-5">
              <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="pi pi-chart-pie text-emerald-600"></i>
                Répartition des Frais
              </h3>
              <div class="space-y-3">
                <div *ngFor="let fee of feeBreakdown()" class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full" [style.background-color]="fee.color"></div>
                    <span class="text-sm text-gray-600">{{ fee.name }}</span>
                  </div>
                  <span class="text-sm font-semibold text-gray-800">{{ fee.percentage }}%</span>
                </div>
              </div>
            </div>

            <div class="bg-gradient-to-br from-orange-50 to-red-50 rounded-xl shadow-sm p-5 border border-orange-100">
              <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                <i class="pi pi-exclamation-triangle text-orange-600"></i>
                Alertes Impayés
              </h3>
              <div class="space-y-2">
                <div class="text-sm text-gray-600">
                  <span class="font-semibold text-red-600">{{ stats().criticalUnpaid }}</span> factures > 90 jours
                </div>
                <div class="text-sm text-gray-600">
                  <span class="font-semibold text-orange-600">{{ stats().warningUnpaid }}</span> factures > 30 jours
                </div>
              </div>
              <a routerLink="/accounting/unpaid" 
                 class="block mt-3 text-center py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-sm font-medium">
                Gérer les impayés
              </a>
            </div>
          </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
          <div class="bg-gray-800 px-6 py-4 flex items-center justify-between">
            <h2 class="text-white font-bold flex items-center gap-2">
              <i class="pi pi-list"></i>
              Dernières Transactions
            </h2>
            <a routerLink="/accounting/payments" class="text-white/80 hover:text-white text-sm">
              Voir tout →
            </a>
          </div>
          <div class="divide-y divide-gray-100">
            <div *ngIf="recentTransactions().length === 0" class="p-8 text-center text-gray-500">
              <i class="pi pi-inbox text-4xl text-gray-300"></i>
              <p class="mt-2">Aucune transaction récente</p>
            </div>
            <div *ngFor="let tx of recentTransactions()" 
                 class="p-4 flex items-center gap-4 hover:bg-gray-50">
              <div class="w-10 h-10 rounded-full flex items-center justify-center"
                   [ngClass]="tx.type === 'payment' ? 'bg-green-100 text-green-600' : 'bg-blue-100 text-blue-600'">
                <i [class]="tx.type === 'payment' ? 'pi pi-arrow-down' : 'pi pi-file'"></i>
              </div>
              <div class="flex-1">
                <div class="font-medium text-gray-800">{{ tx.description }}</div>
                <div class="text-sm text-gray-500">{{ tx.student }} • {{ tx.date }}</div>
              </div>
              <div class="text-right">
                <div class="font-semibold" [ngClass]="tx.type === 'payment' ? 'text-green-600' : 'text-gray-800'">
                  {{ tx.type === 'payment' ? '+' : '' }}{{ tx.amount | number }} FCFA
                </div>
                <div class="text-xs text-gray-400">{{ tx.method }}</div>
              </div>
            </div>
          </div>
        </div>
      </ng-container>
    </div>
  `
})
export class AccountingDashboardComponent implements OnInit {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl || 'http://localhost:8000/api';

  isLoading = signal(true);
  error = signal<string | null>(null);

  stats = signal({
    monthlyRevenue: 0,
    collectionRate: 0,
    totalUnpaid: 0,
    unpaidCount: 0,
    pendingPayments: 0,
    criticalUnpaid: 0,
    warningUnpaid: 0
  });

  monthlyData = signal<{month: string, value: number}[]>([]);
  feeBreakdown = signal<{name: string, percentage: number, color: string}[]>([]);
  recentTransactions = signal<any[]>([]);

  maxMonthlyValue = () => {
    const data = this.monthlyData();
    return data.length > 0 ? Math.max(...data.map(d => d.value)) : 1;
  };

  ngOnInit() {
    this.loadData();
  }

  loadData() {
    this.isLoading.set(true);
    this.error.set(null);

    this.http.get<any>(`${this.apiUrl}/dashboard/accounting`).subscribe({
      next: (response) => {
        if (response.success !== false) {
          const data = response.data || response;
          
          // Map finance data
          const finance = data.finance || {};
          this.stats.set({
            monthlyRevenue: finance.monthly_revenue ?? finance.total_collected ?? data.overview?.total_payments ?? 0,
            collectionRate: finance.collection_rate ?? 75,
            totalUnpaid: finance.total_pending ?? 0,
            unpaidCount: finance.unpaid_count ?? 0,
            pendingPayments: finance.pending_payments ?? 0,
            criticalUnpaid: finance.critical_unpaid ?? 0,
            warningUnpaid: finance.warning_unpaid ?? 0
          });

          // Default monthly data
          this.monthlyData.set(data.monthly_data ?? [
            { month: 'Sep', value: this.stats().monthlyRevenue * 0.68 },
            { month: 'Oct', value: this.stats().monthlyRevenue * 0.74 },
            { month: 'Nov', value: this.stats().monthlyRevenue * 0.84 },
            { month: 'Déc', value: this.stats().monthlyRevenue }
          ]);

          // Default fee breakdown
          this.feeBreakdown.set(data.fee_breakdown ?? [
            { name: 'Scolarité', percentage: 65, color: '#10B981' },
            { name: 'Inscription', percentage: 15, color: '#3B82F6' },
            { name: 'Transport', percentage: 12, color: '#F59E0B' },
            { name: 'Cantine', percentage: 8, color: '#EC4899' }
          ]);

          // Map transactions
          const transactions = data.recent_transactions || data.payments || [];
          this.recentTransactions.set(transactions.slice(0, 5).map((t: any) => ({
            type: t.type ?? 'payment',
            description: t.description ?? `Paiement ${t.payment_type ?? ''}`,
            student: t.student_name ?? t.student ?? 'N/A',
            date: t.date ?? new Date().toLocaleDateString('fr-FR'),
            amount: t.amount ?? 0,
            method: t.method ?? t.payment_method ?? ''
          })));
        }
        this.isLoading.set(false);
      },
      error: (err) => {
        console.error('Error loading accounting data:', err);
        // Load default data on error
        this.loadDefaultData();
        this.isLoading.set(false);
      }
    });
  }

  loadDefaultData() {
    this.stats.set({
      monthlyRevenue: 0,
      collectionRate: 0,
      totalUnpaid: 0,
      unpaidCount: 0,
      pendingPayments: 0,
      criticalUnpaid: 0,
      warningUnpaid: 0
    });

    this.monthlyData.set([
      { month: 'Sep', value: 0 },
      { month: 'Oct', value: 0 },
      { month: 'Nov', value: 0 },
      { month: 'Déc', value: 0 }
    ]);

    this.feeBreakdown.set([
      { name: 'Scolarité', percentage: 65, color: '#10B981' },
      { name: 'Inscription', percentage: 15, color: '#3B82F6' },
      { name: 'Transport', percentage: 12, color: '#F59E0B' },
      { name: 'Cantine', percentage: 8, color: '#EC4899' }
    ]);

    this.recentTransactions.set([]);
  }
}
