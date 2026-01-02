import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface Subject {
  id: number;
  name: string;
  code: string;
}

export interface Evaluation {
  id: number;
  subject_id: number;
  class_room_id: number;
  title: string;
  type: 'exam' | 'test' | 'quiz' | 'homework' | 'participation';
  max_score: number;
  coefficient: number;
  date: string;
  subject?: Subject;
  status?: string;
  description?: string;
  class_room?: { id: number; name: string };
}

export interface Grade {
  id?: number;
  evaluation_id: number;
  student_id: number;
  score: number;
  comment?: string;
  student?: any; // To be typed properly with Student interface
}

export interface ReportCard {
  student: any;
  overall_stats: {
    average_score: number;
    rank?: number;
    total_students?: number;
  };
  subjects: {
    [key: string]: {
      subject: string;
      average: number;
      rank?: number;
      grades: Grade[];
    }
  };
}

@Injectable({
  providedIn: 'root'
})
export class GradeService {
  private http = inject(HttpClient);
  // Assuming the module prefix is 'grade' based on laravel-modules convention + api.php v1 prefix
  private apiUrl = environment.apiUrl;

  // --- MATERNELLE / PRIMAIRE (MP) ---

  /**
   * Saisie de notes groupée pour la Maternelle / Primaire
   */
  submitGradesMPBulk(payload: {
    class_id: string;
    subject_id: string;
    school_year_id: string;
    trimestre: string;
    type_evaluation: 'IO' | 'DV' | 'CP' | 'TP';
    date_evaluation: string;
    notes: { student_id: string; note_obtenue: number; commentaire?: string }[];
    publish?: boolean;
  }): Observable<any> {
    return this.http.post(`${this.apiUrl}/mp/grades/bulk`, payload);
  }

  /**
   * Récupérer les matières du cycle MP
   */
  getSubjectsMP(niveau?: string): Observable<any[]> {
    let params = new HttpParams();
    if (niveau) params = params.set('niveau', niveau);
    return this.http.get<any[]>(`${this.apiUrl}/mp/subjects`, { params });
  }

  // --- BULLETINS MP ---

  getReportCardPreviewMP(classId: string, trimestre: string): Observable<any> {
    let params = new HttpParams().set('class_id', classId).set('trimestre', trimestre);
    return this.http.get(`${this.apiUrl}/mp/report-cards/preview`, { params });
  }

  generateReportCardsMP(payload: { class_id: string; trimestre: string; student_ids: string[] }): Observable<any> {
    return this.http.post(`${this.apiUrl}/mp/report-cards/generate`, payload);
  }

  publishReportCardsMP(payload: { class_id: string; trimestre: string }): Observable<any> {
    return this.http.post(`${this.apiUrl}/mp/report-cards/publish`, payload);
  }

  downloadAllReportCardsMP(classId: string, trimestre: string): Observable<Blob> {
    let params = new HttpParams().set('class_id', classId).set('trimestre', trimestre);
    return this.http.get(`${this.apiUrl}/mp/report-cards/download-all`, {
      params,
      responseType: 'blob'
    });
  }

  // --- LYCEE SPECIFIC (Nouveau Core v2) ---

  getSubjectsLycee(filters: { niveau?: string; serie?: string }): Observable<any[]> {
    let params = new HttpParams();
    if (filters.niveau) params = params.set('niveau', filters.niveau);
    if (filters.serie) params = params.set('serie', filters.serie);
    return this.http.get<any[]>(`${this.apiUrl}/lycee/subjects`, { params });
  }

  // --- BULLETINS LYCEE ---

  getReportCardPreviewLycee(classId: string, trimestre: string): Observable<any> {
    let params = new HttpParams().set('class_id', classId).set('trimestre', trimestre);
    return this.http.get(`${this.apiUrl}/lycee/report-cards/preview`, { params });
  }

  generateReportCardsLycee(payload: { class_id: string; trimestre: string; student_ids: string[] }): Observable<any> {
    return this.http.post(`${this.apiUrl}/lycee/report-cards/generate`, payload);
  }

  downloadAllReportCardsLycee(classId: string, trimestre: string): Observable<Blob> {
    let params = new HttpParams().set('class_id', classId).set('trimestre', trimestre);
    return this.http.get(`${this.apiUrl}/lycee/report-cards/download-all`, {
      params,
      responseType: 'blob'
    });
  }

  // --- COLLEGE SPECIFIC ---

  getSubjectsCollege(filters: { niveau?: string }): Observable<any[]> {
    let params = new HttpParams();
    if (filters.niveau) params = params.set('niveau', filters.niveau);
    return this.http.get<any[]>(`${this.apiUrl}/college/subjects`, { params });
  }

  getReportCardPreviewCollege(classId: string, trimestre: string): Observable<any> {
    let params = new HttpParams().set('class_id', classId).set('trimestre', trimestre);
    return this.http.get(`${this.apiUrl}/college/report-cards/preview`, { params });
  }

  generateReportCardsCollege(payload: { class_id: string; trimestre: string; student_ids: string[] }): Observable<any> {
    return this.http.post(`${this.apiUrl}/college/report-cards/generate`, payload);
  }

  downloadAllReportCardsCollege(classId: string, trimestre: string): Observable<Blob> {
    let params = new HttpParams().set('class_id', classId).set('trimestre', trimestre);
    return this.http.get(`${this.apiUrl}/college/report-cards/download-all`, {
      params,
      responseType: 'blob'
    });
  }

  /**
   * Saisie de notes groupée pour le Lycée
   */
  submitGradesLyceeBulk(payload: {
    class_id: string;
    subject_id: string;
    school_year_id: string;
    trimestre: string | number;
    type_evaluation: 'devoir' | 'compo'; 
    date_evaluation: string;
    coefficient?: number;
    grades: { student_id: string; note: number; appreciation?: string }[];
  }): Observable<any> {
    return this.http.post(`${this.apiUrl}/lycee/grades/bulk`, payload);
  }

  /**
   * Saisie de notes groupée pour le Collège
   */
  submitGradesCollegeBulk(payload: {
    class_id: string;
    subject_id: string;
    school_year_id: string;
    trimestre: number;
    type_evaluation: 'IE' | 'DS' | 'Comp' | 'TP' | 'CC'; // Matches database enum
    date_evaluation: string;
    coefficient?: number; 
    grades: { student_id: string; note: number; appreciation?: string }[];
  }): Observable<any> {
    return this.http.post(`${this.apiUrl}/college/grades/bulk`, payload);
  }

  /**
   * Récupérer les notes d'un élève (Lycée)
   */
  getStudentGradesLycee(studentId: string): Observable<any> {
    return this.http.get(`${this.apiUrl}/lycee/students/${studentId}/grades`);
  }

  /**
   * Récupérer les notes d'un élève (Collège)
   */
  getStudentGradesCollege(studentId: string): Observable<any> {
    return this.http.get(`${this.apiUrl}/college/students/${studentId}/grades`);
  }

  // --- ANCIENNES METHODES (Legacy / Prototype) ---
  // Gardées pour compatibilité temporaire, à migrer progressivement

  getEvaluations(filters: { class_id?: number; subject_id?: number; teacher_id?: number } = {}): Observable<Evaluation[]> {
    // ... implémentation existante adaptée ou laissée telle quelle si utilisée par d'autres modules non audités
    let params = new HttpParams();
    if (filters.class_id) params = params.set('class_id', filters.class_id);
    if (filters.subject_id) params = params.set('subject_id', filters.subject_id);
    
    // Fallback URL temporaire
    return this.http.get<Evaluation[]>(`${this.apiUrl}/evaluations`, { params });
  }

  createEvaluation(data: Partial<Evaluation>): Observable<Evaluation> {
    return this.http.post<Evaluation>(`${this.apiUrl}/evaluations`, data);
  }

  updateEvaluation(id: number, data: Partial<Evaluation>): Observable<Evaluation> {
    return this.http.put<Evaluation>(`${this.apiUrl}/evaluations/${id}`, data);
  }

  deleteEvaluation(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/evaluations/${id}`);
  }

  getGradesByEvaluation(evaluationId: number): Observable<Grade[]> {
    return this.http.get<Grade[]>(`${this.apiUrl}/grades/by-evaluation/${evaluationId}`);
  }

  recordGrade(data: Grade): Observable<Grade> {
    return this.http.post<Grade>(`${this.apiUrl}/grades/record`, data);
  }

  bulkRecordGrades(evaluationId: number, grades: { student_id: number; score: number; comment?: string }[]): Observable<any> {
    return this.http.post(`${this.apiUrl}/grades/bulk-record`, {
      evaluation_id: evaluationId,
      grades
    });
  }

  getStudentReport(studentId: number, academicYearId?: number): Observable<ReportCard> {
     // ...
     return this.http.get<ReportCard>(`${this.apiUrl}/grades/student/${studentId}/report`);
  }

  getClassReport(classId: number, academicYearId?: number): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/grades/class/${classId}/report`);
  }

  downloadReportCard(studentId: number, academicYearId: number): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/grades/student/${studentId}/report-card/${academicYearId}/download`, {
      responseType: 'blob'
    });
  }

  getSchoolStats(): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/grades/school-stats`);
  }
}
