import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface CourseSlot {
  day: 'Lundi' | 'Mardi' | 'Mercredi' | 'Jeudi' | 'Vendredi';
  startTime: string;
  endTime: string;
  subject: string;
  room: string;
  color: string; // Tailwind class like 'blue', 'green'
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
  getSchedule(studentId: number): Observable<CourseSlot[]> {
    return this.http.get<CourseSlot[]>(`${this.apiUrl}/schedule/${studentId}`);
  }
}
