import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { environment } from '../../../environments/environment';

export interface Homework {
  id: number;
  title: string;
  description: string;
  subject_id: number;
  class_room_id: number;
  teacher_id: number;
  due_date: string;
  created_at: string;
  status?: 'pending' | 'done' | 'late';
  subject?: { id: number; name: string; color?: string };
  class_room?: { id: number; name: string };
  teacher?: { id: number; name: string };
  attachments?: string[];
}

export interface LessonEntry {
  id: number;
  subject_id: number;
  class_room_id: number;
  teacher_id: number;
  date: string;
  title: string;
  content: string;
  homework?: string;
  homework_due_date?: string;
  subject?: { id: number; name: string };
  class_room?: { id: number; name: string };
}

export interface HomeworkSubmission {
  id: number;
  homework_id: number;
  student_id: number;
  submitted_at: string;
  file_path?: string;
  content?: string;
  grade?: number;
  comment?: string;
  status: 'submitted' | 'graded' | 'late';
  student?: { id: number; first_name: string; last_name: string };
}

@Injectable({
  providedIn: 'root'
})
export class HomeworkService {
  private http = inject(HttpClient);
  private v1Url = environment.apiUrl;

  // ============ HOMEWORK ============

  /**
   * Get all homework assignments for a class
   */
  getHomeworkByClass(classId: number): Observable<Homework[]> {
    return this.http.get<any>(`${this.v1Url}/homework/class/${classId}`).pipe(
      map(response => response.data || response),
      catchError(() => of([]))
    );
  }

  /**
   * Get homework for a student
   */
  getStudentHomework(studentId: number, status?: string): Observable<Homework[]> {
    let params = new HttpParams();
    if (status) params = params.set('status', status);

    return this.http.get<any>(`${this.v1Url}/students/${studentId}/homework`, { params }).pipe(
      map(response => response.data || response),
      catchError(() => of([]))
    );
  }

  /**
   * Get teacher's homework assignments
   */
  getTeacherHomework(teacherId: number): Observable<Homework[]> {
    return this.http.get<any>(`${this.v1Url}/teachers/${teacherId}/homework`).pipe(
      map(response => response.data || response),
      catchError(() => of([]))
    );
  }

  /**
   * Create homework assignment
   */
  createHomework(data: Partial<Homework>): Observable<Homework> {
    return this.http.post<any>(`${this.v1Url}/homework`, data).pipe(
      map(response => response.data || response)
    );
  }

  /**
   * Update homework
   */
  updateHomework(id: number, data: Partial<Homework>): Observable<Homework> {
    return this.http.put<any>(`${this.v1Url}/homework/${id}`, data).pipe(
      map(response => response.data || response)
    );
  }

  /**
   * Delete homework
   */
  deleteHomework(id: number): Observable<void> {
    return this.http.delete<void>(`${this.v1Url}/homework/${id}`);
  }

  // ============ LESSON ENTRIES (Cahier de texte) ============

  /**
   * Get lesson entries for a class
   */
  getLessonsByClass(classId: number, filters?: { subject_id?: number; start_date?: string; end_date?: string }): Observable<LessonEntry[]> {
    let params = new HttpParams();
    if (filters?.subject_id) params = params.set('subject_id', filters.subject_id);
    if (filters?.start_date) params = params.set('start_date', filters.start_date);
    if (filters?.end_date) params = params.set('end_date', filters.end_date);

    return this.http.get<any>(`${this.v1Url}/lessons/class/${classId}`, { params }).pipe(
      map(response => response.data || response),
      catchError(() => of([]))
    );
  }

  /**
   * Create lesson entry
   */
  createLesson(data: Partial<LessonEntry>): Observable<LessonEntry> {
    return this.http.post<any>(`${this.v1Url}/lessons`, data).pipe(
      map(response => response.data || response)
    );
  }

  /**
   * Update lesson entry
   */
  updateLesson(id: number, data: Partial<LessonEntry>): Observable<LessonEntry> {
    return this.http.put<any>(`${this.v1Url}/lessons/${id}`, data).pipe(
      map(response => response.data || response)
    );
  }

  // ============ SUBMISSIONS ============

  /**
   * Get submissions for a homework
   */
  getSubmissions(homeworkId: number): Observable<HomeworkSubmission[]> {
    return this.http.get<any>(`${this.v1Url}/homework/${homeworkId}/submissions`).pipe(
      map(response => response.data || response),
      catchError(() => of([]))
    );
  }

  /**
   * Submit homework (student)
   */
  submitHomework(homeworkId: number, data: { content?: string; file?: File }): Observable<HomeworkSubmission> {
    const formData = new FormData();
    if (data.content) formData.append('content', data.content);
    if (data.file) formData.append('file', data.file);

    return this.http.post<any>(`${this.v1Url}/homework/${homeworkId}/submit`, formData).pipe(
      map(response => response.data || response)
    );
  }

  /**
   * Grade submission (teacher)
   */
  gradeSubmission(submissionId: number, data: { grade: number; comment?: string }): Observable<HomeworkSubmission> {
    return this.http.post<any>(`${this.v1Url}/submissions/${submissionId}/grade`, data).pipe(
      map(response => response.data || response)
    );
  }
}
