import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { environment } from '../../../environments/environment';

export interface Absence {
  id?: number;
  date: string;
  timeSlot: string;
  subject: string;
  reason: string;
  status: 'Justifiée' | 'Non Justifiée';
  type?: 'absent' | 'late';
  student_id?: number;
  justified?: boolean;
}

export interface AttendanceRecord {
  id: number;
  student_id: number;
  class_room_id: number;
  date: string;
  status: 'present' | 'absent' | 'late' | 'excused';
  subject_id?: number;
  comment?: string;
  student?: {
    id: number;
    first_name: string;
    last_name: string;
    matricule: string;
  };
}

export interface AttendanceStats {
  total_present: number;
  total_absent: number;
  total_late: number;
  attendance_rate: number;
}

@Injectable({
  providedIn: 'root'
})
export class AttendanceService {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  /**
   * Récupère la liste des absences d'un élève.
   */
  getAbsences(studentId: number): Observable<{ totalHours: number, list: Absence[] }> {
    return this.http.get<{ totalHours: number, list: Absence[] }>(`${this.apiUrl}/attendance/${studentId}`);
  }

  /**
   * Get attendance records for a class on a specific date
   */
  getClassAttendance(classId: string, date: string): Observable<AttendanceRecord[]> {
    return this.http.get<any>(`${this.apiUrl}/attendance/class/${classId}`, {
      params: { date }
    }).pipe(
      map(response => response.data || response),
      catchError(() => of([]))
    );
  }

  /**
   * Mark attendance for a student
   */
  markAttendance(data: { student_id: number; class_room_id: string; date: string; status: string; subject_id?: number }): Observable<AttendanceRecord> {
    return this.http.post<any>(`${this.apiUrl}/attendance`, data).pipe(
      map(response => response.data || response)
    );
  }

  /**
   * Bulk mark attendance for multiple students
   */
  bulkMarkAttendance(classId: string, date: string, records: { student_id: number; status: string }[]): Observable<any> {
    return this.http.post(`${this.apiUrl}/attendance/bulk`, {
      class_room_id: classId,
      date,
      records
    });
  }

  /**
   * Get attendance statistics for a class
   */
  getClassAttendanceStats(classId: number, period?: string): Observable<AttendanceStats> {
    let params = new HttpParams();
    if (period) params = params.set('period', period);
    
    return this.http.get<any>(`${this.apiUrl}/attendance/class/${classId}/stats`, { params }).pipe(
      map(response => response.data || response),
      catchError(() => of({ total_present: 0, total_absent: 0, total_late: 0, attendance_rate: 0 }))
    );
  }

  /**
   * Justify an absence
   */
  justifyAbsence(absenceId: number, reason: string, document?: File): Observable<any> {
    const formData = new FormData();
    formData.append('reason', reason);
    if (document) {
      formData.append('document', document);
    }
    return this.http.post(`${this.apiUrl}/attendance/${absenceId}/justify`, formData);
  }

  /**
   * Get student attendance history
   */
  getStudentAttendanceHistory(studentId: number, filters?: { start_date?: string; end_date?: string; type?: string }): Observable<Absence[]> {
    let params = new HttpParams();
    if (filters?.start_date) params = params.set('start_date', filters.start_date);
    if (filters?.end_date) params = params.set('end_date', filters.end_date);
    if (filters?.type) params = params.set('type', filters.type);

    return this.http.get<any>(`${this.apiUrl}/students/${studentId}/attendance`, { params }).pipe(
      map(response => response.data || response.list || []),
      catchError(() => of([]))
    );
  }

  // --- MATERNELLE / PRIMAIRE (MP) ---

  submitAttendanceMPBulk(payload: {
    class_id: string;
    date: string;
    type: 'absence' | 'retard';
    absents: { student_id: string; motif?: string; heure_arrivee?: string }[];
  }): Observable<any> {
    return this.http.post(`${this.apiUrl}/mp/attendance/bulk`, payload);
  }

  justifyAttendanceMP(id: string, payload: { statut: string; motif?: string }): Observable<any> {
    return this.http.patch(`${this.apiUrl}/mp/attendance/${id}/justify`, payload);
  }

  getAttendanceMP(filters: any): Observable<any> {
    let params = new HttpParams();
    Object.keys(filters).forEach(key => {
      if (filters[key]) params = params.set(key, filters[key]);
    });
    return this.http.get(`${this.apiUrl}/mp/attendance`, { params });
  }
}
