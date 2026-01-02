import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators, FormArray } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute, RouterModule } from '@angular/router';
import { environment } from '../../../../../environments/environment';

interface Class {
  id: string;
  nom: string;
  niveau: string;
  effectif_actuel: number;
}

interface Subject {
  id: string;
  code: string;
  nom: string;
  coefficient: number;
}

interface Student {
  id: string;
  matricule: string;
  nom: string;
  prenoms: string;
  full_name: string;
}

interface StudentGrade {
  student: Student;
  note_obtenue: number | null;
  commentaire: string;
}

@Component({
  selector: 'app-grade-entry',
  standalone: true,
  imports: [CommonModule, FormsModule, ReactiveFormsModule, RouterModule],
  template: `
    <div class="grade-entry-container">
      <div class="page-header">
        <h1>Saisie des Notes</h1>
        <p class="subtitle">Enregistrez les notes des élèves par évaluation</p>
      </div>

      <!-- Sélection de classe et matière -->
      <div class="selection-panel">
        <div class="selection-row">
          <div class="form-group">
            <label for="class">Classe *</label>
            <select 
              id="class" 
              [(ngModel)]="selectedClassId"
              (change)="onClassChange()">
              <option value="">Sélectionner une classe...</option>
              <option *ngFor="let cls of classes" [value]="cls.id">
                {{ cls.niveau }} - {{ cls.nom }} ({{ cls.effectif_actuel }} élèves)
              </option>
            </select>
          </div>

          <div class="form-group">
            <label for="subject">Matière *</label>
            <select 
              id="subject" 
              [(ngModel)]="selectedSubjectId"
              (change)="onSubjectChange()">
              <option value="">Sélectionner une matière...</option>
              <option *ngFor="let sub of subjects" [value]="sub.id">
                {{ sub.code }} - {{ sub.nom }} (Coef. {{ sub.coefficient }})
              </option>
            </select>
          </div>

          <div class="form-group">
            <label for="trimestre">Trimestre *</label>
            <select id="trimestre" [(ngModel)]="selectedTrimestre">
              <option value="1">1er Trimestre</option>
              <option value="2">2ème Trimestre</option>
              <option value="3">3ème Trimestre</option>
            </select>
          </div>

          <div class="form-group">
            <label for="type">Type d'évaluation *</label>
            <select id="type" [(ngModel)]="selectedType" (change)="onTypeChange()">
              <option value="IO">Interrogation Orale (/10)</option>
              <option value="DV">Devoir (/20)</option>
              <option value="CP">Composition (/100)</option>
              <option value="TP">Travaux Pratiques (/20)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="date">Date *</label>
            <input 
              type="date" 
              id="date" 
              [(ngModel)]="evaluationDate">
          </div>
        </div>

        <button 
          class="btn-load" 
          (click)="loadStudents()"
          [disabled]="!canLoadStudents()">
          Charger les élèves
        </button>
      </div>

      <!-- Barème info -->
      <div class="bareme-info" *ngIf="selectedType">
        <span class="bareme-label">📊 Barème:</span>
        <span class="bareme-value">{{ getBareme() }}</span>
        <span class="bareme-note">Les notes seront automatiquement converties sur 20</span>
      </div>

      <!-- Tableau des élèves -->
      <div class="grades-panel" *ngIf="students.length > 0">
        <div class="panel-header">
          <h2>
            {{ getSelectedClassName() }} - {{ getSelectedSubjectName() }}
            <span class="badge">{{ students.length }} élèves</span>
          </h2>
          <div class="panel-actions">
            <button class="btn-secondary" (click)="fillAllWithValue()">
              Remplir tout
            </button>
            <button class="btn-secondary" (click)="clearAll()">
              Effacer tout
            </button>
          </div>
        </div>

        <div class="grades-table-container">
          <table class="grades-table">
            <thead>
              <tr>
                <th class="col-num">#</th>
                <th class="col-matricule">Matricule</th>
                <th class="col-name">Nom & Prénoms</th>
                <th class="col-note">Note /{{ getBareme() }}</th>
                <th class="col-note20">Note /20</th>
                <th class="col-comment">Commentaire</th>
                <th class="col-status">Statut</th>
              </tr>
            </thead>
            <tbody>
              <tr *ngFor="let item of studentGrades; let i = index" [class.absent]="item.absent">
                <td class="col-num">{{ i + 1 }}</td>
                <td class="col-matricule">{{ item.student.matricule }}</td>
                <td class="col-name">{{ item.student.full_name }}</td>
                <td class="col-note">
                  <input 
                    type="number" 
                    [(ngModel)]="item.note_obtenue"
                    [min]="0"
                    [max]="getBareme()"
                    step="0.5"
                    (input)="calculateNoteSur20(item)"
                    [class.invalid]="isNoteInvalid(item)"
                    [disabled]="item.absent"
                    placeholder="0">
                </td>
                <td class="col-note20" [class.good]="item.note_sur_20 >= 10" [class.bad]="item.note_sur_20 < 10">
                  {{ item.note_sur_20 !== null ? (item.note_sur_20 | number:'1.2-2') : '-' }}
                </td>
                <td class="col-comment">
                  <input 
                    type="text" 
                    [(ngModel)]="item.commentaire"
                    placeholder="Commentaire..."
                    maxlength="200"
                    [disabled]="item.absent">
                </td>
                <td class="col-status">
                  <label class="checkbox-absent">
                    <input type="checkbox" [(ngModel)]="item.absent" (change)="onAbsentChange(item)">
                    Absent
                  </label>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Statistiques rapides -->
        <div class="quick-stats">
          <div class="stat">
            <span class="stat-value">{{ getFilledCount() }}</span>
            <span class="stat-label">Notes saisies</span>
          </div>
          <div class="stat">
            <span class="stat-value">{{ getAbsentCount() }}</span>
            <span class="stat-label">Absents</span>
          </div>
          <div class="stat">
            <span class="stat-value" [class.good]="getAverage() >= 10" [class.bad]="getAverage() < 10">
              {{ getAverage() | number:'1.2-2' }}
            </span>
            <span class="stat-label">Moyenne /20</span>
          </div>
          <div class="stat">
            <span class="stat-value">{{ getSuccessRate() | number:'1.0-0' }}%</span>
            <span class="stat-label">Taux réussite (≥10)</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="form-actions">
          <button 
            class="btn-secondary" 
            (click)="resetForm()">
            Annuler
          </button>
          <button 
            class="btn-primary" 
            (click)="saveGrades(false)"
            [disabled]="!canSave() || isSaving">
            {{ isSaving ? 'Enregistrement...' : 'Enregistrer (brouillon)' }}
          </button>
          <button 
            class="btn-success" 
            (click)="saveGrades(true)"
            [disabled]="!canPublish() || isSaving">
            {{ isSaving ? 'Publication...' : 'Publier les notes' }}
          </button>
        </div>

        <!-- Messages -->
        <div class="message success" *ngIf="successMessage">
          ✅ {{ successMessage }}
        </div>
        <div class="message error" *ngIf="errorMessage">
          ❌ {{ errorMessage }}
        </div>
      </div>

      <!-- État vide -->
      <div class="empty-state" *ngIf="students.length === 0 && !isLoading">
        <div class="empty-icon">📝</div>
        <h3>Sélectionnez une classe et une matière</h3>
        <p>Choisissez les paramètres ci-dessus pour commencer la saisie des notes.</p>
      </div>

      <!-- Chargement -->
      <div class="loading-state" *ngIf="isLoading">
        <div class="loader"></div>
        <p>Chargement des élèves...</p>
      </div>
    </div>
  `,
  styles: [`
    .grade-entry-container {
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
      margin: 0 0 0.5rem;
    }

    .subtitle {
      color: #64748b;
      margin: 0;
    }

    /* Selection Panel */
    .selection-panel {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      margin-bottom: 1.5rem;
    }

    .selection-row {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .form-group {
      display: flex;
      flex-direction: column;
    }

    .form-group label {
      font-size: 0.85rem;
      font-weight: 600;
      color: #475569;
      margin-bottom: 0.5rem;
    }

    .form-group select,
    .form-group input {
      padding: 0.75rem;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      font-size: 0.95rem;
      transition: border-color 0.3s;
    }

    .form-group select:focus,
    .form-group input:focus {
      outline: none;
      border-color: #4f46e5;
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .btn-load {
      background: linear-gradient(135deg, #4f46e5, #6366f1);
      color: white;
      border: none;
      padding: 0.875rem 2rem;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-load:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
    }

    .btn-load:disabled {
      background: #94a3b8;
      cursor: not-allowed;
    }

    /* Bareme Info */
    .bareme-info {
      background: linear-gradient(135deg, #eff6ff, #dbeafe);
      border: 1px solid #93c5fd;
      border-radius: 8px;
      padding: 0.75rem 1.25rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .bareme-label {
      font-weight: 600;
      color: #1e40af;
    }

    .bareme-value {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1d4ed8;
    }

    .bareme-note {
      font-size: 0.85rem;
      color: #3b82f6;
      margin-left: auto;
    }

    /* Grades Panel */
    .grades-panel {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      overflow: hidden;
    }

    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid #e2e8f0;
      background: #f8fafc;
    }

    .panel-header h2 {
      font-size: 1.1rem;
      color: #1e293b;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .badge {
      background: #4f46e5;
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
    }

    .panel-actions {
      display: flex;
      gap: 0.75rem;
    }

    .btn-secondary {
      background: white;
      border: 1px solid #e2e8f0;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      font-size: 0.85rem;
      color: #475569;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-secondary:hover {
      background: #f1f5f9;
      border-color: #cbd5e1;
    }

    /* Grades Table */
    .grades-table-container {
      overflow-x: auto;
    }

    .grades-table {
      width: 100%;
      border-collapse: collapse;
    }

    .grades-table th,
    .grades-table td {
      padding: 0.875rem 1rem;
      text-align: left;
      border-bottom: 1px solid #e2e8f0;
    }

    .grades-table th {
      background: #f1f5f9;
      font-size: 0.8rem;
      font-weight: 600;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .grades-table tr:hover {
      background: #fafbfc;
    }

    .grades-table tr.absent {
      background: #fef2f2;
      opacity: 0.7;
    }

    .col-num { width: 50px; text-align: center; color: #94a3b8; }
    .col-matricule { width: 120px; font-family: monospace; }
    .col-name { min-width: 200px; font-weight: 500; }
    .col-note { width: 100px; }
    .col-note20 { width: 80px; text-align: center; font-weight: 600; }
    .col-comment { min-width: 200px; }
    .col-status { width: 100px; }

    .col-note input,
    .col-comment input {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      font-size: 0.95rem;
    }

    .col-note input {
      text-align: center;
    }

    .col-note input.invalid {
      border-color: #ef4444;
      background: #fef2f2;
    }

    .col-note input:focus,
    .col-comment input:focus {
      outline: none;
      border-color: #4f46e5;
    }

    .col-note20.good { color: #10b981; }
    .col-note20.bad { color: #ef4444; }

    .checkbox-absent {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      cursor: pointer;
      font-size: 0.85rem;
      color: #64748b;
    }

    /* Quick Stats */
    .quick-stats {
      display: flex;
      gap: 2rem;
      padding: 1.25rem 1.5rem;
      background: #f8fafc;
      border-top: 1px solid #e2e8f0;
    }

    .stat {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .stat-value {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1e293b;
    }

    .stat-value.good { color: #10b981; }
    .stat-value.bad { color: #ef4444; }

    .stat-label {
      font-size: 0.75rem;
      color: #94a3b8;
      text-transform: uppercase;
    }

    /* Form Actions */
    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
      padding: 1.5rem;
      border-top: 1px solid #e2e8f0;
    }

    .btn-primary {
      background: #4f46e5;
      color: white;
      border: none;
      padding: 0.875rem 1.5rem;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-primary:hover:not(:disabled) {
      background: #4338ca;
    }

    .btn-success {
      background: #10b981;
      color: white;
      border: none;
      padding: 0.875rem 1.5rem;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-success:hover:not(:disabled) {
      background: #059669;
    }

    .btn-primary:disabled,
    .btn-success:disabled {
      background: #94a3b8;
      cursor: not-allowed;
    }

    /* Messages */
    .message {
      margin: 1rem 1.5rem 1.5rem;
      padding: 1rem;
      border-radius: 8px;
    }

    .message.success {
      background: #ecfdf5;
      color: #047857;
      border: 1px solid #a7f3d0;
    }

    .message.error {
      background: #fef2f2;
      color: #dc2626;
      border: 1px solid #fecaca;
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

    /* Loading */
    .loading-state {
      text-align: center;
      padding: 4rem 2rem;
    }

    .loader {
      width: 40px;
      height: 40px;
      border: 4px solid #e2e8f0;
      border-top-color: #4f46e5;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 0 auto 1rem;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Responsive */
    @media (max-width: 1200px) {
      .selection-row {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (max-width: 768px) {
      .selection-row {
        grid-template-columns: 1fr;
      }
      
      .quick-stats {
        flex-wrap: wrap;
        justify-content: center;
      }
    }
  `]
})
export class GradeEntryComponent implements OnInit {
  classes: Class[] = [];
  subjects: Subject[] = [];
  students: Student[] = [];
  studentGrades: any[] = [];

  selectedClassId = '';
  selectedSubjectId = '';
  selectedTrimestre = '1';
  selectedType = 'DV';
  evaluationDate = new Date().toISOString().split('T')[0];

  isLoading = false;
  isSaving = false;
  successMessage = '';
  errorMessage = '';

  private baremes: { [key: string]: number } = {
    'IO': 10,
    'DV': 20,
    'CP': 100,
    'TP': 20
  };

  constructor(
    private http: HttpClient,
    private route: ActivatedRoute
  ) {}

  private apiUrl = environment.apiUrl || 'http://localhost:8000/api';

  ngOnInit() {
    this.loadClasses();
    this.loadSubjects();
  }

  loadClasses() {
    this.http.get<any>(`${this.apiUrl}/mp/classes?active=true`)
      .subscribe({
        next: (res) => this.classes = res.data || res,
        error: (err) => console.error('Error loading classes', err)
      });
  }

  loadSubjects() {
    const classParam = this.selectedClassId ? `&class_id=${encodeURIComponent(this.selectedClassId)}` : '';
    this.http.get<any>(`${this.apiUrl}/mp/subjects?active=true${classParam}`)
      .subscribe({
        next: (res) => this.subjects = res.data || res,
        error: (err) => console.error('Error loading subjects', err)
      });
  }

  onClassChange() {
    this.students = [];
    this.studentGrades = [];
    this.selectedSubjectId = '';
    this.loadSubjects();
  }

  onSubjectChange() {
    // Optionally reload with specific subject
  }

  onTypeChange() {
    // Recalculate all notes if type changes
    this.studentGrades.forEach(item => this.calculateNoteSur20(item));
  }

  canLoadStudents(): boolean {
    return !!(this.selectedClassId && this.selectedSubjectId && this.evaluationDate);
  }

  loadStudents() {
    if (!this.canLoadStudents()) return;

    this.isLoading = true;
    this.successMessage = '';
    this.errorMessage = '';

    this.http.get<any>(`${this.apiUrl}/mp/classes/${this.selectedClassId}/students`)
      .subscribe({
        next: (res) => {
          this.students = res.students || res.data || res;
          this.initializeGrades();
          this.isLoading = false;
        },
        error: (err) => {
          this.errorMessage = 'Erreur lors du chargement des élèves';
          this.isLoading = false;
        }
      });
  }

  initializeGrades() {
    this.studentGrades = this.students.map(student => ({
      student,
      note_obtenue: null,
      note_sur_20: null,
      commentaire: '',
      absent: false
    }));
  }

  getBareme(): number {
    return this.baremes[this.selectedType] || 20;
  }

  calculateNoteSur20(item: any) {
    if (item.note_obtenue === null || item.note_obtenue === '') {
      item.note_sur_20 = null;
      return;
    }

    const bareme = this.getBareme();
    const note = parseFloat(item.note_obtenue);

    if (isNaN(note) || note < 0 || note > bareme) {
      item.note_sur_20 = null;
      return;
    }

    item.note_sur_20 = Math.round((note / bareme) * 20 * 100) / 100;
  }

  isNoteInvalid(item: any): boolean {
    if (item.note_obtenue === null || item.note_obtenue === '') return false;
    const note = parseFloat(item.note_obtenue);
    return isNaN(note) || note < 0 || note > this.getBareme();
  }

  onAbsentChange(item: any) {
    if (item.absent) {
      item.note_obtenue = null;
      item.note_sur_20 = null;
      item.commentaire = 'Absent';
    } else {
      item.commentaire = '';
    }
  }

  getSelectedClassName(): string {
    const cls = this.classes.find(c => c.id === this.selectedClassId);
    return cls ? `${cls.niveau} - ${cls.nom}` : '-';
  }

  getSelectedSubjectName(): string {
    const sub = this.subjects.find(s => s.id === this.selectedSubjectId);
    return sub ? sub.nom : '-';
  }

  getFilledCount(): number {
    return this.studentGrades.filter(g => g.note_obtenue !== null && !g.absent).length;
  }

  getAbsentCount(): number {
    return this.studentGrades.filter(g => g.absent).length;
  }

  getAverage(): number {
    const validGrades = this.studentGrades.filter(g => g.note_sur_20 !== null && !g.absent);
    if (validGrades.length === 0) return 0;
    
    const sum = validGrades.reduce((acc, g) => acc + g.note_sur_20, 0);
    return sum / validGrades.length;
  }

  getSuccessRate(): number {
    const validGrades = this.studentGrades.filter(g => g.note_sur_20 !== null && !g.absent);
    if (validGrades.length === 0) return 0;
    
    const passing = validGrades.filter(g => g.note_sur_20 >= 10).length;
    return (passing / validGrades.length) * 100;
  }

  fillAllWithValue() {
    const value = prompt('Entrez la note à appliquer à tous les élèves:');
    if (value === null) return;
    
    const note = parseFloat(value);
    if (isNaN(note) || note < 0 || note > this.getBareme()) {
      alert(`La note doit être entre 0 et ${this.getBareme()}`);
      return;
    }

    this.studentGrades.forEach(item => {
      if (!item.absent) {
        item.note_obtenue = note;
        this.calculateNoteSur20(item);
      }
    });
  }

  clearAll() {
    if (!confirm('Effacer toutes les notes ?')) return;
    
    this.studentGrades.forEach(item => {
      item.note_obtenue = null;
      item.note_sur_20 = null;
      item.commentaire = '';
      item.absent = false;
    });
  }

  canSave(): boolean {
    return this.getFilledCount() > 0 || this.getAbsentCount() > 0;
  }

  canPublish(): boolean {
    // Toutes les notes doivent être saisies (sauf absents)
    const nonAbsent = this.studentGrades.filter(g => !g.absent);
    return nonAbsent.length > 0 && nonAbsent.every(g => g.note_obtenue !== null);
  }

  saveGrades(publish: boolean = false) {
    if (!this.canSave()) return;

    this.isSaving = true;
    this.successMessage = '';
    this.errorMessage = '';

    const notes = this.studentGrades
      .filter(g => g.note_obtenue !== null && !g.absent)
      .map(g => ({
        student_id: g.student.id,
        note_obtenue: g.note_obtenue,
        commentaire: g.commentaire
      }));

    const payload = {
      class_id: this.selectedClassId,
      subject_id: this.selectedSubjectId,
      school_year_id: 'current', // Le backend récupérera l'année courante
      trimestre: this.selectedTrimestre,
      type_evaluation: this.selectedType,
      date_evaluation: this.evaluationDate,
      notes: notes,
      publish: publish
    };

    this.http.post(`${this.apiUrl}/mp/grades/bulk`, payload)
      .subscribe({
        next: (res: any) => {
          this.isSaving = false;
          this.successMessage = publish 
            ? `${res.created} note(s) publiée(s) avec succès !`
            : `${res.created} note(s) enregistrée(s) (brouillon)`;
        },
        error: (err) => {
          this.isSaving = false;
          this.errorMessage = err.error?.message || 'Erreur lors de l\'enregistrement';
        }
      });
  }

  resetForm() {
    if (this.studentGrades.some(g => g.note_obtenue !== null) && 
        !confirm('Annuler les modifications non sauvegardées ?')) {
      return;
    }
    
    this.initializeGrades();
    this.successMessage = '';
    this.errorMessage = '';
  }
}
