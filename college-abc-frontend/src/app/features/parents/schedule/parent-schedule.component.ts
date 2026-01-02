import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-parent-schedule',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="space-y-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Emploi du Temps</h1>
          <p class="text-gray-500">Planning hebdomadaire</p>
        </div>
        <div class="flex gap-2">
          <select [(ngModel)]="selectedChild" class="px-4 py-2 border rounded-lg">
            <option *ngFor="let child of children()" [value]="child.id">{{ child.name }} - {{ child.class }}</option>
          </select>
        </div>
      </div>

      <!-- Weekly Schedule -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden overflow-x-auto">
        <table class="w-full min-w-[800px]">
          <thead>
            <tr class="bg-gray-50">
              <th class="p-3 text-left text-sm font-medium text-gray-500 border-r w-24">Heures</th>
              <th *ngFor="let day of days" class="p-3 text-center text-sm font-medium border-r last:border-r-0"
                  [ngClass]="{'bg-purple-50 text-purple-700': isToday(day)}">
                {{ day }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr *ngFor="let slot of timeSlots" class="border-t">
              <td class="p-2 text-center text-sm font-medium text-gray-600 bg-gray-50 border-r">{{ slot }}</td>
              <td *ngFor="let day of days" class="p-1 border-r last:border-r-0 h-20 align-top"
                  [ngClass]="{'bg-purple-50/30': isToday(day)}">
                <div *ngFor="let course of getCourse(day, slot)"
                     class="p-2 rounded-lg text-xs h-full"
                     [style.background-color]="course.color + '20'"
                     [style.border-left]="'3px solid ' + course.color">
                  <div class="font-bold" [style.color]="course.color">{{ course.subject }}</div>
                  <div class="text-gray-600">{{ course.teacher }}</div>
                  <div class="text-gray-500">{{ course.room }}</div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
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

      <!-- Today's Classes -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="font-bold text-gray-800 mb-4">Cours d'aujourd'hui</h2>
        <div class="space-y-3">
          <div *ngFor="let course of todaysCourses()" class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
            <div class="w-1 h-12 rounded-full" [style.background-color]="course.color"></div>
            <div class="flex-1">
              <div class="font-medium">{{ course.subject }}</div>
              <div class="text-sm text-gray-500">{{ course.time }}</div>
            </div>
            <div class="text-right text-sm text-gray-600">
              <div>{{ course.teacher }}</div>
              <div>{{ course.room }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `
})
export class ParentScheduleComponent {
  selectedChild = '1';

  children = signal([
    { id: '1', name: 'Amadou Diallo', class: '6ème A' },
    { id: '2', name: 'Fatou Diallo', class: '4ème B' },
  ]);

  days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
  timeSlots = ['08:00-09:00', '09:00-10:00', '10:15-11:15', '11:15-12:15', '15:00-16:00', '16:00-17:00'];

  subjects = signal([
    { name: 'Mathématiques', color: '#4F46E5' },
    { name: 'Français', color: '#DC2626' },
    { name: 'Histoire-Géo', color: '#059669' },
    { name: 'SVT', color: '#16A34A' },
    { name: 'Physique-Chimie', color: '#D97706' },
    { name: 'Anglais', color: '#7C3AED' },
    { name: 'EPS', color: '#0891B2' },
  ]);

  schedule = signal([
    { day: 'Lundi', time: '08:00-09:00', subject: 'Mathématiques', teacher: 'M. Ouédraogo', room: 'Salle 101', color: '#4F46E5' },
    { day: 'Lundi', time: '09:00-10:00', subject: 'Français', teacher: 'Mme Sawadogo', room: 'Salle 102', color: '#DC2626' },
    { day: 'Lundi', time: '10:15-11:15', subject: 'Histoire-Géo', teacher: 'M. Kaboré', room: 'Salle 103', color: '#059669' },
    { day: 'Lundi', time: '15:00-16:00', subject: 'Anglais', teacher: 'Mme Diallo', room: 'Salle 104', color: '#7C3AED' },
    { day: 'Mardi', time: '08:00-09:00', subject: 'SVT', teacher: 'M. Traoré', room: 'Labo SVT', color: '#16A34A' },
    { day: 'Mardi', time: '09:00-10:00', subject: 'Physique-Chimie', teacher: 'M. Koné', room: 'Labo Physique', color: '#D97706' },
    { day: 'Mardi', time: '10:15-11:15', subject: 'Mathématiques', teacher: 'M. Ouédraogo', room: 'Salle 101', color: '#4F46E5' },
    { day: 'Mercredi', time: '08:00-09:00', subject: 'Français', teacher: 'Mme Sawadogo', room: 'Salle 102', color: '#DC2626' },
    { day: 'Mercredi', time: '09:00-10:00', subject: 'EPS', teacher: 'M. Sanogo', room: 'Terrain', color: '#0891B2' },
    { day: 'Jeudi', time: '08:00-09:00', subject: 'Anglais', teacher: 'Mme Diallo', room: 'Salle 104', color: '#7C3AED' },
    { day: 'Jeudi', time: '09:00-10:00', subject: 'Histoire-Géo', teacher: 'M. Kaboré', room: 'Salle 103', color: '#059669' },
    { day: 'Vendredi', time: '08:00-09:00', subject: 'Mathématiques', teacher: 'M. Ouédraogo', room: 'Salle 101', color: '#4F46E5' },
    { day: 'Vendredi', time: '09:00-10:00', subject: 'SVT', teacher: 'M. Traoré', room: 'Labo SVT', color: '#16A34A' },
  ]);

  isToday(day: string) { return day === 'Lundi'; }

  getCourse(day: string, time: string) {
    return this.schedule().filter(c => c.day === day && c.time === time);
  }

  todaysCourses = () => this.schedule().filter(c => c.day === 'Lundi');
}
