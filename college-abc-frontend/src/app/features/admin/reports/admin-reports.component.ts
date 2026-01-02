import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-admin-reports',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Rapports & Statistiques</h1>
          <p class="text-gray-500">Analyses et exports de données</p>
        </div>
        <div class="flex gap-2">
          <select [(ngModel)]="selectedPeriod" class="px-4 py-2 border rounded-lg">
            <option value="week">Cette semaine</option>
            <option value="month">Ce mois</option>
            <option value="trimester">Ce trimestre</option>
            <option value="year">Cette année</option>
          </select>
        </div>
      </div>

      <!-- Quick Reports -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <button *ngFor="let report of quickReports()" (click)="generateReport(report)"
                class="bg-white rounded-xl p-5 text-left hover:shadow-md transition-shadow border hover:border-blue-500">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-3"
               [style.background-color]="report.color + '20'" [style.color]="report.color">
            <i [class]="report.icon + ' text-xl'"></i>
          </div>
          <h3 class="font-semibold text-gray-800">{{ report.name }}</h3>
          <p class="text-sm text-gray-500 mt-1">{{ report.description }}</p>
        </button>
      </div>

      <!-- Key Metrics -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-5 text-white">
          <p class="text-white/80 text-sm">Effectif total</p>
          <p class="text-3xl font-bold">{{ totalStudents() }}</p>
          <p class="text-sm mt-1"><i class="pi pi-arrow-up mr-1"></i>+12 ce mois</p>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-green-500">
          <p class="text-gray-500 text-sm">Taux de réussite</p>
          <p class="text-2xl font-bold text-gray-800">{{ successRate() }}%</p>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-orange-500">
          <p class="text-gray-500 text-sm">Taux d'assiduité</p>
          <p class="text-2xl font-bold text-gray-800">{{ attendanceRate() }}%</p>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-purple-500">
          <p class="text-gray-500 text-sm">Recouvrement</p>
          <p class="text-2xl font-bold text-gray-800">{{ collectionRate() }}%</p>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Performance by Class -->
        <div class="bg-white rounded-xl shadow-sm p-6">
          <h3 class="font-bold text-gray-800 mb-4">Performance par Classe</h3>
          <div class="space-y-4">
            <div *ngFor="let cls of classPerformance()">
              <div class="flex justify-between text-sm mb-1">
                <span>{{ cls.name }}</span>
                <span class="font-semibold">{{ cls.average }}/20</span>
              </div>
              <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full" 
                     [style.width.%]="(cls.average / 20) * 100"
                     [style.background-color]="cls.average >= 12 ? '#10B981' : cls.average >= 10 ? '#F59E0B' : '#EF4444'">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Enrollment Trend -->
        <div class="bg-white rounded-xl shadow-sm p-6">
          <h3 class="font-bold text-gray-800 mb-4">Évolution des Effectifs</h3>
          <div class="flex items-end justify-between h-48 gap-2">
            <div *ngFor="let m of monthlyEnrollment()" class="flex-1 flex flex-col items-center">
              <div class="w-full bg-blue-100 rounded-t relative" [style.height.%]="(m.count / maxEnrollment()) * 100">
                <div class="absolute inset-0 bg-blue-500 rounded-t opacity-80"></div>
              </div>
              <span class="text-xs text-gray-500 mt-2">{{ m.month }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Report Generation Modal -->
      <div *ngIf="showReportModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-full max-w-md mx-4">
          <div class="bg-blue-600 px-6 py-4 rounded-t-2xl">
            <h3 class="text-xl font-bold text-white">Générer un Rapport</h3>
          </div>
          <form (ngSubmit)="downloadReport()" class="p-6 space-y-4">
            <div class="bg-blue-50 p-4 rounded-lg">
              <div class="font-medium text-blue-800">{{ selectedReport?.name }}</div>
              <div class="text-sm text-blue-600">{{ selectedReport?.description }}</div>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Format</label>
              <div class="flex gap-3">
                <label class="flex items-center gap-2"><input type="radio" [(ngModel)]="reportFormat" name="format" value="pdf" class="text-blue-600"> PDF</label>
                <label class="flex items-center gap-2"><input type="radio" [(ngModel)]="reportFormat" name="format" value="excel" class="text-blue-600"> Excel</label>
                <label class="flex items-center gap-2"><input type="radio" [(ngModel)]="reportFormat" name="format" value="csv" class="text-blue-600"> CSV</label>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Date début</label>
                <input type="date" [(ngModel)]="reportStartDate" name="startDate" class="w-full px-4 py-2 border rounded-lg">
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Date fin</label>
                <input type="date" [(ngModel)]="reportEndDate" name="endDate" class="w-full px-4 py-2 border rounded-lg">
              </div>
            </div>
            <div class="flex justify-end gap-3 pt-4">
              <button type="button" (click)="showReportModal = false" class="px-6 py-2 border rounded-lg">Annuler</button>
              <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg">Télécharger</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  `
})
export class AdminReportsComponent {
  selectedPeriod = 'month';
  showReportModal = false;
  selectedReport: any = null;
  reportFormat = 'pdf';
  reportStartDate = '';
  reportEndDate = '';

  totalStudents = signal(485);
  successRate = signal(82);
  attendanceRate = signal(94);
  collectionRate = signal(78);

  quickReports = signal([
    { id: 'students', name: 'Liste des élèves', description: 'Export complet des effectifs', icon: 'pi pi-users', color: '#3B82F6' },
    { id: 'grades', name: 'Relevés de notes', description: 'Notes par classe/période', icon: 'pi pi-chart-bar', color: '#10B981' },
    { id: 'attendance', name: 'Absences', description: 'Statistiques de présence', icon: 'pi pi-calendar', color: '#F59E0B' },
    { id: 'finance', name: 'Finances', description: 'État des paiements', icon: 'pi pi-wallet', color: '#8B5CF6' },
  ]);

  classPerformance = signal([
    { name: '6ème A', average: 13.5 },
    { name: '6ème B', average: 12.8 },
    { name: '5ème A', average: 11.2 },
    { name: '4ème A', average: 14.1 },
    { name: '3ème A', average: 12.0 },
  ]);

  monthlyEnrollment = signal([
    { month: 'Sep', count: 450 },
    { month: 'Oct', count: 465 },
    { month: 'Nov', count: 478 },
    { month: 'Déc', count: 485 },
  ]);

  maxEnrollment = () => Math.max(...this.monthlyEnrollment().map(m => m.count));

  generateReport(report: any) {
    this.selectedReport = report;
    this.showReportModal = true;
  }

  downloadReport() {
    alert(`Téléchargement du rapport "${this.selectedReport.name}" en ${this.reportFormat.toUpperCase()}...`);
    this.showReportModal = false;
  }
}
