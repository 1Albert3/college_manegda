import { Component, signal, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-admin-validations',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Validations</h1>
          <p class="text-gray-500">Approbations et contrôles en attente</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm font-medium">
            {{ pendingTotal() }} en attente
          </span>
        </div>
      </div>

      <!-- Categories -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <button *ngFor="let cat of categories()" (click)="selectedCategory = cat.id"
                class="bg-white rounded-xl p-5 text-left transition-all"
                [ngClass]="selectedCategory === cat.id ? 'ring-2 ring-blue-500 shadow-md' : 'hover:shadow-md'">
          <div class="flex items-center justify-between">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                 [style.background-color]="cat.color + '20'" [style.color]="cat.color">
              <i [class]="cat.icon + ' text-xl'"></i>
            </div>
            <span *ngIf="cat.count > 0" class="px-2 py-1 bg-red-500 text-white text-xs rounded-full">{{ cat.count }}</span>
          </div>
          <h3 class="font-semibold text-gray-800 mt-3">{{ cat.name }}</h3>
          <p class="text-sm text-gray-500">{{ cat.count }} en attente</p>
        </button>
      </div>

      <!-- Items -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="bg-gray-800 px-6 py-4 flex justify-between items-center">
          <h2 class="text-white font-bold">{{ getCategoryName() }}</h2>
          <button (click)="loadValidations()" class="text-gray-400 hover:text-white"><i class="pi pi-refresh"></i></button>
        </div>
        <div class="divide-y" *ngIf="!isLoading; else loadingTpl">
          <div *ngFor="let item of filteredItems()" class="p-4 flex items-center gap-4 hover:bg-gray-50">
            <div class="w-12 h-12 rounded-full flex items-center justify-center"
                 [ngClass]="{
                   'bg-blue-100 text-blue-600': item.type === 'bulletin',
                   'bg-green-100 text-green-600': item.type === 'inscription',
                   'bg-purple-100 text-purple-600': item.type === 'absence',
                   'bg-orange-100 text-orange-600': item.type === 'payment'
                 }">
              <i [class]="getItemIcon(item.type)"></i>
            </div>
            <div class="flex-1">
              <div class="font-medium text-gray-800">{{ item.title }}</div>
              <div class="text-sm text-gray-500">{{ item.details }}</div>
              <div class="text-xs text-gray-400 mt-1">Soumis le {{ item.date }} par {{ item.submittedBy }}</div>
            </div>
            <div class="flex gap-2">
              <button (click)="viewItem(item)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Voir">
                <i class="pi pi-eye"></i>
              </button>
              <button (click)="approveItem(item)" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="Approuver">
                <i class="pi pi-check"></i>
              </button>
              <button (click)="rejectItem(item)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Rejeter">
                <i class="pi pi-times"></i>
              </button>
            </div>
          </div>
          <div *ngIf="filteredItems().length === 0" class="p-8 text-center text-gray-500">
            <i class="pi pi-check-circle text-4xl text-green-500 mb-2"></i>
            <p>Aucune validation en attente</p>
          </div>
        </div>
        <ng-template #loadingTpl>
          <div class="p-12 text-center">
             <div class="inline-block w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
             <p class="mt-2 text-gray-500">Chargement...</p>
          </div>
        </ng-template>
      </div>

      <!-- Details Modal -->
      <div *ngIf="selectedItem" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-full max-w-lg mx-4">
          <div class="bg-gray-800 px-6 py-4 rounded-t-2xl flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">Détails de la validation</h3>
            <button (click)="selectedItem = null" class="text-white/80 hover:text-white">
              <i class="pi pi-times"></i>
            </button>
          </div>
          <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-500">Type</p>
                <p class="font-semibold">{{ getTypeName(selectedItem.type) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Date de soumission</p>
                <p class="font-semibold">{{ selectedItem.date }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Soumis par</p>
                <p class="font-semibold">{{ selectedItem.submittedBy }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Statut</p>
                <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded-full text-sm">En attente</span>
              </div>
            </div>
            <div class="border-t pt-4">
              <p class="text-sm text-gray-500">Description</p>
              <p class="text-gray-700">{{ selectedItem.title }}<br>{{ selectedItem.details }}</p>
            </div>
            <div class="flex justify-end gap-3 pt-4">
              <button (click)="rejectItem(selectedItem)" class="px-6 py-2 border border-red-500 text-red-600 rounded-lg hover:bg-red-50">Rejeter</button>
              <button (click)="approveItem(selectedItem)" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Approuver</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  `
})
export class AdminValidationsComponent implements OnInit {
  private http = inject(HttpClient);
  
  selectedCategory = 'all';
  selectedItem: any = null;
  isLoading = false;

  categories = signal([
    { id: 'all', name: 'Tous', icon: 'pi pi-list', color: '#6B7280', count: 0 },
    { id: 'bulletin', name: 'Bulletins', icon: 'pi pi-file', color: '#3B82F6', count: 0 },
    { id: 'inscription', name: 'Inscriptions', icon: 'pi pi-user-plus', color: '#10B981', count: 0 },
    { id: 'absence', name: 'Justifications', icon: 'pi pi-calendar', color: '#8B5CF6', count: 0 },
    { id: 'payment', name: 'Paiements', icon: 'pi pi-wallet', color: '#F59E0B', count: 0 },
  ]);

  items = signal<any[]>([]);

  ngOnInit() {
    this.loadValidations();
  }

  loadValidations() {
    this.isLoading = true;
    this.http.get<any>(`${environment.apiUrl}/dashboard/direction/validations`).subscribe({
      next: (res) => {
        const data = res.data || [];
        this.items.set(data);
        
        // Update counts
        this.categories.update(cats => cats.map(c => ({
          ...c,
          count: c.id === 'all' ? data.length : data.filter((i: any) => i.type === c.id).length
        })));
        
        this.isLoading = false;
      },
      error: () => this.isLoading = false
    });
  }

  pendingTotal = () => this.items().length;

  filteredItems = () => {
    if (this.selectedCategory === 'all') return this.items();
    return this.items().filter(i => i.type === this.selectedCategory);
  };

  getCategoryName = () => this.categories().find(c => c.id === this.selectedCategory)?.name || 'Tous';

  getItemIcon(type: string) {
    const icons: Record<string, string> = { bulletin: 'pi pi-file', inscription: 'pi pi-user-plus', absence: 'pi pi-calendar', payment: 'pi pi-wallet' };
    return icons[type] || 'pi pi-circle';
  }

  getTypeName(type: string) {
    const names: Record<string, string> = { bulletin: 'Bulletin', inscription: 'Inscription', absence: 'Justification', payment: 'Paiement' };
    return names[type] || type;
  }

  viewItem(item: any) { this.selectedItem = item; }

  approveItem(item: any) {
    this.sendValidation(item, 'validee');
  }

  rejectItem(item: any) {
    if (confirm('Rejeter cette validation ?')) {
      this.sendValidation(item, 'refusee');
    }
  }

  private sendValidation(item: any, status: string) {
    this.http.post(`${environment.apiUrl}/dashboard/direction/validations/${item.id}`, {
      status: status,
      cycle: item.cycle,
      type: item.type
    }).subscribe({
      next: () => {
        alert(status === 'validee' ? 'Approuvé !' : 'Rejeté !');
        this.selectedItem = null;
        this.loadValidations();
      },
      error: (err) => alert('Erreur: ' + (err.error?.message || err.message))
    });
  }
}
