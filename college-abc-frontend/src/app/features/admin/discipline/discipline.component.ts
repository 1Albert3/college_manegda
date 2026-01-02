import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute, RouterModule } from '@angular/router';

interface Incident {
  id: string;
  student_id: string;
  student: {
    nom: string;
    prenoms: string;
    matricule: string;
  };
  class: {
    niveau: string;
    nom: string;
  };
  date_incident: string;
  heure_incident: string;
  lieu: string;
  type: string;
  gravite: string;
  description: string;
  statut: string;
}

interface Sanction {
  id: string;
  type: string;
  motif: string;
  date_effet: string;
  date_fin: string;
  duree_jours: number;
  parents_notifies: boolean;
}

@Component({
  selector: 'app-discipline',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  template: `
    <div class="discipline-container">
      <!-- Header -->
      <div class="page-header">
        <div class="header-left">
          <h1>⚖️ Gestion Disciplinaire</h1>
          <p>Incidents, sanctions et conseil de discipline</p>
        </div>
        <div class="header-actions">
          <button class="btn-primary" (click)="openNewIncident()">
            + Signaler un incident
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-row">
        <div class="stat-card warning">
          <span class="stat-icon">⚠️</span>
          <div class="stat-content">
            <span class="stat-value">{{ stats.incidents_pending }}</span>
            <span class="stat-label">En attente</span>
          </div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">📋</span>
          <div class="stat-content">
            <span class="stat-value">{{ stats.incidents }}</span>
            <span class="stat-label">Total incidents</span>
          </div>
        </div>
        <div class="stat-card danger">
          <span class="stat-icon">🚫</span>
          <div class="stat-content">
            <span class="stat-value">{{ stats.sanctions }}</span>
            <span class="stat-label">Sanctions</span>
          </div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">📅</span>
          <div class="stat-content">
            <span class="stat-value">{{ stats.jours_exclusion }}</span>
            <span class="stat-label">Jours d'exclusion</span>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs">
        <button [class.active]="activeTab === 'incidents'" (click)="activeTab = 'incidents'">
          Incidents
        </button>
        <button [class.active]="activeTab === 'sanctions'" (click)="activeTab = 'sanctions'">
          Sanctions
        </button>
        <button [class.active]="activeTab === 'stats'" (click)="activeTab = 'stats'">
          Statistiques
        </button>
      </div>

      <!-- Incidents Tab -->
      <div class="tab-content" *ngIf="activeTab === 'incidents'">
        <!-- Filters -->
        <div class="filters-bar">
          <select [(ngModel)]="filters.statut" (change)="loadIncidents()">
            <option value="">Tous les statuts</option>
            <option value="signale">Signalé</option>
            <option value="en_cours">En cours</option>
            <option value="traite">Traité</option>
          </select>
          <select [(ngModel)]="filters.gravite" (change)="loadIncidents()">
            <option value="">Toutes gravités</option>
            <option value="mineure">Mineure</option>
            <option value="moyenne">Moyenne</option>
            <option value="grave">Grave</option>
            <option value="tres_grave">Très grave</option>
          </select>
          <select [(ngModel)]="filters.type" (change)="loadIncidents()">
            <option value="">Tous les types</option>
            <option value="comportement">Mauvais comportement</option>
            <option value="violence">Violence</option>
            <option value="retards_repetes">Retards répétés</option>
            <option value="tricherie">Tricherie</option>
            <option value="insolence">Insolence</option>
          </select>
        </div>

        <!-- Incidents List -->
        <div class="incidents-list">
          <div class="incident-card" 
               *ngFor="let incident of incidents"
               [class]="'gravity-' + incident.gravite">
            <div class="incident-header">
              <div class="student-info">
                <span class="student-name">{{ incident.student.prenoms }} {{ incident.student.nom }}</span>
                <span class="student-class">{{ incident.class.niveau }} {{ incident.class.nom }}</span>
              </div>
              <div class="incident-meta">
                <span class="incident-date">{{ incident.date_incident | date:'dd/MM/yyyy' }}</span>
                <span class="status-badge" [class]="incident.statut">{{ getStatusLabel(incident.statut) }}</span>
              </div>
            </div>
            <div class="incident-body">
              <div class="incident-tags">
                <span class="tag type">{{ getTypeLabel(incident.type) }}</span>
                <span class="tag gravity" [class]="incident.gravite">{{ getGravityLabel(incident.gravite) }}</span>
                <span class="tag lieu">📍 {{ incident.lieu }}</span>
              </div>
              <p class="incident-desc">{{ incident.description }}</p>
            </div>
            <div class="incident-actions">
              <button class="btn-small" (click)="viewIncident(incident)">Voir</button>
              <button class="btn-small primary" 
                      (click)="openSanctionForm(incident)"
                      *ngIf="incident.statut !== 'traite'">
                Sanctionner
              </button>
              <button class="btn-small" 
                      (click)="updateStatus(incident, 'traite')"
                      *ngIf="incident.statut === 'en_cours'">
                Classer
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Tab -->
      <div class="tab-content" *ngIf="activeTab === 'stats'">
        <div class="stats-grid">
          <!-- Par type -->
          <div class="stat-panel">
            <h3>Incidents par type</h3>
            <div class="stat-bars">
              <div class="bar-item" *ngFor="let item of statsByType">
                <span class="bar-label">{{ getTypeLabel(item.type) }}</span>
                <div class="bar-track">
                  <div class="bar-fill" [style.width.%]="(item.count / maxIncidents) * 100"></div>
                </div>
                <span class="bar-value">{{ item.count }}</span>
              </div>
            </div>
          </div>

          <!-- Top élèves -->
          <div class="stat-panel">
            <h3>Élèves avec le plus d'incidents</h3>
            <div class="top-list">
              <div class="top-item" *ngFor="let item of topStudents; let i = index">
                <span class="rank">{{ i + 1 }}</span>
                <span class="name">{{ item.student?.prenoms }} {{ item.student?.nom }}</span>
                <span class="count">{{ item.incidents_count }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- New Incident Modal -->
      <div class="modal-overlay" *ngIf="showIncidentModal" (click)="closeIncidentModal()">
        <div class="modal-content large" (click)="$event.stopPropagation()">
          <div class="modal-header">
            <h2>Signaler un incident</h2>
            <button class="btn-close" (click)="closeIncidentModal()">×</button>
          </div>
          <div class="modal-body">
            <div class="form-row">
              <div class="form-group">
                <label>Élève *</label>
                <select [(ngModel)]="incidentForm.student_id">
                  <option value="">Sélectionner un élève...</option>
                  <option *ngFor="let s of students" [value]="s.id">
                    {{ s.matricule }} - {{ s.nom }} {{ s.prenoms }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>Classe *</label>
                <select [(ngModel)]="incidentForm.class_id">
                  <option value="">Sélectionner...</option>
                  <option *ngFor="let c of classes" [value]="c.id">
                    {{ c.niveau }} {{ c.nom }}
                  </option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Date *</label>
                <input type="date" [(ngModel)]="incidentForm.date_incident">
              </div>
              <div class="form-group">
                <label>Heure</label>
                <input type="time" [(ngModel)]="incidentForm.heure_incident">
              </div>
              <div class="form-group">
                <label>Lieu *</label>
                <input type="text" [(ngModel)]="incidentForm.lieu" placeholder="Salle, cour, cantine...">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Type *</label>
                <select [(ngModel)]="incidentForm.type">
                  <option value="comportement">Mauvais comportement</option>
                  <option value="violence">Violence</option>
                  <option value="retards_repetes">Retards répétés</option>
                  <option value="absences">Absences injustifiées</option>
                  <option value="tricherie">Tricherie</option>
                  <option value="degradation">Dégradation</option>
                  <option value="insolence">Insolence</option>
                  <option value="tenue">Non-respect tenue</option>
                  <option value="autre">Autre</option>
                </select>
              </div>
              <div class="form-group">
                <label>Gravité *</label>
                <select [(ngModel)]="incidentForm.gravite">
                  <option value="mineure">Mineure</option>
                  <option value="moyenne">Moyenne</option>
                  <option value="grave">Grave</option>
                  <option value="tres_grave">Très grave</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Description *</label>
              <textarea [(ngModel)]="incidentForm.description" rows="4"
                        placeholder="Décrivez l'incident en détail..."></textarea>
            </div>

            <div class="form-group">
              <label>Circonstances (optionnel)</label>
              <textarea [(ngModel)]="incidentForm.circonstances" rows="2"
                        placeholder="Contexte, témoins, etc."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn-secondary" (click)="closeIncidentModal()">Annuler</button>
            <button class="btn-primary" (click)="submitIncident()" [disabled]="!isIncidentValid()">
              Signaler
            </button>
          </div>
        </div>
      </div>

      <!-- Sanction Modal -->
      <div class="modal-overlay" *ngIf="showSanctionModal" (click)="closeSanctionModal()">
        <div class="modal-content" (click)="$event.stopPropagation()">
          <div class="modal-header">
            <h2>Appliquer une sanction</h2>
            <button class="btn-close" (click)="closeSanctionModal()">×</button>
          </div>
          <div class="modal-body">
            <div class="incident-summary" *ngIf="selectedIncident">
              <p><strong>Élève:</strong> {{ selectedIncident.student.prenoms }} {{ selectedIncident.student.nom }}</p>
              <p><strong>Incident:</strong> {{ getTypeLabel(selectedIncident.type) }} - {{ selectedIncident.date_incident | date:'dd/MM/yyyy' }}</p>
            </div>

            <div class="form-group">
              <label>Type de sanction *</label>
              <select [(ngModel)]="sanctionForm.type">
                <option value="avertissement_oral">Avertissement oral</option>
                <option value="avertissement_ecrit">Avertissement écrit</option>
                <option value="blame">Blâme</option>
                <option value="retenue">Retenue</option>
                <option value="travail_interet_general">Travail d'intérêt général</option>
                <option value="exclusion_temporaire">Exclusion temporaire</option>
                <option value="conseil_discipline">Conseil de discipline</option>
              </select>
            </div>

            <div class="form-group">
              <label>Motif *</label>
              <textarea [(ngModel)]="sanctionForm.motif" rows="3"
                        placeholder="Motif de la sanction..."></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Date d'effet *</label>
                <input type="date" [(ngModel)]="sanctionForm.date_effet">
              </div>
              <div class="form-group" *ngIf="sanctionForm.type === 'exclusion_temporaire'">
                <label>Durée (jours)</label>
                <input type="number" [(ngModel)]="sanctionForm.duree_jours" min="1" max="8">
              </div>
            </div>

            <div class="form-group">
              <label>Niveau de décision *</label>
              <select [(ngModel)]="sanctionForm.niveau_decision">
                <option value="enseignant">Enseignant</option>
                <option value="censorat">Censorat</option>
                <option value="direction">Direction</option>
              </select>
            </div>

            <div class="form-group checkbox">
              <label>
                <input type="checkbox" [(ngModel)]="sanctionForm.notifier_parents">
                Notifier les parents par SMS
              </label>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn-secondary" (click)="closeSanctionModal()">Annuler</button>
            <button class="btn-danger" (click)="submitSanction()" [disabled]="!isSanctionValid()">
              Appliquer la sanction
            </button>
          </div>
        </div>
      </div>

      <!-- Toast -->
      <div class="toast" *ngIf="message" [class]="messageType">
        {{ message }}
      </div>
    </div>
  `,
  styles: [`
    .discipline-container {
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

    .btn-primary {
      background: linear-gradient(135deg, #dc2626, #ef4444);
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
    }

    /* Stats */
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
      border-left: 4px solid #e2e8f0;
    }

    .stat-card.warning { border-left-color: #f59e0b; }
    .stat-card.danger { border-left-color: #ef4444; }

    .stat-icon { font-size: 1.5rem; }
    .stat-value { font-size: 1.5rem; font-weight: 700; color: #1e293b; display: block; }
    .stat-label { font-size: 0.8rem; color: #64748b; }

    /* Tabs */
    .tabs {
      display: flex;
      gap: 0.5rem;
      margin-bottom: 1.5rem;
      background: #f1f5f9;
      padding: 0.25rem;
      border-radius: 8px;
      width: fit-content;
    }

    .tabs button {
      padding: 0.625rem 1.25rem;
      border: none;
      background: transparent;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      color: #64748b;
    }

    .tabs button.active {
      background: white;
      color: #1e293b;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    /* Filters */
    .filters-bar {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .filters-bar select {
      padding: 0.625rem 1rem;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      min-width: 160px;
    }

    /* Incidents List */
    .incidents-list {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .incident-card {
      background: white;
      border-radius: 12px;
      padding: 1.25rem;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      border-left: 4px solid #e2e8f0;
    }

    .incident-card.gravity-mineure { border-left-color: #fef9c3; background: #fffbeb; }
    .incident-card.gravity-moyenne { border-left-color: #fed7aa; background: #fff7ed; }
    .incident-card.gravity-grave { border-left-color: #fecaca; background: #fef2f2; }
    .incident-card.gravity-tres_grave { border-left-color: #ef4444; background: #fee2e2; }

    .incident-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.75rem;
    }

    .student-name {
      font-weight: 600;
      color: #1e293b;
    }

    .student-class {
      font-size: 0.85rem;
      color: #64748b;
      margin-left: 0.5rem;
    }

    .incident-meta {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .incident-date {
      font-size: 0.85rem;
      color: #64748b;
    }

    .status-badge {
      font-size: 0.75rem;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-weight: 500;
    }

    .status-badge.signale { background: #fef3c7; color: #92400e; }
    .status-badge.en_cours { background: #dbeafe; color: #1e40af; }
    .status-badge.traite { background: #dcfce7; color: #166534; }

    .incident-tags {
      display: flex;
      gap: 0.5rem;
      margin-bottom: 0.75rem;
    }

    .tag {
      font-size: 0.75rem;
      padding: 0.25rem 0.75rem;
      border-radius: 15px;
      background: #f1f5f9;
    }

    .tag.gravity.mineure { background: #fef9c3; }
    .tag.gravity.moyenne { background: #fed7aa; }
    .tag.gravity.grave { background: #fecaca; }
    .tag.gravity.tres_grave { background: #ef4444; color: white; }

    .incident-desc {
      font-size: 0.9rem;
      color: #475569;
      margin: 0;
      line-height: 1.5;
    }

    .incident-actions {
      display: flex;
      gap: 0.5rem;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid #f1f5f9;
    }

    .btn-small {
      padding: 0.375rem 0.875rem;
      border: 1px solid #e2e8f0;
      background: white;
      border-radius: 6px;
      font-size: 0.85rem;
      cursor: pointer;
    }

    .btn-small.primary {
      background: #4f46e5;
      color: white;
      border: none;
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.5rem;
    }

    .stat-panel {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .stat-panel h3 {
      margin: 0 0 1rem;
      font-size: 1rem;
      color: #1e293b;
    }

    .bar-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 0.75rem;
    }

    .bar-label {
      width: 120px;
      font-size: 0.85rem;
      color: #475569;
    }

    .bar-track {
      flex: 1;
      height: 8px;
      background: #e2e8f0;
      border-radius: 4px;
      overflow: hidden;
    }

    .bar-fill {
      height: 100%;
      background: linear-gradient(135deg, #ef4444, #f87171);
      border-radius: 4px;
    }

    .bar-value {
      width: 30px;
      font-weight: 600;
      color: #1e293b;
    }

    .top-list {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .top-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.75rem;
      background: #f8fafc;
      border-radius: 8px;
    }

    .rank {
      width: 24px;
      height: 24px;
      background: #ef4444;
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .top-item .name {
      flex: 1;
      font-weight: 500;
    }

    .top-item .count {
      font-weight: 700;
      color: #ef4444;
    }

    /* Modal */
    .modal-overlay {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }

    .modal-content {
      background: white;
      border-radius: 16px;
      width: 100%;
      max-width: 500px;
      max-height: 90vh;
      overflow: auto;
    }

    .modal-content.large {
      max-width: 700px;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid #e2e8f0;
    }

    .modal-header h2 { margin: 0; font-size: 1.25rem; }
    .btn-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b; }

    .modal-body { padding: 1.5rem; }
    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 0.75rem;
      padding: 1rem 1.5rem;
      border-top: 1px solid #e2e8f0;
    }

    .form-row {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1rem;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    .form-group label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: #475569;
      margin-bottom: 0.5rem;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      font-size: 0.95rem;
    }

    .form-group.checkbox label {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-secondary {
      background: white;
      border: 1px solid #e2e8f0;
      padding: 0.625rem 1.25rem;
      border-radius: 8px;
      cursor: pointer;
    }

    .btn-danger {
      background: #ef4444;
      color: white;
      border: none;
      padding: 0.625rem 1.25rem;
      border-radius: 8px;
      cursor: pointer;
    }

    .incident-summary {
      background: #f8fafc;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
    }

    .incident-summary p { margin: 0.25rem 0; font-size: 0.9rem; }

    .toast {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      padding: 1rem 1.5rem;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .toast.success { background: #ecfdf5; color: #047857; }
    .toast.error { background: #fef2f2; color: #dc2626; }

    @media (max-width: 1024px) {
      .stats-row, .stats-grid, .form-row {
        grid-template-columns: 1fr;
      }
    }
  `]
})
export class DisciplineComponent implements OnInit {
  activeTab = 'incidents';
  incidents: Incident[] = [];
  students: any[] = [];
  classes: any[] = [];

  stats = {
    incidents: 0,
    incidents_pending: 0,
    sanctions: 0,
    jours_exclusion: 0
  };

  statsByType: any[] = [];
  topStudents: any[] = [];
  maxIncidents = 1;

  filters = {
    statut: '',
    gravite: '',
    type: ''
  };

  showIncidentModal = false;
  showSanctionModal = false;
  selectedIncident: Incident | null = null;

  incidentForm = {
    student_id: '',
    class_id: '',
    date_incident: new Date().toISOString().split('T')[0],
    heure_incident: '',
    lieu: '',
    type: 'comportement',
    gravite: 'moyenne',
    description: '',
    circonstances: ''
  };

  sanctionForm = {
    type: 'avertissement_ecrit',
    motif: '',
    date_effet: new Date().toISOString().split('T')[0],
    duree_jours: 1,
    niveau_decision: 'censorat',
    notifier_parents: true
  };

  message = '';
  messageType = '';

  constructor(private http: HttpClient) {}

  ngOnInit() {
    this.loadIncidents();
    this.loadStats();
    this.loadStudents();
    this.loadClasses();
  }

  loadIncidents() {
    const params: any = {};
    if (this.filters.statut) params.statut = this.filters.statut;
    if (this.filters.gravite) params.gravite = this.filters.gravite;
    if (this.filters.type) params.type = this.filters.type;

    this.http.get<any>('/api/discipline/incidents', { params })
      .subscribe({
        next: (res) => this.incidents = res.data || res
      });
  }

  loadStats() {
    this.http.get<any>('/api/discipline/stats')
      .subscribe({
        next: (res) => {
          this.stats = res.totals || this.stats;
          this.statsByType = Object.entries(res.by_type || {}).map(([type, data]: any) => ({
            type,
            count: data.count || data
          }));
          this.maxIncidents = Math.max(...this.statsByType.map(s => s.count), 1);
        }
      });

    this.http.get<any>('/api/discipline/top-incidents?limit=5')
      .subscribe({
        next: (res) => this.topStudents = res
      });
  }

  loadStudents() {
    this.http.get<any>('/api/college/students?active=true')
      .subscribe({
        next: (res) => this.students = res.data || res
      });
  }

  loadClasses() {
    this.http.get<any>('/api/college/classes?active=true')
      .subscribe({
        next: (res) => this.classes = res.data || res
      });
  }

  getTypeLabel(type: string): string {
    const types: any = {
      comportement: 'Mauvais comportement',
      violence: 'Violence',
      retards_repetes: 'Retards répétés',
      absences: 'Absences',
      tricherie: 'Tricherie',
      degradation: 'Dégradation',
      insolence: 'Insolence',
      tenue: 'Tenue',
      autre: 'Autre'
    };
    return types[type] || type;
  }

  getGravityLabel(gravity: string): string {
    const labels: any = {
      mineure: 'Mineure',
      moyenne: 'Moyenne',
      grave: 'Grave',
      tres_grave: 'Très grave'
    };
    return labels[gravity] || gravity;
  }

  getStatusLabel(status: string): string {
    const labels: any = {
      signale: 'Signalé',
      en_cours: 'En cours',
      traite: 'Traité',
      classe: 'Classé'
    };
    return labels[status] || status;
  }

  openNewIncident() {
    this.incidentForm = {
      student_id: '',
      class_id: '',
      date_incident: new Date().toISOString().split('T')[0],
      heure_incident: '',
      lieu: '',
      type: 'comportement',
      gravite: 'moyenne',
      description: '',
      circonstances: ''
    };
    this.showIncidentModal = true;
  }

  closeIncidentModal() {
    this.showIncidentModal = false;
  }

  isIncidentValid(): boolean {
    return !!(
      this.incidentForm.student_id &&
      this.incidentForm.class_id &&
      this.incidentForm.date_incident &&
      this.incidentForm.lieu &&
      this.incidentForm.description.length >= 20
    );
  }

  submitIncident() {
    if (!this.isIncidentValid()) return;

    this.http.post('/api/discipline/incidents', this.incidentForm)
      .subscribe({
        next: (res: any) => {
          this.showMessage('Incident signalé avec succès', 'success');
          this.closeIncidentModal();
          this.loadIncidents();
          this.loadStats();
        },
        error: () => this.showMessage('Erreur lors de l\'enregistrement', 'error')
      });
  }

  viewIncident(incident: Incident) {
    // TODO: Navigate to detail page
  }

  openSanctionForm(incident: Incident) {
    this.selectedIncident = incident;
    this.sanctionForm = {
      type: 'avertissement_ecrit',
      motif: '',
      date_effet: new Date().toISOString().split('T')[0],
      duree_jours: 1,
      niveau_decision: 'censorat',
      notifier_parents: true
    };
    this.showSanctionModal = true;
  }

  closeSanctionModal() {
    this.showSanctionModal = false;
    this.selectedIncident = null;
  }

  isSanctionValid(): boolean {
    return !!(
      this.sanctionForm.type &&
      this.sanctionForm.motif.length >= 10 &&
      this.sanctionForm.date_effet
    );
  }

  submitSanction() {
    if (!this.isSanctionValid() || !this.selectedIncident) return;

    const payload = {
      ...this.sanctionForm,
      incident_id: this.selectedIncident.id
    };

    this.http.post('/api/discipline/sanctions', payload)
      .subscribe({
        next: () => {
          this.showMessage('Sanction appliquée', 'success');
          this.closeSanctionModal();
          this.loadIncidents();
          this.loadStats();
        },
        error: () => this.showMessage('Erreur', 'error')
      });
  }

  updateStatus(incident: Incident, status: string) {
    this.http.patch(`/api/discipline/incidents/${incident.id}/status`, { statut: status })
      .subscribe({
        next: () => {
          this.showMessage('Statut mis à jour', 'success');
          this.loadIncidents();
        }
      });
  }

  showMessage(msg: string, type: string) {
    this.message = msg;
    this.messageType = type;
    setTimeout(() => this.message = '', 3000);
  }
}
