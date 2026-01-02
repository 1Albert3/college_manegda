import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FinanceService } from '../../../../core/services/finance.service';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-payments-history',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="p-6">
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-50 flex items-center justify-between bg-gray-50/30">
           <div>
             <h2 class="text-xs font-black uppercase text-gray-400 tracking-[0.2em]">Historique des Paiements</h2>
             <p class="text-[10px] text-gray-400 font-bold uppercase mt-1">Flux de trésorerie entrant par date</p>
           </div>
           
           <div class="flex items-center gap-3">
              <select [(ngModel)]="filterMode" (change)="loadPayments()" 
                      class="text-[10px] font-black uppercase tracking-widest border-gray-200 rounded-lg px-3 py-2 bg-white shadow-sm focus:ring-indigo-500">
                 <option value="">Tous les modes</option>
                 <option value="especes">Espèces</option>
                 <option value="mobile_money">Mobile Money</option>
                 <option value="cheque">Chèque</option>
                 <option value="virement">Virement</option>
              </select>
           </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-gray-50/50 text-[10px] uppercase text-gray-400 font-black tracking-widest">
                <th class="px-8 py-5">Référence</th>
                <th class="px-8 py-5">Élève</th>
                <th class="px-8 py-5">Mode</th>
                <th class="px-8 py-5">Montant</th>
                <th class="px-8 py-5">Date</th>
                <th class="px-8 py-5 text-center">Statut</th>
                <th class="px-8 py-5 text-right">Reçu</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr *ngFor="let p of payments" class="hover:bg-indigo-50/20 transition-all duration-200 group">
                <td class="px-8 py-5">
                   <span class="text-xs font-mono font-black text-indigo-600 bg-indigo-50 px-2 py-1 rounded border border-indigo-100 italic">
                     {{ p.reference }}
                   </span>
                </td>
                <td class="px-8 py-5">
                   <div class="font-black text-gray-900 group-hover:text-indigo-700 transition-colors uppercase italic text-sm">
                     {{ p.student?.first_name }} {{ p.student?.last_name }}
                   </div>
                   <div class="text-[10px] text-gray-400 font-bold tracking-tight mt-0.5 uppercase">{{ p.student?.matricule }}</div>
                </td>
                <td class="px-8 py-5">
                   <div class="flex items-center gap-2">
                     <i [class]="getModeIcon(p.mode_paiement)" class="text-gray-400 group-hover:text-indigo-400 transition-colors"></i>
                     <span class="text-[10px] font-black uppercase text-gray-600 tracking-tighter">{{ p.mode_paiement }}</span>
                   </div>
                </td>
                <td class="px-8 py-5">
                   <span class="text-sm font-black text-emerald-600">{{ p.montant | number }} FCFA</span>
                </td>
                <td class="px-8 py-5 text-xs font-bold text-gray-500">
                   {{ p.date_paiement | date:'dd MMM yyyy' }}
                </td>
                <td class="px-8 py-5 text-center">
                   <div [class]="getStatusClass(p.statut)" class="inline-flex items-center px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-tighter border">
                      {{ p.statut }}
                   </div>
                </td>
                <td class="px-8 py-5 text-right">
                   <button *ngIf="p.statut === 'valide'" (click)="downloadReceipt(p)" 
                           class="text-indigo-600 hover:bg-white p-2 rounded-xl transition-all shadow-sm active:scale-95 border border-transparent hover:border-gray-100 group/btn">
                     <i class="pi pi-file-pdf group-hover/btn:scale-110 transition-transform"></i>
                   </button>
                </td>
              </tr>
              <tr *ngIf="payments.length === 0 && !loading">
                 <td colspan="7" class="px-8 py-20 text-center text-gray-400 italic font-medium">Aucun paiement enregistré pour ces critères.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div *ngIf="loading" class="p-20 flex justify-center">
           <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>
      </div>
    </div>
  `
})
export class PaymentsHistoryComponent implements OnInit {
  payments: any[] = [];
  loading = false;
  filterMode = '';

  constructor(private financeService: FinanceService) {}

  ngOnInit() {
    this.loadPayments();
  }

  loadPayments() {
    this.loading = true;
    const params: any = {};
    if (this.filterMode) params.mode_paiement = this.filterMode;

    this.financeService.getPayments(params).subscribe({
      next: (res) => {
        this.payments = res.data || res;
        this.loading = false;
      },
      error: () => this.loading = false
    });
  }

  getModeIcon(mode: string) {
    switch(mode) {
        case 'especes': return 'pi pi-money-bill';
        case 'mobile_money': return 'pi pi-phone';
        case 'cheque': return 'pi pi-id-card';
        case 'virement': return 'pi pi-building';
        default: return 'pi pi-credit-card';
    }
  }

  getStatusClass(statut: string) {
    switch(statut) {
        case 'valide': return 'bg-emerald-50 text-emerald-700 border-emerald-100';
        case 'en_attente': return 'bg-orange-50 text-orange-700 border-orange-100';
        case 'rejete': return 'bg-rose-50 text-rose-700 border-rose-100';
        default: return 'bg-gray-50 text-gray-700 border-gray-100';
    }
  }

  downloadReceipt(payment: any) {
    this.financeService.downloadReceipt(payment.id).subscribe(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `recu_${payment.reference}.pdf`;
        a.click();
        window.URL.revokeObjectURL(url);
    });
  }
}
