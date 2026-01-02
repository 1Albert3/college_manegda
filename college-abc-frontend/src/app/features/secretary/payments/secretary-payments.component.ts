import { Component, signal, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-secretary-payments',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6 relative">
      <!-- Toast Notification -->
      <div *ngIf="showSuccessToast" class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-xl z-[100] flex items-center gap-3 transition-opacity duration-300">
        <i class="pi pi-check-circle text-xl"></i>
        <span class="font-medium">{{ successMessage }}</span>
      </div>

      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Suivi des Paiements</h1>
          <p class="text-gray-500">Enregistrez et suivez les paiements des élèves</p>
        </div>
        <button (click)="showNewPayment = true"
                class="px-5 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-700 flex items-center gap-2 font-bold shadow-sm transition transform hover:scale-105">
          <i class="pi pi-plus"></i> Enregistrer un paiement
        </button>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg shadow-green-200 p-5 text-white transform hover:-translate-y-1 transition duration-300">
          <div class="flex justify-between items-start">
            <div>
                 <p class="text-white/80 text-sm font-medium mb-1">Encaissé aujourd'hui</p>
                 <p class="text-3xl font-black">{{ todayTotal() | number }} <span class="text-sm font-normal opacity-80">FCFA</span></p>
            </div>
            <i class="pi pi-wallet text-3xl opacity-20"></i>
          </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500 hover:shadow-md transition">
          <div class="flex justify-between items-start">
            <div>
                 <p class="text-gray-500 text-sm font-medium mb-1">Paiements aujourd'hui</p>
                 <p class="text-3xl font-bold text-gray-800">{{ todayCount() }}</p>
            </div>
            <div class="p-2 bg-blue-50 rounded-lg"><i class="pi pi-list text-blue-500"></i></div>
          </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500 hover:shadow-md transition">
          <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-500 text-sm font-medium mb-1">Ce mois</p>
                <p class="text-2xl font-bold text-gray-800">{{ monthTotal() | number }} <span class="text-sm font-normal text-gray-400">FCFA</span></p>
            </div>
            <div class="p-2 bg-purple-50 rounded-lg"><i class="pi pi-calendar text-purple-500"></i></div>
          </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500 hover:shadow-md transition">
          <div class="flex justify-between items-start">
             <div>
                <p class="text-gray-500 text-sm font-medium mb-1">À valider</p>
                <p class="text-3xl font-bold text-gray-800">{{ pendingValidation() }}</p>
             </div>
             <div class="p-2 bg-orange-50 rounded-lg"><i class="pi pi-exclamation-circle text-orange-500"></i></div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl shadow-sm p-4 flex flex-wrap gap-4 border border-gray-100">
        <div class="relative">
             <i class="pi pi-calendar absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
             <input type="date" [(ngModel)]="dateFilter"
               class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 transition">
        </div>
        <select [(ngModel)]="methodFilter"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 bg-white transition">
          <option value="">Tous modes</option>
          <option value="cash">Espèces</option>
          <option value="mobile">Mobile Money</option>
          <option value="transfer">Virement</option>
        </select>
        <div class="flex-1 relative">
            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" [(ngModel)]="searchQuery" placeholder="Rechercher par élève ou référence..."
               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 transition">
        </div>
      </div>

      <!-- Payments Table -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
              <tr class="text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-4">Référence</th>
                <th class="px-6 py-4">Élève</th>
                <th class="px-6 py-4">Facture</th>
                <th class="px-6 py-4">Montant</th>
                <th class="px-6 py-4">Mode</th>
                <th class="px-6 py-4">Date</th>
                <th class="px-6 py-4">Statut</th>
                <th class="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr *ngFor="let pay of filteredPayments()" class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 font-mono text-sm font-medium text-gray-600">{{ pay.reference }}</td>
                <td class="px-6 py-4">
                  <div class="font-bold text-gray-800">{{ pay.student }}</div>
                  <div class="text-xs text-gray-500 font-medium">{{ pay.class }}</div>
                </td>
                <td class="px-6 py-4 text-gray-600 font-mono text-xs">{{ pay.invoiceRef }}</td>
                <td class="px-6 py-4 font-black text-green-600">{{ pay.amount | number }} FCFA</td>
                <td class="px-6 py-4">
                  <span class="flex items-center gap-2 text-sm font-medium bg-gray-50 px-2 py-1 rounded w-fit text-gray-700">
                    <i [class]="getPaymentIcon(pay.method)"></i>
                    {{ getMethodLabel(pay.method) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-gray-600 text-sm">{{ pay.date }}</td>
                <td class="px-6 py-4">
                  <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                        [ngClass]="{
                          'bg-yellow-100 text-yellow-700 border border-yellow-200': pay.status === 'pending',
                          'bg-green-100 text-green-700 border border-green-200': pay.status === 'validated'
                        }">
                    {{ pay.status === 'validated' ? 'Validé' : 'En attente' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex justify-end gap-2">
                    <button *ngIf="pay.status === 'pending'" (click)="validatePayment(pay)"
                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition" title="Valider">
                      <i class="pi pi-check text-lg"></i>
                    </button>
                    <button class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Reçu">
                      <i class="pi pi-file-pdf text-lg"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- New Payment Modal -->
      <div *ngIf="showNewPayment" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showNewPayment = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden" (click)="$event.stopPropagation()">
          <div class="bg-green-600 px-6 py-4 flex justify-between items-center">
            <h2 class="text-xl font-bold text-white">Enregistrer un Paiement</h2>
            <button (click)="showNewPayment = false" class="text-white/80 hover:text-white transition"><i class="pi pi-times"></i></button>
          </div>
          <div class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Facture</label>
              <input type="text" [(ngModel)]="newPayment.invoiceRef" placeholder="FAC-2024-XXX"
                     class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 transition">
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Montant (FCFA)</label>
                <input type="number" [(ngModel)]="newPayment.amount"
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 transition font-bold text-gray-800">
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Mode de paiement</label>
                <select [(ngModel)]="newPayment.method"
                        class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 transition bg-white">
                  <option value="cash">Espèces</option>
                  <option value="mobile">Mobile Money</option>
                  <option value="transfer">Virement bancaire</option>
                </select>
              </div>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Référence transaction (optionnel)</label>
              <input type="text" [(ngModel)]="newPayment.transactionRef" placeholder="Numéro de transaction"
                     class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 transition">
            </div>
          </div>
          <div class="border-t border-gray-100 px-6 py-4 flex justify-end gap-3 bg-gray-50">
            <button (click)="showNewPayment = false"
                    class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-100 transition">Annuler</button>
            <button (click)="createPayment()"
                    class="px-5 py-2.5 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 transition shadow-lg shadow-green-200">Enregistrer</button>
          </div>
        </div>
      </div>
    </div>
  `
})
export class SecretaryPaymentsComponent implements OnInit {
  private http = inject(HttpClient);
  
  showNewPayment = false;
  searchQuery = '';
  dateFilter = '';
  methodFilter = '';
  
  showSuccessToast = false;
  successMessage = '';
  isLoading = signal(false);

  todayTotal = signal(0);
  todayCount = signal(0);
  monthTotal = signal(0);
  pendingValidation = signal(0);

  newPayment = { invoiceRef: '', amount: 0, method: 'cash', transactionRef: '' };

  payments = signal<any[]>([]);

  ngOnInit() {
    this.loadStats();
    this.loadPayments();
  }

  loadStats() {
    this.http.get<any>(`${environment.apiUrl}/finance/payments/stats`).subscribe({
      next: (res) => {
        this.todayTotal.set(res.total_collected || 0);
        const totalCount = res.by_mode ? Object.values(res.by_mode).reduce((a: number, b: any) => a + (b.count || 0), 0) : 0;
        this.todayCount.set(totalCount as number);
        this.monthTotal.set(res.total_collected || 0);
        this.pendingValidation.set(res.pending?.count || 0);
      }
    });
  }

  loadPayments() {
    this.isLoading.set(true);
    this.http.get<any>(`${environment.apiUrl}/finance/payments`).subscribe({
      next: (res) => {
        const data = res.data || res || [];
        this.payments.set(data.map((p: any) => ({
          reference: p.reference,
          student: p.student_id ? 'Élève #' + p.student_id.substring(0,8) : 'Anonyme', // The API doesn't join student names in index
          class: 'N/A',
          invoiceRef: p.invoice?.reference || 'N/A',
          amount: p.montant,
          method: p.mode_paiement === 'mobile_money' ? 'mobile' : (p.mode_paiement === 'especes' ? 'cash' : 'transfer'),
          date: new Date(p.date_paiement).toLocaleDateString('fr-FR'),
          status: p.statut === 'valide' ? 'validated' : 'pending'
        })));
        this.isLoading.set(false);
      },
      error: () => this.isLoading.set(false)
    });
  }

  getMethodLabel(method: string): string {
    const labels: Record<string, string> = { 'cash': 'Espèces', 'mobile': 'Mobile Money', 'transfer': 'Virement' };
    return labels[method] || method;
  }

  getPaymentIcon(method: string): string {
    const icons: Record<string, string> = { 'cash': 'pi pi-money-bill text-green-600', 'mobile': 'pi pi-mobile text-blue-600', 'transfer': 'pi pi-building text-purple-600' };
    return icons[method] || 'pi pi-wallet';
  }

  filteredPayments = () => {
    let result = this.payments();
    if (this.searchQuery) {
      const q = this.searchQuery.toLowerCase();
      result = result.filter(p => p.student.toLowerCase().includes(q) || p.reference.toLowerCase().includes(q));
    }
    if (this.methodFilter) result = result.filter(p => p.method === this.methodFilter);
    return result;
  };

  validatePayment(pay: any) {
    this.payments.update(list => list.map(p => p === pay ? { ...p, status: 'validated' } : p));
    this.pendingValidation.update(v => Math.max(0, v - 1));
    this.showToast('Paiement validé !');
  }

  createPayment() {
    this.showToast('Paiement enregistré avec succès !');
    
    this.payments.update(list => [{
        reference: `PAY-2024-${Math.floor(Math.random() * 1000)}`,
        student: 'Nouvel Élève', // Mock
        class: 'N/A',
        invoiceRef: this.newPayment.invoiceRef || 'N/A',
        amount: this.newPayment.amount,
        method: this.newPayment.method,
        date: new Date().toLocaleDateString('fr-FR'),
        status: 'pending' // New payments often pending
    }, ...list]);

    this.showNewPayment = false;
    this.newPayment = { invoiceRef: '', amount: 0, method: 'cash', transactionRef: '' };
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
