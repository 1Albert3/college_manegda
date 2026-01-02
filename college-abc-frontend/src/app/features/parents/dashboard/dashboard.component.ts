import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { ParentService, DashboardData, Child } from '../../../core/services/parent.service';

@Component({
  selector: 'app-parent-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule],
  template: `
    <div class="parent-dashboard">
      <!-- Header -->
      <div class="dashboard-header">
        <div class="welcome">
          <h1>Bienvenue sur le Portail Parents</h1>
          <p>Collège Privé Wend-Manegda - Suivez la scolarité de votre enfant</p>
        </div>
        <div class="date">
          {{ today | date:'EEEE dd MMMM yyyy':'':'fr' }}
        </div>
      </div>

      <!-- Sélecteur d'enfant (si plusieurs) -->
      <div class="child-selector" *ngIf="data?.children && data.children.length > 1">
        <span class="selector-label">Enfant sélectionné :</span>
        <div class="children-tabs">
          <button 
            *ngFor="let child of data.children"
            class="child-tab"
            [class.active]="selectedChildId === child.id"
            (click)="selectChild(child)">
            <img [src]="child.photo_url || defaultAvatar" class="child-avatar">
            <span class="child-name">{{ child.prenoms }}</span>
            <span class="child-class">{{ child.niveau }}</span>
          </button>
        </div>
      </div>

      <!-- Carte profil enfant -->
      <div class="profile-card" *ngIf="data?.current_child">
        <div class="profile-photo">
          <img [src]="data.current_child.photo_url || defaultAvatar" alt="Photo">
        </div>
        <div class="profile-info">
          <h2>{{ data.current_child.full_name }}</h2>
          <div class="profile-details">
            <span class="detail">
              <span class="label">Matricule:</span>
              {{ data.current_child.matricule }}
            </span>
            <span class="detail">
              <span class="label">Classe:</span>
              {{ data.current_child.class_name }}
            </span>
          </div>
        </div>
        <div class="profile-stats" *ngIf="data?.grades_summary">
          <div class="stat-item">
            <span class="stat-value" [class.good]="data.grades_summary.moyenne_generale >= 10">
              {{ data.grades_summary.moyenne_generale | number:'1.2-2' }}
            </span>
            <span class="stat-label">Moyenne</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">
              {{ data.grades_summary.rang }}<sup>e</sup>/{{ data.grades_summary.effectif }}
            </span>
            <span class="stat-label">Rang</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">T{{ data.grades_summary.trimestre }}</span>
            <span class="stat-label">Trimestre</span>
          </div>
        </div>
      </div>

      <!-- Grille principale -->
      <div class="main-grid">
        <!-- Notes récentes -->
        <div class="card grades-card">
          <div class="card-header">
            <h3>📊 Notes Récentes</h3>
            <a routerLink="/parents/grades">Voir tout →</a>
          </div>
          <div class="card-content">
            <div class="grades-list" *ngIf="data?.recent_grades?.length">
              <div class="grade-item" *ngFor="let grade of data.recent_grades">
                <div class="grade-subject">{{ grade.subject }}</div>
                <div class="grade-info">
                  <span class="grade-type">{{ getEvaluationType(grade.type) }}</span>
                  <span class="grade-date">{{ grade.date | date:'dd/MM' }}</span>
                </div>
                <div class="grade-value" [class.good]="grade.note >= 10" [class.bad]="grade.note < 10">
                  {{ grade.note | number:'1.1-1' }}/20
                </div>
              </div>
            </div>
            <div class="empty-message" *ngIf="!data?.recent_grades?.length">
              Aucune note récente
            </div>
          </div>
        </div>

        <!-- Absences -->
        <div class="card attendance-card">
          <div class="card-header">
            <h3>📅 Assiduité</h3>
            <a routerLink="/parents/attendance">Détails →</a>
          </div>
          <div class="card-content">
            <div class="attendance-summary" *ngIf="data?.attendance_summary">
              <div class="attendance-stat">
                <div class="stat-circle" [class.alert]="data.attendance_summary.absences > 5">
                  {{ data.attendance_summary.absences }}
                </div>
                <span>Absences</span>
              </div>
              <div class="attendance-stat">
                <div class="stat-circle" [class.warning]="data.attendance_summary.retards > 3">
                  {{ data.attendance_summary.retards }}
                </div>
                <span>Retards</span>
              </div>
              <div class="attendance-stat">
                <div class="stat-circle alert" *ngIf="data.attendance_summary.non_justifiees > 0">
                  {{ data.attendance_summary.non_justifiees }}
                </div>
                <span *ngIf="data.attendance_summary.non_justifiees > 0">Non justifiées</span>
              </div>
            </div>
            <div class="attendance-alert" *ngIf="data?.attendance_summary?.non_justifiees > 0">
              ⚠️ {{ data.attendance_summary.non_justifiees }} absence(s) à justifier
            </div>
          </div>
        </div>

        <!-- Paiements -->
        <div class="card payment-card">
          <div class="card-header">
            <h3>💰 Situation Financière</h3>
            <a routerLink="/parents/payments">Voir plus →</a>
          </div>
          <div class="card-content" *ngIf="data?.payment_status">
            <div class="payment-progress">
              <div class="progress-bar">
                <div 
                  class="progress-fill" 
                  [style.width.%]="getPaymentPercentage()">
                </div>
              </div>
              <div class="progress-labels">
                <span>{{ data.payment_status.paid | number }} FCFA payé</span>
                <span>{{ data.payment_status.total | number }} FCFA total</span>
              </div>
            </div>
            <div class="payment-remaining" *ngIf="data.payment_status.remaining > 0">
              <span class="amount">{{ data.payment_status.remaining | number }} FCFA</span>
              restant à payer
              <span class="deadline" *ngIf="data.payment_status.next_deadline">
                (avant le {{ data.payment_status.next_deadline | date:'dd/MM/yyyy' }})
              </span>
            </div>
            <div class="payment-complete" *ngIf="data.payment_status.remaining === 0">
              ✅ Scolarité entièrement payée
            </div>
          </div>
        </div>

        <!-- Messages -->
        <div class="card messages-card">
          <div class="card-header">
            <h3>📨 Messagerie</h3>
            <a routerLink="/parents/messages">Ouvrir →</a>
          </div>
          <div class="card-content">
            <div class="messages-badge" *ngIf="data?.unread_messages > 0">
              <span class="badge-number">{{ data.unread_messages }}</span>
              <span class="badge-text">nouveau(x) message(s)</span>
            </div>
            <div class="no-messages" *ngIf="!data?.unread_messages">
              Aucun nouveau message
            </div>
          </div>
        </div>
      </div>

      <!-- Actions rapides -->
      <div class="quick-actions">
        <h3>Actions rapides</h3>
        <div class="actions-grid">
          <a routerLink="/parents/bulletins" class="action-card">
            <span class="action-icon">📄</span>
            <span class="action-text">Bulletins</span>
          </a>
          <a routerLink="/parents/homework" class="action-card">
            <span class="action-icon">📝</span>
            <span class="action-text">Devoirs</span>
          </a>
          <a routerLink="/parents/schedule" class="action-card">
            <span class="action-icon">📆</span>
            <span class="action-text">Emploi du temps</span>
          </a>
          <a routerLink="/parents/appointments" class="action-card">
            <span class="action-icon">📅</span>
            <span class="action-text">RDV Professeur</span>
          </a>
          <a routerLink="/parents/invoices" class="action-card">
            <span class="action-icon">🧾</span>
            <span class="action-text">Factures</span>
          </a>
          <a routerLink="/parents/documents" class="action-card">
            <span class="action-icon">📁</span>
            <span class="action-text">Documents</span>
          </a>
        </div>
      </div>

      <!-- Événements à venir -->
      <div class="events-section" *ngIf="data?.upcoming_events?.length">
        <h3>📅 Prochains événements</h3>
        <div class="events-list">
          <div class="event-item" *ngFor="let event of data.upcoming_events">
            <div class="event-date">
              <span class="event-day">{{ event.date | date:'dd' }}</span>
              <span class="event-month">{{ event.date | date:'MMM':'':'fr' }}</span>
            </div>
            <div class="event-info">
              <span class="event-title">{{ event.title }}</span>
              <span class="event-type">{{ event.type }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .parent-dashboard {
      padding: 1.5rem 2rem;
      max-width: 1200px;
      margin: 0 auto;
      background: #f8fafc;
      min-height: 100vh;
    }

    .dashboard-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
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

    .date {
      color: #64748b;
      font-size: 0.9rem;
    }

    /* Child Selector */
    .child-selector {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1.5rem;
      padding: 1rem;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    }

    .selector-label {
      color: #64748b;
      font-size: 0.9rem;
    }

    .children-tabs {
      display: flex;
      gap: 0.75rem;
    }

    .child-tab {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border: 2px solid #e2e8f0;
      border-radius: 25px;
      background: white;
      cursor: pointer;
      transition: all 0.3s;
    }

    .child-tab.active {
      border-color: #4f46e5;
      background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    }

    .child-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
    }

    .child-name {
      font-weight: 500;
      color: #1e293b;
    }

    .child-class {
      font-size: 0.75rem;
      color: #64748b;
      padding: 0.125rem 0.5rem;
      background: #f1f5f9;
      border-radius: 10px;
    }

    /* Profile Card */
    .profile-card {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      padding: 1.5rem;
      background: linear-gradient(135deg, #4f46e5, #6366f1);
      border-radius: 16px;
      color: white;
      margin-bottom: 2rem;
      box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
    }

    .profile-photo img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      border: 3px solid rgba(255, 255, 255, 0.3);
      object-fit: cover;
    }

    .profile-info {
      flex: 1;
    }

    .profile-info h2 {
      margin: 0 0 0.5rem;
      font-size: 1.35rem;
    }

    .profile-details {
      display: flex;
      gap: 1.5rem;
      opacity: 0.9;
    }

    .profile-details .label {
      opacity: 0.7;
      margin-right: 0.25rem;
    }

    .profile-stats {
      display: flex;
      gap: 2rem;
    }

    .stat-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 0.75rem 1rem;
      background: rgba(255, 255, 255, 0.15);
      border-radius: 12px;
    }

    .stat-item .stat-value {
      font-size: 1.5rem;
      font-weight: 700;
    }

    .stat-item .stat-value.good {
      color: #86efac;
    }

    .stat-item .stat-label {
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
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 1.25rem;
      border-bottom: 1px solid #f1f5f9;
    }

    .card-header h3 {
      margin: 0;
      font-size: 1rem;
      color: #1e293b;
    }

    .card-header a {
      color: #4f46e5;
      text-decoration: none;
      font-size: 0.85rem;
    }

    .card-content {
      padding: 1.25rem;
    }

    /* Grades Card */
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

    .grade-info {
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

    .grade-value {
      font-weight: 700;
      font-size: 1.1rem;
    }

    .grade-value.good { color: #10b981; }
    .grade-value.bad { color: #ef4444; }

    /* Attendance Card */
    .attendance-summary {
      display: flex;
      justify-content: space-around;
      margin-bottom: 1rem;
    }

    .attendance-stat {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.5rem;
    }

    .stat-circle {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      font-weight: 700;
      background: #e2e8f0;
      color: #475569;
    }

    .stat-circle.alert {
      background: #fee2e2;
      color: #dc2626;
    }

    .stat-circle.warning {
      background: #fef3c7;
      color: #d97706;
    }

    .attendance-alert {
      background: #fef3c7;
      color: #92400e;
      padding: 0.75rem;
      border-radius: 8px;
      font-size: 0.9rem;
      text-align: center;
    }

    /* Payment Card */
    .payment-progress {
      margin-bottom: 1rem;
    }

    .progress-bar {
      height: 12px;
      background: #e2e8f0;
      border-radius: 6px;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #10b981, #34d399);
      border-radius: 6px;
      transition: width 0.5s ease;
    }

    .progress-labels {
      display: flex;
      justify-content: space-between;
      font-size: 0.75rem;
      color: #64748b;
      margin-top: 0.5rem;
    }

    .payment-remaining {
      text-align: center;
      color: #64748b;
    }

    .payment-remaining .amount {
      font-size: 1.25rem;
      font-weight: 700;
      color: #f59e0b;
    }

    .payment-remaining .deadline {
      display: block;
      font-size: 0.8rem;
      margin-top: 0.25rem;
    }

    .payment-complete {
      text-align: center;
      color: #10b981;
      font-weight: 500;
    }

    /* Messages Card */
    .messages-badge {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 1rem;
      background: linear-gradient(135deg, #4f46e5, #6366f1);
      border-radius: 10px;
      color: white;
    }

    .badge-number {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      font-weight: 700;
    }

    .no-messages {
      text-align: center;
      color: #94a3b8;
      padding: 1rem;
    }

    .empty-message {
      text-align: center;
      color: #94a3b8;
      padding: 1rem;
    }

    /* Quick Actions */
    .quick-actions {
      margin-bottom: 2rem;
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
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
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

    /* Events Section */
    .events-section h3 {
      font-size: 1.1rem;
      color: #1e293b;
      margin: 0 0 1rem;
    }

    .events-list {
      display: flex;
      gap: 1rem;
    }

    .event-item {
      display: flex;
      gap: 1rem;
      padding: 1rem;
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
      flex: 1;
    }

    .event-date {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 0.5rem;
      background: #eff6ff;
      border-radius: 8px;
      min-width: 50px;
    }

    .event-day {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1e40af;
    }

    .event-month {
      font-size: 0.7rem;
      color: #3b82f6;
      text-transform: uppercase;
    }

    .event-info {
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .event-title {
      font-weight: 500;
      color: #1e293b;
    }

    .event-type {
      font-size: 0.8rem;
      color: #64748b;
    }

    @media (max-width: 1024px) {
      .main-grid {
        grid-template-columns: 1fr;
      }
      
      .actions-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (max-width: 768px) {
      .profile-card {
        flex-direction: column;
        text-align: center;
      }
      
      .profile-stats {
        width: 100%;
        justify-content: center;
      }
      
      .actions-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .events-list {
        flex-direction: column;
      }
    }
  `]
})
export class ParentDashboardComponent implements OnInit {
  private parentService = inject(ParentService);
  
  data: DashboardData | null = null;
  selectedChildId: string = '';
  today = new Date();
  defaultAvatar = '/assets/images/default-avatar.png';

  ngOnInit() {
    this.loadDashboard();
  }

  loadDashboard() {
    this.parentService.getDashboard(this.selectedChildId || undefined)
      .subscribe({
        next: (data) => {
          this.data = data;
          if (!this.selectedChildId && data.current_child) {
            this.selectedChildId = data.current_child.id;
          }
        },
        error: (err) => console.error('Error loading dashboard', err)
      });
  }

  selectChild(child: Child) {
    this.selectedChildId = child.id;
    this.loadDashboard();
  }

  getPaymentPercentage(): number {
    if (!this.data?.payment_status?.total) return 0;
    return (this.data.payment_status.paid / this.data.payment_status.total) * 100;
  }

  getEvaluationType(type: string): string {
    const types: { [key: string]: string } = {
      'IO': 'Interro',
      'DV': 'Devoir',
      'CP': 'Compo',
      'TP': 'TP'
    };
    return types[type] || type;
  }
}
