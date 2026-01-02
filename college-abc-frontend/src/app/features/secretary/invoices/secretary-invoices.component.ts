import { Component, signal, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-secretary-invoices',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6 relative">
      <!-- Toast Notification -->
      <div *ngIf="showSuccessToast" class="fixed top-4 right-4 bg-teal-600 text-white px-6 py-3 rounded-lg shadow-xl z-[100] flex items-center gap-3 transition-opacity duration-300">
        <i class="pi pi-check-circle text-xl"></i>
        <span class="font-medium">{{ successMessage }}</span>
      </div>

      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Facturation</h1>
          <p class="text-gray-500">Générez et gérez les factures des élèves</p>
        </div>
        <div class="flex gap-3">
          <button class="px-4 py-2 border border-teal-600 text-teal-600 rounded-lg hover:bg-teal-50 font-medium transition flex items-center gap-2">
            <i class="pi pi-cog"></i> Génération en masse
          </button>
          <button (click)="showNewInvoice = true"
                  class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-bold transition flex items-center gap-2 shadow-sm">
            <i class="pi pi-plus"></i> Nouvelle Facture
          </button>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500">
          <p class="text-gray-500 text-sm font-medium">Total Facturé</p>
          <p class="text-2xl font-bold text-gray-800">{{ totalInvoiced() | number }} <span class="text-sm font-normal text-gray-400">FCFA</span></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
          <p class="text-gray-500 text-sm font-medium">Factures ce mois</p>
          <p class="text-2xl font-bold text-gray-800">{{ monthlyInvoices() }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-yellow-500">
          <p class="text-gray-500 text-sm font-medium">En attente</p>
          <p class="text-2xl font-bold text-gray-800">{{ pendingInvoices() }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-red-500">
          <p class="text-gray-500 text-sm font-medium">En retard</p>
          <p class="text-2xl font-bold text-gray-800">{{ overdueInvoices() }}</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl shadow-sm p-4 flex flex-wrap gap-4 border border-gray-100">
        <div class="relative">
             <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
             <input type="text" [(ngModel)]="searchQuery" placeholder="Rechercher..."
               class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 w-64 transition">
        </div>
        <select [(ngModel)]="statusFilter"
                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 bg-white cursor-pointer transition">
          <option value="">Tous les statuts</option>
          <option value="pending">En attente</option>
          <option value="paid">Payée</option>
          <option value="overdue">En retard</option>
        </select>
        <select [(ngModel)]="classFilter"
                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 bg-white cursor-pointer transition">
          <option value="">Toutes les classes</option>
          <option>6ème A</option>
          <option>6ème B</option>
          <option>5ème A</option>
          <option>3ème A</option>
        </select>
      </div>

      <!-- Invoices Table -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr class="text-left text-sm font-bold text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-4">Référence</th>
                <th class="px-6 py-4">Élève</th>
                <th class="px-6 py-4">Description</th>
                <th class="px-6 py-4">Montant</th>
                <th class="px-6 py-4">Échéance</th>
                <th class="px-6 py-4">Statut</th>
                <th class="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr *ngFor="let inv of filteredInvoices()" class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 font-mono text-sm font-bold text-gray-600">{{ inv.reference }}</td>
                <td class="px-6 py-4">
                  <div class="font-bold text-gray-800">{{ inv.student }}</div>
                  <div class="text-xs text-gray-500 font-medium">{{ inv.class }}</div>
                </td>
                <td class="px-6 py-4 text-gray-600">{{ inv.description }}</td>
                <td class="px-6 py-4 font-black text-gray-800">{{ inv.amount | number }} FCFA</td>
                <td class="px-6 py-4 text-gray-600 font-medium">{{ inv.dueDate }}</td>
                <td class="px-6 py-4">
                  <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                        [ngClass]="{
                          'bg-yellow-100 text-yellow-700': inv.status === 'pending',
                          'bg-green-100 text-green-700': inv.status === 'paid',
                          'bg-red-100 text-red-700': inv.status === 'overdue'
                        }">
                    {{ getStatusLabel(inv.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex justify-end gap-2">
                    <button class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="PDF">
                      <i class="pi pi-file-pdf"></i>
                    </button>
                    <button class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition" title="Enregistrer paiement">
                      <i class="pi pi-money-bill"></i>
                    </button>
                    <button class="p-2 text-gray-500 hover:bg-gray-50 rounded-lg transition" title="Relance">
                      <i class="pi pi-envelope"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- New Invoice Modal -->
      <div *ngIf="showNewInvoice" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" (click)="showNewInvoice = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden" (click)="$event.stopPropagation()">
          <div class="bg-teal-600 px-6 py-4 flex justify-between items-center">
            <h2 class="text-xl font-bold text-white">Nouvelle Facture</h2>
            <button (click)="showNewInvoice = false" class="text-white/80 hover:text-white transition"><i class="pi pi-times"></i></button>
          </div>
          <form (ngSubmit)="createInvoice()" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Élève</label>
              <input type="text" [(ngModel)]="newInvoice.student" name="student" placeholder="Rechercher un élève..."
                     class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 transition">
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Type de frais</label>
              <select [(ngModel)]="newInvoice.type" name="type"
                      class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 bg-white transition">
                <option>Frais de scolarité</option>
                <option>Frais d'inscription</option>
                <option>Frais de transport</option>
                <option>Frais de cantine</option>
                <option>Autre</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Montant (FCFA)</label>
                <input type="number" [(ngModel)]="newInvoice.amount" name="amount"
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 font-bold transition">
              </div>
              <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Date d'échéance</label>
                <input type="date" [(ngModel)]="newInvoice.dueDate" name="dueDate"
                       class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 transition">
              </div>
            </div>
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-1">Description</label>
              <textarea [(ngModel)]="newInvoice.description" name="description" rows="2"
                        class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 transition"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 bg-gray-50 -mx-6 -mb-6 px-6 py-4 mt-2">
              <button type="button" (click)="showNewInvoice = false"
                      class="px-5 py-2.5 border border-gray-300 rounded-xl font-bold text-gray-700 hover:bg-gray-100 transition">Annuler</button>
              <button type="submit"
                      class="px-5 py-2.5 bg-teal-600 text-white rounded-xl font-bold hover:bg-teal-700 transition shadow-lg shadow-teal-200">Créer</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  `
})
export class SecretaryInvoicesComponent implements OnInit {
  private http = inject(HttpClient);

  showNewInvoice = false;
  searchQuery = '';
  statusFilter = '';
  classFilter = '';
  
  showSuccessToast = false;
  successMessage = '';
  isLoading = signal(false);
  
  totalInvoiced = signal(0);
  monthlyInvoices = signal(0);
  pendingInvoices = signal(0);
  overdueInvoices = signal(0);

  newInvoice = { student: '', type: 'Frais de scolarité', amount: 150000, dueDate: '', description: '' };

  invoices = signal<any[]>([]);

  ngOnInit() {
    this.loadInvoices();
  }

  loadInvoices() {
    this.isLoading.set(true);
    this.http.get<any>(`${environment.apiUrl}/finance/invoices`).subscribe({
      next: (res) => {
        const data = res.data || res || [];
        this.invoices.set(data.map((inv: any) => ({
          reference: inv.reference || 'FAC-' + inv.id.substring(0,8),
          student: inv.student_name || 'Élève #' + (inv.student_id ? inv.student_id.substring(0,8) : '?'),
          class: 'N/A',
          description: inv.libelle || inv.description || 'Frais scolarité',
          amount: inv.montant_ttc || inv.montant,
          dueDate: inv.date_echeance ? new Date(inv.date_echeance).toLocaleDateString('fr-FR') : 'N/A',
          status: inv.statut === 'payee' ? 'paid' : (inv.statut === 'annulee' ? 'cancelled' : 'pending')
        })));
        
        // Simple stats calculation from list
        this.totalInvoiced.set(this.invoices().reduce((acc, inv) => acc + inv.amount, 0));
        this.pendingInvoices.set(this.invoices().filter(i => i.status === 'pending').length);
        this.monthlyInvoices.set(this.invoices().length);
        
        this.isLoading.set(false);
      },
      error: () => this.isLoading.set(false)
    });
  }

  getStatusLabel(status: string): string {
    const labels: Record<string, string> = { 'pending': 'En attente', 'paid': 'Payée', 'overdue': 'En retard' };
    return labels[status] || status;
  }

  filteredInvoices = () => {
    let result = this.invoices();
    if (this.searchQuery) {
      const q = this.searchQuery.toLowerCase();
      result = result.filter(i => i.student.toLowerCase().includes(q) || i.reference.toLowerCase().includes(q));
    }
    if (this.statusFilter) result = result.filter(i => i.status === this.statusFilter);
    if (this.classFilter) result = result.filter(i => i.class === this.classFilter);
    return result;
  };

  createInvoice() {
    this.showToast('Facture créée avec succès !');
    this.showNewInvoice = false;
    this.newInvoice = { student: '', type: 'Frais de scolarité', amount: 150000, dueDate: '', description: '' };
  }

  private showToast(message: string) {
    this.successMessage = message;
    this.showSuccessToast = true;
    setTimeout(() => this.showSuccessToast = false, 3000);
  }
}
