import { Component, signal, computed, OnInit, inject } from '@angular/core';
import { CommonModule, DecimalPipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { FinanceService } from '../../../core/services/finance.service';
import { finalize } from 'rxjs/operators';
import { Router } from '@angular/router';

@Component({
  selector: 'app-accounting-payments',
  standalone: true,
  imports: [CommonModule, FormsModule, DecimalPipe],
  template: `
    <div class="space-y-6 relative">
      <!-- Toast Notification -->
      <div *ngIf="showSuccessToast" class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-xl z-[100] flex items-center gap-3 transition-opacity duration-300">
        <i class="pi pi-check-circle text-xl"></i>
        <span class="font-medium">{{ successMessage }}</span>
      </div>

      <!-- Error Notification -->
      <div *ngIf="errorMessage" class="fixed top-4 right-4 bg-red-600 text-white px-6 py-3 rounded-lg shadow-xl z-[100] flex items-center gap-3 transition-opacity duration-300">
        <i class="pi pi-exclamation-circle text-xl"></i>
        <span class="font-medium">{{ errorMessage }}</span>
        <button (click)="errorMessage = ''" class="ml-2 hover:bg-white/20 rounded-full p-1"><i class="pi pi-times"></i></button>
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
          <button (click)="loadPayments()" 
                  class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            <i class="pi pi-refresh mr-2" [class.spin]="loading()"></i>Actualiser
          </button>
          <button (click)="openNewPaymentModal()" 
                  class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 shadow-sm transition flex items-center gap-2">
            <i class="pi pi-plus"></i>Nouveau paiement
          </button>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-yellow-500">
          <p class="text-gray-500 text-sm">En attente</p>
          <p class="text-2xl font-bold text-gray-800">{{ stats()?.pending?.count || 0 }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-500">
          <p class="text-gray-500 text-sm">Total Collecté (Période)</p>
          <p class="text-2xl font-bold text-gray-800">{{ stats()?.total_collected || 0 | number }} F</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-blue-500">
          <p class="text-gray-500 text-sm">Total En Attente</p>
          <p class="text-2xl font-bold text-gray-800">{{ stats()?.pending?.total || 0 | number }} F</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-purple-500">
          <p class="text-gray-500 text-sm">Facturé Total</p>
          <p class="text-2xl font-bold text-gray-800">{{ stats()?.total_invoiced || 0 | number }} F</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div class="md:col-span-2">
            <input type="text" [(ngModel)]="searchQuery" placeholder="Rechercher..."
                   class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
          </div>
          <select [(ngModel)]="statusFilter" class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500">
            <option value="">Tous les statuts</option>
            <option value="en_attente">En attente</option>
            <option value="valide">Validé</option>
            <option value="rejete">Rejeté</option>
            <option value="annule">Annulé</option>
          </select>
          <select [(ngModel)]="methodFilter" class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500">
            <option value="">Tous les modes</option>
            <option value="especes">Espèces</option>
            <option value="mobile_money">Mobile Money</option>
            <option value="virement">Virement</option>
            <option value="cheque">Chèque</option>
          </select>
          <input type="date" [(ngModel)]="dateFilter" 
                 class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500">
        </div>
      </div>

      <!-- Payments Table -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Référence</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Facture</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Montant</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Mode</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Statut</th>
                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr *ngIf="loading()" class="animate-pulse">
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">Chargement des données...</td>
              </tr>
              <tr *ngIf="!loading() && filteredPayments().length === 0">
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">Aucun paiement trouvé.</td>
              </tr>
              <tr *ngFor="let payment of filteredPayments()" class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                   <!-- Reference is autogenerated backend, might not be sent in list, check fields -->
                  <span class="font-mono text-sm text-gray-600 font-medium">{{ payment.id.substring(0,8) }}...</span> 
                </td>
                <td class="px-6 py-4">
                  <div class="font-bold text-gray-800">{{ payment.invoice?.number || 'N/A' }}</div>
                   <!-- We don't have student name directly on payment unless we load it or from invoice relation -->
                   <!-- The backend index returns with invoice. But invoice relation does not have student name loaded automatically -->
                   <!-- We can try to see if invoice has student_id. -->
                   <div class="text-xs text-gray-500">{{ payment.invoice?.description || '-' }}</div>
                </td>
                <td class="px-6 py-4 font-black text-gray-800">
                  {{ payment.montant | number }} F
                </td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center gap-2 text-sm font-medium bg-gray-50 px-2.5 py-1 rounded text-gray-700">
                    <i [class]="getMethodIcon(payment.mode_paiement)" class="text-gray-500"></i>
                    {{ getMethodLabel(payment.mode_paiement) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600 font-medium">{{ payment.date_paiement | date:'dd/MM/yyyy' }}</td>
                <td class="px-6 py-4">
                  <span class="px-2.5 py-1 text-xs rounded-full font-bold uppercase tracking-wide"
                        [ngClass]="{
                          'bg-yellow-100 text-yellow-800 border border-yellow-200': payment.statut === 'en_attente',
                          'bg-green-100 text-green-800 border border-green-200': payment.statut === 'valide',
                          'bg-red-100 text-red-800 border border-red-200': payment.statut === 'rejete',
                          'bg-gray-100 text-gray-800 border border-gray-200': payment.statut === 'annule'
                        }">
                    {{ getStatusLabel(payment.statut) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button *ngIf="payment.statut === 'en_attente'" (click)="validatePayment(payment)"
                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition" title="Valider">
                      <i class="pi pi-check text-lg"></i>
                    </button>
                    <!--
                    <button *ngIf="payment.statut === 'en_attente'" (click)="confirmReject(payment)"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Rejeter">
                      <i class="pi pi-times text-lg"></i>
                    </button>
                    -->
                    <button (click)="viewDetails(payment)"
                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Détails">
                      <i class="pi pi-eye text-lg"></i>
                    </button>
                    <button *ngIf="payment.statut === 'valide'" (click)="printReceipt(payment)"
                            class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition" title="Reçu">
                      <i class="pi pi-print text-lg"></i>
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
        <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl overflow-hidden" (click)="$event.stopPropagation()">
          <div class="bg-gradient-to-r from-emerald-600 to-green-700 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">Enregistrer un Paiement</h3>
            <button (click)="showNewPayment = false" class="text-white/80 hover:text-white transition"><i class="pi pi-times text-lg"></i></button>
          </div>
          <form (ngSubmit)="createPayment()" class="p-6 space-y-4">
            
            <div *ngIf="loadingUnpaid" class="text-center text-gray-500 py-2">
                <i class="pi pi-spin pi-spinner mr-2"></i> Chargement des factures...
            </div>

            <div *ngIf="!loadingUnpaid">
              <label class="block text-sm font-bold text-gray-700 mb-1">Facture à payer *</label>
              <select [(ngModel)]="newPayment.invoice_id" name="invoice_id" required (change)="onInvoiceSelect()"
                      class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white">
                <option value="">-- Sélectionner une facture --</option>
                <option *ngFor="let inv of unpaidInvoices" [value]="inv.id">
                  {{ inv.number }} - {{ inv.student_name }} (Reste: {{ inv.solde | number }} F)
                </option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Montant *</label>
                <input type="number" [(ngModel)]="newPayment.montant" name="montant" required
                       class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 font-bold">
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Date *</label>
                <input type="date" [(ngModel)]="newPayment.date_paiement" name="date_paiement" required
                       class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500">
              </div>
            </div>

             <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Mode de paiement *</label>
                <select [(ngModel)]="newPayment.mode_paiement" name="mode_paiement" required
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white">
                  <option value="especes">Espèces</option>
                  <option value="mobile_money">Mobile Money</option>
                  <option value="virement">Virement bancaire</option>
                  <option value="cheque">Chèque</option>
                  <option value="carte">Carte Bancaire</option>
                </select>
              </div>

            <div *ngIf="['mobile_money', 'virement', 'cheque'].includes(newPayment.mode_paiement)">
              <label class="block text-sm font-bold text-gray-700 mb-1">Référence / Numéro</label>
              <input type="text" [(ngModel)]="newPayment.reference_transaction" name="reference_transaction"
                     class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500">
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Notes</label>
              <textarea [(ngModel)]="newPayment.notes" name="notes" rows="2"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 bg-gray-50 -mx-6 -mb-6 px-6 py-4 mt-2">
              <button type="button" (click)="showNewPayment = false"
                      class="px-6 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-50 text-sm uppercase tracking-wide transition">
                Annuler
              </button>
              <button type="submit" [disabled]="submitting"
                      class="px-6 py-2.5 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 text-sm uppercase tracking-wide transition shadow-lg shadow-emerald-200 flex items-center gap-2">
                <i *ngIf="submitting" class="pi pi-spin pi-spinner"></i>
                Enregistrer
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
export class AccountingPaymentsComponent implements OnInit {
  private financeService = inject(FinanceService);
  private router = inject(Router);

  searchQuery = '';
  statusFilter = '';
  methodFilter = '';
  dateFilter = '';
  
  // UI State
  loading = signal(false);
  submitting = false;
  showNewPayment = false;
  selectedPayment: any = null;
  errorMessage = '';
  
  showSuccessToast = false;
  successMessage = '';

  loadingUnpaid = false;
  unpaidInvoices: any[] = [];

  stats = signal<any>(null);
  payments = signal<any[]>([]);

  // Form
  newPayment = {
    invoice_id: '',
    montant: 0,
    mode_paiement: 'especes',
    date_paiement: new Date().toISOString().split('T')[0],
    reference_transaction: '',
    notes: ''
  };

  filteredPayments = computed(() => {
    let result = this.payments();
    // Filter locally if needed, but search usually backend. 
    // Here we do simple local filtering for demo speed
    if (this.searchQuery) {
      const q = this.searchQuery.toLowerCase();
      result = result.filter(p => 
        (p.invoice?.number?.toLowerCase() || '').includes(q) ||
        (p.reference_transaction?.toLowerCase() || '').includes(q)
      );
    }
    if (this.statusFilter) result = result.filter(p => p.statut === this.statusFilter);
    if (this.methodFilter) result = result.filter(p => p.mode_paiement === this.methodFilter);
    return result;
  });

  ngOnInit() {
    this.checkRouteFilters();
    this.loadPayments();
  }

  checkRouteFilters() {
    // Check URL to set default filters
    const url = this.router.url;
    if (url.includes('validation')) {
      this.statusFilter = 'en_attente';
    } else if (url.includes('history')) {
      // Show all or maybe 'valide'
      // this.statusFilter = 'valide'; 
    }
  }

  getPageTitle(): string {
    const url = this.router.url;
    if (url.includes('validation')) return 'Validation des Paiements';
    if (url.includes('history')) return 'Historique des Paiements';
    return 'Gestion des Paiements (CLEAN)';
  }

  getPageSubtitle(): string {
     const url = this.router.url;
    if (url.includes('validation')) return 'Validez les paiements en attente';
    if (url.includes('history')) return 'Consultez l\'historique complet';
    return 'Validation et suivi des encaissements';
  }

  loadPayments() {
    this.loading.set(true);
    // Load stats then payments
    this.financeService.getInvoiceStats().subscribe({
        next: (stats) => this.stats.set(stats),
        error: (err) => console.error('Stats error', err)
    });

    this.financeService.getPayments({ per_page: 50 }).pipe(
        finalize(() => this.loading.set(false))
    ).subscribe({
        next: (res: any) => {
            this.payments.set(res.data || []);
        },
        error: (err) => {
            this.errorMessage = "Impossible de charger les paiements.";
            console.error(err);
        }
    });
  }

  openNewPaymentModal() {
      this.showNewPayment = true;
      this.loadUnpaidInvoices();
      this.newPayment = {
        invoice_id: '',
        montant: 0,
        mode_paiement: 'especes',
        date_paiement: new Date().toISOString().split('T')[0],
        reference_transaction: '',
        notes: ''
      };
  }

  loadUnpaidInvoices() {
      this.loadingUnpaid = true;
      this.financeService.getUnpaidInvoices().subscribe({
          next: (res: any) => {
              this.unpaidInvoices = res.invoices.data || []; // Structure from PaymentController::unpaid
              this.loadingUnpaid = false;
          },
          error: (err) => {
              this.errorMessage = "Erreur chargement factures.";
              this.loadingUnpaid = false;
          }
      });
  }

  onInvoiceSelect() {
      const invoice = this.unpaidInvoices.find(i => i.id === this.newPayment.invoice_id);
      if (invoice) {
          this.newPayment.montant = parseFloat(invoice.solde);
      }
  }

  createPayment() {
    if (!this.newPayment.invoice_id || !this.newPayment.montant) {
        this.errorMessage = "Veuillez remplir les champs obligatoires.";
        return;
    }

    this.submitting = true;
    this.financeService.createPayment(this.newPayment).pipe(
        finalize(() => this.submitting = false)
    ).subscribe({
        next: (res) => {
            this.showToast('Paiement enregistré avec succès !');
            this.showNewPayment = false;
            this.loadPayments(); // Reload list
        },
        error: (err) => {
            console.error(err);
            this.errorMessage = err.error?.message || "Erreur lors de l'enregistrement.";
        }
    });
  }

  validatePayment(payment: any) {
      if(!confirm("Valider ce paiement ?")) return;
      
      this.financeService.validatePayment(payment.id).subscribe({
          next: () => {
              this.showToast("Paiement validé.");
              this.loadPayments();
          },
          error: (err) => this.errorMessage = "Erreur validation."
      });
  }

  viewDetails(payment: any) {
      this.selectedPayment = payment; // Simple detail view, can require more
  }

  printReceipt(payment: any) {
    if(payment.statut !== 'valide') return;
    this.financeService.downloadReceipt(payment.id).subscribe(blob => {
        const url = window.URL.createObjectURL(blob);
        window.open(url);
    });
  }

  // Helpers
  getMethodIcon(method: string): string {
    const icons: Record<string, string> = {
      especes: 'pi pi-money-bill',
      mobile_money: 'pi pi-mobile',
      virement: 'pi pi-building',
      cheque: 'pi pi-file',
      carte: 'pi pi-credit-card'
    };
    return icons[method] || 'pi pi-credit-card';
  }

  getMethodLabel(method: string): string {
    const labels: Record<string, string> = {
      especes: 'Espèces',
      mobile_money: 'Mobile Money',
      virement: 'Virement',
      cheque: 'Chèque',
      carte: 'Carte'
    };
    return labels[method] || method;
  }

  getStatusLabel(status: string): string {
    const labels: Record<string, string> = {
      en_attente: 'En attente',
      valide: 'Validé',
      rejete: 'Rejeté',
      annule: 'Annulé'
    };
    return labels[status] || status;
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
