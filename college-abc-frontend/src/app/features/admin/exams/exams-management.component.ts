import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';

interface Candidate {
  enrollment_id: string;
  student: {
    id: string;
    matricule: string;
    nom: string;
    prenoms: string;
    full_name: string;
    date_naissance: string;
    lieu_naissance: string;
    sexe: string;
    photo_url: string;
  };
  class: string;
  serie?: string;
  moyenne_annuelle: number;
  eligible: boolean;
  eligibility_notes: string[];
  dossier_status: string;
  selected?: boolean;
}


@Component({
  selector: 'app-exams-management',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="exams-container">
      <!-- Header -->
      <div class="page-header">
        <div class="header-left">
          <h1>🎓 Examens Nationaux</h1>
          <p>Gestion des candidatures CEP, BEPC, BAC</p>
        </div>
      </div>

      <!-- Exam Selector -->
      <div class="exam-tabs">
        <button 
          *ngFor="let exam of exams"
          [class.active]="selectedExam === exam.key"
          (click)="selectExam(exam.key)">
          <span class="exam-icon">{{ exam.icon }}</span>
          <span class="exam-name">{{ exam.name }}</span>
          <span class="exam-count">{{ exam.count }}</span>
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="stats-row">
        <div class="stat-card">
          <span class="stat-icon">👥</span>
          <div class="stat-content">
            <span class="stat-value">{{ stats.total }}</span>
            <span class="stat-label">Total candidats</span>
          </div>
        </div>
        <div class="stat-card green">
          <span class="stat-icon">✅</span>
          <div class="stat-content">
            <span class="stat-value">{{ stats.eligible }}</span>
            <span class="stat-label">Éligibles</span>
          </div>
        </div>
        <div class="stat-card warning">
          <span class="stat-icon">⚠️</span>
          <div class="stat-content">
            <span class="stat-value">{{ stats.non_eligible }}</span>
            <span class="stat-label">Non éligibles</span>
          </div>
        </div>
        <div class="stat-card blue">
          <span class="stat-icon">📁</span>
          <div class="stat-content">
            <span class="stat-value">{{ stats.dossier_complet }}</span>
            <span class="stat-label">Dossiers complets</span>
          </div>
        </div>
      </div>

      <!-- Filters & Actions -->
      <div class="actions-bar">
        <div class="filters">
          <input 
            type="text" 
            placeholder="Rechercher un candidat..."
            [(ngModel)]="searchQuery"
            (input)="filterCandidates()">
          <select [(ngModel)]="filterClass" (change)="loadCandidates()">
            <option value="">Toutes les classes</option>
            <option *ngFor="let c of classes" [value]="c.id">{{ c.full_name }}</option>
          </select>
          <select *ngIf="selectedExam === 'bac'" [(ngModel)]="filterSerie" (change)="loadCandidates()">
            <option value="">Toutes les séries</option>
            <option value="A">Série A</option>
            <option value="C">Série C</option>
            <option value="D">Série D</option>
          </select>
        </div>
        <div class="export-buttons">
          <button class="btn-secondary" (click)="exportCSV()">📥 Export CSV</button>
          <button class="btn-secondary" (click)="exportPDF()">📄 Export PDF</button>
          <button class="btn-primary" (click)="exportOfficial()">🏛️ Format Officiel</button>
        </div>
      </div>

      <!-- Candidates Table -->
      <div class="candidates-panel">
        <table class="candidates-table">
          <thead>
            <tr>
              <th><input type="checkbox" (change)="toggleAll($event)"></th>
              <th>Photo</th>
              <th>Matricule</th>
              <th>Nom & Prénom(s)</th>
              <th>Date/Lieu Naiss.</th>
              <th>Classe</th>
              <th *ngIf="selectedExam === 'bac'">Série</th>
              <th>Moyenne</th>
              <th>Éligibilité</th>
              <th>Dossier</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr *ngFor="let candidate of filteredCandidates; let i = index"
                [class.not-eligible]="!candidate.eligible">
              <td>
                <input type="checkbox" [(ngModel)]="candidate.selected">
              </td>
              <td>
                <img [src]="candidate.student.photo_url || defaultAvatar" 
                     class="candidate-photo" alt="Photo">
              </td>
              <td class="matricule">{{ candidate.student.matricule }}</td>
              <td class="name">
                <span class="full-name">{{ candidate.student.nom }} {{ candidate.student.prenoms }}</span>
                <span class="sexe">{{ candidate.student.sexe === 'M' ? '♂' : '♀' }}</span>
              </td>
              <td class="birth">
                <span class="date">{{ candidate.student.date_naissance }}</span>
                <span class="lieu">{{ candidate.student.lieu_naissance }}</span>
              </td>
              <td>{{ candidate.class }}</td>
              <td *ngIf="selectedExam === 'bac'" class="serie">{{ candidate.serie }}</td>
              <td class="moyenne" [class.good]="candidate.moyenne_annuelle >= 10">
                {{ candidate.moyenne_annuelle | number:'1.2-2' }}/20
              </td>
              <td>
                <span class="eligibility-badge" [class.eligible]="candidate.eligible">
                  {{ candidate.eligible ? '✅ Éligible' : '❌ Non éligible' }}
                </span>
                <ul class="eligibility-notes" *ngIf="candidate.eligibility_notes?.length">
                  <li *ngFor="let note of candidate.eligibility_notes">{{ note }}</li>
                </ul>
              </td>
              <td>
                <span class="dossier-badge" [class]="candidate.dossier_status">
                  {{ candidate.dossier_status === 'complet' ? '✓ Complet' : '⚠ Incomplet' }}
                </span>
              </td>
              <td>
                <button class="btn-icon" title="Voir détails" (click)="viewCandidate(candidate)">👁️</button>
                <button class="btn-icon" title="Imprimer fiche" (click)="printFiche(candidate)">🖨️</button>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="empty-state" *ngIf="filteredCandidates.length === 0">
          <span class="empty-icon">🎓</span>
          <p>Aucun candidat trouvé</p>
        </div>
      </div>

      <!-- Bulk Actions -->
      <div class="bulk-actions" *ngIf="selectedCount > 0">
        <span>{{ selectedCount }} candidat(s) sélectionné(s)</span>
        <button class="btn-secondary" (click)="exportSelected()">Exporter sélection</button>
        <button class="btn-secondary" (click)="printFiches()">Imprimer fiches</button>
      </div>

      <!-- Candidate Detail Modal -->
      <div class="modal-overlay" *ngIf="showDetailModal" (click)="closeDetailModal()">
        <div class="modal-content large" (click)="$event.stopPropagation()">
          <div class="modal-header">
            <h2>Dossier Candidat {{ getExamName() }}</h2>
            <button class="btn-close" (click)="closeDetailModal()">×</button>
          </div>
          <div class="modal-body" *ngIf="selectedCandidate">
            <div class="candidate-profile">
              <img [src]="selectedCandidate.student.photo_url || defaultAvatar" class="profile-photo">
              <div class="profile-info">
                <h3>{{ selectedCandidate.student.full_name }}</h3>
                <p>{{ selectedCandidate.student.matricule }}</p>
                <p>{{ selectedCandidate.class }} <span *ngIf="selectedCandidate.serie">- Série {{ selectedCandidate.serie }}</span></p>
              </div>
              <div class="profile-stats">
                <div class="profile-stat">
                  <span class="value">{{ selectedCandidate.moyenne_annuelle | number:'1.2-2' }}</span>
                  <span class="label">Moyenne</span>
                </div>
              </div>
            </div>

            <div class="section">
              <h4>📋 État civil</h4>
              <div class="info-grid">
                <div class="info-item">
                  <span class="label">Né(e) le</span>
                  <span class="value">{{ selectedCandidate.student.date_naissance }}</span>
                </div>
                <div class="info-item">
                  <span class="label">À</span>
                  <span class="value">{{ selectedCandidate.student.lieu_naissance }}</span>
                </div>
                <div class="info-item">
                  <span class="label">Sexe</span>
                  <span class="value">{{ selectedCandidate.student.sexe === 'M' ? 'Masculin' : 'Féminin' }}</span>
                </div>
              </div>
            </div>

            <div class="section">
              <h4>📁 Pièces du dossier</h4>
              <div class="pieces-list">
                <div class="piece-item" [class.complete]="true">
                  <span class="piece-icon">✓</span>
                  <span class="piece-name">Extrait de naissance</span>
                </div>
                <div class="piece-item" [class.complete]="selectedCandidate.student.photo_url">
                  <span class="piece-icon">{{ selectedCandidate.student.photo_url ? '✓' : '✗' }}</span>
                  <span class="piece-name">Photos d'identité</span>
                </div>
                <!-- Add more pieces based on exam type -->
              </div>
            </div>

            <div class="section" *ngIf="selectedCandidate.eligibility_notes?.length">
              <h4>⚠️ Points d'attention</h4>
              <ul class="attention-list">
                <li *ngFor="let note of selectedCandidate.eligibility_notes">{{ note }}</li>
              </ul>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn-secondary" (click)="closeDetailModal()">Fermer</button>
            <button class="btn-primary" (click)="printFiche(selectedCandidate)">🖨️ Imprimer fiche</button>
          </div>
        </div>
      </div>
    </div>
  `,
  styles: [`
    .exams-container {
      padding: 1.5rem 2rem;
      max-width: 1400px;
      margin: 0 auto;
    }

    .page-header {
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

    /* Exam Tabs */
    .exam-tabs {
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .exam-tabs button {
      flex: 1;
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1.25rem 1.5rem;
      background: white;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s;
    }

    .exam-tabs button.active {
      border-color: #4f46e5;
      background: linear-gradient(135deg, #eff6ff, #dbeafe);
    }

    .exam-icon {
      font-size: 2rem;
    }

    .exam-name {
      font-weight: 600;
      color: #1e293b;
      flex: 1;
    }

    .exam-count {
      font-size: 1.5rem;
      font-weight: 700;
      color: #4f46e5;
    }

    /* Stats */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 1.5rem;
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

    .stat-card.green { border-left-color: #10b981; }
    .stat-card.warning { border-left-color: #f59e0b; }
    .stat-card.blue { border-left-color: #3b82f6; }

    .stat-icon { font-size: 1.5rem; }
    .stat-value { font-size: 1.5rem; font-weight: 700; color: #1e293b; display: block; }
    .stat-label { font-size: 0.8rem; color: #64748b; }

    /* Actions Bar */
    .actions-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      background: white;
      padding: 1rem;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .filters {
      display: flex;
      gap: 1rem;
    }

    .filters input,
    .filters select {
      padding: 0.625rem 1rem;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
    }

    .filters input {
      width: 250px;
    }

    .export-buttons {
      display: flex;
      gap: 0.75rem;
    }

    .btn-secondary {
      background: white;
      border: 1px solid #e2e8f0;
      padding: 0.625rem 1rem;
      border-radius: 8px;
      cursor: pointer;
    }

    .btn-primary {
      background: linear-gradient(135deg, #4f46e5, #6366f1);
      color: white;
      border: none;
      padding: 0.625rem 1rem;
      border-radius: 8px;
      cursor: pointer;
    }

    /* Table */
    .candidates-panel {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      overflow: hidden;
    }

    .candidates-table {
      width: 100%;
      border-collapse: collapse;
    }

    .candidates-table th,
    .candidates-table td {
      padding: 0.875rem 1rem;
      text-align: left;
      border-bottom: 1px solid #f1f5f9;
    }

    .candidates-table th {
      background: #f8fafc;
      font-size: 0.75rem;
      font-weight: 600;
      color: #64748b;
      text-transform: uppercase;
    }

    .candidates-table tr:hover {
      background: #fafbfc;
    }

    .candidates-table tr.not-eligible {
      background: #fef2f2;
    }

    .candidate-photo {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
    }

    .matricule {
      font-family: monospace;
      font-weight: 600;
    }

    .name .full-name {
      font-weight: 500;
    }

    .name .sexe {
      margin-left: 0.5rem;
      color: #64748b;
    }

    .birth {
      display: flex;
      flex-direction: column;
    }

    .birth .lieu {
      font-size: 0.8rem;
      color: #64748b;
    }

    .serie {
      font-weight: 700;
      color: #4f46e5;
    }

    .moyenne {
      font-weight: 600;
    }

    .moyenne.good {
      color: #10b981;
    }

    .eligibility-badge {
      font-size: 0.8rem;
    }

    .eligibility-notes {
      margin: 0.25rem 0 0;
      padding-left: 1rem;
      font-size: 0.7rem;
      color: #ef4444;
    }

    .dossier-badge {
      font-size: 0.8rem;
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
    }

    .dossier-badge.complet {
      background: #dcfce7;
      color: #166534;
    }

    .dossier-badge.incomplet {
      background: #fef3c7;
      color: #92400e;
    }

    .btn-icon {
      background: none;
      border: none;
      padding: 0.25rem;
      cursor: pointer;
      font-size: 1rem;
    }

    /* Bulk Actions */
    .bulk-actions {
      position: fixed;
      bottom: 2rem;
      left: 50%;
      transform: translateX(-50%);
      background: #1e293b;
      color: white;
      padding: 1rem 2rem;
      border-radius: 12px;
      display: flex;
      align-items: center;
      gap: 1.5rem;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }

    .bulk-actions button {
      background: rgba(255, 255, 255, 0.2);
      border: none;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      cursor: pointer;
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
      max-width: 600px;
      max-height: 90vh;
      overflow: auto;
    }

    .modal-content.large {
      max-width: 800px;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid #e2e8f0;
    }

    .modal-header h2 { margin: 0; font-size: 1.25rem; }
    .btn-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; }

    .modal-body { padding: 1.5rem; }

    .candidate-profile {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      padding: 1.5rem;
      background: linear-gradient(135deg, #4f46e5, #6366f1);
      border-radius: 12px;
      color: white;
      margin-bottom: 1.5rem;
    }

    .profile-photo {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      border: 3px solid rgba(255, 255, 255, 0.3);
    }

    .profile-info { flex: 1; }
    .profile-info h3 { margin: 0; font-size: 1.25rem; }
    .profile-info p { margin: 0.25rem 0 0; opacity: 0.9; }

    .profile-stats {
      text-align: center;
    }

    .profile-stat .value {
      font-size: 2rem;
      font-weight: 700;
      display: block;
    }

    .profile-stat .label {
      font-size: 0.85rem;
      opacity: 0.8;
    }

    .section {
      margin-bottom: 1.5rem;
    }

    .section h4 {
      margin: 0 0 1rem;
      font-size: 1rem;
      color: #1e293b;
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1rem;
    }

    .info-item {
      background: #f8fafc;
      padding: 0.75rem;
      border-radius: 8px;
    }

    .info-item .label {
      font-size: 0.75rem;
      color: #64748b;
      display: block;
    }

    .info-item .value {
      font-weight: 500;
      color: #1e293b;
    }

    .pieces-list {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .piece-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem;
      background: #fef2f2;
      border-radius: 8px;
    }

    .piece-item.complete {
      background: #ecfdf5;
    }

    .piece-icon {
      font-size: 1.25rem;
    }

    .attention-list {
      margin: 0;
      padding-left: 1.5rem;
      color: #dc2626;
    }

    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 0.75rem;
      padding: 1rem 1.5rem;
      border-top: 1px solid #e2e8f0;
    }

    .empty-state {
      text-align: center;
      padding: 3rem;
      color: #94a3b8;
    }

    .empty-icon { font-size: 3rem; }

    @media (max-width: 1024px) {
      .stats-row, .exam-tabs {
        grid-template-columns: repeat(2, 1fr);
      }
    }
  `]
})
export class ExamsManagementComponent implements OnInit {
  exams = [
    { key: 'cep', name: 'CEP (CM2)', icon: '📚', count: 0 },
    { key: 'bepc', name: 'BEPC (3ème)', icon: '📖', count: 0 },
    { key: 'bac', name: 'BAC (Tle)', icon: '🎓', count: 0 }
  ];

  selectedExam = 'cep';
  candidates: Candidate[] = [];
  filteredCandidates: Candidate[] = [];
  classes: any[] = [];

  stats = {
    total: 0,
    eligible: 0,
    non_eligible: 0,
    dossier_complet: 0,
    moyenne_generale: 0
  };

  searchQuery = '';
  filterClass = '';
  filterSerie = '';

  showDetailModal = false;
  selectedCandidate: Candidate | null = null;

  defaultAvatar = '/assets/images/default-avatar.png';

  constructor(private http: HttpClient) {}

  ngOnInit() {
    this.loadCandidates();
    this.loadClasses();
    this.loadExamCounts();
  }

  selectExam(exam: string) {
    this.selectedExam = exam;
    this.filterClass = '';
    this.filterSerie = '';
    this.loadCandidates();
    this.loadClasses();
  }

  loadCandidates() {
    const params: any = {};
    if (this.filterClass) params.class_id = this.filterClass;
    if (this.filterSerie && this.selectedExam === 'bac') params.serie = this.filterSerie;

    this.http.get<any>(`/api/examens/${this.selectedExam}/candidates`, { params })
      .subscribe({
        next: (res) => {
          this.candidates = (res.candidates || []).map((c: any) => ({ ...c, selected: false }));
          this.filteredCandidates = this.candidates;
          this.stats = res.stats || this.stats;
        }
      });
  }

  loadClasses() {
    const endpoints: any = {
      cep: '/api/mp/classes?niveau=CM2',
      bepc: '/api/college/classes?niveau=3eme',
      bac: '/api/lycee/classes?niveau=Tle'
    };

    this.http.get<any>(endpoints[this.selectedExam])
      .subscribe({
        next: (res) => this.classes = res.data || res
      });
  }

  loadExamCounts() {
    // Load counts for each exam
    ['cep', 'bepc', 'bac'].forEach(exam => {
      this.http.get<any>(`/api/examens/${exam}/stats`)
        .subscribe({
          next: (res) => {
            const ex = this.exams.find(e => e.key === exam);
            if (ex) ex.count = res.total || 0;
          }
        });
    });
  }

  filterCandidates() {
    if (!this.searchQuery) {
      this.filteredCandidates = this.candidates;
      return;
    }

    const query = this.searchQuery.toLowerCase();
    this.filteredCandidates = this.candidates.filter(c =>
      c.student.nom.toLowerCase().includes(query) ||
      c.student.prenoms.toLowerCase().includes(query) ||
      c.student.matricule.toLowerCase().includes(query)
    );
  }

  toggleAll(event: any) {
    const checked = event.target.checked;
    this.filteredCandidates.forEach(c => c.selected = checked);
  }

  get selectedCount(): number {
    return this.candidates.filter(c => c.selected).length;
  }

  getExamName(): string {
    const names: any = { cep: 'CEP', bepc: 'BEPC', bac: 'BAC' };
    return names[this.selectedExam] || '';
  }

  viewCandidate(candidate: Candidate) {
    this.selectedCandidate = candidate;
    this.showDetailModal = true;
  }

  closeDetailModal() {
    this.showDetailModal = false;
    this.selectedCandidate = null;
  }

  printFiche(candidate: Candidate) {
    window.open(`/api/examens/${this.selectedExam}/fiches?student_ids[]=${candidate.student.id}`, '_blank');
  }

  printFiches() {
    const ids = this.candidates.filter(c => c.selected).map(c => c.student.id);
    if (ids.length === 0) return;
    
    const params = ids.map(id => `student_ids[]=${id}`).join('&');
    window.open(`/api/examens/${this.selectedExam}/fiches?${params}`, '_blank');
  }

  exportCSV() {
    window.open(`/api/examens/${this.selectedExam}/export?format=csv`, '_blank');
  }

  exportPDF() {
    window.open(`/api/examens/${this.selectedExam}/export?format=pdf`, '_blank');
  }

  exportOfficial() {
    const format = this.selectedExam === 'bac' ? 'office' : 'dgess';
    window.open(`/api/examens/${this.selectedExam}/export?format=${format}`, '_blank');
  }

  exportSelected() {
    const ids = this.candidates.filter(c => c.selected).map(c => c.student.id);
    if (ids.length === 0) return;
    
    this.http.post(`/api/examens/${this.selectedExam}/export`, {
      format: 'csv',
      student_ids: ids
    }, { responseType: 'blob' }).subscribe(blob => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `candidats_${this.selectedExam}_selection.csv`;
      a.click();
    });
  }
}
