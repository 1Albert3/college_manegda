import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export type SchoolCycle = 'mp' | 'college' | 'lycee';

export interface StudentDashboardData {
  student: {
    id: string;
    matricule: string;
    full_name: string;
    class_name: string;
    photo_url?: string;
  };
  school_year: string;
  grades_summary: {
    moyenne_generale: number;
    rang: number;
    effectif: number;
    trimestre: number;
  };
  recent_grades: Array<{
    subject: string;
    note: number;
    date: string;
    type: string;
    commentaire?: string;
  }>;
  attendance_summary: {
    absences: number;
    retards: number;
    heures_manquees: number;
  };
  upcoming_homework: Array<{
    subject: string;
    title: string;
    due_date: string;
    is_overdue: boolean;
  }>;
  schedule_today: Array<{
    time: string;
    subject: string;
    teacher: string;
    room: string;
  }>;
  announcements: Array<{
    title: string;
    content: string;
    date: string;
  }>;
}

@Injectable({
  providedIn: 'root'
})
export class StudentService {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl || 'http://localhost:8000/api/v1';

  /**
   * Get student dashboard data
   */
  getDashboard(): Observable<StudentDashboardData> {
    return this.http.get<StudentDashboardData>(`${this.apiUrl}/dashboard/student`);
  }

  /**
   * Get students by class ID (default to MP cycle)
   * @param classId The class ID
   * @param cycle Optional cycle ('mp', 'college', 'lycee')
   */
  getStudentsByClass(classId: string, cycle: 'mp' | 'college' | 'lycee' = 'mp'): Observable<any> {
    return this.http.get(`${this.apiUrl}/${cycle}/classes/${classId}/students`);
  }

  /**
   * Add a new student (for registration)
   * @param studentData The student data to create
   * @param cycle Optional cycle ('mp', 'college', 'lycee')
   */
  addStudent(studentData: any, cycle: 'mp' | 'college' | 'lycee' = 'mp'): Observable<any> {
    return this.http.post(`${this.apiUrl}/${cycle}/students`, studentData);
  }

  /**
   * Get all students (with optional filters)
   * @param params Optional query parameters for filtering
   * @param cycle Optional cycle ('mp', 'college', 'lycee')
   */
  getStudents(params?: any, cycle: 'mp' | 'college' | 'lycee' = 'mp'): Observable<any> {
    return this.http.get(`${this.apiUrl}/${cycle}/students`, { params });
  }

  /**
   * Get a single student by ID
   * @param studentId The student ID
   * @param cycle Optional cycle ('mp', 'college', 'lycee')
   */
  getStudent(studentId: string, cycle: 'mp' | 'college' | 'lycee' = 'mp'): Observable<any> {
    return this.http.get(`${this.apiUrl}/${cycle}/students/${studentId}`);
  }

  /**
   * Update a student
   * @param studentId The student ID
   * @param studentData The updated student data
   * @param cycle Optional cycle ('mp', 'college', 'lycee')
   */
  updateStudent(studentId: string, studentData: any, cycle: 'mp' | 'college' | 'lycee' = 'mp'): Observable<any> {
    return this.http.put(`${this.apiUrl}/${cycle}/students/${studentId}`, studentData);
  }

  /**
   * Delete a student
   * @param studentId The student ID
   * @param cycle Optional cycle ('mp', 'college', 'lycee')
   */
  deleteStudent(studentId: string, cycle: 'mp' | 'college' | 'lycee' = 'mp'): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${cycle}/students/${studentId}`);
  }

  /**
   * Register a new student with enrollment
   */
  register(cycle: SchoolCycle, data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/${cycle}/enrollments`, data);
  }

  /**
   * Get formatted list of all students (MP, College, Lycee) for Admin Dashboard
   */
  getAllStudentsForAdmin(): Observable<{data: any[]}> {
    return this.http.get<{data: any[]}>(`${this.apiUrl}/dashboard/direction/students`);
  }
}