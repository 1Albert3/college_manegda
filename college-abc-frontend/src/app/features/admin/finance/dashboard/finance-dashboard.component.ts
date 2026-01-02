import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FinanceService } from '../../../../core/services/finance.service';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-finance-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule],
  template: `
    <div class="p-6 space-y-8">
      <!-- Welcome & Overall Stats -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow group">
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 transition-colors group-hover:bg-indigo-600 group-hover:text-white">
              <i class="pi pi-file-o text-xl"></i>
            </div>
            <div>
              <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Total Facturé</p>
              <h3 class="text-2xl font-black text-gray-900">{{ stats.total_invoiced | number }} FCFA</h3>
            </div>
          </div>
          <div class="h-1.5 w-full bg-gray-50 rounded-full overflow-hidden">
             <div class="h-full bg-indigo-500 rounded-full" [style.width]="'100%'"></div>
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow group">
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
              <i class="pi pi-check-circle text-xl"></i>
            </div>
            <div>
              <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Total Collecté</p>
              <h3 class="text-2xl font-black text-emerald-600">{{ stats.total_paid | number }} FCFA</h3>
            </div>
          </div>
          <div class="h-1.5 w-full bg-gray-50 rounded-full overflow-hidden">
             <div class="h-full bg-emerald-500 rounded-full" [style.width]="(stats.collection_rate || 0) + '%'"></div>
          </div>
          <p class="text-[10px] text-gray-400 font-bold mt-2 uppercase">Taux de recouvrement: {{ stats.collection_rate || 0 }}%</p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow group">
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-2xl bg-orange-50 flex items-center justify-center text-orange-600 transition-colors group-hover:bg-orange-600 group-hover:text-white">
              <i class="pi pi-clock text-xl"></i>
            </div>
            <div>
              <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">En Attente</p>
              <h3 class="text-2xl font-black text-orange-600">{{ stats.total_pending | number }} FCFA</h3>
            </div>
          </div>
          <div class="h-1.5 w-full bg-gray-50 rounded-full overflow-hidden">
             <div class="h-full bg-orange-400 rounded-full" [style.width]="(100 - (stats.collection_rate || 0)) + '%'"></div>
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow group">
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 flex items-center justify-center text-rose-600 transition-colors group-hover:bg-rose-600 group-hover:text-white">
              <i class="pi pi-exclamation-triangle text-xl"></i>
            </div>
            <div>
              <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">En Retard</p>
              <h3 class="text-2xl font-black text-rose-600">{{ stats.total_overdue | number }} FCFA</h3>
            </div>
          </div>
          <div class="h-1.5 w-full bg-gray-50 rounded-full overflow-hidden">
             <div class="h-full bg-rose-500 rounded-full" [style.width]="'30%'"></div>
          </div>
        </div>
      </div>

      <!-- Main Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Invoices Table -->
        <div class="lg:col-span-2 space-y-6">
           <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
              <div class="p-6 border-b border-gray-50 flex items-center justify-between">
                 <h2 class="text-xs font-black uppercase text-gray-400 tracking-[0.2em]">Facturations Récentes</h2>
                 <a routerLink="../invoices" class="text-indigo-600 text-[10px] font-black uppercase tracking-widest hover:underline">Voir tout</a>
              </div>
              <div class="overflow-x-auto">
                 <table class="w-full text-left">
                    <thead>
                       <tr class="bg-gray-50/50 text-[10px] uppercase text-gray-400 font-black">
                          <th class="px-6 py-4">Facture</th>
                          <th class="px-6 py-4">Élève</th>
                          <th class="px-6 py-4">Montant</th>
                          <th class="px-6 py-4">Statut</th>
                       </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                       <tr *ngFor="let invoice of recentInvoices" class="hover:bg-gray-50 transition-colors text-sm">
                          <td class="px-6 py-4 font-mono font-bold text-gray-500">{{ invoice.number }}</td>
                          <td class="px-6 py-4">
                             <div class="font-bold text-gray-900">{{ invoice.student_name }}</div>
                             <div class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">{{ invoice.student_matricule }}</div>
                          </td>
                          <td class="px-6 py-4 font-black">{{ invoice.montant_ttc | number }}</td>
                          <td class="px-6 py-4">
                             <span [class]="getStatusClass(invoice.statut)" class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter border">
                                {{ invoice.statut }}
                             </span>
                          </td>
                       </tr>
                       <tr *ngIf="recentInvoices.length === 0">
                          <td colspan="4" class="px-6 py-10 text-center text-gray-400 italic">Aucune donnée récente</td>
                       </tr>
                    </tbody>
                 </table>
              </div>
           </div>
        </div>

        <!-- Sidebar / Distribution -->
        <div class="space-y-6">
           <!-- Recouvrement par cycle -->
           <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
              <h2 class="text-xs font-black uppercase text-gray-400 tracking-[0.2em] mb-6">Distribution par Cycle</h2>
              <div class="space-y-6">
                 <div *ngFor="let cycle of stats.by_cycle" class="space-y-2">
                    <div class="flex justify-between items-center text-xs">
                       <span class="font-black text-gray-600 uppercase tracking-tight">{{ cycle.name }}</span>
                       <span class="font-bold text-indigo-600">{{ cycle.collected | number }} / {{ cycle.total | number }}</span>
                    </div>
                    <div class="h-2 w-full bg-gray-50 rounded-full overflow-hidden">
                       <div class="h-full bg-indigo-500 rounded-full" [style.width]="cycle.percentage + '%'"></div>
                    </div>
                 </div>
              </div>
           </div>

           <!-- Quick Actions -->
           <div class="bg-indigo-900 rounded-2xl p-6 text-white overflow-hidden relative">
              <div class="relative z-10">
                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-indigo-300 mb-4">Actions Rapides</h3>
                <div class="grid grid-cols-2 gap-3">
                   <button routerLink="../invoices" class="bg-white/10 hover:bg-white/20 p-4 rounded-xl transition-all text-center group border border-white/10 shadow-inner">
                      <i class="pi pi-plus-circle mb-2 scale-125 block group-hover:scale-150 transition-transform"></i>
                      <span class="text-[9px] font-black uppercase tracking-widest block">Nouvelle Facture</span>
                   </button>
                   <button routerLink="../payments" class="bg-white/10 hover:bg-white/20 p-4 rounded-xl transition-all text-center group border border-white/10 shadow-inner">
                      <i class="pi pi-money-bill mb-2 scale-125 block group-hover:scale-150 transition-transform"></i>
                      <span class="text-[9px] font-black uppercase tracking-widest block">Rapport Paiements</span>
                   </button>
                </div>
              </div>
              <i class="pi pi-wallet absolute -bottom-4 -right-4 text-7xl text-indigo-800 opacity-30 -rotate-12"></i>
           </div>
        </div>
      </div>
    </div>
  `
})
export class FinanceDashboardComponent implements OnInit {
  stats: any = {
    total_invoiced: 0,
    total_paid: 0,
    total_pending: 0,
    total_overdue: 0,
    collection_rate: 0,
    by_cycle: [
        { name: 'MP', collected: 0, total: 1, percentage: 0 },
        { name: 'Collège', collected: 0, total: 1, percentage: 0 },
        { name: 'Lycée', collected: 0, total: 1, percentage: 0 }
    ]
  };
  recentInvoices: any[] = [];

  constructor(private financeService: FinanceService) {}

  ngOnInit() {
    this.loadStats();
    this.loadRecentData();
  }

  loadStats() {
    this.financeService.getInvoiceStats().subscribe({
      next: (res: any) => {
        this.stats = {
            ...this.stats,
            ...res,
            collection_rate: res.total_total > 0 ? Math.round((res.total_paid / res.total_invoiced) * 100) : 0
        };
      },
      error: (err) => console.error('Error stats finance', err)
    });
  }

  loadRecentData() {
    // Top 5 invoices
    this.financeService.getInvoices({ per_page: 5 }).subscribe(res => {
        this.recentInvoices = res.data || [];
    });
  }

  getStatusClass(statut: string) {
    switch(statut) {
        case 'payee': return 'bg-emerald-50 text-emerald-700 border-emerald-100';
        case 'partiellement_payee': return 'bg-orange-50 text-orange-700 border-orange-100';
        case 'emise': return 'bg-blue-50 text-blue-700 border-blue-100';
        default: return 'bg-gray-50 text-gray-700 border-gray-100';
    }
  }
}
