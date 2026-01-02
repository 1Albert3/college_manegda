import { Component, inject, signal, OnInit, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
import { FinanceService, Invoice } from '../../../core/services/finance.service';

@Component({
  selector: 'app-parent-invoices',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6 relative">
      <!-- Toast Notification -->
      <div *ngIf="showSuccessToast" class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-xl z-[100] flex items-center gap-3 transition-opacity duration-300">
         <i *ngIf="toastType === 'success'" class="pi pi-check-circle text-xl"></i>
         <i *ngIf="toastType === 'error'" class="pi pi-exclamation-circle text-xl"></i>
         <span class="font-medium">{{ toastMessage }}</span>
      </div>

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Factures & Paiements</h1>
          <p class="text-gray-500">Gérez les frais de scolarité de vos enfants</p>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500">
          <p class="text-gray-500 text-sm">Total Payé</p>
          <p class="text-2xl font-bold text-gray-800 mt-1">{{ totalPaid() | number }} FCFA</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500">
          <p class="text-gray-500 text-sm">En Attente</p>
          <p class="text-2xl font-bold text-gray-800 mt-1">{{ totalPending() | number }} FCFA</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-red-500">
          <p class="text-gray-500 text-sm">En Retard</p>
          <p class="text-2xl font-bold text-gray-800 mt-1">{{ totalOverdue() | number }} FCFA</p>
        </div>
        <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl shadow-sm p-5 text-white">
          <p class="text-white/80 text-sm">Prochain Paiement</p>
          <p class="text-xl font-bold mt-1">{{ nextDueAmount() | number }} FCFA</p>
          <p class="text-sm mt-1 opacity-80">Échéance: {{ nextDueDate() }}</p>
        </div>
      </div>

      <!-- Invoices Table -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4 flex items-center justify-between">
          <h2 class="text-white font-bold flex items-center gap-2">
            <i class="pi pi-file"></i>
            Mes Factures
          </h2>
          <select [(ngModel)]="statusFilter"
                  class="px-3 py-1.5 bg-white/20 text-white rounded-lg text-sm border-0 focus:ring-0 cursor-pointer">
            <option value="" class="text-gray-800">Toutes</option>
            <option value="pending" class="text-gray-800">En attente</option>
            <option value="paid" class="text-gray-800">Payées</option>
            <option value="overdue" class="text-gray-800">En retard</option>
          </select>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr class="text-left text-sm font-bold text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-4">Référence</th>
                <th class="px-6 py-4">Description</th>
                <th class="px-6 py-4">Enfant</th>
                <th class="px-6 py-4">Montant</th>
                <th class="px-6 py-4">Échéance</th>
                <th class="px-6 py-4">Statut</th>
                <th class="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr *ngFor="let invoice of filteredInvoices()" class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 font-mono text-sm font-medium text-gray-600">{{ invoice.reference }}</td>
                <td class="px-6 py-4 text-gray-800 font-medium">{{ invoice.description }}</td>
                <td class="px-6 py-4 text-gray-600">{{ invoice.student?.first_name }} {{ invoice.student?.last_name }}</td>
                <td class="px-6 py-4 font-black text-gray-800">{{ invoice.amount | number }} FCFA</td>
                <td class="px-6 py-4 text-gray-600 font-medium">{{ invoice.due_date }}</td>
                <td class="px-6 py-4">
                  <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                        [ngClass]="{
                          'bg-yellow-100 text-yellow-800 border-yellow-200': invoice.status === 'pending',
                          'bg-green-100 text-green-800 border-green-200': invoice.status === 'paid',
                          'bg-red-100 text-red-800 border-red-200': invoice.status === 'overdue'
                        }">
                    {{ getStatusLabel(invoice.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex justify-end gap-2">
                    <button (click)="downloadInvoice(invoice.id)"
                            class="p-2 text-gray-500 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition"
                            title="Télécharger">
                      <i class="pi pi-download"></i>
                    </button>
                    <button *ngIf="invoice.status !== 'paid'"
                            (click)="payInvoice(invoice)"
                            class="px-4 py-1.5 bg-purple-600 text-white rounded-lg text-sm font-bold hover:bg-purple-700 transition shadow-sm">
                      Payer
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Payment Modal -->
      <div *ngIf="showPaymentModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showPaymentModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" (click)="$event.stopPropagation()">
          <div class="bg-gradient-to-r from-purple-600 to-purple-800 px-6 py-4 flex justify-between items-center">
            <h2 class="text-xl font-bold text-white">Paiement en ligne</h2>
            <button (click)="showPaymentModal = false" class="text-white/80 hover:text-white transition"><i class="pi pi-times"></i></button>
          </div>
          <div class="p-6 space-y-4">
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
              <p class="text-sm text-gray-500 font-medium mb-1">Montant à payer</p>
              <p class="text-3xl font-black text-purple-600">{{ selectedInvoice?.amount | number }} FCFA</p>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Mode de paiement</label>
              <div class="grid grid-cols-2 gap-3">
                <button class="border-2 border-purple-600 bg-purple-50 rounded-lg p-3 flex items-center gap-2 transition ring-2 ring-purple-100">
                  <i class="pi pi-mobile text-purple-600 text-xl"></i>
                  <span class="font-bold text-purple-800">Mobile Money</span>
                </button>
                <button class="border-2 border-gray-200 rounded-lg p-3 flex items-center gap-2 opacity-50 cursor-not-allowed">
                  <i class="pi pi-credit-card text-gray-400 text-xl"></i>
                  <span class="text-gray-500 font-medium">Carte bancaire</span>
                </button>
              </div>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Numéro de téléphone</label>
              <input type="tel" placeholder="Ex: 70 12 34 56"
                     class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 transition font-medium">
            </div>
          </div>
          <div class="border-t border-gray-100 px-6 py-4 flex justify-end gap-3 bg-gray-50">
            <button (click)="showPaymentModal = false"
                    class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-100 transition">
              Annuler
            </button>
            <button (click)="confirmPayment()" class="px-5 py-2.5 bg-purple-600 text-white rounded-xl font-bold hover:bg-purple-700 transition shadow-lg shadow-purple-200 flex items-center gap-2">
              <i class="pi pi-check"></i> Confirmer
            </button>
          </div>
        </div>
      </div>
    </div>
  `
})
export class ParentInvoicesComponent implements OnInit {
  private authService = inject(AuthService);
  private financeService = inject(FinanceService);

  invoices = signal<Invoice[]>([]);
  statusFilter = '';
  showPaymentModal = false;
  selectedInvoice: Invoice | null = null;
  
  showSuccessToast = false;
  toastMessage = '';
  toastType: 'success' | 'error' = 'success';

  totalPaid = signal(450000);
  totalPending = signal(125000);
  totalOverdue = signal(50000);
  nextDueAmount = signal(125000);
  nextDueDate = signal('31/01/2025');

  filteredInvoices = () => {
    if (!this.statusFilter) return this.invoices();
    return this.invoices().filter(i => i.status === this.statusFilter);
  };

  ngOnInit() {
    this.loadInvoices();
  }

  loadInvoices() {
    // Mock data - replace with API
    this.invoices.set([
      { id: 1, student_id: 1, reference: 'FAC-2024-001', amount: 150000, due_date: '2024-09-30', status: 'paid', description: 'Frais de scolarité - 1er trimestre', created_at: '2024-09-01', student: { first_name: 'Amadou', last_name: 'Diallo', matricule: '25-DIA-0001' } },
      { id: 2, student_id: 1, reference: 'FAC-2024-002', amount: 150000, due_date: '2024-12-31', status: 'paid', description: 'Frais de scolarité - 2ème trimestre', created_at: '2024-11-01', student: { first_name: 'Amadou', last_name: 'Diallo', matricule: '25-DIA-0001' } },
      { id: 3, student_id: 1, reference: 'FAC-2025-001', amount: 150000, due_date: '2025-03-31', status: 'pending', description: 'Frais de scolarité - 3ème trimestre', created_at: '2025-01-01', student: { first_name: 'Amadou', last_name: 'Diallo', matricule: '25-DIA-0001' } },
      { id: 4, student_id: 1, reference: 'FAC-2024-010', amount: 25000, due_date: '2024-11-30', status: 'overdue', description: 'Frais de transport - Décembre', created_at: '2024-11-01', student: { first_name: 'Amadou', last_name: 'Diallo', matricule: '25-DIA-0001' } },
    ]);
  }

  getStatusLabel(status: string): string {
    const labels: Record<string, string> = {
      'pending': 'En attente',
      'paid': 'Payée',
      'overdue': 'En retard',
      'cancelled': 'Annulée'
    };
    return labels[status] || status;
  }

  downloadInvoice(id: number) {
    this.financeService.downloadInvoicePdf(id).subscribe({
      next: (blob) => {
        const url = window.URL.createObjectURL(blob);
        window.open(url);
      },
      error: () => this.showToast('Erreur lors du téléchargement', 'error')
    });
  }

  payInvoice(invoice: Invoice) {
    this.selectedInvoice = invoice;
    this.showPaymentModal = true;
  }
  
  confirmPayment() {
      // Simulate payment success provided by mock
      this.showToast('Paiement effectué avec succès !');
      if (this.selectedInvoice) {
        // Update local mock state to 'paid'
        this.invoices.update(list => list.map(i => i.id === this.selectedInvoice!.id ? {...i, status: 'paid'} : i));
      }
      this.showPaymentModal = false;
  }

  private showToast(message: string, type: 'success' | 'error' = 'success') {
    this.toastMessage = message;
    this.toastType = type;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
