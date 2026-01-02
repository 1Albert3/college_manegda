import { Component, signal, inject, OnInit, computed } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive, Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-parent-layout',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, CommonModule, FormsModule],
  template: `
    <div class="flex h-screen bg-gray-50 font-sans">
      <!-- Sidebar -->
      <aside class="bg-[#1e1e2d] text-white flex flex-col transition-all duration-300 shadow-xl z-20" 
             [class.w-72]="!collapsed()"
             [class.w-20]="collapsed()">
        
        <!-- Brand Logo -->
        <div class="h-16 flex items-center justify-center border-b border-gray-700 bg-[#1a1a27]">
          <div *ngIf="!collapsed()" class="flex items-center gap-2">
             <div class="w-8 h-8 rounded bg-secondary flex items-center justify-center shadow-lg">
                <i class="pi pi-users text-white"></i>
             </div>
             <span class="font-bold text-lg text-white">ESPACE PARENTS</span>
          </div>
          <div *ngIf="collapsed()" class="flex items-center justify-center w-full">
            <i class="pi pi-users text-2xl text-secondary"></i>
          </div>
        </div>

        <!-- Child Selector -->
        <div *ngIf="!collapsed() && children().length > 1" class="px-3 py-3 border-b border-gray-700">
          <label class="text-xs text-gray-500 uppercase tracking-wide mb-2 block">Enfant sélectionné</label>
          <select [ngModel]="selectedChildId()" 
                  (ngModelChange)="selectChild($event)"
                  class="w-full bg-[#2a2a3c] text-white text-sm rounded-lg px-3 py-2 border border-gray-600">
            <option *ngFor="let child of children()" [ngValue]="child.id">
              {{ child.firstName }} {{ child.lastName }} - {{ child.class }}
            </option>
          </select>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 scrollbar-hide">
          <ng-container *ngFor="let section of menuSections">
            <div class="mb-6">
              <div *ngIf="!collapsed()" class="text-xs text-gray-500 uppercase tracking-wider px-3 mb-2">
                {{ section.title }}
              </div>
              <ul class="space-y-1">
                <li *ngFor="let item of section.items">
                  <a [routerLink]="item.link" 
                     [routerLinkActiveOptions]="{exact: item.exact}"
                     routerLinkActive="bg-secondary text-white shadow-lg" 
                     class="flex items-center px-3 py-2.5 text-gray-400 hover:bg-[#2a2a3c] hover:text-gray-100 rounded-lg transition-all group cursor-pointer">
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
        <div class="p-4 border-t border-gray-700 bg-[#1a1a27]">
          <div class="flex items-center gap-3">
             <div class="w-10 h-10 rounded-full bg-gradient-to-br from-secondary to-amber-600 flex items-center justify-center text-white font-bold">
               {{ (currentUser()?.name?.charAt(0) || 'P') | uppercase }}
             </div>
            <div *ngIf="!collapsed()" class="flex-1 overflow-hidden">
              <div class="font-bold text-sm truncate text-gray-100">{{ currentUser()?.name || 'Parent' }}</div>
              <div class="text-xs text-gray-500">{{ children().length }} enfant(s)</div>
            </div>
             <button (click)="logout()" *ngIf="!collapsed()" class="text-gray-500 hover:text-red-400 p-1.5 rounded-full hover:bg-gray-800">
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
              <button (click)="toggleSidebar()" class="p-2 text-gray-500 hover:text-secondary hover:bg-gray-50 rounded-lg">
                <i class="pi pi-bars text-xl"></i>
              </button>
              <div class="flex items-center gap-3" *ngIf="selectedChild() as child">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-blue-600 flex items-center justify-center text-white font-bold shadow-md">
                  {{ child.firstName?.charAt(0) }}{{ child.lastName?.charAt(0) }}
                </div>
                <div class="hidden sm:block">
                  <div class="font-semibold text-gray-800">{{ child.firstName }} {{ child.lastName }}</div>
                  <div class="text-xs text-gray-500">{{ child.class }} • {{ child.matricule }}</div>
                </div>
              </div>
          </div>
          <div class="flex items-center gap-3">
            <span class="hidden md:flex items-center text-sm text-gray-500 bg-gray-50 px-3 py-1.5 rounded-full">
                <i class="pi pi-calendar mr-2 text-secondary"></i> 2024-2025
            </span>
            <button class="p-2 text-gray-400 hover:text-secondary rounded-full relative">
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
export class ParentLayoutComponent implements OnInit {
  collapsed = signal(false);
  selectedChildId = signal<string | null>(null);
  
  private authService = inject(AuthService);
  private router = inject(Router);
  
  currentUser = () => this.authService.currentUser();
  children = computed(() => this.currentUser()?.children || []);
  
  selectedChild = computed(() => {
    const kids = this.children();
    const id = this.selectedChildId();
    if (!kids.length) return null;
    return kids.find((c: any) => c.id === id) || kids[0];
  });


  menuSections = [
    {
      title: 'Principal',
      items: [
        { label: 'Tableau de bord', icon: 'pi pi-home', link: '/parents/dashboard', exact: true },
      ]
    },
    {
      title: 'Suivi Académique',
      items: [
        { label: 'Notes & Moyennes', icon: 'pi pi-chart-bar', link: '/parents/grades', exact: false },
        { label: 'Bulletins', icon: 'pi pi-file-pdf', link: '/parents/bulletins', exact: false },
        { label: 'Cahier de texte', icon: 'pi pi-book', link: '/parents/homework', exact: false },
      ]
    },
    {
      title: 'Vie Scolaire',
      items: [
        { label: 'Absences & Retards', icon: 'pi pi-clock', link: '/parents/attendance', exact: false },
        { label: 'Emploi du temps', icon: 'pi pi-calendar', link: '/parents/schedule', exact: false },
      ]
    },
    {
      title: 'Communication',
      items: [
        { label: 'Messagerie', icon: 'pi pi-envelope', link: '/parents/messages', badge: 3, exact: false },
        { label: 'Rendez-vous', icon: 'pi pi-calendar-plus', link: '/parents/appointments', exact: false },
      ]
    },
    {
      title: 'Administratif',
      items: [
        { label: 'Factures', icon: 'pi pi-file', link: '/parents/invoices', exact: false },
        { label: 'Paiements', icon: 'pi pi-credit-card', link: '/parents/payments', exact: false },
        { label: 'Documents', icon: 'pi pi-folder', link: '/parents/documents', exact: false },
      ]
    }
  ];

  ngOnInit() {
    const kids = this.children();
    if (kids.length > 0) {
      this.selectedChildId.set(kids[0].id);
    }
  }

  selectChild(id: string) {
    this.selectedChildId.set(id);
  }


  toggleSidebar() {
    this.collapsed.update(v => !v);
  }

  logout() {
    this.authService.logout();
  }
}
