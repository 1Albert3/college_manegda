import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FinanceService } from '../../../../core/services/finance.service';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-fee-settings',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="p-6 max-w-5xl mx-auto">
      <div class="flex items-center justify-between mb-8">
         <div>
            <h2 class="text-2xl font-black text-gray-900 leading-none">Configurations des Frais</h2>
            <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-2">Gestion des grilles tarifaires par cycle et niveau</p>
         </div>
         <button class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 flex items-center gap-2">
            <i class="pi pi-plus"></i>
            Ajouter une structure
         </button>
      </div>

      <div class="space-y-6">
         <!-- Tabs Cycle -->
         <div class="flex items-center gap-2 bg-white p-1.5 rounded-2xl border border-gray-100 shadow-sm w-fit">
            <button *ngFor="let c of cycles" (click)="selectedCycle = c"
                    [class]="selectedCycle === c ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-gray-50'"
                    class="px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
               {{ c }}
            </button>
         </div>

         <!-- Fee structures Grid -->
         <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div *ngFor="let fee of filteredFees" class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden group">
               <div class="p-6 bg-gradient-to-br from-indigo-50/50 to-white">
                  <div class="flex justify-between items-start mb-4">
                     <div>
                        <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">{{ fee.cycle }}</span>
                        <h3 class="text-xl font-black text-gray-900 group-hover:text-indigo-600 transition-colors">{{ fee.niveau }}</h3>
                     </div>
                     <div class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-tighter">Actif</div>
                  </div>
                  
                  <div class="space-y-3 mt-6">
                     <div class="flex justify-between text-xs">
                        <span class="text-gray-400 font-bold uppercase">Scolarité</span>
                        <span class="font-black text-gray-800">{{ fee.scolarite | number }} FCFA</span>
                     </div>
                     <div class="flex justify-between text-xs">
                        <span class="text-gray-400 font-bold uppercase">Inscription</span>
                        <span class="font-black text-gray-800">{{ fee.inscription | number }} FCFA</span>
                     </div>
                     <div class="flex justify-between text-xs" *ngIf="fee.apee">
                        <span class="text-gray-400 font-bold uppercase">APEE</span>
                        <span class="font-black text-gray-800">{{ fee.apee | number }} FCFA</span>
                     </div>
                  </div>

                  <div class="mt-8 pt-6 border-t border-dashed border-gray-200 flex items-center justify-between">
                     <div>
                        <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Total Annuel</div>
                        <div class="text-lg font-black text-indigo-600">{{ fee.total | number }} FCFA</div>
                     </div>
                     <button class="w-10 h-10 rounded-xl bg-gray-50 text-gray-400 hover:bg-indigo-600 hover:text-white transition-all flex items-center justify-center shadow-sm">
                        <i class="pi pi-pencil"></i>
                     </button>
                  </div>
               </div>
            </div>

            <div *ngIf="filteredFees.length === 0" class="bg-white rounded-3xl border-2 border-dashed border-gray-100 p-12 text-center flex flex-col items-center justify-center col-span-full">
               <div class="w-16 h-16 rounded-3xl bg-gray-50 flex items-center justify-center text-gray-200 mb-4">
                  <i class="pi pi-cog text-3xl"></i>
               </div>
               <p class="text-sm font-bold text-gray-400 uppercase tracking-tighter italic">Aucune structure tarifaire configurée pour ce cycle.</p>
            </div>
         </div>
      </div>
    </div>
  `
})
export class FeeSettingsComponent implements OnInit {
  fees: any[] = [];
  cycles = ['Maternelle', 'Primaire', 'Collège', 'Lycée'];
  selectedCycle = 'Collège';

  constructor(private financeService: FinanceService) {}

  ngOnInit() {
    this.loadFees();
  }

  loadFees() {
    this.financeService.getFeeTypes().subscribe({
      next: (res) => {
        this.fees = res;
      },
      error: () => {
          // Fallback demo data
          this.fees = [
              { cycle: 'Collège', niveau: '6ème', inscription: 15000, scolarite: 65000, apee: 5000, total: 85000 },
              { cycle: 'Collège', niveau: '5ème', inscription: 15000, scolarite: 65000, apee: 5000, total: 85000 },
              { cycle: 'Collège', niveau: '4ème', inscription: 15000, scolarite: 65000, apee: 5000, total: 85000 },
              { cycle: 'Collège', niveau: '3ème', inscription: 20000, scolarite: 75000, apee: 5000, total: 100000 },
          ];
      }
    });
  }

  get filteredFees() {
    return this.fees.filter(f => f.cycle.toLowerCase() === this.selectedCycle.toLowerCase());
  }
}
