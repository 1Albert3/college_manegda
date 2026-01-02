import { Component, signal, inject, OnInit } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../../core/services/auth.service';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-admin-layout',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, CommonModule],
  template: `
    <div class="flex h-screen bg-gray-50 font-sans">
      <!-- Sidebar design style SUPINFO (Dark/Clean) -->
      <aside class="bg-[#1e1e2d] text-white flex flex-col transition-all duration-300 shadow-xl z-20" 
             [class.w-64]="!collapsed()"
             [class.w-20]="collapsed()">
        
        <!-- Brand Logo -->
        <div class="h-16 flex items-center justify-center border-b border-gray-700 bg-[#1a1a27]">
          <div *ngIf="!collapsed()" class="flex items-center gap-2 animate-fade-in">
             <div class="w-8 h-8 rounded bg-primary flex items-center justify-center shadow-lg shadow-primary/40">
                <span class="font-bold text-white text-lg">C</span>
             </div>
             <span class="font-bold text-lg tracking-wide text-white">COLLEGE ABC</span>
          </div>
          <div *ngIf="collapsed()" class="flex items-center justify-center w-full">
            <i class="pi pi-shield text-2xl text-primary"></i>
          </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 overflow-y-auto py-6 px-3 scrollbar-hide">
          <ul class="space-y-1">
            <li *ngFor="let item of menuItems">
              <a [routerLink]="item.link" 
                 [routerLinkActiveOptions]="{exact: item.exact === true}"
                 routerLinkActive="bg-primary text-white shadow-lg shadow-primary/30" 
                 class="flex items-center px-3 py-3 text-gray-400 hover:bg-[#2a2a3c] hover:text-gray-100 rounded-lg transition-all duration-200 group mb-1 cursor-pointer">
                
                <div class="w-8 flex justify-center">
                    <i [class]="item.icon + ' text-lg transition-transform group-hover:scale-110'"></i>
                </div>
                
                <span class="ml-3 font-medium text-sm tracking-wide whitespace-nowrap opacity-100 transition-opacity duration-200" 
                      *ngIf="!collapsed()">
                  {{ item.label }}
                </span>
                
                <!-- Tooltip for collapsed state -->
                <div *ngIf="collapsed()" 
                     class="absolute left-16 bg-gray-900 text-white text-xs font-semibold px-3 py-2 rounded shadow-xl opacity-0 group-hover:opacity-100 transition-opacity z-50 whitespace-nowrap pointer-events-none transform translate-x-2 group-hover:translate-x-0 transition-transform">
                  {{ item.label }}
                  <div class="absolute top-1/2 -left-1 -mt-1 border-4 border-transparent border-r-gray-900"></div>
                </div>
              </a>
            </li>
          </ul>
        </nav>

        <!-- User Profile Footer -->
        <div class="p-4 border-t border-gray-700 bg-[#1a1a27]">
          <div class="flex items-center gap-3">
             <div class="relative">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-blue-600 flex items-center justify-center text-white font-bold text-lg shadow-md ring-2 ring-[#2a2a3c]">
                  {{ (currentUser()?.name?.charAt(0) || 'U') | uppercase }}
                </div>
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-[#1a1a27] rounded-full"></div>
             </div>
            
            <div *ngIf="!collapsed()" class="overflow-hidden flex-1 animate-fade-in">
              <div class="font-bold text-sm truncate text-gray-100 leading-tight">{{ currentUser()?.name || 'Utilisateur' }}</div>
              <div class="text-xs text-gray-500 truncate capitalize mt-0.5">{{ currentUser()?.role || 'Rôle inconnu' }}</div>
            </div>

             <button (click)="logout()" *ngIf="!collapsed()" 
                     class="text-gray-500 hover:text-red-400 p-1.5 rounded-full hover:bg-gray-800 transition-colors" 
                     title="Se déconnecter">
                <i class="pi pi-sign-out font-bold"></i>
             </button>
          </div>
        </div>
      </aside>

      <!-- Main Layout Content -->
      <div class="flex-1 flex flex-col overflow-hidden bg-[#f3f4f6] relative">
        
        <!-- Top Header Bar -->
        <header class="h-16 bg-white flex items-center justify-between px-6 z-10 border-b border-gray-200 shadow-sm">
          <div class="flex items-center gap-4">
              <button (click)="toggleSidebar()" class="p-2 -ml-2 text-gray-500 hover:text-primary hover:bg-gray-50 rounded-lg focus:outline-none transition-colors">
                <i class="pi pi-bars text-xl"></i>
              </button>
              <h2 class="text-lg font-semibold text-gray-800 hidden sm:block">Tableau de Bord</h2>
          </div>

          <div class="flex items-center gap-6">
            <div class="hidden md:flex items-center text-sm text-gray-500 bg-gray-50 px-3 py-1.5 rounded-full border border-gray-100">
                <i class="pi pi-calendar mr-2 text-primary"></i>
                <span>Année Scolaire {{ schoolYear() || '...' }}</span>
            </div>

            <div class="flex items-center gap-3 border-l border-gray-100 pl-6">
                <button class="p-2 text-gray-400 hover:text-primary hover:bg-blue-50 rounded-full transition-all relative group">
                  <i class="pi pi-bell text-xl"></i>
                  <span class="absolute top-1.5 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                </button>
                <button class="p-2 text-gray-400 hover:text-primary hover:bg-blue-50 rounded-full transition-all">
                   <i class="pi pi-cog text-xl"></i>
                </button>
            </div>
          </div>
        </header>

        <!-- Dynamic Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 scroll-smooth">
            <div class="max-w-7xl mx-auto">
                <router-outlet></router-outlet>
            </div>
        </main>
      </div>
    </div>
  `,
  styles: [`
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .animate-fade-in {
        animation: fadeIn 0.3s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateX(-10px); }
        to { opacity: 1; transform: translateX(0); }
    }
  `]
})
export class AdminLayoutComponent implements OnInit {
  collapsed = signal(false);
  schoolYear = signal<string | null>(null);
  private authService = inject(AuthService);
  private http = inject(HttpClient);
  
  currentUser = () => this.authService.currentUser();
  menuItems: any[] = [];

  ngOnInit() {
    this.updateMenu();
    this.loadSchoolYear();
  }

  loadSchoolYear() {
    this.http.get<any>(`${environment.apiUrl}/core/school-years/current`).subscribe({
      next: (res) => this.schoolYear.set(res?.data?.name || '2025-2026'),
      error: () => this.schoolYear.set('2025-2026')
    });
  }

  updateMenu() {
    const role = this.currentUser()?.role;

    if (role === 'teacher') {
      this.menuItems = [
        { label: 'Tableau de bord', icon: 'pi pi-home', link: '/admin/dashboard', exact: true },
        { label: 'Mes Classes', icon: 'pi pi-users', link: '/admin/students', exact: false },
        { label: 'Emploi du temps', icon: 'pi pi-calendar', link: '/admin/schedule', exact: false },
        { label: 'Sillabus', icon: 'pi pi-book', link: '/admin/syllabus', exact: false },
        { label: 'Notes & Bulletins', icon: 'pi pi-pencil', link: '/admin/grades', exact: false },
        { label: 'Messagerie', icon: 'pi pi-envelope', link: '/admin/messages', exact: false },
      ];
    } else {
      // Default to Admin menu
      this.menuItems = [
        { label: 'Tableau de Bord', icon: 'pi pi-home', link: '/admin/dashboard', exact: true },
        { label: 'Élèves', icon: 'pi pi-users', link: '/admin/students', exact: false },
        { label: 'Académique', icon: 'pi pi-book', link: '/admin/academic', exact: false },
        { label: 'Finance', icon: 'pi pi-wallet', link: '/admin/finance', exact: false },
        { label: 'RH & Staff', icon: 'pi pi-briefcase', link: '/admin/hr', exact: false },
        { label: 'Collège (Gestion)', icon: 'pi pi-building', link: '/admin/college/classes', exact: false },
        { label: 'Maternelle / Primaire', icon: 'pi pi-star', link: '/admin/mp/classes', exact: false },
        { label: 'Lycée (Gestion)', icon: 'pi pi-graduation-cap', link: '/admin/lycee/classes', exact: false },
        { label: 'Emploi du Temps', icon: 'pi pi-calendar', link: '/admin/schedule', exact: false },
        { label: 'Paramètres', icon: 'pi pi-cog', link: '/admin/settings', exact: false },
      ];
    }
  }

  toggleSidebar() {
    this.collapsed.update(v => !v);
  }

  logout() {
    this.authService.logout().subscribe({
      next: () => {},
      error: () => this.authService.logoutLocal()
    });
  }
}
