import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-secretary-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink],
  template: `
    <div class="space-y-6">
      
      <!-- Loading State -->
      <div *ngIf="isLoading()" class="text-center py-12">
        <i class="pi pi-spin pi-spinner text-4xl text-teal-600"></i>
        <p class="mt-4 text-gray-500">Chargement des données...</p>
      </div>
      
      <!-- Error State -->
      <div *ngIf="error()" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
        <i class="pi pi-exclamation-triangle text-4xl text-red-500"></i>
        <p class="mt-4 text-red-700">{{ error() }}</p>
        <button (click)="loadData()" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Réessayer
        </button>
      </div>

      <ng-container *ngIf="!isLoading() && !error()">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-teal-600 to-cyan-600 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
          <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
          <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h1 class="text-2xl md:text-3xl font-bold mb-2">Bonjour ! 📋</h1>
              <p class="text-teal-100">
                {{ stats().pendingEnrollments }} nouvelles inscriptions à traiter • {{ stats().documentsPending }} documents en attente
              </p>
            </div>
            <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 border border-white/20 text-center">
              <p class="text-[10px] uppercase font-bold tracking-widest text-teal-200 mb-1">Année Scolaire</p>
              <p class="text-xl font-black">{{ stats().schoolYearName || '2025-2026' }}</p>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          
          <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-teal-500 hover:shadow-md transition-shadow cursor-pointer" routerLink="/secretary/enrollments">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-500 text-sm font-medium">Inscriptions en attente</p>
                <p class="text-3xl font-bold text-gray-800 mt-1">{{ stats().pendingEnrollments }}</p>
                <p class="text-xs text-teal-600 mt-1">{{ stats().todayEnrollments }} aujourd'hui</p>
              </div>
              <div class="w-14 h-14 rounded-full bg-teal-100 flex items-center justify-center">
                <i class="pi pi-user-plus text-2xl text-teal-600"></i>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500 hover:shadow-md transition-shadow cursor-pointer" routerLink="/secretary/enrollments">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-500 text-sm font-medium">Élèves Inscrits</p>
                <p class="text-3xl font-bold text-gray-800 mt-1">{{ stats().totalStudents }}</p>
                <p class="text-xs text-blue-600 mt-1">{{ stats().activeClasses }} classes</p>
              </div>
              <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">
                <i class="pi pi-users text-2xl text-blue-600"></i>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500 hover:shadow-md transition-shadow cursor-pointer" routerLink="/secretary/documents">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-500 text-sm font-medium">Documents à générer</p>
                <p class="text-3xl font-bold text-gray-800 mt-1">{{ stats().documentsPending }}</p>
                <p class="text-xs text-orange-600 mt-1">Certificats, attestations</p>
              </div>
              <div class="w-14 h-14 rounded-full bg-orange-100 flex items-center justify-center">
                <i class="pi pi-file text-2xl text-orange-600"></i>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500 hover:shadow-md transition-shadow cursor-pointer" routerLink="/secretary/payments">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-500 text-sm font-medium">Paiements du jour</p>
                <p class="text-3xl font-bold text-gray-800 mt-1">{{ stats().todayPayments | number }}</p>
                <p class="text-xs text-green-600 mt-1">FCFA</p>
              </div>
              <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">
                <i class="pi pi-money-bill text-2xl text-green-600"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          
          <!-- Left Column -->
          <div class="lg:col-span-2 space-y-6">
            
            <!-- Recent Enrollments -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
              <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-6 py-4 flex items-center justify-between">
                <h2 class="text-white font-bold flex items-center gap-2">
                  <i class="pi pi-user-plus"></i>
                  Inscriptions Récentes
                </h2>
                <a routerLink="/secretary/enrollments" class="text-white/80 hover:text-white text-sm">
                  Voir tout →
                </a>
              </div>
              <div class="divide-y divide-gray-100">
                <div *ngIf="recentEnrollments().length === 0" class="p-8 text-center text-gray-500">
                  <i class="pi pi-inbox text-4xl text-gray-300"></i>
                  <p class="mt-2">Aucune inscription récente</p>
                </div>
                <div *ngFor="let enrollment of recentEnrollments()" 
                     class="p-4 flex items-center gap-4 hover:bg-gray-50">
                  <div class="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center text-teal-600 font-bold">
                    {{ enrollment.name?.charAt(0) || '?' }}
                  </div>
                  <div class="flex-1">
                    <div class="font-medium text-gray-800">{{ enrollment.name }}</div>
                    <div class="text-sm text-gray-500">{{ enrollment.class }} • {{ enrollment.date }}</div>
                  </div>
                  <span class="px-2 py-1 rounded-full text-xs font-medium"
                        [ngClass]="{
                          'bg-yellow-100 text-yellow-700': enrollment.status === 'pending',
                          'bg-green-100 text-green-700': enrollment.status === 'approved' || enrollment.status === 'active',
                          'bg-blue-100 text-blue-700': enrollment.status === 'processing'
                        }">
                    {{ getStatusLabel(enrollment.status) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Class Overview -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
              <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
                <h2 class="text-white font-bold flex items-center gap-2">
                  <i class="pi pi-sitemap"></i>
                  Répartition par Classe
                </h2>
              </div>
              <div class="p-4 grid grid-cols-2 md:grid-cols-4 gap-3">
                <div *ngIf="classDistribution().length === 0" class="col-span-4 text-center py-4 text-gray-500">
                  Aucune classe configurée
                </div>
                <div *ngFor="let cls of classDistribution()" 
                     class="border border-gray-200 rounded-lg p-3 text-center hover:border-teal-500 cursor-pointer">
                  <div class="text-lg font-bold text-gray-800">{{ cls.name }}</div>
                  <div class="text-2xl font-bold text-teal-600">{{ cls.count }}</div>
                  <div class="text-xs text-gray-500">/ {{ cls.capacity }}</div>
                  <div class="h-1.5 bg-gray-200 rounded-full mt-2 overflow-hidden">
                    <div class="h-full bg-teal-500 rounded-full" [style.width.%]="(cls.count / cls.capacity) * 100"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div class="space-y-6">
            
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm p-5">
              <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="pi pi-bolt text-teal-600"></i>
                Actions Rapides
              </h3>
              <div class="space-y-2">
                <a routerLink="/secretary/enrollments" [queryParams]="{action: 'new'}" 
                   class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-teal-500 hover:bg-teal-50 transition-all">
                  <i class="pi pi-user-plus text-teal-500"></i>
                  <span class="text-sm font-medium">Nouvelle inscription</span>
                </a>
                <a routerLink="/secretary/documents" 
                   class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-teal-500 hover:bg-teal-50 transition-all">
                  <i class="pi pi-file text-blue-500"></i>
                  <span class="text-sm font-medium">Générer un document</span>
                </a>
                <a routerLink="/secretary/classes" 
                   class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-teal-500 hover:bg-teal-50 transition-all">
                  <i class="pi pi-sitemap text-purple-500"></i>
                  <span class="text-sm font-medium">Affecter à une classe</span>
                </a>
                <a routerLink="/admin/grades/bulletins" 
                   class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-teal-500 hover:bg-teal-50 transition-all">
                  <i class="pi pi-file-pdf text-red-500"></i>
                  <span class="text-sm font-medium">Générer des bulletins</span>
                </a>
              </div>
            </div>

            <!-- Pending Documents -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
              <div class="bg-gray-800 px-4 py-3">
                <h3 class="text-white font-bold flex items-center gap-2">
                  <i class="pi pi-file-pdf"></i>
                  Documents en Attente
                </h3>
              </div>
              <div class="divide-y divide-gray-100">
                <div *ngIf="pendingDocuments().length === 0" class="p-4 text-center text-gray-500">
                  Aucun document en attente
                </div>
                <div *ngFor="let doc of pendingDocuments()" class="p-4 hover:bg-gray-50 cursor-pointer">
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="font-medium text-gray-800 text-sm">{{ doc.type }}</div>
                      <div class="text-xs text-gray-500">{{ doc.student }}</div>
                    </div>
                    <button class="px-2 py-1 bg-teal-100 text-teal-700 rounded text-xs hover:bg-teal-200">
                      Générer
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Upcoming Exams -->
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl shadow-sm p-5 border border-purple-100">
              <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                  <i class="pi pi-pencil text-purple-600"></i>
                </div>
                <div>
                  <h3 class="font-bold text-gray-800">Examens à venir</h3>
                  <p class="text-xs text-gray-500">{{ stats().upcomingExams }} à préparer</p>
                </div>
              </div>
              <a routerLink="/secretary/exams" 
                 class="block w-full py-2 bg-purple-600 hover:bg-purple-700 text-white text-center rounded-lg transition-colors text-sm font-medium">
                Gérer les examens
              </a>
            </div>
          </div>
        </div>
      </ng-container>
    </div>
  `
})
export class SecretaryDashboardComponent implements OnInit {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl || 'http://localhost:8000/api';

  isLoading = signal(true);
  error = signal<string | null>(null);
  
  stats = signal({
    schoolYearName: '',
    pendingEnrollments: 0,
    todayEnrollments: 0,
    totalStudents: 0,
    activeClasses: 0,
    documentsPending: 0,
    todayPayments: 0,
    upcomingExams: 0
  });

  recentEnrollments = signal<any[]>([]);
  classDistribution = signal<any[]>([]);
  pendingDocuments = signal<any[]>([]);

  ngOnInit() {
    this.loadData();
  }

  loadData() {
    this.isLoading.set(true);
    this.error.set(null);

    // Load dashboard data from backend
    this.http.get<any>(`${this.apiUrl}/dashboard/secretary`).subscribe({
      next: (response) => {
        if (response.success !== false) {
          const data = response.data || response;
          
          this.stats.set({
            schoolYearName: data.school_year?.name || '',
            pendingEnrollments: data.overview?.pending_enrollments ?? data.enrollments?.pending ?? 0,
            todayEnrollments: data.overview?.today_enrollments ?? 0,
            totalStudents: data.overview?.total_students ?? 0,
            activeClasses: data.classes?.total ?? data.overview?.total_classes ?? 0,
            documentsPending: 0, 
            todayPayments: data.overview?.today_payments ?? 0,
            upcomingExams: 0
          });

          // Map real recent activity
          const activity = data.recent_activity || [];
          const enrollments = activity.map((e: any) => ({
             name: e.student_name || e.message,
             class: e.class_name || '',
             date: e.date ? new Date(e.date).toLocaleDateString('fr-FR') : '',
             status: e.status || 'pending'
          }));
          this.recentEnrollments.set(enrollments);
          
          // Map real class distribution
          const distribution = data.classes?.distribution || [];
          this.classDistribution.set(distribution);
          
          this.pendingDocuments.set([]);
        }
        this.isLoading.set(false);
      },
      error: (err) => {
        console.error('Error loading dashboard data:', err);
        this.error.set('Impossible de charger les données du tableau de bord. Vérifiez votre connexion.');
        this.isLoading.set(false);
      }
    });
  }

  loadFallbackData() {
    // Try direction dashboard as fallback
    this.http.get<any>(`${this.apiUrl}/dashboard/direction`).subscribe({
      next: (response) => {
        if (response.success !== false) {
          const data = response.data || response;
          
          this.stats.set({
            schoolYearName: data.school_year?.name || '',
            pendingEnrollments: data.overview?.pending_enrollments ?? 0,
            todayEnrollments: data.overview?.today_enrollments ?? 0,
            totalStudents: data.overview?.total_students ?? 0,
            activeClasses: data.overview?.active_classes ?? 0,
            documentsPending: 0,
            todayPayments: data.finance?.today_payments ?? 0,
            upcomingExams: 0
          });

          // Map enrollments
          const enrollments = data.recent_enrollments || data.recentActivity || [];
          this.recentEnrollments.set(enrollments.slice(0, 5).map((e: any) => ({
            name: e.student_name || e.name || 'N/A',
            class: e.class_name || e.class || 'N/A',
            date: e.date || new Date().toLocaleDateString('fr-FR'),
            status: e.status || 'active'
          })));

          // Map classes
          const classes = data.class_stats || data.classDistribution || [];
          this.classDistribution.set(classes.map((c: any) => ({
            name: c.name || c.level || 'N/A',
            count: c.count || c.students || 0,
            capacity: c.capacity || 35
          })));
        }
        this.isLoading.set(false);
      },
      error: (fallbackErr) => {
        console.error('Fallback also failed:', fallbackErr);
        this.error.set('Impossible de charger les données. Vérifiez votre connexion.');
        this.isLoading.set(false);
      }
    });
  }

  getStatusLabel(status: string): string {
    const labels: Record<string, string> = {
      'pending': 'En attente',
      'processing': 'En cours',
      'approved': 'Validée',
      'active': 'Actif'
    };
    return labels[status] || status;
  }
}
