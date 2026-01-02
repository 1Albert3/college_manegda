import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { RouterModule } from '@angular/router';
import { FinanceService } from '../../../../core/services/finance.service';
import { environment } from '../../../../../environments/environment';

interface Invoice {
  id: string;
  number: string;
  student_name: string;
  student_matricule: string;
  type: string;
  description: string;
  montant_ttc: number;
  montant_paye: number;
  solde: number;
  statut: string;
  date_emission: string;
  date_echeance: string;
  is_overdue: boolean;
}

interface Payment {
  id: string;
  reference: string;
  montant: number;
  mode_paiement: string;
  date_paiement: string;
  statut: string;
}

@Component({
  selector: 'app-invoices-management',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  template: `
    <div class="invoices-container">
      <div class="page-header">
        <div class="header-left">
          <h1>💰 Gestion des Factures</h1>
          <p>Suivi des factures et paiements</p>
        </div>
        <div class="header-actions">
          <button class="btn-primary" (click)="openNewInvoice()">
            + Nouvelle Facture
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-row">
        <div class="stat-card">
          <span class="stat-icon">📄</span>
          <div class="stat-content">
            <span class="stat-value">{{ stats.total_invoices }}</span>
            <span class="stat-label">Total Factures</span>
          </div>
        </div>
        <div class="stat-card green">
          <span class="stat-icon">💵</span>
          <div class="stat-content">
            <span class="stat-value">{{ stats.total_collected | number }} FCFA</span>
            <span class="stat-label">Total Collecté</span>
          </div>
        </div>
        <div class="stat-card orange">
          <span class="stat-icon">⏳</span>
          <div class="stat-content">
            <span class="stat-value">{{ stats.total_pending | number }} FCFA</span>
            <span class="stat-label">En Attente</span>
          </div>
        </div>
        <div class="stat-card red">
          <span class="stat-icon">⚠️</span>
          <div class="stat-content">
            <span class="stat-value">{{ stats.overdue_count }}</span>
            <span class="stat-label">En Retard</span>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters-panel">
        <div class="filters-row">
          <div class="filter-group">
            <label>Recherche</label>
            <input 
              type="text" 
              [(ngModel)]="searchQuery"
              placeholder="N° facture, nom élève..."
              (input)="filterInvoices()">
          </div>
          <div class="filter-group">
            <label>Statut</label>
            <select [(ngModel)]="filterStatus" (change)="filterInvoices()">
              <option value="">Tous</option>
              <option value="emise">Émises</option>
              <option value="partiellement_payee">Partiellement payées</option>
              <option value="payee">Payées</option>
              <option value="annulee">Annulées</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Type</label>
            <select [(ngModel)]="filterType" (change)="filterInvoices()">
              <option value="">Tous</option>
              <option value="inscription">Inscription</option>
              <option value="scolarite">Scolarité</option>
              <option value="cantine">Cantine</option>
              <option value="transport">Transport</option>
            </select>
          </div>
          <div class="filter-group">
            <label>&nbsp;</label>
            <button class="btn-secondary" (click)="resetFilters()">
              Réinitialiser
            </button>
          </div>
        </div>
      </div>

      <!-- Invoices Table -->
      <div class="invoices-panel">
        <table class="invoices-table">
          <thead>
            <tr>
              <th>N° Facture</th>
              <th>Élève</th>
              <th>Type</th>
              <th>Montant</th>
              <th>Payé</th>
              <th>Solde</th>
              <th>Échéance</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr *ngFor="let invoice of filteredInvoices" [class.overdue]="invoice.is_overdue">
              <td class="col-number">{{ invoice.number }}</td>
              <td class="col-student">
                <span class="student-name">{{ invoice.student_name }}</span>
                <span class="student-matricule">{{ invoice.student_matricule }}</span>
              </td>
              <td class="col-type">
                <span class="type-badge" [class]="invoice.type">
                  {{ getTypeName(invoice.type) }}
                </span>
              </td>
              <td class="col-amount">{{ invoice.montant_ttc | number }} FCFA</td>
              <td class="col-paid green">{{ invoice.montant_paye | number }} FCFA</td>
              <td class="col-balance" [class.red]="invoice.solde > 0">
                {{ invoice.solde | number }} FCFA
              </td>
              <td class="col-due" [class.overdue]="invoice.is_overdue">
                {{ invoice.date_echeance | date:'dd/MM/yyyy' }}
                <span class="overdue-tag" *ngIf="invoice.is_overdue">En retard</span>
              </td>
              <td class="col-status">
                <span class="status-badge" [class]="invoice.statut">
                  {{ getStatusName(invoice.statut) }}
                </span>
              </td>
              <td class="col-actions">
                <button class="btn-icon" title="Voir" (click)="viewInvoice(invoice)">👁️</button>
                <button class="btn-icon" title="Paiement" (click)="addPayment(invoice)" 
                        [disabled]="invoice.solde <= 0">💳</button>
                <button class="btn-icon" title="Imprimer" (click)="printInvoice(invoice)">🖨️</button>
                <button class="btn-icon" title="Rappel" (click)="sendReminder(invoice)"
                        *ngIf="invoice.solde > 0">📨</button>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="empty-state" *ngIf="filteredInvoices.length === 0">
          <span class="empty-icon">📄</span>
          <p>Aucune facture trouvée</p>
        </div>

        <!-- Pagination -->
        <div class="pagination" *ngIf="totalPages > 1">
          <button (click)="changePage(-1)" [disabled]="currentPage === 1">← Précédent</button>
          <span>Page {{ currentPage }} / {{ totalPages }}</span>
          <button (click)="changePage(1)" [disabled]="currentPage === totalPages">Suivant →</button>
        </div>
      </div>

      <!-- Payment Modal -->
      <div class="modal-overlay" *ngIf="showPaymentModal" (click)="closePaymentModal()">
        <div class="modal-content" (click)="$event.stopPropagation()">
          <div class="modal-header">
            <h2>Enregistrer un paiement</h2>
            <button class="btn-close" (click)="closePaymentModal()">×</button>
          </div>
          <div class="modal-body" *ngIf="selectedInvoice">
            <div class="invoice-summary">
              <p><strong>Facture:</strong> {{ selectedInvoice.number }}</p>
              <p><strong>Élève:</strong> {{ selectedInvoice.student_name }}</p>
              <p><strong>Solde restant:</strong> {{ selectedInvoice.solde | number }} FCFA</p>
            </div>

            <div class="form-group">
              <label>Montant du paiement *</label>
              <input 
                type="number" 
                [(ngModel)]="paymentForm.montant"
                [max]="selectedInvoice.solde"
                min="1">
              <span class="help-text">Maximum: {{ selectedInvoice.solde | number }} FCFA</span>
            </div>

            <div class="form-group">
              <label>Mode de paiement *</label>
              <select [(ngModel)]="paymentForm.mode_paiement">
                <option value="especes">Espèces</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="cheque">Chèque</option>
                <option value="virement">Virement bancaire</option>
                <option value="carte">Carte bancaire</option>
              </select>
            </div>

            <div class="form-group">
              <label>Date du paiement *</label>
              <input type="date" [(ngModel)]="paymentForm.date_paiement">
            </div>

            <div class="form-group" *ngIf="paymentForm.mode_paiement === 'mobile_money'">
              <label>Référence transaction</label>
              <input type="text" [(ngModel)]="paymentForm.reference_transaction">
            </div>

            <div class="form-group" *ngIf="paymentForm.mode_paiement === 'cheque'">
              <label>Numéro de chèque</label>
              <input type="text" [(ngModel)]="paymentForm.numero_cheque">
            </div>

            <div class="form-group" *ngIf="paymentForm.mode_paiement === 'cheque' || paymentForm.mode_paiement === 'virement'">
              <label>Banque</label>
              <input type="text" [(ngModel)]="paymentForm.banque">
            </div>

            <div class="form-group">
              <label>Notes</label>
              <textarea [(ngModel)]="paymentForm.notes" rows="2"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn-secondary" (click)="closePaymentModal()">Annuler</button>
            <button class="btn-primary" (click)="submitPayment()" [disabled]="isSaving || !isPaymentValid()">
              {{ isSaving ? 'Enregistrement...' : 'Enregistrer le paiement' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Messages -->
      <div class="toast success" *ngIf="successMessage">
        ✅ {{ successMessage }}
      </div>
      <div class="toast error" *ngIf="errorMessage">
        ❌ {{ errorMessage }}
      </div>
    </div>
  `,
  styles: [`
    .invoices-container {
      padding: 1.5rem 2rem;
      max-width: 1400px;
      margin: 0 auto;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 2rem;
    }

    .page-header h1 {
      font-size: 1.75rem;
      color: #1a365d;
      margin: 0 0 0.25rem;
    }

    .page-header p {
      color: #64748b;
      margin: 0;
    }

    .btn-primary {
      background: linear-gradient(135deg, #4f46e5, #6366f1);
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
    }

    /* Stats */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 1.25rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      border-left: 4px solid #4f46e5;
    }

    .stat-card.green { border-left-color: #10b981; }
    .stat-card.orange { border-left-color: #f59e0b; }
    .stat-card.red { border-left-color: #ef4444; }

    .stat-icon { font-size: 1.5rem; }

    .stat-value {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1e293b;
    }

    .stat-label {
      font-size: 0.8rem;
      color: #64748b;
    }

    /* Filters */
    .filters-panel {
      background: white;
      border-radius: 12px;
      padding: 1.25rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .filters-row {
      display: flex;
      gap: 1.5rem;
      align-items: flex-end;
    }

    .filter-group {
      display: flex;
      flex-direction: column;
      min-width: 150px;
    }

    .filter-group:first-child {
      flex: 1;
    }

    .filter-group label {
      font-size: 0.8rem;
      font-weight: 600;
      color: #64748b;
      margin-bottom: 0.5rem;
    }

    .filter-group input,
    .filter-group select {
      padding: 0.625rem 1rem;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      font-size: 0.9rem;
    }

    .btn-secondary {
      background: white;
      border: 1px solid #e2e8f0;
      padding: 0.625rem 1rem;
      border-radius: 8px;
      cursor: pointer;
    }

    /* Table */
    .invoices-panel {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      overflow: hidden;
    }

    .invoices-table {
      width: 100%;
      border-collapse: collapse;
    }

    .invoices-table th,
    .invoices-table td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid #f1f5f9;
    }

    .invoices-table th {
      background: #f8fafc;
      font-size: 0.75rem;
      font-weight: 600;
      color: #64748b;
      text-transform: uppercase;
    }

    .invoices-table tr:hover {
      background: #fafbfc;
    }

    .invoices-table tr.overdue {
      background: #fef2f2;
    }

    .col-number {
      font-family: monospace;
      font-weight: 600;
    }

    .col-student {
      display: flex;
      flex-direction: column;
    }

    .student-name {
      font-weight: 500;
      color: #1e293b;
    }

    .student-matricule {
      font-size: 0.75rem;
      color: #94a3b8;
    }

    .type-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 500;
    }

    .type-badge.inscription { background: #dbeafe; color: #1e40af; }
    .type-badge.scolarite { background: #dcfce7; color: #166534; }
    .type-badge.cantine { background: #fef3c7; color: #92400e; }
    .type-badge.transport { background: #f3e8ff; color: #7c3aed; }

    .col-amount { font-weight: 600; }
    .green { color: #10b981; }
    .red { color: #ef4444; }

    .col-due.overdue { color: #ef4444; }

    .overdue-tag {
      display: block;
      font-size: 0.7rem;
      color: #ef4444;
      font-weight: 600;
    }

    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 500;
    }

    .status-badge.emise { background: #dbeafe; color: #1e40af; }
    .status-badge.partiellement_payee { background: #fef3c7; color: #92400e; }
    .status-badge.payee { background: #dcfce7; color: #166534; }
    .status-badge.annulee { background: #f1f5f9; color: #64748b; }

    .btn-icon {
      background: none;
      border: none;
      padding: 0.25rem;
      font-size: 1rem;
      cursor: pointer;
      opacity: 0.7;
    }

    .btn-icon:hover:not(:disabled) { opacity: 1; }
    .btn-icon:disabled { opacity: 0.3; cursor: not-allowed; }

    .empty-state {
      text-align: center;
      padding: 3rem;
      color: #94a3b8;
    }

    .empty-icon { font-size: 3rem; }

    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 1rem;
      padding: 1rem;
      border-top: 1px solid #f1f5f9;
    }

    .pagination button {
      padding: 0.5rem 1rem;
      border: 1px solid #e2e8f0;
      background: white;
      border-radius: 6px;
      cursor: pointer;
    }

    .pagination button:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    /* Modal */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }

    .modal-content {
      background: white;
      border-radius: 16px;
      width: 100%;
      max-width: 500px;
      max-height: 90vh;
      overflow: auto;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid #f1f5f9;
    }

    .modal-header h2 {
      margin: 0;
      font-size: 1.25rem;
    }

    .btn-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      color: #64748b;
      cursor: pointer;
    }

    .modal-body {
      padding: 1.5rem;
    }

    .invoice-summary {
      background: #f8fafc;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
    }

    .invoice-summary p {
      margin: 0.25rem 0;
      font-size: 0.9rem;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    .form-group label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: #475569;
      margin-bottom: 0.5rem;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      font-size: 0.95rem;
    }

    .help-text {
      font-size: 0.75rem;
      color: #94a3b8;
    }

    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 0.75rem;
      padding: 1rem 1.5rem;
      border-top: 1px solid #f1f5f9;
    }

    /* Toast */
    .toast {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      padding: 1rem 1.5rem;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
      animation: slideIn 0.3s ease;
    }

    .toast.success {
      background: #ecfdf5;
      color: #047857;
    }

    .toast.error {
      background: #fef2f2;
      color: #dc2626;
    }

    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }

    @media (max-width: 1024px) {
      .stats-row {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .filters-row {
        flex-wrap: wrap;
      }
    }
  `]
})
export class InvoicesManagementComponent implements OnInit {
  invoices: Invoice[] = [];
  filteredInvoices: Invoice[] = [];
  
  searchQuery = '';
  filterStatus = '';
  filterType = '';
  
  currentPage = 1;
  totalPages = 1;
  perPage = 20;

  stats = {
    total_invoices: 0,
    total_collected: 0,
    total_pending: 0,
    overdue_count: 0
  };

  showPaymentModal = false;
  selectedInvoice: Invoice | null = null;
  paymentForm = {
    montant: 0,
    mode_paiement: 'especes',
    date_paiement: new Date().toISOString().split('T')[0],
    reference_transaction: '',
    numero_cheque: '',
    banque: '',
    notes: ''
  };

  isSaving = false;
  successMessage = '';
  errorMessage = '';

  private financeService = inject(FinanceService);

  constructor() {}

  ngOnInit() {
    this.loadInvoices();
    this.loadStats();
  }

  loadInvoices() {
    const params: any = {
      page: this.currentPage,
      per_page: this.perPage
    };

    if (this.filterStatus) params.statut = this.filterStatus;
    if (this.filterType) params.type = this.filterType;
    if (this.searchQuery) params.search = this.searchQuery;

    this.financeService.getInvoices(params)
      .subscribe({
        next: (res) => {
          this.invoices = res.data || [];
          this.filteredInvoices = this.invoices;
          this.totalPages = res.last_page || 1;
        },
        error: (err) => console.error('Error loading invoices', err)
      });
  }

  loadStats() {
    this.financeService.getInvoiceStats()
      .subscribe({
        next: (res) => {
          this.stats = {
            total_invoices: res.total_invoiced || 0,
            total_collected: res.total_paid || 0,
            total_pending: res.total_pending || 0,
            overdue_count: res.total_overdue || 0
          };
        }
      });
  }

  filterInvoices() {
    this.currentPage = 1;
    this.loadInvoices();
  }

  resetFilters() {
    this.searchQuery = '';
    this.filterStatus = '';
    this.filterType = '';
    this.filterInvoices();
  }

  changePage(delta: number) {
    this.currentPage += delta;
    this.loadInvoices();
  }

  getTypeName(type: string): string {
    const types: { [key: string]: string } = {
      inscription: 'Inscription',
      scolarite: 'Scolarité',
      cantine: 'Cantine',
      transport: 'Transport'
    };
    return types[type] || type;
  }

  getStatusName(status: string): string {
    const statuses: { [key: string]: string } = {
      emise: 'Émise',
      partiellement_payee: 'Partielle',
      payee: 'Payée',
      annulee: 'Annulée'
    };
    return statuses[status] || status;
  }

  openNewInvoice() {
    // TODO: Ouvrir formulaire nouvelle facture
  }

  viewInvoice(invoice: Invoice) {
    // TODO: Naviguer vers les détails
  }

  printInvoice(invoice: Invoice) {
    window.open(`${environment.apiUrl}/finance/invoices/${invoice.id}/print`, '_blank');
  }

  sendReminder(invoice: Invoice) {
    // Note: On pourrait ajouter sendReminders dans FinanceService
    this.financeService.getInvoices({ invoice_ids: [invoice.id] }).subscribe({ // Mocking or adding method...
        // ...
    });
    // Pour l'instant on laisse comme ça ou on ajoute la méthode
  }

  addPayment(invoice: Invoice) {
    this.selectedInvoice = invoice;
    this.paymentForm.montant = invoice.solde;
    this.showPaymentModal = true;
  }

  closePaymentModal() {
    this.showPaymentModal = false;
    this.selectedInvoice = null;
    this.paymentForm = {
      montant: 0,
      mode_paiement: 'especes',
      date_paiement: new Date().toISOString().split('T')[0],
      reference_transaction: '',
      numero_cheque: '',
      banque: '',
      notes: ''
    };
  }

  isPaymentValid(): boolean {
    return this.paymentForm.montant > 0 && 
           this.paymentForm.mode_paiement !== '' &&
           this.paymentForm.date_paiement !== '';
  }

  submitPayment() {
    if (!this.selectedInvoice || !this.isPaymentValid()) return;

    this.isSaving = true;

    const payload = {
      ...this.paymentForm,
      invoice_id: this.selectedInvoice.id
    };

    this.financeService.createPayment(payload)
      .subscribe({
        next: (res: any) => {
          this.isSaving = false;
          this.successMessage = res.message || 'Paiement enregistré !';
          this.closePaymentModal();
          this.loadInvoices();
          this.loadStats();
          setTimeout(() => this.successMessage = '', 3000);
        },
        error: (err) => {
          this.isSaving = false;
          this.errorMessage = err.error?.message || 'Erreur lors de l\'enregistrement';
          setTimeout(() => this.errorMessage = '', 3000);
        }
      });
  }
}
