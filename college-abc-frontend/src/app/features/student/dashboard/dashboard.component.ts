import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { HttpClient } from '@angular/common/http';

interface StudentData {
  student: {
    id: string;
    matricule: string;
    nom: string;
    prenoms: string;
    full_name: string;
    photo_url: string | null;
    class_name: string;
    niveau: string;
  };
  school_year: string;
  grades_summary: {
    moyenne_generale: number;
    rang: number;
    effectif: number;
    trimestre: number;
  };
  recent_grades: {
    subject: string;
    note: number;
    date: string;
    type: string;
    commentaire: string;
  }[];
  attendance_summary: {
    absences: number;
    retards: number;
    heures_manquees: number;
  };
  upcoming_homework: {
    subject: string;
    title: string;
    due_date: string;
    is_overdue: boolean;
  }[];
  schedule_today: {
    time: string;
    subject: string;
    teacher: string;
    room: string;
  }[];
  announcements: {
    title: string;
    content: string;
    date: string;
  }[];
}

@Component({
  selector: 'app-student-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule],
  template: `
    <div class="student-dashboard">
      <!-- Profile Header -->
      <div class="profile-header" *ngIf="data?.student">
        <div class="profile-left">
          <div class="avatar">
            <img [src]="data.student.photo_url || defaultAvatar" alt="Photo">
          </div>
          <div class="profile-info">
            <h1>Bonjour, {{ data.student.prenoms }} 👋</h1>
            <p class="profile-details">
              <span class="matricule">{{ data.student.matricule }}</span>
              <span class="class">{{ data.student.class_name }}</span>
            </p>
            <p class="school-year">{{ data.school_year }}</p>
          </div>
        </div>
        <div class="profile-stats" *ngIf="data?.grades_summary">
          <div class="stat-box moyenne" [class.good]="data.grades_summary.moyenne_generale >= 10">
            <span class="stat-value">{{ data.grades_summary.moyenne_generale | number:'1.2-2' }}</span>
            <span class="stat-label">Moyenne</span>
          </div>
          <div class="stat-box rang">
            <span class="stat-value">{{ data.grades_summary.rang }}<sup>e</sup></span>
            <span class="stat-label">sur {{ data.grades_summary.effectif }}</span>
          </div>
          <div class="stat-box trimestre">
            <span class="stat-value">T{{ data.grades_summary.trimestre }}</span>
            <span class="stat-label">Trimestre</span>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="main-grid">
        <!-- Today's Schedule -->
        <div class="card schedule-card">
          <div class="card-header">
            <h2>📅 Emploi du temps - Aujourd'hui</h2>
            <a routerLink="/student/schedule">Voir tout →</a>
          </div>
          <div class="card-content">
            <div class="schedule-timeline" *ngIf="data?.schedule_today?.length">
              <div class="schedule-item" 
                   *ngFor="let item of data.schedule_today"
                   [class.current]="isCurrentPeriod(item.time)">
                <div class="time-block">
                  <span class="time">{{ item.time }}</span>
                </div>
                <div class="course-block">
                  <span class="subject">{{ item.subject }}</span>
                  <span class="details">{{ item.teacher }} • {{ item.room }}</span>
                </div>
              </div>
            </div>
            <div class="empty-state" *ngIf="!data?.schedule_today?.length">
              🎉 Pas de cours aujourd'hui !
            </div>
          </div>
        </div>

        <!-- Recent Grades -->
        <div class="card grades-card">
          <div class="card-header">
            <h2>📊 Dernières Notes</h2>
            <a routerLink="/student/grades">Voir tout →</a>
          </div>
          <div class="card-content">
            <div class="grades-list" *ngIf="data?.recent_grades?.length">
              <div class="grade-item" *ngFor="let grade of data.recent_grades">
                <div class="grade-subject">{{ grade.subject }}</div>
                <div class="grade-meta">
                  <span class="grade-type">{{ getEvaluationType(grade.type) }}</span>
                  <span class="grade-date">{{ grade.date | date:'dd/MM' }}</span>
                </div>
                <div class="grade-score" [class.good]="grade.note >= 10" [class.bad]="grade.note < 10">
                  {{ grade.note | number:'1.1-1' }}/20
                </div>
              </div>
            </div>
            <div class="empty-state" *ngIf="!data?.recent_grades?.length">
              Aucune note récente
            </div>
          </div>
        </div>

        <!-- Homework -->
        <div class="card homework-card">
          <div class="card-header">
            <h2>📝 Devoirs à rendre</h2>
            <a routerLink="/student/homework">Voir tout →</a>
          </div>
          <div class="card-content">
            <div class="homework-list" *ngIf="data?.upcoming_homework?.length">
              <div class="homework-item" 
                   *ngFor="let hw of data.upcoming_homework"
                   [class.overdue]="hw.is_overdue">
                <div class="hw-icon">
                  <span *ngIf="hw.is_overdue">⚠️</span>
                  <span *ngIf="!hw.is_overdue">📚</span>
                </div>
                <div class="hw-content">
                  <span class="hw-subject">{{ hw.subject }}</span>
                  <span class="hw-title">{{ hw.title }}</span>
                </div>
                <div class="hw-due">
                  <span class="due-label">{{ hw.is_overdue ? 'En retard' : 'Pour le' }}</span>
                  <span class="due-date">{{ hw.due_date | date:'dd/MM' }}</span>
                </div>
              </div>
            </div>
            <div class="success-state" *ngIf="!data?.upcoming_homework?.length">
              ✅ Tous les devoirs sont rendus !
            </div>
          </div>
        </div>

        <!-- Attendance -->
        <div class="card attendance-card">
          <div class="card-header">
            <h2>📋 Mon Assiduité</h2>
            <a routerLink="/student/attendance">Détails →</a>
          </div>
          <div class="card-content">
            <div class="attendance-stats" *ngIf="data?.attendance_summary">
              <div class="attendance-stat">
                <div class="stat-circle" [class.alert]="data.attendance_summary.absences > 5">
                  {{ data.attendance_summary.absences }}
                </div>
                <span class="stat-name">Absences</span>
              </div>
              <div class="attendance-stat">
                <div class="stat-circle" [class.warning]="data.attendance_summary.retards > 3">
                  {{ data.attendance_summary.retards }}
                </div>
                <span class="stat-name">Retards</span>
              </div>
              <div class="attendance-stat">
                <div class="stat-circle">
                  {{ data.attendance_summary.heures_manquees }}h
                </div>
                <span class="stat-name">Heures manquées</span>
              </div>
            </div>
            <div class="attendance-message" *ngIf="data?.attendance_summary?.absences === 0 && data?.attendance_summary?.retards === 0">
              🌟 Excellent ! Tu es un élève assidu.
            </div>
          </div>
        </div>
      </div>

      <!-- Announcements -->
      <div class="announcements-section" *ngIf="data?.announcements?.length">
        <h2>📢 Annonces</h2>
        <div class="announcements-list">
          <div class="announcement" *ngFor="let ann of data.announcements">
            <div class="ann-date">{{ ann.date | date:'dd MMM':'':'fr' }}</div>
            <div class="ann-content">
              <h3>{{ ann.title }}</h3>
              <p>{{ ann.content }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <a routerLink="/student/grades" class="action-btn">
          <span class="icon">📊</span>
          <span class="text">Mes Notes</span>
        </a>
        <a routerLink="/student/bulletins" class="action-btn">
          <span class="icon">📄</span>
          <span class="text">Bulletins</span>
        </a>
        <a routerLink="/student/schedule" class="action-btn">
          <span class="icon">📅</span>
          <span class="text">Emploi du temps</span>
        </a>
        <a routerLink="/student/homework" class="action-btn">
          <span class="icon">📝</span>
          <span class="text">Devoirs</span>
        </a>
        <a routerLink="/student/messages" class="action-btn">
          <span class="icon">📨</span>
          <span class="text">Messages</span>
        </a>
      </div>
    </div>
  `,
  styles: [`
    .student-dashboard {
      padding: 1.5rem 2rem;
      max-width: 1200px;
      margin: 0 auto;
      background: linear-gradient(180deg, #eef2ff 0%, #f8fafc 100%);
      min-height: 100vh;
    }

    /* Profile Header */
    .profile-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(135deg, #4f46e5, #7c3aed);
      border-radius: 20px;
      padding: 2rem;
      margin-bottom: 2rem;
      color: white;
      box-shadow: 0 10px 30px rgba(79, 70, 229, 0.3);
    }

    .profile-left {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }

    .avatar img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      border: 3px solid rgba(255, 255, 255, 0.4);
      object-fit: cover;
    }

    .profile-info h1 {
      font-size: 1.5rem;
      margin: 0 0 0.5rem;
    }

    .profile-details {
      display: flex;
      gap: 1rem;
      margin: 0 0 0.25rem;
    }

    .matricule {
      font-family: monospace;
      background: rgba(255, 255, 255, 0.2);
      padding: 0.125rem 0.5rem;
      border-radius: 4px;
      font-size: 0.85rem;
    }

    .class {
      font-weight: 500;
    }

    .school-year {
      font-size: 0.9rem;
      opacity: 0.8;
      margin: 0;
    }

    .profile-stats {
      display: flex;
      gap: 1rem;
    }

    .stat-box {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(10px);
      padding: 1rem 1.5rem;
      border-radius: 12px;
      text-align: center;
      min-width: 80px;
    }

    .stat-box .stat-value {
      font-size: 1.75rem;
      font-weight: 700;
      display: block;
    }

    .stat-box.moyenne.good .stat-value {
      color: #86efac;
    }

    .stat-box .stat-label {
      font-size: 0.75rem;
      opacity: 0.8;
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
      border-radius: 16px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
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
      color: #6366f1;
      text-decoration: none;
      font-size: 0.85rem;
    }

    .card-content {
      padding: 1.25rem;
    }

    /* Schedule */
    .schedule-timeline {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .schedule-item {
      display: flex;
      gap: 1rem;
      padding: 0.75rem;
      border-radius: 10px;
      background: #f8fafc;
      transition: all 0.3s;
    }

    .schedule-item.current {
      background: linear-gradient(135deg, #eef2ff, #e0e7ff);
      border: 2px solid #6366f1;
    }

    .time-block {
      background: #4f46e5;
      color: white;
      padding: 0.5rem 0.75rem;
      border-radius: 8px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .course-block {
      display: flex;
      flex-direction: column;
      flex: 1;
    }

    .course-block .subject {
      font-weight: 600;
      color: #1e293b;
    }

    .course-block .details {
      font-size: 0.8rem;
      color: #64748b;
    }

    /* Grades */
    .grades-list {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .grade-item {
      display: flex;
      align-items: center;
      padding: 0.75rem;
      background: #f8fafc;
      border-radius: 8px;
    }

    .grade-subject {
      flex: 1;
      font-weight: 500;
      color: #1e293b;
    }

    .grade-meta {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      margin-right: 1rem;
    }

    .grade-type {
      font-size: 0.75rem;
      color: #64748b;
    }

    .grade-date {
      font-size: 0.7rem;
      color: #94a3b8;
    }

    .grade-score {
      font-size: 1.1rem;
      font-weight: 700;
      min-width: 60px;
      text-align: right;
    }

    .grade-score.good { color: #10b981; }
    .grade-score.bad { color: #ef4444; }

    /* Homework */
    .homework-list {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .homework-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.75rem;
      background: #f8fafc;
      border-radius: 8px;
    }

    .homework-item.overdue {
      background: #fef2f2;
      border-left: 3px solid #ef4444;
    }

    .hw-icon {
      font-size: 1.25rem;
    }

    .hw-content {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .hw-subject {
      font-weight: 500;
      color: #1e293b;
    }

    .hw-title {
      font-size: 0.85rem;
      color: #64748b;
    }

    .hw-due {
      text-align: right;
    }

    .due-label {
      font-size: 0.7rem;
      color: #94a3b8;
      display: block;
    }

    .due-date {
      font-weight: 600;
      font-size: 0.9rem;
      color: #1e293b;
    }

    .homework-item.overdue .due-date {
      color: #ef4444;
    }

    .success-state {
      text-align: center;
      padding: 1.5rem;
      color: #10b981;
      font-weight: 500;
    }

    /* Attendance */
    .attendance-stats {
      display: flex;
      justify-content: space-around;
    }

    .attendance-stat {
      text-align: center;
    }

    .stat-circle {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: #e2e8f0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      font-weight: 700;
      color: #475569;
      margin: 0 auto 0.5rem;
    }

    .stat-circle.alert {
      background: #fee2e2;
      color: #dc2626;
    }

    .stat-circle.warning {
      background: #fef3c7;
      color: #d97706;
    }

    .stat-name {
      font-size: 0.85rem;
      color: #64748b;
    }

    .attendance-message {
      text-align: center;
      margin-top: 1rem;
      padding: 0.75rem;
      background: #ecfdf5;
      border-radius: 8px;
      color: #047857;
    }

    .empty-state {
      text-align: center;
      padding: 2rem;
      color: #94a3b8;
    }

    /* Announcements */
    .announcements-section {
      margin-bottom: 2rem;
    }

    .announcements-section h2 {
      font-size: 1.1rem;
      color: #1e293b;
      margin: 0 0 1rem;
    }

    .announcements-list {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .announcement {
      display: flex;
      gap: 1.5rem;
      background: white;
      padding: 1.25rem;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .ann-date {
      font-size: 0.85rem;
      color: #64748b;
      white-space: nowrap;
    }

    .ann-content h3 {
      margin: 0 0 0.5rem;
      font-size: 1rem;
      color: #1e293b;
    }

    .ann-content p {
      margin: 0;
      font-size: 0.9rem;
      color: #64748b;
    }

    /* Quick Actions */
    .quick-actions {
      display: flex;
      gap: 1rem;
      justify-content: center;
    }

    .action-btn {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.5rem;
      padding: 1.25rem 2rem;
      background: white;
      border-radius: 12px;
      text-decoration: none;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      transition: all 0.3s;
    }

    .action-btn:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(79, 70, 229, 0.15);
    }

    .action-btn .icon {
      font-size: 1.75rem;
    }

    .action-btn .text {
      font-size: 0.85rem;
      color: #475569;
      font-weight: 500;
    }

    @media (max-width: 1024px) {
      .main-grid {
        grid-template-columns: 1fr;
      }
      
      .profile-header {
        flex-direction: column;
        gap: 1.5rem;
        text-align: center;
      }
      
      .profile-left {
        flex-direction: column;
      }
    }

    @media (max-width: 768px) {
      .quick-actions {
        flex-wrap: wrap;
      }
    }
  `]
})
export class StudentDashboardComponent implements OnInit {
  data: StudentData | null = null;
  defaultAvatar = '/assets/images/default-avatar.png';

  constructor(private http: HttpClient) {}

  ngOnInit() {
    this.loadDashboard();
  }

  loadDashboard() {
    this.http.get<StudentData>('/api/v1/dashboard/student')
      .subscribe({
        next: (data) => this.data = data,
        error: (err) => console.error('Error loading dashboard', err)
      });
  }

  getEvaluationType(type: string): string {
    const types: { [key: string]: string } = {
      'IO': 'Interro',
      'DV': 'Devoir',
      'CP': 'Composition',
      'TP': 'TP'
    };
    return types[type] || type;
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
