import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../environments/environment';

interface ScheduleSlot {
  id: string;
  day: string;
  start_time: string;
  end_time: string;
  subject: string;
  subject_code: string;
  teacher: string;
  room: string;
  class_name?: string;
}

interface ClassOption {
  id: string;
  niveau: string;
  nom: string;
  full_name: string;
}

@Component({
  selector: 'app-schedule',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="schedule-container">
      <div class="page-header">
        <div class="header-left">
          <h1>📅 Emploi du Temps</h1>
          <p>{{ viewType === 'class' ? 'Vue par classe' : 'Vue par enseignant' }}</p>
        </div>
        <div class="header-actions">
          <div class="view-toggle">
            <button 
              [class.active]="viewType === 'class'"
              (click)="setViewType('class')">
              Par Classe
            </button>
            <button 
              [class.active]="viewType === 'teacher'"
              (click)="setViewType('teacher')">
              Par Enseignant
            </button>
          </div>
          <select [(ngModel)]="selectedId" (change)="loadSchedule()">
            <option value="">Sélectionner...</option>
            <option *ngFor="let opt of options" [value]="opt.id">
              {{ opt.full_name || opt.nom }}
            </option>
          </select>
          <button class="btn-export" (click)="exportPdf()">
            📥 Exporter PDF
          </button>
        </div>
      </div>

      <!-- Grille horaire -->
      <div class="schedule-grid" *ngIf="schedule.length > 0">
        <div class="schedule-header">
          <div class="time-column">Horaires</div>
          <div class="day-column" *ngFor="let day of days">{{ day }}</div>
        </div>

        <div class="schedule-body">
          <div class="time-row" *ngFor="let slot of timeSlots">
            <div class="time-cell">
              <span class="time-start">{{ slot.start }}</span>
              <span class="time-end">{{ slot.end }}</span>
            </div>
            <div class="slot-cell" *ngFor="let day of days">
              <div 
                class="schedule-slot" 
                *ngIf="getSlot(day, slot.start)"
                [style.background]="getSubjectColor(getSlot(day, slot.start)?.subject_code)"
                (click)="showSlotDetails(getSlot(day, slot.start))">
                <span class="slot-subject">{{ getSlot(day, slot.start)?.subject }}</span>
                <span class="slot-info">{{ getSlot(day, slot.start)?.room }}</span>
                <span class="slot-teacher" *ngIf="viewType === 'class'">
                  {{ getSlot(day, slot.start)?.teacher }}
                </span>
                <span class="slot-class" *ngIf="viewType === 'teacher'">
                  {{ getSlot(day, slot.start)?.class_name }}
                </span>
              </div>
              <div class="empty-slot" *ngIf="!getSlot(day, slot.start)">-</div>
            </div>
          </div>
        </div>
      </div>

      <!-- État vide -->
      <div class="empty-state" *ngIf="schedule.length === 0 && !isLoading">
        <div class="empty-icon">📅</div>
        <h3>Sélectionnez une classe ou un enseignant</h3>
        <p>Choisissez une option pour afficher l'emploi du temps correspondant.</p>
      </div>

      <!-- Légende -->
      <div class="legend" *ngIf="schedule.length > 0">
        <h4>Légende des matières</h4>
        <div class="legend-items">
          <div class="legend-item" *ngFor="let subject of uniqueSubjects">
            <span class="legend-color" [style.background]="getSubjectColor(subject.code)"></span>
            <span class="legend-name">{{ subject.name }}</span>
          </div>
        </div>
      </div>

      <!-- Modal détails -->
      <div class="modal-overlay" *ngIf="selectedSlot" (click)="closeDetails()">
        <div class="modal-content" (click)="$event.stopPropagation()">
          <div class="modal-header">
            <h3>Détails du cours</h3>
            <button class="btn-close" (click)="closeDetails()">×</button>
          </div>
          <div class="modal-body">
            <div class="detail-row">
              <span class="detail-label">Matière</span>
              <span class="detail-value">{{ selectedSlot.subject }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Jour</span>
              <span class="detail-value">{{ selectedSlot.day }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Horaire</span>
              <span class="detail-value">{{ selectedSlot.start_time }} - {{ selectedSlot.end_time }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Salle</span>
              <span class="detail-value">{{ selectedSlot.room }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Enseignant</span>
              <span class="detail-value">{{ selectedSlot.teacher }}</span>
            </div>
            <div class="detail-row" *ngIf="selectedSlot.class_name">
              <span class="detail-label">Classe</span>
              <span class="detail-value">{{ selectedSlot.class_name }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .schedule-container {
      padding: 1.5rem 2rem;
      max-width: 1400px;
      margin: 0 auto;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 2rem;
    }

    .page-header h1 {
      font-size: 1.75rem;
      color: #1a365d;
      margin: 0 0 0.25rem;
    }

    .page-header p {
      color: #64748b;
      margin: 0;
    }

    .header-actions {
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    .view-toggle {
      display: flex;
      background: #f1f5f9;
      border-radius: 8px;
      overflow: hidden;
    }

    .view-toggle button {
      padding: 0.5rem 1rem;
      border: none;
      background: transparent;
      color: #64748b;
      cursor: pointer;
      font-weight: 500;
    }

    .view-toggle button.active {
      background: #4f46e5;
      color: white;
    }

    .header-actions select {
      padding: 0.5rem 1rem;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      min-width: 200px;
    }

    .btn-export {
      background: white;
      border: 1px solid #e2e8f0;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      cursor: pointer;
    }

    /* Schedule Grid */
    .schedule-grid {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      overflow: hidden;
    }

    .schedule-header {
      display: grid;
      grid-template-columns: 100px repeat(6, 1fr);
      background: linear-gradient(135deg, #4f46e5, #6366f1);
      color: white;
    }

    .schedule-header > div {
      padding: 1rem;
      text-align: center;
      font-weight: 600;
      border-right: 1px solid rgba(255, 255, 255, 0.2);
    }

    .schedule-header > div:last-child {
      border-right: none;
    }

    .schedule-body {
      display: flex;
      flex-direction: column;
    }

    .time-row {
      display: grid;
      grid-template-columns: 100px repeat(6, 1fr);
      border-bottom: 1px solid #e2e8f0;
    }

    .time-row:last-child {
      border-bottom: none;
    }

    .time-cell {
      padding: 0.75rem;
      background: #f8fafc;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      border-right: 1px solid #e2e8f0;
    }

    .time-start {
      font-weight: 600;
      color: #1e293b;
    }

    .time-end {
      font-size: 0.8rem;
      color: #64748b;
    }

    .slot-cell {
      padding: 0.5rem;
      min-height: 80px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-right: 1px solid #e2e8f0;
    }

    .slot-cell:last-child {
      border-right: none;
    }

    .schedule-slot {
      width: 100%;
      height: 100%;
      padding: 0.5rem;
      border-radius: 8px;
      display: flex;
      flex-direction: column;
      cursor: pointer;
      transition: transform 0.2s;
    }

    .schedule-slot:hover {
      transform: scale(1.02);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .slot-subject {
      font-weight: 600;
      font-size: 0.9rem;
      color: white;
      margin-bottom: 0.25rem;
    }

    .slot-info, .slot-teacher, .slot-class {
      font-size: 0.75rem;
      color: rgba(255, 255, 255, 0.9);
    }

    .empty-slot {
      color: #cbd5e1;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .empty-icon {
      font-size: 4rem;
      margin-bottom: 1rem;
    }

    .empty-state h3 {
      color: #1e293b;
      margin: 0 0 0.5rem;
    }

    .empty-state p {
      color: #64748b;
    }

    /* Legend */
    .legend {
      margin-top: 1.5rem;
      background: white;
      border-radius: 12px;
      padding: 1.25rem;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    }

    .legend h4 {
      margin: 0 0 1rem;
      font-size: 0.9rem;
      color: #64748b;
    }

    .legend-items {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .legend-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .legend-color {
      width: 16px;
      height: 16px;
      border-radius: 4px;
    }

    .legend-name {
      font-size: 0.85rem;
      color: #475569;
    }

    /* Modal */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }

    .modal-content {
      background: white;
      border-radius: 12px;
      width: 100%;
      max-width: 400px;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 1.25rem;
      border-bottom: 1px solid #e2e8f0;
    }

    .modal-header h3 {
      margin: 0;
      font-size: 1.1rem;
    }

    .btn-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      color: #64748b;
      cursor: pointer;
    }

    .modal-body {
      padding: 1.25rem;
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      padding: 0.75rem 0;
      border-bottom: 1px solid #f1f5f9;
    }

    .detail-row:last-child {
      border-bottom: none;
    }

    .detail-label {
      color: #64748b;
    }

    .detail-value {
      font-weight: 500;
      color: #1e293b;
    }

    @media (max-width: 1024px) {
      .schedule-header,
      .time-row {
        grid-template-columns: 80px repeat(6, 1fr);
      }
    }

    @media (max-width: 768px) {
      .header-actions {
        flex-direction: column;
        align-items: flex-end;
      }
    }
  `]
})
export class ScheduleComponent implements OnInit {
  viewType: 'class' | 'teacher' = 'class';
  selectedId = '';
  options: ClassOption[] = [];
  schedule: ScheduleSlot[] = [];
  isLoading = false;

  selectedSlot: ScheduleSlot | null = null;

  days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

  timeSlots = [
    { start: '07:30', end: '08:30' },
    { start: '08:30', end: '09:30' },
    { start: '09:45', end: '10:45' },
    { start: '10:45', end: '11:45' },
    { start: '15:00', end: '16:00' },
    { start: '16:00', end: '17:00' },
  ];

  // Couleurs pour les matières
  subjectColors: { [key: string]: string } = {
    'FRA': '#4f46e5',
    'MAT': '#10b981',
    'SVT': '#f59e0b',
    'HIS': '#8b5cf6',
    'GEO': '#06b6d4',
    'ECM': '#ec4899',
    'EPS': '#f97316',
    'ANG': '#3b82f6',
    'DES': '#a855f7',
    'CHA': '#14b8a6',
  };

  constructor(private http: HttpClient) {}

  private apiUrl = environment.apiUrl || 'http://localhost:8000/api';

  ngOnInit() {
    this.loadOptions();
  }

  setViewType(type: 'class' | 'teacher') {
    this.viewType = type;
    this.selectedId = '';
    this.schedule = [];
    this.loadOptions();
  }

  loadOptions() {
    const endpoint = this.viewType === 'class' 
      ? `${this.apiUrl}/mp/classes?active=true` 
      : `${this.apiUrl}/mp/teachers?active=true`;

    this.http.get<any>(endpoint)
      .subscribe({
        next: (res) => this.options = res.data || res,
        error: (err) => console.error('Error loading options', err)
      });
  }

  loadSchedule() {
    if (!this.selectedId) {
      this.schedule = [];
      return;
    }

    this.isLoading = true;

    const endpoint = this.viewType === 'class'
      ? `${this.apiUrl}/schedules/class/${this.selectedId}?level=mp`
      : `${this.apiUrl}/schedules/teacher/${this.selectedId}?level=mp`;

    this.http.get<any>(endpoint)
      .subscribe({
        next: (res) => {
          this.schedule = res.data || res.schedule || [];
          this.isLoading = false;
        },
        error: (err) => {
          this.isLoading = false;
          console.error('Error loading schedule', err);
        }
      });
  }

  getSlot(day: string, startTime: string): ScheduleSlot | null {
    return this.schedule.find(s => 
      s.day === day && s.start_time === startTime
    ) || null;
  }

  getSubjectColor(code: string | undefined): string {
    if (!code) return '#94a3b8';
    return this.subjectColors[code] || this.generateColor(code);
  }

  generateColor(code: string): string {
    // Générer une couleur basée sur le code
    let hash = 0;
    for (let i = 0; i < code.length; i++) {
      hash = code.charCodeAt(i) + ((hash << 5) - hash);
    }
    const h = hash % 360;
    return `hsl(${h}, 65%, 50%)`;
  }

  get uniqueSubjects(): { code: string; name: string }[] {
    const subjects = new Map<string, string>();
    this.schedule.forEach(s => {
      if (s.subject_code && !subjects.has(s.subject_code)) {
        subjects.set(s.subject_code, s.subject);
      }
    });
    return Array.from(subjects).map(([code, name]) => ({ code, name }));
  }

  showSlotDetails(slot: ScheduleSlot | null) {
    this.selectedSlot = slot;
  }

  closeDetails() {
    this.selectedSlot = null;
  }

  exportPdf() {
    if (!this.selectedId) return;

    const url = this.viewType === 'class'
      ? `${this.apiUrl}/schedules/export/pdf?level=mp&class_id=${this.selectedId}`
      : `${this.apiUrl}/schedules/export/pdf?level=mp&teacher_id=${this.selectedId}`;

    window.open(url, '_blank');
  }
}
