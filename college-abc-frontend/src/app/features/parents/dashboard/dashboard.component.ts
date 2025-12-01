import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { GradeService, ReportCard } from '../../../core/services/grade.service';
import { AttendanceService, Absence } from '../../../core/services/attendance.service';
import { ScheduleService, CourseSlot } from '../../../core/services/schedule.service';
import { DocumentService, AdminDoc } from '../../../core/services/document.service';
import { Observable } from 'rxjs';

@Component({
  selector: 'app-parent-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink],
  template: `
    <div class="min-h-screen bg-gray-50 flex flex-col">
      <!-- Top Bar -->
      <header class="bg-primary text-white shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
          <div class="flex items-center gap-3">
            <i class="pi pi-shield text-2xl text-secondary"></i>
            <span class="font-serif font-bold text-xl">Espace Parents</span>
          </div>
          <div class="flex items-center gap-4">
            <div class="text-right hidden md:block" *ngIf="authService.currentUser() as user">
              <div class="font-bold">{{ user.name }}</div>
              <div class="text-xs text-blue-200" *ngIf="user.children?.length">
                 Parents de {{ user.children![0].firstName }} ({{ user.children![0].class }})
              </div>
            </div>
            <div class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center">
               <i class="pi pi-user"></i>
            </div>
            <button (click)="logout()" class="text-sm bg-red-500 hover:bg-red-600 px-3 py-1 rounded transition-colors">
               <i class="pi pi-power-off"></i>
            </button>
          </div>
        </div>
      </header>

      <div class="flex-1 container mx-auto px-6 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
          
          <!-- Sidebar Navigation -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden sticky top-8">
               <nav class="flex flex-col">
                 <button (click)="activeTab = 'notes'" [class]="activeTab === 'notes' ? 'bg-blue-50 text-primary border-l-4 border-primary' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'" class="flex items-center gap-3 px-6 py-4 transition-all text-left font-medium">
                    <i class="pi pi-book"></i> Notes & Bulletins
                 </button>
                 <button (click)="activeTab = 'absences'" [class]="activeTab === 'absences' ? 'bg-blue-50 text-primary border-l-4 border-primary' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'" class="flex items-center gap-3 px-6 py-4 transition-all text-left font-medium">
                    <i class="pi pi-calendar-times"></i> Absences & Retards
                 </button>
                 <button (click)="activeTab = 'planning'" [class]="activeTab === 'planning' ? 'bg-blue-50 text-primary border-l-4 border-primary' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'" class="flex items-center gap-3 px-6 py-4 transition-all text-left font-medium">
                    <i class="pi pi-calendar"></i> Emploi du Temps
                 </button>
                 <button (click)="activeTab = 'documents'" [class]="activeTab === 'documents' ? 'bg-blue-50 text-primary border-l-4 border-primary' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'" class="flex items-center gap-3 px-6 py-4 transition-all text-left font-medium">
                    <i class="pi pi-folder-open"></i> Documents Administratifs
                 </button>
               </nav>
            </div>
            
            <div class="mt-6 bg-blue-50 p-4 rounded-xl border border-blue-100">
               <h4 class="font-bold text-primary mb-2 flex items-center gap-2"><i class="pi pi-info-circle"></i> Note Importante</h4>
               <p class="text-sm text-gray-600">
                  Cet espace est réservé à la consultation. Pour toute modification ou réclamation, veuillez contacter le secrétariat.
               </p>
            </div>
          </div>

          <!-- Main Content -->
          <div class="lg:col-span-3">
            
            <!-- Notes Section -->
            <div *ngIf="activeTab === 'notes'" class="space-y-6 animate-fade-in">
               <h2 class="text-2xl font-bold text-gray-800 mb-4">Relevé de Notes - Trimestre 1</h2>
               
               <ng-container *ngIf="reportCard$ | async as report">
                   <!-- Summary Cards -->
                   <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-green-500">
                         <div class="text-gray-500 text-sm mb-1">Moyenne Générale</div>
                         <div class="text-3xl font-bold text-gray-800">{{ report.generalAverage }}<span class="text-lg text-gray-400">/20</span></div>
                      </div>
                      <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                         <div class="text-gray-500 text-sm mb-1">Rang</div>
                         <div class="text-3xl font-bold text-gray-800">{{ report.rank }}<span class="text-lg text-gray-400">ème</span></div>
                      </div>
                      <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-purple-500">
                         <div class="text-gray-500 text-sm mb-1">Appréciation</div>
                         <div class="text-lg font-bold text-gray-800">{{ report.appreciation }}</div>
                      </div>
                   </div>

                   <!-- Grades Table -->
                   <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                      <table class="w-full text-left">
                         <thead class="bg-gray-50 border-b">
                            <tr>
                               <th class="px-6 py-4 font-bold text-gray-600">Matière</th>
                               <th class="px-6 py-4 font-bold text-gray-600">Devoirs</th>
                               <th class="px-6 py-4 font-bold text-gray-600">Compo</th>
                               <th class="px-6 py-4 font-bold text-gray-600">Moyenne</th>
                               <th class="px-6 py-4 font-bold text-gray-600">Professeur</th>
                            </tr>
                         </thead>
                         <tbody class="divide-y">
                            <tr *ngFor="let grade of report.grades" class="hover:bg-gray-50">
                               <td class="px-6 py-4 font-medium">{{ grade.subject }}</td>
                               <td class="px-6 py-4 text-gray-600">{{ grade.marks.join(', ') }}</td>
                               <td class="px-6 py-4 font-bold text-primary">{{ grade.average }}</td>
                               <td class="px-6 py-4 font-bold">{{ grade.classAverage }}</td>
                               <td class="px-6 py-4 text-sm text-gray-500">{{ grade.teacher }}</td>
                            </tr>
                         </tbody>
                      </table>
                   </div>
               </ng-container>
            </div>

            <!-- Absences Section -->
            <div *ngIf="activeTab === 'absences'" class="space-y-6 animate-fade-in">
               <h2 class="text-2xl font-bold text-gray-800 mb-4">Suivi des Absences</h2>
               
               <ng-container *ngIf="absences$ | async as absenceData">
                   <div class="bg-white p-6 rounded-xl shadow-sm mb-6 flex items-center gap-6">
                      <div class="w-16 h-16 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 text-2xl font-bold">
                         {{ absenceData.totalHours }}
                      </div>
                      <div>
                         <div class="text-gray-500">Total Absences (Heures)</div>
                         <div class="text-xl font-bold text-gray-800">{{ absenceData.totalHours }} heures justifiées</div>
                      </div>
                   </div>

                   <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                      <table class="w-full text-left">
                         <thead class="bg-gray-50 border-b">
                            <tr>
                               <th class="px-6 py-4 font-bold text-gray-600">Date</th>
                               <th class="px-6 py-4 font-bold text-gray-600">Heure</th>
                               <th class="px-6 py-4 font-bold text-gray-600">Matière</th>
                               <th class="px-6 py-4 font-bold text-gray-600">Motif</th>
                               <th class="px-6 py-4 font-bold text-gray-600">Statut</th>
                            </tr>
                         </thead>
                         <tbody class="divide-y">
                            <tr *ngFor="let abs of absenceData.list" class="hover:bg-gray-50">
                               <td class="px-6 py-4">{{ abs.date }}</td>
                               <td class="px-6 py-4">{{ abs.timeSlot }}</td>
                               <td class="px-6 py-4 font-medium">{{ abs.subject }}</td>
                               <td class="px-6 py-4 text-gray-600">{{ abs.reason }}</td>
                               <td class="px-6 py-4"><span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">{{ abs.status }}</span></td>
                            </tr>
                         </tbody>
                      </table>
                   </div>
               </ng-container>
            </div>

            <!-- Planning Section -->
            <div *ngIf="activeTab === 'planning'" class="space-y-6 animate-fade-in">
               <div class="flex justify-between items-center mb-4">
                  <h2 class="text-2xl font-bold text-gray-800">Emploi du Temps</h2>
                  <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">Semaine du 25 Nov</span>
               </div>
               
               <div class="bg-white rounded-xl shadow-sm p-6 overflow-x-auto">
                  <div class="grid grid-cols-6 gap-4 min-w-[800px]">
                     <!-- Header -->
                     <div class="font-bold text-gray-400 text-center">Heure</div>
                     <div class="font-bold text-gray-800 text-center">Lundi</div>
                     <div class="font-bold text-gray-800 text-center">Mardi</div>
                     <div class="font-bold text-gray-800 text-center">Mercredi</div>
                     <div class="font-bold text-gray-800 text-center">Jeudi</div>
                     <div class="font-bold text-gray-800 text-center">Vendredi</div>

                     <!-- Rows (Simplified for Demo - In real app, we'd map this dynamically) -->
                     <ng-container *ngIf="schedule$ | async as slots">
                         <!-- 07h - 09h -->
                         <div class="text-gray-500 text-sm text-center pt-4">07h - 09h</div>
                         <ng-container *ngFor="let day of ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi']">
                             <div *ngIf="getSlot(slots, day, '07h') as slot; else emptySlot" 
                                  [class]="'bg-' + slot.color + '-100 border-' + slot.color + '-500'"
                                  class="p-3 rounded-lg border-l-4">
                                <div [class]="'text-' + slot.color + '-800'" class="font-bold">{{ slot.subject }}</div>
                                <div [class]="'text-' + slot.color + '-600'" class="text-xs">{{ slot.room }}</div>
                             </div>
                             <ng-template #emptySlot>
                                <div class="bg-gray-50 rounded-lg"></div>
                             </ng-template>
                         </ng-container>

                         <!-- Break -->
                         <div class="col-span-6 bg-gray-50 p-2 text-center text-xs text-gray-400 font-bold uppercase tracking-widest rounded">Récréation</div>

                         <!-- 10h - 12h -->
                         <div class="text-gray-500 text-sm text-center pt-4">10h - 12h</div>
                         <ng-container *ngFor="let day of ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi']">
                             <div *ngIf="getSlot(slots, day, '10h') as slot; else emptySlot" 
                                  [class]="'bg-' + slot.color + '-100 border-' + slot.color + '-500'"
                                  class="p-3 rounded-lg border-l-4">
                                <div [class]="'text-' + slot.color + '-800'" class="font-bold">{{ slot.subject }}</div>
                                <div [class]="'text-' + slot.color + '-600'" class="text-xs">{{ slot.room }}</div>
                             </div>
                             <ng-template #emptySlot>
                                <div class="bg-gray-50 rounded-lg"></div>
                             </ng-template>
                         </ng-container>
                     </ng-container>
                  </div>
               </div>
            </div>

            <!-- Documents Section -->
            <div *ngIf="activeTab === 'documents'" class="space-y-6 animate-fade-in">
               <h2 class="text-2xl font-bold text-gray-800 mb-4">Documents Administratifs</h2>
               
               <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg flex items-start gap-3 mb-6">
                  <i class="pi pi-exclamation-triangle text-yellow-600 mt-1"></i>
                  <div>
                     <h4 class="font-bold text-yellow-800">Service Payant</h4>
                     <p class="text-sm text-yellow-700">Le téléchargement de certains documents officiels peut nécessiter le règlement des frais de scolarité.</p>
                  </div>
               </div>

               <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <ng-container *ngIf="documents$ | async as docs">
                      <div *ngFor="let doc of docs" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow flex justify-between items-center">
                         <div class="flex items-center gap-4">
                            <div [class]="'bg-' + doc.iconColor + '-100 text-' + doc.iconColor + '-500'" class="w-12 h-12 rounded-lg flex items-center justify-center">
                               <i class="pi" [class.pi-file-pdf]="doc.type === 'PDF'" [class.pi-file]="doc.type !== 'PDF'"></i>
                            </div>
                            <div>
                               <h3 class="font-bold text-gray-800">{{ doc.title }}</h3>
                               <p class="text-xs text-gray-500">{{ doc.date }}</p>
                            </div>
                         </div>
                         <button class="w-10 h-10 rounded-full bg-gray-100 hover:bg-primary hover:text-white flex items-center justify-center transition-colors">
                            <i class="pi pi-download"></i>
                         </button>
                      </div>
                  </ng-container>
               </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  `
})
export class ParentDashboardComponent implements OnInit {
  // Onglet actif par défaut
  activeTab: 'notes' | 'absences' | 'planning' | 'documents' = 'notes';

  // Injection des services
  authService = inject(AuthService);
  private gradeService = inject(GradeService);
  private attendanceService = inject(AttendanceService);
  private scheduleService = inject(ScheduleService);
  private documentService = inject(DocumentService);

  // Observables pour les données (Pattern AsyncPipe)
  reportCard$!: Observable<ReportCard>;
  absences$!: Observable<{ totalHours: number, list: Absence[] }>;
  schedule$!: Observable<CourseSlot[]>;
  documents$!: Observable<AdminDoc[]>;

  ngOnInit() {
    // Simulation de l'ID de l'élève connecté (à récupérer via AuthService dans le futur)
    const studentId = 101;
    
    // Vérification de l'authentification (Mock)
    if (!this.authService.currentUser()) {
        this.authService.login({}).subscribe();
    }

    // Initialisation des flux de données
    this.reportCard$ = this.gradeService.getReportCard(studentId, 1);
    this.absences$ = this.attendanceService.getAbsences(studentId);
    this.schedule$ = this.scheduleService.getSchedule(studentId);
    this.documents$ = this.documentService.getDocuments(studentId);
  }

  /**
   * Déconnecte l'utilisateur
   */
  logout() {
      this.authService.logout();
  }

  /**
   * Helper pour récupérer un créneau de cours spécifique dans la grille
   */
  getSlot(slots: CourseSlot[], day: string, time: string): CourseSlot | undefined {
      return slots.find(s => s.day === day && s.startTime === time);
  }
}
