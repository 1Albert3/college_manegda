import { Component, signal, inject } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive, Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-accounting-layout',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, CommonModule],
  template: `
    <div class="flex h-screen bg-gray-50 font-sans">
      <!-- Sidebar -->
      <aside class="bg-gradient-to-b from-emerald-800 to-emerald-900 text-white flex flex-col transition-all duration-300 shadow-xl z-20" 
             [class.w-64]="!collapsed()"
             [class.w-20]="collapsed()">
        
        <!-- Brand Logo -->
        <div class="h-16 flex items-center justify-center border-b border-white/10">
          <div *ngIf="!collapsed()" class="flex items-center gap-2">
             <div class="w-8 h-8 rounded bg-white/20 flex items-center justify-center">
                <i class="pi pi-wallet text-white"></i>
             </div>
             <span class="font-bold text-lg text-white">COMPTABILITÉ</span>
          </div>
          <div *ngIf="collapsed()">
            <i class="pi pi-wallet text-2xl text-white"></i>
          </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 scrollbar-hide">
          <ng-container *ngFor="let section of menuSections">
            <div class="mb-6">
              <div *ngIf="!collapsed()" class="text-xs text-white/50 uppercase tracking-wider px-3 mb-2">
                {{ section.title }}
              </div>
              <ul class="space-y-1">
                <li *ngFor="let item of section.items">
                  <a [routerLink]="item.link" 
                     [routerLinkActiveOptions]="{exact: item.exact}"
                     routerLinkActive="bg-white/20 text-white shadow-lg" 
                     class="flex items-center px-3 py-2.5 text-white/70 hover:bg-white/10 hover:text-white rounded-lg transition-all group cursor-pointer">
                    <div class="w-8 flex justify-center">
                        <i [class]="item.icon + ' text-lg'"></i>
                    </div>
                    <span class="ml-3 font-medium text-sm" *ngIf="!collapsed()">{{ item.label }}</span>
                    <span *ngIf="item.badge && !collapsed()" 
                          class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                      {{ item.badge }}
                    </span>
                  </a>
                </li>
              </ul>
            </div>
          </ng-container>
        </nav>

        <!-- User Footer -->
        <div class="p-4 border-t border-white/10">
          <div class="flex items-center gap-3">
             <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white font-bold">
               {{ (currentUser()?.name?.charAt(0) || 'C') | uppercase }}
             </div>
            <div *ngIf="!collapsed()" class="flex-1 overflow-hidden">
              <div class="font-bold text-sm truncate text-white">{{ currentUser()?.name || 'Comptable' }}</div>
              <div class="text-xs text-white/50">Comptabilité</div>
            </div>
             <button (click)="logout()" *ngIf="!collapsed()" class="text-white/50 hover:text-red-400 p-1.5 rounded-full hover:bg-white/10">
                <i class="pi pi-sign-out"></i>
             </button>
          </div>
        </div>
      </aside>

      <!-- Main Content -->
      <div class="flex-1 flex flex-col overflow-hidden bg-[#f3f4f6]">
        <!-- Header -->
        <header class="h-16 bg-white flex items-center justify-between px-6 border-b border-gray-200 shadow-sm">
          <div class="flex items-center gap-4">
              <button (click)="toggleSidebar()" class="p-2 text-gray-500 hover:text-emerald-600 hover:bg-gray-50 rounded-lg">
                <i class="pi pi-bars text-xl"></i>
              </button>
              <h1 class="text-lg font-semibold text-gray-800 hidden sm:block">Espace Comptabilité</h1>
          </div>
          <div class="flex items-center gap-3">
            <span class="hidden md:flex items-center text-sm text-gray-500 bg-gray-50 px-3 py-1.5 rounded-full">
                <i class="pi pi-calendar mr-2 text-emerald-600"></i> 2024-2025
            </span>
            <button class="p-2 text-gray-400 hover:text-emerald-600 rounded-full relative">
              <i class="pi pi-bell text-xl"></i>
              <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>
          </div>
        </header>

        <!-- Content -->
        <main class="flex-1 overflow-auto p-6">
            <div class="max-w-7xl mx-auto">
                <router-outlet></router-outlet>
            </div>
        </main>
      </div>
    </div>
  `,
  styles: [`
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
  `]
})
export class AccountingLayoutComponent {
  collapsed = signal(false);
  
  private authService = inject(AuthService);
  private router = inject(Router);
  
  currentUser = () => this.authService.currentUser();

  menuSections = [
    {
      title: 'Principal',
      items: [
        { label: 'Tableau de bord', icon: 'pi pi-home', link: '/accounting/dashboard', exact: true },
      ]
    },
    {
      title: 'Paiements',
      items: [
        { label: 'Suivi paiements', icon: 'pi pi-money-bill', link: '/accounting/payments', exact: false, badge: 8 },
        { label: 'Validation', icon: 'pi pi-check-circle', link: '/accounting/validation', exact: false },
        { label: 'Historique', icon: 'pi pi-history', link: '/accounting/history', exact: false },
      ]
    },
    {
      title: 'Factures',
      items: [
        { label: 'Gestion factures', icon: 'pi pi-file', link: '/accounting/invoices', exact: false },
        { label: 'Impayés', icon: 'pi pi-exclamation-triangle', link: '/accounting/unpaid', exact: false, badge: 12 },
        { label: 'Relances', icon: 'pi pi-envelope', link: '/accounting/reminders', exact: false },
      ]
    },
    {
      title: 'Finance',
      items: [
        { label: 'Budget', icon: 'pi pi-chart-pie', link: '/accounting/budget', exact: false },
        { label: 'Rapports', icon: 'pi pi-chart-bar', link: '/accounting/reports', exact: false },
        { label: 'Bourses & Aides', icon: 'pi pi-heart', link: '/accounting/scholarships', exact: false },
      ]
    },
    {
      title: 'Communication',
      items: [
        { label: 'Messagerie', icon: 'pi pi-envelope', link: '/accounting/messages', exact: false },
      ]
    }
  ];

  toggleSidebar() {
    this.collapsed.update(v => !v);
  }

  logout() {
    this.authService.logout();
  }
}
