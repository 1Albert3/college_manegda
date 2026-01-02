import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { HttpClient } from '@angular/common/http';

interface TeacherData {
  teacher: {
    id: string;
    name: string;
    email: string;
  };
  school_year: string;
  current_trimestre: number;
  classes: ClassInfo[];
  subjects: Subject[];
  pending_grades: PendingGrade[];
  today_schedule: ScheduleItem[];
  recent_activity: Activity[];
  stats: {
    total_students: number;
    total_grades: number;
    classes_count: number;
  };
}

interface ClassInfo {
  id: string;
  niveau: string;
  nom: string;
  full_name: string;
  effectif: number;
  is_principal: boolean;
}

interface Subject {
  id: string;
  code: string;
  nom: string;
}

interface PendingGrade {
  class: string;
  class_id: string;
  subject: string;
  subject_id: string;
  trimestre: number;
  missing: number;
}

interface ScheduleItem {
  time: string;
  class: string;
  subject: string;
  room: string;
}

interface Activity {
  type: string;
  message: string;
  date: string;
}

@Component({
  selector: 'app-teacher-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule],
  template: `
    <div class="teacher-dashboard">
      <!-- Header -->
      <div class="dashboard-header">
        <div class="welcome">
          <h1>Bonjour, {{ data?.teacher?.name }} 👋</h1>
          <p>{{ today | date:'EEEE dd MMMM yyyy':'':'fr' }} • {{ data?.school_year }}</p>
        </div>
        <div class="header-actions">
          <a routerLink="/teacher/grades/entry" class="btn-primary">
            <span>📝</span> Saisir des notes
          </a>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-icon blue">🏫</div>
          <div class="stat-content">
            <span class="stat-value">{{ data?.stats?.classes_count || 0 }}</span>
            <span class="stat-label">Classes</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">👨‍🎓</div>
          <div class="stat-content">
            <span class="stat-value">{{ data?.stats?.total_students || 0 }}</span>
            <span class="stat-label">Élèves</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple">📊</div>
          <div class="stat-content">
            <span class="stat-value">{{ data?.stats?.total_grades || 0 }}</span>
            <span class="stat-label">Notes saisies</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange">📆</div>
          <div class="stat-content">
            <span class="stat-value">T{{ data?.current_trimestre }}</span>
            <span class="stat-label">Trimestre</span>
          </div>
        </div>
      </div>

      <!-- Main Grid -->
      <div class="main-grid">
        <!-- Emploi du temps du jour -->
        <div class="card schedule-card">
          <div class="card-header">
            <h2>📅 Emploi du temps - Aujourd'hui</h2>
          </div>
          <div class="card-content">
            <div class="schedule-list" *ngIf="data?.today_schedule?.length">
              <div class="schedule-item" 
                   *ngFor="let item of data.today_schedule; let i = index"
                   [class.current]="isCurrentPeriod(item.time)">
                <div class="schedule-time">{{ item.time }}</div>
                <div class="schedule-details">
                  <span class="schedule-class">{{ item.class }}</span>
                  <span class="schedule-subject">{{ item.subject }}</span>
                </div>
                <div class="schedule-room">{{ item.room }}</div>
              </div>
            </div>
            <div class="empty-state" *ngIf="!data?.today_schedule?.length">
              Pas de cours aujourd'hui
            </div>
          </div>
        </div>

        <!-- Notes en attente -->
        <div class="card pending-card">
          <div class="card-header">
            <h2>⚠️ Notes à saisir</h2>
            <a routerLink="/teacher/grades/entry">Voir tout →</a>
          </div>
          <div class="card-content">
            <div class="pending-list" *ngIf="data?.pending_grades?.length">
              <div class="pending-item" *ngFor="let item of data.pending_grades">
                <div class="pending-info">
                  <span class="pending-class">{{ item.class }}</span>
                  <span class="pending-subject">{{ item.subject }}</span>
                </div>
                <div class="pending-badge">
                  {{ item.missing }} manquantes
                </div>
                <a [routerLink]="['/teacher/grades/entry']" 
                   [queryParams]="{class_id: item.class_id, subject_id: item.subject_id}"
                   class="btn-small">
                  Saisir
                </a>
              </div>
            </div>
            <div class="success-state" *ngIf="!data?.pending_grades?.length">
              ✅ Toutes les notes sont à jour !
            </div>
          </div>
        </div>

        <!-- Mes classes -->
        <div class="card classes-card">
          <div class="card-header">
            <h2>📚 Mes Classes</h2>
          </div>
          <div class="card-content">
            <div class="classes-grid" *ngIf="data?.classes?.length">
              <a *ngFor="let cls of data.classes" 
                 [routerLink]="['/teacher/class', cls.id]"
                 class="class-tile">
                <div class="class-level">{{ cls.niveau }}</div>
                <div class="class-name">{{ cls.nom }}</div>
                <div class="class-effectif">{{ cls.effectif }} élèves</div>
                <span class="principal-badge" *ngIf="cls.is_principal">Prof. Principal</span>
              </a>
            </div>
            <div class="empty-state" *ngIf="!data?.classes?.length">
              Aucune classe assignée
            </div>
          </div>
        </div>

        <!-- Activité récente -->
        <div class="card activity-card">
          <div class="card-header">
            <h2>📋 Activité Récente</h2>
          </div>
          <div class="card-content">
            <div class="activity-list" *ngIf="data?.recent_activity?.length">
              <div class="activity-item" *ngFor="let activity of data.recent_activity">
                <div class="activity-icon">
                  <ng-container [ngSwitch]="activity.type">
                    <span *ngSwitchCase="'grade'">📝</span>
                    <span *ngSwitchCase="'attendance'">📅</span>
                    <span *ngSwitchDefault>📌</span>
                  </ng-container>
                </div>
                <div class="activity-content">
                  <p>{{ activity.message }}</p>
                  <span class="activity-date">{{ activity.date | date:'dd/MM HH:mm' }}</span>
                </div>
              </div>
            </div>
            <div class="empty-state" *ngIf="!data?.recent_activity?.length">
              Aucune activité récente
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <h3>⚡ Actions rapides</h3>
        <div class="actions-grid">
          <a routerLink="/teacher/grades/entry" class="action-card">
            <span class="action-icon">📝</span>
            <span class="action-text">Saisir notes</span>
          </a>
          <a routerLink="/teacher/attendance" class="action-card">
            <span class="action-icon">✅</span>
            <span class="action-text">Appel</span>
          </a>
          <a routerLink="/teacher/homework" class="action-card">
            <span class="action-icon">📚</span>
            <span class="action-text">Devoirs</span>
          </a>
          <a routerLink="/teacher/schedule" class="action-card">
            <span class="action-icon">📆</span>
            <span class="action-text">Emploi du temps</span>
          </a>
          <a routerLink="/teacher/messages" class="action-card">
            <span class="action-icon">📨</span>
            <span class="action-text">Messages</span>
          </a>
          <a routerLink="/teacher/stats" class="action-card">
            <span class="action-icon">📊</span>
            <span class="action-text">Statistiques</span>
          </a>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .teacher-dashboard {
      padding: 1.5rem 2rem;
      max-width: 1400px;
      margin: 0 auto;
      background: #f8fafc;
      min-height: 100vh;
    }

    .dashboard-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .welcome h1 {
      font-size: 1.5rem;
      color: #1a365d;
      margin: 0 0 0.25rem;
    }

    .welcome p {
      color: #64748b;
      margin: 0;
      font-size: 0.95rem;
    }

    .btn-primary {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      background: linear-gradient(135deg, #4f46e5, #6366f1);
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(79, 70, 229, 0.35);
    }

    /* Stats Row */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 1.25rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
    }

    .stat-icon.blue { background: #eff6ff; }
    .stat-icon.green { background: #ecfdf5; }
    .stat-icon.purple { background: #f5f3ff; }
    .stat-icon.orange { background: #fff7ed; }

    .stat-content {
      display: flex;
      flex-direction: column;
    }

    .stat-value {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1e293b;
    }

    .stat-label {
      font-size: 0.85rem;
      color: #64748b;
    }

    /* Main Grid */
    .main-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      overflow: hidden;
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 1.25rem;
      border-bottom: 1px solid #f1f5f9;
    }

    .card-header h2 {
      font-size: 1rem;
      color: #1e293b;
      margin: 0;
    }

    .card-header a {
      color: #4f46e5;
      text-decoration: none;
      font-size: 0.85rem;
    }

    .card-content {
      padding: 1.25rem;
    }

    /* Schedule */
    .schedule-list {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .schedule-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.75rem 1rem;
      background: #f8fafc;
      border-radius: 8px;
      border-left: 3px solid #e2e8f0;
    }

    .schedule-item.current {
      background: linear-gradient(135deg, #eff6ff, #dbeafe);
      border-left-color: #4f46e5;
    }

    .schedule-time {
      font-weight: 600;
      color: #475569;
      min-width: 100px;
      font-size: 0.9rem;
    }

    .schedule-details {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .schedule-class {
      font-weight: 500;
      color: #1e293b;
    }

    .schedule-subject {
      font-size: 0.85rem;
      color: #64748b;
    }

    .schedule-room {
      font-size: 0.85rem;
      color: #94a3b8;
      background: white;
      padding: 0.25rem 0.75rem;
      border-radius: 15px;
    }

    /* Pending Grades */
    .pending-list {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .pending-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.75rem;
      background: #fef3c7;
      border-radius: 8px;
    }

    .pending-info {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .pending-class {
      font-weight: 500;
      color: #1e293b;
    }

    .pending-subject {
      font-size: 0.85rem;
      color: #64748b;
    }

    .pending-badge {
      font-size: 0.75rem;
      color: #92400e;
      background: #fde68a;
      padding: 0.25rem 0.75rem;
      border-radius: 15px;
      font-weight: 500;
    }

    .btn-small {
      background: #4f46e5;
      color: white;
      padding: 0.375rem 0.875rem;
      border-radius: 6px;
      font-size: 0.8rem;
      text-decoration: none;
    }

    .success-state {
      text-align: center;
      padding: 1.5rem;
      color: #10b981;
      font-weight: 500;
    }

    /* Classes Grid */
    .classes-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1rem;
    }

    .class-tile {
      position: relative;
      padding: 1rem;
      background: linear-gradient(135deg, #f8fafc, #f1f5f9);
      border-radius: 10px;
      text-decoration: none;
      transition: all 0.3s;
      border: 2px solid transparent;
    }

    .class-tile:hover {
      transform: translateY(-2px);
      border-color: #4f46e5;
      box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
    }

    .class-level {
      font-size: 1.25rem;
      font-weight: 700;
      color: #4f46e5;
    }

    .class-name {
      font-weight: 500;
      color: #1e293b;
    }

    .class-effectif {
      font-size: 0.8rem;
      color: #64748b;
    }

    .principal-badge {
      position: absolute;
      top: 0.5rem;
      right: 0.5rem;
      font-size: 0.65rem;
      background: #10b981;
      color: white;
      padding: 0.125rem 0.5rem;
      border-radius: 10px;
    }

    /* Activity */
    .activity-list {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .activity-item {
      display: flex;
      gap: 0.75rem;
    }

    .activity-icon {
      font-size: 1.25rem;
    }

    .activity-content {
      flex: 1;
    }

    .activity-content p {
      margin: 0;
      font-size: 0.9rem;
      color: #334155;
    }

    .activity-date {
      font-size: 0.75rem;
      color: #94a3b8;
    }

    .empty-state {
      text-align: center;
      padding: 1.5rem;
      color: #94a3b8;
    }

    /* Quick Actions */
    .quick-actions {
      margin-top: 2rem;
    }

    .quick-actions h3 {
      font-size: 1.1rem;
      color: #1e293b;
      margin: 0 0 1rem;
    }

    .actions-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 1rem;
    }

    .action-card {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.5rem;
      padding: 1.25rem 1rem;
      background: white;
      border-radius: 12px;
      text-decoration: none;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      transition: all 0.3s;
    }

    .action-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    .action-icon {
      font-size: 1.75rem;
    }

    .action-text {
      font-size: 0.85rem;
      color: #475569;
      font-weight: 500;
    }

    @media (max-width: 1024px) {
      .stats-row {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .main-grid {
        grid-template-columns: 1fr;
      }
      
      .actions-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (max-width: 768px) {
      .actions-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }
  `]
})
export class TeacherDashboardComponent implements OnInit {
  data: TeacherData | null = null;
  today = new Date();

  constructor(private http: HttpClient) {}

  ngOnInit() {
    this.loadDashboard();
  }

  loadDashboard() {
    this.http.get<TeacherData>('/api/v1/dashboard/teacher')
      .subscribe({
        next: (data) => this.data = data,
        error: (err) => console.error('Error loading dashboard', err)
      });
  }

  isCurrentPeriod(timeRange: string): boolean {
    const now = new Date();
    const [start, end] = timeRange.split(' - ').map(t => {
      const [hours, minutes] = t.split(':').map(Number);
      const date = new Date();
      date.setHours(hours, minutes, 0, 0);
      return date;
    });
    
    return now >= start && now <= end;
  }
}
