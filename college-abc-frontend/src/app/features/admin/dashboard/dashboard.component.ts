import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { Subject, takeUntil, interval } from 'rxjs';
import { environment } from '../../../../environments/environment';

interface DashboardData {
  school_year: any;
  overview: {
    total_students: number;
    students_mp: number;
    students_college: number;
    students_lycee: number;
    total_teachers: number;
    total_staff: number;
    total_classes: number;
  };
  enrollments: {
    pending: number;
    validated: number;
    rejected: number;
    total: number;
    by_regime: { [key: string]: number };
    with_scholarship: number;
  };
  classes: {
    total: number;
    almost_full: number;
    full: number;
    fill_rate: number;
    total_capacity: number;
    total_enrolled: number;
  };
  finance: {
    total_expected: number;
    total_collected: number;
    total_pending: number;
    recovery_rate: number;
    total_scholarships: number;
  };
  alerts: Alert[];
  recent_activity: Activity[];
}

interface Alert {
  type: 'warning' | 'info' | 'error' | 'success';
  icon: string;
  title: string;
  message: string;
  action: string;
  priority: 'high' | 'medium' | 'low';
}

interface Activity {
  type: string;
  message: string;
  date: string;
}

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule],
  template: `
    <div class="dashboard-container">
      <!-- Header -->
      <div class="dashboard-header">
        <div class="header-left">
          <h1>Tableau de Bord Direction</h1>
          <p class="school-year" *ngIf="data?.school_year">
            Année scolaire {{ data.school_year.name }}
          </p>
        </div>
        <div class="header-right">
          <span class="last-update">
            Dernière mise à jour: {{ lastUpdate | date:'HH:mm:ss' }}
          </span>
          <button class="btn-refresh" (click)="loadDashboard()" [disabled]="isLoading">
            <span class="refresh-icon" [class.spinning]="isLoading">🔄</span>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div class="loading-state" *ngIf="isLoading && !data">
        <div class="loader"></div>
        <p>Chargement des données...</p>
      </div>

      <!-- Main Content -->
      <div class="dashboard-content" *ngIf="data">
        <!-- Stats Cards Row -->
        <div class="stats-row">
          <div class="stat-card students">
            <div class="stat-icon">👨‍🎓</div>
            <div class="stat-content">
              <div class="stat-value">{{ data.overview.total_students | number }}</div>
              <div class="stat-label">Élèves inscrits</div>
              <div class="stat-breakdown">
                <span>MP: {{ data.overview.students_mp }}</span>
                <span>Col: {{ data.overview.students_college }}</span>
                <span>Lyc: {{ data.overview.students_lycee }}</span>
              </div>
            </div>
          </div>

          <div class="stat-card teachers">
            <div class="stat-icon">👨‍🏫</div>
            <div class="stat-content">
              <div class="stat-value">{{ data.overview.total_teachers | number }}</div>
              <div class="stat-label">Enseignants</div>
            </div>
          </div>

          <div class="stat-card classes">
            <div class="stat-icon">🏫</div>
            <div class="stat-content">
              <div class="stat-value">{{ data.overview.total_classes | number }}</div>
              <div class="stat-label">Classes actives</div>
              <div class="stat-detail">
                Taux remplissage: {{ data.classes.fill_rate }}%
              </div>
            </div>
          </div>

          <div class="stat-card finance">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
              <div class="stat-value">{{ data.finance.recovery_rate }}%</div>
              <div class="stat-label">Taux recouvrement</div>
              <div class="stat-detail">
                {{ data.finance.total_collected | number }} / {{ data.finance.total_expected | number }} FCFA
              </div>
            </div>
          </div>
        </div>

        <!-- Alerts Section -->
        <div class="alerts-section" *ngIf="data.alerts && data.alerts.length > 0">
          <h2>🔔 Alertes et Actions Requises</h2>
          <div class="alerts-grid">
            <div 
              *ngFor="let alert of data.alerts" 
              class="alert-card"
              [class.high]="alert.priority === 'high'"
              [class.medium]="alert.priority === 'medium'"
              [class.low]="alert.priority === 'low'">
              <div class="alert-icon">
                <ng-container [ngSwitch]="alert.icon">
                  <span *ngSwitchCase="'clipboard-list'">📋</span>
                  <span *ngSwitchCase="'users'">👥</span>
                  <span *ngSwitchCase="'money'">💵</span>
                  <span *ngSwitchDefault>⚠️</span>
                </ng-container>
              </div>
              <div class="alert-content">
                <h3>{{ alert.title }}</h3>
                <p>{{ alert.message }}</p>
              </div>
              <a [routerLink]="alert.action" class="alert-action">
                Voir →
              </a>
            </div>
          </div>
        </div>

        <!-- Main Grid -->
        <div class="main-grid">
          <!-- Inscriptions Panel -->
          <div class="panel inscriptions-panel">
            <div class="panel-header">
              <h2>📝 Inscriptions</h2>
              <a routerLink="/admin/students/register" class="btn-small">+ Nouvelle</a>
            </div>
            <div class="panel-content">
              <div class="donut-chart">
                <svg viewBox="0 0 120 120">
                  <circle 
                    class="donut-ring" 
                    cx="60" cy="60" r="50" 
                    fill="transparent" 
                    stroke="#e2e8f0" 
                    stroke-width="15"/>
                  <circle 
                    class="donut-segment validated" 
                    cx="60" cy="60" r="50" 
                    fill="transparent" 
                    stroke="#10b981" 
                    stroke-width="15"
                    [attr.stroke-dasharray]="getDonutSegment('validated')"
                    stroke-dashoffset="0"/>
                  <circle 
                    class="donut-segment pending" 
                    cx="60" cy="60" r="50" 
                    fill="transparent" 
                    stroke="#f59e0b" 
                    stroke-width="15"
                    [attr.stroke-dasharray]="getDonutSegment('pending')"
                    [attr.stroke-dashoffset]="getDonutOffset('pending')"/>
                </svg>
                <div class="donut-center">
                  <span class="donut-value">{{ data.enrollments.total }}</span>
                  <span class="donut-label">Total</span>
                </div>
              </div>
              <div class="legend">
                <div class="legend-item">
                  <span class="dot validated"></span>
                  <span>Validées: {{ data.enrollments.validated }}</span>
                </div>
                <div class="legend-item">
                  <span class="dot pending"></span>
                  <span>En attente: {{ data.enrollments.pending }}</span>
                </div>
                <div class="legend-item">
                  <span class="dot rejected"></span>
                  <span>Refusées: {{ data.enrollments.rejected }}</span>
                </div>
              </div>
            </div>
            <div class="panel-footer">
              <a routerLink="/admin/validations">Voir les inscriptions en attente →</a>
            </div>
          </div>

          <!-- Classes Panel -->
          <div class="panel classes-panel">
            <div class="panel-header">
              <h2>🏫 Occupation des Classes</h2>
            </div>
            <div class="panel-content">
              <div class="capacity-bar">
                <div class="capacity-fill" [style.width.%]="data.classes.fill_rate"></div>
              </div>
              <div class="capacity-stats">
                <div class="cap-stat">
                  <span class="cap-value">{{ data.classes.total_enrolled }}</span>
                  <span class="cap-label">inscrits</span>
                </div>
                <div class="cap-stat">
                  <span class="cap-value">{{ data.classes.total_capacity }}</span>
                  <span class="cap-label">capacité</span>
                </div>
                <div class="cap-stat">
                  <span class="cap-value">{{ data.classes.total_capacity - data.classes.total_enrolled }}</span>
                  <span class="cap-label">places libres</span>
                </div>
              </div>
              <div class="warnings" *ngIf="data.classes.almost_full > 0 || data.classes.full > 0">
                <div class="warning-item" *ngIf="data.classes.full > 0">
                  🔴 {{ data.classes.full }} classe(s) complète(s)
                </div>
                <div class="warning-item" *ngIf="data.classes.almost_full > 0">
                  🟡 {{ data.classes.almost_full }} classe(s) presque pleine(s)
                </div>
              </div>
            </div>
            <div class="panel-footer">
              <a routerLink="/admin/academic">Gérer les classes →</a>
            </div>
          </div>

          <!-- Finance Panel -->
          <div class="panel finance-panel">
            <div class="panel-header">
              <h2>💰 Vue Financière</h2>
            </div>
            <div class="panel-content">
              <div class="finance-stats">
                <div class="finance-stat large">
                  <div class="finance-value expected">
                    {{ data.finance.total_expected | number }} <small>FCFA</small>
                  </div>
                  <div class="finance-label">Attendu</div>
                </div>
                <div class="finance-row">
                  <div class="finance-stat">
                    <div class="finance-value collected">
                      {{ data.finance.total_collected | number }}
                    </div>
                    <div class="finance-label">Collecté</div>
                  </div>
                  <div class="finance-stat">
                    <div class="finance-value pending">
                      {{ data.finance.total_pending | number }}
                    </div>
                    <div class="finance-label">En attente</div>
                  </div>
                </div>
                <div class="finance-stat highlight">
                  <div class="finance-value">
                    {{ data.finance.total_scholarships | number }} <small>FCFA</small>
                  </div>
                  <div class="finance-label">Bourses accordées</div>
                </div>
              </div>
            </div>
            <div class="panel-footer">
              <a routerLink="/admin/finance">Voir les détails financiers →</a>
            </div>
          </div>

          <!-- Recent Activity Panel -->
          <div class="panel activity-panel">
            <div class="panel-header">
              <h2>📊 Activité Récente</h2>
            </div>
            <div class="panel-content">
              <div class="activity-list">
                <div class="activity-item" *ngFor="let activity of data.recent_activity">
                  <div class="activity-icon">
                    <ng-container [ngSwitch]="activity.type">
                      <span *ngSwitchCase="'enrollment'">📝</span>
                      <span *ngSwitchCase="'grade'">📊</span>
                      <span *ngSwitchCase="'payment'">💳</span>
                      <span *ngSwitchDefault>📌</span>
                    </ng-container>
                  </div>
                  <div class="activity-content">
                    <p>{{ activity.message }}</p>
                    <span class="activity-time">{{ activity.date | date:'dd/MM HH:mm' }}</span>
                  </div>
                </div>
                <div class="no-activity" *ngIf="!data.recent_activity || data.recent_activity.length === 0">
                  Aucune activité récente
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
          <h2>⚡ Actions Rapides</h2>
          <div class="actions-grid">
            <a routerLink="/admin/students/register" class="action-btn">
              <span class="action-icon">📝</span>
              <span class="action-text">Nouvelle Inscription</span>
            </a>
            <a routerLink="/admin/grades/entry" class="action-btn">
              <span class="action-icon">📊</span>
              <span class="action-text">Saisir des Notes</span>
            </a>
            <a routerLink="/admin/grades/bulletins" class="action-btn">
              <span class="action-icon">📄</span>
              <span class="action-text">Générer Bulletins</span>
            </a>
            <a routerLink="/admin/messages" class="action-btn">
              <span class="action-icon">📨</span>
              <span class="action-text">Envoyer Message</span>
            </a>
            <a routerLink="/admin/reports" class="action-btn">
              <span class="action-icon">📈</span>
              <span class="action-text">Rapports</span>
            </a>
            <a routerLink="/admin/settings" class="action-btn">
              <span class="action-icon">⚙️</span>
              <span class="action-text">Paramètres</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div class="error-state" *ngIf="error">
        <span class="error-icon">❌</span>
        <p>{{ error }}</p>
        <button (click)="loadDashboard()">Réessayer</button>
      </div>
    </div>
  `,
  styles: [`
    .dashboard-container {
      padding: 1.5rem 2rem;
      min-height: 100vh;
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }

    .dashboard-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 2rem;
    }

    .dashboard-header h1 {
      font-size: 1.75rem;
      color: #1a365d;
      margin: 0 0 0.25rem 0;
    }

    .school-year {
      color: #64748b;
      font-size: 0.95rem;
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .last-update {
      color: #94a3b8;
      font-size: 0.85rem;
    }

    .btn-refresh {
      background: white;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 0.5rem;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-refresh:hover {
      background: #f1f5f9;
    }

    .refresh-icon {
      display: inline-block;
      font-size: 1.25rem;
    }

    .refresh-icon.spinning {
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    /* Loading State */
    .loading-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 60vh;
      gap: 1rem;
    }

    .loader {
      width: 50px;
      height: 50px;
      border: 4px solid #e2e8f0;
      border-top-color: #4f46e5;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    /* Stats Row */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: white;
      border-radius: 16px;
      padding: 1.5rem;
      display: flex;
      gap: 1rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s, box-shadow 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
      font-size: 2.5rem;
      display: flex;
      align-items: center;
    }

    .stat-content {
      flex: 1;
    }

    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: #1e293b;
      line-height: 1.2;
    }

    .stat-label {
      color: #64748b;
      font-size: 0.9rem;
      margin-top: 0.25rem;
    }

    .stat-breakdown {
      display: flex;
      gap: 0.75rem;
      margin-top: 0.5rem;
      font-size: 0.8rem;
      color: #94a3b8;
    }

    .stat-detail {
      font-size: 0.8rem;
      color: #94a3b8;
      margin-top: 0.5rem;
    }

    .stat-card.students {
      border-left: 4px solid #4f46e5;
    }

    .stat-card.teachers {
      border-left: 4px solid #10b981;
    }

    .stat-card.classes {
      border-left: 4px solid #f59e0b;
    }

    .stat-card.finance {
      border-left: 4px solid #ec4899;
    }

    /* Alerts Section */
    .alerts-section {
      margin-bottom: 2rem;
    }

    .alerts-section h2 {
      font-size: 1.1rem;
      color: #1e293b;
      margin-bottom: 1rem;
    }

    .alerts-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1rem;
    }

    .alert-card {
      background: white;
      border-radius: 12px;
      padding: 1rem 1.25rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      border-left: 4px solid #64748b;
    }

    .alert-card.high {
      border-left-color: #ef4444;
      background: linear-gradient(135deg, #fff 90%, #fef2f2 100%);
    }

    .alert-card.medium {
      border-left-color: #f59e0b;
      background: linear-gradient(135deg, #fff 90%, #fffbeb 100%);
    }

    .alert-card.low {
      border-left-color: #3b82f6;
    }

    .alert-icon {
      font-size: 1.5rem;
    }

    .alert-content {
      flex: 1;
    }

    .alert-content h3 {
      font-size: 0.95rem;
      color: #1e293b;
      margin: 0 0 0.25rem;
    }

    .alert-content p {
      font-size: 0.85rem;
      color: #64748b;
      margin: 0;
    }

    .alert-action {
      color: #4f46e5;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
      white-space: nowrap;
    }

    .alert-action:hover {
      text-decoration: underline;
    }

    /* Main Grid */
    .main-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .panel {
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid #f1f5f9;
    }

    .panel-header h2 {
      font-size: 1.1rem;
      color: #1e293b;
      margin: 0;
    }

    .btn-small {
      background: #4f46e5;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      font-size: 0.85rem;
      text-decoration: none;
      transition: background 0.3s;
    }

    .btn-small:hover {
      background: #4338ca;
    }

    .panel-content {
      padding: 1.5rem;
    }

    .panel-footer {
      padding: 1rem 1.5rem;
      border-top: 1px solid #f1f5f9;
      background: #fafbfc;
    }

    .panel-footer a {
      color: #4f46e5;
      text-decoration: none;
      font-size: 0.9rem;
    }

    .panel-footer a:hover {
      text-decoration: underline;
    }

    /* Donut Chart */
    .donut-chart {
      position: relative;
      width: 160px;
      height: 160px;
      margin: 0 auto 1.5rem;
    }

    .donut-chart svg {
      transform: rotate(-90deg);
    }

    .donut-center {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      text-align: center;
    }

    .donut-value {
      font-size: 1.75rem;
      font-weight: 700;
      color: #1e293b;
      display: block;
    }

    .donut-label {
      font-size: 0.8rem;
      color: #94a3b8;
    }

    .legend {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .legend-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.9rem;
      color: #64748b;
    }

    .dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
    }

    .dot.validated { background: #10b981; }
    .dot.pending { background: #f59e0b; }
    .dot.rejected { background: #ef4444; }

    /* Capacity Bar */
    .capacity-bar {
      height: 24px;
      background: #e2e8f0;
      border-radius: 12px;
      overflow: hidden;
      margin-bottom: 1.5rem;
    }

    .capacity-fill {
      height: 100%;
      background: linear-gradient(90deg, #10b981 0%, #3b82f6 50%, #f59e0b 100%);
      border-radius: 12px;
      transition: width 1s ease;
    }

    .capacity-stats {
      display: flex;
      justify-content: space-around;
      text-align: center;
      margin-bottom: 1rem;
    }

    .cap-stat {
      display: flex;
      flex-direction: column;
    }

    .cap-value {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1e293b;
    }

    .cap-label {
      font-size: 0.8rem;
      color: #94a3b8;
    }

    .warnings {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .warning-item {
      font-size: 0.85rem;
      color: #64748b;
    }

    /* Finance Stats */
    .finance-stats {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .finance-stat {
      text-align: center;
    }

    .finance-stat.large .finance-value {
      font-size: 2rem;
    }

    .finance-row {
      display: flex;
      justify-content: space-around;
    }

    .finance-value {
      font-size: 1.5rem;
      font-weight: 700;
    }

    .finance-value.expected { color: #1e293b; }
    .finance-value.collected { color: #10b981; }
    .finance-value.pending { color: #f59e0b; }

    .finance-value small {
      font-size: 0.8rem;
      font-weight: 400;
    }

    .finance-label {
      font-size: 0.85rem;
      color: #94a3b8;
    }

    .finance-stat.highlight {
      background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
      padding: 1rem;
      border-radius: 12px;
      margin-top: 0.5rem;
    }

    /* Activity List */
    .activity-list {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .activity-item {
      display: flex;
      gap: 1rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #f1f5f9;
    }

    .activity-item:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .activity-icon {
      font-size: 1.25rem;
    }

    .activity-content p {
      margin: 0 0 0.25rem;
      color: #334155;
      font-size: 0.9rem;
    }

    .activity-time {
      font-size: 0.75rem;
      color: #94a3b8;
    }

    .no-activity {
      text-align: center;
      color: #94a3b8;
      padding: 2rem;
    }

    /* Quick Actions */
    .quick-actions {
      margin-top: 2rem;
    }

    .quick-actions h2 {
      font-size: 1.1rem;
      color: #1e293b;
      margin-bottom: 1rem;
    }

    .actions-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 1rem;
    }

    .action-btn {
      background: white;
      border-radius: 12px;
      padding: 1.25rem 1rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.75rem;
      text-decoration: none;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      transition: all 0.3s;
    }

    .action-btn:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
      background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    }

    .action-icon {
      font-size: 2rem;
    }

    .action-text {
      font-size: 0.85rem;
      color: #334155;
      text-align: center;
      font-weight: 500;
    }

    /* Error State */
    .error-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 60vh;
      gap: 1rem;
    }

    .error-icon {
      font-size: 3rem;
    }

    .error-state button {
      background: #4f46e5;
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 8px;
      cursor: pointer;
    }

    /* Responsive */
    @media (max-width: 1200px) {
      .stats-row {
        grid-template-columns: repeat(2, 1fr);
      }

      .actions-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (max-width: 768px) {
      .dashboard-container {
        padding: 1rem;
      }

      .stats-row {
        grid-template-columns: 1fr;
      }

      .main-grid {
        grid-template-columns: 1fr;
      }

      .actions-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }
  `]
})
export class AdminDashboardComponent implements OnInit, OnDestroy {
  data: DashboardData | null = null;
  isLoading = false;
  error = '';
  lastUpdate = new Date();

  private destroy$ = new Subject<void>();

  constructor(private http: HttpClient) {}

  ngOnInit() {
    this.loadDashboard();

    // Rafraîchir toutes les 5 minutes
    interval(5 * 60 * 1000)
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => this.loadDashboard());
  }

  ngOnDestroy() {
    this.destroy$.next();
    this.destroy$.complete();
  }

  loadDashboard() {
    this.isLoading = true;
    this.error = '';

    this.http.get<any>(`${environment.apiUrl}/dashboard/direction`)
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          // Backend returns {data: ...} or directly the data
          const rawData = response.data || response;
          
          // Map the response to our expected format
          this.data = {
            school_year: rawData.school_year || null,
            overview: {
              total_students: rawData.overview?.total_students ?? 0,
              students_mp: rawData.overview?.students_mp ?? 0,
              students_college: rawData.overview?.students_college ?? 0,
              students_lycee: rawData.overview?.students_lycee ?? 0,
              total_teachers: rawData.overview?.total_teachers ?? 0,
              total_staff: rawData.overview?.total_staff ?? 0,
              total_classes: rawData.overview?.total_classes ?? 0,
            },
            enrollments: {
              pending: rawData.enrollment_stats?.pending ?? rawData.overview?.pending_enrollments ?? 0,
              validated: rawData.enrollment_stats?.validated ?? 0,
              rejected: rawData.enrollment_stats?.rejected ?? 0,
              total: rawData.enrollment_stats?.total ?? 0,
              by_regime: rawData.enrollment_stats?.by_regime ?? {},
              with_scholarship: rawData.enrollment_stats?.with_scholarship ?? 0,
            },
            classes: {
              total: rawData.class_stats?.total ?? rawData.overview?.total_classes ?? 0,
              almost_full: rawData.class_stats?.almost_full ?? 0,
              full: rawData.class_stats?.full ?? 0,
              fill_rate: rawData.class_stats?.fill_rate ?? 0,
              total_capacity: rawData.class_stats?.total_capacity ?? 0,
              total_enrolled: rawData.class_stats?.total_enrolled ?? 0,
            },
            finance: {
              total_expected: rawData.finance?.total_expected ?? 0,
              total_collected: rawData.finance?.total_payments ?? rawData.finance?.total_collected ?? 0,
              total_pending: rawData.finance?.total_pending ?? 0,
              recovery_rate: rawData.finance?.recovery_rate ?? 0,
              total_scholarships: rawData.finance?.total_scholarships ?? 0,
            },
            alerts: rawData.alerts ?? [],
            recent_activity: rawData.recent_activity ?? [],
          };
          
          this.isLoading = false;
          this.lastUpdate = new Date();
        },
        error: (err) => {
          console.error('Dashboard loading error:', err);
          this.error = err.error?.message || 'Erreur lors du chargement du dashboard';
          this.isLoading = false;
          
          // Load empty data to prevent infinite loading
          this.data = {
            school_year: null,
            overview: { total_students: 0, students_mp: 0, students_college: 0, students_lycee: 0, total_teachers: 0, total_staff: 0, total_classes: 0 },
            enrollments: { pending: 0, validated: 0, rejected: 0, total: 0, by_regime: {}, with_scholarship: 0 },
            classes: { total: 0, almost_full: 0, full: 0, fill_rate: 0, total_capacity: 0, total_enrolled: 0 },
            finance: { total_expected: 0, total_collected: 0, total_pending: 0, recovery_rate: 0, total_scholarships: 0 },
            alerts: [],
            recent_activity: [],
          };
        }
      });
  }

  // Calcul pour le graphique donut
  getDonutSegment(type: string): string {
    if (!this.data) return '0 314';
    
    const total = this.data.enrollments.total || 1;
    const circumference = 2 * Math.PI * 50; // r=50
    
    let value = 0;
    if (type === 'validated') {
      value = this.data.enrollments.validated;
    } else if (type === 'pending') {
      value = this.data.enrollments.pending;
    }
    
    const segmentLength = (value / total) * circumference;
    return `${segmentLength} ${circumference}`;
  }

  getDonutOffset(type: string): string {
    if (!this.data) return '0';
    
    const total = this.data.enrollments.total || 1;
    const circumference = 2 * Math.PI * 50;
    
    if (type === 'pending') {
      const validatedLength = (this.data.enrollments.validated / total) * circumference;
      return `-${validatedLength}`;
    }
    
    return '0';
  }
}
