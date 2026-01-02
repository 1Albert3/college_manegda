import { Component, signal, inject, OnInit } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive, Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../../core/services/auth.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-secretary-layout',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, CommonModule],
  template: `
    <div class="flex h-screen bg-gray-50 font-sans">
      <!-- Sidebar -->
      <aside class="bg-gradient-to-b from-teal-800 to-teal-900 text-white flex flex-col transition-all duration-300 shadow-xl z-20" 
             [class.w-64]="!collapsed()"
             [class.w-20]="collapsed()">
        
        <!-- Brand Logo -->
        <div class="h-16 flex items-center justify-center border-b border-white/10">
          <div *ngIf="!collapsed()" class="flex items-center gap-2">
             <div class="w-8 h-8 rounded bg-white/20 flex items-center justify-center">
                <i class="pi pi-clipboard text-white"></i>
             </div>
             <span class="font-bold text-lg text-white">SECRÉTARIAT</span>
          </div>
          <div *ngIf="collapsed()">
            <i class="pi pi-clipboard text-2xl text-white"></i>
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
                  </a>
                </li>
              </ul>
            </div>
          </ng-container>
        </nav>

        <!-- User Footer -->
        <div class="p-4 border-t border-white/10">
          <div class="flex items-center gap-3">
             <div class="w-10 h-10 rounded-full bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center text-white font-bold">
               {{ (currentUser()?.name?.charAt(0) || 'S') | uppercase }}
             </div>
            <div *ngIf="!collapsed()" class="flex-1 overflow-hidden">
              <div class="font-bold text-sm truncate text-white">{{ currentUser()?.name || 'Secrétaire' }}</div>
              <div class="text-xs text-white/50">Secrétariat</div>
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
              <button (click)="toggleSidebar()" class="p-2 text-gray-500 hover:text-teal-600 hover:bg-gray-50 rounded-lg">
                <i class="pi pi-bars text-xl"></i>
              </button>
              <h1 class="text-lg font-semibold text-gray-800 hidden sm:block">Espace Secrétariat</h1>
          </div>
          <div class="flex items-center gap-3">
            <span class="hidden md:flex items-center text-sm text-gray-500 bg-gray-50 px-3 py-1.5 rounded-full">
                <i class="pi pi-calendar mr-2 text-teal-600"></i> {{ schoolYear() || 'Chargement...' }}
            </span>
            <button class="p-2 text-gray-400 hover:text-teal-600 rounded-full relative">
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
export class SecretaryLayoutComponent implements OnInit {
  collapsed = signal(false);
  schoolYear = signal<string | null>(null);
  
  private authService = inject(AuthService);
  private http = inject(HttpClient);
  private router = inject(Router);
  
  currentUser = () => this.authService.currentUser();

  ngOnInit() {
    this.loadSchoolYear();
  }

  loadSchoolYear() {
    this.http.get<any>(`${environment.apiUrl}/core/school-years/current`).subscribe({
      next: (res) => this.schoolYear.set(res?.data?.name || '2025-2026'),
      error: () => this.schoolYear.set('2025-2026')
    });
  }

  menuSections = [
    {
      title: 'Principal',
      items: [
        { label: 'Tableau de bord', icon: 'pi pi-home', link: '/secretary/dashboard', exact: true },
      ]
    },
    {
      title: 'Inscriptions',
      items: [
        { label: 'Inscriptions', icon: 'pi pi-user-plus', link: '/secretary/enrollments', exact: false },
        { label: 'Dossiers élèves', icon: 'pi pi-folder', link: '/secretary/students', exact: false },
        { label: 'Affectation classes', icon: 'pi pi-sitemap', link: '/secretary/classes', exact: false },
      ]
    },
    {
      title: 'Documents',
      items: [
        { label: 'Génération docs', icon: 'pi pi-file', link: '/secretary/documents', exact: false },
        { label: 'Emplois du temps', icon: 'pi pi-calendar', link: '/secretary/timetable', exact: false },
        { label: 'Examens', icon: 'pi pi-pencil', link: '/secretary/exams', exact: false },
      ]
    },
    {
      title: 'Finance',
      items: [
        { label: 'Facturation', icon: 'pi pi-money-bill', link: '/secretary/invoices', exact: false },
        { label: 'Suivi paiements', icon: 'pi pi-credit-card', link: '/secretary/payments', exact: false },
      ]
    },
    {
      title: 'Communication',
      items: [
        { label: 'Messagerie', icon: 'pi pi-envelope', link: '/secretary/messages', exact: false },
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
