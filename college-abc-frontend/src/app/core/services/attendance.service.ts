import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface Absence {
  date: string;
  timeSlot: string;
  subject: string;
  reason: string;
  status: 'Justifiée' | 'Non Justifiée';
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
}
