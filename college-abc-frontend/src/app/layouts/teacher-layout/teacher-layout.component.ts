import { Component, signal, inject, OnInit, computed } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive, Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../../core/services/auth.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-teacher-layout',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, CommonModule],
  template: `
    <div class="flex h-screen bg-gray-50 font-sans">
      <!-- Sidebar -->
      <aside class="bg-[#1e1e2d] text-white flex flex-col transition-all duration-300 shadow-xl z-20" 
             [class.w-64]="!collapsed()"
             [class.w-20]="collapsed()">
        
        <!-- Brand Logo -->
        <div class="h-16 flex items-center justify-center border-b border-gray-700 bg-[#1a1a27]">
          <div *ngIf="!collapsed()" class="flex items-center gap-2">
             <div class="w-8 h-8 rounded bg-primary flex items-center justify-center shadow-lg">
                <i class="pi pi-book text-white"></i>
             </div>
             <span class="font-bold text-lg text-white">ESPACE PROF</span>
          </div>
          <div *ngIf="collapsed()">
            <i class="pi pi-book text-2xl text-primary"></i>
          </div>
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
                     routerLinkActive="bg-primary text-white shadow-lg" 
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
             <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-blue-600 flex items-center justify-center text-white font-bold">
               {{ (currentUser()?.name?.charAt(0) || 'P') | uppercase }}
             </div>
            <div *ngIf="!collapsed()" class="flex-1 overflow-hidden">
              <div class="font-bold text-sm truncate text-gray-100">{{ currentUser()?.name || 'Enseignant' }}</div>
              <div class="text-xs text-gray-500 capitalize">{{ currentUser()?.role }}</div>
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
              <button (click)="toggleSidebar()" class="p-2 text-gray-500 hover:text-primary hover:bg-gray-50 rounded-lg">
                <i class="pi pi-bars text-xl"></i>
              </button>
              <h1 class="text-lg font-semibold text-gray-800 hidden sm:block">Espace Enseignant</h1>
          </div>
          <div class="flex items-center gap-3">
            <span class="hidden md:flex items-center text-sm text-gray-500 bg-gray-50 px-3 py-1.5 rounded-full">
                <i class="pi pi-calendar mr-2 text-primary"></i> {{ schoolYear() || 'Chargement...' }}
            </span>
            <button class="p-2 text-gray-400 hover:text-primary rounded-full relative">
              <i class="pi pi-bell text-xl"></i>
              <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>
            <button class="p-2 text-gray-400 hover:text-primary rounded-full">
              <i class="pi pi-envelope text-xl"></i>
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
export class TeacherLayoutComponent implements OnInit {
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
        { label: 'Tableau de bord', icon: 'pi pi-home', link: '/teacher/dashboard', exact: true },
      ]
    },
    {
      title: 'Mes Classes',
      items: [
        { label: 'Liste des élèves', icon: 'pi pi-users', link: '/teacher/classes', exact: false },
        { label: 'Saisie des notes', icon: 'pi pi-pencil', link: '/teacher/grades', exact: false },
        { label: 'Saisie des absences', icon: 'pi pi-clock', link: '/teacher/attendance', exact: false },
      ]
    },
    {
      title: 'Pédagogie',
      items: [
        { label: 'Cahier de texte', icon: 'pi pi-book', link: '/teacher/homework', exact: false },
        { label: 'Observations', icon: 'pi pi-comment', link: '/teacher/observations', exact: false },
        { label: 'Ressources', icon: 'pi pi-folder', link: '/teacher/resources', exact: false },
      ]
    },
    {
      title: 'Planning',
      items: [
        { label: 'Mon emploi du temps', icon: 'pi pi-calendar', link: '/teacher/schedule', exact: false },
        { label: 'Conseils de classe', icon: 'pi pi-users', link: '/teacher/councils', exact: false },
      ]
    },
    {
      title: 'Communication',
      items: [
        { label: 'Messagerie', icon: 'pi pi-envelope', link: '/teacher/messages', exact: false },
        { label: 'Rendez-vous parents', icon: 'pi pi-calendar-plus', link: '/teacher/appointments', exact: false },
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
