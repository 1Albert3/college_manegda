import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-admin-finance',
  standalone: true,
  imports: [CommonModule, RouterModule],
  template: `
    <div class="min-h-screen bg-gray-50/50">
      <!-- Finance Navigation Bar -->
      <div class="bg-white border-b border-gray-100 sticky top-0 z-30 shadow-sm">
        <div class="container mx-auto px-6">
          <div class="flex items-center justify-between h-16">
            <div class="flex items-center gap-8">
              <h1 class="text-xl font-black text-gray-900 flex items-center gap-2">
                <i class="pi pi-wallet text-indigo-600"></i>
                <span class="tracking-tight uppercase text-xs">Gestion Financière</span>
              </h1>
              
              <nav class="flex items-center gap-1">
                <a routerLink="./dashboard" routerLinkActive="bg-indigo-50 text-indigo-700 border-indigo-200"
                   class="px-4 py-2 rounded-xl text-sm font-bold text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-all border border-transparent">
                  Vue d'ensemble
                </a>
                <a routerLink="./invoices" routerLinkActive="bg-indigo-50 text-indigo-700 border-indigo-200"
                   class="px-4 py-2 rounded-xl text-sm font-bold text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-all border border-transparent">
                  Factures
                </a>
                <a routerLink="./payments" routerLinkActive="bg-indigo-50 text-indigo-700 border-indigo-200"
                   class="px-4 py-2 rounded-xl text-sm font-bold text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-all border border-transparent">
                  Paiements
                </a>
                <a routerLink="./settings" routerLinkActive="bg-indigo-50 text-indigo-700 border-indigo-200"
                   class="px-4 py-2 rounded-xl text-sm font-bold text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-all border border-transparent">
                  Configurations
                </a>
              </nav>
            </div>

            <div class="flex items-center gap-3">
               <button class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">
                  Rapport Journalier
               </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Module Content -->
      <div class="animate-in fade-in duration-500">
        <router-outlet></router-outlet>
      </div>
    </div>
  `
})
export class AdminFinanceComponent {}
