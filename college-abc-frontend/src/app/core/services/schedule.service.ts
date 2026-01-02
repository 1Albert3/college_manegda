import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { environment } from '../../../environments/environment';

export interface CourseSlot {
  id?: number;
  day: 'Lundi' | 'Mardi' | 'Mercredi' | 'Jeudi' | 'Vendredi';
  startTime: string;
  endTime: string;
  subject: string;
  subject_id?: number;
  room: string;
  color: string;
  teacher?: string;
  teacher_id?: number;
  class_room?: string;
  class_room_id?: number;
}

export interface ScheduleEntry {
  id: number;
  class_room_id: number;
  subject_id: number;
  teacher_id: number;
  day_of_week: number;
  start_time: string;
  end_time: string;
  room?: string;
  subject?: { id: number; name: string; color?: string };
  teacher?: { id: number; name: string };
  class_room?: { id: number; name: string };
}

@Injectable({
  providedIn: 'root'
})
export class ScheduleService {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  /**
   * Récupère l'emploi du temps hebdomadaire d'un élève.
   */
  getSchedule(studentId: string | number): Observable<CourseSlot[]> {
    return this.http.get<CourseSlot[]>(`${this.apiUrl}/schedule/${studentId}`);
  }


  /**
   * Get schedule for a class
   */
  getClassSchedule(classId: number): Observable<ScheduleEntry[]> {
    return this.http.get<any>(`${this.apiUrl}/schedules/class/${classId}`).pipe(
      map(response => response.data || response),
      catchError(() => of([]))
    );
  }

  /**
   * Get teacher's schedule
   */
  getTeacherSchedule(teacherId: number): Observable<ScheduleEntry[]> {
    return this.http.get<any>(`${this.apiUrl}/schedules/teacher/${teacherId}`).pipe(
      map(response => response.data || response),
      catchError(() => of([]))
    );
  }

  /**
   * Get all schedules
   */
  getAllSchedules(): Observable<ScheduleEntry[]> {
    return this.http.get<any>(`${this.apiUrl}/schedules`).pipe(
      map(response => response.data || response),
      catchError(() => of([]))
    );
  }

  /**
   * Create schedule entry
   */
  createScheduleEntry(data: Partial<ScheduleEntry>): Observable<ScheduleEntry> {
    return this.http.post<any>(`${this.apiUrl}/schedules`, data).pipe(
      map(response => response.data || response)
    );
  }

  /**
   * Update schedule entry
   */
  updateScheduleEntry(id: number, data: Partial<ScheduleEntry>): Observable<ScheduleEntry> {
    return this.http.put<any>(`${this.apiUrl}/schedules/${id}`, data).pipe(
      map(response => response.data || response)
    );
  }

  /**
   * Delete schedule entry
   */
  deleteScheduleEntry(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/schedules/${id}`);
  }

  /**
   * Transform backend schedule entries to CourseSlot format
   */
  transformToSlots(entries: ScheduleEntry[]): CourseSlot[] {
    const dayMap: { [key: number]: 'Lundi' | 'Mardi' | 'Mercredi' | 'Jeudi' | 'Vendredi' } = {
      1: 'Lundi', 2: 'Mardi', 3: 'Mercredi', 4: 'Jeudi', 5: 'Vendredi'
    };

    return entries.map(entry => ({
      id: entry.id,
      day: dayMap[entry.day_of_week] || 'Lundi',
      startTime: entry.start_time,
      endTime: entry.end_time,
      subject: entry.subject?.name || '',
      subject_id: entry.subject_id,
      room: entry.room || '',
      color: entry.subject?.color || '#4F46E5',
      teacher: entry.teacher?.name || '',
      teacher_id: entry.teacher_id,
      class_room: entry.class_room?.name || '',
      class_room_id: entry.class_room_id
    }));
  }
}
