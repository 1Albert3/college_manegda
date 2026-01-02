import { Component, signal, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-secretary-timetable',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Emplois du Temps</h1>
          <p class="text-gray-500">Créez et gérez les emplois du temps des classes</p>
        </div>
        <div class="flex gap-3">
          <button class="px-4 py-2 border border-teal-600 text-teal-600 rounded-lg hover:bg-teal-50">
            <i class="pi pi-download mr-1"></i> Exporter
          </button>
          <button class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
            <i class="pi pi-plus mr-1"></i> Ajouter un cours
          </button>
        </div>
      </div>

      <!-- Class Selector -->
      <div class="bg-white rounded-xl shadow-sm p-4 flex flex-wrap gap-4 items-center">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Classe</label>
          <select [(ngModel)]="selectedClass"
                  class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
            <option *ngFor="let cls of classes()" [value]="cls">{{ cls }}</option>
          </select>
        </div>
        <div class="flex gap-2 ml-auto">
          <button *ngFor="let view of ['Semaine', 'Jour']"
                  (click)="currentView = view"
                  class="px-4 py-2 rounded-lg text-sm font-medium"
                  [ngClass]="currentView === view ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
            {{ view }}
          </button>
        </div>
      </div>

      <!-- Timetable Grid -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 w-20">Heure</th>
                <th *ngFor="let day of days" class="px-4 py-3 text-center text-sm font-medium text-gray-700">{{ day }}</th>
              </tr>
            </thead>
            <tbody>
              <tr *ngFor="let slot of timeSlots" class="border-t border-gray-100">
                <td class="px-4 py-2 text-sm text-gray-500 font-medium bg-gray-50">{{ slot }}</td>
                <td *ngFor="let day of days" class="px-2 py-2 border-l border-gray-100">
                  <div *ngIf="getCourse(day, slot)" 
                       class="p-2 rounded-lg text-xs cursor-pointer hover:opacity-80"
                       [style.background-color]="getCourse(day, slot).color + '20'"
                       [style.border-left]="'3px solid ' + getCourse(day, slot).color">
                    <div class="font-semibold" [style.color]="getCourse(day, slot).color">{{ getCourse(day, slot).subject }}</div>
                    <div class="text-gray-600">{{ getCourse(day, slot).teacher }}</div>
                    <div class="text-gray-400">Salle {{ getCourse(day, slot).room }}</div>
                  </div>
                  <div *ngIf="!getCourse(day, slot)" 
                       class="p-2 text-center text-gray-300 text-xs cursor-pointer hover:bg-gray-50 rounded">
                    <i class="pi pi-plus"></i>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Legend -->
      <div class="bg-white rounded-xl shadow-sm p-4">
        <h3 class="font-medium text-gray-700 mb-3">Légende des matières</h3>
        <div class="flex flex-wrap gap-4">
          <div *ngFor="let subject of subjects()" class="flex items-center gap-2">
            <div class="w-4 h-4 rounded" [style.background-color]="subject.color"></div>
            <span class="text-sm text-gray-600">{{ subject.name }}</span>
          </div>
        </div>
      </div>
    </div>
  `
})
export class SecretaryTimetableComponent implements OnInit {
  private http = inject(HttpClient);

  selectedClass = '';
  currentView = 'Semaine';
  
  classes = signal<string[]>([]);
  days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
  timeSlots = ['08:00-09:00', '09:00-10:00', '10:30-11:30', '11:30-12:30', '14:30-15:30', '15:30-16:30'];
  
  subjects = signal([
    { name: 'Mathématiques', color: '#3B82F6' },
    { name: 'Français', color: '#10B981' },
    { name: 'Anglais', color: '#8B5CF6' },
    { name: 'Histoire-Géo', color: '#F59E0B' },
    { name: 'SVT', color: '#EC4899' },
    { name: 'Physique-Chimie', color: '#06B6D4' },
    { name: 'Sport', color: '#EF4444' },
  ]);

  schedule: any[] = [];

  ngOnInit() {
    this.loadClasses();
  }

  loadClasses() {
    this.http.get<any[]>(`${environment.apiUrl}/academic/classrooms`).subscribe({
      next: (data) => {
        const names = data.map(c => c.name);
        this.classes.set(names);
        if (names.length > 0) {
            this.selectedClass = names[0];
            this.loadSchedule();
        }
      }
    });
  }

  loadSchedule() {
    // Placeholder - will integrate with /schedules/class/{id} later
    // For now we clear the mock list
    this.schedule = [];
  }

  getCourse(day: string, slot: string) {
    return this.schedule.find(c => c.day === day && c.slot === slot);
  }
}
