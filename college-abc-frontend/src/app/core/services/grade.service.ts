import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface Grade {
  subject: string;
  marks: number[];
  average: number;
  classAverage: number;
  teacher: string;
}

export interface ReportCard {
  studentId: number;
  trimestre: number;
  grades: Grade[];
  generalAverage: number;
  rank: number;
  appreciation: string;
}

@Injectable({
  providedIn: 'root'
})
export class GradeService {
  private http = inject(HttpClient);
  private apiUrl = environment.apiUrl;

  /**
   * Récupère le bulletin de notes d'un élève pour un trimestre donné.
   */
  getReportCard(studentId: number, trimestre: number): Observable<ReportCard> {
    return this.http.get<ReportCard>(`${this.apiUrl}/grades/${studentId}/${trimestre}`);
  }
}
