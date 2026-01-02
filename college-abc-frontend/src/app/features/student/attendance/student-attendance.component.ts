import { Component, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-student-attendance',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Mes Absences</h1>
          <p class="text-gray-500">Historique des présences</p>
        </div>
        <select [(ngModel)]="periodFilter" class="px-4 py-2 border rounded-lg">
          <option value="month">Ce mois</option>
          <option value="trimester">Ce trimestre</option>
          <option value="year">Cette année</option>
        </select>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-5 text-white">
          <p class="text-white/80 text-sm">Taux de présence</p>
          <p class="text-3xl font-bold">{{ attendanceRate() }}%</p>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-red-500">
          <p class="text-gray-500 text-sm">Absences</p>
          <p class="text-2xl font-bold text-gray-800">{{ absenceCount() }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-orange-500">
          <p class="text-gray-500 text-sm">Retards</p>
          <p class="text-2xl font-bold text-gray-800">{{ lateCount() }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 border-l-4 border-yellow-500">
          <p class="text-gray-500 text-sm">Non justifiées</p>
          <p class="text-2xl font-bold text-yellow-600">{{ unjustifiedCount() }}</p>
        </div>
      </div>

      <!-- Monthly Calendar View -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold text-gray-800 mb-4">Calendrier du mois</h3>
        <div class="grid grid-cols-7 gap-2">
          <div *ngFor="let day of weekDays" class="text-center text-sm font-medium text-gray-500 py-2">
            {{ day }}
          </div>
          <div *ngFor="let day of calendarDays()" 
               class="aspect-square rounded-lg flex items-center justify-center text-sm cursor-pointer"
               [ngClass]="{
                 'bg-gray-50 text-gray-400': !day.currentMonth,
                 'bg-green-100 text-green-700': day.status === 'present',
                 'bg-red-100 text-red-700': day.status === 'absent',
                 'bg-orange-100 text-orange-700': day.status === 'late',
                 'bg-gray-100': day.status === 'weekend' || !day.status
               }">
            <span *ngIf="day.date">{{ day.date }}</span>
          </div>
        </div>
        <div class="flex gap-4 mt-4 text-sm">
          <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded bg-green-100"></div>
            <span>Présent</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded bg-red-100"></div>
            <span>Absent</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded bg-orange-100"></div>
            <span>Retard</span>
          </div>
        </div>
      </div>

      <!-- Absences List -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="bg-gray-800 px-6 py-4">
          <h2 class="text-white font-bold">Historique des absences</h2>
        </div>
        <div class="divide-y">
          <div *ngFor="let record of absenceHistory()" class="p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center"
                 [ngClass]="{
                   'bg-red-100 text-red-600': record.type === 'absence',
                   'bg-orange-100 text-orange-600': record.type === 'retard'
                 }">
              <i [class]="record.type === 'absence' ? 'pi pi-times' : 'pi pi-clock'"></i>
            </div>
            <div class="flex-1">
              <div class="font-medium text-gray-800">{{ record.type === 'absence' ? 'Absence' : 'Retard' }}</div>
              <div class="text-sm text-gray-500">{{ record.date }} • {{ record.course }}</div>
            </div>
            <div class="text-right">
              <span class="px-2 py-1 text-xs rounded-full"
                    [ngClass]="record.justified ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">
                {{ record.justified ? 'Justifiée' : 'Non justifiée' }}
              </span>
              <div *ngIf="record.reason" class="text-sm text-gray-500 mt-1">{{ record.reason }}</div>
            </div>
          </div>
          <div *ngIf="absenceHistory().length === 0" class="p-8 text-center text-gray-500">
            <i class="pi pi-check-circle text-4xl text-green-500 mb-2"></i>
            <p>Aucune absence enregistrée</p>
          </div>
        </div>
      </div>
    </div>
  `
})
export class StudentAttendanceComponent {
  periodFilter = 'trimester';

  weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

  attendanceRate = signal(94);
  absenceCount = signal(3);
  lateCount = signal(2);
  unjustifiedCount = signal(1);

  calendarDays = signal([
    ...Array(6).fill({ date: null, currentMonth: false }),
    { date: 1, currentMonth: true, status: 'present' },
    { date: 2, currentMonth: true, status: 'present' },
    { date: 3, currentMonth: true, status: 'present' },
    { date: 4, currentMonth: true, status: 'present' },
    { date: 5, currentMonth: true, status: 'present' },
    { date: 6, currentMonth: true, status: 'weekend' },
    { date: 7, currentMonth: true, status: 'weekend' },
    { date: 8, currentMonth: true, status: 'present' },
    { date: 9, currentMonth: true, status: 'late' },
    { date: 10, currentMonth: true, status: 'present' },
    { date: 11, currentMonth: true, status: 'present' },
    { date: 12, currentMonth: true, status: 'absent' },
    { date: 13, currentMonth: true, status: 'weekend' },
    { date: 14, currentMonth: true, status: 'weekend' },
    { date: 15, currentMonth: true, status: 'present' },
    { date: 16, currentMonth: true, status: 'present' },
    { date: 17, currentMonth: true, status: 'present' },
    { date: 18, currentMonth: true, status: 'absent' },
    { date: 19, currentMonth: true, status: 'present' },
    { date: 20, currentMonth: true, status: 'weekend' },
    { date: 21, currentMonth: true, status: 'weekend' },
    { date: 22, currentMonth: true, status: 'present' },
    { date: 23, currentMonth: true, status: 'present' },
  ]);

  absenceHistory = signal([
    { id: 1, type: 'absence', date: '18/12/2024', course: 'Mathématiques', justified: true, reason: 'Maladie' },
    { id: 2, type: 'absence', date: '12/12/2024', course: 'Français', justified: false, reason: '' },
    { id: 3, type: 'retard', date: '09/12/2024', course: 'Histoire-Géo', justified: true, reason: 'Transport' },
  ]);
}
